<?php
require_once 'config.php';

class QRCodeGenerator {
    
    public static function generateQRCode($data, $filename) {
        // Inclure la bibliothèque phpqrcode
        require_once __DIR__ . '/../libs/phpqrcode/qrlib.php';
        
        // Chemin complet du fichier
        $filepath = QR_CODE_DIR . $filename;
        
        // Générer le QR Code
        QRcode::png($data, $filepath, QR_CODE_SIZE, QR_CODE_MARGIN);
        
        return $filename;
    }
    
    public static function generateSecureHash($data) {
        // Créer un hash sécurisé avec les données et une clé secrète
        $data_string = json_encode($data);
        return hash_hmac('sha256', $data_string, SECRET_KEY);
    }
    
    public static function verifyDocument($reference, $hash) {
        $pdo = Database::getConnection();
        
        $query = "SELECT * FROM documents WHERE reference = :reference AND hash_verification = :hash";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':reference' => $reference,
            ':hash' => $hash
        ]);
        
        return $stmt->fetch();
    }
    
    public static function getVerificationURL($reference, $hash) {
        return APP_URL . '/verifier.php?ref=' . urlencode($reference) . '&hash=' . urlencode($hash);
    }
}
?>