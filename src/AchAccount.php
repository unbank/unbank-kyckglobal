<?php

namespace Unbank\Kyckglobal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\BelongsToUser;

class AchAccount extends Model {
    use HasFactory;
    use BelongsToUser;

    protected $fillable = [
        'payee_id',
        'routing_number',
        'account_number',
        'account_name',
        'account_type',
    ];

    protected $casts = [
        'data' => 'array'
    ];

}