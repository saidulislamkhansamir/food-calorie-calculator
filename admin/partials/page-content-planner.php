<?php
/**
 * Admin partial: Content Planner — priority table combining missed searches + food requests.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$items         = \FCC\Database::get_content_planner_items( 50 );
$planner_nonce = wp_create_nonce( 'fcc_ajax_planner' );
?>
<div class="wrap fcc-admin-wrap">

	<div class="fcc-cp-hero">
		<h1><?php esc_html_e( 'Content Planner', 'food-calorie-calculator' ); ?></h1>
		<p><?php esc_html_e( 'Priority score = (missed search count × 2) + food request count. Focus on the top items to close content gaps and maximise user satisfaction.', 'food-calorie-calculator' ); ?></p>
	</div>

	<div class="fcc-an-section">
		<h2><?php printf( esc_html__( 'Priority Items (%d)', 'food-calorie-calculator' ), count( $items ) ); ?></h2>

		<?php if ( empty( $items ) ) : ?>
			<p style="color:#888"><?php esc_html_e( 'No content gaps found. Your food database is comprehensive!', 'food-calorie-calculator' ); ?></p>
		<?php else : ?>
		<table class="widefat fcc-reqs-table" id="fcc-cp-table">
			<thead>
				<tr>
					<th style="width:64px"><?php esc_html_e( 'Score', 'food-calorie-calculator' ); ?></th>
					<th><?php esc_html_e( 'Item', 'food-calorie-calculator' ); ?></th>
					<th style="width:130px"><?php esc_html_e( 'Missed Searches', 'food-calorie-calculator' ); ?></th>
					<th style="width:120px"><?php esc_html_e( 'User Requests', 'food-calorie-calculator' ); ?></th>
					<th style="width:190px"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $items as $item ) :
				$score       = (int) $item['priority_score'];
				$score_class = $score >= 20 ? 'fcc-cp-score--high' : ( $score >= 10 ? 'fcc-cp-score--med' : '' );
				$ms_id       = (int) ( $item['ms_id'] ?? 0 );
				$row_id      = $ms_id > 0 ? $ms_id : esc_attr( $item['item_name'] );
			?>
				<tr id="fcc-cp-row-<?php echo esc_attr( $row_id ); ?>">
					<td>
						<span class="fcc-cp-score <?php echo esc_attr( $score_class ); ?>"><?php echo esc_html( $score ); ?></span>
					</td>
					<td>
						<strong><?php echo esc_html( $item['item_name'] ); ?></strong>
						<?php if ( $score >= 10 ) : ?>
							<span class="fcc-reqs-hot"><?php esc_html_e( 'Hot', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( (int) $item['missed_count'] ); ?></td>
					<td><?php echo esc_html( (int) $item['request_count'] ); ?></td>
					<td>
						<div style="display:flex;gap:.5rem;flex-wrap:nowrap;align-items:center">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods&action=add&prefill=' . urlencode( $item['item_name'] ) ) ); ?>"
							   class="button button-primary button-small">
								<?php esc_html_e( 'Add Food', 'food-calorie-calculator' ); ?>
							</a>
							<?php if ( $ms_id > 0 ) : ?>
							<button type="button"
							        class="fcc-cp-dismiss-btn"
							        data-ms-id="<?php echo esc_attr( $ms_id ); ?>"
							        data-nonce="<?php echo esc_attr( $planner_nonce ); ?>">
								<?php esc_html_e( 'Dismiss', 'food-calorie-calculator' ); ?>
							</button>
							<?php endif; ?>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

</div>

<script>
(function () {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

	document.querySelectorAll( '.fcc-cp-dismiss-btn' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var msId  = this.dataset.msId;
			var nonce = this.dataset.nonce;
			var row   = document.getElementById( 'fcc-cp-row-' + msId );
			btn.disabled = true;
			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', ajaxUrl );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			xhr.onload = function () {
				try {
					var res = JSON.parse( xhr.responseText );
					if ( res.success && row ) {
						row.style.transition = 'opacity .3s';
						row.style.opacity    = '0';
						setTimeout( function () { row.remove(); }, 310 );
					} else {
						btn.disabled = false;
					}
				} catch ( e ) {
					btn.disabled = false;
				}
			};
			xhr.send(
				'action=fcc_planner_dismiss' +
				'&_ajax_nonce=' + encodeURIComponent( nonce ) +
				'&ms_id=' + encodeURIComponent( msId )
			);
		} );
	} );
}() );
</script>
