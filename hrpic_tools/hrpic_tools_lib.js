function getXhr(){
	var xhr = null; 
	
	if(window.XMLHttpRequest) // Firefox et autres
		xhr = new XMLHttpRequest(); 
	else if(window.ActiveXObject){ // Internet Explorer 
		try {
			xhr = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}
	} else { // XMLHttpRequest non supporté par le navigateur 
		alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest..."); 
		xhr = false; 
	} 
	return xhr;
}

function evolUpdate(callback, projid, evolid, field, value) {
	var xhr = getXhr();
	// On défini ce qu'on va faire quand on aura la réponse
	xhr.onreadystatechange = function(){
		// On ne fait quelque chose que si on a tout reçu et que le serveur est ok
		if(xhr.readyState == 4 && xhr.status == 200)
			callback(xhr.responseText, field, projid, evolid);
	}

	xhr.open("POST", "rodeo_ajax.php", true);
	// ne pas oublier ça pour le post
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.send("ajax_action=evolUpdate&projid_maj="+projid+"&evolid_maj="+evolid+"&field="+field+"&value="+value);
}

function environnementUpdate(callback, environnementid, field, value) {
	var xhr = getXhr();
	// On défini ce qu'on va faire quand on aura la réponse
	xhr.onreadystatechange = function(){
		// On ne fait quelque chose que si on a tout reçu et que le serveur est ok
		if(xhr.readyState == 4 && xhr.status == 200)
			callback(xhr.responseText, field, environnementid);
	}

	xhr.open("POST", "../hrpic_tools/rodeo_ajax.php", true);
	// ne pas oublier ça pour le post
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.send("ajax_action=environnementUpdate&envid_maj="+environnementid+"&field="+field+"&value="+value);
}

function refreshEvolLine(texte, field, projid, evolid) {
	var id = projid+"_"+evolid;
	
	change_couleur_ligne("l_"+id, 0);
	if((field == "facture_emise") && (texte == "1")) {
		document.getElementById("chkb_"+field+"_"+id).checked = true;
		document.getElementById("chkb_"+field+"_"+id).setAttribute("disabled","disabled");
		document.getElementById("btn_del_"+id).innerHTML = '';
	} else
		document.getElementById("field_"+field+"_"+id).innerHTML = texte;
	if(field.substr(0, 5) == 'jours') {
		document.getElementById("field_jours_total_"+id).innerHTML = parseFloat(document.getElementById("field_jours_analyse_"+id).innerHTML)
																	+ parseFloat(document.getElementById("field_jours_developpement_"+id).innerHTML)
																	+ parseFloat(document.getElementById("field_jours_parametrage_"+id).innerHTML)
																	+ parseFloat(document.getElementById("field_jours_test_"+id).innerHTML)
																	+ parseFloat(document.getElementById("field_jours_documentation_"+id).innerHTML);
	}
}

function refreshEnvLine(texte, field, environnementid) {
	var id = environnementid;
	
	change_couleur_ligne("l_"+id, 0);
	document.getElementById("field_"+field+"_"+id).innerHTML = texte;
}