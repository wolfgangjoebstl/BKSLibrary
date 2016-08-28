<?

// Dieses Skript �bermittelt Nachrichten und Befehle an IPSWatchDog
// (c) 2011 by Andreas Pieroth
//
// Es MUSS von einem anderen Skript aufgerufen werden, welches in der
// Variablen $state die zu �bermittelnde Nachricht enth�lt.
// Beispiel:
// IPS_RunScriptEx(<ID dieses Skriptes>, Array('state' =>  'Nachricht'));


// Hier wird der Dateiname der Message-Datei festgelegt.
// Er ist standardm��ig auf "message.iwd" gesetzt und muss, falls ge�ndert, im
// Setup von IPSWatchDog angepasst werden!

$DateiName = 'message.iwd';


// Parent-ID der Kategorie ermitteln
$parentID = IPS_GetObject($IPS_SELF);
$parentID = $parentID['ParentID'];


define("MessageDateiName", "..\\".$DateiName); //Dateiname f�r Nachrichten-Datei definieren
//erst mal alle Dateileichen l�schen
@unlink (MessageDateiName);

$datei = fopen(MessageDateiName, "a");
fwrite ($datei, $state);
fclose($datei);

?>