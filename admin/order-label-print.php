<?php
/**
 * BSC Order Label print template.
 * Receives: $labels (BSC_Order_Label_ViewModel[]), $autoprint (bool)
 * Called exclusively by bsc_render_order_labels() in class-bsc-order-labels.php
 *
 * STYLES
 *   Source  : sass/pages/admin/_order-label-print.scss
 *   Compile : npm run watch  (or: npx sass sass/admin-order-label-print.scss:admin-order-label-print.css)
 *   Output  : admin-order-label-print.css  (theme root)
 */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Etiquetas de despacho — BSC</title>
  <style>
  <?php
  // Inline the compiled CSS so this page is self-contained (no WP enqueue).
  // Edit styles in sass/pages/admin/_order-label-print.scss then recompile.
  $css_file = get_template_directory() . '/admin-order-label-print.css';
  if ( file_exists( $css_file ) ) {
      // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
      echo file_get_contents( $css_file ); // local filesystem, safe
  }
  ?>
  </style>
</head>
<body>

  <!-- ── Toolbar (screen only, hidden in print) ── -->
  <div class="bsc-labels-toolbar">
    <button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--primary"
            onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
    <button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--secondary"
            onclick="window.close()">← Volver</button>
    <p class="bsc-labels-toolbar__hint">
      Usa la impresión del navegador y selecciona<br>
      <strong>Guardar como PDF</strong> si quieres descargarlo.
    </p>
  </div>

  <?php foreach ( $labels as $vm ) : ?>
  <!-- ── Label page: #<?php echo esc_html( $vm->order_number ); ?> ── -->
  <div class="bsc-print-label-page">
    <article class="bsc-print-label"
             aria-label="Etiqueta pedido #<?php echo esc_attr( $vm->order_number ); ?>">

      <!-- 1 ─ HEADER -->
      <header class="bsc-print-label__header">

        <!-- Smiley icon (inline SVG — no external dependency) -->
        <div class="bsc-print-label__header-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"
               width="100%" height="100%">
            <circle cx="25" cy="25" r="22"
                    stroke="white" stroke-width="2.2" fill="none"/>
            <circle cx="18" cy="20" r="2.8" fill="white"/>
            <circle cx="32" cy="20" r="2.8" fill="white"/>
            <path d="M 14 30 Q 25 42 36 30"
                  stroke="white" stroke-width="2.5"
                  fill="none" stroke-linecap="round"/>
          </svg>
        </div>

        <div class="bsc-print-label__header-copy">
          <div class="bsc-print-label__header-title">delicado</div>
          <div class="bsc-print-label__header-sub">
            <strong>NO</strong> poner peso encima&nbsp;:)
          </div>
        </div>

      </header>

      <!-- 2 ─ CLAIMS ROW + CONTAINS -->
      <div class="bsc-print-label__claims">

        <div class="bsc-print-label__claims-group">
          <div class="bsc-print-label__claim">
            <span class="bsc-print-label__claim-icon">✦</span>
            100% Original
          </div>
          <div class="bsc-print-label__claim">
            <span class="bsc-print-label__claim-icon">✿</span>
            K-beauty lover
          </div>
          <div class="bsc-print-label__claim">
            <span class="bsc-print-label__claim-icon">◈</span>
            Glow gang
          </div>
        </div>

        <div class="bsc-print-label__contains">
          <div class="bsc-print-label__contains-label">Contiene</div>
          <div class="bsc-print-label__contains-value">
            <?php echo esc_html( $vm->contains ); ?>
          </div>
        </div>

      </div>

      <!-- 3 ─ RECIPIENT -->
      <section class="bsc-print-label__recipient">

        <div class="bsc-print-label__recipient-name">
          <?php echo esc_html( $vm->name ); ?>
        </div>

        <?php if ( $vm->phone !== '' ) : ?>
        <div class="bsc-print-label__recipient-phone">
          <?php echo esc_html( $vm->phone ); ?>
        </div>
        <?php endif; ?>

        <?php if ( $vm->address_line1 !== '' ) : ?>
        <div class="bsc-print-label__recipient-address">
          <?php echo esc_html( $vm->address_line1 ); ?>
          <?php if ( $vm->address_line2 !== '' ) : ?>
          <br><?php echo esc_html( $vm->address_line2 ); ?>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php
          $location = array_filter( [ $vm->city, $vm->state ] );
          if ( ! empty( $location ) ) :
        ?>
        <div class="bsc-print-label__recipient-location">
          <?php echo esc_html( implode( ', ', $location ) ); ?>
        </div>
        <?php endif; ?>

      </section>

      <!-- 4 ─ OBSERVATIONS -->
      <section class="bsc-print-label__observations">
        <div class="bsc-print-label__obs-label">Observaciones</div>
        <div class="bsc-print-label__obs-value">
          <?php echo esc_html( $vm->observations ); ?>
        </div>
      </section>

      <!-- 5 ─ FOOTER — BSC brand lockup -->
      <footer class="bsc-print-label__footer">

        <div class="bsc-print-label__footer-brand">BSC</div>

        <!-- Decorative waves (inline SVG) -->
        <svg class="bsc-print-label__footer-waves"
             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 12"
             aria-hidden="true">
          <path d="M0 6 C10 0 20 12 30 6 C40 0 50 12 60 6 C70 0 80 12 80 6"
                stroke="#111" stroke-width="1.5" fill="none"/>
        </svg>

        <!-- Outlined tagline -->
        <div class="bsc-print-label__footer-tagline" aria-label="Skin Care First">
          <span>SKIN</span>
          <span>CARE</span>
          <span>FIRST</span>
        </div>

      </footer>

    </article>
  </div>
  <?php endforeach; ?>

  <?php if ( $autoprint ) : ?>
  <script>window.addEventListener('load', function(){ window.print(); });</script>
  <?php endif; ?>

</body>
</html>
