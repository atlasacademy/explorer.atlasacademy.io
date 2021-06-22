<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bucket extends Model
{

    protected $table = 'buckets';

    public function files(): HasMany
    {
        return $this->hasMany(BucketFile::class);
    }

}
