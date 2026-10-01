<?php

namespace App\Http\Controllers;

use App\Models\AccountAlert;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        return view('market.alerts', ['alerts' => AccountAlert::where('user_id', $request->user()->id)->latest()->paginate(20)]);
    }

    public function read(Request $request, AccountAlert $alert): RedirectResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 403);
        $alert->update(['read_at' => now()]);

        return back()->with('success', 'Notification marquée comme lue.');
    }
}
