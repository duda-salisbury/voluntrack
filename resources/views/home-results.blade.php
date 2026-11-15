@if(mb_strlen($search) >= 2)
    @forelse($organizations as $item)
        @php
            $initials = collect(preg_split('/\s+/u', trim($item->name)))->take(2)
                ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->join('');
        @endphp
        <a href="{{ route('org.hours.index', $item) }}" class="search-result">
            <span class="organization-avatar" style="background-color: {{ $item->accent_color }}; color: {{ $item->accent_text_color }}" aria-hidden="true">
                @if($item->logo_url)<img src="{{ $item->logo_url }}" alt="">@else{{ $initials }}@endif
            </span>
            <strong class="search-result-name">{{ $item->name }}</strong>
            <span class="search-result-action">Log hours <span aria-hidden="true">&rsaquo;</span></span>
        </a>
    @empty
        <p class="text-muted small mt-3 mb-0">No organizations found. Try another name, or ask your organization for its direct link.</p>
    @endforelse
    @if($organizations->count() === 10)<p class="form-text mt-3 mb-0">Showing up to 10 results. Refine your search to find a specific organization.</p>@endif
@endif
