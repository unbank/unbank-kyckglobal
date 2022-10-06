<?php

namespace Unbank\Kyckglobal\Traits;

use Osoobe\Utilities\Helpers\Str;
use Osoobe\Utilities\Helpers\Utilities;
use Unbank\Kyckglobal\Events\PayeeCreated;
use Unbank\Kyckglobal\Events\PayeeNotFound;
use Unbank\Kyckglobal\Facades\KyckGlobal;
use Unbank\Kyckglobal\Payee;
use Unbank\Kyckglobal\PayPalAccount;
use Unbank\Kyckglobal\VenmoAccount;

/**
 * Kyck Payee Trait
 *
 * @property \Unbank\Kyckglobal\Payee $payee               Payee Model object
 * @property-read mixed $payee_id       Kyck Payee id
 */
trait KyckPayeeTrait {

    /**
     * Get the payee that owns the KyckPayeeTrait
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function payee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne('\Unbank\Kyckglobal\Payee');
    }

    /**
     * Get all of the Paypal Email for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function paypalAccount()
    {
        return $this->hasOne(PayPalAccount::class, 'user_id');
    }

    /**
     * Get all of the Paypal Email for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function venmoAccount()
    {
        return $this->hasOne(VenmoAccount::class, 'user_id');
    }

    /**
     * Get or create payee object
     *
     * @return mixed
     */
    public function getOrCreatePayee() {
        if ( empty($this->payee) ) {
            if ( empty($this->email) ) {
                return null;
            }

            $user = $this;
            $payee = Payee::where('user_id', $user->id)
                ->orWhere('email', $user->email)
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
                event(new PayeeNotFound($user, $payee));
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


    /**
     * Get payee id
     *
     * @return mixed    Returns Payee ID from the payee object if not null
     */
    public function getPayeeIdAttribute() {
        if ( !empty($this->payee) ) {
            return $this->payee->payee_id;
        }
        $payee = $this->getOrCreatePayee();
        if ( !empty($payee) ) {
            return $payee->payee_id;
        }
        return null;
    }


    /**
     * Get kyck payees
     *
     * @return mixed
     */
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

    /**
     * Get main address
     *
     * @return mixed
     */
    public function main_address() {
        return $this->belongsTo('\Unbank\Kyckglobal\PayeeAddress', 'address_id');
    }

    /**
     * Get Kyck registration data for Payee
     *
     * @return array
     */
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
     * Generate Allocation data
     *
     * @param Payee $payee
     * @param string $method
     * @return array
     */
    public function generateAllocationData($method='ncrpay360') {
        $payee = $this->getOrCreatePayee();
        $has_paypal = !empty($this->paypalAccount );
        $has_venmo = !empty($this->venmoAccount );

        $paypalAllocation = 0;
        $venmoAllocation = 0;
        $ncrpay360Allocation = 0;

        switch (strtolower($method)) {
            case 'ncrpay360':
                $ncrpay360Allocation = 100;
                break;

            case 'paypal':
                if ( $has_paypal ) {
                    $paypalAllocation = 100;
                } else {
                    $ncrpay360Allocation = 100;
                }
                break;

            case 'venmo':
                if ( $has_venmo ) {
                    $venmoAllocation = 100;
                } else {
                    $ncrpay360Allocation = 100;
                }
                break;

            default:
                $ncrpay360Allocation = 100;
                break;
        }


        $data = [
            "payeeId" => $payee->payee_id,
            "contactInfo" => [
                "mobile" => [
                    "contactCode" => "+1",
                    "contactNumber" => substr($this->phone_number_base, -10)
                ],
                "sendSMS" => false
            ],
            "paymentTypes" => [
                "NCRpay360",
            ],
            "ncrPay360" => [
                "ncrPay360Allocation" => $ncrpay360Allocation
            ]
        ];

        // Paypal account info
        try {
            if ( $has_paypal ) {
                $data['paymentTypes'][] = "paypal";
                $data["payeePaypalFinancialAccounts"] = [
                    "paypalAllocation" => $paypalAllocation,
                    "paypalEmail" => $this->paypalAccount->email,
                    "paypalcurrency" => $this->paypalAccount->currency
                ];
            }

            // Venmo account info
            if ( $has_venmo ) {
                $data['paymentTypes'][] = "venmo";
                $data["payeeVenmoAccount"] = [
                    "PhoneNmber" => $this->venmoAccount->phone_number_base,
                    "venmocurrency" => $this->venmoAccount->currency
                ];
                $data["venmo"] = true;
                $data["venmoAllocation"] = $venmoAllocation;
            }
        } catch (\Throwable $th) {
            logger($th->getMessage(), [
                'context' => "KyckPayeeTrait::getKyckPayeeUpdateData"
            ]);
        }
        return $data;
    }


    /**
     * Get data that is used to update the payee account
     *
     * @see https://developer.kyckglobal.com/api/#/paths/~1apis~1singlePayeeUpdate/put
     * @return array
     */
    public function getKyckPayeeUpdateData() {
        $payee = $this->getOrCreatePayee();
        $data = [
            "payeeId" => $payee->payee_id,
            "payeeFirstName" => $this->firstname,
            "payeeLastName" => $this->lastname,
            "contactInfo" => [
                "mobile" => [
                    "contactCode" => "+1",
                    "contactNumber" => substr($this->phone_number_base, -10)
                ],
                "sendSMS" => false
            ]
        ];
        return array_merge(
            $data,
            $this->generateAllocationData($payee, 'ncrpay360')
        );
    }


}


?>
