<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once get_template_directory() . '/plugins/bubble-points/classes/class-bsc-bubble-points-coupon.php';
require_once get_template_directory() . '/plugins/bubble-points/classes/class-bsc-bubble-points.php';

$coupons = require get_template_directory() . '/plugins/bubble-points/config/coupons.php';

$user_id = get_current_user_id();
$points_by_user =  BSC_Bubble_Points::get($user_id);

?>
<div class="bsc profile-points">
    <div class="profile-points__container">
        <h1 class="profile-points__title">¡ Tienes <strong><?php echo number_format($points_by_user); ?></strong> Bubble Points !</h1>
        <div class="profile-points__coupons">
            <?php foreach ($coupons as $coupon_config) :
                $state = $points_by_user >= $coupon_config['points'] ? 'normal' : 'disabled';
                $coupon = new BSC_Bubble_Point_Coupon([
                    'points'    => $coupon_config['points'],
                    'value'     => $coupon_config['price'],
                    'text'      => $coupon_config['text'],
                    'image'     => 'plugins/bubble-points/images/' . $coupon_config['image'] . '.png', 
                    'alt'       => $coupon_config['tag'],
                    'state'     => $state,
                    'color_mod' => $coupon_config['color'] // this must match SCSS modifier names
                ]);
                $coupon->render();
            endforeach; ?>
        </div>
        <div class="profile-points__footer">
            
            <?php require get_template_directory() . '/plugins/bubble-points/views/coupons-bubble-points.php'; ?>

        </div>
        <div class="profile-points__footer">
            <h2>¡En BSC todas tus compras suman Bubble Points que se convierten en K-Beauty GRATISSS!</h2>
            <p>Entre más compras, ¡más ahorras! Cada $1.000 COP en productos coreanos equivale a 1 Bubble Point.
                Tus puntos tienen una vigencia aproximada de 2 años, ya que el programa se actualiza cada enero.
                <strong>Próxima actualización: Enero 2027 :)</strong>
            </p>
        </div>
    </div>
</div>

<?php require get_template_directory() . '/plugins/bubble-points/views/modal-bubble-points.php'; ?>