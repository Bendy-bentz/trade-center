<?php
require_once 'config/config.php';

 $pageTitle = 'Politique de Confidentialité';
include 'includes/header_public.php';
?>

<!-- HERO SIMPLE -->
<section class="bg-slate-900 text-white py-16">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-3xl md:text-4xl font-bold">Politique de Confidentialité</h1>
        <p class="mt-2 text-gray-400">Dernière mise à jour : <?= date('d/m/Y') ?></p>
    </div>
</section>

<!-- CONTENT -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="prose prose-lg max-w-none text-gray-600">
            
            <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">1. Introduction</h2>
            <p>
                La présente politique de confidentialité décrit comment Trade Center ("nous", "notre" ou "nos") collecte, utilise et protège les informations personnelles que vous nous fournissez lorsque vous utilisez notre site web et nos services de location de véhicules.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 border-b pb-2">2. Collecte des Données</h2>
            <p>Nous collectons des informations lorsque vous :</p>
            <ul class="list-disc pl-6 mt-2 space-y-2">
                <li>Créez un compte sur notre site.</li>
                <li>Effectuez une réservation.</li>
                <li>Nous contactez via notre formulaire.</li>
                <li>Naviguez sur notre site (via des cookies).</li>
            </ul>
            <p class="mt-4">Les données collectées incluent : nom, prénom, adresse email, numéro de téléphone, adresse postale, numéro de permis de conduire et informations de paiement.</p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 border-b pb-2">3. Utilisation des Données</h2>
            <p>Vos informations sont utilisées pour :</p>
            <ul class="list-disc pl-6 mt-2 space-y-2">
                <li>Traiter vos réservations et fournir nos services.</li>
                <li>Communiquer avec vous concernant votre location.</li>
                <li>Améliorer nos services et votre expérience utilisateur.</li>
                <li>Envoyer des offres promotionnelles (avec votre consentement).</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 border-b pb-2">4. Protection des Données</h2>
            <p>
                Nous mettons en œuvre des mesures de sécurité appropriées (chiffrement SSL, pare-feu, accès restreint) pour protéger vos données contre tout accès non autorisé, modification ou destruction.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 border-b pb-2">5. Partage des Données</h2>
            <p>
                Vos données ne sont pas vendues à des tiers. Elles peuvent être partagées avec des partenaires nécessaires à l'exécution du contrat (assureurs, prestataires de paiement) ou si la loi l'exige.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 border-b pb-2">6. Vos Droits</h2>
            <p>
                Conformément au RGPD, vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité de vos données. Pour exercer ces droits, contactez-nous à : <a href="mailto:privacy@tradecenter.ma" class="text-orange-500 hover:underline">privacy@tradecenter.ma</a>.
            </p>
        </div>
    </div>
</section>

<?php include 'includes/footer_public.php'; ?>