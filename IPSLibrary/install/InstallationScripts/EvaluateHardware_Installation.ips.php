<?php

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

    /* EvaluateHardware macht die Evaluierung aller Geräte und ihrer Möglichkeiten
     *
     * Routine geht davon aus dass OperationCenter und DetectMovement installiert sind
     * es werden fünf verschiedene Tabellen angelegt:
     *      StatusDevice            StatusOverview.EvaluateHardware.SystemTP
     *      StatusEvaluateHardware  Auswertung.EvaluateHardware.SystemTP
     *      LogEvaluateHardware     Nachrichten.EvaluateHardware.SystemTP
     *
     *      ValuesTable             Werte.HardwareStatus                    DeviceManager->showHardwareStatus  nette Tabelle per Typ, Update onDemand ImproveDeviceDetection
     *      MessageTable            MessageList.SystemTP                    $testMovement->getComponentEventListTable, Update onDemand ImproveDeviceDetection
     *      TableEvents             MessageTabellen.SystemTP                $html=$detectMovement->writeEventlistTable($detectMovement->eventlist), Kategorie data.DetectMovement
     *
     * Erweiterung um einfache Visualisierungen im Webfront zur Analyse, Darstellung im DoctorBag und Schraubenschlüssel
     * im Schraubenschlüssel- SystemTP: EvaluateHardware und MessageList
     *      Subtab mit EvaluateHardware
     *          darin drei Tabs, Auswertung/StatusOverview und Nachrichten        siehe oben
     *          und einen UpdateButton
     *
     *      Subtab MessageList
     *          darin html Box Tabelle und einfache Sortieranforderungen per Variable
     *
     * im House Icon: EvalTopologyTPA -> HouseTPA -> roottp 
     *      Subtab mit EvalTopology und  Icon Wellness (Palme)
     *      das ist die Kategorie Topologie ohne Instanzen wie in Tiles
     *
     */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");
    IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
    IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');    
    IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(400);                 // max. Scriptlaufzeit definieren
    $startexec=microtime(true);
    
    if ($_IPS['SENDER']=="Execute") 
        {
        echo "Script Execute, Darstellung automatisch mit Debug aktiviert. Kein Aufruf des EvaluateHardware Scripts.\n";
        $debug=true;
        }
    else $debug=false;

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    $moduleManager    = new ModuleManagerIPS7('EvaluateHardware',$repository);

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

	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdImproveDeviceDetection   = IPS_GetScriptIDByName('ImproveDeviceDetection', $CategoryIdApp);

    $ipsOps = new ipsOps();
    $webOps = new webOps();

    $topologyLibrary = new TopologyLibraryManagement();                     // in EvaluateHardware Library, neue Form des Topology Managements

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
    $valuesDeviceID                 = CreateVariable("ValuesTable", 3, $CategoryIdData,1020,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    $statusDeviceID                 = CreateVariable("StatusDevice", 3, $CategoryIdData,1020,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    $statusEvaluateHardwareID       = CreateVariable("StatusEvaluateHardware", 3, $CategoryIdData,1010,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    $logEvaluateHardwareID          = CreateVariable("LogEvaluateHardware", 3, $CategoryIdData,1010,"~HTMLBox",null,null,"");

    $pname="UpdateTables";                                         // keine Standardfunktion, da Inhalte Variable
    $nameID=["Update"];
    $webOps->createActionProfileByName($pname,$nameID,0);  // erst das Profil, dann die Variable, 0 ohne Selektor
    $actionUpdateID          = CreateVariableByName($CategoryIdData,"Update", 1,$pname,"",1000,$scriptIdImproveDeviceDetection);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)

	echo "\n";
	echo "Category OIDs for data : ".$CategoryIdData." for App : ".$CategoryIdApp."\n";	
    echo "DetectDeviceMessages Tabelle erstellen.\n";
    $categoryId_DetectDevice        = CreateCategory('DetectDevice',        $CategoryIdData, 20);
    $pname="DetectDeviceMessages";                                         // keine Standardfunktion, da Inhalte Variable
    $nameID=["Module","LastRun","Sort1","Sort2", "Sort3"];
    $webOps->createActionProfileByName($pname,$nameID,0);  // erst das Profil, dann die Variable
    $actionSortMessageTableID          = CreateVariableByName($categoryId_DetectDevice,"SortTableBy", 1,$pname,"",1010,$scriptIdImproveDeviceDetection);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
    $messageTableID          = CreateVariable("MessageTable", 3, $categoryId_DetectDevice,1010,"~HTMLBox",null,null,"");

    echo "DetectTopologies Tabelle erstellen.\n";
    $categoryId_DetectTopologies        = CreateCategory('DetectTopologies',        $CategoryIdData, 30);
    $pname="DetectTopologies";                                         // keine Standardfunktion, da Inhalte Variable
    $nameID=["View1","View2", "View3"];
    $webOps->createActionProfileByName($pname,$nameID,0);  // erst das Profil, dann die Variable
    $actionViewMessageTableID       = CreateVariableByName($categoryId_DetectTopologies,"ViewTableOn", 1,$pname,"",1020,$scriptIdImproveDeviceDetection);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
    $topologyTableID                = CreateVariableByName($categoryId_DetectTopologies,"TopologyViewTable", 3,"~HTMLBox", "", 1010);
    $topologyControlTableID         = CreateVariableByName($categoryId_DetectTopologies,"TopologyControlTable", 3, "~HTMLBox","", 1030);
    $topologyConfigTableID          = CreateVariableByName($categoryId_DetectTopologies,"TopologyConfigTable", 3, "","", 1100);

    echo "Webbrowser Cookies and Configuration Tabelle erstellen.\n";
    $categoryId_BrowserCookies        = CreateCategory('WebfrontCookies',        $CategoryIdData, 40);
    $pname="WebfrontCookies";                                         // keine Standardfunktion, da Inhalte Variable
    $nameID=["View1","View2", "View3"];
    $webOps->createActionProfileByName($pname,$nameID,0);  // erst das Profil, dann die Variable
    $actionViewWebbrowserTableID      = CreateVariableByName($categoryId_BrowserCookies,"ViewBrowserOn", 1,$pname,"",1020,$scriptIdImproveDeviceDetection);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
    $webbrowserCookiesTableID         = CreateVariableByName($categoryId_BrowserCookies,"BrowserCookieTable", 3,"~HTMLBox", "", 1010);


    if (  (isset($installedModules["OperationCenter"])) && (isset($installedModules["DetectMovement"]))  )
        {    
        IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter'); 
        $moduleManagerOC 	= new IPSModuleManager('OperationCenter',$repository);
        $CategoryIdDataOC   = $moduleManagerOC->GetModuleCategoryID('data');
        $categoryId_DetectMovement    = CreateCategory('DetectMovement',   $CategoryIdDataOC, 150);
		$TableEventsID=CreateVariable("TableEvents",3, $categoryId_DetectMovement,0,"~HTMLBox",null,null,"");

        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
    	$moduleManagerDM = new IPSModuleManager('DetectMovement',$repository);
        $CategoryIdDataDM     = $moduleManagerDM->GetModuleCategoryID('data');
        $CategoryIdAppDM      = $moduleManagerDM->GetModuleCategoryID('app');
        $testMovementscriptId  = IPS_GetObjectIDByIdent('TestMovement', $CategoryIdAppDM);  
		$SchalterSortID=CreateVariable("Tabelle sortieren",1, $categoryId_DetectMovement,0,"SortTableEvents",$testMovementscriptId,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    
    	$DetectDeviceHandler = new DetectDeviceHandler();
        $DetectDeviceListHandler = new DetectDeviceListHandler();               // neuer Handler für die DeviceList, registriert die Devices in EvaluateHarwdare_Configuration

        }

    IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
    $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php

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
     * wir nutzen SystemTPA (Werkzeugschlüsel, Tabpane ist EvaluateHardware)
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    /* Webfront in SystemTPA, Anzeige Homematic Errror Status und Log */

    // EvaluateHardware

    //$wfcHandling =  new WfcHandling($WFC10_ConfigId);                  // gleich für Interop Admin konfigurieren
    $WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();   

    $moduleManagerGUI = new IPSModuleManager('IPSModuleManagerGUI',$repository);
    $configWFrontGUI=$ipsOps->configWebfront($moduleManagerGUI,false);     // wenn true mit debug Funktion
    $tabPaneParent="roottp";                        // Default Wert

    $configWF=array();                                      // für die Verwendung vorbereiten
    if (isset($configWFrontGUI["Administrator"]))
        {
        $tabPaneParent=$configWFrontGUI["Administrator"]["TabPaneItem"];
        //print_r($configWFrontGUI["Administrator"]);   

        /* es gibt kein Module mit passenden ini Dateien, daher etwas improvisieren und fixe Namen nehmen */
        $configWF["Enabled"]=true;
        $configWF["Path"]="Visualization.WebFront.Administrator.EvaluateHardware";
        $configWF["ConfigId"]=$WebfrontConfigID["Administrator"];              
        $configWF["TabPaneParent"]=$tabPaneParent;
        $configWF["TabPaneItem"]="EvaluateHardware"; 
        $configWF["TabPaneOrder"]=1050;                                          
        echo "====================================================================================================\n";
        echo "EvaluateHardware Module Überblick im Administrator Webfront $tabPaneParent.".$configWF["TabPaneItem"]." abspeichern.\n";
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
    // Nachrichten sind immer rechts
    $webfront_links["EvaluateHardware"]["Nachrichten"] = array(
        $actionUpdateID=> array(
                "NAME"				=> "Update",
                "ORDER"				=> 10,
                "ADMINISTRATOR" 	=> true,
                "USER"				=> false,
                "MOBILE"			=> false,
                    ),
        $logEvaluateHardwareID => array(
                "NAME"				=> "Nachrichten",
                "ORDER"				=> 20,
                "ADMINISTRATOR" 	=> true,
                "USER"				=> false,
                "MOBILE"			=> false,
                    ),
                );	           
    $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern
    $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            //true für Debug
    $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       

    // MessageHandler Webfront, verwendet einfaches easysetupWebfront

   if (  (isset($installedModules["OperationCenter"])) && (isset($installedModules["DetectMovement"]))  )
        {    
        $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern

        /* es gibt kein Module mit passenden ini Dateien, daher etwas improvisieren und fixe Namen nehmen */
        $configWF["Path"]="Visualization.WebFront.Administrator.MessageHandler";
        $configWF["TabPaneItem"]="MessageHandler"; 
        $configWF["TabPaneOrder"]=2000;                                          

        echo "====================================================================================================\n";
        echo "Webfront TabPaneItem $tabPaneParent.".$configWF["TabPaneItem"]." erzeugen:\n";

        $webfront_links=array(
            "MessageTabellen" => array(
                $SchalterSortID => array(
                        "NAME"				=> "SortierenEreignisse",
                        "ORDER"				=> 100,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),        
                $TableEventsID => array(
                        "NAME"				=> "NachrichtenTabelleDetailiert",
                        "ORDER"				=> 110,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),
            
                "CONFIG" => array("type" => "link"),
                        ),
                    );	           

        $wfcHandling->easySetupWebfront($configWF,$webfront_links,["Scope" => "Administrator","EmptyCategory"=>true],true);            //true für Debug
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       
        $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern

        $configWF["TabPaneItem"]="MessageList"; 
        $configWF["TabPaneOrder"]=2010;                                          

        echo "====================================================================================================\n";
        echo "Webfront TabPaneItem $tabPaneParent.".$configWF["TabPaneItem"]." erzeugen:\n";

        $webfront_links=array(
            "MessageLists" => array(
                $actionSortMessageTableID => array(
                        "NAME"				=> "Sortieren",
                        "ORDER"				=> 200,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),        
                $messageTableID => array(
                        "NAME"				=> "NachrichtenTabelle",
                        "ORDER"				=> 210,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),
                "CONFIG" => array("type" => "link"),
                        ),
                    );	           

        $wfcHandling->easySetupWebfront($configWF,$webfront_links,["Scope" => "Administrator","EmptyCategory"=>false],true);            //true für Debug
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       

        /*-------------------------------------*/

        $configWF["TabPaneItem"]="TopologyList"; 
        $configWF["TabPaneOrder"]=2020;                                          

        echo "====================================================================================================\n";
        echo "Webfront TabPaneItem $tabPaneParent.".$configWF["TabPaneItem"]." erzeugen:\n";

        $webfront_links=array(
            "DetectTopologies" => array(
                $topologyControlTableID => array(
                        "NAME"				=> "ControlTable",
                        "ORDER"				=> 200,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),
                $actionViewMessageTableID => array(
                        "NAME"				=> "Ansichten",
                        "ORDER"				=> 210,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),        
                $topologyTableID => array(
                        "NAME"				=> "TopologieTabelle",
                        "ORDER"				=> 220,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),
                "CONFIG" => array("type" => "link"),
                        ),
                    );	           


        $wfcHandling->easySetupWebfront($configWF,$webfront_links,["Scope" => "Administrator","EmptyCategory"=>false],true);            //true für Debug
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       

        /*-------------------------------------*/

        $configWF["TabPaneItem"]="BrowserCookies"; 
        $configWF["TabPaneOrder"]=2030; 

        echo "====================================================================================================\n";
        echo "Webfront Browser Cookies $tabPaneParent.".$configWF["TabPaneItem"]." erzeugen:\n";

        $webfront_links=array(
            "BrowserCookies" => array(
                $actionViewWebbrowserTableID => array(
                        "NAME"				=> "Ansichten",
                        "ORDER"				=> 210,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),        
                $webbrowserCookiesTableID => array(
                        "NAME"				=> "BrowserCookies",
                        "ORDER"				=> 220,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),
                "CONFIG" => array("type" => "link"),
                        ),
                    );	           

        $wfcHandling->easySetupWebfront($configWF,$webfront_links,["Scope" => "Administrator","EmptyCategory"=>false],true);            //true für Debug
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       

        }
    /*-------------*/

	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben */

		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "Webportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in: ". $categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."\n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		//DeleteWFCItems($WFC10_ConfigId, "root");
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

        $configWF = $configWFront["Administrator"];
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$configWF["TabPaneItem"]." -> HouseTPA -> ".$configWF["TabPaneParent"]." Order ".$configWF["TabPaneOrder"]." Icon ".$configWF["TabPaneIcon"]."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, "HouseTPA", $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], "", "HouseRemote");    /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $configWF["TabPaneItem"], "HouseTPA",  20, $configWF["TabPaneName"], $configWF["TabPaneIcon"]);  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

        $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern
		echo "\nWebportal Topology Datenstruktur installieren in: ".$configWF["Path"]." \n";
        $categoryId_WebFrontAdministrator         = CreateCategoryPath($configWF["Path"]);
		IPS_SetHidden($categoryId_WebFrontAdministrator,true);
		$worldID=CreateCategory("World",  $categoryId_WebFrontAdministrator, 10);           // Wird nicht mehr neu benannt, wenn schon einmal verhanden
	    //EmptyCategory($worldID);                                                              // die wird nicht mehr erzeugt
        $wfcHandling->DeleteWFCItems("World");
		$wfcHandling->CreateWFCItemCategory  ('WorldTPA', $configWF["TabPaneItem"],   10, 'World', 'Wellness', $worldID   /*BaseId*/, 'true' /*BarBottomVisible*/);
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       

        // Die Kategorie World in Webfront-EvaluateHardware wird in DetectMovement_Installation mit Werten befüllt

		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Webfront Tiles aufbauen, Struktur mit Instanzen in Topology aufbauen
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    if (isset($DetectDeviceHandler))		
        {
        $topId=@IPS_GetObjectIDByName("Topology", 0 );            
        $topConfig["ID"]=$topId;
        $topConfig["Use"]=["Place","Room","Device"];
        // init nicht aktiviert, eigene Config hier.
        echo "====================================================================================\n";
		echo "Webportal Tiles/Kacheln mit Startpunt Topology aufbauen:\n";
        echo "TopologyID gefunden : $topId, eine Topologie mit Kategorien hier erstellen.\n";
        $DetectDeviceHandler->create_Topology($topConfig, $debug);            // true für init, true für Debug, bei init löscht sich die ganze Kategorie und baut sie neu auf, macht auch schon _construct
        $topology=$DetectDeviceHandler->Get_Topology();
        $channelEventList    = $DetectDeviceHandler->Get_EventConfigurationAuto();              // alle Events
        $deviceEventList     = $DetectDeviceListHandler->Get_EventConfigurationAuto();          // alle Geräte
        $configurationDevice = $DetectDeviceHandler->Get_EventConfigurationAuto();        // IPSDetectDeviceHandler_GetEventConfiguration()
        echo "CreateTopologyInstances wird aufgerufen, erzeugt die Instances Place, Room und Device/Devicegroup aus der TopologyLibrary:\n";
        $topinstconfig = array();
        $topinstconfig["Sort"]="Kategorie";
        $topinstconfig["Use"]["Room"]=true;
        $topinstconfig["Use"]["Place"]=true;
        $topinstconfig["Use"]["DeviceGroup"]=true;
        $topologyLibrary->createTopologyInstances($topology,$topinstconfig, $debug);          // true für Debug, erzeugt eh keine Devices            
        $topinstconfig["Use"]["Device"]="Actuators";                               // nur die Actuators hinzufügen
        $topologyLibrary->sortTopologyInstances($deviceList, $topology, $channelEventList, $deviceEventList,$topinstconfig,$debug);          // die Devices hinzunehmen und einsortieren
        $topologyPlusLinks=$DetectDeviceHandler->mergeTopologyObjects($topology,$configurationDevice,$debug);        // true for Debug
        $DetectDeviceHandler->updateLinks($topologyPlusLinks);
        }





	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Evaluierung starten
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    if ($debug==false)          // anders rum, wenn nicht als Script von der Console aufgerufen auch EvaluateHardare starten
        {
        $scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdApp);
        echo "\n";
        echo "Die Scripts sind auf               ".$CategoryIdApp."\n";
        echo "Evaluate Hardware hat die ScriptID ".$scriptIdEvaluateHardware.". Wird jetzt aufgerufen.\n";
        echo "Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
        echo "====================================================================================\n";
        echo IPS_RunScriptWait($scriptIdEvaluateHardware);
        echo "====================================================================================\n";
        echo "Script Evaluate Hardware bereits abgeschlossen. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
        }
	
	
	
?>