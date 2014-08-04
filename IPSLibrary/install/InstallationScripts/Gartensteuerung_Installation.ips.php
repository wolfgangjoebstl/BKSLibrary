<?

 //Fügen Sie hier Ihren Skriptquellcode ein

	$repository = 'https://10.0.1.6/user/repository/';

	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
	}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Gartensteuerung');
	echo "\nGartensteuerung Version : ".$ergebnis;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	echo "\nWebportal installieren: ".$WFC10_Enabled;

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
	// Add Scripts
	$scriptIdGartensteuerung   = IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);
	IPS_RunScript($scriptIdGartensteuerung);
	$scriptIdNachrichtenverlauf   = IPS_GetScriptIDByName('Nachrichtenverlauf-Garten', $CategoryIdApp);
	IPS_RunScript($scriptIdNachrichtenverlauf);
	
	echo "\nData Kategorie : ".$CategoryIdData;
	echo "\nApp  Kategorie : ".$CategoryIdApp;
	echo "\nScriptID #1    : ".$scriptIdGartensteuerung;
	echo "\nScriptID #2    : ".$scriptIdNachrichtenverlauf;


?>
