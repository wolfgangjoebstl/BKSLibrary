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

/*****************************************************************************************************
 *
 * Übersicht verwendete Klassen
 *   AutosteuerungHandler zum Anlegen der Konfigurationszeilen im config File
 *   AutosteuerungConfiguration, abstract class für:
 *       AutosteuerungConfigurationAlexa 
 *       AutosteuerungConfigurationHandler
 *   AutosteuerungOperator für Funktionen die zum Betrieb notwendig sind (zB Anwesenheitsberechnung) 
 *    Autosteuerung sind die Funktionen die für die Evaluierung der Befehle im Konfigfile notwendig sind. 
 *
 *
 *
 * Autosteuerung_class ist eine Sammlung aus unterschiedlichen Klassen:
 *
 *    AutosteuerungHandler zum Anlegen der Konfigurationszeilen im config File
 *      Get_EventConfigurationAuto()
 *      Set_EventConfigurationAuto
 *      CreateEvent
 *      StoreEventConfiguration
 *      RegisterAutoEvent
 *      UnRegisterAutoEvent
 *
 *    AutosteuerungConfiguration, abstract class für:
 *       AutosteuerungConfigurationAlexa 
 *       AutosteuerungConfigurationHandler 
 *
 *          mit folgenden abstract functions:
 *              Get_EventConfigurationAuto()
 *              Set_EventConfigurationAuto
 *              CreateEvent
 *              InitEventConfiguration()
 *              StoreEventConfiguration
 *              RegisterAutoEvent
 *              UnRegisterAutoEvent
 *              printAutoEvent
 *              getAutoEvent 
 * 
 *    AutosteuerungOperator für Funktionen die zum Betrieb notwendig sind (zB Anwesenheitsberechnung)
 *          Anwesend()
 *
 *    Autosteuerung class sind die Funktionen die für die Evaluierung der Befehle im Konfigfile notwendig sind.
 *       getFunctions
 *       isitdark(), isitlight(), isitsleep(), isitwakeup(), isitawake(), isithome(), isitmove(),isitalarm()
 *       isitheatday(),
 *       getDaylight, switchingTimes, timeright,
 *       setNewValue, setNewValueIfDif
 *       trimCommand
 *
 *       ParseCommand, parseName, parseParameter, parseValue,
 *
 *       EvaluateCommand, evalCom_IFDIF, evalCom_LEVEL, ControlSwitchLevel
 *
 *       ExecuteCommand, availableModuleDevice, switchObject, switch
 *
 *
 *    AutosteuerungFunktionen
 *       AutosteuerungRegler
 *       AutosteuerungAnwesenheitsSimulation
 *       AutosteuerungAlexa
 *    AutoSteuerungStromheizung für Funktionen rund um die Heizungssteuerung
 *
 * alleinstehende functions
 *
 *
 *  Ventilator, Ventilator1, Alexa, 
 *  test, parseParemeter, Parameter
 *
 **************************************************************************************************************/
 
 
/*****************************************************************************************************
 *
 * AutosteuerungHandler zum Anlegen der Konfigurationszeilen im config File
 *
 * werden mittlerweile groestenteils haendisch angelegt, es geht aber auch automatisch, zB für Standardvariablen
 *
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
 * Allgemeiner Handler (Abstract)  zum Anlegen der Konfigurationszeilen im config File
 *
 * werden mittlerweile groestenteils haendisch angelegt, es geht aber auch automatisch, zB für Standardvariablen
 * im construct wird der Messagehandler uebergeben
 * mit Create Event wird ein Event für den Messagehandler angelegt 
 * mit storeEventConfiguration wird das Config File wieder geschrieben
 *
 * Exkurs, Unterschied $this-> und self::
 * this referenziert auf die Instanz, bei static Variablen ist es die selbe Variable für alle Instanzen die mit self adressiert werden kann
 *
 *
 **************************************************************************************************************/ 

abstract class AutosteuerungConfiguration 
	{

		/**
		 * @public
		 *
		 * Initialisierung des IPSMessageHandlers
		 *
		 */
		public function __construct($scriptID) {
			$this->scriptID=$scriptID;
		}

		/**
		 * @private
		 *
		 * Liefert die aktuelle Auto Event Konfiguration
		 *
		 * @return string[] Event Konfiguration
		 */
		private function Get_EventConfigurationAuto() 
			{
			if ($this->eventConfigurationAuto == null) 
				{
				$func=$this->functionName;
				if (function_exists($func)===false)
					{
					echo ">>>>Fehler, Function ".$func."() nicht definiert im Configfile.\n";
					$this->InitEventConfiguration();
					$fatalerror=true;
					}
				$this->eventConfigurationAuto = $func();		/* >>>>>>>> change here */
				echo "Config Function heisst : ".$func."\n";	
				}
			return $this->eventConfigurationAuto;
			}
		
		/**
		 * @private
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 * @param string[] $configuration Neue Event Konfiguration
		 */
		private function Set_EventConfigurationAuto($configuration) {
		   $this->eventConfigurationAuto = $configuration;
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
			switch ($eventType) 
				{
				case 'OnChange':
					$triggerType = 1;
					break;
				case 'OnUpdate':
					$triggerType = 0;
					break;
				default:
					$triggerType = 99;
					if (is_numeric($variableId) ) throw new Exception('Found unknown EventType '.$eventType);
					break;
				}
			if ($triggerType<99)
				{	
				$eventName = $eventType.'_'.$variableId;
				$eventId   = @IPS_GetObjectIDByIdent($eventName, $scriptId);
				if ($eventId === false) 
					{
					$eventId = IPS_CreateEvent(0);
					IPS_SetName($eventId, $eventName);
					IPS_SetIdent($eventId, $eventName);
					IPS_SetEventTrigger($eventId, $triggerType, $variableId);
					IPS_SetParent($eventId, $scriptId);
					IPS_SetEventActive($eventId, true);
					IPSLogger_Dbg (__file__, 'Created IPSMessageHandler Event for Variable='.$variableId);
					}
				}
			}

		/**
		 * @private
		 *
		 * Speichert die aktuelle Event Konfiguration
		 *
		 * @param string[] $configuration Konfigurations Array
		 */
		private function InitEventConfiguration()
			{
			// Build Configuration String
			$configString = chr(9).chr(9).'$'.$this->identifier.' = array('.PHP_EOL.PHP_EOL;
			$configString .= chr(9).chr(9).chr(9).');'.PHP_EOL;
			$configString .= chr(9).chr(9).'return $'.$this->identifier.';'.PHP_EOL;				
			$funcString=PHP_EOL.PHP_EOL.chr(9).'function '.$this->functionName.'() {'.PHP_EOL;
			$funcString.=$configString;
			$funcString.=chr(9).'}'.PHP_EOL.PHP_EOL;				

			// Write to File
			$fileNameFull = IPS_GetKernelDir().$this->filename;  /* >>>>>>>> change here */
			if (!file_exists($fileNameFull)) 
				{
				throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);
				}
			$fileContent = file_get_contents($fileNameFull, true);
			$pos3 = strpos($fileContent, '?>');
			if ($pos3 === false) {
				throw new IPSMessageHandlerException($fileNameFull.' is not a config file !!!', E_USER_ERROR);
			}
			echo " End of config file Marker ist auf Position ".$pos3."\n"; 
			$fileContentNew = substr($fileContent, 0, $pos3).$funcString.substr($fileContent, $pos3);
			//echo $fileContentNew;
			file_put_contents($fileNameFull, $fileContentNew);
						 
			}

		/**
		 * @private
		 *
		 * Speichert die aktuelle Event Konfiguration
		 *
		 * @param string[] $configuration Konfigurations Array
		 */
		private function StoreEventConfiguration($configuration)
		   {
			// Build Configuration String
			$configString = '$'.$this->identifier.' = array(';
			//echo "----> wird jetzt gespeichert:\n";
			//print_r($configuration);

			foreach ($configuration as $variableId=>$params) 
				{
				//echo "   process ".$variableId."  (".IPS_GetName(IPS_GetParent($variableId))."/".IPS_GetName($variableId).")\n";
				//print_r($params);
				if ( is_numeric((string)$variableId) )
					{
					$configString .= PHP_EOL.chr(9).chr(9).chr(9).$variableId.' => array(';
					for ($i=0; $i<count($params); $i=$i+3) 
						{
						if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';
						$configString .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
						}
					$configString .= '),'.'        /* '.IPS_GetName($variableId).'  '.IPS_GetName(IPS_GetParent($variableId)).'     */';
					}
				else
					{
					$configString .= PHP_EOL.chr(9).chr(9).chr(9).'"'.$variableId.'" => array(';
					for ($i=0; $i<count($params); $i=$i+3) 
						{
						if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';
						$configString .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
						}
					$configString .= '),'.'        ';
					}
				}
			$configString .= PHP_EOL.chr(9).chr(9).chr(9).');'.PHP_EOL.PHP_EOL.chr(9).chr(9);
			//echo $configString;
			// Write to File
			$fileNameFull = IPS_GetKernelDir().$this->filename;  /* >>>>>>>> change here */
			if (!file_exists($fileNameFull)) {
				throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);
			}
			$fileContent = file_get_contents($fileNameFull, true);
			$pos1 = strpos($fileContent, '$'.$this->identifier.' = array(');
			$pos2 = strpos($fileContent, 'return $'.$this->identifier.';');

			if ($pos1 === false or $pos2 === false) {
				throw new IPSMessageHandlerException($this->identifier.' could NOT be found !!!', E_USER_ERROR);
			}
			$fileContentNew = substr($fileContent, 0, $pos1).$configString.substr($fileContent, $pos2);
			file_put_contents($fileNameFull, $fileContentNew);
			$this->Set_EventConfigurationAuto($configuration);
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
			$configuration = $this->Get_EventConfigurationAuto();
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
				$this->StoreEventConfiguration($configuration);
				$this->CreateEvent($variableId, $eventType, $this->scriptID);
   		}
		
		/************************************************************************
		 *
		 * einmal registrierte Events können auch gelöscht werden
		 * das Event muss auch später gelöscht werden 
		 *		
		 ********************************************************************************/	
			
		function UnRegisterAutoEvent($variableId)
			{
			$configuration = $this->Get_EventConfigurationAuto();

			if (array_key_exists($variableId, $configuration))
				{
				unset($configuration[$variableId]);
				$this->StoreEventConfiguration($configuration);
				//self::CreateEvent($variableId, $eventType, self::$scriptID);
				}
   		}			

		/**
		 *
		 * Druckt die aktuelle Auto Event Konfiguration
		 *
		 * @return string[] Event Konfiguration
		 */
		function PrintAutoEvent() 
			{
			$configuration = $this->Get_EventConfigurationAuto();
			//print_r($configuration);
			if (sizeof($configuration)==0 )
				{
				echo "No configuration stored.\n";
				}
			else
				{
				echo "Configuration has ".sizeof($configuration)." entries.\n";
				foreach ($configuration as $id => $entry)
					{
                    $entry2=str_replace("\n","",$entry[2]);
                    //echo $entry2."\n";  
                    $kommandotrim=array();
                    $kommandogruppe=explode(";",$entry2);
                    foreach ($kommandogruppe as $index => $kommando)
                        {
                        $befehlsgruppe=explode(",",$kommando);
                        foreach ( $befehlsgruppe as $count => $befehl)
                            {
                            $result=trim($befehl);
                            if ($result != "")
                                {
                                //echo "   ".$index." ".$count."  ".$result."\n ";
                                $kommandotrim[$index][$count]=$result;
                                }
                            }
                        }
                    $entry3="";  $semi="";  
                    //print_r($kommandotrim);
                    foreach ($kommandotrim as $kommando)
                        {
                        //print_r($kommando);
                        $comma="";
                        $entry3.=$semi;
                        if ($semi=="") $semi=";"; 
                        foreach ($kommando as $befehl)
                            {
                            //echo $befehl;
                            $entry3.=$comma.$befehl;
                            if ($comma=="") $comma=",";
                            }
                        }
    				echo "  ".$id." => (".$entry[0].",".$entry[1].",".$entry3.",)\n";
					}
				}
			return $configuration;
			}
			
		/**
		 *
		 * Gibt das aktuelle Auto Event aus
		 *
		 * @return string[] Event Konfiguration
		 */
		function getAutoEvent($ID=null) 
			{
			$configuration = $this->Get_EventConfigurationAuto();
			//print_r($configuration);
			if (sizeof($configuration)>0 )
				{
				//echo "Configuration has ".sizeof($configuration)." entries.\n";
				foreach ($configuration as $id => $entry)
					{
					//echo "  ".$id." => (".$entry[0].",".$entry[1].",".$entry[2].",)\n";
					}
				}
			if ($ID==null)
				{	
				return $configuration;
				}
			else
				{
				if ( isset($configuration[$ID]) ) return $configuration[$ID];
				else return (false);
				}	
			}			

	} /* ende class */

class AutosteuerungConfigurationAlexa extends AutosteuerungConfiguration
	{
	
	protected $eventConfigurationAuto = array();
	protected $scriptID;
	protected $functionName="Alexa_GetEventConfiguration";
	protected $identifier="alexaConfiguration";
	protected $filename='scripts/IPSLibrary/config/modules/Autosteuerung/Autosteuerung_Configuration.inc.php';
	
	} 

class AutosteuerungConfigurationHandler extends AutosteuerungConfiguration
	{
	
	protected $eventConfigurationAuto = array();
	protected $scriptID;
	protected $functionName="Autosteuerung_GetEventConfiguration";
	protected $identifier="eventConfiguration";
	protected $filename='scripts/IPSLibrary/config/modules/Autosteuerung/Autosteuerung_Configuration.inc.php';

	} 


/*****************************************************************************************************
 *
 * Autosteuerung Operator
 *
 * Routinen für den Betrieb
 *
 * derzeit nur Ermittlung von Anwesend
 *
 **************************************************************************************************************/

class AutosteuerungOperator 
	{
	
	private $logicAnwesend;

	public function __construct()
		{
		//IPSLogger_Dbg(__file__, 'Construct Class AutosteuerungOperator.');
		IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
			
		$this->logicAnwesend=Autosteuerung_Anwesend();
		}

	public function Anwesend()
		{
		$result=false;
		$operator="";
		foreach($this->logicAnwesend as $type => $operation)
			{
			if ($type == "OR")
				{
				$operator.="OR";
				foreach ($operation as $oid)
					{
					$result = $result || GetValueBoolean($oid);
					$operator.=" ".IPS_GetName($oid);
					//echo "Operation OR for OID : ".$oid." ".GetValue($oid)." Result : ".$result."\n";
					}
				}
			if ($type == "AND")
				{
				$operator.=" AND";				
				foreach ($operation as $oid)
					{
					$result = $result && GetValue($oid);
					$operator.=" ".IPS_GetName($oid);
					//echo "Operation AND for OID : ".$oid." ".GetValue($oid)." ".$result."\n";
					}
				}
			}
		IPSLogger_Dbg(__file__, 'AutosteuerungOperator, Anwesenheitsauswertung: '.$operator.'.= '.($result?"Aus":"Ein"));
		return ($result);				
		}
		
	public function getLogicAnwesend()
		{
		$result=false;
		$operator="";		
		foreach($this->logicAnwesend as $type => $operation)
			{
			if ($type == "OR")
				{
				$operator.="OR";
				foreach ($operation as $oid)
					{
					$result = $result || GetValueBoolean($oid);
					$operator.=" ".IPS_GetName($oid);
					echo "Operation OR for OID : ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))." (".$oid.") ".(GetValue($oid)?"Anwesend":"Abwesend")." Result : ".$result."\n";
					}
				}
			if ($type == "AND")
				{
				$operator.=" AND";				
				foreach ($operation as $oid)
					{
					$result = $result && GetValue($oid);
					$operator.=" ".IPS_GetName($oid);
					echo "Operation AND for OID : ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))." (".$oid.") ".(GetValue($oid)?"Anwesend":"Abwesend")." ".$result."\n";
					}
				}
			}
		echo 'AutosteuerungOperator, Anwesenheitsauswertung: '.$operator.'.= '.($result?"Aus":"Ein")."\n";
		}			

	} /* ende class */

	
/*****************************************************************************************************
 *
 * Autosteuerung
 *
 * Routinen zur Evaluierung der Befehlsketten
 *
 *      getFunctions			welche AutosteuerungsFunktionen sind aktiviert
 *      isitdark, isitlight, isitsleep, isitwakeup, isitawake, isithome, isitmove, isitalarm, isitheatday	aktuellen Zustand feststellen und ausgeben
 *      getDaylight
 *      switichingTimes
 *      timeright
 *      setNewValue, setNewValueIfDif
 *      trimCommand
 *
 *      ParseCommand, parseName, parseParameter, parseValue,
 *
 *      EvaluateCommand, evalCom_IFDIF, evalCom_LEVEL, ControlSwitchLevel
 *
 *      ExecuteCommand, 
 *
 *      GetColor 
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
	var $CategoryIdData, $CategoryIdApp;
	var $CategoryId_Ansteuerung;
	var $availableModules;							// eigentlich Liste aller GUIDs der Module, für check ob Parameter ein gültige Modul GID hat
	
	var $log;										// logging class, called with this class
	
	var $CategoryId_Anwesenheit, $CategoryId_Alarm;	
	var $CategoryId_SchalterAnwesend, $CategoryId_SchalterAlarm;

    var $scriptId_Autosteuerung;

    var $AnwesenheitssimulationID;                             // für Anwesenheitssimulation, Category

	var $CategoryId_Stromheizung;
	var $CategoryId_Wochenplan;
	
	var $lightManager;
	var $switchCategoryId, $groupCategoryId , $prgCategoryId;

	var $heatManager;
	var $switchCategoryHeatId, $groupCategoryHeatId , $prgCategoryHeatId;
	
	var $DENONsteuerung;
	var $dataCategoryIdDenon, $configCategoryIdDenon;
	
	/***********************
	 *
	 * es wird innerhalb von data.modules.Autosteuerung die Kategorie Ansteuerung erstellt und dort pro 
	 * im Config File angelegter Applikation ein Eintrag erstellt. Derzeit unterstützt:
	 *
	 * Anwesenheitserkennung, Alarmanlage, Stromheizung
	 *
	 *************************************************************/
	
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
		$this->CategoryIdApp      				= $moduleManager->GetModuleCategoryID('app');
		$this->CategoryId_Ansteuerung			= IPS_GetCategoryIDByName("Ansteuerung", $this->CategoryIdData);

		$this->scriptId_Autosteuerung  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));

		$object_data= new ipsobject($this->CategoryIdData);
		$object_app= new ipsobject($this->CategoryIdApp);

		$NachrichtenID = $object_data->osearch("Nachricht");
		$NachrichtenScriptID  = $object_app->osearch("Nachricht");

		if (isset($NachrichtenScriptID))
			{
			$setup = Autosteuerung_Setup();
			if ( isset($setup["LogDirectory"]) == false )
				{
				$setup["LogDirectory"]="C:/Scripts/Autosteuerung/";
				}	
			$object3= new ipsobject($NachrichtenID);
			$NachrichtenInputID=$object3->osearch("Input");
			/* logging in einem File und in einem String am Webfront */
			$this->log=new Logging($setup["LogDirectory"]."Autosteuerung.csv",$NachrichtenInputID,IPS_GetName(0).";Autosteuerung;");			
			}
		else echo "!!!!!FEHLER: Logging Funktion nicht gefunden.\n";
		
		/* speziell fuer Anwesenheitserkennung */
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
       
        $this->AnwesenheitssimulationID = @IPS_GetObjectIDByName("Anwesenheitssimulation",$this->CategoryId_Ansteuerung);
			
		/* Modulliste erstellen */
		//echo "  g:(".memory_get_usage()." Byte).";
		$this->availableModules=array();
		foreach(IPS_GetModuleList() as $guid)
			{
			$instanzlist=IPS_GetInstanceListByModuleID($guid);
			if (sizeof($instanzlist)>0)
				{			
				$module = IPS_GetModule($guid);
				$name=$module['ModuleName'];
				$this->availableModules[$name] = $guid;
				}
			}
		//ksort($this->availableModules);
		//echo "  k:(".memory_get_usage()." Byte).";
				
		if ( isset($this->installedModules["Stromheizung"] ) )
			{
			include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");						
			/* speziell für Stromheizung */
			$this->CategoryId_Stromheizung			= @IPS_GetObjectIDByName("Stromheizung",$this->CategoryId_Ansteuerung);
			if ($this->CategoryId_Stromheizung === false)
				{
				$this->CategoryId_Wochenplan = false;			
				}
			else
				{	
				$wochenplan = IPS_GetObjectIDByName("Wochenplan-Stromheizung",$this->CategoryIdData);
				$this->CategoryId_Wochenplan	= IPS_GetObjectIDByName("Wochenplan",$wochenplan);			
				}
			$this->heatManager = new IPSHeat_Manager();
			
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');
			$this->switchCategoryHeatId  = IPS_GetObjectIDByIdent('Switches', $baseId);
			$this->groupCategoryHeatId   = IPS_GetObjectIDByIdent('Groups', $baseId);
			$this->programCategoryHeatId = IPS_GetObjectIDByIdent('Programs', $baseId);			
			}	
								
		if ( isset($this->installedModules["IPSLight"] ) )
			{	
			include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");						
			$this->lightManager = new IPSLight_Manager();
	
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
			$this->switchCategoryId 	= IPS_GetObjectIDByIdent('Switches', $baseId);
			$this->groupCategoryId   	= IPS_GetObjectIDByIdent('Groups', $baseId);
			$this->prgCategoryId   		= IPS_GetObjectIDByIdent('Programs', $baseId);
			}
			
								
		if ( isset($this->installedModules["DENONsteuerung"] ) )
			{
			Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DENONsteuerung\DENONsteuerung.Library.inc.php");						
			$this->DENONsteuerung = new DENONsteuerung();	
			$this->dataCategoryIdDenon = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.DENONsteuerung');
			//$this->configCategoryIdDenon = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.DENONsteuerung');
			}									
		}

	/* welche AutosteuerungsFunktionen sind aktiviert:
     * unter data.modules.Autosteuerung.Ansteuerung alle bekannten Variablen auswerten und den Status zurückmelden
     * Bekannt sind derzeit
     *  GutenMorgenWecker, Anwesenheitserkennung, Alarmanlage, Stromheizung, Alexa
     *
     * wird dann von den einzelnen Routinen isitawake etc. ausgewertet
     *
     *
     */

	public function getFunctions($function="All")
		{
		$children=array();
		$childrenIDs=IPS_GetChildrenIDs($this->CategoryId_Ansteuerung);
		foreach ($childrenIDs as $ID)
			{
			$children[IPS_GetName($ID)]["OID"]=$ID;
			$children[IPS_GetName($ID)]["VALUE"]=GetValue($ID);
			$children[IPS_GetName($ID)]["VALUE_F"]=GetValueFormatted($ID);
			//echo "getFunctions : ".$ID." (".IPS_GetName($ID).")\n";
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
					$anwesend2ID=IPS_GetVariableIDByName("StatusAnwesend",$ID);
					//echo "SchalterAnwesend OID : ".$anwesendID."\n";
					$children[IPS_GetName($ID)]["STATUS2"]=(integer)GetValue($anwesend2ID);					
					break;					
				case "Alarmanlage":
					$alarmID=IPS_GetVariableIDByName("SchalterAlarmanlage",$ID);
					//echo "SchalterAlarmanlage OID : ".$alarmID."\n";
					$children[IPS_GetName($ID)]["STATUS"]=(integer)GetValue($alarmID);					
					break;					
				case "Stromheizung":
					/* es gibt keinen Schalter der unter dem Hauptschalter noch zusaetzlich verwendet wird */
					//$heatID=IPS_GetVariableIDByName("Stromheizung",$ID);
					//$children[IPS_GetName($ID)]["STATUS"]=(integer)GetValue($heatID);					
					break;					
				case "Alexa":				
					/* es gibt keinen Schalter der unter dem Hauptschalter noch zusaetzlich verwendet wird */
					//$alexaID=IPS_GetVariableIDByName("Alexa",$ID);
					//$children[IPS_GetName($ID)]["STATUS"]=(integer)GetValue($alexaID);					
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
	 * sleep, wakeup, awake vom GutenMorgenWecker
     * home/move von Anwesenheitserkennung
	 * geplant Anwesend/Alarm von Erkennung
	 *
	 *****************************************************************/

	/* es ist dunkel draussen */

	public function isitdark()
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

	/* es ist hell draussen */
	
	public function isitlight()
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

	/* GutenMorgenWecker steht auf Schlafen */

	public function isitsleep()
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

	/* GutenMorgenWecker steht auf Aufwachen */

	public function isitwakeup()
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

	/* GutenMorgenWecker steht auf Munter */

	public function isitawake()
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

	/* Anwesenheitserkennung steht auf zu Hause */

	public function isithome()
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

	/* Anwesenheitserkennung steht auf Bewegung */

	public function isitmove()
		{
		$functions=self::getFunctions();
		if ( isset($functions["Anwesenheitserkennung"]["STATUS2"]) )
			{
			if ($functions["Anwesenheitserkennung"]["STATUS2"] == 1) { return(true); }
			else  { return(false); }
			}
		else
			{
			return (true);	/* default moving */	
			}		
		}

	/* Alarmanlage eingeschaltet */

	public function isitalarm()
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
		
	/* laut Wochenplan wird heute geheizt ? */	
		
	public function isitheatday()
		{
		$status=false;
		$functions=self::getFunctions();
		if ( isset($functions["Stromheizung"]["STATUS"]) )
			{
			if ($functions["Stromheizung"]["STATUS"] == 0) { $status=true; }
			}
		else
			{
			/* der Status wurde nicht zentral ermittelt, selbst erfassen */
			$found=@IPS_GetObjectIDByName("Zeile1",IPS_GetParent($this->CategoryId_Wochenplan));			
			if ($found!== false) 
				{
				//$property=IPS_GetObject($found);
				//if ( $property["ObjectType"]==6 ) $status=(integer)GetValue(IPS_GetLink($found)["TargetID"]);
				$status=(integer)GetValue($found);
				//echo "    Status Heiztag : ".($status?"JA":"NEIN")."\n";
				}				
			//print_r($childrenIDs);
			return ($status);		
			}	
		}		

	/* gibt die Sonnenauf- und -untergangszeiten aus */

	public function getDaylight()
		{
		$result["Sunrise"]=$this->sunrise;
		$result["Sunset"]=$this->sunset;
		//echo "Sonnenauf/untergang ".date("H:i",$this->sunrise)." ".date("H:i",$this->sunset)." \n";
		$result["Text"]="Sonnenauf/untergang ".date("H:i",$this->sunrise)." ".date("H:i",$this->sunset);
		return $result;
		}

	/* Auswertung der Angaben in den Szenen, Berechnung der Werte für sunrise und sunset */

	public function switchingTimes($scene)
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
		
	/* Auswertung der Angaben in den Szenen. Schauen ob auf ein oder aus geschaltet werden soll */		
		
	public function timeright($scene)
		{
		//echo "Szene ".$scene["NAME"]."\n";
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
			//echo "Schaltzeiten:".$actualTimeStartHour.":".$actualTimeStartMinute." bis ".$actualTimeStopHour.":".$actualTimeStopMinute."\n";
			$this->timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);
			$this->timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
			$this->now = time();

			if ($this->timeStart > $this->timeStop)
				{
				echo "        stop is considered to be on the next day.\n";
				if (($this->now > $this->timeStart) || ($this->now < $this->timeStop))
					{				
					$minutesRange = ($this->timeStop-$this->timeStart)/60+24*60;
					$actionTriggerMinutes = 5;
					$rndVal = rand(1,100);
					//echo "Zufallszahl:".$rndVal."\n";
					if ( ($rndVal < $scene["EVENT_CHANCE"]) || ($scene["EVENT_CHANCE"]==100)) { $timeright=true; }
					}
				}

			if (($this->now > $this->timeStart) && ($this->now < $this->timeStop))
				{
				$minutesRange = ($this->timeStop-$this->timeStart)/60;
				$actionTriggerMinutes = 5;
				$rndVal = rand(1,100);
				//echo "Zufallszahl:".$rndVal."\n";
				if ( ($rndVal < $scene["EVENT_CHANCE"]) || ($scene["EVENT_CHANCE"]==100)) { $timeright=true; }
				}
			}	
		return ($timeright);	
		}	

    /****************************************************
     *
     * Konfiguration der Anwesenheitsliste ausgeben
     *
     ********************************************************************/

    public function statusAnwesenheitSimulation($html=false)
        {
        $text=""; 
        if ($html) $cr="<br>";
        else $cr="\n"; 
    	$text .= "Eingestellte Anwesenheitssimulation:".$cr.$cr;
        $scenes=Autosteuerung_GetScenes();    
    	foreach($scenes as $scene)
	    	{
		    if (isset($scene["TYPE"]))
			    {
    			if ( strtoupper($scene["TYPE"]) == "AWS" )   /* nur die Events bearbeiten, die der Anwesenheitssimulation zugeordnet sind */
	    			{		
		    		$text .= "  Anwesenheitssimulation Szene : ".$scene["NAME"].$cr;
			    	}
    			else
	    			{		
		    		$text .= "  Timer Szene : ".$scene["NAME"].$cr;
			    	}
    			}
	    	$switch = $this->timeright($scene);	
		    $text .= "      Schaltet jetzt : ".($switch ? "Ja":"Nein").$cr;
    		/* Kennt nur zwei Zeiten, sollte auch für mehrere Zeiten getrennt durch , funktionieren, gerade from, ungerader Index to */	
	    	$actualTimes = $this->switchingTimes($scene);
		    //echo "Evaluierte Schaltzeiten:\n";	
    		//print_r($actualTimes);
	    	for ($sindex=0;($sindex <sizeof($actualTimes));$sindex++)
		    	{
			    //echo "   Schaltzeit ".$sindex."\n";
    			$actualTimeStart = explode(":",$actualTimes[$sindex][0]);
	    		$actualTimeStartHour = $actualTimeStart[0];
		    	$actualTimeStartMinute = $actualTimeStart[1];
			    $actualTimeStop = explode(":",$actualTimes[$sindex][1]);
    			$actualTimeStopHour = $actualTimeStop[0];
	    		$actualTimeStopMinute = $actualTimeStop[1];
		    	$text .= "      Schaltzeiten:".$actualTimeStartHour.":".$actualTimeStartMinute." bis ".$actualTimeStopHour.":".$actualTimeStopMinute."\n";
			    $timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);
    			$timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
	    		}
		    $now = time();
    		//include(IPS_GetKernelDir()."scripts/IPSLibrary/app/modules/IPSLight/IPSLight.inc.php");
	    	if (isset($scene["EVENT_IPSLIGHT"]))
		    	{
			    $text .= "      Objekt : ".$scene["EVENT_IPSLIGHT"].$cr;
    			//IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
             	}
             else
                {
          		if (isset($scene["EVENT_IPSLIGHT_GRP"]))
          	   		{
	          		$text .= "      Objektgruppe : ".$scene["EVENT_IPSLIGHT_GRP"].$cr;
   	      	    	//IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
      	   		    }	
    			}
        	}
        return ($text);        
        }

    /**********************************************
     *
     * getScenes
     *
     * Liest die AWS/TIMER Konfiguration aus, filtert auf die AWS Events, oder Eingabe
     *
     ********************************************************/

    public function getScenes($filter="AWS")
        {
        $result=array();
        $scenes=Autosteuerung_GetScenes();    
	    foreach($scenes as $scene)
		    {
    		if (isset($scene["TYPE"]))
	    		{
		    	if ( ($filter=="") || ($filter=="") || ( strtoupper($scene["TYPE"]) == $filter ) )   /* nur die Events bearbeiten, die der Anwesenheitssimulation zugeordnet sind */
			    	{		
        		    if (isset($scene["EVENT_IPSLIGHT"]))
		        	    {
           	      		$result[$scene["EVENT_IPSLIGHT"]]=$scene["EVENT_CHANCE"];
                     	}
                     else
                        {
              	    	if (isset($scene["EVENT_IPSLIGHT_GRP"]))
      	           	    	{
        	      		    $result[$scene["EVENT_IPSLIGHT_GRP"]]=$scene["EVENT_CHANCE"];
                  	   		}	
	    	        	}
                    }
                }
            }
        return($result);
        }

    public function getScenesById()
        {
        $result=array();
        $scenes=Autosteuerung_GetScenes();    
    	foreach($scenes as $Id => $scene)
	    	{
		    if (isset($scene["TYPE"]))
			    {
    			//if ( strtoupper($scene["TYPE"]) == "AWS" )   /* nur die Events bearbeiten, die der Anwesenheitssimulation zugeordnet sind */
	    			{		
            		if (isset($scene["EVENT_IPSLIGHT"]))
		            	{
       	      	    	$result[$Id]=$scene["EVENT_CHANCE"];
                 	    }
                     else
                        {
                  		if (isset($scene["EVENT_IPSLIGHT_GRP"]))
      	               		{
        	          		$result[$Id]=$scene["EVENT_CHANCE"];
              	   	    	}	
    		        	}
                    }
                }
            }
        return($result);
        }    

    function getChancesById($Id)
        {
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	    $moduleManager = new IPSModuleManager('OperationCenter',$repository);
    	$CategoryIdDataOC = $moduleManager->GetModuleCategoryID('data');
        $CategoryId_TimerSimulation    	= IPS_GetCategoryIDByName('TimerSimulation',$CategoryIdDataOC);
    
        $result=false;
        $scenes=Autosteuerung_GetScenes();    
    	if (isset($scenes[$Id]["TYPE"]))
			{
			//if ( strtoupper($scene["TYPE"]) == "AWS" )   /* nur die Events bearbeiten, die der Anwesenheitssimulation zugeordnet sind */
				{		
        		if (isset($scenes[$Id]["EVENT_IPSLIGHT"]))
		        	{
       	      		$result=$scenes[$Id]["EVENT_CHANCE"];
                 	}
                 else
                    {
              		if (isset($scenes[$Id]["EVENT_IPSLIGHT_GRP"]))
      	           		{
        	      		$result=$scenes[$Id]["EVENT_CHANCE"];
              	   		}	
		        	}
                }
            }
        return($result);
        }

	/***************************************
	 *
	 * Vorwert erfassen und speichern, eingesetzt bei Heizung um zB zu erkennen Temperatur gestiegen, gesunken, etc.
	 * wird bei jeder Änderung aufgerufen
	 *
	 * in der entsprechenden Kategorie data.modules.Autosteuerung.Ansteuerung.Stromheizung einen Eintrag mit
	 * dem selben Variablennamen machen (plus Parentname) und daraus den Vorwert ableiten
	 *
	 ******************************************************************/

	public function setNewValue($variableID,$value)
		{
		if ($this->CategoryId_Stromheizung !== false)
			{
			if ( !(IPS_ObjectExists($variableID)) ) 
				{
				echo "Variable ID : ".$variableID." existiert nicht.\n";
				return($value);
				}
			else
				{	 
				//echo "Stromheizung Speicherort OID : ".$this->CategoryId_Stromheizung." (".IPS_GetName(IPS_GetParent($this->CategoryId_Stromheizung))."/".IPS_GetName($this->CategoryId_Stromheizung).")  Variable OID : ".$variableID." (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")\n";
				// CreateVariable ($Name, $Type ( 0 Boolean, 1 Integer 2 Float, 3 String) , $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
				$mirrorVariableID=CreateVariable (IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID)), 2, $this->CategoryId_Stromheizung, $Position=0, $Profile="", $Action=null, $ValueDefault=0, $Icon='');
				//echo "Spiegelvariable ist auf OID : ".$mirrorVariableID."   ".IPS_GetName($mirrorVariableID)."/".IPS_GetName(IPS_GetParent($mirrorVariableID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorVariableID)))."   alter Wert ist : ".GetValue($mirrorVariableID)."\n";
				$oldValue=GetValue($mirrorVariableID);
				SetValue($mirrorVariableID,$value);
				return($oldValue);
				}
			}
		else return ($value);	
		}

	/***************************************
	 *
	 * Vorwert erfassen und speichern wenn Änderung groesser $dif ist
	 *
	 * in der entsprechenden Kategorie data.modules.Autosteuerung.Ansteuerung.Stromheizung einen Eintrag mit
	 * dem selben Variablennamen machen (plus Parentname plus dif)  und daraus den Vorwert ableiten
	 *
	 ******************************************************************/

	public function setNewValueIfDif($variableID,$value,$dif)
		{
		if ($this->CategoryId_Stromheizung !== false)
			{
			if ( !(IPS_ObjectExists($variableID)) ) 
				{
				echo "Variable ID : ".$variableID." existiert nicht.\n";
				return($value);
				}
			else
				{	 
				echo "Stromheizung Speicherort OID : ".$this->CategoryId_Stromheizung." (".IPS_GetName(IPS_GetParent($this->CategoryId_Stromheizung))."/".IPS_GetName($this->CategoryId_Stromheizung).")  Variable OID : ".$variableID." (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")\n";
				// CreateVariable ($Name, $Type ( 0 Boolean, 1 Integer 2 Float, 3 String) , $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
				$mirrorVariableID=CreateVariable (IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID))."_Dif", 2, $this->CategoryId_Stromheizung, $Position=0, $Profile="", $Action=null, $ValueDefault=0, $Icon='');
				echo "Spiegelvariable ist auf OID : ".$mirrorVariableID."   ".IPS_GetName($mirrorVariableID)."/".IPS_GetName(IPS_GetParent($mirrorVariableID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorVariableID))).
						"   alter Wert ist : ".GetValue($mirrorVariableID)."\n";
				$oldValue=GetValue($mirrorVariableID);
				if (abs($oldValue-$value)>=$dif) SetValue($mirrorVariableID,$value);
				return($oldValue);
				}
			}
		else return ($value);	
		}

	/**
	 *
	 * Command trimmen, damit es in einer Zeile ausgegeben werden kann, Befehl wird oft formatiert für bessere Lesbarkeit
	 *
	 * @return string[] Event Konfiguration
	 */
	function trimCommand($command) 
		{
	    $kommandotrim=array();
        $kommandogruppe=explode(";",$command);
        foreach ($kommandogruppe as $index => $kommando)
            {
            $befehlsgruppe=explode(",",$kommando);
            foreach ( $befehlsgruppe as $count => $befehl)
                {
                $result=trim($befehl);
                if ($result != "")
                    {
                    //echo "   ".$index." ".$count."  ".$result."\n ";
                    $kommandotrim[$index][$count]=$result;
                    }
                }
            }
        $entry3="";  $semi="";  
        //print_r($kommandotrim);
        foreach ($kommandotrim as $kommando)
            {
            //print_r($kommando);
            $comma="";
            $entry3.=$semi;
            if ($semi=="") $semi=";"; 
            foreach ($kommando as $befehl)
               {
               //echo $befehl;
               $entry3.=$comma.$befehl;
               if ($comma=="") $comma=",";
               }
           }
    	return ($entry3);
	    }



	/***************************************
	 *
	 * hier wird der Befehl in die Einzelbefehle zerlegt, und Kurzbefehle in die Langform gebracht
	 * unterschiedliche Bearbeitung für Ventilator, Anwesenheit und alle anderen
	 *
	 * Es gibt folgende Befehle die extrahiert werden:
	 *  NAME, SPEAK, OFF, ON, OFF_MASK, ON_MASK, COND, DELAY
	 *  NAME, SETPOINT, THRESHOLD, MODE
	 *
	 * Kurzbefehle:
	 *  1 Parameter:   "NAME"
	 *  2 Parameter    "NAME" "STATUS"
	 *  3 Parameter:   "NAME" "STATUS" "DELAY"
	 *
	 * Es folgen der Reihe nach die Befehle, möglichst gleich für alle verschiedenen Varianten
	 *		ParseCommand()
	 * 	mehrmals EvaluateCommand() (in einem Config-String sind mehrere Kommandos (separiert durch ;), die aus einzelnen Befehlen (separiert durch ,)zusammengesetzt sind)
	 *      ExecuteCommand() mit switch() liefert als Ergebnis die Parameter für Dim und Delay.
	 *......Abarbeitung von Delay Befehl
	 *
	 *******************************************************/

	public function ParseCommand($params,$status=false,$simulate=false)
		{
		/************************** 
		 *
		 * keine Befehlsevaluierung, nur Festlegung des Rahmengerüsts und Vereinheitlichung der Abkürzer
		 *
		 * Abkürzer unterschiedlich behandeln für: Ventilator(HeatControl, Heizung), Anwesenheit und alle anderen 
		 * 
		 * Befehlsgruppe zerlegen zB von params : [0] OnChange [1] Status [2] name:Stiegenlicht,speak:Stiegenlicht
		 * aus [2] name:Stiegenlicht,speak:Stiegenlicht wird
		 *          [0] name:Stiegenlicht [1] speak:Stiegenlicht
		 *
		 * Kommandos getrennt mit ; Kommandos enthalten Befehle, damit können mehrere Befehle nacheinander abgearbeitet werden
		 * Befehle getrennt mit , und erweitert um Parameter mit :
		 * Parameter mit : enthalten Befehl:Parameter
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
		 * aus params[0..2] wird dann params[2] zerlegt in commands[0..n] und jedes command in Befehl und Parameter, getrennt durch : 
		 * hintereinander abgespeichert in parges[Kommando][Eintrag][Befehl] und danach [Parameter] 
		 *
		 *
		 */
		$parges=array();
		//$this->log->LogMessage("ParseParameter.");
		$params2=$this->trimCommand($params[2]);
		$commands = explode(';', $params2);
        //print_r($commands);
		$Kommando=0;
		echo "   ParseCommand Gesamter Befehl : ".$params2."\n";
		foreach ($commands as $command)
			{
			$Kommando++;
			$command=trim($command);		// Leerzeichen am Anfang und Ende des Kommandos entfernen
			$moduleParams2 = explode(',', $command);
			$count=count($moduleParams2);
			$Eintrag=$count;
			$switch=false;		// marker wenn kein ON oder OFF Befehl gesetzt wurde
			echo "        Kommando ".$Kommando." : Anzahl ".$count." Parameter erkannt in \"".$command."\" \n";

			switch (strtoupper($params[1]))
				{
				case "VENTILATOR":
				case "HEATCONTROL":
				case "HEIZUNG": 
					//echo "Es geht um Ventilatoren \n"; 
					// Dieser Befehl muss erkannt werden "Ventilator,25,true,24,false"
					switch ($count)
						{
						case "10":
						case "9":
						case "8":
						case "7":
						case "6": 
						case "5": /* wenn Anzahl Parameter groesser 4 ist, gibt es keine Sonderlocken, einfach einlesen */
							$i=4;
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
						case "4":
							$i=3;
							$params_more=explode(":",$moduleParams2[$i]);
							if (count($params_more)>1)
								{
								$parges[$Kommando][$Eintrag]=self::parseParameter($params_more);
								$Eintrag--;						
								}
							else
								{
								$parges[$Kommando][$Eintrag][]="MODE";								
								$parges[$Kommando][$Eintrag][]=$params_more[0];
								$Eintrag--;								
								}
							$i--;								
						case "3":
							$params_three=explode(":",$moduleParams2[2]);
							if (count($params_three)>1)
								{
								$parges[$Kommando][$Eintrag]=self::parseParameter($params_three);
								$Eintrag--;						
								}
							else
								{
								$parges[$Kommando][$Eintrag][]="THRESHOLD";								
								$parges[$Kommando][$Eintrag][]=$params_three[0];
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
								$parges[$Kommando][$Eintrag][]="SETPOINT";								
								$parges[$Kommando][$Eintrag][]=$params_two[0];
								$Eintrag--;								
								}
						case "1":			/* Name */
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
					break; 
				case "ANWESENHEIT":
                case "ALEXA":
					/* es gibt noch Sonderformen der Befehlsdarstellung, diese vorverarbeiten und damit standardisieren
					 *
					 * "Schaltername"
					 * "Schaltername,true"   "Schaltername,false"
					 * "Schaltername,true,20"
					 * "name:Schaltername,On:true,Off:false,Delay:20"
					 *
                     * Aufpassen mit leeren Befehlen, damit hier nicht ein leerer Name erkannt wird
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
                                $oid=$this->parseName($result[0]);
								//print_r($result);
                                if ($oid !== false)
                                    {
									$parges[$Kommando][$Eintrag][]="OID";
									$parges[$Kommando][$Eintrag][]=$oid;
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
								/* nur ein Parameter, muss der Name des Schalters/Gruppe sein, wenn der Name nach trim leer ist, ueberspringen */
                                $name=trim($params_one[0]);
                                if ($name != "")
                                    {
                                    $oid=$this->parseName(strtoupper($name));
                                    if ($oid !== false)
                                        {
									    $parges[$Kommando][$Eintrag][]="OID";
									    $parges[$Kommando][$Eintrag][]=$oid;
                                        }
                                    else 
                                        {
    								    $parges[$Kommando][$Eintrag][]="NAME";
	    							    $parges[$Kommando][$Eintrag][]=$name;
                                        }
		    						$Eintrag--;								
                                    }
								}
							break;
						default:
							echo "Anzahl Parameter falsch in Param2: ".count($moduleParams2)."\n";
							break;
						}
					break;	
				default:		/* ------------- Standardbefehlssatz ------------------------- */
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
					break;	
				}
			if ( isset ($parges[$Kommando]) )
				{
				foreach ($parges[$Kommando] as $command)
					{
					//echo $command[0]."   ";
					if ($command[0]=="ON") 				{ $switch=true; }	
					if ($command[0]=="OFF") 			{ $switch=true; }
					if ($command[0]=="ON#COLOR") 		{ $switch=true; }
					if ($command[0]=="OFF#COLOR") 		{ $switch=true; }
					if ($command[0]=="ON#LEVEL") 		{ $switch=true; }
					if ($command[0]=="OFF#LEVEL") 		{ $switch=true; }
					}								
				/* default Schaltbefehl einfügen einmal herausnehmen, es wird nur geschaltet wenn definitiv gefordert 
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
					}	*/				
				$parges[$Kommando][0][]="SOURCE";		/* Die Quelle des Befehls dokumentieren */
				$parges[$Kommando][0][]=$params[0];		/* OnUpdate oder OnChange  */
				$parges[$Kommando][0][]=$status;			/* der aktuelle Wert des auslösenden Objektes, default false */
				ksort($parges[$Kommando]);
				}
			}		/* foreach alle Strichpunkt Befehle durchgehen. Leere Befehle werden uebersprungen */
			
		/* parges in richtige Reihenfolge bringen , NAME muss an den Anfang, es können auch Sortierinfos an den Anfang gepackt werden */
		if ($simulate==true) 
			{
			echo "       >>Ergebnisse : ".json_encode($parges)."\n\n";
			//print_r($parges);
			}			
		return($parges);
		}

	/*
	 * Wenn der Name der IPSLight Variablen keine IPS Light Variable aber vielleicht ein vordefinierter Wert ist, die OID der bekannten Variable zurückmelden
	 */
		
	private function parseName($name)
		{
        switch ($name)
            {
            case "ALARM":    
				$oid=$this->CategoryId_SchalterAlarm;
                break;
            case "ANWESEND":    
				$oid=$this->CategoryId_SchalterAnwesend;
                break;
            default:
                $oid=false;
                break;    
            }			
        return ($oid);
        }

	/*
	 * ersten teil des Arrays als befehl erkenn, auf Grossbuchtaben wandeln, und das ganze array nochmals darunter speichern
	 * Erweitert das übergebene Array.
	 *
	 *
	 */
		
	private function parseParameter($params,$result=array())
		{
		//print_r($params);
		$size=count($params);
		if ( $size > 1 )
			{
			$i=0;
			while ($i < $size )
				{ 
				$result[]=strtoupper($params[$i++]);
				$result[]=$params[$i++];
				}
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
	
	/* überprüft IPSHeat, IPSLight auf den Namen und gibt ID, Wert, Typ und Modul zurück */

	function getIdByName($lightName)
		{
		$result=array();
		if ( isset($this->installedModules["Stromheizung"] ) )
			{
			$switchId = @$this->heatManager->GetSwitchIdByName($lightName);
			$groupId = @$this->heatManager->GetGroupIdByName($lightName);
			$programId = @$this->heatManager->GetProgramIdByName($lightName);
			//echo "IPSHeat Switch ".$switchId." Group ".$groupId." Program ".$programId."\n";
			if ($switchId)
				{
				$result["ID"]=$switchId;
				$result["TYP"]="Switch";
				$result["MODULE"]="IPSHeat";
				}
			elseif ($groupId)
				{	
				$result["ID"]=$groupId;
				$result["TYP"]="Group";
				$result["MODULE"]="IPSHeat";
				}
			elseif ($programId)
				{	
				$result["ID"]=$programId;
				$result["TYP"]="Program";
				$result["MODULE"]="IPSHeat";
				}
			}
		if ( (isset($this->installedModules["IPSLight"])) && (sizeof($result)==0))
			{
			$lightManager = new IPSLight_Manager();
			$switchId = @$lightManager->GetSwitchIdByName($lightName);
			$groupId = @$lightManager->GetGroupIdByName($lightName);
			$programId = @$lightManager->GetProgramIdByName($lightName);
			//echo "IPSLight Switch ".$switchId." Group ".$groupId." Program ".$programId."\n";
			if ($switchId)
				{
				$result["ID"]=$switchId;
				$result["TYP"]="Switch";
				$result["MODULE"]="IPSLight";
				}
			elseif ($groupId)
				{	
				$result["ID"]=$groupId;
				$result["TYP"]="Group";
				$result["MODULE"]="IPSLight";
				}
			elseif ($programId)
				{	
				$result["ID"]=$programId;
				$result["TYP"]="Program";
				$result["MODULE"]="IPSLight";
				}
			}
		if (sizeof($result)==0)
			{
			echo "Fehler getIdByName, Name of ID not found.\n"; 
			}
		
		return($result);	

		}

	/*********************************************************************************************************
	 *
	 * Auch das Evaluieren kann gemeinsam erfolgen, es gibt nur kleine Unterschiede zwischen den Befehlen 
	 *
	 * beim Evaluieren wird auch der Wert bevor er geändert wird als VALUE, VALUE#LEVEL, VALUE#COLOR erfasst
	 *
	 * SOURCE, OID, NAME, 
	 * ON#COLOR, ON#LEVEL, ON, OFF#COLOR, OFF#LEVEL, OFF,
	 * MODE, SETPPOINT, THRESHOLD, NOFROST 
	 * DELAY, DIM, DIM#LEVEL, DIM#TIME, 
	 * ENVELOPE, LEVEL, SPEAK, MONITOR, MUTE, IF, IFNOT
	 *
	 * IF: oder IFNOT:<parameter>     DARK, LIGHT, SLEEP, WAKEUP, AWAKE, HEATDAY, ON, OFF oder einen Variablenwert 
	 *			DARK,LIGHT sind vom Sonneauf/unteragng abhängig
	 *			SLEEP, WAKEUP, AWAKE sind vom GutenMorgenWecker abhängig
	 *			HEATDAY wird vom Stromheizung Kalendar festgelegt
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
	 *  	bei Stromheizung wird auch noch OLDSTATUS (alter Wert vom Trigger aus einer eigenen Spiegelvariable)
	 * 	mehrmals EvaluateCommand() (in einem Config-String sind mehrere Kommandos (separiert durch ;), die aus einzelnen Befehlen (separiert durch ,)zusammengesetzt sind)
	 *		ExecuteCommand() mit switch() liefert als Ergebnis die Parameter für Dim und Delay.
	 *		Abarbeitung von Delay Befehl
	 *
	 ************************************/

	public function EvaluateCommand($befehl,array &$result,$simulate=false)
		{
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug, für EchoControl Loadspeaker Ausgabe verwendet
		//$this->log->LogMessage("EvaluateCommand : ".json_encode($befehl));		
		//echo "       EvaluateCommand: Befehl ".$befehl[0]." ".$befehl[1]." abarbeiten.\n";
		$Befehl0=trim(strtoupper($befehl[0]));	/* nur Grossbuchstaben, Leerzeichen am Anfang und Ende entfernen */
		$Befehl1=trim(strtoupper($befehl[1]));	/* nur Grossbuchstaben, Leerzeichen am Anfang und Ende entfernen */
		switch ($Befehl0)	/* nur Grossbuchstaben, Leerzeichen am Anfang und Ende entfernen */
			{
			case "SOURCE":
				$result[$Befehl0]=$Befehl1;
				break;
			case "OID":			/* wie switch name nur statt IPSLight die OID */
			case "MODULE":		/* Das IPSModule in dem das Device oder der Name zu finden ist */
			case "DEVICE":		/* ein Geraet, wenn das Modul nicht bekannt ist */
			case "SPEAK":		/* text der zum Sprechen ist */
			case "COMMAND":	/* befehl für Module oder Device */
				$result[$Befehl0]=$befehl[1];
				break;
			case "LOUDSPEAKER":			/* wenn echocontrol installiert ist kann auch auf einem Amazon Gerät ausgegeben werden. macht nicht tts_play */
                /* Plausibilitätscheck gleich hier durchführen, wenn keine EchoControl installiert ist bei tts_play als default bleiben */
                $echos=$modulhandling->getInstances('EchoRemote');
               // echo "  Check if ".$befehl[1]." is im Amazon Echo Loudspeaker Array von ".json_encode($echos)."\n";
                if (in_array((integer)$befehl[1],$echos)) $result[$Befehl0]=$befehl[1];
				break;
			case "NAME":		/* Default IPSLight identifier, kann aber auch etwas anderes sein, wenn Module definiert ist */
				$result["NAME"]=$befehl[1];
				$ergebnis=$this->getIdByName($result["NAME"]);
				if (isset($ergebnis["ID"]))
					{
					if (isset($result["MODULE"])==false) $result["MODULE"]=$ergebnis["MODULE"];
					$switchId = $ergebnis["ID"];
					$result["VALUE"]=GetValue($switchId);					
					//echo "   Befehl NAME, Wert OID von ".$result["NAME"]." : ".$switchID." mit Wert wie bisher : ".$result["VALUE"]." \n";	
					}	
				break;
				
			case "ON#COLOR":
			case "ON#LEVEL":
				$result["NAME_EXT"]=strtoupper(substr($befehl[0],strpos($befehl[0],"#"),10));
				$name_ext="#".ucfirst(strtolower(strtoupper(substr($befehl[0],strpos($befehl[0],"#")+1,10))));
				$ergebnis=$this->getIdByName($result["NAME"].$name_ext);
				if (isset($ergebnis["ID"]))				
					{
					$switchId = $ergebnis["ID"];
					$result["VALUE".$result["NAME_EXT"]]=GetValue($switchId);					
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
                        case "START":
                        case "NEXT":
                        case "PREV":
                        case "END":
                            /* für die IPSLight/Heat Programmabarbeitung, bekannten Wert übernehmen */
                            $result["ON"]=$value_on;
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
				
			case "MODE":
				$result["MODE"]=strtoupper($befehl[1]);
				break;				
			case "THRESHOLD":
				$result["THRESHOLD"]=(float)$befehl[1];
				break;
			case "SETPOINT":
				$command=$befehl[1];
				if ( is_numeric($command) )
					{
					/* unterschiedliche Varianten, Integer, Float, Hex, Farbbezeichnung gefordert */
					$result["SETPOINT"]=(float)$command;
					}
				else
					{
					/* es handelt sich voraussichtlich um einen Variablennamen oder einen Befehl in geschwungenen Klammern 
					 * Ersatzwert 19 wenn alles schief geht 
					 */
					$value=19; 
					if ( is_string($command) )
						{
						if ( (strpos($command,"{") !==false) and (strpos($command,"}") !==false) ) { echo "Subcommand to evaluate found.\n"; }
						//if (strpos($command,"#") === false) { $command.="#Level"; } 
						if (strpos($command,"#") === false) { $command.="#Temp"; }
						//echo "!!!! not numeric. Adapted Variablename (".$command."), can be Heat or Light. Check Heat first.\n";
						if ($this->heatManager != Null)
							{
							$switchID = $this->heatManager->GetSwitchIdByName($command);	
							if ($switchID === false) 
								{
								echo "      ".$command." ist kein IPSHeat Thermostat.\n";
								$switchID = $this->heatManager->GetGroupIdByName($command);
								if ($switchID===false) 
									{
									echo "auch keine Gruppe.\n";
									}
								else
									{
									$value=$this->heatManager->GetValue($switchID);
									echo "HeatManager: Variablename found as Group, OID=".$switchID." with value ".$value."\n";
									}	
								}
							else
								{
								$value=$this->heatManager->GetValue($switchID);
								echo "HeatManager: Variablename found as Switch, OID=".$switchID." with value ".$value."\n";
								}		
							}
						}
					$result["SETPOINT"]=$value;;
					}	
				break;
			case "NOFROST":
				$result["NOFROST"]=(float)$befehl[1];
				break;
			case "DELAY":
				$result["DELAY"]=(integer)$befehl[1];
				break;
			case "DIM":
			case "DIM#LEVEL":			
				$result["DIM"]=(integer)$befehl[1];
				$result["DIM#LEVEL"]=$result["DIM"];
				break;			
			case "DIM#ADD":			
				$result["DIM#ADD"]=(integer)$befehl[1];
				$result["NAME_EXT"]="#LEVEL";
				$name_ext="#Level";
				$ergebnis=$this->getIdByName($result["NAME"].$name_ext);
				if (isset($ergebnis["ID"]))				
					{
					$switchId = $ergebnis["ID"];
					$result["ON"]="TRUE";
					$result["VALUE_ON"]=GetValue($switchId)+$result["DIM#ADD"];	
					if ( $result["VALUE_ON"] > 100 ) { $result["VALUE_ON"]=100; }
					IPSLogger_Dbg(__file__, 'Autosteuerung Befehl DIM#ADD:'.$result["DIM#ADD"].'. Alter Wert: '.$this->lightManager->GetValue($switchId).' Neuer Wert '.$result["VALUE_ON"]);					
					}					
				break;					
			case "DIM#SUB":			
				$result["DIM#SUB"]=(integer)$befehl[1];
				$result["NAME_EXT"]="#LEVEL";
				$name_ext="#Level";
				$ergebnis=$this->getIdByName($result["NAME"].$name_ext);
				if (isset($ergebnis["ID"]))				
					{
					$switchId = $ergebnis["ID"];
					$result["ON"]="TRUE";
					$result["VALUE_ON"]=GetValue($switchId)-$result["DIM#SUB"];	
					if ( $result["VALUE_ON"] < 0 ) { $result["VALUE_ON"]=0; }
					}					
				break;					
			case "DIM#TIME":			
				$result["DIM#TIME"]=(integer)$befehl[1];
				break;				
			case "ENVELOPE":
				$result["ENVEL"]=(integer)$befehl[1];
				break;
			case "LEVEL":
				$this->evalCom_LEVEL($befehl,$result);
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
			case "IFOR":	
			case "IFAND":	/* überschreibt den Wert vom vorigen if wenn false, gleich wie mehrere if hintereinander */
			case "IF":     /* parges hat nur die Parameter übermittelt, hier die Auswertung machen. Es gibt zumindest light, dark und einen IPS Light Variablenname (wird zum Beispiel für die Heizungs Follow me Funktion verwendet) */
				$cond=$Befehl1;
				if ( $cond == "STATUS") 		/* andere Befehldarstellung IF:STATUS:EQ:parameter */
					{
					$comp=strtoupper($befehl[2]);
					$val=(integer)$befehl[3];
					switch ($comp)
						{
						case "EQ":
							if ($result["STATUS"] != $val)
								{
								$result["SWITCH"]=false;						
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:status:eq ungleich '.$val.'. Nicht Schalten, Triggervariable ist false ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor:status:eq gleich '.$val.'. Nicht Schalten, Triggervariable ist true ');								
								}									
							break;							
						case "LT":
							if ( ($result["STATUS"] == $val) or ($result["STATUS"] > $val) ) 
								{
								$result["SWITCH"]=false;						
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:status:lt ungleich '.$val.'. Nicht Schalten, Triggervariable ist false ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor:status:lt gleich '.$val.'. Nicht Schalten, Triggervariable ist true ');								
								}									
							break;								
						case "GT":
							if ( ($result["STATUS"] == $val) or ($result["STATUS"] < $val) ) 
								{
								$result["SWITCH"]=false;						
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:status:gt ungleich '.$val.'. Nicht Schalten, Triggervariable ist false ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor:status:gt gleich '.$val.'. Nicht Schalten, Triggervariable ist true ');								
								}									
							break;								
						default:
							break;
						}
					}
				else	/*  Normale befehlsdarstellung, zweiter Paramneter xx des IF Befehls IF:xx , xx kann auch ein = enthalten für Vergleich*/
					{ 	
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
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, Triggervariable ist true ');								
								}									
							break;	
						case "OFF":
							/* nur Schalten wenn Statusvariable false ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
							if ($result["STATUS"] !== false)
								{
								$result["SWITCH"]=false;						
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist true ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, Triggervariable ist false ');								
								}								
							break;										
						case "LIGHT":
							/* nur Schalten wenn es hell ist, geschaltet wird nur wenn ein variablenname bekannt ist */
							if (self::isitdark())
								{
								$result["SWITCH"]=false;						
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist dunkel ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, es ist hell ');								
								}								
							break;
						case "DARK":
							/* nur Schalten wenn es dunkel ist, geschaltet wird nur wenn ein variablenname bekannt ist */
							if (self::isitlight())
								{
								$result["SWITCH"]=false;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, es ist hell ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, es ist dunkel ');								
								}								
							break;	
						case "SLEEP":
							/* nur Schalten wenn wir nicht schlafen */
							if ( self::isitwakeup() || self::isitawake() )
								{
								$result["SWITCH"]=false;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind munter oder im aufwachen');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, wir sind beim schlafen ');								
								}								
							break;
						case "AWAKE":
							/* nur Schalten wenn wir nicht munter sind */
							if ( self::isitwakeup() || self::isitsleep() )
								{
								$result["SWITCH"]=false;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind beim aufwachen oder schlafen ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, wir sind munter ');								
								}								
							break;
						case "WAKEUP":
							/* nur Schalten wenn wir nicht im aufwachen sind */
							if ( self::isitawake() || self::isitsleep() )
								{
								$result["SWITCH"]=false;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind munter ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, wir sind beim aufwachen ');								
								}								
							break;
						case "MOVE":
							/* nur Schalten wenn wir zuhause sind */
							if ( self::isitmove() == false )
								{
								$result["SWITCH"]=false;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir bewegen uns nicht ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, wir bewegen uns ');								
								}
							break;
						case "HOME":
							/* nur Schalten wenn wir zuhause sind */
							if ( self::isithome() == false )
								{
								$result["SWITCH"]=false;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir sind nicht zu Hause ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, wir sind zu Hause ');								
								}
							break;
						case "ALARM":
							/* nur Schalten wenn Alarmanlage aktiv */
							if ( self::isitalarm() == false )
								{
								$result["SWITCH"]=false;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Alarmanlage deaktiviert ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, Alarmanlage aktiviert ');								
								}
							break;
						case "HEATDAY":
							/* nur Schalten wenn der Kalender aktiv */
							if ( self::isitheatday() == false )
								{
								$result["SWITCH"]=false;
								echo 'Autosteuerung Befehl if:heatday ergibt  Nicht Schalten,kein Heiztag '."\n";
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:heatday ergibt Nicht Schalten,kein Heiztag ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor: Schalten, Heiztag ');								
								}
							break;
						case "TURNON":
						case "TURNOFF":
						case "SETPERCENTAGE":
						case "SETTARGETTEMPERATURE":
							$gefunden=false;
							if ($result["SOURCEID"]==$cond) $gefunden=true;
							else
								{
								$reqpos=strpos($result["SOURCEID"],"REQUEST");
								if ($reqpos !==false)
									{
									$compare=substr($result["SOURCEID"],0,$reqpos);
									//echo " REQUEST erkannt, noch einmal vergleichen mit \"".$compare."\"\n";
									if ($compare==$cond) $gefunden=true;
									}
								}	
							if ($gefunden==false)
								{
								$result["SWITCH"]=false;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:'.$cond.' ');
								}
							elseif ( strtoupper($befehl[0]) == "IFOR" ) 
								{
								$result["SWITCH"]=true;
								IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifor:'.$cond.' ');
								}
							break;
						default:
                        	/* weder light noch dark, wird ein IPSLight Variablenname sein. Wert ermitteln */
							echo "Evaluate: IF : kein definierter Begriff, wird ein IPSLight Variablenname sein. Wert für \"".$befehl[1]."\" ermitteln \n";
						    $compare=explode("=",$befehl[1]);
    						$sizeBefehl=sizeof($compare);
	    					//print_r($compare);
		    				$checkId = $this->lightManager->GetSwitchIdByName($compare[0]);		/* Light Manager ist context sensitive */
			    			if ($checkId == false)
				    			{
					    		$checkId = $this->lightManager->GetGroupIdByName($compare[0]);		/* Light Manager ist context sensitive */
						    	if ($checkId == false)
							    	{
								    if ($sizeBefehl>1)
									    {
    									$checkId = $this->lightManager->GetProgramIdByName($compare[0]);		/* Light Manager ist context sensitive */
	    								if ($checkId !== false)
		    								{
			    							echo "Vielleicht ein Program, dann ist ein Wertvergleich dabei, eingestellt auf ".$compare[1].".Vergleich mit ".GetValue($checkId)."  ".GetValueFormatted($checkId)."\n";
				    						$statusCheck = ($compare[1]==GetValueFormatted($checkId));
					    					IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifnot: Program '.$compare[0]."   ".$compare[1].".vergleich mit ".GetValueFormatted($checkId)."   ergibt ".($statusCheck?"OK":"NOK"));
						    				}
							    		}
								    }
							    else $statusCheck=$this->lightManager->GetValue($checkId);		// Wert von der Group	
							    }
						    else $statusCheck=$this->lightManager->GetValue($checkId);		// Wert vom Switch	
    						if ($checkId !== false)
	    						{
    							if ( strtoupper($befehl[0]) == "IFAND" )
	    							{
		    						$result["SWITCH"]=$result["SWITCH"] && $statusCheck;
			    					}
				    			else
					    			{	
						    		$result["SWITCH"]=$statusCheck;
							    	}
							    echo "Auswertung IF:".$befehl[1]." Wert ist ".$statusCheck." VariableID ist ".$checkId." (".IPS_GetName(IPS_GetParent($checkId))."/".IPS_GetName($checkId).")\n";	
							    }
						    else 
							    {
    							echo "Auswertung IF:".$befehl[1]." nicht bekannt, wird ignoriert.\n";	
	    						}
                            break;
                            }       // ende switch cond
						}	        // ende else andere befehlsdarstellung	
								
				break;
			case "IFDIF":
				$dif=(float)($befehl[1]);
				$oldvalue=$this->setNewValueIfDif($result["SOURCEID"],$result["STATUS"],$dif);
				echo "IFDIF Befehl erkannt : aktuelle Temperatur : ".($result["STATUS"])."    ".$dif." alter ifdif Wert : ".$oldvalue."\n";
				if (abs($result["STATUS"]-$oldvalue)<$dif) $result["SWITCH"]=false;
				//print_r($result);
				break;	
			case "IFANDNOT":
			case "IFORNOT":	
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
					case "MOVE":
						/* Nicht Schalten wenn wir zuhause sind */
						if ( self::isitmove() == true )
							{
							$result["SWITCH"]=false;
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if: Nicht Schalten, wir bewegen uns nicht ');
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
					case "HEATDAY":
						/* Nicht Schalten wenn der Kalender aktiv */
						if ( self::isitheatday() == true )
							{
							$result["SWITCH"]=false;
							echo 'Autosteuerung Befehl ifnot: Nicht Schalten, Heiztag '."\n";
							IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifnot: Nicht Schalten, Heiztag ');
							}
					default:
					  	/* weder light noch dark, wird ein IPSLight Variablenname sein. Wert ermitteln */
						$compare=explode("=",$befehl[1]);
						$sizeBefehl=sizeof($compare);
						//print_r($compare);
						$checkId = $this->lightManager->GetSwitchIdByName($compare[0]);		/* Light Manager ist context sensitive */
						if ($checkId == false)
							{
							$checkId = $this->lightManager->GetGroupIdByName($compare[0]);		/* Light Manager ist context sensitive */
							if ($checkId == false)
								{
								if ($sizeBefehl>1)
									{
									$checkId = $this->lightManager->GetProgramIdByName($compare[0]);		/* Light Manager ist context sensitive */
									if ($checkId !== false)
										{
										echo "Vielleicht ein Program, dann ist ein Wertvergleich dabei, eingestellt auf ".$compare[1].".Vergleich mit ".GetValue($checkId)."  ".GetValueFormatted($checkId)."\n";
										$statusCheck = ($compare[1]==GetValueFormatted($checkId));
										IPSLogger_Dbg(__file__, 'Autosteuerung Befehl ifnot: Program '.$compare[0]."   ".$compare[1].".vergleich mit ".GetValueFormatted($checkId)."   ergibt ".($statusCheck?"OK":"NOK"));
										}
									}
								}
							else $statusCheck=$this->lightManager->GetValue($checkId);		// Wert von der Group	
							}
						else $statusCheck=$this->lightManager->GetValue($checkId);		// Wert vom Switch	
						if ($checkId !== false)
							{
							$result["SWITCH"]=!$statusCheck;
							if ( strtoupper($befehl[0]) == "IFANDNOT" )
								{
								$result["SWITCH"]=$result["SWITCH"] && !$statusCheck;
								}
							else
								{	
								$result["SWITCH"]=!$statusCheck;
								}
							echo "Auswertung IF:".$befehl[1]." Wert ist ".$statusCheck." VariableID ist ".$checkId." (".IPS_GetName(IPS_GetParent($checkId))."/".IPS_GetName($checkId).")\n";	
							}
						else 
							{
							echo "Auswertung IF:".$befehl[1]." nicht bekannt, wird ignoriert.\n";	
							}
						break;
					}			
				break;
			default:
				echo "Function EvaluateCommand, Befehl unbekannt: \"".strtoupper($befehl[0])."\" ".$befehl[1]."   \n";
				break;				
			}  /* ende switch */
		return ($result);
		}	

	private function evalCom_IFDIF(&$befehl,&$result)
		{
		
		
		}

	private function evalCom_LEVEL(&$befehl,&$result)
		{
		$Befehl1=trim(strtoupper($befehl[1]));
		switch ($Befehl1)
			{
			case "VALUE":
				$result["LEVEL"]=(integer)$result["STATUS"];
				break;
			default:
				$result["LEVEL"]=(integer)$befehl[1];
				break;
			}
		}

	/***************************************
	 *
	 *  HeatControl, Stromheizung erfordert Regelfunktionen, die können entweder auf Switch oder Level gehen. 
	 *
	 *  Derzeit Switch implementiert
	 *
	 *******************************************************/


	public function ControlSwitchLevel(array &$result,$simulate=false)
		{
		/* Defaultwerte bestimmen, festlegen */
		$ergebnis="";
        if (isset($result["NAME"]) ) $ergebnis.=$result["NAME"]." ";  
        $ergebnisLang="ControlSwitchLevel ";
		$ControlModeHeat=true;	/* Regler fuer Heizen ist Default */
		$threshold=1;
		$nofrost=6;		
		if (isset($result["MODE"]) == true)
			{
 			if ($result["MODE"]=="COOL") $ControlModeHeat=false;
			}
		if (isset($result["THRESHOLD"]) == true)
			{
			$treshold=(float)$result["THRESHOLD"]; 
			}
		if (isset($result["NOFROST"]) == true)
			{
 			$nofrost=(float)$result["NOFROST"];
			}					
		if (isset($result["SETPOINT"]) == true)  
			{
			/* es wird wirklich eine Regelfunktion benötigt */
			$actTemp=(float)$result["STATUS"];	/* sicherheitshalber umwandeln, vielleicht doch einmal als string übernommen */
			$setTemp=(float)$result["SETPOINT"];
						
			IPSLogger_Dbg(__file__, 'Function ControlSwitchLevel Aufruf mit Wert: '.json_encode($result));
			$ergebnis .= "T ".$actTemp." SP ".$setTemp." NF ".$nofrost." T ".$threshold." IF:".($result["SWITCH"]?"ON":"OFF")." Vnow:".($result["VALUE"]?"ON":"OFF");
			if (isset($result["ON"]) == true) $ergebnis .= " ON: ".$result["ON"];
			if (isset($result["OFF"]) == true) $ergebnis .= " OFF ".$result["OFF"];
			
			if ($result["SWITCH"]==true)  /* Es soll die Temperatur geregelt werden, if Bedingung wurde erfüllt */
				{
				if ($ControlModeHeat==true)
					{
					/************************************************* 
					 *
					 * Regler , Heizen nur wenn Temp unter Sollwert und die if Bedingungen erfüllt sind 
					 *
					 * Entscheidung ob eingeschaltet oder ausgeschaltet wird abhängig vom aktuellen Zustand
					 *
					 ******************/
					if ($result["VALUE"]==true)
						{
						if ($actTemp > $setTemp ) 
							{
							/* Ist Temperatur über Sollwert gestiegen und heizt noch, SWITCH ist true, es wird ausgeschaltet daher ist der Wert OFF false */
							$ergebnis .=" |H1:T>SP| ";
							$result["SWITCH"]=true;
							unset($result["ON"]);
							$result["OFF"]="FALSE";
							}
						}
					else	
						{
						if ( $actTemp < ($setTemp-$treshold) ) 
							{
							$ergebnis .=" |H0:T<(SP-T)| ";							
							$result["SWITCH"]=true;
							unset($result["OFF"]);
							$result["ON"]="TRUE";
							}
						else
							{
							$ergebnis .=" |H0:T>=(SP-T)| ";							
							$result["SWITCH"]=true;
							unset($result["ON"]);
							$result["OFF"]="FALSE";
							}
						}
					}
				else
					{
					if ($result["VALUE"]==true)
						{
						if ($actTemp < $setTemp )
							{
							$ergebnis .=" |C1:T<SP| ";								
							$result["SWITCH"]=true;
							unset($result["ON"]);
							$result["OFF"]="FALSE";
							}
						}
					else	
						{
						if ($actTemp > ($setTemp+$treshold) ) 
							{
							$ergebnis .=" |C1:T>(SP+T)| ";							
							$result["SWITCH"]=true;
							unset($result["OFF"]);
							$result["ON"]="TRUE";
							}						
						}
					}					
				}	
			else	/* keine Temperaturregelung notwendig, aber nofrost Funktion machen */
				{
				if ( ($result["STATUS"]<$nofrost) && ($ControlModeHeat==true) )
					{
					/* Ist Temperatur unter Nofrost Wert gefallen, SWITCH ist true, es wird geschaltet und der Wert ist ON true */
					$result["SWITCH"]=true;
					unset($result["OFF"]);
					$result["ON"]="TRUE";
					}
				else
					{
					unset($result["OFF"]);
					unset($result["ON"]);
					}		
				}

			$ergebnis .= "  ==>> Ergebnis : ".($result["SWITCH"]?"ON":"OFF");	
			if (isset($result["ON"]) == true) $ergebnis .= " ON:".$result["ON"];
			if (isset($result["OFF"]) == true) $ergebnis .= " OFF:".$result["OFF"];	
			
			IPSLogger_Inf(__file__, $ergebnisLang." für ".$ergebnis."    ".json_encode($result));
	
			}
		else
			{
			echo "Keine Regelfunktion, Setpoint nicht gesetzt.\n";
			}		
			
		//echo $ergebnis."\n";
		$result["COMMENT"]=$ergebnis;			
		return ($result);	
		}

	/***************************************
	 *
	 * hier wird der Befehl umgesetzt. Abhängig von "MODULE" werden die Funktionen unterschiedlich umgesetzt
     * Folgende Module sind aktuell implementiert
     *  IPSLight, IPSHeat
     *  SamsungTV
     *  für die anderen Namen wird nachgeschaut ob das Modul installiert ist
     *      HarmonyHub
     *      SamsungTizen
     *      DenonAVRHTTP
     *
     * Implementierung IPSLight/IPSHeat
     *      Es wird entweder "OID" oder "NAME" für das TARGET übergeben
     *      Rückmeldung ist "COMMAND" mit einem passenden Befehl zum Rückgängig machen wenn Delay ausgewählt wurde
     *          und $ergebnis mit dem Resultat des Schaltbefehls, am Ende der Routine mit "COMMENT" übergeben
     *      Wenn Name gesetzt ist wird OID nachtraeglich ermittelt, verwendet für Sprachausgabe #Ziel#
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

	public function ExecuteCommand($result,$simulate=false)
		{
		echo "   Execute Command, Befehl nun abarbeiten und dann eventuell Sprachausgabe:\n";
		$ergebnis="";  // fuer Rückmeldung dieser Funktion als COMMENT
		$command="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
		IPSLogger_Dbg(__file__, 'Function ExecuteCommand Aufruf mit Wert: '.json_encode($result));

		if (isset($result["MODULE"])==false) $result["MODULE"]="";	// damit Switch bei leeren Befehlen keinen Fehler macht 
		$this->log->LogMessage("ExecuteCommand;Module ".$result["MODULE"]."; ".json_encode($result));		

		/* hier wird zwischen IPS Modulen der alten Generation und direkt implementierten Modulen geschaltet.
		 * IPS Module der neuen Generation werden unter Default bearbeitet
		 */
		switch ($result["MODULE"])
			{
			/******
			 *
			 *  hier wird zuerst geschaltet
			 *
			 *****************/
			case "IPSHeat":     // Heat wird in switchObject anders behandeln als Light
			case "IPSLight":
				if (isset($result["OID"]) == true)
					{
					IPSLogger_Dbg(__file__, 'OID '.$result["OID"]);
					$result["IPSLIGHT"]="None";
					$command.="SetValue(".$result["OID"].",false);";
					$result["COMMAND"]=$command;
					$ergebnis .= self::switchObject($result,$simulate);					
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
                	$ergebnisTyp=$this->getIdByName($result["NAME"]);
                    if (isset($ergebnisTyp["ID"]))
                        {   /* Der Name ist in IPSHeat oder IPSLight bekannt */
                        switch ($ergebnisTyp["TYP"])
                            {
                            case "Switch":
      					    	if (isset($result["NAME_EXT"])==true)
		      				    	{
				      			    IPSLogger_Dbg(__file__, 'Wert '.$name.' ist Wert für einen Schalter. ');
    						       	$result["IPSLIGHT"]=$result["NAME_EXT"];						
          							if ($result["MODULE"]=="IPSLight") $result["OID"] = $this->lightManager->GetSwitchIdByName($name);
                                    else $result["OID"] = $this->heatManager->GetSwitchIdByName($name);
		          					$value=GetValue($result["OID"]);
			    	      			if ($result["IPSLIGHT"]=="#COLOR") 	{	$command.='$lightManager->SetRGB('.$result["OID"].",".$value.");"; }	
				    		      	if ($result["IPSLIGHT"]=="#LEVEL") 	{	$command.='$lightManager->SetValue('.$result["OID"].",".$value.");"; }	
      				    			if ($result["IPSLIGHT"]=="None") 	{	$command.='SetValue('.$result["OID"].",".$value.");"; }	
		      			    		$command.="IPSLogger_Dbg(__file__, 'Delay abgelaufen von ".$name."');";
				      		    	$result["COMMAND"]=$command;
						      	    $ergebnis .= self::switchObject($result,$simulate);	
          							//echo "**** Aufruf Switch Ergebnis command \"".$result["IPSLIGHT"]."\"   ".str_replace("\n","",$command)."\n";										
	    	      					}
		    		      		else
			    			      	{	 				
      			    				IPSLogger_Dbg(__file__, 'Wert '.$name.' ist ein Schalter. ');
		      		    			$command.="IPSLight_SetSwitchByName(\"".$name."\", false);\n";
          							if ($result["MODULE"]=="IPSLight") 
                                        {
                                        $command.="IPSLight_SetSwitchByName(\"".$name."\", false);\n";
                                        $result["OID"] = $this->lightManager->GetSwitchIdByName($name);
                                        }
                                    else 
                                        {
                                        $command.="IPSHeat_SetSwitchByName(\"".$name."\", false);\n";
                                        $result["OID"] = $this->heatManager->GetSwitchIdByName($name);
                                        }					
				      	    		$result["COMMAND"]=$command;
						          	$result["IPSLIGHT"]="Switch";
      							    $ergebnis .= self::switchObject($result,$simulate);
    		       					}                            
                               break;   
                           case "Group":
			    		        IPSLogger_Dbg(__file__, 'Wert '.$name.' ist eine Gruppe. ');
                                if ($result["MODULE"]=="IPSLight") 
                                    {
      				    			$command.="IPSLight_SetGroupByName(\"".$name."\", false);\n";
                                    $result["OID"] = $this->lightManager->GetGroupIdByName($name);
                                    }
                                else 
                                    {
       				    			$command.="IPSHeat_SetGroupByName(\"".$name."\", false);\n";
                                    $result["OID"] = $this->heatManager->GetGroupIdByName($name);                                					
                                    }
	  				    		$result["COMMAND"]=$command;
		  				    	$result["IPSLIGHT"]="Group";	
			   				    $ergebnis .= self::switchObject($result,$simulate);	
                                break;                            
                          case "Program":
	    				        IPSLogger_Dbg(__file__, 'Wert '.$name.' ist ein Programm. ');
                          	    echo "Hier ist die IPSLight/Heat Programm-Abarbeitung. Ergebnistyp: \n"; print_r($ergebnisTyp);
                                if ($result["MODULE"]=="IPSLight") $result["OID"] = $this->lightManager->GetProgramIdByName($name);
                                else $result["OID"] = $this->heatManager->GetProgramIdByName($name);
				    			if (isset($result["ON"]))
					    			{
						    		switch (strtoupper($result["ON"]))
							    		{
								    	case "START":
                                           if ($ergebnisTyp["MODULE"]=="IPSHeat")
                                                {
   									    	    $command.="IPSHeat_SetProgramName(\"".$name."\",0);\n";
					    					    if ($simulate==false) IPSHeat_SetProgramName($name,0);	
                                                }
                                           else     
                                                {
   									    	    $command.="IPSLight_SetProgramName(\"".$name."\",0);\n";
					    					    if ($simulate==false) IPSLight_SetProgramName($name,0);	
                                                }
    											break;										
	    								case "NEXT":
                                           if ($ergebnisTyp["MODULE"]=="IPSHeat")
                                                { 
												echo "IPSHeat_SetProgramNextByName($name).\n";                                       
    	    									$command.="IPSHeat_SetProgramName(\"".$name."\",0);\n";
			    		    					if ($simulate==false) IPSHeat_SetProgramNextByName($name);
                                                }
                                           else     
                                                {
   									    	    $command.="IPSLight_SetProgramName(\"".$name."\",0);\n";
					    					    if ($simulate==false) IPSLight_SetProgramNextByName($name);	
                                                }
				    							break;
				    					case "END":
					    				case "PREV":
						    			default:
							    			break;
								    	}
                                    }    
	    					    $result["COMMAND"]=$command;
    		    				$result["IPSLIGHT"]="Program";									
	    						//print_r($result);
                                break;                                
                           default:
                                break;
                            }
                        }
                    else
                        {
						/* Name nicht bekannt */
						$result["IPSLIGHT"]="None";
                        }    
                    /*
					$resultID=@IPS_GetVariableIDByName($name,$this->switchCategoryId);
					if ($resultID==false)
						{
						$resultID=@IPS_GetVariableIDByName($name,$this->groupCategoryId);
						if ($resultID==false)
							{
							$resultID=@IPS_GetVariableIDByName($name,$this->prgCategoryId);
							if ($resultID==false)
								{

								$result["IPSLIGHT"]="None";
								}
							else 
								{
								IPSLogger_Dbg(__file__, 'Wert '.$name.' ist ein Programm. ');
								$command.="IPSLight_SetProgramNextByName(\"".$name."\");\n";
								$result["COMMAND"]=$command;
								$result["IPSLIGHT"]="Program";
								if ($simulate==false)
									{
                                    echo "Hier ist die IPSLight Programmabarbeitung.\n";
									IPSLight_SetProgramNextByName($name);
									}
								}
							}
						else   
							{
							IPSLogger_Dbg(__file__, 'Wert '.$name.' ist eine Gruppe. ');
							$command.="IPSLight_SetGroupByName(\"".$name."\", false);\n";
							$result["COMMAND"]=$command;
							$result["IPSLIGHT"]="Group";	
							$ergebnis .= self::switchObject($result,$simulate);			   	 	
							}
						}
					else     
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
							$ergebnis .= self::switchObject($result,$simulate);	
							//echo "**** Aufruf Switch Ergebnis command \"".$result["IPSLIGHT"]."\"   ".str_replace("\n","",$command)."\n";										
							}
						else
							{	 				
							IPSLogger_Dbg(__file__, 'Wert '.$name.' ist ein Schalter. ');
							$command.="IPSLight_SetSwitchByName(\"".$name."\", false);\n";
							$result["COMMAND"]=$command;
							$result["IPSLIGHT"]="Switch";
							$ergebnis .= self::switchObject($result,$simulate);
							}			
						}   
                */ 
					} 
				else
					{
					}
				break;
			case "SamsungTV":
				/* Fernseher der alten Generation behandlen, hier gibt es kein Modul, mach ich direkt hier */
				$DENONconfig=$this->DENONsteuerung->Configuration($result["NAME"]);
				if ($DENONconfig !== false)
					{
					//print_r($DENONconfig);
					$instanzID=$this->availableModuleDevice("Client Socket",$result["DEVICE"]);
					if ($instanzID !== false)
						{
						echo "Geraet ".$result["DEVICE"]." mit ID ".$instanzID." bekannt. Befehl : ".$result["COMMAND"]."\n";
						if (isset($DENONconfig["IPADRESSE"])==true)
							{
							$ping = Sys_Ping($DENONconfig["IPADRESSE"], 1000);
							if ($ping ==false)
								{
							    echo "<FONT SIZE='+3' COLOR=red><br>FERNSEHER REAGIERT NICHT</FONT>\n";
								$this->log->LogMessage("ExecuteCommand; Samsung TV reagiert nicht auf sys_ping.");		
							    }
							else
								{
								$samsungstatus = IPS_GetInstance($instanzID);
								if ($samsungstatus['InstanceStatus']>102)
									{
								    CSCK_SetOpen($instanzID, true);
								    IPS_ApplyChanges($instanzID);
								    $samsungstatus = IPS_GetInstance($instanzID);
								    if ($samsungstatus['InstanceStatus']>102)
										{
								        echo "<FONT SIZE='+3' COLOR=red><br>FERNSEHER REAGIERT NICHT</FONT>";
										$this->log->LogMessage("ExecuteCommand; Samsung TV reagiert nicht auf Open Socket.");		
        								}
									else
										{
										if ($simulate==false)
											{
											$src = "10.0.0.145"; 			/* ip des IP Symcon, dummy Wert zur Wiedererkennung */
											$mac = "e4-e0-c5-25-66-27"; /* mac des IP Symcons, dummy Wert zur Wiedererkennung */	
											$remote = "Perl Samsung Remote";	/* Name der Fernbedienung, erscheint am Fernseher und muss quittiert werden */	
											$app="iphone..iapp.samsung";		/* tut so als wäre IPS ein iPhone */
											$tv = "UE55C6700"; 			// iphone.UE55C6700.iapp.samsung										
											$key=$result["COMMAND"];
																												
											CSCK_SetOpen($instanzID, true);
											$msg = chr(0x64).chr(0x00).chr(strlen(base64_encode($src))).chr(0x00).base64_encode($src).chr(strlen(base64_encode($mac))).chr(0x00).base64_encode($mac).chr(strlen(base64_encode($remote))).chr(0x00).base64_encode($remote);
											$pkt = chr(0x00).chr(strlen($app)).chr(0x00).$app.chr(strlen($msg)).chr(0x00).$msg;
											CSCK_SendText($instanzID,$pkt);
											$this->log->LogMessage("ExecuteCommand; Send Data to Samsung TV ".$instanzID.": ".$app." ".$src." ".$mac."  ".$remote.".");		

											$msg = chr(0x00).chr(0x00).chr(0x00).chr(strlen(base64_encode($key))).chr(0x00).base64_encode($key);
											$pkt = chr(0x00).chr(strlen($tv)).chr(0x00).$tv.chr(strlen($msg)).chr(0x00).$msg;
											CSCK_SendText($instanzID,$pkt);
											$this->log->LogMessage("ExecuteCommand; Send Data to Samsung TV ".$instanzID.": ".$tv." ".$key.".");		
	
											CSCK_SetOpen($instanzID, false);
											}										
										}	
									}								
								//print_r($samsungstatus);
								}		
							}	
						}
					else												
						{
						echo "Geraet ".$result["DEVICE"]." nicht bekannt.\n"; 
						}																					
					}
				else 
					{
					echo "Geraet ".$result["NAME"]." in DENONsteuerung Config nicht eingetragen.\n";
					$DENONconfig=$this->DENONsteuerung->Configuration();
					if ($DENONconfig !== false)
						{
						print_r($DENONconfig);
						}
					}
					
				break;	
			case "":			
				/* kein Modul definiert, auch nicht Default Wert, sicherheitshalber nichts machen 
				 * ausser wenn OID gesetzt ist, das funktioniert immer. 
				 */
				if (isset($result["OID"]) == true)
					{
					IPSLogger_Dbg(__file__, 'OID '.$result["OID"]);
					$result["IPSLIGHT"]="None";
					$command.="SetValue(".$result["OID"].",false);";
					$result["COMMAND"]=$command;
					$ergebnis .= self::switchObject($result,$simulate);					
					}			
				break;	
			default:
				/* nachschauen ob das gesuchte Modul überhaupt installiert ist */
				echo "Fehler, ".$result["MODULE"]." nicht bekannt.\n";
				$instanzID=$this->availableModuleDevice($result["MODULE"],$result["DEVICE"]);				
				if ($instanzID !== false)
					{
					echo "Geraet ".$result["DEVICE"]." mit ID ".$instanzID." bekannt. Befehl : ".$result["COMMAND"]."\n";
					$this->log->LogMessage("ExecuteCommand;Module ".$result["MODULE"].";Geraet ".$result["DEVICE"]." mit ID ".$instanzID." bekannt. Befehl : ".$result["COMMAND"]);					
					switch ($result["MODULE"])
						{
						case "DenonAVRHTTP": 
							if ($simulate==false) DAVRH_SendHTTPCommand($instanzID,$result["COMMAND"]);
							break;
						case "SamsungTizen":
							$this->log->LogMessage("ExecuteCommand;Module ".$result["MODULE"]."; ".$instanzID."  ".$result["COMMAND"]);	
							if ($simulate==false) SamsungTizen_SendKeys($instanzID,$result["COMMAND"]);
							break;
						case "HarmonyHub":	
							/* noch Geraetenamen Instanz ermitteln */
							$DENONconfig=$this->DENONsteuerung->Configuration();
							$name=false;
							foreach ($DENONconfig as $device)
								{
								if ( ($result["MODULE"]==$device["TYPE"]) && ($result["DEVICE"]==$device["INSTANZ"]) ) $name=$device["NAME"];
								}
							print_r($DENONconfig);
							if ($name !== false)
								{
								$Harmony = @IPS_GetCategoryIDByName($name, $this->dataCategoryIdDenon);
								if ($Harmony !== false)
									{
									echo "HarmonyHub Verzeichnis innerhalb data.DENONsteuerung : ".$name."  liegt auf ".$Harmony." (".IPS_GetName($Harmony).") \n";
									$Childrens=IPS_GetChildrenIDs($Harmony);
									foreach ($Childrens as $children)
										{
										if ( (IPS_GetObject($children)["ObjectType"]==1) && (IPS_GetObject($children)["ObjectName"]==$result["NAME"]) )
											{
											$Device=IPS_getName($children);
											echo "HarmonyHub Geraet Verzeichnis innerhalb data.DENONsteuerung.".$name." liegt auf ".$children." (".IPS_GetName($children).") \n";
											if ($simulate==false) LHD_Send($children, $result["COMMAND"]);
											}
										}
									}
								}
							break;								
						default:
							break;		
						}
					}	
				else												
					{
					echo "Geraet ".$result["DEVICE"]." oder Module ".$result["MODULE"]." nicht bekannt.\n"; 
					}
				break;
			}
					
		/***********************************************************
		 *
		 * und dann gesprochen
		 *
		 * vordefinierte zwischen Hashtags gekennzeichnete Texte werden ausgewertet
		 * #WERT#  #WERTBOOL#  #CHANGE#
		 * nur Event Trigger Variablen vom Typ Variable werden ausgewertet. Es wird das Standard oder das Custom Profil ausgewertet
		 *
		 *****************/

		if (isset($result["SPEAK"]) == true)
			{
			/* parse Sprachtext auf eingebettete Variablen und ersetze sie mit echten Werten */
			$start=0;
			do {	/* alle # Werte bearbeiten */
				//echo "Bearbeite Text ab Pos ".$start." :".substr($result["SPEAK"],$start)."\n";
				$pos=strpos(substr($result["SPEAK"],$start),"#");
				$len=strpos(substr($result["SPEAK"],$start+$pos+1),"#");
				if ( ( $pos !== false) && ( $len !== false) )
					{
					/* gültiger Wert zwischen zwei # Zeichen erkannt */
					$var=strtoupper(substr($result["SPEAK"],$start+$pos+1,$len));
					//echo "  eingebettete Variable ".$var." Pos : ".$pos." Len ".$len." erkannt. \n";
					$part1=substr($result["SPEAK"],0,$start+$pos);
					$part2=substr($result["SPEAK"],$start+$pos+$len+2);
					switch ($var)
						{
                        case "ZIEL":
                            $wert=$this->getSpeakWert($result["OID"],GetValue($result["OID"]));
                            break;
						case "WERT":
                            $wert=$this->getSpeakWert($result["SOURCEID"],$result["STATUS"]);
							break;
						case "WERTBOOL":
							if ( (IPS_GetVariable($result["SOURCEID"])["VariableType"])==2)
								{
								$temperatur=$result["STATUS"];
								$wert=floor($temperatur)." Komma ".floor(($temperatur-floor($temperatur))*10);
								}
							else $wert=($result["SOURCEID"]?"Aus":"Ein");
							break;
						case "":
							$wert=GetValueFormatted($result["SOURCEID"]);
							break;	
						case "CHANGE":
							$wert="geändert";
							if ( isset($result["OLDSTATUS"]) )
								{
								if ($result["STATUS"]>$result["OLDSTATUS"]) $wert="erhöht";
								if ($result["STATUS"]<$result["OLDSTATUS"]) $wert="gesenkt";
								}
							break;
						default:
							break;
						}
					$result["SPEAK"]=$part1.$wert.$part2;	
					$start+=$pos+strlen($wert);					
					//echo "   ".$var." Pos : ".$pos." Len ".$len."\n";
					}
				else $pos = false;	/* sicherheitshalber hier Ende festlegen wenn nur ein # erkannt wurde */	
				}  while ($pos !== false);
				
			if ($result["SWITCH"]===true)			/* nicht nur die Schaltbefehle mit If Beeinflussen, auch die Sprachausgabe */
				{
				if ( ( (self::isitsleep() == false) || (self::getFunctions("SilentMode")["VALUE"] == 0) ) &&  (self::getFunctions("SilentMode")["VALUE"] != 1) )
					{
                    //print_r($result);
					if ( isset($result["LOUDSPEAKER"]) )
						{
						echo "   Es wird am Amazon Echo ".IPS_GetName($result["LOUDSPEAKER"])." gesprochen : ".$result["SPEAK"]."\n";
						if ($simulate==false) 
                            {
                            //EchoRemote_TextToSpeech($result["LOUDSPEAKER"], $result["SPEAK"]);
                            tts_play($result["LOUDSPEAKER"],$result["SPEAK"],'',2); //tts_play kann mehrere Lautsprecher
                            }
                        }																																																					
					else
                        { 
					    echo "  Es wird am Default Lautsprecher gesprochen : ".$result["SPEAK"]."\n";                            
                        if ($simulate==false) tts_play(1,$result["SPEAK"],'',2);
						}
					}	
				}
			}
		$result["COMMENT"]=$ergebnis;			
		return ($result);							
		}

    private function getSpeakWert($oid, $value)   
        {
		$typObj=IPS_GetObject($oid)["ObjectType"];
		$formWert="";
		echo "Speak Wert ".$value." von OID ".$oid." (".IPS_GetName($oid).") vom Typ ".$typObj."   (";
		//Objekt-Typ (0: Kategorie, 1: Instanz, 2: Variable, 3: Skript, 4: Ereignis, 5: Media, 6: Link)
		switch ($typObj)
			{
								case 0: echo "Kategorie"; break;
								case 1: echo "Instanz"; break;
								case 2: 
									echo "Variable->"; 
									$typWert=IPS_GetVariable($oid)["VariableType"];
									switch ($typWert)
										{
										case 0: echo "Boolean"; break;
										case 1: echo "Integer"; break;
										case 2: echo "Float"; break;
										case 3: echo "String"; break;
										default:  echo "unknown"; break;
										}
									$formWert=IPS_GetVariable($oid)["VariableProfile"].IPS_GetVariable($oid)["VariableCustomProfile"];										
									break;
								case 3: echo "Skript"; break;
								case 4: echo "Ereignis"; break;
								case 5: echo "Medie"; break;
								case 6: echo "link"; break;
								default:  echo "unknown"; break;
			}
		if ($typObj==2)
								{
								/* Sprachausgabe einer Variable	*/
								if ($formWert == "") echo ")\n";	
								else echo "   Profil ".$formWert." , formatiert \"".GetValueFormatted($oid)."\")\n";
								if ($typWert==2)
									{
									/* vom Typ Float */
									$temperatur=$value;
									$wert=floor($temperatur)." Komma ".floor(($temperatur-floor($temperatur))*10);
									}
								else $wert=$value;
								switch ($formWert)
									{
									case "~Temperature":
									case "Temperatur":
										$wert.=" Grad";
										break;
									default: 
										$wert = GetValueFormatted($oid);
										break;
									}																				
								//echo "   ".$var." Pos : ".$pos." Len ".$len."\n";
								}
		else $wert="Achtung Fehler, Wert ist keine Variable";
            
        return ($wert);
        }

	private function availableModuleDevice($module,$device)
		{
		$instanzID=false;
		if (isset($this->availableModules[$module]) == true)
			{
			$gid=$this->availableModules[$module];
			echo "Module ".$module." mit GUID ".$gid." bekannt.\n";
			$instanzlist=(IPS_GetInstanceListByModuleID($gid));
			foreach ($instanzlist as $instanz)
				{
				if ($device==IPS_GetName($instanz)) $instanzID=$instanz;
				}
			if ($instanzID !== false)
				{
				//echo "Geraet ".$device." mit ID ".$instanzID." bekannt.\n";
				}
			else												
				{
				echo "Geraet ".$device." nicht bekannt.\n"; 
				}
			}
		else
			{
			echo "Module ".$module." nicht bekannt.\n";
			}		
		return $instanzID;
		}

	/***************************************
	 *
	 * hier wird geschaltet. Funktion funktioniert für Modul IPSLight und IPSHeat
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
        //echo "SwitchObject : \n"; print_r($result);
		$ergebnis="";
		IPSLogger_Dbg(__file__, 'SwitchObject :  '.json_encode($result));	
		if ($result["SWITCH"]===true)
			{
			if (isset($result["ON"])==true)
				{
				$result["ON"]=strtoupper($result["ON"]);
				if ($result["ON"]=="FALSE")
					{
					if ($simulate==false)			/* Bei simulate nicht schalten */
						{
						if ($result["IPSLIGHT"]=="None") 	{	SetValue($result["OID"],false); }
                        elseif ($result["MODULE"]=="IPSLight")
                            {
						    if ($result["IPSLIGHT"]=="Group")  	{	IPSLight_SetGroupByName($result["NAME"],false);  }
						    if ($result["IPSLIGHT"]=="Switch") 	{	IPSLight_SetSwitchByName($result["NAME"],false); }
                            }
                        else												
                            {
						    if ($result["IPSLIGHT"]=="Group")  	{	IPSHeat_SetGroupByName($result["NAME"],false);  }
						    if ($result["IPSLIGHT"]=="Switch") 	{	IPSHeat_SetSwitchByName($result["NAME"],false); }
                            }
						}
					else $ergebnis .= "Set Switch, Group or Value auf false.\n";
					}	
				if ($result["ON"]=="TRUE")
					{
					if ($simulate==false)			/* Bei simulate nicht schalten */
						{					
						if ($result["IPSLIGHT"]=="None") 	{	SetValue($result["OID"],true); }
                        elseif ($result["MODULE"]=="IPSLight")
                            {                          
    						if ($result["IPSLIGHT"]=="Group")  	{	IPSLight_SetGroupByName($result["NAME"],true); }
	    					if ($result["IPSLIGHT"]=="Switch")	{	IPSLight_SetSwitchByName($result["NAME"],true); } 
		    				if ($result["IPSLIGHT"]=="#COLOR") 	{	$this->lightManager->SetRGB($result["OID"], $result["VALUE_ON"]); }	
			    			if ($result["IPSLIGHT"]=="#LEVEL") 	{	$this->lightManager->SetValue($result["OID"], $result["VALUE_ON"]); }	
                            }
                        else
                            {
    						if ($result["IPSLIGHT"]=="Group")  	{	IPSHeat_SetGroupByName($result["NAME"],true); }
	    					if ($result["IPSLIGHT"]=="Switch")	{	IPSHeat_SetSwitchByName($result["NAME"],true); } 
		    				if ($result["IPSLIGHT"]=="#COLOR") 	{	$this->heatManager->SetRGB($result["OID"], $result["VALUE_ON"]); }	
			    			if ($result["IPSLIGHT"]=="#LEVEL") 	{	$this->heatManager->SetValue($result["OID"], $result["VALUE_ON"]); }	
                            }    
						}
					else 
                        {
                        if (isset($result["NAME"])) $ergebnis .= "Set ".$result["NAME"]." (".$result["IPSLIGHT"].") Switch, Group or Value auf true und Level auf Wert (ON).\n";
                        else $ergebnis .= "Set ".$result["OID"]." (".$result["IPSLIGHT"].") Switch, Group or Value auf true und Level auf Wert (ON).\n";
                        }
					}
				}	
			if (isset($result["OFF"])==true)
				{
				$result["OFF"]=strtoupper($result["OFF"]);					
				if ($result["OFF"]=="FALSE")
					{
					if ($simulate==false)			/* Bei simulate nicht schalten */
						{	
						if ($result["IPSLIGHT"]=="None") 	{	SetValue($result["OID"],false); }	
                        elseif ($result["MODULE"]=="IPSLight")
                            {                           
						    if ($result["IPSLIGHT"]=="Group") 	{	IPSLight_SetGroupByName($result["NAME"],false); }
						    if ($result["IPSLIGHT"]=="Switch") 	{	IPSLight_SetSwitchByName($result["NAME"],false); }
                            }
                        else												
                            {
						    if ($result["IPSLIGHT"]=="Group")  	{	IPSHeat_SetGroupByName($result["NAME"],false);  }
						    if ($result["IPSLIGHT"]=="Switch") 	{	IPSHeat_SetSwitchByName($result["NAME"],false); }
                            }                            
						}
					else $ergebnis .= "Set Switch, Group or Value auf false.\n";
					}
				if ($result["OFF"]=="TRUE")
					{
					if ($simulate==false)			/* Bei simulate nicht schalten */
						{	
						if ($result["IPSLIGHT"]=="None") 	{	SetValue($result["OID"],false); }
                        elseif ($result["MODULE"]=="IPSLight")
                            {
                            if ($result["IPSLIGHT"]=="Group") 	{	IPSLight_SetGroupByName($result["NAME"],true); }
						    if ($result["IPSLIGHT"]=="Switch") 	{	IPSLight_SetSwitchByName($result["NAME"],true); }
						    if ($result["IPSLIGHT"]=="#COLOR") 	{	$this->lightManager->SetRGB($result["OID"], $result["VALUE_OFF"]); }
						    if ($result["IPSLIGHT"]=="#LEVEL") 	{	$this->lightManager->SetValue($result["OID"], $result["VALUE_OFF"]); }						
                            }
                        else
                            {
    						if ($result["IPSLIGHT"]=="Group")  	{	IPSHeat_SetGroupByName($result["NAME"],true); }
	    					if ($result["IPSLIGHT"]=="Switch")	{	IPSHeat_SetSwitchByName($result["NAME"],true); } 
		    				if ($result["IPSLIGHT"]=="#COLOR") 	{	$this->heatManager->SetRGB($result["OID"], $result["VALUE_OFF"]); }	
			    			if ($result["IPSLIGHT"]=="#LEVEL") 	{	$this->heatManager->SetValue($result["OID"], $result["VALUE_OFF"]); }	
                            }															
						}
					else $ergebnis .= "Set ".$result["NAME"]." Switch, Group or Value auf true und Level auf Wert (OFF).\n";	
					}
				}
			}
        echo "SwitchObject Ergebnis : "; print_r($ergebnis);
		return($ergebnis);		
		}

	/*************************************************************************/
	/* bereits obsolet, da nur für IPSLight funktioniert 
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
    */

    /* Zusammenfassung der Befehle die nach der execute Funktion kommen */

	public function timerCommand($result,$simulate=false)
		{
        echo "Aufruf timerCommand mit :".json_encode($result)."\n";
		/********************
		 *
		 * Timer wird einmal aufgerufen um nach Ablauf wieder den vorigen Zustand herzustellen.
		 * Bei DIM Befehl anders, hier wird der unter DIM#LEVEL definierte Zustand während der Zeit DIM#DELAY versucht zu erreichen
		 * 
		 * Delay ist davon unabhängig und kann zusätzlich verwendet werden
		 *
		 * nur machen wenn if condition erfüllt ist, andernfalls wird der Timer ueberschrieben
		 *
		 ***************************************************************/					
		if ($result["SWITCH"]===true)
			{
			if (isset($result["DIM"])==true)
				{
				echo "**********Execute Command Dim mit Level : ".$result["DIM#LEVEL"]." und Time : ".$result["DIM#TIME"]." Ausgangswert : ".$result["VALUE_ON"]." für OID ".$result["OID"]."\n";
				$value=(integer)(($result["DIM#LEVEL"]-$result["VALUE_ON"])/10);
				$time=(integer)($result["DIM#TIME"]/10);
				$EreignisID = @IPS_GetEventIDByName($result["NAME"]."_EVENT_DIM", $this->CategoryIdApp);
			
				$befehl="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
				$befehl.='$value=$lightManager->GetValue('.$result["OID"].')+'.$value.";\n";
				$befehl.='if ($value<=('.$result["DIM#LEVEL"].')) {'."\n";
				$befehl.='  $lightManager->SetValue('.$result["OID"].',$value); } '."\n".'else {'."\n";
				$befehl.='  IPS_SetEventActive('.$EreignisID.',false);}'."\n";
				$befehl.='IPSLogger_Dbg(__file__, "Command Dim '.$result["NAME"].' mit aktuellem Wert : ".$value."   ");'."\n";
                echo "===================\n".$befehl."\n===================\n";
				echo "   Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$result["COMMAND"])."\n";
				echo "   Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$befehl)."\n";
				/* Timer wird insgesamt 10 mal aufgerufen, d.h. increment ist Differenz aktueller Wert zu Zielwert. Zeit zwischen den Timeraufrufen ist delay durch 10 */		
				if ($simulate==false)
					{
					setDimTimer($result["NAME"],$time,$befehl);
					}
				}
			if (isset($result["DELAY"])==true)
				{
				if ($result["DELAY"]>0)
					{
					echo "Execute Command Delay, Script für Timer ".$result["NAME"]." für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$result["COMMAND"])."\n";
					//print_r($result);
					if ($simulate==false)
						{
						setEventTimer($result["NAME"],$result["DELAY"],$result["COMMAND"]);
						}
					}	
				}
			}	

        }

    /* Vereinfachung der Timeransteuerung in der AWS */    

    public function switchAWS($switch, $scene)
        {
        $status=false;
		$statusID  = CreateVariable($scene["NAME"]."_Status",  1, $this->AnwesenheitssimulationID, 0, "AusEin",null,null,""  );            
		$counterID = CreateVariable($scene["NAME"]."_Counter", 1, $this->AnwesenheitssimulationID, 0, "",null,null,""  );            		
		if ( strtoupper($scene["TYPE"]) == "AWS" )  $text="AWS für ";
        else $text="TIMER für ";
        if ($switch)
            {
            /* IPS_Light einschalten. timer schaltet selbsttaetig wieder aus */
			SetValue($statusID,true);            
									if (isset($scene["EVENT_IPSLIGHT"]))
										{
										$text.='IPSLight Switch '.$scene["EVENT_IPSLIGHT"].' einschalten. ';
										$this->log->LogMessage($text.json_encode($scene));
										IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], true);
										$command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSLight_SetSwitchByName("'.$scene["EVENT_IPSLIGHT"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer für IPSLight Schalter '.$scene["EVENT_IPSLIGHT"].' wurde abgeschlossen.");';
										}
									else
										{
										if (isset($scene["EVENT_IPSLIGHT_GRP"]))
											{
											$text.='IPSLight Group '.$scene["EVENT_IPSLIGHT_GRP"].' einschalten. ';
											$this->log->LogMessage($text.json_encode($scene));
											IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], true);
											$command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSLight_SetGroupByName("'.$scene["EVENT_IPSLIGHT_GRP"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer AWS Script für IPSLight Schalter '.$scene["EVENT_IPSLIGHT_GRP"].' wurde abgeschlossen.");';
											}
										}
                                    $status=$this->getEventTimerStatus($scene["NAME"]);     // keine Textausgabe wenn Timer bereits gesetzt    
									if ($scene["EVENT_CHANCE"]==100)
										{
										//echo "feste Ablaufzeit, keine anderen Parameter notwendig.\n";
										setEventTimer($scene["NAME"],$this->timeStop-$this->now,$command);
                                        $text.=' Timer gesetzt auf '.date("D d.m.Y H:i",($this->timeStop));
										}
									else
										{
										SetValue($counterID,$scene["EVENT_DURATION"]);
										setEventTimer($scene["NAME"],$scene["EVENT_DURATION"]*60,$command);
                                        $text.=' Timer gesetzt auf '.date("D d.m.Y H:i",($this->now+$scene["EVENT_DURATION"]*60));
										}
									$this->log->LogMessage('Befehl aktiv, '.$text);
            }
        else
            { 
            /* IPS_Light ausschalten. */
			SetValue($statusID,false);                             

								if (isset($scene["EVENT_IPSLIGHT"]))
									{
									$text.='IPSLight Switch '.$scene["EVENT_IPSLIGHT"].' ausgeschaltet.';
									$this->log->LogMessage($text.json_encode($scene));
									IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], false);
									}
								else
									{
									if (isset($scene["EVENT_IPSLIGHT_GRP"]))
										{
										$text.='IPSLight Group '.$scene["EVENT_IPSLIGHT_GRP"].'ausgeschaltet.';								
										$log_Autosteuerung->LogMessage($text.json_encode($scene));								
										IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
										}
									}
								//SetValue($StatusAnwesendZuletztID,false);	
            }
        if ($status) return("");
        else return($text);
        }   

    function setEventTimer($name,$delay,$command)
	    {
    	echo "Jetzt wird der Timer gesetzt : ".$name."_EVENT"."\n";
	    IPSLogger_Dbg(__file__, 'Autosteuerung, Timer setzen : '.$name.' mit Zeitverzoegerung von '.$delay.' Sekunden. Befehl lautet : '.str_replace("\n","",$command));	
    	$now = time();
	    $EreignisID = @IPS_GetEventIDByName($name."_EVENT",  $this->CategoryIdApp);
    	if ($EreignisID === false)
	    	{ //Event nicht gefunden > neu anlegen
		    $EreignisID = IPS_CreateEvent(1);
    		IPS_SetName($EreignisID,$name."_EVENT");
	    	IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
		    }
    	IPS_SetEventActive($EreignisID,true);
	    IPS_SetEventCyclic($EreignisID, 1, 0, 0, 0, 0,0);
    	/* EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
	    /* EreignisID, 1 einmalig,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
    	IPS_SetEventCyclicTimeBounds($EreignisID,$now+$delay,0);
	    IPS_SetEventCyclicDateBounds($EreignisID,$now+$delay,0);
    	IPS_SetEventScript($EreignisID,$command);
	    }

    function getEventTimerStatus($name)
	    {
        $result=false;
    	$EreignisID = @IPS_GetEventIDByName($name."_EVENT", $this->CategoryIdApp);
        //echo "Timer ID : ".$EreignisID."   (".IPS_GetName($EreignisID)."/".IPS_GetName(IPS_GetParent($EreignisID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($EreignisID))).")\n";
        if ($EreignisID !== false)
            {
            $status=IPS_GetEvent($EreignisID);
            //print_r($status);
            //echo $status["EventActive"]."   ".date("Y-m-d H:i:s",$targetTime)."   ".$status["CyclicDateFrom"]["Day"].".".$status["CyclicDateFrom"]["Month"].".".$status["CyclicDateFrom"]["Year"]." ".$status["CyclicTimeFrom"]["Hour"].":".$status["CyclicTimeFrom"]["Minute"].":".$status["CyclicTimeFrom"]["Second"]."\n";
            $targetTime=strtotime($status["CyclicDateFrom"]["Day"].".".$status["CyclicDateFrom"]["Month"].".".$status["CyclicDateFrom"]["Year"]." ".$status["CyclicTimeFrom"]["Hour"]
                .":".$status["CyclicTimeFrom"]["Minute"].":".$status["CyclicTimeFrom"]["Second"]);
            if ( ($status["EventActive"]==true) && (time()<=$targetTime) ) $result=true;
            else $result=false;    
            }
        return($result);
        }


    /* Umsetzung von Farbennamen in den Hexcode */     

	public function GetColor($Colorname) 
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


/*****************************************************************************************************
 *
 * Funktionen innerhalb der Autosteuerung
 *
 **************************************************************************************************************/

abstract class AutosteuerungFunktionen
	{
	
	/********************************************************************
	 *
	 * Funktionen für Konstruktor, es gibt ein eigenes Logfile, Filename inklusive Pfad muss übergeben werden, Input Variable für Variablen-Log muss ebenfalls übergeben werden.
	 * wenn nicht, wird nicht gelogged. 
     *
	 * Funktion Init() macht zwar einiges aber im Endeffekt wird nur $this->scriptIdHeatControl bestimmt und sicherheitshalber zB  
     * und der Kategorie Pfad für Administartor Autosteuerung angelegt.
     *
     * Funktion InitLogMessage() legt ein Logfile an
     *
     * Funktion InitLogNachrichten(() ruft das Geraete spezifische InitMesagePuffer() auf
     *
	 ****************************************************************************/
	
	function Init()
		{
		IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');		
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
		$CategoryIdApp = $moduleManager->GetModuleCategoryID('app');
		$this->scriptIdHeatControl   = IPS_GetScriptIDByName('Autosteuerung_HeatControl', $CategoryIdApp);
						
		$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
		if ($WFC10_Enabled==true)
			{
			$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
			}
		//echo "\nWebportal User.Autosteuerung Datenstruktur installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);		
		}	
		
	function InitLogMessage()
		{
		//echo "Initialisierung ".get_class($this)." mit Logfile: ".$this->log_File." mit Meldungsspeicher: ".$this->script_Id." \n";
		//var_dump($this);
		if ($this->log_File=="No-Output")
			{
			/* kein Logfile anlegen */
			}
		else
			{	
			$log_ConfigFile=get_IPSComponentLoggerConfig();	
			if (!file_exists($this->log_File))
				{
				/* Pfad aus dem Dateinamen herausrechnen. Wenn keiner definiert ist einen aus dem Configfile nehmen und sonst mit Default arbeiten */
				//echo "construct class Anwesenheitssimulation, File ".$this->logfile." existiert nicht. Mit Verzeichnis gemeinsam anlegen.\n";
				$FilePath = pathinfo($this->log_File, PATHINFO_DIRNAME);
				//echo "Verzeichnis für Logfile : ".PATHINFO_DIRNAME.$FilePath."\n";				
				if ($FilePath==".") 
					{
					//echo "Es existiert kein Filepath, einen aus der config nehmen oder annehmen und hinzufügen\n";
					//print_r($log_ConfigFile);
					if (isset($log_ConfigFile["LogDirectories"]["AnwesenheitssimulationLog"])==true)
						{
						$FilePath=$log_ConfigFile["LogDirectories"]["AnwesenheitssimulationLog"];
						}
					else $FilePath="C:/Scripts/";	
					$this->log_File=$FilePath.$this->log_File;
					}
				if (!file_exists($FilePath)) 
					{
					if (!mkdir($FilePath, 0755, true)) {
						throw new Exception('Create Directory '.$destinationFilePath.' failed!');
						}
					}
				}
			/* nocheinmal probieren, jetzt sollte der Pfad definiert sein */	
			if (!file_exists($this->log_File))
				{											
				//echo "Create new file : ".$this->log_File." im Verzeichnis : ".$FilePath." \n";
				$handle3=fopen($this->log_File, "a");
				fwrite($handle3, date("d.m.y H:i:s").";Meldung\r\n");
 				fclose($handle3);
				//echo "construct class Anwesenheitssimulation, Filename angelegt. Verzeichnis für Logfile : ".$this->log_File."\n";
				}
			else
				{
				//echo "construct class Anwesenheitssimulation, Filename vorhanden. Verzeichnis für Logfile : ".$this->log_File."\n";
				}		
			}
		}																												

	function InitLogNachrichten($type,$profile)
		{
		/*momentan nur durchreichen */
		$this->InitMesagePuffer($type,$profile);
		}
		
	abstract function InitMesagePuffer($type,$profile);
			
	abstract function WriteLink($i,$type,$vid,$profile,$scriptIdHeatControl); 	
		
	function LogMessage($message)
		{
		if ($this->log_File != "No-Output")
			{
			$handle3=fopen($this->log_File, "a");
			fwrite($handle3, date("d.m.y H:i:s").";".$message."\r\n");
			fclose($handle3);
			//echo $this->log_File."   ".$message."\n";
			}
		}
		
	function LogNachrichten($message)
		{		
		if ($this->nachrichteninput_Id != "Ohne")
			{
			//print_r($this->zeile);
			for ($i=16; $i>1;$i--) 
				{ 
				SetValue($this->zeile[$i],GetValue($this->zeile[$i-1])); 
				//echo "Wert : ".$i."\n";
				}
			SetValue($this->zeile[$i],date("d.m.y H:i:s")." : ".$message);
			}		
		}
		
	function PrintNachrichten()
		{
		$result=false;
		if ($this->nachrichteninput_Id != "Ohne")
			{
			$result="";
			for ($i=1; $i<17;$i++) 
				{ 
				$result .= GetValue($this->zeile[$i])."\n"; 
				}		   
			//$result=GetValue($this->zeile1)."\n".GetValue($this->zeile2)."\n".GetValue($this->zeile3)."\n".GetValue($this->zeile4)."\n".GetValue($this->zeile5)."\n".GetValue($this->zeile6)."\n".GetValue($this->zeile7)."\n".GetValue($this->zeile8)."\n".GetValue($this->zeile9)."\n".GetValue($this->zeile10)."\n".GetValue($this->zeile11)."\n".GetValue($this->zeile12)."\n".GetValue($this->zeile13)."\n".GetValue($this->zeile14)."\n".GetValue($this->zeile15)."\n".GetValue($this->zeile16)."\n";
			}
		return $result;
		}

	function status()
	   {
	   return true;
	   }		
						
	}

/*****************************************************************************************************
 *
 * Temperaturregelung in der Autosteuerung
 *
 * Routinen zur Temperaturregelung. Construktor wird nun üblicherweise ohne Parameter aufgerufen.
 * d.h. construct muss sich selbst helfen und Annahmen treffen:
 *
 * Default: kein Logging in einem File, Verzeichnis
 *
 **************************************************************************************************************/


class AutosteuerungRegler extends AutosteuerungFunktionen
	{

	protected $log_File="Default";
	protected $script_Id="Default";
	protected $nachrichteninput_Id="Default";
	protected $installedmodules;
	protected $zeile=array();
	protected $scriptIdHeatControl;	

	public function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
		{
		//echo "Logfile Construct\n";
		
		/******************************* Init *********/
		$this->log_File=$logfile;
		$this->nachrichteninput_Id=$nachrichteninput_Id;			
		$this->Init();			/* Autosteuerung_HeatControl Script ID wird festgelegt in $this->scriptIdHeatControl */

		/******************************* File Logging *********/
		
		$this->InitLogMessage();		/* Filename festlegen für Logging in einem externem File */
		
		/******************************* Nachrichten Logging *********/
		
		$type=3;$profile=""; $this->zeile=array();
		$this->InitLogNachrichten($type,$profile);		/*  ruft das Geraete spezifische InitMesagePuffer() auf, logging in Objekten mit String und ohne Profil festlegen, keien Abstrkte Routine, auf jeden Fall programmieren */
		}

	function WriteLink($i,$type,$vid,$profile,$scriptIdHeatControl)
		{
		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')	
		$this->zeile[$i]=@IPS_GetObjectIDByName("Zeile".$i,$vid);
		if ($this->zeile[$i]==false) 
			{
			//echo "Neue Regelzeile für Zeile".$i." in ".$vid." anlegen \n";	
			$this->zeile[$i] = CreateVariable("Zeile".$i,$type,$vid, $i*10,$profile,$scriptIdHeatControl );
			}
		}

	function InitMesagePuffer($type=3,$profile="")
		{		
		if ($this->nachrichteninput_Id != "Ohne")
			{
			// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
			// bei etwas anderem als einem String stimmt der defaultwert nicht
			$vid=@IPS_GetObjectIDByName("ReglerAktionen",$this->nachrichteninput_Id);
			if ($vid===false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung Regleraktionen InitMessagePuffer, keine Kategorie"ReglerAktionene in '.$this->nachrichteninput_Id);
				}
			else
				{	
				//EmptyCategory($vid);			
				for ($i=1; $i<17;$i++)	{ $this->WriteLink($i,$type,$vid,$profile,null); }  /* kein Actionscript notwendig */
				}	
			}
		else
			{
			/* auch Wenn "Ohne" angegeben wird, wird gelogged, Verzeichnis wird dann selbst ermittelt */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);			
			$this->installedmodules=$moduleManager->GetInstalledModules();			
			$moduleManager_AS = new IPSModuleManager('Autosteuerung');
			$CategoryIdData     = $moduleManager_AS->GetModuleCategoryID('data');
			//echo "  Kategorien, Variablen und Links im Datenverzeichnis Autosteuerung : ".$CategoryIdData."  (".IPS_GetName($CategoryIdData).")\n";
			$this->nachrichteninput_Id=@IPS_GetObjectIDByName("ReglerAktionen-Stromheizung",$CategoryIdData);
			$vid=@IPS_GetObjectIDByName("ReglerAktionen",$this->nachrichteninput_Id);
			if ($vid===false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung "Regleraktionen InitMessagePuffer, keine Kategorie ReglerAktionen in '.$this->nachrichteninput_Id);
				}
			else
				{
				//EmptyCategory($vid);			
				for ($i=1; $i<17;$i++)	{ $this->WriteLink($i,$type,$vid,$profile,null); }
				}		
			}
		}

	function LogMessage($message)
		{
		if ($this->log_File != "No-Output")
			{
			$handle3=fopen($this->log_File, "a");
			fwrite($handle3, date("d.m.y H:i:s").";".$message."\r\n");
			fclose($handle3);
			//echo $this->log_File."   ".$message."\n";
			}
		}

	}
	
/*****************************************************************************************************
 *
 * Anwesenheitssimulation in der Autosteuerung
 *
 * Routinen zur Anwesenheitssimulation
 *
 **************************************************************************************************************/


class AutosteuerungAnwesenheitssimulation extends AutosteuerungFunktionen
	{

	protected $log_File="Default";
	protected $script_Id="Default";
	protected $nachrichteninput_Id="Default";
	protected $installedmodules;
	protected $zeile=array();
	protected $scriptIdHeatControl;	

	public function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
		{
		//echo "Logfile Construct\n";
		
		/******************************* Init *********/
		$this->log_File=$logfile;
		$this->nachrichteninput_Id=$nachrichteninput_Id;			
		$this->Init();      /* Autosteuerung_HeatControl Script ID wird festgelegt in $this->scriptIdHeatControl */

		/******************************* File Logging *********/
		$this->InitLogMessage();
		
		/******************************* Nachrichten Logging *********/
		$type=3;$profile=""; $this->zeile=array();
		$this->InitLogNachrichten($type,$profile);      /*  ruft das Geraete spezifische InitMesagePuffer() auf */
		}

	function WriteLink($i,$type,$vid,$profile,$scriptIdHeatControl)
		{
		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
		$this->zeile[$i]=@IPS_GetObjectIDByName("Zeile".$i,$vid);
		if ($this->zeile[$i]==false) 
			{			
			$this->zeile[$i] = CreateVariable("Zeile".$i,$type,$vid, $i*10,$profile,$scriptIdHeatControl );
			}
		}

	function InitMesagePuffer($type=3,$profile="")
		{		
		if ($this->nachrichteninput_Id != "Ohne")
			{
			// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
			// bei etwas anderem als einem String stimmt der defaultwert nicht
			$vid=@IPS_GetObjectIDByName("Schaltbefehle",$this->nachrichteninput_Id);
			if ($vid===false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung Anwesenheitssimulation InitMessagePuffer, keine Kategorie Schaltbefehle in '.$this->nachrichteninput_Id);
				}
			else
				{	
				//EmptyCategory($vid);			
				for ($i=1; $i<17;$i++)	{ $this->WriteLink($i,$type,$vid,$profile,null); }
				}	
			}
		else
			{
			/* auch Wenn "Ohne" angegeben wird, wird gelogged, Verzeichnis wird dann selbst ermittelt */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);			
			$this->installedmodules=$moduleManager->GetInstalledModules();			
			$moduleManager_AS = new IPSModuleManager('Autosteuerung');
			$CategoryIdData     = $moduleManager_AS->GetModuleCategoryID('data');
			//echo "  Kategorien, Variablen und Links im Datenverzeichnis Autosteuerung : ".$CategoryIdData."  (".IPS_GetName($CategoryIdData).")\n";
			$this->nachrichteninput_Id=@IPS_GetObjectIDByName("Schaltbefehle-Anwesenheitssimulation",$CategoryIdData);
			$vid=@IPS_GetObjectIDByName("Schaltbefehle",$this->nachrichteninput_Id);
			if ($vid==false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung Anwesenheitssimulation InitMessagePuffer, keine Kategorie Schaltbefehle in '.$this->nachrichteninput_Id);
				}
			else
				{
				//EmptyCategory($vid);			
				for ($i=1; $i<17;$i++)	{ $this->WriteLink($i,$type,$vid,$profile,null); }
				}		
			}
		}

	function LogMessage($message)
		{
		if ($this->log_File != "No-Output")
			{
			$handle3=fopen($this->log_File, "a");
			fwrite($handle3, date("d.m.y H:i:s").";".$message."\r\n");
			fclose($handle3);
			//echo $this->log_File."   ".$message."\n";
			}
		}

	}

/*****************************************************************************************************
 *
 * Alexa in der Autosteuerung
 *
 * Routinen zur Darstellung der Alexa Funktionen von Amazon
 *
 * Achtung, beim kopieren von Defaultfunktionen auf folgende Punkte aufpassen:
 *    Links und Namen in Init MessagePugffer adaptieren
 *
 *
 **************************************************************************************************************/


class AutosteuerungAlexa extends AutosteuerungFunktionen
	{

	protected $log_File="Default";
	protected $script_Id="Default";
	protected $nachrichteninput_Id="Default";
	protected $installedmodules;
	protected $zeile=array();
	protected $scriptIdHeatControl;	

	public function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
		{
		//echo "Logfile Construct\n";
		
		/******************************* Init *********/
		$this->log_File=$logfile;
		$this->nachrichteninput_Id=$nachrichteninput_Id;			
		$this->Init();      /* Autosteuerung_HeatControl Script ID wird festgelegt in $this->scriptIdHeatControl */

		/******************************* File Logging *********/
		$this->InitLogMessage();
		
		/******************************* Nachrichten Logging *********/
		$type=3;$profile=""; $this->zeile=array();
		$this->InitLogNachrichten($type,$profile);          /*  ruft das Geraete spezifische InitMesagePuffer() auf */
		}

	function WriteLink($i,$type,$vid,$profile,$scriptIdHeatControl)
		{
		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
		$this->zeile[$i]=@IPS_GetObjectIDByName("Zeile".$i,$vid);
		if ($this->zeile[$i]==false) 
			{			
			$this->zeile[$i] = CreateVariable("Zeile".$i,$type,$vid, $i*10,$profile,$scriptIdHeatControl );
			}
		}

	function InitMesagePuffer($type=3,$profile="")
		{		
		if ($this->nachrichteninput_Id != "Ohne")
			{
			// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
			// bei etwas anderem als einem String stimmt der defaultwert nicht
			$vid=@IPS_GetObjectIDByName("Nachrichten",$this->nachrichteninput_Id);	/* <<<<<<< change here */
			if ($vid===false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung Anwesenheitssimulation InitMessagePuffer, keine Kategorie Schaltbefehle in '.$this->nachrichteninput_Id);
				}
			else
				{	
				//EmptyCategory($vid);			
				for ($i=1; $i<17;$i++)	{ $this->WriteLink($i,$type,$vid,$profile,null); }
				}	
			}
		else
			{
			/* auch Wenn "Ohne" angegeben wird, wird gelogged, Verzeichnis wird dann selbst ermittelt */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);			
			$this->installedmodules=$moduleManager->GetInstalledModules();			
			$moduleManager_AS = new IPSModuleManager('Autosteuerung');
			$CategoryIdData     = $moduleManager_AS->GetModuleCategoryID('data');
			//echo "  Kategorien, Variablen und Links im Datenverzeichnis Autosteuerung : ".$CategoryIdData."  (".IPS_GetName($CategoryIdData).")\n";
			$this->nachrichteninput_Id=@IPS_GetObjectIDByName("Nachrichten-Alexa",$CategoryIdData);	/* <<<<<<< change here */
			$vid=@IPS_GetObjectIDByName("Nachrichten",$this->nachrichteninput_Id);							/* <<<<<<< change here */
			if ($vid==false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung Anwesenheitssimulation InitMessagePuffer, keine Kategorie Schaltbefehle in '.$this->nachrichteninput_Id);
				}
			else
				{
				//EmptyCategory($vid);			
				for ($i=1; $i<17;$i++)	{ $this->WriteLink($i,$type,$vid,$profile,null); }
				}		
			}
		}

	function LogMessage($message)
		{
		if ($this->log_File != "No-Output")
			{
			$handle3=fopen($this->log_File, "a");
			fwrite($handle3, date("d.m.y H:i:s").";".$message."\r\n");
			fclose($handle3);
			//echo $this->log_File."   ".$message."\n";
			}
		}

	}
										
/*****************************************************************************************************
 *
 * Stromheizung in der Autosteuerung
 *
 * Routinen zur Stromheizungssteuerung aufgebaut auf die Standard Autosteuerungsklasse die kleine Nachrichtenspeicher aufbaut
 * zusaetzlich wird auch in Files gelogged. Hier werden beim AUfruf die Logging und Nachrichtenspeicher Default genutzt.
 * Default bedeutet ohne
 *
 * es werden statt Nachrichten ein Kalendar dargestellt
 * im construct muss Init() aufgerufen werden das eigentlich nur $this->scriptIdHeatControl bestimmt.
 * dann InitLogMessage() dass bei Defaultwert kein Logfile anlegt
 * dann InitLogNachrichten das InitMesagePuffer($type,$profile) aufruft.
 *
 **************************************************************************************************************/


class AutosteuerungStromheizung extends AutosteuerungFunktionen
	{

	protected $log_File="Default";
	protected $script_Id="Default";
	protected $nachrichteninput_Id="Default";
	protected $installedmodules;
	protected $zeile=array();
	protected $scriptIdHeatControl;
	
	public function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
		{
		//echo "Logfile Construct\n";
		
		/******************************* Init *********/
		$this->log_File=$logfile;
		$this->nachrichteninput_Id=$nachrichteninput_Id;			
		$this->Init();          /* Autosteuerung_HeatControl Script ID wird festgelegt in $this->scriptIdHeatControl */

		/******************************* File Logging *********/
		$this->InitLogMessage();
		
		/******************************* Nachrichten Logging *********/
		$this->zeile=array();		
		//$type=1;$profile="AusEin"; 		/* Umstellen auf Boolean und Standard Profil für bessere Darstellung Mobile Frontend*/
		$type=0;$profile="~Switch";
		$this->InitLogNachrichten($type,$profile);          	/*  ruft das Geraete spezifische InitMesagePuffer() auf */
		}

	/*************
	 *
	 * schreibt einen Link mit aktuellem Datum auf zeile1 bis zeile16
	 * $i		Zeilennummer (1-16)
	 * $type	Type Boolen, Integer, Float oder String
	 * $vid	parent in der die Zeile1 bis zeile16 angelegt werden
	 * $profile	wenn notwendig
	 * $sctriptIdHeatControl	ist das Action Script
	 *
	 *****************************************/

	function WriteLink($i,$type,$vid,$profile,$scriptIdHeatControl)
		{
		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
		$this->zeile[$i]=@IPS_GetObjectIDByName("Zeile".$i,$vid);
		if ($this->zeile[$i]==false) 
			{
			echo "Variable mit Name Zeile$i in $vid (".IPS_GetName($vid).") mit $profile neu anlegen.\n";			
			$this->zeile[$i] = CreateVariable2("Zeile".$i,$type,$vid, $i*10,$profile,$scriptIdHeatControl);
			}
		}

	function CreateLink($i,$sourceCategory,$linkCategory)
		{
		//$this->zeile[$i] = CreateVariable("Zeile".$i,$type,IPS_GetParent($vid), $i*10,$profile,$scriptIdHeatControl,0 );
		//IPS_SetHidden($this->zeile[$i],true);

		$this->zeile[$i] = @IPS_GetObjectIDByName("Zeile".$i,$sourceCategory);
		if ($this->zeile[$i]) CreateLinkByDestination(date("D d",time()+(($i-1)*24*60*60)), $this->zeile[$i], $linkCategory,  (($i*10)+100));		
		}

    /* unter linkCategory werden Links auf die einzelnen Zeilen des Wochenplans erstellt
     * der Name der Links ist aufsteigend beginnend mit dem aktuellen Tag
     */

	function UpdateLinks($linkCategory)
		{
		for ($i=1;$i<17;$i++)
			{
			if ($this->zeile[$i]) 
				{
				CreateLinkByDestination(date("D d",time()+(($i-1)*24*60*60)), $this->zeile[$i], $linkCategory, (($i*10)+100));
				}
			}		
		}

    /* sucht die Variable Wochenplan im Nachrichtenspeicher/Wochenplan*/    
		
	function getWochenplanID()
		{	
		return(@IPS_GetObjectIDByName("Wochenplan",$this->nachrichteninput_Id));
		}
	
    function getAutoFillID()
		{	
		return(@IPS_GetObjectIDByName("AutoFill",$this->getWochenplanID()));
		}

	function writeWochenplan()
		{
        $wochenplanID=$this->getWochenplanID();	
		echo "Wochenplan ID : ".$wochenplanID."   ".IPS_GetName($wochenplanID)."\n";
        $childIDs=IPS_GetChildrenIDs($wochenplanID);
        foreach ($childIDs as $childID) 
            {
            if ( (IPS_GetObject($childID)["ObjectType"])==6)
                {
                $targetID=IPS_GetLink($childID)["TargetID"];
                echo "    ".$targetID."   ".IPS_GetName($childID);
                echo "   ".(GetValue($targetID)?"Ein":"Aus");
                echo "\n";
                }
            }
		}

	/************************
	 *
	 * wird mit construct automatisch aufgerufen. Ist in InitLogNachrichten($type,$profile) enthalten
	 * wenn construct mit Defaultwerten aufgerufen wird kommt die Kategorie Wochenplan-Stromheizung zur Anwendung
	 *
	 ***********************************************/
	 
	function InitMesagePuffer($type=1,$profile="AusEin")
		{
		$fatalerror=false;
		if ($this->nachrichteninput_Id != "Ohne")
			{
			// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
			// bei etwas anderem als Integer stimmt der defaultwert nicht		
			}
		else
			{
			/* auch Wenn "Ohne" angegeben wird, wird gelogged, Verzeichnis wird dann selbst ermittelt, $this->nachrichteninput_Id umschreiben */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);			
			$this->installedmodules=$moduleManager->GetInstalledModules();			
			$moduleManager_AS = new IPSModuleManager('Autosteuerung');
			$CategoryIdData     = $moduleManager_AS->GetModuleCategoryID('data');
			//echo "  Kategorien, Variablen und Links im Datenverzeichnis Autosteuerung : ".$CategoryIdData."  (".IPS_GetName($CategoryIdData).")\n";
			$this->nachrichteninput_Id=@IPS_GetObjectIDByName("Wochenplan-Stromheizung",$CategoryIdData);
			}		
		$vid=$this->getWochenplanID();	
		if ($vid==false) 
			{
			echo "Fatal Error !!!!\n";
			$fatalerror=true;
			}
		else 
			{    
			//EmptyCategory($vid);
			$vid=IPS_GetParent($vid);	/* Wochenplan ist nur für die lokalen links auf die Heiztage Register */
			//echo "Init MessagePuffer in construct with Typ $type und Profil $profile in Kategorie $vid (".IPS_GetName($vid).")\n";			
			for ($i=1; $i<17;$i++)	
				{
				//echo $i."  "; 
				$this->WriteLink($i,$type,$vid,$profile,$this->scriptIdHeatControl); 		/* schreibt die Zeile Register */
				}
			}
		return($fatalerror);	
		}	

	/* nur den Wochenplan um eins weiterschieben. Variable ist der Inputwert für den letzten Eintrag
	 * die Links mit den aktuellen Datum werden in einer anderen Routine gemacht.
	 */	
	function ShiftforNextDay($message=0)
		{
		if ($this->nachrichteninput_Id != "Ohne")
			{
			if ($this->getWochenplanID() != false)
				{
				//print_r($this->zeile);
				for ($i=1; $i<16;$i++) 
					{ 
					SetValue($this->zeile[$i],GetValue($this->zeile[$i+1])); 
					//echo "Wert : ".$i."\n";
					}
				SetValue($this->zeile[$i],$message);
				}
			}		
		}

    function setAutoFill($value)
        {
        $oid=$this->getAutoFillID();		// OID von Profilvariable für Autofill
        $descrID=IPS_GetVariableIDByName("Beschreibung",$this->getWochenplanID());		// OID von Profilvariable für Autofill
        $text="";
        //echo "Einstellung Werte $oid ".GetValue($oid)."  ".GetValueFormatted($oid)."  --> neuer Wert : ".$value."\n";
        switch ($value)
            {
            case 0:
                $text="Nächster Tag immer AUS";
                break;
            case 1:
                $text="Nächster Tag immer EIN";
                break;
            case 2:
                $text="Nächster Tag gleich wie die Woche davor";
                break;
            case 3:
                $text="An allen Freitagen auf EIN";
                break;
            case 4:
                $text="An allen Freitagen und einen Tag davor auf EIN";
                break;
            case 5:
                $text="An allen Freitagen und zwei Tage davor auf EIN";
                break;
            case 6:
                $text="An allen Arbeitstagen auf EIN";
                break;
            default:
                $value=0;
            }    
        SetValue($oid,$value);
        SetValue($descrID,$text);
        }

	/* Es gibt aus, Ein und Profil 2 bis 5
	 *
	 * Profil 2: nur Freitage, wenn zu Hause
	 * Profil 3: nur Freitage plus 1, wenn zu Hause plus 1 Tag Vorwärmzeit
	 * Profil 4  nur Freitage plus 2, wenn zu Hause plus 2 Tag Vorwärmzeit
	 * Profil 5 nur Arbeitstage
	 */
	function getStatusfromProfile($profile=0)
		{
		$status=false;

		$result0=$this->EvaluateAutoStatus(0);
		$result1=$this->EvaluateAutoStatus(1);
		$result2=$this->EvaluateAutoStatus(2);

		switch ($profile)
			{
			case 0:     // Aus
				$status=false;
				break;
			case 1:     // Ein
				$status=true;
				break;
            case 2:     // Auto
                $status=GetValue($this->zeile[7]);           // Wert vor einer Woche
                break;
			case 3:     // Profil 1
				if ($result0["Arbeitstag"]=="Freitag") $status=true;
				else $status=false;
				break;
			case 4:     // Profil 2
				if ($result0["Arbeitstag"]=="Freitag") $status=true;
				elseif ($result1["Arbeitstag"]=="Freitag") $status=true;
				else $status=false;
				break;
			case 5:     // Profil 3
				if ($result0["Arbeitstag"]=="Freitag") $status=true;
				elseif ($result1["Arbeitstag"]=="Freitag") $status=true;
				elseif ($result2["Arbeitstag"]=="Freitag") $status=true;
				else $status=false;
				break;
			case 6:     // Profil 4
				if ($result0["Arbeitstag"]=="Arbeitstag") $status=true;
				else $status=false;
				break;
			default:
				echo "Profil $profile unbekannt.\n";
				break;
			}
		return ($status);
		}
	
	/* Unterstützung für Shift for next day
	 *
	 */		
	function EvaluateAutoStatus($time=0)
		{
		//echo "    EvaluateAutoStatus:\n";
		$result=array();
		if ($time==0) $time=(time()+16*24*60*60);
		elseif ($time<15) $time=(time()+(16+$time)*24*60*60);
		$wochentag=date("D",$time);
		$feiertag=$this->feiertag($time,"W");		// gibt zurück Arbeitstag, Wochenende oder Name des Feiertages 
		echo "     ".date("D d.m.Y",$time)."    Wochentag: ".$wochentag."    Status: ".$feiertag."\n";
		switch ($wochentag)
			{
			case "Mon":
			case "Tue":
			case "Wed":
			case "Thu":
			case "Fri":
			case "Sat":
			case "Sun":
				$result["Wochentag"]=$wochentag;
			  	break;
			default:
				echo "FEHLER: Wochentag nicht bekannt ...\n";
				break;	
			}
		switch ($feiertag)
			{
			case "Arbeitstag":
				$result["Arbeitstag"]="Arbeitstag";
				$result["Feiertag"]="";
				break;
			case "Wochenende": 
				$result["Arbeitstag"]="Freitag";
				$result["Feiertag"]="";
				break;
			default:			// benannte Feiertage
				$result["Arbeitstag"]="Freitag";
				$result["Feiertag"]=$feiertag;
				break;
			}	
		return ($result);
		}
		
	/** 
	 * Ermittle Feiertage, Arbeitstage und Wochenenden von einem Datum 
	 * 
	 * @param $datum als String im Format Y-m-d oder als Timestamp
	 * @param string $bundesland<br>
	 *    B   = Burgenland<br>
	 *    K   = Kärnten<br>
	 *    NOE = Niederösterreich<br>
	 *    OOE = Oberösterreich<br>
	 *    S   = Salzburg<br>
	 *    ST  = Steiermark<br>
	 *    T   = Tirol<br>
	 *    V   = Vorarlberg<br>
	 *    W   = Wien
	 * @return 'Arbeitstag', 'Wochenende' oder Name des Feiertags als String
	 */ 
	function feiertag ($datum, $bundesland='')
		{ 
	    $bundesland = strtoupper($bundesland);
    	if (is_object($datum))
    		{
			//echo "Timestamp Object: ".$datum."\n";
        	$datum = date("Y-m-d", $datum);
    		}
		elseif (is_integer($datum))
			{
			//echo "Timestamp Integer: ".$datum."\n";	
			$datum = date("Y-m-d", $datum);
			}
    	$datum = explode("-", $datum); 
		//print_r($datum);
		$datum[1] = str_pad($datum[1], 2, "0", STR_PAD_LEFT); 
    	$datum[2] = str_pad($datum[2], 2, "0", STR_PAD_LEFT); 

	    if (!checkdate($datum[1], $datum[2], $datum[0])) return false; 

	    $datum_arr = getdate(mktime(0,0,0,$datum[1],$datum[2],$datum[0])); 

	    $easter_d = date("d", easter_date($datum[0])); 
    	$easter_m = date("m", easter_date($datum[0])); 

	    $status = 'Arbeitstag'; 
    	if ($datum_arr['wday'] == 0 || $datum_arr['wday'] == 6) $status = 'Wochenende'; 

	    if ($datum[1].$datum[2] == '0101')
    		{ 
        	return 'Neujahr'; 
   			}
	    elseif ($datum[1].$datum[2] == '0106')
    		{ 
        	return 'Heilige Drei Könige'; 
    		}
	    elseif ($datum[1].$datum[2] == '0319' && ($bundesland == 'K' || $bundesland == 'ST' || $bundesland == 'T' || $bundesland == 'V'))
    		{ 
	        return 'Josef'; 
    		} 
	    elseif ($datum[1].$datum[2] == $easter_m.$easter_d)
    		{ 
	        return 'Ostersonntag'; 
    		}
		elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+1,$datum[0])))
    		{ 
	        return 'Ostermontag'; 
    		}
	    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+39,$datum[0])))
    		{ 
	        return 'Christi Himmelfahrt'; 
    		}
	    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+49,$datum[0])))
    		{ 
	        return 'Pfingstsonntag'; 
    		}
	    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+50,$datum[0])))
    		{ 
	        return 'Pfingstmontag'; 
    		}
	    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+60,$datum[0])))
    		{ 
	        return 'Fronleichnam'; 
    		}
	    elseif ($datum[1].$datum[2] == '0501')
    		{ 
	        return 'Erster Mai'; 
    		}
	    elseif ($datum[1].$datum[2] == '0504' && $bundesland == 'OOE')
    		{ 
	        return 'Florian'; 
    		}
	    elseif ($datum[1].$datum[2] == '0815')
    		{ 
	        return 'Mariä Himmelfahrt'; 
    		}
	    elseif ($datum[1].$datum[2] == '0924' && $bundesland == 'S')
    		{ 
	        return 'Rupertitag'; 
    		}
	    elseif ($datum[1].$datum[2] == '1010' && $bundesland == 'K')
    		{ 
	        return 'Tag der Volksabstimmung'; 
    		}
	    elseif ($datum[1].$datum[2] == '1026')
    		{ 
	        return 'Nationalfeiertag'; 
    		}
	    elseif ($datum[1].$datum[2] == '1101')
    		{ 
	        return 'Allerheiligen'; 
    		}
	    elseif ($datum[1].$datum[2] == '1111' && $bundesland == 'B')
    		{ 
	        return 'Martini'; 
    		}
	    elseif ($datum[1].$datum[2] == '1115' && ($bundesland == 'NOE' || $bundesland == 'W'))
    		{ 
	        return 'Leopoldi'; 
    		}
	    elseif ($datum[1].$datum[2] == '1208')
    		{ 
	        return 'Mariä Empfängnis'; 
		    }
	    elseif ($datum[1].$datum[2] == '1224')
		    { 
        	return 'Heiliger Abend'; 
		    }	
	    elseif ($datum[1].$datum[2] == '1225')
    		{ 
	        return 'Christtag'; 
    		}
	    elseif ($datum[1].$datum[2] == '1226')
    		{ 
	        return 'Stefanitag'; 
    		}
		else
		    { 
        	return $status; 
		    } 
		}

		

	function SetupKalender($type=1,$profile="AusEin")
		{
		/* alles loeschen und neu aufbauen */
		$vid=$this->getWochenplanID();
		$pvid=IPS_GetParent($vid);
		EmptyCategory($vid);	
		EmptyCategory($pvid);	
		echo "Kategorien geloescht. Neu anlegen mit Typ ".$type." und Profil ".$profile."\n";
		$vid=CreateVariable("Wochenplan",3,$pvid, 0,'',null,'');				
		for ($i=1; $i<17;$i++)	
			{
			//echo $i."  ";
			/* Create Variable macht Probleme mit den Profilen. Besser createVariable2 verwenden */ 
			$this->zeile[$i] = CreateVariable2("Zeile".$i,$type,$pvid, $i*10,$profile,$this->scriptIdHeatControl,0 );
			IPS_SetHidden($this->zeile[$i],true);
			$this->CreateLink($i,$pvid,$vid); 
			}
		//echo "\n";			
		}

	}







/*********************************************************************************************/

/*  setEventTimer($scene["NAME"],$scene["EVENT_DURATION"]*60)                                */

function setEventTimer($name,$delay,$command)
	{
	echo "Jetzt wird der Timer gesetzt : ".$name."_EVENT"."\n";
	IPSLogger_Dbg(__file__, 'Autosteuerung, Timer setzen : '.$name.' mit Zeitverzoegerung von '.$delay.' Sekunden. Befehl lautet : '.str_replace("\n","",$command));	
	$now = time();
	$EreignisID = @IPS_GetEventIDByName($name."_EVENT", IPS_GetParent($_IPS['SELF']));
	if ($EreignisID === false)
		{ //Event nicht gefunden > neu anlegen
		$EreignisID = IPS_CreateEvent(1);
		IPS_SetName($EreignisID,$name."_EVENT");
		IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
		}
	IPS_SetEventActive($EreignisID,true);
	IPS_SetEventCyclic($EreignisID, 1, 0, 0, 0, 0,0);
	/* EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
	/* EreignisID, 1 einmalig,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
	IPS_SetEventCyclicTimeBounds($EreignisID,$now+$delay,0);
	IPS_SetEventCyclicDateBounds($EreignisID,$now+$delay,0);
	IPS_SetEventScript($EreignisID,$command);
	}

function getEventTimerStatus($name)
	{
	$EreignisID = @IPS_GetEventIDByName($name."_EVENT", IPS_GetParent($_IPS['SELF']));
    echo "Timer ID : ".$EreignisID."\n";

    }

/*  setEventTimer($scene["NAME"],$scene["EVENT_DURATION"]*60)                                */

function setDimTimer($name,$delay,$command)
	{
	echo "Jetzt wird der Timer gesetzt : ".$name."_EVENT_DIM"." und 10x alle ".$delay." Sekunden aufgerufen\n";
	IPSLogger_Dbg(__file__, 'Autosteuerung, Timer setzen : '.$name.' mit Zeitverzoegerung von '.$delay.' Sekunden. Befehl lautet : '.str_replace("\n","",$command));	
  	$now = time();
   $EreignisID = @IPS_GetEventIDByName($name."_EVENT_DIM", IPS_GetParent($_IPS['SELF']));
   if ($EreignisID === false)
		{ //Event nicht gefunden > neu anlegen
      $EreignisID = IPS_CreateEvent(1);
      IPS_SetName($EreignisID,$name."_EVENT_DIM");
      IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
     	}
   IPS_SetEventActive($EreignisID,true);
   IPS_SetEventCyclic($EreignisID, 0, 0, 0, 0, 1, $delay);
	/* EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 1 Sekuendlich Anzahl Sekunden */
	/* EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
	/* EreignisID, 1 einmalig,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit */
   IPS_SetEventScript($EreignisID,$command);
	}


/********************************************************************************************
 *
 *	Implementierte Applikationen:
 *
 *	Anwesenheit, iTunesSteuerung, GutenMorgenWecker, Status, Ventilator2, Alexa
 *  Ventilator1, Ventilator, StatusRGB sollte nicht mehr in Verwendung sein
 *
 ***************************************************************************************************/



function Anwesenheit($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
	global $AnwesenheitssimulationID;

	IPSLogger_Inf(__file__, 'Aufruf Routine Anwesenheit mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	$parges=$auto->ParseCommand($params,$status,$simulate);
	$command=array(); $entry=1;
	//print_r($parges);
	foreach ($parges as $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=true;		 /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird auf true geschaltet */
		$command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */	
		$switch=true; $delayValue=0; $speak="Status"; $switchOID=0; // fuer Kompatibilitaetszwecke
		
		IPSLogger_Dbg(__file__,"Aufruf mit ***** ".json_encode($Kommando));
		foreach ($Kommando as $befehl)
			{
			//echo "Bearbeite Befehl ".$befehl[0]."\n";
			switch (strtoupper($befehl[0]))
				{
				default:
					$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					IPSLogger_Dbg(__file__,"COMMAND: ".$befehl[0]." ".json_encode($command[$entry]));
					break;
				}	
			} /* Ende foreach Befehl */
		$result=$auto->ExecuteCommand($command[$entry],$simulate);
		
		if (isset($result["DELAY"])==true)
			{
			if ($result["DELAY"]>0)
				{
				echo ">>>Ergebnis ExecuteCommand, DELAY.\n";			
				print_r($result);
				if ($simulate==false)
					{
					setEventTimer($result["NAME"],$result["DELAY"],$result["COMMAND"]);
					}
				}
			}
		$entry++;	
		} /* Ende foreach Kommando */	
	
	return($command);	
	}

/********************************************************************************************
 *
 *  iTunes und Mediabefehle 
 *
 *	Neben Standardfunktionen wie in Status Spezialisierung auf die Mediensteuerung mit Sondergeräten wie 
 *	zB iTunes, Denon, Samsung, Logitech Harmony
 *
 *	Befehlsbeispiele:
 *   'OnUpdate','Media','Denon Arbeitszimmer,PwrOn;',			 Default Device ist Denon
 *
 *  komplizierte Algorithmen werden immer mit befehl:parameter eigegeben:
 *	 'OnUpdate','Media','Device:Denon Arbeitszimmer,Command:PwrOn;',
 * 
 *
 *
 **********************************************************************************************/

function iTunesSteuerung($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
	IPSLogger_Inf(__file__, 'Aufruf Routine MediaSteuerung mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	//echo "  i:(".memory_get_usage()." Byte).";
	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	//echo "  a:(".memory_get_usage()." Byte).";
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */	
	$parges=$auto->ParseCommand($params,$status,$simulate);
	//echo "  p:(".memory_get_usage()." Byte).";
	/* nun sind jedem Parameter Befehle zugeordnet die nun abgearbeitet werden, Kommando fuer Kommando */
	$command=array(); $entry=1;	
	foreach ($parges as $kom => $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;	
		$command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */	
		foreach ($Kommando as $num => $befehl)
			{
			switch (strtoupper($befehl[0]))
				{
				default:
					$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					//echo "  ".$entry.":(".memory_get_usage()." Byte).";
					break;
				}	
			} /* Ende foreach Befehl */
		$result=$auto->ExecuteCommand($command[$entry],$simulate);
		//echo "  z:(".memory_get_usage()." Byte).";
		$entry++;			
		} /* Ende foreach Kommando */
	unset ($auto);	
	return($command);	
	}

/********************************************************************************************
 *
 *	GutenMorgenwecker
 *
 *	Sonderfunktion für Status, könnte eigentlich gleich sein, da die Abfrage ob der Wecker aktiv ist bereits in Autosteuerung erfolgt ist.
 *
 * 	in Autosteurung, Autosteuerung_GetEventConfiguration() steht die Konfiguration. Aufgrund einer Variablenänderung
 *  oder einem Update der Ereignisvariable wird eine Applikation, wie diese oder eine Funktion aufgerufen.
 *	In der Applikation wird der letzte Parameter, die Kommandokette geparst und als Array gespeichert.
 * 	Die Kommandokette besteht aus mehreren Kommandos. Jedes Kommando wird dann noch Applikationsspezifisch ergänzt werden.
 *	Jedes Kommando besteht aus mehreren Befehlen.
 *
 *	Parse -> Evaluate -> Execute  
 *	  
 *
 *******************************************************************************************************/

function GutenMorgenWecker($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
	IPSLogger_Inf(__file__, 'Aufruf Routine GutenMorgenWecker mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);   // kommt nicht so oft vor, kann einen Level höher sein im Logfile
	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	//$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */	
	$parges=$auto->ParseCommand($params,$status,$simulate);
	/* nun sind jedem Parameter Befehle zugeordnet die nun abgearbeitet werden, Kommando fuer Kommando */
	$command=array(); $entry=1;	
	foreach ($parges as $kom => $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;	
		$command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */	
		foreach ($Kommando as $num => $befehl)
			{
			switch (strtoupper($befehl[0]))
				{
				default:
					$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					break;
				}	
			} /* Ende foreach Befehl */
		$result=$auto->ExecuteCommand($command[$entry],$simulate);
        $ergebnis=$auto->timerCommand($result,$simulate);
		$entry++;			
		} /* Ende foreach Kommando */
	return($command);	
	}

/********************************************************************************************
 *
 *  Statusbefehle
 *
 *  egal ob bei einer variablenänderung oder bei einem Update werden verschiedene Befehle die im Parameterfeld stehen abgearbeitet
 *  IpsLight Name und optional ob ein, aus und am Ende noch ein delay kann ohne Spezialbefehle eingegeben werden
 *
 * zum Beispiel:  'OnUpdate','Status','WohnzimmerKugellampe,toggle',
 *                 siehe auch Beispiele weiter unten 
 *
 *  komplizierte Algorithmen werden immer mit befehl:parameter eigegeben
 *
 *  OID:12345        Definition des zu schaltenden objektes
 *  NAME:Wohnzimmer  Definition des zu schaltenden IPSLight Schalters, Gruppe oder Programms, wird automatisch der Reihe nach auf
 *                       	Vorhandensein überprüft
 * 								Wenn keine Angabe wird der Status des Objektes (OnUpdate/OnChange) für den neuen Schaltzustand verwendet
 *
 *  DELAY:TIME      ein timer wird aktiviert, nach Ablauf von TIME (in Sekunden) wird der Schalter ausgeschaltet
 *  ENVELOPE:TIME   ein Statuswert wird so verschliffen, das nur selten tatsächlich der Schalter aktiviert wird
 *                      immer bei Wert 1 wird der timer neu aktiviert, ein Ablaufen des Timers führt zum Ausschalten
 *  MONITOR:ON|OFF|STATUS  die custom function monitorOnOff wird aufgerufen
 *  MUTE:ON|OFF|STATUS
 *
 ************************************************************************************************/

function Status($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
	global $speak_config;
	
	if ($wertOpt=="") IPSLogger_Inf(__file__, 'Aufruf Routine Status mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);

   /* bei einer Statusaenderung oder Aktualisierung einer Variable 																						*/
   /* array($params[0], $params[1],             $params[2],),                     										*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,false',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,true,20',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on:true,off:false,timer#dawn-23:45',),       			*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on:true,off:false,if:light',),       				*/

	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	
	$parges=$auto->ParseCommand($params,$status,$simulate);
	$command=array(); $entry=1;	
		
	/* nun sind jedem Parameter Befehle zugeordnet die nun abgearbeitet werden, Kommando fuer Kommando */
	//print_r($parges);
	foreach ($parges as $kom => $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;	
		$command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */	
	
		foreach ($Kommando as $num => $befehl)
			{
			//echo "      |".$num." : Bearbeite Befehl ".$befehl[0]."\n";
			switch (strtoupper($befehl[0]))
				{
				default:
					$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					echo "       Evaluate Befehl Ergebnis : ".json_encode($command[$entry])."\n";
					break;
				}	
			} /* Ende foreach Befehl */
        //echo "        Aufruf Befehl ExecuteCommand mit : ".json_encode($command[$entry])."\n";            
		$result=$auto->ExecuteCommand($command[$entry],$simulate);
        $ergebnis=$auto->timerCommand($result,$simulate);
		//print_r($command[$entry]);
		$entry++;			
		} /* Ende foreach Kommando */
	unset ($auto);							/* Platz machen im Speicher */		
	return($command);
	}

/*********************************************************************************************
 *  StatusParallel
 *
 *  testweise die einzelnen Befehle als eigene Threads aufrufen
 *
 *********************************************************************************************/

function StatusParallel($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
	global $speak_config;
	
	IPSLogger_Inf(__file__, 'Aufruf Routine StatusParallel mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	
	$parges=$auto->ParseCommand($params,$status,$simulate);
	//print_r($parges);
	$command=array(); $entry=1;	
	foreach ($parges as $kom => $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;	
		$command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */	
	
		foreach ($Kommando as $num => $befehl)
			{
			switch (strtoupper($befehl[0]))
				{
				default:
					$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					//echo "       Evaluate $befehl[0] : ".json_encode($command[$entry])."\n";
					break;
				}	
			} /* Ende foreach Befehl */
		echo "       Evaluate Befehl Ergebnis : ".json_encode($command[$entry])."\n\n";			
        /* für die beiden folgenden Befehle ein eigenes script starten, Übergabe wie bei Alexa */
        $request['REQUEST']=json_encode($command[$entry]);      // Übergabe von mehrstufigen Arrays nicht möglich, Übergabe daher serialisiert
        $request['MODULE']="Autosteuerung";
        //echo "Aufruf Execute mit RunScriptEx und script : ".$auto->scriptId_Autosteuerung."  ".json_encode($request)."\n";
        //print_r($request);
        IPS_RunScriptEx($auto->scriptId_Autosteuerung, $request);
		$entry++;			
		} /* Ende foreach Kommando */
	unset ($auto);							/* Platz machen im Speicher */		
	return($command);
	}

/*********************************************************************************************
 *  StatusRGB
 *
 *
 *********************************************************************************************/

function statusRGB($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
   /* allerlei Spielereien mit einer RGB Anzeige */

   /* bei einer Statusaenderung einer Variable 																						*/
   /* array($params[0], $params[1],             $params[2],),                     										*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe',),       														*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe,on:true,off:false,timer:dawn-23:45',),       			*/
   /* array('OnChange','StatusRGB',   'ArbeitszimmerLampe,on:true,off:false,if:xxxxxx',),       				*/

	IPSLogger_Inf(__file__, 'Aufruf Routine StatusRGB mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	$parges=$auto->ParseCommand($params,$status,$simulate);
	$command=array(); $entry=1;	

	if ($simulate==true) 
		{
		echo "***Simulationsergebnisse (parges):";
		print_r($parges);
		}

	foreach ($parges as $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=true;		 /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird auf true geschaltet */
		$command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */	

		foreach ($Kommando as $befehl)
			{
			//echo "Bearbeite Befehl ".$befehl[0]."\n";
			switch (strtoupper($befehl[0]))
				{
				default:
					$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					break;
				}	
			} /* Ende foreach Befehl */
		$result=$auto->ExecuteCommand($command[$entry],$simulate);
        $ergebnis=$auto->timerCommand($result,$simulate);
		$entry++;			
		} /* Ende foreach Kommando */
		
	return $command;
	}

/*********************************************************************************************/

/*********************************************************************************************/

function SwitchFunction($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
	
	global $params2,$speak_config;
	
	/* Anlegen eines Schalters in der GUI der Autosteuerung, Bedienelemente können angegeben werden */
	$switchStatus=GetValue($_IPS['VARIABLE']);
	$moduleParams2 = explode(',', $params[2]);
	if ($switchStatus==0)
	   {
		IPSLight_SetSwitchByName($params[2],false);
	}
	if ($switchStatus==1)
	   {
		IPSLight_SetSwitchByName($params[2],true);
	  	}
	if ($speak_config["Parameter"][1]=="Debug")
		{
		tts_play(1,"Schalter ".$params[2]." manuell auf ".$switchStatus.".",'',2);
		}
	}

/********************************************************************************************
 *
 *  HeatControl, Heizung
 *
 * eigentlich die Status Applikation, kopiert damit möglicherweise andere Abläufe 
 * zusaetzlich eingebaut werden können, Ziel ist selbe Applikation für alles
 *
 * uebernimmt die Befehlsparameter, den neuen Wert der auslösenden Variable und deren ID
 * im Unterschied zu den anderen Applikationen wird auch der Vorwert ermittelt OLDSTATUS und gemeinsam mit SWITCH übergeben
 * SWITCH, STATUS, OLDSTATUS
 *
 * Hierarchie notwendig wie bei IPSLight, Haus, Zimmer, Gruppen von Zimmern
 *
 * neue Befehle:
 *  short:  xxxxxx => array('OnChange','Ventilator','Ventilator,25,true,24,false',),
 *  long:   xxxxxx => array('OnChange','Ventilator','name:Ventilator,control:25,threshold:1,exceed:true',)
 *
 * xxxxxx => array('OnChange','HeatControl','if:Arbeitszimmer,name:ArbeitszimmerHeizung,control:18,threshold:1,exceed:false',)
 *
 *************************************************************************************************/

function Ventilator2($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
	global $speak_config;
	global $log_Autosteuerung;
	
   /* bei einer Statusaenderung oder Aktualisierung einer Variable 																						*/
   /* array($params[0], $params[1],             $params[2],),                     										*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,false',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,true,20',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on:true,off:false,timer#dawn-23:45',),       			*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on:true,off:false,if:light',),       				*/

	//print_r($params);
	$auto=new Autosteuerung(); 						/* um Auto Klasse auch in der Funktion verwenden zu können */
	$nachrichtenVent=new AutosteuerungRegler();			/* Nachrichten fuer Regler hier sammeln */

	/* alten Wert der Variable ermitteln um den Unterschied erkennen, gleich, groesser, kleiner 
	 * neuen Wert gleichzeitig schreiben
	 */
	$oldValue=$auto->setNewValue($variableID,$status);	
	if ($wertOpt=="") IPSLogger_Inf(__file__, 'Aufruf Routine Heatcontrol mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status.' der Variable '.$variableID.' alter Wert war : '.$oldValue);
	
	$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	
	$parges=$auto->ParseCommand($params,$status,$simulate);
	$command=array(); $entry=1;	
		
	/* nun sind jedem Parameter Befehle zugeordnet die nun abgearbeitet werden, Kommando fuer Kommando */
	//print_r($parges);
	foreach ($parges as $kom => $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  					/* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;					/* neuer Wert der den befehl ausgelöst hat */
		$command[$entry]["OLDSTATUS"]=$oldValue;			/* alter Wert, vor der Änderung */	
		$command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */	
			
		foreach ($Kommando as $num => $befehl)
			{
			//echo $kom."|".$num." : Bearbeite Befehl ".$befehl[0]."\n";
			switch (strtoupper($befehl[0]))
				{
				default:
					$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					break;
				}	
			} /* Ende foreach Befehl */
		echo "Ergebnis EvaluateCommand ".$entry." : ".json_encode($command[$entry])."\n";
		$auto->ControlSwitchLevel($command[$entry],$simulate);	
		
		if ($simulate) echo $command[$entry]["COMMENT"]."\n";
		else 
			{
			if (strlen($command[$entry]["COMMENT"])>4) $nachrichtenVent->LogNachrichten(substr($command[$entry]["COMMENT"],0,100));
			if (strlen($command[$entry]["COMMENT"])>104) $nachrichtenVent->LogNachrichten(substr($command[$entry]["COMMENT"],100));
			}
		//if (strlen($command[$entry]["COMMENT"])>4) $log_Autosteuerung->LogNachrichten(substr($command[$entry]["COMMENT"],0,100));
		//if (strlen($command[$entry]["COMMENT"])>140) $log_Autosteuerung->LogNachrichten(substr($command[$entry]["COMMENT"],140));	

		//$log_Autosteuerung->LogNachrichten('Variablenaenderung von '.$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).').'.$result);
					
		$result=$auto->ExecuteCommand($command[$entry],$simulate);
		
		if ($simulate) echo $result["COMMENT"]."\n";
		//else $nachrichtenVent->LogNachrichten("Erg: ".$result["COMMENT"]);
        $ergebnis=$auto->timerCommand($result,$simulate);		
		//print_r($command[$entry]);	

		/********************
		 *
		 * Timer wird einmail aufgerufen um nach Ablauf wieder den vorigen Zustand herzustellen.
		 * Bei DIM Befehl anders, hier wird der unter DIM#LEVEL definierte Zustand während der Zeit DIM#DELAY versucht zu erreichen
		 * 
		 * Delay ist davon unabhängig und kann zusätzlich verwendet werden
		 *
		 * nur machen wenn if condition erfüllt ist, andernfalls wird der Timer ueberschrieben
		 *
							
		if ($result["SWITCH"]===true)
			{
			if (isset($result["DIM"])==true)
				{
				echo "**********Execute Command Dim mit Level : ".$result["DIM#LEVEL"]." und Time : ".$result["DIM#TIME"]." Ausgangswert : ".$result["VALUE_ON"]." für OID ".$result["OID"]."\n";
				$value=(integer)(($result["DIM#LEVEL"]-$result["VALUE_ON"])/10);
				$time=(integer)($result["DIM#TIME"]/10);
				$EreignisID = @IPS_GetEventIDByName($result["NAME"]."_EVENT_DIM", IPS_GetParent($_IPS["SELF"]));
			
				$befehl="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
				$befehl.='$value=$lightManager->GetValue('.$result["OID"].')+'.$value.";\n";
				$befehl.='if ($value<=('.$result["DIM#LEVEL"].')) {'."\n";
				$befehl.='  $lightManager->SetValue('.$result["OID"].',$value); } '."\n".'else {'."\n";
				$befehl.='  IPS_SetEventActive('.$EreignisID.',false);}'."\n";
				$befehl.='IPSLogger_Dbg(__file__, "Command Dim '.$result["NAME"].' mit aktuellem Wert : ".$value."   ");'."\n";
				echo "   Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$result["COMMAND"])."\n";
				echo "   Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$befehl)."\n";
				if ($simulate==false)
					{
					setDimTimer($result["NAME"],$time,$befehl);
					}
				}
			if (isset($result["DELAY"])==true)
				{
				if ($result["DELAY"]>0)
					{
					echo "Execute Command Delay, Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$result["COMMAND"])."\n";
					//print_r($result);
					if ($simulate==false)
						{
						setEventTimer($result["NAME"],$result["DELAY"],$result["COMMAND"]);
						}
					}
				}
			}	*/
		$entry++;			
		} /* Ende foreach Kommando */
	return($command);
	}

/********************************************************************************************
 *
 *  Alexa
 *
 * passt nicht ganz dazu. Soll aber wie die Routinen Status die Befehle die von Alexa kommen abarbeiten.
 * Parameter Struktur ist etwas anders, statt der Variable ID wird ein Request Identifier der die Art der Anforderung beschreibt mitgeliefert.
 *
 *************************************************************************************************/

function Alexa($params,$status,$request,$simulate=false,$wertOpt="")
	{
	$auto=new Autosteuerung(); 						/* um Auto Klasse auch in der Funktion verwenden zu können */
	$parges=$auto->ParseCommand($params,$status,$simulate);
	$command=array(); $entry=1;	
		
	/* nun sind jedem Parameter Befehle zugeordnet die nun abgearbeitet werden, Kommando fuer Kommando */
	foreach ($parges as $kom => $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  				/* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;					/* neuer Wert der den Befehl ausgelöst hat */
		//$command[$entry]["OLDSTATUS"]=$oldValue;			/* alter Wert, vor der Änderung */	
		$command[$entry]["SOURCEID"]=$request;				/* normalerweise Variable ID des Wertes, hier der von Alexa erkannte Befehl */	
			
		foreach ($Kommando as $num => $befehl)
			{
			//echo $kom."|".$num." : Bearbeite Befehl ".$befehl[0]."\n";
			switch (strtoupper($befehl[0]))
				{
				default:
					$auto->EvaluateCommand($befehl,$command[$entry],$simulate);
					break;
				}	
			} /* Ende foreach Befehl */
		echo "Ergebnis EvaluateCommand ".$entry." : ".json_encode($command[$entry])."\n";
		$auto->ControlSwitchLevel($command[$entry],$simulate);	
		
		if ($simulate) echo "Bislang erhaltene Kommentare : ".$command[$entry]["COMMENT"]."\n";
		else 
			{
			if (strlen($command[$entry]["COMMENT"])>4) $nachrichtenVent->LogNachrichten("Com: ".substr($command[$entry]["COMMENT"],0,100));
			if (strlen($command[$entry]["COMMENT"])>104) $nachrichtenVent->LogNachrichten("Com: ".substr($command[$entry]["COMMENT"],100));
			}

		$result=$auto->ExecuteCommand($command[$entry],$simulate);
		
		if ($simulate) echo "Bislang erhaltene Kommentare : ".$result["COMMENT"]."\n";
        $ergebnis=$auto->timerCommand($result,$simulate);
		/********************
		 *
		 * Timer wird einmal aufgerufen um nach Ablauf wieder den vorigen Zustand herzustellen.
		 * Bei DIM Befehl anders, hier wird der unter DIM#LEVEL definierte Zustand während der Zeit DIM#DELAY versucht zu erreichen
		 * 
		 * Delay ist davon unabhängig und kann zusätzlich verwendet werden
		 *
		 * nur machen wenn if condition erfüllt ist, andernfalls wird der Timer ueberschrieben
		 *
		 ************************************************
		if ($result["SWITCH"]===true)
			{
			if (isset($result["DIM"])==true)
				{
				echo "**********Execute Command Dim mit Level : ".$result["DIM#LEVEL"]." und Time : ".$result["DIM#TIME"]." Ausgangswert : ".$result["VALUE_ON"]." für OID ".$result["OID"]."\n";
				$value=(integer)(($result["DIM#LEVEL"]-$result["VALUE_ON"])/10);
				$time=(integer)($result["DIM#TIME"]/10);
				$EreignisID = @IPS_GetEventIDByName($result["NAME"]."_EVENT_DIM", IPS_GetParent($_IPS["SELF"]));
			
				$befehl="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
				$befehl.='$value=$lightManager->GetValue('.$result["OID"].')+'.$value.";\n";
				$befehl.='if ($value<=('.$result["DIM#LEVEL"].')) {'."\n";
				$befehl.='  $lightManager->SetValue('.$result["OID"].',$value); } '."\n".'else {'."\n";
				$befehl.='  IPS_SetEventActive('.$EreignisID.',false);}'."\n";
				$befehl.='IPSLogger_Dbg(__file__, "Command Dim '.$result["NAME"].' mit aktuellem Wert : ".$value."   ");'."\n";
				echo "   Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$result["COMMAND"])."\n";
				echo "   Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$befehl)."\n";
				if ($simulate==false)
					{
					setDimTimer($result["NAME"],$time,$befehl);
					}
				}
			if (isset($result["DELAY"])==true)
				{
				if ($result["DELAY"]>0)
					{
					echo "Execute Command Delay, Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$result["COMMAND"])."\n";
					//print_r($result);
					if ($simulate==false)
						{
						setEventTimer($result["NAME"],$result["DELAY"],$result["COMMAND"]);
						}
					}
				}
			}	*/
		$entry++;			
		} /* Ende foreach Kommando */
	return($command);

	}
	
/********************************************************************************************
 *
 * alte Routine, schaltet eigentlich nur den Hauptschalter ein und aus, noch benötigt .....
 *
 *
 ************************************************************************************************/

function Ventilator1($params,$status,$variableID,$simulate=false)
	{
	global $categoryId_Autosteuerung,$params;
	
	$VentilatorsteuerungID = IPS_GetObjectIDByName("Ventilatorsteuerung",$categoryId_Autosteuerung);

   /* Funktion um Ventilatorsteuerung ein und aus zuschalten */
	$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));
   $eventName = 'OnChange_'.$_IPS['VARIABLE'];
	$eventId   = @IPS_GetObjectIDByIdent($eventName, $scriptId);
   If (GetValue($VentilatorsteuerungID)>0)
     	{
		if ($eventId === false)
			{
			$eventId = IPS_CreateEvent(0);
			IPS_SetName($eventId, $eventName);
			IPS_SetIdent($eventId, $eventName);
			IPS_SetEventTrigger($eventId, 1, $params[3]);
			IPS_SetParent($eventId, $scriptId);
			IPS_SetEventActive($eventId, true);
			IPSLogger_Dbg (__file__, 'Created IPSMessageHandler Event for Variable='.$params[3]);
			}
		else
			{
			echo "EventName uns ID: ".$eventName."  ".$eventId."\n";
			}
		}
	else
	   {
		IPS_SetEventActive($eventId, false);
	   }
   }

/********************************************************************************************
 *
 * Ventilatorroutine (alt) in stastus uebernehmen
 * diese Routine macht genau die Funktion die in BKS funktioniert
 *
 * in BKS für Deckenventilator so aufgesetzt, spricht auch die Temperatur:
 *  
 *	xxxxxx => array('OnChange','Ventilator','Ventilator,25,true,24,false',),        
 *
 *
 *
 *********************************************************************************************/

function Ventilator($params,$status,$variableID,$simulate=false)
	{
	global $categoryId_Autosteuerung,$speak_config;

	IPSLogger_Dbg(__file__, 'Aufruf Routine Ventilator mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	echo 'Aufruf Routine Ventilator mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status."\n";
	
	$VentilatorsteuerungID = IPS_GetObjectIDByName("Ventilatorsteuerung",$categoryId_Autosteuerung);
  	$moduleParams2 = explode(',', $params[2]);

	echo "Es wird ".$moduleParams2[0]." geschaltet und der Status von ".$VentilatorsteuerungID." ist ".getValueFormatted($VentilatorsteuerungID).".\n";
	if (GetValue($VentilatorsteuerungID)==0)
	   	{
  		IPSLight_SetSwitchByName($moduleParams2[0],false);
	   	}
	if (GetValue($VentilatorsteuerungID)==1)
	   	{
  		IPSLight_SetSwitchByName($moduleParams2[0],true);
	   	}
	if (GetValue($VentilatorsteuerungID)==2)
	   	{
      	/* wenn Parameter ueberschritten etwas tun */
   		//$temperatur=GetValue($_IPS['VARIABLE']);
		$temperatur=$status;

		$TemperaturID = IPS_GetObjectIDByName("Temperatur",$VentilatorsteuerungID);	
		$TemperaturZuletztID = 	IPS_GetObjectIDByName("TemperaturZuletzt",$VentilatorsteuerungID);
		if (abs($temperatur - GetValue($TemperaturZuletztID)) > 0.9) 
			{
			SetValue($TemperaturZuletztID,$temperatur);
 			tts_play(1,'Temperatur im Wohnzimmer '.floor($temperatur)." Komma ".floor(($temperatur-floor($temperatur))*10)." Grad.",'',2);
			}
		SetValue($TemperaturID,$temperatur);

   		if ($speak_config["Parameter"][1]=="Debug")
  		   	{
  			tts_play(1,'Temperatur im Wohnzimmer '.floor($temperatur)." Komma ".floor(($temperatur-floor($temperatur))*10)." Grad.",'',2);
  			}

     	//print_r($moduleParams2);
     	if ($moduleParams2[2]=="true") {$switch_ein=true;} else {$switch_ein=false; }
  	  	if ($moduleParams2[4]=="true") {$switch_aus=true;} else {$switch_aus=false; }
  		$lightManager = new IPSLight_Manager();
		$switchID=$lightManager->GetSwitchIdByName($moduleParams2[0]);
		$status=$lightManager->GetValue($switchID);
     	if ($temperatur>$moduleParams2[1])
  	  	   	{
			if ($status==false)
			   	{
	     		IPSLight_SetSwitchByName($moduleParams2[0],$switch_ein);
		     	if ($speak_config["Parameter"][1]=="Debug")
	   	   			{
	     			tts_play(1,"Ventilator ein.",'',2);
		  			}
		  		}
	  		}
	  	if ($temperatur<$moduleParams2[3])
	  	   	{
			if ($status==true)
			   	{
		     	IPSLight_SetSwitchByName($moduleParams2[0],$switch_aus);
	   	  		if ($speak_config["Parameter"][1]=="Debug")
	  	   			{
	  				tts_play(1,"Ventilator aus.",'',2);
	  				}
	  			}
	    	}
		} /* ende if Auto */
	}

/*********************************************************************************************/

/*********************************************************************************************/

function Parameter($params,$status,$variableID,$simulate=false)
	{
	global $speak_config,$params;
	
	/* wenn Parameter ueberschritten etwas tun */
	$temperatur=GetValue($_IPS['VARIABLE']);
	if ($speak_config["Parameter"][1]=="Debug")
	   {
		tts_play(1,'Temperatur im Wohnzimmer '.floor($temperatur)." Komma ".floor(($temperatur-floor($temperatur))*10)." Grad.",'',2);
		}
	$moduleParams2 = explode(',', $params[2]);
	//print_r($moduleParams2);
	if ($moduleParams2[2]=="true") {$switch_ein=true;} else {$switch_ein=false; }
	if ($moduleParams2[4]=="true") {$switch_aus=true;} else {$switch_aus=false; }
	$lightManager = new IPSLight_Manager();
	$switchID=$lightManager->GetSwitchIdByName($moduleParams2[0]);
	$status=$lightManager->GetValue($switchID);
	if ($temperatur>$moduleParams2[1])
	   {
		if ($status==false)
		   {
	     	IPSLight_SetSwitchByName($moduleParams2[0],$switch_ein);
	     	if ($speak_config["Parameter"][1]=="Debug")
	  	   	{
	  			tts_play(1,"Ventilator ein.",'',2);
	  			}
	  		}
	  	}
  	if ($temperatur<$moduleParams2[3])
  	   {
		if ($status==true)
		   {
	     	IPSLight_SetSwitchByName($moduleParams2[0],$switch_aus);
	     	if ($speak_config["Parameter"][1]=="Debug")
	  	   	{
	  			tts_play(1,"Ventilator aus.",'',2);
				}
			}
	  	}
	}

/*********************************************************************************************/

function parseParameter($params,$result=array())
	{
	if (count($params)>1)
		{
		$result[$params[0]]=$params;
		}
	return($result);
	}

/********************************************************************************************
 *
 * Zuordnung webfrontLink in einer Zeile
 * es werden $webfront_link["TAB"] und $webfront_link["TABNAME"] beschrieben
 * es bleibt bei TAB Autosteurung wenn kein OWNTAB definiert ist.
 * bei OWNTAB wird TAB auf einen neuen eigenen TAB gesetzt und ein neuer Name dafür definiert, sonst wird der per Default übergebene verwednet
 *
 ***************************************************************************/


function defineWebfrontLink($AutoSetSwitch, $default)
    {
    $webfront_link["TAB"]="Autosteuerung";
	if ( isset( $AutoSetSwitch["OWNTAB"] ) == true )				/* es ist doch ein Tab konfiguriert, kann immer noch der selbe sein */
	    {
		$webfront_link["TAB"]=$AutoSetSwitch["OWNTAB"];	/* Default Tab Name ueberschreiben */
		if ( isset( $AutoSetSwitch["TABNAME"] ) == true )
			{
			$webfront_link["TABNAME"]=$AutoSetSwitch["TABNAME"];		/* und wenn gewuenscht auch noch einen speziellen namen dafür vergeben */
			}
		else $webfront_link["TABNAME"]=$default; 						
		}
    return ($webfront_link);
    }


/*********************************************************************************************/

function test()
	{
	global $AnwesenheitssimulationID,$NachrichtenScriptID,$NachrichtenInputID;

	echo "Anwesenheitsimulation  ID : ".$AnwesenheitssimulationID." \n";
	echo "Nachrichten Script     ID : ".$NachrichtenScriptID."\n";
	echo "Nachrichten Input      ID : ".$NachrichtenInputID."\n";
	}

/*********************************************************************************************/




?>