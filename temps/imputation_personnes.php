<?php
	include('../hrpic_tools/rodeo_stats_inc_init.php');
	
	$liste_projets_gestionnaire = $_POST['project_gestionnaire_select'];
	$liste_projets_consultant = $_POST['project_consultant_select'];
	
	$imput_action = $_POST["imput_action"];
		
	if (!empty($imput_action)) {
		switch($imput_action) {
			case "imput_modif" :
				if(!empty($user_choisi[0])){
					$db_requete = "DELETE FROM rodeo.project_responsable WHERE userid = $user_choisi[0];";
					//print_r($db_requete);
					$db_resultat = mysqli_query($db_connect, $db_requete);
					if(!empty($liste_projets_gestionnaire))
						foreach($liste_projets_gestionnaire as $projet_id) {
							$db_requete = "INSERT INTO rodeo.project_responsable ";
							$db_requete.= "(projid, userid, responsable_activite) ";
							$db_requete .= "VALUES ($projet_id, $user_choisi[0], 'g');";
							//print_r($db_requete);
							$db_resultat = mysqli_query($db_connect, $db_requete);
						}
					if(!empty($liste_projets_consultant))
						foreach($liste_projets_consultant as $projet_id) {
							$db_requete = "INSERT INTO rodeo.project_responsable ";
							$db_requete.= "(projid, userid, responsable_activite) ";
							$db_requete .= "VALUES ($projet_id, $user_choisi[0], 'c');";
							//print_r($db_requete);
							$db_resultat = mysqli_query($db_connect, $db_requete);
						}
				}
				break;
		}
	}
	
	unset($liste_projets_gestionnaire);
	unset($liste_projets_consultant);
	unset($project_choisi);
	$liste_projets_gestionnaire = array();
	$liste_projets_consultant = array();
	$project_choisi = array();
	
//  Liste des projets gestionnaire
	$db_requete = " 
		SELECT p.projid, p.description, p.project_type_id 
		FROM rodeo.projects p, rodeo.project_responsable pr
		WHERE responsable_activite = 'g'
		  AND p.projid = pr.projid
		  AND pr.userid = $user_choisi[0];
	";
//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_projets_gestionnaire[$ligne["projid"]] = $ligne;
	mysqli_free_result($db_resultat);
//	print_r($liste_projets_gestionnaire); echo "<br />";

//  Liste des projets consultant
	$db_requete = " 
		SELECT p.projid, p.description, p.project_type_id 
		FROM rodeo.projects p, rodeo.project_responsable pr
		WHERE responsable_activite = 'c'
		  AND p.projid = pr.projid
		  AND pr.userid = $user_choisi[0];
	";
//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_projets_consultant[$ligne["projid"]] = $ligne;
	mysqli_free_result($db_resultat);
//	print_r($liste_projets_consultant); echo "<br />";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Intranet de CBL Consulting Nord - Acc&egrave;s client</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="expires" content="0" />
<script type="text/javascript">
<!--
	function charger_objet(liste_source, liste_cible) {
		var m1 = window.document.forms["f_imput_modif"].elements[liste_source];
		var m2 = window.document.forms["f_imput_modif"].elements[liste_cible];
		
		for ( i=0; i<m1.length ; i++)
			if (m1.options[i].selected == true )
			   m2.options[m2.length] = new Option(m1.options[i].text, m1.options[i].value);
	}
	
	function decharger_objet(liste_source) {
		var m1 = window.document.forms["f_imput_modif"].elements[liste_source];
		
		for ( i = (m1.length -1); i>=0; i--)
			if (m1.options[i].selected == true )
				//m1.options[i] = null;
				m1.remove(i);

	}
	
	function selectionner_liste(liste1) {
		var m1 = window.document.forms["f_imput_modif"].elements[liste1];
		
		for ( i=0; i<m1.length ; i++)
			m1.options[i].selected = 'selected'
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

<body>
<div>
	<div id="haut"><a href="http://lille.hr-path.local">&lt;&lt; Retour accueil</a></div>
	<hr />
	<div>
		&lt;Imputation des personnes&gt;
	</div>
</div>
<div>
	<br />
<form name="f_user_select" action="./imputation_personnes.php" method="post" class="formulaire">
	Personne
	<select name="user_select[]">
<?php foreach($liste_managers as $ligne_manager): ?>
		<optgroup label="<?php echo htmlentities((!$ligne_manager["userid"])?"Autre manager":$liste_users[$ligne_manager["userid"]]["nom_complet"]); ?>">
<?php 	foreach($liste_users as $ligne): ?>
<?php		if($ligne["managerid"] == $ligne_manager["userid"]): ?>
<?php 			if($ligne["surname"][0] != '#'): ?>
			<option value="<?php echo $ligne["userid"]; ?>" <?php echo (in_array($ligne["userid"], $user_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["name"])." ".htmlentities($ligne["surname"]); ?></option>
<?php 			endif; ?>
<?php 		endif; ?>
<?php 	endforeach; ?>
<?php 	foreach($liste_users as $ligne): ?>
<?php		if($ligne["managerid"] == $ligne_manager["userid"]): ?>
<?php 			if($ligne["surname"][0] == '#'): ?>
<?php				$ligne["surname"][0] = ''; ?>
			<option value="<?php echo $ligne["userid"]; ?>" <?php echo (in_array($ligne["userid"], $user_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["nom_complet"]); ?></option>
<?php 			endif; ?>
<?php 		endif; ?>
<?php 	endforeach; ?>
		</optgroup>
<?php endforeach; ?>
	</select>
	<input type="hidden" name="imput_action" value="user_select" />
	<input type="submit" value="OK" />
</form>
</div>
<div>
<form name="f_imput_modif" action="./imputation_personnes.php" method="post">
<table>
	<thead>
	<tr>
		<th>Comptes gestionnaire</th>
		<th colspan="3">Comptes</th>
		<th>Comptes consultant</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>
			<select name="project_gestionnaire_select[]" size="10" multiple="multiple">
<?php foreach($liste_projets_gestionnaire as $ligne): ?>
				<option value="<?php echo $ligne["projid"]; ?>" <?php echo (in_array($ligne["projid"], $project_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["description"]); ?></option>
<?php endforeach; ?>
			</select>
		</td>
		<td>
			<input type="button" onClick="javascript:charger_objet('project_select[]','project_gestionnaire_select[]');" value="&lt;&lt;" /><br />
			<input type="button" onClick="javascript:decharger_objet('project_gestionnaire_select[]');" value="&gt;&gt;" />			
		</td>
		<td>
			<select name="project_select[]" size="10" multiple="multiple">
<?php foreach($liste_customers as $ligne_customer): ?>
				<optgroup label="<?php echo "-----------------------------------".$ligne_customer["description"]."-----------------------------------"; ?>">
<?php 	foreach($liste_project_type as $ligne_project_type): ?>
					<optgroup label="<?php echo $ligne_project_type["libelle"]; ?>">
<?php 		foreach($liste_projets as $ligne): ?>
<?php			if(($ligne["project_type_id"] == $ligne_project_type["id"])&&($ligne["custid"] == $ligne_customer["custid"])): ?>
<?php				if($ligne["description"][0]!="#"): ?>
						<option value="<?php echo $ligne["projid"]; ?>" <?php echo (in_array($ligne["projid"], $project_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["description"]); ?></option>
<?php				endif; ?>
<?php 			endif; ?>
<?php 		endforeach; ?>
<?php 		foreach($liste_projets as $ligne): ?>
<?php			if(($ligne["project_type_id"] == $ligne_project_type["id"])&&($ligne["custid"] == $ligne_customer["custid"])): ?>
<?php				if($ligne["description"][0]=="#"): ?>
						<option value="<?php echo $ligne["projid"]; ?>" <?php echo (in_array($ligne["projid"], $project_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["description"]); ?></option>
<?php				endif; ?>
<?php 			endif; ?>
<?php 		endforeach; ?>
					</optgroup>
<?php 	endforeach; ?>
				</optgroup>
<?php endforeach; ?>
			</select>
		</td>
		<td>
			<input type="button" onClick="javascript:charger_objet('project_select[]','project_consultant_select[]');" value="&gt;&gt;" /><br />
			<input type="button" onClick="javascript:decharger_objet('project_consultant_select[]');" value="&lt;&lt;" />
		</td>
		<td>
			<select name="project_consultant_select[]" size="10" multiple="multiple">
<?php 	foreach($liste_projets_consultant as $ligne): ?>
				<option value="<?php echo $ligne["projid"]; ?>" <?php echo (in_array($ligne["projid"], $project_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["description"]); ?></option>
<?php 	endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="5">
			<input type="hidden" name="imput_action" value="imput_modif" />
			<input type="hidden" name="user_select[]" value="<?php echo $user_choisi[0]; ?>" />
			<input type="submit" value="OK" onClick="javascript:selectionner_liste('project_gestionnaire_select[]');selectionner_liste('project_consultant_select[]');" />
		</td>
	</tr>
	</tbody>
</table>
</form>
</div>
<?php
	mysqli_close($db_connect);
?>
</body>
</html>
