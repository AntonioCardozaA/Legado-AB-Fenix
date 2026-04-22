<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ config('app.name', 'Legado Ave Fenix') }} · Industrial</title>
    
    <meta name="description" content="Legado Ave Fénix">
    <meta name="author" content="Legado Ave Fénix">
    <meta name="robots" content="index, follow">
    
    <meta property="og:title" content="Legado Ave Fénix">
    <meta property="og:description" content="Soluciones industriales en lavado y pasteurización.">
    <meta property="og:type" content="website">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #ffffff;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border: #e5e7eb;
            --accent-blue: #2563eb;
            --transition: all 0.2s ease-out;
        }

        body {
            background-color: var(--bg);
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .skip-link {
            position: absolute;
            top: -40px;
            left: 12px;
            background: var(--text-primary);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            border-radius: 40px;
            z-index: 100;
        }
        .skip-link:focus { top: 16px; }

        .landing {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .login-wrapper {
            display: flex;
            justify-content: center;
            margin: 1rem 0 2rem;
        }
        .login-button {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--text-primary);
            color: white;
            padding: 0.85rem 2rem;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
        }
        .login-button:hover {
            background: #1f2937;
            transform: scale(0.98);
        }
        .login-button svg {
            width: 1.1rem;
            height: 1.1rem;
        }

        .footer {
            border-top: 1px solid var(--border);
            padding: 2rem 0 1rem;
            margin-top: 2rem;
            text-align: center;
        }
        .footer-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            font-size: 0.8rem;
        }
        .footer-links a, .copyright {
            color: var(--text-secondary);
            text-decoration: none;
        }
        .footer-links a:hover {
            color: var(--text-primary);
        }
        .copyright {
            font-size: 0.75rem;
        }

        .loader {
            position: fixed;
            inset: 0;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        .loader.hidden {
            opacity: 0;
        }
        .dot {
            width: 8px;
            height: 8px;
            background: var(--text-primary);
            border-radius: 50%;
            margin: 0 5px;
            animation: pulse 1s infinite alternate;
        }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes pulse {
            0% { opacity: 0.2; transform: scale(0.8);}
            100% { opacity: 1; transform: scale(1);}
        }

        @media (max-width: 780px) {
            .landing {
                padding: 1rem;
            }
            .footer-links {
                gap: 1rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        .login-button:focus-visible {
            outline: 2px solid var(--accent-blue);
            outline-offset: 4px;
        }
    </style>
</head>
<body>

<div class="loader" id="globalLoader">
    <div class="dot"></div>
    <div class="dot"></div>
    <div class="dot"></div>
</div>

<main class="landing" id="main-start">

    <h1 style="font-size: 2.5rem; font-weight: 700; text-align: center; margin-top: 4rem;">
        Bienvenido a <span style="color: var(--accent-blue);">{{ config('app.name', 'Legado Ave Fenix') }}</span>
    </h1>
    <div class="login-wrapper">
        <a href="{{ route('login') }}" class="login-button" aria-label="Acceder al sistema">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                <polyline points="10 17 15 12 10 7" />
                <line x1="15" y1="12" x2="3" y2="12" />
            </svg>
            <span>Acceder</span>
        </a>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="#">Privacidad</a>
                <a href="#">Términos</a>
                <a href="#">Contacto</a>
                <a href="#">Soporte</a>
            </div>
            <div class="copyright">
                <span>© <span id="currentYear"></span> Legado Ave Fénix</span>
                <span style="margin-left: 0.75rem;">v3.0</span>
            </div>
        </div>
    </footer>
</main>

<script>
    (function() {
        const loader = document.getElementById('globalLoader');
        if (loader) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    loader.classList.add('hidden');
                    setTimeout(() => {
                        if (loader.parentNode) loader.style.display = 'none';
                    }, 300);
                }, 200);
            });
        }

        const yearSpan = document.getElementById('currentYear');
        if (yearSpan) yearSpan.textContent = new Date().getFullYear();

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (reduceMotion.matches) {
            const styleSheet = document.createElement('style');
            styleSheet.textContent = `.login-button { transition: none; transform: none !important; }`;
            document.head.appendChild(styleSheet);
        }
    })();
</script>

<noscript>
    <div style="background:#f3f4f6; color:#111827; text-align:center; padding:1rem; border-bottom:1px solid #e5e7eb;">
        JavaScript desactivado. Algunas funcionalidades pueden no estar disponibles.
    </div>
</noscript>
</body>
</html>