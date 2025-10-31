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
			<th><a href="#" onclick="sortTable(this, <?php echo $j++; ?>); return false;">Temps (h)</a></th>
			<th>Temps (j)</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="<?php echo $detail_client_choisi+$detail_activite_choisi+$detail_otp_choisi+$detail_theme_choisi+$detail_libelle_choisi+$detail_user_choisi; ?>">Total</td>
			<td class="valeur"><?php echo htmlentities($data_heures_cumul_tfoot); ?></td>
			<td class="valeur"><?php echo htmlentities(round($data_heures_cumul_tfoot/($extraction_option_choisi?7:8),2)); ?></td>
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
<?php if($detail_otp_choisi): ?>
			<td><?php echo htmlentities($tab_entete_ligne[5]); ?> </td>
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
			<td class="valeur" style="background-color:<?php echo $projet_data["couleur"] ?>"><?php echo htmlentities($projet_data["heures"]); ?> </td>
			<td class="valeur" style="background-color:<?php echo $projet_data["couleur"] ?>"><?php echo htmlentities(round($projet_data["heures"]/($extraction_option_choisi?7:8),2)); ?> </td>
		</tr>
<?php	$i++; ?>
<?php endforeach; ?>
	</tbody>
</table>