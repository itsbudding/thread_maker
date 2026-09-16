<?php

// Client pour les API compatibles Mastodon (Mastodon lui-même, et Pixelfed via sous-classe).
class MastodonPublisher implements PublisherInterface{

	public function __construct(private bool $simulation = false){}

	public function publier(array $compte, string $texte, ?string $cheminImage): PublishResult{
		if($this->simulation){
			return new PublishResult(true, "simulation-".bin2hex(random_bytes(4)));
		}

		$instance = rtrim((string)($compte["instance_url"] ?? ""), "/");
		if($instance === ""){
			return new PublishResult(false, null, "URL d'instance manquante pour ce compte.");
		}

		$mediaIds = [];
		if($cheminImage !== null){
			$idMedia = $this->uploaderMedia($instance, $compte["jeton"], $cheminImage);
			if($idMedia === null){
				return new PublishResult(false, null, "Échec de l'upload de l'image.");
			}
			$mediaIds[] = $idMedia;
		}

		$reponse = $this->appelHttp($instance."/api/v1/statuses", $compte["jeton"], [
			"status" => $texte,
			"media_ids" => $mediaIds,
		]);

		if($reponse === null){
			return new PublishResult(false, null, "Erreur réseau lors de la publication.");
		}
		if(isset($reponse["error"])){
			return new PublishResult(false, null, (string)$reponse["error"]);
		}

		return new PublishResult(true, isset($reponse["id"]) ? (string)$reponse["id"] : null);
	}

	private function uploaderMedia(string $instance, string $jeton, string $cheminImage): ?string{
		$reponse = $this->appelHttp($instance."/api/v1/media", $jeton, [
			"file" => new CURLFile($cheminImage),
		]);
		return $reponse["id"] ?? null;
	}

	private function appelHttp(string $url, string $jeton, array $donnees): ?array{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $donnees,
			CURLOPT_HTTPHEADER => ["Authorization: Bearer ".$jeton],
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
