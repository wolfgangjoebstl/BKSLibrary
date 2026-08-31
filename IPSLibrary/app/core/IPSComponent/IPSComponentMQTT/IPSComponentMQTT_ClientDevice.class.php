<?php
    /**@addtogroup ipscomponent
     * @{
     *
      *
     * @file          IPSComponentMQTT_ClientDevice.class.php
     * @author        Wolfgang Joebstl, inspiriert von Andreas Brauneis
     *
     *
     */

	/**
	 * @class IPSComponentMQTT_ClientDevice
	 *
	 * Definiert ein IPSComponentMQTT_ClientDevice Object, das ein IPSComponentMQTT Object fuer MQTT Client Devices implementiert.
	 *
     *
     * was macht dann IPSComponent noch, siehe weiter unten den Überblick der implementierten Funktionen
     * Die Befehle werden nur mehr mit RequestAction und der passenden VariablenID umgesetzt
     *
     *
	 * Überblick über implementierte Funktionen:
     *
     *      __construct             $lampOID kann man beim construct übergeben werden
     *      HandleEvent             Leermeldung
     *      GetComponentParams      Info über class geben
     * 
     *
     *
	 */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('IPSComponentMQTT.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentMQTT');

	IPSUtils_Include ("IPSLogger.inc.php", "IPSLibrary::app::core::IPSLogger");
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
    
	class IPSComponentMQTT_ClientDevice extends IPSComponentMQTT {

		private $mqttOID;
        private $statusId=false,$helligkeitId=false,$farbeId=false,$farbtemperaturId=false;             // die einzelnen Variablen, werden jetzt direkt gesetztm´, nicht mehr über Modul
			
    
        /**
         * @public
         *
         * Initialisierung eines IPSComponentMQTT_ClientDevice Objektes
         * CheckEvent(24934) : ["OnChange","IPSComponentMQTT_ClientDevice,,,","IPSModuleMQTT_ClientDevice,"]
         * es ist bereits Value registriert, es müssen keine Children mehr berücksichtigt werden, optional kann man beim registrieren die Instanz mitgeben
         * 
		 * 
		 *
		 * 
		 *
		 *
		 *
         */
		public function __construct($mqttOID) 
			{
			$this->mqttOID = $mqttOID;
            if (is_numeric($this->mqttOID))
                {
                echo "construct get MQTT Client Device ID : ".$this->mqttOID;
                $cids = IPS_GetChildrenIDs($this->mqttOID);           // für jede Instanz die Children einsammeln
                foreach($cids as $cid)
                    {
                    $regName=IPS_GetName($cid);
                    if ($regName=="Value")          $this->statusId=$cid;           // Value, das ist der Wert der sich ändert, hier wahrscheinlich JSON Format
                    }
                }
            }

        /**
         * @public
         *
         * Function um Events zu behandeln, diese Funktion wird vom IPSMessageHandler aufgerufen, um ein aufgetretenes Event 
         * an das entsprechende Module zu leiten.
         *
         * @param integer $variable ID der auslösenden Variable
         * @param string $value Wert der Variable
         * @param IPSModuleRGB $module Module Object an das das aufgetretene Event weitergeleitet werden soll
         */
        public function HandleEvent($variable, $value, IPSModuleMQTT $module, $debug=false)
            {
            //if variable is type status
            //$debug=true; echo "IPSComponentRGB_PHUE2::HandleEvent($variable, $value  :  ".$this->statusId."  ".$this->helligkeitId."  ".$this->farbtemperaturId."  ".$this->farbeId."\n";
            $log=new MQTT_Logging($variable);         		//echo "Logging.\n";
			            
            if ($this->statusId == $variable)                   // Value, alles in Ordnung        
                {
                $result=$log->MQTT_LogValue($value);
                //$result=$log->Switch_LogValue("State");                    
                //$module->SyncState($value, $this, $debug);               // debug level
                }
            else echo "IPSComponentMQTT_ClientDevice::HandleEvent, do not know VariableID $variable \n";            
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
            //return get_class($this).','.$this->bridgeIP.','.$this->hueKey.','.$this->lampNr.','.$this->modelID;
			return (get_class($this).','.$this->mqttOID.',');
        }


        public function get_Ids($type)          // überarbeiten
            {
            switch (strtoupper($type))
                {
                case "LEVEL":
                    return ($this->helligkeitId);
                case "STATE":
                    return ($this->statusIdId);
                default:
                    return(false);
                }
            }     

        /**
         * @public
         * aus Stromheizung die Variable setzen
         * vielleicht für ReqestAction verwenden ?
         *
         */
		public function SetState($power, $color=false, $level=512, $ambience=false) 
			{
            echo "SetState nicht implementiert";
		    }
				

		/**
		 *  @brief Sets the alert state. 'select' blinks once, 'lselect' blinks repeatedly, 'none' turns off blinking
		 *  
		 */
		public function SetAlert( $alert_type = 'select' ) 
            {
            echo "SetAlert nicht implementiert";
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

	class MQTT_Logging extends Logging
		{
		//private $variable, $variableLogID;

		protected $MQTTAuswertungID;
		protected $MQTTNachrichtenID;


		// $configuration, $variablename, $CategoryIdData

		protected $installedmodules;              /* installierte Module */
        protected $DetectHandler;		        /* Unterklasse */        
        protected $archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */          
				
		function __construct($variable=false,$variablename=Null,$variableTypeReg="Switch",$debug=false)
			{
            if ( ($this->GetDebugInstance()) && ($this->GetDebugInstance()==$variable) ) $this->debug=true;
            else $this->debug=$debug;

            $this->debug=true;
            if ($this->debug) echo "   MQTT_Logging, construct : ($variable,$variablename,$variableTypeReg).\n";

            $this->constructFirst();        // sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()

            if ($variable===false) $this->do_create();
            else
                {
                //echo "Construct IPSComponentSswitch_Remote Logging for Variable ID : ".$variable."\n";
                $result=IPS_GetObject($variable);
                $this->variablename=IPS_GetName((integer)$result["ParentID"]);			// Variablenname ist immer der Parent Name 
            
                // Get Category to store the Move-LogNachrichten und Spiegelregister	
                $this->MQTTNachrichtenID  = $this->CreateCategoryNachrichten("MQTT",$this->CategoryIdData);
                $this->MQTTAuswertungID   = $this->CreateCategoryAuswertung("MQTT",$this->CategoryIdData);;
                if ($this->debug) echo "  MQTT_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   ".IPS_GetName($this->CategoryIdData)." anlegen : [".$this->MQTTNachrichtenID.",".$this->MQTTAuswertungID."]\n";

                // lokale Spiegelregister aufsetzen 
                if ($variable<>null)
                    {
                    $this->variable=$variable;   
                    if ($this->debug) echo "      Lokales Spiegelregister als String auf ".$this->variablename." ".$this->MQTTAuswertungID." ".IPS_GetName($this->MQTTAuswertungID)." anlegen.\n";
                    $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->MQTTAuswertungID,3,"");                   // $this->variableLogID schreiben
                    }
                $directory=$this->configuration["LogDirectories"]["SwitchLog"];
                $this->filename=$directory.str_replace(array('<', '>', ':', '"', '/', '\\', '|', '?', '*'), '', $this->variablename)."_Switch.csv";   
                }

			parent::__construct($this->filename,$this->MQTTNachrichtenID);          // Logging class
			}

        /* do_create
         * Trennung von create und init, create ist einmal beim Anlegen des Moduls und init beim construct
         * create ist ohne Variable, init macht das construct für eine bestimmte Variable
         */
        protected function do_create()          // Initialisiserung getrent vom Operativen
            {
            $dosOps= new dosOps();
		
			// Create Category to store the Move-LogNachrichten und Spiegelregister	
			$this->MQTTNachrichtenID=$this->CreateCategoryNachrichten("MQTT",$this->CategoryIdData);
			$this->MQTTAuswertungID=$this->CreateCategoryAuswertung("MQTT",$this->CategoryIdData);;

            $directory=$this->configuration["LogDirectories"]["MQTTLog"];
            $dosOps->mkdirtree($directory);
            $this->filename=false;
            }

        function getNachrichtenID()
            {
            return ($this->MQTTNachrichtenID);    
            }

        function getAuswertungID()
            {
            return ($this->MQTTAuswertungID);    
            }

		function MQTT_LogValue($param=false)
			{
			$result=GetValue($this->variable);
			SetValue($this->variableLogID,GetValue($this->variable));          // nur Status wird gespiegelt
			echo "Neuer Wert fuer $param ".$this->variablename." ist ".GetValue($this->variable).", ".$this->variableLogID." ist updated.\n";
			//parent::LogMessage($result);
			parent::LogNachrichten($result);
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