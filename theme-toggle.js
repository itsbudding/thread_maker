// Bouton de bascule clair/sombre. Le thème initial (localStorage ou préférence système) est
// déjà appliqué par lib/theme_init.php avant même que ce fichier ne charge ; ce script ne gère
// que le bouton et le changement manuel de thème.

function mettreAJourBoutonTheme(theme){
	var bouton = document.getElementById("bouton_theme");
	if(!bouton) return;

	if(theme === "sombre"){
		bouton.textContent = "☀️ Mode clair";
		bouton.setAttribute("aria-label", "Passer en mode clair");
	}
	else{
		bouton.textContent = "🌙 Mode sombre";
		bouton.setAttribute("aria-label", "Passer en mode sombre");
	}
}

function basculerTheme(){
	var actuel = document.documentElement.getAttribute("data-theme") === "sombre" ? "sombre" : "clair";
	var nouveau = actuel === "sombre" ? "clair" : "sombre";

	document.documentElement.setAttribute("data-theme", nouveau);
	try{
		localStorage.setItem("theme_choisi", nouveau);
	}
	catch(erreur){}

	mettreAJourBoutonTheme(nouveau);
}

window.addEventListener("load", function(){
	var themeActuel = document.documentElement.getAttribute("data-theme") === "sombre" ? "sombre" : "clair";
	mettreAJourBoutonTheme(themeActuel);
	mettreAJourBoutonDensite(densiteActuelle());
});

// Densité des tableaux (.tableau_donnees) : compacte ou confortable (par défaut), retenue en
// localStorage comme le thème. N'a d'effet que sur les pages qui ont un #bouton_densite.
function densiteActuelle(){
	return document.documentElement.getAttribute("data-densite") === "compacte" ? "compacte" : "confortable";
}

function mettreAJourBoutonDensite(densite){
	var bouton = document.getElementById("bouton_densite");
	if(!bouton) return;

	if(densite === "compacte"){
		bouton.textContent = "↕️ Confortable";
		bouton.setAttribute("aria-label", "Passer les tableaux en affichage confortable");
	}
	else{
		bouton.textContent = "↕️ Compact";
		bouton.setAttribute("aria-label", "Passer les tableaux en affichage compact");
	}
}

function basculerDensite(){
	var nouvelle = densiteActuelle() === "compacte" ? "confortable" : "compacte";

	document.documentElement.setAttribute("data-densite", nouvelle);
	try{
		localStorage.setItem("densite_choisie", nouvelle);
	}
	catch(erreur){}

	mettreAJourBoutonDensite(nouvelle);
}
