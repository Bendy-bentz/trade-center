<?php
require_once 'config/config.php';

 $pageTitle = 'À Propos';
include 'includes/header_public.php';
?>

<!-- HERO SECTION -->
<section class="relative h-[60vh] min-h-[500px] flex items-center justify-center overflow-hidden">
    <!-- Image de fond -->
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>/assets/images/a-propos.jpg" 
             alt="Conduite de luxe" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-transparent"></div>
    </div>
    
    <div class="container mx-auto px-4 relative z-10 text-center md:text-left">
        <div class="max-w-2xl">
            <span class="inline-block bg-orange-500 text-white px-4 py-1 rounded-full text-sm font-semibold mb-4 uppercase tracking-wider">
                Notre Histoire
            </span>
            <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
                Plus qu'une location, <br>
                <span class="text-orange-500">une expérience.</span>
            </h1>
            <p class="text-lg text-gray-200 mb-8 leading-relaxed">
                Depuis nos débuts, nous redéfinissons la location de véhicules en alliant luxe, confort et service irréprochable.
            </p>
            <a href="<?= BASE_URL ?>/vehicules.php" class="inline-block bg-orange-500 hover:bg-orange-600 text-white px-8 py-4 rounded-lg font-bold transition shadow-lg transform hover:scale-105">
                Découvrir notre flotte
            </a>
        </div>
    </div>
</section>

<!-- SECTION NOTRE MISSION -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row items-center gap-16">
            <!-- Texte -->
            <div class="lg:w-1/2">
                <h2 class="text-sm font-bold text-orange-500 uppercase tracking-widest mb-2">Notre Mission</h2>
                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                    Vous accompagner à chaque kilomètre
                </h3>
                <p class="text-gray-600 leading-relaxed mb-6">
                    Chez Trade Center, notre mission est de transformer chaque trajet en un moment privilégié. Nous croyons que la location de voiture ne doit pas être une simple transaction, mais le début d'une aventure. C'est pourquoi nous sélectionnons méticuleusement chaque véhicule et formons notre équipe pour répondre à vos attentes les plus élevées.
                </p>
                <p class="text-gray-600 leading-relaxed mb-8">
                    Notre engagement est de fournir un service transparent, flexible et personnalisé, vous permettant de vous concentrer sur l'essentiel : la route et vos souvenirs.
                </p>
                
                <!-- Stats Mini -->
                <div class="grid grid-cols-3 gap-4 border-t pt-8">
                    <div>
                        <div class="text-3xl font-bold text-gray-900">10+</div>
                        <p class="text-sm text-gray-500">Années d'expérience</p>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-gray-900">98%</div>
                        <p class="text-sm text-gray-500">Clients satisfaits</p>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-gray-900">24/7</div>
                        <p class="text-sm text-gray-500">Support dédié</p>
                    </div>
                </div>
            </div>
            
            <!-- Image -->
            <div class="lg:w-1/2 relative">
                <div class="relative z-10 rounded-2xl overflow-hidden shadow-2xl">
                    <img src="<?= BASE_URL ?>/assets/images/modern-business-center.jpg" 
                         alt="Notre équipe" class="w-full h-auto transform hover:scale-105 transition duration-500">
                </div>
                <!-- Forme décorative -->
                <div class="absolute -bottom-6 -right-6 w-64 h-64 bg-orange-100 rounded-2xl -z-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION VALEURS (Cards Dynamiques) -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-sm font-bold text-orange-500 uppercase tracking-widest mb-2">Nos Valeurs</h2>
            <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ce qui nous distingue</h3>
            <p class="text-gray-500">Des principes solides pour un service exceptionnel.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Valeur 1 -->
            <div class="group bg-white p-8 rounded-2xl shadow-sm hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-orange-500 transform hover:-translate-y-2">
                <div class="w-14 h-14 bg-orange-50 rounded-xl flex items-center justify-center mb-6 group-hover:bg-orange-500 transition">
                    <i class="fas fa-gem text-2xl text-orange-500 group-hover:text-white transition"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Excellence</h4>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Une flotte entretenue méticuleusement et des prestations haut de gamme pour une expérience sans faille.
                </p>
            </div>

            <!-- Valeur 2 -->
            <div class="group bg-white p-8 rounded-2xl shadow-sm hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-orange-500 transform hover:-translate-y-2">
                <div class="w-14 h-14 bg-orange-50 rounded-xl flex items-center justify-center mb-6 group-hover:bg-orange-500 transition">
                    <i class="fas fa-handshake text-2xl text-orange-500 group-hover:text-white transition"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Confiance</h4>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Transparence totale sur les tarifs, les conditions et l'état de nos véhicules. Aucune surprise.
                </p>
            </div>

            <!-- Valeur 3 -->
            <div class="group bg-white p-8 rounded-2xl shadow-sm hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-orange-500 transform hover:-translate-y-2">
                <div class="w-14 h-14 bg-orange-50 rounded-xl flex items-center justify-center mb-6 group-hover:bg-orange-500 transition">
                    <i class="fas fa-heart text-2xl text-orange-500 group-hover:text-white transition"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Passion</h4>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Amoureux de l'automobile, nous mettons notre passion au service de votre plaisir de conduire.
                </p>
            </div>

            <!-- Valeur 4 -->
            <div class="group bg-white p-8 rounded-2xl shadow-sm hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-orange-500 transform hover:-translate-y-2">
                <div class="w-14 h-14 bg-orange-50 rounded-xl flex items-center justify-center mb-6 group-hover:bg-orange-500 transition">
                    <i class="fas fa-bolt text-2xl text-orange-500 group-hover:text-white transition"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-800 mb-3">Réactivité</h4>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Une équipe disponible et réactive pour répondre à vos besoins en temps réel.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- PARALLAX / CITATION -->
<section class="relative py-32 bg-fixed bg-cover bg-center" style="background-image: url('<?= BASE_URL ?>/assets/images/low-angle-shot-car-with-reflection-puddle-water.jpg');">
    <div class="absolute inset-0 bg-black/60"></div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <i class="fas fa-quote-left text-5xl text-orange-500 mb-6 opacity-50"></i>
        <blockquote class="text-3xl md:text-4xl font-bold text-white max-w-4xl mx-auto leading-relaxed">
            "Chez Trade Center, nous ne louons pas seulement des voitures, nous offrons les clés de votre liberté."
        </blockquote>
        <p class="mt-6 text-gray-300 text-lg font-medium">— L'équipe Trade Center</p>
    </div>
</section>

<!-- SECTION GALERIE / ÉQUIPE (Optionnel) -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900">Notre Équipe</h2>
            <p class="text-gray-500 mt-2">Les visages derrière votre satisfaction</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            <!-- Membre 1 -->
            <div class="text-center group">
                <div class="relative w-48 h-48 mx-auto rounded-full overflow-hidden mb-4 border-4 border-gray-100 group-hover:border-orange-500 transition duration-300">
                    <img src="<?= BASE_URL ?>/assets/images/CEO.png" 
                         alt="Directeur" class="w-full h-full object-cover">
                </div>
                <h4 class="font-bold text-gray-800">Mompremier Rubendy</h4>
                <p class="text-sm text-orange-500">Fondateur & CEO</p>
            </div>
            
            <!-- Membre 2 -->
            <div class="text-center group">
                <div class="relative w-48 h-48 mx-auto rounded-full overflow-hidden mb-4 border-4 border-gray-100 group-hover:border-orange-500 transition duration-300">
                    <img src="<?= BASE_URL ?>/assets/images/operations-manager.png" 
                         alt="Responsable" class="w-full h-full object-cover">
                </div>
                <h4 class="font-bold text-gray-800">Marie Lefebvre</h4>
                <p class="text-sm text-orange-500">Directrice des Opérations</p>
            </div>

            <!-- Membre 3 -->
            <div class="text-center group">
                <div class="relative w-48 h-48 mx-auto rounded-full overflow-hidden mb-4 border-4 border-gray-100 group-hover:border-orange-500 transition duration-300">
                    <img src="<?= BASE_URL ?>/assets/images/mecanicien.png" 
                         alt="Responsable Flotte" class="w-full h-full object-cover">
                </div>
                <h4 class="font-bold text-gray-800">CELIFIN Jodel</h4>
                <p class="text-sm text-orange-500">Responsable Flotte</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="py-20 bg-orange-100">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-orange-500 mb-4">Prêt à nous rejoindre ?</h2>
        <p class="text-black/90 mb-8 text-lg">Réservez votre véhicule idéal dès maintenant.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="<?= BASE_URL ?>/vehicules.php" class="bg-white text-orange-600 px-8 py-4 rounded-lg font-bold hover:bg-orange-100 transition shadow-lg inline-flex items-center gap-2">
                <i class="fas fa-car"></i> Voir les véhicules
            </a>
            <a href="<?= BASE_URL ?>/contact.php" class="bg-orange-500 border-2 border-white text-white px-8 py-4 rounded-lg font-bold hover:bg-black hover:text-orange-600 transition inline-flex items-center gap-2">
                <i class="fas fa-envelope"></i> Contactez-nous
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer_public.php'; ?>