<?php

namespace App\Http\Middleware;

use App\Enums\ElementType;
use App\Models\Element;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveDonationElement
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('element');

        $element = Element::query()
            ->where('is_active', true)
            ->where('type', ElementType::Form)
            ->where(fn ($q) => $q->where('token', $token)->orWhere('form_slug', $token))
            ->first();

        if (! $element) {
            abort(404);
        }

        $request->route()->setParameter('element', $element);

        return $next($request);
    }
}
