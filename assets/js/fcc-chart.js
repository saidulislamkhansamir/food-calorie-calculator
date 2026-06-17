/**
 * Food Calorie Calculator — Macro Pie/Donut Chart
 *
 * Pure vanilla JS, Canvas API. No CDN. No jQuery.
 * Exported to window.FccChart for use by fcc-calculator.js.
 */
( function ( global ) {
	'use strict';

	const COLORS = {
		protein: '#2D7A4F',
		carbs:   '#6DBF67',
		fat:     '#F47B20',
	};

	/**
	 * Draw a donut chart on a canvas element.
	 *
	 * @param {HTMLCanvasElement} canvas
	 * @param {{ protein: number, carbs: number, fat: number }} data  kcal values
	 * @param {HTMLElement}       legendEl  Element to populate with a legend
	 * @param {Object}            i18n      { protein, carbs, fat } labels
	 */
	function draw( canvas, data, legendEl, i18n ) {
		const total = data.protein + data.carbs + data.fat;
		if ( ! canvas || total <= 0 ) return;

		const ctx = canvas.getContext( '2d' );
		const dpr = window.devicePixelRatio || 1;
		const size = canvas.offsetWidth || 220;

		canvas.width  = size * dpr;
		canvas.height = size * dpr;
		canvas.style.width  = size + 'px';
		canvas.style.height = size + 'px';
		ctx.scale( dpr, dpr );

		const cx    = size / 2;
		const cy    = size / 2;
		const outer = size / 2 - 8;
		const inner = outer * 0.55; // Donut hole ratio

		ctx.clearRect( 0, 0, size, size );

		const segments = [
			{ key: 'protein', value: data.protein, color: COLORS.protein, label: i18n.protein },
			{ key: 'carbs',   value: data.carbs,   color: COLORS.carbs,   label: i18n.carbs   },
			{ key: 'fat',     value: data.fat,      color: COLORS.fat,     label: i18n.fat     },
		].filter( s => s.value > 0 );

		let startAngle = -Math.PI / 2;

		segments.forEach( function ( seg ) {
			const slice = ( seg.value / total ) * 2 * Math.PI;
			ctx.beginPath();
			ctx.moveTo( cx, cy );
			ctx.arc( cx, cy, outer, startAngle, startAngle + slice );
			ctx.closePath();
			ctx.fillStyle = seg.color;
			ctx.fill();
			startAngle += slice;
		} );

		// Donut hole
		ctx.beginPath();
		ctx.arc( cx, cy, inner, 0, 2 * Math.PI );
		ctx.fillStyle = getComputedStyle( canvas.closest( '.fcc-calculator' ) || document.body )
			.getPropertyValue( '--fcc-surface' ).trim() || '#ffffff';
		ctx.fill();

		// Centre text
		ctx.fillStyle = getComputedStyle( canvas.closest( '.fcc-calculator' ) || document.body )
			.getPropertyValue( '--fcc-primary' ).trim() || '#005EB8';
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.font = 'bold ' + Math.round( inner * 0.32 ) + 'px sans-serif';
		ctx.fillText( Math.round( total ) + ' kcal', cx, cy );

		// Legend
		if ( legendEl ) {
			legendEl.innerHTML = segments.map( function ( seg ) {
				const pct = ( ( seg.value / total ) * 100 ).toFixed( 0 );
				return '<div class="fcc-macro-legend-item">' +
					'<span class="fcc-macro-legend-dot" style="background:' + seg.color + '"></span>' +
					'<span>' + escHtml( seg.label ) + ' ' + pct + '%</span>' +
					'</div>';
			} ).join( '' );
		}
	}

	function escHtml( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	global.FccChart = { draw: draw };

}( window ) );
