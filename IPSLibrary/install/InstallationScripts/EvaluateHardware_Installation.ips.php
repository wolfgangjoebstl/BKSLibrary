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

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(400); 
    $startexec=microtime(true);

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

    $ipsOps = new ipsOps();

    $statusDeviceID                 = CreateVariable("StatusDevice", 3, $CategoryIdData,1020,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    $statusEvaluateHardwareID       = CreateVariable("StatusEvaluateHardware", 3, $CategoryIdData,1010,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    $logEvaluateHardwareID          = CreateVariable("LogEvaluateHardware", 3, $CategoryIdData,1010,"~HTMLBox",null,null,"");

    /* check if Administrator and User Webfronts are already available */

    $wfcHandling =  new WfcHandling();
    $wfcHandling->installWebfront();
    $WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();	            // configID für die beiden Webfronts User und Administrator

/*******************************
 *
 * Webfront Konfiguration einlesen
 *
 ********************************/

    $configWFront=$ipsOps->configWebfront($moduleManager,false);     // wenn true mit debug Funktion
    
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	$Mobile_Enabled       = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
    $Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);

	if ($WFC10_Enabled==true)       $WFC10_ConfigId       = $WebfrontConfigID["Administrator"];		
	if ($WFC10User_Enabled==true)   $WFC10User_ConfigId   = $WebfrontConfigID["User"];
	if ($Mobile_Enabled==true)      $Mobile_Path          = $moduleManager->GetConfigValue('Path', 'Mobile');
	if ($Retro_Enabled==true)		$Retro_Path        	  = $moduleManager->GetConfigValue('Path', 'Retro');

	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    /* Webfront in SystemTPA, Anzeige Homematic Errror Status und Log */

    //$wfcHandling =  new WfcHandling($WFC10_ConfigId);                  // gleich für Interop Admin konfigurieren
    $WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();   

    $moduleManagerGUI = new IPSModuleManager('IPSModuleManagerGUI',$repository);
    $configWFrontGUI=$ipsOps->configWebfront($moduleManagerGUI,false);     // wenn true mit debug Funktion
    $tabPaneParent="roottp";                        // Default Wert

    $configWF=array();                                      // für die Verwendung vorbereiten
    if (isset($configWFrontGUI["Administrator"]))
        {
        $tabPaneParent=$configWFrontGUI["Administrator"]["TabPaneItem"];
        echo "EvaluateHardware Module Überblick im Administrator Webfront $tabPaneParent abspeichern.\n";
        //print_r($configWFrontGUI["Administrator"]);   

        /* es gibt kein Module mit passenden ini Dateien, daher etwas improvisieren und fixe Namen nehmen */
        $configWF["Enabled"]=true;
        $configWF["Path"]="Visualization.WebFront.Administrator.EvaluateHardware";
        $configWF["ConfigId"]=$WebfrontConfigID["Administrator"];              
        $configWF["TabPaneParent"]=$tabPaneParent;
        $configWF["TabPaneItem"]="EvaluateHardware"; 
        $configWF["TabPaneOrder"]=1050;                                          
        }
    else echo "EvaluateHardware Module Überblick im Administrator Standard Webfront $tabPaneParent abspeichern.\n";         
    $webfront_links=array();
    $webfront_links["EvaluateHardware"]["Auswertung"]=array(
        $statusEvaluateHardwareID => array(
                "NAME"				=> "Auswertung",
                "ORDER"				=> 10,
                "ADMINISTRATOR" 	=> true,
                "USER"				=> false,
                "MOBILE"			=> false,
                    ),        
        $statusDeviceID=> array(
                "NAME"				=> "StatusOverview",
                "ORDER"				=> 20,
                "ADMINISTRATOR" 	=> true,
                "USER"				=> false,
                "MOBILE"			=> false,
                    ),
    );
    $webfront_links["EvaluateHardware"]["Nachrichten"] = array(
        $logEvaluateHardwareID => array(
                "NAME"				=> "Nachrichten",
                "ORDER"				=> 10,
                "ADMINISTRATOR" 	=> true,
                "USER"				=> false,
                "MOBILE"			=> false,
                    ),
                );	           
    $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern
    $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",false);            //true für Debug
    $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       

    /*-------------*/

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

        $configWF = $configWFront["Administrator"];
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$WFC10_ConfigId." ".$configWF["TabPaneItem"]." ".$configWF["TabPaneParent"]." ".$configWF["TabPaneOrder"]." ".$configWF["TabPaneIcon"]."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, "HouseTPA", $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], "", "HouseRemote");    /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $configWF["TabPaneItem"], "HouseTPA",  20, $configWF["TabPaneName"], $configWF["TabPaneIcon"]);  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */
		
		echo "\nWebportal Datenstruktur installieren in: ".$configWF["Path"]." \n";
        $categoryId_WebFrontAdministrator         = CreateCategoryPath($configWF["Path"]);
		IPS_SetHidden($categoryId_WebFrontAdministrator,true);
		$worldID=CreateCategory("World",  $categoryId_WebFrontAdministrator, 10);
	    EmptyCategory($worldID);

		CreateWFCItemCategory  ($WFC10_ConfigId, 'World', $configWF["TabPaneItem"],   10, 'World', 'Wellness', $worldID   /*BaseId*/, 'true' /*BarBottomVisible*/);

		
		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Evaluierung starten
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	$scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdApp);
	echo "\n";
	echo "Die Scripts sind auf               ".$CategoryIdApp."\n";
	echo "Evaluate Hardware hat die ScriptID ".$scriptIdEvaluateHardware.". Wird jetzt aufgerufen.\n";
	echo "Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
    echo "====================================================================================\n";
	echo IPS_RunScriptWait($scriptIdEvaluateHardware);
	echo "====================================================================================\n";
	echo "Script Evaluate Hardware bereits abgeschlossen. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	
	
	
	
?>