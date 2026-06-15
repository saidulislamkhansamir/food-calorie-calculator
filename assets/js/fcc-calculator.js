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

	const cfg       = fccData;
	const features  = cfg.features  || {};
	const general   = cfg.general   || {};
	const ri        = general.ri    || {};
	const fsa       = general.fsa   || {};
	const i18n      = cfg.i18n      || {};
	const labels    = cfg.labels    || {};
	const decimals  = general.decimalPlaces !== undefined ? Number( general.decimalPlaces ) : 1;
	const OZ_TO_G   = 28.3495;

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

	const searchInput  = root.querySelector( '.fcc-search-input' );
	const dropdown     = root.querySelector( '.fcc-results-dropdown' );
	const spinner      = root.querySelector( '.fcc-search-spinner' );
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
	const trafficLights = features.fsa_traffic_lights ? root.querySelector( '.fcc-traffic-lights' ) : null;
	const bmrSection    = features.bmr_tdee ? root.querySelector( '.fcc-bmr-section' ) : null;
	const mealTabBadge  = root.querySelector( '.fcc-tab-badge' );
	const mealEmptyState = root.querySelector( '.fcc-meal-empty' );

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

	// -------------------------------------------------------------------------
	// Autocomplete
	// -------------------------------------------------------------------------
	if ( searchInput ) {
		searchInput.addEventListener( 'input', function () {
			clearTimeout( debounceTimer );
			const q = this.value.trim();
			if ( q.length < 2 ) { hideDropdown(); return; }
			debounceTimer = setTimeout( function () { doSearch( q ); }, 280 );
		} );

		searchInput.addEventListener( 'keydown', handleDropdownKeys );
		document.addEventListener( 'click', function ( e ) {
			if ( ! root.contains( e.target ) ) hideDropdown();
		} );
	}

	function doSearch( q ) {
		setSpinner( true );
		const cat = Number( general.defaultCategory || 0 );
		let url = '/foods/search?q=' + encodeURIComponent( q ) + '&limit=10';
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
			li.textContent = i18n.noResults || 'No foods found';
			dropdown.appendChild( li );
			showDropdown();
			return;
		}
		foods.forEach( function ( food ) {
			const li = document.createElement( 'li' );
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', 'false' );
			li.dataset.id = food.id;
			li.innerHTML =
				'<span class="fcc-result-name">' + escHtml( food.name ) + '</span>' +
				'<span class="fcc-result-kcal">' + fmt( food.energy_kcal ) + ' kcal</span>';
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
		state.quantity = 100;
		state.unit     = 'g';

		searchInput.value = food.name;
		hideDropdown();

		// Populate unit selector with serving sizes.
		rebuildUnitSelect( food );

		if ( qtyInput  ) qtyInput.value = 100;
		if ( foodNameEl ) foodNameEl.textContent = food.name;
		if ( qtySection ) qtySection.hidden = false;
		if ( addToMealBtn && features.meal_builder ) addToMealBtn.hidden = false;

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
			window.FccChart.draw(
				macroCanvas,
				{ protein: protein_kcal, carbs: carbs_kcal, fat: fat_kcal },
				macroLegend,
				{ protein: 'Protein', carbs: 'Carbs', fat: 'Fat' }
			);
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
			const val = food[ fields[ key ] ];
			const td  = row.querySelector( 'td' );
			if ( ! td ) return;

			if ( val === null || val === undefined ) {
				td.textContent = i18n.dataNotAvailable || 'Data not available';
				td.className = 'fcc-data-na';
			} else {
				td.textContent = fmt( val * factor, d ) + ' mg';
				td.className = '';
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
	if ( addToMealBtn ) {
		addToMealBtn.addEventListener( 'click', function () {
			if ( ! state.food ) return;
			const grams = quantityInGrams();
			state.meal.push( {
				food:  Object.assign( {}, state.food ),
				grams: grams,
				label: state.food.name + ' (' + fmt( grams, 0 ) + 'g)',
			} );
			renderMeal();
			showToast( escHtml( state.food.name ) + ' added to your meal' );
		} );
	}

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

		// Mifflin-St Jeor BMR.
		let bmr;
		if ( sex === 'male' ) {
			bmr = 10 * weight + 6.25 * height - 5 * age + 5;
		} else {
			bmr = 10 * weight + 6.25 * height - 5 * age - 161;
		}

		const tdeeMaintain = Math.round( bmr * activity );
		let tdee = tdeeMaintain;
		if ( goal === 'lose' ) tdee -= 500;
		if ( goal === 'gain' ) tdee += 500;

		state.bmrTdee = tdee;

		const resultEl    = bmrSection.querySelector( '.fcc-bmr-result' );
		const tdeeEl      = bmrSection.querySelector( '.fcc-bmr-tdee-display' );
		const bmrValEl    = bmrSection.querySelector( '.fcc-bmr-bmr-val' );
		const loseEl      = bmrSection.querySelector( '.fcc-bmr-kcal-lose' );
		const maintainEl  = bmrSection.querySelector( '.fcc-bmr-kcal-maintain' );
		const gainEl      = bmrSection.querySelector( '.fcc-bmr-kcal-gain' );

		if ( tdeeEl )     tdeeEl.textContent     = fmt( tdee, 0 );
		if ( bmrValEl )   bmrValEl.textContent   = fmt( Math.round( bmr ), 0 );
		if ( loseEl )     loseEl.textContent     = fmt( tdeeMaintain - 500, 0 );
		if ( maintainEl ) maintainEl.textContent = fmt( tdeeMaintain, 0 );
		if ( gainEl )     gainEl.textContent     = fmt( tdeeMaintain + 500, 0 );

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
		shareBtn.addEventListener( 'click', function () {
			const url = buildShareUrl();
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( url ).then( function () {
					showToast( i18n.copyDone || 'Link copied!' );
				} );
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
		url.searchParams.set( 'fcc_qty',  state.quantity );
		url.searchParams.set( 'fcc_unit', state.unit );
		return url.toString();
	}

	function updateShareUrl() {
		if ( history.replaceState && state.food ) {
			history.replaceState( null, '', buildShareUrl() );
		}
	}

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
			window.print();
		} );
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
		foods.forEach( function ( food ) {
			var li = document.createElement( 'li' );
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', 'false' );
			li.dataset.id = food.id;
			li.innerHTML =
				'<span class="fcc-result-name">' + escHtml( food.name ) + '</span>' +
				'<span class="fcc-result-kcal">' + fmt( food.energy_kcal ) + ' kcal</span>';
			li.addEventListener( 'click', function () { selectCompareFood( s, food ); } );
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
			if ( q.length < 2 ) { hideCompareDropdown( s ); return; }
			cTimer = setTimeout( function () {
				var cat = Number( general.defaultCategory || 0 );
				var url = '/foods/search?q=' + encodeURIComponent( q ) + '&limit=8';
				if ( cat > 0 ) url += '&category=' + cat;
				apiFetch( url )
					.then( function ( foods ) { renderCompareDropdown( s, foods ); } )
					.catch( function () { renderCompareDropdown( s, [] ); } );
			}, 280 );
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
