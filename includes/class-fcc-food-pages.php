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
	private static ?string $page_type = null;
	private static ?array $current_category = null;

	public function register( Loader $loader ): void {
		$loader->add_action( 'init',              $this, 'add_rewrite_rules' );
		$loader->add_filter( 'query_vars',        $this, 'add_query_vars' );
		$loader->add_action( 'wp_enqueue_scripts', $this, 'maybe_enqueue_assets' );
		$loader->add_action( 'template_redirect', $this, 'handle_food_page' );
		$loader->add_action( 'wp_head',           $this, 'output_seo_meta', 1 );
		$loader->add_filter( 'document_title_parts', $this, 'filter_title' );
	}

	public function maybe_enqueue_assets(): void {
		if ( get_query_var( 'fcc_food_slug' ) || get_query_var( 'fcc_food_directory' ) || get_query_var( 'fcc_food_category_slug' ) ) {
			Shortcode::enqueue_public_assets();
		}
	}

	public function add_rewrite_rules(): void {
		// New /calories/ hierarchy.
		add_rewrite_rule( 'calories/([^/]+)/([^/]+)/?$', 'index.php?fcc_food_category_slug=$matches[1]&fcc_food_slug=$matches[2]', 'top' );
		add_rewrite_rule( 'calories/([^/]+)/?$', 'index.php?fcc_food_category_slug=$matches[1]', 'top' );
		add_rewrite_rule( 'calories/?$', 'index.php?fcc_food_directory=1', 'top' );
		// Legacy /food/ redirects — 301 to new URLs.
		add_rewrite_rule( 'food/category/([^/]+)/?$', 'index.php?fcc_food_category_slug=$matches[1]&fcc_food_redirect=category', 'top' );
		add_rewrite_rule( 'food/?$', 'index.php?fcc_food_directory=1&fcc_food_redirect=hub', 'top' );
		add_rewrite_rule( 'food/([^/]+)/?$', 'index.php?fcc_food_slug=$matches[1]&fcc_food_redirect=food', 'top' );

		// Auto-flush when plugin version changes so new rules take effect without manual Permalinks save.
		if ( get_option( 'fcc_rewrite_ver' ) !== FCC_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'fcc_rewrite_ver', FCC_VERSION );
		}
	}

	public function add_query_vars( array $vars ): array {
		$vars[] = 'fcc_food_slug';
		$vars[] = 'fcc_food_directory';
		$vars[] = 'fcc_food_category_slug';
		$vars[] = 'fcc_food_redirect';
		return $vars;
	}

	public function handle_food_page(): void {
		// Legacy /food/ 301 redirects.
		$redirect = get_query_var( 'fcc_food_redirect' );
		if ( $redirect ) {
			if ( 'hub' === $redirect ) {
				wp_safe_redirect( home_url( '/calories/' ), 301 );
				exit;
			}
			if ( 'category' === $redirect ) {
				$slug = get_query_var( 'fcc_food_category_slug' );
				wp_safe_redirect( home_url( '/calories/' . sanitize_title( $slug ) . '/' ), 301 );
				exit;
			}
			if ( 'food' === $redirect ) {
				$slug = get_query_var( 'fcc_food_slug' );
				$food = Database::get_food_by_slug( sanitize_title( $slug ) );
				if ( $food ) {
					$cat = Database::get_category( (int) $food['category_id'] );
					if ( $cat ) {
						wp_safe_redirect( home_url( '/calories/' . $cat['slug'] . '/' . $food['slug'] . '/' ), 301 );
						exit;
					}
				}
				wp_safe_redirect( home_url( '/calories/' ), 301 );
				exit;
			}
		}

		// Directory hub: /calories/
		if ( get_query_var( 'fcc_food_directory' ) ) {
			self::$page_type = 'directory';
			$this->render_directory_page();
			exit;
		}

		// Individual food page: /calories/{cat}/{slug}/ — both vars set.
		$cat_slug  = get_query_var( 'fcc_food_category_slug' );
		$food_slug = get_query_var( 'fcc_food_slug' );

		if ( $cat_slug && $food_slug ) {
			$cat  = Database::get_category_by_slug( sanitize_title( $cat_slug ) );
			$food = Database::get_food_by_slug( sanitize_title( $food_slug ) );

			if ( ! $food || ! $cat || (int) $food['category_id'] !== (int) $cat['id'] || empty( $food['is_active'] ) ) {
				wp_safe_redirect( home_url( '/calories/' ), 301 );
				exit;
			}

			self::$current_food = $food;
			self::$current_category = $cat;

			$food_id = $food['id'];
			add_filter( 'fcc_preload_food', function () use ( $food_id ) { return $food_id; } );
			add_filter( 'fcc_heading_tag', function () { return 'h2'; } );

			$this->render_food_page( $food );
			exit;
		}

		// Category page: /calories/{slug}/ — only category var set.
		if ( $cat_slug ) {
			$cat = Database::get_category_by_slug( sanitize_title( $cat_slug ) );
			if ( ! $cat ) {
				wp_safe_redirect( home_url( '/calories/' ), 301 );
				exit;
			}
			self::$page_type = 'category';
			self::$current_category = $cat;
			$this->render_category_page( $cat );
			exit;
		}
	}

	// -------------------------------------------------------------------------
	// URL helper
	// -------------------------------------------------------------------------

	private static function food_url( array $food ): string {
		$cat = Database::get_category( (int) $food['category_id'] );
		$cat_slug = $cat['slug'] ?? 'uncategorised';
		return home_url( '/calories/' . $cat_slug . '/' . $food['slug'] . '/' );
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

		// Breadcrumb: Home > Calories > {Category} > {Food Name}
		$bread_cat = Database::get_category( (int) $food['category_id'] );
		if ( $bread_cat ) {
			echo '<p class="fcc-category-page__breadcrumb">'
				. '<a href="' . esc_url( home_url( '/calories/' ) ) . '">Calories</a> &rsaquo; '
				. '<a href="' . esc_url( home_url( '/calories/' . $bread_cat['slug'] . '/' ) ) . '">' . esc_html( $bread_cat['name'] ) . '</a> &rsaquo; '
				. esc_html( $food['name'] )
				. '</p>';
		}

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
	// Directory Page (/food/)
	// -------------------------------------------------------------------------

	private function render_directory_page(): void {
		$categories = Database::get_category_food_counts();
		$total = 0;
		foreach ( $categories as $c ) { $total += (int) $c['food_count']; }

		status_header( 200 );
		get_header();

		echo '<div class="fcc-food-page fcc-directory" style="max-width:1000px;margin:0 auto;padding:2rem 1rem;">';
		echo '<h1 class="fcc-food-page__title">Calories &amp; Nutrition for ' . number_format( $total ) . '+ Foods</h1>';
		echo '<p class="fcc-food-page__intro">Browse calorie counts, macro breakdowns, and full nutrition facts by category. Use the calculator to search any food by name.</p>';

		echo '<div class="fcc-directory__grid">';
		foreach ( $categories as $cat ) {
			$url   = home_url( '/calories/' . $cat['slug'] . '/' );
			$count = (int) $cat['food_count'];
			echo '<a href="' . esc_url( $url ) . '" class="fcc-directory__card">';
			echo '<span class="fcc-directory__card-name">' . esc_html( $cat['name'] ) . '</span>';
			echo '<span class="fcc-directory__card-count">' . $count . ' ' . ( 1 === $count ? 'food' : 'foods' ) . '</span>';
			echo '</a>';
		}
		echo '</div>';

		// Show top foods per category.
		foreach ( $categories as $cat ) {
			$foods = Database::get_foods_in_category( (int) $cat['id'] );
			if ( empty( $foods ) ) { continue; }
			$cat_url = home_url( '/calories/' . $cat['slug'] . '/' );

			echo '<div class="fcc-directory__section">';
			echo '<h2><a href="' . esc_url( $cat_url ) . '">' . esc_html( $cat['name'] ) . '</a></h2>';
			echo '<ul class="fcc-directory__list">';
			$shown = array_slice( $foods, 0, 15 );
			foreach ( $shown as $f ) {
				$url = home_url( '/calories/' . $cat['slug'] . '/' . $f['slug'] . '/' );
				echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $f['name'] ) . '</a> <span class="fcc-directory__kcal">' . number_format( (float) $f['energy_kcal'], 0 ) . ' kcal</span></li>';
			}
			echo '</ul>';
			if ( count( $foods ) > 15 ) {
				echo '<a href="' . esc_url( $cat_url ) . '" class="fcc-directory__viewall">View all ' . count( $foods ) . ' ' . esc_html( $cat['name'] ) . ' foods &rarr;</a>';
			}
			echo '</div>';
		}

		echo '</div>';
		get_footer();
	}

	// -------------------------------------------------------------------------
	// Category Page (/food/category/{slug}/)
	// -------------------------------------------------------------------------

	private function render_category_page( array $cat ): void {
		$foods = Database::get_foods_in_category( (int) $cat['id'] );
		$count = count( $foods );

		$cat_descriptions = [
			'fruits & vegetables'    => 'Discover the nutritional value of fresh fruits and vegetables. These natural foods are rich in vitamins, minerals, and fibre, forming the foundation of a healthy diet.',
			'fruit & vegetables'     => 'Discover the nutritional value of fresh fruits and vegetables. These natural foods are rich in vitamins, minerals, and fibre, forming the foundation of a healthy diet.',
			'meat & poultry'         => 'Explore calorie and protein content for meat and poultry products. These foods are primary sources of complete protein, iron, and B vitamins in the diet.',
			'fish & seafood'         => 'Browse nutrition data for fish and seafood. These foods are valued for their protein content, omega-3 fatty acids, and essential minerals.',
			'dairy & eggs'           => 'View nutrition facts for dairy products and eggs. These foods provide calcium, protein, and essential vitamins important for bone health and growth.',
			'grains & cereals'       => 'Check calorie and carbohydrate content for grains, cereals, and bread products. These staple foods provide energy, fibre, and B vitamins.',
			'nuts & seeds'           => 'Explore the nutritional profile of nuts and seeds. These energy-dense foods are rich in healthy fats, protein, and minerals.',
			'legumes & pulses'       => 'Browse nutrition data for beans, lentils, and other pulses. These plant-based foods are excellent sources of protein, fibre, and iron.',
			'drinks'                 => 'View calorie counts and nutritional information for beverages. From water to smoothies, see what each drink contributes to your daily intake.',
			'condiments & sauces'    => 'Check nutrition facts for sauces, dressings, and condiments. These flavouring ingredients can vary significantly in their calorie and salt content.',
			'snacks & confectionery' => 'Browse calorie and sugar content for snacks and sweet treats. Understanding the nutritional value helps with mindful snacking and portion control.',
			'takeaway & ready meals' => 'View nutrition data for takeaway dishes and ready meals from around the world. These convenient foods range widely in their nutritional profiles.',
		];
		$desc = $cat_descriptions[ strtolower( $cat['name'] ) ] ?? 'Browse nutrition facts for foods in the ' . esc_html( $cat['name'] ) . ' category.';

		status_header( 200 );
		get_header();

		echo '<div class="fcc-food-page fcc-category-page" style="max-width:1000px;margin:0 auto;padding:2rem 1rem;">';
		echo '<p class="fcc-category-page__breadcrumb"><a href="' . esc_url( home_url( '/calories/' ) ) . '">Calories</a> &rsaquo; ' . esc_html( $cat['name'] ) . '</p>';
		echo '<h1 class="fcc-food-page__title">' . esc_html( $cat['name'] ) . ' &mdash; Calories &amp; Nutrition Facts</h1>';
		echo '<p class="fcc-food-page__intro">' . esc_html( $desc ) . ' Showing <strong>' . $count . '</strong> foods in this category.</p>';

		if ( $foods ) {
			echo '<div class="fcc-category-page__table-wrap">';
			echo '<table class="fcc-category-page__table">';
			echo '<thead><tr><th>Food</th><th>kcal</th><th>Protein</th><th>Fat</th></tr></thead>';
			echo '<tbody>';
			foreach ( $foods as $f ) {
				$url = home_url( '/calories/' . $cat['slug'] . '/' . $f['slug'] . '/' );
				echo '<tr>';
				echo '<td><a href="' . esc_url( $url ) . '">' . esc_html( $f['name'] ) . '</a></td>';
				echo '<td>' . number_format( (float) $f['energy_kcal'], 0 ) . '</td>';
				echo '<td>' . number_format( (float) $f['protein_g'], 1 ) . 'g</td>';
				echo '<td>' . number_format( (float) $f['fat_g'], 1 ) . 'g</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '</div>';
		}

		echo '<p style="margin-top:1.5rem;font-size:0.88rem;color:#718096;">All values are per 100g. Click any food to see the full nutritional breakdown, FSA traffic lights, and interactive calculator.</p>';
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

		$cat_context = [
			'fruits & vegetables'    => 'a natural whole food enjoyed worldwide as part of a balanced diet',
			'fruit & vegetables'     => 'a natural whole food enjoyed worldwide as part of a balanced diet',
			'meat & poultry'         => 'a protein-rich food commonly used in main meals across many cuisines',
			'fish & seafood'         => 'a seafood item valued for its nutritional profile and versatility in cooking',
			'dairy & eggs'           => 'a dairy or egg-based product that forms a staple part of many diets',
			'grains & cereals'       => 'a grain-based food that provides carbohydrates and energy for daily activities',
			'nuts & seeds'           => 'a nutrient-dense food rich in healthy fats, often eaten as a snack or ingredient',
			'legumes & pulses'       => 'a plant-based protein source widely used in soups, stews, and salads',
			'drinks'                 => 'a beverage consumed for hydration, energy, or enjoyment',
			'condiments & sauces'    => 'a flavouring or condiment used to enhance the taste of dishes',
			'snacks & confectionery' => 'a snack or confectionery item typically eaten between meals',
			'takeaway & ready meals' => 'a prepared or takeaway food designed for convenience',
		];
		$context = $cat_context[ strtolower( $cat['name'] ?? '' ) ] ?? 'a food item that can be part of a varied diet';

		// Build paragraph pool — each paragraph only included if its condition is met.
		$paragraphs = [];

		// 1. Context (always).
		$paragraphs[] = $name . ' is ' . $context . '. Per 100g serving, it provides ' . $kcal . ' kcal of energy, '
			. $prot . 'g of protein, ' . $carb . 'g of carbohydrates, and ' . $fat . 'g of fat.';

		// 2. Protein focus.
		if ( $prot_raw >= 15 ) {
			$rni_pct = round( $prot_raw / 50 * 100 );
			$paragraphs[] = 'It is ' . ( $prot_raw >= 25 ? 'an excellent' : 'a good' ) . ' source of protein, providing ' . $prot . 'g per 100g — approximately ' . $rni_pct . '% of the UK Reference Nutrient Intake of 50g per day. Protein plays a key role in muscle repair, immune function, and keeping you feeling full for longer.';
		}

		// 3. Fat profile.
		if ( $fat_raw >= 17.5 ) {
			$sat = null !== $food['of_which_saturates_g'] ? number_format( $food['of_which_saturates_g'], 1 ) . 'g of which is saturated' : 'check the label for saturated fat content';
			$paragraphs[] = 'Under UK FSA traffic-light labelling, this food is classified as high in fat with ' . $fat . 'g per 100g (' . $sat . '). The NHS recommends that fat makes up no more than 35% of daily energy intake. Portion awareness is advisable when including it regularly.';
		} elseif ( $fat_raw <= 3 ) {
			$paragraphs[] = 'With just ' . $fat . 'g of fat per 100g, it falls into the low-fat category under FSA traffic-light guidelines (below 3g per 100g). This makes it a lighter option for those monitoring their fat intake as part of a heart-healthy or weight-management diet.';
		}

		// 4. Omega-3.
		if ( null !== $food['omega3_total_mg'] && (float) $food['omega3_total_mg'] > 100 ) {
			$o3 = number_format( $food['omega3_total_mg'], 0 );
			$paragraphs[] = 'It provides ' . $o3 . 'mg of omega-3 fatty acids per 100g. The British Nutrition Foundation recommends eating at least one portion of oily fish per week for heart health. Omega-3s, particularly EPA and DHA, are linked to reduced inflammation, improved cardiovascular function, and cognitive benefits.';
		}

		// 5. Micronutrient spotlight (pick the most notable one).
		if ( null !== $food['iron_mg'] && (float) $food['iron_mg'] >= 2.0 ) {
			$iron = number_format( $food['iron_mg'], 1 );
			$paragraphs[] = 'This food is a useful source of iron, providing ' . $iron . 'mg per 100g. The UK recommended intake is 8.7mg for men and 14.8mg for women per day. Iron is essential for producing haemoglobin, which carries oxygen in the blood. Low iron intake is the most common nutritional deficiency in the UK.';
		} elseif ( null !== $food['calcium_mg'] && (float) $food['calcium_mg'] >= 100 ) {
			$cal = number_format( $food['calcium_mg'], 0 );
			$paragraphs[] = 'With ' . $cal . 'mg of calcium per 100g, it contributes meaningfully to daily calcium needs (the UK recommendation is 700mg for adults). Calcium is vital for maintaining strong bones and teeth, as well as supporting normal muscle and nerve function.';
		} elseif ( null !== $food['vitamin_c_mg'] && (float) $food['vitamin_c_mg'] >= 10 ) {
			$vc = number_format( $food['vitamin_c_mg'], 1 );
			$paragraphs[] = 'It contains ' . $vc . 'mg of vitamin C per 100g. The UK recommended daily intake is 40mg. Vitamin C is an antioxidant that supports immune function, skin health, wound healing, and enhances the absorption of non-haeme iron from plant-based foods.';
		}

		// 6. Fibre.
		if ( null !== $food['fibre_g'] && (float) $food['fibre_g'] >= 3 ) {
			$fib = number_format( $food['fibre_g'], 1 );
			$paragraphs[] = 'Each 100g serving provides ' . $fib . 'g of dietary fibre per 100g. The UK government recommends 30g of fibre per day for adults. Adequate fibre intake supports healthy digestion, helps regulate blood sugar levels, and may reduce the risk of heart disease and bowel cancer.';
		}

		// 7. Weight management.
		if ( $kcal_raw <= 100 && $fat_raw <= 3 ) {
			$paragraphs[] = 'At just ' . $kcal . ' kcal and ' . $fat . 'g of fat per 100g, this is a low-calorie, low-fat option that can support weight management goals. It can be included generously in calorie-controlled meals without significantly increasing overall energy intake.';
		}

		// 8. Dietary suitability (merged).
		$diets = [];
		if ( ! empty( $food['diet_keto'] ) )       { $diets[] = 'ketogenic (with only ' . $carb . 'g of carbs per 100g)'; }
		if ( ! empty( $food['diet_vegan'] ) )       { $diets[] = 'vegan'; }
		elseif ( ! empty( $food['diet_vegetarian'] ) ) { $diets[] = 'vegetarian'; }
		if ( ! empty( $food['diet_halal'] ) && ! empty( $food['diet_kosher'] ) ) { $diets[] = 'halal and kosher'; }
		elseif ( ! empty( $food['diet_halal'] ) )  { $diets[] = 'halal'; }
		if ( $diets ) {
			$paragraphs[] = $name . ' is suitable for ' . implode( ', ', $diets ) . ' diets, making it a versatile choice for households with mixed dietary requirements.';
		}

		// 9. Energy density.
		if ( $kcal_raw >= 400 ) {
			$paragraphs[] = 'This is an energy-dense food at ' . $kcal . ' kcal per 100g' . ( $fat_raw >= 17.5 ? ', largely due to its fat content (' . $fat . 'g per 100g). Fat provides 9 calories per gram — more than double that of protein or carbohydrates' : '' ) . '. While it can be part of a balanced diet, portion control is recommended to avoid exceeding daily calorie needs.';
		}

		// 10. Caffeine.
		if ( null !== $food['caffeine_mg'] && (float) $food['caffeine_mg'] > 10 ) {
			$caf = number_format( $food['caffeine_mg'], 0 );
			$paragraphs[] = 'It contains ' . $caf . 'mg of caffeine per 100g. The NHS advises that most adults can safely consume up to 400mg of caffeine per day, while pregnant women should limit intake to 200mg. Caffeine can improve alertness and concentration but may disrupt sleep if consumed late in the day.';
		}

		// Cap at 4 paragraphs max.
		if ( count( $paragraphs ) > 4 ) {
			$paragraphs = array_slice( $paragraphs, 0, 4 );
		}

		// Append CTA to the last paragraph.
		$last = count( $paragraphs ) - 1;
		$paragraphs[ $last ] .= ' Use the calculator above to adjust the serving size and explore the full nutritional breakdown.';

		echo '<div class="fcc-food-page__overview">';
		echo '<h2>Nutritional Overview</h2>';
		foreach ( $paragraphs as $p ) {
			echo '<p>' . $p . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
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
			$url = self::food_url( $r );
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
		// Directory page.
		if ( 'directory' === self::$page_type ) {
			echo '<meta name="description" content="Browse calories and nutrition facts for over 4,900 foods. Calories, protein, fat, carbs, vitamins, and minerals — free UK food calorie calculator.">' . "\n";
			echo '<link rel="canonical" href="' . esc_url( home_url( '/calories/' ) ) . '">' . "\n";
			return;
		}

		// Category page.
		if ( 'category' === self::$page_type && self::$current_category ) {
			$cn = esc_attr( self::$current_category['name'] );
			echo '<meta name="description" content="' . esc_attr( "{$cn} — browse calories, protein, fat, and full nutrition facts for all foods in this category. Free UK food calorie calculator." ) . '">' . "\n";
			echo '<link rel="canonical" href="' . esc_url( home_url( '/calories/' . self::$current_category['slug'] . '/' ) ) . '">' . "\n";
			return;
		}

		$food = self::$current_food;
		if ( ! $food ) { return; }

		$name = esc_attr( $food['name'] );
		$kcal = number_format( (float) $food['energy_kcal'], 0 );
		$prot = number_format( (float) $food['protein_g'], 1 );
		$carb = number_format( (float) $food['carbohydrate_g'], 1 );
		$fat  = number_format( (float) $food['fat_g'], 1 );
		$url  = self::food_url( $food );

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
		if ( 'directory' === self::$page_type ) {
			$title['title'] = 'Calorie Counter — Browse All Foods by Category';
			$title['site']  = 'Food Calorie Calculator';
		} elseif ( 'category' === self::$page_type && self::$current_category ) {
			$title['title'] = self::$current_category['name'] . ' — Calories & Nutrition';
			$title['site']  = 'Food Calorie Calculator';
		} elseif ( self::$current_food ) {
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
			"SELECT slug, category_id, updated_at FROM {$table} WHERE is_active = 1 ORDER BY slug ASC LIMIT %d OFFSET %d",
			$per, $offset
		), ARRAY_A );

		$urls = [];

		// First page: include /calories/ hub and category pages.
		if ( 1 === $page_num ) {
			$urls[] = [ 'loc' => home_url( '/calories/' ) ];
			$cats = $wpdb->get_results( "SELECT slug FROM {$wpdb->prefix}fcc_categories ORDER BY display_order ASC", ARRAY_A ); // phpcs:ignore
			foreach ( $cats ?? [] as $c ) {
				$urls[] = [ 'loc' => home_url( '/calories/' . $c['slug'] . '/' ) ];
			}
		}

		foreach ( $rows ?? [] as $row ) {
			$cat     = \FCC\Database::get_category( (int) $row['category_id'] );
			$cat_slug = $cat['slug'] ?? 'uncategorised';
			$urls[] = [
				'loc'     => home_url( '/calories/' . $cat_slug . '/' . $row['slug'] . '/' ),
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
