<?php
require_once __DIR__ . '/config/config.php';

 $db = getDB();

// Filtre par catégorie
 $categorieId = $_GET['categorie'] ?? null;

 $sql = "SELECT v.*, c.nom_categorie FROM Vehicules v 
        LEFT JOIN Categories_Vehicules c ON v.id_categorie = c.id_categorie 
        WHERE v.etat = 'Disponible'";

if ($categorieId) {
    $stmt = $db->prepare($sql . " AND v.id_categorie = ? ORDER BY v.marque");
    $stmt->execute([$categorieId]);
    $vehicules = $stmt->fetchAll();
} else {
    $vehicules = $db->query($sql . " ORDER BY v.marque")->fetchAll();
}

// Récupérer les catégories pour le menu
 $categories = $db->query("SELECT * FROM Categories_Vehicules ORDER BY nom_categorie")->fetchAll();

 $pageTitle = 'Notre Flotte';
include __DIR__ . '/includes/header_public.php';
?>

<!-- HERO SECTION AVEC SLIDESHOW -->
<style>
    .hero-slideshow-vehicules {
        position: absolute;
        inset: 0;
        background-color: #000;
    }
    .slide-v {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        opacity: 0;
        transition: opacity 1.5s ease-in-out;
    }
    .slide-v.active { opacity: 1; }
</style>

<!-- HAUTEUR AUGMENTÉE : min-h-[80vh] (80% de l'écran) -->
<section class="relative min-h-[80vh] flex items-center justify-center overflow-hidden">
    <!-- Slideshow Background -->
    <div class="hero-slideshow-vehicules">
        <div class="slide-v active" style="background-image: url('<?= BASE_URL ?>/assets/images/bg-cars-1.jpg');"></div>
        <div class="slide-v" style="background-image: url('<?= BASE_URL ?>/assets/images/bg-cars-2.jpg');"></div>
        <div class="slide-v" style="background-image: url('<?= BASE_URL ?>/assets/images/mercedes_classe_e.jpg');"></div>
         <div class="slide-v" style="background-image: url('<?= BASE_URL ?>/assets/images/woman.jpg');"></div>
        <div class="slide-v" style="background-image: url('<?= BASE_URL ?>/assets/images/3d-car-vibrant-city-night.jpg');"></div>
        
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/70 to-black/40"></div>
    </div>

    <!-- Contenu Hero -->
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block bg-orange-500/20 text-orange-400 px-4 py-1 rounded-full text-sm font-semibold mb-4 uppercase tracking-widest border border-orange-500/30">
            Notre Flotte
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
            Trouvez le véhicule <br>
            <span class="text-orange-500">idéal pour vous</span>
        </h1>
        <p class="text-lg text-gray-300 max-w-2xl mx-auto">
            Découvrez notre sélection de véhicules récents, entretenus et prêts à partir.
        </p>
    </div>
    
    <!-- Forme décorative en bas -->
    <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-slate-50 to-transparent z-10"></div>
</section>
<!-- =========================================================== -->
<!-- FILTRES CATEGORIES (Sticky) -->
<!-- =========================================================== -->
<section class="sticky top-24 z-30 py-4 bg-slate-50/95 backdrop-blur-sm border-b border-gray-200 shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="vehicules.php" class="px-6 py-2.5 rounded-full text-sm font-bold transition 
                <?= !$categorieId 
                    ? 'bg-orange-500 text-white shadow-lg shadow-orange-200 scale-105' 
                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 hover:border-orange-500' ?>">
                <i class="fas fa-th-large mr-2"></i> Tous
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="?categorie=<?= $cat['id_categorie'] ?>" 
               class="px-6 py-2.5 rounded-full text-sm font-bold transition 
               <?= $categorieId == $cat['id_categorie'] 
                    ? 'bg-orange-500 text-white shadow-lg shadow-orange-200 scale-105' 
                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 hover:border-orange-500' ?>">
                <?= htmlspecialchars($cat['nom_categorie']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- =========================================================== -->
<!-- LISTE DES VÉHICULES -->
<!-- =========================================================== -->
<section class="py-16 bg-gray-50 text-center">
    <span class="inline-block bg-orange-500/20 text-orange-400 px-4 py-1 rounded-full text-sm font-semibold mb-4 uppercase tracking-widest border border-orange-500/30">
            Notre Flotte
        </span>
    <div class="container mx-auto px-4">
        
        <?php if (empty($vehicules)): ?>
        <!-- État Vide -->
        <div class="text-center py-20">
            <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-car text-gray-300 text-5xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Aucun véhicule trouvé</h3>
            <p class="text-gray-500 mb-6">Aucun véhicule disponible dans cette catégorie pour le moment.</p>
            <a href="vehicules.php" class="inline-block bg-gray-900 hover:bg-gray-800 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fas fa-arrow-left mr-2"></i> Voir tous les véhicules
            </a>
        </div>
        
        <?php else: ?>
        
        <!-- Compteur -->
        <div class="mb-8 text-center">
            <span class="text-gray-500 font-medium"><span class="text-orange-500 font-bold"><?= count($vehicules) ?></span> véhicules trouvés</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($vehicules as $v): ?>
            <div class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border border-gray-100 hover:border-orange-500">
                
                <!-- Image Container -->
                <div class="relative h-56 overflow-hidden">
                    <img src="<?= BASE_URL ?>/uploads/vehicules/<?= htmlspecialchars($v['image'] ?? 'default.jpg') ?>" 
                         alt="<?= htmlspecialchars($v['marque']) ?>" 
                         class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700"
                         onerror="this.src='https://via.placeholder.com/400x300?text=Image+Indisponible'">
                    
                    <!-- Overlay au survol -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>

                    <!-- Badge Catégorie -->
                    <div class="absolute top-4 left-4">
                        <span class="bg-white/90 backdrop-blur-sm text-gray-800 px-4 py-1.5 rounded-full text-xs font-bold shadow-lg flex items-center gap-2">
                            <i class="fas fa-tag text-orange-500"></i> <?= htmlspecialchars($v['nom_categorie']) ?>
                        </span>
                    </div>

                    <!-- Badge Disponible -->
                    <div class="absolute top-4 right-4">
                        <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                            Disponible
                        </span>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-orange-500 transition">
                                <?= htmlspecialchars($v['marque']) ?> <?= htmlspecialchars($v['modele']) ?>
                            </h3>
                            <p class="text-sm text-gray-400">
                                <i class="fas fa-id-card mr-1"></i> <?= htmlspecialchars($v['immatriculation']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Specs Grid -->
                    <div class="grid grid-cols-3 gap-3 my-5">
                        <div class="text-center py-2 px-1 rounded-lg bg-slate-50 group-hover:bg-orange-50 transition">
                            <i class="fas fa-users text-orange-500 mb-1"></i>
                            <p class="text-xs text-gray-500">Places</p>
                            <p class="text-sm font-bold text-gray-800"><?= (int)$v['nb_places'] ?></p>
                        </div>
                        <div class="text-center py-2 px-1 rounded-lg bg-slate-50 group-hover:bg-orange-50 transition">
                            <i class="fas fa-gas-pump text-orange-500 mb-1"></i>
                            <p class="text-xs text-gray-500">Carb.</p>
                            <p class="text-sm font-bold text-gray-800"><?= substr(htmlspecialchars($v['carburant']), 0, 4) ?></p>
                        </div>
                        <div class="text-center py-2 px-1 rounded-lg bg-slate-50 group-hover:bg-orange-50 transition">
                            <i class="fas fa-cog text-orange-500 mb-1"></i>
                            <p class="text-xs text-gray-500">Trans.</p>
                            <p class="text-sm font-bold text-gray-800"><?= substr(htmlspecialchars($v['transmission']), 0, 4) ?></p>
                        </div>
                    </div>
                    
                    <!-- Footer Card -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div>
                            <span class="text-2xl font-extrabold text-gray-900"><?= formatPrice($v['prix_jour']) ?></span>
                            <span class="text-gray-400 text-sm font-medium">/jour</span>
                        </div>
                        
                        <?php $link = isLoggedIn() ? "client/reserver.php?vehicule={$v['id_vehicule']}" : "auth/login.php"; ?>
                        <a href="<?= BASE_URL ?>/<?= $link ?>" class="bg-gray-900 hover:bg-orange-500 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition shadow-md transform hover:scale-105">
                            Réserver <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer_public.php'; ?>

<!-- Script Hero Slideshow -->
<script>
    const slidesV = document.querySelectorAll('.slide-v');
    let currentV = 0;
    
    if(slidesV.length > 1) {
        setInterval(() => {
            slidesV[currentV].classList.remove('active');
            currentV = (currentV + 1) % slidesV.length;
            slidesV[currentV].classList.add('active');
        }, 4000); // Change toutes les 4 secondes
    }
</script>