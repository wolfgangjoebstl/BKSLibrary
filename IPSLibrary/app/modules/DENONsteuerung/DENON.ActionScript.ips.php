<?
//--------- DENON AVR 3311 Anbindung V0.95 18.06.11 15:08.53 by Raketenschnecke ---------

/*

Funktionen:
	*wird vom Script "DENON.Install_Library" in allen DENON-Variablen als Actionsript
		in den Variableneigenschaften eingetragen
	* sendet (WFE-)Kommandos an das DENON.Functions-Script
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/****************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	//echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('DENONsteuerung',$repository);
	}

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptIdDENONsteuerung   = IPS_GetScriptIDByName('DENONsteuerung', $CategoryIdApp);
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

$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);

$object_data= new ipsobject($CategoryIdData);
$object_app= new ipsobject($CategoryIdApp);

$NachrichtenID = $object_data->osearch("Nachricht");
$NachrichtenScriptID  = $object_app->osearch("Nachricht");

if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($NachrichtenID);
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	/* logging in einem File und in einem String am Webfront */
	$log_Denon=new Logging("C:\Scripts\Log_Denon.csv",$NachrichtenInputID);
	}
else break;

/****************************************************************/

if ($_IPS['SENDER'] == "Execute")
	{
	echo "Script wurde direkt aufgerufen.\n";
	echo "\n";
	echo "Category App           ID: ".$CategoryIdApp."\n";
	echo "Category Data          ID: ".$CategoryIdData."\n";
	echo "Webfront Administrator ID: ".$categoryId_WebFront."     ".$WFC10_Path."\n";
	echo "Nachrichten Script     ID: ".$NachrichtenScriptID."\n";
	echo "Nachrichten      Input ID: ".$NachrichtenInputID."\n\n";

	$log_Denon->LogMessage("Script wurde direkt aufgerufen");
	$log_Denon->LogNachrichten("Script wurde direkt aufgerufen");
	break;
	}

$oid=$_IPS['VARIABLE'];
$name=IPS_GetName(IPS_GetParent(IPS_GetParent($oid)));

$configuration=Denon_Configuration();

foreach ($configuration as $config)
	{
	if ($config['NAME']==$name)
	   {
	   $instanz=$config['INSTANZ'];
	   }
	//print_r($config);
	}

if ($_IPS['SENDER'] == "WebFront")
	{
	//echo "Script wurde über Webfront aufgerufen.\n";
	$log_Denon->LogMessage("Script wurde über Webfront von Variable ID :".$oid." aufgerufen.");
	$log_Denon->LogNachrichten("Script wurde über Webfront  von Variable ID :".$oid." aufgerufen.");
	}

if (isset($instanz)==false)
	{
	$log_Denon->LogMessage("Instanz wurde nicht gefunden");
	$log_Denon->LogNachrichten("Instanz wurde nicht gefunden");
	break;
	}

if (IPS_GetObjectIDByName($instanz." Client Socket", 0) >0)
	{
	$id = IPS_GetObjectIDByName($instanz." Client Socket", 0);
	$log_Denon->LogMessage("ID Client Socket \"".$instanz." Client Socket\" ist ".$id." ");
	$log_Denon->LogNachrichten("ID Client Socket \"".$instanz." Client Socket\" ist ".$id." ");
	}
else
	{
	//echo "die ID des DENON Client Sockets kann nicht ermittelt werden/n ->		Client Socket angelegt?/n Name richtig geschrieben (DENON Client Socket)?";
	$log_Denon->LogMessage("ID Client Socket \"".$instanz." Client Socket\" wurde nicht gefunden");
	$log_Denon->LogNachrichten("ID Client Socket \"".$instanz." Client Socket\" wurde nicht gefunden");
	}

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

//if ($IPS_SENDER == "WebFront")
//{
	SetValue($IPS_VARIABLE, $IPS_VALUE);
	$VarName = IPS_GetName($IPS_VARIABLE);

	switch ($VarName)
	{
	   ############### Main Zone ################################################
		case "Power":
			if (getValueBoolean($IPS_VARIABLE) == false)
			{
				DENON_Power($id, "STANDBY");
			}
			else
			{
				DENON_Power($id, "ON");
			}
		break;

		case "DigitalInputMode":
         $DigitalInputMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_DigitalInputMode($id, $DigitalInputMode_val);
		break;

		case "InputSource":
         $InputSource_val = GetValueFormatted($IPS_VARIABLE);
			DENON_InputSource($id, $InputSource_val);
		break;

		case "AuswahlFunktion":
         $InputSource_val = GetValueFormatted($IPS_VARIABLE);
		   /* 0, "VOID", 	1, "PC",	2, "XBOX",	3, "TUNER"			*/
			if ($InputSource_val>0)
			   {
				if ($InputSource_val==1)
				   {
				   $InputSource_val=3;
				   }
				elseif($InputSource_val==2)
				   {
				   $InputSource_val=6;
				   }
				elseif($InputSource_val==3)
				   {
				   $InputSource_val=2;
				   }
				DENON_InputSource($id, $InputSource_val);
				}
		break;

		case "InputMode":
         $InputMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_InputMode($id, $InputMode_val);
		break;

		case "RoomSize":
			DENON_RoomSize($id, $IPS_VALUE);
		break;

		case "MainMute":
         $MainMute_val = GetValueFormatted($IPS_VARIABLE);
			DENON_MainMute($id, $MainMute_val);
		break;

		case "ToneCTRL":
         $ToneCTRL_val = GetValueFormatted($IPS_VARIABLE);
			DENON_ToneCTRL($id, $ToneCTRL_val);
		break;

		case "ToneDefeat":
         $ToneDefeat_val = GetValueFormatted($IPS_VARIABLE);
			DENON_ToneDefeat($id, $ToneDefeat_val);
		break;

		case "QuickSelect":
         $QuickSelect_val = GetValueInteger($IPS_VARIABLE);
			DENON_Quickselect($id, $QuickSelect_val);
		break;

		case "VideoSelect":
         $VideoSelect_val = GetValueFormatted($IPS_VARIABLE);
			DENON_VideoSelect($id, $VideoSelect_val);
		break;

		case "Panorama":
         $Panorama_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Panorama($id, $Panorama_val);
		break;

		case "FrontHeight":
         $FrontHeight_val = GetValueFormatted($IPS_VARIABLE);
			DENON_FrontHeight($id, $FrontHeight_val);
		break;

		case "BassLevel":
			DENON_BassLevel($id, $IPS_VALUE);
		break;

		case "LFELevel":
			DENON_LFELevel($id, $IPS_VALUE);
		break;

		case "TrebleLevel":
			DENON_TrebleLevel($id, $IPS_VALUE);
		break;

		case "DynamicEQ":
         $DynamicEQ_val = GetValueFormatted($IPS_VARIABLE);
			DENON_DynamicEQ($id, $DynamicEQ_val);
		break;

		case "DynamicCompressor":
         $DynamicCompressor_val = GetValueFormatted($IPS_VARIABLE);
			DENON_DynamicCompressor($id, $DynamicCompressor_val);
		break;

		case "DynamicVolume":
         DENON_DynamicVolume($id, $IPS_VALUE);
		break;

		case "DynamicRange":
         $DynamicCompressor_val = GetValueFormatted($IPS_VARIABLE);
			DENON_DynamicCompressor($id, $DynamicCompressor_val);
		break;

		case "AudioDelay":
			DENON_AudioDelay($id, $IPS_VALUE);
		break;

		case "AudioRestorer":
			DENON_AudioRestorer($id, $IPS_VALUE);
		break;

		case "MasterVolume":
			DENON_MasterVolumeFix($id, $IPS_VALUE);
		break;

		case "C.Width":
			DENON_CWidth($id, $IPS_VALUE);
		break;

		case "Dimension":
			DENON_Dimension($id, $IPS_VALUE);
		break;

		case "SurroundMode":
         $SurroundMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_SurroundMode($id, $SurroundMode_val);
		break;

		case "SurroundPlayMode":
         $SurroundPlayMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_SurroundPlayMode($id, $SurroundPlayMode_val);
		break;

		case "SurroundBackMode":
         $SurroundBackMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_SurroundBackMode($id, $SurroundBackMode_val);
		break;

		case "Sleep":
			DENON_Sleep($id, $IPS_VALUE);
		break;

		case "CinemaEQ":
         $CinemaEQ_val = GetValueFormatted($IPS_VARIABLE);
			DENON_CinemaEQ($id, $CinemaEQ_val);
		break;

		case "MainZonePower":
			$MainZonePower_val = GetValueFormatted($IPS_VARIABLE);
			DENON_MainZonePower($id, $MainZonePower_val);
		break;

		case "MultiEQMode":
         $MultiEQMode_val = GetValueFormatted($IPS_VARIABLE);
			DENON_MultiEQMode($id, $MultiEQMode_val);
		break;

		case "Preset":
         $Preset_val = GetValueInteger($IPS_VARIABLE);
			DENON_Preset($id, $Preset_val);
		break;

		case "ChannelVolumeFL":
			DENON_ChannelVolumeFL($id, $IPS_VALUE);
		break;

		case "ChannelVolumeFR":
			DENON_ChannelVolumeFR($id, $IPS_VALUE);
		break;

		case "ChannelVolumeC":
			DENON_ChannelVolumeC($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSW":
			DENON_ChannelVolumeSW($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSL":
			DENON_ChannelVolumeSL($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSR":
			DENON_ChannelVolumeSR($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSBL":
			DENON_ChannelVolumeSBL($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSBR":
			DENON_ChannelVolumeSBR($id, $IPS_VALUE);
		break;

		case "ChannelVolumeSB":
			DENON_ChannelVolumeSB($id, $IPS_VALUE);
		break;

		case "ChannelVolumeFHL":
			DENON_ChannelVolumeFHL($id, $IPS_VALUE);
		break;

		case "ChannelVolumeFHR":
			DENON_ChannelVolumeFHR($id, $IPS_VALUE);
		break;

		case "ChannelVolumeFWL":
			DENON_ChannelVolumeFWL($id, $IPS_VALUE);
		break;

      case "ChannelVolumeFWR":
			DENON_ChannelVolumeFWR($id, $IPS_VALUE);
		break;


		#################### Cursorsteuerung #####################################

		case "CursorUp":
			DENON_CursorUp($id);
		break;

		case "CursorDown":
			DENON_CursorDown($id);
		break;

		case "CursorLeft":
			DENON_CursorLeft($id);
		break;

		case "CursorRight":
			DENON_CursorRight($id);
		break;

		case "Enter":
			DENON_Enter($id);
		break;

		case "Return":
			DENON_Return($id);
		break;

		#################### Zone 2 ##############################################
      case "Zone2Power":
         $Zone2Power_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone2Power($id, $Zone2Power_val);
		break;

		case "Zone2Volume":
			DENON_Zone2VolumeFix($id, $IPS_VALUE);
		break;

		case "Zone2Mute":
			$Zone2Mute_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone2Mute($id, $Zone2Mute_val);
		break;

		case "Zone2InputSource":
			$Zone2InputSource_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone2InputSource($id, $Zone2InputSource_val);
		break;

		case "Zone2ChannelSetting":
			if (getValueBoolean($IPS_VARIABLE) == false)
			{
				DENON_Zone2ChannelSetting($id, "ST");
			}
			else
			{
				DENON_Zone2ChannelSetting($id, "MONO");
			}
		break;

		case "Zone2ChannelVolumeFL":
			DENON_Zone2ChannelVolumeFL($id, $IPS_VALUE);
		break;

		case "Zone2ChannelVolumeFR":
			DENON_Zone2ChannelVolumeFL($id, $IPS_VALUE);
		break;


		#################### Zone 3 ##############################################
      case "Zone3Power":
         $Zone3Power_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone3Power($id, $Zone3Power_val);
		break;

		case "Zone3Volume":
			DENON_Zone3VolumeFix($id, $IPS_VALUE);
		break;

		case "Zone3Mute":
			$Zone3Mute_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone3Mute($id, $Zone3Mute_val);
		break;

		case "Zone3InputSource":
			$Zone3InputSource_val = GetValueFormatted($IPS_VARIABLE);
			DENON_Zone3InputSource($id, $Zone3InputSource_val);
		break;

		case "Zone3ChannelSetting":
			if (getValueBoolean($IPS_VARIABLE) == false)
			{
				DENON_Zone3ChannelSetting($id, "ST");
			}
			else
			{
				DENON_Zone3ChannelSetting($id, "MONO");
			}
		break;

		case "Zone3ChannelVolumeFL":
			DENON_Zone3ChannelVolumeFL($id, $IPS_VALUE);
		break;

		case "Zone3ChannelVolumeFR":
			DENON_Zone3ChannelVolumeFL($id, $IPS_VALUE);
		break;

	}
//}
?>
