<?php
/**
 * BSC Contact Options — helper central para WhatsApp y datos de contacto.
 *
 * FUENTE ÚNICA DE VERDAD para el número de WhatsApp y los mensajes por contexto.
 * No hardcodear el número en ningún otro archivo del theme.
 *
 * @package BSC2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna el número de WhatsApp solo con dígitos.
 *
 * @return string
 */
function bsc_get_whatsapp_number() {
	$phone = (string) get_option( 'bsc_whatsapp_number', '573156922859' );
	$phone = preg_replace( '/\D+/', '', $phone );

	return $phone ?: '573156922859';
}

/**
 * Retorna la URL completa de WhatsApp con número y mensaje pre-llenado.
 *
 * @param string $context  'general' | 'product' | 'support' | 'order' | 'encargo'
 * @return string URL de WhatsApp con el mensaje codificado.
 */
function bsc_get_whatsapp_url( $context = 'general' ) {
	// BSC-064: configurable from BSC Settings admin page
	$phone = bsc_get_whatsapp_number();

	$messages = array(
		'general' => '¡Hola Bubble Skin Care! 🌈✨💗 Quiero más información sobre sus productos.',
		'product' => '¡Hola Bubble Skin Care! Tengo una pregunta sobre un producto de la tienda.',
		'support' => '¡Hola Bubble Skin Care! Necesito ayuda con mi pedido.',
		'order'   => '¡Hola Bubble Skin Care! Tuve un problema con mi pedido y necesito ayuda.',
		'encargo' => '¡Hola Bubble Skin Care! 🌈✨💗 Quiero hacer un encargo.',
	);

	$msg = $messages[ $context ] ?? $messages['general'];

	return 'https://api.whatsapp.com/send?phone=' . $phone . '&text=' . rawurlencode( $msg );
}

/**
 * Retorna el número de WhatsApp formateado para mostrar.
 *
 * @return string Número en formato +57 315 692 2859
 */
function bsc_get_whatsapp_display() {
	$phone = bsc_get_whatsapp_number();

	if ( 12 === strlen( $phone ) && 0 === strpos( $phone, '57' ) ) {
		return sprintf(
			'+%s %s %s %s',
			substr( $phone, 0, 2 ),
			substr( $phone, 2, 3 ),
			substr( $phone, 5, 3 ),
			substr( $phone, 8, 4 )
		);
	}

	return '+' . ltrim( $phone, '+' );
}
