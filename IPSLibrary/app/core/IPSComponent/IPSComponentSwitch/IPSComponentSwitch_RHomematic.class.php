<?
	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */

    /**
     * @class IPSComponentSwitch_Homematic
     *
     * Definiert ein IPSComponentSwitch_RHomematic Object, das ein IPSComponentSwitch Object für Homematic implementiert.
     *
     * Wird von IPSLight oder IPSHeat aufgerufen und schaltet einen lokalen Homematic Schalter. 
	  * oder wird über den Messagehandler aufgerufen, wenn sich ein Status aendert
	  *
     *  construct übernimmt Parameter aus Konfiguration von IPSLight/IPSHeat und CustomComponent
     *  HandleEvent schreibt auf externe Logging Server und synchronisiert den lokalen Status (syncState)
     *  SetState und GetState schalten die lokale Instanz
     * 
     * Auftrennung der unterschiedlichen Klassen und Module:
     *  IPSComponentSwitch_Homematic    Standard ohne Extras, das ist die Grundfunktionalitaet
     *  IPSComponentSwitch_XHomematic   Schaltet eine Remote Variable ohne remote Logging
     *  IPSComponentSwitch_RHomematic   Schaltet eine lokale Homematic Variable mit remote Logging 
     *  IPSComponentSwitch_Remote       Ändert eine lokale Variable mit remote Logging    
     *
     * Die RemoteAccessClass muss immer mit IPSLight zusammenpassen. Auch der Aufruf von construct.
     * Anstelle von IPSComponentSwitch_Remote sollte die spezielle Remote Homematic Klasse IPSComponentSwitch_RHomematic verwendet werden.
     * Bei IPSLight die Konfiguration richtig setzen - das Logging am ersten Parameter kann freibleiben
     *
     * RemoteAccessServerTable() ist in AllgemeineDefinitionen definiert und ermittelt die Konfiguration auf Basis des Status der Logging Server. Status Erreichbarkeit wird von OperationCenter erfasst.
     *
     * Nach Construct wird HandleEvent mit dem neuen Status aufgerufen. Zuerst wird vom konfigurierten Modul aus Sync State aufgerufen.
     * dann das lokale und das remote Logging.
     *
     * Die Änderung des Schalters könnte als Event wieder gelogged werden.
     * Wird von IPSComponentSwitch_Remote erledigt. Zuordnung erfolgt in remoteAccess Modul:
     *
	 * Events werden im Event Handler des IPSMessageHandler registriert. Bei Änderung oder Update wird der Event Handler aufgerufen.
	 * In der IPSMessageHandler Config steht wie die Daten Variable ID und Wert zu behandeln sind. Es wird die Modulklasse und der Component vorgegeben.
	 * 	xxxx => array('OnChange','IPSComponentSensor_Remote,','IPSModuleSensor_Remote,1,2,3',),
	 * Nach Angabe des Components und des Moduls sind noch weitere Parameter (1,2,3) möglich, genutzt wenn RemoteAccess installiert ist:
	 * der erste Zusatzparameter aus der obigen Konfig sind Pärchen von Remoteserver und remoteOIDs
	 * in der RemoteAccessServerTable sind alle erreichbaren Log Remote Server aufgelistet, abgeleitet aus der Server Config und dem Status der Erreichbarkeit
	 * für alle erreichbaren Server wird auch die remote OID mit dem Wert beschrieben 
     *
	 * Es wird zuerst der construct mit den obigen weiteren Config Parametern und dann HandleEvent mit VariableID und Wert der Variable aufgerufen.
	 *
	 * Hier, da für IPSLight im Einsatz erfolgt ein allgemeines Handling, Klasse macht kein lokales Logging und auch keine weitere Verarbeitung
	 *
     * Es gibt eine normale Homematic Switch Klasse. Damit wird nur das lokale Homematic Objekt geschaltet.
     * Sensoren übertragen den Wert auch auf einen oder mehrere Logging Server 
	 *
     *
     * @author Wolfgang Joebstl, inspiriert von Andreas Brauneis
     * 
     ****/


	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');

    /* Erweiterung zur Homematic Switch Class */
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
	IPSUtils_Include ('IPSComponentSwitch_Remote.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');		// für Switch_Logging		
		
	IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");

	class IPSComponentSwitch_RHomematic extends IPSComponentSwitch {

		private $instanceId;
		private $supportsOnTime;
		private $remServer;	                /* Erweiterung zur Homematic Switch Class */
		private $ErrorHandlerAltID	;       /* ErrorHandler alt für Abfangen des Duty Cycle Events */
		private $remoteOID;
	
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_RHomematic Objektes
		 *
         * @param string $var1 sind die Remote Server Name:ROID Pärchen, wenn sie nicht benannt werden, ignorieren und zweiten Parameter als ersten Parameter nehmen
		 * @param integer $instanceId InstanceId des Homematic Devices
		 * @param integer $supportsOnTime spezifiziert ob das Homematic Device eine ONTIME unterstützt
		 */
		public function __construct($var1, $instanceId=false, $supportsOnTime=true) 
			{
			//echo "Construct IPSComponentSwitch_RHomematic mit \"".$var1."\"   \"".$instanceId."\"   \"".$supportsOnTime."\"\n";
			if (($instanceId===false) || ($instanceId==""))  
				{
				$this->instanceId   = IPSUtil_ObjectIDByPath($var1);
				$this->remoteOID    = "";
				//echo "    InstanceID war nicht definiert, hier festgelegt mit ".$this->instanceId ." \n";
				}
			else 
				{
				//$this->instanceId     = IPSUtil_ObjectIDByPath($instanceId);
    			//$this->RemoteOID    = $var1;
				$this->instanceId     = IPSUtil_ObjectIDByPath($var1);
    			$this->remoteOID    = $instanceId;
            	}
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
   		    /* verbiegen des Error Handlers um Duty Cycle Events abzufangen, der alte Error_Handler wird als Variable zu AllgemeneDefinitionen zwischengespeichert   
        	$mManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        	$AllgemeineDefId     = IPS_GetObjectIDByName('AllgemeineDefinitionen',$mManager->GetModuleCategoryID('data'));
            $this->ErrorHandlerAltID = CreateVariableByName($AllgemeineDefId, "ErrorHandler", 3);
            $alter_error_handler = set_error_handler("AD_ErrorHandler");
            SetValue($this->ErrorHandlerAltID,$alter_error_handler);                			
            */
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
			echo "IPSComponentSwitch_RHomematic:HandleEvent für VariableID : ".$variable." (".IPS_GetName($variable).") mit Wert : ".($value?"Ein":"Aus")." \n";
	   	    //IPSLogger_Inf(__file__, 'HandleEvent: IPSComponentSwitch_RHomematic: HandleEvent für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.($value?"Ein":"Aus"));			
            $startexec=microtime(true);
			$module->SyncState($value, $this);
            echo "Aktuelle Laufzeit nach SyncState ".exectime($startexec)." Sekunden.\n";        
			$log=new Switch_Logging($variable);         		//echo "Logging.\n";
			$result=$log->Switch_LogValue();        			//echo "Logging Done !\n";
            echo "Aktuelle Laufzeit nach LogValue ".exectime($startexec)." Sekunden.\n";        
            $log->RemoteLogValue($value, $this->remServer, $this->remoteOID );
            echo "Aktuelle Laufzeit nach RemoteLogValue ".exectime($startexec)." Sekunden.\n";        
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

        /* return Logging class, shall be stored */

		public function GetComponentLogger() 
			{
            return "";
            }


		/**
		 * @public
		 *
		 * Zustand Setzen , es wird von IPS-Light über das Webfront direkt diese Routine aufgerufen
         * Daher das umbiegen des Errorhandlers hier und im construct machen.
		 *
		 * @param boolean $value Wert für Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll
		 */
		public function SetState($value, $onTime=false) 
            {
            //echo "Aufruf SetState fuer ".$this->instanceId." (".IPS_GetName($this->instanceId).") mit Wert ".($value ? "true":"false")." und Ontime Wert ".($onTime ?:"false")."   \n";
   			if ($onTime!==false and $value and $this->supportsOnTime===true) HM_WriteValueFloat($this->instanceId, "ON_TIME", $onTime);  
			$state=@HM_WriteValueBoolean($this->instanceId, "STATE", $value);
            if ($state==false)
                {
				echo "Aufruf SetState fuer ".$this->instanceId." (".IPS_GetName($this->instanceId).") mit Wert ".($value ? "true":"false")." und Ontime Wert ".($onTime ?:"false")."   \n";
                echo "Fehler beim Setzen des Homematic Registers. 5 Sekunden warten. ".date("H:i:s")."\n";
                sleep(5);   /* 5 Sekunde warten und noch einmal */
    			$state=HM_WriteValueBoolean($this->instanceId, "STATE", $value);
                if ($state==false)
                    {
                    echo "Erneuter Fehler beim Setzen des Homematic Registers. 15 Sekunden warten.".date("H:i:s")."\n";
                    sleep(15);   /* 15 Sekunden warten und noch einmal */
    			    $state=HM_WriteValueBoolean($this->instanceId, "STATE", $value);
                    if ($state==false) echo "Wieder Fehler beim Setzen des Homematic Registers. Abbruch.".date("H:i:s")."\n";
                    }
                }

            $this->updateStatusGroup($this->instanceId);

            if ($this->ErrorHandlerAltID>0)
                {
                echo "Zurückstellen des Error Handlers, Info in Variable zu AllgemeneDefinitionen zwischengespeichert in : ".$this->ErrorHandlerAltID." \n";    
                $alter_error_handler=GetValue($this->ErrorHandlerAltID);                  
                set_error_handler($alter_error_handler);
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

        /*********************
         *
         *  IPS-Light Status Variablen in Kategorie Data korrigieren, wenn Homematic einen Fehler macht und das Spiegelregister für Gruppe und Status auf den richtigen Wert stellen
         *
         *****************************************/

        public function updateStatusGroup($instanceID)
            {
            /* ganze IPSHeat oder IPSLight Konfiguration durchgehen und HomematicInstanz suchen */
    	    if (function_exists("IPSHeat_GetHeatConfiguration")) 
				{
				$lightConfig  = IPSHeat_GetHeatConfiguration();
	            $baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');
    	        $switchCategoryId  = IPS_GetObjectIDByIdent('Switches', $baseId);
        	    $groupCategoryId   = IPS_GetObjectIDByIdent('Groups', $baseId);
            	$programCategoryId = IPS_GetObjectIDByIdent('Programs', $baseId); 
				}
    	    else 
				{
				$lightConfig  = IPSLight_GetLightConfiguration();
            	$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.IPSLight');
        	    $switchCategoryId  = IPS_GetObjectIDByIdent('Switches', $baseId);
    	        $groupCategoryId   = IPS_GetObjectIDByIdent('Groups', $baseId);
	            $programCategoryId = IPS_GetObjectIDByIdent('Programs', $baseId); 
				}

            //print_r($lightConfig);
            foreach ($lightConfig as $switchName=>$deviceData) 
                {
    			$switchId      = IPS_GetVariableIDByName($switchName, $switchCategoryId);
                $componentConfig=explode(",",$deviceData[3]);
                if ($componentConfig[0]=="IPSComponentSwitch_RHomematic") 
                    {
                    if ($componentConfig[1]==$instanceID)
                        {
                        $homematicID=IPS_GetVariableIDByName('STATE', $componentConfig[1]);
                        //echo "    ".$switchId."  (".IPS_GetName($switchId).") Wert ".(GetValue($switchId)?"Ein":"Aus")."  Homematic Wert : ".(GetValue($homematicID)?"Ein":"Aus")."  \n";
                        if ( GetValue($switchId) != GetValue($homematicID) ) 
                            {
                            //echo "                 --> Wert angepasst.\n";
                            SetValue($switchId, GetValue($homematicID));
                            }
                        }
                    }
                }

            if (false)
                {
        	$groupName    = IPS_GetName($groupId);    
		    $groupState   = GetValue($groupId);
		    foreach ($lightConfig as $switchName=>$deviceData) 
                {
    			$switchId      = IPS_GetVariableIDByName($switchName, $switchCategoryId);
	    		$switchInGroup = array_key_exists($groupName, array_flip(explode(',', $deviceData[1])));
                $componentConfig=explode(",",$deviceData[3]);
			    if ( $switchInGroup ) 
                    {
                    if ($componentConfig[0]=="IPSComponentSwitch_RHomematic") 
                        {
                        $homematicID=IPS_GetVariableIDByName('STATE', $componentConfig[1]);
                        echo "    ".$switchId."  (".IPS_GetName($switchId).") Wert ".(GetValue($switchId)?"Ein":"Aus")."  Homematic Wert : ".(GetValue($homematicID)?"Ein":"Aus")."  \n";
                        if ( (GetValue($homematicID)<>$groupState) )
                            {
                            SetValue($groupId,GetValue($homematicID));
                            SetValue($switchId, GetValue($homematicID));
                            //IPSLight_SetSwitchByName($switchName,$groupState);
                            }                    
                        }
                    else 
                        {
                        echo "    ".$switchId."  (".IPS_GetName($switchId).") Wert ".(GetValue($switchId)?"Ein":"Aus")."   ".$componentConfig[0]."   ".$componentConfig[1]."\n";
                        if ( (GetValue($switchId)<>$groupState) )
                            {
	    	    		    //SetValue($switchId, $groupState);
                            //IPSLight_SetSwitchByName($switchName,$groupState);
                            }
                        }
	    			}
                }
                } /* false */
            }


	}

	/** @}*/
?>