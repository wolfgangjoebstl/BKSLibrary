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

    /*********************
     *
     * Detect Movement Library
     * Weiterverarbeitung und Auswertung von Events aller Art, begonnen wurde mit Motion/Movement Auswertungen deshalb der Name
     * Folgende Klassen stehen zur Verfügung:
     *
     * abstract class DetectHandler
     *      DetectSensorHandler extends DetectHandler            allgemeine Bearbeitung Sensor
     *      DetectCounterHandler extends DetectHandler            allgemeine Bearbeitung Counter
     *      DetectClimateHandler extends DetectHandler            
     *      DetectHumidityHandler extends DetectHandler
     *      DetectMovementHandler extends DetectHandler
     *      DetectBrightnessHandler extends DetectHandler
     *      DetectContactHandler extends DetectHandler
     *      DetectTemperatureHandler extends DetectHandler
     *      DetectHeatControlHandler extends DetectHandler
     *      DetectHeatSetHandler extends DetectHandler
     *
     *      DetectHandlerTopology extends DetectHandler 
     *          DetectDeviceHandler extends DetectHandlerTopology, writes function IPSDetectDeviceHandler_GetEventConfiguration in EvaluateHardware_Configuration.inc.php	 
     *          DetectDeviceListHandler extends DetectHandlerTopology, writes function IPSDetectDeviceListHandler_GetEventConfiguration in EvaluateHardware_Configuration.inc.php
     *
     *      TopologyManagement extends  DetectDeviceHandler extends DetectHandlerTopology extends DetectHandler
     *          variable topology is centered, create, process, write, unification
     *
     * TestMovement, ausarbeiten einer aussagekraeftigen Tabelle basierend auf den Event des MessageHandlers
     *
     * DetectHandler provides the following functions as abstract class
     *  __construct
	 *	StoreEventConfiguration             Store config as php function in the file, the file and function name are given in the final class
	 *	CreateEvents                        Erzeugt anhand der Konfiguration alle Events, ruft CreateEvent auf
	 *	ListEvents                          Listet anhand der Konfiguration alle Events als array
	 *	ListGroups                          Listet anhand der Konfiguration alle Gruppen. Das ist der zweite Parameter in der Configuration.
     *  ListConfigurations                  Liest die ganze Konfiguration eines Events aus. Wertet auch die mirror Register config im par3 aus
     *  getMirrorRegisterNameFromConfig     liest die configuration des Events aus. Wenn ein Mirror Index vorkommt, diesen ausgeben.
     *  getMirrorRegisterName               ermittelt den Namen des Spiegelregister für eine Variable oder eine Variable eines Gerätes
	 *	CreateEvent                         hier nur mehr ein check, im MessageHandler wird tatsächlich ein Event kreiert
	 *	sortEventList
	 *	registerEvent
	 *  Print_EventConfigurationAuto
	 *
	 * Zur Funktion
     *      der Messagehandler verwendet die MessageHandler Konfiguration, damit weiss er welchen Component er mit welchen Parametern aufrufen soll.
     *      anhand der Parameter werden die Funktionen die bei jeder Änderung erforderlich sind festgelegt.
     *      darunter befinden sich auch Funktionen, die wenn dieses Modul installiert ist, durchgeführt werden. Hier sind Funktionen zusammengestellt, die 
     *      Gruppen von Registern betreffen und neue Datenobjekte schaffen. Ähnlich wie die Components gibt es hier unterschiedliche Klassen die je nach Typue
     *      unterscheiedliche Funktionen unterstützen.
     *
     * die Funktionen für den MessageHandler stehen in IPSMessageHandler_GetEventConfiguration() beispielsweise so:
     *          552x4 => array('OnChange','IPSComponentSensor_Temperatur,,LBG70-2Virt:18579;,TEMPERATUR','IPSModuleSensor_Temperatur',),
     * die Funktionen für DetectMovement stehen in eigenen Konfigurationen
     *          170x6 => array('Temperatur','Wohnung','',),   //Temperatur  Toilette Temperature
     *
     * InstallComponentFull registriert die Events basierend auf Filter von Einträgen in der DeviceList
     *          Ausnahme AMIS, da erfolgt sie über AMIS_Installation und EvaluateHardware_Stromverbrauch, 
     * Register werden angelegt und im Messagehandler_Configuration in der function IPSMessageHandler_GetEventConfiguration() registriert
     *
     * Die Routine wird vom RemoteAccess Module mit den functions Evaluate_xxxx aufgerufen, damit werden neu erkannte Geräte auch gleich angelegt 
	 * RemoteAccess deshalb damit auch entfernte Server aktualisierte Informationen über den Zustand bekommen können.
     *
     * EvaluateHardware erstellt die DeviceListe und ruft auch die DetectHandler auf um die Konfigurationen für DetectMovementLib zu erstellen
     *
     * Da Events auch verortet werden müssen gibt es auch eine Topology in die die Datenregister die von den Cmponents und den DetectDeviceHandlern erzeugt werden verlinkt werden können
     *
     * Die entfernten logging Server sind in RemoteAccess_GetServerConfig() definiert. Der Key ist das Kurzzeichen in den Component Parametern
				"LBG70-2Virt"        		=> 	array(
                            "ADRESSE"   	=> 	'http://wolfgangjoebstl@yahoo.com:password@100.66.204.72:3777/api/',
							"STATUS"		=>		'Active',
							"LOGGING"		=>		'Enabled',
     * Daraus wird basierend auf der Erreichbarkeit eine ROID_List erstellt.
     *
     * Normalerweise haben bereits die Component Datenregister die Verbindung zu den externen Logging Servern.
     * Bei Evaluate_Stromverbrach gibt es keine Logging Server Installation
     *
     **********************************************/

    IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    /*******************************************************************
	 *
     * abstract class DetectHandler als Abbild für unter anderem die folgenden classes  
	 *
     * DetectDeviceHandler, DetectDeviceListHandler
     *
     * DetectSensorHandler          $eventSensorConfiguration       Sensor-Auswertung
     * DetectClimateHandler         $eventClimateConfiguration      Climate-Auswertung
     * DetectHumidityHandler        $eventHumidityConfiguration     Feuchtigkeit-Auswertung
     * DetectMovementHandler        $eventMoveConfiguration         Bewegung-Auswertung
     * DetectBrightnessHandler      $eventBrightnessConfiguration   Helligkeit-Auswertung
     * DetectContactHandler         $eventContactConfiguration      Kontakt-Auswertung
     * DetectTemperatureHandler     $eventTempConfiguration         Temperatur-Auswertung
     * DetectHeatControlHandler     $eventHeatConfiguration         HeatControl-Auswertung
     * DetectHeatSetHandler
     * 
     * gemeinsame Routinen, die natürlich auch überschrieben werden können
     *
     * Set_EventConfigurationAuto, Get_EventConfigurationAuto        self::$eventConfigurationAuto setzen, lesen
     *
     *
     *      __construct
     *      getDetectDataID                     Detect_DataID, das ist die Component Category aus CustomCoponent für die jeweilige Class
     *      cloneProfileandType                 clone profile and type from original variable Identifier variableID
     *      StoreEventConfiguration             Speichert die aktuelle Event Konfiguration im File, immer noch zumindest drei Parameter, können aber ach mehr als drei sein 
     *      insertNewFunction                   FileContent in eine Datei neu schreiben, gemeinsame function
     *      CreateEvents
     *      ListEvents
     *      ListGroups
     *      ListConfigurations
     *      getRoomNamefromConfig
     *      ** getMirrorRegister
     *      getMirrorRegisterNamefromConfig
     *      getMirrorRegisterName
     *      getMirrorRegisters
     *      checkMirrorRegisters
     *      CreateEvent
     *      sortEventList
     *      RegisterEvent
     *      Print_EventConfigurationAuto
     *      PrintEvent
     *
     *  Beispiel DetectClimateHandler:
     *      Get_Configtype
     *      Get_ConfigFileName
     *      Get_EventConfigurationAuto
     *      Set_EventConfigurationAuto
     *      getMirrorRegister
     *      getVariableName
     *      CreateMirrorRegister
     *      InitGroup
     *      CalcGroup     
     *
     **********************************************/

	abstract class DetectHandler 
        {
		/* von den extended Classes mindestens geforderte Funktionen */
		abstract function Get_Configtype();
		abstract function Get_ConfigFileName();		
				
		abstract function Get_EventConfigurationAuto();
		abstract function Set_EventConfigurationAuto($configuration);
		abstract function is_groupconfig();                                     // es gibt aiuch andere die die Funktionen nützen
		abstract function CreateMirrorRegister($variableId);

		protected $installedModules, $log_OperationCenter;

  		protected $Detect_DataID;												// Speicherort der Mirrorregister, von getMirrorRegisterName verwendet 
        protected $debug;
        protected $archiveHandlerID;
		
		/* DetectHandler::construct
         *
         * Initialisierung des DetectHandler Objektes, abstract class, this construc will be called after undividual one
		 *
		 */
		public function __construct($debug=false)
			{
            $this->debug=$debug;
			$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			if (!isset($moduleManager))
				{
				IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
				$moduleManager = new IPSModuleManager('OperationCenter',$repository);
				}
			$CategoryIdData=$moduleManager->GetModuleCategoryID('data');
			$this->installedModules = $moduleManager->GetInstalledModules();
			$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
			$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
			$this->log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
            $this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			}

        /* DetectHandler::getDetectDataID
         *
         * get protected Detect_DataID, das ist die Component Category aus CustomCoponent für die jeweilige Class
         *
         */
        public function getDetectDataID()
            {
            return ($this->Detect_DataID);
            }

        /* DetectHandler::cloneProfileandType
         *
         * clone profile and type from original variable Identifier variableID
         * output defines two different ways of returning the information 
         *      true        seperated by dot type . profile
         *      false       as array [type,profile]
         * type comes as readable string 
         */
        protected function cloneProfileandType($variableId,$output=false)
            {
            $variableProfile=IPS_GetVariable($variableId)["VariableProfile"];
            if ($variableProfile=="") $variableProfile=IPS_GetVariable($variableId)["VariableCustomProfile"];
            $type=IPS_GetVariable($variableId)["VariableType"];
            switch ($type)
                {
                case 0: $variableType="Boolean"; break;
                case 1: $variableType="Integer"; break;
                case 2: $variableType="Float"; break;
                case 3: $variableType="String"; break;
                }
            if ($output) return ($variableType.".".$variableProfile);
            //else return ([$variableType,$variableProfile]);
            else return ([$type,$variableProfile,$variableType]);                         // wird für CreateVariable verwendet
            }

		/* DetectHandler::StoreEventConfiguration
		 *
		 * Speichert die aktuelle Event Konfiguration im File, immer noch zumindest drei Parameter, können aber ach mehr als drei sein 
         * Routine schreibt in Dreierblöcken, der erste Block muss drei Parameter enthalten
         * unter function gibt es einen Kommentar über die letzte hinzugefügte Variable
		 * die Konfiguration ist einfach formattiert configuration as variableId=>params
         * VariableID ist die Variable, dann der Typ, die möglichen gruppen und verschiedene Parameter wie zB mirror
		 */
		function StoreEventConfiguration($configuration, $comment="",$debug=false)
			{
			//$configurationSort=$this->sortEventList($configuration);			
            //print_r($configuration);
			// Build Configuration String
            $count=0;$countmax=200;
			$configString = $this->Get_Configtype().' = array('.PHP_EOL;
            $configString .= chr(9).chr(9).chr(9).'/* This is the new comment area : '.$comment.' */';
			foreach ($configuration as $variableId=>$params) 
                {
                if (IPS_ObjectExists($variableId))
                    {                    
                    $configString .= PHP_EOL.chr(9).chr(9).chr(9).$variableId.' => ';
                    $configString .= $this->convertParams($params).'),';
                    /* $update=""; 
                    //echo "  $variableId  ".count($params)."\n";
                    for ($i=0; $i<count($params); $i=$i+3)          // schreibt in Dreierreihe, Infos sollten nicht verlorengehen
                        {
                        //if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';                // keine newline für die nächsten drei Parameter
                        if ($i>0)                                                                                   // nach den ersten 3 Parametern keine Limitierung, aber weiterhin Abarbeitung in Dreiergruppen
                            {
                            for ($j=0;(isset($params[$i+$j])&&($j<3));$j++) $update .= "'".$params[$i+$j]."',";             // Abbruch wenn Parameter nicht in einer Reihe
                            //if ($count++<$countmax) echo "  $i write more parameter $update ".json_encode($params)."\n";
                            }
                        else $update .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
                        }
                    $configString .= $update.'),'; */
                    $configString .= '   //'.IPS_GetName($variableId)."  ".IPS_GetName(IPS_GetParent($variableId));
                    }
			    }
			$configString .= PHP_EOL.chr(9).chr(9).chr(9).');'.PHP_EOL.PHP_EOL.chr(9).chr(9);

			// Write to File
			$fileNameFull = $this->Get_ConfigFileName();
			if (!file_exists($fileNameFull)) {
				throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);
			}
			$fileContent = file_get_contents($fileNameFull, true);
			$pos1 = strpos($fileContent, $this->Get_Configtype().' = array(');
			$pos2 = strpos($fileContent, 'return '.$this->Get_Configtype().';');

			if ($pos1 === false or $pos2 === false) 
				{
				$posn = $this->insertNewFunction($fileContent);

				if ( $posn === false )
					{
					throw new IPSMessageHandlerException('EventConfiguration File maybe empty !!!', E_USER_ERROR);
					}
				//echo $fileContent."\n  Position last } : ".$posn."   ".substr($fileContent, $posn,5)."\n";	
                $fullString  = "\n\n	 /*".get_class($this)."\n	  *\n	  *\n	  */\n";
				$fullString .= "	 function IPS".get_class($this)."_GetEventConfiguration() {\n                          ".$configString."\n".'		return '.$this->Get_Configtype().";\n	}\n";
				$fileContentNew = substr($fileContent, 0, $posn+1).$fullString.substr($fileContent, $posn+1);
				//echo $fileContentNew;	
				//echo "\n\n class name : ".get_class($this)."\n";						
				//throw new IPSMessageHandlerException('EventConfiguration could NOT be found !!!', E_USER_ERROR);
				}
			else
				{	
				$fileContentNew = substr($fileContent, 0, $pos1).$configString.substr($fileContent, $pos2);
				}
			if ($debug) echo $configString;
            else file_put_contents($fileNameFull, $fileContentNew);

			$this->Set_EventConfigurationAuto($configuration);
			}

        /* DetectHandler::insertNewFunction
         *
         * FileContent in eine Datei neu schreiben, gemeinsame function
         * die Position zurückmelden, wo die neue Function eingefügt werden kann
         */
        protected function insertNewFunction($fileContent,$debug=false)
            {
            if ($debug)
                {
				echo "================================================\n";
				echo "Function not inserted in config file. Insert now.\n";
                }
            $comment=0; 
            $posn=false;	// letzte Klammer finden und danach einsetzen, den ganzen String durchgehen
            $star = 0;
            $inString = false;
            $stringChar = '';
            $len = strlen($fileContent);            
            for ($i = 0; $i < $len; $i++)  {
                $char = $fileContent[$i];

                // Handle string literals to avoid false positives on braces inside strings
                if ($inString) {
                    if ($char === $stringChar) {
                        $inString = false;
                    } elseif ($char === '\\') {
                        $i++; // Skip escaped character
                    }
                    continue;
                }
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                    continue;
                }  
                
                // Handle comments
                switch ($char)           
                    {
                    /* comment : 0 ok 1 / erkannt 2 /* erkannt 3 // erkannt */
                    case '/':
                        if ( ($comment==2) && ($star==1) ) $comment=0;          // End of /* */
                        elseif ($comment==1) $comment=3;                    // //   erkannt
                        else $comment=1;                                // / erkannt
                        $star=0;
                    case '*':
                        if ($comment==1) $comment=2;            // /*
                        $star=1;	
                        break;
                    case '}':
                    if ($comment <2 ) $posn=$i;	
                    case chr(10):
                    case chr(13):
                        if ($comment==3) $comment=0;		// End of //
                    default:
                        $star=0;
                        break;
                    }		
                }
            
            // Fallback: try to find closing PHP tag if no brace found
            if ( $posn === false )
                {
                $phpClose = strrpos($fileContent, '?>');
                if ($phpClose !== false) {
                    $posn = $phpClose - 1;
                    if ($debug) echo "No closing brace found. Using PHP close tag at $posn.\n";
                } elseif ($len > 6 )
                    {
                    $posn = $len - 1;
                    echo "No closing brace or PHP tag found. Using end of file at $posn.\n";
                    }
                else throw new IPSMessageHandlerException('EventConfiguration File maybe empty !!!', E_USER_ERROR);
                }
            //echo $fileContent."\n  Position last } : ".$posn."   ".substr($fileContent, $posn,5)."\n";	

            return ($posn);
            }

        function convertParams($params)
            {
                    $update="array("; 
                    for ($i=0; $i<count($params); $i=$i+3)          // schreibt in Dreierreihe, Infos sollten nicht verlorengehen
                        {
                        if ($i>0)                                                                                   // nach den ersten 3 Parametern keine Limitierung, aber weiterhin Abarbeitung in Dreiergruppen
                            {
                            for ($j=0;(isset($params[$i+$j])&&($j<3));$j++) $update .= "'".$params[$i+$j]."',";             // Abbruch wenn Parameter nicht in einer Reihe
                            }
                        else $update .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
                        }

            return ($update);
            }

		/** DetectHandler::CreateEvents
		 *
		 * Erzeugt anhand der Konfiguration alle Events
		 *
		 */
		public function CreateEvents()
			{
			$configuration = $this->Get_EventConfigurationAuto();

			foreach ($configuration as $variableId=>$params)
				{
				$this->CreateEvent($variableId, $params[0]);
				}
			}

		/* DetectHandler::ListEvents
		 *
		 * Listet anhand der Konfiguration alle Events als array
		 * Erster Parameter ist ein bekannter Event Typ. Wenn kein Eventtyp übergeben wird, wird dieser ausgegeben.
		 * Wenn ein bekannter Eventtyp übergeben wird, wird der nächste Parameter ausgegeben.
		 * 
		 */
		public function ListEvents($type="")
			{
			$configuration = $this->Get_EventConfigurationAuto();
			$result=array();
            //echo "check ";
			foreach ($configuration as $variableId=>$params)
				{
                //echo $variableId."  ";
				switch ($type)
					{
					case 'Motion':
					case 'Contact':
					case 'Topology':
					case 'HeatControl':	
						if ($type==$params[0])
							{
							$result[$variableId]=$params[1];
							}
						break;
					default:
						/* Modulname nicht bekannt, suche nach Gruppenname und gib die Mitglieder einer Gruppe aus */
						if ($type!="")
							{
							$gruppen=explode(",",$params[1]);
							foreach ($gruppen as $gruppe)
								{
								if ($type==$gruppe)
									{
									$result[$variableId]=$gruppe;
									}
								}
							}
						else
							{
							$result[$variableId]=$params[0];
							}
					   break;
					}
				}
			return ($result);
			}

		/* DetectHandler::ListGroups
		 *
		 * Listet anhand der Konfiguration alle Gruppen. Das ist der zweite Parameter.
		 * Wenn type angegeben wird und bekannt ist werden auch mehrer Gruppen die durch "," getrennt sind ebenfalls aufgelöst
		 * Wenn varID zusaetzlich angegeben ist werden nur die Gruppen aufgelöst in den varID vorkommt.
         *
		 */
		public function ListGroups($type="",$varId=false)
			{
            //echo "ListGroups Aufruf mit $type.\n";
			$configuration = $this->Get_EventConfigurationAuto();
			$result=array();
            if ($varId==false)
                {
                foreach ($configuration as $variableId=>$params)
                    {
                    //echo "Switch type $type.\n";
                    switch ($type)
                        {
                        case 'Motion':
                        case 'Contact':
                        case 'Topology':
                        case 'Temperatur':										
                        case 'Feuchtigkeit':
                        case 'Humidity':
                        case 'HeatControl':
                        case "HeatSet":
                        case "Climate":
                        case "Sensor":
                        case "Brightness":										
                            if (($type==$params[0]) && ($params[1] != ""))
                                {
                                $params1=explode(",",$params[1]);
                                //echo sizeof($params1)."  ".$params1[0]."  ";
                                foreach ($params1 as $entry) $result[$entry]="available";
                                }
                            break;
                        default:
                            if ($type != "") echo "ListGroups Type $type unknown.\n";
                            if ($params[1] != "")
                                {
                                $result[$params[1]]="available";
                                }
                            break;
                        }
                    }
                }
            else
                {
                echo "      ".get_class($this)."::ListGroups, Aufruf mit $type,$varId \n";
                switch ($type)
                    {
                    case "Sensor":
                        if (isset($configuration[$varId][0])) $type=$configuration[$varId][0];
                        break;
                    default:
                        break;
                    }

                if ( (isset($configuration[$varId])) && ($type==$configuration[$varId][0]) && ($configuration[$varId][1] != "") )    
                    {
                    $params1=explode(",",$configuration[$varId][1]);
                    foreach ($params1 as $entry) $result[$entry]="available";
                    }
                else return ($result);
                }
			return ($result);
			}

		/** DetectHandler::ListConfigurations           
		 *
		 * Liest die ganze Konfiguration eines oder aller Events aus. Wertet auch die mirror Register config im par3 aus
		 * Rückmeldung ist ein config array. Index ist die Event OID, 
         *      [Room] ist par2
		 *      [Type] ist par3
         * wenn in par3 -> vorkommen, werden diese Register einzelnen Variablen zugeordnet
         *
		 */
		public function ListConfigurations($oid=false,$debug=false)
			{
            $configurationAll = $this->Get_EventConfigurationAuto();
            if ( ($oid !== false) && (isset($configurationAll[$oid])) ) 
                {
                $configuration = array();
                $configuration[$oid] = $configurationAll[$oid];
                }
            else $configuration = $configurationAll;

			$result=array();
			foreach ($configuration as $oid=>$eventConfig)
				{
                //echo "   bearbeite ".$eventConfig[0]."\n";
                $result[$oid]["Category"] = $eventConfig[0];
                switch ($eventConfig[0])
                    {
					case 'Contact':
					case 'Feuchtigkeit':
					case 'Humidity':
					case 'HeatControl':										
					case 'Motion':
					case 'Temperatur':	
					case 'Topology':
                    case "Climate":
                    case "Sensor":
                        $result[$oid]["Room"] = $eventConfig[1];
                        $typedevs=explode(",",$eventConfig[2]);
                        foreach ($typedevs as $typedev)
                            {
                            $entry = explode("->",$typedev);
                            if (count($entry)>1) 
                                {
                                switch (strtoupper($entry[0]))          // bekannte Selektoren umwandeln, andere übernehmen
                                    {
                                    case "MIRROR":              
                                        $result[$oid]["Config"]["Mirror"] = $entry[1];    
                                        break;
                                    default:
                                        $result[$oid]["Config"][$entry[0]] = $entry[1];
                                        break;
                                    }
                                }
                            else $result[$oid]["Config"]["Type"] = $entry[0];
                            }
                        break;
                    default:
                        break;
                    }
				}
			return ($result);
			}

		/** DetectHandler::getRoomNamefromConfig
         *
         * liest die configuration des Events aus. Wenn ein Raum vorkommt, diesen ausgeben.
		 * $oid ist der Event
         * $group ist der Filter
         *
		 */
        public function getRoomNamefromConfig($oid,$group=false)
            {
            $config = $this->ListConfigurations($oid);
            if (isset($config[$oid]["Room"])) 
                {
                $room=explode(",",$config[$oid]["Room"]);
                if ($group !== false)
                    {
                    foreach ($room as $index => $entry)
                        {
                        //if (isset($room[$group])) unset($room[$group]);
                        if ($entry==$group) unset($room[$index]);
                        }
                    //print_r($room);
                    }
                $result=implode(",",$room);
                return ($result);
                }
            else return (false);
            }  

		/* DetectHandler::getMirrorRegister
         *
         * versucht das Mirror Register zu finden
         * ruft dazu getMirrorRegisterName auf, dieser bedient sich wieder an getMirrorRegisterNamefromConfig welches ListConfigurations ausliest, weil das steht alles drinnen 
         * und überprüft das Ergebnis
		 *
		 */
		public function getMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "      DetectHandler::getMirrorRegister($variableId  \n";
            $variablename=$this->getMirrorRegisterName($variableId,$debug);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "      DetectHandler Fehler, getMirrorRegister for ".get_class()." \"$variablename\" nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            return ($mirrorID);
            }

		/* DetectHandler::getMirrorRegisterNamefromConfig
         *
         * liest die configuration des Events aus. Wenn ein Mirror Index vorkommt, diesen ausgeben.
		 *
		 */
        public function getMirrorRegisterNamefromConfig($oid,$debug=false)
            {
            $config = $this->ListConfigurations($oid,$debug);
            if ($debug) print_R($config);
            if (isset($config[$oid]["Config"]["Mirror"])) return ($config[$oid]["Config"]["Mirror"]);
            else return (false);
            }  

		/* DetectHandler::getMirrorRegisterName    ermittelt den Namen des Spiegelregister für eine Variable oder eine Variable eines Gerätes
         *
		 *  wenn der Name Bestandteil der Config, dann diesen nehmen
         *  sonst schauen ob der Parent eine Instanz ist, dann den Namen der Instanz nehmen, oder
         *  wenn nicht den Namen der Variablen nehmen
         *  
         * am Ende wird der Name der Variable im entsprechenden Datenbereich gesucht. Wenn nicht vorhanden wird false zurückgegeben
         * sonst wird bereits die OID des Mirror Registers geliefert
         *
		 */
		public function getMirrorRegisterName($oid,$debug=false)
			{
			if ($debug)
                {
                echo "          getMirrorRegisterName: Mirror Register von Hardware Register ".$oid." suchen.\n";
                if ($this->Detect_DataID==0) echo "Fehler, Kategorien noch nicht vorhanden,\n";
			    else echo "               Kategorie der Custom Components Spiegelregister : ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).")\n";
                }
            $variablename = $this->getMirrorRegisterNamefromConfig($oid,$debug);       /* wenn ein Wert in der Config abgelegt ist und der Wert vorhanden ist, gleich diesen nehmen */
            if ($variablename === false)
                {
                $result=@IPS_GetObject($oid);
                if ($result !== false)
                    {
                    $parentId=(integer)$result["ParentID"];
                    $resultParent=IPS_GetObject($parentId);
                    if ($debug) 
                        {
                        echo "            No Mirrorregister in Config, try other ways.\n";
                        //echo "            Decision Parameters // ".json_encode($result).json_encode($resultParent)."\n";
                        }
                    //print_R($resultParent);
                    if ($resultParent["ObjectType"]==1)     // Abhängig vom Typ (1 ist Instanz) entweder Parent (typischerweise Homematic) oder gleich die Variable für den Namen nehmen
                        {
                        $variablename=IPS_GetName($parentId);		/* Hardware Komponente */
                        //echo "Mirror Register von Hardware Register ".$oid." aufgrund des Namens der übergeordneten Instanz suchen: $variablename\n";                    
                        }
                    elseif ($resultParent["ObjectType"]==2)
                        {
                        $resultVar=IPS_GetVariable($parentId);
                        //if ($debug) echo "           Variable ".json_encode($resultVar)."\n";
                        if ($resultVar["VariableType"]==3) $variablename=IPS_GetName($parentId)."_".IPS_GetName($oid);
                        else $variablename=IPS_GetName($oid);
                        //echo "Mirror Register von Hardware Register ".$oid." aufgrund des eigenen Namens suchen: $variablename\n";                    
                        }
                    else $variablename=IPS_GetName($oid);
                    }
                }
			return($variablename);
			}

		/* DetectHandler::getMirrorRegisters
         *
         * Array mit Spiegelregistern anlegen
         *
		 */
		public function getMirrorRegisters($events)
            {
            echo "getMirrorRegisters, Array mit Spiegelregistern anlegen.\n";
            //echo "      OID    Pfad                                                                              Config aus EvaluateHardware                                             TemperatureConfig aus DetectMovement            \n";
            $mirrorsFound=array();
            foreach ($events as $oid => $typ)
                {
                $moid=$this->getMirrorRegister($oid);
                if (IPS_GetObject($oid) === false) echo "     Fehler, Register nicht bekannt.\n";
                else
                    {
                    if ($moid === false) echo "  --> Fehler, Spiegelregister nicht bekannt.\n";
                    else
                        {
                        $mirrorsFound[$moid] = IPS_GetName($moid);                
                        //echo "     ".IPS_GetName($oid)."\n";
                        }
                    }
                }
            return($mirrorsFound);
            }

		/* DetectHandler::getVariableName
         *
         * 
         *
		 */
        public function getVariableName($variableId, $debug = false)
            {
            $variablename=$this->getMirrorRegisterName($variableId);
            // variableName witrd nicht erweitert
            return ($variablename);               
            }


		/* DetectHandler::checkMirrorRegisters
         *
         * in CustomComponent Data (data.core.IPSComponent.xxx_Auswertung) gibt es die bereinigten Spiegelregister
         * in der Kategorie werden einige Register gefunden (Istzustand) und über mirrorsFound wird der Sollzustand übergeben
         * Sollzustand so ermitteln:
         *    	$events=$DetectTemperatureHandler->ListEvents();
         *      $mirrorsFound = $DetectTemperatureHandler->getMirrorRegisters($events);
         * Für jeden  Eintrag in der Kategorie den Status ausgeben
         *
		 */
		public function checkMirrorRegisters($AuswertungID,$mirrorsFound)
            {
            $i=0;
            $childrens=IPS_getChildrenIDs($AuswertungID);               // welche register gibt es in der Kategorie
            $mirrors = array();
            foreach ($childrens as $oid)                                // diese einmal einlesen und sortieren
                {    
                $mirrors[IPS_GetName($oid)]=$oid;
                }
            ksort($mirrors);
            //print_r($mirrors);
            foreach ($mirrors as $oid)
                {
                $nochange=true;
                $werte = @AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-120*24*60*60, time(),10000);      // 120 Tage oder 10.000 Werte zurück
                if ($werte === false) echo "   ".str_pad($i,4).str_pad($oid,6).str_pad("(".IPS_GetName($oid).")",35)."  : no archive\n";
                else 
                    {
                    $count=count($werte);
                    echo "   ".str_pad($i,4).str_pad($oid,6).str_pad("(".IPS_GetName($oid).")",35)."  : ".str_pad($count,4)."  ";
                    if ($count>0) 
                        {
                        //print_r($werte[0]);
                        $recentChange=$werte[0]["TimeStamp"];
                        echo " change from ".date("d.m.Y H:i:s",$werte[$count-1]["TimeStamp"])." to ".date("d.m.Y H:i:s",$recentChange);
                        $delay=time()-$recentChange;
                        $delay = $delay/60/60/24;           // Delay in Tagen
                        if ($delay>10) echo " =>10 Tage kein Wert! ";
                        else $nochange=false;
                        //echo " last change ".date("d.m.Y H:i:s",$werte[0]["TimeStamp"]);
                        }
                    else echo "                                ";
                    if (isset($mirrorsFound[$oid])) echo "   -> Mirror in Config";
                    echo "\n";
                    }
                if ($nochange) IPS_SetHidden($oid,true);
                $i++;
                }
            }

		/* DetectHandler::CreateEvent
         *
         * Erzeugt ein Event für eine übergebene Variable, das den IPSMessageHandler beim Auslösen
		 * aufruft.
		 *
    	 */
		public function CreateEvent($variableId, $eventType)
			{
			
			/* Funktion in diesem Kontext nicht mehr klar, wird von Create Events aufgerufen. Hier erfolgt nur ein check ob die Parameter richtig benannt worden sind 
             *  triggerType wird nicht benötigt
             */
			
			switch ($eventType)
				{
				case 'Sensor':
					$triggerType = 9;
					break;                    
				case 'Brightness':
					$triggerType = 8;
					break;
				case 'HeatSet':
					$triggerType = 7;
					break;
				case 'Climate':
					$triggerType = 6;
					break;
				case 'Topology':
					$triggerType = 5;
					break;
				case 'HeatControl':
					$triggerType = 4;
					break;				
				case 'Temperatur':
					$triggerType = 3;
					break;
				case 'Feuchtigkeit':
					$triggerType = 2;
					break;
				case 'Motion':                      /* <-------- change here */
					$triggerType = 1;
					break;
				case 'Contact':
					$triggerType = 0;
					break;
				case 'par0':
				case 'par1':
				case 'par2':
				   break;
				default:
					throw new IPSMessageHandlerException('Found unknown EventType '.$eventType);
				}
			//IPSLogger_Dbg (__file__, 'Created '.$this->Get_Configtype().' Handler Event for Variable='.$variableId);
			}

       /* DetectHandler::sortEventList 
        *
        * eventlist nach Kriterien/Ueberschriften sortieren
        *
        */
        public function sortEventList($array)
            {
            $order="SORT_ASC";
            $new_array = array();
            $sortable_array = array();
            //if ( sizeof($array)==0 ) $array=$this->eventlist;
            if (count($array) > 0) 
                {
                foreach ($array as $k => $v) 
                    {
                    //echo "Sort ".IPS_GetName($k)."\n";
                    $sortable_array[$k] = IPS_GetName($k);
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


        /* DetectHandler::RegisterEvent
         *
         * Registriert ein Event im IPSMessageHandler. Die Funktion legt ein ensprechendes Event
         * für die übergebene Variable an und registriert die dazugehörigen Parameter im MessageHandler
         * Konfigurations File.
         *
         * Abhandlungen von dieser Funktion im MessageHandler, RemoteAccess und Stromheizung Module
         * diese Version kann mehrere Datenelemente wenn eventTypeInput ein Array ist
         * aber nur Get_EventConfigurationAuto nicht Get_EventConfigurationCust
         *
         * Beispiel $Handler->RegisterEvent($soid,'Topology','Wohnzimmer','Temperature,Weather');
         *
         * wenn die oid nicht in der Tabelle enthalten ist wird eine neue Zeile angelegt sonst ein vorhandener Wert upgedated
         *      dazu einen neuen Eintrag machen, das Event registrieren und ein Mirror Register anlegen
         *      für mehr als drei Einträge bestimmt eventTypeInput die tatsächliche Größe (>3)
         *
         * wenn die oid in der Tabelle ist wird es komplizierter
         *      Länge unterschieldich
         *      in den Componen und ModuleParams gibt es Untergruppen, die durch , getrennnt sind
         *      geschrieben wird in Dreiergruppen event,component,module
         *
         * @param integer $variableId ID der auslösenden Variable
         * @param string $eventType Type des Events (OnUpdate oder OnChange)
         * @param string $componentParams Parameter für verlinkte Hardware Komponente (Klasse+Parameter)
         * @param string $moduleParams Parameter für verlinktes Module (Klasse+Parameter)
         *
         * Bei den Parameter kann festgelegt werden ob ein Wert überschrieben wird oder wenn er bereits vorhanden ist übernommen wird.
         *
         */
        public function RegisterEvent($variableId, $eventTypeInput, $componentParamsInput, $moduleParamsInput, $componentOverwrite=false, $moduleOverwrite=false)
            {
            // mehr als drei Inputvariablen können verarbeitet werden, grundsaetzlich haben wir $eventType,$componentParams,$moduleParams
            $targetcount=3;
            if (is_array($eventTypeInput)) 
                {
                $eventType=$eventTypeInput[0];                  // aus den jeweils ersten Werten generieren
                $targetcount=count($eventTypeInput)*3;
                }
            else $eventType=$eventTypeInput;
            if (is_array($componentParamsInput)) $componentParams=$componentParamsInput[0];
            else $componentParams=$componentParamsInput;
            if (is_array($moduleParamsInput)) $moduleParams=$moduleParamsInput[0];
            else $moduleParams=$moduleParamsInput;

            $debug=$this->debug;       // kein Übergabeparameter
            if ($debug) echo "DetectHandler::RegisterEvent, Aufruf mit VariableID $variableId (".IPS_GetName($variableId)."/".IPS_GetName(IPS_GetParent($variableId)).") für EventType $eventType $componentParams $moduleParams \n";
            $configurationAuto = $this->Get_EventConfigurationAuto();
            //print_r($configurationAuto);
            $comment = "Letzter Befehl war RegisterEvent mit VariableID ".$variableId." ".date("d.m.Y H:i:s");
            // Search Configuration
            $found = false;
            $update=false;                      // nur wenn update true ist das File neu schreiben
            if ($variableId !== false)
                {
                if (array_key_exists($variableId, $configurationAuto))
                    {
                    //if ($debug) echo "   Eintrag in Konfiguration besteht fuer VariableID:".$variableId."\n";
                    //echo "Search Config : ".$variableId." with Event Type : ".$eventType." Component ".$componentParams." Module ".$moduleParams."\n";
                    $moduleParamsNew = explode(',', $moduleParams);
                    //print_r($moduleParamsNew);
                    $moduleClassNew  = $moduleParamsNew[0];

                    $params = $configurationAuto[$variableId];          /* die bisherige Konfiguration zB yayay => array('Topology','Arbeitszimmer','',)   */
                    $count = count($params);
                    if ($targetcount>$count) $count=$targetcount;

                    if ($debug) echo "   Eintrag in Konfiguration besteht fuer VariableID:".$variableId." : ".json_encode($params)." sind ".count($params)." Einträge.\n";
                    //print_r($params);
                    for ($i=0; $i<$targetcount; $i=$i+3)              /* immer Dreiergruppen, es könnten auch mehr als eine sein !!! */
                        {
                        if ($i<3)       //0,1,2 der erste Run besteht aus $i=>$eventType; $i+1=>$componentParams[], $i+2=>$moduleParams[]
                            {
                            $moduleParamsCfg = $params[$i+2];           
                            $moduleParamsCfg = explode(',', $moduleParamsCfg);          /* die ModuleCfg als array */
                            $moduleClassCfg  = $moduleParamsCfg[0];                     /* der erste Parameter bei der Modulcfg ist die Class : Temperature, Humidity ... */
                            // Found Variable and Module --> Update Configuration
                            //echo "ModulclassCfg : ".$moduleClassCfg." New ".$moduleClassNew."\n";
                            /* Wenn die Modulklasse gleich ist werden die Werte upgedatet */
                            /*if ($moduleClassCfg=$moduleClassNew)
                                {
                                $found = true;
                                $configurationAuto[$variableId][$i]   = $eventType
                                $configurationAuto[$variableId][$i+1] = $componentParams;
                                $configurationAuto[$variableId][$i+2] = $moduleParams;
                                } */
                            $found = true;
                            if ($configurationAuto[$variableId][$i]   != $eventType ) 
                                {
                                $update=true;
                                echo "   Event Type $eventType hat sich verändert.\n";
                                $configurationAuto[$variableId][$i]   = $eventType;
                                }
                            //else if ($debug) echo "   Event Type $eventType hat sich nicht verändert.\n";
                            /* überschreiben oder doch nicht genauer machen. Neuer Wert ParameterBlock 1 ist componentParams, neuer Wert ParameterBlock 2 ist moduleParams
                            * Es werden nur nicht leere neue Parameter überschrieben. 
                            * Bei moduleParams wird auch in subarrays unterschieden - 
                            */
                            if ( ($componentParams != "") || $componentOverwrite ) { $configurationAuto[$variableId][$i+1] = $componentParams; }     
                            if ( ($moduleParams    != "") || $moduleOverwrite)
                                {
                                $moduleParamsCfgNewCount=count($moduleParamsNew);                       // wieviele subParameter hat der neue ParameterBlock 2
                                if ( (count($moduleParamsCfg)) != ($moduleParamsCfgNewCount) )          // haben sich die subParameter alt zu neu geändert, dann schreiben
                                    {
                                    echo "Update Module Params: $moduleParams. Anzahl hat sich geändert. ".count($moduleParamsCfg)." != $moduleParamsCfgNewCount\n";
                                    //print_r($moduleParamsCfg); print_r($moduleParamsNew);
                                    $moduleParamsArray=array();
                                    $result="";
                                    for ($j=0;$j<$moduleParamsCfgNewCount;$j++) 
                                        {
                                        if ( (isset($moduleParamsCfg[$j])==false) || ( (isset($moduleParamsCfg[$j])) && ($moduleParamsCfg[$j] != "") ) || $moduleOverwrite) $moduleParamsArray[$j]=$moduleParamsNew[$j];
                                        }
                                    foreach ($moduleParamsArray as $entry) $result .= $entry.",";
                                    //print_R($moduleParamsArray); echo "ModuleParams Wert wird gesetzt $entry.\n";
                                    $update=true;
                                    $configurationAuto[$variableId][$i+2] = substr($result,0,strlen($result));      // letztes Komma wieder wegnehmen
                                    } 
                                else
                                    {
                                    if ($configurationAuto[$variableId][$i+2] != $moduleParams)
                                        {
                                        $update=true;
                                        echo "Update Module Params: $moduleParams. Anzahl hat sich nicht geändert ($moduleParamsCfgNewCount) aber der Inhalt: ".$configurationAuto[$variableId][$i+2]." != $moduleParams\n";
                                        $configurationAuto[$variableId][$i+2] = $moduleParams;
                                        } 
                                    }
                                }
                            //echo "RegisterEvent $variableId : ".json_encode($configurationAuto[$variableId])."\n";
                            }
                        else        // noch drei Werte, aber es gibt nix zum Updaten, bleiben wie sie sind
                            {
                            $index=intval($count/3);
                            $configurationAuto[$variableId][$i]   = $eventType[$index];
                            if (isset($componentParams[$index]))$configurationAuto[$variableId][$i+1] = $componentParams[$index];
                            if (isset($moduleParams[$index]))$configurationAuto[$variableId][$i+2] = $moduleParams[$index];
                            }           // ende if
                        }                   // ende for
                    }                           // ende if variableID

                // Variable NOT found --> Create new Configuration Entry in configurationAuto with index variableId
                if (!$found)
                    {
                    if ($debug) echo "Create Event and store it if Update is true : ".($update?"true":"false")."\n";
                    $configurationAuto[$variableId][] = $eventType;         // 0
                    $configurationAuto[$variableId][] = $componentParams;   // 1
                    $configurationAuto[$variableId][] = $moduleParams;      // 2
                    if ($targetcount>3)
                        {
                        for ($i=3;$i<$targetcount;$i=$i+3)          // Input Werte von der nächsten Zeile $eventType[1],$componentParams[1],$moduleParams[1] usw.
                            {
                            $index=intval($i/3);
                            $configurationAuto[$variableId][$i]   = $eventType[$index];
                            if (isset($componentParams[$index]))$configurationAuto[$variableId][$i+1] = $componentParams[$index];
                            if (isset($moduleParams[$index]))$configurationAuto[$variableId][$i+2] = $moduleParams[$index];
                            }    
                        }
                    $update=true;
                    }
                if ($update) $this->StoreEventConfiguration($configurationAuto,$comment);             // zweiter Parameter wäre jetzt ein Kommentar
                $this->CreateEvent($variableId, $eventType);					// Funktion macht eigentlich nichts mehr
                $debug=true;
                $this->CreateMirrorRegister($variableId,$debug);
                }           // Ende variablID ist nicht false
            else echo "Fehler DetectMovement RegisterEvent, variableId ist false.\n";
            return ($update);
            }

        /** DetectHandler::Print_EventConfigurationAuto
         *
         * Ausgabe der registrierten Events oder einer definierten Liste (Auswahl) als echo print
         * wenn ein Array als Parameter übergeben wird, dieses Array ausgeben
         * mit Parameter true kann man eine erweiterte Ausgabe erzwingen
         */
        public function Print_EventConfigurationAuto($list=false,$inputConf=false,$debug=false)
            {
            $extend=false; $filter=false;
            if (is_array($inputConf))
                {
                if (isset($inputConf["extend"])) $extend=$inputConf["extend"];
                if (isset($inputConf["filter"])) 
                    {
                    $filterVal=explode(",",$inputConf["filter"]);
                    $filter=true;
                    echo "Print_EventConfigurationAuto Filter ".json_encode($filterVal)."\n";
                    }
                }
            else $extend=$inputConf;
            if (is_array($list)) 
                {
                $configuration = $list;
                }
            else 
                {
                if ($list==1) $extend=true;
                $configuration = $this->Get_EventConfigurationAuto();
                }
            if ($extend)
                {
                $DetectDeviceHandler = new DetectDeviceHandler();                       // alter Handler für channels, das Event hängt am Datenobjekt
                $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();        
                //print_R($eventDeviceConfig);
                //echo "Classname ".get_class($this)."\n";    
                echo "      OID    Pfad                                                                     Config aus  DetectDeviceHandler (EvaluateHardware)                               Config aus ".get_class($this)."            \n";
                foreach ($configuration as $oid => $typ)
                    {
                    $moid=$this->getMirrorRegister($oid);
                    if (IPS_GetObject($oid) === false) echo "Register nicht bekannt.\n";
                    else
                        {
                        $poid=IPS_GetParent($oid);      // wir brauchen die Parent ID für den Vergleich mit den Instanzen
                        echo "     ".$oid."  ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))),75);
                        if (isset($eventDeviceConfig[$poid])===false) echo str_pad(" --> DeviceConfig für $poid (".IPS_GetName($poid).") nicht bekannt.",70);
                        else                                         echo str_pad(json_encode($eventDeviceConfig[$poid]),70);
                        if (isset($configuration[$oid])===false)  echo "  ".str_pad("--> Config nicht bekannt.\n",60);
                        else                                        echo "  ".str_pad(json_encode($configuration[$oid]),60);
                        if ($moid === false) echo "  --> Spiegelregister nicht bekannt.\n";
                        else                 echo "     ".IPS_GetName($moid);
                        echo "\n";
                        }
                    }
                }
            else
                {
                foreach ($configuration as $variableId=>$params)
                    {
                    $doecho=true;
                    if ($filter)
                        {
                        for ($i=0;$i<count($filterVal);$i++)
                            {
                            if ( (isset($params[$i])) && (isset($filterVal[$i])) && ($params[$i]!=$filterVal[$i]) ) $doecho=false;
                            }
                        }
                    if ($doecho)
                        {
                        echo "  ".$variableId."   ";
                        if (IPS_ObjectExists($variableId)) 
                            {
                            echo str_pad("(".IPS_GetName($variableId)."/".IPS_GetName(IPS_GetParent($variableId))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($variableId))).")",90);
                            foreach ($params as $index=>$param) { echo " \"$param\","; }
                            }
                        else echo "******* Delete Entry from IPSDetectDeviceHandler_GetEventConfiguration() in EvaluateHardware_Configuration.";
                        echo "\n";
                        }
                    }
                }
            }

        /** DetectHandler::PrintEvent
         *
         */
        public function PrintEvent($group)
            {
            echo "ListEvents for $group:\n";
            $config=$this->ListEvents($group);
            //print_R($config);
            foreach ($config as $ID=>$entry) echo "   ".str_pad($ID,6).str_pad(IPS_GetName($ID).".".IPS_GetName(IPS_GetParent($ID)),60).str_pad(GetValue($ID),20)."$entry\n";
            }

        /*  DetectHandler::registerPerType 
         *      called from register in the dedicated Handler class with type as parameter
         *      register ein DetectDeviceHandler Topology Event mit der mirror Register OID eines Events und aller Gruppen
         *      findet sich in der IPSDetectDeviceHandler_GetEventConfiguration() von EvaluateHardware_Configuration
         *
         */
        public function registerPerType($DetectDeviceHandler,$type,$debug=false)
            {
            $do=false;
            switch ($type)
                {
                case "Sensor":          // tested with
                case "Climate":
                case "Humidity":
                    $do=true;
                    $type1=$type;
                    $type2=$type;
                    break;
                case "Motion":
                case "Movement":
                    $do=true;
                    $type1="Motion";                // für ListGroups
                    $type2="Movement";
                    break;
                case "Contact":
                    $do=true;
                    $type1="Motion";
                    $type2="Contact";
                    break;
                case "Temperatur":
                    $do=true;
                    $type1="Temperatur";
                    $type2="Temperature";
                    break;
                }
            if ($do)
                {
                $groups=$this->ListGroups($type1);       /* Type angeben damit mehrere Gruppen aufgelöst werden können */
                $events=$this->ListEvents();
                if ($debug) echo "----------------Liste der Detect".$type1." Events durchgehen:\n";
                foreach ($events as $oid => $typ)
                    {
                    if ($debug) echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
                    $moid=$this->getMirrorRegister($oid);
                    if ($moid !== false) 
                        {
                        if ($debug) echo "         Event register $oid Mirror register $moid Name ".IPS_GetName($moid)."\n";
                        $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','',$type2);		            // <- hier anpasen
                        if ($result) { if ($debug) echo "   *** register Event $moid: $typ\n"; }
                        }
                    }
                if ($debug) echo "----------------Liste der Detect".$type1." Groups durchgehen:\n";
                print_r($groups); 
                foreach ($groups as $group => $entry)
                    {
                    $soid=$this->InitGroupPerName($group,$debug);          // Handler spezifische function, ruft aber wieder DetectHandler::InitGroupPerName auf
                    if ($debug) echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
                    $result=$DetectDeviceHandler->RegisterEvent($soid,'Topology','',$type2);		
                    if ($result) { if ($debug) echo "   *** register Event $soid\n"; }
                    }	
                }
            }

		/* DetectHandler::InitGroupPerName
		 *
		 * Die Gesamtauswertung_ Variablen erstellen, es können verschiedene Variablen sein, diese Rutine fasst mehrere float basierte Variabletypen zusammen 
		 * CO2 in ppm, Baro in mbar
         *
         * mit ListGroups("Climate") wurden die verschiedenen Gruppen berechnet, der Reihe nach hier InitGroup aufrufen
         *
         * verwendet cloneProfileandType um den Variablentyp und die Darstellung herauszufinden. Gibt es ein Spiegelregister gilt dieser Wert
		 */
		function InitGroupPerName($group, $debug=false)
			{
			$config=$this->ListEvents($group);
            //print_R($config);                     // Register die zu dieser Gruppe gehören ausgeben, sollten dem selben Variablentyp angehören
			$status1=(float)0; $count1=0;
            $status=(float)0; $count=0;
			foreach ($config as $oid=>$params)          // [xxx0] => Innen, gibt Name/Parent/Parent aus
				{
                $status+=GetValue($oid);
				$count++;
				if ($debug) echo "  OID:     ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".GetValue($oid)." ".$status."\n";
                $format = $this->cloneProfileandType($oid,false);
				$moid=$this->getMirrorRegister($oid);
				if ($moid !== false) 
                    {
                    //echo "Mirror Register found.\n";
                    $status1+=GetValue($moid);
                    $count1++;
                    if ($debug) echo "     MOID: ".$moid." Name: ".str_pad((IPS_GetName($moid)."/".IPS_GetName(IPS_GetParent($moid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($moid)))),50)."Status: ".GetValue($moid)." ".$status1."\n";
                    $format = $this->cloneProfileandType($moid,false);
                    }
                if ($debug) echo "         OID:  ".$this->cloneProfileandType($oid,true)."  MOID:  ".$this->cloneProfileandType($moid,true)." \n";
				}
            if ($count>0) { $status=round($status/$count,1); }  
            if ($count1>0) { $status1=round($status1/$count,1); }              

            // ist viel schneller als wenn immer Archive und Logging Status setzen, allerdings keine Variablentyp Änderungen möglich
            $statusID=@IPS_GetObjectIDByName("Gesamtauswertung_".$group,$this->Detect_DataID);
            if ($statusID===false)                      
                {
                //function CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') {
                //function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
                $statusID=CreateVariable("Gesamtauswertung_".$group,$format[0],$this->Detect_DataID,1000, $format[1], null,false);           // format = [type,profile], wenn vorhanden ist das das format vom vereinheitlichten Spiegelregister
                if ($debug) echo "      Variable Gesamtauswertung_".$group." mit OID $statusID ist Type und Format : ".$this->cloneProfileandType($statusID,true)."\n";
                
                $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
                AC_SetAggregationType($archiveHandlerID,$statusID,0);      // normaler Wwert 
                IPS_ApplyChanges($archiveHandlerID);
                }
            SetValue($statusID,$status);
			echo "  Gruppe ".str_pad($group,22)." hat neuen Status, Wert ".str_pad($status."    $status1    ",30).GetValueIfFormatted($statusID)."\n";
            return ($statusID);	
			}  

        /**
         * DetectHandler::updateData    
         *  Ein Array wird mit Werten aus einem zweiten Array ergänzt/überschrieben 
         * @param array $target      das Zielarray, wird per Referenz übergeben
         *  
         * @param array $update      das Quellarray mit den zu übernehmenden Werten
         * @param string|array $uniquename   ein Name der bei Debugausgaben mit ausgegeben wird, oder ein Array mit Konfigurationswerten
         * @param bool $debug        Debugausgaben ein/ausschalten
         * 
         * @return bool               true wenn keine Überschreibungen erfolgt sind, false wenn Werte überschrieben wurden
         * 
         * Beispiel:
         *  $target = array("a"=>"1","b"=>"2","c"=>"3");
         *  $update = array("b"=>"20","c"=>"30","d"=>"40");
         * $status = $this->updateData($target,$update,"TestUpdate",true);
         * Ergebnis: $status == false, $target == array("a"=>"1","b"=>"20","c"=>"30","d"=>"40");
         * bei suppress können Werte angegeben werden, die nicht als Überschreibung gewertet werden
         * zB  $suppress=["False","none"];
         *  wenn der alte Wert "False" ist und der neue Wert "True" wird das nicht als Überschreibung gewertet
         */
        public function updateData(&$target,$update,$uniquename="",$debug=false)
            {
            $status=true;
            $suppress=["False","none"];
            if (is_array($uniquename)) 
                {
                $config=$uniquename;
                if (isset($uniquename["suppress"])) $suppress = $uniquename["suppress"];
                if (isset($uniquename["name"])) $uniquename = $uniquename["name"];
                else $uniquename="";
                }
            if ($uniquename!="") $uniquename .= ",";
            foreach ($update as $index => $value)
                {
                if ($value != "")
                    {
                    if (isset($target[$index])===false) 
                        { 
                        $target[$index]=$value;    
                        if ($debug) echo "    $uniquename new Value at $index with ".json_encode($value)." \n";
                        }
                    elseif ($target=="")
                        {
                        $target[$index]=$value;    
                        if ($debug) echo "    $uniquename update empty Value at $index with ".json_encode($value)." \n";
                        }
                    else    
                        {
                        $oldValue=$target[$index];
                        $known = array_search($oldValue, $suppress); 
                        if ($oldValue != $value)
                            {
                            $target[$index]=$value;    
                            if ($known===false) echo "      $uniquename overwrite Value at $index with ".json_encode($value)." . Old Value was ".json_encode($oldValue)." \n";
                            $status=false;
                            }
                        }
                    }
                }
            return ($status);
            }

        }

    /***********************************************************
     *
     * DetectSensorHandler, einfache Erweiterung ohne involvierung von besonderen DetectMovement Funktionen
     *  Detect_DataID   die Kategorie für die Spiegelregister
     *
     ********************************************************/

	class DetectSensorHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				

		protected $Detect_DataID;												/* Speicherort der Mirrorregister, private teilt sich den Speicherort nicht mit der übergeordneten Klasse */ 

		/** DetectSensorHandler::construct
		 *
		 * Initialisierung des DetectSensorHandler Objektes
		 *
		 */
		public function __construct()
			{
			/* Customization of Classes */
			self::$configtype = '$eventSensorConfiguration';
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
			$moduleManagerCC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
			$name="Sensor-Auswertung";
			$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($mdID==false)
				{
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script. ");
				}			
			$this->Detect_DataID=$mdID;	
						
			parent::__construct();
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("Sensor");
            }

		/* DetectSensorHandler::Get_Configtype
         * Customization Part 
         */
		function Get_Configtype()
			{
			return self::$configtype;
			}
		/* DetectSensorHandler::Get_ConfigFileName
         * Customization Part 
         */            
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}				

		/*  DetectSensorHandler::Get_EventConfigurationAuto
         *
         * obige variable in dieser Class kapseln, dannn ist sie static für diese Class 
         */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
                if ( function_exists('IPSDetectSensorHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectSensorHandler_GetEventConfiguration();
				else self::$eventConfigurationAuto = array();					
				}					
			return self::$eventConfigurationAuto;
			}

		/** DetectSensorHandler::Set_EventConfigurationAuto
		 *  
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}

		/**  DetectSensorHandler::getMirrorRegister
		 * 
         *  für Humidity
		 * 
		 */

		public function getMirrorRegister($variableId, $debug=false)
			{
            if ($debug) echo "      DetectSensorHandler::getMirrorRegister($variableId  \n";
            $variablename=$this->getMirrorRegisterName($variableId,$debug);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "DetectSensorHandler Fehler, $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            return ($mirrorID);
            }

		/** DetectSensorHandler::CreateMirrorRegister
		 * 
         * Das Spiegelregister anlegen
		 * 
		 */

		public function CreateMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectSensorHandler::CreateMirrorRegister in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).").\n";    

            /* clone profile and type from original variable */
            $variableProfile=IPS_GetVariable($variableId)["VariableProfile"];
            if ($variableProfile=="") $variableProfile=IPS_GetVariable($variableId)["VariableCustomProfile"];
            $variableType=IPS_GetVariable($variableId)["VariableType"];
                
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,$variableType,$this->Detect_DataID,10, $variableProfile, null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/* DetectSensorHandler::InitGroup
		 *
		 * Die DetectSensorHandler Sensor Gesamtauswertung_ Variablen erstellen
         * aufgerufen von register
         * ruft aus der gleichen Klasse ListEvents auf 
         * 
         * siehe DetectClimateHandler
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			if ($debug) echo "\nDetect Sensor Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$statusID=$this->InitGroupPerName($group,$debug);
			return ($statusID);			
			}  
			
        /*  DetectSensorHandler::register 
         *      registerPerType Sensor
         *      Customizing  Sensor, DetectSensor, Sensor
         *      register ein DetectDeviceHandler Topology Event mit der mirror Register OID eines Events und aller Gruppen
         *      findet sich in der IPSDetectDeviceHandler_GetEventConfiguration() von EvaluateHardware_Configuration
         *
         */
        public function register($DetectDeviceHandler,$debug=false)
            {
            $this->registerPerType($DetectDeviceHandler,"Sensor",$debug); 
            }

		}


    /***********************************************************
     *
     * DetectCounterHandler, einfache Erweiterung ohne involvierung von besonderen DetectMovement Funktionen
     *  Detect_DataID   die Kategorie für die Spiegelregister
     *
     ********************************************************/

	class DetectCounterHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				

		protected $Detect_DataID;												/* Speicherort der Mirrorregister, private teilt sich den Speicherort nicht mit der übergeordneten Klasse */ 

		/**
		 * @public
		 *
		 * Initialisierung des DetectSensorHandler Objektes
		 *
		 */
		public function __construct()
			{
			/* Customization of Classes */
			self::$configtype = '$eventCounterConfiguration';
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
			$moduleManagerCC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
			$name="Counter-Auswertung";                                     /*   <--- change here */
			$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($mdID==false)
				{
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script. ");
				}			
			$this->Detect_DataID=$mdID;	
						
			parent::__construct();
			}
        
        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("Counter");
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}				

		/* 
         * DetectSensorHandler Objektes
         * obige variable in dieser Class kapseln, dannn ist sie static für diese Class 
         */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
                if ( function_exists('IPSDetectCounterHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectCounterHandler_GetEventConfiguration();            /*   <--- change here */
				else self::$eventConfigurationAuto = array();					
				}					
			return self::$eventConfigurationAuto;
			}

		/**
		 * DetectSensorHandler Objektes
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}

		/**
		 * getMirrorRegister für Counter
		 * 
		 */

		public function getMirrorRegister($variableId, $debug = false)
			{
            if ($debug) echo "      DetectCounterHandler::getMirrorRegister($variableId  \n";
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "DetectCounterHandler Fehler, $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            return ($mirrorID);
            }

		/**
		 * Das DetectCounterHandler Spiegelregister anlegen
		 * 
		 */

		public function CreateMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectCounterHandler::CreateMirrorRegister in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).").\n";    

            /* clone profile and type from original variable */
            $variableProfile=IPS_GetVariable($variableId)["VariableProfile"];
            if ($variableProfile=="") $variableProfile=IPS_GetVariable($variableId)["VariableCustomProfile"];
            $variableType=IPS_GetVariable($variableId)["VariableType"];
                
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,$variableType,$this->Detect_DataID,10, $variableProfile, null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/* DetectCounterHandler::InitGroup
		 *
		 * Die DetectCounterHandler Sensor Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			if ($debug) echo "\nDetect Counter Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$statusID=$this->InitGroupPerName($group,$debug);
			return ($statusID);			
			}  
			
		}

    /*****************************************************************************************************************
     *
     * DetectClimateHandler für die Netatmo register
     *      __construct
     *      Get_Configtype
     *      Get_ConfigFileName
     *      Get_EventConfigurationAuto
     *      Set_EventConfigurationAuto
     *      getMirrorRegister
     *      getVariableName
     *      CreateMirrorRegister
     *      InitGroup
     *      CalcGroup
     *
     *
     */
	class DetectClimateHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				

		protected $Detect_DataID;												/* Speicherort der Mirrorregister, private teilt sich den Speicherort nicht mit der übergeordneten Klasse */ 

		/* Initialisierung des DetectClimateHandler Objektes
		 *
		 */
		public function __construct($debug=false)
			{
            $this->debug=$debug;
			/* Customization of Classes */
			self::$configtype = '$eventClimateConfiguration';
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
			$moduleManagerCC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
			$name="Climate-Auswertung";
			$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($mdID==false)
				{
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script. ");
				}			
			$this->Detect_DataID=$mdID;	
						
			parent::__construct($debug);
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("Climate");
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}				

		/* DetectSensorHandler Objektes
         * obige variable in dieser Class kapseln, dannn ist sie static für diese Class 
         */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
                if ( function_exists('IPSDetectClimateHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectClimateHandler_GetEventConfiguration();
				else self::$eventConfigurationAuto = array();					
				}					
			return self::$eventConfigurationAuto;
			}

		/* DetectSensorHandler Objektes
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}

		/* DetectClimateHandler::getMirrorRegister 
		 * Achtung, für Climate gibt es einen anderen MirrorRegisterName, Endung CO2 oder BARO
		 */
		public function getMirrorRegister($variableId, $debug = false)
			{
            if ($debug) echo "      DetectClimateHandler::getMirrorRegister($variableId  \n";
            $variablename=$this->getVariableName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "      DetectClimateHandler Fehler, $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            return ($mirrorID);
            }

        public function getVariableName($variableId, $debug = false)
            {
            $variablename=$this->getMirrorRegisterName($variableId);
            switch ($format=$this->cloneProfileandType($variableId,true)) 
                {
                case "Integer.Netatmo.CO2":
                    $variablename=$variablename."CO2";
                    break;  
                case "Float.Netatmo.Pressure":
                    $variablename=$variablename."BARO";
                    break;  
                default:
                    echo "DetectClimateHandler::getMirrorRegister $format unknown. \n";
                    break;
                } 
            return ($variablename);               
            }

		/* DetectClimateHandler::CreateMirrorRegister
         *
		 * Das DetectClimateHandler Spiegelregister anlegen
		 * 
		 */
		public function CreateMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectClimateHandler::CreateMirrorRegister in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).").\n";    
            //$format = $this->cloneProfileandType($variableId,false);          /* clone profile and type from original variable */
            
            $variableProfile=IPS_GetVariable($variableId)["VariableProfile"];
            if ($variableProfile=="") $variableProfile=IPS_GetVariable($variableId)["VariableCustomProfile"];
            $variableType=IPS_GetVariable($variableId)["VariableType"];
                
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($debug) echo "      More Data : Input register $variableId $variablename $variableProfile Type: $variableType Ergebnis Mirror ID $mirrorID \n";
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,$variableType,$this->Detect_DataID,10, $variableProfile, null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/* DetectClimateHandler::InitGroup
		 *
		 * Die Climate Gesamtauswertung_ Variablen erstellen, es können verschiedene Variablen sein, diese Rutine fasst mehrere Variabletypen zusammen 
		 * CO2 in ppm, Baro in mbar
         *
         * mit ListGroups("Climate") wurden die verschiedenen Gruppen berechnet, der Reihe nach hier InitGroup aufrufen
         *
         * verwendet cloneProfileandType um den Variablentyp und die Darstellung herauszufinden. Gibt es ein Spiegelregister gilt dieser Wert
		 */
		function InitGroup($group, $debug=false)
			{
			if ($debug) echo "\nDetect Climate Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$statusID=$this->InitGroupPerName($group,$debug);
			return ($statusID);			
			}  

		/* Die Climate Gesamtauswertung_ Variablen berechnen 
		 *
		 */
		function CalcGroup($config,$debug=false)
			{
			//if ($debug) echo "\nCalcgroup ".json_encode($config)."\n";
            $status=(float)0;                       // Status zusammenzählen
            $power=(float)0;                        // Leistung zusammenzählen
            $count=0;
			foreach ($config as $oid=>$params)
				{
                $mirrorID=$this->getMirrorRegister($oid);
                $variablename=IPS_GetName($mirrorID);
                $mirrorPowerID=@IPS_GetObjectIDByName($variablename."_Power",$mirrorID);                    
                //$status+=GetValue($oid);                  // unterschiede Integer und Power
                if ($mirrorID) $status+=GetValue($mirrorID);
                if ($mirrorPowerID) $power+=GetValue($mirrorPowerID);
                $count++;                
                /* Ausgabe der Berechnung der Gruppe */
                if ($debug) 
                    {
                    echo "    OID: ".$oid;
                    //echo " Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)                
                    echo " Name: ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),50);
                    echo "Status (LEVEL | POWER) ".str_pad(GetValue($oid),10)." ".str_pad($status,10)." | ";
                    if ($mirrorPowerID) echo str_pad(GetValue($mirrorPowerID),10);
                    else echo "   0  ";
                    echo "   ".$power."\n";
                    }
				}
            if ($count>0) { $status=$status/$count; }
            echo "Gruppe hat neuen Status : ".$status." | ".$power."\n";                
            /* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
			return ($power);			
			}					

			
        /*  DetectClimateHandler::register 
         *      registerPerType Climate
         *      register ein DetectDeviceHandler Topology Event mit der mirror Register OID eines Events und aller Gruppen
         *      findet sich in der IPSDetectDeviceHandler_GetEventConfiguration() von EvaluateHardware_Configuration
         *
         */
        public function register($DetectDeviceHandler,$debug=false)
            {
            $this->registerPerType($DetectDeviceHandler,"Climate",$debug); 
            }


        /*  DetectClimateHandler::registerMirrorByID 
         *  DetectDeviceHandler kümmert sich um die Bearbeitung von MirrorID im Zusammenhang mit der Topologie
         *
         *  komplexere Routine in EvaluateHardware für Liste Events , übernimmt das Mirroregister in die Konfiguration !!!
         *  generiert zuätzliche Einträge, kontrolliert ob Fehler sind, und wenn nicht keine Übernahme
         *
         */
        public function registerMirrorByID($DetectDeviceHandler,$oid,$debug=false)
            {
            $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();
            $eventClimateConfig=$this->Get_EventConfigurationAuto(); 
            $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            if ($debug) echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$this->getMirrorRegister($oid);
            if ($moid !== false) 
                {
                $mirror = IPS_GetName($moid);    
                // überwacht ob Logging im Spiegelregister erfolgt
                $werte = @AC_GetLoggedValues($archiveHandlerID,$moid, time()-60*24*60*60, time(),1000);
                if ($werte === false) echo "        Kein Logging für Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).")\n";  
                // überwacht ob die oid registriert ist
                if (isset($eventDeviceConfig[$oid])===false) echo "        DetectDeviceHandler->Get_EventConfigurationAuto() ist für $oid false  \n";
                if (isset($eventClimateConfig[$oid])===false)   echo "        DetectClimateHandler->Get_EventConfigurationAuto() ist für $oid false  \n";               // <- hier anpasen
                if (IPS_ObjectExists($oid)===false)          echo "        IPS_ObjectExists($oid) ist false \n";   

                $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Climate');		            // <- hier anpasen
                // das Mirror Register in die Konfiguration schreiben
                // <- hier anpasen
                $this->RegisterEvent($oid,"Climate",'','mirror->'.$mirror);     // par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben , Mirror Register als Teil der Konfig

                if ($result) { if ($debug) echo "   *** register Event $moid\n"; }
                }
            }                 


		}

    /******************************************************************************************************************/

    /* DetectHumidityHandler
     * neue Funktionen
     *      __construct
     *      Get_Configtype
     *      Get_ConfigFileName
     *      Get_EventConfigurationAuto
     *      Set_EventConfigurationAuto
     *      getMirrorRegister
     *      CreateMirrorRegister
     *      InitGroup
     *
     *
     *
     */

	class DetectHumidityHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				

		protected $Detect_DataID;												/* Speicherort der Mirrorregister, private teilt sich den Speicherort nicht mit der übergeordneten Klasse */ 

		/**
		 * @public
		 *
		 * Initialisierung des DetectHumidityHandler Objektes
		 *
		 */
		public function __construct()
			{
			/* Customization of Classes */
			self::$configtype = '$eventHumidityConfiguration';
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
			$moduleManagerCC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
			$name="Feuchtigkeit-Auswertung";
			$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($mdID==false)
				{
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script. ");
				}			
			$this->Detect_DataID=$mdID;	
						
			parent::__construct();
			}


        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("Feuchtigkeit");
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}				

		/* 
         * DetectHumidityHandler Objektes
         * obige variable in dieser Class kapseln, dannn ist sie static für diese Class 
         */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
                if ( function_exists('IPSDetectHumidityHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectHumidityHandler_GetEventConfiguration();
				else self::$eventConfigurationAuto = array();					
				}					
			return self::$eventConfigurationAuto;
			}

		/**
		 * DetectHumidityHandler Objektes
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}

		/**
		 * getMirrorRegister für Humidity
		 * 
		 */

		public function getMirrorRegister($variableId, $debug = false)
			{
            if ($debug) echo "      DetectHumidityHandler::getMirrorRegister($variableId  \n";
            $variablename=$this->getMirrorRegisterName($variableId, $debug);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "       DetectHumidityHandler Fehler, $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            return ($mirrorID);
            }



		/**
		 * Das DetectHumidityHandler Spiegelregister anlegen
		 * 
		 */

		public function CreateMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectHumidityHandler::CreateMirrorRegister in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).").\n";    

            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,1,$this->Detect_DataID,10, '~Humidity', null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/** DetectHumidityHandler
		 *
		 * Die Humidity Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			if ($debug) echo "\nDetect Feuchtigkeit Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$statusID=$this->InitGroupPerName($group,$debug);
			return ($statusID);			
			}

        /*  DetectHumidityHandler::register 
         *      registerPerType Humidity
         */
        public function register($DetectDeviceHandler,$debug=false)
            {
            $this->registerPerType($DetectDeviceHandler,"Humidity",$debug); 
            }

        /*  DetectHumidityHandler::registerMirrorByID 
         *  DetectDeviceHandler kümmert sich um die Bearbeitung von MirrorID im Zusammenhang mit der Topologie
         *
         *  komplexere Routine in EvaluateHardware für Liste Events , übernimmt das Mirroregister in die Konfiguration !!!
         *  generiert zuätzliche Einträge, kontrolliert ob Fehler sind, und wenn nicht keine Übernahme
         *
         */
        public function registerMirrorByID($DetectDeviceHandler,$oid,$debug=false)
            {
            $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();
            $eventTempConfig=$this->Get_EventConfigurationAuto(); 
            $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            if ($debug) echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$this->getMirrorRegister($oid);
            if ($moid !== false) 
                {
                $mirror = IPS_GetName($moid);    
                // überwacht ob Logging im Spiegelregister erfolgt
                $werte = @AC_GetLoggedValues($archiveHandlerID,$moid, time()-60*24*60*60, time(),1000);
                if ($werte === false) echo "        Kein Logging für Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).")\n";  
                // überwacht ob die oid registriert ist
                if (isset($eventDeviceConfig[$oid])===false) echo "        DetectDeviceHandler->Get_EventConfigurationAuto() ist für $oid false  \n";
                if (isset($eventTempConfig[$oid])===false)   echo "        DetectHumidityHandler->Get_EventConfigurationAuto() ist für $oid false  \n";
                if (IPS_ObjectExists($oid)===false)          echo "        IPS_ObjectExists($oid) ist false \n";   

                $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Humidity');		            // <- hier anpasen
                // das Mirror Register in die Konfiguration schreiben
                $this->RegisterEvent($oid,"Feuchtigkeit",'','mirror->'.$mirror);     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben , Mirror Register als Teil der Konfig*/

                if ($result) { if ($debug) echo "   *** register Event $moid\n"; }
                }
            }

		}

    /*****************************************************************************************************************
     *
     *  DetectMovementHandler
     *  mit neuen Funktionen
     *      __construct
     *      Get_Configtype
     *      Get_ConfigFileName
     *      Get_CategoryData
     *      Get_MoveAuswertungID
     *      Set_MoveAuswertungID
     *      getCustomComponentsDataGroup
     *      getDetectMovementDataGroup
     *      Get_EventConfigurationAuto
     *      Set_EventConfigurationAuto
     *      getMirrorRegister
     *      CreateMirrorRegister
     *      InitGroup
     *
     */
	class DetectMovementHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				
		
        protected $CategoryIdData;                          /* Category Data Kategorie des eigenen Moduls */
		private $MoveAuswertungID;

		protected $Detect_DataID;		                    /* ist nicht das normale Detect_DataID , zeigt auf DetectMovement */

		/**
		 * @public
		 *
		 * Initialisierung des DetectMovementHandler Objektes
		 *
		 */
		public function __construct($MoveAuswertungID=false)
			{
			/* Customization of Classes */
			self::$configtype = '$eventMoveConfiguration';                                          /* <-------- change here */
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
			if ($MoveAuswertungID===false)
				{
				$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
				$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
				$name="Bewegung-Auswertung";
				$MoveAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
				if ($MoveAuswertungID==false)
					{
					$MoveAuswertungID = IPS_CreateCategory();
					IPS_SetParent($MoveAuswertungID, $CategoryIdData);
					IPS_SetName($MoveAuswertungID, $name);
					IPS_SetInfo($MoveAuswertungID, "this category was created by script. ");
					}
				$this->MoveAuswertungID=$MoveAuswertungID;					
				}
			else $this->MoveAuswertungID=$MoveAuswertungID;
			
			$moduleManager_DM = new IPSModuleManager('DetectMovement');     /*   <--- change here */
			$this->CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
			$name="Motion-Detect";
			$mdID=@IPS_GetObjectIDByName($name,$this->CategoryIdData);
			if ($mdID==false)
				{
                echo "Create Category Motion-Detect in ".$this->CategoryIdData."\n";
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $this->CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script construct of class DetectMovementHandler. ");
				}			
			$this->Detect_DataID=$mdID;			
			parent::__construct();
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("Motion");
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}	

        /* Move spezifische Funktionen */

		function Get_CategoryData()
			{
			return ($this->CategoryIdData);
			}
            
        /* wird beim construct (new) mitgegeben, oder selbst definiert, ist die Kategorie ID von "Bewegung-Auswertung" im CustomComponent  */

		function Get_MoveAuswertungID()
			{
			return ($this->MoveAuswertungID);
			}	

		function Set_MoveAuswertungID($MoveAuswertungID=false)
			{
			if ($MoveAuswertungID===false) $this->MoveAuswertungID=$MoveAuswertungID;
			return (true);
			}	
            
		/**
		 *
		 * private Variablen ausgeben 
		 *
		 */
		function getCustomComponentsDataGroup()
			{
            echo "Use Get_MoveAuswertungID\n";
			return($this->MoveAuswertungID);
			}			

		function getDetectMovementDataGroup()
			{
			return($this->Detect_DataID);
			}			
			

		/* obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
                if ( function_exists('IPSDetectMovementHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectMovementHandler_GetEventConfiguration();       /* <-------- change here */
    			else self::$eventConfigurationAuto = array();					
				}					
			return self::$eventConfigurationAuto;
			}

		/** DetectMovementHandler:
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
		   	self::$eventConfigurationAuto = $configuration;
			}

		/** DetectMovementHandler:getMirrorRegister
		 * 
         *  sucht den Namen des Spiegelregister für eine Variable oder eine Variable eines Gerätes
		 *  wenn der Name Bestandteil der Config, dann diesen nehmen
         *  sonst schauen ob der Parent eine Instanz ist, dann den Namen der Instanz nehmen, oder
         *  wenn der Name der Variablen CAM_Motion ist, dann auch den Name des Parent nehmen, oder
         *  wenn beides nicht den Namen der Variablen nehmen
         *  
         * am Ende wird der Name der Variable im entsprechenden Datenbereich gesucht. Wenn nicht vorhanden wird die OID des aktuellen Registers zurückgegeben
         *      $this->MoveAuswertungID         Kategorie für Spiegelregister, schnell, Standard
         *      $this->Detect_DataID            Kategorie für Spiegelregister, zusätzliches Register geglättet mit Delay
         *
         */

		public function getMirrorRegister($variableId, $debug = false)
			{
            if ($debug) echo "      DetectMovementHandler::getMirrorRegister($variableId  \n";    
            $variablename=$this->getMirrorRegisterName($variableId);
            $result=IPS_GetObject($variableId);            
			if ($variablename == "Cam_Motion")					/* was ist mit den Kameras */
				{
				$variablename=IPS_GetName((integer)$result["ParentID"]);
				}            
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->MoveAuswertungID);
            if ($mirrorID === false) echo "       DetectMovementHandler Fehler, $variablename nicht in ".$this->MoveAuswertungID." (".IPS_GetName($this->MoveAuswertungID).") gefunden.\n";
            return ($mirrorID);
            }

		/** DetectMovementHandler
        *
		 * Das  Spiegelregister anlegen
		 * 
		 */
			
		public function CreateMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectMovementHandler::CreateMirrorRegister in ".$this->MoveAuswertungID." (".IPS_GetName($this->MoveAuswertungID).") und ".
                                                $this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).").\n";    
            $variablename=$this->getMirrorRegisterName($variableId);
            $result=IPS_GetObject($variableId);             
			if ($variablename == "Cam_Motion")					/* was ist mit den Kameras */
				{
				$variablename=IPS_GetName((integer)$result["ParentID"]);
				}
			$mirrorID=@IPS_GetObjectIDByName($variablename,$this->MoveAuswertungID);		/* das sind die schnellen Register, ohne Delay */
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,0,$this->MoveAuswertungID,10, '~Motion', null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}

			$mirrorID=@IPS_GetObjectIDByName($variablename,$this->Detect_DataID);		/* das sind die geglätteten Register mit Delay */
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,0,$this->Detect_DataID,10, '~Motion', null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/** DetectMovementHandler:
		 *
		 * Die Movement Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			if ($debug) echo "\nDetect Movement Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->MoveAuswertungID." (".IPS_GetName($this->MoveAuswertungID).") und in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
			$status=false; $status1=false;
			foreach ($config as $oid=>$params)
				{
				$status=$status || GetValue($oid);
				if ($debug) echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
				$moid=$this->getMirrorRegister($oid);              // $this->Detect_DataID nicht benötigt
				if ($moid !== false) $status1=$status1 || GetValue($moid);
				}
			if ($debug) echo "  Gruppe ".$group." hat neuen Status, Wert ohne Delay: ".(integer)$status."  mit Delay:  ".(integer)$status1."\n";
			$statusID=CreateVariable("Gesamtauswertung_".$group,0,$this->MoveAuswertungID,1000, '~Motion', null,false);
			SetValue($statusID,$status1);
			$status1ID=CreateVariable("Gesamtauswertung_".$group,0,$this->Detect_DataID,1000, '~Motion', null,false);
			SetValue($status1ID,$status);
			
  			$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
     		AC_SetLoggingStatus($archiveHandlerID,$status1ID,true);
			AC_SetAggregationType($archiveHandlerID,$status1ID,0);      /* normaler Wwert */
			IPS_ApplyChanges($archiveHandlerID);
			return ($statusID);			
			}


        /*  DetectMovementHandler::register 
         *      registerPerType Motion, synonym for Movement
         */
        public function register($DetectDeviceHandler,$debug=false)
            {
            $this->registerPerType($DetectDeviceHandler,"Motion",$debug); 
            }
                     	
			
		} /* ende class */	

    /*****************************************************************************************************************
     *  alle Register mit einer Helligkeitsmessung vereinen
     *  die Konfiguration wäre in DetectMovement_Configuration zu finden
     *  in data/core/IPSComponent/ werden die Kategorien Helligkeit-Auswertung und Helligkeit-Nachrichten angelegt
     *
     * Nachdem es auch Mirror und Gruppen Register gibt, die wenn sich ein Wert ändert auch upgedatet werden, fasst diese Funktion zusammen.
     * Mit der Config wird in scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php die Funktion IPSDetectTemperatureHandler_GetEventConfiguration upgedatet
     *
     *  Get_Configtype, Get_ConfigFileName
     *  Get_EventConfigurationAuto
     *  Set_EventConfigurationAuto
     *  getMirrorRegister
     *  CreateMirrorRegister
     *  InitGroup 
     *
     * mit construct wird nur die Kategorie angelegt
     */

	class DetectBrightnessHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				
		
        protected $CategoryIdData;                          /* Category Data Kategorie des eigenen Moduls */
		protected $Detect_DataID;		

		/**
		 * @public
		 *
		 * Initialisierung des DetectBrightnessHandler Objektes
		 *
		 */
		public function __construct()
			{
			/* Customization of Classes */
            $debug=false;
			self::$configtype = '$eventBrightnessConfiguration';                                          /* <-------- change here */
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
            $moduleManagerCC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
            $CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
            $name="Helligkeit-Auswertung";                                                              /*   <--- change here */
            $mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
            if ($mdID==false)
                {
                $mdID = IPS_CreateCategory();
                IPS_SetParent($mdID, $CategoryIdData);
                IPS_SetName($mdID, $name);
                IPS_SetInfo($mdID, "this category was created by script. ");
                }			
            $this->Detect_DataID=$mdID;			
			if ($debug) echo "DetectBrightnessHandler construct set Detect_DataID=$mdID.\n";
			parent::__construct();			
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("Brightness");
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}	

		/* obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
                if ( function_exists('IPSDetectBrightnessHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectBrightnessHandler_GetEventConfiguration();       /* <-------- change here */
    			else self::$eventConfigurationAuto = array();					
				}					
			return self::$eventConfigurationAuto;
			}

		/**
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
		   	self::$eventConfigurationAuto = $configuration;
			}

		/** getMirrorRegister für Movement
		 * 
         *  sucht den Namen des Spiegelregister für eine Variable oder eine Variable eines Gerätes
		 *  wenn der Name Bestandteil der Config, dann diesen nehmen
         *  sonst schauen ob der Parent eine Instanz ist, dann den Namen der Instanz nehmen, oder
         *  wenn der Name der Variablen CAM_Motion ist, dann auch den Name des Parent nehmen, oder
         *  wenn beides nicht den Namen der Variablen nehmen
         *  
         * am Ende wird der Name der Variable im entsprechenden Datenbereich gesucht. Wenn nicht vorhanden wird die OID des aktuellen Registers zurückgegeben
         *      $this->MoveAuswertungID         Spiegelregister, schnell, Standard
         *      $this->motionDetect_DataID      Spiegelregister, zusätzliches Register geglättet mit Delay
         *
         */

		public function getMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "      DetectBrightnessHandler::getMirrorRegister($variableId) aufgerufen.\n";
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "      DetectBrightnessHandler Fehler, getMirrorRegister for Brightness $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            //else echo "getMirrorRegister for Temperature $variablename\n";
            return ($mirrorID);
            }

		/**
		 * Das DetectbrightnessHandler Spiegelregister anlegen
		 * 
		 */
			
		public function CreateMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectBrightnessHandler::CreateMirrorRegister in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).").\n";    

            $variablename=$this->getMirrorRegisterName($variableId);
            $result=IPS_GetObject($variableId);             
			$mirrorID=@IPS_GetObjectIDByName($variablename,$this->Detect_DataID);		/* das sind die geglätteten Register mit Delay */
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,0,$this->Detect_DataID,10, '~Motion', null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/**
		 *
		 * Die brightness Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			echo "\nDetect Brightness Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
			$status=(float)0; $status1=(float)0; $i=0;
			foreach ($config as $oid=>$params)
				{
				$status=$status + GetValue($oid);
				if ($debug) echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),70)."Status: ".GetValue($oid)." ".$status."\n";
				$moid=$this->getMirrorRegister($oid);
				if ($moid !== false) $status1=$status1 + GetValue($moid);
                $i++;
				}
            $status=$status/$i;
            $status1=$status1/$i;
			echo "  Gruppe ".$group." hat neuen Status, Wert: ".(integer)$status."  im Mirror register:  ".(integer)$status1."\n";
			$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->Detect_DataID,1000, '~Brightness', null,false);
			SetValue($statusID,$status);
			
  			$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
			IPS_ApplyChanges($archiveHandlerID);
			return ($statusID);			
			}
					
			
			
		} /* ende class */	

    /*****************************************************************************************************************
     *
     *  alle Register mit einem Kontakthier vereinen
     *  die Konfiguration wäre in DetectMovement_Configuration zu finden
     *  in data/core/IPSComponent/ werden die Kategorien Kontakt-Auswertung und Kontakt-Nachrichten angelegt
     *
     * Nachdem es auch Mirror und Gruppen Register gibt, die wenn sich ein Wert ändert auch upgedatet werden, fasst diese Funktion zusammen.
     * Mit der Config wird in scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php die Funktion IPSDetectTemperatureHandler_GetEventConfiguration upgedatet
     *
     *  __construct
     *  Get_Configtype, Get_ConfigFileName
     *  Get_EventConfigurationAuto
     *  Set_EventConfigurationAuto
     *  getMirrorRegister
     *  CreateMirrorRegister
     *  InitGroup 
     *
     * mit construct wird nur die Kategorie angelegt

    *
    *
    */

	class DetectContactHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				
		
        protected $CategoryIdData;                          /* Category Data Kategorie des eigenen Moduls */
		protected $Detect_DataID;		

		/**
		 * @public
		 *
		 * Initialisierung des DetectContactHandler Objektes
		 *
		 */
		public function __construct()
			{
            $debug=false;
			/* Customization of Classes */
			self::$configtype = '$eventContactConfiguration';                                          /* <-------- change here */
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
            $moduleManager_CC = new IPSModuleManager('CustomComponent');                                /*   <--- change here */
            $CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            $name="Kontakt-Auswertung";                                                              /*   <--- change here */
            $mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
            if ($mdID==false)
                {
                $mdID = IPS_CreateCategory();
                IPS_SetParent($mdID, $CategoryIdData);
                IPS_SetName($mdID, $name);
                IPS_SetInfo($mdID, "this category was created by script. ");
                }			
            $this->Detect_DataID=$mdID;			
			if ($debug) echo "DetectContactHandler construct set Detect_DataID=$mdID.\n";
			parent::__construct();
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("Contact");
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}	

		/* obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
                if ( function_exists('IPSDetectContactHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectContactHandler_GetEventConfiguration();       /* <-------- change here */
    			else self::$eventConfigurationAuto = array();					
				}					
			return self::$eventConfigurationAuto;
			}

		/**
		 * DetectContactHandler:Set_EventConfigurationAuto
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
		   	self::$eventConfigurationAuto = $configuration;
			}

		/** DetectContactHandler:getMirrorRegister
		 * 
         *  sucht den Namen des Spiegelregister für eine Variable oder eine Variable eines Gerätes
		 *  wenn der Name Bestandteil der Config, dann diesen nehmen
         *  sonst schauen ob der Parent eine Instanz ist, dann den Namen der Instanz nehmen, oder
         *  wenn der Name der Variablen CAM_Motion ist, dann auch den Name des Parent nehmen, oder
         *  wenn beides nicht den Namen der Variablen nehmen
         *  
         * am Ende wird der Name der Variable im entsprechenden Datenbereich gesucht. Wenn nicht vorhanden wird die OID des aktuellen Registers zurückgegeben
         *
         */

		public function getMirrorRegister($variableId, $debug = false)
			{
            if ($debug) echo "      DetectContactHandler::getMirrorRegister($variableId  \n";
            $variablename=$this->getMirrorRegisterName($variableId);
            if ($debug) echo "         getMirrorRegister($variableId  Name Variable ist $variablename.\n";
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "      DetectContactHandler Fehler, getMirrorRegister for Contact $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            //else echo "getMirrorRegister for Temperature $variablename\n";
            return ($mirrorID);
            }

		/** DetectContactHandler:CreateMirrorRegister
		 * 
		 * Das DetectContactHandler Spiegelregister anlegen
		 */
			
		public function CreateMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectContactHandler::CreateMirrorRegister in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).").\n";    

            $variablename=$this->getMirrorRegisterName($variableId);
            $result=IPS_GetObject($variableId);             
			$mirrorID=@IPS_GetObjectIDByName($variablename,$this->Detect_DataID);		/* das sind die geglätteten Register mit Delay */
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,0,$this->Detect_DataID,10, '~Motion', null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/**  DetectContactHandler:InitGroup
		 *
		 * Die Contact Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			if ($debug) echo "\nDetect Contact Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
			$status=false; $status1=false;
			foreach ($config as $oid=>$params)
				{
				$status=$status || GetValue($oid);
				if ($debug) echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
				$moid=$this->getMirrorRegister($oid);                   // $this->Detect_DataID nicht benötigt
				if ($moid !== false) $status1=$status1 || GetValue($moid);
				}
			if ($debug) echo "  Gruppe ".$group." hat neuen Status, Wert im Gerät: ".$status."  Wert im Mirror Register:  ".$status1."\n";
            $statusID=@IPS_GetObjectIDByName("Gesamtauswertung_".$group,$this->Detect_DataID);
			if ($statusID===false) 
                {
                $statusID=CreateVariable("Gesamtauswertung_".$group,0,$this->Detect_DataID,1000, '~Motion', null,false);
                $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
                AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
                IPS_ApplyChanges($archiveHandlerID);
                }
			SetValue($statusID,$status);
            //echo "Gesamtauswertung $statusID auf $status gesetzt.\n";
			return ($statusID);	
			}
			

        /*  DetectContactHandler:register 
         *      registerPerType Contact
         */
        public function register($DetectDeviceHandler,$debug=false)
            {
            $this->registerPerType($DetectDeviceHandler,"Contact",$debug); 
            }
			
		} /* ende class */	


    /**************************************************
     *
     * DetectTemperatureHandler
     *
     * Erweiterung zum DetectHandler den alle Channels verwenden.
     * Nachdem es auch Mirror und Gruppen Register gibt, die wenn sich ein Wert ändert auch upgedatet werden, fasst diese Funktion zusammen.
     * Mit der Config wird in scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php die Funktion IPSDetectTemperatureHandler_GetEventConfiguration upgedatet
     *
     *  Get_Configtype, Get_ConfigFileName
     *  Get_EventConfigurationAuto
     *  Set_EventConfigurationAuto
     *  getMirrorRegister
     *  CreateMirrorRegister
     *  InitGroup
     *
     *****************************************************************/

	class DetectTemperatureHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();			/* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;	
        protected $debug;
		
		protected $Detect_DataID;												/* Speicherort der Mirrorregister, kommt auch in der abstrract class zur Anwendung, daher protected */ 

		/**
		 * @public
		 *
		 * Initialisierung des DetectHumidityHandler Objektes
		 *
		 */
		public function __construct($debug=false)
			{
			/* Customization of Classes */
            $this->debug=$debug;
			self::$configtype = '$eventTempConfiguration';                                          /* <-------- change here */
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
			$moduleManagerCC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
			$name="Temperatur-Auswertung";
			$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($mdID==false)
				{
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script. ");
				}			
			$this->Detect_DataID=$mdID;			
			if ($debug) echo "DetectTemperatureHandler construct set Detect_DataID=$mdID.\n";
			parent::__construct();			
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("Temperatur");
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}

		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}	

		/* obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
				if ( function_exists('IPSDetectTemperatureHandler_GetEventConfiguration') )
					{
					self::$eventConfigurationAuto = IPSDetectTemperatureHandler_GetEventConfiguration();       /* <-------- change here */
					}
				else
					{
					self::$eventConfigurationAuto = array();					
					}						
				}
			return self::$eventConfigurationAuto;
			}

		/**
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
		   	self::$eventConfigurationAuto = $configuration;
			}

		/**
		 * getMirrorRegister für Temperature
		 * 
		 */

		public function getMirrorRegister($variableId, $debug = false)
			{
            if ($debug) echo "      DetectTemperatureHandler::getMirrorRegister($variableId  \n";
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "      DetectTemperatureHandler Fehler, getMirrorRegister for Temperature $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            //else echo "getMirrorRegister for Temperature $variablename\n";
            return ($mirrorID);
            }
		
        /**
		 * Das DetectTemperaturHandler Spiegelregister anlegen
		 * 
		 */

		public function CreateMirrorRegister($variableId,$debug=false)
			{
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,2,$this->Detect_DataID,10, '~Temperature', null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/* DetectTemperatureHandler::InitGroup
		 *
		 * Die Temperature Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			if ($debug) echo "\nDetect Temperature Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
            $statusID=$this->InitGroupPerName($group,$debug);
			return ($statusID);

			/*  $config=$this->ListEvents($group);
			$status=(float)0; $status1=(float)0; $i=0;
			foreach ($config as $oid=>$params)
				{
				$status=$status + GetValue($oid);
				if ($debug) echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),70)."Status: ".GetValue($oid)." ".$status."\n";
				$moid=$this->getMirrorRegister($oid);
				if ($moid !== false) $status1=$status1 + GetValue($moid);
                $i++;
				}
            $status=$status/$i;
            $status1=$status1/$i;
			if ($debug) echo "Gruppe ".$group." hat neuen Status, Wert im Gerät: ".$status."  Wert im Mirror Register:  ".$status1."\n";
            $statusID=@IPS_GetObjectIDByName("Gesamtauswertung_".$group,$this->Detect_DataID);
			if ($statusID===false) 
                {
                $statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->Detect_DataID,1000, '~Temperature', null,false);
                $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
                AC_SetAggregationType($archiveHandlerID,$statusID,0);      
                IPS_ApplyChanges($archiveHandlerID);
                }
			SetValue($statusID,$status);
            //echo "Gesamtauswertung $statusID auf $status gesetzt.\n";
			return ($statusID);			*/
			}
			


        /*  DetectTemperatureHandler::register 
         *      registerPerType Temperature
         */
        public function register($DetectDeviceHandler,$debug=false)
            {
            $this->registerPerType($DetectDeviceHandler,"Temperatur",$debug); 
            }

        /*  DetectTemperatureHandler::registerMirror 
         *  komplexere Routine in EvaluateHardware für Liste Events , übernimmt das Mirroregister in die Konfiguration !!!
         *  generiert zuätzliche Einträge, kontrolliert ob Fehler sind, und wenn nicht keine Übernahme
         *
         *  noch nicht vollständig kopiert
         *      vereinheitlichen mit 
         */
        public function registerMirror($DetectDeviceHandler,$debug=false)
            {
            $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();
            $eventTempConfig=$this->Get_EventConfigurationAuto(); 

            $groups=$this->ListGroups("Temperatur");       /* Type angeben damit mehrere Gruppen aufgelöst werden können */
            $events=$this->ListEvents();
            if ($debug) echo "----------------Liste der DetectTemperature Events durchgehen:\n";
            $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            foreach ($events as $oid => $typ)
                {
                if ($debug) echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
                $moid=$this->getMirrorRegister($oid);
                if ($moid !== false) 
                    {
                    $mirror = IPS_GetName($moid);    
                    // überwacht ob Logging im Spiegelregister erfolgt
                    $werte = @AC_GetLoggedValues($archiveHandlerID,$moid, time()-60*24*60*60, time(),1000);
                    if ($werte === false) echo "        Kein Logging für Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).")\n";  
                    // überwacht ob die oid registriert ist
                    if (isset($eventDeviceConfig[$oid])===false) echo "        DetectDeviceHandler->Get_EventConfigurationAuto() ist für $oid false  \n";
                    if (isset($eventTempConfig[$oid])===false)   echo "        DetectTemperatureHandler->Get_EventConfigurationAuto() ist für $oid false  \n";
                    if (IPS_ObjectExists($oid)===false)          echo "        IPS_ObjectExists($oid) ist false \n";   

                    $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Temperature');		            // <- hier anpasen
                    // das Mirror Register in die Konfiguration schreiben
                    $this->RegisterEvent($oid,"Temperatur",'','mirror->'.$mirror);     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben , Mirror Register als Teil der Konfig*/
                    //$result=$DetectDeviceHandler->RegisterEvent($oid,'Topology','','Temperature,Mirror->'.$mirror,false, true);	        	/* par 3 config overwrite, Mirror Register als Zusatzinformation, nicht relevant */

                    if ($result) { if ($debug) echo "   *** register Event $moid: $typ\n"; }
                    }
                }
            if ($debug) echo "----------------Liste der DetectTemperature Groups durchgehen:\n";
            //print_r($groups); 
            foreach ($groups as $group => $entry)
                {
                $soid=$this->InitGroup($group);
                if ($debug) echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
                $result=$DetectDeviceHandler->RegisterEvent($soid,'Topology','','Temperature');		
                if ($result) { if ($debug) echo "   *** register Event $soid\n"; }
                }	
            }


        /*  DetectTemperatureHandler::registerMirrorByID 
         *  DetectDeviceHandler kümmert sich um die Bearbeitung von MirrorID im Zusammenhang mit der Topologie
         *
         *  komplexere Routine in EvaluateHardware für Liste Events , übernimmt das Mirroregister in die Konfiguration !!!
         *  generiert zuätzliche Einträge, kontrolliert ob Fehler sind, und wenn nicht keine Übernahme
         *
         */
        public function registerMirrorByID($DetectDeviceHandler,$oid,$debug=false)
            {
            $eventDeviceConfig=$DetectDeviceHandler->Get_EventConfigurationAuto();
            $eventTempConfig=$this->Get_EventConfigurationAuto(); 
            $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            if ($debug) echo "     ".$oid."  ".IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."\n";
            $moid=$this->getMirrorRegister($oid);
            if ($moid !== false) 
                {
                $mirror = IPS_GetName($moid);    
                // überwacht ob Logging im Spiegelregister erfolgt
                $werte = @AC_GetLoggedValues($archiveHandlerID,$moid, time()-60*24*60*60, time(),1000);
                if ($werte === false) echo "        Kein Logging für Spiegelregister $moid (".IPS_GetName($moid).".".IPS_GetName(IPS_GetParent($moid)).")\n";  
                // überwacht ob die oid registriert ist
                if (isset($eventDeviceConfig[$oid])===false) echo "        DetectDeviceHandler->Get_EventConfigurationAuto() ist für $oid false  \n";
                if (isset($eventTempConfig[$oid])===false)   echo "        DetectTemperatureHandler->Get_EventConfigurationAuto() ist für $oid false  \n";
                if (IPS_ObjectExists($oid)===false)          echo "        IPS_ObjectExists($oid) ist false \n";   

                $result=$DetectDeviceHandler->RegisterEvent($moid,'Topology','','Temperature');		            // <- hier anpasen
                // das Mirror Register in die Konfiguration schreiben
                $this->RegisterEvent($oid,"Temperatur",'','mirror->'.$mirror);     /* par2 Parameter frei lassen, dann wird ein bestehender Wert nicht überschreiben , Mirror Register als Teil der Konfig*/

                if ($result) { if ($debug) echo "   *** register Event $moid\n"; }
                }
            }                 


		}	/* ende class */
		

    /*****************************************************************************************************************
    *
    *  DetectHeatControl, das sind die Stellwerte des Ventils auf den Aktuatoren
    *  wenn sie sich ändern auch ein Abbild in IP Symcon schaffen
    *
    *       _construct
    *       Get_Configtype, Get_ConfigFileName
    *       Get_EventConfigurationAuto,Set_EventConfigurationAuto
    *       getMirrorRegister,CreateMirrorRegister
    *       InitGroup
    */

	class DetectHeatControlHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				

		protected $Detect_DataID;												/* Speicherort der Miorrorregister */ 

		/**
		 * @public
		 *
		 * Initialisierung des DetectHeatControlHandler Objektes
		 *
		 */
		public function __construct()
			{
			/* Customization of Classes */
			self::$configtype = '$eventHeatConfiguration';                                          /* <-------- change here */
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
			$moduleManagerCC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
			$name="HeatControl-Auswertung";
			$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($mdID==false)
				{
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script. ");
				}			
			$this->Detect_DataID=$mdID;
			
			parent::__construct();			
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("HeatControl");
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}				

		/* DetectHeatControlHandler, obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
				if ( function_exists('IPSDetectHeatControlHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectHeatControlHandler_GetEventConfiguration();       /* <-------- change here */
				else self::$eventConfigurationAuto = array();					
				}
			return self::$eventConfigurationAuto;
			}

        /* die Power Konfiguration einheitlich auslesen. Für Logging relevant */

        function get_PowerConfig()
            {
            $configs = $this->ListConfigurations();
            //print_r($configs);
            $powerConfig=array();
            foreach ($configs as $oid => $config)
                {
                if (isset($config["Config"]["Power"]))$powerConfig[$oid] = $config["Config"]["Power"];
                }
            //print_R($powerConfig);
            if (sizeof($powerConfig)==0)        // nix gespeichert, vielleicht auf einem anderen Platz
                {
                if (function_exists('get_IPSComponentHeatConfig'))
                    {
                    $config=get_IPSComponentHeatConfig();
                    if (isset($config["HeatingPower"]))
                        {
                        foreach ($config["HeatingPower"] as $oid=>$power) 
                            {
                            $powerConfig[$oid]=$power;
                            }
                        } 
                    }
                }
            return ($powerConfig);
            }

		/**
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}
			

		/**
		 * Das DetectHeatControlHandler Spiegelregister anlegen
		 * 
		 */

		public function CreateMirrorRegister($variableId,$debug=false)			/* für DetectHeatControl */
			{
            $variablename=$this->getMirrorRegisterName($variableId);
			$mirrorID=@IPS_GetObjectIDByName($variablename,$this->Detect_DataID);		/* Ort der Spiegelregister */
            if ($debug) echo "CreateMirrorRegister : Input $variableId $variablename $mirrorID \n";
			if ($mirrorID !== false) $powerID=@IPS_GetObjectIDByName($variablename."_Power",$mirrorID); else $powerID=false;
			if ( ($mirrorID===false) || ($powerID===false) )		
				{	
				if ($debug) echo "  Spiegelregister noch nicht erzeugt, manchmal wenn wenig Bewegung ist werden sie nicht rechtzeitig vor den Gruppenabfragen erzeugt \n";
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

				$variableLogID=CreateVariable($variablename,1,$this->Detect_DataID, 10, "~Intensity.100", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
				AC_SetLoggingStatus($archiveHandlerID,$variableLogID,true);
				AC_SetAggregationType($archiveHandlerID,$variableLogID,0);      /* normaler Wwert */
				$energyID=CreateVariable($variablename."_Energy",2,$variableLogID, 10, "~Electricity", null, null );  /* 1 steht für Integer, 2 für Float, alle benötigten Angaben machen, sonst Fehler */
				AC_SetLoggingStatus($archiveHandlerID,$energyID,true);
				AC_SetAggregationType($archiveHandlerID,$energyID,0);      /* normaler Wwert */
				$powerID=CreateVariable($variablename."_Power",2,$variableLogID,10, '~Power', null,false);
				AC_SetLoggingStatus($archiveHandlerID,$powerID,true);
				AC_SetAggregationType($archiveHandlerID,$powerID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				$timeID=CreateVariable($variablename."_ChangeTime",1,$variableLogID, 10, "~UnixTimestamp", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
				if (GetValue($timeID) == 0) SetValue($timeID,time());
				}
			return ($mirrorID);			
			}

		/**
		 *
		 * Die HeatControl Gesamtauswertung_ Variablen berechnen und erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			if ($debug) echo "\nDetect HeatControl Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
            $power = $this->CalcGroup($config);
            /* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
            $statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->Detect_DataID,1000, "~Power", null, null);
			//$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->Detect_DataID,1000, '~Power', null,false);
            echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID."\n";
            SetValue($statusID,$power);
			
  			$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
			IPS_ApplyChanges($archiveHandlerID);
			return ($statusID);			
			}			
			
		/**
		 *
		 * Die HeatControl Gesamtauswertung_ Variablen berechnen 
		 *
		 */
		function CalcGroup($config,$debug=false)
			{
			//if ($debug) echo "\nCalcgroup ".json_encode($config)."\n";
            $status=(float)0;                       // Status zusammenzählen
            $power=(float)0;                        // Leistung zusammenzählen
            $count=0;
			foreach ($config as $oid=>$params)
				{
                $mirrorID=$this->getMirrorRegister($oid);
                $variablename=IPS_GetName($mirrorID);
                $mirrorPowerID=@IPS_GetObjectIDByName($variablename."_Power",$mirrorID);                    
                //$status+=GetValue($oid);                  // unterschiede Integer und Power
                if ($mirrorID) $status+=GetValue($mirrorID);
                if ($mirrorPowerID) $power+=GetValue($mirrorPowerID);
                $count++;                
                /* Ausgabe der Berechnung der Gruppe */
                if ($debug) 
                    {
                    echo "    OID: ".$oid;
                    //echo " Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)                
                    echo " Name: ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),50);
                    echo "Status (LEVEL | POWER) ".str_pad(GetValue($oid),10)." ".str_pad($status,10)." | ";
                    if ($mirrorPowerID) echo str_pad(GetValue($mirrorPowerID),10);
                    else echo "   0  ";
                    echo "   ".$power."\n";
                    }
				}
            if ($count>0) { $status=$status/$count; }
            echo "Gruppe hat neuen Status : ".$status." | ".$power."\n";                
            /* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
			return ($power);			
			}					
			
		}


    /******************************************************************************************************************/

	class DetectHeatSetHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				

		protected $Detect_DataID;												/* Speicherort der Miorrorregister */ 

		/**
		 * @public
		 *
		 * Initialisierung des DetectHeatSetHandler Objektes
		 *
		 */
		public function __construct()
			{
			/* Customization of Classes */
			self::$configtype = '$eventHeatSetConfiguration';                                          /* <-------- change here */
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			
			$moduleManagerCC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$CategoryIdData     = $moduleManagerCC->GetModuleCategoryID('data');
			$name="HeatSet-Auswertung";
			$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($mdID==false)
				{
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script. ");
				}			
			$this->Detect_DataID=$mdID;
			
			parent::__construct();			
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (true);
            }

        public function getGroupIdentifier()
            {
            return ("HeatSet");                     // but no group management
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}				

		/* DetectHeatSetHandler, obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
                if ( function_exists('IPSDetectHeatSetHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectHeatSetHandler_GetEventConfiguration();       /* <-------- change here */
				else self::$eventConfigurationAuto = array();					
                //echo "GetEventConf\n"; print_R(self::$eventConfigurationAuto);
				}
			return self::$eventConfigurationAuto;
			}

		/**
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}
			

		/** getMirrorRegister für HetSet
		 * 
         *  sucht den Namen des Spiegelregister für eine Variable oder eine Variable eines Gerätes
		 *  wenn der Name Bestandteil der Config, dann diesen nehmen
         *  sonst schauen ob der Parent eine Instanz ist, dann den Namen der Instanz nehmen, oder
         *  wenn der Name der Variablen CAM_Motion ist, dann auch den Name des Parent nehmen, oder
         *  wenn beides nicht den Namen der Variablen nehmen
         *  
         * am Ende wird der Name der Variable im entsprechenden Datenbereich gesucht. Wenn nicht vorhanden wird die OID des aktuellen Registers zurückgegeben
         *      $this->MoveAuswertungID         Spiegelregister, schnell, Standard
         *      $this->motionDetect_DataID      Spiegelregister, zusätzliches Register geglättet mit Delay
         *
         */

		public function getMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectHeatSetHandler::getMirrorRegister($variableId) aufgerufen.\n";
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "      DetectHeatSetHandler Fehler, getMirrorRegister for HeatSet $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            //else echo "getMirrorRegister for Temperature $variablename\n";
            return ($mirrorID);
            }

		/**
		 * Das DetectbrightnessHandler Spiegelregister anlegen
		 * 
		 */
			
		public function CreateMirrorRegister($variableId,$debug=false)
			{
            if ($debug) echo "   DetectHeatSetHandler::CreateMirrorRegister in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).").\n";    

            $variablename=$this->getMirrorRegisterName($variableId);
            $result=IPS_GetObject($variableId);             
			$mirrorID=@IPS_GetObjectIDByName($variablename,$this->Detect_DataID);		/* das sind die geglätteten Register mit Delay */
			if ($mirrorID===false)			
				{	// Spiegelregister noch nicht erzeugt
				$mirrorID=CreateVariable($variablename,2,$this->Detect_DataID,10, '~Temperature', null,false);
				$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
				AC_SetLoggingStatus($archiveHandlerID,$mirrorID,true);
				AC_SetAggregationType($archiveHandlerID,$mirrorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				}
			return ($mirrorID);			
			}

		/**
		 *
		 * Die HeatSetGesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			echo "\nDetect HeatSet Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
			$status=(float)0; $status1=(float)0; $i=0;
			foreach ($config as $oid=>$params)
				{
				$status=$status + GetValue($oid);
				if ($debug) echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),70)."Status: ".GetValue($oid)." ".$status."\n";
				$moid=$this->getMirrorRegister($oid);
				if ($moid !== false) $status1=$status1 + GetValue($moid);
                $i++;
				}
            $status=$status/$i;
            $status1=$status1/$i;
			echo "  Gruppe ".$group." hat neuen Status, Wert: ".(integer)$status."  im Mirror register:  ".(integer)$status1."\n";
			$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->Detect_DataID,1000, '~Temperature', null,false);
			SetValue($statusID,$status);
			
  			$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
			IPS_ApplyChanges($archiveHandlerID);
			return ($statusID);			
			}
	
			
			
			
		}

    /*****************************************************************************************************************
     *
     * TestMovement -> DetectEventListHandler -> DetectEventHandler -> DetectHandler
     *
     * DetectHandler ist abstract, DetectEventHandler ist prozedural, $eventlist ist der objektorientierte Teil von DetectEventListHandler
     * TestMovement ist die Darstellung 
     *
     * Vorliegende Funktionen:
     *      __construct                     DetectMovement_Configuration.inc.php , $eventConfiguration einlesen
     *      is_groupconfig
     *      Get_Configtype
     *      Get_ConfigFileName
     *      Get_EventConfigurationAuto
     *      Set_EventConfigurationAuto
     *      CreateMirrorRegister
     *      convertParams
     *      getGroupClasses
     *      getAllGroups
     *
     */
	class DetectEventHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				

		/**
		 * @public
		 *
		 * Initialisierung des DetectEventHandler Objektes
		 *
		 */
		public function __construct()
			{
			/* Customization of Classes */
			self::$configtype = '$eventConfiguration';                                          /* <-------- change here */
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			parent::__construct();			
			}

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (false);
            }

		/* Customization Part */
		
		function Get_Configtype()
			{
			return self::$configtype;
			}
		function Get_ConfigFileName()
			{
			return self::$configFileName;
			}				

		/* DetectEventHandler, obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto($debug=false)
			{
            if ($debug) echo "DetectEventHandler::Get_EventConfigurationAuto \n";
			if (self::$eventConfigurationAuto == null)
				{
                //IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                if ( function_exists('IPS'.get_class($this).'_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectEventHandler_GetEventConfiguration();       /* <-------- change here */
				else self::$eventConfigurationAuto = array();					
                //echo "GetEventConf\n"; print_R(self::$eventConfigurationAuto);
				}
			return self::$eventConfigurationAuto;
			}

		/**
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}
			
		public function CreateMirrorRegister($variableId,$debug=false)          // because of abstract class
			{
            }

        /* fuer StoreEventConfiguration
         */
        function convertParams($params,$debug=false)
            {
            //print_r($params);
            $ipsOps = new ipsOps();
            $update = "array(";
            $config["ident"]=20;$config["start"]=false;
            $ipsOps->serializeArrayAsPhp($params,$update,0,$config,$debug);           // debug is true, depth muss 0 sein
            //$update .= "),";
            return ($update);
            }            

        /*
         * return array of classnames
         */
        public function getGroupClasses($parentclass="DetectHandler",$debug=false)
            {
            if ($debug) echo "add group Configurations from:\n";
            $classes = new phpOps();
            $classes->load_classes();                       // alle Klassen
            $classes->filter_classes($parentclass);         // Klassen mit Parent
            $output = $classes->get_classes();              // Ergebnis für foreach übergeben

            //print_r($output);
            $result=array();
            foreach ($output as $className => $items)
                {
                $config=false;
                if ($className != ".")
                    {
                    if ($debug) echo "  ".str_pad($className,30);
                    if ( (class_exists($className)) && (method_exists($className,"Get_EventConfigurationAuto")) )
                        {
                        $groupConfig = new $className();
                        if ($groupConfig->is_groupconfig())
                            {
                            if ($debug) echo "available ";
                            $result[]=$className;
                            }
                        }
                    if ($debug) echo "\n";
                    }
                }
            return $result;
            }


        /* DetectEventHandler::getAllGroups
         * php Funktionen nutzen, alle Klassen auslesen, die Klassen die parentclass als parent haben rausfiltern
         * von denen bleiben dann nur die auch eine Eventkonfiguration haben
         */
        public function getAllGroups($parentclass="DetectHandler",$debug=false)
            {
            if ($debug) echo "add group Configurations from:\n";
            $classes = new phpOps();
            $classes->load_classes();                       // alle Klassen
            $classes->filter_classes($parentclass);         // Klassen mit Parent
            $output = $classes->get_classes();              // Ergebnis für foreach übergeben

            //print_r($output);
            $result=array();
            foreach ($output as $className => $items)
                {
                $config=false;
                if ($className != ".")
                    {
                    if ($debug) echo "  ".str_pad($className,30);
                    if ( (class_exists($className)) && (method_exists($className,"Get_EventConfigurationAuto")) )
                        {
                        $groupConfig = new $className();
                        if ($groupConfig->is_groupconfig())
                            {
                            if ($debug) echo "available ";
                            $config = $groupConfig->Get_EventConfigurationAuto();
                            }
                        }
                    if ($debug) echo "\n";
                    }
                if ($config) 
                    {
                    //print_R($config);
                    foreach ($config as $event => $params)
                        {
                        $result[$event][$className]=$params;
                        }
                    }
                }
            return $result;
            }

        /*
         * DetectHandler has children classes for Temperature, Humidity, Movement aso
         * go through all classes that are childrens to DetectHandler and have group attributes
         * first get all groups of one class and then all events of one group
         *
         * Inputvariablen
         *      $eventlist              Messagehandler Events
         *      $eventDeviceConfig       = $channelEventList->Get_Eventlist();
         *      $coids                  Devicelist Info with key channel oids
         *
         * varlogData               Verknüpfung oid mit logging variable will be created from class eventlist
         * if there is an association between a logging variable and the source event, than switch to logging varaiable
         * if varlogData        show oid name valueifformatted lastchanged      
         * ifnot                show oid name groupconfig
         * if member eventDeviceconfig also show  eventdeviceconfig
         * if topo of coids show the topo path, show * if it is not a logging var
         * check eventlistdata
         *
         */
        public function showGroupClasses(DetectEventListHandler $eventList, $eventDeviceConfig, $coids)
            {
            $eventListData = $eventList->getEventlist();
            $varlogData=array();
            foreach ($eventListData as $oid => $entries)
                {
                if (isset($entries["LoggingInfo"]["variableLogID"])) $varlogData[$oid]=$entries["LoggingInfo"]["variableLogID"];
                }                
            $classnames = $this->getGroupClasses("DetectHandler");
            foreach ($classnames as $classname)
                {
                echo "\n";
                echo "Jetzt $classname hereinholen:\n";								
                $sensorHandler = new $classname();
                //$eventHumidityConfig=$sensorHandler->Get_EventConfigurationAuto();          // alle feuchtigkeitsregister und ihre Zuordnung zu einer Gruppe (Parameter 2)
                //print_R($eventHumidityConfig);
                //$groups=$DetectHumidityHandler->ListGroups("Humidity");
                $groups=$sensorHandler->ListGroups($sensorHandler->getGroupIdentifier());
                //print_r($groups);
                foreach ($groups as $group => $entry)           // die einzelnen Gruppen durchgehen
                    {
                    echo "Gruppe $group :\n";
                    $config=$sensorHandler->ListEvents($group);
                    //print_R($config);                     // Register die zu dieser Gruppe gehören ausgeben, sollten dem selben Variablentyp angehören   
                    foreach ($config as $coid=>$subentry)
                        {       // da brauchen wir die Components Info
                        if (isset($varlogData[$coid])) $variableLogId=$varlogData[$coid];
                        else $variableLogId=$coid;
                        $lastchanged=date("d.m.Y H:i:s",IPS_GetVariable($variableLogId)["VariableChanged"]);
                        $duration = time()-IPS_GetVariable($variableLogId)["VariableChanged"];
                        if (isset($varlogData[$coid]))
                            {
                            echo "   ".str_pad($coid."/".$varlogData[$coid],20).str_pad(IPS_GetName($varlogData[$coid]),55);
                            echo str_pad(GetValueIfFormatted($varlogData[$coid]),10);
                            echo nf($duration, "s",10);                 // better since time
                            }
                        else echo "   ".str_pad($coid,10).str_pad(IPS_GetName($coid),55)."$subentry";                    
                        if (isset($coids[$variableLogId]["QUALITY"])) echo " ".str_pad("QI: ".nf($coids[$variableLogId]["QUALITY"],1),12);
                        if (isset($eventDeviceConfig[$variableLogId])) 
                            {
                            echo str_pad(json_encode($eventDeviceConfig[$variableLogId]),80);
                            }
                        if (isset($coids[$varlogData[$coid]]["TOPO"]["SIMPLE"])) echo $coids[$varlogData[$coid]]["TOPO"]["SIMPLE"];
                        elseif (isset($coids[$coid]["TOPO"]["SIMPLE"])) echo $coids[$coid]["TOPO"]["SIMPLE"]." *";                      // special check
                        echo "\n";
                        if (isset($eventlistData[$coid])) print_r($eventlistData[$coid]);
                        }             
                    $soid=$sensorHandler->InitGroup($group);
                    if (isset($eventDeviceConfig[$soid])) echo "   ".str_pad($soid,10).str_pad(IPS_GetName($soid),55).json_encode($eventDeviceConfig[$soid])."\n";
                    else echo "     ".$soid."  ".IPS_GetName($soid).".".IPS_GetName(IPS_GetParent($soid)).".".IPS_GetName(IPS_GetParent(IPS_GetParent($soid)))."\n";
                    }
                }
            }


		}

	/*******************************************************************************
	 *
	 * Class Definitionen DetectDeviceHandler und DetectDeviceListHandler
     *
     * erzegugt die Config Liste IPSDetectDeviceHandler_GetEventConfiguration in scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php
     * das ist die config Liste die die Topology zu Instanzen herstellt, etwas mühsam zum EIngeben, da es oft mehrere Instanzen in einem Gerät gibt
     * für die Zuordnung Geräte zu Topolgie siehe:            IPSDetectDeviceListHandler_GetEventConfiguration 
     *
     *
	 * DetectDeviceHandler extends DetectHandler with
	 *	    __construct
	 *	    Get_Configtyp, Get_ConfigFileName, Get_Topology		gemeinsame (self) Konfigurations Variablen
	 * 	    Get_EventConfigurationAuto, Set_EventConfigurationAuto
	 *	    CreateMirrorRegister                verwendet getMirrorRegisterName, liefert die OID des MirrorRegisters
	 *	    evalTopology
     *
	 *
	 ****************************************************************************************/

    /* intermediate Topology class, wird von DetectDeviceHandler und DetectDeviceListHandler erweitert
     *
	 * von den extended Classes mindestens geforderte Funktionen übergangsmaessig definieren
	 *	abstract function Get_Configtype();
	 *	abstract function Get_ConfigFileName();		
	 *	abstract function Get_EventConfigurationAuto();
	 *	abstract function Set_EventConfigurationAuto($configuration);
	 *	abstract function CreateMirrorRegister($variableId);     
	 *	
	 * zusaetzlich gemeinsam definiert
     *
     *  create_Topology	
     *  create_TopologyChildrens                erzeugt die topologie, rekursiv zu verwenden	
     *  copyUnknownIndexes	
     *  create_TopologyConfigurationFile        die entsprechende function im Config File für die Topologie erstellen : $getTopology User muss einsortieren und die Topologie definieren
     *  create_UnifiedTopologyConfigurationFile  das ergebnis ebenfalls im Configuration File abspeichern, zur kontrolle, kann nicht editiert werden
     *  mergeTopologyObjects                    topologypluslinks erzeugen, ein Abbild der Gesamtkonfiguration
     *  topologyReferences
     *  uniqueNameReference
     *  evalTopology
     */

    class DetectHandlerTopology extends DetectHandler
		{
		protected $topology;            // topologie ist auch in der Children class verfügbar
        protected $ID,$Config;

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;		

        /* is_groupconfig
         *
         */
        public function is_groupconfig()
            {
            return (false);
            }

	    public function Get_Configtype()
			{
			return self::$configtype;
			}

		public function Get_ConfigFileName()
			{
			return self::$configFileName;
			}	

		public function Get_EventConfigurationAuto()
			{
			return self::$eventConfigurationAuto;
			}

		public function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}

		public function CreateMirrorRegister($variableId,$debug=false)
			{
			}

        /* Functions that are common 
         *
         */

        /* DetectHandlerTopology::create_Topology 
         *
         * in webfront als Kategorien entsprechend vom Benutzer angelegter topology config get_Topology() anlegen, entweder true/false oder ein Array mit Parameter
         *      ID      die Startkategorie, wenn false dann den Webfront Path von EvaluateHardware nehmen
         *      Init    wenn true vorher die World Topologie loeschen, 
         *      Use     nur bestimmte Kategorien erstellen Place, Room, DeviceGroup
         * verwendet get_topology von EvaluateHardware_Configuration, wenn nicht vorhanden Abbruch
         *
         * Topology Format ist flat, keine Hierarchie, index ist der Name !!! es braucht mehrere Durchläufe
         *      World
         *          OID     wenn category angelegt wird
         *          Name
         *          Parent
         *          Type
         *          Children array of all parents refering to it
         *
         * es gibt die Möglichkeit die Config abzusetzen mit Childrens, rekursiv aufrufen
         * ruft create_TopologyChildrens auf
         *
         */
        public function create_Topology($input=false,$debug=false)
            {
            if ($debug) echo "create_Topology aufgerufen: Config ".json_encode($input)."\n";
            $this->Config=array();
            if (is_array($input)) 
                {
                if (isset($input["Init"])) $init=$input["Init"];
                else $init=false;
                if (isset($input["ID"])) $ID=$input["ID"];
                else $ID=false;
                if (isset($input["Use"])) $this->Config["Use"]=$input["Use"];
                else $use=false;
                }
            else
                {
                $init=$input;    
                $ID=false;
                $use=false;
                }
            $ipsOps=new ipsOps();                
			if (function_exists("get_Topology") === false)
				{
                echo "******Failure, do not find function get_Topology. Try to include EvaluateHardware_Configuration.\n";
                return (false);
                }
            else
                {
                if ($ID==false)
                    {
                    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
                    if (!isset($moduleManager))
                        {
                        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
                        $moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
                        }
                    //$installedModules = $moduleManager->GetInstalledModules();
                    //print_r($installedModules); 
                    $WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
                    if ($debug) echo "   Webportal EvaluateHardware Datenstruktur installieren in: ".$WFC10_Path." \n";
                    $categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
                    $this->ID=CreateCategory("World",  $categoryId_WebFrontAdministrator, 10);
                    }
                else 
                    {
                    $this->ID=$ID; 
                    if ($debug) echo "   Webportal Topology installieren in: $ID \n";
                    }               
                
                if ($init) 
                    {
                    if ($debug) echo "   Voher Kategorie ".$this->ID." loeschen. nur wenn init true.\n";
                    $ipsOps->emptyCategory($this->ID);
                    }
                $this->topology=array();
                $topology = get_Topology();         //get_Topology ist die Datei im Config File
				$topology["World"]["OID"]=$this->ID;        // Startpunkt, das ist die ID von World, für die Erzeugung von CreateCategoryPath
                $this->create_TopologyChildrens($this->topology,$topology,$debug);           
                }
            return($this->topology);
            }

        /* DetectHandlerTopology::create_TopologyChildrens
         *
         * rekursive function, key wird erweitert, erzeugt uniqueTopology
         *
         * input ist die Sub Topologie die jetzt in topology eingeordnet werden muss
         *      Struktur zumindest Type, Parent und Name, 
         *              optional Config, Childrens oder verschiedene Infos für Startpage Display, neuerdings zusammengefasst in Config
         *
         * gleiche Indexe nicht überschreiben sondern erweitern mit __# # ist eine aufsteigende Zahl
         * es wird nicht mehr vorher alles rüberkopiert, Schritt für Schritt prüfen
         * für jede Input Category wird überprüft
         *      keine __ im Index die werden nur automatisch bei redundanten categories für den Index erzeugt
         *      schon im Zielarray unter selben Index bekannt, daher neuen Category Index vergeben, Übersetzungsarray Eintrag anlegen
         *      hat einen Parent Eintrag
         *
         */
        private function create_TopologyChildrens(&$topology,$input, $debug=false)
            {
            if ($debug) echo "   create_TopologyChildrens aufgerufen.\n";
            $translation=array();                   // zur Umrechnung von gleichen Indexes
            foreach ($input as $category => $entry)
                {
                $oid=false; $gocreate=false;
                if ($debug>1)
                    {
                    echo "    process  $category";
                    if (isset($input[$category]["Parent"])) echo ".".$input[$category]["Parent"]; 
                    if (isset($topology[$category])) echo " redundant ";
                    echo "\n";
                    }
                if (strpos($category,"__")) echo "Warning, found extension characters \"__\" in Index of array. Please avoid.\n";
                
                // zu bearbeitende Category schon im Zielarray unter selben Index bekannt, neuen Index vergeben, der Rest bleibt gleich
                if (isset($topology[$category]))                
                    {
                    $i=0;
                    do {
                        $i++;
                        $categoryIndex = $category."__$i";          
                    } while (isset($topology[$categoryIndex]));                // Category mit __Index schon im Zielarray bekannt, solange weitermachen bis nicht
                    $translation[$category]=$categoryIndex;                     // und speichern als translation, einer pro function Aufruf
                    }
                else $categoryIndex=$category;                    // wenn keine neue categorie Index notwendig, trotzdem beide führen

                if (isset($entry["Parent"])) 
                    {
                    $parent = $entry["Parent"];                                      // Input Array gibt den Parent Index vor
                    if (isset($translation[$parent])) $parentIndex = $translation[$parent];          // eventuell umbenennen, wenn doppelt, translation Tabelle wachst innerhalb einer for routine und gibt an die Childrens weiter
                    else $parentIndex = $parent;
                    $topology[$categoryIndex]["Parent"]=$parentIndex;
                    if ( (isset($topology[$parentIndex]["OID"]) == true) && ($category != "World") )
                        {
                        $parentID=$topology[$parentIndex]["OID"];
                        //$topology[$category]["OID"]=CreateCategoryByName($parentID,$input[$category]["Name"], 10);          // keinen identifier schreiben, ist überfordert mit __
                        if (isset($topology[$parentIndex]["Path"])) $topology[$categoryIndex]["Path"]=$parent.".".$topology[$parentIndex]["Path"];
                        else $topology[$categoryIndex]["Path"]=$parent;
                        if ( (isset($this->Config["Use"])) && (isset($entry["Type"])) )
                            {
                            if (in_array($entry["Type"],$this->Config["Use"])===false ) 
                                {
                                if ($debug>1) echo "                          >> ".$entry["Name"]." Type ".$entry["Type"]." not found in array use : ".json_encode($this->Config["Use"])."\n";
                                }
                            else $gocreate=true;
                            }
                        else $gocreate=true;
                        if ($debug) 
                            {
                            //echo "CreateCategoryPathFromOid(".$input[$category]["Name"].".".$topology[$category]["Path"].",".$this->ID.")\n";
                            echo "     ".str_pad($input[$category]["Name"].".".$topology[$categoryIndex]["Path"],120)." $categoryIndex \n";
                            }
                        if ($gocreate) $oid = CreateCategoryPathFromOid($input[$category]["Name"].".".$topology[$categoryIndex]["Path"],$this->ID,false);              // true für Debug
                        else $oid="none";
                        $topology[$parentIndex]["Children"][$categoryIndex]=$categoryIndex;                   // den neuen Index nehmen, soll ja ein verweis sein
                        if ($debug>1) echo "      ".$topology[$categoryIndex]["Path"]."\n";
                        }
                    }
                else echo "Warning, no Parent definiert für $category.\n";
                if (isset($entry["OID"])===false) $topology[$categoryIndex]["OID"]=$oid;
                else $topology[$categoryIndex]["OID"]=$entry["OID"];                             // unwahrscheinlich dass schon die OID übergeben wird
                if (isset($entry["Name"])===false) $topology[$categoryIndex]["Name"]=$category;
                else $topology[$categoryIndex]["Name"]=$entry["Name"];
                if (isset($entry["Type"])===false)
                    {
                    if ($category != "World")  echo "Warning no Type definiert für $category.\n";
                    }                
                else $topology[$categoryIndex]["Type"]=$entry["Type"];
                if (isset($entry["Config"])) $topology[$categoryIndex]["Config"]=$entry["Config"];
                else $topology[$categoryIndex]["Config"] = $this->copyUnknownIndexes($entry,["Name","Type","OID","Parent","Childrens"]);
                if (isset($entry["Childrens"]))
                    {
                    if (true)               // auch den Parent Index anpassen
                        {
                        $subinput=array(); $translate=false;
                        foreach ($entry["Childrens"] as $subcategory => $subentry)
                            {
                            // translate index
                            //if (isset($translation[$subcategory])) $subinput[$translation[$subcategory]]=$subentry;
                            //else $subinput[$subcategory]=$subentry;
                            $subinput[$subcategory]=$subentry;
                            $parent = $subinput[$subcategory]["Parent"];
                            if (isset($translation[$parent])) 
                                {
                                $subinput[$subcategory]["Parent"]=$translation[$parent];
                                $translate=true;
                                }
                            //else $subinput[$subcategory]=$subentry;
                            } 
                        if ($translate && $debug) 
                            {
                            echo "Translate Parent Index to new naming\n";
                            print_r($subinput);
                            }
                        }
                    else $subinput=$entry["Childrens"];
                    $this->create_TopologyChildrens($topology,$subinput,$debug) ;
                    } 

                }

            }
        /* von obiger function aufgerufen
         */
        private function copyUnknownIndexes($entry,$excludes)
            {
            $result=array();
            foreach ($entry as $index => $item)
                {
                $found=false;
                foreach ($excludes as $exclude) { if ($exclude==$index) $found=true; }
                if ($found==false) $result[$index] = $item;
                }
            return ($result);
            }

        /* DetectHandlerTopology::create_TopologyConfigurationFile
         *
         * Filename kommt von Get_ConfigFilename also self::$configFileName für Topology : IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php'
         * File mit Filename wird eingelesen und $getTopology gesucht
         * wenn nicht vorhanden wird ein leerer Eintrag erstellt, verwendet dazu insertNewFunction
         */
        public function create_TopologyConfigurationFile($debug=false)
            {
			$fileNameFull = $this->Get_ConfigFileName();
            if ($debug) echo "create_TopologyConfigurationFile: Filename for function get_Topology is $fileNameFull\n";
            if (!file_exists($fileNameFull)) 
                {
                throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);
                }
            $fileContent = file_get_contents($fileNameFull, true);
            $search1='$getTopology = array(';
            $search2='return $getTopology;';
            $pos1 = strpos($fileContent, $search1);
            $pos2 = strpos($fileContent, $search2);

            if ($pos1 === false or $pos2 === false) 
                {
                $posn = $this->insertNewFunction($fileContent);
                if ( $posn !== false )
                    {
                    $configString="\n	 function get_Topology() {\n        ".'$getTopology'." = array(\n".'			"World" 			=>	array("Name" => "World","Parent"	=> "World"),'."\n         );\n       return ".'$getTopology'.";\n	}\n\n";
                    $fileContentNew = substr($fileContent, 0, $posn+1).$configString.substr($fileContent, $posn+1);
                    file_put_contents($fileNameFull, $fileContentNew);							
                    }
                else throw new IPSMessageHandlerException('EventConfiguration File maybe empty !!!', E_USER_ERROR);
                }
            $this->create_Topology();           // input parameter false no init/empty category false no debug
            }

        /* DetectHandlerTopology::create_UnifiedTopologyConfigurationFile
         *
         * get_Topology ist der Gesamtüberblick vom Benutzer erstellt für alle Standorte
         * get_UnifiedTopology ist die Erweiterung um die bekannten Informationen
         *
         * Filename kommt von Get_ConfigFilename also self::$configFileName für Topology : IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php'
         * File mit Filename wird eingelesen und $get_UnifiedTopology gesucht
         * während get_Topology der allgemeine input des users ist, ist $getUnifiedTopology die übergeordnete Topologie, ähnlich der Function devicelist
         *
         */
        public function create_UnifiedTopologyConfigurationFile($topology=false, $debug=false)
            {
            $ipsOps=new ipsOps();                
			$fileNameFull = $this->Get_ConfigFileName();
            if ($debug) echo "create_UnifiedTopologyConfigurationFile: Filename for function get_UnifiedTopology is $fileNameFull\n";
            if (!file_exists($fileNameFull)) 
                {
                throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);           // zumindes das Config File muss es geben
                }
            if (is_array($topology)===false) $topology=$this->topology; 
            
            $comment = "//last time written on ".date("d.m.Y H:i:s")."\n       ";
            $fileContent = file_get_contents($fileNameFull, true);
            $configString="";
            $ipsOps->serializeArrayAsPhp($topology, $configString, 0, 10, false);          // true mit Debug, ConfigString mit Zusatzinformationen anreichern, mit ident 10 anfangen
            //$search1='$getUnifiedTopology = array(';
            $search1='get_UnifiedTopology(';                        // vor dem Kommentarfeld schon zu ersetzen beginnen
            $search2='return $getUnifiedTopology;';
            $pos1 = strpos($fileContent, $search1);
            $pos2 = strpos($fileContent, $search2);

            if ($pos1 === false or $pos2 === false)                 // kommt noch nicht vor, guten Platz zum Einfügen suchen
                {
                if ($debug) 
                    {
                    echo "Not found, new function to be inserted: Search Item \"$search1\" found at $pos1 and Search Item \"$search2\" found at $pos2. \n";
                    }                    
                $posn = $this->insertNewFunction($fileContent);
                if ( $posn !== false )
                    {
                    $configString ="\n	 function get_UnifiedTopology() {\n        ".'$getUnifiedTopology'." = ".$configString."\n       return ".'$getUnifiedTopology'.";\n	}\n\n";
                    $fileContentNew = substr($fileContent, 0, $posn+1).$configString.substr($fileContent, $posn+1);
                    }
                else throw new IPSMessageHandlerException('EventConfiguration File maybe empty !!!', E_USER_ERROR);
                }
			else
				{	
                if ($debug) 
                    {
                    echo "Search Item \"$search1\" found at $pos1 and Search Item \"$search2\" found at $pos2. \n";
                    }
				$fileContentNew = substr($fileContent, 0, $pos1)."get_UnifiedTopology() {        $comment ".'$getUnifiedTopology'." = ".$configString.substr($fileContent, $pos2);
				}
			file_put_contents($fileNameFull, $fileContentNew);

            }


        /* DetectHandlerTopology::mergeTopologyObjects
         *
         * braucht als Topology die unifiedTopology, da nach OID gesucht wird.
         *
         * Verbindung der Topologie mit der Object und instanzen Konfiguration. Ergebnis ist $topologyPlusLinks,  nur einsortieren, keine Links erzeugen, das macht updateLinks
         * übernimmt topology und $objectsConfig, siehe weiter unten, objectsConfig = $DetectDeviceHandler->Get_EventConfigurationAuto();        vulgo aka channelEventList
         *
         * Übergabeparameter
         *  topology            die tatsächliche Topologies aus der config in EvaluateHardware_configuration, eindeutige Keys mit __#, erzeugt references
         *  objectsConfig       die Object/register Config aus dem DetectDeviceHandler, das sind nicht die Geräte sondern die Register
         *                      es gibt dort auch Gesamt register die definiert werden
         *
         * in der objectsConfig gilt, 
         *  Index            Register or Instance OID
         *  Index Subarray:  0 Topology  1 Array of Rooms seperated by , 2 Array of Registertype seperated by , 
         *        optional:  3 ROOM|DEVICE  4     5
         *    
         * 42539 : Zentralzimmer Bewegung[0] => Topology, [1] => Zentralzimmer,  [2] => Brightness
         *
         * Die objectsConfig aka channelEventList der Reihe nach durchgehen, wir haben die OID der Register als Index. Format siehe oben
         *      ich brauch einen Raum oder mehrere, aus der Raumangabe auch mit ~ den uniqueName rausbekommen
         *          für den Raum muss es in topology zumindest eine OID geben
         *          wenn mehrere Räume angegeben wurden, werden die Objekte in allen Räumen angelegt
         *              es können jetzt auch mehrstufige hierarchische Gewerke aufgebaut werden
         *              zB Weather besteht aus Temperatur und Feuchtigkeit
         *          abhängig von den weiteren Parametern, mehr als drei:
         *              wenn mehr als drei ist der vierte Parameter ROOM oder Device, damit wird TOPO befüllt
         *
         *              wenn weniger oder gleich viel als drei Parameter
         *                  es ist ein Gewerk angegeben oder ein Gewerk und eine Übergruppe
         *                      es wird ein OBJECT angelegt
         *                  es ist kein Gewerk angegeben
         *                      es wird eine INSTANCE angelegt
         *
         * in der Topology muss es zumindest den Ort oder die Orte geben, die mit Par 1 übergeben wurde
         * erst nach dem Einsortieren ist klar wieviele Werte pro Raum vorhanden sind
         *
         * topologypluslinks structure, keyindex ist der uniquename der Topologie, Arbeitszimmer
         *
         *
         * Stromheizung Modul installiert, Actuator definieren
         *
         *
        /**
         * Merge two collections of topology objects into a single, consistent collection.
         *
         * This method performs a deep, deterministic merge of two sets of topology objects (arrays or
         * associative structures representing nodes, edges, devices, metadata, etc.). The merge preserves
         * unique objects, combines object properties recursively, and provides configurable conflict
         * resolution for objects that share the same identifier.
         *
         * Merge semantics:
         * - Objects are matched by identifier (either array key or a designated id field). Matching objects
         *   are merged recursively.
         * - For scalar property conflicts, the $options flags determine whether the source overrides the
         *   target or the original value is preserved.
         * - For nested arrays/objects, the merge is performed recursively (deep merge).
         * - If a conflictResolver callback is provided, it is invoked to produce the final object for a
         *   given conflict; otherwise the source object overwrites the target by default.
         *
         * Parameters:
         * @param array $target   Base collection of topology objects to be merged into.
         * @param array $source   Collection of topology objects to merge from.
         * @param array $options  Optional associative array of options:
         *                        - 'idField' (string|null): name of the field used as unique identifier
         *                          inside object entries (default: 'id'). If null, array keys are used.
         *                        - 'preserveKeys' (bool): if true, preserve original array keys when
         *                          possible; otherwise reindex merged numeric keys (default: false).
         *                        - 'preserveScalars' (bool): if true, scalar values from $target are
         *                          preserved and not overwritten by $source (default: false).
         *                        - 'conflictResolver' (callable|null): function(array $targetObj, array $sourceObj, $id)
         *                          : array. If provided, called to resolve conflicts between two objects with the same id.
         *                        - 'mergeCallback' (callable|null): function(array $mergedObj, $id) : void
         *                          called after each successful merge of an object.
         *
         * Return:
         * @return array The merged collection of topology objects.
         *
         * Exceptions:
         * @throws InvalidArgumentException If $target or $source is not an array or if options contain invalid values.
         * @throws RuntimeException If a conflict cannot be resolved (for example, the conflictResolver returns an invalid value).
         *
         * Examples:
         * // Simple merge where objects are matched by 'id' and source wins on conflict:
         * // $merged = mergeTopologyObjects($target, $source, ['idField' => 'id']);
         *
         * // Merge with a custom conflict resolver:
         * // $merged = mergeTopologyObjects($a, $b, [
         * //     'conflictResolver' => function($t, $s, $id) {
         * //         // custom logic to merge/choose properties
         * //         return array_merge($t, $s);
         * //     }
         * // ]);
         *
         * Notes:
         * - Implementations should avoid mutating the original input arrays unless explicitly documented.
         * - The function is intended to be idempotent: repeated merges of the same inputs should produce the same result.
         */
        function mergeTopologyObjects($topologyObject, $channelEventListObject, $debug=false)
            {
            if ($channelEventListObject instanceof DetectDeviceHandler) $objectsConfig = $channelEventListObject->get_Eventlist();
            else $objectsConfig = $channelEventListObject;
            if ($topologyObject instanceof TopologyManagement) $topology = $topologyObject->get_Topology();
            else $topology = $topologyObject;

            if ($debug) echo "mergeTopologyObjects mit Informationen aus einer DetectDeviceHandler Configuration aufgerufen, in die Topologie einsortieren:\n";
            if (isset($this->installedModules["Stromheizung"])) 
                {                                                
                if ($debug>1)echo "    Stromheizung Modul installiert, Actuator definieren.\n"; 
                IPSUtils_Include ("IPSHeat.inc.php",  "IPSLibrary::app::modules::Stromheizung");   
                $ipsheatManager = new IPSHeat_Manager();            // class from Stromheizung
                }
            /* place name kann jetzt redundant sein, index eindeutig aber nicht unbedingt passend zur User Angabe, User nimmt immer den ersten Wert
                * ausser es wird die Baseline mit ~ angegben, name~baseline bedeutet wir suchen einen index dessen Pfad name und baseline enthält
                * reference ist der key der 
                */
            $references = $this->topologyReferences($topology,$debug>1);              // aus einer Topology eine Reference machen welche uniqueNames einem mehrdeutigen Name zugeordnet sind, unter index Path abspeichern
            $text="";                
            $topologyPlusLinks=$topology;               // Topologie ins Ergebnis Array übernehmen
            //echo "Beispiel Waschkueche in topology ausgeben:"; print_r($topology["Waschkueche"]);
            $topoCount=0;
            if ($debug) echo "Register aus objectsConfig der Reihe nach durchgehen:\n";
            foreach ($objectsConfig as $index => $entry)                    // Register der Reihe nach durchgehen, Informationen über den Raum analysieren
                {
                if ($debug) 
                    {
                    $newText=$entry[0]; 
                    for ($i=1;$i<count($entry);$i++) $newText.="|".$entry[$i];                 //entry 0 ist immer Topology, gar nicht erst einmal kontrollieren, für alle Elemente machen, können mehr als 3 sein
                    if ($newText != $text) echo str_pad($index,10).str_pad(IPS_GetName($index),40)." \"$newText\"\n";          // nur die geänderten Zeilen ausgeben
                    $text=$newText;
                    }
                $name=IPS_GetName($index);
                $entry1=explode(",",$entry[1]);		/* Zuordnung Ortsgruppen, es können auch mehrere sein, das ist der Ort zB Arbeitszimmer */
                $entry2=explode(",",$entry[2]);		/* Zuordnung Gewerke, eigentlich sollte pro Objekt nur jeweils ein Gewerk definiert sein. Dieses vorrangig anordnen */

                if ( ($entry[1]!="") && (sizeof($entry1)>0) )           // es wurden ein oder mehrere Räume definiert
                    {
                    $topoCount++;
                    foreach ($entry1 as $entryplace)         // alle Räume durchgehen, es können auch Raumangaben mit einer tilde und einem übergeordneten Ort
                        {
                        $place=$this->uniqueNameReference($entryplace,$references);         // für eine Raumangabe Wohzimmer~LBG70 den uniquename Wohnzimmer__1 finden
                        if ( isset($topology[$place]["OID"]) != true )                      // es gibt den UnqueName oder er wurde gar nicht vorher gefunden
                            {
                            if ($debug) echo "   Fehler, zumindest erst einmal die Kategorie \"$place\" in function get_Topology() von EvaluateHardware_Configuration anlegen.\n";
                            }
                        else        // uniqueName in topology vorhanden
                            {
                            $oid=$topology[$place]["OID"];
                            //print_r($topology[$place]);
                            if (count($entry)>3)                        // wir haben Zusatzparameter, nicht nur Ort und Gewerk, danach kommt noch ROOM oder DEVICE
                                {
                                $entry3=strtoupper($entry[3]);
                                if (isset($entry[4])) $newname = $entry[4];
                                else $newname = IPS_GetName($index);
                                if ($debug) echo "        mergeTopologyObjects mit zusätzlichen Informationen in TOPO anlegen : ".str_pad(IPS_GetName($index)."($index)",50)." => ".$entry[0].",".$entry[1].",".$entry[2].",$entry3,$newname .\n";
                                switch ($entry3)    
                                    {
                                    case "ROOM":
                                        $topologyPlusLinks[$place]["TOPO"][$entry3][$index]=$newname;          // TOPO -> ROOM -> oid of source -> link name
                                        break;
                                    case "DEVICE":
                                        /* welcher Aktuator, schreibt TOPO.DEVICE.name.oid.name
                                            [ArbeitszimmerHue] => Array ( [0] => Array (
                                                    [Name] => ArbeitszimmerHue
                                                    [Type] => Ambient
                                                    [Activateable] => 1
                                                    [Category] => Groups ) )
                                         * anreichern um zusätzliche           
                                         */
                                        $plusLink=array();
                                        if (isset($topology[$place]["Actuators"][IPS_GetName($index)]))
                                            {
                                            if ($debug) echo "      DEVICE,Actuator given as ".IPS_GetName($index).": ";
                                            $configActuators = $topology[$place]["Actuators"][IPS_GetName($index)];         // das Objekt aus der devicelist
                                            foreach ($configActuators as $subindex => $subconfigActuator)                // there is also a port
                                                {
                                                //print_R($subconfigActuator);
                                                if ($subindex=="TopologyInstance")
                                                    {

                                                    }
                                                else    
                                                    {
                                                    $identifier = strtoupper($subconfigActuator["Category"]).strtoupper($subconfigActuator["Type"]);
                                                    $plusLink=$ipsheatManager->checkActuators($subconfigActuator["Name"],$identifier,$debug);
                                                    /* echo "Identifier for Actuator $newname : $identifier";
                                                    switch ($identifier)
                                                        {
                                                        case "GROUPSAMBIENT":
                                                            $soid = $ipsheatManager->GetGroupIdByName($subconfigActuator["Name"]);
                                                            $plusLink[$soid]=$subconfigActuator["Name"];
                                                            $soid = $ipsheatManager->GetGroupAmbienceIdByName($subconfigActuator["Name"]);
                                                            $plusLink[$soid]=$subconfigActuator["Name"]."#ColTemp";
                                                            $soid = $ipsheatManager->GetGroupLevelIdByName($subconfigActuator["Name"]);
                                                            $plusLink[$soid]=$subconfigActuator["Name"]."#Level";
                                                            break;
                                                        case "PROGRAMSPROGRAM":
                                                            $soid = $ipsheatManager->GetProgramIdByName($subconfigActuator["Name"]);
                                                            $plusLink[$soid]=$subconfigActuator["Name"];
                                                            break; 
                                                        case "SWITCHESRGB":
                                                            echo ", ".$subconfigActuator["Name"]."  ";
                                                            $soid = $ipsheatManager->GetSwitchIdByName($subconfigActuator["Name"]);
                                                            $plusLink[$soid]=$subconfigActuator["Name"];
                                                            $soid = $ipsheatManager->GetLevelIdByName($subconfigActuator["Name"]); 
                                                            $plusLink[$soid]=$subconfigActuator["Name"].IPSHEAT_DEVICE_LEVEL;
                                                            $soid = $ipsheatManager->GetColorIdByName($subconfigActuator["Name"]); 
                                                            $plusLink[$soid]=$subconfigActuator["Name"].IPSHEAT_DEVICE_COLOR;
                                                            break;
                                                        default:
                                                            echo "       ->> WARNING, Identifier unknown !!!!";  
                                                            break;                                                     
                                                        }                       // end switch  */
                                                    }
                                                }                       // end foreach
                                            if ($debug) echo " \n";
                                            }                       // end ifset
                                        else $plusLink[$index]=$newname;
                                        $topologyPlusLinks[$place]["TOPO"][$entry3][$newname]=$plusLink;          // TOPO -> ROOM -> oid of source -> link name
                                        break;
                                    default:
                                        break;    
                                    }           // end switch entry3
                                }       // end more entries than 3
                            else                                        // Standardprocedure Topology, Raum|Räume, Gewerk|Gewerk und Übergruppe
                                {
                                $size=sizeof($entry2);              // Gewerk, Type der Register überprüfen
                                if ($entry2[0]=="") $size=0;            // Kein Gewerk angegeben
                                if ($size == 1)         // es wurde ein Gewerk angeben, zB Temperatur, vorne einsortieren 
                                    {	
                                    if ($debug>1) echo "   erzeuge OBJECT Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid).") ".$entry[2]."\n";
                                    //CreateLinkByDestination($name, $index, $oid, 1000);	
                                    //$topologyPlusLinks[$place]["OBJECT"][$entry2[0]][$index]=$name;       // nach OBJECT auch das Gewerk als Identifier nehmen
                                    if (isset($topologyPlusLinks[$place]["OBJECT"][$entry2[0]])) $plusLink = $topologyPlusLinks[$place]["OBJECT"][$entry2[0]];          // Wert wird ergänzt
                                    else $plusLink=array();
                                    if (isset($topology[$place]["Actuators"][IPS_GetName($index)]))
                                        {
                                        if ($debug) echo "      DEVICE,Actuator given as ".IPS_GetName($index).": ";
                                        $configActuators = $topology[$place]["Actuators"][IPS_GetName($index)];         // das Objekt aus der devicelist
                                        foreach ($configActuators as $subindex => $subconfigActuator)                // there is also a port
                                            {
                                            //print_R($subconfigActuator);
                                            if ($subindex=="TopologyInstance")
                                                {

                                                }
                                            else    
                                                {
                                                $identifier = strtoupper($subconfigActuator["Category"]).strtoupper($subconfigActuator["Type"]);
                                                $plusLink=$ipsheatManager->checkActuators($subconfigActuator["Name"],$identifier,$debug);
                                                }
                                            }   	    // ende foreach
                                        if ($debug) echo " \n";
                                        }
                                    else 
                                        {
                                        $plusLink[$index]=$name;
                                        if ($debug) echo "      OBJECT,Sensor ".$entry[2]." given as $index => $name .\n";
                                        }
                                    $topologyPlusLinks[$place]["OBJECT"][$entry2[0]]=$plusLink;          // OBJECT -> TYPE -> oid of source -> link name
                                    }
                                elseif ($size == 2)         // eine zusätzliche Hierarchie einführen, der zweite Wert im Gewerk ist die Übergruppe 
                                    {
                                    if ($debug>1) echo "   erzeuge OBJECT Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid).") ".json_encode($entry[2])."\n";
                                    //CreateLinkByDestination($name, $index, $oid, 1000);	
                                    $topologyPlusLinks[$place]["OBJECT"][$entry2[1]][$entry2[0]][$index]=$name;       // nach OBJECT auch das Gewerk als Identifier nehmen
                                    } 
                                else        // empty size = 0 oder mehr Parameter, eine Instanz, dient nur der Vollstaendigkeit 
                                    {	
                                    if ($debug>1) echo "   erzeuge INSTANCE Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid)."), wird nachrangig einsortiert.".$entry[2]."\n";						
                                    //CreateLinkByDestination($name, $index, $oid, 1000);						
                                    $topologyPlusLinks[$place]["INSTANCE"][$index]=$name;
                                    }
                                }           // end 3 entries
                            }           // end  uniqueName in topology vorhanden
                        }              // end alle Räume durchgehen
                    //print_r($entry1);
                    }
                else                // kein Raum angegeben
                    {
                    $parent = IPS_GetName(IPS_GetParent($index));
                    switch ($parent)
                        {
                        case "HomematicRSSI":               // suppress some known parentIds
                            break;
                        default:
                            if ($debug)
                                {
                                echo "    ***$index, ";
                                echo IPS_GetName($index)."/$parent hat keinen Ort:\"".$entry[1]."\"\n";                    
                                }
                            break;
                        }
                    }
                }  // ende foreach
            if ($debug) echo "   Insgesamt $topoCount Register in die Topologie einsortiert.\n";
            return ($topologyPlusLinks);
            }

        /* DetectHandlerTopology::topologyReferences oder eben die übergeordneten classes wie DetectDeviceHandler und DetectDeviceListHandler
         * die Topology ist mittlerweile unter get_UifiedTopology gespeichert
         * aus einer Topology eine Reference machen welche uniqueNames einem mehrdeutigen Name zugeordnet sind, unter index Path abspeichern
         * Wohnzimmer ist Wohnzimmer.LBG70 und Wohnzimmer.BKS01
         * auch in findRoom und mergeTopologyObjects benutzt.
         *
         * Struktur Topology:
         *      Index  =>  Entry [Name, Path, ...]
         * Struktur references:
         *      Name => Path => Entry
         *
         */
        public function topologyReferences($topology,$debug=false)
            {
            $references=array();
            foreach ($topology as $topindex => $topentry)
                {
                if ($debug) echo "  ".str_pad($topindex,22);
                if (!( (isset($topentry["Name"])) && (isset($topentry["Path"])) )) 
                    {
                    if ($debug) echo "  Warning, topologyReferences, incomplete array, Name or Path ar missing entries.";
                    if ($debug>1) print_R($topentry);          // Eintrag fehlt
                    }
                else 
                    {
                    $references[$topentry["Name"]][$topentry["Path"]] = $topindex;               // für einen namen alle Einträge durchgehen, der wo die baseline im key ist den index übernehmen
                    if ($debug) echo "  ".str_pad($topentry["Path"],42);
                    }
                if ($debug) echo "\n";
                }
            return($references);
            }

        /* DetectHandlerTopology::findRoom
         *
         */
        public function findRoom($instances,$channelEventList,$topology)
            {
            $references = $this->topologyReferences($topology);
                        $room="";
                        foreach ($instances as $instance)       // alle Instanzen aus einem deviceList Eintrag durchgehen und mit $channelEventList abgleichen, eine Rauminformation daraus ableiten, Plausicheck inklusive
                            {
                            $config="";
                            //print_r($channelEventList[$instance["OID"]]);
                            if (isset($channelEventList[$instance["OID"]])) 
                                {
                                $config=json_encode($channelEventList[$instance["OID"]]);
                                if ($channelEventList[$instance["OID"]][1] !="")
                                    {
                                    if ($room == "") 
                                        {
                                        $room=$channelEventList[$instance["OID"]][1];
                                        $entryplace=$this->uniqueNameReference($room,$references);          // BKS01 bei BKS01~Wohnzimmer
                                        }
                                    else
                                        {
                                        if ($room != $channelEventList[$instance["OID"]][1]) echo "!!!Fehler, die Channels sind in unterschiedlichen Räumen. ".$instance["OID"]."  $room != ".$channelEventList[$instance["OID"]][1]."\n";
                                        }
                                    }
                                }
                            //echo "     ".$instance["OID"]."   $config  \n";
                            }


            return($room);
            }

        /* DetectHandlerTopology::uniqueNameReference
         * Raumangabe kann eine Tilde enthalten, entsprechend auflösen
         * References wird aus topology erzeugt, name->reference(path), das heisst unter einem nicht eindeutigen namen sind mehrere Pfade gespeichert, jeder Pfad referenziert auf den unique Name
         * wenn der zweite teil der Tilde nicht im Pfad gefunden wird wird trotzdem der erste Teil als Ergebnis ausgegeben
         * wenn keine tilde wird der ursprüngliche Name wieder als resultat ausgegeben
         * wenn tilde wird der erste name in der references tabelle gesucht, wenn er gefunden wird dann ist das resultat der unique name
         */
        public function uniqueNameReference($entryplace,$references,$debug=false)
            {
                        $placeName=explode("~",$entryplace);        // Raum mit Zusatzangabe Ort im Pfad 
                        if (sizeof($placeName)>1) 
                            {
                            if ($debug>1) echo "Name reference with Baseline Identifier found : $entryplace detected as ".$placeName[0]."~".$placeName[1]."\n";
                            //print_R($references);
                            if (isset($references[$placeName[0]]))
                                {
                                //print_r($references[$placeName[0]]);
                                $found=$placeName[0]; $foundpos=false;
                                foreach ($references[$placeName[0]] as $refname => $reference) 
                                    {
                                    $pos1 = strpos($refname,$placeName[1]); 
                                    if ($debug>1) echo "$refname $pos1  ";
                                    if ($pos1 !==false) 
                                        {
                                        if ( ($foundpos && ($pos1<$foundpos)) || ($foundpos===false)) { $foundpos=$pos1; $found=$reference; }
                                        }
                                    }
                                $place=$found;
                                if ($debug>1) echo "  => $place\n";
                                }
                            else 
                                {
                                echo "Fehler,uniqueNameReference findet ".$placeName[0]." nicht in references for ($entryplace).\n";
                                print_r($references);
                                return (false);
                                }
                            }
                        else $place=$entryplace;

            return ($place);
            }

        /* DetectHandlerTopology::gettopoevents
         * verwendet in Autosteuerung_Installation (?)
         * Ergebnis von evalTopology für ein Objekt, oder irgendeinen Ort. index ist nur eine laufende Nummer. Aber im Entry gibt es Index.
         * Daher im Entry muss dann "Index" definiert sein. Diesen nehmen um einen index für topologypluslinks haben.
         * jetzt schauen ob es dort TOPO ROOM gibt. Und es eine referenz auf Bewegung gibt.
         */
        public function gettopoevents($topologyPlusLinks,$home,$debug=false)
            {
            $result = $this->evalTopology($home,false);          // verwendet topology ohne Erweiterungen, true für Anzeige der topologie
            $object=false;
            $topo="Bewegung";                       // bei TOPO gibt es einheitliche Namen                
            $floorplan=array();
            foreach ($result as $index => $entry)
                {
                if (isset($entry["Index"]))
                    {
                    if (isset($topologyPlusLinks[$entry["Index"]]))
                        {
                        $data=$topologyPlusLinks[$entry["Index"]];
                        if ($debug)
                            {
                            for ($i=0;$i<$entry["Hierarchy"];$i++) echo "   ";
                            echo str_pad($entry["Name"],80-($entry["Hierarchy"]*3))."| ";
                            if ((isset($data["OBJECT"]["Movement"])) && $object)
                                {
                                echo json_encode($data["OBJECT"]["Movement"]);
                                }
                            }
                        if ((isset($data["TOPO"]["ROOM"])) && $topo)
                            {
                            if ($debug) echo json_encode($data["TOPO"]["ROOM"]);
                            foreach ($data["TOPO"]["ROOM"] as $index => $type)
                                {
                                if ($type==$topo) $floorplan[$entry["Name"]]=$index;
                                }
                            }
                        if ($debug) echo "\n";
                        }
                    else echo "Warning, no Data \n";                // OBJECT wurde für den Raum nicht angelegt
                    }
                else "Warning, no Index\n";
                }
            return ($floorplan);
            }


		/* DetectHandlerTopology::evalTopology
		 *
		 * Topologie als Tree darstellen, fuer gettopoevents
         * Der Beginn des Trees muss angegeben werden, kann World sein oder LBG70 oder mehrere wie LBG70, BKS01
		 *
		 */
	    public function evalTopology($lookfor,$output=false)
            {
            $result = array();
            $this->evalTopologyRecursive($lookfor,0,$result,$output);
            return ($result);
            }

        /* DetectHandlerTopology::evalTopologyRecursive
         * recursive function für evalTopology, Result wird für das Ergebnis verwendet
         * $hierarchy zeigt an in welcher Stufe der Hierarchie man gerade recursive ermittelt
         */
	    private function evalTopologyRecursive($lookfor,$hierarchy,&$result,$output)
		    {
    		if (is_array($lookfor)==true ) 
	    		{
		    	//echo "Ist Array. ".$hierarchy."\n";
			    foreach ($lookfor as $item)
				    {
    				//for ($i=0;$i<$hierarchy;$i++) echo "   ";
	    			//echo $item."\n";
		    		$this->evalTopologyRecursive($item,$hierarchy,$result,$output);
			    	}
    			//print_r($lookfor);
	    		}
		    else                // lookfor ist eine Bezeichnung
			    {
    			$goal=$lookfor;	
                if ($output)
                    {
                    for ($i=0;$i<$hierarchy;$i++) echo "   ";
                    echo $goal."\n";
                    }
			    foreach ($this->topology as $index => $entry)
				    {
    				if ($index == $goal) 
	    				{
                        $entry["Index"]=$index;
                        $entry["Hierarchy"]=$hierarchy;
                        $result[]=$entry;
		    			if (isset($entry["Children"]) == true )
			    			{
				    		if ( sizeof($entry["Children"]) > 0 )
    							{
	    						//print_r($entry["Children"]);
		    					$hierarchy++;
			    				$this->evalTopologyRecursive($entry["Children"],$hierarchy,$result,$output);
				    			}
					    	}	
    					}
	    			}
		    	}
    		}           // ende evalTopology

        /* DetectHandlerTopology::analyseTopology
         * $topologyhierarchy ist das Ergebnis, dazu vor Aufruf ein array anlegen. Topology die unified topology.
         * ohne Hierarchie und index im Ergebnis array
         */
        public function analyseTopology(&$topologyhierarchy,$topology,$entryplace)
            {
            foreach ($topology as $place=>$entry)
                {
                if ( ($entry["Parent"]==$entryplace) && ($entry["Name"] !== "World") )          // alle Root Orte herausfinden
                    {
                    //echo "     ".str_pad($place,20)."   \n";
                    $topologyhierarchy[$place]=array();
                    if (isset($entry["Children"]))
                        {
                        $this->analyseTopology($topologyhierarchy[$place],$topology,$place);
                        }
                    }
                }
            }

        /* we use topologyPlusLinks from mergeTopologyObjects and normalize it for writing into a file or database
         * only UUID, Path (Name.Path), OID, Location and ItemData (json encoded full entry) are stored
         */    
        public function normalizeTopologyPlusLinks($topologyPlusLinks)
            {
            $writedata=array(); $line=0;
            foreach ($topologyPlusLinks as $place => $entry)
                {
                if (isset($entry["UUID"])) $writedata[$line]["UUID"]=$entry["UUID"];
                if (isset($entry["Type"])) $writedata[$line]["Type"]=$entry["Type"];

                if ( (isset($entry["Path"])) && (isset($entry["Name"])) )$writedata[$line]["Path"]=$entry["Name"].".".$entry["Path"];
                if (isset($entry["OID"]))           // OID ist die Kategorie in der Topology
                    {
                    $writedata[$line]["OID"]=$entry["OID"];
                    $writedata[$line]["OID-Location"]=IPS_GetLocation($entry["OID"]);
                    }
                if (isset($entry["TopologyInstance"]))           // ist die OID der Topology Instance
                    {
                    $writedata[$line]["TopologyInstance"]=$entry["TopologyInstance"];
                    $writedata[$line]["TopologyInstance-Location"]=IPS_GetLocation($entry["TopologyInstance"]);
                    }                    
                $writedata[$line]["ItemData"]=json_encode($entry);                    
                $line++;
                }
            return($writedata);
            }

        }    // ende class

    /*
     * TopologyManagement
     * reads EvaluateHardware Configuration the function get_UnifiedTopology and creates unified Topology
     * methods: 
     *      __construct             unifiedTopology = get_UnifiedTopology() if available
     *      writeAsHierarchy
     *      writeAsTree
     *      writeleaf   
     *      create_Topology         topology instances are read for filling unifiedTopology
     *      write_EventList         creates EvaluateHardware_Configuration.inc.php with unifiedTopology
     *      Get_Topology            returns topology or unifiedTopology depending on type
     *      updateData              updates unifiedTopology entry with input data, used in createUnifiedTopology
     * 
     *    type: input, unique, unified  
     *
     */
    class TopologyManagement extends DetectDeviceHandler
        {

        public $unifiedTopology=array();
        protected $type="input";
    
        public function __construct($debug=false)
            {
            $dosOps = new dosOps();
            if ($debug) echo "TopologyManagement->DetectDeviceHandler->DetectHandlerTopology -> detectHandler wird aufgerufen.\n"; 
            $fullDir = IPS_GetKernelDir()."scripts\\IPSLibrary\\config\\modules\\EvaluateHardware\\";
            $fullDir = $dosOps->correctDirName($fullDir,false);          //true für Debug
            $result = $dosOps->fileIntegrity($fullDir,'EvaluateHardware_Configuration.inc.php');
            if ($result === false) 
                {
                echo "File integrity of EvaluateHardware_Configuration.inc.php is NOT approved.\n";
                }
            else 
                {
                if ($debug) echo "File integrity of EvaluateHardware_Configuration.inc.php is approved.\n";
                IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');
                }  
            // eine unified Topology laden

	        parent::__construct($debug);
            if (sizeof($this->topology)>0) $this->type="unique";            // nicht eindeutig
            if (function_exists("get_UnifiedTopology")) 
                {
                $this->unifiedTopology       = get_UnifiedTopology(); 
                $this->type="unified";
                }   
            }

        /* objektorientierte Schreibweise für parent Routine
         */
        public function writeAsHierarchy($entryplace)
            {
            $result=array();
            $this->analyseTopology($result,$this->Get_Topology(),$entryplace);
            return ($result);
            }

       public function writeAsTree($entryplace)
            {
            $result=$this->writeAsHierarchy($entryplace);
            $this->writeleaf($result,"");
            }
        
        function writeleaf($result,$indent)
            {
            foreach ($result as $name => $entry)
                {
                $data=$this->unifiedTopology[$name];
                unset($data["Config"]);
                unset($data["Children"]);
                unset($data["TopologyConfig"]);
                unset($data["Parent"]);
                unset($data["Path"]);
                echo str_pad("$indent $name",55).json_encode($data)."  \n";
                if ( (is_array($entry)) && (sizeof($entry)>0) ) $this->writeleaf($entry,$indent."  ");
                }
            }

        /* TopologyManagement::createUnifiedTopology
        * copy from TopologyLibraryManagement, besser hier
        * input ist topology und output ist unifiedTopology (inklusive Overwrite Warning)
        * 
        */
        public function createUnifiedTopology($debug=false)
            {
            $status=true;

            $modulhandling = new ModuleHandling(); 
            $deviceInstances = $modulhandling->getInstances('TopologyDevice',"NAME");
            $roomInstances = $modulhandling->getInstances('TopologyRoom',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            $placeInstances = $modulhandling->getInstances('TopologyPlace',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            $devicegroupInstances = $modulhandling->getInstances('TopologyDeviceGroup',"NAME");       // Formatierung ist eine Liste mit dem Instanznamen als Key
            if ($debug) echo "createUnifiedTopology aufgerufen: TopologyDevice (".sizeof($deviceInstances).") TopologyRoom (".sizeof($roomInstances).") TopologyPlace (".sizeof($placeInstances).") TopologyDeviceGroup (".sizeof($devicegroupInstances).") \n";

            foreach($this->topology as $uniqueName => $entry)
                {
                if (isset($entry["Type"]))
                    {
                    switch ($entry["Type"])
                        {
                        case "Place":
                            $InstanzID = $placeInstances[$uniqueName];
                            break;
                        case "Room":
                            $InstanzID = $roomInstances[$uniqueName];
                            break;
                        case "Device":
                            $InstanzID = $deviceInstances[$uniqueName];
                            break;
                        case "DeviceGroup":
                            $InstanzID = $devicegroupInstances[$uniqueName];
                            break;
                        default:
                            //$InstanzID = @IPS_GetInstanceIDByName($name, $parent);
                            break;
                        }
                    if ($InstanzID) 
                        {
                        $configTopologyDevice=json_decode(IPS_GetConfiguration($InstanzID),true);
                        unset($configTopologyDevice["ImportCategoryID"]);
                        unset($configTopologyDevice["Open"]);
                        unset($configTopologyDevice["UpdateInterval"]);                        
                        if ($debug>1) echo "   ".str_pad($uniqueName,25).json_encode($configTopologyDevice)." \n";
                        $inputdata=array();
                        $inputdata[$uniqueName]["TopologyInstance"]=$InstanzID;
                        $inputdata[$uniqueName]["OID"]=IPS_GetParent($InstanzID);
                        $inputdata[$uniqueName]["UUID"]=$configTopologyDevice["UUID"];
                        $inputdata[$uniqueName]["TopologyConfig"]=$configTopologyDevice;
                        $status=$this->updateData($this->unifiedTopology[$uniqueName],$inputdata[$uniqueName],$uniqueName,$debug);
                        }
                    }
                else echo "createUnifiedTopology, warning, $uniqueName without Type definition.\n";
                }
            return($status);
            }

        public function write_EventList($debug=false)
            {
            $this->create_UnifiedTopologyConfigurationFile($this->unifiedTopology,$debug);
            }

            /* TopologyManagement::addTopologyByDeviceList
             * fügt der unifiedTopology Einträge aus der devicelist hinzu, wenn sie noch nicht vorhanden sind
             * input ist die devicelist
             */
        public function addTopologyInfoByDeviceList(DeviceListManagement $deviceList,$debug=false)
            {
            $status=true;
            $modulhandling = new ModuleHandling();
            $deviceInstances = $modulhandling->getInstances('TopologyDevice',"NAME");
            $deviceListData = $deviceList->read_devicelist();
            foreach ($deviceListData as $uniqueName => $entry)
                {
                if (!isset($this->unifiedTopology[$uniqueName]))
                    {
                    if ($debug) echo "TopologyManagement::addTopologyInfoByDeviceList, adding $uniqueName to unifiedTopology.\n";
                    if (isset( $deviceInstances[$uniqueName]))
                        {
                        $InstanzID = $deviceInstances[$uniqueName];
                        $configTopologyDevice=json_decode(IPS_GetConfiguration($InstanzID),true);
                        unset($configTopologyDevice["ImportCategoryID"]);
                        unset($configTopologyDevice["Open"]);
                        unset($configTopologyDevice["UpdateInterval"]);                        
                        if ($debug>1) echo "   ".str_pad($uniqueName,25).json_encode($configTopologyDevice)." \n";
                        }
                    $inputdata=array();
                    $inputdata[$uniqueName]["Type"]="Device";
                    //print_R($entry);
                    //$inputdata[$uniqueName]["OID"]=$entry["CategoryID"];
                    $inputdata[$uniqueName]["UUID"]=$configTopologyDevice["UUID"];
                    $inputdata[$uniqueName]["Path"]=$configTopologyDevice["Path"];
                    //$inputdata[$uniqueName]["TopologyConfig"]=array();          // leer
                    $status=$this->updateData($this->unifiedTopology[$uniqueName],$inputdata[$uniqueName]);
                    }
                }
            return($status);
            }   


        /* TopologyManagement::Get_Topology
         * überschreibt Methode von DetectDeviceHandler
         *
         * gibt this->topology aus, muss vorher gesetzt werden, mit DetectHandlerTopology::create_Topology gemacht
         */
		public function Get_Topology()
			{
            if ($this->type=="unified") return($this->unifiedTopology);
			return($this->topology);
			}

        public function normalizeTopology()
            {
            $writedata=array(); $line=0;
            foreach ($this->unifiedTopology as $place => $entry)
                {
                //echo str_pad($place,20);
                if (isset($entry["UUID"])) $writedata[$line]["UUID"]=$entry["UUID"];
                if ( (isset($entry["Path"])) && (isset($entry["Name"])) )$writedata[$line]["Path"]=$entry["Name"].".".$entry["Path"];
                if (isset($entry["OID"])) 
                    {
                    $writedata[$line]["OID"]=$entry["OID"];
                    $writedata[$line]["Location"]=IPS_GetLocation($entry["OID"]);
                    }
                $writedata[$line]["ItemData"]=json_encode($entry);
                $line++;
                //echo json_encode($entry);
                //echo "\n";
                }
            return($writedata);
            }

		public function Get_unifiedTopology()
			{
			return($this->unifiedTopology);
			}

        public function Update($input)
            {
            foreach ($input as $uniquename=>$entry)
                {
                $this->unifiedTopology[$uniquename]=$entry;             // alles überschreiben
                }
            }

        }


	/*******************************************************************************
	 *
	 * Class Definitionen DetectDeviceHandler 
     *
     * erzeugt die Config Liste IPSDetectDeviceHandler_GetEventConfiguration in scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php
     * das ist die config Liste die die Topology und Gewerke zu Instanzen und Registern herstellt, etwas mühsam zum Eingeben, da es oft mehrere Instanzen in einem Gerät gibt
     * soll in Zukunft automatisch passieren, über die DeviceListe und IPSDetectDeviceListHandler_GetEventConfiguration kann eine Verbindung hergestellt werden
     *
     * für die Zuordnung Geräte zu Topologie siehe:            IPSDetectDeviceListHandler_GetEventConfiguration 
     *
     *  Geräte
     *      Instanzen
     *          Register
     *
     * Verwendung in: EvaluateHardware
     *
     *      $topology            = $DetectDeviceHandler->Get_Topology();
     *      $channelEventList    = $DetectDeviceHandler->Get_EventConfigurationAuto();        
     *      $deviceEventList     = $DetectDeviceListHandler->Get_EventConfigurationAuto();     
     *      $topologyLibrary->createTopologyInstances($topology);           
     *       $topologyLibrary->sortTopologyInstances($deviceList,$channelEventList,$deviceEventList);           Topologie in Devicelist einsortieren
     *
     *
     * in einem eigenen Bereich von EvaluateHardware werden die Register angelegt
     *
	 * DetectDeviceHandler extends DetectHandlerTopolog extends DetectHandler with
	 *	    __construct
     *      setEventListFromConfigFile
	 *	    Get_Configtyp, Get_ConfigFileName, Get_Topology		gemeinsame (self) Konfigurations Variablen
	 * 	    Get_EventConfigurationAuto, Set_EventConfigurationAuto
	 *	    CreateMirrorRegister                verwendet getMirrorRegisterName, liefert die OID des MirrorRegisters
     *      UpdateRegisterEvent
	 *	    updateLinks
     *
	 *
	 ****************************************************************************************/

	class DetectDeviceHandler extends DetectHandlerTopology
		{

        protected $channelEventList=array();

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;		

		protected $topology;
        protected $ID;

		/* Initialisierung des DetectDeviceHandler Objektes
		 * wir brauchen das config file EvaluateHardware_Configuration mit function get_topology()
		 * wird mit create_Topology eingelesen, wenn die Function noch nicht da ist diese erstellen
		 * create_Topology legt auch gleich die entsprechenden Kategorien an. Default ist in EvaluateHardware entsprechend WFC10_Path
		 *
		 */
		public function __construct($debug=false)
			{
			self::$configtype = '$deviceTopology';
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php';
            if ($this->create_Topology(false,$debug)===false)           // berechnet topology und ID, false wenn get_topology nicht definiert, ID kommt aus Module EvaluateHradware WFC10_Path
                {
                $this->create_TopologyConfigurationFile();          //true for Debug                    
				}
            if ($debug) echo "DetectHandlerTopology wird aufgerufen.\n";    
	        parent::__construct($debug);
			}

        /* setEventListFromConfigFile
        * get Eventlist from config file and store it in class
        * es fehlt die Angabe des include files
        */
        public function setEventListFromConfigFile($debug=false)
            {
            if ($debug) echo "DetectDeviceHandler::setEventListFromConfigFile:\n";
            $this->channelEventList = $this->Get_EventConfigurationAuto();
            }

		public function Get_Configtype()
			{
			return self::$configtype;
			}
			
		public function Get_ConfigFileName()
			{
			return self::$configFileName;
			}	

        /* DetectDeviceHandler::Get_Topology
         * gibt this->topology aus, muss vorher gesetzt werden, mit DetectHandlerTopology::create_Topology gemacht
         */
		public function Get_Topology()
			{
			return($this->topology);
			}
            		

		/* DetectDeviceHandler::Get_EventConfigurationAuto
         * obige variable in dieser Class kapseln, dannn ist sie static für diese Class 
         */
		public function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
				if ( function_exists("IPSDetectDeviceHandler_GetEventConfiguration") == true ) self::$eventConfigurationAuto = IPSDetectDeviceHandler_GetEventConfiguration();
				else 
                    {
                    echo "FEHLER: function IPSDetectDeviceHandler_GetEventConfiguration nicht vorhanden.\n";
                    self::$eventConfigurationAuto = array();
                    }
				}
			return self::$eventConfigurationAuto;
			}

		/* DetectDeviceHandler::Set_EventConfigurationAuto
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		public function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}

        /* DetectDeviceHandler::CreateMirrorRegister
         * nur da damit keine Fehlermeldung, eigentlich egal 
         */
		public function CreateMirrorRegister($variableId,$debug=false)
			{
			}

        /* DetectDeviceHandler::UpdateRegisterEvent 
         * according to deviceList
         */

        public function UpdateRegisterEvent($DetectHandler, $deviceList, $debug=false)
            {
            $configurationDevice    = $this->Get_EventConfigurationAuto();                
            $events=$DetectHandler->ListEvents();         
            foreach ($events as $oid => $typ)
                {
                $rooms=""; 
                $poid=IPS_GetParent($oid);
                $instance=IPS_GetName($poid);
                $nameInstance = explode(":",$instance)[0];		/* Zuordnung Gruppen */
                $typeInstance = explode(":",$instance)[1];		/* Zuordnung Gewerke, eigentlich sollte pro Objekt nur jeweils ein Gewerk definiert sein. Dieses vorrangig anordnen */        
                echo "     $oid/$poid  ".IPS_GetName($oid).".$instance.".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))."    for devicelist name \"$nameInstance\"  and type \"$typeInstance\" \n";
                if (isset($deviceList[$nameInstance])) 
                    {
                    if ($debug) echo "found already in deviceList().";
                    if (isset($deviceList[$nameInstance]["Topology"]))
                        {
                        $first=true;
                        foreach ($deviceList[$nameInstance]["Topology"] as $index => $room) 
                            {
                            if ($first) 
                                {
                                $rooms = $room["ROOM"]; 
                                $first=false; 
                                //print_R($rooms);
                                }
                            else $rooms .= ",".$room["ROOM"];
                            }
                        if ($debug) echo " Available Rooms detected there: \"$rooms\".";
                        }
                    if ($debug) echo "\n";
                    //print_r($deviceList[$nameInstance]);
                    }
                if ($debug) 
                    {
                    if (isset($configurationDevice[$oid])) echo "found already in DetectDeviceHandler\n";
                    if (isset($configurationDevice[$poid])) echo "found already as Parent in DetectDeviceHandler\n";        
                    if (isset($configurationEvent[$oid])) echo "found already in DetectDeviceListHandler\n";        
                    }
                $moid=$DetectHandler->getMirrorRegister($oid);
                if ($moid !== false) 
                    {
                    if ($debug) echo "   *** register Event $moid: $typ\n";  
                    if (isset($configurationDevice[$moid])) 
                        {
                        $roomsConfig=$configurationDevice[$moid][1];                    
                        if ($debug) echo "found already in Mirror DetectDeviceHandler. Rooms are \"$roomsConfig\".\n";
                        if ( ($roomsConfig != $rooms) && ($roomsConfig != "") ) { if ($debug) echo " *** Failure on Rooms Assignment. No Change of Config.\n"; $rooms=$roomsConfig; }
                        }
                    if (isset($configurationEvent[$moid])) 
                        {
                        if ($debug) echo "found already in Mirror DetectDeviceListHandler.\n";           // unwahrscheinlich, es werden nur Topology OIDs registriert
                        }
                    $this->RegisterEvent($moid,'Topology',$rooms,'Brightness');	                                    //registrieren in IPSDetectDeviceHandler_GetEventConfiguration mit dem Gewerk aber ohne Raum ?
                    }      
                if ($debug) echo "\n";
                }                
            }

        /* DetectDeviceHandler::updateLinks
         * prozedurale Routine um Links in die Topology einzufügen
         * Config Array mitgeben, ob Instanzen und LinkFromParent erstellt werden sollen
         *      doinstances = show.instances
         *      doLinkFromParent = show.linkfromparent
         * 
         * aus mergeTopologyObjects wird in Visualization...EvaluateHardware das Webfront LocalData erstellt.
         *
         * topologyPlusLinks Eintrag für Eintrag durchgehen, das sind alle Räume mit uniqueName
         *
         * OID ist bereits auf eine bestimmte Topologie bestehend aus Kategorien festgelegt
         * hier die Links einsortieren, kann auch in anderen Bereichen verwendet werden
         * es werden nur Objekte hinzugefügt
         * den Index als Oif für die Datenquelle nehmen und dem Eintrag OID als Speicherort.
         *
         *  uniqueName =>   Type            ist der place, also Place, Room, DeviceGroup
         *                  Path
         *                  OID             die OID der Kategorie
         *                  OBJECT          Register
         *                      IpsHeatSwitch
         *                  INSTANCE        Instanzen mit untergeordneten Registern
         *                  TOPO
         *                      ROOM        in die Room Instanz verschiedene Objekte hineinverlinken
         *                      DEVICE      für jedes Actuator Device eine Anzahl von Registern hineinverlinken
         *
         *                      ...         in eine Device Group mehrere Actuator Devices hineinverlinken
         *
         *                  Actuators       Liste der Actuator, IPSHeat die es in diesem Raum gibt
         *  
         *  TOPO            wenn Parameter 3 Device oder Room heisst
         *
         *  OBJECTS         Untergruppen anhand Parameter 2 IPSHeat etc
         *      es braucht einen Type (Parameter 2), eine OID und den gewünschten Namen dafür
         *      Routine denkt nicht mehr, was angeführt wurde, wird als Link in der Kategorie wenn vorhanden oder in der Topology Instanz dargestellt, siehe mergeTopology
         *  INSTANCES
         *      nix implementiert         
         */

        public function updateLinks($topologyPlusLinks,$config=false, $debug=false)
            {
            $doInstances=false; $doLinkFromParent=false; $quality=array();
            if (is_array($config))
                {
                if (isset($config["Show"]["Instances"])) $doInstances=$config["Show"]["Instances"];
                if (isset($config["Show"]["LinkFromParent"])) $doLinkFromParent=$config["Show"]["LinkFromParent"];
                if ( (isset($config["Show"]["QualityIndex"])) && (is_array($config["Show"]["QualityIndex"])) ) $quality=$config["Show"]["QualityIndex"];
                }
            foreach ($topologyPlusLinks as $place => $entry)            // $topologyPlusLinks Eintrag für Eintrag durchgehen, das sind alle Räume mit uniqueName
                {
                // input Werte aus dem array darstellen, Zusammenafssung aller TOPO, OBJECT und INSTANCE arrays, INSTANCE wird nur angezeigt wenn keine OBJECT definiert
                if (isset($entry["Type"])) echo "$place (".$entry["Type"].") : ";
                else echo "$place : ";
                $object=false; $instance=false; $topo=false;
                if (isset($entry["TOPO"])) 
                    {
                    echo "  TOPO (".(count($entry["TOPO"])).")";
                    $topo=true;            							//CreateLinkByDestination($name, $index, $oid, 10);
                    }
                if (isset($entry["OBJECT"])) 
                    {
                    echo "  OBJECT (".(count($entry["OBJECT"])).")";
                    $object=true;            							//CreateLinkByDestination($name, $index, $oid, 10);
                    }
                if (isset($entry["INSTANCE"])) 
                    {
                    echo "  INSTANCE (".(count($entry["INSTANCE"])).")";
                    $instance=true;
                    }
                if (isset($entry["Path"])) echo "    ".$entry["Path"];    
                echo "\n";
                if ( (isset($entry["OID"])) && (is_numeric($entry["OID"])) )     // Category, do children hide
                    {
                    $childs=IPS_GetChildrenIDs($entry["OID"]);
                    foreach ($childs as $child) 
                        {
                        $objectType=IPS_GetObject($child)["ObjectType"];
                        if ($objectType==6) IPS_SetHidden($child,true);
                        }
                    }
                if ($topo)          // ROOM oder DEVICE
                    {
                    echo "      TOPO  :\n";
                    foreach ($entry["TOPO"] as $type => $subentry)   // TOPO -> Room -> oid source -> name of link
                        {
                        echo "           $type  :\n";
                        switch ($type)
                            {
                            case "DEVICE":                                  // mehrere Device
                                foreach ($subentry as $devicename => $deviceentry)
                                    {
                                    if (isset($entry["Actuators"]))
                                        {
                                        foreach ($deviceentry as $oid => $name)
                                            {
                                            $objects = @IPS_GetVariable($oid);
                                            if ($objects===false)
                                                {
                                                echo "        $oid   -> ***** Failure, dont know VariableID.\n";
                                                }
                                            else
                                                {
                                                if (isset($entry["Actuators"][$devicename]["TopologyInstance"]))
                                                    {
                                                    $topologyinstance=$entry["Actuators"][$devicename]["TopologyInstance"];         // wird nur hier gebraucht
                                                    echo "              ".str_pad("$oid/$name",55).str_pad(GetvalueIfFormatted($oid),20);
                                                    if (isset($quality[$oid])) echo " QI: ".nf($quality[$oid],1);
                                                    else echo "      ";
                                                    echo " last Update ".date("d.m.y H:i:s",$objects["VariableUpdated"]);
                                                    if ((time()-$objects["VariableUpdated"])>(60*60*24)) echo "   ****** too long time, check !!";
                                                    echo "\n";
                                                    $linkId=CreateLinkByDestination($name, $oid, $topologyinstance, 1000);
                                                    IPS_SetHidden($linkId,false);
                                                    }
                                                else
                                                    {
                                                    echo "Warning, not known ";
                                                    print_R($entry["Actuators"]);
                                                    }	                
                                                }                    
                                            }
                                        }
                                    else 
                                        {
                                        echo "    >>> Warning, no Index Actuators in Topo $place.";
                                        print_R($entry);
                                        }
                                    }
                                break;
                            case "ROOM":                                    // ein Raum
                                $topologyinstance=$entry["TopologyInstance"];
                                foreach ($subentry as $oid => $name)
                                    {
                                    $objects = @IPS_GetVariable($oid);
                                    if ($objects===false)
                                        {
                                        echo "        $oid   -> ***** Failure, dont know VariableID.\n";
                                        }
                                    else
                                        {
                                        echo "              ".str_pad("$oid/$name",55).str_pad(GetvalueIfFormatted($oid),20);
                                        if (isset($quality[$oid])) echo " QI: ".nf($quality[$oid],1);  
                                        else echo "      ";                                          
                                        echo " last Update ".date("d.m.y H:i:s",$objects["VariableUpdated"]);
                                        if ((time()-$objects["VariableUpdated"])>(60*60*24)) echo "   ****** too long time, check !!";
                                        echo "\n";
                                        $linkId=CreateLinkByDestination($name, $oid, $topologyinstance, 1000);
                                        IPS_SetHidden($linkId,false);	                
                                        }                    
                                    }
                                if ($doLinkFromParent)      // Config Parameter $config["Show"]["LinkFromParent"]
                                    {
                                    //wenn es einen Parent mit einer OID gibt dann einen Link dorthin machen mit der aktuellen Instance
                                    $parent = $entry["Parent"];
                                    if (isset($topologyPlusLinks[$parent]["OID"]))
                                        {
                                        $parentId = $topologyPlusLinks[$parent]["OID"];
                                        echo "            Link at Parent, Look for $parent in Kategorie $parentId, Name ".$entry["Name"]." \n";
                                        $linkId=CreateLinkByDestination($entry["Name"], $topologyinstance, $parentId, 10);
                                        IPS_SetHidden($linkId,false);           // anzeigen
                                        }
                                    }
                                break;
                            default:
                                echo "Do not know $type \n";
                                break;
                            }               // end switch
                        }
                    }
                if ($object)            // wenn ein Objekt konfiguriert wurde, alle Einträge dafür durchgehen
                    {
                    foreach ($entry["OBJECT"] as $type => $subentry)
                        {
                        echo "      $type  :\n";                        //IpsHeatSwitch, Temperature etc.
                        foreach ($subentry as $oid => $name)
                            {
                            $objects = @IPS_GetVariable($oid);
                            if ($objects===false)
                                {
                                echo "        $oid   -> ***** Failure, dont know VariableID.\n";
                                }
                            else
                                {
                                echo "              ".str_pad("$oid/$name",55).str_pad(GetvalueIfFormatted($oid),20);
                                if (isset($quality[$oid])) echo " QI: ".nf($quality[$oid],1); 
                                else echo "      ";                                                                   
                                echo " last Update ".date("d.m.y H:i:s",$objects["VariableUpdated"]);
                                if ((time()-$objects["VariableUpdated"])>(60*60*24)) echo "   ****** too long time, check !!";
                                echo "\n";
                                if (is_numeric($entry["OID"])) 
                                    {
                                    $linkId=CreateLinkByDestination($name, $oid, $entry["OID"], 1000);
                                    IPS_SetHidden($linkId,false);	                
                                    }
                                else 
                                    {
                                    if (isset($entry['TopologyInstance'])) 
                                        {
                                        $topologyinstance=$entry["TopologyInstance"];
                                        $linkId=CreateLinkByDestination($name, $oid, $topologyinstance, 1000);
                                        IPS_SetHidden($linkId,false);
                                        }
                                    else echo "Entry OID is not numeric, TopologyInstance not available, probably none and no TOPD Instanz, ie DeviceGroup Entry.\n";
                                    }
                                }                    
                            }
                        }
                    }
                /* vorerst der Übersichtlichkeit wegen keine Instanzen als Link hinzufügen, daher ab jetzt konfigurierbar
                 * [INSTANCE] => Array   (  [10xx] => Hue Smart button 2, ) 
                 */
                if ($instance && $doInstances)             // 
                    {
                    echo "      INSTANCE  :\n";
                    foreach ($entry["INSTANCE"] as $oid => $name)
                        {
                        if (IPS_ObjectExists($oid)===false)
                            {
                            echo "        $oid   -> ***** Failure, dont know Instance ID.\n";
                            }
                        else
                            {
                            echo "        $oid   -> $name\n";
                            if (is_numeric($entry["OID"])) 
                                {
                                $linkId=CreateLinkByDestination($name, $oid, $entry["OID"], 2000);	
                                IPS_SetHidden($linkId,false);                
                                }
                            elseif (isset($entry['TopologyInstance'])) 
                                {
                                $linkId=CreateLinkByDestination($name, $oid, $entry["TopologyInstance"], 2000);
                                IPS_SetHidden($linkId,false);
                                }
                            else echo "Entry OID is not numeric, TopologyInstance not available, probably none and no TOPD Instanz, ie DeviceGroup Entry.\n";
                            }
                        }
                    }
                echo "\n";
                //print_R($entry);
                }       // ende foreach
            }

        /* DetectDeviceHandler::addTopologyByDeviceList   same method in DeviceListManagement
         * channelEventlist update based on devicelist data and aligned with deviceEventList and eventList
         * Data that is not in the devicelist is not changed
         * 
         * Topology in devicelist anlegen
         *      es kommt dazu
         *          UUID, von der Topology Device Konfiguration mit gleichem Namen
         *          Name, der Raumname
         *          Uniquename
         *          Path
         *           
         * devicelist muss ein Instances item haben, die einzelne Instance items haben eine OID von einer Instanz
         * input $deviceEventList, doublecheck $channelEventList
         * in der DeviceList sollte es auch schon unique Elemente geben und den Pfad, hier aber noch einmal auf gleichem Weg erzeugen
         * je nach übergebenen Daten, unterschiedliche Funktionen aufrufen
         *      keine Parameter, alle Topology Devices einlesen, liste mit Namen machen, Konfigurationsdaten auslesen   -> noch nicht unterstützt
         *      nur topology, es muss kann die unified topology sein
         *
         * Todo, nachdem die falsche Datei behalten wurde:
         * hinzunehmen von eventlist, Abgleich der DeviceListe mit allen anderen Files und am Ende speichern
         * direkter Zugriff auf die Liste
         *     
         */
        /**
         * Adds topology information based on the provided device list.
         *
         * @param DeviceListManagement $devicelist         The device list management instance containing devices to be added to the topology.
         * @param DetectDeviceListHandler $deviceEventList The handler for device events used in topology detection.
         * @param DetectEventListHandler $eventList        The handler for general event management in topology detection.
         * @param bool $debug                             Optional. If true, enables debug output. Default is false.
         *
         * @return void
         */
        public function addTopologyByDeviceList(DeviceListManagement $devicelist,DetectDeviceListHandler $deviceEventList, DetectEventListHandler $eventList, $debug=false)
            {
            //if ( (($devicelist instanceof DeviceListManagement) && ($deviceEventList instanceof DetectDeviceListHandler))===false) return (false);
            $status=true;
            $modulhandling = new ModuleHandling();
            $deviceInstances = $modulhandling->getInstances('TopologyDevice',"NAME");      // Liste von Topolog Devices, Name Ident mit Eintrag Devicelist

            //deviceEventListData
            $deviceEventListData = $deviceEventList->get_Eventlist();

            //channelEventListData
            $this->setEventListFromConfigFile($debug);
            $channelEventListData =  $this->Get_EventConfigurationAuto();

            //devicelistdata
            $devicelistdata = $devicelist->read_devicelist();
            //if ($debug) $devicelist->printDevicelist();          // Liste von Geräten mit Topology Eintrag

            //eventListData
            $eventListData = $eventList->getEventList();

            echo "addTopologyByDeviceList to channeleventlist (".sizeof($channelEventListData).") from devicelist (".sizeof($devicelistdata).") and deviceEventList (".sizeof($deviceEventListData)." and EventList (".sizeof($eventListData).")) \n";           

            foreach ($devicelistdata as $name => $entry)            // name is unique in devicelist
                {
                $oids=$devicelist->getalloids($entry);
                if ( (isset($deviceInstances[$name])) === false ) echo "Warning, Topology Device with name $name not created.\n"; 
                else    
                    { 
                    $room="";                            // mit einem leeren Raum anfangen und nach Indizien suchen
                    // für alle oids die ich finde die channelEventList befragen, was ist vorhanden
                    foreach ($oids as $oid)                 
                        {
                        if (isset($channelEventListData[$oid])) 
                            {
                            $config=json_encode($channelEventListData[$oid]);
                            if ($channelEventListData[$oid][1] !="")
                                {
                                if ($room == "") 
                                    {
                                    $room=$channelEventListData[$oid][1];
                                    }
                                else
                                    {
                                    if ($room != $channelEventListData[$oid][1]) 
                                        {
                                        //echo "    Fehler channelEventList, die Channels sind in unterschiedlichen Räumen. $oid  $room != ".$channelEventList[$instance["OID"]][1]."\n";
                                        echo "    Fehler channelEventList, die Channels sind in unterschiedlichen Räumen. $oid  $room != ".$channelEventListData[$oid][1]."\n";
                                        }
                                    }
                                }
                            }
                        }

                    // dann erst die Instanzen vergleichen
                    $InstanzID = $deviceInstances[$name];   
                    if (isset($deviceEventListData[$InstanzID]))        // device mit Rauminformation abgleichen, pro Gerät gibt es basierend auf dem Topology Device eine Rauminformation
                        {
                        //print_r($deviceEventList[$InstanzID]);
                        if ($room == "")                            // immer noch nichts gefunden, wo ist die Instanz eigentlich
                            {
                            if ($deviceEventListData[$InstanzID][1] != "")
                                {
                                $room = $deviceEventListData[$InstanzID][1];
                                }
                            else
                                {
                                $parentName=IPS_GetName(IPS_GetParent($InstanzID));
                                if ($debug>1) echo "   addTopology, deviceEventList no room data. Topology Instanz $name ($InstanzID) Parent : $parentName \n";
                                }
                            }
                        elseif ($room != $deviceEventListData[$InstanzID][1]) 
                            {
                            echo "Topology Device ".str_pad($name,55)." in room    $room   or here ".$deviceEventListData[$InstanzID][1]."\n";
                            }
                        elseif ($debug>1) echo "Topology Device ".str_pad($name,55)." in room    $room \n";
                        }
                    else echo " Warning, da lauft was schief, $name $deviceEventListData kein Eintrag für Topology Device $InstanzID. \n";
                    //$configUpdated = $topologyLibrary->initInstanceConfiguration($InstanzID,$newentry,$debug);          //true für debug
                    $configTopologyDevice=IPS_GetConfiguration($InstanzID);  
                    $configUpdated=json_decode($configTopologyDevice,true);                   
                    unset($configUpdated['ImportCategoryID']);
                    unset($configUpdated['Open']);
                    unset($configUpdated['UpdateInterval']);
                    $configUpdated["Name"]=$room;           // das ist der aus der Config
                    // auslesen weil eh schon richtig
                    $configUpdated["UniqueName"]=$deviceEventListData[$InstanzID][4];
                    $configUpdated["UniquePath"]=$deviceEventListData[$InstanzID][5];
                    
                    // update room in channelEventList if needed
                    if ($room != "") 
                        {
                        foreach ($oids as $oid)    
                            {
                            if (isset($channelEventListData[$oid])) 
                                {
                                $config=json_encode($channelEventListData[$oid]);

                                if ($channelEventListData[$oid][1] == "") 
                                    {
                                    $channelEventListData[$oid][1]=$room;
                                    if ($debug) echo "$name, update channelEventList $oid from ".$channelEventListData[$oid][1]." with $room.  $config\n";
                                    }
                                else
                                    {
                                    if ($room != $channelEventListData[$oid][1]) 
                                        {
                                        echo "$name, Fehler channelEventList, die Channels sind in unterschiedlichen Räumen. $oid  $room != ".$channelEventListData[$oid][1]."\n";
                                        $channelEventListData[$oid][1]=$room;
                                        }
                                    }                            
                                }
                            }
                        }
                    $status = $this->updateData($devicelistdata[$name]["Topology"],$configUpdated,$name);
                    }
                }           // ende foreach
            $devicelist->write_devicelist($devicelistdata);
            $this->channelEventList = $channelEventListData;
            return ($status);
            }
            
        public function get_Eventlist()
            {
            return ($this->channelEventList);
            }

        public function write_EventList()
            {
            if (sizeof($this->channelEventList)>0) $this->StoreEventConfiguration($this->channelEventList,"full update from todo");
            else echo "warning, write_EventList, channelevents, data has not been created.\n";
            }

        public function addTopologyByEventList(DetectEventListHandler $eventList, $debug=false)
            {
            $status=true;
            $eventListData = $eventList->getEventList();
            if ($debug) echo "addTopologyByEventList to channeleventlist (".sizeof($this->channelEventList).") from eventList (".sizeof($eventListData).") \n";           
            foreach ($eventListData as $oid => $entry)
                {
                }
            }
        
        public function alignByCoidsList($coids,$style="summary")
            {
            if ($style=="summary") 
                {
                $coidCount=0;
                foreach ($this->channelEventList as $variableId=>$params)
                    {
                    if (isset($coids[$variableId])) $coidCount++;
                    }
                echo "DetectDeviceHandler::alignByCoidsList ".sizeof($this->channelEventList)." coid found $coidCount.\n";
                }
            else
                {
                $coidCount=0;                    
                foreach ($this->channelEventList as $variableId=>$params)
                    {
                    if (isset($coids[$variableId]))
                        {
                        echo "* ";
                        $coidCount++;
                        }
                    else
                        {
                        echo "  ";
                        }
                    echo $variableId."   ";
                    if (IPS_ObjectExists($variableId)) 
                        {
                        echo str_pad("(".IPS_GetName($variableId)."/".IPS_GetName(IPS_GetParent($variableId))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($variableId))).")",90);
                        foreach ($params as $index=>$param) { echo " \"$param\","; }
                        }
                    else echo "******* Delete Entry from IPSDetectDeviceHandler_GetEventConfiguration() in EvaluateHardware_Configuration.";
                    echo "\n";
                    }
                echo "DetectDeviceHandler::alignByCoidsList ".sizeof($this->channelEventList).", thereof coid found $coidCount.\n";
                }
            }

        public function addTopologyByCoidsList($coids,$style="summary")
            {
            $coidCount=0;                    
            foreach ($this->channelEventList as $variableId=>$params)
                {
                if (isset($coids[$variableId]))
                    {
                    echo "* ";
                    $coidCount++;
                    }
                else
                    {
                    echo "  ";
                    }
                echo $variableId."   ";
                if (IPS_ObjectExists($variableId)) 
                    {
                    echo str_pad("(".IPS_GetName($variableId)."/".IPS_GetName(IPS_GetParent($variableId))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($variableId))).")",90);
                    foreach ($params as $index=>$param) { echo " \"$param\","; }
                    }
                else echo "******* Delete Entry from IPSDetectDeviceHandler_GetEventConfiguration() in EvaluateHardware_Configuration.";
                if (isset($coids[$variableId]["TOPO"]["SIMPLE"]))
                    {
                    $path=$coids[$variableId]["TOPO"]["SIMPLE"];
                    $pathInfo=explode(".",$path);
                    if (isset($pathInfo[0])) $this->channelEventList[$variableId][1]=$pathInfo[0];
                    echo "   found in coidslist.".json_encode($coids[$variableId]["TOPO"]["SIMPLE"]);
                    }
                echo "\n";
                }
            echo "DetectDeviceHandler::addTopologyByCoidsList ".sizeof($this->channelEventList).", thereof coid found $coidCount.\n";
            }

		}  /* ende class */

	/*******************************************************************************
	 *
	 * Class Definitionen DetectDeviceListHandler
     *
     * erzeugt die config IPSDetectDeviceListHandler_GetEventConfiguration in scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php
	 *
     * das ist die config Liste die die Topology zu Geräten angibt, das soll eine Erleichterung birngen da ein Gerät nur in einem Raum sein kann
     * für die Zuordnung Instanzen/Register zu Topolgie und Gewerke siehe:            IPSDetectDeviceHandler_GetEventConfiguration 
     *
     * verwendet, erweitert DetectHandlerTopology
     *      __construct     gleich, erzeugt topology von file, oder erstell config im file
     *      setEventListFromConfigFile
     *      Get_Configtype, Get_ConfigFileName  für die Erzeugung der Function im File
     *      Get_EventConfigurationAuto, Set_EventConfigurationAuto 
     *      Get_Topology    topology unified ausgeben
     *      CreateMirrorRegister    wird nicht verwendet
     *
     * nutzt von DetectHandlerTopology
     *      create_Topology
	 *      create_TopologyConfigurationFile, wenn es noch kein Configfile gibt
     *
	 ****************************************************************************************/

	class DetectDeviceListHandler extends DetectHandlerTopology
		{

        protected $deviceEventList;
       	
        private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;		

        protected $topology;
 

		/**
		 * @public
		 *
		 * Initialisierung des  DetectDeviceListHandler Objektes
		 *
		 */
		public function __construct()
			{
			self::$configtype = '$deviceListTopology';
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php';
            if ($this->create_Topology()===false)           // berechnet topology und ID, false wenn get_topology nicht definiert, input parameter false no init/empty category false no debug
				{
                $this->create_TopologyConfigurationFile(true);          //true for Debug 
				}
	        parent::__construct();
			}

        /* DetectDeviceListHandler::setEventListFromConfigFile
         * get Eventlist from config file and store it in class
         * es fehlt die Angabe des include files
         */
        public function setEventListFromConfigFile($debug=false)
            {
            if ($debug) echo "DetectDeviceListHandler::setEventListFromConfigFile:\n";
            $this->deviceEventList = $this->Get_EventConfigurationAuto($debug);
            // extend deviceEventlist always to 6 entries 0..5
            foreach ($this->deviceEventList as $topoId => &$entry) 
                {
                for ($i=0;$i<6;$i++) if (isset($entry[$i])===false) $entry[$i]="";
                }
            }

		public function Get_Configtype()
			{
			return self::$configtype;
			}
			
		public function Get_ConfigFileName()
			{
			return self::$configFileName;
			}	

		public function Get_Topology()
			{
			return($this->topology);
			}
            		

		/* DetectDeviceListHandler::Get_EventConfigurationAuto()
         * obige variable in dieser Class kapseln, dannn ist sie static für diese Class 
         */

		public function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
				if ( function_exists("IPSDetectDeviceListHandler_GetEventConfiguration") == true ) self::$eventConfigurationAuto = IPSDetectDeviceListHandler_GetEventConfiguration();
				else 
                    {
                    echo "FEHLER: function IPSDetectDeviceListHandler_GetEventConfiguration nicht vorhanden.\n";
                    self::$eventConfigurationAuto = array();
                    }
				}
			return self::$eventConfigurationAuto;
			}

		/**
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		public function Set_EventConfigurationAuto($configuration)
			{
			self::$eventConfigurationAuto = $configuration;
			}

        /* DetectDeviceListHandler, nur da damit keine Fehlermeldung, eigentlich egal */

		public function CreateMirrorRegister($variableId,$debug=false)
			{
			}

        /* DetectDeviceListHandler::analyseTopologData 
         * Rauminformationen werden dort händisch eingetragen, mögen nicht eindeutig sein, mit references sieht man das gleich
         * deviceeventlist ist die Topology Device Liste, die hat eine Configuration mit einer UUID, nach dem Create
         * in der deviceeventliste gibt es unter Parameter 1 den Raum, der nicht eindeutig ist, daher nach diese Routine unter Parameter 4 den eindeutigen Namen und den Pfad aus eindeutigen Namen
         * einen Pfad ohne eindeutigen namen könnt eman ohne Erweiterungen lesen, also alles ab _ weg
         */       
        public function analyseTopologyData($topology,$debug=false)
            {
            $status=true;
            $objectway=false;
            if ($topology instanceof TopologyManagement)            // überprüfung zur Runtime nicht Compile Time ?
                {
                $objectway=true;
                $topologydata=$topology->Get_Topology();
                }
            else $topologydata=$topology;

            if ($debug) echo "analyseTopologData based on topology (".sizeof($topologydata)." Data elements)\n";
            $references = $this->topologyReferences($topologydata);         // ist in der falschen class, move to TopologyManagement
            //print_r($references);
            //$this->deviceEventList = $this->Get_EventConfigurationAuto();
            foreach ($this->deviceEventList as $topodeviceId => $configuration)               // $DetectDeviceListHandler Methode
                {
                $roomconf=$configuration[1];            // da gabs noch mehr Parameter
                $uniqueroom=$roomconf;
                //  uniqueNameReference($entryplace,$references);         // für eine Raumangabe entryplace Wohzimmer~LBG70 den uniquename Wohnzimmer__1 finden
                $uniqueroom=$this->uniqueNameReference($roomconf,$references,$debug);           // zumindest der erste Teil vor der Tilde muss bekannt sein
                if ($uniqueroom !== $roomconf) 
                    {
                    $room=explode("~",$roomconf)[0];
                    //echo "da ist jetzt was passiert, Tilde ausgewertet:  $roomconf : $room -> $uniqueroom\n";
                    // fehlt $room richtig setzen  Ort am Anfang oder am Ende ???
                    }
                else $room=$roomconf;
                $name=IPS_GetName($topodeviceId);
                /* wir haben jetzt  name (gleich für topo device und device, später auch UUID), 
                *                  roomconf, das ist die Original Configuration (auch mit Tilde) die aus Gründen der Lesbarkeit auch in andere Configfiles übertragen werdn soll
                *                  room das ist der Name lesbar ohne __ Erweiterung, auch der Index für References             
                *                  uniqueroom das ist der unified room, mit Erweiterung __ zur eindeutigen Indexierung 
                * die Topology Device Liste könnte man leicht auf dies Parameter erwietern und abspeichern   
                */
                $pathecho=""; $path="";
                if (isset($references[$room]))                  // das geht nicht eindeutig, room ist mehrdeutig
                    {
                    if (is_array($references[$room]) === false) $pathecho="Path not known";
                    else
                        {
                        if (sizeof($references[$room])>1) 
                            {
                            $pathecho="Path not unique : $room as $uniqueroom :";
                            foreach ($references[$room] as $pathfound => $data) 
                                {
                                $pathecho .= "$data ";
                                if ($data==$uniqueroom) { $path = $pathfound; $pathecho =""; break; }
                                }
                            }
                        if (sizeof($references[$room])==1) foreach ($references[$room] as $path=>$entry) ;
                        if (sizeof($references[$room])==0) $pathecho="Path not known";  
                        }  
                    }
                else $pathecho="Room unknown in references";

                // Pfad zum Raum definieren und uniquename
                // par3
                if (isset($this->deviceEventList[$topodeviceId][3])===false) $this->deviceEventList[$topodeviceId][3]="";
                // par4, $uniqueroom
                if (isset($this->deviceEventList[$topodeviceId][4])===false) $this->deviceEventList[$topodeviceId][4]=$uniqueroom;
                else    
                    {
                    if ($this->deviceEventList[$topodeviceId][4] == "") $this->deviceEventList[$topodeviceId][4]=$uniqueroom;
                    else if ($this->deviceEventList[$topodeviceId][4] != $uniqueroom)   
                        { 
                        echo "    Warning, analyseTopologData, Overwrite par4, $name : ".$this->deviceEventList[$topodeviceId][4]." != $uniqueroom , clear data from config.\n"; 
                        $status=false; 
                        }
                    }
                // par5, $path    
                if (isset($this->deviceEventList[$topodeviceId][5])===false) $this->deviceEventList[$topodeviceId][5]=$path;
                else    
                    {
                    if ($this->deviceEventList[$topodeviceId][5] == "") $this->deviceEventList[$topodeviceId][5]=$path;
                    else if ($this->deviceEventList[$topodeviceId][5] != $path)    
                        { 
                        echo "     Warning, analyseTopologData, Overwrite par5, $name : ".$this->deviceEventList[$topodeviceId][5]." != $path , clear data from config.\n";  
                        $status=false; 
                        }
                    }
                }
            return ($status);
            }

        public function get_Eventlist()
            {
            return ($this->deviceEventList);
            }

        public function write_Eventlist()
            {
            $this->StoreEventConfiguration($this->deviceEventList,"Full update of List");
            }

        public function printEventlist($style="table")  
            {
            if ($style=="table")
                {
                echo "   ".str_pad("TOPOID",10)." ".str_pad("Name",30)." ".str_pad("RoomConf",30)." ".str_pad("UniqueRoom",30)." ".str_pad("Path",50)."\n";
                echo "   ".str_repeat("=",150)."\n";
                foreach ($this->deviceEventList as $topoid => $entry)
                    {
                    $name=IPS_GetName($topoid);
                    $roomconf=$entry[1];
                    $uniqueroom=$entry[4];
                    $path=$entry[5];
                    echo "   ".str_pad($topoid,10)." ".str_pad($name,30)." ".str_pad($roomconf,30)." ".str_pad($uniqueroom,30)." ".str_pad($path,50)."\n";
                    }   
                }
            if ($style=="summary")
                {
                $topoCount=0;
                foreach ($this->deviceEventList as $topoid => $entry)
                    {
                    $name=IPS_GetName($topoid);
                    $roomconf=$entry[1];
                    $uniqueroom=$entry[4];
                    $path=$entry[5];
                    if ($path != "") $topoCount++;
                    }   
                echo "   DetectDeviceListHandler::printEventlist Summary of Device Topology ".sizeof($this->deviceEventList)." Entries, $topoCount with Topology.\n";
                }
            }     

        public function compareEventlist($deviceEventListDataOld, $debug=false)
            {
            // compare deviceEventListData and deviceEventListDataOld
            $newEntries=0;
            foreach ($this->deviceEventList as $objectId => $params)
                {
                if (!isset($deviceEventListDataOld[$objectId]))
                    {
                    echo "     New Entry for $objectId ".IPS_GetName($objectId)."  ";
                    foreach ($params as $index=>$param) { echo " \"$param\","; }
                    echo "\n";
                    $newEntries++;
                    }
                else
                    {
                    $oldParams=$deviceEventListDataOld[$objectId];
                    foreach ($params as $index=>$param)
                        {
                        if (!isset($oldParams[$index]) || ($oldParams[$index] != $param))
                            {
                            echo "     Changed Entry for $objectId ".IPS_GetName($objectId)."  ";
                            echo "     \"$index\" was \"".$oldParams[$index]."\" now \"$param\" \n";
                            }
                        }
                    }   
                }            
            }

		}  /* ende class */

/****************************************************************************************
 *
 * Class TestMovement, erstellen einer Tabelle mit allen CustomEvents des IPSMessageHandlers
 *
 * Die tabelle $this->eventlist wird bereits mit dem construct erstellt und dann weiter verarbeitet
 * OID, Name, EventID, Pfad, EventName, Instanz, Typ, Config, Homematic usw. 
 *      OID, die ID des Events
 *      Name, der Name des Events
 *      EventID aus dem Namen extrahiert, steht nach dem _
 *          Wenn das Object zur EventID nicht vorhanden ist, wird ein Fehler für diese Zeile ausgegeben
 *      Pfad, der Objektpfad bis zum Object
 *      EventName, der Object Name
 *      Instanz, für das Object zur EventID wird geprüft ob der Parent eine instanz ist,
 *      Typ, ist der DetectMovement Auswertungstyp, in welcher Configuration steht das IPS_GetObject
 *      Config, ist die Config aus dem MessageHandler
 *      Homematic, ist das Object eine Homematic Instanz und wenn ja welche
 *
 *  syncEventList                   von construct aufgerufen, obige Tabelle erstellen oder updaten
 *  getEventListforDeletion
 *  getEventListfromIPS
 *  getComponentEventListTable
 *  getAutoEventListTable
 *  writeEventListTable
 *  findMotionDetection
 *  findSwitches
 *  findButtons
 *  sortEventList
 *
 **************************************/

class TestMovement extends DetectEventListHandler
    {
	/* TestMovement::__construct
	 *
	 */
	public function __construct($debug=false) 
		{	
		$this->debug=$debug;
		if ($debug) echo "TestMovement Construct, Debug Mode, zusätzliche Checks bei der Eventbearbeitung:\n";
        //$this->syncEventList($debug);       // speichert eventList und eventListDelete
        parent::__construct($debug);
		}

    /* EventList erzeugen
     *  ScriptID von IPSMessageHandler_Event rausfinden und die darunter angelegten Children, Events rausfinden
     *  sortiert dabei auch gleich die Events nach Ihrer EventID
     *
     *  Abgleich mit vorhandenen Konfigurationen und Darstellung in der Eventlist
     *
     * Folgende tabelleneintraege gibt es bereits in der Grundausstattung 
     *   OID Objekt OID vom IP Symcon Event
     *   Name des IP Symcon Events
     *   OID des auslösendes Registers, eigentlichem Event
     *   Fehler eventueller Eintrag wenn etwas nicht stimmt
     *   Pfad des auslösenden Registers
     *   NameEvent Name des auslösenden Registers 
     *   Instanz sollte der Parent des auslösenden registers eine Instanz sein, diese hier anführen. ZB Homematic Device
     *   Typ, wird in der Tabelle als Funktion ausgegeben, Wenn das auslösende Register in einer der detectMovement Konfigurationen steht, hier einen entsprechenden Eintrag machen
     *
     * Bearbeitet folgende class Variablen
     *  eventlist
     *  eventlistDelete
     *
     */
    function syncEventList($debug=false)
        {
		/* Autosteuerung */
		IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
		$autosteuerung_config=Autosteuerung_GetEventConfiguration();
	
		/* IPSComponent mit CustomComponent */
        if (function_exists("IPSMessageHandler_GetEventConfiguration")) $eventlistConfig = IPSMessageHandler_GetEventConfiguration();
        else $eventlistConfig = array(); 
        if (function_exists("IPSMessageHandler_GetEventConfigurationCust")) $eventlistConfigCust = IPSMessageHandler_GetEventConfigurationCust();
        else $eventlistConfigCust = array();
        $eventlistMerged = $eventlistConfig + $eventlistConfigCust;
        if ($debug) echo "Overview of registered Events ".sizeof($eventlistConfig)." + ".sizeof($eventlistConfigCust)." = ".sizeof($eventlistMerged)." Eintraege : \n";

		$this->motionDevice=$this->findMotionDetection();		        // aus der EvaluateHardware_Include List aus den Homematic Devices und wenn IPCams auch aus den Kameras die Bewgungserkennungen rausfiltern					
		$this->switchDevice=$this->findSwitches();
		$this->buttonDevice=$this->findButtons();	
		$motionDevice=$this->motionDevice;
		$switchDevice=$this->switchDevice;
		$buttonDevice=$this->buttonDevice;		
		//print_r($motionDevice); print_r($switchDevice);
		
		if ($debug) 
			{
			//if (false)
				{
				echo "Liste Motion Devices, Bewegungsmelder Geräte:\n";
				//print_r($this->motionDevice);
				foreach ($this->motionDevice as $key => $status) echo "    ".$key."  ".IPS_GetName($key)."/".IPS_GetName(IPS_GetParent($key))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($key)))."\n";
				echo "Liste Switch Devices, Schalter Geräte:\n";
				//print_r($this->switchDevice);
				foreach ($this->switchDevice as $key => $status) echo "    ".$key."  ".IPS_GetName($key)."/".IPS_GetName(IPS_GetParent($key))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($key)))."\n";
				echo "Liste Button Devices, Taster Geräte:\n";
				//print_r($this->buttonDevice);
				foreach ($this->buttonDevice as $key => $status) echo "    ".$key."  ".IPS_GetName($key)."/".IPS_GetName(IPS_GetParent($key))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($key)))."\n";
				}
			echo "\n============================================================\n";
			echo "CustomComponents MessageHandler Events auslesen:\n";
			}	
        /*
         * DetectSensorHandler          $eventSensorConfiguration       Sensor-Auswertung
         * DetectClimateHandler         $eventClimateConfiguration      Climate-Auswertung
         * DetectHumidityHandler        $eventHumidityConfiguration     Feuchtigkeit-Auswertung
         * DetectMovementHandler        $eventMoveConfiguration         Bewegung-Auswertung
         * DetectBrightnessHandler      $eventBrightnessConfiguration   Helligkeit-Auswertung
         * DetectContactHandler         $eventContactConfiguration      Kontakt-Auswertung
         * DetectTemperatureHandler     $eventTempConfiguration         Temperatur-Auswertung
         * DetectHeatControlHandler     $eventHeatConfiguration         HeatControl-Auswertung
         * DetectHeatSetHandler            
         */

        /* DetectSensor      no config seen in DetectMovemeent_Configuration */

        /* DetectClimate      Netatmo */

        /* DetectBrightness */

        /* DetectContact */

        /* Detect HeatSet */

		/* DetectMovement */
		if (function_exists('IPSDetectMovementHandler_GetEventConfiguration')) 		
            {
            if ($debug) echo "    Movement Configuration auslesen.\n";    
            $movement_config=IPSDetectMovementHandler_GetEventConfiguration();
            }
		else $movement_config=array();
		//print_r($movement_config);
		if (function_exists('IPSDetectTemperatureHandler_GetEventConfiguration'))	
            {
            if ($debug) echo "    Temperature Configuration auslesen.\n";    
            $temperature_config=IPSDetectTemperatureHandler_GetEventConfiguration();
            }
		else $temperature_config=array();
		if (function_exists('IPSDetectHumidityHandler_GetEventConfiguration'))		
            {
            if ($debug) echo "    Humidity Configuration auslesen.\n";    
            $humidity_config=IPSDetectHumidityHandler_GetEventConfiguration();
            }
		else $humidity_config=array();
		if (function_exists('IPSDetectHeatControlHandler_GetEventConfiguration'))	
            {
            if ($debug) echo "    HeatControl Configuration auslesen.\n";    
            $heatcontrol_config=IPSDetectHeatControlHandler_GetEventConfiguration();
            }
		else $heatcontrol_config=array();



		$i=0;
		$delete=0;			// mitzaehlen wieviele events geloescht werden muessen 
		$eventlist=array();
		$this->eventlistDelete=array();		// Sammlung der Events für die es kein Objekt mehr dazu gibt

		if ($debug) echo " EventID  ".str_pad("Name",75)." Type       Parent Instanz   \n";          // Ueberschrift tabelle
        $scriptId  = $this->getScriptIdMessageHandler();                // suche scriptId von 'IPSMessageHandler_Event'

        $events=array();
		$children=IPS_GetChildrenIDs($scriptId);		// alle Events des IPSMessageHandler erfassen
		foreach ($children as $childrenID)
			{
			$name=IPS_GetName($childrenID);
			$eventID_str=substr($name,Strpos($name,"_")+1,10);
			$eventID=(integer)$eventID_str;
			if (substr($name,0,1)=="O")									// sollte mit O anfangen
				{
				$eventlist[$i]["OID"]=$childrenID;				
				$eventlist[$i]["Name"]=IPS_GetName($childrenID);
				$events[$eventID]=$childrenID;                          // eventID die auslösende Variable, childrenID das Event das unterhalb des Scripts gespeichert ist
    		    IPS_SetPosition($childrenID,$eventID);		            // einsortieren	
                $i++;	
                }
            }

		//$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
        $eventlist = $this->getEventListByScriptId($scriptId);                    // alle Events die diesem Script zugeordnet sind

        foreach ($eventlist as $i => $event)
            { 
            $eventID=$eventlist[$i]["EventID"];

            if (IPS_ObjectExists($eventID)==false)
                { /* Objekt für das Event existiert nicht */
                $delete++;
                if ($debug) echo "*** ".$eventID." existiert nicht.\n";
                $eventlist[$i]["Fehler"]='does not exists any longer. Event has to be deleted ***********.';
                $this->eventlistDelete[$eventID]["Fehler"]=1;
                $this->eventlistDelete[$eventID]["OID"]=$childrenID;
                if (isset($eventlistConfig[$eventID])) if ($debug) echo "**** und Event $eventID auch aus der Config Datei \"IPSMessageHandler_GetEventConfiguration\" loeschen: ".$eventlistConfig[$eventID][1].$eventlistConfig[$eventID][2]."\n";
                }	
            else
                { /* Objekt für das Event existiert, den Pfad dazu ausgeben */
                $instanzID=IPS_GetParent($eventID);
                if ($debug) echo "   ".$eventID."  ".str_pad(IPS_GetName($instanzID)."/".IPS_GetName($eventID),75)." ";
                $instanz="";
                switch (IPS_GetObject($instanzID)["ObjectType"])
                    {
                    /* 0: Kategorie, 1: Instanz, 2: Variable, 3: Skript, 4: Ereignis, 5: Media, 6: Link */
                    case 0:
                        if ($debug) echo "Kategorie  ";
                        break;
                    case 1:
                        $instanz=IPS_GetInstance($instanzID)["ModuleInfo"]["ModuleName"];
                        if ($debug) echo "Instanz    ";
                        break;
                    case 2:
                        if ($debug) echo "Variable   ";
                        break;
                    case 3:	
                        if ($debug) echo "Skript     ";
                        break;
                    case 4:
                        if ($debug) echo "Ereignis   ";
                        break;
                    case 5:
                        if ($debug) echo "Media      ";
                        break;
                    case 6:
                        if ($debug) echo "Link       ";
                        break;
                    default:
                        echo "**** Fehler unknown Type";
                        break;
                    }
                if (IPS_GetObject($eventID)["ObjectType"]==2)    // Wenn Event vom Typ Variable auch Wert Instanz ID ausgeben 	
                    {
                    if ($debug) echo $instanzID;
                    }
                else 	
                    {
                    echo "**** Fehler, referenzierte EventID ist vom Typ keine Variable.   ";
                    echo "Objekt : ".$eventID." Instanz : ".IPS_GetName($instanzID)." \n ";
                    }
                $eventlist[$i]["Pfad"]=IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($eventID))))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($eventID)))."/".IPS_GetName(IPS_GetParent($eventID));
                $eventlist[$i]["NameEvent"]=IPS_GetName($eventID);
                $eventlist[$i]["Instanz"]=$instanz;



                if (isset($movement_config[$eventID]))
                    {	/* kommt in der Detect Movement Config vor */
                    $eventlist[$i]["Typ"]="Movement";
                    }
                elseif (isset($temperature_config[$eventID]))
                    {	/* kommt in der Detect Temperature Config vor */
                    $eventlist[$i]["Typ"]="Temperatur";							
                    }	
                elseif (isset($humidity_config[$eventID]))
                    {	/* kommt in der Detect Humidity Config vor */
                    $eventlist[$i]["Typ"]="Humidity";
                    }	
                elseif (isset($heatcontrol_config[$eventID]))
                    {	/* kommt in der Detect Heatcontrol Config vor */
                    $eventlist[$i]["Typ"]="HeatControl";
                    }	
                else
                    {	/* kommt in keiner Detect Config vor */
                    $eventlist[$i]["Typ"]="";
                    }	

                if (isset($eventlistConfig[$eventID]))
                    {
                    $eventlist[$i]["Config"]=$eventlistConfig[$eventID][1];
                    //print_r($eventlistConfig[$eventID]);
                    }
                elseif (isset($eventlistConfigCust[$eventID]))
                    {
                    if ($debug) echo "Custom";
                    $eventlist[$i]["Config"]=$eventlistConfigCust[$eventID][1];
                    //print_r($eventlistConfig[$eventID]);
                    }
                else {
                    if ($debug) echo "  --> Objekt : ".$eventID." Konfiguration nicht vorhanden.";
                    $delete++;
                    $eventlist[$i]["Config"]='Error no Configuration available **************.';
                    $this->eventlistDelete[$eventID]["Fehler"]=2;
                    $this->eventlistDelete[$eventID]["OID"]=$childrenID;						
                    }
                    
                if (isset($motionDevice[$eventID])==true)
                    { 
                    $eventlist[$i]["Homematic"]="Motion";
                    $motionDevice[$eventID]=false;
                    if ($debug) echo " Homematic Motion";						
                    }
                elseif (isset($switchDevice[$eventID])==true)
                    {
                    $eventlist[$i]["Homematic"]="Switch";
                    $switchDevice[$eventID]=false;
                    if ($debug) echo " Homematic Switch";						
                    }     
                elseif (isset($buttonDevice[$eventID])==true)
                    {
                    $eventlist[$i]["Homematic"]="Button";
                    $buttonDevice[$eventID]=false;
                    if ($debug) echo " Homematic Button";						
                    }  					else $eventlist[$i]["Homematic"]="";	
                if ($debug) echo "\n";

                if (isset($movement_config[$eventID])==true)
                    { 
                    $eventlist[$i]["DetectMovement"]=$movement_config[$eventID][1];
                    $movement_config[$eventID][4]="found";
                    }
                else $eventlist[$i]["DetectMovement"]="";	
                
                if (isset($autosteuerung_config[$eventID])==true)
                    { 
                    $eventlist[$i]["Autosteuerung"]=$autosteuerung_config[$eventID][1]."|".$autosteuerung_config[$eventID][2];
                    }
                else $eventlist[$i]["Autosteuerung"]="";	
                
                }				
            }
			
			

		$this->eventlist=$eventlist;
		if ($delete>0) 
            {
            if ($debug)
                {
                echo "****Es muessen insgesamt ".$delete." Events geloescht werden, das Objekt auf das sie verweisen gibt es nicht mehr.\n";
                print_r($this->eventlistDelete);
                }
            }
        // return ($this->eventlistDelete);      // kein return von einem construct
        }

    /* getComponentEventListTable
     * Ausgabe als html Tabelle, ermittelt löschbare Items und (!!!) löscht sie auch gleich, deaktiviert
     * verwendet die eventlist 
     * sie kann unterschiedliche Formate annehmen 
     * es gibt für die Darstellung mögliche Filter
     *      IPSDetectEventListHandler_Event
     *
     *      Standard
     *
     * das Ergebnis der Events in resultEventList auswerten 
     * erwartetes Format, Tabelle indexiert
     *      OID
     *      Name
     *      LastRun
     *      Pfad
     *      Type
     *      Script
     */
    public function getComponentEventListTable($resultEventList,$filter="",$htmlOutput=false,$debug=false)
        {
        $messageHandler = new IPSMessageHandlerExtended();          // kann auch register loeschen

		$html="";
		$html.="<style>";
		$html.='#customers { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; font-size: 12px; color:black; border-collapse: collapse; width: 100%; }';
		$html.='#customers td, #customers th { border: 1px solid #ddd; padding: 8px; }';
		$html.='#customers tr:nth-child(even){background-color: #f2f2f2;}';
		$html.='#customers tr:nth-child(odd){background-color: #e2e2e2;}';
		$html.='#customers tr:hover {background-color: #ddd;}';
		$html.='#customers th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; }';
		$html.="</style>";
	
		$html.='<table id="customers" >';
        $result=array();
        if ($htmlOutput) $echo=false;
        else $echo=true;

        if ($filter == "IPSMessageHandler_Event")
            {
            if ($echo) echo str_pad(" #",3)." ".str_pad("OID",6).str_pad("Name",40)."\n";
		    $html.="<tr><th>#</th><th>OID</th><th>Name</th><th>ObjektID</th><th>Objektpfad/Fehler</th><th>Component Config</th><th>Module Config</th><th>LastRun</th><th>Pfad</th><th>Type</th><th>Script</th></tr>";
            }
        elseif ($filter == "IPSDetectEventListHandler_Event")  // IPSDetectEventListHandler_GetEventConfiguration
            {           // #  OID  Pfad/Name  Trigger Path Component Module LastRun Pfad Type Script
            if ($echo) echo str_pad(" #",3)." ".str_pad("OID",6).str_pad("Name",40)."\n";
		    $html.='<tr><th>#</th><th>OID</th><th>Pfad/Name</th><th>ObjektID</th><th>Objektpfad/Fehler</th><th>Component Config</th><th>Module Config</th><th>LastRun</th><th>Group</th><th>Config</th><th>Topologie</th><th style="width:10%">Autosteuerung</th></tr>';
            }  
        else            // #  OID  Name LastRun
            {
            if ($echo) echo str_pad(" #",3)." ".str_pad("OID",6).str_pad("Name",40)."\n";
		    $html.="<tr><th>#</th><th>OID</th><th>Name</th><th>Funktion</th><th>Konfiguration</th><th>Homematic</th><th>Detect Movement</th><th>Autosteuerung</th></tr>";
            }

        $delete=array(); $i=0;
        foreach ($resultEventList as $index => $entry)
            {
            $continue=true;
            if (isset($entry["TriggerVariableID"])) $trigger=$entry["TriggerVariableID"];
            elseif (isset($entry["EventID"])) $trigger=$entry["EventID"];
            else
                {
                $continue=false;
                echo "Delete Line $i for $index, format wrong: "; Print_r($entry);
                }
            if ($continue)
                {
                $path=$entry["Pfad"]."/".IPS_GetName($trigger);
                if (IPS_EventExists($entry["OID"])===false) echo "Event ".$entry["OID"]."does not exists.\n";
                $entry["LastRun"]=@IPS_GetEvent($entry["OID"])["LastRun"];
                if ($entry["LastRun"]==0) 
                    {
                    $lastrun="nie";
                    }
                else 
                    {
                    $timePassed=time()-$entry["LastRun"];
                    }

                if ($filter == "IPSDetectEventListHandler_Event") 
                    {
                    if ($echo) echo str_pad($i,3);
                    $html .= "<tr><td>".$i."</td>";
                    $info=@IPS_GetVariable($trigger);
                    if ($info === false) $html .= '<td colspan="2">==> Variable nicht mehr vorhanden.</td>';
                    else $html .= "<td>$trigger</td><td>$path</td>";
                    
                    $varLogId=false; $varlocation="";
                    //if (isset($entry["LoggingInfo"]["variableLogID"])) echo json_encode($entry)."\n";
                    if (isset($entry["LoggingInfo"]["variableLogID"])) $varLogId=$entry["LoggingInfo"]["variableLogID"];
                    if (isset($entry["LoggingInfo"]["LocationLog"])) $varlocation=$entry["LoggingInfo"]["LocationLog"];   
                    $needle='Program\IPSLibrary\data\core\IPSComponent\\';
                    $np = strpos($varlocation,$needle);
                    if ($np===0) 
                        {
                        //echo ".";
                        $varlocation = substr($varlocation,strlen($needle));
                        }
                    //else echo "not found $needle in $path.\n";
 
                    if ($varLogId) $html .= "<td>$varLogId</td><td>$varlocation</td>";
                    else $html .= '<td colspan="2">==> Variable nicht mehr vorhanden.</td>';
                    if (isset($entry["Component"])) 
                        {
                        if ($echo) echo str_pad($entry["Component"],50);
                        $html.="<td>".$entry["Component"]."</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("-----",50);
                        $html.="<td>------</td>";
                        }
                    if (isset($entry["Module"])) 
                        {
                        if ($echo) echo str_pad($entry["Module"],50);
                        $html.="<td>".$entry["Module"]."</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("-----",50);
                        $html.="<td>------</td>";
                        }
                    if ($entry["LastRun"]==0) 
                        {
                        if ($echo) echo str_pad("nie",20);
                        $html.="<td>nie</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("vor ".nf($timePassed,"s"),20);
                        $html.="<td>vor ".nf($timePassed,"s")."</td>";
                        }   
                    if (isset($entry["Group"])) 
                        {
                        if ($echo) echo str_pad(json_encode($entry["Group"]),50);
                        $group=""; $handlerconfig="";
                        foreach ($entry["Group"] as $handler => $configHandler)
                            {
                            if ($group != "") $group .= " , ";
                            if ($handlerconfig != "") $handlerconfig .= " , ";
                            $group .= $handler;
                            $handlerconfig .= $configHandler;
                            }
                        $html.="<td>".$group."</td><td>".$handlerconfig."</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("-----",50);
                        $html.='<td colspan="2"> </td>';
                        } 
                    if (isset($entry["TOPO"]["SIMPLE"]))                                         // dann auch ein Devicelist Eintrag
                        {
                        if ($echo) echo str_pad($entry["TOPO"]["SIMPLE"],50);
                        $html.="<td>".$entry["TOPO"]["SIMPLE"]."</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("-----",50);
                        $html.="<td> </td>";
                        }                         
                    if (isset($entry["Autosteuerung"])) 
                        {
                        if ($echo) echo str_pad($entry["Autosteuerung"],50);
                        $html.='<td style="width:10%">'.$entry["Autosteuerung"]."</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("-----",50);
                        $html.="<td> </td>";
                        } 
                    }
                elseif ($filter == "IPSMessageHandler_Event") 
                    {
                    if ($echo) echo str_pad($index,3)." ".str_pad($entry["OID"],6);
                    $html.="<tr><td>".$index."</td><td>".$entry["OID"]."</td>";
                    $result[$index]["OID"]=$entry["OID"];
                    if ($echo) echo str_pad($entry["Name"],15)." ";
                    $html.="<td>".$entry["Name"]."</td>";
                    $result[$index]["Name"]=$entry["Name"];
                    $info=@IPS_GetVariable($trigger);
                    if ($info !== false) 
                        {
                        $path=IPS_GetName($trigger)."/".IPS_GetName(IPS_GetParent($trigger));
                        if ($echo) echo str_pad($path,40)."  ";
                        $html.="<td>$trigger</td>";
                        $html.="<td>$path</td>";
                        $result[$index]["Pfad"]=$path;
                        }
                    else 
                        {
                        if ($echo) echo str_pad("==> Variable nicht mehr vorhanden.",40)."  ";
                        $html.='<td colspan="2">==> Variable nicht mehr vorhanden.</td>';
                        $delete[$entry["OID"]]=true;
                        }
                    if (isset($entry["Component"])) 
                        {
                        if ($echo) echo str_pad($entry["Component"],50);
                        $html.="<td>".$entry["Component"]."</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("-----",50);
                        $html.="<td>------</td>";
                        }
                    if (isset($entry["Module"])) 
                        {
                        if ($echo) echo str_pad($entry["Module"],50);
                        $html.="<td>".$entry["Module"]."</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("-----",50);
                        $html.="<td>------</td>";
                        }
                    if ($entry["LastRun"]==0) 
                        {
                        if ($echo) echo str_pad("nie",20);
                        $html.="<td>nie</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("vor ".nf($timePassed,"s"),20);
                        $html.="<td>vor ".nf($timePassed,"s")."</td>";
                        }                    
                    }
                else        // filter nicht bekannt
                    {
                    if ($echo) echo str_pad($index,3)." ".str_pad($entry["OID"],6);
                    $html.="<tr><td>".$index."</td><td>".$entry["OID"]."</td>";
                    $result[$index]["OID"]=$entry["OID"];

                    if ($echo) echo str_pad($entry["Name"],40)." ";
                    $html.="<td>".$entry["Name"]."</td>";
            
                    if ($entry["LastRun"]==0) 
                        {
                        if ($echo) echo str_pad("nie",20);
                        $html.="<td>nie</td>";
                        }
                    else 
                        {
                        if ($echo) echo str_pad("vor ".nf($timePassed,"s"),20);
                        $html.="<td>vor ".nf($timePassed,"s")."</td>";
                        //echo str_pad(date("Y.m.d H:i:s",$entry["LastRun"]),20);
                        }
                    if ($echo) echo "  ".str_pad($entry["Pfad"],80)."  ";
                    $html .= "<td>".$entry["Pfad"]."</td><td>";
                    if (isset($entry["Type"])) 
                        {
                        if ($echo) echo str_pad($entry["Type"],14)."   ";
                        $html .= $entry["Type"]."</td><td>";                    
                        }
                    else 
                        {
                        if ($echo) echo str_pad("unknown",14)."   ";
                        $html .= "unknown</td><td>";                    
                        }
                    if (isset($entry["Script"])) 
                        {
                        if ($echo) echo str_pad($entry["Script"],44)."   "."\n";
                        $html .= $entry["Script"]."</td></tr>";                      
                        }
                    else
                        {
                        if ($echo) echo str_pad("unknown",44)."   ";
                        $html.="unknown</td><td>unknown</td>";
                        }
                    }
                if ($echo) echo "\n";;
                $html .= "</tr>";                
                $i++;
                }
            }

        /* eventuell veraltete unbenutzte Events loeschen , aber nicht hier !!! */
        if ((sizeof($delete)>0) && false)
            {
            echo "Folgende Events loeschen:\n";
            //print_r($delete);
            foreach ($delete as $oid => $state)
                {
                $eventName=IPS_GetName($oid);
                $event=explode("_",$eventName)[1];
                //echo "   $oid $eventName $event ".IPS_GetName($event)."\n";
                echo "   $oid $eventName $event \n";
                $messageHandler->UnRegisterEvent($event);
                IPS_DeleteEvent($oid);
                }
            }
		$html.="</table>";
		if ($htmlOutput) return($html);
        else return($result);
        }
    }

/****************************************************************************************
 *
 * Class DetectEventListHandler, erstellen eines Arrays eventList mit allen CustomEvents des IPSMessageHandlers
 * eventlist ist die zentrale Variable
 *
 *  __contruct
 *  setEventListFromConfigFile              eventlist ist die zentrale Variable, diese aus dem Config File setzen
 *  setEventList                            get Eventlist from config variable and store it in class
 *  Get_EventConfigurationAuto              organisatorisches, methode muss in der richtigen class sitzen, sonst ist die function falsch
 *  getScriptIdMessageHandler               script ID von IPSMessage Handler rausfinden und als Input für nächste Routine verwenden
 *
 *  getEventListByScriptId                  IPS_GetScriptEventList($scriptId)
 *  checkEventsConfig
 *  sortEvents
 *  alignEventsConfig
 *  alignOtherEvents
 *  alignByCoidsList
 *  extendComponent
 *  extendGroups
 *  alignDeviceList
 *
 *  getEventListforDeletion
 *  getEventList                   
 *  getEventListfromIPS                     IPS_GetEventList
 *  getAutoEventListTable
 *  writeEventListTable
 *  findMotionDetection
 *  findSwitches
 *  findButtons
 *  sortEventList
 *
 **************************************/

class DetectEventListHandler extends DetectEventHandler
	{
	
	public $eventlist;          // for object oriented part

	public $eventlistDelete;
    public static $eventConfigurationAuto=array();
	protected $debug;	
	public $motionDevice, $switchDevice, $buttonDevice;	       /* erkannte Homematic Geräte */

	
	/* DetectEventListHandler::__construct
	 *
	 */
	public function __construct($debug=false) 
		{	
		$this->debug=$debug;
		if ($debug) echo "DetectEventListHandler Construct, Debug Mode, zusätzliche Checks bei der Eventbearbeitung:\n";
        //$this->syncEventList($debug);       // speichert eventList und eventListDelete
        parent::__construct($debug);
		}	

    /* setEventListFromConfigFile, object oriented
     * get Eventlist from config file and store it in class
     * es fehlt die Angabe des include files
     */
    public function setEventListFromConfigFile($debug=false)
        {
        if ($debug) echo "DetectEventListHandler::setEventListFromConfigFile:\n";
        $this->eventlist = $this->Get_EventConfigurationAuto($debug);
        }

    /* setEventList, object oriented
     * get Eventlist from config variable and store it in class
     */
    public function setEventList($configuration)
        {
        $this->eventlist = $configuration;
        }

    /* DetectEventHandler, obige variable in dieser Class kapseln, dannn ist sie static für diese Class 
     */
    function Get_EventConfigurationAuto($debug=false)
        {
        if ($debug) echo "DetectEventListHandler::Get_EventConfigurationAuto ".get_class($this)."\n";
        if (self::$eventConfigurationAuto == null)
            {
            $functionName = 'IPS'.get_class($this).'_GetEventConfiguration';
            if ( function_exists($functionName) ) self::$eventConfigurationAuto = $functionName();       /* <-------- change here */
            else self::$eventConfigurationAuto = array();					
            //echo "GetEventConf\n"; print_R(self::$eventConfigurationAuto);
            }
        return self::$eventConfigurationAuto;
        }        
    /* DetectEventListHandler::getScriptIdMessageHandler
     * die Script ID auslesen von dem Script an dem die Events hängen
     */
    public function getScriptIdMessageHandler()
        {
        return IPS_GetScriptIDByName('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));    
        }

    /* DetectEventListHandler::getEventListByScriptId
     * eventliste erzeugen aus Liste aller Events die einem Script zugeordnet sind
     * check ob Name event stimmt
     * check ob der Parent des Events das Script ist 
     * check ob der Name des Events mit der Triggervariable zusammenpasst
     * check ob es die Triggervariable noch gibt, sonst nicht übernehmen
     */
    public function getEventListByScriptId($scriptId)
        {
        if ($this->debug) echo "Script $scriptId EventListe ausgeben:  \n";
        $eventlist=array(); $eventlistordered=array();
        $events=IPS_GetScriptEventList($scriptId);          // Variablenveränderungen die einem Script zugeordnet sind
        //print_R($events);
        $i=0;
        foreach ($events as $eventOid)
            {
			$name=IPS_GetName($eventOid);
			$eventID_str=substr($name,Strpos($name,"_")+1,10);
			$eventID=(integer)$eventID_str;
			if (substr($name,0,1)=="O")	;								// sollte mit O anfangen
            else echo "   Fehler getEventListByScriptId $eventOid, Name falsch.\n";
            
            if (IPS_ObjectExists($eventID)==false) echo "   Fehler getEventListByScriptId $eventOid, Event Trigger Variable existiert nicht.\n"; 
            else
                {
                $eventlist[$i]["OID"]=$eventOid;	            //die OID des Events			
                $eventlist[$i]["Name"]=$name;
                $eventlist[$i]["EventID"]=$eventID;             // die Variable die das Event auslöst

                $event = IPS_GetEvent($eventOid);
                $eventlist[$i]["LastRun"]=$event["LastRun"];
                switch ($event["EventType"])
                    {
                    case 0:
                        $eventlist[$i]["Type"]="Auslöser";
                        break;
                    case 1:
                        $eventlist[$i]["Type"]="Zyklisch";
                        break;
                    case 2:
                        $eventlist[$i]["Type"]="Wochenplan";
                        break;
                    }
                $script=str_replace("\n","",$event["EventScript"]);
                $eventlist[$i]["Script"]=$script;

                if ($event["TriggerVariableID"] != $eventID) 
                    {
                    echo "   Fehler getEventListByScriptId $eventOid, Event Trigger Variable stimmt nicht mit dem Namen zusammen \n";
                    $eventlist[$i]["EventID"]=$event["TriggerVariableID"]; 
                    }
                if (IPS_GetParent($eventOid) !== $scriptId) echo "    Fehler getEventListByScriptId $eventOid, Ort falsch, Parent nicht $scriptId \n";	
                $i++;
                }
            }
        foreach ($eventlist as $entry) $eventlistordered[$entry["EventID"]]=$entry;

        $this->eventlist=$eventlistordered;
        return $eventlist;
        }


    /* DetectEventListHandler::checkEventsConfig, object oriented
     * eventList gibt es, die ist nach Einträgen sortiert, mit Config aus MessageHandler abgleichen
     * Fehler 1, es gibt mehr Events als Config Einträge
     * Fehler 2 es gibt mehr Config Einträge als Events
     * Fehler 3 das TriggerObjekt für das Event gibt es nicht, eine DeleteListe erstellen
     * dazu die Eventlist auf den selben Index wie die Config bringen, das ist die EventID
     *
     *
     */
    public function checkEventsConfig($eventlistConfig)
        {
        $debug=true;
		$i=0;
		$delete=0;			// mitzaehlen wieviele events geloescht werden muessen 

		$eventlistDelete=array();		// Sammlung der Events für die es kein Objekt mehr dazu gibt

        foreach ($eventlistConfig as $eventID => $entry)
            {
            if (IPS_ObjectExists($eventID)==false)
                { 
                // Objekt für das Event existiert nicht mehr
                $delete++;
                $eventlistDelete[$eventID]["Fehler"]=1;
                $eventlistDelete[$eventID]["OID"]=$eventID;
                echo "    Fehler 3, alignEvents, ".$eventID." in Configuration existiert nicht, aus der Config Datei \"IPSMessageHandler_GetEventConfiguration\" loeschen: ".$eventlistConfig[$eventID][1].$eventlistConfig[$eventID][2]."\n";
                }	                
            if (isset($this->eventlist[$eventID])===false) 
                {
                echo "   Fehler 2, alignEvents, ".$eventID." not found in Eventlist \n";
                }
            }
        $events=array();
        foreach ($this->eventlist as $oid => $entry)               // das ist die gefundene Liste aller Events mit dem Script als Target
            {
            if (isset($eventlistConfig[$oid])===false) 
                {
                echo "    Fehler 1, alignEvents, not found $oid in Configuration, add to Configuration, RegisterEvent with installComponent. \n";
                }
            else
                {
                $events[$oid]=$entry["OID"];                          // eventID die auslösende Variable, OID das Event das unterhalb des Scripts gespeichert ist  
                }           
            }
        $this->eventlistDelete = $eventlistDelete;
        return $events;
        }

    /* DetectEventListHandler::sortEvents
     * die Events so einsortieren, damit sie leichter zu erkennen sind
     */
    public function sortEvents($events)
        {
		foreach ($events as $eventId => $oid)
            {
    		IPS_SetPosition($oid,$eventId);		            // einsortieren	
            }
        }

    /* DetectEventListHandler::alignEventsConfig, obect oriented
     * $eventlistConfig aus MessageHandler in eventlist einbinden und weiter überprüfen
     * wenn Parent von EventId eine Instanz ist den ModuleName auslesen 
     */
    public function alignEventsConfig($eventlistConfig,$debug=false)
        {
        foreach ($this->eventlist as $index => $entry)
            {
            $eventID=$entry["EventID"];             // die TargetVariable, das TriggerEvent
            $instanzID=IPS_GetParent($eventID);         // den Pfad ermitteln, was ist der Parent
            if ($debug) echo "   ".$eventID."  ".str_pad(IPS_GetName($instanzID)."/".IPS_GetName($eventID),75)." ";
            $instanz="";
            switch (IPS_GetObject($instanzID)["ObjectType"])
                {
                /* 0: Kategorie, 1: Instanz, 2: Variable, 3: Skript, 4: Ereignis, 5: Media, 6: Link */
                case 0:
                    if ($debug) echo "Kategorie  ";
                    break;
                case 1:
                    $instanz=IPS_GetInstance($instanzID)["ModuleInfo"]["ModuleName"];
                    if ($debug) echo "Instanz    ";
                    break;
                case 2:
                    if ($debug) echo "Variable   ";
                    break;
                case 3:	
                    if ($debug) echo "Skript     ";
                    break;
                case 4:
                    if ($debug) echo "Ereignis   ";
                    break;
                case 5:
                    if ($debug) echo "Media      ";
                    break;
                case 6:
                    if ($debug) echo "Link       ";
                    break;
                default:
                    echo "**** Fehler unknown Type";
                    break;
                }
            if (IPS_GetObject($eventID)["ObjectType"]==2)    // Wenn Event vom Typ Variable auch Wert Instanz ID ausgeben 	
                {
                if ($debug) echo $instanzID;
                }
            else 	
                {
                echo "**** Fehler, referenzierte EventID ist vom Typ keine Variable.   ";
                echo "Objekt : ".$eventID." Instanz : ".IPS_GetName($instanzID)." \n ";
                }
            $this->eventlist[$index]["Pfad"]=IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($eventID))))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($eventID)))."/".IPS_GetName(IPS_GetParent($eventID));
            $this->eventlist[$index]["NameEvent"]=IPS_GetName($eventID);
            $this->eventlist[$index]["Instanz"]=$instanz;
            if (isset($eventlistConfig[$eventID][1])) $this->eventlist[$index]["Component"]=$eventlistConfig[$eventID][1];           // Component Config
            if (isset($eventlistConfig[$eventID][2])) $this->eventlist[$index]["Module"]=$eventlistConfig[$eventID][2];           // Component 
            if (isset($eventlistConfig[$eventID][3])) $this->eventlist[$index]["Parameter"]=$eventlistConfig[$eventID][3];
            if (isset($eventlistConfig[$eventID][4])) echo "   There is more I do not know\n";
            }
        }

    /* DetectEventListHandler::alignOtherEvents, object oriented
     *
     */
    public function alignOtherEvents($eventlistConfig,$identifier)
        {
        foreach ($this->eventlist as $index => $entry)
            {
            $eventID=$entry["EventID"];             // die TargetVariable, das TriggerEvent                
            if (isset($eventlistConfig[$eventID][1])) $this->eventlist[$index][$identifier]  = $eventlistConfig[$eventID][1];
            if (isset($eventlistConfig[$eventID][2])) $this->eventlist[$index][$identifier] .= "|".$eventlistConfig[$eventID][2];
            // keine Leermeldungen, der Übersicht halber
            }
        }

    /* DetectEventListHandler::alignByCoidsList, object oriented
     * die eventlist mit der coids Liste abgleichen, ob die Variablen noch existieren
     * wenn in der EventList LoggingInfo angelegt ist, auch diese Variable mit der coids Liste abgleichen
     * die coids Liste wird dabei erweitert, wenn eine Variable in der EventList ist, aber nicht in der coids Liste
     * die Variable wird dann mit dem selben Wert wie die EventList Variable angelegt
     * Ausgabe auf der Konsole, mit * markiert wenn die Variable in der coids Liste ist
     * die EventList wird nicht verändert
     */
    public function alignByCoidsList(&$coids, $style="summary" ,$debug=false)
        {
        if ($debug) echo "DetectEventListHandler::alignByCoidsList  ".sizeof($this->eventlist)." Entries. Use Table to get details\n";
        if ($style=="table") $doTable=true;
        else $doTable=false;

        foreach ($this->eventlist as $variableId=>$params)
            {
            if ($doTable)
                {
                if (isset($coids[$variableId]))
                    {
                    echo "* ";
                    }
                else
                    {
                    echo "  ";
                    }
                echo "  ".$variableId."   ";
                }
            if (IPS_ObjectExists($variableId)) 
                {
                if ($doTable) 
                    {
                    echo str_pad("(".IPS_GetName($variableId)."/".IPS_GetName(IPS_GetParent($variableId))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($variableId))).")",90);
                    foreach ($params as $index=>$param) 
                        { 
                        if (is_array($param)) echo "$index,";
                        else echo " \"$param\","; 
                        }
                    }
                }
            else echo "******* Delete Entry from IPSDetectEventListHandler_GetEventConfiguration() in EvaluateHardware_Configuration.\n";
            if ($doTable) echo "\n";
            if (isset($params["LoggingInfo"])) 
                {
                $loggingInfo = $params["LoggingInfo"];
                if (isset($loggingInfo["variableLogID"])) 
                    {
                    $varLogId = $loggingInfo["variableLogID"];
                    if (isset($coids[$varLogId]))
                        {
                        if ($doTable) echo "* ";
                        }
                    else
                        {
                        if ($doTable) echo "  ";
                        if (isset($coids[$variableId])) $coids[$varLogId]=$coids[$variableId];        
                        }
                    if (IPS_ObjectExists($varLogId)) 
                        {
                        if ($doTable) echo "      ".str_pad($varLogId,10).str_pad("    (".IPS_GetName($varLogId)."/".IPS_GetName(IPS_GetParent($varLogId))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($varLogId))).")",90);
                        }
                    else echo str_pad("    ******* Delete Entry from LoggingInfo variableLogID ".$varLogId." in EvaluateHardware_Configuration.",90)."\n";;
                    }
                /*else echo str_pad("    No variableLogID in LoggingInfo, error in EvaluateHardware_Configuration.",90);  
                if (is_array($loggingInfo))
                    {
                    echo "      LoggingInfo: ";
                    foreach ($loggingInfo as $index=>$param) 
                        { 
                        if (is_array($param)) echo "$index,";
                        else echo " $index => \"$param\","; 
                        }
                    }
                else echo " LoggingInfo not an array, error in EvaluateHardware_Configuration.";  */
                if ($doTable) echo "\n";  
                }        
            }
        }

    /* DetectEventListHandler::extendComponent, object oriented
     *
     */
    public function extendComponent($countmax=20)
        {
        echo "Component Info abgleichen: verarbeite $countmax Zeilen\n";                    // max Zeilen Begrenzung
        $startexec=microtime(true);
        $first=true; $count=0;             
        foreach ($this->eventlist as $index => $event)
            {
            if ($count++>$countmax) break;
            echo "  ".str_pad($event["EventID"],10).str_pad($event["NameEvent"],30);
            $componentName = false;
            $loggerName    = false;
            $config=explode(",",$event["Component"]);
            // Component raussuchen, aus dem className IPSComponentSensor_Motion das IncludeFile ableiten, componentType bis zum Underscore aber mit IPModule statt IPSComponent
            if (isset($config[0]))
                { 
                $className=$config[0];
			    $pos = strpos($className, '_');
			    if ($pos===false) echo "Fehler, Class Name entspricht nicht der Naming Convention : \n";
				else    
                    {
                    $componentType = substr($className, 0, $pos);
                    $componentType = str_replace('IPSModule','IPSComponent', $componentType);
                    IPSUtils_Include ($className.'.class.php', 'IPSLibrary::app::core::IPSComponent::'.$componentType);
                    if (class_exists($className)) 
                        {
                        // nach dem include muss die class existieren, die Parameter rausfinden, nicht vorhanden ist null
                        $componentName=$config[0];
                        if (isset($config[1])) $instanceId=$config[1];
                        else $instanceId=null;
                        if (isset($config[2])) $remoteOID=$config[2];
                        else $remoteOID=null;
                        if (isset($config[3])) $typeDef=$config[3];
                        else $typeDef=null;
                        echo str_pad("$componentName($instanceId,$remoteOID,$typeDef)",80);
                        $component = new $componentName($instanceId,$remoteOID,$typeDef);       // Parameter haben keine Auswirkung, Logging wird direkt aufgerufen
                        /* zwei class Methoden, eine für den class Namen, eh der gerade verwendete, und spannender der für den Logger, damit kann er direkt aufgerufen werden, und nicht über den 
                         * Umweg des Components, wie zB bei HandleEvent und UpdateEvent
                         * wichtig ist getVariableOIDLogging, diese liefert eine brauchbare Zusammenfassung, wenn sie implementiert ist
                         */
                        if (method_exists($component,"GetComponentParams")) 
                            {
                            $class=$component->GetComponentParams($event["EventID"]);                // da kommt wieder der componentName raus
                            //print_R($class);
                            }
                        if (method_exists($component,"GetComponentLogger")) 
                            {
                            $loggerName=$component->GetComponentLogger();                // da kommt jetzt der Logger raus
                            if ($loggerName && ($loggerName !== ""))
                                {
                                //echo "     $loggerName(".$event["EventID"].",null,null,$typeDef) ";
                                $logger = new $loggerName($event["EventID"],null,null,$typeDef);
                                $variableInfo=$logger->getVariableOIDLogging();
                                if (isset($variableInfo["variableLogID"]))
                                    {
                                    $variableLogID = $variableInfo["variableLogID"];
                                    echo str_pad($variableLogID,8).str_pad(GetValueIfFormatted($variableLogID),20).IPS_GetLocation($variableLogID);
                                    $variableInfo["LocationLog"]=IPS_GetLocation($variableLogID);   
                                    }
                                else echo " ".json_encode($variableInfo);
                                }
                        /* Motion_Logging($variable,$variablename=Null, $value=Null, $typedev="unknown", $debug=false) 
                         *
                         */



                            }
                        else $logger=false;
                        }
                    }
                }
            if ($componentName && $loggerName) 
                {
                echo "     ".exectime($startexec)." Sekunden\n";
                //echo "  $componentName $loggerName\n";
                // Component

                // Logger

                // MirrorID

                // Instance

                // RemoteIDs

                // TypeDev

                $this->eventlist[$index]["LoggingInfo"]=$variableInfo;
                }
            elseif ($componentName) echo "  $componentName , LoggerName missing\n";
            else echo "     ".$event["Config"]."\n";
            if ($first)
                {
                echo "First Event for $index : ";
                print_r($this->eventlist[$index]);
                $first=false;
                }
            }
        }


    /* DetectEventListHandler::extendGroups, object oriented
     * aus alle Components mit Parent DetectHandler die Group Config rausnehmen und den Events zuordnen
     * die class muss eine methode mit Get_EventConfigurationAuto und als Abfrage auf Aufruf der Methode is_groupconfig true liefern (!)
     * eventlist um Group erweitern und alle Configfiles mit dem Class Identifier darunter hängen
     */
    public function extendGroups($parentclass="DetectHandler",$debug=false)
        {
        $status=false;

        // von function im parent übernehmen, prozedural, nicht oop, 
        if ($debug) echo "add group Configurations from:\n";
        $classes = new phpOps();
        $classes->load_classes();                       // alle Klassen
        $classes->filter_classes($parentclass);         // Klassen mit Parent
        $output = $classes->get_classes();              // Ergebnis für foreach übergeben

        //print_r($output);
        $result=array();
        foreach ($output as $className => $items)
            {
            $config=false;
            if ($className != ".")
                {
                if ($debug) echo "  ".str_pad($className,30);
                if ( (class_exists($className)) && (method_exists($className,"Get_EventConfigurationAuto")) )
                    {
                    $groupConfig = new $className();
                    if ($groupConfig->is_groupconfig())
                        {
                        if ($debug) echo "available ";
                        $config = $groupConfig->Get_EventConfigurationAuto();
                        }
                    }
                if ($debug) echo "\n";
                }
            if ($config) 
                {
                //print_R($config);
                foreach ($config as $event => $params)
                    {
                    $result[$event][$className]=$params;
                    }
                }
            }
        //print_r($result);
        foreach ($this->eventlist as $event => $item)
            {
            //echo $event."\n";
            if (isset($result[$event])) 
                {
                foreach ($result[$event] as $groupName => $group)
                    {
                    $status=true;
                    $this->eventlist[$event]["Group"][$groupName]=json_encode($group);
                    }
                }
            }
        return ($status);            
        }

    public function extendRemoteAccess(XConfigurator $xconfig,$debug=false)
        {
        $ipsOps = new ipsOps();
        $excludeModules=null;               // ganze Module löschen
        $i=0; 
        foreach ($this->eventlist as $oid => $data)
            {
            if ($oid != $data["EventID"]) echo "Wrong configuration ";
            $typeUpdateChange=explode("_",$data["Name"])[0];
            if ($debug) echo str_pad($i,3," ",STR_PAD_LEFT)." | ".$data["EventID"]." | ".str_pad($typeUpdateChange,8)." | ".str_pad($data["Component"],80)." | ".str_pad($data["Module"],30);
            if (IPS_ObjectExists($oid))
                {
                if ($debug) echo " | ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),65)."    | ".str_pad(GetValue($oid),25);
                if (isset($excludeModules))                 // delete full modules
                    {
                    foreach ($excludeModules as $module)
                        {
                        if ($ipsOps->isMemberOfCategoryName($oid,$module)) 
                            {
                            if ($debug) echo " | $module";
                            $delete[$oid]=true;
                            //$messageHandler->UnRegisterEvent($eventID);
                            //IPS_DeleteEvent($childrenID);
                            }
                        }
                    }
                $remoteData=$xconfig->checkRemoteOIDData($data["Component"],$debug);
                if ($debug) echo json_encode($remoteData)."\n";
                if ($remoteData===false)                 
                    {
                    //echo "    Warning, RemotAccess failed, wrong Data, Object will be deleted from messagehandler. Install again \n";
                    $delete[$oid]=true;
                    }
                }
            else
                {
                if ($debug) echo "  ---> OID $oid nicht verfügbar !\n";
                $delete[$oid]=true;
                }
            $i++;
            }
        return ($delete);
        }


    /* DetectEventListHandler::alignByDeviceList, object oriented
     * die devicelist vorher umwandeln und aus allen Channeln die eine zugeordneten Type haben die Coid herausfiltern und ein array daraus zusammenstellen
     * dieses mit der eventliste abgleichen und erweitern
     * eventlist vs coid list from devicelist
     * array: output left and right, the intersection is the aligned eventlist
     * summary: one echo with left, aligned, right
     */
    public function alignByDeviceList(DeviceListManagement $devicelist,$output="array")
        {
        $coids=$devicelist->get_coids();          // die Coid Liste aus der Devicelist holen
        //print_r($coids);
        if ($output=="summary") echo "DetectEventListHandler::alignByDeviceList: compare eventlist with devicelist:\n";
        //print_r($this->eventlist);    
        $echos=true; $left=0;$aligned=0; $right=0;
        if (($output=="array") || ($output="summary")) $echos=false;
        $result=array();
        foreach ($this->eventlist as $index => $entry)
            {
            $eventID=$entry["EventID"];             // die TargetVariable, das TriggerEvent     
            if (isset($coids[$eventID]) === false)  
                { 
                if ($echos) echo "do not find $eventID ".$entry["NameEvent"]." from eventList in devicelist. Nicht alle Events sind in der DeviceList erfasst.\n";
                else $result["Left"][$eventID]=$entry;
                $left++;
                //echo json_encode($entry)."\n";
                }
            else    
                {
                $this->eventlist[$index]["DeviceList"]=$coids[$eventID];
                if (isset($coids[$eventID]["TOPO"])) 
                    {
                    $topo=$coids[$eventID]["TOPO"];
                    unset($this->eventlist[$index]["DeviceList"]["TOPO"]);
                    $this->eventlist[$index]["TOPO"]=$topo;
                    }
                if (isset($coids[$eventID]["QUALITY"])) 
                    {
                    $this->eventlist[$index]["QUALITY"]=$coids[$eventID]["QUALITY"];
                    }
                $aligned++;
                }
            }
        $eventlistordered=array();                          // ist scho passiert, aber sicherheitshalber        
        foreach ($this->eventlist as $index => $entry) 
            {
            //echo json_encode($entry)."\n"; 
            $eventlistordered[$entry["EventID"]]=$entry;
            }
        foreach ($coids as $eventID => $entry)
            {
            if (isset($eventlistordered[$eventID])===false)
                {
                if ($echos) echo "Devicelist Variable ".IPS_GetName($eventID)." von ".$entry["OID"]." ".IPS_GetName($entry["OID"])." mit Typ ".$entry["Type"]." mit OID $eventID nicht in eventlist eingetragen. ".json_encode($entry)."\n";
                else $result["Right"][$eventID]=$entry;
                $right++;
                }
            }
        if ($output=="summary") echo "    result alignByDeviceList : eventlist $left aligned $aligned devicelist channels $right.\n"; 
        if ($echos===false) return ($result);

        }



	/**
	 * DetectEventListHandler, getEventListforDeletion Werte ausgeben die beim construct bereits erstellt wurden
	 * es gibt Events in der IPS_MessageHandler Konfigurationsdatei/function denen keine eigene Konfiguration zugeordnet ist
	 * berücksichtigt sowohl Configuration als auch ConfigurationCust
	 *
	 *
     * 
     */
	public function getEventListforDeletion() 
		{	
        return ($this->eventlistDelete);
        }
    
    /* DetectEventListHandler::getEventList
     * die eventlist zurückgeben
     * wenn style true ist nur die quality informationen zurückgeben
     *  
     * */
	public function getEventList($style=false) 
		{
        if ($style==false) return ($this->eventlist);
        else 
            {
            $filtered=array();
            foreach ($this->eventlist as $eventId => $entry)
                {
                if (isset($entry["LoggingInfo"]["variableLogID"])) $key=$entry["LoggingInfo"]["variableLogID"];
                else $key=$eventId;
                if (isset($entry["QUALITY"])) $filtered[$key]=$entry["QUALITY"];
                }
            return ($filtered);
            }
        }

	/* DetectEventListHandler::getEventListfromIPS
     * erst einmal alle Events von IP Symcon auslesen und in einem neuen Format ausgeben
     * Es gibt filter als Parameter, das ist der Name des Parents des Events
     * Alternative Funktion vorhanden bei dem die Events die einer ScriptId zugeordnet ausgelesen werden, diese Variante ist sicherer
     * da nicht ausgeschlossen werden kann, dass die Event snicht dem richtigen Script als Parent zugeordnet sind
	 *
	 */
	public function getEventListfromIPS($filter="",$debug=false) 
		{	
        $resultEventList=array();

        /* Alle Events einsammeln und strukturieren */
        $alleEreignisse = IPS_GetEventList();
        $index=0;
        foreach ($alleEreignisse as $ereignis)
            {
            if ( ($filter == "") || ($filter == IPS_GetName(IPS_GetParent($ereignis))) )
                {
                $resultEventList[$index]["OID"]=$ereignis;
                $resultEventList[$index]["Name"]=IPS_GetName($ereignis);
                $resultEventList[$index]["Pfad"]=IPS_GetName(IPS_GetParent($ereignis))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($ereignis)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($ereignis))))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent(IPS_GetParent($ereignis)))));
                $details=IPS_GetEvent($ereignis);
                //print_r($details);
                switch ($details["EventType"])
                    {
                    case 0:
                        $resultEventList[$index]["Type"]="Auslöser";
                        break;
                    case 1:
                        $resultEventList[$index]["Type"]="Zyklisch";
                        break;
                    case 2:
                        $resultEventList[$index]["Type"]="Wochenplan";
                        break;
                    }
                $resultEventList[$index]["LastRun"]=$details["LastRun"];
                //$resultEventList[$index]["EventConditions"]=$details["EventConditions"];
                $resultEventList[$index]["TriggerVariableID"]=$details["TriggerVariableID"];
                $script=str_replace("\n","",$details["EventScript"]);
                $resultEventList[$index]["Script"]=$script;
                $index++;
                }
            }
        if ($debug) echo "testMovement::getEventListfromIPS mit Filter \"$filter\" wurden aus ".count($alleEreignisse)." insgesamt ".count($resultEventList)." Eintraege.\n";
        return ($resultEventList);
        }



	/**************************************************
	 *
	 * nicht nur die CustomComponents Events bearbeiten, sondern auch Autosteuerungs Events anschauen, hier die Liste erstellen
	 *
	 * mit der CustomComponents Eventliste auch vergleichen
	 *
	 *
	 * Alle Events die unter dem Autosteuerung Script angelegt sind durchgehen
	 * Events die nicht mit O anfangen ignorieren. Es gibt noch einen Aufruftimer für die Anwesenheitssimulation
	 * Die Events heissen OnUpdtae oder Unchange mit _ und der ID des überwachten Objektes im Anschluss.
	 *
	 * #, OID, Name, EventID, Pfad, EventName, Instanz, Typ, Config, Homematic usw.
	 *      #, EventID nur ein Index von 0 aufsteigend 
	 *      OID, ID, die ID des Events
	 *      Name, der Name des Events (OnUpdate/OnChange_EventID
	 *      EventID, ObjektID aus dem Namen extrahiert, steht nach dem _
	 *          Wenn das Object zur EventID nicht vorhanden ist, wird ein Fehler (2) für diese Zeile ausgegeben
	 *          Wenn für das Objekt keine Configuration vorlieget gibt es ebenfalls einen Fehler (2)
	 *          Wenn OnChange oder OnUpdate nicht zur Configuration passt wird ebenfalls ein Fehler (3) ausgegeben
	 *      Pfad, Objektpfad/Fehler bis zum Object (damit kann man die Herkunft bestimmen, hier steht der fehler wenn es einer ist)
	 *      EventName, NameEvent, Objektname, der Object Name
	 *      Instanz, Module für das Object zur EventID wird geprüft ob der Parent eine instanz ist, Wenn dann den Namen hier hinschreiben
	 *      Typ, ist der DetectMovement Auswertungstyp, in welcher Configuration steht das IPS_GetObject
	 *      Config, ist die Config aus dem MessageHandler
	 *      Homematic, ist das Object eine Homematic Instanz und wenn ja welche*
	 *****************************************/
	
	public function getAutoEventListTable($autosteuerung_config, $debug=true)
		{
		//print_r($autosteuerung_config);
		$i=0; $delete=0;
		$motionDevice=$this->motionDevice;		/* kopieren, da zusaetzliche Eintraege dazu gemacht werden */
		$switchDevice=$this->switchDevice;
		$buttonDevice=$this->buttonDevice;
		//print_r($motionDevice); print_r($switchDevice);
		if ($debug)
			{ 
			if ( (sizeof($motionDevice) == 0) || (sizeof($motionDevice) == 0) ) echo "********** Fehler, keine Homematic Typen erkannt.\n";
			echo "\n============================================================\n";
			echo "Autosteuerung Events auslesen:\n";
			}		
			  			
		$eventlist=array();
		$eventlistDelete=array();		// Sammlung der Events für die es kein Objekt mehr dazu gibt
		$eventlistCC=$this->eventlist;
		$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));
		$children=IPS_GetChildrenIDs($scriptId);		// alle Events des IPSMessageHandler erfassen
		foreach ($children as $childrenID)
			{
			$name=IPS_GetName($childrenID);
			if (substr($name,0,1)=="O")									// sollte mit O anfangen, Aufruftimer alle 5 Minuten wird herausgenommen
				{ 
				//$eventID_str=substr($name,Strpos($name,"_")+1,10);
				$event=explode("_",$name);
				$eventID_str=$event[1];
				$eventID=(integer)$eventID_str;
				
				$eventlist[$i]["OID"]=$childrenID;				
				$eventlist[$i]["Name"]=IPS_GetName($childrenID);
				$eventlist[$i]["EventID"]=$eventID;
				if (IPS_ObjectExists($eventID)==false)
					{ /* Objekt für das Event existiert nicht */
					$delete++;
					if ($debug) echo "***".$eventID." existiert nicht.\n";
					$eventlist[$i]["Fehler"]='does not exists any longer. Event has to be deleted ***********.';
					$eventlistDelete[$eventID_str]["Fehler"]=1;
					$eventlistDelete[$eventID_str]["OID"]=$childrenID;
					//if (isset($eventlistConfig[$eventID_str])) echo "**** und Event ".$eventID_str." auch aus der Config Datei loeschen.: ".$eventlistConfig[$eventID_str][1].$eventlistConfig[$eventID_str][2]."\n";
					}	
				elseif (isset ($autosteuerung_config[$eventID])==false )
					{	/* kein Eintrag in der Konfiguration für dieses Event */
					$delete++;
					if ($debug) echo "***".$eventID." hat keine Konfiguration\n";
					$eventlist[$i]["Fehler"]='has no Configuration in Autosteuerung. Event has to be deleted ***********.';
					$eventlistDelete[$eventID_str]["Fehler"]=2;
					$eventlistDelete[$eventID_str]["OID"]=$childrenID;
					}
				elseif ($event[0] != $autosteuerung_config[$eventID][0])
					{ /* Event für das Objekt entspricht nicht der Konfiguration */
					$delete++;
					if ($debug) echo "***".$eventID." hat falschen Typ. Konfiguration : ".$autosteuerung_config[$eventID][0].",".$autosteuerung_config[$eventID][1].",".$autosteuerung_config[$eventID][2]." versus ".$event[0]."\n";
					$eventlist[$i]["Fehler"]='has wrong type OnUpdate/OnChange. Event has to be deleted ***********.';
					$eventlistDelete[$eventID_str]["Fehler"]=3;
					$eventlistDelete[$eventID_str]["OID"]=$childrenID;
					}
				else	
					{ /* Objekt für das Event existiert, den Pfad dazu ausgeben */
					$instanzID=IPS_GetParent($eventID);
					if ($debug) echo "   ".$eventID."  ".IPS_GetName($instanzID)." Type : ";
					$instanz="";
					switch (IPS_GetObject($instanzID)["ObjectType"])
						{
						/* 0: Kategorie, 1: Instanz, 2: Variable, 3: Skript, 4: Ereignis, 5: Media, 6: Link */
						case 0:
							if ($debug) echo "Kategorie";
							break;
						case 1:
							$instanz=IPS_GetInstance($instanzID)["ModuleInfo"]["ModuleName"];
							if ($debug) echo "Instanz ";
							break;
						case 2:
							if ($debug) echo "Variable";
							break;
						case 3:	
							if ($debug) echo "Skript";
							break;
						case 4:
							if ($debug) echo "Ereignis";
							break;
						case 5:
							if ($debug) echo "Media";
							break;
						case 6:
							if ($debug) echo "Link";
							break;
						default:
							echo "unknown";
							break;
						}
					if (IPS_GetObject($eventID)["ObjectType"]==2) 	
						{
						if ($debug) echo $instanz;
						}
					else 	
						{
						if ($debug) echo "Fehler, Objekt ist vom Typ keine Variable.   ";
						if ($debug) echo "Objekt : ".$eventID." Instanz : ".IPS_GetName($instanzID);
						}
					$eventlist[$i]["Pfad"]=IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($eventID))))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($eventID)))."/".IPS_GetName(IPS_GetParent($eventID));
					$eventlist[$i]["NameEvent"]=IPS_GetName($eventID);
					$eventlist[$i]["Instanz"]=$instanz;
					/* Array Format: <index> OID Name EventID Pfad NameEvent Instanz Typ Config Homemeatic DetectMovemeent Autosteuerung */
					$eventlist[$i]["DetectMovement"]="";
					$found=false;
					foreach ($eventlistCC as $entry) { if ($entry["EventID"]==$eventID) $found=true; }
					if ( $found ) 
						{
						$eventlist[$i]["DetectMovement"]="CustomComponent";
						if ($debug) echo " Typ CustomComponent";
						}
					else $eventlist[$i]["DetectMovement"]="";
					$eventlist[$i]["Typ"]="";
					$eventlist[$i]["Config"]="";
					
					if (isset($motionDevice[$eventID])==true)
						{ 
						$eventlist[$i]["Homematic"]="Motion";
						$motionDevice[$eventID]=false;
						if ($debug) echo " Homematic Motion";
						}
					elseif (isset($switchDevice[$eventID])==true)
						{
						$eventlist[$i]["Homematic"]="Switch";
						$switchDevice[$eventID]=false;
						if ($debug) echo " Homematic Switch";
						}     
					elseif (isset($buttonDevice[$eventID])==true)
						{
						$eventlist[$i]["Homematic"]="Button";
						$buttonDevice[$eventID]=false;
						if ($debug) echo " Homematic Button";
						}     
					else $eventlist[$i]["Homematic"]="";
										
					$eventlist[$i]["Autosteuerung"]="";
					if (isset($autosteuerung_config[$eventID])==true)
						{ 
						$eventlist[$i]["Autosteuerung"]=$autosteuerung_config[$eventID][1]."|".$autosteuerung_config[$eventID][2];
						$autosteuerung_config[$eventID]["EventExists"]=true; 
						}
					else if ($debug) echo " Fehler************************";                       
					if ($debug) echo "\n";
					}				
				}
			$i++;
			//IPS_SetPosition($childrenID,$eventID);				
			}		
		//print_r($eventlist);
		return ($eventlist);
		}			    
	
    /* DetectEventListHandler::printEventList, object oriented
     * die eventlist auf der Konsole ausgeben
     * und die verwendeten Components zählen
     */
    public function getUsedComponents($debug=false)
        {
        $usedComponents=array();
        $componentHandling=new ComponentHandling();
        foreach ($this->eventlist as $eventId => $entry)
            {
            if ($debug) echo str_pad($eventId,8);
            if (isset($entry["Component"])) 
                {
                $config=explode(",",$entry["Component"]);
                // Component raussuchen, aus dem className IPSComponentSensor_Motion das IncludeFile ableiten, componentType bis zum Underscore aber mit IPModule statt IPSComponent
                if (isset($config[0]))
                    { 
                    $className=$config[0];
                    if ($debug) echo str_pad($className,35).$entry["Component"];
                    if (isset($usedComponents[$className])) $usedComponents[$className]["Count"]++;
                    else $usedComponents[$className]["Count"]=1;
                    $pos = strpos($className, '_');
                    if ($pos===false) echo "Fehler, Class Name entspricht nicht der Naming Convention : \n";
                    else    
                        {
                        $componentType = substr($className, 0, $pos);
                        $componentType = str_replace('IPSModule','IPSComponent', $componentType);
                        if (isset($config[1])) $instanceId=$config[1];
                        else $instanceId=null;
                        if (isset($config[2])) $remoteOID=$config[2];
                        else $remoteOID=null;
                        $keyName=array();
                        if (isset($config[3])) 
                            {
                            $typeDef=$config[3];
                            if (isset($usedComponents[$className][$typeDef])) $usedComponents[$className][$typeDef]["Count"]++;
                            else $usedComponents[$className][$typeDef]["Count"]=1;
                            $keyName["KEY"]=$typeDef;
                            }
                        else $typeDef=null;
                        if (isset($entry["LoggingInfo"]["variableLogID"])) 
                            {
                            $variableLogID = $entry["LoggingInfo"]["variableLogID"];
                            $variableType=IPS_GetVariable($variableLogID);
                            $profileName="";
                            if ( (isset($variableType["VariableProfile"])) && ($variableType["VariableProfile"] != "") ) $profileName=$variableType["VariableProfile"];
                            elseif ( (isset($variableType["VariableCustomProfile"])) && ($variableType["VariableCustomProfile"] != "") ) $profileName=$variableType["VariableCustomProfile"];

                            if (isset($usedComponents[$className][$typeDef]["Variables"][$profileName])) $usedComponents[$className][$typeDef]["Variables"][$profileName]["Count"]++;
                            else  $usedComponents[$className][$typeDef]["Variables"][$profileName]["Count"]=1;

                            if (isset($keyName["KEY"]))
                                {
                                $componentHandling->addOnKeyName($keyName,$debug);
                                if ($debug) echo str_pad($eventId,8).str_pad($entry["NameEvent"],40).str_pad($className,35)." Variable Profile: ".json_encode($keyName)."\n";
                                $expectedProfile=$keyName["PROFILE"];
                                if ( ($expectedProfile != "") && ($profileName != $expectedProfile) )
                                    {
                                    echo "  ***** Warnung, VariableProfile stimmt nicht überein, erwartet $expectedProfile, ist $profileName \n";
                                    }
                                }
                            }
                        }
                    }
                elseif ($debug)  echo $entry["Component"];
                }
            elseif ($debug) echo " no Component defined ";
            if ($debug) echo "\n";   
            }
        return ($usedComponents);
        }

    /***************************************
     *
     * Ausgabe eines Arrays als html formatierte Tabelle. Entweder ein Array wird übergeben oder das interne wird verwendet
     * Array Format: <index> OID Name EventID Pfad NameEvent Instanz Typ Config Homemeatic DetectMovemeent Autosteuerung
     *
     **************************/

	public function writeEventListTable($eventlist=array())
		{
		if (sizeof($eventlist)==0) $eventlist=$this->eventlist;
		$html="";
		$html.="<style>";
		$html.='#customers { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; font-size: 12px; color:black; border-collapse: collapse; width: 100%; }';
		$html.='#customers td, #customers th { border: 1px solid #ddd; padding: 8px; }';
		$html.='#customers tr:nth-child(even){background-color: #f2f2f2;}';
		$html.='#customers tr:nth-child(odd){background-color: #e2e2e2;}';
		$html.='#customers tr:hover {background-color: #ddd;}';
		$html.='#customers th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; }';
		$html.="</style>";
	
		$html.='<table id="customers" >';
		$html.="<tr><th>Event #</th><th>ID</th><th>Name</th><th>ObjektID</th><th>Objektpfad/Fehler</th><th>Objektname</th><th>Module</th><th>Funktion</th><th>Konfiguration</th><th>Homematic</th><th>Detect Movement</th><th>Autosteuerung</th></tr>";
		foreach ($eventlist as $index=>$childrenID)
			{
			$html.="<tr><td>".$index."</td><td>".$childrenID["OID"]."</td><td>".$childrenID["Name"]."</td><td>".$childrenID["EventID"]."</td>";
			if (isset ($childrenID["Fehler"]) )	$html.='<td bgcolor=#00FF00">"'.$childrenID["Fehler"].'</td>';
			else
				{
				$html.="<td>".$childrenID["Pfad"]."</td><td>".$childrenID["NameEvent"]."</td><td>".$childrenID["Instanz"]."</td>";
				$html.="<td>".$childrenID["Typ"]."</td><td>".$childrenID["Config"]."</td>";
				$html.="<td>".$childrenID["Homematic"]."</td><td>".$childrenID["DetectMovement"]."</td>";
				$html.="<td>".$childrenID["Autosteuerung"]."</td>";
				$html.="</tr>";	 
				}			// ende check substring fangt mit 0 an	
			}			// ende foreach children
		$html.="</table>";
		return($html);
		}	// ende function

    /* findMotionDetection
     *
     * Geräte mit Bewegungserkennung finden
     * nutzt dazu die in EvaluateHardware_Include Datei gespeicherten HomematicList und FS20List
     *
     */
	public function findMotionDetection()
		{
		//$alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
		IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
		if ( function_exists("HomematicList") ) $Homematic = HomematicList();
        else $Homematic=array();

		if ( function_exists("FS20List") ) 
            { 
            $FS20 = FS20List(); 		
            $TypeFS20=RemoteAccess_TypeFS20(); // if there is no FS20 it will not be needed
            }
        else $FS20=array();
	
		$motionDevice=array();
	
		//echo "\n===========================Alle Homematic Bewegungsmelder ausgeben.\n";
		foreach ($Homematic as $Key)
			{
			/* Alle Homematic Bewegungsmelder ausgeben */
			if ( (isset($Key["COID"]["MOTION"])==true) )
				{
				/* alle Bewegungsmelder */
				$oid=(integer)$Key["COID"]["MOTION"]["OID"];
				$motionDevice[$oid]=true;
				//$log=new Motion_Logging($oid);
				//$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
				{
				/* alle Kontakte */
				$oid=(integer)$Key["COID"]["STATE"]["OID"];
				$motionDevice[$oid]=true;
				//$log=new Motion_Logging($oid);
				//$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			}
		//echo "\n===========================Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein.\n";
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		foreach ($FS20 as $Key)
			{
			/* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
			if ( (isset($Key["COID"]["MOTION"])==true) )
				{
				/* alle Bewegungsmelder */
				$oid=(integer)$Key["COID"]["MOTION"]["OID"];
				$motionDevice[$oid]=true;
				//$log=new Motion_Logging($oid);
				//$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			/* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei verknüpfen */
			if ((isset($Key["COID"]["StatusVariable"])==true))
				{
				foreach ($TypeFS20 as $Type)
					{
					if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
						{
						$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
						$variabletyp=IPS_GetVariable($oid);
						IPS_SetName($oid,"MOTION");
						$motionDevice[$oid]=true;
						//$log=new Motion_Logging($oid);
						//$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
						}
					}
				}
			}
		//echo "\n===========================Alle IPCam Bewegungsmelder ausgeben.\n";
		if (isset ($installedModules["IPSCam"]))
			{	
			IPSUtils_Include ("IPSCam.inc.php",     "IPSLibrary::app::modules::IPSCam");
			$camManager = new IPSCam_Manager();
			$config     = IPSCam_GetConfiguration();
			echo "Folgende Kameras sind im Modul IPSCam vorhanden:\n";
			foreach ($config as $cam)
				{
				//echo "   Kamera : ".$cam["Name"]." vom Typ ".$cam["Type"]."\n";
				}
			if (isset ($installedModules["OperationCenter"]))
				{
				//echo "IPSCam und OperationCenter Modul installiert. \n";
				IPSUtils_Include ("OperationCenter_Configuration.inc.php",     "IPSLibrary::config::modules::OperationCenter");
				$OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
				$OperationCenterConfig=OperationCenter_Configuration();
				if (isset ($OperationCenterConfig['CAM']))
					{
					foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
						{
						$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$OperationCenterDataId);
						$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
						//echo "   Bearbeite Kamera : ".$cam_name." Cam Category ID : ".$cam_categoryId."  Motion ID : ".$WebCam_MotionID."\n";
						$motionDevice[$WebCam_MotionID]=true;
						//$log=new Motion_Logging($WebCam_MotionID);
						//$alleMotionWerte.="********* ".$cam_name."\n".$log->writeEvents()."\n\n";
						}
					}  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
				}     /* isset OperationCenter */
			}     /* isset IPSCam */
			
		//$alleMotionWerte.="********* Gesamtdarstellung\n".$log->writeEvents(true,true)."\n\n";
		//echo $alleMotionWerte;
			
		return($motionDevice);
		}

    /*******************************
     *
     * Geräte mit Schaltfunktion finden
     *
     **************************************************************/

	public function findSwitches()
		{
		IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");		
		if ( function_exists("HomematicList") ) $Homematic = HomematicList();
        else $Homematic=array();

		if ( function_exists("FS20List") ) 
            { 
            $FS20 = FS20List(); 		
            $TypeFS20=RemoteAccess_TypeFS20(); // if there is no FS20 it will not be needed
            }
        else $FS20=array();
	
		$switchDevice=array();
	
		//echo "\n===========================Alle Homematic Switche ausgeben.\n";
		foreach ($Homematic as $Key)
			{
			/* Alle Homematic Switche ausgeben */
    		if ( isset($Key["COID"]["STATE"]) and isset($Key["COID"]["INHIBIT"]) and (isset($Key["COID"]["ERROR"])==false) )
				{
                //print_r($Key);
				$oid=(integer)$Key["COID"]["STATE"]["OID"];
				$switchDevice[$oid]=true;
				}
    		/* alle HomematicIP Switche ausgeben */
		    if ( isset($Key["COID"]["STATE"]) and isset($Key["COID"]["SECTION"]) and isset($Key["COID"]["PROCESS"]) )
				{
				$oid=(integer)$Key["COID"]["STATE"]["OID"];
				$switchDevice[$oid]=true;
				}
			}
		//echo "\n===========================Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein.\n";
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		foreach ($FS20 as $Key)
			{
    		/* FS20 alle Schalterzustände ausgeben */
	    	if (isset($Key["COID"]["StatusVariable"])==true)
		    	{
				$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
				$switchDevice[$oid]=true;
				}
			}
		return($switchDevice);
		}
		
    /*******************************
     *
     * Geräte mit Taster finden
     *
     **************************************************************/

	public function findButtons()
		{
		IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");		
		if ( function_exists("HomematicList") ) $Homematic = HomematicList();
		else $Homematic=array();

		if ( function_exists("FS20List") ) 
            { 
            $FS20 = FS20List(); 		
            $TypeFS20=RemoteAccess_TypeFS20(); // if there is no FS20 it will not be needed
            }
        else $FS20=array();
	
		$buttonDevice=array();
		
		//echo "\n===========================Alle Homematic Buttons ausgeben.\n";
		foreach ($Homematic as $Key)
			{
			if ( ( (isset($Key["COID"]["INSTALL_TEST"])==true) and (isset($Key["COID"]["PRESS_SHORT"])==true) ) ||
					( (isset($Key["COID"]["PRESS_LONG"])==true) and (isset($Key["COID"]["PRESS_SHORT"])==true) ) )
				{
				//print_r($Key);			
				$oid=(integer)$Key["COID"]["PRESS_SHORT"]["OID"];
				$buttonDevice[$oid]=true;
				if (isset($Key["COID"]["INSTALL_TEST"])==true)
					{				
					$oid=(integer)$Key["COID"]["INSTALL_TEST"]["OID"];
					$buttonDevice[$oid]=true;
					}
				if (isset($Key["COID"]["PRESS_LONG"])==true)
					{
					$oid=(integer)$Key["COID"]["PRESS_LONG"]["OID"];
					$buttonDevice[$oid]=true;					
					}
				}
			}
		return ($buttonDevice);
		}
		
		
    /************************************************************
     *
     * eventlist nach Kriterien/Ueberschriften sortieren
     *
     ****************************************************************************/

	public function sortEventList($on,$array=array())
		{
		$order="SORT_ASC";
		$new_array = array();
		$sortable_array = array();
		if ( sizeof($array)==0 ) $array=$this->eventlist;
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
		
	}		// ende class




	/** @}*/
?>