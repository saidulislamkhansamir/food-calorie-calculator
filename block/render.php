<?php
/**
 * Server-side render callback for the Food Calorie Calculator block.
 *
 * $attributes and $content are available via block.json render template.
 *
 * @package FoodCalorieCalculator
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue frontend assets (idempotent — class tracks whether already done).
\FCC\Shortcode::enqueue_public_assets();

// Render the calculator HTML.
$shortcode = new \FCC\Shortcode();
echo $shortcode->render( $attributes ?? [] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() returns pre-escaped HTML
