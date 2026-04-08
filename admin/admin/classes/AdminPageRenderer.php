<?php
class AdminPageRenderer {

    private $accessManager;

    public function __construct(AccessControlManager $accessManager) {
        $this->accessManager = $accessManager;
    }

    public function render(): void {
        // Get all pages and roles for the table structure
        $pages = $this->accessManager->getPages();
        $roles = $this->accessManager->getRoles();

        // Get saved configuration
        $savedConfig = $this->accessManager->getSavedConfig();

        // Generate HTML form
        echo $this->generateForm($pages, $roles, $savedConfig);
    }

    private function generateForm(array $pages, array $roles, array $savedConfig): string {
        // Start the form and table structure
        $html = '<div class="wrap bsc-admin-access">';
        $html .= '<h1>Control de Acceso BSC</h1>';
        $html .= '<p style="color:#555;max-width:600px">' .
                esc_html__('Define qué páginas del panel BSC puede ver cada rol operativo.', 'bsc-2-0') .
                '<strong>' . esc_html__('Administradores', 'bsc-2-0') .
                '</strong> y <strong>' . esc_html__('Shop Managers', 'bsc-2-0') .
                '</strong> siempre tienen acceso completo.</p>';

        $html .= '<form method="post">' .
                 wp_nonce_field(BSC_ACCESS_SAVE_NONCE, BSC_ACCESS_SAVE_NONCE) .
                 '<table class="bsc-access-table wp-list-table widefat fixed">' .
                 $this->generateTableHeader($roles) .
                 $this->generateTableBody($pages, $roles, $savedConfig) .
                 '</table>' .
                 $this->generateSubmitButton() .
                 '</form>';

        // Add CSS
        $html .= '<style>';
        $html .= '.bsc-access-table td, .bsc-access-table th { vertical-align:middle; padding:10px 12px; }';
        $html .= '.bsc-access-table input[type=checkbox] { width:18px; height:18px; cursor:pointer; }';
        $html .= '.bsc-access-table input[disabled] { opacity:.5; cursor:not-allowed; }';
        $html .= '.bsc-access-table tbody tr:hover { background:#f9f9f9; }';
        $html .= '</style>';

        return $html;
    }

    private function generateTableHeader(array $roles): string {
        $html = '<thead><tr>';
        $html .= '<th style="width:200px">' . esc_html__('Página', 'bsc-2-0') . '</th>';

        foreach ($roles as $role => $label) {
            $html .= '<th style="text-align:center;width:150px">' .
                     esc_html($label) .
                     '</th>';
        }

        $html .= '</tr></thead>';
        return $html;
    }

    private function generateTableBody(array $pages, array $roles, array $savedConfig): string {
        $html = '<tbody>';

        foreach ($pages as $pageSlug => $pageLabel) {
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html($pageLabel) .
                     '</strong><br><small style="color:#888;font-family:monospace">' .
                     esc_html($pageSlug) .
                     '</small></td>';

            foreach ($roles as $role => $label) {
                $isChecked = in_array($pageSlug, $savedConfig[$role] ?? [], true);
                $isMandatory = $pageSlug === $this->accessManager->getMandatoryPages()[0];

                $html .= '<td style="text-align:center">';
                $html .= '<input type="checkbox" name="access[' . esc_attr($role) .
                         '][]" value="' . esc_attr($pageSlug) .
                         '" ';

                if ($isChecked || $isMandatory) {
                    $html .= 'checked';
                }

                if ($isMandatory) {
                    $html .= ' disabled';
                }

                $html .= '>';

                if ($isMandatory) {
                    $html .= '<input type="hidden" name="access[' . esc_attr($role) .
                             '][]" value="' . esc_attr($pageSlug) . '">';
                }

                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';

        // Add mandatory note
        $mandatoryNote = '<div style="margin-top:16px;padding:12px 16px;background:#fffbeb;border:1px solid #f6ad55;border-radius:6px;max-width:700px">';
        $mandatoryNote .= '<strong>' . esc_html__('Nota:', 'bsc-2-0') .
                         '</strong> "' . esc_html($this->accessManager->getMandatoryPages()[0]) .
                         '" está marcado como obligatorio para todos los roles operativos y no se puede desmarcar.';
        $mandatoryNote .= '</div>';

        return $html . $mandatoryNote;
    }

    private function generateSubmitButton(): string {
        return '<div style="margin-top:16px;padding:12px 16px;background:#fffbeb;border:1px solid #f6ad55;border-radius:6px;max-width:700px">';
        $html = '<strong>' . esc_html__('Nota:', 'bsc-2-0') .
                '</strong> Los cambios se aplican inmediatamente al guardar.';
        $html .= '</div>';

        return $html . '<br><input type="submit" value="' .
                     esc_attr(__('Guardar control de acceso', 'bsc-2-0')) .
                     '" class="button-primary" style="margin-top:16px">';
    }
}
