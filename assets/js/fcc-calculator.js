/**
 * Food Calorie Calculator — Frontend Logic
 *
 * Vanilla JS. No jQuery. No CDN dependencies.
 * All data comes from the REST API (fcc/v1) or from the URL state (share link).
 * wp_localize_script injects `fccData` — see class-fcc-shortcode.php.
 *
 * Architecture:
 *   State  → single `state` object (selected food, quantity, unit, meal items)
 *   Render → pure functions that write to the DOM from state
 *   Events → thin event listeners that update state and call render
 */
( function () {
	'use strict';

	if ( typeof fccData === 'undefined' ) return;

	const cfg        = fccData;
	const features   = cfg.features   || {};
	const general    = cfg.general    || {};
	const ri         = general.ri     || {};
	const fsa        = general.fsa    || {};
	const i18n       = cfg.i18n       || {};
	const labels     = cfg.labels     || {};
	const appearance = cfg.appearance || {};
	const advanced   = cfg.advanced   || {};
	const ht         = advanced.healthThresholds || {};
	const decimals   = general.decimalPlaces !== undefined ? Number( general.decimalPlaces ) : 1;
	const OZ_TO_G    = 28.3495;

	// -------------------------------------------------------------------------
	// State
	// -------------------------------------------------------------------------
	const state = {
		food:         null,   // Full food object from REST API.
		quantity:     100,    // User-entered quantity.
		unit:         'g',    // 'g', 'oz', or serving slug.
		meal:         [],     // Array of { food, grams, label }.
		bmrTdee:      null,   // Calculated TDEE (kcal).
		compareA:     null,   // Food object for compare slot A.
		compareB:     null,   // Food object for compare slot B.
		compareQtyA:  100,
		compareQtyB:  100,
		compareUnitA: 'g',
		compareUnitB: 'g',
	};

	// -------------------------------------------------------------------------
	// DOM refs
	// -------------------------------------------------------------------------
	const root         = document.getElementById( 'fcc-calculator' );
	if ( ! root ) return;

	const searchInput    = root.querySelector( '.fcc-search-input' );
	const searchClearBtn = root.querySelector( '.fcc-search-clear' );
	const dropdown       = root.querySelector( '.fcc-results-dropdown' );
	const spinner        = root.querySelector( '.fcc-search-spinner' );
	const popularSection = root.querySelector( '#fcc-popular-section' );
	const popularChips   = root.querySelector( '#fcc-popular-chips' );
	const requestPanel   = root.querySelector( '.fcc-request-panel' );
	const requestForm    = root.querySelector( '#fcc-request-form' );
	const reqFoodInput   = root.querySelector( '#fcc-req-food-input' );
	const reqFoodName    = root.querySelector( '.fcc-req-food-name' );
	const reqSuccess     = root.querySelector( '#fcc-request-success' );
	const qtySection   = root.querySelector( '.fcc-quantity-section' );
	const resultsSection = root.querySelector( '.fcc-results-section' );
	const mealSection  = features.meal_builder ? root.querySelector( '.fcc-meal-section' ) : null;
	const qtyInput     = root.querySelector( '.fcc-quantity-input' );
	const unitSelect   = root.querySelector( '.fcc-unit-select' );
	const foodNameEl   = root.querySelector( '.fcc-selected-food-name' );
	const addToMealBtn = root.querySelector( '.fcc-add-to-meal' );
	const nutrientsBody = root.querySelector( '.fcc-nutrients-body' );
	const servingDesc   = root.querySelector( '.fcc-serving-desc' );
	const macroCanvas   = features.macro_chart  ? root.querySelector( '#fcc-macro-chart' ) : null;
	const macroLegend   = features.macro_chart  ? root.querySelector( '.fcc-macro-legend' ) : null;
	const macroWrapper  = features.macro_chart  ? root.querySelector( '.fcc-macro-chart-wrapper' ) : null;
	const omega3Sec     = features.omega3_display  ? root.querySelector( '.fcc-omega3-section' )  : null;
	const caffeineSec   = features.caffeine_display ? root.querySelector( '.fcc-caffeine-section' ) : null;
	const trafficLights    = features.fsa_traffic_lights ? root.querySelector( '.fcc-traffic-lights' ) : null;
	const healthHighlights = root.querySelector( '.fcc-health-highlights' );
	const bmrSection    = features.bmr_tdee ? root.querySelector( '.fcc-bmr-section' ) : null;
	const mealTabBadge   = root.querySelector( '.fcc-tab-badge' );
	const mealEmptyState = root.querySelector( '.fcc-meal-empty' );
	const affiliateSec   = root.querySelector( '.fcc-affiliate-links' );
	const affiliateChips = root.querySelector( '.fcc-affiliate-links__chips' );
	const suppSec        = root.querySelector( '.fcc-supplement-suggestions' );
	const suppGrid       = suppSec ? suppSec.querySelector( '.fcc-supplement-suggestions__grid' ) : null;

	// -------------------------------------------------------------------------
	// REST helpers
	// -------------------------------------------------------------------------
	let debounceTimer = null;

	function apiFetch( endpoint ) {
		const url = cfg.restUrl + endpoint;
		return fetch( url, {
			headers: {
				'X-WP-Nonce': cfg.restNonce,
				'Content-Type': 'application/json',
			},
		} ).then( function ( r ) { return r.ok ? r.json() : Promise.reject( r ); } );
	}

	function apiPost( endpoint ) {
		return fetch( cfg.restUrl + endpoint, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': cfg.restNonce,
				'Content-Type': 'application/json',
			},
		} ).catch( function () {} );
	}

	// -------------------------------------------------------------------------
	// Autocomplete
	// -------------------------------------------------------------------------
	function updateClearBtn() {
		if ( ! searchClearBtn ) return;
		searchClearBtn.hidden = ! ( searchInput && searchInput.value.length );
	}

	function clearFoodSelection() {
		state.food     = null;
		state.quantity = general.defaultQuantity || 100;
		state.unit     = 'g';
		if ( searchInput    ) { searchInput.value = ''; searchInput.focus(); }
		if ( searchClearBtn ) searchClearBtn.hidden = true;
		hideDropdown();
		hideRequestPanel();
		showPopular();
		if ( qtySection     ) qtySection.hidden    = true;
		if ( resultsSection ) resultsSection.hidden = true;
		root.querySelectorAll( '.fcc-add-to-meal' ).forEach( function ( b ) { b.hidden = true; } );
		if ( foodNameEl ) {
			const bar = foodNameEl.parentNode.querySelector( '.fcc-sponsor-bar' );
			if ( bar ) bar.remove();
		}
		if ( affiliateSec ) affiliateSec.hidden = true;
		if ( suppSec     ) suppSec.hidden      = true;
	}

	function showPopular() {
		if ( popularSection && popularChips && popularChips.children.length ) {
			popularSection.hidden = false;
		}
	}

	function hidePopular() {
		if ( popularSection ) popularSection.hidden = true;
	}

	function showRequestPanel( foodName ) {
		hideDropdown();
		hidePopular();
		if ( ! requestPanel ) return;
		if ( reqFoodInput ) reqFoodInput.value = foodName;
		if ( reqFoodName )  reqFoodName.textContent = foodName;
		if ( requestForm )  { requestForm.reset(); requestForm.hidden = false; }
		if ( reqSuccess )   reqSuccess.hidden = true;
		requestPanel.hidden = false;
		requestPanel.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
	}

	function hideRequestPanel() {
		if ( ! requestPanel ) return;
		requestPanel.hidden = true;
		if ( requestForm ) { requestForm.reset(); requestForm.hidden = false; }
		if ( reqSuccess )  reqSuccess.hidden = true;
	}

	if ( requestPanel ) {
		requestPanel.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '.fcc-request-close' ) ) hideRequestPanel();
		} );
	}

	if ( requestForm ) {
		requestForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			const btn        = requestForm.querySelector( '.fcc-req-submit' );
			const emailInput = requestForm.querySelector( '[name="email"]' );
			const emailError = root.querySelector( '#fcc-req-email-error' );
			const email      = emailInput ? emailInput.value.trim() : '';

			// Validate email.
			const emailValid = email.length > 0 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
			if ( ! emailValid ) {
				if ( emailInput )  emailInput.classList.add( 'fcc-req-input--error' );
				if ( emailError )  emailError.hidden = false;
				if ( emailInput )  emailInput.focus();
				return;
			}
			if ( emailInput )  emailInput.classList.remove( 'fcc-req-input--error' );
			if ( emailError )  emailError.hidden = true;

			const note           = ( requestForm.querySelector( '[name="note"]' )             || {} ).value || '';
			const optinEl        = requestForm.querySelector( '[name="marketing_optin"]' );
			const marketing_optin = optinEl ? optinEl.checked : true;
			const food_name      = reqFoodInput ? reqFoodInput.value : '';
			btn.disabled = true;

			fetch( cfg.restUrl + '/food-requests', {
				method: 'POST',
				headers: {
					'X-WP-Nonce': cfg.restNonce,
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( { food_name: food_name, note: note, email: email, marketing_optin: marketing_optin } ),
			} ).then( function ( r ) {
				btn.disabled = false;
				if ( r.ok ) {
					requestForm.hidden = true;
					if ( reqSuccess ) reqSuccess.hidden = false;
				}
			} ).catch( function () {
				btn.disabled = false;
			} );
		} );
	}

	function loadPopularFoods() {
		if ( ! popularSection || ! popularChips ) return;
		var popLimit = general.popularFoodsCount || 8;
		if ( popLimit === 0 || features.popular_foods === false ) return;
		apiFetch( '/foods/popular?limit=' + popLimit ).then( function ( foods ) {
			if ( ! foods || ! foods.length ) return;
			popularChips.innerHTML = '';
			foods.forEach( function ( food ) {
				const btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.className = 'fcc-popular-chip';
				btn.textContent = food.name;
				btn.addEventListener( 'click', function () {
					searchInput.value = food.name;
					updateClearBtn();
					hidePopular();
					selectFood( food );
				} );
				popularChips.appendChild( btn );
			} );
			popularSection.hidden = false;
		} ).catch( function () {} );
	}

	if ( searchClearBtn ) {
		searchClearBtn.addEventListener( 'click', clearFoodSelection );
	}

	if ( searchInput ) {
		searchInput.addEventListener( 'input', function () {
			clearTimeout( debounceTimer );
			updateClearBtn();
			const q = this.value.trim();
			if ( q.length < ( advanced.searchMinChars || 2 ) ) { hideDropdown(); showPopular(); return; }
			hidePopular();
			debounceTimer = setTimeout( function () { doSearch( q ); }, general.searchDebounce || 280 );
		} );

		searchInput.addEventListener( 'keydown', handleDropdownKeys );
		document.addEventListener( 'click', function ( e ) {
			if ( ! root.contains( e.target ) ) hideDropdown();
		} );
	}

	var missedSearchLog = {};

	function logMissedSearch( q ) {
		var now = Date.now();
		if ( missedSearchLog[ q ] && ( now - missedSearchLog[ q ] ) < 30000 ) return;
		missedSearchLog[ q ] = now;
		fetch( cfg.restUrl + '/missed-search', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( { query: q } ),
		} ).catch( function () {} );
	}

	function doSearch( q ) {
		setSpinner( true );
		const cat = Number( general.defaultCategory || 0 );
		let url = '/foods/search?q=' + encodeURIComponent( q ) + '&limit=' + ( general.searchResultLimit || 10 );
		if ( cat > 0 ) url += '&category=' + cat;

		apiFetch( url )
			.then( renderDropdown )
			.catch( function () { renderDropdown( [] ); } )
			.finally( function () { setSpinner( false ); } );
	}

	function renderDropdown( foods ) {
		dropdown.innerHTML = '';
		if ( ! foods.length ) {
			const li = document.createElement( 'li' );
			li.className = 'fcc-no-results';
			const q = searchInput ? searchInput.value.trim() : '';
			li.innerHTML =
				'<span class="fcc-no-results__text">' + escHtml( i18n.noResults || 'No foods found' ) + '</span>' +
				'<button type="button" class="fcc-no-results__request">' +
				escHtml( i18n.requestFood || 'Request this food' ) +
				'</button>';
			li.querySelector( '.fcc-no-results__request' ).addEventListener( 'click', function () {
				showRequestPanel( q );
			} );
			dropdown.appendChild( li );
			showDropdown();
			if ( q.length >= 2 ) logMissedSearch( q );
			return;
		}
		foods.forEach( function ( food ) {
			const li = document.createElement( 'li' );
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', 'false' );
			li.dataset.id = food.id;
			const isActive = food.is_sponsored && food.sponsor_active;
			if ( isActive ) li.classList.add( 'fcc-result--sponsored' );
			li.innerHTML =
				'<span class="fcc-result-name">' + escHtml( food.name ) + '</span>' +
				'<span class="fcc-result-kcal">' + fmt( food.energy_kcal ) + ' kcal</span>' +
				( isActive ? '<span class="fcc-sponsored-pill">Sponsored</span>' : '' );
			li.addEventListener( 'click', function () { selectFood( food ); } );
			dropdown.appendChild( li );
		} );
		showDropdown();
	}

	function showDropdown() {
		dropdown.hidden = false;
		root.querySelector( '.fcc-autocomplete' ).setAttribute( 'aria-expanded', 'true' );
	}
	function hideDropdown() {
		dropdown.hidden = true;
		dropdown.innerHTML = '';
		root.querySelector( '.fcc-autocomplete' ).setAttribute( 'aria-expanded', 'false' );
	}

	function handleDropdownKeys( e ) {
		if ( dropdown.hidden ) return;
		const items = Array.from( dropdown.querySelectorAll( '[role="option"]' ) );
		if ( ! items.length ) return;
		const current = dropdown.querySelector( '[aria-selected="true"]' );
		let idx = items.indexOf( current );

		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			idx = ( idx + 1 ) % items.length;
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			idx = ( idx - 1 + items.length ) % items.length;
		} else if ( e.key === 'Enter' && current ) {
			e.preventDefault();
			current.click();
			return;
		} else if ( e.key === 'Escape' ) {
			hideDropdown();
			return;
		} else {
			return;
		}

		items.forEach( function ( el ) { el.setAttribute( 'aria-selected', 'false' ); } );
		items[ idx ].setAttribute( 'aria-selected', 'true' );
		items[ idx ].scrollIntoView( { block: 'nearest' } );
	}

	// -------------------------------------------------------------------------
	// Food selection
	// -------------------------------------------------------------------------
	function selectFood( food ) {
		state.food     = food;
		state.quantity = general.defaultQuantity || 100;
		state.unit     = 'g';

		searchInput.value = food.name;
		updateClearBtn();
		hideDropdown();
		hidePopular();
		hideRequestPanel();
		apiPost( '/foods/' + food.id + '/hit' );

		// Populate unit selector with serving sizes.
		rebuildUnitSelect( food );

		if ( qtyInput  ) qtyInput.value = general.defaultQuantity || 100;
		if ( foodNameEl ) {
			foodNameEl.textContent = food.name;
			// Remove any previous sponsor bar.
			const prevBar = foodNameEl.parentNode.querySelector( '.fcc-sponsor-bar' );
			if ( prevBar ) prevBar.remove();
			// Inject sponsor bar if active sponsored food.
			if ( food.is_sponsored && food.sponsor_active ) {
				const bar = document.createElement( 'div' );
				bar.className = 'fcc-sponsor-bar';
				let inner = '';
				if ( food.sponsor_logo_url ) {
					inner += '<img class="fcc-sponsor-logo" src="' + escHtml( food.sponsor_logo_url ) + '" alt="' + escHtml( food.sponsor_name || '' ) + '">';
				}
				inner += '<span class="fcc-sponsor-label">Sponsored';
				if ( food.sponsor_name ) {
					inner += ' by ';
					if ( food.sponsor_url ) {
						inner += '<a href="' + escHtml( food.sponsor_url ) + '" target="_blank" rel="noopener sponsored">' + escHtml( food.sponsor_name ) + '</a>';
					} else {
						inner += escHtml( food.sponsor_name );
					}
				}
				inner += '</span>';
				bar.innerHTML = inner;
				foodNameEl.parentNode.insertBefore( bar, foodNameEl.nextSibling );
				// Fire sponsor-click silently.
				fetch( cfg.restUrl + '/foods/' + food.id + '/sponsor-click', { method: 'POST' } ).catch( function () {} );
			}
		}
		if ( qtySection ) qtySection.hidden = false;
		if ( features.meal_builder ) root.querySelectorAll( '.fcc-add-to-meal' ).forEach( function ( b ) { b.hidden = false; } );

		renderResults();
		updateShareUrl();
	}

	function rebuildUnitSelect( food ) {
		if ( ! unitSelect ) return;
		// Clear all options first.
		unitSelect.innerHTML = '';

		const gOpt = document.createElement( 'option' );
		gOpt.value = 'g'; gOpt.textContent = i18n.grams ? 'grams (g)' : 'grams (g)';
		unitSelect.appendChild( gOpt );

		if ( general.defaultUnit === 'imperial' ) {
			const ozOpt = document.createElement( 'option' );
			ozOpt.value = 'oz'; ozOpt.textContent = 'ounces (oz)';
			unitSelect.appendChild( ozOpt );
		}

		if ( food.serving_sizes && food.serving_sizes.length ) {
			food.serving_sizes.forEach( function ( s, i ) {
				const opt = document.createElement( 'option' );
				opt.value = 'serving_' + i;
				opt.dataset.grams = s.grams;
				const gramsTag = '(' + s.grams + 'g)';
				opt.textContent = s.label.includes( gramsTag ) ? s.label : s.label + ' ' + gramsTag;
				unitSelect.appendChild( opt );
			} );
		}

		updateUnitTriggerText();
	}

	// -------------------------------------------------------------------------
	// Unit / quantity changes
	// -------------------------------------------------------------------------
	if ( qtyInput ) {
		qtyInput.addEventListener( 'input', function () {
			state.quantity = Math.max( 0, parseFloat( this.value ) || 0 );
			renderResults();
			updateShareUrl();
		} );
	}

	if ( unitSelect ) {
		unitSelect.addEventListener( 'change', function () {
			const opt = this.options[ this.selectedIndex ];
			state.unit = this.value;

			if ( state.unit.startsWith( 'serving_' ) ) {
				// Switch quantity to 1 serving, convert to grams internally.
				state.quantity = 1;
				qtyInput.value = 1;
				qtyInput.step  = '1';
			} else if ( state.unit === 'oz' ) {
				qtyInput.step = '0.1';
			} else {
				qtyInput.step = '1';
			}
			renderResults();
			updateShareUrl();
		} );
	}

	/**
	 * Resolve the current quantity to grams for calculation.
	 */
	function quantityInGrams() {
		const q = state.quantity;
		if ( state.unit === 'oz' ) return q * OZ_TO_G;
		if ( state.unit.startsWith( 'serving_' ) ) {
			const idx = parseInt( state.unit.replace( 'serving_', '' ), 10 );
			const sizes = state.food && state.food.serving_sizes;
			if ( sizes && sizes[ idx ] ) return q * sizes[ idx ].grams;
		}
		return q; // grams
	}

	// -------------------------------------------------------------------------
	// Render results
	// -------------------------------------------------------------------------
	function renderResults() {
		if ( ! state.food ) return;

		const grams  = quantityInGrams();
		const factor = grams / 100;
		const food   = state.food;
		const d      = decimals;

		// Update serving description.
		if ( servingDesc ) {
			let desc = fmt( grams, 1 ) + 'g';
			if ( state.unit === 'oz' ) desc = fmt( state.quantity, 1 ) + ' oz (' + fmt( grams, 1 ) + 'g)';
			servingDesc.textContent = desc;
		}

		// Update kcal badge in food header.
		const kcalBadge = root.querySelector( '.fcc-food-kcal-num' );
		if ( kcalBadge ) kcalBadge.textContent = fmt( ( food.energy_kcal || 0 ) * factor, 0 );

		// Build nutrient rows.
		const rows = buildNutrientRows( food, factor, d );
		if ( nutrientsBody ) nutrientsBody.innerHTML = rows;

		// Health highlights.
		renderHealthHighlights( food );

		// FSA traffic lights.
		if ( trafficLights && features.fsa_traffic_lights ) {
			renderTrafficLights( food );
			trafficLights.hidden = false;
		}

		// Macro chart.
		if ( macroWrapper && macroCanvas && features.macro_chart && window.FccChart ) {
			const protein_kcal = ( food.protein_g || 0 ) * factor * 4;
			const carbs_kcal   = ( food.carbohydrate_g || 0 ) * factor * 4;
			const fat_kcal     = ( food.fat_g || 0 ) * factor * 9;
			var chartColors = {
				protein: appearance.chartProteinColour || '#3b82f6',
				carbs:   appearance.chartCarbsColour   || '#f59e0b',
				fat:     appearance.chartFatColour     || '#ef4444',
			};
			window.FccChart.draw(
				macroCanvas,
				{ protein: protein_kcal, carbs: carbs_kcal, fat: fat_kcal },
				macroLegend,
				{ protein: 'Protein', carbs: 'Carbs', fat: 'Fat' },
				chartColors
			);
			var macroDetail = macroWrapper.querySelector( '.fcc-macro-detail' );
			if ( macroDetail ) {
				var pG = fmt( ( food.protein_g || 0 ) * factor, d );
				var cG = fmt( ( food.carbohydrate_g || 0 ) * factor, d );
				var fG = fmt( ( food.fat_g || 0 ) * factor, d );
				var totalKcal = Math.round( protein_kcal + carbs_kcal + fat_kcal );
				var cPerG = totalKcal > 0 && state.quantity > 0 ? fmt( totalKcal / state.quantity, 1 ) : '—';
				macroDetail.innerHTML =
					'<div class="fcc-macro-detail__row">'
					+  '<span class="fcc-macro-detail__dot" style="background:' + chartColors.protein + '"></span>'
					+  '<span class="fcc-macro-detail__lbl">Protein</span>'
					+  '<span class="fcc-macro-detail__val">' + pG + 'g</span>'
					+  '<span class="fcc-macro-detail__kcal">' + Math.round( protein_kcal ) + ' kcal</span>'
					+ '</div>'
					+ '<div class="fcc-macro-detail__row">'
					+  '<span class="fcc-macro-detail__dot" style="background:' + chartColors.carbs + '"></span>'
					+  '<span class="fcc-macro-detail__lbl">Carbs</span>'
					+  '<span class="fcc-macro-detail__val">' + cG + 'g</span>'
					+  '<span class="fcc-macro-detail__kcal">' + Math.round( carbs_kcal ) + ' kcal</span>'
					+ '</div>'
					+ '<div class="fcc-macro-detail__row">'
					+  '<span class="fcc-macro-detail__dot" style="background:' + chartColors.fat + '"></span>'
					+  '<span class="fcc-macro-detail__lbl">Fat</span>'
					+  '<span class="fcc-macro-detail__val">' + fG + 'g</span>'
					+  '<span class="fcc-macro-detail__kcal">' + Math.round( fat_kcal ) + ' kcal</span>'
					+ '</div>'
					+ '<div class="fcc-macro-detail__footer">'
					+  '<span>' + cPerG + ' kcal/g</span>'
					+ '</div>';
			}
			macroWrapper.hidden = false;
		}

		// Omega-3 (only when all values are non-null).
		if ( omega3Sec && features.omega3_display ) {
			renderOmega3( food, factor, d );
		}

		// Caffeine.
		if ( caffeineSec && features.caffeine_display ) {
			renderCaffeine( food, factor, d );
		}

		// Show results.
		if ( resultsSection ) resultsSection.hidden = false;

		// BMR comparison.
		if ( state.bmrTdee && features.daily_needs_comparison ) {
			updateBmrComparison( food, factor );
		}

		// Affiliate buy buttons.
		renderAffiliateLinks( food );

		// Supplement suggestions (contextual lead gen).
		renderSupplementSuggestions( food );
	}

	// -------------------------------------------------------------------------
	// Affiliate buy buttons
	// -------------------------------------------------------------------------
	function renderAffiliateLinks( food ) {
		if ( ! affiliateSec || ! affiliateChips ) return;
		var affiliates = cfg.affiliates;
		if ( ! affiliates || ! affiliates.length ) {
			affiliateSec.hidden = true;
			return;
		}

		var query  = encodeURIComponent( food.name );
		var html   = '';
		var target = ( cfg.affiliates_open_new_tab !== false ) ? ' target="_blank" rel="noopener sponsored"' : '';

		affiliates.forEach( function ( aff ) {
			var url = aff.url
				.replace( /\{QUERY\}/g, query )
				.replace( /\{ID\}/g, encodeURIComponent( aff.id || '' ) );

			var iconHtml = aff.icon
				? '<span class="fcc-aff-chip__icon" style="color:' + escHtml( aff.colour ) + ';">' + aff.icon + '</span>'
				: '';

			html += '<a href="' + escHtml( url ) + '" class="fcc-aff-chip"'
				+ target
				+ ' style="--aff-colour:' + escHtml( aff.colour ) + ';"'
				+ ' data-retailer="' + escHtml( aff.key ) + '">'
				+ iconHtml
				+ '<span>' + escHtml( aff.label ) + '</span>'
				+ '<svg class="fcc-aff-chip__ext" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>'
				+ '</a>';
		} );

		affiliateChips.innerHTML = html;
		affiliateSec.hidden = false;
	}

	// -------------------------------------------------------------------------
	// Supplement lead gen — contextual suggestions
	// -------------------------------------------------------------------------
	function renderSupplementSuggestions( food ) {
		if ( ! suppSec || ! suppGrid ) return;
		var sData = cfg.supplements;
		if ( ! sData || ! sData.config ) { suppSec.hidden = true; return; }

		var matched = matchSupplements( food, sData );
		if ( ! matched.length ) { suppSec.hidden = true; return; }

		var scfg    = sData.config;
		var heading = suppSec.querySelector( '.fcc-supplement-suggestions__heading' );
		var disc    = suppSec.querySelector( '.fcc-supplement-suggestions__disclosure' );
		if ( heading ) heading.textContent = scfg.heading || 'Recommended Supplements';
		if ( disc )    disc.textContent    = scfg.disclosure || '';

		var netNames = {
			amazon_uk: 'Amazon UK', myprotein: 'MyProtein', hollbarrett: 'Holland & Barrett',
			boots: 'Boots', bulk: 'Bulk™', chemist_drct: 'Chemist Direct', custom: ''
		};

		var html = '';
		matched.forEach( function ( s ) {
			var netLabel = netNames[ s.network ] || s.network || '';
			var imgHtml  = s.image
				? '<img class="fcc-supp-card__img" src="' + escHtml( s.image ) + '" alt="' + escHtml( s.name ) + '" loading="lazy">'
				: '<div class="fcc-supp-card__img-placeholder"></div>';

			var badgeHtml = s.badge
				? '<span class="fcc-supp-card__badge">' + escHtml( s.badge ) + '</span>'
				: '';

			var priceHtml = ( scfg.show_price && s.price )
				? '<span class="fcc-supp-card__price">' + escHtml( s.price ) + '</span>'
				: '';

			var netHtml = ( scfg.show_network && netLabel )
				? '<span class="fcc-supp-card__network">via ' + escHtml( netLabel ) + '</span>'
				: '';

			if ( scfg.style === 'compact' ) {
				html += '<a class="fcc-supp-row" href="' + escHtml( s.url ) + '" target="_blank" rel="noopener sponsored"'
					+ ' data-supp-id="' + escHtml( s.id ) + '">'
					+ '<span class="fcc-supp-row__name">' + escHtml( s.name ) + '</span>'
					+ '<span class="fcc-supp-row__brand">' + escHtml( s.brand ) + '</span>'
					+ priceHtml
					+ '<span class="fcc-supp-row__cta">' + escHtml( scfg.cta ) + ' →</span>'
					+ '</a>';
			} else {
				html += '<a class="fcc-supp-card" href="' + escHtml( s.url ) + '" target="_blank" rel="noopener sponsored"'
					+ ' data-supp-id="' + escHtml( s.id ) + '">'
					+ imgHtml
					+ '<div class="fcc-supp-card__body">'
					+ '<div class="fcc-supp-card__top">'
					+ badgeHtml
					+ netHtml
					+ '</div>'
					+ '<div class="fcc-supp-card__brand">' + escHtml( s.brand ) + '</div>'
					+ '<div class="fcc-supp-card__name">' + escHtml( s.name ) + '</div>'
					+ ( s.tagline ? '<div class="fcc-supp-card__tagline">' + escHtml( s.tagline ) + '</div>' : '' )
					+ '<div class="fcc-supp-card__footer">'
					+ priceHtml
					+ '<span class="fcc-supp-card__cta">' + escHtml( scfg.cta ) + ' →</span>'
					+ '</div>'
					+ '</div>'
					+ '</a>';
			}
		} );

		suppGrid.innerHTML = html;
		suppSec.hidden = false;

		// Track impressions.
		trackSuppImpressions( matched.map( function ( s ) { return s.id; } ), sData.nonce );

		// Bind click tracking.
		suppGrid.querySelectorAll( '[data-supp-id]' ).forEach( function ( el ) {
			el.addEventListener( 'click', function () {
				trackSuppClick( el.dataset.suppId, sData.nonce );
			} );
		} );
	}

	function matchSupplements( food, sData ) {
		var matchedCats = [];

		( sData.rules || [] ).forEach( function ( rule ) {
			if ( ! rule.enabled ) return;
			var hit = false;

			if ( rule.type === 'nutrient' ) {
				var val       = parseFloat( food[ rule.field ] || 0 );
				var threshold = parseFloat( rule.value || 0 );
				if ( rule.operator === 'gte' && val >= threshold ) hit = true;
				else if ( rule.operator === 'lte' && val <= threshold ) hit = true;
				else if ( rule.operator === 'eq'  && val === threshold ) hit = true;

			} else if ( rule.type === 'keyword' ) {
				var foodName = ( food.name || '' ).toLowerCase();
				var keywords = ( rule.value || '' ).split( ',' );
				for ( var k = 0; k < keywords.length; k++ ) {
					var kw = keywords[ k ].trim().toLowerCase();
					if ( kw && foodName.includes( kw ) ) { hit = true; break; }
				}

			} else if ( rule.type === 'category' ) {
				var catName = ( food.category_name || '' ).toLowerCase();
				var kw2     = ( rule.value || '' ).toLowerCase().trim();
				if ( kw2 && catName.includes( kw2 ) ) hit = true;
			}

			if ( hit ) {
				( rule.cats || [] ).forEach( function ( c ) {
					if ( ! matchedCats.includes( c ) ) matchedCats.push( c );
				} );
			}
		} );

		if ( ! matchedCats.length ) return [];

		var catalog = ( sData.catalog || [] ).filter( function ( s ) {
			return s.url && matchedCats.includes( s.category );
		} );

		// Shuffle slightly so the same supplement doesn't always appear first.
		catalog.sort( function () { return Math.random() - 0.5; } );

		return catalog.slice( 0, sData.config.max_sugg || 2 );
	}

	function trackSuppClick( suppId, nonce ) {
		var fd = new FormData();
		fd.append( 'action', 'fcc_supp_click' );
		fd.append( 'nonce',  nonce );
		fd.append( 'id',     suppId );
		fetch( cfg.ajaxUrl, { method: 'POST', body: fd, keepalive: true } ).catch( function () {} );
	}

	function trackSuppImpressions( ids, nonce ) {
		if ( ! ids.length ) return;
		var fd = new FormData();
		fd.append( 'action', 'fcc_supp_impression' );
		fd.append( 'nonce',  nonce );
		ids.forEach( function ( id ) { fd.append( 'ids[]', id ); } );
		fetch( cfg.ajaxUrl, { method: 'POST', body: fd, keepalive: true } ).catch( function () {} );
	}

	// -------------------------------------------------------------------------
	// Nutrient rows builder
	// -------------------------------------------------------------------------
	function buildNutrientRows( food, factor, d ) {
		const show = general.showNutrients;
		const per  = function ( val ) { return val !== null && val !== undefined ? fmt( val * factor, d ) : '—'; };
		const p100 = function ( val ) { return val !== null && val !== undefined ? fmt( val, d ) : '—'; };
		const riCell = function ( val, riKey ) {
			if ( val === null || val === undefined ) return '—';
			const riVal = ri[ riKey ];
			if ( ! riVal ) return '—';
			const num    = Math.round( ( val * factor / riVal ) * 100 );
			const capped = Math.min( num, 100 );
			const color  = num > 100 ? '#dc2626' : num > 50 ? '#d97706' : 'var(--fcc-primary)';
			return '<span class="fcc-ri-cell">' +
				'<span class="fcc-ri-val">' + num + '%</span>' +
				'<span class="fcc-ri-bar"><span class="fcc-ri-bar__fill" style="width:' + capped + '%;background:' + color + '"></span></span>' +
				'</span>';
		};

		const nutrientDefs = [
			{ key: 'energy_kcal',          label: 'Energy',        unit: 'kcal', rowClass: 'fcc-row--energy', riKey: 'energy_kcal' },
			{ key: 'energy_kj',            label: 'Energy',        unit: 'kJ',   rowClass: 'fcc-row--energy fcc-row--kj', riKey: 'energy_kj' },
			{ key: 'fat_g',                label: 'Fat',           unit: 'g',    rowClass: '', riKey: 'fat_g' },
			{ key: 'of_which_saturates_g', label: '– of which Saturates', unit: 'g', rowClass: 'fcc-row--sub', riKey: 'saturates_g' },
			{ key: 'carbohydrate_g',       label: 'Carbohydrate',  unit: 'g',    rowClass: '', riKey: 'carbohydrate_g' },
			{ key: 'of_which_sugars_g',    label: '– of which Sugars', unit: 'g', rowClass: 'fcc-row--sub', riKey: 'sugars_g' },
			{ key: 'fibre_g',              label: 'Fibre',         unit: 'g',    rowClass: '', riKey: 'fibre_g' },
			{ key: 'protein_g',            label: 'Protein',       unit: 'g',    rowClass: '', riKey: 'protein_g' },
			{ key: 'salt_g',               label: 'Salt',          unit: 'g',    rowClass: '', riKey: 'salt_g' },
		];

		let html = '';
		nutrientDefs.forEach( function ( def ) {
			if ( show && show.length && ! show.includes( def.key ) ) return;
			const val = food[ def.key ];
			if ( val === null || val === undefined ) return;

			html += '<tr class="' + def.rowClass + '">' +
				'<th scope="row">' + escHtml( def.label ) + '</th>' +
				'<td class="fcc-col-per">' + per( val ) + ' ' + def.unit + '</td>' +
				'<td class="fcc-col-per100">' + p100( val ) + ' ' + def.unit + '</td>';

			if ( features.ri_display ) {
				html += '<td class="fcc-col-ri">' + riCell( val, def.riKey ) + '</td>';
			}
			html += '</tr>';
		} );

		return html;
	}

	// -------------------------------------------------------------------------
	// Health Highlights
	// -------------------------------------------------------------------------
	function renderHealthHighlights( food ) {
		if ( ! healthHighlights ) return;

		const ICON_CHECK = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
		const ICON_OMEGA = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 19c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M3 12a9 9 0 0 1 18 0"/></svg>';
		const ICON_WARN  = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';

		const positive = [];
		const warnings = [];

		if ( food.protein_g             != null && food.protein_g             >= ( ht.highProtein || 15 )    ) positive.push( { label: 'High Protein',    type: 'positive', icon: ICON_CHECK } );
		if ( food.fat_g                 != null && food.fat_g                 <= ( ht.lowFat || 3 )         ) positive.push( { label: 'Low Fat',          type: 'positive', icon: ICON_CHECK } );
		if ( food.energy_kcal           != null && food.energy_kcal           <= ( ht.lowCalorie || 100 )   ) positive.push( { label: 'Low Calorie',      type: 'positive', icon: ICON_CHECK } );
		if ( food.of_which_sugars_g     != null && food.of_which_sugars_g     <= ( ht.lowSugar || 5 )       ) positive.push( { label: 'Low Sugar',        type: 'positive', icon: ICON_CHECK } );
		if ( food.fibre_g               != null && food.fibre_g               >= ( ht.highFibre || 6 )      ) positive.push( { label: 'High Fibre',       type: 'positive', icon: ICON_CHECK } );
		if ( food.salt_g                != null && food.salt_g                <= ( ht.lowSalt || 0.3 )      ) positive.push( { label: 'Low Salt',         type: 'positive', icon: ICON_CHECK } );
		if ( food.omega3_total_mg       != null && food.omega3_total_mg       >= ( ht.omega3Rich || 500 )   ) positive.push( { label: 'Rich in Omega-3',  type: 'omega3',   icon: ICON_OMEGA } );

		if ( food.salt_g                != null && food.salt_g                >= ( ht.warnHighSalt || 1.5 )       ) warnings.push( { label: 'High in Salt',     type: 'danger',   icon: ICON_WARN  } );
		if ( food.of_which_saturates_g  != null && food.of_which_saturates_g  >= ( ht.warnHighSaturates || 5 )    ) warnings.push( { label: 'High Saturates',   type: 'warning',  icon: ICON_WARN  } );
		if ( food.of_which_sugars_g     != null && food.of_which_sugars_g     >= ( ht.warnHighSugar || 22.5 )     ) warnings.push( { label: 'High Sugar',       type: 'warning',  icon: ICON_WARN  } );

		const all = positive.slice( 0, 3 ).concat( warnings );

		if ( ! all.length ) { healthHighlights.hidden = true; return; }

		let html = '<p class="fcc-health-highlights__label">Health Highlights</p><div class="fcc-health-chips" role="list">';
		all.forEach( function ( chip ) {
			html += '<span class="fcc-health-chip fcc-health-chip--' + chip.type + '" role="listitem">' +
				chip.icon + escHtml( chip.label ) + '</span>';
		} );
		html += '</div>';

		healthHighlights.innerHTML = html;
		healthHighlights.hidden = false;
	}

	// -------------------------------------------------------------------------
	// FSA Traffic Lights
	// -------------------------------------------------------------------------
	function renderTrafficLights( food ) {
		if ( ! trafficLights ) return;

		const lights = [
			{ nutrient: 'fat',       val: food.fat_g,                low: fsa.fat_low,       high: fsa.fat_high,       label: 'g' },
			{ nutrient: 'saturates', val: food.of_which_saturates_g, low: fsa.saturates_low, high: fsa.saturates_high, label: 'g' },
			{ nutrient: 'sugars',    val: food.of_which_sugars_g,    low: fsa.sugars_low,    high: fsa.sugars_high,    label: 'g' },
			{ nutrient: 'salt',      val: food.salt_g,               low: fsa.salt_low,      high: fsa.salt_high,      label: 'g' },
		];

		lights.forEach( function ( l ) {
			const item     = trafficLights.querySelector( '[data-nutrient="' + l.nutrient + '"]' );
			if ( ! item ) return;

			const dot      = item.querySelector( '.fcc-tl-dot' );
			const valEl    = item.querySelector( '.fcc-tl-value' );
			const ratingEl = item.querySelector( '.fcc-tl-rating' );
			const val      = l.val;

			dot.className  = 'fcc-tl-dot';
			item.className = 'fcc-tl-item';

			if ( val === null || val === undefined ) {
				if ( valEl    ) valEl.textContent    = '—';
				if ( ratingEl ) ratingEl.textContent = '';
				return;
			}

			if ( valEl ) valEl.textContent = fmt( val, 1 ) + l.label + '/100g';

			if ( val <= l.low ) {
				item.classList.add( 'fcc-tl-item--green' );
				dot.classList.add( 'fcc-tl-dot--green' );
				dot.setAttribute( 'aria-label', 'Low' );
				if ( ratingEl ) ratingEl.textContent = 'Low';
			} else if ( val > l.high ) {
				item.classList.add( 'fcc-tl-item--red' );
				dot.classList.add( 'fcc-tl-dot--red' );
				dot.setAttribute( 'aria-label', 'High' );
				if ( ratingEl ) ratingEl.textContent = 'High';
			} else {
				item.classList.add( 'fcc-tl-item--amber' );
				dot.classList.add( 'fcc-tl-dot--amber' );
				dot.setAttribute( 'aria-label', 'Medium' );
				if ( ratingEl ) ratingEl.textContent = 'Medium';
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Omega-3
	// -------------------------------------------------------------------------
	function renderOmega3( food, factor, d ) {
		if ( ! omega3Sec ) return;

		const hasData = food.omega3_total_mg !== null;
		omega3Sec.hidden = ! hasData;

		if ( ! hasData ) return;

		const fields = { total: 'omega3_total_mg', ala: 'omega3_ala_mg', epa: 'omega3_epa_mg', dha: 'omega3_dha_mg' };
		Object.keys( fields ).forEach( function ( key ) {
			const row = omega3Sec.querySelector( '[data-omega3="' + key + '"]' );
			if ( ! row ) return;
			const val    = food[ fields[ key ] ];
			const valEl  = row.querySelector( '.fcc-omega3-val' );
			if ( ! valEl ) return;

			if ( val === null || val === undefined ) {
				valEl.textContent = i18n.dataNotAvailable || 'N/A';
				valEl.className = 'fcc-omega3-val fcc-data-na';
			} else {
				valEl.textContent = fmt( val * factor, d ) + ' mg';
				valEl.className = 'fcc-omega3-val';
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Caffeine
	// -------------------------------------------------------------------------
	function renderCaffeine( food, factor, d ) {
		if ( ! caffeineSec ) return;

		const hasData = food.caffeine_mg !== null;
		caffeineSec.hidden = ! hasData;

		if ( ! hasData ) return;

		const valEl = caffeineSec.querySelector( '.fcc-caffeine-value' );
		if ( valEl ) {
			valEl.textContent = fmt( food.caffeine_mg * factor, d ) + ' mg';
		}
	}

	// -------------------------------------------------------------------------
	// Meal Builder
	// -------------------------------------------------------------------------
	const addToMealOriginalHTML = addToMealBtn ? addToMealBtn.innerHTML : '';

	root.querySelectorAll( '.fcc-add-to-meal' ).forEach( function ( btn ) {
		var origHTML = btn.innerHTML;
		btn.addEventListener( 'click', function () {
			if ( ! state.food ) return;
			var grams = quantityInGrams();
			state.meal.push( {
				food:  Object.assign( {}, state.food ),
				grams: grams,
				label: state.food.name + ' (' + fmt( grams, 0 ) + 'g)',
			} );
			renderMeal();

			btn.innerHTML =
				'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Added!';
			btn.disabled = true;
			setTimeout( function () {
				btn.innerHTML = origHTML;
				btn.disabled  = false;
			}, 1500 );
		} );
	} );

	function renderMeal() {
		if ( ! mealSection ) return;
		const itemsEl    = mealSection.querySelector( '.fcc-meal-items' );
		const totalsEl   = mealSection.querySelector( '.fcc-meal-totals' );
		const totBodyEl  = mealSection.querySelector( '.fcc-meal-totals-body' );
		const macrosEl   = mealSection.querySelector( '.fcc-meal-macros' );
		const totalKcalEl = mealSection.querySelector( '.fcc-meal-total-kcal' );

		if ( ! itemsEl ) return;
		itemsEl.innerHTML = '';

		if ( ! state.meal.length ) {
			if ( mealEmptyState ) mealEmptyState.hidden = false;
			mealSection.hidden = true;
			if ( mealTabBadge ) mealTabBadge.hidden = true;
			return;
		}

		if ( mealEmptyState ) mealEmptyState.hidden = true;
		mealSection.hidden = false;
		if ( mealTabBadge ) {
			mealTabBadge.textContent = state.meal.length;
			mealTabBadge.hidden = false;
		}

		// Totals accumulator.
		const totals = { energy_kcal: 0, energy_kj: 0, protein_g: 0, carbohydrate_g: 0, fat_g: 0,
			of_which_sugars_g: 0, of_which_saturates_g: 0, fibre_g: 0, salt_g: 0,
			omega3_total_mg: 0, caffeine_mg: 0 };
		let omega3HasData = false;
		let caffeineHasData = false;

		state.meal.forEach( function ( item, i ) {
			const f      = item.food;
			const factor = item.grams / 100;

			// Accumulate.
			Object.keys( totals ).forEach( function ( k ) {
				if ( f[ k ] !== null && f[ k ] !== undefined ) {
					totals[ k ] += f[ k ] * factor;
					if ( k === 'omega3_total_mg' ) omega3HasData = true;
					if ( k === 'caffeine_mg'     ) caffeineHasData = true;
				}
			} );

			// Render item row with number badge + X icon.
			const div = document.createElement( 'div' );
			div.className = 'fcc-meal-item';
			div.setAttribute( 'role', 'listitem' );
			div.innerHTML =
				'<span class="fcc-meal-item__num">' + ( i + 1 ) + '</span>' +
				'<span class="fcc-meal-item__name">' + escHtml( item.label ) + '</span>' +
				'<span class="fcc-meal-item__kcal">' + fmt( f.energy_kcal * factor, 0 ) + ' kcal</span>' +
				'<button type="button" class="fcc-meal-item__remove" data-idx="' + i + '" aria-label="Remove ' + escHtml( f.name ) + '">' +
				'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
				'</button>';
			itemsEl.appendChild( div );
		} );

		// Update header total kcal.
		if ( totalKcalEl ) totalKcalEl.textContent = fmt( totals.energy_kcal, 0 );

		// Update macro pills.
		if ( macrosEl ) {
			const pEl = macrosEl.querySelector( '.fcc-meal-macro__val--protein' );
			const cEl = macrosEl.querySelector( '.fcc-meal-macro__val--carbs' );
			const fEl = macrosEl.querySelector( '.fcc-meal-macro__val--fat' );
			const fiEl = macrosEl.querySelector( '.fcc-meal-macro__val--fibre' );
			if ( pEl  ) pEl.textContent  = fmt( totals.protein_g, 1 ) + 'g';
			if ( cEl  ) cEl.textContent  = fmt( totals.carbohydrate_g, 1 ) + 'g';
			if ( fEl  ) fEl.textContent  = fmt( totals.fat_g, 1 ) + 'g';
			if ( fiEl ) fiEl.textContent = fmt( totals.fibre_g, 1 ) + 'g';
			macrosEl.hidden = false;
		}

		// Render totals table.
		if ( totBodyEl ) {
			let html = totalRow( 'Energy',       totals.energy_kcal, 'kcal', 0 );
			html    += totalRow( 'Energy',       totals.energy_kj,   'kJ',   0 );
			html    += totalRow( 'Fat',          totals.fat_g,       'g',    decimals );
			html    += totalRow( '– Saturates',  totals.of_which_saturates_g, 'g', decimals );
			html    += totalRow( 'Carbohydrate', totals.carbohydrate_g, 'g', decimals );
			html    += totalRow( '– Sugars',     totals.of_which_sugars_g, 'g', decimals );
			html    += totalRow( 'Fibre',        totals.fibre_g,     'g',    decimals );
			html    += totalRow( 'Protein',      totals.protein_g,   'g',    decimals );
			html    += totalRow( 'Salt',         totals.salt_g,      'g',    decimals );
			if ( omega3HasData )   html += totalRow( 'Omega-3 Total', totals.omega3_total_mg, 'mg', decimals );
			if ( caffeineHasData ) html += totalRow( 'Caffeine',      totals.caffeine_mg,     'mg', decimals );
			totBodyEl.innerHTML = html;
		}

		if ( totalsEl ) totalsEl.hidden = false;
	}

	function totalRow( label, val, unit, d ) {
		return '<tr><th scope="row">' + escHtml( label ) + '</th><td>' + fmt( val, d ) + ' ' + unit + '</td></tr>';
	}

	// Remove meal item.
	if ( mealSection ) {
		mealSection.addEventListener( 'click', function ( e ) {
			const btn = e.target.closest( '.fcc-meal-item__remove' );
			if ( ! btn ) return;
			const idx = parseInt( btn.dataset.idx, 10 );
			state.meal.splice( idx, 1 );
			renderMeal();
		} );
	}

	// -------------------------------------------------------------------------
	// BMR / TDEE (Mifflin-St Jeor)
	// -------------------------------------------------------------------------
	let bmrHeightUnit = 'cm';

	if ( bmrSection ) {
		const calcBtn = bmrSection.querySelector( '.fcc-bmr-calculate' );
		if ( calcBtn ) {
			calcBtn.addEventListener( 'click', calculateBmr );
		}

		// Activity card clicks — sync to hidden select.
		bmrSection.querySelectorAll( '.fcc-bmr-act-card' ).forEach( function ( card ) {
			card.addEventListener( 'click', function () {
				bmrSection.querySelectorAll( '.fcc-bmr-act-card' ).forEach( function ( c ) {
					c.classList.remove( 'fcc-bmr-act-card--active' );
				} );
				card.classList.add( 'fcc-bmr-act-card--active' );
				const actSel = bmrSection.querySelector( '.fcc-bmr-activity' );
				if ( actSel ) actSel.value = card.dataset.activity;
				if ( bmrSection.querySelector( '.fcc-bmr-result' ) && ! bmrSection.querySelector( '.fcc-bmr-result' ).hidden ) {
					calculateBmr();
				}
			} );
		} );

		// Goal pill clicks — sync to hidden select.
		bmrSection.querySelectorAll( '.fcc-bmr-goal-pill' ).forEach( function ( pill ) {
			pill.addEventListener( 'click', function () {
				bmrSection.querySelectorAll( '.fcc-bmr-goal-pill' ).forEach( function ( p ) {
					p.classList.remove( 'fcc-bmr-goal-pill--active' );
				} );
				pill.classList.add( 'fcc-bmr-goal-pill--active' );
				const goalSel = bmrSection.querySelector( '.fcc-bmr-goal' );
				if ( goalSel ) goalSel.value = pill.dataset.goal;
				if ( bmrSection.querySelector( '.fcc-bmr-result' ) && ! bmrSection.querySelector( '.fcc-bmr-result' ).hidden ) {
					calculateBmr();
				}
			} );
		} );

		// Re-calculate on numeric input change while result is visible.
		bmrSection.querySelectorAll( 'input[type="number"]' ).forEach( function ( el ) {
			el.addEventListener( 'change', function () {
				if ( bmrSection.querySelector( '.fcc-bmr-result' ) && ! bmrSection.querySelector( '.fcc-bmr-result' ).hidden ) {
					calculateBmr();
				}
			} );
		} );
		// Sex toggle buttons.
		bmrSection.querySelectorAll( '.fcc-bmr-sex-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				bmrSection.querySelectorAll( '.fcc-bmr-sex-btn' ).forEach( function ( b ) {
					b.classList.remove( 'fcc-bmr-sex-btn--active' );
				} );
				btn.classList.add( 'fcc-bmr-sex-btn--active' );
				const sexSel = bmrSection.querySelector( '.fcc-bmr-sex' );
				if ( sexSel ) sexSel.value = btn.dataset.sex;
				if ( bmrSection.querySelector( '.fcc-bmr-result' ) && ! bmrSection.querySelector( '.fcc-bmr-result' ).hidden ) {
					calculateBmr();
				}
			} );
		} );

		// Height unit toggle (cm ↔ in).
		const heightInput = bmrSection.querySelector( '.fcc-bmr-height' );
		bmrSection.querySelectorAll( '.fcc-bmr-height-unit-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const newUnit = btn.dataset.unit;
				if ( newUnit === bmrHeightUnit ) return;

				const val = parseFloat( heightInput ? heightInput.value : 0 );
				if ( heightInput ) {
					if ( newUnit === 'in' ) {
						heightInput.value  = val > 0 ? ( val / 2.54 ).toFixed( 1 ) : '67.0';
						heightInput.min    = '39';
						heightInput.max    = '98';
						heightInput.placeholder = '67';
					} else {
						heightInput.value  = val > 0 ? Math.round( val * 2.54 ) : '170';
						heightInput.min    = '100';
						heightInput.max    = '250';
						heightInput.placeholder = '170';
					}
				}

				bmrHeightUnit = newUnit;

				const heightUnitDisplay = bmrSection.querySelector( '.fcc-bmr-height-unit-display' );
				if ( heightUnitDisplay ) heightUnitDisplay.textContent = newUnit;

				bmrSection.querySelectorAll( '.fcc-bmr-height-unit-btn' ).forEach( function ( b ) {
					b.classList.toggle( 'fcc-bmr-height-unit-btn--active', b === btn );
				} );

				if ( bmrSection.querySelector( '.fcc-bmr-result' ) && ! bmrSection.querySelector( '.fcc-bmr-result' ).hidden ) {
					calculateBmr();
				}
			} );
		} );
	}

	function calculateBmr() {
		if ( ! bmrSection ) return;

		const sex    = bmrSection.querySelector( '.fcc-bmr-sex' ).value;
		const age    = parseFloat( bmrSection.querySelector( '.fcc-bmr-age' ).value )    || 30;
		const weight = parseFloat( bmrSection.querySelector( '.fcc-bmr-weight' ).value ) || 70;
		const heightRaw = parseFloat( bmrSection.querySelector( '.fcc-bmr-height' ).value ) || ( bmrHeightUnit === 'in' ? 67 : 170 );
		const height    = bmrHeightUnit === 'in' ? heightRaw * 2.54 : heightRaw;
		const activity = parseFloat( bmrSection.querySelector( '.fcc-bmr-activity' ).value ) || 1.55;
		const goal     = bmrSection.querySelector( '.fcc-bmr-goal' ).value;

		const formula = advanced.bmrFormula || 'mifflin';
		const goalAdj = advanced.calorieGoalAdjustment || 500;
		let bmr;
		if ( formula === 'harris_benedict' ) {
			bmr = sex === 'male'
				? 88.362 + 13.397 * weight + 4.799 * height - 5.677 * age
				: 447.593 + 9.247 * weight + 3.098 * height - 4.330 * age;
		} else if ( formula === 'katch_mcardle' ) {
			var lbm = weight * 0.8;
			bmr = 370 + 21.6 * lbm;
		} else {
			bmr = sex === 'male'
				? 10 * weight + 6.25 * height - 5 * age + 5
				: 10 * weight + 6.25 * height - 5 * age - 161;
		}

		const tdeeMaintain = Math.round( bmr * activity );
		let tdee = tdeeMaintain;
		if ( goal === 'lose' ) tdee -= goalAdj;
		if ( goal === 'gain' ) tdee += goalAdj;

		state.bmrTdee = tdee;

		const resultEl    = bmrSection.querySelector( '.fcc-bmr-result' );
		const tdeeEl      = bmrSection.querySelector( '.fcc-bmr-tdee-display' );
		const bmrValEl    = bmrSection.querySelector( '.fcc-bmr-bmr-val' );
		const loseEl      = bmrSection.querySelector( '.fcc-bmr-kcal-lose' );
		const maintainEl  = bmrSection.querySelector( '.fcc-bmr-kcal-maintain' );
		const gainEl      = bmrSection.querySelector( '.fcc-bmr-kcal-gain' );

		if ( tdeeEl )     tdeeEl.textContent     = fmt( tdee, 0 );
		if ( bmrValEl )   bmrValEl.textContent   = fmt( Math.round( bmr ), 0 );
		if ( loseEl )     loseEl.textContent     = fmt( tdeeMaintain - goalAdj, 0 );
		if ( maintainEl ) maintainEl.textContent = fmt( tdeeMaintain, 0 );
		if ( gainEl )     gainEl.textContent     = fmt( tdeeMaintain + goalAdj, 0 );

		// Highlight the active goal stat box.
		bmrSection.querySelectorAll( '.fcc-bmr-stat' ).forEach( function ( s ) {
			s.classList.remove( 'fcc-bmr-stat--selected' );
		} );
		const activeStatEl = bmrSection.querySelector( '.fcc-bmr-stat--' + goal );
		if ( activeStatEl ) activeStatEl.classList.add( 'fcc-bmr-stat--selected' );

		if ( resultEl ) resultEl.hidden = false;

		updateBmrComparison( state.food, state.food ? quantityInGrams() / 100 : 0 );
	}

	function updateBmrComparison( food, factor ) {
		const cmpEl = bmrSection ? bmrSection.querySelector( '.fcc-bmr-comparison' ) : null;
		if ( ! cmpEl || ! state.bmrTdee || ! food ) return;

		const mealKcal = food.energy_kcal * factor;
		const pct      = ( mealKcal / state.bmrTdee * 100 ).toFixed( 0 );
		cmpEl.textContent = 'This portion = ' + fmt( mealKcal, 0 ) + ' kcal (' + pct + '% of your daily target)';
	}

	// -------------------------------------------------------------------------
	// Share link
	// -------------------------------------------------------------------------
	const shareBtn = root.querySelector( '.fcc-share-btn' );
	if ( shareBtn ) {
		const shareBtnOriginalHTML = shareBtn.innerHTML;
		shareBtn.addEventListener( 'click', function () {
			const url = buildShareUrl();
			function markShareCopied() {
				shareBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
				shareBtn.disabled = true;
				setTimeout( function () {
					shareBtn.innerHTML = shareBtnOriginalHTML;
					shareBtn.disabled = false;
				}, 2200 );
			}
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( url ).then( markShareCopied );
			} else {
				window.prompt( 'Copy link:', url );
			}
		} );
	}

	function buildShareUrl() {
		if ( ! state.food ) return window.location.href;
		const url = new URL( window.location.href );
		url.searchParams.set( 'fcc_food', state.food.id );
		if ( state.food.slug ) {
			url.searchParams.set( 'fcc_name', state.food.slug );
		}
		if ( state.food.energy_kcal != null ) {
			url.searchParams.set( 'fcc_kcal', Math.round( state.food.energy_kcal ) );
		}
		if ( state.food.protein_g != null ) {
			url.searchParams.set( 'fcc_protein', Number( state.food.protein_g ).toFixed( 1 ) );
		}
		if ( state.food.omega3_total_mg != null ) {
			url.searchParams.set( 'fcc_o3',  Math.round( state.food.omega3_total_mg ) );
		}
		if ( state.food.omega3_epa_mg != null ) {
			url.searchParams.set( 'fcc_epa', Math.round( state.food.omega3_epa_mg ) );
		}
		if ( state.food.omega3_dha_mg != null ) {
			url.searchParams.set( 'fcc_dha', Math.round( state.food.omega3_dha_mg ) );
		}
		url.searchParams.set( 'fcc_qty',  state.quantity );
		url.searchParams.set( 'fcc_unit', state.unit );
		return url.toString();
	}

	function updateShareUrl() {}

	// -------------------------------------------------------------------------
	// Social share buttons (Email, WhatsApp, Facebook, X)
	// -------------------------------------------------------------------------
	function buildShareText() {
		if ( ! state.food ) return '';
		var f = state.food;
		var q = state.quantity || 100;
		var u = state.unit === 'oz' ? 'oz' : 'g';
		var factor = state.unit === 'oz' ? ( q * OZ_TO_G ) / 100 : q / 100;
		return f.name + ' — ' + fmt( ( f.energy_kcal || 0 ) * factor, 0 ) + ' kcal per ' + q + u
			+ '\nProtein ' + fmt( ( f.protein_g || 0 ) * factor, 1 ) + 'g'
			+ ' | Carbs ' + fmt( ( f.carbohydrate_g || 0 ) * factor, 1 ) + 'g'
			+ ' | Fat ' + fmt( ( f.fat_g || 0 ) * factor, 1 ) + 'g';
	}

	var shareGroup = root.querySelector( '.fcc-share-group' );
	if ( shareGroup ) {
		shareGroup.querySelectorAll( '.fcc-share-icon' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var channel = this.dataset.channel;
				var text    = buildShareText();
				var url     = buildShareUrl();
				var title   = state.food ? state.food.name + ' — Nutrition Facts' : 'Food Calorie Calculator';

				switch ( channel ) {
					case 'email':
						window.location.href = 'mailto:?subject=' + encodeURIComponent( title )
							+ '&body=' + encodeURIComponent( text + '\n\nCalculate any food:\n' + url );
						break;
					case 'whatsapp':
						window.open( 'https://wa.me/?text=' + encodeURIComponent( text + '\n\n' + url ), '_blank' );
						break;
					case 'facebook':
						window.open( 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent( url ), '_blank', 'width=600,height=400' );
						break;
					case 'x':
						window.open( 'https://x.com/intent/tweet?text=' + encodeURIComponent( text ) + '&url=' + encodeURIComponent( url ), '_blank', 'width=600,height=400' );
						break;
					case 'linkedin':
						window.open( 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent( url ), '_blank', 'width=600,height=500' );
						break;
					case 'reddit':
						window.open( 'https://www.reddit.com/submit?url=' + encodeURIComponent( url ) + '&title=' + encodeURIComponent( title ), '_blank', 'width=600,height=500' );
						break;
				}
			} );
		} );
	}

	// Load popular foods on page init.
	loadPopularFoods();

	// Restore state from URL params on page load.
	( function restoreFromUrl() {
		var p = new URLSearchParams( window.location.search );

		// Compare restore.
		var caId = p.get( 'fcc_ca' ), cbId = p.get( 'fcc_cb' );
		if ( caId && cbId ) {
			Promise.all( [
				apiFetch( '/foods/' + encodeURIComponent( caId ) ),
				apiFetch( '/foods/' + encodeURIComponent( cbId ) ),
			] ).then( function ( results ) {
				var foodA = results[ 0 ], foodB = results[ 1 ];
				selectCompareFood( 'a', foodA );
				selectCompareFood( 'b', foodB );
				var qa = parseFloat( p.get( 'fcc_qa' ) || '100' );
				var ua = p.get( 'fcc_ua' ) || 'g';
				var qb = parseFloat( p.get( 'fcc_qb' ) || '100' );
				var ub = p.get( 'fcc_ub' ) || 'g';
				state.compareQtyA = qa; state.compareUnitA = ua;
				state.compareQtyB = qb; state.compareUnitB = ub;
				if ( cSlot.a.qty ) cSlot.a.qty.value = qa;
				if ( cSlot.b.qty ) cSlot.b.qty.value = qb;
				if ( cSlot.a.unitSelect ) cSlot.a.unitSelect.value = ua;
				if ( cSlot.b.unitSelect ) cSlot.b.unitSelect.value = ub;
				updateCompareUnitTriggerText( 'a' );
				updateCompareUnitTriggerText( 'b' );
				renderComparison();
				var cBtn = root.querySelector( '.fcc-tab-btn[data-tab="compare"]' );
				if ( cBtn ) cBtn.click();
			} ).catch( function () {} );
		}

		// Single food restore.
		var foodId = p.get( 'fcc_food' );
		if ( ! foodId ) return;

		var qty  = parseFloat( p.get( 'fcc_qty' )  || '100' );
		var unit = p.get( 'fcc_unit' ) || 'g';

		apiFetch( '/foods/' + encodeURIComponent( foodId ) ).then( function ( food ) {
			// Patch omega-3 values from URL params if the DB record lacks them.
			var o3  = p.get( 'fcc_o3'  );
			var epa = p.get( 'fcc_epa' );
			var dha = p.get( 'fcc_dha' );
			if ( food.omega3_total_mg == null && o3  != null ) food.omega3_total_mg = parseFloat( o3  );
			if ( food.omega3_epa_mg   == null && epa != null ) food.omega3_epa_mg   = parseFloat( epa );
			if ( food.omega3_dha_mg   == null && dha != null ) food.omega3_dha_mg   = parseFloat( dha );

			selectFood( food );
			state.quantity = qty;
			state.unit     = unit;
			if ( qtyInput  ) qtyInput.value = qty;
			if ( unitSelect ) unitSelect.value = unit;
			updateUnitTriggerText();
			renderResults();
		} ).catch( function () {} );
	} )();

	// -------------------------------------------------------------------------
	// Print
	// -------------------------------------------------------------------------
	const printBtn = root.querySelector( '.fcc-print-btn' );
	if ( printBtn ) {
		printBtn.addEventListener( 'click', function () {
			const clone = root.cloneNode( true );
			clone.classList.add( 'fcc-print-clone' );

			// Remove non-result sections.
			[ 'fcc-tabs-nav', 'fcc-search-section', 'fcc-result-actions', 'fcc-popular-section',
			  'fcc-favourites-section', 'fcc-request-panel' ].forEach( function ( cls ) {
				var el = clone.querySelector( '.' + cls ) || clone.querySelector( '#' + cls );
				if ( el ) el.remove();
			} );
			clone.querySelectorAll( '.fcc-tab-panel' ).forEach( function ( panel ) {
				if ( panel.dataset.panel !== 'calculator' ) panel.remove();
			} );
			clone.querySelectorAll( '[hidden]' ).forEach( function ( el ) { el.removeAttribute( 'hidden' ); } );

			// Strip quantity section + food header KCAL box (branded header replaces it).
			[ '.fcc-qty-controls', '.fcc-quantity-row', '.fcc-quantity-section', '.fcc-food-header' ].forEach( function ( sel ) {
				var el = clone.querySelector( sel );
				if ( el ) el.remove();
			} );
			clone.querySelectorAll( '.fcc-add-to-meal' ).forEach( function ( b ) { b.remove(); } );

			// Remove empty sections (omega-3 with no values, caffeine with no data).
			clone.querySelectorAll( '.fcc-omega3-section, .fcc-caffeine-section' ).forEach( function ( sec ) {
				var vals = sec.querySelectorAll( '.fcc-omega3-card__val, .fcc-caffeine-val' );
				var hasData = false;
				vals.forEach( function ( v ) {
					if ( v.textContent.trim() && ! v.textContent.trim().match( /^0(\.0)?$/ ) ) hasData = true;
				} );
				if ( ! hasData ) {
					var anyNum = sec.textContent.match( /\d+\.\d+\s*mg/ );
					if ( ! anyNum ) sec.remove();
				}
			} );

			// Convert canvas donut chart to image for print.
			var origCanvas = root.querySelector( '#fcc-macro-chart' );
			var cloneCanvas = clone.querySelector( '#fcc-macro-chart' );
			if ( origCanvas && cloneCanvas ) {
				try {
					var dataUrl = origCanvas.toDataURL( 'image/png' );
					if ( dataUrl && dataUrl.length > 100 ) {
						var img = document.createElement( 'img' );
						img.src = dataUrl;
						img.style.cssText = 'width:180px;height:180px;display:block;margin:0 auto;';
						img.className = 'fcc-print-chart-img';
						cloneCanvas.parentNode.replaceChild( img, cloneCanvas );
					} else {
						var wrapper = clone.querySelector( '.fcc-macro-chart-wrapper' );
						if ( wrapper ) { var canv = wrapper.querySelector( 'canvas' ); if ( canv ) canv.remove(); }
					}
				} catch ( e ) {
					var wrapper = clone.querySelector( '.fcc-macro-chart-wrapper' );
					if ( wrapper ) { var canv = wrapper.querySelector( 'canvas' ); if ( canv ) canv.remove(); }
				}
			}

			// Add branded print header.
			var food = state.food;
			var qty  = state.quantity || 100;
			var unit = state.unit === 'oz' ? 'oz' : 'g';
			var now  = new Date().toLocaleDateString( 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' } );
			var printHeader = document.createElement( 'div' );
			printHeader.className = 'fcc-print-header';
			printHeader.innerHTML =
				'<div class="fcc-print-header__brand">'
				+ '<strong>Food Calorie Calculator</strong>'
				+ '<span>foodcaloriecalculator.co.uk</span>'
				+ '</div>'
				+ '<div class="fcc-print-header__info">'
				+ '<span class="fcc-print-header__food">' + ( food ? food.name : '' ) + ' — ' + qty + unit + '</span>'
				+ '<span class="fcc-print-header__date">' + now + '</span>'
				+ '</div>';
			clone.insertBefore( printHeader, clone.firstChild );

			// Add branded print footer.
			var printFooter = document.createElement( 'div' );
			printFooter.className = 'fcc-print-footer';
			printFooter.innerHTML = 'Generated by Food Calorie Calculator · foodcaloriecalculator.co.uk · ' + now;
			clone.appendChild( printFooter );

			document.body.appendChild( clone );

			const prevTitle = document.title;
			document.title  = ( food ? food.name + ' – ' : '' ) + 'Food Calorie Calculator';
			window.print();
			document.title  = prevTitle;

			document.body.removeChild( clone );
		} );
	}

	// -------------------------------------------------------------------------
	// Copy Nutrition Data
	// -------------------------------------------------------------------------
	const copyNutrBtn = root.querySelector( '.fcc-copy-nutrition-btn' );
	const copyNutrLbl = root.querySelector( '.fcc-copy-nutrition-label' );
	if ( copyNutrBtn ) {
		copyNutrBtn.addEventListener( 'click', function () {
			if ( ! state.food ) return;
			var f = state.food;
			var q = state.quantity || 100;
			var u = state.unit === 'oz' ? 'oz' : 'g';
			var factor = state.unit === 'oz' ? ( q * OZ_TO_G ) / 100 : q / 100;
			var text = f.name + ' — per ' + q + u + '\n'
				+ fmt( ( f.energy_kcal || 0 ) * factor, 0 ) + ' kcal'
				+ ' | Protein ' + fmt( ( f.protein_g || 0 ) * factor, 1 ) + 'g'
				+ ' | Carbs ' + fmt( ( f.carbohydrate_g || 0 ) * factor, 1 ) + 'g'
				+ ' | Fat ' + fmt( ( f.fat_g || 0 ) * factor, 1 ) + 'g'
				+ '\nFibre ' + fmt( ( f.fibre_g || 0 ) * factor, 1 ) + 'g'
				+ ' | Salt ' + fmt( ( f.salt_g || 0 ) * factor, 1 ) + 'g';
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( text ).then( function () {
					if ( copyNutrLbl ) {
						copyNutrLbl.textContent = 'Copied!';
						copyNutrBtn.classList.add( 'fcc-btn--copied' );
						setTimeout( function () {
							copyNutrLbl.textContent = 'Copy';
							copyNutrBtn.classList.remove( 'fcc-btn--copied' );
						}, 2000 );
					}
				} );
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Favourite (localStorage)
	// -------------------------------------------------------------------------
	const favBtn       = root.querySelector( '.fcc-favourite-btn' );
	const favIcon      = root.querySelector( '.fcc-favourite-icon' );
	const favLabel     = root.querySelector( '.fcc-favourite-label' );
	const favSection   = root.querySelector( '#fcc-favourites-section' );
	const favChips     = root.querySelector( '#fcc-favourite-chips' );
	const FAV_KEY      = 'fcc_favourites';
	const FAV_MAX      = 20;

	function getFavs() {
		try { return JSON.parse( localStorage.getItem( FAV_KEY ) ) || []; } catch ( e ) { return []; }
	}
	function saveFavs( arr ) { localStorage.setItem( FAV_KEY, JSON.stringify( arr.slice( 0, FAV_MAX ) ) ); }
	function isFav( id ) { return getFavs().some( function ( f ) { return f.id === id; } ); }

	function updateFavBtn() {
		if ( ! favBtn || ! state.food ) return;
		var saved = isFav( state.food.id );
		if ( favIcon ) favIcon.innerHTML = saved ? '&#9829;' : '&#9825;';
		if ( favLabel ) favLabel.textContent = saved ? 'Saved' : 'Save';
		favBtn.classList.toggle( 'fcc-favourite-btn--saved', saved );
	}

	function renderFavChips() {
		var favs = getFavs();
		if ( ! favSection || ! favChips ) return;
		if ( ! favs.length ) { favSection.hidden = true; return; }
		favSection.hidden = false;
		favChips.innerHTML = favs.map( function ( f ) {
			return '<button type="button" class="fcc-popular-chip fcc-fav-chip" data-id="' + f.id + '" data-slug="' + ( f.slug || '' ) + '">&#9829; ' + f.name + '</button>';
		} ).join( '' );
	}

	if ( favBtn ) {
		favBtn.addEventListener( 'click', function () {
			if ( ! state.food ) return;
			var favs = getFavs();
			var id   = state.food.id;
			var idx  = favs.findIndex( function ( f ) { return f.id === id; } );
			if ( idx >= 0 ) {
				favs.splice( idx, 1 );
			} else {
				favs.unshift( { id: id, name: state.food.name, slug: state.food.slug || '' } );
			}
			saveFavs( favs );
			updateFavBtn();
			renderFavChips();
		} );
	}

	if ( favChips ) {
		favChips.addEventListener( 'click', function ( e ) {
			var chip = e.target.closest( '.fcc-fav-chip' );
			if ( ! chip ) return;
			var foodId = chip.dataset.id;
			if ( foodId ) {
				apiFetch( '/foods/' + foodId ).then( function ( food ) {
					if ( food ) selectFood( food );
				} );
			}
		} );
	}

	renderFavChips();

	// -------------------------------------------------------------------------
	// Compare Shortcut
	// -------------------------------------------------------------------------
	var compareShortcutBtns = root.querySelectorAll( '.fcc-compare-shortcut-btn' );
	var _cmpOrigHTML = compareShortcutBtns.length ? compareShortcutBtns[0].innerHTML : '';

	function resetCompareBtn() {
		compareShortcutBtns.forEach( function ( b ) {
			b.innerHTML = _cmpOrigHTML;
			b.classList.remove( 'fcc-action-btn--waiting' );
		} );
	}

	function flashCompareBtn( text, ms ) {
		compareShortcutBtns.forEach( function ( b ) {
			var prev = b.innerHTML;
			b.textContent = text;
			b.disabled = true;
			setTimeout( function () { b.innerHTML = prev; b.disabled = false; }, ms || 2000 );
		} );
	}

	compareShortcutBtns.forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			if ( ! state.food ) return;

			// Both slots full → reset
			if ( state.compareA && state.compareB ) {
				state.compareA = null;
				state.compareB = null;
				resetCompareBtn();
				updateCompareDots();
			}

			// Slot A empty → fill A
			if ( ! state.compareA ) {
				try { selectCompareFood( 'a', state.food ); } catch ( e ) {}
				// Persistent "waiting for B" state
				compareShortcutBtns.forEach( function ( b ) {
					b.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Set as Slot B';
					b.classList.add( 'fcc-action-btn--waiting' );
				} );
				return;
			}

			// Slot A filled, trying to fill B — check duplicate
			if ( state.compareA.id === state.food.id ) {
				flashCompareBtn( '⚠ Same food — pick different', 2000 );
				return;
			}

			// Fill Slot B → switch to Compare tab
			try { selectCompareFood( 'b', state.food ); } catch ( e ) {}
			resetCompareBtn();

			// Pulse Slot B column in Compare tab
			var slotBCol = root.querySelector( '.fcc-compare-col[data-slot="b"]' );
			if ( slotBCol ) {
				slotBCol.classList.add( 'fcc-compare-col--pulse' );
				setTimeout( function () { slotBCol.classList.remove( 'fcc-compare-col--pulse' ); }, 3000 );
			}

			setTimeout( function () {
				var compareTab = root.querySelector( '.fcc-tab-btn[data-tab="compare"]' );
				if ( compareTab ) compareTab.click();
			}, 200 );
		} );
	} );

	// -------------------------------------------------------------------------
	// Update new buttons visibility on food selection
	// -------------------------------------------------------------------------
	var origRenderResults = null;
	function showActionButtons() {
		[ '.fcc-copy-nutrition-btn', '.fcc-favourite-btn', '.fcc-compare-shortcut-btn', '.fcc-add-to-meal--action', '.fcc-share-group' ].forEach( function ( sel ) {
			var el = root.querySelector( sel );
			if ( el ) el.hidden = ! state.food;
		} );
		updateFavBtn();
		// Restore Compare button "waiting for B" state if Slot A is filled
		if ( state.food && state.compareA && ! state.compareB ) {
			compareShortcutBtns.forEach( function ( b ) {
				b.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Set as Slot B';
				b.classList.add( 'fcc-action-btn--waiting' );
			} );
		}
	}

	var _origResultsSec = root.querySelector( '.fcc-results-section' );
	if ( _origResultsSec ) {
		new MutationObserver( function () { showActionButtons(); } ).observe( _origResultsSec, { attributes: true, attributeFilter: [ 'hidden' ] } );
	}

	// -------------------------------------------------------------------------
	// Utilities
	// -------------------------------------------------------------------------
	function fmt( n, d ) {
		if ( n === null || n === undefined || isNaN( n ) ) return '0';
		return Number( n ).toFixed( d !== undefined ? d : decimals );
	}

	function escHtml( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function setSpinner( on ) {
		if ( spinner ) spinner.classList.toggle( 'fcc-spinning', on );
		if ( searchClearBtn ) searchClearBtn.hidden = on || ! ( searchInput && searchInput.value.length );
	}

	// -------------------------------------------------------------------------
	// Tab navigation
	// -------------------------------------------------------------------------
	const tabBtns   = root.querySelectorAll( '.fcc-tab-btn' );
	const tabPanels = root.querySelectorAll( '.fcc-tab-panel' );

	if ( tabBtns.length ) {
		tabBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const target = btn.dataset.tab;
				tabBtns.forEach( function ( b ) {
					b.classList.toggle( 'fcc-tab-btn--active', b === btn );
					b.setAttribute( 'aria-selected', b === btn ? 'true' : 'false' );
				} );
				tabPanels.forEach( function ( panel ) {
					panel.hidden = panel.dataset.panel !== target;
				} );
				// Pulse Slot B when switching to Compare with only Slot A filled
				if ( target === 'compare' && state.compareA && ! state.compareB ) {
					var slotBCol = root.querySelector( '.fcc-compare-col[data-slot="b"]' );
					if ( slotBCol ) {
						slotBCol.classList.add( 'fcc-compare-col--pulse' );
						setTimeout( function () { slotBCol.classList.remove( 'fcc-compare-col--pulse' ); }, 3000 );
					}
				}
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// Custom unit dropdown
	// -------------------------------------------------------------------------
	const unitCustom      = root.querySelector( '.fcc-unit-custom' );
	const unitTriggerBtn  = root.querySelector( '.fcc-unit-trigger' );
	const unitTriggerText = root.querySelector( '.fcc-unit-trigger__text' );
	const unitOptions     = root.querySelector( '.fcc-unit-options' );

	function updateUnitTriggerText() {
		if ( ! unitTriggerText || ! unitSelect ) return;
		const sel = unitSelect.options[ unitSelect.selectedIndex ];
		if ( sel ) unitTriggerText.textContent = sel.textContent;
	}

	function syncUnitCustom() {
		if ( ! unitOptions || ! unitSelect ) return;
		unitOptions.innerHTML = '';
		Array.from( unitSelect.options ).forEach( function ( opt ) {
			const li   = document.createElement( 'li' );
			const isAct = opt.selected;
			li.className = 'fcc-unit-option' + ( isAct ? ' fcc-unit-option--active' : '' );
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', isAct ? 'true' : 'false' );
			li.dataset.value = opt.value;

			const span = document.createElement( 'span' );
			span.textContent = opt.textContent;
			li.appendChild( span );

			if ( isAct ) {
				const chk = document.createElement( 'span' );
				chk.className = 'fcc-unit-option__check';
				chk.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
				li.appendChild( chk );
			}

			li.addEventListener( 'click', function () {
				unitSelect.value = opt.value;
				unitSelect.dispatchEvent( new Event( 'change' ) );
				updateUnitTriggerText();
				closeUnitDropdown();
			} );

			unitOptions.appendChild( li );
		} );
	}

	function openUnitDropdown() {
		if ( ! unitCustom ) return;
		syncUnitCustom();
		unitCustom.classList.add( 'fcc-unit-custom--open' );
		unitCustom.setAttribute( 'aria-expanded', 'true' );
	}

	function closeUnitDropdown() {
		if ( ! unitCustom ) return;
		unitCustom.classList.remove( 'fcc-unit-custom--open' );
		unitCustom.setAttribute( 'aria-expanded', 'false' );
	}

	if ( unitTriggerBtn ) {
		unitTriggerBtn.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			if ( unitCustom.classList.contains( 'fcc-unit-custom--open' ) ) {
				closeUnitDropdown();
			} else {
				openUnitDropdown();
			}
		} );
		unitTriggerBtn.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) closeUnitDropdown();
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		if ( unitCustom && ! unitCustom.contains( e.target ) ) closeUnitDropdown();
	} );

	// -------------------------------------------------------------------------
	// Compare Tab
	// -------------------------------------------------------------------------

	// Per-slot DOM refs.
	const cSlot = {};
	[ 'a', 'b' ].forEach( function ( s ) {
		cSlot[ s ] = {
			search:         root.querySelector( '.fcc-compare-search[data-slot="' + s + '"]' ),
			dropdown:       root.querySelector( '.fcc-compare-dropdown[data-slot="' + s + '"]' ),
			selected:       root.querySelector( '.fcc-compare-selected[data-slot="' + s + '"]' ),
			foodName:       root.querySelector( '.fcc-compare-selected[data-slot="' + s + '"] .fcc-compare-food-name' ),
			qty:            root.querySelector( '.fcc-compare-qty[data-slot="' + s + '"]' ),
			unitSelect:     root.querySelector( '.fcc-compare-unit-select[data-slot="' + s + '"]' ),
			unitCustom:     root.querySelector( '.fcc-compare-unit-custom[data-slot="' + s + '"]' ),
			unitTriggerTxt: root.querySelector( '.fcc-compare-unit-custom[data-slot="' + s + '"] .fcc-unit-trigger__text' ),
			unitOptions:    root.querySelector( '.fcc-compare-unit-custom[data-slot="' + s + '"] .fcc-unit-options' ),
			unitTriggerBtn: root.querySelector( '.fcc-compare-unit-custom[data-slot="' + s + '"] .fcc-unit-trigger' ),
		};
	} );

	const compareResults  = root.querySelector( '.fcc-compare-results' );
	const compareTbody    = root.querySelector( '.fcc-compare-tbody' );
	const compareThNameA  = root.querySelector( '.fcc-compare-th__name--a' );
	const compareThNameB  = root.querySelector( '.fcc-compare-th__name--b' );
	const compareHdNameA  = root.querySelector( '.fcc-compare-results-hd__name--a' );
	const compareHdNameB  = root.querySelector( '.fcc-compare-results-hd__name--b' );
	const compareFsaA     = root.querySelector( '.fcc-compare-fsa-col[data-slot="a"]' );
	const compareFsaB     = root.querySelector( '.fcc-compare-fsa-col[data-slot="b"]' );
	const compareShareBtn = root.querySelector( '.fcc-compare-share-btn' );
	const compareSummary  = root.querySelector( '.fcc-compare-summary' );

	function showCompareDropdown( s ) {
		if ( cSlot[ s ].dropdown ) cSlot[ s ].dropdown.hidden = false;
	}
	function hideCompareDropdown( s ) {
		const dd = cSlot[ s ].dropdown;
		if ( dd ) { dd.hidden = true; dd.innerHTML = ''; }
	}

	function syncCompareUnitCustom( s ) {
		const sl = cSlot[ s ];
		if ( ! sl.unitOptions || ! sl.unitSelect ) return;
		sl.unitOptions.innerHTML = '';
		Array.from( sl.unitSelect.options ).forEach( function ( opt ) {
			const li    = document.createElement( 'li' );
			const isAct = opt.selected;
			li.className = 'fcc-unit-option' + ( isAct ? ' fcc-unit-option--active' : '' );
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', isAct ? 'true' : 'false' );
			li.dataset.value = opt.value;
			const span = document.createElement( 'span' );
			span.textContent = opt.textContent;
			li.appendChild( span );
			if ( isAct ) {
				const chk = document.createElement( 'span' );
				chk.className = 'fcc-unit-option__check';
				chk.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
				li.appendChild( chk );
			}
			li.addEventListener( 'click', function () {
				sl.unitSelect.value = opt.value;
				sl.unitSelect.dispatchEvent( new Event( 'change' ) );
				closeCompareUnitDropdown( s );
			} );
			sl.unitOptions.appendChild( li );
		} );
	}

	function openCompareUnitDropdown( s ) {
		const uc = cSlot[ s ].unitCustom;
		if ( ! uc ) return;
		syncCompareUnitCustom( s );
		uc.classList.add( 'fcc-unit-custom--open' );
		uc.setAttribute( 'aria-expanded', 'true' );
	}

	function closeCompareUnitDropdown( s ) {
		const uc = cSlot[ s ].unitCustom;
		if ( ! uc ) return;
		uc.classList.remove( 'fcc-unit-custom--open' );
		uc.setAttribute( 'aria-expanded', 'false' );
	}

	function updateCompareUnitTriggerText( s ) {
		const sl = cSlot[ s ];
		if ( ! sl.unitTriggerTxt || ! sl.unitSelect ) return;
		const sel = sl.unitSelect.options[ sl.unitSelect.selectedIndex ];
		if ( sel ) sl.unitTriggerTxt.textContent = sel.textContent;
	}

	function rebuildCompareUnitSelect( s, food ) {
		const sl = cSlot[ s ];
		if ( ! sl.unitSelect ) return;
		sl.unitSelect.innerHTML = '';

		const gOpt = document.createElement( 'option' );
		gOpt.value = 'g'; gOpt.textContent = 'grams (g)';
		sl.unitSelect.appendChild( gOpt );

		if ( general.defaultUnit === 'imperial' ) {
			const ozOpt = document.createElement( 'option' );
			ozOpt.value = 'oz'; ozOpt.textContent = 'ounces (oz)';
			sl.unitSelect.appendChild( ozOpt );
		}

		if ( food.serving_sizes && food.serving_sizes.length ) {
			food.serving_sizes.forEach( function ( sv, i ) {
				const opt = document.createElement( 'option' );
				opt.value = 'serving_' + i;
				opt.dataset.grams = sv.grams;
				const gramsTag = '(' + sv.grams + 'g)';
				opt.textContent = sv.label.includes( gramsTag ) ? sv.label : sv.label + ' ' + gramsTag;
				sl.unitSelect.appendChild( opt );
			} );
		}
		updateCompareUnitTriggerText( s );
	}

	function updateCompareDots() {
		var dotA = root.querySelector( '#fcc-dot-a' );
		var dotB = root.querySelector( '#fcc-dot-b' );
		if ( dotA ) dotA.classList.toggle( 'fcc-compare-dot--filled', !! state.compareA );
		if ( dotB ) dotB.classList.toggle( 'fcc-compare-dot--filled', !! state.compareB );
	}

	function selectCompareFood( s, food ) {
		const S = s.toUpperCase();
		state[ 'compare' + S ]     = food;
		state[ 'compareQty' + S ]  = 100;
		state[ 'compareUnit' + S ] = 'g';

		const sl = cSlot[ s ];
		if ( sl.search   ) sl.search.value          = food.name;
		if ( sl.foodName ) sl.foodName.textContent   = food.name;
		if ( sl.selected ) sl.selected.hidden        = false;
		if ( sl.qty      ) sl.qty.value              = 100;

		rebuildCompareUnitSelect( s, food );
		hideCompareDropdown( s );
		renderComparison();
		updateCompareDots();
	}

	function resolveCompareGrams( s ) {
		const S    = s.toUpperCase();
		const qty  = state[ 'compareQty'  + S ] || 100;
		const unit = state[ 'compareUnit' + S ] || 'g';

		if ( unit === 'oz' ) return qty * OZ_TO_G;
		if ( unit.startsWith( 'serving_' ) ) {
			const idx   = parseInt( unit.replace( 'serving_', '' ), 10 );
			const food  = state[ 'compare' + S ];
			const sizes = food && food.serving_sizes;
			if ( sizes && sizes[ idx ] ) return qty * sizes[ idx ].grams;
		}
		return qty;
	}

	function buildFsaHtml( food, factor ) {
		if ( ! features.fsa_traffic_lights ) return '';

		const lights = [
			{ val: food.fat_g,                low: fsa.fat_low,       high: fsa.fat_high,       label: 'Fat' },
			{ val: food.of_which_saturates_g, low: fsa.saturates_low, high: fsa.saturates_high, label: 'Saturates' },
			{ val: food.of_which_sugars_g,    low: fsa.sugars_low,    high: fsa.sugars_high,    label: 'Sugars' },
			{ val: food.salt_g,               low: fsa.salt_low,      high: fsa.salt_high,      label: 'Salt' },
		];

		var html = '<p class="fcc-tl-label">FSA Traffic Lights</p><div class="fcc-tl-row">';
		lights.forEach( function ( l ) {
			var val = l.val;
			var colorClass = '', dotClass = '', rating = '';
			if ( val !== null && val !== undefined ) {
				if ( val <= l.low ) {
					colorClass = 'fcc-tl-item--green'; dotClass = 'fcc-tl-dot--green'; rating = 'Low';
				} else if ( val > l.high ) {
					colorClass = 'fcc-tl-item--red'; dotClass = 'fcc-tl-dot--red'; rating = 'High';
				} else {
					colorClass = 'fcc-tl-item--amber'; dotClass = 'fcc-tl-dot--amber'; rating = 'Medium';
				}
			}
			var valStr = ( val !== null && val !== undefined ) ? fmt( val, 1 ) + 'g/100g' : '—';
			html += '<div class="fcc-tl-item ' + colorClass + '">' +
				'<div class="fcc-tl-dot ' + dotClass + '"></div>' +
				'<div class="fcc-tl-info">' +
				'<span class="fcc-tl-nutrient">' + escHtml( l.label ) + '</span>' +
				'<span class="fcc-tl-value">' + escHtml( valStr ) + '</span></div>' +
				( rating ? '<span class="fcc-tl-rating">' + escHtml( rating ) + '</span>' : '' ) +
				'</div>';
		} );
		html += '</div>';
		return html;
	}

	function buildCompareRows( a, b, fa, fb ) {
		var NUTRIENTS = [
			{ key: 'energy_kcal',          label: 'Energy',       unit: 'kcal', lowerBetter: true  },
			{ key: 'energy_kj',            label: 'Energy',       unit: 'kJ',   lowerBetter: true  },
			{ key: 'fat_g',                label: 'Fat',          unit: 'g',    lowerBetter: true  },
			{ key: 'of_which_saturates_g', label: '– Saturates',  unit: 'g',    lowerBetter: true  },
			{ key: 'carbohydrate_g',       label: 'Carbohydrate', unit: 'g',    lowerBetter: null  },
			{ key: 'of_which_sugars_g',    label: '– Sugars',     unit: 'g',    lowerBetter: true  },
			{ key: 'fibre_g',              label: 'Fibre',        unit: 'g',    lowerBetter: false },
			{ key: 'protein_g',            label: 'Protein',      unit: 'g',    lowerBetter: false },
			{ key: 'salt_g',               label: 'Salt',         unit: 'g',    lowerBetter: true  },
			{ key: 'omega3_total_mg',      label: 'Omega-3',      unit: 'mg',   lowerBetter: false },
			{ key: 'caffeine_mg',          label: 'Caffeine',     unit: 'mg',   lowerBetter: true  },
		];

		var html = '';
		NUTRIENTS.forEach( function ( n ) {
			var valA = a[ n.key ], valB = b[ n.key ];
			if ( ( valA === null || valA === undefined ) && ( valB === null || valB === undefined ) ) return;

			var dispA = ( valA !== null && valA !== undefined ) ? fmt( valA * fa, 1 ) + ' ' + n.unit : '—';
			var dispB = ( valB !== null && valB !== undefined ) ? fmt( valB * fb, 1 ) + ' ' + n.unit : '—';

			var clsA = '', clsB = '';
			if ( n.lowerBetter !== null && valA !== null && valA !== undefined && valB !== null && valB !== undefined ) {
				var numA = valA * fa, numB = valB * fb;
				if ( Math.abs( numA - numB ) > 0.001 ) {
					var aWins = n.lowerBetter ? numA < numB : numA > numB;
					if ( aWins ) { clsA = ' class="fcc-compare-winner--a"'; }
					else         { clsB = ' class="fcc-compare-winner--b"'; }
				}
			}

			var isSub = n.label.charAt( 0 ) === '–';
			var thCls = isSub ? ' class="fcc-compare-sub"' : '';
			html += '<tr><th scope="row"' + thCls + '>' + escHtml( n.label ) + '</th>' +
				'<td' + clsA + '>' + dispA + '</td>' +
				'<td' + clsB + '>' + dispB + '</td></tr>';
		} );
		return html;
	}

	function buildCompareSummary( a, b, fa, fb ) {
		var SCORE = [
			{ key: 'energy_kcal', lowerBetter: true  },
			{ key: 'fat_g',       lowerBetter: true  },
			{ key: 'protein_g',   lowerBetter: false },
			{ key: 'fibre_g',     lowerBetter: false },
			{ key: 'salt_g',      lowerBetter: true  },
		];
		var winsA = 0, winsB = 0;
		SCORE.forEach( function ( n ) {
			var vA = a[ n.key ], vB = b[ n.key ];
			if ( vA == null || vB == null ) return;
			var numA = vA * fa, numB = vB * fb;
			if ( Math.abs( numA - numB ) < 0.001 ) return;
			if ( n.lowerBetter ? numA < numB : numA > numB ) { winsA++; } else { winsB++; }
		} );
		if ( ! winsA && ! winsB ) return '';

		var total = winsA + winsB;
		var pctA  = Math.round( winsA / total * 100 );
		var label = winsA > winsB
			? escHtml( a.name ) + ' wins <strong>' + winsA + '</strong> of ' + total + ' categories'
			: winsB > winsA
			? escHtml( b.name ) + ' wins <strong>' + winsB + '</strong> of ' + total + ' categories'
			: 'Tied — ' + winsA + ' categories each';

		return '<div class="fcc-compare-summary-inner">' +
			'<div class="fcc-compare-summary-labels">' +
			'<span class="fcc-compare-badge fcc-compare-badge--a">A</span>' +
			'<span class="fcc-compare-summary-text">' + label + '</span>' +
			'<span class="fcc-compare-badge fcc-compare-badge--b">B</span>' +
			'</div>' +
			'<div class="fcc-compare-summary-bar">' +
			'<div class="fcc-compare-summary-bar__fill fcc-compare-summary-bar__fill--a" style="width:' + pctA + '%"></div>' +
			'<div class="fcc-compare-summary-bar__fill fcc-compare-summary-bar__fill--b" style="width:' + ( 100 - pctA ) + '%"></div>' +
			'</div></div>';
	}

	function renderComparison() {
		if ( ! compareResults ) return;
		var a = state.compareA, b = state.compareB;
		if ( ! a || ! b ) { compareResults.hidden = true; return; }

		var fa = resolveCompareGrams( 'a' ) / 100;
		var fb = resolveCompareGrams( 'b' ) / 100;

		if ( compareHdNameA ) compareHdNameA.textContent = a.name;
		if ( compareHdNameB ) compareHdNameB.textContent = b.name;
		if ( compareThNameA ) compareThNameA.textContent = a.name;
		if ( compareThNameB ) compareThNameB.textContent = b.name;
		if ( compareFsaA )    compareFsaA.innerHTML      = buildFsaHtml( a, fa );
		if ( compareFsaB )    compareFsaB.innerHTML      = buildFsaHtml( b, fb );
		if ( compareTbody )   compareTbody.innerHTML     = buildCompareRows( a, b, fa, fb );
		if ( compareSummary ) compareSummary.innerHTML   = buildCompareSummary( a, b, fa, fb );

		compareResults.hidden = false;
	}

	function buildCompareShareUrl() {
		if ( ! state.compareA || ! state.compareB ) return window.location.href;
		var url = new URL( window.location.href );
		url.searchParams.set( 'fcc_ca', state.compareA.id );
		if ( state.compareA.slug ) url.searchParams.set( 'fcc_na', state.compareA.slug );
		url.searchParams.set( 'fcc_cb', state.compareB.id );
		if ( state.compareB.slug ) url.searchParams.set( 'fcc_nb', state.compareB.slug );
		url.searchParams.set( 'fcc_qa', state.compareQtyA );
		url.searchParams.set( 'fcc_ua', state.compareUnitA );
		url.searchParams.set( 'fcc_qb', state.compareQtyB );
		url.searchParams.set( 'fcc_ub', state.compareUnitB );
		return url.toString();
	}

	if ( compareShareBtn ) {
		compareShareBtn.addEventListener( 'click', function () {
			var btn = this;
			var url = buildCompareShareUrl();
			var originalHTML = btn.innerHTML;

			function markCopied() {
				btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
				btn.classList.add( 'fcc-btn--copied' );
				btn.disabled = true;
				setTimeout( function () {
					btn.innerHTML = originalHTML;
					btn.classList.remove( 'fcc-btn--copied' );
					btn.disabled = false;
				}, 2200 );
			}

			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( url ).then( markCopied ).catch( function () {
					window.prompt( 'Copy link:', url );
				} );
			} else {
				window.prompt( 'Copy link:', url );
			}
		} );
	}

	var compareResetBtn = root.querySelector( '.fcc-compare-reset-btn' );
	if ( compareResetBtn ) {
		compareResetBtn.addEventListener( 'click', function () {
			[ 'a', 'b' ].forEach( function ( s ) {
				var S  = s.toUpperCase();
				var sl = cSlot[ s ];
				state[ 'compare' + S ]     = null;
				state[ 'compareQty' + S ]  = 100;
				state[ 'compareUnit' + S ] = 'g';
				if ( sl.search   ) sl.search.value   = '';
				if ( sl.selected ) sl.selected.hidden = true;
				if ( sl.qty      ) sl.qty.value       = 100;
				if ( sl.unitSelect ) {
					sl.unitSelect.innerHTML = '<option value="g">grams (g)</option>';
				}
				updateCompareUnitTriggerText( s );
				hideCompareDropdown( s );
			} );
			if ( compareResults ) compareResults.hidden = true;
			showToast( 'Comparison reset' );
		} );
	}

	function renderCompareDropdown( s, foods ) {
		var sl = cSlot[ s ];
		if ( ! sl.dropdown ) return;
		sl.dropdown.innerHTML = '';
		if ( ! foods.length ) {
			var noLi = document.createElement( 'li' );
			noLi.className = 'fcc-no-results';
			noLi.textContent = i18n.noResults || 'No foods found';
			sl.dropdown.appendChild( noLi );
			showCompareDropdown( s );
			return;
		}
		var otherSlotKey = s === 'a' ? 'compareB' : 'compareA';
		var otherFood    = state[ otherSlotKey ];
		foods.forEach( function ( food ) {
			var isDuplicate = otherFood && otherFood.id === food.id;
			var li = document.createElement( 'li' );
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', 'false' );
			li.dataset.id = food.id;
			if ( isDuplicate ) {
				li.className = 'fcc-compare-result--disabled';
				li.innerHTML =
					'<span class="fcc-result-name">' + escHtml( food.name ) + '</span>' +
					'<span class="fcc-result-kcal" style="color:#999">Already in Slot ' + ( s === 'a' ? 'B' : 'A' ) + '</span>';
			} else {
				li.innerHTML =
					'<span class="fcc-result-name">' + escHtml( food.name ) + '</span>' +
					'<span class="fcc-result-kcal">' + fmt( food.energy_kcal ) + ' kcal</span>';
				li.addEventListener( 'click', function () { selectCompareFood( s, food ); } );
			}
			sl.dropdown.appendChild( li );
		} );
		showCompareDropdown( s );
	}

	function initCompareSlot( s ) {
		var sl = cSlot[ s ];
		if ( ! sl.search ) return;

		var cTimer = null;

		sl.search.addEventListener( 'input', function () {
			clearTimeout( cTimer );
			var q = this.value.trim();
			if ( q.length < ( advanced.searchMinChars || 2 ) ) { hideCompareDropdown( s ); return; }
			cTimer = setTimeout( function () {
				var cat = Number( general.defaultCategory || 0 );
				var url = '/foods/search?q=' + encodeURIComponent( q ) + '&limit=' + ( general.searchResultLimit || 10 );
				if ( cat > 0 ) url += '&category=' + cat;
				apiFetch( url )
					.then( function ( foods ) { renderCompareDropdown( s, foods ); } )
					.catch( function () { renderCompareDropdown( s, [] ); } );
			}, general.searchDebounce || 280 );
		} );

		sl.search.addEventListener( 'keydown', function ( e ) {
			if ( ! sl.dropdown || sl.dropdown.hidden ) return;
			var items   = Array.from( sl.dropdown.querySelectorAll( '[role="option"]' ) );
			var current = sl.dropdown.querySelector( '[aria-selected="true"]' );
			var idx     = items.indexOf( current );
			if ( e.key === 'ArrowDown' ) { e.preventDefault(); idx = ( idx + 1 ) % items.length; }
			else if ( e.key === 'ArrowUp' ) { e.preventDefault(); idx = ( idx - 1 + items.length ) % items.length; }
			else if ( e.key === 'Enter' && current ) { e.preventDefault(); current.click(); return; }
			else if ( e.key === 'Escape' ) { hideCompareDropdown( s ); return; }
			else return;
			items.forEach( function ( el ) { el.setAttribute( 'aria-selected', 'false' ); } );
			if ( items[ idx ] ) { items[ idx ].setAttribute( 'aria-selected', 'true' ); items[ idx ].scrollIntoView( { block: 'nearest' } ); }
		} );

		if ( sl.unitSelect ) {
			sl.unitSelect.addEventListener( 'change', function () {
				var S = s.toUpperCase();
				state[ 'compareUnit' + S ] = this.value;
				if ( this.value.startsWith( 'serving_' ) ) {
					state[ 'compareQty' + S ] = 1;
					if ( sl.qty ) { sl.qty.value = 1; sl.qty.step = '1'; }
				} else if ( this.value === 'oz' ) {
					if ( sl.qty ) sl.qty.step = '0.1';
				} else {
					if ( sl.qty ) sl.qty.step = '1';
				}
				updateCompareUnitTriggerText( s );
				renderComparison();
			} );
		}

		if ( sl.qty ) {
			sl.qty.addEventListener( 'input', function () {
				state[ 'compareQty' + s.toUpperCase() ] = Math.max( 0, parseFloat( this.value ) || 0 );
				renderComparison();
			} );
		}

		if ( sl.unitTriggerBtn ) {
			sl.unitTriggerBtn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				if ( sl.unitCustom && sl.unitCustom.classList.contains( 'fcc-unit-custom--open' ) ) {
					closeCompareUnitDropdown( s );
				} else {
					openCompareUnitDropdown( s );
				}
			} );
			sl.unitTriggerBtn.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Escape' ) closeCompareUnitDropdown( s );
			} );
		}

		document.addEventListener( 'click', function ( e ) {
			if ( sl.unitCustom && ! sl.unitCustom.contains( e.target ) ) closeCompareUnitDropdown( s );
			if ( sl.dropdown && ! sl.dropdown.contains( e.target ) &&
				sl.search && ! sl.search.contains( e.target ) ) {
				hideCompareDropdown( s );
			}
		} );
	}

	initCompareSlot( 'a' );
	initCompareSlot( 'b' );

	// -------------------------------------------------------------------------
	// White Label — logo injection + powered-by visibility
	// -------------------------------------------------------------------------
	( function initWhiteLabel() {
		var wl = cfg.wl;
		if ( ! wl ) return;

		// Apply hide-powered-by class to the wrapper.
		if ( wl.hidePoweredBy ) {
			var wrapper = document.querySelector( '.fcc-calculator-wrapper' ) || root;
			if ( wrapper ) wrapper.classList.add( 'fcc-wl-hide-powered-by' );
		}

		// Inject logo above the search input (Growth+ tier).
		if ( wl.logoUrl ) {
			var searchSection = root.querySelector( '.fcc-search-section' );
			if ( searchSection && ! root.querySelector( '.fcc-wl-logo' ) ) {
				var img = document.createElement( 'img' );
				img.src       = wl.logoUrl;
				img.alt       = wl.brandName || 'Brand logo';
				img.className = 'fcc-wl-logo';
				searchSection.insertBefore( img, searchSection.firstChild );
			}
		}
	} )();

	function showToast( msg ) {
		let toast = document.getElementById( 'fcc-toast' );
		if ( ! toast ) {
			toast = document.createElement( 'div' );
			toast.id = 'fcc-toast';
			toast.setAttribute( 'role', 'status' );
			toast.setAttribute( 'aria-live', 'polite' );
			toast.style.cssText = 'position:fixed;bottom:2rem;right:2rem;background:#1d2327;color:#fff;padding:.6rem 1.2rem;border-radius:8px;font-size:.875rem;z-index:99999;opacity:0;transition:opacity .3s;pointer-events:none;';
			document.body.appendChild( toast );
		}
		toast.textContent = msg;
		toast.style.opacity = '1';
		setTimeout( function () { toast.style.opacity = '0'; }, 2500 );
	}

} )();
