<?php
/**
 * Food Requests — grouped table inner region (AJAX-replaced).
 *
 * Required variables from caller:
 *   array  $requests    — from Database::get_food_requests_grouped()
 *   int    $paged
 *   int    $total_pages
 *   int    $total
 *   int    $per_page
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
	<div class="fcc-reqs-empty">
		<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
		<p><?php esc_html_e( 'No requests found.', 'food-calorie-calculator' ); ?></p>
	</div>
<?php else : ?>
	<div class="fcc-reqs-table-wrap">

		<!-- Top pagination bar -->
		<div class="fcc-reqs-table-footer fcc-reqs-table-header">
			<p class="fcc-reqs-count">
				<?php if ( $total > $per_page ) :
					$from = ( $paged - 1 ) * $per_page + 1;
					$to   = min( $paged * $per_page, $total );
					printf( esc_html__( 'Showing %1$d–%2$d of %3$d', 'food-calorie-calculator' ), $from, $to, $total );
				else :
					printf( esc_html( _n( '%d request', '%d requests', $total, 'food-calorie-calculator' ) ), $total );
				endif; ?>
			</p>
			<?php echo fcc_build_reqs_pagination( $paged, $total_pages ); ?>
		</div>

		<table class="fcc-reqs-table">
			<thead>
				<tr>
					<th class="fcc-reqs-th--num">#</th>
					<th class="fcc-reqs-th--name"><?php esc_html_e( 'Food Name', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--count"><?php esc_html_e( 'Requests', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--date"><?php esc_html_e( 'Last Requested', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--status"><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--actions"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$status_map = [
					'pending'   => [ 'label' => __( 'Pending',   'food-calorie-calculator' ), 'cls' => 'fcc-reqs-status--pending' ],
					'done'      => [ 'label' => __( 'Added',     'food-calorie-calculator' ), 'cls' => 'fcc-reqs-status--done' ],
					'dismissed' => [ 'label' => __( 'Dismissed', 'food-calorie-calculator' ), 'cls' => 'fcc-reqs-status--dismissed' ],
				];
				$row_num = ( $paged - 1 ) * $per_page + 1;
				foreach ( $requests as $req ) :
					$gs      = $req['group_status'] ?? 'pending';
					$s       = $status_map[ $gs ] ?? $status_map['pending'];
					$note    = $req['latest_note'] ?? '';
					$note_short = mb_strlen( $note ) > 60 ? mb_substr( $note, 0, 60 ) . '…' : $note;
					$has_more   = mb_strlen( $note ) > 60;
				?>
				<tr class="fcc-reqs-row<?php echo 'pending' === $gs ? ' fcc-reqs-row--pending' : ''; ?>">
					<td class="fcc-reqs-td--num">
						<span class="fcc-reqs-rownum"><?php echo (int) $row_num; ?></span>
					</td>
					<td class="fcc-reqs-td--name">
						<strong><?php echo esc_html( $req['food_name'] ); ?></strong>
						<?php if ( $note ) : ?>
							<div class="fcc-reqs-note">
								<svg class="fcc-reqs-note__icon" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
								<span class="fcc-reqs-note__text">
									<span class="fcc-reqs-note__short"><?php echo esc_html( $note_short ); ?></span>
									<?php if ( $has_more ) : ?>
										<span class="fcc-reqs-note__full" hidden><?php echo esc_html( $note ); ?></span>
									<?php endif; ?>
								</span>
								<?php if ( $has_more ) : ?>
									<button type="button" class="fcc-reqs-note-toggle"><?php esc_html_e( 'More', 'food-calorie-calculator' ); ?> &#x2193;</button>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</td>
					<td class="fcc-reqs-td--count">
						<span class="fcc-reqs-count-badge"><?php echo (int) $req['request_count']; ?>&times;</span>
					</td>
					<td class="fcc-reqs-td--date">
						<?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $req['last_requested'] ) ) ); ?>
					</td>
					<td class="fcc-reqs-td--status">
						<span class="fcc-reqs-status <?php echo esc_attr( $s['cls'] ); ?>"><?php echo esc_html( $s['label'] ); ?></span>
					</td>
					<td class="fcc-reqs-td--actions">
						<div class="fcc-reqs-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods' ) ); ?>"
								target="_blank"
								rel="noopener"
								class="fcc-reqs-btn fcc-reqs-btn--add">
								<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
								<?php esc_html_e( 'Add to DB', 'food-calorie-calculator' ); ?>
							</a>
							<?php if ( 'pending' === $gs || 'dismissed' === $gs ) : ?>
								<button type="button"
									class="fcc-reqs-btn fcc-reqs-btn--mark-added fcc-reqs-group-btn"
									data-action="mark_added"
									data-food="<?php echo esc_attr( $req['food_name'] ); ?>">
									<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
									<?php esc_html_e( 'Mark Added', 'food-calorie-calculator' ); ?>
								</button>
							<?php endif; ?>
							<?php if ( 'pending' === $gs ) : ?>
								<button type="button"
									class="fcc-reqs-btn fcc-reqs-btn--dismiss fcc-reqs-group-btn"
									data-action="dismiss"
									data-food="<?php echo esc_attr( $req['food_name'] ); ?>">
									<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
									<?php esc_html_e( 'Dismiss', 'food-calorie-calculator' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</td>
				</tr>
				<?php $row_num++; endforeach; ?>
			</tbody>
		</table>

		<div class="fcc-reqs-table-footer">
			<p class="fcc-reqs-count">
				<?php if ( $total > $per_page ) :
					$from = ( $paged - 1 ) * $per_page + 1;
					$to   = min( $paged * $per_page, $total );
					printf( esc_html__( 'Showing %1$d–%2$d of %3$d', 'food-calorie-calculator' ), $from, $to, $total );
				else :
					printf( esc_html( _n( '%d request', '%d requests', $total, 'food-calorie-calculator' ) ), $total );
				endif; ?>
			</p>
			<?php echo fcc_build_reqs_pagination( $paged, $total_pages ); ?>
		</div>

	</div><!-- .fcc-reqs-table-wrap -->
<?php endif; ?>
