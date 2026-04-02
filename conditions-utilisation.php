<?php
require_once 'config/config.php';

 $pageTitle = 'Conditions d\'Utilisation';
include 'includes/header_public.php';
?>

<!-- HERO SIMPLE -->
<section class="bg-slate-800 text-white py-16">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-3xl md:text-4xl font-bold">Conditions Générales d'Utilisation</h1>
        <p class="mt-2 text-gray-300">Veuillez lire attentivement ces conditions avant d'utiliser nos services.</p>
    </div>
</section>

<!-- CONTENT -->
<section class="py-16 bg-slate-50">
    <div class="container mx-auto px-4 max-w-4xl bg-white p-8 md:p-12 shadow-sm rounded-xl">
        <div class="prose prose-lg max-w-none text-gray-600">

            <h2 class="text-2xl font-bold text-gray-800 mb-4">Article 1 : Objet</h2>
            <p>
                Les présentes conditions générales régissent les relations entre la société Trade Center et ses clients pour la location de véhicules. Tout client effectuant une réservation accepte sans réserve ces conditions.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Article 2 : Conditions de Location</h2>
            <p>Pour louer un véhicule, le client doit :</p>
            <ul class="list-disc pl-6 mt-2 space-y-2">
                <li>Être âgé de 21 ans minimum et posséder un permis de conduire valide depuis au moins 2 ans.</li>
                <li>Présenter une pièce d'identité en cours de validité.</li>
                <li>Fournir une carte bancaire au nom du conducteur principal pour la caution.</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Article 3 : Utilisation du Véhicule</h2>
            <p>
                Le véhicule doit être utilisé en bon père de famille. Il est strictement interdit de :
            </p>
            <ul class="list-disc pl-6 mt-2 space-y-2">
                <li>Conduire en état d'ivresse ou sous l'emprise de stupéfiants.</li>
                <li>Sous-louer le véhicule.</li>
                <li>Utiliser le véhicule pour des compétitions ou hors des routes carrossables.</li>
                <li>Dépasser les limites de kilométrage prévues au contrat (si applicable).</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Article 4 : Tarifs et Paiement</h2>
            <p>
                Les tarifs affichés sont exprimés en Dirhams (MAD) ou en Euros selon le choix. Le paiement s'effectue au moment de la réservation ou lors de la prise en charge. Une caution sera préautorisée sur la carte bancaire du client.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Article 5 : Assurance</h2>
            <p>
                Tous nos véhicules sont assurés en responsabilité civile et risques locatifs. Une franchise reste à la charge du client en cas de sinistre responsable. Des options d'assurance complémentaires sont proposées pour réduire cette franchise.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Article 6 : Annulation</h2>
            <p>
                Toute annulation doit être notifiée par écrit. Les conditions de remboursement sont définies selon le délai d'annulation. En cas de non-présentation, la totalité du montant de la location pourra être facturée.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Article 7 : Responsabilité</h2>
            <p>
                Trade Center ne saurait être tenu responsable en cas de retard dans la mise à disposition du véhicule dû à un cas de force majeure. Le client est responsable des infractions commises durant la durée de la location.
            </p>

        </div>
    </div>
</section>

<?php include 'includes/footer_public.php'; ?>