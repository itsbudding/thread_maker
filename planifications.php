<?php

require_once __DIR__."/lib/bootstrap.php";
require_once __DIR__."/lib/Database.php";

Auth::exigerConnexion();

$pdo = Database::connexion();
$erreur = "";
$succes = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){
	if(!Auth::verifierCsrf($_POST["jeton_csrf"] ?? null)){
		$erreur = "Session expirée, merci de réessayer.";
	}
	elseif(($_POST["action"] ?? "") === "annuler"){
		$id = (int)($_POST["id"] ?? 0);
		$requete = $pdo->prepare("UPDATE publications_planifiees SET statut = 'annulee' WHERE id = :id AND statut = 'en_attente'");
		$requete->execute([":id" => $id]);
		$succes = "Publication programmée annulée.";
	}
}

$planifications = $pdo->query("
	SELECT publications_planifiees.*, comptes.identifiant AS compte_identifiant, familles.nom AS famille_nom
	FROM publications_planifiees
	LEFT JOIN comptes ON comptes.id = publications_planifiees.compte_id
	LEFT JOIN familles ON familles.id = comptes.famille_id
	ORDER BY date_prevue DESC
	LIMIT 200
")->fetchAll();

$jetonCsrf = Auth::jetonCsrf();
$libellesStatut = [
	"en_attente" => "En attente",
	"publiee" => "Publiée",
	"erreur" => "Échec",
	"annulee" => "Annulée",
];

?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Publications programmées — Thread Maker</title>
		<link rel="stylesheet" href="./reset.css" />
		<link rel="stylesheet" href="./style.css" />
		<link rel="icon" type="image/png" href="favicon.png">
	</head>
	<body>
		<header role="banner">
			<h1>Thread Maker</h1>
		</header>
		<main role="main">
			<p><a href="./index.php">← Retour au générateur</a> · <a href="./comptes.php">Gérer les comptes</a> · <a href="./historique.php">Historique</a> · <a href="./logout.php">Se déconnecter</a></p>

			<h2>Publications programmées</h2>
			<p class="informations">
				Une tâche planifiée système doit exécuter <code>cron_publier_planifie.php</code> régulièrement pour que les publications ci-dessous partent effectivement à l'heure prévue.
			</p>

			<?php if($erreur !== ""): ?>
				<p class="informations" role="alert"><?php echo h($erreur); ?></p>
			<?php endif; ?>
			<?php if($succes !== ""): ?>
				<p class="informations" role="status"><?php echo h($succes); ?></p>
			<?php endif; ?>

			<?php if(count($planifications) === 0): ?>
				<p class="informations">Aucune publication programmée pour l'instant.</p>
			<?php else: ?>
				<table class="tableau_donnees">
					<thead>
						<tr>
							<th scope="col">Prévue le</th>
							<th scope="col">Famille</th>
							<th scope="col">Réseau</th>
							<th scope="col">Compte</th>
							<th scope="col">Texte</th>
							<th scope="col">Statut</th>
							<th scope="col">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($planifications as $planification): ?>
							<tr>
								<td><?php echo h($planification["date_prevue"]); ?></td>
								<td><?php echo h($planification["famille_nom"] ?? "—"); ?></td>
								<td><?php echo h($planification["reseau"]); ?></td>
								<td><?php echo h($planification["compte_identifiant"] ?? "compte supprimé"); ?></td>
								<td><?php echo h(mb_strimwidth($planification["texte"], 0, 80, "…")); ?></td>
								<td>
									<?php $statut = $planification["statut"]; ?>
									<span class="badge_statut <?php echo match($statut){ "publiee" => "ok", "erreur" => "ko", default => "attente" }; ?>">
										<?php echo h($libellesStatut[$statut] ?? $statut); ?>
									</span>
									<?php if($statut === "erreur" && $planification["message_erreur"]): ?>
										<div class="informations"><?php echo h($planification["message_erreur"]); ?></div>
									<?php endif; ?>
								</td>
								<td>
									<?php if($statut === "en_attente"): ?>
										<form action="./planifications.php" method="post">
											<input type="hidden" name="jeton_csrf" value="<?php echo h($jetonCsrf); ?>" />
											<input type="hidden" name="action" value="annuler" />
											<input type="hidden" name="id" value="<?php echo (int)$planification["id"]; ?>" />
											<button type="submit" class="copy">Annuler</button>
										</form>
									<?php else: ?>
										—
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</main>
	</body>
</html>
