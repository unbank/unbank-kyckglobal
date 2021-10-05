<?php

namespace Unbank\Kyckglobal\Traits;

use Osoobe\Utilities\Helpers\Str;
use Osoobe\Utilities\Helpers\Utilities;

trait KyckPayeeTrait {


    /**
     * Get all of the payees for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('Unbank\Kyckglobal\Payee', 'user_id', 'id');
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
                'pNumber' => $this->phone_number,
                'pAddress' => Utilities::getObjectValue($this->main_address, 'street_address', ''),
                'pcity' => Utilities::getObjectValue($this->main_address, 'city', ''),
                'pstate' => Utilities::getObjectValue($this->main_address, 'state', ''),
                'pcountry' => Utilities::getObjectValue($this->main_address, 'country', '')
            ),
            'contactInfo' => array (
                'mobile' => $this->phone_number,
                'sendSMS' => true,
            ),
            'userDisabled' => false,
            'payeeStatus' => ( $this->kyckPayee )?  $this->kyckPayee->status : false,
            'paymentTypes' =>array (
                0 => 'ATMPass',
            ),
            'atmPass' => array (
                'atmPassAllocation' => 100,
            )
        ];

        return $postData;
    }


}


?>
