<?php
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
	
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentSwitch_Remote.class.php
	 * @author        Wolfgang Jöbstl inspiriert durch Andreas Brauneis
	 *
	 *
	 */

	/******************************
	 *
     * Definiert ein IPSComponentSwitch_Remote Object, das ein IPSComponentSwitch Object für Homematic, Hue und Shelly implementiert.
     * es handelt sich um ein Actuator Set, das heisst der Component wird geschaltet, für HUEV2 mit SwitchRGB, oder auch HeatSet für Temperatur
     * im Normalfall sind für die Beeinflussing des Actuators spezifische Befehle notwendig, mittlerweile 
     *
     * Wird von IPSLight oder IPSHeat aufgerufen und schaltet einen lokalen Homematic, Hue oder Shelly Schalter. 
	 * oder wird über den Messagehandler aufgerufen, wenn sich ein Status aendert
     * für die Energiemesswerte gibt es einen zweiten unabhängigen Component
     *
     * die Status VariableID ist als Event des IPS_MessageHandlers script registriert
     * bei Änderung der Variable wird das script und damit als class MessageHandler das HandleEvent aufgerufen
     * HandleEvent des MessageHandler ruft entsprechend Configuration IPSMessageHandler_Configuration das HandleEvent des in der config eingestellten Components auf.
     * Eintrag in der Configuration dafür zB: array('OnChange','IPSComponentSensor_Remote,par1,par2,par3','IPSModuleSensor_IPSHeat,par1,par2,par3',),
     * Entsprechend HandleEvent Script wird jetzt als Teil der classes der IPSComponentSensor_Temperatur und der IPSModuleSensor_Temperatur aufgerufen.
     *          $component = IPSComponent::CreateObjectByParams($params[1]);            => new IPSComponentSensor_Temperatur(par1,par2,par3)
	 *			$module    = IPSLibraryModule::CreateObjectByParams($params[2]);        => new IPSModuleSensor_Temperatur(par1,par2,par3)
	 *		    $component->HandleEvent($variable, $value, $module);
     *
     * als Beispiele  ein Taster Sensor, ein veralteter Parameter für einen Homematic Switch und eine moderne Konfiguration
     *      array('OnChange','IPSComponentSensor_Remote,39578,LBG70-2Virt:23566;,BUTTON','IPSModuleSensor_Remote,',),
     *      array('OnChange','IPSComponentSwitch_Homematic,14368','IPSModuleSwitch_IPSHeat,',)
     *      array('OnChange','IPSComponentSwitch_RHomematic,37228,LBG70-2Virt:11608;,STATE','IPSModuleSwitch_IPSHeat,',),
     *
     * sicherheitshalber eine automatische Zuordnung:
     * automatische Zuordnung, weil immer etwas falsch ist, alle Varianten, auch historische unterstützt
     *      instance,ROID,typedev
     *      ROID,instance,typedev
     *      ROID,typedev      
     *
     * in component::HandleEvent ist dann immer
     *      ein Debug Printout
     *      eine Logging class                                  =>   $log=new Temperature_Logging($variable); $result=$log->Temperature_LogValue();
     *      und das loggen auf einer remote Instanz mit         =>   $rpc->SetValue($roid, $value);
     * Im selben script sind die class Temperature_Logging extends Logging untergebracht.     
	 *
     *  construct übernimmt Parameter aus Konfiguration von IPSLight/IPSHeat und CustomComponent
     *  HandleEvent schreibt auf externe Logging Server und synchronisiert den lokalen Status (syncState)
     *  SetState und GetState schalten die lokale Instanz, hier brauchen wir die Instanz, damit das Hardware Device Modul bekannt ist
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
	 * Für jede Variable die gelogged wird erfolgt ein Eintrag ins config File IPSMessageHandler_Configuration
	 * Es wird ein Event erzeugt dass bei Änderung der Variable HandleEvent mit VariableID udn Wert aufruft.
     *
     * Unterschied zu IPSComponentSwitch_RHomematic :
	 *   __construct
     *      zusaetzliche Variablen für instanceID und SupportsonTime
     *      Verbiegen des DutyCycle Error Handlers um nicht Erreichbarkeits Events etc abzufangen
     *
	 *  class Switch_Logging extends Logging wird hier für beide definiert
     *
     *
	 ****************************************/

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('IPSComponent.class.php', 'IPSLibrary::app::core::IPSComponent');
	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');
	
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
	
    IPSUtils_Include ('IPSComponentSwitch_Remote.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');		// für Switch_Logging	


	class IPSComponentSwitch_Remote extends IPSComponentSwitch {

		private $installedmodules;

        private $instanceId=false;
		private $supportsOnTime=false;
        private $typedev="STATE";
		private $remServer;	                /* Erweiterung zur Homematic Switch Class */
		private $remoteOID=false;	

		private $ErrorHandlerAltID=false	;       // ErrorHandler alt für Abfangen des Duty Cycle Events 	

		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_Remote Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
         *
         * Andere Reihenfolge bei Remote und Temperatur: $instanceId=null, $remoteOID=null, $tempValue=null     hier        $remoteOID=null, $instanceId=null, $tempValue=null 
         *      Var1            instance        tempObject
         *      lightObject     ROID            RemoteOID
         *      lightValue      typedev         tempValue
         *
         * automatische Zuordnung, weil immer etwas falsch ist, auch unterstützt
         *      ROID,instance,typedev
         *      ROID,typedev         
		 * @param $var1   OID der STATE Variable des Schalters
		 */
		public function __construct($par1=false,$par2=false,$par3=false) 
			{
            $pars=array();          // Zuordnung nach Typ, nicht zugeordnete bleiben false
            if ($par1) $pars[]=$par1;
            if ($par2) $pars[]=$par2;
            if ($par3) $pars[]=$par3;
            foreach ($pars as $par)
                {
                if (strpos($par,":") !== false) $this->RemoteOID = $par;                // ROID ist par da ein : gefunden wurde
                elseif (is_numeric($par)) $this->instanceId = $par;
                elseif (is_string($par)) $this->typedev = $par;                                    
                else echo "IPSComponentSwitch_Remote, warning parameter $par not identified\n";
                }

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


		/*
		 * aktueller Status der remote logging server
		 */	
	
		public function remoteServerAvailable()
			{
			return ($this->remServer);			
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
			echo "IPSComponentSwitch_Remote:HandleEvent für VariableID : ".$variable." (".IPS_GetName($variable).") mit Wert : ".($value?"Ein":"Aus")." \n";
	   	    //IPSLogger_Inf(__file__, 'HandleEvent: IPSComponentSwitch_RHomematic: HandleEvent für VariableID '.$variable.' ('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.($value?"Ein":"Aus"));			
            $startexec=microtime(true);
			$module->SyncState($value, $this);
            echo "Aktuelle Laufzeit nach SyncState ".exectime($startexec)." Sekunden.\n";        
			$log=new Switch_Logging($variable);         		//echo "Logging.\n";
			$result=$log->Switch_LogValue();        			//echo "Logging Done !\n";
            echo "Aktuelle Laufzeit nach LogValue ".exectime($startexec)." Sekunden.\n";        
            $log->RemoteLogValue($value, $this->remServer, $this->remoteOID );
            echo "Aktuelle Laufzeit nach RemoteLogValue ".exectime($startexec)." Sekunden.\n";  

			//echo "Switch Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
	   		IPSLogger_Dbg(__file__, 'HandleEvent: Switch Message Handler für VariableID '.$variable.' mit Wert '.$value);			
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
         * verwendet Homematic spezifische Befehle
         * updateStatusGroup deaktiviert, SetState wird eh von der Stromheizung IPSHeat Managaer aufgerufen
		 *
		 * @param boolean $value Wert für Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll
		 */
		public function SetState($value, $onTime=false,$debug=false) 
            {
            $moduleName=false;
            if ($this->instanceId>0)
                {
                $objectType=IPS_GetObject($this->instanceId)["ObjectType"];
                if ($objectType==1) 
                    {
                    $moduleInfo=IPS_GetInstance($this->instanceId)["ModuleInfo"];
                    $moduleID=$moduleInfo["ModuleID"];
                    $moduleName=$moduleInfo["ModuleName"];
                    }
                if ($debug) echo "       IPSComponentSwitch_Remote::SetState, Aufruf fuer ".$this->instanceId." (".IPS_GetName($this->instanceId)."), Module: $moduleName, mit Wert ".($value ? "true":"false")." und Ontime Wert ".($onTime ?:"false")."   \n";
                switch ($moduleName)
                    {
                    case "HomeMatic CCU Device":
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
                        break;
                    case "ShellyComponent":             // verwendet ActionRequest, ShellyDevice nicht unterstützt
                        $cids=IPS_GetChildrenIDs($this->instanceId);
                        foreach($cids as $cid)              // eigentlich brauche ich auch den Namen der Variable, kann alles sein, steht nur in Devicelist
                            {
                            /* gleiche Erkennung wie in getDevicelist, suche nach Status am Anfang des Namen, Zahlen dahinter ignorieren, 
                             * Alternativ gibt es einen Identifier, aber immer noch nicht klar welche Zahl, in einem Component ist eh immer nur ein Switch
                             * Weiters könnte man auch nach der eintzigen Variable mit einer hinterlegten Action suchen
                             */
                            $name = IPS_GetName($cid);
                            $ident = IPS_GetObject($cid)["ObjectIdent"];             // ObjectIdent
                            $pos1=strpos($name,"Status");
                            if ($pos1===0) 
                                {
                                echo "         Found $cid $name $ident , now RequestAction \n";
                                RequestAction($cid,$value);
                                break;
                                }
                            }
                        break;
                    default:
                        echo "unknown $moduleName \n";
                        break;
                    }
                $this->updateStatusGroup($this->instanceId,$debug);                 // das ist nur ein Check wenn requestAction scheitert, war wichtig für Homematic
                }
            else echo "Waring, IPSComponentSwitchRemote::SetState called without instance \n";
            if ($this->ErrorHandlerAltID>0)
                {
                echo "Zurückstellen des Error Handlers, Info in Variable zu AllgemeneDefinitionen zwischengespeichert in : ".$this->ErrorHandlerAltID." \n";    
                $alter_error_handler=GetValue($this->ErrorHandlerAltID);                  
                //set_error_handler($alter_error_handler);
                }
		    }


        /*********************
         *
         *  IPS-Light Status Variablen in Kategorie Data korrigieren, wenn Homematic einen Fehler macht und das Spiegelregister für Gruppe und Status auf den richtigen Wert stellen
         *
         *****************************************/

        public function updateStatusGroup($instanceID,$debug=false)
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
            echo "updateStatusGroup, check Stromheizung ComponentConfig for $instanceID  \n";
            foreach ($lightConfig as $switchName=>$deviceData) 
                {
    			$switchId      = IPS_GetVariableIDByName($switchName, $switchCategoryId);
                $componentConfig=explode(",",$deviceData[3]);
                if ($componentConfig[0]=="IPSComponentSwitch_RHomematic") 
                    {
                    if ($componentConfig[1]==$instanceID)
                        {
                        $homematicID=IPS_GetVariableIDByName('STATE', $componentConfig[1]);
                        echo "    ".$switchId."  (".IPS_GetName($switchId).") Wert ".(GetValue($switchId)?"Ein":"Aus")."  Homematic Wert : ".(GetValue($homematicID)?"Ein":"Aus")."  \n";
                        if ( GetValue($switchId) != GetValue($homematicID) ) 
                            {
                            echo "                 --> Wert angepasst.\n";
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

	/********************************* 
	 *
	 * Klasse überträgt die Werte an einen remote Server und schreibt lokal in einem Log register mit
     * IPSComponentSwitch_Remote war Teil des Includes für RHomematic
	 *
	 * legt dazu zwei Kategorien im eigenen data Verzeichnis ab
	 *
	 * xxx_Auswertung und xxxx_Nachrichten
	 *
	 * in Auswertung wird eine lokale Kopie aller Register angelegt und archiviert. 
	 * in Nachrichten wird jede Änderung als Nachricht mitgeschrieben 
     *
     * im construct die beiden zusätzlichen Werte wegen Kompatibilität zu zB Temperature_Logging
     *
     * teilweise umgestellt auf vergleichbare Routinen mit
     *      constructFirst
     *      do_init noch offen, $variableTypeReg="Switch" bereits vorbereitet
	 *
	 **************************/

	class Switch_Logging extends Logging
		{
		//private $variable, $variableLogID;

		//private $SwitchAuswertungID;
		//private $SwitchNachrichtenID;

		// $configuration, $variablename, $CategoryIdData

		protected $installedmodules;              /* installierte Module */
        protected $DetectHandler;		        /* Unterklasse */        
        protected $archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */          
				
		function __construct($variable,$variablename=Null,$variableTypeReg="Switch",$debug=false)
			{
            if ( ($this->GetDebugInstance()) && ($this->GetDebugInstance()==$variable) ) $this->debug=true;
            else $this->debug=$debug;
            if ($this->debug) echo "   Switch_Logging, construct : ($variable,$variablename,$variableTypeReg).\n";

            $this->constructFirst();        // sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()


            /************** abgelöst durch constructFirst
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();   
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     //   <--- change here 
			$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            */

            //$NachrichtenID=$this->do_init($variable,$variablename,null, $variableTypeReg, $this->debug);              // $typedev ist $variableTypeReg, $value wird normalerweise auch übergeben, $variable kann auch false sein

            /* abgelöst durch do_init und do_init_switch */
            $dosOps= new dosOps();

			//echo "Construct IPSComponentSswitch_Remote Logging for Variable ID : ".$variable."\n";
			$result=IPS_GetObject($variable);
			$this->variablename=IPS_GetName((integer)$result["ParentID"]);			// Variablenname ist immer der Parent Name 
		
			// Create Category to store the Move-LogNachrichten und Spiegelregister	
			$this->SwitchNachrichtenID=$this->CreateCategoryNachrichten("Switch",$this->CategoryIdData);
			$this->SwitchAuswertungID=$this->CreateCategoryAuswertung("Switch",$this->CategoryIdData);;
			echo "  Switch_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   ".IPS_GetName($this->CategoryIdData)." anlegen : [".$this->SwitchNachrichtenID.",".$this->SwitchAuswertungID."]\n";

			// lokale Spiegelregister aufsetzen 
			if ($variable<>null)
				{
		        $this->variable=$variable;   
				echo "      Lokales Spiegelregister als Boolean auf ".$this->variablename." ".$this->SwitchAuswertungID." ".IPS_GetName($this->SwitchAuswertungID)." anlegen.\n";
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->SwitchAuswertungID,0,'~Switch');                   // $this->variableLogID schreiben
				}

			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
            $directory=$this->configuration["LogDirectories"]["SwitchLog"];
            $dosOps= new dosOps();
            $dosOps->mkdirtree($directory);
            // str_replace(array('<', '>', ':', '"', '/', '\\', '|', '?', '*'), '', $logfile);             // alles wegloeschen das einem korrekten Filenamen widerspricht, Logging:construct macht keine weitere Bearbeitung mehr, da hier schon die Verzeichnisse dabei sind
            $this->filename=$directory.str_replace(array('<', '>', ':', '"', '/', '\\', '|', '?', '*'), '', $this->variablename)."_Switch.csv";   

			/* im do_init oder gerade hier oben besser
            $directories=get_IPSComponentLoggerConfig();                                // Log verzeichnis richtig einordnen
			if (isset($directories["LogDirectories"]["SwitchLog"]))
		   		 { $directory=$directories["LogDirectories"]["SwitchLog"]; }
			else {
                $directory="Switch/"; 	
                $systemDir     = $dosOps->getWorkDirectory();
                $directory=$systemDir.$directory;
                }
            echo "      Erzeuge Verzeichnis: ".$directory."\n";
            $dosOps->mkdirtree($directory);
			$filename=$directory.$this->variablename."_Switch.csv";  */
			parent::__construct($this->filename,$this->SwitchNachrichtenID);
			}

		function Switch_LogValue()
			{
			$result=GetValueFormatted($this->variable);
			SetValue($this->variableLogID,GetValue($this->variable));
			//echo "Neuer Wert fuer ".$this->variablename." ist ".GetValueFormatted($this->variable)."\n";
			parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
			//echo "done.\n";
			}

		public function GetComponent() {
			return ($this);
			}
			
		/*************************************************************************************
		Ausgabe des Eventspeichers in lesbarer Form
		erster Parameter true: macht zweimal evaluate
		zweiter Parameter true: nimmt statt dem aktuellem Event den Gesamtereignisspeicher
		*************************************************************************************/

		public function writeEvents($comp=true,$gesamt=false)
			{

			}
			
	   }




	/** @}*/
?>