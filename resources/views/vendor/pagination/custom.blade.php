@if ($paginator->hasPages())
<nav class="glass-pagination" role="navigation" aria-label="Pagination">
    <div class="pagination-info">
        Menampilkan
        <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
        dari <strong>{{ $paginator->total() }}</strong> data
    </div>

    <ul class="pagination-pages">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled">
                <span class="page-link page-nav">‹</span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link page-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">‹</a>
            </li>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled">
                    <span class="page-link page-dots">{{ $element }}</span>
                </li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page">
                            <span class="page-link page-current">{{ $page }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link page-nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">›</a>
            </li>
        @else
            <li class="page-item disabled">
                <span class="page-link page-nav">›</span>
            </li>
        @endif
    </ul>

    {{-- Jump to page --}}
    <div class="pagination-jump">
        <span class="jump-label">Halaman</span>
        <input
            type="number"
            class="jump-input"
            id="jumpToPage_{{ uniqid() }}"
            min="1"
            max="{{ $paginator->lastPage() }}"
            value="{{ $paginator->currentPage() }}"
            onkeydown="if(event.key==='Enter') jumpToPage(this, {{ $paginator->lastPage() }}, '{{ $paginator->url(1) }}')"
        >
        <span class="jump-label">/ {{ $paginator->lastPage() }}</span>
    </div>
</nav>

<script>
function jumpToPage(input, lastPage, baseUrl) {
    let page = parseInt(input.value);
    if (isNaN(page) || page < 1) page = 1;
    if (page > lastPage) page = lastPage;
    const url = new URL(baseUrl);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}
</script>
@endif
