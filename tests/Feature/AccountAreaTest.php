<?php

use App\Models\AccountDocument;
use App\Models\SupportTicket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores private identity documents and resets review when replaced', function (string $disk) {
    config(['filesystems.private_documents' => $disk]);
    Storage::fake($disk);
    $user = marketUser();
    $other = marketUser();
    $admin = marketUser('admin');
    $this->actingAs($user)->get('/mon-dossier')->assertOk();
    $this->post(route('account.upload'), ['kind' => 'identity_front', 'document' => UploadedFile::fake()->create('identity.pdf', 20, 'application/pdf'), 'expires_at' => today()->addYear()->format('Y-m-d')])->assertRedirect();
    $doc = AccountDocument::firstOrFail();
    $path = $doc->path;
    $version = hash('sha256', $path);
    Storage::disk($disk)->assertExists($path);
    $this->get(route('account.download', $doc))->assertOk();
    $this->actingAs($other)->get(route('account.download', $doc))->assertForbidden();
    $this->post(route('account.review', $doc), ['status' => 'approved', 'review_note' => 'Pièce examinée', 'version' => $version])->assertForbidden();
    $this->actingAs($admin)->get(route('account.admin.show', $user))->assertOk();
    $this->post(route('account.review', $doc), ['status' => 'approved', 'review_note' => 'Pièce examinée', 'version' => $version])->assertRedirect();
    expect($doc->fresh()->status)->toBe('approved');
    $this->actingAs($user)->post(route('account.upload'), ['kind' => 'identity_front', 'document' => UploadedFile::fake()->create('new.pdf', 20, 'application/pdf')])->assertRedirect();
    Storage::disk($disk)->assertMissing($path);
    expect($doc->fresh()->status)->toBe('pending')->and($doc->fresh()->review_note)->toBeNull();
    $this->actingAs($admin)->post(route('account.review', $doc), ['status' => 'approved', 'review_note' => 'Ancienne pièce', 'version' => $version])->assertStatus(409);
})->with(['local', 's3']);

it('rejects invalid uploads and client business document submissions', function () {
    Storage::fake('local');
    $this->actingAs(marketUser());
    $this->post(route('account.upload'), ['kind' => 'identity_front', 'document' => UploadedFile::fake()->create('script.html', 20, 'text/html')])->assertSessionHasErrors('document');
    $this->post(route('account.upload'), ['kind' => 'business', 'document' => UploadedFile::fake()->create('company.pdf', 20, 'application/pdf')])->assertSessionHasErrors('kind');
    $this->post(route('account.upload'), ['kind' => 'identity_front', 'document' => UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('document');
    expect(AccountDocument::count())->toBe(0);
});

it('saves company details only for the authenticated driver', function () {
    $driver = marketUser('driver');
    $this->actingAs($driver);
    $data = ['address' => '12 rue Exemple', 'postal_code' => '75001', 'country' => 'France', 'business_type' => 'societe'];
    $this->post(route('account.save'), $data)->assertSessionHasErrors(['company_name', 'registration', 'legal_form', 'representative', 'company_address']);
    $this->post(route('account.save'), $data + ['company_name' => 'Transport Exemple', 'registration' => '12345678901234', 'legal_form' => 'SAS', 'representative' => 'Marie Exemple', 'company_address' => '12 rue Exemple', 'role' => 'admin'])->assertRedirect();
    expect($driver->fresh()->account_details['company_name'])->toBe('Transport Exemple')->and($driver->fresh()->role)->toBe('driver');
    $this->get('/mon-dossier')->assertOk()->assertSee('Transport Exemple');
    $this->get('/dashboard')->assertOk()->assertSee('Ouvrir mon dossier')->assertSee('Contacter le support');
});

it('keeps support conversations private and allows admin replies and reopening', function () {
    $user = marketUser();
    $other = marketUser();
    $admin = marketUser('admin');
    $this->actingAs($user)->post(route('support.store'), ['subject' => 'Question sur mon colis', 'body' => 'Quand aura lieu le ramassage ?'])->assertRedirect();
    $ticket = SupportTicket::firstOrFail();
    $this->get(route('support.show', $ticket))->assertOk()->assertSee('Quand aura lieu');
    $this->actingAs($other)->get(route('support.show', $ticket))->assertForbidden();
    $this->post(route('support.reply', $ticket), ['body' => 'Intrusion'])->assertForbidden();
    $this->get('/support')->assertOk()->assertDontSee('Question sur mon colis');
    $this->actingAs($admin)->get('/support')->assertOk()->assertSee('Question sur mon colis');
    $this->post(route('support.reply', $ticket), ['body' => 'Le livreur va vous contacter.', 'close' => 1])->assertRedirect();
    expect($ticket->fresh()->status)->toBe('closed');
    $this->actingAs($user)->get(route('support.show', $ticket))->assertOk()->assertSee('Le livreur va vous contacter.');
    $this->post(route('support.reply', $ticket), ['body' => 'Merci, une autre question.', 'close' => 1])->assertRedirect();
    expect($ticket->fresh()->status)->toBe('open')->and($ticket->replies()->count())->toBe(3);
});

it('identifies expired documents and prevents their approval', function () {
    $user = marketUser();
    $doc = AccountDocument::create(['user_id' => $user->id, 'kind' => 'identity_front', 'path' => 'account-documents/'.$user->id.'/old.pdf', 'expires_at' => today()->subDay(), 'status' => 'approved']);
    expect($doc->statusLabel())->toBe('Expiré — à remplacer');
    $this->actingAs(marketUser('admin'))->post(route('account.review', $doc), ['status' => 'approved', 'review_note' => 'Pièce expirée', 'version' => hash('sha256', $doc->path)])->assertStatus(422);
});
