<style type="text/css">
<!--
table.zip_details {
    border: 3px double black;
    border-collapse: collapse;
}

table.zip_details td,
table.zip_details th {
    border: 1px solid black;
}

table.zip_fichiers td,
table.zip_fichiers th {
    border: 0px none;
}
-->
</style>

<?php
function formater_taille($taille) {
    $unites = array('o', 'ko', 'Mo', 'Go');

    for ($u = count($unites); $u >= 0; $u--) {
        if (isset($unites[$u]) && $taille >= 1024 * pow(1024, $u - 1)) {
            $taille = $taille / pow(1024, $u);
            $unite = $unites[$u];
            break;
        }
    }

    if ($u > 0) {
        return number_format($taille, 2, ',', ' ') . ' ' . $unite;
    } else {
        return $taille . ' ' . $unite;
    }
}

function afficher_zip($archive) {
    if (($zip = zip_open($archive)) === FALSE) {
        return FALSE;
    }
    echo '<table class="zip_details">';
    echo '<tr><th colspan="2">' . $archive . '</th></tr>';
    echo '<tr><td>Taille :</td><td>' . formater_taille(filesize('sources.zip')) . '</td></tr>';
    $nbEntrees = 0;
    echo '<tr>
        <td>Fichiers archivés :</td>
        <td><table class="zip_fichiers">
        <tr>
            <th>Nom</th>
            <th>Taille compressée</th>
            <th>Taille non compressée</th>
        </tr>';
    while ($entree = zip_read($zip)) {
        echo '<tr>
            <td>' . zip_entry_name($entree) . '</td>
            <td align="center">' . formater_taille(zip_entry_compressedsize($entree)) . '</td>
            <td align="center">' . formater_taille(zip_entry_filesize($entree)) . '</td>
        </tr>';
        $nbEntrees++;
    }
    echo '</table></td></tr>';
    echo '<tr><td>Nombre de fichiers archivés :</td><td>' . $nbEntrees . '</td></tr>';
    echo '</table>';
    zip_close($zip);
    return TRUE;
}

# Exemple d'utilisation
afficher_zip('sources');
?>
