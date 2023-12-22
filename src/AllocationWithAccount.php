<?php

namespace Unbank\Kyckglobal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\BelongsToUser;
use Osoobe\Utilities\Traits\TimeDiff;
use Unbank\Kyckglobal\Traits\BelongsToPayee;

class AllocationWithAccount extends Model
{
    use BelongsToPayee;
    use BelongsToUser;
    use HasFactory;
    use TimeDiff;

    const ACCOUNT_TYPE_PUSH_TO_CARD = 'PushToCard';
    const ACCOUNT_TYPE_PAYPAL = 'paypal';


    protected $fillable = [
        'user_id',
        'payee_id',
        'account_id',
        'account_type',
        'allocation',
        'account_reference',
        'disbursable_id',
        'disbursable_type'
    ];

    protected $table = "kyck_allocation_with_accounts";

    protected $casts = [
        'account_id' => 'int',
        'allocation' => 'int'
    ];


    /**
     * Get kyck disbusrsement account
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function disbursable(): \Illuminate\Database\Eloquent\Relations\MorphTo {
        return $this->morphTo();
    }


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($allocation) {
            $allocation->account_reference = $allocation->disbursable->getKyckDisbursemntAccountIdentifier();
            $allocation->account_type = $allocation->disbursable->getKyckDisbursemntAccountType();
        });

        static::updating(function ($allocation) {
            $allocation->account_reference = $allocation->disbursable->getKyckDisbursemntAccountIdentifier();
            $allocation->account_type = $allocation->disbursable->getKyckDisbursemntAccountType();
        });
    }

    /**
     * Scope a query to only include push to card account type
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePushToCard($query) {
        return $query->where('account_type', static::ACCOUNT_TYPE_PUSH_TO_CARD)
            ->where('account_id', '!=', null);
    }

    /**
     * Scope a query to only include paypal account type
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePayPal($query) {
        return $query->where('account_type', static::ACCOUNT_TYPE_PAYPAL)
            ->where('account_id', '!=', null);
    }

    /**
     * Scope a query to only include payee id
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePayeeId($query, string $payee_id) {
        return $query->where('payee_id', $payee_id);
    }

    /**
     * Scope a query to only include account id
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param integer $account_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccountId($query, int $account_id) {
        return $query->where('account_id', $account_id);
    }


    /**
     * Scope a query to only include account reference
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param integer $account_reference
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccountReference(R$query, string $account_reference) {
        return $query->where('account_reference', $account_reference);
    }

}
