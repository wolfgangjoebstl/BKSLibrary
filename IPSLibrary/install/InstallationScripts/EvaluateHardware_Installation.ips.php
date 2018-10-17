<?

	/**@defgroup EvaluateHardware
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script um Herauszufinden welche Hardware installiert ist
	 *
	 *
	 * @file          EvaluateHardware_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('EvaluateHardware');
	echo "\nEvaluateHardware Modul Version : ".$ergebnis;

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
	
	echo "\n";
	echo "Category OIDs for data : ".$CategoryIdData." for App : ".$CategoryIdApp."\n";	
	
	echo "\n";
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
		$config=json_decode(IPS_GetConfiguration($instanz));
		echo "  Remote Access Webfront Password set : (".$config->Password.")\n";
		echo "  Mobile Webfront aktiviert : ".$config->MobileID."\n";		
		echo "  Retro Webfront aktiviert : ".$config->RetroID."\n";		
		if (false)
			{
			$config=json_decode(IPS_GetConfiguration($instanz));
			$configItems = json_decode(json_decode(IPS_GetConfiguration($instanz))->Items);
			print_r($configItems);	
			}	
		}	

	/* check nach weiteren Webfront Konfiguratoren */

	echo "Security and Configuration check.\n";
	foreach ($WebfrontConfigID as $Key=>$Item)
		{
		$config=json_decode(IPS_GetConfiguration($Item));
		switch ($Key)
			{
			case "User":	
				if ($config->MobileID < 0) 
					{
					echo "  ".$Key.": Mobile Access for User not set (".$config->MobileID.").   --> setzen\n";
					}
			case "Administrator":
				if ($config->Password == "") 
					{
					echo "  ".$Key.": Remote Access Webfront Password not set.   --> setzen\n";
					}
				else	
					{
					echo "  OK ".$Key.": Remote Access Webfront Password set : (".$config->Password.")\n";
					}					
				break;
			default:
				echo "    Zusaetzlichen Webfront Configurator gefunden.  --> loeschen\n";
			}
		}	

/*******************************
 *
 * Webfront Konfiguration einlesen
 *
 ********************************/
			
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled==true)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];		
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10');
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10');
		$WFC10_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10');
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10');
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10');
		$WFC10_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10');
		$WFC10_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10');
		$WFC10_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10');
		$WFC10_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10');
		echo "WF10 Administrator\n";
		echo "  Path          : ".$WFC10_Path."\n";
		echo "  ConfigID      : ".$WFC10_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10_ConfigId)).".".IPS_GetName($WFC10_ConfigId).")\n";
		echo "  TabPaneItem   : ".$WFC10_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10_TabItem."\n";
		echo "  TabName       : ".$WFC10_TabName."\n";
		echo "  TabIcon       : ".$WFC10_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10_TabOrder."\n";
		}

	echo "\n";
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];		
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10User');
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10User');
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10User');
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10User');
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10User');
		$WFC10User_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10User');
		$WFC10User_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10User');
		$WFC10User_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10User');
		$WFC10User_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10User');
		echo "WF10 User \n";
		echo "  Path          : ".$WFC10User_Path."\n";
		echo "  ConfigID      : ".$WFC10User_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10User_ConfigId)).".".IPS_GetName($WFC10User_ConfigId).")\n";
		echo "  TabPaneItem   : ".$WFC10User_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10User_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10User_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10User_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10User_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10User_TabItem."\n";
		echo "  TabName       : ".$WFC10User_TabName."\n";
		echo "  TabIcon       : ".$WFC10User_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10User_TabOrder."\n";
		}

	echo "\n";      
	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
	if ($Mobile_Enabled==true)
	   	{
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile \n";
		echo "  Path          : ".$Mobile_Path."\n";
		}
		
	$Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);
	if ($Retro_Enabled==true)
	   	{
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		echo "  Path          : ".$Retro_Path."\n";		
		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben */

		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "\nWebportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in: ". $categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."\n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		//DeleteWFCItems($WFC10_ConfigId, "root");
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$WFC10_ConfigId." ".$WFC10_TabPaneItem." ".$WFC10_TabPaneParent." ".$WFC10_TabPaneOrder." ".$WFC10_TabPaneIcon."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, "HouseTPA", $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, "", "HouseRemote");    /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, "HouseTPA",  20, $WFC10_TabPaneName, $WFC10_TabPaneIcon);  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */
		
		echo "\nWebportal Datenstruktur installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
		IPS_SetHidden($categoryId_WebFrontAdministrator,true);
		$worldID=CreateCategory("World",  $categoryId_WebFrontAdministrator, 10);


		CreateWFCItemCategory  ($WFC10_ConfigId, 'World', $WFC10_TabPaneItem,   10, 'World', 'Wellness', $worldID   /*BaseId*/, 'true' /*BarBottomVisible*/);

		
		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Evaluierung starten
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	$scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdApp);
	echo "\n";
	echo "Die Scripts sind auf               ".$CategoryIdApp."\n";
	echo "Evaluate Hardware hat die ScriptID ".$scriptIdEvaluateHardware." \n";
	IPS_RunScript($scriptIdEvaluateHardware);
	echo "Script Evaluate Hardware gestartet wird parallel zu diesem Script ausgeführt.\n";
	
	
	
	
?>