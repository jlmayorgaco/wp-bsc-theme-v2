<?php

class BSC_Order_Progress_Bar {
	public const PENDING          = 'pending';
	public const ON_HOLD          = 'on-hold';
	public const RECEIVED         = 'received';
	public const SHIPPED          = 'shipped';
	public const DONE             = 'done';
	public const CANCELLED        = 'cancelled';
	public const REFUNDED         = 'refunded';
	public const ARCHIVED         = 'archived';
	public const DISPLAY_STANDARD = 'standard';
	public const DISPLAY_COMPACT  = 'compact';

	private string $status       = self::CANCELLED;
	private string $display_mode = self::DISPLAY_STANDARD;

	public function __construct( string $initial_status = self::CANCELLED, string $display_mode = self::DISPLAY_STANDARD ) {
		$this->setStatus( $initial_status );
		$this->setDisplayMode( $display_mode );
	}

	public function setStatus( string $status ): void {
		$status = match ($status) {
			self::DONE     => self::SHIPPED,
			self::REFUNDED => self::CANCELLED,
			default        => $status,
		};

		$valid        = array( self::PENDING, self::ON_HOLD, self::RECEIVED, self::SHIPPED, self::CANCELLED, self::ARCHIVED );
		$this->status = in_array( $status, $valid, true ) ? $status : self::CANCELLED;
	}

	public function setDisplayMode( string $display_mode ): void {
		$valid              = array( self::DISPLAY_STANDARD, self::DISPLAY_COMPACT );
		$this->display_mode = in_array( $display_mode, $valid, true ) ? $display_mode : self::DISPLAY_STANDARD;
	}

	public function render(): void {
		if ( in_array( $this->status, array( self::PENDING, self::ON_HOLD, self::CANCELLED, self::ARCHIVED ), true ) ) {
			$single_labels = array(
				self::PENDING   => 'Pendiente de pago',
				self::ON_HOLD   => 'Pago por confirmar',
				self::CANCELLED => 'Cancelado',
				self::ARCHIVED  => 'Archivado',
			);
			$single_label = $single_labels[ $this->status ];
			$aria_label   = 'Estado del pedido: ' . $single_label;
			$width_class  = 'level--100%';
			?>
			<div class="bsc__progress-bar bsc__progress-bar--single" aria-label="<?php echo esc_attr( $aria_label ); ?>">
				<div class="progress-bar">
					<div class="progress-bar__background"></div>
					<div class="progress-bar__level level--gray <?php echo esc_attr( $width_class ); ?>"></div>
				</div>
				<div class="labels">
					<div class="label label--focus">
						<span class="label__line">|</span>
						<span class="label__text"><?php echo esc_html( $single_label ); ?></span>
					</div>
				</div>
			</div>
			<?php
			return;
		}

		$config = $this->build_progress_config();
		$aria_label = 'Estado del pedido: ' . $config['current_label'];
		?>
		<div class="bsc__progress-bar" aria-label="<?php echo esc_attr( $aria_label ); ?>">
			<div class="progress-bar">
				<div class="progress-bar__background"></div>
				<div class="progress-bar__level <?php echo esc_attr( trim( $config['bar_color_class'] . ' ' . $config['progress_width_class'] ) ); ?>"></div>
			</div>
			<div class="labels">
				<?php foreach ($config['labels'] as $label) : ?>
					<div class="label <?php echo esc_attr( $label['is_active'] ? 'label--focus' : 'label--non-focus' ); ?>">
						<span class="label__line">|</span>
						<span class="label__text"><?php echo esc_html( $label['text'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	private function build_progress_config(): array {
		$is_shipped           = $this->status === self::SHIPPED;
		$current_label        = $is_shipped ? 'Enviado' : 'Recibido';
		$progress_width_class = $is_shipped ? 'level--100%' : 'level--50%';
		$bar_color_class      = $is_shipped ? 'level--blue' : 'level--pink';

		return array(
			'progress_width_class' => $progress_width_class,
			'bar_color_class'      => $bar_color_class,
			'current_label'        => $current_label,
			'labels'               => array(
				array(
					'text'      => 'Recibido',
					'is_active' => ! $is_shipped,
				),
				array(
					'text'      => 'Enviado',
					'is_active' => $is_shipped,
				),
			),
		);
	}
}
?>
