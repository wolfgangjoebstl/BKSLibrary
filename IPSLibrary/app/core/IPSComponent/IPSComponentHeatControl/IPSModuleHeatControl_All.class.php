<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSModuleSensor_HeatControl.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

	/**
	 * @class IPSModuleSensor_HeatControl
	 *
	 * Definiert ein IPSModuleSensor Object, das als Wrapper für Sensoren in der IPSLibrary
	 * verwendet werden kann.
	 *
	 */

	IPSUtils_Include ('IPSModuleHeatControl.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl');

	class IPSModuleHeatControl_All extends IPSModuleHeatControl {

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
			echo "construct IPSModuleHeatControl_All with parameter ".$var1."  ".$var2."  ".$var3."\n";
			//$this->instanceId = IPSUtil_ObjectIDByPath($instanceId);
			//$this->movementId = $movementId;
			}
	
	
		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation von Sensorwerten mit Modulen
		 *
		 * @param string $value Sensorwert
		 * @param IPSComponentSensor $component Sensor Komponente
		 */
		public function SyncButton($value, IPSComponentHeatControl $component) {
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