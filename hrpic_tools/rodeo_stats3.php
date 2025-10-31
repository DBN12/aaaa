<?php
	setlocale (LC_TIME, 'fr-fr');
	
	$db_connect = null;
	$ligne = null;
	$db_requete = null;
	$db_requete_option = null;
	$db_resultat = null;
	
	$liste_users = array();
	$liste_projets = array();
	$liste_activite = array();
	$liste_customers = array();
	$liste_customers_choisis = array();
	
	$data_heures = array();
	$data_heures_cumul = array();
	$date_heures_detail = array();
	$data_heures_cumul_tfoot = null;
	$date_heures_detail_tfoot = array();
	
	$an_choisi = null;
	$semaine_periode = null;
	$continue = 0;
	
	$customer_choisi = null;
	$theme_content = "";
	
	$jour1_semaine1 = 0;

	$db_connect = mysql_connect("127.0.0.1", "root", "d10lillE2005");
	mysql_select_db('rodeo');

	$semaine_choisi_from = (empty($_POST['select_semaine_from'])?$_GET['select_semaine_from']:$_POST['select_semaine_from']);
	if(empty($semaine_choisi_from)) 
		$semaine_choisi_from = (int)date("w",mktime(0,0,0,date("m"),1,date("Y")));
	$an_choisi = (empty($_POST['select_ans_from'])?$_GET['select_ans_from']:$_POST['select_ans_from']);
	if(empty($an_choisi))
		$an_choisi = date('Y');
	$semaine_choisi_to = (empty($_POST['select_semaine_to'])?$_GET['select_semaine_to']:$_POST['select_semaine_to']);
	if(empty($semaine_choisi_to)) 
		$semaine_choisi_to = (int)date("w",mktime(0,0,0,date("m"),1,date("Y"))) + 4;
	
$jour1_semaine1 = 1;
// La première semaine ne contient pas forcément le permier janvier

  for($k=1;$k<=7;$k++) {
    $first_jour = date('N',mktime(0,0,0,1,$k,$an_choisi)); //1= Lundi...
    if($first_jour == 4) {
      $first_jeudi = date('d',mktime(0,0,0,1,$k,$an_choisi));
    }
  }
  if($first_jeudi >= 4) $jour1_semaine1 = $first_jeudi - 3;
  else $jour1_semaine1 = 1;

	
		$nom_table = 'reports_'.$an_choisi;
/*		
		$db_requete = " 
			SELECT r.projid, r.actid, LOWER(TRIM(r.theme)) as theme, LOWER(TRIM(r.commentaire)) as libelle, r.userid, r.week, r.monday, r.tuesday, r.wednesday, r.thursday, r.friday, r.saturday, r.sunday
			FROM rodeo.".$nom_table." r, rodeo.projects p
			WHERE		
		";
		$db_requete .= $db_requete_option;
		$db_requete .= "r.week >= '$semaine_choisi_from' and r.week <= '$$semaine_choisi_to'"; 
*/				

	$semaine_choisi_plancher = ($semaine_choisi_from>12)?$semaine_choisi_from - 3 * 4:1; // 3 mois en arrière
	

	$db_requete = " 	
		(SELECT r.userid, name, surname, SUM(r.monday + r.tuesday + r.wednesday + r.thursday + r.friday + r.saturday + r.sunday)  as somme 
FROM .".$nom_table." AS r
left join users as u on u.userid = r.userid
WHERE week >= $semaine_choisi_from and week <= $semaine_choisi_to
GROUP BY r.userid
)	
UNION (
SELECT userid, name, surname , 0 as somme
FROM users
WHERE users.userid NOT
IN (

SELECT DISTINCT r2.userid
FROM .".$nom_table." AS r2
WHERE week >= $semaine_choisi_from and week <= $semaine_choisi_to

)

AND users.userid IN (
SELECT DISTINCT userid
FROM .".$nom_table."
WHERE week >= $semaine_choisi_plancher and week < $semaine_choisi_from
)
)
ORDER BY somme ASC
	";	
	
		/*
		Exemple
(
// Liste des Cumuls des activités sur la période demandée...
	SELECT r.userid, name, surname, SUM(r.monday + r.tuesday + r.wednesday + r.thursday + r.friday + r.saturday + r.sunday) as somme 
	FROM .reports_2012 AS r left join users as u on u.userid = r.userid 
	WHERE week >= 36 and week <= 39 
	GROUP BY r.userid 
) 
// ...Ajouttée à...
UNION 
( 
// ...Liste des personnes sans activités sur la période demandée...
	SELECT userid, name, surname , 0 as somme 
	FROM users 
	WHERE users.userid NOT IN ( 
		SELECT DISTINCT r2.userid 
		FROM .reports_2012 AS r2 
		WHERE week >= 36 and week <= 39 ) 
	AND users.userid IN ( 
// ...restreinte aux personnes ayant pointé dans les 3 derniers mois	
		SELECT DISTINCT userid 
		FROM .reports_2012 
		WHERE week >= 24 and week < 36 ) 
) 
ORDER BY somme ASC 
		*/	
	

//		print $db_requete; echo "<br />";
		$liste = array();
		$indice= $an_choisi*10000 + $semaine_periode*100;
		$db_result = mysql_query($db_requete, $db_connect);
		while($objet = mysql_fetch_object($db_result)) {	# au format objet pour avoir les libellés + valeurs de la table
			$liste[$indice] = $objet;

			$indice++; 
		}						# Tant qu'il y a des retours dans la requete: on continue
		



	
	
//	print_r($date_heures_detail); echo "<br />";
//	print_r($data_heures_cumul); echo "<br />";
?>
<html>
<head>
<title>Statistiques Rodeo - Pointages du mois</title>


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
	form {padding:10px; margin-left:30px; border:1px solid #AAA; background:#EEE}
	form select {width:200px}
	form label {width:200px}
	form select.an {width:100px}
-->
</style>
</head>

<body>
<h2>Rodéo - Pointages sur période</h2>
<div>
	<div id="haut"><a href="http://www.cbl-consulting-nord.com">&lt;&lt; Retour accueil</a></div>
	<hr />
</div>
<form id="f_criteres" action="rodeo_stats3.php" method="post" class="formulaire">
<ul>
	<li><label>Année :</label>
		<select name="select_ans_from" class='an'>
<?php for($i=2013;$i>=2007;$i--): ?>
				<option value="<?php echo $i; ?>" <?php echo (($an_choisi==$i)?'selected="selected"':''); ?>><?php echo $i; ?></option>
<?php endfor; ?>
		</select></li>
	<li><label>Du :</label>
		<select name="select_semaine_from">
<?php for($i=1;$i<=52;$i++): ?>

			<option <?php echo "value='$i'"; echo ($i==$_POST['select_semaine_from'])?'SELECTED':''; ?> ><?php echo "Lundi ".date('d/m',mktime(0,0,0,1,($jour1_semaine1 + ($i-1)*7),$an_choisi))." (semaine $i)"; ?></option>
<?php endfor; ?>
		</select></li>
	<li><label>Au :</label>
		<select name="select_semaine_to">
<?php for($i=1;$i<=52;$i++): ?>
			<option <?php echo "value='$i'"; echo ($i==$_POST['select_semaine_to'])?'SELECTED':''; ?> ><?php echo "Dimanche ".date('d/m',mktime(0,0,0,1,($jour1_semaine1 + ($i-1)*7 +6),$an_choisi))." (semaine $i)"; ?></option>
<?php endfor; ?>
		</select></li>
</ul>
	<BUTTON type="SUBMIT">Go!</BUTTON>			
</form>
<br />

<?php 
$select_mois = date('m',mktime(0,0,0,1,($jour1_semaine1 + ($semaine_choisi_from-1)*7),$an_choisi));
echo '<TABLE>';
echo "<TR><TH>User</TH><TH>Semaine</TH><TH>Nb d'heures</TH></TR>";
foreach($liste as $obj) {
	echo "<TR><TD><a href='http://lille/hrpic_tools/rodeo_to_hrpic.php?user_select=$obj->userid&select_mois=$select_mois&select_ans=$an_choisi'>$obj->name $obj->surname</TD><TD>$obj->week</TD><TD>$obj->somme</TD></TR>";
}
echo '</TABLE>';
 ?>

</body>
</html>