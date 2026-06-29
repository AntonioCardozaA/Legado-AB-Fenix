<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ config('app.name', 'Legado AB Fenix') }}</title>
    
    <meta name="description" content="Legado AB Fénix - Soluciones industriales en lavado y pasteurización.">
    <meta name="author" content="Legado AB Fénix">
    <meta name="robots" content="index, follow">
    
    <meta property="og:title" content="Legado AB Fénix">
    <meta property="og:description" content="Soluciones industriales en lavado y pasteurización.">
    <meta property="og:type" content="website">
    
    <!-- Precarga de fuentes para mejor rendimiento -->
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
            --accent-blue: #3b82f6;
            --accent-glow: rgba(59,130,246,0.5);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text-primary);
            line-height: 1.5;
            position: relative;
            min-height: 100vh;
            background-color: #0a0a0a; /* Fallback */
        }

        /* Fondo mejorado con parallax suave */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('/images/fondo.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            transform: scale(1.05); /* Efecto de profundidad */
            z-index: -2;
        }

        /* Overlay con degradado en lugar de sólido */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 100%);
            backdrop-filter: blur(2px);
            z-index: -1;
        }

        .skip-link {
            position: absolute;
            top: -40px;
            left: 12px;
            background: white;
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            border-radius: 40px;
            z-index: 100;
            transition: top 0.2s;
        }
        .skip-link:focus { top: 16px; outline: 2px solid var(--accent-blue); }

        .landing {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Centrado vertical */
            align-items: center;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        /* Contenedor del contenido principal */
        .hero {
            max-width: 800px;
            margin: 2rem auto;
        }

        /* Badge de estado (nuevo) */
        .status-badge {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 0.4rem 1rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #94a3b8;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Título principal con efecto typing */
        .hero h1 {
            font-size: clamp(2rem, 8vw, 4rem);
            font-weight: 800;
            color: white;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }
        
        .hero h1 span {
            background: linear-gradient(135deg, #fff 0%, #60a5fa 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            position: relative;
        }
        
        /* Línea de cursor para el efecto typing */
        .typing-cursor {
            display: inline-block;
            width: 3px;
            background-color: #60a5fa;
            margin-left: 4px;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        /* Subtítulo mejorado */
        .hero p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            margin: 1.5rem auto;
            max-width: 600px;
            line-height: 1.6;
        }

        /* Timeline visual (nuevo - para el legado) */
        .legacy-timeline {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .timeline-dot {
            width: 8px;
            height: 8px;
            background: #3b82f6;
            border-radius: 50%;
            box-shadow: 0 0 8px #3b82f6;
        }

        /* Botón de acceso modernizado */
        .login-wrapper {
            margin: 2rem 0 3rem;
        }
        
        .login-button {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 60px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        
        /* Efecto de brillo en hover */
        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .login-button:hover::before {
            left: 100%;
        }
        
        .login-button:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #2d3a4e 0%, #1e293b 100%);
            box-shadow: 0 12px 25px rgba(0,0,0,0.4);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .login-button svg {
            width: 1.2rem;
            height: 1.2rem;
            transition: transform 0.2s;
        }
        
        .login-button:hover svg {
            transform: translateX(4px);
        }

        /* Footer con Glassmorphism mejorado */
        .footer {
            width: 100%;
            margin-top: auto;
            padding: 2rem 0 1rem;
            border-top: 1px solid rgba(255,255,255,0.15);
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            border-radius: 20px 20px 0 0;
        }
        
        .footer-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .copyright {
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
            display: flex;
            gap: 1rem;
        }

        /* Loader moderno (spinner) */
        .loader {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            transition: opacity 0.4s, visibility 0.4s;
        }
        
        .loader.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .landing {
                padding: 1rem;
                justify-content: flex-start;
                padding-top: 3rem;
            }
            
            .hero p {
                font-size: 1rem;
                padding: 0 1rem;
            }
            
            .footer-links {
                gap: 1rem;
            }
            
            .legacy-timeline {
                gap: 1rem;
            }
            
            .timeline-item {
                font-size: 0.7rem;
            }
        }

        /* Modo oscuro automático (si el sistema lo prefiere) */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
            
            .typing-cursor {
                animation: none;
                opacity: 1;
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
    <div class="spinner"></div>
</div>

<main class="landing" id="main-start">
    <div class="hero">
        <!-- Nuevo badge de estado -->
        <h1>
            Bienvenido a <span id="typingText">Legado AB Fénix</span><span class="typing-cursor"></span>
        </h1>
        
        <p>
            Plataforma integral para el registro, monitoreo y consulta del estado de los componentes de 
            maquinaria en el Departamento de Envasado CCZ.
        </p>
        
        <!-- Timeline visual del legado -->
        <div class="legacy-timeline">
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <span>Lavadora</span>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <span>Pasteurizadora</span>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <span>Proximamente...</span>
            </div>
        </div>
        
        <div class="login-wrapper">
            <a href="{{ route('login') }}" class="login-button" aria-label="Acceder al sistema">
                <span>Acceder</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                    <polyline points="10 17 15 12 10 7" />
                    <line x1="15" y1="12" x2="3" y2="12" />
                </svg>
            </a>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="#">Privacidad</a>
                <a href="#">Términos de Servicio</a>
                <a href="#">Contacto Comercial</a>
                <a href="#">Soporte Técnico</a>
            </div>
            <div class="copyright">
                <span>© <span id="currentYear"></span> Legado AB Fénix</span>
                <span>|</span>
                <span>v2.2.1 · Edition</span>
                <span>|</span>
                <span> Departamento de Envasado CCZ</span>
            </div>
        </div>
    </footer>
</main>

<script>
    (function() {
        // --- Loader mejorado ---
        const loader = document.getElementById('globalLoader');
        if (loader) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    loader.classList.add('hidden');
                    setTimeout(() => {
                        if (loader.parentNode) loader.style.display = 'none';
                    }, 400);
                }, 300); // Pequeño delay para que se vea el spinner
            });
        }

        // --- Año dinámico ---
        const yearSpan = document.getElementById('currentYear');
        if (yearSpan) yearSpan.textContent = new Date().getFullYear();

        // --- EFECTO TYPING (NUEVO) ---
        const textElement = document.getElementById('typingText');
        if (textElement && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            const fullText = "Legado AB Fénix";
            textElement.textContent = ""; // Vaciar al inicio
            let i = 0;
            
            function typeWriter() {
                if (i < fullText.length) {
                    textElement.textContent += fullText.charAt(i);
                    i++;
                    setTimeout(typeWriter, 100);
                }
            }
            
            // Iniciar el efecto cuando la página esté cargada
            window.addEventListener('load', () => {
                setTimeout(typeWriter, 500);
            });
        } else if (textElement) {
            // Si el usuario prefiere poco movimiento, mostrar texto completo inmediatamente
            textElement.textContent = "Legado AB Fénix";
        }

        // --- Detectar reducción de movimiento y desactivar animaciones ---
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (reduceMotion.matches) {
            const styleSheet = document.createElement('style');
            styleSheet.textContent = `
                .login-button { transition: none; transform: none !important; }
                .login-button::before { display: none; }
                .typing-cursor { animation: none; opacity: 1; }
            `;
            document.head.appendChild(styleSheet);
        }
        
        // --- Pequeño efecto de parallax opcional (sutil) ---
        window.addEventListener('mousemove', (e) => {
            const moveX = (e.clientX - window.innerWidth / 2) * 0.01;
            const moveY = (e.clientY - window.innerHeight / 2) * 0.01;
            document.body.style.backgroundPosition = `${50 + moveX}% ${50 + moveY}%`;
        });
    })();
</script>

<noscript>
    <div style="background:#f3f4f6; color:#111827; text-align:center; padding:1rem; position:fixed; bottom:0; left:0; right:0; z-index:9999;">
        ⚠️ JavaScript desactivado. Para una mejor experiencia, active JavaScript.
    </div>
</noscript>
</body>
</html>