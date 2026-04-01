<?php
require_once 'config/config.php';

 $pageTitle = 'Nos Services';
include 'includes/header_public.php';
?>

<!-- HERO SECTION (Hauteur augmentée) -->
<section class="relative min-h-[70vh] bg-slate-900 text-white overflow-hidden flex items-center justify-center">
    <!-- Image de fond -->
    <div class="absolute inset-0 z-0">
        <img src="<?= BASE_URL ?>/assets/images/secretaire1.jpg" alt="Background" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/70 to-black/50"></div>
    </div>
    
    <div class="container mx-auto px-4 relative z-10 text-center py-20">
        <span class="inline-block bg-orange-500 text-white px-4 py-1 rounded-full text-sm font-semibold mb-4 uppercase tracking-wider">
            Nos Expertises
        </span>
        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">
            Des Services <span class="text-orange-500">Sur Mesure</span>
        </h1>
        <p class="text-lg text-black max-w-2xl mx-auto">
            De la location journalière à la gestion de flotte d'entreprise, Trade Center vous accompagne avec des solutions flexibles et premium.
        </p>
    </div>
</section>

<!-- LISTE DES SERVICES (AVEC ID ET IMAGES) -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- Service 1 : Location Courte Durée -->
            <div id="location-courte" class="bg-white rounded-3xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 flex flex-col">
                <!-- Image Container -->
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/young-woman-driving-car-night.jpg" 
                         alt="Location Courte Durée" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-orange-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-car text-2xl"></i>
                    </div>
                </div>
                <!-- Content -->
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Location Courte Durée</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4 flex-grow">
                        Parfait pour vos déplacements ponctuels, week-ends ou voyages d'affaires. Flexibilité totale, réservation rapide et kilométrage illimité disponible.
                    </p>
                    <a href="<?= BASE_URL ?>/vehicules.php" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Voir les véhicules <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 2 : Location Longue Durée -->
            <div id="location-lld" class="bg-white rounded-3xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/side-view-woman-closing-trunk-her-car.jpg" 
                         alt="Location Longue Durée" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-blue-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-calendar-alt text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Location Longue Durée (LLD)</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4 flex-grow">
                        Une solution économique pour une durée de 1 à 36 mois. Loyers fixes, entretien inclus et possibilité d'achat en fin de contrat.
                    </p>
                    <a href="#contact" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Demander un devis <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 3 : Transfert Aéroport -->
            <div id="transfert-aeroport" class="bg-white rounded-3xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/blurred-street-scene-city-with-plane-flying.jpg" 
                         alt="Transfert Aéroport" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-green-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-plane-arrival text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Transfert Aéroport</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4 flex-grow">
                        Soyez accueillis à votre arrivée par nos chauffeurs. Service ponctuel et discret vers toutes les gares et aéroports.
                    </p>
                    <a href="#contact" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Réserver un transfert <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 4 : Location avec Chauffeur -->
            <div id="location-chauffeur" class="bg-white rounded-3xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/driver-with-opened-car-door.jpg" 
                         alt="Chauffeur Privé" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-purple-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-user-tie text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Location avec Chauffeur</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4 flex-grow">
                        Profitez d'un service VIP pour vos événements, réunions d'affaires ou occasions spéciales. Confort et discrétion garantis.
                    </p>
                    <a href="#contact" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        En savoir plus <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 5 : Location pour Entreprises -->
            <div id="location-entreprises" class="bg-white rounded-3xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/secretaire2.jpg" 
                         alt="Flotte Entreprise" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-slate-800 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-building text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Location pour Entreprises</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4 flex-grow">
                        Gérez votre flotte de véhicules simplement. Tarifs préférentiels, facturation centralisée et outils de gestion dédiés.
                    </p>
                    <a href="#contact" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Contact pro <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 6 : Maintenance & Assistance -->
            <div id="maintenance" class="bg-white rounded-3xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/african-american-mechanic-helping-client-with-car-maintenance-auto-repair-shop-employee-garage-facility-looking-automobile-parts-with-woman-mending-her-vehicle-engine-inspection.jpg" 
                         alt="Maintenance" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-red-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-tools text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Maintenance & Assistance 24/7</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-4 flex-grow">
                        Tous nos véhicules sont assurés et entretenus. Une panne ? Notre équipe d'assistance est disponible 24h/24.
                    </p>
                    <a href="tel:+509 48360967" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Appeler l'assistance <i class="fas fa-phone transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- CTA CONTACT -->
<section id="contact" class="py-20 bg-orange-100">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Vous avez un projet spécifique ?</h2>
        <p class="text-gray-90 mb-8 max-w-xl mx-auto">Notre équipe est à votre disposition pour construire une offre sur mesure adaptée à vos besoins.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="tel:+509 48360967" class="bg-gray-100 text-orange-600 px-8 py-4 rounded-lg font-bold transition shadow-lg hover:shadow-xl inline-flex items-center gap-2">
                <i class="fas fa-phone-alt"></i> Appelez-nous
            </a>
            <a href="<?= BASE_URL ?>/vehicules.php" class="bg-orange-500 border-2 border-white text-white px-8 py-4 rounded-lg font-bold transition hover:bg-white hover:text-orange-600 inline-flex items-center gap-2">
                <i class="fas fa-car"></i> Réserver en ligne
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer_public.php'; ?>