<header class="bsc bsc__header bsc__header--mobile" id="mobileHeader">
    <div class="header-mobile__nav">

        <div class="header-mobile__nav-left">
            <button
                id="mobileMenuToggle"
                type="button"
                aria-label="Abrir menú"
                aria-controls="mobileSidebar"
                aria-expanded="false"
                class="header-mobile__icon-btn header-mobile__menu-btn"
            >
                <span class="mobile-toggle__icon mobile-toggle__icon--open">
                    <i aria-hidden="true" class="dlicon ui-3_menu-left"></i>
                </span>

                <span class="mobile-toggle__icon mobile-toggle__icon--close" aria-hidden="true">
                    &times;
                </span>
            </button>
        </div>

        <div class="header-mobile__nav-center">
            <a href="/" class="item--logo">
                <img
                    class="header-mobile__logo"
                    src="<?php echo get_template_directory_uri(); ?>/images/bsc_logo_header_mobile.png"
                    alt="Bubbles Skin Care"
                >
            </a>
        </div>

        <div class="header-mobile__nav-right">
            <button
                id="mobile-search-btn"
                aria-label="Buscar productos"
                class="header-mobile__icon-btn header-mobile__search-btn"
                type="button"
            >
                <div class="image__icon-hoverable">
                    <img
                        class="image__icon icon--normal"
                        alt="Buscar"
                        src="<?php echo get_template_directory_uri();?>/images/bsc_header__search-icon--hover.png"
                    >
                    <img
                        class="image__icon icon--hover"
                        alt=""
                        src="<?php echo get_template_directory_uri();?>/images/bsc_header__search-icon--hover.png"
                    >
                </div>
            </button>
            <a
                id="profile-button-mobile"
                href="<?php echo esc_url($mobile_profile_url); ?>"
                aria-label="<?php echo esc_attr($mobile_profile_label); ?>"
                class="header-mobile__icon-btn header-mobile__profile-btn"
            >
                <div class="image__icon-hoverable">
                    <img
                        class="image__icon icon--normal"
                        alt="<?php echo esc_attr($mobile_profile_label); ?>"
                        src="<?php echo esc_url( get_template_directory_uri() );?>/images/bsc_header__profile-icon--hover.png"
                    >
                    <img
                        class="image__icon icon--hover"
                        alt=""
                        src="<?php echo esc_url( get_template_directory_uri() );?>/images/bsc_header__profile-icon--hover.png"
                    >
                </div>
            </a>
        </div>

    </div>
</header>

<!-- Mobile search panel (standalone — not inside any display:none header) -->
<div class="bsc-mobile-search-panel" role="search" aria-label="Buscar productos">
    <input type="text" class="header-search-input" placeholder="Buscar productos…" autocomplete="off">
    <ul class="search-results"></ul>
</div>
