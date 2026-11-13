<?php
if (!defined( 'ABSPATH' )) {
	exit;
}

if (!class_exists( 'WP_List_Table' )) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BSC_BP_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'user',
				'plural'   => 'users',
				'ajax'     => false,
			)
		);
	}

	/*
	----------------------------
	 * Columns & Sorting
	 * -------------------------- */
	public function get_columns() {
		return array(
			'user'     => 'Cliente',
			'email'    => 'Correo',
			'points'   => 'Saldo',
			'last_txn' => 'Último movimiento',
			'actions'  => 'Acciones',
		);
	}

	public function get_sortable_columns() {
		// sort by points or last_txn (date)
		return array(
			'points'   => array( 'points', true ),
			'last_txn' => array( 'last_txn', false ),
		);
	}

	public function no_items() {
		echo 'No se encontraron clientes.';
	}

	/*
	----------------------------
	 * Data Preparation
	 * -------------------------- */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'bsc_bp_users_per_page', 20 );
		$paged    = max( 1, $this->get_pagenum() );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List-table search is read-only.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List-table sorting is read-only.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'points';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- List-table sorting is read-only.
		$order = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
		if (!in_array( $orderby, array( 'points', 'last_txn' ), true )) {
			$orderby = 'points';
		}
		if ($order !== 'ASC') {
			$order = 'DESC';
		}

		// IMPORTANT: set headers so WP_List_Table knows what to render
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable, 'user' ); // 'user' = primary column

		$data        = $this->query_users_with_points( $per_page, $paged, $search, $orderby, $order );
		$this->items = $data['rows'];

		$this->set_pagination_args(
			array(
				'total_items' => $data['total'],
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $data['total'] / $per_page ) ),
			)
		);
	}

	/*
	----------------------------
	 * Column Renderers
	 * -------------------------- */
	public function column_user( $item ) {
		$avatar      = get_avatar( $item->ID, 24 );
		$history_url = admin_url( 'admin.php?page=bsc-bubble-points&user_id=' . (int) $item->ID );
		return sprintf(
			'%s <a href="%s"><strong>%s</strong></a>',
			$avatar ? $avatar : '',
			esc_url( $history_url ),
			esc_html( $item->display_name )
		);
	}

	public function column_default( $item, $column_name ) {
		switch ($column_name) {
			case 'email':
				return esc_html( $item->user_email );

			case 'points':
				return '<strong class="bsc-bp-balance">' . esc_html( number_format_i18n( (int) $item->points ) ) . '</strong>';

			case 'last_txn':
				if (!$item->last_txn) {
					return '—';
				}
				$fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				return esc_html( mysql2date( $fmt, $item->last_txn ) );

			case 'actions':
				return $this->column_actions( $item );

			default:
				return '';
		}
	}

	public function column_actions( $item ) {
		$user_id     = (int) $item->ID;
		$history_url = admin_url( 'admin.php?page=bsc-bubble-points&user_id=' . $user_id );

		ob_start(); ?>
		<a href="<?php echo esc_url( $history_url ); ?>">Ver historial</a>
		<details class="bsc-bp-quick-adjust">
			<summary>Ajustar puntos<span class="screen-reader-text"> de <?php echo esc_html( $item->display_name ); ?></span></summary>
			<?php bsc_bp_render_adjust_form( $user_id, $item->display_name, (int) $item->points, true ); ?>
		</details>
		<?php
		return ob_get_clean();
	}


	/*
	----------------------------
	 * Query (all users; points default to 0)
	 * -------------------------- */
	private function query_users_with_points( $per_page, $paged, $search, $orderby, $order ) {
		global $wpdb;

		$users  = $wpdb->users;
		$umeta  = $wpdb->usermeta;
		$ledger = $wpdb->prefix . 'bsc_points_ledger';

		$params       = array();
		$search_where = '';

		if ($search !== '') {
			$like         = '%' . $wpdb->esc_like( $search ) . '%';
			$search_where = 'WHERE (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)';
			$params[]     = $like;
			$params[]     = $like;
			$params[]     = $like;
		}

		$order_by_sql = ( $orderby === 'last_txn' ) ? 'last_txn' : 'points';
		$order        = ( $order === 'ASC' ) ? 'ASC' : 'DESC';

		$params[] = (int) $per_page;
		$params[] = (int) ( ( $paged - 1 ) * $per_page );

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
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin list table query uses allowlisted ORDER BY and prepared values.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );

		// Total users (respect search)
		$count_params = $params;
		array_pop( $count_params );
		array_pop( $count_params );
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
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin list table count query uses prepared search values.
		$total = (int) $wpdb->get_var( $wpdb->prepare( $sql_total, ...$count_params ) );

		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}

	/*
	----------------------------
	 * Extra UI (optional)
	 * -------------------------- */
	public function extra_tablenav( $which ) {
		// Room for filters in future
	}
}
