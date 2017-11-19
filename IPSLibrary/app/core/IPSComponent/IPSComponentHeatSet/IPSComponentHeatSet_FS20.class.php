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
    * @class IPSComponentHeatControl_Data
    *
    * Definiert ein IPSComponentHeatControl_Data Object, das ein IPSComponentHeatControl Object für Homematic implementiert.
    *
    */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	
	IPSUtils_Include ('IPSComponentHeatSet.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatSet');

	class IPSComponentHeatSet_FS20 extends IPSComponentHeatSet 
		{

		protected 	$tempValue;
		protected 	$installedmodules;
		protected	$instanceId;			/* Instanz des Homematic Gerätes, wird mitgeliefert vom Event Handler */
		
		protected 	$RemoteOID;		/* Liste der RemoteAccess server, Server Kurzname getrennt von OID durch : */
		protected 	$remServer;		/* Liste der Urls und der Kurznamen */
		private 		$rpcADR;			/* mit der Parametrierung übergegebene Server Shortnames und OIDs, getrennt durch : und für jeden Eintrag durch ;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentheatControl_FS20 Objektes
		 *
		 * legt die Remote Server an, an die wenn RemoteAccess Modul installiert ist reported werden muss
		 * var1 ist eine Liste aller Remote Server mit den entsprechenden Remote OID Nummern	
		 *	 
		 
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param boolean $reverseControl Reverse Ansteuerung des Devices
		 */
		public function __construct($instanceId=null, $rpcADR="", $lightValue=null) 
			{
			if (strpos($instanceId,":") !== false ) 
				{	/* ROID Angabe auf der ersten Position */
				$this->rpcADR 			= null;				
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
			//$this->instanceId  	= IPSUtil_ObjectIDByPath($instanceId);
			
			//echo "construct IPSComponentHeatSet_FS20 with Parameter: Instanz (Remote oder Lokal): ".$this->instanceId." ROIDs:  ".$this->RemoteOID." Remote Server : ".$this->rpcADR." Zusatzparameter :  ".$this->tempValue."\n";	
			$this->remoteServerSet();
			}
			
		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 *
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IIPSModuleHeatControl $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleHeatSet $module)
			{
			//echo "HeatSet FS20 Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: HeatSet FS20 Message Handler für VariableID '.$variable.' mit Wert '.$value);			
			
			if (isset ($this->installedmodules["Stromheizung"])) 
				{
				//IPSUtils_Include ('Stromheizung_Configuration.inc.php', 'IPSLibrary::config::modules::Stromheizung');
				$module->SyncPosition($value, $this);
				}
						
			$log=new HeatSet_Logging($variable);		/* zweite Variable ist optional und wäre der Variablenname wenn er nicht vom Parent Namen abgeleitet werden soll */
			$result=$log->HeatSet_LogValue($value);	/* Variable ist optional, sonst wird sie aus der OID vom construct ausgelesen */
			
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
			//echo "     Component HeatSet_FS20 SetState Adresse:".$this->rpcADR."und Level ".$level." Power ".($power?'On':'Off')." \n";
			if (!$power) 
				{
				$setlevel=6;
				}
			else
				{
				$setlevel=$level;
				}				
			if ($this->rpcADR==Null)
				{
				FHT_SetTemperature($this->instanceId,  $setlevel);
				}
			else
				{
				$rpc = new JSONRPC($this->rpcADR);
				//echo "           Befehl FHT_SetTemperature(".$this->instanceId.", ".$setlevel.") ausfuehren.\n";
				$rpc->FHT_SetTemperature((integer)$this->instanceId, (float)$setlevel);
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