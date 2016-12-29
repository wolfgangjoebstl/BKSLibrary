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
					echo "Operation OR for OID : ".$oid." ".GetValue($oid)." Result : ".$result."\n";
					}
				}
			if ($type == "AND")
				{
				foreach ($operation as $oid)
					{
					$result = $result && GetValue($oid);
					echo "Operation AND for OID : ".$oid." ".GetValue($oid)." ".$result."\n";
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
		$moduleParams2=Array();

	   /* Befehlsgruppe zerlegen zB von params : [0] OnChange [1] Status [2] name:Stiegenlicht,speak:Stiegenlicht
		 * aus [2] name:Stiegenlicht,speak:Stiegenlicht wird
		 *          [0] name:Stiegenlicht [1] speak:Stiegenlicht
		 *
		 * Parameter mit : enthalten Befehl:Parameter
		 */

	   $params2=$params[2];
  		$moduleParams2 = explode(',', $params2);
		$count=count($moduleParams2);
		echo "Insgesamt ".$count." Parameter erkannt in \"".$params2."\" \n";
		
		/* in parges werden alle Parameter erfasst und abgespeichert */
		$parges=array();
		switch ($count)
		   {
	   	case "6":
		   case "5":
		   case "4":
				$i=3;
				while ($i<count($moduleParams2))
			   	{
					$params_more=explode(":",$moduleParams2[$i]);
					if (count($params)>1)
      	   		{
						$parges=self::parseParameter($params_more,$parges);
					   }
					$i++;
				   }
	   	case "3":
				$params_three=explode(":",$moduleParams2[2]);
				if (count($params_three)>1)
					{
					$parges=self::parseParameter($params_three,$parges);
					}
				else
				   {
					$parges["DELAY"]=(integer)$params_three[0];
					}
		   case "2":
		   	$params_two=explode(":",$moduleParams2[1]);
				if (count($params_two)>1)
					{
					$parges=self::parseParameter($params_two,$parges);
					}
				else
				   {
					$parges["STATUS"]=$params_two[0];
				   }
	   	case "1":
		   	$params_one=explode(":",$moduleParams2[0]);
				if (count($params_one)>1)
					{
					$parges=self::parseParameter($params_one,$parges);
					}
				else
				   {
					$parges["NAME"]=$params_one[0];
					}
		      break;
			default:
				echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
			   break;
			}
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
			$result[strtoupper($params[0])]=$params;
			}
		return($result);
		}


	}
		

?>