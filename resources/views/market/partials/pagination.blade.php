@if ($paginator->hasPages())<nav aria-label="Pagination" class="actions mt">
        @if ($paginator->onFirstPage())<span> Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"> Précédent</a>
        @endif
        <span>Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant </a>
        @endif
    </nav>
@endif
