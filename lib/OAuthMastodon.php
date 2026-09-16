<?php

require_once __DIR__."/Crypto.php";

// Client OAuth2 pour les API compatibles Mastodon (Mastodon et Pixelfed, qui partagent le même
// protocole d'enregistrement d'application et d'autorisation). L'application est enregistrée à
// la volée sur chaque instance via POST /api/v1/apps — ce point de l'API Mastodon n'exige aucune
// inscription développeur préalable — et le résultat est mis en cache dans `applications_oauth`
// pour ne pas ré-enregistrer une application à chaque connexion.
class OAuthMastodon{

	private const SCOPES = "read write";

	public static function obtenirApplication(PDO $pdo, string $instanceUrl, string $reseau, string $redirectUri): array{
		$requete = $pdo->prepare("
			SELECT client_id, client_secret_chiffre FROM applications_oauth
			WHERE instance_url = :instance_url AND reseau = :reseau
		");
		$requete->execute([":instance_url" => $instanceUrl, ":reseau" => $reseau]);
		$ligne = $requete->fetch();
		if($ligne !== false){
			return [$ligne["client_id"], Crypto::dechiffrer($ligne["client_secret_chiffre"])];
		}

		$reponse = self::appelHttp($instanceUrl."/api/v1/apps", [
			"client_name" => "Thread Maker",
			"redirect_uris" => $redirectUri,
			"scopes" => self::SCOPES,
		]);

		if(!isset($reponse["client_id"], $reponse["client_secret"])){
			throw new RuntimeException("Impossible d'enregistrer l'application auprès de ".$instanceUrl.".");
		}

		$requete = $pdo->prepare("
			INSERT INTO applications_oauth (instance_url, reseau, client_id, client_secret_chiffre)
			VALUES (:instance_url, :reseau, :client_id, :client_secret_chiffre)
		");
		$requete->execute([
			":instance_url" => $instanceUrl,
			":reseau" => $reseau,
			":client_id" => $reponse["client_id"],
			":client_secret_chiffre" => Crypto::chiffrer($reponse["client_secret"]),
		]);

		return [$reponse["client_id"], $reponse["client_secret"]];
	}

	public static function urlAutorisation(string $instanceUrl, string $clientId, string $redirectUri, string $etat): string{
		return $instanceUrl."/oauth/authorize?".http_build_query([
			"client_id" => $clientId,
			"redirect_uri" => $redirectUri,
			"response_type" => "code",
			"scope" => self::SCOPES,
			"state" => $etat,
		]);
	}

	public static function echangerCode(string $instanceUrl, string $clientId, string $clientSecret, string $redirectUri, string $code): ?string{
		$reponse = self::appelHttp($instanceUrl."/oauth/token", [
			"client_id" => $clientId,
			"client_secret" => $clientSecret,
			"redirect_uri" => $redirectUri,
			"grant_type" => "authorization_code",
			"code" => $code,
			"scope" => self::SCOPES,
		]);

		return $reponse["access_token"] ?? null;
	}

	public static function recupererIdentifiant(string $instanceUrl, string $jeton): ?string{
		$ch = curl_init($instanceUrl."/api/v1/accounts/verify_credentials");
		curl_setopt_array($ch, [
			CURLOPT_HTTPHEADER => ["Authorization: Bearer ".$jeton],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 15,
		]);
		$corps = curl_exec($ch);
		curl_close($ch);

		$decode = is_string($corps) ? json_decode($corps, true) : null;
		return is_array($decode) ? ($decode["acct"] ?? $decode["username"] ?? null) : null;
	}

	private static function appelHttp(string $url, array $donnees): ?array{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $donnees,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 15,
		]);
		$corps = curl_exec($ch);
		curl_close($ch);

		if($corps === false){
			return null;
		}

		$decode = json_decode($corps, true);
		return is_array($decode) ? $decode : null;
	}

}
