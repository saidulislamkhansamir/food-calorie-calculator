<?php
/**
 * Ad Networks monetization admin class.
 *
 * @package FCC\Admin
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Manages ad network configuration and frontend slot rendering.
 * Loaded unconditionally — static helpers are called by the shortcode.
 */
class Ads {

	const OPTION_KEY = 'fcc_ads_config';

	// -------------------------------------------------------------------------
	// Wiring
	// -------------------------------------------------------------------------

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_save_ads', $this, 'ajax_save' );
	}

	// -------------------------------------------------------------------------
	// Admin page entry-point
	// -------------------------------------------------------------------------

	public function page_ads(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-ads.php';
	}

	// -------------------------------------------------------------------------
	// AJAX save
	// -------------------------------------------------------------------------

	public function ajax_save(): void {
		check_ajax_referer( 'fcc_ads_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted   = isset( $_POST['networks'] ) ? (array) wp_unslash( $_POST['networks'] ) : [];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$gen_post = isset( $_POST['general'] ) ? (array) wp_unslash( $_POST['general'] ) : [];

		$config             = [];
		$config['general']  = [
			'enabled'    => ! empty( $gen_post['enabled'] ),
			'show_label' => ! empty( $gen_post['show_label'] ),
			'label_text' => sanitize_text_field( $gen_post['label_text'] ?? 'Advertisement' ),
		];

		$allowed_positions = [ 'above_search', 'before_results', 'after_results', 'below_calculator' ];

		foreach ( self::get_network_definitions() as $key => $def ) {
			$n = isset( $posted[ $key ] ) ? (array) $posted[ $key ] : [];
			$config['networks'][ $key ] = [
				'enabled'     => ! empty( $n['enabled'] ),
				'position'    => in_array( $n['position'] ?? '', $allowed_positions, true ) ? $n['position'] : 'after_results',
				// Trusted admin HTML — must not use esc_url_raw or wp_kses here.
				'head_script' => wp_unslash( $n['head_script'] ?? '' ),
				'ad_code'     => wp_unslash( $n['ad_code'] ?? '' ),
			];
		}

		update_option( self::OPTION_KEY, $config );
		wp_send_json_success( [ 'saved' => true ] );
	}

	// -------------------------------------------------------------------------
	// Config helpers (static — used by shortcode on frontend)
	// -------------------------------------------------------------------------

	/**
	 * Merged saved config with defaults. Always returns full structure.
	 *
	 * @return array{general: array, networks: array}
	 */
	public static function get_config(): array {
		$saved = (array) get_option( self::OPTION_KEY, [] );

		$general = array_merge(
			[ 'enabled' => true, 'show_label' => true, 'label_text' => 'Advertisement' ],
			$saved['general'] ?? []
		);

		$result = [ 'general' => $general, 'networks' => [] ];

		foreach ( self::get_network_definitions() as $key => $def ) {
			$saved_n = $saved['networks'][ $key ] ?? [];
			$result['networks'][ $key ] = array_merge(
				[
					'enabled'     => false,
					'position'    => 'after_results',
					'head_script' => '',
					'ad_code'     => '',
				],
				$saved_n,
				// Always override from definition (non-editable meta).
				[
					'name'         => $def['name'],
					'category'     => $def['category'],
					'description'  => $def['description'],
					'cpm_range'    => $def['cpm_range'],
					'colour'       => $def['colour'],
					'badge'        => $def['badge'] ?? '',
					'head_example' => $def['head_example'],
					'unit_example' => $def['unit_example'],
				]
			);
		}

		return $result;
	}

	/**
	 * Build ad HTML for a given position. Returns empty string when no networks
	 * are configured for that position or ads are globally disabled.
	 */
	public static function get_slot_html( string $position ): string {
		$config = self::get_config();
		if ( ! $config['general']['enabled'] ) {
			return '';
		}

		$html = '';
		foreach ( $config['networks'] as $key => $n ) {
			if ( ! $n['enabled'] ) continue;
			if ( $n['position'] !== $position ) continue;
			if ( empty( $n['ad_code'] ) ) continue;

			$label = '';
			if ( $config['general']['show_label'] ) {
				$label = '<p class="fcc-ad-label">' . esc_html( $config['general']['label_text'] ) . '</p>';
			}
			// Ad code is admin-only trusted HTML (may contain <script> tags).
			$html .= '<div class="fcc-ad-unit fcc-ad-unit--' . esc_attr( $key ) . '">'
				. $label
				. $n['ad_code'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				. '</div>';
		}

		return $html;
	}

	/**
	 * Get all head scripts for enabled networks (output in wp_footer).
	 */
	public static function get_head_scripts(): string {
		$config = self::get_config();
		if ( ! $config['general']['enabled'] ) {
			return '';
		}

		$scripts = '';
		foreach ( $config['networks'] as $n ) {
			if ( ! $n['enabled'] ) continue;
			if ( empty( $n['head_script'] ) ) continue;
			$scripts .= "\n" . $n['head_script'] . "\n";
		}

		return $scripts;
	}

	/** True if any network is enabled. */
	public static function has_active_networks(): bool {
		$config = self::get_config();
		if ( ! $config['general']['enabled'] ) return false;
		foreach ( $config['networks'] as $n ) {
			if ( $n['enabled'] ) return true;
		}
		return false;
	}

	// -------------------------------------------------------------------------
	// Network definitions
	// -------------------------------------------------------------------------

	public static function get_network_definitions(): array {
		return [

			// ── Premium Display ───────────────────────────────────────────────
			'mediavine' => [
				'name'        => 'Mediavine',
				'category'    => 'Premium Display',
				'description' => 'Managed premium display. Requires 50k sessions/mo. Sets ads automatically — just add the head script.',
				'cpm_range'   => '£10–25',
				'colour'      => '#7B2D8B',
				'badge'       => 'Premium',
				'head_example' => '<!-- Mediavine Script Tag -->' . "\n" . '<script data-noptimize="1" data-cfasync="false" data-no-lazy="1" async src="//scripts.mediavine.com/tags/YOUR-SITE-ID.js"></script>',
				'unit_example' => '',
			],

			'raptive' => [
				'name'        => 'Raptive (AdThrive)',
				'category'    => 'Premium Display',
				'description' => 'Top-tier managed ads (formerly AdThrive). Requires 100k pageviews/mo. Auto-places — just add the head script.',
				'cpm_range'   => '£12–30',
				'colour'      => '#E31837',
				'badge'       => 'Premium',
				'head_example' => '<script async src="https://cdn.adthrive.com/sites/YOUR-SITE-ID/ads.min.js" data-site="YOUR-SITE-ID" data-adthrive-ads-manual="false"></script>',
				'unit_example' => '',
			],

			'monumetric' => [
				'name'        => 'Monumetric',
				'category'    => 'Premium Display',
				'description' => 'Managed display ads for publishers with 10k+ monthly pageviews.',
				'cpm_range'   => '£6–15',
				'colour'      => '#2C3E8C',
				'badge'       => '',
				'head_example' => '<!-- Monumetric Header Tag -->' . "\n" . '<script async src="https://tag.brandcdn.com/autoscript/YOUR-ID/mono.js"></script>',
				'unit_example' => '',
			],

			// ── Programmatic ──────────────────────────────────────────────────
			'adsense' => [
				'name'        => 'Google AdSense',
				'category'    => 'Programmatic',
				'description' => "World's largest ad network. No minimum traffic. Ideal first choice for new publishers.",
				'cpm_range'   => '£2–8 (UK health)',
				'colour'      => '#4285F4',
				'badge'       => 'Top pick',
				'head_example' => '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>',
				'unit_example' => '<ins class="adsbygoogle"' . "\n" . '     style="display:block"' . "\n" . '     data-ad-client="ca-pub-XXXXXXXXXXXXXXXXX"' . "\n" . '     data-ad-slot="XXXXXXXXXX"' . "\n" . '     data-ad-format="auto"' . "\n" . '     data-full-width-responsive="true"></ins>' . "\n" . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>',
			],

			'ezoic' => [
				'name'        => 'Ezoic',
				'category'    => 'Programmatic',
				'description' => 'AI-driven ad optimisation. No minimum traffic. Often beats AdSense CPMs.',
				'cpm_range'   => '£4–12',
				'colour'      => '#0069AA',
				'badge'       => '',
				'head_example' => '<script async src="//www.ezojs.com/ezoic/sa.min.js"></script>' . "\n" . '<script>window.ezstandalone = window.ezstandalone || {}; ezstandalone.cmd = ezstandalone.cmd || [];</script>',
				'unit_example' => '<div id="ezoic-pub-ad-placeholder-XXX"></div>',
			],

			'media_net' => [
				'name'        => 'Media.net',
				'category'    => 'Programmatic',
				'description' => 'Yahoo–Bing contextual ads. Strong CPMs for health & nutrition content.',
				'cpm_range'   => '£2–7',
				'colour'      => '#FF5933',
				'badge'       => '',
				'head_example' => '<script async src="https://contextual.media.net/dmedianet.js?cid=XXXXXXXXX" crossorigin="anonymous"></script>',
				'unit_example' => '<div id="YOUR-SITE-ID" data-section="YOUR-SECTION"></div>',
			],

			'amazon_aps' => [
				'name'        => 'Amazon Publisher Services',
				'category'    => 'Programmatic',
				'description' => "Amazon's header bidding stack. Typically boosts existing ad revenue by 20–40%.",
				'cpm_range'   => '+20–40% uplift',
				'colour'      => '#FF9900',
				'badge'       => '',
				'head_example' => '<script>' . "\n" . 'window.apstag={init:function(){},fetchBids:function(){},setDisplayBids:function(){},targetingKeys:function(){return[]}};' . "\n" . '</script>' . "\n" . '<script async src="https://c.amazon-adsystem.com/aax2/apstag.js"></script>',
				'unit_example' => '',
			],

			// ── Direct / Marketplace ──────────────────────────────────────────
			'buysellads' => [
				'name'        => 'BuySellAds',
				'category'    => 'Direct / Marketplace',
				'description' => 'Connects publishers with premium direct advertisers. Good for health brands.',
				'cpm_range'   => '£5–20 (direct)',
				'colour'      => '#CC0000',
				'badge'       => '',
				'head_example' => '',
				'unit_example' => '<script async src="https://m.servedby-buysellads.com/monetization.js" type="text/javascript"></script>' . "\n" . '<script type="text/javascript">' . "\n" . '(window.BSA = window.BSA || {}).ads = window.BSA.ads || [];' . "\n" . 'window.BSA.ads.push({ serve: {} });' . "\n" . '</script>' . "\n" . '<div class="bsa_pro"></div>',
			],

			'carbon_ads' => [
				'name'        => 'Carbon Ads',
				'category'    => 'Direct / Marketplace',
				'description' => 'Single high-quality ad per page. Very popular in health, tech & design niches.',
				'cpm_range'   => '£8–20 (niche)',
				'colour'      => '#1DAFEC',
				'badge'       => '',
				'head_example' => '',
				'unit_example' => '<script async type="text/javascript" src="//cdn.carbonads.com/carbon.js?serve=YOUR-CODE&placement=YOUR-DOMAIN" id="_carbonads_js"></script>',
			],

			// ── Performance ───────────────────────────────────────────────────
			'adsterra' => [
				'name'        => 'Adsterra',
				'category'    => 'Performance',
				'description' => 'High fill-rate display & interstitial network. Good for global traffic.',
				'cpm_range'   => '£1–5',
				'colour'      => '#1B75BC',
				'badge'       => '',
				'head_example' => '',
				'unit_example' => '<script async data-cfasync="false" src="//pl-XXXXXXXX.adsterra.com/XXXXXXXX/invoke.js"></script>',
			],

			'propellerads' => [
				'name'        => 'PropellerAds',
				'category'    => 'Performance',
				'description' => 'Monetise with push notifications + on-page display. High fill globally.',
				'cpm_range'   => '£1–4',
				'colour'      => '#FD6B22',
				'badge'       => '',
				'head_example' => '',
				'unit_example' => '<div id="container-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"></div>' . "\n" . '<script async src="https://www.profitabledisplaynow.com/XXXXXXXX/invoke.js" data-cfasync="false"></script>',
			],

			'hilltopads' => [
				'name'        => 'HilltopAds',
				'category'    => 'Performance',
				'description' => 'Direct display network. Competitive CPMs for health & lifestyle content.',
				'cpm_range'   => '£1–4',
				'colour'      => '#37A64A',
				'badge'       => '',
				'head_example' => '',
				'unit_example' => '<script async src="https://cdn.hilltopads.com/widget/XXXXXXXX.js" data-cfasync="false"></script>',
			],

			// ── Native ────────────────────────────────────────────────────────
			'taboola' => [
				'name'        => 'Taboola',
				'category'    => 'Native',
				'description' => 'Content discovery widgets. Requires ~1M monthly pageviews.',
				'cpm_range'   => '£2–8',
				'colour'      => '#0D47A1',
				'badge'       => '',
				'head_example' => '<script>' . "\n" . 'window._taboola = window._taboola || [];' . "\n" . '_taboola.push({article:"auto"});' . "\n" . '</script>',
				'unit_example' => '<div id="taboola-below-article-thumbnails"></div>' . "\n" . '<script>' . "\n" . 'window._taboola = window._taboola || [];' . "\n" . '_taboola.push({mode:"thumbnails-a",container:"taboola-below-article-thumbnails",placement:"Below Article Thumbnails",target_type:"mix"});' . "\n" . '</script>',
			],

			'outbrain' => [
				'name'        => 'Outbrain',
				'category'    => 'Native',
				'description' => 'Native content recommendation widgets for premium publishers.',
				'cpm_range'   => '£2–8',
				'colour'      => '#E44E2C',
				'badge'       => '',
				'head_example' => '<script async src="https://widgets.outbrain.com/outbrain.js"></script>',
				'unit_example' => '<div class="OUTBRAIN" data-src="YOUR-PAGE-URL" data-widget-id="YOUR-WIDGET-ID" data-ob-template="YOUR-TEMPLATE"></div>',
			],

			'mgid' => [
				'name'        => 'MGID',
				'category'    => 'Native',
				'description' => 'Native ad network with strong health & lifestyle advertiser base.',
				'cpm_range'   => '£1–6',
				'colour'      => '#00A651',
				'badge'       => '',
				'head_example' => '<script async src="//jsc.mgid.com/YOUR-DOMAIN.js"></script>',
				'unit_example' => '<div id="M_YOUR-WIDGET-ID"></div>',
			],

			// ── Specialist ────────────────────────────────────────────────────
			'infolinks' => [
				'name'        => 'Infolinks',
				'category'    => 'Specialist',
				'description' => 'In-text & display ads. Zero minimum traffic — easy to start.',
				'cpm_range'   => '£1–4',
				'colour'      => '#FF5722',
				'badge'       => '',
				'head_example' => '<script>' . "\n" . 'var infolinks_pid = XXXXXXX;' . "\n" . 'var infolinks_wsid = 0;' . "\n" . '</script>' . "\n" . '<script async src="https://resources.infolinks.com/js/infolinks_main.js"></script>',
				'unit_example' => '',
			],

			'sovrn' => [
				'name'        => 'Sovrn //Commerce',
				'category'    => 'Specialist',
				'description' => 'Monetises existing content links + display. Pairs well with affiliate links.',
				'cpm_range'   => '£1–5',
				'colour'      => '#0093D0',
				'badge'       => '',
				'head_example' => '<script src="https://platform.viglink.com/api/viglink.js?key=YOUR-KEY" type="text/javascript" async></script>',
				'unit_example' => '',
			],

			'setupad' => [
				'name'        => 'Setupad',
				'category'    => 'Specialist',
				'description' => 'Header bidding optimisation platform for mid-tier publishers (100k+ visits).',
				'cpm_range'   => '£5–15',
				'colour'      => '#6C2DC7',
				'badge'       => '',
				'head_example' => '<script async src="https://cdn.setupad.io/YOUR-SITE-ID/setupad.js"></script>',
				'unit_example' => '<div class="setupad-ad" data-zone="YOUR-ZONE-ID"></div>',
			],

			'adpushup' => [
				'name'        => 'AdPushup',
				'category'    => 'Specialist',
				'description' => 'Ad revenue optimisation platform. Requires 1M+ monthly visits.',
				'cpm_range'   => '£8–20 (managed)',
				'colour'      => '#E91E63',
				'badge'       => 'Enterprise',
				'head_example' => '<script async src="https://cdn.adpushup.com/YOUR-ID/adpushup.js" crossorigin="anonymous"></script>',
				'unit_example' => '',
			],

		];
	}
}
