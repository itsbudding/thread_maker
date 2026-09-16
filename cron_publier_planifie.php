<?php

// Publie toute publication programmée dont l'échéance est passée, en réutilisant exactement
// la même logique (dispatch réseau, anti-doublon, journalisation) que publier.php via
// PublicationService — voir aussi planifier.php et planifications.php.
//
// À exécuter périodiquement via une tâche planifiée système, par exemple (toutes les 5 minutes) :
//   */5 * * * * php /chemin/vers/thread_maker/cron_publier_planifie.php >> /var/log/thread_maker_cron.log 2>&1
//
// Limite connue : les dates programmées sont comparées à l'heure locale de CE serveur (voir le
// commentaire dans planifier.php sur l'origine de cette contrainte).

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";
require_once __DIR__."/lib/Crypto.php";
require_once __DIR__."/lib/PublicationService.php";

if(PHP_SAPI !== "cli"){
	http_response_code(403);
	exit("Ce script ne peut être exécuté qu'en ligne de commande (cron).");
}

$pdo = Database::connexion();
$aPublier = $pdo->query("
	SELECT * FROM publications_planifiees
	WHERE statut = 'en_attente' AND date_prevue <= datetime('now', 'localtime')
")->fetchAll();

$traitees = 0;

foreach($aPublier as $planification){
	$requeteCompte = $pdo->prepare("SELECT * FROM comptes WHERE id = :id");
	$requeteCompte->execute([":id" => $planification["compte_id"]]);
	$compte = $requeteCompte->fetch();

	if($compte === false){
		marquerEchec($pdo, (int)$planification["id"], "Compte introuvable (supprimé depuis la planification).");
		continue;
	}

	try{
		$compte["jeton"] = Crypto::dechiffrer($compte["jeton_chiffre"]);
	}
	catch(Throwable $e){
		marquerEchec($pdo, (int)$planification["id"], "Impossible de déchiffrer le jeton de ce compte.");
		continue;
	}

	$nomsImages = json_decode($planification["images"] ?? "[]", true) ?: [];
	$textesAlternatifs = json_decode($planification["images_alt"] ?? "[]", true) ?: [];

	$resultat = PublicationService::publier($compte, $planification["texte"], $nomsImages, $textesAlternatifs);

	$requeteMaj = $pdo->prepare("UPDATE publications_planifiees SET statut = :statut, message_erreur = :message WHERE id = :id");
	$requeteMaj->execute([
		":statut" => $resultat->succes ? "publiee" : "erreur",
		":message" => $resultat->messageErreur,
		":id" => $planification["id"],
	]);

	$traitees++;
}

echo "$traitees publication(s) programmée(s) traitée(s) sur ".count($aPublier).".".PHP_EOL;

function marquerEchec(PDO $pdo, int $id, string $message): void{
	$requete = $pdo->prepare("UPDATE publications_planifiees SET statut = 'erreur', message_erreur = :message WHERE id = :id");
	$requete->execute([":message" => $message, ":id" => $id]);
}
