<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/qrcode.php';

$reference = isset($_GET['ref']) ? $_GET['ref'] : '';
$hash = isset($_GET['hash']) ? $_GET['hash'] : '';

$document = null;
$verification_status = 'invalid';

if (!empty($reference) && !empty($hash)) {
    $document = QRCodeGenerator::verifyDocument($reference, $hash);
    
    if ($document) {
        // Vérifier si le hash correspond toujours
        $data = [
            'reference' => $document['reference'],
            'titre' => $document['titre'],
            'contenu' => $document['contenu'],
            'cabinet' => $document['cabinet'],
            'date_document' => $document['date_document']
        ];
        
        $expected_hash = QRCodeGenerator::generateSecureHash($data);
        
        if ($expected_hash === $hash) {
            $verification_status = 'valid';
        } else {
            $verification_status = 'tampered';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de Document - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container verification-container">
        <header>
            <h1><i class="fas fa-shield-alt"></i> Vérification de Document</h1>
        </header>
        
        <main class="verification-main">
            <div class="verification-card">
                <div class="verification-icon">
                    <?php if ($verification_status === 'valid'): ?>
                        <i class="fas fa-check-circle fa-5x valid"></i>
                    <?php elseif ($verification_status === 'tampered'): ?>
                        <i class="fas fa-exclamation-triangle fa-5x tampered"></i>
                    <?php else: ?>
                        <i class="fas fa-times-circle fa-5x invalid"></i>
                    <?php endif; ?>
                </div>
                
                <div class="verification-status">
                    <?php if ($verification_status === 'valid'): ?>
                        <h2 class="valid">DOCUMENT VALIDE</h2>
                        <p class="verification-message">
                            Ce document a été vérifié et est authentique.
                        </p>
                    <?php elseif ($verification_status === 'tampered'): ?>
                        <h2 class="tampered">DOCUMENT AUTHENTIQUE</h2>
                        <p class="verification-message">

                        </p>
                    <?php else: ?>
                        <h2 class="invalid">DOCUMENT INVALIDE</h2>
                        <p class="verification-message">
                            Ce document n'existe pas ou le lien de vérification est incorrect.
                        </p>
                    <?php endif; ?>

                    <!-- Résumé toujours visible si le document existe -->
                    <?php if ($document): ?>
                    <div class="document-summary">
                        <p><strong>Référence :</strong> <?php echo htmlspecialchars($document['reference']); ?></p>
                        <p><strong>Signataire :</strong> <?php echo htmlspecialchars($document['signataire']); ?></p>
                        <p><strong>Date du document :</strong> <?php echo date('d/m/Y', strtotime($document['date_document'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Affiché pour tout document trouvé (valide ou modifié) -->
                <?php if ($document): ?>
                    <div class="document-verification-details">
                        <h3><i class="fas fa-file-contract"></i> Informations du Document</h3>
                        
                        <?php if ($verification_status === 'tampered'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Les informations ci-dessous sont celles enregistrées lors de la création. 
                        </div>
                        <?php endif; ?>
                        
                        <div class="details-grid">

                            <div class="detail-item">
                                <strong>Titre:</strong>
                                <span><?php echo htmlspecialchars($document['titre']); ?></span>
                            </div>
                   
                                <!-- Affichage en tant que Signataire -->

                            </div>
                            <div class="detail-item">
                                <strong>Date de Création:</strong>
                                <span><?php echo date('d/m/Y H:i', strtotime($document['date_creation'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Statut:</strong>
                                <span class="badge badge-<?php echo $document['statut'] === 'actif' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($document['statut']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="verification-hash">
                            <p><strong>Hash de vérification (original) :</strong></p>
                            <code><?php echo substr($document['hash_verification'], 0, 16) . '...'; ?></code>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="verification-actions">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                    
                    <!-- Lien vers le document complet uniquement si valide -->
                    <?php if ($document && $verification_status === 'valid'): ?>
                        <a href="voir_document.php?id=<?php echo $document['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> Voir le document complet
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - République Démocratique du Congo</p>
        </footer>
    </div>
</body>
</html>