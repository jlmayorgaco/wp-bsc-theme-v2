<?php
/**
 * BSC Order Label print template.
 * Receives: $labels, $autoprint, $label_order_ids, $pdf_export_nonce
 */
defined( 'ABSPATH' ) || exit;

$default_width_mm  = 100;
$default_height_mm = 150;

if ( $default_width_mm <= 0 ) {
	$default_width_mm = 100;
}

if ( $default_height_mm <= 0 ) {
	$default_height_mm = 150;
}

$label_print_css_path = get_template_directory() . '/admin-order-label-print.css';
$label_print_js_path  = get_template_directory() . '/js/admin/bsc-order-label-print.js';
$label_print_css_url  = trailingslashit( get_template_directory_uri() ) . 'admin-order-label-print.css';
$label_print_js_url   = trailingslashit( get_template_directory_uri() ) . 'js/admin/bsc-order-label-print.js';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Etiquetas de despacho - BSC</title>
	<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', file_exists( $label_print_css_path ) ? (string) filemtime( $label_print_css_path ) : '1', $label_print_css_url ) ); ?>">
	<style id="bsc-label-dynamic-size"></style>
</head>
<body
	class="bsc-order-label-print"
	data-default-width-mm="<?php echo esc_attr( $default_width_mm ); ?>"
	data-default-height-mm="<?php echo esc_attr( $default_height_mm ); ?>"
		data-autoprint="<?php echo esc_attr( $autoprint ? '1' : '0' ); ?>">

	<div class="bsc-labels-toolbar">
	<button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--primary"
			type="button"
			data-bsc-label-action="download-pdf">Descargar PDF exacto</button>
	<button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--secondary"
			type="button"
			data-bsc-label-action="print">Imprimir navegador</button>
	<button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--secondary"
			type="button"
			data-bsc-label-action="close-window">Volver</button>
	<button class="bsc-labels-toolbar__btn bsc-labels-toolbar__btn--secondary"
			type="button"
			data-bsc-label-action="reset-size">Restablecer medida</button>

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
		class="bsc-labels-toolbar__pdf-form">
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
		$location = array_filter( array( $vm->city, $vm->state ) );
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

	<script src="<?php echo esc_url( add_query_arg( 'ver', file_exists( $label_print_js_path ) ? (string) filemtime( $label_print_js_path ) : '1', $label_print_js_url ) ); ?>" defer></script>

</body>
</html>
