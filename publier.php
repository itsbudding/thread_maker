<?php

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";
require_once __DIR__."/lib/Crypto.php";
require_once __DIR__."/lib/Publishers/PublishResult.php";
require_once __DIR__."/lib/Publishers/PublisherInterface.php";
require_once __DIR__."/lib/Publishers/MastodonPublisher.php";
require_once __DIR__."/lib/Publishers/PixelfedPublisher.php";
require_once __DIR__."/lib/Publishers/BlueskyPublisher.php";
require_once __DIR__."/lib/Publishers/InstagramPublisher.php";

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

$simulation = getenv("APP_MODE_SIMULATION") === "1";
$dossierUploads = __DIR__."/uploads";

if($compte["reseau"] === "instagram"){
	// Instagram publie toujours toutes les images uploadées ensemble, en un seul carrousel.
	$nomsImages = array_map(
		fn($nom) => basename((string)$nom),
		is_array($_POST["images"] ?? null) ? $_POST["images"] : []
	);
	$nomsImages = array_values(array_filter(
		$nomsImages,
		fn($nom) => $nom !== "" && is_file($dossierUploads."/".$nom)
	));

	if(count($nomsImages) === 0){
		repondre(400, ["succes" => false, "erreur" => "Au moins une image est nécessaire pour publier sur Instagram."]);
	}

	$hoteBase = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https://" : "http://").$_SERVER["HTTP_HOST"];
	$urlsImages = array_map(fn($nom) => $hoteBase."/uploads/".$nom, $nomsImages);

	$resultat = (new InstagramPublisher($simulation))->publierCarrousel($compte, $texte, $urlsImages);
}
else{
	// Réseaux "fil" : une image optionnelle, uploadée directement (pas besoin d'URL publique).
	$nomImage = basename((string)($_POST["image_nom"] ?? ""));
	$cheminImage = ($nomImage !== "" && is_file($dossierUploads."/".$nomImage)) ? $dossierUploads."/".$nomImage : null;

	$publisher = match($compte["reseau"]){
		"mastodon" => new MastodonPublisher($simulation),
		"pixelfed" => new PixelfedPublisher($simulation),
		"bluesky" => new BlueskyPublisher($simulation),
		default => null,
	};

	if($publisher === null){
		repondre(400, ["succes" => false, "erreur" => "Réseau non pris en charge par cet endpoint."]);
	}

	$resultat = $publisher->publier($compte, $texte, $cheminImage);
}

$journal = $pdo->prepare("
	INSERT INTO publications (compte_id, reseau, statut, message_erreur, id_externe)
	VALUES (:compte_id, :reseau, :statut, :message_erreur, :id_externe)
");
$journal->execute([
	":compte_id" => $compteId,
	":reseau" => $compte["reseau"],
	":statut" => $resultat->succes ? "succes" : "erreur",
	":message_erreur" => $resultat->messageErreur,
	":id_externe" => $resultat->idExterne,
]);

repondre(200, [
	"succes" => $resultat->succes,
	"erreur" => $resultat->messageErreur,
	"id_externe" => $resultat->idExterne,
]);
