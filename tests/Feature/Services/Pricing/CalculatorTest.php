<?php

namespace Tests\Feature\Services\Pricing;

use App\Models\MemberModel;
use App\Models\PricingModifierModel;
use App\Models\PricingOptionModel;
use App\Models\ProductModel;
use App\Models\VenueModel;
use App\PricingModifier;
use App\Services\Pricing\Calculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    private Calculator $calculator;
    private PricingOptionModel $pricingOption;
    private ProductModel $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator    = $this->app->make(Calculator::class);
        $this->pricingOption = factory(PricingOptionModel::class)->create(['price' => 10]);
        $this->product       = factory(ProductModel::class)->make([
            'pricing_option_id' => $this->pricingOption->getId(),
        ]);
    }

    public function testTwentyPercentOffForCustomerUnderTwentyFiveScenario(): void
    {
        $this->attachModifierToPricingOption(factory(PricingModifierModel::class)->create([
            'conditions'       => [PricingModifier::CONDITION_AGE => ['to' => 25]],
            'adjustment_type'  => PricingModifier::ADJUSTMENT_MULTIPLIER,
            'adjustment_value' => 0.8,
        ]));

        $this->assertSame(8.0, $this->calculator->getBestPrice(
            $this->product,
            factory(VenueModel::class)->make(),
            factory(MemberModel::class)->make(['date_of_birth' => Carbon::parse('18 years ago')])
        ));
    }

    public function testGlasgowThreePoundProductScenario(): void
    {
        $this->attachModifierToPricingOption(factory(PricingModifierModel::class)->create([
            'conditions'       => [PricingModifier::CONDITION_VENUE_LOCATION => ['Glasgow']],
            'adjustment_type'  => PricingModifier::ADJUSTMENT_OVERRIDE,
            'adjustment_value' => 3,
        ]));

        $this->assertSame(3.0, $this->calculator->getBestPrice(
            $this->product,
            factory(VenueModel::class)->make(['location' => 'Glasgow']),
            factory(MemberModel::class)->make()
        ));
    }

    public function testFreeProductForPlatinumMemberScenario(): void
    {
        $this->attachModifierToPricingOption(factory(PricingModifierModel::class)->create([
            'conditions'       => [PricingModifier::CONDITION_MEMBERSHIP_TYPE => ['platinum']],
            'adjustment_type'  => PricingModifier::ADJUSTMENT_OVERRIDE,
            'adjustment_value' => 0,
        ]));

        $this->assertSame(0.0, $this->calculator->getBestPrice(
            $this->product,
            factory(VenueModel::class)->make(),
            factory(MemberModel::class)->make(['membership_type' => 'platinum'])
        ));
    }

    private function attachModifierToPricingOption(PricingModifierModel $modifier): void
    {
        $this->pricingOption->pricingModifiers()->attach($modifier->id, [
            'valid_from' => Carbon::yesterday(),
            'active'     => true,
        ]);
    }
}
