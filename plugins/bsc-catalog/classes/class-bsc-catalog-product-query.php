<?php

class BSC_Catalog_Product_Query {
	private BSC_Catalog_Filter_Config $config;

	public function __construct( ?BSC_Catalog_Filter_Config $config = null ) {
		$this->config = $config ?: new BSC_Catalog_Filter_Config();
	}

	public function get_query( BSC_Catalog_Request_Context $context ): WP_Query {
		return new WP_Query( $this->get_query_args( $context ) );
	}

	public function get_query_args( BSC_Catalog_Request_Context $context ): array {
		$base_category     = $context->get_category();
		$filter_categories = array();

		foreach ($this->config->get_group_field_names( $context->get_group() ) as $field_name) {
			foreach ($context->get_selected_values( $field_name ) as $value) {
				$filter_categories[] = $value;
			}
		}

		$args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 48,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'meta_query'             => array(
				array(
					'key'     => '_price',
					'value'   => array(
						$context->has_min_price() ? $context->get_min_price() : $this->config->get_display_min_price(),
						$context->has_max_price() ? $context->get_max_price() : $this->config->get_default_query_max_price(),
					),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			),
		);

		$args = $this->apply_ordering( $args, $context->get_orderby() );

		if (class_exists( 'BSC_Stock' )) {
			$args['meta_query'][] = BSC_Stock::get_available_stock_meta_query();
		}

		$tax_query = array();

		if ($base_category !== '') {
			$tax_query[] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'slug',
				'terms'            => array( $base_category ),
				'operator'         => 'IN',
				'include_children' => true,
			);
		}

		if (!empty( $filter_categories )) {
			$tax_query[] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'slug',
				'terms'            => array_values( array_unique( $filter_categories ) ),
				'operator'         => 'AND',
				'include_children' => true,
			);
		}

		if (!empty( $tax_query )) {
			if (count( $tax_query ) > 1) {
				$tax_query['relation'] = 'AND';
			}

			$args['tax_query'] = $tax_query;
		}

		return $args;
	}

	private function apply_ordering( array $args, string $orderby ): array {
		switch ($orderby) {
			case 'date':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;

			case 'price':
				$args['meta_key'] = '_price';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'ASC';
				break;

			case 'price-desc':
				$args['meta_key'] = '_price';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'popularity':
				$args['meta_key'] = 'total_sales';
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'menu_order':
			default:
				$args['orderby'] = array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				);
				break;
		}

		return $args;
	}
}
