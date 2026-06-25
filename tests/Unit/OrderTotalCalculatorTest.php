<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Order\OrderTotalCalculator;
use Tests\TestCase;

final class OrderTotalCalculatorTest extends TestCase
{
    public function test_calculates_line_total_and_order_total(): void
    {
        $calculator = new OrderTotalCalculator();

        $this->assertSame('20.00', $calculator->calculateLineTotal([
            'quantity' => 2,
            'unit_price' => '10.00',
        ]));

        $this->assertSame('35.50', $calculator->calculate([
            ['quantity' => 2, 'unit_price' => '10.00'],
            ['quantity' => 1, 'unit_price' => '15.50'],
        ]));
    }
}
