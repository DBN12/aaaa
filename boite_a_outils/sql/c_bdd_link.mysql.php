<?php

	
	$host="127.0.0.1";

	$user="root";
	$passwd="d10lillE2005";
	$bdd="hrexp_mvc";


		if(!mysql_connect($host, $user, $passwd)) {
			echo 'Erreur de connexion au serveur de bases de données';
			}
		elseif(!mysql_select_db($bdd)) {
			echo 'Erreur de connexion à la base de données';
			}
		else echo 'OK';
?>