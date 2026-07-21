<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ __('Donate via :name', ['name' => $organization?->name ?? config('app.name')]) }}</title>
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #eef1f6;
                color: #475569;
            }
            .loader {
                text-align: center;
                max-width: 320px;
            }
            .spinner {
                width: 40px;
                height: 40px;
                border: 3px solid #e2e8f0;
                border-top-color: #0d9488;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 16px;
            }
            @keyframes spin { to { transform: rotate(360deg); } }
            .fallback {
                margin-top: 24px;
                font-size: 14px;
            }
            .fallback a {
                color: #0d9488;
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="loader">
            <div class="spinner"></div>
            <p>{{ __('Loading secure donation form…') }}</p>

            <div class="fallback">
                <a href="{{ route('donations.show', ['element' => $element->token, 'popup' => 1]) }}">
                    {{ __('Tap here if the form does not open') }}
                </a>
            </div>
        </div>

        <script
            src="{{ \App\Support\EmbedWidget::scriptUrl() }}"
            data-token="{{ $element->token }}"
            data-auto-trigger="true"
        ></script>
    </body>
</html>
