<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\UI\LayoutBlueprintService;
use PHPUnit\Framework\TestCase;

class LayoutTask00019Test extends TestCase
{
    public function test_layout_blueprint_service_should_render_core_sections_when_base_layout_is_requested(): void
    {
        $service = new LayoutBlueprintService();

        $layout = $service->render('desktop');

        $this->assertTrue($layout['header']);
        $this->assertTrue($layout['footer']);
        $this->assertTrue($layout['navigation']);
    }

    public function test_layout_blueprint_service_should_adapt_structure_when_viewport_changes_between_mobile_and_desktop(): void
    {
        $service = new LayoutBlueprintService();

        $mobile = $service->render('mobile');
        $desktop = $service->render('desktop');

        $this->assertNotSame($mobile['columns'], $desktop['columns']);
        $this->assertGreaterThanOrEqual(1, $mobile['columns']);
    }
}