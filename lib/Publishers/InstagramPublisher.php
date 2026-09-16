<?php

// Client Graph API pour Instagram : contrairement aux autres réseaux, une image (ou plusieurs,
// en carrousel) est obligatoire — il n'existe pas de publication texte seul. $compte["identifiant"]
// doit être l'ID du compte professionnel Instagram (ig-user-id), et $compte["jeton"] un jeton
// d'accès Meta valide (instagram_business_content_publish).
class InstagramPublisher{

	private const VERSION_API = "v19.0";
	private const LIMITE_CAROUSEL = 10;

	public function __construct(private bool $simulation = false){}

	// $urlsImages doit contenir des URL publiques (Meta les récupère lui-même), jamais des chemins locaux.
	public function publierCarrousel(array $compte, string $legende, array $urlsImages): PublishResult{
		if($this->simulation){
			return new PublishResult(true, "simulation-".bin2hex(random_bytes(4)));
		}

		if(count($urlsImages) === 0){
			return new PublishResult(false, null, "Au moins une image est nécessaire pour publier sur Instagram.");
		}
		if(count($urlsImages) > self::LIMITE_CAROUSEL){
			return new PublishResult(false, null, "Instagram limite un carrousel à ".self::LIMITE_CAROUSEL." images.");
		}

		$idCompte = $compte["identifiant"];
		$jeton = $compte["jeton"];

		if(count($urlsImages) === 1){
			$idConteneur = $this->creerConteneurMedia($idCompte, $jeton, [
				"image_url" => $urlsImages[0],
				"caption" => $legende,
			]);
		}
		else{
			$idsEnfants = [];
			foreach($urlsImages as $url){
				$idEnfant = $this->creerConteneurMedia($idCompte, $jeton, [
					"image_url" => $url,
					"is_carousel_item" => "true",
				]);
				if($idEnfant === null){
					return new PublishResult(false, null, "Échec de la création d'un élément du carrousel.");
				}
				$idsEnfants[] = $idEnfant;
			}

			$idConteneur = $this->creerConteneurMedia($idCompte, $jeton, [
				"media_type" => "CAROUSEL",
				"caption" => $legende,
				"children" => implode(",", $idsEnfants),
			]);
		}

		if($idConteneur === null){
			return new PublishResult(false, null, "Échec de la création du conteneur média Instagram.");
		}

		$publication = $this->appelHttp(
			"https://graph.facebook.com/".self::VERSION_API."/{$idCompte}/media_publish",
			["creation_id" => $idConteneur, "access_token" => $jeton]
		);

		if($publication === null || !isset($publication["id"])){
			return new PublishResult(false, null, "Échec de la publication Instagram.");
		}

		return new PublishResult(true, (string)$publication["id"]);
	}

	private function creerConteneurMedia(string $idCompte, string $jeton, array $parametres): ?string{
		$parametres["access_token"] = $jeton;
		$reponse = $this->appelHttp("https://graph.facebook.com/".self::VERSION_API."/{$idCompte}/media", $parametres);
		return $reponse["id"] ?? null;
	}

	private function appelHttp(string $url, array $donnees): ?array{
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $donnees,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 20,
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
