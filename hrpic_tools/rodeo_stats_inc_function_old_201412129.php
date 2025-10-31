<?php
	function f_calculate_date_periode($im_option, &$ch_mois_choisi_from, &$ch_an_choisi_from, &$ch_mois_choisi_to, &$ch_an_choisi_to, &$ch_date_from, &$ch_date_to) {
		if ($im_option == 9) {
			$ch_date_from = new DateTime('now');
			$ch_date_to = new DateTime('now');
			$ch_date_from->setTimestamp(strtotime($ch_mois_choisi_from.'/01/'.$ch_an_choisi_from));
			$ch_date_from->modify('First day of month');
			$ch_date_to->setTimestamp(strtotime($ch_mois_choisi_to.'/01/'.$ch_an_choisi_to));
			$ch_date_to->modify('First day of next month');
			$ch_date_to->modify('-1 day');
		} else {
			$ch_date_from = new DateTime('now');
			switch ($im_option) {
				case 0:		//semaine précédente
					$ch_date_from->setTimestamp(strtotime($ch_date_from->format('Y').'W'.$ch_date_from->format('W')));
					$ch_date_from->modify('-7 days');
					$ch_date_to = clone $ch_date_from;
					$ch_date_to->modify('+6 days');
					break;
				case 1:		//mois précédent
					$ch_date_from->modify('First day of previous month');
					$ch_date_to = new DateTime('now');
					$ch_date_to->modify('First day of month');
					$ch_date_to->modify('-1 days');
					break;
				case 2:		//trimestre précédent
					$ch_date_to = new DateTime('now');
					switch ($ch_date_from->format('m')) {
						case '01':
						case '02':
						case '03':
							$ch_date_from->setTimestamp(strtotime('09/01/'.($ch_date_from->format('Y') - 1)));
							$ch_date_to->setTimestamp(strtotime('12/31/'.($ch_date_from->format('Y') - 1)));
							break;
						case '04':
						case '05':
						case '06':
							$ch_date_from->setTimestamp(strtotime('01/01/'.$ch_date_from->format('Y')));
							$ch_date_to->setTimestamp(strtotime('03/31/'.$ch_date_from->format('Y')));
							break;
						case '07':
						case '08':
						case '09':
							$ch_date_from->setTimestamp(strtotime('04/01/'.$ch_date_from->format('Y')));
							$ch_date_to->setTimestamp(strtotime('06/30/'.$ch_date_from->format('Y')));
							break;
						case '10':
						case '11':
						case '12':
							$ch_date_from->setTimestamp(strtotime('07/01/'.$ch_date_from->format('Y')));
							$ch_date_to->setTimestamp(strtotime('09/30/'.$ch_date_from->format('Y')));
							break;
					}
					break;
				case 3:		//année fiscale
					$ch_date_to = new DateTime('now');
					if($ch_date_from->format('m') < 4) {
						$ch_date_from->setTimestamp(strtotime('04/01/'.($ch_date_from->format('Y') - 1)));
						$ch_date_to->setTimestamp(strtotime('03/31/'.$ch_date_to->format('Y')));
					} else {
						$ch_date_from->setTimestamp(strtotime('04/01/'.$ch_date_from->format('Y')));
						$ch_date_to->setTimestamp(strtotime('03/31/'.($ch_date_to->format('Y') + 1)));
					}
					break;
			}
			$ch_mois_choisi_from = $ch_date_from->format('m');
			$ch_an_choisi_from   = $ch_date_from->format('Y');
			$ch_mois_choisi_to   = $ch_date_to->format('m');
			$ch_an_choisi_to     = $ch_date_to->format('Y');
		}
		echo "date_from:".$ch_date_from->format('d-m-y')." date_to:".$ch_date_to->format('d-m-y')."<br />";
		$i = $ch_date_from->diff($ch_date_to);
		echo $i->format("%y")."_".$i->format("%m")."_".$i->format("%d")."_".$i->format("%a")."<br />";
		
		if($i->format("%y") > 0)
			return 1;
		else
			if($i->format("%m") > 0)
				if($i->format("%d") <= 2)
					return 0;
				else
					return 1;
			else
				return 0;
	}
	
	function f_prepare_date_boucle (&$ch_time_mois_debut, &$ch_time_mois_fin, &$im_jour1_semaine1, $im_mois_periode, $im_an_periode) {
//  	La première semaine ne contient pas forcément le premier janvier
		$im_jour1_semaine1 = 1;
		if ($im_mois_periode == 1)
		switch ($im_an_periode) {
			case 2010:
				$im_jour1_semaine1 = 4;
			case 2011:
				$im_jour1_semaine1 = 3;
			case 2012:
				$im_jour1_semaine1 = 2;
			case 2013:
				$im_jour1_semaine1 = 1;
			case 2014:
				$im_jour1_semaine1 = 1;
		}
		$ch_time_mois_debut->setTimestamp(strtotime($im_mois_periode.'/'.$im_jour1_semaine1.'/'.$im_an_periode));
		$ch_time_mois_fin->setTimestamp(strtotime($im_mois_periode.'/01/'.$im_an_periode));
		$ch_time_mois_fin->modify('First day of next month');
		$ch_time_mois_fin->modify('-1 day');
	}
	
	function f_borner_date(&$ch_time_mois_debut, &$ch_time_mois_fin, $im_date_from, $im_date_to) {
		if($ch_time_mois_debut < $im_date_from)
			$ch_time_mois_debut = clone $im_date_from;
		if($ch_time_mois_fin > $im_date_to)
			$ch_time_mois_fin = clone $im_date_to;
	}
	
	function f_normaliser_theme($im_valeur) {
		
//		/^ticket ?    <= chaîne débutant par "ticket", éventuellement suivie d'un espace PUIS
//		[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
		if(preg_match("/^ticket ? ?[xnz0-9]{3,}/i", $im_valeur))
			$im_valeur = preg_replace("/^ticket ? ?/i", "", $im_valeur);
//		/^mantis ?    <= chaîne débutant par "ticket", éventuellement suivie d'un espace PUIS
//		[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
		if(preg_match("/^mantis ? ?[xnz0-9]{3,}/i", $im_valeur))
			$im_valeur = preg_replace("/^mantis ? ?/i", "", $im_valeur);
//		/^fiche ?    <= chaîne débutant par "ticket", éventuellement suivie d'un espace PUIS
//		[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
		if(preg_match("/^fiche ? ?[xnz0-9]{3,}/i", $im_valeur))
			$im_valeur = preg_replace("/^fiche ? ?/i", "", $im_valeur);
//		/^hrx? ?    <= chaîne débutant par "hrx" ou "hr", éventuellement suivie d'un espace PUIS
//		[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
		if(preg_match("/^hrx? ?[xnz0-9]{3,}/i", $im_valeur))
			$im_valeur = preg_replace("/^hrx? ?/i", "", $im_valeur);
//		/^hrexp ?    <= chaîne débutant par "hrexp", éventuellement suivie d'un espace
//		[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
		if(preg_match("/^hrexp ?[xnz0-9]{3,}/i", $im_valeur))
			$im_valeur = preg_replace("/^hrexp ?/i", "", $im_valeur);
//		/^fiche ?    <= chaîne débutant par "fiche", éventuellement suivie d'un espace
//		[xnz0-9]{3,} <= répétition d'au moins 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
		if(preg_match("/^fiche ?[xnz0-9]{3,}/i", $im_valeur))
			$im_valeur = preg_replace("/^fiche ?/i", "", $im_valeur);
//		/^ano_? ?    <= chaîne débutant par "ano", éventuellement suivie d'un espace et/ou d'un "_"
//		[xnz0-9]{3} <= répétition de 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
		if(preg_match("/^ano_? ?[xnz0-9]{3,}/i", $im_valeur))
			$im_valeur = preg_replace("/^ano ?/i", "", $im_valeur);
//		/^evol?.? ?_?    <= chaîne débutant par "evo", éventuellement suivie d'un "l" et/ou d'un espace et/ou d'un point
//		[xnz0-9]{3,} <= répétition de 3 chiffres ou 3 "x" ou 3 "n" ou 3 "z"
		if(preg_match("/^evol?.? ?_?[xnz0-9]{3,}/i", $im_valeur))
			$im_valeur = preg_replace("/^evol?.? ?_?/i", "", $im_valeur);
//		/^fe_? ?    <= chaîne débutant par "evo", éventuellement suivie d'un "l" et/ou d'un espace
//		[xnz0-9]{5,} <= répétition d'au moins 5 chiffres ou 5 "x" ou 5 "n" ou 5 "z"
		if(preg_match("/^fe_? ?[xnz0-9]{5,}/i", $im_valeur))
			$im_valeur = preg_replace("/^fe_? ?/i", "", $im_valeur);
//		remplacement des caractères accentués
		$im_valeur = preg_replace("/[éèê]/i", "e", $im_valeur);
		
		return $im_valeur;
	}
	
	function f_prepare_chart1($im_tableau, $im_cumul_heures, $im_option, $im_detail1, $im_detail2, $im_detail3, $im_detail4, $im_detail5, $im_detail6, $im_liste_projets, $im_liste_activities, $im_liste_users) {
		$i = 0;
		$nb_lignes = 0;
		//usort($im_tableau, "f_cmp");
		foreach($im_tableau as $projet_id=>$projet_data) {
			if($projet_id != "zzz total zzz") {
				$tab_entete_ligne = explode('|', $projet_id);
				$nb_lignes++;
				$ratio = 100 * $im_tableau[$projet_id]["zzz total zzz"] / $im_cumul_heures;
				if($i == 0)
					$couleur = $i;
				if($projet_id == 400 || $tab_entete_ligne[1] == 400){
					$couleur = -1;
					$im_tableau[$projet_id]["couleur"] = $couleur;
					$couleur = $i;
				}else
					if($ratio >= $im_option){
						if($nb_lignes > 1)
							$couleur = ++$i;
						$im_tableau[$projet_id]["couleur"] = $couleur;
					}else
						$im_tableau[$projet_id]["couleur"] = $couleur;
			}
		}
		($nb_lignes > 1)?$nb_lignes--:1;
		$nb_couleurs = $i;

		foreach($im_tableau as $projet_id=>$projet_data) {
			if($projet_id != "zzz total zzz"){
				$i = $im_tableau[$projet_id]["couleur"];
				if($i >= 0) {
					$im_tableau[$projet_id]["couleur"] = "#".sprintf("%02X%02X%02X", round(255*$i/$nb_couleurs), round(255-255*$i/$nb_couleurs), 0);
					$tab_entete_ligne = explode('|', $projet_id);
					$im_tableau[$projet_id]["couleur_label"] = null;
					if($im_detail1)
						$im_tableau[$projet_id]["couleur_label"] .= htmlentities($im_liste_projets[$tab_entete_ligne[0]]["description"])." ";
					if($im_detail2)
						$im_tableau[$projet_id]["couleur_label"] .= htmlentities($im_liste_activities[$tab_entete_ligne[1]]["description"])." ";
					if($im_detail3)
						$im_tableau[$projet_id]["couleur_label"] .= htmlentities($tab_entete_ligne[5])." ";
					if($im_detail4)
						$im_tableau[$projet_id]["couleur_label"] .= htmlentities($tab_entete_ligne[2])." ";
					if($im_detail5)
						$im_tableau[$projet_id]["couleur_label"] .= htmlentities($tab_entete_ligne[3])." ";
					if($im_detail6)
						$im_tableau[$projet_id]["couleur_label"] .= htmlentities($im_liste_users[$tab_entete_ligne[4]]["nom_complet"])." ";
				} else {
					$im_tableau[$projet_id]["couleur"] = "#CCCCCC";
					$im_tableau[$projet_id]["couleur_label"] = "Absence";
				}
			}
		}
		return $im_tableau;
	}
	
	function f_prepare_chart2($im_tableau) {
		$i = 0;
		$nb_lignes = sizeof($im_tableau);
		($nb_lignes > 1)?$nb_lignes--:1;
		foreach($im_tableau as $projet_id=>$projet_data) {
			$im_tableau[$projet_id]["couleur"] = round(255*$i/$nb_lignes).",".rand(0,255).",".round(255-255*$i/$nb_lignes);
			$i++;
		}
		return $im_tableau;
	}
?>