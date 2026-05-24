<?php

class BSC_Order_Progress_Bar {
    public const RECEIVED  = 'received';
    public const SHIPPED   = 'shipped';
    public const DONE      = 'done';
    public const CANCELLED = 'cancelled';
    public const REFUNDED  = 'refunded';
    public const DISPLAY_STANDARD = 'standard';
    public const DISPLAY_COMPACT  = 'compact';

    private string $status = self::CANCELLED;
    private string $display_mode = self::DISPLAY_STANDARD;

    public function __construct(string $initial_status = self::CANCELLED, string $display_mode = self::DISPLAY_STANDARD) {
        $this->setStatus($initial_status);
        $this->setDisplayMode($display_mode);
    }

    public function setStatus(string $status): void {
        $valid = [self::RECEIVED, self::SHIPPED, self::DONE, self::CANCELLED, self::REFUNDED];
        $this->status = in_array($status, $valid, true) ? $status : self::CANCELLED;
    }

    public function setDisplayMode(string $display_mode): void {
        $valid = [self::DISPLAY_STANDARD, self::DISPLAY_COMPACT];
        $this->display_mode = in_array($display_mode, $valid, true) ? $display_mode : self::DISPLAY_STANDARD;
    }

    public function render(): void {
        if ($this->status === self::CANCELLED || $this->status === self::REFUNDED) {
            $single_label = $this->status === self::REFUNDED ? 'Reembolsado' : 'Cancelado';
            $width_class = 'level--100%';
            ?>
            <div class="bsc__progress-bar bsc__progress-bar--single">
                <div class="progress-bar">
                    <div class="progress-bar__background"></div>
                    <div class="progress-bar__level level--gray <?php echo esc_attr($width_class); ?>"></div>
                </div>
                <div class="labels">
                    <div class="label label--focus">
                        <span class="label__line">|</span>
                        <span class="label__text"><?php echo esc_html($single_label); ?></span>
                    </div>
                </div>
            </div>
            <?php
            return;
        }

        $config = $this->build_progress_config();
        ?>
        <div class="bsc__progress-bar">
            <div class="progress-bar">
                <div class="progress-bar__background"></div>
                <div class="progress-bar__level <?php echo esc_attr(trim($config['bar_color_class'] . ' ' . $config['progress_width_class'])); ?>"></div>
            </div>
            <div class="labels">
                <?php foreach ($config['labels'] as $label): ?>
        <div class="label <?php echo esc_attr($label['is_active'] ? 'label--focus' : 'label--non-focus'); ?>">
                        <span class="label__line">|</span>
                        <span class="label__text"><?php echo esc_html($label['text']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function build_progress_config(): array {
        if ($this->display_mode === self::DISPLAY_COMPACT) {
            $is_done = $this->status === self::DONE;

            return [
                'progress_width_class' => $is_done ? 'level--100%' : 'level--50%',
                'bar_color_class'      => $is_done ? 'level--blue' : 'level--pink',
                'labels'               => [
                    ['text' => 'Recibido', 'is_active' => true],
                    ['text' => 'Entregado', 'is_active' => $is_done],
                ],
            ];
        }

        $step_order = [
            self::RECEIVED => 1,
            self::SHIPPED  => 2,
            self::DONE     => 3,
        ];

        $progress_width_class = match ($this->status) {
            self::RECEIVED => 'level--33%',
            self::SHIPPED  => 'level--66%',
            self::DONE     => 'level--100%',
            default        => '',
        };

        $bar_color_class = ($this->status === self::DONE) ? 'level--blue' : 'level--pink';
        $active_index    = $step_order[$this->status] ?? 0;

        return [
            'progress_width_class' => $progress_width_class,
            'bar_color_class'      => $bar_color_class,
            'labels'               => [
                ['text' => 'Recibido', 'is_active' => $active_index >= 1],
                ['text' => 'Enviado', 'is_active' => $active_index >= 2],
                ['text' => 'Entregado', 'is_active' => $active_index >= 3],
            ],
        ];
    }
}
?>
