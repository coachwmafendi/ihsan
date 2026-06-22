<?php

namespace App\Mail;

use App\Jobs\Middleware\ThrottleMailtrapMiddleware;
use Illuminate\Mail\Mailable as BaseMailable;

abstract class Mailable extends BaseMailable
{
    public function middleware(): array
    {
        return [new ThrottleMailtrapMiddleware];
    }
}
