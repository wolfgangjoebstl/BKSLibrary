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

	/**@defgroup Autosteuerung::Autosteuerung_Installation
	 *
	 * Script um automatisch irgendetwas ein und auszuschalten
	 * Installationsroutine:
     *      Initialisiserung, Modul Handling Vorbereitung
     *      Webfronts Vorbereitung und Konfiguration aus ini Datei 
     *      Variablen Profile für lokale Darstellung anlegen, sind die selben wie bei Remote Access
     *      spezielle Sicherheitsfunktionen bearbeiten, nur wenn PowerLock eingesetzt wir
     *      Links für Webfront identifizieren
     *      Bearbeite AutosetSwitch
     *          Initialisierung der wichtigsten Variablen abhängig von den freigeschalteten Funktionen
     *      Programme für Schalter registrieren nach OID des Events, CreateEvent
     *      Timer Konfiguration
     *      Webfront Installation
     *
     *
     * Webfront Erstellung wurde bereits auf verschiedene Arten automatisisert
     * aktuell drei Varianten gleichzeitig in Betrieb, muss harmonisiert werden
     * es werden aktuell keine webfront scripts installiert und verwendet
     *
     *
	 *
	 * @file          Autosteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/

    $startexec=microtime(true);     /* Laufzeitmessung */
    
    $updateEvents=true;            // shall be true
    $installWebfront=false;          // extended formatting mit Autosteuerung_GetWebFrontConfiguration()
    $incomplete=false;              // alle benötigten Module sind vorhanden
    
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    
    IPSUtils_Include ('Autosteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Autosteuerung');
    IPSUtils_Include ("Autosteuerung_Class.inc.php","IPSLibrary::app::modules::Autosteuerung"); 
    IPSUtils_Include ("Autosteuerung_AlexaClass.inc.php","IPSLibrary::app::modules::Autosteuerung");

	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
		}

    $debug=false;
    //if ($_IPS['SENDER']=="Execute") $debug=true;            // Mehr Ausgaben produzieren wenn im Attended Mode

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

    if ($debug)
        {
        $ergebnis1=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
        $ergebnis2=$moduleManager->VersionHandler()->GetVersion('Autosteuerung');
        echo "\nIP Symcon Kernelversion     : ".IPS_GetKernelVersion();
        echo "\nIPS ModulManager Version    : ".$ergebnis1;
        echo "\nModul Autosteuerung Version : ".$ergebnis2."   Status : ".$moduleManager->VersionHandler()->GetModuleState()."\n";
        }

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	if ($debug) echo $inst_modules."\n";

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	if (isset ($installedModules["OperationCenter"])) 
        { 	
        if ($debug) echo "  Modul OperationCenter ist installiert.\n"; 
		IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
		IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');	
		$moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);                
  	    $CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');
        $categoryId_AutosteuerungAlexa    = IPS_GetObjectIdByName('Alexa',$CategoryIdDataOC);      
        } 
    else 
        { 
        echo "Modul OperationCenter ist NICHT installiert, incomplete Installation.\n"; 
        $categoryId_AutosteuerungAlexa    =        false;
        $incomplete=true;
        }

	if (isset ($installedModules["EvaluateHardware"])) 
        { 
        if ($debug) echo "  Modul EvaluateHardware ist installiert.\n"; 
        IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
        IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');           // sonst werden die Event Listen überschrieben
        IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
        }
    else 
        {
        echo "  Modul EvaluateHardware ist NICHT installiert, incomplete Installation.\n"; 
        $incomplete=true;
        }

    if (isset($installedModules["DetectMovement"]))
        {
        if ($debug) echo "  Modul DetectMovement ist installiert.\n"; 
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        }
    else 
        {
        echo "  Modul DetectMovement ist NICHT installiert, incomplete Installation.\n"; 
        $incomplete=true;
        }

    $ipsOps = new ipsOps();
    $wfcHandling =  new WfcHandling();
    $profileOps = new profileOps();         // local                Profilverwaltung

	$scriptIdWebfrontControl   = IPS_GetScriptIDByName('WebfrontControl', $CategoryIdApp);
	$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);
	$scriptIdHeatControl   = IPS_GetScriptIDByName('Autosteuerung_HeatControl', $CategoryIdApp);
	$scriptIdAlexaControl   = IPS_GetScriptIDByName('Autosteuerung_AlexaControl', $CategoryIdApp);
	
	$eventType='OnChange';
	$categoryId_Autosteuerung  = CreateCategory("Ansteuerung", $CategoryIdData, 10);            // Unterfunktionen wie Stromheizung, Anwesenheitsberechnung sind hier
	$categoryId_Status  = CreateCategory("Status", $CategoryIdData, 100);                 // ein paar Spiegelregister für die Statusberechnung, Debounce Funktion

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	
	$modulhandling = new ModuleHandling();
	$Alexa=$modulhandling->getInstances("Alexa");
	$countAlexa = sizeof($Alexa);                                       // später verwendet
    if ($debug)
        {
        echo "Es gibt insgesamt ".$countAlexa." Alexa Instanzen.\n";
        if ($countAlexa>0)
            {
            $config=IPS_GetConfiguration($modulhandling->getInstances("Alexa")[0]);
            echo "   ".$config."\n";
            }	
        }

    if (isset($installedModules["Stromheizung"])==true)
	    {
	    IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");
	    IPSUtils_Include ("IPSHeat_Constants.inc.php",      "IPSLibrary::app::modules::Stromheizung");
    	}
    else
	    {
    	// Confguration Property Definition
	    define ('IPSHEAT_WFCSPLITPANEL',		'WFCSplitPanel');
    	define ('IPSHEAT_WFCCATEGORY',			'WFCCategory');
	    define ('IPSHEAT_WFCGROUP',			'WFCGroup');
	    define ('IPSHEAT_WFCLINKS',			'WFCLinks');
	    }

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	if ($debug) echo "\nDefault WFC10_ConfigId fuer Autosteuerung, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
	$WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();

/*******************************
 *
 * Webfront Konfiguration aus ini Datei einlesen
 *
 ********************************/
 	
    // nue Art der Webfront ini Konfiguration einlesen
    $configWFront=$ipsOps->configWebfront($moduleManager,false);     // wenn true mit debug Funktion
    //print_r($configWFront);
    
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
    $Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);

	if ($WFC10_Enabled==true)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];		
        }
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];
        }    

/*----------------------------------------------------------------------------------------------------------------------------
 *
 * Variablen Profile für lokale Darstellung anlegen, sind die selben wie bei Remote Access
 *
 * Vorteil ist das ein vorhandenes Profil nicht mühsam über Remote angelegt werden muss also gleich hier richtig anlegen
 *
 *
 * ----------------------------------------------------------------------------------------------------------------------------*/

	if ($debug) echo "Darstellung der Variablenprofile im lokalem Bereich für Autosteuerungsfunktionen, wenn fehlt anlegen:\n";
	$profilname=array("AusEinAuto"=>"update","AusEin"=>"update","AusEin-Boolean"=>"update","NeinJa"=>"update","Null"=>"update","SchlafenAufwachenMunter"=>"update","AusEinAutoP1P2P3P4"=>"update",);
    $profileOps->synchronizeProfiles($profilname);    


/*----------------------------------------------------------------------------------------------------------------------------
 * spezielle Sicherheitsfunktionen bearbeiten
 * nur wenn PowerLock eingesetzt wird, danach suchen und ein paar kosmetische Tätigkeiten ansetzen
 */

	if ($debug) echo "Darstellung der Variablenprofile für PowerLock im lokalem Bereich, wenn fehlt anlegen:\n";
	$profilname=array("PowerLockBefehl"=>"update","PowerLockStatus"=>"update", );
    $profileOps->synchronizeProfiles($profilname,$debug);             //true für Debug

    $componentHandling = new ComponentHandling();
    $DeviceManager     = new DeviceManagement();
    $operate           = new AutosteuerungOperator();

    echo "Geräte mit getComponent suchen, geht jetzt mit HardwareList und DeviceList.\n";
    IPSUtils_Include ("EvaluateHardware_Devicelist.inc.php","IPSLibrary::config::modules::EvaluateHardware");
    $deviceList = deviceList();            // Configuratoren sind als Function deklariert, ist in EvaluateHardware_Devicelist.inc.php

    // Aktuator
    $resultKey=$componentHandling->getComponent($deviceList,["TYPECHAN" => "TYPE_POWERLOCK","REGISTER" => "KEYSTATE"],"Install",$debug);                        // true für Debug, bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben   
    $countPowerLock=(sizeof($resultKey));				
    $resulttext="Alle Tuerschloesser Aktuatoren ausgeben ($countPowerLock):\n";            
    $resulttext.=$DeviceManager->writeCheckStatus($resultKey);          
    echo $resulttext;
    //print_r($resultKey);
    foreach ($resultKey as $homematic=>$entry)
        {
        IPS_SetVariableCustomProfile($entry["COID"],"PowerLockBefehl");    
        }

    // Status
    $resultState=$componentHandling->getComponent($deviceList,["TYPECHAN" => "TYPE_POWERLOCK","REGISTER" => "LOCKSTATE"],"Install",$debug);                        // true für Debug, bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben   
    $countPowerLock+=(sizeof($resultState));				
    $resulttext="Alle Tuerschloesser Stati ausgeben ($countPowerLock):\n";            
    $resulttext.=$DeviceManager->writeCheckStatus($resultState);          
    echo $resulttext;
    //print_r($resultState);
    foreach ($resultState as $homematic=>$entry)
        {
        IPS_SetVariableCustomProfile($entry["COID"],"PowerLockStatus");    
        }

    if ($countPowerLock>0)
        {
        echo "Es wird ein PowerLock von Homematic verwendet. Die Darstellung erfolgt unter Alarmanlage/Tab Sicherheit:\n";

        }

/*----------------------------------------------------------------------------------------------------------------------------
 * SendWebhook finden und Events entsprechend geofencies anlegen
 * Ein registerEvent und ein Install machen, wegen Transparenz
 * sowie Floorplan_GetEventConfiguration() und Autosteuerung_GetEventConfiguration() in Autosteuerung_Class
 *
 */
    echo "Geofency Links erzeugen:\n";
    $geofencies=$operate->getGeofencyInformation(true);         // true für Debug, geofencies wird später noch verwendet

    $registerGeofency=new AutosteuerungConfigurationGeofency();            // $scriptIdAutosteuerung weglassen. damit werden keine Events erzeugt
    //print_R($geofencies);
    foreach ($geofencies as $phone=>$index)           // erstellen von Geofency_GetEventConfiguration
        {
        foreach ($index as $id=>$entry)         // beim ersten Mal anlegen eine Fehlermeldung dass es die function noch nicht gibt in Zeile 488
            {
            //registerAutoEvent($variableId, $eventType, $componentParams, $moduleParams)
            $registerGeofency->registerAutoEvent($entry["OID"],"OnChange","Place,".$entry["Name"],"");
            }
        }

/*----------------------------------------------------------------------------------------------------------------------------
 * Unterstützung für Anwesenheitserkennung und Visualisiserung
 * config automatisch erweitern für Anzeige eines Floorplans
 * check function as UC01 in HandleAlexaEvents
 */

    echo "TopologyPlusLinks erzeugen, Basis ist die unified Topology. Ableitung eines php files für die Darstellung eines Floorplans\n";
    $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
    $channelEventList    = $DetectDeviceHandler->Get_EventConfigurationAuto();              // alle Events

    $registerFloorplan=new AutosteuerungConfigurationFloorplan();            // $scriptIdAutosteuerung weglassen. damit werden keine Events erzeugt

    /* IPSDetectDeviceHandler_GetEventConfiguration die einzige Tabelle mit Event und Raumzuordnung, händisch !!!
     * seit Implementierung EvaluateHardware sind alle Informationen in EvaluateHardware_Configuration gespeichert: get_UnifiedTopology
     * mit Autosteuerung Install mitnehmen
     */

    $topology=get_UnifiedTopology();
    $topologyPlusLinks=$DetectDeviceHandler->mergeTopologyObjects($topology,$channelEventList,$debug);        // true,2 for Debug, 1 für Warnings  Verwendet ~ in den Ortsangaben, dadurch werden die Orte wieder eindeutig ohne den Pfad zu wissen

    /* beeinflussung des Ergebnisses in EvaluateHardware_Configuration
     */

    $auto=new Autosteuerung();
	$AutoSetSwitches = $auto->get_Autosteuerung_SetSwitches();      // sicherstellen dass PROFIL, NAME und TABNAME gesetzt sind
    //print_r($AutoSetSwitches);

    // eine eigene AutoSetSwitches Configuration erstellen
    $setSwitches = new AutosteuerungConfigurationSetSwitches();
    $configuration=$auto->get_Autosteuerung_SetSwitches();
    $setSwitches->StoreConfiguration($configuration);               // comment is extra
    $setSwitches->ChangeComment("look here");

    $register=new AutosteuerungHandler($scriptIdAutosteuerung);
	$setup = $register->get_Configuration();
	//print_r($setup);

    if (isset($setup["FloorPlan"]["PlaceToStart"])) $home = $setup["FloorPlan"]["PlaceToStart"];
    else $home="LBG70";
    echo "Erstellung Floorplan für diese lokale Topologie: $home\n";

    $result = $DetectDeviceHandler->evalTopology($home,false);          // verwendet topology ohne Erweiterungen, true für Anzeige der topologie
    $object=false;
    $topo="Bewegung";                       // bei TOPO gibt es einheitliche Namen
    $floorplan=array();
    foreach ($result as $index => $entry)
        {
        if (isset($entry["Index"]))
            {
            if (isset($topologyPlusLinks[$entry["Index"]]))
                {
                $data=$topologyPlusLinks[$entry["Index"]];
                if ($debug)
                    {
                    for ($i=0;$i<$entry["Hierarchy"];$i++) echo "   ";
                    echo str_pad($entry["Name"],80-($entry["Hierarchy"]*3))."| ";
                    if ((isset($data["OBJECT"]["Movement"])) && $object)
                        {
                        echo json_encode($data["OBJECT"]["Movement"]);
                        }
                    }
                if ((isset($data["TOPO"]["ROOM"])) && $topo)
                    {
                    if ($debug) echo json_encode($data["TOPO"]["ROOM"]);
                    foreach ($data["TOPO"]["ROOM"] as $index => $type)
                        {
                        if ($type==$topo) $floorplan[$entry["Name"]]=$index;
                        }
                    }
                if ($debug) echo "\n";
                }
            else echo "Warning, no Data \n";                // OBJECT wurde für den Raum nicht angelegt
            }
        else "Warning, no Index\n";
        }

    //print_R($floorplan);

    foreach ($floorplan as $room=>$index)           // erstellen von Floorplan_GetEventConfiguration
        {
        //registerAutoEvent($variableId, $eventType, $componentParams, $moduleParams)
        $registerFloorplan->registerAutoEvent($index,"ROOM","","");
        }

/*******************************
 *
 * Links für Webfront identifizieren
 *
 * Webfront Links werden für alle Autosteuerungs Default Funktionen erfasst. Es werden auch gleich die 
 * Default Variablen dazu angelegt
 *
 * Anwesenheitserkennung, Alarmanlage, GutenMorgenWecker, SilentMode, Ventilatorsteuerung, Stromheizung
 *
 * funktioniert für jeden beliebigen Vartiablennamen, zumindest ein/aus Schalter wird angelegt
 *
 * Folgende Gruppen können in Autosteuerung_SetSwitches() definiert werden:
 *
 *    Anwesenheitssimulation, Anwesenheitserkennung, Alarmanlage,  Ventilatorsteuerung, GutenMorgenWecker, SilentMode, Stromheizung, Alexa
 *
 * Für jede dieser Gruppen kann festgelegt werden ob es ein Eigenes Tab gibt oder die Informationen auf einer Seite zusammengefasst werden.
 *
 ********************************/

    /* verschiedene Loggingspeicher initialisieren, damit kein Fehler wenn nicht installiert wegen Konfiguration aber referenziert da im Code 
     *      Nachrichtenverlauf-Autosteuerung
     *      Nachrichtenverlauf-Wichtig
     *      Nachrichtenverlauf-AnwesenheitErkennung
     *      Nachrichtenverlauf-Sicherheit
     */
    $categoryId_NachrichtenAuto    = CreateCategory('Nachrichtenverlauf-Autosteuerung',   $CategoryIdData, 20);
	$inputAuto = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenAuto, 0, "",null,null,""  );   /* Nachrichtenzeilen werden automatisch von der Logging Klasse gebildet */
    $log_Autosteuerung=new Logging($setup["LogDirectory"]."Autosteuerung.csv",$inputAuto,IPS_GetName(0).";Autosteuerung;");

    $categoryId_NachrichtenHtml    = CreateCategory('Nachrichtenverlauf-Wichtig',   $CategoryIdData, 200); 
    $inputHtml = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenHtml, 0, "",null,null,""  );   /* Nachrichtenzeilen werden automatisch von der Logging Klasse gebildet */
    $log = new Logging("No-Output",$inputHtml,"", true);            // true für html

    $categoryId_NachrichtenAnwe    = CreateCategory('Nachrichtenverlauf-AnwesenheitErkennung',   $CategoryIdData, 20);
    $inputAnwe = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenAnwe, 0, "",null,null,""  );     /* Nachrichtenzeilen werden automatisch von der Logging Klasse gebildet */
    $log_Anwesenheitserkennung=new Logging($setup["LogDirectory"]."Anwesenheitserkennung.csv",$inputAnwe,IPS_GetName(0).";Anwesenheitserkennung;");

    $categoryId_NachrichtenSicherheit    = CreateCategory('Nachrichtenverlauf-Sicherheit',   $CategoryIdData, 20);
    $inputSicherheit = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenSicherheit, 0, "",null,null,""  );     /* Nachrichtenzeilen werden automatisch von der Logging Klasse gebildet */
    $log_AlarmSicherheit=new Logging($setup["LogDirectory"]."AlarmSicherheit.csv",$inputSicherheit,IPS_GetName(0).";Sicherheit;");

	$categoryId_Schaltbefehle = CreateCategory('Schaltbefehle-Anwesenheitssimulation',   $CategoryIdData, 20);
    $inputSchalt=CreateVariable("Schaltbefehle",3,$categoryId_Schaltbefehle, 0,'',null,'');
    //$categoryId_Wochenplan = CreateCategory('Wochenplan-Stromheizung',   $CategoryIdData, 20);
    //$inputWoche=IPS_GetVariableIDByName("Wochenplan",$categoryId_Wochenplan);							// nicht generieren, wird erst später von den Routinen erstellt
    $categoryId_Alexa = CreateCategory('Nachrichten-Alexa',   $CategoryIdData, 20);
    $inputAlexa=CreateVariable("Nachrichten",3,$categoryId_Alexa, 0,'',null,'');
	$categoryId_Control = CreateCategory('ReglerAktionen-Stromheizung',   $CategoryIdData, 20);
	$inputControl=CreateVariable("ReglerAktionen",3,$categoryId_Control, 0,'',null,'');

	$categoryId_Available = CreateCategory('Available',   $CategoryIdData, 200);
    $StatusAnwesenheitID = CreateVariable("StatusAnwesenheit",0, $categoryId_Available,0,"~Presence",null,null,"");
    $StatusAlarmID    = CreateVariable("StatusAlarm",0, $categoryId_Available,0,"~Presence",null,null,"");
    AC_SetLoggingStatus($archiveHandlerID,$StatusAnwesenheitID,true);
    AC_SetAggregationType($archiveHandlerID,$StatusAnwesenheitID,0);      /* normaler Wert */
    AC_SetLoggingStatus($archiveHandlerID,$StatusAlarmID,true);
    AC_SetAggregationType($archiveHandlerID,$StatusAlarmID,0);      /* normaler Wert */

    $log->LogMessage('Autosteuerung Installation aufgerufen');
    $log_Autosteuerung->LogNachrichten('Autosteuerung Installation aufgerufen');      

    $tabs=array();                          // neue Darstellung
	$webfront_links=array();
	foreach ($AutoSetSwitches as $nameAuto => $AutoSetSwitch)
		{
        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
        if (strtoupper($AutoSetSwitch["PROFIL"])=="NULL")        // leere Optionen als String anlegen, damit sie nicht eine falsche 0 anzeigen
            { 
            $AutosteuerungID = CreateVariableByName($categoryId_Autosteuerung,$AutoSetSwitch["NAME"], 3, "", false,  0, $scriptIdWebfrontControl);   /* 0 Boolean 1 Integer 2 Float 3 String */
            SetValue($AutosteuerungID,"");
            }
        else $AutosteuerungID = CreateVariableByName($categoryId_Autosteuerung, $AutoSetSwitch["NAME"], 1, $AutoSetSwitch["PROFIL"], false, 0, $scriptIdWebfrontControl );  /* 0 Boolean 1 Integer 2 Float 3 String */        
        echo "-------------------------------------------------------\n";
        echo "Bearbeite Autosetswitch : ".$AutoSetSwitch["NAME"]."  Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";

		$webfront_links[$AutosteuerungID]["TAB"]="Autosteuerung";
		$webfront_links[$AutosteuerungID]["OID_L"]=$AutosteuerungID;
        $webfront_links[$AutosteuerungID]["OID_R"]=$inputAuto;              // Default Nachrichtenspeicher

		/* Spezialfunktionen hier abarbeiten, default am Ende des Switches 
         * wenn OWNTAB in der Config den richtigen Namen hat, gibt es einen eigenen Tab, sonst alle Variablen gemeinsam geordnet als Gruppen in Autosteuerung 
         *      Anwesenheitserkennung       OWNTAB=>Erkennung
         *      Alarmanlage                 OWNTAB=>Sicherheit
         *      GutenMorgenWecker
         *      VentilatorSteuerung
         *      AnwesenheitsSimulation       OWNTAB->Schaltbefehle
         *      StromHeizung                 OWNTAB=>Wochenplan
         *      GartenSteuerung             OWNTAB=>Gartensteuerung
         *      Alexa                       OWNTAB=>Alexa
         *      Private
         *      Logging
         *      Control
         *      MonitorMode
         *      SientMode
         *      Denon
         *
         */
    	switch (strtoupper($AutoSetSwitch["TABNAME"]))          // das ist der Key, standardisiserte Namen
			{
            case "FLOORPLAN":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Floorplan'));  

                /* Setup Standard Variables */
                echo "   Variablen für Floorplan in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";	

                $webFrontConfiguration = Autosteuerung_GetWebFrontConfiguration()["Administrator"];
                if (isset($webFrontConfiguration[$AutoSetSwitch["TABNAME"]])===false) echo "Achtung, ohne einen Eintrag in Autosteuerung_GetWebFrontConfiguration geht gar nichts.\n";

  				/* einfache Visualisierung der Bewegungswerte, testweise */
				$StatusFloorplanHtml   = CreateVariable("FloorplanView",   3 /*String*/,  $AutosteuerungID, 1010, '~HTMLBox');          // unter der anderen Variable, wegen der Ordnung

                $registerFloorplan->writePhpFile();
                
                $script=$registerFloorplan->writeScript();
                if ($file=$registerFloorplan->loadFloorplan())          // wenn es keinen floorplan gibt auch nicht weiter probieren
                    {
                    $floorplan = $registerFloorplan->modifyFloorplan($file);             // file wird in der Abfrage gesetzt
                    $html  ='';
                    $html .= '<div style="height: 1250px;">';
                    $html .= $floorplan;            // read-file better, try
                    $html .= '  <div id="statusinfofloorplan">status floorplan</div>';
                    $html .= '</div>';
                    $html .= $script;                       // entweder am Schluss oder mit onload parameter

                    SetValue($StatusFloorplanHtml, $html);
                    }
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste, mit wichtigsten Bewegungsmeldungen */
                    {  				
			    	$webfront_links[$AutosteuerungID]["OID_R"]=$inputHtml;											/* Darstellung rechts im Webfront, immer eine Nachrichtenliste, welche muss man hier entscheiden */				
                    }            
                break;    
			case "ANWESENHEITSERKENNUNG":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Erkennung'));            
				
                /* Setup Standard Variables */
                if ($debug) echo "   Variablen für Anwesenheitserkennung in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";			
                
                /* Status Anwesend */
				$StatusAnwesendID=CreateVariable("StatusAnwesend",0, $AutosteuerungID,0,"~Presence",null,null,"");
				$StatusAnwesendZuletztID=CreateVariable("StatusAnwesendZuletzt",0, $AutosteuerungID,0,"~Presence",null,null,"");
				IPS_SetHidden($StatusAnwesendZuletztID,true);
				$register->registerAutoEvent($StatusAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

                /* DetectMovement Register für Anwesend Erkennung auch im Anwesenheitswerkennung Log anzeigen */
                $operate=new AutosteuerungOperator();
                $delayed=$operate->getConfigDelayed();          // Ausgabe this->logicAnwesend oder von setLogicAnwesend
                //print_R($delayed);
                foreach ($delayed as $eventID=>$entry)
                    {
                    $register->registerAutoEvent($eventID, $eventType, "Anwesenheit", "");
                    }

                /* Schalter Anwesend */
				if ($countAlexa>0) 	$StatusSchalterAnwesendID=CreateVariable("SchalterAnwesend",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdAlexaControl,null,"");	
				else $StatusSchalterAnwesendID=CreateVariable("SchalterAnwesend",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdWebfrontControl,null,"");			
				$register->registerAutoEvent($StatusSchalterAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusSchalterAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusSchalterAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);				
                
  				/* einfache Visualisierung der Bewegungswerte, testweise */
				$StatusTableMapHtml   = CreateVariable("StatusTableView",   3 /*String*/,  $AutosteuerungID, 1010, '~HTMLBox');

                $operate->setGeofencyAddressesToArchive($geofencies,true);  // true für Debug
                $operate->linkGeofencyAddresses($geofencies, $AutosteuerungID,true);

                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {
    				$webfront_links[$AutosteuerungID]["OID_R"]=$inputAnwe;				
                    /* mehr Informationen anzeigen, wenn wir einen eigenen Tab haben. */
                    }
				break;
			case "ALARMANLAGE":
				echo "   Variablen für Alarmanlage in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Sicherheit'));  

				$StatusAnwesendID=CreateVariable("StatusAlarmanlage",0, $AutosteuerungID,0,"~Presence",null,null,"");
				$StatusAnwesendZuletztID=CreateVariable("StatusAlarmanlageZuletzt",0, $AutosteuerungID,0,"~Presence",null,null,"");
				IPS_SetHidden($StatusAnwesendZuletztID,true);
				$register->registerAutoEvent($StatusAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				
				if ($countAlexa>0) 	$StatusSchalterAnwesendID=CreateVariable("SchalterAlarmanlage",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdAlexaControl,null,"");	
				else $StatusSchalterAnwesendID=CreateVariable("SchalterAlarmanlage",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdWebfrontControl,null,"");			
				$register->registerAutoEvent($StatusSchalterAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusSchalterAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusSchalterAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

				$alarm=new AutosteuerungAlarmanlage();
                if ($debug) echo " --> AutosteuerungAlarmanlage erfolgreich aufgerufen.\n";

                /* PowerLock Variables */
                if ($countPowerLock>0)
                    {
                    // CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')				
				    $SliderLockID=CreateVariable("LockBuilding",1, $AutosteuerungID,100,"~Intensity.100",$scriptIdWebfrontControl,null,"");			


                    $order=200;
                    foreach ($resultKey as $homematic=>$entry)
                        {
                        CreateLinkByDestination("PowerlockKey-".IPS_GetName($entry["OID"]), $entry["COID"],    $AutosteuerungID, $order);
                        $order+=10;
                        }
                    $order=200;
                    foreach ($resultState as $homematic=>$entry)
                        {
                        CreateLinkByDestination("PowerlockState".IPS_GetName($entry["OID"]), $entry["COID"],    $AutosteuerungID, $order);
                        $order+=10;
                        }
                    }

                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {
                    /* mehr Informationen anzeigen, wenn wir einen eigenen Tab haben. */
                    echo "Eigener Tab für die Alarmanlage mit Name Sicherheit:\n";
    				$webfront_links[$AutosteuerungID]["OID_R"]=$inputSicherheit;	
                    print_r($webfront_links[$AutosteuerungID]);			
                    }

                break;										
			case "GUTENMORGENWECKER":
				echo "   Variablen für GutenMorgenWecker in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";		
	   			// CreateVariable($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')					
				$WeckerID = CreateVariable("Wecker", 1, $AutosteuerungID, 0, "SchlafenAufwachenMunter",null,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
				$register->registerAutoEvent($WeckerID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$WeckerID,true);
				AC_SetAggregationType($archiveHandlerID,$WeckerID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
								
				$Wochenplan_ID = @IPS_GetEventIDByName("WeckerKalender", $WeckerID);
 				if ($Wochenplan_ID === false)
					{
					/* Wochenplan muss entweder ueber einer Variable oder über einem Script angeordnet sein.
					 *   wenn über Variable, dann gibt es ACtive Scripts im Action Table zum Eintragen 
					 *   wenn über einem Script muss IPS_Target ausgewertet werden. -> flexibler ...
					 */
					$Wochenplan_ID = IPS_CreateEvent(2);                  //Wochenplan Ereignis
					IPS_SetEventScheduleGroup($Wochenplan_ID, 0, 1); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 1, 2); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 2, 4); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 3, 8); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 4, 16); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 5, 32); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 6, 64); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)

			    	IPS_SetEventScheduleAction($Wochenplan_ID, 0, "Schlafen",   8048584, "SetValue(".(string)$WeckerID.",0)");
			    	IPS_SetEventScheduleAction($Wochenplan_ID, 1, "Aufwachen", 16750848, "SetValue(".(string)$WeckerID.",1)");
			    	IPS_SetEventScheduleAction($Wochenplan_ID, 2, "Munter",    32750848, "SetValue(".(string)$WeckerID.",2)");

					IPS_SetParent($Wochenplan_ID, $WeckerID);         //Ereignis zuordnen
					IPS_SetName($Wochenplan_ID,"WeckerKalender");
					IPS_SetEventActive($Wochenplan_ID, true);
					}
				else
					{
					/*gruendlich loeschen */
					for ($j=0;$j<8;$j++)
						{
						for ($i=0;$i<100;$i++)
							{
							@IPS_SetEventScheduleGroupPoint($Wochenplan_ID, $j /*Gruppe*/, $i /*Schaltpunkt*/, -1/*H*/, 0/*M*/, 0/*s*/, 0 /*Aktion*/);
							}
						}  
					//echo "Wochenplan Config ausgeben:\n";
					//$result_Wp=IPS_GetEvent($Wochenplan_ID);
					//print_r($result_Wp["ScheduleGroups"]);
					IPS_SetEventScheduleAction($Wochenplan_ID, 0, "Schlafen",   8048584, "SetValue(".(string)$WeckerID.",0);");
					IPS_SetEventScheduleAction($Wochenplan_ID, 1, "Aufwachen", 16750848, "SetValue(".(string)$WeckerID.",1);");
					IPS_SetEventScheduleAction($Wochenplan_ID, 2, "Munter",    32750848, "SetValue(".(string)$WeckerID.",2);");
					}
				if (true)
					{			
					$i=0;
				//Montag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 0 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 0/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 0 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 0/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 0 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 0/*M*/, 0/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 0 /*Gruppe*/, $i++ /*Schaltpunkt*/, 22/*H*/, 20/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Dienstag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 1 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 1/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 1 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 1/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 1 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 1/*M*/, 1/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 1 /*Gruppe*/, $i++ /*Schaltpunkt*/, 22/*H*/, 20/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Mittwoch
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 2/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 2/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 2/*M*/, 2/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i++ /*Schaltpunkt*/, 22/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Donnerstag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 3 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 3/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 3 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 3/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 3 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 3/*M*/, 3/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 3 /*Gruppe*/, $i++ /*Schaltpunkt*/, 22/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Freitag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 4 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 3/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 4 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 4/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 4 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 0/*M*/, 4/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 4 /*Gruppe*/, $i++ /*Schaltpunkt*/, 23/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Samstag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 5 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 3/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 5 /*Gruppe*/, $i++ /*Schaltpunkt*/, 8/*H*/, 30/*M*/, 5/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 5 /*Gruppe*/, $i++ /*Schaltpunkt*/, 10/*H*/, 0/*M*/, 5/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 5 /*Gruppe*/, $i++ /*Schaltpunkt*/, 23/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Sonntag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 6 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 3/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 6 /*Gruppe*/, $i++ /*Schaltpunkt*/, 8/*H*/, 30/*M*/, 6/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 6 /*Gruppe*/, $i++ /*Schaltpunkt*/, 12/*H*/, 0/*M*/, 6/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 6 /*Gruppe*/, $i++ /*Schaltpunkt*/, 23/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
					}
					
				if (false)		/* for test purposes only */
					{
					for ($i = 0; $i < 20; $i++) 
						{
						IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i*3 /*Schaltpunkt*/, 18/*H*/, $i*3/*M*/, 2/*s*/, 0 /*Aktion*/);	
						IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, ($i*3)+1 /*Schaltpunkt*/, 18/*H*/, ($i*3)+1/*M*/, 2/*s*/, 1 /*Aktion*/);
						IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, ($i*3)+2 /*Schaltpunkt*/, 18/*H*/, ($i*3)+2/*M*/, 2/*s*/, 2 /*Aktion*/);					
						}
					}	
						
				CreateLinkByDestination("WeckerKalender", $Wochenplan_ID, $AutosteuerungID,  10);
				$EventInfos = IPS_GetEvent($Wochenplan_ID);
				//print_r($EventInfos);
				break;
			case "VENTILATORSTEUERUNG":	
                echo "   Variablen für Ventilatorsteuerung in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";	
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')		
				$TemperaturID = CreateVariable("Temperatur", 2, $AutosteuerungID, 0, "",null,0,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */			
				$TemperaturZuletztID = CreateVariable("TemperaturZuletzt", 2, $AutosteuerungID, 0, "",null,0,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */			
				break;
			case "ANWESENHEITSSIMULATION":
                $AnwesenheitssimulationID = CreateVariableByName($categoryId_Autosteuerung,"Anwesenheitssimulation",1,"AusEinAuto",null,0,$scriptIdWebfrontControl); 
                $childs=IPS_GetChildrenIDs($AnwesenheitssimulationID);
                //print_r($childs);
                foreach ($childs as $child) IPS_SetHidden($child,true);                 // Alles verstecken                   
                $scenes=Autosteuerung_GetScenes(); 
                foreach($scenes as $scene)
                    {
                    if (isset($scene["TYPE"]))
                        {
                        if ( (isset($scene["STATUS"])) && (strtoupper($scene["STATUS"])=="DISABLED") )
                            {
                            /* Schalter ist deaktiviert, nichts tun */    
                            }
                        else
                            {                        
                            $statusID  = CreateVariable($scene["NAME"]."_Status",  1, $AnwesenheitssimulationID, 0, "AusEin",null,null,""  );
                            $counterID = CreateVariable($scene["NAME"]."_Counter", 1, $AnwesenheitssimulationID, 0, "",null,null,""  );
                            IPS_SetHidden($statusID,false);                 // nur die konfigurierten anzeigen
                            IPS_SetHidden($counterID,false);
                            AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
                            AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
                            IPS_ApplyChanges($archiveHandlerID);
                            }
                        }
                    }
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Schaltbefehle'));
				$simulation=new AutosteuerungAnwesenheitssimulation();
                if ($debug) echo " --> AutosteuerungAnwesenheitssimulation erfolgreich aufgerufen.\n";
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {                
				    $webfront_links[$AutosteuerungID]["OID_R"]=$inputSchalt;									
                    }
				break;	
			case "STROMHEIZUNG":
				/* Stromheizung macht ein Progamm für die nächsten 14 Tage. Funktioniert derzeit für die Stromheizung und den eigenen Temperaturregler
				 * Neue Funktion befüllt die Tabelle automatisch. Es gibt eine eigene Klasse für diese Funktion: AutosteuerungStromheizung
				 * der Heizungsregler funktioniert in der Klasse AutosteuerungRegler
				 */
				$kalender=new AutosteuerungStromheizung();
				echo "    Wochenkalender neu aufsetzen.\n";
                $categoryId_Wochenplan = CreateCategory('Wochenplan-Stromheizung',   $CategoryIdData, 20);                
				$kalender->SetupKalender(0,"~Switch");	/* Kalender neu aufsetzen, alle Werte werden geloescht, immer bei Neuinstallation */
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Wochenplan'));
				echo "    AutosteuerungRegler initialisieren.\n";
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
				$categoryId_Wochenplan = IPS_getCategoryIdByName('Wochenplan-Stromheizung',   $CategoryIdData);
				$inputWoche=IPS_GetVariableIDByName("Wochenplan",$categoryId_Wochenplan);				
				echo "    noch ein paar Variablen dazu in $inputWoche anlegen. Action Script $scriptIdHeatControl\n";
				$oid=CreateVariable("AutoFill",1,$inputWoche, 1000,'AusEinAutoP1P2P3P4',$scriptIdHeatControl,null,'');  // $Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon=''
				$descrID=CreateVariable("Beschreibung",3,$inputWoche, 1010,'',null,null,'');  // $Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon=''
				echo "    und eine neue Kategorie in $CategoryIdData.\n";	

                echo "    dann noch das Autofill Wochenprogramm, da die Variablenwerte nach einem Install neu angelegt werden:\n";
                $configuration = $kalender->get_Configuration();
                //print_r($configuration); 
                echo "      setAutoFill aufgerufen mit Defaultwert aus Konfiguration    ".$configuration["HeatControl"]["AutoFill"]." \n";        
                $kalender->setAutoFill($configuration["HeatControl"]["AutoFill"],false,true);           // false kein Shift wenn selber Wert wie bereits eingestellt, true for Debug
                echo "      aktuell eingestelltes Profil: ".GetValueIfFormatted($oid)." (".GetValue($oid).")\n";
                for ($i=-16;$i<0;$i++)
                    {
                    $value=$kalender->getStatusfromProfile(GetValue($oid),$i);                 
                    $kalender->ShiftforNextDay($value);                                     /* die Werte im Wochenplan durchschieben, neuer Wert ist der Parameter, die Links heissen aber immer noch gleich */
                    }
                $kalender->UpdateLinks($kalender->getWochenplanID());                   /* Update Links für Administrator Webfront */
                $kalender->UpdateLinks($kalender->getCategoryIdTab());		                            /* Upodate Links for Mobility Webfront */

                $tempId=CreateVariable("AutoTemp",2, $inputWoche, 1010, '~Temperature.HM',$scriptIdHeatControl,null,'');         // $Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon=''   2..Float
                SetValue($tempId,$configuration["HeatControl"]["setTemp"]);
				$categoryId_Schaltbefehle = CreateCategory('ReglerAktionen-Stromheizung',   $CategoryIdData, 20);
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')				
				$vid=CreateVariable("ReglerAktionen",3,$categoryId_Schaltbefehle, 0,'',null,'');
				$regler=new AutosteuerungRegler();
				echo "    webfrontlinks noch setzen und fertig.\n";													
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {                
	    			$webfront_links[$AutosteuerungID]["OID_R"]=$inputWoche;
                    }
				break;	
			case "GARTENSTEUERUNG":
				if ( isset( $installedModules["Gartensteuerung"] ) == true )
					{
                    $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Gartensteuerung'));                        
                    $moduleManagerGS = new IPSModuleManager('Gartensteuerung',$repository);
					$CategoryIdDataGS     = $moduleManagerGS->GetModuleCategoryID('data');
					$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdDataGS, 10);
					$SubCategory=IPS_GetChildrenIDs($categoryId_Gartensteuerung);
					foreach ($SubCategory as $SubCategoryId)
						{
						CreateLinkByDestination(IPS_GetName($SubCategoryId), $SubCategoryId,    $AutosteuerungID,  10);
						}					
					$categoryId_Register    		= CreateCategory('Gartensteuerung-Register',   $CategoryIdDataGS, 200);
					$SubCategory=IPS_GetChildrenIDs($categoryId_Register);
					foreach ($SubCategory as $SubCategoryId)
						{
						CreateLinkByDestination(IPS_GetName($SubCategoryId), $SubCategoryId,    $AutosteuerungID,  10);
						}					
                    if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                        {        
                        // Gartensteuerung, NachrichtenInputID finden, suche Nachricht und danach Input
                        $NachrichtenID = $ipsOps->searchIDbyName("Nachricht",$CategoryIdDataGS);
                        $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);                        
		    			$webfront_links[$AutosteuerungID]["OID_R"]=$NachrichtenInputID;
                        }
					echo "****Modul Gartensteuerung konfiguriert und erkannt.\n";
					}
                break;		
			case "ALEXA":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Alexa'));            
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')	
                if ($categoryId_AutosteuerungAlexa) 
                    {               			
				    $autosteuerungAlexa        = new AutosteuerungAlexa();	            // in Autosteuerung Class definiert, erweitert AutosteuerungFunktionen für das Logging
                    $AutosteuerungAlexaHandler = new AutosteuerungAlexaHandler();
                    $TableEventsAlexa_ID			= IPS_GetObjectIDByName("TableEvents",$categoryId_AutosteuerungAlexa);
                    $SchalterSortAlexa_ID			= IPS_GetObjectIDByName("Tabelle sortieren",$categoryId_AutosteuerungAlexa); 
                    CreateLinkByDestination("TableEvents", $TableEventsAlexa_ID,    $AutosteuerungID,  100);  
                    CreateLinkByDestination("Sort", $SchalterSortAlexa_ID,    $AutosteuerungID,  110);                                                          
                    }
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
                    $webfront_links[$AutosteuerungID]["OID_R"]=$inputAlexa;											/* Darstellung rechts im Webfront */				
                    }
                break;
			case "PRIVATE":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Private'));             
                // OWNTAB nicht unterstützt
				//$StatusPrivateID=CreateVariable("StatusPrivate",1, $AutosteuerungID,0,"",null,null,"");
				break;
			case "LOGGING":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Logging'));             
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
			    	$webfront_links[$AutosteuerungID]["OID_R"]=$inputHtml;											/* Darstellung rechts im Webfront, immer eine Nachrichtenliste */				
                    }
                //echo "Logging,$AutosteuerungID \n";  print_r($webfront_links[$AutosteuerungID]);
				break;                
			case "SILENTMODE":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'SilentMode'));             
                // OWNTAB nicht unterstützt
				$PushSoundID=CreateVariable("PushSound",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdWebfrontControl,null,"");
				break;
			case "CONTROL":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Control'));             
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
			    	$webfront_links[$AutosteuerungID]["OID_R"]=$inputControl;											/* Darstellung rechts im Webfront */				
                    }
				break;
			case "MONITORMODE":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'MonitorMode'));             

                /* Setup Standard Variables */
                echo "   Variablen für MonitorStatus Steuerung in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";			
				$StatusMonitorID=CreateVariable("StatusMonitor",0, $AutosteuerungID,0,"AusEin-Boolean",null,null,"");
				$StatusMonitorZuletztID=CreateVariable("StatusMonitorZuletzt",0, $AutosteuerungID,0,"AusEin-Boolean",null,null,"");
				IPS_SetHidden($StatusMonitorZuletztID,true);
				$register->registerAutoEvent($StatusMonitorID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusMonitorID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusMonitorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

				if ($countAlexa>0) 	$MonitorSchalterID=CreateVariable("SchalterMonitor",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdAlexaControl,null,"");	
				else $MonitorSchalterID=CreateVariable("SchalterMonitor",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdWebfrontControl,null,"");			
				$register->registerAutoEvent($MonitorSchalterID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$MonitorSchalterID,true);
				AC_SetAggregationType($archiveHandlerID,$MonitorSchalterID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);				

                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
			    	$webfront_links[$AutosteuerungID]["OID_R"]=$inputControl;											/* Darstellung rechts im Webfront */				
                    }
				break;
			case "DENON":
                $webFrontConfiguration = Autosteuerung_GetWebFrontConfiguration()["Administrator"];
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Denon'));  
                $tab = $webfront_links[$AutosteuerungID]["TAB"];
                $auswertungID  = $webfront_links[$AutosteuerungID]["OID_L"];
                $tabs[$tab]=array();
                $orderDenon=10;
                $tabs[$tab]["Auswertung"][$auswertungID]=array();
                $tabs[$tab]["Auswertung"][$auswertungID]["NAME"]=$AutoSetSwitch["NAME"];
                $tabs[$tab]["Auswertung"][$auswertungID]["ORDER"]=$orderDenon;
                $DenonHttp=$modulhandling->getInstances("DenonAVRHTTP");
                $DenonTelnet=$modulhandling->getInstances("DenonAVRTelnet");
                $Denon = array_merge($DenonHttp,$DenonTelnet);
                $countDenon = sizeof($Denon);
                echo "Es gibt insgesamt ".$countDenon." Denon Instanzen mit der Konfiguration.\n";
                //print_r($Denon);   
                foreach ($Denon as $oid)
                    {
                    $orderDenon +=10;
                    //CreateLinkByDestination(IPS_GetName($oid), $oid,    $AutosteuerungID,  20);                        
                    $tabs[$tab]["Auswertung"][$oid]=array();
                    $tabs[$tab]["Auswertung"][$oid]["NAME"]=IPS_GetName($oid);
                    $tabs[$tab]["Auswertung"][$oid]["ORDER"]=$orderDenon;
                    $childrens = IPS_GetChildrenIDs($oid); 
                    foreach ($childrens as $children)
                        {
                        $scriptActionID = IPS_GetVariable($children)["VariableAction"];
                        if ($scriptActionID) echo "   ($oid) ".str_pad(IPS_GetName($oid),30)."    ".IPS_GetName($children)."    ".IPS_GetName($scriptActionID)."\n";
                        //IPS_SetVariableCustomAction($children, $scriptIdWebfrontControl);
                        }                      
                    }  
                // defineWebfrontLink definiert TABNAME und TAB, anhand von OWNTAB wird entschieden ob in einem eigenen TAB dargestellt wird
                print_R($webfront_links[$AutosteuerungID]);
                           
                //if (isset($webFrontConfiguration[$AutoSetSwitch["TABNAME"]])===false)  echo "Achtung, ohne einen Eintrag in Autosteuerung_GetWebFrontConfiguration geht gar nichts.\n";
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
			    	$webfront_links[$AutosteuerungID]["OID_R"]=false;											/* Darstellung rechts im Webfront, immer eine Nachrichtenliste, hier keine Verfügbar */				
                    }
                if (isset($AutoSetSwitch["ORDER"])) $tabs[$tab]["ORDER"]=$AutoSetSwitch["ORDER"];
                $webfront_link = $webfront_links[$AutosteuerungID];  
                if ($webfront_link["OID_R"])
                    {
                    $nachrichtenID = $webfront_link["OID_R"];
                    $tabs[$tab]["Nachrichten"][$nachrichtenID]=array();
                    $tabs[$tab]["Nachrichten"][$nachrichtenID]["NAME"]=$AutoSetSwitch["NAME"];
                    $tabs[$tab]["Nachrichten"][$nachrichtenID]["ORDER"]=100;        
                    }
                break;                 
			default:
                // Check ob Konfiguration auch wirklich passt
                $webFrontConfiguration = Autosteuerung_GetWebFrontConfiguration()["Administrator"];
                if (isset($webFrontConfiguration[$AutoSetSwitch["TABNAME"]])===false) echo "Achtung, ohne einen Eintrag in Autosteuerung_GetWebFrontConfiguration geht gar nichts.\n";

                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,$AutoSetSwitch["NAME"]));             
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
			    	$webfront_links[$AutosteuerungID]["OID_R"]=$inputHtml;											/* Darstellung rechts im Webfront, immer eine Nachrichtenliste, welche mussman hier entscheiden */				
                    }            
				break;
			}
		$register->registerAutoEvent($AutosteuerungID, $eventType, "par1", "par2");         // class AutosteuerungHandler
		$webfront_links[$AutosteuerungID]["NAME"]=$AutoSetSwitch["NAME"];
		$webfront_links[$AutosteuerungID]["ADMINISTRATOR"]=$AutoSetSwitch["ADMINISTRATOR"];
		$webfront_links[$AutosteuerungID]["USER"]=$AutoSetSwitch["USER"];
		$webfront_links[$AutosteuerungID]["MOBILE"]=$AutoSetSwitch["MOBILE"];
		echo "Register Webfront Events : ".$AutoSetSwitch["NAME"]." with ID : ".$AutosteuerungID."\n";
		}
	echo "-------------------------------------------------------\n";		
	//print_r($AutoSetSwitches);

	/*
   $AutosteuerungID = CreateVariable("Ventilatorsteuerung", 1, $categoryId_Autosteuerung, 0, "AutosteuerungProfil",$scriptIdWebfrontControl,null,""  );  
	registerAutoEvent($AutosteuerungID, $eventType, "par1", "par2");

   $AnwesenheitssimulationID = CreateVariable("Anwesenheitssimulation", 1, $categoryId_Autosteuerung, 0, "AutosteuerungProfil",$scriptIdWebfrontControl,null,""  );  
	registerAutoEvent($AnwesenheitssimulationID, $eventType, "par1", "par2");
	*/
	
	/*****************************************************************
     * 
     * Programme für Schalter registrieren nach OID des Events, CreateEvent
	 *
	 * war schon einmal ausgeklammert, wird aber intuitiv von der Install Routine erwartet dass auch die Events registriert werden
	 *
	 */
    echo "\n";
    echo "====================================================================\n";
    echo "\n";
    if ($updateEvents)
        {
        echo "\nProgramme für Schalter registrieren nach OID des Events.  Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";

        $AutoConfiguration = Autosteuerung_GetEventConfiguration();
        foreach ($AutoConfiguration as $variableId=>$params)
            {
            if (IPS_ObjectExists($variableId))
                {
                echo "   Create Event für ID : ".$variableId."   ".IPS_GetName($variableId)." \n";
                $register->CreateEvent($variableId, $params[0], $scriptIdAutosteuerung);
                }
            else echo "   Delete Event für ID : ".$variableId."  does no loger exists !!! \n";
            }
        }


/******************************************************
 *
 * Timer Konfiguration
 *
 * Wecker programmierung ist bei GutenMorgen Funktion
 *
 ***********************************************************************/
		
	$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $scriptIdAutosteuerung);
	if ($tim1ID==false)
		{
		$tim1ID = IPS_CreateEvent(1);
		IPS_SetParent($tim1ID, $scriptIdAutosteuerung);
		IPS_SetName($tim1ID, "Aufruftimer");
		IPS_SetEventCyclic($tim1ID,0,0,0,0,2,5);		/* alle 5 Minuten */
		//IPS_SetEventCyclicTimeFrom($tim1ID,1,40,0);  /* immer um 02:20 */
		}
	IPS_SetEventActive($tim1ID,true);
		
	$tim3ID = @IPS_GetEventIDByName("Anwesendtimer", $scriptIdAutosteuerung);
	if ($tim3ID==false)
		{
		$tim3ID = IPS_CreateEvent(1);
		IPS_SetParent($tim3ID, $scriptIdAutosteuerung);
		IPS_SetName($tim3ID, "Anwesendtimer");
		IPS_SetEventCyclic($tim3ID,0,0,0,0,1,60);		/* alle 60 Sekunden , kein Datumstyp, 0, 0 ,0 2 minütlich/ 1 sekündlich */
		}
	IPS_SetEventActive($tim3ID,true);

    /* für die Heizungsregelung zusaetzlich einen reset einbauen */

	$tim2ID = @IPS_GetEventIDByName("KalenderTimer", $scriptIdHeatControl);
	if ($tim2ID==false)
		{
		$tim2ID = IPS_CreateEvent(1);
		IPS_SetParent($tim2ID, $scriptIdHeatControl);
		IPS_SetName($tim2ID, "KalenderTimer");
		IPS_SetEventCyclicTimeFrom($tim2ID,0,0,10);  /* immer um 00:00:10 */
		}
	IPS_SetEventActive($tim2ID,true);


/*----------------------------------------------------------------------------------------------------------------------------
 *
 * WebFront Installation
 * Vereinheitlichung der unterschiedlichen über die Vergangenheit angewachsenen Methoden
 *
 * ----------------------------------------------------------------------------------------------------------------------------*/

	echo "\nWebfront Konfiguration für Administrator User usw, geordnet nach data.OID  \n";
	//print_r($webfront_links);
    foreach ($webfront_links as $OID => $webfront_link)
		{
        $tab = $webfront_link["TAB"];
        $auswertungID  = $webfront_link["OID_L"];
        $nachrichtenID = $webfront_link["OID_R"];
        if (isset($tabs[$tab])===false)     
            {
            $tabs[$tab]=array();
            $tabs[$tab]["Auswertung"][$auswertungID]=array();
            $tabs[$tab]["Auswertung"][$auswertungID]["NAME"]=$webfront_link["NAME"];
            $tabs[$tab]["Auswertung"][$auswertungID]["ORDER"]=100;
            $tabs[$tab]["Nachrichten"][$nachrichtenID]=array();
            $tabs[$tab]["Nachrichten"][$nachrichtenID]["NAME"]=$webfront_link["NAME"];
            $tabs[$tab]["Nachrichten"][$nachrichtenID]["ORDER"]=100;        
            }
        else        // gibts schon, nicht mehr neu schreiben
            {
            //echo "    das war schon einmal da.\n";
            $auswertungID  = $webfront_link["OID_L"];
            $tabs[$tab]["Auswertung"][$auswertungID]=array();
            $tabs[$tab]["Auswertung"][$auswertungID]["NAME"]=$webfront_link["NAME"];
            $tabs[$tab]["Auswertung"][$auswertungID]["ORDER"]=100;            
            }
		}
	echo "\nWebfront Tabs anlegen:\n";
    $webfront_OrigLinks=$webfront_links;
	$webfront_links=$tabs;

	echo "Entsprechend den Webfront Links wird das Webfront automatisch aufgebaut:\n";
	echo "  Tab ".$configWFront["Administrator"]["TabPaneName"]."(".$configWFront["Administrator"]["TabPaneItem"].")\n";

    $webFrontConfiguration = Autosteuerung_GetWebFrontConfiguration()["Administrator"];         // Zusatzparametrierung
	foreach ($webfront_links as $Name => $webfront_group)
	   	{
		echo "    Subtab:    ".$Name."  ";
        if (isset($webFrontConfiguration[$Name])) 
            {
            echo json_encode($webFrontConfiguration[$Name]);
            $webfront_links[$Name]["CONFIG"]=$webFrontConfiguration[$Name];
            }
        echo "\n";
        }
    if ($debug==false) { 
        echo "****************Ausgabe Webfront Links               ";    
	    print_r($webfront_links); 
        }

    /*  if ( ($WFC10_Enabled) && (isset($configWFront["Administrator"])) )
            {
            $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID

            // Init for all Webfronts, necessary only once
            $categoryId_WebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
            $wfcHandling->CreateWFCItemCategory('Admin',   "roottp",   0, IPS_GetName(0).'-Admin', '', $categoryId_WebFront   , 'true' );
            $wfcHandling->UpdateVisibility("root",false);	            // gibts eh nicht, aber wenn verstecken			
            $wfcHandling->UpdateVisibility("dwd",false);                // gibts eh nicht, aber wenn verstecken

            $configWf=$configWFront["Administrator"];
            $configWf["Path"] .="Test";            // sonst loescht er immer die aktuellen Kategorien
            $wfcHandling->CreateWFCItemTabPane($configWf["TabPaneItem"], $configWf["TabPaneParent"],  $configWf["TabPaneOrder"], $configWf["TabPaneName"], $configWf["TabPaneIcon"]);
            $configWf["TabPaneParent"]=$configWf["TabPaneItem"];          // überschreiben wenn roottp, wir sind jetzt bereits eins drunter, Autosteuerungs Auto wurde bereits angelegt  
            $configWf["TabPaneItem"] = $configWf["TabPaneItem"].$configWf["TabItem"];  
            echo "\n\n===================================================================================================\n";            
            $wfcHandling->easySetupWebfront($configWf,$webfront_links, "Administrator", true);

            //$wfc=$wfcHandling->read_wfc(1);
            $wfc=$wfcHandling->read_wfcByInstance(false,1);                 // false interne Datanbank für Config nehmen
            foreach ($wfc as $index => $entry)                              // Index ist User, Administrator
                {
                echo "\n------$index:\n";
                $wfcHandling->print_wfc($wfc[$index]);
                }        
            }
        if ( ($WFC10User_Enabled) && (isset($configWFront["User"])) )
            {
            $configWF = $configWFront["User"];
            echo "\n\n===================================================================================================\n";
            $wfcHandling->easySetupWebfront($configWF,$webfront_links,"User");
            }           */

    // mit easysetup das Webfront erstellen
    if (isset($configWFront["Administrator"])) 
        {
        /* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben 
        *
        * typische Struktur, festgelegt im ini File:
        *
        * roottp/AutoTPA (Autosteuerung)/AutoTPADetails und /AutoTPADetails2
        *
        */
        
        $categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
        echo "====================================================================================\n";
        echo "Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";
        echo "Webportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in: ". $categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."\n";
        /* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
        CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);
        
        @WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
        @WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

        /*************************************/
        $configWF = $configWFront["Administrator"];
        /* Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen */
        echo "\nWebportal Administrator.Autosteuerung Datenstruktur installieren in: ".$configWF["Path"]." \n";
        $categoryId_WebFrontAdministrator         = CreateCategoryPath($configWF["Path"]);
        $ipsOps->emptyCategory($categoryId_WebFrontAdministrator);
        /* in der normalen Viz Darstellung verstecken */
        IPS_SetHidden($categoryId_WebFrontAdministrator, true); //Objekt verstecken

        /*************************************/
        
        /* TabPaneItem anlegen, etwas kompliziert geloest */
        $tabItem = $configWF["TabPaneItem"].$configWF["TabItem"];
        if ( exists_WFCItem($WFC10_ConfigId, $configWF["TabPaneItem"]) )
            {
            echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  löscht TabItem : ".$configWF["TabPaneItem"]."\n";
            DeleteWFCItems($WFC10_ConfigId, $configWF["TabPaneItem"]);
            }
        else
            {
            echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  TabItem : ".$configWF["TabPaneItem"]." nicht mehr vorhanden.\n";
            }	
        echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$configWF["TabPaneItem"]." in ".$configWF["TabPaneParent"]."\n";
        CreateWFCItemTabPane   ($WFC10_ConfigId, $configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);

        $configWF = $configWFront["Administrator"];
        $configWF["TabPaneParent"]=$configWF["TabPaneItem"];          // überschreiben wenn roottp, wir sind jetzt bereits eins drunter, Autosteuerungs Auto wurde bereits angelegt
        echo "\n\n===================================================================================================\n";
        $wfcHandling =  new WfcHandling($WFC10_ConfigId);
        $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            //true für Debug
        } 

    if (isset($configWFront["User"]))
        {
        $configWF = $configWFront["User"];
        echo "\n\n===================================================================================================\n";
        $wfcHandling =  new WfcHandling($WFC10User_ConfigId);
        $wfcHandling->easySetupWebfront($configWF,$webfront_links,"User");
        } 

        /*******************************************************
        *
        * es gibt keine automatisierte Webfront Erstellung mehr die anhand der Kategorien, die gerade angelegt worden sind erstellt wird 
        *
        * Es werden Kategorien AutoTPADetailsX mit laufender Nummer angelegt, beginnend bei 0, nicht aendern und in Config uebernehmen
        *
        *		'Alexa' => array(
        *			array(IPSHEAT_WFCSPLITPANEL, 'AutoTPADetails3',        'AutoTPA',        'Alexa','Eyes',1,40,0,0,'true'),
        *			array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails3_Left',  'AutoTPADetails3', null,null),
        *			array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails3_Right',  'AutoTPADetails3', null,null),
        *			),
        *
        * Aktuell werden neue Tabs angelegt anstelle bestehende verändert
        *
        *****************************************************************************/

    if ($installWebfront) 
        {
        $webFrontConfiguration = Autosteuerung_GetWebFrontConfiguration();
        /* if ($WFC10_Enabled && false)         // alte Art für Administrator Webfront Installation
            {
            if ( isset($webFrontConfiguration["Administrator"]) == true )
                {	// neue Art der Konfiguration 
                $webFrontConfig=$webFrontConfiguration["Administrator"];
                echo "Neue Webfront Konfiguration mit Unterscheidung Administrator, User und Mobile.\n";
                }
            else
                {	// alte Art der Konfiguration 
                echo "Alte Webfront Konfiguration nur für Administrator.\n";
                $webFrontConfig=$webFrontConfiguration;
                }
            // Default Path ist Visualization.WebFront.Administrator.Autosteuerung, die folgenden beiden Befehle wurden weiter oben bereits durchgeführt 
            //$categoryId_WebFrontAdministartor                = CreateCategoryPath($WFC10_Path);
            //CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem,  $WFC10_TabPaneParent, $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);

            $order = 10;
            foreach($webFrontConfig as $tabName=>$tabData) 
                {
                // tabname muss einer der oben kreierten Tabs sein, sonst Fehler 
                if (isset($tabs[$tabName])===false) { echo "\nFalsche Konfiguration in Autosteuerung. Tabname ".$tabName." stimmt nicht mit WebConfiguration ueberein.\n"; break; } 
                $tabCategoryId	= CreateCategory($tabName, $categoryId_WebFrontAdministrator, $order);			
                foreach($tabData as $WFCItem) {
                    $order = $order + 10;
                    switch($WFCItem[0]) 
                        {
                        case IPSHEAT_WFCSPLITPANEL:
                            CreateWFCItemSplitPane ($WFC10_ConfigId, $WFCItem[1], $WFCItem[2],$order,$WFCItem[3],$WFCItem[4],(int)$WFCItem[5],(int)$WFCItem[6],(int)$WFCItem[7],(int)$WFCItem[8],$WFCItem[9]);
                            break;
                        case IPSHEAT_WFCCATEGORY:
                            $categoryId	= CreateCategory($WFCItem[1], $tabCategoryId, $order);
                            CreateWFCItemCategory ($WFC10_ConfigId, $WFCItem[1], $WFCItem[2],$order, $WFCItem[3],$WFCItem[4], $categoryId, 'false');
                            break;
                        case IPSHEAT_WFCGROUP:
                        case IPSHEAT_WFCLINKS:
                            echo "  WFCLINKS : ".$WFCItem[2]."   ".$WFCItem[3]."\n";
                            $categoryId = IPS_GetCategoryIDByName($WFCItem[2], $tabCategoryId);
                            if ($WFCItem[0]==IPSHEAT_WFCGROUP) {
                                $categoryId = CreateDummyInstance ($WFCItem[1], $categoryId, $order);
                            }
                            $links      = explode(',', $WFCItem[3]);
                            $names      = $links;
                            if (array_key_exists(4, $WFCItem)) {
                                $names = explode(',', $WFCItem[4]);
                            }
                            foreach ($links as $idx=>$link) {
                                $order = $order + 1;
                                // CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="")
                                CreateLinkByDestination($names[$idx], getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $categoryId, $order);
                            }
                            break;
                        default:
                            trigger_error('Unknown WFCItem='.$WFCItem[0]);
                        }
                    }
                }
            }           // ende webfront admistrator install  

        // WebFront User Installation
        if ($WFC10User_Enabled && false)            // alte Art ein User Webfront zu erstellen
            {
            // Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen 
            // typische Struktur, festgelegt im ini File:
            // roottp/AutoTPU (Autosteuerung)/AutoTPUDetails
            $categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
            echo "====================================================================================\n";
            echo "Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";
            echo "Webportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
            CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   , 'true' );

            @WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
            @WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);

            // Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen 
            echo "\nWebportal User.Autosteuerung Datenstruktur installieren in: ".$WFC10User_Path." \n";
            $categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
            EmptyCategory($categoryId_WebFrontUser);
            echo "Kategorien erstellt, Main: ".$categoryId_WebFrontUser."\n";
            // in der normalen Viz Darstellung verstecken 
            IPS_SetHidden($categoryId_WebFrontUser, true); //Objekt verstecken		
            
            $tabItem = $WFC10User_TabPaneItem.$WFC10User_TabItem;
            if ( exists_WFCItem($WFC10User_ConfigId, $tabItem) )
                {
                echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10User_ConfigId).")  löscht TabItem : ".$tabItem."\n";
                DeleteWFCItems($WFC10User_ConfigId, $tabItem);
                }
            else
                {
                echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10User_ConfigId).")  TabItem : ".$tabItem." nicht mehr vorhanden.\n";
                }	
            echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$WFC10User_TabPaneItem." in ".$WFC10User_TabPaneParent."\n";
            CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
            
            // wenn nur ein Tab benötigt wird, ohne Teilung 
            CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem,   $WFC10User_TabPaneItem,    $WFC10User_TabOrder,     $WFC10User_TabName,     $WFC10User_TabIcon, $categoryId_WebFrontUser , 'false' );

            if (false)
                {
                CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem,               $WFC10User_TabPaneItem,    $WFC10User_TabOrder,     $WFC10User_TabName,     $WFC10User_TabIcon);
                $categoryId_WebFrontTab = $categoryId_WebFrontUser;
                CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   , 'false' );
                }

            foreach ($webfront_links as $OID => $webfront_link)
                {
                if ($webfront_link["USER"]==true)
                    {
                    echo "User CreateLinkByDestination : ".$webfront_link["NAME"]."   ".$OID."   ".$categoryId_WebFrontUser."\n";
                    CreateLinkByDestination($webfront_link["NAME"], $OID,    $categoryId_WebFrontUser,  10);
                    }
                }
            }

        // installiert entsprechend webfront_links -> OID -> array
        //if ($Mobile_Enabled)                // imer noch aktiv  */
        if (isset($configWFront["Mobile"]))
            {
            $configWF = $configWFront["Mobile"];
            if ( (isset($configWF["Path"])) && (isset($configWF["Enabled"])) )
                {
                $Mobile_Path = $configWF["Path"];
                $Mobile_Enabled = $configWF["Enabled"];
                if ($Mobile_Enabled)
                    {
                    $categoryId_MobileWebFront=CreateCategoryPath("Visualization.Mobile");
                    echo "====================================================================================\n";		
                    echo "Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";
                    print_r($configWF);
                    echo "Webportal Mobile Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in der Kategorie ". $categoryId_MobileWebFront." (".IPS_GetName($categoryId_MobileWebFront).")\n";
                    echo "Webportal Mobile.Autosteuerung Datenstruktur installieren in: ".$Mobile_Path." \n";
                    $categoryId_WebFrontMobile         = CreateCategoryPath($Mobile_Path);
                    EmptyCategory($categoryId_WebFrontMobile);
                    $i=0;
                    echo "Tabs abarbeiten : ".json_encode($tabs)."\n";
                    foreach ($tabs as $tabname => $tab)
                        {
                        echo "  Wir beginnen mit $tabname und Config ".json_encode($tab).": prüfen ob eine Mobile Konfig vorliegt.\n";
                        foreach ($webfront_OrigLinks as $OID => $webfront_link)
                            {
                            if (isset(($webfront_link["MOBILE"])))
                                {
                                if ( ($webfront_link["MOBILE"]==true) && ($webfront_link["TAB"]==$tabname) )
                                    {
                                    echo "         Mobile Konfig: $OID => ".json_encode($webfront_link)."\n";
                                    // nur eine Kategorie anlegen wenn Mobile aktiviert ist, es wird das alte Format, nicht das für easyWebFront, verwendet
                                    $categoryIdTab  = CreateCategory($tabname,  $categoryId_WebFrontMobile, 100);
                                    $i++;
                                    echo "         CreateLinkByDestination für $tabname: ".$webfront_link["NAME"]."   ".$OID."   ".$categoryIdTab."\n";
                                    CreateLinkByDestination($webfront_link["NAME"], $OID,    $categoryIdTab,  10);
                                    if ( isset( $webfront_link["OID_R"]) == true )
                                        {
                                        if (IPS_GetName($webfront_link["OID_R"])=="Wochenplan")
                                            {
                                            $nachrichteninput_Id=@IPS_GetObjectIDByName("Wochenplan-Stromheizung",$CategoryIdData);
                                            for ($i=1;$i<17;$i++)
                                                {
                                                $zeile = IPS_GetVariableIDbyName("Zeile".$i,$nachrichteninput_Id);
                                                echo "          Link ".date("D d",time()+(($i-1)*24*60*60))." aufbauen von ".$webfront_link["OID_R"]." in ".$categoryIdTab." Name Quelle: ".IPS_GetName($webfront_link["OID_R"])."\n";
                                                CreateLinkByDestination(date("D d",time()+(($i-1)*24*60*60)), $zeile, $categoryIdTab,  $i*10);
                                                }
                                            }
                                        else 
                                            {
                                            echo "            Link Nachrichtenverlauf aufbauen von ".$webfront_link["OID_R"]." in ".$categoryIdTab." Name Quelle: ".IPS_GetName($webfront_link["OID_R"])."\n";
                                            CreateLinkByDestination("Nachrichtenverlauf", $webfront_link["OID_R"],    $categoryIdTab,  20);
                                            }
                                        }
                                    if ( isset( $webfront_link["OID_L"]) == true )
                                        {
                                        echo "            Link AuswertungDaten aufbauen von ".$webfront_link["OID_L"]." in ".$categoryIdTab." Name Quelle: ".IPS_GetName($webfront_link["OID_L"])."\n";
                                        CreateLinkByDestination("AuswertungDaten", $webfront_link["OID_L"],    $categoryIdTab,  20);
                                        $childrens=IPS_GetChildrenIDs($webfront_link["OID_L"]);
                                        if ($childrens)
                                            {
                                            foreach ($childrens as $children)
                                                {
                                                $wfcHandling->CreateLinkWithDestination(IPS_GetName($children), $children,    $categoryIdTab,  30);
                                                }
                                            }
                                        }                                    
                                    echo ">>Kategorien fertig gestellt fuer ".$tabname." (".$categoryIdTab.") \n\n";                                    
                                    }
                                }
                            }
                        }	
                    }
                }
            }

        if ($Retro_Enabled)
            {
            echo "\nWebportal Retro installieren: \n";
            $categoryId_RetroWebFront         = CreateCategoryPath($Retro_Path);
            }


        // auch im IPSLight Modul herumpfuschen
        if (isset($installedModules["IPSLight"])==true)
            {

            /*----------------------------------------------------------------------------------------------------------------------------
            *
            * WebFront Installation für IPS Light wenn User konfiguriert
            *
            * ----------------------------------------------------------------------------------------------------------------------------*/

            echo "\n======================================================\n";
            echo "Webportal User für IPS Light installieren:  Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.";
            echo "\n======================================================\n\n";
            
            $moduleManagerLight = new IPSModuleManager('IPSLight');

            $moduleManagerLight->VersionHandler()->CheckModuleVersion('IPS','2.50');
            $moduleManagerLight->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.2');
            $moduleManagerLight->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');
            $moduleManagerLight->VersionHandler()->CheckModuleVersion('IPSComponent','2.50.1');
            $moduleManagerLight->VersionHandler()->CheckModuleVersion('IPSMessageHandler','2.50.1');

            IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");
            IPSUtils_Include ("IPSLight.inc.php",                "IPSLibrary::app::modules::IPSLight");
            IPSUtils_Include ("IPSLight_Constants.inc.php",      "IPSLibrary::app::modules::IPSLight");
            IPSUtils_Include ("IPSLight_Configuration.inc.php",  "IPSLibrary::config::modules::IPSLight");

            $CategoryIdData     = $moduleManagerLight->GetModuleCategoryID('data');
            $CategoryIdApp      = $moduleManagerLight->GetModuleCategoryID('app');

            $categoryIdSwitches = CreateCategory('Switches', $CategoryIdData, 10);
            $categoryIdGroups   = CreateCategory('Groups',   $CategoryIdData, 20);
            $categoryIdPrograms = CreateCategory('Programs', $CategoryIdData, 30);
            
            echo " Category IDs:\n";
            echo "    Data         :".$CategoryIdData."\n";
            echo "    App          :".$CategoryIdApp."\n";
            echo "    Switches     :".$categoryIdSwitches."\n";	
            echo "    Groups       :".$categoryIdGroups."\n";		
            echo "    Programs     :".$categoryIdPrograms."\n\n";	

            echo " Webfront Configurations:\n";		
            $WFC10User_Enabled    		= $moduleManagerLight->GetConfigValueDef('Enabled', 'WFC10User',false);
            if ($WFC10User_Enabled==true)
                {
                $WFC10User_ConfigId       	= $WebfrontConfigID["User"];		
                $WFC10User_Path    	 		= $moduleManagerLight->GetConfigValue('Path', 'WFC10User');
                $WFC10User_TabPaneItem    	= $moduleManagerLight->GetConfigValue('TabPaneItem', 'WFC10User');
                $WFC10User_TabPaneParent  	= $moduleManagerLight->GetConfigValue('TabPaneParent', 'WFC10User');
                $WFC10User_TabPaneName    	= $moduleManagerLight->GetConfigValue('TabPaneName', 'WFC10User');
                $WFC10User_TabPaneIcon    	= $moduleManagerLight->GetConfigValue('TabPaneIcon', 'WFC10User');
                $WFC10User_TabPaneOrder   	= $moduleManagerLight->GetConfigValueInt('TabPaneOrder', 'WFC10User');	
                echo "WF10 User \n";
                echo "  Path          : ".$WFC10User_Path."\n";
                echo "  ConfigID      : ".$WFC10User_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10User_ConfigId)).".".IPS_GetName($WFC10User_ConfigId).")\n";
                echo "  TabPaneItem   : ".$WFC10User_TabPaneItem."\n";
                echo "  TabPaneParent : ".$WFC10User_TabPaneParent."\n";
                echo "  TabPaneName   : ".$WFC10User_TabPaneName."\n";
                echo "  TabPaneIcon   : ".$WFC10User_TabPaneIcon."\n";
                echo "  TabPaneOrder  : ".$WFC10User_TabPaneOrder."\n";
            
                }	


            if ($WFC10User_Enabled) {
                $categoryId_WebFrontUser                = CreateCategoryPath($WFC10User_Path);
                /* in der normalen Viz Darstellung verstecken */
                IPS_SetHidden($categoryId_WebFrontUser, true); //Objekt verstecken	
                EmptyCategory($categoryId_WebFrontUser);
                echo "================= ende empty categories \ndelete ".$WFC10User_TabPaneItem."\n";	
                DeleteWFCItems($WFC10User_ConfigId, $WFC10User_TabPaneItem);
                echo "================= ende delete ".$WFC10User_TabPaneItem."\n";			
                echo " CreateWFCItemTabPane : ".$WFC10User_ConfigId. " ".$WFC10User_TabPaneItem. " ".$WFC10User_TabPaneParent. " ".$WFC10User_TabPaneOrder. " ".$WFC10User_TabPaneName. " ".$WFC10User_TabPaneIcon."\n";
                CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem,  $WFC10User_TabPaneParent, $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
                echo "================ende create Tabitem \n";
                $webFrontConfig = IPSLight_GetWebFrontUserConfiguration();
                $order = 10;
                foreach($webFrontConfig as $tabName=>$tabData) {
                    echo "================create ".$tabName."\n";
                    $tabCategoryId	= CreateCategory($tabName, $categoryId_WebFrontUser, $order);
                    foreach($tabData as $WFCItem) {
                        $order = $order + 10;
                        switch($WFCItem[0]) {
                            case IPSLIGHT_WFCSPLITPANEL:
                                CreateWFCItemSplitPane ($WFC10User_ConfigId, $WFCItem[1], $WFCItem[2]/*Parent*/,$order,$WFCItem[3],$WFCItem[4],(int)$WFCItem[5],(int)$WFCItem[6],(int)$WFCItem[7],(int)$WFCItem[8],$WFCItem[9]);
                                break;
                            case IPSLIGHT_WFCCATEGORY:
                                $categoryId	= CreateCategory($WFCItem[1], $tabCategoryId, $order);
                                CreateWFCItemCategory ($WFC10User_ConfigId, $WFCItem[1], $WFCItem[2]/*Parent*/,$order, $WFCItem[3]/*Name*/,$WFCItem[4]/*Icon*/, $categoryId, 'false');
                                break;
                            case IPSLIGHT_WFCGROUP:
                            case IPSLIGHT_WFCLINKS:
                                $categoryId = IPS_GetCategoryIDByName($WFCItem[2], $tabCategoryId);
                                if ($WFCItem[0]==IPSLIGHT_WFCGROUP) {
                                    $categoryId = CreateDummyInstance ($WFCItem[1], $categoryId, $order);
                                }
                                $links      = explode(',', $WFCItem[3]);
                                $names      = $links;
                                if (array_key_exists(4, $WFCItem)) {
                                    $names = explode(',', $WFCItem[4]);
                                }
                                foreach ($links as $idx=>$link) {
                                    $order = $order + 1;
                                    CreateLinkByDestination($names[$idx], getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $categoryId, $order);
                                }
                                break;
                            default:
                                trigger_error('Unknown WFCItem='.$WFCItem[0]);
                            }
                        }
                    }
                }
            }

        }        // install Webfront

	ReloadAllWebFronts(); /* es wurde das Autosteuerung Webfront komplett geloescht und neu aufgebaut, reload erforderlich */
		
	echo "================= ende webfront installation. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";

	
?>