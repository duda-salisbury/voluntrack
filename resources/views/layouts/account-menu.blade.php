@auth
@php
    $accountUser = auth()->user();
    $contextOrg = $org ?? null;
    $contextMembership = $contextOrg ? $accountUser->organizations()->where('organizations.id', $contextOrg->id)->first() : null;
@endphp
<div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $accountUser->name }}</button>
    <ul class="dropdown-menu dropdown-menu-end">
        @if($contextMembership && ! ($accountUser->isSuperAdmin() && request()->routeIs('admin.*')))<li><span class="dropdown-item-text small text-muted">{{ $contextMembership->name }} · {{ $contextMembership->pivot->role === 'admin' ? 'Administrator' : 'Volunteer' }}</span></li>@endif
        @if($contextMembership?->pivot->role === 'admin' && $contextOrg->is_active && ! ($accountUser->isSuperAdmin() && request()->routeIs('admin.*')))
            <li><form method="POST" action="{{ route('account.organizations.switch', $contextOrg) }}">@csrf<button class="dropdown-item">Organization dashboard</button></form></li>
            <li><form method="POST" action="{{ route('account.organizations.switch', $contextOrg) }}">@csrf<input type="hidden" name="destination" value="settings"><button class="dropdown-item">Organization settings</button></form></li>
            <li><form method="POST" action="{{ route('account.organizations.switch', $contextOrg) }}">@csrf<input type="hidden" name="destination" value="people"><button class="dropdown-item">People & access</button></form></li>
        @endif
        @if($accountUser->isSuperAdmin() && $contextOrg && request()->routeIs('admin.*'))
            <li><a class="dropdown-item" href="{{ route('admin.branding.edit') }}">Organization settings</a></li>
        @endif
        @if($accountUser->isSuperAdmin())
            <li><form method="POST" action="{{ route('account.platform') }}">@csrf<button class="dropdown-item">Platform overview</button></form></li>
            <li><form method="POST" action="{{ route('account.platform') }}">@csrf<input type="hidden" name="destination" value="organizations"><button class="dropdown-item">{{ $contextOrg && request()->routeIs('admin.*') ? 'Return to Platform' : 'Organizations' }}</button></form></li>
        @endif
        <li><a class="dropdown-item" href="{{ route('account.hours') }}">My hours</a></li>
        <li><a class="dropdown-item" href="{{ route('account.organizations') }}">My organizations</a></li>
        <li><a class="dropdown-item" href="{{ route('organization-requests.index') }}">My organization requests</a></li>
        @foreach($accountUser->organizations()->where('organizations.is_active', true)->orderBy('name')->get() as $switchOrg)
            @if($accountUser->isSuperAdmin() && request()->routeIs('admin.*') && $contextOrg?->id === $switchOrg->id) @continue @endif
            <li><form method="POST" action="{{ route('account.organizations.switch', $switchOrg) }}">@csrf<button class="dropdown-item small">{{ $switchOrg->name }} · {{ $switchOrg->pivot->role === 'admin' ? 'Administrator' : 'Volunteer' }}</button></form></li>
        @endforeach
        <li><a class="dropdown-item" href="{{ route('account.profile') }}">My profile</a></li>
        <li><a class="dropdown-item" href="{{ route('account.profile') }}#security">Account security</a></li>
        <li><hr class="dropdown-divider"></li><li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item">Log out</button></form></li>
    </ul>
</div>
@endauth
