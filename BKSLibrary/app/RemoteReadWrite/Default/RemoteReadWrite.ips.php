<?

 //Fügen Sie hier Ihren Skriptquellcode ein

/* Wie beim Webfront Install, für die Wichtigen Register ein Spiegelregister anlegen und
alle 2 Sekunden lesen, bzw bei Aenderung am Webfront auch schreiben

*/


Include(IPS_GetKernelDir()."scripts\AllgemeineDefinitionen.inc.php");
include(IPS_GetKernelDir()."scripts\\".IPS_GetScriptFile(35115 /*[Program\_include\Logging Class]*/));

/* Typ 0 Boolean 1 Integer 2 Float 3 String */
$StatusID = CreateVariableByName($_IPS['SELF'], "StatusReadWrite-BKS", 0);
$InnnenTempID = CreateVariableByName($_IPS['SELF'], "Innentemperatur-BKS", 3);
$AussenTempID = CreateVariableByName($_IPS['SELF'], "Aussentemperatur-BKS", 3);
$KellerMinTempID = CreateVariableByName($_IPS['SELF'], "KellerMintemperatur-BKS", 3);
$HeizleistungID = CreateVariableByName($_IPS['SELF'], "Heizleistung-BKS", 3);
$letzterWertID = CreateVariableByName($_IPS['SELF'], "LetzterWert-BKS", 3);

//echo "Connect to http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/";
$rpc = new JSONRPC("http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/");
$ergebnis=$rpc->GetValueFormatted(56688 /*[Visualization-Backup\Webfront\Zusatzheizung\Innentemperatur]*/);
if ($ergebnis)
	{
	SetValueBoolean($StatusID,true);
	SetValue($InnnenTempID,$ergebnis);
	SetValue($letzterWertID,date("d.m.y H:i:s").": Innentemperatur");
	}
else
	{
	SetValueBoolean($StatusID,false);
	}

$ergebnis=$rpc->GetValueFormatted(21416 /*[Visualization-Backup\Webfront\Zusatzheizung\Aussentemperatur]*/);
if ($ergebnis)
	{
	SetValueBoolean($StatusID,true);
	SetValue($AussenTempID,$ergebnis);
	SetValue($letzterWertID,date("d.m.y H:i:s").": Aussentemperatur");
	}
else
	{
	SetValueBoolean($StatusID,false);
	}

$ergebnis=$rpc->GetValueFormatted(52129 /*[Visualization-Backup\Webfront\Temperatur\Zusammenfassung2\Keller\KZ-Temperatur (gestern, Minimum)]*/);
if ($ergebnis)
	{
	SetValueBoolean($StatusID,true);
	SetValue($KellerMinTempID,$ergebnis);
	SetValue($letzterWertID,date("d.m.y H:i:s").": Keller Minimum Temperatur (gestern)");
	}
else
	{
	SetValueBoolean($StatusID,false);
	}

$ergebnis=$rpc->GetValueFormatted(34354 /*[Program\BKSLibrary\app\Stromheizung\Berechnung Energie Temperatur\Summe_Leistung]*/);
if ($ergebnis)
	{
	SetValueBoolean($StatusID,true);
	SetValue($HeizleistungID,$ergebnis);
	SetValue($letzterWertID,date("d.m.y H:i:s").": Heizleistung");
	}
else
	{
	SetValueBoolean($StatusID,false);
	}




?>