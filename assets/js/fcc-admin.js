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
			per_page:    $list.data( 'per-page' ) || 20,
			status:      $list.data( 'status' ) || '',
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
			per_page:    $list.data( 'per-page' ) || 20,
			status:      $list.data( 'status' ) || '',
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
	// Quick Edit — inline row for foods table
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.fcc-foods-qe-trigger', function () {
		var $row = $( this ).closest( '.fcc-foods-row' );
		// Remove any existing quick-edit row.
		$( '.fcc-foods-qe-row' ).remove();

		var id   = $row.data( 'id' );
		var cats = $( '#fcc-bulk-cat' ).html();
		var qe   = '<tr class="fcc-foods-qe-row"><td colspan="11" class="fcc-foods-qe-cell">'
			+ '<div class="fcc-foods-qe-inner">'
			+ '<label>Name <input type="text" class="fcc-qe-name" value="' + $row.data('name').toString().replace(/"/g,'&quot;') + '"></label>'
			+ '<label>Category <select class="fcc-qe-cat">' + cats + '</select></label>'
			+ '<label>kcal <input type="number" class="fcc-qe-kcal" value="' + $row.data('kcal') + '" step="0.1"></label>'
			+ '<label>Protein <input type="number" class="fcc-qe-protein" value="' + $row.data('protein') + '" step="0.1"></label>'
			+ '<label>Carbs <input type="number" class="fcc-qe-carbs" value="' + $row.data('carbs') + '" step="0.1"></label>'
			+ '<label>Fat <input type="number" class="fcc-qe-fat" value="' + $row.data('fat') + '" step="0.1"></label>'
			+ '<div class="fcc-foods-qe-btns">'
			+ '<button type="button" class="fcc-foods-qe-save button button-primary">Save</button>'
			+ '<button type="button" class="fcc-foods-qe-cancel button">Cancel</button>'
			+ '</div></div></td></tr>';
		$row.after( qe );
		var $qeRow = $row.next( '.fcc-foods-qe-row' );
		$qeRow.find( '.fcc-qe-cat' ).val( $row.data( 'cat' ) );
		$qeRow.find( '.fcc-qe-name' ).focus();
	} );

	$( document ).on( 'click', '.fcc-foods-qe-cancel', function () {
		$( this ).closest( '.fcc-foods-qe-row' ).remove();
	} );

	$( document ).on( 'click', '.fcc-foods-qe-save', function () {
		var $qe   = $( this ).closest( '.fcc-foods-qe-row' );
		var $row  = $qe.prev( '.fcc-foods-row' );
		var $list = $( '#fcc-foods-list' );
		var $btn  = $( this );
		$btn.prop( 'disabled', true ).text( 'Saving…' );

		$.post( fccAdmin.ajaxUrl, {
			action:         'fcc_quick_update_food',
			_ajax_nonce:    $list.data( 'nonce' ),
			food_id:        $row.data( 'id' ),
			name:           $qe.find( '.fcc-qe-name' ).val(),
			category_id:    $qe.find( '.fcc-qe-cat' ).val(),
			energy_kcal:    $qe.find( '.fcc-qe-kcal' ).val(),
			protein_g:      $qe.find( '.fcc-qe-protein' ).val(),
			carbohydrate_g: $qe.find( '.fcc-qe-carbs' ).val(),
			fat_g:          $qe.find( '.fcc-qe-fat' ).val(),
		}, function ( res ) {
			if ( res.success ) {
				$row.data( 'name', $qe.find('.fcc-qe-name').val() );
				$row.data( 'cat', $qe.find('.fcc-qe-cat').val() );
				$row.data( 'kcal', $qe.find('.fcc-qe-kcal').val() );
				$row.data( 'protein', $qe.find('.fcc-qe-protein').val() );
				$row.data( 'carbs', $qe.find('.fcc-qe-carbs').val() );
				$row.data( 'fat', $qe.find('.fcc-qe-fat').val() );
				// Update visible cells.
				$row.find('.fcc-foods-name-link').text( $qe.find('.fcc-qe-name').val() );
				$row.find('.fcc-foods-kcal').text( Math.round( parseFloat($qe.find('.fcc-qe-kcal').val()) ) );
				$row.find('.fcc-foods-td--protein').html( parseFloat($qe.find('.fcc-qe-protein').val()).toFixed(1) + 'g' );
				$row.find('.fcc-foods-td--carbs').html( parseFloat($qe.find('.fcc-qe-carbs').val()).toFixed(1) + 'g' );
				$row.find('.fcc-foods-td--fat').html( parseFloat($qe.find('.fcc-qe-fat').val()).toFixed(1) + 'g' );
				$qe.remove();
			} else {
				$btn.prop( 'disabled', false ).text( 'Save' );
				alert( res.data || 'Error saving.' );
			}
		} ).fail( function () {
			$btn.prop( 'disabled', false ).text( 'Save' );
		} );
	} );

	// -------------------------------------------------------------------------
	// Per-page selector — AJAX reload (no full page refresh)
	// -------------------------------------------------------------------------
	$( document ).on( 'change', '#fcc-perpage', function () {
		var pp    = parseInt( $( this ).val(), 10 ) || 20;
		var $list = $( '#fcc-foods-list' );
		$list.data( 'per-page', pp );

		$list.addClass( 'fcc-loading' );
		$.post( fccAdmin.ajaxUrl, {
			action:      'fcc_foods_page',
			_ajax_nonce: $list.data( 'nonce' ),
			paged:       1,
			s:           $list.data( 'search' ),
			category_id: $list.data( 'cat' ),
			orderby:     $list.data( 'orderby' ),
			order:       $list.data( 'order' ),
			per_page:    pp,
			status:      $list.data( 'status' ) || '',
		}, function ( res ) {
			$list.removeClass( 'fcc-loading' );
			if ( res.success ) {
				$list.html( res.data.html );
				$list[0].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		} ).fail( function () { $list.removeClass( 'fcc-loading' ); } );
	} );

	// -------------------------------------------------------------------------
	// Bulk Action — show/hide category picker when "Change Category" selected
	// -------------------------------------------------------------------------
	$( document ).on( 'change', '#fcc-bulk-action', function () {
		var $cat = $( '#fcc-bulk-cat' );
		if ( $( this ).val() === 'change_category' ) {
			$cat.show();
		} else {
			$cat.hide();
		}
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
	// Category filter — instant JS hide/show cards by name
	// -------------------------------------------------------------------------
	$( document ).on( 'input', '#fcc-cat-filter', function () {
		var q = this.value.toLowerCase();
		$( '#fcc-cats-grid .fcc-cat-card' ).each( function () {
			var name = ( $( this ).data( 'cat-name' ) || '' ).toString().toLowerCase();
			$( this ).toggle( name.indexOf( q ) !== -1 );
		} );
	} );

	// -------------------------------------------------------------------------
	// Category sort — reorder cards by data attribute
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.fcc-cats-sort-btn', function () {
		$( '.fcc-cats-sort-btn' ).removeClass( 'fcc-cats-sort-btn--active' );
		$( this ).addClass( 'fcc-cats-sort-btn--active' );

		var key   = $( this ).data( 'sort' );
		var $grid = $( '#fcc-cats-grid' );
		var $cards = $grid.children( '.fcc-cat-card' ).detach().sort( function ( a, b ) {
			var $a = $( a ), $b = $( b );
			if ( key === 'name' )     return ( $a.data('cat-name') || '' ).toString().localeCompare( ( $b.data('cat-name') || '' ).toString() );
			if ( key === 'foods' )    return ( parseInt( $b.data('foods'), 10 ) || 0 ) - ( parseInt( $a.data('foods'), 10 ) || 0 );
			if ( key === 'searches' ) return ( parseInt( $b.data('searches'), 10 ) || 0 ) - ( parseInt( $a.data('searches'), 10 ) || 0 );
			return ( parseInt( $a.data('cat-order'), 10 ) || 0 ) - ( parseInt( $b.data('cat-order'), 10 ) || 0 );
		} );
		$grid.append( $cards );
	} );

	// -------------------------------------------------------------------------
	// Category merge — move foods from source into target, delete source
	// -------------------------------------------------------------------------
	$( document ).on( 'click', '.fcc-cat-merge-btn', function () {
		var $card  = $( this ).closest( '.fcc-cat-card' );
		var srcId  = $card.data( 'cat-id' );
		var srcName = $card.data( 'cat-name' );

		// Build list of other categories.
		var opts = '';
		$( '#fcc-cats-grid .fcc-cat-card' ).each( function () {
			var cid  = $( this ).data( 'cat-id' );
			var name = $( this ).data( 'cat-name' );
			if ( cid !== srcId ) {
				opts += name + ' (#' + cid + ')\n';
			}
		} );

		var target = prompt(
			'Merge "' + srcName + '" into which category?\n\nAll foods will be moved, then "' + srcName + '" will be deleted.\n\nEnter the target category ID number:\n\n' + opts
		);
		if ( ! target ) return;
		var targetId = parseInt( target.replace( /\D/g, '' ), 10 );
		if ( ! targetId || targetId === srcId ) { alert( 'Invalid category ID.' ); return; }

		var $region = $( '#fcc-cats-region' );
		$region.addClass( 'fcc-loading' );

		$.post( fccAdmin.ajaxUrl, {
			action:      'fcc_ajax_merge_category',
			_ajax_nonce: $region.data( 'nonce' ),
			source_id:   srcId,
			target_id:   targetId,
		}, function ( res ) {
			$region.removeClass( 'fcc-loading' );
			if ( res.success ) {
				$region.html( res.data.html );
				if ( typeof showToast === 'function' ) showToast( res.data.message );
			} else {
				alert( res.data || 'Merge failed.' );
			}
		} ).fail( function () { $region.removeClass( 'fcc-loading' ); } );
	} );

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
	// Food Requests page — AJAX (grouped view)
	// -------------------------------------------------------------------------

	function loadReqsPage( page ) {
		const $list = $( '#fcc-reqs-list' );
		if ( ! $list.length ) return;
		$list.data( 'paged', page ).addClass( 'fcc-loading' );
		$.post( fccAdmin.ajaxUrl, {
			action:      'fcc_reqs_page',
			_ajax_nonce: $list.data( 'nonce' ),
			paged:       page,
			status:      $list.data( 'status' ) || '',
			sort:        $list.data( 'sort' )   || 'most_requested',
			period:      $list.data( 'period' ) !== undefined ? $list.data( 'period' ) : 0,
			date_from:   $list.data( 'date-from' ) || '',
			date_to:     $list.data( 'date-to' )   || '',
		}, function ( response ) {
			$list.removeClass( 'fcc-loading' );
			if ( response.success ) {
				$list.html( response.data.html );
				$list[0].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		} ).fail( function () { $list.removeClass( 'fcc-loading' ); } );
	}

	function loadMsPage( page ) {
		const $list = $( '#fcc-ms-list' );
		if ( ! $list.length ) return;
		$list.data( 'paged', page ).addClass( 'fcc-loading' );
		$.post( fccAdmin.ajaxUrl, {
			action:      'fcc_ms_page',
			_ajax_nonce: $list.data( 'nonce' ),
			paged:       page,
			status:      $list.data( 'status' ) || '',
			sort:        $list.data( 'sort' )   || 'most_searched',
			period:      $list.data( 'period' ) !== undefined ? $list.data( 'period' ) : 0,
			date_from:   $list.data( 'date-from' ) || '',
			date_to:     $list.data( 'date-to' )   || '',
		}, function ( response ) {
			$list.removeClass( 'fcc-loading' );
			if ( response.success ) {
				$list.html( response.data.html );
				$list[0].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		} ).fail( function () { $list.removeClass( 'fcc-loading' ); } );
	}

	// Status filter tabs.
	$( document ).on( 'click', '.fcc-reqs-tab-btn', function () {
		const $btn   = $( this );
		const region = $btn.data( 'region' );
		const status = $btn.data( 'status' ) !== undefined ? String( $btn.data( 'status' ) ) : '';

		$( '.fcc-reqs-tab-btn[data-region="' + region + '"]' ).removeClass( 'fcc-reqs-tab--active' );
		$btn.addClass( 'fcc-reqs-tab--active' );

		if ( 'ms' === region ) {
			$( '#fcc-ms-list' ).data( 'status', status );
			loadMsPage( 1 );
		} else {
			$( '#fcc-reqs-list' ).data( 'status', status );
			loadReqsPage( 1 );
		}
	} );

	// Sort pills.
	$( document ).on( 'click', '.fcc-reqs-sort-btn', function () {
		const $btn   = $( this );
		const region = $btn.data( 'region' );
		const sort   = $btn.data( 'sort' );

		$( '.fcc-reqs-sort-btn[data-region="' + region + '"]' ).removeClass( 'fcc-reqs-pill--active' );
		$btn.addClass( 'fcc-reqs-pill--active' );

		if ( 'ms' === region ) {
			$( '#fcc-ms-list' ).data( 'sort', sort );
			loadMsPage( 1 );
		} else {
			$( '#fcc-reqs-list' ).data( 'sort', sort );
			loadReqsPage( 1 );
		}
	} );

	// Period pills.
	$( document ).on( 'click', '.fcc-reqs-period-btn', function () {
		const $btn    = $( this );
		const region  = $btn.data( 'region' );
		const period  = parseInt( $btn.data( 'period' ), 10 );
		const drId    = 'ms' === region ? '#fcc-ms-daterange' : '#fcc-reqs-daterange';

		$( '.fcc-reqs-period-btn[data-region="' + region + '"]' ).removeClass( 'fcc-reqs-pill--active' );
		$btn.addClass( 'fcc-reqs-pill--active' );
		$( drId ).prop( 'hidden', period !== -1 );

		if ( 'ms' === region ) {
			$( '#fcc-ms-list' ).data( 'period', period );
			if ( period !== -1 ) loadMsPage( 1 );
		} else {
			$( '#fcc-reqs-list' ).data( 'period', period );
			if ( period !== -1 ) loadReqsPage( 1 );
		}
	} );

	// Custom date inputs.
	$( document ).on( 'change', '.fcc-reqs-date-input', function () {
		const $inp   = $( this );
		const region = $inp.data( 'region' );
		const field  = $inp.data( 'field' );
		const val    = $inp.val();

		if ( 'ms' === region ) {
			$( '#fcc-ms-list' ).data( field === 'date_from' ? 'date-from' : 'date-to', val );
			loadMsPage( 1 );
		} else {
			$( '#fcc-reqs-list' ).data( field === 'date_from' ? 'date-from' : 'date-to', val );
			loadReqsPage( 1 );
		}
	} );

	// Pagination — food requests.
	$( document ).on( 'click', '#fcc-reqs-list .fcc-reqs-page-btn[data-page]', function ( e ) {
		e.preventDefault();
		loadReqsPage( parseInt( $( this ).data( 'page' ), 10 ) );
	} );

	// Pagination — missed searches.
	$( document ).on( 'click', '#fcc-ms-list .fcc-ms-page-btn[data-page]', function ( e ) {
		e.preventDefault();
		loadMsPage( parseInt( $( this ).data( 'page' ), 10 ) );
	} );

	// Note expand / collapse.
	$( document ).on( 'click', '.fcc-reqs-note-toggle', function () {
		const $toggle = $( this );
		const $wrap   = $toggle.closest( '.fcc-reqs-note' );
		const $short  = $wrap.find( '.fcc-reqs-note__short' );
		const $full   = $wrap.find( '.fcc-reqs-note__full' );
		const expanded = ! $full.prop( 'hidden' );

		$short.prop( 'hidden', ! expanded );
		$full.prop( 'hidden', expanded );
		$toggle.html( expanded
			? ( escAttr( 'More' ) + ' &#x2193;' )
			: ( escAttr( 'Less' ) + ' &#x2191;' )
		);
	} );

	// Food Requests — inline search filter (client-side).
	$( document ).on( 'input', '#fcc-reqs-search', function () {
		var q = this.value.toLowerCase();
		$( '#fcc-reqs-list .fcc-reqs-row' ).each( function () {
			var food = ( $( this ).data( 'food' ) || '' ).toString().toLowerCase();
			$( this ).toggle( food.indexOf( q ) !== -1 );
		} );
	} );

	// Food Requests — select-all checkbox.
	$( document ).on( 'change', '.fcc-reqs-select-all', function () {
		var checked = this.checked;
		$( '#fcc-reqs-list .fcc-reqs-row-check' ).prop( 'checked', checked );
		updateReqsBulkBar();
	} );
	$( document ).on( 'change', '.fcc-reqs-row-check', function () {
		updateReqsBulkBar();
	} );

	function updateReqsBulkBar() {
		var $checks  = $( '#fcc-reqs-list .fcc-reqs-row-check:checked' );
		var $bar     = $( '#fcc-reqs-bulk-bar' );
		var $counter = $( '#fcc-reqs-selected-count' );
		if ( $checks.length > 0 ) {
			$bar.removeAttr( 'hidden' );
			$counter.text( $checks.length );
		} else {
			$bar.attr( 'hidden', '' );
		}
	}

	// Food Requests — batch actions (Mark Added / Dismiss for selected rows).
	$( document ).on( 'click', '.fcc-reqs-bulk-btn', function () {
		var bulkAction = $( this ).data( 'bulk-action' );
		var foods = [];
		$( '#fcc-reqs-list .fcc-reqs-row-check:checked' ).each( function () {
			foods.push( $( this ).val() );
		} );
		if ( ! foods.length ) return;

		var $section = $( '#fcc-reqs-section' );
		var nonce    = $section.data( 'nonce' );
		var ajaxAction = bulkAction === 'mark_added' ? 'fcc_ajax_mark_group_added' : 'fcc_ajax_dismiss_group';

		// Process sequentially (simple approach — one per food name).
		var $btn = $( this );
		$btn.prop( 'disabled', true );
		var done = 0;

		function next() {
			if ( done >= foods.length ) {
				// Reload the table via the existing AJAX paginator.
				$( '#fcc-reqs-list .fcc-reqs-page-btn--active' ).trigger( 'click' );
				$btn.prop( 'disabled', false );
				return;
			}
			$.post( fccAdmin.ajaxUrl, {
				action:      ajaxAction,
				_ajax_nonce: nonce,
				food_name:   foods[ done ],
			}, function () { done++; next(); } ).fail( function () { done++; next(); } );
		}
		next();
	} );

	// Food Requests — group action buttons (Mark Added / Dismiss).
	$( document ).on( 'click', '#fcc-reqs-list .fcc-reqs-group-btn', function () {
		const $btn    = $( this );
		const action  = $btn.data( 'action' );
		const food    = $btn.data( 'food' );
		const $list   = $( '#fcc-reqs-list' );

		const ajaxAction = {
			mark_added: 'fcc_ajax_mark_group_added',
			dismiss:    'fcc_ajax_dismiss_group',
		}[ action ];
		if ( ! ajaxAction ) return;

		$btn.prop( 'disabled', true );
		$.post( fccAdmin.ajaxUrl, {
			action:      ajaxAction,
			food_name:   food,
			_ajax_nonce: $list.data( 'nonce' ),
		}, function ( response ) {
			if ( response.success ) {
				loadReqsPage( $list.data( 'paged' ) || 1 );
			} else {
				$btn.prop( 'disabled', false );
				showToast( ( response.data && response.data.message ) || fccAdmin.i18n.error );
			}
		} ).fail( function () { $btn.prop( 'disabled', false ); } );
	} );

	// Missed Searches — action buttons (Mark Added / Dismiss / Delete).
	$( document ).on( 'click', '#fcc-ms-list .fcc-ms-action-btn', function () {
		const $btn   = $( this );
		const action = $btn.data( 'action' );
		const id     = $btn.data( 'id' );
		const $list  = $( '#fcc-ms-list' );

		if ( 'delete' === action ) {
			if ( ! window.confirm( fccAdmin.i18n.confirmDeleteReq || 'Delete this entry?' ) ) return;
		}

		const ajaxAction = {
			mark_added: 'fcc_ajax_mark_ms_added',
			dismiss:    'fcc_ajax_dismiss_ms',
			delete:     'fcc_ajax_delete_ms',
		}[ action ];
		if ( ! ajaxAction ) return;

		$btn.prop( 'disabled', true );
		$.post( fccAdmin.ajaxUrl, {
			action:      ajaxAction,
			ms_id:       id,
			_ajax_nonce: $list.data( 'nonce' ),
		}, function ( response ) {
			if ( response.success ) {
				loadMsPage( $list.data( 'paged' ) || 1 );
			} else {
				$btn.prop( 'disabled', false );
				showToast( ( response.data && response.data.message ) || fccAdmin.i18n.error );
			}
		} ).fail( function () { $btn.prop( 'disabled', false ); } );
	} );

	// Bulk Delete All Dismissed — both Missed Searches and User Requests.
	$( document ).on( 'click', '.fcc-bulk-delete-dismissed-btn', function () {
		var $btn    = $( this );
		var region  = $btn.data( 'region' );
		if ( ! window.confirm( 'Delete ALL dismissed entries? This cannot be undone.' ) ) return;
		$btn.prop( 'disabled', true ).text( 'Deleting…' );
		var ajaxAction = region === 'ms' ? 'fcc_ajax_bulk_delete_dismissed_ms' : 'fcc_ajax_bulk_delete_dismissed_reqs';
		var nonceKey   = region === 'ms' ? $( '#fcc-ms-list' ).data( 'nonce' ) : $( '#fcc-reqs-list' ).data( 'nonce' );
		$.post( fccAdmin.ajaxUrl, {
			action:      ajaxAction,
			_ajax_nonce: nonceKey,
		}, function ( response ) {
			if ( response.success ) {
				showToast( response.data.message || 'Deleted.' );
				if ( region === 'ms' ) {
					$( '#fcc-ms-list' ).data( 'status', 'dismissed' );
					loadMsPage( 1 );
				} else {
					$( '#fcc-reqs-list' ).data( 'status', 'dismissed' );
					loadReqsPage( 1 );
				}
				$btn.remove();
			} else {
				$btn.prop( 'disabled', false ).text( 'Delete All Dismissed' );
				showToast( ( response.data && response.data.message ) || 'Error' );
			}
		} ).fail( function () { $btn.prop( 'disabled', false ).text( 'Delete All Dismissed' ); } );
	} );

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------
	function escAttr( s ) {
		return String( s ).replace( /"/g, '&quot;' ).replace( /</g, '&lt;' );
	}

}( window.jQuery ) );
