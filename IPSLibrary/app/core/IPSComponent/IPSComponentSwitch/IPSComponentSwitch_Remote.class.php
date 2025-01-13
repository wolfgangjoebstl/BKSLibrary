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

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('IPSComponentSwitch.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSwitch');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

	class IPSComponentSwitch_Remote extends IPSComponentSwitch {

		private $installedmodules;
		private $remoteOID;	

		private $remServer;			
			
		/**
		 * @public
		 *
		 * Initialisierung eines IPSComponentSwitch_Remote Objektes
		 *
		 * @param $var1   OID der STATE Variable des Schalters
		 */
		public function __construct($var1=Null) 
			{
			$this->remoteOID    = $var1;
			//echo "IPSComponentSensor_Remote: Construct Switch with ".$var1.".\n";			
			
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
			//echo "Switch Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";
	   		IPSLogger_Dbg(__file__, 'HandleEvent: Switch Message Handler für VariableID '.$variable.' mit Wert '.$value);			
			
			$log=new Switch_Logging($variable);
			$result=$log->Switch_LogValue();
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
		public function GetComponentParams() {
			return get_class($this).','.$this->instanceId;
		}

		/**
		 * @public
		 *
		 * Zustand Setzen 
		 *
		 * @param boolean $value Wert für Schalter
		 * @param integer $onTime Zeit in Sekunden nach der der Aktor automatisch ausschalten soll
		 */
		public function SetState($value, $onTime=false) {
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