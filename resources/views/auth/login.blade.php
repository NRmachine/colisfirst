@extends('layouts.market')
@section('title','Connexion')
@section('content')
    <section class="wrap section auth-grid">
        <div>
            <span class="eyebrow">MON COMPTE</span>
            <h1 class="page-title">Vos réservations. <br>Vos trajets. <br>
                <em>Votre suivi.</em>
            </h1>
            <p>Retrouvez vos réservations et gardez le contact<br>avec les personnes qui transportent vos colis.</p>
            <div class="note">Première visite ? <a href="{{ route('register') }}">Créer un compte </a>
            </div>
        </div>
        <div class="panel">
            <h2>Se connecter</h2>
            <form method="post" action="{{ route('login.store') }}">
                @csrf
                <label>Adresse e-mail<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                </label>
                <label>Mot de passe<input type="password" name="password" required autocomplete="current-password">
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="remember">
                    <span>Rester connecté</span>
                </label>
                <button class="btn full">Connexion </button>
                <a class="center-link" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
            </form>
            @if(config('marketplace.demo') && !app()->isProduction())
                <div class="note">
                    <strong>Comptes de démonstration</strong>
                    <p>Client : client@colisfirst.test<br>Livreur : livreur@colisfirst.test<br>Administration : admin@colisfirst.test</p>
                    <small>Mot de passe commun : DemoColis2026!</small>
                </div>
            @endif
        </div>
    </section>
@endsection
