<?php

// Déduit l'URL publique du site (sans slash final) depuis la requête HTTP en cours. En dehors
// d'une requête HTTP (cron), $_SERVER["HTTP_HOST"] n'existe pas : APP_PUBLIC_URL dans .env doit
// alors fournir l'URL publique explicitement.
class UrlPublique{

	public static function actuelle(): string{
		$depuisEnv = getenv("APP_PUBLIC_URL");
		if($depuisEnv !== false && $depuisEnv !== ""){
			return rtrim($depuisEnv, "/");
		}
		if(isset($_SERVER["HTTP_HOST"])){
			$schema = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https://" : "http://";
			return $schema.$_SERVER["HTTP_HOST"];
		}
		throw new RuntimeException("Impossible de déterminer l'URL publique : définissez APP_PUBLIC_URL dans .env (nécessaire hors contexte web, ex. cron).");
	}

}
