<?php

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";
require_once __DIR__."/lib/Crypto.php";
require_once __DIR__."/lib/Comptes.php";

Auth::exigerConnexion();

const RESEAUX_DISPONIBLES = ["mastodon", "pixelfed", "bluesky", "instagram"];
const RESEAUX_OAUTH_DISPONIBLES = ["mastodon", "pixelfed"];

$pdo = Database::connexion();
$erreur = "";
$succes = ($_GET["oauth"] ?? "") === "succes" ? "Compte connecté avec succès." : "";

function slugifier(string $nom): string{
	$slug = strtolower(trim($nom));
	$slug = iconv("UTF-8", "ASCII//TRANSLIT", $slug);
	$slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
	return trim($slug, "-");
}

if($_SERVER["REQUEST_METHOD"] === "POST"){

	if(!Auth::verifierCsrf($_POST["jeton_csrf"] ?? null)){
		$erreur = "Session expirée, merci de réessayer.";
	}
	else{
		$action = $_POST["action"] ?? "";

		if($action === "ajouter_famille"){
			$nom = trim($_POST["nom"] ?? "");
			$logo = trim($_POST["logo"] ?? "");
			if($nom === ""){
				$erreur = "Le nom de la famille est obligatoire.";
			}
			else{
				$slug = slugifier($nom);
				$requete = $pdo->prepare("INSERT INTO familles (nom, slug, logo) VALUES (:nom, :slug, :logo)");
				$requete->execute([
					":nom" => $nom,
					":slug" => $slug,
					":logo" => $logo !== "" ? $logo : null,
				]);
				$succes = "Famille « ".$nom." » ajoutée.";
			}
		}
		elseif($action === "supprimer_famille"){
			$id = (int)($_POST["famille_id"] ?? 0);
			$requete = $pdo->prepare("DELETE FROM familles WHERE id = :id");
			$requete->execute([":id" => $id]);
			$succes = "Famille supprimée.";
		}
		elseif($action === "ajouter_compte"){
			$familleId = (int)($_POST["famille_id"] ?? 0);
			$reseau = $_POST["reseau"] ?? "";
			$identifiant = trim($_POST["identifiant"] ?? "");
			$instanceUrl = trim($_POST["instance_url"] ?? "");
			$jeton = trim($_POST["jeton"] ?? "");

			if($familleId <= 0 || !in_array($reseau, RESEAUX_DISPONIBLES, true) || $identifiant === "" || $jeton === ""){
				$erreur = "Tous les champs (famille, réseau, identifiant, jeton) sont obligatoires.";
			}
			else{
				insererCompte($pdo, $familleId, $reseau, $identifiant, $instanceUrl !== "" ? $instanceUrl : null, $jeton);
				$succes = "Compte ".$reseau." ajouté.";
			}
		}
		elseif($action === "supprimer_compte"){
			$id = (int)($_POST["compte_id"] ?? 0);
			$requete = $pdo->prepare("DELETE FROM comptes WHERE id = :id");
			$requete->execute([":id" => $id]);
			$succes = "Compte supprimé.";
		}
		elseif($action === "modifier_compte"){
			$id = (int)($_POST["compte_id"] ?? 0);
			$identifiant = trim($_POST["identifiant"] ?? "");
			$instanceUrl = trim($_POST["instance_url"] ?? "");
			$jeton = trim($_POST["jeton"] ?? "");

			if($id <= 0 || $identifiant === ""){
				$erreur = "L'identifiant est obligatoire.";
			}
			else{
				if($jeton !== ""){
					$requete = $pdo->prepare("
						UPDATE comptes SET identifiant = :identifiant, instance_url = :instance_url, jeton_chiffre = :jeton_chiffre
						WHERE id = :id
					");
					$requete->execute([
						":identifiant" => $identifiant,
						":instance_url" => $instanceUrl !== "" ? $instanceUrl : null,
						":jeton_chiffre" => Crypto::chiffrer($jeton),
						":id" => $id,
					]);
				}
				else{
					// Jeton laissé vide : on ne touche pas au jeton_chiffre existant.
					$requete = $pdo->prepare("
						UPDATE comptes SET identifiant = :identifiant, instance_url = :instance_url
						WHERE id = :id
					");
					$requete->execute([
						":identifiant" => $identifiant,
						":instance_url" => $instanceUrl !== "" ? $instanceUrl : null,
						":id" => $id,
					]);
				}
				$succes = "Compte mis à jour.";
			}
		}
	}
}

$familles = $pdo->query("SELECT * FROM familles ORDER BY nom")->fetchAll();
$comptesParFamille = [];
foreach($pdo->query("SELECT * FROM comptes ORDER BY reseau")->fetchAll() as $compte){
	$comptesParFamille[$compte["famille_id"]][] = $compte;
}

$jetonCsrf = Auth::jetonCsrf();

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<?php include __DIR__."/lib/theme_init.php"; ?>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Comptes — Thread Maker</title>
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
			<?php $pageCourante = "comptes"; include __DIR__."/lib/nav.php"; ?>
			<button id="bouton_densite" type="button" onclick="basculerDensite()">↕️ Compact</button>

			<h2>Familles de comptes</h2>

			<?php if($erreur !== ""): ?>
				<p class="informations" role="alert"><?php echo h($erreur); ?></p>
			<?php endif; ?>
			<?php if($succes !== ""): ?>
				<p class="informations" role="status"><?php echo h($succes); ?></p>
			<?php endif; ?>

			<?php foreach($familles as $famille): ?>
				<div id="formulaire">
					<h3><?php echo h($famille["nom"]); ?> <span class="informations">(<?php echo h($famille["slug"]); ?>)</span></h3>

					<?php if(count($comptesParFamille[$famille["id"]] ?? []) === 0): ?>
						<p class="informations">Aucun compte pour l'instant.</p>
					<?php else: ?>
						<table class="tableau_donnees">
							<thead>
								<tr>
									<th scope="col">Réseau</th>
									<th scope="col">Identifiant</th>
									<th scope="col">Instance</th>
									<th scope="col">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($comptesParFamille[$famille["id"]] ?? [] as $compte): ?>
									<tr>
										<td><?php echo h($compte["reseau"]); ?></td>
										<td><?php echo h($compte["identifiant"]); ?></td>
										<td><?php echo h($compte["instance_url"] ?? "—"); ?></td>
										<td>
											<details>
												<summary>Modifier</summary>
												<form action="./comptes.php" method="post">
													<input type="hidden" name="jeton_csrf" value="<?php echo h($jetonCsrf); ?>" />
													<input type="hidden" name="action" value="modifier_compte" />
													<input type="hidden" name="compte_id" value="<?php echo (int)$compte["id"]; ?>" />
													<label>Identifiant
														<input type="text" name="identifiant" value="<?php echo h($compte["identifiant"]); ?>" required />
													</label>
													<label>URL d'instance
														<input type="text" name="instance_url" value="<?php echo h($compte["instance_url"] ?? ""); ?>" />
													</label>
													<label>Nouveau jeton (laisser vide pour conserver l'actuel)
														<input type="password" name="jeton" autocomplete="off" />
													</label>
													<button type="submit">Enregistrer</button>
												</form>
											</details>
											<form action="./comptes.php" method="post" onsubmit="return confirm('Supprimer ce compte (<?php echo h($compte["reseau"]); ?> — <?php echo h($compte["identifiant"]); ?>) ?');">
												<input type="hidden" name="jeton_csrf" value="<?php echo h($jetonCsrf); ?>" />
												<input type="hidden" name="action" value="supprimer_compte" />
												<input type="hidden" name="compte_id" value="<?php echo (int)$compte["id"]; ?>" />
												<button type="submit" class="copy">Supprimer</button>
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<h4>Se connecter via Mastodon/Pixelfed</h4>
					<p class="informations">Enregistre automatiquement une application sur l'instance donnée (aucun compte développeur nécessaire) et redirige vers sa page d'autorisation ; le compte est ajouté ici une fois la connexion approuvée.</p>
					<form action="./oauth_demarrer.php" method="post">
						<input type="hidden" name="jeton_csrf" value="<?php echo h($jetonCsrf); ?>" />
						<input type="hidden" name="famille_id" value="<?php echo (int)$famille["id"]; ?>" />

						<label>Réseau
							<select name="reseau" required>
								<?php foreach(RESEAUX_OAUTH_DISPONIBLES as $reseau): ?>
									<option value="<?php echo h($reseau); ?>"><?php echo h($reseau); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>URL d'instance
							<input type="text" name="instance_url" placeholder="https://mastodon.social" required />
						</label>
						<button type="submit">Se connecter</button>
					</form>

					<p class="informations">
						Bluesky : pas de connexion en un clic pour l'instant, mais vous pouvez
						<a href="https://bsky.app/settings/app-passwords" target="_blank" rel="noopener noreferrer">créer un « app password »</a>
						sur bsky.app puis le coller ci-dessous dans le champ jeton.<br>
						Instagram : nécessite une App Meta (compte Business + App Review) déjà configurée par vous en dehors de cet outil ; collez le jeton d'accès obtenu dans le champ jeton ci-dessous.
					</p>

					<h4>Ajouter un compte à cette famille (jeton existant)</h4>
					<form action="./comptes.php" method="post">
						<input type="hidden" name="jeton_csrf" value="<?php echo h($jetonCsrf); ?>" />
						<input type="hidden" name="action" value="ajouter_compte" />
						<input type="hidden" name="famille_id" value="<?php echo (int)$famille["id"]; ?>" />

						<label>Réseau
							<select name="reseau" required>
								<?php foreach(RESEAUX_DISPONIBLES as $reseau): ?>
									<option value="<?php echo h($reseau); ?>"><?php echo h($reseau); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>Identifiant (compte / handle)
							<input type="text" name="identifiant" required />
						</label>
						<label>URL d'instance (Mastodon/Pixelfed uniquement)
							<input type="text" name="instance_url" placeholder="https://mastodon.social" />
						</label>
						<label>Jeton d'accès
							<input type="password" name="jeton" autocomplete="off" required />
						</label>
						<button type="submit">Ajouter le compte</button>
					</form>

					<form action="./comptes.php" method="post" onsubmit="return confirm('Supprimer la famille « <?php echo h($famille["nom"]); ?> » et tous ses comptes associés ?');">
						<input type="hidden" name="jeton_csrf" value="<?php echo h($jetonCsrf); ?>" />
						<input type="hidden" name="action" value="supprimer_famille" />
						<input type="hidden" name="famille_id" value="<?php echo (int)$famille["id"]; ?>" />
						<button type="submit" class="copy">Supprimer la famille « <?php echo h($famille["nom"]); ?> »</button>
					</form>
				</div>
				<hr/>
			<?php endforeach; ?>

			<div id="formulaire">
				<h3>Ajouter une famille</h3>
				<form action="./comptes.php" method="post">
					<input type="hidden" name="jeton_csrf" value="<?php echo h($jetonCsrf); ?>" />
					<input type="hidden" name="action" value="ajouter_famille" />
					<label>Nom
						<input type="text" name="nom" required />
					</label>
					<label>Logo (chemin optionnel, ex. ./img/logo.png)
						<input type="text" name="logo" />
					</label>
					<button type="submit">Ajouter la famille</button>
				</form>
			</div>
		</main>
		<script src="./theme-toggle.js"></script>
	</body>
</html>
