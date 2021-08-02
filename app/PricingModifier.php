<?php

namespace App;

use Illuminate\Support\Collection;

/**
 * Interface PricingModifier
 * @package OpenPlay\Pricing\Modifiers
 */
interface PricingModifier
{
    public const CONDITION_AGE             = 'age_range';
    public const CONDITION_VENUE_LOCATION  = 'venue_locations';
    public const CONDITION_MEMBERSHIP_TYPE = 'membership_types';

    public const ADJUSTMENT_MULTIPLIER = 'multiplier';
    public const ADJUSTMENT_FIXED      = 'fixed';
    public const ADJUSTMENT_OVERRIDE   = 'override';

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return array
     */
    public function getConditions(): array;

    /**
     * @return string
     */
    public function getAdjustmentType(): string;

    /**
     * @return float
     */
    public function getAdjustmentValue(): float;

    /**
     * @return Collection|PricingOption[]
     */
    public function getPricingOptions(): Collection;

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime;

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime;
}
