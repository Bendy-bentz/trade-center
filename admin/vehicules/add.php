<?php
require_once __DIR__ . '/../../config/config.php';
requireAdminOrAgent();

$db = getDB();

// Dossier d'upload
$uploadDir = __DIR__ . '/../../uploads/vehicules/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Traitement UNIQUEMENT si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image = '';
    
    // Gestion de l'upload d'image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = uniqid() . '_' . time() . '.' . $extension;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image);
        }
    }
    
    // Récupération des données du formulaire avec valeurs par défaut
    $marque = sanitize($_POST['marque'] ?? '');
    $modele = sanitize($_POST['modele'] ?? '');
    $immatriculation = sanitize($_POST['immatriculation'] ?? '');
    $annee = !empty($_POST['annee']) ? (int)$_POST['annee'] : null;
    $couleur = sanitize($_POST['couleur'] ?? '');
    $carburant = $_POST['carburant'] ?? 'Essence';
    $transmission = $_POST['transmission'] ?? 'Manuelle';
    $nb_places = (int)($_POST['nb_places'] ?? 5);
    $prix_jour = (float)($_POST['prix_jour'] ?? 0);
    $id_categorie = (int)($_POST['id_categorie'] ?? 1);
    $etat = $_POST['etat'] ?? 'Disponible';
    $est_vedette = isset($_POST['est_vedette']) ? 1 : 0;
    
    $stmt = $db->prepare("INSERT INTO Vehicules (marque, modele, immatriculation, annee, couleur, carburant, transmission, nb_places, prix_jour, id_categorie, etat, est_vedette, image) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $marque,
        $modele,
        $immatriculation,
        $annee,
        $couleur,
        $carburant,
        $transmission,
        $nb_places,
        $prix_jour,
        $id_categorie,
        $etat,
        $est_vedette,
        $image
    ]);
    redirect('/admin/vehicules/index.php?msg=added');
}

$categories = $db->query("SELECT * FROM Categories_Vehicules ORDER BY nom_categorie")->fetchAll();

$pageTitle = 'Ajouter un véhicule';
include __DIR__ . '/../../includes/header_dashboard.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Ajouter un véhicule</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <!-- Image du véhicule -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo du véhicule</label>
                <div class="flex items-center gap-4">
                    <div id="imagePreview" class="w-32 h-24 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-300">
                        <i class="fas fa-car text-gray-400 text-3xl" id="previewIcon"></i>
                        <img id="previewImg" class="hidden w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/gif,image/webp" 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                        <p class="text-xs text-gray-400 mt-1">Formats acceptés: JPG, PNG, GIF, WEBP (max 5 Mo)</p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marque *</label>
                    <input type="text" name="marque" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Modèle *</label>
                    <input type="text" name="modele" required class="input-field">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Immatriculation *</label>
                    <input type="text" name="immatriculation" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Année</label>
                    <input type="number" name="annee" min="1990" max="2030" class="input-field">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Couleur</label>
                    <input type="text" name="couleur" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carburant</label>
                    <select name="carburant" class="input-field">
                        <option value="Essence">Essence</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Électrique">Électrique</option>
                        <option value="Hybride">Hybride</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transmission</label>
                    <select name="transmission" class="input-field">
                        <option value="Manuelle">Manuelle</option>
                        <option value="Automatique">Automatique</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Places</label>
                    <input type="number" name="nb_places" value="5" min="2" max="9" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix/Jour (MAD) *</label>
                    <input type="number" name="prix_jour" step="0.01" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie *</label>
                    <select name="id_categorie" required class="input-field">
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id_categorie'] ?>"><?= $c['nom_categorie'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">État</label>
                    <select name="etat" class="input-field">
                        <option value="Disponible">Disponible</option>
                        <option value="Loué">Loué</option>
                        <option value="En maintenance">En maintenance</option>
                    </select>
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="est_vedette" value="1" class="mr-2">
                        <span class="text-sm text-gray-700">Véhicule vedette</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <a href="index.php" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
// Prévisualisation de l'image
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('previewImg');
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            document.getElementById('previewIcon').classList.add('hidden');
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer_dashboard.php'; ?>
