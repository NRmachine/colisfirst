@extends('layouts.market')
@section('title', 'Sécurité des envois')
@section('content')
    <section class="wrap section narrow prose">
        <span class="eyebrow">COLISFIRST, EN TOUTE TRANSPARENCE</span>
        <h1 class="page-title">La confiance se construit.</h1>
        <figure class="trust-photo"><img src="{{ asset('images/preparer-colis.png') }}" alt="Deux personnes préparent un colis de vêtements et de livres" width="1536" height="1024" loading="lazy"><figcaption>Illustration générée ; ces personnes ne représentent pas des clients réels.</figcaption></figure>
        <h2>Déclarer, vérifier, refuser si nécessaire</h2>
        <p>Décrivez chaque objet, son poids et sa valeur. Ne remettez pas un colis dont vous ignorez le contenu. En cas de contenu douteux, le transport doit être refusé. Les stupéfiants illicites, armes, explosifs, contrefaçons et produits dangereux sont exclus de la plateforme.</p>
        <p>Les aliments, médicaments, plantes et autres produits réglementés nécessitent des vérifications spécifiques. Pour la démonstration, limitez les envois fictifs aux vêtements et livres.</p>
        <h2>Des règles dans chaque pays</h2>
        <p>Les obligations d’exportation, de transit et d’importation dépendent du trajet et des marchandises. Une déclaration sur le site ne remplace pas les documents douaniers.</p>
        <h2>Des livreurs examinés</h2>
        <p>Le dossier prévoit l’identité, l’assurance adaptée, un justificatif pertinent pour le transport envisagé et, pour les professionnels, l’activité et les autorisations. Cette liste de travail n’est pas une validation juridique. Les profils de test sont fictifs.</p>
        <h2>Une responsabilité qui ne disparaît pas</h2>
        <p>Une case cochée ou une signature ne garantit aucune immunité à l’expéditeur, au livreur ou à la plateforme. La qualification de l’activité et les responsabilités doivent être examinées avant l’ouverture réelle.</p>
        <h2>Sources officielles à consulter</h2>
        <ul>
            <li>
                <a href="https://www.douane.gouv.fr/fiche/restriction-de-circulation-ou-interdiction-de-certaines-marchandises">Douane française : marchandises interdites et réglementées</a>
            </li>
            <li>
                <a href="https://www.ecologie.gouv.fr/politiques-publiques/acces-exercice-profession-transporteur-marchandises">Ministère des Transports : accès à la profession de transporteur</a>
            </li>
        </ul>
        <p>Les règles du pays de destination doivent également être vérifiées. Ces sources françaises ne suffisent pas à autoriser un trajet international.</p>
    </section>
@endsection
