@extends('layouts.market')
@section('title','Créer un compte')
@section('content')<section class="wrap section auth-grid">
        <div>
            <span class="eyebrow">BIENVENUE CHEZ COLISFIRST</span>
            <h1 class="page-title">Inscrivez-vous<br>pour envoyer<br>
                <em>votre colis.</em>
            </h1>
            <p>Demandez votre compte pour réserver un trajet.<br>Vous pourrez ensuite candidater comme livreur.</p>
            <div class="note">Votre demande sera examinée par l’administration avant votre première réservation.</div>
        </div>
        <div class="panel">
            <h2>Créer mon compte</h2>
            <form method="post" action="{{ route('register.store') }}">
                @csrf
                <label>Nom et prénom<input name="name" required maxlength="120" value="{{ old('name') }}" autocomplete="name">
                </label>
                <label>Adresse e-mail<input type="email" name="email" required value="{{ old('email') }}" autocomplete="email">
                </label>
                <div class="two-fields">
                    <label>Téléphone<input type="tel" name="phone" required maxlength="30" value="{{ old('phone') }}">
                    </label>
                    <label>Ville en France<input name="city" required maxlength="100" value="{{ old('city') }}">
                    </label>
                </div>
                <label>Mot de passe<input type="password" name="password" required minlength="8" autocomplete="new-password">
                </label>
                <label>Confirmer le mot de passe<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="terms" value="1" required>
                    <span>J’accepte les <a href="{{ route('terms') }}">conditions de démonstration</a> et j’ai lu les informations sur <a href="{{ route('privacy') }}">mes données personnelles</a>.</span>
                </label>
                <button class="btn full">Envoyer ma demande </button>
                <a class="center-link" href="{{ route('login') }}">Déjà inscrit ? Se connecter</a>
            </form>
        </div>
    </section>
@endsection
