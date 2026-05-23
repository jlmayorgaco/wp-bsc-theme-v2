<?php
/**
 * BSC custom account details form.
 *
 * Reviewed against WooCommerce form-edit-account.php 9.7.0.
 *
 * @package WooCommerce\Templates
 * @version 9.7.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_edit_account_form');

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

$birthday    = get_user_meta($user_id, 'bsc_birthday', true);
$skin_type   = get_user_meta($user_id, 'bsc_skin_type', true);
$sensitivity = get_user_meta($user_id, 'bsc_sensitivity', true);

$needs = [
  'bsc_needs1' => get_user_meta($user_id, 'bsc_needs1', true),
  'bsc_needs2' => get_user_meta($user_id, 'bsc_needs2', true),
  'bsc_needs3' => get_user_meta($user_id, 'bsc_needs3', true),
  'bsc_needs4' => get_user_meta($user_id, 'bsc_needs4', true),
];

$skin_types    = ['Grasa', 'Mixta', 'Seca', 'Normal', 'Normal a seca', 'Normal a grasa'];
$sensitivities = ['Sensible normal', 'Muy sensible', 'No sensible'];

$default_display_name = $current_user->display_name ? $current_user->display_name : trim($current_user->first_name . ' ' . $current_user->last_name);
$default_first_name   = $current_user->first_name ? $current_user->first_name : '';
$default_last_name    = $current_user->last_name ? $current_user->last_name : '';
?>

<form class="bsc__account-form" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?>>
  <?php do_action('woocommerce_edit_account_form_start'); ?>

  <div class="bsc__account-grid">

    <div class="bsc__account-col">
      <div class="bsc__field">
        <label for="account_full_name">Nombres y apellidos:</label>
        <input
          type="text"
          name="account_full_name"
          id="account_full_name"
          value="<?php echo esc_attr($default_display_name); ?>"
          autocomplete="name"
        />
      </div>

      <div class="bsc__field">
        <label for="account_email">Correo electr&oacute;nico:</label>
        <input type="email" name="account_email" id="account_email" value="<?php echo esc_attr($current_user->user_email); ?>" autocomplete="email" />
      </div>

      <div class="bsc__field">
        <label for="account_password">Contrase&ntilde;a:</label>
        <input type="password" name="account_password" id="account_password" value="XXXXXXXXXX" disabled />
      </div>

      <input type="hidden" name="account_first_name" id="account_first_name" value="<?php echo esc_attr($default_first_name); ?>" />
      <input type="hidden" name="account_last_name" id="account_last_name" value="<?php echo esc_attr($default_last_name); ?>" />
      <input type="hidden" name="account_display_name" id="account_display_name" value="<?php echo esc_attr($default_display_name); ?>" />
    </div>

    <div class="bsc__vl"></div>

    <div class="bsc__account-col">
      <div class="bsc__field">
        <label for="account_birthday">Cumplea&ntilde;os:</label>
        <input type="date" name="account_birthday" id="account_birthday" value="<?php echo esc_attr($birthday); ?>" />
      </div>

      <div class="bsc__field">
        <label for="account_skin_type">Tipo de piel:</label>
        <select name="account_skin_type" id="account_skin_type">
          <option value="">Selecciona una opci&oacute;n</option>
          <?php foreach ($skin_types as $option): ?>
            <option value="<?php echo esc_attr($option); ?>" <?php selected($skin_type, $option); ?>>
              <?php echo esc_html($option); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="bsc__field">
        <label for="account_sensitivity">Sensibilidad:</label>
        <select name="account_sensitivity" id="account_sensitivity">
          <option value="">Selecciona una opci&oacute;n</option>
          <?php foreach ($sensitivities as $option): ?>
            <option value="<?php echo esc_attr($option); ?>" <?php selected($sensitivity, $option); ?>>
              <?php echo esc_html($option); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="bsc__vl"></div>

    <div class="bsc__account-col bsc__account-needs">
      <h3>Necesidades:</h3>
      <?php
        $placeholders = [
          'bsc_needs1' => html_entity_decode('ej. Pigmentaci&oacute;n', ENT_QUOTES, 'UTF-8'),
          'bsc_needs2' => 'ej. Elasticidad',
          'bsc_needs3' => 'ej. Exceso de sebo',
          'bsc_needs4' => html_entity_decode('ej. Hidrataci&oacute;n', ENT_QUOTES, 'UTF-8'),
        ];

        foreach ($needs as $meta_key => $value): ?>
        <div class="bsc__need">
          <img class="bsc__need-icon" src="<?php echo esc_url(get_template_directory_uri()); ?>/images/profile/bsc_profile_points.png" alt="Check icon" />
          <input
            type="text"
            class="bsc__need-input"
            name="<?php echo esc_attr($meta_key); ?>"
            id="<?php echo esc_attr($meta_key); ?>"
            value="<?php echo esc_attr($value); ?>"
            placeholder="<?php echo esc_attr($placeholders[$meta_key] ?? ''); ?>"
          />
        </div>
      <?php endforeach; ?>
    </div>

  </div>

  <div class="bsc__account-submit">
    <?php do_action('woocommerce_edit_account_form'); ?>
    <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
    <button type="submit" class="bsc__button bsc__button--compact-pill bsc__address-button"><?php echo esc_html( html_entity_decode( '&#161; Guardar datos !', ENT_QUOTES, 'UTF-8' ) ); ?></button>
    <input type="hidden" name="action" value="save_account_details" />
  </div>

  <?php do_action('woocommerce_edit_account_form_end'); ?>
</form>

<?php do_action('woocommerce_after_edit_account_form'); ?>
