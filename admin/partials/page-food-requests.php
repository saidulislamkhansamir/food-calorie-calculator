<?php
/**
 * Admin page: Food Requests.
 *
 * @package FCC
 */

use FCC\Database;

defined( 'ABSPATH' ) || exit;

$filter    = sanitize_key( $_GET['status'] ?? 'all' );
$allowed   = [ 'all', 'pending', 'done', 'dismissed' ];
$filter    = in_array( $filter, $allowed, true ) ? $filter : 'all';
$requests  = Database::get_food_requests( 'all' === $filter ? '' : $filter );
$pending   = Database::count_pending_requests();
$total     = count( Database::get_food_requests() );

$palette = [
	'primary' => '#2D7A4F',
	'tangerine' => '#F47B20',
	'lime'    => '#6DBF67',
	'bg'      => '#F8FAF5',
	'dark'    => '#1E2A1E',
];
?>
<div class="wrap fcc-admin-wrap">

	<!-- Page header -->
	<div class="fcc-page-header">
		<div class="fcc-page-header__title">
			<h1><?php esc_html_e( 'Food Requests', 'food-calorie-calculator' ); ?></h1>
			<p class="fcc-page-header__sub"><?php esc_html_e( 'Foods users have requested to be added to the database.', 'food-calorie-calculator' ); ?></p>
		</div>
		<div class="fcc-page-header__meta">
			<?php if ( $pending > 0 ) : ?>
				<span class="fcc-reqs-badge fcc-reqs-badge--pending"><?php echo esc_html( $pending ); ?> <?php esc_html_e( 'pending', 'food-calorie-calculator' ); ?></span>
			<?php else : ?>
				<span class="fcc-reqs-badge fcc-reqs-badge--clear"><?php esc_html_e( 'All clear', 'food-calorie-calculator' ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Filter tabs -->
	<div class="fcc-reqs-filters">
		<?php
		$tabs = [
			'all'       => __( 'All', 'food-calorie-calculator' ),
			'pending'   => __( 'Pending', 'food-calorie-calculator' ),
			'done'      => __( 'Done', 'food-calorie-calculator' ),
			'dismissed' => __( 'Dismissed', 'food-calorie-calculator' ),
		];
		foreach ( $tabs as $key => $label ) :
			$active = $filter === $key ? ' fcc-reqs-tab--active' : '';
			$url    = admin_url( 'admin.php?page=fcc-food-requests&status=' . $key );
		?>
			<a href="<?php echo esc_url( $url ); ?>" class="fcc-reqs-tab<?php echo esc_attr( $active ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<!-- Requests table -->
	<div class="fcc-card fcc-reqs-card">
		<?php if ( empty( $requests ) ) : ?>
			<div class="fcc-reqs-empty">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr( $palette['primary'] ); ?>" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				<p><?php esc_html_e( 'No requests found.', 'food-calorie-calculator' ); ?></p>
			</div>
		<?php else : ?>
			<table class="fcc-reqs-table">
				<thead>
					<tr>
						<th class="fcc-reqs-th--name"><?php esc_html_e( 'Food Name', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-reqs-th--note"><?php esc_html_e( 'Note', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-reqs-th--email"><?php esc_html_e( 'Email', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-reqs-th--status"><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-reqs-th--date"><?php esc_html_e( 'Date', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-reqs-th--actions"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
					</tr>
				</thead>
				<tbody id="fcc-reqs-tbody">
					<?php foreach ( $requests as $req ) :
						$is_pending = 'pending' === $req['status'];
					?>
					<tr class="fcc-reqs-row<?php echo $is_pending ? ' fcc-reqs-row--pending' : ''; ?>" id="fcc-req-row-<?php echo (int) $req['id']; ?>">
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
						<td class="fcc-reqs-td--status">
							<?php
							$status_map = [
								'pending'   => [ 'label' => __( 'Pending', 'food-calorie-calculator' ),   'cls' => 'fcc-reqs-status--pending' ],
								'done'      => [ 'label' => __( 'Done', 'food-calorie-calculator' ),      'cls' => 'fcc-reqs-status--done' ],
								'dismissed' => [ 'label' => __( 'Dismissed', 'food-calorie-calculator' ), 'cls' => 'fcc-reqs-status--dismissed' ],
							];
							$s = $status_map[ $req['status'] ] ?? $status_map['pending'];
							?>
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
		<?php endif; ?>
	</div>

</div>

<script>
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.fcc-reqs-btn', function () {
		const $btn    = $( this );
		const action  = $btn.data( 'action' );
		const id      = $btn.data( 'id' );
		const $row    = $( '#fcc-req-row-' + id );

		if ( action === 'delete' ) {
			if ( ! window.confirm( fccAdmin.i18n.confirmDelete ) ) return;
		}

		const ajaxAction = {
			done:    'fcc_ajax_mark_request_done',
			dismiss: 'fcc_ajax_dismiss_request',
			delete:  'fcc_ajax_delete_request',
		}[ action ];

		if ( ! ajaxAction ) return;

		$btn.prop( 'disabled', true );

		$.post( fccAdmin.ajaxUrl, {
			action:      ajaxAction,
			request_id:  id,
			_ajax_nonce: fccAdmin.reqsNonce,
		}, function ( response ) {
			if ( response.success ) {
				if ( action === 'delete' ) {
					$row.fadeOut( 250, function () { $row.remove(); } );
				} else {
					// Reload to reflect new status + badge count.
					window.location.reload();
				}
			} else {
				$btn.prop( 'disabled', false );
				alert( ( response.data && response.data.message ) || fccAdmin.i18n.error );
			}
		} ).fail( function () {
			$btn.prop( 'disabled', false );
		} );
	} );
}( window.jQuery ) );
</script>
