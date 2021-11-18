<?php

namespace Unbank\Kyckglobal\Traits;

use App\Events\PickupReady;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Osoobe\Utilities\Helpers\Str;
use Unbank\Kyckglobal\Facades\KyckGlobal;

trait HasKyckTransaction {

    public function scopeKyck($query) {
        return $query->where('service_provider', 'kyck');
    }

    public function scopeNotRejected($query) {
        return $query->where('status', '!=', 'Rejected');
    }

    public function scopeNotReturned($query) {
        return $query->where('status', '!=', 'Returned');
    }

    public function scopePickupReady($query) {
        return $query->where('status', 'Pickup Ready');
    }

    public function getExpiryDateAttribute() {
        return $this->transfer_date->copy()->addDays(
            config('kyckglobal.expires_in_days', 3)
        );
    }

    public function getExpiryJsDateAttribute() {
        return $this->expiry_date->format('m-d-Y')." 23:59:59";
    }

    public function getTransferJsDateAttribute() {
        return $this->transfer_date->format('m-d-Y')." 23:59:59";
    }

    public function pickupExpirationHours() {
        return $this->transfer_date->diffInHours($this->expiry_date);
    }

    public function expiresInHours() {
        return Carbon::now()->diffInHours($this->expiry_date);
    }

    public function expiration_progress() {
        $diff_start = $this->pickupExpirationHours();
        $diff_now = $this->expiresInHours();
        return (int) ( ( ($diff_start - $diff_now) / $diff_start ) * 100 );
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
                // return $message." ".route(
                //     'transaction.pickup'
                // );
                // return "Hello"
                return "$message https://demo.unbanked.world/pickupready";
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
            config("kyckglobal.statuses.$index")
        );
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

    public function triggerKyckStatusEvent($status) {
        $transaction = $this;
        if ( $this->status == $status || $this->isRejected() ) {
            return true;
        } elseif ( $this->isProccessed() && self::checkKyckStatus($status, 'accepted') ) {
           // Send Pickup Ready Notification
            event(new PickupReady($transaction));
            $this->status = $status;
            return true;
        } elseif ( $this->isPickupReady() && self::checkKyckStatus($status, 'completed') ) {
            // Send Transaction Compeleted Notification
            event(new PickupReady($transaction));
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
                'effectivePaymentDate' => $this->transfer_date->format('m/d/y'),
                'amount' => $this->amount,
            ),
            'PayerPaymentReferenceNum' => $this->order_id,
            // 'preferredMethod' => $this->payment_method,
            // 'PickupCountry' => $pickupCountry,
            // 'PickupState' => $pickupState,
            'isConvenienceFeeDisabled' => false,
        ];

        // try {
        //     $data['PickupCountry'] = $this->location->country;
        //     $data['PickupState'] = $this->location->state;
        // } catch (\Throwable $th) {
        //     Log::warning("No location was found");
        // }
        return $data;
    }

    public static function generateTransactionNumber() {
        return "UNBT".Str::random(3)."0".static::count();
    }

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

    public function updateFromKyckStatement(array $data, bool $save=true) {
        if ( $data['success'] && !empty($data['payStub'])) {
            $this->data = $data;
            $status = $data['payStub']['status'];
            $this->triggerKyckStatusEvent($status);
            $this->status = $data['payStub']['status'];
            $this->ach_type = $data['payStub']['achType'];
            $this->payment_method = $data['payStub']['payeePaymentMethod'];

            try {
                $this->pickup_cash_code = $data['payStub']["responseData"]["strAuthorizationCode"];
            } catch (\Throwable $th) {
                //throw $th;
            }

            if ( $save ) {
                $this->save();
            }

            return true;
        }
        return false;
    }

    public function updateFromKyckResponse($data, $save=true) {
        $this->data = $data;
        if ( $data['success'] && !empty($data['accept'])) {
            if ( ! $this->hasValidStatus() ) {
                $this->status = "sent";
            }

            $this->ach_type = $data['accept'][0]['achType'];
            $this->kyck_reference_Id = $data['accept'][0]["paymentDetails"][0]["Reference_ID"];
            $this->payment_method = $data['accept'][0]["paymentDetails"][0]["payeePaymentMethod"];
        }

        if ( $save ) {
            $this->save();
        }
    }

    public function cancelPayment($save=false) {
        $this->status = "Rejected";
        $this->is_active = 0;
        if ( $save ) {
            $this->save();
        }
    }


}

?>
