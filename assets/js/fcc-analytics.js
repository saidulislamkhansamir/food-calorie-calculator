/* global fccAnalytics, Chart */
(function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var wrap = document.querySelector( '.fcc-an-page' );
		if ( ! wrap ) { return; }

		var nonce = wrap.dataset.nonce;
		var range = parseInt( wrap.dataset.range, 10 ) || 30;

		function loadCharts() {
			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', fccAnalytics.ajaxUrl );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			xhr.onload = function () {
				if ( xhr.status !== 200 ) { return; }
				try {
					var res = JSON.parse( xhr.responseText );
					if ( ! res.success ) { return; }
					renderVolumeChart( res.data.volume );
					renderFoodsChart( res.data.foods );
				} catch ( e ) {}
			};
			xhr.send(
				'action=fcc_analytics_charts' +
				'&_ajax_nonce=' + encodeURIComponent( nonce ) +
				'&range=' + range
			);
		}

		function removeLoading( el ) {
			var loading = el.parentNode.querySelector( '.fcc-an-loading' );
			if ( loading ) { loading.remove(); }
		}

		function renderVolumeChart( volume ) {
			var el = document.getElementById( 'fcc-an-volume-chart' );
			if ( ! el ) { return; }
			removeLoading( el );
			new Chart( el, {
				type: 'line',
				data: {
					labels: volume.labels,
					datasets: [ {
						label: 'Searches',
						data: volume.data,
						borderColor: '#2D7A4F',
						backgroundColor: 'rgba(45,122,79,.1)',
						borderWidth: 2,
						pointRadius: 3,
						tension: 0.3,
						fill: true,
					} ],
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					plugins: { legend: { display: false } },
					scales: {
						x: { ticks: { maxTicksLimit: 10 } },
						y: { beginAtZero: true, ticks: { precision: 0 } },
					},
				},
			} );
		}

		function renderFoodsChart( foods ) {
			var el = document.getElementById( 'fcc-an-foods-chart' );
			if ( ! el ) { return; }
			removeLoading( el );
			new Chart( el, {
				type: 'bar',
				data: {
					labels: foods.labels,
					datasets: [ {
						label: 'Searches',
						data: foods.data,
						backgroundColor: 'rgba(45,122,79,.75)',
						borderColor: '#2D7A4F',
						borderWidth: 1,
					} ],
				},
				options: {
					indexAxis: 'y',
					responsive: true,
					maintainAspectRatio: true,
					plugins: { legend: { display: false } },
					scales: {
						x: { beginAtZero: true, ticks: { precision: 0 } },
					},
				},
			} );
		}

		loadCharts();
	} );
}() );
