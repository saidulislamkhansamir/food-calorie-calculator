<?php
/**
 * Affiliate Links admin controller.
 *
 * Handles the Affiliate Links settings page and AJAX save handler.
 * Config is stored in WP option `fcc_affiliate_config`.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Affiliates {

	/** Option key used to store the affiliate config. */
	const OPTION_KEY = 'fcc_affiliate_config';

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_save_affiliates', $this, 'ajax_save' );
	}

	public function page_affiliates(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-affiliates.php';
	}

	// -------------------------------------------------------------------------
	// AJAX save.
	// -------------------------------------------------------------------------

	public function ajax_save(): void {
		check_ajax_referer( 'fcc_affiliates_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$posted  = $_POST['retailers'] ?? [];
		$general = $_POST['general']   ?? [];

		if ( ! is_array( $posted ) ) {
			wp_send_json_error( [ 'message' => 'Invalid payload.' ] );
		}

		$retailers = self::get_retailer_definitions();
		$config    = [];

		// General settings.
		$config['general'] = [
			'button_style'   => in_array( $general['button_style'] ?? '', [ 'pill', 'button', 'text' ], true )
				? $general['button_style'] : 'pill',
			'show_icon'      => ! empty( $general['show_icon'] ),
			'label_prefix'   => sanitize_text_field( $general['label_prefix'] ?? 'Buy on' ),
			'open_new_tab'   => ! empty( $general['open_new_tab'] ),
		];

		// Per-retailer config.
		foreach ( $retailers as $key => $def ) {
			$r = $posted[ $key ] ?? [];
			$config['retailers'][ $key ] = [
				'enabled'       => ! empty( $r['enabled'] ),
				'tracking_id'   => sanitize_text_field( $r['tracking_id'] ?? '' ),
				'custom_label'  => sanitize_text_field( $r['custom_label'] ?? '' ),
				'url_template'  => sanitize_text_field( $r['url_template'] ?? $def['url_template'] ),
			];
		}

		update_option( self::OPTION_KEY, $config );
		wp_send_json_success( [ 'saved' => true ] );
	}

	// -------------------------------------------------------------------------
	// Public helpers.
	// -------------------------------------------------------------------------

	/**
	 * Get the stored config, merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_config(): array {
		$saved     = get_option( self::OPTION_KEY, [] );
		$retailers = self::get_retailer_definitions();

		$general = array_merge(
			[ 'button_style' => 'pill', 'show_icon' => true, 'label_prefix' => 'Buy on', 'open_new_tab' => true ],
			$saved['general'] ?? []
		);

		$result = [ 'general' => $general, 'retailers' => [] ];

		foreach ( $retailers as $key => $def ) {
			$saved_r = $saved['retailers'][ $key ] ?? [];
			// Heal templates previously broken by esc_url_raw() stripping braces.
			$saved_tpl = $saved_r['url_template'] ?? '';
			if ( $saved_tpl && ! str_contains( $saved_tpl, '{QUERY}' ) && str_contains( $saved_tpl, 'QUERY' ) ) {
				$saved_tpl = str_replace( 'QUERY', '{QUERY}', $saved_tpl );
				$saved_tpl = str_replace( '{ID}', '{ID}', $saved_tpl ); // already fine
				$saved_tpl = str_replace( 'tag=ID', 'tag={ID}', $saved_tpl );
				$saved_tpl = str_replace( 'aff=ID', 'aff={ID}', $saved_tpl );
				$saved_tpl = str_replace( 'ref=ID', 'ref={ID}', $saved_tpl );
				$saved_tpl = str_replace( 'rcode=ID', 'rcode={ID}', $saved_tpl );
				$saved_tpl = str_replace( 'affil=ID', 'affil={ID}', $saved_tpl );
				$saved_tpl = str_replace( 'awc=ID', 'awc={ID}', $saved_tpl );
				$saved_r['url_template'] = $saved_tpl;
			}

			$result['retailers'][ $key ] = array_merge(
				[
					'enabled'      => false,
					'tracking_id'  => '',
					'custom_label' => '',
					'url_template' => $def['url_template'],
				],
				$saved_r,
				[
					// Always include display-only data from definition.
					'name'        => $def['name'],
					'id_label'    => $def['id_label'],
					'id_placeholder' => $def['id_placeholder'],
					'category'    => $def['category'],
					'colour'      => $def['colour'],
					'icon'        => $def['icon'],
				]
			);
		}

		return $result;
	}

	/**
	 * Get only the retailers that are enabled and have a tracking ID (or have no ID requirement).
	 * Formatted for frontend fccData.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function get_enabled_for_frontend(): array {
		$config  = self::get_config();
		$general = $config['general'];
		$out     = [];

		foreach ( $config['retailers'] as $key => $r ) {
			if ( ! $r['enabled'] ) continue;
			$label  = ! empty( $r['custom_label'] ) ? $r['custom_label']
				: ( $general['label_prefix'] . ' ' . $r['name'] );
			$out[] = [
				'key'      => $key,
				'name'     => $r['name'],
				'label'    => esc_html( $label ),
				'url'      => $r['url_template'],
				'id'       => $r['tracking_id'],
				'colour'   => $r['colour'],
				'icon'     => $r['icon'],
			];
		}

		return $out;
	}

	/**
	 * Master retailer definitions — single source of truth.
	 * `{QUERY}` = URL-encoded food name; `{ID}` = affiliate tracking ID.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function get_retailer_definitions(): array {
		return [
			// ---- UK Grocery ----
			'amazon_uk' => [
				'name'           => 'Amazon UK',
				'category'       => 'UK Grocery',
				'url_template'   => 'https://www.amazon.co.uk/s?k={QUERY}&tag={ID}',
				'id_label'       => 'Associate Tag',
				'id_placeholder' => 'mysite-21',
				'colour'         => '#FF9900',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.08 16.97c-3.19 2.36-7.81 3.62-11.79 3.62-5.58 0-10.6-2.07-14.4-5.5-.3-.27-.03-.63.32-.43 4.1 2.39 9.17 3.83 14.41 3.83 3.53 0 7.41-.73 10.98-2.25.54-.23 1 .35.48.73zm1.36-1.55c-.4-.52-2.68-.24-3.7-.12-.31.04-.36-.23-.08-.43 1.81-1.27 4.78-.9 5.12-.48.35.43-.09 3.41-1.79 4.84-.26.22-.51.1-.39-.18.38-.95 1.24-3.11.84-3.63z"/></svg>',
			],
			'ocado' => [
				'name'           => 'Ocado',
				'category'       => 'UK Grocery',
				'url_template'   => 'https://www.ocado.com/search?entry={QUERY}&utm_source=aff&utm_medium=cpa&utm_campaign={ID}',
				'id_label'       => 'Awin Publisher ID',
				'id_placeholder' => '123456',
				'colour'         => '#7AC143',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
			],
			'tesco' => [
				'name'           => 'Tesco',
				'category'       => 'UK Grocery',
				'url_template'   => 'https://www.tesco.com/groceries/en-GB/search?query={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'tesco-aff',
				'colour'         => '#00539F',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
			],
			'sainsburys' => [
				'name'           => "Sainsbury's",
				'category'       => 'UK Grocery',
				'url_template'   => 'https://www.sainsburys.co.uk/search/results?q={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'sains-aff',
				'colour'         => '#EB6A13',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
			],
			'asda' => [
				'name'           => 'ASDA',
				'category'       => 'UK Grocery',
				'url_template'   => 'https://groceries.asda.com/search/{QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'asda-aff',
				'colour'         => '#78BE20',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
			],
			'waitrose' => [
				'name'           => 'Waitrose',
				'category'       => 'UK Grocery',
				'url_template'   => 'https://www.waitrose.com/ecom/shop/search?query={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'wait-aff',
				'colour'         => '#005F4C',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
			],
			'morrisons' => [
				'name'           => 'Morrisons',
				'category'       => 'UK Grocery',
				'url_template'   => 'https://groceries.morrisons.com/search?entry={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'morr-aff',
				'colour'         => '#00864B',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
			],
			// ---- UK Health & Supplements ----
			'holland_barrett' => [
				'name'           => 'Holland & Barrett',
				'category'       => 'UK Health',
				'url_template'   => 'https://www.hollandandbarrett.com/search/?query={QUERY}&affil={ID}',
				'id_label'       => 'Awin Publisher ID',
				'id_placeholder' => '123456',
				'colour'         => '#003C30',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a3 3 0 0 0-3 3v1H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3V5a3 3 0 0 0-3-3z"/><line x1="12" y1="11" x2="12" y2="15"/><line x1="10" y1="13" x2="14" y2="13"/></svg>',
			],
			'myprotein' => [
				'name'           => 'MyProtein',
				'category'       => 'UK Health',
				'url_template'   => 'https://www.myprotein.com/search?search={QUERY}&affil={ID}',
				'id_label'       => 'Awin Publisher ID',
				'id_placeholder' => '123456',
				'colour'         => '#1A1A1A',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
			],
			'bulk' => [
				'name'           => 'Bulk',
				'category'       => 'UK Health',
				'url_template'   => 'https://www.bulk.com/uk/search?q={QUERY}&ref={ID}',
				'id_label'       => 'Referral Code',
				'id_placeholder' => 'MYCODE',
				'colour'         => '#E63329',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>',
			],
			'musclefood' => [
				'name'           => 'MuscleFood',
				'category'       => 'UK Health',
				'url_template'   => 'https://www.musclefood.com/search?q={QUERY}&aff={ID}',
				'id_label'       => 'Affiliate ID',
				'id_placeholder' => 'aff123',
				'colour'         => '#E8232E',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>',
			],
			'the_protein_works' => [
				'name'           => 'The Protein Works',
				'category'       => 'UK Health',
				'url_template'   => 'https://www.theproteinworks.com/search/{QUERY}?affil={ID}',
				'id_label'       => 'Affiliate Code',
				'id_placeholder' => 'TPW123',
				'colour'         => '#F7941D',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>',
			],
			'boots' => [
				'name'           => 'Boots',
				'category'       => 'UK Health',
				'url_template'   => 'https://www.boots.com/search?q={QUERY}&awc={ID}',
				'id_label'       => 'Awin Click Ref',
				'id_placeholder' => '12345_...',
				'colour'         => '#0099CC',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a3 3 0 0 0-3 3v1H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3V5a3 3 0 0 0-3-3z"/></svg>',
			],
			'healthspan' => [
				'name'           => 'Healthspan',
				'category'       => 'UK Health',
				'url_template'   => 'https://www.healthspan.co.uk/search?q={QUERY}&aff={ID}',
				'id_label'       => 'Affiliate ID',
				'id_placeholder' => 'hs123',
				'colour'         => '#009B77',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a3 3 0 0 0-3 3v1H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3V5a3 3 0 0 0-3-3z"/></svg>',
			],
			'natures_best' => [
				'name'           => "Nature's Best",
				'category'       => 'UK Health',
				'url_template'   => "https://www.naturesbest.co.uk/search/?search={QUERY}&aff={ID}",
				'id_label'       => 'Affiliate Code',
				'id_placeholder' => 'nb123',
				'colour'         => '#5B8C3E',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a10 10 0 0 1 10 10c0 4.42-2.87 8.17-6.84 9.49A10 10 0 0 1 2 12C2 6.48 6.48 2 12 2z"/></svg>',
			],
			'vitabiotics' => [
				'name'           => 'Vitabiotics',
				'category'       => 'UK Health',
				'url_template'   => 'https://www.vitabiotics.com/pages/search-results?q={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'vb-aff',
				'colour'         => '#0071C5',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a3 3 0 0 0-3 3v1H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3V5a3 3 0 0 0-3-3z"/></svg>',
			],
			// ---- UK Organic / Specialty ----
			'planet_organic' => [
				'name'           => 'Planet Organic',
				'category'       => 'UK Organic',
				'url_template'   => 'https://www.planetorganic.com/search?q={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'po-aff',
				'colour'         => '#3D7B38',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 22s4-2 8-8c4-6 6-12 6-12s-6 2-10 7c-4 5-4 13-4 13z"/></svg>',
			],
			'grape_tree' => [
				'name'           => 'Grape Tree',
				'category'       => 'UK Organic',
				'url_template'   => 'https://www.grapetreetrade.co.uk/search?q={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'gt-aff',
				'colour'         => '#6B2F8A',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 22s4-2 8-8c4-6 6-12 6-12s-6 2-10 7c-4 5-4 13-4 13z"/></svg>',
			],
			'abel_cole' => [
				'name'           => 'Abel & Cole',
				'category'       => 'UK Organic',
				'url_template'   => 'https://www.abelandcole.co.uk/search?q={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'ac-aff',
				'colour'         => '#E8543C',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 22s4-2 8-8c4-6 6-12 6-12s-6 2-10 7c-4 5-4 13-4 13z"/></svg>',
			],
			'whole_foods' => [
				'name'           => 'Whole Foods Market',
				'category'       => 'UK Organic',
				'url_template'   => 'https://www.wholefoodsmarket.com/search?query={QUERY}',
				'id_label'       => 'Affiliate Ref',
				'id_placeholder' => 'wf-aff',
				'colour'         => '#00674B',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 22s4-2 8-8c4-6 6-12 6-12s-6 2-10 7c-4 5-4 13-4 13z"/></svg>',
			],
			// ---- International ----
			'iherb' => [
				'name'           => 'iHerb',
				'category'       => 'International',
				'url_template'   => 'https://www.iherb.com/search?q={QUERY}&rcode={ID}',
				'id_label'       => 'Referral Code',
				'id_placeholder' => 'ABC1234',
				'colour'         => '#67AB43',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
			],
			'amazon_us' => [
				'name'           => 'Amazon US',
				'category'       => 'International',
				'url_template'   => 'https://www.amazon.com/s?k={QUERY}&tag={ID}',
				'id_label'       => 'Associate Tag',
				'id_placeholder' => 'mysite-20',
				'colour'         => '#FF9900',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.08 16.97c-3.19 2.36-7.81 3.62-11.79 3.62-5.58 0-10.6-2.07-14.4-5.5-.3-.27-.03-.63.32-.43 4.1 2.39 9.17 3.83 14.41 3.83 3.53 0 7.41-.73 10.98-2.25.54-.23 1 .35.48.73z"/></svg>',
			],
			'walmart' => [
				'name'           => 'Walmart',
				'category'       => 'International',
				'url_template'   => 'https://www.walmart.com/search?q={QUERY}&affinityOverride={ID}',
				'id_label'       => 'Impact Affiliate ID',
				'id_placeholder' => 'impact123',
				'colour'         => '#0071CE',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/></svg>',
			],
			'vitacost' => [
				'name'           => 'Vitacost',
				'category'       => 'International',
				'url_template'   => 'https://www.vitacost.com/search?q={QUERY}&aff={ID}',
				'id_label'       => 'Affiliate ID',
				'id_placeholder' => 'vc123',
				'colour'         => '#0058A0',
				'icon'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/></svg>',
			],
		];
	}
}
