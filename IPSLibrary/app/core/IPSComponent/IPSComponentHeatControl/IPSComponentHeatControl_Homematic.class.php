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

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	
	IPSUtils_Include ('IPSComponentHeatControl.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatControl');

	class IPSComponentHeatControl_Homematic extends IPSComponentHeatControl {

		protected 	$tempValue;
		protected 	$installedmodules;
		protected	$instanceId;			/* Instanz des Homematic Gerätes, wird mitgeliefert vom Event Handler */
		
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
				{	/* ROID Angabe auf der ersten Position */
				$this->rpcADR 			= $rpcADR;				
				$this->RemoteOID  	= $instanceId;
				$this->instanceId		= null;
				}
			else
				{	/* keine ROID Angabe auf der ersten Position, kommt von IPSHeat */
				$this->instanceId		= $instanceId;
				if (strpos($rpcADR,":") !== false )
					{ 	/* ROID Angabe auf der zweiten Position */			
					$this->RemoteOID  	= $rpcADR;
					$this->rpcADR 			= $rpcADR;
					}
				else
					{	
					$this->RemoteOID  	= null;
					$this->rpcADR 			= $rpcADR;
					}				
				}
			$this->tempValue  	= $lightValue;
			
			//echo "construct IPSComponentHeatControl_Homematic with Parameter : Instanz (Remote oder Lokal): ".$this->instanceId." ROIDs:  ".$this->RemoteOID." Remote Server : ".$this->rpcADR." Zusatzparameter :  ".$this->tempValue."\n";
			
			$this->remoteServerSet();
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
		public function HandleEvent($variable, $value, IPSModuleHeatControl $module)
			{
			//echo "HeatControl Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: HeatControl Message Handler für VariableID '.$variable.' mit Wert '.$value);			
			
			$log=new HeatControl_Logging($variable);
			$result=$log->HeatControl_LogValue($value);
			
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