@if(auth()->user()->isSuperAdmin())
    <input type="hidden" name="_admin_workspace" value="{{ $org?->id ?? 0 }}">
@endif
