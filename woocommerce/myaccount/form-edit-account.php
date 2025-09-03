<?php
defined( 'ABSPATH' ) || exit;
do_action( 'woocommerce_before_edit_account_form' );

$current_user = wp_get_current_user();

// Valores personalizados
$birthday     = get_user_meta($current_user->ID, 'bsc_birthday', true);
$skin_type    = get_user_meta($current_user->ID, 'bsc_skin_type', true);
$sensitivity  = get_user_meta($current_user->ID, 'bsc_sensitivity', true);

// Necesidades como texto
$needs = [
  'bsc_needs1' => get_user_meta($current_user->ID, 'bsc_needs1', true),
  'bsc_needs2' => get_user_meta($current_user->ID, 'bsc_needs2', true),
  'bsc_needs3' => get_user_meta($current_user->ID, 'bsc_needs3', true),
  'bsc_needs4' => get_user_meta($current_user->ID, 'bsc_needs4', true),
];

// Opciones de piel y sensibilidad
$skin_types = ['Grasa', 'Mixta', 'Seca', 'Normal', 'Normal a seca', 'Normal a grasa'];
$sensitivities = ['Sensible normal', 'Muy sensible', 'No sensible'];
?>

<form class="bsc__account-form" method="post">
  <div class="bsc__account-grid">

    <!-- Columna 1 -->
    <div class="bsc__account-col">
      <div class="bsc__field">
        <label for="account_full_name">Nombres y apellidos:</label>
        <input type="text" name="account_full_name" id="account_full_name" value="<?php echo esc_attr($current_user->display_name); ?>" />
      </div>

      <div class="bsc__field">
        <label for="account_email">Correo electrónico:</label>
        <input type="email" name="account_email" id="account_email" value="<?php echo esc_attr($current_user->user_email); ?>" />
      </div>

      <div class="bsc__field">
        <label for="account_password">Contraseña:</label>
        <input type="password" name="account_password" id="account_password" value="XXXXXXXXXX" disabled />
      </div>
    </div>


    <div class="bsc__vl"></div>

    <!-- Columna 2 -->
    <div class="bsc__account-col">
      <div class="bsc__field">
        <label for="account_birthday">Cumpleaños:</label>
        <input type="date" name="account_birthday" id="account_birthday" value="<?php echo esc_attr($birthday); ?>" />
      </div>

      <div class="bsc__field">
        <label for="account_skin_type">Tipo de piel:</label>
        <select name="account_skin_type" id="account_skin_type">
          <option value="">Selecciona una opción</option>
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
          <option value="">Selecciona una opción</option>
          <?php foreach ($sensitivities as $option): ?>
            <option value="<?php echo esc_attr($option); ?>" <?php selected($sensitivity, $option); ?>>
              <?php echo esc_html($option); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="bsc__vl"></div>

<!-- Columna 3 -->
<div class="bsc__account-col bsc__account-needs">
  <h3>Necesidades:</h3>
  <?php
    $placeholders = [
      'bsc_needs1' => 'ej. Pigmentación',
      'bsc_needs2' => 'ej. Elasticidad',
      'bsc_needs3' => 'ej. Exceso de sebo',
      'bsc_needs4' => 'ej. Hidratación',
    ];

    foreach ($needs as $meta_key => $value): ?>
    <div class="bsc__need">
      <img class="bsc__need-icon" src="<?php echo get_template_directory_uri(); ?>/images/profile/bsc_profile_points.png" alt="Check icon" />
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
    <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
    <button type="submit" class="bsc__button">¡ Guardar datos !</button>
    <input type="hidden" name="action" value="save_account_details" />
  </div>

  <?php do_action( 'woocommerce_edit_account_form_end' ); ?>
</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
