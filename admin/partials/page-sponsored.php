<?php
/**
 * Admin partial: Sponsored Foods dashboard.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$sponsored   = \FCC\Database::get_sponsored_foods();
$ajax_nonce  = wp_create_nonce( 'fcc_ajax_sponsor' );
$status_labels = [
	'active'  => __( 'Active', 'food-calorie-calculator' ),
	'paused'  => __( 'Paused', 'food-calorie-calculator' ),
	'expired' => __( 'Expired', 'food-calorie-calculator' ),
];
?>
<div class="wrap fcc-admin-wrap">

	<div class="fcc-sp-hero">
		<div>
			<h1><?php esc_html_e( 'Sponsored Listings', 'food-calorie-calculator' ); ?></h1>
			<p><?php printf( esc_html__( '%d sponsored food(s) in the database', 'food-calorie-calculator' ), count( $sponsored ) ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods' ) ); ?>" class="button" style="background:rgba(255,255,255,.2);color:#fff;border-color:rgba(255,255,255,.5)">
			<?php esc_html_e( '+ Add Food', 'food-calorie-calculator' ); ?>
		</a>
	</div>

	<?php if ( empty( $sponsored ) ) : ?>
		<div class="fcc-an-section" style="text-align:center;padding:3rem">
			<p style="color:#888;font-size:1rem"><?php esc_html_e( 'No sponsored foods yet. Edit any food and enable the Sponsorship option to get started.', 'food-calorie-calculator' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods' ) ); ?>" class="button button-primary" style="margin-top:1rem">
				<?php esc_html_e( 'Browse Foods', 'food-calorie-calculator' ); ?>
			</a>
		</div>
	<?php else : ?>

	<div class="fcc-an-section">
		<h2><?php esc_html_e( 'All Sponsored Foods', 'food-calorie-calculator' ); ?></h2>
		<table class="widefat fcc-reqs-table fcc-sp-table">
			<thead>
				<tr>
					<th style="width:200px"><?php esc_html_e( 'Food', 'food-calorie-calculator' ); ?></th>
					<th style="width:160px"><?php esc_html_e( 'Brand', 'food-calorie-calculator' ); ?></th>
					<th style="width:80px"><?php esc_html_e( 'Logo', 'food-calorie-calculator' ); ?></th>
					<th style="width:100px"><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?></th>
					<th style="width:120px"><?php esc_html_e( 'Expires', 'food-calorie-calculator' ); ?></th>
					<th style="width:90px"><?php esc_html_e( 'Clicks (30d)', 'food-calorie-calculator' ); ?></th>
					<th style="width:220px"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $sponsored as $item ) :
				$status = $item['sponsor_status'];
				$logo_url = $item['sponsor_logo_id'] ? wp_get_attachment_url( (int) $item['sponsor_logo_id'] ) : '';
				$edit_url = admin_url( 'admin.php?page=fcc-foods&action=edit&food_id=' . $item['id'] );
			?>
				<tr id="fcc-sp-row-<?php echo esc_attr( $item['id'] ); ?>">
					<td>
						<strong><?php echo esc_html( $item['name'] ); ?></strong>
					</td>
					<td>
						<?php if ( $item['sponsor_url'] ) : ?>
							<a href="<?php echo esc_url( $item['sponsor_url'] ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( $item['sponsor_name'] ?: '—' ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html( $item['sponsor_name'] ?: '—' ); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $logo_url ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-height:36px;border:1px solid #eee;border-radius:4px;padding:2px">
						<?php else : ?>
							<span style="color:#ccc">—</span>
						<?php endif; ?>
					</td>
					<td>
						<span class="fcc-sp-status fcc-sp-status--<?php echo esc_attr( $status ); ?>">
							<?php echo esc_html( $status_labels[ $status ] ?? ucfirst( $status ) ); ?>
						</span>
					</td>
					<td>
						<?php echo $item['sponsor_expires_at']
							? esc_html( date_i18n( 'd M Y', strtotime( $item['sponsor_expires_at'] ) ) )
							: '<span style="color:#999">' . esc_html__( 'Never', 'food-calorie-calculator' ) . '</span>';
						?>
					</td>
					<td style="text-align:center;font-weight:700;color:#2D7A4F">
						<?php echo esc_html( $item['clicks_30d'] ); ?>
					</td>
					<td>
						<div style="display:flex;gap:.5rem;flex-wrap:nowrap;align-items:center">
							<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
							</a>
							<?php if ( 'expired' !== $status ) : ?>
							<button type="button"
							        class="button button-small fcc-sp-toggle"
							        data-food-id="<?php echo esc_attr( $item['id'] ); ?>"
							        data-active="<?php echo esc_attr( $item['sponsor_active'] ? '1' : '0' ); ?>"
							        data-nonce="<?php echo esc_attr( $ajax_nonce ); ?>">
								<?php echo $item['sponsor_active']
									? esc_html__( 'Pause', 'food-calorie-calculator' )
									: esc_html__( 'Activate', 'food-calorie-calculator' );
								?>
							</button>
							<?php endif; ?>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php endif; ?>

</div>

<script>
(function () {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

	document.querySelectorAll( '.fcc-sp-toggle' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var foodId  = this.dataset.foodId;
			var current = parseInt( this.dataset.active, 10 );
			var newVal  = current ? 0 : 1;
			var nonce   = this.dataset.nonce;
			var row     = document.getElementById( 'fcc-sp-row-' + foodId );
			var self    = this;
			self.disabled = true;

			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', ajaxUrl );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			xhr.onload = function () {
				self.disabled = false;
				try {
					var res = JSON.parse( xhr.responseText );
					if ( ! res.success ) return;
					self.dataset.active = newVal;
					self.textContent = newVal ? '<?php echo esc_js( __( 'Pause', 'food-calorie-calculator' ) ); ?>' : '<?php echo esc_js( __( 'Activate', 'food-calorie-calculator' ) ); ?>';
					// Update status badge.
					var badge = row ? row.querySelector( '.fcc-sp-status' ) : null;
					if ( badge ) {
						badge.className = 'fcc-sp-status fcc-sp-status--' + ( newVal ? 'active' : 'paused' );
						badge.textContent = newVal ? '<?php echo esc_js( __( 'Active', 'food-calorie-calculator' ) ); ?>' : '<?php echo esc_js( __( 'Paused', 'food-calorie-calculator' ) ); ?>';
					}
				} catch ( e ) {}
			};
			xhr.send(
				'action=fcc_sponsor_toggle' +
				'&_ajax_nonce=' + encodeURIComponent( nonce ) +
				'&food_id=' + encodeURIComponent( foodId ) +
				'&active=' + newVal
			);
		} );
	} );
}() );
</script>
