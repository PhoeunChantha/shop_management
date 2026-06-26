<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Overview</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">{{ __('Dashboard') }}</h2>
        </div>
    </x-slot>

    @php
        $admin = Auth::user();

        $kpis = [
            ['label' => 'Total Sales', 'value' => '$48,260', 'trend' => '+12.5%', 'up' => true, 'icon' => 'fa-sack-dollar', 'tone' => 'blue', 'spark' => [40, 55, 45, 70, 60, 82, 65, 90, 76, 95, 84, 100]],
            ['label' => 'Total Orders', 'value' => '1,840', 'trend' => '+8.2%', 'up' => true, 'icon' => 'fa-bag-shopping', 'tone' => 'orange', 'spark' => [30, 42, 38, 55, 50, 62, 58, 70, 66, 78, 74, 88]],
            ['label' => 'Total Products', 'value' => '326', 'trend' => '+3.1%', 'up' => true, 'icon' => 'fa-shirt', 'tone' => 'violet', 'spark' => [60, 55, 62, 58, 64, 60, 68, 64, 70, 66, 72, 70]],
            ['label' => 'Total Customers', 'value' => '2,415', 'trend' => '-1.4%', 'up' => false, 'icon' => 'fa-users', 'tone' => 'green', 'spark' => [80, 70, 75, 65, 72, 60, 66, 58, 62, 55, 60, 52]],
        ];

        $toneIcon = [
            'blue' => 'text-blue-600 bg-blue-50 dark:bg-blue-500/15',
            'orange' => 'text-orange-500 bg-orange-50 dark:bg-orange-500/15',
            'violet' => 'text-violet-600 bg-violet-50 dark:bg-violet-500/15',
            'green' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/15',
        ];

        $chart = [
            ['m' => 'Jan', 'v' => 42], ['m' => 'Feb', 'v' => 55], ['m' => 'Mar', 'v' => 48],
            ['m' => 'Apr', 'v' => 67], ['m' => 'May', 'v' => 60], ['m' => 'Jun', 'v' => 78],
            ['m' => 'Jul', 'v' => 64], ['m' => 'Aug', 'v' => 88], ['m' => 'Sep', 'v' => 72],
            ['m' => 'Oct', 'v' => 95], ['m' => 'Nov', 'v' => 83], ['m' => 'Dec', 'v' => 100],
        ];

        $orders = [
            ['id' => '#3201', 'cust' => 'Alex Rivera', 'status' => 'paid', 'total' => '$128.00', 'date' => '26 Jun'],
            ['id' => '#3200', 'cust' => 'Mia Chen', 'status' => 'shipped', 'total' => '$74.50', 'date' => '26 Jun'],
            ['id' => '#3199', 'cust' => 'Daniel Cole', 'status' => 'pending', 'total' => '$240.00', 'date' => '25 Jun'],
            ['id' => '#3198', 'cust' => 'Sara Kim', 'status' => 'paid', 'total' => '$56.00', 'date' => '25 Jun'],
            ['id' => '#3197', 'cust' => 'Liam Osei', 'status' => 'cancelled', 'total' => '$98.00', 'date' => '24 Jun'],
        ];

        $statusChip = [
            'paid' => 'text-emerald-700 bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-500/15',
            'pending' => 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-500/15',
            'shipped' => 'text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-500/15',
            'cancelled' => 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-500/15',
        ];

        $lowStock = [
            ['name' => 'Classic White Tee', 'sku' => 'TS-001', 'stock' => 4, 'pct' => 12],
            ['name' => 'Vintage Black Hoodie', 'sku' => 'HD-014', 'stock' => 6, 'pct' => 18],
            ['name' => 'Sunset Graphic Tee', 'sku' => 'TS-052', 'stock' => 3, 'pct' => 9],
            ['name' => 'Oversized Beige Crew', 'sku' => 'CR-008', 'stock' => 8, 'pct' => 24],
        ];

        $quick = [
            ['label' => 'New User', 'icon' => 'fa-user-plus', 'url' => route('admin.users.create')],
            ['label' => 'New Category', 'icon' => 'fa-layer-group', 'url' => route('admin.categories.create')],
            ['label' => 'New Color', 'icon' => 'fa-palette', 'url' => route('admin.colors.create')],
            ['label' => 'Settings', 'icon' => 'fa-gear', 'url' => route('admin.settings.index')],
        ];

        $card = 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#121c31]';
        $headBorder = 'border-slate-100 dark:border-white/10';
    @endphp

    <div x-data="{ shown: false }" x-init="$nextTick(() => shown = true)" class="space-y-5">

        {{-- ============ Hero ============ --}}
        <section class="transition-all duration-500 ease-out"
            :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'">
            <div class="relative overflow-hidden rounded-3xl px-7 py-8 text-white shadow-xl"
                style="background: linear-gradient(135deg, #0f766e 0%, #101928 60%, #0b1220 100%);">
                <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/5"></div>
                <div class="absolute -right-24 bottom-[-7rem] h-72 w-72 rotate-12 rounded-3xl border border-white/10"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-300/90">
                            {{ now()->format('l, d M Y') }}
                        </p>
                        <h1 class="mt-2 text-3xl font-extrabold leading-tight">
                            Welcome back, {{ $admin->name }} 👋
                        </h1>
                        <p class="mt-2 max-w-xl text-sm text-white/70">
                            Here's what's happening in your store today. Sales are up and orders keep rolling in.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.categories.create') }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-2.5 text-sm font-extrabold text-slate-900 shadow-lg shadow-amber-500/30 transition hover:-translate-y-0.5 hover:bg-amber-300">
                            <i class="fa-solid fa-plus"></i> Add product
                        </a>
                        <a href="{{ route('admin.users.index') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-2.5 text-sm font-extrabold text-white transition hover:-translate-y-0.5 hover:bg-white/20">
                            <i class="fa-regular fa-eye"></i> View customers
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ KPI cards ============ --}}
        <section class="grid grid-cols-1 gap-5 transition-all delay-100 duration-500 ease-out sm:grid-cols-2 xl:grid-cols-4"
            :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'">
            @foreach ($kpis as $kpi)
                <div class="group {{ $card }} p-5 transition duration-200 hover:-translate-y-1 hover:shadow-xl">
                    <div class="flex items-start justify-between">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl text-lg {{ $toneIcon[$kpi['tone']] }}">
                            <i class="fa-solid {{ $kpi['icon'] }}"></i>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-extrabold {{ $kpi['up'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' }}">
                            <i class="fa-solid {{ $kpi['up'] ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                            {{ $kpi['trend'] }}
                        </span>
                    </div>
                    <p class="mt-4 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ $kpi['value'] }}</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $kpi['label'] }}</p>

                    <div class="mt-4 flex h-9 items-end gap-1 {{ explode(' ', $toneIcon[$kpi['tone']])[0] }}">
                        @foreach ($kpi['spark'] as $h)
                            <span class="flex-1 rounded-sm bg-current {{ $loop->last ? 'opacity-60' : 'opacity-20' }}"
                                style="height: {{ $h }}%"></span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>

        {{-- ============ Main grid ============ --}}
        <section class="grid grid-cols-1 gap-5 transition-all delay-200 duration-500 ease-out xl:grid-cols-3"
            :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'">

            {{-- Left column --}}
            <div class="space-y-5 xl:col-span-2">

                {{-- Sales chart --}}
                <div class="{{ $card }}">
                    <div class="flex items-center justify-between gap-3 border-b {{ $headBorder }} px-6 py-4">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Sales overview</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Revenue across the last 12 months</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 dark:text-slate-400">
                            <span class="h-2 w-2 rounded-full bg-slate-800 dark:bg-teal-400"></span> Revenue
                        </span>
                    </div>
                    <div class="px-6 py-5">
                        <div class="flex h-52 items-end gap-2 pt-2">
                            @foreach ($chart as $bar)
                                <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                                    <div class="w-full max-w-[28px] rounded-t-lg transition hover:brightness-110 {{ $loop->last ? 'bg-gradient-to-b from-teal-500 to-teal-700' : 'bg-gradient-to-b from-slate-600 to-slate-900 dark:from-slate-500 dark:to-slate-700' }}"
                                        style="height: {{ $bar['v'] }}%" title="{{ $bar['m'] }}"></div>
                                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ $bar['m'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Recent orders --}}
                <div class="overflow-hidden {{ $card }}">
                    <div class="flex items-center justify-between gap-3 border-b {{ $headBorder }} px-6 py-4">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Recent orders</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Latest activity from your store</p>
                        </div>
                        <a href="#" class="text-xs font-extrabold text-teal-700 hover:text-teal-800 dark:text-teal-400">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b {{ $headBorder }} bg-slate-50/70 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-white/5 dark:text-slate-400">
                                    <th class="px-6 py-3">Order</th>
                                    <th class="px-6 py-3">Customer</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Total</th>
                                    <th class="px-6 py-3 text-right">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                                @foreach ($orders as $order)
                                    <tr class="transition hover:bg-slate-50 dark:hover:bg-white/5">
                                        <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-slate-100">{{ $order['id'] }}</td>
                                        <td class="px-6 py-3.5 text-slate-600 dark:text-slate-300">{{ $order['cust'] }}</td>
                                        <td class="px-6 py-3.5">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-extrabold capitalize {{ $statusChip[$order['status']] }}">
                                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                {{ $order['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5 text-right font-bold text-slate-900 dark:text-slate-100">{{ $order['total'] }}</td>
                                        <td class="px-6 py-3.5 text-right text-slate-500 dark:text-slate-400">{{ $order['date'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right column --}}
            <div class="space-y-5">

                {{-- Low stock --}}
                <div class="{{ $card }}">
                    <div class="flex items-center justify-between gap-3 border-b {{ $headBorder }} px-6 py-4">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Low stock</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Products running out soon</p>
                        </div>
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/15">
                            <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                        </span>
                    </div>
                    <div class="px-6 py-2">
                        @foreach ($lowStock as $item)
                            <div class="flex items-center gap-3.5 border-b border-dashed {{ $headBorder }} py-3 last:border-0">
                                <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                                    <i class="fa-solid fa-shirt"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13px] font-extrabold text-slate-900 dark:text-slate-100">{{ $item['name'] }}</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $item['sku'] }}</p>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                                        <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-500" style="width: {{ $item['pct'] }}%"></div>
                                    </div>
                                </div>
                                <span class="flex-shrink-0 text-sm font-black text-red-500">{{ $item['stock'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Quick actions --}}
                <div class="{{ $card }}">
                    <div class="border-b {{ $headBorder }} px-6 py-4">
                        <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">Quick actions</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Jump straight to common tasks</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 p-5">
                        @foreach ($quick as $action)
                            <a href="{{ $action['url'] }}"
                                class="group flex flex-col gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 p-4 transition hover:-translate-y-0.5 hover:border-teal-200 hover:bg-white hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-teal-500/40 dark:hover:bg-white/10">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-700 transition group-hover:bg-teal-600 group-hover:text-white dark:bg-teal-500/15 dark:text-teal-300">
                                    <i class="fa-solid {{ $action['icon'] }} text-sm"></i>
                                </span>
                                <span class="text-[13px] font-extrabold text-slate-800 dark:text-slate-200">{{ $action['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
