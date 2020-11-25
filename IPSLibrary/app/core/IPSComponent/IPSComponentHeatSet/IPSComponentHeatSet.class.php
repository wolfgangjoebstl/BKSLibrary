<?
	/**@addtogroup ipscomponent
	 * @{
	 *
 	 *
	 * @file          IPSComponentHeatSet.class.php
	 * @author        Wolfgang Jöbstl und Andreas Brauneis
	 *
	 *
	 */

   /**
    * @class IPSComponentHeatSet
    *
    * Definiert ein IPSComponentHeatSet
    *
    */

	IPSUtils_Include ('IPSComponent.class.php', 'IPSLibrary::app::core::IPSComponent');

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	abstract class IPSComponentHeatSet extends IPSComponent {

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
		abstract public function HandleEvent($variable, $value, IPSModuleHeatSet $module);

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param boolean $value Wert für Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll (ACHTUNG: wird nicht von
		 *                        allen Hardware Komponenten unterstützt).
		 */
		abstract public function SetState($value, $level);

		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand des Dimmers
		 *
		 * @return integer aktueller Dimmer Zustand  
		 */
		abstract public function GetLevel();
		
		/**
		 * @public
		 *
		 * Liefert aktuellen Zustand
		 *
		 * @return boolean aktueller Schaltzustand  
		 */
		abstract public function GetState();

		/**
		 * @public
		 *
		 * Hinauffahren der Beschattung
		 */
		abstract public function MoveUp();

		/**
		 * @public
		 *
		 * Hinunterfahren der Beschattung
		 */
		abstract public function MoveDown();

		/**
		 * @public
		 *
		 * Stop
		 */
		abstract public function Stop();
				
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
					//echo "WriteValueRemote Wert :".$val." Anzahl ",count($para)." \n";
					if (count($para)==2)
						{
                        //echo "WriteValueRemote Wert :".$val." Anzahl ",count($para)." \n";
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

	class HeatSet_Logging extends Logging
		{
        private $startexecute;                  /* interne Zeitmessung */
		protected $installedmodules;                    /* installierte Module */
        protected $debug;                           /* zusaetzliche Echo Ausgaben */

		private $variable;
		public $variableLogID;					/* ID der entsprechenden lokalen Spiegelvariable */
        protected $DetectHandler;               /* DetectMovement/HeatSet ... ist auch ein Teil der Aktivitäten */

		public $variableEnergyLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den Energiewert*/
		public $variablePowerLogID;			/* ID der entsprechenden lokalen Spiegelvariable für den Energiewert*/
				
		private $HeatSetAuswertungID;
        private $HeatSetNachrichtenID;

		private $powerConfig;					/* Powerwerte der einzelnen Heizkoerper, Null wenn Configfile nicht vorhanden */

		//protected $configuration $variablename,$CategoryIdData;           // in der parent class definiert
        // protected $DetectHandler,$archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */           


        /* HeatSet_Logging construct
         * die wichtigsten Variablen initialisieren und anlegen
         */
				
		function __construct($variable,$variablename=Null,$variableTypeReg="unknown", $debug=false)
			{
            $this->startexecute=microtime(true);                 
			$this->debug = $debug;

            /************** INIT */
            $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
			/**************** installierte Module und verfügbare Konfigurationen herausfinden */
			$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
			$this->installedmodules=$moduleManager->GetInstalledModules();
            if ( (isset ($this->installedmodules["DetectMovement"])) && false)   // DetectHeatSetHandler gibt es keinen   
                {
                /* Detect Movement kann auch Leistungswerte agreggieren */
                IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                $this->DetectHandler = new DetectHeatSetHandler();
                }
            $dosOps= new dosOps();                

            $this->variablename = $this->getVariableName($variable, $variablename, $this->debug);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config (noch nicht implementiert) oder selber bestimmen
            if ($this->debug) echo "HeatSet_Logging:construct for Variable ID : ".$variable." und Spiegelregister Variablename : ".$this->variablename."\n";

			/* Find Data category of IPSComponent Module to store the Data */				
			$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
			$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			if ($this->debug) echo "  Kategorien im CustomComponents Datenverzeichnis:".$this->CategoryIdData."   ".IPS_GetName($this->CategoryIdData)."\n";

			/* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
			$this->HeatSetNachrichtenID=$this->CreateCategoryNachrichten("HeatSet",$this->CategoryIdData);
			$this->HeatSetAuswertungID=$this->CreateCategoryAuswertung("HeatSet",$this->CategoryIdData);;
			
			/* lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
			if ($variable<>null)
				{
                $this->variable = $variable;
				if ($this->debug) echo "Lokales Spiegelregister als Float auf ".$this->variablename." unter Kategorie ".$this->HeatSetAuswertungID." ".IPS_GetName($this->HeatSetAuswertungID)." anlegen.\n";
                $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->HeatSetAuswertungID,2,'TemperaturSet');                   // $this->variableLogID schreiben
                IPS_SetHidden($this->variableLogID,false);
                }

			//echo "Uebergeordnete Variable : ".$this->variablename."\n";
			$directories=get_IPSComponentLoggerConfig();
			if (isset($directories["LogDirectories"]["HeatSetLog"])) { $directory=$directories["LogDirectories"]["HeatSetLog"]; }
			else {$directory="C:/Scripts/HeatSet/"; }	
			$dosOps->mkdirtree($directory);
			$filename=$directory.$this->variablename."_HeatSet.csv";
			parent::__construct($filename,$this->HeatSetNachrichtenID);
            echo "~~~~~~~~~~~~~~~~~~~\n";
			}

		/* hier wird der Wert gelogged, Wert immer direkt aus der Variable nehmen, der übergebene Wert hat nur für Remote Write aber nicht für das Logging einen EInfluss */

		function HeatSet_LogValue($value=Null, $subregister=false)
			{
            if ($this->debug) echo "HeatSet_LogValue($value, $subregister) aufgerufen.\n";
			// result formatieren
			$variabletyp=IPS_GetVariable($this->variable);
			if ( ($variabletyp["VariableProfile"]!="" && ($value == Null) ))
				{
				$result=GetValueFormatted($this->variable);
				$value=GetValue($this->variable);
				}
			else
				{
				if ($value == Null) { $value=GetValue($this->variable); }
				$result=number_format($value,2,',','.')." °C";				
				}
            if ($subregister)
                {
                $this->variableSubID=$this->setVariableId($this->variablename."_".$subregister,$this->variableLogID,1,'');
                SetValue($this->variableSubID,$value);
                }				
            else
                {
                $unchanged=time()-$variabletyp["VariableChanged"];
                $oldvalue=GetValue($this->variableLogID);
                SetValue($this->variableLogID,$value);
                echo "Neuer Wert fuer ".$this->variablename."(".$this->variable.") ist ".$value." °C. Alter Wert war : ".$oldvalue." unverändert für ".$unchanged." Sekunden.\n";

                // Leistungs und Energiewerte berechnen
                if ( ($this->powerConfig<>Null) && false)
                    {
                    /* Werte sind in Integer Prozenten also 0 bis 100, daher Wert zusätzlich durch 100 */
                    SetValue($this->variableEnergyLogID,(GetValue($this->variableEnergyLogID)+$oldvalue/100*$unchanged/60/60/1000*$this->powerConfig[$this->variable]));
                    SetValue($this->variablePowerLogID,($value/100/1000*$this->powerConfig[$this->variable]));					
                    }
                
                if ( (isset ($this->installedmodules["DetectMovement"])) && false )
                    {
                    /* Detect Movement kann auch Leistungswerte agreggieren */
                    IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                    IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
                    $DetectHeatSetHandler = new DetectHeatSetHandler();
                    //print_r($DetectMovementHandler->ListEvents("Motion"));
                    //print_r($DetectMovementHandler->ListEvents("Contact"));

                    $groups=$DetectHeatSetHandler->ListGroups();
                    foreach($groups as $group=>$name)
                        {
                        echo "Gruppe ".$group." behandeln.\n";
                        $config=$DetectHeatSetHandler->ListEvents($group);
                        $status=(float)0;
                        $count=0;
                        foreach ($config as $oid=>$params)
                            {
                            $status+=GetValue($oid);
                            $count++;
                            //echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".GetValue($oid)." ".$status."\n";
                            echo "OID: ".$oid." Name: ".str_pad(IPS_GetName($oid),30)."Status: ".GetValue($oid)." ".$status."\n";
                            }
                        if ($count>0) { $status=$status/$count; }
                        echo "Gruppe ".$group." hat neuen Status : ".$status."\n";
                        /* Herausfinden wo die Variablen gespeichert, damit im selben Bereich auch die Auswertung abgespeichert werden kann */
                        //$statusID=CreateVariable("Gesamtauswertung_".$group,2,$this->TempAuswertungID,100, "~Temperature", null, null);
                        echo "Gesamtauswertung_".$group." ist auf OID : ".$statusID."\n";
                        //SetValue($statusID,$status);
                        }
                    }
                }
			parent::LogMessage($result);
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