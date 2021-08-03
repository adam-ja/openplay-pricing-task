<?php

namespace App\Services\Pricing;

use App\Member;
use App\PricingModifier;
use App\Product;
use App\Venue;

class Calculator
{
    private array $validModifiers = [];

    public function __construct(
        private ModifierConditionChecker $modifierConditionChecker,
        private ModifierAdjustmentCalculator $modifierAdjustmentCalculator
    ) {
    }

    public function getBestPrice(Product $product, Venue $venue, Member $member): float
    {
        $this->validModifiers = [];

        $pricingOption    = $product->getPricingOption();
        $bestPrice        = $pricingOption->getPrice();
        $currentModifiers = $pricingOption->getCurrentPricingModifiers();

        /** @var PricingModifier $modifier */
        foreach ($currentModifiers as $modifier) {
            if (! $this->modifierConditionChecker->checkConditions($modifier, $venue, $member)) {
                continue;
            }

            $price = $this->modifierAdjustmentCalculator->calculatePrice($pricingOption->getPrice(), $modifier);

            $this->validModifiers[] = [
                'modifier' => $modifier,
                'price'    => $price,
            ];

            $bestPrice = min($bestPrice, $price);
        }

        return $bestPrice;
    }

    public function getValidModifiers(): array
    {
        return $this->validModifiers;
    }
}
