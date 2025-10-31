<table class="report">
	<colgroup style="background-color:yellow;" >
<?php if($detail_client_choisi): ?>
		<col width="200" />
<?php endif; ?>
<?php if($detail_activite_choisi): ?>
		<col width="200"/>
<?php endif; ?>
<?php if($detail_otp_choisi): ?>
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
		<col width="80" />
		<col width="80" />
	</colgroup>
	<colgroup>
<?php foreach($tab_periode as $unite): ?>
		<col width="25" />
<?php endforeach; ?>
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
<?php if($detail_otp_choisi): ?>
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">OTP</a></th>
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
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Temps (h)</a></th>
			<th>Temps (j)</th>
<?php foreach($tab_periode as $unite): ?>
			<th><small><?php echo strlen($unite)>7?substr($unite, 0, 5).'<br />'.substr($unite, strlen($unite)-4, 4):$unite; ?></small></th>
<?php endforeach; ?>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="<?php echo $detail_client_choisi+$detail_activite_choisi+$detail_otp_choisi+$detail_theme_choisi+$detail_libelle_choisi+$detail_user_choisi; ?>">Total</td>
<?php if($chart2_choisi): ?>
			<td style="background-color:rgba(0,0,0,1)">&nbsp;</td>
<?php endif; ?>
			<td class="valeur"><?php echo htmlentities(round($data_heures_detail["zzz total zzz"]["zzz total zzz"],3)); ?></td>
			<td class="valeur"><?php echo htmlentities(round($data_heures_detail["zzz total zzz"]["zzz total zzz"]/($extraction_option_choisi?7:8), 3)); ?></td>
<?php foreach($tab_periode as $unite): ?>
			<td class="valeur"><?php echo htmlentities(round($data_heures_detail["zzz total zzz"][$unite]/($extraction_option_choisi?7:8), 3)); ?></td>
<?php endforeach; ?>
		</tr>
	</tfoot>
	<tbody>
<?php //print_r($data_heures_detail); ?>
<?php foreach($data_heures_detail as $projet_id=>$projet_data): ?>
<?php	if($projet_id != "zzz total zzz"): ?>
<?php 		$tab_entete_ligne = explode('|', $projet_id); ?>
		<tr>
<?php 		if($detail_client_choisi): ?>
			<td><?php echo htmlentities($liste_projets[$tab_entete_ligne[0]]["description"]); ?> </td>
<?php 		endif; ?>
<?php 		if($detail_activite_choisi): ?>
			<td><?php echo htmlentities($liste_activities[$tab_entete_ligne[1]]["description"]); ?> </td>
<?php 		endif; ?>
<?php 		if($detail_otp_choisi): ?>
			<td><?php echo htmlentities($tab_entete_ligne[5]); ?> </td>
<?php 		endif; ?>
<?php 		if($detail_theme_choisi): ?>
			<td><?php echo htmlentities($tab_entete_ligne[2]); ?> </td>
<?php 		endif; ?>
<?php 		if($detail_libelle_choisi): ?>
			<td><?php echo htmlentities($tab_entete_ligne[3]); ?> </td>
<?php 		endif; ?>
<?php 		if($detail_user_choisi): ?>
			<td><?php echo htmlentities($liste_users[$tab_entete_ligne[4]]["nom_complet"]); ?> </td>
<?php 		endif; ?>
<?php 		if($chart2_choisi): ?>
			<td style="background-color:rgba(<?php echo $data_heures_detail[$projet_id]["couleur"] ?>,1)">&nbsp;</td>
<?php 		endif; ?>
			<td class="valeur" style="background-color:<?php echo $projet_data["couleur"] ?>"><?php echo htmlentities(round($data_heures_detail[$projet_id]["zzz total zzz"],3)); ?> </td>
			<td class="valeur" style="background-color:<?php echo $projet_data["couleur"] ?>"><?php echo htmlentities(round($data_heures_detail[$projet_id]["zzz total zzz"]/($extraction_option_choisi?7:8),3)); ?> </td>
<?php 		foreach($tab_periode as $unite): ?>
<?php 			$valeur = $data_heures_detail[$projet_id][$unite]/($extraction_option_choisi?7:($mode_affichage<1?1:8));
				if(intval($valeur) == $valeur)
					$valeur=intval($valeur);
				else
					$valeur = round($valeur,2);
				if(floatval($valeur) == 0)
					$valeur = '&nbsp;';
?>
			<td class="valeur"><?php echo $valeur; ?></td>
<?php 		endforeach; ?>
		</tr>
<?php	endif; ?>
<?php endforeach; ?>
	</tbody>
</table>