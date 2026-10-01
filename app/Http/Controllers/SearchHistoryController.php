<?php

namespace App\Http\Controllers;

use App\Services\SearchHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SearchHistoryController extends Controller
{
    public function destroy(Request $request, SearchHistory $history): RedirectResponse
    {
        abort_unless(config('search_history.enabled'), 404);
        try {
            $history->clear($request->session()->getId());
        } catch (Throwable $exception) {
            report($exception);

            return to_route('trips.index')->with('history_status', 'Historique indisponible. Réessayez pour l’effacer.');
        }

        return to_route('trips.index')->with('history_status', 'Historique des recherches effacé.');
    }
}
