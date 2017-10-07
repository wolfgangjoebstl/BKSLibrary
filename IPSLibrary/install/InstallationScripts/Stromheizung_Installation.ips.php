<?

	/**@defgroup Stromheizung
	 *
	 * Script um elektrische Heizung nachzusteuern
	 *
	 *
	 * @file          Stromheizung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Stromheizung',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Stromheizung');
	echo "\nStromheizung Version : ".$ergebnis;

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');	
	
	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");

	IPSUtils_Include ("IPSComponentHeatControl_FS20.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl");
	
	IPSUtils_Include ('StromheizungLib.class.php', 'IPSLibrary::app::modules::Stromheizung');
	IPSUtils_Include ('Stromheizung_Configuration.inc.php', 'IPSLibrary::config::modules::Stromheizung');
	
	if (false)
		{  /* bereits bei Custom Components implementiert */
		
	echo "FHT Heizungssteuerung Geräte mit Positionswerten werden registriert.\n";

	$StromheizungHandler = new StromheizungHandler();

	$FHT = FHTList();
	$keyword="PositionVar";

	foreach ($FHT as $Key)
		{
		/* alle Positionswerte der Heizungssteuerungen ausgeben */
		if (isset($Key["COID"][$keyword])==true)
			{
			$oid=(integer)$Key["COID"][$keyword]["OID"];
			$variabletyp=IPS_GetVariable($oid);
			if ($variabletyp["VariableProfile"]!="")
				{
				echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
				}
			else
				{
				echo str_pad($Key["Name"],30)." = ".GetValue($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
				}
				
			$StromheizungHandler->RegisterEvent($oid,"Heizung",'','');	

			if (isset ($installedModules["RemoteAccess"]))
				{
				//echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
				}
			else
				{
				/* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
				echo "Remote Access nicht installiert, Variablen selbst registrieren.\n";
				$messageHandler = new IPSMessageHandler();
				$messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
				$messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

				/* wenn keine Parameter nach IPSComponentSensor_Temperatur angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
				$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All,1,2,3');
				}

			}  /* Ende isset Heizungssteuerung */
		} /* Ende foreach */

		} /* ende iffalse */


?>