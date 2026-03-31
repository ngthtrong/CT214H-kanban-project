<?php

declare(strict_types=1);

namespace App\UI;

class LayoutBlueprintService
{
    /**
     * @return array{header: bool, footer: bool, navigation: bool, columns: int}
     */
    public function render(string $viewport): array
    {
        $isMobile = strtolower(trim($viewport)) === 'mobile';

        return [
            'header' => true,
            'footer' => true,
            'navigation' => true,
            'columns' => $isMobile ? 1 : 3,
        ];
    }
}