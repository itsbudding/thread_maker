<?php

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";
require_once __DIR__."/lib/Crypto.php";
require_once __DIR__."/lib/PublicationService.php";

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

if($compteId <= 0 || trim($texte) === ""){
	repondre(400, ["succes" => false, "erreur" => "Requête invalide."]);
}

$pdo = Database::connexion();
$requete = $pdo->prepare("SELECT * FROM comptes WHERE id = :id");
$requete->execute([":id" => $compteId]);
$compte = $requete->fetch();

if($compte === false){
	repondre(404, ["succes" => false, "erreur" => "Compte introuvable."]);
}

try{
	$compte["jeton"] = Crypto::dechiffrer($compte["jeton_chiffre"]);
}
catch(Throwable $e){
	repondre(500, ["succes" => false, "erreur" => "Impossible de déchiffrer le jeton de ce compte."]);
}

if($compte["reseau"] === "instagram"){
	// Instagram publie toujours toutes les images uploadées ensemble, en un seul carrousel.
	$nomsImages = is_array($_POST["images"] ?? null) ? $_POST["images"] : [];
	if(count($nomsImages) === 0){
		repondre(400, ["succes" => false, "erreur" => "Au moins une image est nécessaire pour publier sur Instagram."]);
	}
	$textesAlternatifs = [];
}
else{
	// Réseaux "fil" : une image optionnelle (avec sa description alternative éventuelle).
	$nomImage = (string)($_POST["image_nom"] ?? "");
	$nomsImages = $nomImage !== "" ? [$nomImage] : [];
	$texteAlternatif = (string)($_POST["image_alt"] ?? "");
	$textesAlternatifs = $texteAlternatif !== "" ? [$texteAlternatif] : [];
}

$resultat = PublicationService::publier($compte, $texte, $nomsImages, $textesAlternatifs);

repondre(200, [
	"succes" => $resultat->succes,
	"erreur" => $resultat->messageErreur,
	"id_externe" => $resultat->idExterne,
]);
