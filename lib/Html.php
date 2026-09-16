<?php

// Échappement HTML systématique de toute donnée utilisateur avant affichage.
function h(?string $valeur){
	return htmlspecialchars($valeur ?? "", ENT_QUOTES, "UTF-8");
}
