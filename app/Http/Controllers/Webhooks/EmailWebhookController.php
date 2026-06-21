<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Services\EmailWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailWebhookController
{
    public function __construct(
        private EmailWebhookService $service,
    ) {}

    public function mailgun(Request $request): Response
    {
        $this->service->processMailgun($request);

        return response('OK', 200);
    }

    public function postmark(Request $request): Response
    {
        $this->service->processPostmark($request);

        return response('OK', 200);
    }

    public function ses(Request $request, string $token): Response
    {
        $this->service->processSes($request, $token);

        return response('OK', 200);
    }
}
