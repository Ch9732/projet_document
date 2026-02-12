<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);

$pdo = Database::getConnection();
$query = "SELECT * FROM documents WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute([':id' => $id]);
$document = $stmt->fetch();

if (!$document) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document <?php echo $document['reference']; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bibliothèques pour générer PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        /* Styles pour l'impression - VERSION SIMPLIFIÉE */
        @media print {
            * {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
                border: none !important;
            }
            
            body {
                font-family: 'Times New Roman', serif;
                font-size: 12pt;
                line-height: 1.6;
                color: #000;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                width: 210mm;
                min-height: 297mm;
            }
            
            header, nav, .document-actions-view, footer,
            .document-meta, .no-print, .pdf-timbre,
            .search-modal, .modal-backdrop,
            .timbre-horodatage, .security-pattern,
            .timbre-preview, .letter-head,
            .cabinet-header, .qr-code-section,
            .signature-placeholder {
                display: none !important;
            }
            
            .qr-code-print {
                display: block !important;
                position: fixed;
                bottom: 20mm;
                left: 50%;
                transform: translateX(-50%);
                width: 50mm;
                height: 50mm;
                z-index: 100;
                page-break-inside: avoid;
                text-align: center;
            }
            
            .qr-code-print img {
                width: 100%;
                height: 100%;
                display: block;
                margin: 0 auto;
            }
            
            .qr-code-label {
                text-align: center;
                font-size: 9pt;
                color: #000;
                margin-top: 5mm;
                font-family: 'Arial', sans-serif;
                line-height: 1.3;
            }
            
            /* Ajout de la référence sous le QR code */
            .qr-code-reference {
                text-align: center;
                font-size: 9pt;
                color: #000;
                margin-top: 3mm;
                font-family: 'Arial', sans-serif;
                font-weight: bold;
                line-height: 1.3;
            }
            
            .signature-section {
                display: block !important;
                margin-top: 30mm !important;
                padding-top: 10mm !important;
                border-top: 1px solid #ccc !important;
                text-align: right !important;
                position: relative !important;
                page-break-inside: avoid !important;
            }
            
            .signature-placeholder-print {
                display: block !important;
                width: 300px !important;
                margin-left: auto !important;
                text-align: center !important;
                position: relative !important;
            }
            
            .signature-placeholder-print p {
                margin-bottom: 5mm !important;
                font-size: 11pt !important;
                text-align: center !important;
            }
            
            .signature-line {
                display: block !important;
                width: 250px !important;
                border-top: 1px solid #000 !important;
                margin: 20mm auto 5mm !important;
            }
            
            .signature-placeholder-print p strong {
                font-size: 11pt !important;
                display: block !important;
                margin-top: 2mm !important;
            }
            
            .signature-placeholder-print p:last-child {
                margin-top: 2mm !important;
                font-size: 10pt !important;
            }
            
            .container {
                width: 100% !important;
                margin: 0 !important;
                padding: 15mm !important;
                box-shadow: none !important;
                background: white !important;
                border: none !important;
            }
            
            .document-view {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                border: none !important;
                box-shadow: none !important;
                margin-bottom: 80mm !important;
            }
            
            .document-header-view {
                text-align: left;
                margin-bottom: 10mm;
                padding-bottom: 5mm;
                border-bottom: 1px solid #ccc;
            }
            
            .document-header-view h2 {
                font-size: 14pt;
                color: #000;
                margin-bottom: 5mm;
                font-weight: bold;
            }
            
            .document-content-view {
                margin: 0;
                padding: 0;
            }
            
            .document-body {
                font-size: 12pt;
                line-height: 1.6;
                text-align: justify;
                margin: 0;
                padding: 0;
            }
            
            .document-body p {
                margin-bottom: 5mm;
                text-indent: 10mm;
            }
            
            .footer-reference {
                display: block !important;
                position: fixed;
                bottom: 10mm;
                left: 15mm;
                font-size: 9pt;
                color: #666;
                font-family: 'Arial', sans-serif;
                page-break-inside: avoid;
            }
            
            @page {
                size: A4;
                margin: 15mm;
                @bottom-right {
                    content: "Page " counter(page) " sur " counter(pages);
                    font-size: 9pt;
                    color: #666;
                }
            }
            
            /* Empêcher la coupure sur plusieurs pages */
            body, .container, .document-view, .qr-code-print, .footer-reference {
                page-break-inside: avoid !important;
            }
        }
        
        /* Styles pour l'écran */
        .timbre-horodatage, .qr-code-print, .security-pattern, .footer-reference {
            display: none;
        }
        
        .timbre-preview {
            background: linear-gradient(135deg, #FFD700 0%, #DAA520 25%, #B8860B 50%, #DAA520 75%, #FFD700 100%);
            color: #8B4513;
            padding: 12px 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            font-weight: bold;
            border: 2px solid #B8860B;
            display: inline-block;
            margin: 10px 0;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
            text-align: center;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.7);
            position: relative;
        }
        
        .timbre-preview::before,
        .timbre-preview::after {
            content: "✪";
            position: absolute;
            font-size: 12px;
            color: #8B4513;
            background: #FFD700;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #B8860B;
        }
        .timbre-preview::before { top: -8px; left: 10px; }
        .timbre-preview::after { bottom: -8px; right: 10px; }
        
        .timbre-preview strong {
            color: #8B4513;
            display: block;
            margin-bottom: 3px;
            font-size: 11pt;
            border-bottom: 1px solid rgba(139, 69, 19, 0.3);
            padding-bottom: 3px;
        }
        
        .timbre-preview small {
            display: block;
            font-size: 8pt;
            margin-top: 3px;
            color: #8B4513;
            font-weight: normal;
        }
        
        .document-body {
            text-align: justify;
            text-justify: inter-word;
            line-height: 1.8;
        }
        
        .no-print {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        
        .no-print i {
            color: #ff9800;
            margin-right: 5px;
        }
        
        .letter-head {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .letter-head h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .letter-head p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .document-meta {
            margin: 10px 0;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #e9ecef;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 5px;
            color: #495057;
        }
        
        .qr-code-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
        
        .qr-code-image {
            width: 150px;
            height: 150px;
            margin: 15px auto;
            display: block;
        }
        
        .signature-section {
            margin-top: 60px;
            padding: 20px;
        }
        
        .signature-placeholder {
            width: 300px;
            margin-left: auto;
            text-align: center;
        }
        
        .signature-line {
            width: 250px;
            border-top: 1px solid #333;
            margin: 40px auto 10px;
        }
        
        .pdf-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 99999;
        }
        
        .pdf-loading-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        
        .pdf-loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .search-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .search-modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }
        
        .search-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .search-modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close-search-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .close-search-modal:hover {
            color: #e74c3c;
        }
        
        .search-form-group {
            margin-bottom: 20px;
        }
        
        .search-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .search-form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .search-form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .search-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        
        .search-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .search-btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .search-btn-primary:hover {
            background-color: #2980b9;
        }
        
        .search-btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .search-btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .search-nav-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }
        
        .search-nav-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <!-- QR Code pour l'impression (visible uniquement à l'impression) -->
    <?php if (!empty($document['qr_code'])): ?>
    <div class="qr-code-print">
        <img src="qrcodes/<?php echo $document['qr_code']; ?>" 
             alt="QR Code de vérification">
        <div class="qr-code-label">
            Scannez pour vérifier<br>
            Réf: <?php echo $document['reference']; ?><br>
            Site officiel : https://dgdp.cd
        </div>
        <!-- Ajout de la référence en bas du QR code -->
        <div class="qr-code-reference">
            Référence: <?php echo $document['reference']; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Référence en bas de page (visible uniquement à l'impression) -->
    <div class="footer-reference">
        Référence: <?php echo $document['reference']; ?>
    </div>
    
    <!-- Écran de chargement pour PDF -->
    <div class="pdf-loading" id="pdfLoading">
        <div class="pdf-loading-content">
            <div class="pdf-loading-spinner"></div>
            <h3>Génération du PDF en cours...</h3>
            <p>Veuillez patienter pendant la création du document.</p>
            <p><small>Cette opération peut prendre quelques secondes.</small></p>
        </div>
    </div>
    
    <!-- Modal de recherche -->
    <div class="search-modal" id="searchModal">
        <div class="search-modal-content">
            <div class="search-modal-header">
                <h3><i class="fas fa-search"></i> Rechercher un Document</h3>
                <button class="close-search-modal" onclick="closeSearchModal()">&times;</button>
            </div>
            <form id="searchForm" method="GET" action="index.php">
                <div class="search-form-group">
                    <label for="search_reference"><i class="fas fa-barcode"></i> Référence du document</label>
                    <input type="text" id="search_reference" name="search_reference" 
                           placeholder="Ex: DOC-20240315-ABC123">
                </div>
                
                <div class="search-form-group">
                    <label for="search_date"><i class="fas fa-calendar"></i> Date du document</label>
                    <input type="date" id="search_date" name="search_date">
                </div>
                
                <div class="search-form-group">
                    <label for="search_titre"><i class="fas fa-heading"></i> Titre (mot-clé)</label>
                    <input type="text" id="search_titre" name="search_titre" 
                           placeholder="Mot-clé dans le titre">
                </div>
                
                <div class="search-form-actions">
                    <button type="button" class="search-btn search-btn-secondary" onclick="closeSearchModal()">
                        Annuler
                    </button>
                    <button type="submit" class="search-btn search-btn-primary">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="no-print" style="text-align: center;">
        <p style="font-size: 12px; color: #666; margin-top: 5px;">
            <i class="fas fa-info-circle"></i> À l'impression : format A4 simple avec signature, cachet et QR code.
        </p>
        <?php if (!empty($document['qr_code'])): ?>
        <p style="font-size: 12px; color: #666; margin-top: 5px;">
            <i class="fas fa-qrcode"></i> Le QR code apparaîtra en bas de page, centré, à l'impression.
        </p>
        <?php endif; ?>
    </div>

    <div class="container">
        <header class="no-print">
            <h1><i class="fas fa-file-contract"></i> Visualisation du Document</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="creer_document.php"><i class="fas fa-plus-circle"></i> Nouveau Document</a>
                <button class="search-nav-btn" onclick="openSearchModal()">
                    <i class="fas fa-search"></i> Rechercher
                </button>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </header>
        
        <main>
            <div class="no-print">
                <i class="fas fa-info-circle"></i> 
                Pour imprimer ce document, utilisez le bouton "Imprimer" ou Ctrl+P. 
                <br><small>Le document sera imprimé au format A4 avec signature, cachet et QR code.</small>
            </div>
            
            <div class="document-view" id="documentContent">
                <div class="letter-head">
                    <h2>RÉPUBLIQUE DÉMOCRATIQUE DU CONGO</h2>
                    <p>Ministère des Finances</p>
                    <p>DIRECTION GÉNÉRALE DE LA DETTE PUBLIQUE</p>
                </div>
                
                <div class="document-header-view">
                    <h2><?php echo htmlspecialchars($document['titre']); ?></h2>
                    <div class="document-meta no-print">
                        <span class="badge">Référence: <?php echo $document['reference']; ?></span>
                        <span class="badge">Date: <?php echo date('d/m/Y', strtotime($document['date_document'])); ?></span>
                        <span class="badge">Créé le: <?php echo date('d/m/Y H:i', strtotime($document['date_creation'])); ?></span>
                    </div>
                </div>
                
                <div class="document-content-view">
                    <div class="cabinet-header">
                        <p>Kinshasa le <?php echo date('d/m/Y', strtotime($document['date_document'])); ?></p>
                    </div>
                    
                    <div class="document-body">
                        <?php 
                        $contenu = $document['contenu'];
                        $contenu = strip_tags($contenu, '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6>');
                        $paragraphes = preg_split('/\n\s*\n/', $contenu);
                        foreach ($paragraphes as $paragraphe) {
                            if (trim($paragraphe) !== '') {
                                $paragraphe = nl2br(trim($paragraphe));
                                echo '<p>' . $paragraphe . '</p>';
                            }
                        }
                        ?>
                    </div>
                    
                    <!-- Section signature - Version écran -->
                    <div class="signature-section">
                        <div class="signature-placeholder">
                            <div class="signature-line"></div>
                            <p><strong>Signature et cachet</strong></p>
                            <p><strong><?php echo htmlspecialchars($document['signataire']); ?></strong></p>
                            <p>Le Directeur Général</p>
                        </div>
                    </div>
                    
                    <!-- Section signature - Version impression (cachée à l'écran) -->
                    <div class="signature-section" style="display: none;">
                        <div class="signature-placeholder-print">
                            <div class="signature-line"></div>
                            <p><strong>Signature et cachet</strong></p>
                            <p><strong><?php echo htmlspecialchars($document['signataire']); ?></strong></p>
                            <p>Le Directeur Général</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($document['qr_code'])): ?>
                    <div class="qr-code-section no-print">
                        <h4><i class="fas fa-qrcode"></i> Code QR de Vérification</h4>
                        <img src="qrcodes/<?php echo $document['qr_code']; ?>" 
                             alt="QR Code de vérification" 
                             class="qr-code-image">
                        <p class="qr-info">
                            <strong><?php echo htmlspecialchars($document['reference']); ?></strong><br>
                            Scannez ce QR code pour vérifier l'authenticité du document et accéder au site officiel.<br>
                            <strong>Site officiel : <a href="https://dgdp.cd" target="_blank">https://dgdp.cd</a></strong>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Actions sur le document -->
                <div class="document-actions-view no-print">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <?php if (!empty($document['hash_verification'])): ?>
                    <a href="verifier.php?ref=<?php echo urlencode($document['reference']); ?>&hash=<?php echo urlencode($document['hash_verification']); ?>" 
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-shield-alt"></i> Vérifier ce document
                    </a>
                    <?php endif; ?>
                    <button onclick="imprimerDocument()" class="btn btn-success">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                    <button onclick="telechargerPDF()" class="btn btn-info">
                        <i class="fas fa-download"></i> Télécharger PDF
                    </button>
                </div>
            </div>
        </main>
        
        <footer class="no-print">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - République Démocratique du Congo</p>
        </footer>
    </div>
    
    <script>
    function imprimerDocument() {
        window.print();
    }
    
    function telechargerPDF() {
        document.getElementById('pdfLoading').style.display = 'flex';
        
        const now = new Date();
        const dateStr = now.toLocaleDateString('fr-FR');
        
        const originalContent = document.getElementById('documentContent');
        const pdfContent = originalContent.cloneNode(true);
        
        pdfContent.style.width = '100%';
        pdfContent.style.padding = '15mm';
        pdfContent.style.backgroundColor = 'white';
        pdfContent.style.fontFamily = 'Times New Roman, serif';
        pdfContent.style.fontSize = '12pt';
        pdfContent.style.lineHeight = '1.6';
        pdfContent.style.color = '#000';
        pdfContent.style.marginBottom = '80mm';
        
        <?php if (!empty($document['qr_code'])): ?>
        const qrCodeImg = document.querySelector('.qr-code-image');
        if (qrCodeImg) {
            const qrCodeContainer = document.createElement('div');
            qrCodeContainer.style.position = 'absolute';
            qrCodeContainer.style.bottom = '20mm';
            qrCodeContainer.style.left = '50%';
            qrCodeContainer.style.transform = 'translateX(-50%)';
            qrCodeContainer.style.width = '50mm';
            qrCodeContainer.style.height = '50mm';
            qrCodeContainer.style.zIndex = '100';
            qrCodeContainer.style.pageBreakInside = 'avoid';
            qrCodeContainer.style.textAlign = 'center';
            
            const qrCodeClone = qrCodeImg.cloneNode(true);
            qrCodeClone.style.width = '100%';
            qrCodeClone.style.height = '100%';
            qrCodeClone.style.display = 'block';
            qrCodeClone.style.margin = '0 auto';
            
            const qrLabel = document.createElement('div');
            qrLabel.style.textAlign = 'center';
            qrLabel.style.fontSize = '9pt';
            qrLabel.style.color = '#000';
            qrLabel.style.marginTop = '5mm';
            qrLabel.style.fontFamily = 'Arial, sans-serif';
            qrLabel.style.lineHeight = '1.3';
            qrLabel.innerHTML = 'Scannez pour vérifier<br>Réf: <?php echo $document['reference']; ?><br>Site officiel : https://dgdp.cd';
            
            // Ajout de la référence sous le QR code dans le PDF
            const qrReference = document.createElement('div');
            qrReference.style.textAlign = 'center';
            qrReference.style.fontSize = '9pt';
            qrReference.style.color = '#000';
            qrReference.style.marginTop = '3mm';
            qrReference.style.fontFamily = 'Arial, sans-serif';
            qrReference.style.fontWeight = 'bold';
            qrReference.style.lineHeight = '1.3';
            qrReference.innerHTML = 'Référence: <?php echo $document['reference']; ?>';
            
            qrCodeContainer.appendChild(qrCodeClone);
            qrCodeContainer.appendChild(qrLabel);
            qrCodeContainer.appendChild(qrReference);
            
            const signatureSection = pdfContent.querySelector('.signature-section');
            if (signatureSection) {
                signatureSection.parentNode.insertBefore(qrCodeContainer, signatureSection.nextSibling);
            } else {
                pdfContent.appendChild(qrCodeContainer);
            }
        }
        <?php endif; ?>
        
        const footerReference = document.createElement('div');
        footerReference.style.position = 'absolute';
        footerReference.style.bottom = '10mm';
        footerReference.style.left = '15mm';
        footerReference.style.fontSize = '9pt';
        footerReference.style.color = '#666';
        footerReference.style.fontFamily = 'Arial, sans-serif';
        footerReference.style.pageBreakInside = 'avoid';
        footerReference.innerHTML = 'Référence: <?php echo $document['reference']; ?>';
        pdfContent.appendChild(footerReference);
        
        const pdfContainer = document.createElement('div');
        pdfContainer.style.position = 'fixed';
        pdfContainer.style.left = '-9999px';
        pdfContainer.style.top = '0';
        pdfContainer.style.width = '210mm';
        pdfContainer.style.minHeight = '297mm';
        pdfContainer.style.padding = '15mm';
        pdfContainer.style.backgroundColor = 'white';
        pdfContainer.style.boxSizing = 'border-box';
        pdfContainer.style.position = 'relative';
        pdfContainer.appendChild(pdfContent);
        
        document.body.appendChild(pdfContainer);
        
        const opt = {
            margin: [15, 15, 15, 15],
            filename: `DOCUMENT_<?php echo $document['reference']; ?>_${dateStr.replace(/\//g, '-')}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false, backgroundColor: '#ffffff', width: pdfContainer.offsetWidth, windowWidth: pdfContainer.offsetWidth },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
        };
        
        html2pdf()
            .set(opt)
            .from(pdfContainer)
            .save()
            .then(() => {
                document.getElementById('pdfLoading').style.display = 'none';
                document.body.removeChild(pdfContainer);
            })
            .catch((error) => {
                console.error('Erreur lors de la génération du PDF:', error);
                document.getElementById('pdfLoading').style.display = 'none';
                document.body.removeChild(pdfContainer);
                alert('Une erreur est survenue lors de la génération du PDF. Veuillez réessayer.');
            });
    }
    
    function openSearchModal() {
        document.getElementById('searchModal').style.display = 'flex';
    }
    
    function closeSearchModal() {
        document.getElementById('searchModal').style.display = 'none';
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('searchModal');
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeSearchModal();
            }
        });
        
        document.addEventListener('keydown', function(event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 'f') {
                event.preventDefault();
                openSearchModal();
            }
            if (event.key === 'Escape') {
                closeSearchModal();
            }
            if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
                event.preventDefault();
                imprimerDocument();
            }
        });
        
        window.addEventListener('beforeprint', function() {
            document.title = "DOCUMENT - <?php echo $document['reference']; ?>";
        });
        
        window.addEventListener('afterprint', function() {
            document.title = "Document <?php echo $document['reference']; ?> - <?php echo APP_NAME; ?>";
        });
        
        const today = new Date().toISOString().split('T')[0];
        const searchDateInput = document.getElementById('search_date');
        if (searchDateInput) {
            searchDateInput.value = today;
        }
    });
    </script>
    
    <script src="js/script.js"></script>
</body>
</html>