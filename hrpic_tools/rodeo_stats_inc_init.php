<?php
	if( ! ini_get('date.timezone') )
		date_default_timezone_set('Europe/Paris');
		
	require('rodeo_stats_inc_function.php');
	
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
	//$data_heures_cumul = array();
	$data_heures_detail = array();
	//$data_heures_cumul_tfoot = null;
	//$data_heures_detail_tfoot = array();

	$tab_periode = array();

	$chart1_choisi = null;
	$chart2_choisi = null;
	$chart1_option_choisi = null;
	$chart2_option_choisi = null;
	$extraction_option_choisi = null;
	
	$an_periode   = null;
	$mois_periode = null;
	$time_mois_debut = new DateTime('now');
	$time_mois_fin   = new DateTime('now');
	$continue = 0;
	$theme_content = "";
	$libelle_content = "";

	$jour1_semaine1 = 0;

	$precision_chart = 3;

	$db_connect = mysqli_connect("localhost", "rodeo_user", "123RoDeO_UsEr/456");

/* début pavé possiblement à supprimer */
	$mois_choisi = (int)$_POST['select_mois'];
	if(empty($mois_choisi)) 
		$mois_choisi = (int)date("m",mktime(0,0,0,date("m"),1,date("Y")));
	$an_choisi = (int)$_POST['select_ans'];
	if(empty($an_choisi))
		$an_choisi = (int)date('Y');
/* fin pavé possiblement à supprimer */
	
	$semaine_choisi_from = (int)$_POST['select_semaine_from'];
	if(empty($semaine_choisi_from)) 
		$semaine_choisi_from = (int)date("o",mktime(0,0,0,date("m"),1,date("Y")));
	$semaine_choisi_to = (int)$_POST['select_semaine_to'];
	if(empty($semaine_choisi_to)) 
		$semaine_choisi_to = (int)date("o",mktime(0,0,0,date("m"),1,date("Y")));
	$mois_choisi_from = (int)$_POST['select_mois_from'];
	if(empty($mois_choisi_from)) 
		$mois_choisi_from = (int)date("m",mktime(0,0,0,date("m"),1,date("Y")));
	$an_choisi_from = (int)$_POST['select_ans_from'];
	if(empty($an_choisi_from))
		$an_choisi_from = (int)date('Y');
	$mois_choisi_to = (int)$_POST['select_mois_to'];
	if(empty($mois_choisi_to))
 		$mois_choisi_to = (int)date("m",mktime(0,0,0,date("m"),1,date("Y")));
	$an_choisi_to = (int)$_POST['select_ans_to'];
	if(empty($an_choisi_to))
		$an_choisi_to = (int)date('Y');
//	Vérifier la cohérence des dates
	if(mktime(0,0,0,$mois_choisi_from,1,$an_choisi_from) > mktime(0,0,0,$mois_choisi_to,1,$an_choisi_to)){
		$mois_choisi_from = $mois_choisi_to;
		$an_choisi_from = $an_choisi_from;
	}

	$extraction_option_choisi = (int)$_POST['extraction_option'];
	$tableau_cra = (int)$_POST['tableau_cra'];
	$theme_content = $_POST['theme_content'];
	$libelle_content = $_POST['libelle_content'];
	$user_choisi = $_POST['user_select'];

	if((sizeof($user_choisi) >= 1) && in_array('*', $user_choisi)) {
		unset($user_choisi);
		$user_choisi = array();
	}
	if(empty($user_choisi))
		$user_choisi[0] = "*";
	$project_choisi = $_POST['project_select'];
	if((sizeof($project_choisi) >= 1) && in_array('*', $project_choisi)) {
		unset($project_choisi);
		$project_choisi = array();
	}
	if(empty($project_choisi)) {
		$project_choisi[0] = "*";
	}
	$customer_choisi = $_POST['customer_select'];
	if((sizeof($customer_choisi) >= 1) && in_array('*', $customer_choisi)) {
		unset($customer_choisi);
		$customer_choisi = array();
	}
	if(empty($customer_choisi))
		$customer_choisi[0] = "*";
	$activity_choisi = $_POST['activity_select'];
	if((sizeof($activity_choisi) >= 1) && in_array('*', $activity_choisi)) {
		unset($activity_choisi);
		$activity_choisi = array();
	}
	if(empty($activity_choisi))
		$activity_choisi[0] = "*";
	$periode_option_choisi = (int)$_POST['periode_option'];
	if(empty($periode_option_choisi)) {
      		$periode_option_choisi = 0;
	}
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

//	Au cas oÃ¹ rien n'aurait été choisi, on force Ã  afficher au moins le détail par client
	if(!($detail_client_choisi+$detail_activite_chois+$detail_otp_choisi+$detail_theme_choisi+$detail_libelle_choisi+$detail_user_choisi))
		$detail_client_choisi = 1;

	$chart1_choisi = (int)$_POST['chart1'];
	$chart2_choisi = (int)$_POST['chart2'];

//  Liste des customers
	$db_requete = " 
		SELECT custid, name, description 
		FROM rodeo.customers 
		ORDER BY name;
	";
//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_customers[$ligne["custid"]] = $ligne;
	mysqli_free_result($db_resultat);
//	print_r($liste_customers); echo "<br />";
//	print_r($liste_customers_choisis); echo "<br />";

//  Liste des managers
	$db_requete = " 
		SELECT userid
		FROM rodeo.users 
		WHERE authority > 0;
	";
//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_managers[$ligne["userid"]] = $ligne; 
	mysqli_free_result($db_resultat);
	$liste_managers["null"] = 0;
//	print_r($liste_managers); echo "<br />";

//  Liste des utilisateurs
	$db_requete = " 
		SELECT userid, name, surname, nick, CONCAT_WS(' ', name, surname) AS nom_complet, managerid 
		FROM rodeo.users 
		ORDER BY surname, name;
	";
//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_users[$ligne["userid"]] = $ligne; 
	mysqli_free_result($db_resultat);
//	print_r($liste_users); echo "<br />";

//	Liste des types de projet
	$db_requete = "
		SELECT * FROM rodeo.project_type;";
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_project_type[$ligne["id"]] = $ligne;
	mysqli_free_result($db_resultat);
//	print_r($liste_project_type); echo "<br />";

//  Liste des projets
	$db_requete = " 
		SELECT projid, description, project_type_id, custid 
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
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_projets[$ligne["projid"]] = $ligne;
	$ligne["projid"] = 400;
	$ligne["description"] = "zzz Absence";
	$liste_projets[$ligne["projid"]] = $ligne;
	mysqli_free_result($db_resultat);
//	print_r($liste_projets); echo "<br />";

//  Liste des types d'activité
	$db_requete = " 
		SELECT * 
		FROM rodeo.activity_type;
	";
//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_activity_type[$ligne["id"]] = $ligne; 
	mysqli_free_result($db_resultat);
//	print_r($liste_activity_type); echo "<br />";
	
//  Liste des activités
	$db_requete = " 
		SELECT actid, name, description ,activity_type_id 
		FROM rodeo.activities
		ORDER BY description;
	";
//	print $db_requete;
	$db_resultat = mysqli_query($db_connect, $db_requete);
	while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
		$liste_activities[$ligne["actid"]] = $ligne; 
	$ligne["actid"] = 400;
	$ligne["description"] = "zzz Abs";
	$liste_activities[$ligne["actid"]] = $ligne;
	mysqli_free_result($db_resultat);
//	print_r($liste_activities); echo "<br />";
?>
