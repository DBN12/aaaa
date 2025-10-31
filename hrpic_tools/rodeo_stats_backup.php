<?php
	setlocale (LC_TIME, 'fr-fr');
	
	$db_connect = null;
	$ligne = null;
	$db_requete = null;
	$db_requete_option = null;
	$db_resultat = null;
	
	$liste_users = array();
	$liste_managers = array();
	$liste_projets = array();
	$liste_activities = array();
	$liste_customers = array();
	$liste_customers_choisis = array();
	
	$data_heures = array();
	$data_heures_cumul = array();
	$date_heures_detail = array();
	$data_heures_cumul_tfoot = null;
	$date_heures_detail_tfoot = array();
	
	$chart1_choisi = null;
	$chart2_choisi = null;
	$chart1_option_choisi = null;
	$chart2_option_choisi = null;
	$extraction_option_choisi = null;
	$theme_content = "";
	
	$jour1_semaine1 = 0;
	
	$precision_chart = 3;

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
		
	$customer_choisi = (empty($_POST['customer_select'])?$_GET['customer_select']:$_POST['customer_select']);
	if((sizeof($customer_choisi) >= 1) && in_array('*', $customer_choisi)) {
		unset($customer_choisi);
		$customer_choisi = array();
	}
	if(empty($customer_choisi))
		$customer_choisi[0] = "*";

	mysql_free_result($db_resultat);
//	print_r($liste_customers); echo "<br />";
//	print_r($liste_customers_choisis); echo "<br />";
	
//  Liste des managers
	$db_requete = " 
		SELECT DISTINCT managerid
		FROM rodeo.users 
		WHERE managerid IS NOT NULL
		ORDER BY managerid;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_managers[$ligne["managerid"]] = $ligne; 
	mysql_free_result($db_resultat);
	$liste_managers["null"] = null;
//	print_r($liste_managers); echo "<br />";

//  Liste des utilisateurs
	$db_requete = " 
		SELECT userid, name, surname, nick, CONCAT_WS(' ', name, surname) AS nom_complet, managerid 
		FROM rodeo.users 
		ORDER BY surname, name;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_users[$ligne["userid"]] = $ligne; 
	mysql_free_result($db_resultat);
//	print_r($liste_users); echo "<br />";

//	Liste des types de projet
	$db_requete = "
		SELECT * FROM rodeo.project_type;";
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_project_type[$ligne["id"]] = $ligne;
	mysql_free_result($db_resultat);
//	print_r($liste_project_type); echo "<br />";
	
//  Liste des projets
	$db_requete = " 
		SELECT projid, description, project_type_id 
		FROM rodeo.projects ";
//  Filtrer par customer
	if($customer_choisi[0] != '*') {
		$db_requete .= "WHERE ";
		$taille_liste = sizeof($customer_choisi);
		foreach($customer_choisi as $customer_id) {
			$db_requete .= "custid = $customer_id ";
			if(--$taille_liste)
				$db_requete .= "OR ";
		}
		$db_requete .= "OR custid = 0 ";
	}
	$db_requete .= "
		ORDER BY description;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_projets[$ligne["projid"]] = $ligne;
	$ligne["projid"] = 400;
	$ligne["description"] = "zzz Absence";
	$liste_projets[$ligne["projid"]] = $ligne;
	mysql_free_result($db_resultat);
//	print_r($liste_projets); echo "<br />";
	
//  Liste des types d'activité
	$db_requete = " 
		SELECT * 
		FROM rodeo.activity_type;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_activity_type[$ligne["id"]] = $ligne; 
	mysql_free_result($db_resultat);
//	print_r($liste_activity_type); echo "<br />";
	
//  Liste des activités
	$db_requete = " 
		SELECT actid, name, description ,activity_type_id 
		FROM rodeo.activities
		ORDER BY description;
	";
//	print $db_requete;
	$db_resultat = mysql_query($db_requete, $db_connect);
	while ($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
		$liste_activities[$ligne["actid"]] = $ligne; 
	$ligne["actid"] = 400;
	$ligne["description"] = "zzz Abs";
	$liste_activities[$ligne["actid"]] = $ligne;
	mysql_free_result($db_resultat);
//	print_r($liste_activities); echo "<br />";
	
	$mois_choisi = (empty($_POST['select_mois'])?$_GET['select_mois']:$_POST['select_mois']);
	if(empty($mois_choisi)) 
		$mois_choisi = (int)date("m",mktime(0,0,0,date("m"),1,date("Y")));
	$an_choisi = (empty($_POST['select_ans'])?$_GET['select_ans']:$_POST['select_ans']);
	if(empty($an_choisi))
		$an_choisi = date('Y');
	
	$extraction_option_choisi = (int)$_POST['extraction_option'];
	$theme_content = (empty($_POST['theme_content'])?$_GET['theme_content']:$_POST['theme_content']);
	$user_choisi = (empty($_POST['user_select'])?$_GET['user_select']:$_POST['user_select']);
	if((sizeof($user_choisi) >= 1) && in_array('*', $user_choisi)) {
		unset($user_choisi);
		$user_choisi = array();
	}
	if(empty($user_choisi))
		$user_choisi[0] = "*";
	$project_choisi = (empty($_POST['project_select'])?$_GET['project_select']:$_POST['project_select']);
	if((sizeof($project_choisi) >= 1) && in_array('*', $project_choisi)) {
		unset($project_choisi);
		$project_choisi = array();
	}
	if(empty($project_choisi)) {
		$project_choisi[0] = "*";
	}
	$activity_choisi = (empty($_POST['activity_select'])?$_GET['activity_select']:$_POST['activity_select']);
	if((sizeof($activity_choisi) >= 1) && in_array('*', $activity_choisi)) {
		unset($activity_choisi);
		$activity_choisi = array();
	}
	if(empty($activity_choisi))
		$activity_choisi[0] = "*";
	$chart1_option_choisi = (int)$_POST['chart1_option'];
	if(empty($chart1_option_choisi)) {
		$chart1_option_choisi = 3;
	}
	$chart2_option_choisi = (int)$_POST['chart2_option'];
	if(empty($chart2_option_choisi)) {
		$chart2_option_choisi = 0;
	}

	$detail_client_choisi = (int)$_POST['detail_client'];
	$detail_activite_choisi = (int)$_POST['detail_activite'];
	$detail_otp_choisi = (int)$_POST['detail_otp'];
	$detail_theme_choisi = (int)$_POST['detail_theme'];
	$detail_libelle_choisi = (int)$_POST['detail_libelle'];
	$detail_user_choisi = (int)$_POST['detail_user'];
	
//	Au cas où rien n'aurait été choisi, on force à afficher au moins le détail par client
	if(!($detail_client_choisi+$detail_activite_choisi+$detail_theme_choisi+$detail_libelle_choisi+$detail_user_choisi))
		$detail_client_choisi = 1;
	
	$chart1_choisi = (int)$_POST['chart1'];
	$chart2_choisi = (int)$_POST['chart2'];
	
//  Extraction des données
	if(!empty($user_choisi) && !empty($project_choisi)){
		$nom_table = $an_choisi;
	
// 		La première semaine ne contient pas forcément le premier janvier
		$jour1_semaine1 = 1;
		if($an_choisi == 2010 and $mois_choisi == 1) $jour1_semaine1 = 4;
		if($an_choisi == 2011 and $mois_choisi == 1) $jour1_semaine1 = 3;
		if($an_choisi == 2012 and $mois_choisi == 1) $jour1_semaine1 = 2;
		if($an_choisi == 2013 and $mois_choisi == 1) $jour1_semaine1 = 1;
		if($an_choisi == 2014 and $mois_choisi == 1) $jour1_semaine1 = 1;
	
// 		On détermine la période en semaine et jour
		$time_mois_debut = mktime(0,0,0,$mois_choisi,$jour1_semaine1,$an_choisi);
		$semaine_min = date('W',$time_mois_debut);
		$time_mois_fin = mktime(0,0,0,$mois_choisi+1,1-1,$an_choisi);
		$semaine_max = date('W',$time_mois_fin);

// 		Attention : le mois de décembre peut se terminer en semaine 1!
		$a_cheval = false;
		if ($semaine_max == 1) {
			$a_cheval = true;
			$semaine_max = 52;
		}
//	 	Ou en semaine 53
		if ($semaine_min == 53) {
			$semaine_min = 01;
		}
	
		$db_requete_option = "";
		$db_requete_option .= "r.projid = p.projid AND ";
		if($customer_choisi[0] != '*') {
			$db_requete_option .= "(";
			$taille_liste = sizeof($customer_choisi);
			foreach($customer_choisi as $customer_id) {
				$db_requete_option .= "p.custid = $customer_id ";
				if(--$taille_liste)
					$db_requete_option .= "OR  ";
			}
			$db_requete_option .= " OR p.custid = 0) AND ";
		}
		if($user_choisi[0] != '*'){
			$db_requete_option .= "(";
			$taille_liste = sizeof($user_choisi);
			foreach($user_choisi as $user_id) {
				$db_requete_option .= "r.userid='$user_id' ";
				if(--$taille_liste)
					$db_requete_option .= "OR  ";
			}
			$db_requete_option .= ") AND ";
		}
		if($activity_choisi[0] != '*') {
			$db_requete_option .= "(";
			$taille_liste = sizeof($activity_choisi);
			foreach($activity_choisi as $activity_id){
				$db_requete_option .= "r.actid='$activity_id'  ";
				if(--$taille_liste)
					$db_requete_option .= "OR  ";
			}
			$db_requete_option .= ") AND ";
		}
		if($project_choisi[0] != '*') {
			$db_requete_option .= "(";
			$taille_liste = sizeof($project_choisi);
			foreach($project_choisi as $project_id){
				$db_requete_option .= "r.projid='$project_id'  ";
				if(--$taille_liste)
					$db_requete_option .= "OR  ";
			}
			$db_requete_option .= ") AND ";
		}
				
		if(!empty($theme_content))
			$db_requete_option .= "theme LIKE \"%".$theme_content."%\" AND ";
		
		$db_requete = "
		SELECT r.userid, r.week, SUM(r.monday) AS sum_1, SUM(r.tuesday) AS sum_2, SUM(r.wednesday) AS sum_3, SUM(r.thursday) AS sum_4, SUM(r.friday) AS sum_5, SUM(r.saturday) AS sum_6, SUM(r.sunday) AS sum_7
		FROM rodeo.reports_".$nom_table." r
		WHERE r.week >= '$semaine_min' and r.week <= '$semaine_max'
		GROUP BY r.userid, r.week";
//		print $db_requete; echo "<br />";
		$db_resultat = mysql_query($db_requete, $db_connect);
		while($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
			for($i = 1; $i <= 7; $i++)
				$data_heures_hebdo[$ligne["userid"]."|".$ligne["week"]."|".$an_choisi]["sum_".$i] = $ligne["sum_".$i];
//		print_r($data_heures_hebdo); echo "<br />";
		mysql_free_result($db_resultat);
		$db_requete = " 
			SELECT r.projid, r.actid, LOWER(TRIM(r.theme)) as theme, LOWER(TRIM(r.commentaire)) as libelle, r.userid, r.week, '".$an_choisi."' as year, r.monday, r.tuesday, r.wednesday, r.thursday, r.friday, r.saturday, r.sunday
			FROM rodeo.reports_".$nom_table." r, rodeo.projects p
			WHERE		
		";
		$db_requete .= $db_requete_option;
		$db_requete .= "r.week >= '$semaine_min' and r.week <= '$semaine_max'";	
//		print $db_requete;
		$db_resultat = mysql_query($db_requete, $db_connect);
		while($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC)) {
//			Ne pas bouger la ligne suivante
			if($ligne["projid"] == 400) {
				$ligne["theme"] = "zzz Abs";
				$ligne["libelle"] = "zzz Absence";
				$ligne["actid"] = 400;
			}
			if(!$detail_client_choisi)
				$ligne["projid"] = null;
			if(!$detail_activite_choisi)
				$ligne["actid"] = null;
			if(!$detail_theme_choisi)
				$ligne["theme"] = null;
			if(!$detail_libelle_choisi)
				$ligne["libelle"] = null;
//			if(!$detail_user_choisi)
//				$ligne["userid"] = null;
			
//			/^hrx? ?    <= chaîne débutant par "hrx" ou "hr", éventuellement suivie d'un espace PUIS
//			[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
			if(preg_match("/^hrx? ?[xnz0-9]{3,}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^hrx? ?/i", "", $ligne["theme"]);
//			/^hrexp ?    <= chaîne débutant par "hrexp", éventuellement suivie d'un espace
//			[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
			if(preg_match("/^hrexp ?[xnz0-9]{3,}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^hrexp ?/i", "", $ligne["theme"]);
//			/^fiche ?    <= chaîne débutant par "fiche", éventuellement suivie d'un espace
//			[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
			if(preg_match("/^fiche ?[xnz0-9]{3,}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^fiche ?/i", "", $ligne["theme"]);
//			/^ano ?    <= chaîne débutant par "ano", éventuellement suivie d'un espace
//			[xnz0-9]{3} <= répétition de 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
			if(preg_match("/^ano ?[xnz0-9]{3,}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^ano ?/i", "", $ligne["theme"]);
//			/^evol? ?_?    <= chaîne débutant par "evo", éventuellement suivie d'un "l" et/ou d'un espace
//			[xnz0-9]{3,} <= répétition de 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
			if(preg_match("/^evol? ?_?[xnz0-9]{3,}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^evol? ?_?/i", "", $ligne["theme"]);
//			/^fe_? ?    <= chaîne débutant par "evo", éventuellement suivie d'un "l" et/ou d'un espace
//			[xnz0-9]{5,} <= répétition d'au moins 5 chiffres ou 5 "x" ou 5 "n" ou 5 "z"
			if(preg_match("/^fe_? ?[xnz0-9]{5,}/i", $ligne["theme"]))
				$ligne["theme"] = preg_replace("/^fe_? ?/i", "", $ligne["theme"]);
//			remplacement des caractères accentués
			$ligne["theme"] = preg_replace("/[éèê]/i", "e", $ligne["theme"]);
			array_push($data_heures, $ligne);
		}
		mysql_free_result($db_resultat);

		if ($a_cheval) {
			$an_suivant = $an_choisi + 1;
			$nom_table = $an_suivant;
			$db_requete = "
				SELECT r.userid, r.week, SUM(r.monday) AS sum_1, SUM(r.tuesday) AS sum_2, SUM(r.wednesday) AS sum_3, SUM(r.thursday) AS sum_4, SUM(r.friday) AS sum_5, SUM(r.saturday) AS sum_6, SUM(r.sunday) AS sum_7
				FROM rodeo.reports_".$nom_table." r 
				WHERE r.week >= '01' and r.week <= '01'
				GROUP BY r.userid, r.week";
//			print $db_requete; echo "<br />";
			$db_resultat = mysql_query($db_requete, $db_connect);
			while($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC))
				for($i = 1; $i <= 7; $i++)
					$data_heures_hebdo[$ligne["userid"]."|".$ligne["week"]."|".$an_suivant]["sum_".$i] = $ligne["sum_".$i];
			mysql_free_result($db_resultat);
			$db_requete = " 
				SELECT r.projid, actid, LOWER(TRIM(theme)) as theme, LOWER(TRIM(r.commentaire)) as libelle, userid, week, '".$an_suivant."' as year, monday, tuesday, wednesday, thursday, friday, saturday, sunday
				FROM rodeo.reports_".$nom_table." r, rodeo.projects p 
				WHERE		
			";
			$db_requete .= $db_requete_option;
			$db_requete .= "week = '1';";
//			print $db_requete;
			$db_resultat = mysql_query($db_requete, $db_connect);
//			print $db_resultat;
			while($ligne = mysql_fetch_array($db_resultat, MYSQL_ASSOC)) {
//				Ne pas bouger la ligne suivante
				if($ligne["projid"] == 400) {
					$ligne["theme"] = null;
					$ligne["libelle"] = null;
					$ligne["actid"] = null;
				}
				if(!$detail_client_choisi)
					$ligne["projid"] = null;
				if(!$detail_activite_choisi)
					$ligne["actid"] = null;
				if(!$detail_theme_choisi)
					$ligne["theme"] = null;
				if(!$detail_theme_choisi)
					$ligne["libelle"] = null;
//				if(!$detail_user_choisi)
//					$ligne["userid"] = null;
				array_push($data_heures, $ligne);
//				print_r($ligne);
			}
			mysql_free_result($db_resultat);
		}
//		print_r($data_heures_hebdo);
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
					if($extraction_option_choisi)
						$valeur_du_jour[$i] = (7 * $valeur_du_jour[$i]) / $data_heures_hebdo[$ligne["userid"]."|".$ligne["week"]."|".$ligne["year"]]["sum_".$i];
					$data_heures_cumul[$ligne["projid"]."|".$ligne["actid"]."|".$ligne["theme"]."|".$ligne["libelle"]."|".($detail_user_choisi?$ligne["userid"]:null)]["heures"] += $valeur_du_jour[$i];
					$data_heures_cumul_tfoot += $valeur_du_jour[$i];
					// detail de la semaine pour ce projet
					$jour_encours = ($ligne["week"]-$semaine_min)*7 + $i - $jour_semaine_deb + $jour1_semaine1;
					if($ligne["week"] == 1 and $a_cheval)
						$jour_encours = (53-$semaine_min)*7 + $i - $jour_semaine_deb + $jour1_semaine1;
//					echo $jour_encours."_".$i."_".$semaine_min."_".$semaine_max."_".$jour_semaine_deb."_".$jour1_semaine1."<br />";
					$date_heures_detail[$ligne["projid"]."|".$ligne["actid"]."|".$ligne["theme"]."|".$ligne["libelle"]."|".($detail_user_choisi?$ligne["userid"]:null)][$jour_encours] += $valeur_du_jour[$i];
//					print_r($date_heures_detail);
					$data_heures_detail_tfoot[$jour_encours] += $valeur_du_jour[$i];
				}
			}
		}
	}
//	-- Preparation des couleurs de graphique 1--
	if($chart1_choisi) {
		$i = 0;
		asort($data_heures_cumul);
		foreach($data_heures_cumul as $projet_id=>$projet_data) {
			$tab_entete_ligne = explode('|', $projet_id);
			$nb_lignes++;
			$ratio = 100 * $data_heures_cumul[$projet_id]["heures"] / $data_heures_cumul_tfoot;
			if($i == 0)
				$couleur = $i;
			if($projet_id == 400 || $tab_entete_ligne[1] == 400){
				$couleur = -1;
				$data_heures_cumul[$projet_id]["couleur"] = $couleur;
				$couleur = $i;
			}else
				if($ratio >= $chart1_option_choisi){
					if($nb_lignes > 1)
						$couleur = ++$i;
					$data_heures_cumul[$projet_id]["couleur"] = $couleur;
				}else
					$data_heures_cumul[$projet_id]["couleur"] = $couleur;
		}
		($nb_lignes > 1)?$nb_lignes--:1;
		$nb_couleurs = $i;
//		print_r($data_heures_cumul);
		foreach($data_heures_cumul as $projet_id=>$projet_data) {
			$i = $data_heures_cumul[$projet_id]["couleur"];
			if($i >= 0)
				$data_heures_cumul[$projet_id]["couleur"] = "#".sprintf("%02X%02X%02X", round(255*$i/$nb_couleurs), round(255-255*$i/$nb_couleurs), 0);
			else
				$data_heures_cumul[$projet_id]["couleur"] = "#CCCCCC";
		}
	}

	//	-- Preparation des couleurs de graphique 2--
	if($chart2_choisi) {
		$i = 0;
		$nb_lignes = sizeof($date_heures_detail);
		($nb_lignes > 1)?$nb_lignes--:1;
		foreach($date_heures_detail as $projet_id=>$projet_data) {
			$date_heures_detail[$projet_id]["couleur"] = round(255*$i/$nb_lignes).",".rand(0,255).",".round(255-255*$i/$nb_lignes);
			$i++;
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

<body>
<div>
	<div id="retour"><a href="http://lille/">&lt;&lt; Retour accueil</a></div>
	<hr />
	<nav><a href="./rodeo_stats.php">&lt;Vue sur un mois&gt;</a> | <a href="./rodeo_stats2.php">&lt;Vue sur plusieurs mois&gt;</a></nav>
	<br />
<form id="f_criteres" action="rodeo_stats.php" method="post" class="formulaire">
<table style="width:1400px;">
	<thead>
	<tr>
		<th colspan="8">Filtrer mes donn&eacute;es [ <a href="#" onclick='expandCondenseCriteriaBox(this);return false;'>-</a> ]</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td style="vertical-align:top;">Type d'extraction</td>
		<td colspan="7">
			<input type="radio" name="extraction_option" value="0" <?php echo (($extraction_option_choisi=="0")?' checked="checked"':''); ?> />En heures
			<input type="radio" name="extraction_option" value="1" <?php echo (($extraction_option_choisi=="1")?' checked="checked"':''); ?> />En journ&eacute;e proportionnelle
		</td>
	</tr>
	<tr>
		<td style="vertical-align:top;">Soci&eacute;t&eacute;</td>
		<td>
			<select name="customer_select[]" size="10" multiple="multiple">
				<option value="*" <?php echo (in_array('*', $customer_choisi)?'selected="selected"':''); ?>>Toutes les soci&eacute;t&eacute;s</option>
<?php foreach($liste_customers as $ligne): ?>
				<option value="<?php echo $ligne["custid"]; ?>" <?php echo (in_array($ligne["custid"], $customer_choisi)?' selected="selected"':''); ?> /><?php echo $ligne["name"]."\n"; ?>
<?php endforeach; ?>
			</select>
		</td>
		<td style="vertical-align:top;">Client</td>
		<td>
			<select name="project_select[]" size="10" multiple="multiple">
				<option value="*" <?php echo (in_array('*', $project_choisi)?'selected="selected"':''); ?>>Tous les projets</option>
<?php foreach($liste_project_type as $ligne_project_type): ?>
				<optgroup label="<?php echo $ligne_project_type["libelle"]; ?>">
<?php foreach($liste_projets as $ligne): ?>
<?php	if($ligne["project_type_id"] == $ligne_project_type["id"]): ?>
					<option value="<?php echo $ligne["projid"]; ?>" <?php echo (in_array($ligne["projid"], $project_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["description"]); ?></option>
<?php 	endif; ?>
<?php endforeach; ?>
				</optgroup>
<?php endforeach; ?>
			</select>
		</td>
		<td style="vertical-align:top;">Utilisateur</td>
		<td>
			<select name="user_select[]" size="10" multiple="multiple">
				<option value="*" <?php echo (in_array('*', $user_choisi)?'selected="selected"':''); ?>>Tous les users</option>
<?php foreach($liste_managers as $ligne_manager): ?>
				<optgroup label="<?php echo htmlentities($ligne_manager["managerid"]==null?"Autre manager":$liste_users[$ligne_manager["managerid"]]["nom_complet"]); ?>">
<?php 	foreach($liste_users as $ligne): ?>
<?php		if($ligne["managerid"] == $ligne_manager["managerid"]): ?>
<?php 			if($ligne["surname"][0] != '#'): ?>
					<option value="<?php echo $ligne["userid"]; ?>" <?php echo (in_array($ligne["userid"], $user_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["name"])." ".htmlentities($ligne["surname"]); ?></option>
<?php 			endif; ?>
<?php 		endif; ?>
<?php 	endforeach; ?>
<?php 	foreach($liste_users as $ligne): ?>
<?php		if($ligne["managerid"] == $ligne_manager["managerid"]): ?>
<?php 			if($ligne["surname"][0] == '#'): ?>
<?php				$ligne["surname"][0] = ''; ?>
					<option value="<?php echo $ligne["userid"]; ?>" <?php echo (in_array($ligne["userid"], $user_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["nom_complet"]); ?></option>
<?php 			endif; ?>
<?php 		endif; ?>
<?php 	endforeach; ?>
				</optgroup>
<?php endforeach; ?>
			</select>
		</td>
		<td style="vertical-align:top;">Activit&eacute;</td>
		<td>
			<select name="activity_select[]" size="10" multiple="multiple">
				<option value="*" <?php echo (in_array('*', $activity_choisi)?'selected="selected"':''); ?>>Toutes les activit&eacute;s</option>
<?php foreach($liste_activity_type as $ligne_activity_type): ?>
				<optgroup label="<?php echo $ligne_activity_type["libelle"]; ?>">
<?php 	foreach($liste_activities as $ligne): ?>
<?php 		if($ligne["activity_type_id"] == $ligne_activity_type["id"]): ?>
					<option value="<?php echo $ligne["actid"]; ?>" <?php echo (in_array($ligne["actid"], $activity_choisi)?'selected="selected"':''); ?>><?php echo htmlentities($ligne["description"]); ?></option>
<?php		endif; ?>
<?php 	endforeach; ?>
				</optgroup>
<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<td>P&eacute;riode</td>
		<td colspan="3">
			<select name="select_mois">
<?php for($i=1;$i<=12;$i++): ?>
				<option value="<?php echo $i; ?>" <?php echo (($mois_choisi==$i)?'selected="selected"':''); ?>><?php echo htmlentities(strftime("%B",mktime(0,0,0,$i,'01','2007')));?></option>
<?php endfor; ?>
			</select>
			<select name="select_ans">
<?php for($i=2014;$i>=2007;$i--): ?>
				<option value="<?php echo $i; ?>" <?php echo (($an_choisi==$i)?'selected="selected"':''); ?>><?php echo $i; ?></option>
<?php endfor; ?>
			</select>
		</td>
		<td>Th&egrave;me contient</td>
		<td colspan="3">
			<input type="text" name="theme_content" value="<?php echo $theme_content; ?>" />
		</td>
	</tr>
	<tr>
		<td rowspan="3">&nbsp;</td>
		<td style="vertical-align:top;" rowspan="3">
			<b>Niveau de d&eacute;tail :</b>
			<br /><input type="checkbox" name="detail_client" value="1" <?php echo ($detail_client_choisi?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par client
			<br /><input type="checkbox" name="detail_activite" value="1" <?php echo ($detail_activite_choisi?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par activit&eacute;
			<br /><input type="checkbox" name="detail_theme" value="1" <?php echo ($detail_theme_choisi?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par th&egrave;me
			<br /><input type="checkbox" name="detail_libelle" value="1" <?php echo ($detail_libelle_choisi?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par libell&eacute;
			<br /><input type="checkbox" name="detail_user" value="1" <?php echo ($detail_user_choisi?' checked="checked"':''); ?> />
			D&eacute;taill&eacute; par user
		</td>
		<td colspan="6" style="vertical-align:top;">
			<b>Graphiques :</b>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="checkbox" name="chart1" value="1" <?php echo (!empty($chart1_choisi)?' checked="checked"':''); ?> />Avec graphique 1
		</td>
		<td colspan="4">
			Condenser les valeurs inf&eacute;rieures au pourcentage suivant :
			<input type="radio" name="chart1_option" value="0" <?php echo (($chart1_option_choisi=="0")?' checked="checked"':''); ?> />0%
			<input type="radio" name="chart1_option" value="1" <?php echo (($chart1_option_choisi=="1")?' checked="checked"':''); ?> />1%
			<input type="radio" name="chart1_option" value="2" <?php echo (($chart1_option_choisi=="2")?' checked="checked"':''); ?> />2%
			<input type="radio" name="chart1_option" value="3" <?php echo (($chart1_option_choisi=="3")?' checked="checked"':''); ?> />3%
			<input type="radio" name="chart1_option" value="5" <?php echo (($chart1_option_choisi=="5")?' checked="checked"':''); ?> />5%
			<input type="radio" name="chart1_option" value="10" <?php echo (($chart1_option_choisi=="10")?' checked="checked"':''); ?> />10%			
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="checkbox" name="chart2" value="1" <?php echo (!empty($chart2_choisi)?' checked="checked"':''); ?> />Avec graphique 2
		</td>
		<td colspan="3">
			<input type="radio" name="chart2_option" value="0" <?php echo (($chart2_option_choisi=="0")?' checked="checked"':''); ?> />sans courbe du total<br />
			<input type="radio" name="chart2_option" value="1" <?php echo (($chart2_option_choisi=="1")?' checked="checked"':''); ?> />avec courbe du total
		</td>
		<td>
			<input type="submit" name="go" value="GO" />
		</td>
	</tr>
</table>
</form>
<br />
<table>
<tr>
<td>
<table class="report">
	<colgroup style="background-color:yellow;" >
<?php if($detail_client_choisi): ?>
		<col width="200" />
<?php endif; ?>
<?php if($detail_activite_choisi): ?>
		<col width="200"/>
<?php endif; ?>
<?php if($detail_theme_choisi): ?>
		<col width="90" />
<?php endif; ?>
<?php if($detail_libelle_choisi): ?>
		<col width="200" />
<?php endif; ?>
<?php if($detail_user_choisi): ?>
		<col width="180" />
<?php endif; ?>
	</colgroup>
	<colgroup>
		<col width="80" />
		<col width="80" />
	</colgroup>
	<thead>
		<tr>
<?php $j = 0; ?>
<?php if($detail_client_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Client</a></th>
<?php endif; ?>
<?php if($detail_activite_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Activit&eacute;</a></th>
<?php endif; ?>
<?php if($detail_theme_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Th&egrave;me</a></th>
<?php endif; ?>
<?php if($detail_libelle_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Libell&eacute;</a></th>
<?php endif; ?>
<?php if($detail_user_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">User</a></th>
<?php endif; ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Temps (h)</a></th>
			<th>Temps (j)</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="<?php echo $detail_client_choisi+$detail_activite_choisi+$detail_theme_choisi+$detail_libelle_choisi+$detail_user_choisi; ?>">Total</td>
			<td class="valeur"><?php echo htmlentities(round($data_heures_cumul_tfoot,3)); ?></td>
			<td class="valeur"><?php echo htmlentities(round($data_heures_cumul_tfoot/($extraction_option_choisi?7:8),3)); ?></td>
		</tr>
	</tfoot>
	<tbody style="overflow: auto; overflow-x: hidden;">
<?php //--Affichage du cumul-- ?>
<?php foreach($data_heures_cumul as $projet_id=>$projet_data): ?>
<?php $tab_entete_ligne = explode('|', $projet_id); ?>
		<tr>
<?php if($detail_client_choisi): ?>
			<td><?php echo htmlentities($liste_projets[$tab_entete_ligne[0]]["description"]); ?> </td>
<?php endif; ?>
<?php if($detail_activite_choisi): ?>
			<td><?php echo htmlentities($liste_activities[$tab_entete_ligne[1]]["description"]); ?> </td>
<?php endif; ?>
<?php if($detail_theme_choisi): ?>
			<td><?php echo htmlentities($tab_entete_ligne[2]); ?> </td>
<?php endif; ?>
<?php if($detail_libelle_choisi): ?>
			<td><?php echo htmlentities($tab_entete_ligne[3]); ?> </td>
<?php endif; ?>
<?php if($detail_user_choisi): ?>
			<td><?php echo htmlentities($liste_users[$tab_entete_ligne[4]]["nom_complet"]); ?> </td>
<?php endif; ?>
			<td class="valeur" style="background-color:<?php echo $projet_data["couleur"] ?>"><?php echo htmlentities(round($projet_data["heures"],3)); ?> </td>
			<td class="valeur" style="background-color:<?php echo $projet_data["couleur"] ?>"><?php echo htmlentities(round($projet_data["heures"]/($extraction_option_choisi?7:8),2)); ?> </td>
		</tr>
<?php	$i++; ?>
<?php endforeach; ?>
	</tbody>
</table>
</td>
<td>
<?php if($chart1_choisi): ?>
<canvas id="myChart1" width="400" height="400"></canvas>
<script type="text/javascript">
<!-- // Graphique 1
var chartData1 = [
<?php $i = 0; $couleur = null; $nb_heures = 0; $nb_couleur = 0; ?>
<?php foreach($data_heures_cumul as $projet_id=>$projet_data): ?>
<?php	$imprimer = 0; ?>
<?php 	if($couleur == null): ?>
<?php		$nb_heures = $projet_data["heures"]; ?>
<?php		$couleur = $projet_data["couleur"]; ?>
<?php		$imprimer = (($i == $nb_lignes)||($nb_lignes == 1))?1:0; ?>
<?php	else: ?>
<?php		if($couleur == $projet_data["couleur"]): ?>
<?php			$nb_heures += $projet_data["heures"]; ?>
<?php		else: ?>
<?php			$imprimer = 1; ?>
<?php		endif; ?>
<?php	endif; ?>
<?php	$imprimer = ($i == $nb_lignes)?1:$imprimer; ?>
<?php	if($imprimer): ?>
<?php		$nb_couleur++; ?>
	<?php echo ($nb_couleur == 1)?"":","; ?>{
		value: <?php echo htmlentities($nb_heures); ?>,
		color: "<?php echo $couleur; ?>"
	}
<?php		if($couleur != $projet_data["couleur"]): ?>
	<?php echo ($nb_couleur > 0)?",":""; ?>{
		value: <?php echo htmlentities($projet_data["heures"]); ?>,
		color: "<?php echo $projet_data["couleur"]; ?>"
	}
<?php		endif; ?>
<?php		$couleur = null; $nb_heures = 0; ?>
<?php	endif; ?>
<?php	$i++; ?>
<?php endforeach; ?>
]

var myChart1 = new Chart(document.getElementById("myChart1").getContext("2d")).Doughnut(chartData1);

function onClickChart1 () {
var dataUrl1 = document.getElementById("myChart1").toDataURL();
window.open(dataUrl1, "Chart1", "width=400, height=400");
}
</script>
<span onClick="onClickChart1(); return false;">x</span>
<?php endif; ?>
</td>
</tr>
</table>
<br />
<table class="report">
	<colgroup style="background-color:yellow;" >
<?php if($detail_client_choisi): ?>
		<col width="200" />
<?php endif; ?>
<?php if($detail_activite_choisi): ?>
		<col width="200"/>
<?php endif; ?>
<?php if($detail_theme_choisi): ?>
		<col width="90" />
<?php endif; ?>
<?php if($detail_libelle_choisi): ?>
		<col width="200" />
<?php endif; ?>
<?php if($detail_user_choisi): ?>
		<col width="180" />
<?php endif; ?>
<?php if($chart2_choisi): ?>
		<col width="20" />
<?php endif; ?>
	</colgroup>
	<colgroup>
<?php for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
		<col width="25" />
<?php endfor; ?>
	</colgroup>
	<thead style="overflow: auto; overflow-x: hidden;">
		<tr>
<?php $j = 0; ?>
<?php if($detail_client_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Client</a></th>
<?php endif; ?>
<?php if($detail_activite_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Activit&eacute;</a></th>
<?php endif; ?>
<?php if($detail_theme_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Th&egrave;me</a></th>
<?php endif; ?>
<?php if($detail_libelle_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Libell&eacute;</a></th>
<?php endif; ?>
<?php if($detail_user_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">User</a></th>
<?php endif; ?>
<?php if($chart2_choisi): ?>
			<th>&nbsp;</th>
<?php endif; ?>
<?php for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
			<th><?php echo $i; ?></th>
<?php endfor; ?>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="<?php echo $detail_client_choisi+$detail_activite_choisi+$detail_theme_choisi+$detail_libelle_choisi+$detail_user_choisi; ?>">Total</td>
<?php if($chart2_choisi): ?>
			<td style="background-color:rgba(0,0,0,1)">&nbsp;</td>
<?php endif; ?>

<?php for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
			<td class="valeur"><?php echo htmlentities(round($data_heures_detail_tfoot[$i]/($extraction_option_choisi?7:1), 2)); ?></td>
<?php endfor; ?>
		</tr>
	</tfoot>
	<tbody>
<?php foreach($data_heures_cumul as $projet_id=>$projet_data): ?>
<?php $tab_entete_ligne = explode('|', $projet_id); ?>
		<tr>
<?php if($detail_client_choisi): ?>
			<td><?php echo htmlentities($liste_projets[$tab_entete_ligne[0]]["description"]); ?> </td>
<?php endif; ?>
<?php if($detail_activite_choisi): ?>
			<td><?php echo htmlentities($liste_activities[$tab_entete_ligne[1]]["description"]); ?> </td>
<?php endif; ?>
<?php if($detail_theme_choisi): ?>
			<td><?php echo htmlentities($tab_entete_ligne[2]); ?> </td>
<?php endif; ?>
<?php if($detail_libelle_choisi): ?>
			<td><?php echo htmlentities($tab_entete_ligne[3]); ?> </td>
<?php endif; ?>
<?php if($detail_user_choisi): ?>
			<td><?php echo htmlentities($liste_users[$tab_entete_ligne[4]]["nom_complet"]); ?> </td>
<?php endif; ?>
<?php if($chart2_choisi): ?>
			<td style="background-color:rgba(<?php echo $date_heures_detail[$projet_id]["couleur"] ?>,1)">&nbsp;</td>
<?php endif; ?>
<?php				for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
<?php					$valeur = $date_heures_detail[$projet_id][$i]/($extraction_option_choisi?7:1);
						if(intval($valeur) == $valeur)
							$valeur=intval($valeur);
						else
							$valeur = round($valeur,2);
						if(floatval($valeur) == 0)
							$valeur = '&nbsp;';
?>
			<td class="valeur"><?php echo $valeur; ?></td>
<?php				endfor; ?>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
<?php //--graphique 2 -- ?>
<?php if($chart2_choisi): ?>
<div>
<canvas id="myChart2" width="1000" height="400"></canvas>
<script type="text/javascript">
var chartData2 = {
	labels : [
<?php				for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
				"<?php echo $i; ?>"<?php echo ($i < date('t',$time_mois_debut))?",":""; ?>
<?php				endfor; ?>
	],
	datasets : [
<?php $taille_liste = sizeof($data_heures_cumul); ?>
<?php foreach($data_heures_cumul as $projet_id=>$projet_data): ?>
		{
			fillColor : "rgba(255,255,255,0)",
			strokeColor : "rgba(<?php echo $date_heures_detail[$projet_id]["couleur"]; ?>,1)",
			pointColor : "rgba(<?php echo $date_heures_detail[$projet_id]["couleur"]; ?>,1)",
			pointStrokeColor : "#fff",
			data : [
<?php				for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
<?php					$valeur = $date_heures_detail[$projet_id][$i]/($extraction_option_choisi?7:1);
						if(intval($valeur) == $valeur)
							$valeur=intval($valeur);
						else
							$valeur = round($valeur,2);
?>
			<?php echo $valeur; ?><?php echo ($i < date('t',$time_mois_debut))?",":""; ?>
<?php				endfor; ?>
			]
		}<?php echo --$taille_liste?",":""; ?>
<?php endforeach; ?>
<?php if($chart2_option_choisi > 0): ?>
		,{
			fillColor : "rgba(0,0,0,0.05)",
			strokeColor : "rgba(0,0,0,1)",
			pointColor : "rgba(0,0,0,1)",
			pointStrokeColor : "#fff",
			data : [
<?php	for($i = 1; $i <= date('t',$time_mois_debut); $i++): ?>
			<?php echo round($data_heures_detail_tfoot[$i]/($extraction_option_choisi?7:1), 2); ?><?php echo ($i < date('t',$time_mois_debut))?",":""; ?>
<?php 	endfor; ?>
			]
		}
<?php	endif; ?>
	]}
	var myChart2 = new Chart(document.getElementById("myChart2").getContext("2d")).Line(chartData2);
	
function onClickChart2 () {
var dataUrl2 = document.getElementById("myChart2").toDataURL();
window.open(dataUrl2, "Chart2", "width=1000, height=400");
}
</script>
<span onClick="onClickChart2(); return false;">x</span>
</div>
<?php endif; ?>
<?php
	mysql_close($db_connect);
?>
</body>
</html>