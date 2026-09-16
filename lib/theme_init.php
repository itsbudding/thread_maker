<script>
	// Applique le thème choisi (ou celui du système, à défaut) avant le premier rendu,
	// pour éviter un flash de la mauvaise couleur. Doit rester dans le <head>, avant les
	// feuilles de style. Le bouton de bascule est dans theme-toggle.js.
	(function(){
		try{
			var choisi = localStorage.getItem("theme_choisi");
			var theme = (choisi === "clair" || choisi === "sombre")
				? choisi
				: (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "sombre" : "clair");
			document.documentElement.setAttribute("data-theme", theme);
		}
		catch(erreur){}
	})();
</script>
