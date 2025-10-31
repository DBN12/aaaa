<html>
<head>
<title>Alimenter Rodeo to Hrpic</title>
<style>
td.valeur {width:1em;text-align:center}
.erreur {color:red; border:2px solid red; padding:1px; font-weight:bold}
.warning {margin:10px}
h2 {border:1px solid #66A; background: #CCF ; margin:20px; padding:10px; font-size:18px;font-weight:bold; color:#66A; text-align : center}
h3 {margin:5px; font-size:18px;font-weight:bold; color:#393; text-align : left; text-decoration:underline}
form.formulaire table {padding:10px; margin-left:30px; border:1px solid #AAA; background:#EEE}
form.formulaire table tr.head td {background:#CEC; padding : 5px}
table.report {border:1px solid #AAA; background:#EEE; color:#339}
table.report tr th{border:0px solid #AAA; background:#CCC}

</style>
</head>
<body>
<h2>Alimenter la table : Rodéo -> HRPIC</h2>

<?php
/* ************************************************** */
/*                 INITIALISATION                     */
/* ************************************************** */
setlocale (LC_TIME, 'fr-fr');
include ('c_bdd_link.mysql.php');
$liaison = new bdd_link();

// Types d'absences (copié de rodeo)
$absent_vals = array(
	501 => 'CP',
	502 => 'Maladie',
	503 => 'RTT',
	504 => 'Vacation',
	505 => 'VAB',
	506 => 'Absence payée',
	507 => 'Flextime',
	508 => 'Komptid' );

// Projets
$liste_projets = $liaison->filtre('','projects');

// Activités
$liste_activites = $liaison->filtre('','activities');


/* ************************************************** */
/*                  TRAITEMENT                        */
/* ************************************************** */

$datas = $liaison->filtre('','rodeo_to_hrpic');

/* ************************************************** */
/*                   AFFICHAGE                        */
/* ************************************************** */
// Formulaire
echo '
<div class="warning">Modifier une configuration</div>
<form method="POST" class="formulaire">
<table class="report">
	<tr><th>Projet</th><th>Activité</th><th>Facturable</th><th>Type</th><th>Nom SAP</th></tr>
	<tr><tr><td><select name="modif_projet">'.$affiche_projets.'</select></td>
	<td><select name="modif_activite">'.$affiche_activites.'</select></td>
	<td><select name="modif_facturable">'.$affiche_facturable.'</select></td>

	<td><select name="modif_type">'.$affiche_types.'</select></td>
	<td><input name="modif_nom"/></td></tr>
	<tr><td colspan=2><input type="submit" name="go" value="GO"></input></td></tr>
</table>

</form>';

	print_t($datas);



/* ************************************************** */
/*                   FONCTIONS                        */
/* ************************************************** */

function print_t($tab){
if($tab!=null and is_array($tab)) echo '<pre>'.print_r($tab,1).'</pre>';
else echo '<i>Table vide</i>';
}
?>



</body>
</html>
