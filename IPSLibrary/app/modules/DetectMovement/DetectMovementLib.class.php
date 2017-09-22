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

		/**
		 * @public
		 *
		 * Initialisierung des DetectHumidityHandler Objektes
		 *
		 */
		public function __construct()
			{
        	self::$configtype = '$eventMoveConfiguration';                                          /* <-------- change here */
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


		}

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






	/** @}*/
?>