@if ($paginator->hasPages())
    <nav class="admin-pagination" role="navigation" aria-label="Pagination">
        <p class="admin-pagination-summary">
            {{ __('Showing') }}
            <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
            {{ __('to') }}
            <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
            {{ __('of') }}
            <strong>{{ $paginator->total() }}</strong>
            {{ __('results') }}
        </p>

        <div class="admin-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="admin-page-link is-disabled" aria-disabled="true">{{ __('Previous') }}</span>
            @else
                <a class="admin-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('Previous') }}</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="admin-page-link is-ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="admin-page-link is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="admin-page-link" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="admin-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('Next') }}</a>
            @else
                <span class="admin-page-link is-disabled" aria-disabled="true">{{ __('Next') }}</span>
            @endif
        </div>
    </nav>
@endif
