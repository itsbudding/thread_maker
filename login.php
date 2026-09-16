<?php

require_once __DIR__."/lib/bootstrap.php";

$erreur = "";
$retour = $_GET["retour"] ?? "./index.php";
if(!str_starts_with($retour, "./") && !str_starts_with($retour, "/")){
	$retour = "./index.php";
}

if($_SERVER["REQUEST_METHOD"] === "POST"){
	$motDePasse = $_POST["mot_de_passe"] ?? "";
	if(Auth::connecter($motDePasse)){
		header("Location: ".$retour);
		exit;
	}
	$erreur = "Mot de passe incorrect.";
}

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<?php include __DIR__."/lib/theme_init.php"; ?>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Connexion — Thread Maker</title>
		<link rel="stylesheet" href="./reset.css" />
		<link rel="stylesheet" href="./style.css" />
		<link rel="icon" type="image/png" href="favicon.png">
	</head>
	<body>
		<header role="banner">
			<h1>Thread Maker</h1>
			<button id="bouton_theme" type="button" onclick="basculerTheme()">🌙 Mode sombre</button>
		</header>
		<main role="main">
			<div id="formulaire">
				<h2 id="form_lbl">Connexion</h2>
				<?php if($erreur !== ""): ?>
					<p class="informations" role="alert"><?php echo h($erreur); ?></p>
				<?php endif; ?>
				<form action="./login.php?retour=<?php echo h(urlencode($retour)); ?>" method="post">
					<h3 id="mot_de_passe_lbl">Mot de passe</h3>
					<input aria-labelledby="mot_de_passe_lbl" type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="current-password" required autofocus />
					<hr/>
					<button type="submit">Se connecter</button>
				</form>
			</div>
		</main>
		<script src="./theme-toggle.js"></script>
	</body>
</html>
