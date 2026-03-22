<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'SJ Armory') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <style>
            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: Figtree, sans-serif;
                background: #000;
                min-height: 100svh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .wrap {
                position: relative;
                width: 100%;
                height: 100svh;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .hero {
                position: relative;
                z-index: 1;
                width: 100%;
                height: 100%;
                object-fit: fill;
            }
            .cta {
                position: absolute;
                left: 69%;
                top: 71%;
                transform: translate(-50%, 0);
                z-index: 2;
            }
            .login-button {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                min-width: clamp(190px, 19vw, 250px);
                padding: 0.48rem 1.2rem 0.48rem 0.55rem;
                border-radius: 999px;
                text-decoration: none;
                background: linear-gradient(90deg, #2d46ff 0%, #1f67ff 38%, #10b9d9 100%);
                border: 2px solid rgba(255, 255, 255, 0.9);
                box-shadow:
                    0 14px 30px rgba(2, 15, 43, 0.42),
                    inset 0 1px 0 rgba(255, 255, 255, 0.22);
                transition: transform 150ms ease, filter 150ms ease, box-shadow 150ms ease;
            }
            .login-button__icon {
                flex: 0 0 auto;
                width: 2.35rem;
                height: 2.35rem;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: radial-gradient(circle at 30% 30%, #66d7ff 0%, #2192ff 58%, #1436c6 100%);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.34),
                    0 6px 14px rgba(6, 18, 56, 0.32);
            }
            .login-button__icon svg {
                width: 1.2rem;
                height: 1.2rem;
                display: block;
            }
            .login-button__label {
                display: inline-flex;
                flex-direction: column;
                justify-content: center;
                line-height: 1;
                color: #ffffff;
                font-weight: 800;
                letter-spacing: 0.08em;
                font-size: clamp(0.84rem, 0.95vw, 1rem);
                text-transform: uppercase;
                text-shadow: 0 1px 1px rgba(3, 14, 41, 0.42);
            }
            .login-button:hover {
                transform: translateY(-1px);
                filter: brightness(1.05);
                box-shadow:
                    0 18px 36px rgba(2, 15, 43, 0.48),
                    inset 0 1px 0 rgba(255, 255, 255, 0.26);
            }
            @media (max-width: 900px) {
                .cta {
                    left: 50%;
                    top: auto;
                    bottom: 8%;
                    transform: translateX(-50%);
                }
            }
        </style>
    </head>
    <body>
        <div class="wrap">
            <img src="{{ asset('images/SJwelcome.png') }}" alt="SJ Seguridad Privada" class="hero" />
            <div class="cta">
                <a href="{{ route('login') }}" class="login-button" aria-label="{{ __('Iniciar sesion') }}">
                    <span class="login-button__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.5 10V7.8C8.5 5.7 10.18 4 12.25 4C14.32 4 16 5.7 16 7.8V10" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/>
                            <rect x="6.5" y="10" width="11.5" height="9.5" rx="2.6" fill="#ffffff"/>
                            <path d="M12.25 13.1C13.16 13.1 13.9 13.84 13.9 14.75C13.9 15.33 13.6 15.84 13.15 16.13V17.15C13.15 17.65 12.75 18.05 12.25 18.05C11.75 18.05 11.35 17.65 11.35 17.15V16.13C10.9 15.84 10.6 15.33 10.6 14.75C10.6 13.84 11.34 13.1 12.25 13.1Z" fill="#1c5dff"/>
                        </svg>
                    </span>
                    <span class="login-button__label">{{ __('Iniciar sesion') }}</span>
                </a>
            </div>
        </div>
    </body>
</html>




