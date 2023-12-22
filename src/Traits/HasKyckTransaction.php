<?php

namespace Unbank\Kyckglobal\Traits;

use Carbon\Carbon;
use Osoobe\Laravel\Settings\Models\AppMeta;
use Osoobe\Utilities\Helpers\Str;
use Unbank\Kyckglobal\Events\KyckTransactionCompleted;
use Unbank\Kyckglobal\Events\KyckTransactionError;
use Unbank\Kyckglobal\Events\KyckTransactionRejected;
use Unbank\Kyckglobal\Events\PickupReady;
use Unbank\Kyckglobal\Facades\KyckGlobal;

trait HasKyckTransaction {

    /**
     * Boot function whenever a change is made to the model.
     *
     * @return void
     */
    public static function bootHasKyckTransaction()
    {
        $expiry_trigger = ['kyck'];
        $expire_in_hours = config('kyckglobal.expire_in_hours', 72);
        static::creating(function($item) use($expiry_trigger, $expire_in_hours) {
            if ( !empty($item->transfer_date) ) {
                if ( in_array($item->service_provider, $expiry_trigger) ) {
                    $item->expiry_date = $item->transfer_date->copy()
                        ->addHours($expire_in_hours);
                }
            }
        });

        static::updating(function($item) use($expire_in_hours) {
            if ( $item->status == 'Pickup Ready' && $item->getOriginal('status') != 'Pickup Ready' ) {
                $item->expiry_date = now()->addHours($expire_in_hours);
            }
        });
    }

    /**
     * Scope a query to only include kyck transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKyck($query) {
        return $query->where('service_provider', 'kyck');
    }

    /**
     * Scope a query to only include kyck transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeServiceMethod($query, $method) {
        return $query->where('service_method', $method);
    }

    /**
     * Scope a query to only exclude rejected transactions
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param array $statuses
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotRejected($query, array $statuses = ['Rejected']) {
        return $query->whereNotIn('status', $statuses);
    }

    /**
     * Scope a query to only exclude rejected transactions
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param string $kyck_reference_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKyckReference($query, $kyck_reference_id) {
        return $query->where('kyck_reference_id', $kyck_reference_id);
    }


    /**
     * Scope a query to only exclude transactions returned by Kyck
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotReturned($query) {
        return $query->where('status', '!=', 'Returned');
    }

    /**
     * Scope a query to only include transactions that are Ready to be picked up
     * at a Kyck Location
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePickupReady($query) {
        return $query->where('status', 'Pickup Ready');
    }


    /**
     * Scope a query to only include transactions that have been processed by Kyck
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSentToKyck($query) {
        return $query->kyck()
            ->whereIn('status', [
                'Pickup Ready', 'sent', 'Submitted', 'Success', 'Returned',
                'Proccessing', 'Processing'
            ]);
    }

    /**
     * Get expiry date timestamp for JavaScript
     *
     * @example $transaction->expiry_js_date
     * @return string
     */
    public function getExpiryJsDateAttribute() {
        return $this->expiry_date->format('m-d-Y')." 23:59:59";
    }

    /**
     * Get transfer date timestamp for JavaScript
     *
     * @example $transaction->transfer_js_date
     * @return string
     */
    public function getTransferJsDateAttribute() {
        return $this->transfer_date->format('m-d-Y')." 23:59:59";
    }

    /**
     * Get the difference between the transfer and expiry dates in hours
     *
     * @return mixed
     */
    public function pickupExpirationHours() {
        try {
            return $this->transfer_date->diffInHours($this->expiry_date);
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * Get the expiration progress
     *
     * @return int
     */
    public function expiration_progress() {
        $diff_start = $this->pickupExpirationHours();
        $diff_now = $this->expiresInHours();
        return intval( ( ($diff_start - $diff_now) / $diff_start ) * 100 );
    }

    /**
     * Get status message for kyck.
     *
     * @return string
     */
    public function getKyckStatusMessageAttribute(): string {

        $status_slug = Str::slug($this->status);
        try {
            $message = __('twilio.kyck_statuses.'.$status_slug);
            if ( !empty($this->kyck_reference_id) ) {
                return "$message ".config('kyckglobal.pickup_ready');
            }
            return $message;

        } catch (\Throwable $th) {
            return '';
        }
    }


    /**
     * Compare the transaction status with the list of statuses based on the index given.
     *
     * @see https://github.com/unbank/unbankwebapp/blob/master/config/kyckglobal.php for the list of statuses.
     *
     * @param string $index
     * @return bool
     */
    protected static function checkKyckStatus($status, $index): bool {
        return in_array(
            $status,
            config("kyckglobal.statuses.$index", [])
        );
    }

    /**
     * Check if the transaction is a NCRPay360 transaction
     *
     * @return boolean
     */
    public function isNcrPay360(): bool {
        return strtolower($this->service_method) == 'ncrpay360';
    }

    /**
     * Check if the transaction is a PayPal transaction
     *
     * @return boolean
     */
    public function isPayPal(): bool {
        return strtolower($this->service_method) == 'paypal';
    }

    /**
     * Check if the transaction has been accepted and ready for pickup.
     *
     * @return boolean
     */
    public function isPickupReady(): bool {
        return self::checkKyckStatus(
            $this->status,
            'accepted'
        );
    }

    public function isCancellable(): bool {
        return self::checkKyckStatus(
            $this->status,
            'cancellable'
        );
    }

    /**
     * Check if the transaction is a Venmo transaction
     *
     * @return boolean
     */
    public function isVenmo(): bool {
        return strtolower($this->service_method) == 'venmo';
    }

    /**
     * Trigger kyck status event
     *
     * @param string $status
     * @return bool
     */
    public function triggerKyckStatusEvent($status) {
        $transaction = $this;
        if ( $this->status == $status && $this->isRejected() ) {
            // Trigger Kyck Transaction Reject Event
            event(new KyckTransactionRejected($transaction));
            return true;
        } elseif ( $this->isProccessed() && self::checkKyckStatus($status, 'accepted') ) {
           // Send Pickup Ready Notification
            event(new PickupReady($transaction));
            $this->status = $status;
            return true;
        } elseif ( $this->isPickupReady() && self::checkKyckStatus($status, 'completed') ) {
            // Send Transaction Compeleted Notification
            event(new KyckTransactionCompleted($transaction));
            $this->status = $status;
            return true;
        } elseif ( $this->status != "Success" && self::checkKyckStatus($status, 'completed') ) {
            // Send Transaction Compeleted Notification
            event(new KyckTransactionCompleted($transaction));
            $this->status = $status;
            return true;
        }
        return false;
    }

    /**
     * Check if the transaction has been cancelled
     *
     * @return boolean
     */
    public function isRejected(): bool {
        return self::checkKyckStatus(
            $this->status,
            'cancelled');
    }


    /**
     * Check if the transaction has been proccesed
     *
     * @return boolean
     */
    public function isProccessed(): bool {
        return self::checkKyckStatus(
            $this->status,
            'proccessed');
    }

    /**
     * Check if the transaction has been completed and the payee has picked up the cash.
     *
     * @return boolean
     */
    public function isCompleted(): bool {
        return self::checkKyckStatus(
            $this->status,
            'completed');
    }

    /**
     * Check if the transaction status is valid.
     *
     * @return boolean
     */
    public function hasValidStatus(): bool {
        return (
            ! empty($this->status) && (
                $this->isCompleted() ||
                $this->isProccessed() ||
                $this->isRejected() ||
                $this->isPickupReady()
            )
        );
    }

    /**
     * Create kyck payment object to be sent to the kyck server.
     *
     * @return array
     */
    public function kyck_payment() {
        $data = [
            'payeeDetails' => array(
                'payeeId' => $this->user->kyck()->payee_id,
                'email' => $this->user->email,
                'payeeName' => $this->user->name,
            ),
            'paymentReason' => $this->reason,
            // 'TID' => '421847434',
            'paymentData' => array(
                'effectivePaymentDate' => $this->transfer_date->format('m/d/Y'),
                'amount' => $this->amount,
            ),
            'PayerPaymentReferenceNum' => $this->order_id,
            // 'preferredMethod' => $this->payment_method,
            // 'PickupCountry' => $pickupCountry,
            // 'PickupState' => $pickupState,
            'isConvenienceFeeDisabled' => false
        ];

        // try {
        //     $data['PickupCountry'] = $this->location->country;
        //     $data['PickupState'] = $this->location->state;
        // } catch (\Throwable $th) {
        //     Log::warning("No location was found");
        // }
        return $data;
    }

    /**
     * Generate transaction number
     *
     * @return string
     */
    public static function generateTransactionNumber() {
        return "UNBTRNS".strtoupper(Str::random(3))."0".static::count();
    }

    /**
     * Get cash code from Kyck
     *
     * @return string
     */
    public function getCashCode() {
        if ( !empty($this->pickup_cash_code)) {
            return $this->pickup_cash_code;
        }
        try {
            $this->pickup_cash_code = $this->data['payStub']["responseData"]["strAuthorizationCode"];
            $this->save();
            return $this->pickup_cash_code;
        } catch (\Throwable $th) {
            $data = KyckGlobal::getPaymentStatement($this->service_ref_no);
            $this->updateFromKyckStatement($data);
            return $this->pickup_cash_code;
        }
    }

    /**
     * Update the transaction data from Kyck statement response
     *
     * @param array $data
     * @param boolean $save
     * @return bool
     */
    public function updateFromKyckStatement(array $data, bool $save=true) {
        if ( $data['success'] && !empty($data['payStub'])) {
            $this->data = $data;
            $status = $data['payStub']['status'];
            if ( empty($status) ) {
                $this->status = "Error";
                event(new KyckTransactionError(
                    $this,
                    "Kyck Error: ".$data['payStub']["pendingReason"],
                    $data
                ));
            } else {
                $this->triggerKyckStatusEvent($status);
                $this->status = $status;
                $this->ach_type = $data['payStub']['achType'];
                // $this->payment_method = $data['payStub']['payeePaymentMethod'];
            }

            if ( $this->isNcrPay360() ) {
                try {
                    $this->pickup_cash_code = $data['payStub']["responseData"]["strAuthorizationCode"];
                } catch (\Throwable $th) {
                    logger("Pickup cash code error", [
                        "context" => 'Transaction:'.$this->id,
                        'message' => $th->getMessage(),
                        'file' => "HasKyckTransaction"
                    ]);
                }
            }

            if ( $save ) {
                $this->save();
            }

            return true;
        }
        return false;
    }

    /**
     * Update the transaction data from Kyck response
     *
     * @param array $data
     * @param boolean $save
     * @return bool
     */
    public function updateFromKyckResponse($data, $save=true) {
        $this->data = $data;
        if ( $data['success'] && !empty($data['accept'])) {
            if ( ! $this->hasValidStatus() ) {
                $this->status = "sent";
            }
            $this->kyck_reference_id = $data['accept'][0]["paymentDetails"][0]["Reference_ID"];
        }
        if ( $save ) {
            $this->save();
        }
    }

    /**
     * Mark transaction as canceled
     *
     * @param boolean $save
     * @return void
     */
    public function cancelPayment($save=false) {
        $this->status = "Rejected";
        $this->is_active = 0;
        if ( $save ) {
            $this->save();
        }
    }


    /**
     * Get the next available transfer date
     *
     * @param Carbon $date
     * @return Carbon
     */
    public static function nextTransferDate(Carbon $date=null) {
        if ( empty($date) ) {
            $date = Carbon::now();
        }

        $transfer_date = $date->copy();
        if ( in_array($transfer_date->shortEnglishDayOfWeek, ['Thur', 'Fri', 'Sat', 'Sun']) ) {
            $transfer_date->addDays(
                AppMeta::getMetaOrConfig('services.kyck.num_days_to_transfer', 2) + 2
            );
        } else {
            $transfer_date = $transfer_date->addDays(
                AppMeta::getMetaOrConfig('services.kyck.num_days_to_transfer', 2)
            );
        }

        $isNextDay = false;
        try {
            if ( ! $transfer_date->isOpen() ) {
                $$transfer_date = $transfer_date->nextOpen();
                $isNextDay = true;
            }
        } catch (\Throwable $th) {
            $isNextDay = false;
        }

        if ( $isNextDay ) {
            $transfer_date->hour = 11;
        }
        return $transfer_date;
    }


}

?>
