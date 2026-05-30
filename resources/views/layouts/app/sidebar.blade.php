<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                {{-- <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate /> --}}
                <a {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
                    <svg width="40" height="40" viewBox="0 0 80 80" fill="none">
                        <defs>
                            <linearGradient id="goldGradient" x1="0" y1="0" x2="80" y2="80">
                                <stop offset="0%" stop-color="#e8c84a"/>
                                <stop offset="100%" stop-color="#a07810"/>
                            </linearGradient>
                        </defs>

                        <path
                            d="M40 6L70 18L70 44Q70 62 40 76Q10 62 10 44L10 18Z"
                            fill="url(#goldGradient)"
                            stroke="#a07810"
                            stroke-width="1.5"
                        />

                        <path
                            d="M40 14L63 23L63 44Q63 57 40 69Q17 57 17 44L17 23Z"
                            fill="#13161c"
                            fill-opacity=".85"
                        />

                        <circle
                            cx="40"
                            cy="40"
                            r="14"
                            fill="none"
                            stroke="url(#goldGradient)"
                            stroke-width="2"
                        />

                        <text
                            x="40"
                            y="46"
                            text-anchor="middle"
                            font-size="14"
                            font-weight="700"
                            fill="#d4af37"
                        >
                            S
                        </text>
                    </svg>

                    <div class="flex flex-col">
                        <span class="font-serif text-xs uppercase tracking-[0.18em] text-yellow-400">
                            Silver Ledger
                        </span>

                        <span class="text-[10px] uppercase tracking-[0.25em] text-zinc-500">
                            Market Tracker
                        </span>
                    </div>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shopping-bag" :href="route('buy-order')" :current="request()->routeIs('buy-order')" wire:navigate>
                        {{ __('Buy Orders') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" :href="route('sales-order')" :current="request()->routeIs('sales-order')" wire:navigate>
                        {{ __('Sales Order') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="archive-box" :href="route('inventory')" :current="request()->routeIs('inventory')" wire:navigate>
                        {{ __('Inventory') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>
                        {{ __('Reports') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                {{-- <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item> --}}

                {{-- <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item> --}}
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
