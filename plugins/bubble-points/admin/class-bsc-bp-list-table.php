<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BSC_BP_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'user',
            'plural'   => 'users',
            'ajax'     => false,
        ]);
    }

    /* ----------------------------
     * Columns & Sorting
     * -------------------------- */
    public function get_columns() {
        return [
            'user'     => __('User', 'bsc'),
            'email'    => __('Email', 'bsc'),
            'points'   => __('Points', 'bsc'),
            'last_txn' => __('Last movement', 'bsc'),
            'actions'  => __('Actions', 'bsc'),
        ];
    }

    public function get_sortable_columns() {
        // sort by points or last_txn (date)
        return [
            'points'   => ['points', true],
            'last_txn' => ['last_txn', false],
        ];
    }

    public function no_items() {
        esc_html_e('No users found.', 'bsc');
    }

    /* ----------------------------
     * Data Preparation
     * -------------------------- */
    public function prepare_items() {
        $per_page = $this->get_items_per_page('bsc_bp_users_per_page', 20);
        $paged    = max(1, $this->get_pagenum());
        $search   = isset($_REQUEST['s']) ? trim(wp_unslash($_REQUEST['s'])) : '';

        $orderby  = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'points';
        $order    = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';
        if (!in_array($orderby, ['points','last_txn'], true)) $orderby = 'points';
        if ($order !== 'ASC') $order = 'DESC';

        // IMPORTANT: set headers so WP_List_Table knows what to render
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable, 'user']; // 'user' = primary column

        $data = $this->query_users_with_points($per_page, $paged, $search, $orderby, $order);
        $this->items = $data['rows'];

        $this->set_pagination_args([
            'total_items' => $data['total'],
            'per_page'    => $per_page,
            'total_pages' => max(1, (int)ceil($data['total'] / $per_page)),
        ]);
    }

    /* ----------------------------
     * Column Renderers
     * -------------------------- */
    public function column_user($item) {
        $avatar     = get_avatar($item->ID, 24);
        $history_url = admin_url('admin.php?page=bsc-bubble-points&user_id='.(int)$item->ID);
        return sprintf(
            '%s <a href="%s"><strong>%s</strong></a>',
            $avatar ? $avatar : '',
            esc_url($history_url),
            esc_html($item->display_name)
        );
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'email':
                return esc_html($item->user_email);

            case 'points':
                return number_format_i18n((int)$item->points);

            case 'last_txn':
                if (!$item->last_txn) return '—';
                $fmt = get_option('date_format').' '.get_option('time_format');
                return esc_html(mysql2date($fmt, $item->last_txn));

            case 'actions':
                return $this->column_actions($item);

            default:
                return '';
        }
    }

    private function column_actions($item) {
        $user_id     = (int)$item->ID;
        $history_url = admin_url('admin.php?page=bsc-bubble-points&user_id='.$user_id);

        // One form, two submit buttons (add / reduce)
        $action_url = wp_nonce_url(
            admin_url('admin-post.php?action=bsc_bp_inline_adjust'),
            'bsc_bp_inline_adjust_'.$user_id
        );

        ob_start(); ?>
        <a class="button button-small" href="<?php echo esc_url($history_url); ?>">
            <?php esc_html_e('View history', 'bsc'); ?>
        </a>

        <form method="post" action="<?php echo esc_url($action_url); ?>" class="bsc-bp-inline-form" style="display:inline-flex; gap:6px; margin-left:8px; align-items:center;">
            <input type="hidden" name="user_id" value="<?php echo (int)$user_id; ?>" />
            <label class="screen-reader-text" for="delta-<?php echo (int)$user_id; ?>"><?php esc_html_e('Points delta', 'bsc'); ?></label>
            <input id="delta-<?php echo (int)$user_id; ?>" type="number" name="amount" step="1" min="0" placeholder="e.g. 100" style="width:110px" required />
            <input type="text" name="note" placeholder="<?php esc_attr_e('Note', 'bsc'); ?>" style="width:160px" />
            <button class="button button-small" type="submit" name="op" value="add"><?php esc_html_e('Add', 'bsc'); ?></button>
            <button class="button button-small" type="submit" name="op" value="reduce"><?php esc_html_e('Reduce', 'bsc'); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }


    /* ----------------------------
     * Query (all users; points default to 0)
     * -------------------------- */
    private function query_users_with_points($per_page, $paged, $search, $orderby, $order) {
        global $wpdb;

        $users  = $wpdb->users;
        $umeta  = $wpdb->usermeta;
        $ledger = $wpdb->prefix . 'bsc_points_ledger';

        $params = [];
        $search_where = '';

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $search_where = "WHERE (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $order_by_sql = ($orderby === 'last_txn') ? 'last_txn' : 'points';
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';

        $params[] = (int)$per_page;
        $params[] = (int)(($paged - 1) * $per_page);

        $sql = "
            SELECT
                u.ID,
                u.display_name,
                u.user_email,
                COALESCE(CAST(umeta.meta_value AS SIGNED), 0) AS points,
                MAX(l.created_at) AS last_txn
            FROM {$users} u
            LEFT JOIN {$umeta} umeta
                   ON umeta.user_id = u.ID
                  AND umeta.meta_key = 'bsc_bubble_points'
            LEFT JOIN {$ledger} l
                   ON l.user_id = u.ID
            {$search_where}
            GROUP BY u.ID
            ORDER BY {$order_by_sql} {$order}
            LIMIT %d OFFSET %d
        ";
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        // Total users (respect search)
        $count_params = $params; array_pop($count_params); array_pop($count_params);
        $sql_total = "
            SELECT COUNT(*)
            FROM (
                SELECT u.ID
                FROM {$users} u
                LEFT JOIN {$umeta} umeta
                       ON umeta.user_id = u.ID
                      AND umeta.meta_key = 'bsc_bubble_points'
                {$search_where}
                GROUP BY u.ID
            ) t
        ";
        $total = (int)$wpdb->get_var($wpdb->prepare($sql_total, ...$count_params));

        return ['rows' => $rows, 'total' => $total];
    }

    /* ----------------------------
     * Extra UI (optional)
     * -------------------------- */
    public function extra_tablenav($which) {
        // Room for filters in future
    }
}
