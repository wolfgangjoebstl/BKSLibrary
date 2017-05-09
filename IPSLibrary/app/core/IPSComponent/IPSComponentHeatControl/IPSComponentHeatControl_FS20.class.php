<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentHeatControl_Homematic.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentShutter_Homematic
    *
    * Definiert ein IPSComponentShutter_Homematic Object, das ein IPSComponentShutter Object für Homematic implementiert.
    *
    */

	IPSUtils_Include ('IPSComponentHeatControl.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl');

	class IPSComponentHeatControl_FS20 extends IPSComponentHeatControl {

		private $instanceId;
		private $reverseControl;
		private $rpcADR;

		/**
		 * @public
		 *
		 * Funktion liefert String IPSComponent Constructor String.
		 * String kann dazu benützt werden, das Object mit der IPSComponent::CreateObjectByParams
		 * wieder neu zu erzeugen.
		 *
		 * @return string Parameter String des IPSComponent Object
		 */
		public function GetComponentParams() {
			return get_class($this).','.$this->instanceId;
		}

		/**
		 * @public
		 *
		 * Zustand Setzen
		 *
		 * @param integer $power Geräte Power
		 * @param integer $level Wert für Dimmer Einstellung (Wertebereich 0-100)
		 */
		public function SetState($power, $level)
			{
			//echo "Adresse:".$this->rpcADR."und Level ".$level." Power ".$power." \n";
			if ($this->rpcADR=="")
			   {
				if (!$power) {
					HM_WriteValueFloat($this->instanceId, "LEVEL", 0);
					}
				else
					{
					$levelHM = $level / 100;
					HM_WriteValueFloat($this->instanceId, "LEVEL", $levelHM);
					}
			   }
			else
			   {
				$rpc = new JSONRPC($this->rpcADR);
				if (!$power) {
					$rpc->HM_WriteValueFloat($this->instanceId, "LEVEL", 0);
					}
				else
					{
					$levelHM = $level / 100;
					$rpc->HM_WriteValueFloat($this->instanceId, "LEVEL", $levelHM);
					}
				}
			}

		/**
		 * @public
		 *
		 * Hinauffahren der Beschattung
		 */
		public function MoveUp(){
		   if ($this->reverseControl) {
				HM_WriteValueFloat($this->instanceId , 'LEVEL', 0);
			} else {
				HM_WriteValueFloat($this->instanceId , 'LEVEL', 1);
			}
		}
		
		/**
		 * @public
		 *
		 * Hinunterfahren der Beschattung
		 */
		public function MoveDown(){
		   if ($this->reverseControl) {
				HM_WriteValueFloat($this->instanceId , 'LEVEL', 1);
			} else {
				HM_WriteValueFloat($this->instanceId , 'LEVEL', 0);
			}
		}
		
		/**
		 * @public
		 *
		 * Stop
		 */
		public function Stop() {
			HM_WriteValueBoolean($this->instanceId , 'STOP', true);
		}

	}

	/** @}*/
?>