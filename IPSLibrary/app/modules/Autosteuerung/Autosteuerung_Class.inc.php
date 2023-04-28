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
 *      getConfig
 *      Anwesend
 *      setLogicAnwesend
 *      getLogicAnwesend
 *      writeTopologyTable
 *      getGeofencyInformation
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
 *    AutosteuerungFunktionen               Abstract Class für, liefert jeweils eigenes Logging, rechts vom Webfront dargestellt
 *       AutosteuerungRegler
 *       AutosteuerungAnwesenheitsSimulation
 *       AutosteuerungAlexa
 *       AutoSteuerungStromheizung für Funktionen rund um die Heizungssteuerung
 *
 * alleinstehende functions die die Funktionen aus dem configfile übernehmen
 *	    Anwesenheit
 *      iTunesSteuerung
 *      GutenMorgenWecker
 *      Status, StatusParallel
 *        SwitchFunction
 *      Ventilator2         Heatcontrol
 *      Alexa               Unterstützung der Steuerung über Alexa
 *
 *      StatusRGB sollte nicht mehr in Verwendung sein
 *      Ventilator, Ventilator1         alte Routinen
 *
 *      parseParemeter, Parameter
 *      defineWebfrontLink
 *      test
 *
 *
 *
 *
 *
 *    
 *  
 **************************************************************************************************************/
 
 
/*****************************************************************************************************
 *
 * AutosteuerungHandler zum Anlegen der Konfigurationszeilen im config File
 *
 * werden mittlerweile groestenteils haendisch angelegt, es geht aber auch automatisch, zB für Standardvariablen
 *
 *      Get_EventConfigurationAuto
 *      Set_EventConfigurationAuto
 *      CreateEvent
 *      StoreEventConfiguration
 *      RegisterAutoEvent
 *      UnRegisterAutoEvent 
 *
 **************************************************************************************************************/ 

class AutosteuerungHandler 
	{

		private static $eventConfigurationAuto = array();
		private static $scriptID;

        protected $configuration;       // die Konfiguration

		/**
		 * @public
		 *
		 * Initialisierung des IPSMessageHandlers
		 *
		 */
		public function __construct($scriptID=false) 
            {
            if ($scriptID===false)
                {
                $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
                if (!isset($moduleManager)) 
                    {
                    IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
                    $moduleManager = new IPSModuleManager('Autosteuerung',$repository);
                    }
                $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
                $scriptID  = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);                    
                } 
            /* standardize configuration */       
            $this->configuration = $this->set_Configuration();
			self::$scriptID=$scriptID;
		    }

        /* Konfigurationsmanagement , Abstraktion mit set und get im AutosteuerungHandler 
         * behandelt das LogDirectory und HeatControl aus Autosteuerung_Setup
         *
         */

        private function set_Configuration()
            {
            $config=array();
            if ((function_exists("Autosteuerung_Setup"))===false) IPSUtils_Include ('Autosteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Autosteuerung');				
            if (function_exists("Autosteuerung_Setup")) $configInput = Autosteuerung_Setup();
            else $configInput=array();

            // vernünftiges Logdirectory aufsetzen , copy from configInput to config     
            configfileParser($configInput, $config, ["LogDirectory" ],"LogDirectory" ,"/Autosteuerung/");  
            $dosOps = new dosOps();
            $systemDir     = $dosOps->getWorkDirectory(); 
            if (strpos($config["LogDirectory"],"C:/Scripts/")===0) $config["LogDirectory"]=substr($config["LogDirectory"],10);      // Workaround für C:/Scripts"
            $config["LogDirectory"] = $dosOps->correctDirName($systemDir.$config["LogDirectory"]);
            configfileParser($configInput, $configHeatControl, ["HeatControl" ],"HeatControl" ,null);  
            //print_R($configHeatControl);
            configfileParser($configHeatControl["HeatControl" ], $config["HeatControl" ], ["EVENT_IPSHEAT","SwitchName" ],"SwitchName" ,null);  
            configfileParser($configHeatControl["HeatControl" ], $config["HeatControl" ], ["Module" ],"Module" ,"IPSHeat");  
            configfileParser($configHeatControl["HeatControl" ], $config["HeatControl" ], ["Type" ],"Type" ,"Switch");  
            configfileParser($configHeatControl["HeatControl" ], $config["HeatControl" ], ["AutoFill","Autofill","autofill","AUTOFILL" ],"AutoFill" ,"Aus");          //Default bedeutet Heizung Aus nach einem Update, ReInstall
            configfileParser($configHeatControl["HeatControl" ], $config["HeatControl" ], ["setTemp","settemp","Settemp","SETTEMP","SetTemp" ],"setTemp" ,22);          //Default bedeutet Heizung Aus nach einem Update, ReInstall
            //print_r($config);
            return ($config);
            }

        public function get_Configuration()
            {
            return ($this->configuration);
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
		private static function StoreEventConfiguration($configuration, $debug=false)
		   {
			// Build Configuration String
			$configString = '$eventConfiguration = array(';
			if ($debug) echo "StoreEventConfiguration, Configuration wird jetzt wieder gespeichert:\n";
			//print_r($configuration);

			foreach ($configuration as $variableId=>$params) 
				{
                $configString .= PHP_EOL.chr(9).chr(9).chr(9).$variableId.' => array(';
                for ($i=0; $i<count($params); $i=$i+3) 
                    {
                    if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';
                    $configString .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
                    }
                $configString .= '),';                
                if (IPS_ObjectExists($variableId)==false) 
                    {
                    if ($debug) echo "Objekt $variableId nicht angelegt. Parameter: ".json_encode($params).". Trotzdem in config übernehmen damit es nicht verloren geht ...\n";
                    $configString .= '        /* !!! UNKNOWN variableID  */';                    
                    }
                else
                    {
                    if ($debug) echo "   process ".$variableId."  (".IPS_GetName(IPS_GetParent($variableId))."/".IPS_GetName($variableId).")\n";
                    $configString .= '        /* '.IPS_GetName($variableId).'  '.IPS_GetName(IPS_GetParent($variableId)).'     */';
                    }
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

		function registerAutoEvent($variableId, $eventType, $componentParams, $moduleParams, $debug=false)
			{
			$configuration = self::Get_EventConfigurationAuto();
            if ($debug) echo "register Autoevent für $variableId (".IPS_GetName($variableId)."/".IPS_GetName(IPS_GetParent($variableId)).")\n";
			//echo "---> war gespeichert.\n";

			if (array_key_exists($variableId, $configuration))
				{
                if ($debug) echo "  Variable mit ID : ".$variableId." : Event Type $eventType und Parameter, ComponentPars: ".$componentParams." ModulPars: ".$moduleParams." vorhanden.\n";
    			//print_r($configuration[$variableId]);
				$moduleParamsNew = explode(',', $moduleParams);
				$moduleClassNew  = $moduleParamsNew[0];

				$params = $configuration[$variableId];
				//echo "Bearbeite Variable with ID : ".$variableId." : ".count($params)." Parameter, ComponentPars: ".$componentParams." ModulPars: ".$moduleParams."\n";
				//print_r($params);
				$ct_par=count($params);
				if (($ct_par%3)>0)
				    {
					if ($debug) echo "Anzahl Parameter bei ID ".$variableId." : ".$ct_par." da sind ",($ct_par%3)." Parameter zuviel.\n";
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
                if ($debug) echo "Lege neue Variable mit ID : ".$variableId." : Event Type $eventType und Parameter, ComponentPars: ".$componentParams." ModulPars: ".$moduleParams." an.\n";
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
 *    AutosteuerungConfiguration, abstract class für:
 *       AutosteuerungConfigurationAlexa 
 *       AutosteuerungConfigurationHandler
 *
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
		 * Druckt die aktuelle Auto Event Konfiguration von function Alexa_GetEventConfiguration
		 *
		 * @return string[] Event Konfiguration
		 */

		function PrintAutoEvent($debug=false) 
			{
            if ($debug) echo "PrintAutoEvent, Function Identifier ".$this->identifier."\n";
			$configuration = $this->Get_EventConfigurationAuto();
			//print_r($configuration);
			if (sizeof($configuration)==0 )
				{
				if ($debug) echo "  No configuration stored.\n";
				}
			else
				{
				$print=array();
				if ($debug) echo "  Configuration has ".sizeof($configuration)." entries.\n";
				foreach ($configuration as $id => $entry)
					{
                    /*    
                    //if ($debug) print_r($entry);
                    $entry2=str_replace("\n","",$entry[2]);
                    //if ($debug) echo "$id    $entry2 \n";                   // entry2 ist zu lange, ueberschüssige blanks weiterhin enthalten, daraus eine Standard Routine machen
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
                    echo "Befehl $entry3 \n"; */
                    $ips = new ipsOps();
                    $command=$ips->trimCommand($entry[2]);
					$print[$id]["ID"]=$id;
                    if ((is_numeric($id))==false) $print[$id]["NAME"]="unknown OID";
					else $print[$id]["NAME"]=IPS_GetName($id)."/".IPS_GetName(IPS_GetParent($id))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($id)));
					$print[$id]["MODE"]=$entry[0];
					$print[$id]["FUNCTION"]=$entry[1];
					$print[$id]["COMMAND"]=$command;
					}
				/* sortieren */
                //print_r($print);
				unset($entry);
				foreach ($this->sortList("NAME",$print,"SORT_ASC") as $entry) echo "  ".$entry["ID"]." (".str_pad($entry["NAME"].")",50)." => (".$entry["MODE"]."|".$entry["FUNCTION"]."|".$entry["COMMAND"].",)\n";

				}
			return $configuration;
			}

    /************************************************************
     *
     * liste nach Kriterien/Ueberschriften sortieren
	 * $on ist der Key nach dessen Inhalt sortiert werden soll, $array ist das array mit den Werten
	 * $order kann SORT_ASC oder SORT_DESC sein
	 * Das sortierte $new_array wird als return wert ausgegeben, $sortable_array wird aus $array anhand des keys nachdem sortiert werden soll aufgebaut
	 * bei eindimensionalen arrays wird der Wert des Keys übernommen und nach diesem Wert sortiert
	 *
	 * das array ist multidemsional aufgebaut [ 0 => ["Index"=>"Wert", "Eintrag" => "leer"], 1 => ["Index"=>"String", "Eintrag" => "Voll"]]
	 * sortable_arry = [0 =>"Leer", "1" => "Voll"] wenn nach Eintrag sortiert werden soll
     *
     ****************************************************************************/

	private function sortList($on,$array=array(),$order="SORT_ASC")
		{
		$new_array = array();
		$sortable_array = array();
		if (count($array) > 0) 
			{
			foreach ($array as $k => $v) 
				{
				if (is_array($v)) 
					{
					foreach ($v as $k2 => $v2) 
						{
						if ($k2 == $on) 
							{
							$sortable_array[$k] = $v2;
							}
						}
					} 
				else 
					{
					$sortable_array[$k] = $v;
					}
				}
			switch ($order) 
				{
				case "SORT_ASC":
					asort($sortable_array);
					break;
				case "SORT_DESC":
					arsort($sortable_array);
					break;
				}
			foreach ($sortable_array as $k => $v) 
				{
				$new_array[$k] = $array[$k];
				}
			}
		return $new_array;
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
 *      getConfig
 *      MonitorStatus
 *      getLogicMonitorConf
 *      Anwesend
 *      setLogicAnwesend
 *      getLogicAnwesend
 *      colorCodeAnwesenheit
 *      writeTopologyTable
 *      getGeofencyInformation
 *
 * für die Ermittleung von Delayed Werten wird das Modul DetectMovement benötigt das die Werte in Ihrer Katorie unter Detect_Movement speichert
 *
 *
 *
 **************************************************************************************************************/

class AutosteuerungOperator 
	{
	
	private $logicAnwesend;			// die überarbeitete Konfiguration
	private $motionDetect_DataID;	// hier sind die verzoegerten Spiegelvariablen gespeichert 

	public function __construct($debug=false)
		{
		//IPSLogger_Dbg(__file__, 'Construct Class AutosteuerungOperator.');
		
		/* Verzoegerte Motion Detection Variablen im Modul DetectMovement finden */
		$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
		$installedmodules=$moduleManager->GetInstalledModules();
		if (isset ($installedmodules["DetectMovement"]))
			{
			$moduleManagerDM = new IPSModuleManager('DetectMovement');     /*   <--- change here */
			$CategoryIdDataDM     = $moduleManagerDM->GetModuleCategoryID('data');
			$this->motionDetect_DataID=@IPS_GetObjectIDByName("Motion-Detect",$CategoryIdDataDM);
			}
				
		$this->setLogicAnwesend();              // Config auslesen und logicAnwesend abspeichern
		}

	/* Ausgabe der privaten Config Variable, das ist die Config aus dem include File in ein allgemeines Format gebracht */

	public function getConfig()
		{
		return($this->logicAnwesend);
		}

    /* Delayed Einträge als indexierte Liste ausgeben
     */

	public function getConfigDelayed()
		{
        $delayed=array();
        foreach($this->getConfig() as $type => $operation)
            {
            foreach ($operation as $oid=>$topology) 
                {
                if (isset($topology["Delayed"])) $delayed[$topology["Delayed"]]=array();
                }    
            }            
		return($delayed);
		}


    /*
     * Im Configfile gibt es eine Möglichkeit den gewünschten  Status des Monitors (Ein/Aus) aus einer OR und AND Verknüpfung von  Statuswerten zu ermitteln.
     * Das ist die schnellste Art Monitor Ein/Aus zu ermitteln. Wird im Autosteuerungs Handler alle 60 Sekunden aufgerufen.
     *
     *
     */

	public function MonitorStatus($debug=false)
        {
		$result=false;
		$config=$this->getLogicMonitorConf($debug);           // angepasste, standardisierte Konfiguration
		$operator="";
        if ($debug) echo "AutosteuerungOperator::MonitorStatus Berechnung nach Formel: ".json_encode($config)."\n";
		foreach($config as $type => $operation)
			{
            if ($debug) echo "   Operator $type :".json_encode($operation)."\n";
			if (strtoupper($type) == "OR")
				{
				$operator.="OR";
				foreach ($operation as $oid=>$formula)
					{
                    $name=IPS_GetName($oid);
                    if (is_array($formula)) 
                        {
                        foreach ($formula as $operator => $value)
                            {
                            $valueStored=GetValue($oid);
                            switch (strtoupper($operator))
                                {
                                case "EQ":
                                    if ($valueStored==$value)
                                        {
                                        //echo "Equal result true for $name with value ".($valueStored?"true":"false")."\n";
                                        if ($debug) echo "      Equal result true for $name with value ".GetValueIfFormatted($oid)."\n";
                                        $result=true;
                                        }
                                    else 
                                        {
                                        if ($debug) echo "      Equal result false for $name with value ".GetValueIfFormatted($oid)."\n";
                                        }
                                    break;
                                default:
                                    echo "AutosteuerungOperator::MonitorStatus kenne Befehl $operator nicht.\n";
                                    break;    
                                }
                            }
                        }
                    else
                        {   
                        $result = $result || GetValueBoolean($oid);
                        $operator.=" $name ($formula)";
					    //echo "Operation OR for OID : ".$oid." ".GetValue($oid)." Result : ".$result."\n";
                        }
					}
				}
			elseif (strtoupper($type) == "AND")
				{
				$operator.=" AND";				
				foreach ($operation as $oid=>$formula)
					{
                    $name=IPS_GetName($oid);
                    if (is_array($formula)) 
                        {

                        }
                    else
                        {
                        $result = $result && GetValue($oid);
                        $operator.=" $name ($formula)";
					    //echo "Operation AND for OID : ".$oid." ".GetValue($oid)." ".$result."\n";
                        }
					}
				}
			}
        if ($debug) echo "   AutosteuerungOperator: Sollstatus Monitor Auswertung: $operator = ".($result?"Ein":"Aus")."   ($result)\n";
		IPSLogger_Dbg(__file__, 'AutosteuerungOperator: Sollstatus Monitor Auswertung: '.$operator.'.= '.($result?"Ein":"Aus"));
		return ($result);				
        }

    /* get Logic Monitor Configuration
     * Analyse der entsprechenden Konfiguration und abspeichern in einem class object
     * Switchname und Condition müssen mindestens vorhanden sein 
     * dient zur Entkopplung der Konfiguration von den internen Prozessen
     * keine automatische Delayed Funktion, Objekt muss dezidiert angegeben werden
     *
     */ 

	public function getLogicMonitorConf($debug=false)
		{
		IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
        $config=array();
        if (function_exists("Autosteuerung_MonitorMode"))
            {
            $configMonitor=Autosteuerung_MonitorMode();
            /* Vervollständigung der Konfiguration */            
            if ($debug) echo "MonitorMode Konfiguration analysieren:\n";
            if (isset($configMonitor["Condition"]) )
                {
                $configCondition=$configMonitor["Condition"];
                foreach($configCondition as $type => $operation)
                    {
                    switch (strtoupper($type))
                        {
                        case "OR":
                        case "AND":
                            if ($debug) echo "  Type $type erkannt, analysiere : ".json_encode($operation)."\n";
                            foreach ($operation as $index => $oid)
                                {
                                if (is_array($oid))             // array("x" => 1,"y" => 1,"ShortName" => "AZ")
                                    {
                                    //$name=IPS_GetName($index);
                                    if ($debug) echo " --> Index $index (".IPS_GetName($index)."/".IPS_GetName(IPS_GetParent($index))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($index))).").\n";
                                    //$config[$type][$index] = $oid;
                                    foreach ($oid as $pos => $entry) 
                                        {
                                        $config[$type][$index][$pos] = $entry;
                                        }
                                    }
                                else
                                    {               // wenn kein array definiert nix übernehmen
                                    if ($debug) 
                                        {
                                        echo " --> kein Array, Topology hinzufuegen.\n";
                                        echo " --> Index $oid (".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))).").\n";	
                                        }
                                    }
                                }
                            unset($operation);
                            break;
                        default:
                            echo "  !! class AutosteuerungOperator construct, Index unbekannt, $type erkannt.\n";
                            $config[$type]=$operation;
                            break;
                        }
                    }
                }           // if condition exists
            }       //if function exists
        return ($config);
        }


    /*
     * Im Configfile gibt es eine Möglichkeit die Anwesenheit aus einer OR und AND Verknüpfung von  Statuswerten zu ermitteln.
     * Das ist die schnellste Art Anwesend oder Abwesend zu ermitteln. Wird im Autosteuerungs Handler alle 60 Sekunden aufgerufen.
     *
     *
     */

	public function Anwesend()
		{
		$result=false;
		$delayed = $this->logicAnwesend["Config"]["Delayed"];
		$operator="";
		foreach($this->logicAnwesend as $type => $operation)
			{
			if ($type == "OR")
				{
				$operator.="OR";
				foreach ($operation as $oid=>$topology)
					{
					if ( (isset($topology["Delayed"])) && ($delayed) ) 
						{
						$result = $result || GetValueBoolean($topology["Delayed"]);
						$operator.=" ".IPS_GetName($topology["Delayed"])."(d)";
						}
					else 
						{
						$result = $result || GetValueBoolean($oid);
						$operator.=" ".IPS_GetName($oid);
						}
					//echo "Operation OR for OID : ".$oid." ".GetValue($oid)." Result : ".$result."\n";
					}
				}
			if ($type == "AND")
				{
				$operator.=" AND";				
				foreach ($operation as $oid=>$topology)
					{
					if ( (isset($topology["Delayed"])) && ($delayed) ) 
						{
						$result = $result && GetValue($topology["Delayed"]);
						$operator.=" ".IPS_GetName($topology["Delayed"])."(d)";
						}
					else 
						{
						$result = $result && GetValue($oid);
						$operator.=" ".IPS_GetName($oid);
						}
					//echo "Operation AND for OID : ".$oid." ".GetValue($oid)." ".$result."\n";
					}
				}
			}
		IPSLogger_Dbg(__file__, 'AutosteuerungOperator, Anwesenheitsauswertung: '.$operator.'.= '.($result?"Aus":"Ein"));
		return ($result);				
		}


    /* AutosteuerungOperator::setLogicAnwesend   die Configuration schreiben , get Funktion dazu passend siehe weiter unten
     * analyse der entsprechenden Konfiguration in Autosteuerung_Configuration.inc.php mit der Funktion Autosteuerung_Anwesend und abspeichern in einem class object
     * wird von construct aufgerufen 
     * Funktion Delayed ist Default false, kann man in der Config einschalten
     * AND/OR kann ein Array aus Werten enthalten oder eine Array aus weiteren Arrayzeilen mit der Topologieinformation für eine kleine Minitabelee
     * zu jeder Array Zeile noch den Delayed Wert hinzufügen, das ist der Wert der mit dem selben Namen in der Kategorie $this->motionDetect_DataID steht
     */ 
     
	public function setLogicAnwesend($debug=false)
		{
		IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
        $config=array();
        if (function_exists("Autosteuerung_Anwesend"))
            {
            $configAnwesend=Autosteuerung_Anwesend();
            if (isset($configAnwesend["Config"])==false) $configAnwesend["Config"]["Delayed"]=false;
            if (isset($configAnwesend["Config"]["Delayed"])==false) $configAnwesend["Config"]["Delayed"]=false;
            
            if ($debug) echo "Anwesend Konfiguration analysieren:\n";
            //print_r($configAnwesend);
            foreach($configAnwesend as $type => $operation)
                {
                switch (strtoupper($type))
                    {
                    case "OR":
                    case "AND":
                        if ($debug) echo "  Type $type erkannt:\n";
                        foreach ($operation as $index => $oid)
                            {
                            if (is_array($oid)) 
                                {
                                $name=IPS_GetName($index);
                                $oidEntry=$index;
                                $newPos="x";
                                foreach ($oid as $pos => $entry) 
                                    {
                                    if ($pos === "x") 
                                        {
                                        //echo "x erkannt : $pos mit $entry\n";
                                        $config[$type][$index][$pos] = $entry;
                                        }
                                    elseif ($pos === "y") 
                                        {
                                        //echo "y erkannt : $pos mit $entry\n";
                                        $config[$type][$index][$pos] = $entry;
                                        }
                                    elseif ( ($pos === 0) || ($pos === 1) )
                                        {
                                        if ($debug) echo "   0/1 erkannt : $pos wird zu $newPos mit $entry\n";
                                        $config[$type][$index][$newPos] = $entry;
                                        if ($newPos =="x") $newPos="y";
                                        }
                                    else 
                                        {
                                        if ($debug) echo "   alle anderen uebernehmen : $pos mit $entry\n";
                                        $config[$type][$index][$pos] = $entry;
                                        }	
                                    }
                                }
                            else
                                {  /* add default entry and remove old one */
                                //unset($configAnwesend[$type][$index]);
                                if ($debug) echo " --> kein Array, Topology hinzufuegen.\n";
                                $name=IPS_GetName($oid);
                                $oidEntry=$oid;
                                $config[$type][$oid]=["x" => 0,"y" => 0];
                                }
                            $DataID=@IPS_GetObjectIDByName($name,$this->motionDetect_DataID);							
                            if ($debug) echo " --> Index $oidEntry (".IPS_GetName($oidEntry)."/".IPS_GetName(IPS_GetParent($oidEntry))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oidEntry)))."). Verzoegert : $DataID\n";							//$config[$type][$index] = $oid;
                            if ($DataID===false)
                                {
                                $parentName=IPS_GetName(IPS_GetParent($index));
                                $DataID=@IPS_GetObjectIDByName($parentName,$this->motionDetect_DataID);
                                if ($debug) echo "Suche Detect Movement zu $parentName/$name in ".$this->motionDetect_DataID." : $DataID  \n";
                                }
                            if ($DataID>0) $config[$type][$index]["Delayed"]=$DataID;           // Delayed Paraeter ermitteln und der in der class gespeicherten config hinzufügen   
                            }
                        unset($operation);
                        break;
                    case "CONFIG":                    
                        $config[$type]=$operation;
                        break;
                    default:
                        echo "  !! class AutosteuerungOperator construct, Index unbekannt, $type erkannt.\n";
                        $config[$type]=$operation;
                        break;
                    }
                    
                }
            }
		$this->logicAnwesend=$config;
        return ($config);
        }

    /* AutosteuerungOperator::getLogicAnwesend   das ist die passende Debug Funktion zu Anwesend()
     * hier ausgeben wie berechnet wurde. Könnte als Zusatzinfo in Autosteuerung Anwesenheitserkennung gemacht werden.
     *
     * zwei Ausgabemöglichkeiten, html oder als Array
     * es wird das Array logicAnwesend au der Config durchgegangen und nach Index OR oder AND durchsucht, 
     * Eintrag wird nach oid=>topology ausgewertet
     *
     *
     */ 
	
    public function getLogicAnwesend($htmlOut=false,$dispMode=false,$debug=false)
		{
        if ($debug) echo "getLogicAnwesend aufgerufen. Ausgabe als ".($htmlOut?"Html":"Array").". Displaymode ".($dispMode?$dispMode:"false")."\n";
        $html='<table id="infoAnwesenheit">';                                 // style definition in writeTopologyTable
		$delayed = $this->logicAnwesend["Config"]["Delayed"];
		if ($debug&&false) 
            {
            echo "Eingabewerte erhalten:\n";
            print_r($this->logicAnwesend );
            }
        $result=false; $resultDelayed=false;                                // ie beiden Ergebniswerte für das AND/OR Ergebnis
		$operator="";
		$topologyStatus=array();	                                        // Rückgabewert wenn Array parallel ermitteln
		foreach($this->logicAnwesend as $type => $operation)
			{
			if ($type == "OR")              // entweder OR oder AND, gleiche Funktion
				{
				$operator.="OR(";
                $html.='<tr><td colspan="6">'."Operation OR</td></tr>";
                switch ($dispMode)
                    {
                    case 1:
                        $html.="<tr><td>Name</td><td>Bewegung Ist</td><td>Delayed</td><td>Letzte Änderung</td></tr>";
                        break;
                    default:
                        $html.="<tr><td>Name</td><td>OID</td><td>Bewegung Ist</td><td>Delayed</td><td>Summe<br>Status</td><td>Koordinaten</td></tr>";
                        break;
                    }
				//print_r($operation);
                $first=true;
				foreach ($operation as $oid=>$topology)                 // die OIDs durchgehen, dahinter kommt das Array mit der Topology, hier gibt es auch den Delayed Parameter für die andere Variable
					{
					$result1=GetValueBoolean($oid);                     // Status und wann geändert auslesen
					$statusChanged=IPS_GetVariable($oid)["VariableChanged"];

                    $result = $result || $result1;

					if (isset($topology["Delayed"])) 
                        {
                        $resultDelayed1 = GetValueBoolean($topology["Delayed"]);
                        $statusDelayedChanged=IPS_GetVariable($topology["Delayed"])["VariableChanged"];
                        }
					else 
                        {
                        $resultDelayed1 = false; 
                        $statusDelayedChanged = $statusChanged;
                        }
                    $statusDelay = round(($statusDelayedChanged - $statusChanged)/60);    
                    if ($statusDelay<0) $statusDelay=0;
					$resultDelayed = $resultDelayed || $resultDelayed1;

                   echo "Aktualität der Einträge : ".date("Ymd H:i:s",$statusChanged)." für $statusDelay Minuten, geändert ".date("Ymd H:i:s",$statusDelayedChanged)."\n";
					
                    if ($first) $first=false;
                    else $operator.=",";

					if ( (isset($topology["Delayed"])) && ($delayed) ) $operator.=" ".IPS_GetName($topology["Delayed"])."(d)";
					else $operator.=" ".IPS_GetName($oid);
					
					echo "Operation OR for OID : ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),50)." (".$oid.") ".($result1?"Anwesend":"Abwesend")."  ".($resultDelayed1?"Anwesend":"Abwesend")." Result : ".($result+$resultDelayed)."   [".$topology["x"].",".$topology["y"]."]\n";
                    $html.="<tr><td>".IPS_GetName($oid);
                    switch ($dispMode)
                        {
                        case 1:
                            $html.="</td>".$this->colorCodeAnwesenheit($result1).$this->colorCodeAnwesenheit($resultDelayed1,$statusDelay);
                            $html.="</td><td>".date("d.m.Y H:i:s",$statusChanged);
                            break;
                        default:
                            $html.="</td><td>".$oid;
                            $html.="</td>".$this->colorCodeAnwesenheit($result1).$this->colorCodeAnwesenheit($resultDelayed1,$statusDelay);
                            $html.="<td>".($result+$resultDelayed);
                            $html.="</td><td>[".$topology["x"].",".$topology["y"]."]";
                            break;
                        }
                    $html.="</td></tr>";

					$topologyStatus[$topology["y"]][$topology["x"]]["Status"]=(integer)GetValueBoolean($oid);
					if (isset($topology["Delayed"])) $topologyStatus[$topology["y"]][$topology["x"]]["Status"]+=(integer)GetValueBoolean($topology["Delayed"]);
					if (isset($topology["ShortName"])) $topologyStatus[$topology["y"]][$topology["x"]]["ShortName"]=$topology["ShortName"];
					}
                $operator.=" ) "; 
				}
   
			if ($type == "AND")
				{
				$operator.=" AND(";				
                $html.='<tr><td colspan="6">'."Operation OR</td></tr>";
                $html.="<tr><td>Name</td><td>OID</td><td>Bewegung Ist</td><td>Delayed</td><td>Summe<br>Status</td><td>Koordinaten</td></tr>";
				
                $first=true;
                foreach ($operation as $oid=>$topology)
					{
					$result = $result && GetValue($oid);

                    if ($first) $first=false;
                    else $operator.=",";

					$operator.=" ".IPS_GetName($oid);
					echo "Operation AND for OID : ".IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))." (".$oid.") ".(GetValue($oid)?"Anwesend":"Abwesend")." ".$result."   [".$topology["x"].",".$topology["y"]."]\n";
                    $html.="<tr><td>".IPS_GetName($oid)."</td><td>".$oid."</td>".$this->colorCodeAnwesenheit(GetValue($oid))."<td></td><td>".($result)."</td><td>[".$topology["x"].",".$topology["y"]."]</td></tr>";
					$topologyStatus[$topology["y"]][$topology["x"]]["Status"]=(integer)GetValueBoolean($oid);
					if (isset($topology["Delayed"])) $topologyStatus[$topology["y"]][$topology["x"]]["Status"]+=(integer)GetValueBoolean($topology["Delayed"]);
					if (isset($topology["ShortName"])) $topologyStatus[$topology["y"]][$topology["x"]]["ShortName"]=$topology["ShortName"];
					}
                $operator.=" ) "; 
				}
			}
		echo 'AutosteuerungOperator, Anwesenheitsauswertung: '.$operator.'.= '.($result?"Anwesenheit":"Abwesend")."\n";
        $html.="</table>";
        $html.='<table id="summAnwesenheit"><tr><td>'."Anwesenheitsauswertung:<br> $operator = ".($result?"Anwesenheit":"Abwesend")."</td></tr></table>";
        if ($htmlOut) return ($html);
		else return ($topologyStatus);
		}			

    private function colorCodeAnwesenheit($status,$statusDelay=0)
        {
        if ($status) 
            {
            if ($statusDelay>0) return ('<td class="colorGreen">Anwesend ('.$statusDelay.' Min)</td>');
            else return ('<td class="colorGreen">Anwesend</td>');
            }
        else return ('<td class="colorRed">Abwesend</td>');

        }


	/*
	 * die Topologischen Werte für die Anzeige des Status verwenden, derzeit Anzeige als simple Tabelle, gibt aber schon einen grundsätzlichen Überblick
	 * style für html wird hier definiert, max x für die Breite der Tabelle  wird automatisch definiert
	 * die Werte kommen von Input topology, da ist auch der Status drinnen, also ob Anwesend oder nicht (2,1,0)
     * darunter noch eine Tabelle als Legende zeichnen
     * und noch eine Tabelle mit getLogicAnwesend mit detaillierten Informationen
     *
	 */
	public function writeTopologyTable($topology)
		{
		ksort($topology);
		$html="";
		$html.="<style>";                               // arbeiten mit IDs, eine ID ist ein Feld das mit Java adressierbar ist, hier definieren wie es auszuschauen hat
		$html.="#anwesenheit { border-collapse: collapse; border: 1px solid #ddd;   }";
		$html.="#anwesenheit td, #anwesenheit th { border: 1px solid #ddd; text-align: center; height: 50px; width: 50px; }";
		$html.="#infoAnwesenheit { border-collapse: collapse; border: 1px solid #ddd; width:100%  }";
		$html.="#infoAnwesenheit td, #infoAnwesenheit th { border: 1px solid #ddd; text-align: center; }";
		$html.="#summAnwesenheit { border-collapse: collapse; border: 1px solid #af0; text-align: center; height: 200px; }";    
        $html.="td.colorRed {color:#a00000} td.colorGreen {color:#00a000}";     
		$html.="</style>";
		$html.="<table id=anwesenheit>";
		$maxx=1;
		foreach ($topology as $y => $line)	foreach ($line as $x => $status) {if ($x > $maxx) $maxx=$x; }           //max x für die Breite der Tabelle  wird automatisch definiert

		foreach ($topology as $y => $line)
			{
			$html.="<tr>";
			for ($i=1;$i<=$maxx;$i++)
				{
				$text=$i;
				if (isset($line[$i]["ShortName"])) $text=$line[$i]["ShortName"];
				if (isset($line[$i]["Status"])) 
					{
					if ($line[$i]["Status"]==2) $html.='<td bgcolor="00FF00"> '.$text.' </td>'; 
					elseif ($line[$i]["Status"]==1) $html.='<td bgcolor="00FFFF"> '.$text.' </td>';			
					else $html.='<td bgcolor="0000FF"> '.$text.' </td>';
					}
				else $html.='<td bgcolor="FFFFFF"> '.$text.' </td>';
				}
			$html.="</tr>";
			}
        $html.="</table><br>";
        $html.='<table><tr><td colspan="3">Legende</td></tr>';
        $html.='<tr><td bgcolor="0000FF">off</td><td bgcolor="00FFFF">delayed</td><td bgcolor="00FF00">active</td></tr>';
		$html.="</table><br>";
        $html.=$this->getLogicAnwesend(true,1);           // true html Output der netten Tabelle mit den Zuständen ist und delayed, alternative Darstellung mit Zeitpunkt letzter Bewegung 
        $html.="<br>zuletzt aktualisert am ".date("d.m.Y H:i:s");
		return ($html);
		}


    /* AutosteuerungOperator::getGeofencyInformation
     *
     * bearbeitet die Geofency Informationen wenn Geofency Hooks installiert wurden
     * alles soweit möglich automatisiert herausfinden
     * die geofency Adressen auch gleich mit Archivierung setzen damit grafische Auswertungen ebenfalls möglich sind.
     */
    function getGeofencyInformation($debug=false)
        {
        $geofencyAddresses=array();
	    $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
    	//$modulhandling->printLibraries();
	    if ($debug) echo "\n";

    	//$modulhandling->printModules('Misc Modules');
	    if ($debug) $modulhandling->printInstances('Geofency');
    	$Geofencies=$modulhandling->getInstances('Geofency');
        if (sizeof($Geofencies)>0)
            {
            foreach ($Geofencies as $Geofency)
                {
                /*sollte nur ein Modul sein und unter dem Modul verschiedene Geräte */
                $devices=IPS_GetChildrenIDs($Geofency);
                if (sizeof($devices)>0) 
                    {
                    foreach ($devices as $device)
                        {
                        if ($debug) echo "Instanz $Geofency (".IPS_GetName($Geofency).") mit Gerät $device (".IPS_GetName($device).").\n";
                        $childrens=IPS_GetChildrenIDs($device);
                        $foundAdresse=0;
                        foreach ($childrens as $children)
                            {
                            if (IPS_GetVariable($children)["VariableType"]==0) 
                                {
                                if ($foundAdresse==0) 
                                    {
                                    $foundAdresse=$children;
                                    $geofencyAddresses[$foundAdresse]=IPS_GetName($foundAdresse);
                                    }
                                else echo "**Fehler, zwei Adressen gefunden.\n";
                                }
                            //echo "    ".$children." (".IPS_GetName($children).")  ".IPS_GetVariable($children)["VariableType"]."\n";
                            }
                        if ($foundAdresse) echo "    ".$foundAdresse." (".IPS_GetName($foundAdresse).")  \n";
                        }
                    //print_r($devices);
            	    if ($debug) echo "\n";
                    }
                }
            }
        if ($debug) print_r($geofencyAddresses);

        $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        foreach ($geofencyAddresses as $geofencyAddress => $Address)
            {
            echo "Archive Handler setzen für Variable $geofencyAddress. \n";
    		AC_SetLoggingStatus($archiveHandlerID,$geofencyAddress,true);
	    	AC_SetAggregationType($archiveHandlerID,$geofencyAddress,0);      /* normaler Wwert */
            }
        IPS_ApplyChanges($archiveHandlerID);
        return($geofencyAddresses);
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
 *
 *      isitheatday             schaut nach ob ein Heiztag ist, das ist die Tabelle in Autosteuerung
 *
 *      getDaylight             gibt die Sonnenauf- und -untergangszeiten aus 
 *      switichingTimes         Auswertung der Angaben in den Szenen, Berechnung der Werte für sunrise und sunset 
 *      timeright               Auswertung der Angaben in den Szenen. Schauen ob auf ein oder aus geschaltet werden soll
 *
 *      statusAnwesenheitSimulation     Konfiguration der Anwesenheitsliste als text ausgeben
 *      getScenes               Liest die AWS/TIMER Konfiguration aus, filtert auf die AWS Events, oder Eingabe
 *      getScenesbyId
 *      getChancesById
 *
 *      setNewValue
 *      getOldValue
 *      setNewValueDim
 *      setNewValueIfDif               Vorwert und Änderung erfassen in data::modules::Autosteuerung::Ansteuerung
 *      setNewStatus, setNewStatusBounce            trigger status (boolean) erfassen in data::modules::Autosteuerung::Status 
 *
 *      trimCommand
 *
 *      ParseCommand
 *      parseName, parseParameter, parseValue
 *
 *      findSimilar
 *
 *      getIdByName
 *      switchByTypeModule
 *
 *      EvaluateCommand
 *      evalCondition
 *      evalCom_IFDIF, evalCom_LEVEL, 
 *      ControlSwitchLevel
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
	var $CategoryId_Ansteuerung, $CategoryId_Status;        // werden immer in Install generiert
	var $availableModules;							// eigentlich Liste aller GUIDs der Module, für check ob Parameter ein gültige Modul GID hat
    var $configuration;                     // die Konfiguration
    var $debug;                                             // centrally set Debug Mode

	var $log,$logHtml;										// logging class, called with this class
	
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
	 * construct of Autosteuerung
	 * es wird innerhalb von data.modules.Autosteuerung die Kategorie Ansteuerung erstellt und dort pro 
	 * im Config File angelegter Applikation ein Eintrag erstellt. Derzeit unterstützt:
	 *
	 * Anwesenheitserkennung, Alarmanlage, Stromheizung
	 *
	 *************************************************************/
	
	public function __construct($debug=false)
		{
        $this->debug=$debug;
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
		$this->CategoryId_Status			    = IPS_GetCategoryIDByName("Status", $this->CategoryIdData);

		$this->scriptId_Autosteuerung  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));
        $this->configuration = $this->set_Configuration($debug);
	
        $ipsOps = new ipsOps();
        $NachrichtenID    = $ipsOps->searchIDbyName("Nachrichtenverlauf-Autosteuerung",$this->CategoryIdData);                // needle ist Nachricht
        $NachrichtenAltID = $ipsOps->searchIDbyName("Nachrichtenverlauf-Wichtig",$this->CategoryIdData);
        $NachrichtenScriptID = $ipsOps->searchIDbyName("Nachricht",$this->CategoryIdApp);

        if ($NachrichtenScriptID)           // nicht 0 oder false
            {
            $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);
			/* logging in einem File und in einem String am Webfront : $logfile,$nachrichteninput_Id,$prefix="", $html=false, $count=false   */
			$this->log=new Logging($this->configuration["LogDirectory"]."Autosteuerung.csv",$NachrichtenInputID,IPS_GetName(0).";Autosteuerung;");	
            $nachrichtenInputID = $ipsOps->searchIDbyName("Nachricht",$NachrichtenAltID);    
            $this->logHtml = new Logging("No-Output",$nachrichtenInputID,"", true);            // true für html            		
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
			//include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");	
            IPSUtils_Include ("IPSHeat.inc.php","IPSLibrary::app::modules::Stromheizung");					
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
			//include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
            IPSUtils_Include ("IPSLight.inc.php","IPSLibrary::app::modules::IPSLight");            						
			$this->lightManager = new IPSLight_Manager();
	
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
			$this->switchCategoryId 	= IPS_GetObjectIDByIdent('Switches', $baseId);
			$this->groupCategoryId   	= IPS_GetObjectIDByIdent('Groups', $baseId);
			$this->prgCategoryId   		= IPS_GetObjectIDByIdent('Programs', $baseId);
			}
			
								
		if ( isset($this->installedModules["DENONsteuerung"] ) )
			{
			//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DENONsteuerung\DENONsteuerung.Library.inc.php");						
            IPSUtils_Include ("DENONsteuerung.Library.inc.php","IPSLibrary::app::modules::DENONsteuerung");
			$this->DENONsteuerung = new DENONsteuerung();	
			$this->dataCategoryIdDenon = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.DENONsteuerung');
			//$this->configCategoryIdDenon = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.DENONsteuerung');
			}									
		}


    /* of Autosteuerung
     * Konfigurationsmanagement , Abstraktion mit set und get im AutosteuerungHandler */

    private function set_Configuration($debug=false)
        {
        $autosteuerungHandler = new AutosteuerungHandler();         // nur zum Configuration einlesen anlegen
        $setup = $autosteuerungHandler->get_Configuration();
        if ($debug) { echo "AutosteuerungHandler->get_Configuration benutzt um Konfiguration zentral einzulesen\n"; print_r($setup); }
        return ($setup);
        }

    public function get_Configuration()
        {
        return ($this->configuration);
        }

	/* of Autosteuerung
     * welche AutosteuerungsFunktionen sind aktiviert:
     * unter data.modules.Autosteuerung.Ansteuerung alle bekannten Variablen auswerten und den  Status zurückmelden
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
			$children[IPS_GetName($ID)]["VALUE_F"]=GetValueIfFormatted($ID);
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
					/* es gibt keinen Schalter der unter dem Hauptschalter noch zusaetzlich verwendet wird, Hauptzschalter steht als VALUE und VALUE_F zur Verfügung */
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
	 * of Autosteuerung
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
		
	/* laut Wochenplan wird heute geheizt ? 
     *
     * entweder auf getFunctions vertrauen und Status auswerten oder
     * selbst erfassen
     *
     */	
		
	public function isitheatday($debug=false)
		{
		$status=false;
		$functions=self::getFunctions();
		if ( isset($functions["Stromheizung"]["STATUS"]) )              // es gibt einen Untergeordneten Schalter zum Ein oder Ausschalten
			{
            if ($debug) echo "Autosteuerung::isitheatday, getFunctions stellt Status bereits zur Verfügung.\n";
			if ($functions["Stromheizung"]["STATUS"] == 0) { $status=true; }
			}
		elseif ( isset($functions["Stromheizung"]["VALUE_F"]) )              // Stromheizung mode Ein/Aus/Auto
            {
            if ($functions["Stromheizung"]["VALUE_F"]=="Auto")
                {
                /* der Status wurde nicht zentral ermittelt, selbst erfassen */
                $found=@IPS_GetObjectIDByName("Zeile1",IPS_GetParent($this->CategoryId_Wochenplan));			
                if ($found!== false) 
                    {
                    //$property=IPS_GetObject($found);
                    //if ( $property["ObjectType"]==6 ) $status=(integer)GetValue(IPS_GetLink($found)["TargetID"]);
                    $status=(integer)GetValue($found);
                    if ($debug) echo "Autosteuerung::isitheatday,Status Heiztag : ".($status?"JA":"NEIN")."  ($status)\n";
                    }				
                //print_r($childrenIDs);
                }
            elseif ($functions["Stromheizung"]["VALUE_F"]=="Ein") $status=true;
            }
		return ($status);		
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
		
	/* Auswertung der Angaben in den Szenen. Schauen ob auf ein oder aus geschaltet werden soll 
     * this->timeStop wird in switchAWS verwendet
     * 
     */		
		
	public function timeright($scene, $debug=false)
		{
		$actualTimes = self::switchingTimes($scene);
		if ($debug) echo "        timeright, Szene ".$scene["NAME"]."  Chance: ".$scene["EVENT_CHANCE"]."% ".json_encode($actualTimes)."\n";
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
			if ($debug) echo "             >>Schaltzeiten:".$actualTimeStartHour.":".$actualTimeStartMinute." bis ".$actualTimeStopHour.":".$actualTimeStopMinute." analysieren\n";
			$timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);           // sind Zwischenwerte, nicht nach AUssen tragen
			$timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
			$this->now = time();

			if ($timeStart > $timeStop)
				{
				if ($debug) echo "        stop is considered to be on the next day.\n";
				if (($this->now > $timeStart) || ($this->now < $timeStop))
					{				
                    if ($debug) echo "        *** aktuell im Schaltinterval.\n";
					$minutesRange = ($timeStop-$timeStart)/60+24*60;
					$actionTriggerMinutes = 5;
					$rndVal = rand(1,100);
					//echo "Zufallszahl:".$rndVal."\n";
					if ( ($rndVal < $scene["EVENT_CHANCE"]) || ($scene["EVENT_CHANCE"]==100)) 
                        { 
                        $timeright=true; 
                        $this->timeStop = $timeStop; 
                        if ($debug) echo "SWITCH in ".nf($timeStop-time()+24*60*60,"s")."\n";           // Zeit länger da nächster Tag
                        }
					}
				}

			if (($this->now > $timeStart) && ($this->now < $timeStop))
				{
                if ($debug) echo "        *** aktuell im Schaltinterval.    ";
				$minutesRange = ($timeStop-$timeStart)/60;
				$actionTriggerMinutes = 5;
				$rndVal = rand(1,100);
				//echo "Zufallszahl:".$rndVal."\n";
				if ( ($rndVal < $scene["EVENT_CHANCE"]) || ($scene["EVENT_CHANCE"]==100)) 
                    { 
                    $timeright=true;
                    $this->timeStop = $timeStop; 
                    if ($debug) echo "SWITCH in ".nf($timeStop-time(),"s")."\n";
                    }
                elseif ($debug) echo "\n"; 
				}
			}	
        $this->timeStart=$timeStart;            // warum eigentlich ???? wird nicht benötigt 
		return ($timeright);	
		}	

    /****************************************************
     * of Autosteuerung
     * Konfiguration der Anwesenheitsliste als text ausgeben
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
     * getScenes of Autosteuerung
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

    public function getChancesById($Id)
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
	 * of Autosteuerung
	 * Vorwert (Float) erfassen und in der Category Stromheizung speichern, 
     * eingesetzt bei Heizung um zB zu erkennen Temperatur gestiegen, gesunken, etc.
	 * wird bei jeder Änderung zB von Ventilator2 aufgerufen und der Wert wird in OLDSTATUS gespeichert
     * 
     * Übergabe variableID  OID
     *          value      neuer Wert
     *          category   Category in der die Werte zum Vergleichen gespeichert werden sollen
	 *
	 * wenn die variableID gültig ist erfolgt in der entsprechenden Kategorie data.modules.Autosteuerung.Ansteuerung.Stromheizung (default) ein Eintrag mit
	 * dem selben Variablennamen machen (plus Parentname) und daraus den Vorwert ableiten
	 *
	 ******************************************************************/

	public function setNewValue($variableID,$value, $category=0)
		{
        if ($category === 0) $category=$this->CategoryId_Stromheizung;
		if ($category !== false)
			{
			if ( !(IPS_ObjectExists($variableID)) ) 
				{
				echo "Variable ID : ".$variableID." existiert nicht.\n";
				return($value);
				}
			else
				{	 
				//echo "Stromheizung Speicherort OID : ".$category." (".IPS_GetName(IPS_GetParent($category))."/".IPS_GetName($category).")  Variable OID : ".$variableID." (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")\n";
				$typ=IPS_GetVariable($variableID)["VariableType"];
                $profil=IPS_GetVariable($variableID)["VariableProfile"];
				// CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
                $mirrorVariableID=CreateVariableByName($category,IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID)), $typ, $profil);
				//echo "Spiegelvariable ist auf OID : ".$mirrorVariableID."   ".IPS_GetName($mirrorVariableID)."/".IPS_GetName(IPS_GetParent($mirrorVariableID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorVariableID)))."   alter Wert ist : ".GetValue($mirrorVariableID)."\n";
				$oldValue=GetValue($mirrorVariableID);
				SetValue($mirrorVariableID,$value);
				return($oldValue);
				}
			}
		else return ($value);	
		}


	public function getOldValue($variableID, $category=0)
		{
        if ($category === 0) $category=$this->CategoryId_Stromheizung;
		if ($category !== false)
			{
			if ( !(IPS_ObjectExists($variableID)) ) 
				{
				echo "Variable ID : ".$variableID." existiert nicht.\n";
				return(false);
				}
			else
				{	 
				echo "Stromheizung Speicherort OID : ".$category." (".IPS_GetName(IPS_GetParent($category))."/".IPS_GetName($category).")  Variable OID : ".$variableID." (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")\n";
				$typ=IPS_GetVariable($variableID)["VariableType"];
                $profil=IPS_GetVariable($variableID)["VariableProfile"];
                // CreateVariable ($Name, $Type ( 0 Boolean, 1 Integer 2 Float, 3 String) , $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
				// CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
                $mirrorVariableID=CreateVariableByName($category,IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID)), $typ, $profil);
				echo "Spiegelvariable ist auf OID : ".$mirrorVariableID."   ".IPS_GetName($mirrorVariableID)."/".IPS_GetName(IPS_GetParent($mirrorVariableID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorVariableID)))."   alter Wert ist : ".GetValue($mirrorVariableID)."\n";
				$oldValue=GetValue($mirrorVariableID);
				return($oldValue);
				}
			}
		else return ($value);	
		}

	/***************************************
	 * of Autosteuerung
	 * Spezialfunktion für Ansteuerung des Dimmers, bei Angabe von Change herausfinden ob wir ADD oder SUB machen sollen
     * der alte Wert wird abgefragt und herausgefunden wie lange die Änderung zurücklag
     * 
     * Unterschiedliche Implementierung bei den Tastern:
     *     Homematic mit INSTALL_TEST , Update relativ langsam alle 1-2 Sekunden
     *     HomematicIP mit zb PRESS_LONG, Update 
	 *
	 ******************************************************************/

	public function setNewValueDim($variableID,$value, $category=0)
		{
        if ($category === 0) $category=$this->CategoryId_Stromheizung;
		if ($category !== false)
			{
			if ( !(IPS_ObjectExists($variableID)) ) 
				{
				echo "Variable ID : ".$variableID." existiert nicht.\n";
				return($value);
				}
			else
				{	 
				//echo "Stromheizung Speicherort OID : ".$category." (".IPS_GetName(IPS_GetParent($category))."/".IPS_GetName($category).")  Variable OID : ".$variableID." (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")\n";
				$varProps=IPS_GetVariable($variableID);
                $typ=$varProps["VariableType"];
                $profil=$varProps["VariableProfile"];
                //$noChange=time()-$varProps["VariableChanged"];
                //$noUpdate=time()-$varProps["VariableUpdated"];
				// CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
                $mirrorVariableID=CreateVariableByName($category,IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID)), $typ, $profil);
                $directionAddVariableID=CreateVariableByName($category,IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID))."_direction", 0);  // Boolean true ad false sub
                $hitRateVariableID=CreateVariableByName($category,IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID))."_hitrate", 0);  // Boolean true ad false sub
                $result=array();
				$result["oldValue"]=GetValue($mirrorVariableID);
				$varDir=IPS_GetVariable($directionAddVariableID);
                $noChange=time()-$varDir["VariableChanged"];
                $noUpdate=time()-$varDir["VariableUpdated"];
                $result["noChange"]=$noChange;
                $result["noUpdate"]=$noUpdate;
                $direction=GetValue($directionAddVariableID);
                $hitrate=GetValue($hitRateVariableID)+1;
				echo "Spiegelvariable ist auf OID : ".$mirrorVariableID."   ".IPS_GetName($mirrorVariableID)."/".IPS_GetName(IPS_GetParent($mirrorVariableID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorVariableID)))."   alter Wert ist : ".GetValue($mirrorVariableID)." Direction steht auf ".(GetValue($directionAddVariableID)?"ADD":"TRUE")." Nochange $noChange Sekunden. Hitrate $hitrate.\n";
                if ($noUpdate>2) 
                    {
                    $hitrate=0;
                    $direction=(!$direction);
                    }
                Setvalue($directionAddVariableID,$direction);                   // Update wird gemessen da sich Richtung ja nicht ändert
                Setvalue($hitRateVariableID,$hitrate);
                $result["hitRate"]=$hitrate;
                if ($direction) $newValue=$result["oldValue"]+$value;
                else $newValue=$result["oldValue"]-$value;
                $result["direction"]=$direction;
                if ($newValue>100) $newValue=100;
                if ($newValue<0) $newValue=0;
				SetValue($mirrorVariableID,$newValue);
                $result["newValue"]=$newValue;
				return($result);
				}
			}
		else return ($value);	
		}

	/***************************************
	 * of Autosteuerung
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
				$typ=IPS_GetVariable($variableID)["VariableType"];
                $profil=IPS_GetVariable($variableID)["VariableProfile"];
				// CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
                $mirrorVariableID=CreateVariableByName($this->CategoryId_Stromheizung,IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID))."_Dif", $typ, $profil);
				echo "Spiegelvariable ist auf OID : ".$mirrorVariableID."   ".IPS_GetName($mirrorVariableID)."/".IPS_GetName(IPS_GetParent($mirrorVariableID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorVariableID))).
						"   alter Wert ist : ".GetValue($mirrorVariableID)."\n";
				$oldValue=GetValue($mirrorVariableID);
				if (abs($oldValue-$value)>=$dif) SetValue($mirrorVariableID,$value);
				return($oldValue);
				}
			}
		else return ($value);	
		}

	/***************************************
	 * of Autosteuerung
	 * Vorwert (Boolean) des Triggers erfassen und in der Category data::modules::Autosteuerung::Status speichern, 
     * eingesetzt bei Status und StatusParallel um zB zu erkennen wie sich der Status des Triggers geändert hat on->off, off->on, just bounce, or only update on->on etc.
	 * wird bei jeder Änderung aufgerufen
     *
     * vergleiche auch Funktion setNewValue
	 *
	 * in der entsprechenden Kategorie data.modules.Autosteuerung.Status einen Eintrag mit
	 * dem selben Variablennamen machen (plus Parentname) und daraus den Vorwert ableiten
     *
     * bislang wurde nur abhängig vom aktuellen  Statuswert geschaltet    on:true, off:none
	 *
	 ******************************************************************/

	public function setNewStatus($variableID,$value, $category=0)
		{
        if ($category === 0) $category=$this->CategoryId_Status;            
		if ($category !== false)
			{
			if ( !(IPS_ObjectExists($variableID)) ) 
				{
				echo "Variable ID : ".$variableID." existiert nicht.\n";
				return($value);
				}
			else
				{	 
				echo "   setNewStatus: Status Speicherort OID : ".$category." (";
                echo IPS_GetName(IPS_GetParent($category))."/".IPS_GetName($category).")   ";
                echo "Variable OID : ".$variableID." (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")\n";
				$typ=IPS_GetVariable($variableID)["VariableType"];
                $profil=IPS_GetVariable($variableID)["VariableProfile"];
				// CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
                $mirrorVariableID=CreateVariableByName($category,IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID)), $typ, $profil);
				echo "  setNewStatus: Spiegelvariable ist auf OID : ".$mirrorVariableID."   ".IPS_GetName($mirrorVariableID)."/".IPS_GetName(IPS_GetParent($mirrorVariableID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorVariableID)))."   alter Wert ist : ".GetValue($mirrorVariableID)."\n";
				$oldValue=GetValue($mirrorVariableID);
				if ($value != $oldValue) SetValue($mirrorVariableID,$value);
				return($oldValue);
				}
			}
		else return ($value);	
		}

	/***************************************
	 * of Autosteuerung
	 * Ermittlung zeitlicher Abstand zur letzen Änderung, wenn groesser $bounce ist wird true rückgemeldet
     *
     * Änderungen nur abspeichern wenn zeitlich lang genug vom letztem Speicherdatum entfernt sind, Wert ist eigentlich völlig egal, es wird das Speicherdatum der Variable genommen
     * Routine wird nur bei Änderungen von 0 auf 1 oder von 1 auf 0 aufgerufen  Befehl   ON:true oder OFF:true
	 *
	 * in der entsprechenden Kategorie data.modules.Autosteuerung.Status einen Eintrag mit
	 * dem selben Variablennamen machen (plus Parentname plus dif)  und daraus den Zeitabstand zum letztem Schreiben ableiten
     *
     * Parameter:
     *  variable    die ID 
     *
     * Zusatzparameter:
	 *
	 ******************************************************************/

	public function setNewStatusBounce($variableID,$value,$dif,$update=false,$token=false,$category=0,$debug=false)
		{
        $bounce=false;      /* default Rückmeldewert ist kein Bounce */
        if ($token===false) $token=IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID));
        if ($category === 0) $category=$this->CategoryId_Status; 
		if ($category !== false)
			{
			if ( !(IPS_ObjectExists($variableID)) ) 
				{
				echo "Variable ID : ".$variableID." existiert nicht.\n";
				return($bounce);
				}
			else
				{	 
                if ($update) 
                    {   
                    if ($debug) echo "Bounce Speicherort OID : ".$category." (".IPS_GetName(IPS_GetParent($category))."/".IPS_GetName($category).")  Variable OID : ".$variableID." (".IPS_GetName(IPS_GetParent($variableID))."/".IPS_GetName($variableID).")\n";
                    $typ=IPS_GetVariable($variableID)["VariableType"];
                    $profil=IPS_GetVariable($variableID)["VariableProfile"];
    				// CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
                    $mirrorVariableID=CreateVariableByName($category,$token."_Dif", $typ, $profil);
                    $bounceVariableID=CreateVariableByName($category,$token."_Bounce", 0);      // Boolean bounce true oder false
                    $timeOfUpdateID=CreateVariableByName($category,$token."_TimeStamp", 1);      // Time since first call
                    if ($debug) echo "Spiegelvariable ist auf OID : ".$mirrorVariableID."   ".IPS_GetName($mirrorVariableID)."/".IPS_GetName(IPS_GetParent($mirrorVariableID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorVariableID))).
                            "   alter Wert ist : ".GetValue($mirrorVariableID)."\n";
                    if ($dif>10)
                        {
                        $timeSinceUpdate=exectime(GetValue($timeOfUpdateID),"ms");
                        if ($timeSinceUpdate>=$dif) SetValue($timeOfUpdateID,startexec("ms"));
                        else $bounce=true;
                        if ($debug) echo "Time since Update $timeSinceUpdate ms.\n";
                        }
                    else
                        {
                        $timeOfChange=IPS_GetVariable($mirrorVariableID)["VariableUpdated"];
                        $timeSinceChange=time()-$timeOfChange;
                        if ($timeSinceChange>=$dif) SetValue($mirrorVariableID,$value);
                        else $bounce=true;
                        if ($debug) echo "Time of change ".date("d.m.Y H:i:s", $timeOfChange)." Time since change $timeSinceChange Sekunden.\n";
                        }                    
                    //IPSLogger_Inf(__file__, 'Autosteuerung, setNewStatusBounce : time since Change is '.$timeSinceChange.' Bounce Status is '.($bounce?"Yes":"No") );	
                    SetValue($bounceVariableID,$bounce);				
                    }
                else
                    {       
                    /* seltsame Funktion, wird benötigt wenn mehrmals bounce in einem Befehl abgefragt wird. daher beim zweiten Mal nur mehr den  Status abfragen, statt nocheinmal evaluieren
                     */
                    $bounceVariableID=@IPS_GetVariableIDByName(IPS_GetName($variableID)."_".IPS_GetName(IPS_GetParent($variableID))."_Bounce", $category);
                    if ($bounceVariableID) $bounce=GetValue($bounceVariableID);				
                    }
				}
			}
		
        return ($bounce);	
		}

	/**
	 * of Autosteuerung
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
	 * of Autosteuerung
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
         * danach kommt erst EvaluateCommand
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

	/* of Autosteuerung
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

	/* of Autosteuerung
	 * ersten teil des Arrays als Befehl erkenn, auf Grossbuchtaben wandeln, und das ganze array nochmals darunter speichern
	 * Erweitert das übergebene Array.
	 * bei size 1 nix machen, bei size 2, 4 oder groesser nur den ersten parameter mit trim/strtoupper bearbeiten
	 * bei size 3 den ersten und zweiten parameter mit trim/strtoupper bearbeiten
     *
	 */
		
	private function parseParameter($params,$result=array())
		{
		$size=count($params);
        $i=0;
		//echo "parseParameter: parse $size params :\n"; 
        //print_r($params);
        if ($size==3)
            {
			$result[]=trim(strtoupper($params[$i++]));
			$result[]=trim(strtoupper($params[$i++]));
            $result[]=$params[$i++];
            }
		elseif ( $size > 1 )
			{
			while ($i < $size )
				{ 
				$result[]=trim(strtoupper($params[$i++]));
				if ($i < $size) $result[]=$params[$i++];
				}
			}
        //print_r($result);
		return($result);
		}

	/* of Autosteuerung
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
				//$result=self::getColor($input);
				$result=$this->getColor($input);
				if ($result==false)
					{
					/* Color ist eine Hex Zahl */
					$value=hexdec($input);
					}
				else
					{
					//$value=($result["red"]*128+$result["green"])*128+$result["blue"];
					$value=($result["red"]*256+$result["green"])*256+$result["blue"];		// richtig multipliziert, shift 8 Bits
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

	/**********************************
     * findSimilar of Autosteuerung
     *
     *
     ***************************************/

	function findSimilar($befehl, $echos=false, $debug=false)
        {
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug, für EchoControl Loadspeaker Ausgabe verwendet
        $resultInt=0; $result=array();
        if ($echos===false) $echos=$modulhandling->getInstances('EchoRemote');
        if ($debug) echo "findSimilar, Befehl LOUDSPEAKER, check ob ".$befehl." im Amazon Echo Loudspeaker Array von ".json_encode($echos)." ist.\n";
        if (is_numeric($befehl)) 
            {
            $resultFloat=(float)$befehl;
            $resultInt=(integer)$resultFloat;   
            if ($debug) echo "Auswertung: es ist eine Zahl $resultFloat .\n";
            if ( ($resultInt) > 0) 
                {
                if ($debug) echo "Auswertung: es ist eine OID. Name: ".IPS_GetName($resultInt)."\n";
                }
            if (in_array($resultInt,$echos)) 
                {
                if ($debug) echo "      -> JA\n";
                $result["equal"][]=$resultInt;    
                }
            }
        else
            {
            if ($debug) 
                {
                echo "keine Zahl angegeben, es ist ein Name, korrektes Match möglich ?\n";
                echo "     es gibt folgende Echos:\n";
                }
            foreach ($echos as $echo) 
                {
                $echoName=IPS_GetName($echo);
                if ($debug) echo "        $echo ($echoName)\n";
                if ($befehl == $echoName)   
                    {
                    if ($debug) echo "                    --> tata, gefunden.\n";
                    $result["similar"][]=$echo;
                    }
                $pos = strstr($echoName,$befehl);
                if ($pos !== false) 
                    {
                    if ($debug) echo "      ähnlichen Eintrag gefunden, $befehl ist in $echoName auf Position $pos.\n";
                    $result["inbetween"][]=$echo;
                    }
                $pos = stristr($echoName,$befehl);
                if ($pos !== false) 
                    {
                    if ($debug) echo "      ähnlichen Eintrag gefunden, $befehl ist in $echoName auf Position $pos.\n";
                    $result["inBetWEEN"][]=$echo;
                    }
                }
            if ($debug) echo "\n";    
            }
        /* Auswertung */
        if ( (isset($result["equal"])) && (sizeof($result["equal"])==1) ) $result["result"]=$result["equal"][0];
        elseif ( (isset($result["similar"])) && (sizeof($result["similar"])==1) ) $result["result"]=$result["similar"][0];
        elseif ( (isset($result["inbetween"])) && (sizeof($result["inbetween"])==1) ) $result["result"]=$result["inbetween"][0];
        elseif ( (isset($result["inBetWEEN"])) && (sizeof($result["inBetWEEN"])==1) ) $result["result"]=$result["inBetWEEN"][0];
        else 
            {
            if ( ( (isset($result["inbetween"])) && (sizeof($result["inbetween"])>1) ) || ( (isset($result["inBetWEEN"])) && (sizeof($result["inBetWEEN"])==1) ) )
                {
                echo "gleiche Ergebnisse:";
                foreach ($result["inbetween"] as $echo) echo " \"".IPS_GetName($echo)."\" ";
                foreach ($result["inBetWEEN"] as $echo) echo " \"".IPS_GetName($echo)."\" ";
                }
            else
                {
                foreach ($echos as $echo) echo " \"".IPS_GetName($echo)."\" ";
                }
            $result["result"]=false;
            }

        return ($result);
        }

	/**********************************
     * getIdByName of Autosteuerung
     *
     * überprüft IPSHeat, IPSLight auf den Namen und gibt ID, TYP und MODULE zurück 
     *
     * zuerst wird IPSHeat (Stromheizung) geprüft
     * wenn IPSLight installiert ist, der Wert vorhanden ist und nicht in IPSHEat vorkommt dann werden diese Eintraege ermittelt
     *
     * für den Namen rausfinden ob Switch, Group oder Program (in dieser Reihenfolge)
     *
     * rückgemeldet wird ID, TYP und MODULE oder ein leeres array
     *
     ***************************************/

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
			echo "Fehler getIdByName, Name of ID $lightName not found.\n"; 
			}
		$result["NAME"]=$lightName;
		return($result);	

		}

    /* of Autosteuerung
     * Schnelle Funktion um auf Basis des Ergebnis Typs einen Schalter oder eine Gruppe zu schalten
     */

    function switchByTypeModule($ergebnisTyp, $state, $debug=false)
        {
        if ($debug) echo "switchByTypeModule Type ".json_encode($ergebnisTyp)."\n";

        switch ($ergebnisTyp["TYP"])
            {
            case "Switch":
                if ($ergebnisTyp["MODULE"]=="IPSLight")
                    {
                    IPSLight_SetSwitchByName($ergebnisTyp["NAME"],$state);
                    }
                elseif ($ergebnisTyp["MODULE"]=="IPSHeat")
                    {
                    if ($debug) echo 'Aufruf IPSHeat_SetSwitchByName("'.$ergebnisTyp["NAME"].'",'.($state?"true":"false").").\n";								
                    IPSHeat_SetSwitchByName($ergebnisTyp["NAME"],$state); 
                    }
                else echo "Dont know Modul type ".$ergebnisTyp["MODULE"]."\n";
                break;
            case "Group":
                if ($ergebnisTyp["MODULE"]=="IPSLight")
                    {
                    IPSLight_SetGroupByName($ergebnisTyp["NAME"],$state);
                    }
                elseif ($ergebnisTyp["MODULE"]=="IPSHeat")
                    {
                    if ($debug) echo 'Aufruf IPSHeat_SetGroupByName("'.$ergebnisTyp["NAME"].'",'.($state?"true":"false").").\n";								
                    IPSHeat_SetGroupByName($ergebnisTyp["NAME"],$state); 
                    }
                else echo "Dont know Modul type ".$ergebnisTyp["MODULE"]."\n";            
                break;
            case "Program":
                break;
            default:
                break;
            }
        }


	/*********************************************************************************************************
	 * of Autosteuerung
	 * Auch das Evaluieren kann gemeinsam erfolgen, es gibt nur kleine Unterschiede zwischen den Befehlen 
     * Vorher wurde ParseCommand aufgerufen, der versucht einen String mit Abkürzerne etc. in eine allgemein verständliche Form zu bringen
	 *
	 * beim Evaluieren wird auch der Wert bevor er geändert wird als VALUE, VALUE#LEVEL, VALUE#COLOR erfasst
	 *
	 * SOURCE, OID, NAME, 
     * MODULE, DEVICE, COMMAND
     * SPEAK, LOUDSPEAKER
	 * STATUS, ON#COLOR, ON#LEVEL, ON, OFF#COLOR, OFF#LEVEL, OFF,
	 * MODE, SETPPOINT, THRESHOLD, NOFROST 
	 * DELAY, DIM, DIM#LEVEL, DIM#TIME, 
	 * ENVELOPE, LEVEL, MONITOR, MUTE, IF, IFNOT
	 *
	 * IF: oder IFNOT:<parameter>     DARK, LIGHT, SLEEP, WAKEUP, AWAKE, HEATDAY, ON, OFF oder einen Variablenwert 
	 *			DARK,LIGHT sind vom Sonneauf/unteragng abhängig
	 *			SLEEP, WAKEUP, AWAKE sind vom GutenMorgenWecker abhängig
	 *			HEATDAY wird vom Stromheizung Kalendar festgelegt
	 *             Auswertung Befehl beeinflusst Parameter result.switch, der das Schalten und Sprechen aktiviert oder deaktiviert
	 *
	 * Befehl ON und OFF haben ähnliche Funktion, aber es ist keine IF Funktion sondern entscheidet wie die Variable geschaltet werden soll
	 *   Schaltbefehl Ausführung nur wenn bei "OnChange" die Triggervariable den  Status true (ON) oder false (OFF) hat, speak funktioniert weiterhin.
	 *   ON:true, nur ausführen wenn Trigger Variable den  Status true hat (nicht zu verwechseln mit der zu schaltenden Variable !!!) oder OnUpdate als Triggerfunktion
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
	 *		setzen von  STATUS (Wert vom Trigger) und SWITCH (true) auf Defaultwerte
	 *  	bei Stromheizung wird auch noch OLDSTATUS (alter Wert vom Trigger aus einer eigenen Spiegelvariable)
	 * 	mehrmals EvaluateCommand() (in einem Config-String sind mehrere Kommandos (separiert durch ;), die aus einzelnen Befehlen (separiert durch ,)zusammengesetzt sind)
	 *		ExecuteCommand() mit switch() liefert als Ergebnis die Parameter für Dim und Delay.
	 *		Abarbeitung von Delay Befehl
	 *
	 ************************************/

	public function EvaluateCommand($befehl,array &$result,$simulate=false,$debug=false)
		{
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug, für EchoControl Loadspeaker Ausgabe verwendet
		//$this->log->LogMessage("EvaluateCommand : ".json_encode($befehl));		
		if ($debug) echo "       EvaluateCommand: Befehl ".$befehl[0]." ".$befehl[1]." abarbeiten.\n";
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
                /* Plausibilitätscheck gleich hier durchführen, wenn keine EchoControl installiert ist bei tts_play als default bleiben, zusaetzlich
                 * Umwandlung auf OID machgen. muss nicht später erfolgen. Gehtr gleich hier. 
                 */
                //print_r($befehl); 
                $loudspeaker=$this->findSimilar($befehl[1]);                
                if ($loudspeaker["result"]!==false) 
                    {
                    $result[$Befehl0]=$loudspeaker["result"];
                    if ( (isset($befehl[2])) && (strtoupper($befehl[2])=="FORCE") ) $result["FORCE"]=true;
                    }
                else 
                    {
                    echo "      -> Loudspeaker ".$befehl[1]." nicht vorhanden es gibt ";
                    }
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
			case "STATUS":
                echo "    --> STATUS Befehl erkannt ....\n";
				$value_on=strtoupper($befehl[1]);
                switch ($value_on)
                    {
                    case "TRUE":
                        if ($result["STATUS"]) $result["ON"]="TRUE";
                        else $result["ON"]="FALSE"; 
                        break;
                    case "FALSE":	
                        if ($result["STATUS"]) $result["OFF"]="FALSE";              // ob ON oder oFF ist eigentlich egal
                        else $result["OFF"]="TRUE"; 
                        break;
                    default:
                        echo "new Status command. add some lines of code.\n";
                        break;
                    }
                print_r($result);
                break;	
			case "ON#COLOR":                    
			case "ON#LEVEL":
				$result["NAME_EXT"]=strtoupper(substr($befehl[0],strpos($befehl[0],"#"),10));                               // Gemeinschaftsbefehl, hier nur aus dem Befehl rausfinden ob #COLOR oder #LEVEL den Aufruf verursacht hat und NAME_EXT so setzen
				$name_ext="#".ucfirst(strtolower(strtoupper(substr($befehl[0],strpos($befehl[0],"#")+1,10))));              // Ein Color oder Level daraus machen und den Wert des Registers herausfinden
				$ergebnis=$this->getIdByName($result["NAME"].$name_ext);                                                    // und auswerten
				if (isset($ergebnis["ID"]))				
					{
					$switchId = $ergebnis["ID"];
					$result["VALUE".$result["NAME_EXT"]]=GetValue($switchId);					
					//echo "   Befehl ON#LEVEL, Wert OID von ".$result["NAME"].$name_ext." : ".$resultID." mit Wert bisher : ".$result["VALUE".$result["NAME_EXT"]]." \n";	
					}						

			case "ON":
                //echo "    --> ON Befehl erkannt ....\n";
				if ( ($result["STATUS"] !== false) || ($result["SOURCE"] == "ONUPDATE") )   
					{
					/* nimmt den Wert des auslösenden Ereignisses, ON Befehl wird nur abgearbeitet wenn Wert des Ereignis auf true steht oder als Auslöser update eingetragen wird (statt onchange) 
                     * ON:TRUE:MASK   der Befehl On hat noch zusätzliche parameter, der geläufigste ist TRUE. Kann aber auch FALSE, TOGGLE oder NONE sein. 
                     *                Für die Programmbearbeitung von IPLight gibt es auch noch START, NEXT, PREV, END
                     *                ein Spezialbefehl ist ON:TRUE:MASK:0x4356 ---> Funktion muss noch weiter untersucht werden
                     *                BOUNCE:2, mask within time interval, 12:01:34 erste Änderung von 0 auf 1 , dazwischen 1 auf 0, 12:01:35 zweite Änderung von 0 auf 1
                     */
					$value_on=strtoupper($befehl[1]);
                    //echo "    --> ON:$value_on Befehl erkannt ....\n";
					$i=2;
					while ($i<count($befehl))
						{
						if (strtoupper($befehl[$i])=="MASK")
							{
                            $i++;
							$mask_on=hexdec($befehl[$i]);
							//$notmask_on=~($mask_on)&0xFFFFFF;						
							$result["ON_MASK"]=$mask_on;
							}
                        elseif ( (strtoupper($befehl[$i])=="BOUNCE") || (strtoupper($befehl[$i])=="BOUNCES") )
                            {
                            if (strtoupper($befehl[$i])=="BOUNCES") 
                                {
                                $update=false;
                                $interval=0;
                                }
                            elseif (count($befehl)>2)           // zweiten Parameter einlesen 
                                {
                                $update=true;
                                $interval=$befehl[2];
                                }
                            else        // Default Paramter ist 4
                                {
                                $update=true;
                                $interval=4;
                                }                                
                            $bounce=$this->setNewStatusBounce($result["SOURCEID"],$result["STATUS"],$interval,$update);        // mit Update Bounce Status
                            echo "Befehl Bounce mit Parameter $interval gefunden. Ignore status change : ".($bounce?"Yes":"No")."\n";
                            if ($bounce) 
                                {
                                $result["SWITCH"]=false;
              					IPSLogger_Inf(__file__, 'Autosteuerung, setNewStatusBounce : Bounce erkannt, Befehl ON:'.$value_on.':BOUNCE:'.$interval);					
                                }
                            //print_r($result);    
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
                            if ((isset($result["VALUE"]))==false) IPSLogger_Not(__file__, 'Autosteuerung, ON:TOGGLE erkannt ohne VALUE.'.json_encode($result));					
							if ($result["VALUE"] == false)
								{
								$result["ON"]="TRUE";
								}
							else
								{
								$result["ON"]="FALSE";
								}
							break;
						case "NONE":
							/* gleich wie wenn der Befehl gar nicht dort steht */
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
				    echo "   ON Befehl ".$result["SWITCH"]."   ".$result["ON"]."\n";
					}
                else echo "     ON Befehl nicht ausgeführt. Status war ".$result["STATUS"]." Source war  ".$result["SOURCE"]."\n";
				break;
			case "OFF#COLOR":
			case "OFF#LEVEL":
				$result["NAME_EXT"]=strtoupper(substr($befehl[0],strpos("#",$befehl[0]),10));                           // Gemeinschaftsbefehl, hier nur aus dem Befehl rausfinden ob #COLOR oder #LEVEL den Aufruf verursacht hat und NAME_EXT so setzen
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
						case "NONE":
							/* gleich wie wenn der Befehl gar nicht dort steht */
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
				$result["DELAY#CHECK"]=false;
                break;            
			case "DELAY#CHECK":
				$result["DELAY"]=(integer)$befehl[1];
				$result["DELAY#CHECK"]=true;
				break;
			case "DIM":
			case "DIM#LEVEL":			
				$result["DIM"]=(integer)$befehl[1];
				$result["DIM#LEVEL"]=$result["DIM"];
				break;			
            case "DIM#CHG":         // ADD or SUB Dimlevel, toogle between add or subtract the value
                /* eigentlich gleiche Routine, egal ob ADD, CHG or SUB , interessant wäre der Wert Auto bei dem Tageszeitabhängig gesetzt wird */
                echo "DIM#CHG erkannt. Weiter mit Bearbeitung:\n";
				$result["DIM#CHG"]=(integer)$befehl[1];
				$result["NAME_EXT"]="#LEVEL";                                   // NAME_EXT manuell setzen, muss nicht Teil von Befehl name:  sein
				$name_ext="#Level";
				$ergebnis=$this->getIdByName($result["NAME"].$name_ext);        // wenn vorhanden gibt Routine ID, TYP und MODULE zurück
				if (isset($ergebnis["ID"]))				
					{
					$result["ON"]="TRUE";
                    $setNewValueDim=$this->setNewValueDim($ergebnis["ID"],$result["DIM#CHG"]);      //array oldValue,noChange,noUpdate und direction 
					$result["VALUE_ON"]=$setNewValueDim["newValue"];	
                    echo "Autosteuerung Befehl DIM#CHG:".$result["DIM#CHG"].". Alter Wert: ".GetValue($ergebnis["ID"])." Neuer Wert ".$result["VALUE_ON"]."  ".json_encode($setNewValueDim)."\n"; 
					//IPSLogger_Inf(__file__, 'Autosteuerung Befehl DIM#CHG:'.$result["DIM#CHG"].'. Alter Wert: '.GetValue($ergebnis["ID"]).' Neuer Wert '.$result["VALUE_ON"]."  ".json_encode($setNewValueDim)."   ");					
					}					
                break;    
			case "DIM#ADD":			
				$result["DIM#ADD"]=(integer)$befehl[1];             // DIM#ADD wird nicht weiter verwendet,alle Berechnungen gleich hier duchführen
				$result["NAME_EXT"]="#LEVEL";                                           // NAME_EXT manuell setzen, muss nicht Teil von Befehl name:  sein
				$name_ext="#Level";
				$ergebnis=$this->getIdByName($result["NAME"].$name_ext);
				if (isset($ergebnis["ID"]))				
					{
					$result["ON"]="TRUE";
					$result["VALUE_ON"]=GetValue($ergebnis["ID"])+$result["DIM#ADD"];	
					if ( $result["VALUE_ON"] > 100 ) { $result["VALUE_ON"]=100; }
					//IPSLogger_Inf(__file__, 'Autosteuerung Befehl DIM#ADD:'.$result["DIM#ADD"].'. Alter Wert: '.GetValue($ergebnis["ID"]).' Neuer Wert '.$result["VALUE_ON"]);					
					}					
				break;					
			case "DIM#SUB":			
				$result["DIM#SUB"]=(integer)$befehl[1];
				$result["NAME_EXT"]="#LEVEL";                                       // NAME_EXT manuell setzen, muss nicht Teil von Befehl name:  sein
				$name_ext="#Level";
				$ergebnis=$this->getIdByName($result["NAME"].$name_ext);
				if (isset($ergebnis["ID"]))				
					{
					$result["ON"]="TRUE";
					$result["VALUE_ON"]=GetValue($ergebnis["ID"])-$result["DIM#SUB"];	
					if ( $result["VALUE_ON"] < 0 ) { $result["VALUE_ON"]=0; }
					//IPSLogger_Inf(__file__, 'Autosteuerung Befehl DIM#SUB:'.$result["DIM#SUB"].'. Alter Wert: '.$this->lightManager->GetValue($ergebnis["ID"]).' Neuer Wert '.$result["VALUE_ON"]);					
					}					
				break;					
			case "DIM#TIME":			
				$result["DIM#TIME"]=(integer)$befehl[1];
				break;				
			case "ENVELOPE":
				$result["ENVEL"]=(integer)$befehl[1];
				break;
			case "LEVEL":
				$this->evalCom_LEVEL($befehl,$result);      // erzeugt result[LEVEL], Befehl vergleichbar mit ON#LEVEL:95, aber ohne ON, OFF Auswertung
				break;
			case "MONITOR":
				$this->evalCom_MONITOR($befehl,$result);    // erzeugt result[MONITOR]
				break;
			case "MUTE":
				$this->evalCom_MUTE($befehl,$result);    // erzeugt result[MUTE]
				break;
			case "IFOR":
            case "ORIF":	
			case "IFAND":	/* überschreibt den Wert vom vorigen if wenn false, gleich wie mehrere if hintereinander */
            case "ANDIF":
			case "IF":     /* parges hat nur die Parameter übermittelt, hier die Auswertung machen. Es gibt zumindest light, dark und einen IPS Light Variablenname (wird zum Beispiel für die Heizungs Follow me Funktion verwendet) */
                $state=true;            // Rückgabewert, Input für evalcondition, wenn nicht zutrifft wird state zu false
                $if=trim(strtoupper($befehl[0]));
                if ($this->evalCondition($befehl,$result,$state,$debug)) ;                // gleich bekannte Befehle gemeinsam abarbeiten  dritter Parameter true normal, false invertiert, vierter Debug
                else
                    {	                                                    // wenn Befehle in evalCondition noch nicht bekannt hier weitermachen
                    $result["COND"]=$Befehl1;                             // Befehl1 wäre mit trim und strtoupper
                    switch ($result["COND"])
                        {
                        case "STATUS": 		/* andere Befehldarstellung IF:STATUS:EQ:parameter, funktioniert nicht mit NOTIF/IFNOT */
                            $comp=strtoupper($befehl[2]);
                            $val=(integer)$befehl[3];
                            switch ($comp)
                                {
                                case "EQ":
                                    if ($result["STATUS"] != $val)
                                        {
                                        $state=false;						
                                        IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:status:eq ungleich '.$val.'. Nicht Schalten, Triggervariable ist false ');
                                        }
                                    break;							
                                case "LT":
                                    if ( ($result["STATUS"] == $val) or ($result["STATUS"] > $val) ) 
                                        {
                                        $state=false;						
                                        IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:status:lt ungleich '.$val.'. Nicht Schalten, Triggervariable ist false ');
                                        }
                                    break;								
                                case "GT":
                                    if ( ($result["STATUS"] == $val) or ($result["STATUS"] < $val) ) 
                                        {
                                        $state=false;						
                                        IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:status:gt ungleich '.$val.'. Nicht Schalten, Triggervariable ist false ');
                                        }
                                    break;								
                                default:
                                    break;
                                }
                            break;
                        case "TURNON":
                        case "TURNOFF":
                        case "SETPERCENTAGE":
                        case "SETTARGETTEMPERATURE":
                            $gefunden=false;
                            if ($result["SOURCEID"]==$result["COND"]) $gefunden=true;
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
                                $state=false;
                                IPSLogger_Dbg(__file__, 'Autosteuerung Befehl if:'.$result["COND"].' ');
                                }
                            break;
                        default:
                            if ($this->evalConditionExtended($befehl,$result,$state,$debug)) echo "erfolgreich\n";
                            break;
                        }       // ende switch cond
                    }           // ende else
                /* Berechnung von IF Ergebnis gesamt, Berücksichtigung vorheriges Ergebnis in $result["SWITCH"] */
                switch ($if)
                    {
                    case "IF":
                        $result["SWITCH"] = $state;     // keine Verknüpfung mit vorherigem Ergebnis
                        break;
                    case "IFOR":
                    case "ORIF":
                        $result["SWITCH"] = $result["SWITCH"] || $state;
                        break;
                    case "IFAND":
                    case "ANDIF":
                        $result["SWITCH"] = $result["SWITCH"] && $state; 
                        break;
                    default:
                        echo "Kenne Befehl $if nicht.\n";
                        break;
                    }                      		
                echo "   Ergebnis Evaluierung $Befehl0 $Befehl1:".($result["SWITCH"]?"true":"false")."\n";
				break;
			case "IFDIF":           /* bei Temperaturwerten ist das der Abstand, der zum alten Wert erreicht werden muss bevor der befehl ausgeführt wird */
				$dif=(float)($befehl[1]);
				$oldvalue=$this->setNewValueIfDif($result["SOURCEID"],$result["STATUS"],$dif);
				echo "IFDIF Befehl erkannt : aktuelle Temperatur : ".($result["STATUS"])."    ".$dif." alter ifdif Wert : ".$oldvalue."\n";
				if (abs($result["STATUS"]-$oldvalue)<$dif) $result["SWITCH"]=false;
				//print_r($result);
				break;	
			case "IFANDNOT":
			case "NOTIFAND":
            case "ANDNOTIF":
			case "IFORNOT":	
			case "NOTIFOR":            
			case "ORNOTIF":            
            case "NOTIF":
			case "IFNOT":     /* parges hat nur die Parameter übermittelt, hier die Auswertung machen. Es gibt zumindest light, dark und einen IPS Light Variablenname (wird zum Beispiel für die Heizungs Follow me Funktion verwendet) */
                $state=false;            // Rückgabewert
                $if=trim(strtoupper($befehl[0]));
                if ($this->evalCondition($befehl,$result,$state,$debug)) echo "erfolgreich\n";	            // Zusammenfassung der if Bearbeitung, verändert result, dritter Parameter true normal, false invertiert, vierter Debug ! 
                else
                    {
                    $result["COND"]=$Befehl1;                             // Befehl1 wäre mit trim und strtoupper
                    switch ($result["COND"])
                        {


                        default:
                            if ($this->evalConditionExtended($befehl,$result,$state,$debug)) echo "erfolgreich\n";
                            break;          // von default
                        }           // ende switch
                    }	        // ende else von eval condition
                /* Berechnung von IFNOT Ergebnis gesamt, Berücksichtigung vorheriges Ergebnis in $result["SWITCH"] , AND oder OR bezieht sich nicht auf das NOT, das NOT bezieht sich auf das IF */
                switch ($if)
                    {
                    case "NOTIF":
                    case "IFNOT":
                        $result["SWITCH"] = $state;     // keine Verknüpfung mit vorherigem Ergebnis
                        break;
                    case "IFORNOT":	
                    case "NOTIFOR":            
                    case "ORNOTIF":            
                        $result["SWITCH"] = $result["SWITCH"] || $state;
                        break;
                    case "IFANDNOT":
                    case "NOTIFAND":
                    case "ANDNOTIF":
                        $result["SWITCH"] = $result["SWITCH"] && $state; 
                        break;
                    default:
                        echo "Kenne Befehl $if nicht.\n";
                        break;
                    }                      		
                echo "   Ergebnis Evaluierung ".($result["SWITCH"]?"true":"false")."\n";
                break;
            default:
                echo "Function EvaluateCommand, Befehl unbekannt: \"".strtoupper($befehl[0])."\" ".$befehl[1]."   \n";
                break;				
            }  /* ende switch */

		return ($result);
		}	

    /***********************************
     * of Autosteuerung
     * IF Befehl wird mehrfach evaluiert, einmal bei IF und einmal bei IFNOT, in einer Routine soweit möglich zusammenfassen zu versuchen
     * wenn true rückgemeldet wird, ist der Befehl bearbeitet worden, sonst muss er noch weiter bearbeitet werden. 
     * es werden sowohl befehl,result und state als Zeiger übergeben und abgeändert. 
     *
     * state wird als Defaultwert übergeben, bei IF als true und bei IFNOT als false
     *
     * Befehl wird als einfaches index array übergeben. Die : sind bereits in ein array umgewandelt
     * Befehl[0]  ist IF, IFNOT, IFOR etc.
     * Befehl[1]  ON,OFF überprüft "STATUS" für die Entscheidung: 
     *                       IF:   state=true,  STATUS=false IF:ON =>  false  STATUS=true IF:ON  => true
     *                                          STATUS=false IF:OFF => true   STATUS=true IF:OFF => false
     *                       IFNOT:state=false, STATUS=false IF:ON =>  true   STATUS=true IF:ON  => false
     *                                          STATUS=false IF:OFF => false  STATUS=true IF:OFF => true
     *        LIGHT,DARK,SLEEP,AWAKE,WAKEUP,MOVE,HOME,ALARM,HEATDAY werden überprüft, wenn nicht zutrifft (false) wird state invertiert 
     *        oder ein Ausdruck oder sonst irgend etwas neues, !!!! wird noch nicht hier behandelt
     *        zusaetzlich gibt es :
     *            BOUNCE, BOUNCES   IF:BOUNCES:4 ruft setnewstatusBounce, wenn ein Bounce wird state auf false gesetzt
     * Befehl [2..n] werden in remain gesammelt
     *
     * result["COND"] ist Befehl[1]
     *
     */


	private function evalCondition(&$befehl,&$result,&$state,$debug=false)
		{
        $found=true;
        $if=trim(strtoupper($befehl[0]));
		$cond=trim(strtoupper($befehl[1]));
        $remain="";
        if (count($befehl)>2) foreach ($befehl as $index => $entry) if ($index>2) $remain .= $entry;
        if ($debug)
            {
		    echo "evalCondition: allgemeine Funktion zur Evaluierung von Varianten des Befehls $if:$cond ";
            if ($remain != "") echo " und es gibt weitere Zusatzbefehle: \"$remain\".";
            //IPSLogger_Inf(__file__, 'evalCondition: allgemeine Funktion zur Evaluierung von Varianten des Befehls "'.$if.':'.$cond.'".');
            }
        $result["COND"]=$cond;
        switch ($cond)
            {            
            case "BOUNCE":
            case "BOUNCES":
                if ($cond=="BOUNCES") 
                    {
                    $update=false;
                    $interval=0;
                    }
                elseif (count($befehl)>2)           // zweiten Parameter einlesen 
                    {
                    $update=true;
                    $interval=$befehl[2];
                    }
                else        // Default Paramter ist 4
                    {
                    $update=true;
                    $interval=4;
                    }
                $bounce=$this->setNewStatusBounce($result["SOURCEID"],$result["STATUS"],$interval, $update);
                if ($debug)
                    {
                    echo "\nBefehl Bounce mit Parameter $interval gefunden. Ignore status change : ".($bounce?"Yes":"No")."\n";
                    IPSLogger_Inf(__file__, 'Autosteuerung, evalCondition, setNewStatusBounce : Befehl IF:BOUNCE:'.$interval.' gefunden. Ignore status change : '.($bounce?"Yes":"No"));
                    }
                if ($bounce) 
                    {
                    $state=false;
                    //IPSLogger_Inf(__file__, 'Autosteuerung, evalCondition, setNewStatusBounce : Bounce erkannt, Befehl IF:BOUNCE:'.$interval.' with decision no switch.');					
                    }            
            case "ON":
                if ($state)     // normal
                    {
                    /* nur Schalten wenn  Statusvariable true ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
                    if ($result["STATUS"] == false)
                        {
                        $state=false;						
                        if ($debug) IPSLogger_Inf(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist false ');
                        }
                    }
                else            // NOT
                    {
                    /* nur Schalten wenn  Statusvariable false ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
                    if ($result["STATUS"] !== false)
                        {
                        $state=false;						
                        if ($debug) IPSLogger_Inf(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist false ');
                        }                    								
                    }
                break;
            case "OFF":
                if ($state)     // normal
                    {
                    /* nur Schalten wenn  Statusvariable false ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
                    if ($result["STATUS"] !== false)
                        {
                        $state=false;						
                        if ($debug) IPSLogger_Inf(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist true ');
                        }
                    }
                else            // NOT
                    {
                    /* nur Schalten wenn  Statusvariable true ist, OnUpdate wird ignoriert, da ist die Statusvariable immer gleich */
                    if ($result["STATUS"] == false)
                        {
                        $state=false;						
                        if ($debug) IPSLogger_Inf(__file__, 'Autosteuerung Befehl if: Nicht Schalten, Triggervariable ist false ');
                        }
                    }
                break;					
            case "LIGHT":		
                /* wenn es hell ist soll geschalten werden, der STATUS bleibt unverändert, Teil der Info bei ON oder OFF */		
                if (($this->isitlight())==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert						
                break;	
            case "DARK":							
                if (($this->isitdark())==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert						
                break;
            case "SLEEP":
                if (($this->isitsleep()) ==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert
                break;
            case "AWAKE":
                if (($this->isitawake()) ==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert
                break;
            case "WAKEUP":
                if (( $this->isitwakeup())==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert
                break;
            case "MOVE":
                if (( $this->isitmove())==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert
                break;
            case "HOME":
                if (( $this->isithome())==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert
                break;
            case "ALARM":
                if (( $this->isitalarm())==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert
                break;	
            case "HEATDAY":
                if (( $this->isitheatday())==false) $state=!$state;                // IF State Ergebnis bleibt sonst wird es geändert
                break;
            default:
                if ($debug) echo " --> keinen Parameter gefunden, normale Variante weitermachen.\n";
                $found=false;
                break;	
            } 
        if ($debug) echo " --> Ergebnis : ".($state?"true":"false")."\n";           
        return ($found);     // andere evaluierung des IF Befehls findet auch statt
		}

    /* of Autosteuerung
     * Zusatz Evaluierungen von Conditions, Rückgabe über die ersten drei Variablen.
     * state auch updaten, wird für die IFAND etc Befehle verwendet
     */

	private function evalConditionExtended(&$befehl,&$result,&$state,$debug)
        {
        $found=true;
        $if=trim(strtoupper($befehl[0]));
		$cond=trim(strtoupper($befehl[1]));
        $remain="";         // wäre für Zusatzbefehle
        if ($debug)
            {
		    echo "evalConditionExtended: allgemeine Funktion zur Evaluierung von besonderen Varianten des Befehls $if:$cond ";
            if ($remain != "") echo " und es gibt weitere Zusatzbefehle: \"$remain\".\n";
            else echo "\n";
            IPSLogger_Inf(__file__, 'evalConditionExtended: allgemeine Funktion zur Evaluierung von besonderen Varianten des Befehls "'.$if.':'.$cond.'".');
            }
        /* weder light noch dark, wird ein IPSLight Variablenname sein. Wert ermitteln */
        $compare=explode("=",$befehl[1]);
        $sizeBefehl=sizeof($compare);
        $checkId=false;                                 // false wenn nix erkannt wurde
        $ergebnisTyp=$this->getIdByName($compare[0]);
        if ($debug) 
            {
            if ($sizeBefehl>1) echo "Vergleichsoperator = gefunden. vergleicht Wert von ".$compare[0]." mit ".$compare[1]." \n";    
            //print_r($compare);
            //print_r($ergebnisTyp);
            }
        if ($ergebnisTyp["MODULE"]=="IPSLight")
            {
            if ($debug) echo "Suche Name in IPSLight: \n";    
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
                            if ($debug) echo "Vielleicht ein Program, dann ist ein Wertvergleich dabei, eingestellt auf ".$compare[1].".Vergleich mit ".GetValue($checkId)."  ".GetValueFormatted($checkId)."\n";
                            $statusCheck = ($compare[1]==GetValueFormatted($checkId));
                            IPSLogger_Inf(__file__, 'Autosteuerung IPSLight Befehl IF: Program '.$compare[0]."   ".$compare[1].". Vergleich mit ".GetValueFormatted($checkId)."   ergibt ".($statusCheck?"OK":"NOK"));
                            }
                        }
                    }
                else $statusCheck=$this->lightManager->GetValue($checkId);		// Wert von der Group	
                }
            else $statusCheck=$this->lightManager->GetValue($checkId);		// Wert vom Switch	
            }
        elseif ($ergebnisTyp["MODULE"]=="IPSHeat")
            {
            $checkId=$ergebnisTyp["ID"];
            switch ($ergebnisTyp["TYP"])
                {
                case "Switch":
                case "Group":
                    $statusCheck=$this->heatManager->GetValue($checkId);
                    break;
                case "Program":
                    if ($debug) echo "Es ist ein IPSHeat Program mit Wertvergleich, abgefragt auf ".$compare[1].". Vergleich mit ".GetValueFormatted($checkId)."  (".GetValue($checkId).")  \n";
                    if ($sizeBefehl>1)
                        {
                        $statusCheck = ($compare[1]==GetValueFormatted($checkId));
                        if ($debug) echo "Ergebnis ".($statusCheck?"Gleich":"Ungleich")."\n";
                        IPSLogger_Inf(__file__, 'Autosteuerung IPSHeat Befehl IF: Program '.$compare[0]."   ".$compare[1].".vergleich mit ".GetValueFormatted($checkId)."   ergibt ".($statusCheck?"OK":"NOK"));
                        }
                    break;
                default:
                    $checkId=false;
                    break;
                }
            }
        if ($checkId !== false)
            {
            if ( (strtoupper($befehl[0]) == "IFAND") || (strtoupper($befehl[0]) == "ANDIF") )
                {
                $result["SWITCH"]=$result["SWITCH"] && $statusCheck;
                $state=$state && $statusCheck;
                }
            else
                {	
                $result["SWITCH"]=$statusCheck;
                $state=$statusCheck;
                }
            if ($debug) 
                {
                echo "Auswertung IF:".$befehl[1]." Wert ist ".($statusCheck?"gleich":"ungleich")." VariableID ist ".$checkId." (".IPS_GetName(IPS_GetParent($checkId))."/".IPS_GetName($checkId).")\n";
                echo json_encode($result)."\n";	
                }
            }
        else  
            {
            echo "*****Fehler, Auswertung IF:".$befehl[1]." nicht bekannt, wird ignoriert.  $checkId  \n";	
            }            
        }       // ende function

    /******************
     *
     * Versuch den Parser übersichtlicher zu gstalten, es wird immer Befehl und result bearbeitet
     *
     **************************************/

	private function evalCom_IFDIF(&$befehl,&$result)
		{
		
		
		}

    /* Befehl MUTE
     * MUTE:status, den Wert der übergeben wird als wert für $result[MUTE] übernehmen
     * sonst den Wert schreiben, also MUTE:on
     */

   	private function evalCom_MUTE(&$befehl,&$result)
		{
        $result["MODULE"]="Internal";
        $mute=strtoupper($befehl[1]);
        if ($mute=="STATUS")
            {
            if ($result["STATUS"]==true)
                {
                $mute="ON";
                $result["MUTE"]=$mute;
                }
            else
                {
                $mute="OFF";
                $result["MUTE"]=$mute;
                }
            }
        else
            {
            $result["MUTE"]=$mute;
            }
        }

    /* Befehl MONITOR
     * MONITOR:status, den Wert der übergeben wird als wert für $result[MONITOR] übernehmen
     * sonst den Wert schreiben, also MONITOR:on
     */

   	private function evalCom_MONITOR(&$befehl,&$result)
		{
        $result["MODULE"]="Internal";
        $monitor=strtoupper($befehl[1]);
        if ($monitor=="STATUS")
            {
            if ($result["STATUS"]==true)
                {
                $result["MONITOR"]="ON";
                }
            else
                {
                $result["MONITOR"]="OFF";
                }
            }
        else
            {
            $result["MONITOR"]=$monitor;
            }
        }

    /* Befehl wurde bislang noch nicht für Autosteuerung verarbeitet, es geht um die Verarbeitung von Werten statt Statusinformationen
     * Bei Alexa kommen die Werte rein und müssen dann weitergegeben werden, es wird result[LEVEL] gesetzt
     * LEVEL:5 oder LEVEL:VALUE, 
     * es gibt noch keine Konvertierung
     */

	private function evalCom_LEVEL(&$befehl,&$result)
		{
		$Befehl1=trim(strtoupper($befehl[1]));
        echo "evalCom_LEVEL $Befehl1 \n";
		switch ($Befehl1)
			{
			case "VALUE":
				$result["LEVEL"]=(integer)$result["STATUS"];
				break;
            case "PPM":
                echo "    PPM, Es wird der Wert \"".$result["STATUS"]."\" von ".$result["SOURCEID"]." übergeben.\n";
                $value=$result["STATUS"];
                $result["NAME_EXT"]="#COLOR";                                   // NAME_EXT manuell setzen, muss nicht Teil von Befehl name:  sein
                $result["ON"]="TRUE";                                           // sonst wird nichts gemacht
                if ($value<=1000) $color=$this->getColor("green");               // ppm Definition von Netatmo.CO2
                if ($value>1000) $color=$this->getColor("yellow");
                if ($value>1250) $color=$this->getColor("orange");
                if ($value>1300) $color=$this->getColor("red");
				$result["VALUE_ON"]=($color["red"]*256+$color["green"])*256+$color["blue"];
                $result["LEVEL"]=(integer)$result["STATUS"];
                break;
            case "BRIGHTNESS":
                echo "    BRIGHTNESS, Es wird der Wert \"".$result["STATUS"]."\" von ".$result["SOURCEID"]." übergeben.\n";  
                $value=$result["STATUS"];
                $result["NAME_EXT"]="#LEVEL";                                   // NAME_EXT manuell setzen, muss nicht Teil von Befehl name:  sein
                $result["ON"]="TRUE";                                           // sonst wird nichts gemacht
                if ($value<=5) $level=2;
                if ($value>5) $level=5;
                if ($value>10) $level=15;
                if ($value>20) $level=40;
                if ($value>25) $level=100;
				$result["VALUE_ON"]=$level;
                $result["LEVEL"]=(integer)$result["STATUS"];
                break;         
            case "ILLUMINATION":
                echo "    ILLUMINATION, Es wird der Wert \"".$result["STATUS"]."\" von ".$result["SOURCEID"]." übergeben.\n";  
                $value=$result["STATUS"];
                $result["NAME_EXT"]="#LEVEL";                                   // NAME_EXT manuell setzen, muss nicht Teil von Befehl name:  sein
                $result["ON"]="TRUE";                                           // sonst wird nichts gemacht
                if ($value<=4) $level=1;                    // beide Werte Messwert und Stellwert sind quadratisch, Anpassung überlegt
                if ($value>4) $level=2;
                if ($value>16) $level=4;
                if ($value>64) $level=7;
                if ($value>256) $level=13;
                if ($value>1024) $level=16;
                if ($value>2048) $level=32;
                if ($value>4096) $level=64;
                if ($value>8032) $level=100;
				$result["VALUE_ON"]=$level;
                $result["LEVEL"]=(integer)$result["STATUS"];
                break;         
			default:
				$result["LEVEL"]=(integer)$befehl[1];
				break;
			}
		}

    /******************
     * of Autosteuerung
     * mit dem Befehl Status oder Anwesenheit kann mit + noch ein Zusatzparameter mitgegeben werden. Diese als Control auswerten.
     *
     **************************************/

    function evalWertOpt(&$control, $wertOptInput, $variableID, $status, $debug=false)
        {
        $token=false;            
        echo "evalWertOpt wurde aufgerufen mit Eingabewert ".json_encode($wertOptInput).".\n";
        $control["note"]=false;
        $control["log"]=false;
        $control["bounce"]=false; 
        if ((is_array($wertOptInput))===false) 
            {
            if ($wertOptInput != "") $wertOptGo[]=$wertOptInput;
            else $wertOptGo=array();
            }
        else $wertOptGo=$wertOptInput;
        foreach ($wertOptGo as $wertOptEntry)
            {
            $wertOpt=trim(strtoupper($wertOptEntry));
            $wertOptArray=explode(":",$wertOpt);
            switch ($wertOptArray[0])
                {
                case "NOTE":
                    $control["note"]=true;
                    break;
                case "LOG":
                    $control["log"]=true;
                    echo "evalWertOpt Log true\n";
                    break;
                case "NOLOG":
                    $control["log"]=false;
                    break;
                case "BOUNCE":
                case "BOUNCES":
                    if ($wertOptArray[0]=="BOUNCES")                 {
                        $update=false;
                        $interval=0;
                        }
                    elseif (count($wertOptArray)>1)           // zweiten Parameter einlesen 
                        {
                        $update=true;
                        $interval=$wertOptArray[1];
                        //if ($debug) echo "Status+".$wertOptArray[0].":$interval erkannt.\n";
                        if (isset($wertOptArray[2])) $token=$wertOptArray[2];
                        }
                    else        // Default Paramter ist 4
                        {
                        $update=true;
                        $interval=4;
                        } 
                    //function setNewStatusBounce($variableID,$value,$dif,$update=false,$token=false,$category=0,$debug=false)    
                    echo "*********Aufruf Routine Status mit Zusatzparameter $wertOpt.\n";
                    $control["bounce"]=$this->setNewStatusBounce($variableID,$status,$interval,$update,$token,0,$debug);        // mit Update Bounce Status
                    IPSLogger_Inf(__file__, 'Aufruf Routine Status von '.IPS_GetName($variableID).'('.$variableID.') mit Zusatzparameter '.$wertOpt.' Bounce erkannt: '.($control["bounce"]?"Yes":"No"));
                    break;
                default:
                    break;
                }
            }
        }


	/***************************************
	 * ControlSwitchLevel of Autosteuerung
	 * HeatControl, Stromheizung erfordert Regelfunktionen, die können entweder auf Switch oder Level gehen. 
	 * übernimmt result und benötigt daraus zumindestens:
     *      NAME
     *      MODE        COOL oder default HEAT, setzt $ControlModeHeat
     *      THRESHOLD   default 1, setzt $treshold
     *      NOFROST     default 6, setzt $nofrost
     *      SETPOINT    Sollwert $setTemp versucht $actTemp aus STATUS dort hin zu bringen, ohne diesen Wert keine regelfunktion
     *      STATUS      aktuelle Temperatur
     *      ON
     *      OFF
     *      VALUE
     *      SWITCH      die IF Funktion, if:heatday, wenn true wird nach Sollwert geheizt, sonst nur nach Nofrost Steuerung/Absicherung
     *
     *
	 *  Derzeit Switch implementiert.
     *
     *  Liefert Status der aktuellen Berechnungen und Überlegungen als Ergebnis in COMMENT, dieser String wird auch gelogged
     *  Name, aktuelle gemessene Temperatur, Sollwert, Nofrost Wert, Threshold (geht nach unten), IF:OFF/ON als Zustand von SWITCH, Vnow ON/OFF als Zustand von VALUE, 
     *  die Reglerfunktion zwischen |    |
     *  und das Ergebinis mit neuem SWITCH, und den Wert von result[OFF]=false oder result[ON]=true
	 *
	 *******************************************************/


	public function ControlSwitchLevel(array &$result,$simulate=false, $debug=false)
		{
		/* Defaultwerte bestimmen, festlegen */
		$ergebnis=""; $ergebnisText=""; 
        $comment=false;         // nur COMMENT setzen wenn dieser Schalter true ist, nicht alle Ausgaben im Log machen um die Übersichtlichkeit zu verbessern
        if (isset($result["NAME"]) ) { $ergebnis.=$result["NAME"]." ";   $ergebnisText.=$result["NAME"]." ";  }
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
						
			//IPSLogger_Dbg(__file__, 'Function ControlSwitchLevel Aufruf mit Wert: '.json_encode($result));
			$ergebnis .= "T ".number_format($actTemp,2)." SP ".$setTemp." NF ".$nofrost." T ".$threshold." IF:".($result["SWITCH"]?"ON":"OFF")." Vnow:".($result["VALUE"]?"ON":"OFF");
            $ergebnisText .= ", aktuelle Temperatur ".number_format($actTemp,2);
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
                            $ergebnisText .= "Heizung ausschalten, Temperatur größer als Solltemperatur $setTemp °C.";
                            $comment=true;                            
							$result["SWITCH"]=true;
							unset($result["ON"]);
							$result["OFF"]="FALSE";
            			    IPSLogger_Inf(__file__, $ergebnisLang." für ".$ergebnis."    ".json_encode($result));                    					
							}
						}
					else	
						{
						if ( $actTemp < ($setTemp-$treshold) ) 
							{
                            /* Ist Temperatur unter (Sollwert-Threshold) gefallen und es wird nicht geheizt, SWITCH ist true, es wird eingeschaltet daher ist der Wert ON true */
							$ergebnis .=" |H0:T<(SP-T)| ";
                            $ergebnisText .= "Heizung einschalten, Temperatur um $threshold °C kleiner als Solltemperatur $setTemp °C.";
                            $comment=true;                            							
							$result["SWITCH"]=true;
							unset($result["OFF"]);
							$result["ON"]="TRUE";
            			    IPSLogger_Inf(__file__, $ergebnisLang." für ".$ergebnis."    ".json_encode($result));                    					
							}
						else
							{
							/* Ist Temperatur über (Sollwert-Threshold) gestiegen und es wird nicht geheizt, SWITCH ist true, es wird ausgeschaltet daher ist der Wert OFF false 
                             * Sicherheitsfunktion, Ausschalten muss immer gemacht werden
                             */
							$ergebnis .=" |H0:T>=(SP-T)| ";							
							$result["SWITCH"]=true;
							unset($result["ON"]);
							$result["OFF"]="FALSE";
							}
						}
					}
				else
					{
					/************************************************* 
					 *
					 * Regler , Kühlen nur wenn Temp über Sollwert und die if Bedingungen erfüllt sind 
					 *
					 * Entscheidung ob eingeschaltet oder ausgeschaltet wird abhängig vom aktuellen Zustand
					 *
					 ******************/                        
					if ($result["VALUE"]==true)
						{
						if ($actTemp < $setTemp )
							{
                            /* Ist Temperatur unter Sollwert gefallen und es wird gekühlt, SWITCH ist true, es wird ausgeschaltet daher ist der Wert OFF false */
							$ergebnis .=" |C1:T<SP| ";								
							$result["SWITCH"]=true;
							unset($result["ON"]);
							$result["OFF"]="FALSE";
            			    IPSLogger_Inf(__file__, $ergebnisLang." für ".$ergebnis."    ".json_encode($result));                             
							}
						}
					else	
						{
						if ($actTemp > ($setTemp+$treshold) ) 
							{
                            /* Ist Temperatur über (Sollwert+Threshold) gestiegen und es wird nicht gekühlt, SWITCH ist true, es wird eingeschaltet daher ist der Wert ON true */
							$ergebnis .=" |C1:T>(SP+T)| ";							
							$result["SWITCH"]=true;
							unset($result["OFF"]);
							$result["ON"]="TRUE";
            			    IPSLogger_Inf(__file__, $ergebnisLang." für ".$ergebnis."    ".json_encode($result));                             
							}
						}
					}
				}	
			else	
				{
                /* keine Temperaturregelung aktiviert, zB durch Wochenprogramm, aber nofrost Funktion trotzdem machen 
                 * es muss auch sichergestellt werden das eventuell noch eingeschaltete Heizkörper ausgeschaltet werden 
                 */
				if ($ControlModeHeat==true)
					{                    
                    /* nur für Heizgeräte machen */
					if ($result["VALUE"]==true)
						{
						if ($actTemp >$nofrost ) 
							{
							/* Ist Temperatur über NOFROST gestiegen und heizt noch, SWITCH ist true, es wird ausgeschaltet daher ist der Wert OFF false */
							$ergebnis .=" |H1:T>NF| ";
                            $ergebnisText .= "Nofrost aktiv: Heizung ausschalten, Temperatur größer als Nofrost Temperatur $nofrost °C.";
                            $comment=true;
							$result["SWITCH"]=true;
							unset($result["ON"]);
							$result["OFF"]="FALSE";
            			    IPSLogger_Inf(__file__, $ergebnisLang." für ".$ergebnis."    ".json_encode($result));                    					
							}
						}
					else	
						{
						if ( $actTemp < ($nofrost-$treshold) ) 
							{
                            /* Ist Temperatur unter (NOFROST-Threshold) gefallen und es wird nicht geheizt, SWITCH ist true, es wird eingeschaltet daher ist der Wert ON true */
							$ergebnis .=" |H0:T<(NF-T)| ";							
                            $ergebnisText .= "Nofrost aktiv: Heizung einschalten, Temperatur um $threshold °C kleiner als Nofrost Temperatur $nofrost °C.";
                            $comment=true;
							$result["SWITCH"]=true;
							unset($result["OFF"]);
							$result["ON"]="TRUE";
            			    IPSLogger_Inf(__file__, $ergebnisLang." für ".$ergebnis."    ".json_encode($result));                    					
							}
						else
							{
							/* Ist Temperatur über (NOFROST-Threshold) gestiegen und es wird nicht geheizt, SWITCH ist true, es wird ausgeschaltet daher ist der Wert OFF false 
                             * Sicherheitsfunktion, Ausschalten muss immer gemacht werden
                             */
							$ergebnis .=" |H0:T>=(NF-T)| ";							
							$result["SWITCH"]=true;
							unset($result["ON"]);
							$result["OFF"]="FALSE";
							}
						} 
                    }                       
				else
					{
                    /* wenn es um Kühlen geht gibt es keine NOFROST Position */
					unset($result["OFF"]);
					unset($result["ON"]);
					}		
				}

			$ergebnis .= "  ==>> Ergebnis : ".($result["SWITCH"]?"ON":"OFF");	
			if (isset($result["ON"]) == true) $ergebnis .= " ON:".$result["ON"];
			if (isset($result["OFF"]) == true) $ergebnis .= " OFF:".$result["OFF"];	
			}
		else
			{
			echo "Keine Regelfunktion, Setpoint nicht gesetzt.\n";
			}		
			
		//echo $ergebnis."\n";
		//$result["COMMENT"]=$ergebnis;	
        if ($comment) $result["COMMENT"]=$ergebnisText;
        else $result["COMMENT"]="";         // Länge größer 4 führt erst zu einer Anzeige 	
		return ($result);	
		}

	/***************************************
	 * ExecuteCommand of Autosteuerung
	 * 
     * hier wird der Befehl umgesetzt. Abhängig von "MODULE" werden die Funktionen unterschiedlich umgesetzt
     * Folgende Module sind aktuell implementiert
     *  IPSLight, IPSHeat
     *  SamsungTV
     *  Selenium
     *  für die anderen Namen wird nachgeschaut ob das Modul installiert ist
     *      HarmonyHub
     *      SamsungTizen
     *      DenonAVRHTTP
     *
     * Implementierung IPSLight/IPSHeat
     *      Es wird entweder "OID" oder "NAME" für das TARGET übergeben
     *      für NAME wird MODULE, ID und TYP ermittelt
     *      Rückmeldung ist "COMMAND" mit einem passenden Befehl zum Rückgängig machen wenn Delay ausgewählt wurde
     *          und $ergebnis mit dem Resultat des Schaltbefehls, am Ende der Routine mit "COMMENT" übergeben
     *      Wenn Name gesetzt ist wird OID nachtraeglich ermittelt, verwendet für Sprachausgabe #Ziel#
	 *    benötigt IPSLight oder IPSHeat (Stromheizung), Schalten ist eine eigene Funktion - erfolgt abhängig von if Auswertung
	 *    geht aber auch mit einem OID Wert
	 *
	 * IPSLight funktioniert auch für Gruppen und Programme, bei Switch ist auch Level und Color möglich 
	 * Die Unterscheidung Switch, Group oder program wird automatisch getroffen (getIdbyName ermittelt Modul, Typ und OID)
	 * Als Ergebnis dieser Auswertung wird ein zusaetzlicher Parameter in result IPSLIGHT ermittelt:  None, Program, Group,  
     *
     * switchObject wird aufgerufen, Parameter sind result MODULE, IPSLIGHT und TYPE. COMMAND ist der Rückgängig Befehl
     *      MODULE ist IPSLIGHT oder IPSHEAT
     *      IPSLIGHT ist None, Switch, Group, Program
     *      TYPE ist None, Switch, Group oder #LEVEL, #COLOR etc.
     *
     *       SWITCH|GROUP mit SWITCH|GROUP oder #LEVEL etc. NONE ist nichts tun
	 *
	 * Value für Wert nach Delay ist hier falsch !!!! wird nicht übergeben sondern geraten
	 * false, Next oder Value ???? ist nicht richtig
	 *
	 *******************************************************/

	public function ExecuteCommand($result,$simulate=false, $debug=false)
		{
        $debug=true;
		if ($debug) echo "   Execute Command, Befehl nun abarbeiten und dann eventuell Sprachausgabe:\n";
		$ergebnis="";  // fuer Rückmeldung dieser Funktion als COMMENT
		$command="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
		//IPSLogger_Inf(__file__, 'Function ExecuteCommand Aufruf mit Wert: '.json_encode($result));

		if (isset($result["MODULE"])==false) $result["MODULE"]="";	// damit Switch bei leeren Befehlen keinen Fehler macht 
		$this->log->LogMessage("ExecuteCommand;Module ".$result["MODULE"]."; ".json_encode($result));		

		/* hier wird zwischen IPS Modulen der alten Generation und direkt implementierten Modulen geschaltet.
		 * IPS Module der neuen Generation werden unter Default bearbeitet
         *  Internal    Monitor on/off, läuft am Ende weiter duch zu
         *  IPSHeat,IPSLight
         *  Selenium
         *  SamsungTV
         *  EchoRemote
         *  leer
         *  default mit weiteren Modulbezeichnungen
         *
		 */
		switch ($result["MODULE"])
			{
            case "Internal":
                /* Bearbeitung erfolgt nur wenn
                 *     es einen MonitorMode Tab mit einem Namen und der dazugehörigen  Statusvariable gibt
                 *     es eine Konfiguration in der Function Autosteuerung_MonitorMode gibt
                 *     dort der Switchname für den Monitor definiert ist und es diesen auch gibt
                 *     wenn die Konfiguration auf Aus oder Ein steht wird der Input aus der Autosteuerung ignoriert
                 *     wenn wir auf Auto stehen und es gibt eine Condition Config, dann leider auch
                 *     sonst natürlich
                 *
                 * wenn ich Monitor:status schreibe, komme ich hier heraus
                 * wenn ich name:Arbeitszimmer,status:on schreibe ist es eine normele IPSHeat Funktion
                 *
                 */
                 
   				//IPSLogger_Not(__file__, 'Autosteuerung::ExecuteCommand, Internal Module vorhanden. Hier evaluieren.');
            	$AutoSetSwitches = Autosteuerung_SetSwitches();
                if (isset($AutoSetSwitches["MonitorMode"]["NAME"])) 
                    {
                    $monitorId = @IPS_GetObjectIDByName($AutoSetSwitches["MonitorMode"]["NAME"],$this->CategoryId_Ansteuerung);
                    if ($monitorId) 
                        {
                        $SchalterMonitorID            = IPS_GetObjectIDByName("SchalterMonitor", $monitorId);
                        $StatusMonitorID              = IPS_GetObjectIDByName("StatusMonitor",$monitorId);                            
                        $MonConfig=GetValue($monitorId);        // Status MonitorMode in Zahlen
                        echo "Autosteuerung::ExecuteCommand: Modul \"Internal\" abarbeiten, Werte in ".$this->CategoryId_Ansteuerung." Name : ".$AutoSetSwitches["MonitorMode"]["NAME"].":  $monitorId hat ".GetValueIfFormatted($monitorId)."  \n";
                        $monConfigFomat=GetValueIfFormatted($monitorId);            // Status MonitorMode formattiert
                        if (function_exists("Autosteuerung_MonitorMode")) 
                            {
                            $MonitorModeConfig=Autosteuerung_MonitorMode();
                            if (isset($MonitorModeConfig["SwitchName"]))
                                {
                   				//IPSLogger_Not(__file__, 'Autosteuerung::ExecuteCommand, Internal Module vorhanden. function Autosteuerung_MonitorMode existiert: '.json_encode($MonitorModeConfig));
                                echo "function Autosteuerung_MonitorMode existiert, es geht weiter: ".json_encode($MonitorModeConfig)."\n";
                                $result["NAME"]=$MonitorModeConfig["SwitchName"];
                                $ergebnisTyp=$this->getIdByName($result["NAME"]);                                
                                $state=GetValue($monitorId);
                                if ($ergebnisTyp !== false)             // Switch Variable vorhanden
                                    {
                                    if ($state<2)            // nicht Auto Mode 
                                        {
                                        if ($state) $result["ON"]="TRUE";
                                        else $result["OFF"]="FALSE";
                                        }
                                    else 
                                        {
                                        //echo "MonitorMode auf AUTO.\n";
                                        if (isset($MonitorModeConfig["Condition"]))
                                            {
                                            echo "Autosteuerung::ExecuteCommand, function Autosteuerung_MonitorMode existiert, aber auch Condition, nichts tun.\n";
                            				//IPSLogger_Not(__file__, 'Autosteuerung::ExecuteCommand, function Autosteuerung_MonitorMode existiert, aber auch Condition, nichts tun.');     // zuviele Logs werden ausgegeben
                                            }
                                        else
                                            {
                                            if ($result["MONITOR"]=="ON") 
                                                {
                                                $result["ON"]="TRUE";                                                
                                                $state=true;
                                                }
                                            elseif ($result["MONITOR"]=="OFF") 
                                                {
                                                $result["OFF"]="FALSE";;                                                
                                                $state=false;
                                                }
                                            }
                                        }
                                    SetValue($SchalterMonitorID,$state);
                                    SetValue($StatusMonitorID,$state);              // sollte auch den Änderungsdienst zum Zuletzt Wert machen                                    
                                    IPSLogger_Inf(__file__, "Autosteuerung Befehl MONITOR: Switch Befehl gesetzt auf ".$result["NAME"]." State : ".($state?"Ein":"Aus")."  ".json_encode($ergebnisTyp));    
                                    }
                                //print_r($ergebnisTyp);
                                }
                            }
                        }
                    }

                //IPS_getCategoryIdByName('Wochenplan-Stromheizung',   $CategoryIdData);

                //$configurationAutosteuerung = Autosteuerung_Setup(); print_r($configurationAutosteuerung);
                //print_r($AutoSetSwitches["MonitorMode"]);

                if (isset($result["NAME"]))
                    {
                    /* kein Break sondern einfach weiterlaufen lassen, Name Monitor gefunden */
                    }
                else break;

			/******
			 *
			 *  hier wird zuerst geschaltet, wir erwarten uns in result entweder OID oder NAME, wenn name auch eventuell NAME_EXT für Level und Color
			 *  wenn die OID angegeben wurde geht es sehr einfach, sonst
             *  wenn der NAME angegeben wurde rausfinden ob es ein SWITCH, GROUP oder PROGRAM ist
			 *  es wird die OID ebenfalls ermittelt
             *  dann switchObject aufrufen
             *
			 *****************/
			case "IPSHeat":     // Heat wird in switchObject anders behandeln als Light
			case "IPSLight":
				if (isset($result["OID"]) == true)
					{
					//IPSLogger_Dbg(__file__, 'OID '.$result["OID"]);
					$result["IPSLIGHT"]="None";
					$command.="SetValue(".$result["OID"].",false);";
					$result["COMMAND"]=$command;
					//$ergebnis .= self::switchObject($result,$simulate);					
					$ergebnis .= $this->switchObject($result,$simulate);
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
                                if ($debug) echo "       IPSHEAT/IPSLIGHT SWITCH:\n";
      					    	if (isset($result["NAME_EXT"])==true)           // wert für einen Schalter
		      				    	{
				      			    //IPSLogger_Dbg(__file__, 'Wert '.$name.' ist Wert für einen Schalter. ');
    						       	$result["TYPE"]=$result["NAME_EXT"];	
          							if ($result["MODULE"]=="IPSLight") 
										{
										$result["OID"] = $this->lightManager->GetSwitchIdByName($name);
			          					$value=GetValue($result["OID"]);
				    	      			if ($result["TYPE"]=="#COLOR") 	{	$command.='$lightManager->SetRGB('.$result["OID"].",".$value.");"; }	
					    		      	if ($result["TYPE"]=="#LEVEL") 	{	$command.='$lightManager->SetValue('.$result["OID"].",".$value.");"; }	
      					    			if ($result["TYPE"]=="None") 	{	$command.='SetValue('.$result["OID"].",".$value.");"; }											
										}
                                    else 
										{
										$result["OID"] = $this->heatManager->GetSwitchIdByName($name);
			          					$value=GetValue($result["OID"]);
				    	      			if ($result["TYPE"]=="#COLOR") 	{	$command.='$heatManager->SetRGB('.$result["OID"].",".$value.");"; }	
					    		      	if ($result["TYPE"]=="#LEVEL") 	{	$command.='$heatManager->SetValue('.$result["OID"].",".$value.");"; }	
      					    			if ($result["TYPE"]=="None") 	{	$command.='SetValue('.$result["OID"].",".$value.");"; }											
										}														
		      			    		$command.="IPSLogger_Dbg(__file__, 'Delay abgelaufen von ".$name."');";
          							//echo "**** Aufruf Switch Ergebnis command \"".$result["IPSLIGHT"]."\"   ".str_replace("\n","",$command)."\n";										
	    	      					}
		    		      		else                                        // Schalter Ein/Aus
			    			      	{	 				
      			    				//IPSLogger_Dbg(__file__, 'Wert '.$name.' ist ein Schalter. ');
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
                                    $result["TYPE"]="Switch";                                        					
    		       					}                            
					          	$result["IPSLIGHT"]="Switch";
		      		    	    $result["COMMAND"]=$command;
                                if ($debug) echo "      ->Hier wird ein IPSHEAT SWITCH geschaltet, Aufruf SwitchObject, erwartet sich ON: oder OFF:\n";
								$ergebnis .= $this->switchObject($result,$simulate,$debug);									
                                break;   
                           case "Group":
                                if (isset($result["NAME_EXT"])==true)       // Wert für eine Gruppe
		      				    	{
				      			    //IPSLogger_inf(__file__, 'Wert '.$name.' ist eine Gruppe mit Extension '.$result["NAME_EXT"].'. ');
    						       	$result["TYPE"]=$result["NAME_EXT"];	
          							if ($result["MODULE"]=="IPSLight") 
										{
										$result["OID"] = $this->lightManager->GetGroupIdByName($name);
			          					$value=GetValue($result["OID"]);
				    	      			//if ($result["IPSLIGHT"]=="#COLOR") 	{	$command.='$lightManager->SetRGB('.$result["OID"].",".$value.");"; }	
					    		      	//if ($result["IPSLIGHT"]=="#LEVEL") 	{	$command.='$lightManager->SetValue('.$result["OID"].",".$value.");"; }	
      					    			//if ($result["IPSLIGHT"]=="None") 	{	$command.='SetValue('.$result["OID"].",".$value.");"; }											
										}
                                    else 
										{
										$result["OID"] = $this->heatManager->GetGroupIdByName($name);
			          					$value=GetValue($result["OID"]);
				    	      			//if ($result["IPSLIGHT"]=="#COLOR") 	{	$command.='$heatManager->SetRGB('.$result["OID"].",".$value.");"; }	
					    		      	//if ($result["IPSLIGHT"]=="#LEVEL") 	{	$command.='$heatManager->SetValue('.$result["OID"].",".$value.");"; }	
      					    			//if ($result["IPSLIGHT"]=="None") 	{	$command.='SetValue('.$result["OID"].",".$value.");"; }											
										}														
		      			    		$command.="IPSLogger_Dbg(__file__, 'Delay abgelaufen von ".$name."');";
				      		    	$result["COMMAND"]=$command;                                      
                                    }
                                else                                        // Gruppe Ein/Aus
                                    {
			    		            //IPSLogger_Dbg(__file__, 'Wert '.$name.' ist eine Gruppe. ');
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
                                    $result["TYPE"]="Group";                                         
                                    }
                                $result["IPSLIGHT"]="Group";                                
                                $result["COMMAND"]=$command;
                                $ergebnis .= $this->switchObject($result,$simulate);
                                break;                            
                          case "Program":
	    				        //IPSLogger_Dbg(__file__, 'Wert '.$name.' ist ein Programm. ');
                          	    if ($debug) echo "Hier ist die IPSLight/Heat Programm-Abarbeitung. Ergebnistyp: \n"; print_r($ergebnisTyp);
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
                    if (isset($result["MONITOR"]))          // übernimmt auch den  STATUS Wert, aber benötigt werden für switchObject result["ON"] und ["OFF"]
                        {
                        $ergebnisFormatted=str_replace("\n","",$ergebnis);
    					IPSLogger_Inf(__file__, "Autosteuerung Befehl MONITOR : Ergebnis $ergebnisFormatted Result: ".json_encode($result));
                        echo "Monitor geschaltet: Ergebnis $ergebnisFormatted Result: ".json_encode($result).".\n";    
                        }
					} 
				else        // weder result OID oder NAME ist gesetzt, nichts tun
					{
					}
				break;
            case "Selenium":            // neuer Bereich um Webserver anzusprechen
                $this->moduleSelenium($result);
                break;
			case "SamsungTV":
                $this->moduleSamsungTV($result); 
				break;
            case "EchoRemote": 
                $this->moduleEchoRemote($result);             	
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
				echo "Fehler in class Autosteuerung ExecuteCommand, Module ".$result["MODULE"]." nicht bekannt. Referenziert im Befehl ".json_encode($result)."\n";
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
   		//IPSLogger_Inf(__file__, 'Aufruf execute, Ergebnis ist "'.$ergebnis.'"');
					
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
							//$wert="geändert";
                            $wert="geendert";
							if ( isset($result["OLDSTATUS"]) )
								{
								if ($result["STATUS"]>$result["OLDSTATUS"]) 
                                    {
                                    //$wert="erhöht";
                                    $wert="gestiegen";
                                    }
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
    		//IPSLogger_Inf(__file__, 'Aufruf execute '.json_encode($result));
			
			if ($result["SWITCH"]===true)			/* nicht nur die Schaltbefehle mit If Beeinflussen, auch die Sprachausgabe */
				{
				if ( ( (isset($result["FORCE"])) && ($result["FORCE"]==true) ) || 
                           ( ( (self::isitsleep() == false) || (self::getFunctions("SilentMode")["VALUE"] == 0) ) &&  (self::getFunctions("SilentMode")["VALUE"] != 1) ) )
					{
                    //print_r($result);
					if ( isset($result["LOUDSPEAKER"]) )
						{
						echo "   Es wird am Amazon Echo ".IPS_GetName($result["LOUDSPEAKER"])." gesprochen : ".$result["SPEAK"]."\n";
                       	IPSLogger_Inf(__file__, 'Es wird am Amazon Echo '.IPS_GetName($result["LOUDSPEAKER"]).' gesprochen : '.$result["SPEAK"]);
						if ($simulate==false) 
                            {
                            //EchoRemote_TextToSpeech($result["LOUDSPEAKER"], $result["SPEAK"]);
                            tts_play($result["LOUDSPEAKER"],$result["SPEAK"],'',2); //tts_play kann mehrere Lautsprecher
                            }
                        }																																																					
					else
                        { 
					    echo "  Es wird am Default Lautsprecher gesprochen : ".$result["SPEAK"]."\n"; 
                       	IPSLogger_Inf(__file__, 'Es wird am Default Lautsprecher gesprochen : '.$result["SPEAK"]);
                        if ($simulate==false) tts_play(1,$result["SPEAK"],'',2);
						}
					}	
				}
			}
		$result["COMMENT"]=$ergebnis;			
		return ($result);							
		}

    /* neue Selenium Ansteuerung, als Teil von ExecuteCommand
     * result["NAME"]
     * result["DEVICE"]
     * result["COMMAND"]
     *
     * module:SamsungTizen,device:Wohnzimmer SamsungTizen,name:none,command:KEY_POWERON
     * module:HarmonyHub,device:Logitech Wohnzimmer Harmony,name:Samsung TV,command:PowerOn
     * module:Selenium,name:Iiyama,device:Wohnzimmer,comman:PowerOn
     */
    private function moduleSelenium(&$result)
        {
        if ( isset($this->installedModules["Guthabensteuerung"] ) )
            {
            echo "moduleSelenium aufgerufen mit ".$result["DEVICE"]." ".$result["NAME"]." ".$result["COMMAND"].":\n";
            IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
            IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
            IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
            $guthabenHandler = new GuthabenHandler(true,true,true);         // Steuerung für parsetxtfile
            $seleniumHandler = new SeleniumHandler();           // Selenium Test Handler, false deaktiviere Ansteuerung von webdriver für Testzwecke vollstaendig
            $configSelenium = $guthabenHandler->getGuthabenConfiguration()["Selenium"];
            print_R($configSelenium);
            $seleniumOperations = new SeleniumOperations(); 

            switch (strtoupper($result["DEVICE"]))
                {
                case "IIYAMA":
                    $configTabs=array(
                        "Hosts" => array(
                                "IIYAMA"   =>  array (
                                    "URL" => "10.0.1.42",
                                    "CLASS"     => "SeleniumIiyama", 
                                    "CONFIG"    => array(                   // ohne Config wird zum Beispiel auch nicht die URL verfügbar sein
                                                "Power" => "On"             // oder Standby
                                                    ),                   
                                                ),
                                        ),
                                );
                    if (Strtoupper($result["COMMAND"])=="POWEROFF")  $configTabs["Hosts"]["IIYAMA"]["CONFIG"]["Power"] = "Off";     // anderen Wert überschreiben, Default is On
                    $seleniumOperations->automatedQuery(false,$configTabs["Hosts"],true);          // true debug, $webDriverName false for default
                    break;
                default:
                    break;
                }
            }
        }

    /* alte SamsungTV Ansteuerung, als Teil von ExecuteCommand
     */
    private function moduleSamsungTV(&$result)
        {
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
        }

    /* beim Abarbeiten der Befehle wird manchmal auf ein Module verwiesen, hier abarbeiten
     * es wird die Variable $result als Link übergeben. Änderungen werden direkt drinnen gemacht.
     */
    private function moduleEchoRemote(&$result)
        {
        IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              // umgeleitet auf das config Verzeichnis, wurde immer irrtuemlich auf Github gestellt
        $hardware = new Hardware();           // in Hardware_Library definiert, entsprechendes include machen
        $deviceListFiltered = $hardware->getDeviceListFiltered(deviceList(),["Type" => "EchoControl", "TYPEDEV" => "TYPE_LOUDSPEAKER"],false);     // true with Debug
        //echo "Ausgabe aller Geräte mit Type EchoControl und TYPEDEV TYPE_LOUDSPEAKER:\n";
        $echos = array();
        foreach ($deviceListFiltered as $name => $device) 
            {
            echo "   ".str_pad($name,35)."    ".$device["Instances"][0]["OID"]."\n";
            $echos[]=$device["Instances"][0]["OID"];
            }

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
	 * benötigt wird innerhalb von result die keys "SWITCH", "ON", "OFF", "IPSLIGHT", "MODULE", "NAME", "OID", "VALUE_ON", "VALUE_OFF"
     *
     * switchObjectNow wird aufgerufen, Parameter sind result MODULE, IPSLIGHT und TYPE. COMMAND ist der Rückgängig Befehl
     *      MODULE ist IPSLIGHT oder IPSHEAT
     *      IPSLIGHT ist None, Switch, Group, Program
     *      TYPE ist None, Switch, Group oder #LEVEL, #COLOR etc.
     *
     *      SWITCH|GROUP mit SWITCH|GROUP oder #LEVEL etc. NONE ist nichts tun
	 *
	 *******************************************************/
	 
	private function switchObject(array &$result,$simulate=false,$debug=false)
		{
        if ($debug) echo "Autosteuerung::SwitchObject mit ".json_encode($result)." aufgerufen. \n";
		$ergebnis=""; $undo="";
		//IPSLogger_Inf(__file__, 'SwitchObject :  '.json_encode($result));	
		if ($result["SWITCH"]===true)
			{
			if (isset($result["ON"])==true)
				{
				$result["ON"]=strtoupper($result["ON"]);
				if ($result["ON"]=="FALSE")
					{
					if ($simulate==false)			/* Bei simulate nicht schalten */
						{
						$undo = $this->switchObjectNow($result,false);
						}
					else $ergebnis .= "Set Switch, Group or Value auf false.\n";
					}	
				if ($result["ON"]=="TRUE")
					{
					if ($simulate==false)			/* Bei simulate nicht schalten */
						{
						$undo = $this->switchObjectNow($result,true);											
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
						$undo = $this->switchObjectNow($result,false);                           
						}
					else $ergebnis .= "Set Switch, Group or Value auf false.\n";
					}
				if ($result["OFF"]=="TRUE")
					{
					if ($simulate==false)			/* Bei simulate nicht schalten */
						{
						$undo = $this->switchObjectNow($result,true);	
						}
					else $ergebnis .= "Set ".$result["NAME"]." Switch, Group or Value auf true und Level auf Wert (OFF).\n";	
					}
				}
			}
        if ($simulate) echo "SwitchObject Ergebnis : \"$ergebnis\" \n";
		else 
			{
			$ergebnis.= "SwitchObject Undo with $undo.\n";
			echo "Ergebnis : \"$ergebnis\"   ".($simulate?"Simulate":"")."  ".($debug?"Debug":"")."\n";
			}
		$result["UNDO"]=$undo;			// auch wenn Simulate ist die DIM und DELAY Funktionen austesten
   		//IPSLogger_Inf(__file__, 'Switch Object '.json_encode($result));

		return($ergebnis);		
		}

	/* Jetzt wirklich schalten, es muss das ganze result übergeben werden, und ob ein oder aus 
     * Parameter sind result MODULE, IPSLIGHT und TYPE. COMMAND ist der Rückgängig Befehl
     *      MODULE ist IPSLIGHT oder IPSHEAT
     *      IPSLIGHT ist None, Switch, Group, Program
     *      TYPE ist None, Switch, Group oder #LEVEL, #COLOR etc.
     *
     *      SWITCH|GROUP mit SWITCH|GROUP oder #LEVEL etc. NONE ist nichts tun	 *
     *
	 * benötigt werden innerhalb von result die keys "ON", "OFF", "IPSLIGHT", "MODULE", "NAME", "OID", "VALUE_ON", "VALUE_OFF"
     *
     *  IPSLIGHT==None          SetValue OID
     *  Rest abhängig vom MODULE
     *
	 *
	 */
	
	private function switchObjectNow($result,$state,$debug=false)
		{
        //IPSLogger_Inf(__file__, 'switchObjectNow('.$result["NAME"].','.$state.')'."  Result: ".json_encode($result));	
        //echo "Autosteuerung::switchObjectNow mit ".json_encode($result)." und Status ".($state?"true":"false")." aufgerufen.\n";
		$undo=""; 
		//$value=0;		// wenn Variable undefined ist, liegt ein Fehler vor, doch noch so radikal debuggen
		if ( (isset($result["ON"])==true) && (isset($result["VALUE_ON"])==true) )  $value=$result["VALUE_ON"];
		if ( (isset($result["OFF"])==true) && (isset($result["VALUE_OFF"])==true) )   $value=$result["VALUE_OFF"];
		
        if ($result["IPSLIGHT"]=="None") 	
            {	
            SetValue($result["OID"],$state);
            $undo.="SetValue(".$result["OID"].",".($state?"false":"true").");"; 
            }
        elseif ($result["MODULE"]=="IPSLight")
            {
            if ($result["IPSLIGHT"]=="Group") 	
                {
                if ($result["TYPE"]=="Group")                                    	
                    {                    	
                    IPSLight_SetGroupByName($result["NAME"],$state); 
                    $undo.='IPSLight_SetGroupByName("'.$result["NAME"].'",'.($state?"false":"true").");";
                    }
                if ($result["TYPE"]=="#LEVEL") 	
                    {
                    IPSLight_SetGroupByName($result["NAME"]."#Level",$value);                           // Routine bleibt gleich, passt sich an Namen an
                    $undo.='IPSLight_SetGroupByName("'.$result["NAME"].'#Level",'.$value.");";      
                    }                    
                if ($result["TYPE"]=="#COLOR") 	
                    {
                    IPSLight_SetGroupByName($result["NAME"]."#Color",$value);                           // Routine bleibt gleich, passt sich an Namen an
                    $undo.='IPSLight_SetGroupByName("'.$result["NAME"]."#Color".'",'.$value.");";
                    }                    
                }
            elseif ($result["IPSLIGHT"]=="Switch") 	
                {
                if ($result["TYPE"]=="Switch")                                    	
                    {
                    IPSLight_SetSwitchByName($result["NAME"],$state); 
                    $undo.='IPSLight_SetSwitchByName("'.$result["NAME"].'",'.($state?"false":"true").");";
                    }
                if ($result["TYPE"]=="#COLOR") 	
                    {
                    if ($State) $this->lightManager->SetRGB($result["OID"], $value);
                    else $undo.='$lightManager->SetRGB("'.$result["OID"].'",'.$value.");";	 
                    }
                if ($result["TYPE"]=="#LEVEL") 	
                    {	
                    if ($State) $this->lightManager->SetValue($result["OID"], $value);
                    else $undo.='$lightManager->SetValue("'.$result["OID"].'",'.$value.");"; 
                    }
                }						
            }
        else
            {
            echo "Autosteuerung::switchObjectNow (IPS_Heat) mit Status ".($state?"true":"false")." aufgerufen (".json_encode($result).").\n";
            if ($result["IPSLIGHT"]=="Group")  	
                {
                if ($result["TYPE"]=="Group")                                    	
                    {                     
                    //IPSLogger_Inf(__file__, 'IPSHeat_SetGroupByName('.$result["NAME"].','.$state.')');	
                    IPSHeat_SetGroupByName($result["NAME"],$state); 
                    $undo.='IPSHeat_SetGroupByName("'.$result["NAME"].'",'.($state?"false":"true").");";
                    }
                if ($result["TYPE"]=="#LEVEL") 	
                    {
                    //IPSLogger_Inf(__file__, 'IPSHeat_SetGroupByName('.$result["NAME"].'#Level,'.$value.')');
                    IPSHeat_SetGroupByName($result["NAME"]."#Level",$value);                           // Routine bleibt gleich, Funktion passt sich an Namen an
                    $undo.='IPSHeat_SetGroupByName("'.$result["NAME"]."#Level".'",'.$value.");";      
                    }                    
                if ($result["TYPE"]=="#COLOR") 	
                    {
                    //IPSLogger_Inf(__file__, 'IPSHeat_SetGroupByName('.$result["NAME"].'#Color,'.$value.')');
                    IPSHeat_SetGroupByName($result["NAME"]."#Color",$value);                           // Routine bleibt gleich, Funktion passt sich an Namen an
                    $undo.='IPSHeat_SetGroupByName("'.$result["NAME"]."#Color".'",'.$value.");";
                    }                 
                }
            elseif ($result["IPSLIGHT"]=="Switch")	
                {	
                if ($result["TYPE"]=="Switch")
                    {
                    if ($debug) echo 'Aufruf IPSHeat_SetSwitchByName("'.$result["NAME"].'",'.($state?"true":"false").");";								
                    IPSHeat_SetSwitchByName($result["NAME"],$state); 
                    $undo.='IPSHeat_SetSwitchByName("'.$result["NAME"].'",'.($state?"false":"true").");";				// wegen undo genau umgekehrt aufloesen
                    } 
                if ($result["TYPE"]=="#COLOR") 	
                    {	
                    if ($state) $this->heatManager->SetRGB($result["OID"], $value); 
                    else $undo.='$heatManager->SetRGB("'.$result["OID"].'",'.$value.");";	 
                    }	
                if ($result["TYPE"]=="#LEVEL") 	
                    {	
                    if ($state) $this->heatManager->SetValue($result["OID"], $value); 
                    else $undo.='$heatManager->SetValue("'.$result["OID"].'",'.$value.");";	 
                    }	
                }
            }
		return ($undo);
		}
		
	/* Zusammenfassung der Befehle die nach der execute Funktion kommen 
     * echo "Aufruf timerCommand mit :".json_encode($result)."\n";
     *
	 * Timer wird einmal aufgerufen um nach Ablauf wieder den vorigen Zustand herzustellen.
	 * Bei DIM Befehl anders, hier wird der unter DIM#LEVEL definierte Zustand während der Zeit DIM#DELAY versucht zu erreichen
	 * 
	 * Delay ist davon unabhängig und kann zusätzlich verwendet werden
	 * "DELAY":720,"DELAY#CHECK":true
     *
     * DELAY#CHECK ist eine Sonderfunktion, nach Ablauf der Zeit prüfen ob Bedingung noch gültig, wenn nicht mehr gültig, Switch und Timer ausschalten. 
     *
	 * nur machen wenn if condition erfüllt ist, andernfalls wird der Timer ueberschrieben
     *
     */

	public function timerCommand($result,$simulate=false,$ipslogger='IPSLogger_Dbg')
		{
        $ergebnis="";
		if ($result["SWITCH"]===true)
			{
			if (isset($result["DIM"])==true)
				{
                echo "Aufruf timerCommand für DIM mit :".json_encode($result)."\n";
				$ergebnis .= "Execute Command Dim mit Level : ".$result["DIM#LEVEL"]." und Time : ".$result["DIM#TIME"]." Ausgangswert : ".$result["VALUE_ON"]." für OID ".$result["OID"];
				$value=(integer)(($result["DIM#LEVEL"]-$result["VALUE_ON"])/10);
				$time=(integer)($result["DIM#TIME"]/10);
				$EreignisID = $this->getEventTimerID($result["NAME"]."_EVENT_DIM");

                if ($result["MODULE"]=="IPSHeat")
                    {
    				$befehl="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
	    			$befehl.='$value=$heatManager->GetValue('.$result["OID"].')+'.$value.";\n";
		    		$befehl.='if ($value<=('.$result["DIM#LEVEL"].')) {'."\n";
			    	$befehl.='  $heatManager->SetValue('.$result["OID"].',$value); } '."\n".'else {'."\n";
				    $befehl.='  IPS_SetEventActive('.$EreignisID.',false);}'."\n";
				    $befehl.=$ipslogger.'(__file__, "Timer Switch Command Dim '.$result["NAME"].' mit aktuellem Wert : ".$value."   ");'."\n";
                    }
                else
                    {                    
    				$befehl="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
	    			$befehl.='$value=$lightManager->GetValue('.$result["OID"].')+'.$value.";\n";
		    		$befehl.='if ($value<=('.$result["DIM#LEVEL"].')) {'."\n";
			    	$befehl.='  $lightManager->SetValue('.$result["OID"].',$value); } '."\n".'else {'."\n";
				    $befehl.='  IPS_SetEventActive('.$EreignisID.',false);}'."\n";
				    $befehl.=$ipslogger.'(__file__, "Timer Switch Command Dim '.$result["NAME"].' mit aktuellem Wert : ".$value."   ");'."\n";
                    }
                echo "===================\n".$befehl."\n===================\n";
				echo "   Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$result["COMMAND"])."\n";
				echo "   Script für Timer für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$befehl)."\n";
				/* Timer wird insgesamt 10 mal aufgerufen, d.h. increment ist Differenz aktueller Wert zu Zielwert. Zeit zwischen den Timeraufrufen ist delay durch 10 */		
				if ($simulate==false)
					{
					$this->setDimTimer($result["NAME"],$time,$befehl);
					}
				}
			if (isset($result["DELAY"])==true)
				{
				if ($result["DELAY"]>0)
					{
					if ( (isset($result["DELAY#CHECK"])) && ($result["DELAY#CHECK"]==true) )        // DELAY#CHECK ist oft gesetzt obwohl der Zustand false ist
						{
                        if ($result["UNDO"] != "")
                            {
                            echo "Aufruf timerCommand für DELAY#CHECK mit :".json_encode($result)."\n";
                            $EreignisID = $this->getEventTimerID($result["NAME"]."_EVENT_DIM");		// damit der richtige Timer deaktiviert wird				
                            $befehl="include(IPS_GetKernelDir().\"scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php\");\n";
                            $befehl.='if (GetValue('.$result["SOURCEID"].")==false) {\n";
                            $befehl.='  IPS_SetEventActive('.$EreignisID.',false);'."\n";
                            $befehl.='  '.$result["UNDO"]."\n";
                            $befehl.='  '.$ipslogger.'(__file__, "Timer Switch Command Delay '.$result["NAME"].' mit aktuellem Wert : false, wird ausgeschaltet "); }'."\n";
                            $befehl.='else '.$ipslogger.'(__file__, "Timer Switch Command Delay '.$result["NAME"].' mit aktuellem Wert : true, bedeutet retrigger ");'."\n";
                            $ergebnis .= "Execute Command Delay#Check , Script für Timer ".$result["NAME"]." für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$befehl);
                            //print_r($result);
                            if ($simulate==false)
                                {
                                $this->setDimTimer($result["NAME"],$result["DELAY"],$befehl);           // richtige Implementierung, alle Delay Sekunden nachschauen obs noch passt
                                IPSLogger_Dbg(__file__, "timerCommand für Timer ".$result["NAME"]." Funktion DELAY#CHECK mit gültigem Undo Befehl ".$result["UNDO"]." erkannt.");
                                //$this->setEventTimer($result["NAME"],$result["DELAY"],$result["COMMAND"]);        // nur ein einmaliger Timer aufruf
                                }
                            }
                        else 
                            {
                            IPSLogger_Dbg(__file__, "timerCommand für Timer ".$result["NAME"]." Funktion DELAY#CHECK mit ungültigem Undo Befehl erkannt.");   
                            }
						}
					else
						{
                        echo "Aufruf timerCommand für DELAY mit :".json_encode($result)."\n";
						$ergebnis .= "Execute Command Delay, Script für Timer ".$result["NAME"]." für Register \"".$result["IPSLIGHT"]."\" : ".str_replace("\n","",$result["COMMAND"]);
						//print_r($result);
						if ($simulate==false)
							{
							$this->setEventTimer($result["NAME"],$result["DELAY"],$result["COMMAND"]);
							}
						}
					}	
				}
			}
        echo $ergebnis."\n";	
        return($ergebnis);
        }

    /**********************************************************
     * Vereinfachung der Timeransteuerung in der AWS 
     *
     * Funktion hier zusammengefasst. So angelegt dass es immer noch für ISPLight und IPSHEat Module funktioniert.
     *
     *************************************/    

    public function switchAWS($switch, $scene, $debug=false)
        {
        $status=false;
        // CreateVariable ($Name, $Type ( 0 Boolean, 1 Integer 2 Float, 3 String) , $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
		$statusID  = CreateVariableByName($this->AnwesenheitssimulationID,$scene["NAME"]."_Status", 1,"AusEin");            
		$counterID = CreateVariableByName($this->AnwesenheitssimulationID,$scene["NAME"]."_Counter",1,"");            		
		if ( strtoupper($scene["TYPE"]) == "AWS" )  $text="AWS für ";
        else $text="TIMER für ";
        if ($switch)
            {
            /* IPS_Light/Heat einschalten. timer schaltet selbsttaetig wieder aus */
			SetValue($statusID,true);            
			if (isset($scene["EVENT_IPSLIGHT"]))
				{
				$text.='IPSLight Switch '.$scene["EVENT_IPSLIGHT"].' einschalten. ';
				$this->log->LogMessage($text.json_encode($scene));
				IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], true);
				$command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSLight_SetSwitchByName("'.$scene["EVENT_IPSLIGHT"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer für IPSLight Schalter '.$scene["EVENT_IPSLIGHT"].' wurde abgeschlossen.");';
				}
			elseif (isset($scene["EVENT_IPSLIGHT_GRP"]))
                {
                $text.='IPSLight Group '.$scene["EVENT_IPSLIGHT_GRP"].' einschalten. ';
                $this->log->LogMessage($text.json_encode($scene));
                IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], true);
                $command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSLight_SetGroupByName("'.$scene["EVENT_IPSLIGHT_GRP"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer für IPSLight Gruppe '.$scene["EVENT_IPSLIGHT_GRP"].' wurde abgeschlossen.");';
                }
			elseif (isset($scene["EVENT_IPSHEAT"]))
				{
				$text.='IPSHeat Switch '.$scene["EVENT_IPSHEAT"].' einschalten. ';
				$this->log->LogMessage($text.json_encode($scene));
				IPSHeat_SetSwitchByName($scene["EVENT_IPSHEAT"], true);
				$command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSHeat_SetSwitchByName("'.$scene["EVENT_IPSHEAT"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer für IPSHeat Schalter '.$scene["EVENT_IPSHEAT"].' wurde abgeschlossen.");';
				}
			elseif (isset($scene["EVENT_IPSHEAT_GRP"]))
                {
                $text.='IPSHeat Group '.$scene["EVENT_IPSHEAT_GRP"].' einschalten. ';
                $this->log->LogMessage($text.json_encode($scene));
                IPSHeat_SetGroupByName($scene["EVENT_IPSHEAT_GRP"], true);
                $command='include(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Switch.inc.php");'."\n".'SetValue('.$statusID.',false);'."\n".'IPSHeat_SetGroupByName("'.$scene["EVENT_IPSHEAT_GRP"].'", false);'."\n".'$log_Autosteuerung->LogMessage("Befehl Timer für IPSHeat Gruppe '.$scene["EVENT_IPSHEAT_GRP"].' wurde abgeschlossen.");';
                }

            $status=$this->getEventTimerStatus($scene["NAME"]."_EVENT");     // keine Textausgabe wenn Timer bereits gesetzt    
            echo "   getEventTimerStatus(".$scene["NAME"]."_EVENT) liefert Status : $status\n";
			if ($scene["EVENT_CHANCE"]==100)
				{
				//echo "feste Ablaufzeit, keine anderen Parameter notwendig.\n";
				$this->setEventTimer($scene["NAME"],$this->timeStop-$this->now,$command);
                $text.=' Timer gesetzt auf '.date("D d.m.Y H:i",($this->timeStop));
				}
			else
				{
				SetValue($counterID,$scene["EVENT_DURATION"]);
				$this->setEventTimer($scene["NAME"],$scene["EVENT_DURATION"]*60,$command);
                $text.=' Timer gesetzt auf '.date("D d.m.Y H:i",($this->now+$scene["EVENT_DURATION"]*60));
				}
			$this->log->LogMessage('Befehl aktiv, '.$text);
            }
        else
            { 
            /* IPS_Light/Heat ausschalten. */
			SetValue($statusID,false);                             
			if (isset($scene["EVENT_IPSLIGHT"]))
				{
				$text.='IPSLight Switch '.$scene["EVENT_IPSLIGHT"].' ausgeschaltet.';
				$this->log->LogMessage($text.json_encode($scene));
				IPSLight_SetSwitchByName($scene["EVENT_IPSLIGHT"], false);
				}
			elseif (isset($scene["EVENT_IPSLIGHT_GRP"]))
                {
                $text.='IPSLight Group '.$scene["EVENT_IPSLIGHT_GRP"].'ausgeschaltet.';								
                $log_Autosteuerung->LogMessage($text.json_encode($scene));								
                IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], false);
                }
			elseif (isset($scene["EVENT_IPSHEAT"]))
				{
				$text.='IPSHeat Switch '.$scene["EVENT_IPSHEAT"].' ausgeschaltet.';
				$this->log->LogMessage($text.json_encode($scene));
				IPSHeat_SetSwitchByName($scene["EVENT_IPSHEAT"], false);
				}
			elseif (isset($scene["EVENT_IPSHEAT_GRP"]))
                {
                $text.='IPSHeat Group '.$scene["EVENT_IPSHEAT_GRP"].'ausgeschaltet.';								
                $log_Autosteuerung->LogMessage($text.json_encode($scene));								
                IPSLight_SetGroupByName($scene["EVENT_IPSHEAT_GRP"], false);
                }
			//SetValue($StatusAnwesendZuletztID,false);	
            }
        if ($status) return("");            // Logging reduzoeren, nicht alles aufzeichnen
        else return($text);
        }   

	/* einen Timer anlegen und setzen, ist für ein einmaliges Event */
    
    function setEventTimer($name,$delay,$command)
	    {
        $timerOps = new TimerOps();
        $timerOps->setEventTimer($name,$delay,$command,$this->CategoryIdApp);
    	/*
        $now = time();
    	$EreignisID = $this->getEventTimerID($name."_EVENT");
    	IPS_SetEventActive($EreignisID,true);
	    IPS_SetEventCyclic($EreignisID, 1, 0, 0, 0, 0,0);
    	// EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit 
	    // EreignisID, 1 einmalig,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit 
    	IPS_SetEventCyclicTimeBounds($EreignisID,$now+$delay,0);
	    IPS_SetEventCyclicDateBounds($EreignisID,$now+$delay,0);
    	IPS_SetEventScript($EreignisID,$command);*/
	    }

	/* einen zyklischen Timer anlegen und setzen, ist für ein einmaliges Event 
     */

	function setDimTimer($name,$delay,$command)
		{
        $timerOps = new TimerOps();
        $result = $timerOps->setDimTimer($name,$delay,$command,$this->CategoryIdApp);
        /*
  		$now = time();
		$EreignisID = $this->getEventTimerID($name."_EVENT_DIM");
   		IPS_SetEventActive($EreignisID,true);
   		IPS_SetEventCyclic($EreignisID, 0, 0, 0, 0, 1, $delay);
		// EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 1 Sekuendlich,  Anzahl Sekunden 
		// EreignisID, 0 kein Datumstyp:  tägliche Ausführung,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit 
		// EreignisID, 1 einmalig,0 keine Auswertung, 0 keine Auswertung, 0 keine Auswertung, 0 Einmalig IPS_SetEventCyclicTimeBounds für Zielzeit 
   		IPS_SetEventScript($EreignisID,$command);  */
		}

	/* für einen Timer Namen den aktuellen Status zurückmelden 
     *
     */
    function getEventTimerStatus($name)
	    {
        $timerOps = new TimerOps();
        $result = $timerOps->getEventTimerStatus($name,$this->CategoryIdApp);	
        /*
    	$EreignisID = $this->getEventTimerID($name);
        //echo "Timer ID : ".$EreignisID."   (".IPS_GetName($EreignisID)."/".IPS_GetName(IPS_GetParent($EreignisID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($EreignisID))).")\n";
        $status=IPS_GetEvent($EreignisID);
        //print_r($status);
        //echo $status["EventActive"]."   ".date("Y-m-d H:i:s",$targetTime)."   ".$status["CyclicDateFrom"]["Day"].".".$status["CyclicDateFrom"]["Month"].".".$status["CyclicDateFrom"]["Year"]." ".$status["CyclicTimeFrom"]["Hour"].":".$status["CyclicTimeFrom"]["Minute"].":".$status["CyclicTimeFrom"]["Second"]."\n";
        $targetTime=strtotime($status["CyclicDateFrom"]["Day"].".".$status["CyclicDateFrom"]["Month"].".".$status["CyclicDateFrom"]["Year"]." ".$status["CyclicTimeFrom"]["Hour"]
            .":".$status["CyclicTimeFrom"]["Minute"].":".$status["CyclicTimeFrom"]["Second"]);
        if ( ($status["EventActive"]==true) && (time()<=$targetTime) ) $result=true;
        else $result=false;    */
        return($result);
        }

	/* für einen Timer Namen die ID zurückgeben, wenn die ID noch nocht bekannt ist den timer zumindest dem Namen nach anlegen */
    function getEventTimerID($name)
	    {
        $timerOps = new TimerOps();
        $EreignisID = $timerOps->getEventTimerID($name,$this->CategoryIdApp);	
        /*
	    $EreignisID = @IPS_GetEventIDByName($name,  $this->CategoryIdApp);
    	if ($EreignisID === false)
	    	{ //Event nicht gefunden > neu anlegen
		    $EreignisID = IPS_CreateEvent(1);
    		IPS_SetName($EreignisID,$name);
	    	IPS_SetParent($EreignisID, $this->CategoryIdApp);
		    }  */
		return($EreignisID);
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
 * die AbstractClass verwendet ihr eigenes Logging, InitMesagePuffer wird von der benutzenden Klasse bereitgestellt
 * verwendet von:
 *       AutosteuerungRegler
 *       AutosteuerungAnwesenheitsSimulation
 *       AutosteuerungAlexa
 *       AutoSteuerungStromheizung für Funktionen rund um die Heizungssteuerung 
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
	

    /* Konfigurationsmanagement , Abstraktion mit set und get im AutosteuerungHandler */

    protected function set_Configuration()
        {
        $autosteuerungHandler = new AutosteuerungHandler();         // nur zum Configuration einlesen anlegen
        $setup = $autosteuerungHandler->get_Configuration();
        //echo "Aufruf von ".get_class()."::set_Configuration().\n"; print_R($setup);
        return ($setup);
        }

    public function get_Configuration()
        {
        return ($this->configuration);
        }

	function Init()     //class AutosteuerungFunktionen
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
		
	function InitLogMessage($debug=false)           //class AutosteuerungFunktionen
		{
		if ($debug) echo "Initialisierung ".get_class($this)." mit Logfile: ".$this->log_File." mit Meldungsspeicher: ".$this->script_Id." \n";
        $logging = new Logging();	
        $log_ConfigFile = $logging -> get_IPSComponentLoggerConfig();	
		//var_dump($this);
		if ($this->log_File=="No-Output")
			{
			if ($debug) 
                {
                echo "   kein Logfile anlegen.\n";
                print_R($log_ConfigFile);
                }
			}
		else
			{
			if (!file_exists($this->log_File))
				{
				/* Pfad aus dem Dateinamen herausrechnen. Wenn keiner definiert ist einen aus dem Configfile nehmen und sonst mit Default arbeiten */
				//echo "construct class Anwesenheitssimulation, File ".$this->logfile." existiert nicht. Mit Verzeichnis gemeinsam anlegen.\n";
				$FilePath = pathinfo($this->log_File, PATHINFO_DIRNAME);
				if ($debug) echo "Verzeichnis für Logfile : ".PATHINFO_DIRNAME.$FilePath."\n";				
				if ($FilePath==".") 
					{
					//echo "Es existiert kein Filepath, einen aus der config nehmen oder annehmen und hinzufügen\n";
					//print_r($log_ConfigFile);
					$FilePath=$log_ConfigFile["LogDirectories"]["AnwesenheitssimulationLog"];
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

    /* verweist auf den jeweiligen InitMesagePuffer 
     */

	function InitLogNachrichten($type,$profile)
		{
		$this->InitMesagePuffer($type,$profile);                // momentan nur durchreichen 
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
		
    /* kein Logging wenn nicht html und nachrichteninput_Id == Ohne
     */

	function LogNachrichten($message)
		{
        echo "LogNachrichten ".json_encode($this->config)." Nachricht \"$message\"";
        if ($this->config["HTMLOutput"])
            {
            echo "  Ausgabe als html\n";
            $sumTableID = $this->config["sumTableID"]; 
            if ($sumTableID===false) echo "LogNachrichten, Fehler  InputID $sumTableID nicht definiert.\n";
            else
                {
                if ($this->config["storeTableID"])
                    {
                    $messages = json_decode(GetValue($this->config["storeTableID"]),true);
                    $messages[time()]=$message;
                    krsort($messages);
                    if (count($messages)>50)
                        {
                        end( $messages );
                        $key = key( $messages );
                        unset ($messages[$key]);
                        }
                    SetValue($this->config["storeTableID"],json_encode($messages));
                    }    
                SetValue($sumTableID,$this->PrintNachrichten(true));
                }
            }   
        else
            {   
            echo "  Ausgabe als array of lines\n";         		
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
		}

    /* Ausgeb des Logspeichers */

	function PrintNachrichten($html=false)
		{
		$result=false;
        $PrintHtml="";
        $PrintHtml.='<style>';             
        $PrintHtml.='.messagy table,td {align:center;border:1px solid white;border-collapse:collapse;}';
        $PrintHtml.='.messagy table    {table-layout: fixed; width: 100%; }';
        $PrintHtml.='.messagy td:nth-child(1) { width: 30%; }';
        $PrintHtml.='.messagy td:nth-child(2) { width: 70%; }';
        $PrintHtml.='</style>';        
        $PrintHtml.='<table class="messagy">';
        if ($this->config["HTMLOutput"] && $this->config["storeTableID"])
            {
            $messageJson=GetValue($this->config["storeTableID"]);
            $messages = json_decode($messageJson,true);
            //IPSLogger_Inf(__file__, "Logging:PrintNachrichten ".$messageJson."   ".$this->log_File."   ".$this->zeile1);
            $PrintHtml .= '<tr><td>Date</td><td>Message</td></tr>';
            if (is_array($messages))
                {
                if (count($messages)>0) 
                    {
                    foreach ($messages as $timeIndex => $message)
                        {
                        $PrintHtml .= '<tr><td>'.date("d.m H:i:s",$timeIndex).'</td><td>'.$message.'</td></tr>';
                        }
                    }
                }
            }  
		elseif ($this->nachrichteninput_Id != "Ohne")
		    {
            $result="";
            $count=sizeof($this->zeile);
            if ($count>1)
                {
                for ($i=1;$i<=$count;$i++)
                    {
                    $result    .= GetValue($this->zeile[$i])."\n";
                    //$PrintHtml .= '<tr><td>'.str_pad($i, 2 ,'0', STR_PAD_LEFT).'</td><td>'.GetValue($this->zeile[$i]).'</td></tr>';
                    $PrintHtml .= '<tr><td>'.GetValue($this->zeile[$i]).'</td></tr>';
                    }
                }
            else $result=GetValue($this->zeile1)."\n".GetValue($this->zeile2)."\n".GetValue($this->zeile3)."\n".GetValue($this->zeile4)."\n".GetValue($this->zeile5)."\n".GetValue($this->zeile6)."\n".GetValue($this->zeile7)."\n".GetValue($this->zeile8)."\n".GetValue($this->zeile9)."\n".GetValue($this->zeile10)."\n".GetValue($this->zeile11)."\n".GetValue($this->zeile12)."\n".GetValue($this->zeile13)."\n".GetValue($this->zeile14)."\n".GetValue($this->zeile15)."\n".GetValue($this->zeile16)."\n";
			}
        $PrintHtml.='</table>';        

		if ($html) return ($PrintHtml);
        else return $result;
		}

	/*function PrintNachrichten()
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
		}*/

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

    protected $configuration;                   // Configuration from Config File
    protected $config=array();                  // internal configuration

    protected $htmlLogging=true;

    /* construct 
     */

	public function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
		{
		//echo "Logfile Construct\n";
        $this->configuration = $this->set_Configuration();
		
		/******************************* Init *********/
		$this->log_File=$logfile;
		$this->nachrichteninput_Id=$nachrichteninput_Id;			
		$this->Init();			/* Autosteuerung_HeatControl Script ID wird festgelegt in $this->scriptIdHeatControl */

		/******************************* File Logging *********/
		
		$this->InitLogMessage();		/* Filename festlegen für Logging in einem externem File */
		
		/******************************* Nachrichten Logging *********/
		
		$type=3;$profile=""; $this->zeile=array();
        $this->config["HTMLOutput"]=$this->htmlLogging;        
		$this->InitLogNachrichten($type,$profile);		/*  ruft das Geraete spezifische InitMesagePuffer() auf, logging in Objekten mit String und ohne Profil festlegen, keien Abstrkte Routine, auf jeden Fall programmieren */
		}

	function WriteLink($i,$type,$vid,$profile,$scriptIdHeatControl)
		{
        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
		$this->zeile[$i] = CreateVariableByName($vid,"Zeile".$i,$type,$profile,"",$i*10,$scriptIdHeatControl);
		}

	function InitMesagePuffer($type=3,$profile="")              // AutosteuerungRegler
		{		
		if ($this->nachrichteninput_Id != "Ohne")
			{
			// bei etwas anderem als einem String stimmt der defaultwert nicht
			$vid=@IPS_GetObjectIDByName("ReglerAktionen",$this->nachrichteninput_Id);
			if ($vid===false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung Regleraktionen InitMessagePuffer, keine Kategorie"ReglerAktionene in '.$this->nachrichteninput_Id);
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
			}

      if ($vid)
            {
            if ($this->config["HTMLOutput"])
                {
                //echo "AutosteuerungAnwesenheitssimulation::InitMesagePuffer, Init Html Message buffer\n";
                $sumTableID = CreateVariable("MessageTable", 3,  $vid, 900 , '~HTMLBox',null,null,""); // obige Informationen als kleine Tabelle erstellen
                $storeTableID = CreateVariable("MessageStorage", 3,  $vid, 910 , '',null,null,""); // die Tabelle in einem größerem Umfeld speichern
                IPS_SetHidden($storeTableID,true);                    // Nachrichtenarray nicht anzeigen
                $this->config["storeTableID"]=$storeTableID;
                $this->config["sumTableID"]=$sumTableID;
                $this->config["nachrichteninput_Id"]=$vid;
                SetValue($sumTableID,$this->PrintNachrichten(true));            // true für htmlOutput
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

    protected $configuration;                   // Configuration from Config File
    protected $config=array();                  // internal configuration

    protected $htmlLogging=true;

    /**************************/

	public function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
		{
		//echo "Logfile Construct\n";
        $this->configuration = $this->set_Configuration();
		
		/******************************* Init *********/
		$this->log_File=$logfile;
		$this->nachrichteninput_Id=$nachrichteninput_Id;			
		$this->Init();      /* Autosteuerung_HeatControl Script ID wird festgelegt in $this->scriptIdHeatControl */

		/******************************* File Logging *********/
		$this->InitLogMessage();
		
		/******************************* Nachrichten Logging *********/
		$type=3;$profile=""; $this->zeile=array();
        $this->config["HTMLOutput"]=$this->htmlLogging;
		$this->InitLogNachrichten($type,$profile);      /*  ruft das Geraete spezifische InitMesagePuffer() auf */
		}

	function WriteLink($i,$type,$vid,$profile,$scriptIdHeatControl)
		{
        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
		$this->zeile[$i] = CreateVariableByName($vid,"Zeile".$i,$type,$profile,"",$i*10,$scriptIdHeatControl);
		}

	function InitMesagePuffer($type=3,$profile="")                  // AutosteuerungAnwesenheitssimulation
		{
        //echo "AutosteuerungAnwesenheitssimulation::InitMesagePuffer\n";		
		if ($this->nachrichteninput_Id != "Ohne")
			{
			// bei etwas anderem als einem String stimmt der defaultwert nicht
			$vid=@IPS_GetObjectIDByName("Schaltbefehle",$this->nachrichteninput_Id);
			if ($vid===false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung Anwesenheitssimulation InitMessagePuffer, keine Kategorie Schaltbefehle in '.$this->nachrichteninput_Id);
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
			}

        if ($vid)
            {
            if ($this->config["HTMLOutput"])
                {
                //echo "AutosteuerungAnwesenheitssimulation::InitMesagePuffer, Init Html Message buffer\n";
                $sumTableID = CreateVariable("MessageTable", 3,  $vid, 900 , '~HTMLBox',null,null,""); // obige Informationen als kleine Tabelle erstellen
                $storeTableID = CreateVariable("MessageStorage", 3,  $vid, 910 , '',null,null,""); // die Tabelle in einem größerem Umfeld speichern
                IPS_SetHidden($storeTableID,true);                    // Nachrichtenarray nicht anzeigen
                $this->config["storeTableID"]=$storeTableID;
                $this->config["sumTableID"]=$sumTableID;
                $this->config["nachrichteninput_Id"]=$vid;
                SetValue($sumTableID,$this->PrintNachrichten(true));            // true für htmlOutput
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

    protected $configuration;                   // Configuration from Config File
    protected $config=array();                  // internal configuration

    protected $htmlLogging=true;
    
    /**************************/

	public function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
		{
		//echo "Logfile Construct\n";
        $this->configuration = $this->set_Configuration();
		
		/******************************* Init *********/
		$this->log_File=$logfile;
		$this->nachrichteninput_Id=$nachrichteninput_Id;			
		$this->Init();      /* Autosteuerung_HeatControl Script ID wird festgelegt in $this->scriptIdHeatControl */

		/******************************* File Logging *********/
		$this->InitLogMessage();
		
		/******************************* Nachrichten Logging *********/
		$type=3;$profile=""; $this->zeile=array();
        $this->config["HTMLOutput"]=$this->htmlLogging;        
		$this->InitLogNachrichten($type,$profile);          /*  ruft das Geraete spezifische InitMesagePuffer() auf */
		}

	function WriteLink($i,$type,$vid,$profile,$scriptIdHeatControl)
		{
        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
		$this->zeile[$i] = CreateVariableByName($vid,"Zeile".$i,$type,$profile,"",$i*10,$scriptIdHeatControl);
		}

	function InitMesagePuffer($type=3,$profile="")
		{		
		if ($this->nachrichteninput_Id != "Ohne")
			{
			// bei etwas anderem als einem String stimmt der defaultwert nicht
			$vid=@IPS_GetObjectIDByName("Nachrichten",$this->nachrichteninput_Id);	/* <<<<<<< change here */
			if ($vid===false) 
				{
				IPSLogger_Dbg (__file__, '*** Fehler: Autosteuerung Anwesenheitssimulation InitMessagePuffer, keine Kategorie Schaltbefehle in '.$this->nachrichteninput_Id);
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
			}
        if ($vid)
            {
            if ($this->config["HTMLOutput"])
                {
                //echo "AutosteuerungAnwesenheitssimulation::InitMesagePuffer, Init Html Message buffer\n";
                $sumTableID = CreateVariable("MessageTable", 3,  $vid, 900 , '~HTMLBox',null,null,""); // obige Informationen als kleine Tabelle erstellen
                $storeTableID = CreateVariable("MessageStorage", 3,  $vid, 910 , '',null,null,""); // die Tabelle in einem größerem Umfeld speichern
                IPS_SetHidden($storeTableID,true);                    // Nachrichtenarray nicht anzeigen
                $this->config["storeTableID"]=$storeTableID;
                $this->config["sumTableID"]=$sumTableID;
                $this->config["nachrichteninput_Id"]=$vid;
                SetValue($sumTableID,$this->PrintNachrichten(true));            // true für htmlOutput
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
 * zusaetzlich wird auch in Files gelogged. Hier werden beim Aufruf die Logging und Nachrichtenspeicher Default genutzt.
 * Default bedeutet ohne
 *
 * es werden statt Nachrichten ein Kalendar dargestellt
 * im construct muss Init() aufgerufen werden das eigentlich nur $this->scriptIdHeatControl bestimmt.
 * dann InitLogMessage() dass bei Defaultwert kein Logfile anlegt
 * dann InitLogNachrichten das InitMesagePuffer($type,$profile) aufruft.
 *
 *  __construct
 *  getStatus
 *  getCategoryIdTab
 *  WriteLink, CreateLink, UpdateLinks
 *  getWochenplanID, getZeile1ID, getAutoFillID, writeWochenplan
 *  InitMesagePuffer
 *  ShiftforNextDay
 *  setAutoFill
 *  getStatusfromProfile
 *  EvaluateAutoStatus
 *  feiertag
 *  SetupKalender
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
    protected $categoryIdTab;
	
    protected $configuration;                   // Configuration from Config File

    /**************************/

	public function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne")
		{
		//echo "Logfile Construct\n";
        $debug=false;
        $this->configuration = $this->set_Configuration();
		
		/******************************* Init *********/
		$this->log_File=$logfile;
		$this->nachrichteninput_Id=$nachrichteninput_Id;			
		$this->Init();          //class AutosteuerungFunktionen, Autosteuerung_HeatControl Script ID wird festgelegt in $this->scriptIdHeatControl, Category Path entspechend config.ini angelegt 

		/******************************* File Logging *********/
		$this->InitLogMessage($debug);              // tut nix wenn $logfile="No-Output"
		
		/******************************* Nachrichten Logging *********/
		$this->zeile=array();		
		//$type=1;$profile="AusEin"; 		/* Umstellen auf Boolean und Standard Profil für bessere Darstellung Mobile Frontend*/
		$type=0;$profile="~Switch";
		$this->InitLogNachrichten($type,$profile);          	/*  nur durchreichen, ruft das Geraete spezifische InitMesagePuffer() in dieser class auf */

        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        if (!isset($moduleManager)) 
            {
            IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
            $moduleManager = new IPSModuleManager('Autosteuerung',$repository);
            }
        $Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
        if ($Mobile_Enabled==true)
            {	
            $Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
            }
        $this->categoryIdTab         = CreateCategoryPath($Mobile_Path.".Stromheizung");

		}

    /* rausfinden wie AutosteuerungStromheizung konfiguriert wurde */

    function getStatus()
        {
        echo "Logfile ist hier abgelegt : ".$this->log_File."\n";    
        }

	function getCategoryIdTab()
		{	
		return($this->categoryIdTab);
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
        // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)
		$this->zeile[$i] = CreateVariableByName($vid,"Zeile".$i,$type,$profile,"",$i*10,$scriptIdHeatControl);
		}

	function CreateLink($i,$sourceCategory,$linkCategory)
		{
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
	
	function getZeile1ID()
		{	
		return(@IPS_GetObjectIDByName("Zeile1",$this->nachrichteninput_Id));
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
     * in construct ist es $type=0;$profile="~Switch";
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
			echo "Fatal Error, Object Wochenplan not found in Category Wochenplan-Stromheizung (".$this->nachrichteninput_Id."/".$CategoryIdData.") !!!!\n";                  // was ist passiert ? 
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

    /* für die Stromheizung den Autofill Defaultwert einstellen. Es gibt verschiedene Betriebsarten. Nach einem Restart sind die alle wieder auf aus ?
     * unter der Einstell Liste gibt es eine kurze Erklärung was gerade passiert. In Autosteuerung HeatControl ist die Funktion für das Webfront und den TTiomer, jeden Tag um 00:10
     *
     */

    function setAutoFill($value, $shift=true, $debug=false)
        {
        if ($debug) echo "      setAutoFill($value) aufgerufen:\n";
        if (is_numeric($value)) $value=(string)$value;
        $oid=$this->getAutoFillID();		// OID von Profilvariable für Autofill        
        $descrID=IPS_GetVariableIDByName("Beschreibung",$this->getWochenplanID());		// OID von Profilvariable für Autofill
        $text="";
        //echo "Einstellung Werte $oid ".GetValue($oid)."  ".GetValueFormatted($oid)."  --> neuer Wert : ".$value."\n";
        switch ($value)
            {
            case "Aus":
            case "0":
                $finalValue=0;
                $text="Nächster Tag immer AUS";
                break;
            case "Ein":
            case "1":
                $finalValue=1;
                $text="Nächster Tag immer EIN";
                break;
            case "Auto":
            case "2":
                $finalValue=2;
                $text="Nächster Tag gleich wie die Woche davor";
                break;
            case "Profil 1":
            case "3":
                $finalValue=3;
                $text="An allen Freitagen auf EIN";
                break;
            case "Profil 2":
            case "4":
                $finalValue=4;
                $text="An allen Freitagen und einen Tag davor auf EIN";
                break;
            case "Profil 3":
            case "5":
                $finalValue=5;
                $text="An allen Freitagen und zwei Tage davor auf EIN";
                break;
            case "Profil 4":
            case "6":
                $finalValue=6;
                $text="An allen Arbeitstagen auf EIN";
                break;
            default:
                $finalValue=0;
            }    
        if ($shift && (GetValue($oid) == $finalValue)) 
            {
            if ($debug) echo "     Gleiche Funktion noch einmal gedrueckt.\n";   
            $oidWP=IPS_GetVariableIDByName("AutoFill",$this->getWochenplanID());		// OID von Profilvariable für Autofill
            $valueNext=$this->getStatusfromProfile(GetValue($oidWP));                   
            $this->ShiftforNextDay($valueNext);                                     /* die Werte im Wochenplan durchschieben, neuer Wert ist der Parameter, die Links heissen aber immer noch gleich */
            $this->UpdateLinks($this->getWochenplanID());                   /* Update Links für Administrator Webfront */
            $this->UpdateLinks($this->categoryIdTab);		                            /* Upodate Links for Mobility Webfront */
            }
        if ($debug) echo "      Es wird $value / $finalValue und \"$text\" geschrieben.\n";   
        SetValue($oid,$finalValue);
        SetValue($descrID,$text);
        }

	/* Es gibt aus, Ein und Profil 2 bis 5
	 *
	 * Profil 2: nur Freitage, wenn zu Hause
	 * Profil 3: nur Freitage plus 1, wenn zu Hause plus 1 Tag Vorwärmzeit
	 * Profil 4  nur Freitage plus 2, wenn zu Hause plus 2 Tag Vorwärmzeit
	 * Profil 5 nur Arbeitstage
	 */
	function getStatusfromProfile($profile=0,$day=0)
		{
		$status=false;

		$result0=$this->EvaluateAutoStatus($day);                           // 0 ist +16, -16 ist 0
		$result1=$this->EvaluateAutoStatus($day+1);
		$result2=$this->EvaluateAutoStatus($day+2);

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
	function EvaluateAutoStatus($time=0,$debug=false)
		{
		if ($debug) echo "    EvaluateAutoStatus mit Time $time:\n";
		$result=array();
		if ($time==0) $time=(time()+16*24*60*60);                               // 0 ist genau in 16 tage
		elseif ($time<15) $time=(time()+(16+$time)*24*60*60);                   // 1,2,3 ist in 17,18,19 Tagen, 16 ist in 16 Tagen
		$wochentag=date("D",$time);
		$feiertag=$this->feiertag($time,"W");		// gibt zurück Arbeitstag, Wochenende oder Name des Feiertages 
		if ($debug) echo "     ".date("D d.m.Y",$time)."    Wochentag: ".$wochentag."    Status: ".$feiertag."\n";
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
				if ($debug) echo "FEHLER: Wochentag nicht bekannt ...\n";
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
			/* Create Variable macht Probleme mit den Profilen. Besser createVariable2 oder gleich CreateVariableByName verwenden 
            function CreateVariable2($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault=null, $Icon='')
            function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)    */

			$this->zeile[$i] = CreateVariableByName($pvid,"Zeile".$i,$type, $profile,false,$i*10,$this->scriptIdHeatControl,0 );
			IPS_SetHidden($this->zeile[$i],true);
			$this->CreateLink($i,$pvid,$vid); 
			}
		//echo "\n";			
		}


	}       // ende class








/********************************************************************************************
 *
 *	Implementierte Applikationen:
 *
 *	Anwesenheit, iTunesSteuerung, GutenMorgenWecker, Status, StatusParallel, Ventilator2, Alexa
 *      SwitchFunction
 *  Ventilator1, Ventilator, StatusRGB sollte nicht mehr in Verwendung sein
 *
 ***************************************************************************************************/



function Anwesenheit($params,$status,$variableID,$simulate=false,$wertOpt="")
	{
	global $AnwesenheitssimulationID;

	IPSLogger_Inf(__file__, 'Aufruf Routine Anwesenheit mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
	$auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */
	//$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	$parges=$auto->ParseCommand($params,$status,$simulate);
	$command=array(); $entry=1;
	//print_r($parges);
	foreach ($parges as $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;		 /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, übernimmt den  Status beim Aufruf */
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
		$result=$auto->ExecuteCommand($command[$entry],$simulate);          // für Anwesenheit
		
		if (isset($result["DELAY"])==true)
			{
			if ($result["DELAY"]>0)
				{
				echo ">>>Ergebnis ExecuteCommand, DELAY.\n";			
				print_r($result);
				if ($simulate==false)
					{
					$auto->setEventTimer($result["NAME"],$result["DELAY"],$result["COMMAND"]);
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
 *	Neben Standardfunktionen wie in  Status Spezialisierung auf die Mediensteuerung mit Sondergeräten wie 
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
		$result=$auto->ExecuteCommand($command[$entry],$simulate);          // für iTunes
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
		$result=$auto->ExecuteCommand($command[$entry],$simulate);              // für Wecker
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
 *  Aufruf mit Status($params,$status,$variableID,$simulate=false,$wertOptInput="",$debug=false)
 *      params, die Konfiguration, der Befehl
 *      status, Wert des Events mit dem diese Routine aufgerufen wurde
 *      variableID, die ID der Variable die den Wert Status hält
 *      simulate, true wenn nur zu SImulationzwecken der Compiler geprüft wird
 *      wertOptInput (neu), Zusatzparameter neben  Status, Zb Aufruf mit Status+Nolog oder Status+Nolog+Bounces
 *      debug, Zusatzinfos bei simulate oder wenn direkt aufgerufen wird
 *
 * 	in Autosteurung, Autosteuerung_GetEventConfiguration() steht die Konfiguration. Aufgrund einer Variablenänderung
 *  oder einem Update der Ereignisvariable wird eine Applikation, wie diese oder eine Funktion aufgerufen.
 *	In der Applikation wird der letzte Parameter, die Kommandokette geparst und als Array gespeichert.
 * 	Die Kommandokette besteht aus mehreren Kommandos. Jedes Kommando wird dann noch Applikationsspezifisch ergänzt werden.
 *	Jedes Kommando besteht aus mehreren Befehlen.
 *
 *	Optioning -> Parse -> Evaluate -> Execute 
 *
 *      Beispiel für Optioning setNewStatusBounce($variableID,$status,$interval,$update);
 *      ParseCommand($params,$status,$simulate)
 *      für jeden Befehl innerhalb eines Kommandos EvaluateCommand($befehl,$command[$entry],$simulate)
 *      für jedes Kommando $result=ExecuteCommand($command[$entry],$simulate) und timerCommand($result,$simulate);
 *
 *  IpsLight Name und optional ob ein, aus und am Ende noch ein delay kann ohne Spezialbefehle eingegeben werden
 *
 * zum Beispiel:  'OnUpdate','Status','WohnzimmerKugellampe,toggle',
 *                 siehe auch Beispiele weiter unten 
 *
 *  komplizierte Algorithmen werden immer mit befehl:parameter eingegeben
 *
 *  OID:12345        Definition des zu schaltenden objektes
 *  NAME:Wohnzimmer  Definition des zu schaltenden IPSLight Schalters, Gruppe oder Programms, wird automatisch der Reihe nach auf
 *                       	Vorhandensein überprüft
 * 								Wenn keine Angabe wird der Status des Objektes (OnUpdate/OnChange) für den neuen Schaltzustand verwendet
 *
 *  DELAY:TIME      ein  timer wird aktiviert, nach Ablauf von TIME (in Sekunden) wird der Schalter ausgeschaltet
 *  ENVELOPE:TIME   ein  Statuswert wird so verschliffen, das nur selten tatsächlich der Schalter aktiviert wird
 *                      immer bei Wert 1 wird der timer neu aktiviert, ein Ablaufen des Timers führt zum Ausschalten
 *  MONITOR:ON|OFF|STATUS  die custom function monitorOnOff wird aufgerufen
 *  MUTE:ON|OFF|STATUS
 *
 * Beschreibung Optioning:
 *      evalWertOpt         speichert Ergebnis in array control 
 *            +log
 *            +note
 *
 *
 ************************************************************************************************/

function Status($params,$status,$variableID,$simulate=false,$wertOptInput="",$debug=false)
	{
	global $speak_config;

    if ($debug) echo "Aufruf status+$wertOptInput\n";
    $control=array();           // hier sind die Zusatzelemente gespeichert

    $auto=new Autosteuerung(); /* um Auto Klasse auch in der Funktion verwenden zu können */

    $exectime=hrtime(true)/1000000;

    $auto->evalWertOpt($control, $wertOptInput, $variableID, $status, $debug);

   /* bei einer Statusaenderung oder Aktualisierung einer Variable 																						*/
   /* array($params[0], $params[1],             $params[2],),                     										*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,false',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,true,20',),       														*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on:true,off:false,timer#dawn-23:45',),       			*/
   /* array('OnChange','Status',   'ArbeitszimmerLampe,on:true,off:false,if:light',),       				*/

    /* alten Wert der Variable ermitteln um den Unterschied erkennen, gleich, groesser, kleiner 
     * neuen Wert gleichzeitig schreiben
     */
    $oldValue=$auto->setNewStatus($variableID,$status);	                    // Category ist der dritte Parameter, hier default 0
    $command=array(); 
    $entry=1;	

    if ($control["bounce"]==false)   // bei einem Bounce die ganze Befehlsabarbeitung deaktivieren
        {
        if ($control["log"]) 
            {
            IPSLogger_Inf(__file__, 'Aufruf Routine Status von '.IPS_GetName($variableID).'('.$variableID.') mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
            //$auto->log->LogNachrichten('Aufruf Routine Status von '.IPS_GetName($variableID).'('.$variableID.') mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status);
            }
        if ($control["note"]) 
            {
            if (is_bool($status))   $auto->logHtml->LogNachrichten('Status+Note: '.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).' ('.$variableID.') : '.$params[0].' und Status '.($status?"Ein":"Aus")); 
            else                    $auto->logHtml->LogNachrichten('Status+Note: '.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).' ('.$variableID.') : '.$params[0].' und Wert '.$status); 
            }
        //$lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
        
        $parges=$auto->ParseCommand($params,$status,$simulate);
        
        /* nun sind jedem Parameter Befehle zugeordnet die nun abgearbeitet werden, Kommando fuer Kommando */
        //print_r($parges);
        foreach ($parges as $kom => $Kommando)
            {
            $command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
            $command[$entry]["STATUS"]=$status;	
            $command[$entry]["OLDSTATUS"]=$oldValue;			/* alter Wert, vor der Änderung */        
            $command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */	
        
            foreach ($Kommando as $num => $befehl)
                {
                //echo "      |".$num." : Bearbeite Befehl ".$befehl[0]."\n";
                switch (strtoupper($befehl[0]))
                    {
                    default:
                        $auto->EvaluateCommand($befehl,$command[$entry],$simulate,$debug);
                        //if ($debug) echo "       Evaluate Befehl Ergebnis : ".json_encode($command[$entry])."\n";
                        break;
                    }	
                } /* Ende foreach Befehl */
            if ($debug) echo "        EvaluateCommand abgeschlossen, Aufruf Befehl ExecuteCommand mit : ".json_encode($command[$entry])."\n";            
            $result=$auto->ExecuteCommand($command[$entry],$simulate,$debug);                                               // für Status
            $ergebnis=$auto->timerCommand($result,$simulate);
            //print_r($command[$entry]);
            $entry++;			
            } /* Ende foreach Kommando */
        unset ($auto);							/* Platz machen im Speicher */
        $exectime=round(hrtime(true)/1000000-$exectime,0);
        if ($control["log"]) IPSLogger_Inf(__file__, 'Aufruf Routine Status von '.IPS_GetName($variableID).'('.$variableID.') fertig. Ausführungszeit '.$exectime.' Millisekunden.');
        }
    else
        {           // bounce erkannt, Befehl ignorieren
        $command[$entry]["SWITCH"]=false;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
        $command[$entry]["STATUS"]=$status;	
        $command[$entry]["OLDSTATUS"]=$oldValue;			/* alter Wert, vor der Änderung */        
        $command[$entry]["SOURCEID"]=$variableID;			/* Variable ID des Wertes */
        $exectime=round(hrtime(true)/1000000-$exectime,0);
        if ($control["log"]) IPSLogger_Inf(__file__, 'Aufruf Routine Status von '.$variableID.' wegen Bounce ignoriert. Ausführungszeit '.$exectime.' Millisekunden.');
        }		
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

    /* alten Wert der Variable ermitteln um den Unterschied erkennen, gleich, groesser, kleiner 
	 * neuen Wert gleichzeitig schreiben
	 */
	$oldValue=$auto->setNewStatus($variableID,$status);	
	
    $lightManager = new IPSLight_Manager();  /* verwendet um OID von IPS Light Variablen herauszubekommen */
	
	$parges=$auto->ParseCommand($params,$status,$simulate);
	//print_r($parges);
	$command=array(); $entry=1;	
	foreach ($parges as $kom => $Kommando)
		{
		$command[$entry]["SWITCH"]=true;	  /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird geschaltet */
		$command[$entry]["STATUS"]=$status;	
		$command[$entry]["OLDSTATUS"]=$oldValue;			/* alter Wert, vor der Änderung */        
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
		if ($debug) echo "       Evaluate Befehl Ergebnis : ".json_encode($command[$entry])."\n\n";			
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
		$command[$entry]["STATUS"]=$status;		 /* versteckter Befehl, wird in der Kommandozeile nicht verwendet, default bedeutet es wird auf true geschaltet */
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
		$result=$auto->ExecuteCommand($command[$entry],$simulate);          // für StatusRGB
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

    $debug=false;
	
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

	/* alten Wert der Trigger Variable ermitteln um den Unterschied erkennen, gleich, groesser, kleiner 
	 * neuen Wert gleichzeitig schreiben
	 */
	$oldValue=$auto->setNewValue($variableID,$status);	
	if ($wertOpt=="") 
        {
        if ($debug) IPSLogger_Inf(__file__, 'Aufruf Routine Heatcontrol mit Befehlsgruppe : '.$params[0]." ".$params[1]." ".$params[2].' und Status '.$status.' der Variable '.$variableID.' alter Wert war : '.$oldValue);
        }
	
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
		
        if (isset($command[$entry]["COMMENT"]))         // wenn keine Schaltbefehler getätigt werden keinen Logeintrag machen, wird dadurch übrsichtlicher
            {
            if ($simulate) echo $command[$entry]["COMMENT"]."\n";
            else 
                {
                if (strlen($command[$entry]["COMMENT"])>4) $nachrichtenVent->LogNachrichten(substr($command[$entry]["COMMENT"],0,100));
                if (strlen($command[$entry]["COMMENT"])>104) $nachrichtenVent->LogNachrichten(substr($command[$entry]["COMMENT"],100));
                }
            //if (strlen($command[$entry]["COMMENT"])>4) $log_Autosteuerung->LogNachrichten(substr($command[$entry]["COMMENT"],0,100));
            //if (strlen($command[$entry]["COMMENT"])>140) $log_Autosteuerung->LogNachrichten(substr($command[$entry]["COMMENT"],140));	
            }

		//$log_Autosteuerung->LogNachrichten('Variablenaenderung von '.$variableID.' ('.IPS_GetName($variableID).'/'.IPS_GetName(IPS_GetParent($variableID)).').'.$result);
					
		$result=$auto->ExecuteCommand($command[$entry],$simulate);          // für Ventilator2
		
		if ($simulate) echo $result["COMMENT"]."\n";
		//else $nachrichtenVent->LogNachrichten("Erg: ".$result["COMMENT"]);
        $ergebnis=$auto->timerCommand($result,$simulate);		
		//print_r($command[$entry]);	
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
    $debug=false;
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
		$auto->ControlSwitchLevel($command[$entry],$simulate,$debug);	
		
        if (isset($command[$entry]["COMMENT"]))         // wenn keine Schaltbefehle getätigt werden keinen Logeintrag machen, wird dadurch übersichtlicher
            {
            if ($simulate) echo "Bislang erhaltene Kommentare : ".$command[$entry]["COMMENT"]."\n";
            else 
                {
                if (strlen($command[$entry]["COMMENT"])>4) $nachrichtenVent->LogNachrichten("Com: ".substr($command[$entry]["COMMENT"],0,100));
                if (strlen($command[$entry]["COMMENT"])>104) $nachrichtenVent->LogNachrichten("Com: ".substr($command[$entry]["COMMENT"],100));
                }
            }

		$result=$auto->ExecuteCommand($command[$entry],$simulate);          // für Alexa
		
		if ($simulate) echo "Bislang erhaltene Kommentare : ".$result["COMMENT"]."\n";
        $ergebnis=$auto->timerCommand($result,$simulate);
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
 * Zuordnung webfrontLink in einer Zeile, erweitert Konfiguration in Autosteuerung_SetSwitches
 * wenn in AutosetSwitches OWNTAB konfiguriert ist wird ein eigener Subtab mit dem A´Namen angelegt. Sonst bleibt die Darstellung im Autosteuerung Tab untereinander angeordnet
 *
 *
 * es werden $webfront_link["TAB"] und $webfront_link["TABNAME"] beschrieben
 * es bleibt bei TAB Autosteurung wenn kein OWNTAB definiert ist.
 * bei OWNTAB wird TAB auf einen neuen eigenen TAB gesetzt und ein neuer Name dafür definiert, sonst wird der per Default übergebene verwednet
 *
 ***************************************************************************/


function defineWebfrontLink($AutoSetSwitch, $default="Default")
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






?>