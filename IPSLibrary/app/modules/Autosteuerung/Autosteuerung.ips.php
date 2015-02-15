<?

/***********************************************************************

Automatisches Ansteuern der Heizung, durch Timer, mit Overwrite etc.

zB durch wenn die FS20-STR einen Heizkoerper ansteuert, gleich wieder den Status aendern

funktioniert nur mit elektrischen Heizkoerpern

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
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
$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));
echo "Category App ID:".$CategoryIdApp."\n";
echo "Category Script ID:".$scriptId."\n";



if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */

	}

if ($_IPS['SENDER']=="Variable")
	{
	/* eine Variablenaenderung ist aufgetreten */
	tts_play(1,'Hallo Claudia Wie gehts','',2);
	$remServer=array(
				"BKS-Server"           	=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/',
						);
	foreach ($remServer as $Server)
		{
		$rpc = new JSONRPC($Server);
		}
	$rpc->IPS_RunScript(10004);
	
	switch ($_IPS['VARIABLE'])
		{

		/* Positionswerte geändert */

   case "32688": /* Arbeitszimmer Pos Aenderung Heizung*/
		break;

	case "10884": /* Kellerzimmer Pos Aenderung Heizung*/
		break;

	case "17661": /* Wohnzimmer Pos Aenderung Heizung*/
		break;
		}
	}


if ($_IPS['SENDER']=="TimerEvent")
	{

	}


/*********************************************************************************************/




?>
