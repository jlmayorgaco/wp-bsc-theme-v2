
<?php

class BSC_Order_Progress_Bar {
    // 4 visible states: RECEIVED → SHIPPED → DONE | CANCELLED
    public const RECEIVED  = 'received';
    public const SHIPPED   = 'shipped';
    public const DONE      = 'done';
    public const CANCELLED = 'cancelled';
    public const REFUNDED  = 'refunded';

    private string $status = self::CANCELLED;

    public function __construct(string $initial_status = self::CANCELLED) {
        $this->setStatus($initial_status);
    }

    public function setStatus(string $status): void {
        $valid = [self::RECEIVED, self::SHIPPED, self::DONE, self::CANCELLED, self::REFUNDED];
        $this->status = in_array($status, $valid, true) ? $status : self::CANCELLED;
    }

    public function render(): void {
        if ($this->status === self::CANCELLED || $this->status === self::REFUNDED) {
            $single_label = $this->status === self::REFUNDED ? 'Reembolsado' : 'Cancelado';
            ?>
            <div class="bsc__progress-bar bsc__progress-bar--single">
                <div class="progress-bar">
                    <div class="progress-bar__background"></div>
                    <div class="progress-bar__level level--gray" style="width: 100%;"></div>
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

        // 3-step bar: Recibido → Enviado → Terminado
        $step_order = [
            self::RECEIVED => 1,
            self::SHIPPED  => 2,
            self::DONE     => 3,
        ];

        $progress_percent = match ($this->status) {
            self::RECEIVED => '33%',
            self::SHIPPED  => '66%',
            self::DONE     => '100%',
            default        => '0%',
        };

        $bar_color_class = ($this->status === self::DONE) ? 'level--blue' : 'level--pink';
        $active_index    = $step_order[$this->status] ?? 0;

        $labels = [
            ['text' => '¡Recibido!',  'is_active' => $active_index >= 1],
            ['text' => '¡Enviado!',   'is_active' => $active_index >= 2],
            ['text' => '¡Terminado!', 'is_active' => $active_index >= 3],
        ];
        ?>
        <div class="bsc__progress-bar">
            <div class="progress-bar">
                <div class="progress-bar__background"></div>
                <div class="progress-bar__level <?php echo esc_attr($bar_color_class); ?>" style="width: <?php echo esc_attr($progress_percent); ?>;"></div>
            </div>
            <div class="labels">
                <?php foreach ($labels as $label): ?>
                    <div class="label <?php echo $label['is_active'] ? 'label--focus' : 'label--non-focus'; ?>">
                        <span class="label__line">|</span>
                        <span class="label__text"><?php echo esc_html($label['text']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
?>
