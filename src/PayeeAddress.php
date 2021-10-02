<?php

namespace Unbank\Kyckglobal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Osoobe\Utilities\Traits\HasLocation;
use Osoobe\Utilities\Traits\HasVerified;
use Osoobe\Utilities\Traits\TimeDiff;

class PayeeAddress extends Model
{
    use HasFactory;
    use HasLocation;
    use HasVerified;
    use SoftDeletes;
    use TimeDiff;

    protected $table = "user_addresses";

    protected $fillable = [
        'user_id',
        "name",
        'country',
        'state',
        'city',
        'latitude',
        'longitude',
        'street_address',
        'verified',
        'zip_code',
        'source',
        'source_id'
    ];

    public function user() {
        return $this->belongsTo('App\Models\User');
    }
}
