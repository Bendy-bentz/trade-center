<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>Trade Center</title>
    
    <!-- Tailwind CSS Local -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/output.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* ANIMATION MENU MOBILE */
        #mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        #mobile-menu.open {
            max-height: 500px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-slate-100">

    <!-- Navigation Principale -->
    <nav class="bg-gray-900 shadow-lg sticky top-0 z-50 w-full border-b border-gray-800">
        <div class="container mx-auto px-4">
            <!-- Utilisation de justify-between pour séparer Logo (Gauche) et Hamburger (Droite) sur mobile -->
            <div class="flex items-center justify-between h-20 md:h-24 inline-flex">
                
                <!-- 1. GAUCHE : LOGO -->
                <div class="flex-shrink-0 h-full flex items-center pr-4 mr-6 ">
                    <a href="<?= BASE_URL ?>/" class="flex items-center h-full">
                        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Trade Center"   class="h-16 md:h-20 lg:h-24 w-auto w-full object-contain h-full">
                    </a>
                </div>

                <!-- 2. CENTRE : NAVIGATION (Desktop Uniquement) -->
                <div class="hidden lg:flex flex-grow justify-center items-center space-x-6">
                    <a href="<?= BASE_URL ?>/" class="text-white border-2 border-orange-500 px-4 py-2 rounded-lg hover:bg-orange-500 transition font-medium text-sm uppercase tracking-wider">
                        Accueil
                    </a>
                    <!-- NOUVEAU LIEN : À propos -->
                    <a href="<?= BASE_URL ?>/a-propos.php" class="text-white border-2 border-orange-500 px-4 py-2 rounded-lg hover:bg-orange-500 transition font-medium text-sm uppercase tracking-wider">
                        À propos
                    </a>
                    <a href="<?= BASE_URL ?>/vehicules.php" class="text-white border-2 border-orange-500 px-4 py-2 rounded-lg hover:bg-orange-500 transition font-medium text-sm uppercase tracking-wider">
                        Véhicules
                    </a>
                    <a href="<?= BASE_URL ?>/services.php" class="text-white border-2 border-orange-500 px-4 py-2 rounded-lg hover:bg-orange-500 transition font-medium text-sm uppercase tracking-wider">
                        Services
                    </a>
                    <a href="<?= BASE_URL ?>/contact.php" class="text-white border-2 border-orange-500 px-4 py-2 rounded-lg hover:bg-orange-500 transition font-medium text-sm uppercase tracking-wider">
                        Contact
                    </a>
                </div>

                                <!-- 3. DROITE : AUTH -->
                <div class="hidden lg:flex flex-shrink-0 items-center gap-3">
                    
                    <!-- Horloge -->
                    <div class="flex items-center gap-2 bg-gray-900 px-3 py-1 rounded-full border border-gray-800 shadow-inner text-xs text-white font-mono tracking-wider">
                        <i class="fas fa-clock text-orange-500 text-[10px]"></i>
                        <span id="live-time">00:00:00</span>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Profil Connecté -->
                        <div class="relative group">
                            <button class="flex items-center text-white hover:text-orange-400 transition gap-2 bg-gray-900 rounded-full py-1 pl-1 pr-4 focus:outline-none border border-gray-800">
                                <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-xs font-bold shadow">
                                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                                </div>
                                <span class="font-medium text-sm hidden md:block"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 hidden group-hover:block border border-gray-100 z-50 overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                                    <p class="text-xs text-gray-500">Connecté</p>
                                </div>

                                <!-- LIEN MON ESPACE DYNAMIQUE (CORRECTION ICI) -->
                                <?php 
                                    // Définition de l'URL par défaut (Client)
                                    $dashboardLink = BASE_URL . '/client/index.php';
                                    
                                    // Si Admin ou Agent, on change l'URL
                                    if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Admin', 'Agent'])) {
                                        $dashboardLink = BASE_URL . '/admin/index.php';
                                    }
                                ?>
                                 <a href="<?= $dashboardLink ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition text-sm">
                                    <i class="fas fa-tachometer-alt w-5 mr-3 text-gray-400"></i>Mon Espace
                                </a>

                                <hr class="my-1">
                                <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 transition text-sm font-medium">
                                    <i class="fas fa-sign-out-alt w-5 mr-3"></i>Déconnexion
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Boutons Auth (Si non connecté) -->
                        <div class="flex items-center gap-2">
                            <a href="<?= BASE_URL ?>/auth/login.php" class="text-gray-300 hover:text-white transition font-medium text-sm px-3 py-2">
                                Se connecter
                            </a>
                            <a href="<?= BASE_URL ?>/auth/register.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold transition shadow-lg text-sm border border-orange-400">
                                S'inscrire
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 4. DROITE (MOBILE) : Hamburger Seul -->
                <div class="lg:hidden flex-shrink-0 z-50">
                    <button id="mobile-menu-btn" type="button" class="text-white p-2 hover:bg-gray-800 rounded-lg transition focus:outline-none">
                        <i class="fas fa-bars text-xl" id="menu-icon"></i>
                    </button>
                </div>
                
            </div>
        </div>

               <!-- MENU MOBILE CONTENT -->
        <div id="mobile-menu" class="lg:hidden border-t border-gray-800 bg-gray-900">
            <div class="py-4 space-y-1 container mx-auto px-4">
                <a href="<?= BASE_URL ?>/" class="flex items-center py-3 px-4 text-white hover:bg-gray-800 rounded-lg font-medium">
                    <i class="fas fa-home w-6 mr-3 text-orange-500"></i>Accueil
                </a>
                <a href="<?= BASE_URL ?>/a-propos.php" class="flex items-center py-3 px-4 text-white hover:bg-gray-800 rounded-lg font-medium">
                    <i class="fas fa-info-circle w-6 mr-3 text-orange-500"></i>À Propos
                </a>
                <a href="<?= BASE_URL ?>/vehicules.php" class="flex items-center py-3 px-4 text-white hover:bg-gray-800 rounded-lg font-medium">
                    <i class="fas fa-car w-6 mr-3 text-orange-500"></i>Véhicules
                </a>
                <a href="<?= BASE_URL ?>/services.php" class="flex items-center py-3 px-4 text-white hover:bg-gray-800 rounded-lg font-medium">
                    <i class="fas fa-concierge-bell w-6 mr-3 text-orange-500"></i>Services
                </a>
                <a href="<?= BASE_URL ?>/contact.php" class="flex items-center py-3 px-4 text-white hover:bg-gray-800 rounded-lg font-medium">
                    <i class="fas fa-envelope w-6 mr-3 text-orange-500"></i>Contact
                </a>
                
                <hr class="my-3 border-gray-700">
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <!-- LIEN MON ESPACE DYNAMIQUE (MOBILE) -->
                    <?php 
                        $dashboardUrlMobile = BASE_URL . '/client/index.php';
                        if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Admin', 'Agent'])) {
                            $dashboardUrlMobile = BASE_URL . '/admin/index.php';
                        }
                    ?>
                    <a href="<?= $dashboardUrlMobile ?>" class="flex items-center py-3 px-4 text-white hover:bg-gray-800 rounded-lg font-medium">
                        <i class="fas fa-user w-6 mr-3 text-orange-500"></i>Mon Espace
                    </a>

                    <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center py-3 px-4 text-red-500 hover:bg-red-900/20 rounded-lg font-medium">
                        <i class="fas fa-sign-out-alt w-6 mr-3"></i>Déconnexion
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="flex items-center py-3 px-4 text-white hover:bg-gray-800 rounded-lg font-medium">
                        <i class="fas fa-sign-in-alt w-6 mr-3 text-orange-500"></i>Se connecter
                    </a>
                    <div class="pt-2 px-4">
                        <a href="<?= BASE_URL ?>/auth/register.php" class="flex items-center justify-center bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-semibold transition">
                            S'inscrire
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <!-- Main Content Start -->
    <main class="flex-grow">

    <!-- Scripts -->
    <script>
        // Menu Mobile
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        const icon = document.getElementById('menu-icon');
        
        if(btn) {
            btn.addEventListener('click', () => {
                menu.classList.toggle('open');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            });
        }

        // Horloge
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            const timeString = `${hours}:${minutes}:${seconds}`;

            const timeDisplay = document.getElementById('live-time');
            if (timeDisplay) timeDisplay.innerText = timeString;
        }

        setInterval(updateTime, 1000);
        updateTime();
    </script>