<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentSwitch_Homematic.class.php
	 * @author        Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentSwitch_Homematic
    *
    * Definiert ein IPSComponentSwitch_Homematic Object, das ein IPSComponentSwitch Object für Homematic implementiert.
    *
    * @author Andreas Brauneis
    * @version
    * Version 2.50.1, 31.01.2012<br/>
    */

	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');

	class IPSComponentSwitch_XValue extends IPSComponentSwitch {

		private $instanceId;
		private $supportsOnTime;
		private $rpcADR;
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_Homematic Objektes
		 *
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param integer $supportsOnTime spezifiziert ob das Homematic Device eine ONTIME unterstützt
		 */
		public function __construct($instanceId, $rpcADR, $supportsOnTime=true) {
			$this->instanceId     = IPSUtil_ObjectIDByPath($instanceId);
			$this->supportsOnTime = $supportsOnTime;
			$this->rpcADR = $rpcADR;
		}

		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleSwitch $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleSwitch $module){
			$module->SyncState($value, $this);
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
		 * Zustand Setzen 
		 *
		 * @param boolean $value Wert für Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll
		 */
		public function SetState($value, $onTime=false) {
			//echo "Adresse:".$this->rpcADR."\n";
			$rpc = new JSONRPC($this->rpcADR);
			$rpc->SetValue($this->instanceId, $value);
		}

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		public function GetState() {
			GetValue(IPS_GetVariableIDByIdent('STATE', $this->instanceId));
		}

	}

	/** @}*/
?>