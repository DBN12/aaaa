<?php
	setlocale (LC_TIME, 'fr-fr');
	
	$db_connect = null;
	$ligne = null;
	$db_requete = null;
	$db_resultat = null;
	
	$liste_customers = array();
	$liste_projets = array();
	$liste_activite = array();
	
	$vue_choisie = 0;
	
	$db_connect = mysql_connect("127.0.0.1", "root", "d10lillE2005");

	//  Liste des customers
	$db_requete = " 
		SELECT custid, name, description 
		FROM rodeo.customers 
		ORDER BY name;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_customers[$ligne["custid"]] = $ligne;
	mysql_free_result($db_resultat);
	
//  Liste des projets
	$db_requete = " 
		SELECT c.custid AS custid, c.name AS cust_name, c.description AS cust_desc, p.projid AS projid, p.description AS proj_desc, p.activities_mask AS proj_mask
		FROM rodeo.projects p, rodeo.customers c
		WHERE p.custid = c.custid
		ORDER BY cust_name, proj_desc;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_projets[$ligne["custid"]][$ligne["projid"]] = $ligne;
//	print_r($liste_projets);
	mysql_free_result($db_resultat);
	
//  Liste des activités
	$db_requete = " 
		SELECT actid, name, description, maskid 
		FROM rodeo.activities;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_activites[$ligne["actid"]] = $ligne; 
//	print_r($liste_activites);
	mysql_free_result($db_resultat);
	
	$vue_choisie = (int)$_POST['vue'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Affectation des activit&eacute;s aux projets</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="expires" content="0" />
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

<body>
<div>
	<div id="haut"><a href="http://www.cbl-consulting-nord.com">&lt;&lt; Retour accueil</a></div>
	<hr />
</div>
<form id="f_criteres" action="projets_et_activites.php" method="post" class="formulaire">
<table>
	<tr>
		<th colspan="2">Mode d'affichage</th>
	</tr>
	<tr>
		<td>
			<input type="radio" name="vue" value="0" <?php echo ($vue_choisie == 0?'checked="checked"':""); ?> onclick='document.forms["f_criteres"].submit();' /> Liste des activit&eacute;s par projet
		</td>
	</tr>
	<tr>
		<td>
			<input type="radio" name="vue" value="1" <?php echo ($vue_choisie == 1?'checked="checked"':""); ?> onclick='document.forms["f_criteres"].submit();' /> Liste des projets par activit&eacute;
		</td>
	</tr>
</table>
</form>
<br />
<table class="report">
<?php if($vue_choisie == 0): ?>
	<colgroup style="background-color:yellow;" >
		<col width="100" />
		<col width="20" />
		<col width="300" />
	</colgroup>
	<colgroup>
		<col width="20" />
		<col width="200" />
	</colgroup>
<?php endif; ?>
<?php if($vue_choisie == 1): ?>
	<colgroup style="background-color:yellow;" >
		<col width="20" />
		<col width="200" />
	</colgroup>
	<colgroup>
		<col width="100" />
		<col width="20" />
		<col width="300" />
	</colgroup>
<?php endif; ?>
	<thead>
		<tr>
<?php if($vue_choisie == 1): ?>
			<th colspan="2">Activit&eacute;</th>
<?php endif; ?>
			<th>Entit&eacute;</th>
			<th colspan="2">Projet</th>
<?php if($vue_choisie == 0): ?>
			<th colspan="2">Activit&eacute;</th>
<?php endif; ?>
		</tr>
	</thead>
	<tbody style="overflow: auto; height: 300px; overflow-x: hidden;">
<?php switch($vue_choisie): ?>
<?php	case 0: ?>
<?php 		foreach($liste_projets as $cust_id=>$cust_data): ?>
<?php			foreach($cust_data as $proj_id=>$proj_data): ?>
<?php 				foreach($liste_activites as $act_id=>$act_data): ?>
<?php					if( ((float)$act_data["maskid"] & (float)$proj_data["proj_mask"]) != 0 ): ?>
		<tr>
			<td><?php echo htmlentities($proj_data["cust_name"]); ?> </td>
			<td><?php echo htmlentities($proj_data["projid"]); ?> </td>
			<td><?php echo htmlentities($proj_data["proj_desc"]); ?> </td>
			<td><?php echo htmlentities($act_data["actid"]); ?> </td>
			<td><?php echo htmlentities($act_data["description"]); ?> </td>
		</tr>
<?php					endif; ?>
<?php				endforeach; ?>
<?php			endforeach; ?>
<?php 		endforeach; ?>
<?php 		break; ?>
<?php	case 1: ?>
<?php 		foreach($liste_activites as $act_id=>$act_data): ?>
<?php			foreach($liste_projets as $cust_id=>$cust_data): ?>
<?php 				foreach($cust_data as $proj_id=>$proj_data): ?>
<?php					if( ((float)$act_data["maskid"] & (float)$proj_data["proj_mask"]) != 0 ): ?>
		<tr>
			<td><?php echo htmlentities($act_data["actid"]); ?> </td>
			<td><?php echo htmlentities($act_data["description"]); ?> </td>
			<td><?php echo htmlentities($proj_data["cust_name"]); ?> </td>
			<td><?php echo htmlentities($proj_data["projid"]); ?> </td>
			<td><?php echo htmlentities($proj_data["proj_desc"]); ?> </td>

		</tr>
<?php					endif; ?>
<?php				endforeach; ?>
<?php			endforeach; ?>
<?php 		endforeach; ?>
<?php		break; ?>
<?php endswitch; ?>
	</tbody>
</table>
<?php
	mysql_close($db_connect);
?>
<div>
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
	</p>
</div>
</body>
</html>