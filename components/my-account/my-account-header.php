<?php

class BSC_MY_ACCOUNT_HEADER {
  private string $current_route = '';
  private array $menu_links = [];

  public function __construct() {
    $this->menu_links = [
      [
        'id' => 'orders',
        'text' => html_entity_decode('&#161; Mis pedidos !', ENT_QUOTES, 'UTF-8'),
        'href' => wc_get_account_endpoint_url('orders'),
      ],
      [
        'id' => 'bubble-points',
        'text' => html_entity_decode('&#161; Mis puntos !', ENT_QUOTES, 'UTF-8'),
        'href' => home_url('/mi-cuenta/bubble-points/'),
      ],
      [
        'id' => 'edit-address',
        'text' => html_entity_decode('&#161; Env&iacute;o y direcci&oacute;n !', ENT_QUOTES, 'UTF-8'),
        'href' => wc_get_account_endpoint_url('edit-address'),
      ],
      [
        'id' => 'edit-account',
        'text' => html_entity_decode('&#161; Mis datos !', ENT_QUOTES, 'UTF-8'),
        'href' => wc_get_account_endpoint_url('edit-account'),
      ],
    ];
  }

  public function setCurrentRoute(string $route): void {
    $this->current_route = $route;
  }

  private function isActive(string $routeId): bool {
    return $this->current_route === $routeId;
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
              $is_active = $this->isActive($link['id']);
              $item_classes = 'menu__item' . ($is_active ? ' is-active' : '');
              $link_classes = 'menu__link' . ($is_active ? ' is-active' : '');
            ?>
            <li class="<?php echo esc_attr($item_classes); ?>">
              <a class="<?php echo esc_attr($link_classes); ?>" href="<?php echo esc_url($link['href']); ?>">
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