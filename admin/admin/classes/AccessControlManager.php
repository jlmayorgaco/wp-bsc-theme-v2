<?php
class AccessControlManager {

    private $roles;
    private $pages;
    private $defaults;
    private $savedConfig;

    public function __construct(array $config) {
        $this->roles = $config['roles'];
        $this->pages = $config['pages'];
        $this->defaults = $config['defaults'];

        // Load saved configuration
        $this->savedConfig = get_option(BSC_ACCESS_CONTROL_OPTION, []);

        // Initialize with defaults if not exists
        foreach ($this->getRoles() as $role) {
            if (!isset($this->savedConfig[$role])) {
                $this->savedConfig[$role] = $this->defaults[$role];
            }
        }

        // Validate configuration
        $this->validateConfiguration();
    }

    public function getRoles(): array {
        return $this->roles;
    }

    public function getPages(): array {
        return $this->pages;
    }

    public function getSavedConfig(): array {
        return $this->savedConfig;
    }

    public function saveAccessControl(array $accessData): void {
        // Validate nonce
        if (!isset($_POST[BSC_ACCESS_SAVE_NONCE]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[BSC_ACCESS_SAVE_NONCE])), BSC_ACCESS_SAVE_NONCE)) {

            wp_die(esc_html__('Solicitud no válida.', 'bsc-2-0'));
        }

        // Process and validate input
        $validAccess = [];
        foreach ($this->getRoles() as $role) {
            $submitted = (array)(isset($_POST['access'][$role]) ? $_POST['access'][$role] : []);

            // Only allow valid page slugs
            $validAccess[$role] = array_values(
                array_filter($submitted, function ($pageSlug) use ($this) {
                    return isset($this->pages[$pageSlug]);
                })
            );
        }

        // Update option with validated data
        update_option(BSC_ACCESS_CONTROL_OPTION, $validAccess);

        // Success message
        echo '<div class="notice notice-success is-dismissible"><p>✓ Control de acceso guardado.</p></div>';
    }

    public function getMandatoryPages(): array {
        return [$MANDATORY_PAGE_SLUG];
    }

    private function validateConfiguration(): void {
        // Validate all pages exist in config
        foreach ($this->getSavedConfig() as $role => &$pages) {
            foreach ($pages as $pageSlug) {
                if (!isset($this->pages[$pageSlug])) {
                    throw new InvalidArgumentException("Invalid page slug {$pageSlug} for role {$role}");
                }
            }
        }
    }

}
