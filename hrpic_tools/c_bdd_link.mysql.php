<?php

# Classe de connexion pour la bdd Mysql
# VERSION 0

class bdd_link {
	
	var $host="localhost";
	var $bdd="rodeo";
	var $user ="rodeo_user";
	var $passwd="123RoDeO_UsEr/456";

	
	var $table = 'users';
	var $error = '';
	var $status = 0; /* 1 connecté, 0 non connecté*/
	var $link = null;

	var $sql_where = ''; /* Les conditions du WHERE sur le dernier filtre */
	var $sql_set = ''; /* Les derniers éléments ajoutés*/
	var $nb_elements = 0; /* Le nombre d'éléments retourné par la requete*/
	var $sql_request = ''; /* La dernière commande sql passée*/

	/* Paramètres de connexion */
	function connexion() {
		$link = mysqli_connect($this->host, $this->user, $this->passwd);

		if(!$link) {
			$this->error = 'Erreur de connexion au serveur de bases de donn&eacute;es';
			return(0);}
		elseif(!mysqli_select_db($link, $this->bdd)) {
			$this->error = 'Erreur de connexion &agrave; la base de donn&eacute;es';
			return(0);}
		else {$this->status=1;$this->link=$link;return(1);}
	}

	/* Le créateur va faire une liaison à la bdd voulue */	
	function bdd_link() {
		if($this->connexion()) {$this->error='';}
		return($this->status);
	}

	/* Déconnexion */
	function deconnexion() {
		if($this->status) mysqli_close();
		$this->status = 0;
	}

	/* Selection des données d'une table avec filtre */
	/* $Option est une chaine au format SQL */
	function filtre($option= '', $table='',$champs='*') {
		//if(!$this->status) $this->connexion();
 		if($this->status) {
			$this->table=($table!='')? $table : $this->table;
			$this->nb_elements = 0;
			$this->sql_where = $option;
 			$conditions=($option=='')? '' : "WHERE $option"; 
 			$sql= " SELECT $champs FROM $this->table $conditions";	# Requete à passer
			$this->sql_request=$sql;

			$result = mysqli_query($this->link, $sql);

 			if(!$result) {
				$this->error = 'Erreur de requ&ecirc;te SQL';
				return(-1);
			}
 			else {							# Sinon on recupère le résultat
 				$indice=0;
 				while($objet = mysqli_fetch_object($result)) {	# au format objet pour avoir les libellés + valeurs de la table
  					$liste[$indice] = $objet;
  					$indice++; 
  				}						# Tant qu'il y a des retours dans la requete: on continue
				$this->nb_elements=$indice;
				$this->error = '';
				return($liste);
 			}
		}
		else {
			$this->error = 'Erreur : connexion au serveur de base de donn&eacute;es non ouverte';
			$sql_where = '';
			$nb_elements = 0;
			$sql_request = '';
			return(0);
		}
	}


	/* Insertion dans la base de données */
	/* Liste contient la ligne a inserer au format 'nom_champ'=>'valeur' */
	function inserer_ligne($liste=NULL,$table='') {	
		//if(!$this->status) $this->connexion();
		if($this->status){
			$contenu="";
			foreach($liste as $key => $val) {$contenu.=" $key = '$val',";}
			$contenu=substr($contenu,0,-1); # on supprime la derniere virgule en trop
			$this->table=($table!='')? $table : $this->table;
			$sql="INSERT INTO ".$this->table." SET $contenu";
			$this->sql_request=$sql;
			$this->sql_set=$contenu;
 			$result = mysqli_query($sql);
			if(!$result) {
				$this->error = 'Erreur: l\'insertion dans la base de donn&eacute;es a &eacute;chou&eacute;';
				return(0);
			}
 			else {$this->error =''; return(1);}	
		}
		else {
			$this->error = 'Erreur : connexion au serveur de base de donn&eacute;es non ouverte';
			$sql_request = '';
			return(0);
		}	 
	}

	/* Modifier une ligne */
	/* $liste contient l'ensemble des valeurs a modifier au format 'Nom_champ'=>'valeur' */
	/* $condition permet d'identifier les lignes a modifier en renseignant le WHERE */
	/* $condition est une chaine au format SQL */
	function modifier_ligne($liste='',$condition='',$table='') {
		//if(!$this->status) $this->connexion();
		if( ($liste=='')||($condition=='')) {
			$this->error = 'Erreur : Paramètres manquants pour modification';
			$sql_request = '';
			return(0);
		}
		else {
			$this->table=($table!='')? $table : $this->table;
			$contenu="";
			foreach($liste as $key => $val) {$contenu.=" $key = '$val',";}
			$contenu=substr($contenu,0,-1); # on supprime la derniere virgule en trop
			$sql="UPDATE `$this->table` SET $contenu WHERE $condition";
			$this->sql_where=$condition;
			$this->sql_set=$contenu;
			$this->sql_request=$sql;
			$result = mysqli_query($sql);
			if(!$result) {
				$this->error = 'Erreur: la modification de la base de donn&eacute;es a &eacute;chou&eacute;';
				return(0);
			}
 			else {$this->error =''; return(1);}
		}
		
	}

	/* Supprimer une ligne */
	/* $condition permet d'identifier les lignes a supprimer en renseignant le WHERE */
	/* $condition est une chaine au format SQL */
	function supprimer_ligne($condition='',$table='') {
		//if(!$this->status) $this->connexion();
		if($condition=='') {
			$this->error = 'Erreur : Paramètres manquants pour suppression';
			$sql_request = '';
			return(0);
		}
		else {
			$this->table=($table!='')? $table : $this->table;
			$sql="DELETE FROM `$this->table` WHERE $condition";
			$this->sql_where=$condition;
			$this->sql_request=$sql;
			$result = mysqli_query($sql);
			if(!$result) {
				$this->error = 'Erreur: la suppression dans la base de donn&eacute;es a &eacute;chou&eacute;';
				return(0);
			}
 			else {$this->error =''; return(1);}
		}
	}

	/* Afficher Erreur*/
	function afficher_erreur() {
		if($this->error != '') {
			echo '<div class="error">';
			echo '<b>'.$this->error.'</b><br />';
			if($this->sql_request != '') echo $this->sql_request.'<br />';
			if(!$this->status) echo "Vous n'&ecirc;tes pas connect&eacute; &agrave; la base de donn&eacute;es<br />";
			echo "</div>";
		}		
	}

  
  
	function enum($field,$table='') {
		$this->table=($table!='')? $table : $this->table;
		$result = @mysqli_query("show columns from {$table} like \"$field\"");
		$result = @mysqli_fetch_assoc($result);
		if($result["Type"]) {
			preg_match("/(enum\((.*?)\))/", $result["Type"], $enumArray);
			$getEnumSet = explode("'", $enumArray["2"]);
 			$getEnumSet = preg_replace("/,/", "", $getEnumSet);
			$enumFields = array();
			foreach($getEnumSet as $enumFieldValue) {
				if($enumFieldValue) { $enumFields[] = $enumFieldValue; }
			}
			return $enumFields;
		}
		return "l'enumération n'a pas fonctionne";
	}

}/*END CLASS*/
