<?

/***********************************************************************

Sprachsteuerung

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
}

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
echo "Category App ID:".$CategoryIdApp."\n";
echo "Category Script ID:".$scriptId."\n";

$scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);



/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */

	$url="http://whatismyipaddress.com/";  //gesperrt da html 1.1
	//$url="http://www.whatismyip.com/";  //gesperrt
	//$url="http://whatismyip.org/"; // java script
	//$url="http://www.myipaddress.com/show-my-ip-address/"; // check auf computerzugriffe
	//$url="http://www.ip-adress.com/"; //gesperrt

	/* ab und zu gibt es auch bei der whatismyipaddress url timeouts, 30sek maximum timeout */
	/* d.h. Timeout: Server wird nicht erreicht
			Zustand false: kein Internet
	*/


	//curl  ifconfig.co
	
	/* gets the data from a URL */

	//$result=file_get_contents($url);
	$result=get_data($url);

	//echo $result;

	/* letzte Alternative ist die Webcam selbst */

	if ($result==false)
		{
		echo "Whatismyipaddress reagiert nicht. Ip Adresse anders ermitteln.\n";


		}
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{

	}


/*********************************************************************************************/

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

/*********************************************************************************************/




?>
