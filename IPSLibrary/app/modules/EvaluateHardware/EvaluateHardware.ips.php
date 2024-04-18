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

/* EvaluateHardware
 * evalualiert vorhandene Informationen und beschreibt die beiden Dateiein vollstaendig neu:
 *      EvaluateHardware_Devicelist.inc.php
 *      EvaluateHardware_Include.inc.php
 * Herausfinden welche Hardware verbaut ist und in IPSComponent und IPSHomematic bekannt machen
 * Define Files und Array function notwendig
 *
 * wird regelmaessig taeglich um 1:10 aufgerufen. macht nicht nur ein Inventory der gesamten verbauten Hardware sondern versucht auch die Darstellung als Topologie
 *
 * Verwendet wenn installiert auch die Module OperationCenter und DetectMovement
 *
 * Erstellt folgende Dateien an diesen Orten:
 *
 * Geräteunabhängige decivelist in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php
 *      function socketInstanzen()
 *      function gatewayInstanzen()
 *      function deviceList()  mit Input von function hardwareList()
 *
 * Die DeviceList wird von MySQL abgelöst. Bleibt aber als Fixpunkt bestehen. Devicelist wird von $topologyLibrary->get_DeviceList($hardware) erstellt.
 * die TopologyLibrary ist in EvaluateHardware_Library angelegt.
 *
 * Die alte Geräte abhängige Devicelist ist jetzt in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Include.inc.php
 *      mit functions pro gerätetyp
 *	
 * wenn DetectMovement und die TopologyMappingLibrary instaliert ist, wird eine Topologie aufgebaut.
 *
 */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(500);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme

    $ExecuteExecute=false;          // false: Execute routine gesperrt, es wird eh immer die Timer Routine aufgerufen. Ist das selbe !
    $startexec=microtime(true);     // Zeitmessung, um lange Routinen zu erkennen

/******************************************************
 *
 *				INIT
 *
 *************************************************************/

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
    IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');    
    IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

    IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager))
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
        }
    $installedModules = $moduleManager->GetInstalledModules();
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');

    $statusDeviceID                 = IPS_GetObjectIDByName("StatusDevice", $CategoryIdData);
    $statusEvaluateHardwareID       = IPS_GetObjectIDByName("StatusEvaluateHardware", $CategoryIdData);
    $logEvaluateHardwareID          = IPS_GetObjectIDByName("LogEvaluateHardware", $CategoryIdData);

    $ipsOps = new ipsOps();

    $fullDir = IPS_GetKernelDir()."scripts\\IPSLibrary\\config\\modules\\EvaluateHardware\\";
    $fullDir = $dosOps->correctDirName($fullDir,false);          //true für Debug
    $result = $dosOps->fileIntegrity($fullDir,'EvaluateHardware_Configuration.inc.php');
    if ($result === false) 
        {
        echo "File integrity of EvaluateHardware_Configuration.inc.php is NOT approved.\n";
        }
    else 
        {
        echo "File integrity of EvaluateHardware_Configuration.inc.php is approved.\n";
        IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
        }

    if (isset($installedModules["DetectMovement"]))
        {
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
        $DetectDeviceListHandler = new DetectDeviceListHandler();               // neuer Handler für die DeviceList, registriert die Devices in EvaluateHarwdare_Configuration
        }

        echo "\n";
        echo "Kernel Version (Revision) ist : ".IPS_GetKernelVersion()." (".IPS_GetKernelRevision().")\n";
        echo "Kernel Datum ist : ".date("D d.m.Y H:i:s",IPS_GetKernelDate())."\n";
        echo "Kernel Startzeit ist : ".date("D d.m.Y H:i:s",IPS_GetKernelStartTime())."\n";
        echo "Kernel Dir seit IPS 5.3. getrennt abgelegt : ".IPS_GetKernelDir()."\n";
        echo "Kernel Install Dir ist auf : ".IPS_GetKernelDirEx()."\n";
        echo "\n";

    /* DeviceManger muss immer installiert werden, wird in Timer als auch RunScript und Execute verwendet */

    if (isset($installedModules["OperationCenter"])) 
        {
        IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter'); 
        echo "OperationCenter ist installiert.\n";
        $DeviceManager = new DeviceManagement_Homematic();            // class aus der OperationCenter_Library
        //echo "  Aktuelle Fehlermeldung der der Homematic CCUs ausgeben:\n";      
        $homematicErrors = $DeviceManager->HomematicFehlermeldungen();
        echo "$homematicErrors\n";

        $arrHM_Errors = $DeviceManager->HomematicFehlermeldungen(true);         // true Ausgabe als MariaDB freundliches Array
        echo "Aktuelle Homematic Fehlermeldungen, insgesamt ".sizeof($arrHM_Errors).":\n";
        //print_r($arrHM_Errors);
        $arrHM_ErrorsDetailed = $DeviceManager->HomematicFehlermeldungen("Array");    
        //print_r($arrHM_ErrorsDetailed);
        $html=$DeviceManager->showHomematicFehlermeldungen($arrHM_ErrorsDetailed);
        SetValue($statusEvaluateHardwareID,$html);

        /* für eine OID die DeviceID herausfinden. es gibt wie bei AuditTrail einen Eintrag, mit EventId, Datum/Zeitstempel, NameOfIndex, IndexId, Event Description, EventShort 
         *
         */
        $verzeichnis=IPS_GetKernelDir()."scripts\\IPSLibrary\\config\\modules\\EvaluateHardware\\";
        $verzeichnis = $dosOps->correctDirName($verzeichnis,false);          //true für Debug
        $filename=$verzeichnis.'EvaluateHardware_DeviceErrorLog.inc.php';  
        $storedError_Log=$DeviceManager->updateHomematicErrorLog($filename,$arrHM_Errors,false);        //true für Debug
        //print_R($storedError_Log);
        krsort($storedError_Log);
        echo "Ausgabe showHomematicFehlermeldungenLog : \n";
        $html = $DeviceManager->showHomematicFehlermeldungenLog($storedError_Log,true);             //true für Debug
        $hwStatus = $DeviceManager->HardwareStatus("array",true);           // Ausgabe als Array, true für Debug
        $output = $DeviceManager->showHardwareStatus($hwStatus,["Reach"=>false,]);           // Ausgabe als html
        SetValue($statusDeviceID,$output);

        echo "  Homematic Serialnummern erfassen:\n";
        $serials=$DeviceManager->addHomematicSerialList_Typ();      // kein Debug
        }

	$modulhandling = new ModuleHandling();	                	            // in AllgemeineDefinitionen, alles rund um Bibliotheken, Module und Librariestrue bedeutet mit Debug
    $topologyLibrary = new TopologyLibraryManagement();                     // in EvaluateHardware Library, neue Form des Topology Managements
    $evaluateHardware = new EvaluateHardware();

/******************************************************
 *
 *				TIMER Konfiguration
 *
 *************************************************************/

$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "Aufruftimer");
	IPS_SetEventCyclic($tim1ID,2,1,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,1,10,0);  /* immer um 01:10 */
	}
IPS_SetEventActive($tim1ID,true);

/******************************************************
 *
 *				TIMER Routine
 *
 * keine else mehr, immer ausführen, das heisst jeden Tag oder bei jedem AUfruf ein neues Inventory erstellen
 *
 * erstellt mehrere Informationen
 *      include File $includefile für die PhP Runtime in IP-Symcon
 *
 *      include File $includefileDevices für eine modernere Darstellung der Devices, gespeichert in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php
 *
 *
 *  $includefileDevices  -> verwendet TopologyLibrary
 *      Liste aller Gateways (Bridges)
 *      Liste aller Devices mit Instances, Channels und Actuators
 *
 *
 * im PHP Ip Symcon Runtime include File sind folgende Functionen, arrays:
 *      Liste der Homematic Sockets: function HomematicInstanzen()
 *          enthält die Konfiguration der CCU als json encode
 *      Liste der FHT Geräte: function FHTList() 
 *      Liste der F20EX Geräte: function FS20EXList()
 *      Liste der FS20 Geräte: function FS20List()
 *      Liste der Homematic Geräte/Kanäle:  HomematicList()
 *      Liste der Homematic Konfiguration:  getHomematicConfiguration()
 *          Beispiel "Badezimmer-Taster:3" => array("MEQ1084617",3,HM_PROTOCOL_BIDCOSRF,HM_TYPE_BUTTON),
 *
 * parallel werden folgende Informationen gesammelt
 *      Liste der Homematic Geräte mit Konfiguration: function getHomematicConfiguration()
 *      Liste der Homematic Geräte nach Seriennummern
 *
 * wenn $installedModules auch "DetectMovement" enthält:
 *      werden alle Geräte in der EvaluateHardware_Configuration der Tabelle registriert
 *      zwei Tabellen:
 *          get_Topology() für die einfache Darstellung der Topologie mit NameOrt und Parent
 *          IPSDetectDeviceHandler_GetEventConfiguration() für die Funktion mit Objekt 1,2,3 (Standardroutine)
 *              Topology, NameOrt, Funktion : NameOrt kommt aus der obigen Topologie Tabelle, Funktion ist eine Gruppe wie Licht, Wärme, Feuchtigkeit
 *
 * mit getHomematicConfiguration() werden die Homematic Geräte/Kanäle verschiedenen Typen zugeordnet:
 *      TYPE_BUTTON, TYPE_CONTACT, TYPE_ACTUATOR, TYPE_MOTION, TYPE_METER_TEMPERATURE, TYPE_SWITCH, TYPE_METER_POWER, TYPE_THERMOSTAT, TYPE_DIMMER, 
 *
 *************************************************************/

    if (true)           // keine else mehr, immer ausführen, das heisst jeden Tag oder bei jedem Aufruf ein neues Inventory erstellen
        {

        echo "\n";
        echo "==================================================\n";
        echo "Vom Timer gestartet, include File erstellen.\n";
        
        $summary=array();		/* eine Zusammenfassung nach Typen erstellen */
        
        /************************************
        *
        *  Wenn vorhanden Hardware Sockets auflisten, dann kommen die Geräte dran
        *  damit kann die Konfiguration des entsprechenden Gateways wieder hergestellt werden
        *
        ******************************************/

        echo "\nAlle installierten Discovery Instances mit zugehörigem Modul und Library:\n";
        $discovery = $modulhandling->getDiscovery();
        $modulhandling->addNonDiscovery($discovery);    // und zusätzliche noch nicht als Discovery bekannten Module hinzufügen
        echo "\n";

        

        echo "Erstellen der SocketList (I/O Instanzen) in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
        $socket=array();
        $socket = $topologyLibrary->get_SocketList($discovery);
        echo "Erstellen der GatewayList (Configurator Instanzen) in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
        $gateway=array();
        $gateway = $topologyLibrary->get_GatewayList($discovery);
        echo "Erstellen der HardwareList in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
        $hardware = $topologyLibrary->get_HardwareList($discovery);
            //print_r($hardware);
            echo "   Anordnung nach Gerätetypen, Zusammenfassung:\n";
            foreach ($hardware as $type => $entries) echo str_pad("     $type : ",28).count($entries)."\n";
        echo "Erstellen der DeciveList in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
        $configDeviceList=array();
        $configDeviceList["uniqueNames"]="Create";
        $deviceList = $topologyLibrary->get_DeviceList($hardware,$configDeviceList, false);        // class is in EvaluateHardwareLibrary, true ist Debug, einschalten wenn >> Fehler ausgegeben werden
        echo "\n";

        $includefileDevices     = '<?php'."\n";             // für die php Devices and Gateways, neu
        $includefileDevices     .= '/* This file has been generated automatically by EvaluateHardware on '.date("d.m.Y H:i:s").".\n"; 
        $includefileDevices     .= " *  \n";
        $includefileDevices     .= " * Please do not edit, file will be overwritten on a regular base.     \n";
        $includefileDevices     .= " *  \n";
        $includefileDevices     .= " */    \n\n";
        $includefileDevices .= "function socketInstanzen() { return ";
        $ipsOps->serializeArrayAsPhp($socket, $includefileDevices);        // gateway array in das include File schreiben
        $includefileDevices .= ';}'."\n\n"; 

        $includefileDevices .= "function gatewayInstanzen() { return ";
        $ipsOps->serializeArrayAsPhp($gateway, $includefileDevices);        // gateway array in das include File schreiben
        $includefileDevices .= ';}'."\n\n"; 

        echo "\n";
        $result=$modulhandling->getModules('TopologyMappingLibrary');
        if (empty($result)) echo "Modul/Bibliothek TopologyMappingLibrary noch nicht installiert.  \n";
        else 
            {
            if (isset($installedModules["DetectMovement"]))         // wenn von EvaluateHardware aufgerufen wird brauchen ich nicht zu überprüfen ob das Modul installiert ist, ich nehme gleich die internen arrays 
                {
                //echo "TopologyMapping beginnt jetzt:\n";
                IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

                $topID=@IPS_GetObjectIDByName("Topology", 0 );
                if ($topID === false) 	$topID = CreateCategory("Topology",0,20);       // Kategorie anlegen wenn noch nicht da

                $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
                $DetectDeviceListHandler = new DetectDeviceListHandler();               // neuer Handler für die DeviceList, registriert die Devices in EvaluateHarwdare_Configuration

                /* wird nicht benötigt, nur zur Orientierung
                //$modulhandling->printInstances('TopologyDevice');
                $deviceInstances = $modulhandling->getInstances('TopologyDevice',"NAME");
                //$modulhandling->printInstances('TopologyRoom');        
                $roomInstances = $modulhandling->getInstances('TopologyRoom',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
                //$modulhandling->printInstances('TopologyPlace');        
                $placeInstances = $modulhandling->getInstances('TopologyPlace',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
                //$modulhandling->printInstances('TopologyDeviceGroup');        
                $devicegroupInstances = $modulhandling->getInstances('TopologyDeviceGroup',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
                */

                // Alternative Kategorienstruktur in Topology, die Topology Instanzen dort einsortieren
                $topConfig=array();
                $topId = @IPS_GetCategoryIDByName("Topology", 0);
                if ($topId)
                    {
                    echo "----------------------------------------DeviceList mit Informationen zur Topologie anreichern\n";
                    $debug=false;
                    $topConfig["ID"]=$topId;
                    $topConfig["Use"]=["Place","Room","Device"];        // keine Kategorie für DeviceGroup erstellen
                    echo "TopologyID gefunden : $topId, eine Topologie mit Kategorien erstellen.\n";
                    $DetectDeviceHandler->create_Topology($topConfig, $debug);            // true für init, true für Debug, bei init löscht sich die ganze Kategorie und baut sie neu auf, macht auch schon _construct
                    $topology=$DetectDeviceHandler->Get_Topology();
                    $channelEventList    = $DetectDeviceHandler->Get_EventConfigurationAuto();              // alle Events
                    $deviceEventList     = $DetectDeviceListHandler->Get_EventConfigurationAuto();          // alle Geräte
                    
                    echo "CreateTopologyInstances wird aufgerufen, je nach Konfig Place, Room und DeviceGroup einordnen:\n";
                    $topinstconfig = array();
                    $topinstconfig["Sort"]="Kategorie";
                    $topinstconfig["Use"]["Room"]=true;
                    $topinstconfig["Use"]["Place"]=true;
                    $topinstconfig["Use"]["DeviceGroup"]=true;
                    //$topinstconfig["Use"]["Device"]=true;                                       // Topology Device nicht erstellen
                    $topologyLibrary->createTopologyInstances($topology,$topinstconfig);           // wenn so konfiguriert :  Topologie TopologyDevice, TopologyRoom, TopologyPlace, TopologyDeviceGroup in Kategorie Topologie erstellen
                    echo "----------------------------------------\n";
                    echo "SortTopologyInstances wird aufgerufen um die einzelnen Geräte in die Topologie einzusortieren:\n";
                    $topinstconfig["Use"]["Device"]="Actuators";                               // nur die Actuators hinzufügen
                    $debug=true;
                    $topologyLibrary->sortTopologyInstances($deviceList,$topology, $channelEventList,$deviceEventList,$topinstconfig,$debug);           // neu abgeänderte Routine in Arbeit
                    echo "----------------------------------------\n";
                    
                    // Teil von Install, trotzdem täglich durchführen
                    $topologyPlusLinks=$DetectDeviceHandler->mergeTopologyObjects($topology,$channelEventList,false);        // true,2 for Debug, 1 für Warnings  Verwendet ~ in den Ortsangaben, dadurch werden die Orte wieder eindeutig ohne den Pfad zu wissen
                    $topinstconfig["Show"]["Instances"]=true;
                    $topinstconfig["Show"]["LinkFromParent"]=true;
                    $DetectDeviceHandler->updateLinks($topologyPlusLinks,$topinstconfig,true);                  // In den Topology Category tree einsortieren, true debug                    
                    $DetectDeviceHandler->create_UnifiedTopologyConfigurationFile($topology,false);            //true für Debug, speichert topology mit neuen erweiterten Indexen in EvaluateHardware_Configuration ab
                    }
                }           // end isset DetectMovement
            }               // end TopologyMappingLibrary

        $includefileDevices .= 'function deviceList() { return ';
        $ipsOps->serializeArrayAsPhp($deviceList, $includefileDevices, 0, 0, false);          // true mit Debug
        $includefileDevices .= ';}'."\n\n";        
        
        $includefileDevices .= "\n".'?>';
        $verzeichnis=IPS_GetKernelDir().'scripts\IPSLibrary\config\modules\EvaluateHardware';
        $verzeichnis = $dosOps->correctDirName($verzeichnis,false);          //true für Debug
        $filename=$verzeichnis.'EvaluateHardware_Devicelist.inc.php';
        if (!file_put_contents($filename, $includefileDevices)) {
            throw new Exception('Create File '.$filename.' failed!');
                } 
                
        /************************************
        *
        *  Homematic Sender und vorher HomematicSockets, FHT, FS20EX, FS20 einlesen
        *  diese Routine wird in Zukunft nur mehr dazu verwendet eine vollständige Neuinstallation zu unterstützen
        *
        ******************************************/
        if ( (isset($installedModules["OperationCenter"])) )                // wenn nich nicht installiert gibt es kein Devicemanagement
            {
            //$includefile='<?php'."\n".'$fileList = array('."\n";
            $includefile            = '<?php'."\n";             // für die php IP Symcon Runtime
            $includefile            .= '/* This file has been generated automatically by EvaluateHardware on '.date("d.m.Y H:i:s").". */\n\n";
            $summary = array();
            $includefile            .= '/* These are the Homematic Sockets: */'."\n\n";
            $evaluateHardware->getHomeMaticSockets($includefile);            //Wenn vorhanden die Homematic Sockets auflisten
            $includefile            .= "\n".'/* These are the FHT Devices: */'."\n\n";
            $evaluateHardware->getFHTDevices($includefile,$summary);
            $includefile            .= "\n".'/* These are the FS20EX Devices: */'."\n\n";
            $evaluateHardware->getFS20EXDevices($includefile,$summary);
            $includefile            .= "\n".'/* These are the FS20 Devices: */'."\n\n";
            $evaluateHardware->getFS20Devices($includefile,$summary);
            $includefile            .= "\n".'/* These are the Homematic Devices: */'."\n\n";
            $homematicList = $evaluateHardware-> getHomematicInstances($includefile,$summary);

            $evaluateHardware-> getHomematicDevices($includefile);

            $includefile.="\n".'?>';	
            $verzeichnis=IPS_GetKernelDir().'scripts\IPSLibrary\config\modules\EvaluateHardware';
            $verzeichnis = $dosOps->correctDirName($verzeichnis,false);          //true für Debug
            $filename=$verzeichnis.'EvaluateHardware_Include.inc.php';        
            if (!file_put_contents($filename, $includefile)) {
                throw new Exception('Create File '.$filename.' failed!');
                    }
            //include $filename;
            //print_r($fileList);
            }
        } // ende else if execute

	echo "\n";
	echo "=======================================================================\n";
	echo "Zusammenfassung:\n\n";
	//print_r($summary);
    foreach ($summary as $type => $devices)
        {
        echo "   Type : ".$type."   (".count($devices).")\n";
        asort($devices);
        foreach ($devices as $device) echo "     ".$device."\n";
        }

    /* wenn DetectMovement installiert ist zusaetzlich zwei Konfigurationstabellen evaluieren
    * am Ende muss DetectDeviceHandler Configuration befüllt sein.
    *
    * Summen und Mirrorregister suchen und registrieren
    *
    * !!!! Mirrorregister sind noch nicht vereinheitlicht, wir benötigen die Auswertungsregister
    *
    *  $DetectContactHandler           Events und Groups
    *  $DetectMovementHandler          Events und Groups
    *  $DetectTemperatureHandler       etwas andere bearbeitung
    *  $DetectHumidityHandler
    *  $DetectHeatControlHandler
    *
    */

    if (isset($installedModules["DetectMovement"]))
        {
        $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        echo "\n";
        echo "Aktuelle Laufzeit ".(time()-$startexec)." Sekunden.\n";
        echo "=======================================================================\n";
        echo "DetectMovement installiert, Summen und Mirrorregister für Kontakt, Bewegung etc. suchen und registrieren :\n";
        echo "\n";
        echo "DetectContact Kontakt Register hereinholen:\n";								
        $DetectContactHandler = new DetectContactHandler();
        $groups=$DetectContactHandler->ListGroups("Motion");       /* Type angeben damit mehrere Gruppen aufgelöst werden können */
        $events=$DetectContactHandler->ListEvents();
        echo "----------------Liste der DetectContact Events durchgehen:\n";
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$DetectContactHandler->getMirrorRegister($oid);
            if ($moid !== false) 
                {
                $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Contact');		
                if ($result) echo "   *** register Event $moid: $typ\n";
                }
            }
        echo "----------------Liste der DetectContact Groups durchgehen:\n";
        //print_r($groups); 
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectContactHandler->InitGroup($group);
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            $result=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Contact');		
            if ($result) echo "   *** register Event $soid\n";
            }	

        echo "DetectMovement Bewegungsregister hereinholen:\n";								
        $DetectMovementHandler = new DetectMovementHandler();
        $groups=$DetectMovementHandler->ListGroups("Motion");       /* Type angeben damit mehrere Gruppen aufgelöst werden können */
        $events=$DetectMovementHandler->ListEvents();
        echo "----------------Liste der DetectMovement Events durchgehen:\n";
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$DetectMovementHandler->getMirrorRegister($oid);
            if ($moid !== false) 
                {
                $DetectDeviceHandler->RegisterEvent($moid,'Topology','','Movement');		
                if ($result) echo "   *** register Event $moid: $typ\n";
                }
            }
        echo "----------------Liste der DetectMovement Groups durchgehen:\n";        
        //print_r($groups); 
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectMovementHandler->InitGroup($group);
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            $result=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Movement');		
            if ($result) echo "   *** register Event $soid\n";
            }	
        
        echo "\n";
        echo "DetectMovement Temperaturregister aus der Configuration hereinholen, Spiegelregister auch registrieren:\n";								
        $DetectTemperatureHandler = new DetectTemperatureHandler();
        $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();
        $eventTempConfig=$DetectTemperatureHandler->Get_EventConfigurationAuto();    	
        $groups=$DetectTemperatureHandler->ListGroups("Temperatur");        /* Type angeben damit mehrere Gruppen aufgelöst werden können */
        $events=$DetectTemperatureHandler->ListEvents();
        echo "----------------Liste der DetectTemperature Events durchgehen:\n";    
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ";
            $moid=$DetectTemperatureHandler->getMirrorRegister($oid);
            if ($moid !== false) 
                {
                $mirror = IPS_GetName($moid);    
                $werte = @AC_GetLoggedValues($archiveHandlerID,$moid, time()-60*24*60*60, time(),1000);
                if ($werte === false) echo "Kein Logging für Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).")\n";
                if ( (isset($eventDeviceConfig[$oid])) && (isset($eventTempConfig[$oid])) )
                    {
                    if (IPS_ObjectExists($oid))
                        {    
                        echo str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75).
                                json_encode($eventDeviceConfig[$oid])."  ".json_encode($eventTempConfig[$oid])." Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).") Archive Groesse : ".count($werte)."\n";
                        /* check and get mirror register,. It is taken from config file. If config file is empty it is calculated from parent or other inputs and stored afterwards 
                            * Config function DetectDevice follows detecttemperaturehandler
                            */            
                        $DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','Mirror->'.$mirror);     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben , Mirror Register als Teil der Konfig*/
                        $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Temperature',false, true);	        // par 3 config overwrite
                        if ($result) echo "   *** register Event $moid\n";
                        $result=$DetectDeviceHandler->RegisterEvent($oid,'Topology','','Temperature,Mirror->'.$mirror,false, true);	        	/* par 3 config overwrite, Mirror Register als Zusatzinformation, nicht relevant */
                        if ($result) echo "   *** register Event $oid\n";
                        }
                    else echo "   -> ****Fehler, $oid nicht mehr vorhanden aber in config eingetragen.\n";
                    }
                else 
                    {
                    echo str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75)." ---->   not in config.";
                    if (isset($eventDeviceConfig[$oid])===false) echo "DetectDeviceHandler->Get_EventConfigurationAuto() ist false  ";
                    if (isset($eventTempConfig[$oid])===false) echo "DetectTemperatureHandler->Get_EventConfigurationAuto() ist false  ";
                    echo "\n";
                    $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Temperature');                     // zumindest einmal in den DeviceHandler übernehmen
                    if ($result) echo "   *** register Event $moid\n";
                    }
                }
            else echo "  -> ****Fehler, Mirror Register für ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75)." nicht gefunden.\n";
            }
        //print_r($groups);
        echo "----------------Liste der DetectTemperature Gruppen durchgehen:\n";
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectTemperatureHandler->InitGroup($group);
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            $result=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Temperature');		
            if ($result) echo "   *** register Event $soid\n";
            }	

        echo "\n";
        echo "DetectMovement Feuchtigkeitsregister hereinholen:\n";								
        $DetectHumidityHandler = new DetectHumidityHandler();
        $groups=$DetectHumidityHandler->ListGroups("Humidity");
        $events=$DetectHumidityHandler->ListEvents();
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$DetectHumidityHandler->getMirrorRegister($oid);
            if ($moid !== false) 
                {
                $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Humidity');
                if ($result) echo "   *** register Event $moid: $typ\n";
                }		
            }
        echo "----------------Liste der DetectHumidity Gruppen durchgehen:\n";
        //print_r($groups);         
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectHumidityHandler->InitGroup($group);
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            $result=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Humidity');		
            if ($result) echo "   *** register Event $soid\n";
            }	

        echo "\n";
        echo "DetectMovement Stellwertsregister hereinholen:\n";								
        $DetectHeatControlHandler = new DetectHeatControlHandler();
        $groups=$DetectHeatControlHandler->ListGroups("HeatControl");
        $events=$DetectHeatControlHandler->ListEvents();
        foreach ($events as $oid => $typ)
            {
            echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$DetectHeatControlHandler->getMirrorRegister($oid);
            if ($moid !== false)
                {
                $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','HeatControl');	
                if ($result) echo "   *** register Event $moid: $typ\n";
                }	
            }
        echo "----------------Liste der DetectHeatControl Gruppen durchgehen:\n";
        //print_r($groups);    
        foreach ($groups as $group => $entry)
            {
            $soid=$DetectHeatControlHandler->InitGroup($group);
            echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
            $result=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','HeatControl');		
            if ($result) echo str_pad($soid,6)."   *** register Event successful\n";
            }	
        echo "\n";

        if (isset($installedModules["Stromheizung"]))
            {            
            echo "----------------Liste der IPSHeat Gruppen durchgehen:\n";
            IPSUtils_Include ("IPSHeat.inc.php",  "IPSLibrary::app::modules::Stromheizung");
            $heatManager = new IPSHeat_Manager();

            $configGroups = IPSHeat_GetGroupConfiguration();
            //print_r($configGroups);
            $result = $heatManager->getConfigGroups($configGroups);
            //print_R($result);
            foreach ($result as $index => $entry)
                {
                // ID herausbekommen, wird fürs Registrieren benötigt
                if (isset($entry["Type"]))
                    {
                    $soid = $heatManager->GetGroupIdByName($index);
                    $resultEvent=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','IpsHeatGroup');		
                    if ($resultEvent) echo str_pad($soid,6)."   *** register Event successful\n";
                    }
                }
            echo "----------------Liste der IPSHeat Programme durchgehen:\n";                
            $configPrograms = IPSHeat_GetProgramConfiguration();
            //print_r($configPrograms);
            $result = $heatManager->getConfigPrograms($configPrograms);
            //print_R($result);
            foreach ($result as $index => $entry)
                {
                $soid = $heatManager->GetProgramIdByName($index);
                $resultEvent=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','IpsHeatProgram');		
                if ($resultEvent) echo str_pad($soid,6)."   *** register Event successful\n";                  
                }
            } 
            echo "----------------Liste der IPSHeat Switch durchgehen:\n";
            IPSUtils_Include ("IPSHeat.inc.php",  "IPSLibrary::app::modules::Stromheizung");
            $heatManager = new IPSHeat_Manager();

            $configSwitches = IPSHeat_GetHeatConfiguration();
            //print_r($configGroups);
            $result = $heatManager->getConfigSwitches($configSwitches,false);            // true für debug
            //print_R($result);
            foreach ($result as $index => $entry)
                {
                // ID herausbekommen, wird fürs Registrieren benötigt
                if (isset($entry["Type"]))
                    {
                    $soid = $heatManager->GetSwitchIdByName($index);                                            // Name ist nicht eindeutig
                    $resultEvent=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','IpsHeatSwitch');		
                    if ($resultEvent) echo str_pad($soid,6)."   *** register Event successful\n";
                    }
                }

        echo "=======================================================================\n";
        echo "Jetzt noch einmal den ganzen DetectDevice Event table sortieren, damit Raumeintraege schneller gehen :\n";
        $configuration=$DetectDeviceHandler->Get_EventConfigurationAuto();
        echo "    Nachdem die Config ausgelesen wurde, die Events sortieren.\n";
        //print_R($configuration);
        $configurationNew=$DetectDeviceHandler->sortEventList($configuration);
        echo "    Und wieder in der Config abspeichern.\n";
        $DetectDeviceHandler->StoreEventConfiguration($configurationNew);
        }   /* ende if isset DetectMovement */    echo "\n";
    echo "\n";

    /******************************************************
    *
    *				Aufruf von EXECUTE oder RUNSCRIPT
    *
    * soll nur einen Ueberblick ueber die gesammelten Daten geben eigentliche Erfassung kommt dann bei timer, diesen immer ausführen
    *
    *************************************************************/


if ( ( ($_IPS['SENDER']=="Execute") || ($_IPS['SENDER']=="RunScript") ) && $ExecuteExecute )
	{
	echo "Aufruf gestartet von : ".$_IPS['SENDER']."\n";
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");	
	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
	//print_r($installedModules);
	
	echo "\n================================================================================================\n";
	echo "Von der Konsole aus gestartet.\n";
	echo "\n================================================================================================\n";
	echo "Auflistung der angeschlossenen Geräte nach Seriennummern. Es gibt insgesamt ".sizeof($serials).".\n";		
	print_r($serials);
	
	/* IPS Light analysieren */
	if ( isset($installedModules["IPSLight"]) )
		{
		echo "\n=============================================================================\n";
		echo "IPSLight ist installiert. Configuration auslesen.\n";
		IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");		
		IPSUtils_Include ("IPSLight.inc.php",                "IPSLibrary::app::modules::IPSLight");		
		IPSUtils_Include ("IPSLight_Constants.inc.php",      "IPSLibrary::app::modules::IPSLight");		
		IPSUtils_Include ("IPSLight_Configuration.inc.php",  "IPSLibrary::config::modules::IPSLight");
		$IPSLightObjects=IPSLight_GetLightConfiguration();
		foreach ($IPSLightObjects as $name => $object)
			{
			$components=explode(",",$object[IPSLIGHT_COMPONENT]);
			echo "  ".str_pad($name,30)."  ".str_pad($object[IPSLIGHT_TYPE],10)."   ".$components[0]."    ";
			switch (strtoupper($components[0]))
				{
				case "IPSCOMPONENTSWITCH_HOMEMATIC":
					echo $components[1]."   ".IPS_GetName($components[1]);
					break;
				default:
					break;
				}
			echo "\n";	
			}
		}

	/* IPS Heat analysieren */
	if ( isset($installedModules["Stromheizung"]) )
		{
		echo "\nIPSHeat Stromheizung ist installiert. Configuration auslesen.\n";
		IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");		
		IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");		
		IPSUtils_Include ("IPSHeat_Constants.inc.php",      "IPSLibrary::app::modules::Stromheizung");		
		IPSUtils_Include ("Stromheizung_Configuration.inc.php",  "IPSLibrary::config::modules::Stromheizung");
		$IPSLightObjects=IPSHeat_GetHeatConfiguration();
		foreach ($IPSLightObjects as $name => $object)
			{
			$components=explode(",",$object[IPSHEAT_COMPONENT]);
			echo "  ".$name."  ".$object[IPSHEAT_TYPE]."   ".$components[0]."    ";
			switch (strtoupper($components[0]))
				{
				case "IPSCOMPONENTSWITCH_HOMEMATIC":
					echo $components[1]."   ".IPS_GetName($components[1]);
					break;
				default:
					break;
				}
			echo "\n";	
			}
		}

    echo "Auflistung aller Geraeteinstanzen:\n";
	$alleInstanzen = IPS_GetInstanceListByModuleType(3); // nur Geräte Instanzen auflisten
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		//echo IPS_GetName($instanz)." ".$instanz." \n";
        //echo IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		echo "  ".str_pad(IPS_GetName($instanz),40)." ".$instanz." ".str_pad($result['ModuleInfo']['ModuleName'],20)." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r(IPS_GetInstance($instanz));
		}

	echo "\n==================================================================\n";

    echo "\n";
    echo "Gesamtlaufzeit ".(time()-$startexec)." Sekunden.\n";

	} /* ende if execute */







?>