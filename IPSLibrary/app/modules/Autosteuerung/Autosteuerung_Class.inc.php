<?

/*****************************************************************************************************
 *
 * Autosteuerung Handler
 *
 * alle Routinen die mit der Erstellung und Verwaltung der Events der Autosteuerung zu tun haben
 *
 * Unterschiedliche Klassen angelegt:
 *
 * AutosteuerungHandler zum Anlegen der Konfigurationszeilen im config File
 * AutosteuerungOperator für Funktionen die zum Betrieb notwendig sind (zB Anwesenheitsberechnung)
 * Autosteuerung sind die Funktionen die für die Evaluierung der Befehle im Konfigfile notwendig sind.
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
				//echo "   process ".$variableId."  (".IPS_GetName(IPS_GetParent($variableId))."/".IPS_GetName($variableId).")\n";
				//print_r($params);
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
	var $CategoryId_Anwesenheit, $CategoryId_Alarm;	
	var $CategoryId_SchalterAnwesend, $CategoryId_SchalterAlarm;
	var $lightManager;
	var $switchCategoryId, $groupCategoryId , $prgCategoryId;

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

		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager)) 
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
			}
		$this->installedModules 				= $moduleManager->GetInstalledModules();
		$this->CategoryIdData   				= $moduleManager->GetModuleCategoryID('data');
		$this->CategoryId_Ansteuerung			= IPS_GetCategoryIDByName("Ansteuerung", $this->CategoryIdData);
		$this->CategoryId_Anwesenheit			= @IPS_GetObjectIDByName("Anwesenheitserkennung",$this->CategoryId_Ansteuerung);
		if ($this->CategoryId_Anwesenheit === false)
			{
			$this->CategoryId_SchalterAnwesend	= false;
			}
		else
			{	
			$this->CategoryId_SchalterAnwesend	= IPS_GetObjectIDByName("SchalterAnwesend",$this->CategoryId_Anwesenheit);
			}		
		$this->CategoryId_Alarm			= @IPS_GetObjectIDByName("Alarmanlage",$this->CategoryId_Ansteuerung);
		if ($this->CategoryId_Alarm === false)
			{
			$this->CategoryId_SchalterAlarm	= false;
			}
		else
			{	
			$this->CategoryId_SchalterAlarm	= IPS_GetObjectIDByName("SchalterAlarmanlage",$this->CategoryId_Alarm);
			}
		$this->lightManager = new IPSLight_Manager();
		
		$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
		$this->switchCategoryId 	= IPS_GetObjectIDByIdent('Switches', $baseId);
		$this->groupCategoryId   	= IPS_GetObjectIDByIdent('Groups', $baseId);
		$this->prgCategoryId   		= IPS_GetObjectIDByIdent('Programs', $baseId);			
		}

	/* welche AutosteuerungsFunktionen sind aktiviert */

	function getFunctions($function="All")
		{
		$children=array();
		$childrenIDs=IPS_GetChildrenIDs($this->CategoryId_Ansteuerung);
		foreach ($childrenIDs as $ID)
			{
			$children[IPS_GetName($ID)]["OID"]=$ID;
			$children[IPS_GetName($ID)]["VALUE"]=GetValue($ID);
			$children[IPS_GetName($ID)]["VALUE_F"]=GetValueFormatted($ID);
			switch (IPS_GetName($ID))
				{
				case "GutenMorgenWecker":
					$weckerID=IPS_GetVariableIDByName("Wecker",$ID);
					//echo "Wecker OID : ".$weckerID."\n";
					$children[IPS_GetName($ID)]["STATUS"]=(integer)GetValue($weckerID);					
					break;
				case "Anwesenheitserkennung":
					$anwesendID=IPS_GetVariableIDByName("SchalterAnwesend",$ID);
					//echo "SchalterAnwesend OID : ".$anwesendID."\n";
					$children[IPS_GetName($ID)]["STATUS"]=(integer)GetValue($anwesendID);					
					break;					
				case "Alarmanlage":
					$alarmID=IPS_GetVariableIDByName("SchalterAlarmanlage",$ID);
					//echo "SchalterAlarmanlage OID : ".$alarmID."\n";
					$children[IPS_GetName($ID)]["STATUS"]=(integer)GetValue($alarmID);					
					break;					
				default:
					break;	
				}
			}
		if ($function == "All") { return ($children); }
		else
			{  
			if ( isset($children[$function]) == false ) { $children[$function]["VALUE"]=false; } 
			return($children[$function]);
			}
		}	

	/**************************************************
	 *
	 * Befehle für die Zeitperiodenerkennung
	 *
	 * dark/light abhängig vom Daylight
	 * 
	 * geplant Anwesend/Alarm von Erkennung und Awake/Sleep vom Gutenmorgenwecker
	 *
	 *****************************************************************/

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

	function isitsleep()
		{
		$functions=self::getFunctions();
		if ( isset($functions["GutenMorgenWecker"]["STATUS"]) )
			{
			if ($functions["GutenMorgenWecker"]["STATUS"] == 0) { return(true); }
			else  { return(false); }
			}
		else
			{
			return (false);	/* default awake */	
			}	
		}

	function isitwakeup()
		{
		$functions=self::getFunctions();
		if ( isset($functions["GutenMorgenWecker"]["STATUS"]) )
			{
			if ($functions["GutenMorgenWecker"]["STATUS"] == 1) { return(true); }
			else  { return(false); }
			}
		else
			{
			return (false);	/* default awake */	
			}	
		}

	function isitawake()
		{
		$functions=self::getFunctions();
		if ( isset($functions["GutenMorgenWecker"]["STATUS"]) )
			{
			if ($functions["GutenMorgenWecker"]["STATUS"] == 2) { return(true); }
			else  { return(false); }
			}
		else
			{
			return (true);	/* default awake */	
			}		
		}

	function isithome()
		{
		$functions=self::getFunctions();
		if ( isset($functions["Anwesenheitserkennung"]["STATUS"]) )
			{
			if ($functions["Anwesenheitserkennung"]["STATUS"] == 1) { return(true); }
			else  { return(false); }
			}
		else
			{
			return (true);	/* default away */	
			}		
		}

	function isitalarm()
		{
		$functions=self::getFunctions();
		if ( isset($functions["Alarmanlage"]["STATUS"]) )
			{
			if ($functions["Alarmanlage"]["STATUS"] == 1) { return(true); }
			else  { return(false); }
			}
		else
			{
			return (true);	/* default away */	
			}		
		}

	function getDaylight()
		{
		$result["Sunrise"]=$this->sunrise;
		$result["Sunset"]=$this->sunset;
		//echo "Sonnenauf/untergang ".date("H:i",$this->sunrise)." ".date("H:i",$this->sunset)." \n";
		$result["Text"]="Sonnenauf/untergang ".date("H:i",$this->sunrise)." ".date("H:i",$this->sunset);
		return $result;
		}

	/* Auswertung der Angaben in den Szenen. Schauen ob auf ein oder aus geschaltet werden soll */

	function switchingTimes($scene)
		{
		$switchingTimes = explode(",",$scene["ACTIVE_FROM_TO"]);
		$actualTimes=array(); $sindex=0;
		foreach ($switchingTimes as $switchingTime)
			{
			$actualTimes[$sindex] = explode("-",$switchingTime);
			if ($actualTimes[$sindex][0]=="sunset") {$actualTimes[$sindex][0]=date("H:i",$this->sunset);}
			if ($actualTimes[$sindex][0]=="sunrise") {$actualTimes[$sindex][0]=date("H:i",$this->sunrise);}		
			if ($actualTimes[$sindex][1]=="sunset") {$actualTimes[$sindex][1]=date("H:i",$this->sunset);}
			if ($actualTimes[$sindex][1]=="sunrise") {$actualTimes[$sindex][1]=date("H:i",$this->sunrise);}				
			$sindex++;	
			}
		return($actualTimes);
		}
		
	function timeright($scene)
		{
		echo "Szene ".$scene["NAME"]."\n";
       	$actualTimes = self::switchingTimes($scene);
       	//print_r($actualTimes);
		$timeright=false;
		for ($sindex=0;($sindex <sizeof($actualTimes));$sindex++)
			{		
       		$actualTimeStart = explode(":",$actualTimes[$sindex][0]);
        	$actualTimeStartHour = $actualTimeStart[0];
        	$actualTimeStartMinute = $actualTimeStart[1];
        	$actualTimeStop = explode(":",$actualTimes[$sindex][1]);
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
				if ( ($rndVal < $scene["EVENT_CHANCE"]) || ($scene["EVENT_CHANCE"]==100)) { $timeright=true; }
				}
			}	
		return ($timeright);	
		}	

	/***************************************
	 *
	 * hier wird der Befehl in die Einzelbefehle zerlegt, und Kurzbefehle in die Langform gebracht
	 * unterschiedliche Bearbeitung für Ventilator, Anwesenheit und alle anderen
	 *
	 * Es gibt folgende Befehle die extrahiert werden:
	 *  NAME, SPEAK, OFF, ON, oFF_MASK, ON_MASK, COND, DELAY
	 *
	 * Kurzbefehle:
	 *  1 Parameter:   "NAME"
	 *  2 Parameter    "NAME" "STATUS"
	 *  3 Parameter:   "NAME" "STATUS" "DELAY"
	 *
	 * Es folgen der Reihe nach die Befehle, möglichst für alle verscheidenen Varianten
	 *      EvaluateCommand()
	 *      ExecuteCommand() mit switch() liefert als Ergebnis die Parameter für Dim und Delay.
	 *......Abarbeitung von Delay Befehl
	 *
	 *******************************************************/

	function ParseCommand($params,$status=false,$simulate=false)
		{
		/************************** 
		 *
		 * keine Befehlsevaluierung, nur Festlegung des Rahmengerüsts und Vereinheitlichung der Abkürzer
		 * 
		 * Abkürzer unterschiedlich behandeln für: Ventilator, Anwesenheit und alle anderen 
		 * 
		 * Befehlsgruppe zerlegen zB von params : [0] OnChange [1] Status [2] name:Stiegenlicht,speak:Stiegenlicht
		 * aus [2] name:Stiegenlicht,speak:Stiegenlicht wird
		 *          [0] name:Stiegenlicht [1] speak:Stiegenlicht
		 *
		 * Parameter mit : enthalten Befehl:Parameter
		 * vorbereiten für ; Befehl, damit können mehrere Befehle nacheinander abgearbeitet werden
		 *
		 * im uebergeordneten Element steht der Befehl der aber als Unterobjekt im array wiederholt wird, 
		 *
		 * Beispiele:   			
		 *   als String    		"OnUpdate","Status","WohnzimmerKugellampe,toggle"
		 *  nach parse:       	["SOURCE","OnUpdate",0],["NAME","WohnzimmerKugellampe"],["OFF","toggle"]
		 *  nach evaluate:		SWITCH":true,"STATUS":0,"SOURCE":"ONUPDATE","NAME":"WohnzimmerKugellampe","VALUE":false,"OFF":"TRUE"
		 *
		 *  als String		 		"OnUpdate","Status","if:WohnzimmerKugellampe,speak:Lampe ein;ifnot:WohnzimmerKugellampe,speak:Lampe aus"
		 *  nach parse:			1:["SOURCE","OnUpdate",1],["IF","WohnzimmerKugellampe"],["SPEAK","Wohnzimmer Kugel Lampe ein"],["ON","true"]2:[....]  
		 *   nach evaluate: 		1: {"SWITCH":false,"STATUS":1,"SOURCE":"ONUPDATE","COND":"WOHNZIMMERKUGELLAMPE","SPEAK":"Lampe ein","ON":"TRUE"},
		 *								2: {"SWITCH":true ,"STATUS":1,"SOURCE":"ONUPDATE","COND":"WOHNZIMMERKUGELLAMPE","SPEAK":"Lampe aus","ON":"TRUE"}}
		 *
		 *  als String
		 *
		 *
		 */
		$parges=array();

		$params2=$params[2];
		$commands = explode(';', $params2);
		$Kommando=0;
		echo "Gesamter Befehl : ".$params2."\n";
		foreach ($commands as $command)
			{
			$Kommando++;
			$moduleParams2 = explode(',', $command);
			$count=count($moduleParams2);
			$Eintrag=$count;
			$switch=false;		// marker wenn kein ON oder OFF Befehl gesetzt wurde
			echo "   Kommando ".$Kommando." : Anzahl ".$count." Parameter erkannt in \"".$command."\" \n";

			if (strtoupper($params[1])=="VENTILATOR") 
				{ 
				echo "Es geht um Ventilatoren \n"; 
				// Dieser Befehl muss erkannt werden "Ventilator,25,true,24,false"
				switch ($count)
					{
					case "10":
					case "9":
					case "8":
					case "7":
					case "6": /* wenn Anzahl Parameter groesser 5 ist, gibt es keine Sonderlocken, einfach einlesen */
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
							/* wenn kein Doppelpunkt an Parameter Position 5 dann handelt es sich um den Ventilator Spezialbefehl, Aktivität bei Threshold */ 
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
							/* wenn kein Doppelpunkt an Parameter Position 4 dann handelt es sich um den Ventilator Spezialbefehl, Wert für Threshold */ 							
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
							/* hier sollte eigentlich immer "Ventilator" stehen */
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
			elseif (strtoupper($params[1])=="ANWESENHEIT")
				{	
				/* es gibt noch Sonderformen der Befehlsdarstellung, diese vorverarbeiten und damit standardisieren
				 *
				 * "Schaltername"
				 * "Schaltername,true"   "Schaltername,false"
				 * "Schaltername,true,20"
				 * "name:Schaltername,On:true,Off:false,Delay:20"
				 *
				 */
				switch ($count)
					{
					case "10":
					case "9":
					case "8":
					case "7":						
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
							/* wenn drei Parameter gibt der dritte vor wann wieder abgeschaltet werden soll */
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
							/* wenn zwei Parameter, gibt der zweite vor auf welchen Wert gesetzt werden soll */
							if ($status==false)
								{
								$parges[$Kommando][$Eintrag][]="OFF";
								$parges[$Kommando][$Eintrag][]=$params_two[0];
								}
							else	
								{
								$parges[$Kommando][$Eintrag][]="ON";
								$parges[$Kommando][$Eintrag][]=$params_two[0];
								}
							$switch=true;	
							$Eintrag--;								
							}
					case "1":
						$params_one=explode(":",$moduleParams2[0]);
						if (count($params_one)>1)
							{
							$result=self::parseParameter($params_one);
							//print_r($result);
							if ($result[0]=="ALARM")
								{
								echo "Alarm.\n";
								if ($this->CategoryId_SchalterAlarm !== false)
									{
									$parges[$Kommando][$Eintrag][]="OID";
									$parges[$Kommando][$Eintrag][]=$this->CategoryId_SchalterAlarm;
									if ( (strtoupper($result[1]) == "ON") || (strtoupper($result[1]) == "TRUE") )
										{
										$parges[$Kommando][$count+1][]="ON";
										$parges[$Kommando][$count+1][]="TRUE";
										}
									if ( (strtoupper($result[1]) == "OFF") || (strtoupper($result[1]) == "FALSE") )
										{
										$parges[$Kommando][$count+1][]="OFF";
										$parges[$Kommando][$count+1][]="FALSE";
										}
									}
								}	
							elseif ($result[0]=="ANWESEND")
								{
								echo "Anwesend.\n";								
								if ($this->CategoryId_SchalterAnwesend !== false)
									{
									$parges[$Kommando][$Eintrag][]="OID";
									$parges[$Kommando][$Eintrag][]=$this->CategoryId_SchalterAnwesend;
									if ( (strtoupper($result[1]) == "ON") || (strtoupper($result[1]) == "TRUE") )
										{
										$parges[$Kommando][$count+1][]="ON";
										$parges[$Kommando][$count+1][]="TRUE";
										}
									if ( (strtoupper($result[1]) == "OFF") || (strtoupper($result[1]) == "FALSE") )
										{
										$parges[$Kommando][$count+1][]="OFF";
										$parges[$Kommando][$count+1][]="FALSE";
										}
									}
								}
							else
								{		
								$parges[$Kommando][$Eintrag]=$result;
								}
							//echo "Anwesend   ".$this->CategoryId_SchalterAnwesend."   Alarm : ".$this->CategoryId_SchalterAlarm."\n"; 	
							//print_r($parges[$Kommando]);	
							$Eintrag--;							
							}
						else
							{
							/* nur ein Parameter, muss der Name des Schalters/Gruppe sein */
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
				/* es gibt noch Sonderformen der Befehlsdarstellung, diese vorverarbeiten und damit standardisieren
				 *
				 * "Schaltername"
				 * "Schaltername,true"   "Schaltername,false"
				 * "Schaltername,true,20"
				 * "name:Schaltername,On:true,Off:false,Delay:20"
				 *
				 */
				switch ($count)
					{
					case "10":
					case "9":
					case "8":
					case "7":						
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
							/* wenn drei Parameter gibt der dritte vor wann wieder abgeschaltet werden soll */
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
							/* wenn zwei Parameter, gibt der zweite vor auf welchen Wert gesetzt werden soll */
							if ($status==false)
								{
								$parges[$Kommando][$Eintrag][]="OFF";
								$parges[$Kommando][$Eintrag][]=$params_two[0];
								}
							else	
								{
								$parges[$Kommando][$Eintrag][]="ON";
								$parges[$Kommando][$Eintrag][]=$params_two[0];
								}
							$switch=true;	
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
							/* nur ein Parameter, muss der Name des Schalters/Gruppe sein */
							if ( strlen($params_one[0]) > 0 )
								{
								$parges[$Kommando][$Eintrag][]="NAME";
								$parges[$Kommando][$Eintrag][]=$params_one[0];
								$Eintrag--;
								}
							else
								{
								echo "                >>>Länge Name ist ".strlen($params_one[0])." Kommando ".$Kommando." wird nicht angelegt.\n";
								//print_r($parges);
								}																		
							}
						break;
					default:
						echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
						break;
					}
				}
			if ( isset ($parges[$Kommando]) )
				{
				foreach ($parges[$Kommando] as $command)
					{
					//echo $command[0]."   ";
					if ($command[0]=="ON") 				{ $switch=true; }	
					if ($command[0]=="OFF") 			{ $switch=true; }
					if ($command[0]=="ON#COLOR") 		{ $switch=true; }
					if ($command[0]=="OFF#COLOR") 	{ $switch=true; }
					if ($command[0]=="ON#LEVEL") 		{ $switch=true; }
					if ($command[0]=="OFF#LEVEL") 	{ $switch=true; }
					}								
				if ($switch == false )
					{
					$count++;
					if ($status==false)
						{
						$parges[$Kommando][$count][]="OFF";
						$parges[$Kommando][$count][]="false";	// wird im nächsten Schritt umgewandelt, hier wird eine Eingabe simuliert
						}
					else	
						{
						$parges[$Kommando][$count][]="ON";
						$parges[$Kommando][$count][]="true";	// wird im nächsten Schritt umgewandelt, hier wird eine Eingabe simuliert
						}
					}				
				$parges[$Kommando][0][]="SOURCE";		/* Die Quelle des Befehls dokumentieren */
				$parges[$Kommando][0][]=$params[0];		/* OnUpdate oder OnChange  */
				$parges[$Kommando][0][]=$status;			/* der aktuelle Wert des auslösenden Objektes, default false */
				ksort($parges[$Kommando]);
				}
			}		/* foreach alle Strichpunkt Befehle durchgehen. Leere befgehle werden uebersprungen */
		/* parges in richtige Reihenfolge bringen , NAME muss an den Anfang, es können auch Sortierinfos an den Anfang gepackt werden */
		if ($simulate==true) 
			{
			echo "Ergebnisse ParseCommand : ".json_encode($parges)."\n";
			//print_r($parges);
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
			$result[]=strtoupper($params[0]);
			$result[]=$params[1];
			}
		return($result);
		}

	/* 
	 * Wert nicht bekannt, true, false und toggle wurden bereits ausselektiert, entweder ein farbezeichnung oder eine Zahl, 
	 * abhängig ob Level oder Color angegeben wurde handelt es sich wahrscheinlich um eine hex beziehungsweise dez Zahl 
	 *
	 */

	private function parseValue($input,$result=array())
		{
		$extcolor=false;
		if (isset ($result["NAME_EXT"]) == true)
			{
			if ( $result["NAME_EXT"] == "#COLOR" )
				{
				$extcolor=true;
				$result=self::getColor($input);
				if ($result==false)
					{
					/* Color ist eine Hex Zahl */
					$value=hexdec($input);
					}
				else
					{
					$value=($result["red"]*128+$result["green"])*128+$result["blue"];
					}	
				//echo "Hexdec Umwandlung : ".$value."   ".dechex($value)."\n";
				}
			}	
		if ($extcolor == false)
			{
			$value=(integer)$input;
			}	
		return($value);
		}

	/*********************************************************************************************************
	 *
	 * Auch das Evaluieren kann gemeinsam erfolgen, es gibt nur kleine Unterschiede zwischen den Befehlen 
	 *
	 * beim Evaluieren wird auch der Wert bevor er geändert wird als VALUE, VALUE#LEVEL, VALUE#COLOR erfasst
	 *
	 * SOURCE, OID, NAME, ON#COLOR, ON#LEVEL, ON, OFF#COLOR, OFF#LEVEL, OFF, DELAY, DIM, DIM#LEVEL, DIM#TIME, 
	 * ENVELOPE, LEVEL, SPEAK, MONITOR, MUTE, IF, IFNOT
	 *
	 * IF: oder IFNOT:<parameter>     DARK, LIGHT, SLEEP, WAKEUP, AWAKE, ON, OFF oder einen Variablenwert 
	 *			DARK,LIGHT sind vom Sonneauf/unteragng abhängig
	 *			SLEEP, WAKEUP, AWAKE sind vom GutenMorgenWecker abhängig
	 *             Auswertung Befehl beeinflusst Parameter result.switch, der das Schalten und Sprechen aktiviert oder deaktiviert
	 *
	 * Befehl ON und OFF haben ähnliche Funktion, aber es ist keine IF Funktion sondern entscheidet wie die Variable geschaltet werden soll
	 *   Schaltbefehl Ausführung nur wenn bei "OnChange" die Triggervariable den Status true (ON) oder false (OFF) hat, speak funktioniert weiterhin.
	 *   ON:true, nur ausführen wenn Trigger Variable den Status true hat (nicht zu verwechseln mit der zu schaltenden Variable !!!) oder OnUpdate als Triggerfunktion
	 *            setzt result[ON] auf in diesem Fall true, es gibt auch false oder toggle
	 * 
	 * ON#LEVEL:50			zB Dimmer auf 50 stellen
	 * DELAY:Sekunden		nach Sekunden wird das entsprechende IPS-Light Objekt ausgeschaltet, oder der DIM#LEVEL erreicht
	 * DIM#LEVEL:0			DIM Zielwert, der in der Zeit von Delay erreicht wird, ON#LEVEL bestimmt den Ausgangswert
	 * 
	 *
	 *
	 * Es werden der Reihe nach die folgenden Befehle abgearbeitet
	 *		ParseCommand()
	 *		setzen von STATUS (Wert vom Trigger) und SWITCH (true) auf Defaultwerte
	 *      EvaluateCommand()
	 *      ExecuteCommand() mit switch() liefert als Ergebnis die Parameter für Dim und Delay.
	 *......Abarbeitung von Delay Befehl
	 ************************************/

	function EvaluateCommand($befehl,$result=array(),$simulate=false)
		{
		//echo "       EvaluateCommand: Befehl ".$befehl[0]." ".$befehl[1]." abarbeiten.\n";
		
		switch (strtoupper($befehl[0]))
			{
			case "SOURCE":
				$result["SOURCE"]=strtoupper($befehl[1]);
				break;
			case "OID":			/* wie switch name nur statt IPSLight die OID */
				$result["OID"]=$befehl[1];
				break;
			case "NAME":		/* IPSLight identifier */
				$result["NAME"]=$befehl[1];
				$resultID=@IPS_GetVariableIDByName($result["NAME"],$this->switchCategoryId);
				if ($resultID === false) 
					{
					echo "      ".$result["NAME"]." ist kein IPSLight Switch.\n";
					$switchId = $this->lightManager->GetGroupIdByName($result["NAME"]);
					$result["VALUE"]=$this->lightManager->GetValue($switchId);					
					}
				else
					{	
					$switchId = $this->lightManager->GetSwitchIdByName($result["NAME"]);
					$result["VALUE"]=$this->lightManager->GetValue($switchId);
					}
				break;
			case "ON#COLOR":
			case "ON#LEVEL":
				$result["NAME_EXT"]=strtoupper(substr($befehl[0],strpos($befehl[0],"#"),10));
				$name_ext="#".ucfirst(strtolower(strtoupper(substr($befehl[0],strpos($befehl[0],"#")+1,10))));
				$resultID=@IPS_GetVariableIDByName($result["NAME"].$name_ext,$this->switchCategoryId);
				if ($resultID === false) 
					{
					echo "      ".$result["NAME"].$name_ext." ist kein IPSLight Switch.\n";
					$switchId = $this->lightManager->GetGroupIdByName($result["NAME"]);
				
					}
				else
					{	
					$switchId = $this->lightManager->GetSwitchIdByName($result["NAME"].$name_ext);
					$result["VALUE".$result["NAME_EXT"]]=$this->lightManager->GetValue($switchId);
					//echo "   Befehl ON#LEVEL, Wert OID von ".$result["NAME"].$name_ext." : ".$resultID." mit Wert bisher : ".$result["VALUE".$result["NAME_EXT"]]." \n";	
					}						
			case "ON":
				if ( ($result["STATUS"] !== false) || ($result["SOURCE"] == "ONUPDATE") )   
					{
					/* nimmt den Wert des auslösenden Ereignisses, ON nur wenn Wert des Ereignis true ausführen*/
					$value_on=strtoupper($befehl[1]);
					$i=2;
					while ($i<count($befehl))
						{
						if (strtoupper($befehl[$i])=="MASK")
							{
							$mask_on=hexdec($befehl[$i++]);
							//$notmask_on=~($mask_on)&0xFFFFFF;						
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
							if ($result["VALUE"] == false)
								{
								$result["ON"]="TRUE";
								}
							else
								{
								$result["ON"]="FALSE";
								}
							break;
						default:
							$result["ON"]="TRUE";
							$result["VALUE_ON"]=self::parseValue($befehl[1],$result);
							break;
						}		
					}
					
				break;
			case "OFF#COLOR":
			case "OFF#LEVEL":
				$result["NAME_EXT"]=strtoupper(substr($befehl[0],strpos("#",$befehl[0]),10));
			case "OFF":
				if ( ($result["STATUS"] == false) || ($result["SOURCE"] == "ONUPDATE") )
					{			
					$value_off=strtoupper($befehl[1]);
					$i=2;
					while ($i<count($befehl))
						{
						if (strtoupper($befehl[$i])=="MASK")
							{
							$mask_off=hexdec($befehl[$i++]);
							//$notmask_off=~($mask_off)&0xFFFFFF;						
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
							if ($result["VALUE"] == false)
								{
								$result["OFF"]="TRUE";
								}
							else
								{
								$result["OFF"]="FALSE";
								}
							break;
						default:
							/* Befehl nicht bekannt, wahrscheinlich eine Hex Zahl */
							$result["OFF"]="TRUE";
							$result["VALUE_OFF"]=self::parseValue($befehl[1],$result);					
							break;
						}
					}								
				break;
			case "DELAY":
				$result["DELAY"]=(integer)$befehl[1];
				break;
			case "DIM":
			case "DIM#LEVEL":			
				$result["DIM"]=(integer)$befehl[1];
				$result["DIM#LEVEL"]=$result["DIM"];
				break;				
			case "DIM#TIME":			
				$result["DIM#TIME"]=(integer)$befehl[1];
				break;				
			case "ENVELOPE":
				$result["ENVEL"]=(integer)$befehl[1];
				break;
			case "LEVEL":
				$result["LEVEL"]=(integer)$befehl[1];
				break;
			case "SPEAK":
				$result["SPEAK"]=$befehl[1];
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
				switch ($cond)
					{
					case "ON":
						/* nur Schalten wenn Statusvariable true ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
						if ($result["STATUS"] == false)
							{
							$result["SWITCH"]=false;						
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist false ');
							}
						break;	
					case "OFF":
						/* nur Schalten wenn Statusvariable false ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
						if ($result["STATUS"] !== false)
							{
							$result["SWITCH"]=false;						
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist false ');
							}
						break;										
					case "LIGHT":
						/* nur Schalten wenn es hell ist, geschaltet wird nur wenn ein variablenname bekannt ist */
						if (self::isitdark())
							{
							$result["SWITCH"]=false;						
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist dunkel ');
							}
						break;
					case "DARK":
						/* nur Schalten wenn es dunkel ist, geschaltet wird nur wenn ein variablenname bekannt ist */
						if (self::isitlight())
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist hell ');
							}
						break;	
					case "SLEEP":
						/* nur Schalten wenn wir nicht schlafen */
						if ( self::isitwakeup() || self::isitawake() )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind munter oder im aufwachen');
							}
						break;
					case "AWAKE":
						/* nur Schalten wenn wir nicht munter sind */
						if ( self::isitwakeup() || self::isitsleep() )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind munter ');
							}
						break;
					case "WAKEUP":
						/* nur Schalten wenn wir nicht im aufwachen sind */
						if ( self::isitawake() || self::isitsleep() )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind munter ');
							}
						break;
					case "HOME":
						/* nur Schalten wenn wir zuhause sind */
						if ( self::isithome() == false )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind nicht zu Hause ');
							}
						break;
					case "ALARM":
						/* nur Schalten wenn Alarmanlage aktiv */
						if ( self::isitalarm() == false )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Alarmanlage deaktiviert ');
							}
						break;

					default:
					  	/* weder light noch dark, wird ein IPSLight Variablenname sein. Wert ermitteln */
						$checkId = $this->lightManager->GetSwitchIdByName($befehl[1]);		/* Light Manager ist context sensitive */
						$statusCheck=$this->lightManager->GetValue($checkId);
						$result["SWITCH"]=$statusCheck;
						echo "Auswertung IF:".$befehl[1]." Wert ist ".$statusCheck." VariableID ist ".$checkId." (".IPS_GetName(IPS_GetParent($checkId))."/".IPS_GetName($checkId).")\n";
						break;	
					}			
				break;
			case "IFNOT":     /* parges hat nur die Parameter übermittelt, hier die Auswertung machen. Es gibt zumindest light, dark und einen IPS Light Variablenname (wird zum Beispiel für die Heizungs Follow me Funktion verwendet) */
				$cond=strtoupper($befehl[1]);
				$result["COND"]=$cond;
				switch ($cond)
					{
					case "ON":
						/* nur Schalten wenn Statusvariable false ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
						if ($result["STATUS"] !== false)
							{
							$result["SWITCH"]=false;						
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist false ');
							}
						break;	
					case "OFF":
						/* nur Schalten wenn Statusvariable true ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
						if ($result["STATUS"] == false)
							{
							$result["SWITCH"]=false;						
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist false ');
							}
						break;					
					case "LIGHT":				
						/* Nicht Schalten wenn es hell ist, geschaltet wird nur wenn ein variablenname bekannt ist */
						if ( self::isitlight( ))
							{
							$result["SWITCH"]=false;						
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist nicht dunkel ');
							}
						break;	
					case "DARK":							
						/* Nicht Schalten wenn es dunkel ist, geschaltet wird nur wenn ein variablenname bekannt ist */
						if ( self::isitdark() )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist nicht hell ');
							}
						break;
					case "SLEEP":
						/* Nicht Schalten wenn wir schlafen */
						if ( self::isitsleep() )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind munter oder im aufwachen');
							}
						break;
					case "AWAKE":
						/* Nicht Schalten wenn wir munter sind */
						if ( self::isitawake() )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind munter ');
							}
						break;
					case "WAKEUP":
						/* Nicht Schalten wenn wir im aufwachen sind */
						if ( self::isitwakeup() )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind munter ');
							}
						break;
					case "HOME":
						/* Nicht Schalten wenn wir zuhause sind */
						if ( self::isithome() == true )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind nicht zu Hause ');
							}
						break;
					case "ALARM":
						/* Nicht Schalten wenn Alarmanlage aktiv */
						if ( self::isitalarm() == true )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Alarmanlage deaktiviert ');
							}						
					default:
					  	/* weder light noch dark, wird ein IPSLight Variablenname sein. Wert ermitteln */
						$checkId = $this->lightManager->GetSwitchIdByName($befehl[1]);		/* Light Manager ist context sensitive */
						$statusCheck=$this->lightManager->GetValue($checkId);
						$result["SWITCH"]=!$statusCheck;
						echo "Auswertung IF:".$befehl[1]." Wert ist ".$statusCheck." VariableID ist ".$checkId." (".IPS_GetName(IPS_GetParent($checkId))."/".IPS_GetName($checkId).")\n";	
						break;
					}			
				break;
			default:
				echo "Function EvaluateCommand, Befehl unbekannt: ".$befehl[0]." ".$befehl[1]."   \n";
				break;				
			}  /* ende switch */
		return ($result);
		}	


	/***************************************
	 *
	 * hier wird der Befehl umgesetzt:
	 *    benötigt IPSLight, Schalten ist eine eigene Funktion - erfolgt abhängig von if Auswertung
	 *    geht aber auch mit einem OID Wert
	 *
	 * IPSLight funktioniert auch für Gruppen und Programme, bei Switch ist auch Level und Color möglich 
	 * Die Unterscheidung Switch, Group oder program wird automatisch getroffen
	 *
	 * Als Ergebnis dieser Auswertung wird ein zusaetzlicher Parameter IPSLIGHT ermittelt:  None, Program, Group,  
	 *
	 * Value für Wert nach Delay ist hier falsch !!!! wird nicht übergeben sondern geraten
	 * false, Next oder Value ???? ist nicht richtig
	 *
	 *******************************************************/

	function ExecuteCommand($result,$simulate=false)
		{
		$command="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
		IPSLogger_Dbg(__file__, 'Function ExecuteCommand Aufruf mit Wert: '.json_encode($result));

		if (isset($result["OID"]) == true)
			{
			IPSLogger_Dbg(__file__, 'OID '.$result["OID"]);
			$result["IPSLIGHT"]="None";
			$command.="SetValue(".$result["OID"].",false);";
			$result["COMMAND"]=$command;
			self::switchObject($result,$simulate);					
			}
		elseif ( isset($result["NAME"])==true )
			{	/* wenn nicht die OID, dann ist der Name bekannt */
			if ( isset($result["NAME_EXT"])==true ) 
				{ 
				if ($result["NAME_EXT"]=="#COLOR") { $name=$result["NAME"]."#Color"; }
				if ($result["NAME_EXT"]=="#LEVEL") { $name=$result["NAME"]."#Level"; }
				}
			else 
				{
				$name=$result["NAME"];
				}			
			$resultID=@IPS_GetVariableIDByName($name,$this->switchCategoryId);
			if ($resultID==false)
				{
				$resultID=@IPS_GetVariableIDByName($name,$this->groupCategoryId);
				if ($resultID==false)
					{
					$resultID=@IPS_GetVariableIDByName($name,$this->prgCategoryId);
					if ($resultID==false)
						{
						/* Name nicht bekannt */
						$result["IPSLIGHT"]="None";
						}
					else /* Wert ist ein Programm */
						{
						IPSLogger_Dbg(__file__, 'Wert '.$name.' ist ein Programm. ');
						$command.="IPSLight_SetProgramNextByName(\"".$name."\");\n";
						$result["COMMAND"]=$command;
						$result["IPSLIGHT"]="Program";
						if ($simulate==false)
							{
							IPSLight_SetProgramNextByName($name);
							}
						}
					}
				else   /* Wert ist eine Gruppe */
					{
					IPSLogger_Dbg(__file__, 'Wert '.$name.' ist eine Gruppe. ');
					$command.="IPSLight_SetGroupByName(\"".$name."\", false);\n";
					$result["COMMAND"]=$command;
					$result["IPSLIGHT"]="Group";	
					self::switchObject($result,$simulate);			   	 	
					}
				}
			else     /* Wert ist ein Schalter oder Wert eines Schalters */
				{
				if (isset($result["NAME_EXT"])==true)
					{
					IPSLogger_Dbg(__file__, 'Wert '.$name.' ist Wert für einen Schalter. ');
					$result["IPSLIGHT"]=$result["NAME_EXT"];						
					$result["OID"] = $this->lightManager->GetSwitchIdByName($name);					
					$value=GetValue($result["OID"]);
					if ($result["IPSLIGHT"]=="#COLOR") 	{	$command.='$lightManager->SetRGB('.$result["OID"].",".$value.");"; }	
					if ($result["IPSLIGHT"]=="#LEVEL") 	{	$command.='$lightManager->SetValue('.$result["OID"].",".$value.");"; }	
					if ($result["IPSLIGHT"]=="None") 	{	$command.='SetValue('.$result["OID"].",".$value.");"; }	
					$command.="IPSLogger_Dbg(__file__, 'Delay abgelaufen von ".$name."');";
					$result["COMMAND"]=$command;
					self::switchObject($result,$simulate);	
					//echo "**** Aufruf Switch Ergebnis command \"".$result["IPSLIGHT"]."\"   ".str_replace("\n","",$command)."\n";										
					}
				else
					{	 				
					IPSLogger_Dbg(__file__, 'Wert '.$name.' ist ein Schalter. ');
					$command.="IPSLight_SetSwitchByName(\"".$name."\", false);\n";
					$result["COMMAND"]=$command;
					$result["IPSLIGHT"]="Switch";
					self::switchObject($result,$simulate);
					}			
				}   /* Ende Wert ist ein Schalter */
			} /* Ende entweder NAME oder OID ist gesetzt */
		else
			{
			}

		if (isset($result["SPEAK"]) == true)
			{
			if ($result["SWITCH"]===true)			/* nicht nur die Schaltbefehle mit If Beeinflussen, auch die Sprachausgabe */
				{
				if ( (self::isitsleep() == false) || (self::getFunctions("SilentMode")["VALUE"] == 0) )
					{
					echo "Es wird gesprochen : ".$result["SPEAK"]."\n";
					if ($simulate==false)
						{													
						tts_play(1,$result["SPEAK"],'',2);
						}
					}	
				}
			}
		return ($result);
		}

	/***************************************
	 *
	 * hier wird geschaltet 
	 *
	 * abhängig von IF Befehlsauswertung, also nur wenn SWITCH true ist
	 *
	 * Wenn ON oder OFF gesetzt ist:
	 *   ON/OFF auf false: Switch, Group oder Value auf false setzen
	 *   ON/OFF auf true:  Switch, Group oder Value auf true setzen
	 *
	 *
	 *******************************************************/
	 
	private function switchObject($result,$simulate=false)
		{
		IPSLogger_Dbg(__file__, 'SwitchObject :  '.json_encode($result));	
		if ($simulate==false)			/* Bei simulate nicht schalten */
			{
			if ($result["SWITCH"]===true)
				{
				if (isset($result["ON"])==true)
					{
					$result["ON"]=strtoupper($result["ON"]);
					if ($result["ON"]=="FALSE")
						{
						if ($result["IPSLIGHT"]=="Group")  	{	IPSLight_SetGroupByName($result["NAME"],false);  }
						if ($result["IPSLIGHT"]=="Switch") 	{	IPSLight_SetSwitchByName($result["NAME"],false); }
						if ($result["IPSLIGHT"]=="None") 	{	SetValue($result["OID"],false); }												
						}
					if ($result["ON"]=="TRUE")
						{
						if ($result["IPSLIGHT"]=="Group")  	{	IPSLight_SetGroupByName($result["NAME"],true); }
						if ($result["IPSLIGHT"]=="Switch")	{	IPSLight_SetSwitchByName($result["NAME"],true); } 
						if ($result["IPSLIGHT"]=="#COLOR") 	{	$this->lightManager->SetRGB($result["OID"], $result["VALUE_ON"]); }	
						if ($result["IPSLIGHT"]=="#LEVEL") 	{	$this->lightManager->SetValue($result["OID"], $result["VALUE_ON"]); }	
						if ($result["IPSLIGHT"]=="None") 	{	SetValue($result["OID"],true); }													
						}
					}
				if (isset($result["OFF"])==true)
					{
					$result["OFF"]=strtoupper($result["OFF"]);					
					if ($result["OFF"]=="FALSE")
						{
						if ($result["IPSLIGHT"]=="Group") 	{	IPSLight_SetGroupByName($result["NAME"],false); }
						if ($result["IPSLIGHT"]=="Switch") 	{	IPSLight_SetSwitchByName($result["NAME"],false); }
						if ($result["IPSLIGHT"]=="None") 	{	SetValue($result["OID"],false); }														
						}
					if ($result["OFF"]=="TRUE")
						{
						if ($result["IPSLIGHT"]=="Group") 	{	IPSLight_SetGroupByName($result["NAME"],true); }
						if ($result["IPSLIGHT"]=="Switch") 	{	IPSLight_SetSwitchByName($result["NAME"],true); }
						if ($result["IPSLIGHT"]=="#COLOR") 	{	$this->lightManager->SetRGB($result["OID"], $result["VALUE_OFF"]); }
						if ($result["IPSLIGHT"]=="#LEVEL") 	{	$this->lightManager->SetValue($result["OID"], $result["VALUE_ON"]); }						
						if ($result["IPSLIGHT"]=="None") 	{	SetValue($result["OID"],false); }															
						}
					}
				}
			}
		else
			{
			echo "Simulation aktiviert, erhaltener Befehl für switchObject war : ".json_encode($result)."\n";
			}	
		return($result);		
		}

	/*************************************************************************/
	/* bereits obsolet, da nur für IPSLight funktioniert */
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

	function GetColor($Colorname) 
		{
		$Colorname=strtolower($Colorname);
		$Colors  =  ARRAY( 
 
//  Colors  as  they  are  defined  in  HTML  3.2 
            "black"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0x00), 
            "maroon"=>array( "red"=>0x80,  "green"=>0x00,  "blue"=>0x00), 
            "green"=>array( "red"=>0x00,  "green"=>0x80,  "blue"=>0x00), 
            "olive"=>array( "red"=>0x80,  "green"=>0x80,  "blue"=>0x00), 
            "navy"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0x80), 
            "purple"=>array( "red"=>0x80,  "green"=>0x00,  "blue"=>0x80), 
            "teal"=>array( "red"=>0x00,  "green"=>0x80,  "blue"=>0x80), 
            "gray"=>array( "red"=>0x80,  "green"=>0x80,  "blue"=>0x80), 
            "silver"=>array( "red"=>0xC0,  "green"=>0xC0,  "blue"=>0xC0), 
            "red"=>array( "red"=>0xFF,  "green"=>0x00,  "blue"=>0x00), 
            "lime"=>array( "red"=>0x00,  "green"=>0xFF,  "blue"=>0x00), 
            "yellow"=>array( "red"=>0xFF,  "green"=>0xFF,  "blue"=>0x00), 
            "blue"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0xFF), 
            "fuchsia"=>array( "red"=>0xFF,  "green"=>0x00,  "blue"=>0xFF), 
            "aqua"=>array( "red"=>0x00,  "green"=>0xFF,  "blue"=>0xFF), 
            "white"=>array( "red"=>0xFF,  "green"=>0xFF,  "blue"=>0xFF), 
 
//  Additional  colors  as  they  are  used  by  Netscape  and  IE 
            "aliceblue"=>array( "red"=>0xF0,  "green"=>0xF8,  "blue"=>0xFF), 
            "antiquewhite"=>array( "red"=>0xFA,  "green"=>0xEB,  "blue"=>0xD7), 
            "aquamarine"=>array( "red"=>0x7F,  "green"=>0xFF,  "blue"=>0xD4), 
            "azure"=>array( "red"=>0xF0,  "green"=>0xFF,  "blue"=>0xFF), 
            "beige"=>array( "red"=>0xF5,  "green"=>0xF5,  "blue"=>0xDC), 
            "blueviolet"=>array( "red"=>0x8A,  "green"=>0x2B,  "blue"=>0xE2), 
            "brown"=>array( "red"=>0xA5,  "green"=>0x2A,  "blue"=>0x2A), 
            "burlywood"=>array( "red"=>0xDE,  "green"=>0xB8,  "blue"=>0x87), 
            "cadetblue"=>array( "red"=>0x5F,  "green"=>0x9E,  "blue"=>0xA0), 
            "chartreuse"=>array( "red"=>0x7F,  "green"=>0xFF,  "blue"=>0x00), 
            "chocolate"=>array( "red"=>0xD2,  "green"=>0x69,  "blue"=>0x1E), 
            "coral"=>array( "red"=>0xFF,  "green"=>0x7F,  "blue"=>0x50), 
            "cornflowerblue"=>array( "red"=>0x64,  "green"=>0x95,  "blue"=>0xED), 
            "cornsilk"=>array( "red"=>0xFF,  "green"=>0xF8,  "blue"=>0xDC), 
            "crimson"=>array( "red"=>0xDC,  "green"=>0x14,  "blue"=>0x3C), 
            "darkblue"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0x8B), 
            "darkcyan"=>array( "red"=>0x00,  "green"=>0x8B,  "blue"=>0x8B), 
            "darkgoldenrod"=>array( "red"=>0xB8,  "green"=>0x86,  "blue"=>0x0B), 
            "darkgray"=>array( "red"=>0xA9,  "green"=>0xA9,  "blue"=>0xA9), 
            "darkgreen"=>array( "red"=>0x00,  "green"=>0x64,  "blue"=>0x00), 
            "darkkhaki"=>array( "red"=>0xBD,  "green"=>0xB7,  "blue"=>0x6B), 
            "darkmagenta"=>array( "red"=>0x8B,  "green"=>0x00,  "blue"=>0x8B), 
            "darkolivegreen"=>array( "red"=>0x55,  "green"=>0x6B,  "blue"=>0x2F), 
            "darkorange"=>array( "red"=>0xFF,  "green"=>0x8C,  "blue"=>0x00), 
            "darkorchid"=>array( "red"=>0x99,  "green"=>0x32,  "blue"=>0xCC), 
            "darkred"=>array( "red"=>0x8B,  "green"=>0x00,  "blue"=>0x00), 
            "darksalmon"=>array( "red"=>0xE9,  "green"=>0x96,  "blue"=>0x7A), 
            "darkseagreen"=>array( "red"=>0x8F,  "green"=>0xBC,  "blue"=>0x8F), 
            "darkslateblue"=>array( "red"=>0x48,  "green"=>0x3D,  "blue"=>0x8B), 
            "darkslategray"=>array( "red"=>0x2F,  "green"=>0x4F,  "blue"=>0x4F), 
            "darkturquoise"=>array( "red"=>0x00,  "green"=>0xCE,  "blue"=>0xD1), 
            "darkviolet"=>array( "red"=>0x94,  "green"=>0x00,  "blue"=>0xD3), 
            "deeppink"=>array( "red"=>0xFF,  "green"=>0x14,  "blue"=>0x93), 
            "deepskyblue"=>array( "red"=>0x00,  "green"=>0xBF,  "blue"=>0xFF), 
            "dimgray"=>array( "red"=>0x69,  "green"=>0x69,  "blue"=>0x69), 
            "dodgerblue"=>array( "red"=>0x1E,  "green"=>0x90,  "blue"=>0xFF), 
            "firebrick"=>array( "red"=>0xB2,  "green"=>0x22,  "blue"=>0x22), 
            "floralwhite"=>array( "red"=>0xFF,  "green"=>0xFA,  "blue"=>0xF0), 
            "forestgreen"=>array( "red"=>0x22,  "green"=>0x8B,  "blue"=>0x22), 
            "gainsboro"=>array( "red"=>0xDC,  "green"=>0xDC,  "blue"=>0xDC), 
            "ghostwhite"=>array( "red"=>0xF8,  "green"=>0xF8,  "blue"=>0xFF), 
            "gold"=>array( "red"=>0xFF,  "green"=>0xD7,  "blue"=>0x00), 
            "goldenrod"=>array( "red"=>0xDA,  "green"=>0xA5,  "blue"=>0x20), 
            "greenyellow"=>array( "red"=>0xAD,  "green"=>0xFF,  "blue"=>0x2F), 
            "honeydew"=>array( "red"=>0xF0,  "green"=>0xFF,  "blue"=>0xF0), 
            "hotpink"=>array( "red"=>0xFF,  "green"=>0x69,  "blue"=>0xB4), 
            "indianred"=>array( "red"=>0xCD,  "green"=>0x5C,  "blue"=>0x5C), 
            "indigo"=>array( "red"=>0x4B,  "green"=>0x00,  "blue"=>0x82), 
            "ivory"=>array( "red"=>0xFF,  "green"=>0xFF,  "blue"=>0xF0), 
            "khaki"=>array( "red"=>0xF0,  "green"=>0xE6,  "blue"=>0x8C), 
            "lavender"=>array( "red"=>0xE6,  "green"=>0xE6,  "blue"=>0xFA), 
            "lavenderblush"=>array( "red"=>0xFF,  "green"=>0xF0,  "blue"=>0xF5), 
            "lawngreen"=>array( "red"=>0x7C,  "green"=>0xFC,  "blue"=>0x00), 
            "lemonchiffon"=>array( "red"=>0xFF,  "green"=>0xFA,  "blue"=>0xCD), 
            "lightblue"=>array( "red"=>0xAD,  "green"=>0xD8,  "blue"=>0xE6), 
            "lightcoral"=>array( "red"=>0xF0,  "green"=>0x80,  "blue"=>0x80), 
            "lightcyan"=>array( "red"=>0xE0,  "green"=>0xFF,  "blue"=>0xFF), 
            "lightgoldenrodyellow"=>array( "red"=>0xFA,  "green"=>0xFA,  "blue"=>0xD2), 
            "lightgreen"=>array( "red"=>0x90,  "green"=>0xEE,  "blue"=>0x90), 
            "lightgrey"=>array( "red"=>0xD3,  "green"=>0xD3,  "blue"=>0xD3), 
            "lightpink"=>array( "red"=>0xFF,  "green"=>0xB6,  "blue"=>0xC1), 
            "lightsalmon"=>array( "red"=>0xFF,  "green"=>0xA0,  "blue"=>0x7A), 
            "lightseagreen"=>array( "red"=>0x20,  "green"=>0xB2,  "blue"=>0xAA), 
            "lightskyblue"=>array( "red"=>0x87,  "green"=>0xCE,  "blue"=>0xFA), 
            "lightslategray"=>array( "red"=>0x77,  "green"=>0x88,  "blue"=>0x99), 
            "lightsteelblue"=>array( "red"=>0xB0,  "green"=>0xC4,  "blue"=>0xDE), 
            "lightyellow"=>array( "red"=>0xFF,  "green"=>0xFF,  "blue"=>0xE0), 
            "limegreen"=>array( "red"=>0x32,  "green"=>0xCD,  "blue"=>0x32), 
            "linen"=>array( "red"=>0xFA,  "green"=>0xF0,  "blue"=>0xE6), 
            "mediumaquamarine"=>array( "red"=>0x66,  "green"=>0xCD,  "blue"=>0xAA), 
            "mediumblue"=>array( "red"=>0x00,  "green"=>0x00,  "blue"=>0xCD), 
            "mediumorchid"=>array( "red"=>0xBA,  "green"=>0x55,  "blue"=>0xD3), 
            "mediumpurple"=>array( "red"=>0x93,  "green"=>0x70,  "blue"=>0xD0), 
            "mediumseagreen"=>array( "red"=>0x3C,  "green"=>0xB3,  "blue"=>0x71), 
            "mediumslateblue"=>array( "red"=>0x7B,  "green"=>0x68,  "blue"=>0xEE), 
            "mediumspringgreen"=>array( "red"=>0x00,  "green"=>0xFA,  "blue"=>0x9A), 
            "mediumturquoise"=>array( "red"=>0x48,  "green"=>0xD1,  "blue"=>0xCC), 
            "mediumvioletred"=>array( "red"=>0xC7,  "green"=>0x15,  "blue"=>0x85), 
            "midnightblue"=>array( "red"=>0x19,  "green"=>0x19,  "blue"=>0x70), 
            "mintcream"=>array( "red"=>0xF5,  "green"=>0xFF,  "blue"=>0xFA), 
            "mistyrose"=>array( "red"=>0xFF,  "green"=>0xE4,  "blue"=>0xE1), 
            "moccasin"=>array( "red"=>0xFF,  "green"=>0xE4,  "blue"=>0xB5), 
            "navajowhite"=>array( "red"=>0xFF,  "green"=>0xDE,  "blue"=>0xAD), 
            "oldlace"=>array( "red"=>0xFD,  "green"=>0xF5,  "blue"=>0xE6), 
            "olivedrab"=>array( "red"=>0x6B,  "green"=>0x8E,  "blue"=>0x23), 
            "orange"=>array( "red"=>0xFF,  "green"=>0xA5,  "blue"=>0x00), 
            "orangered"=>array( "red"=>0xFF,  "green"=>0x45,  "blue"=>0x00), 
            "orchid"=>array( "red"=>0xDA,  "green"=>0x70,  "blue"=>0xD6), 
            "palegoldenrod"=>array( "red"=>0xEE,  "green"=>0xE8,  "blue"=>0xAA), 
            "palegreen"=>array( "red"=>0x98,  "green"=>0xFB,  "blue"=>0x98), 
            "paleturquoise"=>array( "red"=>0xAF,  "green"=>0xEE,  "blue"=>0xEE), 
            "palevioletred"=>array( "red"=>0xDB,  "green"=>0x70,  "blue"=>0x93), 
            "papayawhip"=>array( "red"=>0xFF,  "green"=>0xEF,  "blue"=>0xD5), 
            "peachpuff"=>array( "red"=>0xFF,  "green"=>0xDA,  "blue"=>0xB9), 
            "peru"=>array( "red"=>0xCD,  "green"=>0x85,  "blue"=>0x3F), 
            "pink"=>array( "red"=>0xFF,  "green"=>0xC0,  "blue"=>0xCB), 
            "plum"=>array( "red"=>0xDD,  "green"=>0xA0,  "blue"=>0xDD), 
            "powderblue"=>array( "red"=>0xB0,  "green"=>0xE0,  "blue"=>0xE6), 
            "rosybrown"=>array( "red"=>0xBC,  "green"=>0x8F,  "blue"=>0x8F), 
            "royalblue"=>array( "red"=>0x41,  "green"=>0x69,  "blue"=>0xE1), 
            "saddlebrown"=>array( "red"=>0x8B,  "green"=>0x45,  "blue"=>0x13), 
            "salmon"=>array( "red"=>0xFA,  "green"=>0x80,  "blue"=>0x72), 
            "sandybrown"=>array( "red"=>0xF4,  "green"=>0xA4,  "blue"=>0x60), 
            "seagreen"=>array( "red"=>0x2E,  "green"=>0x8B,  "blue"=>0x57), 
            "seashell"=>array( "red"=>0xFF,  "green"=>0xF5,  "blue"=>0xEE), 
            "sienna"=>array( "red"=>0xA0,  "green"=>0x52,  "blue"=>0x2D), 
            "skyblue"=>array( "red"=>0x87,  "green"=>0xCE,  "blue"=>0xEB), 
            "slateblue"=>array( "red"=>0x6A,  "green"=>0x5A,  "blue"=>0xCD), 
            "slategray"=>array( "red"=>0x70,  "green"=>0x80,  "blue"=>0x90), 
            "snow"=>array( "red"=>0xFF,  "green"=>0xFA,  "blue"=>0xFA), 
            "springgreen"=>array( "red"=>0x00,  "green"=>0xFF,  "blue"=>0x7F), 
            "steelblue"=>array( "red"=>0x46,  "green"=>0x82,  "blue"=>0xB4), 
            "tan"=>array( "red"=>0xD2,  "green"=>0xB4,  "blue"=>0x8C), 
            "thistle"=>array( "red"=>0xD8,  "green"=>0xBF,  "blue"=>0xD8), 
            "tomato"=>array( "red"=>0xFF,  "green"=>0x63,  "blue"=>0x47), 
            "turquoise"=>array( "red"=>0x40,  "green"=>0xE0,  "blue"=>0xD0), 
            "violet"=>array( "red"=>0xEE,  "green"=>0x82,  "blue"=>0xEE), 
            "wheat"=>array( "red"=>0xF5,  "green"=>0xDE,  "blue"=>0xB3), 
            "whitesmoke"=>array( "red"=>0xF5,  "green"=>0xF5,  "blue"=>0xF5), 
            "yellowgreen"=>array( "red"=>0x9A,  "green"=>0xCD,  "blue"=>0x32)); 
 
        //  GetColor  returns  an  associative  array  with  the  red,  green  and  blue 
        //  values  of  the  desired  color 
 
		if ( isset( $Colors[$Colorname] ) == true ) {	return (  $Colors[$Colorname]); }
		else { return(false); } 
		}	
	

	} // Ende Klasse
									

?>