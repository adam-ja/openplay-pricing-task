<?php

namespace App\Models;


use App\Traits\HasTimestampAccessors;
use App\Venue;
use Illuminate\Database\Eloquent\Model;

/**
 * Class VenueModel
 * @package App\Models
 * @property int $id
 * @property string $name
 * @property string $location
 * @property \DateTime|null $created_at
 * @property \DateTime|null $updated_at
 */
class VenueModel extends Model implements Venue
{
    use HasTimestampAccessors;

    protected $table = 'venues';

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
    public function getLocation(): string
    {
        return $this->location;
    }
}
