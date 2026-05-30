<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div class="min-h-screen flex items-center justify-center p-6 md:p-10 bg-slate-950">

        <div class="w-full max-w-md">

            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 mb-6 font-medium" wire:navigate>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 border border-amber-500/20">
                    <x-app-logo-icon class="size-8 text-amber-400" />
                </span>

                <span class="text-lg font-semibold text-white">
                    Silver Ledger
                </span>

                <span class="text-xs text-slate-400">
                    Albion Market Accounting System
                </span>
            </a>

            <!-- Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">

                {{ $slot }}

            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-slate-500 mt-6">
                Track silver, profit & market trades from Albion Online
            </p>

        </div>
    </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
