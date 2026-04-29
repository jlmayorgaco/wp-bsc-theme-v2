<header class="bsc bsc__header bsc__header--desktop">
    <div class="header__container">
        <a class="header__image" href="/">
            <img src="<?php echo get_template_directory_uri();?>/images/bsc_logo_header.png">
        </a>
        <div class="header__nav">
            <?php
                echo $headerNav->renderNavButtons();
			?>
        </div>
        <div class="header__menu">
            <ul class="menu__icons">
                <li class="menu__icon icon--search">
                    <button class="btn-search-toggle">
                          <div class="image__icon-hoverable">
                                <img class="image__icon icon--normal" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_header__search-icon--hover.png">
                                <img class="image__icon icon--hover" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_header__search-icon--hover.png">
                          </div>
                    </button>
                    
                    <div class="bsc header__search hidden">
                        <input type="text" class="header-search-input" placeholder="Buscar productos...">
                        <ul class="search-results"></ul>
                    </div>
                </li>
                <?php if (is_user_logged_in()) : ?>
                    <li class="menu__icon icon--profile">
                        <button id="profile-button" aria-haspopup="true" aria-expanded="false">
                            <div class="image__icon-hoverable">
                                <img class="image__icon icon--normal" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_header__profile-icon--hover.png">
                                <img class="image__icon icon--hover" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_header__profile-icon--hover.png">
                            </div>
                        </button>
                        <div id="profile-dropdown" class="bsc__profile-dropdown">
                        <ul>
                            <li class="bsc__profile-dropdown-item--hidden"><a href="<?php echo esc_url($my_account_url); ?>">Mi perfil</a></li>
                            <li><hr><a href="<?php echo esc_url($orders_url); ?>">Mis pedidos</a></li>
                            <li><hr><a href="<?php echo esc_url($bubble_points_url); ?>">Mis puntos</a></li>
                            <li><hr><a href="<?php echo esc_url($edit_account_url); ?>">Mis datos</a></li>
                            <li><hr><a href="<?php echo esc_url($edit_address_url); ?>">Mis direcciones</a></li>
                            <li><hr><a href="<?php echo esc_url($logout_url); ?>">Cerrar sesión</a></li>
                        </ul>
                        </div>
                    </li>
                    <?php else : ?>
                    <li class="menu__icon icon--profile">
                        <button id="profile-button" aria-haspopup="true" aria-expanded="false">
                        <i aria-hidden="true" class="dlicon users_single-03"></i>
                        </button>
                        <div id="profile-dropdown" class="bsc__profile-dropdown">
                        <ul>
                            <li><a href="<?php echo esc_url($login_url); ?>">Iniciar sesión</a></li>
                            <li><hr><a href="<?php echo esc_url($register_url); ?>">Crear cuenta</a></li>
                        </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                <li class="menu__icon icon--shop menu__icon--hidden">
                    <a href="<?php echo esc_url($checkout_url); ?>" aria-label="Ir al checkout" title="Ir al checkout">
                        <i aria-hidden="true" class="dlicon shopping_bag-20"></i>
                        <span></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
<div class="bsc header__nav-menus">
<?php
    echo $headerNav->renderNavContent();
?>
</div>
