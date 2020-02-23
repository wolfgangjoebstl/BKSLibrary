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
	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");	

	class IPSComponentHeatSet_HomematicIP extends IPSComponentHeatSet {

		protected 	$tempValue;
		protected 	$installedmodules;
		protected 	$instanceId;		/* Instanz des Homematic Gerätes, wird mitgeliefert vom Event Handler */
		protected  	$deviceHM;			/* Typoe des gerätes, funktioniert nur wenn HM Inventory installiert ist */

		protected 	$RemoteOID;		/* Liste der RemoteAccess server, Server Kurzname getrennt von OID durch : */
		protected 	$remServer;		/* Liste der Urls und der Kurznamen */
		private 		$rpcADR;			/* mit der Parametrierung übergegebene Server Shortnames und OIDs, getrennt durch : und für jeden Eintrag durch ;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentheatSet_Homematic Objektes
		 *
		 * legt die Remote Server an, an die wenn RemoteAccess Modul installiert ist reported werden muss
		 * var1 ist eine Liste aller Remote Server mit den entsprechenden Remote OID Nummern
		 
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param boolean $reverseControl Reverse Ansteuerung des Devices
		 */
		public function __construct($instanceId=null, $rpcADR="", $lightValue=null) 
			{
            //echo "IPSComponentHeatSet_HomematicIP:construct aufgerufen mit $instanceId (".IPS_GetName($instanceId).") RPCAdr $rpcADR Add Parameter $lightValue \n";
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
			//$this->instanceId  	= IPSUtil_ObjectIDByPath($instanceId);
			
			$instances=array();
			foreach (HomematicList() as $instanceHM)
				{
                if ( (isset($instanceHM["OID"])) && (isset($instanceHM["HMDevice"])) )
                    {
				    //echo $instanceHM["OID"]."  ".$instanceHM["HMDevice"]."\n";
				    $instances[$instanceHM["OID"]]=$instanceHM["HMDevice"];
                    }
				}
            //print_r($instances);    
            if (isset($instances[$this->instanceId]) ) 
				{
				//echo "    construct IPSComponentHeatSet_Homematic with Parameter : Instanz (Remote oder Lokal): ".$this->instanceId." ROIDs:  ".$this->RemoteOID." Remote Server : ".$this->rpcADR." Zusatzparameter :  ".$this->tempValue."  ";
				//echo "---> gefunden, Typ ist ".$instances[$this->instanceId]."\n"; 	
				$this->deviceHM = $instances[$this->instanceId];		
				}
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
		public function HandleEvent($variable, $value, IPSModuleHeatSet $module)
			{
			echo "HeatSet HomematicIP HandleEvent für VariableID : ".$variable.' ('.IPS_GetName($variable).") mit Wert : ".$value." \n";
			IPSLogger_Inf(__file__, 'IPSComponentHeatSet_HomematicIP HandleEvent für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			
			
            $log=new HeatSet_Logging($variable);			
			if ( ((IPS_GetName($variable))=="CONTROL_MODE") || ((IPS_GetName($variable))=="SET_POINT_MODE") )
				{
				if (isset ($this->installedmodules["Stromheizung"])) $module->SyncSetMode($value, $this);
				$result=$log->HeatSet_LogValue($value,IPS_GetName($variable));
				}
			else
				{	
				if (isset ($this->installedmodules["Stromheizung"])) $module->SyncSetTemp($value, $this);
				$result=$log->HeatSet_LogValue($value);
				}
			
			$this->WriteValueRemote($value);    /* schreibt alle Remote Server an die in $this->RemoteOID stehen, Format Kurzname:ROID; */
			}
			
		/**
		 * @public
		 *
		 * Zustand Setzen, aufgeteilt in SetState, SetLevel und SetMode
		 * abhängig vom Typ des Thermostats werden die Funktionen unterschiedlich interpretiert
		 *
		 * Ein/Aus setzt die Temperatur bei Aus auf 6 Grad, Rückwärts
		 *
		 * @param integer $power Geräte Power
		 * @param integer $level Wert für Dimmer Einstellung (Wertebereich 0-100)
		 */
		public function SetState($power, $level)
			{
			//echo "Adresse:".$this->rpcADR."und Level ".$level." Power ".$power." \n";
			if (!$power) $setlevel=6; else $setlevel=$level;		// keine Beeinflussung von Level anhand des States
			//$setlevel=$level;				
			if ($this->rpcADR==Null)
				{
				//echo "   IPSComponentHeatSet_HomematicIP SetState von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')." \n";
                IPSLogger_Inf(__file__, "IPSComponentHeatSet_HomematicIP SetState von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')); 
				HM_WriteValueFloat($this->instanceId, "SET_POINT_TEMPERATURE", $setlevel);
				}
			else
				{
				$rpc = new JSONRPC($this->rpcADR);
				$rpc->HM_WriteValueFloat($this->instanceId, "SET_POINT_TEMPERATURE", $setlevel);
				}	
			}

		public function SetLevel($power, $level)				// gleich wie bei Level, verwendet für Temperatur
			{
			//echo "Adresse:".$this->rpcADR."und Level ".$level." Power ".$power." \n";
			//if (!$power) $setlevel=6; else $setlevel=$level;		// keine Beeinflussung von Level anhand des States
			$setlevel=$level;				
			if ($this->rpcADR==Null)
				{
				//echo "   IPSComponent HeatSet_HomematicIP SetState von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')." \n";
                IPSLogger_Inf(__file__, "IPSComponent HeatSet_HomematicIP SetTemp von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')); 
				HM_WriteValueFloat($this->instanceId, "SET_POINT_TEMPERATURE", $setlevel);
				}
			else
				{
                IPSLogger_Inf(__file__, "IPSComponent HeatSet_HomematicIP rpc->SetTemp von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')); 
				$rpc = new JSONRPC($this->rpcADR);
				$rpc->HM_WriteValueFloat($this->instanceId, "SET_POINT_TEMPERATURE", $setlevel);
				}	
			}

		public function SetMode($power, $mode)
			{
			$setMode=$mode;
			if ($this->rpcADR==Null)
				{
				//echo "   IPSComponent HeatSet_HomematicIP SetMode von ".IPS_GetName($this->instanceId)." (".$this->instanceId.") mit folgenden Parametern Mode ".$setMode." Power ".($power?'On':'Off')." \n";
				switch ($this->deviceHM)
					{
					case "HmIP-WTH-2":
						//echo "     --> SetMode, Spezialbehandlung, neue Thermostattype \"HmIP-WTH-2\" \n";
                        IPSLogger_Inf(__file__, "IPSComponent HeatSet_HomematicIP SetMode (SET_POINT_MODE) von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Mode ".$setMode." Power ".($power?'On':'Off'));
						HM_WriteValueInteger($this->instanceId, "SET_POINT_MODE", $setMode);
						break;
					default:	 
                        IPSLogger_Inf(__file__, "IPSComponent HeatSet_HomematicIP SetMode (CONTROL_MODE) von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Mode ".$setMode." Power ".($power?'On':'Off'));
						HM_WriteValueInteger($this->instanceId, "CONTROL_MODE", $setMode);
						break;
					}	
				}
			else
				{
				//echo "   IPSComponent HeatSet_HomematicIP SetState auf RemoteServer von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Mode ".$setMode." Power ".($power?'On':'Off')." \n";
                IPSLogger_Inf(__file__, "IPSComponent HeatSet_HomematicIP rpc->SetMode von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Mode ".$setMode." Power ".($power?'On':'Off')); 
				$rpc = new JSONRPC($this->rpcADR);
				$rpc->HM_WriteValueInteger($this->instanceId, "CONTROL_MODE", $setMode);
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