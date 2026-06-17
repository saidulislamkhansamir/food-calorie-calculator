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
	// AJAX column sort — Foods list table (all sortable headers)
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '#fcc-foods-list .fcc-foods-sort', function ( e ) {
		e.preventDefault();

		const $list = $( '#fcc-foods-list' );
		const href  = $( this ).attr( 'href' );

		// Parse new orderby/order from the link's URL params.
		const urlObj     = new URL( href, window.location.href );
		const newOrderby = urlObj.searchParams.get( 'orderby' ) || 'name';
		const newOrder   = urlObj.searchParams.get( 'order' )   || 'asc';

		// Persist the new sort state on the container for subsequent pagination clicks.
		$list.data( 'orderby', newOrderby ).data( 'order', newOrder );

		const nonce  = $list.data( 'nonce' );
		const search = $list.data( 'search' );
		const cat    = $list.data( 'cat' );

		$list.addClass( 'fcc-loading' );

		$.post( fccAdmin.ajaxUrl, {
			action:      'fcc_foods_page',
			_ajax_nonce: nonce,
			paged:       1,
			s:           search,
			category_id: cat,
			orderby:     newOrderby,
			order:       newOrder,
		}, function ( response ) {
			$list.removeClass( 'fcc-loading' );
			if ( response.success ) {
				$list.html( response.data.html );
				$list[0].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		} ).fail( function () {
			$list.removeClass( 'fcc-loading' );
		} );
	} );

	// -------------------------------------------------------------------------
	// AJAX pagination — Foods list table
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '#fcc-foods-list .fcc-foods-page-btn[data-page]', function ( e ) {
		e.preventDefault();

		const $list  = $( '#fcc-foods-list' );
		const page   = parseInt( $( this ).data( 'page' ), 10 );
		const nonce  = $list.data( 'nonce' );
		const search = $list.data( 'search' );
		const cat    = $list.data( 'cat' );
		const orderby = $list.data( 'orderby' );
		const order   = $list.data( 'order' );

		$list.addClass( 'fcc-loading' );

		$.post( fccAdmin.ajaxUrl, {
			action:      'fcc_foods_page',
			_ajax_nonce: nonce,
			paged:       page,
			s:           search,
			category_id: cat,
			orderby:     orderby,
			order:       order,
		}, function ( response ) {
			$list.removeClass( 'fcc-loading' );
			if ( response.success ) {
				$list.html( response.data.html );
				$list[0].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		} ).fail( function () {
			$list.removeClass( 'fcc-loading' );
		} );
	} );

	// -------------------------------------------------------------------------
	// AJAX Categories — edit, save, delete (no page reload)
	// -------------------------------------------------------------------------

	// Edit button: populate form without reload.
	$( document ).on( 'click', '.fcc-cat-edit-btn', function () {
		const $card  = $( this ).closest( '.fcc-cat-card' );
		const catId  = $card.data( 'cat-id' );
		const catName = $card.data( 'cat-name' );

		$( '#cat_name' ).val( catName );
		// Trigger input so slug auto-gen marks itself as manual (won't overwrite set value).
		$( '#cat_slug' ).val( $card.data( 'cat-slug' ) ).trigger( 'input' );
		$( '#cat_desc' ).val( $card.data( 'cat-desc' ) );
		$( '#cat_order' ).val( $card.data( 'cat-order' ) );
		$( '[name="category_id"]', '#fcc-cat-form' ).val( catId );

		// Switch formbar to edit mode.
		$( '#fcc-cats-formbar' ).addClass( 'fcc-cats-formbar--edit' );
		$( '#fcc-cats-formbar-mode' )
			.addClass( 'fcc-cats-formbar__mode--edit' )
			.html( '&#9999;&#65039; ' + fccAdmin.i18n.editing );
		$( '#fcc-cats-formbar-name' ).text( catName ).removeAttr( 'hidden' );
		$( '#fcc-cat-submit-btn' ).addClass( 'fcc-cats-qsubmit--update' ).text( fccAdmin.i18n.update );
		$( '#fcc-cat-cancel' ).removeAttr( 'hidden' );

		// Highlight active card.
		$( '.fcc-cat-card' ).removeClass( 'fcc-cat-card--active' );
		$card.addClass( 'fcc-cat-card--active' );

		$( '#fcc-cat-form' )[0].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
	} );

	// Cancel edit: reset form without navigating.
	$( document ).on( 'click', '#fcc-cat-cancel', function ( e ) {
		e.preventDefault();
		resetCatForm();
	} );

	// Form submit: AJAX save/update.
	$( document ).on( 'submit', '#fcc-cat-form', function ( e ) {
		e.preventDefault();
		const $form   = $( this );
		const $btn    = $( '#fcc-cat-submit-btn' );
		const $region = $( '#fcc-cats-region' );

		const $actionInput = $form.find( '[name="action"]' );
		$actionInput.val( 'fcc_ajax_save_category' );
		const data = $form.serialize();
		$actionInput.val( 'fcc_save_category' );

		$btn.prop( 'disabled', true );
		$region.addClass( 'fcc-loading' );

		$.post( fccAdmin.ajaxUrl, data, function ( response ) {
			$btn.prop( 'disabled', false );
			$region.removeClass( 'fcc-loading' );
			if ( response.success ) {
				$region.html( response.data.html );
				resetCatForm();
				showToast( response.data.message || fccAdmin.i18n.saved );
			} else {
				showToast( ( response.data && response.data.message ) || fccAdmin.i18n.error );
			}
		} ).fail( function () {
			$btn.prop( 'disabled', false );
			$region.removeClass( 'fcc-loading' );
			showToast( fccAdmin.i18n.error );
		} );
	} );

	// Delete button: AJAX delete.
	$( document ).on( 'click', '.fcc-cat-delete-btn', function () {
		const msg = $( this ).data( 'confirm' ) || fccAdmin.i18n.confirmDelete;
		if ( ! window.confirm( msg ) ) {
			return;
		}
		const catId   = $( this ).data( 'cat-id' );
		const $region = $( '#fcc-cats-region' );

		$region.addClass( 'fcc-loading' );

		$.post( fccAdmin.ajaxUrl, {
			action:      'fcc_ajax_delete_category',
			category_id: catId,
			_ajax_nonce: $region.data( 'nonce' ),
		}, function ( response ) {
			$region.removeClass( 'fcc-loading' );
			if ( response.success ) {
				$region.html( response.data.html );
				resetCatForm();
				showToast( response.data.message || fccAdmin.i18n.saved );
			} else {
				showToast( ( response.data && response.data.message ) || fccAdmin.i18n.error );
			}
		} ).fail( function () {
			$region.removeClass( 'fcc-loading' );
			showToast( fccAdmin.i18n.error );
		} );
	} );

	function resetCatForm() {
		const $form = $( '#fcc-cat-form' );
		$form[0].reset();
		$form.find( '[name="category_id"]' ).val( '0' );

		$( '#fcc-cats-formbar' ).removeClass( 'fcc-cats-formbar--edit' );
		$( '#fcc-cats-formbar-mode' )
			.removeClass( 'fcc-cats-formbar__mode--edit' )
			.html( '&#10133; ' + fccAdmin.i18n.addCategory );
		$( '#fcc-cats-formbar-name' ).text( '' ).attr( 'hidden', '' );
		$( '#fcc-cat-submit-btn' ).removeClass( 'fcc-cats-qsubmit--update' ).text( fccAdmin.i18n.addCategoryBtn );
		$( '#fcc-cat-cancel' ).attr( 'hidden', '' );

		$( '.fcc-cat-card' ).removeClass( 'fcc-cat-card--active' );
	}

	// -------------------------------------------------------------------------
	// AJAX Settings save — inline feedback, no reload
	// -------------------------------------------------------------------------
	$( document ).on( 'submit', '#fcc-stg-form', function ( e ) {
		e.preventDefault();
		const $form = $( this );
		const $btn  = $form.find( '.fcc-stg-save' );
		const $tab  = $form.find( '.fcc-stg-footer__tab' );
		const origTabText = $tab.text();

		const $actionInput = $form.find( '[name="action"]' );
		$actionInput.val( 'fcc_ajax_save_settings' );
		const data = $form.serialize();
		$actionInput.val( 'fcc_save_settings' );

		$btn.prop( 'disabled', true );

		$.post( fccAdmin.ajaxUrl, data, function ( response ) {
			$btn.prop( 'disabled', false );
			if ( response.success ) {
				const msg = ( response.data && response.data.message ) || fccAdmin.i18n.saved;
				$tab.text( '✓ ' + msg );
				showToast( msg );
				setTimeout( function () { $tab.text( origTabText ); }, 3000 );
			} else {
				showToast( ( response.data && response.data.message ) || fccAdmin.i18n.error );
			}
		} ).fail( function () {
			$btn.prop( 'disabled', false );
			showToast( fccAdmin.i18n.error );
		} );
	} );

	// -------------------------------------------------------------------------
	// AJAX Import — file upload with inline result
	// -------------------------------------------------------------------------
	$( document ).on( 'submit', '#fcc-import-form', function ( e ) {
		e.preventDefault();
		const $form   = $( this );
		const $btn    = $( '#fcc-import-btn' );
		const $result = $( '#fcc-import-result' );
		const origBtnHtml = $btn.html();

		const formData = new FormData( $form[0] );
		formData.set( 'action', 'fcc_ajax_import' );

		$btn.prop( 'disabled', true ).text( fccAdmin.i18n.importing );
		$result.hide().text( '' ).removeClass( 'fcc-import-result--success fcc-import-result--error' );

		$.ajax( {
			url:         fccAdmin.ajaxUrl,
			method:      'POST',
			data:        formData,
			processData: false,
			contentType: false,
			success: function ( response ) {
				$btn.prop( 'disabled', false ).html( origBtnHtml );
				if ( response.success ) {
					let html = '<strong>' + escHtml( response.data.message ) + '</strong>';
					if ( response.data.errors && response.data.errors.length ) {
						html += '<ul class="fcc-import-result__errors">';
						$.each( response.data.errors, function ( i, err ) {
							html += '<li>' + escHtml( err ) + '</li>';
						} );
						html += '</ul>';
					}
					$result.addClass( 'fcc-import-result--success' ).html( html ).removeAttr( 'hidden' ).show();
					showToast( response.data.message );
				} else {
					const errMsg = ( typeof response.data === 'string' )
						? response.data
						: ( response.data && response.data.message ) || fccAdmin.i18n.error;
					$result.addClass( 'fcc-import-result--error' )
						.text( errMsg )
						.removeAttr( 'hidden' ).show();
				}
			},
			error: function () {
				$btn.prop( 'disabled', false ).html( origBtnHtml );
				$result.addClass( 'fcc-import-result--error' )
					.text( fccAdmin.i18n.error )
					.removeAttr( 'hidden' ).show();
			},
		} );
	} );

	function escHtml( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

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
