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
	include "DENON.VariablenManager.ips.php";
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
	echo "Category Data          ID: ".$CategoryIdData."\n";
	if ($WFC10_Enabled==true)
	   {
		echo "Webfront Administrator ID: ".$categoryId_WebFront."     ".$WFC10_Path."\n";
		foreach ($configuration as $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFront);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
			echo "  Mainzone Data        ID: ".$VAR_Parent_ID." Denongeraet: ".$id."\n";
			echo "  Mainzone Link        ID: ".$LINK_Parent_ID." Denongeraet: ".$id."\n";
			}
		print_r($display_variables["Administrator"]);
		}
	if ($Audio_Enabled==true)
	   {
		echo "Webfront Administrator Audio ID: ".$categoryId_WebFrontAudio."     ".$Audio_Path."\n";
		foreach ($configuration as $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFrontAudio);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
			echo "  Mainzone Data        ID: ".$VAR_Parent_ID." Denongeraet: ".$id."\n";
			echo "  Mainzone Link        ID: ".$LINK_Parent_ID." Denongeraet: ".$id."\n";
			}
		print_r($display_variables["Audio"]);
		}

	if ($WFC10User_Enabled==true)
	   {
		echo "Webfront User          ID: ".$categoryId_WebFrontUser."     ".$WFC10User_Path."\n";
		foreach ($configuration as $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFrontUser);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
			echo "  Mainzone Data        ID: ".$VAR_Parent_ID." Denongeraet: ".$id."\n";
			echo "  Mainzone Link        ID: ".$LINK_Parent_ID." Denongeraet: ".$id."\n";
			}
		$display=$display_variables["User"];
		foreach ($display as $variable)
		   {
		   echo "    Link für Variable ".$variable."\n";
		   }
		}
	if ($Mobile_Enabled==true)
		{
		echo "Webfront Mobile        ID: ".$categoryId_WebFrontMobile."     ".$Mobile_Path."\n";
		foreach ($configuration as $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFrontMobile);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
			echo "  Mainzone Data        ID: ".$VAR_Parent_ID." Denongeraet: ".$id."\n";
			echo "  Mainzone Link        ID: ".$LINK_Parent_ID." Denongeraet: ".$id."\n";
			}
		}
	if ($Retro_Enabled==true)
		{
		echo "Webfront Retro         ID: ".$categoryId_WebFrontRetro."     ".$Retro_Path."\n";
		foreach ($configuration as $config)
			{
	   	$id=$config['NAME'];
		   $VAR_Parent_ID = IPS_GetCategoryIDByName($id, $CategoryIdData);
   		$VAR_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $VAR_Parent_ID);
		   $LINK_Parent_ID = IPS_GetCategoryIDByName($id, $categoryId_WebFrontRetro);
		   $LINK_Parent_ID = IPS_GetInstanceIDByName("Main Zone", $LINK_Parent_ID);
			echo "  Mainzone Data        ID: ".$VAR_Parent_ID." Denongeraet: ".$id."\n";
			echo "  Mainzone Link        ID: ".$LINK_Parent_ID." Denongeraet: ".$id."\n";
			}
		}
	echo "Nachrichten Script     ID: ".$NachrichtenScriptID."\n";
	echo "Nachrichten      Input ID: ".$NachrichtenInputID."\n\n";

	$log_Denon->LogMessage("Script wurde direkt aufgerufen");
	$log_Denon->LogNachrichten("Script wurde direkt aufgerufen");

	$WFC10_PathDevice=$WFC10_Path.".Audiosteuerung";
	$categoryId_WebFrontDevice         = CreateCategoryPath($WFC10_PathDevice);

	foreach ($configuration as $config)
		{
   	$id=$config['NAME'];
		$item="AuswahlFunktion";
		$vtype = 1;
		$value=1;
		echo "Shortcut anlegen für ".$id.".".$item." in ".$Audio_Path." \n";
		DenonSetValue($item, $value, $vtype, $id,$Audio_Path);
		}




	}





?>
