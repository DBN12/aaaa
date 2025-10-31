<?php
        $page = "rodeo_stats.php";
		$annee_max = 2026;
?>
<form id="f_criteres" action="<?php echo $page; ?>" method="post" class="formulaire">
<table style="width:1400px;">
	<thead>
	<tr>
		<th colspan="8">Filtrer mes donn&eacute;es [ <a href="#" onclick='expandCondenseCriteriaBox(this);return false;'>-</a> ]</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td style="vertical-align:top;">Type d'extraction</td>
		<td colspan="6">
			<input type="radio" name="extraction_option" value="0" <?php echo (($extraction_option_choisi=="0")?' checked="checked"':''); ?> />En heures
			<input type="radio" name="extraction_option" value="1" <?php echo (($extraction_option_choisi=="1")?' checked="checked"':''); ?> />En journ&eacute;e proportionnelle
		</td>
		<td>
			<input type="checkbox" name="tableau_cra" value="1" <?php echo ($tableau_cra?' checked="checked"':''); ?> />Tableau CRA
		</td>
	</tr>
	<tr>
		<td style="vertical-align:top;">Soci&eacute;t&eacute;</td>
		<td>
			<select name="customer_select[]" size="10" multiple="multiple">
				<option value="*" <?php echo (in_array('*', $customer_choisi)?'selected="selected"':''); ?>>Toutes les soci&eacute;t&eacute;s</option>
<?php foreach($liste_customers as $ligne): ?>
				<option value="<?php echo $ligne["custid"]; ?>" <?php echo (in_array($ligne["custid"], $customer_choisi)?' selected="selected"':''); ?>><?php echo $ligne["name"]."\n"; ?></option>
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
			<input type="radio" name="periode_option" value="0" <?php echo (($periode_option_choisi=="0")?' checked="checked"':''); ?> />Semaine pr&eacute;c&eacute;dente
			<br /><input type="radio" name="periode_option" id="periode_option_1" value="1" <?php echo (($periode_option_choisi=="1")?' checked="checked"':''); ?> />Mois pr&eacute;c&eacute;dent
			<br /><input type="radio" name="periode_option" id="periode_option_4" value="4" <?php echo (($periode_option_choisi=="4")?' checked="checked"':''); ?> />Mois actuel
			<br /><input type="radio" name="periode_option" id="periode_option_2" value="2" <?php echo (($periode_option_choisi=="2")?' checked="checked"':''); ?> />Trimestre pr&eacute;c&eacute;dent
			<br /><input type="radio" name="periode_option" id="periode_option_3" value="3" <?php echo (($periode_option_choisi=="3")?' checked="checked"':''); ?> />Ann&eacute;e fiscale actuelle
			<br /><input type="radio" name="periode_option" id="periode_option_5" value="5" <?php echo (($periode_option_choisi=="5")?' checked="checked"':''); ?> />P&eacute;riode libre (semaine)
			<select name="select_semaine_from" onClick="Javascript:document.getElementById('periode_option_5').checked = true;">
<?php for($i=1;$i<=53;$i++): ?>
				<option value="<?php echo $i; ?>" <?php echo (($semaine_choisi_from==$i)?'selected="selected"':''); ?>><?php echo htmlentities($i);?></option>
<?php endfor; ?>
			</select>
			&agrave;
			<select name="select_semaine_to" onClick="Javascript:document.getElementById('periode_option_5').checked = true;">
<?php for($i=1;$i<=53;$i++): ?>
				<option value="<?php echo $i; ?>" <?php echo (($semaine_choisi_to==$i)?'selected="selected"':''); ?>><?php echo htmlentities($i);?></option>
<?php endfor; ?>
			</select>
			<br /><input type="radio" name="periode_option" id="periode_option_9" value="9" <?php echo (($periode_option_choisi=="9")?' checked="checked"':''); ?> />P&eacute;riode libre (mois)
			<select name="select_mois_from" onClick="Javascript:document.getElementById('periode_option_9').checked = true;">
<?php for($i=1;$i<=12;$i++): ?>
				<option value="<?php echo $i; ?>" <?php echo (($mois_choisi_from==$i)?'selected="selected"':''); ?>><?php echo htmlentities(strftime("%B",mktime(0,0,0,$i,'01','2007')));?></option>
<?php endfor; ?>
			</select>
			<select name="select_ans_from" onClick="Javascript:document.getElementById('periode_option_9').checked = true;">
<?php for($i=$annee_max;$i>=2007;$i--): ?>
				<option value="<?php echo $i; ?>" <?php echo (($an_choisi_from==$i)?'selected="selected"':''); ?>><?php echo $i; ?></option>
<?php endfor; ?>
			</select>
			&agrave;
			<select name="select_mois_to" onClick="Javascript:document.getElementById('periode_option_9').checked = true;">
<?php for($i=1;$i<=12;$i++): ?>
				<option value="<?php echo $i; ?>" <?php echo (($mois_choisi_to==$i)?'selected="selected"':''); ?>><?php echo htmlentities(strftime("%B",mktime(0,0,0,$i,'01','2007')));?></option>
<?php endfor; ?>
			</select>
			<select name="select_ans_to" onClick="Javascript:document.getElementById('periode_option_9').checked = true;">
<?php for($i=$annee_max;$i>=2007;$i--): ?>
				<option value="<?php echo $i; ?>" <?php echo (($an_choisi_to==$i)?'selected="selected"':''); ?>><?php echo $i; ?></option>
<?php endfor; ?>
			</select>
		</td>
		<td>Filtre de texte</td>
		<td colspan="3">
			Th&egrave;me <input type="text" name="theme_content" value="<?php echo $theme_content; ?>" />
			<br />Libell&eacute; <input type="text" name="libelle_content" value="<?php echo $libelle_content; ?>" />
		</td>
	</tr>
	<tr>
		<td rowspan="3">&nbsp;</td>
		<td style="vertical-align:top;" rowspan="3">
			<b>Niveau de d&eacute;tail :</b>
			<br /><input type="checkbox" name="detail_client" value="1" <?php echo ($detail_client_choisi?' checked="checked"':''); ?> />
			Client
			<br /><input type="checkbox" name="detail_activite" value="1" <?php echo ($detail_activite_choisi?' checked="checked"':''); ?> />
			Activit&eacute;
			<br /><input type="checkbox" name="detail_otp" value="1" <?php echo ($detail_otp_choisi?' checked="checked"':''); ?> />
			OTP
			<br /><input type="checkbox" name="detail_theme" value="1" <?php echo ($detail_theme_choisi?' checked="checked"':''); ?> />
			Th&egrave;me
			<br /><input type="checkbox" name="detail_libelle" value="1" <?php echo ($detail_libelle_choisi?' checked="checked"':''); ?> />
			Libell&eacute;
			<br /><input type="checkbox" name="detail_user" value="1" <?php echo ($detail_user_choisi?' checked="checked"':''); ?> />
			User
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
	</tbody>
</table>
</form>
