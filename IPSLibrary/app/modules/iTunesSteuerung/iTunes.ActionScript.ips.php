<?


/*

Funktionen:



*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\iTunesSteuerung\iTunes.Configuration.inc.php");

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/****************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('iTunesSteuerung',$repository);
	}

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

/****************************************************************
 *
 *  Init
 *
 */

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$scriptIdiTunesSteuerung   = IPS_GetScriptIDByName('iTunes.ActionScript', $CategoryIdApp);

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
	$log_iTunes=new Logging("C:\Scripts\iTunes\Log_iTunes.csv",$NachrichtenInputID);
	}
else break;

/****************************************************************
 *
 *  Konfiguration
 *
 */
	
	$config=iTunes_Configuration();
	
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

	$log_iTunes->LogMessage("Script wurde direkt aufgerufen");
	$log_iTunes->LogNachrichten("Script wurde direkt aufgerufen");
	}

if ($_IPS['SENDER'] == "WebFront")
	{
	//echo "Script wurde über Webfront aufgerufen.\n";
	$oid=$_IPS['VARIABLE'];
	$name=IPS_GetName($oid);
	$category=IPS_GetName(IPS_GetParent($oid));
	$module=IPS_GetName(IPS_GetParent(IPS_GetParent($oid)));
	$log_iTunes->LogMessage("Script wurde über Webfront von Variable ID :".$oid." aufgerufen.");
	$log_iTunes->LogNachrichten("Variable ID :".$oid." ".$name."/".$category."/".$module." aufgerufen.");
	if ( isset($config["iTunes"][$name])==true )
		{
		$configTunes=$config["iTunes"][$name];
		if ( isset($configTunes["EXECUTE"])==true )
			{
			$log_iTunes->LogNachrichten("Config Eintrag EXECUTE ".$configTunes["EXECUTE"]." vorhanden.");		
			
			}
		}
	SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
	
	}
	
	
	
	
?>