@if ($paginator->hasPages())
    <nav class="admin-pager" role="navigation" aria-label="Paginazione">
        @if ($paginator->onFirstPage())
            <span class="admin-page-arrow disabled" aria-disabled="true" aria-label="Pagina precedente">‹</span>
        @else
            <a class="admin-page-arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Pagina precedente">‹</a>
        @endif

        <div class="admin-page-list">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="admin-page-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="admin-page-number active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="admin-page-number" href="{{ $url }}" aria-label="Vai alla pagina {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a class="admin-page-arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Pagina successiva">›</a>
        @else
            <span class="admin-page-arrow disabled" aria-disabled="true" aria-label="Pagina successiva">›</span>
        @endif
    </nav>
@endif
