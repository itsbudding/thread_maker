
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
	</head>
<?php
	
	define("MAX_LENGTH_TWITTER", 280);
	define("MAX_LENGTH_MASTODON", 500);
	define("MAX_LENGTH_INSTAGRAM", 2200);
	define("PAGINATION", "µµµµµ"); // ££ : numero de tweet, µµ : nombre total de tweets
	// define("MAX_LENGTH_FACEBOOK", 3012);
	// define("MAX_LENGTH_LINKEDIN", 3012);
	
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
			$content_length = strlen($content);
			
						
			// Calcul du nombre de caractères disponibles :
			$habillage = trim(trim($prefixe)." "." ...".PHP_EOL.PHP_EOL.trim($suffixe)." ".PAGINATION.PHP_EOL.PHP_EOL.trim($hashtags));
			$habillage_length = strlen($habillage) - substr_count( $habillage, "\n" );
			// echo strlen(trim(trim($prefixe)." "." ...".trim($suffixe)." ".PAGINATION.trim($hashtags)))."<br/>";
			// echo strlen(trim(trim($prefixe)." "." ...".trim($suffixe)." ".PAGINATION.trim($hashtags)))."<br/>";
			$habillage_length_sec = strlen(" ...".PHP_EOL.PHP_EOL.PAGINATION) - substr_count( " ...".PHP_EOL.PHP_EOL.PAGINATION, "\n" );;
			// echo $habillage_length."<hr/>";
			
			$texteBrut = substr($content, 0, $max_length - $habillage_length);
			$offset = strrpos($texteBrut, " ");
			$texteCoupePropre = substr($texteBrut, 0, $offset);
			
			
			$premier = trim(trim($prefixe)." ".$texteCoupePropre." ...".PHP_EOL.PHP_EOL.trim($suffixe)." ".PAGINATION.PHP_EOL.PHP_EOL.trim($hashtags));
			array_push($arrayReturn, $premier);
			$this->total++;
			
			$content_restant = substr($content, $offset);
			$publi_length = $max_length - $habillage_length_sec;
			
			while(strlen($content_restant) > $publi_length){
				
				$publiBrut = substr($content_restant, 0, $publi_length);
				echo $publiBrut."<br/>";
				$tmpPos = strrpos($publiBrut, " ");
				$publiCoupePropre = substr($publiBrut, 0, $tmpPos);
				echo $publiCoupePropre."<hr/>";
				
				$publiCoupePropre .= " ...".PHP_EOL.PHP_EOL.PAGINATION;
				array_push($arrayReturn, $publiCoupePropre);
				
				$content_restant = substr($content_restant, $tmpPos);
				$this->total++;
				
			}
			
			// récupération du segment restant
			array_push($arrayReturn, $content_restant.PHP_EOL.PHP_EOL.PAGINATION);
			$this->total++;
			
			return $arrayReturn;
		}
	}
	
	class tmForm{
		
		public $theme = "itsbudding";
		// public $title;
		public $prefixe = "";
		public $suffixe = "🧵⬇️";
		// public $intro;
		public $hashtags = "#itsbudding";
		public $content = "";
		public $twitter;
		public $mastodon;
		
		
		function __construct(array $values){
			if(isset($values["thread_theme"])) $this->theme = $values["thread_theme"];
			// if(isset($values["thread_title"])) $this->title = $values["thread_title"];
			if(isset($values["thread_habillage_prefixe"])) $this->prefixe = $values["thread_habillage_prefixe"];
			if(isset($values["thread_habillage_suffixe"])) $this->suffixe = $values["thread_habillage_suffixe"];
			// if(isset($values["thread_intro"])) $this->intro = $values["thread_intro"];
			if(isset($values["thread_hashtags"])) $this->hashtags = stringifyHashtags($values["thread_hashtags"]);			
			if(isset($values["thread_content"])){
				
				$values["thread_content"] = str_replace("\r\n", PHP_EOL, $values["thread_content"]);
				$this->content = $values["thread_content"];
				$this->twitter = new threadRS($values, MAX_LENGTH_TWITTER);
				$this->mastodon = new threadRS($values, MAX_LENGTH_MASTODON);
			}
		}

	}
	
	function stringifyHashtags(string $hashtags){
		
		$stringHashtags = implode(" #", explode(" ", $hashtags));
		$stringHashtags = "#".$stringHashtags;
		
		return $stringHashtags;
	}

	// echo "<pre>"; print_r($_POST); echo "</pre>";
	
	$valeurs = new tmForm($_POST);
	
	// echo "<pre>"; print_r($valeurs->content); echo "</pre>";
	// echo "<hr/>";
	// echo "<pre>$valeurs->content</pre>";

?>
	<body>
		<main>
			<h1>Thread Maker</h1>
			<div id="formulaire">
				<h2 id="form_lbl" class="visually-hidden">Formulaire de saisie</h2>
				<form action="./" method="post">
					<h3 id="thread_theme_lbl">Thème</h3>
					<select aria-labelledby="thread_theme_lbl" id="thread_theme" name="thread_theme" onchange="fill_hashtags(this);">
						<option value="a11y">accessibilité</option>
						<option value="lego">lego</option>
						<option value="itsbudding">itsbudding</option>
						<option value="scc">scc</option>
					</select>
					<hr/>
					<h3>Habillage</h3>
					<h4 id="thread_habillage_prefixe_lbl">Préfixe</h4>
					<input aria-labelledby="thread_habillage_prefixe_lbl" type="text" id="thread_habillage_prefixe" name="thread_habillage_prefixe" value="<?php echo $valeurs->prefixe ?>" />
					<h4 id="thread_habillage_suffixe_lbl">Suffixe</h4>
					<p class="informations">
						La pagination sera ajouté automatiquement.
					</p>
					<input aria-labelledby="thread_habillage_suffixe_lbl" type="text" id="thread_habillage_suffixe" name="thread_habillage_suffixe" value="<?php echo $valeurs->suffixe ?>" />
					<hr/>
					<h3 id="thread_hashtags_lbl">Hashtags</h3>
					<input aria-labelledby="thread_hashtags_lbl" type="text" id="thread_hashtags" name="thread_hashtags" value="<?php str_replace('#', '', $valeurs->hashtags); ?>" />
					<hr/>
					<h3 id="thread_content_lbl">Contenu</h3>
					<!--<textarea aria-labelledby="thread_content_lbl" id="thread_content" name="thread_content"><?php echo str_replace("¤", PHP_EOL, $valeurs->content) ?></textarea>-->
					<textarea aria-labelledby="thread_content_lbl" id="thread_content" name="thread_content"><?php echo $valeurs->content ?></textarea>
					<hr/>
					<p class="informations">
						Les résultats s'afficheront sous le bouton, une fois le formulaire soumis.
					</p>
					<button type="submit">Créer un fil</button>
				</form>
			</div>


			<div id="resultats">
				<h2 class="visually-hidden">Résultats</h2>
				
				<h3>Twitter</h3>
				
				<?php
				
					if(isset($valeurs->twitter)){
						foreach($valeurs->twitter->posts as $i => $tweet){
							$tweet = str_replace(PHP_EOL, "<br/>", $tweet);
							$tweet = str_replace("µµµµµ", ($i+1)."/".$valeurs->twitter->total, $tweet);
							echo "<div class='segment'><p id='tweet_$i'>$tweet</p><button id='btn_tweet_$i' onclick=\"copier('tweet_$i')\">Copier</button></div>";
						}
					}
				
				?>
				
				
			</div>
						
		</main>
		<script src="./script.js"></script>
		<script>
			selectTheme(document.getElementById('thread_theme'),'<?php echo $valeurs->theme ?>');		
		</script>
	</body>
</html>