<?
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
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

	class IPSComponentSwitch_Remote extends IPSComponentSwitch {

		private $instanceId;
		private $supportsOnTime;
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_Homematic Objektes
		 *
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param integer $supportsOnTime spezifiziert ob das Homematic Device eine ONTIME unterstützt
		 */
		public function __construct($var1, $instanceId=0, $supportsOnTime=true) {
			$this->instanceId     = IPSUtil_ObjectIDByPath($instanceId);
			$this->supportsOnTime = $supportsOnTime;
			$this->RemoteOID    = $var1;
			echo "InstanceID gesucht : ".$this->instanceId."\n";
			$this->remServer    = RemoteAccess_GetConfiguration();
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
		public function HandleEvent($variable, $value, IPSModuleSwitch $module)
			{
			//$module->SyncState($value, $this);
			echo "Switch Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			//print_r($this);
			//print_r($module);
			//echo "-----Hier jetzt alles programmieren was bei Veränderung passieren soll:\n";
			$params= explode(';', $this->RemoteOID);
			print_r($params);
			foreach ($params as $val)
				{
				$para= explode(':', $val);
				echo "Wert :".$val." Anzahl ",count($para)." \n";
            if (count($para)==2)
               {
					$Server=$this->remServer[$para[0]];
					echo "Server : ".$Server."\n";
					$rpc = new JSONRPC($Server);
					$roid=(integer)$para[1];
					echo "Remote OID: ".$roid."\n";
					$rpc->SetValue($roid, $value);
					}
				}
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
			if ($onTime!==false and $value and $this->supportsOnTime===true) 
				HM_WriteValueFloat($this->instanceId, "ON_TIME", $onTime);  
			
			HM_WriteValueBoolean($this->instanceId, "STATE", $value);
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
