<?php
	$db_connect = null;
	$tab_projet = array();
	$ligne = null;
	$db_requete = null;
	$db_resultat = null;

	$db_connect = mysql_connect("127.0.0.1", "hrpic_admin", "hrpic_admin");

//	mysql_select_db('hrpic',$db_connect);
?>
<?php
//	print_r($_GET);
	$hrpic_action = $_GET["hrpic_action"];
	if (!empty($hrpic_action)) {
		switch($hrpic_action) {
			case "time_update" :
				$db_requete = "UPDATE hrpic.schema ";
				$db_requete.= "SET time = '".$_GET["hrpic_schema"]."' ";
				$db_requete.= "WHERE projet_id = '".$_GET["hrpic_projet_id"]."';";
				break;
			case "paie_update" :
				$db_requete = "UPDATE hrpic.schema ";
				$db_requete.= "SET paie = '".$_GET["hrpic_schema"]."' ";
				$db_requete.= "WHERE projet_id = '".$_GET["hrpic_projet_id"]."';";
				break;
			case "schema_add" :
				if(!empty($_GET["hrpic_time"]) || !empty($_GET["hrpic_paie"])) {
					$db_requete = "INSERT INTO hrpic.schema ";
					$db_requete.= "VALUES ('".$_GET["hrpic_projet_id"]."', '".$_GET["hrpic_time"]."', '".$_GET["hrpic_paie"]."');";
				}
				break;

			case "schema_delete" :

				if(!empty($_GET["hrpic_projet_id"])) {
					$db_requete = "DELETE FROM hrpic.schema ";
					$db_requete.= "WHERE projet_id = '".$_GET["hrpic_projet_id"]."';";
				}

				break;
		}

//		print $db_requete;
		if ($db_requete != null)
			$db_resultat = mysql_query($db_requete, $db_connect);

		if (!empty($_GET["hrpic_projet_id"]))

			header("Location: ./schema.php?ligne=l_".$_GET["hrpic_projet_id"]);

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
	function schema_update(projet_id, schema) {
		var password_nouveau;

		schema_nouveau = prompt("Nouveau schéma de " + schema +" :");
		if(schema_nouveau != null)
			if(schema == 'paie')
				window.open('http://www.cbl-consulting-nord.com/acces_client/schema.php?hrpic_action=paie_update'+'&hrpic_projet_id='+projet_id+'&hrpic_schema='+schema_nouveau, '_self');
			else
				window.open('http://www.cbl-consulting-nord.com/acces_client/schema.php?hrpic_action=time_update'+'&hrpic_projet_id='+projet_id+'&hrpic_schema='+schema_nouveau, '_self');
	}


	function schema_delete(projet_id, client) {
		var confirmation;

		confirmation = confirm("Voulez-vous vraiment effacer les données de " + client + "?");
		if(confirmation)
			window.open('http://www.cbl-consulting-nord.com/acces_client/schema.php?hrpic_action=schema_delete'+'&hrpic_projet_id='+projet_id, '_self');
	}


	function schema_add(projet_id, schema) {

		var i = 0;

		var liste_projet = window.document.forms["f_schema_add"].elements["hrpic_projet_id"];


		while(liste_projet.options[i].value != projet_id)

			i++;


		liste_projet.options[i].selected = true;

		if(schema == 'paie')
			window.document.forms["f_schema_add"].elements["hrpic_paie"].focus();

		else
			window.document.forms["f_schema_add"].elements["hrpic_time"].focus();
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
-->
</style>
</head>

<body <?php echo ((!empty($_GET["ligne"]))?'onload="javascript:change_couleur_ligne(\''.$_GET["ligne"].'\', 1);"':''); ?>>
<div>
	<div id="haut"><a href="http://www.cbl-consulting-nord.com">&lt;&lt; Retour accueil</a></div>
	<hr />
	<div>
		<a href="http://www.cbl-consulting-nord.com/acces_client/user.php">&lt;Vue des utilisateurs&gt;</a>
		<a href="http://www.cbl-consulting-nord.com/acces_client/environnement.php">&lt;Vue des environnements&gt;</a>
		&lt;Vue des sch&eacute;mas&gt;
	</div>
</div>
<?php
$db_requete = " 
SELECT DISTINCT p.projid AS projet_id, p.description AS client, s.time AS time, s.paie AS paie 
FROM hrpic.environnement e 
JOIN rodeo.projects p ON p.projid = e.projet_id 
LEFT JOIN hrpic.schema s ON p.projid = s.projet_id
WHERE p.custid = 1
ORDER BY client;
";

/*
	$db_requete = "SELECT DISTINCT p.projid AS projet_id, p.description AS client, s.time AS time, s.paie AS paie ";
	$db_requete.= "FROM rodeo.projects p, hrpic.environnement e ";
	$db_requete.= "LEFT JOIN hrpic.schema s ON (p.projid = s.projet_id) ";
	$db_requete.= "WHERE p.custid = 1 ";
	$db_requete.= "AND p.projid = e.projet_id ";
	$db_requete.= "ORDER BY client;";
*/

//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
?>
<div>
	<br />
	<table border="0" cellpadding="5">
		<colgroup  style="background-color:yellow;" >
			<col width="30" />
			<col width="250"/>
		</colgroup>
		<colgroup>
			<col width="80" />
			<col width="30" />
			<col width="80" />
			<col width="30" />
		</colgroup>
		<thead>
		<tr>
			<th>&nbsp;<br />&nbsp;</th>
			<th>Client</th>
			<th colspan="2">Time</th>
			<th colspan="2">Paie</th>
		</tr>
		</thead>
		<tbody>
<?php while ($ligne = mysql_fetch_assoc($db_resultat)): ?>
<?php //print_r($ligne); ?>
		<tr id="l_<?php echo $ligne["projet_id"]; ?>" onclick="javascript:change_couleur_ligne('l_<?php echo $ligne["projet_id"]; ?>', 0);">
			<td>&nbsp;</td>
			<td><?php echo htmlentities($ligne["client"]); ?></td>
			<td><?php echo htmlentities($ligne["time"]); ?></td>
			<td>
<?php if (!empty($ligne["time"])): ?>
				<a href="javascript:schema_update('<?php echo $ligne["projet_id"]; ?>', 'time');"><img src="../spip/img/b_edit.png" alt="Modifier" title="Modifier" /></a>

				<a href="javascript:schema_delete('<?php echo $ligne["projet_id"]; ?>');"><img src="../spip/img/b_drop.png" alt="Supprimer" title="Supprimer" /></a>

<?php else: ?>

				<a href="javascript:schema_add('<?php echo $ligne["projet_id"]; ?>', 'time');"><img src="../spip/img/plus_small.gif" alt="Ajouter" title="Ajouter" /></a>
<?php endif; ?>
			</td>
			<td><?php echo htmlentities($ligne["paie"]); ?></td>
			<td>
<?php if (!empty($ligne["paie"])): ?>
				<a href="javascript:schema_update('<?php echo $ligne["projet_id"]; ?>', 'paie');"><img src="../spip/img/b_edit.png" alt="Modifier" title="Modifier" /></a>

				<a href="javascript:schema_delete('<?php echo $ligne["projet_id"]; ?>');"><img src="../spip/img/b_drop.png" alt="Supprimer" title="Supprimer" /></a>

<?php else: ?>

				<a href="javascript:schema_add('<?php echo $ligne["projet_id"]; ?>', 'paie');"><img src="../spip/img/plus_small.gif" alt="Ajouter" title="Ajouter" /></a>
<?php endif; ?>
			</td>
		</tr>
<?php endwhile; ?>
		</tbody>
	</table>
<form name="f_schema_add" action="./schema.php">
	<table border="0" cellpadding="5">
		<colgroup  style="background-color:yellow;" >
			<col width="30" />
			<col width="250"/>
		</colgroup>
		<colgroup>
			<col width="110" />
			<col width="110" />
		</colgroup>
		<tr>
			<td><input type="image" src="../spip/img/plus_small.gif" alt="Ajouter" title="Ajouter" /></td>
			<td>
<?php
	$db_requete = "SELECT DISTINCT p.projid AS projet_id, p.description AS client ";
	$db_requete.= "FROM rodeo.projects p, hrpic.environnement e  ";
	$db_requete.= "WHERE p.custid = 1 ";
	$db_requete.= "AND p.projid NOT IN (SELECT s.projet_id FROM hrpic.schema s, hrpic.environnement e WHERE s.projet_id = e.projet_id AND s.time != '' AND s.paie != '') ";
	$db_requete.= "AND p.projid = e.projet_id ";
	$db_requete.= "ORDER BY client;";

//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
?>
				<select name="hrpic_projet_id">
<?php while ($ligne = mysql_fetch_assoc($db_resultat)): ?>
					<option value="<?php echo $ligne["projet_id"]; ?>"><?php echo htmlentities($ligne["client"]); ?></option>
<?php endwhile; ?>
				</select>
			</td>
			<td><input type="text" name="hrpic_time" size="10" /></td>
			<td><input type="text" name="hrpic_paie" size="10" /></td>
		</tr>
	</table>
	<input type="hidden" name="hrpic_action" value="schema_add" />
</form>
</div>
<?php
	mysql_close($db_connect);
?>
</body>
</html>

