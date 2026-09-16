<?php

// Charge un fichier .env minimal (KEY=VALUE) dans l'environnement du process,
// sans écraser une variable déjà définie par le serveur web.
class Env{

	public static function charger(string $chemin): void{
		if(!is_file($chemin)){
			return;
		}
		foreach(file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne){
			$ligne = trim($ligne);
			if($ligne === "" || $ligne[0] === "#" || !str_contains($ligne, "=")){
				continue;
			}
			[$cle, $valeur] = explode("=", $ligne, 2);
			$cle = trim($cle);
			$valeur = trim(trim($valeur), "\"'");
			if(getenv($cle) === false){
				putenv("$cle=$valeur");
			}
		}
	}

}
