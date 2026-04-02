<?php
require_once 'config/config.php';

 $pageTitle = 'Contact';
include 'includes/header_public.php';

 $error = '';
 $success = '';

// Traitement du formulaire (exemple basique)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logique d'envoi d'email ici (mail() ou PHPMailer)
    // Pour l'exemple, on simule un succès
    $success = "Votre message a bien été envoyé. Nous vous répondrons rapidement.";
}
?>

<!-- HERO SECTION (Hauteur augmentée) -->
<section class="relative min-h-[70vh] bg-slate-900 text-white overflow-hidden flex items-center justify-center">
    <!-- Image de fond -->
    <div class="absolute inset-0 z-0 opacity-30">
        <img src="<?= BASE_URL ?>/assets/images/closeup-office-wired-telephone.jpg" alt="Contact" class="w-full h-full object-cover">
    </div>
    
    <div class="container mx-auto px-4 relative z-10 text-center py-20">
        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">
            Contactez-<span class="text-orange-500">nous</span>
        </h1>
        <p class="text-lg text-gray-300 max-w-2xl mx-auto text-center">
           <center>Une question, une demande spéciale ? Notre équipe est à votre écoute.</center>
        </p>
    </div>
</section>

<!-- CONTACT SECTION -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 -mt-20 relative z-20">
            
            <!-- Infos de Contact -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Carte Info -->
                <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-100 transform hover:scale-105 transition duration-300">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-map-marker-alt text-orange-500 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Notre Adresse</h3>
                    <p class="text-gray-500 text-sm">123 Avenue Mohammed V<br>Casablanca, Maroc</p>
                </div>

                <!-- Carte Info -->
                <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-100 transform hover:scale-105 transition duration-300">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-phone text-orange-500 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Téléphone</h3>
                    <p class="text-gray-500 text-sm">+212 5XX-XXXXXX</p>
                </div>

                <!-- Carte Info -->
                <div class="bg-white rounded-2xl p-6 shadow-xl border border-gray-100 transform hover:scale-105 transition duration-300">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-envelope text-orange-500 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Email</h3>
                    <p class="text-gray-500 text-sm">contact@tradecenter.ma</p>
                </div>
            </div>

            <!-- Formulaire -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Envoyez-nous un message</h3>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-lg mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                            <input type="text" name="name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition" placeholder="Votre nom">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition" placeholder="votre@email.com">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sujet</label>
                        <input type="text" name="subject" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition" placeholder="Sujet de votre message">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea name="message" rows="5" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition resize-none" placeholder="Votre message..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white py-4 rounded-xl font-bold transition shadow-lg inline-flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i> Envoyer le message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- MAP SECTION -->
<section class="h-96 w-full bg-gray-200 relative">
    <!-- Remplacer par une vraie carte Google Maps -->
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3323.8462913685144!2d-7.618928485017042!3d33.58488348073305!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda7d2b5a3e5f6e1%3A0x8c4e5f6a7b8c9d0e!2sCasablanca!5e0!3m2!1sfr!2sma!4v1620000000000!5m2!1sfr!2sma" 
        width="100%" 
        height="100%" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy"
        class="absolute inset-0 grayscale hover:grayscale-0 transition duration-500">
    </iframe>
</section>

<?php include 'includes/footer_public.php'; ?>