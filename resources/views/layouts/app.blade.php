<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="sj-app sj-has-nav sj-form-compact font-sans antialiased">
        <div class="sj-shell">
            <div class="sj-fixed-header">
                @include('layouts.navigation')

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="sj-page-header">
                        <div class="sj-page-shell sj-page-shell--wide py-6">
                            {{ $header }}
                        </div>
                    </header>
                @endif
            </div>

            <!-- Page Content -->
            <main class="sj-content">
                {{ $slot }}
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
