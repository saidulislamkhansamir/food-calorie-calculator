<?php
/**
 * Gutenberg block registration.
 *
 * The block renders the same output as [food_calorie_calculator] by
 * delegating to the shortcode's render method. This keeps a single source
 * of truth for the HTML and avoids duplicating the asset-enqueue logic.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class FCC_Block {

	public function register( Loader $loader ): void {
		$loader->add_action( 'init', $this, 'register_block' );
	}

	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return; // Pre-5.0 fallback — shouldn't happen given WP 6.0 minimum.
		}

		$block_json = FCC_PLUGIN_DIR . 'block/block.json';

		if ( ! file_exists( $block_json ) ) {
			return;
		}

		register_block_type(
			$block_json,
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Server-side render callback for the block.
	 *
	 * @param array<string,mixed> $attributes
	 */
	public function render( array $attributes ): string {
		// Trigger asset enqueue — same assets as the shortcode.
		Shortcode::enqueue_public_assets();

		// Re-use the shortcode renderer.
		$shortcode = new Shortcode();
		return $shortcode->render( $attributes );
	}
}
