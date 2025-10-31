<?php
	
	$db_connect = null;
	$tab_projet = array();
	$ligne = null;
	$db_requete = null;
	$db_resultat = null;

	$db_connect = mysqli_connect("localhost", "rodeo_user", "123RoDeO_UsEr/456");


//	mysqli_select_db('hrpic',$db_connect);
?>
<?php
//	print_r($_GET);
	$hrpic_action = $_GET["hrpic_action"];
//	print $hrpic_action;
	if (!empty($hrpic_action)) {
		switch($hrpic_action) {
			case "environnement_update" :
//				$db_requete = "UPDATE hrpic.environnement ";
//				$db_requete.= "SET password = '".$_GET["hrpic_password"]."', date_modif = now() ";
//				$db_requete.= "WHERE user = '".$_GET["hrpic_user"]."'";
//				$db_requete.= "AND environnement_id = '".$_GET["hrpic_environnement_id"]."'";
				break;
			case "environnement_add" :
				if (!(empty($_GET["hrpic_id_syst"]) || empty($_GET["hrpic_serveur"]))) {
					$db_requete = "INSERT INTO hrpic.environnement ";
					$db_requete.= "(libelle, env_type_id, version_id, serveur, num_systeme, routeur, mandant, projet_id) ";
					$db_requete.= "VALUES (";
					$db_requete.= "'".$_GET["hrpic_id_syst"]."', ";
					$db_requete.= "'".$_GET["hrpic_type_env_id"]."', ";
					$db_requete.= "'".$_GET["hrpic_version_id"]."', ";
					$db_requete.= "'".$_GET["hrpic_serveur"]."', ";
					$db_requete.= "'".$_GET["hrpic_num_syst"]."', ";
					$db_requete.= "'".$_GET["hrpic_routeur"]."', ";
					$db_requete.= "'".$_GET["hrpic_mandant"]."', ";
					$db_requete.= "'".$_GET["hrpic_client_id"]."');";
				}
				break;

			case "environnement_delete" :

				if(!empty($_GET["hrpic_environnement_id"])) {
					$db_requete = "DELETE FROM hrpic.environnement ";
					$db_requete.= "WHERE id = '".$_GET["hrpic_environnement_id"]."';";
				}
				break;
		}
//		print $db_requete;
		if ($db_requete != null)
			$db_resultat = mysqli_query($db_connect, $db_requete);
	}
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Intranet de CBL Consulting Nord - Acc&egrave;s client</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="expires" content="0" />
<script type="text/javascript" src="../hrpic_tools/hrpic_tools_lib.js">
</script>
<script type="text/javascript">
<!--
	function field_update(environnementid, field) {
		var value;

		switch(field) {
			default:
				value = prompt("Nouvelle valeur :");
		}				
			
 		if(value != null) {
			environnementUpdate(refreshEnvLine, environnementid, field, value);
		}
	}
	function environnement_delete(environnement_id, environnement_nom, environnement_type) {
		var confirmation;
		confirmation = confirm("Voulez-vous vraiment effacer l'environnement " + environnement_nom + " (" + environnement_type + ") ?");
		if(confirmation)
			window.open('./environnement.php?hrpic_action=environnement_delete'+'&hrpic_environnement_id='+environnement_id, '_self');
	}

	function completer_char(texte, caractere, longueur) {
		var i;
		var longueur_texte = texte.length;
//		alert(longueur_texte);
		
		for (i=longueur_texte; i<longueur; i++) {
			texte = caractere + texte;
		}
		document.write(texte);
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

<body>
<?php
$db_requete = "
select e.*, p.projid AS client_id, p.description AS client, t.libelle AS type, t.id AS type_id,  v.libelle as version
from hrpic.environnement as e 
join rodeo.projects as p ON e.projet_id = p.projid 
join hrpic.env_type AS t ON e.env_type_id = t.id 
left JOIN hrpic.version_sap AS v ON (e.version_id = v.id)
where p.custid = 1
ORDER BY client, t.id, version ;
";

/*
	$db_requete = "SELECT e.*, p.projid AS client_id, p.description AS client, t.id AS type_id, t.libelle AS type, v.libelle AS version ";
	$db_requete.= "FROM hrpic.environnement e, rodeo.projects p, hrpic.env_type t ";
	$db_requete.= "LEFT JOIN hrpic.version_sap v ON (e.version_id = v.id) ";
	$db_requete.= "WHERE p.custid = 1 ";
	$db_requete.= "AND e.projet_id = p.projid ";
	$db_requete.= "AND e.env_type_id = t.id ";
//	$db_requete.= "AND e.version_id = v.id ";
	$db_requete.= "ORDER BY client, t.id, version ;";
*/


	$db_resultat = mysqli_query($db_connect, $db_requete);
?>
<div>
	<br />
	<table border="0" cellpadding="5">
		<colgroup  style="background-color:yellow;" >
			<col width="30" />
			<col width="250"/>
			<col width="180" />
			<col width="100" />
		</colgroup>
		<colgroup>
			<col width="225" />
			<col width="70" />
			<col width="80" />
			<col width="130" />
			<col width="50" />
		</colgroup>
		<thead>
		<tr>
			<th>&nbsp;</th>
			<th>Client</th>
			<th>Type</th>
			<th>Version</th>
			<th>Serveur d'application</th>
			<th>Num syst&egrave;me</th>
			<th>ID syst.</th>
			<th>Routeur</th>
			<th colspan="2">Mandant</th>
		</tr>
		</thead>
		<tbody>
<?php while ($ligne = mysqli_fetch_assoc($db_resultat)): ?>
<?php //print_r($ligne); ?>
		<!--<tr id="l_<?php echo $ligne["id"]; ?>_<?php echo $ligne["type"]; ?>" onclick="javascript:change_couleur_ligne('l_<?php echo $ligne["id"]; ?>_<?php echo $ligne["type"]; ?>', 0);"> ?> //-->
		<tr id="l_<?php echo $ligne["id"]; ?>" onclick="javascript:change_couleur_ligne('l_<?php echo $ligne["id"]; ?>', 0);">
			<td><a name="anc_<?php echo $ligne["client"]; ?>_<?php echo $ligne["type"]; ?>">&nbsp;</a></td>
			<td><?php echo htmlentities($ligne["client"]); ?></td>
			<td><?php echo htmlentities($ligne["type"]); ?></td>
			<td><?php echo htmlentities($ligne["version"]); ?></td>
			<td><p id="field_serveur_<?php echo $ligne["id"]; ?>" ondblclick="field_update('<?php echo $ligne["id"]; ?>', 'serveur')"><?php echo htmlentities($ligne["serveur"]); ?></p></td>
			<td>
				<p id="field_num_systeme_<?php echo $ligne["id"]; ?>" ondblclick="field_update('<?php echo $ligne["id"]; ?>', 'num_systeme')">
				<script type="text/javascript">
				<!--
					completer_char('<?php echo htmlentities($ligne["num_systeme"]); ?>', '0', 2);
				//-->
				</script>
				</p>
			</td>
			<td><p id="field_libelle_<?php echo $ligne["id"]; ?>" ondblclick="field_update('<?php echo $ligne["id"]; ?>', 'libelle')"><?php echo htmlentities($ligne["libelle"]); ?></p></td>
			<td><p id="field_routeur_<?php echo $ligne["id"]; ?>" ondblclick="field_update('<?php echo $ligne["id"]; ?>', 'routeur')"><?php echo htmlentities($ligne["routeur"]); ?></p></td>
			<td>
				<p id="field_mandant_<?php echo $ligne["id"]; ?>" ondblclick="field_update('<?php echo $ligne["id"]; ?>', 'mandant')">
				<script type="text/javascript">
				<!--
					completer_char('<?php echo htmlentities($ligne["mandant"]); ?>', '0', 3);
				//-->
				</script>
				</p>
			</td>

			<td>

				<a href="javascript:environnement_delete('<?php echo $ligne["id"]; ?>', '<?php echo $ligne["client"]; ?>', '<?php echo $ligne["type"]; ?>');"><img src="../img/b_drop.png" alt="Supprimer" title="Supprimer" /></a>

			</td>
		</tr>
<?php endwhile; ?>
		</tbody>
	</table>
<form action="./environnement.php">
	<table border="0" cellpadding="5">
		<colgroup  style="background-color:yellow;" >
			<col width="30" />
			<col width="250"/>
			<col width="180" />
			<col width="100" />
		</colgroup>
		<colgroup>
			<col width="225" />
			<col width="70" />
			<col width="80" />
			<col width="130" />
			<col width="80" />
		</colgroup>
		<tr>
			<td><input type="image" src="../img/eclair.gif" alt="Ajouter" title="Ajouter" /></td>
			<td>
<?php
	$db_requete = "SELECT p.projid AS client_id, p.description AS client ";
	$db_requete.= "FROM rodeo.projects p ";
	$db_requete.= "WHERE p.custid = 1 ";
	$db_requete.= "ORDER BY p.description ;";

//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
?>
				<select name="hrpic_client_id">
<?php while ($ligne = mysqli_fetch_assoc($db_resultat)): ?>
					<option value="<?php echo $ligne["client_id"]; ?>"><?php echo htmlentities($ligne["client"]); ?></option>
<?php endwhile; ?>
				</select>
			</td>
			<td>
<?php
	$db_requete = "SELECT t.id AS type_id, t.libelle AS type ";
	$db_requete.= "FROM hrpic.env_type t ";
	$db_requete.= "ORDER BY t.id ;";

//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
?>
				<select name="hrpic_type_env_id">
<?php while ($ligne = mysqli_fetch_assoc($db_resultat)): ?>
					<option value="<?php echo $ligne["type_id"]; ?>"><?php echo htmlentities($ligne["type"]); ?></option>
<?php endwhile; ?>
				</select>
			</td>
			<td>
<?php
	$db_requete = "SELECT v.id AS version_id, v.libelle AS version ";
	$db_requete.= "FROM hrpic.version_sap v ";
	$db_requete.= "ORDER BY v.libelle ;";

//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
?>
				<select name="hrpic_version_id">
<?php while ($ligne = mysqli_fetch_assoc($db_resultat)): ?>
					<option value="<?php echo $ligne["version_id"]; ?>"><?php echo htmlentities($ligne["version"]); ?></option>
<?php endwhile; ?>
				</select>
			</td>
			<td><input type="text" name="hrpic_serveur" size="28" /></td>
			<td><input type="text" name="hrpic_num_syst" size="3" maxlength="3" /></td>
			<td><input type="text" name="hrpic_id_syst" size="3" maxlength="3" /></td>
			<td><input type="text" name="hrpic_routeur" size="15" maxlength="20" /></td>
			<td><input type="text" name="hrpic_mandant" size="3" maxlength="3" /></td>
		</tr>
	</table>
	<input type="hidden" name="hrpic_action" value="environnement_add" />
</form>
</div>
<?php
	mysqli_close($db_connect);
?>
</body>
</html>

