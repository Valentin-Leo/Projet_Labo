<?php
header("Content-Type: application/json");
require "config.php";

// Vérifier la requête
$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {
    case "GET":
        if (isset($_GET["action"])) {
            if ($_GET["action"] == "personnes") {
                // Récupérer toutes les personnes avec leur badge
                $stmt = $pdo->query("SELECT p.id, p.nom, p.prenom, p.role, b.IUD 
                                     FROM personnes p 
                                     LEFT JOIN Badges_has_personnes bhp ON p.id = bhp.personnes_id 
                                     LEFT JOIN Badges b ON bhp.Badges_idBadge = b.idBadge");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

            } elseif ($_GET["action"] == "badges") {
                // Récupérer tous les badges
                $stmt = $pdo->query("SELECT * FROM Badges");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

            } elseif ($_GET["action"] == "historique") {
                // Récupérer l'historique des accès
                $stmt = $pdo->query("SELECT h.dateAction, p.nom, p.prenom, b.IUD
                                     FROM historique h
                                     JOIN Badges b ON h.Badges_idBadge = b.idBadge
                                     JOIN Badges_has_personnes bhp ON bhp.Badges_idBadge = b.idBadge
                                     JOIN personnes p ON p.id = bhp.personnes_id");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            
        } elseif ($_GET["action"] == "acces_enseignants") {
            // Récupérer uniquement les accès des enseignants
            $stmt = $pdo->query("
                SELECT h.dateAction, p.nom, p.prenom, b.IUD
                FROM historique h
                JOIN Badges b ON h.Badges_idBadge = b.idBadge
                JOIN Badges_has_personnes bhp ON bhp.Badges_idBadge = b.idBadge
                JOIN personnes p ON p.id = bhp.personnes_id
                WHERE p.role = 'enseignant'
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } elseif ($_GET["action"] == "acces_eleves") {
        // Récupérer uniquement les accès des eleves
        $stmt = $pdo->query("
            SELECT h.dateAction, p.nom, p.prenom, b.IUD
            FROM historique h
            JOIN Badges b ON h.Badges_idBadge = b.idBadge
            JOIN Badges_has_personnes bhp ON bhp.Badges_idBadge = b.idBadge
            JOIN personnes p ON p.id = bhp.personnes_id
            WHERE p.role = 'eleve'
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
        } elseif ($_GET["action"] == "acces_eleves") {
            // Récupérer via le nom de la personne
            $stmt = $pdo->query("
                SELECT h.dateAction, p.nom, p.prenom, b.IUD
                FROM historique h
                JOIN Badges b ON h.Badges_idBadge = b.idBadge
                JOIN Badges_has_personnes bhp ON bhp.Badges_idBadge = b.idBadge
                JOIN personnes p ON p.id = bhp.personnes_id
                WHERE p.nom = ''
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
         
            } 
        break;

        case "POST":
            $data = json_decode(file_get_contents("php://input"), true);
        
            if (isset($data["action"])) {
                if ($data["action"] == "ajouter_personne") {
                    // Vérifier que tous les champs requis sont présents
                    if (!isset($data["nom"], $data["prenom"], $data["role"], $data["IUD"])) {
                        echo json_encode(["error" => "Champs manquants"]);
                        exit;
                    }
        
                    // Vérifier si l'IUD existe déjà dans la table Badges
                    $stmt = $pdo->prepare("SELECT idBadge FROM Badges WHERE IUD = ?");
                    $stmt->execute([$data["IUD"]]);
                    $badge = $stmt->fetch(PDO::FETCH_ASSOC);
        
                    if (!$badge) {
                        // Ajouter un nouveau badge s'il n'existe pas
                        $stmt = $pdo->prepare("INSERT INTO Badges (IUD) VALUES (?)");
                        $stmt->execute([$data["IUD"]]);
                        $badge_id = $pdo->lastInsertId();
                    } else {
                        $badge_id = $badge["idBadge"];
                    }
        
                    // Ajouter la personne
                    $stmt = $pdo->prepare("INSERT INTO personnes (nom, prenom, role) VALUES (?, ?, ?)");
                    $stmt->execute([$data["nom"], $data["prenom"], $data["role"]]);
                    $personne_id = $pdo->lastInsertId();
        
                    // Associer le badge à la personne
                    $stmt = $pdo->prepare("INSERT INTO Badges_has_personnes (Badges_idBadge, personnes_id, affectation) VALUES (?, ?, NOW())");
                    $stmt->execute([$badge_id, $personne_id]);
        
                    echo json_encode(["message" => "Personne et badge ajoutés avec succès"]);
        
                } elseif ($data["action"] == "enregistrer_acces") {
                    // Vérifier si le badge existe
                    $stmt = $pdo->prepare("SELECT * FROM Badges WHERE IUD = ?");
                    $stmt->execute([$data["IUD"]]);
                    $badge = $stmt->fetch(PDO::FETCH_ASSOC);
        
                    if ($badge) {
                        // Enregistrer un accès dans l'historique
                        $stmt = $pdo->prepare("INSERT INTO historique (dateAction, Badges_idBadge) VALUES (NOW(), ?)");
                        $stmt->execute([$badge["idBadge"]]);
                        echo json_encode(["message" => "Accès enregistré"]);
                    } else {
                        echo json_encode(["error" => "Badge non reconnu"]);
                    }
                } elseif ($data["action"] == "supprimer_personne") {
                    // Vérifier si l'ID de la personne est fourni
                    if (!isset($data["id"])) {
                        echo json_encode(["error" => "ID de la personne manquant"]);
                        exit;
                    }
        
                    $id_personne = $data["id"];
        
                    // Vérifier si la personne existe
                    $stmt = $pdo->prepare("SELECT * FROM personnes WHERE id = ?");
                    $stmt->execute([$id_personne]);
                    $personne = $stmt->fetch(PDO::FETCH_ASSOC);
        
                    if (!$personne) {
                        echo json_encode(["error" => "Personne non trouvée"]);
                        exit;
                    }
        
                    // Récupérer l'ID du badge associé
                    $stmt = $pdo->prepare("SELECT Badges_idBadge FROM Badges_has_personnes WHERE personnes_id = ?");
                    $stmt->execute([$id_personne]);
                    $badge = $stmt->fetch(PDO::FETCH_ASSOC);
        
                    if ($badge) {
                        $badge_id = $badge["Badges_idBadge"];
        
                        // Supprimer l'historique des accès liés à ce badge
                        $stmt = $pdo->prepare("DELETE FROM historique WHERE Badges_idBadge = ?");
                        $stmt->execute([$badge_id]);
        
                        // Supprimer l'association entre la personne et son badge
                        $stmt = $pdo->prepare("DELETE FROM Badges_has_personnes WHERE personnes_id = ?");
                        $stmt->execute([$id_personne]);
        
                        // Supprimer le badge lui-même
                        $stmt = $pdo->prepare("DELETE FROM Badges WHERE idBadge = ?");
                        $stmt->execute([$badge_id]);
                    }
        
                    // Supprimer la personne
                    $stmt = $pdo->prepare("DELETE FROM personnes WHERE id = ?");
                    $stmt->execute([$id_personne]);
        
                    echo json_encode(["message" => "Personne, badge et historique supprimés avec succès"]);
                }
            }
            break;
        

    case "PUT":
        // Modification d'une personne (nom, prénom, rôle, badge)
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data["action"]) && $data["action"] == "modifier_personne") {
            if (!isset($data["id"], $data["nom"], $data["prenom"], $data["role"], $data["IUD"])) {
                echo json_encode(["error" => "Champs manquants"]);
                exit;
            }

            $id_personne = $data["id"];
            $nouveau_IUD = $data["IUD"];

            // Mise à jour des informations de la personne
            $stmt = $pdo->prepare("UPDATE personnes SET nom = ?, prenom = ?, role = ? WHERE id = ?");
            $stmt->execute([$data["nom"], $data["prenom"], $data["role"], $id_personne]);

            // Vérifier si le nouveau badge existe déjà
            $stmt = $pdo->prepare("SELECT idBadge FROM Badges WHERE IUD = ?");
            $stmt->execute([$nouveau_IUD]);
            $badge = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$badge) {
                // Créer un nouveau badge s'il n'existe pas
                $stmt = $pdo->prepare("INSERT INTO Badges (IUD) VALUES (?)");
                $stmt->execute([$nouveau_IUD]);
                $badge_id = $pdo->lastInsertId();
            } else {
                $badge_id = $badge["idBadge"];
            }

            // Mise à jour du badge associé dans Badges_has_personnes
            $stmt = $pdo->prepare("UPDATE Badges_has_personnes SET Badges_idBadge = ?, affectation = NOW() WHERE personnes_id = ?");
            $stmt->execute([$badge_id, $id_personne]);

            echo json_encode(["message" => "Profil mis à jour avec succès"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Méthode non autorisée"]);
}
?>
