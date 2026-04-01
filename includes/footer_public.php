    </main> <!-- Fin du Main -->

    <!-- Footer -->
    <footer id="contact" class="bg-gray-900 text-white pt-16 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                
                <!-- Colonne 1 : Brand (Avec Logo) -->
                <div>
                    <div class="flex items-center mb-6">
                        <!-- Logo dans le footer -->
                        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Trade Center"  class="h-20 w-auto" > 
                    </div>
                    <p class="text-gray-400 mb-6 leading-relaxed">
                        Votre partenaire de confiance pour la location de véhicules premium. 
                        Service qualité et satisfaction client garantis.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-500 transition">
                            <i class="fab fa-facebook-f text-gray-400 hover:text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-500 transition">
                            <i class="fab fa-twitter text-gray-400 hover:text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-500 transition">
                            <i class="fab fa-instagram text-gray-400 hover:text-white"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Colonne 2 : Liens Rapides -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-white">Liens Rapides</h4>
                    <ul class="space-y-3">
                        <li><a href="<?= BASE_URL ?>/" class="text-gray-400 hover:text-orange-500 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-orange-500"></i>Accueil</a></li>
                        <li><a href="<?= BASE_URL ?>/vehicules.php" class="text-gray-400 hover:text-orange-500 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-orange-500"></i>Nos Véhicules</a></li>
                        <li><a href="<?= BASE_URL ?>/services.php" class="text-gray-400 hover:text-orange-500 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-orange-500"></i>Services</a></li>
                        <li><a href="<?= BASE_URL ?>/conditions-utilisation.php" class="text-gray-400 hover:text-orange-500 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-orange-500"></i>Conditions</a></li>
                    </ul>
                </div>
                
                <!-- Colonne 3 : Services -->
                <div id="services">
                    <h4 class="text-lg font-bold mb-6 text-white">Nos Services</h4>
                    <ul class="space-y-3">
                        <li><a href="<?= BASE_URL ?>/services.php#location-courte" class="text-gray-400 hover:text-orange-500 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-orange-500"></i>Location Courte Durée</a></li>
                        <li><a href="<?= BASE_URL ?>/services.php#location-lld" class="text-gray-400 hover:text-orange-500 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-orange-500"></i>Location Longue Durée</a></li>
                        <li><a href="<?= BASE_URL ?>/services.php#transfert-aeroport" class="text-gray-400 hover:text-orange-500 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-orange-500"></i>Transfert Aéroport</a></li>
                        <li><a href="<?= BASE_URL ?>/services.php#location-chauffeur" class="text-gray-400 hover:text-orange-500 transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-orange-500"></i>Location pour Entreprises</a></li>
                    </ul>
                </div>
                
                <!-- Colonne 4 : Contact -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-white">Contact</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-orange-500 mt-1 mr-3"></i>
                            <span class="text-gray-400">123 Cap-Haitien rue 24 boulevard<br>Cap-Haitien, Haiti</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt text-orange-500 mr-3"></i>
                            <span class="text-gray-400">+509 48360967</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-orange-500 mr-3"></i>
                            <span class="text-gray-400">tradecenterlocation@gmail.com</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-gray-500 text-sm">&copy; <?= date('Y') ?> Trade Center. Tous droits réservés.</p>
                    <div class="flex gap-6 text-sm">
                        <a href="politique-de-confidentialite.php" class="text-gray-500 hover:text-orange-500 transition">Politique de confidentialité</a>
                        <a href="conditions-utilisation.php" class="text-gray-500 hover:text-orange-500 transition">Conditions d'utilisation</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Mobile Menu Toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        const icon = document.getElementById('menu-icon');
        
        if(btn) {
            btn.addEventListener('click', () => {
                menu.classList.toggle('open');
                if(menu.classList.contains('open')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        }
    </script>
</body>
</html>