<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentHeatSet_HomematicIP.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentHeatSet_HomematicIP
    *
    * Definiert ein IPSComponentHeatSet_HomematicIP Object, das ein IIPSComponentHeatSet Object für HomematicIP implementiert.
    *
    */
	 
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	
	IPSUtils_Include ('IPSComponentHeatSet.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatSet');

	class IPSComponentHeatSet_HomematicIP extends IPSComponentHeatSet {

		protected 	$tempValue;
		protected 	$installedmodules;
		protected 	$instanceId;		/* Instanz des Homematic Gerätes, wird mitgeliefert vom Event Handler */

		protected 	$RemoteOID;		/* Liste der RemoteAccess server, Server Kurzname getrennt von OID durch : */
		protected 	$remServer;		/* Liste der Urls und der Kurznamen */
		private 		$rpcADR;			/* mit der Parametrierung übergegebene Server Shortnames und OIDs, getrennt durch : und für jeden Eintrag durch ;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentheatControl_Homematic Objektes
		 *
		 * legt die Remote Server an, an die wenn RemoteAccess Modul installiert ist reported werden muss
		 * var1 ist eine Liste aller Remote Server mit den entsprechenden Remote OID Nummern
		 
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param boolean $reverseControl Reverse Ansteuerung des Devices
		 */
		public function __construct($instanceId=null, $rpcADR="", $lightValue=null) 
			{
			if (strpos($instanceId,":") !== false ) 
				{
				$this->RemoteOID  	= $instanceId;
				$this->instanceId		= null;
				}
			else
				{   /* keine ROID Angabe, kommt von IPSHeat */
				$this->instanceId		= $instanceId;
				$this->RemoteOID  	= null;
				}			
			$this->rpcADR = $rpcADR;
			$this->tempValue    = $lightValue;
			$this->instanceId     = IPSUtil_ObjectIDByPath($instanceId);
			
			//echo "construct IPSComponentHeatSet_HomematicIP with parameter ".$this->RemoteOID."  ".$this->instanceId."  (".IPS_GetName($this->instanceId).")   ".$this->rpcADR."  ".$this->tempValue."\n";
			IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
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
		 * @param IIPSModuleHeatControl $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleHeatSet $module)
			{
			echo "HeatControl Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
	   		IPSLogger_Dbg(__file__, 'HandleEvent: HeatControl Message Handler für VariableID '.$variable.' mit Wert '.$value);			
			
			$log=new HeatSet_Logging($variable);
			$result=$log->HeatSet_LogValue($value);
			
			$this->WriteValueRemote($value);
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
					HM_WriteValueFloat($this->instanceId, "SET_POINT_TEMPERATURE", 6);
					}
				else
					{
					$levelHM = $level;
					HM_WriteValueFloat($this->instanceId, "SET_POINT_TEMPERATURE", $levelHM);
					}
				}
			else
				{
				$rpc = new JSONRPC($this->rpcADR);
				if (!$power) {
					$rpc->HM_WriteValueFloat($this->instanceId, "SET_POINT_TEMPERATURE", 6);
					}
				else
					{
					$levelHM = $level;
					$rpc->HM_WriteValueFloat($this->instanceId, "SET_POINT_TEMPERATURE", $levelHM);
					}
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
			GetValue(IPS_GetVariableIDByIdent('STATE', $this->instanceId));
		}

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		public function GetLevel() {
			GetValue(IPS_GetVariableIDByIdent('STATE', $this->instanceId));
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