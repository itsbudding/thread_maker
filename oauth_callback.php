<?php

// Point de retour de l'autorisation OAuth2 (voir oauth_demarrer.php) : échange le code contre un
// jeton d'accès, identifie le compte connecté, et l'enregistre comme un compte classique.

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";
require_once __DIR__."/lib/OAuthMastodon.php";
require_once __DIR__."/lib/Comptes.php";

Auth::exigerConnexion();
Auth::demarrerSession();

function afficherErreurOAuth(string $message): never{
	http_response_code(400);
	?>
	<!DOCTYPE html>
	<html lang="fr" dir="ltr">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Connexion impossible — Thread Maker</title>
			<link rel="stylesheet" href="./reset.css" />
			<link rel="stylesheet" href="./style.css" />
		</head>
		<body>
			<main role="main">
				<h1>Connexion impossible</h1>
				<p class="informations" role="alert"><?php echo h($message); ?></p>
				<p><a href="./comptes.php">Retour aux comptes</a></p>
			</main>
		</body>
	</html>
	<?php
	exit;
}

$code = $_GET["code"] ?? null;
$etatRecu = $_GET["state"] ?? null;
$etatSession = $_SESSION["oauth_etat"] ?? null;
unset($_SESSION["oauth_etat"]);

if(!is_string($code) || !is_string($etatRecu) || !is_array($etatSession) || !hash_equals((string)$etatSession["etat"], $etatRecu)){
	afficherErreurOAuth("Session d'autorisation expirée ou invalide, merci de recommencer depuis la page des comptes.");
}

$instanceUrl = $etatSession["instance_url"];
$reseau = $etatSession["reseau"];
$familleId = $etatSession["famille_id"];
$redirectUri = $etatSession["redirect_uri"];

$pdo = Database::connexion();

try{
	[$clientId, $clientSecret] = OAuthMastodon::obtenirApplication($pdo, $instanceUrl, $reseau, $redirectUri);
}
catch(RuntimeException $exception){
	afficherErreurOAuth($exception->getMessage());
}

$jeton = OAuthMastodon::echangerCode($instanceUrl, $clientId, $clientSecret, $redirectUri, $code);
if($jeton === null){
	afficherErreurOAuth("L'échange du code d'autorisation avec l'instance a échoué.");
}

$identifiant = OAuthMastodon::recupererIdentifiant($instanceUrl, $jeton) ?? $instanceUrl;

insererCompte($pdo, $familleId, $reseau, $identifiant, $instanceUrl, $jeton);

header("Location: ./comptes.php?oauth=succes");
exit;
