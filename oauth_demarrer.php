<?php

// Démarre la connexion OAuth2 à un compte Mastodon/Pixelfed : enregistre l'application sur
// l'instance donnée si besoin, puis redirige l'utilisateur vers la page d'autorisation de
// l'instance. Le callback (oauth_callback.php) reprend la main une fois l'autorisation donnée.

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";
require_once __DIR__."/lib/UrlPublique.php";
require_once __DIR__."/lib/OAuthMastodon.php";

Auth::exigerConnexion();

const RESEAUX_OAUTH_DISPONIBLES = ["mastodon", "pixelfed"];

if($_SERVER["REQUEST_METHOD"] !== "POST" || !Auth::verifierCsrf($_POST["jeton_csrf"] ?? null)){
	http_response_code(400);
	echo "Requête invalide, merci de retourner sur la page des comptes et de réessayer.";
	exit;
}

$familleId = (int)($_POST["famille_id"] ?? 0);
$reseau = $_POST["reseau"] ?? "";
$instanceUrl = trim($_POST["instance_url"] ?? "");

if($familleId <= 0 || !in_array($reseau, RESEAUX_OAUTH_DISPONIBLES, true) || $instanceUrl === ""){
	http_response_code(400);
	echo "Famille, réseau et URL d'instance sont obligatoires.";
	exit;
}

if(!preg_match("#^https?://#i", $instanceUrl)){
	$instanceUrl = "https://".$instanceUrl;
}
$instanceUrl = rtrim($instanceUrl, "/");

$pdo = Database::connexion();
$redirectUri = UrlPublique::actuelle()."/oauth_callback.php";

try{
	[$clientId, ] = OAuthMastodon::obtenirApplication($pdo, $instanceUrl, $reseau, $redirectUri);
}
catch(RuntimeException $exception){
	http_response_code(502);
	echo h($exception->getMessage());
	exit;
}

Auth::demarrerSession();
$etat = bin2hex(random_bytes(16));
$_SESSION["oauth_etat"] = [
	"etat" => $etat,
	"famille_id" => $familleId,
	"reseau" => $reseau,
	"instance_url" => $instanceUrl,
	"redirect_uri" => $redirectUri,
];

header("Location: ".OAuthMastodon::urlAutorisation($instanceUrl, $clientId, $redirectUri, $etat));
exit;
