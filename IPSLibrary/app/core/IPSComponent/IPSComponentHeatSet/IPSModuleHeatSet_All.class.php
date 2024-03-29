<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSModuleSensor_HeatSet_All.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

	/**
	 * @class IPSModule_HeatSet_All
	 *
	 * Definiert ein IPSModuleSensor Object, das als Wrapper für Sensoren in der IPSLibrary
	 * verwendet werden kann.
	 *
	 */

	IPSUtils_Include ('IPSModuleHeatSet.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentHeatSet');
	
	//IPSUtils_Include ('Stromheizung_Configuration.inc.php', 'IPSLibrary::config::modules::Stromheizung');
	
	class IPSModuleHeatSet_All extends IPSModuleHeatSet {

		private $instanceId;
		private $movementId;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_IPSShadowing Objektes
		 *
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param boolean $movementId Movement Command
		 */
		public function __construct($var1=null,$var2=null,$var3=null)
			{
			if ($var1 != null) echo "construct IPSModuleHeatSet_All with parameter ".$var1."  ".$var2."  ".$var3."\n";
			//$this->instanceId = IPSUtil_ObjectIDByPath($instanceId);
			//$this->movementId = $movementId;
			}
	
		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation der aktuellen Position der Beschattung
		 *
		 * @param string $position Aktuelle Position der Beschattung (Wertebereich 0-100)
		 */
		public function SyncPosition($position, IPSComponentHeatSet $componentToSync) 
			{
			IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");
            if ($position>6) { $state=true; } else { $state=false; }  // state automatisch anhand Position setzen
			echo "IPSModuleHeatSet_All HandleEvent SyncPosition mit Wert ".$position."\n";
			IPSLogger_Dbg(__file__, 'HandleEvent: SyncPosition mit Wert '.$position);			
			$componentParamsToSync = $componentToSync->GetComponentParams();
			$deviceConfig          = IPSHeat_GetHeatConfiguration();
			foreach ($deviceConfig as $deviceIdent=>$deviceData) 
				{
				$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);
				$componentParamsConfig = $componentConfig->GetComponentParams();
				$componentParamsConfig1=(string)explode(",",$componentParamsConfig)[1];
				$componentParamsToSync1=(string)explode(",",$componentParamsToSync)[1];
				echo "   Comparing \"$componentParamsConfig1\" with target \"$componentParamsToSync1\"\n";	/* nur die OID vergleichen reicht, sonst gibt es Probleme mit RemoteAccess Daten */
				if ( ($componentParamsConfig1==$componentParamsToSync1) && ($componentParamsConfig1!="") )
					{
					echo "Parameter to Sync found : $componentParamsToSync1 \n";
					$lightManager = new IPSHeat_Manager();
					$lightManager->SynchronizePosition($deviceIdent, $state, $position);				
					}
				}
			}

		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation der aktuellen Stelltemperatur des Thermostaten
         * in der Configuration muss neben dem Wert immer auch eine OID übergeben werden, anhand dieser kann synchronisiert werden
         * ähnliche Implementierung wie SyncState für Switches, nachdem es nur eine IPSHeat Funktion ist erfolgt keine IPSLight Abfrage mehr
		 *
		 * @param string $position Aktuelle Position der Beschattung (Wertebereich 0-100)
		 */
		public function SyncSetTemp($position, IPSComponentHeatSet $componentToSync, $debug=false) 
			{
			IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");
			if ($debug) echo "IPSModuleHeatSet_All HandleEvent SyncSetTemp mit TempWert ".$position."\n";
			IPSLogger_Inf(__file__, 'IPSModuleHeatSet_All HandleEvent: SyncSetTemp mit TempWert '.$position);	
			    
            $componentParamsToSyncCheck = $componentToSync->GetComponentParams();                                    // only for error check, can be removed later on 
    		$componentParamsToSync = explode(",",$componentParamsToSyncCheck);
            //print_r($componentParamsToSync);
			$deviceConfig          = IPSHeat_GetHeatConfiguration();    // die ganze Configuration durchgehen, jedes Objekt mit new anlegen und Parameter auslesen
			foreach ($deviceConfig as $deviceIdent=>$deviceData) 
				{
                //if ($debug) echo "   ---- create new Component ".$deviceData[IPSHEAT_COMPONENT]." und dann getComponentParams aufrufen.\n";
				$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);            
				$componentParamsConfigCheck = $componentConfig->GetComponentParams();                                // only for error check, can be removed later on
				$componentParamsConfig=explode(",", $componentParamsConfigCheck);
   				//if ($debug) echo "   Comparing fom configuration source \"$componentParamsConfigCheck\" with this target \"$componentParamsToSyncCheck\"\n";	/* nur die OID vergleichen reicht, sonst gibt es Probleme mit RemoteAccess Daten */
				if ( (isset($componentParamsConfig[1])) && (isset($componentParamsToSync[1])) )
                    {
    				if ($debug) echo "   Comparing \"$componentParamsConfig[1]\" with target \"$componentParamsToSync[1]\"\n";	/* nur die OID vergleichen reicht, sonst gibt es Probleme mit RemoteAccess Daten */
                    if ( ($componentParamsConfig[1]==$componentParamsToSync[1])  && ($componentParamsConfig[1] != "") ) 
                        {
                        echo "   *****SyncSetTemp Parameter to Sync in Heat/Light Configuration found : $deviceIdent with $position because ".$componentParamsToSync[1]." in \"".json_encode($componentParamsToSyncCheck)."\"\n";
                        IPSLogger_Inf(__file__,"IPSModuleHeatSet_All SyncSetTemp Parameter to Sync found for $deviceIdent with $position because ".$componentParamsToSync[1]." is in \"".json_encode($componentParamsToSyncCheck)."\"");
                        $lightManager = new IPSHeat_Manager();
                        $lightManager->SynchronizeSetTemp($deviceIdent, $position);				
                        }
                    }
				}
			}

		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation der aktuellen Betriebsart des Thermostaten
         * in der Configuration muss neben dem Wert immer auch eine OID übergeben werden, anhand dieser kann synchronisiert werden
		 *
		 * @param string $position Aktuelle Position der Beschattung (Wertebereich 0-100)
		 */
		public function SyncSetMode($position, IPSComponentHeatSet $componentToSync) 
			{
			IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");
			echo "IPSModuleHeatSet_All HandleEvent SyncSetMode mit Wert ".$position."\n";
			IPSLogger_Inf(__file__, 'HandleEvent: SyncSetMode mit Wert '.$position);			
			$componentParamsToSync = explode(",",$componentToSync->GetComponentParams());
            //print_r($componentParamsToSync);
			$deviceConfig          = IPSHeat_GetHeatConfiguration();
			foreach ($deviceConfig as $deviceIdent=>$deviceData) 
				{
				$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);
				$componentParamsConfig = explode(",",$componentConfig->GetComponentParams());
				//echo "   Comparing \"$componentParamsConfig1\" with target \"$componentParamsToSync1\"\n";	/* nur die OID vergleichen reicht, sonst gibt es Probleme mit RemoteAccess Daten */
				if ( (isset($componentParamsConfig[1])) && (isset($componentParamstoSync[1])) && ( ($componentParamsConfig[1]==$componentParamsToSync[1])  && ($componentParamsConfig1 != "") ) )
					{
					echo "   SyncSetMode, Parameter to Sync in Heat/Light Configuration found : $deviceIdent ".$componentParamsToSync[1]."\n";
                    IPSLogger_Inf(__file__,"SyncSetMode, Parameter to Sync found for $deviceIdent because ".$componentParamsToSync[1]);
					$lightManager = new IPSHeat_Manager();
					$lightManager->SynchronizeSetMode($deviceIdent, $position);				
					}
				}
			}
	
		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation einer Beleuchtung zu IPSLight
		 *
		 * @param string $state Aktueller Status des Switch
		 */
		public function SyncState($value, IPSComponentHeatSet $componentToSync) 
			{
			//echo "HandleEvent: SyncState mit Wert ".$value."\n";
			IPSLogger_Dbg(__file__, 'HandleEvent: SyncState mit Wert '.$value);
			if ($value>6) { $state=true; } else {$state=false; }
			$componentParamsToSync = $componentToSync->GetComponentParams();
			$deviceConfig          = IPSHeat_GetHeatConfiguration();
			//print_r($deviceConfig);
			/* alle Thermostate der Reihe nach durchgehen, Gliederung ist nach Name des Objektes */
			foreach ($deviceConfig as $deviceIdent=>$deviceData) 
				{
				echo "     Bearbeite ".$deviceIdent.":\n";
				$componentConfig       = IPSComponent::CreateObjectByParams($deviceData[IPSHEAT_COMPONENT]);
				$componentParamsConfig = $componentConfig->GetComponentParams();
				echo "     Vergleiche ".$componentParamsConfig."   ".$componentParamsToSync."\n";
				if ($componentParamsConfig==$componentParamsToSync) 
					{
					$lightManager = new IPSHeat_Manager();
					$lightManager->SynchronizeSwitch($deviceIdent, $state);
					}
				}
			echo "\nEnde SyncState.\n";	
			}
			
		/**
		 * @public
		 *
		 * Ermöglicht die Synchronisation von Sensorwerten mit Modulen
		 *
		 * @param string $value Sensorwert
		 * @param IPSComponentSensor $component Sensor Komponente
		 */
		public function SyncButton($value, IPSComponentHeatSet $component) {
			$this->ExecuteButton();
		}

		/**
		 * @public
		 *
		 * Ermöglicht das Verarbeiten eines Taster Signals
		 *
		 */
		public function ExecuteButton () {
			$device = new IPSShadowing_Device($this->instanceId);
			$movementId = GetValue(IPS_GetObjectIDByIdent(c_Control_Movement, $this->instanceId));
			if ($movementId==c_MovementId_MovingIn or $movementId==c_MovementId_MovingOut or $movementId==c_MovementId_Up or $movementId==c_MovementId_Down) {
				$device->MoveByControl(c_MovementId_Stop);
			} else {
				$device->MoveByControl($this->movementId);
			}
		}

	}

	/** @}*/
?>