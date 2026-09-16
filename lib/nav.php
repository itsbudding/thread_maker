<?php

// Affiche la navigation commune à toutes les pages protégées, avec la page courante mise en
// évidence. La page incluante doit définir $pageCourante avant d'inclure ce fichier, avec l'une
// des valeurs : "index", "comptes", "historique", "planifications".
function lienNav(string $cible, string $pageCourante, string $href, string $texte): string{
	if($cible === $pageCourante){
		return "<strong aria-current=\"page\">".h($texte)."</strong>";
	}
	return "<a href=\"".h($href)."\">".h($texte)."</a>";
}

?>
<nav aria-label="Navigation principale">
	<p>
		<?php echo lienNav("index", $pageCourante, "./index.php", "Générateur"); ?> ·
		<?php echo lienNav("comptes", $pageCourante, "./comptes.php", "Gérer les comptes"); ?> ·
		<?php echo lienNav("historique", $pageCourante, "./historique.php", "Historique"); ?> ·
		<?php echo lienNav("planifications", $pageCourante, "./planifications.php", "Publications programmées"); ?> ·
		<a href="./logout.php" onclick="return confirm('Se déconnecter de Thread Maker ?');">Se déconnecter</a>
	</p>
</nav>
