<?php

namespace App\Services\Pricing;

use App\Member;
use App\PricingModifier;
use App\Venue;
use DateTime;
use Exception;

class ModifierConditionChecker
{
    public function checkConditions(PricingModifier $modifier, Venue $venue, Member $member): bool
    {
        foreach ($modifier->getConditions() as $type => $condition) {
            switch ($type) {
                case PricingModifier::CONDITION_AGE:
                    $memberAge = $member->getDateOfBirth()->diff(new DateTime())->y;

                    if (
                        (array_key_exists('from', $condition) && $condition['from'] > $memberAge)
                        || (array_key_exists('to', $condition) && $condition['to'] < $memberAge)
                    ) {
                        return false;
                    }

                    break;

                case PricingModifier::CONDITION_VENUE_LOCATION:
                    if (! in_array($venue->getLocation(), $condition)) {
                        return false;
                    }

                    break;

                case PricingModifier::CONDITION_MEMBERSHIP_TYPE:
                    if (! in_array($member->getMembershipType(), $condition)) {
                        return false;
                    }

                    break;

                default:
                    throw new Exception("Unrecognised pricing modifier condition: $type.");
            }
        }

        return true;
    }
}
