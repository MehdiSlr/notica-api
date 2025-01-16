<?php

namespace App\Traits;



trait SendSMS
{
    /**
     * Send SMS to the user.
     *
     * @param string $phone
     * @return string
     */
    public function _sendCode($phone)
    {
        // if phone has 0 at start, remove it
        if (substr($phone, 0, 1) == '0') {
            $phoneNumber = substr($phone, 1);
        }

        $code = rand(10000, 99999);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.sms.ir/v1/send/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>
                '{
                "mobile": "' . $phoneNumber . '",
                "templateId": 612409,
                "parameters":
                    [
                        {
                            "name": "code",
                            "value": "' . $code . '"
                        }
                    ]
                }',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: text/plain', 'x-api-key: ' . config('services.sms.api_key')],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);

        return match ($response['status']) {
            1 => $code,
            default => false,
        };
    }

    public function _inviteUser($phone, $comapany)
    {
        // if phone has 0 at start, remove it
        if (substr($phone, 0, 1) == '0') {
            $phoneNumber = substr($phone, 1);
        }


        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.sms.ir/v1/send/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>
                '{
                "mobile": "' . $phoneNumber . '",
                "templateId": 642348,
                "parameters":
                    [
                        {
                            "name": "company",
                            "value": "' . $comapany . '"
                        },
                        {
                            "name": "phone",
                            "value": "0' . $phone . '"
                        }
                    ]
                }',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: text/plain', 'x-api-key: ' . config('services.sms.api_key')],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);

        return match ($response['status']) {
            1 => true,
            default => false,
        };
    }
}
