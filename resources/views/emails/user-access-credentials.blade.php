<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Acceso') }}</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, sans-serif; line-height: 1.5; color: #111827;">
    <p>{{ __('Hola :name,', ['name' => $recipientName]) }}</p>
    <p>{{ __('Se han generado credenciales de acceso para :app.', ['app' => $appName]) }}</p>
    <ul style="padding-left: 1.25rem;">
        <li><strong>{{ __('Enlace') }}:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></li>
        <li><strong>{{ __('Usuario (correo electrónico)') }}:</strong> {{ $loginEmail }}</li>
        <li><strong>{{ __('Contraseña temporal') }}:</strong> {{ $temporaryPassword }}</li>
    </ul>
    <p>{{ __('Al iniciar sesión por primera vez, el sistema le solicitará de forma obligatoria cambiar esta contraseña por una nueva.') }}</p>
    <p style="margin-top: 1.5rem; color: #6b7280; font-size: 0.875rem;">{{ __('Si no esperaba este mensaje, ignore el correo y contacte al administrador.') }}</p>
</body>
</html>
