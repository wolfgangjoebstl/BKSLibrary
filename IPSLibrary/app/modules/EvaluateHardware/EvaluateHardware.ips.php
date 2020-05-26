<?

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
 *
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
 * Die alte Geräte abhängige Devicelist ist jetzt in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Include.inc.php
 *      mit functions pro gerätetyp
 *	
 * wenn DetectMovement und die TopologyMappingLibrary instaliert ist, wird eine Topologie aufgebaut.
 *
 */

$ExecuteExecute=false;          // false: Execute routine gesperrt, es wird eh immer die Timer Routine aufgerufen. Ist das selbe !
$startexec=microtime(true);     // Zeitmessung, um lange Routinen zu erkennen

/******************************************************
 *
 *				INIT
 *
 *************************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

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
    $DeviceManager = new DeviceManagement();            // class aus der OperationCenter_Library
    //echo "  Aktuelle Fehlermeldung der der Homematic CCUs ausgeben:\n";      
    echo $DeviceManager->HomematicFehlermeldungen()."\n";
    //echo "  Homematic Serialnummern erfassen:\n";
    $serials=$DeviceManager->addHomematicSerialList_Typ();      // kein Debug
    }

//print_r($installedModules); 

    $ipsOps = new ipsOps();
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
    echo "\n";

    /* wenn keine Discovery verfügbar, dann den Configurator als Übergangslösung verwenden 
     * {44CAAF86-E8E0-F417-825D-6BFFF044CBF5} = AmazonEchoConfigurator
     * {DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8} = NetatmoWeatherConfig
     *
     */
    $input["ModuleID"] = "{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}";        // add EchoControl
    $input["ModuleName"] = "AmazonEchoConfigurator";
    $discovery[]=$input;
    $input["ModuleID"] = "{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}";        // add NetatmoWeather
    $input["ModuleName"] = "NetatmoWeatherConfig";
    $discovery[]=$input;

    /* wenn keine Konfiguratoren verfügbar dann die GUIDs der Instanzen eingeben
     *
     *
     */
    $input["ModuleID"] =   "{56800073-A809-4513-9618-1C593EE1240C}";            // FS20EX Instanzen
    $input["ModuleName"] = "FS20EX Instanzen";  
    $discovery[]=$input;
    $input["ModuleID"] =   "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}";            // FS20EX Instanzen
    $input["ModuleName"] = "FS20 Instanzen";          
    $discovery[]=$input;
    $input["ModuleID"] =    "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}";           // FHT devices Instanzen, kein Konfigurator, kein Discovery, haendische Installation
    $input["ModuleName"] = "FHT Instanzen";
    $discovery[]=$input;     

    echo "Erstellen der SocketList in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
    $socket=array();
    $socket = $topologyLibrary->get_SocketList($discovery);
    echo "Erstellen der GatewayList in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
    $gateway=array();
    $gateway = $topologyLibrary->get_GatewayList($discovery);
    echo "Erstellen der HardwareList in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
    $hardware = $topologyLibrary->get_HardwareList($discovery);
        //print_r($hardware);
        echo "   Anordnung nach Gerätetypen, Zusammenfassung:\n";
        foreach ($hardware as $type => $entries) echo str_pad("     $type : ",28).count($entries)."\n";
    echo "Erstellen der DeciveList in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
    $deviceList = $topologyLibrary->get_DeviceList($hardware, false);        // class is in EvaluateHardwareLibrary, true ist Debug, einschalten wenn >> Fehler ausgegeben werden
    echo "\n";

    $includefileDevices     = '<?'."\n";             // für die php Devices and Gateways, neu
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

    $includefileDevices .= 'function deviceList() { return ';
    $ipsOps->serializeArrayAsPhp($deviceList, $includefileDevices, 0, 0, false);          // true mit Debug
    $includefileDevices .= ';}'."\n\n";        

	$filename=IPS_GetKernelDir().'scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php';
	if (!file_put_contents($filename, $includefileDevices)) {
        throw new Exception('Create File '.$filename.' failed!');
    		}       

    echo "\n";
    $result=$modulhandling->printModules('TopologyMappingLibrary');
    if (empty($result)) echo "Modul/Bibliothek TopologyMappingLibrary noch nicht installiert.  \n";
    else 
        {
        if (isset($installedModules["DetectMovement"]))         // wenn von EvaluateHardware aufgerufen wird brauchen ich nicht zu überprüfen ob das Modul installiert ist, ich nehme gleich die internen arrays 
            {
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

            $topID=@IPS_GetObjectIDByName("Topology", 0 );
            if ($topID === false) 	$topID = CreateCategory("Topology",0,20);       // Kategorie anlegen wenn noch nicht da

            $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
            $DetectDeviceListHandler = new DetectDeviceListHandler();               // neuer Handler für die DeviceList, registriert die Devices in EvaluateHarwdare_Configuration

            $modulhandling->printInstances('TopologyDevice');
            $deviceInstances = $modulhandling->getInstances('TopologyDevice',"NAME");
            $modulhandling->printInstances('TopologyRoom');        
            $roomInstances = $modulhandling->getInstances('TopologyRoom',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            $modulhandling->printInstances('TopologyPlace');        
            $placeInstances = $modulhandling->getInstances('TopologyPlace',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            $modulhandling->printInstances('TopologyDeviceGroup');        
            $devicegroupInstances = $modulhandling->getInstances('TopologyDeviceGroup',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
          
            $topology            = $DetectDeviceHandler->Get_Topology();
            $channelEventList    = $DetectDeviceHandler->Get_EventConfigurationAuto();        
            $deviceEventList     = $DetectDeviceListHandler->Get_EventConfigurationAuto();        
            //print_r($topology);
            
            echo "CreateTopologyInstances wird aufgerufen:\n";
            echo "----------------------------------------\n";
            $topologyLibrary->createTopologyInstances($topology);
            echo "SortTopologyInstances wird aufgerufen:\n:";
            echo "----------------------------------------\n";
            $topologyLibrary->sortTopologyInstances($deviceList,$channelEventList,$deviceEventList);
            }           // end isset DetectMovement
        }               // end TopologyMappingLibrary

	/************************************
	 *
	 *  Homematic Sender und vorher HomematicSockets, FHT, FS20EX, FS20 einlesen
     *  diese Routine wird in Zukunft nur mehr dazu verwendet eine vollständige Neuinstallation zu unterstützen
	 *
	 ******************************************/

	//$includefile='<?'."\n".'$fileList = array('."\n";
	$includefile            = '<?'."\n";             // für die php IP Symcon Runtime
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
	$filename=IPS_GetKernelDir().'scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Include.inc.php';
	if (!file_put_contents($filename, $includefile)) {
        throw new Exception('Create File '.$filename.' failed!');
    		}
	//include $filename;
	//print_r($fileList);
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
 *
 *
 */

if (isset($installedModules["DetectMovement"]))
    {
    $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

    echo "\n";
	echo "=======================================================================\n";
	echo "Summenregister suchen und evaluieren :\n";
    echo "\n";
	echo "Bewegungsregister hereinholen:\n";								
	$DetectMovementHandler = new DetectMovementHandler();
	$groups=$DetectMovementHandler->ListGroups("Motion");       /* Type angeben damit mehrere Gruppen aufgelöst werden können */
	$events=$DetectMovementHandler->ListEvents();
	foreach ($events as $oid => $typ)
		{
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$moid=$DetectMovementHandler->getMirrorRegister($oid);
		if ($moid !== false) $DetectDeviceHandler->RegisterEvent($moid,'Topology','','Movement');		
		}
    print_r($groups); 
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectMovementHandler->InitGroup($group);
		echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
		$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Movement');		
		}	
	
    echo "\n";
	echo "Temperaturregister hereinholen, Spiegelregister auch registrieren:\n";								
    $DetectTemperatureHandler = new DetectTemperatureHandler();
	$eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();
    $eventTempConfig=$DetectTemperatureHandler->Get_EventConfigurationAuto();    	
	$groups=$DetectTemperatureHandler->ListGroups("Temperatur");        /* Type angeben damit mehrere Gruppen aufgelöst werden können */
	$events=$DetectTemperatureHandler->ListEvents();
	foreach ($events as $oid => $typ)
		{
		$moid=$DetectTemperatureHandler->getMirrorRegister($oid);
        if ($moid !== false) 
            {
            $mirror = IPS_GetName($moid);    
            $werte = @AC_GetLoggedValues($archiveHandlerID,$moid, time()-60*24*60*60, time(),1000);
            if ($werte === false) echo "Kein Logging für Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).")\n";
            if (isset($eventDeviceConfig[$oid])&& isset($eventTempConfig[$oid])) 
                {
                echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75).
                        json_encode($eventDeviceConfig[$oid])."  ".json_encode($eventTempConfig[$oid])." Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).") Archive Groesse : ".count($werte)."\n";
                /* check and get mirror register,. It is taken from config file. If config file is empty it is calculated from parent or other inputs and stored afterwards 
                    * Config function DetectDevice follows detecttemperaturehandler
                    */            
                $DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','Mirror->'.$mirror);     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben , Mirror Register als Teil der Konfig*/
                $DetectDeviceHandler->RegisterEvent($moid,'Topology','','Temperature',false, true);	        // par 3 config overwrite
                $DetectDeviceHandler->RegisterEvent($oid,'Topology','','Temperature,Mirror->'.$mirror,false, true);	        	/* par 3 config overwrite, Mirror Register als Zusatzinformation, nicht relevant */
                }
            else echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75)." ---->   not in config.\n";
            }
        else echo "Fehler, Mirror Register für ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75)." nicht gefunden.\n";
		}
	print_r($groups);
    //echo "Alle Gruppen durchgehen:\n";
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectTemperatureHandler->InitGroup($group);
		echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
		$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Temperature');		
		}	

    echo "\n";
	echo "Feuchtigkeitsregister hereinholen:\n";								
	$DetectHumidityHandler = new DetectHumidityHandler();
	$groups=$DetectHumidityHandler->ListGroups("Humidity");
	$events=$DetectHumidityHandler->ListEvents();
	foreach ($events as $oid => $typ)
		{
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$moid=$DetectHumidityHandler->getMirrorRegister($oid);
		if ($moid !== false) $DetectDeviceHandler->RegisterEvent($moid,'Topology','','Humidity');		
		}
    print_r($groups);         
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectHumidityHandler->InitGroup($group);
		echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
		$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Humidity');		
		}	

    echo "\n";
	echo "Stellwertsregister hereinholen:\n";								
	$DetectHeatControlHandler = new DetectHeatControlHandler();
	$groups=$DetectHeatControlHandler->ListGroups("HeatControl");
	$events=$DetectHeatControlHandler->ListEvents();
	foreach ($events as $oid => $typ)
		{
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$moid=$DetectHeatControlHandler->getMirrorRegister($oid);
		if ($moid !== false) $DetectDeviceHandler->RegisterEvent($moid,'Topology','','HeatControl');		
		}
    print_r($groups);    
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectHeatControlHandler->InitGroup($group);
		echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
		$DetectDeviceHandler->RegisterEvent($soid,'Topology','','HeatControl');		
		}	
																																													
    echo "\n";
    echo "=======================================================================\n";
	echo "Jetzt noch einmal den ganzen DetectDevice Event table sortieren, damit Raumeintraege schneller gehen :\n";
    $configuration=$DetectDeviceHandler->Get_EventConfigurationAuto();
    echo "    Nachdem die Config ausgelesen wurde, die Events sortieren.\n";
    $configurationNew=$DetectDeviceHandler->sortEventList($configuration);
    echo "    Und wieder in der Config abspeichern.\n";
    $DetectDeviceHandler->StoreEventConfiguration($configurationNew);
    } /* ende if isset DetectMovement */

echo "\n";
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
		echo "\nStromheizung ist installiert. Configuration auslesen.\n";
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
	echo "es geht weiter mit der Timer Routine\n";
	} /* ende if execute */


echo "\n";
echo "Gesamtlaufzeit ".(time()-$startexec)." Sekunden.\n";





?>