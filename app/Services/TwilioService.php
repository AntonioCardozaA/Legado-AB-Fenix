<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Rest\Api\V2010\Account\MessageInstance;

class TwilioService
{
    protected Client $client;
    protected ?string $from;

    public function __construct()
    {
        /** @var string|null $sid */
        $sid = config('services.twilio.sid');
        /** @var string|null $token */
        $token = config('services.twilio.auth_token');

        $this->client = new Client(
            $sid,
            $token
        );

        /** @var string|null $from */
        $from = config('services.twilio.phone');
        $this->from = $from;
    }

    public function sendSMS(string $to, string $message): MessageInstance
    {
        return $this->client->messages->create($to, [
            'from' => $this->from,
            'body' => $message
        ]);
    }
}