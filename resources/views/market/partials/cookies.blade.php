@if(!request()->cookie('cf_cookie_notice'))
<section class="cookie-notice" aria-label="Information sur les cookies">
    <div><strong>Vos cookies, simplement.</strong><p>Seuls des cookies nécessaires à la connexion et à la sécurité sont utilisés. Aucun cookie publicitaire ni outil de suivi d’audience.</p></div>
    <div class="actions"><a href="{{ route('cookies') }}">En savoir plus</a><form method="post" action="{{ route('cookies.save') }}">@csrf<input type="hidden" name="choice" value="essential"><button class="btn small">Compris</button></form></div>
</section>
@endif
<dialog id="cookie-settings" aria-labelledby="cookie-title">
    <h2 id="cookie-title">Paramètres des cookies</h2>
    <p><strong>Cookies essentiels : toujours actifs.</strong> Ils assurent la connexion, la protection des formulaires et la mémorisation de cet avis.</p>
    <p><strong>Publicité et mesure d’audience : aucun outil installé.</strong> Il n’y a donc aucun consentement facultatif à donner ou retirer actuellement.</p>
    <p>Le paiement par carte s’ouvre sur une page sécurisée uniquement lorsque vous le demandez. <a href="{{ route('cookies') }}">Lire la politique cookies</a>.</p>
    <form method="post" action="{{ route('cookies.save') }}">@csrf<input type="hidden" name="choice" value="essential"><div class="actions"><button class="btn">Conserver uniquement les essentiels</button><button class="btn outline" type="button" data-cookie-close>Fermer</button></div></form>
</dialog>
