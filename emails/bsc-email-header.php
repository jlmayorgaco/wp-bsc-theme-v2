<?php
/**
 * Shared BSC email header.
 *
 * Variables:
 * - $email_title (string)
 * - $email_hero (string image filename)
 * - $email_hero_width (int CSS pixels)
 * - $email_shell_width (int CSS pixels)
 * - $email_preheader (string)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$email_title       = (string) ( $email_title ?? 'Bubble Skin Care' );
$email_hero        = (string) ( $email_hero ?? 'bsc-email-hero-smile-pink.png' );
$email_hero_width  = max( 110, (int) ( $email_hero_width ?? 230 ) );
$email_shell_width = max( 320, (int) ( $email_shell_width ?? 670 ) );
$email_preheader   = (string) ( $email_preheader ?? $email_title );
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
	.bsc-email-component-pad { padding-left: 24px !important; padding-right: 24px !important; }
	.bsc-email-component-pad.bsc-email-fixed-card-pad { padding-left: 15px !important; padding-right: 15px !important; }
	.bsc-email-fluid { width: 100% !important; max-width: 100% !important; }
	.bsc-email-button-row { padding-left: 24px !important; padding-right: 24px !important; }
	.bsc-email-button-table { width: 100% !important; }
	.bsc-email-button-cell { display: block !important; padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; }
	.bsc-email-button { box-sizing: border-box !important; min-width: 0 !important; width: 100% !important; }
	.bsc-email-coupon-graphic,
	.bsc-email-ticket-graphic { display: none !important; padding: 0 !important; width: 0 !important; }
	.bsc-email-product-cell { display: block !important; padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; }
	.bsc-email-display { font-size: 28px !important; letter-spacing: .4px !important; line-height: 34px !important; }
}
</style>
</head>
<body style="background:#ffffff;margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
<div class="bsc-email-preheader" style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;mso-hide:all;opacity:0;overflow:hidden;">
	<?php echo esc_html( $email_preheader ); ?>
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;border-collapse:collapse;margin:0;padding:0;width:100%;">
	<tr>
		<td align="center" style="padding:0;">
			<table role="presentation" width="<?php echo esc_attr( $email_shell_width ); ?>" cellpadding="0" cellspacing="0" border="0" class="bsc-email-shell" style="background:#ffffff;border-collapse:collapse;margin:0 auto;max-width:<?php echo esc_attr( $email_shell_width ); ?>px;width:<?php echo esc_attr( $email_shell_width ); ?>px;">
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
						<h1 class="bsc-email-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'title' ) ); ?>margin:0;text-align:center;">
							<?php echo esc_html( $email_title ); ?>
						</h1>
					</td>
				</tr>
				<tr>
					<td align="center" style="padding:14px 0 0;">
						<img src="<?php echo esc_url( bsc_email_asset_url( 'bsc-email-wave.png' ) ); ?>" width="58" alt="" style="border:0;display:block;height:auto;margin:0 auto;max-width:58px;width:58px;">
					</td>
				</tr>
