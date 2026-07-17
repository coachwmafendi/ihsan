<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found - ihsan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <style>
        :root {
            --teal-50: #f0fdfa;
            --teal-100: #ccfbf1;
            --teal-500: #14b8a6;
            --teal-600: #0d9488;
            --teal-700: #0f766e;
            --slate-50: #f8fafc;
            --slate-200: #e2e8f0;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-900: #0f172a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background-color: var(--slate-50);
            color: var(--slate-900);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            position: relative;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Soft teal glow behind the composition */
        .glow {
            position: absolute;
            width: 640px;
            height: 640px;
            border-radius: 9999px;
            background: radial-gradient(closest-side, var(--teal-100), transparent);
            opacity: 0.55;
            pointer-events: none;
        }

        /* Oversized faint brand star bleeding off the corner */
        .watermark {
            position: absolute;
            top: -180px;
            right: -180px;
            width: 520px;
            height: 520px;
            color: var(--teal-600);
            opacity: 0.05;
            pointer-events: none;
        }

        .watermark--bottom {
            top: auto;
            right: auto;
            bottom: -220px;
            left: -220px;
            width: 560px;
            height: 560px;
        }

        main {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            max-width: 34rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--teal-700);
            background: var(--teal-50);
            border: 1px solid var(--teal-100);
            border-radius: 9999px;
            padding: 0.375rem 0.875rem;
        }

        .code {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 1.75rem;
            font-size: clamp(6rem, 20vw, 9rem);
            font-weight: 700;
            letter-spacing: -0.05em;
            line-height: 1;
            color: var(--slate-900);
            user-select: none;
        }

        .code svg {
            width: 0.78em;
            height: 0.78em;
            color: var(--teal-600);
            animation: spin 48s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        h1 {
            margin-top: 1.5rem;
            font-size: 1.375rem;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        p {
            margin-top: 0.75rem;
            font-size: 0.9375rem;
            line-height: 1.65;
            color: var(--slate-500);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            padding: 0.625rem 1.25rem;
            text-decoration: none;
            transition: background-color 150ms ease, border-color 150ms ease, transform 150ms ease;
        }

        .btn:active { transform: translateY(1px); }

        .btn--primary {
            background: var(--teal-600);
            color: #fff;
        }

        .btn--primary:hover { background: var(--teal-700); }

        .btn--ghost {
            background: #fff;
            color: var(--slate-900);
            border: 1px solid var(--slate-200);
        }

        .btn--ghost:hover { border-color: var(--slate-400); }

        footer {
            position: absolute;
            bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--slate-400);
        }

        footer svg { width: 1rem; height: 1rem; color: var(--teal-600); }

        /* Staggered entrance */
        .reveal {
            opacity: 0;
            transform: translateY(12px);
            animation: rise 600ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        .reveal:nth-child(1) { animation-delay: 0ms; }
        .reveal:nth-child(2) { animation-delay: 90ms; }
        .reveal:nth-child(3) { animation-delay: 180ms; }
        .reveal:nth-child(4) { animation-delay: 260ms; }
        .reveal:nth-child(5) { animation-delay: 340ms; }

        @keyframes rise {
            to { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .code svg { animation: none; }
            .reveal { animation: none; opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
    <div class="glow" aria-hidden="true"></div>

    <svg class="watermark" viewBox="0 0 64 64" fill="none" aria-hidden="true">
        <rect x="14" y="14" width="36" height="36" rx="7" stroke="currentColor" stroke-width="5"/>
        <rect x="14" y="14" width="36" height="36" rx="7" stroke="currentColor" stroke-width="5" transform="rotate(45 32 32)"/>
        <circle cx="32" cy="32" r="6" fill="currentColor"/>
    </svg>
    <svg class="watermark watermark--bottom" viewBox="0 0 64 64" fill="none" aria-hidden="true">
        <rect x="14" y="14" width="36" height="36" rx="7" stroke="currentColor" stroke-width="5"/>
        <rect x="14" y="14" width="36" height="36" rx="7" stroke="currentColor" stroke-width="5" transform="rotate(45 32 32)"/>
        <circle cx="32" cy="32" r="6" fill="currentColor"/>
    </svg>

    <main>
        <span class="badge reveal">Error 404</span>

        <div class="code reveal" aria-label="404">
            <span>4</span>
            <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
                <rect x="14" y="14" width="36" height="36" rx="7" stroke="currentColor" stroke-width="5"/>
                <rect x="14" y="14" width="36" height="36" rx="7" stroke="currentColor" stroke-width="5" transform="rotate(45 32 32)"/>
                <circle cx="32" cy="32" r="6" fill="currentColor"/>
            </svg>
            <span>4</span>
        </div>

        <h1 class="reveal">This page doesn't exist</h1>

        <p class="reveal">
            The page you're looking for may have been moved or removed —
            but the good you set out to do is still very much here.
        </p>

        <div class="actions reveal">
            <a href="{{ url('/') }}" class="btn btn--primary">Go to homepage</a>
            <a href="javascript:history.back()" class="btn btn--ghost">Go back</a>
        </div>
    </main>

    <footer>
        <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
            <rect x="14" y="14" width="36" height="36" rx="7" stroke="currentColor" stroke-width="5"/>
            <rect x="14" y="14" width="36" height="36" rx="7" stroke="currentColor" stroke-width="5" transform="rotate(45 32 32)"/>
            <circle cx="32" cy="32" r="6" fill="currentColor"/>
        </svg>
        ihsan
    </footer>
</body>
</html>
