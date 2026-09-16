<?php

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";

Auth::exigerConnexion();

$pdo = Database::connexion();
$publications = $pdo->query("
	SELECT publications.*, comptes.identifiant AS compte_identifiant, familles.nom AS famille_nom
	FROM publications
	LEFT JOIN comptes ON comptes.id = publications.compte_id
	LEFT JOIN familles ON familles.id = comptes.famille_id
	ORDER BY publications.id DESC
	LIMIT 200
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<?php include __DIR__."/lib/theme_init.php"; ?>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Historique — Thread Maker</title>
		<link rel="stylesheet" href="./reset.css" />
		<link rel="stylesheet" href="./style.css" />
		<link rel="icon" type="image/png" href="favicon.png">
	</head>
	<body>
		<header role="banner">
			<h1>Thread Maker</h1>
		</header>
		<main role="main">
			<?php $pageCourante = "historique"; $afficherBoutonDensite = true; include __DIR__."/lib/nav.php"; ?>

			<h2>Historique des publications</h2>
			<p class="informations">Les 200 dernières tentatives de publication (succès et échecs), les plus récentes en premier.</p>

			<?php if(count($publications) === 0): ?>
				<p class="informations">Aucune publication pour l'instant.</p>
			<?php else: ?>
				<table class="tableau_donnees">
					<thead>
						<tr>
							<th scope="col">Date</th>
							<th scope="col">Famille</th>
							<th scope="col">Réseau</th>
							<th scope="col">Compte</th>
							<th scope="col">Statut</th>
							<th scope="col">Détail</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($publications as $publication): ?>
							<tr>
								<td><?php echo h($publication["cree_le"]); ?></td>
								<td><?php echo h($publication["famille_nom"] ?? "—"); ?></td>
								<td><?php echo h($publication["reseau"]); ?></td>
								<td><?php echo h($publication["compte_identifiant"] ?? "compte supprimé"); ?></td>
								<td>
									<?php if($publication["statut"] === "succes"): ?>
										<span class="badge_statut ok">Succès</span>
									<?php else: ?>
										<span class="badge_statut ko">Échec</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if($publication["statut"] === "succes"): ?>
										<?php echo h($publication["id_externe"] ?? "—"); ?>
									<?php else: ?>
										<?php echo h($publication["message_erreur"] ?? "—"); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</main>
		<script src="./theme-toggle.js"></script>
	</body>
</html>
