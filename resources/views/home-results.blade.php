@if(mb_strlen($search) < 2)
    <div class="search-empty"><strong>Your community is waiting.</strong><span>Search for your organization to get started.</span></div>
@elseif($organizations->isEmpty())
    <div class="search-empty"><strong>No organizations found.</strong><span>Try another name, or ask your organization for its direct link.</span></div>
@else
    <p class="eyebrow">Organizations matching &ldquo;{{ $search }}&rdquo;</p>
    @foreach($organizations as $item)
        <a href="{{ route('org.hours.index', $item) }}" class="search-result">
            <span class="organization-avatar" style="background-color: {{ $item->accent_color }}; color: {{ $item->accent_text_color }}">
                @if($item->logo_url)<img src="{{ $item->logo_url }}" alt="">@else{{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}@endif
            </span>
            <span><strong>{{ $item->name }}</strong><small>Log volunteer hours</small></span>
            <span class="ms-auto" aria-hidden="true">&rarr;</span>
        </a>
    @endforeach
    @if($organizations->count() === 10)<p class="form-text mt-3">Showing up to 10 results. Refine your search to find a specific organization.</p>@endif
@endif
