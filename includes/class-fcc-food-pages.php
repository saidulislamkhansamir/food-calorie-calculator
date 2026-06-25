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
		$loader->add_action( 'wp_enqueue_scripts', $this, 'maybe_enqueue_assets' );
		$loader->add_action( 'template_redirect', $this, 'handle_food_page' );
		$loader->add_action( 'wp_head',           $this, 'output_seo_meta', 1 );
		$loader->add_filter( 'document_title_parts', $this, 'filter_title' );
	}

	public function maybe_enqueue_assets(): void {
		if ( ! get_query_var( 'fcc_food_slug' ) ) { return; }
		Shortcode::enqueue_public_assets();
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

		// Tell the shortcode to preload this food by ID and use h2 for calculator title.
		$food_id = $food['id'];
		add_filter( 'fcc_preload_food', function () use ( $food_id ) {
			return $food_id;
		} );
		add_filter( 'fcc_heading_tag', function () { return 'h2'; } );

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

		// Nutritional overview (100-150 words).
		$this->render_nutritional_overview( $food );

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
	// Nutritional Overview (100-150 words)
	// -------------------------------------------------------------------------

	private function render_nutritional_overview( array $food ): void {
		$name     = esc_html( $food['name'] );
		$kcal     = number_format( (float) $food['energy_kcal'], 0 );
		$prot     = number_format( (float) $food['protein_g'], 1 );
		$carb     = number_format( (float) $food['carbohydrate_g'], 1 );
		$fat      = number_format( (float) $food['fat_g'], 1 );
		$prot_raw = (float) $food['protein_g'];
		$fat_raw  = (float) $food['fat_g'];
		$kcal_raw = (float) $food['energy_kcal'];

		$cat      = Database::get_category( (int) $food['category_id'] );
		$cat_name = $cat ? strtolower( $cat['name'] ) : 'food';

		$cat_context = [
			'fruits & vegetables' => 'a natural whole food enjoyed worldwide as part of a balanced diet',
			'fruit & vegetables'  => 'a natural whole food enjoyed worldwide as part of a balanced diet',
			'meat & poultry'      => 'a protein-rich food commonly used in main meals across many cuisines',
			'fish & seafood'      => 'a seafood item valued for its nutritional profile and versatility in cooking',
			'dairy & eggs'        => 'a dairy or egg-based product that forms a staple part of many diets',
			'grains & cereals'    => 'a grain-based food that provides carbohydrates and energy for daily activities',
			'nuts & seeds'        => 'a nutrient-dense food rich in healthy fats, often eaten as a snack or ingredient',
			'legumes & pulses'    => 'a plant-based protein source widely used in soups, stews, and salads',
			'drinks'              => 'a beverage consumed for hydration, energy, or enjoyment',
			'condiments & sauces' => 'a flavouring or condiment used to enhance the taste of dishes',
			'snacks & confectionery' => 'a snack or confectionery item typically eaten between meals',
			'takeaway & ready meals' => 'a prepared or takeaway food designed for convenience',
		];
		$context = $cat_context[ strtolower( $cat['name'] ?? '' ) ] ?? 'a food item that can be part of a varied diet';

		echo '<div class="fcc-food-page__overview">';
		echo '<h2>Nutritional Overview</h2>';

		// Paragraph 1: What it is.
		echo '<p>' . $name . ' is ' . $context . '. Per 100g serving, it provides ' . $kcal . ' kcal of energy, '
			. $prot . 'g of protein, ' . $carb . 'g of carbohydrates, and ' . $fat . 'g of fat.</p>';

		// Paragraph 2: Nutritional highlights (conditional).
		$highlights = [];
		if ( $prot_raw >= 15 ) {
			$highlights[] = 'It is notably high in protein (' . $prot . 'g per 100g), making it valuable for muscle maintenance, recovery, and satiety.';
		}
		if ( $fat_raw <= 3 ) {
			$highlights[] = 'With only ' . $fat . 'g of fat per 100g, it is classified as low-fat under UK FSA traffic-light guidelines.';
		}
		if ( $fat_raw >= 17.5 ) {
			$highlights[] = 'It is high in fat (' . $fat . 'g per 100g) according to FSA guidelines, so portion awareness is recommended.';
		}
		if ( null !== $food['omega3_total_mg'] && (float) $food['omega3_total_mg'] > 100 ) {
			$highlights[] = 'It is a notable source of omega-3 fatty acids (' . number_format( $food['omega3_total_mg'], 0 ) . 'mg per 100g), which support cardiovascular and cognitive health.';
		}
		if ( null !== $food['iron_mg'] && (float) $food['iron_mg'] >= 2.0 ) {
			$highlights[] = 'It provides ' . number_format( $food['iron_mg'], 1 ) . 'mg of iron per 100g, contributing to healthy red blood cell formation.';
		} elseif ( null !== $food['calcium_mg'] && (float) $food['calcium_mg'] >= 100 ) {
			$highlights[] = 'It is a useful source of calcium (' . number_format( $food['calcium_mg'], 0 ) . 'mg per 100g), supporting bone and dental health.';
		} elseif ( null !== $food['vitamin_c_mg'] && (float) $food['vitamin_c_mg'] >= 10 ) {
			$highlights[] = 'It contains ' . number_format( $food['vitamin_c_mg'], 1 ) . 'mg of vitamin C per 100g, supporting immune function and skin health.';
		}
		if ( null !== $food['fibre_g'] && (float) $food['fibre_g'] >= 3 ) {
			$highlights[] = 'With ' . number_format( $food['fibre_g'], 1 ) . 'g of fibre per 100g, it supports digestive health.';
		}
		if ( null !== $food['caffeine_mg'] && (float) $food['caffeine_mg'] > 10 ) {
			$highlights[] = 'It contains ' . number_format( $food['caffeine_mg'], 0 ) . 'mg of caffeine per 100g.';
		}
		if ( $highlights ) {
			echo '<p>' . implode( ' ', $highlights ) . '</p>';
		}

		// Paragraph 3: Who benefits (conditional).
		$benefits = [];
		if ( $kcal_raw <= 100 && $fat_raw <= 3 ) {
			$benefits[] = 'those managing their weight or following a calorie-controlled eating plan';
		}
		if ( $prot_raw >= 15 ) {
			$benefits[] = 'athletes, gym-goers, and anyone looking to increase their daily protein intake';
		}
		if ( ! empty( $food['diet_keto'] ) ) {
			$benefits[] = 'individuals following a ketogenic or low-carbohydrate diet';
		}
		if ( ! empty( $food['diet_vegan'] ) ) {
			$benefits[] = 'those on a plant-based or vegan diet';
		} elseif ( ! empty( $food['diet_vegetarian'] ) ) {
			$benefits[] = 'vegetarians seeking varied meal options';
		}

		if ( $benefits ) {
			echo '<p>' . $name . ' may be particularly suitable for ' . implode( ', ', $benefits )
				. '. Use the calculator above to adjust the serving size and see personalised nutrition values.</p>';
		} else {
			echo '<p>' . $name . ' can be enjoyed as part of a balanced diet. Use the calculator above to adjust the serving size and see personalised nutrition values for your chosen portion.</p>';
		}

		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// FAQ Section
	// -------------------------------------------------------------------------

	private function build_faqs( array $food ): array {
		$name = $food['name'];
		$kcal_raw = (float) $food['energy_kcal'];
		$kcal = number_format( $kcal_raw, 0 );
		$prot_raw = (float) $food['protein_g'];
		$prot = number_format( $prot_raw, 1 );
		$fat_raw = (float) $food['fat_g'];
		$fat  = number_format( $fat_raw, 1 );
		$carb_raw = (float) $food['carbohydrate_g'];
		$carb = number_format( $carb_raw, 1 );
		$sugars_raw = null !== $food['of_which_sugars_g'] ? (float) $food['of_which_sugars_g'] : null;
		$salt_raw = null !== $food['salt_g'] ? (float) $food['salt_g'] : null;
		$daily_pct = round( $kcal_raw / 2000 * 100 );

		$faqs = [];

		// 1. Always: calorie question.
		$faqs[] = [
			'q' => "How many calories are in {$name}?",
			'a' => "{$name} contains {$kcal} kcal per 100g, which is approximately {$daily_pct}% of the recommended daily intake of 2,000 kcal for an average adult."
				. ( $kcal_raw <= 100 ? " This makes it a relatively low-calorie food choice." : '' )
				. ( $kcal_raw >= 400 ? " This is considered energy-dense." : '' ),
		];

		// 2. High protein (≥15g).
		if ( $prot_raw >= 15 ) {
			$faqs[] = [
				'q' => "Is {$name} high in protein?",
				'a' => "Yes, {$name} provides {$prot}g of protein per 100g, making it " . ( $prot_raw >= 25 ? 'an excellent' : 'a good' ) . " source of protein. The UK Reference Nutrient Intake for protein is around 50g per day for adults. A 100g serving of {$name} provides " . round( $prot_raw / 50 * 100 ) . "% of this.",
			];
		}

		// 3. Low protein (<3g).
		if ( $prot_raw < 3 ) {
			$faqs[] = [
				'q' => "Is {$name} a good source of protein?",
				'a' => "{$name} contains {$prot}g of protein per 100g, which is relatively low. If you are looking to increase your protein intake, consider pairing it with protein-rich foods such as lean meat, fish, eggs, or legumes.",
			];
		}

		// 4. High fat (FSA ≥17.5g).
		if ( $fat_raw >= 17.5 ) {
			$faqs[] = [
				'q' => "Is {$name} high in fat?",
				'a' => "Yes, according to FSA traffic-light guidelines, {$name} is classified as high in fat with {$fat}g per 100g (the threshold is 17.5g). Of this, " . ( null !== $food['of_which_saturates_g'] ? number_format( $food['of_which_saturates_g'], 1 ) . 'g is saturated fat.' : 'saturated fat content should be checked on the label.' ),
			];
		}

		// 5. Low fat (FSA ≤3g).
		if ( $fat_raw <= 3 && $fat_raw >= 0 ) {
			$faqs[] = [
				'q' => "Is {$name} low in fat?",
				'a' => "Yes, {$name} contains only {$fat}g of fat per 100g, which is classified as low fat under FSA traffic-light guidelines (below 3g per 100g). This makes it a suitable choice for those watching their fat intake.",
			];
		}

		// 6. High sugar (FSA ≥22.5g).
		if ( null !== $sugars_raw && $sugars_raw >= 22.5 ) {
			$faqs[] = [
				'q' => "Is {$name} high in sugar?",
				'a' => "{$name} contains " . number_format( $sugars_raw, 1 ) . "g of sugar per 100g, which the FSA classifies as high (above 22.5g per 100g). If you are managing sugar intake, consider moderating portion sizes.",
			];
		}

		// 7. Low sugar (FSA ≤5g).
		if ( null !== $sugars_raw && $sugars_raw <= 5 && $sugars_raw >= 0 ) {
			$faqs[] = [
				'q' => "Is {$name} low in sugar?",
				'a' => "Yes, {$name} has only " . number_format( $sugars_raw, 1 ) . "g of sugar per 100g, which is classified as low under FSA guidelines (below 5g per 100g).",
			];
		}

		// 8. High salt (FSA ≥1.5g).
		if ( null !== $salt_raw && $salt_raw >= 1.5 ) {
			$faqs[] = [
				'q' => "Is {$name} high in salt?",
				'a' => "{$name} contains " . number_format( $salt_raw, 2 ) . "g of salt per 100g, which the FSA classifies as high (above 1.5g per 100g). The recommended daily maximum for adults is 6g. A 100g serving provides " . round( $salt_raw / 6 * 100 ) . "% of this.",
			];
		}

		// 9. Keto.
		if ( ! empty( $food['diet_keto'] ) ) {
			$faqs[] = [
				'q' => "Is {$name} keto-friendly?",
				'a' => "Yes, with only {$carb}g of carbohydrates per 100g, {$name} fits within a standard ketogenic diet that typically limits carbs to 20-50g per day.",
			];
		}

		// 10. Vegan.
		if ( ! empty( $food['diet_vegan'] ) ) {
			$faqs[] = [
				'q' => "Is {$name} suitable for vegans?",
				'a' => "Yes, {$name} is suitable for a vegan diet as it contains no animal-derived ingredients. It provides {$kcal} kcal and {$prot}g of protein per 100g.",
			];
		}

		// 11. Vegetarian (but not vegan).
		if ( ! empty( $food['diet_vegetarian'] ) && empty( $food['diet_vegan'] ) ) {
			$faqs[] = [
				'q' => "Is {$name} suitable for vegetarians?",
				'a' => "Yes, {$name} is suitable for a vegetarian diet, though it is not vegan as it may contain dairy, eggs, or other animal by-products.",
			];
		}

		// 12. Halal.
		if ( ! empty( $food['diet_halal'] ) && ( ! empty( $food['allergen_fish'] ) || $prot_raw >= 10 ) ) {
			$faqs[] = [
				'q' => "Is {$name} halal?",
				'a' => "Yes, {$name} is considered halal. However, preparation methods and cross-contamination may vary, so always verify with the specific brand or supplier.",
			];
		}

		// 13. Contains allergens.
		$allergens = [];
		$allergen_map = [ 'allergen_fish' => 'fish', 'allergen_shellfish' => 'shellfish', 'allergen_dairy' => 'dairy', 'allergen_eggs' => 'eggs', 'allergen_nuts' => 'tree nuts', 'allergen_gluten' => 'gluten', 'allergen_soy' => 'soy', 'allergen_celery' => 'celery' ];
		foreach ( $allergen_map as $key => $label ) {
			if ( ! empty( $food[ $key ] ) ) { $allergens[] = $label; }
		}
		if ( $allergens ) {
			$faqs[] = [
				'q' => "Does {$name} contain any allergens?",
				'a' => "Yes, {$name} contains the following allergens: " . implode( ', ', $allergens ) . ". Under UK food labelling regulations, these must be clearly declared. Always check the product label if you have food allergies or intolerances.",
			];
		}

		// 14. Allergen-free.
		if ( ! $allergens ) {
			$faqs[] = [
				'q' => "Is {$name} free from major allergens?",
				'a' => "{$name} does not contain any of the 14 major allergens recognised under UK food law. However, cross-contamination during manufacturing is always possible — check the packaging for 'may contain' warnings.",
			];
		}

		// 15. Good source of iron (≥2mg).
		if ( null !== $food['iron_mg'] && (float) $food['iron_mg'] >= 2.0 ) {
			$faqs[] = [
				'q' => "Is {$name} a good source of iron?",
				'a' => "{$name} contains " . number_format( $food['iron_mg'], 2 ) . "mg of iron per 100g. The UK recommended daily intake is 8.7mg for men and 14.8mg for women. Iron is essential for red blood cell production and preventing anaemia.",
			];
		}

		// 16. Good source of calcium (≥100mg).
		if ( null !== $food['calcium_mg'] && (float) $food['calcium_mg'] >= 100 ) {
			$faqs[] = [
				'q' => "Is {$name} a good source of calcium?",
				'a' => "{$name} provides " . number_format( $food['calcium_mg'], 0 ) . "mg of calcium per 100g. The UK recommended daily intake is 700mg for adults. Calcium is vital for strong bones, teeth, and muscle function.",
			];
		}

		// 17. Rich in vitamin C (≥10mg).
		if ( null !== $food['vitamin_c_mg'] && (float) $food['vitamin_c_mg'] >= 10 ) {
			$faqs[] = [
				'q' => "Is {$name} rich in vitamin C?",
				'a' => "Yes, {$name} contains " . number_format( $food['vitamin_c_mg'], 1 ) . "mg of vitamin C per 100g. The UK recommended daily intake is 40mg. Vitamin C supports immune function, skin health, and iron absorption.",
			];
		}

		// 18. Contains omega-3.
		if ( null !== $food['omega3_total_mg'] && (float) $food['omega3_total_mg'] > 0 ) {
			$faqs[] = [
				'q' => "Does {$name} contain omega-3 fatty acids?",
				'a' => "Yes, {$name} contains " . number_format( $food['omega3_total_mg'], 0 ) . "mg of total omega-3 fatty acids per 100g. Omega-3s are essential fats that support heart health, brain function, and may reduce inflammation.",
			];
		}

		// 19. Contains caffeine.
		if ( null !== $food['caffeine_mg'] && (float) $food['caffeine_mg'] > 0 ) {
			$faqs[] = [
				'q' => "Does {$name} contain caffeine?",
				'a' => "{$name} contains " . number_format( $food['caffeine_mg'], 0 ) . "mg of caffeine per 100g. The NHS recommends no more than 400mg of caffeine per day for most adults, and 200mg per day during pregnancy.",
			];
		}

		// 20. Good for weight loss (low kcal + low fat).
		if ( $kcal_raw <= 100 && $fat_raw <= 3 ) {
			$faqs[] = [
				'q' => "Is {$name} good for weight loss?",
				'a' => "With only {$kcal} kcal and {$fat}g of fat per 100g, {$name} is a low-calorie, low-fat food that can be a helpful part of a calorie-controlled diet for weight management.",
			];
		}

		// 21. Energy-dense (≥400 kcal).
		if ( $kcal_raw >= 400 ) {
			$faqs[] = [
				'q' => "Why is {$name} so high in calories?",
				'a' => "At {$kcal} kcal per 100g, {$name} is energy-dense" . ( $fat_raw >= 17.5 ? ", primarily due to its high fat content ({$fat}g per 100g). Fat provides 9 calories per gram, more than double that of protein or carbohydrates." : ". Portion control is recommended if you are managing your calorie intake." ),
			];
		}

		return $faqs;
	}

	private function render_faq( array $food ): void {
		$faqs = $this->build_faqs( $food );

		echo '<div class="fcc-food-page__faq">';
		echo '<h2>Frequently Asked Questions</h2>';
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
		$faqs = $this->build_faqs( $food );
		$entities = [];
		foreach ( $faqs as $faq ) {
			$entities[] = [
				'@type' => 'Question',
				'name'  => $faq['q'],
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => $faq['a'],
				],
			];
		}
		return [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		];
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
