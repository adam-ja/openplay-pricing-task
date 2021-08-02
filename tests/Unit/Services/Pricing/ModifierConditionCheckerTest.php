<?php

namespace Tests\Unit\Services\Pricing;

use App\Models\MemberModel;
use App\Models\PricingModifierModel;
use App\Models\VenueModel;
use App\PricingModifier;
use App\Services\Pricing\ModifierConditionChecker;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Tests\TestCase;

class ModifierConditionCheckerTest extends TestCase
{
    private static ModifierConditionChecker $checker;
    private static Generator $faker;

    public static function setUpBeforeClass(): void
    {
        self::$checker = new ModifierConditionChecker();
        self::$faker   = Factory::create();
    }

    public function testReturnsTrueForModifierWithNoConditions(): void
    {
        $this->assertTrue(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make(['conditions' => []]),
            factory(VenueModel::class)->make(),
            factory(MemberModel::class)->make()
        ));
    }

    /**
     * @dataProvider provideFailingAgeRangeConditions
     *
     * @param int[] $conditionAgeRange
     * @param int[] $memberAgeRange
     */
    public function testReturnsFalseIfAgeRangeConditionIsNotMet(array $conditionAgeRange, array $memberAgeRange): void
    {
        $this->assertFalse(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make([
                'conditions' => [PricingModifier::CONDITION_AGE => $conditionAgeRange],
            ]),
            factory(VenueModel::class)->make(),
            factory(MemberModel::class)->make([
                'date_of_birth' => self::$faker->dateTimeBetween(
                    "-{$memberAgeRange['to']} years",
                    "-{$memberAgeRange['from']} years"
                )
            ]),
        ));
    }

    public function provideFailingAgeRangeConditions(): array
    {
        return [
            'too old with "to" only'         => [['to' => 18], ['from' => 19, 'to' => 99]],
            'too young with "from" only"'    => [['from' => 65], ['from' => 13, 'to' => 64]],
            'too old with "from" and "to"'   => [['from' => 18, 'to' => 25], ['from' => 26, 'to' => 99]],
            'too young with "from" and "to"' => [['from' => 18, 'to' => 25], ['from' => 13, 'to' => 17]],
        ];
    }

    /**
     * @dataProvider provideSuccessfulAgeRangeConditions
     *
     * @param int[] $conditionAgeRange
     * @param int[] $memberAgeRange
     */
    public function testReturnsTrueIfAgeRangeConditionIsMet(array $conditionAgeRange, array $memberAgeRange): void
    {
        $this->assertTrue(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make([
                'conditions' => [PricingModifier::CONDITION_AGE => $conditionAgeRange],
            ]),
            factory(VenueModel::class)->make(),
            factory(MemberModel::class)->make([
                'date_of_birth' => self::$faker->dateTimeBetween(
                    "-{$memberAgeRange['to']} years",
                    "-{$memberAgeRange['from']} years"
                )
            ]),
        ));
    }

    public function provideSuccessfulAgeRangeConditions(): array
    {
        return [
            'no older than "to" limit'       => [['to' => 18], ['from' => 13, 'to' => 18]],
            'no younger than "from" limit'   => [['from' => 65], ['from' => 65, 'to' => 99]],
            'between "from" and "to" limits' => [['from' => 18, 'to' => 25], ['from' => 18, 'to' => 25]],
        ];
    }

    public function testReturnsFalseIfVenueLocationConditionIsNotMet(): void
    {
        $locations      = ['Edinburgh', 'Glasgow', 'Hull', 'Leeds', 'London', 'Manchester', 'Sheffield'];
        $venueLocations = self::$faker->randomElements($locations, 3);

        $this->assertFalse(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make([
                'conditions' => [PricingModifier::CONDITION_VENUE_LOCATION => $venueLocations],
            ]),
            factory(VenueModel::class)->make([
                'location' => self::$faker->randomElement(array_diff($locations, $venueLocations)),
            ]),
            factory(MemberModel::class)->make(),
        ));
    }

    public function testReturnsTrueIfVenueLocationConditionIsMet(): void
    {
        $locations = ['Hull', 'Leeds', 'Sheffield'];

        $this->assertTrue(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make([
                'conditions' => [PricingModifier::CONDITION_VENUE_LOCATION => $locations],
            ]),
            factory(VenueModel::class)->make(['location' => self::$faker->randomElement($locations)]),
            factory(MemberModel::class)->make(),
        ));
    }

    public function testReturnsFalseIfMembershipTypeConditionIsNotMet(): void
    {
        $allTypes       = ['bronze', 'silver', 'gold', 'platinum'];
        $conditionTypes = self::$faker->randomElements($allTypes, 2);

        $this->assertFalse(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make([
                'conditions' => [PricingModifier::CONDITION_MEMBERSHIP_TYPE => $conditionTypes],
            ]),
            factory(VenueModel::class)->make(),
            factory(MemberModel::class)->make([
                'membership_type' => self::$faker->randomElement(array_diff($allTypes, $conditionTypes)),
            ]),
        ));
    }

    public function testReturnsTrueIfMembershipTypeConditionIsMet(): void
    {
        $types = ['gold', 'platinum'];

        $this->assertTrue(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make([
                'conditions' => [PricingModifier::CONDITION_MEMBERSHIP_TYPE => $types],
            ]),
            factory(VenueModel::class)->make(),
            factory(MemberModel::class)->make(['membership_type' => self::$faker->randomElement($types)]),
        ));
    }

    public function testReturnsTrueIfAllOfMultipleConditionsAreMet(): void
    {
        $this->assertTrue(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make(['conditions' => [
                PricingModifier::CONDITION_AGE             => ['from' => 18, 'to' => 65],
                PricingModifier::CONDITION_MEMBERSHIP_TYPE => ['platinum'],
                PricingModifier::CONDITION_VENUE_LOCATION  => ['London', 'Edinburgh'],
            ]]),
            factory(VenueModel::class)->make(['location' => 'Edinburgh']),
            factory(MemberModel::class)->make([
                'membership_type' => 'platinum',
                'date_of_birth'   => self::$faker->dateTimeBetween('-65 years', '-18 years'),
            ]),
        ));
    }

    public function testReturnsFalseIfAnyOneOfMultipleConditionsIsNotMet(): void
    {
        $this->assertFalse(self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make(['conditions' => [
                PricingModifier::CONDITION_AGE             => ['from' => 18, 'to' => 65],
                PricingModifier::CONDITION_MEMBERSHIP_TYPE => ['platinum'],
                PricingModifier::CONDITION_VENUE_LOCATION  => ['London', 'Edinburgh'],
            ]]),
            factory(VenueModel::class)->make(['location' => 'Edinburgh']),
            factory(MemberModel::class)->make([
                'membership_type' => 'silver',
                'date_of_birth'   => self::$faker->dateTimeBetween('-65 years', '-18 years'),
            ]),
        ));
    }

    public function testThrowsExceptionIfConditionTypeIsNotRecognised(): void
    {
        $this->expectException(Exception::class);

        self::$checker->checkConditions(
            factory(PricingModifierModel::class)->make(['conditions' => ['foo' => 'bar']]),
            factory(VenueModel::class)->make(),
            factory(MemberModel::class)->make(),
        );
    }
}
