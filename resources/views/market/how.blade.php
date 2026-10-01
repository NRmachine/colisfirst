@extends('layouts.market')
@section('title', 'Comment ça marche')
@section('content')
    <section class="wrap section narrow prose">
        <span class="eyebrow">COLISFIRST, EN TOUTE TRANSPARENCE</span>
        <h1 class="page-title">Un colis, trois étapes.</h1>
        <h2>1. Trouvez un départ</h2>
        <p>Consultez les trajets depuis la France vers l’Algérie, le Maroc ou la Tunisie. Vérifiez la date, les lieux de ramassage, la capacité et les conditions du livreur.</p>
        <h2>2. Faites une demande</h2>
        <p>Créez votre compte et attendez sa validation. Déclarez le contenu, le poids entier en kilogrammes, la valeur du colis et les coordonnées du destinataire. Le prix est calculé à partir du tarif du livreur, avec 12 % de frais de service en supplément.</p>
        <h2>3. Confirmez et suivez</h2>
        <p>Après acceptation du livreur, simulez le paiement dans votre espace. Les coordonnées et la messagerie deviennent disponibles. Le livreur confirme la prise en charge ; vous confirmez ensuite la réception.</p>
        <h2>Pourquoi rester sur Colisfirst ?</h2>
        <p>Le prix, la déclaration, les étapes et les échanges restent réunis dans une réservation. Les frais de service financeraient le fonctionnement, la modération et l’accompagnement. Ils ne constituent pas une assurance.</p>
        <details>
            <summary>Comment sont calculés les frais ?</summary>
            <p>Pour 5 kg à 8 €/kg : 40 € de transport + 4,80 € de frais = 44,80 €. Dans cette version, tout paiement et toute recette sont simulés.</p>
        </details>
        <details>
            <summary>Est-ce que le colis est assuré ?</summary>
            <p>Aucune assurance de plateforme n’est activée dans cette démonstration. Une couverture réelle, ses exclusions et plafonds doivent être définis avant le lancement.</p>
        </details>
        <details>
            <summary>Comment devenir livreur ?</summary>
            <p>Déposez une candidature depuis votre compte. L’administrateur examine les pièces et l’éligibilité avant de vous autoriser à publier.</p>
        </details>
        <details>
            <summary>Puis-je annuler ?</summary>
            <p>Vous pouvez annuler une demande en attente ou acceptée avant le paiement simulé. Un parcours de litige et de remboursement réel reste à mettre en place avant ouverture commerciale.</p>
        </details>
    </section>
@endsection
