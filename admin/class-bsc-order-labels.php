<?php
/**
 * BSC Order Labels - ViewModel + preview/PDF render entry point.
 * Bulk action: print_order_labels -> bsc_render_order_labels()
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_bsc_order_labels_pdf', 'bsc_handle_order_labels_pdf_request' );

final class BSC_Order_Label_ViewModel {

	public string $order_number;
	public string $name;
	public string $phone;
	public string $address_line1;
	public string $address_line2;
	public string $city;
	public string $state;
	public string $observations;
	public string $contains;

	private function __construct() {}

	public static function from_order( WC_Order $order ): self {
		$vm               = new self();
		$vm->order_number = (string) $order->get_order_number();
		$vm->name         = self::resolve_name( $order );
		$vm->phone        = self::resolve_phone( $order );
		list( $vm->address_line1, $vm->address_line2 ) = self::resolve_address( $order );
		$vm->city                                      = self::resolve_city( $order );
		$vm->state                                     = self::resolve_state( $order );
		$vm->observations                              = self::resolve_observations( $order );
		$vm->contains                                  = self::resolve_contains( $order );
		return $vm;
	}

	private static function resolve_name( WC_Order $order ): string {
		$name = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() );
		if ( $name === '' ) {
			$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		}
		if ( $name === '' ) {
			$user = $order->get_user();
			$name = $user ? $user->display_name : 'Cliente';
		}
		return $name;
	}

	private static function resolve_phone( WC_Order $order ): string {
		$phone = trim( (string) $order->get_meta( '_shipping_phone' ) );
		if ( $phone === '' ) {
			$phone = trim( (string) $order->get_billing_phone() );
		}
		return $phone;
	}

	/**
	 * @return array [line1, line2]
	 */
	private static function resolve_address( WC_Order $order ): array {
		$line1 = trim( $order->get_shipping_address_1() );
		$line2 = trim( $order->get_shipping_address_2() );

		if ( $line1 === '' ) {
			$line1 = trim( $order->get_billing_address_1() );
			$line2 = trim( $order->get_billing_address_2() );
		}

		$barrio = trim( (string) ( $order->get_meta( '_shipping_barrio' ) ?: $order->get_meta( '_billing_barrio' ) ) );
		if ( $barrio !== '' ) {
			$line2 = $line2 !== '' ? $line2 . ', ' . $barrio : $barrio;
		}

		return array( $line1, $line2 );
	}

	private static function resolve_city( WC_Order $order ): string {
		$city = trim( $order->get_shipping_city() ?: $order->get_billing_city() );

		if ( $city !== '' && function_exists( 'bsc_resolve_colombia_city_label' ) ) {
			return bsc_resolve_colombia_city_label( $city );
		}

		if ( $city !== '' && function_exists( 'bsc_get_colombia_shipping_places' ) ) {
			foreach ( bsc_get_colombia_shipping_places() as $_dept => $cities ) {
				if ( is_array( $cities ) && isset( $cities[ $city ] ) ) {
					$city = (string) $cities[ $city ];
					break;
				}
			}
		}

		return $city;
	}

	private static function resolve_state( WC_Order $order ): string {
		$country = $order->get_shipping_country() ?: $order->get_billing_country() ?: 'CO';
		$state   = $order->get_shipping_state() ?: $order->get_billing_state();

		if ( $state !== '' ) {
			$states = WC()->countries->get_states( $country );
			if ( is_array( $states ) && isset( $states[ $state ] ) ) {
				$state = $states[ $state ];
			}
		}

		return (string) $state;
	}

	private static function resolve_observations( WC_Order $order ): string {
		$obs = trim( (string) $order->get_meta( '_bsc_dispatch_notes' ) );
		if ( $obs !== '' ) {
			return $obs;
		}

		$note = trim( $order->get_customer_note() );
		if ( $note !== '' ) {
			return $note;
		}

		return 'COSMETICOS, DELICADO! NO PONER PESO ENCIMA';
	}

	private static function resolve_contains( WC_Order $order ): string {
		$items = $order->get_items();
		if ( empty( $items ) ) {
			return '-';
		}

		$names = array();
		foreach ( $items as $item ) {
			$names[] = self::resolve_contains_item_name( $item );
		}

		$count = count( $names );
		if ( $count === 1 ) {
			return self::truncate( $names[0], 45 );
		}

		if ( $count <= 3 ) {
			$short  = array_map( array( __CLASS__, 'truncate_for_summary' ), $names );
			$joined = implode( ', ', $short );
			if ( self::string_length( $joined ) <= 85 ) {
				return $joined;
			}
		}

		return $count . ' productos';
	}

	/**
	 * Resolve a compact product + variant label for shipping labels.
	 *
	 * @param WC_Order_Item $item Order line item.
	 * @return string
	 */
	private static function resolve_contains_item_name( WC_Order_Item $item ): string {
		$name = $item->get_name();

		if ( ! $item instanceof WC_Order_Item_Product || ! function_exists( 'bsc_format_order_item_variant_label' ) ) {
			return $name;
		}

		$variant_label = bsc_format_order_item_variant_label( $item );
		if ( '' === $variant_label ) {
			return $name;
		}

		return self::truncate( $name, 24 ) . ' - ' . self::truncate( $variant_label, 18 );
	}

	private static function truncate_for_summary( string $name ): string {
		if ( false !== strpos( $name, ' - ' ) ) {
			list( $product_name, $variant_label ) = explode( ' - ', $name, 2 );

			return self::truncate( $product_name, 13 ) . ' - ' . self::truncate( $variant_label, 12 );
		}

		return self::truncate( $name, 28 );
	}

	private static function truncate( string $value, int $limit ): string {
		if ( self::string_length( $value ) <= $limit ) {
			return $value;
		}

		return self::string_substr( $value, 0, $limit - 3 ) . '...';
	}

	private static function string_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}

	private static function string_substr( string $value, int $start, ?int $length = null ): string {
		if ( function_exists( 'mb_substr' ) ) {
			return null === $length ? mb_substr( $value, $start ) : mb_substr( $value, $start, $length );
		}

		return null === $length ? substr( $value, $start ) : substr( $value, $start, $length );
	}
}

function bsc_render_order_labels( array $order_ids ): void {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_orders' ) ) {
		wp_die( esc_html__( 'Sin permisos.', 'bsc-2-0' ) );
	}

	$labels = array();
	foreach ( $order_ids as $id ) {
		$order = wc_get_order( absint( $id ) );
		if ( $order instanceof WC_Order ) {
			$labels[] = BSC_Order_Label_ViewModel::from_order( $order );
		}
	}

	if ( empty( $labels ) ) {
		wp_die( esc_html__( 'No se encontraron ordenes validas.', 'bsc-2-0' ) );
	}

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only print mode toggle.
	$autoprint        = isset( $_GET['autoprint'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['autoprint'] ) );
	$label_order_ids  = array_values( array_filter( array_map( 'absint', $order_ids ) ) );
	$pdf_export_nonce = wp_create_nonce( 'bsc_order_labels_pdf' );
	$template         = get_template_directory() . '/admin/order-label-print.php';

	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	header( 'Content-Type: text/html; charset=UTF-8' );

	if ( file_exists( $template ) ) {
		include $template;
	} else {
		echo '<p>Error: template no encontrado (' . esc_html( $template ) . ').</p>';
	}

	exit;
}

function bsc_handle_order_labels_pdf_request(): void {
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_orders' ) ) {
		wp_die( esc_html__( 'Sin permisos.', 'bsc-2-0' ) );
	}

	check_admin_referer( 'bsc_order_labels_pdf', 'bsc_order_labels_pdf_nonce' );

	$order_ids = array_values(
		array_filter(
			array_map( 'absint', (array) ( $_POST['order_ids'] ?? array() ) )
		)
	);

	if ( empty( $order_ids ) ) {
		wp_die( esc_html__( 'No se recibieron pedidos para exportar.', 'bsc-2-0' ) );
	}

	$labels = array();
	foreach ( $order_ids as $id ) {
		$order = wc_get_order( $id );
		if ( $order instanceof WC_Order ) {
			$labels[] = BSC_Order_Label_ViewModel::from_order( $order );
		}
	}

	if ( empty( $labels ) ) {
		wp_die( esc_html__( 'No se encontraron pedidos validos para generar el PDF.', 'bsc-2-0' ) );
	}

	$width_mm  = max( 10, floatval( wp_unslash( $_POST['width_mm'] ?? 100 ) ) );
	$height_mm = max( 10, floatval( wp_unslash( $_POST['height_mm'] ?? 150 ) ) );

	bsc_render_order_labels_pdf( $labels, $width_mm, $height_mm );
}

function bsc_render_order_labels_pdf( array $labels, float $width_mm, float $height_mm ): void {
	$page_width_pt  = bsc_label_pdf_mm_to_pt( $width_mm );
	$page_height_pt = bsc_label_pdf_mm_to_pt( $height_mm );

	$objects = array(
		1 => '<< /Type /Catalog /Pages 2 0 R >>',
		3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
		4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
	);

	$page_refs = array();
	$next_id   = 5;

	foreach ( $labels as $vm ) {
		if ( ! $vm instanceof BSC_Order_Label_ViewModel ) {
			continue;
		}

		$page_id     = $next_id++;
		$content_id  = $next_id++;
		$page_refs[] = $page_id . ' 0 R';
		$stream      = bsc_build_order_label_pdf_stream( $vm, $width_mm, $height_mm );

		$objects[ $page_id ] = sprintf(
			'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.3F %.3F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
			$page_width_pt,
			$page_height_pt,
			$content_id
		);

		$objects[ $content_id ] = sprintf(
			"<< /Length %d >>\nstream\n%s\nendstream",
			strlen( $stream ),
			$stream
		);
	}

	$objects[2] = sprintf(
		'<< /Type /Pages /Count %d /Kids [ %s ] >>',
		count( $page_refs ),
		implode( ' ', $page_refs )
	);

	ksort( $objects );

	$pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
	$offsets = array( 0 );

	foreach ( $objects as $id => $content ) {
		$offsets[ $id ] = strlen( $pdf );
		$pdf           .= $id . " 0 obj\n" . $content . "\nendobj\n";
	}

	$xref_offset = strlen( $pdf );
	$max_object  = max( array_keys( $objects ) );

	$pdf .= "xref\n0 " . ( $max_object + 1 ) . "\n";
	$pdf .= "0000000000 65535 f \n";

	for ( $i = 1; $i <= $max_object; $i++ ) {
		$offset = $offsets[ $i ] ?? 0;
		$pdf   .= sprintf( "%010d 00000 n \n", $offset );
	}

	$pdf .= "trailer\n";
	$pdf .= sprintf( "<< /Size %d /Root 1 0 R >>\n", $max_object + 1 );
	$pdf .= "startxref\n" . $xref_offset . "\n%%EOF";

	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="bsc-etiquetas-despacho.pdf"' );
	header( 'Cache-Control: private, max-age=0, must-revalidate' );
	header( 'Pragma: public' );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw PDF binary stream.
	echo $pdf;
	exit;
}

function bsc_build_order_label_pdf_stream( BSC_Order_Label_ViewModel $vm, float $width_mm, float $height_mm ): string {
	$commands   = array();
	$commands[] = '1 J 1 j';

	$commands[] = bsc_label_pdf_rect( 0, 0, $width_mm, 35, $height_mm, array( 17, 17, 17 ) );
	$commands[] = bsc_label_pdf_text( ':)', 6, 10, 'F2', 24, $height_mm, array( 255, 255, 255 ) );
	$commands[] = bsc_label_pdf_text( 'delicado', 25, 11.5, 'F2', 22, $height_mm, array( 255, 255, 255 ) );
	$commands[] = bsc_label_pdf_text( 'NO poner peso encima :)', 25, 23.5, 'F1', 7.2, $height_mm, array( 255, 255, 255 ) );

	$commands[] = bsc_label_pdf_line( 0, 51, $width_mm, 51, $height_mm, 0.3, array( 204, 204, 204 ) );
	$commands[] = bsc_label_pdf_line( 22.33, 35, 22.33, 51, $height_mm, 0.25, array( 187, 187, 187 ), true );
	$commands[] = bsc_label_pdf_line( 44.66, 35, 44.66, 51, $height_mm, 0.25, array( 187, 187, 187 ), true );
	$commands[] = bsc_label_pdf_line( 67, 35, 67, 51, $height_mm, 0.25, array( 187, 187, 187 ), true );

	$commands[] = bsc_label_pdf_center_text( '100% Original', 0, 43.2, 22.33, 'F2', 6.2, $height_mm, array( 17, 17, 17 ) );
	$commands[] = bsc_label_pdf_center_text( 'K-beauty lover', 22.33, 43.2, 22.33, 'F2', 6.2, $height_mm, array( 17, 17, 17 ) );
	$commands[] = bsc_label_pdf_center_text( 'Glow gang', 44.66, 43.2, 22.34, 'F2', 6.2, $height_mm, array( 17, 17, 17 ) );
	$commands[] = bsc_label_pdf_text( 'CONTIENE', 70, 37.3, 'F2', 6.1, $height_mm, array( 85, 85, 85 ) );
	$commands[] = bsc_label_pdf_multiline_text(
		bsc_label_pdf_wrap_text( $vm->contains, 16, 4 ),
		70,
		41.6,
		'F1',
		6.1,
		7.2,
		$height_mm,
		array( 17, 17, 17 )
	);

	$current_top  = 66.5;
	$commands[]   = bsc_label_pdf_text( $vm->name, 5, $current_top, 'F2', 13.5, $height_mm, array( 17, 17, 17 ) );
	$current_top += 9.5;

	if ( $vm->phone !== '' ) {
		$commands[]   = bsc_label_pdf_text( $vm->phone, 5, $current_top, 'F2', 10.8, $height_mm, array( 17, 17, 17 ) );
		$current_top += 7.2;
	}

	$address_lines = bsc_label_pdf_wrap_text( trim( $vm->address_line1 . ' ' . $vm->address_line2 ), 32, 3 );
	foreach ( $address_lines as $address_line ) {
		$commands[]   = bsc_label_pdf_text( $address_line, 5, $current_top, 'F1', 9.4, $height_mm, array( 17, 17, 17 ) );
		$current_top += 5.8;
	}

	$location = trim( implode( ', ', array_filter( array( $vm->city, $vm->state ) ) ) );
	if ( $location !== '' ) {
		$location_lines = bsc_label_pdf_wrap_text( strtoupper( $location ), 28, 2 );
		foreach ( $location_lines as $location_line ) {
			$commands[]   = bsc_label_pdf_text( $location_line, 5, $current_top, 'F2', 8.4, $height_mm, array( 17, 17, 17 ) );
			$current_top += 5.4;
		}
	}

	$commands[] = bsc_label_pdf_line( 0, 105, $width_mm, 105, $height_mm, 0.3, array( 204, 204, 204 ) );
	$commands[] = bsc_label_pdf_text( 'OBSERVACIONES', 5, 108, 'F2', 6.1, $height_mm, array( 85, 85, 85 ) );
	$commands[] = bsc_label_pdf_multiline_text(
		bsc_label_pdf_wrap_text( strtoupper( $vm->observations ), 34, 4 ),
		5,
		112.5,
		'F2',
		7.2,
		8.4,
		$height_mm,
		array( 17, 17, 17 )
	);

	$commands[] = bsc_label_pdf_line( 0, 125, $width_mm, 125, $height_mm, 0.3, array( 204, 204, 204 ) );
	$commands[] = bsc_label_pdf_center_text( 'BSC', 0, 131.8, $width_mm, 'F2', 23, $height_mm, array( 17, 17, 17 ) );
	$commands[] = bsc_label_pdf_waves( 34, 139.6, 32, $height_mm );
	$commands[] = bsc_label_pdf_center_text( 'SKIN   CARE   FIRST', 0, 145.4, $width_mm, 'F2', 8.2, $height_mm, array( 17, 17, 17 ) );

	return implode( "\n", array_filter( $commands ) );
}

function bsc_label_pdf_mm_to_pt( float $mm ): float {
	return $mm * 72 / 25.4;
}

function bsc_label_pdf_color( array $rgb ): string {
	return sprintf(
		'%.3F %.3F %.3F',
		max( 0, min( 255, (int) $rgb[0] ) ) / 255,
		max( 0, min( 255, (int) $rgb[1] ) ) / 255,
		max( 0, min( 255, (int) $rgb[2] ) ) / 255
	);
}

function bsc_label_pdf_escape_text( string $text ): string {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );

	if ( function_exists( 'iconv' ) ) {
		$converted = @iconv( 'UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text );
		if ( false !== $converted ) {
			$text = $converted;
		}
	}

	return strtr(
		$text,
		array(
			'\\' => '\\\\',
			'('  => '\\(',
			')'  => '\\)',
		)
	);
}

function bsc_label_pdf_text_width_pt( string $text, float $font_size ): float {
	return strlen( bsc_label_pdf_escape_text( $text ) ) * $font_size * 0.47;
}

function bsc_label_pdf_rect( float $x_mm, float $top_mm, float $width_mm, float $height_box_mm, float $page_height_mm, array $rgb ): string {
	$x_pt = bsc_label_pdf_mm_to_pt( $x_mm );
	$y_pt = bsc_label_pdf_mm_to_pt( $page_height_mm - $top_mm - $height_box_mm );
	$w_pt = bsc_label_pdf_mm_to_pt( $width_mm );
	$h_pt = bsc_label_pdf_mm_to_pt( $height_box_mm );

	return sprintf(
		'q %s rg %.3F %.3F %.3F %.3F re f Q',
		bsc_label_pdf_color( $rgb ),
		$x_pt,
		$y_pt,
		$w_pt,
		$h_pt
	);
}

function bsc_label_pdf_line( float $x1_mm, float $top1_mm, float $x2_mm, float $top2_mm, float $page_height_mm, float $width_pt, array $rgb, bool $dashed = false ): string {
	$x1_pt = bsc_label_pdf_mm_to_pt( $x1_mm );
	$y1_pt = bsc_label_pdf_mm_to_pt( $page_height_mm - $top1_mm );
	$x2_pt = bsc_label_pdf_mm_to_pt( $x2_mm );
	$y2_pt = bsc_label_pdf_mm_to_pt( $page_height_mm - $top2_mm );
	$dash  = $dashed ? '[2 2] 0 d ' : '[] 0 d ';

	return sprintf(
		'q %s RG %.3F w %s%.3F %.3F m %.3F %.3F l S Q',
		bsc_label_pdf_color( $rgb ),
		$width_pt,
		$dash,
		$x1_pt,
		$y1_pt,
		$x2_pt,
		$y2_pt
	);
}

function bsc_label_pdf_text( string $text, float $x_mm, float $top_mm, string $font_key, float $font_size_pt, float $page_height_mm, array $rgb ): string {
	$escaped = bsc_label_pdf_escape_text( $text );
	if ( $escaped === '' ) {
		return '';
	}

	$x_pt = bsc_label_pdf_mm_to_pt( $x_mm );
	$y_pt = bsc_label_pdf_mm_to_pt( $page_height_mm - $top_mm ) - $font_size_pt;

	return sprintf(
		'BT %s rg /%s %.3F Tf 1 0 0 1 %.3F %.3F Tm (%s) Tj ET',
		bsc_label_pdf_color( $rgb ),
		$font_key,
		$font_size_pt,
		$x_pt,
		$y_pt,
		$escaped
	);
}

function bsc_label_pdf_center_text( string $text, float $x_mm, float $top_mm, float $box_width_mm, string $font_key, float $font_size_pt, float $page_height_mm, array $rgb ): string {
	$estimated_width_pt = bsc_label_pdf_text_width_pt( $text, $font_size_pt );
	$box_width_pt       = bsc_label_pdf_mm_to_pt( $box_width_mm );
	$offset_pt          = max( 0, ( $box_width_pt - $estimated_width_pt ) / 2 );
	$final_x_mm         = $x_mm + ( $offset_pt * 25.4 / 72 );

	return bsc_label_pdf_text( $text, $final_x_mm, $top_mm, $font_key, $font_size_pt, $page_height_mm, $rgb );
}

function bsc_label_pdf_multiline_text( array $lines, float $x_mm, float $top_mm, string $font_key, float $font_size_pt, float $line_height_pt, float $page_height_mm, array $rgb ): string {
	if ( empty( $lines ) ) {
		return '';
	}

	$x_pt       = bsc_label_pdf_mm_to_pt( $x_mm );
	$y_pt       = bsc_label_pdf_mm_to_pt( $page_height_mm - $top_mm ) - $font_size_pt;
	$text_lines = array();

	foreach ( $lines as $index => $line ) {
		$escaped = bsc_label_pdf_escape_text( $line );
		if ( $escaped === '' ) {
			continue;
		}

		$text_lines[] = 0 === $index ? '(' . $escaped . ') Tj' : 'T* (' . $escaped . ') Tj';
	}

	if ( empty( $text_lines ) ) {
		return '';
	}

	return sprintf(
		'BT %s rg /%s %.3F Tf %.3F TL 1 0 0 1 %.3F %.3F Tm %s ET',
		bsc_label_pdf_color( $rgb ),
		$font_key,
		$font_size_pt,
		$line_height_pt,
		$x_pt,
		$y_pt,
		implode( ' ', $text_lines )
	);
}

function bsc_label_pdf_wrap_text( string $text, int $max_chars, int $max_lines ): array {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
	if ( $text === '' ) {
		return array();
	}

	$words = preg_split( '/\s+/u', $text ) ?: array();
	$lines = array();
	$line  = '';

	foreach ( $words as $word ) {
		$candidate = $line === '' ? $word : $line . ' ' . $word;
		if ( bsc_label_pdf_string_length( $candidate ) <= $max_chars ) {
			$line = $candidate;
			continue;
		}

		if ( $line !== '' ) {
			$lines[] = $line;
			$line    = '';
		}

		while ( bsc_label_pdf_string_length( $word ) > $max_chars ) {
			$lines[] = bsc_label_pdf_string_substr( $word, 0, $max_chars - 3 ) . '...';
			$word    = bsc_label_pdf_string_substr( $word, $max_chars - 3 );
		}

		$line = $word;
	}

	if ( $line !== '' ) {
		$lines[] = $line;
	}

	if ( count( $lines ) > $max_lines ) {
		$lines      = array_slice( $lines, 0, $max_lines );
		$last_index = $max_lines - 1;
		$last_line  = rtrim( $lines[ $last_index ] );
		if ( bsc_label_pdf_string_length( $last_line ) >= $max_chars ) {
			$last_line = bsc_label_pdf_string_substr( $last_line, 0, $max_chars - 3 );
		}
		$lines[ $last_index ] = rtrim( $last_line, '. ' ) . '...';
	}

	return $lines;
}

function bsc_label_pdf_string_length( string $value ): int {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
}

function bsc_label_pdf_string_substr( string $value, int $start, ?int $length = null ): string {
	if ( function_exists( 'mb_substr' ) ) {
		return null === $length ? mb_substr( $value, $start ) : mb_substr( $value, $start, $length );
	}

	return null === $length ? substr( $value, $start ) : substr( $value, $start, $length );
}

function bsc_label_pdf_waves( float $x_mm, float $top_mm, float $width_mm, float $page_height_mm ): string {
	$y_mm       = $top_mm + 1.8;
	$segment_mm = $width_mm / 4;
	$commands   = array( 'q ' . bsc_label_pdf_color( array( 17, 17, 17 ) ) . ' RG 1.2 w [] 0 d' );

	$start_x_pt = bsc_label_pdf_mm_to_pt( $x_mm );
	$start_y_pt = bsc_label_pdf_mm_to_pt( $page_height_mm - $y_mm );
	$commands[] = sprintf( '%.3F %.3F m', $start_x_pt, $start_y_pt );

	for ( $i = 0; $i < 4; $i++ ) {
		$segment_start = $x_mm + ( $segment_mm * $i );
		$x1            = $segment_start + ( $segment_mm * 0.33 );
		$x2            = $segment_start + ( $segment_mm * 0.66 );
		$x3            = $segment_start + $segment_mm;
		$y1            = $y_mm - 1.5;
		$y2            = $y_mm + 1.5;
		$y3            = $y_mm;

		$commands[] = sprintf(
			'%.3F %.3F %.3F %.3F %.3F %.3F c',
			bsc_label_pdf_mm_to_pt( $x1 ),
			bsc_label_pdf_mm_to_pt( $page_height_mm - $y1 ),
			bsc_label_pdf_mm_to_pt( $x2 ),
			bsc_label_pdf_mm_to_pt( $page_height_mm - $y2 ),
			bsc_label_pdf_mm_to_pt( $x3 ),
			bsc_label_pdf_mm_to_pt( $page_height_mm - $y3 )
		);
	}

	$commands[] = 'S Q';

	return implode( ' ', $commands );
}
