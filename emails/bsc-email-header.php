<?php
/**
 * Shared BSC email header.
 *
 * Variables:
 * - $email_title (string)
 * - $email_hero (string image filename)
 * - $email_hero_width (int CSS pixels)
 * - $email_preheader (string)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$email_title      = (string) ( $email_title ?? 'Bubble Skin Care' );
$email_hero       = (string) ( $email_hero ?? 'bsc-email-hero-smile-pink.png' );
$email_hero_width = max( 110, (int) ( $email_hero_width ?? 230 ) );
$email_preheader  = (string) ( $email_preheader ?? $email_title );
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title><?php echo esc_html( $email_title ); ?></title>
<style type="text/css">
@media only screen and (max-width: 680px) {
	.bsc-email-shell { width: 100% !important; }
	.bsc-email-mobile-pad { padding-left: 24px !important; padding-right: 24px !important; }
	.bsc-email-fluid { width: 100% !important; max-width: 100% !important; }
}
</style>
</head>
<body style="background:#ffffff;margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;mso-hide:all;opacity:0;overflow:hidden;">
	<?php echo esc_html( $email_preheader ); ?>
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;border-collapse:collapse;margin:0;padding:0;width:100%;">
	<tr>
		<td align="center" style="padding:0;">
			<table role="presentation" width="652" cellpadding="0" cellspacing="0" border="0" class="bsc-email-shell" style="background:#ffffff;border-collapse:collapse;margin:0 auto;max-width:652px;width:652px;">
				<tr>
					<td align="center" style="padding:52px 0 0;">
						<img src="<?php echo esc_url( bsc_email_asset_url( 'bsc-email-logo.png' ) ); ?>" width="233" alt="BSC Skin Care First" style="border:0;display:block;height:auto;margin:0 auto;max-width:233px;width:233px;">
					</td>
				</tr>
				<tr>
					<td align="center" style="padding:58px 0 0;">
						<img src="<?php echo esc_url( bsc_email_asset_url( $email_hero ) ); ?>" width="<?php echo esc_attr( $email_hero_width ); ?>" alt="" style="border:0;display:block;height:auto;margin:0 auto;max-width:<?php echo esc_attr( $email_hero_width ); ?>px;width:<?php echo esc_attr( $email_hero_width ); ?>px;">
					</td>
				</tr>
				<tr>
					<td align="center" class="bsc-email-mobile-pad" style="padding:24px 46px 0;">
						<h1 style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:24px;font-weight:900;letter-spacing:2px;line-height:30px;margin:0;text-align:center;">
							<?php echo esc_html( $email_title ); ?>
						</h1>
					</td>
				</tr>
				<tr>
					<td align="center" style="padding:14px 0 0;">
						<img src="<?php echo esc_url( bsc_email_asset_url( 'bsc-email-wave.png' ) ); ?>" width="58" alt="" style="border:0;display:block;height:auto;margin:0 auto;max-width:58px;width:58px;">
					</td>
				</tr>
