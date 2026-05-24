<?php
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/plugins/bubble-points/classes/class-bsc-bubble-points-coupon.php';
require_once get_template_directory() . '/plugins/bubble-points/classes/class-bsc-bubble-points.php';

$coupons = require get_template_directory() . '/plugins/bubble-points/config/coupons.php';

$user_id        = get_current_user_id();
$points_by_user = BSC_Bubble_Points::get( $user_id );
?>

<div class="bsc profile-points">
	<div class="profile-points__container">
		<h1 class="profile-points__title"><?php echo esc_html( html_entity_decode( '&#161; Tienes ', ENT_QUOTES, 'UTF-8' ) ); ?><strong><?php echo number_format( $points_by_user ); ?></strong> Bubble Points !</h1>
		<div class="profile-points__coupons">
			<?php
			foreach ($coupons as $coupon_config) :
				$state  = $points_by_user >= $coupon_config['points'] ? 'normal' : 'disabled';
				$coupon = new BSC_Bubble_Point_Coupon(
					array(
						'points'    => $coupon_config['points'],
						'value'     => $coupon_config['price'],
						'text'      => $coupon_config['text'],
						'image'     => 'plugins/bubble-points/images/' . $coupon_config['image'] . '.png',
						'alt'       => $coupon_config['tag'],
						'state'     => $state,
						'color_mod' => $coupon_config['color'],
					)
				);
				$coupon->render();
			endforeach;
			?>
		</div>

		<div class="profile-points__text">
			<h2><?php echo esc_html( html_entity_decode( '&#161;En BSC todas tus compras suman Bubble Points que se convierten en K-Beauty GRATISSS!', ENT_QUOTES, 'UTF-8' ) ); ?></h2>
			<p>
				<?php echo esc_html( html_entity_decode( 'Entre m&aacute;s compras, &#161;m&aacute;s ahorras! Cada $1.000 COP en productos coreanos equivale a 1 Bubble Point. Tus puntos tienen una vigencia de 2 a&ntilde;os, ya que el programa se actualiza cada enero.', ENT_QUOTES, 'UTF-8' ) ); ?>
				<strong><?php echo esc_html( html_entity_decode( 'Pr&oacute;xima actualizaci&oacute;n: Enero ', ENT_QUOTES, 'UTF-8' ) . ( date( 'Y' ) + 2 ) . ' :)' ); ?></strong>
			</p>
		</div>

		<div class="profile-points__footer">
			<?php require get_template_directory() . '/plugins/bubble-points/views/coupons-bubble-points.php'; ?>
		</div>
	</div>
</div>

<?php require get_template_directory() . '/plugins/bubble-points/views/modal-bubble-points.php'; ?>