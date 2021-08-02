<?php

namespace Tests\Unit\Services\Pricing;

use App\Models\MemberModel;
use App\Models\PricingModifierModel;
use App\Models\PricingOptionModel;
use App\Models\ProductModel;
use App\Models\VenueModel;
use App\Services\Pricing\Calculator;
use App\Services\Pricing\ModifierAdjustmentCalculator;
use App\Services\Pricing\ModifierConditionChecker;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface|ModifierConditionChecker $modifierChecker;
    private MockInterface|ModifierAdjustmentCalculator $modifierCalculator;
    private Calculator $calculator;
    private PricingOptionModel $pricingOption;
    private ProductModel $product;
    private VenueModel $venue;
    private MemberModel $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modifierChecker    = $this->mock(ModifierConditionChecker::class);
        $this->modifierCalculator = $this->mock(ModifierAdjustmentCalculator::class);
        $this->calculator         = new Calculator($this->modifierChecker, $this->modifierCalculator);

        $this->pricingOption = factory(PricingOptionModel::class)->create(['price' => 99.99]);
        $this->product       = factory(ProductModel::class)->make([
            'pricing_option_id' => $this->pricingOption->getId(),
        ]);
        $this->venue  = factory(VenueModel::class)->make();
        $this->member = factory(MemberModel::class)->make();
    }

    public function testReturnsOriginalPricingOptionPriceIfNoCurrentModifiers(): void
    {
        $this->attachModifierToPricingOption(factory(PricingModifierModel::class)->create(), false);

        $this->assertSame(99.99, $this->calculator->getBestPrice($this->product, $this->venue, $this->member));
    }

    public function testIgnoresModifiersWhoseConditionsAreNotMet(): void
    {
        $modifier = factory(PricingModifierModel::class)->create();

        $this->attachModifierToPricingOption($modifier);
        $this->mockModifierConditionsMet($modifier, false);

        $this->assertSame(99.99, $this->calculator->getBestPrice($this->product, $this->venue, $this->member));
    }

    public function testReturnsCheapestPriceFromAllModifiers(): void
    {
        $cheaperModifier       = factory(PricingModifierModel::class)->create();
        $cheapestModifier      = factory(PricingModifierModel::class)->create();
        $moreExpensiveModifier = factory(PricingModifierModel::class)->create();

        $this->attachModifierToPricingOption($cheaperModifier);
        $this->attachModifierToPricingOption($cheapestModifier);
        $this->attachModifierToPricingOption($moreExpensiveModifier);
        $this->mockModifierConditionsMet($cheaperModifier);
        $this->mockModifierConditionsMet($cheapestModifier);
        $this->mockModifierConditionsMet($moreExpensiveModifier);
        $this->mockModifierCalculatorResult($cheaperModifier, 89.99);
        $this->mockModifierCalculatorResult($cheapestModifier, 84.99);
        $this->mockModifierCalculatorResult($moreExpensiveModifier, 105);

        $this->assertSame(84.99, $this->calculator->getBestPrice($this->product, $this->venue, $this->member));
    }

    public function testProvidesListOfAllModifiersWhoseConditionsWereMet(): void
    {
        $modifier1 = factory(PricingModifierModel::class)->create();
        $modifier2 = factory(PricingModifierModel::class)->create();
        $modifier3 = factory(PricingModifierModel::class)->create();

        $this->attachModifierToPricingOption($modifier1);
        $this->attachModifierToPricingOption($modifier2);
        $this->attachModifierToPricingOption($modifier3);
        $this->mockModifierConditionsMet($modifier1);
        $this->mockModifierConditionsMet($modifier2, false);
        $this->mockModifierConditionsMet($modifier3);
        $this->mockModifierCalculatorResult($modifier1, 89.99);
        $this->mockModifierCalculatorResult($modifier3, 74.99);

        $this->calculator->getBestPrice($this->product, $this->venue, $this->member);

        $this->assertEquals([
            [
                'id'    => $modifier1->getId(),
                'name'  => $modifier1->getName(),
                'price' => 89.99,
            ],
            [
                'id'    => $modifier3->getId(),
                'name'  => $modifier3->getName(),
                'price' => 74.99,
            ]
        ], $this->calculator->getValidModifiers());
    }

    private function mockModifierConditionsMet(PricingModifierModel $modifier, bool $conditionsMet = true): void
    {
        $this->modifierChecker->expects()->checkConditions(
            Mockery::on(fn (PricingModifierModel $passedModifier) => ($passedModifier->id === $modifier->id)),
            $this->venue,
            $this->member
        )->andReturns($conditionsMet);
    }

    private function attachModifierToPricingOption(PricingModifierModel $modifier, bool $isCurrent = true): void
    {
        $this->pricingOption->pricingModifiers()->attach($modifier->id, [
            'valid_from' => $isCurrent ? Carbon::yesterday() : Carbon::tomorrow(),
            'active'     => true,
        ]);
    }

    private function mockModifierCalculatorResult(PricingModifierModel $modifier, float $price): void
    {
        $this->modifierCalculator->expects()->calculatePrice(
            99.99,
            Mockery::on(fn (PricingModifierModel $passedModifier) => ($passedModifier->id === $modifier->id))
        )->andReturns($price);
    }
}
