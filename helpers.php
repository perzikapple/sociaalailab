<?php
function formatEventDateDisplay($dateValue) {
    if (empty($dateValue)) return '';
    $ts = strtotime((string)$dateValue);
    if ($ts === false) return (string)$dateValue;
    $monthsNl = [
        1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april', 5 => 'mei', 6 => 'juni',
        7 => 'juli', 8 => 'augustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december'
    ];
    $day = (int)date('j', $ts);
    $month = $monthsNl[(int)date('n', $ts)] ?? date('F', $ts);
    $year = date('Y', $ts);
    return $day . ' ' . $month . ' ' . $year;
}

function formatEventTimeDisplay($timeValue) {
    if (empty($timeValue)) return '';
    $ts = strtotime((string)$timeValue);
    if ($ts === false) return (string)$timeValue;
    return date('H:i', $ts);
}

function googleMapsDirectionsUrl($destination) {
    if (empty($destination)) return '#';
    return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode((string)$destination);
}

?>
