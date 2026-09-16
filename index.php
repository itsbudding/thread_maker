<?php

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";

Auth::exigerConnexion();

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<?php include __DIR__."/lib/theme_init.php"; ?>
		<meta name="description" content="Création de threads pour les réseaux sociaux">
		<meta name="keywords" content="">
		<meta name="author" content="It's Budding">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Thread Maker</title>
		<link rel="stylesheet" href="./reset.css" />
		<link rel="stylesheet" href="./style.css" />
		<link rel="icon" type="image/png" href="favicon.png">
	</head>
<?php
	
	define("MAX_LENGTH_TWITTER", 280);
	define("MAX_LENGTH_MASTODON", 666);
	define("MAX_LENGTH_THREADS", 500);
	define("MAX_LENGTH_BLUESKY", 300);
	define("MAX_LENGTH_INSTAGRAM", 2200);
	define("MAX_LENGTH_PIXELFED", 2000);
	define("MAX_LENGTH_FACEBOOK", 33000);
	define("MAX_LENGTH_YOUTUBE", 7700);

	define("PAGINATION", "µµµµµ"); // ££ : numero de tweet, µµ : nombre total de tweets

	class publiRS{
		public $length;
		public $maxlength;
		public $content;
		
		function __construct(array $values, int $max_length){
			$this->length = grapheme_strlen($values["thread_content"]);
			$this->maxlength = $max_length;
			
			$hashtags = stringifyHashtags($values["thread_hashtags"]);
			$content = $values["thread_content"];
			$content = str_replace("\r\n", "¤", $content);
			$this->content = $content."¤¤".$hashtags;
		}
	}
	
	class threadRS{
		public $total;
		public $posts;
		
		function __construct(array $values, int $max_length){
			$this->total = 0;
			$this->posts = $this->decoupage($values, $max_length);
		}
		
		private function decoupage(array $values, int $max_length){
			
			$arrayReturn = array();
			
			$prefixe = $values["thread_habillage_prefixe"];
			$suffixe = $values["thread_habillage_suffixe"];
			$hashtags = stringifyHashtags($values["thread_hashtags"]);
			$content = $values["thread_content"];
			
			$content = str_replace("\r\n", "¤", $content);
			// Calcul du nombre de caractères disponibles :
			if(trim($prefixe) != ""){
				$habillage = trim(trim($prefixe)." ")." ...¤¤".trim($suffixe)." ".PAGINATION."¤¤".trim($hashtags);								
			}
			else{
				$habillage = " ...¤¤".trim($suffixe)." ".PAGINATION."¤¤".trim($hashtags);								
			}
			$habillage_sec = " ...¤¤".PAGINATION;
			
			$habillage_length = grapheme_strlen($habillage);
			
			$habillage_length_sec = grapheme_strlen($habillage_sec);
			
			// $texteBrut = substr($content, 0, $max_length - $habillage_length);
			$texteBrut = grapheme_substr($content, 0, $max_length - $habillage_length);
			
			// echo "max : ".$max_length." - habillage : ".$habillage_length." = ".($max_length - $habillage_length)."<br/>";
			// echo $texteBrut;
			// echo "<hr/>";
			// echo var_dump($texteBrut);
			// echo "<hr/>";
			
			
			$offset = grapheme_strrpos($texteBrut, " ");
			// $offset = strrpos($texteBrut, " ");

			$texteCoupePropre = grapheme_substr($texteBrut, 0, $offset);
			
			$premier = trim(trim($prefixe)." ".$texteCoupePropre." ...¤¤".trim($suffixe)." ".PAGINATION."¤¤".trim($hashtags));
			// $premier = $texteCoupePropre;
			
			array_push($arrayReturn, $premier);
			$this->total++;
			
			$content_restant = grapheme_substr($content, $offset);
			$publi_length = $max_length - $habillage_length_sec;
			
			while(grapheme_strlen($content_restant) > $publi_length){
				
				$publiBrut = grapheme_substr($content_restant, 0, $publi_length);
				$tmpPos = grapheme_strrpos($publiBrut, " ");
				$publiCoupePropre = grapheme_substr($publiBrut, 0, $tmpPos);
				
				$publiCoupePropre .= " ...¤¤".PAGINATION;
				array_push($arrayReturn, $publiCoupePropre);
				
				$content_restant = grapheme_substr($content_restant, $tmpPos);
				$this->total++;
				
			}
			
			// récupération du segment restant
			array_push($arrayReturn, $content_restant."¤¤".PAGINATION);
			//array_push($arrayReturn, $content_restant);
			$this->total++;
			
			return $arrayReturn;
		}
	}
	
	class tmForm{

		public $theme = "none";
		public $familleId = 0;
		public $prefixe = "";
		public $suffixe = "🧵⬇️";
		public $hashtags = "";
		public $content = "";

		public $twitter;
		public $mastodon;
		public $threads;
		public $bluesky;
		public $instagram;
		public $facebook;
		public $youtube;
		public $pixelfed;

		function __construct(array $values){

			// Les marqueurs internes ("¤" et PAGINATION) ne doivent jamais provenir de la saisie utilisateur,
			// sinon ils entrent en collision avec la logique de découpage/pagination.
			foreach(["thread_content", "thread_habillage_prefixe", "thread_habillage_suffixe", "thread_hashtags"] as $champ){
				if(isset($values[$champ])){
					$values[$champ] = str_replace(["¤", PAGINATION], "", $values[$champ]);
				}
			}

			if(isset($values["thread_theme"])) $this->theme = $values["thread_theme"];
			if(isset($values["famille_id"])) $this->familleId = (int)$values["famille_id"];
			if(isset($values["thread_habillage_prefixe"])) $this->prefixe = $values["thread_habillage_prefixe"];
			if(isset($values["thread_habillage_suffixe"])) $this->suffixe = $values["thread_habillage_suffixe"];
			if(isset($values["thread_hashtags"])) $this->hashtags = stringifyHashtags($values["thread_hashtags"]);
			if(isset($values["thread_content"])){

				$this->content = $values["thread_content"];

				$reseauxThread = [
					"twitter"  => MAX_LENGTH_TWITTER,
					"mastodon" => MAX_LENGTH_MASTODON,
					"threads"  => MAX_LENGTH_THREADS,
					"bluesky"  => MAX_LENGTH_BLUESKY,
				];
				foreach($reseauxThread as $reseau => $maxLength){
					$this->$reseau = new threadRS($values, $maxLength);
				}

				$reseauxPubli = [
					"instagram" => MAX_LENGTH_INSTAGRAM,
					"facebook"  => MAX_LENGTH_FACEBOOK,
					"youtube"   => MAX_LENGTH_YOUTUBE,
					"pixelfed"  => MAX_LENGTH_PIXELFED,
				];
				foreach($reseauxPubli as $reseau => $maxLength){
					$this->$reseau = new publiRS($values, $maxLength);
				}
			}
		}

	}

	function stringifyHashtags(string $hashtags){

		$stringHashtags = implode(" #", explode(" ", $hashtags));
		$stringHashtags = "#".$stringHashtags;

		return $stringHashtags;
	}
	function unstringifyHashtags(string $hashtags){

		$stringHashtags = str_replace("#", "", $hashtags);
		return $stringHashtags;
	}

	// Traite les images uploadées avec le formulaire : les enregistre dans /uploads avec un nom
	// non-devinable, et renvoie la liste de leurs noms de fichiers (dans l'ordre d'upload).
	function traiterImagesUploadees(array $fichiers): array{
		$nomsFichiers = [];
		if(!isset($fichiers["images"]["tmp_name"]) || !is_array($fichiers["images"]["tmp_name"])){
			return $nomsFichiers;
		}

		$dossierUploads = __DIR__."/uploads";
		if(!is_dir($dossierUploads)){
			mkdir($dossierUploads, 0770, true);
		}

		$extensionsAutorisees = ["jpg", "jpeg", "png"];

		foreach($fichiers["images"]["tmp_name"] as $i => $cheminTemporaire){
			if(($fichiers["images"]["error"][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK){
				continue;
			}
			$extension = strtolower(pathinfo($fichiers["images"]["name"][$i], PATHINFO_EXTENSION));
			if(!in_array($extension, $extensionsAutorisees, true)){
				continue;
			}
			$nomFichier = bin2hex(random_bytes(16)).".".$extension;
			if(move_uploaded_file($cheminTemporaire, $dossierUploads."/".$nomFichier)){
				$nomsFichiers[] = $nomFichier;
			}
		}

		return $nomsFichiers;
	}

	// Bouton "Publier" affiché uniquement si un compte est configuré pour ce réseau/cette famille.
	// $imageNom (optionnel) associe une image uploadée à ce segment précis (réseaux "fil"),
	// $imageAlt sa description alternative (Mastodon/Pixelfed/Bluesky uniquement, voir plus bas).
	function boutonPublier(?array $compte, string $idTexte, ?string $imageNom = null, ?string $imageAlt = null, bool $familleSelectionnee = false): string{
		if($compte === null){
			return $familleSelectionnee ? " <span class=\"informations\">Aucun compte configuré pour ce réseau sur cette famille.</span>" : "";
		}
		$argImage = $imageNom !== null ? "'".h($imageNom)."'" : "null";
		$argAlt = ($imageAlt !== null && trim($imageAlt) !== "") ? "'".h($imageAlt)."'" : "null";
		$html = " <button id='btn_publier_$idTexte' class=\"copy publier\" onclick=\"publier('$idTexte', ".(int)$compte["id"].", $argImage, $argAlt)\">Publier</button>";
		$html .= controlesProgrammation($idTexte, $compte["id"], $argImage, $argAlt);
		return $html;
	}

	// Contrôle "Programmer" : un input datetime-local + un bouton, à côté de chaque bouton "Publier".
	// $argImage/$argAlt sont déjà formatés en littéraux JS ('...' ou null), pour être réutilisés tels quels.
	function controlesProgrammation(string $idTexte, int $compteId, string $argImage = "null", string $argAlt = "null"): string{
		return " <input type='datetime-local' id='date_$idTexte' class=\"date_programmation\" aria-label='Date de programmation' />".
			" <button id='btn_programmer_$idTexte' class=\"copy programmer\" onclick=\"programmer('$idTexte', $compteId, $argImage, $argAlt)\">Programmer</button>";
	}

	// Affiche les panneaux "fil" (plusieurs posts numérotés) : Twitter, BlueSky, Mastodon, Threads.
	// $images / $imagesAlt (optionnels) sont répartis une entrée par segment : segment i ↔ [i].
	function afficherPanneauFil($threadRS, string $prefixeId, ?array $compte = null, array $images = [], array $imagesAlt = [], bool $familleSelectionnee = false){
		if(!isset($threadRS)) return;
		foreach($threadRS->posts as $i => $post){
			$post = h($post);
			$post = str_replace("¤", "<br/>", $post);
			$post = str_replace(PAGINATION, ($i+1)."/".$threadRS->total, $post);
			$idTexte = "{$prefixeId}_$i";
			echo "<div class='segment'><p id='$idTexte'>$post</p><button id='btn_$idTexte' onclick=\"copier('$idTexte')\" class=\"copy\">Copier</button>".boutonPublier($compte, $idTexte, $images[$i] ?? null, $imagesAlt[$i] ?? null, $familleSelectionnee)."</div>";
		}
	}

	// Affiche les panneaux "post unique" avec indicateur de longueur : Instagram, Facebook, Pixelfed, Youtube.
	function afficherPanneauPubli($publiRS, string $prefixeId, ?array $compte = null, ?string $imageNom = null, ?string $imageAlt = null, bool $familleSelectionnee = false){
		if(!isset($publiRS)) return;
		echo "<div class=\"ctrl_length\" data-valuemax=\"".$publiRS->maxlength."\" data-valuenow=\"".$publiRS->length."\">";
		echo "<span class='length'>".$publiRS->length."</span> / ".$publiRS->maxlength." caractères</div>";
		$post = h($publiRS->content);
		$post = str_replace("¤", "<br/>", $post);
		$idTexte = "{$prefixeId}_0";
		echo "<div class='segment'><p id='$idTexte'>$post</p><button id='btn_$idTexte' onclick=\"copier('$idTexte')\" class=\"copy\">Copier</button>".boutonPublier($compte, $idTexte, $imageNom, $imageAlt, $familleSelectionnee)."</div>";
	}

	// Panneau Instagram : toutes les images uploadées sont publiées ensemble, en un seul carrousel.
	function afficherPanneauInstagram($publiRS, ?array $compte, array $images, bool $familleSelectionnee = false){
		if(!isset($publiRS)) return;
		echo "<div class=\"ctrl_length\" data-valuemax=\"".$publiRS->maxlength."\" data-valuenow=\"".$publiRS->length."\">";
		echo "<span class='length'>".$publiRS->length."</span> / ".$publiRS->maxlength." caractères</div>";
		$post = h($publiRS->content);
		$post = str_replace("¤", "<br/>", $post);
		$idTexte = "insta_0";
		echo "<div class='segment'><p id='$idTexte'>$post</p><button id='btn_$idTexte' onclick=\"copier('$idTexte')\" class=\"copy\">Copier</button>";
		if($compte !== null){
			if(count($images) > 0){
				$imagesJson = htmlspecialchars(json_encode(array_values($images)), ENT_QUOTES, "UTF-8");
				echo " <button id='btn_publier_$idTexte' class=\"copy publier\" onclick='publierInstagram(&quot;$idTexte&quot;, ".(int)$compte["id"].", $imagesJson)'>Publier (carrousel)</button>";
				echo " <input type='datetime-local' id='date_$idTexte' class=\"date_programmation\" aria-label='Date de programmation' />";
				echo " <button id='btn_programmer_$idTexte' class=\"copy programmer\" onclick='programmerInstagram(&quot;$idTexte&quot;, ".(int)$compte["id"].", $imagesJson)'>Programmer (carrousel)</button>";
			}
			else{
				echo " <span class=\"informations\">Ajoutez au moins une image ci-dessus pour publier sur Instagram.</span>";
			}
		}
		elseif($familleSelectionnee){
			echo " <span class=\"informations\">Aucun compte Instagram configuré pour cette famille.</span>";
		}
		echo "</div>";
	}

	// echo "<pre>"; print_r($_POST); echo "</pre>";
	
	$valeurs = new tmForm($_POST);

	// echo "<pre>POST :<br/>"; print_r($valeurs); echo "</pre>";
	// echo "<hr/>";
	// echo "<pre>$valeurs->content</pre>";

	// Familles de comptes disponibles pour la publication (PR "réseaux simples" : mastodon/pixelfed/bluesky).
	$pdo = Database::connexion();
	$familles = $pdo->query("SELECT * FROM familles ORDER BY nom")->fetchAll();

	$comptesParReseau = [];
	if($valeurs->familleId > 0){
		$requeteComptes = $pdo->prepare("SELECT * FROM comptes WHERE famille_id = :famille_id");
		$requeteComptes->execute([":famille_id" => $valeurs->familleId]);
		foreach($requeteComptes->fetchAll() as $compte){
			$comptesParReseau[$compte["reseau"]] = $compte;
		}
	}

	$jetonCsrfPublication = Auth::jetonCsrf();

	// Images uploadées avec le formulaire (PR "Instagram") : une par segment sur les réseaux "fil",
	// toutes ensemble en carrousel sur Instagram.
	$imagesUploadees = isset($_FILES["images"]) ? traiterImagesUploadees($_FILES) : [];

	// Une description alternative par image uploadée, dans le même ordre que $imagesUploadees
	// (un champ par miniature, généré en JS — voir afficherApercuImages() dans script.js).
	$imagesAltUploadees = is_array($_POST["images_alt"] ?? null) ? array_map("trim", $_POST["images_alt"]) : [];

?>
	<body>
		<header role="banner">
			<h1>Thread Maker</h1>
			<button id="bouton_theme" type="button" onclick="basculerTheme()">🌙 Mode sombre</button>
		</header>
		<?php $pageCourante = "index"; include __DIR__."/lib/nav.php"; ?>
		<main role="main">
			<div id="formulaire">
				<h2 id="form_lbl" class="visually-hidden">Formulaire de saisie</h2>
				<form action="./" method="post" enctype="multipart/form-data">
					<h3 id="thread_theme_lbl">Identité</h3>
					<div id="identite_row">
						<img id="thread_theme_img" src="./img/default.png" alt="" />
						<select aria-labelledby="thread_theme_lbl" id="thread_theme" name="thread_theme" onchange="fill_hashtags(this); fill_img(this);">
							<option value="none">-----</option>
							<option value="lego">It's Bricking</option>
							<option value="itsbudding">It's Budding</option>
							<option value="scc">Sporting Culture Club</option>
							<option value="a11y">Cap Accessibilité</option>
						</select>
					</div>
					<hr/>
					<h3 id="famille_id_lbl">Famille de comptes (pour la publication)</h3>
					<select aria-labelledby="famille_id_lbl" id="famille_id" name="famille_id">
						<option value="0">-----</option>
						<?php foreach($familles as $famille): ?>
							<option value="<?php echo (int)$famille["id"]; ?>" <?php echo $famille["id"] == $valeurs->familleId ? "selected" : ""; ?>><?php echo h($famille["nom"]); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="informations">Seuls Mastodon, Pixelfed et Bluesky peuvent être publiés directement pour l'instant.</p>
					<hr/>
					<h3>Habillage</h3>
					<h4 id="thread_habillage_prefixe_lbl">Préfixe</h4>
					<input aria-labelledby="thread_habillage_prefixe_lbl" type="text" id="thread_habillage_prefixe" name="thread_habillage_prefixe" value="<?php echo h($valeurs->prefixe) ?>" />
					<h4 id="thread_habillage_suffixe_lbl">Suffixe</h4>
					<p id="suffixe_informations" class="informations">
						La pagination sera ajouté automatiquement.
					</p>
					<input aria-labelledby="thread_habillage_suffixe_lbl" aria-describedby="suffixe_informations" type="text" id="thread_habillage_suffixe" name="thread_habillage_suffixe" value="<?php echo h($valeurs->suffixe) ?>" />
					<hr/>
					<h3 id="thread_hashtags_lbl">Hashtags</h3>
					<input aria-labelledby="thread_hashtags_lbl" type="text" id="thread_hashtags" name="thread_hashtags" value="<?php echo h(unstringifyHashtags($valeurs->hashtags)); ?>" />
					<hr/>
					<h3 id="thread_content_lbl">Contenu</h3>
					<textarea aria-labelledby="thread_content_lbl" id="thread_content" name="thread_content"><?php echo h($valeurs->content) ?></textarea>
					<p id="compteur_direct" class="informations" aria-live="polite"></p>
					<p class="informations">
						Ce compteur est approximatif (caractères bruts) ; le détail par réseau (habillage, hashtags, pagination) reste calculé après soumission.
					</p>
					<hr/>
					<h3 id="thread_images_lbl">Images</h3>
					<p class="informations">
						Une image par segment sur les réseaux "fil" (Mastodon/BlueSky/Pixelfed) ; toutes les images ensemble en carrousel sur Instagram.
					</p>
					<input aria-labelledby="thread_images_lbl" type="file" id="thread_images" name="images[]" accept="image/png, image/jpeg" multiple />
					<p class="informations">
						Une description par image (optionnelle) peut être saisie sous sa miniature ci-dessous ; non prise en charge par l'API Instagram, seulement Mastodon/Pixelfed/BlueSky.
					</p>
					<ul id="apercu_images" class="apercu_images" aria-live="polite"></ul>
					<hr/>
					<p id="creer_fil_informations" class="informations">
						Les résultats s'afficheront sous le bouton, une fois le formulaire soumis.
					</p>
					<button type="submit" aria-describedby="creer_fil_informations">Créer un fil</button>
				</form>
			</div>


			<div id="resultats" class="tabs">
				<h2 id="lbl_resultats" class="visually-hidden">Résultats</h2>
				
				<div role="tablist" aria-labelledby="lbl_resultats" class="manual">
					<button id="btn_instagram" type="button" role="tab" aria-selected="true" aria-controls="panel_instagram"><h3>Instagram</h3></button>
					<button id="btn_facebook" type="button" role="tab" aria-selected="false" aria-controls="panel_facebook"><h3>Facebook</h3></button>
					<button id="btn_youtube" type="button" role="tab" aria-selected="false" aria-controls="panel_youtube"><h3>Youtube</h3></button>
					<button id="btn_pixelfed" type="button" role="tab" aria-selected="false" aria-controls="panel_pixelfed"><h3>Pixelfed</h3></button>
					<button id="btn_bluesky" type="button" role="tab" aria-selected="false" aria-controls="panel_bluesky"><h3>BlueSky</h3></button>
					<button id="btn_mastodon" type="button" role="tab" aria-selected="false" aria-controls="panel_mastodon"><h3>Mastodon</h3></button>
					<button id="btn_threads" type="button" role="tab" aria-selected="false" aria-controls="panel_threads"><h3>Threads</h3></button>
					<button id="btn_twitter" type="button" role="tab" aria-selected="false" aria-controls="panel_twitter"><h3>Twitter</h3></button>
				</div>
				<div id="panel_twitter" role="tabpanel" aria-labelledby="btn_twitter" class="">
					<?php afficherPanneauFil($valeurs->twitter, "tweet"); ?>
				</div>
				<div id="panel_bluesky" role="tabpanel" aria-labelledby="btn_bluesky" class="is-hidden">
					<?php afficherPanneauFil($valeurs->bluesky, "bluesky", $comptesParReseau["bluesky"] ?? null, $imagesUploadees, $imagesAltUploadees, $valeurs->familleId > 0); ?>
				</div>
				<div id="panel_mastodon" role="tabpanel" aria-labelledby="btn_mastodon" class="is-hidden">
					<?php afficherPanneauFil($valeurs->mastodon, "masto", $comptesParReseau["mastodon"] ?? null, $imagesUploadees, $imagesAltUploadees, $valeurs->familleId > 0); ?>
				</div>
				<div id="panel_threads" role="tabpanel" aria-labelledby="btn_threads" class="is-hidden">
					<?php afficherPanneauFil($valeurs->threads, "threads"); ?>
				</div>
				<div id="panel_instagram" role="tabpanel" aria-labelledby="btn_instagram" class="is-hidden">
					<?php afficherPanneauInstagram($valeurs->instagram, $comptesParReseau["instagram"] ?? null, $imagesUploadees, $valeurs->familleId > 0); ?>
				</div>
				<div id="panel_facebook" role="tabpanel" aria-labelledby="btn_facebook" class="is-hidden">
					<?php afficherPanneauPubli($valeurs->facebook, "facebook"); ?>
				</div>
				<div id="panel_pixelfed" role="tabpanel" aria-labelledby="btn_pixelfed" class="is-hidden">
					<?php afficherPanneauPubli($valeurs->pixelfed, "pixelfed", $comptesParReseau["pixelfed"] ?? null, $imagesUploadees[0] ?? null, $imagesAltUploadees[0] ?? null, $valeurs->familleId > 0); ?>
				</div>
				<div id="panel_youtube" role="tabpanel" aria-labelledby="btn_youtube" class="is-hidden">
					<?php afficherPanneauPubli($valeurs->youtube, "youtube"); ?>
				</div>

			</div>
						
		</main>
		<footer role="contentinfo">
			<a href="https://itsbudding.fr" target="_blank">
				développé par&nbsp;<span lang="en">It's Budding</span> - <?php echo date("Y"); ?><br/>
				<img alt="" src="./img/logo_itsbudding-black.svg">
			</a>
		</footer>
		<script>
			const JETON_CSRF_PUBLICATION = "<?php echo h($jetonCsrfPublication); ?>";
			// selectTheme(document.getElementById('thread_theme'),'<?php echo $valeurs->theme ?>');
		</script>
		<script src="./script.js"></script>
		<script src="./theme-toggle.js"></script>
	</body>
</html>