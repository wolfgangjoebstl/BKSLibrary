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
	 * Definiert ein IPSComponentRGB_PHUE2 Object, das ein IPSComponentRGB Object fuer PhilipsHUE implementiert.
	 *
	 * verwendet nun das IPSModul "Philips HUE V2" , mit der Besonderheit das es keine Modulspezifischen Funktionen mehr gibt
     * die Steuerung erfolgt direkt über das Webfront, entweder Kachel oder Classic Version
     * Gruppen, Zonen, Räume werden direkt aus den Mitteln von Hue unterstützt
     *
     * was macht dann IPSComponent noch, siehe weiter unten den Überblick der implementierten Funktionen
     * Die Befehle werden nur mehr mit RequestAction und der passenden VariablenID umgesetzt
     *
     * die Schwierigkeit ist herauszufinden um welchen Lampentyp es sich handelt
     * es wurden dazu die in der Instanz vorhanden Register mit ihren IDs und Namen ausgelesen 
     * Anhand der Registernamen und ihrer Werte laesst es sich grundsätzlich erkennen
     *      Wenn Power false ist einfach ausschalten, das funktioniert immer gleich 
     *      Mit SetState wird übergeben ob es sich um eine Ambience oder RGB Lampe handelt
     *      Bei AMbience automatisch umrechnen, 
     * IPS_Heat (Stromheizung) setzt die Befehle zum Setzen in der richtigen Art und Weise 
     *
     * Versionsgeschichte:
     * Dann sind Anpassungen für das SymconHUE Modul erfolgt, einfachere Ansteuerung der Hue Funktionen über die Bridge und nicht mehr direkt
	 * Routinen vom initialen HUE Modul (direkte Adressierung) sind zwar noch vorhanden, werden aber nicht mehr verwendet
     * cgpoint class wird nicht mehr verwendet, trotzdem in der Funktion beinhaltet, damit keine Überschneidungen mit dem HUE Modul entstehen. umbennannt auf cgpoint2
	 *
	 *  es wird nur construct und setState aufgerufen
     *
     * PHUE_AlertSet($InstanceID, $Value)           Mit dieser Funktion ist es möglich einen Alarm für eine Lampe / Gruppe zu setzen
     * PHUE_CTSet($InstanceID, $Value)              Mit dieser Funktion ist es möglich die Farbtemperatur der Lampe bzw. der Gruppe zu ändern. Der Wert wird in Integer angegeben werden.
     * PHUE_ColorSet($InstanceID, $Value)           Mit dieser Funktion ist es möglich die Farbe der Lampe bzw. der Gruppe zu ändern. Der Wert wird in Hex angegeben werden.
     * PHUE_DimSet($InstanceID, $Value)             Mit dieser Funktion ist es möglich das Gerät bzw. die Gruppe zu dimmen.
     * PHUE_EffectSet($InstanceID, $Value)          Mit dieser Funktion ist es möglich einen Effekt für die Lampe bzw. Gruppe zu aktiveren.
     * PHUE_GetState($InstanceID)                   Mit dieser Funktion ist es möglich den aktuellen Status der Lampe / Gruppe abzufragen.
     * PHUE_SceneSet($InstanceID, $Value)           Mit dieser Funktion ist es möglich eine Szene für die Gruppe zu aktiveren.
     * PHUE_SwitchMode($InstanceID, $Value)         Mit dieser Funktion ist es möglich das Gerät ein- bzw. auszuschalten.
     *
	 * Überblick über implementierte Funktionen:
     *
     *      __construct             $lampOID kann man beim construct übergeben werden
     *      HandleEvent             Leermeldung
     *      GetComponentParams      Info über class geben
     * 
     *      SetState        Zustand Setzen
     *      SetAlert        Sets the alert state. 'select' blinks once, 'lselect' blinks repeatedly, 'none' turns off blinking
     *
     *      calculateXY
     *      getColorPointsForModel
     *
     * depricated functions
     *      SetStateHUE
	 */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ('IPSComponentMQTT.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentRGB');

	IPSUtils_Include ("IPSLogger.inc.php", "IPSLibrary::app::core::IPSLogger");
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');
    
	class IPSComponentMQTT_ClientDevice extends IPSComponentMQTT {

		private $lampOID;
        private $statusId=false,$helligkeitId=false,$farbeId=false,$farbtemperaturId=false;             // die einzelnen Variablen, werden jetzt direkt gesetztm´, nicht mehr über Modul
			
    
        /**
         * @public
         *
         * Initialisierung eines IPSComponentRGB_PHUE Objektes
         * basiert nun auf dem Philps HUE Modul
         * vorher war es das Symcon HUE Modul und ein proprietäres Modul. Die Bridge bzw. vorher die Schlüssel müssen nicht mehr übergeben werden, sind in der Config ooder in der verbundenen Bridge Instanz 
		 * Bridge ID und alles IP und key spezielle ist damit bereits abgedeckt
		 *
		 * HueBridge ist die I/O Instanz bei der die Parameter der Hue Bridge hinterlegt sind.
		 *
		 *
		 *
         */
		public function __construct($lampOID) 
			{
			$this->lampOID = $lampOID;
            //echo "construct get ".$this->lampOID;
            $cids = IPS_GetChildrenIDs($this->lampOID);           // für jede Instanz die Children einsammeln
            foreach($cids as $cid)
                {
                $regName=IPS_GetName($cid);
                if ($regName=="Status")         $this->statusId=$cid;
                if ($regName=="Helligkeit")     $this->helligkeitId=$cid;
                if ($regName=="Farbtemperatur") $this->farbtemperaturId=$cid;
                if ($regName=="Farbe")          $this->farbeId=$cid;                  
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
        public function HandleEvent($variable, $value, IPSModuleRGB $module, $debug=false)
            {
            //if variable is type status
            //$debug=true; echo "IPSComponentRGB_PHUE2::HandleEvent($variable, $value  :  ".$this->statusId."  ".$this->helligkeitId."  ".$this->farbtemperaturId."  ".$this->farbeId."\n";
            $log=new Lamp_Logging($variable);         		//echo "Logging.\n";
			            
            if ($this->statusId == $variable)               
                {
                $result=$log->Switch_LogValue("State");                    
                $module->SyncState($value, $this, $debug);               // debug level
                }
            elseif ($this->helligkeitId == $variable)       
                {
                $result=$log->Switch_LogValue("Brightness");                    
                $module->SyncBrightness($value, $this, $debug);
                }
            elseif ($this->farbtemperaturId == $variable)   
                {
                $result=$log->Switch_LogValue("ColTemperature");                    
                $module->SyncAmbience($value, $this, $debug);            
                }
            elseif ($this->farbeId == $variable)            
                {
                $result=$log->Switch_LogValue("Color");                    
                $module->SyncColor($value, $this, $debug);
                }
            else echo "IPSComponentRGB_PHUE2::HandleEvent, do not know VariableID $variable \n";            
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
			return (get_class($this).','.$this->lampOID.',');
        }


        public function get_Ids($type)
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
         *
         * @brief Zustand Setzen 
         *
         * mit Ambience=true wird $color zu Farbtemperatur in mired
         * mit 2 Parametern wird es zu einer Philips Dimmer Ledlampe
         *
         *
         * @param boolean $power RGB Gerät On/Off
         * @param integer $color RGB Farben (Hex Codierung)
         * @param integer $level Dimmer Einstellung der RGB Beleuchtung (Wertebereich 0-100)
         */
		public function SetState($power, $color=false, $level=512, $ambience=false) 
			{
            $debug=false;
			if (!$power) 
				{
			    if ($debug) echo "IPSComponentRGB_HUE2 SetState mit Power ".($power?"Ein":"Aus")."\n";
				//HUE_SetValue($this->lampOID, "STATE",$power);
                //PHUE_SwitchMode($this->lampOID, $power);
                RequestAction($this->statusId, $power);
				} 
			elseif ($ambience)                      // als Ambience LED Lampe aufgerufen
				{
				//IPSLight is using percentage in variable Level, Hue is using [0..255] 
                if ($color>1000) 
                    {
                    $unit="mired";
                    $color = 1000000/$color;           // probably Kelvin, convert to mired
                    }
                else $unit="Kelvin";
    			if ($debug) echo "IPSComponentRGB_HUE2 SetState ".$this->lampOID." mit Power ".($power?"Ein":"Aus")."  $unit $color  Level $level  Typ ".($ambience?"Ambience":"RGB")."    \n";

                RequestAction($this->statusId, $power);
                RequestAction($this->helligkeitId, $level);
                RequestAction($this->farbtemperaturId, $color);
                //$helligkeitId=false,$farbeId=false,$farbtemperaturId=false;
				//$level = round($level * 2.54);
                //PHUE_SwitchMode($this->lampOID, $power);
                //PHUE_CTSet($this->lampOID, $color);                     // geht das nicht mehr ?
                //PHUE_DimSet($this->lampOID, $level);
				//echo "Level:".$level."\n";				
				}
			elseif ($color===false)                 // als Switch aufgerufen
                {
    			if ($debug) echo "IPSComponentRGB_HUE2 SetState mit Power ".($power?"Ein":"Aus")."  \n";
                RequestAction($this->statusId, $power);

                }
            elseif ($level==512)	                // als Dimmer aufgerufen, color wird als level verwendet
                {
                //$level = round($color * 2.54);
    			if ($debug) echo "IPSComponentRGB_HUE2 SetState mit Power ".($power?"Ein":"Aus")."  Level $color ($level)   \n";
                RequestAction($this->statusId, $power);
                RequestAction($this->helligkeitId, $color);
                //PHUE_SwitchMode($this->lampOID, $power);
                //PHUE_DimSet($this->lampOID, $level);
                }
            else                                    // als RGB aufgerufen
				{
    			if ($debug) echo "IPSComponentRGB_HUE SetState mit Power ".($power?"Ein":"Aus")."  Color ".dechex($color)."   Level ".$level."  Typ ".($ambience?"Ambience":"RGB")."    \n";
                RequestAction($this->statusId, $power);
                RequestAction($this->helligkeitId, $level);
                RequestAction($this->farbeId, $color);
				//$level = round($level * 2.54);
                //PHUE_SwitchMode($this->lampOID, $power);
                //PHUE_ColorSet($this->lampOID, $color);
                //PHUE_DimSet($this->lampOID, $level);
				//echo "IPSComponentRGB_PHUE SetState mit Power ".($power?"Ein":"Aus")."      \n";
				//echo "IPSComponentRGB_PHUE SetState mit  Color ".dechex($color)." \n";
				//echo "IPSComponentRGB_PHUE SetState mit Level ".$level."      \n";

                /*
				$rotDec = (($color >> 16) & 0xFF);
				$gruenDec = (($color >> 8) & 0xFF);
				$blauDec = (($color >> 0) & 0xFF); 
				$color_array = array($rotDec,$gruenDec,$blauDec);
			   
				$modelID = $this->modelID;
			   
			   //Convert RGB to XY values
				$values = $this->calculateXY($color_array, $modelID);
			  
				//IPSLight is using percentage in variable Level, Hue is using [0..255] 
				$level = round($level * 2.54);
				$cmd 	= '"bri":'.$level.', "xy":['.$values->x.','.$values->y.'], "on":true'; 
				HUE_SetValue($this->lampOID, "STATE",$power);
				HUE_SetValue($this->lampOID, "COLOR",$color);
				HUE_SetValue($this->lampOID, "BRIGHTNESS",$level);
                */
				}
		    }
				

		/**
		 *  @brief Sets the alert state. 'select' blinks once, 'lselect' blinks repeatedly, 'none' turns off blinking
		 *  
		 */
		public function SetAlert( $alert_type = 'select' ) 
            {
            echo "SetAlert nicht implementiert";
            //PHUE_AlertSet($this->lampOID, $alert_type);
            /*
			 $type	 	= 'Lights'; //Type of Command
			 $request 	= 'PUT';	 //Type of Request
             $cmd 		= '"alert":"'.$alert_type.'"';
             $this->hue_SendLampCommand($type, $request, $cmd);		//Send command to Hue lamp
             */
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
			$thi

            //$NachrichtenID=$this->do_init($variable,$variablename,null, $variableTypeReg, $this->debug);              // $typedev ist $variableTypeReg, $value wird normalerweise auch übergeben, $variable kann auch false sein

            $dosOps= new dosOps();

			//echo "Construct IPSComponentSswitch_Remote Logging for Variable ID : ".$variable."\n";
			$result=IPS_GetObject($variable);
			$this->variablename=IPS_GetName((integer)$result["ParentID"]);			// Variablenname ist immer der Parent Name 
		
			// Create Category to store the Move-LogNachrichten und Spiegelregister	
			$this->SwitchNachrichtenID=$this->CreateCategoryNachrichten("Switch",$this->CategoryIdData);
			$this->SwitchAuswertungID=$this->CreateCategoryAuswertung("Switch",$this->CategoryIdData);;
			if ($this->debug) echo "  Switch_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   ".IPS_GetName($this->CategoryIdData)." anlegen : [".$this->SwitchNachrichtenID.",".$this->SwitchAuswertungID."]\n";

			// lokale Spiegelregister aufsetzen 
			if ($variable<>null)
				{
		        $this->variable=$variable;   
				if ($this->debug) echo "      Lokales Spiegelregister als Boolean auf ".$this->variablename." ".$this->SwitchAuswertungID." ".IPS_GetName($this->SwitchAuswertungID)." anlegen.\n";
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

		function Switch_LogValue($param=false)
			{
			$result=GetValueFormatted($this->variable);
			if ($param=="State") SetValue($this->variableLogID,GetValue($this->variable));          // nur Status wird gespiegelt
			echo "Neuer Wert fuer $param ".$this->variablename." ist ".GetValueFormatted($this->variable).", ".$this->variableLogID." ist updated.\n";
			//parent::LogMessage($result);
			parent::LogNachrichten($this->variablename." ($param) mit Wert ".$result);
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