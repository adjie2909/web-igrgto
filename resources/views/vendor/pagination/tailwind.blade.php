@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="pager__wrap">
            @if ($paginator->onFirstPage())
                <span class="pager__item pager__item--ghost pager__item--disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    &lsaquo;
                </span>
            @else
                <a class="pager__item pager__item--ghost" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">
                    &lsaquo;
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager__dots" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager__item pager__item--active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pager__item" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pager__item pager__item--ghost" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">
                    &rsaquo;
                </a>
            @else
                <span class="pager__item pager__item--ghost pager__item--disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    &rsaquo;
                </span>
            @endif
        </div>
    </nav>
@endif
