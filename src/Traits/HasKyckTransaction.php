<?php

namespace Unbank\Kyckglobal\Traits;

use Illuminate\Support\Facades\Log;
use Osoobe\Utilities\Helpers\Str;

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
            'PayerPaymentReferenceNum' => $this->transaction_id,
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

    public function updateFromKyckStatement(array $data, bool $save=true) {
        if ( $data['success'] && !empty($data['payStub'])) {
            $this->data = $data;
            $this->status = $data['payStub']['status'];
            $this->ach_type = $data['payStub']['achType'];
            // $this->status = $data['payStub']['status'];
            $this->payment_method = $data['payStub']['payeePaymentMethod'];

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
            $this->status = "sent";

            $this->ach_type = $data['accept'][0]['achType'];
            $this->service_ref_no = $data['accept'][0]["paymentDetails"][0]["Reference_ID"];
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
