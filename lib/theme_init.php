<script>
	// Applique le thème et la densité de tableau choisis (ou les valeurs par défaut) avant le
	// premier rendu, pour éviter un flash de la mauvaise valeur. Doit rester dans le <head>,
	// avant les feuilles de style. Les boutons de bascule sont dans theme-toggle.js.
	(function(){
		try{
			var themeChoisi = localStorage.getItem("theme_choisi");
			var theme = (themeChoisi === "clair" || themeChoisi === "sombre")
				? themeChoisi
				: (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "sombre" : "clair");
			document.documentElement.setAttribute("data-theme", theme);

			var densiteChoisie = localStorage.getItem("densite_choisie");
			if(densiteChoisie === "compacte"){
				document.documentElement.setAttribute("data-densite", "compacte");
			}
		}
		catch(erreur){}
	})();
</script>
