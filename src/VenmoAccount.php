<?php

namespace Unbank\Kyckglobal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\BelongsToUser;
use Osoobe\LaravelTraits\Support\HasPhoneNumber;
use Osoobe\LaravelTraits\Support\IsDefault;
use Unbank\Kyckglobal\Contract\DisbursemntAccount;
use Unbank\Kyckglobal\Traits\HasKyckAccountAllocation;

class VenmoAccount extends Model implements DisbursemntAccount
{
    use BelongsToUser;
    use HasFactory;
    use HasKyckAccountAllocation;
    use IsDefault;
    use HasPhoneNumber;

    protected $fillable = [
        "phone_number",
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
        return "Venmo";
    }


    /**
     * Get kyck disbursement account identifier
     */
    public function getKyckDisbursemntAccountReference() {
        return $this->phone_number;
    }

}
