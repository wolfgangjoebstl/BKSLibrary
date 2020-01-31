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
 *      function deviceList()  mit Inpout von function hardwareList()
 *
 * Die alte Geräte anhängige Devicelist ist jetzt in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Include.inc.php
 *      mit functions pro gerätetyp
 *	
 * wenn DetectMovement und die TopologyMappingLibrary instaliert ist, wird eine Topologie aufgebaut.
 *
 */

$ExecuteExecute=false;          // false Execute routine gesperrt, es wird eh immer die Timer Routine aufgerufen. Ist das selbe !
$startexec=microtime(true);

/******************************************************
 *
 *				INIT
 *
 *************************************************************/
Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

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

/* DeviceManger muss immer installuert werden, wird in Timer als auch RunScript und Execute verwendet */

if (isset($installedModules["OperationCenter"])) 
    {
    IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter'); 
    echo "OperationCenter ist installiert:\n";
    $DeviceManager = new DeviceManagement();
    //echo "  Aktuelle Fehlermeldung der der Homematic CCUs ausgeben:\n";      
    echo $DeviceManager->HomematicFehlermeldungen()."\n";
    //echo "  Homematic Serialnummern erfassen:\n";
    $serials=$DeviceManager->addHomematicSerialList_Typ();      // kein Debug
    }

//print_r($installedModules); 

    echo "\n";
    echo "Kernel Version (Revision) ist : ".IPS_GetKernelVersion()." (".IPS_GetKernelRevision().")\n";
    echo "Kernel Datum ist : ".date("D d.m.Y H:i:s",IPS_GetKernelDate())."\n";
    echo "Kernel Startzeit ist : ".date("D d.m.Y H:i:s",IPS_GetKernelStartTime())."\n";
    echo "Kernel Dir seit IPS 5.3. getrennt abgelegt : ".IPS_GetKernelDir()."\n";
    echo "Kernel Install Dir ist auf : ".IPS_GetKernelDirEx()."\n";
    echo "\n";

    $ipsOps = new ipsOps();
	$modulhandling = new ModuleHandling();	                	// in AllgemeDefinitionen, alles rund um Bibliotheken, Module und Librariestrue bedeutet mit Debug
    $topologyLibrary = new TopologyLibraryManagement();                   // in EvaluateHardware Library, neue Form des Topology Managements
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
//else

/******************************************************
 *
 *				TIMER Routine
 *
 * keine else mehr, immer ausführen, das heisst jeden Tag ein neues Inventory erstellen
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
     *
     */
    $input["ModuleID"] = "{44CAAF86-E8E0-F417-825D-6BFFF044CBF5}";        // add EchoControl
    $input["ModuleName"] = "AmazonEchoConfigurator";
    $discovery[]=$input;

    echo "Fehlermeldungen beim Erstellen der decivelist in scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Devicelist.inc.php \n";
    $socket = $topologyLibrary->get_SocketList($discovery);
    $gateway = $topologyLibrary->get_GatewayList($discovery);
    $hardware = $topologyLibrary->get_HardwareList($discovery);
    $deviceList = $topologyLibrary->get_DeviceList($hardware);
    echo "\n";

    $includefileDevices     = '<?'."\n";             // für die php Devices and Gateways, neu
    $includefileDevices .= "\n\n";
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

            $DetectDeviceHandler = new DetectDeviceHandler();                       // aleter Handler für channels, das Event hängt am Datenobjekt
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

        if (false)
            {
            echo "\n";
            $onlyOne=true;
            $parent = $topID;
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
                                $topologyLibrary->createTopologyInstance($placeInstances, $placeInstances, $entry, "{4D96B245-6B06-EC46-587F-25E8A323A206}");     // Palces können nur in Places eingeordnet werden
                                break;
                            case "Room":
                                //print_r($entry);
                                $topologyLibrary->createTopologyInstance($roomInstances, $placeInstances, $entry, "{F8CBACC3-6D51-9C88-58FF-3D7EBDF213B5}");      // Rooms können nur in Places vorkommen
                                break;
                            case "Device":
                                /* Devices sind üblicherweise nicht in der Topologyliste. Bei Sonderwünschen halt auch dort eintragen */
                                $topologyLibrary->createTopologyInstance($deviceInstances, $roomInstances, $entry, "{5F6703F2-C638-B4FA-8986-C664F7F6319D}");      // Devices in Rooms vorkommen
                                $topologyLibrary->createTopologyInstance($deviceInstances, $devicegroupInstances, $entry, "{5F6703F2-C638-B4FA-8986-C664F7F6319D}");      // Devices in Rooms vorkommen
                                break;
                            case "DeviceGroup":
                                $topologyLibrary->createTopologyInstance($devicegroupInstances, $roomInstances, $entry, "{CE5AD2B0-A555-3A22-5F41-63CFF00D595F}");      // DeviceGroups können nur in Rooms vorkommen
                                break;
                            default:
                                //$InstanzID = @IPS_GetInstanceIDByName($name, $parent);
                                break;
                            }
                        }
                    }
                else echo "$name without Type definition.\n";
                }

            $i=0;
            $onlyOne=true;
            $parent=$topID;
            foreach ($deviceList as $name => $entry)
                {
                $instances=$entry["Instances"];
                //if ($onlyOne)
                    {
                    if ( (isset($deviceInstances[$name])) === false )
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
                        $InstanzID = $deviceInstances[$name];    
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
                        if (isset($roomInstances[$room]))
                            {
                            //echo "Vergleiche ".IPS_GetParent($InstanzID)." mit ".$roomInstances[$room]."\n";
                            if ( IPS_GetParent($InstanzID) != $roomInstances[$room])
                                {
                                echo "    -> Instanz Room vorhanden. Parent auf $room setzen.\n";
                                IPS_SetParent($InstanzID,$roomInstances[$room]);
                                }
                            }
                        elseif (isset($devicegroupInstances[$room]))    
                            {
                            if ( IPS_GetParent($InstanzID) != $devicegroupInstances[$room])
                                {
                                echo "    -> Instanz DeviceGroup vorhanden. Parent $room setzen.\n";
                                IPS_SetParent($InstanzID,$devicegroupInstances[$room]);
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
                        if (isset($installedModules["DetectMovement"]))  $DetectDeviceListHandler->RegisterEvent($InstanzID,'Topology',$room,'');	                    /* für Topology registrieren, ich brauch eine OID damit die Liste erzeugt werden kann */
                        }
                    //$onlyOne=false;
                    $i++;    
                    }
                }      // end foreach

            }   // ende if false

            }           // end isset DetectMovement
        }               // end TopologyMappingLibrary

	/************************************
	 *
	 *  Homematic Sender und vorher HomematicSockets, FHT, FS20EX, FS20 einlesen
	 *
	 ******************************************/

	//$includefile='<?'."\n".'$fileList = array('."\n";
	$includefile            = '<?'."\n";             // für die php IP Symcon Runtime

    $summary = array();
    $evaluateHardware->getHomeMaticSockets($includefile);            //Wenn vorhanden die Homematic Sockets auflisten
    $evaluateHardware->getFHTDevices($includefile,$summary);
    $evaluateHardware->getFS20EXDevices($includefile,$summary);
    $evaluateHardware->getFS20Devices($includefile,$summary);
    $evaluateHardware-> getHomematicInstances($includefile,$summary);

if (false) {
	$guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	$includehomematic=	'function getHomematicConfiguration() {'."\n".'            return array('." \n";
	$includefile.='function HomematicList() { return array('."\n";

	echo "\nHomematic Instanzen von Geräten: ".sizeof($alleInstanzen)."\n\n";
	$serienNummer=array(); $i=0;
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
		if (isset($installedModules["DetectMovement"])) $DetectDeviceHandler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren, RSSI Register mit registrieren für spätere geografische Auswertungen */
        //echo "check.\n";
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
				$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
				$includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
				$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
				$includefile.="\n         ".'"CCU" => "'.$HM_CCU_Name.'", ';
				$includefile.="\n         ".'"Protocol" => "'.$protocol.'", ';
				$includefile.="\n         ".'"EmulateStatus" => "'.IPS_GetProperty($instanz,'EmulateStatus').'", ';
                
                //echo "Typen und Geräteerkennung durchführen.\n";
                if (isset($installedModules["OperationCenter"])) 
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
					$includehomematic.='             '.str_pad(('"'.IPS_GetName($instanz).'"'),40).' => array("'.$result[0].'",'.$result[1].',HM_PROTOCOL_BIDCOSRF,'.$type.'),'."\n";
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
						$serienNummer[$HM_CCU_Name][$result[0]]["Values"].=$o['ObjectIdent']." ";
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
	/*$includefile.=');'."\n".'?>';*/
	$includefile.=');}'."\n";

} // ende false

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
		$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Movement');		
		}
    print_r($groups); 
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectMovementHandler->InitGroup($group);
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
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
        $mirror = IPS_GetName($moid);    
        $werte = @AC_GetLoggedValues($archiveHandlerID,$moid, time()-60*24*60*60, time(),1000);
        if ($werte === false) echo "Kein Logging für Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).")\n";
		echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75).
               json_encode($eventDeviceConfig[$oid])."  ".json_encode($eventTempConfig[$oid])." Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).") Archive Groesse : ".count($werte)."\n";
        /* check and get mirror register,. It is taken from config file. If config file is empty it is calculated from parent or other inputs and stored afterwards 
         * Config function DetectDevice follows detecttemperaturehandler
         */            
    	$DetectTemperatureHandler->RegisterEvent($oid,"Temperatur",'','Mirror->'.$mirror);     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben , Mirror Register als Teil der Konfig*/
		$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Temperature',true);	        // par 3 config overwrite
		$DetectDeviceHandler->RegisterEvent($oid,'Topology','','Temperature,Mirror->'.$mirror,true);	        	/* par 3 config overwrite, Mirror Register als Zusatzinformation, nicht relevant */
		}
	print_r($groups);
    //echo "Alle Gruppen durchgehen:\n";
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectTemperatureHandler->InitGroup($group);
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
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
		$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Humidity');		
		}
    print_r($groups);         
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectHumidityHandler->InitGroup($group);
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
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
		$DetectDeviceHandler->RegisterEvent($moid,'Topology','','HeatControl');		
		}
    print_r($groups);    
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectHeatControlHandler->InitGroup($group);
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$DetectDeviceHandler->RegisterEvent($soid,'Topology','','HeatControl');		
		}	
																																													
    echo "\n";
    echo "=======================================================================\n";
	echo "Jetzt noch einmal den ganzen DetectDevice Event table sortieren, damit Raumeintraege schneller gehen :\n";


    $configuration=$DetectDeviceHandler->Get_EventConfigurationAuto();
    $configurationNew=$DetectDeviceHandler->sortEventList($configuration);
    $DetectDeviceHandler->StoreEventConfiguration($configurationNew);
    } /* ende if isset DetectMovement */

echo "\n";
echo "\n";
echo "\n";
echo "Gesamtlaufzeit ".(time()-$startexec)." Sekunden.\n";





?>