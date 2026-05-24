<?php
$coming_soon_css_path = get_template_directory() . '/woocommerce-coming-soon.css';
$coming_soon_css_url  = trailingslashit( get_template_directory_uri() ) . 'woocommerce-coming-soon.css';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>&iexcl;Pronto regresaremos! - Bubbles Skin Care</title>
	<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', file_exists( $coming_soon_css_path ) ? (string) filemtime( $coming_soon_css_path ) : '1', $coming_soon_css_url ) ); ?>">
</head>
<body class="bsc-coming-soon-body">
	<div class="coming-soon-container">
		<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/BSC_COMING_SOON_FACE.png" alt="Bubbles Skin Care">

		<h1>&iexcl;Volvemos pronto!</h1>

		<p>Nuestra p&aacute;gina web est&aacute; en mantenimiento.</p>

		<p><strong>Cont&aacute;ctanos en nuestras redes sociales</strong></p>

		<ul class="social-media-list">
			<li>
				<a href="https://www.instagram.com/bubbles.skincare/" target="_blank" rel="noopener noreferrer">
					<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/BSC_COMING_SOON_SOCIAL_INSTAGRAM.png" alt="Instagram">
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( bsc_get_whatsapp_url( 'general' ) ); ?>" target="_blank" rel="noopener noreferrer">
					<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/BSC_COMING_SOON_SOCIAL_WHATSAPP.png" alt="WhatsApp">
				</a>
			</li>
			<li>
				<a href="https://www.tiktok.com/@bubblesskincare" target="_blank" rel="noopener noreferrer">
					<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/BSC_COMING_SOON_SOCIAL_TIKTOK.png" alt="TikTok">
				</a>
			</li>
		</ul>
	</div>
</body>
</html>
