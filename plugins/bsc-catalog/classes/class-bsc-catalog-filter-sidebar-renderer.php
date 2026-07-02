<?php

class BSC_Catalog_Filter_Sidebar_Renderer {
	private BSC_Catalog_Filter_Config $config;

	public function __construct( ?BSC_Catalog_Filter_Config $config = null ) {
		$this->config = $config ?: new BSC_Catalog_Filter_Config();
	}

	public function render( ?BSC_Catalog_Request_Context $context = null ): void {
		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
			: '/';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only catalog filter query.
		$query_params = BSC_Catalog_Request_Context::sanitize_request_array( $_GET );

		$context = $context ?: BSC_Catalog_Request_Context::from_request_uri(
			$request_uri,
			$query_params,
			$this->config
		);

		if (!$this->config->is_valid_group( $context->get_group() )) {
			return;
		}

		echo '<form id="bscFiltersForm" class="bsc__filters">';
		$this->render_hidden_inputs( $context );
		$this->render_sort_group( $context );

		foreach ($this->config->get_group_filters( $context->get_group() ) as $filter) {
			$this->render_group( $filter, $context );
		}

		$this->render_price_group( $context );
		echo '</form>';
	}

	private function render_hidden_inputs( BSC_Catalog_Request_Context $context ): void {
		if ($context->get_group() !== '') {
			printf( '<input hidden type="text" value="%s" name="group">', esc_attr( $context->get_group() ) );
		}

		if ($context->get_subgroup() !== '') {
			printf( '<input hidden type="text" value="%s" name="subgroup">', esc_attr( $context->get_subgroup() ) );
		}

		if ($context->get_category() !== '') {
			printf( '<input hidden type="text" value="%s" name="category">', esc_attr( $context->get_category() ) );
		}
	}

	private function render_group( array $filter, BSC_Catalog_Request_Context $context ): void {
		$parent = get_term_by( 'slug', (string) $filter['slug'], 'product_cat' );
		if (!$parent instanceof WP_Term) {
			return;
		}

		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent->term_id,
				'hide_empty' => false,
			)
		);

		if (empty( $children ) || is_wp_error( $children )) {
			return;
		}

		$field_name      = (string) $filter['name'];
		$selected_values = $context->get_selected_values( $field_name );
		$is_multiple     = !empty( $filter['multiple'] );
		if (!$is_multiple) {
			$selected_values = array_slice( $selected_values, 0, 1 );
		}
		$input_type      = $is_multiple ? 'checkbox' : 'radio';
		$input_name      = $is_multiple ? "{$field_name}[]" : $field_name;
		$modifier        = $is_multiple ? 'checkbox' : 'radio';

		echo '<details class="bsc__filters-group">';
		echo '<summary class="bsc__filters-group-title">' . esc_html( (string) $filter['title'] ) . '</summary>';
		echo '<div class="bsc__filters-options">';

		foreach ($children as $child) {
			if (!$child instanceof WP_Term) {
				continue;
			}

			$checked = in_array( $child->slug, $selected_values, true ) ? 'checked' : '';

			echo '<label class="bsc__filters-option">';
			printf(
				"<input class='bsc__filters-input bsc__filters-input--%s' type='%s' name='%s' value='%s' %s> %s",
				esc_attr( $modifier ),
				esc_attr( $input_type ),
				esc_attr( $input_name ),
				esc_attr( $child->slug ),
				esc_attr( $checked ),
				esc_html( $child->name )
			);
			echo '</label>';
		}

		echo '</div>';
		echo '</details>';
	}

	private function render_price_group( BSC_Catalog_Request_Context $context ): void {
		$display_min = $this->config->get_display_min_price();
		$display_max = $this->config->get_display_max_price();
		$step        = $this->config->get_price_step();

		echo '<div class="bsc__filters-group bsc__filters-group--price">';
		echo '<div class="bsc__filters-price-wrapper">';

		printf(
			"<input class='bsc__filters-range bsc__filters-range--min' type='range' min='%s' max='%s' step='%s' name='min_price' id='min_price' value='%s'>",
			esc_attr( (string) $display_min ),
			esc_attr( (string) $display_max ),
			esc_attr( (string) $step ),
			esc_attr( (string) $context->get_min_price() )
		);

		printf(
			"<input class='bsc__filters-range bsc__filters-range--max' type='range' min='%s' max='%s' step='%s' name='max_price' id='max_price' value='%s'>",
			esc_attr( (string) $display_min ),
			esc_attr( (string) $display_max ),
			esc_attr( (string) $step ),
			esc_attr( (string) $context->get_max_price() )
		);

		echo '<div class="bsc__filters-price-values">';
		echo '<label class="bsc__filters-price-label"><output class="bsc__filters-price-output" id="min_price_output">' . esc_html( (string) $context->get_min_price() ) . '</output>$</label>';
		echo '<label class="bsc__filters-price-label"><output class="bsc__filters-price-output" id="max_price_output">' . esc_html( (string) $context->get_max_price() ) . '</output>$</label>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	private function render_sort_group( BSC_Catalog_Request_Context $context ): void {
		echo '<div class="bsc__filters-group bsc__filters-group--sort">';
		echo '<label class="bsc__filters-sort-label" for="bsc_catalog_orderby">Ordenar por</label>';
		echo '<select class="bsc__filters-sort-select" id="bsc_catalog_orderby" name="orderby">';

		foreach ($this->config->get_orderby_options() as $value => $label) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $context->get_orderby(), $value, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
		echo '</div>';
	}
}
