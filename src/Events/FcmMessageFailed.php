<?php

namespace Nylo\LaravelFCM\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FcmMessageFailed
{
    use Dispatchable;

    public function __construct(public string $token, public string $errorMessage) {}
}
