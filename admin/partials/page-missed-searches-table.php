<?php
/**
 * Missed Searches — table inner region (AJAX-replaced).
 *
 * Required variables from caller:
 *   array  $searches    — from Database::get_missed_searches()
 *   int    $paged
 *   int    $total_pages
 *   int    $total
 *   int    $per_page
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'fcc_build_ms_pagination' ) ) :
	function fcc_build_ms_pagination( int $paged, int $total_pages ): string {
		if ( $total_pages <= 1 ) return '';

		$show = [];
		for ( $i = 1; $i <= $total_pages; $i++ ) {
			if ( 1 === $i || $i === $total_pages || abs( $i - $paged ) <= 2 ) {
				$show[] = $i;
			}
		}
		$pages = [];
		$prev  = null;
		foreach ( $show as $p ) {
			if ( null !== $prev && $p - $prev > 1 ) $pages[] = null;
			$pages[] = $p;
			$prev    = $p;
		}

		$out = '<div class="fcc-reqs-pagination">';
		if ( $paged > 1 ) {
			$out .= sprintf( '<button type="button" class="fcc-reqs-page-btn fcc-reqs-page-btn--nav fcc-ms-page-btn" data-page="%d" aria-label="Previous">&#8249;</button>', $paged - 1 );
		} else {
			$out .= '<span class="fcc-reqs-page-btn fcc-reqs-page-btn--nav fcc-reqs-page-btn--disabled">&#8249;</span>';
		}
		foreach ( $pages as $p ) {
			if ( null === $p ) {
				$out .= '<span class="fcc-reqs-page-btn fcc-reqs-page-btn--ellipsis">&#8230;</span>';
			} else {
				$out .= sprintf(
					'<button type="button" class="fcc-reqs-page-btn fcc-ms-page-btn%s" data-page="%d">%d</button>',
					$p === $paged ? ' fcc-reqs-page-btn--active' : '',
					$p, $p
				);
			}
		}
		if ( $paged < $total_pages ) {
			$out .= sprintf( '<button type="button" class="fcc-reqs-page-btn fcc-reqs-page-btn--nav fcc-ms-page-btn" data-page="%d" aria-label="Next">&#8250;</button>', $paged + 1 );
		} else {
			$out .= '<span class="fcc-reqs-page-btn fcc-reqs-page-btn--nav fcc-reqs-page-btn--disabled">&#8250;</span>';
		}
		$out .= '</div>';
		return $out;
	}
endif;
?>
<?php if ( empty( $searches ) ) : ?>
	<div class="fcc-reqs-empty">
		<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
		<p><?php esc_html_e( 'No missed searches found.', 'food-calorie-calculator' ); ?></p>
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
					printf( esc_html( _n( '%d query', '%d queries', $total, 'food-calorie-calculator' ) ), $total );
				endif; ?>
			</p>
			<?php echo fcc_build_ms_pagination( $paged, $total_pages ); ?>
		</div>

		<table class="fcc-reqs-table">
			<thead>
				<tr>
					<th class="fcc-reqs-th--num">#</th>
					<th class="fcc-reqs-th--name"><?php esc_html_e( 'Search Query', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--count"><?php esc_html_e( 'Times Searched', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--date"><?php esc_html_e( 'Last Searched', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--status"><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-reqs-th--actions"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$status_map = [
					'active'    => [ 'label' => __( 'Active',    'food-calorie-calculator' ), 'cls' => 'fcc-reqs-status--pending' ],
					'done'      => [ 'label' => __( 'Added',     'food-calorie-calculator' ), 'cls' => 'fcc-reqs-status--done' ],
					'dismissed' => [ 'label' => __( 'Dismissed', 'food-calorie-calculator' ), 'cls' => 'fcc-reqs-status--dismissed' ],
				];
				$row_num = ( $paged - 1 ) * $per_page + 1;
				foreach ( $searches as $ms ) :
					$s    = $status_map[ $ms['status'] ] ?? $status_map['active'];
					$is_hot = (int) $ms['search_count'] >= 5 && 'active' === $ms['status'];
				?>
				<tr class="fcc-reqs-row<?php echo 'active' === $ms['status'] ? ' fcc-reqs-row--pending' : ''; ?>">
					<td class="fcc-reqs-td--num">
						<span class="fcc-reqs-rownum"><?php echo (int) $row_num; ?></span>
					</td>
					<td class="fcc-reqs-td--name">
						<strong>
							<?php echo esc_html( $ms['query'] ); ?>
							<?php if ( $is_hot ) : ?>
								<span class="fcc-reqs-hot" title="<?php esc_attr_e( 'Searched 5+ times — high priority', 'food-calorie-calculator' ); ?>">&#x1F525; <?php esc_html_e( 'Hot', 'food-calorie-calculator' ); ?></span>
							<?php endif; ?>
						</strong>
					</td>
					<td class="fcc-reqs-td--count">
						<span class="fcc-reqs-count-badge fcc-reqs-count-badge--search"><?php echo (int) $ms['search_count']; ?>&times;</span>
					</td>
					<td class="fcc-reqs-td--date">
						<?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $ms['last_searched_at'] ) ) ); ?>
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
							<?php if ( 'active' === $ms['status'] || 'dismissed' === $ms['status'] ) : ?>
								<button type="button"
									class="fcc-reqs-btn fcc-reqs-btn--mark-added fcc-ms-action-btn"
									data-action="mark_added"
									data-id="<?php echo (int) $ms['id']; ?>">
									<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
									<?php esc_html_e( 'Mark Added', 'food-calorie-calculator' ); ?>
								</button>
							<?php endif; ?>
							<?php if ( 'active' === $ms['status'] ) : ?>
								<button type="button"
									class="fcc-reqs-btn fcc-reqs-btn--dismiss fcc-ms-action-btn"
									data-action="dismiss"
									data-id="<?php echo (int) $ms['id']; ?>">
									<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
									<?php esc_html_e( 'Dismiss', 'food-calorie-calculator' ); ?>
								</button>
							<?php endif; ?>
							<button type="button"
								class="fcc-reqs-btn fcc-reqs-btn--delete fcc-ms-action-btn"
								data-action="delete"
								data-id="<?php echo (int) $ms['id']; ?>"
								title="<?php esc_attr_e( 'Delete', 'food-calorie-calculator' ); ?>">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
							</button>
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
					printf( esc_html( _n( '%d query', '%d queries', $total, 'food-calorie-calculator' ) ), $total );
				endif; ?>
			</p>
			<?php echo fcc_build_ms_pagination( $paged, $total_pages ); ?>
		</div>

	</div><!-- .fcc-reqs-table-wrap -->
<?php endif; ?>
