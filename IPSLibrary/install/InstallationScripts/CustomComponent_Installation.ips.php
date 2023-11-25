<?php
	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */
	 
	/***********************************
	 *
	 * Script für zusätzliche eigene Komponenten und Programme rund um die Verarbeitung der Hardware Komponenten
	 * baut das gesamte Webfront mit Administrator und User auf
	 * korrigiert diverse Einstellungen, loescht nicht konfigurierte Webfrontends
	 *
	 * @file          CustomComponent_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

    $noinstall=true;                        /* true, keine Installation der lokalen Variablen um die Laufzeit der Routine zu verkuerzen, macht RemoteAccess on the fly mit installComponenFull */
    $evaluateHardware=false;                /* false, keine EvaluateHardware aufgerufen für die Aktualisierung */
    $excessiveLog=true;                    /* true für mehr echo Logging when doing install */ 

    /*******************************
    *
    * Initialisierung, Modul Handling Vorbereitung
    *
    ********************************/

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');

    $startexec=microtime(true);    
    echo "Abgelaufene Zeit : ".exectime($startexec)." Sek. Max Scripttime is 30 Sek \n";
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('CustomComponent',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	$ergebnis1=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	$ergebnis2=$moduleManager->VersionHandler()->GetVersion('CustomComponent')."     Status: ".$moduleManager->VersionHandler()->GetModuleState();
	//echo "\nKernelversion : ".IPS_GetKernelVersion()."\n";
	//echo "IPSModulManager Version : ".$ergebnis1."\n";
	//echo "CustomComponent Version : ".$ergebnis2."\n";

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	//echo $inst_modules."\n";

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

    $dosOps = new dosOps();
    $ipsOps = new ipsOps();    
    $webOps = new webOps();
	
    $modulhandling = new ModuleHandling();	                    // aus AllgemeineDefinitionen

    $dosOps->setMaxScriptTime(30);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme

    /*******************************
     *
     * Basic-Init
     *
     ********************************/

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $hardwareStatusCat      = CreateCategoryByName($CategoryIdData,"HardwareStatus",10001);
    $mirrorCat              = CreateCategoryByName($CategoryIdData,"Mirror",10001);
    $loggingConfCat         = CreateCategoryByName($CategoryIdData,"LoggingConfig",10001);

    $valuesDeviceTableID    = CreateVariable("ValuesTable", 3, $hardwareStatusCat,1020,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')

    $loggingConf            = CreateVariableByName($loggingConfCat, "Logging_Variable", 1); /* 0 Boolean 1 Integer 2 Float 3 String */   // nur eine Variable Loggen, ist übersichtlicher
    SetValue($loggingConf,false);         // false no logg, true logg all, OID value log OID

    /*******************************
     *
     * Install von anderen Modulen zuerst
     *
     ********************************/

	if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; }
	if (isset ($installedModules["EvaluateHardware"])) 
        { 
        IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');      
        IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");                  // jetzt neu unter config
        $moduleManagerEH = new IPSModuleManager('EvaluateHardware',$repository);
        $CategoryIdAppEH      = $moduleManagerEH->GetModuleCategoryID('app');
        echo "Modul EvaluateHardware ist installiert. Scripts für WebfronControl sind hier $CategoryIdAppEH.\n"; 
        $scriptIdImproveDeviceDetection   = IPS_GetScriptIDByName('ImproveDeviceDetection', $CategoryIdAppEH);
        $pname="DeviceTables";                                         // keine Standardfunktion, da Inhalte Variable
        $nameID=["DeviceType","Rooms"];
        $webOps->createActionProfileByName($pname,$nameID,0);  // erst das Profil, dann die Variable, 0 ohne Selektor
        $actionDeviceTableID          = CreateVariableByName($hardwareStatusCat,"ShowTablesBy", 1,$pname,"",1010,$scriptIdImproveDeviceDetection);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)

        $hardwareTypeDetect = new Hardware();
        $dir = IPS_GetKernelDir()."scripts\\IPSLibrary\\config\\modules\\EvaluateHardware\\";
        $file = "EvaluateHardware_Devicelist.inc.php";
        if ($dosOps->fileAvailable($file,$dir))
            {
            if ($excessiveLog) 
                {
                echo "========================================================================\n";    
                echo "Statistik der Register nach Typen:\n";
                }
            IPSUtils_Include ($file,"IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt
            if (function_exists("deviceList")) $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php
            else $deviceList = array();
            }
        else 
            {
            echo "Modul EvaluateHardware ist zwar installiert, aber EvaluateHardware wurde noch nicht aufgerufen.\n"; 
            $deviceList = array();    
            }
        $statistic = $hardwareTypeDetect->getRegisterStatistics($deviceList,false);                // false keine Warnings ausgeben
        if ($excessiveLog) $hardwareTypeDetect->writeRegisterStatistics($statistic);        
        //print_r($statistic);        
        } 
    else 
        { 
        echo "Modul EvaluateHardware ist NICHT installiert. Routinen werden uebersprungen.\n"; 
        $evaluateHardware=false;
        }
	if (isset ($installedModules["RemoteReadWrite"])) { echo "Modul RemoteReadWrite ist installiert.\n"; } else { echo "Modul RemoteReadWrite ist NICHT installiert.\n"; }
	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "Modul RemoteAccess ist installiert.\n";
		IPSUtils_Include ('RemoteAccess_Configuration.inc.php', 'IPSLibrary::config::modules::RemoteAccess');
		}
	else
		{
		echo "Modul RemoteAccess ist NICHT installiert. Variablen selbst hier installieren, möglicherweise nicht alle, check !\n";
        $noinstall=false;
		}
	if (isset ($installedModules["IPSCam"])) { 				echo "Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
	if (isset ($installedModules["OperationCenter"])) { 	echo "Modul OperationCenter ist installiert.\n"; } else { echo "Modul OperationCenter ist NICHT installiert.\n"; }

    /*******************************
    *
    * Zusammenräumen
    *
    ********************************/

	if (isset ($installedModules["IPSTwilight"]))
		{
		$moduleManagerTwilight = new IPSModuleManager('IPSTwilight');
		$WFC10Twilight_Path    	 		= $moduleManagerTwilight->GetConfigValue('Path', 'WFC10');
		$categoryId_Twilight_Path                = CreateCategoryPath($WFC10Twilight_Path);
		IPS_SetHidden($categoryId_Twilight_Path, true); /* in der normalen Viz Darstellung verstecken */	
		echo "Twilight Vizualisation Path : ".$WFC10Twilight_Path." versteckt.\n";
		}

    $module="IPSModuleManagerGUI";
	if (isset ($installedModules[$module]))
		{
		$mManager = new IPSModuleManager($module);
		$WFC10Module_Path    	 = $mManager->GetConfigValue('Path', 'WFC10');
		$categoryId_Module_Path  = CreateCategoryPath($WFC10Module_Path);
		IPS_SetHidden($categoryId_Module_Path, true); /* in der normalen Viz Darstellung verstecken */	
        $parent=IPS_GetParent($categoryId_Module_Path);
        if ( ($parent != "Administrator") && ($parent != "User") ) IPS_SetHidden($parent, true);
		echo "Module ".$module." Vizualisation Path : ".$WFC10Module_Path." versteckt.\n";
		}        

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Evaluierung Harwdare Script starten um die aktuellen Werte zu erfassen, CustomComponent baut darauf auf
	 * dauert halt auch etwas.
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    if ($evaluateHardware)
        {
        $moduleManagerEH = new IPSModuleManager('EvaluateHardware',$repository);
        $CategoryIdAppEH      = $moduleManagerEH->GetModuleCategoryID('app');	
        echo "\n";
        echo "Die EvaluateHardware Scripts sind auf               ".$CategoryIdAppEH."\n";
        $scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdAppEH);
        echo "Evaluate Hardware hat die ScriptID                  ".$scriptIdEvaluateHardware." \n";
        IPS_RunScriptWait($scriptIdEvaluateHardware);
        echo "Script Evaluate Hardware wurde gestartet und bereits abgearbeitet. Aktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";
        }

    /*******************************
    *
    * Webfront Vorbereitung
    *
    ********************************/

	echo "\n";
	if ($excessiveLog) echo "Custom Component Category OIDs for data : ".$CategoryIdData." for App : ".$CategoryIdApp."\n";

    /* check if Administrator and User Webfronts already available */
    $VisualizationID   = CreateCategoryByName(0, "Visualization",3000);       // Kategorie anlegen
    $webfrontID        = CreateCategoryByName($VisualizationID, "WebFront");
    $webfrontAdminID   = CreateCategoryByName($webfrontID, "Administrator");
    $webfrontUserID    = CreateCategoryByName($webfrontID, "User");
    $webfrontTileID    = CreateCategoryByName($webfrontID, "Tiles");


    $wfcHandling =  new WfcHandling();
    /* Workaround wenn im Webfront die Root fehlt */
    $WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();   
    //$wfcHandling->CreateWFCItemRootTabPane($WebfrontConfigID["Administrator"],"roottp","",0,"IP-Symcon","IPS");         // roottp im Administrator Webfront anlegen
    //$wfcHandling->CreateWFCItemRootTabPane($WebfrontConfigID["User"],"roottp","",0,"IP-Symcon","IPS");                  // roottp im User Webfront anlegen
    $wfcHandling->CreateWFCItemRootTabPane($WebfrontConfigID["Administrator"],"roottp","",$webfrontAdminID,"IP-Symcon","IPS");         // roottp im Administrator Webfront anlegen
    $wfcHandling->CreateWFCItemRootTabPane($WebfrontConfigID["User"],"roottp","",$webfrontUserID,"IP-Symcon","IPS");                  // roottp im User Webfront anlegen

    /* Standard Config überprüfen */    
    $WebfrontConfigID = $wfcHandling->installWebfront();            // die beiden Webfronts anlegen und das Standard Webfront loeschen

    if (isset($WebfrontConfigID["Kachel Visualisierung"]))
        {
        $KachelID = $WebfrontConfigID["Kachel Visualisierung"];
        echo "Webfront Configurator \"Kachel Visualisierung\" bereits vorhanden : ".$KachelID." \n";
        $config=json_decode(IPS_GetConfiguration($KachelID));
        if (isset($config->BaseID))
            {
            $BaseID=IPS_GetProperty($KachelID, "BaseID");
            if ($BaseID != $webfrontTileID)
                {
                echo "BaseID $BaseID nicht gleich $webfrontTileID \n"; 
                IPS_SetProperty($KachelID, "BaseID", $webfrontTileID);
                IPS_ApplyChanges($KachelID);
                }
            }
        }

    /*******************************
    *
    * Webfront Konfiguration einlesen
    *
    ********************************/
			
    $configWFront=$ipsOps->configWebfront($moduleManager,false);     // wenn true mit debug Funktion
    if ($excessiveLog) 
        {
        echo "\nKonfiguration für das neue Webfront des CustomComponent auslesen :\n";        
        print_R($configWFront);
        }

	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	$Mobile_Enabled       = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
    $Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);

	if ($WFC10_Enabled==true)       $WFC10_ConfigId       = $WebfrontConfigID["Administrator"];		
	if ($WFC10User_Enabled==true)   $WFC10User_ConfigId   = $WebfrontConfigID["User"];
	if ($Mobile_Enabled==true)      $Mobile_Path          = $moduleManager->GetConfigValue('Path', 'Mobile');
	if ($Retro_Enabled==true)		$Retro_Path        	  = $moduleManager->GetConfigValue('Path', 'Retro');
	
    /**************************************************
     *
     * Netatmo wird hier überwacht, Status HTML von jedem Netatmo Gateway hier einbringen
     * wird im System Tab angezegt. Es gibt dafür ein eigenes SubTab. Hardcoded, verwendet kein .ini
     *
     */

    /* wird auch für die nächste Abfrage benötigt 
     * zuerst aus dem ModulManager die Konfig von IPSModuleManagerGUI abrufen */
    $moduleManagerGUI = new IPSModuleManager('IPSModuleManagerGUI',$repository);
    $configWFrontGUI=$ipsOps->configWebfront($moduleManagerGUI,false);     // wenn true mit debug Funktion
    $tabPaneParent="roottp";                        // Default Wert

    echo "\n";
    echo "==================================================\n";
    echo "Webfront für Netatmo Geräteverwaltung erstellen.\n";
    echo "Status Evaluierung, check ob Netatmos Modules vorhanden sind:\n";
    $guid = "{1023DB4A-D491-A0D5-17CD-380D3578D0FA}";  // Netatmo Gerät 
    $instances = $modulhandling->getInstances($guid);
    if (sizeof($instances)>0)                                   // es gibt Netatmo Geräte
        {
        $configWF=array();
        if (isset($configWFrontGUI["Administrator"]))
            {
            $tabPaneParent=$configWFrontGUI["Administrator"]["TabPaneItem"];
            echo "  Netatmo Module Überblick im Administrator Webfront $tabPaneParent abspeichern.\n";
            //print_r($configWFrontGUI["Administrator"]);   

            /* es gibt kein Module mit Netatmo ini Dateien, daher etwas improvisieren und fixe Namen nehmen */
            $configWF["Enabled"]=true;
            $configWF["Path"]="Visualization.WebFront.Administrator.Netatmo";
            $configWF["ConfigId"]=$WebfrontConfigID["Administrator"];              
            $configWF["TabPaneParent"]=$tabPaneParent;
            $configWF["TabPaneItem"]="Netatmo"; 
            $configWF["TabPaneOrder"]=1000;                                          
            }

        /* Netatmo Stationen auswerten */

        $station=array();
        foreach ($instances as $id => $instance)
            {
            $objectType=IPS_GetObject($instance)["ObjectType"];
            if ($objectType==1)
                {
                $config=IPS_GetConfiguration($instance);
                $configArray=json_decode($config,true);
                if ($configArray["module_type"]=="Station") $station[$configArray["station_id"]]=$instance;
                }
            }        
        if ($excessiveLog) echo "   Diese Stationen haben wir gefunden:\n";
        $webfront_links=array(); $stationKey="Netatmo";
        foreach ($station as $stationId => $oidStation)
            {
            $stationName = IPS_GetName($oidStation);                
            if ($excessiveLog) echo "      $stationName\n";
            $webfront_links[$stationKey]["Auswertung"][$oidStation]["NAME"]=$stationName;
            $webfront_links[$stationKey]["Auswertung"][$oidStation]["ORDER"]=10;
            $childs=IPS_GetChildrenIDs($oidStation);
            //print_R($childs);
            foreach ($childs as $child)
                {
                $childName=IPS_GetName($child);
                if ($childName=="Status der Station und der Module")
                    {
                    $webfront_links[$stationKey]["Auswertung"][$oidStation]["GROUP"][$child]["NAME"]=$childName;
                    $webfront_links[$stationKey]["Auswertung"][$oidStation]["GROUP"][$child]["ORDER"]=10;
                    }
                //echo "      $childName\n";
                }
            }
        if ($excessiveLog) print_r($webfront_links);

        //$wfcHandling =  new WfcHandling($WFC10_ConfigId);         // Convergence to old way of configuring Webfront, new way is faster
        $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern
        $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            // true für Debug
        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       
        }
    else if ($excessiveLog) echo "   Keine Netatmos Modules vorhanden.\n";

    // Hardwarestatus als Responsive Display machen, lustige Darstellung des Hardwarestatus nach Räumen oder nach Gewerken
    echo "\n";
    echo "==================================================\n";
    echo "Webfront für Darstellung Hardwarestatus erstellen.\n";
    $configWF = $configWFront["Administrator"];
	echo "Webfront SubTabPane mit Parameter ConfigID:".$WFC10_ConfigId.",Item:".$configWF["TabPaneItem"]."Show".",Parent:".$configWF["TabPaneParent"].",Order:20,Name:".$configWF["TabPaneName"].",Icon:".$configWF["TabPaneIcon"]."\n"; 
    //CreateWFCItemTabPane   ($WFC10_ConfigId, $configWF["TabPaneItem"]."Show"  , $configWF["TabPaneParent"],  20, "WerteTabellen", "");  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */
    $configWF["Path"]="Visualization.WebFront.Administrator.HardwareStatus";        // sonst wird ganze Gruppe gelöscht, mit allen local data
    echo $configWF["Path"]."\n";
    $configWF["TabPaneName"] = "WerteTabellen";
    $webfront_links=array(
        "Werte" => array(                                           // das soll ein CategoryPane werden
            $valuesDeviceTableID => array(
                    "NAME"				=> "Werte",
                    "ORDER"				=> 100,
                    "ADMINISTRATOR" 	=> true,
                    "USER"				=> false,
                    "MOBILE"			=> false,
                        ),      
            "CONFIG" => array("type" => "link"),
                    ),
                );
    if (isset($actionDeviceTableID)) 
        {
        echo "Es kommt noch ein Button dazu.\n";
        $webfront_links["Werte"][$actionDeviceTableID] =array(
                    "NAME"				=> "Choose Display Arrangement by",
                    "ORDER"				=> 10,
                    "ADMINISTRATOR" 	=> true,
                    "USER"				=> false,
                    "MOBILE"			=> false,
                ); 
        }       
    $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern
    $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            //true für Debug
    $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);

    echo "Abgelaufene Zeit nach Bearbeitung des Webfronts für Netatmo und Hardwarestatus: ".exectime($startexec)." Sek \n";
    echo "\n\n===================================================================================================\n";

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Variablen Profile für lokale Darstellung anlegen, sind die selben wie bei Remote Access
	 *
     * Vorteil ist das ein vorhandenes Profil nicht mühsam über Remote angelegt werden muss also gleich hier richtig anlegen
     *
     *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    $profileOps = new profileOps();
	echo "Darstellung der Variablenprofile im lokalem Bereich, wenn fehlt anlegen:\n";
	//$profilname=array("Temperatur"=>"new","TemperaturSet"=>"new","Humidity"=>"new","Switch"=>"new","Button"=>"new","Contact"=>"new","Motion"=>"new","Pressure"=>"Netatmo.Pressure","CO2"=>"Netatmo.CO2","mode.HM"=>"new");
	$profilname=array("Temperatur"=>"update","TemperaturSet"=>"update","Humidity"=>"update","Switch"=>"update","Button"=>"update","Contact"=>"update","Window"=>"update","Motion"=>"update","Pressure"=>"Netatmo.Pressure","CO2"=>"Netatmo.CO2","mode.HM"=>"update");
    $profileOps->synchronizeProfiles($profilname);

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Variablen für Darstellung evaluieren
     * webfront_links für HouseTP(A/U) erstellen
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
		
	/* Links für Webfront identifizieren, alle Kategorien in CustomComponents Data Verzeichnis  /core/IPSComponent  darstellen  
	 * Trennung in Tabs erfolgt durch die bezeichnung der Kategrorien wie zB Bewegung-Auswertung und Bewegung-Nachrichten 
     * - Zeichen für Aufteilung in links und rechts nach Auswertung und Nachrichten
	 * wenn kein - zeichen auch keine Darstellung
     *
     * easySetupWebfront braucht folgende Struktur
     * Tabpane 
     *   Subtabpane Auswertung
     *
     *   Subtabpane Nachrichten
     *      VariableID
     *          NAME
     *          ORDER
	 */
	 
	echo "\nLinks für Webfront Administrator und User identifizieren :\n";
	$webfront_links=array();
	$Category=IPS_GetChildrenIDs($CategoryIdData);
	foreach ($Category as $CategoryId)
		{
		echo "  Category    ID : ".$CategoryId." Name : ".IPS_GetName($CategoryId)."\n";
		$Params = explode("-",IPS_GetName($CategoryId)); 
        if (sizeof($Params)>1)
            {
            $SubCategory=IPS_GetChildrenIDs($CategoryId);
            foreach ($SubCategory as $SubCategoryId)
                {
                if (IPS_GetObject($SubCategoryId)["ObjectIsHidden"] == false)           // versteckte Objekte nicht mehr im Webfront anzeigen
                    {
                    //echo "       ".IPS_GetName($SubCategoryId)."   ".$Params[0]."   ".$Params[1]."\n";
                    $webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["NAME"]=IPS_GetName($SubCategoryId);
                    $webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["ORDER"]=IPS_GetObject($SubCategoryId)["ObjectPosition"];
                    }
                }
            }
		}
	
    /* Das erste Arrayfeld bestimmt die Tabs in denen jeweils ein linkes und rechtes Feld erstellt werden: Bewegung, Feuchtigkeit etc.	
	 *
	 */
    if ($excessiveLog) echo "Array webfront_Links als Input für die Webfront Erstellung: \n";
    $countEntries=0; 
    //print_r($webfront_links);
    foreach ($webfront_links as $group => $webfront_link)
        {
        if ($excessiveLog) echo "Gruppe $group:\n";
        foreach ($webfront_link as $area => $entries)
            {
            if ($excessiveLog) echo "     Area $area:\n";
            if ($area != "Nachrichten")
                {
                foreach ($entries as $name => $entry) 
                    {
                    if ($excessiveLog) echo "       ".$entry["NAME"]." ".$entry["ORDER"]."\n";
                    $countEntries++;
                    }
                }
            }
        }

    echo "\n\n===================================================================================================\n";
    echo "Abgelaufene Zeit vor Bearbeitung des Webfronts für Administrator: ".exectime($startexec)." Sek \n";

    /* ----------------------------------------------------------------------------------------------------------------------------
        * WebFront Installation von Administrator und User wenn im ini gewünscht
        *    Installation erfolgt im roottp.HouseTPA.AccessTPA.
        *
        * ---------------------------------------------------------------------------------------------------------------------------- */

    if (isset($configWFront["Administrator"]))
        {
		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "Webportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in Kategorie ". $categoryId_AdminWebFront." (".IPS_GetName($categoryId_AdminWebFront).")\n";
        echo "Es sind insgesamt $countEntries Einträge zu bearbeiten\n";

		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		//DeleteWFCItems($WFC10_ConfigId, "root");
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

        $configWF = $configWFront["Administrator"];
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit    Parameter ConfigID:".$WFC10_ConfigId.",Item:".$configWF["TabPaneParent"].",Parent:roottp,Order:".$configWF["TabPaneOrder"]."Name:,Icon:HouseRemote\n";        
 		echo "Webfront SubTabPane mit Parameter ConfigID:".$WFC10_ConfigId.",Item:".$configWF["TabPaneItem"].",Parent:".$configWF["TabPaneParent"].",Order:20,Name:".$configWF["TabPaneName"].",Icon:".$configWF["TabPaneIcon"]."\n"; 
        if ($configWF["TabPaneParent"] !=  "roottp")      
            {               
            CreateWFCItemTabPane   ($WFC10_ConfigId, $configWF["TabPaneParent"], "roottp",             $configWF["TabPaneOrder"], "", "HouseRemote");    /* macht das Haeuschen in die oberste Leiste */
            CreateWFCItemTabPane   ($WFC10_ConfigId, $configWF["TabPaneItem"]  , $configWF["TabPaneParent"],  20, $configWF["TabPaneName"], $configWF["TabPaneIcon"]);  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */
            //CreateWFCItemTabPane   ($WFC10_ConfigId, $configWF["TabPaneItem"]  , $configWF["TabPaneParent"],  20, "WerteTabellen", "");  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */
            //print_R($configWF);
            //$wfcHandling->deletePane($configWF["ConfigId"], "roottpBewegung");
            echo "\n\n===================================================================================================\n";

            //$wfcHandling =  new WfcHandling($WFC10_ConfigId);         // Convergence to old way of configuring Webfront, new way is faster
            $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, wir arbeiten im internen Speicher und müssen nachher speichern
            $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            // true für Debug
            $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       
            }
        else echo "***Fehler, ".$configWF["TabPaneParent"]." darf nicht roottp sein.\n";
        }



     if (isset($configWFront["User"]))
        {
        $configWF = $configWFront["User"];
        echo "\n\n===================================================================================================\n";
        $wfcHandling =  new WfcHandling($WFC10User_ConfigId);
        $wfcHandling->easySetupWebfront($configWF,$webfront_links,"User");
        } 

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Installation von HouseTP(A/U)
     * auf Basis von webfront_links
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

if (false)
    {
	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben */

		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "Webportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in Kategorie ". $categoryId_AdminWebFront." (".IPS_GetName($categoryId_AdminWebFront).")\n";
        
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		//DeleteWFCItems($WFC10_ConfigId, "root");
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit    Parameter ConfigID:".$WFC10_ConfigId.",Item:".$WFC10_TabPaneParent.",Parent:rootp,Order:".$WFC10_TabPaneOrder."Name:,Icon:HouseRemote\n";        
 		echo "Webfront SubTabPane mit Parameter ConfigID:".$WFC10_ConfigId.",Item:".$WFC10_TabPaneItem.",Parent:".$WFC10_TabPaneParent.",Order:20,Name:".$WFC10_TabPaneName.",Icon:".$WFC10_TabPaneIcon."\n";        
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneParent, "roottp",             $WFC10_TabPaneOrder, "", "HouseRemote");    /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem  , $WFC10_TabPaneParent,  20, $WFC10_TabPaneName, $WFC10_TabPaneIcon);  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

		/*************************************/
		
		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */
		echo "\nWebportal Datenstruktur installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
		IPS_SetHidden($categoryId_WebFrontAdministrator,true);

		foreach ($webfront_links as $Name => $webfront_group)
		   {
			/* Das erste Arrayfeld bestimmt die Tabs in denen jeweils ein linkes und rechtes Feld erstellt werden: Bewegung, Feuchtigkeit etc.
			 * Der Name für die Felder wird selbst erfunden.
			 */
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontAdministrator, 10);
			EmptyCategory($categoryId_WebFrontTab);

			$categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFrontTab, 10);
			$categoryIdRight = CreateCategory('Right', $categoryId_WebFrontTab, 20);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n";

			$tabItem = $WFC10_TabPaneItem.$Name;				/* Netten eindeutigen Namen berechnen */
			if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
			 	{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($WFC10_ConfigId, $tabItem);
				}
			else
				{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}	
			IPS_ApplyChanges ($WFC10_ConfigId);   /* wenn geloescht wurde dann auch uebernehmen, sonst versagt das neue Anlegen ! */
			echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";
			//CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,    0,     $Name,     "", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

			foreach ($webfront_group as $Group => $webfront_link)
				{
				foreach ($webfront_link as $OID => $link)
					{
					/* Hier erfolgt die Aufteilung auf linkes und rechtes Feld
			 		 * Auswertung kommt nach links und Nachrichten nach rechts
			 		 */					
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryIdLeft,  $link["ORDER"]);
				 		}
				 	if ($Group=="Nachrichten")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdRight."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryIdRight,  $link["ORDER"]);
						}
					}
    			}
			}
		}
	else
	   {
	   /* Admin not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert sind
		*/
	   DeleteWFCItems($WFC10_ConfigId, "HouseTPA");
	   EmptyCategory($categoryId_WebFrontAdministrator);		
	   }

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront User Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	if ($WFC10User_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */

		$categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
		echo "====================================================================================\n";
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
		CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);
		
		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */
		echo "\nWebportal LocalAccess TabPane installieren in: ".$WFC10User_Path." \n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible 
		echo "Webfront TabPane mit Parameter : ".$WFC10User_ConfigId." ".$WFC10User_TabPaneItem." ".$WFC10User_TabPaneParent." ".$WFC10User_TabPaneOrder." ".$WFC10User_TabPaneIcon."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, "HouseTPU", $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, "", "HouseRemote");
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, "HouseTPU",  20, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);       */
		
        echo "Webfront TabPane mit    Parameter ConfigID:".$WFC10_ConfigId.",Item:".$WFC10_TabPaneParent.",Parent:rootp,Order:".$WFC10_TabPaneOrder."Name:,Icon:HouseRemote\n";        
 		echo "Webfront SubTabPane mit Parameter ConfigID:".$WFC10_ConfigId.",Item:".$WFC10_TabPaneItem.",Parent:".$WFC10_TabPaneParent.",Order:20,Name:".$WFC10_TabPaneName.",Icon:".$WFC10_TabPaneIcon."\n";        
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneParent,"roottp", $WFC10_TabPaneOrder, "", "HouseRemote");    /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  20, $WFC10_TabPaneName, $WFC10_TabPaneIcon);  /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */
		/*************************************/

		$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
		IPS_SetHidden($categoryId_WebFrontUser,true);
		
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontUser, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			$tabItem = $WFC10User_TabPaneItem.$Name;
			if ( exists_WFCItem($WFC10User_ConfigId, $tabItem) )
			 	{
				echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($WFC10User_ConfigId, $tabItem);
				}
			else
				{
				echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}	
			IPS_ApplyChanges ($WFC10User_ConfigId);   /* wenn geloescht wurde dann auch uebernehmen, sonst versagt das neue Anlegen ! */
			echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10User_TabPaneItem."\n";

			CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem, $WFC10User_TabPaneItem, 0, $Name, "");
			CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);

			foreach ($webfront_group as $Group => $webfront_link)
				 {
				foreach ($webfront_link as $OID => $link)
					{
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontTab,  20);
				 		}
					}
    			}
			}
		}
	else
	   {
	   /* User not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert isnd
		*/
	   DeleteWFCItems($WFC10User_ConfigId, "HouseTPU");
	   EmptyCategory($categoryId_WebFrontUser);
	   }
    }
    
	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_MobileWebFront         = CreateCategoryPath($Mobile_Path);
		IPS_SetHidden($categoryId_MobileWebFront,false);	/* mus dargestellt werden, sonst keine Anzeige am Mobiltelefon */	
			
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_MobileWebFront, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			foreach ($webfront_group as $Group => $webfront_link)
				 {
				foreach ($webfront_link as $OID => $link)
					{
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryId_WebFrontTab."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontTab,  20);
				 		}
					}
    			}
			}
		}
	else
	   {
	   /* Mobile not enabled, alles loeschen */
	   }

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_RetroWebFront         = CreateCategoryPath($Retro_Path);
		}
	else
	   {
	   /* Retro not enabled, alles loeschen */
	   }

    echo "\nNach Webfront Installation, aktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";    


	/****************************************************************************************************************
	 *                                                                                                    
	 *                                      COMPONENTS INSTALLATION
	 *
	 ****************************************************************************************************************/

if ($noinstall==false)
    {
	$commentField="zuletzt Konfiguriert von CustomComponent_Installation um ".date("h:i am d.m.Y ").".";

	IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
	
	/****************************************************************************************************************
	 *                                                                                                    
	 *                                      Movement
	 *
	 ****************************************************************************************************************/


	echo "\n";
	echo "***********************************************************************************************\n";
	echo "Ereignishandler für CustomComponents aktivieren, selbe Routine auch in RemoteAccess und DetectMovement.\n";
	echo "\n";
	
	/* nur die CustomComponent Funktion registrieren */
	/* Wenn der Eintrag in Datenbank bereits besteht wird er nicht mehr geaendert */

	echo "***********************************************************************************************\n";
	echo "Bewegungsmelder und Contact Handler wird ausgeführt.\n";
    if (function_exists('deviceList'))
        {
        echo "Bewegungsmelder von verschiedenen Geräten werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "MOTION"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungsensoren */
        //print_r($result);
        echo "Kontakte von verschiedenen Geräten werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_CONTACT","REGISTER" => "CONTACT"],'IPSComponentSensor_Motion','IPSModuleSensor_Motion,',$commentField, false);				/* true ist Debug, Bewegungsensoren */
        //print_r($result);
        }
    elseif (function_exists('HomematicList'))
		{
		echo "\n";
		echo "    Homematic Bewegungsmelder werden registriert.\n";
		$componentHandling->installComponentFull(HomematicList(),"MOTION",'IPSComponentSensor_Motion','IPSModuleSensor_Motion',$commentField);

        if (false)  // alte Routine, ersetzt durch installComponent
            {
            echo "Homematic Bewegungsmelder und Kontakte werden registriert.\n";
            $Homematic = HomematicList();
            $keyword="MOTION";
            foreach ($Homematic as $Key)
                {
                $found=false;
                if ( (isset($Key["COID"][$keyword])==true) )
                    {
                    /* alle Bewegungsmelder */

                    $oid=(integer)$Key["COID"][$keyword]["OID"];
                    $found=true;
                    }

                if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
                    {
                    /* alle Kontakte */

                    $oid=(integer)$Key["COID"]["STATE"]["OID"];
                    $found=true;
                    }
                if ($found)
                    {
                    $variabletyp=IPS_GetVariable($oid);
                    if ($variabletyp["VariableProfile"]!="")
                        {
                        echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                        }
                    else
                        {
                        echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                        }

                    if (isset ($installedModules["RemoteAccess"]))
                        {
                        //echo "Rufen sie dazu eine entsprechende remote Access Routine auf .... \n";
                        }
                    else
                        {
                        /* Nachdem keine Remote Access Variablen geschrieben werden müssen die Eventhandler selbst aufgesetzt werden */
                        echo "Remote Access nicht installiert, Variable ".IPS_GetName($oid)." selbst registrieren.\n";
                        $messageHandler = new IPSMessageHandler();
                        $messageHandler->CreateEvents(); /* * Erzeugt anhand der Konfiguration alle Events */
                        $messageHandler->CreateEvent($oid,"OnChange");  /* reicht nicht aus, wird für HandleEvent nicht angelegt */

                        /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
                        $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
                        }
                    }
                }
            }       // ende if false

        if (function_exists('FS20List'))
            {
            echo "FS20 Bewegungsmelder und Kontakte werden registriert.\n";
            $TypeFS20=RemoteAccess_TypeFS20();
            $FS20= FS20List();
            foreach ($FS20 as $Key)
                {
                /* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
                $found=false;
                if ( (isset($Key["COID"]["MOTION"])==true) )
                    {
                    /* alle Bewegungsmelder */
                    $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                    $found=true;
                    }
                /* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei verknüpfen */
                if ((isset($Key["COID"]["StatusVariable"])==true))
                    {
                    foreach ($TypeFS20 as $Type)
                    {
                if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
                    {
                    $oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
                    $found=true;
                    }
                }
            }

            if ($found)
                {
                $variabletyp=IPS_GetVariable($oid);
                    if ($variabletyp["VariableProfile"]!="")
                    {
                        echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                        }
                    else
                    {
                        echo "   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
                        }

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

                    /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
                        $messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
                    }
                    }
                }
            }
        }
        
	if (isset ($installedModules["IPSCam"]))
		{
		IPSUtils_Include ("IPSCam.inc.php",     "IPSLibrary::app::modules::IPSCam");

		$camManager = new IPSCam_Manager();
		$config     = IPSCam_GetConfiguration();
	    echo "Folgende Kameras sind im Modul IPSCam vorhanden:\n";
		foreach ($config as $cam)
	   	    {
		    echo "   Kamera : ".$cam["Name"]." vom Typ ".$cam["Type"]."\n";
		    }
	    echo "Bearbeite lokale Kameras im Modul OperationCenter definiert:\n";
		if (isset ($installedModules["OperationCenter"]))
			{
			IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
			$OperationCenterConfig = OperationCenter_Configuration();
			echo "IPSCam und OperationCenter Modul installiert. \n";
			if (isset ($OperationCenterConfig['CAM']))
				{
				echo "Im OperationCenterConfig sind auch die CAM Variablen angelegt.\n";
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
					$OperationCenterScriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
					$OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
					$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$OperationCenterDataId);

					$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
					echo "   Bearbeite Kamera : ".$cam_name." Cam Category ID : ".$cam_categoryId."  Motion ID : ".$WebCam_MotionID."\n";;

    				$oid=$WebCam_MotionID;
    				$cam_name="IPCam_".$cam_name;
	  	      	    $variabletyp=IPS_GetVariable($oid);
					if ($variabletyp["VariableProfile"]!="")
					   {
						echo "      ".str_pad($cam_name,30)." = ".str_pad(GetValueFormatted($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
						}
					else
					   {
						echo "      ".str_pad($cam_name,30)." = ".str_pad(GetValue($oid),30)."  ".$oid."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")       \n";
						}
			
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

					   /* wenn keine Parameter nach IPSComponentSensor_Motion angegeben werden entfällt das Remote Logging. Andernfalls brauchen wir oben auskommentierte Routine */
						$messageHandler->RegisterEvent($oid,"OnChange",'IPSComponentSensor_Motion','IPSModuleSensor_Motion,1,2,3');
					   }
					}

				}  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
			}     /* isset OperationCenter */
		}     /* isset IPSCam */


	/****************************************************************************************************************
	 *
	 *                                      Switches
	 *
	 ****************************************************************************************************************/

    $componentHandling=new ComponentHandling();

	echo "\n";
    echo "\nAktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";    
	echo "***********************************************************************************************\n";
	echo "Switch Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic Switche werden registriert.\n";
	if (function_exists('HomematicList'))
		{
		//installComponentFull(HomematicList(),"STATE",'IPSComponentSensor_RHomematic','IPSModuleSwitch_IPSHeat,');				/* Switche */
        $struktur1=$componentHandling->installComponentFull(HomematicList(),["STATE","INHIBIT","!ERROR"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,',$commentField); 				/* Homematic Switche */
	    echo "***********************************************************************************************\n";
		$struktur2=$componentHandling->installComponentFull(HomematicList(),["STATE","SECTION","PROCESS"],'IPSComponentSwitch_RHomematic','IPSModuleSwitch_IPSHeat,',$commentField);			    /* HomemeaticIP Switche */       
        print_r($struktur1);            // Ausgabe RemoteAccess Variablen
        print_r($struktur2);
        }
	if (function_exists('FS20List'))
		{
	    echo "***********************************************************************************************\n";        
        $struktur3=$componentHandling->installComponentFull(FS20List(),"StatusVariable",'IPSComponentSwitch_RFS20','IPSModuleSwitch_IPSHeat,',$commentField);
        print_r($struktur3);
		}
	echo "***********************************************************************************************\n"; 	


	/****************************************************************************************************************
	 *
	 *                                      Temperature
	 *
	 ****************************************************************************************************************/
	echo "\n";
    echo "\nAktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";    
	echo "***********************************************************************************************\n";
	echo "Temperatur Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
    if (function_exists('deviceList'))
        {
        echo "Temperatur Sensoren von verschiedenen Geräten werden registriert.\n";
        $result = $componentHandling->installComponentFull(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "TEMPERATURE"],'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField,true);				/* Temperatursensoren und Homematic Thermostat */
        //print_r($result);
        }
    if (false) 
        {
        echo "Homematic Temperatur Sensoren werden registriert.\n";
        if (function_exists('HomematicList'))
            {
            $componentHandling->installComponentFull(HomematicList(),"TEMPERATURE",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);				/* Temperatursensoren und Homematic Thermostat */
            $componentHandling->installComponentFull(HomematicList(),"ACTUAL_TEMPERATURE",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);		/* HomematicIP Thermostat */
            } 
        echo "FHT Heizungssteuerung Geräte werden registriert.\n";
        if (function_exists('FHTList'))
            {
            $componentHandling->installComponentFull(FHTList(),"TemeratureVar",'IPSComponentSensor_Temperatur','IPSModuleSensor_Temperatur,',$commentField);
            } 	
        }

	/****************************************************************************************************************
	 *
	 *                                      Humidity
	 *
	 ****************************************************************************************************************/
	echo "\n";
    echo "\nAktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";    
	echo "***********************************************************************************************\n";
	echo "Humidity Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic Humidity Sensoren werden registriert.\n";
	if (function_exists('HomematicList'))
		{
		$componentHandling->installComponentFull(HomematicList(),"HUMIDITY",'IPSComponentSensor_Feuchtigkeit','IPSModuleSensor_Feuchtigkeit,',$commentField,true);          // true mit Debug
		} 			
		
	/****************************************************************************************************************
	 *
	 *                                      Heat Control Actuators
	 *
	 ****************************************************************************************************************/
	echo "\n";
    echo "\nAktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";    
	echo "***********************************************************************************************\n";
	echo "Heat Control Actuator Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic Heat Control Actuator werden registriert.\n";
	if (function_exists('HomematicList'))
		{
		$componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),"TYPE_ACTUATOR",'IPSComponentHeatControl_Homematic','IPSModuleHeatControl_All',$commentField);
		$componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),"TYPE_ACTUATOR",'IPSComponentHeatControl_HomematicIP','IPSModuleHeatControl_All',$commentField);
		} 			
	echo "\n";
	echo "FHT80b Heat Control Actuator werden registriert.\n";
	if (function_exists('FHTList'))
		{
		//installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All');
		$componentHandling->installComponentFull(FHTList(),"PositionVar",'IPSComponentHeatControl_FS20','IPSModuleHeatControl_All',$commentField);
		}

	echo "***********************************************************************************************\n";

	/****************************************************************************************************************
	 *
	 *                                      Heat Set (Thermostate, zB an der Wand)
	 *
	 ****************************************************************************************************************/
	echo "\n";
    echo "\nAktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";    // kein zweiter Parameter "s" sonst hrtime in nanoseconds instead of unix timestamp as float
	echo "***********************************************************************************************\n";
	echo "Heat Control Set Handler wird ausgeführt. Macht bereits RemoteAccess mit !\n";
	echo "\n";
	echo "Homematic Heat Set Werte werden aus den Thermostaten registriert.\n";
	if (function_exists('HomematicList'))
		{
		//installComponentFull(HomematicList(),array("SET_TEMPERATURE","WINDOW_OPEN_REPORTING"),'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All');
		//installComponentFull(HomematicList(),"TYPE_THERMOSTAT",'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All');
		$componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),"TYPE_THERMOSTAT",'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All',$commentField);
		$componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),"TYPE_THERMOSTAT",'IPSComponentHeatSet_HomematicIP','IPSModuleHeatSet_All',$commentField);

        $componentHandling->installComponentFull(selectProtocol("Funk",HomematicList()),["CONTROL_MODE","WINDOW_OPEN_REPORTING"],'IPSComponentHeatSet_Homematic','IPSModuleHeatSet_All',$commentField);
	    $componentHandling->installComponentFull(selectProtocol("IP",HomematicList()),["CONTROL_MODE","!VALVE_STATE"],'IPSComponentHeatSet_HomematicIP','IPSModuleHeatSet_All',$commentField);
		} 	
	if ( (function_exists('FHTList')) && (sizeof(FHTList())>0) )
		{
    	echo "\n";
	    echo "FHT80b Heat Set Werte aus den Thermostaten werden registriert.\n";		
        //installComponentFull(FHTList(),"TargetTempVar",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All');
		$componentHandling->installComponentFull(FHTList(),"TYPE_THERMOSTAT",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All',$commentField);
        
        $componentHandling->installComponentFull(FHTList(),"TargetModeVar",'IPSComponentHeatSet_FS20','IPSModuleHeatSet_All',$commentField);          
		}

	echo "***********************************************************************************************\n";
    
    }  // ende noinstall

	/****************************************************************************************************************
	 *
	 *                                      Functions
	 *
	 ****************************************************************************************************************/


echo "CustomCompenent Installation abgeschlossen. Optionen : ".($noinstall?"Keine Installation der Components":"Installation der Components")."  \n";
echo "\nAktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";         // kein zweiter Parameter sonst hrtime in nanoseconds instead of unix timestamp as float


?>