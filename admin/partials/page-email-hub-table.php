<?php
/**
 * Admin partial: Email Hub — table region (AJAX-replaceable).
 * Expects: $subscribers, $total, $total_pages, $paged.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( empty( $subscribers ) ) : ?>
	<div class="fcc-eh-empty">
		<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
		<p><?php esc_html_e( 'No subscribers found matching your filters.', 'food-calorie-calculator' ); ?></p>
	</div>
<?php else : ?>

<?php if ( $total > 0 ) : ?>
<div class="fcc-eh-tablenav">
	<span class="fcc-eh-count">
		<?php printf(
			esc_html__( 'Showing %1$d–%2$d of %3$d', 'food-calorie-calculator' ),
			( $paged - 1 ) * 20 + 1,
			min( $paged * 20, $total ),
			$total
		); ?>
	</span>
	<?php if ( $total_pages > 1 ) : ?>
		<div class="fcc-eh-pager">
			<?php for ( $i = 1; $i <= min( $total_pages, 20 ); $i++ ) : ?>
				<button class="fcc-eh-page-btn<?php echo $i === $paged ? ' fcc-eh-page-btn--active' : ''; ?>"
					data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
			<?php endfor; ?>
		</div>
	<?php endif; ?>
</div>
<?php endif; ?>

<div class="fcc-eh-table-wrap">
	<table class="fcc-eh-table">
		<thead>
			<tr>
				<th class="fcc-eh-th--num">#</th>
				<th><a href="#" class="fcc-eh-sort" data-col="requester_email"><?php esc_html_e( 'Email', 'food-calorie-calculator' ); ?> ↕</a></th>
				<th><a href="#" class="fcc-eh-sort" data-col="food_name"><?php esc_html_e( 'Food Requested', 'food-calorie-calculator' ); ?> ↕</a></th>
				<th><?php esc_html_e( 'Note', 'food-calorie-calculator' ); ?></th>
				<th><a href="#" class="fcc-eh-sort" data-col="status"><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?> ↕</a></th>
				<th><a href="#" class="fcc-eh-sort" data-col="created_at"><?php esc_html_e( 'Date', 'food-calorie-calculator' ); ?> ↕</a></th>
				<th class="fcc-eh-th--actions"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php $row_num = ( $paged - 1 ) * 20 + 1; ?>
		<?php foreach ( $subscribers as $sub ) : ?>
			<tr class="fcc-eh-row">
				<td class="fcc-eh-td--num"><?php echo $row_num++; ?></td>
				<td class="fcc-eh-td--email">
					<a href="mailto:<?php echo esc_attr( $sub['requester_email'] ); ?>" class="fcc-eh-email-link">
						<?php echo esc_html( $sub['requester_email'] ); ?>
					</a>
				</td>
				<td class="fcc-eh-td--food"><?php echo esc_html( $sub['food_name'] ); ?></td>
				<td class="fcc-eh-td--note"><?php echo esc_html( mb_strimwidth( $sub['note'] ?? '', 0, 50, '…' ) ); ?></td>
				<td>
					<span class="fcc-reqs-status fcc-reqs-status--<?php echo esc_attr( $sub['status'] ); ?>">
						<?php echo esc_html( ucfirst( $sub['status'] === 'done' ? 'added' : $sub['status'] ) ); ?>
					</span>
				</td>
				<td class="fcc-eh-td--date"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $sub['created_at'] ) ) ); ?></td>
				<td class="fcc-eh-td--actions">
					<button type="button" class="fcc-eh-copy-btn" data-email="<?php echo esc_attr( $sub['requester_email'] ); ?>"
						title="<?php esc_attr_e( 'Copy email', 'food-calorie-calculator' ); ?>">📋</button>
					<button type="button" class="fcc-eh-delete-btn" data-id="<?php echo absint( $sub['id'] ); ?>"
						title="<?php esc_attr_e( 'Remove', 'food-calorie-calculator' ); ?>">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
					</button>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php endif; ?>
