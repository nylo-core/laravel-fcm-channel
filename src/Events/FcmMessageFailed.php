<?php

namespace Nylo\LaravelFCM\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class FcmMessageFailed
{
    use Dispatchable;

    public string $token;
    
    public string $errorMessage;

    public function __construct(string $token, string $errorMessage)
    {
        $this->token = $token;
        $this->errorMessage = $errorMessage;
    }
}
