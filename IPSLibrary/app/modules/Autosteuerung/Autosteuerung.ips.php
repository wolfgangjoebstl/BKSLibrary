<?

/***********************************************************************

Automatisches Ansteuern der Heizung, durch Timer, mit Overwrite etc.

zB durch wenn die FS20-STR einen Heizkoerper ansteuert, gleich wieder den Status aendern

funktioniert nur mit elektrischen Heizkoerpern

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ("Stromheizung_Configuration.inc.php","IPSLibrary::config::modules::Stromheizung");

/******************************************************

				INIT

*************************************************************/

$dataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('Stromheizung',$repository);
}
$gartensteuerung=false;
$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,20)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

$EinschaltdauerID = CreateVariableByName($dataID, "Einschaltdauer", 1);
$Temp1AnfangID = CreateVariableByName($dataID, "StartTemperatur1", 2);
$Temp2AnfangID = CreateVariableByName($dataID, "StartTemperatur2", 2);
$TempErhoehungID = CreateVariableByName($dataID, "DeltaTemperatur", 2);

$arr=LogAlles_Configuration();    /* Konfigurationsfile mit allen Temperatur Variablen  */
$WZ_TempID=$arr["WZ"]["OID_Temp"];
$KZ_TempID=$arr["KZ"]["OID_Temp"];

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);

	switch ($_IPS['VALUE'])
		{
		case "2":  /* Auto */
		   /* im Auto Mode sichergehen dass Zusatzheizung nicht eingeschaltet bleibt */
	  		FS20_SwitchMode(ADR_Zusatzheizung_KZ, false); //Gerät komplett ausschalten
	  		FS20_SwitchMode(ADR_Zusatzheizung_WZ, false); //Gerät komplett ausschalten
			break;
		case "3":  /* 4 Stunden Timer ein */
			IPS_SetScriptTimer($_IPS['SELF'], 10*60);
			SetValue($EinschaltdauerID,24);    /* 4 Stunden lang vorheizen,  in 10 Minuten Schritten einstellen */
			SetValue(57474,240);
			FS20_SwitchDuration(ADR_Zusatzheizung_KZ, true, 800); //Gerät für 800 sek einschalten
			FS20_SwitchDuration(ADR_Zusatzheizung_WZ, true, 800); //Gerät für 800 sek einschalten
			SetValue($Temp1AnfangID,GetValue($WZ_TempID));
			SetValue($Temp2AnfangID,GetValue($KZ_TempID));

			/* ein email zur Bestätigung schicken */
		 	$ergebnis=send_status();
		 	SetValue(14087,"Heizung an ".date("Y.m.d D H:i:s")." : ".$ergebnis);
			IPS_RunScript(49434);
			break;
		case "4":  /* 8 Stunden Timer ein */
			IPS_SetScriptTimer($_IPS['SELF'], 10*60);
			SetValue($EinschaltdauerID,48);    /* 8 Stunden lang vorheizen,  in 10 Minuten Schritten einstellen */
			SetValue(57474,480);
			FS20_SwitchDuration(ADR_Zusatzheizung_KZ, true, 800); //Gerät für 800 sek einschalten
			FS20_SwitchDuration(ADR_Zusatzheizung_WZ, true, 800); //Gerät für 800 sek einschalten
			SetValue($Temp1AnfangID,GetValue($WZ_TempID));
			SetValue($Temp2AnfangID,GetValue($KZ_TempID));

			/* ein email zur Bestätigung schicken */
		 	$ergebnis=send_status();
		 	SetValue(14087,"Heizung an ".date("Y.m.d D H:i:s")." : ".$ergebnis);
			IPS_RunScript(49434);
			break;
		case "5":   /* Power Modus, Zusatzheizung folgt normaler Heizung */
		   break;
		}

	OverWriteSwitches(ADR_Heizung_AZ);
	OverWriteSwitches(ADR_Heizung_KZ);
	OverWriteSwitches(ADR_Heizung_WZ);
	OverWriteSwitches(ADR_Zusatzheizung_KZ);
	OverWriteSwitches(ADR_Zusatzheizung_WZ);

	}

if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */

	}

if ($_IPS['SENDER']=="Variable")
	{
	/* eine Variablenaenderung ist aufgetreten */

	switch ($_IPS['VARIABLE'])
		{

		/* Positionswerte geändert */

   case "32688": /* Arbeitszimmer Pos Aenderung Heizung*/
		If ($_IPS['VALUE'])
			{
			WriteLogEvent("Heizungssteuerung AZ ein");
			}
		else
		   {
			WriteLogEvent("Heizungssteuerung AZ aus");
			}
		OverWriteSwitches(ADR_Heizung_AZ);
 		break;

	case "10884": /* Kellerzimmer Pos Aenderung Heizung*/
		If ($_IPS['VALUE'])
			{
			WriteLogEvent("Heizungssteuerung KZ ein");
			}
		else
		   {
			WriteLogEvent("Heizungssteuerung KZ aus");
			}
		OverWriteSwitches(ADR_Heizung_KZ);
		break;

	case "17661": /* Wohnzimmer Pos Aenderung Heizung*/
		If ($_IPS['VALUE'])
			{
			WriteLogEvent("Heizungssteuerung WZ ein");
			}
		else
		   {
			WriteLogEvent("Heizungssteuerung WZ aus");
			}
		OverWriteSwitches(ADR_Heizung_WZ);
		break;

	case "39253": /* Kellerzimmer Pos Aenderung Zusatz-Heizung*/
		If ($_IPS['VALUE'])
			{
			WriteLogEvent("Heizungssteuerung KZ-Zusatz ein");
			}
		else
		   {
			WriteLogEvent("Heizungssteuerung KZ-Zusatz aus");
			}
		OverWriteSwitches(ADR_Zusatzheizung_KZ);
		break;

	case "33800": /* Wohnzimmer Pos Aenderung Zusatz-Heizung*/
		If ($_IPS['VALUE'])
			{
			WriteLogEvent("Heizungssteuerung WZ-Zusatz ein");
			}
		else
		   {
			WriteLogEvent("Heizungssteuerung WZ-Zusatz aus");
			}
		OverWriteSwitches(ADR_Zusatzheizung_WZ);
		break;

		}
	}


if ($_IPS['SENDER']=="TimerEvent")
	{
	$Restzeit=GetValue($EinschaltdauerID)-1;
	SetValue(57474,$Restzeit*10);
	$TempErhoehung=(GetValue($WZ_TempID)-GetValue($Temp1AnfangID)+GetValue($KZ_TempID)-GetValue($Temp2AnfangID))/2;
	SetValue($TempErhoehungID,$TempErhoehung);

 	$ergebnis=send_status();
 	$ergebnis_lang=$ergebnis."\n\nTemperaturerehoehung seit Einschaltzeitpunkt:\n\n".number_format($TempErhoehung, 2, ",", "" )."°C\n ";

 	if ((GetValue($WZ_TempID)>22) or (GetValue($KZ_TempID)>20))
 	   {
 	   /* Wenn eine bestimmte Zemperatur bereits erreicht wurde, gleich ausschalten */
		$Restzeit=0;
		SetValue(57474,$Restzeit*10);
	  	WriteLogEvent("Zusatzheizung Temperatur erreicht. Temperatur WZ:".GetValue($WZ_TempID)." und KZ:".GetValue($KZ_TempID));
		}

	if ($Restzeit>0)
	   {
   	SetValue($EinschaltdauerID,$Restzeit);
   	IPS_SetScriptTimer($_IPS['SELF'], 10*60);
   	FS20_SwitchDuration(ADR_Zusatzheizung_KZ, true, 800); //Gerät für 800 sek einschalten
		FS20_SwitchDuration(ADR_Zusatzheizung_WZ, true, 800); //Gerät für 800 sek einschalten
		$ergebnis="Status ".date("Y.m.d D H:i:s")." : ".$ergebnis;
 		$ergebnis_lang="Status OK : Zusatzheizung aktiviert".$ergebnis_lang."\n\n";
	  	WriteLogEvent("Zusatzheizung aktiviert. Temperatur WZ:".GetValue($WZ_TempID)." und KZ:".GetValue($KZ_TempID));
	   }
	else
	   {
	   /* Zeit abgelaufen, Timer nicht mehr aufrufen, abschliessendes email senden, mit Erfolgsmeldung */
	   IPS_SetScriptTimer($_IPS['SELF'], 0);
		$ergebnis="Heizung aus ".date("Y.m.d D H:i:s")." : ".$ergebnis;
		$ergebnis_lang="Status OK : Zusatzheizung wieder deaktiviert".$ergebnis_lang."\n\n";
	  	WriteLogEvent("Zusatzheizung wieder deaktiviert. Temperatur WZ:".GetValue($WZ_TempID)." und KZ:".GetValue($KZ_TempID));
		/* nach Ablauf des Events wieder zurück in den Auto Mode schalten */
		SetValue(19155,2);
		}
	if (($Restzeit/6)==(round($Restzeit/6, 0, PHP_ROUND_HALF_DOWN)))
	   {
	   /* das email aber nur jede volle Stunde abschicken */
		SMTP_SendMail($sendResponse, date("Y.m.d D H:i:s")." Status BKS01 (email response)", $ergebnis_lang);
	 	SetValue(14087,$ergebnis);
		IPS_RunScript(49434);
		}
	}


/*********************************************************************************************/

function OverWriteSwitches($adresse)
{

$status=GetValue(19155);
switch ($status)
	{
	case "0":   /* dauernd aus */
	  	FS20_SwitchMode($adresse, false); //Gerät komplett ausschalten
	  	WriteLogEvent("    -> schaltet ".$adresse." aus");
		break;

	case "1":   /* dauernd ein */
	  	FS20_SwitchDuration($adresse, true, 800); //Gerät nur mit Timer für 800 sek einschalten
	  	WriteLogEvent("    -> schaltet ".$adresse." fuer 800 Sek ein");
		break;

	case "2":   /* Auto */
	  	WriteLogEvent("    -> schaltet ".$adresse." nicht");
	   break;

	case "3":   /* 4 Stunden */
	  	FS20_SwitchDuration($adresse, true, 800); //Gerät nur mit Timer für 800 sek einschalten
	  	WriteLogEvent("    -> schaltet ".$adresse." fuer 800 Sek ein (4 Stunden Modus)");
	   break;

	case "4":   /* 8 Stunden */
	  	FS20_SwitchDuration($adresse, true, 800); //Gerät nur mit Timer für 800 sek einschalten
	  	WriteLogEvent("    -> schaltet ".$adresse." fuer 800 Sek ein (8 Stunden Modus)");
	   break;

	case "5":   /* Power */
		switch ($adresse)
		   {
		   case "10884": /* Kellerzimmer Pos Aenderung Heizung*/
		      if (GetValue(10884)==true)
		         {
	  				FS20_SwitchDuration(ADR_Zusatzheizung_KZ, true, 800); //Gerät nur mit Timer für 800 sek einschalten
	  				WriteLogEvent("    -> schaltet ".ADR_Zusatzheizung_KZ." fuer 800 Sek ein (Power Modus)");
					}
		   break;
			case "17661": /* Wohnzimmer Pos Aenderung Heizung*/
		      if (GetValue(17661)==true)
		         {
	  				FS20_SwitchDuration(ADR_Zusatzheizung_WZ, true, 800); //Gerät nur mit Timer für 800 sek einschalten
	  				WriteLogEvent("    -> schaltet ".ADR_Zusatzheizung_WZ." fuer 800 Sek ein (Power Modus)");
					}
			break;
			}
	  	FS20_SwitchDuration($adresse, true, 800); //Gerät nur mit Timer für 800 sek einschalten
	  	WriteLogEvent("    -> schaltet ".$adresse." fuer 800 Sek ein (8 Stunden Modus)");
	   break;

	}
}



?>
