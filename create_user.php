<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Données du nouvel utilisateur
$email = 'admin@example.com';
$password = 'Admin123!'; // Mot de passe en clair
$nom_complet = 'Administrateur';
$role = 'admin';

// Hacher le mot de passe
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo = Database::getConnection();
    $query = "INSERT INTO utilisateurs (email, mot_de_passe, nom_complet, role) 
              VALUES (:email, :mot_de_passe, :nom_complet, :role)";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':email' => $email,
        ':mot_de_passe' => $password_hash,
        ':nom_complet' => $nom_complet,
        ':role' => $role
    ]);
    
    echo "Utilisateur créé avec succès !<br>";
    echo "Email: $email<br>";
    echo "Mot de passe en clair: $password<br>";
    echo "Nom: $nom_complet<br>";
    echo "Rôle: $role<br><br>";
    echo "<a href='login.php'>Aller à la page de connexion</a>";
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>