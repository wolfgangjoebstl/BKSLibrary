<?

	/**@defgroup 
	 * @ingroup 
	 * @{
	 *
	 * Script zur 
	 *
	 *
	 * @file          
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.08.2014<br/>
	 **/


	/******************** Defaultprogrammteil ********************/
	 
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Amis');       /*   <--- change here */
	echo "\nAmis Version : ".$ergebnis;    										/*   <--- change here */
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	echo "\nWebuser activated : ";
	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	if ($WFC10_Enabled)
		{
		$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
		echo "Admin ";
		}


	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	if ($WFC10User_Enabled)
		{
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		echo "User ";
		}

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled)
		{
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile ";
		}

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled)
		{
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		}

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');


	/******************* Variable Definition **********************/
	
	$ReadMeterID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
	
	/******************* Timer Definition *******************************/
	
	$scriptIdMomAbfrage   = IPS_GetScriptIDByName('MomentanwerteAbfragen', $CategoryIdApp);
	IPS_SetScriptTimer($scriptIdMomAbfrage, 15*60);  /* alle 15 Minuten */


	
?>
