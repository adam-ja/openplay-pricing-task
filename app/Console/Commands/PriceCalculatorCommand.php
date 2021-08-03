<?php

namespace App\Console\Commands;

use App\Member;
use App\Models\MemberModel;
use App\Models\ProductModel;
use App\Models\VenueModel;
use App\Product;
use App\Services\Pricing\Calculator;
use App\Venue;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PriceCalculatorCommand extends Command
{
    protected $signature = 'price:calculate
        {--product= : ID of the product to calculate a price for (optional)}
        {--venue= : ID of the venue to calculate a price for (optional)}
        {--member= : ID of the member to calculate a price for (optional)}';

    protected $description = 'Find the best price for a product, venue, member combination. If the ID for any of these
        three entities is not passed, one will be selected at random.';

    public function handle(Calculator $calculator): int
    {
        try {
            $product = $this->getProduct();
            $venue   = $this->getVenue();
            $member  = $this->getMember();
        } catch (ModelNotFoundException $e) {
            $this->output->error($e->getMessage());

            return 1;
        }

        $price = $calculator->getBestPrice($product, $venue, $member);

        $this->output->info('Original product price: £' . number_format($product->getPricingOption()->getPrice(), 2));
        $this->outputModifiers($calculator->getValidModifiers());
        $this->output->success('Best price: £' . number_format($price, 2));

        return 0;
    }

    private function getProduct(): Product
    {
        $productId = $this->option('product');
        /** @var Product $product */
        $product = $productId ? ProductModel::findOrFail($productId) : ProductModel::inRandomOrder()->first();

        $this->output->info("Product {$product->getId()}: {$product->getName()}");

        return $product;
    }

    private function getVenue(): Venue
    {
        $venueId = $this->option('venue');
        /** @var Venue $venue */
        $venue = $venueId ? VenueModel::findOrFail($venueId) : VenueModel::inRandomOrder()->first();

        $this->output->info("Venue {$venue->getId()}: {$venue->getName()} ({$venue->getLocation()})");

        return $venue;
    }

    private function getMember(): Member
    {
        $memberId = $this->option('member');
        /** @var Member $member */
        $member    = $memberId ? MemberModel::findOrFail($memberId) : MemberModel::inRandomOrder()->first();
        $memberAge = $member->getDateOfBirth()->diff(new DateTime())->y;

        $this->output->info(
            "Member {$member->getId()}: {$member->getName()}"
            . " ({$member->getMembershipType()} member, $memberAge years old)"
        );

        return $member;
    }

    private function outputModifiers(array $validModifiers): void
    {
        if (empty($validModifiers)) {
            $this->output->info('No valid pricing modifiers for this product/venue/member combination.');

            return;
        }

        $this->output->info('The conditions were met for the following pricing modifiers:');

        $this->output->table([
            'ID',
            'Name',
            'Conditions',
            'Adjustment type',
            'Adjustment value',
            'New price',
        ], array_map(fn(array $modifier) => ([
            $modifier['modifier']->getId(),
            $modifier['modifier']->getName(),
            json_encode($modifier['modifier']->getConditions()),
            $modifier['modifier']->getAdjustmentType(),
            $modifier['modifier']->getAdjustmentValue(),
            $modifier['price'],
        ]), $validModifiers));
    }
}
