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
		$loader->add_action( 'wp_head',              $this, 'output_seo_meta', 1 );
		$loader->add_filter( 'document_title_parts', $this, 'filter_title',       999 );
		$loader->add_filter( 'pre_get_document_title', $this, 'override_title_pre', 999 );
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

	private function inject_page_spacing_fix(): void {
		add_action( 'wp_head', static function () {
			echo '<style>
.wd-page-content,.main-page-wrapper,
#main-content,.wd-content-layout,.content-layout-wrapper{padding-top:0!important;margin-top:0!important}
</style>' . "\n";
		}, 99 );
	}

	private function render_food_page( array $food ): void {
		// Ensure shortcode assets load.
		$shortcode = new Shortcode();

		status_header( 200 );
		$this->inject_page_spacing_fix();
		get_header();

		echo '<div class="fcc-food-page" style="max-width:900px;margin:0 auto;padding:1rem;">';

		// Breadcrumb: Home > Calories > {Category} > {Food Name}
		$bread_cat = Database::get_category( (int) $food['category_id'] );
		if ( $bread_cat ) {
			echo '<p class="fcc-category-page__breadcrumb">'
				. '<a href="' . esc_url( home_url( '/' ) ) . '">Home</a> &rsaquo; '
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
		$this->inject_page_spacing_fix();
		get_header();

		echo '<div class="fcc-food-page fcc-directory" style="max-width:1000px;margin:0 auto;padding:1rem;">';
		echo '<p class="fcc-category-page__breadcrumb"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a> &rsaquo; Calories</p>';
		$hub_intro = Settings::get( 'content.hub_intro' );
		if ( '' === $hub_intro ) {
			$hub_intro = 'Browse calorie counts, macro breakdowns, and full nutrition facts by category. Use the calculator to search any food by name.';
		}
		echo '<h1 class="fcc-food-page__title">Calories &amp; Nutrition for ' . number_format( $total ) . '+ Foods</h1>';
		echo '<p class="fcc-food-page__intro">' . wp_kses_post( $hub_intro ) . '</p>';

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

		// Editorial content — semantic SEO section.
		$hub_editorial = Settings::get( 'content.hub_editorial' );
		if ( ! empty( $hub_editorial ) ) {
			echo '<div class="fcc-food-page__editorial">';
			echo wp_kses_post( $hub_editorial );
			echo '</div>';
		} else {
			$cal_url  = esc_url( home_url( '/calories/' ) );
			$fv_url   = esc_url( home_url( '/calories/fruit-veg/' ) );
			$mp_url   = esc_url( home_url( '/calories/meat-poultry/' ) );
			$fs_url   = esc_url( home_url( '/calories/fish-seafood/' ) );
			$bc_url   = esc_url( home_url( '/calories/bread-cereals/' ) );
			$lp_url   = esc_url( home_url( '/calories/legumes-pulses/' ) );
			$ns_url   = esc_url( home_url( '/calories/nuts-seeds/' ) );
			$fo_url   = esc_url( home_url( '/calories/fats-oils/' ) );
			$dk_url   = esc_url( home_url( '/calories/drinks/' ) );
			$sc_url   = esc_url( home_url( '/calories/snacks-confectionery/' ) );

			echo '<div class="fcc-food-page__editorial">';

			echo '<h2>What Are Calories?</h2>';
			echo '<p>A calorie, more precisely a kilocalorie (kcal), is the unit used to measure the energy provided by food and drink. Your body uses this energy continuously: to breathe, maintain body temperature, power muscle movement, and support every metabolic process that keeps you alive. When your calorie intake matches your energy expenditure, your weight stays broadly stable. Consume more than you burn and the surplus is stored as body fat; consume less and your body draws on those stores instead.</p>';
			echo '<p>The <a href="https://www.nhs.uk/live-well/eat-well/food-guidelines-and-food-labels/understanding-calories/" target="_blank" rel="noopener">NHS recommends</a> that adult women aim for approximately <strong>2,000 kcal per day</strong> and adult men around <strong>2,500 kcal per day</strong>. These are population averages; your personal requirement depends on your age, height, current weight, and how active you are. Use the Daily Calorie Need tab inside the calculator above to get a personalised estimate based on your details.</p>';

			echo '<h2>Understanding Macronutrients</h2>';
			echo '<p>Every calorie you eat comes from one of three macronutrients: carbohydrates, protein, or fat, each used differently by the body:</p>';
			echo '<ul>';
			echo '<li><strong>Carbohydrates</strong> provide 4 kcal per gram and are the body\'s preferred fuel source, particularly for the brain and during exercise. The UK government recommends carbohydrates make up around 50% of total energy intake. <a href="' . $bc_url . '">Breads, grains, and cereals</a> are the most common sources in a typical UK diet.</li>';
			echo '<li><strong>Protein</strong> provides 4 kcal per gram and is essential for building and repairing muscle tissue, producing enzymes and hormones, and supporting immune function. The UK Reference Nutrient Intake (RNI) for protein is 50g per day. <a href="' . $mp_url . '">Meat and poultry</a>, <a href="' . $fs_url . '">fish and seafood</a>, and <a href="' . $lp_url . '">legumes and pulses</a> are the richest dietary sources.</li>';
			echo '<li><strong>Fat</strong> provides 9 kcal per gram, more than twice that of carbohydrates or protein, and is vital for absorbing fat-soluble vitamins A, D, E, and K, hormone production, and insulating body organs. The NHS recommends fat provides no more than 35% of daily energy. Browse <a href="' . $ns_url . '">nuts and seeds</a> or <a href="' . $fo_url . '">fats and oils</a> to compare fat profiles across common ingredients.</li>';
			echo '</ul>';
			echo '<p>Dietary fibre, a form of carbohydrate the body cannot fully digest, contributes around 2 kcal per gram and plays a crucial role in gut health, blood sugar regulation, and reducing the risk of bowel disease. UK adults are advised to consume at least <strong>30g of fibre per day</strong>, yet most people fall well short. <a href="' . $fv_url . '">Fruits and vegetables</a> and <a href="' . $lp_url . '">legumes and pulses</a> are among the best natural sources.</p>';

			echo '<h2>How to Use This Calorie Calculator</h2>';
			echo '<p>This tool gives you instant access to calorie counts and full nutrition data for over 5,200 foods from more than 190 countries. To get started, type the name of any food into the search box at the top of this page; results appear as you type. Select a food to see its energy content alongside a complete breakdown of protein, carbohydrates, fat, fibre, sugars, salt, and key micronutrients including iron, calcium, and vitamin C.</p>';
			echo '<p>You can adjust the serving size using the quantity and unit controls to match what you actually eat, switching between grams, ounces, or common portion sizes. Use the <strong>Your Meal</strong> tab to build a full day\'s intake by adding multiple foods; the running total shows calories and macros at a glance. The <strong>Compare</strong> tab places two foods side by side so you can spot nutritional differences instantly. Not sure where to start? Browse by category below, or try popular pages such as <a href="' . $sc_url . '">snacks and confectionery</a> or <a href="' . $dk_url . '">drinks</a>.</p>';

			echo '<h2>FSA Traffic Light Labels Explained</h2>';
			echo '<p>Every food page on this site displays <a href="https://www.food.gov.uk/safety-hygiene/food-labels" target="_blank" rel="noopener">UK Food Standards Agency (FSA) traffic-light labels</a> for fat, saturated fat, sugar, and salt. These colour-coded ratings, green (low), amber (medium), and red (high), are the same system printed on supermarket packaging and give you an instant read on how a food fits into a healthy diet. As a general guide:</p>';
			echo '<ul>';
			echo '<li>Green for fat: less than 3g per 100g; red: more than 17.5g per 100g</li>';
			echo '<li>Green for sugar: less than 5g per 100g; red: more than 22.5g per 100g</li>';
			echo '<li>Green for salt: less than 0.3g per 100g; red: more than 1.5g per 100g</li>';
			echo '</ul>';
			echo '<p>Choosing mostly green- and amber-rated foods is a practical, evidence-based approach to building a balanced diet, endorsed by the NHS, <a href="https://www.diabetes.org.uk/guide-to-diabetes/enjoy-food/eating-with-diabetes/food-labelling/traffic-light-labelling" target="_blank" rel="noopener">Diabetes UK</a>, and the <a href="https://www.bhf.org.uk/informationsupport/heart-matters-magazine/nutrition/reading-food-labels" target="_blank" rel="noopener">British Heart Foundation</a>.</p>';

			echo '<h2>Why Track What You Eat?</h2>';
			echo '<p>Research consistently shows that people who monitor their food intake, even loosely, make better dietary choices and are more likely to achieve and maintain a healthy weight. Calorie awareness does not have to mean obsessive counting. Simply knowing that a large latte adds around 250 kcal, or that two tablespoons of olive oil contain more calories than a small banana, helps you make informed trade-offs without giving anything up entirely.</p>';
			echo '<p>Beyond weight management, tracking nutrition helps you spot gaps in your diet: too little protein if you are active, insufficient iron if you feel persistently tired, or hidden salt if you are managing blood pressure. According to the <a href="https://www.nutrition.org.uk/" target="_blank" rel="noopener">British Nutrition Foundation</a>, most UK adults do not meet recommendations for fibre, oily fish, or fruit and vegetable intake. This calculator covers all of those markers, making it a useful tool whether your goal is weight loss, muscle gain, general wellbeing, or simply eating a little more mindfully.</p>';

			echo '<h2>About Our Nutrition Data</h2>';
			echo '<p>All nutritional values on this site are sourced from established, peer-reviewed databases including <a href="https://www.gov.uk/government/publications/composition-of-foods-integrated-dataset-cofid" target="_blank" rel="noopener">McCance &amp; Widdowson\'s Composition of Foods</a> (the definitive UK food composition tables published by the Department of Health), the <a href="https://fdc.nal.usda.gov/" target="_blank" rel="noopener">USDA FoodData Central</a> database, and guidance from the <a href="https://www.nutrition.org.uk/" target="_blank" rel="noopener">British Nutrition Foundation</a>. Values are provided per 100g unless otherwise stated and are intended for general informational purposes. Individual products may vary by brand, preparation method, and serving size. For clinical dietary advice, please consult a <a href="https://www.bda.uk.com/find-a-dietitian.html" target="_blank" rel="noopener">registered dietitian</a>.</p>';

			echo '</div>'; // .fcc-food-page__editorial
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

		// DB description takes priority; fall back to built-in descriptions, then generic.
		if ( ! empty( $cat['description'] ) ) {
			$desc = $cat['description'];
		} else {
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
			$desc = $cat_descriptions[ strtolower( $cat['name'] ) ] ?? 'Browse nutrition facts for foods in the ' . $cat['name'] . ' category.';
		}

		status_header( 200 );
		$this->inject_page_spacing_fix();
		get_header();

		echo '<div class="fcc-food-page fcc-category-page" style="max-width:1000px;margin:0 auto;padding:1rem;">';
		echo '<p class="fcc-category-page__breadcrumb"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a> &rsaquo; <a href="' . esc_url( home_url( '/calories/' ) ) . '">Calories</a> &rsaquo; ' . esc_html( $cat['name'] ) . '</p>';
		echo '<h1 class="fcc-food-page__title">' . $this->get_category_h1( $cat ) . '</h1>';
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

		$this->render_category_editorial( $cat );

		echo '</div>';
		get_footer();
	}

	// -------------------------------------------------------------------------
	// Category Editorial Content (one section per category slug)
	// -------------------------------------------------------------------------

	private function render_category_editorial( array $cat ): void {
		$slug = $cat['slug'];

		$known = [
			'fruit-veg', 'meat-poultry', 'fish-seafood', 'dairy-eggs',
			'bread-cereals', 'nuts-seeds', 'fats-oils', 'drinks',
			'snacks-confectionery', 'takeaway', 'legumes-pulses',
			'condiments-sauces', 'condiments',
		];
		if ( ! in_array( $slug, $known, true ) ) { return; }

		$hub = esc_url( home_url( '/calories/' ) );
		$fv  = esc_url( home_url( '/calories/fruit-veg/' ) );
		$mp  = esc_url( home_url( '/calories/meat-poultry/' ) );
		$fs  = esc_url( home_url( '/calories/fish-seafood/' ) );
		$de  = esc_url( home_url( '/calories/dairy-eggs/' ) );
		$bc  = esc_url( home_url( '/calories/bread-cereals/' ) );
		$ns  = esc_url( home_url( '/calories/nuts-seeds/' ) );
		$fo  = esc_url( home_url( '/calories/fats-oils/' ) );
		$dk  = esc_url( home_url( '/calories/drinks/' ) );
		$sc  = esc_url( home_url( '/calories/snacks-confectionery/' ) );
		$ta  = esc_url( home_url( '/calories/takeaway/' ) );
		$lp  = esc_url( home_url( '/calories/legumes-pulses/' ) );

		echo '<div class="fcc-food-page__editorial">';

		switch ( $slug ) {

			case 'fruit-veg':
				echo '<h2>Why Fruit and Vegetables Are Essential</h2>';
				echo '<p>The UK government\'s <a href="https://www.nhs.uk/live-well/eat-well/5-a-day/why-5-a-day/" target="_blank" rel="noopener">5 A Day recommendation</a> is based on World Health Organisation guidance that consuming at least 400g of fruit and vegetables daily reduces the risk of heart disease, stroke, type 2 diabetes, and certain cancers. Despite this clear guidance, fewer than a third of UK adults consistently meet the target. Most fresh fruit and vegetables are naturally low in calories, typically between 20 and 90 kcal per 100g, making them ideal for filling your plate without significantly increasing your energy intake. Their high water and fibre content also helps you feel full for longer, which supports healthy weight management over time.</p>';
				echo '<h2>Key Nutrients and Health Benefits</h2>';
				echo '<p>Fruit and vegetables provide vitamins and minerals that are difficult to obtain in sufficient quantities from other food groups. Vitamin C, found in high concentrations in citrus fruits, kiwi, strawberries, and red peppers, supports immune function, skin health, wound healing, and the absorption of non-haeme iron from plant foods. The <a href="https://www.nutrition.org.uk/healthy-sustainable-diets/fruit-and-veg/" target="_blank" rel="noopener">British Nutrition Foundation</a> notes the UK recommended daily intake for vitamin C is 40mg, an amount easily met by a single portion of citrus fruit. Leafy greens are rich in folate, important for cell division and especially critical during pregnancy. Orange and yellow produce provides beta-carotene, which converts to vitamin A in the body, essential for vision and immune defence. Potassium, found in bananas, potatoes, and tomatoes, helps regulate blood pressure and supports heart health.</p>';
				echo '<h2>Eating a Variety and Hitting Your 5 A Day</h2>';
				echo '<p>Different colours in fruit and vegetables signal different protective plant compounds called phytonutrients. Red and pink produce contains lycopene; purple and blue fruits such as blueberries and red cabbage provide anthocyanins with anti-inflammatory properties; and green vegetables supply lutein and folate. Eating a wide range of colours each week maximises the variety of these compounds in your diet. Fresh, frozen, canned, and dried fruit and vegetables all count towards your 5 A Day. Frozen options are often nutritionally comparable to fresh, as they are processed shortly after harvest. Pair this category with <a href="' . $lp . '">legumes and pulses</a> for additional plant protein and fibre, or browse the full <a href="' . $hub . '">food calorie database</a> to explore all categories.</p>';
				break;

			case 'meat-poultry':
				echo '<h2>Protein and Key Nutrients in Meat and Poultry</h2>';
				echo '<p>Meat and poultry are among the most protein-rich foods in the diet. A 100g serving of cooked chicken breast provides around 31g of protein, while lean beef and lamb deliver comparable amounts. Protein from meat is complete, meaning it contains all nine essential amino acids the body cannot synthesise on its own. Beyond protein, meat is one of the best dietary sources of haem iron, the form most readily absorbed by the body, alongside zinc, selenium, and B vitamins including B12. Vitamin B12 is found almost exclusively in animal products and is essential for nerve function and red blood cell production. The <a href="https://www.nhs.uk/live-well/eat-well/food-types/meat-nutrition/" target="_blank" rel="noopener">NHS confirms</a> that meat can be part of a healthy balanced diet when consumed in appropriate amounts.</p>';
				echo '<h2>Red Meat, White Meat, and Health</h2>';
				echo '<p>UK dietary guidance distinguishes between red meat (beef, lamb, pork) and white meat (chicken, turkey). Red meat is higher in saturated fat than most poultry and has been associated with increased colorectal cancer risk at high consumption levels. The <a href="https://www.wcrf.org/dietandcancer/red-and-processed-meat/" target="_blank" rel="noopener">World Cancer Research Fund</a> recommends limiting red and processed meat to no more than 500g of cooked weight per week and eating as little processed meat as possible. White meat, particularly skinless chicken and turkey, is lower in saturated fat and is often recommended as a leaner protein source. Removing skin from poultry before cooking significantly reduces calorie and fat content.</p>';
				echo '<h2>UK Guidance and Choosing Lean Cuts</h2>';
				echo '<p>The NHS advises that if you currently eat more than 90g of red or processed meat per day, reducing your intake is advisable. Processed meats such as bacon, sausages, ham, and salami have been classified as Group 1 carcinogens by the World Health Organisation due to consistent evidence linking them to bowel cancer. For those who eat meat, choosing leaner cuts, removing visible fat before cooking, and using lower-fat methods such as grilling, baking, or steaming rather than frying helps manage saturated fat and calorie intake. Compare meat with <a href="' . $fs . '">fish and seafood</a> for a lower-saturated-fat protein option, or explore <a href="' . $lp . '">legumes and pulses</a> for plant-based alternatives.</p>';
				break;

			case 'fish-seafood':
				echo '<h2>Why Fish Is a Key Part of a Healthy Diet</h2>';
				echo '<p>Fish and seafood provide high-quality protein alongside a nutritional profile that differs meaningfully from land-based animal foods. The <a href="https://www.nhs.uk/live-well/eat-well/food-types/fish-and-shellfish-nutrition/" target="_blank" rel="noopener">NHS recommends</a> eating at least two portions of fish per week, one of which should be oily. This recommendation is based on strong evidence linking regular fish consumption to reduced cardiovascular disease risk, improved brain function, and better outcomes in inflammatory conditions. Fish is generally lower in saturated fat than red meat, making it a heart-friendly protein source that fits well into a calorie-controlled or heart-healthy diet.</p>';
				echo '<h2>Oily Fish and Omega-3 Fatty Acids</h2>';
				echo '<p>Oily fish including salmon, mackerel, sardines, herring, and fresh tuna are among the richest dietary sources of long-chain omega-3 fatty acids, specifically EPA and DHA. These fats play a central role in cardiovascular health, reducing triglyceride levels, lowering blood pressure, and decreasing the risk of irregular heart rhythms. The <a href="https://www.bhf.org.uk/informationsupport/heart-matters-magazine/nutrition/fish-and-omega-3" target="_blank" rel="noopener">British Heart Foundation</a> recommends two portions of oily fish per week as part of a heart-healthy diet. DHA is also critical for fetal brain and eye development, making oily fish particularly important during pregnancy.</p>';
				echo '<h2>Calories, White Fish, and Sustainable Choices</h2>';
				echo '<p>White fish such as cod, haddock, and pollock are very low in fat and calories, with a 100g cooked portion typically providing around 80 to 100 kcal, making them ideal for calorie-controlled eating. Shellfish including prawns, mussels, and oysters are low in calories and fat while being rich in zinc, iodine, and selenium. When selecting fish, the <a href="https://www.mcsuk.org/goodfishguide/" target="_blank" rel="noopener">Marine Conservation Society Good Fish Guide</a> helps you choose sustainably sourced options. Compare fish nutrition data with our <a href="' . $mp . '">meat and poultry</a> category or browse <a href="' . $hub . '">all food categories</a> in the database.</p>';
				break;

			case 'dairy-eggs':
				echo '<h2>Dairy and Eggs as Nutritional Staples</h2>';
				echo '<p>Dairy products and eggs have been central to the human diet for thousands of years and remain nutritionally important in the modern UK diet. The dairy group includes milk, cheese, yoghurt, butter, and cream, each with a distinct nutritional profile. Eggs are one of the most nutrient-dense foods available, providing high-quality complete protein, healthy fats, and a wide range of vitamins and minerals. Together, this category is one of the most efficient ways to obtain calcium, protein, vitamin B12, vitamin D, and iodine from whole foods. The <a href="https://www.nhs.uk/live-well/eat-well/food-guidelines-and-food-labels/the-eatwell-guide/" target="_blank" rel="noopener">NHS Eat Well Guide</a> recommends including some dairy or dairy alternatives as part of a balanced daily diet.</p>';
				echo '<h2>Calcium, Protein, and Bone Health</h2>';
				echo '<p>Dairy products are the richest dietary source of calcium in the UK diet. A 200ml glass of semi-skimmed milk provides around 240mg of calcium, roughly a third of the adult daily requirement of 700mg. Adequate calcium intake throughout life, combined with weight-bearing physical activity and sufficient vitamin D, is essential for building and maintaining bone density and reducing the risk of osteoporosis. The <a href="https://www.nutrition.org.uk/healthy-sustainable-diets/dairy/" target="_blank" rel="noopener">British Nutrition Foundation</a> recommends three portions of dairy per day for most adults. A single large egg provides around 6g of complete protein alongside vitamins A, D, E, and B12, plus choline, a nutrient important for brain health that is often under-consumed in UK diets.</p>';
				echo '<h2>Choosing Healthier Options</h2>';
				echo '<p>Full-fat dairy products can be high in saturated fat, which the NHS advises limiting to reduce cardiovascular disease risk. Choosing reduced-fat versions of milk, yoghurt, and cheese helps manage saturated fat intake without sacrificing calcium or protein. Plain yoghurt is a lower-sugar option compared with flavoured varieties that often contain significant added sugar. For those avoiding dairy, fortified plant-based milks such as oat, soy, or almond milk can provide comparable levels of calcium and vitamin D when selected carefully. Browse our <a href="' . $fv . '">fruit and vegetables</a> category for plant-based calcium sources such as leafy greens, or explore the full <a href="' . $hub . '">calorie database</a>.</p>';
				break;

			case 'bread-cereals':
				echo '<h2>Carbohydrates as Your Primary Energy Source</h2>';
				echo '<p>Bread, rice, pasta, oats, and other cereals provide the body\'s primary source of carbohydrate energy and form the foundation of most UK diets. The <a href="https://www.nhs.uk/live-well/eat-well/food-guidelines-and-food-labels/the-eatwell-guide/" target="_blank" rel="noopener">UK Eat Well Guide</a> recommends that starchy carbohydrates make up just over a third of what you eat each day. Carbohydrates are broken down into glucose, which fuels the brain, muscles, and all other cells. Energy density across this category varies considerably: a slice of white bread provides around 240 kcal per 100g, while dry oats provide around 370 kcal per 100g. Portion size matters as much as food choice, since all starchy carbohydrates provide broadly similar amounts of energy per gram.</p>';
				echo '<h2>Wholegrain vs Refined Grains</h2>';
				echo '<p>The key nutritional difference within this category is between wholegrain and refined products. Wholegrain foods retain the bran and germ of the grain, providing significantly more fibre, B vitamins, iron, and magnesium than their refined counterparts. The <a href="https://www.nhs.uk/live-well/eat-well/food-types/starchy-foods-and-carbohydrates/" target="_blank" rel="noopener">NHS recommends</a> choosing wholegrain versions of bread, pasta, and rice wherever possible. Research from the <a href="https://www.wcrf.org/" target="_blank" rel="noopener">World Cancer Research Fund</a> has found consistent evidence that wholegrains reduce the risk of colorectal cancer. Oats are particularly valuable, containing beta-glucan, a soluble fibre that lowers LDL cholesterol when consumed regularly as part of a healthy diet.</p>';
				echo '<h2>Fibre, Blood Sugar, and Practical Guidance</h2>';
				echo '<p>Wholegrains have a lower glycaemic index than refined grains, meaning they raise blood sugar more slowly and provide more sustained energy throughout the day. This is particularly relevant for people managing type 2 diabetes or those looking to avoid energy dips. The UK government recommends 30g of dietary fibre per day for adults; a diet built around wholegrains alongside <a href="' . $fv . '">fruits and vegetables</a> and <a href="' . $lp . '">legumes and pulses</a> is the most practical way to meet this target consistently. When reading food labels, look for products where a wholegrain ingredient is listed first. Return to the <a href="' . $hub . '">main calorie database</a> to explore the full range of foods.</p>';
				break;

			case 'nuts-seeds':
				echo '<h2>Nutritional Value of Nuts and Seeds</h2>';
				echo '<p>Nuts and seeds are among the most nutritionally dense foods available, providing significant amounts of healthy fats, protein, fibre, vitamins, and minerals in small portions. Despite their high calorie content, typically 500 to 700 kcal per 100g, research consistently shows that regular nut consumption is associated with reduced cardiovascular disease risk, better weight management outcomes, and lower rates of type 2 diabetes. The <a href="https://www.bhf.org.uk/informationsupport/heart-matters-magazine/nutrition/nuts" target="_blank" rel="noopener">British Heart Foundation</a> confirms that nuts can be included as part of a heart-healthy diet, as the fats they contain are predominantly unsaturated.</p>';
				echo '<h2>Healthy Fats, Protein, and Minerals</h2>';
				echo '<p>The fat in most nuts and seeds is largely unsaturated. Walnuts are one of the few plant foods to provide significant amounts of ALA, a plant-based omega-3 fatty acid. Brazil nuts are exceptionally rich in selenium, with a single nut often meeting the entire daily requirement. Almonds and sesame seeds are good sources of calcium, useful for those who consume limited dairy. Pumpkin seeds provide substantial zinc and magnesium. Flaxseeds and chia seeds are high in both ALA omega-3 and dietary fibre, making them easy additions to porridge, smoothies, or yoghurt. Compare protein content with <a href="' . $lp . '">legumes and pulses</a> for other plant-based sources, or see <a href="' . $fo . '">fats and oils</a> for more on healthy fat choices.</p>';
				echo '<h2>Portion Awareness and Practical Use</h2>';
				echo '<p>Because nuts and seeds are calorie-dense, portion awareness matters for those managing their weight. A standard portion is around 30g, roughly a small handful, providing 150 to 200 kcal depending on the variety. Choosing unsalted, unroasted nuts avoids the added salt and oils found in many commercial products. Nut butters are a convenient way to include nuts in the diet, though many commercial versions contain added salt, sugar, or palm oil; look for products where nuts are the only ingredient. Sprinkling seeds over salads, soups, and breakfast dishes is a straightforward way to boost nutrient intake without significantly increasing meal calories. Browse the full <a href="' . $hub . '">food calorie database</a> for more nutrition comparisons.</p>';
				break;

			case 'fats-oils':
				echo '<h2>The Role of Fat in a Healthy Diet</h2>';
				echo '<p>Fat is an essential macronutrient providing 9 kcal per gram, making fats and oils the most calorie-dense of all food types. Despite decades of low-fat dietary advice, current evidence supports a more nuanced view: the type of fat consumed matters far more than the total amount. Fat is required for absorbing fat-soluble vitamins A, D, E, and K; for producing hormones; and for maintaining the structural integrity of every cell membrane in the body. The goal is not to eliminate fat from the diet but to shift the balance from saturated to unsaturated fats where possible. The <a href="https://www.nhs.uk/live-well/eat-well/food-types/fat-the-facts/" target="_blank" rel="noopener">NHS recommends</a> that fat provides no more than 35% of total daily energy intake.</p>';
				echo '<h2>Saturated vs Unsaturated Fats</h2>';
				echo '<p>Saturated fats, found predominantly in butter, lard, coconut oil, and palm oil, raise LDL cholesterol when consumed in excess, increasing cardiovascular risk. The NHS recommends that men consume no more than 30g of saturated fat per day and women no more than 20g. Unsaturated fats, found in olive oil, rapeseed oil, avocado oil, and most plant-based oils, have a neutral or beneficial effect on cholesterol. Extra-virgin olive oil, a cornerstone of the Mediterranean diet, has been consistently associated with reduced risk of heart disease and stroke in large-scale research. Trans fats, found in partially hydrogenated vegetable oils, are the most harmful type and have been largely removed from UK food manufacturing, though they may still appear in some imported products.</p>';
				echo '<h2>Calories in Cooking Oils and Practical Guidance</h2>';
				echo '<p>All cooking oils provide roughly 900 kcal per 100g, as they are almost entirely fat. Even healthier oils need to be used in measured amounts from a calorie perspective. Using an oil spray rather than pouring directly, or measuring portions with a spoon, can significantly reduce calorie intake from cooking fats without changing the flavour of your food. Butter and spreads vary considerably in calorie and fat content; reduced-fat spreads can contain as little as half the fat of full-fat butter. For baking, replacing butter with rapeseed or sunflower oil reduces saturated fat while maintaining moisture. Browse our <a href="' . $ns . '">nuts and seeds</a> category for more unsaturated fat sources, or return to the full <a href="' . $hub . '">calorie database</a>.</p>';
				break;

			case 'drinks':
				echo '<h2>Liquid Calories and Hydration</h2>';
				echo '<p>Drinks are one of the most overlooked sources of calories in the UK diet. Unlike solid food, liquid calories do not trigger the same satiety signals in the brain, meaning it is easy to consume a significant proportion of your daily energy intake from beverages without feeling full. The <a href="https://www.nhs.uk/live-well/eat-well/food-guidelines-and-food-labels/water-drinks-nutrition/" target="_blank" rel="noopener">NHS recommends</a> drinking 6 to 8 glasses of fluid per day, with water, lower-fat milk, and sugar-free drinks being the healthiest choices. Calorie content across this category ranges enormously: water contains zero calories, while some milkshakes and specialty coffee drinks can exceed 500 kcal per serving, comparable to a full meal.</p>';
				echo '<h2>Sugar in Soft Drinks and Juices</h2>';
				echo '<p>The UK introduced the <a href="https://www.gov.uk/guidance/soft-drinks-industry-levy" target="_blank" rel="noopener">Soft Drinks Industry Levy</a> in 2018 to encourage manufacturers to reduce sugar in commercial beverages, and many have reformulated their products accordingly. Despite this, many soft drinks, energy drinks, and fruit juices remain high in free sugars. The NHS recommends limiting free sugars to no more than 30g per day for adults; a single 330ml can of full-sugar cola contains around 35g. Fruit juice, though often perceived as healthy, is high in free sugars and low in fibre compared with whole fruit. The NHS advises limiting juice and smoothies to a maximum of 150ml per day, counting as one portion of your 5 A Day.</p>';
				echo '<h2>Making Healthier Drink Choices</h2>';
				echo '<p>Alcoholic drinks vary widely in calorie content: a pint of lager contains around 200 kcal and a large glass of wine around 180 kcal. Alcohol also lowers inhibitions around food choices and can stimulate appetite, contributing to higher overall calorie intake on days when it is consumed. Tea and coffee without added sugar are essentially calorie-free, but adding full-fat milk, syrups, or cream transforms them into high-calorie drinks. For those looking to reduce calorie intake from beverages, switching from full-sugar to sugar-free versions of familiar drinks is one of the easiest changes to make. Compare drink calories with our <a href="' . $sc . '">snacks and confectionery</a> section or browse the full <a href="' . $hub . '">calorie database</a>.</p>';
				break;

			case 'snacks-confectionery':
				echo '<h2>Snack Calories and How They Add Up</h2>';
				echo '<p>Snacks and confectionery are one of the largest sources of discretionary calories in the UK diet. National Diet and Nutrition Survey data consistently shows that adults consume 400 to 500 kcal per day from biscuits, cakes, chocolate, crisps, and confectionery. While snacking itself is not inherently problematic, the calorie density of many commercial snack foods is high: a standard 45g bag of crisps contains around 230 kcal, a milk chocolate bar can exceed 500 kcal per 100g, and a supermarket muffin can approach 400 kcal. Understanding these figures allows you to make deliberate choices rather than mindless ones. The <a href="https://www.nhs.uk/change4life/food-facts/sugar" target="_blank" rel="noopener">NHS Change4Life programme</a> provides practical tools for reducing snack calories without feeling deprived.</p>';
				echo '<h2>Sugar, Salt, and Saturated Fat in Snacks</h2>';
				echo '<p>Most commercial snacks and confectionery score red on at least one <a href="https://www.food.gov.uk/safety-hygiene/food-labels" target="_blank" rel="noopener">FSA traffic-light label</a>, typically for sugar, salt, or saturated fat. Chocolate and sweets tend to score red for sugar; crisps and savoury snacks for salt; biscuits and pastries for saturated fat. Regularly consuming foods with multiple red ratings increases the risk of tooth decay, high blood pressure, weight gain, and cardiovascular disease. Reading traffic-light labels before purchasing and choosing amber-rated alternatives where available is a practical first step toward a healthier snacking pattern.</p>';
				echo '<h2>Smarter Snacking Strategies</h2>';
				echo '<p>Replacing energy-dense processed snacks with whole foods is the most effective strategy for reducing snack calories without increasing hunger. A 30g portion of <a href="' . $ns . '">nuts and seeds</a> provides around 150 to 180 kcal alongside protein, healthy fats, and minerals. Fresh <a href="' . $fv . '">fruit</a> satisfies a sweet craving while providing fibre and micronutrients. Plain yoghurt with berries offers more protein than most commercial snacks, helping to manage appetite between meals. Oatcakes paired with nut butter or hummus are lower in calories than most biscuits and provide more sustained energy. If you choose confectionery, treating it as a planned, portioned item rather than an unthinking habit makes it far easier to stay within your overall calorie goals. Return to the <a href="' . $hub . '">main calorie database</a> to look up any snack before eating it.</p>';
				break;

			case 'takeaway':
				echo '<h2>Calories in Takeaway and Ready Meals</h2>';
				echo '<p>Takeaway meals and ready meals are convenient but often significantly higher in calories, salt, and saturated fat than equivalent home-cooked dishes. Popular takeaway meals can range from around 600 kcal for a portion of fish and chips to over 1,200 kcal for a pizza combination, sometimes approaching an adult\'s entire daily calorie requirement in a single meal. The main factor is portion size; takeaway portions are typically 30 to 50% larger than home-cooked equivalents, and cooking methods such as deep-frying add substantial calories from absorbed oil. The <a href="https://www.nutrition.org.uk/" target="_blank" rel="noopener">British Nutrition Foundation</a> encourages greater awareness of calorie content when eating out or choosing convenience foods.</p>';
				echo '<h2>Salt, Fat, and Hidden Ingredients</h2>';
				echo '<p>Salt is a particular concern in takeaway and ready meal consumption. Research from the <a href="https://www.food.gov.uk/" target="_blank" rel="noopener">Food Standards Agency</a> has found that some takeaway meals contain more than the entire recommended daily maximum of 6g of salt in a single dish. High salt intake raises blood pressure, the leading risk factor for stroke and cardiovascular disease. Saturated fat is also significantly elevated in many takeaway dishes due to the use of oils, butter, cream, and cheese in large quantities. Ready meals vary considerably in their nutritional profile; some supermarket ranges are carefully calorie-controlled, while others are comparable in nutrient density to restaurant takeaways. Checking the traffic-light label on packaging remains the most reliable way to assess a ready meal at a glance.</p>';
				echo '<h2>Making Healthier Choices When Eating Out</h2>';
				echo '<p>Reducing takeaway consumption does not mean giving it up entirely. Choosing grilled over fried dishes, selecting rice rather than chips as a side, requesting sauces on the side, and avoiding supersized portions are practical strategies that reduce calorie and salt intake significantly. For Indian and Chinese takeaways, dishes described as grilled, steamed, or stir-fried tend to be lower in calories than those described as battered, crispy, or creamy. When buying ready meals, look for options with green or amber ratings across all four traffic-light categories. Compare your choices with home-cooked alternatives from our <a href="' . $mp . '">meat and poultry</a>, <a href="' . $fv . '">fruit and vegetables</a>, or <a href="' . $bc . '">bread and cereals</a> sections, or browse the full <a href="' . $hub . '">calorie database</a>.</p>';
				break;

			case 'legumes-pulses':
				echo '<h2>Plant Protein and Why Legumes Matter</h2>';
				echo '<p>Legumes and pulses, including lentils, chickpeas, kidney beans, black beans, split peas, and edamame, are one of the most nutritionally and environmentally valuable food groups available. They are the primary source of plant protein in many of the world\'s healthiest dietary patterns, including the Mediterranean and traditional South Asian diets. A 100g cooked portion of red lentils provides around 9g of protein and 8g of fibre for just 116 kcal. Protein from legumes lacks sufficient quantities of one or more essential amino acids on its own, but combining pulses with grains such as rice or bread over the course of a day provides a full complement of essential amino acids. The <a href="https://www.nutrition.org.uk/healthy-sustainable-diets/plant-based-diets/" target="_blank" rel="noopener">British Nutrition Foundation</a> actively encourages greater legume consumption as part of a shift toward more sustainable, plant-based eating.</p>';
				echo '<h2>Fibre, Blood Sugar, and Gut Health</h2>';
				echo '<p>Legumes are exceptionally high in both soluble and insoluble dietary fibre. Soluble fibre slows the absorption of glucose, helping to moderate blood sugar levels after meals and making pulses particularly valuable for people managing or at risk of type 2 diabetes. Clinical trials have shown that replacing higher glycaemic index foods with legumes significantly improves blood sugar control. Insoluble fibre promotes regular bowel movements and feeds beneficial gut bacteria, supporting a healthy microbiome. <a href="https://www.diabetes.org.uk/guide-to-diabetes/enjoy-food/eating-with-diabetes/whats-good-to-eat/beans-and-pulses-in-your-diet" target="_blank" rel="noopener">Diabetes UK</a> recommends including pulses in meals at least three times per week as part of a blood-sugar-friendly diet.</p>';
				echo '<h2>Incorporating Pulses Into Your Diet</h2>';
				echo '<p>Adding more legumes to your diet is straightforward and affordable. Canned beans and lentils require no soaking and are ready in minutes, making them one of the most convenient sources of plant protein available. Stir chickpeas into a curry, add lentils to a soup, or swap half the meat in a bolognese for red lentils to reduce calories and saturated fat while increasing fibre. Hummus, made from chickpeas and tahini, makes an excellent dip or sandwich spread. For those new to pulses, starting with smaller portions helps the gut adapt gradually. Pair legumes with <a href="' . $fv . '">fruits and vegetables</a> for a complete plant-based meal, or compare protein content with <a href="' . $mp . '">meat and poultry</a> in the full <a href="' . $hub . '">calorie database</a>.</p>';
				break;

			case 'condiments-sauces':
			case 'condiments':
				echo '<h2>How Condiments Contribute to Calorie Intake</h2>';
				echo '<p>Condiments and sauces are rarely eaten in large quantities, but their calorie contribution can be surprisingly significant when added to multiple meals throughout the day. Mayonnaise contains around 680 kcal per 100g, primarily from fat. A generous tablespoon (approximately 15g) adds around 100 kcal to a sandwich before any filling is considered. Salad dressings, peanut sauces, and cream-based sauces are similarly calorie-dense. By contrast, tomato-based sauces, mustard, vinegar, and hot sauces are very low in calories and can add significant flavour without meaningfully increasing energy intake. Tracking condiments alongside your main meals gives a more accurate picture of total daily calorie intake, particularly when the rest of the diet is well managed.</p>';
				echo '<h2>Salt and Sugar in Sauces and Dressings</h2>';
				echo '<p>Beyond calories, condiments are a notable source of salt and sugar in the UK diet. Soy sauce is among the saltiest foods in common use, with some varieties containing over 6g of salt per 100ml, equivalent to the entire recommended daily maximum in a small quantity. Ketchup and sweet chilli sauce can contain 20g or more of sugar per 100g. The <a href="https://www.food.gov.uk/safety-hygiene/food-labels" target="_blank" rel="noopener">FSA traffic-light system</a> is particularly useful for condiments because they are served in small portions but consumed frequently. Products rated red for salt or sugar should be used sparingly; reduced-salt and reduced-sugar alternatives are widely available in most supermarkets.</p>';
				echo '<h2>Using Condiments Wisely</h2>';
				echo '<p>Rather than eliminating condiments entirely, the goal is to use them deliberately and choose lower-calorie options where flavour allows. Swapping full-fat mayonnaise for a light version reduces calorie content by roughly 50 to 60%. Using mustard, salsa, or fresh herbs as alternatives to cream-based dressings transforms the nutritional profile of a salad without sacrificing taste. Lemon juice, balsamic vinegar, and low-sodium soy sauce are versatile, low-calorie flavourings that replace heavier sauces in many recipes. Olive oil is a healthier fat choice than many processed dressings when used in small, measured amounts. Always check traffic-light labels and be mindful of serving sizes, as nutrition information on condiments is typically given per 100g rather than per portion. Explore the full <a href="' . $hub . '">calorie database</a> or browse related categories such as <a href="' . $fo . '">fats and oils</a> and <a href="' . $sc . '">snacks and confectionery</a>.</p>';
				break;
		}

		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// H1 Variation Helpers
	// -------------------------------------------------------------------------

	private function get_category_h1( array $cat ): string {
		$map = [
			'fruit-veg'            => 'Fruit &amp; Vegetables: Calories, Vitamins &amp; Nutrition Guide',
			'meat-poultry'         => 'Meat &amp; Poultry: Calories, Protein &amp; Nutrition Facts',
			'fish-seafood'         => 'Fish &amp; Seafood: Calories, Omega-3 &amp; Nutritional Values',
			'dairy-eggs'           => 'Dairy &amp; Eggs: Calcium, Protein &amp; Calorie Guide',
			'bread-cereals'        => 'Bread &amp; Cereals: Calories, Carbs &amp; Fibre Content',
			'nuts-seeds'           => 'Nuts &amp; Seeds: Calories, Healthy Fats &amp; Nutrition Data',
			'fats-oils'            => 'Fats &amp; Oils: Calorie Counts &amp; Nutritional Breakdown',
			'drinks'               => 'Drinks: Calories, Sugar &amp; Nutritional Values',
			'snacks-confectionery' => 'Snacks &amp; Confectionery: Calories, Sugar &amp; Nutrition Guide',
			'takeaway'             => 'Takeaway &amp; Ready Meals: Calories &amp; Nutrition Data',
			'legumes-pulses'       => 'Legumes &amp; Pulses: Protein, Fibre &amp; Calorie Count',
			'condiments-sauces'    => 'Condiments &amp; Sauces: Calories, Salt &amp; Nutrition Data',
			'condiments'           => 'Condiments &amp; Sauces: Calories, Salt &amp; Nutrition Data',
		];
		return $map[ $cat['slug'] ] ?? esc_html( $cat['name'] ) . ' Calories &amp; Nutrition Facts';
	}

	private function get_food_h1( array $food ): string {
		$n = esc_html( $food['name'] );
		$pool = [
			$n . ' Calories &amp; Nutrition Facts',
			'How Many Calories in ' . $n . '?',
			$n . ': Calorie Count &amp; Nutritional Breakdown',
			'Calories in ' . $n . ': Protein, Carbs &amp; Fat',
			$n . ' Nutrition Facts per 100g',
			$n . ': Full Macro &amp; Calorie Guide',
			'Calorie &amp; Nutrition Information for ' . $n,
			$n . ': Nutritional Values &amp; Calorie Data',
			$n . ' Calories, Macros &amp; Key Nutrients',
			$n . ': Calories, Protein &amp; Nutrition Guide',
		];
		return $pool[ (int) $food['id'] % count( $pool ) ];
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
			echo '<h1 class="fcc-food-page__title">' . $this->get_food_h1( $food ) . '</h1>';
			echo '<div class="fcc-food-page__content">' . wp_kses_post( $food['page_content'] ) . '</div>';
			return;
		}

		// Auto-generated content.
		echo '<h1 class="fcc-food-page__title">' . $this->get_food_h1( $food ) . '</h1>';

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
			$paragraphs[] = 'It is ' . ( $prot_raw >= 25 ? 'an excellent' : 'a good' ) . ' source of protein, providing ' . $prot . 'g per 100g, approximately ' . $rni_pct . '% of the UK Reference Nutrient Intake of 50g per day. Protein plays a key role in muscle repair, immune function, and keeping you feeling full for longer.';
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
			$o3          = number_format( $food['omega3_total_mg'], 0 );
			$fish_cat    = Database::get_category_by_slug( 'fish-seafood' );
			$oily_fish   = $fish_cat ? Database::get_top_foods_by_nutrient( 'omega3_total_mg', (int) $fish_cat['id'], (int) $food['id'], 2 ) : [];
			$fish_clause = '';
			if ( ! empty( $oily_fish ) ) {
				$fish_links = [];
				foreach ( $oily_fish as $of ) {
					$fish_links[] = '<a href="' . esc_url( home_url( '/calories/fish-seafood/' . $of['slug'] . '/' ) ) . '">' . esc_html( $of['name'] ) . '</a>';
				}
				$fish_clause = ' (including ' . implode( ' and ', $fish_links ) . ')';
			}
			$paragraphs[] = 'It provides ' . $o3 . 'mg of omega-3 fatty acids per 100g. The British Nutrition Foundation recommends eating at least one portion of oily fish' . $fish_clause . ' per week for heart health. Omega-3s, particularly EPA and DHA, are linked to reduced inflammation, improved cardiovascular function, and cognitive benefits.';
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
			$paragraphs[] = 'This is an energy-dense food at ' . $kcal . ' kcal per 100g' . ( $fat_raw >= 17.5 ? ', largely due to its fat content (' . $fat . 'g per 100g). Fat provides 9 calories per gram, more than double that of protein or carbohydrates' : '' ) . '. While it can be part of a balanced diet, portion control is recommended to avoid exceeding daily calorie needs.';
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
			// Fetch 1 top-protein food from each high-protein category for natural food-to-food links.
			$mp_cat  = Database::get_category_by_slug( 'meat-poultry' );
			$fs_cat  = Database::get_category_by_slug( 'fish-seafood' );
			$lp_cat  = Database::get_category_by_slug( 'legumes-pulses' );
			$mp_food = $mp_cat ? Database::get_top_foods_by_nutrient( 'protein_g', (int) $mp_cat['id'], (int) $food['id'], 1 ) : [];
			$fs_food = $fs_cat ? Database::get_top_foods_by_nutrient( 'protein_g', (int) $fs_cat['id'], (int) $food['id'], 1 ) : [];
			$lp_food = $lp_cat ? Database::get_top_foods_by_nutrient( 'protein_g', (int) $lp_cat['id'], (int) $food['id'], 1 ) : [];

			$examples = [];
			if ( ! empty( $mp_food[0] ) ) {
				$examples[] = '<a href="' . esc_url( home_url( '/calories/meat-poultry/' . $mp_food[0]['slug'] . '/' ) ) . '">' . esc_html( $mp_food[0]['name'] ) . '</a>';
			}
			if ( ! empty( $fs_food[0] ) ) {
				$examples[] = '<a href="' . esc_url( home_url( '/calories/fish-seafood/' . $fs_food[0]['slug'] . '/' ) ) . '">' . esc_html( $fs_food[0]['name'] ) . '</a>';
			}
			if ( ! empty( $lp_food[0] ) ) {
				$examples[] = '<a href="' . esc_url( home_url( '/calories/legumes-pulses/' . $lp_food[0]['slug'] . '/' ) ) . '">' . esc_html( $lp_food[0]['name'] ) . '</a>';
			}

			$lp_a_html = ! empty( $examples )
				? esc_html( $name ) . ' contains ' . $prot . 'g of protein per 100g, which is relatively low. To increase your protein intake, consider pairing it with ' . implode( ', ', $examples ) . ', or other protein-rich foods.'
				: esc_html( $name ) . ' contains ' . $prot . 'g of protein per 100g, which is relatively low. To increase your protein intake, consider pairing it with foods from our <a href="' . esc_url( home_url( '/calories/meat-poultry/' ) ) . '">meat and poultry</a>, <a href="' . esc_url( home_url( '/calories/fish-seafood/' ) ) . '">fish and seafood</a>, or <a href="' . esc_url( home_url( '/calories/legumes-pulses/' ) ) . '">legumes and pulses</a> sections.';

			$faqs[] = [
				'q'      => "Is {$name} a good source of protein?",
				'a'      => "{$name} contains {$prot}g of protein per 100g, which is relatively low. If you are looking to increase your protein intake, consider pairing it with protein-rich foods such as lean meat, fish, eggs, or legumes.",
				'a_html' => $lp_a_html,
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
				'a' => "{$name} does not contain any of the 14 major allergens recognised under UK food law. However, cross-contamination during manufacturing is always possible; always check the packaging for 'may contain' warnings.",
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
			if ( ! empty( $faq['a_html'] ) ) {
				echo '<p itemprop="text">' . wp_kses_post( $faq['a_html'] ) . '</p>';
			} else {
				echo '<p itemprop="text">' . esc_html( $faq['a'] ) . '</p>';
			}
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

		// Core four — always shown on every food page.
		echo '<li><a href="https://www.nutrition.org.uk/" target="_blank" rel="noopener">British Nutrition Foundation</a> — UK nutrition science and education</li>';
		echo '<li><a href="https://www.gov.uk/government/publications/composition-of-foods-integrated-dataset-cofid" target="_blank" rel="noopener">McCance &amp; Widdowson\'s Composition of Foods</a> — UK food composition tables</li>';

		// Auto-link to USDA FDC if source_notes contains an FDC ID.
		$fdc_link = '';
		if ( ! empty( $food['source_notes'] ) && preg_match( '/FDC\s*#?\s*(\d+)/i', $food['source_notes'], $m ) ) {
			$fdc_link = ' — <a href="https://fdc.nal.usda.gov/fdc-app.html#/food-details/' . esc_attr( $m[1] ) . '/nutrients" target="_blank" rel="noopener">View ' . esc_html( $food['name'] ) . ' data</a>';
		}
		echo '<li><a href="https://fdc.nal.usda.gov/" target="_blank" rel="noopener">USDA FoodData Central</a> — US Department of Agriculture food composition database' . $fdc_link . '</li>';

		echo '<li><a href="https://www.nhs.uk/live-well/eat-well/" target="_blank" rel="noopener">NHS Eat Well</a> — UK National Health Service dietary guidance</li>';

		// Extra sources — pool of 7; 1, 2, or 3 added per food (totalling 5, 6, or 7).
		// Selection is deterministic: starting index and count both vary by food ID,
		// gcd(pool_size=7, step=3)=1 so no duplicates across any 3-pick window.
		$extra_pool = [
			'<a href="https://www.food.gov.uk/safety-hygiene/food-labelling" target="_blank" rel="noopener">Food Standards Agency (FSA)</a> — UK food labelling regulations and traffic-light nutrition guidelines',
			'<a href="https://www.gov.uk/government/groups/scientific-advisory-committee-on-nutrition" target="_blank" rel="noopener">Scientific Advisory Committee on Nutrition (SACN)</a> — UK government dietary recommendations and nutrient reference values',
			'<a href="https://www.efsa.europa.eu/en/topics/topic/dietary-reference-values" target="_blank" rel="noopener">European Food Safety Authority (EFSA)</a> — European dietary reference values and food safety science',
			'<a href="https://www.bda.uk.com/resource/food-facts-home.html" target="_blank" rel="noopener">British Dietetic Association (BDA)</a> — UK registered dietitians and evidence-based food fact sheets',
			'<a href="https://www.who.int/health-topics/nutrition" target="_blank" rel="noopener">World Health Organization (WHO) Nutrition</a> — global dietary guidelines and public health nutrition',
			'<a href="https://nutritionsource.hsph.harvard.edu/" target="_blank" rel="noopener">Harvard T.H. Chan School of Public Health — The Nutrition Source</a> — evidence-based nutrition research and dietary guidance',
			'<a href="https://www.bhf.org.uk/informationsupport/heart-matters-magazine/nutrition" target="_blank" rel="noopener">British Heart Foundation</a> — heart-healthy eating advice and dietary guidance',
		];
		$extra_count = ( (int) $food['id'] % 3 ) + 1;
		$pool_size   = count( $extra_pool );
		for ( $i = 0; $i < $extra_count; $i++ ) {
			$idx = ( (int) $food['id'] + $i * 3 ) % $pool_size;
			echo '<li>' . $extra_pool[ $idx ] . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

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
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $r['name'] ) . '</a>: '
				. number_format( (float) $r['energy_kcal'], 0 ) . ' kcal</li>';
		}
		echo '</ul>';
		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// SEO Meta Tags
	// -------------------------------------------------------------------------

	private function generate_food_meta_desc( array $food ): string {
		$name     = $food['name'];
		$kcal_raw = (float) $food['energy_kcal'];
		$prot_raw = (float) $food['protein_g'];
		$carb_raw = (float) $food['carbohydrate_g'];
		$fat_raw  = (float) $food['fat_g'];
		$id       = (int) ( $food['id'] ?? 1 );
		$cat_id   = (int) ( $food['category_id'] ?? 1 );

		$kcal = (int) round( $kcal_raw );
		$prot = number_format( $prot_raw, 1 );
		$carb = number_format( $carb_raw, 1 );
		$fat  = number_format( $fat_raw,  1 );

		// Pool A — 8 action verbs (max 10 chars each).
		$verbs = [
			'Find out', 'Discover', 'See', 'Check',
			'Learn', 'Explore', 'Compare', 'Understand',
		];
		// Pool B — 15 short subject phrases (max 19 chars each).
		$subjects = [
			'full UK food data',
			'its macro profile',
			'all calorie details',
			'fibre and salt data',
			'all nutrient data',
			'full nutrition data',
			'all macro details',
			'FSA traffic data',
			'its allergen info',
			'its macro split',
			'its calorie data',
			'daily calorie info',
			'its vitamin data',
			'full allergen data',
			'all food nutrients',
		];
		// Pool C — 6 short closing tails (max 20 chars each).
		$tails = [
			' on our free tool.',
			' in our UK database.',
			' on our UK site.',
			' free on our tool.',
			' on our UK tool.',
			' at our UK database.',
		];

		// 8 × 15 × 6 = 720 unique endings; × 8 body profiles = 5,760 combinations.
		$verb    = $verbs[    $id % count( $verbs )                              ];
		$subject = $subjects[ ( $id * 3  + $cat_id     ) % count( $subjects )   ];
		$tail    = $tails[    ( $id * 7  + $cat_id * 3 ) % count( $tails )      ];
		$ending  = $verb . ' ' . $subject . $tail;

		// Profile-based body chosen by nutritional characteristics.
		if ( $prot_raw >= 20 && $carb_raw <= 5 ) {
			$body = "{$name} is high in protein ({$prot}g) with just {$carb}g carbs at {$kcal} kcal/100g.";
		} elseif ( $prot_raw >= 15 ) {
			$body = "{$name} delivers {$prot}g of protein per 100g at {$kcal} kcal. Carbs {$carb}g, Fat {$fat}g.";
		} elseif ( $kcal_raw <= 50 ) {
			$body = "{$name} has just {$kcal} kcal per 100g. Protein {$prot}g, Carbs {$carb}g, Fat {$fat}g.";
		} elseif ( $kcal_raw <= 80 ) {
			$body = "{$name} is light at {$kcal} kcal per 100g. Protein {$prot}g, Carbs {$carb}g, Fat {$fat}g.";
		} elseif ( $carb_raw <= 3 ) {
			$body = "{$name} has {$kcal} kcal per 100g with just {$carb}g of carbs. Protein {$prot}g, Fat {$fat}g.";
		} elseif ( $kcal_raw >= 400 ) {
			$body = "{$name} is energy-dense at {$kcal} kcal per 100g. Protein {$prot}g, Carbs {$carb}g, Fat {$fat}g.";
		} elseif ( $fat_raw >= 20 ) {
			$body = "{$name} contains {$fat}g of fat per 100g at {$kcal} kcal. Protein {$prot}g, Carbs {$carb}g.";
		} else {
			$body = "Calories in {$name}: {$kcal} kcal per 100g. Protein {$prot}g, Carbs {$carb}g, Fat {$fat}g.";
		}

		$desc = $body . ' ' . $ending;

		// Safety: clip at word boundary without ellipsis for very long food names.
		if ( mb_strlen( $desc ) > 160 ) {
			$trimmed = mb_substr( $desc, 0, 160 );
			$last    = mb_strrpos( $trimmed, ' ' );
			$desc    = $last > 100 ? mb_substr( $trimmed, 0, $last ) : $trimmed;
		}

		return $desc;
	}

	public function output_seo_meta(): void {
		// Directory page.
		if ( 'directory' === self::$page_type ) {
			echo '<meta name="description" content="Browse calories and nutrition facts for over 4,900 foods. Calories, protein, fat, carbs, vitamins, and minerals. Free UK food calorie calculator.">' . "\n";
			echo '<link rel="canonical" href="' . esc_url( home_url( '/calories/' ) ) . '">' . "\n";
			return;
		}

		// Category page.
		if ( 'category' === self::$page_type && self::$current_category ) {
			$cn = esc_attr( self::$current_category['name'] );
			echo '<meta name="description" content="' . esc_attr( "{$cn}: browse calories, protein, fat, and full nutrition facts for all foods in this category. Free UK food calorie calculator." ) . '">' . "\n";
			echo '<link rel="canonical" href="' . esc_url( home_url( '/calories/' . self::$current_category['slug'] . '/' ) ) . '">' . "\n";
			return;
		}

		$food = self::$current_food;
		if ( ! $food ) { return; }

		$url  = self::food_url( $food );

		echo '<meta name="description" content="' . esc_attr( $this->generate_food_meta_desc( $food ) ) . '">' . "\n";
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

	public function override_title_pre( string $title ): string {
		if ( 'directory' === self::$page_type ) {
			return 'Browse UK Food Calories: 4,900+ Foods Free';
		}
		if ( 'category' === self::$page_type && self::$current_category ) {
			$cn     = self::$current_category['name'];
			$cat_id = (int) ( self::$current_category['id'] ?? 1 );
			$opts   = array_values( array_filter( [
				"{$cn}: Full UK Calorie and Nutrition Guide",
				"UK Calorie and Nutrition Facts for {$cn} Foods",
				"Browse {$cn} Calories and Nutrition Facts",
				"{$cn} Foods: UK Calorie and Nutrition Data",
				"{$cn}: Calories and Nutrition Facts",
				"Calories in {$cn} Foods",
				"{$cn} Food Calories and Macros",
				"UK {$cn} Calorie Guide",
			], fn( $s ) => mb_strlen( $s ) >= 40 && mb_strlen( $s ) <= 60 ) );
			if ( ! $opts ) {
				$under60 = array_values( array_filter( [ "Calories in {$cn} Foods", "{$cn}: UK Calorie Data" ], fn( $s ) => mb_strlen( $s ) <= 60 ) );
				usort( $under60, fn( $a, $b ) => mb_strlen( $b ) - mb_strlen( $a ) );
				return $under60 ? $under60[0] : mb_substr( $cn, 0, 60 );
			}
			return $opts[ $cat_id % count( $opts ) ];
		}
		if ( self::$current_food ) {
			return $this->generate_food_title( self::$current_food );
		}
		return $title;
	}

	public function filter_title( array $title ): array {
		if ( 'directory' === self::$page_type ) {
			$title['title'] = 'Browse UK Food Calories: 4,900+ Foods Free';
			$title['site']  = '';
		} elseif ( 'category' === self::$page_type && self::$current_category ) {
			$cn     = self::$current_category['name'];
			$cat_id = (int) ( self::$current_category['id'] ?? 1 );
			$opts   = array_values( array_filter( [
				"{$cn}: Full UK Calorie and Nutrition Guide",
				"UK Calorie and Nutrition Facts for {$cn} Foods",
				"Browse {$cn} Calories and Nutrition Facts",
				"{$cn} Foods: UK Calorie and Nutrition Data",
				"{$cn}: Calories and Nutrition Facts",
				"Calories in {$cn} Foods",
				"{$cn} Food Calories and Macros",
				"UK {$cn} Calorie Guide",
			], fn( $s ) => mb_strlen( $s ) >= 40 && mb_strlen( $s ) <= 60 ) );
			if ( ! $opts ) {
				$under60 = array_values( array_filter( [ "Calories in {$cn} Foods", "{$cn}: UK Calorie Data" ], fn( $s ) => mb_strlen( $s ) <= 60 ) );
				usort( $under60, fn( $a, $b ) => mb_strlen( $b ) - mb_strlen( $a ) );
				$opts = $under60 ?: [ mb_substr( $cn, 0, 60 ) ];
			}
			$title['title'] = $opts[ $cat_id % count( $opts ) ];
			$title['site']  = '';
		} elseif ( self::$current_food ) {
			$title['title'] = $this->generate_food_title( self::$current_food );
			$title['site']  = '';
		}
		return $title;
	}

	private function generate_food_title( array $food ): string {
		$name = $food['name'];
		$kcal = (int) round( (float) $food['energy_kcal'] );
		$id   = (int) ( $food['id'] ?? 1 );

		// All templates include "per 100g". Longer fixed-text templates bring short
		// food names above 40 chars; shorter ones are filtered out for long names.
		$templates = [
			"Calorie and Nutrition Facts for {$name} per 100g",
			"{$name}: UK Calories, Protein, Carbs and Fat per 100g",
			"{$name}: Full Calorie and Nutrition Facts per 100g",
			"Calories in {$name}: {$kcal} kcal per 100g",
			"{$name} Nutrition Facts and Calories per 100g",
			"{$name}: Calories, Protein and Fat per 100g",
			"{$name} - UK Calorie and Nutrition per 100g",
			"{$name} Calories and Nutrition per 100g",
			"How Many Calories in {$name} per 100g?",
			"{$name}: {$kcal} kcal per 100g",
			"Full Nutrition: {$name} per 100g",
		];

		// Target the 40-60 char sweet spot.
		$fitting = array_values(
			array_filter( $templates, fn( $s ) => mb_strlen( $s ) >= 40 && mb_strlen( $s ) <= 60 )
		);

		// Fallback: use longest template under 60 if nothing reaches 40.
		if ( empty( $fitting ) ) {
			$under60 = array_values( array_filter( $templates, fn( $s ) => mb_strlen( $s ) <= 60 ) );
			if ( $under60 ) {
				usort( $under60, fn( $a, $b ) => mb_strlen( $b ) - mb_strlen( $a ) );
				return $under60[0];
			}
			return mb_substr( $name, 0, 60 );
		}

		return $fitting[ $id % count( $fitting ) ];
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
