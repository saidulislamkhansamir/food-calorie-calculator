<?php
/**
 * Individual food pages — SEO-friendly URLs for every food.
 *
 * Registers /food/{slug}/ rewrite, renders a full page template with
 * auto-generated SEO content, calculator widget, FAQ, related foods,
 * and source citations. Provides an XML sitemap and JSON-LD schema.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Food_Pages {

	private static ?array $current_food = null;

	public function register( Loader $loader ): void {
		$loader->add_action( 'init',              $this, 'add_rewrite_rules' );
		$loader->add_filter( 'query_vars',        $this, 'add_query_vars' );
		$loader->add_action( 'template_redirect', $this, 'handle_food_page' );
		$loader->add_action( 'wp_head',           $this, 'output_seo_meta', 1 );
		$loader->add_filter( 'document_title_parts', $this, 'filter_title' );
		$loader->add_filter( 'wp_sitemaps_add_provider', $this, 'register_sitemap_provider', 10, 2 );
	}

	public function add_rewrite_rules(): void {
		add_rewrite_rule( 'food/([^/]+)/?$', 'index.php?fcc_food_slug=$matches[1]', 'top' );
	}

	public function add_query_vars( array $vars ): array {
		$vars[] = 'fcc_food_slug';
		return $vars;
	}

	public function handle_food_page(): void {
		$slug = get_query_var( 'fcc_food_slug' );
		if ( ! $slug ) { return; }

		$food = Database::get_food_by_slug( sanitize_title( $slug ) );

		if ( ! $food || empty( $food['is_active'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}

		self::$current_food = $food;

		// Tell the shortcode to preload this food by ID.
		$food_id = $food['id'];
		add_filter( 'fcc_preload_food', function () use ( $food_id ) {
			return $food_id;
		} );

		$this->render_food_page( $food );
		exit;
	}

	// -------------------------------------------------------------------------
	// Page Rendering
	// -------------------------------------------------------------------------

	private function render_food_page( array $food ): void {
		// Ensure shortcode assets load.
		$shortcode = new Shortcode();

		status_header( 200 );
		get_header();

		echo '<div class="fcc-food-page" style="max-width:900px;margin:0 auto;padding:2rem 1rem;">';

		// H1 + intro content.
		$this->render_seo_content( $food );

		// Calculator widget pre-loaded with this food.
		echo do_shortcode( '[food_calorie_calculator]' );

		// FAQ section.
		$this->render_faq( $food );

		// Sources & references.
		$this->render_sources( $food );

		// Related foods.
		$this->render_related_foods( $food );

		echo '</div>';

		get_footer();
	}

	// -------------------------------------------------------------------------
	// Auto-Generated SEO Content
	// -------------------------------------------------------------------------

	private function render_seo_content( array $food ): void {
		$name = esc_html( $food['name'] );
		$kcal = number_format( (float) $food['energy_kcal'], 0 );
		$prot = number_format( (float) $food['protein_g'], 1 );
		$carb = number_format( (float) $food['carbohydrate_g'], 1 );
		$fat  = number_format( (float) $food['fat_g'], 1 );

		// Custom content overrides auto-generated if available.
		if ( ! empty( $food['page_content'] ) ) {
			echo '<h1 class="fcc-food-page__title">' . $name . ' — Calories &amp; Nutrition Facts</h1>';
			echo '<div class="fcc-food-page__content">' . wp_kses_post( $food['page_content'] ) . '</div>';
			return;
		}

		// Auto-generated content.
		echo '<h1 class="fcc-food-page__title">' . $name . ' — Calories &amp; Nutrition Facts</h1>';

		// Intro paragraph.
		$highlights = $this->build_highlights( $food );
		echo '<p class="fcc-food-page__intro">'
			. sprintf( '%s contains <strong>%s kcal per 100g</strong>, with %sg of protein, %sg of carbohydrates, and %sg of fat.', $name, $kcal, $prot, $carb, $fat );
		if ( $highlights ) {
			echo ' ' . implode( ' ', $highlights );
		}
		echo '</p>';

		// Key facts.
		$this->render_key_facts( $food );
	}

	private function build_highlights( array $food ): array {
		$h = [];
		$kcal = (float) $food['energy_kcal'];
		$prot = (float) $food['protein_g'];
		$fat  = (float) $food['fat_g'];

		if ( $prot >= 15 ) { $h[] = 'It is <strong>high in protein</strong>.'; }
		if ( $fat <= 3 )   { $h[] = 'It is <strong>low in fat</strong>.'; }
		if ( $kcal <= 100 ){ $h[] = 'It is <strong>low in calories</strong>, making it suitable for calorie-controlled diets.'; }
		if ( $kcal >= 400 ){ $h[] = 'It is <strong>energy-dense</strong>.'; }

		if ( ! empty( $food['diet_keto'] ) )       { $h[] = 'It is considered <strong>keto-friendly</strong>.'; }
		if ( ! empty( $food['diet_vegan'] ) )       { $h[] = 'It is <strong>suitable for vegans</strong>.'; }
		if ( ! empty( $food['diet_vegetarian'] ) && empty( $food['diet_vegan'] ) ) { $h[] = 'It is <strong>suitable for vegetarians</strong>.'; }

		return $h;
	}

	private function render_key_facts( array $food ): void {
		$facts = [];

		// Allergens.
		$allergens = [];
		$allergen_map = [
			'allergen_fish' => 'Fish', 'allergen_shellfish' => 'Shellfish',
			'allergen_dairy' => 'Dairy', 'allergen_eggs' => 'Eggs',
			'allergen_nuts' => 'Tree Nuts', 'allergen_gluten' => 'Gluten',
			'allergen_soy' => 'Soy', 'allergen_celery' => 'Celery',
		];
		foreach ( $allergen_map as $key => $label ) {
			if ( ! empty( $food[ $key ] ) ) { $allergens[] = $label; }
		}
		if ( $allergens ) {
			$facts[] = '<strong>Contains:</strong> ' . esc_html( implode( ', ', $allergens ) );
		} else {
			$facts[] = '<strong>Allergens:</strong> None of the major allergens';
		}

		// Dietary.
		$diets = [];
		$diet_map = [
			'diet_keto' => 'Keto', 'diet_paleo' => 'Paleo',
			'diet_halal' => 'Halal', 'diet_kosher' => 'Kosher',
			'diet_vegan' => 'Vegan', 'diet_vegetarian' => 'Vegetarian',
		];
		foreach ( $diet_map as $key => $label ) {
			if ( ! empty( $food[ $key ] ) ) { $diets[] = $label; }
		}
		if ( $diets ) {
			$facts[] = '<strong>Dietary:</strong> ' . esc_html( implode( ', ', $diets ) );
		}

		// Micronutrients.
		if ( null !== $food['iron_mg'] || null !== $food['calcium_mg'] || null !== $food['vitamin_c_mg'] ) {
			$micro = [];
			if ( null !== $food['iron_mg'] )      { $micro[] = 'Iron ' . number_format( $food['iron_mg'], 2 ) . 'mg'; }
			if ( null !== $food['calcium_mg'] )    { $micro[] = 'Calcium ' . number_format( $food['calcium_mg'], 1 ) . 'mg'; }
			if ( null !== $food['vitamin_c_mg'] )  { $micro[] = 'Vitamin C ' . number_format( $food['vitamin_c_mg'], 1 ) . 'mg'; }
			$facts[] = '<strong>Key Micronutrients (per 100g):</strong> ' . implode( ', ', $micro );
		}

		if ( $facts ) {
			echo '<ul class="fcc-food-page__facts">';
			foreach ( $facts as $f ) {
				echo '<li>' . $f . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</ul>';
		}
	}

	// -------------------------------------------------------------------------
	// FAQ Section
	// -------------------------------------------------------------------------

	private function render_faq( array $food ): void {
		$name = esc_html( $food['name'] );
		$kcal = number_format( (float) $food['energy_kcal'], 0 );
		$prot = number_format( (float) $food['protein_g'], 1 );
		$fat  = number_format( (float) $food['fat_g'], 1 );
		$carb = number_format( (float) $food['carbohydrate_g'], 1 );

		$faqs = [];

		$faqs[] = [
			'q' => "How many calories are in {$name}?",
			'a' => "{$name} contains {$kcal} kcal per 100g. This represents approximately " . round( (float) $food['energy_kcal'] / 2000 * 100 ) . "% of the recommended daily intake of 2,000 kcal for an average adult.",
		];

		$faqs[] = [
			'q' => "What is the nutritional breakdown of {$name}?",
			'a' => "Per 100g, {$name} provides {$prot}g of protein, {$carb}g of carbohydrates, and {$fat}g of fat. " .
				( null !== $food['fibre_g'] ? "It contains " . number_format( $food['fibre_g'], 1 ) . "g of fibre. " : '' ) .
				( null !== $food['salt_g'] ? "Salt content is " . number_format( $food['salt_g'], 2 ) . "g per 100g." : '' ),
		];

		if ( (float) $food['protein_g'] >= 10 ) {
			$faqs[] = [
				'q' => "Is {$name} high in protein?",
				'a' => "Yes, {$name} contains {$prot}g of protein per 100g, which is " . ( (float) $food['protein_g'] >= 20 ? 'a very high' : 'a good' ) . " source of protein. Protein contributes to the growth and maintenance of muscle mass.",
			];
		}

		if ( ! empty( $food['diet_keto'] ) ) {
			$faqs[] = [
				'q' => "Is {$name} keto-friendly?",
				'a' => "Yes, {$name} is considered keto-friendly with only {$carb}g of carbohydrates per 100g, making it suitable for a ketogenic diet.",
			];
		}

		$allergens = [];
		$allergen_map = [ 'allergen_fish' => 'fish', 'allergen_shellfish' => 'shellfish', 'allergen_dairy' => 'dairy', 'allergen_eggs' => 'eggs', 'allergen_nuts' => 'tree nuts', 'allergen_gluten' => 'gluten', 'allergen_soy' => 'soy', 'allergen_celery' => 'celery' ];
		foreach ( $allergen_map as $key => $label ) {
			if ( ! empty( $food[ $key ] ) ) { $allergens[] = $label; }
		}
		$faqs[] = [
			'q' => "What allergens does {$name} contain?",
			'a' => $allergens
				? "{$name} contains the following allergens: " . implode( ', ', $allergens ) . ". Always check the label if you have food allergies or intolerances."
				: "{$name} does not contain any of the 8 major allergens (fish, shellfish, dairy, eggs, tree nuts, gluten, soy, celery). However, always check the label for potential cross-contamination.",
		];

		echo '<div class="fcc-food-page__faq">';
		echo '<h2>Frequently Asked Questions</h2>';
		// FAQ schema-friendly markup.
		foreach ( $faqs as $faq ) {
			echo '<div class="fcc-food-page__faq-item" itemscope itemtype="https://schema.org/Question">';
			echo '<h3 itemprop="name">' . esc_html( $faq['q'] ) . '</h3>';
			echo '<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">';
			echo '<p itemprop="text">' . esc_html( $faq['a'] ) . '</p>';
			echo '</div></div>';
		}
		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// Sources & References (E-E-A-T)
	// -------------------------------------------------------------------------

	private function render_sources( array $food ): void {
		echo '<div class="fcc-food-page__sources">';
		echo '<h2>Sources &amp; References</h2>';
		echo '<ol>';
		echo '<li><a href="https://www.nutrition.org.uk/" target="_blank" rel="noopener">British Nutrition Foundation</a> — UK nutrition science and education</li>';
		echo '<li><a href="https://www.gov.uk/government/publications/composition-of-foods-integrated-dataset-cofid" target="_blank" rel="noopener">McCance &amp; Widdowson\'s Composition of Foods</a> — UK food composition tables</li>';

		// Auto-link to USDA FDC if source_notes contains an FDC ID.
		$fdc_link = '';
		if ( ! empty( $food['source_notes'] ) && preg_match( '/FDC\s*#?\s*(\d+)/i', $food['source_notes'], $m ) ) {
			$fdc_link = ' — <a href="https://fdc.nal.usda.gov/fdc-app.html#/food-details/' . esc_attr( $m[1] ) . '/nutrients" target="_blank" rel="noopener">View ' . esc_html( $food['name'] ) . ' data</a>';
		}
		echo '<li><a href="https://fdc.nal.usda.gov/" target="_blank" rel="noopener">USDA FoodData Central</a> — US Department of Agriculture food composition database' . $fdc_link . '</li>';

		echo '<li><a href="https://www.nhs.uk/live-well/eat-well/" target="_blank" rel="noopener">NHS Eat Well</a> — UK National Health Service dietary guidance</li>';
		echo '</ol>';

		echo '<p class="fcc-food-page__disclaimer"><em>Nutritional values are per 100g unless otherwise stated and are provided for general information only. '
			. 'Not a substitute for professional dietary or medical advice. If you have a health condition, allergy, or are pregnant, consult a qualified healthcare professional. '
			. 'Values may vary depending on brand, preparation method, and serving size.</em></p>';
		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// Related Foods
	// -------------------------------------------------------------------------

	private function render_related_foods( array $food ): void {
		$related = Database::get_related_foods( $food['category_id'], $food['id'], 6 );
		if ( empty( $related ) ) { return; }

		echo '<div class="fcc-food-page__related">';
		echo '<h2>Related Foods</h2>';
		echo '<ul>';
		foreach ( $related as $r ) {
			$url = home_url( '/food/' . $r['slug'] . '/' );
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $r['name'] ) . '</a> — '
				. number_format( (float) $r['energy_kcal'], 0 ) . ' kcal</li>';
		}
		echo '</ul>';
		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// SEO Meta Tags
	// -------------------------------------------------------------------------

	public function output_seo_meta(): void {
		$food = self::$current_food;
		if ( ! $food ) { return; }

		$name = esc_attr( $food['name'] );
		$kcal = number_format( (float) $food['energy_kcal'], 0 );
		$prot = number_format( (float) $food['protein_g'], 1 );
		$carb = number_format( (float) $food['carbohydrate_g'], 1 );
		$fat  = number_format( (float) $food['fat_g'], 1 );
		$url  = home_url( '/food/' . $food['slug'] . '/' );

		echo '<meta name="description" content="' . esc_attr( "{$name} has {$kcal} kcal per 100g. Protein {$prot}g, Carbs {$carb}g, Fat {$fat}g. Full nutrition facts, FSA traffic lights, allergen info, and macro breakdown." ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";

		// JSON-LD NutritionInformation schema.
		$schema = [
			'@context'        => 'https://schema.org',
			'@type'           => 'NutritionInformation',
			'name'            => $food['name'],
			'calories'        => $kcal . ' kcal',
			'proteinContent'  => $prot . 'g',
			'carbohydrateContent' => $carb . 'g',
			'fatContent'      => $fat . 'g',
			'servingSize'     => '100g',
		];
		if ( null !== $food['fibre_g'] )   { $schema['fiberContent']          = number_format( $food['fibre_g'], 1 ) . 'g'; }
		if ( null !== $food['salt_g'] )    { $schema['sodiumContent']         = number_format( $food['salt_g'] * 400, 0 ) . 'mg'; }
		if ( null !== $food['of_which_saturates_g'] ) { $schema['saturatedFatContent'] = number_format( $food['of_which_saturates_g'], 1 ) . 'g'; }
		if ( null !== $food['of_which_sugars_g'] )    { $schema['sugarContent']        = number_format( $food['of_which_sugars_g'], 1 ) . 'g'; }

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";

		// FAQPage schema.
		echo '<script type="application/ld+json">' . wp_json_encode( $this->build_faq_schema( $food ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	public function filter_title( array $title ): array {
		if ( self::$current_food ) {
			$title['title'] = self::$current_food['name'] . ' Calories & Nutrition';
			$title['site']  = 'Food Calorie Calculator';
		}
		return $title;
	}

	private function build_faq_schema( array $food ): array {
		$name = $food['name'];
		$kcal = number_format( (float) $food['energy_kcal'], 0 );
		$prot = number_format( (float) $food['protein_g'], 1 );

		return [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => [
				[
					'@type' => 'Question',
					'name'  => "How many calories are in {$name}?",
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => "{$name} contains {$kcal} kcal per 100g.",
					],
				],
				[
					'@type' => 'Question',
					'name'  => "Is {$name} high in protein?",
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => "{$name} contains {$prot}g of protein per 100g.",
					],
				],
			],
		];
	}

	// -------------------------------------------------------------------------
	// XML Sitemap Provider
	// -------------------------------------------------------------------------

	public function register_sitemap_provider( $provider, string $name ) {
		if ( 'posts' === $name ) {
			// Also register our custom food pages sitemap.
			add_filter( 'wp_sitemaps_add_provider', function ( $p, $n ) {
				return $p;
			}, 10, 2 );
		}
		return $provider;
	}

	public static function get_current_food(): ?array {
		return self::$current_food;
	}

	// -------------------------------------------------------------------------
	// Sitemap — registered via init
	// -------------------------------------------------------------------------

	public static function register_sitemap(): void {
		if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) { return; }

		$provider = new Food_Sitemap_Provider();
		wp_register_sitemap_provider( 'fcc-foods', $provider );
	}
}

// ---------------------------------------------------------------------------
// Custom Sitemap Provider
// ---------------------------------------------------------------------------

class Food_Sitemap_Provider extends \WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'fcc-foods';
		$this->object_type = 'fcc-food';
	}

	public function get_url_list( $page_num, $object_subtype = '' ): array {
		global $wpdb;
		$table   = $wpdb->prefix . 'fcc_foods';
		$per     = 2000;
		$offset  = ( $page_num - 1 ) * $per;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT slug, updated_at FROM {$table} WHERE is_active = 1 ORDER BY slug ASC LIMIT %d OFFSET %d",
			$per, $offset
		), ARRAY_A );

		$urls = [];
		foreach ( $rows ?? [] as $row ) {
			$urls[] = [
				'loc'     => home_url( '/food/' . $row['slug'] . '/' ),
				'lastmod' => $row['updated_at'] ? gmdate( 'Y-m-d\TH:i:s+00:00', strtotime( $row['updated_at'] ) ) : '',
			];
		}
		return $urls;
	}

	public function get_max_num_pages( $object_subtype = '' ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'fcc_foods';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_active = 1" );
		return (int) ceil( $total / 2000 );
	}
}
