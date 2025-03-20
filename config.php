<?php
$host = "localhost"; // Adresse du serveur MySQL
$dbname = "bdd_labo"; // Nom de la base de données
$username = "root"; // Nom d'utilisateur
$password = ""; // Mot de passe

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
