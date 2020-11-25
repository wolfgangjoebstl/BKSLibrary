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
    * @class IPSComponentSensor_Motion
    *
    * Definiert ein IPSComponentSensor_Motion Object, das ein IPSComponentSensor Object für einen Bewegungsmelder implementiert.
	*
	* Eine Veränderung der Variable im Gerät löst ein Event aus und ruft den MessageHandler auf:  IPSMessageHandler::HandleEvent($variable, $value);
	* HandleEvent im IPSMessageHandler sucht sich die passende Konfiguration und ermittelt den richtigen Component und das übergeordnet Modul für mehrere Components
	* für den Component aus der Config wird wieder HandleEvent aufgerufen component::HandleEvent, hier IPSComponentSensor_Motion::HandleEvent
	*
	* wenn es eine Remote OID gibt wird der Wert dort auch hin geschrieben, gespiegelt
	*
	* sonst wird vorher Motion_LogValue aufgerufen Motion_Logging::Motion_LogValue
	* Motion_LogValue liefert entweder ein 1zu1 Spiegelregister oder das Ausschalten wird mittels Timer verzoegert
	* Funktion abhängig von der Einstellung in 
    *
    * in der aktuellen Implementierung ist das Component Module von der Instanz abhängig, hier TYPE_MOTION
    * eine Instanz aht unterschiedliche register die alle hier bearbeitet werden sollen.
    *
    * @author Wolfgang Jöbstl
    * @version
    *   Version 2.50.1, 09.06.2012<br/>
    */
	 
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");	

    IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

	/******************************************************************************************************
	 *
	 *   Class IPSComponentSensor_Motion
	 *
	 ************************************************************************************************************/

	class IPSComponentSensor_Motion extends IPSComponentSensor {

		private $tempObject;
		private $RemoteOID;
		private $tempValue;
		private $installedmodules;
		private $remServer;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSensor_Monitor Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null)
			{
		    echo "Build Motion Sensor with OID ".$var1.", RemoteOIds $lightObject und Type Definitionen: $lightValue.\n";
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;                    // par1 manchmal auch par2
			$this->tempValue    = $lightValue;
			
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
		 * @param IPSModuleSensor $module Module Object an das das aufgetretene Event weitergeleitet werden soll
		 */
		public function HandleEvent($variable, $value, IPSModuleSensor $module)
			{
            $debug=true;
			/* if ($value<2) 
                {
                if ($debug) echo "IPSComponentSensor_Motion, HandleEvent für VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Wert : ".($value?"Bewegung":"Still")." \n";
    			IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.($value?"Bewegung":"Still"));
                }
            else 
                {
                if ($debug) echo "IPSComponentSensor_Motion, HandleEvent für VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Helligkeitswert $value \n";
    			IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Helligkeitswert '.$value);
                }   */

			$log=new Motion_Logging($variable,Null,$value,$this->tempValue, $debug);                 // kein Variablename vorgegeben, Initialisierung je nach Typ des registers (Motion, Brightness oder Contact) 
            $mirrorOldValue=$log->updateMirrorVariableValue($value);         // noch eine Mirrorvariable, aber hier ganz einfach gelöst, alle in Mirror Category
			$result=$log->Motion_LogValue($value, $debug);                  // hier könnte man mit der Mirrorvariable gleiche Werte noch unterdrücken
            
            $log->RemoteLogValue($value, $this->remServer, $this->RemoteOID );
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
		public function GetComponentParams() 
			{
			return get_class($this);
			}

        /* return Logging class, shall be stored */

		public function GetComponentLogger() 
			{
            return "Motion_Logging";
            }


	}

	/******************************************************************************************************
	 *
	 *   Class Motion_Logging
     *
     *  Erweiterung, diese Klasse kann jetzt zwischen Motion, Contact und Helligkeit unterscheiden
     *
	 *
	 ************************************************************************************************************/
	
	class Motion_Logging extends Logging
		{
        /* init at construct */
        private     $startexecute;                              /* interne Zeitmessung */
        protected   $archiveHandlerID;                          /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */        

        /* init at do_init */
		protected   $installedmodules;                          /* installierte Module */
		protected   $variable, $variableProfile, $variableType;
        protected   $Type;                                      // Eigenschaften der input Variable auf die anderen Register clonen        

        //private  $variableTypeReg;              // Untergruppen, hier MOTION oder BRIGHTNESS 
		//private $mirrorCatID, $mirrorNameID;                    // Spiegelregister in CustomComponent um eine Änderung zu erkennen

		//private $AuswertungID, $NachrichtenID, $filename;             /* Auswertung für Custom Component */

		// $configuration,$variablename,$CategoryIdData       wird in parent class als protected geführt

		/* zusaetzliche Variablen für DetectMovement Funktionen, Detect Movement ergründet Bewegungen im Nachhinein */
		//private $EreignisID,$variableLogID, $variableDelayLogID;

        /* Set_LogValue  */
        // $EreignisID;                     // verwendet Variable von Logging

		//private $motionDetect_NachrichtenID, $motionDetect_DataID;            /* zusätzliche Auswertungen */
        
		/* Unter Klassen 		
        protected $DetectHandler;		        // Unterklasse 
        */
				
		/**********************************************************************
		 * 
		 * Construct und gleichzeitig eine Variable zum Motion Logging hinzufügen. Es geht nur eine Variable gleichzeitig
		 * es werden alle notwendigen Variablen erstmalig angelegt, bei Set_logValue werden keine Variablen angelegt, nur die Register gesetzt
         *
         * Die Spiegelregister anlegen:
         *      CustomComonents schreibt Nachrichten und Süiegelregister in der eigenen Data Kategorie mit
         *      DetectMovement macht dasselbe in seiner Kategorie. Es werden mehrere Spiegelregister angelegt.
         *
         * Initialisiserung erfolgt allgemein mit do_init für die lokalen Variablen und in der parent class dann die zusätzlich Typ spezifischen Variablen
         * in den do_xxxx functions
		 *
		 *************************************************************************/
		 	
		function __construct($variable,$variablename=Null, $value, $typedev, $debug=false)          // construct ohne variable nicht mehr akzeptieren
			{
            if ($debug) echo "Motion_Logging::construct do_init mit $typedev aufrufen:\n";
            $this->startexecute=microtime(true); 
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
            $NachrichtenID=$this->do_init($variable,$variablename,$value, $typedev, $debug);              // $variable kann auch false sein
			parent::__construct($this->filename,$NachrichtenID);                                       // this->filename wird von do_init_xxx geschrieben
			}


        private function do_init($variable,$variablename=NULL,$value, $typedev, $debug=false)
            {
            /**************** installierte Module und verfügbare Konfigurationen herausfinden */
            $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
            $this->installedmodules=$moduleManager->GetInstalledModules();     
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                /* Detect Movement agreggiert die Bewegungs Ereignisse (oder Verknüpfung) */
                IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                }             
            if ($variable!==false)
                {
                $this->$variable=$variable;
                $this->variableProfile=IPS_GetVariable($variable)["VariableProfile"];
                if ($this->variableProfile=="") $this->variableProfile=IPS_GetVariable($variable)["VariableCustomProfile"];
                $this->variableType=IPS_GetVariable($variable)["VariableType"];
                //echo "do_init machen und getfromDatabase aufrufen.\n";                                
                $rows=getfromDatabase("COID",$variable,false,$debug);                // Bestandteil der MySQL_Library, false Alternative
                if ( ($rows === false) || (sizeof($rows) != 1) )
                    {
                    if ($typedev==Null)
                        {
                        if ($debug) echo "\ndo_init,getfromDatabase ohne Ergebnis, selber bestimmen aufgrund des Typs.\n";    
                        if (IPS_GetVariable($variable)["VariableType"]==0) $this->variableType = "MOTION";            // kann STATE auch sein, tut aber nichts zur Sache
                        else $this->variableType = "BRIGHTNESS";
                        }
                    else
                        {
                        if ($debug) echo "\ndo_init,getfromDatabase ohne Ergebnis, dann übergebenes typedev $typedev nehmen.\n";    
                        switch (strtoupper($typedev))
                            {
                            case "MOTION":
                                $this->variableType = "MOTION";
                                break;    
                            case "BRIGHTNESS":
                                $this->variableType = "BRIGHTNESS";
                                break;    
                            case "CONTACT":
                                $this->variableType = "CONTACT";
                                break;   
                            default: 
                                echo "\ndo_init,getfromDatabase ohne Ergebnis und dann noch typedev mit einem unbekannten Typ übergeben -> Fehler.\n";    
                            }    
                        }
                    }
                else    // getfromDatabase
                    {
                    //print_r($rows);   
                    $this->variableType = $rows[0]["TypeRegKey"];
                    if ($debug) echo "\nAus der Datenbank ausgelesen: Register Typ ist ".$this->variableType.". Jetzt unterschiedliche Initialisierungen machen.\n";    
                    }
                $this->Type=0;      // Motion und Contact ist boolean
                if ($this->variableType =="MOTION") $NachrichtenID=$this->do_init_motion($variable, $variablename, $value, $debug);
                elseif ($this->variableType =="CONTACT") $NachrichtenID=$this->do_init_contact($variable, $variablename,$value,$debug);
                elseif ($this->variableType =="BRIGHTNESS") 
                    {
                    $this->Type=1;  // Brightness ist Integer
                    $NachrichtenID=$this->do_init_brightness($variable, $variablename,$value,$debug);
                    }
                else echo "Fehler, kenne den Variable Typ nicht.\n";
                }
            else $this->do_init_statistics();                
            return ($NachrichtenID);    // damit die Nachrichtenanzeige richtig aufgesetzt wird 
            }


        /*** get protectet variables
         *
         */

		public function GetComponent() {
			return ($this);
			}

        public function getVariableNameLogging()   
            {
            return $this->variablename;      
            }

        public function getConfigurationLogging()
            {
            return $this->configuration;      
            }

        public function getVariableOIDLogging()
            {
            if ( (isset ($this->installedmodules["DetectMovement"])) && ($this->variableType==0) )
                {
                $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "variableLogID" => $this->variableLogID, "variableDelayLogID" => $this->variableDelayLogID, "Ereignisspeicher" => $this->EreignisID, "Gesamt_Ereignisspeicher" => $this->GesamtID, "Gesamt_Ereigniszaehler" => $this->GesamtCountID];
                }
            elseif ($this->variableType==0) $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "variableLogID" => $this->variableLogID, "variableDelayLogID" => $this->variableDelayLogID];
            else $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "variableLogID" => $this->variableLogID];

            return $result;
            }


        /* Spiegelregister updaten */

        function updateMirrorVariableValue($value)
            {
            $oldvalue=GetValue($this->mirrorNameID);
            SetValue($this->mirrorNameID,$value);
            return($oldvalue);
            }


		/**********************************************************************
		 * 
		 * Eine Variable zum Motion Logging hinzufügen. Es geht nur eine Variable gleichzeitig
         * Routine wird verwendet bei der Status Ausgabe für die Events:
         *      $log->Set_LogValue($oid);
		 *		$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
		 *
		 *************************************************************************/

		function Set_LogValue($variable)
			{
			if ( ($variable<>null) && ($variable<>false) )
				{
				echo "Set_LogValue, Add Variable ID : ".$variable." (".IPS_GetName($variable).") für IPSComponentSensor Motion Logging.\n";
                $this->do_init($variable);                                                                                                  // initialisiserung gleich wie in construct
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->AuswertungID,$this->Type,$this->variableProfile);                   // $this->variableLogID schreiben aus do_setVariableLogId
				}
			else echo "Set_LogValue aufgerufen mit variable mit Null oder False.\n"; 

			/* DetectMovement Spiegelregister und statische Anwesenheitsauswertung, nachtraeglich */
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* nur wenn Detect Movement installiert ist ein Motion Log fuehren */
				echo "Construct Motion Logging for DetectMovement, Uebergeordnete Variable : ".$this->variablename."\n";
				$variablename=str_replace(" ","_",$this->variablename)."_Ereignisspeicher";
				$erID=CreateVariable($variablename,3, $this->motionDetect_DataID, 10, '', null );
				echo "  Ereignisspeicher aufsetzen        : ".$erID." \n";
				$this->EreignisID=$erID;
				}
	   		}
			
		/**********************************************************************
		 * 
		 * Den Wert einer Variable dem Motion Logging zuführen
		 *
		 * IPSComponents_Sensor wird vom Messagehandler aufgerufen
		 * Die VariableID wird im construct Aufruf übergeben, der neue Wert 
		 * sollte bereits in der Variable gespeichert sein
		 *
		 * ACHTUNG der testweise Wertübertrag führt zu Verwirrung weil ein Ueberschreiben des Wertes gleich wieder einen Trigger ausloest
		 *
		 *************************************************************************/			
	   
		function Motion_LogValue($value,$debug=false)
			{
			$result=GetValue($this->variable);
            switch ($this->variableType)
                {            
                case "MOTION":
                    $resultLog=$this->doLogMotion($result);
                    break;
                case "CONTACT":
                    $resultLog=$this->doLogContact($result);
                    break;
                case "BRIGHTNESS":
                    $resultLog=$this->doLogBrightness($result);
                    break;
                default:
                    echo "Fehler Motion_LogValue, do not know Type\n";
                    break;
                }
			parent::LogMessage($resultLog);
			parent::LogNachrichten($this->variablename." mit Status ".$resultLog,$debug);
			}

        private function doLogMotion($result)
            {
            if (true)
                {
                //$result=$value;		/* für Testzwecke, der mitgelieferte Wert wird normalerweise nicht geschrieben */
                //echo "NUR FUER TESTZWECKE WERT UEBERMITTELN.\n";
                }
            $resultLog=GetValueIfFormatted($this->variable);
            echo "CustomComponent Motion_LogValue Log Variable ID : ".$this->variable." (".IPS_GetName($this->variable)."), aufgerufen von Script ID : ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert : $resultLog\n";
            IPSLogger_Inf(__file__, 'CustomComponent Motion_LogValue: Lets log Motion '.$this->variable." (".IPS_GetName($this->variable)."/".IPS_GetName(IPS_GetParent($this->variable)).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert $resultLog");
            if ( (isset($this->configuration["LogConfigs"]["DelayMotion"])) == true)
                {
                if ($result==true)
                    {
                    $delaytime=$this->configuration["LogConfigs"]["DelayMotion"];
                    SetValue($this->variableDelayLogID,$result);
                    echo "   Verzögerung der Events konfiguriert, Timer im selben Verzeichnis wie Script gesetzt : ".$this->variable."_".$this->variablename."_EVENT"."\n";
                    $now = time();
                    $EreignisID = @IPS_GetEventIDByName($this->variable."_".$this->variablename."_EVENT", IPS_GetParent($_IPS['SELF']));
                    if ($EreignisID === false)
                        { //Event nicht gefunden > neu anlegen
                        $EreignisID = IPS_CreateEvent(1);
                        IPS_SetName($EreignisID,$this->variable."_".$this->variablename."_EVENT");
                        IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
                        }
                    IPS_SetEventCyclic($EreignisID,0,1,0,0,1,$delaytime);      /* konfigurierbar, zB alle 30 Minuten, d.h. 30 Minuten kann man still sitzen bevor keine Bewegung mehr erkannt wird */
                    IPS_SetEventCyclicTimeBounds($EreignisID,time(),0);  /* damit die Timer hintereinander ausgeführt werden */
                    IPS_SetEventScript($EreignisID,"if (GetValue(".$this->variable.")==false) { SetValue(".$this->variableDelayLogID.",false); IPS_SetEventActive(".$EreignisID.",false);} \n");
                    IPS_SetEventActive($EreignisID,true);
                    }
                }	
            else
                {
                /* Kein Delay konfiguriert, Wert egal ob true oder false einfach übernehmen */
                SetValue($this->variableLogID,$result);				
                }
            //print_r($this);
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                /* etwas kompliziert, wenn DetectMovement nicht installiert is sind beide Variablen auf dem selben Wert.
                * wenn installiert, wird Delay abgewickelt, aber es muss noch wer den Wert in CustomComponents setzen
                */
                SetValue($this->variableLogID,$result);
                
                /* DetectMovement class verwenden */
                IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                                                                        
                /* Achtung die folgenden Werte haben keine Begrenzung, sicherstellen dass String Variablen nicht zu gross werden. */
                $EreignisVerlauf=GetValue($this->EreignisID);
                $GesamtVerlauf=GetValue($this->GesamtID);
                $GesamtZaehler=GetValue($this->GesamtCountID);
                if ($GesamtZaehler<STAT_WenigBewegung) {$GesamtZaehler=STAT_WenigBewegung;}
                if (IPS_GetName($this->variable)=="MOTION")
                    {
                    if (GetValue($this->variable))
                        {
                        $resultLog="Bewegung";
                        //$EreignisVerlauf.=date("H:i").";".STAT_Bewegung.";";
                        $Ereignis=time().";".STAT_Bewegung.";";
                        $GesamtZaehler+=1;
                        $EreignisVerlauf.=$Ereignis;
                        $GesamtVerlauf.=$Ereignis;
                        }
                    else
                        {
                        $resultLog="Ruhe";
                        //$EreignisVerlauf.=date("H:i").";".STAT_WenigBewegung.";";
                        $Ereignis=time().";".STAT_WenigBewegung.";";
                        $GesamtZaehler-=1;
                        if ($GesamtZaehler<STAT_WenigBewegung) {$GesamtZaehler=STAT_WenigBewegung;}
                        //$GesamtVerlauf.=date("H:i").";".$GesamtZaehler.";";
                        $EreignisVerlauf.=$Ereignis;
                        $GesamtVerlauf.=$Ereignis;
                        }
                    }
                else
                    {
                    $Ereignis=time().";".STAT_Bewegung.";".time().";".STAT_WenigBewegung.";";
                    if (GetValue($this->variable))
                        {
                        $resultLog="Offen";
                        }
                    else
                        {
                        $resultLog="Geschlossen";
                        }
                    $EreignisVerlauf.=$Ereignis;
                    }
                echo "\nEreignisverlauf evaluieren bevor neu geschrieben wird von : ".IPS_GetName($this->EreignisID)." \n";
                SetValue($this->EreignisID,$this->evaluateEvents($EreignisVerlauf));
                echo "\nEreignisverlauf evaluieren bevor neu geschrieben wird von : ".IPS_GetName($this->GesamtID)." \n";
                SetValue($this->GesamtID,$this->evaluateEvents($GesamtVerlauf,60));
                SetValue($this->GesamtCountID,$GesamtZaehler);
            
                //print_r($DetectMovementHandler->ListEvents("Motion"));
                //print_r($DetectMovementHandler->ListEvents("Contact"));

                $groups=$this->DetectHandler->ListGroups('Motion',$this->variable);      // nur die Gruppen für dieses Event updaten, wenn Parameter Motion angegeben ist gibt es auch ein Explode der mit Komma getrennten Gruppennamen
                foreach($groups as $group=>$name)
                    {
                    echo "\nMotion_LogValue Log DetectMovement Gruppe ".$group." behandeln.\n";
                    $config=$this->DetectHandler->ListEvents($group);
                    $status=false; $status1=false;
                    foreach ($config as $oid=>$params)
                        {
                        $status=$status || GetValue($oid);
                        echo "  OID: ".$oid." Name: ".str_pad((IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($oid)))),50)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
                        $moid=$this->DetectHandler->getMirrorRegister($oid);
                        $status1=$status1 || GetValue($moid);
                        }
                    echo "  Gruppe ".$group." hat neuen Status, Wert ohne Delay: ".(integer)$status."  mit Delay:  ".(integer)$status1."\n";
                    $statusID=CreateVariable("Gesamtauswertung_".$group,0,IPS_GetParent($this->variableDelayLogID),1000, '~Motion', null,false);
                    $oldstatus1=GetValue($statusID);
                    if ($oldstatus1 != $status1) 
                        {
                        echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID." Änderung Wert von $oldstatus1 auf $status1.\n";
                        SetValue($statusID,$status1);     // Vermeidung von Update oder Change Events
                        }
                    $statusID=CreateVariable("Gesamtauswertung_".$group,0,IPS_GetParent($this->variableLogID),1000, '~Motion', null,false);
                    $oldstatus=GetValue($statusID);
                    if ($oldstatus != $status1) 
                        {
                        echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID." Änderung Wert von $oldstatus auf $status.\n";
                        SetValue($statusID,$status);     // Vermeidung von Update oder Change Events
                        }
                    
                    $ereignisID=CreateVariable("Gesamtauswertung_".$group."_Ereignisspeicher",3,IPS_GetParent($this->variableDelayLogID),0, '', null);
                    echo "  EreignisID       : ".$ereignisID." (".IPS_GetName($ereignisID).")\n";
                    echo "  Ereignis         : ".$Ereignis."\n";
                    //echo "  Size             : ".strlen(GetValue($ereignisID))."\n";
                    $EreignisVerlauf=GetValue($ereignisID).$Ereignis;
                    //echo "  Ereignis Verlauf : ".$EreignisVerlauf."\n";
                    SetValue($ereignisID,$this->addEvents($EreignisVerlauf));
                    }
                } /* Ende Detect Motion */
            return ($resultLog);
            }

        /* eigentliches Logging durchführen, speziell für Helligkeit */

        private function doLogBrightness($result)
            {
            $resultLog=GetValueIfFormatted($this->variable);
            echo "CustomComponent Motion_LogValue Log Brightness Variable ID : ".$this->variable." (".IPS_GetName($this->variable)."), aufgerufen von Script ID : ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert : $resultLog\n";
            IPSLogger_Inf(__file__, 'CustomComponent Motion_LogValue: Lets log Brightness '.$this->variable." (".IPS_GetName($this->variable).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert $resultLog");
            SetValue($this->variableLogID,$result);		
            return ($resultLog);
            }

        /* eigentliches Logging durchführen, speziell für Kontakte 
         *
         * Funktion gleich wie Helligkeit, nur andere Debug Ausgabe
         */
        
        private function doLogContact($result)
            {
            $resultLog=GetValueIfFormatted($this->variable);
            echo "CustomComponent Motion_LogValue Log Contact Variable ID : ".$this->variable." (".IPS_GetName($this->variable)."), aufgerufen von Script ID : ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert : $resultLog\n";
            IPSLogger_Inf(__file__, 'CustomComponent Brightness Log: Lets log motion '.$this->variable." (".IPS_GetName($this->variable).") ".$_IPS['SELF']." (".IPS_GetName($_IPS['SELF']).") mit Wert $resultLog");
            SetValue($this->variableLogID,$result);		
            return ($resultLog);
            }

        /* Gesamtauswertung verallgemeinern, die von Motion hab ich extra gelassen da sie auch die Bewegung mit Delays extra aggregiert */

        private function do_gesamtauswertung($aggType)
            {
	                /*****************Agreggierte Variablen beginnen mit Gesamtauswertung_ */
                if (isset ($this->installedmodules["DetectMovement"]))
                    {
                    echo "     DetectMovement ist installiert. Aggregation abarbeiten:\n";
                    $groups=$this->DetectHandler->ListGroups($aggType,$this->variable);      // nur die Gruppen für dieses Event updaten
                    foreach($groups as $group=>$name)
                        {
                        echo "      --> Gruppe ".$group." behandeln.\n";
                        $config=$this->DetectHandler->ListEvents($group);
                        $status=(float)0;
                        $count=0;
                        foreach ($config as $oid=>$params)
                            {
                            $status+=GetValue($oid);
                            $count++;
                            //echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".GetValue($oid)." ".$status."\n";
                            echo "OID: ".$oid." Name: ".str_pad(IPS_GetName($oid).".".IPS_GetName(IPS_GetParent($oid)),50)."Status: ".GetValue($oid)." ".$status."\n";
                            }
                        switch ($this->variableType)
                            {
                            case 2:
                                if ($count>0) { $statusResult=round($status/$count,1); }
                                else echo "Gruppe ".$group." hat keine eigenen Eintraege.\n";
                                break;
                            case 1:
                                if ($count>0) { $status=$status/$count; }
                                else echo "Gruppe ".$group." hat keine eigenen Eintraege.\n";
                                $statusResult=(integer)$status;                            
                                break;
                            }
                        //echo "Gruppe ".$group." hat neuen Status : ".$status."\n";
                        /* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
                        $statusID=CreateVariableByName($this->AuswertungID,"Gesamtauswertung_".$group,$this->variableType, $this->variableProfile, null, 1000, null);
                        $oldstatus=GetValue($statusID);
                        if ($oldstatus != $statusResult) 
                            {
                            echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID." Änderung Wert von $oldstatus auf $statusResult.\n";
                            SetValue($statusID,$statusResult);     // Vermeidung von Update oder Change Events
                            }
                        }
                    }	
            }

		/*************************************************************************************
		Bearbeiten des Eventspeichers
		hier nur überprüfen ober der Eventspeicher nicht zu lang wird

		*************************************************************************************/

		private function addEvents($value)
			{
			/* keine Indizierung auf Herkunft der Variable, nur String Werte evaluieren */
			echo "  Check Eventliste (max 20.000 Eintraege), derzeit Länge Ereignisstring: ".strlen($value)."\n";
			$max=20000;
			$EventArray = explode(";", $value);
		   $array_size = count($EventArray);
         $i = $array_size-2;  /* Array Index geht von 0 bis Länge minus 1 */
         if ($i>0)
            {
            /* Events komprimieren erst wenn gross genug */
				$previous_state=$EventArray[$i];
				$previous_time=(integer)$EventArray[$i-1];
				if ($i>($max*2))
				   {
				   /* events nicht groesser Eintraege werden lassen */
					$indexbefordelete=$max;
					}
				else
					{
					$indexbefordelete=0;
					}

				//echo "Array Size is ".$i."  : last values are ".$previous_state." ? ".$previous_time."\n";
				//echo "      Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." \n";
				$i=$i-2;
				$delete=false;
			 	while($i > 0)
 					{
		   		/* Process array data:  Bewegungsmelder kennt nur zwei Zustaende, Bewegung:7 und wenigBewegung:6
						Wenn zwischen 7 und vorher 6 weniger als 15 Minuten vergangen sind den Zustand 6 loeschen
						Wenn 7 auf 7 folgt den juengsten wert 7 loeschen
					*/
					$now_time=$previous_time;
					$bef_time=(integer)$EventArray[$i-1];

					if ($i<$indexbefordelete) {$delete=true;}
					if ($delete==true)
					   {
  					   unset($EventArray[$i+0]);
					   unset($EventArray[$i-1]);
					   }
					$i=$i-2; /* immer zwei Werte, Zeit ueberspringen */
				 	}
				 }
			$value=implode(";",$EventArray);
			return ($value);
			}

		/*************************************************************************************
		Bearbeiten des Eventspeichers


		*************************************************************************************/

		private function evaluateEvents($value, $diftimemax=15)
			{
			/* keine Indizierung auf Herkunft der Variable, nur String Werte evaluieren */
			echo "  Evaluate Eventliste (max 20 Eintraege) : ".$value."\n";
			$EventArray = explode(";", $value);
		   $array_size = count($EventArray);
         $i = $array_size-2;  /* Array Index geht von 0 bis Länge minus 1 */
         if ($i>0)
            {
            /* Events komprimieren erst wenn gross genug */
				$previous_state=$EventArray[$i];
				$previous_time=(integer)$EventArray[$i-1];
				if ($i>40)
				   {
				   /* events nicht groesser als 20 Eintraege werden lassen */
					$indexbefordelete=$i-20;
					}
				else
					{
					$indexbefordelete=0;
					}

				//echo "Array Size is ".$i."  : last values are ".$previous_state." ? ".$previous_time."\n";
				echo "      Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." \n";
				$i=$i-2;
				$delete=false;
			 	while($i > 0)
 					{
		   		/* Process array data:  Bewegungsmelder kennt nur zwei Zustaende, Bewegung:7 und wenigBewegung:6
						Wenn zwischen 7 und vorher 6 weniger als 15 Minuten vergangen sind den Zustand 6 loeschen
						Wenn 7 auf 7 folgt den juengsten wert 7 loeschen
					*/
					$now_time=$previous_time;
					$bef_time=(integer)$EventArray[$i-1];

					if ($i<$indexbefordelete) {$delete=true;}
					if ($delete==true)
					   {
  					   unset($EventArray[$i+0]);
					   unset($EventArray[$i-1]);
					   }
					else
					   {
						$dif_time=(($now_time-$bef_time)/60);
						//echo "Betrachteter (".$i.") State jetzt ".$previous_state," am ".date("d.m H:i",$previous_time)." und davor ".$EventArray[$i]." am ".date("d.m H:i",$EventArray[$i-1])." Abstand: ".number_format($dif_time,1,",",".")." Minute \n";
						echo "      Betrachteter (".$i.") State jetzt ".$EventArray[$i]." am ".date("d.m H:i",$EventArray[$i-1])." Abstand: ".number_format($dif_time,1,",",".")." Minute \n";
						switch ($previous_state)
   	  				   {
   	  				   /*****************************************************************************
							 erst einmal Unterscheidung anhand aktuellem Status
							 Bewegung   ->  um so mehr Bewegungssender aktiv sind um so hoeher der Wert
							******************************************************************************/
	 			     		case STAT_Bewegung9:
	 			     		case STAT_Bewegung8:
	 			     		case STAT_Bewegung7:
	 			     		case STAT_Bewegung6:
	 			     		case STAT_Bewegung5:
	 			     		case STAT_Bewegung4:
	 			     		case STAT_Bewegung3:
	 			     		case STAT_Bewegung2:
			   	  	   case STAT_Bewegung:
						      /* Wenn jetzt Bewegung ist unterscheiden ob vorher Bewegung oder wenigBewegung war			   */
		      				switch ($EventArray[$i]) /* Zustand vorher */
								 	{
	 			     		   	case STAT_Bewegung9:
	 			     		   	case STAT_Bewegung8:
	 			     		   	case STAT_Bewegung7:
	 			     		   	case STAT_Bewegung6:
	 			     		   	case STAT_Bewegung5:
	 			     		   	case STAT_Bewegung4:
	 			     		   	case STAT_Bewegung3:
	 			     		   	case STAT_Bewegung2:
	 			     		   	case STAT_Bewegung:
		 							   $previous_state=$EventArray[$i];
						   			$previous_time=(integer)$EventArray[$i-1];
				   				 	/* einfach die aktuellen zwei Einträge loeschen, ich brauche keinen Default Wert */
				   				 	if (isset($EventArray[$i+2]))
											{
											/* nicht zweimal loeschen */
											echo "--->Bewegung, wir loeschen Eintrag ".($i+2)." mit ".$EventArray[$i+2]." am ".date("d.m H:i",$EventArray[$i+1])."\n";
   									 	unset($EventArray[$i+2]);
	  							 			unset($EventArray[$i+1]);
	  							 			}
									 	break;
						 			case STAT_WenigBewegung:
									case STAT_KeineBewegung:
									case STAT_vonzuHauseweg:
									   //echo "Wenig Bewegung: ".$dif_time."\n";
										if (($dif_time<$diftimemax) and ($dif_time>=0))
										   {
										   // Warum mus dif_time >0 sein ????
	  			   						$previous_state=10;    /* default, einen ueberspringen, damit voriger Wert vorerst nicht mehr geloescht werden kann */
		   							 	/* einfach die letzten zwei Einträge loeschen, nachdem Wert kein zweites Mal geloescht werden kann vorerst mit Default Wert arbeiten */
											echo "--->WenigBewegung, wir loeschen Eintrag ".($i)." mit ".$EventArray[$i+0]." am ".date("d.m H:i",$EventArray[$i-1])."\n";
   									 	unset($EventArray[$i+0]);
	   								 	unset($EventArray[$i-1]);
				   				 		}
		   					 		else
		   						 	   {
						    				$previous_state=$EventArray[$i];
									      $previous_time=(integer)$EventArray[$i-1];
											}
									 	break;
							 		default:
								 	   /* Wenn der Defaultwert kommt einfach weitermachen, er kommt schon beim naechsten Durchlauf dran */
				    					$previous_state=$EventArray[$i];
							   	   $previous_time=(integer)$EventArray[$i-1];
							    		break;
								 }
								break;
			   	  	   case STAT_WenigBewegung:
						      /* Wenn jetzt wenigBewegung ist unterscheiden ob vorher Bewegung oder wenigBewegung war			   */
		      				switch ($EventArray[$i]) /* Zustand vorher */
		      				   {
	 			     		   	case STAT_WenigBewegung:
		 							   $previous_state=$EventArray[$i];
						   			$previous_time=(integer)$EventArray[$i-1];
				   				 	/* einfach die aktuellen zwei Einträge loeschen, ich brauche keinen Default Wert */
				   				 	if (isset($EventArray[$i+2]))
											{
											/* nicht zweimal loeschen */
											echo "--->WenigBewegung, wir loeschen Eintrag ".($i+2)." mit ".$EventArray[$i+2]." am ".date("d.m H:i",$EventArray[$i+1])."\n";
   									 	unset($EventArray[$i+2]);
	  							 			unset($EventArray[$i+1]);
	  							 			}
									 	break;
							 		default:
								 	   /* Wenn der Defaultwert kommt einfach weitermachen, er kommt schon beim naechsten Durchlauf dran */
				    					$previous_state=$EventArray[$i];
							   	   $previous_time=(integer)$EventArray[$i-1];
							    		break;
									}
			   	  	      break;
			   	   	case STAT_vonzuHauseweg:
						       /* Wenn zletzt bereits Abwesend erkannt wurde, kann ich von zuHause weg und nicht zu Hause
								    wegfiltern, allerdings ich lasse die Zeit des jetzigen events ,also dem früheren
								    2 eliminiert den vorigen 2 er und lässt aktuelle Zeit
							    */
				   	   	 switch ($EventArray[$i])
								    {
				 					 case STAT_vonzuHauseweg:
				   					 $previous_state=10;    /* default */
				   					 /* einfach von den letzten zwei Einträgen rausloeschen */
			   						 unset($EventArray[$i+0]);
						   			 unset($EventArray[$i-1]);
							 		 break;
						 			 default:
									 	 $previous_state=$EventArray[$i];
						   			 $previous_time=(integer)$EventArray[$i-1];
								 		 break;
							 		 }
								break;
   	  			   	case STAT_Abwesend:
						       /* Wenn zletzt bereits Abwesend erkannt wurde, kann ich von zuHause weg und nicht zu Hause
								    wegfiltern, allerdings ich lasse die Zeit des jetzigen events ,also dem früheren
								    0 übernimmt die Zeit des Vorgängers und eliminiert 0,1 und 2
							     */
					   	    switch ($EventArray[$i])
								    {
			     	   			 case STAT_Abwesend:
									 case STAT_nichtzuHause:
					 				 case STAT_vonzuHauseweg:
						   			 $previous_state=10;    /* default */
   									 /* einfach von den letzten zwei Einträgen die mittleren Werte rausloeschen */
		   							 unset($EventArray[$i+1]);
   									 unset($EventArray[$i+0]);
								 		 break;
					 				 default:
									    $previous_state=$EventArray[$i];
								   	 $previous_time=(integer)$EventArray[$i-1];
								 		 break;
					 				 }
								break;
							default:
							   $previous_state=$EventArray[$i];
	      					$previous_time=(integer)$EventArray[$i-1];
								break;
							}
						}
					$i=$i-2; /* immer zwei Werte, Zeit ueberspringen */
				 	}
				 }
			$value=implode(";",$EventArray);
			return ($value);
			}


		/*************************************************************************************
		Ausgabe des Eventspeichers in lesbarer Form
		erster Parameter true: macht zweimal evaluate
		zweiter Parameter true: nimmt statt dem aktuellem Event den Gesamtereignisspeicher
		*************************************************************************************/

        public function writeEvents($comp=true,$gesamt=false)
            {
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                if ($gesamt)
                {
                    $value=GetValue($this->GesamtID);
                    $diftimemax=60;
                    }
                else
                {
                    $value=GetValue($this->EreignisID);
                    $diftimemax=15;
                    }
                /* es erfolgt zwar eine Kompromierung aber keine Speicherung in den Events, das ist nur bei Auftreten eines Events */
                if ($comp)
                    {
                    $value=$this->evaluateEvents($value, $diftimemax);
                    $value=$this->evaluateEvents($value, $diftimemax);
                    }
                $EventArray = explode(";", $value);
                echo "Write Eventliste von ".IPS_GetName($this->EreignisID)." : ".$value."\n";

                /* Umsetzung des kodierten Eventarrays in lesbaren Text */
                $event2="";
                $array_size = count($EventArray);
                for ($k=1; $k<($array_size); $k++ )
                    {
                    $event2=$event2.date("d.m H:i",(integer)$EventArray[$k-1])." : ";
                    //echo "check : ".$EventArray[$k]."\n";
                    switch ($EventArray[$k])
                        {
                        case STAT_KommtnachHause:
                            $event2=$event2."Kommt nach Hause";
                        break;
                    case STAT_Bewegung9:
                        $event2=$event2."Bewegung 9 Sensoren";
                        break;
                        case STAT_Bewegung8:
                        $event2=$event2."Bewegung 8 Sensoren";
                        break;
                    case STAT_Bewegung7:
                        $event2=$event2."Bewegung 7 Sensoren";
                        break;
                        case STAT_Bewegung6:
                        $event2=$event2."Bewegung 6 Sensoren";
                        break;
                    case STAT_Bewegung5:
                        $event2=$event2."Bewegung 5 Sensoren";
                        break;
                        case STAT_Bewegung4:
                        $event2=$event2."Bewegung 4 Sensoren";
                        break;
                    case STAT_Bewegung3:
                        $event2=$event2."Bewegung 3 Sensoren";
                        break;
                        case STAT_Bewegung2:
                        $event2=$event2."Bewegung 2 Sensoren";
                        break;
                    case STAT_Bewegung:
                        $event2=$event2."Bewegung";
                        break;
                        case STAT_WenigBewegung:
                        $event2=$event2."Wenig Bewegung";
                        break;
                        case STAT_KeineBewegung;
                        $event2=$event2."Keine Bewegung";
                        break;
                        case STAT_Unklar:
                        $event2=$event2."Unklar";
                        break;
                        case STAT_Undefiniert:
                        $event2=$event2."Undefiniert";
                        break;
                        case STAT_vonzuHauseweg:
                        $event2=$event2."Von zu Hause weg";
                        break;
                        case STAT_nichtzuHause:
                        $event2=$event2."Nicht zu Hause";
                        break;
                        case STAT_Abwesend:
                        $event2=$event2."Abwesend";
                        break;
                        }
                    $k++;
                $event2=$event2."\n";
                    }
                return ($event2);
                }
            else
                {
                return ("");
                }		
            } /* ende function */
            
        } /* ende class */	

	/******************************************************************************************************
	 *
	 *   Class Motion_LoggingStatistics
     *
     * Erweiterung der Klasse um statistische Auswertungen. Aktuell sind die Routinen noch in der child class
     *
	 *
	 ************************************************************************************************************/

	class Motion_LoggingStatistics extends Motion_Logging
		{

		function __construct()          // construct ohne variable nur für übergeordnete Aufrufe erlauben
			{
            $this->startexecute=microtime(true); 
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 

    		parent::__construct(false);
			}



        }
	/** @}*/
?>