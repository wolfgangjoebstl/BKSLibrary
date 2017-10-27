<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSModuleSensor_HeatSet_All.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

	/**
	 * @class IPSModule_HeatSet_All
	 *
	 * Definiert ein IPSModuleSensor Object, das als Wrapper für Sensoren in der IPSLibrary
	 * verwendet werden kann.
	 *
	 */

	IPSUtils_Include ('IPSModuleHeatSet.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatSet');
	
	//IPSUtils_Include ('Stromheizung_Configuration.inc.php', 'IPSLibrary::config::modules::Stromheizung');
	
	class IPSModuleHeatSet_All extends IPSModuleHeatSet {

		private $instanceId;
		private $movementId;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_IPSShadowing Objektes
		 *
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param boolean $movementId Movement Command
		 */
		public function __construct($var1=null,$var2=null,$var3=null)
			{
			echo "construct IPSModuleHeatSet_All with parameter ".$var1."  ".$var2."  ".$var3."\n";
			//$this->instanceId = IPSUtil_ObjectIDByPath($instanceId);
			//$this->movementId = $movementId;
			}
	
			/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation der aktuellen Position der Beschattung
		 *
		 * @param string $position Aktuelle Position der Beschattung (Wertebereich 0-100)
		 */
		public function SyncPosition($position, IPSComponentHeatSet $componentToSync) 
			{
			if ($position>6) { $state=true; } else { $state=false; }
			echo "HandleEvent: SyncPosition mit Status ".($state?'On':'Off')." mit Wert ".$position."\n";
			IPSLogger_Dbg(__file__, 'HandleEvent: SyncPosition mit Status '.($state?'On':'Off').' mit Wert '.$position);			
			$componentParamsToSync = $componentToSync->GetComponentParams();
			$deviceConfig          = IPSHeat_GetHeatConfiguration();
			foreach ($deviceConfig as $deviceIdent=>$deviceData) {
				$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);
				$componentParamsConfig = $componentConfig->GetComponentParams();
				if ($componentParamsConfig==$componentParamsToSync) {
					$lightManager = new IPSHeat_Manager();
					$lightManager->SynchronizePosition($deviceIdent, $state, $position);				
				}
			}
		}
	
		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation einer Beleuchtung zu IPSLight
		 *
		 * @param string $state Aktueller Status des Switch
		 */
		public function SyncState($value, IPSComponentHeatSet $componentToSync) 
			{
			echo "HandleEvent: SyncState mit Wert ".$value."\n";
			IPSLogger_Dbg(__file__, 'HandleEvent: SyncState mit Wert '.$value);
			if ($value>6) { $state=true; } else {$state=false; }
			$componentParamsToSync = $componentToSync->GetComponentParams();
			$deviceConfig          = IPSHeat_GetHeatConfiguration();
			//print_r($deviceConfig);
			/* alle Thermostate der Reihe nach durchgehen, Gliederung ist nach Name des Objektes */
			foreach ($deviceConfig as $deviceIdent=>$deviceData) 
				{
				echo "     Bearbeite ".$deviceIdent.":\n";
				$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);
				$componentParamsConfig = $componentConfig->GetComponentParams();
				echo "     Vergleiche ".$componentParamsConfig."   ".$componentParamsToSync."\n";
				if ($componentParamsConfig==$componentParamsToSync) 
					{
					$lightManager = new IPSHeat_Manager();
					$lightManager->SynchronizeSwitch($deviceIdent, $state);
					}
				}
			echo "\nEnde SyncState.\n";	
			}
			
		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation von Sensorwerten mit Modulen
		 *
		 * @param string $value Sensorwert
		 * @param IPSComponentSensor $component Sensor Komponente
		 */
		public function SyncButton($value, IPSComponentHeatSet $component) {
			$this->ExecuteButton();
		}

		/**
		 * @public
		 *
		 * Ermöglicht das Verarbeiten eines Taster Signals
		 *
		 */
		public function ExecuteButton () {
			$device = new IPSShadowing_Device($this->instanceId);
			$movementId = GetValue(IPS_GetObjectIDByIdent(c_Control_Movement, $this->instanceId));
			if ($movementId==c_MovementId_MovingIn or $movementId==c_MovementId_MovingOut or $movementId==c_MovementId_Up or $movementId==c_MovementId_Down) {
				$device->MoveByControl(c_MovementId_Stop);
			} else {
				$device->MoveByControl($this->movementId);
			}
		}

	}

	/** @}*/
?>