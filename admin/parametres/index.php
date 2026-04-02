<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$db = getDB();

$pageTitle = 'Paramètres';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Paramètres du système</h2>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Informations entreprise -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Informations entreprise</h3>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise</label>
                <input type="text" name="nom_entreprise" value="TradecenterEntreprise" class="input-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <input type="text" name="adresse" class="input-field" placeholder="Adresse complète">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="text" name="telephone" class="input-field" placeholder="+212 XXX XXX XXX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" class="input-field" placeholder="contact@example.com">
                </div>
            </div>
            <button type="submit" class="btn-primary">Enregistrer</button>
        </form>
    </div>

    <!-- Paramètres de location -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Paramètres de location</h3>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Caution par défaut (MAD)</label>
                <input type="number" name="caution" value="5000" class="input-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kilométrage inclus/jour</label>
                <input type="number" name="km_inclus" value="200" class="input-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Supplément km (MAD)</label>
                <input type="number" name="supplement_km" value="2" step="0.5" class="input-field">
            </div>
            <button type="submit" class="btn-primary">Enregistrer</button>
        </form>
    </div>
</div>

<!-- Actions système -->
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions système</h3>
    <div class="flex flex-wrap gap-4">
        <a href="<?= BASE_URL ?>/database/tradecenter.sql" class="btn-secondary" target="_blank">
            Télécharger le SQL
        </a>
        <button class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600">
            Sauvegarder la base
        </button>
        <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600" onclick="return confirm('Vider les données de test ?')">
            Vider les données de test
        </button>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
