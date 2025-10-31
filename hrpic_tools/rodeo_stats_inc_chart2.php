<?php if($chart2_choisi): ?>
<div>
<canvas id="myChart2" width="1000" height="400"></canvas>
<script type="text/javascript">
var chartData2 = {
	labels : [
<?php 	$taille_liste = sizeof($tab_periode); ?>
<?php 	foreach($tab_periode as $unite): ?>
				"<?php echo $unite; ?>"<?php echo --$taille_liste?",":""; ?>
<?php	endforeach; ?>
	],
	datasets : [
<?php 	$taille_liste  = sizeof($tab_periode); ?>
<?php 	$taille_liste2 = sizeof($data_heures_detail) - 1; ?>
<?php 	foreach($data_heures_detail as $projet_id=>$projet_data): ?>
<?php		if($projet_id != "zzz total zzz"): ?>
		{
			fillColor : "rgba(255,255,255,0)",
			strokeColor : "rgba(<?php echo $data_heures_detail[$projet_id]["couleur"]; ?>,1)",
			pointColor : "rgba(<?php echo $data_heures_detail[$projet_id]["couleur"]; ?>,1)",
			pointStrokeColor : "#fff",
			data : [
<?php 		foreach($tab_periode as $unite): ?>
<?php			$valeur = $data_heures_detail[$projet_id][$unite]/($extraction_option_choisi?7:1);
				if(intval($valeur) == $valeur)
					$valeur=intval($valeur);
				else
					$valeur = round($valeur,2);
?>
			<?php echo $valeur; ?><?php echo --$taille_liste?",":""; ?>
<?php		endforeach; ?>
			]
		}<?php echo --$taille_liste2?",":""; ?>
<?php		endif; ?>
<?php 	endforeach; ?>
<?php 	if($chart2_option_choisi > 0): ?>
		,{
			fillColor : "rgba(0,0,0,0.05)",
			strokeColor : "rgba(0,0,0,1)",
			pointColor : "rgba(0,0,0,1)",
			pointStrokeColor : "#fff",
			data : [
<?php 		$taille_liste  = sizeof($tab_periode); ?>
<?php 		foreach($tab_periode as $unite): ?>
			<?php echo round($data_heures_detail["zzz total zzz"][$unite]/($extraction_option_choisi?7:1), 2); ?><?php echo --$taille_liste?",":""; ?>
<?php 		endforeach; ?>
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