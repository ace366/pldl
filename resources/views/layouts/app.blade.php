<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">
        <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">

        <script src="https://cdn.tailwindcss.com"></script>


        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            @php
                $hasMobileBottomNav = auth()->check() || session()->has('family_child_id');
            @endphp
            <main class="{{ $hasMobileBottomNav ? 'pb-[calc(5.25rem+env(safe-area-inset-bottom))] sm:pb-0' : '' }}">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
