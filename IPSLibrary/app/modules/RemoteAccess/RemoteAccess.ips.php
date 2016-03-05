<?

/* Program baut auf einem remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 * es wird die Struktur am remote Server aufgebaut
 *
 * zusaetzlich wird ein evaluate.inc erstellt
 *    mit function GuthabensteuerungList(), function AmisStromverbrauchList() und function ROID_List()
 *
 * function ROID_List() beinhaltet die Liste der VIS Server
 *     und die Kategorien der dort angelegten Tabs
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

// max. Scriptlaufzeit definieren
ini_set('max_execution_time', 500);
$startexec=microtime(true);

/********************************************************************************
 *
 *    EVALUATION
 *
 * welche Module sind installiert
 *
 ************************************************************************************/	

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager))
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('RemoteAccess',$repository);
		}

	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n\n";

	echo "Folgende Module werden von RemoteAccess bearbeitet:\n";
	if (isset ($installedModules["Guthabensteuerung"])) { 			echo "  Modul Guthabensteuerung ist installiert.\n"; } else { echo "Modul Guthabensteuerung ist NICHT installiert.\n"; }
	//if (isset ($installedModules["Gartensteuerung"])) { 	echo "  Modul Gartensteuerung ist installiert.\n"; } else { echo "Modul Gartensteuerung ist NICHT installiert.\n";}
	if (isset ($installedModules["Amis"])) { 				echo "  Modul Amis ist installiert.\n"; } else { echo "Modul Amis ist NICHT installiert.\n"; }
	echo "\n";

	$remote=new RemoteAccess();
	if (isset ($installedModules["Guthabensteuerung"])) { $remote->add_Guthabensteuerung(); }
	if (isset ($installedModules["Amis"]))	{ $remote->add_Amis(); }
	echo "Ende Variablen zum include file honzufügen : ".(microtime(true)-$startexec)." Sekunden \n";
	$remote->add_RemoteServer();
	echo "Ende Remote Server installieren : ".(microtime(true)-$startexec)." Sekunden \n";
	$remote->write_includeFile();
	echo "Ende Evaluierung : ".(microtime(true)-$startexec)." Sekunden \n";

	//$remote->rpc_showProfiles();
	$remote->rpc_createProfiles();

	$remote->write_classresult();



/******************************************************************/




?>
