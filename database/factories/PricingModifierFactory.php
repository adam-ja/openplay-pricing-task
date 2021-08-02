<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\PricingModifierModel;
use App\PricingModifier;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$conditions = [
    [],
    [PricingModifier::CONDITION_AGE => ['to' => 18]],
    [PricingModifier::CONDITION_AGE => ['from' => 18, 'to' => 25]],
    [PricingModifier::CONDITION_AGE => ['from' => 65]],
    [PricingModifier::CONDITION_MEMBERSHIP_TYPE => ['silver', 'gold', 'platinum']],
    [PricingModifier::CONDITION_VENUE_LOCATION => ['Glasgow']],
    [PricingModifier::CONDITION_VENUE_LOCATION => ['London', 'Kidderminster']],
];

$adjustmentTypes = [
    PricingModifier::ADJUSTMENT_MULTIPLIER => [
        0.5,
        0.75,
        1.25,
        2,
    ],
    PricingModifier::ADJUSTMENT_FIXED => [
        -15,
        -5,
        2,
        50,
    ],
    PricingModifier::ADJUSTMENT_OVERRIDE => [
        3,
        5,
        5.50
    ],
];

$factory->define(PricingModifierModel::class, function (Faker $faker) use ($conditions, $adjustmentTypes) {
    $adjustmentType = $faker->randomElement(array_keys($adjustmentTypes));

    return [
        'name'             => $faker->company,
        'conditions'       => $faker->randomElement($conditions),
        'adjustment_type'  => $adjustmentType,
        'adjustment_value' => $faker->randomElement($adjustmentTypes[$adjustmentType]),
    ];
});
