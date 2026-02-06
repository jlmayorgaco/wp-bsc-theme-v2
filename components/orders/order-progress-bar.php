
<?php
// src/components/BSC_Order_Progress_Bar.php

class BSC_Order_Progress_Bar {
    // --- Constants for status ---
    public const PENDING    = 'pending';
    public const RECEIVED   = 'received';
    public const SHIPPED    = 'shipped';
    public const DELIVERED  = 'delivered';
    public const CANCELLED  = 'cancelled';

    // --- Properties ---
    private string $status = self::PENDING;
    private array $statuses = [
        self::PENDING,
        self::RECEIVED,
        self::SHIPPED,
        self::DELIVERED,
    ];

    // --- Constructor ---
    public function __construct(string $initial_status = self::PENDING) {
        $this->setStatus($initial_status);
    }

    // --- Set current status ---
    public function setStatus(string $status): void {
        if (!in_array($status, [
            self::PENDING,
            self::RECEIVED,
            self::SHIPPED,
            self::DELIVERED,
            self::CANCELLED
        ])) {
            $status = self::PENDING;
        }
        $this->status = $status;
    }

    // --- Render progress bar ---
    public function render(): void {
        $status_order = [
            self::RECEIVED  => 1,
            self::DELIVERED => 3,
        ];

        // Special handling for pending and cancelled
        if ($this->status === self::PENDING || $this->status === self::CANCELLED) {
            $label_text = $this->status === self::PENDING ? 'Pago Pendiente' : 'Cancelado';
            ?>
            <div class="bsc__progress-bar bsc__progress-bar--single">
                <div class="progress-bar">
                    <div class="progress-bar__background"></div>  
                    <div class="progress-bar__level level--gray" style="width: 100%;"></div>  
                </div>    
                <div class="labels">
                    <div class="label label--focus">
                        <span class="label__line">|</span>
                        <span class="label__text"><?php echo esc_html($label_text); ?></span>
                    </div>
                </div>    
            </div>
            <?php
            return;
        }

        // Map status to width percentage
        $progress_percent = match ($this->status) {
            self::RECEIVED  => '33%',
            self::DELIVERED => '100%',
            default         => '0%',
        };

        $bar_color_class = match ($this->status) {
            self::DELIVERED => 'level--blue',
            default         => 'level--pink',
        };

        $active_index = $status_order[$this->status] ?? 0;

        $labels = [
            1 => ['text' => '¡Recibido!',  'is_active' => $active_index >= 1],
            3 => ['text' => '¡Entregado!', 'is_active' => $active_index >= 3],
        ];
        ?>
        <div class="bsc__progress-bar">
            <div class="progress-bar">
                <div class="progress-bar__background"></div>  
                <div class="progress-bar__level <?php echo $bar_color_class; ?>" style="width: <?php echo $progress_percent; ?>;"></div>  
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




    private function isStepActive(string $step): bool {
        $order = [
            self::PENDING => 0,
            self::RECEIVED => 1,
            self::SHIPPED => 2,
            self::DELIVERED => 3,
            self::CANCELLED => -1,
        ];
        return $order[$step] <= $order[$this->status];
    }

    private function getLabel(string $status): string {
        return match ($status) {
            self::PENDING   => 'Pago Pendiente',
            self::RECEIVED  => '¡Recibido!',
            self::SHIPPED   => '¡Enviado!',
            self::DELIVERED => '¡Entregado!',
            self::CANCELLED => '¡Cancelado!',
            default         => $status,
        };
    }
}


?>