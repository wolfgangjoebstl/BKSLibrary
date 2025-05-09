<?php


/* EvaluateHardware_Library
 *
 * class 	
 *  TopologyLibraryManagement           zum erstellen der DeviceList
 *  ImproveDeviceDetection
 *  EvaluateHardware
 *
 *
 *
 */


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

/**********************************************************************************************
 *
 * class TopologyLibraryManagement mit folgenden Funktionen:
 *
 *      __construct
 *      updateInstanceLists
 *      getPlaceInstances
 *      getRoomInstances
 *      getDeviceInstances
 *      getDeviceGroupInstances
 *      get_SocketList
 *      get_GatewayList
 *      get_HardwareList                        erstellt eine Liste der Hardware basierend auf Instanzen die zu einem Konfigurator oder einer Instanz gehören
 *      get_DeviceList                          erstellt ein Inventory nach einem für alle Geräte gemeinsamen Datenmodell 
 *
 *      createTopologyInstances                 TOPD Instanzen erzeugen und im zweiten Schritt einsortieren
 *      createTopologyInstance
 *      getParentId
 *      initInstanceConfiguration
 *      sortTopologyInstances                   Topologie in DeviceList einsortieren
 *
 * Nutzt die eigene Library. Es gibt Instanzen mit unterschiedlichen Eigenschaften, Ort, Gerät, Gruppe
 * Die neue Tiles / Kachel Webdarstellung unterstützt diese Form der Darstellung.
 * Allerdings müssen für die Strukturierung weiterhin Kategorien verwendet werden und keine Ortsinstanzen
 *
 */

class TopologyLibraryManagement
    {
	
    var $topID;                 // OID der Topology Kategorie, wird automatisch angelegt, wenn nicht vorhanden
    var $modulhandling;         // andere classes die genutzt werden, einmal instanzieren
    var $deviceInstances,$roomInstances,$placeInstances,$devicegroupInstances;                       // Instanzenlisten, für schnelleren Zugriff, müssen regelmaessig upgedatet werden.
    var $debug, $createUniqueNames;
    var $installedModules;

    public function __construct($debug=false)
        {
        $this->debug=$debug;
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        if (!isset($moduleManager))
            {
            IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
            $moduleManager = new IPSModuleManager('OperationCenter',$repository);
            }
        $this->installedModules = $moduleManager->GetInstalledModules();

        $this->topID=@IPS_GetObjectIDByName("Topology", 0 );
        if ($this->topID === false) 	$this->topID = CreateCategory("Topology",0,20);       // Kategorie anlegen wenn noch nicht da 

        $this->modulhandling = new ModuleHandling(); 
        $this->updateInstanceLists();                       // liste der Instanzen updaten
        $this->createUniqueNames = false;               // keine uniqueNames, Index abhängig vom Namen
        }

    /* die Liste der Instanzen wird gechached und nicht automatisch upgedated.
     * Hier das Update machen
     *
     * getInstances TopologyDevice, TopologyRoom, TopologyPlace, TopologyDeviceGroup
     *
     */
    private function updateInstanceLists()
        {
        if ($this->debug) $this->modulhandling->printInstances('TopologyDevice');
        $this->deviceInstances = $this->modulhandling->getInstances('TopologyDevice',"NAME");
        if ($this->debug) $this->modulhandling->printInstances('TopologyRoom');        
        $this->roomInstances = $this->modulhandling->getInstances('TopologyRoom',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
        if ($this->debug) $this->modulhandling->printInstances('TopologyPlace');        
        $this->placeInstances = $this->modulhandling->getInstances('TopologyPlace',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
        if ($this->debug) $this->modulhandling->printInstances('TopologyDeviceGroup');        
        $this->devicegroupInstances = $this->modulhandling->getInstances('TopologyDeviceGroup',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
        }

    /* instances are indexed by Name
     * doubles are extended by __#
     */
    public function getPlaceInstances()
        {
        return($this->placeInstances);
        }

    public function getRoomInstances()
        {
        return($this->roomInstances);
        }

    public function getDeviceInstances()
        {
        return($this->deviceInstances);
        }

    public function getDeviceGroupInstances()
        {
        return($this->deviceGroupInstances);
        }

    /* TopologyLibraryManagement::get_SocketList
     * Liste aller montierten Sockets ausgeben 
     * Format ist gleich, Key ist der Hardwaretyp dann der Name der Instanz mit den Einträgen OID und CONFIG
     *
     */
    public function get_SocketList($discovery, $debug=false)
        {
        if ($debug) echo "get_SocketList aufgerufen:\n";
        $gateway=array();
        $hardwareTypeDetect = new Hardware();
        foreach ($discovery as $entry)
            {
            $hardwareType = $hardwareTypeDetect->getHardwareType($entry["ModuleID"]);
            if ($hardwareType != false) 
                {
                if ($debug) echo "    get_SocketList, bearbeite $hardwareType, new class Hardware$hardwareType getSocketID in Hardware_Library \n";
                $objectClassName = "Hardware".$hardwareType;
                $object = new $objectClassName(false,$debug);       // übernimmt Config und Debug, see class Hardware
                $socketID = $object->getSocketID();
                $validModule = @IPS_GetModule($socketID)["ModuleName"];
                if ($validModule != "")
                    {    
                    if ($debug) echo "        SocketID :   $socketID  in Module:  $validModule   \n";
                    $sockets=$this->modulhandling->getInstances($socketID);
                    foreach ($sockets as $socket)
                        {
                        //echo "           ".IPS_GetName($bridge)."\n";
                        $config = @IPS_GetConfiguration($socket);
                        if ($config !== false)
                            {
                            $gateway[$hardwareType][IPS_GetName($socket)]["OID"]=$socket;
                            $gateway[$hardwareType][IPS_GetName($socket)]["CONFIG"]=$config;
                            }
                        }
                    }
                elseif ($debug) echo "        SocketID    unbekannt, keine Socketliste anlegen.\n";
                }
            else echo $entry["ModuleName"]."\n";   // Fehlermeldung schon in getHardwareType, es fehlt nur am lf
            }
        return($gateway);
        }


    /* Liste aller montierten Gateways ausgeben 
     * Format ist gleich, Key ist der Hardwaretyp dann der Name der Instanz mit den Einträgen OID und CONFIG
     *
     */

    public function get_GatewayList($discovery, $debug=false)
        {
        $gateway=array();
        $hardwareTypeDetect = new Hardware();
        foreach ($discovery as $entry)
            {
            $hardwareType = $hardwareTypeDetect->getHardwareType($entry["ModuleID"]);
            if ($hardwareType != false) 
                {
                //echo "    $hardwareType \n";
                $objectClassName = "Hardware".$hardwareType;
                $object = new $objectClassName(); 
                $bridgeID = $object->getBridgeID();
                $validModule = @IPS_GetModule($bridgeID)["ModuleName"];
                if ($validModule != "")
                    {    
                    if ($debug) echo "        BridgeID    $bridgeID    $validModule\n";
                    $bridges=$this->modulhandling->getInstances($bridgeID);
                    foreach ($bridges as $bridge)
                        {
                        //echo "           ".IPS_GetName($bridge)."\n";
                        $config = @IPS_GetConfiguration($bridge);
                        if ($config !== false) 
                            {                   
                            $gateway[$hardwareType][IPS_GetName($bridge)]["OID"]=$bridge;
                            $gateway[$hardwareType][IPS_GetName($bridge)]["CONFIG"]=$config;
                            }
                        }
                    }
                elseif ($debug) echo "        BridgeID    unbekannt, keine Gatewayliste anlegen.\n";
                }
            }
        return($gateway);
        }

    /* Liste aller montierten Hardware Instanzen ausgeben, von EvaluateHardware aufgerufen
     *
     * Format ist gleich, Key ist der Hardwaretyp dann der Name der Instanz mit den Einträgen OID und CONFIG
     * übergeben wird eine Liste von Discovery oder Configurator Instanzen
     * aus den Discovery Instanzen wird nur die ModulID übernommen, mit getHardwareTyp aus der Hardware_Library ein Object class identifier gefunden
     * bekannt Hardware Type: 
     *      Homematic, HomematicExtended, HUE, Harmony, EchoControl, OpCentCam, IPSHeat
     *
     * der Ausgabewert ist Input für die Erstellung der DeviceList
     */

    public function get_HardwareList($discovery, $debug=false)
        {
        $hardware=array(); 
        $hardwareTypeDetect = new Hardware();           // in Hardware_library
        foreach ($discovery as $entry)
            {
            $hardwareType = $hardwareTypeDetect->getHardwareType($entry["ModuleID"]);       // einfaches Showup um die Erweiterung der class herauszufinden, zB HardwareHomematic
            if ($hardwareType != false) 
                {
                if ($debug) echo "    get_HardwareList: $hardwareType vom ".$entry["ModuleID"]." \n";
                $objectClassName = "Hardware".$hardwareType;
                $object = new $objectClassName(); 
                $deviceID = $object->getDeviceID();                     // wird im construct gesetzt {xxxx-xxx...}
                if ($debug) echo "      DeviceID :    ".json_encode($deviceID)." \n";               // kann neuerdings auch ein array sein
                //$devices=$this->modulhandling->getInstances($deviceID);
                $devices=$object->getDeviceIDInstances();
                foreach ($devices as $device)
                    {
                    if ($debug) echo "           ".IPS_GetName($device)."\n";
                    $object->getDeviceConfiguration($hardware, $device, $hardwareType, $debug);         // hardware als vektor übergeben
                    /*$config = @IPS_GetConfiguration($device);                                         // Routine Hardware spezifisch angelegt 
                    if ($config !== false) 
                        {                    
                        $hardware[$hardwareType][IPS_GetName($device)]["OID"]=$device;
                        $hardware[$hardwareType][IPS_GetName($device)]["CONFIG"]=$config;
                        }*/
                    }
                }
            }
        return($hardware);
        }

    /* TopologyLibraryManagement::get_Devicelist, Bestandteil der config::EvaluateHardware_Include   
     *
     *    $discovery = $modulhandling->getDiscovery();
     *    $hardware = $topologyLibrary->get_HardwareList($discovery);
     *    $deviceList = $topologyLibrary->get_DeviceList($hardware, false);        // class is in EvaluateHardwareLibrary, true ist Debug, einschalten wenn >> Fehler ausgegeben werden
     *
     *  basierend auf den Ergebnissen der Discovery module zuerst eine Hardwareliste und dann eine Deviceliste erstellen
     *  die Hardwareliste ist betreffend index je hardwareType unique. Übere mehrere HardwareTypes kann das nicht garantiert werden
     *  array deviceList erstellen, sortieren und um weitere Informationen anreichern 
     *   
     *  erstellen funktioniert modular mit getDeviceCheck, getDeviceParameter, getDeviceChannels, getDeviceActuators
     *  alle obigen functions sind Teil der class Hardware$hardwaretype, hardwaretype kommt aus der getHardwarelist
     *  diese Klassen findet man unter HardwareLibrary
     *  Beispiel 
     *      class HardwareHomematic extends Hardware
     *
     * Struktur devicelist
     *  In der Funktion devicelist wird für jeden Gerätenamen ein Array aufgemacht. 
     *  Untergruppen sind dann INSTANCES, CHANNELS, DEVICE, ACTUATORS, TOPOLOGY
     *  parallel zu den Untergruppen wird zumindest TYPE angelegt
     *
     * die Funktionen sind Teil der Hardware_Library und nach classes sortiert
     *  Beispiel wie oben zB HardwareHomematic->getDeviceChannels
     *      HardwareDenonAVR extends Hardware
     *      HardwareNetatmoWeather extends Hardware
     *      HardwareHomematic
     *      HardwareHUE
     *      HardwareHUEV2
     *      HardwareHarmony
     *      HardwareFHTFamily
     *      HardwareFS20Family
     *      HardwareFS20ExFamily
     *      HardwareEchoControl
     *
     * Wesentliche Änderungen in der Bibliothek:
     *  getDeviceCheck kann jetzt Namen abändern wenn er nicht mehr Unique ist, testweise durchziehen
     *  wir haben eine Liste der Devices mit einem eindeutigen Identifier, damit können Instanzen einfacher einem gemeinsamen Gerät zugeordnet werden
     *      siehe HueV2 und als alterntive Implementierung, weil vorher, in der Homematic Welt
     *
     */

    public function get_DeviceList($hardware, $config=false, $debug=false)
        {
        echo "  TopologyLibraryManagement::get_DeviceList aufgerufen";
        if (is_array($config))
            {
            if (isset($config["uniqueNames"]))
                {
                if (strtoupper($config["uniqueNames"])=="CREATE") 
                    {
                    $this->createUniqueNames=true;   
                    echo ", Erzeugung von uniqueNames wird unterstützt";
                    }
                }
            }
        echo ":\n";
        $deviceList=array();
        $hardwareTypeDetect = new Hardware();        
        foreach ($hardware as $hardwareType => $deviceEntries)          // die device types durchgehen HUE, Homematic etc.
            {
            $objectClassName = "Hardware".$hardwareType;
            $object = new $objectClassName(["uniqueNames"=>$this->createUniqueNames,"combineDevices"=>$deviceEntries],$debug); 
            foreach ($deviceEntries as $name => $entry)         // die devices durchgehen, Homematic Devices müssen gruppiert werden 
                {
                if ($debug) echo "      Bearbeite Gerät mit Index \"$name\" vom Typ \"$hardwareType\", new class is \"Hardware$hardwareType\":\n";
                if ($object->getDeviceCheck($deviceList, $name, $hardwareType, $entry, $debug))                 // name kann sich ebenfalls ändern wen unqueNames erzeugt werden              
                    {
                    if ($debug>1) echo "          $objectClassName=>getDeviceParameter aufgerufen:\n";
                    $object->getDeviceParameter($deviceList, $name, $hardwareType, $entry, $debug);             // Ergebnis von erkannten (Sub) Instanzen wird in die deviceList integriert, eine oder mehrer Instanzen einem Gerät zuordnen
                    if ($debug>1) echo "          $objectClassName=>getDeviceChannels aufgerufen:\n";
                    $ok = $object->getDeviceChannels($deviceList, $name, $hardwareType, $entry, $debug);      // Ergebnis von erkannten Channels wird in die deviceList integriert, jede Instanz wird zu einem oder mehreren channels eines Gerätes
                    if ($debug>1) echo "          $objectClassName=>getDeviceActuators aufgerufen:\n";
                    $object->getDeviceActuators($deviceList, $name, $hardwareType, $entry, $debug);             // Ergebnis von erkannten Actuators wird in die deviceList integriert, Aktuatoren sind Instanzen die wie in IPSHEAT bezeichnet sind
                    if ($debug>1) echo "          $objectClassName=>getDeviceInformation aufgerufen:\n";
                    if ($ok) $object->getDeviceInformation($deviceList, $name, $hardwareType, $entry, $debug);             // Ergebnis von erkannten Actuators wird in die deviceList integriert, Aktuatoren sind Instanzen die wie in IPSHEAT bezeichnet sind
                    if ($debug>1) echo "          $objectClassName=>getDeviceTopology aufgerufen:\n";
                    $object->getDeviceTopology($deviceList, $name, $hardwareType, $entry, $debug);             // Ergebnis von erkannten Actuators wird in die deviceList integriert, Aktuatoren sind Instanzen die wie in IPSHEAT bezeichnet sind
                    }
                }
            }
        ksort($deviceList);
        $actuators=$hardwareTypeDetect->getDeviceActuatorsFromIpsHeat($deviceList);         // die deviceList erweitern, siehe Hardware_Library
        if ($debug>1)
            {
            //print_r($deviceList);
            echo "\n";
            echo "TopologyLibraryManagement::get_DeviceList : Bereits konfigurierte Actuators aus IPSHeat dazugeben, Ergebnis der Funktion: \n";
            print_r($actuators);
            }
        elseif ($debug) 
            {
            echo "\n";
            echo "TopologyLibraryManagement::get_DeviceList : Bereits konfigurierte Actuators aus IPSHeat dazugeben.\n";
            }
        return($deviceList);
        }

    /* TopologyLibraryManagement::createTopologyInstances
     * mit Dummy Instanzen eine Topologie aufbauen, entweder als Tree Instanz als Children von Instanz oder in einen Kategoriebaum einsortieren
     * Input ist Topology mit eindeutigem Index und Eintrag Name und Pfad, immer name und Pfad verwenden, nicht den uniqueName als Index
     *
     * verwendet aus der class
     *      placeinstances
     *      roominstances
     *      deviceinstances
     *      devicegroupInstances
     *
     * diese werden aus der Modulsammlung generiert, Name ist nicht eindeutig
     * ruft createTopologyInstance auf, zweiter Parameter ist Parentliste, das sind die Objekte die als Parent in Frage kommen
     *
     * sort Order position room , places , devicegroups
     */

    public function createTopologyInstances(&$topology, $config=false, $debug=false)
        {
        $sortCateg=false;
        if ($config)
            {
            if (isset($config["Sort"]))
                {
                if (strtoupper($config["Sort"])=="KATEGORIE") 
                    {
                    $sortCateg=true;
                    }
                }
            }
        else
            {
            $config["Use"]["Place"]=true;
            $config["Use"]["Room"]=true;
            $config["Use"]["Device"]=true;
            $config["Use"]["DeviceGroup"]=true;
            }
        $onlyOne=true;
        $parent = $this->topID;
        if ($debug) 
            {
            echo "createTopologyInstances aufgerufen, Topology Eintraege durchgehen, als Liste dargestellt, insgesamt ".count($topology)." Einträge";
            if ($config) echo " (Config is ".json_encode($config).") ";
            echo " :\n";
            }
        foreach($topology as $uniqueName => $entry)
            {
            if (isset($entry["Type"]))
                {
                if ($debug) echo "   $uniqueName with Name ".$entry["Name"].", Type ".$entry["Type"]."   \n";         // UniqueName und Name und Type
                //print_R($entry);
                $entry["UniqueName"] = $uniqueName;           // Index auch als Parameter mitnehmen, wird an createTopologyInstance übergeben
                if ($onlyOne)
                    {
                    switch ($entry["Type"])
                        {
                        case "Place":
                            //print_r($entry);
                            if ( (isset($config["Use"]["Place"])) && $config["Use"]["Place"] )
                                {
                                $config["GUID"] = "{4D96B245-6B06-EC46-587F-25E8A323A206}";
                                if ($sortCateg) $InstanzID=$this->createTopologyInstance($this->placeInstances, $topology, $entry, $config, $debug);     // Places können nur in Places eingeordnet werden
                                else $InstanzID=$this->createTopologyInstance($this->placeInstances, $this->placeInstances, $entry, $config, $debug);     // Places können nur in Places eingeordnet werden
                                IPS_SetPosition($InstanzID,400);
                                }
                            break;
                        case "Room":
                            //print_r($entry);
                            if ( (isset($config["Use"]["Room"])) && $config["Use"]["Room"] )
                                {
                                $config["GUID"] = "{F8CBACC3-6D51-9C88-58FF-3D7EBDF213B5}";
                                if ($sortCateg) $InstanzID=$this->createTopologyInstance($this->roomInstances, $topology, $entry, $config, $debug);     // Places können nur in Places eingeordnet werden
                                else $InstanzID=$this->createTopologyInstance($this->roomInstances, $this->placeInstances, $entry,$config , $debug);      // Rooms können nur in Places vorkommen
                                IPS_SetPosition($InstanzID,500);
                                }
                            break;
                        case "Device":
                            /* Devices sind üblicherweise nicht in der Topologyliste. Bei Sonderwünschen halt auch dort eintragen */
                            if ( (isset($config["Use"]["Device"])) && $config["Use"]["Device"] )
                                {
                                $config["GUID"] = "{5F6703F2-C638-B4FA-8986-C664F7F6319D}";
                                if ($sortCateg) $InstanzID=$this->createTopologyInstance($this->deviceInstances, $topology, $entry, $config, $debug);
                                else $InstanzID=$this->createTopologyInstance($this->deviceInstances, $this->roomInstances, $entry, $config, $debug);      // Devices in Rooms vorkommen
                                $this->createTopologyInstance($this->deviceInstances, $this->devicegroupInstances, $entry, $config, $debug);      // Devices in Rooms vorkommen
                                IPS_SetPosition($InstanzID,900);
                                }
                            break;
                        case "DeviceGroup":
                            if ( (isset($config["Use"]["DeviceGroup"])) && $config["Use"]["DeviceGroup"] )
                                {
                                $config["GUID"] = "{CE5AD2B0-A555-3A22-5F41-63CFF00D595F}";
                                if ($sortCateg) $InstanzID=$this->createTopologyInstance($this->devicegroupInstances, $topology, $entry, $config , $debug);
                                else $InstanzID=$this->createTopologyInstance($this->devicegroupInstances, $this->roomInstances, $entry, $config , $debug);      // DeviceGroups können nur in Rooms vorkommen
                                IPS_SetPosition($InstanzID,600);
                                }
                            break;
                        default:
                            //$InstanzID = @IPS_GetInstanceIDByName($name, $parent);
                            break;
                        }
                    if ($InstanzID) $topology[$uniqueName]["TopologyInstance"]=$InstanzID;
                    //$onlyOne=false;           // Debug Purpose
                    }
                }
            else echo "$uniqueName without Type definition.\n";
            }
        }



    /* createTopologyInstance, siehe oben wird von createTopologyInstances aufgerufen
     * 
     * Es wird eine Neue TOPD Instanz mit der GUID erstellt wenn sie noch nicht in der InstanceList enthalten ist
     * der Name ist entweder der UniqueName oder (noch nicht implementeiert) der Name, gesucht wird aber immer nach UniqueName, das ist der eindeutige Key
     * sonst wird sie in die unter entry[parent] genanten Instanz, muss in der ParentList vorhanden sein, einsortiert
     *
     *  InstanceList    List of relevant instances, key ist eindeutiger Instance key nicht der Name
     *  ParentList      List of relevant Parent Instances, same format as InstanceList   Name->OID
     *  entry           UniqueName, Name, Parent, unique Guid as result of operation
     *  GUID            for createInstance, oder config mit guid und weiteren Parametern
     *
     * Beginnt in root.Topology, wenn vorhanden weitermachen, sonst return false
     *
     * Konfiguration
     *  ParentID        DefaultLagerort für alle Instanzen, andernfalls wird Topology gesucht
     *
     *
     * Format InstanceList, gibt eine Liste jeweils für Room, place, Device etc
     *      uniquename => 
     *
     * Format parentList
     *      uniquename => 
     *
     * Format entry
     *      name
     *      UniqueName
     *
     */

    private function createTopologyInstance($InstanceList, $ParentList, $entry, $guid, $debug=false)
        {
        $first=true; $sortCateg=false; $InstanzID=false;
        if (is_array($guid)) $config=$guid;
        else $config["GUID"]=$guid;
        if (isset($config["Sort"]))
            {
            if (strtoupper($config["Sort"])=="KATEGORIE") 
                {
                $sortCateg=true;
                }
            }
        if (isset($config["ParentID"])) $parent=$config["ParentID"];
        else $parent=@IPS_GetObjectIDByName("Topology", 0 );         // Default Parent
        if ($debug>1) echo "   createTopologyInstance aufgerufen, Config is ".json_encode($config).") \n";

        /* gibt es die Instanz mit dem Namen schon. Es wird in der InstanceList gesucht. Wenn nicht erstellen. Wenn schon update des Parents  */
        if ($parent)        // ohne Kategorie keine Funktion, sie wird aber übergeordnet von TopID eingelesen, also 100$ Wahrscheinlichkeit dass sie da ist
            {
            if ( (isset($InstanceList[$entry["UniqueName"]])) === false)          // Instanz noch nicht erstellt
                {
                $InstanzID = IPS_CreateInstance($config["GUID"]);          //Topology Room/Place/Device/DeviceGroup Instanz erstellen mit dem Namen "Stehlampe"
                if ($InstanzID !== false)
                    {
                    IPS_SetName($InstanzID, $entry["UniqueName"]);          // Instanz benennen, Name muss nicht eindeutig sein
                    if ($entry["Name"] == $entry["Parent"])             // Root identifier, üblicherweise World
                        {
                        if ($debug) echo "      -> Eine neue Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["UniqueName"]." unter $parent erstellen.\n"; 
                        IPS_SetParent($InstanzID, $parent); // Instanz einsortieren unter dem angeführten Objekt 
                        }
                    else
                        {
                        if (isset($ParentList[$entry["Parent"]]))    // auch hier ist ein UniqueName
                            {
                            if ($debug) echo "   -> Eine neue Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["UniqueName"]." unter ".$entry["Parent"]." erstellen.\n"; 
                            if (isset($ParentList[$entry["Parent"]]["OID"])) IPS_SetParent($InstanzID, $ParentList[$entry["Parent"]]["OID"]);
                            else IPS_SetParent($InstanzID, $ParentList[$entry["Parent"]]);
                            }
                        else 
                            {
                            if ($debug) echo "    -> Eine neue Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["UniqueName"]." vorerst unter $parent erstellen. Wird später einsortiert.\n"; 
                            IPS_SetParent($InstanzID, $parent);        // Parent noch nicht bekannt, Sauhaufen machen, und später unten korrigieren und neu einordnen
                            }
                        }
                    
                    //Konfiguration
                    //IPS_SetProperty($InstanzID, "HomeCode", "12345678"); // Ändere Eigenschaft "HomeCode"
                    IPS_ApplyChanges($InstanzID);           // Übernehme Änderungen -> Die Instanz benutzt den geänderten HomeCode
                    }
                else echo "!!! Fehler beim Instanz erstellen. Wahrscheinlich ein echo Befehl im Modul versteckt. \n";
                }
            else
                {
                $InstanzID = $InstanceList[$entry["UniqueName"]]; 
                $configTopologyDevice=IPS_GetConfiguration($InstanzID);         // Konfiguration bearbeiten/update
                if ($debug>1) echo "      Die Topology ".$entry["Type"]." Instanz-ID gibt es bereits und lautet: ".IPS_GetName($InstanzID)." (".$InstanzID."). Sie hat die Konfiguration : $configTopologyDevice und liegt unter ".IPS_GetName(IPS_GetParent($InstanzID))."(".IPS_GetParent($InstanzID).").\n";
                if ($entry["Name"] == $entry["Parent"])         // Root
                    {
                    if ((IPS_GetParent($InstanzID)) != $parent)
                        {
                        if ($debug) echo "    -> Die Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." unter $parent einsortieren (Root).\n"; 
                        IPS_SetParent($InstanzID, $parent); // Instanz einsortieren unter dem angeführten Objekt 
                        }
                    }
                else
                    {
                    if ($sortCateg) $expectedParent = $entry["UniqueName"];       // Name ist nicht UniqueName
                    else $expectedParent = $entry["Parent"];                         
                    if ($expectedParentID=$this->getParentId($expectedParent,$ParentList,$sortCateg,$debug=false))           // none ist auch false
                        {
                        if ($debug>1) echo "      suche $expectedParent und vergleiche $expectedParentID != ".(IPS_GetParent($InstanzID))."\n";
                        if ( $expectedParentID != (IPS_GetParent($InstanzID)) ) 
                            {
                            if ($debug) echo "    -> Die Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." unter ".$expectedParent." ($expectedParentID) einsortieren.\n"; 
                            IPS_SetParent($InstanzID, $expectedParentID);
                            }
                        }
                    else
                        {
                        $expectedParent=$entry["Parent"];               // nur bei sortCateg interessant, andernfalls den Pfad nach hinten fahren
                        if ($expectedParentID=$this->getParentId($expectedParent,$ParentList,$sortCateg,$debug=false))           
                            {
                            if ($debug) echo "      -> Nächsten Parent suchen, probiere ".$entry["Parent"].".\n";
                            if ($debug>1) echo "      suche $expectedParent und vergleiche $expectedParentID != ".(IPS_GetParent($InstanzID))."\n";
                            if ( $expectedParentID != (IPS_GetParent($InstanzID)) ) 
                                {
                                if ($debug) echo "        -> Die Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." unter ".$expectedParent." ($expectedParentID) einsortieren.\n"; 
                                IPS_SetParent($InstanzID, $expectedParentID);
                                }
                            }
                        else echo "          >>> schon wieder ein fail.\n";
                        }
                    }
                $entry["guid"]=$config["GUID"];
                $this->initInstanceConfiguration($InstanzID,$entry,true);           //true für debug

                //TOPD_SetDeviceList($InstanzID,$instances);
                //if (isset($installedModules["DetectMovement"]))  $Handler->RegisterEvent($InstanzID,'Topology','','');	                    /* für Topology registrieren, ich brauch eine OID damit die Liste erzeugt werden kann */
                }  
            return ($InstanzID);
            }
        else return (false);
        }

    /* vereinfachtes erlangen der ParentId
     * bei sortCateg ist es der UniqueName, sonst der Parent
     * in der ParentList kann es der Eintrag OID sein oder direkt ein Wert
     */
    private function getParentId($expectedParent,$ParentList,$sortCateg,$debug=false)
        {
        $expectedParentID=false;                // das geht besser zu programmieren
        if (isset($ParentList[$expectedParent]["OID"])) $expectedParentID = $ParentList[$expectedParent]["OID"];  
        elseif (isset($ParentList[$expectedParent]))  $expectedParentID = $ParentList[$expectedParent]; 
        else echo "Die Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["UniqueName"]." vorerst unter ".IPS_GetParent($InstanzID)." lassen. ".$expectedParent." nicht gefunden.\n";

        if ( ($expectedParentID) && (is_numeric($expectedParentID)) && ($expectedParentID>0)) return ($expectedParentID) ;
        else return (false);
        }

    /* TopologyLibraryManagement::initInstanceConfiguration
     * eine Topology instanz initialisieren 
     * erzeugt UUID
     * übernimmt aus entry 
     *      Path
     *      UniqueName
     *
     */
    public function initInstanceConfiguration($InstanzID, $entry=array(), $debug=false)
        {
                $configTopologyDevice=IPS_GetConfiguration($InstanzID);                     
                $newConfigTopologyDevice = json_decode($configTopologyDevice,true);         // als array
                if ($debug>1) print_R($newConfigTopologyDevice);
                if ($newConfigTopologyDevice["UUID"]=="")          // UUID setzen
                    {
                    switch ($entry["guid"])
                        {
                        case "{4D96B245-6B06-EC46-587F-25E8A323A206}":          // place
                            if ($debug) echo "topology Place UUID setzen.\n";
                            $newConfigTopologyDevice["UUID"]=TOPP_createUuid($InstanzID);
                            break;

                        case "{F8CBACC3-6D51-9C88-58FF-3D7EBDF213B5}":          // room
                            if ($debug) echo "topology Room UUID setzen.\n";
                            TOPR_getDefinition($InstanzID);
                            $newConfigTopologyDevice["UUID"]=TOPR_createUuid($InstanzID);
                            break;

                        case "{5F6703F2-C638-B4FA-8986-C664F7F6319D}":          // device
                            if ($debug) echo "topology Device UUID setzen.\n";
                            TOPD_SetDeviceList($InstanzID,$entry["instances"]);         // array order->instance -> NAME,OID,   erzeugt einzelne Variablen oder eine DeviceList
                            $newConfigTopologyDevice["UUID"]=TOPD_createUuid($InstanzID);
                            break;
                        case "{CE5AD2B0-A555-3A22-5F41-63CFF00D595F}":          // device group
                            if ($debug) echo "topology Device Group UUID setzen.\n";
                            $newConfigTopologyDevice["UUID"]=TOPDG_createUuid($InstanzID);
                            break;
                        }
                    }
                if (($debug>1) && (isset($entry["Path"])===false) ) print_R($entry);
                if ( (isset($entry["UniqueName"])) && ($newConfigTopologyDevice["UniqueName"]=="") )    $newConfigTopologyDevice["UniqueName"] = $entry["UniqueName"];                  
                if ( (isset($entry["Path"])) && ($newConfigTopologyDevice["Path"]=="") )                $newConfigTopologyDevice["Path"] = $entry["Path"];                  
                IPS_SetConfiguration($InstanzID,json_encode($newConfigTopologyDevice));
                IPS_ApplyChanges($InstanzID);                           // Übernehme Änderungen -> Die Instanz benutzt den geänderten HomeCode
        return ($newConfigTopologyDevice);
        }

    /* TopologyLibraryManagement::sortTopologyInstances, update der deviceList um Topology und Topology um infos aus deviceList
     *
     * verwendet aus der class
     *  topID                   start/base OID der Kategorien, nicht der Instanzen
     *  deviceInstances         alle Topology Device Instances (TOPD) bevor die Routine aufgerufen wurde
     *  roomInstances           Zusammenfassung Räume, damit Top Instance richtig einsortiert werden kann
     *  devicegroupInstances    alle Device Gruppen
     *
     * benötigt Modul DetectMovement, Input ist
     *   IPSUtils_Include ('EvaluateHardware_DeviceList.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
     *   $deviceList = deviceList(); 
     *   $channelEventList    = $DetectDeviceHandler->Get_EventConfigurationAuto();              // alle Events
     *   $deviceEventList     = $DetectDeviceListHandler->Get_EventConfigurationAuto();
     *
     * einsortieren der Geräte in das übergebene Array $devicelist, check mit $deviceEventList
     * verwendet deviceInstances für die Identifikation eines Gerätes
     *
     * das Gerät muss in der Devicelist Instances haben
     * beim ersten Mal aufrufen werden die Geräte in der Topology ohne Raumzuordnung direkt unter Topology angelegt
     * deviceInstances ist die Liste in denen bereits vorhandene Instanzen angeführt sind
     * der Index der deviceList ist eindeutig, daher auch für die anderen Instanzen verwendbar
     *   device             ein Gerät, typischerweise etwas zum Anfassen
     *      instances       Einheiten innerhalb des Gerätes, ein Schalter, Ein Messgerät, ein Taster etc.
     *          channels    Register, Einzelwerte für jeweilige Instance
     *
     * bei zweiten Mal von allen Instanzen die Räume auslesen, müssen gleich sein wenn sie nicht leer sind 
     * danach den Raum des Gerätes abgleichen, übernehmen wenn Raum aus den Instanzen bereits ermittelt wurde

     *
     * In der devicelist eine Topology Information einfügen
     *      Topology 0 ROOM|GROUP
     * In der Topology Liste eine Information über die erzeugte Instanz einfügen
     *
     *
     * Noch einmal Schritt für Schritt beschrieben:
     * das deviceList Array Eintrag für Eintrag durchgehen, nur wenn es einen Eintrag Instances gibt weiter machen
     * den Namen des Eintrages mit dem deviceInstances Objekt der class abgleichen, alle erstellten topology instances sind hier gespeichert 
     *    wenn noch nicht vorhanden einen neue Device Instanz erzeugen
     *    wenn vorhanden alle Instanzen aus dem deviceList Eintrag durchgehen und mit $channelEventList abgleichen
     *          alle Einträge sollen im selben Raum sein dann passts
     *          es werden Warnings ausgegeben, aber die fehler nicht automatisch korrigiert
     *
     *    wenn $config["Use"]["Device"])=="ACTUATORS"
     *          in topology einen neuen key mit Actuators anlegen
     *
     * Devices mit Order Position mit 900 eimsortieren
     */

    public function sortTopologyInstances(&$deviceList, &$topology, $channelEventList, $deviceEventList, $config=false, $debug=false)
        {
        if (isset($this->installedModules["DetectMovement"]))   // benötige topologyReferences
            {
            $DetectDeviceListHandler = new DetectDeviceListHandler();
            $DetectDeviceHandler = new DetectDeviceHandler();
            }
        else
            {
            echo "sortTopologyInstances called, Modul DetectMovement not installed, return as false.\n"; 
            return(false);
            }   
        if (isset($config["Sort"]))
            {
            if (strtoupper($config["Sort"])=="KATEGORIE") $sortCateg=true;
            else $sortCateg=false;
            }
        else $sortCateg=false;

        $i=0;
        $onlyOne=true;
        $parent=$this->topID;
        if ($debug) 
            {
            echo "sortTopologyInstances aufgerufen: Input ist die Devicelist mit ".count($deviceList)." Geraete Eintraegen. Der Reihe nach durchgehen. \n";
            echo "Base Category ist ".IPS_GetName($parent)." ($parent) ";
            if ($sortCateg) echo ", wir sortieren in Kategorien (angefordert). \n";
            else echo ", wir sortieren in Topology (default). \n";
            }
        $references = $DetectDeviceHandler->topologyReferences($topology,($debug>1));
        foreach ($deviceList as $name => $entry)            // name is unique in devicelist
            {
            //echo "$i   $name\n";
            $topRoom=false; $topGroup=false; $entryplace=false; $configUpdated=false;
            if (isset($entry["Instances"]))                 // es gibt die Kategorie Instances in der devicelist, alle Instanzen gemeinsam haben einen Room, ID=0
                {
                $instances=$entry["Instances"];
                //if ($onlyOne)
                    {
                    $gocreate=false;
                    if ( (isset($this->deviceInstances[$name])) === false )         // neue Device Instanz erzeugen, einordnen im Root       
                        {
                        if ( (isset($config["Use"]["Device"])) && ($config["Use"]["Device"]) ) 
                            {
                            if (strtoupper($config["Use"]["Device"])=="ACTUATORS") 
                                {
                                if (isset($entry["Actuators"])) $gocreate=true;
                                }
                            else $gocreate=true;
                            if ($gocreate)
                                {
                                if ($debug) echo str_pad($i,4)."Eine Device Instanz mit dem Namen $name unter ".IPS_GetName($parent)." ($parent) erstellen:\n";
                                $InsID = IPS_CreateInstance("{5F6703F2-C638-B4FA-8986-C664F7F6319D}");          //Topology Device Instanz erstellen 
                                if ($InsID !== false)
                                    {
                                    IPS_SetName($InsID, $name); // Instanz benennen
                                    IPS_SetParent($InsID, $parent); // Instanz einsortieren unter dem angeführten Objekt 
                                    
                                    //Konfiguration
                                    //IPS_SetProperty($InsID, "HomeCode", "12345678"); // Ändere Eigenschaft "HomeCode"
                                    IPS_ApplyChanges($InsID);           // Übernehme Änderungen -> Die Instanz benutzt den geänderten HomeCode
                                    $this->deviceInstances[$name]=$InsID;
                                    }
                                else if ($debug) echo "Fehler beim Instanz erstellen. Wahrscheinlich ein echo Befehl im Modul versteckt. \n";
                                }
                            }
                        $room="none";
                        }
                    if (isset($this->deviceInstances[$name]))                   // DeviceInstanz bereits erstellt nur mehr einsortieren
                        {
                        // die DeviceInstances sind mit dem Unique Name aus der deviceList erzeugt wurden, keine Überschneidungen zu erwarten
                        $InstanzID = $this->deviceInstances[$name];    
                        if ($debug) echo str_pad($i,4)."Eine Device Instanz mit dem Namen $name unter ".IPS_GetName(IPS_GetParent($InstanzID))." (".IPS_GetParent($InstanzID).") gibt es bereits und lautet: ". $InstanzID."   \n";
                        // Workaround für Device Variable Childrens
                        $devId = @IPS_GetObjectIDByName ($name, $InstanzID);
                        if ($devId)
                            {
                            IPS_SetHidden($devId,true);
                            $devId = IPS_GetObjectIDByName ("Device Liste", $InstanzID);
                            IPS_SetHidden($devId,true);
                            }
                        else 
                            {
                            $childs = IPS_GetChildrenIDs($InstanzID);
                            if (count($childs)) echo "      >>>Warnung 183, eine Device Instanz mit dem Namen $name ($InstanzID) unter ".IPS_GetName(IPS_GetParent($InstanzID))." (".IPS_GetParent($InstanzID).") gibt es, sie hat aber keine gleichnamigen Children die man verstecken kann.\n";
                            else echo "      >>>Warnung 192, eine Device Instanz mit dem Namen $name ($InstanzID) unter ".IPS_GetName(IPS_GetParent($InstanzID))." (".IPS_GetParent($InstanzID).") gibt es, sie hat aber keine Children die man verstecken kann.\n";
                            }
                        IPS_SetPosition($InstanzID,900);
                        // Abgleich Topology DeviceInstance mit channelEventList, deviceEventList und topology ob der room stimmt
                        $room=$DetectDeviceHandler->findRoom($instances,$channelEventList,$topology);                   // alle Instanzen aus einem deviceList Eintrag durchgehen und mit $channelEventList abgleichen, eine Rauminformation daraus ableiten, Plausicheck inklusive
                        //if ($name=="ArbeitszimmerHue") { echo "***************\n"; }

                        if (isset($deviceEventList[$InstanzID]))        // device mit Rauminformation abgleichen
                            {
                            //print_r($deviceEventList[$InstanzID]);
                            if ($room != $deviceEventList[$InstanzID][1]) 
                                {
                                if ($room != "") echo "      !!!Fehler 097, die Channels und das Device sind in unterschiedlichen Räumen: \"$room\" \"".$deviceEventList[$InstanzID][1]."\" Zweiten Begriff übernehmen.\n";
                                else echo "      >>>Warnung 112, Erster Raum ist leer in Topology, bitte einen Eintrag durchführen.\n";
                                if ($deviceEventList[$InstanzID][1] == "") echo "      >>>Warnung, Zweiter Raum ist leer für ".IPS_GetName($InstanzID)." ($InstanzID): ".json_encode($deviceEventList[$InstanzID])."\n";
                                $room = $deviceEventList[$InstanzID][1];
                                $entryplace=$DetectDeviceHandler->uniqueNameReference($room,$references);
                                }
                            }
                        else echo "      >>>Warnung, Instanz $InstanzID nicht in der $deviceEventList. Eigentlich unmöglich ?\n";
                        // room eindeutig machen wenn tilde im Namen
                        if ( ($entryplace) && (isset($topology[$entryplace])) )
                            {
                            if ($debug)
                                {
                                //echo "      >>>Info, Tilde im Raum, auflösen : $entryplace aus ".json_encode($topology[$entryplace]).".\n";
                                echo "      >>>Info, Tilde im Raum, auflösen : $entryplace aus ".$topology[$entryplace]["Path"].".\n";
                                }
                            $newentry=$topology[$entryplace];
                            }
                        else $newentry=array();
                        if ($sortCateg)         // in eine Kategorie einsortieren
                            {
                            $expectedParent = false;                    // default nix machen
                            if ( (isset($config["Use"]["Device"])) && ($config["Use"]["Device"]) )              // Was machen wir mit Devices, in die Kategorie einsortiern
                                {
                                $expectedParent = $parent;                  // wenn use Device in den parent schicken                                
                                if (strtoupper($config["Use"]["Device"])=="ACTUATORS")
                                    {
                                    if (isset($entry["Actuators"]))                        // neue Betriebsart, Schalter, RGB und Ambient Lampen einbauen
                                        {
                                        //echo $name."  ".json_encode($entry["Actuators"])."\n";    
                                        if (isset($topology[$room]["OID"])) $expectedParent=$topology[$room]["OID"];
                                        $topology[$room]["Actuators"][$name]=$entry["Actuators"];
                                        $topology[$room]["Actuators"][$name]["TopologyInstance"]=$InstanzID;
                                        // more Information out of devicelist for updateLinks
                                        }
                                    }
                                elseif (isset($topology[$room]["OID"])) $expectedParent=$topology[$room]["OID"];
                                }
                            if ( ($expectedParent) && (is_numeric($expectedParent)) && ($expectedParent>0))
                                {
                                if ($debug>1) echo "         Vergleiche ".IPS_GetParent($InstanzID)." (".IPS_GetName(IPS_GetParent($InstanzID)).") mit $expectedParent  ";    
                                if ( IPS_GetParent($InstanzID) != $expectedParent)
                                    {
                                    if ($debug) echo "    -> Kategorie Room vorhanden. Ungleich zu $expectedParent, Parent auf $room setzen.\n";
                                    IPS_SetParent($InstanzID,$expectedParent);
                                    }
                                elseif ($debug>1) echo "    -> Kategorie Room vorhanden.\n";
                                $topRoom=true;                                                  // auch in deviceList abspeichern
                                $newentry["guid"]="{5F6703F2-C638-B4FA-8986-C664F7F6319D}";
                                $newentry["instances"]=$instances;
                                $newentry["UniqueName"]=$entryplace;
                                $configUpdated = $this->initInstanceConfiguration($InstanzID,$newentry,$debug);          //true für debug
                                if ($debug>1) print_R($configUpdated);
                                }
                            }
                        else                // in eine andere Instanz einsortieren, nicht mehr so üblich
                            {
                            if (isset($this->roomInstances[$room]))         // kennt den Parent room für einen Raum, kümmert sich aber nicht um gleiche Namen
                                {
                                //echo "Vergleiche ".IPS_GetParent($InstanzID)." mit ".$roomInstances[$room]."\n";
                                if ( IPS_GetParent($InstanzID) != $this->roomInstances[$room])
                                    {
                                    if ($debug) echo "    -> Instanz Room vorhanden. Parent auf $room setzen.\n";
                                    IPS_SetParent($InstanzID,$this->roomInstances[$room]);
                                    }
                                $topRoom=true;                                                  // auch in deviceList abspeichern
                                }
                            elseif (isset($this->devicegroupInstances[$room]))    
                                {
                                if ( IPS_GetParent($InstanzID) != $this->devicegroupInstances[$room])
                                    {
                                    if ($debug) echo "    -> Instanz DeviceGroup vorhanden. Parent $room setzen.\n";
                                    IPS_SetParent($InstanzID,$this->devicegroupInstances[$room]);
                                    }
                                $topGroup=true;                                                  // auch in deviceList abspeichern
                                }
                            $newentry["guid"]="{5F6703F2-C638-B4FA-8986-C664F7F6319D}";
                            $newentry["instances"]=$instances;
                            $newentry["UniqueName"]=$entryplace;
                            $this->initInstanceConfiguration($InstanzID,$newentry,true);          //true für debug
                            
                            /* $configTopologyDevice=IPS_GetConfiguration($InstanzID);
                            //echo "  Hier ist die abgespeicherte Konfiguration:    $configTopologyDevice \n";
                            
                            $oldconfig=json_decode($configTopologyDevice,true);
                            print_r($oldconfig);
                            $oldconfig["UpdateInterval"]=10;
                            $newconfig=json_encode($oldconfig);
                            echo "Neue geplante Konfiguration wäre : $newconfig \n";
                            IPS_SetConfiguration($InstanzID,$newconfig);
                            
                            TOPD_SetDeviceList($InstanzID,$instances);      */
                            }
                        if (isset($this->installedModules["DetectMovement"]))  $DetectDeviceListHandler->RegisterEvent($InstanzID,'Topology',$room,'');	                    /* für Topology registrieren, ich brauch eine OID damit die Liste erzeugt werden kann */
                        }
                    //$onlyOne=false;
                    /* Ein Eintrag wurde erstellt oder ist vorhanden, devicelist erweitern mit den zusätzlichen Informationen */
                    if ($topRoom) 
                        {
                        unset($configUpdated['ImportCategoryID']);
                        unset($configUpdated['Open']);
                        unset($configUpdated['UpdateInterval']);
                        $configUpdated["Name"]=$room;
                        //$deviceList[$name]["Topology"][0]["ROOM"]=$room;
                        if ($configUpdated) $deviceList[$name]["Topology"] = $configUpdated;
                        }
                    if ($topGroup) $deviceList[$name]["Topology"][0]["GROUP"]=$room;
                    $i++;    
                    }
                }           // ende isset instances
            }      // end foreach
        }



    }       // ende class

/**************************************************************
 *
 * Webfront Darstellungs Unterstützung
 *      __construct
 *      setTopologyConfig
 *      showControlLine
 *      showTopologyTableFrame
 *      showTableHtml
 *
 */

class showEvaluateHardware
    { 

    protected $topologyControlTableID,$topologyConfigTableID,$topologyTableID;
    protected $topologyConfig;

    /* construct, wichtige Variablen und Konfigurationen zur Darstellung
     */

    public function __construct()
        {
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        $moduleManager    = new ModuleManagerIPS7('EvaluateHardware',$repository);
        $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
        $categoryId_DetectTopologies = @IPS_GetCategoryIDByName('DetectTopologies', $CategoryIdData);
        if ($categoryId_DetectTopologies)
            {
            $this->topologyControlTableID  = IPS_GetVariableIDByName("TopologyControlTable",$categoryId_DetectTopologies);   
            $this->topologyConfigTableID   = IPS_GetVariableIDByName("TopologyConfigTable",$categoryId_DetectTopologies);
            $this->topologyTableID         = IPS_GetVariableIDByName("TopologyViewTable",$categoryId_DetectTopologies);
            }
        }

    /* use config of class, update it
     */
    public function setTopologyConfig($config)  
        {
        $this->topologyConfig=$config;
        }

    /* eine Tabelle zum Konfigurieren von Filtern bauen
     */
    public function showControlLine($configInput=false)
        {
        $table=false;
        $js = new jsSnippets();
        if (is_array($configInput)) $config = $configInput;
        if (isset($config["display"]))
            {
            $table="<table><tr>";
            foreach ($config["display"] as $index=>$entry)
                {
                if ((is_array($entry)) && (isset($entry["header"]))) $header=$entry["header"];
                else $header=$entry;
                $table .= "<th>".$header."</th>";
                }
            $table .= "</tr><tr>";
            foreach ($config["display"] as $index=>$entry)
                {
                if ((is_array($entry)) && (isset($entry["header"]))) $header=$entry["header"];
                else $header=$entry;
                if (isset($config["analyse"][$header]))
                    {
                    $table .= "<td>".count($config["analyse"][$header])."</td>";
                    }
                else $table .= "<td>".$header."</td>";
                }  
            $table .= "</tr><tr>";
            $col=0; $test=3;
            foreach ($config["display"] as $index=>$entry)
                {
                if ((is_array($entry)) && (isset($entry["header"]))) $header=$entry["header"];
                else $header=$entry;
                if (isset($config["analyse"][$header]))
                    {
                    $inputId = "eh".str_pad($col, 2, 0, STR_PAD_LEFT)."input";    
                    $listId = "eh".str_pad($col, 2, 0, STR_PAD_LEFT)."list";
                    $formId = "eh".str_pad($col, 2, 0, STR_PAD_LEFT).$header;
                    $input  = '<form id="'.$formId.'" onsubmit="checkformtable(event)">';
                    //$input  .= '<input name="formulaCell">';
                    $input  .= '<input list="'.$inputId.'" name="formulaCell" id="'.$listId.'">';
                    $input  .= '<datalist id="'.$inputId.'">';
                    $i=0;
                    foreach ($config["analyse"][$header] as $dataValue => $subentry)
                        {
                        $input  .= '<option value="'.$dataValue.'">';
                        if ($i++>5) break;
                        }
                    $input  .= '</datalist>';
                    $input .= '</form>';
                    $table .= "<td>".$input."</td>";
                    $col++;
                    }
                else $table .= "<td>".$header."</td>";
                } 
            $table .= "</tr>";                         
            $table .= "</table>";
            }
        $html  = "";
                        /*
                    //$input = '<form action="/user/EvaluateHardware/EvaluateHardware_Receiver.php" method="post">';           // open new page with php file name
                    //$input = '<form action="/user/EvaluateHardware/EvaluateHardware_Receiver.php" method="get">';           // open new page with php file name, get uses new url to open webpage
                    //$input = '<form action="#" onsubmit="return validateFormOnSubmit(this);">';                             // opens same page again, with browser as get info
                    //$input = '<form action="javascript:handleIt()">';                                                       // über onsubmit bereits vor der form validation
                    $input = '<form id="eingabeTabelle" method="POST" enctype="multipart/form-data" onsubmit="checkformtable(event)">';              // onsubmit ruft das javascript auf, sicherstellen das nicht auch noch das post gestartet wird mit preventDefault
                    //$input = '<form id="eingabeTabelle">';                                                                      // gleich im javascript abfangen, etwas unübersichtlicher, aber ziemlich üblich
                    $input .= '  <label for="browser">Choose your browser from the list:</label>
                            <input list="browsers" name="formulaCell" id="browser">
                            <datalist id="browsers">
                                <option value="Edge">
                                <option value="Firefox">
                                <option value="Chrome">
                                <option value="Opera">
                                <option value="Safari">
                            </datalist>
                            <input type="submit" value="Absenden">
                            </form>';

                    $input2 = '<form id="eingabeTabelle2" method="POST" enctype="multipart/form-data" onsubmit="checkformtable(event)"> <input name="formulaCell"> </form>';
                    //$input2 = '<form id="eingabeTabelle2"> <input type="text" id="cell3" name="formulaCell3"> </form>';
                    $input3 = '<form id="eingabeTabelle3"> <input name="formulaCell"> </form>';                             // mit einem eigenen document.getElementById(\'eingabeTabelle3\').addEventListener(\'submit\', function(e) { e.preventDefault(); ..... });';    
                    if ($col==$test) $table .= "<td>".$input."</td>";
                    elseif ($col==4) $table .= "<td>".$input2."</td>";
                    elseif ($col==5) $table .= "<td>".$input3."</td>";
                    else $table .= "<td>".count($config["analyse"][$header])."</td>";
                    */
        /*$html  .= '<script type="text/javascript" src="/user/EvaluateHardware/jquery-3.7.1.min.js"></script>';        // diese oder eine ähnliche Datei sollte bereits geladen sein
        //$html  .= '<script type="text/javascript" src="/user/EvaluateHardware/EvaluateHardware.js" ></script>';       // kein Verweis auf externe javascript Dateien ohne iframe
        //$html .= '$("#evhw-go").click(function(){document.getElementById("evhw-go").innerHTML = "empty image picture";});';
	    //$html .= '$("#ev-send-ajax-id").click(function(){ ';                                                                                                                          //jquery ausdesignen, an vanilla javascript gewöhnen
        $ready .= 'document.querySelector("#ev-send-ajax-id").addEventListener("click", (e) => {
		              //document.getElementById("ev-inf-ajax-id").innerHTML = "ajax request will be send";
		              EVtrigger_ajax("button-ev3", "Cookie", "evaluatehardware", "");
		              //document.getElementById("ev-inf-ajax-id").innerHTML = "ajax request was send" + Date();
		              });'; 
        $ready .= '  document.getElementById(\'eingabeTabelle3\').addEventListener(\'submit\', function(e) {
                        e.preventDefault();
                        // Bearbeiten Sie die Formulardaten
                        var element = document.getElementById(\'formulaCell\');
                        const form = new FormData(e.target); 
                        const formula = form.get("formulaCell");
                        alert (formula);
                        });';           
        //$ready .= '  const goodTime = `${new Date().getHours()}:${new Date().getMinutes()}:${new Date().getSeconds()}`;   ';                          
                                                */

        $identifier="ev";
        /* ready definiert die Listener. Allerdings ruft das Form auch bereits eine function auf, ohne listener gelöst
         * siehe https://developer.mozilla.org/en-US/docs/Web/API/HTMLFormElement/submit_event
         */

        $html .= '<script type="text/javascript">';
        $ready  = "";
        $ready .= '  if (typeof trigger_ajax !== "undefined") { document.getElementById("evhw-go").innerHTML = "look here, got loaded, trigger_ajax somewhere else defined"; }
                     else { document.getElementById("evhw-go").innerHTML = "look here, got loaded"; }
                ';
 
        $ready .= '  var el = document.getElementById("evhw-go");
                 ';
        $ready .= '  setInterval(function() {
                        var currentTime = new Date(),
                            hours = currentTime.getHours(),
                            minutes = currentTime.getMinutes(),
                            seconds = currentTime.getSeconds(),
                            ampm = hours > 11 ? "PM" : "AM";
                        seconds = seconds <10 ? "0"+seconds : seconds;
                        hours = hours < 10 ? "0+hours" : hours;
                        minutes = minutes < 10 ? "0"+minutes : minutes;
                    el.innerHTML = hours + ":" + minutes + ":" + seconds + " " + ampm;
                            }, 1000);  
                 ';                             
        $ready .= '  document.querySelector("#evhw-go").addEventListener("click", (e) => { document.getElementById("evhw-go").innerHTML = "ready steady go"; });
                ';
        $ready .= '  document.querySelector("#ev-send-ajax-id").addEventListener("click", (e) => {
		              '.$identifier.'_trigger_ajax("button-ev3", "Cookie", "evaluatehardware", "");
		              });'; 

        $html .= $js->ready($ready);  
        $html .= '     function '.$identifier.'_trigger_ajax(id, action, module, config) {
                            //$.ajax({type: "POST", url: "/user/EvaluateHardware/EvaluateHardware_Receiver.php", data: "&id="+id+"&action="+action+"&module="+module+"&info="+info});
                            var result;			// will become object after assignment
                            action='.$identifier.'_readCookie("identifier-symcon-evaluatehardware");
                            ajax.post("/user/EvaluateHardware/EvaluateHardware_Receiver.php", 
                                    {id:id,action:action,module:module,info:config},
                                    function(data, status){	
                                        var configws = '.$identifier.'_analyseConfig(data);
                                        document.getElementById("ev-inf-ajax-id").innerHTML = "Ajax Response: \'"+configws+"\'   "+status + "   " + action + "   " + Date();
                                        });
                                };';
       $html .= '     function '.$identifier.'_analyseConfig(obj) {
                        var result;
                        result = JSON.parse(obj);
                        if (typeof result.module == "undefined") alert ("module identifier not available");
                        else {
                            if (result.module=="evaluatehardware")
                                {
                                }
                            else
                                {
                                alert (result.module);
                                if (typeof result.startofscript == "undefined") alert ("startofscript not available");
                                else {
                                    var result1 = JSON.parse(result.startofscript);
                                    if (typeof result1.startofscript == "undefined") alert ("startofscript.startofscript not available");
                                    if (typeof result1.buttoneins == "undefined") alert ("startofscript.buttoneins not available");
                                        {
                                        var buttoneins = JSON.parse(result1.buttoneins);
                                        if (typeof buttoneins.info == "undefined") alert ("startofscript.buttoneins.info not available");
                                        picture = buttoneins.info;
                                        updatePictureStyle(picture);
                                        }
                                    return result.startofscript;
                                    if (typeof result.buttoneins == "undefined") alert ("buttoneins not available");
                                    }
                                }
                            }
                        return obj;
                        };';                                	
       $html .= '     function '.$identifier.'_analyseConfig(obj) {
                        var result;
                        result = JSON.parse(obj);
                        return obj;
                        };';  
       $html .= '     function '.$identifier.'_readCookie(name) {
                        var nameEQ = encodeURIComponent(name) + "=";
                        var ca = document.cookie.split(\';\');
                        for (var i = 0; i < ca.length; i++) {
                            var c = ca[i];
                            while (c.charAt(0) === \' \')
                                c = c.substring(1, c.length);
                            if (c.indexOf(nameEQ) === 0)
                                return decodeURIComponent(c.substring(nameEQ.length, c.length));
                            }
                        return null;
                        };
                ';   
        $html .= '  var ajax = {};
                    ajax.x = function () {
                        if (typeof XMLHttpRequest !== \'undefined\') {
                            return new XMLHttpRequest();
                        }
                        var versions = [
                            "MSXML2.XmlHttp.6.0",
                            "MSXML2.XmlHttp.5.0",
                            "MSXML2.XmlHttp.4.0",
                            "MSXML2.XmlHttp.3.0",
                            "MSXML2.XmlHttp.2.0",
                            "Microsoft.XmlHttp"
                        ];

                        var xhr;
                        for (var i = 0; i < versions.length; i++) {
                            try {
                                xhr = new ActiveXObject(versions[i]);
                                break;
                            } catch (e) {
                            }
                        }
                        return xhr;
                    };

                    ajax.send = function (url, callback, method, data, async) {
                        if (async === undefined) {
                            async = true;
                        }
                        var x = ajax.x();
                        x.open(method, url, async);
                        x.onreadystatechange = function () {
                            if (x.readyState == 4) {
                                callback(x.responseText)
                            }
                        };
                        if (method == \'POST\') {
                            x.setRequestHeader(\'Content-type\', \'application/x-www-form-urlencoded\');
                        }
                        x.send(data)
                    };

                    ajax.get = function (url, data, callback, async) {
                        var query = [];
                        for (var key in data) {
                            query.push(encodeURIComponent(key) + \'=\' + encodeURIComponent(data[key]));
                        }
                        ajax.send(url + (query.length ? \'?\' + query.join(\'&\') : \'\'), callback, \'GET\', null, async)
                    };

                    ajax.post = function (url, data, callback, async) {
                        var query = [];
                        for (var key in data) {
                            query.push(encodeURIComponent(key) + \'=\' + encodeURIComponent(data[key]));
                        }
                        ajax.send(url, callback, \'POST\', query.join(\'&\'), async)
                    };
                ';
        $html .= 'function handleIt() {        alert("hello");       } ';

        /* empfängt die Filter 
         * siehe auch https://developer.mozilla.org/en-US/docs/Web/API/Event/target
         *
         *  let submitter = e.submitter;let handler = submitter.id;console.log(formula); alert (handler + ":" + formula);
         */
        $html .= 'const checkformtable = (e) => {    
                        const form = new FormData(e.target);    
                        const formula = form.get("formulaCell");   // gleicher Name, Unterscheidung über
                        e.preventDefault();
                         '.$identifier.'_trigger_ajax(formula + ":" + e.target.id, "Filter", "evaluatehardware", "");           // id, action, module, config
                         //alert(formula + ":" + e.target.id);
                         return false };';
        $html .= '</script>';
                        $id="greenheadergray"; $size=0; $text="";
                        $text .= "<style>";
                        $text .= '.'.$id.' { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; ';          // table style
                        if ($size==0) $text.='font-size: 100%; width: 100%;';
                        elseif ($size==-1) $text.='font-size:50%vw; max-width: 900px ';        // responsive font size
                        else $text.='font-size: 150%; width: 100%;';
                        $text.='color:black; border-collapse: collapse;  }';
                        //$wert .= '<font size="1" face="Courier New" >';
                        $text .= '.'.$id.' td th { border: 1px solid #ddd; padding: 8px; }';
                        $text .= '.'.$id.' tr:nth-child(even){background-color: #f2f2f2;color:black;}';
                        $text .= '.'.$id.' tr:nth-child(odd){background-color: #e2e2e2;color:black;}';
                        $text .= '.'.$id.' tr:hover {background-color: #ddd;}';
                        $text .= '.'.$id.' th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; word-wrap: break-word; white-space: normal;}';
                        $text .= "</style>";  
        $html .= $text;                
        if ($table) $html .= '<div id="evhw-tableheader" class="'.$id.'">'.$table."</div>";              
        $html .= '<div id="evhw-go" style="display:inline">look here</div>';
	    $html .= '<div id="ev-inf-ajax-id" style="display:inline; padding:5px">or maybe here</div>';
	    $html .= '<div id="ev-send-ajax-id" style="display:inline; padding:5px">then press here</div>';   
        SetValue($this->topologyControlTableID,$html);
        }
    /* die Topologie Tabelle zeigen und manipulieren wie zB
     *          sortieren
     */
    public function showTopologyTableFrame($configInput=false)
        {
        $html = "";
        $ipsTables = new ipsTables();               // fertige Routinen für eine Tabelle in der HMLBox verwenden
        $inputData=array();
        foreach (IPSDetectDeviceHandler_GetEventConfiguration() as $key => $entry)
            {
            $inputData[$key]=$entry;
            if (IPS_VariableExists($key)) $inputData[$key]["Value"]=GetValueIfFormatted($key);
            }

        $config["text"]    = true;
        $config["insert"]["Header"]    = true;
        $config["insert"]["Index"]    = true;
        //$config["html"]    = 'html';                        // Ausgabe als html formatierte Tabelle
        $config["format"]["class-id"]="topy";           // make it short
        $config["format"]["header-id"]="hrow";          // make it short
        $config["display"] = [
                        "Index"                     => ["header"=>"OID","format"=>"OID"],
                        "Name"                      => "ObjectName",
                        "Value"                     => "Wert",
                        "0"                         => "Modul",
                        "1"                         => "Place",
                        "2"                         => "Type",
                        "3"                         => "Device",
                        "4"                         => "newName",
                    ];
        $config["process"] = ["Place" => "extend"];
        $config["filter"] = ["Type"  => "zimmer"];
        $text = $ipsTables->showTable($inputData, false ,$config, true);                // true Debug


        /* HTML Box benötigt Javascript Componente und individuellen Identifier
        *  Elements sind:  status      reportWindowSize
        *                  browser     reportBrowserVersion
        *                  fullscreen  toggleFullScreen
        */
        $identifier="individuell";

        $html = "";
        $html .= '<script type="text/javascript">';
        $ready  = "";
        $ready .= '  document.querySelector("#'.$identifier.'-status").addEventListener("click", (e) => {
                        '.$identifier.'reportWindowSize ();
                        });
                    ';   
        $ready .= '  document.querySelector("#'.$identifier.'-browser").addEventListener("click", (e) => {
                        document.getElementById("'.$identifier.'-browser").innerHTML = '.$identifier.'reportBrowserVersion ();
                        });  
                ';
        $ready .= '  document.querySelector("#'.$identifier.'-fullscreen").addEventListener("click", (e) => {
                        document.getElementById("'.$identifier.'-fullscreen").innerHTML =  '.$identifier.'toggleFullScreen(document.documentElement);
                        });  
                ';   
        $html .= $js->ready($ready);  
        $html .= '  function '.$identifier.'reportWindowSize () {
                        let varheight=Math.round(window.innerHeight * 0.85);
                        document.getElementById("'.$identifier.'-status").innerHTML = "Size " + window.innerHeight + " (" + varheight + ") x " + window.innerWidth + "  " + Date();
                        };
                    window.onresize = '.$identifier.'reportWindowSize;                     
                ';
        $html .= '  function '.$identifier.'reportBrowserVersion() {
                        var Sys = {};  
                        var ua = navigator.userAgent.toLowerCase();  
                        var s;  
                        (s = ua.match(/msie ([\d.]+)/)) ? Sys.ie = s[1] :  
                        (s = ua.match(/firefox\/([\d.]+)/)) ? Sys.firefox = s[1] :  
                        (s = ua.match(/chrome\/([\d.]+)/)) ? Sys.chrome = s[1] :  
                        (s = ua.match(/opera.([\d.]+)/)) ? Sys.opera = s[1] :  
                        (s = ua.match(/version\/([\d.]+).*safari/)) ? Sys.safari = s[1] : 0; 
                        if (Sys.ie) return ("IE: " + Sys.ie);  
                        if (Sys.firefox) return ("Firefox: " + Sys.firefox);  
                        if (Sys.chrome) return ("Chrome: " + Sys.chrome);  
                        if (Sys.opera) return ("Opera: " + Sys.opera);  
                        if (Sys.safari) return ("Safari: " + Sys.safari); 
                        }  
                  ';                     
        $html .= '  var fullScreen=0;
                    function '.$identifier.'toggleFullScreen(elem) {
                        if (fullScreen==0) { elem.requestFullscreen(); fullScreen=1; return ("Full Screen"); }
                        else { document.exitFullscreen(); fullScreen=0; return ("Standard Screen"); } 
                        } 
                ';   
        $html .= '</script>';
        $html .= '<div style="box-sizing: border-box;">';
        $html .= '  <iframe id="'.$identifier.'-start" name="EvaluateHardware" src="../user/EvaluateHardware/EvaluateHardware.php" style="width:100%; height:85vh; ">';
        $html .= '      </iframe>';
        $html .= '  </div>';
        $html .= '<div id="'.$identifier.'-status" style="font-size: 1hm; display:inline; float:left;">Statusangaben hier clicken</div>';        
        $html .= '<div id="'.$identifier.'-browser" style="font-size: 1hm; display:inline; padding: 5px;">Browser hier clicken</div>';        
        $html .= '<div id="'.$identifier.'-fullscreen" style="font-size: 1hm; display:inline; float:right;">Fullscreen hier clicken</div>';  
        SetValue($this->topologyTableID,$html);
        }

    public function showTableHtml()
        {
        $ipsTables = new ipsTables();               // fertige Routinen für eine Tabelle in der HMLBox verwenden
        $inputData=array();
        foreach (IPSDetectDeviceHandler_GetEventConfiguration() as $key => $entry)
            {
            $inputData[$key]=$entry;
            if (IPS_VariableExists($key)) $inputData[$key]["Value"]=GetValueIfFormatted($key);
            }
        $config["text"]    = false;						// kein echo
        $config["insert"]["Header"]    = true;
        $config["insert"]["Index"]    = true;
        $config["html"]    = 'html';    
        $config["display"] = [
                        "Index"                     => ["header"=>"OID","format"=>"OID"],
                        "Name"                      => "ObjectName",
                        "Value"                     => "Wert",					
                        "0"                         => "Modul",
                        "1"                         => "Place",
                        "2"                         => "Type",
                        "3"                         => "Device",
                        "4"                         => "newName",
                    ];
        $config["format"]["class-id"]="topy";           // make it short
        $config["format"]["header-id"]="hrow";          // make it short				
        $text = $ipsTables->showTable($inputData, false ,$config, false);                // true Debug
        return $text;	
        }

    }    

/***************************************************************/



class ImproveDeviceDetection
    {    
    /* anhand der Hardware Liste die Instanzen einordnen
     */

    public function setParentHomemeaticDevicePortZero($hardware,$parent)
        {
        echo "setParentHomemeaticDevicePortZero aufgerufen:\n";
        foreach ($hardware as $type => $device)
            {
            if ($type=="Homematic") 
                {
                foreach ($device as $name => $entry)
                    {
                    $nameSelect=explode(":",$name);
                    if (count($nameSelect)<2) 
                        {
                        echo "    setParentHomemeaticDevicePortZero Fehler, Name \"".$nameSelect[0]."\" : Homematic Gerät Name falsch, ist ohne Doppelpunkt: $name \n";
                        }

                    $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
                    if (isset($result["Address"])) 
                        {
                        $addressSelect=explode(":",$result["Address"]);
                        if (count($addressSelect)>1)
                            {
                            $port=(integer)$addressSelect[1];
                            if ($port==0) 
                                {
                                if (IPS_GetParent($entry["OID"])!=$parent) 
                                    {
                                    echo "Fehler, Port 0 von \"".$nameSelect[0]."\" wird ignoriert : ".$entry["CONFIG"]." ---> move to $parent\n";
                                    IPS_SetParent($entry["OID"],$parent);
                                    }
                                else echo "Fehler, Port 0 von \"".$nameSelect[0]."\" wird ignoriert : ".$entry["CONFIG"].".\n"; 
                                }
                            }
                        else 
                            {
                            echo "   getDeviceParameter Fehler, Seriennummer ohne Port.\n";
                            }
                        }
                    else 
                        {
                        echo "   getDeviceParameter Fehler, keine Seriennummer.\n";
                        }
                    }
                }
            }
        }     

    /* anhand der Hardware Liste die Instanzen einordnen
     */

    public function setParentHomemeaticDeviceNewOne($hardware,$parent)
        {
        echo "setParentHomemeaticDeviceNewOne aufgerufen.\n";
        foreach ($hardware as $type => $device)
            {
            if ($type=="Homematic") 
                {
                foreach ($device as $name => $entry)
                    {
                    $nameSelect=explode(":",$name);
                    if (count($nameSelect)<2) 
                        {
                        echo "    setParentHomemeaticDeviceNewOne Fehler, Name \"".$nameSelect[0]."\" : Homematic Gerät Name falsch, ist ohne Doppelpunkt: $name \n";
                        }
                    else
                        {
                        if ($this->isNameSuitableforIdent($nameSelect[0])) echo "     setParentHomemeaticDeviceNewOne, New Homematic Device found: Name \"".$nameSelect[0]."\"\n";
                        }    
                    }
                }
            }
        }     

    public function analyseDifferentNamesForDevice($hardware)
        {
        echo "analyseDifferentNamesForDevice aufgerufen.\n";
        $serials=array();
        $types = array();

        foreach ($hardware as $type => $device)
            {
            if ($type=="Homematic") 
                {                
                if (isset($types[$type])) $types[$type]++;
                else $types[$type]=1;
                foreach ($device as $name => $entry)
                    {
                    $nameSelect=explode(":",$name);
                    if (count($nameSelect)<2) 
                        {
                        echo "    analyseDifferentNamesForDevice Fehler, Name \"".$nameSelect[0]."\" : Homematic Gerät Name falsch, ist ohne Doppelpunkt: $name \n";
                        }

                    $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
                    if (isset($result["Address"])) 
                        {
                        $addressSelect=explode(":",$result["Address"]);
                        if (isset($serials[$addressSelect[0]]["Name"]))
                            {
                            if ($serials[$addressSelect[0]]["Name"] != $nameSelect[0]) 
                                {
                                echo "---------------------------------------------------\n";
                                echo "    Fehler : Unterschiedlicher Name für selbes Gerät (".$addressSelect[0]."): ".$serials[$addressSelect[0]]["Fullname"]." (".$serials[$addressSelect[0]]["OID"].") versus ".$name." (".$entry["OID"].") \n";
                                //echo "    "; $this->showInfo($entry["OID"]);
                                //print_r($serials[$addressSelect[0]]);
                                $newIdent=false;
                                if ($this->isNameSuitableforIdent($serials[$addressSelect[0]]["Name"])) 
                                    {
                                    $newIdent=Get_IdentByName2($serials[$addressSelect[0]]["Name"],"_");
                                    $newName=$nameSelect[0];
                                    }
                                if ($this->isNameSuitableforIdent($nameSelect[0])) 
                                    {
                                    $newIdent=Get_IdentByName2($nameSelect[0],"_"); 
                                    $newName=$serials[$addressSelect[0]]["Name"];
                                    }
                                if ($newIdent) 
                                    {
                                    echo "       Neuer Identifier $newIdent gefunden, change if empty. New Name shall be set as well to $newName.\n";
                                    }
                                echo "       "; $result = $this->findNameForDevice($hardware, $nameSelect[0]);
                                //print_r($result);
                                $differentName=false; 
                                //$differentAddress=false;
                                $differentAddress = $addressSelect[0];
                                foreach($result as $entry) 
                                    {
                                    $address=$this->getAddressofHomematicDevice($entry["OID"]);
                                    //if ($differentName === false);
                                    //if ($differentAddress === false) $differentAddress=$address;
                                    if ($differentAddress != $address) echo "         ".IPS_getName($entry["OID"])." ignorieren, $address not same as requested $differentAddress .\n";
                                    else
                                        {
                                        echo "         ".IPS_getName($entry["OID"])." weiter bearbeiten:\n";
                                        echo "              "; $this->showInfo($entry["OID"]);
                                        if ($newIdent) 
                                            {
                                            echo "              "; $this->updateIdent($entry["OID"], $newIdent); 
                                            if ($this->isNameSuitableforIdent($nameSelect[0])) 
                                                {
                                                if (($port=$this->getPortofHomematicDevice($entry["OID"])) !== false )
                                                    {
                                                    $newName1 = $newName.":".$port;                                                   
                                                    echo "               -> Automatic Correction: ".IPS_GetName($entry["OID"])."  changed to $newName1.\n";  
                                                    IPS_SetName($entry["OID"],$newName1);                                 
                                                    }
                                                }
                                            else echo "              name not suitable for change to new name $newName:".$this->getPortofHomematicDevice($entry["OID"])."\n";
                                            }
                                        else echo "              no new ident.\n";
                                        }
                                    }
                                echo "       "; $result = $this->findNameForDevice($hardware, $serials[$addressSelect[0]]["Name"]);
                                foreach($result as $entry) 
                                    {
                                    $address=$this->getAddressofHomematicDevice($entry["OID"]);
                                    //if ($differentAddress === false) $differentAddress=$address;
                                    if ($differentAddress != $address) echo "         ".IPS_getName($entry["OID"])." ignorieren, $address not same as requested $differentAddress .\n";                                        
                                    else
                                        {
                                        echo "         ".IPS_getName($entry["OID"])." weiter bearbeiten:\n";
                                        echo "              "; $this->showInfo($entry["OID"]);
                                        if ($newIdent) 
                                            {
                                            echo "              "; $this->updateIdent($entry["OID"], $newIdent);                                     
                                            if ($this->isNameSuitableforIdent($serials[$addressSelect[0]]["Name"])) 
                                                {
                                                if (($port=$this->getPortofHomematicDevice($entry["OID"])) !== false) 
                                                    {
                                                    $newName1 = $newName.":".$port;
                                                    echo "             -> Automatic Correction: ".IPS_GetName($entry["OID"])." changed to $newName1.\n";  
                                                    IPS_SetName($entry["OID"],$newName1);
                                                    }
                                                }
                                            else echo "              name not suitable for change to new name $newName:".$this->getPortofHomematicDevice($entry["OID"])."\n";
                                            }
                                        else echo "              no new ident.\n";
                                        }
                                    }
                                }
                            }
                        else 
                            {
                            $serials[$addressSelect[0]]["Name"] = $nameSelect[0];
                            $serials[$addressSelect[0]]["Fullname"] = $name;
                            $serials[$addressSelect[0]]["OID"] = $entry["OID"];
                            }
                        if (count($addressSelect)>1)
                            {

                            }
                        else 
                            {
                            echo "   getDeviceParameter Fehler, Seriennummer ohne Port.\n";
                            }
                        }
                    else 
                        {
                        echo "   getDeviceParameter Fehler, keine Seriennummer.\n";
                        }
                    }       // ende foreach
                }           // ende if type
            else 
                {
                if (isset($types[$type])) $types[$type]++;
                else
                    {
                    echo "Type $type wird nicht analysiert\n";
                    $types[$type]=1;
                    }
                }
            }
        }

    public function findNameForDevice($hardware, $nameFind)
        {
        echo "findNameForDevice aufgerufen, wir suchen nach $nameFind.\n";
        $result = array();
        $i=0;
        foreach ($hardware as $type => $device)
            {
            foreach ($device as $name => $entry)
                {
                if (strpos($name, $nameFind) !== false) 
                    {
                    //echo "    -> gefunden, mit OID ".$entry["OID"]."\n";
                    $result[$i]["Name"]=$name;
                    $result[$i]["OID"]=$entry["OID"];
                    $result[$i]["Type"]=$type;
                    $i++;
                    //print_r($entry);
                    }
                }
            }
        return($result);
        }

    private function isNameSuitableforIdent($name)
        {
        $result=false;
        if (strpos($name,'HM-')===0) $result=true; 
        if (strpos($name,'HMIP-')===0) $result=true;
        return ($result);
        }

    private function getAddressofHomematicDevice($oid)
        {
        $result=false;
        $configuration=IPS_GetConfiguration($oid);
        $config=json_decode($configuration,true);   // als array zurückgeben 
        if (isset($config["Address"])) 
            {
            $addressSelect=explode(":",$config["Address"]);
            if (count($addressSelect)>1)       //Doppelpunkt gefunden 
                    $result = $addressSelect[0];
            }
        return ($result);
        }

    private function getPortofHomematicDevice($oid)
        {
        $result=false;
        $configuration=IPS_GetConfiguration($oid);
        $config=json_decode($configuration,true);   // als array zurückgeben 
        if (isset($config["Address"])) 
            {
            $addressSelect=explode(":",$config["Address"]);
            if (count($addressSelect)>1)       //Doppelpunkt gefunden 
                    $result = $addressSelect[1];
            }
        return ($result);
        }

    private function showInfo($oid)
        {
        $ipsOps = new ipsOps();
        //echo "showinfo of $oid:\n";

        $ident=IPS_GetObject($oid)["ObjectIdent"];
        $info=IPS_GetObject($oid)["ObjectInfo"];
        
        echo "Name.Pfad         : ".str_pad($ipsOps->path($oid),45)." ($oid)  Ident: $ident  Info: $info\n";
        }

    private function updateIdent($oid, $newIdent)
        {
        $ident=IPS_GetObject($oid)["ObjectIdent"];
        /* Port dazugeben, damit eindeutig */
        $configuration=IPS_GetConfiguration($oid);
        $config=json_decode($configuration,true);   // als array zurückgeben 
        if (isset($config["Address"])) 
            {
            $addressSelect=explode(":",$config["Address"]);
            if (count($addressSelect)>1)       //Doppelpunkt gefunden 
                {
                $address = $addressSelect[0];
                $port = $addressSelect[1];
                $newIdent .= "_".$port;
                if ($ident != "")
                    {
                    if ($ident != $newIdent)
                        {
                        echo "Unterschiedlicher Identifier $ident und $newIdent. Trotzdem setzen.\n";
                        //IPS_SetIdent($oid, $newIdent);
                        }
                    else echo "Gleicher Identifier $ident und $newIdent. Nichts tun.\n";
                    }
                else 
                    {
                    if ($availableOID=@IPS_GetObjectIDByIdent($newIdent,IPS_GetParent($oid)) ) echo  "      ERROR $availableOID (".IPS_GetName($availableOID).")  has same identifier $newIdent.\n";                       
                    else IPS_SetIdent($oid, $newIdent);
                    }
                }
            }

        }



    }





/********************************************************************************************************************/

/*    FUNKTIONEN       */

/********************************************************************************************************************/
/********************************************************************************************************************/
/********************************************************************************************************************/

/*******************************************************************************************************************
 *
 * class EvaluateHardware
 *      getHomeMaticSockets
 *      getFHTDevices
 *      getFS20EXDevices
 *      getFS20Devices
 *      getHomematicInstances
 *
 *
 **************************************/


class EvaluateHardware
    {

    var $installedModules;                 

    public function __construct()
        {
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        if (!isset($moduleManager))
            {
            IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
            $moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
            }
        $this->installedModules = $moduleManager->GetInstalledModules();
        if (isset($this->installedModules["OperationCenter"])) 
            {
            IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter'); 
            }
        }


    function getHomeMaticSockets(&$includefile)
        {
        /************************************
        *
        *  Wenn vorhanden die Homematic Sockets auflisten, dann kommen die Geräte dran
        *  damit kann die Konfiguration der CCU Anknüpfung wieder hergestellt werden
        *  CCU Sockets werden als function HomematicInstanzen() dargestellt
        *
        ******************************************/

        $ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
        $HomInstanz=sizeof($ids);
        if($HomInstanz == 0)
            {
            //echo "ERROR: Keine HomeMatic Socket Instanz gefunden!\n";         
            $includefile.='function HomematicInstanzen() { return array('."\n";
            $includefile.=');}'."\n\n";		
            }
        else
            {	
            $includefile.='function HomematicInstanzen() { return array('."\n";
            for ($i=0;$i < $HomInstanz; $i++)
                {
                $ccu_name=IPS_GetName($ids[$i]);
                echo "\nHomatic Socket ID ".$ids[$i]." / ".$ccu_name."   \n";
                $config[$i]=json_decode(IPS_GetConfiguration($ids[$i]));
                //print_r($config[$i]);
                
                //$config=IPS_GetConfigurationForm($ids[$i]);
                //echo "    ".$config[$i]."\n";		
                $config[$i]->Open=0;			/* warum wird true nicht richtig abgebildet und muss für set auf 0 geaendert werden ? */
                $configString=json_encode($config[$i]);
                $includefile.='"'.$ccu_name.'" => array('."\n         ".'"CONFIG" => \''.$configString.'\', ';
                $includefile.="\n             ".'	),'."\n";
                //print_r(IPS_GetInstance($instanz));
                }
            $includefile.=');}'."\n\n";
            }
        return (true);
        }


        /************************************
        *
        *  FHT Sender
        *
        ******************************************/

    function getFHTDevices(&$includefile,&$summary)
        {
        $guid = "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}";
        //Auflisten
        $alleInstanzen = IPS_GetInstanceListByModuleID($guid);
        $includefile.='function FHTList() { return array('."\n";
        if (isset($this->installedModules["DetectMovement"]))     $DetectDeviceHandler = new DetectDeviceHandler(); 
        if (isset($this->installedModules["OperationCenter"]))    $DeviceManager = new DeviceManagement();

        echo "\nFHT Geräte Instanzen gefunden: ".sizeof($alleInstanzen)."\n\n";
        foreach ($alleInstanzen as $instanz)
            {
            echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
            if (isset($this->installedModules["DetectMovement"])) $DetectDeviceHandler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren */            
                
            //echo IPS_GetName($instanz)." ".$instanz." \n";
            $includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
            $includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
            $includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';

            if (isset($this->installedModules["OperationCenter"])) $typedev=$DeviceManager->getFS20DeviceType($instanz);  /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
            else $typedev="";
            if ($typedev<>"") 
                {
                $includefile.="\n         ".'"Device" => "'.$typedev.'", ';
                $summary[$typedev][]=IPS_GetName($instanz);
                }    
            
            $includefile.="\n         ".'"COID" => array(';
            $cids = IPS_GetChildrenIDs($instanz);
            //print_r($cids);
            foreach($cids as $cid)
                {
                $o = IPS_GetObject($cid);
                //echo "\nCID :".$cid;
                //print_r($o);
                if($o['ObjectIdent'] != "")
                    {
                    $includefile.="\n                ".'"'.$o['ObjectIdent'].'" => array(';
                    $includefile.="\n                              ".'"OID" => "'.$o['ObjectID'].'", ';
                    $includefile.="\n                              ".'"Name" => "'.$o['ObjectName'].'", ';
                    $includefile.="\n                              ".'"Typ" => "'.$o['ObjectType'].'",), ';
                    }
                }


            $includefile.="\n             ".'	),'."\n";
            $includefile.="\n      ".'	),'."\n";	//print_r(IPS_GetInstance($instanz));
            }
        $includefile.=');}'."\n";
        return (true);
        }

    /************************************
    *
    *  FS20EX Sender
    *
    ******************************************/

    function getFS20EXDevices(&$includefile,&$summary)
        { 
        $guid = "{56800073-A809-4513-9618-1C593EE1240C}";
        //Auflisten
        $alleInstanzen = IPS_GetInstanceListByModuleID($guid);
        $includefile.='function FS20EXList() { return array('."\n";
        if (isset($this->installedModules["DetectMovement"])) $DetectDeviceHandler = new DetectDeviceHandler(); 
        if (isset($this->installedModules["OperationCenter"]))    $DeviceManager = new DeviceManagement_FS20();

        echo "\nFS20EX Geräte: ".sizeof($alleInstanzen)."\n\n";
        foreach ($alleInstanzen as $instanz)
            {
            echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'HomeCode')." ".IPS_GetProperty($instanz,'DeviceList')."\n";
            if (isset($this->installedModules["DetectMovement"])) $DetectDeviceHandler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren */            
                
            //$FS20EXconfig=IPS_GetConfiguration($instanz);
            //print_r($FS20EXconfig);

            $includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
            $includefile.="\n         ".'"HomeCode" => \''.IPS_GetProperty($instanz,'HomeCode').'\', ';
            $includefile.="\n         ".'"DeviceList" => \''.IPS_GetProperty($instanz,'DeviceList').'\', ';
            $includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
            $includefile.="\n         ".'"CONFIG" => \''.IPS_GetConfiguration($instanz).'\', ';		

            if (isset($this->installedModules["OperationCenter"])) $typedev=$DeviceManager->getFS20DeviceType($instanz);  /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
            else $typedev="";
            if ($typedev<>"") 
                {
                $includefile.="\n         ".'"Device" => "'.$typedev.'", ';
                $summary[$typedev][]=IPS_GetName($instanz);
                }

            $includefile.="\n         ".'"COID" => array(';
            $cids = IPS_GetChildrenIDs($instanz);
            //print_r($cids);
            foreach($cids as $cid)
                {
                $o = IPS_GetObject($cid);
                //echo "\nCID :".$cid;
                //print_r($o);
                if($o['ObjectIdent'] != "")
                    {
                    $includefile.="\n                ".'"'.$o['ObjectIdent'].'" => array(';
                    $includefile.="\n                              ".'"OID" => "'.$o['ObjectID'].'", ';
                    $includefile.="\n                              ".'"Name" => "'.$o['ObjectName'].'", ';
                    $includefile.="\n                              ".'"Typ" => "'.$o['ObjectType'].'",), ';
                    }
                }
            $includefile.="\n             ".'	),'."\n";
            $includefile.="\n      ".'	),'."\n";	//print_r(IPS_GetInstance($instanz));
            }
        $includefile.=');}'."\n";
        return (true);
        }

    /************************************
    *
    *  FS20 Sender
    *
    ******************************************/

    function getFS20Devices(&$includefile,&$summary)
        { 
        $guid = "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}";
        //Auflisten
        $alleInstanzen = IPS_GetInstanceListByModuleID($guid);
        $includefile.='function FS20List() { return array('."\n";
        if (isset($this->installedModules["DetectMovement"])) $DetectDeviceHandler = new DetectDeviceHandler();
        if (isset($this->installedModules["OperationCenter"]))    $DeviceManager = new DeviceManagement_FS20();

        echo "\nFS20 Geräte: ".sizeof($alleInstanzen)."\n\n";
        foreach ($alleInstanzen as $instanz)
            {
            echo str_pad(IPS_GetName($instanz),45)." ".$instanz." ".IPS_GetProperty($instanz,'HomeCode')." ".IPS_GetProperty($instanz,'Address').IPS_GetProperty($instanz,'SubAddress')." ".IPS_GetProperty($instanz,'EnableTimer')." ".IPS_GetProperty($instanz,'EnableReceive').IPS_GetProperty($instanz,'Mapping')."\n";
            if (isset($this->installedModules["DetectMovement"])) $DetectDeviceHandler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren */            
                
            //echo IPS_GetName($instanz)." ".$instanz." \n";
            $includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
            $includefile.="\n         ".'"HomeCode" => "'.IPS_GetProperty($instanz,'HomeCode').'", ';
            $includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
            $includefile.="\n         ".'"SubAdresse" => "'.IPS_GetProperty($instanz,'SubAddress').'", ';
            $includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
            $includefile.="\n         ".'"CONFIG" => \''.IPS_GetConfiguration($instanz).'\', ';		

            if (isset($this->installedModules["OperationCenter"])) $typedev=$DeviceManager->getFS20DeviceType($instanz);  /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
            else $typedev="";
            if ($typedev<>"") 
                {
                $includefile.="\n         ".'"Device" => "'.$typedev.'", ';
                $summary[$typedev][]=IPS_GetName($instanz);
                }
                
            $includefile.="\n         ".'"COID" => array(';			
            $cids = IPS_GetChildrenIDs($instanz);
            //print_r($cids);
            foreach($cids as $cid)
                {
                $o = IPS_GetObject($cid);
                //echo "\nCID :".$cid;
                //print_r($o);
                if($o['ObjectIdent'] != "")
                    {
                    $includefile.="\n                ".'"'.$o['ObjectIdent'].'" => array(';
                    $includefile.="\n                              ".'"OID" => "'.$o['ObjectID'].'", ';
                    $includefile.="\n                              ".'"Name" => "'.$o['ObjectName'].'", ';
                    $includefile.="\n                              ".'"Typ" => "'.$o['ObjectType'].'",), ';
                    }
                }
            $includefile.="\n             ".'	),'."\n";
            $includefile.="\n      ".'	),'."\n";	//print_r(IPS_GetInstance($instanz));
            }
        $includefile.=');}'."\n";
        return (true);
        }

    /************************************
    *
    *  Homemeatic Geräte Instanzen
    *
    ******************************************/

    function getHomematicInstances(&$includefile,&$summary)
        {
        $guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
        $HomematicList=array();
        //Auflisten
        $alleInstanzen = IPS_GetInstanceListByModuleID($guid);
        $includefile.='/* HomematicList function automatically generated by EvaluateHardware::getHomematicInstances on '.date("d.m.Y H:i:s").'  */'."\n\n";
        $includefile.='function HomematicList() { return array('."\n";
        if (isset($this->installedModules["DetectMovement"]))     $DetectDeviceHandler = new DetectDeviceHandler(); 
        if (isset($this->installedModules["OperationCenter"]))    $DeviceManager = new DeviceManagement();

        echo "\nHomematic Instanzen von Geräten: ".sizeof($alleInstanzen)."\n\n";
        $i=0;
        foreach ($alleInstanzen as $instanz)
            {
            $HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
            switch (IPS_GetProperty($instanz,'Protocol'))
                {
                case 0:
                        $protocol="Funk";
                        break;
                case 1:
                        $protocol="Wired";
                        break;
                case 2:
                        $protocol="IP";
                        break;
                default:
                        $protocol="Unknown";
                        break;
                }
            $HM_Adresse=IPS_GetProperty($instanz,'Address');
            $result=explode(":",$HM_Adresse);
            $sizeResult=sizeof($result);
            //print_r($result);

            echo str_pad($i,4).str_pad(IPS_GetName($instanz),40)." ".$instanz." ".str_pad($HM_Adresse,22)." ".str_pad($protocol,6)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name;
            $i++;
            if (isset($this->installedModules["DetectMovement"])) $DetectDeviceHandler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren, RSSI Register mit registrieren für spätere geografische Auswertungen */
            //echo "check.\n";
            if ($sizeResult > 1)
                {
                if ($result[1]<>"0")
                    {  /* ignore status channel with field RSSI levels and other informations */
                    $instanzName=IPS_GetName($instanz);
                    $HomematicList[$instanzName]["OID"]=$instanz;
                    $HomematicList[$instanzName]["Adresse"]=IPS_GetProperty($instanz,'Address');
                    $HomematicList[$instanzName]["Name"]=IPS_GetName($instanz);
                    $HomematicList[$instanzName]["CCU"]=$HM_CCU_Name;
                    $HomematicList[$instanzName]["Protocol"]=$protocol;
                    $HomematicList[$instanzName]["EmulateStatus"]=IPS_GetProperty($instanz,'EmulateStatus');

                    $includefile.='"'.$instanzName.'" => array('."\n         ".'"OID" => '.$instanz.', ';
                    $includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
                    $includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
                    $includefile.="\n         ".'"CCU" => "'.$HM_CCU_Name.'", ';
                    $includefile.="\n         ".'"Protocol" => "'.$protocol.'", ';
                    $includefile.="\n         ".'"EmulateStatus" => "'.IPS_GetProperty($instanz,'EmulateStatus').'", ';
                    
                    //echo "Typen und Geräteerkennung durchführen.\n";
                    if (isset($this->installedModules["OperationCenter"])) 
                        {
                        $type    = $DeviceManager->getHomematicType($instanz);           /* wird für Homematic IPS Light benötigt */
                        $typedev = $DeviceManager->getHomematicDeviceType($instanz);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
                        $HMDevice= $DeviceManager->getHomematicHMDevice($instanz);
                        echo "  ".str_pad($type,15)."   $typedev \n";
                        }
                    else { $typedev=""; $type=""; $HMDevice=""; }
                    $result=explode(":",IPS_GetProperty($instanz,'Address'));
                    if ($type<>"") 
                        {
                        $HomematicList[$instanzName]["Type"]=$type;
                        $includefile.="\n         ".'"Type" => "'.$type.'", ';
                        }	
                    if ($typedev<>"")                                               /* Alexa freut sich über Device Angaben, kommt von getHomematicDeviceType/HomematicDeviceType */
                        {
                        $HomematicList[$instanzName]["Device"]=$typedev;
                        $includefile.="\n         ".'"Device" => "'.$typedev.'", ';
                        $summary[$typedev][]=IPS_GetName($instanz);
                        }
                    else 
                        {
                        echo '===============> Error "Device" nicht erkannt.'."\n";
                        print_r($HomematicList[$instanzName]);
                        $typedev = $DeviceManager->getHomematicDeviceType($instanz,0,true);         //noch einmal mit Debug
                        }
                    if ($HMDevice<>"") 
                        {
                        $HomematicList[$instanzName]["HMDevice"]=$HMDevice;
                        $includefile.="\n         ".'"HMDevice" => "'.$HMDevice.'", ';
                        }
                                            
                    $includefile.="\n         ".'"COID" => array(';
                    $cids = IPS_GetChildrenIDs($instanz);
                    //print_r($cids);
                    foreach($cids as $cid)
                        {
                        $o = IPS_GetObject($cid);
                        //echo "\nCID :".$cid;
                        //print_r($o);
                        if($o['ObjectIdent'] != "")
                            {
                            $includefile.="\n                ".'"'.$o['ObjectIdent'].'" => array(';
                            $includefile.="\n                              ".'"OID" => "'.$o['ObjectID'].'", ';
                            $includefile.="\n                              ".'"Name" => "'.$o['ObjectName'].'", ';
                            $includefile.="\n                              ".'"Typ" => "'.$o['ObjectType'].'",), ';
                            }
                        }
                    $includefile.="\n             ".'	),';
                    $includefile.="\n      ".'	),'."\n";	//print_r(IPS_GetInstance($instanz));
                    }
                else
                    {
                    echo "     RSSI Statusvariable, wird nicht im Includefile geführt.\n";
                    }
                }		
            }
	    $includefile.=');}'."\n";
        return ($HomematicList);        
        }

    /************************************
    *
    *  Homemeatic Geräte
    *
    ******************************************/

    function getHomematicDevices(&$includehomematic)
        {
        $guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
        //Auflisten
        $alleInstanzen = IPS_GetInstanceListByModuleID($guid);
        $includehomematic .=	'function getHomematicConfiguration() {'."\n".'            return array('." \n";
        if (isset($this->installedModules["OperationCenter"]))    $DeviceManager = new DeviceManagement();
        
        echo "\nHomematic Geräte auswerten und getHomematicConfiguration() schreiben: \n\n";
        $serienNummer=array(); $i=0;
        foreach ($alleInstanzen as $instanz)
            {
            $HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
            $HM_Adresse=IPS_GetProperty($instanz,'Address');
            $result=explode(":",$HM_Adresse);
            $i++;

            $sizeResult=sizeof($result);
            if ($sizeResult > 1)
                {
                if ($result[1]<>"0")
                    {  /* ignore status channel with field RSSI levels and other informations */
                    if (isset($serienNummer[$HM_CCU_Name][$result[0]]))
                        {
                        $serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]+=1;
                        }
                    else
                        {
                        $serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]=1;
                        $serienNummer[$HM_CCU_Name][$result[0]]["Values"]="";
                        }
                    $serienNummer[$HM_CCU_Name][$result[0]]["Instances"][$result[1]]["OID"]=$instanz;
                    $serienNummer[$HM_CCU_Name][$result[0]]["Instances"][$result[1]]["Values"]="";
                    //echo "Typen und Geräteerkennung durchführen.\n";
                    if (isset($this->installedModules["OperationCenter"])) 
                        {
                        $type    = $DeviceManager->getHomematicType($instanz);           /* wird für Homematic IPS Light benötigt */
                        $typedev = $DeviceManager->getHomematicDeviceType($instanz,0);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
                        $info = $DeviceManager->getHomematicDeviceType($instanz,1);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
                        $HMDevice= $DeviceManager->getHomematicHMDevice($instanz);
                        }
                    else { 
                        $typedev=""; $type="";  
                        }

                    if ($type<>"") 
                        {
                        $includehomematic.='             '.str_pad(('"'.IPS_GetName($instanz).'"'),40).' => array("'.$result[0].'",'.$result[1].',HM_PROTOCOL_BIDCOSRF,'.$type.'),'."\n";
                        //$serienNummer[$HM_CCU_Name][$result[0]]["Instances"][$result[1]]["TYPE"]=$type;
                        }	
                    if ($typedev<>"") 
                        {
                        //$summary[$typedev][]=IPS_GetName($instanz);
                        $serienNummer[$HM_CCU_Name][$result[0]]["Instances"][$result[1]]["TYPEDEV"]=$typedev;
                        }
                    if ($info <> "") $serienNummer[$HM_CCU_Name][$result[0]]["Instances"][$result[1]]["INFO"]=$info; 
                    if ($HMDevice <> "") $serienNummer[$HM_CCU_Name][$result[0]]["Instances"][$result[1]]["HMDevice"]=$HMDevice;                    

                    $cids = IPS_GetChildrenIDs($instanz);
                    //print_r($cids);
                    foreach($cids as $cid)
                        {
                        $o = IPS_GetObject($cid);
                        //echo "\nCID :".$cid;
                        //print_r($o);
                        if($o['ObjectIdent'] != "")
                            {
                            $serienNummer[$HM_CCU_Name][$result[0]]["Values"].=$o['ObjectIdent']." ";
                            $serienNummer[$HM_CCU_Name][$result[0]]["Instances"][$result[1]]["Values"].=$o['ObjectIdent']." ";
                            }
                        }
                    }
                else
                    {
                    //echo "     RSSI Statusvariable, wird nicht im Includefile geführt.\n";
                    }
                }		
            }
        $includehomematic.=');}'."\n";            
        return ($serienNummer);        
        }

    }








?>