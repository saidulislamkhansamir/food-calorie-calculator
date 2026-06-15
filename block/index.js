/**
 * Food Calorie Calculator — Gutenberg Block Editor Script
 *
 * Registers the block in the editor. The frontend is rendered server-side
 * via render.php (render_callback), so this script only handles the editor
 * placeholder/preview shown while editing.
 */
( function () {
	'use strict';

	const { registerBlockType } = wp.blocks;
	const { useBlockProps }     = wp.blockEditor;
	const { Placeholder }       = wp.components;
	const { __ }                = wp.i18n;

	registerBlockType( 'food-calorie-calculator/calculator', {
		edit: function Edit( props ) {
			const blockProps = useBlockProps( {
				className: 'fcc-block-preview',
			} );

			return wp.element.createElement(
				'div',
				blockProps,
				wp.element.createElement(
					Placeholder,
					{
						icon:  'carrot',
						label: __( 'Food Calorie Calculator', 'food-calorie-calculator' ),
						instructions: __(
							'The Food Calorie Calculator will appear here on the frontend. Switch to Preview or visit the page to see it in action.',
							'food-calorie-calculator'
						),
					},
					wp.element.createElement(
						'p',
						{ style: { fontSize: '0.875rem', color: '#646970', margin: 0 } },
						__( 'Powered by foodcaloriecalculator.co.uk', 'food-calorie-calculator' )
					)
				)
			);
		},

		// No save — server renders via render.php.
		save: function () { return null; },
	} );
} )();
