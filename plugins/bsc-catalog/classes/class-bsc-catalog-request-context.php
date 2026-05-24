<?php

class BSC_Catalog_Request_Context {
	private string $group;
	private string $subgroup;
	private string $category;
	private array $params;
	private int $min_price;
	private int $max_price;
	private bool $has_min_price;
	private bool $has_max_price;
	private string $orderby;

	private function __construct(
		string $group,
		string $subgroup,
		string $category,
		array $params,
		int $min_price,
		int $max_price,
		bool $has_min_price,
		bool $has_max_price,
		string $orderby
	) {
		$this->group         = $group;
		$this->subgroup      = $subgroup;
		$this->category      = $category;
		$this->params        = $params;
		$this->min_price     = $min_price;
		$this->max_price     = $max_price;
		$this->has_min_price = $has_min_price;
		$this->has_max_price = $has_max_price;
		$this->orderby       = $orderby;
	}

	public static function from_request( array $request, ?BSC_Catalog_Filter_Config $config = null ): self {
		$config  = $config ?: new BSC_Catalog_Filter_Config();
		$request = self::sanitize_request_array( $request );

		return new self(
			self::sanitize_scalar( $request['group'] ?? '' ),
			self::sanitize_scalar( $request['subgroup'] ?? '' ),
			self::sanitize_scalar( $request['category'] ?? '' ),
			$request,
			array_key_exists( 'min_price', $request ) ? absint( $request['min_price'] ) : $config->get_display_min_price(),
			array_key_exists( 'max_price', $request ) ? absint( $request['max_price'] ) : $config->get_display_max_price(),
			array_key_exists( 'min_price', $request ),
			array_key_exists( 'max_price', $request ),
			$config->normalize_orderby( $request['orderby'] ?? '' )
		);
	}

	public static function from_request_uri( string $request_uri, array $query_params = array(), ?BSC_Catalog_Filter_Config $config = null ): self {
		$config       = $config ?: new BSC_Catalog_Filter_Config();
		$query_params = self::sanitize_request_array( $query_params );

		$path           = trim( (string) parse_url( $request_uri, PHP_URL_PATH ), '/' );
		$segments       = $path === '' ? array() : explode( '/', $path );
		$category_index = array_search( 'product-category', $segments, true );

		$group    = '';
		$subgroup = '';
		$category = '';

		if ($category_index !== false) {
			$group    = self::sanitize_scalar( $segments[ $category_index + 1 ] ?? '' );
			$subgroup = self::sanitize_scalar( $segments[ $category_index + 2 ] ?? '' );
			$category = self::sanitize_scalar( $segments[ $category_index + 3 ] ?? '' );
		}

		return new self(
			$group,
			$subgroup,
			$category,
			$query_params,
			array_key_exists( 'min_price', $query_params ) ? absint( $query_params['min_price'] ) : $config->get_display_min_price(),
			array_key_exists( 'max_price', $query_params ) ? absint( $query_params['max_price'] ) : $config->get_display_max_price(),
			array_key_exists( 'min_price', $query_params ),
			array_key_exists( 'max_price', $query_params ),
			$config->normalize_orderby( $query_params['orderby'] ?? '' )
		);
	}

	public static function sanitize_request_array( array $request ): array {
		$unslashed = wp_unslash( $request );

		if (!is_array( $unslashed )) {
			return array();
		}

		return map_deep( $unslashed, 'sanitize_text_field' );
	}

	public function get_group(): string {
		return $this->group;
	}

	public function get_subgroup(): string {
		return $this->subgroup;
	}

	public function get_category(): string {
		return $this->category;
	}

	public function get_min_price(): int {
		return $this->min_price;
	}

	public function get_max_price(): int {
		return $this->max_price;
	}

	public function has_min_price(): bool {
		return $this->has_min_price;
	}

	public function has_max_price(): bool {
		return $this->has_max_price;
	}

	public function get_orderby(): string {
		return $this->orderby;
	}

	public function get_selected_values( string $field_name ): array {
		if (!array_key_exists( $field_name, $this->params )) {
			return array();
		}

		$raw_value = $this->params[ $field_name ];
		$values    = is_array( $raw_value ) ? $raw_value : array( $raw_value );
		$selected  = array();

		foreach ($values as $value) {
			$value = self::sanitize_scalar( $value );
			if ($value !== '') {
				$selected[] = $value;
			}
		}

		return array_values( array_unique( $selected ) );
	}

	private static function sanitize_scalar( $value ): string {
		return sanitize_text_field( (string) $value );
	}
}
