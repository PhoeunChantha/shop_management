<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="header-kicker mb-1">Access Management</p>
            <h2 class="font-semibold text-xl text-gray-900 leading-tight mb-0">
                {{ __('Permission Audit') }}
            </h2>
        </div>
    </x-slot>

    <div class="admin-page permission-audit-page">
        <div class="page-section-header">
            <div>
                <p class="section-kicker">Governance Review</p>
                <h3>Admin Permission Audit</h3>
            </div>
            <a href="{{ route('admin.permission-audit.export', request()->query()) }}" class="premium-button premium-button--dark">
                <i class="fa-solid fa-file-arrow-down"></i>
                <span>Export Matrix</span>
            </a>
        </div>

        <div class="permission-audit-strip">
            <div class="permission-audit-stat">
                <div>
                    <span>Roles</span>
                    <strong>{{ number_format($stats['roles']) }}</strong>
                </div>
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="permission-audit-stat">
                <div>
                    <span>Permissions</span>
                    <strong>{{ number_format($stats['permissions']) }}</strong>
                </div>
                <i class="fa-solid fa-key"></i>
            </div>
            <div class="permission-audit-stat permission-audit-stat--warning">
                <div>
                    <span>Risky Grants</span>
                    <strong>{{ number_format($stats['risky_permissions']) }}</strong>
                </div>
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="permission-audit-stat">
                <div>
                    <span>Direct User Grants</span>
                    <strong>{{ number_format($stats['direct_permissions']) }}</strong>
                </div>
                <i class="fa-solid fa-user-lock"></i>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.permission-audit.index') }}" class="permission-audit-compare">
            <div class="permission-audit-compare__intro">
                <span><i class="fa-solid fa-code-compare"></i></span>
                <div>
                    <p class="section-kicker">Role Compare</p>
                    <strong>Review permission gaps</strong>
                </div>
            </div>

            <div class="permission-audit-compare__fields">
                <label>
                    <span>Compare</span>
                    <select name="role_a">
                        <option value="">First role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(($filters['role_a'] ?? null) == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Against</span>
                    <select name="role_b">
                        <option value="">Second role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(($filters['role_b'] ?? null) == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="permission-audit-compare__actions">
                <a href="{{ route('admin.permission-audit.index') }}" class="ghost-button">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset</span>
                </a>
                <button type="submit" class="filter-button">
                    <i class="fa-solid fa-code-compare"></i>
                    <span>Compare</span>
                </button>
            </div>
        </form>

        <div class="permission-audit-grid">
            <section class="permission-audit-panel">
                <div class="permission-audit-panel__head">
                    <div>
                        <p class="section-kicker">Role Health</p>
                        <h4>Access Summary</h4>
                    </div>
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="permission-audit-role-list">
                    @foreach ($roleSummaries as $summary)
                        <div class="permission-audit-role">
                            <div>
                                <strong>{{ $summary['name'] }}</strong>
                                <span>{{ number_format($summary['users_count']) }} users</span>
                            </div>
                            <div class="permission-audit-role__meta">
                                <span>{{ number_format($summary['permissions_count']) }} permissions</span>
                                <span class="{{ $summary['risky_count'] > 0 ? 'is-warning' : '' }}">{{ number_format($summary['risky_count']) }} risky</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="permission-audit-panel">
                <div class="permission-audit-panel__head">
                    <div>
                        <p class="section-kicker">High Impact</p>
                        <h4>Risky Permissions</h4>
                    </div>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="permission-audit-risk-list">
                    @forelse ($riskyPermissions->take(8) as $permission)
                        <div class="permission-audit-risk">
                            <strong>{{ $permission['name'] }}</strong>
                            <span>{{ $permission['roles']->isEmpty() ? 'No role assigned' : $permission['roles']->join(', ') }}</span>
                        </div>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-shield-heart" title="No risky permissions" message="No high-impact permissions were detected." />
                    @endforelse
                </div>
            </section>
        </div>

        @if ($comparison)
            <section class="permission-audit-panel permission-audit-panel--wide">
                <div class="permission-audit-panel__head">
                    <div>
                        <p class="section-kicker">Role Difference</p>
                        <h4>{{ $comparison['left']->name }} vs {{ $comparison['right']->name }}</h4>
                    </div>
                    <span class="status-pill status-pill--neutral">{{ $comparison['differences']->count() }} differences</span>
                </div>
                <div class="table-responsive">
                    <table class="premium-table permission-audit-table">
                        <thead>
                            <tr>
                                <th>Permission</th>
                                <th>Subject</th>
                                <th>{{ $comparison['left']->name }}</th>
                                <th>{{ $comparison['right']->name }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($comparison['differences'] as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong></td>
                                    <td>{{ $row['subject'] }}</td>
                                    <td>{!! $row['roles'][$comparison['left']->id] ? '<span class="audit-check">Yes</span>' : '<span class="audit-miss">No</span>' !!}</td>
                                    <td>{!! $row['roles'][$comparison['right']->id] ? '<span class="audit-check">Yes</span>' : '<span class="audit-miss">No</span>' !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-admin.empty-state icon="fa-solid fa-code-compare" title="Roles match" message="These roles currently have the same permission set." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="permission-audit-grid">
            <section class="permission-audit-panel">
                <div class="permission-audit-panel__head">
                    <div>
                        <p class="section-kicker">Direct Grants</p>
                        <h4>User-Specific Permissions</h4>
                    </div>
                    <i class="fa-solid fa-user-lock"></i>
                </div>
                <div class="permission-audit-compact-list">
                    @forelse ($directPermissions->take(8) as $grant)
                        <div>
                            <strong>{{ $grant['user_name'] }}</strong>
                            <span>{{ $grant['permission_name'] }}</span>
                        </div>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-user-check" title="No direct grants" message="Users receive permissions through roles only." />
                    @endforelse
                </div>
            </section>

            <section class="permission-audit-panel">
                <div class="permission-audit-panel__head">
                    <div>
                        <p class="section-kicker">Access Review</p>
                        <h4>Stale Admin Records</h4>
                    </div>
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div class="permission-audit-compact-list">
                    @forelse ($staleAdmins as $admin)
                        <div>
                            <strong>{{ $admin->name }}</strong>
                            <span>{{ $admin->email }} - updated {{ $admin->updated_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <x-admin.empty-state icon="fa-solid fa-user-shield" title="No stale admins" message="No admin, manager, or staff records older than 90 days were found." />
                    @endforelse
                </div>
            </section>
        </div>

        <section class="permission-audit-panel permission-audit-panel--wide">
            <div class="permission-audit-panel__head">
                <div>
                    <p class="section-kicker">Permission Matrix</p>
                    <h4>Role Coverage</h4>
                </div>
                <span class="status-pill status-pill--neutral">{{ $permissions->count() }} permissions</span>
            </div>
            <div class="table-responsive">
                <table class="premium-table permission-audit-table permission-audit-matrix">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th>Subject</th>
                            <th>Action</th>
                            @foreach ($roles as $role)
                                <th>{{ $role->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($matrix as $row)
                            <tr class="{{ $row['is_risky'] ? 'permission-audit-row--warning' : '' }}">
                                <td><strong>{{ $row['name'] }}</strong></td>
                                <td>{{ $row['subject'] }}</td>
                                <td><span class="status-pill status-pill--neutral">{{ $row['action'] }}</span></td>
                                @foreach ($roles as $role)
                                    <td>
                                        @if ($row['roles'][$role->id])
                                            <span class="audit-check"><i class="fa-solid fa-check"></i></span>
                                        @else
                                            <span class="audit-miss"><i class="fa-solid fa-minus"></i></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + $roles->count() }}">
                                    <x-admin.empty-state icon="fa-solid fa-key" title="No permissions found" message="Create permissions before running an access audit." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
