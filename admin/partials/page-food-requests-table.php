<?php
/**
 * Admin: Food Requests table + pagination inner region.
 *
 * Required variables from caller:
 *   array $requests, int $paged, int $total_pages, int $total, int $per_page
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'fcc_build_reqs_pagination' ) ) :
	function fcc_build_reqs_pagination( int $paged, int $total_pages ): string {
		if ( $total_pages <= 1 ) {
			return '';
		}

		$show = [];
		for ( $i = 1; $i <= $total_pages; $i++ ) {
			if ( 1 === $i || $i === $total_pages || abs( $i - $paged ) <= 2 ) {
				$show[] = $i;
			}
		}
		$pages = [];
		$prev  = null;
		foreach ( $show as $p ) {
			if ( null !== $prev && $p - $prev > 1 ) {
				$pages[] = null;
			}
			$pages[] = $p;
			$prev    = $p;
		}

		$out = '<div class="fcc-reqs-pagination">';

		if ( $paged > 1 ) {
			$out .= sprintf(
				'<button type="button" class="fcc-reqs-page-btn fcc-reqs-page-btn--nav" data-page="%d" aria-label="Previous">&#8249;</button>',
				$paged - 1
			);
		} else {
			$out .= '<span class="fcc-reqs-page-btn fcc-reqs-page-btn--nav fcc-reqs-page-btn--disabled" aria-disabled="true">&#8249;</span>';
		}

		foreach ( $pages as $p ) {
			if ( null === $p ) {
				$out .= '<span class="fcc-reqs-page-btn fcc-reqs-page-btn--ellipsis" aria-hidden="true">&#8230;</span>';
			} else {
				$out .= sprintf(
					'<button type="button" class="fcc-reqs-page-btn%s" data-page="%d">%d</button>',
					$p === $paged ? ' fcc-reqs-page-btn--active' : '',
					$p,
					$p
				);
			}
		}

		if ( $paged < $total_pages ) {
			$out .= sprintf(
				'<button type="button" class="fcc-reqs-page-btn fcc-reqs-page-btn--nav" data-page="%d" aria-label="Next">&#8250;</button>',
				$paged + 1
			);
		} else {
			$out .= '<span class="fcc-reqs-page-btn fcc-reqs-page-btn--nav fcc-reqs-page-btn--disabled" aria-disabled="true">&#8250;</span>';
		}

		$out .= '</div>';
		return $out;
	}
endif;
?>
<?php if ( empty( $requests ) ) : ?>
	<div class="fcc-card fcc-reqs-card">
		<div class="fcc-reqs-empty">
			<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
			<p><?php esc_html_e( 'No requests found.', 'food-calorie-calculator' ); ?></p>
		</div>
	</div>
<?php else : ?>
	<div class="fcc-card fcc-reqs-card">
		<table class="fcc-reqs-table">
			<thead>
				<tr>
					<th class="fcc-reqs-th--name"><?php esc_html_e( 'Food Name', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--note"><?php esc_html_e( 'Note', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--email"><?php esc_html_e( 'Email', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--optin"><?php esc_html_e( 'Newsletter', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--status"><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--date"><?php esc_html_e( 'Date', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--actions"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$status_map = [
					'pending'   => [ 'label' => __( 'Pending', 'food-calorie-calculator' ),   'cls' => 'fcc-reqs-status--pending' ],
					'done'      => [ 'label' => __( 'Done', 'food-calorie-calculator' ),      'cls' => 'fcc-reqs-status--done' ],
					'dismissed' => [ 'label' => __( 'Dismissed', 'food-calorie-calculator' ), 'cls' => 'fcc-reqs-status--dismissed' ],
				];
				foreach ( $requests as $req ) :
					$is_pending = 'pending' === $req['status'];
					$s          = $status_map[ $req['status'] ] ?? $status_map['pending'];
				?>
				<tr class="fcc-reqs-row<?php echo $is_pending ? ' fcc-reqs-row--pending' : ''; ?>">
					<td class="fcc-reqs-td--name">
						<strong><?php echo esc_html( $req['food_name'] ); ?></strong>
					</td>
					<td class="fcc-reqs-td--note">
						<?php echo esc_html( $req['note'] ?: '—' ); ?>
					</td>
					<td class="fcc-reqs-td--email">
						<?php if ( ! empty( $req['requester_email'] ) ) : ?>
							<a href="mailto:<?php echo esc_attr( $req['requester_email'] ); ?>"><?php echo esc_html( $req['requester_email'] ); ?></a>
						<?php else : ?>
							<span class="fcc-reqs-muted">—</span>
						<?php endif; ?>
					</td>
					<td class="fcc-reqs-td--optin">
						<?php if ( ! empty( $req['marketing_optin'] ) ) : ?>
							<span class="fcc-reqs-optin-yes" title="<?php esc_attr_e( 'Opted in', 'food-calorie-calculator' ); ?>">&#10003;</span>
						<?php else : ?>
							<span class="fcc-reqs-muted">—</span>
						<?php endif; ?>
					</td>
					<td class="fcc-reqs-td--status">
						<span class="fcc-reqs-status <?php echo esc_attr( $s['cls'] ); ?>"><?php echo esc_html( $s['label'] ); ?></span>
					</td>
					<td class="fcc-reqs-td--date">
						<?php echo esc_html( date_i18n( 'd M Y', strtotime( $req['created_at'] ) ) ); ?>
					</td>
					<td class="fcc-reqs-td--actions">
						<div class="fcc-reqs-actions">
							<?php if ( $is_pending ) : ?>
								<button type="button"
									class="fcc-reqs-btn fcc-reqs-btn--done"
									data-action="done"
									data-id="<?php echo (int) $req['id']; ?>"
									title="<?php esc_attr_e( 'Mark as done', 'food-calorie-calculator' ); ?>">
									<?php esc_html_e( 'Done', 'food-calorie-calculator' ); ?>
								</button>
								<button type="button"
									class="fcc-reqs-btn fcc-reqs-btn--dismiss"
									data-action="dismiss"
									data-id="<?php echo (int) $req['id']; ?>"
									title="<?php esc_attr_e( 'Dismiss', 'food-calorie-calculator' ); ?>">
									<?php esc_html_e( 'Dismiss', 'food-calorie-calculator' ); ?>
								</button>
							<?php endif; ?>
							<button type="button"
								class="fcc-reqs-btn fcc-reqs-btn--delete"
								data-action="delete"
								data-id="<?php echo (int) $req['id']; ?>"
								title="<?php esc_attr_e( 'Delete', 'food-calorie-calculator' ); ?>">
								<?php esc_html_e( 'Delete', 'food-calorie-calculator' ); ?>
							</button>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="fcc-reqs-table-footer">
			<p class="fcc-reqs-count">
				<?php if ( $total > $per_page ) :
					$from = ( $paged - 1 ) * $per_page + 1;
					$to   = min( $paged * $per_page, $total );
					/* translators: 1: first item, 2: last item, 3: total */
					printf( esc_html__( 'Showing %1$d–%2$d of %3$d', 'food-calorie-calculator' ), $from, $to, $total );
				else :
					/* translators: %d: count */
					printf( esc_html( _n( '%d request', '%d requests', $total, 'food-calorie-calculator' ) ), $total );
				endif; ?>
			</p>
			<?php echo fcc_build_reqs_pagination( $paged, $total_pages ); ?>
		</div>
	</div>
<?php endif; ?>
