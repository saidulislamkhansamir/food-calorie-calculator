<?php
/**
 * Admin: Settings page handler.
 *
 * Saves each tab's data via admin-post action. All fields sanitised.
 * Nonce + capability checked on every save.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Settings_Page {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'admin_post_fcc_save_settings', $this, 'handle_save_settings' );
	}

	public function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}

		check_admin_referer( 'fcc_save_settings' );

		$tab = isset( $_POST['fcc_tab'] ) ? sanitize_key( $_POST['fcc_tab'] ) : 'general';

		$all = \FCC\Settings::get_all();

		switch ( $tab ) {
			case 'general':
				$all['general'] = $this->sanitise_general( $_POST );
				break;
			case 'features':
				$all['features'] = $this->sanitise_features( $_POST );
				break;
			case 'appearance':
				$all['appearance'] = $this->sanitise_appearance( $_POST );
				break;
			case 'labels':
				$all['labels'] = $this->sanitise_labels( $_POST );
				break;
			case 'advanced':
				$all['advanced'] = $this->sanitise_advanced( $_POST );
				break;
		}

		\FCC\Settings::save( $all );

		wp_safe_redirect( add_query_arg(
			[
				'page'       => 'fcc-settings',
				'tab'        => $tab,
				'fcc_notice' => rawurlencode( __( 'Settings saved.', 'food-calorie-calculator' ) ),
				'fcc_ntype'  => 'success',
			],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Sanitisers.
	// -------------------------------------------------------------------------

	/** @param array<string,mixed> $post */
	private function sanitise_general( array $post ): array {
		$defaults = \FCC\Settings::defaults()['general'];

		$unit_options = [ 'metric', 'imperial' ];
		$unit         = in_array( $post['default_unit'] ?? '', $unit_options, true )
			? sanitize_key( $post['default_unit'] )
			: 'metric';

		// show_nutrients: submitted as a multi-select array of allowed keys.
		$allowed_nutrients = [
			'energy_kcal', 'energy_kj', 'protein_g', 'carbohydrate_g',
			'of_which_sugars_g', 'fat_g', 'of_which_saturates_g',
			'fibre_g', 'salt_g', 'omega3_total_mg', 'caffeine_mg',
		];
		$show = [];
		if ( ! empty( $post['show_nutrients'] ) && is_array( $post['show_nutrients'] ) ) {
			foreach ( (array) $post['show_nutrients'] as $nutrient ) {
				if ( in_array( $nutrient, $allowed_nutrients, true ) ) {
					$show[] = sanitize_key( $nutrient );
				}
			}
		}

		$ri_fields = [
			'ri_energy_kcal', 'ri_energy_kj', 'ri_fat_g', 'ri_saturates_g',
			'ri_carbohydrate_g', 'ri_sugars_g', 'ri_protein_g', 'ri_fibre_g', 'ri_salt_g',
		];
		$fsa_fields = [
			'fsa_fat_low', 'fsa_fat_high', 'fsa_saturates_low', 'fsa_saturates_high',
			'fsa_sugars_low', 'fsa_sugars_high', 'fsa_salt_low', 'fsa_salt_high',
		];

		$sanitised = [
			'default_unit'     => $unit,
			'default_category' => absint( $post['default_category'] ?? 0 ),
			'decimal_places'   => min( 3, max( 0, absint( $post['decimal_places'] ?? 1 ) ) ),
			'show_nutrients'   => $show ?: $defaults['show_nutrients'],
		];

		foreach ( array_merge( $ri_fields, $fsa_fields ) as $field ) {
			$sanitised[ $field ] = (float) ( $post[ $field ] ?? $defaults[ $field ] );
		}

		return $sanitised;
	}

	/** @param array<string,mixed> $post */
	private function sanitise_features( array $post ): array {
		$boolean_keys = [
			'bmr_tdee', 'daily_needs_comparison', 'fsa_traffic_lights',
			'ri_display', 'macro_chart', 'omega3_display', 'caffeine_display',
			'meal_builder', 'print_pdf', 'share_link', 'add_custom_food', 'json_ld_schema',
		];

		$sanitised = [];
		foreach ( $boolean_keys as $key ) {
			$sanitised[ $key ] = ! empty( $post[ $key ] );
		}
		return $sanitised;
	}

	/** @param array<string,mixed> $post */
	private function sanitise_appearance( array $post ): array {
		$sanitised = [
			'primary_colour'    => $this->sanitise_hex_colour( $post['primary_colour']    ?? '' ),
			'accent_colour'     => $this->sanitise_hex_colour( $post['accent_colour']     ?? '' ),
			'background_colour' => $this->sanitise_hex_colour( $post['background_colour'] ?? '' ),
			'dark_mode'         => ! empty( $post['dark_mode'] ),
			'button_radius'     => min( 50, max( 0, absint( $post['button_radius'] ?? 8 ) ) ),
			'font_family'       => sanitize_key( $post['font_family'] ?? 'system' ),
			// Allow limited CSS; strip script tags but permit valid CSS.
			'custom_css'        => self::sanitise_custom_css( wp_strip_all_tags( $post['custom_css'] ?? '' ) ),
		];
		return $sanitised;
	}

	/** @param array<string,mixed> $post */
	private function sanitise_labels( array $post ): array {
		$label_keys = [
			'calculator_title', 'search_placeholder', 'quantity_label', 'unit_label',
			'results_title', 'per_label', 'ri_label', 'ri_footnote', 'meal_title',
			'add_to_meal_label', 'bmr_title', 'omega3_title', 'caffeine_title',
			'no_data_label', 'traffic_light_label',
		];
		$sanitised = [];
		foreach ( $label_keys as $key ) {
			$sanitised[ $key ] = sanitize_text_field( $post[ $key ] ?? '' );
		}
		return $sanitised;
	}

	/** @param array<string,mixed> $post */
	private function sanitise_advanced( array $post ): array {
		return [
			'delete_data_on_uninstall' => ! empty( $post['delete_data_on_uninstall'] ),
			'cache_enabled'            => ! empty( $post['cache_enabled'] ),
		];
	}

	private function sanitise_hex_colour( string $colour ): string {
		$colour = sanitize_hex_color( $colour );
		return $colour ?: '#005EB8';
	}

	private static function sanitise_custom_css( string $css ): string {
		$css = preg_replace( '/javascript\s*:/i', '', $css );
		$css = preg_replace( '/expression\s*\(/i', '', $css );
		$css = preg_replace( '/@import\b/i', '', $css );
		return $css;
	}
}
