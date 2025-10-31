<?php
	setlocale (LC_TIME, 'fr-fr');
	
	$db_connect = null;
	$ligne = null;
	$db_requete = null;
	$db_requete_option = null;
	$db_resultat = null;
	
	$liste_users = array();
	$liste_projets = array();
	$liste_activite = array();
	
	$data_heures = array();
	$data_heures_cumul = array();
	$data_heures_cumul_tfoot = null;
	$date_heures_detail_tfoot = array();
	
	$jour1_semaine1 = 0;

	$db_connect = mysql_connect("127.0.0.1", "root", "d10lillE2005");

//  Liste des utilisateurs
	$db_requete = " 
		SELECT userid, name, surname, nick 
		FROM rodeo.users 
		ORDER BY surname, name;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_users[$ligne["userid"]] = $ligne; 
	mysql_free_result($db_resultat);
//	print_r($liste_users);
	
//  Liste des projets
	$db_requete = " 
		SELECT projid, description 
		FROM rodeo.projects 
		WHERE custid = 1
		ORDER BY description;
	";
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_projets[$ligne["projid"]] = $ligne;
	$ligne["projid"] = 400;
	$ligne["description"] = "zzz Absence";
	$liste_projets[$ligne["projid"]] = $ligne;
	mysql_free_result($db_resultat);
//	print_r($liste_projets);
	
//  Liste des activités
	$db_requete = " 
		SELECT actid, name, description 
		FROM rodeo.activities;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_activites[$ligne["actid"]] = $ligne; 
	$ligne["actid"] = 400;
	$ligne["description"] = "zzz Abs";
	$liste_activites[$ligne["actid"]] = $ligne;
	mysql_free_result($db_resultat);
//	print_r($liste_activites);
	
	$mois_choisi = (empty($_POST['select_mois'])?$_GET['select_mois']:$_POST['select_mois']);
	if(empty($mois_choisi)) 
		$mois_choisi = (int)date("m",mktime(0,0,0,date("m"),1,date("Y")));
	$an_choisi = (empty($_POST['select_ans'])?$_GET['select_ans']:$_POST['select_ans']);
	if(empty($an_choisi))
		$an_choisi = date('Y');
	$user_choisi = (empty($_POST['user_select'])?$_GET['user_select']:$_POST['user_select']);
	if(empty($user_choisi))
		$user_choisi = "*";
	$project_choisi = (empty($_POST['project_select'])?$_GET['project_select']:$_POST['project_select']);
	if(empty($project_choisi)) {
		$project_choisi = "*";
		$_POST['detail_client'] = "1";
	}

	$detail_client_choisi = (int)$_POST['detail_client'];
	$detail_activite_choisi = (int)$_POST['detail_activite'];
	$detail_theme_choisi = (int)$_POST['detail_theme'];
	$detail_user_choisi = (int)$_POST['detail_user'];
	
	$activite_gestionnaire_choisi = (int)$_POST['activite_gestionnaire'];
	$activite_autre_choisi = (int)$_POST['activite_autre'];
	
//  Extraction des données
	if(!empty($user_choisi) && !empty($project_choisi)){
		$nom_table = 'reports_'.$an_choisi;
	
// 		La première semaine ne contient pas forcément le premier janvier
		$jour1_semaine1 = 1;
		if($an_choisi == 2010 and $mois_choisi == 1) $jour1_semaine1 = 4;
		if($an_choisi == 2011 and $mois_choisi == 1) $jour1_semaine1 = 3;
		if($an_choisi == 2012 and $mois_choisi == 1) $jour1_semaine1 = 2;
	
// 		On détermine la période en semaine et jour
		$time_mois_debut = mktime(0,0,0,$mois_choisi,$jour1_semaine1,$an_choisi);
		$semaine_min = date('W',$time_mois_debut);
		$time_mois_fin = mktime(0,0,0,$mois_choisi+1,1-1,$an_choisi);
		$semaine_max = date('W',$time_mois_fin);
// 		Attention : le mois de décembre peut se terminer en semaine 1!
		$a_cheval = false;
		if ($semaine_max == 1) {
			$semaine_max = 52;
			$a_cheval = true;
		}
//	 	Ou en semaine 53
		if ($semaine_min == '53') {
			$semaine_min = '01';
		}
	
		$db_requete_option = "";
		if($user_choisi != '*')
			$db_requete_option .= "userid='$user_choisi' and ";
		if($project_choisi != '*')
			$db_requete_option .= "projid='$project_choisi' and ";
		if($activite_gestionnaire_choisi + $activite_autre_choisi == 0)
			$activite_gestionnaire_choisi = $activite_autre_choisi = 1;
		if($activite_gestionnaire_choisi + $activite_autre_choisi < 2) {
			if($activite_gestionnaire_choisi == 1)
				$db_requete_option .= "(actid='20' OR actid='27') AND ";
			if($activite_autre_choisi == 1)
				$db_requete_option .= "(actid!='20' AND actid!='27') AND ";
		}
		
		$db_requete = " 
			SELECT projid, actid, LOWER(TRIM(theme)) as theme, userid, week, monday, tuesday, wednesday, thursday, friday, saturday, sunday
			FROM rodeo.".$nom_table."
			WHERE		
		";
		$db_requete .= $db_requete_option;
		$db_requete .= "week >= '$semaine_min' and week <= '$semaine_max'"; 	
//		print $db_requete;
		$db_resultat = mysql_query($db_requete, $db_connect);
		while($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC)) {
//			Ne pas bouger la ligne suivante
			if($ligne["projid"] == 400) {
				$ligne["theme"] = "zzz Abs";
				$ligne["actid"] = 400;
			}
			if(!$detail_client_choisi)
				$ligne["projid"] = null;
			if(!$detail_activite_choisi)
				$ligne["actid"] = null;
			if(!$detail_theme_choisi)
				$ligne["theme"] = null;
			if(!$detail_user_choisi)
				$ligne["userid"] = null;
			
//			/^hrx ?    <= chaîne débutant par "hrx", éventuellement suivie d'un espace
//			[xnz0-9]{3} <= répétition de 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
			if(preg_match("/^hrx ?[xnz0-9]{3}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^hrx ?/i", "", $ligne["theme"]);
//			/^ano ?    <= chaîne débutant par "ano", éventuellement suivie d'un espace
//			[xnz0-9]{3} <= répétition de 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
			if(preg_match("/^ano ?[xnz0-9]{3}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^ano ?/i", "", $ligne["theme"]);
//			/^evol? ?    <= chaîne débutant par "evo", éventuellement suivie d'un "l" et/ou d'un espace
//			[xnz0-9]{3} <= répétition de 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
			if(preg_match("/^evol? ?[xnz0-9]{3}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^evol? ?/i", "", $ligne["theme"]);
//			remplacement des caractères accentués
			$ligne["theme"] = preg_replace("/[éèê]/i", "e", $ligne["theme"]);
			array_push($data_heures, $ligne);
		}

		if ($a_cheval) {
			$an_suivant = $an_choisi + 1;
			$nom_table = 'reports_'.$an_suivant;
			$db_requete = " 
				SELECT projid, actid, LOWER(TRIM(theme)) as theme, userid, week, monday, tuesday, wednesday, thursday, friday, saturday, sunday
				FROM rodeo.".$nom_table."
				WHERE		
			";
			$db_requete .= $db_requete_option;
			$db_requete .= "week = '1'";
//			print $db_requete;			
			$db_resultat = mysql_query($db_requete, $db_connect);
			while($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC)) {
//				Ne pas bouger la ligne suivante
				if($ligne["projid"] == 400) {
					$ligne["theme"] = null;
					$ligne["actid"] = null;
				}
				if(!$detail_client_choisi)
					$ligne["projid"] = null;
				if(!$detail_activite_choisi)
					$ligne["actid"] = null;
				if(!$detail_theme_choisi)
					$ligne["theme"] = null;
				if(!$detail_user_choisi)
					$ligne["userid"] = null;
				array_push($data_heures, $ligne);
			}
		}
//		print_r($data_heures);
	}

// 	Quel jour de la semaine sont le debut et la fin de mois?
// 	Lundi = 1, Dimanche = 7
	$jour_semaine_deb = (date('w',$time_mois_debut)==0)?7:date('w',$time_mois_debut);
	$jour_semaine_fin = (date('w',$time_mois_fin)==0)?7:date('w',$time_mois_fin);
	
	if($data_heures != null){
		foreach($data_heures as $ligne){
			$valeur_du_jour=array(	1=>$ligne["monday"],
									2=>$ligne["tuesday"],
									3=>$ligne["wednesday"],
									4=>$ligne["thursday"],
									5=>$ligne["friday"],
									6=>$ligne["saturday"],
									7=>$ligne["sunday"]
								);
			$i_min = ($ligne["week"] == $semaine_min)?$jour_semaine_deb:1;	
			$i_max = (($ligne["week"] == $semaine_max AND !$a_cheval) OR ($a_cheval AND $ligne["week"] == 1))?$jour_semaine_fin:7;
			
			for($i = $i_min;$i <= $i_max; $i++) {	
				if(floatval($valeur_du_jour[$i]) > 0) {
//					cumul de la semaine pour ce projet
					$data_heures_cumul[$ligne["projid"]][$ligne["actid"]][$ligne["theme"]][$ligne["userid"]] += $valeur_du_jour[$i];
					$data_heures_cumul_tfoot += $valeur_du_jour[$i];
					// detail de la semaine pour ce projet
					$jour_encours = ($ligne["week"]-$semaine_min)*7 + $i - $jour_semaine_deb + $jour1_semaine1;
					if($ligne["week"] == 1 and $a_cheval)
						$jour_encours = (53-$semaine_min)*7 + $i - $jour_semaine_deb + $jour1_semaine1;
//					echo $jour_encours."_".$i."_".$semaine_min."_".$semaine_max."_".$jour_semaine_deb."_".$jour1_semaine1."<br />";
					$date_heures_detail[$ligne["projid"]][$ligne["actid"]][$ligne["theme"]][$ligne["userid"]][$jour_encours] += $valeur_du_jour[$i];
//					print_r($date_heures_detail);
					$data_heures_detail_tfoot[$jour_encours] += $valeur_du_jour[$i];
				}
			}
		}
	}
//	print_r($data_heures_cumul);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Statistiques Rodeo (Mensuelles)</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="expires" content="0" />
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

<body>
<div>
	<div id="haut"><a href="http://www.cbl-consulting-nord.com">&lt;&lt; Retour accueil</a></div>
	<hr />
</div>
<form action="rodeo_stats.php" method="post" class="formulaire">
<table>
	<tr>
		<td>Client</td>
		<td colspan="2">
			<select name="project_select">
				<option value="*" <?php echo ($project_choisi=="*"?'selected="selected"':''); ?>>Tous les projets</option>
<?php foreach($liste_projets as $ligne): ?>
				<option value="<?php echo $ligne["projid"]; ?>" <?php echo ($project_choisi==$ligne["projid"]?'selected="selected"':''); ?>><?php echo htmlentities($ligne["description"]); ?></option>
<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<td>Utilisateur</td>
		<td colspan="2">
			<select name="user_select">
				<option value="*" <?php echo ($user_choisi=="*"?'selected="selected"':''); ?>>Tous les users</option>
<?php foreach($liste_users as $ligne): ?>
				<option value="<?php echo $ligne["userid"]; ?>" <?php echo ($user_choisi==$ligne["userid"]?'selected="selected"':''); ?>><?php echo htmlentities($ligne["name"])." ".htmlentities($ligne["surname"]); ?></option>
<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<td>P&eacute;riode</td>
		<td colspan="2">
			<select name="select_mois">
<?php for($i=1;$i<=12;$i++): ?>
				<option value="<?php echo $i; ?>" <?php echo (($mois_choisi==$i)?'selected="selected"':''); ?>><?php echo htmlentities(strftime("%B",mktime(0,0,0,$i,'01','2007')));?></option>
<?php endfor; ?>
			</select>
			<select name="select_ans">
<?php for($i=2012;$i>=2007;$i--): ?>
				<option value="<?php echo $i; ?>" <?php echo (($an_choisi==$i)?'selected="selected"':''); ?>><?php echo $i; ?></option>
<?php endfor; ?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" name="go" value="GO" />
		</td>
		<td style="vertical-align:top;">
			<b>Niveau de d&eacute;tail :</b>
			<br /><input type="checkbox" name="detail_client" value="1" <?php echo ($detail_client_choisi == 1?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par client
			<br /><input type="checkbox" name="detail_activite" value="1" <?php echo ($detail_activite_choisi == 1?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par activit&eacute;
			<br /><input type="checkbox" name="detail_theme" value="1" <?php echo ($detail_theme_choisi == 1?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par th&egrave;me
			<br /><input type="checkbox" name="detail_user" value="1" <?php echo ($detail_user_choisi == 1?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par user
		</td>
		<td style="vertical-align:top;">
			<b>Secteur d'activit&eacute; :</b>
			<br /><input type="checkbox" name="activite_gestionnaire" value="1" <?php echo ($activite_gestionnaire_choisi == 1?' checked="checked"':''); ?> />
			Activit&eacute; "Gestionnaire"
			<br /><input type="checkbox" name="activite_autre" value="1" <?php echo ($activite_autre_choisi == 1?' checked="checked"':''); ?> />
			Activit&eacute; "Autre"
		</td>
	</tr>
</table>
</form>
<br />
<table class="report">
	<colgroup style="background-color:yellow;" >
<?php if($detail_client_choisi == 1): ?>
		<col width="200" />
<?php endif; ?>
<?php if($detail_activite_choisi == 1): ?>
		<col width="200"/>
<?php endif; ?>
<?php if($detail_theme_choisi == 1): ?>
		<col width="90" />
<?php endif; ?>
<?php if($detail_user_choisi == 1): ?>
		<col width="80" />
<?php endif; ?>
	</colgroup>
	<colgroup>
		<col width="80" />
		<col width="80" />
	</colgroup>
	<thead>
		<tr>
<?php $j = 0; ?>
<?php if($detail_client_choisi == 1): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Client</a></th>
<?php endif; ?>
<?php if($detail_activite_choisi == 1): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Activit&eacute;</a></th>
<?php endif; ?>
<?php if($detail_theme_choisi == 1): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Th&egrave;me</a></th>
<?php endif; ?>
<?php if($detail_user_choisi == 1): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">User</a></th>
<?php endif; ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Temps (h)</a></th>
			<th>Temps (j)</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="<?php echo $detail_client_choisi+$detail_activite_choisi+$detail_theme_choisi+$detail_user_choisi; ?>">Total</td>
			<td class="valeur"><?php echo htmlentities($data_heures_cumul_tfoot); ?></td>
			<td class="valeur"><?php echo htmlentities($data_heures_cumul_tfoot/8); ?></td>
		</tr>
	</tfoot>
	<tbody style="overflow: auto; height: 300px; overflow-x: hidden;">
<?php foreach($data_heures_cumul as $projet_id=>$projet_data): ?>
<?php 	foreach($projet_data as $activite_id=>$activite_data): ?>
<?php 		foreach($activite_data as $theme_id=>$theme_data): ?>
<?php 			foreach($theme_data as $user_id=>$user_heures): ?>
		<tr>
<?php if($detail_client_choisi == 1): ?>
			<td><?php echo htmlentities($liste_projets[$projet_id]["description"]); ?> </td>
<?php endif; ?>
<?php if($detail_activite_choisi == 1): ?>
			<td><?php echo htmlentities($liste_activites[$activite_id]["description"]); ?> </td>
<?php endif; ?>
<?php if($detail_theme_choisi == 1): ?>
			<td><?php echo htmlentities($theme_id); ?> </td>
<?php endif; ?>
<?php if($detail_user_choisi == 1): ?>
			<td><?php echo htmlentities($liste_users[$user_id]["nick"]); ?> </td>
<?php endif; ?>
			<td class="valeur"><?php echo htmlentities($user_heures); ?> </td>
			<td class="valeur"><?php echo htmlentities($user_heures/8); ?> </td>
		</tr>
<?php 			endforeach; ?>
<?php 		endforeach; ?>
<?php 	endforeach; ?>
<?php endforeach; ?>
	</tbody>
</table>
<br />
<table class="report">
	<colgroup style="background-color:yellow;" >
<?php if($detail_client_choisi == 1): ?>
		<col width="200" />
<?php endif; ?>
<?php if($detail_activite_choisi == 1): ?>
		<col width="200"/>
<?php endif; ?>
<?php if($detail_theme_choisi == 1): ?>
		<col width="90" />
<?php endif; ?>
<?php if($detail_user_choisi == 1): ?>
		<col width="80" />
<?php endif; ?>
	</colgroup>
	<colgroup>
<?php for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
		<col width="25" />
<?php endfor; ?>
	</colgroup>
	<thead style="overflow: auto; height: 300px; overflow-x: hidden;">
		<tr>
<?php $j = 0; ?>
<?php if($detail_client_choisi == 1): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Client</a></th>
<?php endif; ?>
<?php if($detail_activite_choisi == 1): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Activit&eacute;</a></th>
<?php endif; ?>
<?php if($detail_theme_choisi == 1): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Th&egrave;me</a></th>
<?php endif; ?>
<?php if($detail_user_choisi == 1): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">User</a></th>
<?php endif; ?>
<?php for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
			<th><?php echo $i; ?></th>
<?php endfor; ?>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="<?php echo $detail_client_choisi+$detail_activite_choisi+$detail_theme_choisi+$detail_user_choisi; ?>">Total</td>
<?php for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
			<td class="valeur"><?php echo htmlentities($data_heures_detail_tfoot[$i]); ?></td>
<?php endfor; ?>
		</tr>
	</tfoot>
	<tbody>
<?php foreach($data_heures_cumul as $projet_id=>$projet_data): ?>
<?php 	foreach($projet_data as $activite_id=>$activite_data): ?>
<?php 		foreach($activite_data as $theme_id=>$theme_data): ?>
<?php 			foreach($theme_data as $user_id=>$user_heures): ?>
		<tr>
<?php if($detail_client_choisi == 1): ?>
			<td><?php echo htmlentities($liste_projets[$projet_id]["description"]); ?> </td>
<?php endif; ?>
<?php if($detail_activite_choisi == 1): ?>
			<td><?php echo htmlentities($liste_activites[$activite_id]["description"]); ?> </td>
<?php endif; ?>
<?php if($detail_theme_choisi == 1): ?>
			<td><?php echo htmlentities($theme_id); ?> </td>
<?php endif; ?>
<?php if($detail_user_choisi == 1): ?>
			<td><?php echo htmlentities($liste_users[$user_id]["nick"]); ?> </td>
<?php endif; ?>
<?php				for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
<?php					$valeur = $date_heures_detail[$projet_id][$activite_id][$theme_id][$user_id][$i];
						if(intval($valeur) == $valeur)
							$valeur=intval($valeur);
						if(floatval($valeur) == 0)
							$valeur = '&nbsp;';
?>
			<td class="valeur"><?php echo $valeur; ?></td>
<?php				endfor; ?>
		</tr>
<?php 			endforeach; ?>
<?php 		endforeach; ?>
<?php 	endforeach; ?>
<?php endforeach; ?>
	</tbody>
</table>
<?php
	mysql_close($db_connect);
?>
</body>
</html>