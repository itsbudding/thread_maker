<?php

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";

Auth::exigerConnexion();
header("Content-Type: application/json; charset=UTF-8");

function repondre(int $code, array $donnees): never{
	http_response_code($code);
	echo json_encode($donnees);
	exit;
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
	repondre(405, ["succes" => false, "erreur" => "Méthode non autorisée."]);
}

if(!Auth::verifierCsrf($_POST["jeton_csrf"] ?? null)){
	repondre(403, ["succes" => false, "erreur" => "Session expirée, merci de recharger la page."]);
}

$compteId = (int)($_POST["compte_id"] ?? 0);
$texte = (string)($_POST["texte"] ?? "");
$datePrevueBrute = (string)($_POST["date_prevue"] ?? "");

if($compteId <= 0 || trim($texte) === "" || $datePrevueBrute === ""){
	repondre(400, ["succes" => false, "erreur" => "Requête invalide."]);
}

// Un <input type="datetime-local"> envoie "AAAA-MM-JJTHH:MM" dans le fuseau horaire du
// navigateur ; faute de mieux, on la stocke telle quelle et on la compare plus tard à l'heure
// locale du serveur (limite documentée : à utiliser avec un serveur dans le même fuseau).
$datePrevue = DateTime::createFromFormat("Y-m-d\TH:i", $datePrevueBrute);
if($datePrevue === false){
	repondre(400, ["succes" => false, "erreur" => "Date invalide."]);
}
if($datePrevue <= new DateTime()){
	repondre(400, ["succes" => false, "erreur" => "La date programmée doit être dans le futur."]);
}

$pdo = Database::connexion();
$requete = $pdo->prepare("SELECT id, reseau FROM comptes WHERE id = :id");
$requete->execute([":id" => $compteId]);
$compte = $requete->fetch();

if($compte === false){
	repondre(404, ["succes" => false, "erreur" => "Compte introuvable."]);
}

if($compte["reseau"] === "instagram"){
	$nomsImages = is_array($_POST["images"] ?? null) ? $_POST["images"] : [];
	if(count($nomsImages) === 0){
		repondre(400, ["succes" => false, "erreur" => "Au moins une image est nécessaire pour programmer une publication Instagram."]);
	}
	$textesAlternatifs = [];
}
else{
	$nomImage = (string)($_POST["image_nom"] ?? "");
	$nomsImages = $nomImage !== "" ? [$nomImage] : [];
	$texteAlternatif = (string)($_POST["image_alt"] ?? "");
	$textesAlternatifs = $texteAlternatif !== "" ? [$texteAlternatif] : [];
}

$requete = $pdo->prepare("
	INSERT INTO publications_planifiees (compte_id, reseau, texte, images, images_alt, date_prevue)
	VALUES (:compte_id, :reseau, :texte, :images, :images_alt, :date_prevue)
");
$requete->execute([
	":compte_id" => $compteId,
	":reseau" => $compte["reseau"],
	":texte" => $texte,
	":images" => json_encode($nomsImages),
	":images_alt" => json_encode($textesAlternatifs),
	":date_prevue" => $datePrevue->format("Y-m-d H:i:s"),
]);

repondre(200, ["succes" => true]);
