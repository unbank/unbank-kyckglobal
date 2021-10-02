<?php

namespace Unbank\Kyckglobal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Osoobe\Utilities\Traits\Active;

class Payee extends Model
{
    use Active;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'is_active',
        'user_id',
        'payee_id',
        'service_provider',
        'status',
        'verified',
        'data'
    ];

    protected $table = "payees";


     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    public function user() {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

}
