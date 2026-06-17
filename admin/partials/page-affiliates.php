<?php
/**
 * Affiliate Links settings page.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$config     = \FCC\Admin\Affiliates::get_config();
$general    = $config['general'];
$retailers  = $config['retailers'];
$nonce      = wp_create_nonce( 'fcc_affiliates_nonce' );
$ajax_url   = admin_url( 'admin-ajax.php' );

// Group retailers by category.
$by_category = [];
foreach ( $retailers as $key => $r ) {
	$by_category[ $r['category'] ][ $key ] = $r;
}

$enabled_count = count( array_filter( $retailers, fn( $r ) => $r['enabled'] ) );
?>
<div class="wrap fcc-admin-wrap">

  <!-- ===== HERO ===== -->
  <div class="fcc-aff-hero">
    <div class="fcc-aff-hero__inner">
      <div>
        <h1 class="fcc-aff-hero__title">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          Affiliate Links
        </h1>
        <p class="fcc-aff-hero__sub">Show "Buy on …" buttons alongside food results and earn commission when visitors purchase. 22 retailers — toggle each on or off.</p>
      </div>
      <div class="fcc-aff-hero__meta">
        <div class="fcc-aff-stat">
          <span class="fcc-aff-stat__val <?php echo $enabled_count > 0 ? 'fcc-aff-stat__val--green' : ''; ?>"><?php echo esc_html( $enabled_count ); ?></span>
          <span class="fcc-aff-stat__lbl">Active Retailers</span>
        </div>
        <div class="fcc-aff-stat">
          <span class="fcc-aff-stat__val"><?php echo esc_html( count( $retailers ) ); ?></span>
          <span class="fcc-aff-stat__lbl">Total Available</span>
        </div>
      </div>
    </div>
  </div>

  <form id="fcc-aff-form">
    <input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

    <!-- ===== GENERAL SETTINGS ===== -->
    <div class="fcc-aff-card fcc-aff-card--general">
      <h2 class="fcc-aff-card__title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M4.93 4.93l1.41 1.41M19.07 19.07l-1.41-1.41M4.93 19.07l1.41-1.41M20 12h2M2 12h2M12 20v2M12 2v2"/></svg>
        General Settings
      </h2>
      <div class="fcc-aff-general-grid">
        <div class="fcc-aff-field">
          <label for="fcc-aff-btn-style">Button Style</label>
          <select id="fcc-aff-btn-style" name="general[button_style]" class="regular-text">
            <option value="pill"   <?php selected( $general['button_style'], 'pill' ); ?>>Pill chips (default)</option>
            <option value="button" <?php selected( $general['button_style'], 'button' ); ?>>Solid buttons</option>
            <option value="text"   <?php selected( $general['button_style'], 'text' ); ?>>Text links</option>
          </select>
        </div>
        <div class="fcc-aff-field">
          <label for="fcc-aff-label-prefix">Label Prefix</label>
          <input type="text" id="fcc-aff-label-prefix" name="general[label_prefix]" value="<?php echo esc_attr( $general['label_prefix'] ); ?>" class="regular-text" placeholder="Buy on">
          <span class="fcc-aff-hint">Prepended to retailer name when no custom label is set — e.g. "Buy on Amazon UK"</span>
        </div>
        <div class="fcc-aff-field fcc-aff-field--checkbox">
          <label class="fcc-aff-toggle-label">
            <input type="hidden" name="general[open_new_tab]" value="0">
            <input type="checkbox" name="general[open_new_tab]" value="1" <?php checked( $general['open_new_tab'] ); ?>>
            Open links in new tab
          </label>
        </div>
        <div class="fcc-aff-field fcc-aff-field--checkbox">
          <label class="fcc-aff-toggle-label">
            <input type="hidden" name="general[show_icon]" value="0">
            <input type="checkbox" name="general[show_icon]" value="1" <?php checked( $general['show_icon'] ); ?>>
            Show retailer icon in button
          </label>
        </div>
      </div>

      <!-- Live preview row -->
      <div class="fcc-aff-preview">
        <p class="fcc-aff-preview__label">Preview (example food: "Chicken Breast")</p>
        <div class="fcc-aff-preview__row" id="fcc-aff-preview-row">
          <span class="fcc-aff-chip fcc-aff-chip--preview" style="background:#FF9900;color:#fff;">Buy on Amazon UK</span>
          <span class="fcc-aff-chip fcc-aff-chip--preview" style="background:#7AC143;color:#fff;">Buy on Ocado</span>
          <span class="fcc-aff-chip fcc-aff-chip--preview" style="background:#003C30;color:#fff;">Buy at Holland & Barrett</span>
        </div>
      </div>
    </div>

    <!-- ===== RETAILER SECTIONS ===== -->
    <?php foreach ( $by_category as $cat => $cat_retailers ) : ?>
    <div class="fcc-aff-section">
      <div class="fcc-aff-section__header">
        <h2 class="fcc-aff-section__title"><?php echo esc_html( $cat ); ?></h2>
        <span class="fcc-aff-section__count">
          <?php
          $cat_enabled = count( array_filter( $cat_retailers, fn( $r ) => $r['enabled'] ) );
          echo esc_html( $cat_enabled . ' / ' . count( $cat_retailers ) . ' enabled' );
          ?>
        </span>
      </div>

      <div class="fcc-aff-retailer-grid">
        <?php foreach ( $cat_retailers as $key => $r ) : ?>
        <div class="fcc-aff-retailer-card <?php echo $r['enabled'] ? 'fcc-aff-retailer-card--active' : ''; ?>" id="fcc-aff-card-<?php echo esc_attr( $key ); ?>">

          <div class="fcc-aff-retailer-card__header">
            <!-- Colour dot -->
            <span class="fcc-aff-retailer-dot" style="background:<?php echo esc_attr( $r['colour'] ); ?>;"></span>
            <span class="fcc-aff-retailer-card__name"><?php echo esc_html( $r['name'] ); ?></span>

            <!-- Toggle switch -->
            <label class="fcc-aff-toggle" title="Enable <?php echo esc_attr( $r['name'] ); ?>">
              <input type="hidden" name="retailers[<?php echo esc_attr( $key ); ?>][enabled]" value="0">
              <input type="checkbox"
                name="retailers[<?php echo esc_attr( $key ); ?>][enabled]"
                value="1"
                class="fcc-aff-toggle__cb"
                data-card="<?php echo esc_attr( $key ); ?>"
                <?php checked( $r['enabled'] ); ?>>
              <span class="fcc-aff-toggle__slider"></span>
            </label>
          </div>

          <div class="fcc-aff-retailer-card__body">
            <div class="fcc-aff-field">
              <label><?php echo esc_html( $r['id_label'] ); ?></label>
              <input type="text"
                name="retailers[<?php echo esc_attr( $key ); ?>][tracking_id]"
                value="<?php echo esc_attr( $r['tracking_id'] ); ?>"
                placeholder="<?php echo esc_attr( $r['id_placeholder'] ); ?>"
                class="regular-text">
            </div>
            <div class="fcc-aff-field">
              <label>Custom Button Label <span class="fcc-aff-optional">(optional)</span></label>
              <input type="text"
                name="retailers[<?php echo esc_attr( $key ); ?>][custom_label]"
                value="<?php echo esc_attr( $r['custom_label'] ); ?>"
                placeholder="Buy at <?php echo esc_attr( $r['name'] ); ?>"
                class="regular-text">
            </div>
            <details class="fcc-aff-advanced">
              <summary>Custom URL template</summary>
              <div class="fcc-aff-field">
                <input type="url"
                  name="retailers[<?php echo esc_attr( $key ); ?>][url_template]"
                  value="<?php echo esc_attr( $r['url_template'] ); ?>"
                  class="large-text code"
                  placeholder="<?php echo esc_attr( $r['url_template'] ); ?>">
                <span class="fcc-aff-hint">Use <code>{QUERY}</code> for food name and <code>{ID}</code> for your tracking ID.</span>
              </div>
            </details>
          </div>

        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- ===== SAVE BAR ===== -->
    <div class="fcc-aff-save-bar">
      <button type="submit" class="button button-primary button-large" id="fcc-aff-save">Save All Changes</button>
      <span class="fcc-aff-save-msg" id="fcc-aff-save-msg"></span>
    </div>

  </form><!-- #fcc-aff-form -->

</div><!-- .wrap -->

<script>
(function($){
  var ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;

  // Toggle card active state visually.
  $(document).on('change', '.fcc-aff-toggle__cb', function(){
    var card = $('#fcc-aff-card-' + $(this).data('card'));
    if ($(this).is(':checked')) {
      card.addClass('fcc-aff-retailer-card--active');
    } else {
      card.removeClass('fcc-aff-retailer-card--active');
    }
  });

  // Save form via AJAX.
  $('#fcc-aff-form').on('submit', function(e){
    e.preventDefault();
    var $btn = $('#fcc-aff-save').prop('disabled', true).val('Saving…');
    var data = $(this).serializeArray();
    data.push({ name: 'action', value: 'fcc_save_affiliates' });

    $.post(ajaxUrl, $.param(data), function(res){
      if (res.success) {
        $('#fcc-aff-save-msg').css('color','#2D7A4F').text('✓ Saved successfully!');
        setTimeout(function(){ $('#fcc-aff-save-msg').text(''); }, 3000);
      } else {
        $('#fcc-aff-save-msg').css('color','red').text(res.data.message || 'Error saving.');
      }
      $btn.prop('disabled', false).val('Save All Changes');
    }).fail(function(){
      $('#fcc-aff-save-msg').css('color','red').text('Server error.');
      $btn.prop('disabled', false).val('Save All Changes');
    });
  });

})(jQuery);
</script>
