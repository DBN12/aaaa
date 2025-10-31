<?php
function date_sql_to_french($date){
	if($date != null && $date != '0000-00-00')
		return date("d/m/Y", strtotime($date));
}

function date_french_to_sql($date){
	return substr($date,6,4)."-".substr($date,3,2)."-".substr($date,0,2);
}

function text_cut($texte){
	if(strlen($texte) > 12)
		return substr($texte,0,5)."...".substr($texte,strlen($texte)-5,5);
	else
		return $texte;
}
?>