<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Trouvez un trajet depuis la France et confiez votre colis à un livreur de la communauté Colisfirst.">
        <title>
            @yield('title', 'Livraison de colis France – Maghreb') · Colisfirst</title>
        <link rel="stylesheet" href="{{ asset('css/market.css').'?v=4' }}">
        <script src="{{ asset('js/market.js') }}?v=4" defer>
        </script>
    </head>
    <body>
        <a href="#main-content" class="skip-link">Aller au contenu</a>
        
        <header class="header">
            <div class="wrap nav">
                <a class="brand" href="{{ route('home') }}" aria-label="ColisFirst, accueil"><img src="{{ asset('images/colisfirstlogo.png') }}" alt="ColisFirst" width="2172" height="724"></a>
                <nav aria-label="Navigation principale">
                    <a href="{{ route('trips.index') }}" @class(['active'=>request()->routeIs('trips.*')])>Trouver un trajet</a>
                    <a href="{{ route('how') }}">Comment ça marche</a>
                    <a href="{{ route('drivers.apply') }}">Devenir livreur</a>
                </nav>
                <div class="nav-end">
                    @auth<a href="{{ route('dashboard') }}" class="btn small outline">Mon espace</a>
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button class="text-button" aria-label="Se déconnecter">Déconnexion</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">Connexion</a>
                        <a class="btn small" href="{{ route('register') }}">Inscription</a>
                    @endauth
                </div>
            </div>
        </header>
        <main id="main-content">
            @if(session('success'))
                <div class="wrap">
                    <div class="alert success" role="status">{{ session('success') }}</div>
                </div>
            @endif
            @if($errors->any())
                <div class="wrap">
                    <div class="alert error" role="alert">
                        <strong>Quelques informations sont à vérifier :</strong>
                        <ul>
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @yield('content')</main>
        <footer>
            <div class="wrap footer-grid">
                <div>
                    <a class="brand" href="{{ route('home') }}" aria-label="ColisFirst, accueil"><img src="{{ asset('images/colisfirstlogo.png') }}" alt="ColisFirst" width="2172" height="724"></a>
                    <p>Livraison de colis entre particuliers.<br>Au départ de la France.</p>
                    <small>France  Algérie · Maroc · Tunisie</small>
                </div>
                <div>
                    <strong>Le service</strong>
                    <a href="{{ route('trips.index') }}">Les prochains départs</a>
                    <a href="{{ route('drivers.apply') }}">Devenir livreur</a>
                    <a href="{{ route('how') }}">Comment ça marche</a>
                </div>
                <div>
                    <strong>Informations utiles</strong>
                    <a href="{{ route('trust') }}">Sécurité et colis autorisés</a>
                    <a href="{{ route('terms') }}">Conditions d’utilisation</a>
                    <a href="{{ route('privacy') }}">Données personnelles</a>
                    <a href="{{ route('legal') }}">Mentions légales</a>
                    <a href="{{ route('cookies') }}">Cookies</a>
                    <a href="{{ route('accessibility') }}">Accessibilité</a>
                    <a href="{{ route('regulations') }}">Cadre réglementaire</a>
                    <button class="text-button" type="button" data-cookie-open>Gérer les cookies</button>
                </div>
            </div>
            <div class="wrap footer-bottom">
                <span>© {{ date('Y') }} Colisfirst</span>
                <span>France · Algérie · Maroc · Tunisie</span>
            </div>
        </footer>
        @include('market.partials.cookies')
    </body>
</html>
