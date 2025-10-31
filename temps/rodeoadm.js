/////////////////////////////////////////////////////////////////////////////////////
// meny

function mOvr(src, clrOver, alt){
	window.status = alt;
	if (!document.all || !src.contains(event.fromElement)){
		src.style.cursor = 'pointer';  // 'hand'
		src.bgColor = clrOver;
		//src.background = "floppy.gif";
	}
	return true;
}
function mOut(src, clrIn){
	window.status = '';
	if (!document.all || !src.contains(event.toElement)){
		src.style.cursor = 'default';
		src.bgColor = clrIn;
		//src.background = "";
	}
	return true;
}

function mClk(src){
	if(src.tagName == 'TD')
		src.children.tags('A')[0].click();
	return true;
}

var layerStyleRef;
var layerRef;
var styleSwitch;
var showSwitch;
var hideSwitch;

function fixedLayer(layerName) {
	if (document.all) {
		naviOBJ = document.all["adminMenu"].style;
		naviOBJ.top =  document.body.scrollTop;
	}
	else {
		document.getElementById("adminMenu").style.top = window.pageYOffset;
		setTimeout("fixedLayer()", 50);
	}
}

function initMenu(layerName) {
	
	if (document.all) {
		window.onscroll = fixedLayer;
	}
	else { // netscape
	//	setTimeout("fixedLayer('adminMenu')", 200);
	}
}

initMenu("adminMenu");

function init(){
	if (navigator.appName == "Netscape") {
		layerStyleRef="layer.";
		layerRef="document.layers";
		styleSwitch="";
		showSwitch = "show";
		hideSwitch = "hide";

	}
	else {
		layerStyleRef="layer.style.";
		layerRef="document.all";
		styleSwitch=".style";
		showSwitch = "visible";
		hideSwitch = "hidden";
	}
}

init();

function showLayer(layerName){
	var layer = document.getElementById(layerName);
	if (layer)
		layer.style.visibility = "visible";

}

function hideLayer(layerName){
	var layer = document.getElementById(layerName);
	if (layer)
		layer.style.visibility = "hidden";
	
}

function helpWin(str)
{
	if (top.frames.length == 0)
		return;
	aWin = self.parent;
	aWin.popupHelp(str);
}


function writeMenu(cls, ref, caption, alt)
{
	document.write(
	"<td align=center class='td_menu_"+cls+"'><a href='"+scriptName+"?"+ref+"' title='"+alt+"'>"+
		caption+"</a></td>");
}

/////////////////////////////////////////////////////////////////////////////////////
// nytt projekt

function one2two() {
	var m1 = document.NyttProjekt.menu1;
	var m2 = document.NyttProjekt.menu2;

	for ( i=0; i<m1.length ; i++)
		if (m1.options[i].selected == true )
		   m2.options[m2.length] = new Option(m1.options[i].text, m1.options[i].value);

	for ( i = (m1.length -1); i>=0; i--)
		if (m1.options[i].selected == true )
			m1.options[i] = null;
}

function two2one() {
	var m1 = document.NyttProjekt.menu1;
	var m2 = document.NyttProjekt.menu2;
	for ( i=0; i<m2.length ; i++)
		if (m2.options[i].selected == true )
			m1.options[m1.length]= new Option(m2.options[i].text, m2.options[i].value);

	for ( i=(m2.length-1); i>=0; i--)
		if (m2.options[i].selected == true ) m2.options[i] = null;
}

function Send_String() {
	var m1 = document.NyttProjekt.menu1;
	var m2 = document.NyttProjekt.menu2;
	var mask = 0;
	var oldMask = 0;
	
	for ( i=0; i<m1.length ; i++) {
		k = m1.options[i].value;
		oldMask = mask;
		mask |= Math.pow(2, k-1);
	}
	
	document.NyttProjekt.mask.value = mask;

/*
	for (i = m1.length -1; i >= 0; i--)
		m1.options[i] = null;
	for (i = m2.length -1; i >= 0; i--)
		m2.options[i] = null;
*/
}

function avslutaProjekt() {
	with (document.NyttProjekt) {
		if (avslut.checked)
			setStatusbar("Projektet kommer markeras som avslutat !");
		else
			setStatusbar("Projektets avslutsmärkning borttas !");
	}
}

function validera_projekt(){
	Send_String();
	
	with (document.NyttProjekt) {
		if ( projnr.value == "" ) {
			setStatusbar("Entrez le numéro de projet !");
			projnr.focus();
			projnr.select();
			return false;
		}
		if ( kundnr.options[kundnr.selectedIndex].value == 0 ) {
			setStatusbar("Sélectionnez Client !");
			kundnr.focus();
			return false;
		}
		if ( beskrivning.value == "" ) {
			setStatusbar("Entrez description !");
			beskrivning.focus();
			beskrivning.select();
			return false;
		}
		if ( ledare.selectedIndex == 0 && ledare[0].value == "" ) {
			setStatusbar("Choisir un chef de projet !");
			ledare.focus();
			return false;
		}
		if ( menu1.length == 0 ) {
			setStatusbar("Choisir au moins une activité !");
			menu1.focus();
			return false;
		}
		if ( avslut != null ) {
			if ( avslut.checked ) {
		  		if ( !confirm("Vous avez choisi de cloturer le projet, continuer ?") )
					return false;
			}
		}
		if ( overtid1.value == "" ) {
			setStatusbar("Choisir un facteur de surfacturation (1.5) !");
			overtid1.focus();
			overtid1.select();
			return false;
		}
		if ( overtid2.value == "" ) {
			setStatusbar("Choisir un facteur de surfacturation (2) !");
			overtid2.focus();
			overtid2.select();
			return false;
		}
	}	
		
	return true;
}

function clear_statusbar(form) {
	setStatusbar('&nbsp;');
	//form.statusbar.value = "";
		
}

/////////////////////////////////////////////////////////////////////////////////////
// projektdeltagare

function fCheck(i) {
	if ( !eval('document.medverkan.user'+i+'.checked') )
		eval("document.medverkan.arvode"+i+".value = ''");
}

function fDisable(i) {
	if ( !eval('document.medverkan.user'+i+'.checked') )
		eval('document.medverkan.user'+i+'.focus()');
}

function validate(i) {
	eval("val = document.medverkan.arvode"+i+".value;");
	if (1*val+' ' =="NaN " || val<0) {
		//eval("medverkan.arvode"+i+".value = '';");
		eval("document.medverkan.arvode"+i+".select();");
		eval("document.medverkan.arvode"+i+".focus();");
	}
}

function validera_deltagare() {
	for (i=1; i<=document.medverkan.projantal.value; i++)
		u = userV[i];
	if ( eval('document.medverkan.user'+u+'.checked') ){
	  eval("val = document.medverkan.arvode"+u+".value");
	  if ( isNaN(val) || val<1) {
			eval("document.medverkan.arvode"+u+".focus()");
			eval("document.medverkan.arvode"+u+".select()");
			event.returnValue = false;
			return;
	  }
	}
}

/////////////////////////////////////////////////////////////////////////////////////
// ny anställd

function valDatum(src)
{
	var months = new Array(0,31,29,31,30,31,30,31,31,30,31,30,31);

	var datum = src.value;
	if (datum.length < 6)
		return false;

	if (datum.length == 6)
		src.value = datum = '20' + datum;

	yyyy = parseInt( datum.substr(0,4) );
	mm   = datum.substr(4,2);
	if (mm.substr(0,1) == '0')
		mm = mm.substr(1,1);
	dd   = datum.substr(6,2);
	if (dd.substr(0,1) == '0')
		dd = dd.substr(1,1);

	return (yyyy>2000 && yyyy<2025) &&
	       (mm>0 && mm<13) &&
	       (dd>0 && dd <= months[mm]);

}

function avslutaUser() {
	with (document.NyttProjekt) {
		if (avslut.checked)
			setStatusbar("The user will be marked as 'resigned' !");
		else
			setStatusbar("The user is active !");
	}
}

function validera_users(){
	with (document.add_user) {
		val = empid.value;
		if ( isNaN(val) || val < 1 || parseInt(val) != val) {
			setStatusbar("Enter userid !");
			empid.focus();
			empid.select();
			return false;
		}
		if (name.value == "") {
			setStatusbar("Enter firstname !");
			name.focus();
			name.select();
			return false;
		}
		if (surname.value == "") {
			setStatusbar("Enter lastname !");
			surname.focus();
			surname.select();
			return false;
		}
		if (change.value == "" && password.value == "") {
			setStatusbar("Enter password !");
			password.focus();
			password.select();
			return false;
		}
		if ( !valDatum(startdatum) ) {
			setStatusbar("Startdate must be given on the form yyyymmdd !");
			startdatum.focus();
			startdatum.select();
			return false;
		}
	}
	return true;
}

function validera_invoice(){
	with (document.invoice) {
		val = belopp.value;				
		if ( !val || isNaN(val) ) {
			setStatusbar("Enter amount !");
			belopp.focus();
			belopp.select();
			return false;
		}
		val = timmar.value;		
		if ( !val ||  isNaN(val) ) {
			setStatusbar("Enter number of hours !");
			timmar.focus();
			timmar.select();
			return false;
		}
		if ( !valDatum(paymentdue) ) {
			setStatusbar("--Startdate must be given on the form yyyymmdd !");
			paymentdue.focus();
			paymentdue.select();
			return false;
		}
	}
	return true;
}

function validera_received(){
	with (document.paymentreceived) {
		if ( !valDatum(date) ) {
			setStatusbar("Received date must be on the form yyyymmdd !");
			date.focus();
			date.select();
			return false;
		}
	}
	return true;
}

/////////////////////////////////////////////////////////////////////////////////////
// kalender

function fyllRuta(week, arbetstid, kommentar,red) {
	document.ruta.week.value = week;
	document.ruta.arbetstid.value = arbetstid;
	document.ruta.kommentar.value = kommentar;
	fyllCheck(red);
	showLayer('layer1');
	hideLayer('layer2');
}

function fyllCheck(str) {
	checkV = str.split( /,/ );
	for (i=1; i<=5; i++)		
		eval( 'document.ruta.check'+i+'.checked = false' );
	
	if (checkV.length > 0) {
		for (i=0; i<checkV.length; i++) {
		
			if( (checkV[i]*1 > 0) && (checkV[i]*1 < 6) ) {
				 eval( 'document.ruta.check'+ checkV[i] +'.checked = true' );
			}
		}
	}
	
}

/////////////////////////////////////////////////////////////////////////////////////
// tidbank

function validera_tidbank() {
	with (document.tidbank) {
		if    ( radio[0].checked  )
			transtyp.value = 1;
		else if ( radio[1].checked  )
			transtyp.value = 2;

		if ( transtyp.value < 1 ) {
			setStatusbar("Välj uttag av tidbank i kompledighet eller lön !");
			return false;
		}
		val = trans_nytid.value;
		if ( 1*val== "NaN" || val+"a" == "a" ) {
			setStatusbar("Välj ny tidbank !");
			trans_nytid.focus();
			trans_nytid.select();
			return false;
		}
	}
	return true;
}

/////////////////////////////////////////////////////////////////////////////////////
// ny kund

function validera_kunder(){
	with (document.NyKund) {
		if (kundnr.value == "") {
			setStatusbar("Enter customerid !");
			kundnr.focus();
			kundnr.select();
			return false;
		}
		if (kund.value == "") {
			setStatusbar("Enter name !");
			kund.focus();
			kund.select();
			return false;
		}
	}
	return true;
}

/////////////////////////////////////////////////////////////////////////////////////
// projektdeltagare

function checkNyDeltagare(obj){
//	var state = NyDeltagare.radiobutton;
//	if (state[0].checked){
//		NyDeltagare.statusbar.value = "Vid 'förläng period kan endast slutmånad ändras'";
//		obj.blur();
//	}
//	else if (state[0].checked){
//		obj.blur();
//	}
}

var month1_orig, month2_orig;
function historik2(){
  
  
	var i = document.NyDeltagare.historik.selectedIndex;
	if (i == -1)
		return;
//	var s = NyDeltagare.historik[i].text;
//	sV = s.split( / / );
//	dV = sV[0].split( /-/ );
//	kr = sV[1];
//	omfattn = sV[3];
//	i = omfattn.indexOf("%");
//	omfattn = omfattn.substr(0,i);
//
//	with (NyDeltagare){
//		month1_orig = month1.value = dV[0];
//		month2_orig = month2.value = dV[1];
//		arvode.value = kr;
//		omfattning.value = omfattn;
//		ch_hist.value = historik.value;
//		radiobutton[0].checked = "1";
//	}
	sV = projV[i];
	with (document.NyDeltagare){
		month1_orig = month1.value = sV[0];
		month2_orig = month2.value = sV[1];
		arvode.value = sV[2];
		omfattning.value = sV[3];
		ch_hist.value = historik.value;
		radiobutton[0].checked = "1";
		radiobutton.value = 1
	}

}

function addPeriod(){	
	with (document.NyDeltagare){
		if (historik.selectedIndex == -1)
			return;
		month1.value = "";
		month2.value = "";
		arvode.value = "";
		omfattning.value = "100";
		historik.selectedIndex = -1;
	}
}

// nya eventStoppare
function submitData(e, func) {
	if ( !func )
		if ( document.all )			// ie
			event.returnValue = false;
		else
			e.preventDefault();		// mozilla
}

function validera_NyDeltagare() {
	with (document.NyDeltagare){
		if (type.value == "change"){
			if (inaktiv.checked){
				event.returnValue = true;
				return false;
			}
		}
		if (type.value != "change" && deltagare.value == 0) {
			setStatusbar("Enter members !");
			deltagare.focus();
			return false;
		}
		m1 = parseInt(month1.value);
		m2 = parseInt(month2.value);
		if ((m1+" "=="NaN " || m2+" "=="NaN ") || (m1<1 || m1>12) || (m2<1 || m2>12) || (m2<m1)){
			setStatusbar("Period must be in the range 1-12 !");
			month1.focus();
			return false;
		}
		if (type.value == "change"){
			monthsV2 = monthsV;

			if (radiobutton.value == 1)
				for (i=month1_orig; i<=month2_orig; i++)
					monthsV2[i] = 0;

			for (i=m1; i<=m2; i++)
				if (monthsV2[i] > 0){
					statusbar.value = "Period crosses another period !"+i+month1_orig+month2_orig;
					month1.focus();
					return false;
				}
		}
		kr = parseInt(arvode.value);
		if (kr+" "=="NaN " || kr<1){
			setStatusbar("Enter fee [$] !");
			arvode.focus();
			return false;
		}
	}
	return true;
}

function projInaktiv() {
	with (document.NyDeltagare){
		if (inaktiv.checked)
			setStatusbar("The project will not be visible for the user !");
		else
			setStatusbar("");
	}
}

function deletePeriod(){
	if (month1_orig == null)
		setStatusbar("Select period to remove !");
	else
		setStatusbar("The period "+month1_orig+"-"+month2_orig+" will be removed !");
}

function setReceived(unik, invoice) {
		
	var td = document.getElementById('td_invoice');
	if (td)
		td.innerHTML = invoice;
		
	document.paymentreceived.ID.value = unik;
		
	showLayer('layer1');	
}

function removeInvoice(url) {
	if ( confirm("Remove entry ?") )
		document.location.href = url;
}

function setStatusbar(val) {
	var div = document.getElementById('statusbar2');
	if (div)
		div.innerHTML = val;
}


function defaultPeriod(m1) {
	with (document.NyDeltagare){
		arvode.value = "1";
		month1.value = m1;
		month2.value = "12";				
	}	
}

function checkActivity(maxId) {
	if(maxId > 32) {
		alert('Non, on ne peut toujours pas aller plus loin que 32 activit\351s !!!');
		return false;
	}
	return true;		
}