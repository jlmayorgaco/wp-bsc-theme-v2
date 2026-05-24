<?php

class BSC_HeaderNav {
	private string $id    = '';
	private string $class = '';
	private array $menus  = array();

	public function setId( string $id ): void {
		$this->id = $id;
	}

	public function setClass( string $class ): void {
		$this->class = $class;
	}

	public function setMenus( array $menus ): void {
		$this->menus = $menus;
	}

	public function getMenus(): array {
		return $this->menus;
	}

	public function addMenu( BSC_MenuNav $menu ): void {
		$this->menus[] = $menu;
	}

	public function renderNavButtons(): string {
		ob_start();
		echo '<nav id="' . esc_attr( $this->id ) . '" class="' . esc_attr( $this->class ) . '">';

		foreach ($this->menus as $menu) {
			if ($menu instanceof BSC_MenuNav) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BSC_MenuNav renders escaped navigation markup.
				echo $menu->renderButton();
			}
		}

		echo '</nav>';
		return ob_get_clean();
	}

	public function renderNavContent(): string {
		ob_start();
		echo '<nav id="' . esc_attr( $this->id ) . '" class="' . esc_attr( $this->class ) . '">';

		foreach ($this->menus as $menu) {
			if ($menu instanceof BSC_MenuNav) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BSC_MenuNav renders escaped navigation markup.
				echo $menu->renderContent();
			}
		}

		echo '</nav>';
		return ob_get_clean();
	}
}
