/* global fccAdmin, wp */
/**
 * Food Calorie Calculator — Admin JavaScript
 * Requires: jquery, wp-color-picker
 */
( function ( $ ) {
	'use strict';

	// -------------------------------------------------------------------------
	// Colour pickers
	// -------------------------------------------------------------------------
	$( '.fcc-color-picker' ).wpColorPicker();

	// -------------------------------------------------------------------------
	// Delete confirmation
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.fcc-confirm-delete', function ( e ) {
		const msg = $( this ).data( 'confirm' ) || ( fccAdmin.i18n.confirmDelete );
		if ( ! window.confirm( msg ) ) {
			e.preventDefault();
		}
	} );

	// -------------------------------------------------------------------------
	// Bulk select-all checkbox
	// -------------------------------------------------------------------------
	$( '#fcc-select-all' ).on( 'change', function () {
		$( 'input[name="food_ids[]"]' ).prop( 'checked', this.checked );
	} );

	// Keep header checkbox in sync when individual boxes change.
	$( document ).on( 'change', 'input[name="food_ids[]"]', function () {
		const all   = $( 'input[name="food_ids[]"]' ).length;
		const checked = $( 'input[name="food_ids[]"]:checked' ).length;
		$( '#fcc-select-all' ).prop( 'indeterminate', checked > 0 && checked < all );
		$( '#fcc-select-all' ).prop( 'checked', checked === all );
	} );

	// -------------------------------------------------------------------------
	// Serving sizes editor (dynamic add/remove rows)
	// -------------------------------------------------------------------------
	const $servingContainer = $( '#fcc-serving-sizes' );

	$( document ).on( 'click', '.fcc-add-serving', function () {
		const row = $( '<div class="fcc-serving-row">' +
			'<input type="text" name="serving_label[]" placeholder="' + escAttr( fccAdmin.i18n.saved ) + '" class="fcc-serving-label">' +
			'<input type="number" name="serving_grams[]" placeholder="grams" step="0.1" min="0.1" class="fcc-serving-grams">' +
			'<button type="button" class="button fcc-remove-serving">×</button>' +
			'</div>' );
		$servingContainer.append( row );
		row.find( 'input' ).first().focus();
	} );

	$( document ).on( 'click', '.fcc-remove-serving', function () {
		const $rows = $servingContainer.find( '.fcc-serving-row' );
		if ( $rows.length > 1 ) {
			$( this ).closest( '.fcc-serving-row' ).remove();
		} else {
			// Keep the last row but clear it.
			$( this ).closest( '.fcc-serving-row' ).find( 'input' ).val( '' );
		}
	} );

	// -------------------------------------------------------------------------
	// Copy shortcode to clipboard
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.fcc-copy-shortcode', function () {
		const text = $( this ).data( 'clipboard' );
		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( text ).then( function () {
				showToast( fccAdmin.i18n.saved );
			} );
		} else {
			const el = document.createElement( 'textarea' );
			el.value = text;
			document.body.appendChild( el );
			el.select();
			document.execCommand( 'copy' );
			document.body.removeChild( el );
			showToast( fccAdmin.i18n.saved );
		}
	} );

	// -------------------------------------------------------------------------
	// Auto-generate kJ from kcal input
	// -------------------------------------------------------------------------
	$( '#energy_kcal' ).on( 'input', function () {
		const kcal = parseFloat( this.value );
		if ( ! isNaN( kcal ) ) {
			$( '#energy_kj' ).val( ( kcal * 4.184 ).toFixed( 2 ) );
		}
	} );

	// -------------------------------------------------------------------------
	// Small toast notification
	// -------------------------------------------------------------------------
	function showToast( msg ) {
		let $toast = $( '#fcc-toast' );
		if ( ! $toast.length ) {
			$toast = $( '<div id="fcc-toast" style="position:fixed;bottom:2rem;right:2rem;background:#1d2327;color:#fff;padding:.6rem 1.2rem;border-radius:6px;font-size:.875rem;z-index:99999;opacity:0;transition:opacity .3s;pointer-events:none;"></div>' );
			$( 'body' ).append( $toast );
		}
		$toast.text( msg ).animate( { opacity: 1 }, 200, function () {
			setTimeout( function () { $toast.animate( { opacity: 0 }, 400 ); }, 2000 );
		} );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------
	function escAttr( s ) {
		return String( s ).replace( /"/g, '&quot;' ).replace( /</g, '&lt;' );
	}

}( window.jQuery ) );
