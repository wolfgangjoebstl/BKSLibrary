<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentHeatSet_Homematic.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentHeatSet_Homematic
    *
    * Definiert ein IPSComponentHeatSet_Homematic Object, das ein IPSComponentHeatSet Object für Homematic implementiert.
    *
    */

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	
	IPSUtils_Include ('IPSComponentHeatSet.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatSet');
   	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");	

	class defaultIPSComponentHeatSet_Homematic extends IPSComponentHeatSet {

		protected 	$tempValue;
		protected 	$installedmodules;
		protected	$instanceId;			/* Instanz des Homematic Gerätes, wird mitgeliefert vom Event Handler */
		protected  	$deviceHM;			/* Typoe des Gerätes, funktioniert nur wenn HM Inventory installiert ist */
		
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
		 
		 * InstanceId 		ID des Homematic Devices
		 * rpcADR			Liste der Server, Kurzname1:ROID1;Kurzname2:ROID2
		 * weitere Variable zur freien Verfügung, nicht verwendet
		 *  
		 */
		public function __construct($instanceId=null, $rpcADR="", $lightValue=null) 
			{
			//echo "IPSComponentHeatSet_Homematic:construct aufgerufen mit $instanceId (".IPS_GetName($instanceId).") RPCAdr $rpcADR Add Parameter $lightValue \n";
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
			
			//echo "construct IPSComponentHeatSet_Homematic with Parameter : Instanz (Remote oder Lokal): ".$this->instanceId." ROIDs:  ".$this->RemoteOID." Remote Server : ".$this->rpcADR." Zusatzparameter :  ".$this->tempValue."\n";
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
			echo "HandleEvent: HeatSet Homematic Message Handler für VariableID : ".$variable.' ('.IPS_GetName($variable).") mit Wert : ".$value." \n";
			IPSLogger_Dbg(__file__, 'HandleEvent: HeatSet Homemeatic Message Handler für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.$value);			

			if ( (IPS_GetName($variable))=="CONTROL_MODE")
				{
				if (isset ($this->installedmodules["Stromheizung"])) $module->SyncSetMode($value, $this);
				}
			else
				{			
			    if (isset ($this->installedmodules["Stromheizung"])) $module->SyncSetTemp($value, $this);
			
			    $log=new HeatSet_Logging($variable);
			    $result=$log->HeatSet_LogValue($value);
                }

			$this->WriteValueRemote($value);  /* schreibt alle Remote Server an die in $this->RemoteOID stehen, Format Kurzname:ROID; */
			}

		/**
		 * @public
		 *
		 * Zustand Setzen
		 *
		 * 
		 *
		 * @param integer $power Geräte Power
		 * @param integer $level Wert für Dimmer Einstellung (Wertebereich 0-100)
		 */
		public function SetState($power, $level)
			{
			if (!$power) $setlevel=6; else $setlevel=$level;		// keine Beeinflussung von Level anhand des States	
			//$setlevel=$level;
			if ($this->rpcADR==Null)
				{
				echo "   IPSComponentHeatSet_Homematic SetState von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')." \n";
                IPSLogger_Inf(__file__, "IPSComponentHeatSet_Homematic SetState von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')); 
				HM_WriteValueFloat($this->instanceId, "SET_TEMPERATURE", $setlevel);
				}
			else
				{
				//echo "   IPSComponent HeatSet_Homematic SetState mit folgenden Parametern rpc Adresse:".$this->rpcADR."und Level ".$level." Power ".($power?'On':'Off')." \n";
				$rpc = new JSONRPC($this->rpcADR);
				$rpc->HM_WriteValueFloat($this->instanceId, "SET_TEMPERATURE", $setlevel);
				}
			}

		/**
		 * @public
		 *
		 * Temperatur Setzen
		 *
		 * @param integer $power Geräte Power
		 * @param integer $level Wert für Dimmer Einstellung (Wertebereich 0-100)
		 */
		public function SetLevel($power, $level)
			{
			//if (!$power) $setlevel=6; else $setlevel=$level;		// keine Beeinflussung von Level anhand des States	
			$setlevel=$level;
			if ($this->rpcADR==Null)
				{
				//echo "   IPSComponent HeatSet_Homematic SetState von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')." \n";
                IPSLogger_Inf(__file__, "IPSComponent HeatSet_Homematic SetLevel von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')); 
				HM_WriteValueFloat($this->instanceId, "SET_TEMPERATURE", $setlevel);
				}
			else
				{
				//echo "   IPSComponent HeatSet_Homematic SetState mit folgenden Parametern rpc Adresse:".$this->rpcADR."und Level ".$level." Power ".($power?'On':'Off')." \n";
				$rpc = new JSONRPC($this->rpcADR);
				$rpc->HM_WriteValueFloat($this->instanceId, "SET_TEMPERATURE", $setlevel);
				}
			}

		public function SetMode($power, $mode)
			{
			if ($mode<2) 		// nur Automatisch und Manuell weitergeben
				{
				$setMode=$mode;
				if ($this->rpcADR==Null)
					{
					echo "   IPSComponent HeatSet_Homematic SetMode von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Level ".$setlevel." Power ".($power?'On':'Off')." \n";
            	    IPSLogger_Inf(__file__, "IPSComponent HeatSet_Homematic SetMode von ".IPS_GetName($this->instanceId)." mit folgenden Parametern Mode ".$setMode." Power ".($power?'On':'Off')); 
					HM_WriteValueInteger($this->instanceId, "CONTROL_MODE", $setMode);
					}
				else
					{
					//echo "   IPSComponent HeatSet_Homematic SetState mit folgenden Parametern rpc Adresse:".$this->rpcADR."und Level ".$level." Power ".($power?'On':'Off')." \n";
					$rpc = new JSONRPC($this->rpcADR);
					$rpc->HM_WriteValueInteger($this->instanceId, "CONTROL_MODE", $setMode);
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