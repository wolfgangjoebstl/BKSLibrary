<?

/* eingefuegt von den timer Events beim Schalten von Stromheizung Switches */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) 
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();
if ( isset($installedModules["Sprachsteuerung"]) === true )
	{
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Sprachsteuerung\Sprachsteuerung_Library.class.php");
    IPSUtils_Include ("Sprachsteuerung_Library.class.php","IPSLibrary::app::modules::Sprachsteuerung");
	}

if ( isset($installedModules["IPSLight"]) === true )
	{
    //include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
    IPSUtils_Include ("IPSLight.inc.php","IPSLibrary::app::modules::IPSLight");
	$lightManager = new IPSLight_Manager();
    $baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
	}    

if ( isset($installedModules["Stromheizung"]) === true )
	{
    //include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
    IPSUtils_Include ("IPSHeat.inc.php","IPSLibrary::app::modules::IPSHeat");        
    $heatManager = new IPSHeat_Manager();
    $baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');
    }

$switchCategoryId 	= IPS_GetObjectIDByIdent('Switches', $baseId);
$groupCategoryId   	= IPS_GetObjectIDByIdent('Groups', $baseId);
$prgCategoryId   		= IPS_GetObjectIDByIdent('Programs', $baseId);	

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$object_data= new ipsobject($CategoryIdData);
$object_app= new ipsobject($CategoryIdApp);

$NachrichtenID = $object_data->osearch("Nachricht");
$NachrichtenScriptID  = $object_app->osearch("Nachricht");

if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($NachrichtenID);
	$NachrichtenInputID=$object3->osearch("Input");
	$log_Autosteuerung=new Logging("C:\Scripts\Log_Autosteuerung.csv",$NachrichtenInputID);
	}

?>