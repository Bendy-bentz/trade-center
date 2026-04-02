<?php
require_once __DIR__ . '/../config/config.php';
requireClient();

 $db = getDB();
 $userId = getUserId();

// Récupérer l'ID client
 $client = $db->prepare("SELECT id_client FROM Clients WHERE id_utilisateur = ?");
 $client->execute([$userId]);
 $client = $client->fetch();

if (!$client) {
    redirect('/client/index.php');
}

 $idClient = $client['id_client'];

// Messages de succès/erreur pour les avis
 $success_review = $_SESSION['success_review'] ?? '';
 $error_review = $_SESSION['error_review'] ?? '';
unset($_SESSION['success_review'], $_SESSION['error_review']);

// Liste des réservations
 $reservations = $db->prepare("SELECT r.*, v.marque, v.modele, v.immatriculation, v.prix_jour 
    FROM Reservations r 
    JOIN Vehicules v ON r.id_vehicule = v.id_vehicule 
    WHERE r.id_client = ? 
    ORDER BY r.date_reservation DESC");
 $reservations->execute([$idClient]);
 $reservations = $reservations->fetchAll();

 $pageTitle = 'Mes Réservations';
include __DIR__ . '/../includes/header_dashboard.php';
?>

<!-- Affichage des messages d'avis -->
<?php if ($success_review): ?>
<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center">
    <i class="fas fa-check-circle mr-3"></i> <?= $success_review ?>
</div>
<?php endif; ?>

<?php if ($error_review): ?>
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center">
    <i class="fas fa-exclamation-circle mr-3"></i> <?= $error_review ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800">Mes Réservations</h3>
        <a href="reserver.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fas fa-plus mr-2"></i> Réserver
        </a>
    </div>
    <div class="p-6">
        <?php if (empty($reservations)): ?>
            <p class="text-gray-500 text-center py-8">Aucune réservation</p>
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/vehicules.php" class="text-orange-500 hover:text-orange-600 font-semibold">Voir les véhicules disponibles</a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Référence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Véhicule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($reservations as $r): ?>
                        <?php $nbJours = max(1, (strtotime($r['date_fin']) - strtotime($r['date_debut'])) / 86400); ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">
                                #<?= str_pad($r['id_reservation'], 5, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?= $r['marque'] ?> <?= $r['modele'] ?></div>
                                <div class="text-sm text-gray-500"><?= $r['immatriculation'] ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div><?= formatDate($r['date_debut']) ?> → <?= formatDate($r['date_fin']) ?></div>
                                <div class="text-gray-400 text-xs"><?= $nbJours ?> jour(s)</div>
                            </td>
                            <td class="px-6 py-4 font-bold text-orange-600">
                                <?= formatPrice($nbJours * $r['prix_jour']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusClass = 'bg-yellow-100 text-yellow-700';
                                if ($r['statut'] == 'Confirmée') $statusClass = 'bg-green-100 text-green-700';
                                if ($r['statut'] == 'En cours') $statusClass = 'bg-blue-100 text-blue-700';
                                if ($r['statut'] == 'Terminée') $statusClass = 'bg-gray-100 text-gray-700';
                                if ($r['statut'] == 'Annulée') $statusClass = 'bg-red-100 text-red-700';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusClass ?>">
                                    <?= $r['statut'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="<?= BASE_URL ?>/admin/contrats/view.php?id=<?= $r['id_reservation'] ?>" target="_blank" class="text-orange-500 hover:text-orange-700 font-medium text-sm inline-flex items-center gap-1 transition">
                                        <i class="fas fa-file-pdf"></i> PDF
                                     </a>
                                    <?php if ($r['statut'] == 'En attente'): ?>
                                    <a href="annuler.php?id=<?= $r['id_reservation'] ?>" 
                                       onclick="return confirm('Voulez-vous annuler cette réservation ?')"
                                       class="text-gray-400 hover:text-red-600 text-sm transition">
                                        Annuler
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- ========================================================= -->
                        <!-- NOUVEAU : LIGNE CACHEE POUR LAISSER UN AVIS SI TERMINÉE -->
                        <!-- ========================================================= -->
                        <?php if ($r['statut'] == 'Terminée'): ?>
                        <?php 
                        // Vérifier en BDD s'il a déjà noté cette réservation
                        $verifAvis = $db->prepare("SELECT id_avis FROM Avis WHERE id_reservation = ?");
                        $verifAvis->execute([$r['id_reservation']]);
                        $aDejaNote = $verifAvis->fetch();
                        ?>
                        <tr id="avis-row-<?= $r['id_reservation'] ?>" class="bg-gray-50">
                            <td colspan="6" class="px-6 py-4">
                                <?php if (!$aDejaNote): ?>
                                <!-- Formulaire pour laisser un avis -->
                                <form action="submit_review.php" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-4">
                                    <input type="hidden" name="id_reservation" value="<?= $r['id_reservation'] ?>">
                                    <input type="hidden" name="id_vehicule" value="<?= $r['id_vehicule'] ?>">
                                    
                                    <p class="text-sm font-bold text-gray-700 whitespace-nowrap">Votre avis :</p>
                                    
                                    <!-- Étoiles cliquables -->
                                    <div class="flex gap-1" id="stars-<?= $r['id_reservation'] ?>">
                                        <button type="button" onclick="setNote(<?= $r['id_reservation'] ?>, 1)" class="text-gray-300 hover:text-orange-400 text-xl transition"><i class="fas fa-star"></i></button>
                                        <button type="button" onclick="setNote(<?= $r['id_reservation'] ?>, 2)" class="text-gray-300 hover:text-orange-400 text-xl transition"><i class="fas fa-star"></i></button>
                                        <button type="button" onclick="setNote(<?= $r['id_reservation'] ?>, 3)" class="text-gray-300 hover:text-orange-400 text-xl transition"><i class="fas fa-star"></i></button>
                                        <button type="button" onclick="setNote(<?= $r['id_reservation'] ?>, 4)" class="text-gray-300 hover:text-orange-400 text-xl transition"><i class="fas fa-star"></i></button>
                                        <button type="button" onclick="setNote(<?= $r['id_reservation'] ?>, 5)" class="text-gray-300 hover:text-orange-400 text-xl transition"><i class="fas fa-star"></i></button>
                                        <input type="hidden" name="note" id="note-<?= $r['id_reservation'] ?>" value="0" required>
                                    </div>
                                    
                                    <!-- Commentaire -->
                                    <input type="text" name="commentaire" class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-orange-500" placeholder="Commentaire (optionnel)...">
                                    
                                    <!-- Bouton -->
                                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold transition whitespace-nowrap">
                                        Envoyer
                                    </button>
                                </form>
                                <?php else: ?>
                                <!-- S'il a déjà noté -->
                                <div class="flex items-center gap-2 text-sm text-green-600 font-medium">
                                    <i class="fas fa-check-circle"></i> Merci, votre avis a bien été enregistré.
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <!-- ========================================================= -->

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script pour rendre les étoiles cliquables -->
<script>
function setNote(reservationId, note) {
    // Mettre à jour la valeur cachée
    document.getElementById('note-' + reservationId).value = note;
    
    // Changer la couleur des étoiles
    const container = document.getElementById('stars-' + reservationId);
    const buttons = container.querySelectorAll('button i');
    buttons.forEach((star, index) => {
        if (index < note) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-orange-400');
        } else {
            star.classList.remove('text-orange-400');
            star.classList.add('text-gray-300');
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer_dashboard.php'; ?>