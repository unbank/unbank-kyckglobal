<?php

namespace Unbank\Kyckglobal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Osoobe\Utilities\Traits\Active;
use Osoobe\Utilities\Traits\TimeDiff;

class Payee extends Model
{
    use Active;
    use HasFactory;
    use SoftDeletes;
    use TimeDiff;

    protected $fillable = [
        'is_active',
        'user_id',
        'payee_id',
        'email',
        'phone_number',
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



    /**
     * Scope a query to only include objects  verified by email.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param string $email
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

}
