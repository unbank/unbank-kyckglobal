<?php

namespace Unbank\Kyckglobal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Osoobe\Utilities\Helpers\Utilities;
use Unbank\Kyckglobal\Events\API\KyckGetCashoutLocationAPIError;
use Unbank\Kyckglobal\Events\PayeeCreated;
use Unbank\Kyckglobal\Events\PayeeError;
use Unbank\Kyckglobal\Events\PayeeUpdated;

class KyckGlobalAPI
{

    protected $api_url;
    protected $auth_data;
    protected $password;
    protected $payer_id;
    protected $payer_name;
    protected $token = '';
    protected $username;

    public function __construct(
        string $username,
        string $password,
        string $api_url = "https://api.kyckglobal.com",
        string $payer_name = '',
        string $payer_id = '',
        $auth = true
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->api_url = $api_url;
        $this->payer_name = $payer_name;
        $this->payer_id = $payer_id;
        if ($auth) {
            $this->auth();
        }
    }


    /**
     * KyckGlobal Authentication
     *
     * @return boolean
     */
    public function auth()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("$this->api_url/apis/userAuth", [
            'email' => $this->username,
            'password' => $this->password
        ]);

        $this->auth_data = $response->json();
        try {
            if ( $this->auth_data['success'] ) {
                $this->token = $this->auth_data['token'];
            }
            $status = $this->auth_data['success'];
        } catch (\Throwable $th) {
            $status = false;
            logger($th->getMessage(), [
                "context" => "KyckGlobalAPI::auth",
                "message" => "Authorization failed for Kyck"
            ]);
        }
        return $status;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken(): string {
        if ( empty($this->token) ) {
            $this->auth();
        }
        return $this->token;
    }


    /**
     * Create Payee
     *
     * @param \App\Models\User $user
     * @return array   Return Payee object if use is registered, else false;
     */
    public function createPayee($user)
    {
        $payeeData = $user->getKyckRegistrationData();

        $payeeData["payerId"] = $this->payer_id;
        $payeeData["payerLegalName"] = $this->payer_name;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->post("$this->api_url/apis/singlePayeeCreatingAPI", $payeeData);

        $result = $response->json();


        if ( empty($result) ) {
            event(new PayeeError($user, "No result returned", $result));
            return [
                false,
                []
            ];
        }

        if ( empty($result['success']) || !empty($result['success']) && ! $result['success'] ) {
            event(new PayeeError($user, "Unable to create payee", $result));
            return [
                false,
                $result
            ];
        }

        $payee = Payee::updateOrCreate(
            ['user_id' => $user->id],
            [
                "payee_id" => $result["payeeId"],
                "data" => $result,
                "service_provider" => 'kyck',
                "is_active" => 1,
                "verified" => 1
            ]
        );
        event(new PayeeCreated($user, $payee));
        return [
            true,
            $payee
        ];
    }


    /**
     * Update Payee
     *
     * @see https://developer.kyckglobal.com/api/#/paths/~1apis~1singlePayeeUpdate/put
     *
     * @param \App\Models\User $user
     * @return object   Returns object with keys: status, data and response
     */
    public function updatePayee($user)
    {
        $payeeData = $user->getKyckPayeeUpdateData();

        $payeeData["payerId"] = $this->payer_id;
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->put("$this->api_url/apis/singlePayeeUpdate", $payeeData);

        $result = $response->json();
        if ( empty($result) ) {
            event(new PayeeError($user, "No result returned", $result));
            return (object) [
                'status' => false,
                'data' => [],
                'response' => $result
            ];
        }

        $result["payeeId"] = $user->payee->payee_id;
        if ($result['success'] != true) {
            event(new PayeeError($user, "Unable to update payee", $result));
            return (object) [
                'status' => false,
                "data" => [],
                'response' => $result
            ];
        }

        event(new PayeeUpdated($user, $result));
        $payee = Payee::updateOrCreate(
            ['user_id' => $user->id],
            [
                "service_provider" => 'kyck',
                "is_active" => 1,
                "verified" => 1
            ]
        );
        return (object) [
            'status' => true,
            'data' => $payee,
            'response' => $result
        ];
    }

    /**
     * Add Card to Payee Financial Account
     * AddCard to Payee Financial Accounts As Push-To-Card
     * @see https://developer.kyckglobal.com/api/#/paths/~1apis~1giftCard/post
     *
     * @param \App\Models\User $user
     * @param string $nameOnCard
     * @param string $cardNumber
     * @param string $year
     * @param string $month
     * @param string $cvv
     * @param string $street
     * @param string $city          Mandatory field for Mastercard: ex: Dallas
     * @param string $state         Mandatory field for Mastercard: ex: Dallas
     * @param string $postalCode
     * @param integer $allocation
     * @param bool $p2p             Optional, for Mastercard Only (Person-to-Person Payments): ex: false
     * @return mixed
     */
    public function addPushToCardDetails(
        $user,
        $nameOnCard,
        $cardNumber,
        $year,
        $month,
        $cvv,
        $street,
        $postalCode,
        $city=null,
        $state=null,
        $allocation=0
    ) {

        // Date of birth is required
        if ( empty($user->dob) ) {
            return (object) [
                'status' => false,
                'data' => [],
                'result' => [
                    "success" => false,
                    "msg" => "No date of birth was provided",
                    "reason" => ""
                ]
            ];
        }

        $data = [
            "payerId" => $this->payer_id,
            'payeeId' => $user->payee_id,
            "dateOfBirth" => $user->dob->format('Y-m-d'),
            "payeePushToCard" => [
                "nameOnCard" => $nameOnCard,
                "cardNumber" => $cardNumber,
                "expiryYear" => $year,
                "expiryMonth" => $month,
                "street" => $street,
                "postalCode" => $postalCode,
                "cvv" => $cvv,
                "allocation" => $allocation
            ]
        ];

        if ( !empty($city) ) {
            $data["payeePushToCard"]['city'] = $city;
        }
        if ( !empty($state) ) {
            $data["payeePushToCard"]['state'] = $state;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->post("$this->api_url/apis/addPushToCardDetails", $data);

        $result = $response->json();
        if ( empty($result) ) {
            return (object) [
                'status' => false,
                'data' => [],
                'result' => $result
            ];
        }

        return (object) [
            'status' => true,
            'data' => [],
            'result' => $result
        ];

    }

    /**
     * Push To Card Widget - Token Request
     * Make available a widget for Visa Push To Card functionality.
     *
     * @param string $unique_id
     * @param string $payee_email
     * @return mixed
     */
    public function pushToCardAuthToken(string $unique_id, string $payee_email) {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->post("$this->api_url/apis/pushToCardAuthToken", [
            "uniqueId" => $unique_id,
            "payeeEmail" => $payee_email
        ]);
        return $response->json();
    }


    /**
     * Update Payee Allocation
     *
     * @see https://developer.kyckglobal.com/api/#/paths/~1apis~1singlePayeeUpdate/put
     *
     * @param \App\Models\User $user
     * @return object   Keys: status, data, result
     */
    public function updateAllocation($user, string $method="ncrpay360", array $options=[])
    {
        $payeeData = $user->generateAllocationData($method, $options);
        $payeeData["payerId"] = $this->payer_id;
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->put("$this->api_url/apis/singlePayeeUpdate", $payeeData);

        $result = $response->json();
        if ( empty($result) ) {
            return (object) [
                'status' => false,
                'data' => [],
                'result' => $result
            ];
        }

        $result["payeeId"] = $user->payee->payee_id;
        if ($result['success'] != 'true') {
            return (object) [
                'status' => false,
                "data" => $result,
                'result' => $result
            ];
        }

        $payee = Payee::updateOrCreate(
            ['user_id' => $user->id],
            [
                "service_provider" => 'kyck',
                "is_active" => 1,
                "verified" => 1
            ]
        );
        return (object) [
            'status' => true,
            'data' => $payee,
            'result' => $result
        ];
    }


    /**
     * Update Payee Allocation
     *
     * @see https://developer.kyckglobal.com/api/#/paths/~1apis~1singlePayeeUpdate/put
     *
     * @param \App\Models\User $user
     * @return object   Keys: status, data, result
     */
    public function updateMultipleAllocation($user, array $allocationWithAccountIds = [] )
    {
        $payeeData = [
            "payeeId" => $user->payee_id,
            "payerId" => $this->payer_id,
            "allocationWithAccountId" => $allocationWithAccountIds
        ];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->put("$this->api_url/apis/singlePayeeUpdate", $payeeData);

        $result = $response->json();
        if ( empty($result) ) {
            return (object) [
                'status' => false,
                'data' => [],
                'result' => $result
            ];
        }

        $result["payeeId"] = $user->payee->payee_id;
        if ($result['success'] != 'true') {
            return (object) [
                'status' => false,
                "data" => $result,
                'result' => $result
            ];
        }

        $payee = Payee::updateOrCreate(
            ['user_id' => $user->id],
            [
                "service_provider" => 'kyck',
                "is_active" => 1,
                "verified" => 1
            ]
        );

        // Update locations
        foreach($allocationWithAccountIds as $account_id => $allocation) {
            $user->kyckAccounts()->accountId($account_id)->update([
                'allocation' => $allocation,
                'payee_id' => $user->payee_id
            ]);
        }

        return (object) [
            'status' => true,
            'data' => $payee,
            'result' => $result
        ];
    }


    /**
     * Get payee data
     *
     * @see https://developer.kyckglobal.com/api/#/paths/~1apis~1getPayeeById~1{{PayeeId}}/get
     *
     * @param Payee $payee
     * @return array
     */
    public function getPayee(Payee $payee) {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->get("$this->api_url/apis/getPayeeById/$payee->payee_id");
        return $response->json();
    }


    /**
     * Get payees
     *
     * @return array
     */
    public function getPayees()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->get("$this->api_url/apis/fetchPayeesOfPayer/$this->payer_id")
            ->json();
        if ( !empty($response['data']) ) {
            return Utilities::getArrayValue($response['data'], 'Items', []);
        }
        return [];
    }

    public function createAccountsForPayees(array $payees)
    {
        foreach ($payees as $payee) {
            $payee_email = $payee["payeeEmail"];
            $payee_name = $payee["payeeName"];
        }
    }

    /**
     * Get Cash Out ATM Locations within the given radius in miles based on the given cordinates
     *
     * @example KyckGlobal::getZipCodeCashOutATMLocations("11530", 25)
     *
     * @deprecated 1.1.0
     * @param string $zipCode
     * @param integer $distance
     * @return void
     */
    public function getZipCodeCashOutATMLocations(string $zipCode, int $distance = 25)
    {
        return $this->getCashOutATMLocationsByZipCode($zipCode, $distance);
    }



    /**
     * Get Cash Out ATM Locations within the given radius in miles based on the given cordinates
     *
     * @example KyckGlobal::getCashOutATMLocationsByZipCode("11530", 25)
     *
     * @param string $zipCode
     * @param integer $distance
     * @return void
     */
    public function getCashOutATMLocationsByZipCode(string $zipCode, int $distance = 25, int $limit = 30)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "$this->api_url/apis/GetCashOutAtmLocations",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "postalCode" : "'.$zipCode.'",
            "records": "'.$limit.'",
            "dblDistance": ' . $distance . '
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->token,
                'Content-Type: application/json',
                // 'Cookie: AWSALB=XBAdDXPm5iCFwDLIlkNu5kCvnf3t0j84R29IB8zO0xi/bS4DnGyqX/KMK7Bo7Scjxixkdw+dbKREgvS7hkdDrLxiyNFzcimZYw+tRSYGe/hZrTZ43W/7NPA993/b; AWSALBCORS=XBAdDXPm5iCFwDLIlkNu5kCvnf3t0j84R29IB8zO0xi/bS4DnGyqX/KMK7Bo7Scjxixkdw+dbKREgvS7hkdDrLxiyNFzcimZYw+tRSYGe/hZrTZ43W/7NPA993/b'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $data = json_decode($response, true);
        if ( !empty($data['status']) && is_string($data['data']) ) {
            event(new KyckGetCashoutLocationAPIError(
                [
                    "method" => "POST",
                    "payload" => [
                        "postalCode" => $zipCode,
                        "dblDistance" => $distance,
                        "records" => $limit
                    ]
                ],
                $response
            ));
        }

        return $data;
    }


    /**
     * Get Cash Out ATM Locations within the given radius in miles based on the given coordinates
     *
     * @example KyckGlobal::getCashOutATMLocations(28.66207, -81.381724, 25)
     *
     * @deprecated 1.1.0
     * @param float $latitude
     * @param float $longitude
     * @param integer $distance
     * @return void
     */
    public function getCashOutATMLocations(float $latitude, float $longitude, int $distance = 25)
    {
        return $this->getCashOutATMLocationsByCoords($latitude, $longitude, $distance);
    }

    /**
     * Get Cash Out ATM Locations within the given radius in miles based on the given coordinates
     *
     * @example KyckGlobal::getCashOutATMLocationsByCoords(28.66207, -81.381724, 25)
     *
     * @param float $latitude
     * @param float $longitude
     * @param integer $distance
     * @return void
     */
    public function getCashOutATMLocationsByCoords(float $latitude, float $longitude, int $distance = 25)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "$this->api_url/apis/GetCashOutAtmLocations",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "lattitude" : ' . $latitude . ',
            "longitude" : ' . $longitude . ',
            "records": 30,
            "dblDistance": ' . $distance . '
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->token,
                'Content-Type: application/json',
                // 'Cookie: AWSALB=XBAdDXPm5iCFwDLIlkNu5kCvnf3t0j84R29IB8zO0xi/bS4DnGyqX/KMK7Bo7Scjxixkdw+dbKREgvS7hkdDrLxiyNFzcimZYw+tRSYGe/hZrTZ43W/7NPA993/b; AWSALBCORS=XBAdDXPm5iCFwDLIlkNu5kCvnf3t0j84R29IB8zO0xi/bS4DnGyqX/KMK7Bo7Scjxixkdw+dbKREgvS7hkdDrLxiyNFzcimZYw+tRSYGe/hZrTZ43W/7NPA993/b'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $data = json_decode($response, true);
        return $data;
    }

    public function sendPostRequest($path, $data, $method)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "$this->api_url/$path",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: ' . $this->token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $data = json_decode($response, true);
        return $data;
    }

    public function sendGetRequest($path) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "$this->api_url/$path",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $this->token
        ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response, true);
        return $data;
    }


    /**
     * Payments Create
     *
     * The Create Payments operation initiates one or more payments to the payees defined in the request body.
     * The request body must include one or more payeeDetails records that specify the Payee record,
     * the Payee's TID, and the payment date and amount.
     * This operation can submit payments for processing on multiple dates.
     *
     * @see https://developer.kyckglobal.com/api/#/paths/~1apis~1bulkPaymentByJSON/post
     *
     * @param array $payemnts
     * @return mixed
     */
    public function makePayments(array $payemnts) {
        $data = [
            'payerId' => $this->payer_id,
            'payments' => $payemnts
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->post("$this->api_url/apis/bulkPaymentByJSON", $data);
        return $response->json();
    }


    /**
     * Get Payments - Get by Payer Id
     *
     * The Get All Payments by Payer ID operation returns a list of all of the payments ever made by a
     * specified Payer. Send a GET request to the /getAllPayments endpoint with the Organization ID
     * as the last element in the URL, as shown in the example below:
     *
     * https://api.kyckglobal.com/apis/getAllPayments/{{organizationId}}
     *
     * @return array                    Returns the json response for the KyckGlobal API Endpoint.
     */
    public function getPayerPayments(): array {
            $data = $this->sendGetRequest("apis/getAllPayments/$this->payer_id");
            return $data;
    }


    /**
     * Get Payments - Get Pay Statement
     *
     * The Get Pay Statement Information operation returns pay stub data for the specified payment.
     * Send a GET request to the /getPayStub endpoint with the payment's reference ID as the last
     * element in the URL, as shown in the example below:
     *
     * https://api.kyckglobal.com/apis/getPayStub/{{paymentReferenceId}}
     *
     * @param string $reference_id
     * @return mixed                    Returns the json response for the KyckGlobal API Endpoint.
     */
    public function getPaymentStatement(string $reference_id): array {
        $response = Http::withHeaders([
            'Authorization' => $this->token
        ])->get("$this->api_url/apis/getPayStub/$reference_id");
        return $response->json();
    }

    /**
     * Cancel Payments - Get Pay Statement
     *
     * The Cancel Payment by Reference ID operation cancels the payment done by a specified Payer.
     *
     * https://api.kyckglobal.com/apis/cancelPayment/{{paymentReferenceId}}
     *
     * @param string $reference_id
     * @return mixed                    Returns the json response for the KyckGlobal API Endpoint.
     */
    public function cancelPayment($reference_id) {

        $response = Http::withHeaders([
            'Authorization' => $this->token
        ])->get("$this->api_url/apis/cancelPayment/$reference_id");

        return $response->json();
    }

    /**
     * Repprocess/Reject Payment
     *
     * https://api.kyckglobal.com/apis/processOrRejectTransactions
     *
     * @param string $reference_id
     * @return mixed                    Returns the json response for the KyckGlobal API Endpoint.
     */
    public function reprocessPayment(string $reference_id, string $action, ?string $account_last_4_digits = null, ?int $payee_disbursement_account_id = null
    ) {
        
        $data = [
            'action' => $action,
            'ReferenceId' => $reference_id,
        ];

        if ($account_last_4_digits !== null) {
            $data['accountNumber'] = $account_last_4_digits;
        }

        if ($payee_disbursement_account_id !== null) {
            $data['payeeDisbursementAccountId'] = $payee_disbursement_account_id;
        }

        $response = Http::withHeaders([
            'Authorization' => $this->token
        ])->post("$this->api_url/apis/processOrRejectTransactions", $data);
        
        return $response->json();
    }
    


    /**
     * Payee TIN Check
     * Validate a user's Taxpayer Identification Number.
     *
     * @param string $tax_id
     * @param string $first_name
     * @param string $last_name
     * @return mixed
     */
    public function tinCheck(string $tax_id, string $first_name, string $last_name) {

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->post("$this->api_url/apis/tinvalidate", [
            "ptaxid" => $tax_id,
            "FirstName" => $first_name,
            "LastName" => $last_name
        ]);

        return $response->json();
    }


    public function getBankDetailsByRoutingCode(string $routing_code) {
        $payload = [
            "data" => [
                "routingCode" => $routing_code
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $this->token
        ])->post("$this->api_url/apis/getBankDetailsByRoutingCode", $payload);
        return $response->json();
    }


}
