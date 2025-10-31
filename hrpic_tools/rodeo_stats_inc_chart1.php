<?php if($chart1_choisi): ?>
<canvas id="myChart1" width="400" height="400"></canvas>
<script type="text/javascript">
<!-- // Graphique 1
var chartData1 = [
<?php $i = 1; $couleur = null; $couleur_label = null; $nb_heures = 0; $nb_couleur = 0; $nb_lignes = sizeof($data_heures_detail) - 1;?>
<?php foreach($data_heures_detail as $projet_id=>$projet_data): ?>
<?php   if($projet_id != "zzz total zzz"): ?>
<?php	$imprimer = 0; ?>
<?php 	if($couleur == null): ?>
<?php		$nb_heures = $projet_data["zzz total zzz"]; ?>
<?php		$couleur = $projet_data["couleur"]; ?>
<?php		$couleur_label = $projet_data["couleur_label"]; ?>
<?php		$imprimer = (($i == $nb_lignes)||($nb_lignes == 1))?1:0; ?>
<?php	else: ?>
<?php		if($couleur == $projet_data["couleur"]): ?>
<?php			$nb_heures += $projet_data["heures"]; ?>
<?php           $couleur_label = "Condensé"; ?>
<?php		else: ?>
<?php			$imprimer = 1; ?>
<?php		endif; ?>
<?php	endif; ?>
<?php	$imprimer = ($i == $nb_lignes)?1:$imprimer; ?>
<?php	if($imprimer): ?>
<?php		$nb_couleur++; ?>
	<?php echo ($nb_couleur == 1)?"":","; ?>{
		value: <?php echo htmlentities($nb_heures); ?>,
		color: "<?php echo $couleur; ?>",
		label: "<?php echo $couleur_label; ?>"
	}
<?php		if($couleur != $projet_data["couleur"]): ?>
	<?php echo ($nb_couleur > 0)?",":""; ?>{
		value: <?php echo htmlentities($projet_data["zzz total zzz"]); ?>,
		color: "<?php echo $projet_data["couleur"]; ?>",
		label: "<?php echo $projet_data["couleur_label"]; ?>"
	}
<?php		endif; ?>
<?php		$couleur = null; $nb_heures = 0; ?>
<?php	endif; ?>
<?php	$i++; ?>
<?php	endif; ?>
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