<?php
/**
 * Modules Loader - Les modules sont optionnels
 */

 $moduleFiles = [
    __DIR__ . '/Vehicule/Vehicule.php',
    __DIR__ . '/Client/Client.php',
    __DIR__ . '/Reservation/Reservation.php',
    __DIR__ . '/Utilisateur/Utilisateur.php',
    __DIR__ . '/Entretien/Entretien.php',
    __DIR__ . '/Paiement/Paiement.php',
    __DIR__ . '/Contrat/Contrat.php'
];

foreach ($moduleFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

function initModules() {
    $modules = [];
    if (class_exists('VehiculeModule')) $modules['vehicule'] = new VehiculeModule();
    if (class_exists('ClientModule')) $modules['client'] = new ClientModule();
    if (class_exists('ReservationModule')) $modules['reservation'] = new ReservationModule();
    if (class_exists('UtilisateurModule')) $modules['utilisateur'] = new UtilisateurModule();
    if (class_exists('EntretienModule')) $modules['entretien'] = new EntretienModule();
    if (class_exists('PaiementModule')) $modules['paiement'] = new PaiementModule();
    if (class_exists('ContratModule')) $modules['contrat'] = new ContratModule();
    return $modules;
}