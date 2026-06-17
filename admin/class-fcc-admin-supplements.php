<?php
/**
 * Lead Gen for Supplements — admin class.
 * Manages the supplement catalog, smart trigger rules, and contextual
 * suggestion display on the frontend.
 *
 * @package FCC\Admin
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Supplements class. Loaded unconditionally — static helpers are called by
 * the shortcode on the frontend.
 */
class Supplements {

	const CATALOG_KEY = 'fcc_supplements_catalog';
	const CONFIG_KEY  = 'fcc_supplements_config';
	const RULES_KEY   = 'fcc_supplements_rules';
	const STATS_KEY   = 'fcc_supplement_stats';

	// -------------------------------------------------------------------------
	// Wiring
	// -------------------------------------------------------------------------

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_save_supplements',      $this, 'ajax_save' );
		$loader->add_action( 'wp_ajax_fcc_supp_click',            $this, 'ajax_track_click' );
		$loader->add_action( 'wp_ajax_nopriv_fcc_supp_click',     $this, 'ajax_track_click' );
		$loader->add_action( 'wp_ajax_fcc_supp_impression',       $this, 'ajax_track_impression' );
		$loader->add_action( 'wp_ajax_nopriv_fcc_supp_impression', $this, 'ajax_track_impression' );
	}

	// -------------------------------------------------------------------------
	// Admin page entry-point
	// -------------------------------------------------------------------------

	public function page_supplements(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-supplements.php';
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	/** Save catalog + config + rules in one call. */
	public function ajax_save(): void {
		check_ajax_referer( 'fcc_supplements_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$raw_catalog = isset( $_POST['catalog'] ) ? (array) wp_unslash( $_POST['catalog'] ) : [];
		$raw_config  = isset( $_POST['config'] )  ? (array) wp_unslash( $_POST['config'] )  : [];
		$raw_rules   = isset( $_POST['rules'] )   ? (array) wp_unslash( $_POST['rules'] )   : [];
		// phpcs:enable

		// ── Config ─────────────────────────────────────────────────────────
		$allowed_positions = [ 'after_results', 'below_calculator' ];
		$allowed_styles    = [ 'card', 'compact' ];
		$config = [
			'enabled'          => ! empty( $raw_config['enabled'] ),
			'position'         => in_array( $raw_config['position'] ?? '', $allowed_positions, true ) ? $raw_config['position'] : 'after_results',
			'max_sugg'         => max( 1, min( 5, absint( $raw_config['max_sugg'] ?? 2 ) ) ),
			'display_style'    => in_array( $raw_config['display_style'] ?? '', $allowed_styles, true ) ? $raw_config['display_style'] : 'card',
			'show_price'       => ! empty( $raw_config['show_price'] ),
			'show_network'     => ! empty( $raw_config['show_network'] ),
			'cta_text'         => sanitize_text_field( $raw_config['cta_text']  ?? 'View Deal' ),
			'heading'          => sanitize_text_field( $raw_config['heading']   ?? 'Recommended Supplements' ),
			'disclosure'       => sanitize_text_field( $raw_config['disclosure'] ?? 'Affiliate links — we may earn a small commission at no extra cost to you' ),
			'avg_commission'   => (float) ( $raw_config['avg_commission'] ?? 0.50 ),
		];
		update_option( self::CONFIG_KEY, $config );

		// ── Supplement catalog ─────────────────────────────────────────────
		$catalog    = [];
		$valid_cats = array_keys( self::get_category_definitions() );
		$valid_nets = array_keys( self::get_network_definitions() );

		foreach ( $raw_catalog as $item ) {
			if ( empty( $item['name'] ) ) continue;
			$id = sanitize_key( $item['id'] ?? '' );
			if ( ! $id ) $id = 'supp_' . substr( md5( uniqid( '', true ) ), 0, 8 );
			$catalog[] = [
				'id'            => $id,
				'name'          => sanitize_text_field( $item['name'] ),
				'brand'         => sanitize_text_field( $item['brand'] ?? '' ),
				'tagline'       => sanitize_text_field( $item['tagline'] ?? '' ),
				'category'      => in_array( $item['category'] ?? '', $valid_cats, true ) ? $item['category'] : 'protein',
				'image_url'     => esc_url_raw( $item['image_url'] ?? '' ),
				'affiliate_url' => esc_url_raw( $item['affiliate_url'] ?? '' ),
				'network'       => in_array( $item['network'] ?? '', $valid_nets, true ) ? $item['network'] : 'custom',
				'price'         => sanitize_text_field( $item['price'] ?? '' ),
				'badge'         => sanitize_text_field( $item['badge'] ?? '' ),
				'status'        => ( isset( $item['status'] ) && $item['status'] === 'inactive' ) ? 'inactive' : 'active',
			];
		}
		update_option( self::CATALOG_KEY, $catalog );

		// ── Trigger rules ──────────────────────────────────────────────────
		$rules         = [];
		$allowed_types = [ 'nutrient', 'category', 'keyword' ];
		$allowed_ops   = [ 'gte', 'lte', 'eq', 'contains' ];
		$nutrient_flds = [ 'protein_g', 'fat_g', 'saturates_g', 'carbohydrate_g', 'sugars_g', 'fibre_g', 'salt_g', 'energy_kcal', 'omega3_g', 'caffeine_mg' ];

		foreach ( $raw_rules as $rule ) {
			$id = sanitize_key( $rule['id'] ?? '' );
			if ( ! $id ) $id = 'rule_' . substr( md5( uniqid( '', true ) ), 0, 8 );
			$type = in_array( $rule['type'] ?? '', $allowed_types, true ) ? $rule['type'] : 'nutrient';
			$op   = in_array( $rule['operator'] ?? '', $allowed_ops, true ) ? $rule['operator'] : 'gte';
			$fld  = in_array( $rule['field'] ?? '', $nutrient_flds, true ) ? $rule['field'] : 'protein_g';

			$cats = [];
			if ( ! empty( $rule['cats'] ) && is_array( $rule['cats'] ) ) {
				foreach ( $rule['cats'] as $c ) {
					if ( in_array( sanitize_key( $c ), $valid_cats, true ) ) $cats[] = sanitize_key( $c );
				}
			}

			$rules[] = [
				'id'       => $id,
				'label'    => sanitize_text_field( $rule['label'] ?? 'Rule' ),
				'type'     => $type,
				'field'    => $type === 'nutrient' ? $fld : '',
				'operator' => $type === 'nutrient' ? $op : 'contains',
				'value'    => sanitize_text_field( $rule['value'] ?? '' ),
				'cats'     => $cats,
				'enabled'  => ! empty( $rule['enabled'] ),
				'priority' => absint( $rule['priority'] ?? 10 ),
			];
		}
		update_option( self::RULES_KEY, $rules );

		wp_send_json_success( [ 'saved' => true, 'catalog_count' => count( $catalog ), 'rules_count' => count( $rules ) ] );
	}

	/** Track supplement click (public AJAX — visitors fire this). */
	public function ajax_track_click(): void {
		check_ajax_referer( 'fcc_supp_track', 'nonce' );
		$id = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
		if ( ! $id ) wp_send_json_error();
		$stats         = (array) get_option( self::STATS_KEY, [] );
		$s             = $stats[ $id ] ?? [ 'clicks' => 0, 'impressions' => 0 ];
		$s['clicks']   = (int) $s['clicks'] + 1;
		$stats[ $id ]  = $s;
		update_option( self::STATS_KEY, $stats, false );
		wp_send_json_success();
	}

	/** Track supplement impressions (public AJAX — visitors fire this). */
	public function ajax_track_impression(): void {
		check_ajax_referer( 'fcc_supp_track', 'nonce' );
		$ids = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : [];
		if ( empty( $ids ) ) wp_send_json_error();
		$stats = (array) get_option( self::STATS_KEY, [] );
		foreach ( $ids as $id ) {
			$id = sanitize_key( $id );
			if ( ! $id ) continue;
			$s                   = $stats[ $id ] ?? [ 'clicks' => 0, 'impressions' => 0 ];
			$s['impressions']    = (int) $s['impressions'] + 1;
			$stats[ $id ]        = $s;
		}
		update_option( self::STATS_KEY, $stats, false );
		wp_send_json_success();
	}

	// -------------------------------------------------------------------------
	// Static config helpers (used by shortcode on frontend)
	// -------------------------------------------------------------------------

	public static function get_config(): array {
		$saved = get_option( self::CONFIG_KEY, null );
		if ( $saved === null ) {
			return [
				'enabled'        => false,
				'position'       => 'after_results',
				'max_sugg'       => 2,
				'display_style'  => 'card',
				'show_price'     => true,
				'show_network'   => true,
				'cta_text'       => 'View Deal',
				'heading'        => 'Recommended Supplements',
				'disclosure'     => 'Affiliate links — we may earn a small commission at no extra cost to you',
				'avg_commission' => 0.50,
			];
		}
		return (array) $saved;
	}

	public static function get_catalog(): array {
		$saved = get_option( self::CATALOG_KEY, null );
		if ( $saved === null ) {
			return self::default_catalog();
		}
		return (array) $saved;
	}

	public static function get_rules(): array {
		$saved = get_option( self::RULES_KEY, null );
		if ( $saved === null ) {
			return self::default_rules();
		}
		return (array) $saved;
	}

	public static function get_stats(): array {
		return (array) get_option( self::STATS_KEY, [] );
	}

	/** Active supplements only, enriched with stats — for admin page. */
	public static function get_catalog_with_stats(): array {
		$catalog = self::get_catalog();
		$stats   = self::get_stats();
		foreach ( $catalog as &$s ) {
			$st             = $stats[ $s['id'] ] ?? [ 'clicks' => 0, 'impressions' => 0 ];
			$s['clicks']    = (int) $st['clicks'];
			$s['impressions'] = (int) $st['impressions'];
			$s['ctr']       = $s['impressions'] > 0 ? round( $s['clicks'] / $s['impressions'] * 100, 1 ) : 0;
		}
		return $catalog;
	}

	/** Active catalog + enabled rules formatted for fccData (frontend). */
	public static function get_frontend_data(): ?array {
		$config = self::get_config();
		if ( ! $config['enabled'] ) return null;

		$active_catalog = array_values( array_filter( self::get_catalog(), fn( $s ) => $s['status'] === 'active' && ! empty( $s['affiliate_url'] ) ) );
		if ( empty( $active_catalog ) ) return null;

		$enabled_rules = array_values( array_filter( self::get_rules(), fn( $r ) => ! empty( $r['enabled'] ) && ! empty( $r['cats'] ) ) );

		// Strip stats/internal fields for frontend.
		$lean_catalog = array_map( fn( $s ) => [
			'id'       => $s['id'],
			'name'     => $s['name'],
			'brand'    => $s['brand'],
			'tagline'  => $s['tagline'],
			'category' => $s['category'],
			'image'    => $s['image_url'],
			'url'      => $s['affiliate_url'],
			'network'  => $s['network'],
			'price'    => $s['price'],
			'badge'    => $s['badge'],
		], $active_catalog );

		return [
			'config'  => [
				'max_sugg'    => (int) $config['max_sugg'],
				'style'       => $config['display_style'],
				'cta'         => $config['cta_text'],
				'heading'     => $config['heading'],
				'disclosure'  => $config['disclosure'],
				'show_price'  => (bool) $config['show_price'],
				'show_network'=> (bool) $config['show_network'],
				'position'    => $config['position'],
			],
			'catalog' => $lean_catalog,
			'rules'   => $enabled_rules,
			'nonce'   => wp_create_nonce( 'fcc_supp_track' ),
		];
	}

	// -------------------------------------------------------------------------
	// Static definitions
	// -------------------------------------------------------------------------

	public static function get_category_definitions(): array {
		return [
			'protein'     => [ 'label' => 'Protein & Muscle',     'colour' => '#E53935', 'icon' => '💪' ],
			'vitamins'    => [ 'label' => 'Vitamins & Minerals',  'colour' => '#F9A825', 'icon' => '💊' ],
			'weight_mgmt' => [ 'label' => 'Weight Management',    'colour' => '#43A047', 'icon' => '⚖️' ],
			'sports'      => [ 'label' => 'Sports Performance',   'colour' => '#1E88E5', 'icon' => '⚡' ],
			'gut'         => [ 'label' => 'Gut & Digestive',      'colour' => '#8E24AA', 'icon' => '🌿' ],
			'omega3'      => [ 'label' => 'Omega-3 & Heart',      'colour' => '#039BE5', 'icon' => '🐟' ],
			'energy'      => [ 'label' => 'Energy & Endurance',   'colour' => '#F4511E', 'icon' => '🔋' ],
			'recovery'    => [ 'label' => 'Recovery & Sleep',     'colour' => '#5E35B1', 'icon' => '🌙' ],
			'immunity'    => [ 'label' => 'Immunity & Antioxidants', 'colour' => '#00897B', 'icon' => '🛡️' ],
			'bone'        => [ 'label' => 'Bone & Joint Health',  'colour' => '#6D4C41', 'icon' => '🦴' ],
		];
	}

	public static function get_network_definitions(): array {
		return [
			'amazon_uk'    => [ 'label' => 'Amazon UK',          'colour' => '#FF9900' ],
			'myprotein'    => [ 'label' => 'MyProtein',          'colour' => '#CE0000' ],
			'hollbarrett'  => [ 'label' => 'Holland & Barrett',  'colour' => '#00843D' ],
			'boots'        => [ 'label' => 'Boots',              'colour' => '#003082' ],
			'bulk'         => [ 'label' => 'Bulk™',              'colour' => '#000000' ],
			'chemist_drct' => [ 'label' => 'Chemist Direct',     'colour' => '#0074BD' ],
			'custom'       => [ 'label' => 'Custom',             'colour' => '#607D8B' ],
		];
	}

	public static function get_nutrient_field_labels(): array {
		return [
			'protein_g'      => 'Protein (g)',
			'fat_g'          => 'Fat (g)',
			'saturates_g'    => 'Saturates (g)',
			'carbohydrate_g' => 'Carbohydrates (g)',
			'sugars_g'       => 'Sugars (g)',
			'fibre_g'        => 'Fibre (g)',
			'salt_g'         => 'Salt (g)',
			'energy_kcal'    => 'Calories (kcal)',
			'omega3_g'       => 'Omega-3 (g)',
			'caffeine_mg'    => 'Caffeine (mg)',
		];
	}

	// -------------------------------------------------------------------------
	// Default data (shown on first install)
	// -------------------------------------------------------------------------

	private static function default_catalog(): array {
		return [
			[
				'id' => 'supp_d_001', 'name' => 'Whey Protein Isolate', 'brand' => 'Optimum Nutrition',
				'tagline'  => '24g protein per scoop. Low lactose. 80+ flavours.',
				'category' => 'protein', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'amazon_uk', 'price' => '£34.99', 'badge' => 'Best Seller', 'status' => 'active',
			],
			[
				'id' => 'supp_d_002', 'name' => 'Creatine Monohydrate', 'brand' => 'MyProtein',
				'tagline'  => 'Clinically proven to increase strength and power output.',
				'category' => 'sports', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'myprotein', 'price' => '£12.99', 'badge' => 'Top Rated', 'status' => 'active',
			],
			[
				'id' => 'supp_d_003', 'name' => 'Omega-3 Fish Oil 1000mg', 'brand' => 'Holland & Barrett',
				'tagline'  => 'EPA & DHA for heart, brain, and joint health.',
				'category' => 'omega3', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'hollbarrett', 'price' => '£9.99', 'badge' => '', 'status' => 'active',
			],
			[
				'id' => 'supp_d_004', 'name' => 'Vitamin D3 + K2 4000IU', 'brand' => 'BetterYou',
				'tagline'  => 'UK\'s #1 vitamin D. Boosts immunity and bone density.',
				'category' => 'immunity', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'amazon_uk', 'price' => '£14.99', 'badge' => 'UK Bestseller', 'status' => 'active',
			],
			[
				'id' => 'supp_d_005', 'name' => 'Complete Multivitamin', 'brand' => 'Vitabiotics Wellman',
				'tagline'  => '29 nutrients tailored for men. Once-daily tablet.',
				'category' => 'vitamins', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'boots', 'price' => '£10.95', 'badge' => '', 'status' => 'active',
			],
			[
				'id' => 'supp_d_006', 'name' => 'Iron + Vitamin C Complex', 'brand' => 'Solgar',
				'tagline'  => 'Gentle chelated iron with vitamin C for absorption.',
				'category' => 'vitamins', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'hollbarrett', 'price' => '£12.50', 'badge' => '', 'status' => 'active',
			],
			[
				'id' => 'supp_d_007', 'name' => 'Plant-Based Protein', 'brand' => 'Form Nutrition',
				'tagline'  => '30g vegan protein from pea & rice blend. No grit.',
				'category' => 'protein', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'amazon_uk', 'price' => '£37.99', 'badge' => 'Vegan', 'status' => 'active',
			],
			[
				'id' => 'supp_d_008', 'name' => 'Probiotic 20 Billion CFU', 'brand' => 'Optibac',
				'tagline'  => 'Clinically studied strains for digestive balance.',
				'category' => 'gut', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'amazon_uk', 'price' => '£15.95', 'badge' => 'No.1 UK', 'status' => 'active',
			],
			[
				'id' => 'supp_d_009', 'name' => 'BCAAs 2:1:1 Powder', 'brand' => 'Bulk™',
				'tagline'  => 'Leucine, isoleucine, valine to reduce muscle breakdown.',
				'category' => 'recovery', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'bulk', 'price' => '£16.99', 'badge' => '', 'status' => 'active',
			],
			[
				'id' => 'supp_d_010', 'name' => 'Magnesium Glycinate 400mg', 'brand' => 'Thorne',
				'tagline'  => 'High-absorption form. Supports sleep, recovery & muscle.',
				'category' => 'recovery', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'amazon_uk', 'price' => '£24.99', 'badge' => '', 'status' => 'active',
			],
			[
				'id' => 'supp_d_011', 'name' => 'Meal Replacement Shake', 'brand' => 'Huel',
				'tagline'  => 'Complete nutrition — 400kcal, 26 vitamins, 37g protein.',
				'category' => 'weight_mgmt', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'custom', 'price' => '£40.00', 'badge' => 'Complete Meal', 'status' => 'active',
			],
			[
				'id' => 'supp_d_012', 'name' => 'CLA 1000mg (Weight Support)', 'brand' => 'MyProtein',
				'tagline'  => 'Conjugated linoleic acid to help maintain lean mass.',
				'category' => 'weight_mgmt', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'myprotein', 'price' => '£11.99', 'badge' => '', 'status' => 'active',
			],
			[
				'id' => 'supp_d_013', 'name' => 'Vitamin C 1000mg', 'brand' => 'Solgar',
				'tagline'  => 'High-strength antioxidant. Time-release formula.',
				'category' => 'immunity', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'hollbarrett', 'price' => '£14.99', 'badge' => '', 'status' => 'active',
			],
			[
				'id' => 'supp_d_014', 'name' => 'Joint Complex (Glucosamine + Collagen)', 'brand' => 'Vitabiotics',
				'tagline'  => 'Glucosamine, chondroitin, vitamin C and collagen combined.',
				'category' => 'bone', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'boots', 'price' => '£22.99', 'badge' => '', 'status' => 'active',
			],
			[
				'id' => 'supp_d_015', 'name' => 'Pre-Workout Energy', 'brand' => 'Applied Nutrition',
				'tagline'  => 'Caffeine, beta-alanine & citrulline for peak performance.',
				'category' => 'energy', 'image_url' => '', 'affiliate_url' => '',
				'network'  => 'amazon_uk', 'price' => '£24.99', 'badge' => '', 'status' => 'active',
			],
		];
	}

	private static function default_rules(): array {
		return [
			[ 'id' => 'rule_d_01', 'label' => 'High Protein Food',         'type' => 'nutrient',  'field' => 'protein_g',      'operator' => 'gte', 'value' => '20',  'cats' => [ 'protein', 'sports' ], 'enabled' => true,  'priority' => 10 ],
			[ 'id' => 'rule_d_02', 'label' => 'Very High Protein (30g+)',   'type' => 'nutrient',  'field' => 'protein_g',      'operator' => 'gte', 'value' => '30',  'cats' => [ 'protein', 'sports', 'recovery' ], 'enabled' => true, 'priority' => 9 ],
			[ 'id' => 'rule_d_03', 'label' => 'High Fibre Food',            'type' => 'nutrient',  'field' => 'fibre_g',        'operator' => 'gte', 'value' => '5',   'cats' => [ 'gut' ], 'enabled' => true,  'priority' => 8 ],
			[ 'id' => 'rule_d_04', 'label' => 'Omega-3 Rich Food',          'type' => 'nutrient',  'field' => 'omega3_g',       'operator' => 'gte', 'value' => '0.5', 'cats' => [ 'omega3' ], 'enabled' => true, 'priority' => 9 ],
			[ 'id' => 'rule_d_05', 'label' => 'Low Calorie Food',           'type' => 'nutrient',  'field' => 'energy_kcal',    'operator' => 'lte', 'value' => '100', 'cats' => [ 'weight_mgmt' ], 'enabled' => true, 'priority' => 7 ],
			[ 'id' => 'rule_d_06', 'label' => 'High Sugar Food',            'type' => 'nutrient',  'field' => 'sugars_g',       'operator' => 'gte', 'value' => '20',  'cats' => [ 'gut', 'weight_mgmt' ], 'enabled' => true, 'priority' => 6 ],
			[ 'id' => 'rule_d_07', 'label' => 'Caffeine-Containing Food',   'type' => 'nutrient',  'field' => 'caffeine_mg',    'operator' => 'gte', 'value' => '50',  'cats' => [ 'energy', 'sports' ], 'enabled' => true, 'priority' => 7 ],
			[ 'id' => 'rule_d_08', 'label' => 'Fish or Seafood Food',       'type' => 'keyword',   'field' => '', 'operator' => 'contains', 'value' => 'fish,salmon,tuna,cod,mackerel,sardine,trout,prawn,shrimp,crab,lobster', 'cats' => [ 'omega3' ], 'enabled' => true, 'priority' => 8 ],
			[ 'id' => 'rule_d_09', 'label' => 'Vegetable or Fruit Food',    'type' => 'keyword',   'field' => '', 'operator' => 'contains', 'value' => 'spinach,broccoli,kale,carrot,tomato,apple,banana,orange,berry,mango', 'cats' => [ 'vitamins', 'immunity' ], 'enabled' => true, 'priority' => 6 ],
			[ 'id' => 'rule_d_10', 'label' => 'Dairy / Calcium Food',       'type' => 'keyword',   'field' => '', 'operator' => 'contains', 'value' => 'milk,cheese,yogurt,yoghurt,kefir,quark', 'cats' => [ 'bone' ], 'enabled' => true, 'priority' => 7 ],
		];
	}
}
