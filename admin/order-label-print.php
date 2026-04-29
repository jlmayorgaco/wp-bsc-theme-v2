<?php
/**
 * BSC Order Label print template.
 * Receives: $labels, $autoprint, $label_order_ids, $pdf_export_nonce
 */
defined( 'ABSPATH' ) || exit;

$default_width_mm  = (float) get_option( 'bsc_order_label_width_mm', 100 );
$default_height_mm = (float) get_option( 'bsc_order_label_height_mm', 153 );

if ( $default_width_mm <= 0 ) {
    $default_width_mm = 100;
}

if ( $default_height_mm <= 0 ) {
    $default_height_mm = 153;
}

$build_size_css = static function ( float $width_mm, float $height_mm ): string {
    $width_mm  = max( 10, round( $width_mm, 2 ) );
    $height_mm = max( 10, round( $height_mm, 2 ) );

    return "
@page { size: {$width_mm}mm {$height_mm}mm; margin: 0; }
.bsc-labels-toolbar { max-width: calc({$width_mm}mm + 20px); }
.bsc-print-label {
  width: {$width_mm}mm;
  height: {$height_mm}mm;
  max-width: {$width_mm}mm;
  max-height: {$height_mm}mm;
}
@media print {
  @page { size: {$width_mm}mm {$height_mm}mm; margin: 0; }
  .bsc-print-label {
    width: {$width_mm}mm !important;
    height: {$height_mm}mm !important;
    max-width: {$width_mm}mm !important;
    max-height: {$height_mm}mm !important;
  }
}";
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Etiquetas de despacho - BSC</title>
  <style>
  <?php
  $css_file = get_template_directory() . '/admin-order-label-print.css';
  if ( file_exists( $css_file ) ) {
      echo file_get_contents( $css_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
  }
  ?>
  .bsc-labels-toolbar__size-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .bsc-labels-toolbar__size-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    color: #444;
  }

  .bsc-labels-toolbar__size-input {
    width: 84px;
    padding: 7px 8px;
    border: 1px solid #bbb;
    border-radius: 4px;
    font: inherit;
    color: #111;
    background: #fff;
  }

  .bsc-labels-toolbar__notice {
    flex-basis: 100%;
    margin: 0;
    padding: 10px 12px;
    border-radius: 6px;
    background: #fff5da;
    border: 1px solid #eed28b;
    color: #6a5313;
    font-size: 12px;
    line-height: 1.5;
  }
  </style>
  <style id="bsc-label-dynamic-size"><?php echo $build_size_css( $default_width_mm, $default_height_mm ); ?></style>
</head>
<body>

  <div class="bsc-labels-toolbar">
    <button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--primary"
            type="button"
            onclick="window.BSCLabelPrint.downloadPdf()">Descargar PDF exacto</button>
    <button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--secondary"
            type="button"
            onclick="window.BSCLabelPrint.print()">Imprimir navegador</button>
    <button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--secondary"
            type="button"
            onclick="window.close()">Volver</button>
    <button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--secondary"
            type="button"
            onclick="window.BSCLabelPrint.resetSize()">Restablecer medida</button>

    <div class="bsc-labels-toolbar__size-controls" aria-label="Ajuste de tamano de etiqueta">
      <label class="bsc-labels-toolbar__size-field" for="bsc-label-width-mm">
        Ancho (mm)
        <input id="bsc-label-width-mm"
               class="bsc-labels-toolbar__size-input"
               type="number"
               min="10"
               step="0.5"
               value="<?php echo esc_attr( $default_width_mm ); ?>">
      </label>

      <label class="bsc-labels-toolbar__size-field" for="bsc-label-height-mm">
        Alto (mm)
        <input id="bsc-label-height-mm"
               class="bsc-labels-toolbar__size-input"
               type="number"
               min="10"
               step="0.5"
               value="<?php echo esc_attr( $default_height_mm ); ?>">
      </label>
    </div>

    <p class="bsc-labels-toolbar__hint">
      <strong>PDF exacto</strong> genera un PDF real con ese tamano de hoja.<br>
      <strong>Imprimir navegador</strong> queda como fallback y puede forzar Letter/A4 con destinos como Microsoft Print to PDF.
    </p>

    <p class="bsc-labels-toolbar__notice">
      Si usas el dialogo del navegador, desactiva <strong>headers/footers</strong>. Para tamano exacto, usa el boton <strong>Descargar PDF exacto</strong>.
    </p>
  </div>

  <form id="bsc-label-pdf-export-form"
        method="post"
        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
        target="_blank"
        style="display:none">
    <input type="hidden" name="action" value="bsc_order_labels_pdf">
    <input type="hidden" name="bsc_order_labels_pdf_nonce" value="<?php echo esc_attr( $pdf_export_nonce ); ?>">
    <input type="hidden" id="bsc-label-pdf-width" name="width_mm" value="<?php echo esc_attr( $default_width_mm ); ?>">
    <input type="hidden" id="bsc-label-pdf-height" name="height_mm" value="<?php echo esc_attr( $default_height_mm ); ?>">
    <?php foreach ( $label_order_ids as $label_order_id ) : ?>
      <input type="hidden" name="order_ids[]" value="<?php echo esc_attr( $label_order_id ); ?>">
    <?php endforeach; ?>
  </form>

  <?php foreach ( $labels as $vm ) : ?>
  <div class="bsc-print-label-page">
    <article class="bsc-print-label"
             aria-label="Etiqueta pedido #<?php echo esc_attr( $vm->order_number ); ?>">

      <header class="bsc-print-label__header">
        <div class="bsc-print-label__header-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50" width="100%" height="100%">
            <circle cx="25" cy="25" r="22" stroke="white" stroke-width="2.2" fill="none"/>
            <circle cx="18" cy="20" r="2.8" fill="white"/>
            <circle cx="32" cy="20" r="2.8" fill="white"/>
            <path d="M 14 30 Q 25 42 36 30" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round"/>
          </svg>
        </div>

        <div class="bsc-print-label__header-copy">
          <div class="bsc-print-label__header-title">delicado</div>
          <div class="bsc-print-label__header-sub">
            <strong>NO</strong> poner peso encima :)
          </div>
        </div>
      </header>

      <div class="bsc-print-label__claims">
        <div class="bsc-print-label__claims-group">
          <div class="bsc-print-label__claim">
            <span class="bsc-print-label__claim-icon">*</span>
            100% Original
          </div>
          <div class="bsc-print-label__claim">
            <span class="bsc-print-label__claim-icon">*</span>
            K-beauty lover
          </div>
          <div class="bsc-print-label__claim">
            <span class="bsc-print-label__claim-icon">*</span>
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

      <section class="bsc-print-label__observations">
        <div class="bsc-print-label__obs-label">Observaciones</div>
        <div class="bsc-print-label__obs-value">
          <?php echo esc_html( $vm->observations ); ?>
        </div>
      </section>

      <footer class="bsc-print-label__footer">
        <div class="bsc-print-label__footer-brand">BSC</div>

        <svg class="bsc-print-label__footer-waves"
             xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 80 12"
             aria-hidden="true">
          <path d="M0 6 C10 0 20 12 30 6 C40 0 50 12 60 6 C70 0 80 12 80 6"
                stroke="#111"
                stroke-width="1.5"
                fill="none"/>
        </svg>

        <div class="bsc-print-label__footer-tagline" aria-label="Skin Care First">
          <span>SKIN</span>
          <span>CARE</span>
          <span>FIRST</span>
        </div>
      </footer>
    </article>
  </div>
  <?php endforeach; ?>

  <script>
  (function () {
    var defaultWidth = <?php echo wp_json_encode( $default_width_mm ); ?>;
    var defaultHeight = <?php echo wp_json_encode( $default_height_mm ); ?>;
    var autoPrint = <?php echo $autoprint ? 'true' : 'false'; ?>;
    var storageWidthKey = 'bsc-order-label-width-mm';
    var storageHeightKey = 'bsc-order-label-height-mm';
    var widthInput = document.getElementById('bsc-label-width-mm');
    var heightInput = document.getElementById('bsc-label-height-mm');
    var sizeStyle = document.getElementById('bsc-label-dynamic-size');
    var pdfWidthInput = document.getElementById('bsc-label-pdf-width');
    var pdfHeightInput = document.getElementById('bsc-label-pdf-height');
    var pdfForm = document.getElementById('bsc-label-pdf-export-form');

    function normalizeMm(value, fallbackValue) {
      var parsed = parseFloat(value);
      if (!isFinite(parsed) || parsed < 10) {
        return fallbackValue;
      }

      return Math.round(parsed * 100) / 100;
    }

    function buildSizeCss(widthMm, heightMm) {
      return [
        '@page { size: ' + widthMm + 'mm ' + heightMm + 'mm; margin: 0; }',
        '.bsc-labels-toolbar { max-width: calc(' + widthMm + 'mm + 20px); }',
        '.bsc-print-label {',
        '  width: ' + widthMm + 'mm;',
        '  height: ' + heightMm + 'mm;',
        '  max-width: ' + widthMm + 'mm;',
        '  max-height: ' + heightMm + 'mm;',
        '}',
        '@media print {',
        '  @page { size: ' + widthMm + 'mm ' + heightMm + 'mm; margin: 0; }',
        '  .bsc-print-label {',
        '    width: ' + widthMm + 'mm !important;',
        '    height: ' + heightMm + 'mm !important;',
        '    max-width: ' + widthMm + 'mm !important;',
        '    max-height: ' + heightMm + 'mm !important;',
        '  }',
        '}'
      ].join('\n');
    }

    function applySize(widthMm, heightMm, persist) {
      var normalizedWidth = normalizeMm(widthMm, defaultWidth);
      var normalizedHeight = normalizeMm(heightMm, defaultHeight);

      widthInput.value = normalizedWidth;
      heightInput.value = normalizedHeight;
      pdfWidthInput.value = normalizedWidth;
      pdfHeightInput.value = normalizedHeight;
      sizeStyle.textContent = buildSizeCss(normalizedWidth, normalizedHeight);

      if (persist) {
        window.localStorage.setItem(storageWidthKey, String(normalizedWidth));
        window.localStorage.setItem(storageHeightKey, String(normalizedHeight));
      }
    }

    var api = {
      downloadPdf: function () {
        applySize(widthInput.value, heightInput.value, true);
        pdfForm.submit();
      },
      print: function () {
        applySize(widthInput.value, heightInput.value, true);
        window.print();
      },
      resetSize: function () {
        applySize(defaultWidth, defaultHeight, true);
      }
    };

    window.BSCLabelPrint = api;

    widthInput.addEventListener('input', function () {
      applySize(widthInput.value, heightInput.value, true);
    });

    heightInput.addEventListener('input', function () {
      applySize(widthInput.value, heightInput.value, true);
    });

    window.addEventListener('beforeprint', function () {
      applySize(widthInput.value, heightInput.value, true);
    });

    applySize(
      window.localStorage.getItem(storageWidthKey) || defaultWidth,
      window.localStorage.getItem(storageHeightKey) || defaultHeight,
      false
    );

    if (autoPrint) {
      window.addEventListener('load', function () {
        api.print();
      });
    }
  }());
  </script>

</body>
</html>
