<?php

require_once __DIR__."/Crypto.php";

// Insère un compte réseau (jeton chiffré au repos). Utilisé aussi bien par la saisie manuelle
// dans comptes.php que par le flux OAuth (oauth_callback.php), pour ne pas dupliquer la requête.
function insererCompte(PDO $pdo, int $familleId, string $reseau, string $identifiant, ?string $instanceUrl, string $jetonClair): void{
	$requete = $pdo->prepare("
		INSERT INTO comptes (famille_id, reseau, identifiant, instance_url, jeton_chiffre)
		VALUES (:famille_id, :reseau, :identifiant, :instance_url, :jeton_chiffre)
	");
	$requete->execute([
		":famille_id" => $familleId,
		":reseau" => $reseau,
		":identifiant" => $identifiant,
		":instance_url" => $instanceUrl,
		":jeton_chiffre" => Crypto::chiffrer($jetonClair),
	]);
}
