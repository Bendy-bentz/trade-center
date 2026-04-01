🚗 Trade Center Location
Une application web complète de gestion et de location de véhicules, développée en PHP pur, avec un tableau de bord administrateur et un espace client intuitif.

PHPMySQL
Tailwind
CSS
Stripe

✨ Fonctionnalités

👥 Côté Client
Authentification complète : Inscription, connexion, mot de passe oublié (via PHPMailer/Gmail).
Espace client : Tableau de bord, profil utilisateur, upload de photo de profil.
Réservation : Sélection de véhicules, choix des dates, récapitulatif.
Paiement multi-méthodes : Intégration de Stripe (Carte bancaire), MonCash, NatCash, Espèces, Virement. Calcul automatique des frais.
Avis et Notes : Système d'étoiles (1 à 5) laissé uniquement après une réservation "Terminée".
Historique : Suivi des réservations (En attente, Confirmée, En cours, Terminée, Annulée) et téléchargement des contrats PDF.

🛠️ Côté Administration
Gestion des véhicules : Ajout, modification, photos, catégories, tarification journalière.
Gestion des réservations : Validation, modification des statuts, gestion des contrats.
Gestion des paiements : Configuration des méthodes de paiement, visualisation des transactions.
Gestion des utilisateurs : Liste des clients et agents.
Tableau de bord : Statistiques globales, revenus, réservations récentes.

⚙️ Technique
Sécurité : Requêtes préparées PDO, hachage des mots de passe (bcrypt), protection CSRF basique, gestion des rôles.
Architecture : Structure MVC légère, routing propre, fonctions utilitaires centralisées.
Design : Interface moderne avec Tailwind CSS, responsive (Mobile, Tablette, PC).

📋 Prérequis
Pour faire tourner ce projet en local, vous aurez besoin de :

WampServer 64 bits (Version 3.2.x ou supérieure recommandée).
PHP 8.0+ (Activé dans Wamp).
MySQL / MariaDB.
Un compte Gmail (pour l'envoi d'emails).
Un compte Stripe (en mode Test pour les paiements).
🚀 Installation & Configuration

1. Mise en place des fichiers
   Clonez ou copiez le dossier du projet dans C:\wamp64\www\tradecenter.
   Lancez WampServer et attendez qu'il passe au vert.
   Accédez à http://localhost/tradecenter.

2. Base de données
   Ouvrez phpMyAdmin (http://localhost/phpmyadmin).
   Créez une nouvelle base de données (ex: tradecenter_db).
   Importez votre fichier .sql contenant la structure de la base (tables Utilisateurs, Vehicules, Reservations, etc.).(Note : Les tables Methodes_Paiement et Avis sont créées automatiquement par le code PHP si elles n'existent pas).
3. Configuration de l'environnement (config/config.php)
   Ouvrez le fichier config/config.php et modifiez les constantes suivantes :

// URL de base (sans slash à la fin)define('BASE_URL', 'http://localhost/tradecenter');// Connexion à la base de données (database.php)define('DB_HOST', 'localhost');define('DB_NAME', 'tradecenter_db');define('DB_USER', 'root');define('DB_PASS', ''); // Vide par défaut sur Wamp

4. Configuration des Emails via Gmail (PHPMailer)
   Le projet n'utilise pas Composer pour PHPMailer. Les fichiers sont déjà dans includes/PHPMailer-master/.

Allez sur votre compte Google : Sécurité > Validation en 2 étapes (Doit être activée).
Allez sur Mots de passe d'application.
Créez une application nommée "Wamp Tradecenter" et copiez le mot de passe de 16 caractères.
Dans config.php, remplissez les identifiants SMTP :
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'votre_email@gmail.com');
define('SMTP_PASS', 'abcdefghijklmnop'); // Mot de passe d'application (SANS ESPACES)
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', 'Trade Center Location');

5. Configuration de Stripe
   Le projet intègre Stripe manuellement via includes/stripe-php-master/.

Créez un compte sur Stripe.com.
Récupérez vos clés en mode Test dans votre Dashboard.
Dans config/payment_config.php, ajoutez :
define('STRIPE_PUBLIC_KEY', 'pk_test_VOTRE_CLE_PUBLIQUE');
define('STRIPE_SECRET_KEY', 'sk_test VOTRE_CLE_SECRETE');

Pour tester les paiements côté client, utilisez la carte de test Stripe : 4242 4242 4242 4242.

📁 Structure du Projet

tradecenter/
├── admin/ # Espace Administration (Agents, Admins)
│ ├── methodes-paiement/ # Configurations des moyens de paiement
│ └── ...
├── auth/ # Authentification (Login, Register, MDP oublié)
├── client/ # Espace Client (Réservations, Profil)
│ ├── stripe-success.php # Webhook de retour après paiement Stripe
│ └── ...
├── config/ # Configuration PHP
│ ├── config.php # Fonctions globales, sessions, SMTP
│ ├── database.php # Connexion PDO
│ └── payment_config.php # Clés API (Stripe, etc.)
├── includes/ # Éléments partagés et Librairies externes
│ ├── header_dashboard.php
│ ├── footer_dashboard.php
│ ├── PHPMailer-master/ # (Manuel) Librairie d'envoi d'emails
│ └── stripe-php-master/ # (Manuel) Librairie Stripe
├── assets/ # Ressources statiques
│ ├── css/output.css # Tailwind CSS compilé
│ ├── images/ # Photos des véhicules, logos de paiement
│ └── ...
├── index.php # Page d'accueil publique
└── README.md # Ce fichier

⚠️ Dépannage & Problèmes fréquents
Erreur 403 Forbidden lors de la connexion client
Si Apache renvoie une erreur 403 au lieu d'une erreur PHP, vérifiez :

Qu'il n'y a pas de doublon dans la base de données (ex: même email dans Utilisateurs et Clients non liés).
Qu'aucun fichier .htaccess ne bloque le dossier client/ avec Deny from all.
Problème de PATH avec WampServer
Ne mettez JAMAIS le chemin de PHP de Wamp dans les variables d'environnement Windows (PATH). C'est ce qui cause des conflits avec les librairies locales. Les librairies (Stripe, PHPMailer) sont incluses manuellement via require_once **DIR** . '/../includes/...'.

L'email n'arrive pas (SMTP Error: Could not authenticate)
Vérifiez que la validation en 2 étapes de Google est activée.
Vérifiez que vous utilisez un Mot de passe d'application de 16 lettres, et non votre vrai mot de passe Google.
Assurez-vous d'avoir enlevé les espaces dans le mot de passe collé dans config.php.
📝 Notes de développement
Sans Composer : Le projet a été conçu pour fonctionner sans exécution de ligne de commande Composer, idéal pour un déploiement rapide sur des hébergements classiques ou des environnements Wamp locaux fermés.
Sécurité : La validation de l'email a été assouplie côté login pour permettre aux administrateurs de créer des comptes manuellement sans que l'utilisateur ait besoin de cliquer sur un lien.

Avis : Les clients ne peuvent laisser un avis que sur une réservation terminée, et doivent avoir effectué une réservation pour pouvoir noter.
Frais de paiement : Les frais sont calculés automatiquement en fonction de la méthode choisie, et affichés clairement avant la validation du paiement.
Merci d'avoir choisi Trade Center Location ! Nous espérons que cette application vous apportera une expérience de gestion de location de véhicules fluide et efficace. N'hésitez pas à nous contacter pour toute question ou suggestion d'amélioration.

Développé avec ❤️ pour Trade Center Location.

### Pourquoi ce README est professionnel ?

1. **Les badges** au tout début donnent un aspect "GitHub professionnel" immédiatement.
2. **La séparation claire** des fonctionnalités Client / Admin / Technique permet à quelqu'un d'autre de comprendre le projet en 30 secondes.
3. **La section "Dépannage"** est cruciale : elle documente les problèmes exacts que nous avons résolus ensemble aujourd'hui (le 403, le PATH Wamp, l'authentification Gmail). Si vous revenez sur ce projet dans 6 mois, vous saurez exactement quoi vérifier !
4. **La structure des dossiers** explique clairement pourquoi des dossiers comme `PHPMailer-master` se trouvent dans `includes/`.
