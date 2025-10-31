<?php
switch ($mode) {
    case 1:
        $title = "Rodéo (stats mensuelles)";
        break;
    case 2:
        $title = "Rodéo (stats sur plusieurs mois)";
        break;
}
?>
<head>
<title><?php echo htmlentities($title); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="expires" content="0" />
<script src="../boite_a_outils/js/Chart.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
	function sortTable (tb, n) {
		var iter = 0;
		
		while (!tb.tagName || tb.tagName.toLowerCase()!= "table") {
			if (!tb.parentNode)
				return;
			tb = tb.parentNode;
		}
		if (tb.tBodies && tb.tBodies[0])
			tb = tb.tBodies[0];
//	 	Tri par sélection
		var reg = /^\d+(\.\d+)?$/g;
		var index = 0, value = null, minvalue = null;

		
		for (var i = tb.rows.length -1; i >= 0; i -= 1) {	
			minvalue = value = null;
			index = -1;
			for (var j = 0; j <= i; j += 1) {
				value = tb.rows[j].cells[n].firstChild.nodeValue.toLowerCase();
				if(value == ' ' || value == '  ' || value == '' || value == null) {
					value = "";
				} else {
				if (!isNaN(value))
					value = parseFloat(value);
				}
//				alert(value);
				if (minvalue == null || value < minvalue) {
					index = j;
					minvalue = value;
				}
//				alert(tb.rows[j].cells[0].firstChild.nodeValue + "_" + tb.rows[j].cells[n].firstChild.nodeValue + "_" + minvalue + "_" + value);
			}

			if (index != -1) {
				var row = tb.rows[index];
				if (row) {
					tb.removeChild(row);
					tb.appendChild(row);
				}
			}
		}
	}
	
	function expandCondenseCriteriaBox(box) {
		var expanded;
		var tb = box;
		
		while (!tb.tagName || tb.tagName.toLowerCase()!= "table") {
			if (!tb.parentNode)
				return;
			tb = tb.parentNode;
		}
		
		expanded = ( tb.tBodies[0].hidden == false );
		tb.tBodies[0].hidden = expanded;
		if(expanded)
			box.innerHTML = "+";
		else
			box.innerHTML = "-";
	}
// -->
</script>
<style type="text/css">
<!--
	body,td,div,p,a {
		font-family: arial,sans-serif;
	}
	a, a:link, a:visited, a:active {
		color: blue;
		text-decoration: none;
	}
	a:hover {
		color: blue;
		text-decoration: underline;
	}
	td.valeur {
		width:1em;
		text-align: center;
		font-style: italic;
	}
	tfoot td {
		 font-weight: bold;
	}
	.erreur {
		color:red;
		border:2px solid red;
		padding:1px;
		font-weight:bold
	}
	.warning {
		margin:10px
	}
	h2 {
		border:1px solid #66A;
		background: #CCF;
		margin:20px;
		padding:10px;
		font-size:18px;
		font-weight:bold;
		color:#66A;
		text-align: center
	}
	h3 {
		margin:5px;
		font-size:18px;
		font-weight:bold;
		color:#393;
		text-align : left;
		text-decoration:underline
	}
	form.formulaire table {
		padding:10px;
		margin-left:30px;
		border:1px solid #AAA;
		background:#EEE
	}
	table.report {
		border:1px solid #AAA;
		background:#EEE;
		color:#339
	}
	table.report tr th {
		border:1px solid #AAA;
		background:#CCC
	}
	table.report tr td {
		border:1px solid #AAA;
	}
	table.report tr:hover {
		background-color:chartreuse;
		//font-weight: bold;
	}
-->
</style>
</head>