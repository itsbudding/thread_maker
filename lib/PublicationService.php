<?php

require_once __DIR__."/Database.php";
require_once __DIR__."/Publishers/PublishResult.php";
require_once __DIR__."/Publishers/PublisherInterface.php";
require_once __DIR__."/Publishers/MastodonPublisher.php";
require_once __DIR__."/Publishers/PixelfedPublisher.php";
require_once __DIR__."/Publishers/BlueskyPublisher.php";
require_once __DIR__."/Publishers/InstagramPublisher.php";

// Point d'entrée unique pour publier réellement sur un réseau : résout les images uploadées,
// applique la protection anti double-publication, appelle le bon client, et journalise le
// résultat dans `publications`. Utilisé par publier.php (immédiat) et par le cron de
// publication programmée — donc jamais de dépendance à une requête HTTP en cours ($_SERVER
// peut être absent en CLI, voir urlPubliqueParDefaut()).
class PublicationService{

	// $nomsImages : noms de fichiers dans /uploads (jamais de chemin/URL), dans l'ordre d'upload.
	// $textesAlternatifs : descriptions associées, même ordre (ignorées pour Instagram, l'API
	// Graph n'expose pas de paramètre alt sur les conteneurs média).
	public static function publier(
		array $compte,
		string $texte,
		array $nomsImages = [],
		array $textesAlternatifs = [],
		?string $urlPubliqueBase = null
	): PublishResult{

		$pdo = Database::connexion();
		$simulation = getenv("APP_MODE_SIMULATION") === "1";
		$dossierUploads = __DIR__."/../uploads";

		$nomsImages = array_values(array_filter(
			array_map(fn($nom) => basename((string)$nom), $nomsImages),
			fn($nom) => $nom !== "" && is_file($dossierUploads."/".$nom)
		));

		$cleIdempotence = hash("sha256", $compte["id"]."|".$texte."|".implode(",", $nomsImages));
		$dejaPublie = self::rechercherPublicationRecente($pdo, $cleIdempotence);
		if($dejaPublie !== null){
			// Contenu identique déjà publié avec succès dans les dernières 24h : on ne réappelle
			// pas l'API (évite un doublon en cas de double clic / rechargement / retry réseau)
			// et on ne journalise pas de nouvelle ligne, celle d'origine suffit.
			return new PublishResult(true, $dejaPublie);
		}

		if($compte["reseau"] === "instagram"){
			$resultat = self::publierInstagram($compte, $texte, $nomsImages, $urlPubliqueBase, $simulation);
		}
		else{
			$resultat = self::publierReseauFil($compte, $texte, $nomsImages, $textesAlternatifs, $dossierUploads, $simulation);
		}

		self::journaliser($pdo, $compte, $resultat, $cleIdempotence);

		return $resultat;
	}

	private static function publierInstagram(array $compte, string $texte, array $nomsImages, ?string $urlPubliqueBase, bool $simulation): PublishResult{
		if(count($nomsImages) === 0){
			return new PublishResult(false, null, "Au moins une image est nécessaire pour publier sur Instagram.");
		}

		$base = rtrim($urlPubliqueBase ?? self::urlPubliqueParDefaut(), "/");
		$urlsImages = array_map(fn($nom) => $base."/uploads/".$nom, $nomsImages);

		return (new InstagramPublisher($simulation))->publierCarrousel($compte, $texte, $urlsImages);
	}

	private static function publierReseauFil(array $compte, string $texte, array $nomsImages, array $textesAlternatifs, string $dossierUploads, bool $simulation): PublishResult{
		$publisher = match($compte["reseau"]){
			"mastodon" => new MastodonPublisher($simulation),
			"pixelfed" => new PixelfedPublisher($simulation),
			"bluesky" => new BlueskyPublisher($simulation),
			default => null,
		};

		if($publisher === null){
			return new PublishResult(false, null, "Réseau non pris en charge.");
		}

		$cheminImage = isset($nomsImages[0]) ? $dossierUploads."/".$nomsImages[0] : null;
		$texteAlternatif = $textesAlternatifs[0] ?? null;

		return $publisher->publier($compte, $texte, $cheminImage, $texteAlternatif);
	}

	private static function rechercherPublicationRecente(PDO $pdo, string $cleIdempotence): ?string{
		$requete = $pdo->prepare("
			SELECT id_externe FROM publications
			WHERE cle_idempotence = :cle AND statut = 'succes' AND cree_le >= datetime('now', '-1 day')
			ORDER BY id DESC LIMIT 1
		");
		$requete->execute([":cle" => $cleIdempotence]);
		$ligne = $requete->fetch();
		return $ligne !== false ? $ligne["id_externe"] : null;
	}

	private static function journaliser(PDO $pdo, array $compte, PublishResult $resultat, string $cleIdempotence): void{
		$requete = $pdo->prepare("
			INSERT INTO publications (compte_id, reseau, statut, message_erreur, id_externe, cle_idempotence)
			VALUES (:compte_id, :reseau, :statut, :message_erreur, :id_externe, :cle_idempotence)
		");
		$requete->execute([
			":compte_id" => $compte["id"],
			":reseau" => $compte["reseau"],
			":statut" => $resultat->succes ? "succes" : "erreur",
			":message_erreur" => $resultat->messageErreur,
			":id_externe" => $resultat->idExterne,
			":cle_idempotence" => $cleIdempotence,
		]);
	}

	// En dehors d'une requête HTTP (cron), $_SERVER["HTTP_HOST"] n'existe pas : APP_PUBLIC_URL
	// dans .env doit alors fournir l'URL publique du site (nécessaire pour Instagram, qui va
	// chercher lui-même les images sur cette URL).
	private static function urlPubliqueParDefaut(): string{
		$depuisEnv = getenv("APP_PUBLIC_URL");
		if($depuisEnv !== false && $depuisEnv !== ""){
			return $depuisEnv;
		}
		if(isset($_SERVER["HTTP_HOST"])){
			$schema = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https://" : "http://";
			return $schema.$_SERVER["HTTP_HOST"];
		}
		throw new RuntimeException("Impossible de déterminer l'URL publique : définissez APP_PUBLIC_URL dans .env (nécessaire hors contexte web, ex. cron).");
	}

}
