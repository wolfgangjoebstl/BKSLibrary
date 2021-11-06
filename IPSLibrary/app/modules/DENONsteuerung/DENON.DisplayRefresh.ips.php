<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------


/*

Funktionen:
	*setzt Kommando zur Abfrage der aktuellen Display-Informationen des DENON AVR ab
	*Script kann z.B. durch ein zyklisches Event getrigert werden -> derzeit
		aber nicht Bestandteil des DENON.Installers

*/

//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");

IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ("DENONsteuerung_Configuration.inc.php","IPSLibrary::config::modules::DENONsteuerung");


$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('DENONsteuerung',$repository);
	}

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$scriptIdDENONsteuerung   = IPS_GetScriptIDByName('DENONsteuerung', $CategoryIdApp);

/* include DENON.Functions
  $id des DENON Client sockets muss nun selbst berechnet werden, war vorher automatisch
*/

if (IPS_GetObjectIDByName("DENON.Functions", $CategoryIdApp) >0)
    {
	include "DENON.Functions.ips.php";
    }
else
    {
	echo "Script DENON.Functions kann nicht gefunden werden!";
    }
$Denon_Power_val_all=false;
$configuration=Denon_Configuration();
foreach ($configuration as $config)
	{
	/* jeder denon receiver ist wie folgt definiert. IP Adresse muss derzeot fix sein.
    *         'NAME'               => 'Denon-Wohnzimmer',
    *         'IPADRESSE'          => '10.0.1.149',
    *         'INSTANZ'          	=> 'DENON1'
    */
	if ($config['TYPE']=="Denon")
		{
		$DENON_VAVR_IP = $config['IPADRESSE']; // hier die IP des DENON AVR angeben
		echo "\nDENON.DisplayRefresh for \"".$config['NAME']."\" started with IP Adresse ".$DENON_VAVR_IP."\n(c) Wolfgang Joebstl und www.raketenschnecke.net\n\n";
		$DENON_ID  = CreateCategory($config['NAME'], $CategoryIdData, 10);
		$DENON_MainZone_ID = @IPS_GetInstanceIDByName("Main Zone", $DENON_ID);
		// Timer Ein bei POWER ein
		echo "CategoryID vom receiver ".$config['NAME']." : ".$DENON_ID." (".IPS_GetName($DENON_ID)."/".IPS_GetName(IPS_GetParent($DENON_ID)).") und MainZone ID : ".$DENON_MainZone_ID." .\n";
		$DENON_Power_ID = IPS_GetObjectIDByName("Power", $DENON_MainZone_ID);
		$Denon_Power_val = getvalueBoolean($DENON_Power_ID);
		// Event "DisplayRefreshTimer" anlegen und zuweisen wenn nicht vorhanden
		$DENON_DisplayRefresh_ID = IPS_GetScriptIDByName("DENON.DisplayRefresh", $CategoryIdApp);
		$DisplayRefresh_EventID = IPS_GetObjectIDByName("DENON.DisplayRefreshTimer", $DENON_DisplayRefresh_ID);

		// ermitteln der DENON Quickselct Variablen-ID
		$Denon_Quickselect_ID = @IPS_GetObjectIDByName("QuickSelect", $DENON_MainZone_ID);
		if ($Denon_Quickselect_ID>0)
			{
			$Denon_Quickselect_val = getValueInteger($Denon_Quickselect_ID);
			}
		else
			{
			$Denon_Quickselect_val = 1;
			}

		if (($Denon_Power_val == true) && ($Denon_Quickselect_val == 1))
			{
			$Denon_Power_val_all = true;
			}

		// Client Socket "DENON Client Socket" anlegen wenn nicht vorhanden
		$id = @IPS_GetObjectIDByName($config['INSTANZ']." Client Socket", 0);
		DENON_NSA_DisplayRequest($id);
		} // nur wenn ein Denon Gerät
	}

if ($Denon_Power_val_all == true)
	{
	IPS_SetEventActive($DisplayRefresh_EventID, true);
	}
else
	{
	IPS_SetEventActive($DisplayRefresh_EventID, false);
	}

?>