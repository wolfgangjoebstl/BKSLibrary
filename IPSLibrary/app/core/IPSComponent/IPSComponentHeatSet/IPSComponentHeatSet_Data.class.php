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

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	
	IPSUtils_Include ('IPSComponentHeatSet.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatSet');

	class IPSComponentHeatSet_Data extends IPSComponentHeatSet 
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
		 * Initialisierung eines IPSComponentheatControl_Data Objektes
		 *
		 * legt die Remote Server an, die wenn RemoteAccess Modul installiert ist reported werden müssen
		 * var1 ist eine Liste aller Remote Server mit den entsprechenden Remote OID Nummern
		 * die weiteren Variablen werden nicht benötigt	
		 *
		 * die Module sind eigentlich gleich für alle unterschiedlichen Datenobjekte (Data, FS20, Homematic, HoimematicIP)
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
						
			//echo "construct IPSComponentHeatSet_Data with Parameter : Instanz (Remote oder Lokal): ".$this->instanceId." ROIDs:  ".$this->RemoteOID." Remote Server : ".$this->rpcADR." Zusatzparameter :  ".$this->tempValue."\n";
			$this->remoteServerSet();
			}
			
		/**
		 * @public
		 *
		 * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
		 * an das entsprechende Module zu leiten.
		 *
		 * hier eigentlich nur das Logging aufrufen und die Speicherung des Wertes auf den remoteAccess Servern durchführen
		 *
		 * @param integer $variable ID der auslösenden Variable
		 * @param string $value Wert der Variable
		 * @param IIPSModuleHeatControl $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleHeatSet $module)
			{
			echo "HeatControl Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: HeatSet Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			
			
			if (isset ($this->installedmodules["Stromheizung"])) $module->SyncSetTemp($value, $this);			
			
			$log=new HeatSet_Logging($variable,IPS_GetName($variable));
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
			echo "    IPSComponentHeatSet_Data SetState RPC-Adresse:".$this->rpcADR." InstanceID ".$this->instanceId." und Level ".$level." Power ".$power." \n";
            if (isset ($this->installedmodules["Stromheizung"])) $module->SyncState($power, $this); 
			if ($this->rpcADR==Null)
				{
				/* Dummyobjekt, es gibt nichts zu setzen. Die IPS Heat Register reichen aus */
				}
			else
				{
				$rpc = new JSONRPC($this->rpcADR);
				$rpc->SetValue($this->instanceId, $power);
				}
			}

		public function SetLevel($power, $level, $debug=false)
			{
			if ($debug) echo "    IPSComponentHeatSet_Data SetLevel RPC-Adresse:".$this->rpcADR." InstanceID ".$this->instanceId." und Level ".$level." Power ".$power." \n";
            if (isset ($this->installedmodules["Stromheizung"])) 
                {
                IPSUtils_Include ('IPSModuleHeatSet_All.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatSet');
                $module = new IPSModuleHeatSet_All();
                $module->SyncSetTemp($level, $this);                	
                }
			if ($this->rpcADR==Null)
				{
				/* Dummyobjekt, es gibt nichts zu setzen. Die IPS Heat Register reichen aus 
                   Nachdem es aber keine Hardware Geräte Register gibt die dann über Handle Event wieder rück synchronisiert werden,
                   diesen Teil auch gleich hier machen.
                 */  
				}
			else
				{
				$rpc = new JSONRPC($this->rpcADR);
				$rpc->SetValue($this->instanceId, $level);
				}
			}

		public function SetMode($power, $mode)
			{
			echo "    IPSComponentHeatSet_Data SetMode RPC-Adresse:".$this->rpcADR." InstanceID ".$this->instanceId." und Mode ".$mode." bei Power ".$power." \n";
            if (isset ($this->installedmodules["Stromheizung"])) 
				{
                IPSUtils_Include ('IPSModuleHeatSet_All.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatSet');
                $module = new IPSModuleHeatSet_All();
				$module->SyncSetMode($mode, $this);
				} 
			if ($this->rpcADR==Null)
				{
				/* Dummyobjekt, es gibt nichts zu setzen. Die IPS Heat Register reichen aus */
				}
			else
				{
				$rpc = new JSONRPC($this->rpcADR);
				$rpc->SetValue($this->instanceId, $mode);
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