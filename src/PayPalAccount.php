<?php

namespace Unbank\Kyckglobal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\BelongsToUser;
use Osoobe\LaravelTraits\Support\IsDefault;
use Unbank\Kyckglobal\Contract\DisbursemntAccount;
use Unbank\Kyckglobal\Traits\HasKyckAccountAllocation;

class PayPalAccount extends Model implements DisbursemntAccount
{
    use BelongsToUser;
    use HasFactory;
    use HasKyckAccountAllocation;
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

    /**
     * Get kyck disbursement account type
     *
     * @return string
     */
    public function getKyckDisbursemntAccountType(): string {
        return "PayPal";
    }

    /**
     * Get kyck disbursement account identifier
     */
    public function getKyckDisbursemntAccountIdentifier() {
        return $this->email;
    }


}
