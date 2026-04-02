<?php
require_once 'config/config.php';

 $db = getDB();

// Récupérer les véhicules
 $stmt = $db->query("SELECT * FROM Vehicules ORDER BY id_vehicule DESC LIMIT 6");
 $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

 $pageTitle = 'Accueil';
include __DIR__ . '/includes/header_public.php';
?>

<!-- =========================================================== -->
<!-- INSÉREZ VOTRE HERO SECTION ICI (CODE EXISTANT) -->
<!-- =========================================================== -->
<!-- Styles pour l'animation du Slideshow Hero -->
<style>
    /* Animation simple de fondu pour le slideshow */
    .hero-slideshow {
        position: absolute;
        inset: 0;
        background-color: #000; /* Fond noir par défaut */
    }
    
    .hero-slide {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        opacity: 0;
        transition: opacity 1.5s ease-in-out;
    }
    
    .hero-slide.active {
        opacity: 1;
    }
</style>

<!-- HERO SECTION (Design "Votre Aventure") -->
<section class="relative h-screen flex flex-col justify-center items-center text-white overflow-hidden">
    
    <!-- Slideshow Background -->
    <div class="hero-slideshow">
        <!-- Image 1 -->
        <div class="hero-slide active" style="background-image: url('assets/images/bg-cars-1.jpg');"></div>
        <!-- Image 2 -->
        <div class="hero-slide" style="background-image: url('assets/images/black-white-view-adventure-time-with-off-road-vehicle-rough-terrain.jpg');"></div>
        <!-- Image 3 -->
        <div class="hero-slide" style="background-image: url('assets/images/3d-car-vibrant-city-night.jpg');"></div>
        
        <!-- Image 4 -->
        <div class="hero-slide" style="background-image: url('assets/images/low-angle-shot-car-with-reflection-puddle-water.jpg');"></div>
        <!-- Image 5 -->
        <div class="hero-slide" style="background-image: url('assets/images/night-drive.jpg');"></div>
        
        <!-- Overlay Sombre -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/90"></div>
    </div>

    <!-- Contenu Hero -->
    <div class="relative z-10 text-center px-4 w-full flex flex-col items-center justify-center h-full">
        
        <!-- Titre Jaune -->
        <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight mb-4 text-yellow-400 uppercase drop-shadow-md">
            Votre Aventure Commence Ici
        </h1>
        <p class="text-lg md:text-xl text-white mb-12 max-w-2xl mx-auto font-light opacity-90">
            Découvrez notre flotte de véhicules premium
        </p>

        <!-- Formulaire de Recherche (Style Barre Noire Compacte) -->
        <div class="w-full max-w-4xl px-4">
            <!-- Conteneur Noir Semi-transparent -->
            <div class="bg-black/60 backdrop-blur-md p-4 rounded-2xl border border-white/10 shadow-2xl">
                <form action="<?= BASE_URL ?>/vehicules.php" method="GET" class="flex flex-col md:flex-row gap-4 items-center">
                    
                    <!-- Lieu -->
                    <div class="relative w-full md:flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                            <i class="fas fa-map-marker-alt text-orange-500 text-xl"></i>
                        </div>
                        <input type="text" name="lieu" placeholder="Lieu de prise en charge" 
                               class="w-full bg-gray-800 border border-gray-700 rounded-xl py-4 pl-12 pr-4 text-white placeholder-gray-400 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition text-sm h-14">
                    </div>

                    <!-- Date début -->
                    <div class="relative w-full md:w-48">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                            <i class="fas fa-calendar text-orange-500 text-xl"></i>
                        </div>
                        <input type="date" name="date_debut" 
                               class="w-full bg-gray-800 border border-gray-700 rounded-xl py-4 pl-12 pr-4 text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition text-sm h-14">
                    </div>

                    <!-- Date fin -->
                    <div class="relative w-full md:w-48">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                            <i class="fas fa-calendar-check text-orange-500 text-xl"></i>
                        </div>
                        <input type="date" name="date_fin" 
                               class="w-full bg-gray-800 border border-gray-700 rounded-xl py-4 pl-12 pr-4 text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition text-sm h-14">
                    </div>

                    <!-- Bouton Rechercher -->
                    <div class="w-full md:w-auto">
                        <button type="submit" class="w-full md:w-auto bg-orange-500 hover:bg-orange-600 text-white py-4 px-10 rounded-xl font-bold transition shadow-lg hover:shadow-xl flex items-center justify-center gap-2 text-base h-14 whitespace-nowrap">
                            <i class="fas fa-search"></i>
                            <span>Rechercher</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<!-- Script Slideshow (Corrigé pour boucle infinie) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.hero-slide');
        
        if (slides.length > 1) {
            let current = 0;
            const totalSlides = slides.length;
            
            setInterval(() => {
                // Retirer la classe active de l'image actuelle
                slides[current].classList.remove('active');
                
                // Passer à l'image suivante (retour à 0 si on est à la fin)
                current = (current + 1) % totalSlides;
                
                // Ajouter la classe active à la nouvelle image
                slides[current].classList.add('active');
            }, 5000); // Toutes les 5 secondes
        }
    });
</script>

<!-- =========================================================== -->
<!-- SECTION SERVICES (Synchronisée avec services.php) -->
<!-- =========================================================== -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-sm font-bold text-orange-500 uppercase tracking-widest mb-2">Nos Services</h2>
            <h3 class="text-3xl md:text-4xl font-bold text-gray-900">Des solutions sur mesure</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- Service 1 : Location Courte Durée -->
            <div id="location-courte" class="bg-white rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2 border border-gray-100 flex flex-col">
                <!-- Image Container -->
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/young-woman-driving-car-night.jpg" 
                         alt="Location Courte Durée" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <!-- Icône Flottante -->
                    <div class="absolute bottom-4 left-4 bg-orange-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-car text-2xl"></i>
                    </div>
                </div>
                <!-- Content -->
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Location Courte Durée</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-grow">
                        Parfait pour vos déplacements ponctuels, week-ends ou voyages d'affaires. Flexibilité totale.
                    </p>
                    <a href="<?= BASE_URL ?>/vehicules.php" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Voir les véhicules <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 2 : Location Longue Durée -->
            <div id="location-lld" class="bg-white rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/side-view-woman-closing-trunk-her-car.jpg" 
                         alt="Location Longue Durée" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-blue-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-calendar-alt text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Location Longue Durée (LLD)</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-grow">
                        Une solution économique pour 1 à 36 mois. Loyers fixes et entretien inclus.
                    </p>
                    <a href="<?= BASE_URL ?>/services.php#location-lld" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Demander un devis <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 3 : Transfert Aéroport -->
            <div id="transfert-aeroport" class="bg-white rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/blurred-street-scene-city-with-plane-flying.jpg" 
                         alt="Transfert Aéroport" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-green-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-plane-arrival text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Transfert Aéroport</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-grow">
                        Soyez accueillis à votre arrivée. Service ponctuel vers les aéroports et gares.
                    </p>
                    <a href="<?= BASE_URL ?>/services.php#transfert-aeroport" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Réserver un transfert <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 4 : Location avec Chauffeur -->
            <div id="location-chauffeur" class="bg-white rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/a-propos.jpg" 
                         alt="Chauffeur Privé" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-purple-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-user-tie text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Location avec Chauffeur</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-grow">
                        Service VIP pour vos événements. Confort et discrétion garantis.
                    </p>
                    <a href="#contact" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        En savoir plus <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 5 : Location Entreprises -->
            <div id="location-entreprises" class="bg-white rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/secretaire2.jpg" 
                         alt="Flotte Entreprise" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-slate-800 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-building text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Location Entreprises</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-grow">
                        Gestion de flotte simplifiée. Tarifs préférentiels et facturation centralisée.
                    </p>
                    <a href="<?= BASE_URL ?>/contact.php" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Contact pro <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

            <!-- Service 6 : Maintenance -->
            <div id="maintenance" class="bg-white rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2 border border-gray-100 flex flex-col">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/african-american-mechanic-helping-client-with-car-maintenance-auto-repair-shop-employee-garage-facility-looking-automobile-parts-with-woman-mending-her-vehicle-engine-inspection.jpg" 
                         alt="Maintenance" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 bg-red-500 text-white p-3 rounded-xl shadow-lg">
                        <i class="fas fa-tools text-2xl"></i>
                    </div>
                </div>
                <div class="p-6 flex-grow flex flex-col">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Maintenance & Assistance</h3>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6 flex-grow">
                        Véhicules assurés et entretenus. Assistance disponible 24h/24.
                    </p>
                    <a href="tel:+33123456789" class="text-orange-500 font-semibold hover:text-orange-600 inline-flex items-center gap-2 text-sm group">
                        Appeler l'assistance <i class="fas fa-phone transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>

</section>
<!-- =========================================================== -->
<!-- NOTRE FLOTTE (Véhicules Récents) -->
<!-- =========================================================== -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-sm font-bold text-orange-500 uppercase tracking-widest mb-2">Notre Flotte</h2>
            <h3 class="text-3xl md:text-4xl font-bold text-gray-900">Véhicules Récents</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (empty($vehicules)): ?>
                <p class="col-span-3 text-center text-gray-400 py-8">Aucun véhicule disponible pour le moment.</p>
            <?php else: ?>
                <?php foreach ($vehicules as $v): ?>
                    <div class="group bg-slate-50 rounded-2xl overflow-hidden border border-gray-100 hover:shadow-2xl transition duration-500 transform hover:-translate-y-2">
                        <!-- Image -->
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?= BASE_URL ?>/uploads/vehicules/<?= htmlspecialchars($v['image'] ?? 'default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($v['marque']) ?>"
                                 class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700">
                            <!-- Badge -->
                            <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm text-orange-500 text-xs font-bold px-3 py-1 rounded-full shadow">
                                <?= htmlspecialchars($v['nom_categorie'] ?? 'Standard') ?>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="p-6">
                            <h4 class="text-lg font-bold text-gray-800 mb-1"><?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?></h4>
                            <p class="text-sm text-gray-400 mb-4"><?= htmlspecialchars($v['immatriculation'] ?? 'Disponible') ?></p>
                            
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <span class="text-xl font-bold text-orange-500"><?= number_format($v['prix_jour'], 0, ',', ' ') ?> <span class="text-sm font-normal text-gray-400">/ jour</span></span>
                                <a href="<?= BASE_URL ?>/client/reserver.php?id=<?= $v['id_vehicule'] ?>" class="bg-gray-900 hover:bg-orange-500 text-white px-4 py-2 rounded-lg transition text-sm font-semibold">
                                    Réserver
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-12">
            <a href="<?= BASE_URL ?>/vehicules.php" class="inline-block border-2 border-gray-900 text-gray-900 hover:bg-gray-900 hover:text-white px-8 py-3 rounded-full font-bold transition">
                Voir toute la flotte
            </a>
        </div>
    </div>
</section>

<!-- =========================================================== -->
<!-- SECTION "À PROPOS DE NOUS" (Avec lien vers la page) -->
<!-- =========================================================== -->
<section class="py-24 bg-white overflow-hidden">
    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row items-center gap-16">
            <!-- Image -->
            <div class="lg:w-1/2 relative">
                <div class="relative z-10 rounded-2xl overflow-hidden shadow-2xl">
                    <img src="<?= BASE_URL ?>/assets/images/a-propos.jpg" 
                         alt="Notre entreprise" class="w-full h-auto transform hover:scale-105 transition duration-700">
                </div>
                <!-- Forme décorative -->
                <div class="absolute -bottom-6 -right-6 w-64 h-64 bg-orange-100 rounded-2xl -z-10"></div>
            </div>
            
            <!-- Texte -->
            <div class="lg:w-1/2">
                <h2 class="text-sm font-bold text-orange-500 uppercase tracking-widest mb-2">À Propos De Nous</h2>
                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                    Plus qu'une location, une expérience
                </h3>
                <p class="text-gray-600 leading-relaxed mb-6">
                    Fondée avec passion, Trade Center s'est imposée comme une référence dans la location de véhicules premium. Notre mission est simple : vous offrir liberté, confort et sérénité sur la route.
                </p>
                <p class="text-gray-600 leading-relaxed mb-8">
                    Nous sélectionnons méticuleusement chaque véhicule pour garantir performance et élégance. Notre équipe dédiée est engagée à faire de chaque location un moment privilégié.
                </p>
                
                <!-- Lien vers la page À Propos -->
                <a href="<?= BASE_URL ?>/a-propos.php" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-orange-500 text-white px-8 py-4 rounded-lg font-bold transition shadow-lg group">
                    En savoir plus 
                    <i class="fas fa-arrow-right transform group-hover:translate-x-2 transition"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================== -->
<!-- SECTION TÉMOIGNAGES (IMAGES RECTANGULAIRES) -->
<!-- =========================================================== -->
<?php
// Récupérer les 3 derniers vrais avis publiés (à mettre juste au-dessus de la section HTML)
 $db = getDB();
 $recentAvis = $db->query("SELECT a.*, c.nom as client_nom, v.marque, v.modele 
                           FROM Avis a 
                           JOIN Clients c ON a.id_client = c.id_client 
                           JOIN Vehicules v ON a.id_vehicule = v.id_vehicule 
                           WHERE a.statut = 'Publié' 
                           ORDER BY a.date_avis DESC LIMIT 3")->fetchAll();
?>

<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-sm font-bold text-orange-500 uppercase tracking-widest mb-2">Témoignages</h2>
            <h3 class="text-3xl md:text-4xl font-bold text-gray-900">Ce que disent nos clients</h3>
        </div>

        <!-- VOS ANCIENS TEMOIGNAGES (INTOUCHES) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            
            <!-- Témoignage 1 -->
            <div class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition overflow-hidden flex flex-col">
                <div class="w-full h-64 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/pd-boy.png" 
                         alt="Marc Durand" 
                         class="w-full h-full object-cover object-top transform hover:scale-105 transition duration-500">
                </div>
                <div class="p-6 text-center flex-grow flex flex-col justify-between">
                    <div>
                        <div class="flex justify-center mb-3 text-yellow-400">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-gray-600 italic mb-4 leading-relaxed text-sm">
                            "Service impeccable ! La voiture était propre et récente. L'équipe est très professionnelle."
                        </p>
                    </div>
                    <div class="mt-4">
                        <h4 class="font-bold text-gray-800">Marc Durand</h4>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Entrepreneur</p>
                    </div>
                </div>
            </div>

            <!-- Témoignage 2 -->
            <div class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition overflow-hidden flex flex-col">
                <div class="w-full h-64 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/testimonial-1.jpg" 
                         alt="Sophie Martin" 
                         class="w-full h-full object-cover object-top transform hover:scale-105 transition duration-500">
                </div>
                <div class="p-6 text-center flex-grow flex flex-col justify-between">
                    <div>
                        <div class="flex justify-center mb-3 text-yellow-400">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="text-gray-600 italic mb-4 leading-relaxed text-sm">
                            "Une expérience de location premium. Le processus est simple et rapide. Merci !"
                        </p>
                    </div>
                    <div class="mt-4">
                        <h4 class="font-bold text-gray-800">Sophie Martin</h4>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Directrice Marketing</p>
                    </div>
                </div>
            </div>

            <!-- Témoignage 3 -->
            <div class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition overflow-hidden flex flex-col">
                <div class="w-full h-64 overflow-hidden">
                    <img src="<?= BASE_URL ?>/assets/images/testimonial-2.jpg" 
                         alt="Jean Lefebvre" 
                         class="w-full h-full object-cover object-top transform hover:scale-105 transition duration-500">
                </div>
                <div class="p-6 text-center flex-grow flex flex-col justify-between">
                    <div>
                        <div class="flex justify-center mb-3 text-yellow-400">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="text-gray-600 italic mb-4 leading-relaxed text-sm">
                            "Rapport qualité-prix excellent. J'ai pu louer une berline sans aucune contrainte."
                        </p>
                    </div>
                    <div class="mt-4">
                        <h4 class="font-bold text-gray-800">Jean Lefebvre</h4>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Consultant</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================================= -->
        <!-- NOUVEAU : SECTION DES VRAIS AVIS CLIENTS AVEC ÉTOILES -->
        <!-- ========================================================= -->
        <?php if (!empty($recentAvis)): ?>
        <div class="mt-16 pt-12 border-t-2 border-gray-200">
            <div class="text-center mb-10">
                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-1">Avis vérifiés</p>
                <h3 class="text-2xl font-bold text-gray-800">Derniers retours de nos locataires</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <?php foreach ($recentAvis as $avis): ?>
                <div class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition overflow-hidden flex flex-col">
                    
                    <!-- En-tête avec dégradé et Initiale (au lieu de l'image) -->
                    <div class="w-full h-24 bg-gradient-to-r from-orange-400 to-orange-600 flex items-center justify-center">
                        <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-xl font-bold text-orange-500"><?= strtoupper(substr($avis['client_nom'], 0, 1)) ?></span>
                        </div>
                    </div>
                    
                    <div class="p-6 text-center flex-grow flex flex-col justify-between">
                        <div>
                            <!-- VRAIES ÉTOILES DYNAMIQUES -->
                            <div class="flex justify-center mb-3">
                                <?= getStarsHtml($avis['note']) ?>
                            </div>
                            
                            <p class="text-gray-600 italic mb-4 leading-relaxed text-sm">
                                "<?= htmlspecialchars($avis['commentaire'] ?? 'Excellent service, je recommande.') ?>"
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <h4 class="font-bold text-gray-800"><?= htmlspecialchars($avis['client_nom']) ?></h4>
                            <p class="text-xs text-gray-400 uppercase tracking-wider">
                                <i class="fas fa-check-circle text-green-500 mr-1"></i> A loué un(e) <?= htmlspecialchars($avis['marque'] . ' ' . $avis['modele']) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <!-- FIN SECTION AVIS DYNAMIQUES -->

    </div>
</section>
<!-- CTA FINAL -->
<section class="py-20 bg-orange-100">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-orange-500 mb-4">Prêt à prendre la route ?</h2>
      <center><p class="text-black/90 mb-8 text-lg max-w-xl mx-auto">Réservez votre véhicule idéal dès maintenant et bénéficiez de nos offres exclusives.</p></center>  
        <a href="<?= BASE_URL ?>/vehicules.php" class="inline-block bg-white text-orange-600 px-8 py-4 rounded-lg font-bold shadow-lg hover:shadow-xl transform hover:scale-105 transition">
            Voir les véhicules disponibles
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer_public.php'; ?>