<?php

namespace App\Models;

use App\PricingModifier;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTimestampAccessors;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Class PricingModifierModel
 * @package App/Models
 * @property int $id
 * @property string $name
 * @property array $conditions
 * @property string $adjustment_type
 * @property float $adjustment_value
 * @property \DateTime|null $created_at
 * @property \DateTime|null $updated_at
 */
class PricingModifierModel extends Model implements PricingModifier
{
    use HasTimestampAccessors;

    /**
     * @var string
     */
    protected $table = 'pricing_modifiers';

    /**
     * @var array
     */
    protected $casts = ['conditions' => 'array'];

    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @inheritDoc
     */
    public function getAdjustmentType(): string
    {
        return $this->adjustment_type;
    }

    /**
     * @inheritDoc
     */
    public function getAdjustmentValue(): float
    {
        return $this->adjustment_value;
    }

    /**
     * @inheritDoc
     */
    public function getPricingOptions(): Collection
    {
        return $this->pricingOptions;
    }

    /**
     * @return BelongsToMany
     */
    public function pricingOptions(): BelongsToMany
    {
        return $this->belongsToMany(PricingOptionModel::class)
            ->using(PricingOptionPricingModifierPivot::class);
    }
}
