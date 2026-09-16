<!DOCTYPE html>
<html lang="fr" dir="ltr">
	<head>
		<meta charset="UTF-8">
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
	define("MAX_LENGTH_LINKEDIN", 3000);
	
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
		public $prefixe = "";
		public $suffixe = "🧵⬇️";
		// public $hashtags = "#itsbudding";
		public $hashtags = "";
		public $content = "";
		
		public $twitter;
		public $mastodon;
		public $threads;
		public $bluesky;
		public $instagram;
		public $facebook;
		public $youtube;
		// public $linkedin;
				
		function __construct(array $values){
			if(isset($values["thread_theme"])) $this->theme = $values["thread_theme"];
			if(isset($values["thread_habillage_prefixe"])) $this->prefixe = $values["thread_habillage_prefixe"];
			if(isset($values["thread_habillage_suffixe"])) $this->suffixe = $values["thread_habillage_suffixe"];
			if(isset($values["thread_hashtags"])) $this->hashtags = stringifyHashtags($values["thread_hashtags"]);			
			if(isset($values["thread_content"])){
				
				$this->content = $values["thread_content"];
				
				$this->twitter = new threadRS($values, MAX_LENGTH_TWITTER);
				$this->mastodon = new threadRS($values, MAX_LENGTH_MASTODON);
				$this->threads = new threadRS($values, MAX_LENGTH_THREADS);
				$this->bluesky = new threadRS($values, MAX_LENGTH_BLUESKY);
				$this->instagram = new publiRS($values, MAX_LENGTH_INSTAGRAM);
				$this->facebook = new publiRS($values, MAX_LENGTH_FACEBOOK);
				$this->youtube = new publiRS($values, MAX_LENGTH_YOUTUBE);
				$this->pixelfed = new publiRS($values, MAX_LENGTH_PIXELFED);
				// $this->linkedin = new publiRS($values, MAX_LENGTH_LINKEDIN);
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

	// echo "<pre>"; print_r($_POST); echo "</pre>";
	
	$valeurs = new tmForm($_POST);
	
	// echo "<pre>POST :<br/>"; print_r($valeurs); echo "</pre>";
	// echo "<hr/>";
	// echo "<pre>$valeurs->content</pre>";

?>
	<body>
		<header role="banner">
			<h1>Thread Maker</h1>
		</header>
		<main role="main">
			<div id="formulaire">
				<h2 id="form_lbl" class="visually-hidden">Formulaire de saisie</h2>
				<form action="./" method="post">
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
					<h3>Habillage</h3>
					<h4 id="thread_habillage_prefixe_lbl">Préfixe</h4>
					<input aria-labelledby="thread_habillage_prefixe_lbl" type="text" id="thread_habillage_prefixe" name="thread_habillage_prefixe" value="<?php echo $valeurs->prefixe ?>" />
					<h4 id="thread_habillage_suffixe_lbl">Suffixe</h4>
					<p id="suffixe_informations" class="informations">
						La pagination sera ajouté automatiquement.
					</p>
					<input aria-labelledby="thread_habillage_suffixe_lbl" aria-describedby="suffixe_informations" type="text" id="thread_habillage_suffixe" name="thread_habillage_suffixe" value="<?php echo $valeurs->suffixe ?>" />
					<hr/>
					<h3 id="thread_hashtags_lbl">Hashtags</h3>
					<input aria-labelledby="thread_hashtags_lbl" type="text" id="thread_hashtags" name="thread_hashtags" value="<?php echo unstringifyHashtags($valeurs->hashtags); ?>" />
					<hr/>
					<h3 id="thread_content_lbl">Contenu</h3>
					<textarea aria-labelledby="thread_content_lbl" id="thread_content" name="thread_content"><?php echo $valeurs->content ?></textarea>
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
					<button id="btn_facebook" type="button" role="tab" aria-selected="false" aria-controls="panel_youtube"><h3>Youtube</h3></button>
					<button id="btn_pixelfed" type="button" role="tab" aria-selected="false" aria-controls="panel_pixelfed"><h3>Pixelfed</h3></button>
					<button id="btn_bluesky" type="button" role="tab" aria-selected="false" aria-controls="panel_bluesky"><h3>BlueSky</h3></button>
					<button id="btn_mastodon" type="button" role="tab" aria-selected="false" aria-controls="panel_mastodon"><h3>Mastodon</h3></button>
					<button id="btn_threads" type="button" role="tab" aria-selected="false" aria-controls="panel_threads"><h3>Threads</h3></button>
					<button id="btn_twitter" type="button" role="tab" aria-selected="false" aria-controls="panel_twitter"><h3>Twitter</h3></button>
					<!--<button id="btn_linkedin" type="button" role="tab" aria-selected="false" aria-controls="panel_linkedin"><h3>Linkedin</h3></button>-->
				</div>
				<div id="panel_twitter" role="tabpanel" aria-labelledby="btn_twitter" class="">
					<?php
					
						if(isset($valeurs->twitter)){
							foreach($valeurs->twitter->posts as $i => $post){
								$post = str_replace("¤", "<br/>", $post);
								// $post = str_replace("µµµµµ", ($i+1)."/".$valeurs->twitter->total, $post);
								$post = str_replace(PAGINATION, ($i+1)."/".$valeurs->twitter->total, $post);
								echo "<div class='segment'><p id='tweet_$i'>$post</p><button id='btn_tweet_$i' onclick=\"copier('tweet_$i')\" class=\"copy\">Copier</button></div>";
							}
						}
					
					?>				
				</div>
				<div id="panel_bluesky" role="tabpanel" aria-labelledby="btn_bluesky" class="is-hidden">
					<?php
					
						if(isset($valeurs->bluesky)){
							foreach($valeurs->bluesky->posts as $i => $post){
								$post = str_replace("¤", "<br/>", $post);
								// $post = str_replace("µµµµµ", ($i+1)."/".$valeurs->bluesky->total, $post);
								$post = str_replace(PAGINATION, ($i+1)."/".$valeurs->bluesky->total, $post);
								echo "<div class='segment'><p id='bluesky_$i'>$post</p><button id='btn_bluesky_$i' onclick=\"copier('bluesky_$i')\" class=\"copy\">Copier</button></div>";
							}
						}
					
					?>
				</div>
				<div id="panel_mastodon" role="tabpanel" aria-labelledby="btn_mastodon" class="is-hidden">
					<?php
					
						if(isset($valeurs->mastodon)){
							foreach($valeurs->mastodon->posts as $i => $post){
								$post = str_replace("¤", "<br/>", $post);
								// $post = str_replace("µµµµµ", ($i+1)."/".$valeurs->mastodon->total, $post);
								$post = str_replace(PAGINATION, ($i+1)."/".$valeurs->mastodon->total, $post);
								echo "<div class='segment'><p id='masto_$i'>$post</p><button id='btn_masto_$i' onclick=\"copier('masto_$i')\" class=\"copy\">Copier</button></div>";
							}
						}
					
					?>
				</div>
				<div id="panel_threads" role="tabpanel" aria-labelledby="btn_threads" class="is-hidden">
					<?php
					
						if(isset($valeurs->threads)){
							foreach($valeurs->threads->posts as $i => $post){
								$post = str_replace("¤", "<br/>", $post);
								// $post = str_replace("µµµµµ", ($i+1)."/".$valeurs->threads->total, $post);
								$post = str_replace(PAGINATION, ($i+1)."/".$valeurs->threads->total, $post);
								echo "<div class='segment'><p id='threads_$i'>$post</p><button id='btn_threads_$i' onclick=\"copier('threads_$i')\" class=\"copy\">Copier</button></div>";
							}
						}
					
					?>
				</div>
				<div id="panel_instagram" role="tabpanel" aria-labelledby="btn_instagram" class="is-hidden">
					<?php
					
						if(isset($valeurs->instagram)){
								echo "<div class=\"ctrl_length\" data-valuemax=\"".$valeurs->instagram->maxlength."\" data-valuenow=\"".$valeurs->instagram->length."\">";
								
								echo "<span class='length'>".$valeurs->instagram->length."</span> / ".$valeurs->instagram->maxlength." caractères</div>";
							
								$post = str_replace("¤", "<br/>", $valeurs->instagram->content);
								echo "<div class='segment'><p id='insta_$i'>$post</p><button id='btn_insta_$i' onclick=\"copier('insta_$i')\" class=\"copy\">Copier</button></div>";
						}
					
					?>
				</div>
				<div id="panel_facebook" role="tabpanel" aria-labelledby="btn_facebook" class="is-hidden">
					<?php
					
						if(isset($valeurs->facebook)){
								echo "<div class=\"ctrl_length\" data-valuemax=\"".$valeurs->facebook->maxlength."\" data-valuenow=\"".$valeurs->facebook->length."\">";
								
								echo "<span class='length'>".$valeurs->facebook->length."</span> / ".$valeurs->facebook->maxlength." caractères</div>";
							
								$post = str_replace("¤", "<br/>", $valeurs->facebook->content);
								echo "<div class='segment'><p id='facebook_$i'>$post</p><button id='btn_facebook_$i' onclick=\"copier('facebook_$i')\" class=\"copy\">Copier</button></div>";
						}
					
					?>
				</div>
				<div id="panel_pixelfed" role="tabpanel" aria-labelledby="btn_pixelfed" class="is-hidden">
					<?php
					
						if(isset($valeurs->pixelfed)){
								echo "<div class=\"ctrl_length\" data-valuemax=\"".$valeurs->pixelfed->maxlength."\" data-valuenow=\"".$valeurs->pixelfed->length."\">";
								
								echo "<span class='length'>".$valeurs->pixelfed->length."</span> / ".$valeurs->pixelfed->maxlength." caractères</div>";
							
								$post = str_replace("¤", "<br/>", $valeurs->pixelfed->content);
								echo "<div class='segment'><p id='pixelfed_$i'>$post</p><button id='btn_pixelfed_$i' onclick=\"copier('pixelfed_$i')\" class=\"copy\">Copier</button></div>";
						}
					
					?>
				</div>
				<div id="panel_youtube" role="tabpanel" aria-labelledby="btn_pixelfed" class="is-hidden">
					<?php
					
						if(isset($valeurs->pixelfed)){
								echo "<div class=\"ctrl_length\" data-valuemax=\"".$valeurs->youtube->maxlength."\" data-valuenow=\"".$valeurs->youtube->length."\">";
								
								echo "<span class='length'>".$valeurs->youtube->length."</span> / ".$valeurs->youtube->maxlength." caractères</div>";
							
								$post = str_replace("¤", "<br/>", $valeurs->youtube->content);
								echo "<div class='segment'><p id='youtube_$i'>$post</p><button id='btn_youtube_$i' onclick=\"copier('youtube_$i')\" class=\"copy\">Copier</button></div>";
						}
					
					?>
				</div>
				<!--
				<div id="panel_linkedin" role="tabpanel" aria-labelledby="btn_linkedin" class="is-hidden">
					<?php
					
						if(isset($valeurs->linkedin)){
								echo "<div class=\"ctrl_length\" data-valuemax=\"".$valeurs->linkedin->maxlength."\" data-valuenow=\"".$valeurs->linkedin->length."\">";
								
								echo "<span class='length'>".$valeurs->linkedin->length."</span> / ".$valeurs->linkedin->maxlength." caractères</div>";
							
								$post = str_replace("¤", "<br/>", $valeurs->linkedin->content);
								echo "<div class='segment'><p id='linkedin_$i'>$post</p><button id='btn_linkedin_$i' onclick=\"copier('linkedin_$i')\" class=\"copy\">Copier</button></div>";
						}
					
					?>
				</div>
				-->
				
			</div>
						
		</main>
		<footer role="contentinfo">
			<a href="https://itsbudding.fr" target="_blank">
				développé par&nbsp;<span lang="en">It's Budding</span> - <?php echo date("Y"); ?><br/>
				<img alt="" src="./img/logo_itsbudding-black.svg">
			</a>
		</footer>
		<script src="./script.js"></script>
		<script>
			// selectTheme(document.getElementById('thread_theme'),'<?php echo $valeurs->theme ?>');		
		</script>
	</body>
</html>