<?php
/**
 * REST API endpoints for the frontend calculator.
 *
 * GET /wp-json/fcc/v1/foods/search?q=chicken&limit=10&category=3
 *   Returns matching foods with full nutrition data.
 *
 * GET /wp-json/fcc/v1/foods/{id}
 *   Returns a single food by ID.
 *
 * GET /wp-json/fcc/v1/categories
 *   Returns all categories.
 *
 * No authentication required for read-only endpoints (public nutritional data).
 * Rate-limiting is handled by the server / CloudFlare layer; no WP-level rate
 * limiting is added here to avoid blocking legitimate debounced search calls.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Rest_Api {

	const NAMESPACE = 'fcc/v1';

	public function register( Loader $loader ): void {
		$loader->add_action( 'rest_api_init', $this, 'register_routes' );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/foods/search',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'search_foods' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'q'        => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $val ): bool {
							return is_string( $val ) && strlen( trim( $val ) ) >= 2;
						},
					],
					'limit'    => [
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $val ): bool {
							return $val >= 1 && $val <= 50;
						},
					],
					'category' => [
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/foods/popular',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_popular_foods' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'limit' => [
						'type'              => 'integer',
						'default'           => 8,
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $val ): bool {
							return $val >= 1 && $val <= 20;
						},
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/foods/(?P<id>\d+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_food' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/foods/(?P<id>\d+)/hit',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'record_food_hit' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/categories',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_categories' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/foods/(?P<id>\d+)/sponsor-click',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'record_sponsor_click' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/missed-search',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'log_missed_search' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'query' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $val ): bool {
							return is_string( $val ) && strlen( trim( $val ) ) >= 2;
						},
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/food-requests',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'submit_food_request' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'food_name' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $val ): bool {
							return is_string( $val ) && strlen( trim( $val ) ) >= 2;
						},
					],
					'note'  => [ 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ],
					'email' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
						'validate_callback' => static function ( $val ): bool {
							return is_email( $val );
						},
					],
					'marketing_optin' => [ 'type' => 'boolean', 'default' => true ],
				],
			]
		);
	}

	// -------------------------------------------------------------------------
	// Handlers.
	// -------------------------------------------------------------------------

	public function search_foods( \WP_REST_Request $request ): \WP_REST_Response {
		$query       = $request->get_param( 'q' );
		$limit       = min( (int) $request->get_param( 'limit' ), 50 );
		$category_id = (int) $request->get_param( 'category' );

		$foods = Database::search_foods( $query, $category_id, $limit );

		// Log to search_log for analytics time-series (fire-and-forget).
		Database::log_search( $query, ! empty( $foods ) );

		return new \WP_REST_Response(
			array_map( [ $this, 'format_food' ], $foods ),
			200
		);
	}

	public function get_food( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id   = (int) $request->get_param( 'id' );
		$food = Database::get_food( $id );

		if ( ! $food ) {
			return new \WP_Error(
				'fcc_food_not_found',
				__( 'Food not found.', 'food-calorie-calculator' ),
				[ 'status' => 404 ]
			);
		}

		return new \WP_REST_Response( $this->format_food( $food ), 200 );
	}

	public function get_categories( \WP_REST_Request $request ): \WP_REST_Response {
		$cats = Database::get_all_categories();
		return new \WP_REST_Response( $cats, 200 );
	}

	public function get_popular_foods( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = min( (int) $request->get_param( 'limit' ), 20 );
		$foods = Database::get_popular_foods( $limit );
		return new \WP_REST_Response(
			array_map( [ $this, 'format_food' ], $foods ),
			200
		);
	}

	public function submit_food_request( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$food_name       = trim( (string) $request->get_param( 'food_name' ) );
		$note            = trim( (string) $request->get_param( 'note' ) );
		$email           = trim( (string) $request->get_param( 'email' ) );
		$marketing_optin = (bool) $request->get_param( 'marketing_optin' );
		$ip              = sanitize_text_field(
			$request->get_header( 'x-forwarded-for' ) ?: ( $_SERVER['REMOTE_ADDR'] ?? '' )
		);

		$id = Database::insert_food_request( compact( 'food_name', 'note', 'email', 'marketing_optin', 'ip' ) );

		if ( ! $id ) {
			return new \WP_Error(
				'fcc_request_failed',
				__( 'Request could not be saved.', 'food-calorie-calculator' ),
				[ 'status' => 500 ]
			);
		}

		return new \WP_REST_Response( [ 'ok' => true ], 201 );
	}

	public function record_sponsor_click( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$food = Database::get_food( $id );
		if ( $food && $food['is_sponsored'] && $food['sponsor_active'] ) {
			Database::record_sponsor_click( $id );
		}
		return new \WP_REST_Response( [ 'ok' => true ], 200 );
	}

	public function log_missed_search( \WP_REST_Request $request ): \WP_REST_Response {
		$query = trim( (string) $request->get_param( 'query' ) );
		Database::log_missed_search( $query );
		return new \WP_REST_Response( [ 'ok' => true ], 200 );
	}

	public function record_food_hit( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id   = (int) $request->get_param( 'id' );
		$food = Database::get_food( $id );
		if ( ! $food ) {
			return new \WP_Error(
				'fcc_food_not_found',
				__( 'Food not found.', 'food-calorie-calculator' ),
				[ 'status' => 404 ]
			);
		}
		Database::increment_food_hit( $id );
		return new \WP_REST_Response( [ 'ok' => true ], 200 );
	}

	// -------------------------------------------------------------------------
	// Formatting.
	// -------------------------------------------------------------------------

	/**
	 * Format a food row for the REST response.
	 * Casts types, preserves NULL for nullable fields (never substitutes 0).
	 *
	 * @param array<string,mixed> $food
	 * @return array<string,mixed>
	 */
	private function format_food( array $food ): array {
		return [
			'id'                   => (int) $food['id'],
			'name'                 => esc_html( $food['name'] ),
			'slug'                 => $food['slug'],
			'category_id'          => (int) $food['category_id'],
			'serving_sizes'        => $food['serving_sizes'] ?? [],
			'energy_kcal'          => (float) $food['energy_kcal'],
			'energy_kj'            => (float) $food['energy_kj'],
			'protein_g'            => (float) $food['protein_g'],
			'carbohydrate_g'       => (float) $food['carbohydrate_g'],
			'of_which_sugars_g'    => null !== $food['of_which_sugars_g']    ? (float) $food['of_which_sugars_g']    : null,
			'fat_g'                => (float) $food['fat_g'],
			'of_which_saturates_g' => null !== $food['of_which_saturates_g'] ? (float) $food['of_which_saturates_g'] : null,
			'fibre_g'              => null !== $food['fibre_g']              ? (float) $food['fibre_g']              : null,
			'salt_g'               => null !== $food['salt_g']               ? (float) $food['salt_g']               : null,
			// Omega-3: only present when verified data exists.
			'omega3_total_mg'      => null !== $food['omega3_total_mg']      ? (float) $food['omega3_total_mg']      : null,
			'omega3_ala_mg'        => null !== $food['omega3_ala_mg']        ? (float) $food['omega3_ala_mg']        : null,
			'omega3_epa_mg'        => null !== $food['omega3_epa_mg']        ? (float) $food['omega3_epa_mg']        : null,
			'omega3_dha_mg'        => null !== $food['omega3_dha_mg']        ? (float) $food['omega3_dha_mg']        : null,
			// Caffeine: only present when published data exists.
			'caffeine_mg'          => null !== $food['caffeine_mg']          ? (float) $food['caffeine_mg']          : null,
			'source_notes'         => ! empty( $food['source_notes'] ) ? esc_html( $food['source_notes'] ) : null,
			'is_sponsored'         => (bool) ( $food['is_sponsored'] ?? false ),
			'sponsor_active'       => (bool) ( $food['sponsor_active'] ?? false ),
			'sponsor_name'         => ! empty( $food['sponsor_name'] ) ? esc_html( $food['sponsor_name'] ) : null,
			'sponsor_logo_url'     => ! empty( $food['sponsor_logo_id'] ) ? wp_get_attachment_url( (int) $food['sponsor_logo_id'] ) : null,
			'sponsor_url'          => ! empty( $food['sponsor_url'] ) ? esc_url( $food['sponsor_url'] ) : null,
			'allergen_fish'        => isset( $food['allergen_fish'] ) ? (int) $food['allergen_fish'] : null,
			'allergen_shellfish'   => isset( $food['allergen_shellfish'] ) ? (int) $food['allergen_shellfish'] : null,
			'allergen_dairy'       => isset( $food['allergen_dairy'] ) ? (int) $food['allergen_dairy'] : null,
			'allergen_eggs'        => isset( $food['allergen_eggs'] ) ? (int) $food['allergen_eggs'] : null,
			'allergen_nuts'        => isset( $food['allergen_nuts'] ) ? (int) $food['allergen_nuts'] : null,
			'allergen_gluten'      => isset( $food['allergen_gluten'] ) ? (int) $food['allergen_gluten'] : null,
			'allergen_soy'         => isset( $food['allergen_soy'] ) ? (int) $food['allergen_soy'] : null,
			'allergen_celery'      => isset( $food['allergen_celery'] ) ? (int) $food['allergen_celery'] : null,
			'diet_keto'            => isset( $food['diet_keto'] ) ? (int) $food['diet_keto'] : null,
			'diet_paleo'           => isset( $food['diet_paleo'] ) ? (int) $food['diet_paleo'] : null,
			'diet_halal'           => isset( $food['diet_halal'] ) ? (int) $food['diet_halal'] : null,
			'diet_kosher'          => isset( $food['diet_kosher'] ) ? (int) $food['diet_kosher'] : null,
			'diet_vegan'           => isset( $food['diet_vegan'] ) ? (int) $food['diet_vegan'] : null,
			'diet_vegetarian'      => isset( $food['diet_vegetarian'] ) ? (int) $food['diet_vegetarian'] : null,
		];
	}
}
