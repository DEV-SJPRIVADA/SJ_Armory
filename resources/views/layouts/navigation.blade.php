<style>
    @media (min-width: 640px) {
        .sj-nav .sj-nav-inner {
            margin-left: 0 !important;
            margin-right: 0 !important;
            max-width: none !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .sj-nav .sj-nav-desktop {
            display: grid !important;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: stretch !important;
            background: #0b123c;
            height: 4rem;
            min-height: 4rem;
            overflow: visible !important;
        }

        .sj-nav .sj-nav-left {
            display: flex !important;
            align-items: center !important;
            gap: 0.8rem;
            min-height: 4rem;
            padding: 0 1rem 0 0.8rem;
            background: #fbfdff;
            position: relative;
            z-index: 1;
        }

        .sj-nav .sj-nav-left::after {
            content: '';
            position: absolute;
            top: 0;
            right: -2.55rem;
            width: 2.55rem;
            height: 100%;
            pointer-events: none;
            background: linear-gradient(
                90deg,
                #fbfdff 0,
                rgba(247, 250, 255, 0.76) 34%,
                rgba(212, 223, 239, 0.22) 68%,
                rgba(11, 18, 60, 0) 100%
            );
        }

        .sj-nav .sj-nav-left .sj-nav-home-link {
            display: inline-flex !important;
            align-items: center !important;
            height: 4rem !important;
            padding: 0 0.35rem !important;
            border-color: transparent !important;
            color: #253047 !important;
            font-weight: 700;
            text-shadow: none !important;
            white-space: nowrap;
        }

        .sj-nav .sj-nav-left .sj-nav-home-link:hover {
            color: #0b123c !important;
        }

        .sj-nav .sj-nav-left .sj-nav-home-link.sj-active {
            color: #0a5f9c !important;
            border-color: #0a5f9c !important;
        }

        .sj-nav .sj-nav-fade {
            display: none !important;
        }

        .sj-nav .sj-nav-main {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 0.75rem;
            min-width: 0;
            min-height: 4rem;
            padding: 0 0.45rem 0 1.05rem;
            background: transparent;
            position: relative;
            z-index: 2;
            overflow: visible !important;
        }

        .sj-nav .sj-nav-links {
            display: flex !important;
            align-items: center !important;
            gap: 0.35rem;
            flex: 1 1 auto;
            min-width: 0;
        }

        .sj-nav .sj-nav-links > a {
            white-space: nowrap;
        }

        .sj-nav .sj-nav-user {
            display: flex !important;
            align-items: center !important;
            flex: 0 0 auto;
            margin-left: 0.75rem;
            padding-right: 0.1rem;
            overflow: visible !important;
        }

        .sj-nav .sj-nav-user-button {
            display: inline-flex !important;
            align-items: center !important;
            min-height: 2.8rem;
            padding: 0.55rem 0.95rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.65rem;
            background: rgba(255, 255, 255, 0.1);
            white-space: nowrap;
        }
    }
</style>

<nav
    x-data="{
        menuOpen: false,
        notificationsOpen: false,
        notificationItems: [],
        notificationLoading: false,
        notificationUnread: {{ (int) ($unreadNotificationCount ?? 0) }},
        init() {
            window.addEventListener('inbox-updated', (e) => {
                const n = e.detail?.unread_count;
                if (typeof n === 'number') {
                    this.notificationUnread = n;
                    if (this.notificationsOpen) {
                        this.loadNotifications();
                    }
                }
            });
        },
        async loadNotifications() {
            this.notificationLoading = true;
            try {
                const r = await fetch('{{ route('notifications.index') }}', {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!r.ok) { return; }
                const d = await r.json();
                this.notificationItems = d.notifications || [];
                this.notificationUnread = d.unread_count ?? 0;
            } finally {
                this.notificationLoading = false;
            }
        },
        async openNotificationsModal() {
            this.notificationsOpen = true;
            await this.loadNotifications();
        },
        async markAllNotificationsRead() {
            const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
            await fetch('{{ route('notifications.read-all') }}', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
            });
            this.notificationUnread = 0;
            this.notificationItems = this.notificationItems.map(i => ({ ...i, read: true }));
        },
        async markOneRead(id, url) {
            const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
            const r = await fetch('{{ url('/notifications') }}/' + id + '/read', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
            });
            if (r.ok) {
                const d = await r.json();
                this.notificationUnread = d.unread_count ?? 0;
                this.notificationItems = this.notificationItems.map(i => i.id === id ? { ...i, read: true } : i);
            }
            if (url) {
                window.location.href = url;
            }
        },
    }"
    class="sj-nav"
>
    <!-- Primary Navigation Menu -->
    <div class="sj-nav-inner">
        <div class="hidden sm:flex sj-nav-desktop">
            <div class="sj-nav-left">
                <div class="shrink-0 flex items-center sj-nav-brand-shift">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block" />
                    </a>
                </div>

                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="sj-nav-home-link sj-nav-home-shift">
                    {{ __('Inicio') }}
                </x-nav-link>
            </div>

            <div class="sj-nav-fade" aria-hidden="true"></div>

            <div class="sj-nav-main">
                <div class="sj-nav-links">
                    @can('viewAny', App\Models\Weapon::class)
                        <x-nav-link :href="route('weapons.index')" :active="request()->routeIs('weapons.*')">
                            {{ __('Armamento') }}
                        </x-nav-link>
                    @endcan
                    @if (Auth::user()?->isAdmin())
                        <x-nav-link :href="route('weapon-imports.index')" :active="request()->routeIs('weapon-imports.*')">
                            {{ __('Cargas masivas') }}
                        </x-nav-link>
                    @endif
                    @can('viewAny', App\Models\Client::class)
                        <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                            {{ __('Clientes') }}
                        </x-nav-link>
                    @endcan
                    @if (Auth::user()?->isAdmin())
                        <x-nav-link :href="route('portfolios.index')" :active="request()->routeIs('portfolios.*')">
                            {{ __('Asignaciones') }}
                        </x-nav-link>
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('Usuarios') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()?->isAdmin() || Auth::user()?->isAuditor())
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            {{ __('Reportes') }}
                        </x-nav-link>
                        <x-nav-link :href="route('alerts.documents')" :active="request()->routeIs('alerts.*')">
                            {{ __('Alertas') }}
                        </x-nav-link>
                    @endif
                    @can('viewAny', App\Models\Post::class)
                        <x-nav-link :href="route('posts.index')" :active="request()->routeIs('posts.*')">
                            {{ __('Puestos') }}
                        </x-nav-link>
                    @endcan
                    @can('viewAny', App\Models\Worker::class)
                        <x-nav-link :href="route('workers.index')" :active="request()->routeIs('workers.*')">
                            {{ __('Trabajadores') }}
                        </x-nav-link>
                    @endcan
                    @if (Auth::user()?->isAdmin() || Auth::user()?->isResponsible() || Auth::user()?->isAuditor())
                        <x-nav-link :href="route('maps.index')" :active="request()->routeIs('maps.*')">
                            {{ __('Mapa') }}
                        </x-nav-link>
                        <x-nav-link :href="route('transfers.index')" :active="request()->routeIs('transfers.*')">
                            {{ __('Transferencias') }}
                        </x-nav-link>
                    @endif
                </div>

                <div class="sj-nav-user flex items-center gap-2 shrink-0">
                    @if ($notificationBellEnabled ?? false)
                        <button
                            type="button"
                            @click="openNotificationsModal()"
                            class="sj-nav-notify-btn relative inline-flex items-center justify-center rounded p-1 focus-visible:ring-2 focus-visible:ring-amber-400/60"
                            aria-label="{{ __('Notificaciones') }}"
                        >
                            <svg class="h-6 w-6 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <span
                                x-show="notificationUnread > 0"
                                x-text="notificationUnread > 99 ? '99+' : notificationUnread"
                                class="sj-nav-notify-badge absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white shadow-sm ring-1 ring-white/30"
                            ></span>
                        </button>
                    @endif
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="sj-nav-user-button inline-flex items-center border text-sm leading-4 font-medium rounded-md text-slate-100 hover:text-white focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <form method="POST" action="{{ route('locale.switch') }}" class="px-4 py-2">
                                @csrf
                                <label for="locale-select" class="mb-1 block text-xs text-gray-700">{{ __('Idioma') }}</label>
                                <select id="locale-select" name="locale" class="block w-full rounded border-gray-300 text-sm text-gray-900" onchange="this.form.submit()">
                                    <option value="es" @selected(app()->getLocale() === 'es')>Espa&ntilde;ol</option>
                                    <option value="en" @selected(app()->getLocale() === 'en')>Ingl&eacute;s</option>
                                </select>
                            </form>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Perfil') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                    Cerrar sesi&oacute;n
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>

        <div class="flex justify-between h-16 sm:hidden">
            <div class="shrink-0 flex items-center ml-2 sj-nav-brand-shift">
                <a href="{{ route('dashboard') }}">
                    <x-application-logo class="block" />
                </a>
            </div>

            <div class="me-2 flex items-center gap-2 sm:hidden">
                @if ($notificationBellEnabled ?? false)
                    <button
                        type="button"
                        @click="openNotificationsModal()"
                        class="sj-nav-notify-btn relative inline-flex items-center justify-center rounded p-1 focus-visible:ring-2 focus-visible:ring-amber-400/60"
                        aria-label="{{ __('Notificaciones') }}"
                    >
                        <svg class="h-6 w-6 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span
                            x-show="notificationUnread > 0"
                            x-text="notificationUnread > 99 ? '99+' : notificationUnread"
                            class="sj-nav-notify-badge absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white shadow-sm ring-1 ring-white/30"
                        ></span>
                    </button>
                @endif
                <button @click="menuOpen = ! menuOpen" class="inline-flex items-center justify-center p-2 rounded-md text-slate-100 hover:text-white hover:bg-white/10 focus:outline-none focus:bg-white/10 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': menuOpen, 'inline-flex': ! menuOpen }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! menuOpen, 'inline-flex': menuOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': menuOpen, 'hidden': ! menuOpen}" class="sj-mobile-menu hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Inicio') }}
            </x-responsive-nav-link>
            @can('viewAny', App\Models\Weapon::class)
                <x-responsive-nav-link :href="route('weapons.index')" :active="request()->routeIs('weapons.*')">
                    {{ __('Armamento') }}
                </x-responsive-nav-link>
            @endcan
            @if (Auth::user()?->isAdmin())
                <x-responsive-nav-link :href="route('weapon-imports.index')" :active="request()->routeIs('weapon-imports.*')">
                    {{ __('Cargas masivas') }}
                </x-responsive-nav-link>
            @endif
            @can('viewAny', App\Models\Client::class)
                <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                    {{ __('Clientes') }}
                </x-responsive-nav-link>
            @endcan
            @if (Auth::user()?->isAdmin())
                <x-responsive-nav-link :href="route('portfolios.index')" :active="request()->routeIs('portfolios.*')">
                    {{ __('Asignaciones') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('Usuarios') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()?->isAdmin() || Auth::user()?->isAuditor())
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    {{ __('Reportes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('alerts.documents')" :active="request()->routeIs('alerts.*')">
                    {{ __('Alertas') }}
                </x-responsive-nav-link>
            @endif
            @can('viewAny', App\Models\Post::class)
                <x-responsive-nav-link :href="route('posts.index')" :active="request()->routeIs('posts.*')">
                    {{ __('Puestos') }}
                </x-responsive-nav-link>
            @endcan
            @can('viewAny', App\Models\Worker::class)
                <x-responsive-nav-link :href="route('workers.index')" :active="request()->routeIs('workers.*')">
                    {{ __('Trabajadores') }}
                </x-responsive-nav-link>
            @endcan
            @if (Auth::user()?->isAdmin() || Auth::user()?->isResponsible() || Auth::user()?->isAuditor())
                <x-responsive-nav-link :href="route('maps.index')" :active="request()->routeIs('maps.*')">
                    {{ __('Mapa') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('transfers.index')" :active="request()->routeIs('transfers.*')">
                    {{ __('Transferencias') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-white/10">
            <div class="px-4">
                <div class="font-medium text-base text-slate-100">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-300">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <form method="POST" action="{{ route('locale.switch') }}" class="px-4 py-2">
                    @csrf
                    <label for="locale-select-mobile" class="mb-1 block text-xs text-slate-100">{{ __('Idioma') }}</label>
                    <select id="locale-select-mobile" name="locale" class="block w-full rounded border-gray-300 text-sm text-gray-900" onchange="this.form.submit()">
                        <option value="es" @selected(app()->getLocale() === 'es')>Espa&ntilde;ol</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>Ingl&eacute;s</option>
                    </select>
                </form>

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                    this.closest('form').submit();">
                        Cerrar sesi&oacute;n
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    @if ($notificationBellEnabled ?? false)
        <div
            x-show="notificationsOpen"
            x-cloak
            class="fixed inset-0 z-[5000] flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="notificationsOpen = false"
        >
            <div
                class="flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-lg bg-white shadow-xl"
                @click.outside="notificationsOpen = false"
            >
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('Notificaciones') }}</h2>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="markAllNotificationsRead()"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                            x-show="notificationUnread > 0"
                        >
                            {{ __('Marcar todas leídas') }}
                        </button>
                        <button type="button" class="text-2xl leading-none text-gray-500 hover:text-gray-800" @click="notificationsOpen = false" aria-label="{{ __('Cerrar') }}">&times;</button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto">
                    <div x-show="notificationLoading" class="p-6 text-center text-sm text-gray-500">{{ __('Cargando…') }}</div>
                    <div x-show="!notificationLoading && notificationItems.length === 0" class="p-6 text-center text-sm text-gray-500">{{ __('No hay notificaciones.') }}</div>
                    <div x-show="!notificationLoading && notificationItems.length > 0" class="divide-y divide-gray-100">
                        <template x-for="item in notificationItems" :key="item.id">
                            <div>
                                <button
                                    type="button"
                                    class="flex w-full flex-col items-start gap-0.5 px-4 py-3 text-left text-sm hover:bg-gray-50"
                                    :class="!item.read ? 'bg-indigo-50/80' : ''"
                                    @click="markOneRead(item.id, item.url || null)"
                                >
                                    <span class="font-medium text-gray-900" x-text="item.title"></span>
                                    <span class="text-gray-600" x-text="item.body"></span>
                                    <span class="text-xs text-gray-400" x-text="item.created_at ? new Date(item.created_at).toLocaleString() : ''"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    @endif
</nav>
