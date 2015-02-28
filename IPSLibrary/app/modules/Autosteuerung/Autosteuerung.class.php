<?

		/**
		 * @public
		 *
		 * Erzeugt ein Event für eine übergebene Variable, das den IPSMessageHandler beim Auslösen
		 * aufruft.
		 *
		 * @param integer $variableId ID der auslösenden Variable
		 * @param string $eventType Type des Events (OnUpdate oder OnChange)
		 */
		function CreateEvent2($variableId, $eventType, $scriptId)
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

		function storeconfig($configuration)
		   {
			// Build Configuration String
			$configString = '$eventConfiguration = array(';
			foreach ($configuration as $variableId=>$params) {
				$configString .= PHP_EOL.chr(9).chr(9).chr(9).$variableId.' => array(';
				for ($i=0; $i<count($params); $i=$i+3) {
					if ($i>0) $configString .= PHP_EOL.chr(9).chr(9).chr(9).'               ';
					$configString .= "'".$params[$i]."','".$params[$i+1]."','".$params[$i+2]."',";
				}
				$configString .= '),';
			}
			$configString .= PHP_EOL.chr(9).chr(9).chr(9).');'.PHP_EOL.PHP_EOL.chr(9).chr(9);

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
			}

		function registerAutoEvent($variableId, $eventType, $componentParams, $moduleParams)
			{
			$configuration = Autosteuerung_GetEventConfiguration();

			if (array_key_exists($variableId, $configuration))
				{
				$moduleParamsNew = explode(',', $moduleParams);
				$moduleClassNew  = $moduleParamsNew[0];

				$params = $configuration[$variableId];

				for ($i=0; $i<count($params); $i=$i+3)
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
				// Variable NOT found --> Create Configuration
				$configuration[$variableId][] = $eventType;
				$configuration[$variableId][] = $componentParams;
				$configuration[$variableId][] = $moduleParams;
				}

			storeconfig($configuration);
   		}



?>
