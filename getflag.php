<?php
function get2($three) {
    $f = fopen('countries.csv', 'r');
    while (($data = fgetcsv($f)) !== FALSE) {
        if ($data[2] == $three) {
            fclose($f);
            return ':flag-'.strtolower($data[1]).':';
        }
    }
    fclose($f);
    return FALSE;
}
?>
