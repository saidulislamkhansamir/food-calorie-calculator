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
	const macroRings    = features.macro_chart  ? root.querySelector( '.fcc-macro-rings' ) : null;
	const macroWrapper  = features.macro_chart  ? root.querySelector( '.fcc-macro-chart-wrapper' ) : null;
	const omega3Sec     = features.omega3_display  ? root.querySelector( '.fcc-omega3-section' )  : null;
	const caffeineSec   = features.caffeine_display ? root.querySelector( '.fcc-caffeine-section' ) : null;
	const microSec      = root.querySelector( '.fcc-micronutrients-section' );
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

		searchInput.addEventListener( 'focus', function () {
			if ( this.value.trim().length > 0 ) return;
			var curatedTrending = ( ( fccData.promotions || {} ).trendingFoods || [] );
			if ( ! curatedTrending.length ) return;
			dropdown.innerHTML = '';
			var hdr = document.createElement( 'li' );
			hdr.className = 'fcc-trending-header';
			hdr.textContent = 'Trending Now 🔥';
			dropdown.appendChild( hdr );
			curatedTrending.forEach( function ( f ) {
				var li = document.createElement( 'li' );
				li.setAttribute( 'role', 'option' );
				li.setAttribute( 'aria-selected', 'false' );
				li.dataset.id = f.id;
				li.innerHTML =
					'<span class="fcc-result-name">' + escHtml( f.name ) + '</span>' +
					'<span class="fcc-result-kcal">' + fmt( f.energy_kcal ) + ' kcal</span>';
				li.addEventListener( 'click', function () {
					hideDropdown();
					searchInput.value = f.name;
					updateClearBtn();
					hidePopular();
					apiFetch( '/foods/' + f.id ).then( function ( fullFood ) {
						selectFood( fullFood );
					} );
				} );
				dropdown.appendChild( li );
			} );
			showDropdown();
		} );

		searchInput.addEventListener( 'keydown', handleDropdownKeys );
		document.addEventListener( 'click', function ( e ) {
			if ( ! root.contains( e.target ) ) hideDropdown();
		} );
	}

	// -------------------------------------------------------------------------
	// Voice Search (Web Speech API)
	// -------------------------------------------------------------------------
	var VoiceRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
	var voiceBtn = root.querySelector( '#fcc-voice-btn' );
	if ( VoiceRecognition && voiceBtn && features.voice_search !== false ) {
		// Apply settings BEFORE showing button to prevent flash.
		var vIcon = appearance.voiceIcon || 'svg';
		if ( vIcon === 'emoji' ) vIcon = 'svg';
		var vColour = appearance.voiceColour || '#075B5E';
		var vSize = appearance.voiceSize || 'medium';
		var sizes = { small: '30px', medium: '38px', large: '46px' };
		var iconEl = voiceBtn.querySelector( '.fcc-voice-icon' );
		if ( iconEl ) {
			if ( vIcon === 'svg' ) iconEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1"/><line x1="12" y1="18" x2="12" y2="22"/></svg>';
			else if ( vIcon === 'text' ) { iconEl.textContent = 'Mic'; iconEl.style.cssText = 'font-size:11px;font-weight:700;letter-spacing:.03em;color:#fff;'; }
		}
		voiceBtn.style.background = vColour;
		voiceBtn.style.width = sizes[ vSize ] || '38px';
		voiceBtn.style.height = sizes[ vSize ] || '38px';
		voiceBtn.hidden = false;

		voiceBtn.addEventListener( 'click', function () {
			var rec = new VoiceRecognition();
			rec.lang = 'en-GB';
			rec.interimResults = false;
			rec.maxAlternatives = 1;

			voiceBtn.classList.add( 'fcc-voice-btn--active' );

			rec.onresult = function ( e ) {
				var text = e.results[0][0].transcript.trim();
				if ( text && searchInput ) {
					searchInput.value = text;
					updateClearBtn();
					hidePopular();
					doSearch( text );
				}
				voiceBtn.classList.remove( 'fcc-voice-btn--active' );
			};
			rec.onerror = function () { voiceBtn.classList.remove( 'fcc-voice-btn--active' ); };
			rec.onend   = function () { voiceBtn.classList.remove( 'fcc-voice-btn--active' ); };

			rec.start();
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
		var promos = ( fccData.promotions || {} );
		var badges = promos.badges || {};
		foods.forEach( function ( food ) {
			const li = document.createElement( 'li' );
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', 'false' );
			li.dataset.id = food.id;
			const isActive = food.is_sponsored && food.sponsor_active;
			if ( isActive ) li.classList.add( 'fcc-result--sponsored' );
			var badgeText = badges[ food.id ] || '';
			li.innerHTML =
				'<span class="fcc-result-name">' + escHtml( food.name ) +
				( badgeText ? '<span class="fcc-promo-badge">' + escHtml( badgeText ) + '</span>' : '' ) +
				'</span>' +
				'<span class="fcc-result-kcal">' + fmt( food.energy_kcal ) + ' kcal</span>' +
				( isActive && ! badgeText ? '<span class="fcc-sponsored-pill">Sponsored</span>' : '' );
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
		renderPromoBanner( food );
	}

	function renderPromoBanner( food ) {
		var existing = root.querySelector( '.fcc-promo-banner' );
		if ( existing ) existing.remove();
		var banners = ( ( fccData.promotions || {} ).banners || {} );
		var b = banners[ food.id ];
		if ( ! b || ! b.message ) return;
		var banner = document.createElement( 'div' );
		banner.className = 'fcc-promo-banner';
		var html = '<span class="fcc-promo-banner__msg">' + escHtml( b.message ) + '</span>';
		if ( b.link_url && b.link_text ) {
			html += '<a class="fcc-promo-banner__cta" href="' + escHtml( b.link_url ) + '" target="_blank" rel="noopener">' + escHtml( b.link_text ) + '</a>';
		}
		banner.innerHTML = html;
		var resultsSection = root.querySelector( '.fcc-results-section' );
		if ( resultsSection ) resultsSection.parentNode.insertBefore( banner, resultsSection.nextSibling );
	}

	function rebuildUnitSelect( food ) {
		if ( ! unitSelect ) return;
		// Clear all options first.
		unitSelect.innerHTML = '';

		const gOpt = document.createElement( 'option' );
		gOpt.value = 'g'; gOpt.textContent = 'grams (g)';
		unitSelect.appendChild( gOpt );

		const kgOpt = document.createElement( 'option' );
		kgOpt.value = 'kg'; kgOpt.textContent = 'kilograms (kg)';
		unitSelect.appendChild( kgOpt );

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
			} else if ( state.unit === 'oz' || state.unit === 'kg' ) {
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
		if ( state.unit === 'kg' ) return q * 1000;
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
			if ( state.unit === 'kg' ) desc = fmt( state.quantity, 2 ) + ' kg (' + fmt( grams, 0 ) + 'g)';
			else if ( state.unit === 'oz' ) desc = fmt( state.quantity, 1 ) + ' oz (' + fmt( grams, 1 ) + 'g)';
			servingDesc.textContent = desc;
		}

		// Update kcal badge in food header.
		const kcalBadge = root.querySelector( '.fcc-food-kcal-num' );
		if ( kcalBadge ) kcalBadge.textContent = fmt( ( food.energy_kcal || 0 ) * factor, 0 );

		// Build nutrient rows.
		const rows = buildNutrientRows( food, factor, d );
		if ( nutrientsBody ) nutrientsBody.innerHTML = rows;

		// Hide Per 100g column when serving IS 100g (identical values).
		var isExact100g = Math.abs( grams - 100 ) < 0.05;
		root.querySelectorAll( '.fcc-col-per100' ).forEach( function ( el ) {
			el.style.display = isExact100g ? 'none' : '';
		} );

		// Health highlights.
		renderHealthHighlights( food );
		renderAllergenDietBadges( food );

		// FSA traffic lights.
		if ( trafficLights && features.fsa_traffic_lights ) {
			renderTrafficLights( food );
			trafficLights.hidden = false;
		}

		// Macro breakdown — SVG rings + bars + stats.
		if ( macroWrapper && features.macro_chart ) {
			const protein_kcal = ( food.protein_g || 0 ) * factor * 4;
			const carbs_kcal   = ( food.carbohydrate_g || 0 ) * factor * 4;
			const fat_kcal     = ( food.fat_g || 0 ) * factor * 9;
			var chartColors = {
				protein: appearance.chartProteinColour || '#3b82f6',
				carbs:   appearance.chartCarbsColour   || '#f59e0b',
				fat:     appearance.chartFatColour     || '#ef4444',
			};
			var pG = fmt( ( food.protein_g || 0 ) * factor, d );
			var cG = fmt( ( food.carbohydrate_g || 0 ) * factor, d );
			var fG = fmt( ( food.fat_g || 0 ) * factor, d );
			var totalKcal = Math.round( protein_kcal + carbs_kcal + fat_kcal );
			var pPct = totalKcal > 0 ? Math.round( protein_kcal / totalKcal * 100 ) : 0;
			var cPct = totalKcal > 0 ? Math.round( carbs_kcal / totalKcal * 100 ) : 0;
			var fPct = totalKcal > 0 ? Math.round( fat_kcal / totalKcal * 100 ) : 0;
			var cPerG = totalKcal > 0 && grams > 0 ? fmt( totalKcal / grams, 1 ) : '—';

			if ( macroRings ) {
				macroRings.innerHTML =
					buildMacroRing( 'Protein', pG, pPct, chartColors.protein )
					+ buildMacroRing( 'Carbs', cG, cPct, chartColors.carbs )
					+ buildMacroRing( 'Fat', fG, fPct, chartColors.fat );
			}

			var macroBars = macroWrapper.querySelector( '.fcc-macro-bars' );
			if ( macroBars ) {
				macroBars.innerHTML =
					buildMacroBar( 'Protein', pG, pPct, Math.round( protein_kcal ), chartColors.protein )
					+ buildMacroBar( 'Carbs', cG, cPct, Math.round( carbs_kcal ), chartColors.carbs )
					+ buildMacroBar( 'Fat', fG, fPct, Math.round( fat_kcal ), chartColors.fat );
			}

			var macroDetail = macroWrapper.querySelector( '.fcc-macro-detail' );
			if ( macroDetail ) {
				macroDetail.innerHTML =
					'<div class="fcc-macro-detail__grid">'
					+  '<div class="fcc-macro-detail__stat"><span class="fcc-macro-detail__stat-val">' + totalKcal + '</span><span class="fcc-macro-detail__stat-lbl">Total kcal</span></div>'
					+  '<div class="fcc-macro-detail__stat"><span class="fcc-macro-detail__stat-val">' + cPerG + '</span><span class="fcc-macro-detail__stat-lbl">kcal/g</span></div>'
					+  '<div class="fcc-macro-detail__stat"><span class="fcc-macro-detail__stat-val">' + pPct + '%</span><span class="fcc-macro-detail__stat-lbl">Protein</span></div>'
					+  '<div class="fcc-macro-detail__stat"><span class="fcc-macro-detail__stat-val">' + fPct + '%</span><span class="fcc-macro-detail__stat-lbl">Fat</span></div>'
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

		// Micronutrients.
		renderMicronutrients( food, factor, d );

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
	function buildMacroRing( label, grams, pct, color ) {
		var r = 34, c = 2 * Math.PI * r;
		var offset = c - ( pct / 100 ) * c;
		return '<div class="fcc-macro-ring-wrap">'
			+ '<svg class="fcc-macro-ring" width="90" height="90" viewBox="0 0 90 90" xmlns="http://www.w3.org/2000/svg">'
			+   '<circle cx="45" cy="45" r="' + r + '" fill="none" stroke="#eee" stroke-width="7"/>'
			+   '<circle cx="45" cy="45" r="' + r + '" fill="none" stroke="' + color + '" stroke-width="7"'
			+     ' stroke-dasharray="' + c.toFixed(1) + '" stroke-dashoffset="' + offset.toFixed(1) + '"'
			+     ' stroke-linecap="round" transform="rotate(-90 45 45)"/>'
			+   '<text x="45" y="42" text-anchor="middle" font-size="15" font-weight="800" fill="#1a2e2f">' + grams + 'g</text>'
			+   '<text x="45" y="56" text-anchor="middle" font-size="11" font-weight="600" fill="' + color + '">' + pct + '%</text>'
			+ '</svg>'
			+ '<span class="fcc-macro-ring-label">' + label + '</span>'
			+ '</div>';
	}

	function buildMacroBar( label, grams, pct, kcal, color ) {
		return '<div class="fcc-macro-bar">'
			+ '<div class="fcc-macro-bar__header">'
			+   '<span class="fcc-macro-bar__dot" style="background:' + color + '"></span>'
			+   '<span class="fcc-macro-bar__label">' + label + '</span>'
			+   '<span class="fcc-macro-bar__val">' + grams + 'g</span>'
			+   '<span class="fcc-macro-bar__kcal">' + kcal + ' kcal</span>'
			+   '<span class="fcc-macro-bar__pct">' + pct + '%</span>'
			+ '</div>'
			+ '<div class="fcc-macro-bar__track">'
			+   '<div class="fcc-macro-bar__fill" style="width:' + Math.max( pct, 2 ) + '%;background:' + color + '"></div>'
			+ '</div>'
			+ '</div>';
	}

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
	// Allergen & Dietary Badges
	// -------------------------------------------------------------------------
	var allergenEl = root.querySelector( '.fcc-allergen-badges' );
	var dietEl     = root.querySelector( '.fcc-diet-badges' );

	function renderAllergenDietBadges( food ) {
		// Allergens
		if ( allergenEl ) {
			var allergens = [
				{ key: 'allergen_fish',      label: '🐟 Fish' },
				{ key: 'allergen_shellfish',  label: '🦐 Shellfish' },
				{ key: 'allergen_dairy',      label: '🥛 Dairy' },
				{ key: 'allergen_eggs',       label: '🥚 Eggs' },
				{ key: 'allergen_nuts',       label: '🥜 Tree Nuts' },
				{ key: 'allergen_gluten',     label: '🌾 Gluten' },
				{ key: 'allergen_soy',        label: '🫘 Soy' },
				{ key: 'allergen_celery',     label: '🥬 Celery' },
			];
			var html = '';
			allergens.forEach( function ( a ) {
				if ( food[ a.key ] === 1 || food[ a.key ] === '1' ) {
					html += '<span class="fcc-allergen-badge fcc-allergen-badge--contains">Contains ' + a.label + '</span>';
				} else if ( food[ a.key ] === 0 || food[ a.key ] === '0' ) {
					html += '<span class="fcc-allergen-badge fcc-allergen-badge--free">✅ ' + a.label.split(' ')[1] + '-Free</span>';
				}
			} );
			if ( html ) {
				allergenEl.innerHTML = '<span class="fcc-badge-label">Allergens</span><div class="fcc-badge-pills">' + html + '</div>';
				allergenEl.hidden = false;
			} else {
				allergenEl.hidden = true;
			}
		}

		// Dietary tags
		if ( dietEl ) {
			var diets = [
				{ key: 'diet_keto',       label: '🥑 Keto-Friendly' },
				{ key: 'diet_paleo',      label: '🍖 Paleo' },
				{ key: 'diet_halal',      label: '☪️ Halal' },
				{ key: 'diet_kosher',     label: '✡️ Kosher' },
				{ key: 'diet_vegan',      label: '🌱 Vegan' },
				{ key: 'diet_vegetarian', label: '🥬 Vegetarian' },
			];
			var dhtml = '';
			diets.forEach( function ( d ) {
				if ( food[ d.key ] === 1 || food[ d.key ] === '1' ) {
					dhtml += '<span class="fcc-diet-badge">' + d.label + '</span>';
				}
			} );
			if ( dhtml ) {
				dietEl.innerHTML = '<span class="fcc-badge-label">Dietary</span><div class="fcc-badge-pills">' + dhtml + '</div>';
				dietEl.hidden = false;
			} else {
				dietEl.hidden = true;
			}
		}
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
	// Micronutrients (Iron, Calcium, Vitamin C)
	// -------------------------------------------------------------------------
	function renderMicronutrients( food, factor, d ) {
		if ( ! microSec ) return;

		var fields = {
			iron:      { key: 'iron_mg',      unit: 'mg' },
			calcium:   { key: 'calcium_mg',   unit: 'mg' },
			vitamin_c: { key: 'vitamin_c_mg', unit: 'mg' },
		};

		var hasAny = false;
		for ( var mk in fields ) {
			if ( food[ fields[ mk ].key ] !== null && food[ fields[ mk ].key ] !== undefined ) { hasAny = true; break; }
		}
		microSec.hidden = ! hasAny;
		if ( ! hasAny ) return;

		for ( var mk2 in fields ) {
			var card = microSec.querySelector( '[data-micro="' + mk2 + '"]' );
			if ( ! card ) continue;
			var valEl = card.querySelector( '.fcc-micro-val' );
			var f2 = fields[ mk2 ];
			if ( food[ f2.key ] === null || food[ f2.key ] === undefined ) {
				card.style.display = 'none';
			} else {
				card.style.display = '';
				if ( valEl ) valEl.textContent = fmt( food[ f2.key ] * factor, d ) + ' ' + f2.unit;
			}
		}
	}

	// -------------------------------------------------------------------------
	// Meal Builder
	// -------------------------------------------------------------------------
	const addToMealOriginalHTML = addToMealBtn ? addToMealBtn.innerHTML : '';

	// ── Meal Category State ──
	var mealCatPills = root.querySelectorAll( '.fcc-meal-cat-pill' );
	var currentMealCat = 'breakfast';
	var mealCatCfg = ( advanced && advanced.mealCategories ) ? advanced.mealCategories : {};

	// Hide category pills if feature disabled.
	var catPillsContainer = root.querySelector( '#fcc-meal-cat-pills' );
	if ( features.meal_categories === false && catPillsContainer ) catPillsContainer.hidden = true;

	// Apply custom labels/emojis from settings to pills.
	mealCatPills.forEach( function ( pill ) {
		var cat = pill.dataset.cat;
		var cfg = mealCatCfg[ cat ];
		if ( cfg ) pill.innerHTML = ( cfg.emoji || '' ) + ' ' + ( cfg.label || cat );
	} );

	function autoSelectMealCat() {
		var h = new Date().getHours();
		var bk = mealCatCfg.breakfast || { start: 5, end: 11 };
		var ln = mealCatCfg.lunch     || { start: 11, end: 15 };
		var dn = mealCatCfg.dinner    || { start: 17, end: 22 };
		if ( h >= bk.start && h < bk.end ) currentMealCat = 'breakfast';
		else if ( h >= ln.start && h < ln.end ) currentMealCat = 'lunch';
		else if ( h >= dn.start && h < dn.end ) currentMealCat = 'dinner';
		else currentMealCat = 'snack';

		mealCatPills.forEach( function ( p ) {
			p.classList.toggle( 'fcc-meal-cat-pill--active', p.dataset.cat === currentMealCat );
		} );
	}
	autoSelectMealCat();

	mealCatPills.forEach( function ( pill ) {
		pill.addEventListener( 'click', function () {
			currentMealCat = pill.dataset.cat;
			mealCatPills.forEach( function ( p ) { p.classList.remove( 'fcc-meal-cat-pill--active' ); } );
			pill.classList.add( 'fcc-meal-cat-pill--active' );
		} );
	} );

	root.querySelectorAll( '.fcc-add-to-meal' ).forEach( function ( btn ) {
		var origHTML = btn.innerHTML;
		btn.addEventListener( 'click', function () {
			if ( ! state.food ) return;
			var grams = quantityInGrams();
			state.meal.push( {
				food:     Object.assign( {}, state.food ),
				grams:    grams,
				label:    state.food.name + ' (' + fmt( grams, 0 ) + 'g)',
				category: currentMealCat,
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
			omega3_total_mg: 0, caffeine_mg: 0, iron_mg: 0, calcium_mg: 0, vitamin_c_mg: 0 };
		let omega3HasData = false;
		let caffeineHasData = false;
		let microHasData = false;

		var catEmojis = {}, catLabels = {};
		[ 'breakfast', 'lunch', 'dinner', 'snack' ].forEach( function ( c ) {
			var cfg = mealCatCfg[ c ] || {};
			catEmojis[ c ] = cfg.emoji || '';
			catLabels[ c ] = cfg.label || c.charAt(0).toUpperCase() + c.slice(1);
		} );
		var catOrder  = [ 'breakfast', 'lunch', 'snack', 'dinner' ];

		// Group items by category.
		var groups = {};
		state.meal.forEach( function ( item, i ) {
			var cat = item.category || 'snack';
			if ( ! groups[ cat ] ) groups[ cat ] = [];
			groups[ cat ].push( { item: item, idx: i } );
		} );

		// Accumulate totals.
		state.meal.forEach( function ( item ) {
			var f = item.food;
			var factor = item.grams / 100;
			Object.keys( totals ).forEach( function ( k ) {
				if ( f[ k ] !== null && f[ k ] !== undefined ) {
					totals[ k ] += f[ k ] * factor;
					if ( k === 'omega3_total_mg' ) omega3HasData = true;
					if ( k === 'caffeine_mg'     ) caffeineHasData = true;
					if ( k === 'iron_mg' || k === 'calcium_mg' || k === 'vitamin_c_mg' ) microHasData = true;
				}
			} );
		} );

		// Render grouped items.
		catOrder.forEach( function ( cat ) {
			if ( ! groups[ cat ] ) return;
			var catKcal = 0;
			groups[ cat ].forEach( function ( g ) { catKcal += ( g.item.food.energy_kcal || 0 ) * ( g.item.grams / 100 ); } );

			var header = document.createElement( 'div' );
			header.className = 'fcc-meal-cat-header';
			header.innerHTML = '<span class="fcc-meal-cat-header__emoji">' + ( catEmojis[ cat ] || '' ) + '</span>'
				+ '<span class="fcc-meal-cat-header__label">' + ( catLabels[ cat ] || cat ) + '</span>'
				+ '<span class="fcc-meal-cat-header__kcal">' + fmt( catKcal, 0 ) + ' kcal</span>';
			itemsEl.appendChild( header );

			groups[ cat ].forEach( function ( g ) {
				var f = g.item.food;
				var factor = g.item.grams / 100;
				var div = document.createElement( 'div' );
				div.className = 'fcc-meal-item';
				div.setAttribute( 'role', 'listitem' );
				var qtyHtml = features.meal_edit_quantity
					? '<input type="number" class="fcc-meal-item__qty" data-idx="' + g.idx + '" value="' + Math.round( g.item.grams ) + '" min="1" max="9999" step="1"><span class="fcc-meal-item__unit">g</span>'
					: '<span class="fcc-meal-item__grams">' + Math.round( g.item.grams ) + 'g</span>';
				div.innerHTML =
					'<span class="fcc-meal-item__num">' + ( g.idx + 1 ) + '</span>' +
					'<span class="fcc-meal-item__name">' + escHtml( f.name ) + '</span>' +
					'<div class="fcc-meal-item__controls">' +
						qtyHtml +
						'<span class="fcc-meal-item__kcal">' + fmt( f.energy_kcal * factor, 0 ) + ' kcal</span>' +
						'<button type="button" class="fcc-meal-item__remove" data-idx="' + g.idx + '" aria-label="Remove ' + escHtml( f.name ) + '">' +
						'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
						'</button>' +
					'</div>';
				itemsEl.appendChild( div );
			} );
		} );

		// Servings divider.
		var servesInput = mealSection ? mealSection.querySelector( '.fcc-meal-serves__input' ) : null;
		var servesWrap  = mealSection ? mealSection.querySelector( '.fcc-meal-serves' ) : null;
		var servesLabel = mealSection ? mealSection.querySelector( '.fcc-meal-serves__per-serving' ) : null;
		var serves = servesInput ? Math.max( 1, parseInt( servesInput.value, 10 ) || 1 ) : 1;
		if ( servesWrap && features.meal_servings ) servesWrap.hidden = false;

		// Per-serving values.
		var ps = {};
		Object.keys( totals ).forEach( function ( k ) { ps[ k ] = totals[ k ] / serves; } );

		// Update header total kcal.
		if ( totalKcalEl ) {
			if ( serves > 1 ) {
				totalKcalEl.textContent = fmt( ps.energy_kcal, 0 ) + ' / ' + fmt( totals.energy_kcal, 0 );
			} else {
				totalKcalEl.textContent = fmt( totals.energy_kcal, 0 );
			}
		}

		// Per-serving label.
		if ( servesLabel ) {
			if ( serves > 1 ) {
				servesLabel.textContent = fmt( ps.energy_kcal, 0 ) + ' kcal per serving';
				servesLabel.hidden = false;
			} else {
				servesLabel.hidden = true;
			}
		}

		// Update macro pills (show per-serving when serves > 1).
		var displayTotals = serves > 1 ? ps : totals;
		if ( macrosEl ) {
			const pEl = macrosEl.querySelector( '.fcc-meal-macro__val--protein' );
			const cEl = macrosEl.querySelector( '.fcc-meal-macro__val--carbs' );
			const fEl = macrosEl.querySelector( '.fcc-meal-macro__val--fat' );
			const fiEl = macrosEl.querySelector( '.fcc-meal-macro__val--fibre' );
			if ( pEl  ) pEl.textContent  = fmt( displayTotals.protein_g, 1 ) + 'g';
			if ( cEl  ) cEl.textContent  = fmt( displayTotals.carbohydrate_g, 1 ) + 'g';
			if ( fEl  ) fEl.textContent  = fmt( displayTotals.fat_g, 1 ) + 'g';
			if ( fiEl ) fiEl.textContent = fmt( displayTotals.fibre_g, 1 ) + 'g';
			macrosEl.hidden = false;
		}

		// Daily goal comparison bar (uses per-serving kcal).
		var dailyBar = mealSection ? mealSection.querySelector( '.fcc-meal-daily-bar' ) : null;
		if ( dailyBar && features.meal_daily_goal && state.bmrTdee ) {
			var pct = Math.min( 100, Math.round( ( ps.energy_kcal / state.bmrTdee ) * 100 ) );
			var fill = dailyBar.querySelector( '.fcc-meal-daily-fill' );
			var lbl  = dailyBar.querySelector( '.fcc-meal-daily-label' );
			if ( fill ) fill.style.width = pct + '%';
			if ( lbl )  lbl.textContent  = pct + '% of daily goal (' + fmt( state.bmrTdee, 0 ) + ' kcal)' + ( serves > 1 ? ' per serving' : '' );
			dailyBar.hidden = false;
		} else if ( dailyBar ) {
			dailyBar.hidden = true;
		}

		// Render totals table.
		if ( totBodyEl ) {
			var showTot = serves > 1;
			var hdr = showTot ? '<tr class="fcc-meal-totals-hdr"><th></th><th>Per Serving</th><th>Total</th></tr>' : '';
			function tRow( label, perVal, totVal, unit, d ) {
				if ( showTot ) {
					return '<tr><th scope="row">' + escHtml( label ) + '</th><td>' + fmt( perVal, d ) + ' ' + unit + '</td><td class="fcc-meal-total-col">' + fmt( totVal, d ) + ' ' + unit + '</td></tr>';
				}
				return '<tr><th scope="row">' + escHtml( label ) + '</th><td>' + fmt( totVal, d ) + ' ' + unit + '</td></tr>';
			}
			let html = hdr;
			html += tRow( 'Energy',       ps.energy_kcal,            totals.energy_kcal, 'kcal', 0 );
			html += tRow( 'Energy',       ps.energy_kj,              totals.energy_kj,   'kJ',   0 );
			html += tRow( 'Fat',          ps.fat_g,                  totals.fat_g,       'g',    decimals );
			html += tRow( '– Saturates',  ps.of_which_saturates_g,   totals.of_which_saturates_g, 'g', decimals );
			html += tRow( 'Carbohydrate', ps.carbohydrate_g,         totals.carbohydrate_g, 'g', decimals );
			html += tRow( '– Sugars',     ps.of_which_sugars_g,      totals.of_which_sugars_g, 'g', decimals );
			html += tRow( 'Fibre',        ps.fibre_g,                totals.fibre_g,     'g',    decimals );
			html += tRow( 'Protein',      ps.protein_g,              totals.protein_g,   'g',    decimals );
			html += tRow( 'Salt',         ps.salt_g,                 totals.salt_g,      'g',    decimals );
			if ( omega3HasData )   html += tRow( 'Omega-3 Total', ps.omega3_total_mg, totals.omega3_total_mg, 'mg', decimals );
			if ( caffeineHasData ) html += tRow( 'Caffeine',      ps.caffeine_mg,     totals.caffeine_mg,     'mg', decimals );
			if ( microHasData && features.meal_micronutrients ) {
				if ( totals.iron_mg )      html += tRow( '🩸 Iron',      ps.iron_mg,      totals.iron_mg,      'mg', decimals );
				if ( totals.calcium_mg )   html += tRow( '🦴 Calcium',   ps.calcium_mg,   totals.calcium_mg,   'mg', decimals );
				if ( totals.vitamin_c_mg ) html += tRow( '🍊 Vitamin C', ps.vitamin_c_mg, totals.vitamin_c_mg, 'mg', decimals );
			}
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

		// Serves input change.
		mealSection.addEventListener( 'input', function ( e ) {
			if ( e.target.closest( '.fcc-meal-serves__input' ) ) renderMeal();
		} );

		// Edit quantity inline.
		mealSection.addEventListener( 'change', function ( e ) {
			var input = e.target.closest( '.fcc-meal-item__qty' );
			if ( ! input ) return;
			var idx = parseInt( input.dataset.idx, 10 );
			var newGrams = Math.max( 1, parseFloat( input.value ) || 1 );
			state.meal[ idx ].grams = newGrams;
			state.meal[ idx ].label = state.meal[ idx ].food.name + ' (' + Math.round( newGrams ) + 'g)';
			renderMeal();
		} );

		// Copy Meal Totals.
		mealSection.addEventListener( 'click', function ( e ) {
			var copyBtn = e.target.closest( '.fcc-meal-copy-btn' );
			if ( ! copyBtn || ! state.meal.length ) return;
			var lines = [ 'My Meal — ' + state.meal.length + ' items' ];
			var totalKcal = 0;
			var totalP = 0, totalC = 0, totalF = 0;
			state.meal.forEach( function ( item ) {
				var factor = item.grams / 100;
				var kcal = Math.round( ( item.food.energy_kcal || 0 ) * factor );
				totalKcal += kcal;
				totalP += ( item.food.protein_g || 0 ) * factor;
				totalC += ( item.food.carbohydrate_g || 0 ) * factor;
				totalF += ( item.food.fat_g || 0 ) * factor;
				lines.push( '• ' + item.food.name + ' (' + Math.round( item.grams ) + 'g) — ' + kcal + ' kcal' );
			} );
			lines.push( 'Total: ' + totalKcal + ' kcal | Protein ' + fmt( totalP, 1 ) + 'g | Carbs ' + fmt( totalC, 1 ) + 'g | Fat ' + fmt( totalF, 1 ) + 'g' );
			lines.push( '📊 Build yours → foodcaloriecalculator.co.uk' );
			var text = lines.join( '\n' );
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( text ).then( function () {
					var lbl = copyBtn.querySelector( 'span' );
					if ( lbl ) { lbl.textContent = 'Copied!'; setTimeout( function () { lbl.textContent = 'Copy Meal'; }, 2000 ); }
				} );
			}
		} );

		// Share Meal Link.
		mealSection.addEventListener( 'click', function ( e ) {
			var shareBtn = e.target.closest( '.fcc-meal-share-btn' );
			if ( ! shareBtn || ! state.meal.length ) return;
			var parts = state.meal.map( function ( item ) { return item.food.id + ':' + Math.round( item.grams ); } );
			var url = window.location.origin + window.location.pathname + '?meal=' + parts.join( ',' );
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( url ).then( function () {
					var lbl = shareBtn.querySelector( 'span' );
					if ( lbl ) { lbl.textContent = 'Link Copied!'; setTimeout( function () { lbl.textContent = 'Share Meal'; }, 2000 ); }
				} );
			}
		} );
	}

	// Load meal from URL on init.
	( function () {
		var params = new URLSearchParams( window.location.search );
		var mealParam = params.get( 'meal' );
		if ( ! mealParam ) return;
		var items = mealParam.split( ',' );
		var loaded = 0;
		items.forEach( function ( part ) {
			var bits = part.split( ':' );
			var foodId = parseInt( bits[0], 10 );
			var grams  = parseFloat( bits[1] ) || 100;
			if ( ! foodId ) return;
			apiFetch( '/foods/' + foodId, function ( food ) {
				state.meal.push( { food: food, grams: grams, label: food.name + ' (' + Math.round( grams ) + 'g)', category: currentMealCat } );
				loaded++;
				if ( loaded === items.length ) renderMeal();
			} );
		} );
	} )();

	// Meal Print button (inside meal tab).
	if ( mealSection ) {
		mealSection.addEventListener( 'click', function ( e ) {
			var printMealBtn = e.target.closest( '.fcc-meal-print-btn' );
			if ( ! printMealBtn || ! state.meal.length ) return;
			// Trigger the main print button logic with meal tab forced active.
			var mealPanel = root.querySelector( '.fcc-tab-panel[data-panel="meal"]' );
			if ( mealPanel ) mealPanel.removeAttribute( 'hidden' );
			if ( printBtn ) printBtn.click();
		} );
	}

	// -------------------------------------------------------------------------
	// Meal Templates (localStorage)
	// -------------------------------------------------------------------------
	var TPL_KEY = 'fcc_meal_templates';
	var TPL_MAX = ( advanced && advanced.mealMaxTemplates ) ? advanced.mealMaxTemplates : 10;
	var tplSection = root.querySelector( '#fcc-meal-templates' );
	var tplList    = root.querySelector( '#fcc-meal-templates-list' );
	var tplSaveBtn = root.querySelector( '#fcc-save-tpl-btn' );

	function getTemplates() {
		try { return JSON.parse( localStorage.getItem( TPL_KEY ) ) || []; } catch ( e ) { return []; }
	}
	function saveTemplates( arr ) { localStorage.setItem( TPL_KEY, JSON.stringify( arr.slice( 0, TPL_MAX ) ) ); }

	function renderTemplates() {
		var tpls = getTemplates();
		if ( ! tplSection || ! tplList ) return;
		if ( ! tpls.length ) { tplSection.hidden = true; return; }
		tplSection.hidden = false;
		tplList.innerHTML = tpls.map( function ( t, i ) {
			return '<div class="fcc-meal-tpl-chip">'
				+ '<button type="button" class="fcc-meal-tpl-chip__load" data-tpl-idx="' + i + '">'
				+ escHtml( t.name ) + ' <span class="fcc-meal-tpl-chip__count">(' + t.items.length + ')</span>'
				+ '</button>'
				+ '<button type="button" class="fcc-meal-tpl-chip__del" data-tpl-idx="' + i + '" title="Delete">×</button>'
				+ '</div>';
		} ).join( '' );
	}

	if ( tplSaveBtn ) {
		tplSaveBtn.addEventListener( 'click', function () {
			if ( ! state.meal.length ) return;
			var catLabels = { breakfast: 'Breakfast', lunch: 'Lunch', dinner: 'Dinner', snack: 'Snack' };
			var defaultName = 'My ' + ( catLabels[ currentMealCat ] || 'Meal' );
			var name = prompt( 'Template name:', defaultName );
			if ( ! name ) return;
			var tpls = getTemplates();
			tpls.unshift( {
				name: name,
				items: state.meal.map( function ( m ) {
					return { food: m.food, grams: m.grams, label: m.label, category: m.category };
				} ),
			} );
			saveTemplates( tpls );
			renderTemplates();
			tplSaveBtn.textContent = '✓ Saved!';
			setTimeout( function () {
				tplSaveBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg> Save as Template';
			}, 1500 );
		} );
	}

	if ( tplList ) {
		tplList.addEventListener( 'click', function ( e ) {
			var loadBtn = e.target.closest( '.fcc-meal-tpl-chip__load' );
			var delBtn  = e.target.closest( '.fcc-meal-tpl-chip__del' );

			if ( loadBtn ) {
				var idx = parseInt( loadBtn.dataset.tplIdx, 10 );
				var tpls = getTemplates();
				if ( tpls[ idx ] ) {
					state.meal = [];
					tpls[ idx ].items.forEach( function ( item ) {
						state.meal.push( {
							food:     item.food,
							grams:    item.grams,
							label:    item.label,
							category: item.category || 'snack',
						} );
					} );
					tplList.querySelectorAll( '.fcc-meal-tpl-chip' ).forEach( function ( c ) { c.classList.remove( 'fcc-meal-tpl-chip--active' ); } );
					loadBtn.closest( '.fcc-meal-tpl-chip' ).classList.add( 'fcc-meal-tpl-chip--active' );
					renderMeal();
					var mealTab = root.querySelector( '.fcc-tab-btn[data-tab="meal"]' );
					if ( mealTab ) mealTab.click();
				}
			}

			if ( delBtn ) {
				var idx = parseInt( delBtn.dataset.tplIdx, 10 );
				var tpls = getTemplates();
				tpls.splice( idx, 1 );
				saveTemplates( tpls );
				renderTemplates();
			}
		} );
	}

	renderTemplates();

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
			// Detect if meal tab is active for meal printing.
			var activeMealTab = root.querySelector( '.fcc-tab-panel[data-panel="meal"]:not([hidden])' );
			var isMealPrint = activeMealTab && state.meal.length && features.meal_print;

			const clone = root.cloneNode( true );
			clone.classList.add( 'fcc-print-clone' );

			// Remove non-result sections.
			[ 'fcc-tabs-nav', 'fcc-search-section', 'fcc-result-actions', 'fcc-popular-section',
			  'fcc-favourites-section', 'fcc-request-panel' ].forEach( function ( cls ) {
				var el = clone.querySelector( '.' + cls ) || clone.querySelector( '#' + cls );
				if ( el ) el.remove();
			} );
			var keepPanel = isMealPrint ? 'meal' : 'calculator';
			clone.querySelectorAll( '.fcc-tab-panel' ).forEach( function ( panel ) {
				if ( panel.dataset.panel !== keepPanel ) panel.remove();
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

			// SVG rings print natively — no canvas conversion needed.

			// Build data for header.
			var food = state.food;
			var qty  = state.quantity || 100;
			var unit = state.unit === 'kg' ? 'kg' : ( state.unit === 'oz' ? 'oz' : 'g' );
			var now  = new Date().toLocaleDateString( 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' } );
			var printGrams = quantityInGrams();
			var kcal = food ? Math.round( ( food.energy_kcal || 0 ) * ( printGrams / 100 ) ) : 0;
			var dailyPct = Math.round( kcal / 2000 * 100 );

			if ( isMealPrint ) {
				var mealKcal = 0;
				state.meal.forEach( function ( item ) { mealKcal += ( item.food.energy_kcal || 0 ) * ( item.grams / 100 ); } );
				kcal = Math.round( mealKcal );
				dailyPct = Math.round( kcal / 2000 * 100 );
			}

			// Branded print header with kcal summary.
			var printHeader = document.createElement( 'div' );
			printHeader.className = 'fcc-print-header';
			printHeader.innerHTML =
				'<div class="fcc-print-header__brand">'
				+ '<strong>Food Calorie Calculator</strong>'
				+ '<span>foodcaloriecalculator.co.uk</span>'
				+ '</div>'
				+ '<div class="fcc-print-header__info">'
				+ '<span class="fcc-print-header__food">' + ( isMealPrint ? 'Meal Plan (' + state.meal.length + ' items)' : ( food ? food.name : '' ) ) + '</span>'
				+ '<span class="fcc-print-header__kcal">' + kcal + ' kcal' + ( isMealPrint ? ' total' : ' per ' + qty + unit ) + ' · ' + dailyPct + '% of daily 2000 kcal</span>'
				+ '<span class="fcc-print-header__date">' + now + '</span>'
				+ '</div>';
			clone.insertBefore( printHeader, clone.firstChild );

			// Marketing + affiliate footer.
			var printFooter = document.createElement( 'div' );
			printFooter.className = 'fcc-print-footer';
			printFooter.innerHTML =
				'<div class="fcc-print-footer__line">'
				+ 'Generated by Food Calorie Calculator · ' + now
				+ '</div>'
				+ '<div class="fcc-print-footer__cta">'
				+ '🔍 Calculate nutrition for any food at <strong>foodcaloriecalculator.co.uk</strong>'
				+ '</div>'
				+ '<div class="fcc-print-footer__disclaimer">'
				+ 'Nutritional values are per 100g (unless otherwise stated) and for general information only. Not a substitute for professional dietary or medical advice. '
				+ 'If you have a health condition, allergy, or are pregnant, consult a qualified healthcare professional. Data sourced from USDA FDC and UK food composition tables. '
				+ 'Values may vary depending on brand, preparation method, and serving size.'
				+ '</div>';
			clone.appendChild( printFooter );

			document.body.appendChild( clone );

			const prevTitle = document.title;
			document.title  = ( isMealPrint ? 'Meal Plan (' + state.meal.length + ' items)' : ( food ? food.name : '' ) ) + ' – Food Calorie Calculator';
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
			var u = state.unit === 'kg' ? 'kg' : ( state.unit === 'oz' ? 'oz' : 'g' );
			var grams = state.unit === 'kg' ? q * 1000 : ( state.unit === 'oz' ? q * OZ_TO_G : q );
			var factor = grams / 100;
			var text = f.name + ' — per ' + q + u + '\n'
				+ fmt( ( f.energy_kcal || 0 ) * factor, 0 ) + ' kcal'
				+ ' | Protein ' + fmt( ( f.protein_g || 0 ) * factor, 1 ) + 'g'
				+ ' | Carbs ' + fmt( ( f.carbohydrate_g || 0 ) * factor, 1 ) + 'g'
				+ ( f.of_which_sugars_g != null ? ' (Sugars ' + fmt( ( f.of_which_sugars_g ) * factor, 1 ) + 'g)' : '' )
				+ '\nFat ' + fmt( ( f.fat_g || 0 ) * factor, 1 ) + 'g'
				+ ( f.of_which_saturates_g != null ? ' (Saturates ' + fmt( ( f.of_which_saturates_g ) * factor, 1 ) + 'g)' : '' )
				+ ' | Fibre ' + fmt( ( f.fibre_g || 0 ) * factor, 1 ) + 'g'
				+ ' | Salt ' + fmt( ( f.salt_g || 0 ) * factor, 1 ) + 'g';

			var allergenNames = [];
			var allergenMap = { allergen_fish:'Fish', allergen_shellfish:'Shellfish', allergen_dairy:'Dairy', allergen_eggs:'Eggs', allergen_nuts:'Tree Nuts', allergen_gluten:'Gluten', allergen_soy:'Soy', allergen_celery:'Celery' };
			for ( var ak in allergenMap ) { if ( f[ak] === 1 || f[ak] === '1' ) allergenNames.push( allergenMap[ak] ); }
			var dietNames = [];
			var dietMap = { diet_keto:'Keto', diet_paleo:'Paleo', diet_halal:'Halal', diet_kosher:'Kosher', diet_vegan:'Vegan', diet_vegetarian:'Vegetarian' };
			for ( var dk in dietMap ) { if ( f[dk] === 1 || f[dk] === '1' ) dietNames.push( dietMap[dk] ); }
			if ( allergenNames.length || dietNames.length ) {
				text += '\n';
				if ( allergenNames.length ) text += 'Contains: ' + allergenNames.join( ', ' );
				if ( allergenNames.length && dietNames.length ) text += ' | ';
				if ( dietNames.length ) text += '✅ ' + dietNames.join( ', ' );
			}

			if ( features.fsa_traffic_lights ) {
				var tlItems = [
					{ label: 'Fat', val: f.fat_g, low: fsa.fat_low, high: fsa.fat_high },
					{ label: 'Saturates', val: f.of_which_saturates_g, low: fsa.saturates_low, high: fsa.saturates_high },
					{ label: 'Sugars', val: f.of_which_sugars_g, low: fsa.sugars_low, high: fsa.sugars_high },
					{ label: 'Salt', val: f.salt_g, low: fsa.salt_low, high: fsa.salt_high },
				];
				var tlEmojis = [];
				var tlLabels = [];
				tlItems.forEach( function ( t ) {
					if ( t.val == null ) return;
					var emoji = t.val <= t.low ? '🟢' : ( t.val > t.high ? '🔴' : '🟡' );
					var rating = t.val <= t.low ? 'Low' : ( t.val > t.high ? 'High' : 'Med' );
					tlEmojis.push( emoji );
					tlLabels.push( t.label + ' ' + rating );
				} );
				if ( tlLabels.length ) text += '\n' + tlEmojis.join( '' ) + ' ' + tlLabels.join( ' | ' );
			}

			text += '\n📊 Calculate yours → foodcaloriecalculator.co.uk';
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( text ).then( function () {
					if ( copyNutrLbl ) {
						copyNutrLbl.textContent = 'Copied!';
						copyNutrBtn.classList.add( 'fcc-btn--copied' );
						setTimeout( function () {
							copyNutrLbl.textContent = 'Copy Nutrition';
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

		const kgOpt2 = document.createElement( 'option' );
		kgOpt2.value = 'kg'; kgOpt2.textContent = 'kilograms (kg)';
		sl.unitSelect.appendChild( kgOpt2 );

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
					sl.unitSelect.innerHTML = '<option value="g">grams (g)</option><option value="kg">kilograms (kg)</option>';
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

	// -------------------------------------------------------------------------
	// PWA Install
	// -------------------------------------------------------------------------
	if ( features.pwa_install !== false && 'serviceWorker' in navigator ) {
		var swUrl = ( fccData.pluginUrl || '' ) + 'assets/pwa/sw.js';
		navigator.serviceWorker.register( swUrl, { scope: '/' } ).catch( function () {} );

		var deferredPrompt = null;

		window.addEventListener( 'beforeinstallprompt', function ( e ) {
			e.preventDefault();
			deferredPrompt = e;

			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'fcc-pwa-install-btn';
			btn.innerHTML =
				'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' +
				'<span>Install App</span>';
			btn.addEventListener( 'click', function () {
				if ( ! deferredPrompt ) return;
				deferredPrompt.prompt();
				deferredPrompt.userChoice.then( function ( result ) {
					if ( result.outcome === 'accepted' ) btn.remove();
					deferredPrompt = null;
				} );
			} );

			var footer = root.querySelector( '.fcc-footer' );
			if ( footer ) {
				footer.insertBefore( btn, footer.firstChild );
			} else {
				root.querySelector( '.fcc-tabs-body' ).appendChild( btn );
			}
		} );

		window.addEventListener( 'appinstalled', function () {
			var b = root.querySelector( '.fcc-pwa-install-btn' );
			if ( b ) b.remove();
			deferredPrompt = null;
		} );
	}

} )();
