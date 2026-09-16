<?php

// Client AT Protocol (Bluesky) : authentification par "app password", puis createRecord.
class BlueskyPublisher implements PublisherInterface{

	private const PDS = "https://bsky.social";

	public function __construct(private bool $simulation = false){}

	public function publier(array $compte, string $texte, ?string $cheminImage): PublishResult{
		if($this->simulation){
			return new PublishResult(true, "simulation-".bin2hex(random_bytes(4)));
		}

		$session = $this->creerSession($compte["identifiant"], $compte["jeton"]);
		if($session === null){
			return new PublishResult(false, null, "Authentification Bluesky échouée.");
		}

		$embed = null;
		if($cheminImage !== null){
			$blob = $this->uploaderBlob($session, $cheminImage);
			if($blob === null){
				return new PublishResult(false, null, "Échec de l'upload de l'image sur Bluesky.");
			}
			$embed = [
				"\$type" => "app.bsky.embed.images",
				"images" => [["image" => $blob, "alt" => ""]],
			];
		}

		$enregistrement = [
			"\$type" => "app.bsky.feed.post",
			"text" => $texte,
			"createdAt" => gmdate("Y-m-d\TH:i:s.000\Z"),
		];
		if($embed !== null){
			$enregistrement["embed"] = $embed;
		}

		$reponse = $this->appelHttp(self::PDS."/xrpc/com.atproto.repo.createRecord", $session["accessJwt"], [
			"repo" => $session["did"],
			"collection" => "app.bsky.feed.post",
			"record" => $enregistrement,
		]);

		if($reponse === null || !isset($reponse["uri"])){
			return new PublishResult(false, null, "Erreur lors de la publication sur Bluesky.");
		}

		return new PublishResult(true, (string)$reponse["uri"]);
	}

	private function creerSession(string $identifiant, string $motDePasseApp): ?array{
		$reponse = $this->appelHttp(self::PDS."/xrpc/com.atproto.server.createSession", null, [
			"identifier" => $identifiant,
			"password" => $motDePasseApp,
		]);
		return (is_array($reponse) && isset($reponse["accessJwt"], $reponse["did"])) ? $reponse : null;
	}

	private function uploaderBlob(array $session, string $cheminImage): ?array{
		$corps = file_get_contents($cheminImage);
		if($corps === false){
			return null;
		}

		$ch = curl_init(self::PDS."/xrpc/com.atproto.repo.uploadBlob");
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $corps,
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer ".$session["accessJwt"],
				"Content-Type: ".(mime_content_type($cheminImage) ?: "application/octet-stream"),
			],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 20,
		]);
		$reponseBrute = curl_exec($ch);
		curl_close($ch);

		$decode = $reponseBrute !== false ? json_decode($reponseBrute, true) : null;
		return $decode["blob"] ?? null;
	}

	private function appelHttp(string $url, ?string $jeton, array $donnees): ?array{
		$entetes = ["Content-Type: application/json"];
		if($jeton !== null){
			$entetes[] = "Authorization: Bearer ".$jeton;
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($donnees),
			CURLOPT_HTTPHEADER => $entetes,
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
