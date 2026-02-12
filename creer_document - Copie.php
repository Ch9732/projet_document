<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $contenu = trim($_POST['contenu']);
    $cabinet = trim($_POST['cabinet']);
    $date_document = $_POST['date_document'];
    $signataire = trim($_POST['signataire']);
    
    // Validation
    if (empty($titre) || empty($contenu) || empty($cabinet) || empty($date_document) || empty($signataire)) {
        $error = 'Tous les champs sont obligatoires';
    } else {
        try {
            $pdo = Database::getConnection();
            
            // Générer une référence unique
            $reference = 'DOC-' . date('Ymd') . '-' . strtoupper(uniqid());
            
            // Insérer dans la base de données
            $query = "INSERT INTO documents (reference, titre, contenu, cabinet, date_document, signataire, qr_code, hash_verification) 
                     VALUES (:reference, :titre, :contenu, :cabinet, :date_document, :signataire, '', '')";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':reference' => $reference,
                ':titre' => $titre,
                ':contenu' => $contenu,
                ':cabinet' => $cabinet,
                ':date_document' => $date_document,
                ':signataire' => $signataire
            ]);
            
            $success = 'Document créé avec succès! Référence: ' . $reference;
            
        } catch (PDOException $e) {
            $error = 'Erreur lors de la création du document: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Document - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        /* Styles pour l'impression */
        @media print {
            header, nav, footer, .alert, .no-print {
                display: none !important;
            }
            
            body {
                font-family: 'Arial', sans-serif;
                font-size: 12pt;
                background: white;
                color: black;
                margin: 0;
                padding: 20px;
            }
            
            .container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
            }
            
            main {
                margin: 0;
                padding: 0;
            }
            
            .form-container {
                box-shadow: none;
                border: 1px solid #ddd;
                padding: 20px;
                margin: 0;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            label {
                font-weight: bold;
                display: block;
                margin-bottom: 5px;
                color: #000;
            }
            
            input, textarea {
                border: 1px solid #000;
                background: #fff;
                color: #000;
                width: 100%;
                padding: 8px;
            }
            
            h2 {
                color: #000;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            
            .form-actions {
                display: none;
            }
            
            /* Numéro de page */
            @page {
                margin: 2cm;
                @bottom-right {
                    content: "Page " counter(page) " sur " counter(pages);
                    font-size: 10pt;
                    color: #666;
                }
            }
            
            /* Masquer l'éditeur en impression */
            .ql-editor {
                border: 1px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
            }
            
            .ql-toolbar {
                display: none !important;
            }
        }
        
        /* Styles pour l'écran */
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
        
        /* Styles pour l'éditeur Quill */
        #editor-container {
            height: 300px;
            margin-bottom: 20px;
        }
        
        .ql-toolbar {
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            border: 1px solid #ddd;
            background: #f8f9fa;
        }
        
        .ql-container {
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            border: 1px solid #ddd;
            border-top: none;
            font-family: 'Times New Roman', serif;
            font-size: 14pt;
        }
        
        .ql-editor {
            min-height: 250px;
            padding: 15px;
            line-height: 1.6;
        }
        
        .ql-editor p {
            margin-bottom: 10px;
        }
        
        .editor-info {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #3498db;
        }
        
        .editor-info h4 {
            margin-top: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .editor-info ul {
            margin: 5px 0 5px 20px;
            color: #555;
        }
        
        .editor-info li {
            margin-bottom: 3px;
        }
        
        /* Prévisualisation */
        .document-preview {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            background: #fff;
            min-height: 100px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .document-preview h4 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .preview-content {
            font-family: 'Times New Roman', serif;
            font-size: 14pt;
            line-height: 1.6;
            text-align: justify;
        }
        
        .preview-content p {
            margin-bottom: 10px;
        }
        
        /* Style pour les boutons d'action */
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        
        .btn-warning:hover {
            background: #f57c00;
        }
        
        /* Correction pour l'affichage du contenu HTML */
        .html-content {
            white-space: normal !important;
            word-wrap: break-word !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-file-contract"></i> Créer un Nouveau Document</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="creer_document.php" class="active"><i class="fas fa-plus-circle"></i> Nouveau Document</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </header>
        
        <main>
            <div class="no-print">
                <i class="fas fa-info-circle"></i> 
                Pour imprimer cette page, utilisez Ctrl+P.
            </div>
            
            <div class="form-container">
                <h2>Informations du Document</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="documentForm">
                    <div class="form-group">
                        <label for="cabinet"><i class="fas fa-building"></i> Cabinet *</label>
                        <input type="text" id="cabinet" name="cabinet" required 
                               placeholder="Nom du cabinet" value="République Démocratique du Congo - Direction Générale de la Dette Publique">
                    </div>
                    
                    <div class="form-group">
                        <label for="titre"><i class="fas fa-heading"></i> Titre du Document *</label>
                        <input type="text" id="titre" name="titre" required 
                               placeholder="Titre du document">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_document"><i class="fas fa-calendar"></i> Date du Document *</label>
                        <input type="date" id="date_document" name="date_document" required 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="signataire"><i class="fas fa-signature"></i> Signataire *</label>
                        <input type="text" id="signataire" name="signataire" required 
                               placeholder="Nom et prénom du signataire">
                    </div>
                    
                    <div class="form-group">
                        <label for="contenu"><i class="fas fa-file-alt"></i> Contenu du Document *</label>
                        
                        <div class="editor-info no-print">
                            <h4><i class="fas fa-edit"></i> Éditeur de texte enrichi</h4>
                            <p>Utilisez la barre d'outils ci-dessus pour formater votre texte :</p>
                            <ul>
                                <li><strong>Gras</strong>, <em>Italique</em>, <u>Souligné</u></li>
                                <li>Listes à puces et numérotées</li>
                                <li>Alignement du texte</li>
                                <li>Insérer des liens</li>
                                <li>Annuler/Rétablir</li>
                            </ul>
                        </div>
                        
                        <!-- Éditeur Quill -->
                        <div id="editor-container">
                            <div id="editor">
                                <?php 
                                if (isset($_POST['contenu']) && !empty($_POST['contenu'])) {
                                    // Afficher le contenu sans échapper les balises HTML
                                    echo $_POST['contenu'];
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Champ caché pour stocker le contenu HTML -->
                        <input type="hidden" id="contenu" name="contenu" value="<?php echo isset($_POST['contenu']) ? htmlspecialchars($_POST['contenu']) : ''; ?>">
                        
                        <!-- Aperçu en temps réel -->
                        <div class="document-preview no-print" id="documentPreview">
                            <h4><i class="fas fa-eye"></i> Aperçu du contenu (formaté)</h4>
                            <div class="preview-content html-content" id="previewContent">
                                <?php 
                                if (isset($_POST['contenu']) && !empty($_POST['contenu'])) {
                                    // Afficher le contenu formaté (sans échapper les balises)
                                    echo $_POST['contenu'];
                                } else {
                                    echo 'Le contenu formaté apparaîtra ici...';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="window.history.back()" class="btn btn-secondary no-print">
                            <i class="fas fa-arrow-left"></i> Retour
                        </button>
                        <button type="submit" class="btn btn-primary no-print">
                            <i class="fas fa-save"></i> Créer le Document
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-info no-print">
                            <i class="fas fa-print"></i> Aperçu Impression
                        </button>
                        <button type="button" onclick="previewDocument()" class="btn btn-warning no-print">
                            <i class="fas fa-eye"></i> Prévisualiser
                        </button>
                    </div>
                </form>
            </div>
        </main>
        
        <footer class="no-print">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - République Démocratique du Congo</p>
        </footer>
    </div>
    
    <!-- Quill Editor JS -->
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script src="js/script.js"></script>
    <script>
    // Initialiser l'éditeur Quill
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'clean'],
                [{ 'color': [] }, { 'background': [] }]
            ]
        },
        placeholder: 'Rédigez votre document ici...',
        formats: [
            'header',
            'bold', 'italic', 'underline', 'strike',
            'list', 'bullet',
            'align',
            'link',
            'color', 'background'
        ]
    });
    
    // Définir le style par défaut
    quill.root.style.fontFamily = "'Times New Roman', serif";
    quill.root.style.fontSize = '14pt';
    quill.root.style.lineHeight = '1.6';
    
    // Mettre à jour le champ caché avec le contenu HTML
    function updateHiddenField() {
        var content = quill.root.innerHTML;
        document.getElementById('contenu').value = content;
        
        // Mettre à jour l'aperçu avec le contenu formaté
        document.getElementById('previewContent').innerHTML = content;
    }
    
    // Écouter les changements dans l'éditeur
    quill.on('text-change', function() {
        updateHiddenField();
    });
    
    // Initialiser le champ caché au chargement
    document.addEventListener('DOMContentLoaded', function() {
        // Récupérer le contenu existant du champ caché
        var existingContent = document.getElementById('contenu').value;
        
        // Si le contenu existe, le décoder (car il est encodé en HTML entities)
        if (existingContent) {
            // Créer un élément temporaire pour décoder le HTML
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = existingContent;
            var decodedContent = tempDiv.textContent;
            
            // Définir le contenu dans l'éditeur
            quill.root.innerHTML = decodedContent;
        }
        
        // Mettre à jour l'aperçu initial
        updateHiddenField();
        
        // Ajouter une info-bulle pour l'impression
        const printBtn = document.querySelector('button[onclick="window.print()"]');
        if (printBtn) {
            printBtn.title = "Aperçu avant impression (Ctrl+P)";
        }
    });
    
    // Prévisualiser le document complet
    function previewDocument() {
        const titre = document.getElementById('titre').value;
        const contenu = document.getElementById('contenu').value;
        const signataire = document.getElementById('signataire').value;
        const date = document.getElementById('date_document').value;
        
        if (!titre || !contenu) {
            alert('Veuillez remplir le titre et le contenu du document');
            return;
        }
        
        // Décoder le contenu HTML pour l'affichage
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = contenu;
        var decodedContent = tempDiv.innerHTML;
        
        // Ouvrir une nouvelle fenêtre pour la prévisualisation
        const previewWindow = window.open('', '_blank');
        previewWindow.document.write(`
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Prévisualisation - ${titre}</title>
                <style>
                    body {
                        font-family: 'Times New Roman', serif;
                        font-size: 12pt;
                        line-height: 1.6;
                        margin: 40px;
                        padding: 20px;
                        background: #f9f9f9;
                    }
                    .document-container {
                        background: white;
                        padding: 40px;
                        border: 1px solid #ddd;
                        box-shadow: 0 0 20px rgba(0,0,0,0.1);
                        max-width: 800px;
                        margin: 0 auto;
                    }
                    .letter-head {
                        text-align: center;
                        padding-bottom: 20px;
                        margin-bottom: 30px;
                        border-bottom: 2px solid #2c3e50;
                    }
                    .letter-head h2 {
                        color: #2c3e50;
                        margin-bottom: 5px;
                    }
                    .document-title {
                        text-align: center;
                        font-size: 18pt;
                        margin: 30px 0;
                        color: #2c3e50;
                    }
                    .document-date {
                        text-align: right;
                        margin-bottom: 30px;
                        color: #666;
                    }
                    .document-content {
                        text-align: justify;
                        line-height: 1.8;
                        margin-bottom: 50px;
                    }
                    .document-content p {
                        margin-bottom: 15px;
                    }
                    .signature-section {
                        margin-top: 100px;
                        text-align: right;
                    }
                    .signature-line {
                        width: 300px;
                        border-top: 1px solid #333;
                        margin-left: auto;
                        margin-top: 60px;
                    }
                    .print-btn {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 10px 20px;
                        background: #3498db;
                        color: white;
                        border: none;
                        border-radius: 5px;
                        cursor: pointer;
                        font-weight: bold;
                    }
                    .print-btn:hover {
                        background: #2980b9;
                    }
                </style>
            </head>
            <body>
                <button class="print-btn" onclick="window.print()">Imprimer</button>
                <div class="document-container">
                    <div class="letter-head">
                        <h2>RÉPUBLIQUE DÉMOCRATIQUE DU CONGO</h2>
                        <p>Ministère des Finances</p>
                        <p>DIRECTION GÉNÉRALE DE LA DETTE PUBLIQUE</p>
                    </div>
                    
                    <div class="document-title">${titre}</div>
                    
                    <div class="document-date">
                        <p>Kinshasa le ${new Date(date).toLocaleDateString('fr-FR')}</p>
                    </div>
                    
                    <div class="document-content">
                        ${decodedContent}
                    </div>
                    
                    <div class="signature-section">
                        <p>Veuillez agréer, Monsieur/Madame, l'expression de notre considération distinguée.</p>
                        <div class="signature-line"></div>
                        <p><strong>Signature et cachet</strong></p>
                        <p><strong>${signataire}</strong></p>
                        <p>Le Directeur Général</p>
                        <p>Direction Générale de la Dette Publique</p>
                    </div>
                </div>
                
                <script>
                    // Ajouter un timbre de sécurité
                    const timbre = document.createElement('div');
                    timbre.style.position = 'fixed';
                    timbre.style.top = '20px';
                    timbre.style.left = '20px';
                    timbre.style.background = 'linear-gradient(135deg, #FFD700 0%, #DAA520 25%, #B8860B 50%, #DAA520 75%, #FFD700 100%)';
                    timbre.style.color = '#8B4513';
                    timbre.style.padding = '10px 15px';
                    timbre.style.borderRadius = '5px';
                    timbre.style.fontFamily = 'Courier New, monospace';
                    timbre.style.fontSize = '10pt';
                    timbre.style.fontWeight = 'bold';
                    timbre.style.border = '2px solid #B8860B';
                    timbre.style.textAlign = 'center';
                    timbre.style.boxShadow = '2px 2px 5px rgba(0,0,0,0.2)';
                    timbre.style.textShadow = '1px 1px 2px rgba(255, 255, 255, 0.7)';
                    timbre.innerHTML = 'PRÉVISUALISATION<br>${new Date().toLocaleDateString('fr-FR')}';
                    document.body.appendChild(timbre);
                    
                    // Styles d'impression
                    const style = document.createElement('style');
                    style.textContent = \`
                        @media print {
                            .print-btn, .timbre {
                                display: none !important;
                            }
                            body {
                                margin: 0;
                                padding: 0;
                                background: white;
                            }
                            .document-container {
                                box-shadow: none;
                                border: none;
                                padding: 20px;
                            }
                        }
                    \`;
                    document.head.appendChild(style);
                <\/script>
            </body>
            </html>
        `);
        previewWindow.document.close();
    }
    
    // Gestion de la soumission du formulaire
    document.getElementById('documentForm').addEventListener('submit', function(e) {
        // Valider que le contenu n'est pas vide
        const titre = document.getElementById('titre').value;
        const contenu = document.getElementById('contenu').value;
        const signataire = document.getElementById('signataire').value;
        
        if (!titre.trim() || !contenu.trim() || !signataire.trim()) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires');
            return false;
        }
        
        // Vérifier que le contenu n'est pas juste du HTML vide
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = contenu;
        if (tempDiv.textContent.trim() === '') {
            e.preventDefault();
            alert('Le contenu du document ne peut pas être vide');
            return false;
        }
        
        return true;
    });
    </script>
</body>
</html>