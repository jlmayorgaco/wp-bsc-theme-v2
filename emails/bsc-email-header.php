<?php
/**
 * BSC-053: Shared email header partial.
 * Variables: $email_title (string), $emoji (string)
 */
defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $email_title ?? 'Bubble Skin Care' ); ?></title>
</head>
<body style="margin:0;padding:0;background:#fdf4f8;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#333">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#fdf4f8;padding:32px 16px">
  <tr>
    <td align="center">
      <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.07)">

        <!-- Header -->
        <tr>
          <td style="background:#f8c0cd;padding:28px 40px;text-align:center">
            <p style="margin:0;font-size:26px;font-weight:900;color:#222;letter-spacing:1px">Bubble Skin Care</p>
            <p style="margin:6px 0 0;font-size:12px;color:#555;letter-spacing:2px;text-transform:uppercase">K-Beauty Colombia</p>
          </td>
        </tr>

        <!-- Hero emoji + title -->
        <tr>
          <td style="padding:36px 40px 20px;text-align:center">
            <p style="font-size:40px;margin:0"><?php echo esc_html( $emoji ?? '🌸' ); ?></p>
            <h1 style="font-size:21px;font-weight:800;margin:12px 0 0;color:#222">
              <?php echo esc_html( $email_title ?? '' ); ?>
            </h1>
          </td>
        </tr>
