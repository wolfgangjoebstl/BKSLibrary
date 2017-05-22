<?

/* Program baut auf einem oder mehreren remote Server eine Variablenstruktur auf in die dann bei jeder Veränderung Werte geschrieben werden
 *
 * es wird die Struktur am remote Server aufgebaut
 *
 * zusaetzlich wird ein evaluate.inc erstellt, damit Variablen auf den Remote Servern schneller adressierbar sind
 *    mit function GuthabensteuerungList(), function AmisStromverbrauchList() und function ROID_List()
 *
 * function ROID_List() beinhaltet die Liste der VIS Server
 *     und die Kategorien der dort angelegten Tabs
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
IPSUtils_Include ("RemoteAccess_class.class.php","IPSLibrary::app::modules::RemoteAccess");

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
	if (isset ($installedModules["Guthabensteuerung"])) { 			echo "  Modul Guthabensteuerung ist installiert.\n"; } else { echo "   Modul Guthabensteuerung ist NICHT installiert.\n"; }
	//if (isset ($installedModules["Gartensteuerung"])) { 	echo "  Modul Gartensteuerung ist installiert.\n"; } else { echo "Modul Gartensteuerung ist NICHT installiert.\n";}
	if (isset ($installedModules["Amis"])) { 				echo "  Modul Amis ist installiert.\n"; } else { echo "   Modul Amis ist NICHT installiert.\n"; }
	if (isset ($installedModules["OperationCenter"])) { 				echo "  Modul OperationCenter ist installiert.\n"; } else { echo "   Modul OperationCenter ist NICHT installiert.\n"; }
	echo "\n";

 /******************************************************
  *
  *  			INSTALLATION
  *
  *************************************************************/

	if (isset ($installedModules["DetectMovement"]))
		{
		IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
		IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
		}

	/************************************************************************************************
	 *
	 * Create Include file
	 *
	 ************************************************************************************************/

	$remote=new RemoteAccess();
	if (isset ($installedModules["Guthabensteuerung"])) 
		{ 
		$remote->add_Guthabensteuerung(); 
		echo "Ende Guthabensteuerung Variablen zum include file hinzufügen : ".(microtime(true)-$startexec)." Sekunden \n";
		}
	if (isset ($installedModules["Amis"]))	
		{ 
		$remote->add_Amis(); 
		echo "Ende AMIS Variablen zum include file hinzufügen : ".(microtime(true)-$startexec)." Sekunden \n";
		}
	if (isset ($installedModules["OperationCenter"]))	
		{ 		
		$remote->add_SysInfo();
		echo "Ende OperationCenter Variablen zum include file hinzufügen : ".(microtime(true)-$startexec)." Sekunden \n";		
		}		
	$status=$remote->server_ping();
	$remote->add_RemoteServer($status);
	echo "Ende Remote Server installieren : ".(microtime(true)-$startexec)." Sekunden \n";
	
	$remote->write_includeFile();
	echo "Ende Evaluierung : ".(microtime(true)-$startexec)." Sekunden \n";

	//$remote->rpc_showProfiles();
	$remote->rpc_createProfiles();

	$remote->write_classresult();



/******************************************************************/




?>