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
        /* Styles pour l'impression */
        @media print {
            /* Fond de sécurité pour impression - motif de sécurité */
            body {
                font-family: 'Times New Roman', serif;
                font-size: 12pt;
                line-height: 1.6;
                color: #000;
                margin: 0;
                padding: 0;
                position: relative;
            }
            
            /* Arrière-plan de sécurité avec filigrane */
            body::before {
                content: "";
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: 
                    /* Motif de points de sécurité subtils */
                    radial-gradient(circle at 10px 10px, rgba(0, 100, 200, 0.05) 1px, transparent 2px),
                    radial-gradient(circle at 30px 30px, rgba(0, 100, 200, 0.05) 1px, transparent 2px),
                    radial-gradient(circle at 50px 50px, rgba(0, 100, 200, 0.05) 1px, transparent 2px),
                    /* Fond avec légère teinte bleue pour la sécurité */
                    linear-gradient(45deg, rgba(240, 248, 255, 0.3) 25%, transparent 25%, transparent 75%, rgba(240, 248, 255, 0.3) 75%, rgba(240, 248, 255, 0.3)),
                    linear-gradient(45deg, rgba(240, 248, 255, 0.3) 25%, transparent 25%, transparent 75%, rgba(240, 248, 255, 0.3) 75%, rgba(240, 248, 255, 0.3));
                background-size: 
                    40px 40px,
                    40px 40px,
                    40px 40px,
                    20px 20px,
                    20px 20px;
                background-position: 
                    0 0,
                    20px 20px,
                    40px 40px,
                    0 0,
                    10px 10px;
                z-index: -1;
                opacity: 0.3;
                pointer-events: none;
            }
            
            /* Texte de sécurité en filigrane */
            body::after {
                content: "DOCUMENT OFFICIEL - RÉPUBLIQUE DÉMOCRATIQUE DU CONGO - MINISTÈRE DES FINANCES";
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 40px;
                font-weight: bold;
                color: rgba(0, 50, 150, 0.08);
                white-space: nowrap;
                z-index: -1;
                pointer-events: none;
                letter-spacing: 2px;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            header, nav, .document-actions-view, footer,
            .document-meta, .no-print, .pdf-timbre,
            .search-modal, .modal-backdrop {
                display: none !important;
            }
            
            /* QR Code pour l'impression - Visible uniquement en impression */
            .qr-code-print {
                display: block !important;
                position: fixed;
                bottom: 30px;
                left: 50%;
                transform: translateX(-50%);
                width: 150px;
                height: 150px;
                z-index: 100;
                page-break-inside: avoid;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                text-align: center;
            }
            
            .qr-code-print img {
                width: 100%;
                height: 100%;
                border: 2px solid #2c3e50;
                border-radius: 8px;
                background: white;
                padding: 5px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                display: block;
                margin: 0 auto;
            }
            
            .qr-code-label {
                text-align: center;
                font-size: 9pt;
                color: #666;
                margin-top: 8px;
                font-family: 'Arial', sans-serif;
                font-weight: bold;
                line-height: 1.3;
            }
            
            /* Section QR Code visible uniquement à l'écran */
            .qr-code-section {
                display: none !important;
            }
            
            .container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
                background: transparent !important;
            }
            
            .document-view {
                width: 100%;
                margin: 0;
                padding: 20px;
                background: rgba(255, 255, 255, 0.95);
                border: 1px solid #ccc;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                position: relative;
                margin-bottom: 150px; /* Espace pour le QR code */
            }
            
            /* Bordure de sécurité autour du document */
            .document-view::before {
                content: "";
                position: absolute;
                top: -5px;
                left: -5px;
                right: -5px;
                bottom: -5px;
                border: 2px dashed rgba(0, 100, 200, 0.3);
                pointer-events: none;
                z-index: -1;
            }
            
            /* Lignes de sécurité dans le fond du document */
            .document-view::after {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: 
                    repeating-linear-gradient(
                        0deg,
                        transparent,
                        transparent 24px,
                        rgba(0, 100, 200, 0.05) 24px,
                        rgba(0, 100, 200, 0.05) 25px
                    );
                pointer-events: none;
                z-index: -1;
            }
            
            .document-header-view {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 15px;
                border-bottom: 3px double #2c3e50;
                position: relative;
                padding-top: 60px; /* Espace pour le timbre */
            }
            
            .document-header-view h2 {
                font-size: 16pt;
                color: #000;
                margin-bottom: 10px;
                font-weight: bold;
            }
            
            .cabinet-header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #2c3e50;
                padding-bottom: 15px;
                background: rgba(240, 248, 255, 0.5);
                padding: 15px;
                border-radius: 5px;
            }
            
            .cabinet-header h3 {
                font-size: 14pt;
                font-weight: bold;
                margin-bottom: 5px;
                color: #2c3e50;
            }
            
            .document-content-view {
                margin: 0;
                padding: 0;
                position: relative;
            }
            
            .document-body {
                font-size: 12pt;
                line-height: 1.8;
                text-align: justify;
                text-justify: inter-word;
                margin: 20px 0;
                padding: 0 10px;
            }
            
            .document-body p {
                margin-bottom: 15px;
                text-indent: 30px;
            }
            
            .signature-section {
                margin-top: 100px;
                padding-top: 30px;
                border-top: 2px solid #2c3e50;
                text-align: right;
                position: relative;
            }
            
            .signature-line {
                width: 300px;
                border-top: 2px solid #000;
                margin-left: auto;
                margin-top: 80px;
                position: relative;
            }
            
            .signature-line::before {
                content: "Signature";
                position: absolute;
                top: -25px;
                left: 0;
                font-size: 10pt;
                color: #666;
            }
            
            /* Timbre d'horodatage DORÉ intégré dans l'entête */
            .timbre-horodatage {
                display: block !important;
                position: absolute;
                top: 10px;
                right: 20px;
                background: linear-gradient(135deg, #FFD700 0%, #DAA520 25%, #B8860B 50%, #DAA520 75%, #FFD700 100%);
                color: #8B4513;
                padding: 10px 15px;
                border-radius: 8px;
                font-family: 'Courier New', monospace;
                font-size: 10pt;
                font-weight: bold;
                border: 2px solid #B8860B;
                text-align: center;
                z-index: 100;
                box-shadow: 2px 2px 8px rgba(0,0,0,0.2);
                opacity: 1;
                text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.7);
                min-width: 180px;
                max-width: 180px;
                line-height: 1.3;
                page-break-inside: avoid;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                /* Effet de relief */
                box-shadow: 
                    0 0 0 1px rgba(139, 69, 19, 0.3),
                    2px 2px 6px rgba(0, 0, 0, 0.15);
            }
            
            /* Effet de coins arrondis avec décoration */
            .timbre-horodatage::before {
                content: "✪";
                position: absolute;
                top: -8px;
                left: 10px;
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
            
            .timbre-horodatage::after {
                content: "✪";
                position: absolute;
                bottom: -8px;
                right: 10px;
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
            
            .timbre-horodatage strong {
                color: #8B4513;
                display: block;
                margin-bottom: 5px;
                font-size: 11pt;
                border-bottom: 1px solid rgba(139, 69, 19, 0.3);
                padding-bottom: 3px;
            }
            
            .timbre-horodatage small {
                display: block;
                font-size: 8pt;
                margin-top: 5px;
                color: #8B4513;
                font-weight: normal;
            }
            
            /* Masquer le timbre sur toutes les pages sauf la première */
            @page {
                margin: 2cm;
                @bottom-right {
                    content: "Document sécurisé - " counter(page) " sur " counter(pages);
                    font-size: 9pt;
                    color: #666;
                    font-family: 'Courier New', monospace;
                }
                
                @bottom-left {
                    content: "Réf: <?php echo $document['reference']; ?>";
                    font-size: 9pt;
                    color: #666;
                    font-family: 'Courier New', monospace;
                }
            }
            
            /* Pour la première page de l'impression */
            @page :first {
                @top-center {
                    content: "DOCUMENT OFFICIEL - COPIE D'ARCHIVAGE";
                    font-size: 10pt;
                    color: #999;
                    font-weight: bold;
                    letter-spacing: 1px;
                }
            }
            
            /* Masquer le timbre sur les autres pages */
            @page :not(:first) {
                .timbre-horodatage {
                    display: none !important;
                }
            }
            
            /* Élément de sécurité anti-copie */
            .security-pattern {
                display: block !important;
                position: fixed;
                bottom: 10px;
                left: 10px;
                right: 10px;
                height: 2px;
                background: repeating-linear-gradient(
                    90deg,
                    transparent,
                    transparent 5px,
                    #000 5px,
                    #000 10px
                );
                z-index: 999;
                opacity: 0.5;
            }
            
            /* En-tête de sécurité */
            .letter-head {
                text-align: center;
                padding-bottom: 20px;
                margin-bottom: 20px;
                border-bottom: 2px solid #2c3e50;
                position: relative;
            }
            
            .letter-head h2 {
                font-size: 22px;
                color: #2c3e50;
                margin-bottom: 5px;
                letter-spacing: 1px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            }
            
            .letter-head p {
                color: #2c3e50;
                font-size: 14px;
                font-weight: bold;
                margin: 5px 0;
            }
        }
        
        /* Styles pour l'écran */
        .timbre-horodatage {
            display: none; /* Caché à l'écran, visible uniquement à l'impression */
        }
        
        .security-pattern {
            display: none;
        }
        
        .qr-code-print {
            display: none; /* Caché à l'écran, visible uniquement à l'impression */
        }
        
        /* Aperçu du timbre en doré pour le mode écran */
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
        
        .timbre-preview::before {
            content: "✪";
            position: absolute;
            top: -8px;
            left: 10px;
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
        
        .timbre-preview::after {
            content: "✪";
            position: absolute;
            bottom: -8px;
            right: 10px;
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
        
        /* Styles pour la lettre */
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
        
        /* Style pour le chargement du PDF */
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
        
        /* Modal de recherche */
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
        
        /* Bouton de recherche dans la navigation */
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
    <!-- Timbre d'horodatage DORÉ (visible uniquement à l'impression, première page) -->
    <div class="timbre-horodatage">
        <strong>TIMBRE OFFICIEL</strong>
        <?php 
        echo "Ref: " . $document['reference'] . "<br>";
        echo date('d/m/Y H:i:s', strtotime($document['date_creation'])) . "<br>";
        echo "<small>Document certifié électroniquement</small>";
        ?>
    </div>
    
    <!-- QR Code pour l'impression (visible uniquement à l'impression) -->
    <?php if (!empty($document['qr_code'])): ?>
    <div class="qr-code-print">
        <img src="qrcodes/<?php echo $document['qr_code']; ?>" 
             alt="QR Code de vérification">
        <div class="qr-code-label">
            Scannez ce code QR pour vérifier l'authenticité du document<br>
            Référence: <?php echo $document['reference']; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Motif de sécurité anti-copie (visible uniquement à l'impression) -->
    <div class="security-pattern"></div>
    
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
    
    <!-- Aperçu du timbre pour l'écran -->
    <div class="no-print" style="text-align: center;">
        <div class="timbre-preview">
            <strong><i class="fas fa-stamp" style="color: #8B4513;"></i> TIMBRE OFFICIEL</strong>
            <?php 
            echo "Ref: " . $document['reference'] . "<br>";
            echo date('d/m/Y H:i:s', strtotime($document['date_creation'])) . "<br>";
            echo "<small>Document certifié électroniquement</small>";
            ?>
        </div>
        <p style="font-size: 12px; color: #666; margin-top: 5px;">
            <i class="fas fa-info-circle"></i> Ce timbre apparaîtra dans l'entête de la première page à l'impression.
        </p>
        <?php if (!empty($document['qr_code'])): ?>
        <p style="font-size: 12px; color: #666; margin-top: 5px;">
            <i class="fas fa-qrcode"></i> Le QR code de vérification apparaîtra en bas de page, centré, à l'impression.
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
                Pour imprimer ce document, utilisez le bouton "Imprimer" ou Ctrl+P. Le document imprimé bénéficiera d'un fond de sécurité spécial.
                <br><small><i class="fas fa-stamp"></i> Le timbre officiel apparaîtra dans l'entête de la première page uniquement.</small>
                <?php if (!empty($document['qr_code'])): ?>
                <br><small><i class="fas fa-qrcode"></i> Le QR code de vérification apparaîtra en bas de page, centré.</small>
                <?php endif; ?>
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
                        // N'utilisez PAS htmlspecialchars() ici car cela convertirait les balises HTML en entités
                        $contenu = $document['contenu']; // Ne pas échapper ici
                        
                        // Si vous voulez autoriser uniquement certaines balises HTML sécurisées
                        // Vous pouvez utiliser strip_tags() avec les balises autorisées
                        $contenu = strip_tags($contenu, '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6>');
                        
                        // Diviser en paragraphes basés sur les sauts de ligne doubles
                        $paragraphes = preg_split('/\n\s*\n/', $contenu);
                        
                        foreach ($paragraphes as $paragraphe) {
                            if (trim($paragraphe) !== '') {
                                // Remplacer les simples sauts de ligne par <br>
                                $paragraphe = nl2br(trim($paragraphe));
                                echo '<p>' . $paragraphe . '</p>';
                            }
                        }
                        ?>
                    </div>
                    
                    <!-- Section signature -->
                    <div class="signature-section">
                        <div class="signature-placeholder">
                            <p style="margin-bottom: 30px;">Veuillez agréer, Monsieur/Madame, l'expression de notre considération distinguée.</p>
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
                            Scannez ce QR code pour vérifier l'authenticité du document
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
                        <i class="fas fa-print"></i> Imprimer avec sécurité
                    </button>
                    <button onclick="telechargerPDF()" class="btn btn-info">
                        <i class="fas fa-download"></i> Télécharger PDF sécurisé
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
        // Mettre à jour le timbre avant impression
        const timbre = document.querySelector('.timbre-horodatage');
        if (timbre) {
            const now = new Date();
            const dateStr = now.toLocaleDateString('fr-FR');
            const timeStr = now.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            
            timbre.innerHTML = `
                <strong>TIMBRE OFFICIEL</strong>
                Ref: <?php echo $document['reference']; ?><br>
                ${dateStr} ${timeStr}<br>
                <small>Document imprimé sécurisé</small>
            `;
        }
        
        window.print();
    }
    
    function telechargerPDF() {
        // Afficher l'écran de chargement
        document.getElementById('pdfLoading').style.display = 'flex';
        
        const now = new Date();
        const dateStr = now.toLocaleDateString('fr-FR');
        const timeStr = now.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
        
        // Créer une copie du contenu pour le PDF
        const originalContent = document.getElementById('documentContent');
        const pdfContent = originalContent.cloneNode(true);
        
        // Ajouter les styles d'impression au contenu cloné
        pdfContent.style.width = '100%';
        pdfContent.style.padding = '20px';
        pdfContent.style.backgroundColor = 'white';
        pdfContent.style.fontFamily = 'Times New Roman, serif';
        pdfContent.style.fontSize = '12pt';
        pdfContent.style.lineHeight = '1.6';
        pdfContent.style.color = '#000';
        pdfContent.style.position = 'relative';
        pdfContent.style.marginBottom = '150px'; /* Espace pour le QR code */
        
        // Ajouter un timbre doré au contenu PDF (dans l'entête)
        const timbre = document.createElement('div');
        timbre.style.position = 'absolute';
        timbre.style.top = '10px';
        timbre.style.right = '20px';
        timbre.style.background = 'linear-gradient(135deg, #FFD700 0%, #DAA520 25%, #B8860B 50%, #DAA520 75%, #FFD700 100%)';
        timbre.style.color = '#8B4513';
        timbre.style.padding = '10px 15px';
        timbre.style.borderRadius = '8px';
        timbre.style.fontFamily = 'Courier New, monospace';
        timbre.style.fontSize = '10pt';
        timbre.style.fontWeight = 'bold';
        timbre.style.border = '2px solid #B8860B';
        timbre.style.textAlign = 'center';
        timbre.style.boxShadow = '2px 2px 8px rgba(0,0,0,0.2)';
        timbre.style.textShadow = '1px 1px 2px rgba(255, 255, 255, 0.7)';
        timbre.style.minWidth = '180px';
        timbre.style.maxWidth = '180px';
        timbre.style.zIndex = '100';
        timbre.style.pageBreakInside = 'avoid';
        
        // Ajouter les décorations
        const decor1 = document.createElement('div');
        decor1.innerHTML = '✪';
        decor1.style.position = 'absolute';
        decor1.style.top = '-8px';
        decor1.style.left = '10px';
        decor1.style.fontSize = '12px';
        decor1.style.color = '#8B4513';
        decor1.style.background = '#FFD700';
        decor1.style.width = '20px';
        decor1.style.height = '20px';
        decor1.style.borderRadius = '50%';
        decor1.style.display = 'flex';
        decor1.style.alignItems = 'center';
        decor1.style.justifyContent = 'center';
        decor1.style.border = '1px solid #B8860B';
        
        const decor2 = document.createElement('div');
        decor2.innerHTML = '✪';
        decor2.style.position = 'absolute';
        decor2.style.bottom = '-8px';
        decor2.style.right = '10px';
        decor2.style.fontSize = '12px';
        decor2.style.color = '#8B4513';
        decor2.style.background = '#FFD700';
        decor2.style.width = '20px';
        decor2.style.height = '20px';
        decor2.style.borderRadius = '50%';
        decor2.style.display = 'flex';
        decor2.style.alignItems = 'center';
        decor2.style.justifyContent = 'center';
        decor2.style.border = '1px solid #B8860B';
        
        timbre.innerHTML = `
            <strong>TIMBRE OFFICIEL</strong><br>
            Ref: <?php echo $document['reference']; ?><br>
            ${dateStr} ${timeStr}<br>
            <small>Document PDF sécurisé</small>
        `;
        
        timbre.appendChild(decor1);
        timbre.appendChild(decor2);
        
        // Insérer le timbre comme premier enfant du contenu
        pdfContent.insertBefore(timbre, pdfContent.firstChild);
        
        <?php if (!empty($document['qr_code'])): ?>
        // Ajouter le QR code centré au PDF (en bas de page)
        const qrCodeImg = document.querySelector('.qr-code-image');
        if (qrCodeImg) {
            const qrCodeContainer = document.createElement('div');
            qrCodeContainer.style.position = 'absolute';
            qrCodeContainer.style.bottom = '30px';
            qrCodeContainer.style.left = '50%';
            qrCodeContainer.style.transform = 'translateX(-50%)';
            qrCodeContainer.style.width = '150px';
            qrCodeContainer.style.height = '150px';
            qrCodeContainer.style.zIndex = '100';
            qrCodeContainer.style.pageBreakInside = 'avoid';
            qrCodeContainer.style.textAlign = 'center';
            
            const qrCodeClone = qrCodeImg.cloneNode(true);
            qrCodeClone.style.width = '100%';
            qrCodeClone.style.height = '100%';
            qrCodeClone.style.border = '2px solid #2c3e50';
            qrCodeClone.style.borderRadius = '8px';
            qrCodeClone.style.padding = '5px';
            qrCodeClone.style.backgroundColor = 'white';
            qrCodeClone.style.display = 'block';
            qrCodeClone.style.margin = '0 auto';
            qrCodeClone.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.1)';
            
            // Ajouter le label du QR code
            const qrLabel = document.createElement('div');
            qrLabel.style.textAlign = 'center';
            qrLabel.style.fontSize = '9pt';
            qrLabel.style.color = '#666';
            qrLabel.style.marginTop = '8px';
            qrLabel.style.fontFamily = 'Arial, sans-serif';
            qrLabel.style.fontWeight = 'bold';
            qrLabel.style.lineHeight = '1.3';
            qrLabel.innerHTML = 'Scannez ce code QR pour vérifier l\'authenticité du document<br>Référence: <?php echo $document['reference']; ?>';
            
            qrCodeContainer.appendChild(qrCodeClone);
            qrCodeContainer.appendChild(qrLabel);
            
            // Ajouter le QR code à la fin du contenu PDF
            const signatureSection = pdfContent.querySelector('.signature-section');
            if (signatureSection) {
                signatureSection.parentNode.insertBefore(qrCodeContainer, signatureSection.nextSibling);
            } else {
                pdfContent.appendChild(qrCodeContainer);
            }
        }
        <?php endif; ?>
        
        // Ajouter un conteneur temporaire pour le PDF
        const pdfContainer = document.createElement('div');
        pdfContainer.style.position = 'fixed';
        pdfContainer.style.left = '-9999px';
        pdfContainer.style.top = '0';
        pdfContainer.style.width = '210mm'; // A4 width
        pdfContainer.style.minHeight = '297mm'; // A4 height
        pdfContainer.style.padding = '20mm';
        pdfContainer.style.backgroundColor = 'white';
        pdfContainer.style.boxSizing = 'border-box';
        pdfContainer.style.position = 'relative';
        pdfContainer.appendChild(pdfContent);
        
        document.body.appendChild(pdfContainer);
        
        // Configuration pour le PDF
        const opt = {
            margin: [20, 20, 20, 20],
            filename: `DOCUMENT_<?php echo $document['reference']; ?>_${dateStr.replace(/\//g, '-')}.pdf`,
            image: { 
                type: 'jpeg', 
                quality: 0.98 
            },
            html2canvas: { 
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
                width: pdfContainer.offsetWidth,
                windowWidth: pdfContainer.offsetWidth
            },
            jsPDF: { 
                unit: 'mm', 
                format: 'a4', 
                orientation: 'portrait',
                compress: true
            },
            pagebreak: { 
                mode: ['avoid-all', 'css', 'legacy'],
                before: '.signature-section' // Éviter de couper la section signature
            }
        };
        
        // Générer et télécharger le PDF
        html2pdf()
            .set(opt)
            .from(pdfContainer)
            .save()
            .then(() => {
                // Masquer l'écran de chargement
                document.getElementById('pdfLoading').style.display = 'none';
                
                // Supprimer le conteneur temporaire
                document.body.removeChild(pdfContainer);
            })
            .catch((error) => {
                console.error('Erreur lors de la génération du PDF:', error);
                document.getElementById('pdfLoading').style.display = 'none';
                document.body.removeChild(pdfContainer);
                alert('Une erreur est survenue lors de la génération du PDF. Veuillez réessayer.');
            });
    }
    
    // Fonctions pour la modal de recherche
    function openSearchModal() {
        document.getElementById('searchModal').style.display = 'flex';
    }
    
    function closeSearchModal() {
        document.getElementById('searchModal').style.display = 'none';
    }
    
    // Fermer la modal en cliquant à l'extérieur
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('searchModal');
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeSearchModal();
            }
        });
        
        // Raccourci clavier pour la recherche (Ctrl+F)
        document.addEventListener('keydown', function(event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 'f') {
                event.preventDefault();
                openSearchModal();
            }
            
            // Échap pour fermer la modal
            if (event.key === 'Escape') {
                closeSearchModal();
            }
            
            // Ctrl+P pour imprimer
            if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
                event.preventDefault();
                imprimerDocument();
            }
        });
        
        // Amélioration de l'impression
        window.addEventListener('beforeprint', function() {
            document.title = "DOCUMENT SECURISE - <?php echo $document['reference']; ?> - <?php echo APP_NAME; ?>";
        });
        
        window.addEventListener('afterprint', function() {
            document.title = "Document <?php echo $document['reference']; ?> - <?php echo APP_NAME; ?>";
        });
        
        // Afficher un message de sécurité pour l'impression
        const printBtn = document.querySelector('button[onclick="imprimerDocument()"]');
        if (printBtn) {
            printBtn.title = "Imprimer avec fond de sécurité anti-fraude (Ctrl+P) - Timbre dans l'entête (première page) et QR code centré en bas de page";
        }
    });
    
    // Remplir automatiquement la date d'aujourd'hui dans le champ date
    document.addEventListener('DOMContentLoaded', function() {
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