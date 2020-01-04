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
    $Handler = new DetectDeviceHandler();
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
    echo "Kernel Dir seit IPS 5.3. getrennt abgelegt : ".IPS_GetKernelDir()."\n";
    echo "\n";

    $ipsOps = new ipsOps();
	$modulhandling = new ModuleHandling();		// true bedeutet mit Debug

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
 *      include File für die PhP Runtime in IP-Symcon
 *      include File für das user/webfront, Darstellung als Topologie
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
	
	//$includefile='<?'."\n".'$fileList = array('."\n";

	$includefile    = '<?'."\n";             // für die php IP Symcon Runtime
	$includefileHTML= '<?'."\n";             // für die php user/webfront Runtime    

	/************************************
	 *
	 *  Wenn vorhanden Hardware Sockets auflisten, dann kommen die Geräte dran
     *  damit kann die Konfiguration des entsprechenden Gateways wieder hergestellt werden
	 *
	 ******************************************/

    echo "\nAlle installierten Discovery Instances mit zugehörigem Modul und Library:\n";
    $discovery = $modulhandling->getDiscovery();
    $hardware=array(); $gateway=array();
    $device=array();
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
            $deviceID = $object->getDeviceID();
            //echo "        BridgeID    $bridgeID ".IPS_GetModule($bridgeID)["ModuleName"]."\n";
            //echo "        DeviceID    $deviceID ".IPS_GetModule($deviceID)["ModuleName"]."\n";
            $bridges=$modulhandling->getInstances($bridgeID);
            foreach ($bridges as $bridge)
                {
                //echo "           ".IPS_GetName($bridge)."\n";
                $gateway[$hardwareType][IPS_GetName($bridge)]["OID"]=$bridge;
                $gateway[$hardwareType][IPS_GetName($bridge)]["CONFIG"]=$configHue=IPS_GetConfiguration($bridge);
                }
            $devices=$modulhandling->getInstances($deviceID);
            foreach ($devices as $device)
                {
                //echo "           ".IPS_GetName($device)."\n";
                $hardware[$hardwareType][IPS_GetName($device)]["OID"]=$device;
                $hardware[$hardwareType][IPS_GetName($device)]["CONFIG"]=$configHue=IPS_GetConfiguration($device);
                }
            }
        }

    echo "\n";
    $includefile .= "\n\n";
    $includefile .= "function gatewayInstanzen() { return ";
    $ipsOps->serializeArrayAsPhp($gateway, $includefile);
    $includefile .= ';}'."\n\n"; 

    $deviceList=array();
    foreach ($hardware as $hardwareType => $deviceEntries)          // die device types durchgehen HUE, Homematic etc.
        {
        foreach ($deviceEntries as $name => $entry)         // die devices durchgehen, Homematic Devices müssen gruppiert werden 
            {
            $objectClassName = "Hardware".$hardwareType;
            $object = new $objectClassName(); 
            $object->getDeviceParameter($deviceList, $name, $hardwareType, $entry);     // Ergebnis von erkannten (Sub) Instanzen wird in die deviceList integriert, eine oder mehrer Instanzen einem Gerät zuordnen
            $object->getDeviceChannels($deviceList, $name, $hardwareType, $entry);     // Ergebnis von erkannten Channels wird in die deviceList integriert, jede Instanz wird zu einem oder mehreren channels eines Gerätes
            $object->getDeviceActuators($deviceList, $name, $hardwareType, $entry);     // Ergebnis von erkannten Actuators wird in die deviceList integriert, Acftuatoren sind Instanzen die wie in IPSHEAT bezeichnet sind
            }
        }
    ksort($deviceList);
    //print_r($deviceList);
    echo "\n";
    echo "Bereits konfigurierte Actuators aus IPSHeat dazugeben, Ergebnis der Funktion: \n";
    $actuators=$hardwareTypeDetect->getDeviceActuatorsFromIpsHeat($deviceList);
    print_r($actuators);

    $includefile .= 'function deviceList() { return ';
    $ipsOps->serializeArrayAsPhp($deviceList, $includefile, 0, 0, false);          // true mit Debug
    $includefile .= ';}'."\n\n";        
       

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

	/************************************
	 *
	 *  FHT Sender
	 *
	 ******************************************/
	 
	$guid = "{A89F8DFA-A439-4BF1-B7CB-43D047208DDD}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	$includefile.='function FHTList() { return array('."\n";

	echo "\nFHT Geräte Instanzen gefunden: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		if (isset($installedModules["DetectMovement"])) $Handler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren */            
            
		//echo IPS_GetName($instanz)." ".$instanz." \n";
		$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
		$includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
		$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';

        if (isset($installedModules["OperationCenter"])) $typedev=$DeviceManager->getFS20DeviceType($instanz);  /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
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

	/************************************
	 *
	 *  FS20EX Sender
	 *
	 ******************************************/

	$guid = "{56800073-A809-4513-9618-1C593EE1240C}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	$includefile.='function FS20EXList() { return array('."\n";
	
	echo "\nFS20EX Geräte: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'HomeCode')." ".IPS_GetProperty($instanz,'DeviceList')."\n";
		if (isset($installedModules["DetectMovement"])) $Handler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren */            
            
		//$FS20EXconfig=IPS_GetConfiguration($instanz);
		//print_r($FS20EXconfig);

		$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
		$includefile.="\n         ".'"HomeCode" => \''.IPS_GetProperty($instanz,'HomeCode').'\', ';
		$includefile.="\n         ".'"DeviceList" => \''.IPS_GetProperty($instanz,'DeviceList').'\', ';
		$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
		$includefile.="\n         ".'"CONFIG" => \''.IPS_GetConfiguration($instanz).'\', ';		

        if (isset($installedModules["OperationCenter"])) $typedev=$DeviceManager->getFS20DeviceType($instanz);  /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
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

	/************************************
	 *
	 *  FS20 Sender
	 *
	 ******************************************/

	$guid = "{48FCFDC1-11A5-4309-BB0B-A0DB8042A969}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	$includefile.='function FS20List() { return array('."\n";

	echo "\nFS20 Geräte: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),45)." ".$instanz." ".IPS_GetProperty($instanz,'HomeCode')." ".IPS_GetProperty($instanz,'Address').IPS_GetProperty($instanz,'SubAddress')." ".IPS_GetProperty($instanz,'EnableTimer')." ".IPS_GetProperty($instanz,'EnableReceive').IPS_GetProperty($instanz,'Mapping')."\n";
		if (isset($installedModules["DetectMovement"])) $Handler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren */            
            
		//echo IPS_GetName($instanz)." ".$instanz." \n";
		$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
		$includefile.="\n         ".'"HomeCode" => "'.IPS_GetProperty($instanz,'HomeCode').'", ';
		$includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
		$includefile.="\n         ".'"SubAdresse" => "'.IPS_GetProperty($instanz,'SubAddress').'", ';
		$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
		$includefile.="\n         ".'"CONFIG" => \''.IPS_GetConfiguration($instanz).'\', ';		

        if (isset($installedModules["OperationCenter"])) $typedev=$DeviceManager->getFS20DeviceType($instanz);  /* wird für CustomComponents verwendet, gibt als echo auch den Typ aus */
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

	/************************************
	 *
	 *  Homematic Sender
	 *
	 ******************************************/

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
		if (isset($installedModules["DetectMovement"])) $Handler->RegisterEvent($instanz,'Topology','','');	                    /* für Topology registrieren, RSSI Register mit registrieren für spätere geografische Auswertungen */
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
	$includehomematic.=');}'."\n";
	$includefile.=$includehomematic;
	$includefile.="\n".'?>';	
	$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\EvaluateHardware\EvaluateHardware_Include.inc.php';
	if (!file_put_contents($filename, $includefile)) {
        throw new Exception('Create File '.$filename.' failed!');
    		}
	//include $filename;
	//print_r($fileList);
	
	echo "\n";
	echo $includehomematic;
	
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
		$Handler->RegisterEvent($moid,'Topology','','Movement');		
		}
    print_r($groups); 
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectMovementHandler->InitGroup($group);
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$Handler->RegisterEvent($soid,'Topology','','Movement');		
		}	
	
    echo "\n";
	echo "Temperaturregister hereinholen:\n";								
	$DetectTemperatureHandler = new DetectTemperatureHandler();
	$groups=$DetectTemperatureHandler->ListGroups("Temperature");        /* Type angeben damit mehrere Gruppen aufgelöst werden können */
	$events=$DetectTemperatureHandler->ListEvents();
	foreach ($events as $oid => $typ)
		{
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$moid=$DetectTemperatureHandler->getMirrorRegister($oid);
		$Handler->RegisterEvent($moid,'Topology','','Temperature');		
		}
	print_r($groups);
    //echo "Alle Gruppen durchgehen:\n";
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectTemperatureHandler->InitGroup($group);
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$Handler->RegisterEvent($soid,'Topology','','Temperature');		
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
		$Handler->RegisterEvent($moid,'Topology','','Humidity');		
		}
    print_r($groups);         
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectHumidityHandler->InitGroup($group);
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$Handler->RegisterEvent($soid,'Topology','','Humidity');		
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
		$Handler->RegisterEvent($moid,'Topology','','HeatControl');		
		}
    print_r($groups);    
	foreach ($groups as $group => $entry)
		{
		$soid=$DetectHeatControlHandler->InitGroup($group);
		echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
		$Handler->RegisterEvent($soid,'Topology','','HeatControl');		
		}	




if (false)
    {
	/*--------------------------nur zur Ausgabe und Kontrolle---------------------------------------*/
    echo "\n";
    echo "=======================================================================\n";
	echo "Jetzt in den einzelnen Katgorien die Links hineinsortieren :\n";
    echo "\n";
    echo "Noch einmal Ausgabe der nun erfolgreich registrierten Topologie Eintraege:\n";
	$configurationAuto = $Handler->Get_EventConfigurationAuto();
	//$result=$Handler->sortEventList($configurationAuto);
	foreach ($configurationAuto as $oid => $entry) 
		{ 
		echo "     ".$oid."    ".str_pad(IPS_GetName($oid),40)."   ".str_pad($entry[0],20)."  ".str_pad($entry[1],30)."   ".$entry[2]."  \n"; 
		}

	if ( function_exists("IPSDetectDeviceHandler_GetEventConfiguration") == true )
		{
	    $topology=$Handler->Get_Topology();
    	print_r($topology);
        $topologyPlusLinks=$topology;
		foreach (IPSDetectDeviceHandler_GetEventConfiguration() as $index => $entry)
			{
			$name=IPS_GetName($index);
			$entry1=explode(",",$entry[1]);		/* Zuordnung Gruppen */
			$entry2=explode(",",$entry[2]);		/* Zuordnung Gewerke, eigentlich sollte pro Objekt nur jeweils ein Gewerk definiert sein. Dieses vorrangig anordnen */
			if (sizeof($entry1)>0)
				{
				foreach ($entry1 as $place)
					{
					if ( isset($topology[$place]["OID"]) != true ) 
						{
						echo "Kategorie $place anlegen.\n";
						}
					else
						{
						$oid=$topology[$place]["OID"];
						//print_r($topology[$place]);
						$size=sizeof($entry2);
						if ($entry2[0]=="") $size=0;
						if ($size > 0) 
							{	/* ein Gewerk, vorne einsortieren */
							echo "erzeuge Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid).") ".$entry[2]."\n";
							CreateLinkByDestination($name, $index, $oid, 10);	
                            $topologyPlusLinks[$place]["OBJECT"][$index]=$name;
							}
						else
							{	/* eine Instanz, dient nur der Vollstaendigkeit */
							echo "erzeuge Instanz Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid)."), wird nachrangig einsortiert.".$entry[2]."\n";						
							CreateLinkByDestination($name, $index, $oid, 1000);						
                            $topologyPlusLinks[$place]["INSTANCE"][$index]=$name;
							}
						}
					}
				//print_r($entry1);
				}
			}
		}
    else "FEHLER, function IPSDetectDeviceHandler_GetEventConfiguration noch nicht angelegt.\n";    

	print_r($topologyPlusLinks);
    /*-----------------------------------------------------------------*/
    }
																																													
    echo "\n";
    echo "=======================================================================\n";
	echo "Jetzt noch einmal den ganzen DetectDevice Event table sortieren, damit Raumeintraege schneller gehen :\n";


    $configuration=$Handler->Get_EventConfigurationAuto();
    $configurationNew=$Handler->sortEventList($configuration);
    $Handler->StoreEventConfiguration($configurationNew);
    } /* ende if isset DetectMovement */

echo "\n";
echo "\n";
echo "\n";
echo "Gesamtlaufzeit ".(time()-$startexec)." Sekunden.\n";

/********************************************************************************************************************/

/*    FUNKTIONEN       */

/********************************************************************************************************************/
/********************************************************************************************************************/
/********************************************************************************************************************/
/********************************************************************************************************************/



?>