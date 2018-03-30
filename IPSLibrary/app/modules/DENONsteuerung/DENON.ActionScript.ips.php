<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

/*

Funktionen:
	*wird vom Script "DENON.Install_Library" in allen DENON-Variablen als Actionsript
		in den Variableneigenschaften der /data Variablen Einträge eingetragen
	* sendet (WFE-)Kommandos an das DENON.Functions-Script
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DENONsteuerung\DENONsteuerung.Library.inc.php");

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/****************************************************************/


$DENON=new DENONsteuerung();

/****************************************************************/

if ($_IPS['SENDER'] == "Execute")
	{
	echo "Script wurde direkt aufgerufen.\n";
	echo "\n";
	//echo "Category App           ID: ".$CategoryIdApp."\n";
	//echo "Category Data          ID: ".$CategoryIdData."\n";
	//echo "Webfront Administrator ID: ".$categoryId_WebFront."     ".$WFC10_Path."\n";
	//echo "Nachrichten Script     ID: ".$NachrichtenScriptID."\n";
	//echo "Nachrichten      Input ID: ".$NachrichtenInputID."\n\n";

	$DENON->LogMessage("Activity Script wurde direkt aufgerufen");
	$DENON->LogNachrichten("Activity Script wurde direkt aufgerufen");
	}
else
	{
	$DENON->Activity($_IPS['VARIABLE'],$_IPS['VALUE']);
	}

?>