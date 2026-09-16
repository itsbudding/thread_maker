<?php

// Supprime de /uploads les fichiers de plus de 48h. Les images uploadées ne sont jamais
// nettoyées automatiquement après publication (elles peuvent être réutilisées par plusieurs
// boutons "Publier" : un par segment + le carrousel Instagram), donc ce script à base de
// rétention temporelle est le mécanisme de nettoyage.
//
// À exécuter périodiquement via une tâche planifiée système, par exemple (tous les jours à 3h) :
//   0 3 * * * php /chemin/vers/thread_maker/nettoyer_uploads.php >> /var/log/thread_maker_nettoyage.log 2>&1
//
// Peut aussi être appelé via une requête HTTP authentifiée (nécessite d'être connecté à l'outil).

require_once __DIR__."/lib/bootstrap.php";

$estCli = (PHP_SAPI === "cli");
if(!$estCli){
	Auth::exigerConnexion();
}

const AGE_MAX_SECONDES = 48 * 3600;

$dossierUploads = __DIR__."/uploads";
$maintenant = time();
$supprimes = 0;

foreach(scandir($dossierUploads) as $nomFichier){
	if($nomFichier === "." || $nomFichier === ".." || $nomFichier === ".gitkeep"){
		continue;
	}

	$chemin = $dossierUploads."/".$nomFichier;
	if(!is_file($chemin)){
		continue;
	}

	if(($maintenant - filemtime($chemin)) > AGE_MAX_SECONDES){
		if(unlink($chemin)){
			$supprimes++;
		}
	}
}

$message = "$supprimes fichier(s) supprimé(s) de /uploads (plus de 48h).";

if($estCli){
	echo $message.PHP_EOL;
}
else{
	header("Content-Type: text/plain; charset=UTF-8");
	echo $message;
}
