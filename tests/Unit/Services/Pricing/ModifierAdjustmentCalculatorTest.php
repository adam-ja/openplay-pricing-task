<?php

namespace Tests\Unit\Services\Pricing;

use App\Models\PricingModifierModel;
use App\PricingModifier;
use App\Services\Pricing\ModifierAdjustmentCalculator;
use Exception;
use Tests\TestCase;

class ModifierAdjustmentCalculatorTest extends TestCase
{
    private static ModifierAdjustmentCalculator $calculator;

    public static function setUpBeforeClass(): void
    {
        self::$calculator = new ModifierAdjustmentCalculator();
    }

    /**
     * @dataProvider provideMultiplierAdjustments
     *
     * @param float $originalPrice
     * @param float $adjustmentValue
     * @param float $newPrice
     */
    public function testAppliesMultiplierAdjustmentToOriginalPrice(
        float $originalPrice,
        float $adjustmentValue,
        float $newPrice
    ): void {
        $this->assertSame($newPrice, self::$calculator->calculatePrice(
            $originalPrice,
            factory(PricingModifierModel::class)->make([
                'adjustment_type'  => PricingModifier::ADJUSTMENT_MULTIPLIER,
                'adjustment_value' => $adjustmentValue,
            ])
        ));
    }

    public function provideMultiplierAdjustments(): array
    {
        return [
            'half price'               => [15, 0.5, 7.5],
            '25% off'                  => [95, 0.75, 71.25],
            '15% surcharge'            => [200, 1.15, 230],
            'one third off (rounding)' => [99.99, 0.66, 65.99],
        ];
    }

    /**
     * @dataProvider provideFixedAdjustments
     *
     * @param float $originalPrice
     * @param float $adjustmentValue
     * @param float $newPrice
     */
    public function testAppliesFixedAdjustmentToOriginalPrice(
        float $originalPrice,
        float $adjustmentValue,
        float $newPrice
    ): void {
        $this->assertSame($newPrice, self::$calculator->calculatePrice(
            $originalPrice,
            factory(PricingModifierModel::class)->make([
                'adjustment_type'  => PricingModifier::ADJUSTMENT_FIXED,
                'adjustment_value' => $adjustmentValue,
            ])
        ));
    }

    public function provideFixedAdjustments(): array
    {
        return [
            '£10 off'         => [99.99, -10, 89.99],
            '50p off'         => [9.95, -0.5, 9.45],
            '£1.99 surcharge' => [20, 1.99, 21.99],
        ];
    }

    /**
     * @dataProvider provideOverrideAdjustments
     *
     * @param float $adjustmentValue
     */
    public function testAppliesOverrideAdjustmentToOriginalPrice(float $adjustmentValue): void
    {
        $this->assertSame($adjustmentValue, self::$calculator->calculatePrice(
            64.99,
            factory(PricingModifierModel::class)->make([
                'adjustment_type'  => PricingModifier::ADJUSTMENT_OVERRIDE,
                'adjustment_value' => $adjustmentValue,
            ])
        ));
    }

    public function provideOverrideAdjustments(): array
    {
        return [
            'free'    => [0],
            '99p'     => [0.99],
            '£124.99' => [124.99],
        ];
    }

    public function testThrowsExceptionIfAdjustmentTypeIsNotRecognised(): void
    {
        $this->expectException(Exception::class);

        self::$calculator->calculatePrice(59.98, factory(PricingModifierModel::class)->make([
            'adjustment_type' => 'foo',
        ]));
    }
}
