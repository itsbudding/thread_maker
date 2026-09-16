// LISTE DES HASHTAGS THEMATIQUES
const arrayHashtags = [];
	arrayHashtags["none"] = "";
	arrayHashtags["a11y"] = "accessibilite handicap a11y accessibilitenumerique digitalaccessibility a11y inclusivedesign conceptionaccessible webaccessible accessforall uxaccessible handicapetnumerique designinclusif";
	arrayHashtags["lego"] = "lego legofrance afol legogram instalego legofan legoafol legophotography legomoc legominifigures brickcentral toyphotography legolife legoideas legobuild bricks legocommunity legobuilder legocreation legolove legobricks";
	arrayHashtags["itsbudding"] = "itsbudding";
	arrayHashtags["scc"] = "sport culture sportetculture culturedusport sportaucinema filmsdesport litteraturesportive sportetsociete heritagesportif artetsport espritsportif documentairesportif";

// LISTE DES LOGOS
const arrayLogos = [];
	arrayLogos["none"] = "default.png";
	arrayLogos["a11y"] = "default.png";
	arrayLogos["lego"] = "logo_itsbricking.png";
	arrayLogos["itsbudding"] = "logo_itsbudding.png";
	arrayLogos["scc"] = "logo_scc.png";

// par défaut, affichage des mots clés "itsbudding"
// document.getElementById("thread_hashtags").value = arrayHashtags["itsbudding"];

function fill_hashtags(element){
	document.getElementById("thread_hashtags").value = arrayHashtags[element.value];
}
function fill_img(element){
	document.getElementById("thread_theme_img").src = "./img/" + arrayLogos[element.value];
}

function selectTheme(element, valeur){
	// var el = element;
    // for (var idx=0;idx<el.options.length;idx++)
    // {
        // if (valeur == el.options[idx].value)
        // {
              // el.options[idx].selected=true;
              // break;
        // }
    // }
	element.value = valeur;
	fill_hashtags(element);
}

function copier(id){
	navigator.clipboard.writeText(document.getElementById(id).innerText);
	document.getElementById("btn_" + id).innerText = "✓ Copié !";
	document.getElementById("btn_" + id).classList.add("checked");
}

// Affiche un message discret juste après le bouton concerné, plutôt qu'une alerte bloquante.
function afficherNotificationPublication(bouton, message, succes){
	let notification = bouton.nextElementSibling;
	if(!notification || !notification.classList.contains("notification_publication")){
		notification = document.createElement("span");
		notification.className = "notification_publication";
		bouton.insertAdjacentElement("afterend", notification);
	}
	notification.textContent = message;
	notification.classList.toggle("erreur", !succes);
	notification.classList.toggle("succes", succes);
}

async function envoyerPublication(idTexte, corps){
	const bouton = document.getElementById("btn_publier_" + idTexte);
	const libelleInitial = bouton.innerText;
	bouton.disabled = true;
	bouton.innerText = "Publication…";

	try{
		const reponse = await fetch("./publier.php", { method: "POST", body: corps });
		const resultat = await reponse.json();

		if(resultat.succes){
			bouton.innerText = "✓ Publié !";
			bouton.classList.add("checked");
		}
		else{
			bouton.innerText = libelleInitial;
			bouton.disabled = false;
			afficherNotificationPublication(bouton, resultat.erreur || "Erreur inconnue.", false);
		}
	}
	catch(erreur){
		bouton.innerText = libelleInitial;
		bouton.disabled = false;
		afficherNotificationPublication(bouton, "Erreur réseau : " + erreur.message, false);
	}
}

async function publier(idTexte, compteId, imageNom, imageAlt){
	const texte = document.getElementById(idTexte).innerText;
	const corps = new URLSearchParams();
	corps.set("jeton_csrf", JETON_CSRF_PUBLICATION);
	corps.set("compte_id", compteId);
	corps.set("texte", texte);
	if(imageNom){
		corps.set("image_nom", imageNom);
	}
	if(imageAlt){
		corps.set("image_alt", imageAlt);
	}
	await envoyerPublication(idTexte, corps);
}

async function publierInstagram(idTexte, compteId, images){
	const texte = document.getElementById(idTexte).innerText;
	const corps = new URLSearchParams();
	corps.set("jeton_csrf", JETON_CSRF_PUBLICATION);
	corps.set("compte_id", compteId);
	corps.set("texte", texte);
	images.forEach((nom) => corps.append("images[]", nom));
	await envoyerPublication(idTexte, corps);
}

async function envoyerPlanification(idTexte, corps){
	const bouton = document.getElementById("btn_programmer_" + idTexte);
	const libelleInitial = bouton.innerText;
	bouton.disabled = true;
	bouton.innerText = "Programmation…";

	try{
		const reponse = await fetch("./planifier.php", { method: "POST", body: corps });
		const resultat = await reponse.json();

		if(resultat.succes){
			bouton.innerText = "✓ Programmé !";
			bouton.classList.add("checked");
		}
		else{
			bouton.innerText = libelleInitial;
			bouton.disabled = false;
			alert("Échec de la programmation : " + (resultat.erreur || "erreur inconnue."));
		}
	}
	catch(erreur){
		bouton.innerText = libelleInitial;
		bouton.disabled = false;
		alert("Erreur réseau lors de la programmation : " + erreur.message);
	}
}

function datePrevueDepuisChamp(idTexte){
	const champ = document.getElementById("date_" + idTexte);
	return champ ? champ.value : "";
}

async function programmer(idTexte, compteId, imageNom, imageAlt){
	const datePrevue = datePrevueDepuisChamp(idTexte);
	if(!datePrevue){
		alert("Choisissez une date/heure avant de programmer.");
		return;
	}
	const texte = document.getElementById(idTexte).innerText;
	const corps = new URLSearchParams();
	corps.set("jeton_csrf", JETON_CSRF_PUBLICATION);
	corps.set("compte_id", compteId);
	corps.set("texte", texte);
	corps.set("date_prevue", datePrevue);
	if(imageNom){
		corps.set("image_nom", imageNom);
	}
	if(imageAlt){
		corps.set("image_alt", imageAlt);
	}
	await envoyerPlanification(idTexte, corps);
}

async function programmerInstagram(idTexte, compteId, images){
	const datePrevue = datePrevueDepuisChamp(idTexte);
	if(!datePrevue){
		alert("Choisissez une date/heure avant de programmer.");
		return;
	}
	const texte = document.getElementById(idTexte).innerText;
	const corps = new URLSearchParams();
	corps.set("jeton_csrf", JETON_CSRF_PUBLICATION);
	corps.set("compte_id", compteId);
	corps.set("texte", texte);
	corps.set("date_prevue", datePrevue);
	images.forEach((nom) => corps.append("images[]", nom));
	await envoyerPlanification(idTexte, corps);
}

function controle_length(element){
	// lister les div de controle_lenghth
	// pour chaque div, faire le calcul du reste à saisir
	// si ecart > 100, rien (affichage noir)
	// si ecart < 100 > 50, affichage orange
	// si ecart < 50, affichage rouge
	// si ecart < 0, background tab et panel rouge
	
	// console.log("longueur : " + element.data-value-now);
	// console.log("longueur");
	var listControlLength = [];
	
	listControlLength = document.getElementsByClassName("ctrl_length");
	console.log(listControlLength);

	///Parcours d'un HTMLCollection
	for(let ctrl_length_div of listControlLength){
		// console.log(ctrl_length_div);
		console.log("longueur : " + ctrl_length_div.dataset.valuenow);
		console.log("max : " + ctrl_length_div.dataset.valuemax);
		console.log(ctrl_length_div.childNodes);

		let now = ctrl_length_div.dataset.valuenow;
		let max = ctrl_length_div.dataset.valuemax;

		let ecart = max - now;

		// on repart d'un état propre à chaque recalcul, sinon les classes s'accumulent
		ctrl_length_div.classList.remove("ok", "warning", "ko", "alert");

		if(ecart < 0){
			ctrl_length_div.classList.add("alert");
		}
		else if(ecart < 50){
			ctrl_length_div.classList.add("ko");
		}
		else if(ecart < 100){
			ctrl_length_div.classList.add("warning");
		}
		else{
			ctrl_length_div.classList.add("ok");
		}

	}
	
}

/////////////////////////////////////////////////////////////////////////////////// ONGLETS W3C

class TabsManual {
  constructor(groupNode) {
    this.tablistNode = groupNode;

    this.tabs = [];

    this.firstTab = null;
    this.lastTab = null;

    this.tabs = Array.from(this.tablistNode.querySelectorAll('[role=tab]'));
    this.tabpanels = [];

    for (var i = 0; i < this.tabs.length; i += 1) {
      var tab = this.tabs[i];
      var tabpanel = document.getElementById(tab.getAttribute('aria-controls'));

      tab.tabIndex = -1;
      tab.setAttribute('aria-selected', 'false');
      this.tabpanels.push(tabpanel);

      tab.addEventListener('keydown', this.onKeydown.bind(this));
      tab.addEventListener('click', this.onClick.bind(this));

      if (!this.firstTab) {
        this.firstTab = tab;
      }
      this.lastTab = tab;
    }

    this.setSelectedTab(this.firstTab);
  }

  setSelectedTab(currentTab) {
    for (var i = 0; i < this.tabs.length; i += 1) {
      var tab = this.tabs[i];
      if (currentTab === tab) {
        tab.setAttribute('aria-selected', 'true');
        tab.removeAttribute('tabindex');
        this.tabpanels[i].classList.remove('is-hidden');
      } else {
        tab.setAttribute('aria-selected', 'false');
        tab.tabIndex = -1;
        this.tabpanels[i].classList.add('is-hidden');
      }
    }
  }

  moveFocusToTab(currentTab) {
    currentTab.focus();
  }

  moveFocusToPreviousTab(currentTab) {
    var index;

    if (currentTab === this.firstTab) {
      this.moveFocusToTab(this.lastTab);
    } else {
      index = this.tabs.indexOf(currentTab);
      this.moveFocusToTab(this.tabs[index - 1]);
    }
  }

  moveFocusToNextTab(currentTab) {
    var index;

    if (currentTab === this.lastTab) {
      this.moveFocusToTab(this.firstTab);
    } else {
      index = this.tabs.indexOf(currentTab);
      this.moveFocusToTab(this.tabs[index + 1]);
    }
  }

  /* EVENT HANDLERS */

  onKeydown(event) {
    var tgt = event.currentTarget,
      flag = false;

    switch (event.key) {
      case 'ArrowLeft':
        this.moveFocusToPreviousTab(tgt);
        flag = true;
        break;

      case 'ArrowRight':
        this.moveFocusToNextTab(tgt);
        flag = true;
        break;

      case 'Home':
        this.moveFocusToTab(this.firstTab);
        flag = true;
        break;

      case 'End':
        this.moveFocusToTab(this.lastTab);
        flag = true;
        break;

      default:
        break;
    }

    if (flag) {
      event.stopPropagation();
      event.preventDefault();
    }
  }

  // Since this example uses buttons for the tabs, the click onr also is activated
  // with the space and enter keys
  onClick(event) {
    this.setSelectedTab(event.currentTarget);
  }
}

// Initialize tablist

window.addEventListener('load', function () {
  var tablists = document.querySelectorAll('[role=tablist].manual');
  for (var i = 0; i < tablists.length; i++) {
    new TabsManual(tablists[i]);
  }

  controle_length();

  var champImages = document.getElementById("thread_images");
  if(champImages){
    champImages.addEventListener("change", function(){ afficherApercuImages(champImages); });
  }

  var formulaireGeneration = document.querySelector("#formulaire form");
  if(formulaireGeneration){
    formulaireGeneration.addEventListener("submit", function(){
      var boutonSoumettre = formulaireGeneration.querySelector('button[type="submit"]');
      if(boutonSoumettre){
        boutonSoumettre.disabled = true;
        boutonSoumettre.innerText = "Génération…";
      }
    });
  }

  var champContenu = document.getElementById("thread_content");
  if(champContenu){
    var afficherCompteurDirect = function(){
      var compteur = document.getElementById("compteur_direct");
      if(!compteur) return;
      // Array.from itère par point de code Unicode, plus proche du ressenti utilisateur
      // que .length (qui compte les émojis composés deux fois) sans viser l'exactitude
      // grapheme_strlen() de PHP, calculée après soumission.
      compteur.textContent = Array.from(champContenu.value).length + " caractères (brut)";
    };
    champContenu.addEventListener("input", afficherCompteurDirect);
    afficherCompteurDirect();
  }

});

// Génère des miniatures pour les images choisies, numérotées dans leur ordre d'upload
// (l'ordre qui déterminera ensuite quelle image va sur quel segment / le carrousel Instagram).
function afficherApercuImages(champFichier){
  var conteneur = document.getElementById("apercu_images");
  if(!conteneur) return;

  conteneur.innerHTML = "";

  Array.from(champFichier.files).forEach(function(fichier, index){
    var li = document.createElement("li");

    var img = document.createElement("img");
    img.src = URL.createObjectURL(fichier);
    img.alt = "";
    img.onload = function(){ URL.revokeObjectURL(img.src); };

    var legende = document.createElement("span");
    legende.textContent = "Image " + (index + 1);

    li.appendChild(img);
    li.appendChild(legende);
    conteneur.appendChild(li);
  });
}