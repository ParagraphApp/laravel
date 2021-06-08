<?php

namespace Pushkin;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;

class Mailer extends Transport {
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $lastCall = array_first(debug_backtrace(), function($call) {
            return data_get($call, 'class')  == 'Illuminate\Mail\Mailer' && data_get($call, 'function') == 'send';
        });

        $reflection = new \ReflectionFunction($lastCall['args'][2]);
        $context = get_class($reflection->getClosureThis());

        resolve(Client::class)->submitPage($message->getBody(), $context, Client::PAGE_TYPE_EMAIL);
    }
}