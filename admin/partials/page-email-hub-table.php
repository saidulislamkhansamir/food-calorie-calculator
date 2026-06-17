<?php
/**
 * Admin partial: Email Hub — inner table region (PHP render + AJAX response).
 * Expects: $subscribers, $total, $total_pages, $paged, $food_filter.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( empty( $subscribers ) ) : ?>
	<p style="color:#888;padding:1rem 0"><?php esc_html_e( 'No opted-in subscribers found.', 'food-calorie-calculator' ); ?></p>
<?php else : ?>
<table class="widefat fcc-reqs-table" style="margin-top:0">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Email', 'food-calorie-calculator' ); ?></th>
			<th><?php esc_html_e( 'Food Requested', 'food-calorie-calculator' ); ?></th>
			<th><?php esc_html_e( 'Note', 'food-calorie-calculator' ); ?></th>
			<th><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?></th>
			<th><?php esc_html_e( 'Date', 'food-calorie-calculator' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $subscribers as $sub ) : ?>
		<tr>
			<td><?php echo esc_html( $sub['requester_email'] ); ?></td>
			<td><?php echo esc_html( $sub['food_name'] ); ?></td>
			<td style="max-width:200px;word-break:break-word"><?php echo esc_html( $sub['note'] ?? '' ); ?></td>
			<td>
				<span class="fcc-reqs-status fcc-reqs-status--<?php echo esc_attr( $sub['status'] ); ?>">
					<?php echo esc_html( ucfirst( $sub['status'] ) ); ?>
				</span>
			</td>
			<td><?php echo esc_html( date_i18n( 'd M Y', strtotime( $sub['created_at'] ) ) ); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<?php if ( $total_pages > 1 ) : ?>
<div style="margin-top:1rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
	<span style="color:#666;font-size:.85rem">
		<?php printf( esc_html__( 'Page %1$d of %2$d (%3$d total)', 'food-calorie-calculator' ), $paged, $total_pages, $total ); ?>
	</span>
	<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
		<button class="button fcc-eh-page-btn<?php echo $i === $paged ? ' button-primary' : ''; ?>"
		        data-page="<?php echo esc_attr( $i ); ?>">
			<?php echo esc_html( $i ); ?>
		</button>
	<?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>
