<?


/* Herausfinden welche Hardware verbaut ist und in IPSComponent und IPSHOmematic bekannt machen
	Define Files und Array function notwendig
	
*/

/******************************************************

				INIT

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

				EXECUTE

*************************************************************/

if ($_IPS['SENDER']=="Execute")
	{
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");	
	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
	print_r($installedModules);
	
	echo "\nVon der Konsole aus gestartet.\n";

	$guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
	//Auflisten
	$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
	echo "\nHomematic Geräte: ".sizeof($alleInstanzen)." (angeführt nach Ports, keine Zusammenfassung auf Geräte)\n\n";
	$serienNummer=array();
	foreach ($alleInstanzen as $instanz)
		{
		$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
		switch (IPS_GetProperty($instanz,'Protocol'))
			{
			case 0:
				$protocol="Funk";
				break;
			case 2:
				$protocol="IP";
				break;
			default:
				$protocol="Wired";
				break;
			}
		$HM_Adresse=IPS_GetProperty($instanz,'Address');
		$result=explode(":",$HM_Adresse);
		//print_r($result);
		echo str_pad(IPS_GetName($instanz),40)." ".$instanz." ".$HM_Adresse." ".str_pad($protocol,6)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name."\n";
		if (isset($serienNummer[$HM_CCU_Name][$result[0]]))
			{
			$serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]+=1;
			}
		else
			{
			$serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]=1;
			$serienNummer[$HM_CCU_Name][$result[0]]["Values"]="";
			}
		$serienNummer[$HM_CCU_Name][$result[0]]["Name"]=IPS_GetName($instanz);
		$serienNummer[$HM_CCU_Name][$result[0]]["OID:".$result[1]]=$instanz;
		$serienNummer[$HM_CCU_Name][$result[0]]["Name:".$result[1]]=IPS_GetName($instanz);		
		$cids = IPS_GetChildrenIDs($instanz);
		foreach($cids as $cid)
			{
			$o = IPS_GetObject($cid);
			echo "   CID : ".$cid."  ".IPS_GetName($cid)."  ".date("d.m H:i",IPS_GetVariable($cid)["VariableChanged"])."\n";
			if($o['ObjectIdent'] != "")
				{
				$serienNummer[$HM_CCU_Name][$result[0]]["Values"].=$o['ObjectIdent']." ";
				}
	    	}
		}

	$texte = Array(
	    "CONFIG_PENDING" => "Konfigurationsdaten stehen zur Übertragung an",
    	"LOWBAT" => "Batterieladezustand gering",
	    "STICKY_UNREACH" => "Gerätekommunikation war gestört",
   	 	"UNREACH" => "Gerätekommunikation aktuell gestört"
	);

	$ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
	$HomInstanz=sizeof($ids);
	if($HomInstanz == 0)
		{
		echo "ERROR: Keine HomeMatic Socket Instanz gefunden!\n";
		}

	for ($i=0;$i < $HomInstanz; $i++)
		{
		$ccu_name=IPS_GetName($ids[$i]);
		echo "\nHomatic Socket ID ".$ids[$i]." / ".$ccu_name."   ".sizeof($serienNummer[$ccu_name])." Endgeräte angeschlossen.\n";
		$msgs = HM_ReadServiceMessages($ids[$i]);
		if($msgs === false)
			{
			echo "  ERROR: Verbindung zur CCU fehlgeschlagen!\n";
			}
		if(sizeof($msgs) == 0)
			{
			echo "  OK, keine Servicemeldungen!\n";
			}
		foreach($msgs as $msg)
			{
			if(array_key_exists($msg['Message'], $texte))
				{
  			  	$text = $texte[$msg['Message']];
   				}
			else
				{
  	  			$text = $msg['Message'];
  				}
			$id = GetInstanceIDFromHMID($msg['Address']);
		  	if(IPS_InstanceExists($id))
			 	{
				$name = IPS_GetLocation($id);
				}
			else
				{
				$name = "Gerät nicht in IP-Symcon eingerichtet";
	    		}
		  	echo "  NACHRICHT : ".$name."  ".$msg['Address']."   ".$text." \n";
			}
		}
	echo "\nInsgesamt gibt es ".sizeof($serienNummer)." Homematic CCUs.\n";
	print_r($serienNummer);
	foreach ($serienNummer as $ccu => $geraete)
 		{
		echo "-------------------------------------------\n";
	 	echo "  CCU mit Name :".$ccu."\n";
 		echo "    Es sind ".sizeof($geraete)." Geraete angeschlossen. (Zusammenfassung nach Geräte, Seriennummer)\n";
		foreach ($geraete as $name => $anzahl)
			{
			//echo "\n *** ".$name."  \n";
			//print_r($anzahl);
			$register=explode(" ",trim($anzahl["Values"]));
			sort($register);
			$registerNew=array();
			echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".$name." Inst: ".$anzahl["Anzahl"]." Child: ".sizeof($register)." ";
			if (sizeof($register)>1)
				{ /* es gibt Childrens zum analysieren, zuerst gleiche Werte unterdruecken */
				$oldvalue="";
				foreach ($register as $index => $value)
					{
					//echo "    ".$value."  ".$oldvalue."\n";
					if ($value!=$oldvalue) {$registerNew[]=$value;}
					$oldvalue=$value;
					}
				//print_r($registerNew);
				/* dann Children register sortieren, anhand der sortierten Reihenfolge der Register können die Geräte erkannt werden */
				sort($registerNew);
				switch ($registerNew[0])
					{
					case "ERROR":
						echo "Funk-Tür-/Fensterkontakt\n";
						break;
					case "INSTALL_TEST":
						if ($registerNew[1]=="PRESS_CONT")
							{
							echo "Taster 6fach\n";
							}
						else
							{
							echo "Funk-Display-Wandtaster\n";
							}
						break;
					case "ACTUAL_HUMIDITY":
						echo "Funk-Wandthermostat\n";
						break;
					case "ACTUAL_TEMPERATURE":
						echo "Funk-Heizkörperthermostat\n";
						break;
					case "BRIGHTNESS":
						echo "Funk-Bewegungsmelder\n";
						break;
					case "INHIBIT":
						echo "Funk-Schaltaktor 1-fach\n";
						break;
					case "DIRECTION":
						echo "Funk-Rolladenansteuerung\n";
						print_r($registerNew);	
						break;
					case "BOOT":
						echo "Funk-Schaltaktor 1-fach mit Energiemessung\n";
						break;
					case "HUMIDITY":
						echo "Funk-Thermometer\n";
						break;
					case "CONFIG_PENDING":		/* modernes Register, alles gleich am Anfang */
						switch ($registerNew[1])
							{
							case "DIRECTION":
								echo "Funkaktor Dimmer\n";
								break;
							case "DUTYCYCLE":
								echo "IP Funk-Schaltaktor\n";
								break;
							case "DUTY_CYCLE":
								echo "IP Funk-Stellmotor\n";
								break;								
							case "DEVICE_IN_BOOTLOADER":
							case "INSTALL_TEST":
								echo "Funk-Taster\n";
								break;
							case "CURRENT":
								echo "IP Funk-Schaltaktor Energiemessgeraet\n";
								break;
							default:	
								echo "unknown\n";
								print_r($registerNew);	
								break;
							}					
						break;					
					default:
						echo "unknown\n";
						print_r($registerNew);
						break;
					} /* ende switch */
				} /* ende size too small */
			else
				{	
				echo "not installed\n";
				}	
			}

		}

	/* IPS Light analysieren */
	if ( isset($installedModules["IPSLight"]) )
		{
		echo "IPSLight ist installiert. Configuration auslesen.\n";
		IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");		
		IPSUtils_Include ("IPSLight.inc.php",                "IPSLibrary::app::modules::IPSLight");		
		IPSUtils_Include ("IPSLight_Constants.inc.php",      "IPSLibrary::app::modules::IPSLight");		
		IPSUtils_Include ("IPSLight_Configuration.inc.php",  "IPSLibrary::config::modules::IPSLight");
		$IPSLightObjects=IPSLight_GetLightConfiguration();
		foreach ($IPSLightObjects as $name => $object)
			{
			$components=explode(",",$object[IPSLIGHT_COMPONENT]);
			echo "  ".$name."  ".$object[IPSLIGHT_TYPE]."   ".$components[0]."    ";
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
	echo "\n==================================================================\n";
	echo "es geht weiter mit der Timer Routine\n";
	} /* ende if execute */
//else

/******************************************************

				TIMER

*************************************************************/

	{

	echo "\n";
	echo "==================================================\n";
	echo "Vom Timer gestartet.\n";
	
	//$includefile='<?'."\n".'$fileList = array('."\n";
	$includefile='<?'."\n";
	$alleInstanzen = IPS_GetInstanceListByModuleType(3); // nur Geräte Instanzen auflisten
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		//echo IPS_GetName($instanz)." ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r(IPS_GetInstance($instanz));
		}

	/************************************
	 *
	 *  Homematic Sockets auflisten, nur wenn vorhanden
	 *
	 ******************************************/

	$ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
	$HomInstanz=sizeof($ids);
	if($HomInstanz == 0)
		{
		echo "ERROR: Keine HomeMatic Socket Instanz gefunden!\n";
		}
	else
		{	
		$includefile.='function HomematicInstanzen() { return array('."\n";
		for ($i=0;$i < $HomInstanz; $i++)
			{
			$ccu_name=IPS_GetName($ids[$i]);
			echo "\nHomatic Socket ID ".$ids[$i]." / ".$ccu_name."   \n";
			$config[$i]=json_decode(IPS_GetConfiguration($ids[$i]));
			Print_r($config[$i]);
			
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

	echo "\nFHT Geräte: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		//echo IPS_GetName($instanz)." ".$instanz." \n";
		$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
		$includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
		$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
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

	echo "\nFS20EX Geräte: ".sizeof($alleInstanzen)."\n\n";
	foreach ($alleInstanzen as $instanz)
		{
		echo str_pad(IPS_GetName($instanz),30)." ".$instanz." ".IPS_GetProperty($instanz,'HomeCode')." ".IPS_GetProperty($instanz,'DeviceList')."\n";
		//echo IPS_GetName($instanz)." ".$instanz." \n";
		}

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
		//echo IPS_GetName($instanz)." ".$instanz." \n";
		$includefile.='"'.IPS_GetName($instanz).'" => array('."\n         ".'"OID" => '.$instanz.', ';
		$includefile.="\n         ".'"HomeCode" => "'.IPS_GetProperty($instanz,'HomeCode').'", ';
		$includefile.="\n         ".'"Adresse" => "'.IPS_GetProperty($instanz,'Address').'", ';
		$includefile.="\n         ".'"SubAdresse" => "'.IPS_GetProperty($instanz,'SubAddress').'", ';
		$includefile.="\n         ".'"Name" => "'.IPS_GetName($instanz).'", ';
		$includefile.="\n         ".'"CONFIG" => \''.IPS_GetConfiguration($instanz).'\', ';		
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
	$includehomematic=	'function get_HomematicConfiguration() {'."\n".'            return array('." \n";
	$includefile.='function HomematicList() { return array('."\n";

	echo "\nHomematic Geräte: ".sizeof($alleInstanzen)."\n\n";
	$serienNummer=array();
	foreach ($alleInstanzen as $instanz)
		{
		$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
		switch (IPS_GetProperty($instanz,'Protocol'))
			{
			case 0:
				$protocol="Funk";
				break;
			case 2:
				$protocol="IP";
				break;
			default:
				$protocol="Wired";
				break;
			}
		$HM_Adresse=IPS_GetProperty($instanz,'Address');
		$result=explode(":",$HM_Adresse);
		//print_r($result);
		echo str_pad(IPS_GetName($instanz),40)." ".$instanz." ".$HM_Adresse." ".str_pad($protocol,6)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name;
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
			$includefile.="\n         ".'"COID" => array(';
		
			$type=getHomematicType($instanz);	/* gibt als echo auch den Typ aus */
			$result=explode(":",IPS_GetProperty($instanz,'Address'));
			if ($type<>"") 
				{
				$includehomematic.='             '.str_pad(('"'.IPS_GetName($instanz).'"'),40).' => array("'.$result[0].'",'.$result[1].',HM_PROTOCOL_BIDCOSRF,'.$type.'),'."\n";
				}	
	
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
			echo "     Statusvariable, wird nicht im Includefile geführt.\n";
			}	
		}

	/*$includefile.=');'."\n".'?>';*/
	$includefile.=');}'."\n";
	$includefile.="\n".'?>';
	$includehomematic.=');}'."\n";
	$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\EvaluateHardware\EvaluateHardware_Include.inc.php';
	if (!file_put_contents($filename, $includefile)) {
        throw new Exception('Create File '.$filename.' failed!');
    		}
	//include $filename;
	//print_r($fileList);
	
	echo "\n";
	echo $includehomematic;
	
	} // ende else if execute

/********************************************************************************************************************/


/* durchsucht alle Homematic Instanzen
 * nach Adresse:Port
 * wenn adresse:port uebereinstimmt die Instanz ID zurückgeben, sonst 0
 */

function GetInstanceIDFromHMID($sid)
	{
    $ids = IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
    foreach($ids as $id)
    	{
        $a = explode(":", HM_GetAddress($id));
        $b = explode(":", $sid);
        if($a[0] == $b[0])
        	{
            return $id;
        	}
    	}
    return 0;
	}

/********************************************************************************************************************/

/* anhand einer Homatic Instanz ID ermitteln 
 * um welchen Typ von Homematic Geraet es sich handeln koennte,
 * es wird nur BUTTON, SWITCH, DIMMER, SHUTTER unterschieden
 */

function getHomematicType($instanz)
	{
	$cids = IPS_GetChildrenIDs($instanz);
	//print_r($cids);
	$homematic=array();
	foreach($cids as $cid)
		{
		$homematic[]=IPS_GetName($cid);
		}
	sort($homematic);
	//print_r($homematic);
	/* 	define ('HM_TYPE_LIGHT',					'Light');
	define ('HM_TYPE_SHUTTER',					'Shutter');
	define ('HM_TYPE_DIMMER',					'Dimmer');
	define ('HM_TYPE_BUTTON',					'Button');
	define ('HM_TYPE_SMOKEDETECTOR',			'SmokeDetector');
	define ('HM_TYPE_SWITCH',					'Switch'); */
	$type=""; echo "       ";
	if ( isset ($homematic[0]) ) /* es kann auch Homematic Variablen geben, die zwar angelegt sind aber die Childrens noch nicht bestimmt wurden. igorieren */
		{
		switch ($homematic[0])
				{
				case "ERROR":
					echo "Funk-Tür-/Fensterkontakt\n";
					break;
				case "INSTALL_TEST":
					if ($homematic[1]=="PRESS_CONT")
						{
						echo "Taster 6fach\n";
						}
					else
						{
						echo "Funk-Display-Wandtaster\n";
						}
					$type="HM_TYPE_BUTTON";
					break;
				case "ACTUAL_HUMIDITY":
					echo "Funk-Wandthermostat\n";
					break;
				case "ACTUAL_TEMPERATURE":
					echo "Funk-Heizkörperthermostat\n";
					break;
				case "BRIGHTNESS":
					echo "Funk-Bewegungsmelder\n";
					break;
				case "DIRECTION":
					if ($homematic[1]=="ERROR_OVERHEAT")
						{
						echo "Dimmer\n";
						$type="HM_TYPE_DIMMER";						
						}
					else
						{
						echo "Rolladensteuerung\n";
						}
					break;

				case "PROCESS":
				case "INHIBIT":
					echo "Funk-Schaltaktor 1-fach\n";
					$type="HM_TYPE_SWITCH";
					break;
				case "BOOT":
					echo "Funk-Schaltaktor 1-fach mit Energiemessung\n";
					$type="HM_TYPE_SWITCH";
					break;
				case "CURRENT":
					echo "Energiemessung\n";
					break;
				case "HUMIDITY":
					echo "Funk-Thermometer\n";
					break;
				case "CONFIG_PENDING":
					if ($homematic[1]=="DUTYCYCLE")
						{
						echo "Funkstatusregister\n";
						}
					elseif ($homematic[1]=="DUTY_CYCLE")
						{
						echo "IP Funkstatusregister\n";
						}
					else
						{
						echo "IP Funk-Schaltaktor\n";
						$type="HM_TYPE_SWITCH";
						}
					//print_r($homematic);
					break;					
				default:
					echo "unknown\n";
					print_r($homematic);
					break;
				}
		}
	else
		{
		echo "   noch nicht angelegt.\n";
		}			

	return ($type);
	}

function getHomematicDeviceType($instanz)
	{


	}

?>