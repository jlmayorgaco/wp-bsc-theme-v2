<?php
/**
 * Lightweight CRM admin panel for BSC customers.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_CRM {
	private const NOTES_OPTION = 'bsc_growth_customer_notes';

	public static function register_hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ), 30 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_note_save' ) );
	}

	public static function register_admin_menu(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		add_submenu_page(
			'bsc-dashboard',
			'Clientes CRM',
			'Clientes CRM',
			'manage_woocommerce',
			'bsc-crm',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'bsc-crm' !== $page ) {
			return;
		}

		if ( function_exists( 'bsc_enqueue_admin_ui_assets' ) ) {
			bsc_enqueue_admin_ui_assets();
		}

		wp_enqueue_style(
			'bsc-growth-admin',
			get_template_directory_uri() . '/admin/bsc-growth.css',
			array( 'bsc-admin-ui' ),
			BSC_Growth_Plugin::admin_css_version()
		);
	}

	public static function handle_note_save(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing parameter; POST is verified below.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'bsc-crm' !== $page || ! isset( $_POST['bsc_growth_crm_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
		}

		check_admin_referer( 'bsc_growth_crm_save', 'bsc_growth_crm_nonce' );

		$customer_key = isset( $_POST['customer_key'] ) ? sanitize_key( wp_unslash( $_POST['customer_key'] ) ) : '';
		$note         = isset( $_POST['customer_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_note'] ) ) : '';
		$notes        = self::get_notes();

		if ( '' !== $customer_key ) {
			if ( '' === $note ) {
				unset( $notes[ $customer_key ] );
			} else {
				$notes[ $customer_key ] = array(
					'note'       => $note,
					'updated_at' => current_time( 'mysql' ),
					'updated_by' => get_current_user_id(),
				);
			}

			update_option( self::NOTES_OPTION, $notes, false );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'bsc-crm',
					'customer' => $customer_key,
					'saved'    => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin filters.
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$customer_key = isset( $_GET['customer'] ) ? sanitize_key( wp_unslash( $_GET['customer'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$customers    = self::get_customer_rows( $search );
		$selected     = self::find_customer_row( $customer_key, $customers );

		?>
		<div class="wrap bsc-growth-admin">
			<h1>Clientes CRM</h1>
			<p class="bsc-growth-admin__intro">Vista operativa por cliente: pedidos, puntos, perfil de piel, carritos abandonados, recompra y notas internas.</p>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success notice. ?>
			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Nota guardada.</p></div>
			<?php endif; ?>

			<form class="bsc-growth-admin__toolbar" method="get">
				<input type="hidden" name="page" value="bsc-crm">
				<label>
					<span class="screen-reader-text">Buscar cliente</span>
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Nombre o email">
				</label>
				<button class="button button-primary" type="submit">Buscar</button>
			</form>

			<div class="bsc-growth-admin__layout">
				<section class="bsc-growth-admin__card">
					<h2>Clientes recientes</h2>
					<table class="widefat striped bsc-growth-admin__table">
						<thead>
							<tr>
								<th>Cliente</th>
								<th>Pedidos</th>
								<th>Total</th>
								<th>Puntos</th>
								<th>Perfil</th>
								<th>Carritos</th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $customers ) ) : ?>
								<tr><td colspan="6">No hay clientes para este filtro.</td></tr>
							<?php endif; ?>
							<?php foreach ( $customers as $customer ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'bsc-crm', 'customer' => $customer['key'], 's' => $search ), admin_url( 'admin.php' ) ) ); ?>">
											<strong><?php echo esc_html( $customer['name'] ); ?></strong>
										</a>
										<br>
										<span class="bsc-growth-admin__muted"><?php echo esc_html( $customer['email'] ); ?></span>
									</td>
									<td><?php echo esc_html( (string) $customer['order_count'] ); ?></td>
									<td><?php echo wp_kses_post( BSC_Growth_Plugin::price_html( (float) $customer['spent'] ) ); ?></td>
									<td><?php echo esc_html( (string) $customer['points'] ); ?></td>
									<td><?php echo esc_html( $customer['profile_label'] ); ?></td>
									<td><?php echo esc_html( (string) $customer['abandoned_count'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>

				<?php self::render_customer_detail( $selected ); ?>
			</div>
		</div>
		<?php
	}

	private static function render_customer_detail( ?array $customer ): void {
		if ( ! $customer ) {
			?>
			<aside class="bsc-growth-admin__card bsc-growth-admin__card--muted">
				<h2>Detalle</h2>
				<p>Selecciona un cliente para ver notas internas, recompra y datos de perfil.</p>
			</aside>
			<?php
			return;
		}

		$notes            = self::get_notes();
		$current_note     = (string) ( $notes[ $customer['key'] ]['note'] ?? '' );
		$repurchase_items = BSC_Growth_Repurchase::get_customer_items( (int) $customer['user_id'], $customer['email'], 4 );
		?>
		<aside class="bsc-growth-admin__card bsc-growth-admin__detail">
			<h2><?php echo esc_html( $customer['name'] ); ?></h2>
			<p class="bsc-growth-admin__muted"><?php echo esc_html( $customer['email'] ); ?></p>

			<div class="bsc-growth-admin__summary-grid">
				<div><strong><?php echo esc_html( (string) $customer['order_count'] ); ?></strong><span>Pedidos</span></div>
				<div><strong><?php echo wp_kses_post( BSC_Growth_Plugin::price_html( (float) $customer['spent'] ) ); ?></strong><span>Total</span></div>
				<div><strong><?php echo esc_html( (string) $customer['points'] ); ?></strong><span>Puntos</span></div>
				<div><strong><?php echo esc_html( (string) $customer['abandoned_count'] ); ?></strong><span>Carritos</span></div>
			</div>

			<h3>Perfil de piel</h3>
			<ul class="bsc-growth-admin__plain-list">
				<li><strong>Tipo:</strong> <?php echo esc_html( $customer['skin_type'] ? $customer['skin_type'] : 'Sin dato' ); ?></li>
				<li><strong>Sensibilidad:</strong> <?php echo esc_html( $customer['sensitivity'] ? $customer['sensitivity'] : 'Sin dato' ); ?></li>
				<li><strong>Necesidades:</strong> <?php echo esc_html( $customer['needs_label'] ? $customer['needs_label'] : 'Sin dato' ); ?></li>
			</ul>

			<h3>Recompra</h3>
			<?php if ( empty( $repurchase_items ) ) : ?>
				<p class="bsc-growth-admin__muted">Sin productos cerca de recompra.</p>
			<?php else : ?>
				<ul class="bsc-growth-admin__plain-list">
					<?php foreach ( $repurchase_items as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['name'] ); ?></a>
							<span class="bsc-growth-admin__muted"><?php echo esc_html( $item['message'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<form method="post" class="bsc-growth-admin__notes-form">
				<?php wp_nonce_field( 'bsc_growth_crm_save', 'bsc_growth_crm_nonce' ); ?>
				<input type="hidden" name="customer_key" value="<?php echo esc_attr( $customer['key'] ); ?>">
				<label for="bsc_customer_note"><strong>Notas internas</strong></label>
				<textarea id="bsc_customer_note" name="customer_note" rows="5"><?php echo esc_textarea( $current_note ); ?></textarea>
				<button type="submit" class="button button-primary">Guardar nota</button>
			</form>
		</aside>
		<?php
	}

	private static function get_customer_rows( string $search ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$rows   = array();
		$orders = BSC_Growth_Plugin::orders(
			array(
				'limit'   => 60,
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => array_keys( BSC_Growth_Plugin::order_statuses() ),
			)
		);

		foreach ( $orders as $order ) {
			$user_id = (int) $order->get_customer_id();
			$email   = sanitize_email( $order->get_billing_email() );

			if ( '' === $email && $user_id <= 0 ) {
				continue;
			}

			$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			$name = '' !== $name ? $name : $email;

			self::append_customer_row( $rows, $user_id, $email, $name );
		}

		if ( '' !== $search ) {
			$users = get_users(
				array(
					'number'         => 20,
					'search'         => '*' . esc_attr( $search ) . '*',
					'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
				)
			);

			foreach ( $users as $user ) {
				self::append_customer_row( $rows, (int) $user->ID, (string) $user->user_email, (string) $user->display_name );
			}
		}

		$rows = array_values( $rows );

		if ( '' !== $search ) {
			$needle = strtolower( remove_accents( $search ) );
			$rows   = array_values(
				array_filter(
					$rows,
					static fn( array $row ): bool => str_contains( strtolower( remove_accents( $row['name'] . ' ' . $row['email'] ) ), $needle )
				)
			);
		}

		usort(
			$rows,
			static fn( array $a, array $b ): int => ( $b['last_order_ts'] <=> $a['last_order_ts'] ) ?: strnatcasecmp( $a['name'], $b['name'] )
		);

		return array_slice( $rows, 0, 25 );
	}

	private static function append_customer_row( array &$rows, int $user_id, string $email, string $name ): void {
		$key = self::customer_key( $user_id, $email );

		if ( isset( $rows[ $key ] ) ) {
			return;
		}

		$summary       = self::customer_order_summary( $user_id, $email );
		$profile       = self::customer_profile( $user_id );
		$abandoned     = self::abandoned_count( $email );
		$profile_parts = array_filter( array( $profile['skin_type'], $profile['sensitivity'] ) );

		$rows[ $key ] = array_merge(
			array(
				'key'             => $key,
				'user_id'         => $user_id,
				'email'           => $email,
				'name'            => '' !== $name ? $name : $email,
				'points'          => $user_id > 0 && function_exists( 'bsc_bp_get_balance' ) ? (int) bsc_bp_get_balance( $user_id ) : 0,
				'abandoned_count' => $abandoned,
				'profile_label'   => ! empty( $profile_parts ) ? implode( ' / ', $profile_parts ) : 'Sin perfil',
			),
			$summary,
			$profile
		);
	}

	private static function customer_order_summary( int $user_id, string $email ): array {
		$args = array(
			'limit'   => 50,
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => array( 'completed', 'processing', 'shipped' ),
		);

		if ( $user_id > 0 ) {
			$args['customer_id'] = $user_id;
		} elseif ( is_email( $email ) ) {
			$args['billing_email'] = $email;
		}

		$orders        = BSC_Growth_Plugin::orders( $args );
		$spent         = 0.0;
		$last_order_ts = 0;

		foreach ( $orders as $order ) {
			$spent += (float) $order->get_total();
			$date   = $order->get_date_created();

			if ( $date ) {
				$last_order_ts = max( $last_order_ts, $date->getTimestamp() );
			}
		}

		return array(
			'order_count'   => count( $orders ),
			'spent'         => $spent,
			'last_order_ts' => $last_order_ts,
		);
	}

	private static function customer_profile( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array(
				'skin_type'   => '',
				'sensitivity' => '',
				'needs_label' => '',
			);
		}

		$needs = array();
		for ( $index = 1; $index <= 4; $index++ ) {
			$need = (string) get_user_meta( $user_id, 'bsc_needs' . $index, true );

			if ( '' !== $need ) {
				$needs[] = $need;
			}
		}

		return array(
			'skin_type'   => (string) get_user_meta( $user_id, 'bsc_skin_type', true ),
			'sensitivity' => (string) get_user_meta( $user_id, 'bsc_sensitivity', true ),
			'needs_label' => implode( ', ', array_unique( $needs ) ),
		);
	}

	private static function abandoned_count( string $email ): int {
		if ( ! function_exists( 'bsc_abandoned_cart_get_rows' ) || ! is_email( $email ) ) {
			return 0;
		}

		return count(
			array_filter(
				bsc_abandoned_cart_get_rows(),
				static fn( array $row ): bool => strtolower( (string) ( $row['email'] ?? '' ) ) === strtolower( $email ) && empty( $row['converted_at'] )
			)
		);
	}

	private static function find_customer_row( string $customer_key, array $rows ): ?array {
		foreach ( $rows as $row ) {
			if ( $row['key'] === $customer_key ) {
				return $row;
			}
		}

		return null;
	}

	private static function customer_key( int $user_id, string $email ): string {
		if ( $user_id > 0 ) {
			return 'user-' . $user_id;
		}

		return 'email-' . md5( strtolower( $email ) );
	}

	private static function get_notes(): array {
		$notes = get_option( self::NOTES_OPTION, array() );

		return is_array( $notes ) ? $notes : array();
	}
}
