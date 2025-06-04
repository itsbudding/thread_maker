// LISTE DES HASHTAGS THEMATIQUES
const arrayHashtags = [];
	arrayHashtags["a11y"] = "accessibilite handicap a11y itsbudding";
	arrayHashtags["lego"] = "lego legofrance legoideas frenchafol legoaddict legocollection afolcommunity legofan bricknetwork legoafol legostagram instalego LegoLife LegoBricks afolclub brickfan legobuilder legocollector legolover legocommunity";
	arrayHashtags["itsbudding"] = "itsbudding";
	arrayHashtags["scc"] = "sport culture itsbudding";
	
// par défaut, affichage des mots clés "itsbudding"
// document.getElementById("thread_hashtags").value = arrayHashtags["itsbudding"];

function fill_hashtags(element){
	document.getElementById("thread_hashtags").value = arrayHashtags[element.value];
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
		
		switch(true){
			case (ecart > 100): ctrl_length_div.classList.add("ok"); break;
			case (ecart < 100 && ecart > 50): ctrl_length_div.classList.add("warning"); break;
			case (ecart < 0): ctrl_length_div.classList.add("alert"); break;
			case (ecart < 50): ctrl_length_div.classList.add("ko"); break;
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
  
});