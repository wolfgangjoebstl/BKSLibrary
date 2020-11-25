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

/* Hardware Library
 *
 * overview of classes
 *
 * Hardware
 *      HardwareDenonAVR extends Hardware
 *      HardwareNetatmoWeather extends Hardware
 *      HardwareHomematic
 *      HardwareHUE
 *      HardwareHarmony
 *      HardwareFHTFamily
 *      HardwareFS20Family
 *      HardwareFS20ExFamily
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
 *
 */

class Hardware
    {
	
    protected $socketID, $bridgeID, $deviceID;           // eingeschränkte Sichtbarkeit. Private nicht möglich, da auf selbe Klasse beschränkt
    protected $installedModules;
    protected $modulhandling;         // andere classes die genutzt werden, einmal instanzieren

    public function __construct()
        {
        //echo "parent class Hardware construct.\n";
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
            case "{3718244C-71A2-B20D-F754-DF5C79340AB4}":      // Homematic Discovery
            case "{5214C3C6-91BC-4FE1-A2D9-A3920261DA74}":      // HomeMatic Configurator
                $hardwareType="Homematic";
                break;
            case "{E4B2E379-63A8-4B79-3067-AF906DA91C33}":      // HUE Discovery
            case "{EE92367A-BB8B-494F-A4D2-FAD77290CCF4}":      // HUE Configurator
                $hardwareType="HUE";                
                break;
            case "{22F51957-348D-9A73-E019-3811573E7CA2}":      // Harmony Discovery
            case "{E1FB3491-F78D-457A-89EC-18C832F4E6D9}":      // Harmony Configurator
                $hardwareType="Harmony";  
                break;
            case "{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}":       // EchoControl Configurator, kein automatisches Discovery über System, chekt regelmaessig
                $hardwareType="EchoControl";  
                break;
            case "{4A76B170-60F9-C387-2303-3E3587282296}":      // Denon Discovery
                $hardwareType="DenonAVR";  
                break;
            case "{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}":          // Netatmo Configurator
                $hardwareType="NetatmoWeather";  
                break;
            case "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}":          // FHT Instanzen
                $hardwareType="FHTFamily";  
                break;
            case "{56800073-A809-4513-9618-1C593EE1240C}":            // FS20EX Instanzen
                $hardwareType="FS20EXFamily";  
                break;
            case "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}":            // FS20 Instanzen
                $hardwareType="FS20Family";  
                break;
            case "{D26101C0-BE49-7655-87D3-D721064D4E40}":            // OpCentCam Instanzen, ModuleID erfunden
                $hardwareType="OpCentCam";  
                break;
            default:
                echo "getHardwareType, hardwareType unknown for ModuleID $moduleID.";
                $hardwareType=false;
                break;
            }            
        return ($hardwareType);
        }

    /* Allgemein, die Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist. Ein false führt dazu dass kein Eintrag erstellt wird.
     */

    public function getDeviceCheck(&$deviceList, $name, $type, $entry, $debug=false)
        {
        /* Fehlerprüfung */
        if (isset($deviceList[$name])) 
            {
            echo "          >>getDeviceParameter:Allgemein   Fehler, Name \"$name\" bereits definiert.\n";
            print_r($deviceList[$name]);
            //echo "          >>getDeviceParameter:Allgemein   Fehler, Name \"".$deviceList[$name]."\" bereits definiert.\n";
            return(false);
            }            
        else return (true);
        }


    /* die Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist
     * Entry wird direkt in die Devicelist unter Instances integriert, Name ist der Key des Eintrags mit dem integriert wird, Subkategorie ist eben Instances, Entry ist der Wert der eingesetzt wird 
     * Das Gerät Name wird um den Typ Type erweitert, beim Eintrag entry wird der Name zusätzlich gespeichert
     */

    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo"          getDeviceParameters:Allgemein aufgerufen. Eintrag \"$name\" hinterlegt.\n";            
        $deviceList[$name]["Type"]=$type;
        $entry["NAME"]=$name; 
        $deviceList[$name]["Instances"][]=$entry;
        return (true);
        }

    /* die Device Liste (Geräteliste) um die Channels erweitern, ein Gerät kann mehrere Instances und Channels haben, 
     * es können mehr channels als instances sein, es können aber auch gar keine channels sein - eher unüblich
     *
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo "          getDeviceChannels:Allgemein aufgerufen für ".$entry["OID"]." mit $name $type. Keine Funktion hinterlegt.\n";
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
            $deviceList[$name]["Channels"][$port]["Name"]=$name;
            }
        return (true);
        }

    /* die Device Liste (Geräteliste) um die Actuators erweitern, ein Gerät kann Actuators haben, muss aber nicht
     *
     */

    public function getDeviceActuators(&$deviceList, $name, $type, $entry, $debug=false)
        {
        if ($debug) echo"          getDeviceActuators:Allgemein aufgerufen. Keine Funktion hinterlegt.\n";
        return (true);
        }


    public function getDeviceActuatorsFromIpsHeat(&$deviceList)
        {
        /* IPS Heat analysieren */
        $actuators = array();
        if ( isset($this->installedModules["Stromheizung"]) )
            {
            echo "\nStromheizung ist installiert. Configuration auslesen. Devicelist mit Aktuatoren anreichern:\n";
            IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");		
            IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");		
            IPSUtils_Include ("IPSHeat_Constants.inc.php",      "IPSLibrary::app::modules::Stromheizung");		
            IPSUtils_Include ("Stromheizung_Configuration.inc.php",  "IPSLibrary::config::modules::Stromheizung");
            $IPSLightObjects=IPSHeat_GetHeatConfiguration();
            foreach ($IPSLightObjects as $name => $object)
                {
                $components=explode(",",$object[IPSHEAT_COMPONENT]);
                echo "  ".str_pad($name,25).str_pad($object[IPSHEAT_TYPE],15).str_pad($components[0],35);           // strukturiert ausgeben und wenn Component bekannt ist erweitern
                switch (strtoupper($components[0]))
                    {
                    case "IPSCOMPONENTSWITCH_HOMEMATIC":
                    case "IPSCOMPONENTDIMMER_HOMEMATIC":                
                    case "IPSCOMPONENTRGB_PHUE":
                    case "IPSCOMPONENTRGB_LW12":
                    case "IPSCOMPONENTHEATSET_HOMEMATIC":
                    case "IPSCOMPONENTHEATSET_HOMEMATICIP":                
                    //case "IPSCOMPONENTHEATSET_FS20":                              // remote Adresse, OID nicht vorhanden
                        if (@IPS_ObjectExists($components[1])) echo $components[1]."   ".IPS_GetName($components[1]);
                        else echo $components[1]."   Error, Object does not exist -------------------------\n";                    
                        //echo $components[1]."   ".IPS_GetName($components[1]);
                        $actuators[$components[1]]["ComponentName"]=$components[0];
                        $actuators[$components[1]]["Type"]=$object[IPSHEAT_TYPE];
                        break;
                    default:
                        echo "  unbekannter Component -------------------";
                        break;
                    }
                echo "\n";	
                }
            }
        //print_r($actuators); 
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
        foreach ($deviceList as $name => $entry)
            {
            if (isset($entry["Channels"]))
                {
                /* Ein Channel hat mehrere Subchannels */
                foreach ($entry["Channels"] as $channel)
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
            echo "   $name    ".$device["Count"]." \n";
            //print_R($device);
            foreach ($device as $type => $register)
                {
                switch ($type)
                    {
                    case "Count":
                        break;
                    default:    
                        echo "      $type  ".$register["Count"]."   \n";
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
	
	public function __construct($debug=false)
		{
        $this->socketID = "{9AE3087F-DC25-4ADB-AB46-AD7455E71032}";           // I/O oder Splitter ist der Socket für das Device
        $this->bridgeID = "{4A76B170-60F9-C387-2303-3E3587282296}";           // Configurator
        $this->deviceID = "{DC733830-533B-43CD-98F5-23FC2E61287F}";           // das Gerät selbst
        parent::__construct($debug);        
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
	
	public function __construct($debug=false)
		{
        $this->socketID = "{26A55798-5CBC-88F6-5C7B-370B043B24F9}";           // I/O oder Splitter ist der Socket für das Device
        $this->bridgeID = "{DCA5D76C-A6F8-4762-A6C3-2FF6601DDEC8}";           // Configurator
        $this->deviceID = "{1023DB4A-D491-A0D5-17CD-380D3578D0FA}";           // das Gerät selbst
        parent::__construct($debug);        
        }

    /* NetatmoWeather,der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
     * nachdem bereits eine oder mehrere Instanzen einem Gerät zugeordnet wurden muss nicht mehr kontrolliert werden ob es das Gerät schon gibt
     * es werden nicht nur die Channels identifiziert sondern auch die Registers mit dem typedev
     *          $typedev=$this->getNetatmoDeviceType($oid,4,true);
     *
     */

    public function getDeviceChannels(&$deviceList, $name, $type, $entry, $debug=false)
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
            //echo "Werte für $oid aus getNetatmoDeviceType:\n"; print_r($result);
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
                $deviceList[$name]["Channels"][$port]["Name"]=$name;                
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

        /*--Raumklimasensor-----------------------------------*/
        if ( array_search("CO2",$registerNew) !== false)            /* Sensor Raumklima */
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
 * Hier gibt es Hardware spezifische Routinen die die class hardware erweitern.
 * Homematic hat ein eigenes Naming scheme mit : da ein Gerät mehrere Instanzen haben kann. name Gerät:Instanz
 *
 *
 *      __construct
 *      getDeviceCheck
 *      getDeviceParameter
 *      getDeviceChannels
 *      checkConfig
 *
 *
 *
 */

class HardwareHomematic extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
    protected $installedModules;

    private $DeviceManager;                 /* nur ein Objekt in der class */
	
	public function __construct($debug=false)
		{
        $this->socketID = "{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}";
        $this->bridgeID = "{5214C3C6-91BC-4FE1-A2D9-A3920261DA74}";
        $this->deviceID = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
        $this->setInstalledModules();
        if (isset($this->installedModules["OperationCenter"])) 
            {
            IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');   
            $this->DeviceManager = new DeviceManagement(); 
            }
        parent::__construct($debug);
        }

    /* die Device Liste aus der Geräteliste erstellen 
     * Antwort ist ein Geräteeintrag
     */

    /* Homematic, die Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist. Ein false führt dazu dass kein Eintrag erstellt wird.
     */

    public function getDeviceCheck(&$deviceList, $name, $type, $entry, $debug=false)
        {
        /* Fehlerprüfung, Name bereits in der Devicelist und wenn dann mit Doppelpunkt. Theoretisch werden Namen ohne : erlaubt wenn keine weitere Instanz vorhanden ist.*/
        $nameSelect=explode(":",$name);
        if (isset($deviceList[$nameSelect[0]])) 
            {
            if (count($nameSelect)<2) 
                {
                echo "        >>HardwareHomematic::getDeviceCheck Fehler, Name \"".$nameSelect[0]."\" bereits definiert und Homematic Gerät Name falsch, ist ohne Doppelpunkt: $name \n";
                return (false);
                }
            else 
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
                    if ($debug) echo "          >>Port 0 von \"".$nameSelect[0]."\" wird ignoriert : ".$entry["CONFIG"].".\n";
                    return (false);
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
            echo "       >>HardwareHomematic::getDeviceCheckc Fehler, keine Seriennummer.\n";
            return (false);
            }

        /* erweiterte Fehlerprüfung */

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

        return (true);
        }

    /* die Homematic Device Liste (Geräteliste) um die Instances erweitern, ein Gerät kann mehrere Instances haben
     * Antwort ist true wenn alles in Ordnung verlaufen ist
     * Entry wird direkt in die Devicelist unter Instances integriert, Name ist der Key des Eintrags mit dem integriert wird, Subkategorie ist eben Instances, Entry ist der Wert der eingesetzt wird 
     * Das Gerät Name wird um den Typ Type erweitert, beim Eintrag entry wird der Name zusätzlich gespeichert
     */

    public function getDeviceParameter(&$deviceList,$name, $type, $entry, $debug=false)
        {
        /* Jeder Entry ist ein Device, oder ? */

        /* sehr schwierig, Devices sind nicht automatisch Instanzen */
        /* Zusammenfassen ausprobieren, erster Check alle Homematic Instanzen haben einen Doppelpunkt im Namen */

        $nameSelect=explode(":",$name);
        $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
        $addressSelect=explode(":",$result["Address"]);        
        $port=(integer)$addressSelect[1];

        /* Durchführung */            

        if ($debug) echo "           HardwareHomematic::getDeviceParameter Name \"".$nameSelect[0]."\" neuer Eintrag.".str_pad(" In deviceList unter ".$nameSelect[0]." Port $port.",50)."\n";
        if (isset($result["Protocol"])) 
            {
            switch ($result["Protocol"])
                {
                case 0:
                    $deviceList[$nameSelect[0]]["SubType"]="Funk";                            
                    break;
                case 1:
                    $deviceList[$nameSelect[0]]["SubType"]="Wired";                            
                    break;
                case 2:
                    $deviceList[$nameSelect[0]]["SubType"]="IP";                            
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
                if ($debug) echo " Infofeld aus HMInventory: ".$this->DeviceManager->getHomematicHMDevice($instanz,0)."  ".$this->DeviceManager->getHomematicHMDevice($instanz,1)."  $port und in der Matrix.";
                }
            else echo "   >>keine Ausgabe der Matrix. Gerät nicht hinterlegt.\n";


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
                echo "      >>getDeviceParameter: Name starts with HM, is probably new one, has not been renamed : \"".IPS_GetName($instanz)."\" ($instanz/".$result["Address"]."): ";
                $typedev    = $this->DeviceManager->getHomematicDeviceType($instanz,0,true);     /* noch einmal mit Debug wenn Name mit HM anfangt */
                }

            //$infodev    = $DeviceManager->getHomematicDeviceType($instanz,1);     /* wird für CustomComponents verwendet, gibt als echo auch den Typ der Instanz in beschreibender Form aus */
            $infodev    = $this->DeviceManager->getHomematicHMDevice($instanz,1);     /* Eindeutige Bezeichnung aufgrund des Homematic Gerätenamens */
            if ($infodev<>"")   
                {
                $deviceList[$nameSelect[0]]["Information"]=$infodev;
                if ($debug) echo "    INFO: $infodev";
                }
            else echo "\n       >>getDeviceParameter:Homematic Fehler : \"".IPS_GetName($instanz)."\" ($instanz/".$result["Address"]."): kein INFO ermittelt.\n";
            $deviceList[$nameSelect[0]]["Serialnummer"]=$addressSelect[0];
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
            $entry["NAME"]=$name; 
            $deviceList[$nameSelect[0]]["Instances"][$port]=$entry;             // port ist eine wichtige Information, info um welchen Switch, Taster etc. geht es hier.
            if ($debug) echo "\n";
            return (true);
            }
        else return (false);                // fehlender Typedev Wert erzeugt keinen Eintrag !!!
        }

    /* der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
     * nachdem bereits eine oder mehrere Instanzen einem Gerät zugeordnet wurden, muss nicht mehr kontrolliert werden ob es das Gerät schon gibt
     * Homematic Geräte haben immer mehrer Instances. Die Naming Convention ist Gerätename:Channelname
     * mit den Gerätenamen ist die deviceList indiziert. Der Key muss vorhanden sein.
     * nur wenn OperationCenter installiert ist werden Channels angelegt. Der geräte Port der den Key der Instanz definiert wird auch für den Channel verwendet.
     *
     * mit $DeviceManager->getHomematicDeviceType wird der Typedev bestimmt (Type, Name, RegisterAll, Register ... )
     * Typedev ist der fertige Channel Eintrag, es wird RegisterAll und Name besonders hervorgehoben
     *
     */

    public function getDeviceChannels(&$deviceList,$name, $type, $entry, $debug=false)
        {
        /* Jeder Entry ist ein Device, oder ? */

        /* sehr schwierig, Devices sind nicht automatisch Instanzen */
        /* Zusammenfassen ausprobieren, erster Check alle Homematic Instanzen haben einen Doppelpunkt im Namen */

        $goOn=true;

        /* Fehlerüberprüfung */
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
                        $goOn=false;
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
        $port= $this->checkConfig($goOn, $entry["CONFIG"]);     	// gibt Port zurück, wenn alles okay wird goOn nicht auf false gesetzt
        
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
                    if ($debug) 
                        {
                        if (isset($typedev["TYPE_MOTION"])) print_r($typedev);
                        }
                    if (isset($typedev["RegisterAll"])) 
                        {
                        $deviceList[$nameSelect[0]]["Channels"][$port]["RegisterAll"]=$typedev["RegisterAll"];
                        $deviceList[$nameSelect[0]]["Channels"][$port]=$typedev;
                        $deviceList[$nameSelect[0]]["Channels"][$port]["Name"]=$name;
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

    function checkConfig(&$goOn,$config)
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


class HardwareHUE extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($debug=false)
		{
        $this->socketID = "{6EFF1F3C-DF5F-43F7-DF44-F87EFF149566}";             // I/O oder Splitter ist der Socket für das Device
        $this->bridgeID = "{EE92367A-BB8B-494F-A4D2-FAD77290CCF4}";             // Configurator
        $this->deviceID = "{83354C26-2732-427C-A781-B3F5CDF758B1}";             // das Gerät selbst
        parent::__construct($debug);        
        }

    /* die Device Liste aus der Geräteliste erstellen 
     * Antwort ist ein Geräteeintrag
     */

    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        /* Fehlerpüfung */
        //$debug=true;
        if (isset($deviceList[$name])) 
            {
            echo "          >>getDeviceParameter:HUE   Fehler, Name \"".$nameSelect[0]."\" bereits definiert.\n";
            return(false);
            }            
        /* Durchführung */
        if ($debug) echo "          getDeviceParameters:HUE     aufgerufen. Eintrag \"$name\" hinterlegt.\n";            
        $result=json_decode($entry["CONFIG"],true);   // als array zurückgeben 
        //print_r($result);
        if ( (isset($result["DeviceType"])) &&($result["DeviceType"]=="lights") ) $entry["TYPEDEV"]="TYPE_SWITCH";
        $deviceList[$name]["Type"]=$type;
        $entry["NAME"]=$name; 
        $deviceList[$name]["Instances"][]=$entry;
        return (true);
        }

    }


class HardwareHarmony extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($debug=false)
		{
        $this->socketID = "{03B162DB-7A3A-41AE-A676-2444F16EBEDF}";
        $this->bridgeID = "{E1FB3491-F78D-457A-89EC-18C832F4E6D9}";
        $this->deviceID = "{B0B4D0C2-192E-4669-A624-5D5E72DBB555}";
        parent::__construct($debug);        
        }

    }


class HardwareFHTFamily extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($debug=false)
		{
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}";         // FHT Devices
        parent::__construct($debug);        
        }

    /* FHT, der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
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
                $deviceList[$name]["Channels"][$port]["Name"]=$name;                
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

/*********************************
 * 
 * FS20Family, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 *
 *
 *
 ****************************************/

class HardwareFS20Family extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($debug=false)
		{
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}";
        parent::__construct($debug);        
        }

    /* FS20, der Versuch die Informationen zu einem Gerät in drei Kategorien zu strukturieren. Instanzen (Parameter), Channels (Sensoren) und Actuators (Aktuatoren)
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
                $deviceList[$name]["Channels"][$port]["Name"]=$name;                
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


/*********************************
 * 
 * FS20ExFamily, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 *
 *
 *
 ****************************************/

class HardwareFS20ExFamily extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($debug=false)
		{
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{56800073-A809-4513-9618-1C593EE1240C}";
        parent::__construct($debug);        
        }

    }


/*********************************
 * 
 * OpCentCam, genaue Auswertung nur mehr an einer, dieser Stelle machen 
 *
 *
 *
 ****************************************/

class HardwareOpCentCam extends Hardware
	{
	
    protected $socketID, $bridgeID, $deviceID;
	
	public function __construct($debug=false)
		{
        echo "construct HardwareOpCentCam aufgerufen.\n";    
        $this->socketID = "";               // empty string means, there are no sockets
        $this->bridgeID = "";
        $this->deviceID = "{28E40EBC-F9E3-52B7-E1F9-8F845E79956C}";         // Wir brauchen zumindest eine DeviceID, auch wenn es eine erfundene ist
        parent::__construct($debug);        
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
	
	public function __construct($debug=false)
		{
        $this->socketID = "{C7F853A4-60D2-99CD-A198-2C9025E2E312}";            
        $this->bridgeID = "{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}";
        $this->deviceID = "{496AB8B5-396A-40E4-AF41-32F4C48AC90D}";
        parent::__construct($debug);        
        }

    /* die Device Liste aus der Geräteliste erstellen, übergeben wird $name, $type, $entry["CONFIG"]
     * Antwort ist ein Geräteeintrag
     */

    public function getDeviceParameter(&$deviceList, $name, $type, $entry, $debug=false)
        {
        /* Fehlerpüfung */
        if (isset($deviceList[$name])) 
            {
            echo "          >>getDeviceParameter:EchoControl   Fehler, Name \"".$nameSelect[0]."\" bereits definiert.\n";
            return(false);
            }            
        /* Durchführung */
        if ($debug) 
            {   
            echo "           getDeviceParameter HardwareEchoControl Parameter $name $type\n";          
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
                    if ($debug) echo "      ->  ".$typ."    ".$conf."\n";                    
                    break;
                } 
            }
        if ($device && $tuneIn && $nofire) 
            {
            $entry["TYPEDEV"]="TYPE_LOUDSPEAKER";
            }
        $deviceList[$name]["Type"]=$type;
        $entry["NAME"]=$name; 
        $deviceList[$name]["Instances"][]=$entry;
        return (true);
        }        

    }




?>