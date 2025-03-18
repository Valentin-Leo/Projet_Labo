<?php
header("Content-Type: application/json");
require "config.php"; // Connexion à la BDD

$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {
    case "POST":
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data["action"])) {
            echo json_encode(["error" => "Action requise"]);
            exit;
        }

        if ($data["action"] == "inscription") {
            // Vérification des champs
            if (!isset($data["email"], $data["mot_de_passe"])) {
                echo json_encode(["error" => "Champs manquants"]);
                exit;
            }

            $email = filter_var($data["email"], FILTER_SANITIZE_EMAIL);
            $mot_de_passe = $data["mot_de_passe"];

            // Vérifier la validité du mot de passe
            if (!preg_match("/^(?=.*[A-Z])(?=.*\W).{12,}$/", $mot_de_passe)) {
                echo json_encode(["error" => "Le mot de passe doit contenir au moins 12 caractères, une majuscule et un caractère spécial."]);
                exit;
            }

            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT * FROM Login WHERE `adresse mail` = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(["error" => "Cet email est déjà utilisé"]);
                exit;
            }

            // Hachage du mot de passe avec bcrypt
            $hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);

            // Insertion dans la base de données
            $stmt = $pdo->prepare("INSERT INTO Login (`adresse mail`, `mot de passe`) VALUES (?, ?)");
            if ($stmt->execute([$email, $hash])) {
                echo json_encode(["message" => "Compte créé avec succès"]);
            } else {
                echo json_encode(["error" => "Erreur lors de la création du compte"]);
            }
        }

        elseif ($data["action"] == "changer_mdp") {
            // Vérification des champs
            if (!isset($data["email"], $data["ancien_mdp"], $data["nouveau_mdp"])) {
                echo json_encode(["error" => "Champs manquants"]);
                exit;
            }

            $email = filter_var($data["email"], FILTER_SANITIZE_EMAIL);
            $ancien_mdp = $data["ancien_mdp"];
            $nouveau_mdp = $data["nouveau_mdp"];

            // Vérifier la validité du nouveau mot de passe
            if (!preg_match("/^(?=.*[A-Z])(?=.*\W).{12,}$/", $nouveau_mdp)) {
                echo json_encode(["error" => "Le nouveau mot de passe doit contenir au moins 12 caractères, une majuscule et un caractère spécial."]);
                exit;
            }

            // Vérifier si l'utilisateur existe
            $stmt = $pdo->prepare("SELECT `mot de passe` FROM Login WHERE `adresse mail` = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(["error" => "Utilisateur non trouvé"]);
                exit;
            }

            // Vérifier si l'ancien mot de passe est correct
            if (!password_verify($ancien_mdp, $user["mot de passe"])) {
                echo json_encode(["error" => "Ancien mot de passe incorrect"]);
                exit;
            }

            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if (password_verify($nouveau_mdp, $user["mot de passe"])) {
                echo json_encode(["error" => "Le nouveau mot de passe doit être différent de l'ancien"]);
                exit;
            }

            // Hachage du nouveau mot de passe
            $hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT);

            // Mise à jour du mot de passe
            $stmt = $pdo->prepare("UPDATE Login SET `mot de passe` = ? WHERE `adresse mail` = ?");
            if ($stmt->execute([$hash, $email])) {
                echo json_encode(["message" => "Mot de passe mis à jour avec succès"]);
            } else {
                echo json_encode(["error" => "Erreur lors de la mise à jour du mot de passe"]);
            }
        }

        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Méthode non autorisée"]);
}
?>
