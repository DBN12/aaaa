var tidbank;
var arbetstid;
var days = new Array('','mon','tues','wednes','thurs','fri','satur','sun');
var ichange     = 0;
var bValidering = false;

function helpWin(str) {
	if (top.frames.length == 0) return;
	aWin = self.parent;
	aWin.popupHelp(str);
}

function init_sums(form,nbRows) {
	arbetstid = 1*form.arbetstid.value;
	tidbank   = 1*form.orig_tidbank.value;
	var sum = 0;

	for (j=1; j<8; j++) {
	sum = 0;
	for (i=1; i<=nbRows; i++) {
		eval('sum += 1*form.' +days[j]+i+ '.value;');
		eval('form.sum'+i+'.value = 1*form.mon'+i+'.value + 1*form.tues'+i+'.value + 1*form.wednes'+i+'.value + 1*form.thurs'+i+'.value + 1*form.fri'+i+'.value+ 1*form.satur'+i+'.value+ 1*form.sun'+i+'.value;');
		if ( eval('1*form.'+days[j]+i+'.value == 0') ) eval('form.'+days[j]+i+'.value = "";');
	}
	eval('form.'+days[j]+'day.value= sum;');
	}
	form.summa.value = 1*form.monday.value+1*form.tuesday.value+1*form.wednesday.value+1*form.thursday.value+1*form.friday.value+1*form.saturday.value+1*form.sunday.value;
	form.tidbank.value = form.summa.value - arbetstid + tidbank;

	if (1*form.monday.value == 0) form.monday.value = "";
	if (1*form.tuesday.value == 0) form.tuesday.value = "";
	if (1*form.wednesday.value == 0) form.wednesday.value = "";
	if (1*form.thursday.value == 0) form.thursday.value = "";
	if (1*form.friday.value == 0) form.friday.value = "";
	if (1*form.saturday.value == 0) form.saturday.value = "";
	if (1*form.sunday.value == 0) form.sunday.value = "";
	for (i=1; i<=nbRows; i++) {
		eval('if (1*form.sum'+i+'.value == 0) form.sum'+i+'.value = "";');
	}
	if (1*form.summa.value == 0) form.summa.value = "";
}

function submit_print(form,forma,n, src, nbRows) {

	src.className = "ari";

	var sum = 0;
	var sum2 = 0;
	var str = "";
	var str2 = "";

	eval('var obj = form.'+forma+n+';');
	//status = obj;
	if (obj == null) return;
	if (obj.type != "text") return;

	var s = obj.value;

	ichange = 1;
	var stat3 = stat3 = document.getElementById('statusbar3');
	if (stat3)
		stat3.innerHTML = "Osparad";

	if (!bValidering) clear_statusbar(form);

	//byt ut komma , mot punkt .
	s = s.replace(/,/,".");

	var bTom = (s == "");


	if (s == "" || s == 0) {
		s = 0;
		obj.value = "";
	}

	if ( 1*s < 0  || 1*s+" " == "NaN " ) {
		set_statusbar("Negative number or non digit input!");
		obj.focus();
		obj.select();
		bValidering = true;
		return;
	}

	for (i=1; i<=nbRows; i++)
		sum += 1*eval('form.' +forma+i+ '.value;');

	if (sum == 0) sum = "";
	eval('form.'+forma+'day.value= sum;');

	totsum = 1*form.monday.value+1*form.tuesday.value+1*form.wednesday.value+1*form.thursday.value+1*form.friday.value+1*form.saturday.value+1*form.sunday.value;
	if (totsum == 0) totsum = "";
	form.summa.value = totsum;

	eval('sum2 = 1*form.mon'+n+'.value + 1*form.tues'+n+'.value + 1*form.wednes'+n+'.value + 1*form.thurs'+n+'.value + 1*form.fri'+n+'.value + 1*form.satur'+n+'.value + 1*form.sun'+n+'.value;');
	if (sum2 == 0) sum2 = "";
	eval('form.sum'+n+'.value = sum2;');

	form.tidbank.value = form.summa.value - arbetstid + tidbank;

	if ( sum > 24 ) {
		set_statusbar("It's impossible to work " + sum + " hours a day! ");
		obj.focus();
		obj.select();
		bValidering = true;
		return;
	}

	if (form.summa.value > 70) {
		set_statusbar("You are working hard: "+form.summa.value+" hours a week! ");
		//bValidering = true;
	}

	eval('str2 = form.projnr'+n+'.value;');
	if (!bTom && str2 == "0") {
		set_statusbar("Select project for row "+n);
		eval('form.projnr' +n+ '.focus();');
		bValidering = true;
		return;
	}

	eval('str2 = form.lopnr'+n+'.value;');
	if (!bTom && str2 == "0") {
		set_statusbar("Select activity for row "+n);
		eval('form.lopnr' +n+ '.focus();');
		bValidering = true;
		return;
	}

	if (forma == "sun")  //hoppa över sum i tabulerig
		eval('form.comment'+n+'.focus();') ;

	if ( 1*obj.value == 0 )
		obj.value = "";

	bValidering = false;

}

function f_change() {
	if (ichange)
		return confirm("Vous n'avez pas sauvegardé!\nContinuer?");
	else
		return 1;
}

function f_inst(week,year) {
	if ( f_change() )
		eval('document.location.href = "'+scriptName+'?config=1&week='+week+'&year='+year+'";');
}

// nya eventStoppare
function stopEvent(e) {
	if ( document.all )			// ie
		event.returnValue = false;
	else
		e.preventDefault();		// mozilla
}

function f_checkbox(e) {
	// kontrollera först att ingen dag har mer än 24 h rapporterat
	for (i=1; i<=7; i++)	{
		sum = eval("document.table."+days[i]+"day.value");
		if (sum > 24) {
			alert("It's impossible to work "+sum+" hours a day!");
			stopEvent(e);
			return;
		}
	}

	document.table.saveweek.disabled = true;

	// om veckan har checkbox för slutrapportering ska användaren
	// få fråga om veckan verkligen ska avslutas. Om man svarar
	// nej på frågan avbockas checkboxen
	// sidan spas sedan ovasett om svaret är ja el. nej
	if (document.table.finished != null) {   // ingen checkbox
		var checked = false;
		if (document.table.finished.checked) {  // checkbox fanns och är ikryssad
			checked = confirm("The week is marked as finished!\nContinue?");
			document.table.finished.checked = checked;
		}
		if (!checked) {
			document.table.target = "transmitter";
			document.table.transmitter.value = 1;
		} else {
			document.table.target = "";
			document.table.transmitter.value = "";
		}
	}

	document.table.submit();
}

function Back(week,year) {
	eval('document.location.href = "'+scriptName+'?gotow=1&week='+week+'&year='+year+'";');
}

function GoTo(week,year) {
	if (f_change()) eval('document.location.href = "'+scriptName+'?&gotow=1&week='+week+'&year='+year+'";');
}

function LogOut(user) {
	if (f_change()) eval('document.location.href = "'+scriptName+'?logout=1";');
}

function Fokus(src) {
	src.className = "ari_on";
}

function Fokus2(src) {
	src.className = "theme_on";
}

function LostFokus2(src) {
	src.className = "theme";
}

function Fokus3(src) {
	src.className = "comment_on";
}

function LostFokus3(src) {
	src.className = "comment";
}


function clearSelect(sel) {
	eval('var obj = document.table.'+sel);
	for (i = obj.options.length-1; i >= 0; i--)
		if ( obj.options[i] != null )
			obj.options[i] = null;
}

function fillSelect(sel, V, mask) {
	var j = 0;
	eval('var obj = document.table.'+sel+';')

	obj.options[0] = new Option(V[0][1], V[0][0]);

	for (i = 1; i < V.length; i++)
		if (mask != 0) {
			if (( V[i][2] & mask ) != 0)
				obj.options[++j] = new Option(V[i][1], V[i][0]);
		} else {
			obj.options[++j] = new Option(V[i][1], V[i][0]);
		}
}

function changeMenu(Index) {

	eval('var obj = document.table.projnr'+Index);

	menuNum = obj.selectedIndex;
	projValue = obj.value;

  clearSelect('lopnr'+Index);
  clearSelect('tidkodnr'+Index);

  if (projValue == 400)
  	fillSelect('lopnr'+Index, absentV, 0);
  else {
  	mask = projectV[menuNum][2];
		fillSelect('lopnr'+Index, tasksV, mask);
	}


	// tidkod
	if (menuNum > 0 && projValue != 400)
		fillSelect('tidkodnr'+Index, timecodesV, 0);

}


function selectOption(sel, value) {
	eval('var obj = document.table.'+ sel);

	for (i=0; i<obj.options.length; i++)
		if (obj.options[i].value == value)
				obj.options[i].selected = true;
}

function ProjektOption(Index,pNum,lNum,tNum, disabled, d1,d2,d3,d4,d5,d6,d7,theme,com,evolLibelle,nbRows) {
	with (document) {
		writeln('<tr><td><SELECT NAME="projnr'+Index+'" '+
		        disabled+' onChange="changeMenu('+Index+'); clear_statusbar(this.form)"'+
		        'onMouseOver="window.status=\'Choisissez un projet!\';return true" onMouseOut="window.status=\'\';return true" class=dropdown>');

		for (i = 0; i < projectV.length-1; i++)
			writeln("<OPTION value="+projectV[i][0]+">"+projectV[i][1]);

		writeln("<OPTGROUP label=' '>");
		writeln("<OPTION value="+projectV[i][0]+">"+projectV[i][1]);
		writeln("</OPTGROUP>");

		writeln("</SELECT></td><td>");
		writeln('<SELECT NAME="lopnr'+Index+'"  '+disabled+' onChange="clear_statusbar(this.form)"  class=dropdown><OPTION></SELECT></td>');

		writeln('<td><SELECT NAME="tidkodnr'+Index+'" '+disabled+'  onChange="clear_statusbar(this.form)"  class=dropdown><OPTION></SELECT></td>');


		if (pNum > 0) {
			selectOption('projnr'+Index, pNum);
			changeMenu(Index);
			// fix. om projektet är avslutat
			var proj = eval("document.table.projnr" + Index);
			if (proj.selectedIndex == 0) {
				var x = proj.options.length;
				proj.options[x] = new Option("Project " + pNum + " [finished]", pNum);
				proj.options[x].selected = true;
			}
		}

		if (lNum > 0)
			selectOption('lopnr'+Index, lNum);

		if (tNum > 0)
			selectOption('tidkodnr'+Index, tNum);

		var days_val = new Array(0,d1,d2,d3,d4,d5,d6,d7);
		for (i=1; i<=7; i++){
			if (days_val[i] == 0)
				days_val[i] = '';
				// en riktigt vit (men inte vit) för att lura mozilla
			bgCol = (i<6) ? '' :  'style="background-color:lightyellow;"';
			writeln("<td><input type='text' "+disabled+" name='"+days[i]+Index+"'"+
			        " value='"+days_val[i]+"' maxlength='4' class='ari' "+
			        " onfocus=\"Fokus(this)\" onkeypress=\"return validateKeyPress(event)\" "+
			        " onblur=\"submit_print(this.form,'"+days[i]+"',"+Index+", this, "+nbRows+")\" "+
			        bgCol+"></td>");
		}

		writeln("<td><input type='text' name='sum"+Index+"' class='rsum' readonly></td>");
		writeln("<td><input type='text' "+disabled+" name='theme"+Index+"' value='"+theme+"' "+
		        " class='theme' maxlength='15' onfocus='clear_statusbar(this.form); Fokus2(this)' "+
		        " onblur='LostFokus2(this)'></td>");
		writeln("<td><input type='text' "+disabled+" name='commentaire"+Index+"' value='"+com+"' "+
		        " class='comment' maxlength='65' onfocus='clear_statusbar(this.form); Fokus3(this)' "+
		        " onblur='LostFokus3(this)'></td></tr>");
		writeln("<td><input type='text' name='evolLibelle"+Index+"' class='rsum' value='"+evolLibelle+"' readonly></td>");
	}
}

function validateKeyPress(e)
{
	var key, keychar;
	var validChars = ",.0123456789";

	if(window.event || !e.which) { // IE
		key = e.keyCode; // for IE, same as window.event.keyCode
		fKey = false;
	}
	else if(e) { // netscape{
		key = e.which;
		fKey = key == 8 || key == 9 || key == 37 || key == 39; // backsteg, tab, pilv, pilh
	}
	else
		return true;
	return (validChars.indexOf( String.fromCharCode(key) ) > -1 || fKey);
}

function clear_statusbar(form) {
	var stat = document.getElementById('statusbar');
	stat.innerHTML = "&nbsp;";
	stat.className = "statusbar";
	//document.getElementById('td_stat').bgColor = "menu";
}

function set_statusbar(str) {

	//document.table.statusbar.value = str;
	//document.table.statusbar.className = "statusbar_on";
	//document.all.td_stat.bgColor = "lightyellow";
	//document.getElementById('td_stat').bgColor = "lightyellow";)
	var stat = document.getElementById('statusbar');
	stat.className = "statusbar_on";
	stat.innerHTML = str;

}

function popupWindow(src,w,h) {
	window.open(src,'','toolbar=no,scrollbars=yes,resizable,width='+w+',height='+h);
}

function chooseWeek(iweek, form)
{
	with (document) {
		writeln('<SELECT NAME="week" class="boxes">');
		for (i=1; i<=52; i++)
			writeln("<OPTION value="+i+">"+i);
		writeln("</SELECT></td><td>");
		vecka.week.options[iweek-1].selected = true;
	}
}

function preFillRow(row, proj, akt, tid, d1,d2,d3,d4,d5,d6,d7,theme,commentaire,nbRows) {

	selectOption('projnr'+row, proj);
	changeMenu(row);

	selectOption('lopnr'+row, akt);

	selectOption('tidkodnr'+row, tid);

	for (i=1; i<8; i++) {
		eval("v = d"+i);
		if (v > 0)
			eval("document.table." + days[i] + row + ".value = "+ v);
	}

//	eval("obj = document.table.comment" + row);
	eval("obj = document.table.theme" + row);
	obj.value = theme + '';
	eval("obj = document.table.commentaire" + row);
	obj.value = commentaire + '';

	init_sums(document.table,nbRows);

}

function saved() {
	ichange = 0;
	document.table.saveweek.disabled = false;
	var stat3 = stat3 = document.getElementById('statusbar3');
	if (stat3)
		stat3.innerHTML = "&nbsp;";
}


function validatePassword() {
	with (document.ChangePassword) {
		if (oldpass.value == '') {
			setStatusbar("Ange gammalt lösenord !");
			oldpass.focus();
			oldpass.select();
			return false;
		}

		if (password.value == '') {
			setStatusbar("Ange nytt lösenord !");
			password.focus();
			password.select();
			return false;
		}

		if (password2.value == '') {
			setStatusbar("Repetera nytt lösenord !");
			password2.focus();
			password2.select();
			return false;
		}

		if (password.value != password2.value) {
			setStatusbar("Lösenord matchar inte !");
			password2.focus();
			password2.select();
			return false;
		}

		submit();
	}
}

function setStatusbar(val) {
	var div = document.getElementById('statusbar2');
	if (div)
		div.innerHTML = val;
}

function clearStatusbar() {
	setStatusbar('&nbsp;');
}