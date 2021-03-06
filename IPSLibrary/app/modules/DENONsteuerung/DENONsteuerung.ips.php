<?

 //Fügen Sie hier Ihren Skriptquellcode ein

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("DENONsteuerung_Configuration.inc.php","IPSLibrary::config::modules::DENONsteuerung");

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/******************************************************

				INIT

*************************************************************/

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


$installedModules = $moduleManager->GetInstalledModules();
echo "Folgende Module werden von DenonSteuerung bearbeitet:\n";
if (isset ($installedModules["IPSLight"])) { 			echo "  Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; }
if (isset ($installedModules["IPSPowerControl"])) { 	echo "  Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n";}
if (isset ($installedModules["IPSCam"])) { 				echo "  Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
if (isset ($installedModules["RemoteAccess"])) { 		echo "  Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; }
if (isset ($installedModules["LedAnsteuerung"])) { 	echo "  Modul LedAnsteuerung ist installiert.\n"; } else { echo "Modul LedAnsteuerung ist NICHT installiert.\n";}
if (isset ($installedModules["DENONsteuerung"])) { 	echo "  Modul DENONsteuerung ist installiert.\n"; } else { echo "Modul DENONsteuerung ist NICHT installiert.\n";}
if (isset ($installedModules["NetPlayer"])){ 			echo "  Modul NetPlayer ist installiert.\n"; } else { echo "Modul NetPlayer ist NICHT installiert.\n";}
echo "\n";




$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

$Audio_Enabled        = $moduleManager->GetConfigValue('Enabled', 'AUDIO');
$Audio_Path        	 = $moduleManager->GetConfigValue('Path', 'AUDIO');

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
$categoryId_WebFrontAudio         = CreateCategoryPath($Audio_Path);
$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
$categoryId_WebFrontMobile         = CreateCategoryPath($Mobile_Path);
$categoryId_WebFrontRetro         = CreateCategoryPath($Retro_Path);

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

/* include DENON.Functions
  $id des DENON Client sockets muss nun selbst berechnet werden, war vorher automatisch
*/
if (IPS_GetObjectIDByName("DENON.VariablenManager", $CategoryIdApp) >0)
	{
	IPSUtils_Include ("DENON.VariablenManager.ips.php", "IPSLibrary::app::modules::DENONsteuerung");
	//include "DENON.VariablenManager.ips.php";
	}
else
	{
	echo "Script DENON.VariablenManager kann nicht gefunden werden!";
	}



/****************************************************************/

if ($_IPS['SENDER'] == "Execute")
	{

	$configuration=Denon_Configuration();
	$display_variables=Denon_WebfrontConfig();

	echo "Script wurde direkt aufgerufen.\n";
	echo "\n";
	echo "Category App           ID: ".$CategoryIdApp."\n";
	echo "Category Data          ID: ".$CategoryIdData."\n\n";
	if ($WFC10_Enabled==true)
	   {
		echo "Webfront Administrator ID: ".$categoryId_WebFront."     ".$WFC10_Path."\n";
		foreach ($configuration as $nameTag => $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFront);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
			echo "  ".$nameTag."\n";
			echo "    Mainzone Data        ID: ".$VAR_Parent_ID. "       Denongeraet: ".$id."\n";
			echo "    Mainzone Link        ID: ".$LINK_Parent_ID."       Denongeraet: ".$id."\n";
			$display=$display_variables[$nameTag][$WFC10_Path];
			foreach ($display as $variable)
			   {
		   	echo "         Link für Variable ".$variable."\n";
		   	}
			}
		}
	echo "\n";
	if ($Audio_Enabled==true)
	   {
		if (isset ($installedModules["NetPlayer"]))
			{
			include_once IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\NetPlayer\NetPlayer_Constants.inc.php";
			$moduleManager_NP  = new IPSModuleManager('NetPlayer');     /*   <--- change here */
			$CategoryIdData_NP = $moduleManager_NP->GetModuleCategoryID('data');


			echo "Auch die Links für den Netplayer in Audio einbauen.\n";
			if (@IPS_GetCategoryIDByName('NetPlayer',$categoryId_WebFrontAudio)===false)
			   {
				$NetPlayer_WFE_ID = IPS_CreateCategory();
				IPS_SetName($NetPlayer_WFE_ID, 'NetPlayer');
				IPS_SetInfo($NetPlayer_WFE_ID, "this Object was created by Script DENON.Installer.ips.php");
				IPS_SetParent($NetPlayer_WFE_ID, $categoryId_WebFrontAudio);
				echo "Kategorie DENON Webfront #$NetPlayer_WFE_ID angelegt\n";
			   }
			else
			   {
			   $NetPlayer_WFE_ID=IPS_GetCategoryIDByName('NetPlayer',$categoryId_WebFrontAudio);
			   }
			$NP_powerID=NP_ID_POWER;
			CreateLinkByDestination('Power', $NP_powerID,    $NetPlayer_WFE_ID,  10);
			$NP_sourceID=NP_ID_SOURCE;
			CreateLinkByDestination('Source', $NP_sourceID,    $NetPlayer_WFE_ID,  20);
			$NP_controlID=NP_ID_CONTROL;
			CreateLinkByDestination('Control', $NP_controlID,    $NetPlayer_WFE_ID,  30);
			$NP_albumID=NP_ID_CDALBUM;
			CreateLinkByDestination('Album', $NP_albumID,    $NetPlayer_WFE_ID,  40);
			$NP_radiolistID=NP_ID_RADIOLIST;
			CreateLinkByDestination('Radio Liste', $NP_radiolistID,    $NetPlayer_WFE_ID,  100);
	  		$NP_radionavID=NP_ID_RADIONAV;
			CreateLinkByDestination('Radio Navigation', $NP_radionavID,    $NetPlayer_WFE_ID,  60);
			}
		if (function_exists('Denon_RemoteNetplayer'))
		   {
			if (@IPS_GetCategoryIDByName('RemoteNetPlayer',$categoryId_WebFrontAudio)===false)
			   {
				echo "Auch die Links für den RemoteNetplayer in Audio einbauen.\n";
				$NetPlayer_rWFE_ID = IPS_CreateCategory();
				IPS_SetName($NetPlayer_rWFE_ID, 'RemoteNetPlayer');
				IPS_SetInfo($NetPlayer_rWFE_ID, "this Object was created by Script DENON.Installer.ips.php");
				IPS_SetParent($NetPlayer_rWFE_ID, $categoryId_WebFrontAudio);
				echo "Kategorie DENON Webfront #$NetPlayer_rWFE_ID angelegt\n";
				}
			else
			   {
			   $NetPlayer_rWFE_ID=IPS_GetCategoryIDByName('RemoteNetPlayer',$categoryId_WebFrontAudio);
			   }
			if (@IPS_GetCategoryIDByName('RemoteNetPlayer',$CategoryIdData)===false)
			   {
				echo "Auch die Datenobjekte für den RemoteNetplayer in data Denon einbauen.\n";
				$NetPlayer_rData_ID = IPS_CreateCategory();
				IPS_SetName($NetPlayer_rData_ID, 'RemoteNetPlayer');
				IPS_SetInfo($NetPlayer_rData_ID, "this Object was created by Script DENON.Installer.ips.php");
				IPS_SetParent($NetPlayer_rData_ID, $CategoryIdData);
				echo "Kategorie DENON Webfront #$NetPlayer_rData_ID angelegt\n";
				}
			else
			   {
			   $NetPlayer_rData_ID=IPS_GetCategoryIDByName('RemoteNetPlayer',$CategoryIdData);
			   }
			$actionScriptId = IPS_GetScriptIDByName("DENON.ActionScript", $CategoryIdApp);

			$powerId               = CreateVariable("Power",           0 /*Boolean*/,  $NetPlayer_rData_ID, 100 , '~Switch', $actionScriptId, 0);
			$sourceId              = CreateVariable("Source",          1 /*Integer*/,  $NetPlayer_rData_ID, 110 , 'NetPlayer_Source', $actionScriptId, 0 /*CD*/);
			$controlId             = CreateVariable("Control",         1 /*Integer*/,  $NetPlayer_rData_ID, 120 , 'NetPlayer_Control', $actionScriptId, 2 /*Stop*/);
			$albumId               = CreateVariable("Album",           3 /*String*/,   $NetPlayer_rData_ID, 130, '~String');
			$radioNavId            = CreateVariable("RadioNav",        1 /*Integer*/,  $NetPlayer_rData_ID, 200 , 'NetPlayer_RadioNav', $actionScriptId, -1);
			$radioListId           = CreateVariable("RadioList",       1 /*Integer*/,  $NetPlayer_rData_ID, 210 , 'NetPlayer_RadioList', $actionScriptId,-1);
			CreateLinkByDestination('Power', $powerId,    $NetPlayer_rWFE_ID,  10);
			CreateLinkByDestination('Source', $sourceId,    $NetPlayer_rWFE_ID,  20);
			CreateLinkByDestination('Control', $controlId,    $NetPlayer_rWFE_ID,  30);
			CreateLinkByDestination('Album', $albumId,    $NetPlayer_rWFE_ID,  40);
			CreateLinkByDestination('Radio Liste', $radioListId,    $NetPlayer_rWFE_ID,  100);
			CreateLinkByDestination('Radio Navigation', $radioNavId,    $NetPlayer_rWFE_ID,  60);
			}

		echo "Webfront Administrator Audio ID: ".$categoryId_WebFrontAudio."     ".$Audio_Path."\n";
		print_r($display_variables);
		foreach ($configuration as $nameTag => $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFrontAudio);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
			echo "  ".$nameTag."\n";
			echo "    Mainzone Data        ID: ".$VAR_Parent_ID. "       Denongeraet: ".$id."\n";
			echo "    Mainzone Link        ID: ".$LINK_Parent_ID."       Denongeraet: ".$id."\n";
			$display=$display_variables[$nameTag][$Audio_Path];
			foreach ($display as $variable)
			   {
		   	echo "         Link für Variable ".$variable."\n";
		   	}
			}
		}

	if ($WFC10User_Enabled==true)
	   {
		echo "Webfront User          ID: ".$categoryId_WebFrontUser."     ".$WFC10User_Path."\n";
		foreach ($configuration as $nameTag => $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFrontUser);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
  			echo "  ".$nameTag."\n";
			echo "      Mainzone Data        ID: ".$VAR_Parent_ID. "         Denongeraet: ".$id."\n";
			echo "      Mainzone Link        ID: ".$LINK_Parent_ID."         Denongeraet: ".$id."\n";
			$display=$display_variables[$nameTag][$WFC10User_Path];
			foreach ($display as $variable)
			   {
			   echo "        Link für Variable ".$variable."\n";
		   	}
			}
		}
	if ($Mobile_Enabled==true)
		{
		echo "Webfront Mobile        ID: ".$categoryId_WebFrontMobile."     ".$Mobile_Path."\n";
		foreach ($configuration as $nameTag => $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFrontMobile);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
  			echo "  ".$nameTag."\n";
			echo "    Mainzone Data        ID: ".$VAR_Parent_ID. "       Denongeraet: ".$id."\n";
			echo "    Mainzone Link        ID: ".$LINK_Parent_ID."       Denongeraet: ".$id."\n";
			}
		}
	if ($Retro_Enabled==true)
		{
		echo "Webfront Retro         ID: ".$categoryId_WebFrontRetro."     ".$Retro_Path."\n";
		foreach ($configuration as $nameTag => $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFrontRetro);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
  			echo "  ".$nameTag."\n";
			echo "    Mainzone Data        ID: ".$VAR_Parent_ID. "       Denongeraet: ".$id."\n";
			echo "    Mainzone Link        ID: ".$LINK_Parent_ID."       Denongeraet: ".$id."\n";
			}
		}
	echo "Nachrichten Script     ID: ".$NachrichtenScriptID."\n";
	echo "Nachrichten      Input ID: ".$NachrichtenInputID."\n\n";

	$log_Denon->LogMessage("Script wurde direkt aufgerufen");
	$log_Denon->LogNachrichten("Script wurde direkt aufgerufen");

	}





?>