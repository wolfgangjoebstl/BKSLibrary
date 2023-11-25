<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentDimmer_Homematic.class.php
	 * @author        Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentDimmer_Homematic
    *
    * Definiert ein IPSComponentDimmer_Homematic Object, das ein IPSComponentDimmer Object für Homematic implementiert.
    *
    * @author Andreas Brauneis
    * @version
    * Version 2.50.1, 31.01.2012<br/>
    */

	IPSUtils_Include ('IPSComponentDimmer.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentDimmer');

	class IPSComponentDimmer_RFS20 extends IPSComponentDimmer {

		private $instanceId;
		private $rpcADR;
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentDimmer_Homematic Objektes
		 *
		 * @param integer $instanceId InstanceId des Homematic Devices
		 */
		public function __construct($instanceId, $rpcADR) {
			$this->instanceId = IPSUtil_ObjectIDByPath($instanceId);
			$this->rpcADR = $rpcADR;
		}

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
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleDimmer $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleDimmer $module){
		}

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param integer $power Geräte Power
		 * @param integer $level Wert für Dimmer Einstellung (Wertebereich 0-100)
		 */
		public function SetState($power, $level) {
			// Zeit in Sekunden wie schnell der Aktor dimmmen soll
			$DimspeedSec = 2;
			//echo "Adresse:".$this->rpcADR."\n";
			$rpc = new JSONRPC($this->rpcADR);		   
			if (!$power) {
				$rpc->FS20_SetIntensity ($this->instanceId, 0, $DimspeedSec);
				// Wartezeit um den Aktor auf OFF zu Schalten
				// IPS_Sleep wird in Millisekunden angegeben, darum * 1000
				IPS_Sleep ($DimspeedSec*1000);
				$rpc->FS20_SwitchMode	($this->instanceId, false);
			} else {
				// 100% Helligkeit Entsprechen bei FS20 dem Wert 16
				$levelFS20 = round($level / 100 * 16);
				$rpc->FS20_SetIntensity ($this->instanceId, $levelFS20, $DimspeedSec);
			}
		}

		/**
		 * @public
		 *
		 * Liefert aktuellen Level des Dimmers
		 *
		 * @return integer aktueller Dimmer Level
		 */
		public function GetLevel() {
			return GetValue(IPS_GetVariableIDByName('LEVEL', $this->instanceId))*100;
		}

		/**
		 * @public
		 *
		 * Liefert aktuellen Power Zustand des Dimmers
		 *
		 * @return boolean Gerätezustand On/Off des Dimmers
		 */
		public function GetPower() {
			return GetValue(IPS_GetVariableIDByName('LEVEL', $this->instanceId)) > 0;
		}

	}

	/** @}*/
?>