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

/* Hardware Library
 *
 * overview of classes
 *
 * Hardware
 *      HardwareDenonAVR extends Hardware
 *      HardwareNetatmoWeather extends Hardware
 *      HardwareHomematic
 *      HardwareHUE
 *      HardwareHUEV2
 *      HardwareHarmony
 *      HardwareFHTFamily
 *      HardwareFS20Family
 *      HardwareFS20ExFamily
 *
 *      HardwareIpsHeat
 *      HardwareEchoControl
 *
 *
 *
 *	summary of class Hardware
 *
 *
 * Hardware
 *    setInstalledModules
 *    getBridgeID
 *    getSocketID
 *    getDeviceID
 *    getHardwareType           liefert einen Clas identifier anhand einer modulID
 *    getDeviceCheck
 *    getDeviceParameter
 *    getDeviceChannels
 *    getDeviceActuators
 *    getDeviceActuatorsFromIpsHeat
 *    getDeviceListFiltered
 *    getDeviceStatistics
 *
 *
 *   HardwareNetatmoWeather
 *   HardwareDenonAVR
 *   HardwareHomematic extends Hardware
 *   HardwareHUE extends Hardware
 *   HardwareHUEV2 extends Hardware
 *   HardwareHarmony extends Hardware
 *   HardwareEchoControl extends Hardware
 *      getDeviceParameter
 * 
 */

/******************************************************************************************************/




/* Klassen die extends nutzen müssen in der richtigen Reihenfolge definiert werden wenn sie mit einer Variablen aufgerufen werden,sonst gibt es einen runtime Fehler
 * unter der Klasse alle Gerätespezifischen Eigenschaften sammeln
 *
 * Hardware wird extended by HardwareHUE, HardwareHomematic, HardwareHarmony
 * Die extension classes müssen immer mit Hardware anfangen
 *
 *      __construct
 *      setInstalledModules
 *      getSocketID
 *      getBridgeID
 *      getDeviceID
 *      getDeviceIDInstances
 *      getHardwareType
 *      getDeviceConfiguration
 *
 *
 *      getDeviceCheck
 *      getDeviceParameter
 *      getDeviceChannels
 *      getDeviceTopology
 *      getDeviceInformation
 *      getDeviceActuators
 *      getDeviceActuatorsFromIpsHeat
 *      getDeviceListFiltered
 *      getDeviceStatistics
 *      ....
 *
 */

class Hardware
    {
	
    protected $socketID, $bridgeID, $deviceID;           // eingeschränkte Sichtbarkeit. Private nicht möglich, da auf selbe Klasse beschränkt
    protected $installedModules;
    protected $modulhandling;         // andere classes die genutzt werden, einmal instanzieren
    protected $createUniqueName, $combineDevices;
    protected $configuration;                               // Configuration für alle gespeichert
    protected $ListofDevices=array();                              // obwohl pro Gerät/entry der devicelist aufgerufen, sollte man das Ganze im Überblick behalten

    /* construct, es gibt einen gemeinsamen config Parameter
     * die class wird in get_DeviceList aufgerufen, für jede Hardware Gruppe/Hersteller/Modul gibt es eine eigene class die diese class als parent haben
     *      uniqueNames     für deviceList Erstellung
     *      combineDevices  true wenn die Geräte zusammengefasst werden sollen
     *      deviceList      die eigentliche Liste zum vergleichen und suchen 
     */
    public function __construct($config=false,$debug=false)
        {
        //echo "parent class Hardware construct.\n";
        $configuration=array();
        $this->combineDevices=false;
        $this->createUniqueName=false;
        if (is_array($config))
            {
            // function configfileParser(&$inputArray, &$outputArray, $synonymArray,$tag,$defaultValue,$debug=false)
            configfileParser($config, $configuration, ["uniqueNames","UNIQUENAMES","Uniquenames","UniqueNames","uniquenames"],"uniqueNames",false,false);
            configfileParser($config, $configuration, ["combineDevices","COMBINEDEVICES","Combinedevices","CombineDevices","combinedevices"],"combineDevices",false,false);
            if ($configuration["combineDevices"]) 
                {
                $this->combineDevices=true; 
                //$this->ListofDevices=$configuration["combineDevices"];                   // nicht setzen, wird Gerätespezifisch bearbeitet, nur den Speicherplatz hier reservieren            
                } 
            $this->createUniqueName=$configuration["uniqueNames"];    
            }
        elseif ($config) $this->createUniqueName=true;
        $this->configuration = $configuration;                  // gemeinsame Configuration, kann auch leer sein
        $this->setInstalledModules();
        $this->modulhandling = new ModuleHandling(); 
        }

    protected function setInstalledModules()
        {
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        if (!isset($moduleManager))
            {
            IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
            $moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
            }
        $this->installedModules = $moduleManager->GetInstalledModules();
        }

    public function getSocketID()
        {
        return ($this->socketID);
        }

    public function getBridgeID()
        {
        return ($this->bridgeID);
        }

    public function getDeviceID()
        {
        return ($this->deviceID);
        }

    /* etwas variablere Verarbeitung und Nutzung von ModulIDs, es müssen keine instanzen sein, kann auch was anderes zurückgegeben werden */
    
    public function getDeviceIDInstances()
        {
        return ($this->modulhandling->getInstances($this->getDeviceID()));
        }

    public function getHardwareType($moduleID)
        {
        switch ($moduleID)
            {
            case "{3718244C-71A2-B20D-F754-DF5C79340AB4}":          // Homematic Discovery
            case "{5214C3C6-91BC-4FE1-A2D9-A3920261DA74}":          // HomeMatic Configurator
                $hardwareType="Homematic";
                break;
            case "{91624C6F-E67E-47DA-ADFE-9A5A1A89AAC3}":          // HomematicExtended Instanzen, ModuleID erfunden
                $hardwareType="HomematicExtended";  
                break;                
            case "{E4B2E379-63A8-4B79-3067-AF906DA91C33}":          // HUE Discovery
            case "{EE92367A-BB8B-494F-A4D2-FAD77290CCF4}":          // HUE Configurator
                $hardwareType="HUE";                
                break;
            case "{A1B07C97-AA56-4C8A-81E4-2A4C41A8725C}":          // HUE Discovery V2, neues store Modul
                $hardwareType="HUEV2";                
                break;                
            case "{22F51957-348D-9A73-E019-3811573E7CA2}":          // Harmony Discovery
            case "{E1FB3491-F78D-457A-89EC-18C832F4E6D9}":          // Harmony Configurator
                $hardwareType="Harmony";  
                break;
            case "{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}":          // EchoControl Configurator, kein automatisches Discovery über System, chekt regelmaessig
                $hardwareType="EchoControl";  
                break;
            case "{4A76B170-60F9-C387-2303-3E3587282296}":          // Denon Discovery
                $hardwareType="DenonAVR";  
                break;
            case "{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}":          // Netatmo Configurator
                $hardwareType="NetatmoWeather";  
                break;
            case "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}":          // FHT Instanzen
                $hardwareType="FHTFamily";  
                break;
            case "{56800073-A809-4513-9618-1C593EE1240C}":          // FS20EX Instanzen
                $hardwareType="FS20EXFamily";  
                break;
            case "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}":          // FS20 Instanzen
                $hardwareType="FS20Family";  
                break;
            case "{D26101C0-BE49-7655-87D3-D721064D4E40}":          // OpCentCam Instanzen, ModuleID erfunden
                $hardwareType="OpCentCam";  
                break;
            case "{81F09287-FDDF-204E-98CB-30B27D106ECE}":           // IPSHeat Instanzen, ModuleID erfunden 
                $hardwareType="IPSHeat";
                break;
            default:
                echo "getHardwareType, hardwareType unknown for ModuleID $moduleID.";
                $hardwareType=false;
                break;
            }            
        return ($hardwareType);
        }


    /* Hardware::getDeviceConfiguration für class Hardware
     * kein check im default ob der Name bereits verwendet wird, es wird in der Hardwarelist überschrieben
     */

    public function getDeviceConfiguration(&$hardware, $device, $hardwareType, $debug=false)
        {
        $config = @IPS_GetConfiguration($device);
        if ($config !== false) 
            {                    
            $hardware[$hardwareType][IPS_GetName($device)]["OID"]=$device;
            $hardware[$hardwareType][IPS_GetName($device)]["CONFIG"]=$config;
            }
        }

    /* Hardware::getDeviceCheck
     * Allgemeine Routine, die Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * wenn alles in Ordnung wird $deviceList[$name]["Name"]=$name; angelegt.
     *
     * es wird geprüft ob der name als Index bereits vergeben ist, Index muss ein uniqueName sein.
     * wenn config für createUniqueName ein true ist dann bei doppelten Namen einen neuen Namen definieren
     *
     * 
     * wird überschrieben von HardwareHomematic und HardwareHomematicExtended
     *
     * Antwort ist true wenn alles in Ordnung verlaufen ist. Ein false führt dazu dass kein Eintrag erstellt wird.
     *
     *  if getDeviceCheck(....)                             // verschiedene checks, zumindest ob schon angelegt ?
     *       {
     *       $object->getDeviceParameter(....);             // Ergebnis von erkannten (Sub) Instanzen wird in die deviceList integriert, eine oder mehrer Instanzen einem Gerät zuordnen
     *       $object->getDeviceChannels(....);              // Ergebnis von erkannten Channels wird in die deviceList integriert, jede Instanz wird zu einem oder mehreren channels eines Gerätes
     *       $object->getDeviceActuators(....);             // Ergebnis von erkannten Actuators wird in die deviceList integriert, Acftuatoren sind Instanzen die wie in IPSHEAT bezeichnet sind
     *       $object->getDeviceInformation(....);             // zusaetzlich Geräteinformation, auch das Gateway
     *       $object->getDeviceTopology(....);              // zusaettlich Topolgie Informationen ablegen
     *       }
     *
     *
     */

    public function getDeviceCheck(&$deviceList, &$name, $type, $entry, $debug=false)
        {
        if ($this->createUniqueName)            // mit uniqueNames arbeiten
            {
            /* Fehlerprüfung */
            if (isset($deviceList[$name])) 
                {                    
                if ($debug) echo "          ------------------------------------------------------------------------------------------------------\n";
                echo "          getDeviceCheck:Allgemein, create unique Names aktiviert, Name wird von $name auf $name$type geändert:\n";
                $realname=$name;
                $name = $name.$type;            // wird zurückgegeben, nur ein Pointer
                $deviceList[$name]["Name"]=$realname;
                $deviceList[$name]["Type"]=$type;
                return (true);
                }
            else 
                {
                $deviceList[$name]["Name"]=$name;
                $deviceList[$name]["Type"]=$type;
                return (true);
                }
            }
        else                                // bei gleichen Namen abbrechen
            {
            /* Fehlerprüfung */
            if (isset($deviceList[$name])) 
                {                 
                echo "          >>getDeviceCheck:Allgemein   Fehler, Name \"$name\" bereits definiert. Anforderung nach Type $type wird ignoriert.\n";                    
                print_r($deviceList[$name]);
                return(false);
                }
            else return (true);
            }            
        }

    /* Hardware::getDeviceParameter
     * wenn  getDeviceCheck positiv ist hier weitermachen und einen devicelist Eintrag anlegen
     *
     * die Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist. Die übergebenen Parameter eintragen
     *
     * Entry wird direkt in die Devicelist unter Instances integriert, 
     * Name ist der Key des Eintrags mit dem integriert wird, 
     * Subkategorie ist eben Instances, 
     * Entry ist der Wert der eingesetzt wird 
     * entry besteht aus OID und CONFIG
     *
     * Das Gerät Name wird um den Typ Type erweitert, beim Eintrag entry wird der Name zusätzlich gespeichert
     *
     * Type ist ein muss für die Devicelist, getComponent macht die Erkennung darauf basierend
     *
     */

    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo"          getDeviceParameters:Allgemein aufgerufen. Eintrag \"$name\" hinterlegt.\n";            
        $deviceList[$name]["Type"]=$type;
        if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"];           // Index und Name sind unterschiedlich
        else $entry["NAME"]=$name;
        $deviceList[$name]["Instances"][]=$entry;
        return (true);
        }

    /* Hardware::getDeviceChannels
     * die Device Liste (Geräteliste) um die Channels erweitern, ein Gerät kann mehrere Instances und Channels haben, 
     * es können mehr channels als instances sein, es können aber auch gar keine channels sein - eher unüblich
     * zumindest RegisterAll schreiben
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)                 // class Hardware
        {
        if ($debug) echo "          getDeviceChannels:Allgemein aufgerufen für ".$entry["OID"]." mit $name $type. Keine Funktion hinterlegt.\n";
        //print_r($deviceList[$name]["Instances"]);
        //print_r($entry);
        $oids=array();
        foreach ($deviceList[$name]["Instances"] as $port => $register) 
            {
            $oids[$register["OID"]]=$port;
            }
        if (isset($oids[$entry["OID"]])===false)             
            {
            echo "  >> irgendetwas ist falsch.\n";
            return (false);                                     // nix zum tun, Abbruch
            }
        foreach ($oids as $oid => $port)
            {
            $cids = IPS_GetChildrenIDs($oid);
            $register=array();
            foreach($cids as $cid)
                {
                $register[]=IPS_GetName($cid);
                }
            sort($register);
            $registerNew=array();
            $oldvalue="";        
            /* gleiche Einträge eliminieren */
            foreach ($register as $index => $value)
                {
                if ($value!=$oldvalue) {$registerNew[]=$value;}
                $oldvalue=$value;
                } 
            $deviceList[$name]["Channels"][$port]["RegisterAll"]=$registerNew;
            if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["NAME"]=$deviceList[$name]["Name"];           // Index und Name sind unterschiedlich
            else $deviceList[$name]["Channels"][$port]["NAME"]=$name;
            }
        return (true);
        }

    /* die Device Liste (Geräteliste) um die Beschreibung der Topologie erweitern
     *
     */

    public function getDeviceTopology(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo"          getDeviceTopology:Allgemein aufgerufen. Keine Funktion hinterlegt.\n";
        return (true);
        }

    /* die Device Liste (Geräteliste) um die Beschreibung der Devices erweitern, ein Gerät pro Device, die Hardware selber
     *
     */

    public function getDeviceInformation(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo"          getDeviceInformation:Allgemein aufgerufen. Keine Funktion hinterlegt.\n";
        return (true);
        }

    /* Hardware::getDeviceActuators
     * die Device Liste (Geräteliste) um die Actuators erweitern, ein Gerät kann Actuators haben, muss aber nicht
     * für jede Hardware Gerätetype individuell, oder besser am Ende mit getDeviceActuatorsFromIpsHeat für alle gemeinsam auf einmal
     */

    public function getDeviceActuators(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo"          getDeviceActuators:Allgemein aufgerufen. Keine Funktion hinterlegt.\n";
        return (true);
        }

    /* Hardware::getDeviceActuatorsFromIpsHeat
     * die Device Liste (Geräteliste) um die Actuators erweitern, gemeinsam für alle, da mehr von Stromheizung IPS_HEAT abhängig als von der Harwdare
     * direkter Aufruf dieser class, modul Stromheizung muss installiert sein, die ganze Config einlesen und mit der ganzen deviceList abgleichen
     * aus der Stromheizung Config von IPSHEAT_COMPONENT die einzige OID rausziehen die es gibt
     * deviceList erweitern, wenn OID bei instanzdefinition vorhanden
     *
     */
    public function getDeviceActuatorsFromIpsHeat(&$deviceList, $debug=false)
        {
        /* IPS Heat analysieren */
        $actuators = array();
        $channels  = array();
        if ( isset($this->installedModules["Stromheizung"]) )
            {
            echo "\nStromheizung ist installiert. Configuration auslesen. Devicelist mit Aktuatoren anreichern:\n";
            IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");		
            IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");		
            IPSUtils_Include ("IPSHeat_Constants.inc.php",      "IPSLibrary::app::modules::Stromheizung");		
            IPSUtils_Include ("Stromheizung_Configuration.inc.php",  "IPSLibrary::config::modules::Stromheizung");
            
            $ipsheatManager = new IPSHeat_Manager();            // class from Stromheizung

            // einzelne IPSHeat Aktuatoren
            $IPSLightObjects=IPSHeat_GetHeatConfiguration();
            foreach ($IPSLightObjects as $name => $object)
                {
                $components=explode(",",$object[IPSHEAT_COMPONENT]);
                $text = "  ".str_pad($name,30).str_pad($object[IPSHEAT_TYPE],15).str_pad($components[0],35);           // strukturiert ausgeben und wenn Component bekannt ist erweitern
                switch (strtoupper($components[0]))
                    {
                    case "IPSCOMPONENTSWITCH_HOMEMATIC":
                    case "IPSCOMPONENTDIMMER_HOMEMATIC":                
                    case "IPSCOMPONENTRGB_PHUE":
                    case "IPSCOMPONENTRGB_LW12":
                    case "IPSCOMPONENTHEATSET_HOMEMATIC":
                    case "IPSCOMPONENTHEATSET_HOMEMATICIP":                
                        if (@IPS_ObjectExists($components[1])) 
                            {
                            $text .= $components[1]."   ".IPS_GetName($components[1]);
                            if ($debug) echo $text;
                            }
                        else 
                            {
                            $text .= $components[1]."   Error, Object does not exist -------------------------\n";                    
                            echo $text."\n";
                            }
                        //echo $components[1]."   ".IPS_GetName($components[1]);
                        $actuators[$components[1]]["ComponentName"]=$components[0];
                        $actuators[$components[1]]["Type"]=$object[IPSHEAT_TYPE];
                        if (isset($deviceList[$name]["Name"])) $actuators[$components[1]]["Name"]=$deviceList[$name]["Name"];           // Name als Referenz, damit wir uns besser auskennen
                        else $actuators[$components[1]]["Name"]=$name;
                        $actuators[$components[1]]["Category"]="Switches";
                        break;
                    case "IPSCOMPONENTHEATSET_FS20":                              // remote Adresse, OID nicht vorhanden
                    case "IPSCOMPONENTTUNER_DENON":
                    case "IPSCOMPONENTSWITCH_RFS20":
                    case "IPSCOMPONENTSWITCH_RMONITOR":
                        $text .= "kein Eintrag, keine lokale Instanz referenziert : ".$object[IPSHEAT_COMPONENT];
                        echo $text."\n";
                        break;
                    default:
                        $text .=  "  unbekannter Component -------------------> ".strtoupper($components[0]);
                        echo $text."\n";
                        break;
                    }
                switch (strtoupper($object[IPSHEAT_TYPE]))
                    {
                    case "AMBIENT":

                    case "DIMMER":

                    case "RGB":

                    case "SWITCH":

                    }
                if ($debug )echo "\n";	
                }

            // einzelne IPSHeat Group Aktuatoren
            $IPSGroupObjecs = IPSHeat_GetGroupConfiguration();
            $config = $ipsheatManager->getConfigGroups($IPSGroupObjecs);
            //print_R($config);    
            foreach ($config as $index => $entry)
                {
                if (isset($entry["Type"]))
                    {
                    $entry["Category"]="Groups";
                    $actuators[$ipsheatManager->GetGroupIdByName($entry["Name"])]=$entry;
                    }                    
                }

            // einzelne IPSHeat Programs Aktuatoren
            $IPSProgramObjecs = IPSHeat_GetProgramConfiguration();
            $config = $ipsheatManager->getConfigPrograms($IPSProgramObjecs);
            foreach ($config as $index => $entry)
                {
                // Anpassungen bereits in getDeviceConfiguration erledigt, hier noch einmal
                $configProgram["Scenes"] = $entry;
                $configProgram["Type"]="Program";
                $configProgram["Category"]="Programs";
                $configProgram["Name"]=$index;
                $reference =$ipsheatManager->GetProgramIdByName($configProgram["Name"]);
                echo "getDeviceActuatorsFromIpsHeat Program $reference ".json_encode($configProgram)."\n";
                $actuators[$reference]=$configProgram;
                }
            }
        //print_r($actuators); 

        /* Liste der Aktuatoren in die Deviceliste kopieren, Aktuatoren sind nach OIDs der Instanzen sortiert, es braucht eine oder mehrere Instanzen 
         * wenn es eine Instanz mit der Angabe einer OID gibt und einen Aktuator in IPS_HEAT mit der selben OID wird kopiert und der Eintrag um Actuators erweitert
         *
         */
        foreach ($deviceList as $name => $entry)
            {
            if (isset($entry["Instances"]))
                {
                foreach ($entry["Instances"] as $port => $instance)
                    {
                    if ( (isset($instance["OID"])) && (isset($actuators[$instance["OID"]])) ) 
                        {
                        $deviceList[$name]["Actuators"][$port]=$actuators[$instance["OID"]];
                        $actuators[$instance["OID"]]["result"]="copied";
                        }
                    }
                }
            }                    
        return ($actuators);
        }

    /* die devicelist wird nach bestimmten Filterkriterien gefiltert und durchsucht.
     * Der Filter ist ähnlich wie bei anderen Routinen mit Key => needle generisch aufgebaut
     * die Filter werden der Reihe nach auf die noch verbleibende Menge angewendet
     * Es gibt vorgefertigte Filter Keys:
     *      Type        oder andere, generisch es wird nach dem devicelist[Key] gesucht, die richtige Schreibweise des Keys ist erforderlich
     *      TypeDev     Instanzen durchsuchen
     *      TypeChan    Channels durchsuchen
     *      Register    Register in Channels durchsuchen, falls es sie gibt, moderne Darstellung ist jetzt TYPECHAN OID Keys
     *
     *
     */

    public function getDeviceListFiltered($deviceList,$filter=array(),$output=false, $debug=false)
        {
        if ($debug)
            {
            echo "getDeviceListFiltered aufgerufen. Filter deviceList with: ".json_encode($filter)."\n";
            //print_r($filter);
            }
        $result=array();  $install=array();
        if (is_array($filter))
            {
            $result=$deviceList;    // mit der ganzen Liste anfangen, result ist das Ergebnis der vorigen Runde
            foreach ($filter as $key => $needle)
                {
                $deviceListInput = $result;     // und dann langsam je Runde kleiner machen
                $result=array();                 // result wieder loeschen und neu ermitteln
                echo "    Filter $key anwenden auf ".count($deviceListInput)." Eintraege und dabei $needle suchen.\n";
                foreach ($deviceListInput as $device => $entry)
                    {
                    switch (strtoupper($key))
                        {
                        case "TYPEDEV":     /* Instances durchsuchen */
                            //$found=false;
                            if (isset($entry["Instances"]))
                                {
                                foreach ($entry["Instances"] as $instance)
                                    {
                                    if (isset($instance[$key]))
                                        {
                                        if ($instance[$key] == $needle)  
                                            {
                                            //$found = true;
                                            echo "           TYPEDEV: Eintrag $device mit $key gleich $needle gefunden.\n";                                            
                                            $result[$device] = $entry;
                                            }
                                        //else echo $instance[$key]." ";
                                        } 
                                    }                                
                                }
                            //if ($found) $result[$device] = $entry;
                            break;
                        case "TYPECHAN":    /* Channels durchsuchen  auch TYPECHAN Einträge die durch Beistrich getrennt sind */
                            //$found=false;
                            if (isset($entry["Channels"]))
                                {
                                foreach ($entry["Channels"] as $instance)
                                    {
                                    //print_r($instance);
                                    if (isset($instance[$key]))         /* gibt es denn eine TYPECHAN Eintrag im Array */
                                        {
                                        $keysearch=explode(",",$instance[$key]);
                                        //echo "Wir suchen hier nach  "; print_r($keysearch); echo "\n";
                                        //if ($instance[$key] == $needle)  
                                        if ( array_search($needle,$keysearch) !== false)
                                            {
                                            //$found = true;
                                            echo "           TYPECHAN: Eintrag $device mit $key gleich $needle gefunden.\n";                                            
                                            $result[$device] = $entry;
                                            }
                                        //else echo $instance[$key]." ";
                                        } 
                                    }                                
                                }
                            //if ($found) $result[$device] = $entry;
                            break;
                        case "REGISTER":     /* Register Definitionen durchsuchen, Channels aufmachen, alle Channels durchgehen,  */
                            //$found=false;
                            if (isset($entry["Channels"]))
                                {
                                foreach ($entry["Channels"] as $instance)
                                    {
                                    if (isset($instance["Register"]))
                                        {                     
                                        foreach ($instance["Register"] as $key => $varName)    
                                            {
                                            if ($key == $needle)
                                                {
                                                //$found = true;
                                                echo "           REGISTER: Eintrag $device mit $key gleich $needle gefunden.\n";                                            
                                                $result[$device] = $entry;
                                                }
                                            }
                                        //else echo $instance[$key]." ";
                                        } 
                                    }                                
                                }
                            //if ($found) $result[$device] = $entry;
                            break;
                        case "TYPE":            // generisch, auch für SubType etc.
                        default:
                            if ( (isset($entry[$key])) && ($entry[$key] == $needle) )
                                {
                                echo "           TYPE: Eintrag $device mit $key gleich $needle gefunden.\n";
                                $result[$device] = $entry;
                                }
                            break;
                        }           // ende switch
                    }           // ende foreach
                }           // ende foreach
            switch (strtoupper($output))
                {
                case "INSTALL":
                    return($result);                
                    break;
                default:
                    return($result);                
                    break;
                }
            }
        else return(false);         // kein Filter angesetzt, hier false ausgeben, oder alternativ wäre es möglich die ganze devicelist auszugeben
        }

    /* Plausi und Syntax Check der devicelist. Als Ergebnis werden statistische  Auswertungen geliefert. 
     * Fehlermeldungen werden als echo ausgegeben
     * es erfolgen keine Registrierungen
     * für jeden einzelnen Type, TYPEDEV und TYPECHAN werden Counter angelegt
     *
     * jedes Gerät hat mehrere Instances aber nur einen Type
     *       Type
     *       Instances   TYPEDEV
     *       Channels    TYPECHAN
     */

    public function getDeviceStatistics($deviceList,$warning=true)
        {
        $statistic=array();
        foreach ($deviceList as $name => $entry)
            {
            if (isset($entry["Type"])) 
                {
                if (isset($statistic[$entry["Type"]])) $statistic[$entry["Type"]]["Count"]++;
                else $statistic[$entry["Type"]]["Count"]=1; 
                }
            if (isset($entry["Instances"]))
                {
                /* Geräte zählen, also wie oft kommt Instances vor */
                if (isset($statistic[$entry["Type"]]["Instances"])) $statistic[$entry["Type"]]["Instances"]["Count"]++;
                else $statistic[$entry["Type"]]["Instances"]["Count"]=1;
                /* Instanzen der Geräte im Total zählen */
                foreach ($entry["Instances"] as $instance)
                    {
                    if (isset($statistic[$entry["Type"]]["Instances"]["CountInstances"]))           // Instanzen im Total zählen
                        {
                        $statistic[$entry["Type"]]["Instances"]["CountInstances"]++;
                        }
                    else 
                        {
                        $statistic[$entry["Type"]]["Instances"]["CountInstances"]=1; 
                        }                     
                    if (isset($instance["TYPEDEV"]))
                        {    
                        if (isset($statistic[$entry["Type"]]["Instances"][$instance["TYPEDEV"]])) $statistic[$entry["Type"]]["Instances"][$instance["TYPEDEV"]]["Count"]++;
                        else $statistic[$entry["Type"]]["Instances"][$instance["TYPEDEV"]]["Count"]=1; 
                        }
                    else 
                        {
                        if (isset($entry["Type"])) 
                            {
                            if ($warning) echo "TYPEDEV in den ".$entry["Type"]." Instances für $name nicht definiert.\n";
                            }
                        else echo "TYPEDEV in den Instances für $name nicht definiert.\n";
                        }
                    }
                } 
            if (isset($entry["Channels"]))
                {
                /* Channels zählen, also wie oft kommt die Instanz Channels vor */
                if (isset($statistic[$entry["Type"]]["Channels"]))                                      // Channels zählen
                    {
                    $statistic[$entry["Type"]]["Channels"]["Count"]++;
                    //$statistic[$entry["Type"]]["Channels"]["List"] .= ";".$name;
                    }
                else 
                    {
                    $statistic[$entry["Type"]]["Channels"]["Count"]=1; 
                    //$statistic[$entry["Type"]]["Channels"]["List"] = $name;
                    }
                /* einzelne Channels im Total zählen */
                foreach ($entry["Channels"] as $channel)
                    {
                    if (isset($statistic[$entry["Type"]]["Channels"]["CountChannels"]))         // alle Channels im Total zaehlen 
                        {
                        $statistic[$entry["Type"]]["Channels"]["CountChannels"]++;
                        }
                    else 
                        {
                        $statistic[$entry["Type"]]["Channels"]["CountChannels"]=1; 
                        }                    
                    if (isset($channel["TYPECHAN"]))
                        {
                        /* es gibt Untergruppen im Typechan, also vorher aufteilen */
                        $typechans = explode(",",$channel["TYPECHAN"]);
                        foreach ($typechans as $typechan)
                            {                    
                            if (isset($statistic[$entry["Type"]]["Channels"][$typechan])) 
                                {
                                $statistic[$entry["Type"]]["Channels"][$typechan]["Count"]++;
                                $statistic[$entry["Type"]]["Channels"][$typechan]["List"] .= ";".$channel["Name"];
                                }
                            else 
                                {
                                $statistic[$entry["Type"]]["Channels"][$typechan]["Count"]=1; 
                                $statistic[$entry["Type"]]["Channels"][$typechan]["List"] = $channel["Name"];
                                }
                            }
                        }
                    else echo "TYPECHAN in den Channels für $name nicht definiert.\n";
                    }
                }            
            }    
        return ($statistic);
        }

    /* Statistische Register Auswertung der devicelist.
     * für jedes einzelne Register im Channel werden Counter angelegt
     *
     * jedes Gerät hat mehrere Instances aber nur einen Type
     *       Type
     *       Instances   [...] TYPEDEV
     *       Channels    [...] TYPECHAN
     */

    public function getRegisterStatistics($deviceList,$warning=true)
        {
        $statistic=array();
        foreach ($deviceList as $name => $entry)                        // Nach Name durchgehen
            {
            if (isset($entry["Channels"]))
                {
                /* Ein Channel hat mehrere Subchannels */
                foreach ($entry["Channels"] as $channel)                // alle Channels anschauen
                    {
                    if (isset($channel["TYPECHAN"]))
                        {
                        /* es gibt Untergruppen im Typechan, also vorher aufteilen */
                        $typechans = explode(",",$channel["TYPECHAN"]);
                        foreach ($typechans as $typechan)
                            {                    
                            if (isset($statistic[$typechan])) 
                                {
                                $statistic[$typechan]["Count"]++;
                                }
                            else 
                                {
                                $statistic[$typechan]["Count"]=1; 
                                }
                            if (isset($channel[$typechan]))
                                {
                                foreach ($channel[$typechan] as $register=>$name) 
                                    {
                                    if (isset($statistic[$typechan][$register])) 
                                        {
                                        $statistic[$typechan][$register]["Count"]++;
                                        }
                                    else 
                                        {
                                        $statistic[$typechan][$register]["Count"]=1; 
                                        }
                                    }   // ende foreach
                                }   
                            }
                        }
                    }
                }            
            }    
        return ($statistic);
        }

    /* Ausgeben der statistische Register Auswertung der devicelist.
     * für jedes einzelne Register im Channel wurden von getRegisterStatisticsCounter angelegt
     *
     * jedes Gerät hat mehrere Instances aber nur einen Type
     *       Type
     *       Instances   [...] TYPEDEV
     *       Channels    [...] TYPECHAN
     *
     * Ausgabe der Zusammenfassung in komprimierter Form
     */

    public function writeRegisterStatistics($statistics)
        {
        foreach ($statistics as $name => $device)
            {
            echo "   ".str_pad($name,26).$device["Count"]." \n";
            //print_R($device);
            foreach ($device as $type => $register)
                {
                switch ($type)
                    {
                    case "Count":
                        break;
                    default:    
                        echo "      ".str_pad($type,32).$register["Count"]."   \n";
                        break;
                    }    
                }
            }
        }

    }       // ende class


/* Objektorientiertes class Management für Geräte (Hardware)
 * Hier gibt es Hardware spezifische Routinen die die class hardware erweitern.
 *
 */

class HardwareDenonAVR extends Hardware
    {

    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "{9AE3087F-DC25-4ADB-AB46-AD7455E71032}";           // I/O oder Splitter ist der Socket für das Device
        $this->bridgeID = "{4A76B170-60F9-C387-2303-3E3587282296}";           // Configurator
        $this->deviceID = "{DC733830-533B-43CD-98F5-23FC2E61287F}";           // das Gerät selbst        
        parent::__construct($config, $debug); 
        }
    }

/* Objektorientiertes class Management für Geräte (Hardware)
 * Hier gibt es Hardware spezifische Routinen die die class hardware erweitern.
 * für Library HomematicExtended, CCU Objekte für CCU2 und getrennte Objekte RF und IP auch für CCU3
 * erweitert HardwareHomematic um
 *      getDeviceCheck      vereinfacht
 *      getDeviceParameter
 *      getDeviceChannels
 *      getDeviceInformation
 *      checkConfig
 */

class HardwareHomematicExtended extends HardwareHomematic
    {

    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        parent::__construct($config, $debug);                                            // am Anfang sonst wird DeviceID doppelt geschrieben, da ja HardwareHomatic erweitert wird                                       
        $this->socketID = "{6EE35B5B-9DD9-4B23-89F6-37589134852F}";             // I/O oder Splitter ist der Socket für das Device
        $this->bridgeID = "{91624C6F-E67E-47DA-ADFE-9A5A1A89AAC3}";             // Configurator
        $this->deviceID = "{36549B96-FA11-4651-8662-F310EEEC5C7D}";             // das Gerät selbst
        }

    /* HardwareHomematicExtended::getDeviceCheck
     * Übergabe deviceList und name der Instanz, name besteht aus 2 Teilen mit : getrennt
     * entry beinhaltet die Configuration unter ["CONFIG"], Configuration ist json encoded
     * entry beinhaltet auch ["OID"] , check wenn OperationCenter Modul installiert ist
     *
     * Rückgabe false wenn
     *    wenn Name ohne Doppelpunkt
     *    es gibt eine Adresse mit einem : in der Konfiguration
     *    kein Port 0 in der Adresse
     *    wenn OperationCenter Modul installiert ist 
     *      Matrix Auswertung erfolgreich
     *      Port muss in Matrix ein Index sein mit Eintrag 1 oder größer
     *      typedev Auswertung muss auch erfolgreich sein
     *
     * Homematic, die Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist. Ein false führt dazu dass kein Eintrag erstellt wird.
     *
     * Ein Homematic Device kann aus mehreren Instances bestehen, zuerst prüfen ob aus einer Instanz heraus das Device bereits angelegt wurde
     * der Name der Instanz muss immer einen Doppelpunkt haben, vor dem Doppelpunkt ist der Name des Gerätes
     * In entry gibt es ["CONFIG"]["Address"] mit der Homematic Adresse
     *
     * DeviceList wird nicht abgeändert
     * verwendet einheitlich getHomematicDeviceType
     */

    public function getDeviceCheck(&$deviceList, &$name, $type, $entry, $debug=false)
        {
        if ($debug) echo "HardwareHomematicExtended::getDeviceCheck, $name, keine Prüfung auf : im Namen und der Adresse.\n";

        /* Fehlerprüfung anhand der Seriennummer, Adresse. Port 0 wird nicht ausgewertet */
        $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
        if (isset($result["Address"])===false) 
            {
            echo "       >>HardwareHomematicExtended::getDeviceCheck Fehler, keine Seriennummer.\n";
            print_r($result);
            return (false);
            }

        /* erweiterte Fehlerprüfung wenn OperationCenter, check Matrix */
        if (isset($this->installedModules["OperationCenter"])) 
            {
            $instanz=$entry["OID"];
            $typedev    = $this->DeviceManager->getHomematicDeviceType($instanz,0);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
            if ( ($typedev=="") || ($typedev===false) )
                {
                echo "       >>HardwareHomematic::getDeviceCheck, Fehler : ".IPS_GetName($instanz)." ($instanz): kein TYPEDEV ermittelt für [".$this->DeviceManager->getHomematicDeviceType($instanz,4)."]. \n";
                $this->DeviceManager->getHomematicDeviceType($instanz,0,true);          // gleich mit Debug starten
                return(false);
                }
            
            }
        return (true);
        }

    /* HardwareHomematicExtended::getDeviceParameter 
     * die Homematic Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist
     * Entry wird direkt in die Devicelist unter Instances integriert, Name ist der Key des Eintrags mit dem integriert wird, Subkategorie ist eben Instances, Entry ist der Wert der eingesetzt wird 
     * Das Gerät Name wird um den Typ Type erweitert, beim Eintrag entry wird der Name zusätzlich gespeichert
     *
     * SubType      Funk|Wired|IP
     * Information  DeviceManager->getHomematicHMDevice($instanz,1)     zB 'Schaltaktor 1-fach Energiemessung'
     * Serialnummer $entry["CONFIG"]["Address"][0]
     * Type         HomematicExtended
     * Instances
     *
     *
     * verwendet einheitlich getHomematicDeviceType
     *
     */

    public function getDeviceParameter(&$deviceList,$name, $type, $entry, $debug=false)
        {
        $deviceInfo=array();
        /* Durchführung, es gibt keine : */            

        if ($debug) echo "           HardwareHomematicExtended::getDeviceParameter Name \"".$name."\" neuer Eintrag. Nur wenn OperationCenter instzalliert ist.\n";

        /* Sonderfunktionen wenn OperationCenter Modul installiert ist */
        $deviceList[$name]["Type"]=$type;
        if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"]; 
        else $entry["NAME"]=$name;         
        if (isset($this->installedModules["OperationCenter"])) 
            {
            $instanz=$entry["OID"];

            $typedev    = $this->DeviceManager->getHomematicDeviceType($instanz,0);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
            //if ($debug) echo "    TYPEDEV: $typedev";
            $entry["TYPEDEV"]=$typedev;
            $deviceList[$name]["Instances"][]=$entry;
            return (true);
            }
        else return (false);                // fehlender Typedev Wert erzeugt keinen Eintrag !!!
        }


    /* HardwareHomematicExtended::getDeviceChannels
     * der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
     * nachdem bereits eine oder mehrere Instanzen einem Gerät zugeordnet wurden, muss nicht mehr kontrolliert werden ob es das Gerät schon gibt
     * Homematic Geräte haben immer mehrer Instances. Die Naming Convention ist Gerätename:Channelname
     * mit den Gerätenamen ist die deviceList indiziert. Der Key muss vorhanden sein.
     * nur wenn OperationCenter installiert ist werden Channels angelegt. Der geräte Port der den Key der Instanz definiert wird auch für den Channel verwendet.
     *
     * mit $DeviceManager->getHomematicDeviceType wird der Typedev bestimmt (Type, Name, RegisterAll, Register ... )
     * Typedev ist der fertige Channel Eintrag, es wird RegisterAll und Name besonders hervorgehoben und doppelt abgespeichert
     *
     * verwendet einheitlich getHomematicDeviceType
     */

    public function getDeviceChannels(&$deviceList,$name, $type, $entry, $debug=false)              // class HardwareHomematic
        {
        $port=0;        // kann nicht aus der Adresse ermittelt werden
        if ($debug) echo str_pad("          getDeviceChannels: HomematicExtended  für \"$name\" : ",90).str_pad(" ",40);
        //echo "       getDeviceChannels: Channels hinzufügen.\n"; print_r($this->installedModules);
        if (isset($this->installedModules["OperationCenter"])) 
            {
            IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');   
            $DeviceManager = new DeviceManagement();                    
            $instanz=$entry["OID"];
            //$typedev    = $DeviceManager->getHomematicDeviceType($instanz,4,$debug);     /* true debug, wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
            $typedev    = $DeviceManager->getHomematicDeviceType($instanz,4,false);
            if ($typedev !== false)  
                {
                if ($debug) 
                    {
                    if (isset($typedev["TYPE_MOTION"])) print_r($typedev);
                    }
                if (isset($typedev["RegisterAll"])) 
                    {
                    $deviceList[$name]["Channels"][$port]["RegisterAll"]=$typedev["RegisterAll"];
                    $deviceList[$name]["Channels"][$port]=$typedev;
                    if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["Name"]=$deviceList[$name]["Name"];
                    else $deviceList[$name]["Channels"][$port]["Name"]=$name;
                    if ($debug) 
                        {
                        if (isset($typedev["TYPECHAN"])) echo "       Schreibe Channels $port mit Typ ".$typedev["TYPECHAN"].".\n";         // Ausgabemodus 4
                        else    
                            {
                            echo "       getDeviceChannels in HardwareHomematic, Schreibe Channels $port typedev config ist:\n";
                            print_r($typedev);
                            }
                        }
                    }
                else 
                    {
                    echo "      >>getDeviceChannels Fehler, Name \"".$name."\" kein RegisterAll ermittelt.\n";
                    print_r($typedev);        // typedev nicht immer vollständig, Fehlerüberprüfung
                    echo "\n";
                    }
                }
            else echo "   >>getDeviceChannels, Fehler $instanz: keine Channels ermittelt.\n";
            }                            
        return (true);
        }

    /* eigenes Tab mit Device befüllen, wird aktuell von  getDeviceParameter gemacht
     *
     */

    public function getDeviceInformation(&$deviceList,$name, $type, $entry, $debug=false)
        {
        $deviceInfo=array();

        if (isset($this->installedModules["OperationCenter"])) 
            {
            $instanz=$entry["OID"];
            $deviceInfo["Gateway"]=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
            }

        $deviceList[$name]["Device"][0]=$deviceInfo;                      

        }

    /* rausfinden ob weitergemacht werden kann, verändert die Variable goOn
     * Config ist die Configuration der Variable oid
     */

    function checkConfig(&$goOn,$config,$oid=false)
        {
        //$goOn=true;           // Variable wird als Pointer übergeben
        $result=json_decode($config,true);   // als array zurückgeben 
        //print_r($result);
        if (isset($result["Address"])) 
            {
            $addressSelect=explode(":",$result["Address"]);
            if (count($addressSelect)>1)
                {
                $port=(integer)$addressSelect[1];
                if ($port==0) 
                    {
                    //echo "Fehler, Port 0 von \"".$nameSelect[0]."\" wird ignoriert : ".$entry["CONFIG"].".\n";
                    $goOn=false;
                    }
                }
            else 
                {
                echo "     getDeviceChannels Fehler, Seriennummer ohne Port.\n";
                $goOn=false;
                }
            }
        else 
            {
            echo "      getDeviceChannels Fehler, keine Seriennummer.\n";
            $goOn=false;
            }
        return ($port);
        }

    }

/* Objektorientiertes class Management für Geräte (Hardware)
 * Hier gibt es Hardware spezifische Routinen die die class hardware erweitern.
 *
 * getDeviceCheck, getDeviceParameter, getDeviceChannels werden Hardware Typ spezifisch programmiert
 *
 */

class HardwareNetatmoWeather extends Hardware
    {

    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "{26A55798-5CBC-88F6-5C7B-370B043B24F9}";           // I/O oder Splitter ist der Socket für das Device
        $this->bridgeID = "{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}";           // Configurator
        $this->deviceID = "{1023DB4A-D491-A0D5-17CD-380D3578D0FA}";           // das Gerät selbst
        parent::__construct($config, $debug);        
        }

    /* HardwareNetatmoWeather::getDeviceChannels
     * der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
     * nachdem bereits eine oder mehrere Instanzen einem Gerät zugeordnet wurden muss nicht mehr kontrolliert werden ob es das Gerät schon gibt
     * es werden nicht nur die Channels identifiziert sondern auch die Registers mit dem typedev
     *          $typedev=$this->getNetatmoDeviceType($oid,4,true);
     *
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)     // HardwareNetatmoWeather
        {
        //$debug=true;        // overwrite for local debug
        if ($debug) echo "          getDeviceChannels: NetatmoWeather aufgerufen für \"".$entry["OID"]."\" mit $name $type.\n";
        //echo "Bereits erstellter/bearbeiteter Eintrag in der deviceList über vorhandene Instanzen:\n";        print_r($deviceList[$name]["Instances"]);
        //echo "Übergebene zusätzliche Parameter:\n";        print_r($entry);
        $oids=array();
        foreach ($deviceList[$name]["Instances"] as $port => $register) 
            {
            $oids[$register["OID"]]=$port;
            }
        //echo "Aus den Instanzen ermittelte OIDs:\n"; print_r($oids);
        if (isset($oids[$entry["OID"]])===false) echo "  >> irgendetwas ist falsch, keine OID aus entry übergeben.\n";
        foreach ($oids as $oid => $port)
            {
            //$typedev=$this->getNetatmoDeviceType($oid,4,$debug);
            $typedev=$this->getNetatmoDeviceType($oid,4,false);
            //echo "getDeviceChannels, Werte für $oid aus getNetatmoDeviceType:\n"; print_r($typedev);
            if ($typedev<>"")  
                {
                /* Umstellung der neutralen Ausgabe von typedev auf Channel typische Ausgaben 
                if (isset(($typedev["Type"])))  $deviceList[$name]["Channels"][$port]["TYPECHAN"]=$typedev["Type"];     // umsetzen auf Channeltypischen Filter
                else echo "      >>getDeviceChannels Fehler, Name \"$name\" kein TYPECHAN ermittelt.\n";
                if (isset(($typedev["Register"])))  $deviceList[$name]["Channels"][$port]["Register"]=$typedev["Register"];
                else 
                    {
                    echo "      >>getDeviceChannels Fehler, Name \"$name\" kein Register ermittelt. typedev[register] from getHomematicDeviceType für $instanz / \"$name\" not defined.\n";
                    }
                if (isset(($typedev["RegisterAll"])))  $deviceList[$name]["Channels"][$port]["RegisterAll"]=$typedev["RegisterAll"];
                else echo "      >>getDeviceChannels Fehler, Name \"$name\" kein RegisterAll ermittelt.\n";
                */
                $deviceList[$name]["Channels"][$port]=$typedev;
                if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["Name"]=$deviceList[$name]["Name"];  
                else $deviceList[$name]["Channels"][$port]["Name"]=$name;               
                }
            else
                {
                echo "   >>getDeviceChannels, Fehler $name: keine Channels ermittelt.\n";
                }
            }
        if ( ($debug) && false)
            {
            echo "===> Ergebnis getDeviceChannels:\n";
            print_r($deviceList[$name]["Channels"]);
            }
        return (true);
        }

    /*********************************
     *
     * gibt für eine Netatmo Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     * vorher die Childrens der Instanz ermitteln und dann NetatmoDeviceType aufrufen.
     *
     *
     ***********************************************/

    function getNetatmoDeviceType($instanz, $outputVersion=false, $debug=false)
	    {
    	$cids = IPS_GetChildrenIDs($instanz);
	    $netatmo=array();
    	foreach($cids as $cid)
	    	{
		    $netatmo[$cid]=IPS_GetName($cid);
    		}
    	return ($this->NetatmoDeviceType($netatmo,$outputVersion, $debug));
    	}

    /*********************************
     * 
     * Netatmo Device Type, genaue Auswertung nur mehr an einer, dieser Stelle machen 
     *
     * Übergabe ist ein array aus Variablennamen/Children einer Instanz oder die Sammlung aller Instanzen die zu einem Gerät gehören
     * übergeben wird das Array das alle auch doppelte Eintraege hat. Folgende Muster werden ausgewertet:
     *
     * Es gibt unterschiedliche Arten der Ausgabe, eingestellt mit outputVersion
     *   false   die aktuelle Kategorisierung
     *
     *
     ****************************************/

    private function NetatmoDeviceType($register, $outputVersion=false, $debug=false)
        {
		sort($register);
        $registerNew=array();
    	$oldvalue="";        
        /* gleiche Einträge eliminieren */
	    foreach ($register as $index => $value)
		    {
	    	if ($value!=$oldvalue) {$registerNew[]=$value;}
		    $oldvalue=$value;
			}         
        $found=true; 
        if ($debug) 
            {
            echo "                NetatmoDeviceType: Info mit Debug aufgerufen. Parameter \"";
            foreach ($registerNew as $entry) echo "$entry ";
            echo "\"\n";
            }

        /* result wird geschrieben, 4 Ausgabevarianten, Variante 4 wird für die devicelist verwendet
         *
         * 0 Textuelle Beschreibung
         * 1 Medium und Textuelle Beschreibung
         * 2 Typbeschreibung wie TYPECHAN in der DeviceList
         * 3 Dieses Register und alle register
         * 4 TYPECHAN Zusammenfassung und registerAll 
         *
         */

        /*--Regensensor-----------------------------------*/
        if ( array_search("Regenmenge",$registerNew) !== false)            /* Sensor Raumklima */
            {
            $resultRegCounter["RAIN_COUNTER"]="Regenmenge";

            $result[0] = "Regensensor";
            $result[1] = "Funk Regensensor";
            $result[2] = "TYPE_METER_CLIMATE";

            $result[3]["Type"] = "TYPE_METER_CLIMATE";            
            $result[3]["Register"] = $resultRegCounter;
            $result[3]["RegisterAll"]=$registerNew;  
            $result[4]["TYPECHAN"] = "TYPE_METER_CLIMATE";   
            $result[4]["TYPE_METER_CLIMATE"] = $resultRegCounter;                       
            $result[4]["RegisterAll"]=$registerNew;                      
            }

        /*--Raumklimasensor-----------------------------------*/
        elseif ( array_search("CO2",$registerNew) !== false)            /* Sensor Raumklima */
            {
            if ($debug) echo "                     Sensor Raumklima gefunden.\n";
            $resultRegTemp["TEMPERATURE"]="Temperatur";
            $resultRegTemp["HUMIDITY"]="Luftfeuchtigkeit";
            $resultRegHumi["HUMIDITY"]="Luftfeuchtigkeit";
            $resultRegClim["CO2"]="CO2";
            $resultRegClim["BAROPRESSURE"]="Luftdruck";
            $resultRegClim["NOISE"]="Lärm";

            $result[0] = "Raumklimasensor";
            $result[1] = "Funk Raumklimasensor";
            $result[2] = "TYPE_METER_CLIMATE";            
            $result[3]["Type"] = "TYPE_METER_CLIMATE";            
            $result[3]["Register"] = array_merge($resultRegTemp, $resultRegClim);
            $result[3]["RegisterAll"]=$registerNew;
            $result[4]["TYPECHAN"] = "TYPE_METER_TEMPERATURE,TYPE_METER_HUMIDITY,TYPE_METER_CLIMATE";              
            $result[4]["TYPE_METER_CLIMATE"] = $resultRegClim;
            $result[4]["TYPE_METER_TEMPERATURE"] = $resultRegTemp;
            $result[4]["TYPE_METER_HUMIDITY"] = $resultRegHumi;
            $result[4]["RegisterAll"]=$registerNew;
            }

        /*--Temperatursensor, Aussen-----------------------------------*/
        elseif ( array_search("Temperatur",$registerNew) !== false)            /* Sensor Temperatur */
            {
            //print_r($registerNew);
            if ($debug) echo "                     Sensor Temperatur gefunden.\n";
            $resultRegTemp["TEMPERATURE"]="Temperatur";
            $resultRegTemp["HUMIDITY"]="Luftfeuchtigkeit";
            $resultRegHumi["HUMIDITY"]="Luftfeuchtigkeit";

            $result[0] = "Temperatursensor";
            $result[1] = "Funk Temperatursensor";
            $result[2] = "TYPE_METER_TEMPERATURE";            
            $result[3]["Type"] = "TYPE_METER_TEMPERATURE";            
            $result[3]["Register"] = $resultRegTemp;
            $result[3]["RegisterAll"]=$registerNew;
            $result[4]["TYPECHAN"] = "TYPE_METER_TEMPERATURE,TYPE_METER_HUMIDITY";              
            $result[4]["TYPE_METER_TEMPERATURE"] = $resultRegTemp;
            $result[4]["TYPE_METER_HUMIDITY"] = $resultRegHumi;
            $result[4]["RegisterAll"]=$registerNew;
            }
        elseif ( array_search("Status",$registerNew) !== false)            /* Sensor Temperatur */
            {
            //print_r($registerNew);
            if ($debug) echo "                     Ort der Wetterstation gefunden.\n";
            $result[0] = "Statusinformation";
            $result[1] = "Funk Statusinformation";
            $result[2] = "TYPE_STATUS";            
            $result[3]["Type"] = "TYPE_STATUS";            
            $result[3]["RegisterAll"]=$registerNew;
            $result[4]["TYPECHAN"] = "TYPE_STATUS";              
            $result[4]["RegisterAll"]=$registerNew;
            } 
        else
            {
            $found=false;
            echo "   >>NetatmoDeviceType, Fehler kenne Objekt nicht. Werte im Objekt sind :\n";
            print_r($registerNew);
            }

        if ($found) 
            {
            if ($outputVersion==false) return($result[2]);
            elseif ($outputVersion==2) return ($result[1]);
            elseif ($outputVersion==3) return ($result[3]);
            elseif ($outputVersion==4) return ($result[4]);
			else return ($result[0]);
            }
        else 
            {
            if ($outputVersion>100) 
                {
                $result = "";
                foreach ($registerNew as $entry) $result .= $entry." ";
                return ($result);
                }
            else return (false);
            }
        }

    }

/* Homematic - Objektorientiertes class Management für Geräte (Hardware)
 *
 * siehe auch class HardwareHomematicExtended extends HardwareHomematic versucht einen klareren Blick auf die Aufgaben
 *
 * Hier gibt es Hardware spezifische Routinen die die class hardware erweitern.
 * Homematic hat ein eigenes Naming scheme mit : da ein Gerät mehrere Instanzen haben kann. name Gerät:Instanz
 * ohne : geht es nicht, da der erste Teil der gemeinsame Name ist
 *
 *      __construct
 *
 * die Device Liste deviceList aus der Geräteliste erstellen, Antwort ist ein Geräteeintrag
 *
 *      getDeviceCheck
 *      getDeviceParameter              Instanzen anlegen, pro Gerät mehrere Instanzen
 *      getDeviceChannels
 *      getDeviceInformation
 *      checkConfig
 *
 *
 *
 */

class HardwareHomematic extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
    protected $installedModules;

    protected $DeviceManager;                 /* nur ein Objekt in der class */
	
    /*
     */
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}";
        $this->bridgeID = "{5214C3C6-91BC-4FE1-A2D9-A3920261DA74}";
        $this->deviceID = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
        $this->setInstalledModules();
        if (isset($this->installedModules["OperationCenter"])) 
            {
            IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');
            if ($debug>2) echo "class DeviceManagement aufgerufen:\n";   
            $this->DeviceManager = new DeviceManagement_Homematic($debug>2); 
            }
        parent::__construct($config,$debug);
        }

    /* HardwareHomematic::getDeviceCheck
     * Übergabe deviceList und name der Instanz, name besteht aus 2 Teilen mit : getrennt
     * entry beinhaltet die Configuration unter ["CONFIG"], Configuration ist json encoded
     * entry beinhaltet auch ["OID"] , check wenn OperationCenter Modul installiert ist
     *
     * Rückgabe false wenn
     *    wenn Name ohne Doppelpunkt
     *    es gibt eine Adresse mit einem : in der Konfiguration
     *    kein Port 0 in der Adresse
     *    wenn OperationCenter Modul installiert ist 
     *      Matrix Auswertung erfolgreich
     *      Port muss in Matrix ein Index sein mit Eintrag 1 oder größer
     *      typedev Auswertung muss auch erfolgreich sein
     *
     * Homematic, die Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist. Ein false führt dazu dass kein Eintrag erstellt wird.
     *
     * Ein Homematic Device kann aus mehreren Instances bestehen, zuerst prüfen ob aus einer Instanz heraus das Device bereits angelegt wurde
     * der Name der Instanz muss immer einen Doppelpunkt haben, vor dem Doppelpunkt ist der Name des Gerätes
     * In entry gibt es ["CONFIG"]["Address"] mit der Homematic Adresse
     *
     * DeviceList wird nicht abgeändert
     *
     * verwendet bereits 
     *      DeviceManager->getHomematicHMDevice($instanz,2)
     *      DeviceManager->getHomematicDeviceType($instanz,0)
     */

    public function getDeviceCheck(&$deviceList, &$name, $type, $entry, $debug=false)
        {
        /* Fehlerprüfung, Name bereits in der Devicelist und wenn dann mit Doppelpunkt. Theoretisch werden Namen ohne : erlaubt wenn keine weitere Instanz vorhanden ist.*/
        if ($debug>1) echo "      HardwareHomematic::getDeviceCheck aufgerufen für $name , Warnung wenn Name keinen : enthält:\n";
        $nameSelect=explode(":",$name);

        if (isset($deviceList[$nameSelect[0]])) 
            {       // vorhanden
            if (count($nameSelect)<2) 
                {
                echo "        >>HardwareHomematic::getDeviceCheck Fehler, Name \"".$nameSelect[0]."\" bereits definiert und Homematic Gerät Name falsch, ist ohne Doppelpunkt: $name \n";
                return (false);
                }
            else            // nicht
                {
                if ($debug) 
                    {
                    echo "        HardwareHomematic::getDeviceCheck Name \"".$nameSelect[0]."\" bereits definiert, Eintrag wird ergänzt. Port ";
                    foreach ($deviceList[$nameSelect[0]]["Instances"] as $portInfo => $entryInfo) echo $portInfo." ";
                    echo " bereits definiert.\n";
                    }
                }
            }
        // elseif ($debug)  echo "          getDeviceCheck:Homematic Name \"".$nameSelect[0]."\" neuer Eintrag.\n";

        /* Fehlerprüfung anhand der Seriennummer, Adresse. Port 0 wird nicht ausgewertet */
        $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
        //print_r($result);
        if (isset($result["Address"])) 
            {
            $addressSelect=explode(":",$result["Address"]);
            if (count($addressSelect)>1)
                {
                $port=(integer)$addressSelect[1];
                if ($port==0) 
                    {
                    $instanz=$entry["OID"];
                    if (isset($this->installedModules["OperationCenter"])) 
                        {
                        $matrix    = $this->DeviceManager->getHomematicHMDevice($instanz,2); 
                        if ($debug>2) echo json_encode($matrix),"  \n";
                        if ($matrix[0]<2) return (false);           // vielleicht will ich gerade den Port 0 haben
                        }
                    else 
                        {
                        if ($debug>1) echo "          >>Port 0 von \"".$nameSelect[0]."\" wird ignoriert : ".$entry["CONFIG"]."   $name   $type ";
                        return (false);         // wenn niemand da ders besser weiss, Fehler
                        }
                    }
                else 
                    {
                    /* das ist der positive Pfad, hier gehts weiter. */
                    //if ($debug) echo "       Port $port wird jetzt geschrieben. ".$result["Address"]."\n";
                    //print_r($entry);
                    }
                }
            else 
                {
                echo "       >>HardwareHomematic::getDeviceCheck Fehler, Seriennummer ohne Port.\n";
                return (false);
                }
            }
        else 
            {
            echo "       >>HardwareHomematic::getDeviceCheck Fehler, keine Seriennummer.\n";
            return (false);
            }

        /* erweiterte Fehlerprüfung wenn OperationCenter, check Matrix mit Zuhilfename von Homematic Inventory Creator */
        if (isset($this->installedModules["OperationCenter"])) 
            {
            $instanz=$entry["OID"];
            $matrix    = $this->DeviceManager->getHomematicHMDevice($instanz,2);     /* Eindeutige Bezeichnung aufgrund des Homematic Gerätenamens */
            if (is_array($matrix)) 
                {
                if (isset($matrix[$port])===false)
                    {
                    echo "        Info Port $port von \"$name\" (".$result["Address"].") hat keinen Eintrag für [".$this->DeviceManager->getHomematicDeviceType($instanz,4)."]. Infofeld aus HMInventory: ".$this->DeviceManager->getHomematicHMDevice($instanz,0)."  ".$this->DeviceManager->getHomematicHMDevice($instanz,1)."  \n";    
                    return (false);
                    }    
                if ($matrix[$port]<=1) 
                    {
                    if ($debug) echo "         Info Port $port von \"$name\" (".$result["Address"].") wird nicht berücksichtigt. Infofeld aus HMInventory: ".$this->DeviceManager->getHomematicHMDevice($instanz,0)."  ".$this->DeviceManager->getHomematicHMDevice($instanz,1)."  \n";
                    return(false);
                    }
                //print_r($matrix);
                }
            elseif ($matrix !== false) echo "   >>HardwareHomematic::getDeviceCheck,Fehler \"$name\" (".$entry["OID"]."/".$result["Address"].") keine Bewertung der Matrix. Gerät ".IPS_GetName($entry["OID"])."/".IPS_GetName(IPS_GetParent($entry["OID"]))." nicht hinterlegt. Infofeld aus HMInventory: \"".$this->DeviceManager->getHomematicHMDevice($instanz,0)."\"  \"".$this->DeviceManager->getHomematicHMDevice($instanz,1)."\"\n";
            else return (false);            // Gerät nicht mehr vorhanden

            $typedev    = $this->DeviceManager->getHomematicDeviceType($instanz,0);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
            if ( ($typedev=="") || ($typedev===false) )
                {
                echo "       >>HardwareHomematic::getDeviceCheck, Fehler : ".IPS_GetName($instanz)." ($instanz): kein TYPEDEV ermittelt für [".$this->DeviceManager->getHomematicDeviceType($instanz,4)."]. Infofeld aus HMInventory: ".$this->DeviceManager->getHomematicHMDevice($instanz,0)." \n";
                echo "                Info : ".$this->DeviceManager->getHomematicHMDevice($instanz,1)."  Fehleranalyse typedev=this->DeviceManager->getHomematicDeviceType($instanz... erfolgt hier: \n";
                $this->DeviceManager->getHomematicDeviceType($instanz,0,true);          // gleich mit Debug starten
                return(false);
                }
            
            }
        if ($debug>1) echo "     check completed with true.\n";
        //$deviceList[$name]["Name"]=$name;
        return (true);
        }

    /* HardwareHomematic::getDeviceParameter
     * die Homematic Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist
     * Entry wird direkt in die Devicelist unter Instances integriert, Name ist der Key des Eintrags mit dem integriert wird, Subkategorie ist eben Instances, Entry ist der Wert der eingesetzt wird 
     * Das Gerät Name wird um den Typ Type erweitert, beim Eintrag entry wird der Name zusätzlich gespeichert
     *
     * SubType      Funk|Wired|IP
     * Information  DeviceManager->getHomematicHMDevice($instanz,1)     zB 'Schaltaktor 1-fach Energiemessung'
     * Serialnummer $entry["CONFIG"]["Address"][0]
     * Type
     * Instances    holt sich die Instanznummer aus der Portnummer, die in der Config gespeichet ist
     *
     *
     *
     * verwendet DeviceManager 
     *      DeviceManager->getHomematicHMDevice($instanz,2)
     *      TYPEDEV         DeviceManager->getHomematicDeviceType($instanz,0)
     *      Information     DeviceManager->getHomematicHMDevice($instanz,1)
     *      TypeDevice      DeviceManager->getHomematicHMDevice($instanz,0);
     */

    public function getDeviceParameter(&$deviceList,$name, $type, $entry, $debug=false)
        {
        /* Jeder Entry ist ein Device, oder ? */
        
        /* sehr schwierig, Devices sind nicht automatisch Instanzen */
        /* Zusammenfassen ausprobieren, erster Check alle Homematic Instanzen haben einen Doppelpunkt im Namen */

        $deviceInfo=array();

        $nameSelect=explode(":",$name);
        $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
        $addressSelect=explode(":",$result["Address"]);        
        $port=(integer)$addressSelect[1];

        /* Durchführung */            

        if ($debug) echo "          HardwareHomematic::getDeviceParameter Name \"".$nameSelect[0]."\" neuer Eintrag.".str_pad(" In deviceList unter ".$nameSelect[0]." Port $port.",50)."\n";
        if (isset($result["Protocol"])) 
            {
            switch ($result["Protocol"])
                {
                case 0:
                    //$deviceList[$nameSelect[0]]["SubType"]="Funk"; 
                    $deviceInfo["SubType"]="Funk";                           
                    break;
                case 1:
                    //$deviceList[$nameSelect[0]]["SubType"]="Wired";
                    $deviceInfo["SubType"]="Wired";                            
                    break;
                case 2:
                    //$deviceList[$nameSelect[0]]["SubType"]="IP";
                    $deviceInfo["SubType"]="IP";                            
                    break;
                default:
                    break;    
                }
            }

        /* Sonderfunktionen wenn OperationCenter Modul installiert ist */

        if (isset($this->installedModules["OperationCenter"])) 
            {
            $instanz=$entry["OID"];
            $matrix    = $this->DeviceManager->getHomematicHMDevice($instanz,2);     /* Eindeutige Bezeichnung aufgrund des Homematic Gerätenamens */
            if (is_array($matrix)) 
                {
                if ($debug) echo "             Infofeld aus HMInventory: ".$this->DeviceManager->getHomematicHMDevice($instanz,0)."  ".$this->DeviceManager->getHomematicHMDevice($instanz,1)."  $port und in der Matrix.";
                }
            else echo "   >>Fehler, HardwareHomematic::getDeviceParameter, keine Ausgabe der Matrix. Gerät nicht in getHomematicHMDevice hinterlegt.\n";


            //echo " $instanz: ";
            //$typeInst   = $DeviceManager->getHomematicType($instanz);           /* wird für Homematic IPS Light benötigt */
            //$HMDevice   = $DeviceManager->getHomematicHMDevice($instanz);
            /* if ($typeInst <> "") 
                {
                echo "TypeInst => $typeInst ";
                $entry["TYPE"]=$typeInst;
                }
            if ($HMDevice<>"") 
                {
                echo "HMDevice => $HMDevice ";
                $entry["HMDEVICE"]=$HMDevice;
                }
            echo "\n";
                
            */

            $typedev    = $this->DeviceManager->getHomematicDeviceType($instanz,0);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
            if ($debug) echo "    TYPEDEV: $typedev";
            $entry["TYPEDEV"]=$typedev;
            
            if (strpos($nameSelect[0],"HM") === 0) 
                {
                echo "      >>Fehler, HardwareHomematic::getDeviceParameter: Name starts with HM, is probably new one, has not been renamed : \"".IPS_GetName($instanz)."\" ($instanz/".$result["Address"]."): ";
                $typedev    = $this->DeviceManager->getHomematicDeviceType($instanz,0,true);     /* noch einmal mit Debug wenn Name mit HM anfangt */
                }

            //$infodev    = $DeviceManager->getHomematicDeviceType($instanz,1);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ der Instanz in beschreibender Form aus */
            $infodev    = $this->DeviceManager->getHomematicHMDevice($instanz,1);     /* Eindeutige Bezeichnung aufgrund des Homematic Gerätenamens */
            if ($infodev<>"")   
                {
                //$deviceList[$nameSelect[0]]["Information"]=$infodev;
                $deviceInfo["Information"]=$infodev;
                if ($debug) echo "            INFO: $infodev  ";
                }
            else echo "\n       >>Fehler, HardwareHomematic::getDeviceParameter, \"".IPS_GetName($instanz)."\" ($instanz/".$result["Address"]."): kein INFO ermittelt.\n";
            //$deviceList[$nameSelect[0]]["Serialnummer"]=$addressSelect[0];
            $deviceInfo["Serialnummer"]=$addressSelect[0];
            /*
            $typedev    = $DeviceManager->getHomematicDeviceType($instanz,3);     // wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus
            if ($typedev<>"")  
                {
                $deviceList[$nameSelect[0]]["Channels"][$port]=$typedev;
                $deviceList[$nameSelect[0]]["Channels"][$port]["Name"]=$name;
                }
            else "Fehler $instanz: keine Channels ermittelt.\n";
            */

            if (isset($deviceList[$nameSelect[0]]["Instances"][$port])) echo "\n     >> Fehler Port $port bereits definiert. Wird ueberschrieben.\n";                          
            $deviceList[$nameSelect[0]]["Type"]=$type;
            //$deviceList[$nameSelect[0]]["TypeDevice"]=$this->DeviceManager->getHomematicHMDevice($instanz,0);
            $deviceInfo["TypeDevice"]=$this->DeviceManager->getHomematicHMDevice($instanz,0);
            if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"];
            else $entry["NAME"]=$name;
            $deviceList[$nameSelect[0]]["Instances"][$port]=$entry;             // port ist eine wichtige Information, info um welchen Switch, Taster etc. geht es hier.


            //$deviceList[$nameSelect[0]]["Device"][]=$deviceInfo;
            if ($debug) echo "\n";
            return (true);
            }
        else return (false);                // fehlender Typedev Wert erzeugt keinen Eintrag !!!
        }

    /* HardwareHomematic::getDeviceChannels
     * der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
     * nachdem bereits eine oder mehrere Instanzen einem Gerät zugeordnet wurden, muss nicht mehr kontrolliert werden ob es das Gerät schon gibt
     * Homematic Geräte haben immer mehrer Instances. Die Naming Convention ist Gerätename:Channelname
     * mit den Gerätenamen ist die deviceList indiziert. Der Key muss vorhanden sein.
     * nur wenn OperationCenter installiert ist werden Channels angelegt. Der geräte Port der den Key der Instanz definiert wird auch für den Channel verwendet.
     *
     * mit $DeviceManager->getHomematicDeviceType wird der Typedev bestimmt (Type, Name, RegisterAll, Register ... )
     * Typedev ist der fertige Channel Eintrag, es wird RegisterAll und Name besonders hervorgehoben und doppelt abgespeichert
     *
     * getHomematicDeviceType und HomematicDeviceType finden sich in OperationCenter_Library
     *
     *
     * verwendet DeviceManager 
     *   available port check on address : DeviceManager->getHomematicHMDevice($instanz,2)
     *   detailed typedef         DeviceManager->getHomematicDeviceType($instanz,4)
     *      Information     DeviceManager->getHomematicHMDevice($instanz,1)
     *      TypeDevice      DeviceManager->getHomematicHMDevice($instanz,0);
     */

    public function getDeviceChannels(&$deviceList,$name, $type, $entry, $debug=false)              // class HardwareHomematic
        {
        /* Jeder Entry ist ein Device, oder ? */

        /* sehr schwierig, Devices sind nicht automatisch Instanzen */
        /* Zusammenfassen ausprobieren, erster Check alle Homematic Instanzen haben einen Doppelpunkt im Namen */

        $goOn=true;

        /* Fehlerüberprüfung, die anderen Items adressSelect, port werden nur nach Verfügbarkeit berechnet */
        $nameSelect=explode(":",$name);

        if (isset($deviceList[$nameSelect[0]]) === false) 
            {
            $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
            if (isset($result["Address"])) 
                {
                $addressSelect=explode(":",$result["Address"]);
                if (count($addressSelect)>1)
                    {
                    $port=(integer)$addressSelect[1];
                    if ($port==0) 
                        {
                        //if ($debug) echo "               Port 0 von \"".$nameSelect[0]."\" wird ignoriert : ".$entry["CONFIG"].".\n";         // brauch ich nicht noch einmal ausgeben.
                        $matrix = $this->DeviceManager->getHomematicHMDevice($entry["OID"],2);     /* Eindeutige Bezeichnung aufgrund des Homematic Gerätenamens */
                        if ($matrix[0]<2) $goOn=false;
                        else echo "Port 0 für $name berücksichtigen.\n";
                        }                           
                    else echo "   >>getDeviceChannels Fehler, Name \"".$nameSelect[0]."\" noch nicht definiert. Seriennummer : ".$addressSelect[0]." Port : ".$addressSelect[1]." \n";
                    }
                else
                    {
                    echo "   >>getDeviceChannels Fehler, Name \"".$nameSelect[0]."\" noch nicht definiert. Seriennummer : ".$addressSelect[0]."  \n";
                    }
                }
            else echo "   >>getDeviceChannels Fehler, Name \"".$nameSelect[0]."\" noch nicht definiert. Keine Seriennummer gefunden.\n";
            $goOn=false;
            }

        $port= $this->checkConfig($goOn, $entry["CONFIG"],$entry["OID"]);     	// gibt Port zurück, wenn alles okay wird goOn nicht auf false gesetzt
        //if ($debug) echo "          HardwareHomematic::getDeviceChannels Name \"".$nameSelect[0]."\"" Port  $port ,wirklich weitermachen :  $goOn\n";
        
        /* Durchführung */
        if ($goOn)
            {
            if ($debug) echo str_pad("          getDeviceChannels: Homematic  für \"$name\" : ",90).str_pad(" ",40);
            //echo "       getDeviceChannels: Channels hinzufügen.\n"; print_r($this->installedModules);
            if (isset($this->installedModules["OperationCenter"])) 
                {
                IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');   
                $DeviceManager = new DeviceManagement();                    
                $instanz=$entry["OID"];
                //$typedev    = $DeviceManager->getHomematicDeviceType($instanz,4,$debug);     /* true debug, wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
                $typedev    = $DeviceManager->getHomematicDeviceType($instanz,4,false);
                if ($typedev !== false)  
                    {
                    /*
                    //$deviceList[$nameSelect[0]]["Channels"][$port]=$typedev;
                    if (isset(($typedev["Type"])))  $deviceList[$nameSelect[0]]["Channels"][$port]["TYPECHAN"]=$typedev["Type"];
                    else echo "      >>getDeviceChannels Fehler, Name \"".$nameSelect[0]."\" kein TYPECHAN ermittelt.\n";
                    if (isset(($typedev["Register"])))  $deviceList[$nameSelect[0]]["Channels"][$port]["Register"]=$typedev["Register"];
                    else 
                        {
                        echo "      >>getDeviceChannels Fehler, Name \"".$nameSelect[0]."\" kein Register ermittelt. typedev[register] from getHomematicDeviceType für $instanz / \"$name\" not defined.\n";
                        $infodev    = $DeviceManager->getHomematicHMDevice($instanz,1);             // nutzt HMI Create Report 
                        $type       = $DeviceManager->getHomematicDeviceType($instanz,0,true);     // noch einmal mit Debug wenn Name mit HM anfangt
                        echo "      infodev $infodev type $type \n";
                        print_r($typedev);
                        }
                    */
                    if ($debug>1) 
                        {
                        if (isset($typedev["TYPE_MOTION"])) print_r($typedev);
                        }
                    if (isset($typedev["RegisterAll"])) 
                        {
                        $deviceList[$nameSelect[0]]["Channels"][$port]["RegisterAll"]=$typedev["RegisterAll"];
                        $deviceList[$nameSelect[0]]["Channels"][$port]=$typedev;
                        if (isset($deviceList[$name]["Name"])) $deviceList[$nameSelect[0]]["Channels"][$port]["Name"]=$deviceList[$name]["Name"];
                        else $deviceList[$nameSelect[0]]["Channels"][$port]["Name"]=$name;
                        if ($debug) 
                            {
                            if (isset($typedev["TYPECHAN"])) echo "       Schreibe Channels $port mit Typ ".$typedev["TYPECHAN"].".\n";         // Ausgabemodus 4
                            else    
                                {
                                echo "       getDeviceChannels in HardwareHomematic, Schreibe Channels $port typedev config ist:\n";
                                print_r($typedev);
                                }
                            }
                        }
                    else 
                        {
                        echo "      >>getDeviceChannels Fehler, Name \"".$nameSelect[0]."\" kein RegisterAll ermittelt.\n";
                        print_r($typedev);        // typedev nicht immer vollständig, Fehlerüberprüfung
                        echo "\n";
                        }
                    }
                else echo "   >>getDeviceChannels, Fehler $instanz: keine Channels ermittelt.\n";
                }                            
            return (true);
            }
        else return (false);
        }

    /* eigenes Tab mit Device befüllen, wird aktuell von  getDeviceParameter gemacht
     *
     */

    public function getDeviceInformation(&$deviceList,$name, $type, $entry, $debug=false)
        {
        $deviceInfo=array();
        $nameSelect=explode(":",$name);        
        $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
        if (isset($result["Protocol"])) 
            {
            switch ($result["Protocol"])
                {
                case 0:
                    //$deviceList[$nameSelect[0]]["SubType"]="Funk"; 
                    $deviceInfo["SubType"]="Funk";                           
                    break;
                case 1:
                    //$deviceList[$nameSelect[0]]["SubType"]="Wired";
                    $deviceInfo["SubType"]="Wired";                            
                    break;
                case 2:
                    //$deviceList[$nameSelect[0]]["SubType"]="IP";
                    $deviceInfo["SubType"]="IP";                            
                    break;
                default:
                    break;    
                }
            }
        $addressSelect=explode(":",$result["Address"]);            
        $deviceInfo["Serialnummer"]=$addressSelect[0];
        /* Sonderfunktionen wenn OperationCenter Modul installiert ist */

        if (isset($this->installedModules["OperationCenter"])) 
            {
            $instanz=$entry["OID"];
            $deviceInfo["Gateway"]=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
            $infodev    = $this->DeviceManager->getHomematicHMDevice($instanz,1);     /* Eindeutige Bezeichnung aufgrund des Homematic Gerätenamens */
            if ($infodev<>"")   
                {
                //$deviceList[$nameSelect[0]]["Information"]=$infodev;
                $deviceInfo["Information"]=$infodev;
                if ($debug) echo "          getDeviceInformation:         INFO: $infodev\n";
                }
            else echo "\n       >>getDeviceInformation:Homematic Fehler : \"".IPS_GetName($instanz)."\" ($instanz/".$result["Address"]."): kein INFO ermittelt.\n";  
            $deviceInfo["TypeDevice"]=$this->DeviceManager->getHomematicHMDevice($instanz,0);
            }
        if (isset($deviceList[$nameSelect[0]]["Device"][0]))
            {
            $actual=json_encode($deviceList[$nameSelect[0]]["Device"][0]);
            $new=json_encode($deviceInfo);
            if ($actual != $new) echo "Achtung ueberschreiben $actual mit neuem Wert $new.\n";
            }
        $deviceList[$nameSelect[0]]["Device"][0]=$deviceInfo;                      

        }

    /* rausfinden ob weitergemacht werden kann, verändert die Variable goOn
     *
     */

    function checkConfig(&$goOn,$config,$oid=false)
        {
        //$goOn=true;           // Variable wird als Pointer übergeben
        $result=json_decode($config,true);   // als array zurückgeben 
        //print_r($result);
        if (isset($result["Address"])) 
            {
            $addressSelect=explode(":",$result["Address"]);
            if (count($addressSelect)>1)
                {
                $port=(integer)$addressSelect[1];
                if ($port==0) 
                    {
                    if ($oid) 
                        {
                        $matrix = $this->DeviceManager->getHomematicHMDevice($oid,2);     /* Eindeutige Bezeichnung aufgrund des Homematic Gerätenamens */
                        if ($matrix[0]<2) $goOn=false;
                        }
                    else $goOn=false;
                    }
                }
            else 
                {
                echo "     getDeviceChannels Fehler, Seriennummer ohne Port.\n";
                $goOn=false;
                }
            }
        else 
            {
            echo "      getDeviceChannels Fehler, keine Seriennummer.\n";
            $goOn=false;
            }
        return ($port);
        }

    }

/* Philips HUE 
 * simple Implementation, means individual functions only for construct, getDeviceParameter
 * change to reading of ConfigurationForm to get more information of typedev in DeviceManagement_Hue
 *
 *      construct
 *      getDeviceParameter
 *      getDeviceChannels
 */

class HardwareHUE extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "{6EFF1F3C-DF5F-43F7-DF44-F87EFF149566}";             // I/O oder Splitter ist der Socket für das Device
        $this->bridgeID = "{EE92367A-BB8B-494F-A4D2-FAD77290CCF4}";             // Configurator
        $this->deviceID = "{83354C26-2732-427C-A781-B3F5CDF758B1}";             // das Gerät selbst
        $this->setInstalledModules();
        if (isset($this->installedModules["OperationCenter"])) 
            {
            IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');
            if ($debug>2) echo "class DeviceManagement aufgerufen:\n";   
            $this->DeviceManager = new DeviceManagement_Hue($debug>2); 
            }
        parent::__construct($config,$debug);        
        }

    /* HardwareHUE::getDeviceParameter
     * die Device Liste aus der Geräteliste erstellen 
     * Antwort ist ein Geräteeintrag
     *
     * Standard wäre:         $deviceList[$name]["Type"]=$type; $entry["NAME"]=$name; $deviceList[$name]["Instances"][]=$entry;
     *                              entry[TYPEDEV], entry[OID], entry[NAME],entry[CONFIG] bereits übernommen 
     *                                  TYPE_SWITCH | TYPE_AMBIENT | TYPE_DIMMER
     */
    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        //$debug=true;    
        /* Fehlerpüfung, erfolgt bereits checkDevice, bei doppelten Namen wird entweder ein uniqueName erstellt oder abgebrochen
        if (isset($deviceList[$name])) 
            {
            echo "          >>HardwareHUE::getDeviceParameter, Fehler, Name \"".$name."\" bereits definiert.\n";
            return(false);
            }            
        /* Durchführung */
        if ($debug) echo "          getDeviceParameters:HUE     aufgerufen. Eintrag \"$name\" hinterlegt.\n";      
        if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"];               // Name wird übergeben für Debug
        else $entry["NAME"]=$name;
        if (isset($this->installedModules["OperationCenter"])) 
            {
            if (isset($entry["OID"]))
                {
                $instanz=$entry["OID"];
                $typedev    = $this->DeviceManager->getHueDeviceType($instanz,0,$entry,$debug>1);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
                //if ($debug) echo "    TYPEDEV: $typedev";
                $entry["TYPEDEV"]=$typedev;
                }
            }      

        $deviceList[$name]["Type"]=$type;
        $deviceList[$name]["Instances"][]=$entry;
        return (true);
        }


    /* HardwareHUE::getDeviceChannels
     * die Device Liste (Geräteliste) um die Channels erweitern, ein Gerät hat die Kategorien Instances,Channels,Actuators, es kann mehrer Einträge in Instances und Channels haben, 
     * es können mehr channels als instances sein, es können aber auch gar keine channels sein - eher unüblich
     * alle instances durchgehen, OIDs einsammeln mit Zuordnung Port abspeichern
     * zumindest RegisterAll schreiben
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)                 // class Hardware
        {
        if ($debug) echo "          HardwareHUE::getDeviceChannels, aufgerufen für ".$entry["OID"]." mit $name $type.\n";
        if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"];               // Name wird übergeben für Debug
        else $entry["NAME"]=$name;

        //print_r($deviceList[$name]["Instances"]);
        //print_r($entry);
        $oids=array();
        foreach ($deviceList[$name]["Instances"] as $port => $register)             // wir sind bei den Channels, also kann man instances bereits analysieren
            {
            $oids[$register["OID"]]=$port;              // also für den Fall das ein Device mehrere Instances hat, eigentlich nur bei Homematic der Fall
            }
        if (isset($oids[$entry["OID"]])===false) 
            {
            echo "  >> irgendetwas ist falsch.\n";
            return (false);                                     // nix zum tun, Abbruch
            }
        foreach ($oids as $oid => $port)
            {
            if (isset($this->installedModules["OperationCenter"])) 
                {
                $typedevRegs    = $this->DeviceManager->getHueDeviceType($oid,3,$entry,$debug>1);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
                $deviceList[$name]["Channels"][$port]=$typedevRegs;
                }
            else
                {
                $typedev=false;
                if ($debug>1) echo "             analyse result from getDeviceParameter : ".json_encode($deviceList[$name]["Instances"][$port])."\n";
                if (isset($deviceList[$name]["Instances"][$port]["TYPEDEV"])) $typedev = $deviceList[$name]["Instances"][$port]["TYPEDEV"];
                else echo "         TYPEDEV not found in : ".json_encode($deviceList[$name]["Instances"][$port])."  \n";
                $typedevRegs=array();
                $cids = IPS_GetChildrenIDs($oid);           // für jede Instanz die Children einsammeln
                $register=array();
                if ($debug>1) echo "                  $typedev : ";
                foreach($cids as $cid)
                    {
                    $regName=IPS_GetName($cid);
                    if ($debug>1) echo $regName.",";
                    $register[]=$regName;
                    switch ($typedev)
                        {
                        case "TYPE_SWITCH":
                        case "TYPE_GROUP":
                            if ($regName=="Status") $typedevRegs["STATE"]=$regName;
                            break;
                        case "TYPE_DIMMER":
                            if ($regName=="Status")     $typedevRegs["STATE"]=$regName;
                            if ($regName=="Helligkeit") $typedevRegs["LEVEL"]=$regName;
                            break;
                        case "TYPE_AMBIENT":
                            if ($regName=="Status")         $typedevRegs["STATE"]=$regName;
                            if ($regName=="Helligkeit")     $typedevRegs["LEVEL"]=$regName;
                            if ($regName=="Farbtemperatur") $typedevRegs["AMBIENCE"]=$regName;
                            break;
                        case "TYPE_RGB":                                                        // keine Ahnung wie es zu diesem Type kommt
                            if ($regName=="Status") $typedevRegs["STATE"]=$regName;
                            if ($regName=="Helligkeit") $typedevRegs["LEVEL"]=$regName;
                            if ($regName=="Farbe") $typedevRegs["COLOR"]=$regName;                      // muss aber nicht stimmen
                            break;
                        case "TYPE_SENSOR":
                            break;
                        }
                    }
                if ($debug>1) echo "\n";
                sort($register);
                $registerNew=array();
                $oldvalue="";        
                /* gleiche Einträge eliminieren */
                foreach ($register as $index => $value)
                    {
                    if ($value!=$oldvalue) {$registerNew[]=$value;}
                    $oldvalue=$value;
                    } 
                $deviceList[$name]["Channels"][$port]["RegisterAll"]=$registerNew;
                $deviceList[$name]["Channels"][$port][$typedev]=$typedevRegs;
                }
            if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["Name"]=$deviceList[$name]["Name"];
            else $deviceList[$name]["Channels"][$port]["Name"]=$name;
            }
        return (true);
        }

    }


/* Philips HUE V2  new Modul 
 * Verwenden selbe Library in OperationCenter
 * simple Implementation, means individual functions only for construct, getDeviceParameter
 * change to reading of ConfigurationForm to get more information of typedev in DeviceManagement_Hue
 *
 *      construct                       Übergabe der Namen oder IDs
 *      getDeviceConfiguration
 *      getDeviceParameter
 *      getDeviceChannels
 */

class HardwareHUEV2 extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
    /* wir können devices zusammenlegen wenn gewünscht 
     */

	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "";             // I/O oder Splitter ist der Socket für das Device, hier SEE Client, nicht Teil des Modules
        $this->bridgeID = "{52399872-F02A-4BEB-ACA0-1F6AE04D9663}";             // Configurator, Device Bridge 6786AF05-B089-4BD0-BABA-B2B864CF92E3
        $this->deviceID = ['HUE Button','HUE Light','HUE Device Power','HUE Grouped Light','HUE Light Level','HUE Motion','HUE Relative Rotary','HUE Scene','HUE Temperature'];             // das Gerät selbst
        $this->setInstalledModules();
        if (isset($this->installedModules["OperationCenter"])) 
            {
            IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');
            if ($debug>2) echo "class DeviceManagement aufgerufen:\n";   
            $this->DeviceManager = new DeviceManagement_HueV2($debug>2);              // die Darstellung der ConfigurationForm ist wieder anders als bei Hue
            }
        parent::__construct($config,$debug);                        // wertet das config File gemeinsam für alle aus
        if ($this->combineDevices) 
            {
            if ($debug) echo "    HardwareHUEV2, combineDevices angefordert.\n"; 
            foreach ($this->configuration["combineDevices"] as $idx => $entry)                // Hardwarelist, nocheinmal für alle Geräte
                {
                if (isset($entry["NAME"])) $name=$entry["NAME"];
                else $name=$idx;
                if ($entry["DeviceID"] != "") 
                    {
                    $this->ListofDevices[$entry["DeviceID"]][$entry["OID"]]=$entry["CONFIG"];
                    if (isset($this->ListofDevices[$entry["DeviceID"]]["NAME"])==false) $this->ListofDevices[$entry["DeviceID"]]["NAME"]=$name;
                    }
                }
            //print_r($this->ListofDevices);
            }     
        }

    /* HardwareHUEV2::getDeviceConfiguration 
     * Neudefinition um Check auf gleiche Namen gleich hier einbauen
     * in Homematic wurde ein Device mit mehreren Instanzen über den Doppelpunkt im Namen gelöst, könnte man hier schon richtig machen über DeviceID
     * unique nameing eingeführt, DeviceID um zu erkennen welche Instanzen zusammengehören
     */

    public function getDeviceConfiguration(&$hardware, $device, $hardwareType, $debug=false)
        {
        $config = @IPS_GetConfiguration($device);
        if ($config !== false) 
            {                    
            $devicename = IPS_GetName($device);
            $oldname = $devicename;
            if (isset($hardware[$hardwareType][$devicename]))
                {
                for ($num=1;($num<5);$num++) 
                    {
                    if (isset($hardware[$hardwareType][$devicename.$num])==false) break;
                    }
                $devicename = $devicename.$num;
                echo "HardwareHUEV2::getDeviceConfiguration, warning instance name $oldname exists already, look for new name : $devicename.\n";
                $hardware[$hardwareType][$devicename]["NAME"]=$oldname;
                }
            else $hardware[$hardwareType][$devicename]["NAME"]=$devicename;
            $hardware[$hardwareType][$devicename]["OID"]=$device;
            $hardware[$hardwareType][$devicename]["CONFIG"]=$config;
            $configuration=json_decode($config,true);
            if (isset($configuration["DeviceID"])) $hardware[$hardwareType][$devicename]["DeviceID"]=$configuration["DeviceID"];
            }
        }


    /* HardwareHUEV2::getDeviceCheck
     * Neudefinition, Check um die Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * wenn alles in Ordnung wird $deviceList[$name]["Name"]=$name; angelegt.
     *
     * es wird geprüft ob der name als Index bereits vergeben ist, Index muss ein uniqueName sein.
     * wenn config für createUniqueName ein true ist dann bei doppelten Namen einen neuen Namen definieren
     *
     * Antwort ist true wenn alles in Ordnung verlaufen ist. Ein false führt dazu dass kein Eintrag erstellt wird.
     *
     *  if getDeviceCheck(....)                             // verschiedene checks, zumindest ob schon angelegt ?
     *       {
     *       $object->getDeviceParameter(....);             // Ergebnis von erkannten (Sub) Instanzen wird in die deviceList integriert, eine oder mehrer Instanzen einem Gerät zuordnen
     *       $object->getDeviceChannels(....);              // Ergebnis von erkannten Channels wird in die deviceList integriert, jede Instanz wird zu einem oder mehreren channels eines Gerätes
     *       $object->getDeviceActuators(....);             // Ergebnis von erkannten Actuators wird in die deviceList integriert, Acftuatoren sind Instanzen die wie in IPSHEAT bezeichnet sind
     *       $object->getDeviceInformation(....);             // zusaetzlich Geräteinformation, auch das Gateway
     *       $object->getDeviceTopology(....);              // zusaettlich Topolgie Informationen ablegen
     *       }
     *
     *
     */

    public function getDeviceCheck(&$deviceList, &$name, $type, $entry, $debug=false)
        {
        if ($this->createUniqueName)            // mit uniqueNames arbeiten
            {
            // die DeviceID suchen, wir wollen einen gemeinsamen Namen
            if (isset($entry["CONFIG"]))
                {
                $config=json_decode($entry["CONFIG"],true);
                if (isset($config["DeviceID"])) $deviceID=$config["DeviceID"];
                if (isset($this->ListofDevices[$deviceID]["NAME"])) 
                    {
                    if ($name != $this->ListofDevices[$deviceID]["NAME"]) 
                        {
                        echo "HardwareHUEV2::getDeviceCheck, OID ".$entry["OID"].", DeviceID: $deviceID, Name wird $name -> ".$this->ListofDevices[$deviceID]["NAME"]." geändert !\n";
                        //print_r($this->ListofDevices);
                        $name = $this->ListofDevices[$deviceID]["NAME"];
                        $deviceList[$name]["Name"]=$name;                           //realname ist Teil von instances
                        $deviceList[$name]["Type"]=$type;
                        return (true);
                        }
                    }
                }
            /* Fehlerprüfung, für alle die keinen gemeinsamen deviceID Eintrag haben */
            if (isset($deviceList[$name])) 
                {                    
                if ($debug) echo "          ------------------------------------------------------------------------------------------------------\n";
                echo "          getDeviceCheck:Allgemein, create unique Names aktiviert, Name wird von $name auf $name$type geändert:\n";
                $realname=$name;
                $name = $name.$type;            // wird zurückgegeben, nur ein Pointer
                $deviceList[$name]["Name"]=$realname;
                $deviceList[$name]["Type"]=$type;
                return (true);
                }
            else 
                {
                $deviceList[$name]["Name"]=$name;
                $deviceList[$name]["Type"]=$type;
                return (true);
                }
            }
        else                                // bei gleichen Namen abbrechen
            {
            /* Fehlerprüfung */
            if (isset($deviceList[$name])) 
                {                 
                echo "          >>getDeviceCheck:Allgemein   Fehler, Name \"$name\" bereits definiert. Anforderung nach Type $type wird ignoriert.\n";                    
                print_r($deviceList[$name]);
                return(false);
                }
            else return (true);
            }            
        }

    /* HardwareHUEV2::getDeviceParameter
     * die Device Liste aus der Geräteliste erstellen 
     * Antwort ist ein Geräteeintrag für Type und Instances
     *
     * Standard wäre:         $deviceList[$name]["Type"]=$type; $entry["NAME"]=$name; $deviceList[$name]["Instances"][]=$entry;
     *                              entry[TYPEDEV], entry[OID], entry[NAME],entry[CONFIG] bereits übernommen 
     *                                  TYPE_SWITCH | TYPE_AMBIENT | TYPE_DIMMER
     */
    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        //$debug=true;    
        /* Fehlerpüfung, erfolgt bereits checkDevice, bei doppelten Namen wird entweder ein uniqueName erstellt oder abgebrochen
        if (isset($deviceList[$name])) 
            {
            echo "          >>HardwareHUE::getDeviceParameter, Fehler, Name \"".$name."\" bereits definiert.\n";
            return(false);
            }            
        /* Durchführung */
        if ($debug) echo "          getDeviceParameters:HUE V2    aufgerufen. Eintrag \"$name\" hinterlegt.\n";
        //print_R($entry);      
        //if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"];               // Name wird übergeben für Debug
        //else $entry["NAME"]=$name;
        if (isset($this->installedModules["OperationCenter"])) 
            {
            if (isset($entry["OID"]))
                {
                $instanz=$entry["OID"];
                $typedev    = $this->DeviceManager->getHueDeviceType($instanz,0,$entry,$debug>1);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
                //if ($debug) echo "    TYPEDEV: $typedev";
                $entry["TYPEDEV"]=$typedev;
                }
            }      

        $deviceList[$name]["Type"]=$type;
        $deviceList[$name]["Instances"][]=$entry;
        return (true);
        }


    /* HardwareHUEV2::getDeviceChannels
     * HueV2 hat pro Gerät mehrere Instanzen, die Instanzen werden erst nach jedem Aufruf von getDeviceParameter hinzugefügt, bis es alle sind
     * die Device Liste (Geräteliste) um die Channels erweitern, ein Gerät hat die Kategorien Instances,Channels,Actuators, es kann mehrer Einträge in Instances und Channels haben, 
     * es können mehr channels als instances sein, es können aber auch gar keine channels sein - eher unüblich
     * alle instances durchgehen, OIDs einsammeln mit Zuordnung Port abspeichern
     * zumindest RegisterAll schreiben
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)                 // class Hardware
        {
        if ($debug) echo "          HardwareHUEV2::getDeviceChannels, aufgerufen für ".$entry["OID"]." mit $name $type.\n";
        if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"];               // Name wird übergeben für Debug
        else $entry["NAME"]=$name;

        //print_r($deviceList[$name]["Instances"]);
        //print_r($entry);
        $oids=array();
        foreach ($deviceList[$name]["Instances"] as $port => $register)             // wir sind bei den Channels, also kann man instances bereits analysieren
            {
            if ($register["OID"]==$entry["OID"]) $oids[$register["OID"]]=$port;              // für den Fall das ein Device mehrere Instances hat, den Channel der Instanz zuordnen
            }
        if (isset($oids[$entry["OID"]])===false) 
            {
            echo "  >> irgendetwas ist falsch.\n";
            return (false);                                     // nix zum tun, Abbruch
            }
        // die Instanzen der Reihe nach durchgehen und für jede Instanz, Nummer nach der Reihenfolge wie angelegt bearbeiten, entry stimmt nicht weil es ist von der Instanz die als letztes aufgerufen wurde
        foreach ($oids as $oid => $port)
            {
            if (isset($this->installedModules["OperationCenter"])) 
                {
                $typedevRegs    = $this->DeviceManager->getHueDeviceType($oid,4,$entry,$debug>1);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ in standardisierter Weise aus */
                $deviceList[$name]["Channels"][$port]=$typedevRegs;
                }
            else                // wird eigentlich nicht verwendet
                {
                $typedev=false;
                if ($debug>1) echo "             analyse result from getDeviceParameter : ".json_encode($deviceList[$name]["Instances"][$port])."\n";
                if (isset($deviceList[$name]["Instances"][$port]["TYPEDEV"])) $typedev = $deviceList[$name]["Instances"][$port]["TYPEDEV"];
                else echo "         TYPEDEV not found in : ".json_encode($deviceList[$name]["Instances"][$port])."  \n";
                $typedevRegs=array();
                $cids = IPS_GetChildrenIDs($oid);           // für jede Instanz die Children einsammeln
                $register=array();
                if ($debug>1) echo "                  $typedev : ";
                foreach($cids as $cid)
                    {
                    $regName=IPS_GetName($cid);
                    if ($debug>1) echo $regName.",";
                    $register[]=$regName;
                    switch ($typedev)
                        {
                        case "TYPE_SWITCH":
                        case "TYPE_GROUP":
                            if ($regName=="Status") $typedevRegs["STATE"]=$regName;
                            break;
                        case "TYPE_DIMMER":
                            if ($regName=="Status")     $typedevRegs["STATE"]=$regName;
                            if ($regName=="Helligkeit") $typedevRegs["LEVEL"]=$regName;
                            break;
                        case "TYPE_AMBIENT":
                            if ($regName=="Status")         $typedevRegs["STATE"]=$regName;
                            if ($regName=="Helligkeit")     $typedevRegs["LEVEL"]=$regName;
                            if ($regName=="Farbtemperatur") $typedevRegs["AMBIENCE"]=$regName;
                            break;
                        case "TYPE_RGB":                                                        // keine Ahnung wie es zu diesem Type kommt
                            if ($regName=="Status") $typedevRegs["STATE"]=$regName;
                            if ($regName=="Helligkeit") $typedevRegs["LEVEL"]=$regName;
                            if ($regName=="Farbe") $typedevRegs["COLOR"]=$regName;                      // muss aber nicht stimmen
                            break;
                        case "TYPE_SENSOR":
                            break;
                        }
                    }
                if ($debug>1) echo "\n";
                sort($register);
                $registerNew=array();
                $oldvalue="";        
                /* gleiche Einträge eliminieren */
                foreach ($register as $index => $value)
                    {
                    if ($value!=$oldvalue) {$registerNew[]=$value;}
                    $oldvalue=$value;
                    } 
                $deviceList[$name]["Channels"][$port]["RegisterAll"]=$registerNew;
                $deviceList[$name]["Channels"][$port][$typedev]=$typedevRegs;
                }
            if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["Name"]=$deviceList[$name]["Name"];
            else $deviceList[$name]["Channels"][$port]["Name"]=$name;
            }
        return (true);
        }

    }

/* Logitechs Harmony, seldon used to control infrared remote controlled devices
 * 
 * simple Implementation, means individual functions only for construct, getDeviceParameter
 *      construct
 */
class HardwareHarmony extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "{03B162DB-7A3A-41AE-A676-2444F16EBEDF}";
        $this->bridgeID = "{E1FB3491-F78D-457A-89EC-18C832F4E6D9}";
        $this->deviceID = "{B0B4D0C2-192E-4669-A624-5D5E72DBB555}";
        parent::__construct($config,$debug);        
        }

    }


/* depricated
 * FHT Family, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 *
 *
 *
 */
class HardwareFHTFamily extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}";         // FHT Devices
        parent::__construct($config,$debug);        
        }

    /* HardwareFHTFamily::getDeviceChannels
     * der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
     * nachdem bereits eine oder mehrere Instanzen einem Gerät zugeordnet wurden muss nicht mehr kontrolliert werden ob es das Gerät schon gibt
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)
        {
        $debug=true;        // overwrite for local debug
        if ($debug) echo "          getDeviceChannels:FHT aufgerufen für \"".$entry["OID"]."\" mit $name $type.\n";
        //echo "Bereits erstellter/bearbeiteter Eintrag in der deviceList über vorhandene Instanzen:\n";        print_r($deviceList[$name]["Instances"]);
        //echo "Übergebene zusätzliche Parameter:\n";        print_r($entry);
        $oids=array();
        foreach ($deviceList[$name]["Instances"] as $port => $register) 
            {
            $oids[$register["OID"]]=$port;
            }
        //echo "Aus den Instanzen ermittelte OIDs:\n"; print_r($oids);
        if (isset($oids[$entry["OID"]])===false) echo "  >> irgendetwas ist falsch, keine OID aus entry übergeben.\n";
        foreach ($oids as $oid => $port)
            {
            $typedev=$this->getFHTDeviceType($oid,4,true);
            //echo "Werte für $oid aus getFHTDeviceType:\n"; print_r($result);
            if ($typedev<>"")  
                {
                /* Umstellung der neutralen Ausgabe von typedev auf Channel typische Ausgaben 
                if (isset(($typedev["Type"])))  $deviceList[$name]["Channels"][$port]["TYPECHAN"]=$typedev["Type"];     // umsetzen auf Channeltypischen Filter
                else echo "      >>getDeviceChannels Fehler, Name \"$name\" kein TYPECHAN ermittelt.\n";
                if (isset(($typedev["Register"])))  $deviceList[$name]["Channels"][$port]["Register"]=$typedev["Register"];
                else 
                    {
                    echo "      >>getDeviceChannels Fehler, Name \"$name\" kein Register ermittelt. typedev[register] from getHomematicDeviceType für $instanz / \"$name\" not defined.\n";
                    }
                if (isset(($typedev["RegisterAll"])))  $deviceList[$name]["Channels"][$port]["RegisterAll"]=$typedev["RegisterAll"];
                else echo "      >>getDeviceChannels Fehler, Name \"$name\" kein RegisterAll ermittelt.\n";
                */
                $deviceList[$name]["Channels"][$port]=$typedev;
                if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["Name"]=$deviceList[$name]["Name"]; 
                else $deviceList[$name]["Channels"][$port]["Name"]=$name;               
                }
            else echo "   >>getDeviceChannels, Fehler $instanz: keine Channels ermittelt.\n";
            }
        echo "===> Ergebnis getDeviceChannels:\n";
        print_r($deviceList[$name]["Channels"]);
        return (true);
        }

    /*********************************
     *
     * gibt für eine FHT Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     *
     *
     *
     ***********************************************/

    function getFHTDeviceType($instanz, $outputVersion=false, $debug=false)
	    {
    	$cids = IPS_GetChildrenIDs($instanz);
	    $fht=array();
    	foreach($cids as $cid)
	    	{
		    $fht[$cid]=IPS_GetName($cid);
    		}
    	return ($this->FHTDeviceType($fht,$outputVersion, $debug));
    	}

    /*********************************
     * 
     * FHT Device Type, genaue Auswertung nur mehr an einer, dieser Stelle machen 
     *
     * Übergabe ist ein array aus Variablennamen/Children einer Instanz oder die Sammlung aller Instanzen die zu einem Gerät gehören
     * übergeben wird das Array das alle auch doppelte Eintraege hat. Folgende Muster werden ausgewertet:
     *
     * Es gibt unterschiedliche Arten der Ausgabe, eingestellt mit outputVersion
     *   false   die aktuelle Kategorisierung
     *
     *
     ****************************************/

    private function FHTDeviceType($register, $outputVersion=false, $debug=false)
        {
		sort($register);
        $registerNew=array();
    	$oldvalue="";        
        /* gleiche Einträge eliminieren */
	    foreach ($register as $index => $value)
		    {
	    	if ($value!=$oldvalue) {$registerNew[]=$value;}
		    $oldvalue=$value;
			}         
        $found=true; 
        if ($debug) 
            {
            echo "                FHTDeviceType: Info mit Debug aufgerufen. Parameter \"";
            foreach ($registerNew as $entry) echo "$entry ";
            echo "\"\n";
            }

        /*--Thermostat-----------------------------------*/
        if ( (array_search("Temperatur",$registerNew) !== false) && (array_search("Soll Temperatur",$registerNew) !== false) )          /* Sensor Temperatur , Sollwert */
            {
            //print_r($registerNew);
            echo "                     Thermostat Soll Temperatur und Sensor Temperatur gefunden.\n";
            $resultRegTemp["TEMPERATURE"]="Temperatur";
            $resultReg["SET_TEMPERATURE"]="Soll Temperatur";

            $result[0] = "Wandthermostat";
            $result[1] = "Funk Wandthermostat";
            $result[2] = "TYPE_THERMOSTAT";            
            $result[3]["Type"] = "TYPE_THERMOSTAT";            
            $result[3]["Register"] = $resultReg;
            $result[3]["RegisterAll"]=$registerNew;

            $result[4]["TYPECHAN"] = "TYPE_THERMOSTAT,TYPE_METER_TEMPERATURE";              
            $result[4]["TYPE_METER_TEMPERATURE"] = $resultRegTemp;
            $result[4]["TYPE_THERMOSTAT"]=$resultReg;
            $result[4]["RegisterAll"]=$registerNew;
            }
        else $found=false;

        if ($found) 
            {
            if ($outputVersion==false) return($result[2]);
            elseif ($outputVersion==2) return ($result[1]);
            elseif ($outputVersion==3) return ($result[3]);
            elseif ($outputVersion==4) return ($result[4]);
			else return ($result[0]);
            }
        else 
            {
            if ($outputVersion>100) 
                {
                $result = "";
                foreach ($registerNew as $entry) $result .= $entry." ";
                return ($result);
                }
            else return (false);
            }
        }

    }

/* depricated
 * FS20Family, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 *
 *
 *
 */
class HardwareFS20Family extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}";
        parent::__construct($config,$debug);        
        }

    /* HardwareFS20Family::getDeviceChannels
     * der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
     * nachdem bereits eine oder mehrere Instanzen einem Gerät zugeordnet wurden muss nicht mehr kontrolliert werden ob es das Gerät schon gibt
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)
        {
        $debug=true;        // overwrite for local debug
        if ($debug) echo "          getDeviceChannels:FS20 aufgerufen für \"".$entry["OID"]."\" mit $name $type.\n";
        //echo "Bereits erstellter/bearbeiteter Eintrag in der deviceList über vorhandene Instanzen:\n";        print_r($deviceList[$name]["Instances"]);
        //echo "Übergebene zusätzliche Parameter:\n";        print_r($entry);
        $oids=array();
        foreach ($deviceList[$name]["Instances"] as $port => $register) 
            {
            $oids[$register["OID"]]=$port;
            }
        //echo "Aus den Instanzen ermittelte OIDs:\n"; print_r($oids);
        if (isset($oids[$entry["OID"]])===false) echo "  >> irgendetwas ist falsch, keine OID aus entry übergeben.\n";
        foreach ($oids as $oid => $port)
            {
            $typedev=$this->getFS20DeviceType($oid,4,true);
            //echo "Werte für $oid aus getFS20DeviceType:\n"; print_r($result);
            if ($typedev<>"")  
                {
                /* Umstellung der neutralen Ausgabe von typedev auf Channel typische Ausgaben 
                if (isset(($typedev["Type"])))  $deviceList[$name]["Channels"][$port]["TYPECHAN"]=$typedev["Type"];     // umsetzen auf Channeltypischen Filter
                else echo "      >>getDeviceChannels Fehler, Name \"$name\" kein TYPECHAN ermittelt.\n";
                if (isset(($typedev["Register"])))  $deviceList[$name]["Channels"][$port]["Register"]=$typedev["Register"];
                else 
                    {
                    echo "      >>getDeviceChannels Fehler, Name \"$name\" kein Register ermittelt. typedev[register] from getHomematicDeviceType für $instanz / \"$name\" not defined.\n";
                    }
                if (isset(($typedev["RegisterAll"])))  $deviceList[$name]["Channels"][$port]["RegisterAll"]=$typedev["RegisterAll"];
                else echo "      >>getDeviceChannels Fehler, Name \"$name\" kein RegisterAll ermittelt.\n";
                */
                $deviceList[$name]["Channels"][$port]=$typedev;
                if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["Name"]=$deviceList[$name]["Name"];   
                else $deviceList[$name]["Channels"][$port]["Name"]=$name;             
                }
            else echo "   >>getDeviceChannels, Fehler $instanz: keine Channels ermittelt.\n";
            }
        echo "===> Ergebnis getDeviceChannels:\n";
        print_r($deviceList[$name]["Channels"]);
        return (true);
        }

    /*********************************
     *
     * gibt für eine FS20 Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     *
     *
     *
     ***********************************************/

    function getFS20DeviceType($instanz, $outputVersion=false, $debug=false)
	    {
    	$cids = IPS_GetChildrenIDs($instanz);
	    $fs20=array();
    	foreach($cids as $cid)
	    	{
		    $fs20[$cid]=IPS_GetName($cid);
    		}
    	return ($this->FS20DeviceType($fs20,$outputVersion, $debug));
    	}

    /*********************************
     * 
     * FHT Device Type, genaue Auswertung nur mehr an einer, dieser Stelle machen 
     *
     * Übergabe ist ein array aus Variablennamen/Children einer Instanz oder die Sammlung aller Instanzen die zu einem Gerät gehören
     * übergeben wird das Array das alle auch doppelte Eintraege hat. Folgende Muster werden ausgewertet:
     *
     * Es gibt unterschiedliche Arten der Ausgabe, eingestellt mit outputVersion
     *   false   die aktuelle Kategorisierung
     *
     *
     ****************************************/

    private function FS20DeviceType($register, $outputVersion=false, $debug=false)
        {
		sort($register);
        $registerNew=array();
    	$oldvalue="";        
        /* gleiche Einträge eliminieren */
	    foreach ($register as $index => $value)
		    {
	    	if ($value!=$oldvalue) {$registerNew[]=$value;}
		    $oldvalue=$value;
			}         
        $found=true; 
        if ($debug) 
            {
            echo "                FS20DeviceType: Info mit Debug aufgerufen. Parameter \"";
            foreach ($registerNew as $entry) echo "$entry ";
            echo "\"\n";
            }

        /*--Schalter-----------------------------------*/
        if ( (array_search("Status",$registerNew) !== false) )          /* Schalter */
            {
            //print_r($registerNew);
            echo "                     Schalter gefunden.\n";
            $resultReg["STATE"]="Status";

            $result[0] = "Schaltaktor 1-fach";
            $result[1] = "Funk Schaltaktor 1-fach";
            $result[2] = "TYPE_SWITCH";            
            $result[3]["Type"] = "TYPE_SWITCH";            
            $result[3]["Register"] = $resultReg;
            $result[3]["RegisterAll"]=$registerNew;

            $result[4]["TYPECHAN"] = "TYPE_SWITCH";              
            $result[4]["TYPE_SWITCH"] = $resultReg;
            $result[4]["RegisterAll"]=$registerNew;
            }
        else $found=false;

        if ($found) 
            {
            if ($outputVersion==false) return($result[2]);
            elseif ($outputVersion==2) return ($result[1]);
            elseif ($outputVersion==3) return ($result[3]);
            elseif ($outputVersion==4) return ($result[4]);
			else return ($result[0]);
            }
        else 
            {
            if ($outputVersion>100) 
                {
                $result = "";
                foreach ($registerNew as $entry) $result .= $entry." ";
                return ($result);
                }
            else return (false);
            }
        }

    }


/* depricated 
 * FS20ExFamily, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 *
 *
 */
class HardwareFS20ExFamily extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{56800073-A809-4513-9618-1C593EE1240C}";
        parent::__construct($config, $debug);        
        }

    }


/*********************************
 * 
 * OpCentCam, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 * 
 * __construct
 * getDeviceID,getbridgeID,getsocketID von der übergeordneten class da private definiert,hier anlegen
 * getDeviceIDInstances von der übergeordneten class : return ($this->modulhandling->getInstances($this->getDeviceID()));
 *
 * ModuleHandling ist in AllgemeineDefinitionen
 *
 ****************************************/

class HardwareOpCentCam extends Hardware
	{
	protected $operationCenter;                     // class
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        if ($debug) echo "construct HardwareOpCentCam aufgerufen.\n";    
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{28E40EBC-F9E3-52B7-E1F9-8F845E79956C}";         // Wir brauchen zumindest eine DeviceID, auch wenn es eine erfundene ist
        $this->setInstalledModules();
        if (isset($this->installedModules["OperationCenter"])) 
            {
            IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');   
            $this->operationCenter = new CamOperation();            // chold vom OperationCenter
            }

        parent::__construct($config,$debug);        
        }

    /* getDeviceIDInstances eigene Routine, da keine Instanz
     *
     */

    public function getDeviceIDInstances()
        {
        switch ($this->deviceID)
            {
            case "{28E40EBC-F9E3-52B7-E1F9-8F845E79956C}":
                //echo "Ausgabe Instanzen der Cameras.\n";
                $IDs=$this->operationCenter->getPictureCategoryIDs();
                return ($IDs);
                break;
            default:
                return (false);
            }
        }

    /* HardwareOpCentCam::getDeviceConfiguration eigene Routine, da keine Instanz
     *
     */

    public function getDeviceConfiguration(&$hardware, $device, $hardwareType, $debug=false)
        {
        $hardware[$hardwareType][IPS_GetName($device)]["OID"]=$device;
        $hardware[$hardwareType][IPS_GetName($device)]["CONFIG"]=json_encode(array());
        }

    /* die Device Liste aus der Geräteliste erstellen 
     * Antwort ist ein Geräteeintrag
     *
     * Standard wäre:         $deviceList[$name]["Type"]=$type; $entry["NAME"]=$name; $deviceList[$name]["Instances"][]=$entry;
     */

    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        /* Fehlerpüfung erfolgt bereits in CheckDevice
        //$debug=true;
        if (isset($deviceList[$name])) 
            {
            echo "          >>Fehler  HardwareOpCentCam::getDeviceParameter , Name \"".$name."\" bereits definiert.\n";
            return(false);
            }            
        /* Durchführung */
        if ($debug) echo "          HardwareOpCentCam::getDeviceParameters aufgerufen. Eintrag \"$name\" wird hinterlegt.\n";            

        $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
        echo "                            entry CONFIG is  ".str_pad($name,22)." : ".json_encode($result)."\n";

        $deviceList[$name]["Type"]=$type;
        if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"]; 
        else $entry["NAME"]=$name;
        $deviceList[$name]["Instances"][]=$entry;
        return (true);
        }


    /* HardwareOpCentCam::getDeviceChannels
     * die Device Liste (Geräteliste) um die Channels erweitern, ein Gerät kann mehrere Instances und Channels haben, 
     * es können mehr channels als instances sein, es können aber auch gar keine channels sein - eher unüblich
     *

                                    "TYPE_MOTION" => array(
                                        'MOTION' => 'MOTION',
                                        'BRIGHTNESS' => 'BRIGHTNESS',
                                                  ),
                                   "RegisterAll" => array(
                                        '0' => 'CamStream_Kueche',
                                        '1' => 'Cam_Motion',
                                        '2' => 'Cam_PhotoCount',
                                        '3' => 'Cam_letzteBewegung',
                                                  ),                                                  
                              'TYPECHAN' => 'TYPE_MOTION',
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo "          HardwareOpCentCam::getDeviceChannels aufgerufen für ".$entry["OID"]." mit $name $type. TYPE_MOTION anlegen.\n";
        //print_r($deviceList[$name]["Instances"]);
        //print_r($entry);
        $oids=array();
        foreach ($deviceList[$name]["Instances"] as $port => $register) 
            {
            $oids[$register["OID"]]=$port;
            }
        if (isset($oids[$entry["OID"]])===false) echo "  >> irgendetwas ist falsch.\n";
        foreach ($oids as $oid => $port)
            {
            $cids = IPS_GetChildrenIDs($oid);
            $register=array();
            foreach($cids as $cid)
                {
                $childName = IPS_GetName($cid);
                switch ($childName)
                    {
                    case "Cam_Motion":
                        $typeMotion=array("MOTION" => "Cam_Motion");
                        break;
                    }
                $register[]= $childName;
                }
            sort($register);
            $registerNew=array();
            $oldvalue="";        
            /* gleiche Einträge eliminieren */
            foreach ($register as $index => $value)
                {
                if ($value!=$oldvalue) {$registerNew[]=$value;}
                $oldvalue=$value;
                } 
            $deviceList[$name]["Channels"][$port]["RegisterAll"]=$registerNew;
            if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["Name"]=$deviceList[$name]["Name"];
            else $deviceList[$name]["Channels"][$port]["Name"]=$name;
            if (isset($typeMotion["MOTION"])) 
                {
                $deviceList[$name]["Channels"][$port]["TYPE_MOTION"] = $typeMotion;
                $deviceList[$name]["Channels"][$port]["TYPECHAN"] = "TYPE_MOTION";  
                }
            }
        return (true);
        }

    }


/*********************************
 * 
 * IpsHeat, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 * 
 * __construct
 * getDeviceID,getbridgeID,getsocketID von der übergeordneten class da private definiert,hier anlegen
 * getDeviceIDInstances von der übergeordneten class : return ($this->modulhandling->getInstances($this->getDeviceID()));
 *
 * ModuleHandling ist in AllgemeineDefinitionen
 *
 * kann gleiche Namen
 *
 ****************************************/

class HardwareIpsHeat extends Hardware
	{
	protected $ipsheatManager;                     // class
    protected $socketID, $bridgeID, $deviceID;
    protected $config;                             // Abkuerzer zwischen den Routinen, Config übergben 
    protected $debug;
	
	public function __construct($config=false,$debug=false)
		{
        if ($debug) echo "construct HardwareIpsHeat aufgerufen.\n"; 
        $this->debug=$debug;   
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{FFCD5EAF-F119-D668-7601-A6898C0DC026}";         // Wir brauchen zumindest eine DeviceID, auch wenn es eine erfundene ist
        $this->setInstalledModules();
        if (isset($this->installedModules["Stromheizung"])) 
            {
            IPSUtils_Include ("IPSHeat.inc.php",  "IPSLibrary::app::modules::Stromheizung");   
            $this->ipsheatManager = new IPSHeat_Manager();            // class from Stromheizung
            }

        parent::__construct($config,$debug);        
        }

    /* HardwareIpsHeat::getDeviceIDInstances eigene Routine, da keine Instanz
     * liefert eine einfache Liste mit deviceIDs
     */

    public function getDeviceIDInstances()
        {
        switch ($this->deviceID)
            {
            case "{FFCD5EAF-F119-D668-7601-A6898C0DC026}":
                //echo "Ausgabe Instanzen der IPSHeat DeviceGroups and Programs.\n";
                return (array_merge($this->ipsheatManager->getGroupIDs(),$this->ipsheatManager->getProgramIDs()));
                break;
            default:
                return (false);
            }
        }

    /* HardwareIpsHeat::getDeviceConfiguration eigene Routine, da keine Instanz
     *
     * $devices=$object->getDeviceIDInstances();                                                                                    die Liste der IDs von Groups und Scenes
     * foreach ($devices as $device) $object->getDeviceConfiguration($hardware, $device, $hardwareType, $debug);                    $hardwareType is IpsHeat, device ist dann die OID
     *
     * Nachdem es keine Instanz gibt,kann man die Config wieder bemühen, Abhängig vom Parent eine Groups oder ein Programs
     *
     */

    public function getDeviceConfiguration(&$hardware, $device, $hardwareType, $debug=false)
        {
        $name = IPS_GetName($device);
        //echo "              HardwareIpsHeat::getDeviceConfiguration , $name .   \n";

        $configGroups = IPSHeat_GetGroupConfiguration();
        $configurationG=$this->ipsheatManager->getConfigGroups($configGroups,$this->debug); 
        if (isset($configurationG[$name]))                   // erst einmal die Groups
            {
            //print_r($configurationG[$name]);
            $hardware[$hardwareType][$name]["OID"]=$device;
            $hardware[$hardwareType][$name]["CONFIG"]=json_encode($configurationG[$name]);
            }

        $configPrograms = IPSHeat_GetProgramConfiguration();
        $configProgram=array();
        $configurationP = $this->ipsheatManager->getConfigPrograms($configPrograms,$this->debug);
        if (isset($configurationP[$name]))                   // dann die Programs
            {
            $hardware[$hardwareType][$name]["OID"]=$device;
            $configProgram["Name"]=$name;
            $configProgram["Type"]="Program";
            $configProgram["Scenes"]=$configurationP[$name];
            $hardware[$hardwareType][$name]["CONFIG"]=json_encode($configProgram);;
            }
        }


    /* HardwareIpsHeat::getDeviceParameter
     * die Device Liste aus der Geräteliste erstellen 
     * Antwort ist ein Geräteeintrag
     *
     * Standard wäre:         $deviceList[$name]["Type"]=$type; $entry["NAME"]=$name; $deviceList[$name]["Instances"][]=$entry;
     *
     * für gleiche Namen gibt es schon eine Implementierung, Name ist $deviceList[$name]["Name"], name ist nur ein index
     * hier noch eine zusätzliche Routine nameIpsHeat_IpsHeat (???)
     *
     */

    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        $newname=false;
        /* Fehlerpüfung nicht implementiert, checkDevice setzt bereits deviceList und bei doppelten Namen wird ein uniqueName erzeugt oder bricht ab 
        //$debug=true;
        
        if (isset($deviceList[$name])) 
            {
            $newname = $name."__".$type;
            echo "          >>Name \"".$name."\" bereits definiert. Try $newname\n";
            if (isset($deviceList[$newname]))
                {
                echo "          >>Fehler  HardwareIpsHeat::getDeviceParameter , Name \"".$name."\" und  \"".$newname."\"bereits definiert.\n";
                return(false);
                }
            }            
        /* Durchführung */
        if ($debug) echo "          HardwareIpsHeat::getDeviceParameters aufgerufen. Eintrag \"$name\" wird hinterlegt.\n";            

        $result=json_decode($entry["CONFIG"],true);   // true, als array zurückgeben , Config in Instances
        if (isset($result["Type"])===false) print_r($result);
        else
            {
            switch (strtoupper($result["Type"]))
                {
                case "SWITCH":
                    $entry['TYPEDEV'] = 'TYPE_SWITCH';
                    break;
                case "DIMMER":
                    $entry['TYPEDEV'] = 'TYPE_DIMMER';
                    break;
                case "AMBIENT":
                    $entry['TYPEDEV'] = 'TYPE_AMBIENT';
                    break;
                case "RGB":
                    $entry['TYPEDEV'] = 'TYPE_RGB';         // oder RGBW  check
                    break;
                case "THERMOSTAT":
                    $entry['TYPEDEV'] = 'TYPE_THERMOSTAT';
                    break;
                case "PROGRAM":
                    $entry['TYPEDEV'] = 'TYPE_PROGRAM';
                    break;                    
                default:
                    echo "     >> Warning, HardwareIpsHeat::getDeviceParameters, do not know Type ".$result["Type"]."   \n";
                    break;
                }
            }
        if ($debug) echo "                            entry CONFIG is  ".str_pad($name,22)." : ".json_encode($result)."\n";

        if ($newname) 
            {
            $deviceList[$newname]["Type"]=$type;
            $entry["NAME"]=$name; 
            $deviceList[$newname]["Instances"][]=$entry;
            return($newname);                     // Namensänderung beantragen, aber nur für Index
            }
        else    
            {
            $deviceList[$name]["Type"]=$type;
            if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"];
            else $entry["NAME"]=$name; 
            $deviceList[$name]["Instances"][]=$entry;
            return (true);
            }
        }

    /* HardwareIpsHeat::getDeviceChannels
     * die Device Liste (Geräteliste) um die Channels erweitern, ein Gerät kann mehrere Instances und Channels haben, 
     * es können mehr channels als instances sein, es können aber auch gar keine channels sein - eher unüblich
     *
                                   "TYPE_AMBIENT" => array(
                                        'AMBIENCE' => 'Farbtemperatur',
                                        'STATE' => 'Status',
                                        'LEVEL' => 'Helligkeit',    ),
                                    "TYPE_MOTION" => array(
                                        'MOTION' => 'MOTION',
                                        'BRIGHTNESS' => 'BRIGHTNESS',   ),
                                   "RegisterAll" => array(
                                        '0' => 'CamStream_Kueche',
                                        '1' => 'Cam_Motion',
                                        '2' => 'Cam_PhotoCount',
                                        '3' => 'Cam_letzteBewegung',      ),                                                  
                              'TYPECHAN' => 'TYPE_MOTION',
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo "          HardwareIpsHeat::getDeviceChannels aufgerufen für ".$entry["OID"]." mit $name $type.";
        //print_r($deviceList[$name]["Instances"]);
        //print_r($entry);
        $oids=array();
        foreach ($deviceList[$name]["Instances"] as $port => $registerEntry) 
            {
            $oids[$registerEntry["OID"]]=$port;
            if ($debug) echo $registerEntry["TYPEDEV"]." anlegen. Port $port has Instance with OID ".$registerEntry["OID"].".";
            }
        if ($debug) echo "\n";
        if (isset($oids[$entry["OID"]])===false) echo "  >> irgendetwas ist falsch.\n";
        foreach ($oids as $oid => $port)            // die einzelnen Ports der Instanzen durchgehen, bei IPSHeat immer nur eins, und es gibt keine Instanz sondern nur Variablen die mit # ergänzt werden
            {
            $cids = IPS_GetChildrenIDs(IPS_GetParent($oid));
            $register=array(); $search=IPS_GetName($oid); $registerDev=array();
            foreach($cids as $cid)                                  // alle IPSHeat Groups durchgehen, zumindest der Name oder der Teil des Namens vor # muss stimmen
                {
                $childName = IPS_GetName($cid);
                $childNameParts = explode("#",$childName);
                if ($childNameParts[0]==$search) 
                    {
                    if ($debug) echo "             ".str_pad($childName,24)." ";
                    $register[]= $childName;
                    if (isset($childNameParts[1]))
                        {
                        if ($debug) echo "\"".$childNameParts[0]."\" # \"".$childNameParts[1]." ";
                        switch (strtoupper($childNameParts[1]))
                            {
                            case "LEVEL":
                                $registerDev["LEVEL"]=$childName;
                                break;
                            case "COLTEMP":
                                $registerDev["COLTEMP"]=$childName;
                                break;
                            case "COLOR":
                                $registerDev["COLOR"]=$childName;
                                break;   
                            case "MODE":
                                $registerDev["MODE"]=$childName;
                                break;                                                              
                            case "TEMP":
                                $registerDev["TEMP"]=$childName;
                                break;
                            default:
                                echo "                  >>HardwareIpsHeat::getDeviceChannels, \"".$childNameParts[1]."\" not found.\n";
                                break;
                            }
                        }
                    else 
                        {
                        switch ($deviceList[$name]["Instances"][$port]["TYPEDEV"])
                            {
                            case "TYPE_PROGRAM":
                                $registerDev["PROGRAM"]=$childName;
                                break;
                            default:    
                                $registerDev["STATE"]=$childName;
                                break;
                            }
                        }
                    if ($debug) echo "\n";
                    }
                }
            sort($register);
            $registerNew=array();
            $oldvalue="";        
            /* gleiche Einträge eliminieren */
            foreach ($register as $index => $value)
                {
                if ($value!=$oldvalue) {$registerNew[]=$value;}
                $oldvalue=$value;
                } 
            $deviceList[$name]["Channels"][$port]["RegisterAll"]=$registerNew;
            if (isset($deviceList[$name]["Name"])) $deviceList[$name]["Channels"][$port]["Name"]=$deviceList[$name]["Name"];
            else $deviceList[$name]["Channels"][$port]["Name"]=$name;
            if (isset($registerEntry["TYPEDEV"])) 
                {
                $deviceList[$name]["Channels"][$port][$registerEntry["TYPEDEV"]] = $registerDev;
                $deviceList[$name]["Channels"][$port]["TYPECHAN"] = $registerEntry["TYPEDEV"];  
                }
            }
            
        return (true);
        }

    /* die Device Liste (Geräteliste) um die Beschreibung der Topologie erweitern
     *
     */

    public function getDeviceTopology(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo "          HardwareIpsHeat::getDeviceTopology aufgerufen. Keine Funktion für \"$name\" hinterlegt.\n";
        if ($debug>1) print_R($deviceList[$name]);
        return (true);
        }

    }

/*********************************
 * 
 * EchoControl, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 *
 *
 *
 ****************************************/

class HardwareEchoControl extends Hardware
	{
	
    protected $bridgeID, $deviceID;
	
	public function __construct($config=false,$debug=false)
		{
        $this->socketID = "{C7F853A4-60D2-99CD-A198-2C9025E2E312}";            
        $this->bridgeID = "{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}";
        $this->deviceID = "{496AB8B5-396A-40E4-AF41-32F4C48AC90D}";
        parent::__construct($config,$debug);        
        }

    /* HardwareEchoControl::getDeviceParameter
     * die Device Liste aus der Geräteliste erstellen, übergeben wird $name, $type, $entry["CONFIG"]
     * Antwort ist ein Geräteeintrag
     */

    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        /* Fehlerpüfung bereits in getDeviceCheck, setzt bereits $deviceList[$name]
        if (isset($deviceList[$name])) 
            {
            echo "          >>getDeviceParameter:EchoControl   Fehler, Name \"".$nameSelect[0]."\" bereits definiert.\n";
            return(false);
            }           */ 
        /* Durchführung */
        if ($debug) 
            {   
            echo "           HardwareEchoControl::getDeviceParameter  Parameter $name $type\n";          
            //print_r($entry);
            }
        $configStruct=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
        $device=false; $tuneIn=false;         // anhand der Konfig rausfinden ob es ein physikalisches gerät ist 
        $nofire=(strpos(IPS_GetName($entry["OID"]),"Fire") === false);
        foreach ($configStruct as $typ=>$conf)
            {
            $confStruct=json_decode($conf);
            switch ($typ)
                {
                case "Devicenumber": 
                    $len = strlen($conf);
                    if ($len < 17) $device=true;
                    //if ($debug) echo "      ->  ".$typ."    ".$conf."    $len\n";
                    break;
                case "Devicetype":
                    $len = strlen($conf);                    
                    //if ($debug) echo "      ->  ".$typ."    ".$conf."   $len\n";
                    break;
                case "TuneInStations":
                    //if ($debug) echo "      ->  ".$typ."     \n";                    
                    $tuneIn=true;
                    break;
                case "ExtendedInfo":
                case "Mute":
                case "Title":
                case "Cover":
                case "TitleColor":                    
                case "TitleSize":                    
                case "Subtitle1":
                case "Subtitle1Color":
                case "Subtitle1Size":
                case "Subtitle2":
                case "Subtitle2Color":
                case "Subtitle2Size":
                    break;
                case "AlarmInfo":
                case "TaskList":
                case "ShoppingList":
                    break; 
                case "updateinterval":
                    if ($conf==0) 
                        {
                        //if ($debug) echo "      ->  ".$typ."    ".$conf."     change Configuration !\n";                   
                        }
                    else
                        {
                        //if ($debug)  echo "      ->  ".$typ."    ".$conf."  \n";                   
                        }
                    break;                   
                default:
                    if ($debug>1) echo "     unknown config structure ->  ".$typ."    ".$conf."\n";                    
                    break;
                } 
            }
        if ($device && $tuneIn && $nofire) 
            {
            $entry["TYPEDEV"]="TYPE_LOUDSPEAKER";
            }
        $deviceList[$name]["Type"]=$type;
        if (isset($deviceList[$name]["Name"])) $entry["NAME"]=$deviceList[$name]["Name"]; 
        else $entry["NAME"]=$name;
        $deviceList[$name]["Instances"][]=$entry;
        return (true);
        }        

    }




?>