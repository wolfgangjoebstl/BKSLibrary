<?
// Dieses Skript erstellt die Alive-Datei zur �bermittlung der Alive-Meldung
// an IPSWatchDog per Datei

// Hier wird der Dateiname der Alive-Datei festgelegt.
// Er ist standardm��ig auf "alive.ips" gesetzt und muss, falls ge�ndert, im
// Setup von IPSWatchDog angepasst werden!

$DateiName = 'alive.ips';


// ab hier nichts mehr ver�ndern!!!

define("DateiName", "..\\".$DateiName); //Dateiname f�r alive Datei definieren

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