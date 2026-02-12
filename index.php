
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialisation des variables de recherche
$search_reference = '';
$search_titre = '';
$search_date = '';
$documents = [];

// Récupérer la connexion PDO
$pdo = Database::getConnection();

// Vérifier si une recherche a été effectuée
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Construire la requête de base
    $query = "SELECT * FROM documents WHERE statut = 'actif'";
    $conditions = [];
    $params = [];
    
    // Recherche par référence
    if (!empty($_GET['search_reference'])) {
        $search_reference = trim($_GET['search_reference']);
        $conditions[] = "reference LIKE :reference";
        $params[':reference'] = '%' . $search_reference . '%';
    }
    
    // Recherche par titre
    if (!empty($_GET['search_titre'])) {
        $search_titre = trim($_GET['search_titre']);
        $conditions[] = "titre LIKE :titre";
        $params[':titre'] = '%' . $search_titre . '%';
    }
    
    // Recherche par date
    if (!empty($_GET['search_date'])) {
        $search_date = $_GET['search_date'];
        // Convertir la date au format MySQL
        $mysql_date = date('Y-m-d', strtotime($search_date));
        $conditions[] = "DATE(date_document) = :date_doc";
        $params[':date_doc'] = $mysql_date;
    }
    
    // Ajouter les conditions si elles existent
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    
    // Ajouter l'ordre
    $query .= " ORDER BY date_creation DESC";
    
    // Exécuter la requête
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des documents: " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la recherche.";
    }
} else {
    // Si pas de recherche, récupérer tous les documents
    $query = "SELECT * FROM documents WHERE statut = 'actif' ORDER BY date_creation DESC";
    $stmt = $pdo->query($query);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Tableau de bord</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-container h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .search-form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .search-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .search-form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .search-form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .search-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-results-info {
            margin-top: 15px;
            padding: 10px;
            background: #e8f4fc;
            border-radius: 5px;
            border-left: 4px solid #3498db;
            font-size: 14px;
        }
        
        .search-results-info i {
            color: #3498db;
            margin-right: 8px;
        }
        
        .critere {
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            margin: 0 5px;
            font-size: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #adb5bd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #6c757d;
            max-width: 500px;
            margin: 0 auto 20px;
        }
        
        .documents-count {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-file-contract"></i> <?php echo APP_NAME; ?></h1>
            <nav>
                <a href="index.php" class="active"><i class="fas fa-home"></i> Accueil</a>
                <a href="creer_document.php"><i class="fas fa-plus-circle"></i> Nouveau Document</a>
                <a href="creer_user.php"><i class="fas fa-plus-circle"></i> Nouveau utilisateur</a>
                <a href="modifier_document.php"><i class="fas fa-plus-circle"></i> Modifier Document</a>
                <a href="verifier.php"><i class="fas fa-plus-circle"></i> Verifier</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </header>
        
        <main>
            <div class="dashboard-header">
                <h2>Documents du Cabinet</h2>
                <a href="creer_document.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Créer un Document
                </a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de recherche -->
            <div class="search-container">
                <h3><i class="fas fa-search"></i> Rechercher un document</h3>
                <form method="GET" action="index.php" class="search-form">
                    <div class="search-form-group">
                        <label for="search_reference"><i class="fas fa-barcode"></i> Référence</label>
                        <input type="text" id="search_reference" name="search_reference" 
                               value="<?php echo htmlspecialchars($search_reference); ?>"
                               placeholder="Ex: DOC-20240315-ABC123">
                    </div>
                    
                    <div class="search-form-group">
                        <label for="search_titre"><i class="fas fa-heading"></i> Titre</label>
                        <input type="text" id="search_titre" name="search_titre" 
                               value="<?php echo htmlspecialchars($search_titre); ?>"
                               placeholder="Mot-clé dans le titre">
                    </div>
                    
                    <div class="search-form-group">
                        <label for="search_date"><i class="fas fa-calendar"></i> Date</label>
                        <input type="date" id="search_date" name="search_date" 
                               value="<?php echo htmlspecialchars($search_date); ?>">
                    </div>
                    
                    <div class="search-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        
                        <?php if (!empty($search_reference) || !empty($search_titre) || !empty($search_date)): ?>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Effacer
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Affichage des critères de recherche -->
                <?php if (!empty($search_reference) || !empty($search_titre) || !empty($search_date)): ?>
                    <div class="search-results-info">
                        <i class="fas fa-info-circle"></i> 
                        Recherche effectuée : 
                        <?php 
                        $critères = [];
                        if (!empty($search_reference)) {
                            $critères[] = "<span class='critere'>Référence : " . htmlspecialchars($search_reference) . "</span>";
                        }
                        if (!empty($search_titre)) {
                            $critères[] = "<span class='critere'>Titre : " . htmlspecialchars($search_titre) . "</span>";
                        }
                        if (!empty($search_date)) {
                            $critères[] = "<span class='critere'>Date : " . date('d/m/Y', strtotime($search_date)) . "</span>";
                        }
                        echo implode(' ', $critères);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="documents-grid">
                <?php if (empty($documents)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>
                            <?php if (!empty($search_reference) || !empty($search_titre) || !empty($search_date)): ?>
                                Aucun document trouvé
                            <?php else: ?>
                                Aucun document créé
                            <?php endif; ?>
                        </h3>
                        <p>
                            <?php if (!empty($search_reference) || !empty($search_titre) || !empty($search_date)): ?>
                                Aucun document ne correspond à vos critères de recherche. Essayez d'autres termes.
                            <?php else: ?>
                                Commencez par créer votre premier document
                            <?php endif; ?>
                        </p>
                        
                        <?php if (!empty($search_reference) || !empty($search_titre) || !empty($search_date)): ?>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-list"></i> Voir tous les documents
                            </a>
                        <?php else: ?>
                            <a href="creer_document.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Créer un document
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Résumé des résultats -->
                    <div style="margin-bottom: 15px;">
                        <span class="documents-count">
                            <i class="fas fa-file-alt"></i> 
                            <?php echo count($documents); ?> document<?php echo count($documents) > 1 ? 's' : ''; ?>
                        </span>
                        
                        <?php if (!empty($search_reference) || !empty($search_titre) || !empty($search_date)): ?>
                            <span style="margin-left: 10px; color: #666;">
                                correspondant à votre recherche
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php foreach ($documents as $document): ?>
                        <div class="document-card">
                            <div class="document-header">
                                <h3><?php echo htmlspecialchars($document['titre']); ?></h3>
                                <span class="document-ref">Réf: <?php echo $document['reference']; ?></span>
                            </div>
                            <div class="document-content">
                                <p><strong>Cabinet:</strong> <?php echo htmlspecialchars($document['cabinet']); ?></p>
                                <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($document['date_document'])); ?></p>
                                <p><strong>Signataire:</strong> <?php echo htmlspecialchars($document['signataire']); ?></p>
                                <p class="document-excerpt">
                                    <?php 
                                    $contenu_brut = strip_tags($document['contenu']);
                                    echo mb_substr($contenu_brut, 0, 150, 'UTF-8');
                                    if (mb_strlen($contenu_brut, 'UTF-8') > 150) {
                                        echo '...';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="document-actions">
                                <a href="voir_document.php?id=<?php echo $document['id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                                <a href="verifier.php?ref=<?php echo urlencode($document['reference']); ?>&hash=<?php echo urlencode($document['hash_verification']); ?>" class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="fas fa-qrcode"></i> Vérifier
                                </a>
                                <span class="document-date" style="font-size: 12px; color: #666; margin-left: auto;">
                                    <i class="far fa-clock"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($document['date_creation'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - République Démocratique du Congo</p>
        </footer>
    </div>
    
    <script src="js/script.js"></script>
    <script>
    // Raccourci clavier pour se concentrer sur le champ de recherche (Ctrl+F)
    document.addEventListener('keydown', function(event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 'f') {
            event.preventDefault();
            document.getElementById('search_reference').focus();
        }
        
        // Effacer la recherche avec Echap
        if (event.key === 'Escape') {
            const searchParams = new URLSearchParams(window.location.search);
            if (searchParams.has('search_reference') || searchParams.has('search_titre') || searchParams.has('search_date')) {
                window.location.href = 'index.php';
            }
        }
    });
    
    // Aide à la recherche
    document.addEventListener('DOMContentLoaded', function() {
        const searchReference = document.getElementById('search_reference');
        const searchTitre = document.getElementById('search_titre');
        const searchDate = document.getElementById('search_date');
        
        // Ajouter des tooltips
        if (searchReference) {
            searchReference.title = "Recherche par référence (partielle ou complète)";
        }
        if (searchTitre) {
            searchTitre.title = "Recherche par mot-clé dans le titre";
        }
        if (searchDate) {
            searchDate.title = "Recherche par date exacte du document";
        }
        
        // Exemple de format pour la référence
        if (!searchReference.value) {
            searchReference.placeholder = "Ex: DOC-20240315-ABC123 ou 20240315 ou ABC123";
        }
    });
    </script>
</body>
</html>
