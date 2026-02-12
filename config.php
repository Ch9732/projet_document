<?php
// Configuration de l'application
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'cabinet_documents');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de l'application
define('APP_NAME', 'Cabinet Document Manager');
define('APP_URL', 'http://localhost/projet_documents');
define('SECRET_KEY', 'votre_cle_secrete_unique_ici_changez_la');

// Dossiers
define('QR_CODE_DIR', __DIR__ . '/../qrcodes/');

// Configuration du QR Code
define('QR_CODE_SIZE', 10);
define('QR_CODE_MARGIN', 2);

// Timezone
date_default_timezone_set('Africa/Kinshasa');

// Vérifier si les dossiers existent
if (!file_exists(QR_CODE_DIR)) {
    mkdir(QR_CODE_DIR, 0777, true);
}

// Fonction de débogage
function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
?>