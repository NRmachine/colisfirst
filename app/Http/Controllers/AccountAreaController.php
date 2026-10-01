<?php

namespace App\Http\Controllers;

use App\Models\AccountDocument;
use App\Models\DriverApplication;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountAreaController extends Controller
{
    private function admin(Request $request): bool
    {
        return $request->user()->role === 'admin' && $request->user()->account_status === 'approved';
    }

    private function driver(User $user): bool
    {
        return $user->role === 'driver' || DriverApplication::where('user_id', $user->id)->exists();
    }

    public function dossier(Request $request): View
    {
        $user = $request->user();
        $driver = $this->driver($user);

        return view('market.dossier', ['user' => $user, 'driver' => $driver, 'details' => $user->account_details ?? [], 'kinds' => AccountDocument::kinds($driver), 'documents' => AccountDocument::where('user_id', $user->id)->get()->keyBy('kind'), 'application' => DriverApplication::where('user_id', $user->id)->first()]);
    }

    public function save(Request $request): RedirectResponse
    {
        $user = $request->user();
        $driver = $this->driver($user);
        $rules = ['address' => 'required|string|max:250', 'postal_code' => 'required|string|max:20', 'country' => 'required|string|max:100'];
        if ($driver) {
            $rules += ['business_type' => 'required|in:particulier,micro,societe', 'company_name' => 'required_unless:business_type,particulier|nullable|string|max:160', 'registration' => 'required_unless:business_type,particulier|nullable|digits:14', 'legal_form' => 'required_if:business_type,societe|nullable|string|max:100', 'representative' => 'required_if:business_type,societe|nullable|string|max:160', 'company_address' => 'required_unless:business_type,particulier|nullable|string|max:500', 'company_email' => 'nullable|email|max:200', 'company_phone' => 'nullable|string|max:30', 'vat' => 'nullable|string|max:40'];
        }
        $data = $request->validate($rules);
        $user->account_details = $data;
        $user->save();

        return back()->with('success', 'Informations du dossier mises à jour.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['kind' => ['required', Rule::in(array_keys(AccountDocument::kinds($this->driver($user))))], 'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', 'expires_at' => 'nullable|date|after_or_equal:today']);
        $path = $request->file('document')->store('account-documents/'.$user->id, config('filesystems.private_documents'));
        abort_if($path === false, 503, 'Le stockage du fichier a échoué. Réessayez.');
        try {
            $old = DB::transaction(function () use ($user, $data, $path) {
                User::whereKey($user->id)->update(['updated_at' => now()]);
                $old = AccountDocument::where('user_id', $user->id)->where('kind', $data['kind'])->lockForUpdate()->first();
                $oldPath = $old?->path;
                AccountDocument::updateOrCreate(['user_id' => $user->id, 'kind' => $data['kind']], ['path' => $path, 'expires_at' => $data['expires_at'] ?? null, 'status' => 'pending', 'review_note' => null, 'reviewed_at' => null]);

                return $oldPath;
            });
        } catch (\Throwable $exception) {
            Storage::disk(config('filesystems.private_documents'))->delete($path);
            throw $exception;
        }
        if ($old) {
            Storage::disk(config('filesystems.private_documents'))->delete($old);
        }

        return back()->with('success', 'Document déposé. Il doit être examiné par l’administration ; l’ancienne version a été remplacée.');
    }

    public function download(Request $request, AccountDocument $document): StreamedResponse
    {
        abort_unless($document->user_id === $request->user()->id || $this->admin($request), 403);
        abort_unless(Storage::disk(config('filesystems.private_documents'))->exists($document->path), 404);

        return Storage::disk(config('filesystems.private_documents'))->download($document->path, $document->kind.'.'.pathinfo($document->path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function review(Request $request, AccountDocument $document): RedirectResponse
    {
        abort_unless($this->admin($request), 403);
        $data = $request->validate(['status' => 'required|in:approved,rejected', 'review_note' => 'required|string|min:5|max:1000', 'version' => 'required|string']);
        DB::transaction(function () use ($data, $document) {
            $locked = AccountDocument::lockForUpdate()->findOrFail($document->id);
            abort_unless(hash_equals(hash('sha256', $locked->path), $data['version']), 409, 'Document remplacé : rechargez le dossier avant de le valider.');
            abort_if($data['status'] === 'approved' && $locked->expires_at && $locked->expires_at->isBefore(today()), 422, 'Ce document est expiré.');
            unset($data['version']);
            $locked->update($data + ['reviewed_at' => now()]);
        });

        return back()->with('success', 'Examen du document enregistré.');
    }

    public function support(Request $request): View
    {
        $query = SupportTicket::with('user')->latest('updated_at');
        if (! $this->admin($request)) {
            $query->where('user_id', $request->user()->id);
        }

        return view('market.support', ['tickets' => $query->paginate(15), 'admin' => $this->admin($request)]);
    }

    public function createTicket(Request $request): RedirectResponse
    {
        $data = $request->validate(['subject' => 'required|string|min:5|max:160', 'body' => 'required|string|min:10|max:5000']);
        $ticket = DB::transaction(function () use ($request, $data) {
            $ticket = SupportTicket::create(['user_id' => $request->user()->id, 'subject' => $data['subject']]);
            $ticket->replies()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

            return $ticket;
        });

        return redirect()->route('support.show', $ticket)->with('success', 'Votre demande a été reçue. Vous retrouverez notre réponse ici.');
    }

    public function ticket(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id || $this->admin($request), 403);
        $ticket->load('replies.user');

        return view('market.support-ticket', ['ticket' => $ticket, 'admin' => $this->admin($request)]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id || $this->admin($request), 403);
        $data = $request->validate(['body' => 'required|string|min:2|max:5000', 'close' => 'nullable|boolean']);
        DB::transaction(function () use ($request, $ticket, $data) {
            $locked = SupportTicket::lockForUpdate()->findOrFail($ticket->id);
            $locked->replies()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
            $locked->update(['status' => $this->admin($request) ? ($request->boolean('close') ? 'closed' : 'answered') : 'open']);
        });

        return back()->with('success', 'Message enregistré.');
    }

    public function adminDossiers(Request $request): View
    {
        abort_unless($this->admin($request), 403);

        return view('market.admin-dossiers', ['users' => User::where('role', '!=', 'admin')->latest()->paginate(20)]);
    }

    public function adminDossier(Request $request, User $user): View
    {
        abort_unless($this->admin($request), 403);

        return view('market.admin-dossier', ['user' => $user, 'documents' => AccountDocument::where('user_id', $user->id)->get()]);
    }
}
