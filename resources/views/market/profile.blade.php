@extends('layouts.market')
@section('title','Mon profil')
@section('content')
    <section class="wrap section narrow">
        <a class="back" href="{{ route('dashboard') }}"> Mon espace</a>
        <h1 class="page-title">Mes coordonnées.</h1><p><a href="{{ route('account.dossier') }}">Mon adresse, mes documents et mon activité</a></p>
        <form class="panel" method="post" action="{{ route('profile.save') }}">
            @csrf
            <label>Nom complet<input name="name" value="{{ old('name',$user->name) }}" required maxlength="120">
            </label>
            <label>Téléphone<input type="tel" name="phone" value="{{ old('phone',$user->phone) }}" required maxlength="30">
            </label>
            <label>Ville<input name="city" value="{{ old('city',$user->city) }}" required maxlength="100">
            </label>
            <p>E-mail du compte : {{ $user->email }}</p>
            <button class="btn">Enregistrer les modifications</button>
        </form>
    <div class="note"><a href="{{ route('privacy.requests') }}">Télécharger mes données ou exercer mes droits</a></div></section>
@endsection
