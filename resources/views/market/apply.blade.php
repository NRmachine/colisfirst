@extends('layouts.market')
@section('title','Devenir livreur')
@section('content')
    <section class="wrap section narrow">
        <span class="eyebrow">CANDIDATURE LIVREUR</span>
        <h1 class="page-title">Devenir livreur</h1>
        <p>Nous examinons chaque dossier avant d’autoriser une publication. Pour cette démonstration, fournissez uniquement des documents fictifs.</p>
        @if($application && in_array($application->status,['pending','approved']))
            <div class="panel">
                <h2>{{ $application->status==='approved'?'Votre candidature est approuvée.':'Votre dossier est en cours d’examen.' }}</h2>
                <p>{{ $application->review_note }}</p>
                <a class="btn" href="{{ route('dashboard') }}">Retour à mon espace</a>
            </div>
        @else
            <form class="panel" method="post" enctype="multipart/form-data" action="{{ route('drivers.store') }}">
                @csrf
                <label>Votre situation<select name="business_type">
                        <option value="particulier" @selected(old('business_type')==='particulier')>Particulier — éligibilité à examiner</option>
                        <option value="professionnel" @selected(old('business_type')==='professionnel')>Micro-entreprise / professionnel</option>
                    </select>
                </label>
                <label>SIRET (14 chiffres, obligatoire pour un professionnel)<input name="business_number" value="{{ old('business_number') }}" maxlength="14">
                </label>
                <label>Votre expérience et vos trajets habituels<textarea name="experience" required minlength="20" maxlength="2000">{{ old('experience') }}</textarea>
                </label>
                <h3>Les pièces de votre dossier</h3>
                <p class="muted">PDF, JPG ou PNG · 4 Mo maximum par document. Accès réservé à l’administration.</p>
                @foreach(['identity'=>'Justificatif d’identité fictif','insurance'=>'Justificatif d’assurance couvrant l’activité (fictif)','license'=>'Permis / justificatif adapté au transport envisagé (fictif)','business'=>'Justificatif d’activité et autorisations de transport (professionnel)'] as $field=>$label)<label>{{ $label }}<input type="file" name="{{ $field }}" accept=".pdf,.jpg,.jpeg,.png" @required($field!=='business')>
                    </label>
                @endforeach
                <div class="note">Une micro-entreprise ne suffit pas à autoriser toute activité de transport. Le statut, les autorisations, les assurances et les formalités des pays traversés doivent être vérifiés avant une utilisation réelle.</div>
                <label class="checkbox">
                    <input type="checkbox" name="agreement" value="1" required>
                    <span>Je confirme l’exactitude de mon dossier, accepte les règles d’envoi et m’engage à refuser tout colis interdit ou dont le contenu n’est pas déclaré.</span>
                </label>
                <button class="btn full">Déposer ma candidature </button>
            </form>
        @endif
    </section>
@endsection
