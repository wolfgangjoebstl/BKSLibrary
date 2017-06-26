<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentSwitch_Monitor.class.php
	 * @author        Wolfgang Jöbstl, inspiriert durch Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentSwitch_Monitor
    *
    * Definiert eine IPSComponentSwitch_Monitor Klasse, die ein IPSComponentSwitch Object für die Monitor Steuerung (ein/aus) des PC auf dem lokalen Server implementiert.
	* der Status kann auf Logging Server gespiegelt werden.
	*
	* Aufruf erfolgt von zB IPS-Light mit folgendem Parameter:
	*
	* IPSComponentSwitch_Monitor,12345,http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/ 
    *
    */

	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');

	class IPSComponentSwitch_Monitor extends IPSComponentSwitch {

		private $instanceId;
		private $supportsOnTime;
		private $installedmodules;
		private $remServer;
		
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_Monitor Objektes
		 *
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param integer $supportsOnTime spezifiziert ob das Homematic Device eine ONTIME unterstützt
		 */
		public function __construct($var1=0, $instanceId=0, $supportsOnTime=true) 
			{
			$this->instanceId     = IPSUtil_ObjectIDByPath($instanceId);
			$this->supportsOnTime = $supportsOnTime;
			
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
			if (isset ($this->installedmodules["RemoteAccess"]))
				{
				IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
				$this->remServer	  = RemoteAccessServerTable();
				}
			else
				{								
				$this->remServer	  = array();
				}			
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
			//echo "Switch Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			$module->SyncState($value, $this);
			
			/* hier erfolgt noch keine Übertragung auf den remote Server für das logging*/
						
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
		public function SetState($value, $onTime=false) 
			{
			if ($value==true)
				{
				/* Monitor einschalten, zwei Varianten zur Auswahl, Befehl monitor on funktioniert nicht immer */
				IPS_ExecuteEX("c:/Scripts/nircmd.exe", "sendkeypress ctrl+alt+F1", false, false, 1);
         		//IPS_ExecuteEX("c:/Scripts/nircmd.exe", "monitor on", true, false, 1);
				}
			else
				{
				/* Monitor ausschalten */
        		IPS_ExecuteEX("c:/Scripts/nircmd.exe", "monitor off", true, false, 1);
				}
			}

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		public function GetState() {

		}

	}

	/** @}*/
?>