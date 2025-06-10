<?php

class BSC_MenuNav {
	private string $name = '';
	private string $slug = '';
	private string $image = '';
	private string $link = '';
	private array $menus = [];

	public function setName(string $name): void {
		$this->name = $name;
	}
	public function getName(): string {
		return $this->name;
	}
	public function setImage(string $image): void {
		$this->image = $image;
	}
	public function setLink(string $link): void {
		$this->link = $link;
	}
	public function getLink(): string {
		return $this->link;
	}
	public function setSlug(string $slug): void {
		$this->slug = $slug;
	}

	public function setCover(array $payload): void {
		$this->image =  $payload['image'];
		$this->link = $payload['link'];
	}

	public function setMenus(array $menus): void {
		$this->menus = $menus;
	}
	public function getMenus(): array {
		return $this->menus;
	}

	public function appendMenu(array $menu): void {
		if (
			!isset($menu['slug'], $menu['title'], $menu['items']) ||
			!is_array($menu['items'])
		) {
			throw new InvalidArgumentException('Invalid menu format.');
		}
		$this->menus[] = $menu;
	}

    public function renderButton(): string {

		if(count($this->menus) == 0){
			ob_start();

			echo '<a class="bsc__menu-nav bsc__menu-nav--button" href="'. esc_html($this->link) .'">';
			echo '<span class="menu-nav__name">' . esc_html($this->name) . '</span>';
			echo '</a>';

			return ob_get_clean();
		}

        ob_start();

        echo '<button class="bsc__menu-nav bsc__menu-nav--button"'.' data-target="'.$this->slug.'"'.'>';
        echo '<span class="menu-nav__name">' . esc_html($this->name) . '</span>';
        echo '<span class="menu-nav__icon">';
        echo '<i aria-hidden="true" class="fas fa-angle-down icon-down"></i>';
        echo '<i aria-hidden="true" class="fas fa-angle-up icon-up"></i>';
        echo '</span>';
        echo '</button>';

        return ob_get_clean();
    }

    public function renderContent(): string {
		ob_start();

		echo '<div class="bsc__menu-nav bsc__menu-nav--content" id="' . esc_attr($this->slug) . '">';
		echo '<div class="menu-nav__container">';

		foreach ($this->menus as $menu) {
			echo '<div class="bsc__custom-menu-section" id="' . esc_attr($menu['slug']) . '">';
			echo '<h3 class="bsc__custom-menu-title">' . esc_html($menu['title']) . '</h3>';
			echo '<ul class="bsc__custom-menu">';
			foreach ($menu['items'] as $item) {
				if (!empty($item['title']) && !empty($item['link'])) {
					echo '<li><a href="' . esc_url($item['link']) . '">' . esc_html($item['title']) . '</a></li>';
				}
			}
			echo '</ul></div>';
		}

		if ($this->image) {
			echo '<a class="bsc__menu-nav-image" href="'. esc_attr($this->link) .'">';
			echo '<img src="' . esc_url($this->image) . '" alt="' . esc_attr($this->name) . '">';
			echo '</a>';
		}

		echo '</div>';
		echo '</div>';

		return ob_get_clean();
	}
}
