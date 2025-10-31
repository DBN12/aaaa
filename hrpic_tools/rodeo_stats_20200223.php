<?php
	include('rodeo_stats_inc_init.php');

//	echo "an_choisi_from:".$an_choisi_from." semaine_choisi_from:".$semaine_choisi_from."<br />";
		
//	0 = affichage en jour (si p√©riode <= 1 mois)
//	1 = affichage en mois (si p√©riode > 1 mois)
	$mode_affichage = f_calculate_date_periode($periode_option_choisi,
							$semaine_choisi_from,
							$semaine_choisi_to,
							$mois_choisi_from,
							$an_choisi_from,
							$mois_choisi_to,
							$an_choisi_to,
							$date_from,
							$date_to);
							 
//  Extraction des donn√©es
	if(!empty($user_choisi) && !empty($project_choisi)){
//		Parcourir les ans de la p√©riode
		for($an_periode = $an_choisi_from; $an_periode <= $an_choisi_to; $an_periode++){
//			initialiser le mois de d√©part
			if($mois_periode == null) $mois_periode = $mois_choisi_from;
			$continue = 1;
//			Parcourir les mois de la p√©riode
			while($continue){
				$nom_table = $an_periode;

//				Tester si on est dans la derni√©re boucle
				if($an_periode == $an_choisi_to and $mois_periode == $mois_choisi_to) $continue = 0;
//				echo "anPeriode=".$an_periode."_anChoisiTo=".$an_choisi_to."_moisPeriode=".$mois_periode."_moisChoisiTo=".$mois_choisi_to."<br />";

				f_prepare_date_boucle ($time_mois_debut, $time_mois_fin, $jour1_semaine1, $mois_periode, $an_periode);
//				echo "1. time_mois_debut:".$time_mois_debut->format("d-m-y")." time_mois_fin:".$time_mois_fin->format("d-m-y")."<br />";
//				echo "date_from:".$date_from->format("d-m-y")." date_to:".$date_to->format("d-m-y")."<br />";
				f_borner_date($time_mois_debut, $time_mois_fin, $date_from, $date_to);
//				echo "2. time_mois_debut:".$time_mois_debut->format("d-m-y")." time_mois_fin:".$time_mois_fin->format("d-m-y")."<br />";
//				echo "date_from:".$date_from->format("d-m-y")." date_to:".$date_to->format("d-m-y")."<br />";
				$semaine_min = $time_mois_debut->format('W');
				$semaine_max = $time_mois_fin->format('W');
//				echo "semaine_min:$semaine_min - semaine_max:$semaine_max<br />";
//				Certaines semaines du 1er janvier peuvent se retrouver en 52/53				
//				if($semaine_min > $semaine_max)
//					$semaine_min = 1;

// 				Attention : le mois de d√©cembre peut se terminer en semaine 1!
				$a_cheval = false;
// MODIF 20200114
//				if ($semaine_max == 1) {
//					$semaine_max = 53;
//					$a_cheval = true;
//				}
				if ($semaine_max == 1 && $semaine_max <$semaine_min) {
					$semaine_max = 53;
//					$a_cheval = true;
				}
// FIN MODIF 20200114
// MODIF 20200114
//	 			Ou en semaine 53
//				if ($semaine_min == '53') {
//					$semaine_min = '01';
//				}

//				DÈtecter les semaine ‡ cheval sur 2 mois et les exclure	
//				if($semaine_min == $semaine_max_backup) $semaine_min++;
// FIN MODIF 20200114
				$semaine_max_backup = $semaine_max;
//				echo "time_mois_debut:".$time_mois_debut->format("d-m-y")." time_mois_fin:".$time_mois_fin->format("d-m-y")."<br />";
//				echo "semaine_min=".$semaine_min." semaine_max=".$semaine_max."<br />";

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
					$db_requete_option .= "r.theme LIKE \"%".$theme_content."%\" AND ";
				if(!empty($libelle_content))
					$db_requete_option .= "r.commentaire LIKE \"%".$libelle_content."%\" AND ";

				$db_requete = "
				SELECT '".$nom_table."' AS annee, r.userid, r.week, SUM(r.monday) AS sum_1, SUM(r.tuesday) AS sum_2, SUM(r.wednesday) AS sum_3, SUM(r.thursday) AS sum_4, SUM(r.friday) AS sum_5, SUM(r.saturday) AS sum_6, SUM(r.sunday) AS sum_7
				FROM rodeo.reports_".$nom_table." r
				WHERE r.week >= '$semaine_min' and r.week <= '$semaine_max'
				GROUP BY r.userid, r.week";
//				print "1. $db_requete; <br />";
				$db_resultat = mysqli_query($db_connect, $db_requete);
				while($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC)) {
					f_prepare_date_boucle ($time_mois_debut, $time_mois_fin, $jour1_semaine1, $mois_periode, $an_periode);
					f_borner_date($time_mois_debut, $time_mois_fin, $date_from, $date_to);
					$jour_semaine_deb = ($time_mois_debut->format('w')==0)?7:$time_mois_debut->format('w');
					$jour_semaine_fin = ($time_mois_fin->format('w')==0)?7:$time_mois_fin->format('w');
//	 				Attention : le mois de dÈcembre peut se terminer en semaine 1!
//					Ne pas faire cette op√©ration car d√©j√† faite dans la boucle pr√©c√©dente
//					$a_cheval = false;
// MODIF 20200114
//					if ($semaine_max == 1) {
//						$semaine_max = 53;
//						$a_cheval = true;
//					}
//		 			Ou en semaine 53
//					if ($semaine_min == '53') {
//						$semaine_min = '01';
//					}
// FIN MODIF 20200114

//					echo "debut=".$time_mois_debut->format('D - d/m/Y')." fin=".$time_mois_fin->format('D - d/m/Y')." sem_min=".$semaine_min." ".
//					 "jour_sem_deb=".$jour_semaine_deb." jour_sem_fin=".$jour_semaine_fin." sem_max=".$semaine_max."<br />";
					$i_min = ($ligne["week"] == $semaine_min AND $ligne["annee"] == $an_periode)?$jour_semaine_deb:1;	
					$i_max = ((($ligne["week"] == $semaine_max AND $ligne["annee"] == $an_periode) AND !$a_cheval)
								OR ($a_cheval AND $ligne["week"] == 1))?$jour_semaine_fin:7;
					
					for($i = $i_min; $i <= $i_max; $i++)
//					for($i = 1; $i <= 7; $i++)
						$data_heures_hebdo[$ligne["annee"]."|".$ligne["userid"]."|".$ligne["week"]]["sum_".$i] = $ligne["sum_".$i];
				}
//				print_r($data_heures_hebdo); echo "<br />";
				mysqli_free_result($db_resultat);
				$db_requete = " 
					SELECT DISTINCT r.projid, r.actid, LOWER(TRIM(r.theme)) as theme, LOWER(TRIM(r.commentaire)) as libelle, r.userid, UPPER(rtp.nom) as otp_nom, r.week, r.monday, r.tuesday, r.wednesday, r.thursday, r.friday, r.saturday, r.sunday
					FROM rodeo.projects p, rodeo.reports_".$nom_table." r 
					LEFT JOIN rodeo.rodeo_to_hrpic rtp ON ( r.projid = rtp.projid AND r.actid = rtp.actid )
					WHERE 
				";
				$db_requete .= $db_requete_option;
				$db_requete .= "r.week >= '$semaine_min' and r.week <= '$semaine_max' ";
				$db_requete .= "ORDER BY p.custid, r.week;";
//				print "2. $db_requete <br />";
				$db_resultat = mysqli_query($db_connect, $db_requete);
				while($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC)) {
//					Ne pas bouger la ligne suivante
					if($ligne["projid"] == 400) {
						$ligne["theme"] = "zzz Abs";
						$ligne["libelle"] = "zzz Abs";
						$ligne["otp_nom"] = "zzz Abs";
						$ligne["actid"] = 400;
					}
					if(!$detail_client_choisi)
						$ligne["projid"] = null;
					if(!$detail_activite_choisi)
						$ligne["actid"] = null;
					if(!$detail_otp_choisi)
						$ligne["otp_nom"] = null;
					if(!$detail_theme_choisi)
						$ligne["theme"] = null;
					if(!$detail_libelle_choisi)
						$ligne["libelle"] = null;
//					if(!$detail_user_choisi)
//						$ligne["userid"] = null;
					$ligne["annee"] = $an_periode;

					$ligne["theme"] = f_normaliser_theme($ligne["theme"]);
					array_push($data_heures, $ligne);
//					print_r($ligne); echo "<br />";
				}
				mysqli_free_result($db_resultat);

//				echo "a_cheval:$a_cheval<br />";
				if ($a_cheval) {
					$an_suivant = $an_periode + 1;
					$nom_table = $an_suivant;
					$db_requete = "
						SELECT '".$nom_table."' AS annee, r.userid, r.week, SUM(r.monday) AS sum_1, SUM(r.tuesday) AS sum_2, SUM(r.wednesday) AS sum_3, SUM(r.thursday) AS sum_4, SUM(r.friday) AS sum_5, SUM(r.saturday) AS sum_6, SUM(r.sunday) AS sum_7
						FROM rodeo.reports_".$nom_table." r
						WHERE r.week >= 1 and r.week <= '$semaine_max'
						GROUP BY r.userid, r.week";
//					print "3. $db_requete; <br />";
					$db_resultat = mysqli_query($db_connect, $db_requete);
					while($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
						for($i = 1; $i <= 7; $i++)
							$data_heures_hebdo[$ligne["annee"]."|".$ligne["userid"]."|".$ligne["week"]]["sum_".$i] = $ligne["sum_".$i];
					$db_requete = " 
						SELECT DISTINCT r.projid, r.actid, LOWER(TRIM(r.theme)) as theme, LOWER(TRIM(r.commentaire)) as libelle, r.userid, UPPER(rtp.nom) as otp_nom, r.week, r.monday, r.tuesday, r.wednesday, r.thursday, r.friday, r.saturday, r.sunday
						FROM rodeo.projects p, rodeo.reports_".$nom_table." r 
						LEFT JOIN rodeo.rodeo_to_hrpic rtp ON ( r.projid = rtp.projid AND r.actid = rtp.actid )
						WHERE 
					";		
					$db_requete .= $db_requete_option;
					$db_requete .= "week = '1' ";
					$db_requete .= "ORDER BY p.custid, r.week;";
//					print "4. $db_requete; <br />";
					$db_resultat = mysqli_query($db_connect, $db_requete);
					while($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC)) {
//						Ne pas bouger la ligne suivante
						if($ligne["projid"] == 400) {
							$ligne["theme"] = null;
							$ligne["libelle"] = null;
							$ligne["actid"] = null;
							$ligne["otp_nom"] = null;
						}
						if(!$detail_client_choisi)
							$ligne["projid"] = null;
						if(!$detail_activite_choisi)
							$ligne["actid"] = null;
						if(!$detail_otp_choisi)
							$ligne["otp_nom"] = null;
						if(!$detail_theme_choisi)
							$ligne["theme"] = null;
						if(!$detail_libelle_choisi)
							$ligne["libelle"] = null;
//						if(!$detail_user_choisi)
//							$ligne["userid"] = null;
						$ligne["annee"] = $an_suivant;
//						$ligne["annee"] = $an_periode;

						$ligne["theme"] = f_normaliser_theme($ligne["theme"]);
						array_push($data_heures, $ligne);
//						print_r($ligne); echo "<br />";
					}
					mysqli_free_result($db_resultat);
				}
//				print_r($data_heures_hebdo);
//				print_r($data_heures);

//				Passer au mois suivant
				if($continue)
					if($mois_periode == 12){
						$mois_periode = 1;
						$an_periode++;
					}else
						$mois_periode++;
				else
					$mois_periode = null;
			}	//while
		}	//for
	}	//if

	if($data_heures != null){
		$date_interrogee = new DateTime('now');
		$date_interrogee->setTimestamp(strtotime($date_interrogee->format("m/d/y 00:00:01")));
		for($an_periode = $an_choisi_from; $an_periode <= $an_choisi_to; $an_periode++){
//			initialiser le mois de dÈpart
			if($mois_periode == null) $mois_periode = $mois_choisi_from;
			$continue = 1;
//			Parcourir les mois de la pÈriode
			while($continue){
				$nom_table = $an_periode;

//				Tester si on est dans la derniËre boucle
				if($an_periode == $an_choisi_to and $mois_periode == $mois_choisi_to) $continue = 0;
//				echo "anPeriode=".$an_periode."_anChoisiTo=".$an_choisi_to."_moisPeriode=".$mois_periode."_moisChoisiTo=".$mois_choisi_to."<br />";

				f_prepare_date_boucle ($time_mois_debut, $time_mois_fin, $jour1_semaine1, $mois_periode, $an_periode);
//				echo "--1. time_mois_debut:".$time_mois_debut->format("d-m-y")." time_mois_fin:".$time_mois_fin->format("d-m-y")."<br />";
//				echo "date_from:".$date_from->format("d-m-y")." date_to:".$date_to->format("d-m-y")."<br />";
				f_borner_date($time_mois_debut, $time_mois_fin, $date_from, $date_to);
//				echo "2. time_mois_debut:".$time_mois_debut->format("d-m-y")." time_mois_fin:".$time_mois_fin->format("d-m-y")."<br />";
//				echo "date_from:".$date_from->format("d-m-y")." date_to:".$date_to->format("d-m-y")."<br />";
				$semaine_min = $time_mois_debut->format('W');
				$semaine_max = $time_mois_fin->format('W');
//				echo "semaine_min:$semaine_min - semaine_max:$semaine_max<br />";
				$jour_semaine_deb = ($time_mois_debut->format('w')==0)?7:$time_mois_debut->format('w');
				$jour_semaine_fin = ($time_mois_fin->format('w')==0)?7:$time_mois_fin->format('w');
// 				Attention : le mois de dÈcembre peut se terminer en semaine 1!
				$a_cheval = false;
// MODIF 20200114
//				if ($semaine_max == 1) {
//					$semaine_max = 53;
//					$a_cheval = true;
//				}
				if ($semaine_max == 1 && $semaine_max <$semaine_min) {
					$semaine_max = 53;
//					$a_cheval = true;
				}
// FIN MODIF 20200114
// MODIF 20200114
//	 			Ou en semaine 53
//				if ($semaine_min == '53') {
//					$semaine_min = '01';
//				}
// FIN MODIF 20200114
//				echo "debut=".date("D - d/m/Y",$time_mois_debut)." fin=".date("D - d/m/Y",$time_mois_fin)." sem_min=".$semaine_min." ".
//					 "jour_sem_deb=".$jour_semaine_deb." jour_sem_fin=".$jour_semaine_fin." sem_max=".$semaine_max."<br />";
				$unite = null;
				if($mode_affichage < 1) {
//					echo "date_from=".$date_from->format("d-m-y")." date_to=".$date_to->format("d-m-y")."<br />";
					$i = clone $date_from;
					while($i <= $date_to) {
						$unite = $i->format("d/m/Y");
						$i-> modify('+1 day');
						array_push($tab_periode, $unite);
					}
				} else {
					$unite = (strlen($mois_periode)<2?'0':'').$mois_periode."/".$an_periode;
					array_push($tab_periode, $unite);
				}
				$tab_periode = array_unique($tab_periode);

				foreach($data_heures as $ligne){
//					echo "1. "; print_r($ligne); echo "<br />";
					if( ($ligne["annee"] == $an_periode) ||
						(($ligne["annee"] == ($an_periode + 1)) && $a_cheval) ){
						if(($ligne["annee"] == ($an_periode + 1)) && $a_cheval) {
							$semaine_min = 1;
							$semaine_max = 1;
						}
						if(($ligne["week"] >= $semaine_min AND $ligne["week"] <= $semaine_max)
							OR ($semaine_min == 52 AND ($ligne["week"] == 52 OR ($ligne["week"] >= 1 and $ligne["week"] <= $semaine_max)))){
//							print_r($ligne); echo "<br />";
//							echo "_ an_periode:$an_periode _ a_cheval:$a_cheval<br />";
							$valeur_du_jour=array(	1=>$ligne["monday"],
													2=>$ligne["tuesday"],
													3=>$ligne["wednesday"],
													4=>$ligne["thursday"],
													5=>$ligne["friday"],
													6=>$ligne["saturday"],
													7=>$ligne["sunday"]
												);
//							$i_min = ($ligne["week"] == $semaine_min AND $ligne["annee"] == $an_periode)?$jour_semaine_deb:1;
							$i_min = ((($ligne["week"] == $semaine_min AND $ligne["annee"] == $an_periode) AND !$a_cheval)
										OR ($a_cheval AND $ligne["week"] == 1))?$jour_semaine_deb:1;	
							$i_max = ((($ligne["week"] == $semaine_max AND $ligne["annee"] == $an_periode) AND !$a_cheval)
										OR ($a_cheval AND $ligne["week"] == 1))?$jour_semaine_fin:7;
			//				print_r($valeur_du_jour);echo "<br />";
//							echo "week:".$ligne["week"]." - i_min:$i_min - i_max:$i_max - a_cheval:$a_cheval<br />";
							$date_interrogee->setTimestamp(strtotime($an_periode.'W'.(strlen($ligne["week"])<2?'0':'').$ligne["week"]));
							$date_interrogee->modify("+".($i_min - 1)." days");
//							Ne surtout pas enlever cette putain de ligne ci-dessous
							$date_interrogee->setTimestamp(strtotime($date_interrogee->format("m/d/y 00:00:01")));
//							echo $date_interrogee->format("d/m/Y h:i:s")."-$i_min $i_max-".$time_mois_debut->format("d/m/Y h:i:s")." ".$time_mois_fin->format("d/m/Y h:i:s")."-<br />";
							for($i = $i_min; $i <= $i_max; $i++) {
//								echo "$i-".$date_interrogee->format("d/m/Y h:i")."-".$time_mois_debut->format("d/m/Y h:i")." ".$time_mois_fin->format("d/m/Y h:i")."-".floatval($valeur_du_jour[$i])."<br />";

								if(		(floatval($valeur_du_jour[$i]) > 0)
									&&	($date_interrogee >= $time_mois_debut)
									&&	($date_interrogee <= $time_mois_fin)) {
//									cumul de la semaine pour ce projet
									if($extraction_option_choisi)
										$valeur_du_jour[$i] = (7 * $valeur_du_jour[$i]) / $data_heures_hebdo[$ligne["annee"]."|".$ligne["userid"]."|".$ligne["week"]]["sum_".$i];
									$data_heures_cumul += $valeur_du_jour[$i];
// 									DÈfinition de l'unitÈ temporelle d'affichage
									$unite = null;
									if($mode_affichage < 1)
										$unite = $date_interrogee->format("d/m/Y");
									else
										$unite = $date_interrogee->format("m/Y");
//									echo $unite."_jourSem=".$i."_semaine=".$ligne["week"]."_semaineMin=".$semaine_min."_semaineMax=".$semaine_max."_jourSemaineDeb=".$jour_semaine_deb."_jourSemaine1=".$jour1_semaine1."-valeurJour=".$valeur_du_jour[$i]."<br />";
									$data_heures_detail[$ligne["projid"]."|".$ligne["actid"]."|".$ligne["theme"]."|".$ligne["libelle"]."|".($detail_user_choisi?$ligne["userid"]:null)."|".$ligne["otp_nom"]][$unite] += $valeur_du_jour[$i];
									$data_heures_detail[$ligne["projid"]."|".$ligne["actid"]."|".$ligne["theme"]."|".$ligne["libelle"]."|".($detail_user_choisi?$ligne["userid"]:null)."|".$ligne["otp_nom"]]["zzz total zzz"] += $valeur_du_jour[$i];
									$data_heures_detail["zzz total zzz"][$unite] += $valeur_du_jour[$i];
									$data_heures_detail["zzz total zzz"]["zzz total zzz"] += $valeur_du_jour[$i];
//									print_r($data_heures_detail); echo "<br />";
								}	//if
								$date_interrogee->modify("+1 day");
							}	//for
						}	//if
					}	//if
				}	//foreach
//				CrÈer au moins une case vide si pas de valeur sur ce mois
//				$data_heures_detail_tfoot[$mois_periode."/".$an_periode] += 0;
//				Passer au mois suivant
				if($continue)
					if($mois_periode == 12){
						$mois_periode = 1;
						$an_periode++;
					}else
						$mois_periode++;
				else
					$mois_periode = null;
			}	//while
		}	//for
	}	//if

//	-- Preparation des couleurs de graphique 1--
	if($chart1_choisi) {
		$data_heures_detail = f_prepare_chart1($data_heures_detail,
											  $data_heures_cumul,
											  $chart1_option_choisi,
											  $detail_client_choisi,
											  $detail_activite_choisi,
											  $detail_otp_choisi,
											  $detail_theme_choisi,
											  $detail_libelle_choisi,
											  $detail_user_choisi,
											  $liste_projets, $liste_activities, $liste_users);
	}

//	-- Preparation des couleurs de graphique 2--
	if($chart2_choisi) {
		$data_heures_detail = f_prepare_chart2($data_heures_detail);
	}

//	print_r($data_heures_detail); echo "<br />";
//	print_r($data_heures_cumul); echo "<br />";
//	print_r($tab_periode); echo "<br />";

	mysqli_close($db_connect);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<?php include('rodeo_stats_inc_head.php'); ?>

<body>
<div>
	<div id="retour"><a href="https://rodeo.hr-path.local/">&lt;&lt; Retour accueil</a></div>
	<hr />
</div>
<?php include('rodeo_stats_inc_selection.php'); ?>
<br />
<?php if($tableau_cra): ?>
<?php include('rodeo_stats_inc_tab3.php'); ?>
<?php else: ?>
<?php include('rodeo_stats_inc_tab2.php'); ?>
<?php endif; ?>
<?php include('rodeo_stats_inc_chart1.php'); ?>
<?php include('rodeo_stats_inc_chart2.php'); ?>
</body>
</html>
