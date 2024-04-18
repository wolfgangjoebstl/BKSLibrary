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
     * DetectSensorHandler extends DetectHandler            allgemeine Bearbeitung Sensor
     * DetectCounterHandler extends DetectHandler            allgemeine Bearbeitung Counter
     * DetectClimateHandler extends DetectHandler            
     * DetectHumidityHandler extends DetectHandler
     * DetectMovementHandler extends DetectHandler
     * DetectBrightnessHandler extends DetectHandler
     * DetectContactHandler extends DetectHandler
     * DetectTemperatureHandler extends DetectHandler
     * DetectHeatControlHandler extends DetectHandler
     * DetectHeatSetHandler extends DetectHandler
     *
     * DetectHandlerTopology extends DetectHandler 
     * DetectDeviceHandler extends DetectHandlerTopology, writes function IPSDetectDeviceHandler_GetEventConfiguration in EvaluateHardware_Configuration.inc.php	 
     * DetectDeviceListHandler extends DetectHandlerTopology, writes function IPSDetectDeviceListHandler_GetEventConfiguration in EvaluateHardware_Configuration.inc.php
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
	 *
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
     **********************************************/

	abstract class DetectHandler 
        {
		/* von den extended Classes mindestens geforderte Funktionen */
		abstract function Get_Configtype();
		abstract function Get_ConfigFileName();		
				
		abstract function Get_EventConfigurationAuto();
		abstract function Set_EventConfigurationAuto($configuration);
		
		abstract function CreateMirrorRegister($variableId);

		protected $installedModules, $log_OperationCenter;

  		protected $Detect_DataID;												/* Speicherort der Mirrorregister, von getMirrorRegisterName verwendet */ 
        protected $debug;
        protected $archiveHandlerID;
		
		/**
		 * @public
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

        /* get protected Detect_DataID, das ist die Component Category aus CustomCoponent für die jeweilige Class
         *
         */
        public function getDetectDataID()
            {
            return ($this->Detect_DataID);
            }

        /* clone profile and type from original variable
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
            else return ([$variableType,$variableProfile]);
            }

		/**
		 * @private, DetectHandler
		 *
		 * Speichert die aktuelle Event Konfiguration
		 *
		 * @param string[] $configuration Konfigurations Array
		 */
		function StoreEventConfiguration($configuration, $comment="")
			{
			//$configurationSort=$this->sortEventList($configuration);			
            //print_r($configuration);
			// Build Configuration String
			$configString = $this->Get_Configtype().' = array('.PHP_EOL;
            $configString .= chr(9).chr(9).chr(9).'/* This is the new comment area : '.$comment.' */';
			foreach ($configuration as $variableId=>$params) 
                {
                if (IPS_ObjectExists($variableId))
                    {                    
                    $configString .= PHP_EOL.chr(9).chr(9).chr(9).$variableId.' => array(';
                    for ($i=0; $i<count($params); $i=$i+3)          // schreibt in Dreierreihe, Infos sollten nicht verlorengehen
                        {
                        //if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';                // keine newline für die nächsten drei Parameter
                        if ($i>0)                                                                                   // nach den ersten 3 Parametern keine Limitierung, aber weiterhin Abarbeitung in Dreiergruppen
                            {
                            for ($j=0;(isset($params[$i+$j])&&($j<3));$j++) $configString .= "'".$params[$i+$j]."',";
                            }
                        else $configString .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
                        }
                    $configString .= '),';
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
				/* echo "================================================\n";
				echo "Function not inserted in config file. Insert now.\n";
				$comment=0; $posn=false;	// letzte Klammer finden und danach einsetzen 
				for ($i=0;$i<strlen($fileContent);$i++)
					{
					switch ($fileContent[$i])
						{
						// comment : 0 ok 1 / erkannt 2 / * erkannt 3 // erkannt 
						case '/':
							if ( ($comment==2) && ($star==1) ) $comment=0;
							elseif ($comment==1) $comment=3;
							else $comment=1;
							$star=0;
						case '*':
							if ($comment==1) $comment=2;
							$star=1;	
							break;
						case '}':
						if ($comment <2 ) $posn=$i;	
						case chr(10):
						case chr(13):
							if ($comment==3) $comment=0;		
						default:
							$star=0;
							break;
						}		
					}
				// $posn = strrpos($fileContent, '}');  erkennt auch Klammern innerhalb von Kommentaren   */
				$posn = $this->insertNewFunction($fileContent);

				if ( $posn === false )
					{
					throw new IPSMessageHandlerException('EventConfiguration File maybe empty !!!', E_USER_ERROR);
					}
				//echo $fileContent."\n  Position last } : ".$posn."   ".substr($fileContent, $posn,5)."\n";	
				$configString="\n	 function IPS".get_class($this)."_GetEventConfiguration() {\n                          ".$configString."\n".'		return '.$this->Get_Configtype().";\n	}\n";
				$fileContentNew = substr($fileContent, 0, $posn+1).$configString.substr($fileContent, $posn+1);
				//echo $fileContentNew;	
				//echo "\n\n class name : ".get_class($this)."\n";						
				//throw new IPSMessageHandlerException('EventConfiguration could NOT be found !!!', E_USER_ERROR);
				}
			else
				{	
				$fileContentNew = substr($fileContent, 0, $pos1).$configString.substr($fileContent, $pos2);
				}
			file_put_contents($fileNameFull, $fileContentNew);
			$this->Set_EventConfigurationAuto($configuration);
			}

        /* FileContent in eine Datei neu schreiben, gemeinsame function
         * die Position zurückmelden, wo die neue Function eingefügt werden kann
        */
        protected function insertNewFunction($fileContent,$debug=false)
            {
            if ($debug)
                {
				echo "================================================\n";
				echo "Function not inserted in config file. Insert now.\n";
                }
            $comment=0; $posn=false;	// letzte Klammer finden und danach einsetzen, den ganzen String durchgehen
            for ($i=0;$i<strlen($fileContent);$i++)
                {
                switch ($fileContent[$i])           // der reihe nach auswerten // zeilenkommentar und /* kommentar erkennen
                    {
                    /* comment : 0 ok 1 / erkannt 2 /* erkannt 3 // erkannt */
                    case '/':
                        if ( ($comment==2) && ($star==1) ) $comment=0;
                        elseif ($comment==1) $comment=3;
                        else $comment=1;
                        $star=0;
                    case '*':
                        if ($comment==1) $comment=2;
                        $star=1;	
                        break;
                    case '}':
                    if ($comment <2 ) $posn=$i;	
                    case chr(10):
                    case chr(13):
                        if ($comment==3) $comment=0;		
                    default:
                        $star=0;
                        break;
                    }		
                }
            // $posn = strrpos($fileContent, '}');  erkennt auch Klammern innerhalb von Kommentaren
            
            if ( $posn === false )
                {
                if (strlen($fileContent) > 6 )
                    {
                    $posn = strrpos($fileContent, '?>')-1;
                    echo "Weit und breit keine Klammer. Auf Pos $posn function einfügen.\n";
                    }
                else throw new IPSMessageHandlerException('EventConfiguration File maybe empty !!!', E_USER_ERROR);
                }
            //echo $fileContent."\n  Position last } : ".$posn."   ".substr($fileContent, $posn,5)."\n";	

            return ($posn);
            }

		/**
		 * @public, DetectHandler
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
                echo get_class($this)."::ListGroups, Aufruf mit $type,$varId \n";
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

		/**
		 * @public
		 *
		 * ListConfigurations           Liest die ganze Konfiguration eines oder aller Events aus. Wertet auch die mirror Register config im par3 aus
		 * Rückmeldung ist ein config array. Index ist die Event OID, 
         *      [Room] ist par2
		 *      [Type] ist par3
         * wenn in par3 -> vorkommen, werden diese Register einzelnen Variablen zugeordnet
         *
		 */
		public function ListConfigurations($oid=false)
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

		/**
		 * getRoomNamefromConfig
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

		/**
		 * @public getMirrorRegister
         *
         * versucht das Mirror Register zu finden
		 *
		 */

		public function getMirrorRegister($variableId,$debug=false)
			{
            $variablename=$this->getMirrorRegisterName($variableId,$debug);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, getMirrorRegister for ".get_class()." \"$variablename\" nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            return ($mirrorID);
            }

		/* DetectHandler::getMirrorRegisterNamefromConfig
         *
         * liest die configuration des Events aus. Wenn ein Mirror Index vorkommt, diesen ausgeben.
		 *
		 */

        public function getMirrorRegisterNamefromConfig($oid,$debug=false)
            {
            $config = $this->ListConfigurations($oid);
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
                echo "getMirrorRegisterName: Mirror Register von Hardware Register ".$oid." suchen.\n";
                if ($this->Detect_DataID==0) echo "Fehler, Kategorien noch nicht vorhanden,\n";
			    else echo "Kategorie der Custom Components Spiegelregister : ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).")\n";
                }
            $variablename = $this->getMirrorRegisterNamefromConfig($oid,$debug);       /* wenn ein Wert in der Config abgelegt ist und der Wert vorhanden ist, gleich diesen nehmen */
            if ($variablename === false)
                {
                $result=@IPS_GetObject($oid);
                if ($result !== false)
                    {
                    $resultParent=IPS_GetObject((integer)$result["ParentID"]);
                    //print_R($resultParent);
                    if ($resultParent["ObjectType"]==1)     // Abhängig vom Typ (1 ist Instanz) entweder Parent (typischerweise Homematic) oder gleich die Variable für den Namen nehmen
                        {
                        $variablename=IPS_GetName((integer)$result["ParentID"]);		/* Hardware Komponente */
                        //echo "Mirror Register von Hardware Register ".$oid." aufgrund des Namens der übergeordneten Instanz suchen: $variablename\n";                    
                        }
                    else
                        {
                        $variablename=IPS_GetName($oid);
                        //echo "Mirror Register von Hardware Register ".$oid." aufgrund des eigenen Namens suchen: $variablename\n";                    
                        }
                    }
                }
			return($variablename);
			}

		/** 
		 * getMirrorRegisters
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

		/** 
		 * checkMirrorRegisters
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

		/**
		 * @public
		 *
		 * Erzeugt ein Event für eine übergebene Variable, das den IPSMessageHandler beim Auslösen
		 * aufruft.
		 *
		 * @param integer $variableId ID der auslösenden Variable
		 * @param string $eventType Type des Events (OnUpdate oder OnChange)
		 */

		public function CreateEvent($variableId, $eventType)
			{
			
			/* Funktion in diesem Kontext nicht mehr klar, wird von Create Events aufgerufen. Hier erfolgt nur ein check ob die Parameter richtig benannt worden sind */
			
			switch ($eventType)
				{
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

       /************************************************************
        *
        * eventlist nach Kriterien/Ueberschriften sortieren
        *
        ****************************************************************************/

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


        /**
            * @public
            *
            * Registriert ein Event im IPSMessageHandler. Die Funktion legt ein ensprechendes Event
            * für die übergebene Variable an und registriert die dazugehörigen Parameter im MessageHandler
            * Konfigurations File.
            *
            * Beispiel $Handler->RegisterEvent($soid,'Topology','Wohnzimmer','Temperature,Weather');
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
            // mehr als drei Inputvariablen können
            $targetcount=3;
            if (is_array($eventTypeInput)) 
                {
                $eventType=$eventTypeInput[0];
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
            $comment = "Letzter Befel war RegisterEvent mit VariableID ".$variableId." ".date("d.m.Y H:i:s");
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

                // Variable NOT found --> Create Configuration
                if (!$found)
                    {
                    if ($debug) echo "Create Event and store it if Update is true : ".($update?"true":"false")."\n";
                    $configurationAuto[$variableId][] = $eventType;
                    $configurationAuto[$variableId][] = $componentParams;
                    $configurationAuto[$variableId][] = $moduleParams;
                    if ($targetcount>3)
                        {
                        for ($i=3;$i<$tragetcount;$i=$i+3)
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

        /**
        * Print_EventConfigurationAuto Ausgabe der registrierten Events oder einer definierten Liste (Auswahl) als echo print
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
                echo "      OID    Pfad                                                                     Config aus DetectDeviceHandler (EvaluateHardware)                               Config aus ".get_class($this)."            \n";
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

        public function PrintEvent($group)
            {
            echo "ListEvents for $group:\n";
            $config=$this->ListEvents($group);
            //print_R($config);
            foreach ($config as $ID=>$entry) echo "   ".str_pad($ID,6).str_pad(IPS_GetName($ID).".".IPS_GetName(IPS_GetParent($ID)),60).str_pad(GetValue($ID),20)."$entry\n";
            }

        }

/***********************************************************
 *
 * DetectSensorHandler, einfache Erweiterung ohne involvierung von besonderen DetectMovement Funktionen
 *  Detect_DataID   die Kategorie für die Spiegelregister
 +
 ********************************************************/

	class DetectSensorHandler extends DetectHandler
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
                if ( function_exists('IPSDetectSensorHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectSensorHandler_GetEventConfiguration();
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
		 * getMirrorRegister für Humidity
		 * 
		 */

		public function getMirrorRegister($variableId, $debug = false)
			{
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            return ($mirrorID);
            }

		/**
		 * Das DetectSensorHandler Spiegelregister anlegen
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
		 *
		 */
		function InitGroup($group)
			{
			echo "\nDetect Sensor Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
			$status=false; $status1=false;
			foreach ($config as $oid=>$params)
				{
				$status=$status || GetValue($oid);
				echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
				$moid=$this->getMirrorRegister($oid);
				if ($moid !== false) $status1=$status1 || GetValue($moid);

                // clone profile and type from original variable 
                $variableProfile=IPS_GetVariable($variableId)["VariableProfile"];
                if ($variableProfile=="") $variableProfile=IPS_GetVariable($variableId)["VariableCustomProfile"];
                $variableType=IPS_GetVariable($variableId)["VariableType"];                
				}
			echo "  Gruppe ".$group." hat neuen Status, Wert ".(integer)$status." \n";
			$statusID=CreateVariable("Gesamtauswertung_".$group,$variableType,$this->Detect_DataID,1000, $variableProfile, null,false);
			SetValue($statusID,$status);
			
  			$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      // normaler Wwert 
			IPS_ApplyChanges($archiveHandlerID);
			return ($statusID);			
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
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
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
		function InitGroup($group)
			{
			echo "\nDetect Counter Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
			$status=false; $status1=false;
			foreach ($config as $oid=>$params)
				{
				$status=$status || GetValue($oid);
				echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
				$moid=$this->getMirrorRegister($oid);
				if ($moid !== false) $status1=$status1 || GetValue($moid);

                // clone profile and type from original variable 
                $variableProfile=IPS_GetVariable($variableId)["VariableProfile"];
                if ($variableProfile=="") $variableProfile=IPS_GetVariable($variableId)["VariableCustomProfile"];
                $variableType=IPS_GetVariable($variableId)["VariableType"];                
				}
			echo "  Gruppe ".$group." hat neuen Status, Wert ".(integer)$status." \n";
			$statusID=CreateVariable("Gesamtauswertung_".$group,$variableType,$this->Detect_DataID,1000, $variableProfile, null,false);
			SetValue($statusID,$status);
			
  			$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      // normaler Wwert 
			IPS_ApplyChanges($archiveHandlerID);
			return ($statusID);			
			}  
			
		}

/*****************************************************************************************************************
 *
 * DetectClimateHandler für die Netatmo register
 *
 *
 */

	class DetectClimateHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;				

		protected $Detect_DataID;												/* Speicherort der Mirrorregister, private teilt sich den Speicherort nicht mit der übergeordneten Klasse */ 

		/**
		 * @public
		 *
		 * Initialisierung des DetectClimateHandler Objektes
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
                if ( function_exists('IPSDetectClimateHandler_GetEventConfiguration') ) self::$eventConfigurationAuto = IPSDetectClimateHandler_GetEventConfiguration();
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

		/* DetectClimateHandler::getMirrorRegister 
		 * Achtung, für Climate gibt es einen anderen MirrorRegisterName, Endung CO2 oder BARO
		 */

		public function getMirrorRegister($variableId, $debug = false)
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
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            return ($mirrorID);
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
         * mit ListGroups("Climate") wurden die verschiedenen Gruppen berechnet, der Reihe nach hier InitGroup aufrufen
		 */

		function InitGroup($group, $debug=false)
			{
			if ($debug) echo "\nDetect Climate Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
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
			echo "  Gruppe ".$group." hat neuen Status, Wert ".$status."    $status1\n";
			$statusID=CreateVariable("Gesamtauswertung_".$group,$format[0],$this->Detect_DataID,1000, $format[1], null,false);           // format = [type,profile], wenn vorhanden ist das das format vom vereinheitlichten Spiegelregister
			SetValue($statusID,$status);
			
  			$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      // normaler Wwert 
			IPS_ApplyChanges($archiveHandlerID);
			return ($statusID);			
			}  

		/**
		 *
		 * Die Climate Gesamtauswertung_ Variablen berechnen 
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
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
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

		/**
		 *
		 * Die Humidity Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group)
			{
			echo "\nDetect Feuchtigkeit Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
			$status=false; $status1=false;
			foreach ($config as $oid=>$params)
				{
				$status=$status || GetValue($oid);
				echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
				$moid=$this->getMirrorRegister($oid);
				if ($moid !== false) $status1=$status1 || GetValue($moid);
				}
			echo "  Gruppe ".$group." hat neuen Status, Wert ohne Delay: ".(integer)$status."  mit Delay:  ".(integer)$status1."\n";
			$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->Detect_DataID,1000, '~Humidity', null,false);
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
 *
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
         *      $this->MoveAuswertungID         Kategorie für Spiegelregister, schnell, Standard
         *      $this->Detect_DataID            Kategorie für Spiegelregister, zusätzliches Register geglättet mit Delay
         *
         */

		public function getMirrorRegister($variableId, $debug = false)
			{
            $variablename=$this->getMirrorRegisterName($variableId);
            $result=IPS_GetObject($variableId);            
			if ($variablename == "Cam_Motion")					/* was ist mit den Kameras */
				{
				$variablename=IPS_GetName((integer)$result["ParentID"]);
				}            
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->MoveAuswertungID);
            if ($mirrorID === false) echo "Fehler, $variablename nicht in ".$this->MoveAuswertungID." (".IPS_GetName($this->MoveAuswertungID).") gefunden.\n";
            return ($mirrorID);
            }

		/**
		 * Das DetectMovementHandler Spiegelregister anlegen
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

		/**
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
				$moid=$this->getMirrorRegister($oid,$this->Detect_DataID);
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
            $debug=true;
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
            if ($debug) echo "DetectBrightnessHandler::getMirrorRegister($variableId) aufgerufen.\n";
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, getMirrorRegister for Brightness $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
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
		 *
		 * Setzen der aktuellen Event Konfiguration
		 *
		 */
		function Set_EventConfigurationAuto($configuration)
			{
		   	self::$eventConfigurationAuto = $configuration;
			}

		/** getMirrorRegister für Contact
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
            $variablename=$this->getMirrorRegisterName($variableId);
            if ($debug) echo "getMirrorRegister($variableId  Name Variable ist $variablename.\n";
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, getMirrorRegister for Contact $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
            //else echo "getMirrorRegister for Temperature $variablename\n";
            return ($mirrorID);
            }

		/**
		 * Das DetectContactHandler Spiegelregister anlegen
		 * 
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

		/**
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
				$moid=$this->getMirrorRegister($oid,$this->Detect_DataID);
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
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, getMirrorRegister for Temperature $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
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

		/**
		 *
		 * Die Temperature Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group,$debug=false)
			{
			if ($debug) echo "\nDetect Temperature Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gespeichert.\n";
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
			if ($debug) echo "Gruppe ".$group." hat neuen Status, Wert im Gerät: ".$status."  Wert im Mirror Register:  ".$status1."\n";
            $statusID=@IPS_GetObjectIDByName("Gesamtauswertung_".$group,$this->Detect_DataID);
			if ($statusID===false) 
                {
                $statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->Detect_DataID,1000, '~Temperature', null,false);
                $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
                AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
                IPS_ApplyChanges($archiveHandlerID);
                }
			SetValue($statusID,$status);
            //echo "Gesamtauswertung $statusID auf $status gesetzt.\n";
			return ($statusID);			
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
            if ($debug) echo "DetectHeatSetHandler::getMirrorRegister($variableId) aufgerufen.\n";
            $variablename=$this->getMirrorRegisterName($variableId);
            $mirrorID = @IPS_GetObjectIDByName($variablename,$this->Detect_DataID);
            if ($mirrorID === false) echo "Fehler, getMirrorRegister for HeatSet $variablename nicht in ".$this->Detect_DataID." (".IPS_GetName($this->Detect_DataID).") gefunden.\n";
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

    /* intermediate Topology class
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
     *  mergeTopologyObjects
     *  topologyReferences
     *  uniqueNameReference
     *  evalTopology
     */

    class DetectHandlerTopology extends DetectHandler
		{
		protected $topology;            // topologie ist auch in der Children class verfügbar
        protected $ID,$Config;

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

        /* create_Topology 
         * in webfront als Kategorien entsprechend topology config anlegen, entweder true/false oder ein Array mit Parameter
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

        /* create_TopologyChildrens
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
            $translation=array();
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
         * Filename kommt von Get_ConfigFilename also self::$configFileName für Topology : IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php'
         * File mit Filename wird eingelesen und $getTopology gesucht
         *
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
                /* echo "================================================\n";
                echo "Function get_Topology noch nicht im Config File angelegt. ".$this->Get_Configtype()." nicht gefunden. Neu schreiben.\n";
                $comment=0; $posn=false;	// letzte Klammer finden und danach einsetzen 
                for ($i=0;$i<strlen($fileContent);$i++)
                    {
                    switch ($fileContent[$i])
                        {
                        case '/':
                            if ( ($comment==2) && ($star==1) ) $comment=0;
                            elseif ($comment==1) $comment=3;
                            else $comment=1;
                            $star=0;
                        case '*':
                            if ($comment==1) $comment=2;
                            $star=1;	
                            break;
                        case '}':
                        if ($comment <2 ) $posn=$i;	
                        case chr(10):
                        case chr(13):
                            if ($comment==3) $comment=0;		
                        default:
                            $star=0;
                            break;
                        }		
                    }
                // $posn = strrpos($fileContent, '}');  erkennt auch Klammern innerhalb von Kommentaren   */
            
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
                throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);
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


        /* mergeTopologyObjects
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
         *        optional:  3 ROOM|DEVICE
         *    
         * 42539 : Zentralzimmer Bewegung[0] => Topology, [1] => Zentralzimmer,  [2] => Brightness
         *
         * Die objectsConfig aka channelEventList der Reihe nach durchgehen, wir haben die OID der Register als Index. Format siehe oben
         *      ich brauch einen Raum oder mehrere, aus der Raumangabe auch mit ~ den uniqueName rausbekommen
         *          für den Raum muss es in topology zumindest eine OID geben
         *              es können jetzt auch mehrstufige hierarchische Gewerke aufgebaut werden
         *              zB Weather besteht aus Temperatur und Feuchtigkeit
         *          abhängig von den weiteren Parametern, mehr als drei:
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
         */

        function mergeTopologyObjects($topology, $objectsConfig, $debug=false)
            {
            if ($debug>1) echo "mergeTopologyObjects mit Informationen aus einer DetectDeviceHandler Configuration aufgerufen, in die Topologie einsortieren:\n";
            if (isset($this->installedModules["Stromheizung"])) 
                {                                                
                if ($debug>1)echo "    Stromheizung Modul installiert, Actuator definieren\n"; 
                IPSUtils_Include ("IPSHeat.inc.php",  "IPSLibrary::app::modules::Stromheizung");   
                $ipsheatManager = new IPSHeat_Manager();            // class from Stromheizung
                }
            /* place name kann jetzt redundant sein, index eindeutig aber nicht unbedingt passend zur User Angabe, User nimmt immer den ersten Wert
                * ausser es wird die Baseline mit ~ angegben, name~baseline bedeutet wir suchen einen index dessen Pfad name und baseline enthält
                * reference ist der key der 
                */
            $references = $this->topologyReferences($topology,$debug);              // aus einer Topology eine Reference machen welche uniqueNames einem mehrdeutigen Name zugeordnet sind, unter index Path abspeichern
            $text="";                
            $topologyPlusLinks=$topology;               // Topologie ins Ergebnis Array übernehmen
            foreach ($objectsConfig as $index => $entry)                    // Register der Reihe nach durchgehen, Informationen über den Raum analysieren
                {
                if ($debug>1) 
                    {
                    $newText=$entry[0]; 
                    for ($i=1;$i<count($entry);$i++) $newText.="|".$entry[$i];                 //entry 0 ist immer Topology, gar nicht erst einmal kontrollieren, für alle Elemente machen, können mehr als 3 sein
                    if ($newText != $text) echo "$index   \"$newText\"\n";          // nur die geänderten Zeilen ausgeben
                    $text=$newText;
                    }
                $name=IPS_GetName($index);
                $entry1=explode(",",$entry[1]);		/* Zuordnung Ortsgruppen, es können auch mehrere sein, das ist der Ort zB Arbeitszimmer */
                $entry2=explode(",",$entry[2]);		/* Zuordnung Gewerke, eigentlich sollte pro Objekt nur jeweils ein Gewerk definiert sein. Dieses vorrangig anordnen */

                if ( ($entry[1]!="") && (sizeof($entry1)>0) )           // es wurden ein oder mehrere Räume definiert
                    {
                    foreach ($entry1 as $entryplace)         // alle Räume durchgehen
                        {
                        $place=$this->uniqueNameReference($entryplace,$references);         // für eine Raumangabe Wohzimmer~LBG70 den uniquename Wohnzimmer__1 finden
                        if ( isset($topology[$place]["OID"]) != true ) 
                            {
                            if ($debug) echo "   Fehler, zumindest erst einmal die Kategorie \"$place\" in function get_Topology() von EvaluateHardware_Configuration anlegen.\n";
                            }
                        else        // uniqueName in topology vorhanden
                            {
                            $oid=$topology[$place]["OID"];
                            //print_r($topology[$place]);
                            if (count($entry)>3)
                                {
                                $entry3=strtoupper($entry[3]);
                                if (isset($entry[4])) $newname = $entry[4];
                                else $newname = IPS_GetName($index);
                                echo "mergeTopologyObjects mit zusätzlichen Informationen anlegen : ".str_pad(IPS_GetName($index)."($index)",50)." => ".$entry[0].",".$entry[1].",".$entry[2].",$entry3,$newname .\n";
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
                                            echo "      DEVICE,Actuator given as ".IPS_GetName($index).": ";
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
                                                    $plusLink=$ipsheatManager->checkActuators($subconfigActuator["Name"],$identifier,);
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
                                            echo " \n";
                                            }                       // end ifset
                                        else $plusLink[$index]=$newname;
                                        $topologyPlusLinks[$place]["TOPO"][$entry3][$newname]=$plusLink;          // TOPO -> ROOM -> oid of source -> link name
                                        break;
                                    default:
                                        break;    
                                    }           // end switch entry3
                                }       // end more entries than 3
                            else
                                {
                                $size=sizeof($entry2);              // Gewerk, Type der Register überprüfen
                                if ($entry2[0]=="") $size=0;
                                if ($size == 1)         // es wurde ein Gewerk angeben, zB Temperatur, vorne einsortieren 
                                    {	
                                    if ($debug>1) echo "   erzeuge OBJECT Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid).") ".$entry[2]."\n";
                                    //CreateLinkByDestination($name, $index, $oid, 1000);	
                                    //$topologyPlusLinks[$place]["OBJECT"][$entry2[0]][$index]=$name;       // nach OBJECT auch das Gewerk als Identifier nehmen
                                    $plusLink=array();
                                    if (isset($topology[$place]["Actuators"][IPS_GetName($index)]))
                                        {
                                        echo "      DEVICE,Actuator given as ".IPS_GetName($index).": ";
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
                                                $plusLink=$ipsheatManager->checkActuators($subconfigActuator["Name"],$identifier,true);
                                                }
                                            }   	    // ende foreach
                                        echo " \n";
                                        }
                                    else $plusLink[$index]=$name;
                                    $topologyPlusLinks[$place]["OBJECT"][$entry2[0]]=$plusLink;          // OBJECT -> TYPE -> oid of source -> link name
                                    }
                                elseif ($size == 2)         // eine zusätzliche Hierarchie einführen, der zweite Wert ist die Übergruppe 
                                    {
                                    if ($debug>1) echo "   erzeuge OBJECT Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid).") ".$entry[2]."\n";
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
            return ($topologyPlusLinks);
            }

        /* aus einer Topology eine Reference machen welche uniqueNames einem mehrdeutigen Name zugeordnet sind, unter index Path abspeichern
         * Wohnzimmer ist Wohnzimmer.LBG70 und Wohnzimmer.BKS01
         * auch in findRoom benutzt.
         */
        public function topologyReferences($topology,$debug=false)
            {
            $references=array();
            foreach ($topology as $topindex => $topentry)
                {
                if (!( (isset($topentry["Name"])) && (isset($topentry["Path"])) )) 
                    {
                    if ($debug>1) 
                        {
                        echo "Warning, incomplete array on $topindex:\n";
                        print_R($topentry);          // Eintrag fehlt
                        }
                    }
                else $references[$topentry["Name"]][$topentry["Path"]] = $topindex;               // für einen namen alle Einträge durchgehen, der wo die bvaseline im key ist den index übernehmen
                }
            return($references);
            }

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
                                        $entryplace=$this->uniqueNameReference($room,$references);
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

        /* Raumangabe kann eine Tilde enthalten, entsprechend auflösen
         * References wird aus topology erzeugt
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
                            }
                        else $place=$entryplace;

            return ($place);
            }

		/**
		 *
		 * Topologie als Tree darstellen
		 *
		 */
	    public function evalTopology($lookfor,$hierarchy=0)
		    {
    		if (is_array($lookfor)==true ) 
	    		{
		    	//echo "Ist Array. ".$hierarchy."\n";
			    foreach ($lookfor as $item)
				    {
    				//for ($i=0;$i<$hierarchy;$i++) echo "   ";
	    			//echo $item."\n";
		    		$this->evalTopology($item,$hierarchy);
			    	}
    			//print_r($lookfor);
	    		}
		    else 
			    {
    			$goal=$lookfor;	
	    		for ($i=0;$i<$hierarchy;$i++) echo "   ";
		    	echo $goal."\n";
			    foreach ($this->topology as $index => $entry)
				    {
    				if ($index == $goal) 
	    				{
		    			if (isset($entry["Children"]) == true )
			    			{
				    		if ( sizeof($entry["Children"]) > 0 )
    							{
	    						//print_r($entry["Children"]);
		    					$hierarchy++;
			    				$this->evalTopology($entry["Children"],$hierarchy);
				    			}
					    	}	
    					}
	    			}
		    	}
    		}

        }

	/*******************************************************************************
	 *
	 * Class Definitionen DetectDeviceHandler 
     *
     * erzeugt die Config Liste IPSDetectDeviceHandler_GetEventConfiguration in scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php
     * das ist die config Liste die die Topology und Gewerke zu Instanzen und Registern herstellt, etwas mühsam zum Eingeben, da es oft mehrere Instanzen in einem Gerät gibt
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
            $doInstances=false; $doLinkFromParent=false;
            if (is_array($config))
                {
                if (isset($config["Show"]["Instances"])) $doInstances=$config["Show"]["Instances"];
                if (isset($config["Show"]["LinkFromParent"])) $doLinkFromParent=$config["Show"]["LinkFromParent"];
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
                                                    echo "              ".str_pad("$oid/$name",55).str_pad(GetvalueIfFormatted($oid),20)."last Update ".date("d.m.y H:i:s",$objects["VariableUpdated"]);
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
                                        echo "             ".str_pad("$oid/$name",55).str_pad(GetvalueIfFormatted($oid),20)."last Update ".date("d.m.y H:i:s",$objects["VariableUpdated"]);
                                        if ((time()-$objects["VariableUpdated"])>(60*60*24)) echo "   ****** too long time, check !!";
                                        echo "\n";
                                        $linkId=CreateLinkByDestination($name, $oid, $topologyinstance, 1000);
                                        IPS_SetHidden($linkId,false);	                
                                        }                    
                                    }
                                if ($doLinkFromParent)
                                    {
                                    $parent = $entry["Parent"];
                                    if (isset($topologyPlusLinks[$parent]["OID"]))
                                        {
                                        $parentId = $topologyPlusLinks[$parent]["OID"];
                                        echo "            Link at Parent, Look for $parent in Kategorie $parentId, Name ".$entry["Name"]." \n";
                                        $linkId=CreateLinkByDestination($entry["Name"], $topologyinstance, $parentId, 10);
                                        IPS_SetHidden($linkId,false);
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
                                echo "        ".str_pad("$oid/$name",55).str_pad(GetvalueIfFormatted($oid),20)."last Update ".date("d.m.y H:i:s",$objects["VariableUpdated"]);
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
	 ****************************************************************************************/

	class DetectDeviceListHandler extends DetectHandlerTopology
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		private static $configFileName;		

        protected $topology;

		/**
		 * @public
		 *
		 * Initialisierung des DetectDeviceListHandler Objektes
		 *
		 */
		public function __construct()
			{
			self::$configtype = '$deviceListTopology';
			self::$configFileName = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/EvaluateHardware/EvaluateHardware_Configuration.inc.php';
            if ($this->create_Topology()===false)           // berechnet topology und ID, false wenn get_topology nicht definiert, input parameter false no init/empty category false no debug
				{
                $this->create_TopologyConfigurationFile(true);          //true for Debug 
                /*  schon in obiger Routine enthalten, doppelt                    
				echo "DetectDeviceListHandler: Function get_Topology neu anlegen.\n";
				$fileNameFull = $this->Get_ConfigFileName();
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
					echo "================================================\n";
					echo "Function get_Topology noch nicht im Config File angelegt. ".$this->Get_Configtype()." nicht gefunden. Neu schreiben.\n";
					$comment=0; $posn=false;	// letzte Klammer finden und danach einsetzen 
					for ($i=0;$i<strlen($fileContent);$i++)
						{
						switch ($fileContent[$i])
							{
							// comment : 0 ok 1 erkannt 2 erkannt 3 erkannt 
							case '/':
								if ( ($comment==2) && ($star==1) ) $comment=0;
								elseif ($comment==1) $comment=3;
								else $comment=1;
								$star=0;
							case '*':
								if ($comment==1) $comment=2;
								$star=1;	
								break;
							case '}':
							if ($comment <2 ) $posn=$i;	
							case chr(10):
							case chr(13):
								if ($comment==3) $comment=0;		
							default:
								$star=0;
								break;
							}		
						}
					// $posn = strrpos($fileContent, '}');  erkennt auch Klammern innerhalb von Kommentaren
				
					if ( $posn === false )
						{
						if (strlen($fileContent) > 6 )
							{
							$posn = strrpos($fileContent, '?>')-1;
							echo "Weit und breit keine Klammer. Auf Pos $posn function einfügen.\n";
							$configString="\n	 function get_Topology() {\n        ".'$getTopology'." = array(\n".'			"World" 			=>	array("Name" => "World","Parent"	=> "World"),'."\n         );\n       return ".'$getTopology'.";\n	}\n\n";
							$fileContentNew = substr($fileContent, 0, $posn+1).$configString.substr($fileContent, $posn+1);
							file_put_contents($fileNameFull, $fileContentNew);							
							}
						else throw new IPSMessageHandlerException('EventConfiguration File maybe empty !!!', E_USER_ERROR);
						}
					
					}
				$this->topology=array();
				$this->topology["World"]["OID"]=$ID;	*/			
				}
	        parent::__construct();
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

		/**
		 *
		 * Topologie als Tree darstellen, in parent class
		 *
		 
	    public function evalTopology($lookfor,$hierarchy=0)
		    {
    		if (is_array($lookfor)==true ) 
	    		{
		    	//echo "Ist Array. ".$hierarchy."\n";
			    foreach ($lookfor as $item)
				    {
    				//for ($i=0;$i<$hierarchy;$i++) echo "   ";
	    			//echo $item."\n";
		    		$this->evalTopology($item,$hierarchy);
			    	}
    			//print_r($lookfor);
	    		}
		    else 
			    {
    			$goal=$lookfor;	
	    		for ($i=0;$i<$hierarchy;$i++) echo "   ";
		    	echo $goal."\n";
			    foreach ($this->topology as $index => $entry)
				    {
    				if ($index == $goal) 
	    				{
		    			if (isset($entry["Children"]) == true )
			    			{
				    		if ( sizeof($entry["Children"]) > 0 )
    							{
	    						//print_r($entry["Children"]);
		    					$hierarchy++;
			    				$this->evalTopology($entry["Children"],$hierarchy);
				    			}
					    	}	
    					}
	    			}
		    	}
    		} */

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

class TestMovement
	{
	
	private $debug;
	public $eventlist;
	public $eventlistDelete;
	
	public $motionDevice, $switchDevice;	/* erkannte Homematic Geräte */

	
	/**********************************
	 *
	 * der Reihe nach die Events die unter dem Handler haengen durchgehen und plausibilisieren 
	 *
	 * dabei die Erfassung, Speicherung, Bearbeitung von der Visualisiserung trennen
	 *
	 *******************************/
	
	public function __construct($debug=false) 
		{	
		$this->debug=$debug;
		if ($debug) echo "TestMovement Construct, Debug Mode, zusätzliche Checks bei der Eventbearbeitung:\n";
        $this->syncEventList($debug);       // speichert eventList und eventListDelete
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

		$this->motionDevice=$this->findMotionDetection();							
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

		$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));

		if ($debug) echo " EventID  ".str_pad("Name",75)." Type       Parent Instanz   \n";          // Ueberschrift tabelle

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
				$eventlist[$i]["EventID"]=$eventID;
				if (IPS_ObjectExists($eventID)==false)
					{ /* Objekt für das Event existiert nicht */
					$delete++;
					if ($debug) echo "*** ".$eventID." existiert nicht.\n";
					$eventlist[$i]["Fehler"]='does not exists any longer. Event has to be deleted ***********.';
					$this->eventlistDelete[$eventID_str]["Fehler"]=1;
					$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;
					if (isset($eventlistConfig[$eventID_str])) if ($debug) echo "**** und Event ".$eventID_str." auch aus der Config Datei \"IPSMessageHandler_GetEventConfiguration\" loeschen: ".$eventlistConfig[$eventID_str][1].$eventlistConfig[$eventID_str][2]."\n";
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
					if (isset($movement_config[$eventID_str]))
						{	/* kommt in der Detect Movement Config vor */
  						$eventlist[$i]["Typ"]="Movement";
						}
					elseif (isset($temperature_config[$eventID_str]))
						{	/* kommt in der Detect Temperature Config vor */
						$eventlist[$i]["Typ"]="Temperatur";							
						}	
					elseif (isset($humidity_config[$eventID_str]))
						{	/* kommt in der Detect Humidity Config vor */
						$eventlist[$i]["Typ"]="Humidity";
						}	
					elseif (isset($heatcontrol_config[$eventID_str]))
						{	/* kommt in der Detect Heatcontrol Config vor */
						$eventlist[$i]["Typ"]="HeatControl";
						}	
					else
						{	/* kommt in keiner Detect Config vor */
						$eventlist[$i]["Typ"]="";
						}	

					if (isset($eventlistConfig[$eventID_str]))
						{
						$eventlist[$i]["Config"]=$eventlistConfig[$eventID_str][1];
						//print_r($eventlistConfig[$eventID_str]);
						}
					elseif (isset($eventlistConfigCust[$eventID_str]))
						{
                        if ($debug) echo "Custom";
						$eventlist[$i]["Config"]=$eventlistConfigCust[$eventID_str][1];
						//print_r($eventlistConfig[$eventID_str]);
						}
					else {
						if ($debug) echo "  --> Objekt : ".$eventID." Konfiguration nicht vorhanden.";
    					$delete++;
						$eventlist[$i]["Config"]='Error no Configuration available **************.';
						$this->eventlistDelete[$eventID_str]["Fehler"]=2;
						$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;						
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
			$i++;
			IPS_SetPosition($childrenID,$eventID);				
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

	/**********************************
	 * TestMovement, getEventListforDeletion Werte ausgeben die beim construct bereits erstellt wurden
	 * es gibt Events in der IPS_MessageHandler Konfigurationsdatei/function denen keine eigene Konfiguration zugeordnet ist
	 * berücksichtigt sowohl Configuration als auch ConfigurationCust
	 *
	 *******************************/
	
	public function getEventListforDeletion() 
		{	
        return ($this->eventlistDelete);
        }

	/**********************************
	 * TestMovement, erst einmal alle Events von IP Symcon auslesen und in einem neuen Format ausgeben
     * Es gibt filter als Parameter, das ist der Name des Parents des Events
	 *
	 *******************************/

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

    /* Ausgabe als html Tabelle, ermittelt löschbare Items und löscht sie auch gleich
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
        if ($filter == "IPSMessageHandler_Event")
            {
		    $html.="<tr><th>#</th><th>OID</th><th>Name</th><th>ObjektID</th><th>Objektpfad/Fehler</th><th>Component</th><th>Module</th><th>LastRun</th><th>Pfad</th><th>Type</th><th>Script</th></tr>";
            }
        else
            {
		    $html.="<tr><th>#</th><th>OID</th><th>Name</th><th>Funktion</th><th>Konfiguration</th><th>Homematic</th><th>Detect Movement</th><th>Autosteuerung</th></tr>";
            }
        $result=array();
        if ($htmlOutput) $echo=false;
        else $echo=true;

        if ($echo) echo str_pad(" #",3)." ".str_pad("OID",6).str_pad("Name",40)."\n";
        $delete=array();
        foreach ($resultEventList as $index => $entry)
            {
            if ($echo) echo str_pad($index,3)." ".str_pad($entry["OID"],6);
			$html.="<tr><td>".$index."</td><td>".$entry["OID"]."</td>";
            $result[$index]["OID"]=$entry["OID"];
            if ($filter == "IPSMessageHandler_Event")
                {
                $trigger=$entry["TriggerVariableID"];
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
                }
            else        // filter nicht bekannt
                {
                if ($echo) echo str_pad($entry["Name"],40)." ";
                $html.="<td>".$entry["Name"]."</td>";
                }
            if ($entry["LastRun"]==0) 
                {
                if ($echo) echo str_pad("nie",20);
                $html.="<td>nie</td>";
                }
            else 
                {
                $timePassed=time()-$entry["LastRun"];
                if ($echo) echo str_pad("vor ".nf($timePassed,"s"),20);
                $html.="<td>vor ".nf($timePassed,"s")."</td>";
                //echo str_pad(date("Y.m.d H:i:s",$entry["LastRun"]),20);
                }
            if ($echo) echo "  ".str_pad($entry["Pfad"],80)."  ".str_pad($entry["Type"],14)."   ".str_pad($entry["Script"],44)."   "."\n";;
            $html.="<td>".$entry["Pfad"]."</td><td>".$entry["Type"]."</td><td>".$entry["Script"]."</td></tr>";
            }

        /* eventuell veraltete unbenutzte Events loeschen */
        if (sizeof($delete)>0)
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

    /*******************************
     *
     * Geräte mit Bewegungserkennung finden
     * nutzt dazu die in EvaluateHardware_Include Datei gespeicherten HomematicList und FS20List
     *
     **************************************************************/

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