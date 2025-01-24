<?php

namespace Unbank\Kyckglobal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Osoobe\LaravelTraits\Support\BelongsToUser;
use Unbank\Kyckglobal\Contract\DisbursemntAccount;
use Unbank\Kyckglobal\Traits\HasKyckAccountAllocation;

class AchAccount extends Model implements DisbursemntAccount {
    use HasFactory;
    use BelongsToUser;
    use HasKyckAccountAllocation;

    protected $table="ach_accounts";

    protected $fillable = [
        'user_id',
        'payee_id',
        'routing_number',
        'account_number',
        'account_name',
        'account_type',
        'disbursement_account_id',
        'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    /**
     * Get kyck disbursement account type
     *
     * @return string
     */
    public function getKyckDisbursemntAccountType(): string {
        return "Ach";
    }

    /**
     * Get kyck disbursement account identifier
     */
    public function getKyckDisbursemntAccountReference() {
        return $this->account_number;
    }

}
