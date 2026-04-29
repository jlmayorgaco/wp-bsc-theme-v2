<?php

class BSC_MY_ACCOUNT_HEADER {
  private string $current_route = '';
  private array $menu_links = [];

  public function __construct() {
    $this->menu_links = [
      [
        'id' => 'orders',
        'text' => '¡ Mis pedidos !',
        'href' => wc_get_account_endpoint_url('orders'),
      ],
      [
        'id' => 'bubble-points',
        'text' => '¡ Mis puntos !',
        'href' => home_url('/mi-cuenta/bubble-points/'),
      ],
      [
        'id' => 'edit-address',
        'text' => '¡ Envío y dirección !',
        'href' => wc_get_account_endpoint_url('edit-address'),
      ],
      [
        'id' => 'edit-account',
        'text' => '¡ Mis datos !',
        'href' => wc_get_account_endpoint_url('edit-account'),
      ],
    ];
  }

  public function setCurrentRoute(string $route): void {
    $this->current_route = $route;
  }

  public function render(): void {
    $user = wp_get_current_user();
    $name = $user->display_name ?: ($user->first_name . ' ' . $user->last_name);
    ?>
    <div class="bsc__my-account-header">
      <div class="header__logo">
        <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_profile_logo.png" alt="Perfil BSC">
      </div>

      <div class="header__name bsc__title bsc__title--centered"><?php echo esc_html($name); ?></div>

      <div class="header__menu">
        <ul class="menu__items">
          <?php foreach ($this->menu_links as $link): ?>
            <?php
              $is_active = strpos($_SERVER['REQUEST_URI'], $link['id']) !== false;
              $classes = 'menu__item' . ($is_active ? ' is-active' : '');
            ?>
            <li class="<?php echo esc_attr($classes); ?>">
              <a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url($link['href']); ?>">
                <span><?php echo esc_html($link['text']); ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php
  }
}