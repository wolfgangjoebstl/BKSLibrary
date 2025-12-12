<?php
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentheatControl.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentHeatControl
    *
    * Definiert eine anstrakte class IPSComponentHeatControl die von IPSComponentHeatControl_Homematic und IPSComponentHeatControl_HomematicIP verwendet wird
    * alle verwenden zum Loggen der Variablen das HeatControl_Logging
    * Logging Aufrufe vereinheitlicht, können tabellarisch aufgerufen werden
    * 
    *
    */

	IPSUtils_Include ('IPSComponent.class.php', 'IPSLibrary::app::core::IPSComponent');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	abstract class IPSComponentHeatControl extends IPSComponent {

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
		abstract public function HandleEvent($variable, $value, IPSModuleHeatControl $module);
		
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
		
		/*
		 * aktuellen Status der remote logging server bestimmen
		 */	
	
		public function remoteServerSet()
			{
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
					
		/*****************
		 *
		 * schreibt den Wert value auf die remote Server. Remote Server sind in RemoteOID mit Kurzname Doppelpunkt und Remote OID angelegt
		 * die Zuordnung Kurzname zu url steht im remServer array 
		 *
		 *****************************************/
		
		public function WriteValueRemote($value)
			{
			if ($this->RemoteOID != Null)
				{
				$params= explode(';', $this->RemoteOID);
				foreach ($params as $val)
					{
					$para= explode(':', $val);
					//echo "Wert :".$val." Anzahl ",count($para)." \n";
					if (count($para)==2)
						{
						$Server=$this->remServer[$para[0]]["Url"];
						if ($this->remServer[$para[0]]["Status"]==true)
							{
							$rpc = new JSONRPC($Server);
							$roid=(integer)$para[1];
							//echo "Server : ".$Server." Remote OID: ".$roid." Value ".$value."\n";
							$rpc->SetValue($roid, $value);
							}
						}
					}
				}			
			}

	}  /* ende class */
	


	/********************************* 
	 *
	 * Klasse überträgt die Werte an einen remote Server und schreibt lokal in einem Log register mit
	 *
	 * legt dazu zwei Kategorien im eigenen data Verzeichnis ab
	 *
	 * xxx_Auswertung und xxxx_Nachrichten
	 *
	 **************************/

	class HeatControl_Logging extends Logging
		{
		private     $variable;
		public      $variableLogID;					/* ID der entsprechenden lokalen Spiegelvariable */
        protected   $variableProfile, $variableTypeReg, $variableType;        // Eigenschaften der input Variable auf die anderen Register clonen

		public      $variableEnergyLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den Energiewert */
		public      $variablePowerLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den leistungswert */
		public      $variableTimeLogID;				/* ID der entsprechenden lokalen Spiegelvariable für den Zeitpunkt der letzten Änderung */
				
		private     $HeatControlAuswertungID;
		private     $HeatControlNachrichtenID;

		private     $powerConfig;					/* Powerwerte der einzelnen Heizkoerper, Null wenn Configfile nicht vorhanden */
        protected   $debug;

		//protected $configuration, $variablename,$CategoryIdData;           // in der parent class definiert
		//protected $mirrorCatID, $mirrorNameID;                            // in der parent class definiert, Spiegelregister in CustomComponent um eine Änderung zu erkennen
		//protected $AuswertungID, $NachrichtenID, $filename;             // in der parent class definiert, Auswertung für Custom Component 


		/* Unter Klassen */
		
		protected $installedmodules;                    /* installierte Module */
        //protected $DetectHandler,$archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */          
				
        /* HeatControl_Logging construct
         * die wichtigsten Variablen initialisieren und anlegen
         * Parameter value, typedev und debug für Kompatibilität hinzugefügt
         */   
		function __construct($variable,$variablename=Null, $value=Null, $variableTypeReg="unknown", $debug=false)
			{
            $this->startexecute=microtime(true);    
            if ( ($this->GetDebugInstance()) && ($this->GetDebugInstance()==$variable) ) $this->debug=true;
            else $this->debug=$debug;

			if ($this->debug) echo "HeatControl_Logging:construct for Variable ID ".$variable." with Type $variableTypeReg .\n";

            $this->constructFirst();        // sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()

            if ($variableTypeReg != "unknown")          // einheitliche Parameterierung
                {
                //echo "Feuchtigkeit_Logging  $variableTypeReg\n";      // KEY, PROFIL, TYP wird übernommen
                $component = new ComponentHandling();
                $keyName=array();
                $keyName["KEY"]=$variableTypeReg;
                $status=$component->addOnKeyName($keyName,$this->debug); 
                if ($status===false) $keyName="unknown";                        // Fehler abfangen, Component kennt keinen Abbruch
                //print_R($keyName);
                $variableTypeReg=$keyName;
                }

            /************** INIT */
            //$this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

            $this->powerConfig=array();             // vom DetectHandler beschrieben

			/**************** installierte Module und verfügbare Konfigurationen herausfinden */
			//$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			//$this->installedmodules=$moduleManager->GetInstalledModules();
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                /* Detect Movement agreggiert die Bewegungs Ereignisse (oder Verknüpfung) */
                //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DetectMovement\DetectMovementLib.class.php");
                //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DetectMovement\DetectMovement_Configuration.inc.php");
                IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                $this->DetectHandler = new DetectHeatControlHandler();
				$this->powerConfig=$this->DetectHandler->get_PowerConfig();                
                if ($this->debug) echo "DetectMovement installed.\n";
                } 

            $dosOps= new dosOps();

            $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
			if ($this->debug) echo "  Construct IPSComponentSensor HeatControl Logging for Variable ID : ".$this->variable." mit dem Namen ".$this->variablename."\n";

			/* Find Data category of IPSComponent Module to store the Data */				
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			if ($this->debug) echo "  Kategorie Data im CustomComponents Datenverzeichnis: ".$this->CategoryIdData."  (".IPS_GetName($this->CategoryIdData).")\n";

			/* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
			$this->HeatControlNachrichtenID=$this->CreateCategoryNachrichten("HeatControl",$this->CategoryIdData);
			$this->HeatControlAuswertungID=$this->CreateCategoryAuswertung("HeatControl",$this->CategoryIdData);;
            if ($this->debug) echo "  NachrichtenID : ".$this->HeatControlNachrichtenID."    AuswertungID:  ".$this->HeatControlAuswertungID."  \n";
            	
			/* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
			if ($variable<>null)
				{       // Daten aus der Input Variable nehmen, es erfolgt keine vereinheitlichung
    			$this->variable=$variable;
                $this->variableProfile=IPS_GetVariable($variable)["VariableProfile"];       
                if ($this->variableProfile=="") $this->variableProfile=IPS_GetVariable($variable)["VariableCustomProfile"];
                $this->variableType=IPS_GetVariable($variable)["VariableType"];

				if ($this->debug) echo "  Lokales Spiegelregister als Integer auf ".$this->variablename." unter Kategorie ".$this->HeatControlAuswertungID." ".IPS_GetName($this->HeatControlAuswertungID)." anlegen.\n";


                if (is_array($variableTypeReg))
                    {
                    // wird nachher nicht mehr überschrieben, set eigentlich schon zum Install
                    $this->variableTypeReg = strtoupper($variableTypeReg["KEY"]);  
                    $this->variableProfile=$variableTypeReg["PROFILE"];
                    $this->variableType=$variableTypeReg["TYP"];  
                    }
                // setVariableLogId($variable, $variablename, $AuswertungID,$type,$profile,$debug=false)
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->HeatControlAuswertungID,$this->variableType,$this->variableProfile,true);                   // $this->variableLogID schreiben
                if ($this->debug) echo "    VariableLog Register angelegt als ".$this->variableLogID."\n";
                IPS_SetHidden($this->variableLogID,false);
				
                if ( isset($this->powerConfig[$variable]) )
                    {
                    $archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                    if ($this->debug) echo "   Lokales Spiegelregister für Energie- und Leistungswert unterhalb Variable ID ".$this->variableLogID." und Parent Kategorie ".IPS_GetName($this->HeatControlAuswertungID)." anlegen.\n";
                    /* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
                    //$this->variableEnergyLogID=CreateVariable($this->variablename."_Energy",2,$this->variableLogID, 10, "~Electricity", null, null );  /* 1 steht für Integer, 2 für Float, alle benötigten Angaben machen, sonst Fehler */
                    //$this->variablePowerLogID=CreateVariable($this->variablename."_Power",2,$this->variableLogID, 10, "~Power", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
                    //$this->variableTimeLogID=CreateVariable($this->variablename."_Changetime",1,$this->variableLogID, 10, "~UnixTimestamp", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
                    $this->variableEnergyLogID = $this->setVariableId($this->variablename."_Energy",$this->variableLogID,2,'~Electricity');
                    $this->variablePowerLogID = $this->setVariableId($this->variablename."_Power",$this->variableLogID,2,'~Power');
                    $this->variableTimeLogID = $this->setVariableId($this->variablename."_ChangeTime",$this->variableLogID,1,'~UnixTimestamp');
                    if ($debug) echo "   ".$this->variablename."_ChangeTime hat OID ".$this->variableTimeLogID."\n";
                    if (GetValue($this->variableTimeLogID) == 0) SetValue($this->variableTimeLogID,time());
                    }
                else 
                    {
                    if ($debug) 
                        {
                        echo "\n**************************\n";
                        echo "Attention, Variable ID ".$variable." (".IPS_GetName($variable).") in Configuration IPSDetectHeatControlHandler_GetEventConfiguration() als Erweiterung Power->ppp not available !\n";
                        print_r($this->powerConfig);
                        }
                    $this->powerConfig=Null;        // damit bei HeatControl_LogValue keine Berechnungen statt finden
                    }	
				}

			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["HeatControlLog"]))  { $directory=$directories["LogDirectories"]["HeatControlLog"]; }
			else {$directory="HeatControl/"; }
            $systemDir     = $dosOps->getWorkDirectory(); 	
			$dosOps->mkdirtree($systemDir.$directory);
			$filename=$directory.$this->variablename."_HeatControl.csv";
			parent::__construct($filename,$this->HeatControlNachrichtenID);
			}

		/* hier wird der Wert gelogged, Wert immer direkt aus der Variable nehmen, der übergebene Wert hat nur für Remote Write aber nicht für das Logging einen EInfluss 
         */
		function HeatControl_LogValue($value=Null)
			{
			// result für Ausgabe in IPSLogger formatieren
			$variabletyp=IPS_GetVariable($this->variable);
			if ( ($variabletyp["VariableProfile"]!="" && ($value == Null) ))
				{
				$result=GetValueFormatted($this->variable);
				$value=GetValue($this->variable);
				}
			else
				{
				if ($value == Null) { $value=GetValue($this->variable); }
				$result=number_format($value,2,',','.')." %";				
				}
			$results=$result;

            /* Zielregister Wert setzen, ID wurde im construct angelegt
             */
			SetValue($this->variableLogID,$value);

			// Leistungs und Energiewerte berechnen
			if ($this->powerConfig<>Null)
				{
				$unchanged=time()-GetValue($this->variableTimeLogID);
				$oldvalue=GetValue($this->variableLogID);
				$unchangedformat="Sekunden";
				$unchangedvalue=$unchanged;
				if ($unchangedvalue>100) { $unchangedvalue=$unchangedvalue/60; $unchangedformat="Minuten"; }
				if ($unchangedvalue>100) { $unchangedvalue=$unchangedvalue/60; $unchangedformat="Stunden"; }
				echo "HeatControl_Logging:HeatControl_LogValue, neuer Wert fuer ".$this->variablename."(".$this->variable.") ist ".$value." %. Alter Wert war : ".$oldvalue." % unverändert für ".number_format($unchangedvalue,2,',','.')." ".$unchangedformat.".\n";

				/* Werte sind in Integer Prozenten also 0 bis 100, daher Wert zusätzlich durch 100, alterWert/100*ZeitinSekunden/60/60*Leistung/1000 ergibt kWh , Leistung in kW*/
				SetValue($this->variableTimeLogID,time());
				SetValue($this->variableEnergyLogID,(GetValue($this->variableEnergyLogID)+$oldvalue/100*$unchanged/60/60/1000*$this->powerConfig[$this->variable]));
				SetValue($this->variablePowerLogID,($value/100/1000*$this->powerConfig[$this->variable]));
				echo 'HeatControl Logger für VariableID '.$this->variable.' ('.IPS_GetName($this->variable).') mit Wert '.$value.' % und '.$this->powerConfig[$this->variable].' W ergibt '.GetValue($this->variablePowerLogID).' kW und bislang '.GetValue($this->variableEnergyLogID)." kWh.\n";
				if ($this->CheckDebugInstance($this->variable)) IPSLogger_Inf(__file__, 'HeatControl Logger für VariableID '.$this->variable.' ('.IPS_GetName($this->variable)."/".IPS_GetName(IPS_GetParent($this->variable)).') mit Wert '.$value.' % und '.$this->powerConfig[$this->variable].' W ergibt '.GetValue($this->variablePowerLogID).' kW und bislang '.number_format(GetValue($this->variableEnergyLogID),2,',','.').' kWh.');	
				$results=$result.";".$unchanged.";".number_format(GetValue($this->variablePowerLogID),2,',','.').' kW;'.number_format(GetValue($this->variableEnergyLogID),2,',','.')." kWh";
				}				
			
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* Detect Movement kann auch Leistungswerte agreggieren */
				IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
				IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
				$DetectHeatControlHandler = new DetectHeatControlHandler();
				//print_r($DetectMovementHandler->ListEvents("Motion"));
				//print_r($DetectMovementHandler->ListEvents("Contact"));

				$groups=$DetectHeatControlHandler->ListGroups("HeatControl",$this->variable);       // nur die Gruppen ausgeben in denen die variable ID auch vorkommt
				foreach($groups as $group=>$name)
					{
					echo "Gruppe ".$group." behandeln.\n";
					$config=$DetectHeatControlHandler->ListEvents($group);
					$status=(float)0;
					$power=(float)0;
					$count=0;
					foreach ($config as $oid=>$params)
						{
						$mirrorID=$DetectHeatControlHandler->getMirrorRegister($oid);
						$variablename=IPS_GetName($mirrorID);
						$mirrorPowerID=@IPS_GetObjectIDByName($variablename."_Power",$mirrorID);						
						$status+=GetValue($oid);
						if ($mirrorPowerID) $power+=GetValue($mirrorPowerID);
						$count++;
						/* Ausgabe der Berechnung der Gruppe */
						echo "OID: ".$oid;
						echo " Name: ".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),50)."Status (LEVEL | POWER) ".GetValue($oid)." ".$status." | ";
						if ($mirrorPowerID) echo GetValue($mirrorPowerID);
                        else echo "   0  ";
                        echo "   ".$power."\n";
						}
					if ($count>0) { $status=$status/$count; }
					echo "Gruppe ".$group." hat neuen Status : ".$status." | ".$power."\n";
					/* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
					$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->HeatControlAuswertungID,100, "~Power", null, null);
					echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID."\n";
					SetValue($statusID,$power);
					}
				}
			
			parent::LogMessage($results);
			parent::LogNachrichten($this->variablename." mit Wert ".$result);
			}

        /* set aus brauchbaren methoden, hat man halt so */

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

        /* die schönste Zusammenfassung
         * wenn DetectMovement und es ist ein Boolean
         *      wie unten und zusätzlich
         *      Ereignisspeicher    EreignisID
         *      Gesamt_Ereignisspeicher   GesamtID
         *      Gesamt_Ereigniszaehler    GesamtCountID
         *
         * wenn nicht und es ist ein Bolean
         *      wie unten und zusätzlich
         *      variableDelayLogID  variableDelayLogID      
         *
         * sonst
         *      variableID          variable
         *      profile             variableProfile
         *      type                variableTye
         *      variableLogID       variableLogID
         */
        public function getVariableOIDLogging()
            {
            if ( (isset ($this->installedmodules["DetectMovement"]))  )        // Zusatzberechnungen, später anschauen
                {
                $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "variableLogID" => $this->variableLogID
                //, "variableDelayLogID" => $this->variableDelayLogID, "Ereignisspeicher" => $this->EreignisID, "Gesamt_Ereignisspeicher" => $this->GesamtID, "Gesamt_Ereigniszaehler" => $this->GesamtCountID
                     ];
                }
            else $result = ["variableID" => $this->variable, "profile" => $this->variableProfile, "type" => $this->variableType, "variableLogID" => $this->variableLogID];

            return $result;
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