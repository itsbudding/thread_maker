<?php

// Affiche la navigation commune à toutes les pages protégées, avec la page courante mise en
// évidence, ainsi que les boutons de bascule thème/densité sur la même ligne. La page incluante
// doit définir $pageCourante avant d'inclure ce fichier, avec l'une des valeurs : "index",
// "comptes", "historique", "planifications". Elle peut aussi définir $afficherBoutonDensite = true
// pour les pages qui ont un tableau de données (comptes, historique, planifications).
function lienNav(string $cible, string $pageCourante, string $href, string $texte): string{
	if($cible === $pageCourante){
		return "<strong aria-current=\"page\">".h($texte)."</strong>";
	}
	return "<a href=\"".h($href)."\">".h($texte)."</a>";
}

$afficherBoutonDensite = $afficherBoutonDensite ?? false;

?>
<nav aria-label="Navigation principale" class="barre_navigation">
	<?php echo lienNav("index", $pageCourante, "./index.php", "Générateur"); ?> ·
	<?php echo lienNav("comptes", $pageCourante, "./comptes.php", "Gérer les comptes"); ?> ·
	<?php echo lienNav("historique", $pageCourante, "./historique.php", "Historique"); ?> ·
	<?php echo lienNav("planifications", $pageCourante, "./planifications.php", "Publications programmées"); ?> ·
	<a href="./logout.php" onclick="return confirm('Se déconnecter de Thread Maker ?');">Se déconnecter</a>
	<span class="barre_navigation_bascules">
		<button id="bouton_theme" type="button" onclick="basculerTheme()">🌙 Mode sombre</button>
		<?php if($afficherBoutonDensite): ?>
			<button id="bouton_densite" type="button" onclick="basculerDensite()">↕️ Compact</button>
		<?php endif; ?>
	</span>
</nav>
