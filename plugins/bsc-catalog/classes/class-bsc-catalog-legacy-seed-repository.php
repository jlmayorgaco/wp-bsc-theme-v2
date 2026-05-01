<?php

class BSC_Catalog_Legacy_Seed_Repository {
    private const CATEGORY_SEED = '/seed/legacy-plugin/bsc_categories_json.json';
    private const PRODUCT_SEED = '/seed/legacy-plugin/bsc_products_json.json';

    public function get_category_seed_path(): string {
        return __DIR__ . '/..' . self::CATEGORY_SEED;
    }

    public function get_product_seed_path(): string {
        return __DIR__ . '/..' . self::PRODUCT_SEED;
    }

    public function load_category_seed(): array {
        return $this->load_json_file($this->get_category_seed_path());
    }

    public function load_product_seed(): array {
        return $this->load_json_file($this->get_product_seed_path());
    }

    private function load_json_file(string $path): array {
        if (!file_exists($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }
}
