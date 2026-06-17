<?php
/**
 * White Label admin page.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$stats     = \FCC\Database::get_wl_stats();
$tiers     = \FCC\Database::get_wl_tier_breakdown();
$licenses  = \FCC\Database::get_wl_licenses( [ 'per_page' => 50 ] );
$expiring  = \FCC\Database::get_wl_expiring( 30 );
$nonce     = wp_create_nonce( 'fcc_wl_nonce' );
$ajax_url  = admin_url( 'admin-ajax.php' );
$site_url  = trailingslashit( get_site_url() );

$tier_labels = [
	'starter'      => 'Starter',
	'growth'       => 'Growth',
	'professional' => 'Professional',
	'enterprise'   => 'Enterprise',
];
$tier_prices = [
	'starter'      => 99,
	'growth'       => 149,
	'professional' => 199,
	'enterprise'   => 299,
];
$status_labels = [
	'trial'     => 'Trial',
	'active'    => 'Active',
	'suspended' => 'Suspended',
	'expired'   => 'Expired',
];
$biz_labels = [
	'nutrition_blog'    => 'Nutrition Blog',
	'gym_fitness'       => 'Gym / Fitness',
	'nhs_healthcare'    => 'NHS / Healthcare',
	'dietitian'         => 'Dietitian / Nutritionist',
	'corporate'         => 'Corporate Wellness',
	'education'         => 'Education',
	'other'             => 'Other',
];
?>
<div class="wrap fcc-admin-wrap">

  <!-- ===== HERO ===== -->
  <div class="fcc-wl-hero">
    <div class="fcc-wl-hero__inner">
      <div>
        <h1 class="fcc-wl-hero__title">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
          White Label Licensing
        </h1>
        <p class="fcc-wl-hero__sub">Offer the calculator as a branded embed — nutrition blogs, gyms, NHS wellness programmes &amp; more.</p>
      </div>
      <button type="button" class="fcc-wl-btn fcc-wl-btn--primary" id="fcc-wl-add-btn">
        + Add New License
      </button>
    </div>

    <!-- Stats bar -->
    <div class="fcc-wl-stats">
      <div class="fcc-wl-stat">
        <span class="fcc-wl-stat__val"><?php echo esc_html( $stats['total'] ); ?></span>
        <span class="fcc-wl-stat__lbl">Total Licenses</span>
      </div>
      <div class="fcc-wl-stat">
        <span class="fcc-wl-stat__val fcc-wl-stat__val--green"><?php echo esc_html( $stats['active'] ); ?></span>
        <span class="fcc-wl-stat__lbl">Active</span>
      </div>
      <div class="fcc-wl-stat">
        <span class="fcc-wl-stat__val fcc-wl-stat__val--amber"><?php echo esc_html( $stats['expiring_30d'] ); ?></span>
        <span class="fcc-wl-stat__lbl">Expiring (30d)</span>
      </div>
      <div class="fcc-wl-stat">
        <span class="fcc-wl-stat__val">£<?php echo number_format( $stats['mrr'], 2 ); ?></span>
        <span class="fcc-wl-stat__lbl">MRR</span>
      </div>
      <div class="fcc-wl-stat">
        <span class="fcc-wl-stat__val">£<?php echo number_format( $stats['arr'], 2 ); ?></span>
        <span class="fcc-wl-stat__lbl">ARR</span>
      </div>
    </div>
  </div>

  <!-- ===== TIER CARDS ===== -->
  <div class="fcc-wl-tiers">
    <?php foreach ( [ 'starter', 'growth', 'professional', 'enterprise' ] as $t ) :
      $tc = $tiers[ $t ] ?? [ 'count' => 0, 'arr' => 0 ];
    ?>
    <div class="fcc-wl-tier-card fcc-wl-tier-card--<?php echo esc_attr( $t ); ?>">
      <div class="fcc-wl-tier-card__name"><?php echo esc_html( $tier_labels[ $t ] ); ?></div>
      <div class="fcc-wl-tier-card__price">£<?php echo esc_html( $tier_prices[ $t ] ); ?>/yr</div>
      <div class="fcc-wl-tier-card__count"><?php echo esc_html( $tc['count'] ); ?> license<?php echo $tc['count'] !== 1 ? 's' : ''; ?></div>
      <div class="fcc-wl-tier-card__arr">£<?php echo number_format( $tc['arr'], 0 ); ?> ARR</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ===== ADD / EDIT FORM PANEL ===== -->
  <div class="fcc-wl-form-panel" id="fcc-wl-form-panel" style="display:none;">
    <div class="fcc-wl-form-panel__header">
      <h2 id="fcc-wl-form-title">Add New License</h2>
      <button type="button" class="fcc-wl-form-panel__close" id="fcc-wl-form-close" aria-label="Close">&times;</button>
    </div>

    <form id="fcc-wl-form" class="fcc-wl-form">
      <input type="hidden" name="id" id="fcc-wl-id" value="0">

      <div class="fcc-wl-form__grid">

        <!-- Client details -->
        <div class="fcc-wl-form__section">
          <h3>Client Details</h3>
          <div class="fcc-wl-form__row fcc-wl-form__row--2">
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-client-name">Client Name <span class="fcc-required">*</span></label>
              <input type="text" id="fcc-wl-client-name" name="client_name" required class="regular-text">
            </div>
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-client-email">Client Email <span class="fcc-required">*</span></label>
              <input type="email" id="fcc-wl-client-email" name="client_email" required class="regular-text">
            </div>
          </div>
          <div class="fcc-wl-form__row fcc-wl-form__row--2">
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-client-url">Client Website URL</label>
              <input type="url" id="fcc-wl-client-url" name="client_url" class="regular-text" placeholder="https://example.com">
            </div>
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-biz-type">Business Type</label>
              <select id="fcc-wl-biz-type" name="business_type" class="regular-text">
                <?php foreach ( $biz_labels as $bval => $blbl ) : ?>
                  <option value="<?php echo esc_attr( $bval ); ?>"><?php echo esc_html( $blbl ); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- License settings -->
        <div class="fcc-wl-form__section">
          <h3>License Settings</h3>
          <div class="fcc-wl-form__row fcc-wl-form__row--3">
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-tier">Tier</label>
              <select id="fcc-wl-tier" name="tier" class="regular-text">
                <option value="starter">Starter — £99/yr</option>
                <option value="growth">Growth — £149/yr</option>
                <option value="professional">Professional — £199/yr</option>
                <option value="enterprise">Enterprise — £299/yr</option>
              </select>
            </div>
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-status">Status</label>
              <select id="fcc-wl-status" name="status" class="regular-text">
                <option value="trial">Trial</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-expires">Expiry Date</label>
              <input type="date" id="fcc-wl-expires" name="expires_at" class="regular-text">
            </div>
          </div>
          <div class="fcc-wl-form__field">
            <label for="fcc-wl-domains">Allowed Domains <small id="fcc-wl-domain-hint">(one per line — Starter: max 1)</small></label>
            <textarea id="fcc-wl-domains" name="allowed_domains" rows="3" class="large-text" placeholder="example.com&#10;www.example.com"></textarea>
          </div>
        </div>

        <!-- Branding -->
        <div class="fcc-wl-form__section">
          <h3>Branding</h3>
          <div class="fcc-wl-form__row fcc-wl-form__row--2">
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-brand-name">Brand Name</label>
              <input type="text" id="fcc-wl-brand-name" name="brand_name" class="regular-text" placeholder="Acme Nutrition">
            </div>
            <div class="fcc-wl-form__field fcc-wl-tier-feature fcc-wl-tier-feature--growth">
              <label for="fcc-wl-logo-url">Logo URL <span class="fcc-wl-tier-badge">Growth+</span></label>
              <input type="url" id="fcc-wl-logo-url" name="logo_url" class="large-text" placeholder="https://example.com/logo.png">
            </div>
          </div>
          <div class="fcc-wl-form__row fcc-wl-form__row--2 fcc-wl-tier-feature fcc-wl-tier-feature--professional">
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-primary">Primary Colour <span class="fcc-wl-tier-badge">Pro+</span></label>
              <input type="color" id="fcc-wl-primary" name="primary_colour" value="#2D7A4F" class="fcc-wl-colour-input">
            </div>
            <div class="fcc-wl-form__field">
              <label for="fcc-wl-accent">Accent Colour <span class="fcc-wl-tier-badge">Pro+</span></label>
              <input type="color" id="fcc-wl-accent" name="accent_colour" value="#41B883" class="fcc-wl-colour-input">
            </div>
          </div>
          <div class="fcc-wl-form__field fcc-wl-tier-feature fcc-wl-tier-feature--professional">
            <label class="fcc-wl-checkbox-label">
              <input type="checkbox" id="fcc-wl-hide-powered" name="hide_powered_by" value="1">
              Hide "Powered by FCC" <span class="fcc-wl-tier-badge">Pro+</span>
            </label>
          </div>
          <div class="fcc-wl-form__field fcc-wl-tier-feature fcc-wl-tier-feature--enterprise">
            <label for="fcc-wl-css">Custom CSS <span class="fcc-wl-tier-badge">Enterprise</span></label>
            <textarea id="fcc-wl-css" name="custom_css" rows="4" class="large-text code" placeholder=".fcc-calculator { font-family: 'Inter', sans-serif; }"></textarea>
          </div>
        </div>

        <!-- Notes -->
        <div class="fcc-wl-form__section">
          <div class="fcc-wl-form__field">
            <label for="fcc-wl-notes">Internal Notes</label>
            <textarea id="fcc-wl-notes" name="notes" rows="2" class="large-text"></textarea>
          </div>
        </div>

      </div><!-- .fcc-wl-form__grid -->

      <div class="fcc-wl-form__footer">
        <button type="submit" class="fcc-wl-btn fcc-wl-btn--primary" id="fcc-wl-submit">Save License</button>
        <button type="button" class="fcc-wl-btn fcc-wl-btn--ghost" id="fcc-wl-cancel">Cancel</button>
        <span class="fcc-wl-form__msg" id="fcc-wl-form-msg"></span>
      </div>
    </form>
  </div><!-- .fcc-wl-form-panel -->

  <!-- ===== LICENSES TABLE ===== -->
  <div class="fcc-wl-section">
    <div class="fcc-wl-section__header">
      <h2>All Licenses</h2>
    </div>

    <?php if ( empty( $licenses ) ) : ?>
      <div class="fcc-wl-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        <p>No licenses yet. <button type="button" class="button-link" id="fcc-wl-add-btn2">Add your first license →</button></p>
      </div>
    <?php else : ?>
    <div class="fcc-wl-table-wrap">
      <table class="fcc-wl-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Client</th>
            <th>Business</th>
            <th>Tier</th>
            <th>Status</th>
            <th>Domains</th>
            <th>Expires</th>
            <th>Loads</th>
            <th>Searches</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $licenses as $lic ) :
            $is_expired = ! empty( $lic['expires_at'] ) && strtotime( $lic['expires_at'] ) < time();
            $computed_status = $is_expired ? 'expired' : $lic['status'];
            $domains_list = (array) $lic['allowed_domains'];
          ?>
          <tr id="fcc-wl-row-<?php echo esc_attr( $lic['id'] ); ?>">
            <td><?php echo esc_html( $lic['id'] ); ?></td>
            <td>
              <strong><?php echo esc_html( $lic['client_name'] ); ?></strong><br>
              <a href="mailto:<?php echo esc_attr( $lic['client_email'] ); ?>"><?php echo esc_html( $lic['client_email'] ); ?></a>
              <?php if ( ! empty( $lic['brand_name'] ) ) : ?>
                <br><em class="fcc-wl-brand-label"><?php echo esc_html( $lic['brand_name'] ); ?></em>
              <?php endif; ?>
            </td>
            <td><?php echo esc_html( $biz_labels[ $lic['business_type'] ] ?? ucfirst( $lic['business_type'] ) ); ?></td>
            <td><span class="fcc-wl-tier-pill fcc-wl-tier-pill--<?php echo esc_attr( $lic['tier'] ); ?>"><?php echo esc_html( $tier_labels[ $lic['tier'] ] ?? $lic['tier'] ); ?></span></td>
            <td>
              <span class="fcc-wl-status fcc-wl-status--<?php echo esc_attr( $computed_status ); ?>" id="fcc-wl-status-<?php echo esc_attr( $lic['id'] ); ?>">
                <?php echo esc_html( $status_labels[ $computed_status ] ?? ucfirst( $computed_status ) ); ?>
              </span>
            </td>
            <td>
              <?php if ( ! empty( $domains_list ) ) : ?>
                <span title="<?php echo esc_attr( implode( ', ', $domains_list ) ); ?>">
                  <?php echo esc_html( $domains_list[0] ); ?>
                  <?php if ( count( $domains_list ) > 1 ) echo ' +' . ( count( $domains_list ) - 1 ); ?>
                </span>
              <?php else : ?>
                <span class="fcc-wl-muted">—</span>
              <?php endif; ?>
            </td>
            <td><?php echo ! empty( $lic['expires_at'] ) ? esc_html( date( 'd M Y', strtotime( $lic['expires_at'] ) ) ) : '<span class="fcc-wl-muted">—</span>'; ?></td>
            <td><?php echo esc_html( number_format( $lic['embed_loads'] ) ); ?></td>
            <td><?php echo esc_html( number_format( $lic['search_count'] ) ); ?></td>
            <td class="fcc-wl-actions">
              <button type="button" class="fcc-wl-btn fcc-wl-btn--sm" data-action="edit"
                data-id="<?php echo esc_attr( $lic['id'] ); ?>"
                data-json="<?php echo esc_attr( wp_json_encode( $lic ) ); ?>">Edit</button>
              <button type="button" class="fcc-wl-btn fcc-wl-btn--sm fcc-wl-btn--outline" data-action="embed"
                data-key="<?php echo esc_attr( $lic['license_key'] ); ?>"
                data-name="<?php echo esc_attr( $lic['client_name'] ); ?>">Embed</button>
              <?php if ( $computed_status !== 'suspended' ) : ?>
                <button type="button" class="fcc-wl-btn fcc-wl-btn--sm fcc-wl-btn--amber" data-action="suspend"
                  data-id="<?php echo esc_attr( $lic['id'] ); ?>">Suspend</button>
              <?php else : ?>
                <button type="button" class="fcc-wl-btn fcc-wl-btn--sm fcc-wl-btn--green" data-action="activate"
                  data-id="<?php echo esc_attr( $lic['id'] ); ?>">Activate</button>
              <?php endif; ?>
              <button type="button" class="fcc-wl-btn fcc-wl-btn--sm fcc-wl-btn--danger" data-action="delete"
                data-id="<?php echo esc_attr( $lic['id'] ); ?>">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div><!-- .fcc-wl-table-wrap -->
    <?php endif; ?>
  </div>

  <!-- ===== EXPIRING SOON ===== -->
  <?php if ( ! empty( $expiring ) ) : ?>
  <div class="fcc-wl-section fcc-wl-section--expiring">
    <div class="fcc-wl-section__header">
      <h2>⚠ Expiring Within 30 Days</h2>
    </div>
    <div class="fcc-wl-table-wrap">
      <table class="fcc-wl-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Tier</th>
            <th>Expires</th>
            <th>Days Left</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $expiring as $elic ) :
            $days_left = (int) ceil( ( strtotime( $elic['expires_at'] ) - time() ) / DAY_IN_SECONDS );
          ?>
          <tr>
            <td><?php echo esc_html( $elic['client_name'] ); ?></td>
            <td><span class="fcc-wl-tier-pill fcc-wl-tier-pill--<?php echo esc_attr( $elic['tier'] ); ?>"><?php echo esc_html( $tier_labels[ $elic['tier'] ] ?? $elic['tier'] ); ?></span></td>
            <td><?php echo esc_html( date( 'd M Y', strtotime( $elic['expires_at'] ) ) ); ?></td>
            <td><strong class="fcc-wl-days-left"><?php echo esc_html( $days_left ); ?>d</strong></td>
            <td>
              <button type="button" class="fcc-wl-btn fcc-wl-btn--sm fcc-wl-btn--primary" data-action="renew"
                data-id="<?php echo esc_attr( $elic['id'] ); ?>">Renew +1yr</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div><!-- .wrap -->

<!-- ===== EMBED CODE MODAL ===== -->
<div class="fcc-wl-modal-overlay" id="fcc-wl-embed-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="fcc-wl-modal-title">
  <div class="fcc-wl-modal">
    <div class="fcc-wl-modal__header">
      <h2 id="fcc-wl-modal-title">Embed Code</h2>
      <button type="button" class="fcc-wl-modal__close" id="fcc-wl-modal-close" aria-label="Close">&times;</button>
    </div>
    <div class="fcc-wl-modal__body">
      <p class="fcc-wl-modal__client" id="fcc-wl-modal-client"></p>

      <div class="fcc-wl-embed-block">
        <label>Shortcode (recommended for WordPress pages)</label>
        <div class="fcc-wl-embed-row">
          <code id="fcc-wl-sc-code"></code>
          <button type="button" class="fcc-wl-btn fcc-wl-btn--sm fcc-wl-btn--copy" data-target="fcc-wl-sc-code">Copy</button>
        </div>
      </div>

      <div class="fcc-wl-embed-block">
        <label>iframe Embed (for non-WordPress sites)</label>
        <div class="fcc-wl-embed-row">
          <code id="fcc-wl-iframe-code"></code>
          <button type="button" class="fcc-wl-btn fcc-wl-btn--sm fcc-wl-btn--copy" data-target="fcc-wl-iframe-code">Copy</button>
        </div>
      </div>

      <p class="fcc-wl-modal__note">The embed will apply the client's branding only when loaded on their allowed domains. Any other domain will fall back to default FCC branding.</p>
    </div>
  </div>
</div>

<script>
(function($){
  var ajaxUrl  = <?php echo wp_json_encode( $ajax_url ); ?>;
  var nonce    = <?php echo wp_json_encode( $nonce ); ?>;
  var siteUrl  = <?php echo wp_json_encode( $siteUrl ?? $site_url ); ?>;

  var tierDomainMax = { starter: 1, growth: 2, professional: 5, enterprise: '∞' };

  // ---- Open form (add) ----
  function openForm( license ) {
    $('#fcc-wl-form')[0].reset();
    if ( license ) {
      $('#fcc-wl-form-title').text('Edit License');
      $('#fcc-wl-id').val(license.id);
      $('#fcc-wl-client-name').val(license.client_name);
      $('#fcc-wl-client-email').val(license.client_email);
      $('#fcc-wl-client-url').val(license.client_url || '');
      $('#fcc-wl-biz-type').val(license.business_type);
      $('#fcc-wl-tier').val(license.tier);
      $('#fcc-wl-status').val(license.status);
      $('#fcc-wl-expires').val( license.expires_at ? license.expires_at.substring(0,10) : '' );
      $('#fcc-wl-domains').val( Array.isArray(license.allowed_domains) ? license.allowed_domains.join('\n') : '' );
      $('#fcc-wl-brand-name').val(license.brand_name || '');
      $('#fcc-wl-logo-url').val(license.logo_url || '');
      $('#fcc-wl-primary').val(license.primary_colour || '#2D7A4F');
      $('#fcc-wl-accent').val(license.accent_colour || '#41B883');
      $('#fcc-wl-hide-powered').prop('checked', !!license.hide_powered_by);
      $('#fcc-wl-css').val(license.custom_css || '');
      $('#fcc-wl-notes').val(license.notes || '');
    } else {
      $('#fcc-wl-form-title').text('Add New License');
      $('#fcc-wl-id').val(0);
    }
    updateTierUI( $('#fcc-wl-tier').val() );
    $('#fcc-wl-form-msg').text('');
    $('#fcc-wl-form-panel').slideDown(200);
    $('html, body').animate({ scrollTop: $('#fcc-wl-form-panel').offset().top - 40 }, 200);
  }

  function updateTierUI( tier ) {
    var max = tierDomainMax[tier] || 1;
    $('#fcc-wl-domain-hint').text('(one per line — ' + tier.charAt(0).toUpperCase() + tier.slice(1) + ': max ' + max + ')');
    // Show/hide tier-gated fields.
    var tierOrder = { starter:0, growth:1, professional:2, enterprise:3 };
    var idx = tierOrder[tier] || 0;
    $('.fcc-wl-tier-feature').each(function(){
      var required = $(this).hasClass('fcc-wl-tier-feature--growth') ? 1
        : $(this).hasClass('fcc-wl-tier-feature--professional') ? 2
        : $(this).hasClass('fcc-wl-tier-feature--enterprise') ? 3 : 0;
      $(this).css('opacity', idx >= required ? 1 : 0.4);
    });
  }

  $(document).on('click', '#fcc-wl-add-btn, #fcc-wl-add-btn2', function(){
    openForm(null);
  });
  $(document).on('click', '#fcc-wl-form-close, #fcc-wl-cancel', function(){
    $('#fcc-wl-form-panel').slideUp(200);
  });
  $(document).on('change', '#fcc-wl-tier', function(){
    updateTierUI($(this).val());
  });

  // Edit action
  $(document).on('click', '[data-action="edit"]', function(){
    var license = JSON.parse($(this).data('json'));
    openForm(license);
  });

  // ---- Form submit ----
  $(document).on('submit', '#fcc-wl-form', function(e){
    e.preventDefault();
    var $btn = $('#fcc-wl-submit').prop('disabled', true).text('Saving…');
    var data = $(this).serializeArray().reduce(function(o,f){ o[f.name]=f.value; return o; }, {});
    data.hide_powered_by = $('#fcc-wl-hide-powered').is(':checked') ? 1 : 0;
    data.action = 'fcc_wl_save_license';
    data.nonce  = nonce;

    $.post( ajaxUrl, data, function(res){
      if ( res.success ) {
        $('#fcc-wl-form-msg').css('color','green').text( data.id > 0 ? 'Saved!' : 'License created! Key: ' + (res.data.license_key || '') );
        setTimeout(function(){ location.reload(); }, 1500);
      } else {
        $('#fcc-wl-form-msg').css('color','red').text(res.data.message || 'Error saving license.');
      }
      $btn.prop('disabled', false).text('Save License');
    }).fail(function(){
      $('#fcc-wl-form-msg').css('color','red').text('Server error.');
      $btn.prop('disabled', false).text('Save License');
    });
  });

  // ---- Delete ----
  $(document).on('click', '[data-action="delete"]', function(){
    if ( ! confirm('Delete this license? This cannot be undone.') ) return;
    var id = $(this).data('id');
    $.post( ajaxUrl, { action:'fcc_wl_delete_license', nonce:nonce, id:id }, function(res){
      if ( res.success ) {
        $('#fcc-wl-row-' + id).fadeOut(300, function(){ $(this).remove(); });
      } else {
        alert( res.data.message || 'Delete failed.' );
      }
    });
  });

  // ---- Suspend / Activate ----
  $(document).on('click', '[data-action="suspend"],[data-action="activate"]', function(){
    var id     = $(this).data('id');
    var action = $(this).data('action');
    var newStatus = action === 'suspend' ? 'suspended' : 'active';
    var $btn = $(this).prop('disabled', true);
    $.post( ajaxUrl, { action:'fcc_wl_toggle_license', nonce:nonce, id:id, status:newStatus }, function(res){
      if ( res.success ) {
        location.reload();
      } else {
        alert( res.data.message || 'Error.' );
        $btn.prop('disabled', false);
      }
    });
  });

  // ---- Renew ----
  $(document).on('click', '[data-action="renew"]', function(){
    var id   = $(this).data('id');
    var now  = new Date();
    now.setFullYear( now.getFullYear() + 1 );
    var newDate = now.toISOString().substring(0,10) + ' 23:59:59';
    var $btn = $(this).prop('disabled', true).text('Renewing…');
    $.post( ajaxUrl, { action:'fcc_wl_renew_license', nonce:nonce, id:id, expires_at:newDate }, function(res){
      if ( res.success ) {
        location.reload();
      } else {
        alert( res.data.message || 'Error.' );
        $btn.prop('disabled', false).text('Renew +1yr');
      }
    });
  });

  // ---- Embed modal ----
  $(document).on('click', '[data-action="embed"]', function(){
    var key  = $(this).data('key');
    var name = $(this).data('name');
    var sc   = '[food_calorie_calculator license="' + key + '"]';
    var iframe = '<iframe src="' + siteUrl + '?wl=' + key + '" width="100%" height="700" frameborder="0" style="border:none;"></iframe>';
    $('#fcc-wl-modal-client').text('Client: ' + name + ' — Key: ' + key);
    $('#fcc-wl-sc-code').text(sc);
    $('#fcc-wl-iframe-code').text(iframe);
    $('#fcc-wl-embed-overlay').fadeIn(200);
  });

  $(document).on('click', '#fcc-wl-modal-close, #fcc-wl-embed-overlay', function(e){
    if ( e.target === this ) $('#fcc-wl-embed-overlay').fadeOut(200);
  });

  // ---- Copy buttons ----
  $(document).on('click', '[data-action!="edit"][data-action!="embed"][data-action!="suspend"][data-action!="activate"][data-action!="delete"][data-action!="renew"].fcc-wl-btn--copy', function(){
    var target = $(this).data('target');
    var text   = $('#' + target).text();
    if ( navigator.clipboard ) {
      navigator.clipboard.writeText(text).then(function(){});
    }
    $(this).text('Copied!');
    var $b = $(this);
    setTimeout(function(){ $b.text('Copy'); }, 1500);
  });
  // Direct copy button handler (not conflicting with data-action filtering above)
  $(document).on('click', '.fcc-wl-btn--copy', function(){
    var target = $(this).data('target');
    if (!target) return;
    var text   = $('#' + target).text();
    if ( navigator.clipboard ) {
      navigator.clipboard.writeText(text);
    }
    var $b = $(this).text('Copied!');
    setTimeout(function(){ $b.text('Copy'); }, 1500);
  });

})(jQuery);
</script>
