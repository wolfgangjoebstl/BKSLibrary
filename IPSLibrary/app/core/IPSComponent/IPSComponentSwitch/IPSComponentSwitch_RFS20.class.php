<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentSwitch_FS20.class.php
	 * @author        Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentSwitch_FS20
    *
    * Definiert ein IPSComponentSwitch_FS20 Object, das ein IPSComponentSwitch Object f�r FS20 implementiert.
    *
    * @author Andreas Brauneis
    * @version
    * Version 2.50.1, 31.01.2012<br/>
    */

	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');

	class IPSComponentSwitch_RFS20 extends IPSComponentSwitch {

		private $instanceId;
		private $rpcADR;
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_FS20 Objektes
		 *
		 * @param integer $instanceId InstanceId des FS20 Devices
		 */
		public function __construct($instanceId, $rpcADR) {
			$this->instanceId = IPSUtil_ObjectIDByPath($instanceId);
			$this->rpcADR = $rpcADR;
		}

		/**
		 * @public
		 *
		 * Funktion liefert String IPSComponent Constructor String.
		 * String kann dazu ben�tzt werden, das Object mit der IPSComponent::CreateObjectByParams
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
		 * @param integer $variable ID der ausl�senden Variable
		 * @param string $value Wert der Variable
		 * @param IPSModuleSwitch $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleSwitch $module){
			$module->SyncState($value, $this);
		}

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param boolean $value Wert f�r Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll
		 */
		public function SetState($value, $onTime=false) {
			$rpc = new JSONRPC($this->rpcADR);
			if (!$onTime or !$value)
				$rpc->FS20_SwitchMode($this->instanceId, $value);
			else
				$rpc->FS20_SwitchDuration($this->instanceId, $value, $onTime);   
		}

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		public function GetState() {
			$value = GetValueBoolean(IPS_GetObjectIDByIdent("StatusVariable",$this->instanceId)); 
			return $value;

		}

	}

	/** @}*/
?>