<?php

namespace Unbank\Kyckglobal\Traits;

use Osoobe\Utilities\Helpers\Str;
use Osoobe\Utilities\Helpers\Utilities;
use Unbank\Kyckglobal\Facades\KyckGlobal;
use Unbank\Kyckglobal\Payee;

trait KyckPayeeTrait {

    /**
     * Get the payee that owns the KyckPayeeTrait
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function payee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        // return $this->belongsTo('Unbank\Kyckglobal\Payee', 'user_id');
        return $this->hasOne('\Unbank\Kyckglobal\Payee');
    }

    public function getOrCreatePayee() {
        if ( empty($this->payee) ) {
            if ( empty($this->email) ) {
                return null;
            }

            $user = $this;
            $payee = Payee::where('email', $user->email)
                ->where('user_id', null)
                ->orWhere('user_id', $user->id)
                ->first();

            if ( ! $payee ) {
                [$result, $payee] = KyckGlobal::createPayee($user);
                if ( $result ) {
                    $changes = [
                        'email' => $user->email,
                        'phone_number' => substr($this->phone_number_base, -10)
                    ];
                    activity()
                        ->causedBy($user)
                        ->performedOn($payee)
                        ->withProperties($changes)
                        ->log("Payee account was created");
                }
            }

            if ( ! is_array($payee) ) {
                $payee->user_id = $user->id;
                $payee->save();
                activity()
                    ->causedBy($user)
                    ->performedOn($payee)
                    ->withProperties(['user_id' => $payee->id])
                    ->log("Payee account info was updated");
            } else {
                activity()
                    ->causedBy($user)
                    ->withProperties($payee)
                    ->log("Payee account info was not updated");
                return null;
            }
            return $payee;
        }
        return $this->payee;
    }

    /**
     * Get all of the payees for the KyckPayeeTrait
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('Unbank\Kyckglobal\Payee');
    }


    public function kyck() {
        return $this->payees->where('service_provider', 'kyck')->first();
    }

    /**
     * Get the user that owns the KyckTrait
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function kyckPayee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne('\Unbank\Kyckglobal\Payee');
    }


    /**
     * Get all of the comments for the KyckTrait
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('\Unbank\Kyckglobal\PayeeAddress', 'user_id', 'id');
    }

    public function main_address() {
        return $this->belongsTo('\Unbank\Kyckglobal\PayeeAddress', 'address_id');
    }

    public function getKyckRegistrationData() {

        $name = Str::nameParts($this->name);

        $postData = [
            'email' => $this->email,
            'payeeDetails' => array (
                'payeeFirstName' => $name->first_name,
                'payeeLastName' => $name->last_name,
                'payeeMiddleName' => $name->middle_name,
                'pNumber' => substr($this->phone_number_base, -10),
                'pAddress' => Utilities::getObjectValue($this->main_address, 'street_address', ''),
                'pcity' => Utilities::getObjectValue($this->main_address, 'city', ''),
                'pstate' => Utilities::getObjectValue($this->main_address, 'state', ''),
                'pcountry' => Utilities::getObjectValue($this->main_address, 'country', '')
            ),
            'contactInfo' => array (
                'mobile' => substr($this->phone_number_base, -10),
                'sendSMS' => true,
            ),
            'userDisabled' => false,
            'payeeStatus' => ( $this->kyckPayee )?  $this->kyckPayee->status : false,
            "paymentTypes" => ["NCRpay360"],
            "ncrPay360" => [
                "ncrPay360Allocation" => 100
            ]
        ];

        return $postData;
    }


    /**
     * Get data that is used to update the payee account
     *
     * @see https://developer.kyckglobal.com/api/#/paths/~1apis~1singlePayeeUpdate/put
     * @return array
     */
    public function getKyckPayeeUpdateData() {
        return [
            "payeeId" => $this->payee->payee_id,
            "payeeFirstName" => $this->firstname,
            "payeeLastName" => $this->lastname,
            "contactInfo" => [
                "mobile" => substr($this->phone_number_base, -10),
                "sendSMS" => false
            ],
            "paymentTypes" => [
                "NCRpay360",
            ],
            "ncrPay360" => [
                "ncrPay360Allocation" => 100
            ],
            "ncrPay360Allocation" => 100
        ];

    }


}


?>
