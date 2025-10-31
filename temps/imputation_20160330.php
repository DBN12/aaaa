<?php
	$db_connect = null;
	$tab_projet = array();
	$ligne = null;
	$db_requete = null;
	$db_resultat = null;
	$societe_id = 1;
	$societe_description = null;

	$db_connect = mysql_connect("127.0.0.1", "rodeo_admin", "");
//	print_r($db_connect);	
//	mysql_select_db('rodeo',$db_connect);

//  Société choisie
	if(!empty($_GET["imput_custid"]))
		$customer_id = $_GET["imput_custid"];
		
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

//	Liste des activités
	$db_requete = "
		SELECT * FROM rodeo.activities;";
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_activites[$ligne["actid"]] = $ligne;
	mysql_free_result($db_resultat);

//	Liste des imputations
	$db_requete = "
		SELECT * FROM rodeo.rodeo_to_hrpic;";
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_imputations[$ligne["projid"]."_".$ligne["actid"]] = $ligne;
	mysql_free_result($db_resultat);

//	Liste des types de projet
	$db_requete = "
		SELECT * FROM rodeo.project_type;";
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_project_type[$ligne["id"]] = $ligne;
	mysql_free_result($db_resultat);
		
//  Liste des projets
	$db_requete = " 
		SELECT c.description as societe, projid, p.description, project_type_id, activities_mask 
		FROM rodeo.projects p, rodeo.customers c
		WHERE p.custid = $customer_id
		  AND c.custid = p.custid
		ORDER BY p.description;
	";
//	print_r($db_requete);
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_projets[$ligne["projid"]] = $ligne;
	mysql_free_result($db_resultat);

//	print_r($_GET);
	$imput_action = $_GET["imput_action"];
		
	if (!empty($imput_action)) {
		switch($imput_action) {
			case "imput_add" :
				if(!empty($_GET["imput_nom"])) {
					$db_requete = "INSERT INTO rodeo.rodeo_to_hrpic ";
					$db_requete.= "(projid, actid, facturable, type, nom) ";
					$db_requete.= "VALUES ('".$_GET["imput_projid"]."', '".$_GET["imput_actid"]."', '".$_GET["imput_facturable"]."', '".$_GET["imput_type"]."', '".$_GET["imput_nom"]."');";
				}
				break;
			case "imput_delete" :
				if(!empty($_GET["imput_index"])) {
					$db_requete = "DELETE FROM rodeo.rodeo_to_hrpic ";
					$db_requete.= "WHERE rodeo_to_hrpic.index = '".$_GET["imput_index"]."';";
				}
				break;
		}
//		print $db_requete;
		if ($db_requete != null)
			$db_resultat = mysql_query($db_requete, $db_connect);

//		if (!empty($_GET["hrpic_environnement_id"]))
//			header("Location: ./user.php?ligne=l_".$_GET["hrpic_environnement_id"]."_".$_GET["hrpic_user"]);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Intranet de CBL Consulting Nord - Acc&egrave;s client</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="expires" content="0" />
<script type="text/javascript">
<!--
	function imput_delete(index, imput_nom) {
		var confirmation;

		confirmation = confirm("Voulez-vous vraiment effacer l'entrée " + imput_nom + "?");
		if(confirmation)
			window.open('http://lille/temps/imputation.php?imput_action=imput_delete'+'&imput_index='+index+'&imput_custid=<?php echo $societe_id; ?>', '_self');
	}

	function change_couleur_ligne(ligne_id, focus) {
		var ligne = window.document.getElementById(ligne_id);

		if(ligne.bgColor == "")
			ligne.bgColor = "#98FB98";
		else
			ligne.bgColor = "";

		if(focus == 1)
			ligne.scrollIntoView("true");
	}
	
	function imput_add(projid, actid) {
		var i = 0;
		var liste_projets = window.document.forms["f_imput_add"].elements["imput_projid"];
		var liste_activites = window.document.forms["f_imput_add"].elements["imput_actid"];

		while(liste_projets.options[i].value != projid)
			i++;
		liste_projets.options[i].selected = true;
		
		i = 0;
		while(liste_activites.options[i].value != actid)
			i++;
		liste_activites.options[i].selected = true;
		
		window.document.forms["f_imput_add"].elements["imput_facturable"].focus();

	}
//-->
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
	a img {
		border: 0;
	}
	.header {
		font-size:-1;
		font-weight: bold;
	}
	.title {}
	td .description {
		color: #333333;
		font-size: small;
	}
	tr {
		height:35px;
		background-color:none;
	}
	tr th {
		background-color:#DDD;
	}
	tr:hover {
		background-color:chartreuse;
		//font-weight: bold;
	}
//-->
</style>
</head>

<body <?php echo ((!empty($_GET["ligne"]))?'onload="javascript:change_couleur_ligne(\''.$_GET["ligne"].'\', 1);"':''); ?>>
<div>
	<div id="haut"><a href="http://lille">&lt;&lt; Retour accueil</a></div>
	<hr />
	<div>
<!--//
		<a href="http://lille/acces_client/user.php">&lt;Vue des utilisateurs&gt;</a>
		<a href="http://lille/acces_client/environnement.php">&lt;Vue des environnements&gt;</a>
		<a href="http://lille/acces_client/schema.php">&lt;Vue des sch&eacute;mas&gt;</a>
//-->
		&lt;Imputation&gt;
	</div>
</div>
<div>
	<br />
<form name="f_societe_select" action="./imputation.php">
	<select name="imput_custid">
<?php foreach ($liste_customers as $ligne): ?>
		<option value="<?php echo $ligne["custid"]; ?>" <?php echo ($ligne["custid"] == $customer_id)?"selected=\"selected\"":""; ?>><?php echo htmlentities($ligne["description"]); ?></option>
<?php endforeach; ?>
	</select>
	<input type="submit" value="OK">
	<input type="hidden" name="imput_action" value="societe_select" />
</form>
</div>
<div>
	<br />
	<table border="0" cellpadding="5">
		<colgroup  style="background-color:yellow;" >
			<col width="30" />
			<col width="215" />
			<col width="215" />
			<col width="165" />
			<col width="80" />
		</colgroup>
		<colgroup>
			<col width="80" />
			<col width="130" />
			<col width="30" />
		</colgroup>
		<thead>
		<tr>
			<th><!--index//--></th>
			<th>Soci&eacute;t&eacute; HR-Path</th>
			<th>Client</th>
			<th>Activit&eacute;</th>
			<th>Fact. ?</th>
			<th>Type</th>
			<th colspan="2">Imputation</th>
		</tr>
		</thead>
		<tbody>
<?php
/*
		SELECT c.description as societe, projid, description, project_type_id, activities_mask 
		FROM rodeo.projects p
		WHERE p.custid = '$customer_id'
		ORDER BY p.description;
*/
	$db_requete = "
		SELECT c.description AS societe, a.description AS activite, p.description AS client, rtp.* 
		FROM rodeo.customers c, rodeo.rodeo_to_hrpic rtp
		LEFT JOIN rodeo.projects p ON rtp.projid = p.projid
		LEFT JOIN rodeo.activities a ON rtp.actid = a.actid
		WHERE c.custid = ".$customer_id." 
		AND c.custid = p.custid
		ORDER BY societe, client, activite, facturable;";
?>
<?php foreach($liste_projets AS $ligne): ?>
<?php
		if ($societe_description == null)
			$societe_description = $ligne["societe"];
?>
<?php foreach($liste_activites AS $ligne_activite): ?>
<?php 	if(($ligne_activite["maskid"]+0) & ($ligne["activities_mask"]+0)): ?>
<?php //print_r($ligne); ?>
		<tr id="l_<?php echo $ligne["index"]; ?>" onclick="javascript:change_couleur_ligne('l_<?php echo $ligne["index"]; ?>', 0);">
			<td><?php //echo htmlentities($ligne["index"]); ?></td>
			<td><?php echo htmlentities($societe_description); ?></td>
			<td><?php echo htmlentities($ligne["description"]); ?></td>
			<td><?php echo htmlentities($ligne_activite["description"]); ?></td>
			<td><?php echo ($liste_imputations[$ligne["projid"]."_".$ligne_activite["actid"]]["facturable"]?"Oui":"Non"); ?></td>
			<td><?php echo htmlentities($liste_imputations[$ligne["projid"]."_".$ligne_activite["actid"]]["type"]); ?></td>
			<td><?php echo htmlentities($liste_imputations[$ligne["projid"]."_".$ligne_activite["actid"]]["nom"]); ?></td>
			<td>
<?php if (!empty($liste_imputations[$ligne["projid"]."_".$ligne_activite["actid"]]["index"])): ?>
<!--				<a href="javascript:imput_update('<?php echo $ligne["e_environnement_id"]; ?>', '<?php echo $ligne["user"]; ?>');"><img src="../spip/img/b_edit.png" alt="Modifier" title="Modifier" /></a>//-->
				<a href="javascript:imput_delete('<?php echo $liste_imputations[$ligne["projid"]."_".$ligne_activite["actid"]]["index"]; ?>', '<?php echo $ligne["nom"]; ?>');"><img src="../spip/img/b_drop.png" alt="Supprimer" title="Supprimer" /></a>
<?php else: ?>
				<a href="javascript:imput_add('<?php echo $ligne["projid"]."', '".$ligne_activite["actid"] ?>');"><img src="../spip/img/plus_small.gif" alt="Ajouter" title="Ajouter" /></a>
<?php endif; ?>
			</td>
		</tr>
<?php		endif; ?>
<?php 	endforeach; ?>
<?php endforeach; ?>
		</tbody>
	</table>
<form name="f_imput_add" action="./imputation.php">
	<table border="0" cellpadding="5">
		<colgroup  style="background-color:yellow;" >
			<col width="30" />
			<col width="215" />
			<col width="215" />
			<col width="165" />
			<col width="80" />
		</colgroup>
		<colgroup>
			<col width="80" />
			<col width="162" />
		</colgroup>
		<tr>
			<td><input type="image" src="../spip/img/plus_small.gif" alt="Ajouter" title="Ajouter" /></td>
			<td><?php echo htmlentities($societe_description); ?></td>
			<td>
				<select name="imput_projid">
<?php foreach($liste_projets as $ligne): ?>
					<option value="<?php echo $ligne["projid"]; ?>"><?php echo htmlentities($ligne["description"]); ?></option>
<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="imput_actid">
<?php foreach($liste_activites as $ligne): ?>
					<option value="<?php echo $ligne["actid"]; ?>"><?php echo htmlentities($ligne["description"]); ?></option>
<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="imput_facturable">
					<option value="1">Oui</option>
					<option value="0">Non</option>
				</select>
			</td>
			<td>
				<select name="imput_type">
					<option value="OTP">OTP</option>
					<option value="CDC">CDC</option>
					<option value="ABS">ABS</option>
				</select>
			</td>
			<td><input type="text" name="imput_nom" size="15" /></td>
		</tr>
	</table>
	<input type="hidden" name="imput_action" value="imput_add" />
	<input type="hidden" name="imput_custid" value="<?php echo $societe_id; ?>" />
</form>
</div>
<?php
	mysql_close($db_connect);
?>
</body>
</html>