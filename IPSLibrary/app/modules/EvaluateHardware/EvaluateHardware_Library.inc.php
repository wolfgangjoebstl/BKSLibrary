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

/* EvaluateHardware Library
 *
 *	
 */



/*
 * class TopologyLibrary
 *
 *
 */

class TopologyLibraryManagement
    {
	
    var $topID;                 // OID der Topology Kategorie, wird automatisch angelegt, wenn nicht vorhanden

    var $modulhandling;         // andere classes die genutzt werden, einmal instanzieren

    var $deviceInstances,$roomInstances,$placeInstances,$devicegroupInstances;                       // Instanzenlisten, für schnelleren Zugriff, müssen regelmaessig upgedatet werden.

    var $debug;
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
        }

    /* die Liste der Instanzen wird gechached und nicht automatisch upgedated.
     * Hier das Update machen
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



    /* Liste aller montierten Sockets ausgeben 
     * Format ist gleich, Key ist der Hardwaretyp dann der Name der Instanz mit den Einträgen OID und CONFIG
     *
     */

    public function get_SocketList($discovery, $debug=false)
        {
        $gateway=array();
        $hardwareTypeDetect = new Hardware();
        foreach ($discovery as $entry)
            {
            $hardwareType = $hardwareTypeDetect->getHardwareType($entry["ModuleID"]);
            if ($hardwareType != false) 
                {
                if ($debug) echo "    get_SocketList, bearbeite $hardwareType \n";
                $objectClassName = "Hardware".$hardwareType;
                $object = new $objectClassName(); 
                $socketID = $object->getSocketID();
                $validModule = @IPS_GetModule($socketID)["ModuleName"];
                if ($validModule != "")
                    {    
                    if ($debug) echo "        SocketID    $socketID    $validModule   \n";
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

    /* Liste aller montierten Hardware Instanzen ausgeben
     * Format ist gleich, Key ist der Hardwaretyp dann der Name der Instanz mit den Einträgen OID und CONFIG
     * übergeben wird eine Liste von Discovery Instanzen
     * aus den Discovery Instanzen wird nur die ModulID übernommen
     */

    public function get_HardwareList($discovery, $debug=false)
        {
        $hardware=array(); 
        $hardwareTypeDetect = new Hardware();
        foreach ($discovery as $entry)
            {
            $hardwareType = $hardwareTypeDetect->getHardwareType($entry["ModuleID"]);
            if ($hardwareType != false) 
                {
                if ($debug) echo "    get_HardwareList: $hardwareType vom ".$entry["ModuleID"]." \n";
                $objectClassName = "Hardware".$hardwareType;
                $object = new $objectClassName(); 
                $deviceID = $object->getDeviceID();
                if ($debug) echo "      DeviceID :    $deviceID \n";
                $devices=$this->modulhandling->getInstances($deviceID);
                foreach ($devices as $device)
                    {
                    if ($debug) echo "           ".IPS_GetName($device)."\n";
                    $config = @IPS_GetConfiguration($device);
                    if ($config !== false) 
                        {                    
                        $hardware[$hardwareType][IPS_GetName($device)]["OID"]=$device;
                        $hardware[$hardwareType][IPS_GetName($device)]["CONFIG"]=$config;
                        }
                    }
                }
            }
        return($hardware);
        }

    /* devicelist, Bestandteil der config::EvaluateHardware_Include   */

    public function get_DeviceList($hardware, $debug=false)
        {
        if ($debug) echo "  get_DeviceList aus dem Modul TopologyLibraryManagement aufgerufen:\n";
        $deviceList=array();
        $hardwareTypeDetect = new Hardware();        
        foreach ($hardware as $hardwareType => $deviceEntries)          // die device types durchgehen HUE, Homematic etc.
            {
            foreach ($deviceEntries as $name => $entry)         // die devices durchgehen, Homematic Devices müssen gruppiert werden 
                {
                if ($debug) echo "      Bearbeite Gerät $name vom Typ $hardwareType:\n";
                $objectClassName = "Hardware".$hardwareType;
                $object = new $objectClassName(); 
                if ($object->getDeviceCheck($deviceList, $name, $hardwareType, $entry, $debug))
                    {
                    //if ($debug) echo "          $objectClassName=>getDeviceParameter aufgerufen:\n";
                    $object->getDeviceParameter($deviceList, $name, $hardwareType, $entry, $debug);             // Ergebnis von erkannten (Sub) Instanzen wird in die deviceList integriert, eine oder mehrer Instanzen einem Gerät zuordnen
                    //if ($debug) echo "          $objectClassName=>getDeviceChannels aufgerufen:\n";
                    $object->getDeviceChannels($deviceList, $name, $hardwareType, $entry, $debug);      // Ergebnis von erkannten Channels wird in die deviceList integriert, jede Instanz wird zu einem oder mehreren channels eines Gerätes
                    //if ($debug) echo "          $objectClassName=>getDeviceActuators aufgerufen:\n";
                    $object->getDeviceActuators($deviceList, $name, $hardwareType, $entry, $debug);             // Ergebnis von erkannten Actuators wird in die deviceList integriert, Acftuatoren sind Instanzen die wie in IPSHEAT bezeichnet sind
                    }
                }
            }
        ksort($deviceList);
        $actuators=$hardwareTypeDetect->getDeviceActuatorsFromIpsHeat($deviceList);
        if ($debug) 
            {
            //print_r($deviceList);
            echo "\n";
            echo "Bereits konfigurierte Actuators aus IPSHeat dazugeben, Ergebnis der Funktion: \n";
            print_r($actuators);
            }
        return($deviceList);
        }


    public function createTopologyInstances($topology)
        {
        echo "\n";
        $onlyOne=true;
        $parent = $this->topID;
        echo "Topology Eintraege durchgehen, als Liste dargestellt, insgesamt ".count($topology)." Einträge:\n";
        foreach($topology as $name => $entry)
            {
            if (isset($entry["Type"]))
                {
                echo "$name with Type ".$entry["Type"]."   \n";
                if ($onlyOne)
                    {
                    switch ($entry["Type"])
                        {
                        case "Place":
                            //print_r($entry);
                            $this->createTopologyInstance($this->placeInstances, $this->placeInstances, $entry, "{4D96B245-6B06-EC46-587F-25E8A323A206}");     // Places können nur in Places eingeordnet werden
                            break;
                        case "Room":
                            //print_r($entry);
                            $this->createTopologyInstance($this->roomInstances, $this->placeInstances, $entry, "{F8CBACC3-6D51-9C88-58FF-3D7EBDF213B5}");      // Rooms können nur in Places vorkommen
                            break;
                        case "Device":
                            /* Devices sind üblicherweise nicht in der Topologyliste. Bei Sonderwünschen halt auch dort eintragen */
                            $this->createTopologyInstance($this->deviceInstances, $this->roomInstances, $entry, "{5F6703F2-C638-B4FA-8986-C664F7F6319D}");      // Devices in Rooms vorkommen
                            $this->createTopologyInstance($this->deviceInstances, $this->devicegroupInstances, $entry, "{5F6703F2-C638-B4FA-8986-C664F7F6319D}");      // Devices in Rooms vorkommen
                            break;
                        case "DeviceGroup":
                            $this->createTopologyInstance($this->devicegroupInstances, $this->roomInstances, $entry, "{CE5AD2B0-A555-3A22-5F41-63CFF00D595F}");      // DeviceGroups können nur in Rooms vorkommen
                            break;
                        default:
                            //$InstanzID = @IPS_GetInstanceIDByName($name, $parent);
                            break;
                        }
                    }
                }
            else echo "$name without Type definition.\n";
            }
        }



    /* Es wird eine Neue Instanz mit der GUID erstellt wenn sie noch nicht in der InstanceList enthalten ist
     * sonst wird sie in die unter entry[parent] genanten Instanz, muss in der ParentList vorhanden sein, einsortiert
     *
     * InstanceList  List of relevant instances, key ist Instance Name
     * ParentList    List of relevant Parent Instances, same format as InstanceList   Name->OID
     * entry
     * GUID for createInstance
     *
     */

    private function createTopologyInstance($InstanceList, $ParentList, $entry, $guid)
        {
        $parent=@IPS_GetObjectIDByName("Topology", 0 );         // Default Parent
        /* gibt es die Instanz mit dem Namen schon. Es wird in der InstanceList gesucht. Wenn nicht erstellen. Wenn schon update des Parents  */
        if ( (isset($InstanceList[$entry["Name"]])) === false)
            {
            $InsID = IPS_CreateInstance($guid);          //Topology Room Instanz erstellen mit dem Namen "Stehlampe"
            if ($InsID !== false)
                {
                IPS_SetName($InsID, $entry["Name"]); // Instanz benennen
                if ($entry["Name"] == $entry["Parent"]) 
                    {
                    echo "    -> Eine neue Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." unter $parent erstellen.\n"; 
                    IPS_SetParent($InsID, $parent); // Instanz einsortieren unter dem angeführten Objekt 
                    }
                else
                    {
                    if (isset($ParentList[$entry["Parent"]])) 
                        {
                        echo "   -> Eine neue Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." unter ".$entry["Parent"]." erstellen.\n"; 
                        IPS_SetParent($InsID, $ParentList[$entry["Parent"]]);
                        }
                    else 
                        {
                        echo "    -> Eine neue Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." vorerst unter $parent erstellen. Wird später einsortiert.\n"; 
                        IPS_SetParent($InsID, $parent);        // Parent noch nicht bekannt, Sauhaufen machen, und später unten korrigieren und neu einordnen
                        }
                    }
                
                //Konfiguration
                //IPS_SetProperty($InsID, "HomeCode", "12345678"); // Ändere Eigenschaft "HomeCode"
                IPS_ApplyChanges($InsID);           // Übernehme Änderungen -> Die Instanz benutzt den geänderten HomeCode
                }
            else echo "!!! Fehler beim Instanz erstellen. Wahrscheinlich ein echo Befehl im Modul versteckt. \n";
            }
        else
            {
            $InstanzID = $InstanceList[$entry["Name"]]; 
            $configTopologyDevice=IPS_GetConfiguration($InstanzID);
            echo "    Die Topology ".$entry["Type"]." Instanz-ID gibt es bereits und lautet: ".IPS_GetName($InstanzID)." (".$InstanzID."). Sie hat die Konfiguration : $configTopologyDevice und liegt unter ".IPS_GetName(IPS_GetParent($InstanzID))."(".IPS_GetParent($InstanzID).").\n";
            if ($entry["Name"] == $entry["Parent"])         // Root
                {
                if ((IPS_GetParent($InstanzID)) != $parent)
                    {
                    echo "    -> Die Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." unter $parent einsortieren.\n"; 
                    IPS_SetParent($InstanzID, $parent); // Instanz einsortieren unter dem angeführten Objekt 
                    }
                }
            else
                {
                    
                if (isset($ParentList[$entry["Parent"]])) 
                    {
                    if ( ($ParentList[$entry["Parent"]]) != (IPS_GetParent($InstanzID)) ) 
                        {
                        echo "    -> Die Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." unter ".$entry["Parent"]." einsortieren.\n"; 
                        IPS_SetParent($InstanzID, $ParentList[$entry["Parent"]]);
                        }
                    }
                else 
                    {
                    echo "Die Topology ".$entry["Type"]." Instanz mit dem Namen ".$entry["Name"]." vorerst unter ".IPS_GetParent($InstanzID)." lassen.\n"; 
                    //IPS_SetParent($InsID, $parent);        // Parent noch nicht bekannt, Sauhaufen machen, und später unten korrigieren und neu einordnen
                    }
                }
            
            //IPS_SetConfiguration($InstanzID,$newconfig);
            //TOPD_SetDeviceList($InstanzID,$instances);
            //if (isset($installedModules["DetectMovement"]))  $Handler->RegisterEvent($InstanzID,'Topology','','');	                    /* für Topology registrieren, ich brauch eine OID damit die Liste erzeugt werden kann */
            }
        }

    /* sortTopologyInstances
     *
     * einsortieren der $devicelist Geräte, check mit $deviceEventList
     *
     */

    public function sortTopologyInstances($deviceList, $channelEventList, $deviceEventList)
        {
        if (isset($this->installedModules["DetectMovement"])) $DetectDeviceListHandler = new DetectDeviceListHandler();   
        $i=0;
        $onlyOne=true;
        $parent=$this->topID;
        foreach ($deviceList as $name => $entry)
            {
            if (isset($entry["Instances"]))
                {
                $instances=$entry["Instances"];
                //if ($onlyOne)
                    {
                    if ( (isset($this->deviceInstances[$name])) === false )
                        {
                        echo str_pad($i,4)."Eine Device Instanz mit dem Namen $name unter ".IPS_GetName($parent)." ($parent) erstellen:\n";
                        $InsID = IPS_CreateInstance("{5F6703F2-C638-B4FA-8986-C664F7F6319D}");          //Topology Device Instanz erstellen 
                        if ($InsID !== false)
                            {
                            IPS_SetName($InsID, $name); // Instanz benennen
                            IPS_SetParent($InsID, $parent); // Instanz einsortieren unter dem angeführten Objekt 
                            
                            //Konfiguration
                            //IPS_SetProperty($InsID, "HomeCode", "12345678"); // Ändere Eigenschaft "HomeCode"
                            IPS_ApplyChanges($InsID);           // Übernehme Änderungen -> Die Instanz benutzt den geänderten HomeCode
                            }
                        else echo "Fehler beim Instanz erstellen. Wahrscheinlich ein echo Befehl im Modul versteckt. \n";
                        }
                    else
                        {
                        $InstanzID = $this->deviceInstances[$name];    
                        echo str_pad($i,4)."Eine Device Instanz mit dem Namen $name unter ".IPS_GetName(IPS_GetParent($InstanzID))." (".IPS_GetParent($InstanzID).") gibt es bereits und lautet: ". $InstanzID."   \n";
                        $room="";
                        foreach ($instances as $instance)
                            {
                            $config="";
                            //print_r($channelEventList[$instance["OID"]]);
                            if (isset($channelEventList[$instance["OID"]])) 
                                {
                                $config=json_encode($channelEventList[$instance["OID"]]);
                                if ($room == "") $room=$channelEventList[$instance["OID"]][1];
                                elseif ($room != $channelEventList[$instance["OID"]][1]) echo "!!!Fehler, die Channels sind in unterschiedlichen Räumen.\n";
                                }
                            //echo "     ".$instance["OID"]."   $config  \n";
                            }
                        if (isset($deviceEventList[$InstanzID]))
                            {
                            //print_r($deviceEventList[$InstanzID]);
                            if ($room != $deviceEventList[$InstanzID][1]) 
                                {
                                echo "      !!!Fehler, die Channels und das Device sind in unterschiedlichen Räumen: \"$room\" \"".$deviceEventList[$InstanzID][1]."\" Zweiten Begriff übernehmen.\n";
                                $room = $deviceEventList[$InstanzID][1];
                                }
                            }
                        if (isset($this->roomInstances[$room]))
                            {
                            //echo "Vergleiche ".IPS_GetParent($InstanzID)." mit ".$roomInstances[$room]."\n";
                            if ( IPS_GetParent($InstanzID) != $this->roomInstances[$room])
                                {
                                echo "    -> Instanz Room vorhanden. Parent auf $room setzen.\n";
                                IPS_SetParent($InstanzID,$this->roomInstances[$room]);
                                }
                            }
                        elseif (isset($this->devicegroupInstances[$room]))    
                            {
                            if ( IPS_GetParent($InstanzID) != $this->devicegroupInstances[$room])
                                {
                                echo "    -> Instanz DeviceGroup vorhanden. Parent $room setzen.\n";
                                IPS_SetParent($InstanzID,$this->devicegroupInstances[$room]);
                                }
                            }

                        $configTopologyDevice=IPS_GetConfiguration($InstanzID);
                        //echo "  Hier ist die abgespeicherte Konfiguration:    $configTopologyDevice \n";
                        /*
                        $oldconfig=json_decode($configTopologyDevice,true);
                        print_r($oldconfig);
                        $oldconfig["UpdateInterval"]=10;
                        $newconfig=json_encode($oldconfig);
                        echo "Neue geplante Konfiguration wäre : $newconfig \n";
                        IPS_SetConfiguration($InstanzID,$newconfig);
                        */
                        TOPD_SetDeviceList($InstanzID,$instances);
                        if (isset($this->installedModules["DetectMovement"]))  $DetectDeviceListHandler->RegisterEvent($InstanzID,'Topology',$room,'');	                    /* für Topology registrieren, ich brauch eine OID damit die Liste erzeugt werden kann */
                        }
                    //$onlyOne=false;
                    $i++;    
                    }
                }           // ende isset instances
            }      // end foreach
        }

    }       // ende class


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
/********************************************************************************************************************/


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
        if (isset($this->installedModules["OperationCenter"]))    $DeviceManager = new DeviceManagement();

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
        if (isset($this->installedModules["OperationCenter"]))    $DeviceManager = new DeviceManagement();

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
        //Auflisten
        $alleInstanzen = IPS_GetInstanceListByModuleID($guid);
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
                    $includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
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
                        $includefile.="\n         ".'"Type" => "'.$type.'", ';
                        }	
                    if ($typedev<>"") 
                        {
                        $includefile.="\n         ".'"Device" => "'.$typedev.'", ';
                        $summary[$typedev][]=IPS_GetName($instanz);
                        }
                    if ($HMDevice<>"") 
                        {
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
        return (true);        
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