<?php
require_once "vue_profil.php"; 
require_once 'modele_profil.php';
require_once '././fonction.php';

class ControleurProfil{
    private $vue;
    private $modele;


    public function __construct() {
        $this->vue = new VueProfil();
        $this->modele = new ModeleProfil(); 
    }
    

    private function traiterCreationTournoi() {
        // Vérification du token CSRF
        $token = $_POST['token'] ?? '';
        if (!Fonction::isTokenValid($token)) {
            die('Session expiré. Veuillez réessayer.');
        }

        $idCreateur = $_SESSION['user_id'];
        $nom = $_POST['nom'];
        $regle = $_POST['regle'];
        $dateDebut = $_POST['dateDebut'];
        $dateFin = $_POST['dateFin'];
        $capacite = $_POST['capacite'];
        $recompense = $_POST['recompense'];
        $this->modele->creationTournoi($idCreateur, $nom, $regle, $dateDebut, $dateFin, $capacite, $recompense);
        header("Location: index.php?module=profil&action=afficherProfil");
        exit();
}

private function traiterEnvoiArgent() {
    // Vérification du token CSRF
    $token = $_POST['token'] ?? '';
    if (!Fonction::isTokenValid($token)) {
        die('Session expiré. Veuillez réessayer.');
    }
    
    $destinataireID = $_POST['recipient'];
    $montant = $_POST['amount'];

    $envoiReussi = $this->modele->envoyerArgent($destinataireID, $montant);
    header("Location: index.php?module=profil&action=afficherProfil");
    exit();
}


public function exec() {

   
    
    // Vérifier d'abord si c'est une requête AJAX pour charger les tournois
    if (isset($_GET['action']) && $_GET['action'] == 'loadTournois') {
        $this->chargerListeTournois();
        exit;
    }
    if (isset($_GET['action']) && $_GET['action'] == 'loadMissions') {
        $this->chargerListeMissions();
        exit;
    }

    if (isset($_SESSION['user_id'])) {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['submitTournoi'])) {
                $this->traiterCreationTournoi(); // Traiter la création de tournoi
            } elseif (isset($_POST['submitJoinTournoi'])) {
                $this->rejoindreTournoi(); // Traiter la demande de rejoindre un tournoi
            } elseif (isset($_POST['submitQuitTournoi'])) {
                $this->quitterTournoi(); // Traiter la demande de quitter un tournoi
            } elseif (isset($_POST['submitTournoi'])) {
                $this->traiterCreationTournoi(); // Traiter la création de tournoi
            } elseif (isset($_POST['submitEnvoiArgent'])) {
                $this->traiterEnvoiArgent(); // Traiter l'envoi d'argent 
            }else if (isset($_POST['submitPrendreMission'])) {
                $this->prendreMission(); // Traiter la prise d'une mission
            }else if (isset($_POST['submitQuitterMission'])) {
                $this->quitterMission(); // Traiter la quitte d'une mission
            }elseif (isset($_POST['destinataire_message'])) {
                $this->form_envoyerMessage($_POST['destinataire_message'], $_POST['contenu']); // Traiter l'envoi de message
            }elseif (isset($_POST['action']) && $_POST['action'] == 'rechercherUtilisateur') {
                $this->rechercherUtilisateur();
            }
        }  

            $this->generateTokenDansVue();
            $tournois = $this->modele->getTournois(); // Récupérer les tournois
            $this->vue->afficherProfil($tournois); // Passer les tournois à la vue
            
        } else {
            header("Location: index.php?module=connexion&action=form_connexion"); 
            exit();
        }
    }

    private function generateTokenDansVue(){
        $token = Fonction::generateToken();
        Fonction::storeToken($token);
        $this->vue->afficherProfil($token);
    }

    private function form_envoyerMessage($destinataire, $contenu){
        
    
        // Vérification du token CSRF
        $token = $_POST['token'] ?? '';
        if (!Fonction::isTokenValid($token)) {
            die('Session expiré. Veuillez réessayer.');
        }

            
        if ($contenu != "" && $destinataire) {
            $idDestinataire = $this->modele->getIdUtilisateurParNom($destinataire);

            if($idDestinataire){
            $this->modele->envoyerMessageBd($idDestinataire, $contenu);

            }
            else {
                echo '"<script>alert("Utilisateur introuvable");</script>"';
            }
        } 
        
        else{
            echo "Message vide ou destinataire non spécifié.";
        }
        
        header("Location: index.php?module=profil&action=afficherProfil");
        exit();
    }

    private function rechercherUtilisateur(){
        
        $searchTerm = isset($_GET['user']) ? $_GET['user'] : '';
       

        $results = $this->modele->rechercherUtilisateur($searchTerm);
    
        if (!empty($results)) {
            $htmlResults = "<div>Résultats de recherche :</div><ul>";
            foreach ($results as $result) {
                $htmlResults .= "<li>" . $result['Nom'] . "</li>";
            }
            $htmlResults .= "</ul>";
            echo $htmlResults;
        } else {
            echo "Aucun résultat trouvé.";
        }
    }




private function rejoindreTournoi() {
    $userID = $_SESSION['user_id'];

    // Vérifier si 'tournoiID' est défini et non null
    $tournoiID = isset($_POST['tournoiID']) ? $_POST['tournoiID'] : null;

    if ($tournoiID === null) {
        $_SESSION['popup_message'] = "Aucun tournoi sélectionné.";
        return;
    }

    if (ModeleProfil::estDejaInscrit($userID)) {
        $_SESSION['popup_message'] = "Vous êtes déjà inscrit à un tournoi.";
    } elseif (ModeleProfil::rejoindreTournoi($userID, $tournoiID)) {
        $_SESSION['popup_message'] = "Inscription au tournoi réussie.";
    } else {
        $_SESSION['popup_message'] = "Erreur lors de l'inscription au tournoi.";
    }

}


private function quitterTournoi() {
    $userID = $_SESSION['user_id'];

    if (ModeleProfil::quitterTournoi($userID)) {
        $_SESSION['popup_message'] = "Vous avez quitté le tournoi.";
    } else {
        $_SESSION['popup_message'] = "Erreur lors de la tentative de quitter le tournoi.";
    }
}
public function chargerListeTournois() {
    $userID = $_SESSION['user_id'];
    $tournoiActuel = $this->modele->getTournoiActuel($userID);
    $tournois = $this->modele->getTournois();

    foreach ($tournois as $tournoi) {
        $class = ($tournoi['Id_Tournoi'] == $tournoiActuel) ? 'tournoi-actuel' : '';
        echo '<div class="tournoi-item ' . $class . '">';
        echo '<label for="tournoi_' . htmlspecialchars($tournoi['Id_Tournoi']) . '">';
        echo '<input type="radio" id="tournoi_' . htmlspecialchars($tournoi['Id_Tournoi']) . '" name="tournoiID" value="' . htmlspecialchars($tournoi['Id_Tournoi']) . '" style="display: none;">';
        echo '<h2 class="tournoi-nom">' . htmlspecialchars($tournoi['Nom']) . '</h2>';
        echo '<div class="tournoi-info"><span class="tournoi-label">Règles:</span><span class="tournoi-detail">' . htmlspecialchars($tournoi['Regle']) . '</span></div>';
        echo '<div class="tournoi-info"><span class="tournoi-label">Date de début:</span><span class="tournoi-detail">' . htmlspecialchars($tournoi['DateDebut']) . '</span></div>';
        echo '<div class="tournoi-info"><span class="tournoi-label">Date de fin:</span><span class="tournoi-detail">' . htmlspecialchars($tournoi['DateFin']) . '</span></div>';
        echo '<div class="tournoi-info"><span class="tournoi-label">Capacité:</span><span class="tournoi-detail">' . htmlspecialchars($tournoi['Capacite']) . '</span></div>';
        echo '<div class="tournoi-info"><span class="tournoi-label">Récompense:</span><span class="tournoi-detail">' . htmlspecialchars($tournoi['Recompense']) . '</span></div>';
        echo '</label>';
        echo '</div>';
    }
    exit; // Pour ne pas charger le reste de la page
}

public function chargerListeMissions() {
    $userID = $_SESSION['user_id'];
    $missionActuel = $this->modele->getIdMissionActuelle($userID);
    $missions = $this->modele->getMissions();

    foreach ($missions as $mission) {
        $class = ($mission['Id_Mission'] == $missionActuel) ? 'mission-actuel' : '';
        echo '<div class= "mission-item '. $class . '">';
        echo '<label for="mission_' . htmlspecialchars($mission['Id_Mission']) . '">';
        echo '<input type="radio" id="mission_' . htmlspecialchars($mission['Id_Mission']) . '" name="missionID" value="' . htmlspecialchars($mission['Id_Mission']) . '" style="display: none;">';
        echo '<h2 class="mission-nom">' . htmlspecialchars($mission['Description']) . '</h2>';
        echo '<div class="mission-info"><span class="mission-label">Récompense:</span><span class="mission-detail">' . htmlspecialchars($mission['Recompense']) . '</span></div>';
        echo '<div class="mission-info"><span class="mission-label">Coût de Renouvellement:</span><span class="mission-detail">' . htmlspecialchars($mission['CoutRenouvellement']) . '</span></div>';
        echo '<div class="mission-info"><span class="mission-label">Date:</span><span class="mission-detail">' . htmlspecialchars($mission['Date']) . '</span></div>';
        echo '</label>';
        echo '</div>';
    }
    exit; // Pour ne pas charger le reste de la page
}


private function prendreMission() {
    $userID = $_SESSION['user_id'];
    $missionID = isset($_POST['missionID']) ? $_POST['missionID'] : null;

    if ($missionID === null) {
        $_SESSION['popup_message'] = "Aucune mission sélectionnée.";
        return;
    }

    // Logique pour prendre une mission
    $priseReussie = $this->modele->prendreMission($userID, $missionID);

    if ($priseReussie) {
        $_SESSION['popup_message'] = "Mission prise avec succès.";
    } else {
        $_SESSION['popup_message'] = "Erreur lors de la prise de la mission.";
    }
}
private function quitterMission() {
    $userID = $_SESSION['user_id'];

    // Vérifier si 'missionID' est défini et non null
    $missionID = isset($_POST['missionID']) ? $_POST['missionID'] : null;

    if ($missionID === null) {
        $_SESSION['popup_message'] = "Aucune mission sélectionnée.";
        return;
    }

    // Logique pour quitter une mission
    $quitterReussi = $this->modele->quitterMission($userID, $missionID);

    if ($quitterReussi) {
        $_SESSION['popup_message'] = "Mission quittée avec succès.";
    } else {
        $_SESSION['popup_message'] = "Erreur lors de la tentative de quitter la mission.";
    }
}

}
?>
