<?php

namespace App\Services\Pricing;

use App\PricingModifier;
use Exception;

class ModifierAdjustmentCalculator
{
    public function calculatePrice(float $originalPrice, PricingModifier $modifier): float
    {
        switch ($modifier->getAdjustmentType()) {
            case PricingModifier::ADJUSTMENT_MULTIPLIER:
                return round($originalPrice * $modifier->getAdjustmentValue(), 2);

            case PricingModifier::ADJUSTMENT_FIXED:
                return $originalPrice + $modifier->getAdjustmentValue();

            case PricingModifier::ADJUSTMENT_OVERRIDE:
                return $modifier->getAdjustmentValue();

            default:
                throw new Exception("Unrecognised pricing modifier adjustment type: {$modifier->getAdjustmentType()}");
        }
    }
}
