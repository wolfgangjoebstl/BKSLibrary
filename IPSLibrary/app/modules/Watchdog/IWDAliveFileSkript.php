<?
// Dieses Skript erstellt die Alive-Datei zur Übermittlung der Alive-Meldung
// an IPSWatchDog per Datei

// Hier wird der Dateiname der Alive-Datei festgelegt.
// Er ist standardmäßig auf "alive.ips" gesetzt und muss, falls geändert, im
// Setup von IPSWatchDog angepasst werden!

$DateiName = 'alive.ips';


// ab hier nichts mehr verändern!!!

define("DateiName", "..\\".$DateiName); //Dateiname für alive Datei definieren

//Datei vorhanden?
$dateifehlt = !file_exists(DateiName);

//falls die Datei fehlt, neu anlegen
if ($dateifehlt) {
		$inhalt = date("d.m.y - H:i");
		$datei = fopen(DateiName, "a");
		fwrite ($datei, $inhalt);
		fclose($datei);
		}
?>