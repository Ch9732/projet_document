
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Vérifier la session et le rôle admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Récupérer tous les utilisateurs
$pdo = Database::getConnection();
$utilisateurs = [];

try {
    $query = "SELECT * FROM utilisateurs ORDER BY date_creation DESC";
    $stmt = $pdo->query($query);
    $utilisateurs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des utilisateurs: ' . $e->getMessage();
}

// Traitement de la création d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nom_complet = trim($_POST['nom_complet']);
    $role = $_POST['role'];
    
    // Validation
    if (empty($email) || empty($password) || empty($confirm_password) || empty($nom_complet) || empty($role)) {
        $error = 'Tous les champs sont obligatoires';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } else {
        try {
            // Vérifier si l'email existe déjà
            $checkQuery = "SELECT id FROM utilisateurs WHERE email = :email";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([':email' => $email]);
            
            if ($checkStmt->fetch()) {
                $error = 'Cet email est déjà utilisé par un autre utilisateur';
            } else {
                // Hasher le mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer le nouvel utilisateur
                $insertQuery = "INSERT INTO utilisateurs (email, mot_de_passe, nom_complet, role) 
                               VALUES (:email, :mot_de_passe, :nom_complet, :role)";
                $insertStmt = $pdo->prepare($insertQuery);
                $insertStmt->execute([
                    ':email' => $email,
                    ':mot_de_passe' => $hashed_password,
                    ':nom_complet' => $nom_complet,
                    ':role' => $role
                ]);
                
                $success = 'Utilisateur créé avec succès!';
                
                // Rafraîchir la liste des utilisateurs
                $query = "SELECT * FROM utilisateurs ORDER BY date_creation DESC";
                $stmt = $pdo->query($query);
                $utilisateurs = $stmt->fetchAll();
                
                // Réinitialiser le formulaire
                $_POST = array();
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage();
        }
    }
}

// Traitement de la suppression d'un utilisateur
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Empêcher l'utilisateur de se supprimer lui-même
    if ($id == $_SESSION['user_id']) {
        $error = 'Vous ne pouvez pas supprimer votre propre compte';
    } else {
        try {
            $deleteQuery = "DELETE FROM utilisateurs WHERE id = :id";
            $deleteStmt = $pdo->prepare($deleteQuery);
            $deleteStmt->execute([':id' => $id]);
            
            $success = 'Utilisateur supprimé avec succès!';
            
            // Rafraîchir la liste des utilisateurs
            $query = "SELECT * FROM utilisateurs ORDER BY date_creation DESC";
            $stmt = $pdo->query($query);
            $utilisateurs = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styles spécifiques à l'administration */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .admin-header h1 {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        .admin-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .admin-card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .password-strength.weak {
            color: #e74c3c;
        }
        
        .password-strength.medium {
            color: #f39c12;
        }
        
        .password-strength.strong {
            color: #27ae60;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .users-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .users-table .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .users-table .role-admin {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .users-table .role-utilisateur {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .users-table .actions {
            display: flex;
            gap: 8px;
        }
        
        .users-table .btn-icon {
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .users-table .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .users-table .btn-edit:hover {
            background: #2980b9;
        }
        
        .users-table .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .users-table .btn-delete:hover {
            background: #c0392b;
        }
        
        .users-table .btn-reset {
            background: #f39c12;
            color: white;
        }
        
        .users-table .btn-reset:hover {
            background: #d68910;
        }
        
        .users-table .current-user {
            background: #fffde7;
        }
        
        .no-users {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-card .stat-label {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .modal {
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
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .close-modal:hover {
            color: #e74c3c;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        
        .password-input-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 35px;
            background: none;
            border: none;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .toggle-password:hover {
            color: #3498db;
        }
        
        @media print {
            .admin-header, .admin-actions, .form-actions, .users-table .actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</h1>
            <div class="admin-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <button onclick="window.print()" class="btn btn-info">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>
        </div>
        
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
        
        <!-- Cartes de statistiques -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3>Total Utilisateurs</h3>
                <div class="stat-number"><?php echo count($utilisateurs); ?></div>
                <div class="stat-label">Utilisateurs inscrits</div>
            </div>
            
            <div class="stat-card">
                <h3>Administrateurs</h3>
                <div class="stat-number">
                    <?php 
                    $admin_count = 0;
                    foreach ($utilisateurs as $user) {
                        if ($user['role'] === 'admin') $admin_count++;
                    }
                    echo $admin_count;
                    ?>
                </div>
                <div class="stat-label">Utilisateurs administrateurs</div>
            </div>
            
            <div class="stat-card">
                <h3>Utilisateurs Standards</h3>
                <div class="stat-number">
                    <?php echo count($utilisateurs) - $admin_count; ?>
                </div>
                <div class="stat-label">Utilisateurs standards</div>
            </div>
        </div>
        
        <!-- Formulaire de création d'utilisateur -->
        <div class="admin-card">
            <h2><i class="fas fa-user-plus"></i> Créer un Nouvel Utilisateur</h2>
            <form method="POST" action="" id="createUserForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom_complet"><i class="fas fa-user"></i> Nom Complet *</label>
                        <input type="text" id="nom_complet" name="nom_complet" required 
                               placeholder="Prénom Nom" value="<?php echo isset($_POST['nom_complet']) ? htmlspecialchars($_POST['nom_complet']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="utilisateur@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password"><i class="fas fa-key"></i> Mot de passe *</label>
                        <div class="password-input-container">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Mot de passe (min. 6 caractères)" 
                                   onkeyup="checkPasswordStrength()">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-key"></i> Confirmer le mot de passe *</label>
                        <div class="password-input-container">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Ressaisir le mot de passe">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="role"><i class="fas fa-user-tag"></i> Rôle *</label>
                        <select id="role" name="role" required>
                            <option value="">Sélectionner un rôle</option>
                            <option value="utilisateur" <?php echo (isset($_POST['role']) && $_POST['role'] == 'utilisateur') ? 'selected' : ''; ?>>Utilisateur</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" name="create_user" class="btn btn-primary">
                            <i class="fas fa-save"></i> Créer l'Utilisateur
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Liste des utilisateurs -->
        <div class="admin-card">
            <h2><i class="fas fa-users"></i> Liste des Utilisateurs (<?php echo count($utilisateurs); ?>)</h2>
            
            <?php if (empty($utilisateurs)): ?>
                <div class="no-users">
                    <i class="fas fa-user-slash fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
                    <p>Aucun utilisateur trouvé</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom Complet</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date de Création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $user): ?>
                                <tr class="<?php echo ($user['id'] == $_SESSION['user_id']) ? 'current-user' : ''; ?>">
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['nom_complet']); ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span style="color: #3498db; font-size: 12px; margin-left: 5px;">
                                                <i class="fas fa-user-circle"></i> Vous
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo $user['role']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['date_creation'])); ?></td>
                                    <td class="actions">
                                        <button type="button" class="btn-icon btn-edit" 
                                                onclick="editUser(<?php echo $user['id']; ?>)"
                                                title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn-icon btn-reset" 
                                                onclick="showResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nom_complet']); ?>')"
                                                title="Réinitialiser le mot de passe">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn-icon btn-delete" 
                                                    onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nom_complet']); ?>')"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de réinitialisation de mot de passe -->
    <div class="modal" id="resetPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Réinitialiser le Mot de Passe</h3>
                <button class="close-modal" onclick="closeResetPasswordModal()">&times;</button>
            </div>
            <form id="resetPasswordForm" method="POST" action="">
                <input type="hidden" id="reset_user_id" name="reset_user_id">
                
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe *</label>
                    <div class="password-input-container">
                        <input type="password" id="new_password" name="new_password" required 
                               placeholder="Nouveau mot de passe (min. 6 caractères)">
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_new_password">Confirmer le nouveau mot de passe *</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required 
                               placeholder="Confirmer le nouveau mot de passe">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_new_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeResetPasswordModal()">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitResetPassword()">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Fonction pour vérifier la force du mot de passe
    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthText = document.getElementById('passwordStrength');
        
        if (!password) {
            strengthText.textContent = '';
            strengthText.className = 'password-strength';
            return;
        }
        
        let strength = 0;
        
        // Vérifier la longueur
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        
        // Vérifier la complexité
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        // Afficher le résultat
        let message = '';
        let className = '';
        
        if (strength <= 2) {
            message = 'Faible';
            className = 'weak';
        } else if (strength <= 4) {
            message = 'Moyen';
            className = 'medium';
        } else {
            message = 'Fort';
            className = 'strong';
        }
        
        strengthText.textContent = `Force du mot de passe: ${message}`;
        strengthText.className = `password-strength ${className}`;
    }
    
    // Fonction pour basculer la visibilité du mot de passe
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const button = input.parentNode.querySelector('.toggle-password i');
        
        if (input.type === 'password') {
            input.type = 'text';
            button.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            button.className = 'fas fa-eye';
        }
    }
    
    // Fonction pour confirmer la suppression
    function confirmDelete(userId, userName) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${userName}" ? Cette action est irréversible.`)) {
            window.location.href = `admin_utilisateurs.php?delete=${userId}`;
        }
    }
    
    // Fonction pour éditer un utilisateur
    function editUser(userId) {
        // Pour l'instant, on redirige vers une page d'édition
        alert('Fonctionnalité d\'édition à implémenter. ID utilisateur: ' + userId);
        // window.location.href = `edit_user.php?id=${userId}`;
    }
    
    // Fonction pour afficher le modal de réinitialisation de mot de passe
    function showResetPasswordModal(userId, userName) {
        document.getElementById('reset_user_id').value = userId;
        document.querySelector('#resetPasswordModal h3').innerHTML = 
            `<i class="fas fa-key"></i> Réinitialiser le mot de passe pour "${userName}"`;
        
        // Réinitialiser le formulaire
        document.getElementById('new_password').value = '';
        document.getElementById('confirm_new_password').value = '';
        
        // Afficher le modal
        document.getElementById('resetPasswordModal').style.display = 'flex';
    }
    
    // Fonction pour fermer le modal
    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').style.display = 'none';
    }
    
    // Fonction pour soumettre la réinitialisation de mot de passe
    function submitResetPassword() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_new_password').value;
        const userId = document.getElementById('reset_user_id').value;
        
        if (!newPassword || !confirmPassword) {
            alert('Veuillez remplir tous les champs');
            return;
        }
        
        if (newPassword.length < 6) {
            alert('Le mot de passe doit contenir au moins 6 caractères');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            alert('Les mots de passe ne correspondent pas');
            return;
        }
        
        // Envoyer la requête AJAX pour réinitialiser le mot de passe
        const formData = new FormData();
        formData.append('reset_user_id', userId);
        formData.append('new_password', newPassword);
        formData.append('confirm_new_password', confirmPassword);
        formData.append('reset_password', '1');
        
        fetch('admin_utilisateurs.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Recharger la page pour voir les changements
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la réinitialisation du mot de passe');
        });
    }
    
    // Validation du formulaire de création d'utilisateur
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas');
            document.getElementById('confirm_password').focus();
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 6 caractères');
            document.getElementById('password').focus();
            return false;
        }
        
        return true;
    });
    
    // Fermer le modal en cliquant à l'extérieur
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('resetPasswordModal');
        if (event.target === modal) {
            closeResetPasswordModal();
        }
    });
    
    // Fermer le modal avec la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeResetPasswordModal();
        }
    });
    
    // Initialiser la vérification de force du mot de passe
    document.addEventListener('DOMContentLoaded', function() {
        checkPasswordStrength();
    });
    </script>
</body>
</html>
