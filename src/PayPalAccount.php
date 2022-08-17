<?php

namespace Unbank\Kyckglobal;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\IsDefault;

class PayPalAccount extends Model
{
    use BelongsToUser;
    use HasFactory;
    use IsDefault;

    protected $table = "paypal_accounts";

    protected $fillable = [
        "email",
        'currency',
        'data',
        'is_default',
        'user_id'
    ];



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];


}
