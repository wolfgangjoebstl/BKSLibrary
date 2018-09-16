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

    /*********************
     *
     * Detect Movement Library
     *
     * Folgende Klassen stehen zur Verfügung:
     *
     * abstract class DetectHandler
     * DetectHumidityHandler extends DetectHandler
     * DetectMovementHandler extends DetectHandler
     * DetectTemperatureHandler extends DetectHandler
     * DetectHeatControlHandler extends DetectHandler
     *
     * TestMovement, ausarbeiten einer aussagekraeftigen Tabelle basierend auf den Event des MessageHandlers
     *
     *
     **********************************************/

   IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');


	abstract class DetectHandler {

		abstract function Get_Configtype();
		abstract function Get_EventConfigurationAuto();
		abstract function Set_EventConfigurationAuto($configuration);

		/**
		 * @public
		 *
		 * Initialisierung des IPSLight_Manager Objektes
		 *
		 */
		public function __construct()
			{
			}

		/**
		 * @private
		 *
		 * Speichert die aktuelle Event Konfiguration
		 *
		 * @param string[] $configuration Konfigurations Array
		 */
		function StoreEventConfiguration($configuration)
			{

			// Build Configuration String
			$configString = $this->Get_Configtype().' = array(';
			foreach ($configuration as $variableId=>$params) {
				$configString .= PHP_EOL.chr(9).chr(9).chr(9).$variableId.' => array(';
				for ($i=0; $i<count($params); $i=$i+3) {
					if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';
					$configString .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
				}
				$configString .= '),';
				$configString .= '   /*'.IPS_GetName($variableId)."  ".IPS_GetName(IPS_GetParent($variableId)).'*/';
			}
			$configString .= PHP_EOL.chr(9).chr(9).chr(9).');'.PHP_EOL.PHP_EOL.chr(9).chr(9);

			// Write to File
			$fileNameFull = IPS_GetKernelDir().'scripts/IPSLibrary/config/modules/DetectMovement/DetectMovement_Configuration.inc.php';
			if (!file_exists($fileNameFull)) {
				throw new IPSMessageHandlerException($fileNameFull.' could NOT be found!', E_USER_ERROR);
			}
			$fileContent = file_get_contents($fileNameFull, true);
			$pos1 = strpos($fileContent, $this->Get_Configtype().' = array(');
			$pos2 = strpos($fileContent, 'return '.$this->Get_Configtype().';');

			if ($pos1 === false or $pos2 === false) 
				{
				echo "================================================\n";
				echo "Function for heat control not inserted in config file. Insert now.\n";
				$comment=0; $posn=false;	/* letzte Klammer finden und danach einsetzen */
				for ($i=0;$i<strlen($fileContent);$i++)
					{
					switch ($fileContent[$i])
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


		/**
		 * @public
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

		/**
		 * @public
		 *
		 * Listet anhand der Konfiguration alle Events
		 *
		 */
		public function ListEvents($type="")
			{
			$configuration = $this->Get_EventConfigurationAuto();
			$result=array();
			foreach ($configuration as $variableId=>$params)
				{
				switch ($type)
					{
					case 'Motion':
					case 'Contact':
						if ($type==$params[0])
						   {
							$result[$variableId]=$params[1];
							}
					   break;
					default:
					   if ($type!="")
							{
							if ($type==$params[1])
							   {
								$result[$variableId]=$params[1];
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

		/**
		 * @public
		 *
		 * Listet anhand der Konfiguration alle Events
		 *
		 */
		public function ListGroups($type="")
			{
			$configuration = $this->Get_EventConfigurationAuto();
			$result=array();
			foreach ($configuration as $variableId=>$params)
				{
				switch ($type)
					{
				   	case 'Motion':
					case 'Contact':
						if (($type==$params[0]) && ($params[1] != ""))
						   {
							$result[$params[1]]="available";
							}
					   break;
					default:
					   if ($params[1] != "")
					   		{
							$result[$params[1]]="available";
							}
					   break;
					}
				}
			return ($result);
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
			
			/* Funktion nicht mehr klar, wird von Create Events aufgerufen. Hier erfolgt nur ein check ob die Parametzer richtig benannt worden sind */
			
			switch ($eventType)
				{
				case 'HeatControl':
					$triggerType = 3;
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
			IPSLogger_Dbg (__file__, 'Created '.$this->Get_Configtype().' Handler Event for Variable='.$variableId);
			}



		/**
		 * @public
		 *
		 * Registriert ein Event im IPSMessageHandler. Die Funktion legt ein ensprechendes Event
		 * für die übergebene Variable an und registriert die dazugehörigen Parameter im MessageHandler
		 * Konfigurations File.
		 *
		 * @param integer $variableId ID der auslösenden Variable
		 * @param string $eventType Type des Events (OnUpdate oder OnChange)
		 * @param string $componentParams Parameter für verlinkte Hardware Komponente (Klasse+Parameter)
		 * @param string $moduleParams Parameter für verlinktes Module (Klasse+Parameter)
		 */
		public function RegisterEvent($variableId, $eventType, $componentParams, $moduleParams)
			{
			$configurationAuto = $this->Get_EventConfigurationAuto();
			//print_r($configurationAuto);
			//echo "Register Event with VariableID:".$variableId."\n";
			// Search Configuration
			$found = false;
				if (array_key_exists($variableId, $configurationAuto))
					{
					//echo "Eintrag in Datenbank besteht.\n";
					//echo "Search Config : ".$variableId." with Event Type : ".$eventType." Component ".$componentParams." Module ".$moduleParams."\n";
					$moduleParamsNew = explode(',', $moduleParams);
					//print_r($moduleParamsNew);
					$moduleClassNew  = $moduleParamsNew[0];

					$params = $configurationAuto[$variableId];
					//print_r($params);
					for ($i=0; $i<count($params); $i=$i+3)
						{
						$moduleParamsCfg = $params[$i+2];
						$moduleParamsCfg = explode(',', $moduleParamsCfg);
						$moduleClassCfg  = $moduleParamsCfg[0];
						// Found Variable and Module --> Update Configuration
						//echo "ModulclassCfg : ".$moduleClassCfg." New ".$moduleClassNew."\n";
						/* Wenn die Modulklasse gleich ist werden die Werte upgedatet */
						/*if ($moduleClassCfg=$moduleClassNew)
							{
							$found = true;
							$configurationAuto[$variableId][$i]   = $eventType;
							$configurationAuto[$variableId][$i+1] = $componentParams;
							$configurationAuto[$variableId][$i+2] = $moduleParams;
							} */
						$found = true;
						$configurationAuto[$variableId][$i]   = $eventType;
						if ($componentParams != "") {	$configurationAuto[$variableId][$i+1] = $componentParams; }
						if ($moduleParams != "") {	$configurationAuto[$variableId][$i+2] = $moduleParams; }
						}
					}

			// Variable NOT found --> Create Configuration
			if (!$found)
					{
				   //echo "Create Event."."\n";
					$configurationAuto[$variableId][] = $eventType;
					$configurationAuto[$variableId][] = $componentParams;
					$configurationAuto[$variableId][] = $moduleParams;
					}

				$this->StoreEventConfiguration($configurationAuto);
				$this->CreateEvent($variableId, $eventType);

		}

	}

/******************************************************************************************************************/

	class DetectHumidityHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;

		/**
		 * @public
		 *
		 * Initialisierung des DetectHumidityHandler Objektes
		 *
		 */
		public function __construct()
			{
			self::$configtype = '$eventHumidityConfiguration';
			parent::__construct();
			}


		function Get_Configtype()
			{
			return self::$configtype;
			}

		/* obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
				self::$eventConfigurationAuto = IPSDetectHumidityHandler_GetEventConfiguration();
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


		}

/******************************************************************************************************************/

	class DetectMovementHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;
		
		private $MoveAuswertungID;
		private $motionDetect_DataID;		

		/**
		 * @public
		 *
		 * Initialisierung des DetectHumidityHandler Objektes
		 *
		 */
		public function __construct($MoveAuswertungID=false)
			{
			self::$configtype = '$eventMoveConfiguration';                                          /* <-------- change here */
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
			$CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
			$name="Motion-Detect";
			$mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($mdID==false)
				{
				$mdID = IPS_CreateCategory();
				IPS_SetParent($mdID, $CategoryIdData);
				IPS_SetName($mdID, $name);
	 			IPS_SetInfo($mdID, "this category was created by script. ");
				}			
			$this->motionDetect_DataID=$mdID;			
			parent::__construct();
			}


		function Get_Configtype()
			{
			return self::$configtype;
			}

		/* obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
				self::$eventConfigurationAuto = IPSDetectMovementHandler_GetEventConfiguration();       /* <-------- change here */
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
		 * @public
		 *
		 */
		public function getMirrorRegister($oid)
			{
			//echo "Mirror Register von Hardware Register ".$oid." suchen.\n";
			//echo "Kategorie der Custom Components Spiegelregister : ".$this->MoveAuswertungID." (".IPS_GetName($this->MoveAuswertungID).")\n";
			//echo "Kategorie der Detect Movement   Spiegelregister : ".$this->motionDetect_DataID." (".IPS_GetName($this->motionDetect_DataID).")\n";
			$result=IPS_GetObject($oid);
			$resultParent=IPS_GetObject((integer)$result["ParentID"]);
			if ($resultParent["ObjectType"]==1)     // Abhängig vom Typ (1 ist Instanz) entweder Parent (typischerweise Homematic) oder gleich die Variable für den Namen nehmen
				{
				$variablename=IPS_GetName((integer)$result["ParentID"]);		/* Hardware Komponente */
				}
			elseif (IPS_GetName($oid)=="Cam_Motion")					/* was ist mit den Kameras */
				{
				$variablename=IPS_GetName((integer)$result["ParentID"]);
				}
			else
				{
				$variablename=IPS_GetName($oid);
				}
			//$mirrorID=@IPS_GetObjectIDByName($variablename,$this->MoveAuswertungID);		/* das sind die schnellen Register, ohne Delay */
			$mirrorID=@IPS_GetObjectIDByName($variablename,$this->motionDetect_DataID);		/* das sind die geglätteten Register mit Delay */

			if ($mirrorID===false) $mirrorID=$oid;
			echo "    Spiegelregister für ".$variablename." ist in ".$mirrorID."  (".IPS_GetName($mirrorID)."/".IPS_GetName(IPS_GetParent($mirrorID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($mirrorID))).") \n";
			return($mirrorID);
			}
			

		/**
		 *
		 * Die Gesamtauswertung_ Variablen erstellen 
		 *
		 */
		function InitGroup($group)
			{
			echo "\nDetectMovement Gruppe ".$group." behandeln. Ergebnisse werden in ".$this->MoveAuswertungID." (".IPS_GetName($this->MoveAuswertungID).") und in ".$this->motionDetect_DataID." (".IPS_GetName($this->motionDetect_DataID).") gespeichert.\n";
			$config=$this->ListEvents($group);
			$status=false; $status1=false;
			foreach ($config as $oid=>$params)
				{
				$status=$status || GetValue($oid);
				echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
				$moid=$this->getMirrorRegister($oid);
				$status1=$status1 || GetValue($moid);
				}
			echo "  Gruppe ".$group." hat neuen Status, Wert ohne Delay: ".(integer)$status."  mit Delay:  ".(integer)$status1."\n";
			$statusID=CreateVariable("Gesamtauswertung_".$group,0,$this->MoveAuswertungID,1000, '~Motion', null,false);
			SetValue($statusID,$status1);
			$status1ID=CreateVariable("Gesamtauswertung_".$group,0,$this->motionDetect_DataID,1000, '~Motion', null,false);
			SetValue($status1ID,$status);
			
  			$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
     		AC_SetLoggingStatus($archiveHandlerID,$statusID,true);
			AC_SetAggregationType($archiveHandlerID,$statusID,0);      /* normaler Wwert */
     		AC_SetLoggingStatus($archiveHandlerID,$status1ID,true);
			AC_SetAggregationType($archiveHandlerID,$status1ID,0);      /* normaler Wwert */
			IPS_ApplyChanges($archiveHandlerID);
			return ($statusID);			
			}
			
		/**
		 *
		 * private Variablen ausgeben 
		 *
		 */
		function getCustomComponentsDataGroup()
			{
			return($this->MoveAuswertungID);
			}			

		function getDetectMovementDataGroup()
			{
			return($this->motionDetect_DataID);
			}			
			
			
		} /* ende class */	

/******************************************************************************************************************/

	class DetectTemperatureHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;

		/**
		 * @public
		 *
		 * Initialisierung des DetectHumidityHandler Objektes
		 *
		 */
		public function __construct()
			{
			self::$configtype = '$eventTempConfiguration';                                          /* <-------- change here */
			parent::__construct();			
			}


		function Get_Configtype()
			{
			return self::$configtype;
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


		}
		


/******************************************************************************************************************/

	class DetectHeatControlHandler extends DetectHandler
		{

		private static $eventConfigurationAuto = array();         /* diese Variable sollte Static sein, damit sie für alle Instanzen gleich ist */
		private static $configtype;

		/**
		 * @public
		 *
		 * Initialisierung des DetectHumidityHandler Objektes
		 *
		 */
		public function __construct()
			{
			self::$configtype = '$eventHeatConfiguration';                                          /* <-------- change here */
			parent::__construct();			
			}


		function Get_Configtype()
			{
			return self::$configtype;
			}

		/* obige variable in dieser Class kapseln, dannn ist sie static für diese Class */

		function Get_EventConfigurationAuto()
			{
			if (self::$eventConfigurationAuto == null)
				{
				if ( function_exists('IPSDetectHeatControlHandler_GetEventConfiguration') )
					{
					self::$eventConfigurationAuto = IPSDetectHeatControlHandler_GetEventConfiguration();       /* <-------- change here */
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
		}

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
 * writeEventListTable
 * findMotionDetection
 * sortEventList
 *
 **************************************/

class TestMovement
	{
	
	private $debug;
	public $eventlist;
	public $eventlistDelete;

	
	/**********************************
	 *
	 * der Reihe nach die Events die unter dem Handler haengen durchgehen und plausibilisieren 
	 *
	 * dabei die Erfassung, Speicherung, Bearbeitung von der Visualisiserung trennen
	 *
	 *******************************/
	
	public function __construct($debug) 
		{	
		$this->debug=$debug;
		if ($debug) echo "TestMovement Construct, zusätzliche Checks bei der Eventbearbeitung:\n";

		/* Autosteuerung */
		IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
		$autosteuerung_config=Autosteuerung_GetEventConfiguration();
	
		/* IPSComponent mit CustomComponent */ 	
 		$eventlistConfig = IPSMessageHandler_GetEventConfiguration();

		$motionDevice=$this->findMotionDetection();							
		$switchDevice=$this->findSwitches();							

        if ($debug) echo "Detect Movement Configurationen auslesen:\n";
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
            if ($debug) echo "    Movement Configuration auslesen.\n";    
            $temperature_config=IPSDetectTemperatureHandler_GetEventConfiguration();
            }
		else $temperature_config=array();
		if (function_exists('IPSDetectHumidityHandler_GetEventConfiguration'))		
            {
            if ($debug) echo "    Temperature Configuration auslesen.\n";    
            $humidity_config=IPSDetectHumidityHandler_GetEventConfiguration();
            }
		else $humidity_config=array();
		if (function_exists('IPSDetectHeatControlHandler_GetEventConfiguration'))	
            {
            if ($debug) echo "    HeatControl Configuration auslesen.\n";    
            $heatcontrol_config=IPSDetectHeatControlHandler_GetEventConfiguration();
            }
		else $heatcontrol_config=array();

		$delete=0;			// mitzaehlen wieviele events geloescht werden muessen 

		$i=0;
		$eventlist=array();
		$this->eventlistDelete=array();		// Sammlung der Events für die es kein Objekt mehr dazu gibt
		$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
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
					if (isset($eventlistConfig[$eventID_str])) echo "**** und Event ".$eventID_str." auch aus der Config Datei loeschen.: ".$eventlistConfig[$eventID_str][1].$eventlistConfig[$eventID_str][2]."\n";
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
						if ($debug) echo $instanz."\n";
						}
					else 	
						{
						echo "Fehler, Objekt ist vom Typ keine Variable.   ";
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
					else
						{
						if ($debug) echo "Objekt : ".$eventID." Konfiguration nicht vorhanden.\n";
						$eventlist[$i]["Config"]='Error no Configuration available **************.';
						$this->eventlistDelete[$eventID_str]["Fehler"]=2;
						$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;						
						}
						
					if (isset($motionDevice[$eventID])==true)
						{ 
						$eventlist[$i]["Homematic"]="Motion";
						$motionDevice[$eventID]=false;
						}
                    elseif (isset($switchDevice[$eventID])==true)
                        {
						$eventlist[$i]["Homematic"]="Switch";
						$switchDevice[$eventID]=false;
                        }     
					else $eventlist[$i]["Homematic"]="";	
					
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
		if ($delete>0) echo "****Es muessen insgesamt ".$delete." Events geloescht werden, das Objekt auf das sie verweisen gibt es nicht mehr.\n";
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
     *
     **************************************************************/

	public function findMotionDetection()
		{
		//$alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
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