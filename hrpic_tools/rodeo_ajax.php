<?php
//	header("Content-Type: text/plain"); // Utilisation d'un header pour spécifier le type de contenu de la page. Ici, il s'agit juste de texte brut (text/plain).
	include "hrpic_tools_lib.php";
	
	$db_connect = null;
	$ajax_action = null;
	
	$db_connect = mysqli_connect("localhost", "rodeo_user", "123RoDeO_UsEr/456");
//	// Check connection
//	if ($db_connect->connect_error) {
	  //die("Connection failed: " . $db_connect->connect_error);
//	}
//	echo "Connected successfully";
	
	$ajax_action = (!empty($_POST["ajax_action"]))?$_POST["ajax_action"]:null;
	
	if($ajax_action != null) {
		switch($ajax_action){
			case "evolUpdate" :
				$value = (($_POST["value"] != ' ')?$_POST["value"]:trim($_POST["value"]));
				if(substr($_POST["field"], 0, 4) == "date") {
					if($value != "now()")
						$value = "'".date_french_to_sql($value)."'";
				} else
					$value = "'".mysqli_real_escape_string($db_connect, $value)."'";
				$db_requete = "
					UPDATE rodeo.suivi_evolution 
					SET ".$_POST["field"]." = ".$value."
					WHERE projid = '".$_POST["projid_maj"]."'
					  AND evolid = '".$_POST["evolid_maj"]."';";
//				print($db_requete);
				$db_resultat = mysqli_query($db_connect, $db_requete);
				if(substr($_POST["field"], 0, 5) == "jours") {
					$db_requete = "
						SELECT (jours_analyse + jours_developpement + jours_parametrage + jours_test + jours_documentation) as jours_total
						FROM rodeo.suivi_evolution
						WHERE projid = '".$_POST["projid_maj"]."'
						  AND evolid = '".$_POST["evolid_maj"]."';";
					$db_resultat = mysqli_query($db_connect, $db_requete);
					while($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC)) {
						$db_requete = "
							UPDATE rodeo.suivi_evolution 
							SET jours_total = ".$ligne["jours_total"]."
							WHERE projid = '".$_POST["projid_maj"]."'
							  AND evolid = '".$_POST["evolid_maj"]."';";
						$db_resultat = mysqli_query($db_connect, $db_requete);
					}
					
				}
				$db_requete = "
					SELECT " .$_POST["field"]."
					FROM rodeo.suivi_evolution 
					WHERE projid = '".$_POST["projid_maj"]."'
					  AND evolid = '".$_POST["evolid_maj"]."';";
//				print($db_requete);
				$db_resultat = mysqli_query($db_connect, $db_requete);
				while($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC)) {
					if(substr($_POST["field"], 0, 4) == "date")
						echo date_sql_to_french($ligne[$_POST["field"]]);
					else
						echo $ligne[$_POST["field"]];
				}
				$db_requete = null;
				break;
			case "environnementUpdate" :
				$value = (($_POST["value"] != ' ')?$_POST["value"]:trim($_POST["value"]));
				$value = "'".mysqli_real_escape_string($db_connect, $value)."'";
				$db_requete = "
					UPDATE hrpic.environnement 
					SET ".$_POST["field"]." = ".$value."
					WHERE id = '".$_POST["envid_maj"]."';";
//				print($db_requete);
				$db_resultat = mysqli_query($db_connect, $db_requete);
				$db_requete = "
					SELECT " .$_POST["field"]."
					FROM hrpic.environnement 
					WHERE id = '".$_POST["envid_maj"]."';";
//				print($db_requete);
				$db_resultat = mysqli_query($db_connect, $db_requete);
				while($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC)) {
					if(substr($_POST["field"], 0, 4) == "date")
						echo date_sql_to_french($ligne[$_POST["field"]]);
					else
						echo $ligne[$_POST["field"]];
				}
				$db_requete = null;
				break;
			case "checkEvolId" :
				$projid = $_POST["projid"];
				$evolid = $_POST["evolid"];
				
				//  Liste des evolutions
				$db_requete = "
					SELECT DISTINCT projid 
					FROM rodeo.suivi_evolution 
					WHERE projid = ".$projid.";
				";
			//	print $db_requete;
				$db_resultat = mysqli_query($db_connect, $db_requete);
				$i = 0;
				while (mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
					++$i;
				mysqli_free_result($db_resultat);
				
				if(!$i)
					echo 1;
				else {
					$db_requete = "
						SELECT DISTINCT evolid 
						FROM rodeo.suivi_evolution 
						WHERE projid = ".$projid."
						  AND evolid = ".$evolid.";
					";
				//	print $db_requete;
					$db_resultat = mysqli_query($db_connect, $db_requete);
					$i = 0;
					while (mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
						++$i;
					mysqli_free_result($db_resultat);
					
					if(!$i)
						echo 2;
					else
						echo 0;
				}
				break;
			case "requestEvolList" :
				$projid = $_POST["projid"];
				$selectedvalue = $_POST["selectedvalue"];
				
				//  Liste des evolutions
				$db_requete = "
					SELECT DISTINCT evolid, libelle
					FROM rodeo.suivi_evolution
					WHERE projid = ".$projid."
					ORDER BY evolid DESC
					LIMIT 70";
				//print $db_requete;
				$db_resultat = mysqli_query($db_connect, $db_requete);
				$result = "";
				while ($ligne = mysqli_fetch_array($db_resultat, MYSQLI_ASSOC))
					$result .= "<option value='".$ligne["evolid"]."'".($ligne["evolid"]==$selectedvalue?" selected='selected'":"").">".$ligne["evolid"]." - ".substr($ligne["libelle"],0,35)."</option>";
				mysqli_free_result($db_resultat);
				
				echo $result;
				
				break;
		}
	} else
		echo -1;
?>
