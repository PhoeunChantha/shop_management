@extends('admin.layouts.app')
@section('title', 'Activity Logs')
@section('content')
    @php($logs = \App\Support\AdminData::logs())
    <x-admin.page-header title="Activity Logs" subtitle="Audit trail of every action taken across the console.">
        <button class="ut-btn ut-btn-ghost" @click="toast('Export started','blue')"><span
                x-html="adminIcon('download','w-4 h-4')"></span> Export</button></x-admin.page-header>
    <div class="ut-card p-4 mb-4 grid md:grid-cols-2 xl:grid-cols-5 gap-3">
        <div class="relative xl:col-span-2"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                x-html="adminIcon('search','w-4 h-4')"></span><input class="ut-input !pl-9"
                placeholder="Search user, action or IP…"></div><select class="ut-select">
            <option>All modules</option>
            @foreach (\App\Support\AdminData::modules() as $module)
                <option>{{ $module }}</option>
            @endforeach
        </select>
        <select class="ut-select">
            <option>All actions</option>
            @foreach (\App\Support\AdminData::logActions() as $action)
                <option>{{ $action }}</option>
            @endforeach
        </select>
        <input type="date" class="ut-input">
    </div>
    <div class="ut-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="ut-th">User</th>
                        <th class="ut-th">Module</th>
                        <th class="ut-th">Action</th>
                        <th class="ut-th">Description</th>
                        <th class="ut-th hidden lg:table-cell">IP address</th>
                        <th class="ut-th text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($logs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="ut-td">
                                <div class="flex gap-2.5 items-center"><span
                                        class="w-7 h-7 rounded-full text-white grid place-items-center text-[10px] font-head font-bold"
                                        style="background:{{ $log['tint'] }}">{{ $log['avatar'] }}</span><span
                                        class="font-medium whitespace-nowrap">{{ $log['user'] }}</span></div>
                            </td>
                            <td class="ut-td"><span
                                    class="ut-badge bg-slate-100 text-slate-600 ring-slate-500/15">{{ $log['module'] }}</span>
                            </td>
                            <td class="ut-td"><span
                                    class="ut-badge bg-blue-50 text-brand ring-brand/15">{{ $log['action'] }}</span></td>
                            <td class="ut-td max-w-xs truncate">{{ $log['description'] }}</td>
                            <td class="ut-td hidden lg:table-cell font-mono text-xs text-slate-500">{{ $log['ip'] }}</td>
                            <td class="ut-td text-right text-slate-500 whitespace-nowrap">
                                {{ $log['created_at']->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
