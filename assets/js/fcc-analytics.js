/* global fccAnalytics, Chart */
(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var wrap = document.querySelector( '.fcc-an-page' );
		if ( ! wrap ) { return; }

		var nonce    = wrap.dataset.nonce;
		var csvNonce = wrap.dataset.csvNonce || '';
		var range    = parseInt( wrap.dataset.range, 10 ) || 30;
		var loaded   = {};
		var charts   = {};

		// ── Colour palette ──────────────────────────────────────────────
		var C = {
			green:  '#2D7A4F',
			blue:   '#3498db',
			amber:  '#f39c12',
			red:    '#e74c3c',
			purple: '#9b59b6',
			teal:   '#16a085',
			gold:   '#d4a017',
		};
		var catPalette = [
			'#2D7A4F','#3498db','#e67e22','#9b59b6','#e74c3c',
			'#16a085','#d4a017','#2c3e50','#1abc9c','#c0392b',
			'#8e44ad','#27ae60','#2980b9','#f1c40f','#e91e63',
		];

		// ── Helpers ─────────────────────────────────────────────────────
		function esc( s ) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

		function hideSpinner( id ) {
			var s = document.getElementById( id );
			if ( s ) s.style.display = 'none';
		}

		function ajaxPost( tab, cb, overrideRange, dateFrom, dateTo ) {
			var r = overrideRange !== undefined ? overrideRange : range;
			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', fccAnalytics.ajaxUrl );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			xhr.onload = function () {
				if ( xhr.status !== 200 ) return;
				try {
					var res = JSON.parse( xhr.responseText );
					if ( res.success ) cb( res.data );
				} catch ( e ) {}
			};
			var params = 'action=fcc_analytics_charts'
				+ '&_ajax_nonce=' + encodeURIComponent( nonce )
				+ '&range=' + r
				+ '&tab=' + encodeURIComponent( tab );
			if ( dateFrom ) params += '&date_from=' + encodeURIComponent( dateFrom );
			if ( dateTo ) params += '&date_to=' + encodeURIComponent( dateTo );
			xhr.send( params );
		}

		// ── Tab system ──────────────────────────────────────────────────
		var tabs   = wrap.querySelectorAll( '.fcc-an-tab' );
		var panels = wrap.querySelectorAll( '.fcc-an-tab-panel' );

		function activateTab( key ) {
			tabs.forEach( function ( t ) { t.classList.toggle( 'fcc-an-tab--active', t.dataset.tab === key ); } );
			panels.forEach( function ( p ) { p.classList.toggle( 'fcc-an-tab-panel--active', p.id === 'fcc-an-panel-' + key ); } );
			loadTabData( key );

			var url = new URL( window.location );
			url.searchParams.set( 'tab', key );
			window.history.replaceState( null, '', url );
		}

		tabs.forEach( function ( t ) {
			t.addEventListener( 'click', function () { activateTab( t.dataset.tab ); } );
		} );

		// ── Tab data loaders ────────────────────────────────────────────
		function loadTabData( key ) {
			if ( loaded[ key ] ) return;
			loaded[ key ] = true;

			switch ( key ) {
				case 'overview':      loadOverview();      break;
				case 'search':        loadSearch();        break;
				case 'monetization':  loadMonetization();  break;
				case 'content':       loadContent();       break;
				case 'audience':      loadAudience();      break;
			}
		}

		// ── Overview ────────────────────────────────────────────────────
		function loadOverview() {
			loadVolumeChart( range );
			loadFoodsChart( 0 );
		}

		function loadVolumeChart( r, dateFrom, dateTo ) {
			if ( charts.volume ) { charts.volume.destroy(); charts.volume = null; }
			var spinner = document.getElementById( 'fcc-an-volume-spinner' );
			if ( spinner ) spinner.style.display = '';
			ajaxPost( 'overview', function ( d ) {
				hideSpinner( 'fcc-an-volume-spinner' );
				var ve = document.getElementById( 'fcc-an-volume-chart' );
				if ( ve && d.volume ) {
					charts.volume = new Chart( ve, {
						type: 'line',
						data: { labels: d.volume.labels, datasets: [ {
							label: 'Searches', data: d.volume.data,
							borderColor: C.green, backgroundColor: 'rgba(45,122,79,.1)',
							borderWidth: 2, pointRadius: 3, tension: 0.3, fill: true,
						} ] },
						options: chartOpts( false ),
					} );
				}
			}, r, dateFrom, dateTo );
		}

		function loadFoodsChart( r, dateFrom, dateTo ) {
			if ( charts.foods ) { charts.foods.destroy(); charts.foods = null; }
			var spinner = document.getElementById( 'fcc-an-foods-spinner' );
			if ( spinner ) spinner.style.display = '';
			ajaxPost( 'overview', function ( d ) {
				hideSpinner( 'fcc-an-foods-spinner' );
				var fe = document.getElementById( 'fcc-an-foods-chart' );
				if ( fe && d.foods ) {
					charts.foods = new Chart( fe, {
						type: 'bar',
						data: { labels: d.foods.labels, datasets: [ {
							label: 'Searches', data: d.foods.data,
							backgroundColor: 'rgba(45,122,79,.75)', borderColor: C.green, borderWidth: 1,
						} ] },
						options: Object.assign( {}, chartOpts( false ), { indexAxis: 'y' } ),
					} );
				}
			}, r, dateFrom, dateTo );
		}

		// ── Search ──────────────────────────────────────────────────────
		function loadSearch() {
			ajaxPost( 'search', function ( d ) {
				hideSpinner( 'fcc-an-success-spinner' );
				hideSpinner( 'fcc-an-category-spinner' );
				hideSpinner( 'fcc-an-peak-spinner' );
				hideSpinner( 'fcc-an-hourly-spinner' );

				// Success Rate Trend
				var se = document.getElementById( 'fcc-an-success-chart' );
				if ( se && d.success_trend ) {
					charts.success = new Chart( se, {
						type: 'line',
						data: { labels: d.success_trend.labels, datasets: [ {
							label: 'Success Rate %', data: d.success_trend.data,
							borderColor: C.blue, backgroundColor: 'rgba(52,152,219,.1)',
							borderWidth: 2, pointRadius: 2, tension: 0.3, fill: true,
						} ] },
						options: chartOpts( false, { y: { beginAtZero: true, max: 100, ticks: { callback: function ( v ) { return v + '%'; } } } } ),
					} );
				}

				// Category Breakdown
				var ce = document.getElementById( 'fcc-an-category-chart' );
				if ( ce && d.category_breakdown && d.category_breakdown.labels.length ) {
					charts.category = new Chart( ce, {
						type: 'doughnut',
						data: {
							labels: d.category_breakdown.labels,
							datasets: [ {
								data: d.category_breakdown.data,
								backgroundColor: catPalette.slice( 0, d.category_breakdown.labels.length ),
								borderWidth: 2, borderColor: '#fff',
							} ],
						},
						options: {
							responsive: true, maintainAspectRatio: true,
							plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } },
						},
					} );
				}

				// Peak Days
				var pe = document.getElementById( 'fcc-an-peak-chart' );
				if ( pe && d.peak_days ) {
					charts.peak = new Chart( pe, {
						type: 'bar',
						data: { labels: d.peak_days.labels, datasets: [ {
							label: 'Searches', data: d.peak_days.data,
							backgroundColor: d.peak_days.data.map( function ( v, i ) {
								return i === indexOfMax( d.peak_days.data ) ? C.green : 'rgba(45,122,79,.4)';
							} ),
							borderColor: C.green, borderWidth: 1, borderRadius: 4,
						} ] },
						options: chartOpts( false ),
					} );
				}

				// Hourly Distribution
				var he = document.getElementById( 'fcc-an-hourly-chart' );
				if ( he && d.hourly ) {
					charts.hourly = new Chart( he, {
						type: 'bar',
						data: { labels: d.hourly.labels, datasets: [ {
							label: 'Searches', data: d.hourly.data,
							backgroundColor: d.hourly.data.map( function ( v, i ) {
								return i === indexOfMax( d.hourly.data ) ? C.teal : 'rgba(22,160,133,.35)';
							} ),
							borderColor: C.teal, borderWidth: 1, borderRadius: 3,
						} ] },
						options: chartOpts( false, { x: { ticks: { maxTicksLimit: 12, font: { size: 10 } } } } ),
					} );
				}

				// Trending Searches table
				renderTrendingTable( d.trending || [] );
			} );
		}

		function renderTrendingTable( rows ) {
			var el = document.getElementById( 'fcc-an-trending-table' );
			if ( ! el ) return;
			if ( ! rows.length ) {
				el.innerHTML = '<p class="fcc-an-empty">Not enough data for trending analysis.</p>';
				return;
			}
			var h = '<table class="fcc-an-table"><thead><tr><th>#</th><th>Query</th><th>Current</th><th>Previous</th><th>Growth</th></tr></thead><tbody>';
			rows.forEach( function ( r, i ) {
				var cls = r.growth_pct > 0 ? 'fcc-an-delta--up' : ( r.growth_pct < 0 ? 'fcc-an-delta--down' : 'fcc-an-delta--neutral' );
				var arrow = r.growth_pct > 0 ? '↑' : ( r.growth_pct < 0 ? '↓' : '—' );
				h += '<tr>'
					+ '<td class="fcc-an-td--num">' + ( i + 1 ) + '</td>'
					+ '<td>' + esc( r.query ) + '</td>'
					+ '<td class="fcc-an-td--center">' + r.current_count + '</td>'
					+ '<td class="fcc-an-td--center">' + r.previous_count + '</td>'
					+ '<td class="fcc-an-td--center"><span class="fcc-an-delta ' + cls + '">' + arrow + ' ' + Math.abs( r.growth_pct ) + '%</span></td>'
					+ '</tr>';
			} );
			h += '</tbody></table>';
			el.innerHTML = h;
		}

		// ── Monetization ────────────────────────────────────────────────
		function loadMonetization() {
			ajaxPost( 'monetization', function ( d ) {
				var se = document.getElementById( 'fcc-an-sponsor-chart' );
				if ( se && d.sponsor_trend && d.sponsor_trend.labels.length ) {
					charts.sponsor = new Chart( se, {
						type: 'line',
						data: { labels: d.sponsor_trend.labels, datasets: [ {
							label: 'Clicks', data: d.sponsor_trend.data,
							borderColor: C.gold, backgroundColor: 'rgba(212,160,23,.1)',
							borderWidth: 2, pointRadius: 1, tension: 0.3, fill: true,
						} ] },
						options: {
							responsive: true, maintainAspectRatio: true,
							plugins: { legend: { display: false } },
							scales: {
								x: { display: false },
								y: { display: false, beginAtZero: true },
							},
						},
					} );
				}
			} );
		}

		// ── Content ─────────────────────────────────────────────────────
		function loadContent() {
			ajaxPost( 'content', function ( d ) {
				hideSpinner( 'fcc-an-coverage-spinner' );

				var cce = document.getElementById( 'fcc-an-coverage-chart' );
				if ( cce && d.category_coverage && d.category_coverage.labels.length ) {
					charts.coverage = new Chart( cce, {
						type: 'bar',
						data: { labels: d.category_coverage.labels, datasets: [ {
							label: 'Foods', data: d.category_coverage.data,
							backgroundColor: catPalette.slice( 0, d.category_coverage.labels.length ),
							borderWidth: 1, borderRadius: 4,
						} ] },
						options: Object.assign( {}, chartOpts( false ), {
							indexAxis: 'y',
							scales: {
								x: { beginAtZero: true, ticks: { precision: 0 } },
								y: { ticks: { font: { size: 10 } } },
							},
						} ),
					} );
				}
			} );
		}

		// ── Audience ────────────────────────────────────────────────────
		function loadAudience() {
			ajaxPost( 'audience', function ( d ) {
				hideSpinner( 'fcc-an-growth-spinner' );

				var ge = document.getElementById( 'fcc-an-growth-chart' );
				if ( ge && d.subscriber_growth && d.subscriber_growth.labels.length ) {
					// Update "New This Month" KPI.
					var latest = d.subscriber_growth.data;
					var newMonth = document.getElementById( 'fcc-an-new-subs-month' );
					if ( newMonth && latest.length ) newMonth.textContent = latest[ latest.length - 1 ];

					charts.growth = new Chart( ge, {
						type: 'line',
						data: {
							labels: d.subscriber_growth.labels,
							datasets: [
								{
									label: 'Cumulative', data: d.subscriber_growth.cumulative,
									borderColor: C.green, backgroundColor: 'rgba(45,122,79,.08)',
									borderWidth: 2, pointRadius: 3, tension: 0.3, fill: true,
								},
								{
									label: 'New', data: d.subscriber_growth.data,
									borderColor: C.blue, backgroundColor: 'rgba(52,152,219,.15)',
									borderWidth: 2, pointRadius: 3, tension: 0.3, fill: true,
								},
							],
						},
						options: chartOpts( true ),
					} );
				} else {
					hideSpinner( 'fcc-an-growth-spinner' );
					if ( ge ) ge.parentNode.innerHTML = '<p class="fcc-an-empty">Not enough subscriber data yet.</p>';
				}
			} );
		}

		// ── Chart option helpers ────────────────────────────────────────
		function chartOpts( showLegend, scaleOverrides ) {
			var o = {
				responsive: true, maintainAspectRatio: true,
				plugins: { legend: { display: !! showLegend } },
				scales: Object.assign( {
					x: { ticks: { maxTicksLimit: 10 } },
					y: { beginAtZero: true, ticks: { precision: 0 } },
				}, scaleOverrides || {} ),
			};
			return o;
		}

		function indexOfMax( arr ) {
			var max = 0, idx = 0;
			for ( var i = 0; i < arr.length; i++ ) { if ( arr[i] > max ) { max = arr[i]; idx = i; } }
			return idx;
		}

		// ── Per-card filter pills ───────────────────────────────────────
		wrap.addEventListener( 'click', function ( e ) {
			var pill = e.target.closest( '.fcc-an-card-pill' );
			if ( ! pill ) return;
			var card  = pill.closest( '.fcc-an-chart-card' );
			var chart = card ? card.dataset.chart : '';
			var days  = pill.dataset.days;
			var dp    = card.querySelector( '.fcc-an-card-datepicker' );

			if ( days === 'custom' ) {
				if ( dp ) dp.hidden = ! dp.hidden;
				return;
			}

			// Update active pill.
			card.querySelectorAll( '.fcc-an-card-pill' ).forEach( function ( p ) { p.classList.remove( 'fcc-an-card-pill--active' ); } );
			pill.classList.add( 'fcc-an-card-pill--active' );
			if ( dp ) dp.hidden = true;

			var r = parseInt( days, 10 );
			if ( chart === 'volume' ) loadVolumeChart( r );
			else if ( chart === 'foods' ) loadFoodsChart( r );
		} );

		// ── Custom date apply ───────────────────────────────────────────
		wrap.addEventListener( 'click', function ( e ) {
			var applyBtn = e.target.closest( '.fcc-an-date-apply' );
			if ( ! applyBtn ) return;
			var dp    = applyBtn.closest( '.fcc-an-card-datepicker' );
			var card  = applyBtn.closest( '.fcc-an-chart-card' );
			var chart = card ? card.dataset.chart : '';
			var from  = dp.querySelector( '[data-role="from"]' ).value;
			var to    = dp.querySelector( '[data-role="to"]' ).value;
			if ( ! from || ! to ) return;

			card.querySelectorAll( '.fcc-an-card-pill' ).forEach( function ( p ) { p.classList.remove( 'fcc-an-card-pill--active' ); } );
			card.querySelector( '.fcc-an-card-pill--custom' ).classList.add( 'fcc-an-card-pill--active' );

			if ( chart === 'volume' ) loadVolumeChart( -1, from, to );
			else if ( chart === 'foods' ) loadFoodsChart( -1, from, to );
		} );

		// ── CSV export buttons ──────────────────────────────────────────
		wrap.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.fcc-an-export-btn' );
			if ( ! btn ) return;
			var section = btn.dataset.section;
			if ( ! section ) return;
			window.location.href = fccAnalytics.adminPostUrl
				+ '?action=fcc_export_analytics_csv'
				+ '&section=' + encodeURIComponent( section )
				+ '&_wpnonce=' + encodeURIComponent( csvNonce );
		} );

		// ── Init ────────────────────────────────────────────────────────
		var startTab = wrap.dataset.tab || 'overview';
		loadTabData( startTab );
	} );
}() );
