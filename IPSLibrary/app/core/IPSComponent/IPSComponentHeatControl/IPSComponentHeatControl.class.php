<?
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
    * Definiert ein IPSComponentHeatControl
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
		private $variable;
		private $variablename;
		public $variableLogID;					/* ID der entsprechenden lokalen Spiegelvariable */
		
		public $variableEnergyLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den Energiewert */
		public $variablePowerLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den leistungswert */
		public $variableTimeLogID;				/* ID der entsprechenden lokalen Spiegelvariable für den Zeitpunkt der letzten Änderung */
				
		private $HeatControlAuswertungID;
		private $HeatControlNachrichtenID;

		private $powerConfig;					/* Powerwerte der einzelnen Heizkoerper, Null wenn Configfile nicht vorhanden */

		private $configuration;
		private $CategoryIdData;          

        private $startexecute;                  /* interne Zeitmessung */        

		/* Unter Klassen */
		
		protected $installedmodules;                    /* installierte Module */
        protected $DetectHandler;		                /* Unterklasse */
        protected $archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */          
				
        /* HeatControl_Logging construct
         * die wichtigsten Variablen initialisieren und anlegen
         */
                
		function __construct($variable,$variablename=Null)
			{
            $this->startexecute=microtime(true);                 
			echo "HeatControl_Logging:construct for Variable ID : ".$variable."\n";

            /************** INIT */
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
			/**************** installierte Module und verfügbare Konfigurationen herausfinden */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                /* Detect Movement agreggiert die Bewegungs Ereignisse (oder Verknüpfung) */
                //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\DetectMovement\DetectMovementLib.class.php");
                //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DetectMovement\DetectMovement_Configuration.inc.php");
                IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                $this->DetectHandler = new DetectHeatControlHandler();
                } 

            $dosOps= new dosOps();

            $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
			echo "  Construct IPSComponentSensor HeatControl Logging for Variable ID : ".$this->variable." mit dem Namen ".$this->variablename."\n";

			/* Find Data category of IPSComponent Module to store the Data */				
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorie Data im CustomComponents Datenverzeichnis: ".$this->CategoryIdData."  (".IPS_GetName($this->CategoryIdData).")\n";

			/* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
			$this->HeatControlNachrichtenID=$this->CreateCategoryNachrichten("HeatControl",$this->CategoryIdData);
			$this->HeatControlAuswertungID=$this->CreateCategoryAuswertung("HeatControl",$this->CategoryIdData);;
						
			/* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
			if ($variable<>null)
				{
    			$this->variable=$variable;
				echo "  Lokales Spiegelregister als Integer auf ".$this->variablename." unter Kategorie ".$this->HeatControlAuswertungID." ".IPS_GetName($this->HeatControlAuswertungID)." anlegen.\n";
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->HeatControlAuswertungID,1,'~Intensity.100');                   // $this->variableLogID schreiben
                IPS_SetHidden($this->variableLogID,false);
				
				$this->powerConfig=Null;
				if (function_exists('get_IPSComponentHeatConfig'))
					{
					$this->powerConfig=get_IPSComponentHeatConfig()["HeatingPower"];
					if ( isset($this->powerConfig[$variable]) )
						{
						$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
						echo "   Lokales Spiegelregister für Energie- und Leistungswert unterhalb Variable ID ".$this->variableLogID." und Parent Kategorie ".IPS_GetName($this->HeatControlAuswertungID)." anlegen.\n";
						/* Parameter : $Name, $Type, $Parent, $Position, $Profile, $Action=null */
						//$this->variableEnergyLogID=CreateVariable($this->variablename."_Energy",2,$this->variableLogID, 10, "~Electricity", null, null );  /* 1 steht für Integer, 2 für Float, alle benötigten Angaben machen, sonst Fehler */
						//$this->variablePowerLogID=CreateVariable($this->variablename."_Power",2,$this->variableLogID, 10, "~Power", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
						//$this->variableTimeLogID=CreateVariable($this->variablename."_Changetime",1,$this->variableLogID, 10, "~UnixTimestamp", null, null );  /* 1 steht für Integer, alle benötigten Angaben machen, sonst Fehler */
                        $this->variableEnergyLogID = $this->setVariableId($this->variablename."_Energy",$this->variableLogID,2,'~Electricity');
                        $this->variablePowerLogID = $this->setVariableId($this->variablename."_Power",$this->variableLogID,2,'~Power');
                        $this->variableTimeLogID = $this->setVariableId($this->variablename."_ChangeTime",$this->variableLogID,1,'~UnixTimestamp');
						if (GetValue($this->variableTimeLogID) == 0) SetValue($this->variableTimeLogID,time());
						}
					else 
						{
						echo "Attention, Variable ID ".$variable." (".IPS_GetName($variable).") in Configuration not available !\n";
						$this->powerConfig=Null;
						}	
					}					
				}

			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["HeatControlLog"]))  { $directory=$directories["LogDirectories"]["HeatControlLog"]; }
			else {$directory="C:/Scripts/HeatControl/"; }	
			$dosOps->mkdirtree($directory);
			$filename=$directory.$this->variablename."_HeatControl.csv";
			parent::__construct($filename,$this->HeatControlNachrichtenID);
			}

		/* hier wird der Wert gelogged, Wert immer direkt aus der Variable nehmen, der übergebene Wert hat nur für Remote Write aber nicht für das Logging einen EInfluss */

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

            /* Spiegelregister Wert setzen */
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
				IPSLogger_Inf(__file__, 'HeatControl Logger für VariableID '.$this->variable.' ('.IPS_GetName($this->variable)."/".IPS_GetName(IPS_GetParent($this->variable)).') mit Wert '.$value.' % und '.$this->powerConfig[$this->variable].' W ergibt '.GetValue($this->variablePowerLogID).' kW und bislang '.number_format(GetValue($this->variableEnergyLogID),2,',','.').' kWh.');	
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