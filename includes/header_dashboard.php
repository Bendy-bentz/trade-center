<?php
/**
 * Header conditionnel selon le rôle
 * Admin : Accès complet
 * Agent : Accès limité (pas de gestion utilisateurs, pas de stats financières)
 * Client : Dashboard client avec ses réservations
 */

 $currentUser = getCurrentUser();
 $userRole = getUserRole();
 $currentPage = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>TradecenterEntreprise</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Style général de la Sidebar : Cachée par défaut (Décallée à gauche) */
        .sidebar-wrapper {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 16rem;
            z-index: 50;
            transform: translateX(-100%); /* Cachée par défaut */
            transition: transform 0.3s ease-in-out;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.5);
        }

        /* Classe pour ouvrir la sidebar */
        .sidebar-wrapper.open {
            transform: translateX(0);
        }

        /* Main Wrapper : Pleine largeur car sidebar cachée */
        .main-wrapper {
            margin-left: 0;
            width: 100%;
        }

        /* Overlay (Voile noir) - visible sur mobile et desktop quand menu ouvert */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .sidebar-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        /* Mobile specific: Overlay clickable pour fermer */
        @media (max-width: 1023px) {
             body.menu-open {
                overflow: hidden;
            }
        }
        
        /* Desktop specific: Bouton hamburger toujours visible */
        @media (min-width: 1024px) {
            #sidebar-toggle-btn {
                display: block !important; /* Force l'affichage sur desktop */
            }
        }
    </style>
</head>
<body class="bg-gray-50">
     <!-- Overlay (Visible quand le menu est ouvert) -->
    <div id="sidebar-overlay" class="sidebar-overlay"></div>
    
    <!-- Sidebar (NOIR) -->
    <aside id="sidebar" class="sidebar-wrapper bg-black text-white flex flex-col h-screen border-r border-gray-900">
        <!-- Logo -->
        <div class="p-2 border-b border-gray-800 flex items-center justify-between h-24">
            <a href="<?= BASE_URL ?>/index.php" class="flex items-center h-full px-4">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="TradeCenter" class="h-20 md:h-24 w-auto object-contain">
            </a>
            <!-- Bouton fermeture (utile sur mobile) -->
            <button id="sidebar-close-btn" class="text-gray-500 hover:text-white p-2 rounded-lg hover:bg-gray-800 transition" aria-label="Fermer le menu">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Navigation selon le rôle -->
        <nav class="flex-1 py-6 overflow-y-auto">
            
            <?php if (isAdmin() || isAgent()): ?>
            <!-- MENU ADMIN / AGENT -->
            
            <div class="px-6 mb-4 text-[11px] font-bold text-gray-600 uppercase tracking-widest">Menu Principal</div>
            
            <div class="space-y-1 px-3">
                <!-- Dashboard -->
                <a href="<?= BASE_URL ?>/admin/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/admin/index.php') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-th-large w-5 mr-3"></i>
                    <span class="font-medium text-sm">Tableau de bord</span>
                </a>
                
                <!-- Flotte -->
                <a href="<?= BASE_URL ?>/admin/vehicules/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/vehicules') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-car w-5 mr-3"></i>
                    <span class="font-medium text-sm">Flotte</span>
                </a>
                
                <!-- Réservations -->
                <a href="<?= BASE_URL ?>/admin/reservations/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/reservations') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-calendar-alt w-5 mr-3"></i>
                    <span class="font-medium text-sm">Réservations</span>
                </a>
                
                <!-- Clients -->
                <a href="<?= BASE_URL ?>/admin/clients/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/clients') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-users w-5 mr-3"></i>
                    <span class="font-medium text-sm">Clients</span>
                </a>
                
                <!-- Prises en charge -->
                <a href="<?= BASE_URL ?>/admin/contrats/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/contrats') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-file-signature w-5 mr-3"></i>
                    <span class="font-medium text-sm">Contrats</span>
                </a>
                
                <!-- Revenus -->
                <a href="<?= BASE_URL ?>/admin/paiements/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/paiements') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-dollar-sign w-5 mr-3"></i>
                    <span class="font-medium text-sm">Paiements</span>
                </a>
                
                <!-- Rapports -->
                <a href="<?= BASE_URL ?>/admin/rapports/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/rapports') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-chart-bar w-5 mr-3"></i>
                    <span class="font-medium text-sm">Rapports</span>
                </a>
            </div>
            
            <?php if (isAdmin()): ?>
            <!-- MENU ADMIN UNIQUEMENT -->
            
            <div class="border-t border-gray-800 my-6 mx-4"></div>
            <div class="px-6 mb-4 text-[11px] font-bold text-gray-600 uppercase tracking-widest">Administration</div>
            
            <div class="space-y-1 px-3">
                <!-- Méthodes paiement -->
                <a href="<?= BASE_URL ?>/admin/methodes-paiement/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/methodes-paiement') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-credit-card w-5 mr-3"></i>
                    <span class="font-medium text-sm">Méthodes de paiement</span>
                </a>
                
                <!-- Utilisateurs -->
                <a href="<?= BASE_URL ?>/admin/utilisateurs/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/utilisateurs') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-user-shield w-5 mr-3"></i>
                    <span class="font-medium text-sm">Utilisateurs</span>
                </a>
                
                <!-- Paramètres -->
                <a href="<?= BASE_URL ?>/admin/parametres/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/parametres') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-cog w-5 mr-3"></i>
                    <span class="font-medium text-sm">Paramètres</span>
                </a>
                
                <!-- Entretien -->
                <a href="<?= BASE_URL ?>/admin/entretien/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/entretien') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-tools w-5 mr-3"></i>
                    <span class="font-medium text-sm">Entretien</span>
                </a>
            </div>
            <?php endif; ?>
            
            <?php elseif (isClient()): ?>
            <!-- MENU CLIENT -->
            
            <div class="space-y-1 px-3">
                <a href="<?= BASE_URL ?>/client/index.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/client/index.php') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-home w-5 mr-3"></i> <span class="font-medium text-sm">Tableau de bord</span>
                </a>
                <a href="<?= BASE_URL ?>/client/reserver.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/reserver') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-plus-circle w-5 mr-3"></i> <span class="font-medium text-sm">Réserver</span>
                </a>
                <a href="<?= BASE_URL ?>/client/reservations.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/client/reservations') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-calendar-check w-5 mr-3"></i> <span class="font-medium text-sm">Mes Réservations</span>
                </a>
                <a href="<?= BASE_URL ?>/client/profil.php" class="flex items-center px-4 py-3 text-white hover:bg-gray-900 hover:text-orange-400 transition rounded-r-full mb-1
                    <?= strpos($currentPage, '/client/profil') !== false ? 'bg-orange-500 text-white hover:bg-orange-600 hover:text-white shadow-lg shadow-orange-500/20' : '' ?>">
                    <i class="fas fa-user w-5 mr-3"></i> <span class="font-medium text-sm">Mon Profil</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
        
        <!-- User Info & Logout -->
        <div class="p-4 border-t border-gray-800 bg-gray-950">
            <div class="flex items-center justify-between">
                <a href="<?= isAdminOrAgent() ? BASE_URL . '/admin/profil.php' : BASE_URL . '/client/profil.php' ?>" class="flex items-center gap-3 hover:opacity-80 transition">
                    <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                        <?= strtoupper(substr(getUserName(), 0, 1)) ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white"><?= getUserName() ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= $userRole ?></p>
                    </div>
                </a>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="text-gray-500 hover:text-orange-400 transition p-2" title="Déconnexion">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper flex flex-col min-h-screen">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-20">
            <div class="flex items-center justify-between px-4 lg:px-6 py-4">
                <!-- Mobile Menu Button (Visible sur Desktop aussi maintenant) -->
                <button id="sidebar-toggle-btn" class="text-gray-600 hover:text-gray-900 p-2 -ml-2 mr-2 rounded-lg hover:bg-gray-100 transition" aria-label="Ouvrir le menu">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Search Bar -->
                <div class="relative flex-1 max-w-xl hidden sm:block">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" placeholder="Rechercher véhicule, client, réservation..." 
                        class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition">
                </div>
                
                <div class="flex items-center gap-3 lg:gap-6 ml-2 lg:ml-6">
                    <!-- Date - Hidden on mobile -->
                    <div class="text-sm text-gray-500 hidden md:block">
                        <?= strftime('%A %d %B %Y', strtotime('today')) ?>
                    </div>
                    
                    <!-- Notifications -->
                    <button class="relative text-gray-500 hover:text-orange-500 transition p-2">
                        <i class="fas fa-bell text-lg lg:text-xl"></i>
                        <span class="absolute top-0 right-0 w-4 h-4 bg-red-500 rounded-full text-white text-xs flex items-center justify-center">3</span>
                    </button>
                    
                    <!-- User Avatar - Hidden on mobile -->
                    <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold cursor-pointer hidden sm:flex">
                        <?= strtoupper(substr(getUserName(), 0, 2)) ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area - This is scrollable -->
        <main class="flex-1 p-4 lg:p-6 bg-gray-50">

<!-- Script pour gérer l'ouverture (Click + Hover) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('sidebar-overlay');

        // Fonction pour ouvrir
        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.classList.add('menu-open');
        }

        // Fonction pour fermer
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.classList.remove('menu-open');
        }

        // 1. Clic sur le bouton hamburger (Toggle)
        toggleBtn.addEventListener('click', function() {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        // 2. Clic sur le bouton fermer (croix)
        closeBtn.addEventListener('click', closeSidebar);

        // 3. Clic sur l'overlay (fond noir) pour fermer
        overlay.addEventListener('click', closeSidebar);

        // 4. LOGIQUE HOVER (Desktop uniquement)
        // On vérifie si on est sur desktop (largeur > 1023px)
        if (window.innerWidth >= 1024) {
            // Quand la souris entre sur le bouton hamburger
            toggleBtn.addEventListener('mouseenter', openSidebar);
            
            // Quand la souris entre sur la sidebar, on s'assure qu'elle reste ouverte
            sidebar.addEventListener('mouseenter', openSidebar);

            // Quand la souris sort de la sidebar, on ferme
            sidebar.addEventListener('mouseleave', function() {
                // On ajoute un petit délai pour éviter les fermetures accidentelles
                setTimeout(() => {
                    // On vérifie si la souris n'est pas revenue sur le bouton
                    if (!toggleBtn.matches(':hover') && !sidebar.matches(':hover')) {
                        closeSidebar();
                    }
                }, 100);
            });
        }
    });
</script>