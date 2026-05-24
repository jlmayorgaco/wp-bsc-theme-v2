<?php

class BSC_Catalog_Filter_Config {
    private array $config;

    public function __construct() {
        $config_path = __DIR__ . '/../config/filter-groups.php';
        $config = file_exists($config_path) ? require $config_path : [];
        $this->config = is_array($config) ? $config : [];
    }

    public function get_groups(): array {
        return $this->config['groups'] ?? [];
    }

    public function get_group_filters(string $group_slug): array {
        return $this->get_groups()[$group_slug] ?? [];
    }

    public function get_group_field_names(string $group_slug): array {
        $fields = [];

        foreach ($this->get_group_filters($group_slug) as $filter) {
            if (!empty($filter['name'])) {
                $fields[] = (string) $filter['name'];
            }
        }

        return $fields;
    }

    public function is_valid_group(string $group_slug): bool {
        return isset($this->get_groups()[$group_slug]);
    }

    public function get_display_min_price(): int {
        return (int) ($this->config['price']['display_min'] ?? 0);
    }

    public function get_display_max_price(): int {
        return (int) ($this->config['price']['display_max'] ?? 200000);
    }

    public function get_price_step(): int {
        return (int) ($this->config['price']['step'] ?? 1000);
    }

    public function get_default_query_max_price(): int {
        return (int) ($this->config['price']['query_max'] ?? 999999);
    }

    public function get_orderby_options(): array {
        return [
            'menu_order' => 'Recomendados',
            'date'       => 'Mas recientes',
            'price'      => 'Precio menor a mayor',
            'price-desc' => 'Precio mayor a menor',
            'popularity' => 'Mas vendidos',
        ];
    }

    public function normalize_orderby($orderby): string {
        $orderby = sanitize_key((string) $orderby);
        return array_key_exists($orderby, $this->get_orderby_options()) ? $orderby : 'menu_order';
    }
}
