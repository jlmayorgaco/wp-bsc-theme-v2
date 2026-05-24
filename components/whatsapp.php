<?php
/**
 * WhatsApp floating button component.
 * Usa bsc_get_whatsapp_url() de inc/bsc-contact-options.php como fuente única del número.
 *
 * @package BSC2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<a
	href="<?php echo esc_url( bsc_get_whatsapp_url( 'general' ) ); ?>"
	class="footer__whatsapp whatsapp-float"
	target="_blank"
	rel="noopener noreferrer"
	aria-label="Contactar por WhatsApp"
>
	<i class="fab fa-whatsapp" aria-hidden="true"></i>
	<span class="footer__wa">WhatsApp</span>
</a>
