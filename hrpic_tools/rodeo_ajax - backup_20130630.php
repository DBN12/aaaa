<?php
//	header("Content-Type: text/plain"); // Utilisation d'un header pour spécifier le type de contenu de la page. Ici, il s'agit juste de texte brut (text/plain).
	
	$db_connect = null;
	$ajax_action = null;
	
	$db_connect = mysql_connect("127.0.0.1", "hrpic_admin", "hrpic_admin");
	
	$ajax_action = (!empty($_POST["ajax_action"]))?$_POST["ajax_action"]:null;
	
	if($ajax_action != null) {
		switch($ajax_action){
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
				$db_resultat = mysql_query($db_requete, $db_connect);
				$i = 0;
				while (mysql_fetch_array($db_resultat, MYSQL_ASSOC))
					++$i;
				mysql_free_result($db_resultat);
				
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
					$db_resultat = mysql_query($db_requete, $db_connect);
					$i = 0;
					while (mysql_fetch_array($db_resultat, MYSQL_ASSOC))
						++$i;
					mysql_free_result($db_resultat);
					
					if(!$i)
						echo 2;
					else
						echo 0;
				}
				break;
		}
	} else
		echo -1;
?>