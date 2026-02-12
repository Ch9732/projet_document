[file name]: modifier_document.php
[file content begin]
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

// Récupérer le document existant
$query = "SELECT * FROM documents WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute([':id' => $id]);
$document = $stmt->fetch();

if (!$document) {
    header('Location: index.php');
    exit;
}

// Variables pour le formulaire
$errors = [];
$success = false;

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $titre = trim($_POST['titre'] ?? '');
    $date_document = trim($_POST['date_document'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $signataire = trim($_POST['signataire'] ?? '');
    
    // Validation
    if (empty($titre)) {
        $errors['titre'] = "Le titre est obligatoire";
    } elseif (strlen($titre) > 200) {
        $errors['titre'] = "Le titre ne doit pas dépasser 200 caractères";
    }
    
    if (empty($date_document)) {
        $errors['date_document'] = "La date du document est obligatoire";
    } else {
        // Vérifier que la date est valide
        $date_obj = DateTime::createFromFormat('Y-m-d', $date_document);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $date_document) {
            $errors['date_document'] = "Date invalide";
        }
    }
    
    if (empty($contenu)) {
        $errors['contenu'] = "Le contenu est obligatoire";
    }
    
    if (empty($signataire)) {
        $errors['signataire'] = "Le signataire est obligatoire";
    } elseif (strlen($signataire) > 100) {
        $errors['signataire'] = "Le nom du signataire ne doit pas dépasser 100 caractères";
    }
    
    // Si aucune erreur, mettre à jour le document
    if (empty($errors)) {
        try {
            // Mettre à jour la base de données
            $update_query = "UPDATE documents SET 
                            titre = :titre,
                            date_document = :date_document,
                            contenu = :contenu,
                            signataire = :signataire,
                            date_modification = NOW()
                            WHERE id = :id";
            
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                ':titre' => $titre,
                ':date_document' => $date_document,
                ':contenu' => $contenu,
                ':signataire' => $signataire,
                ':id' => $id
            ]);
            
            // Rafraîchir les données du document
            $stmt->execute([':id' => $id]);
            $document = $stmt->fetch();
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors['database'] = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Document <?php echo $document['reference']; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Éditeur de texte enrichi (optionnel) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* Styles spécifiques pour la page de modification */
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .document-info-header {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
        }
        
        .document-info-header h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .document-info-header .badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
            font-size: 15px;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: #3498db;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        /* Éditeur de texte enrichi */
        #editor-container {
            height: 400px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .ql-toolbar {
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            border-bottom: 1px solid #ddd;
        }
        
        .ql-container {
            border-bottom-left-radius: 6px;
            border-bottom-right-radius: 6px;
            font-size: 16px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .ql-editor {
            min-height: 350px;
        }
        
        /* Prévisualisation en temps réel */
        .preview-toggle {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }
        
        .preview-btn {
            padding: 8px 15px;
            background: #e9ecef;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: #495057;
        }
        
        .preview-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .preview-container {
            display: none;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            margin-top: 10px;
            background: white;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .preview-container.active {
            display: block;
        }
        
        .preview-container .document-body {
            font-family: 'Times New Roman', serif;
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* Section d'historique */
        .history-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .history-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-date {
            color: #666;
            font-size: 13px;
        }
        
        /* Modal de confirmation */
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: #e74c3c;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-edit"></i> Modifier le Document</h1>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="voir_document.php?id=<?php echo $id; ?>"><i class="fas fa-eye"></i> Visualiser</a>
                <a href="creer_document.php"><i class="fas fa-plus-circle"></i> Nouveau</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </header>
        
        <main>
            <div class="form-container">
                <!-- En-tête d'information du document -->
                <div class="document-info-header">
                    <h3><i class="fas fa-file-alt"></i> Modification du document</h3>
                    <div>
                        <span class="badge">Référence : <?php echo htmlspecialchars($document['reference']); ?></span>
                        <span class="badge">Créé le : <?php echo date('d/m/Y H:i', strtotime($document['date_creation'])); ?></span>
                        <span class="badge">Dernière modification : <?php echo date('d/m/Y H:i', strtotime($document['date_modification'])); ?></span>
                    </div>
                </div>
                
                <!-- Messages de succès ou d'erreur -->
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        Document modifié avec succès !
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['database'])): ?>
                    <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $errors['database']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulaire de modification -->
                <form method="POST" action="" id="editDocumentForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="titre"><i class="fas fa-heading"></i> Titre du document *</label>
                            <input type="text" 
                                   id="titre" 
                                   name="titre" 
                                   value="<?php echo htmlspecialchars($document['titre']); ?>"
                                   placeholder="Ex: Lettre de mission"
                                   maxlength="200"
                                   required>
                            <?php if (isset($errors['titre'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo $errors['titre']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_document"><i class="fas fa-calendar"></i> Date du document *</label>
                            <input type="date" 
                                   id="date_document" 
                                   name="date_document" 
                                   value="<?php echo htmlspecialchars($document['date_document']); ?>"
                                   required>
                            <?php if (isset($errors['date_document'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo $errors['date_document']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="signataire"><i class="fas fa-signature"></i> Signataire *</label>
                        <input type="text" 
                               id="signataire" 
                               name="signataire" 
                               value="<?php echo htmlspecialchars($document['signataire']); ?>"
                               placeholder="Ex: M. Jean Dupont, Directeur Général"
                               maxlength="100"
                               required>
                        <?php if (isset($errors['signataire'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $errors['signataire']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <div class="preview-toggle">
                            <button type="button" class="preview-btn active" id="editTab">
                                <i class="fas fa-edit"></i> Éditer
                            </button>
                            <button type="button" class="preview-btn" id="previewTab">
                                <i class="fas fa-eye"></i> Prévisualiser
                            </button>
                        </div>
                        
                        <!-- Éditeur de texte enrichi -->
                        <div id="editor-container">
                            <!-- L'éditeur sera initialisé par JavaScript -->
                        </div>
                        
                        <!-- Champ textearea caché pour le contenu -->
                        <textarea id="contenu" name="contenu" style="display: none;" required>
                            <?php echo htmlspecialchars($document['contenu']); ?>
                        </textarea>
                        
                        <?php if (isset($errors['contenu'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $errors['contenu']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Zone de prévisualisation -->
                        <div class="preview-container" id="previewArea">
                            <div class="document-body">
                                <!-- La prévisualisation sera générée dynamiquement -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section informations de suivi -->
                    <div class="history-section">
                        <h4><i class="fas fa-history"></i> Historique du document</h4>
                        <div class="history-item">
                            <span>Date de création</span>
                            <span class="history-date"><?php echo date('d/m/Y H:i', strtotime($document['date_creation'])); ?></span>
                        </div>
                        <div class="history-item">
                            <span>Dernière modification</span>
                            <span class="history-date"><?php echo date('d/m/Y H:i', strtotime($document['date_modification'])); ?></span>
                        </div>
                        <?php if (!empty($document['qr_code'])): ?>
                        <div class="history-item">
                            <span>QR Code généré</span>
                            <span class="history-date"><i class="fas fa-qrcode"></i> Oui</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Actions du formulaire -->
                    <div class="form-actions">
                        <a href="voir_document.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        
                        <button type="button" class="btn btn-danger" onclick="showConfirmationModal()">
                            <i class="fas fa-trash-alt"></i> Supprimer
                        </button>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                        
                        <a href="voir_document.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Voir le document
                        </a>
                    </div>
                </form>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - République Démocratique du Congo</p>
        </footer>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <h2 style="margin: 0; color: #e74c3c;">Confirmation de suppression</h2>
            </div>
            <p>Êtes-vous sûr de vouloir supprimer ce document ?</p>
            <p><strong>Cette action est irréversible !</strong></p>
            <p>Document : <?php echo htmlspecialchars($document['reference']); ?> - <?php echo htmlspecialchars($document['titre']); ?></p>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="hideConfirmationModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <a href="supprimer_document.php?id=<?php echo $id; ?>" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Confirmer la suppression
                </a>
            </div>
        </div>
    </div>
    
    <!-- Éditeur de texte enrichi (Quill) -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    
    <script>
    // Initialiser l'éditeur de texte enrichi
    const quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['clean']
            ]
        },
        placeholder: 'Saisissez le contenu du document...',
    });
    
    // Charger le contenu existant dans l'éditeur
    quill.root.innerHTML = document.getElementById('contenu').value;
    
    // Gérer les onglets éditer/prévisualiser
    const editTab = document.getElementById('editTab');
    const previewTab = document.getElementById('previewTab');
    const editorContainer = document.getElementById('editor-container');
    const previewArea = document.getElementById('previewArea');
    const contenuField = document.getElementById('contenu');
    
    editTab.addEventListener('click', function() {
        editTab.classList.add('active');
        previewTab.classList.remove('active');
        editorContainer.style.display = 'block';
        previewArea.classList.remove('active');
    });
    
    previewTab.addEventListener('click', function() {
        previewTab.classList.add('active');
        editTab.classList.remove('active');
        editorContainer.style.display = 'none';
        previewArea.classList.add('active');
        
        // Générer la prévisualisation
        const content = quill.root.innerHTML;
        previewArea.querySelector('.document-body').innerHTML = content;
    });
    
    // Mettre à jour le champ textearea caché avant soumission du formulaire
    document.getElementById('editDocumentForm').addEventListener('submit', function(e) {
        // Mettre le contenu de l'éditeur dans le champ textearea
        contenuField.value = quill.root.innerHTML;
        
        // Validation supplémentaire
        const titre = document.getElementById('titre').value.trim();
        const date = document.getElementById('date_document').value;
        const signataire = document.getElementById('signataire').value.trim();
        const contenu = quill.getText().trim(); // Version texte seulement
        
        let errors = [];
        
        if (!titre) {
            errors.push("Le titre est obligatoire");
        }
        
        if (!date) {
            errors.push("La date du document est obligatoire");
        }
        
        if (!signataire) {
            errors.push("Le signataire est obligatoire");
        }
        
        if (contenu.length < 10) {
            errors.push("Le contenu est trop court");
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            alert("Veuillez corriger les erreurs suivantes :\n\n" + errors.join('\n'));
        }
    });
    
    // Gestion de la modal de confirmation
    function showConfirmationModal() {
        document.getElementById('confirmationModal').style.display = 'flex';
    }
    
    function hideConfirmationModal() {
        document.getElementById('confirmationModal').style.display = 'none';
    }
    
    // Fermer la modal en cliquant à l'extérieur
    document.getElementById('confirmationModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideConfirmationModal();
        }
    });
    
    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Ctrl+S pour sauvegarder
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            document.querySelector('button[type="submit"]').click();
        }
        
        // Échap pour annuler
        if (e.key === 'Escape') {
            window.location.href = 'voir_document.php?id=<?php echo $id; ?>';
        }
    });
    
    // Auto-sauvegarde (optionnelle)
    let autoSaveTimer;
    const autoSave = function() {
        const formData = {
            titre: document.getElementById('titre').value,
            date_document: document.getElementById('date_document').value,
            signataire: document.getElementById('signataire').value,
            contenu: quill.root.innerHTML,
            id: <?php echo $id; ?>
        };
        
        // Sauvegarde dans le localStorage
        localStorage.setItem('document_draft_<?php echo $id; ?>', JSON.stringify(formData));
        console.log('Brouillon sauvegardé localement');
    };
    
    // Déclencher l'auto-sauvegarde toutes les 30 secondes
    setInterval(autoSave, 30000);
    
    // Vérifier s'il y a un brouillon sauvegardé
    document.addEventListener('DOMContentLoaded', function() {
        const savedDraft = localStorage.getItem('document_draft_<?php echo $id; ?>');
        if (savedDraft && !<?php echo $success ? 'true' : 'false'; ?>) {
            if (confirm('Un brouillon non enregistré a été trouvé. Voulez-vous le charger ?')) {
                const draft = JSON.parse(savedDraft);
                document.getElementById('titre').value = draft.titre;
                document.getElementById('date_document').value = draft.date_document;
                document.getElementById('signataire').value = draft.signataire;
                quill.root.innerHTML = draft.contenu;
            }
        }
        
        // Effacer le brouillon après sauvegarde réussie
        <?php if ($success): ?>
            localStorage.removeItem('document_draft_<?php echo $id; ?>');
        <?php endif; ?>
    });
    
    // Formatage automatique de la date pour aujourd'hui par défaut
    document.addEventListener('DOMContentLoaded', function() {
        const dateField = document.getElementById('date_document');
        if (!dateField.value) {
            const today = new Date().toISOString().split('T')[0];
            dateField.value = today;
        }
    });
    </script>
    
    <script src="js/script.js"></script>
</body>
</html>
[file content end]