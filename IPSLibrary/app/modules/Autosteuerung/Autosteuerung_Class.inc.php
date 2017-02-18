<?

/*****************************************************************************************************
 *
 * Autosteuerung Handler
 *
 * alle Routinen die mit der Erstellung und Verwaltung der Events der Autostuerung zu tun haben
 *
 **************************************************************************************************************/

class AutosteuerungHandler 
	{

		private static $eventConfigurationAuto = array();
		private static $scriptID;

		/**
		 * @public
		 *
		 * Initialisierung des IPSMessageHandlers
		 *
		 */
		public function __construct($scriptID) {
			self::$scriptID=$scriptID;
		}

		/**
		 * @private
		 *
		 * Liefert die aktuelle Auto Event Konfiguration
		 *
		 * @return string[] Event Konfiguration
		 */
		private static function Get_EventConfigurationAuto() {
			if (self::$eventConfigurationAuto == null) {
				self::$eventConfigurationAuto = Autosteuerung_GetEventConfiguration();
			}
			return self::$eventConfigurationAuto;
		}
		
		/**
		 * @private
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 * @param string[] $configuration Neue Event Konfiguration
		 */
		private static function Set_EventConfigurationAuto($configuration) {
		   self::$eventConfigurationAuto = $configuration;
		}
		
		/**
		 * @public
		 *
		 * Erzeugt ein Event für eine übergebene Variable, das den IPSMessageHandler beim Auslösen
		 * aufruft.
		 *
		 * @param integer $variableId ID der auslösenden Variable
		 * @param string $eventType Type des Events (OnUpdate oder OnChange)
		 */
		function CreateEvent($variableId, $eventType, $scriptId)
			{
			switch ($eventType) {
				case 'OnChange':
					$triggerType = 1;
					break;
				case 'OnUpdate':
					$triggerType = 0;
					break;
				default:
					throw new Exception('Found unknown EventType '.$eventType);
			}
			$eventName = $eventType.'_'.$variableId;
			$eventId   = @IPS_GetObjectIDByIdent($eventName, $scriptId);
			if ($eventId === false) {
				$eventId = IPS_CreateEvent(0);
				IPS_SetName($eventId, $eventName);
				IPS_SetIdent($eventId, $eventName);
				IPS_SetEventTrigger($eventId, $triggerType, $variableId);
				IPS_SetParent($eventId, $scriptId);
				IPS_SetEventActive($eventId, true);
				IPSLogger_Dbg (__file__, 'Created IPSMessageHandler Event for Variable='.$variableId);
			}
		}

		/**
		 * @private
		 *
		 * Speichert die aktuelle Event Konfiguration
		 *
		 * @param string[] $configuration Konfigurations Array
		 */
		private static function StoreEventConfiguration($configuration)
		   {
			// Build Configuration String
			$configString = '$eventConfiguration = array(';
			//echo "----> wird jetzt gespeichert:\n";
			//print_r($configuration);

			foreach ($configuration as $variableId=>$params) 
				{
				$configString .= PHP_EOL.chr(9).chr(9).chr(9).$variableId.' => array(';
				for ($i=0; $i<count($params); $i=$i+3) 
					{
					if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';
					$configString .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
					}
				$configString .= '),'.'        /* '.IPS_GetName($variableId).'  '.IPS_GetName(IPS_GetParent($variableId)).'     */';
				}
			$configString .= PHP_EOL.chr(9).chr(9).chr(9).');'.PHP_EOL.PHP_EOL.chr(9).chr(9);
			//echo $configString;
			// Write to File
			$fileNameFull = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/Autosteuerung/Autosteuerung_Configuration.inc.php';
			if (!file_exists($fileNameFull)) {
				throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);
			}
			$fileContent = file_get_contents($fileNameFull, true);
			$pos1 = strpos($fileContent, '$eventConfiguration = array(');
			$pos2 = strpos($fileContent, 'return $eventConfiguration;');

			if ($pos1 === false or $pos2 === false) {
				throw new IPSMessageHandlerException('EventConfiguration could NOT be found !!!', E_USER_ERROR);
			}
			$fileContentNew = substr($fileContent, 0, $pos1).$configString.substr($fileContent, $pos2);
			file_put_contents($fileNameFull, $fileContentNew);
			self::Set_EventConfigurationAuto($configuration);
						}
		/************************************************************************
		 *
		 * Events neu registrieren
		 * Parameter werden ueberschrieben, wenn sie vorher leer waren
		 * Parameter werden nicht ueberschrieben wenn sie aktuell leer sind 
		 *		
		 ********************************************************************************/	

		function registerAutoEvent($variableId, $eventType, $componentParams, $moduleParams)
			{
			$configuration = self::Get_EventConfigurationAuto();
			//echo "---> war gespeichert.\n";
			//print_r($configuration);

			if (array_key_exists($variableId, $configuration))
				{
				$moduleParamsNew = explode(',', $moduleParams);
				$moduleClassNew  = $moduleParamsNew[0];

				$params = $configuration[$variableId];
				//echo "Bearbeite Variable with ID : ".$variableId." : ".count($params)." Parameter, ComponentPars: ".$componentParams." ModulPars: ".$moduleParams."\n";
				//print_r($params);
				$ct_par=count($params);
				if (($ct_par%3)>0)
				   {
					echo "Anzahl Parameter bei ID ".$variableId." : ".$ct_par." da sind ",($ct_par%3)." Parameter zuviel.\n";
					$ct_parN=$ct_par-($ct_par%3);
					for ($i=$ct_parN; $i<$ct_par; $i++)
						{
						unset($configuration[$variableId][$i]);
						}
               }
            else
               {
               $ct_parN=$ct_par;
               }
				for ($i=0; $i<$ct_parN; $i=$i+3)
					{
					$moduleParamsCfg = $params[$i+2];
					$moduleParamsCfg = explode(',', $moduleParamsCfg);
					$moduleClassCfg  = $moduleParamsCfg[0];
					// Found Variable and Module --> Update Configuration
					if ($moduleClassCfg=$moduleClassNew)
						{
						$found = true;
						$configuration[$variableId][$i]   = $eventType;
						$configuration[$variableId][$i+1] = $componentParams;
						$configuration[$variableId][$i+2] = $moduleParams;
						}
					}
				}
			else
			   {
			   //echo "Lege neue Variable mit ID : ".$variableId." : ".count($params)." Parameter, ComponentPars: ".$componentParams." ModulPars: ".$moduleParams." an.\n";
				//echo "Variable with ID ".$variableId. " not found\n";
				// Variable NOT found --> Create Configuration
				$configuration[$variableId][] = $eventType;
				$configuration[$variableId][] = $componentParams;
				$configuration[$variableId][] = $moduleParams;
				}
				//print_r($configuration);
				self::StoreEventConfiguration($configuration);
				self::CreateEvent($variableId, $eventType, self::$scriptID);
   		}
		
		/************************************************************************
		 *
		 * einmal registrierte Events können auch gelöscht werden
		 * das Event muss auch später gelöscht werden 
		 *		
		 ********************************************************************************/	
			
		function UnRegisterAutoEvent($variableId)
			{
			$configuration = self::Get_EventConfigurationAuto();

			if (array_key_exists($variableId, $configuration))
				{
				unset($configuration[$variableId]);
				self::StoreEventConfiguration($configuration);
				//self::CreateEvent($variableId, $eventType, self::$scriptID);
				}
   		}			

	} /* ende class */

/*****************************************************************************************************
 *
 * Autosteuerung Operator
 *
 * mächtige Routinen für den Betrieb
 *
 **************************************************************************************************************/

class AutosteuerungOperator 
	{

	public function __construct()
		{
		}

	public function Anwesend()
		{
		
		IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
		
		$logic=Autosteuerung_Anwesend();
		$result=false;
		foreach($logic as $type => $operation)
			{
			if ($type == "OR")
				{
				foreach ($operation as $oid)
					{
					$result = $result || GetValueBoolean($oid);
					//echo "Operation OR for OID : ".$oid." ".GetValue($oid)." Result : ".$result."\n";
					}
				}
			if ($type == "AND")
				{
				foreach ($operation as $oid)
					{
					$result = $result && GetValue($oid);
					//echo "Operation AND for OID : ".$oid." ".GetValue($oid)." ".$result."\n";
					}
				
				}
			}
		return ($result);				
		}

	} /* ende class */

	
/*****************************************************************************************************
 *
 * Autosteuerung
 *
 * Routinen zur Evaluierung der Befehlsketten
 *
 **************************************************************************************************************/


class Autosteuerung
	{
	var $sunrise=0;
	var $sunset=0;

	var $now=0;
	var $timeStop=0;
	var $timeStart=0;
	
	var $installedmodules;
	var $CategoryIdData;
	var $CategoryId_Ansteuerung;
	var $CategoryId_Anwesenheit;
	var $CategoryId_SchalterAnwesend;

	public function __construct()
		{
		// Sonnenauf.- u. Untergang berechnen
		$longitude = 16.36; //14.074881;
		$latitude = 48.21;  //48.028615;
		$timestamp = time();
		/*php >Funktion: par1: Zeitstempel des heutigen Tages
					  par2: Format des retourwertes, String, Timestamp, float SUNNFUNCS_RET_xxxxx
					  par3: north direction (for south use negative)
					  par4: west direction (for east use negative)
					  par5: zenith, see example
							$zenith=90+50/60; Sunrise/sunset
							$zenith=96; Civilian Twilight Start/end
							$zenith=102; Nautical Twilight Start/End
							$zenith=108; Astronomical Twilight start/End
					  par6: GMT offset  zB mit date("O")/100 oder date("Z")/3600 bestimmen
					  möglicherweise mit Sommerzeitberechnung addieren:  date("I") == 1 ist Sommerzeit
		*/
		$this->sunrise = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+50/60, date("O")/100);
		$this->sunset = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+50/60, date("O")/100);
		echo "Sonnenauf/untergang ".date("H:i",$this->sunrise)." ".date("H:i",$this->sunset)." \n";

		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager)) 
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
			}
		$this->installedModules 				= $moduleManager->GetInstalledModules();
		$this->CategoryIdData   				= $moduleManager->GetModuleCategoryID('data');
		$this->CategoryId_Ansteuerung			= IPS_GetCategoryIDByName("Ansteuerung", $this->CategoryIdData);
		$this->CategoryId_Anwesenheit			= IPS_GetObjectIDByName("Anwesenheitserkennung",$this->CategoryId_Ansteuerung);
		$this->CategoryId_SchalterAnwesend	= IPS_GetObjectIDByName("SchalterAnwesend",$this->CategoryId_Anwesenheit);		
		}

	function isitdark()
		{
		$acttime=time();
		if (($acttime>$this->sunset) || ($acttime<$this->sunrise))
			{
			return(true);
			}
		else
		   {
			return(false);
			}
		}
	
	function isitlight()
		{
		$acttime=time();
		if (($acttime<$this->sunset) && ($acttime>$this->sunrise))
			{
			return(true);
			}
		else
		   {
			return(false);
			}
		}


	function timeright($scene)
		{
		echo "Szene ".$scene["NAME"]."\n";
       	$actualTime = explode("-",$scene["ACTIVE_FROM_TO"]);
       	if ($actualTime[0]=="sunset") {$actualTime[0]=date("H:i",$this->sunset);}
       	if ($actualTime[1]=="sunrise") {$actualTime[1]=date("H:i",$this->sunrise);}
       	//print_r($actualTime);
       	$actualTimeStart = explode(":",$actualTime[0]);
        	$actualTimeStartHour = $actualTimeStart[0];
        	$actualTimeStartMinute = $actualTimeStart[1];
        	$actualTimeStop = explode(":",$actualTime[1]);
        	$actualTimeStopHour = $actualTimeStop[0];
        	$actualTimeStopMinute = $actualTimeStop[1];
			echo "Schaltzeiten:".$actualTimeStartHour.":".$actualTimeStartMinute." bis ".$actualTimeStopHour.":".$actualTimeStopMinute."\n";
        	$this->timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);
        	$this->timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
      	$this->now = time();

       	if (($this->now > $this->timeStart) && ($this->now < $this->timeStop))
				{
          	$minutesRange = ($this->timeStop-$this->timeStart)/60;
          	$actionTriggerMinutes = 5;
            $rndVal = rand(1,100);
				echo "Zufallszahl:".$rndVal."\n";
				return (($rndVal < $scene["EVENT_CHANCE"]) || ($scene["EVENT_CHANCE"]==100));
				}
			else return (false);	
		}	

	/***************************************
	 *
	 * hier wird der Befehl in die Einzelbefehkle zerlegt
	 *
	 * Es gibt folgende befehle die extrahiert werden:
	 *  NAME, SPEAK, OFF, ON, oFF_MASK, ON_MASK, COND, DELAY
	 *
	 * Kurzbefehle:
	 *  1 Parameter:   "NAME"
	 *  2 Parameter    "NAME" "STATUS"
	 *  3 Parameter:   "NAME" "STATUS" "DELAY"
	 *
	 *******************************************************/

	function ParseCommand($params)
		{
	   	/* Befehlsgruppe zerlegen zB von params : [0] OnChange [1] Status [2] name:Stiegenlicht,speak:Stiegenlicht
		 * aus [2] name:Stiegenlicht,speak:Stiegenlicht wird
		 *          [0] name:Stiegenlicht [1] speak:Stiegenlicht
		 *
		 * Parameter mit : enthalten Befehl:Parameter
		 */

		/* in parges werden alle Parameter pro Kommando erfasst und abgespeichert 
		 *
		 * im uebergeordneten Element steht der Befehl der aber als Unterobjekt im array wiederholt wird, vorbereiten für ; Befehl, damit können mehrere Befehle nacheinander abgearbeitet werden
		 * 
		 */
		$parges=array();

		$params2=$params[2];
		$commands = explode(';', $params2);
		$Kommando=0;
		foreach ($commands as $command)
			{
			$Kommando++;
			$moduleParams2 = explode(',', $command);
			$count=count($moduleParams2);
			$Eintrag=$count;
			echo "Kommando : ".$Kommando." Insgesamt ".$count." Parameter erkannt in \"".$params2."\" \n";

			if (strtoupper($params[1])=="VENTILATOR") 
				{ 
				echo "Es geht um Ventilatoren \n"; 
				// Dieser Befehl muss erkannt werden "Ventilator,25,true,24,false"
				switch ($count)
				   	{
	   				case "6":
						$i=5;
						while ($i<count($moduleParams2))
			   				{
							$params_more=explode(":",$moduleParams2[$i]);
							if (count($params_more)>1)
      		   					{
								$parges[$Kommando][$Eintrag]=self::parseParameter($params_more);
								$Eintrag--;
						   		}
							$i++;
					   		}					
		   			case "5":
						$i=4;					
						$params_more=explode(":",$moduleParams2[$i]);
						if (count($params_more)>1)
   		   					{
							$parges[$Kommando][$Eintrag]=self::parseParameter($params_more);
							$Eintrag--;
					   		}
						else
							{
							//echo "Parameter 5 ist ".$params_more[0]."\n";
							if  (strtoupper($params_more[0])=='FALSE')
								{
								$parges[$Kommando][$Eintrag][]="THREASHOLDLOW";
								}
							else
								{
								$parges[$Kommando][$Eintrag][]="THREASHOLDHIGH";
								}
							}	
						$i--;
		   			case "4":
						$params_more=explode(":",$moduleParams2[$i]);
						if (count($params_more)>1)
							{
							$parges[$Kommando][$Eintrag]=self::parseParameter($params_more);
							$Eintrag--;						
							}
						else
					   		{
							$parges[$Kommando][$Eintrag][]=(integer)$params_more[0];
							$Eintrag--;								
							}
	   				case "3":
						$params_three=explode(":",$moduleParams2[2]);
						if (count($params_three)>1)
							{
							$parges[$Kommando][$Eintrag]=self::parseParameter($params_three);
							$Eintrag--;						
							}
						else
							{
							if  (strtoupper($params_more[0])=='FALSE')
								{
								$parges[$Kommando][$Eintrag][]="THREASHOLDLOW";
								}
							else
								{
								$parges[$Kommando][$Eintrag][]="THREASHOLDHIGH";
								}						
							}
			   		case "2":
		   				$params_two=explode(":",$moduleParams2[1]);
						if (count($params_two)>1)
							{
							$parges[$Kommando][$Eintrag]=self::parseParameter($params_two);
							$Eintrag--;							
							}
						else
					   		{
							$parges[$Kommando][$Eintrag][]=$params_two[0];
							$Eintrag--;								
					   		}
		   			case "1":
			   			$params_one=explode(":",$moduleParams2[0]);
						if (count($params_one)>1)
							{
							$parges[$Kommando][$Eintrag]=self::parseParameter($params_one);
							$Eintrag--;							
							}
						else
					  		{
							$parges[$Kommando][$Eintrag][]="NAME";
							$parges[$Kommando][$Eintrag][]=$params_one[0];
							$Eintrag--;								
							}
			      		break;
					default:
						echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
				   		break;
					}
				} 
			else
				{	
				/* es gibt noch Sonderformen der Befehlsdarstellung, diese vorverarbeiten */
				switch ($count)
				   	{
	   				case "6":
		   			case "5":
			   		case "4":
						$i=3;
						while ($i<count($moduleParams2))
				   			{
							$params_more=explode(":",$moduleParams2[$i]);
							if (count($params_more)>1)
      	   						{
								$parges[$Kommando][$Eintrag]=self::parseParameter($params_more);
								$Eintrag--;
						   		}
							$i++;
					   		}
	   				case "3":
						$params_three=explode(":",$moduleParams2[2]);
						if (count($params_three)>1)
							{
							$parges[$Kommando][$Eintrag]=self::parseParameter($params_three);
							$Eintrag--;						
							}
						else
					   		{
							$parges[$Kommando][$Eintrag][]="DELAY";
							$parges[$Kommando][$Eintrag][]=(integer)$params_three[0];
							$Eintrag--;								
							}
			   		case "2":
			   			$params_two=explode(":",$moduleParams2[1]);
						if (count($params_two)>1)
							{
							$parges[$Kommando][$Eintrag]=self::parseParameter($params_two);
							$Eintrag--;							
							}
						else
					   		{
							$parges[$Kommando][$Eintrag][]="STATUS";
							$parges[$Kommando][$Eintrag][]=$params_two[0];
							$Eintrag--;								
				   			}
		   			case "1":
			   			$params_one=explode(":",$moduleParams2[0]);
						if (count($params_one)>1)
							{
							$parges[$Kommando][$Eintrag]=self::parseParameter($params_one);
							$Eintrag--;							
							}
						else
					  		{
							$parges[$Kommando][$Eintrag][]="NAME";
							$parges[$Kommando][$Eintrag][]=$params_one[0];
							$Eintrag--;								
							}
			      		break;
					default:
						echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
				   		break;
					}
				}
			ksort($parges[$Kommando]);
			}	
		/* parges in richtige Reihenfolge bringen , NAME muss an den Anfang, es können auch Sortierinfos an den Anfang gepackt werden */	
		return($parges);
		}

	/*
	 * ersten teil des Arrays als befehl erkenn, auf Grossbuchtaben wandeln, und das ganze array nochmals darunter speichern
	 * Erweitert das übergebene Array.
	 *
	 *
	 */
		
	private function parseParameter($params,$result=array())
		{
		if (count($params)>1)
			{
			$result[]=strtoupper($params[0]);
			$result[]=$params[1];
			}
		return($result);
		}

	/*
	 * Auch das Evaluieren kann gemeinsam erfolgen, es gibt nur kleine Unterschiede zwischen den Befehlen 
	 *
	 ************************************/

	function EvaluateCommand($befehl,$result=array())
		{
		echo "Befehl ".$befehl[0]." abarbeiten.\n";
		
			switch (strtoupper($befehl[0]))
				{
				case "OID":			/* muss noch implementiert werden , wie switch name nur statt IPSLight die OID */
					$result["OID"]=$befehl[1];
					break;
				
				case "NAME":		/* IPSLight identifier der verändert wird */
					$SwitchName=$befehl[1];
					$result["NAME"]=$SwitchName;
					break;
				
				case "STATUS":    /* für die Kurzbefehle, wird normalerweise durch die Befehle On und OFF ersetzt */
					if (strtoupper($befehl[1])=="TRUE") { $status=true;};
					if (strtoupper($befehl[1])=="FALSE") { $status=false;};
					if (strtoupper($befehl[1])=="TOGGLE")
						{
						if (strtoupper($params[0])=="ONUPDATE")
							{
							/* Bei OnUpdate herausfinden wie der Wert der Variable ist */
							//print_r($result);
							$lightName=$result["NAME"];
							$switchId = $lightManager->GetSwitchIdByName($lightName);
							$status=!$lightManager->GetValue($switchId);
							}
						else
							{
							/* bei OnChange nur invertieren, wenn OnUpdate bei einem Taster dann hat dieser Wert wenig zu sagen */
							$status=!$status;          
							}
						};
					$result["STATUS"]=$status;	
					break;
					
				case "ON":
					$value_on=strtoupper($befehl[1]);
					$i=2;
					while ($i<count($befehl))
						{
						if (strtoupper($befehl[$i])=="MASK")
							{
							$mask_on=$befehl[$i++];
							$result["ON_MASK"]=$mask_on;
							}
						$i++;
						}
					switch ($value_on)
						{
						case "TRUE":
						case "FALSE":	
							$result["ON"]=$value_on;
							break;
						case "TOGGLE":
							/* Befehl noch nicht implementiert */	
						default:
							break;
						}		
					break;
				
				case "OFF":
					$value_off=strtoupper($befehl[1]);
					$i=2;
					while ($i<count($befehl))
						{
						if (strtoupper($befehl[$i])=="MASK")
							{
							$mask_off=$befehl[$i++];
							$result["OFF_MASK"]=$mask_off;
							}
						$i++;
						}
					switch ($value_off)
						{
						case "TRUE":
						case "FALSE":	
							$result["OFF"]=$value_off;
							break;
						case "TOGGLE":
							/* Befehl noch nicht implementiert */	
						default:
							break;
						}							
					break;
				
				case "DELAY":
					$delayValue=(integer)$befehl[1];
					$result["DELAY"]=$delayValue;
					break;
				
				case "ENVELOPE":
					$envelValue=(integer)$befehl[1];
					$result["ENVEL"]=$envelValue;
					break;
				
				case "LEVEL":
					$levelValue=(integer)$befehl[1];
					$result["LEVEL"]=$levelValue;
					break;
				
				case "SPEAK":
					$speak=$befehl[1];
					$result["SPEAK"]=$speak;
					break;
				
				case "MONITOR":
					$monitor=$befehl[1];
					if ($monitor=="STATUS")
						{
						if ($status==true)
							{
							$result="ON";
							$result["MONITOR"]=$monitor;
							}
						else
							{
							$result="OFF";
							$result["MONITOR"]=$monitor;
							}
						}
					else
						{
						$result["MONITOR"]=$monitor;
						}
					break;
				
				case "MUTE":
					$mute=$befehl[1];
					if ($mute=="STATUS")
						{
						if ($status==true)
							{
							$mute="ON";
							$result["MONITOR"]=$mute;
							}
						else
							{
							$mute="OFF";
							$result["MONITOR"]=$mute;
							}
						}
					else
						{
						$result["MUTE"]=$mute;
						}
					break;
				
				case "IF":     /* parges hat nur die Parameter übermittelt, hier die Auswertung machen. Es gibt zumindest light, dark und einen IPS Light Variablenname (wird zum Beispiel für die Heizungs Follow me Funktion verwendet) */
					$cond=strtoupper($befehl[1]);
					$result["COND"]=$cond;
					if ($cond=="LIGHT")
						{
						/* nur Schalten wenn es hell ist, geschaltet wird nur wenn ein variablenname bekannt ist */
						if ($auto->isitdark())
							{
							unset($SwitchName);
							unset($speak);
							$switch=false;
							$result["SWITCH"]=false;						
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist dunkel ');
							}
						}
					elseif ($cond=="DARK")
						{
						/* nur Schalten wenn es dunkel ist, geschaltet wird nur wenn ein variablenname bekannt ist */
				  		if ($auto->isitlight())
							{
							unset($SwitchName);
							unset($speak);
							$switch=false;
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist hell ');
							}
						}
					else
						{  /* weder light noch dark, wird ein IPSLight Variablenname sein. Wert ermitteln */
						$checkId = $lightManager->GetSwitchIdByName($cond);
						$statusCheck=$lightManager->GetValue($checkId);
						$result["SWITCH"]=$statusCheck;	
						}			
					break;
				
				default:
					echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
		   		break;				
				}  /* ende switch */

		return ($result);
		}	


	/***************************************
	 *
	 * hier wird der Befehl umgesetzt, benötigt IPSLight
	 *
	 *******************************************************/

	function ExecuteCommand($result,$simulate=false)
		{
		$command="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");";
		$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
		$switchCategoryId  = IPS_GetObjectIDByIdent('Switches', $baseId);
		$groupCategoryId   = IPS_GetObjectIDByIdent('Groups', $baseId);
		$prgCategoryId   = IPS_GetObjectIDByIdent('Programs', $baseId);
		
		$resultID=@IPS_GetVariableIDByName($result["NAME"],$switchCategoryId);
		if ($resultID==false)
	   		{
			$resultID=@IPS_GetVariableIDByName($result["NAME"],$groupCategoryId);
			if ($resultID==false)
	   			{
				$resultID=@IPS_GetVariableIDByName($result["NAME"],$prgCategoryId);
				if ($resultID==false)
		   			{
					/* Name nicht bekannt */
					$result["IPSLIGHT"]="None";
		   			}
		   		else /* Wert ist ein Programm */
		   	   		{
		  			IPSLogger_Dbg(__file__, 'Wert '.$result["NAME"].' ist ein Programm. ');
		  			$command.="IPSLight_SetProgramNextByName(\"".$result["NAME"]."\");";
					$result["COMMAND"]=$command;
					$result["IPSLIGHT"]="Program";
	  		   		if ($simulate==false)
  		   		   		{
	   	  	   			IPSLight_SetProgramNextByName($result["NAME"]);
						}
		   	   		}
		   		}
		   	else   /* Wert ist eine Gruppe */
	   	   		{
	  			IPSLogger_Dbg(__file__, 'Wert '.$result["NAME"].' ist eine Gruppe. ');
  	   			$command.="IPSLight_SetGroupByName(\"".$result["NAME"]."\", false);";
				$result["COMMAND"]=$command;
				$result["IPSLIGHT"]="Group";	
				self::switchIPSLight($result,$simulate);			   	 	
			   	}
			}
		else     /* Wert ist ein Schalter */
		   	{
  			IPSLogger_Dbg(__file__, 'Wert '.$result["NAME"].' ist ein Schalter. ');
     		$command.="IPSLight_SetSwitchByName(\"".$result["NAME"]."\", false);";
			$result["COMMAND"]=$command;
			$result["IPSLIGHT"]="Switch";
			self::switchIPSLight($result,$simulate);			
		  	}   /* Ende Wert ist ein Schalter */

		return ($result);
		}


	private function switchIPSLight($result,$simulate=false)
		{	
  		if ($simulate==false)
  	   		{
			if ($result["STATUS"]===true)
		    	{
   				if (isset($result["ON"])==true)
   					{
	   				if ($result["ON"]=="FALSE")
   						{
						if ($result["IPSLIGHT"]=="Group")  {	IPSLight_SetGroupByName($result["NAME"],false);  }
		   		  	   	if ($result["IPSLIGHT"]=="Switch") {	IPSLight_SetSwitchByName($result["NAME"],false); }						
				   		}
	   				if ($result["ON"]=="TRUE")
			   			{
			   	  		if ($result["IPSLIGHT"]=="Group")  {	IPSLight_SetGroupByName($result["NAME"],true); }
		   		  	   	if ($result["IPSLIGHT"]=="Switch") 
							{	
							IPSLight_SetSwitchByName($result["NAME"],true); 
							if (isset($result["LEVEL"])==true)
	 							{
								$lightManager = new IPSLight_Manager();
								$switchId = $lightManager->GetSwitchIdByName($result["NAME"]."#Level");
								$lightManager->SetValue($switchId, $result["LEVEL"]);
								}
							
							}						
			   	  		}
				   	}
				else
					{
		   			if ($result["IPSLIGHT"]=="Group") 		{	IPSLight_SetGroupByName($result["NAME"],true); }
	   		  	   	if ($result["IPSLIGHT"]=="Switch") 		
						{	
						IPSLight_SetSwitchByName($result["NAME"],true); 
						if (isset($result["LEVEL"])==true)
 							{
							$lightManager = new IPSLight_Manager();
							$switchId = $lightManager->GetSwitchIdByName($result["NAME"]."#Level");
							$lightManager->SetValue($switchId, $result["LEVEL"]);
							}						
						}						
			   		}
		   	  	}
			else
		  		{
   				if (isset($result["OFF"])==true)
   			   		{
	   		   		if ($result["OFF"]=="FALSE")
   			      		{
		   	  	   		if ($result["IPSLIGHT"]=="Group") 	{	IPSLight_SetGroupByName($result["NAME"],false); }
	   		  	   		if ($result["IPSLIGHT"]=="Switch") 	{	IPSLight_SetSwitchByName($result["NAME"],false); }							
		   	  	   		}
	   		   		if ($result["OFF"]=="TRUE")
			  	   		{
		  		   		if ($result["IPSLIGHT"]=="Group") 	{	IPSLight_SetGroupByName($result["NAME"],true); }
	   		  	   		if ($result["IPSLIGHT"]=="Switch") 	
							{
							IPSLight_SetSwitchByName($result["NAME"],true); 
							if (isset($result["LEVEL"])==true)
 								{
								$lightManager = new IPSLight_Manager();
								$switchId = $lightManager->GetSwitchIdByName($result["NAME"]."#Level");
								$lightManager->SetValue($switchId, $result["LEVEL"]);
								}								
							}							
		 	   			}
		  	  		}
		  		else
		  	   		{
		 	   		if ($result["IPSLIGHT"]=="Group") 		{	IPSLight_SetGroupByName($result["NAME"],false); }
   		  	   		if ($result["IPSLIGHT"]=="Switch") 		{	IPSLight_SetSwitchByName($result["NAME"],false); }							
			   		}
				}
			}
		return($result);		
		}


	}
		

?>