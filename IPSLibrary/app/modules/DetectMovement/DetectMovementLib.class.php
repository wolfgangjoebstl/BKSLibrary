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

	class DetectMovementHandler {

		private static $eventConfigurationAuto = array();

		/**
		 * @public
		 *
		 * Initialisierung des IPSLight_Manager Objektes
		 *
		 */
		public function __construct() {

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
				self::$eventConfigurationAuto = IPSDetectMovementHandler_GetEventConfiguration();
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
		 * Erzeugt anhand der Konfiguration alle Events
		 *
		 */
		public static function CreateEvents()
			{
			$configuration = self::Get_EventConfigurationAuto();

			foreach ($configuration as $variableId=>$params)
				{
				self::CreateEvent($variableId, $params[0]);
				}
			}

		/**
		 * @public
		 *
		 * Listet anhand der Konfiguration alle Events
		 *
		 */
		public static function PrintEvents($type="")
			{
			$configuration = self::Get_EventConfigurationAuto();
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
			foreach ($result as $variableID => $type)
			   {
			   //echo "Variable ID : ".$variableID." Typ : ".$type."  ".IPS_GetName($variableID)."  ".IPS_GetName(IPS_GetParent($variableID))."\n";
			   }
			}


		/**
		 * @public
		 *
		 * Listet anhand der Konfiguration alle Events
		 *
		 */
		public static function ListEvents($type="")
			{
			$configuration = self::Get_EventConfigurationAuto();
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
		public static function ListGroups($type="")
			{
			$configuration = self::Get_EventConfigurationAuto();
			$result=array();
			foreach ($configuration as $variableId=>$params)
				{
				switch ($type)
					{
				   case 'Motion':
					case 'Contact':
						if ($type==$params[0])
						   {
							$result[$params[1]]="available";
							}
					   break;
					default:
						$result[$params[1]]="available";
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
		public static function CreateEvent($variableId, $eventType)
			{
			switch ($eventType)
				{
				case 'Motion':
					$triggerType = 1;
					break;
				case 'Contact':
					$triggerType = 0;
					break;
				case 'par0':
				   break;
				default:
					throw new IPSMessageHandlerException('Found unknown EventType '.$eventType);
				}
			IPSLogger_Dbg (__file__, 'Created IPSDetectMovementHandler Event for Variable='.$variableId);
			}


		/**
		 * @private
		 *
		 * Speichert die aktuelle Event Konfiguration
		 *
		 * @param string[] $configuration Konfigurations Array
		 */
		private static function StoreEventConfiguration($configuration) {

			// Build Configuration String
			$configString = '$eventMoveConfiguration = array(';
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
			$pos1 = strpos($fileContent, '$eventMoveConfiguration = array(');
			$pos2 = strpos($fileContent, 'return $eventMoveConfiguration;');

			if ($pos1 === false or $pos2 === false) {
				throw new IPSMessageHandlerException('EventConfiguration could NOT be found !!!', E_USER_ERROR);
			}
			$fileContentNew = substr($fileContent, 0, $pos1).$configString.substr($fileContent, $pos2);
			file_put_contents($fileNameFull, $fileContentNew);
			self::Set_EventConfigurationAuto($configuration);
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
		public static function RegisterEvent($variableId, $eventType, $componentParams, $moduleParams)
			{
			$configurationAuto = self::Get_EventConfigurationAuto();
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

				self::StoreEventConfiguration($configurationAuto);
				self::CreateEvent($variableId, $eventType);

		}



	}

/******************************************************************************************************************/

	class DetectTemperatureHandler {

		private static $eventConfigurationAuto = array();

		/**
		 * @public
		 *
		 * Initialisierung des IPSLight_Manager Objektes
		 *
		 */
		public function __construct() {

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
				self::$eventConfigurationAuto = IPSDetectTemperatureHandler_GetEventConfiguration();
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
		 * Erzeugt anhand der Konfiguration alle Events
		 *
		 */
		public static function CreateEvents()
			{
			$configuration = self::Get_EventConfigurationAuto();

			foreach ($configuration as $variableId=>$params)
				{
				self::CreateEvent($variableId, $params[0]);
				}
			}

		/**
		 * @public
		 *
		 * Listet anhand der Konfiguration alle Events
		 *
		 */
		public static function ListEvents($type="")
			{
			$configuration = self::Get_EventConfigurationAuto();
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
		public static function ListGroups($type="")
			{
			$configuration = self::Get_EventConfigurationAuto();
			$result=array();
			foreach ($configuration as $variableId=>$params)
				{
				switch ($type)
					{
				   case 'Motion':
					case 'Contact':
						if ($type==$params[0])
						   {
							$result[$params[1]]="available";
							}
					   break;
					default:
						$result[$params[1]]="available";
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
		public static function CreateEvent($variableId, $eventType)
			{
			switch ($eventType)
				{
				case 'Temperatur':
					$triggerType = 1;
					break;
				case 'Feuchtigkeit':
					$triggerType = 0;
					break;
				case 'par0':
				case 'par1':
				case 'par2':
				   break;
				default:
					throw new IPSMessageHandlerException('Found unknown EventType '.$eventType);
				}
			IPSLogger_Dbg (__file__, 'Created IPSDetectMovementHandler Event for Variable='.$variableId);
			}


		/**
		 * @private
		 *
		 * Speichert die aktuelle Event Konfiguration
		 *
		 * @param string[] $configuration Konfigurations Array
		 */
		private static function StoreEventConfiguration($configuration) {

			// Build Configuration String
			$configString = '$eventTempConfiguration = array(';
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
			$pos1 = strpos($fileContent, '$eventTempConfiguration = array(');
			$pos2 = strpos($fileContent, 'return $eventTempConfiguration;');

			if ($pos1 === false or $pos2 === false) {
				throw new IPSMessageHandlerException('EventConfiguration could NOT be found !!!', E_USER_ERROR);
			}
			$fileContentNew = substr($fileContent, 0, $pos1).$configString.substr($fileContent, $pos2);
			file_put_contents($fileNameFull, $fileContentNew);
			self::Set_EventConfigurationAuto($configuration);
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
		public static function RegisterEvent($variableId, $eventType, $componentParams, $moduleParams)
			{
			$configurationAuto = self::Get_EventConfigurationAuto();
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

				self::StoreEventConfiguration($configurationAuto);
				self::CreateEvent($variableId, $eventType);

		}



	}











	/** @}*/
?>
