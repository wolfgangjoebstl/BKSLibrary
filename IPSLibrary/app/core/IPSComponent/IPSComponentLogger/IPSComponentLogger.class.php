<?

   /**
    * @class IPSComponentLogger
    *
    * Loggt die Werte der Sensoren in allen möglichen Medien und Arten
    *
    * @author Wolfgang Jöbstl
    * @version
    *   Version 2.50.1, 09.06.2012<br/>
    */

    //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');

/******************************************************
 *
 * class logging
 *
 * Speicherung von Nachrichten, als Einträge in einem File, als Werte in Objekten und Ausgabe als html tabelle und als echo
 *
 * Variablen Logging wird in verschiedenen Ebenen möglichst zentralisisert unterstützt, CustomComponent Daten in
 * Program::IPSLibrary::data::core::IPSComponent
 * abgespeichert immer in den Kategorien xxx_Auswertung und xxx_Nachrichten und zusätzlich in Mirror als Coponent_Variablenamexxx mit xxx ist Sensor, etc. 
 * in den Kategorien wird längerfristig mit einem standardisierten Namen und VariablenTyp und Profil gespeichert. 
 * in Mirror wird ein Abbild des aktuellen registers mit dem selben Variablentyp und Profil gespeichert.
 *
 * um zum standardisiserten Namen und Typ und Profil zu kommen ist sollten immer die gleichen routinen verwendet werden
 * Im MessageHandler Config gibt es einen tempValue als zusaetzlichen Identifier, es gibt einen Wert in der Datenbank oder es wird der gleiche Wert wie die aktuelle variable verwendet
 * aus dem tempValue (zB BAROPRESSURE)
 *
 * Aufruf mit folgenden Parametern:
 *      logfile     wenn der Wert auf "No-Output" steht wird kein Logfile angelegt. Sonst wird ein Logfile mit diesem Namen angelegt.
 *                  der Filename wird mit vollständigen, absoluten Pfad angegeben
 *
 *      nachrichteninput_id  wenn der Wert auf "Ohne" steht werden die Nachrichtenobjekte in einer Default Kategorie angelegt
 *
 *      prefix      wird am Anfang jeder Nachricht, die als Logfile geschrieben wir mitgegeben: Format Datum, Zeit, Prefix, Nachricht
 *
 *      html        wenn ID ungleich false wird dort eine html tabelle mit den selben Nachrichten geschrieben.
 *
 * Folgende Funktionen stehen zur Verfügung:
 *
 *  __construct
 *  constructFirst
 *  GetComponentParams
 *  GetComponent
 *
 *  SetDebugInstance
 *  SetDebugInstanceRemain
 *  GetDebugInstance
 *  CheckDebugInstance
 *
 *  GetEreignisID
 *
 *  get_IPSComponentLoggerConfig
 *  set_IPSComponentLoggerConfig
 *
 *  createFullDir
 *
 *  CreateCategoryAuswertung
 *  CreateCategoryNachrichten
 *
 *  do_init_motion
 *  do_init_brightness
 *  do_init_contact
 *  do_init_temperature
 *  do_init_humidity
 *  do_init_sensor
 *  do_init_counter
 *  do_init_climate
 *  do_init_statistics
 *  do_init
 *
 *  getVariableName
 *  setVariableLogId
 *  setVariableId
 *  RemoteLogValue
 *  LogMessage
 *  LogNachrichten
 *  PrintNachrichten
 *  CreateZeilen
 *  shiftZeile
 *  shiftZeileDebug
 *  IPSpathinfo
 *  status
 *
 *
 ****************************************************************/

class Logging
	{

    /* init at construct 
     * constructFirst sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()
     */

    protected       $startexecute;                          // interne Zeitmessung 
    protected       $installedmodules;                      // constructFirst, alle installerten Module
    protected       $CategoryIdData;                        // constructFirst, category Data von CustomComponent Modul
	protected       $mirrorCatID;                           // Spiegelregister in CustomComponent um eine Änderung zu erkennen
    protected       $logConfCatID, $logConfID;              // Welche Variable wird ausschliesslich gelogged
    protected       $configuration;                         // verwaltet gesamte Konfiguration, für die do_init_xxxx 

    public static   $debugInstance=false;                   // wenn nicht false werden besondere Debug Nachrichten generiert
    protected       $archiveHandlerID;                      // Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden  

	private         $prefix;							    // Zuordnung File Log Data am Anfang nach Zeitstempel 
	private         $log_File="Default";
	private         $nachrichteninput_Id="Default";
    private         $config=array();                    /* interne Konfiguration */
    private         $zeile=array();                     /* Nachrichteninput Objekte OIDs */
    private         $zeileDM=array();                   /* Nachrichteninput Objekte OIDs, eigenes für Device Management */
    private         $storeTableID = false;              /* ermöglicht längere Speichertiefen für Nachrichten */


    // $variable                                        // definiert in children class
    protected       $variablename;
	protected       $mirrorNameID;            // Spiegelregister in CustomComponent um eine Änderung zu erkennen
    protected       $mirrorType, $mirrorProfile;            // mirror register übernimmt die Parameter des eigentlichen Registers

	private $script_Id="Default";


    /* wird bereits in den children classes verwendet und dort initialisiert */

    protected $DetectHandler;               /* DetectMovement/Humidity ... ist auch ein Teil der Aktivitäten */

    //private     $variableProfile, $variableType;        // Eigenschaften der input Variable auf das Mirror Register clonen        
    protected   $AuswertungID;              /* wird bei der Gesamtauswertung benötigt */
    private     $NachrichtenID;             /* Auswertung für Custom Component, wird al sprivate Variable als ergebnis übergeben */

    /* von do_init_xxx initialisiert */
    protected $filename; 

    /* zusaetzliche Variablen für DetectMovement Funktionen, Detect Movement ergründet Bewegungen im Nachhinein */
    protected $GesamtID, $GesamtCountID, $EreignisID;
    protected $variableLogID, $variableDelayLogID;

    /* wichtige interne Variablen werden angelegt
     *
     * installedmodules     wird auch in den childrens verwendet, daher parallele Initialisierung auch in den Childrens
     * prefix               nur lokal
     * log_file             nur lokal
     * nachrichteninput_Id  nur lokal
     * config               nur lokal
     *
     *
     */

	function __construct($logfile="No-Output",$nachrichteninput_Id="Ohne",$prefix="", $html=false, $count=false)
		{
        //IPSLogger_Dbg(__file__, 'CustomComponent Motion_Logging Construct '.$logfile.'    '.$nachrichteninput_Id.'   '.$prefix);	
        $moduleManager_DM=false;
        $debug=false;
        if ($debug) echo "******Logging construct aufgerufen:  ($logfile | $nachrichteninput_Id | $prefix | $html | $count)\n";
        $this->constructFirst();                            // sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()

		$this->prefix=$prefix;
		$this->log_File=$logfile;     // es kommt das Verzeichnis mit, nicht bearbeiten
		//$this->log_File=str_replace(array('<', '>', ':', '"', '/', '\\', '|', '?', '*'), '', $logfile);             // alles wegloeschen das einem korrekten Filenamen widerspricht
		$this->nachrichteninput_Id=$nachrichteninput_Id;
        $this->config["Prefix"]=$prefix;
        //if ($this->configuration["BasicConfigs"]["LogStyle"] == "html") $html=true;
        if (strtoupper($this->configuration["BasicConfigs"]["LogStyle"]) == "HTML") $this->config["HTMLOutput"]=true;
        else $this->config["HTMLOutput"]=$html;
        //if ($this->config["HTMLOutput"]) echo "Ausgabe als HTML Tabelle). \n"; else echo "Ausgabe zeilenweise). \n";
        $this->config["MessageTable"]=false;
        if ($count>1) $this->config["TableSize"]=$count; 
        else $this->config["TableSize"]=16;
   		//echo "Initialisierung ".get_class($this)." mit Logfile: ".$this->log_File." mit Meldungsspeicher: ".$this->script_Id." \n";
		//echo "Init ".get_class($this)." : ";
		//var_dump($this);
		if ( ($this->log_File=="No-Output") || ($this->log_File==false) ) 
			{
			/* kein Logfile anlegen */
            $this->config["Logfile"]=false;
			}
		else
			{			
            //echo "Ein Logfile $logfile anlegen.\n";
            $this->config["Logfile"]=$logfile;
			if (!file_exists($this->log_File))
				{
				$FilePath = pathinfo($this->log_File, PATHINFO_DIRNAME);
				if (!file_exists($FilePath)) 
					{
					if (!mkdir($FilePath, 0755, true)) {
						throw new Exception('Create Directory '.$destinationFilePath.' failed!');
						}
					}			
				//echo "Create new file : ".$this->log_File." im Verzeichnis : ".$FilePath." \n";
				$handle3=fopen($this->log_File, "a");
				fwrite($handle3, date("d.m.y H:i:s").";Meldung\r\n");
				fclose($handle3);
				}
			}

        /*--------------------------------------------------------*/
		if ($this->nachrichteninput_Id == "Ohne")           // Defaultwert Bewegung, vid festlegen
			{
			//echo "  Kategorien im Datenverzeichnis Custom Components: ".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData).")\n";
			$name="Bewegung-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$this->CategoryIdData);
			if ($vid==0) $vid = CreateCategory($name,$this->CategoryIdData, 10);
            $this->config["MessageInputID"]=$vid; 
            }


		if ($this->nachrichteninput_Id != "Ohne")
		    {            
            if ($this->config["HTMLOutput"]) 
                {
                $sumTableID = CreateVariable("MessageTable", 3,  $this->nachrichteninput_Id, 900 , '~HTMLBox',null,null,""); // obige Informationen als kleine Tabelle erstellen
                $this->storeTableID = CreateVariable("MessageStorage", 3,  $this->nachrichteninput_Id, 910 , '',null,null,""); // die Tabelle in einem größerem Umfeld speichern
                IPS_SetHidden($this->storeTableID,true);                    // Nachrichtenarray nicht anzeigen
                SetValue($sumTableID,$this->PrintNachrichten(true));
                $this->config["MessageTable"]=$sumTableID;
                $this->config["MessageInputID"]=$nachrichteninput_Id;

                if ($debug) echo "      SetHidden ".$this->nachrichteninput_Id." ";
                for ($i=1;$i<=16;$i++)
                    {
                    $zeileId = @IPS_GetObjectIdByName("Zeile".str_pad($i, 2 ,'0', STR_PAD_LEFT),$this->nachrichteninput_Id);
                    if ($zeileId) 
                        {
                        IPS_SetHidden($zeileId,true);
                        if ($debug) echo "*";
                        }
                    }    
                if ($debug) echo "\n";                
                }
            else
                {
                $this->config["MessageInputID"]=$nachrichteninput_Id;                
                $this->zeile = $this->CreateZeilen($this->nachrichteninput_Id);
                $this->zeile1 = CreateVariable("Zeile01",3,$this->nachrichteninput_Id, 10 );
                $this->zeile2 = CreateVariable("Zeile02",3,$this->nachrichteninput_Id, 20 );
                $this->zeile3 = CreateVariable("Zeile03",3,$this->nachrichteninput_Id, 30 );
                $this->zeile4 = CreateVariable("Zeile04",3,$this->nachrichteninput_Id, 40 );
                $this->zeile5 = CreateVariable("Zeile05",3,$this->nachrichteninput_Id, 50 );
                $this->zeile6 = CreateVariable("Zeile06",3,$this->nachrichteninput_Id, 60 );
                $this->zeile7 = CreateVariable("Zeile07",3,$this->nachrichteninput_Id, 70 );
                $this->zeile8 = CreateVariable("Zeile08",3,$this->nachrichteninput_Id, 80 );
                $this->zeile9 = CreateVariable("Zeile09",3,$this->nachrichteninput_Id, 90 );
                $this->zeile10 = CreateVariable("Zeile10",3,$this->nachrichteninput_Id, 100 );
                $this->zeile11 = CreateVariable("Zeile11",3,$this->nachrichteninput_Id, 110 );
                $this->zeile12 = CreateVariable("Zeile12",3,$this->nachrichteninput_Id, 120 );
                $this->zeile13 = CreateVariable("Zeile13",3,$this->nachrichteninput_Id, 130 );
                $this->zeile14 = CreateVariable("Zeile14",3,$this->nachrichteninput_Id, 140 );
                $this->zeile15 = CreateVariable("Zeile15",3,$this->nachrichteninput_Id, 150 );
                $this->zeile16 = CreateVariable("Zeile16",3,$this->nachrichteninput_Id, 160 );                     
                }
			}
        else
			{
            if ($this->config["HTMLOutput"]) 
                {
                $vid= $this->config["MessageInputID"];
                $sumTableID = CreateVariable("MessageTable", 3,  $vid, 900 , '~HTMLBox',null,null,""); // obige Informationen als kleine Tabelle erstellen
                $this->storeTableID = CreateVariable("MessageStorage", 3,  $vid, 910 , '',null,null,""); // die Tabelle in einem größerem Umfeld speichern
                IPS_SetHidden($this->storeTableID,true);                    // Nachrichtenarray nicht anzeigen
                SetValue($sumTableID,$this->PrintNachrichten(true));
                $this->config["MessageTable"]=$sumTableID;
                if ($debug) echo "      SetHidden ".$vid."  ";
                for ($i=1;$i<=16;$i++)
                    {
                    $zeileId = @IPS_GetObjectIdByName("Zeile".str_pad($i, 2 ,'0', STR_PAD_LEFT),$vid);
                    if ($zeileId) 
                        {
                        IPS_SetHidden($zeileId,true);
                        if ($debug) echo "*";                        
                        }
                    } 
                if ($debug) echo "\n";                                     
                }
            else
                {
                //echo "  Kategorien im Datenverzeichnis Custom Components: ".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData).")\n";
                $vid= $this->config["MessageInputID"];
                $this->zeile = $this->CreateZeilen( $this->config["MessageInputID"]);
                // remove 
                    $this->zeile1  = CreateVariable("Zeile01",3,$vid, 10 );
                    $this->zeile2  = CreateVariable("Zeile02",3,$vid, 20 );
                    $this->zeile3  = CreateVariable("Zeile03",3,$vid, 30 );
                    $this->zeile4  = CreateVariable("Zeile04",3,$vid, 40 );
                    $this->zeile5  = CreateVariable("Zeile05",3,$vid, 50 );
                    $this->zeile6  = CreateVariable("Zeile06",3,$vid, 60 );
                    $this->zeile7  = CreateVariable("Zeile07",3,$vid, 70 );
                    $this->zeile8  = CreateVariable("Zeile08",3,$vid, 80 );
                    $this->zeile9  = CreateVariable("Zeile09",3,$vid, 90 );
                    $this->zeile10 = CreateVariable("Zeile10",3,$vid, 100 );
                    $this->zeile11 = CreateVariable("Zeile11",3,$vid, 110 );
                    $this->zeile12 = CreateVariable("Zeile12",3,$vid, 120 );
                    $this->zeile13 = CreateVariable("Zeile13",3,$vid, 130 );
                    $this->zeile14 = CreateVariable("Zeile14",3,$vid, 140 );
                    $this->zeile15 = CreateVariable("Zeile15",3,$vid, 150 );
                    $this->zeile16 = CreateVariable("Zeile16",3,$vid, 160 );
                }
            if (isset ($this->installedmodules["DetectMovement"]))
                {
                // nur wenn Detect Movement installiert zusaetzlich ein Motion Log fuehren 
                $moduleManager_DM = new IPSModuleManager('DetectMovement');     //   <--- change here 
                $CategoryIdDataDM     = $moduleManager_DM->GetModuleCategoryID('data');
                //echo "  Kategorien im Datenverzeichnis Detect Movement :".$CategoryIdDataDM."   ".IPS_GetName($CategoryIdDataDM)."\n";
                $name="Motion-Nachrichten";
                $vid=@IPS_GetObjectIDByName($name,$CategoryIdDataDM);	
                $this->zeileDM = $this->CreateZeilen($vid);		
                // remove , actually used in LogNachrichten
                        $this->zeile01DM = CreateVariable("Zeile01",3,$vid, 10 );
                        $this->zeile02DM = CreateVariable("Zeile02",3,$vid, 20 );
                        $this->zeile03DM = CreateVariable("Zeile03",3,$vid, 30 );
                        $this->zeile04DM = CreateVariable("Zeile04",3,$vid, 40 );
                        $this->zeile05DM = CreateVariable("Zeile05",3,$vid, 50 );
                        $this->zeile06DM = CreateVariable("Zeile06",3,$vid, 60 );
                        $this->zeile07DM = CreateVariable("Zeile07",3,$vid, 70 );
                        $this->zeile08DM = CreateVariable("Zeile08",3,$vid, 80 );
                        $this->zeile09DM = CreateVariable("Zeile09",3,$vid, 90 );
                        $this->zeile10DM = CreateVariable("Zeile10",3,$vid, 100 );
                        $this->zeile11DM = CreateVariable("Zeile11",3,$vid, 110 );
                        $this->zeile12DM = CreateVariable("Zeile12",3,$vid, 120 );
                        $this->zeile13DM = CreateVariable("Zeile13",3,$vid, 130 );
                        $this->zeile14DM = CreateVariable("Zeile14",3,$vid, 140 );
                        $this->zeile15DM = CreateVariable("Zeile15",3,$vid, 150 );
                        $this->zeile16DM = CreateVariable("Zeile16",3,$vid, 160 );			
                }
			}	
        if ($debug) 
            {
            $vid = $this->config["MessageInputID"];
            echo "      InputId: ".IPS_GetName($vid)."/".IPS_GetName(IPS_GetParent($vid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($vid)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($vid))))."\n";
            if ($moduleManager_DM) 
                {
                $vid=@IPS_GetObjectIDByName($name,$CategoryIdDataDM);   
                echo "      InputId: ".IPS_GetName($vid)."/".IPS_GetName(IPS_GetParent($vid))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($vid)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($vid))))."\n";
                }
            }
	    }

    /* Vereinheitlichung des Constructs, was macht das child und was der parent 
     * sets startexecute, installedmodules, CategoryIdData, mirrorCatID, logConfCatID, logConfID, archiveHandlerID, configuration, SetDebugInstance()
     *
     * SetDebugInstance wird nur aufgerufen wenn die Logging_Variable in LoggingConfig nicht 0 ist. Setzt self::$debugInstance statisch in dieser class Logging.
     * das heisst man kann mit einem Wert größer 0 einschalten aber nicht mehr ausschalten
     */

    public function constructFirst()
        {
        $this->startexecute=microtime(true); 

        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);			
        $this->installedmodules=$moduleManager->GetInstalledModules();
        $moduleManager_CC = new IPSModuleManager('CustomComponent');
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = IPS_GetObjectIdByName("Mirror",$this->CategoryIdData);
        $this->logConfCatID = IPS_GetObjectIdByName("LoggingConfig",$this->CategoryIdData);
        $this->logConfID    = IPS_GetObjectIdByName("Logging_Variable",$this->logConfCatID);
        if ($logging=GetValue($this->logConfID)) $this->SetDebugInstance($logging);
        //if (self::$debugInstance) $debug=true; else $debug=false;
		//if ($debug) echo "Logging Construct: ".IPS_GetName(self::$debugInstance)." (".self::$debugInstance.")   MirrorCat ".$this->mirrorCatID."  ConfigCat ".$this->logConfCatID." (at) ".$this->CategoryIdData."\n";

        $this->archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
        $this->configuration=$this->set_IPSComponentLoggerConfig();             /* configuration verifizieren und vervollstaendigen */
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
        return get_class($this);
        }

    public function GetComponent() {
        return ($this);
        }

    public function GetNachrichtenInputID() {
        return ($this->nachrichteninput_Id);
        }

    /* NachrichtenInputID festlegen, auch nachträglich möglich
     * unterstützt nur mehr Html_Output
     * Input Parameter kann eine ID sein, aber auch andere Möglichkeit der Ermittlung zB Statistik
     */

    public function SetNachrichtenInputID($nachrichteninput_Id) 
        {
        if (is_numeric($nachrichteninput_Id))
            {
            $oid = (integer)$nachrichteninput_Id;
            }
        else
            {
            $oid=false;
            $ipsOps = new ipsOps();
            $switch = strtoupper($nachrichteninput_Id);
            switch ($switch)
                {
                case "STATISTIK":
                    $oid = $ipsOps->searchIDbyName($nachrichteninput_Id,$this->CategoryIdData);        // needle in der categorie suchen
                    break;
                default:
                    echo "Do not know $switch.\n";
                    break;
                }
            }
        if ( ($oid>100) && ($this->config["HTMLOutput"]) )
            {
            $this->nachrichteninput_Id=$oid;        // qualifizierte ID
                $sumTableID = CreateVariable("MessageTable", 3,  $this->nachrichteninput_Id, 900 , '~HTMLBox',null,null,""); // obige Informationen als kleine Tabelle erstellen
                $this->storeTableID = CreateVariable("MessageStorage", 3,  $this->nachrichteninput_Id, 910 , '',null,null,""); // die Tabelle in einem größerem Umfeld speichern
                IPS_SetHidden($this->storeTableID,true);                    // Nachrichtenarray nicht anzeigen
                SetValue($sumTableID,$this->PrintNachrichten(true));
                $this->config["MessageTable"]=$sumTableID;
                $this->config["MessageInputID"]=$this->nachrichteninput_Id;
            return ($oid);
            }
        else return(false);
        }

    /* Steuerung der Debugging Funktion. Ziel ist es nicht so viele Info Messages im Webfront Logging zu bekommen
     * Es wird =self::$debugInstance zur Steuerung verwendet und mit CheckDebugInstance aus dem Logging der Variable abgefragt
     *
     *     
     * SetDebugInstance wird In constructFirst nur aufgerufen wenn die Logging_Variable in LoggingConfig nicht 0 ist. Setzt self::$debugInstance statisch in dieser class Logging.
     * das heisst man kann mit einem Wert größer 0 einschalten, aber nicht mehr ausschalten solange diese class verfügbar ist
     * man kann den Wert aber mit dem Aufruf der class jederzeit ändern
     *
     * Zusäzlich kann man mit dem Aufruf von SetDebugInstanceRemain die Logging_Variable in LoggingConfig direkt anpassen
     *
     */

    public function SetDebugInstance($value) {
        self::$debugInstance=$value;
        return (true);
        }

    public function SetDebugInstanceRemain($value) {
        SetValue($this->logConfID,$value);
        self::$debugInstance=$value;
        return (true);
        }

    /* ruft den aktuellen Wert der class aus */
    
    public function GetDebugInstance() {
        return(self::$debugInstance);
        }

    public function CheckDebugInstance($variable) 
        {
        if (self::$debugInstance==true) return (true);            
        elseif ($variable==self::$debugInstance) return (true);
        else return(false);
        }

    public function GetEreignisID() {
        return ($this->EreignisID);
        }

    /*  Kapselung verschiedener Informationen */

    public function get_IPSComponentLoggerConfig()
        {
        return ($this->configuration);
        }

    /* konfiguration einlesen und eventuell anpassen oder ergänzen
     * drei Haupt-Blöcke
     *      BasicConfigs
     *      LogDirectories
     *      LogConfigs
     */

    protected function set_IPSComponentLoggerConfig($debug=false)
        {
        $config=array();
        if ((function_exists("get_IPSComponentLoggerConfig"))===false) IPSUtils_Include ("IPSComponentLogger_Configuration.inc.php","IPSLibrary::config::core::IPSComponent");				
        if (function_exists("get_IPSComponentLoggerConfig")) $configInput=get_IPSComponentLoggerConfig();
        else echo "*************Fehler, Logging Konfig File nicht included oder Funktion get_IPSComponentLoggerConfig() nicht vorhanden. Es wird mit Defaultwerten gearbeitet.\n";

        configfileParser($configInput, $config, ["BasicConfigs","Basicconfigs","basicconfigs","BASICCONFIGS"],"BasicConfigs",null);    
        configfileParser($configInput, $config, ["LogDirectories" ],"LogDirectories" ,null);    
        configfileParser($configInput, $config, ["LogConfigs"],"LogConfigs",null); 
        $configInput=$config;

        /* check BasicConfigs and fill them automatically */
        $dosOps = new dosOps();
        $operatingSystem = $dosOps->evaluateOperatingSystem();
        if ($debug) echo "Operating System $operatingSystem\n";
        if ($operatingSystem ==  "WINDOWS") 
            {
            configfileParser($configInput["BasicConfigs"], $config["BasicConfigs"], ["SystemDir"],"SystemDir","C:/Scripts/"); 
            configfileParser($configInput["BasicConfigs"], $config["BasicConfigs"], ["UserDir"],"UserDir","C:/Users/");   
            }
        else 
            {
            configfileParser($configInput["BasicConfigs"], $config["BasicConfigs"], ["SystemDir"],"SystemDir","/var/");
            configfileParser($configInput["BasicConfigs"], $config["BasicConfigs"], ["UserDir"],"UserDir",null);  
            }
        configfileParser($configInput["BasicConfigs"], $config["BasicConfigs"], ["OperatingSystem"],"OperatingSystem",$operatingSystem);     
        configfileParser($configInput["BasicConfigs"], $config["BasicConfigs"], ["LogStyle","Logstyle","logstyle","LOGSTYLE"],"LogStyle","text");

        /* check logDirectories, replace c:/Scripts/ with systemdir */
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["TemperatureLog"],"TemperatureLog","/Temperature/");    
        $this->createFullDir($config["LogDirectories"]["TemperatureLog"],$config["BasicConfigs"]["SystemDir"]);
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["HumidityLog"],"HumidityLog","/Humidity/");    
        $this->createFullDir($config["LogDirectories"]["HumidityLog"],$config["BasicConfigs"]["SystemDir"]);
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["MotionLog"],"MotionLog","/Motion/");    
        $this->createFullDir($config["LogDirectories"]["MotionLog"],$config["BasicConfigs"]["SystemDir"]);
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["ContactLog"],"ContactLog","/Contact/");    
        $this->createFullDir($config["LogDirectories"]["ContactLog"],$config["BasicConfigs"]["SystemDir"]);
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["CounterLog"],"CounterLog","/Counter/");    
        $this->createFullDir($config["LogDirectories"]["CounterLog"],$config["BasicConfigs"]["SystemDir"]);
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["HeatControlLog"],"HeatControlLog","/HeatControl/");    
        $this->createFullDir($config["LogDirectories"]["HeatControlLog"],$config["BasicConfigs"]["SystemDir"]);
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["AnwesenheitssimulationLog"],"AnwesenheitssimulationLog","/Anwesenheitssimulation/");    
        $this->createFullDir($config["LogDirectories"]["AnwesenheitssimulationLog"],$config["BasicConfigs"]["SystemDir"]);
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["SensorLog"],"SensorLog","/Sensor/");    
        $this->createFullDir($config["LogDirectories"]["SensorLog"],$config["BasicConfigs"]["SystemDir"]);
        configfileParser($configInput["LogDirectories"], $config["LogDirectories"], ["ClimateLog"],"ClimateLog","/Climate/");    
        $this->createFullDir($config["LogDirectories"]["ClimateLog"],$config["BasicConfigs"]["SystemDir"]);

        if ($debug) print_r($config);
        return ($config);
        }

    private function createFullDir(&$input,$systemDir)
        {
        $dosOps = new dosOps();            
        if (strpos($input,"C:/Scripts/")===0) $input=substr($input,10);
        //echo "Verzeichnis derangiert: $input\n";
        $input = $dosOps->correctDirName($systemDir.$input);
        //echo "Verzeichnis korrigiert: $input\n";
        }

    /* in CustomComponent Data werden immer zwei paare an Kategorien erstellet. Auswertung und Nachrichten. Der erste Teil ist variable.
     *
     */

    public function CreateCategoryAuswertung($name,$CategoryIdData)
        {
        $name .= "-Auswertung";
        $MoveAuswertungID=@IPS_GetObjectIDByName($name,$CategoryIdData);
        if ($MoveAuswertungID==false)
            {
            $MoveAuswertungID = IPS_CreateCategory();
            IPS_SetParent($MoveAuswertungID, $CategoryIdData);
            IPS_SetName($MoveAuswertungID, $name);
            IPS_SetInfo($MoveAuswertungID, "this category was created by script. ");
            }
        return ($MoveAuswertungID);
        }

    public function CreateCategoryNachrichten($name,$CategoryIdData)
        {
        /* Create Category to store the Move-LogNachrichten */	
        $name .= "-Nachrichten";
        $MoveNachrichtenID=@IPS_GetObjectIDByName($name,$CategoryIdData);
        if ($MoveNachrichtenID==false)
            {
            $MoveNachrichtenID = IPS_CreateCategory();
            IPS_SetParent($MoveNachrichtenID, $CategoryIdData);
            IPS_SetName($MoveNachrichtenID, $name);
            IPS_SetInfo($MoveNachrichtenID, "this category was created by script. ");
            }
        return ($MoveNachrichtenID);
        }
    
    /* AuswertungID auslesen
     */
    public function getCategoryAuswertung()
        {
        return ($this->AuswertungID);
        }

    /***********************************************************************************
     ***********************************************************************************/

    /* do_init_motion, wird beim construct der Child Logging class aufgerufen
     * wichtige Variablen die angelegt werden:
     *  variablename        Variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
     *  name                MotionMirror_variablename
     *  mirrorNameID        die OID der Mirror Variable, die einfache Variante, die nur zum Vergleichen des Iststandes des Geräteregisters verwendet wird 
     *  mirrorCatID,variableType,variableProfile       Ort, Name, Type, Profil für die Mirror Variable, wird in do_init festgelegt
     *  DetectHandler       abhängig vom Component, also der child class
     *
     * kann auch direkt für die Speicherung der Daten in der Datenbank verwendet werden. 
     * Variable muss gesetzt sein, variablename kann null sein, value ebenfalls
     */

    public function do_init_motion($variable, $variablename, $value,$debug=false)
        {
        if ($debug) echo "IPSComponentSensor_Motion, HandleEvent für Motion VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Wert : ".($value?"Bewegung":"Still")." \n";
        if ($this->CheckDebugInstance($variable)) IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für Motion VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.($value?"Bewegung":"Still"));
        if ($debug) echo "      Aufruf do_init_motion:\n";

        if (isset ($this->installedmodules["DetectMovement"])) 
            {
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');                  
            $this->DetectHandler = new DetectMovementHandler();  // für getVariableName benötigt 
            }
        $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        //$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        //$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        //echo "  Kategorien im Datenverzeichnis : ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData).").\n";
        $name="MotionMirror_".$this->variablename;
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "MOTION":
                break;
            default:
                IPSLogger_Wrn(__file__, "do_init_motion, do not know Type ".$this->variableTypeReg." : Type ".$this->variableType." with Profile ".$this->variableProfile.".");
                echo "***do_init_motion, do not know Type ".$this->variableTypeReg." : Type ".$this->variableType." with Profile ".$this->variableProfile.".\n";               
                break;
            }
        echo "Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".\n";
        //IPSLogger_Wrn(__file__, "do_init_motion, Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".");
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->mirrorType,$this->mirrorProfile);       /* 0 boolean */

        /* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Bewegung",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Bewegung",$this->CategoryIdData);;

        echo "lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen:\n";
        $this->do_setVariableLogID($variable,$debug);
        $this->variableDelayLogID = $this->variableLogID;                                                                                       // sicherheitshalber, kann später noch überschrieben werden.
        
        /* DetectMovement Spiegelregister und statische Anwesenheitsauswertung, nachtraeglich */
        if (isset ($this->installedmodules["DetectMovement"]))
            {
            /* nur wenn Detect Movement installiert ist ein Motion Log fuehren */
            $this->DetectHandler->Set_MoveAuswertungID($this->AuswertungID);
            $CategoryIdData     = $this->DetectHandler->Get_CategoryData();
            /* DetectMovement Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen */
            //echo "  Datenverzeichnis Category Data :".$CategoryIdData."\n";
            $name="Motion-Nachrichten";
            $vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
            if ($vid==false)
                {
                $vid = IPS_CreateCategory();
                IPS_SetParent($vid, $CategoryIdData);
                IPS_SetName($vid, $name);
                IPS_SetInfo($vid, "this category was created by script. ");
                }
            $this->motionDetect_NachrichtenID=$vid;

            $name="Motion-Detect";
            $mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
            if ($mdID==false)
                {
                echo "Create Motion-Detect Kategorie in $CategoryIdData.\n";
                $mdID = IPS_CreateCategory();
                IPS_SetParent($mdID, $CategoryIdData);
                IPS_SetName($mdID, $name);
                IPS_SetInfo($mdID, "this category was created by script. ");
                }
            $this->motionDetect_DataID=$mdID;

            if ($variable<>null)
                {
                //echo "Construct Motion Logging for DetectMovement, Uebergeordnete Variable : ".$this->variablename."\n";
                $directory=$this->configuration["LogDirectories"]["MotionLog"];
                $dosOps= new dosOps();
                $dosOps->mkdirtree($directory);
                $filename=$directory.$this->variablename."_Motion.csv";

                $variablenameEreignis=str_replace(" ","_",$this->variablename)."_Ereignisspeicher";
                $this->EreignisID=CreateVariableByName($this->motionDetect_DataID,$variablenameEreignis,3,'', null, 100, null );
                echo "       Ereignisspeicher aufsetzen        : ".$this->EreignisID." \"$variablenameEreignis\"\n";

                /* Spiegelregister für Bewegung mit Delay, wenn DetectMovement installiert ist */
                echo '       Spiegelregister (Delay) erstellen : Basis ist '.$variable.' Name "'.$this->variablename.'" in '.$this->motionDetect_DataID." (".IPS_GetName($this->motionDetect_DataID).")\n";
                $variableDelayLogID=@IPS_GetObjectIDByName($this->variablename,$this->motionDetect_DataID);
                if ( ($variableDelayLogID===false) || (AC_GetLoggingStatus($this->archiveHandlerID,$variableDelayLogID)==false) || (AC_GetAggregationType($this->archiveHandlerID,$variableDelayLogID) != 0) )
                    {
                    echo "        --> noch nicht vorhanden. Variable Name ".$this->variablename." muss erstellt oder adaptiert werden.\n"; 
                    /* CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0) */
                    $this->variableDelayLogID=CreateVariableByName($this->motionDetect_DataID, $this->variablename,0,'~Motion',null,10,null );
                    AC_SetLoggingStatus($this->archiveHandlerID,$this->variableDelayLogID,true);
                    AC_SetAggregationType($this->archiveHandlerID,$this->variableDelayLogID,0);      /* normaler Wwert */
                    IPS_ApplyChanges($this->archiveHandlerID);
                    }
                else $this->variableDelayLogID=$variableDelayLogID;    					
                }
            /* CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0) */
            $erID=CreateVariableByName($this->motionDetect_DataID,"Gesamt_Ereignisspeicher",3, '', null,10000,null );           // in init motion, wenn DetectMovement
            $this->GesamtID=$erID;
            //echo "  Gesamt Ereignisspeicher aufsetzen : ".$erID." \n";
            $erID=CreateVariableByName($this->motionDetect_DataID,"Gesamt_Ereigniszaehler",1, '', null,10000,null );
            $this->GesamtCountID=$erID;
            //echo "  Gesamt Ereigniszähler aufsetzen   : ".$erID." \n";
            }

        $directory=$this->configuration["LogDirectories"]["MotionLog"];
        $dosOps= new dosOps();
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Bewegung.csv";                
        //return("Ohne");
        return($this->NachrichtenID);        
        }

    /* wird beim construct aufgerufen, wenn keine Datanbank angelegt wurde
     * kann auch direkt für die Speicherung der Daten in der Datenbank verwendet werden. 
     */

    public function do_init_brightness($variable, $variablename,$value, $debug=false)
        {
        if ($debug) echo "IPSComponentSensor_Motion, HandleEvent für Brightness VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Wert $value : ".GetValueIfFormatted($variable)." \n";
        if ($this->CheckDebugInstance($variable)) IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für Brightness VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert $value : '.GetValueIfFormatted($variable));
        if (isset ($this->installedmodules["DetectMovement"])) $this->DetectHandler = new DetectBrightnessHandler();  // für getVariableName benötigt 
        $this->variablename = $this->getVariableName($variable, $variablename, $debug);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
        if ($debug) echo "   Aufruf do_init_brightness Variablenname abgeändert von $variablename auf ".$this->variablename.":\n";
        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        //$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        //$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        //echo "  Kategorien im Datenverzeichnis : ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData).").\n";
        //$this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
        $name="HelligkeitMirror_".$this->variablename;
        if ($debug) echo "      CreateVariableByName at ".$this->mirrorCatID." (".IPS_GetName($this->mirrorCatID).") mit Name \"$name\" Type ".$this->mirrorType." TypeReg ".$this->variableTypeReg." Profile ".$this->variableProfile." Variable available : ".(@IPS_GetVariableIDByName($name, $this->mirrorCatID)?"Yes":"No")." \n";
        //CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
         switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "BRIGHTNESS":          // für Brightness gibt es standardisierte Helligkeits Profile
                //$this->variableProfile="~Window.HM";         // 3 Werte, allgemein für Alle
                //$this->variableType=1;                      // Integer
                break;                
            default:
                IPSLogger_Wrn(__file__, "do_init_brightness, do not know Type ".$this->variableTypeReg." : Type ".$this->variableType." with Profile ".$this->variableProfile.".");
                echo "***do_init_brightness, do not know Type ".$this->variableTypeReg." : Type ".$this->variableType." with Profile ".$this->variableProfile.".\n";            
                break;
            }
        echo "Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".\n";
        //IPSLogger_Wrn(__file__, "do_init_brightness, Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".");
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->mirrorType,$this->mirrorProfile);       

        if ($debug) echo "      Create Category to store the Move-LogNachrichten und Spiegelregister in ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData)."):\n";	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Helligkeit",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Helligkeit",$this->CategoryIdData);;
        if ($debug) echo "         done ".$this->NachrichtenID. "(".IPS_GetName($this->NachrichtenID).") und ".$this->AuswertungID." (".IPS_GetName($this->AuswertungID).").\n";
        
        echo "lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen, es wird für create entsprechend variableType,variableProfile genutzt:\n";
        $this->do_setVariableLogID($variable,$debug);

        $directory = $this->configuration["LogDirectories"]["MotionLog"];
        $dosOps= new dosOps();
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Helligkeit.csv";    
        return($this->NachrichtenID);  
        }

    /* wird beim construct aufgerufen, wenn keine Datanbank angelegt wurde
        * kann auch direkt für die Speicherung der Daten in der Datenbank verwendet werden. 
        */

    public function do_init_contact($variable, $variablename, $value,$debug=false)
        {
        if ($debug) echo "IPSComponentSensor_Motion, do_init_contact für VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Wert $value: ".GetValueIfFormatted($variable)." \n";
        if ($this->CheckDebugInstance($variable)) IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für Contact VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert $value: '.GetValueIfFormatted($variable));
        if (isset ($this->installedmodules["DetectMovement"])) $this->DetectHandler = new DetectContactHandler();  // für getVariableName benötigt   <--- change here 
        $this->variablename = $this->getVariableName($variable, $variablename, $debug);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
        if ($debug) echo "   Aufruf do_init_contact Variablenname abgeändert von $variablename auf ".$this->variablename.":\n";
        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        //$moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        //$this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        //echo "  Kategorien im Datenverzeichnis : ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData).").\n";
        //$this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);

        $name="KontaktMirror_".$this->variablename;
        if ($debug) echo "      CreateVariableByName at ".$this->mirrorCatID." (".IPS_GetName($this->mirrorCatID).") mit Name \"$name\" Type ".$this->variableType." TypeReg ".$this->variableTypeReg." Profile ".$this->variableProfile." Variable available : ".(@IPS_GetVariableIDByName($name, $this->mirrorCatID)?"Yes":"No")." \n";
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float ~Temperature*/
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,1,$this->variableProfile);       /* 1 integer */
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "CONTACT":
                $this->variableProfile="~Window.HM";         // 3 Werte, allgemein für Alle
                $this->variableType=1;                      // Integer
                echo "do_init_contact, Create Variable Log Register $name as ".$this->variableType." with Profile ".$this->variableProfile.", Type is ".$this->variableTypeReg.".\n";
                //IPSLogger_Wrn(__file__, "do_init_contact, Create Mirror Register $name as ".$this->variableType." with Profile ".$this->variableProfile.", Type is ".$this->variableTypeReg.".");
                break;
            default:
                IPSLogger_Wrn(__file__, "do_init_contact, do not know Type ".$this->variableTypeReg." : Type ".$this->variableType." with Profile ".$this->variableProfile.".");
                echo "***do_init_contact, do not know Type ".$this->variableTypeReg." : Type ".$this->variableType." with Profile ".$this->variableProfile.".\n";
                break;
            }
        echo "Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".\n";
        //IPSLogger_Wrn(__file__, "do_init_contact, Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".");
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->mirrorType,$this->mirrorProfile);             /* Selbe Werte wie geloggte Variable als Default übernehmen*/

        if ($debug) echo "      Create Category to store the Move-LogNachrichten und Spiegelregister in ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData)."):\n";	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Kontakt",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Kontakt",$this->CategoryIdData);;
        if ($debug) echo "         done ".$this->NachrichtenID. "(".IPS_GetName($this->NachrichtenID).") und ".$this->AuswertungID." (".IPS_GetName($this->AuswertungID).").\n";
        
        echo "lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen:\n";
        $this->do_setVariableLogID($variable,$debug);

        $directory = $this->configuration["LogDirectories"]["MotionLog"];
        $dosOps= new dosOps();
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Kontakt.csv";    
        return($this->NachrichtenID);  
        }

    /* Initialisierung für Temperature */

    public function do_init_temperature($variable, $variablename)
        {
        /**************** installierte Module und verfügbare Konfigurationen herausfinden */
        if ($this->debug) echo "   Aufruf  do_init_temperature($variable, $variablename).\n";
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $this->installedmodules=$moduleManager->GetInstalledModules();

        if (isset ($this->installedmodules["DetectMovement"]))
            {
            /* Detect Movement kann auch Temperaturen agreggieren */
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            $this->DetectHandler = new DetectTemperatureHandler();
            }

        $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovemet Config oder selber bestimmen

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden 		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     //   <--- change here 
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);   */

        $name="TemperatureMirror_".$this->variablename;
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "TEMPERATURE":
            case "TEMPERATUR":
                echo "do_init_temperature, Create Auswertung Register $name as Float and ".$this->variableProfile."\n";
                if ( ($this->variableProfile <> "~Temperature") && ($this->variableProfile <> "Netatmo.Temperatur") )
                    {
                    IPSLogger_Wrn(__file__, "do_init_temperature, Create Auswertung Register $name as Float with Profile ".$this->variableProfile." instead of \"~Temperature\".");
                    }
                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,"~Temperature");       /* 2 float Netatmo und Homematic bekommen das selbe Profil */
                break;
            default:
                echo "Create Mirror Register $name as Float und ".$this->variableProfile."\n";
                IPSLogger_Wrn(__file__, "do_init_temperature, Create Auswertung Register $name as Float with Profile ".$this->variableProfile.", TypeReg is ".$this->variableTypeReg.".");
                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 2 float für Default*/
                break;
            }
        if ($this->debug) echo "    Temperatur_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData).")\n";
        echo "Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".\n";
        //IPSLogger_Wrn(__file__, "do_init_temperature, Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".");
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->mirrorType,$this->mirrorProfile);             /* Selbe Werte wie geloggte Variable als Default übernehmen*/
        
        /* Create Category to store the LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Temperatur",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Temperatur",$this->CategoryIdData);
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        if ($this->debug) echo "   Uebergeordnete Variable : ".$this->variablename."\n";
        $directory = $this->configuration["LogDirectories"]["MotionLog"];
        $dosOps= new dosOps();              
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Temperature.csv";
        return($this->NachrichtenID);               // nur als Private deklariert
        }    


    /* Initialisierung für Feuchtigkeit */

    public function do_init_humidity($variable, $variablename)
        {
        /**************** installierte Module und verfügbare Konfigurationen herausfinden */
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $this->installedmodules=$moduleManager->GetInstalledModules();

        if (isset ($this->installedmodules["DetectMovement"]))
            {
            /* Detect Movement kann auch Feuchtigkeiten agreggieren */
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            $this->DetectHandler = new DetectHumidityHandler();
            }

        $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden 		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     //   <--- change here 
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);      */          
        $name="HumidityMirror_".$this->variablename;
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float */
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 2 float */
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "CONTACT1":
                echo "do_init_humidity, Create Mirror Register $name as Integer und ".$this->variableProfile."\n";
                //IPSLogger_Wrn(__file__, "do_init_humidity, Create Auswertung Register $name as Integer with Profile ".$this->variableProfile." if necessary.");

                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,1,$this->variableProfile);       /* 1 integer für Typ CO2 */
                break;
            default:
                echo "Create Mirror Register $name as Float und ".$this->variableProfile."\n";
                //IPSLogger_Wrn(__file__, "do_init_humidity, Create Mirror Register $name as ".$this->variableType." with Profile ".$this->variableProfile.", TypeReg is ".$this->variableTypeReg.".");
                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float für Default*/
                break;
            }
        echo "Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".\n";
        //IPSLogger_Wrn(__file__, "do_init_humidity, Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".");
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->mirrorType,$this->mirrorProfile);             /* Selbe Werte wie geloggte Variable als Default übernehmen*/


        /* Create Category to store the LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Feuchtigkeit",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Feuchtigkeit",$this->CategoryIdData);
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        //echo "Uebergeordnete Variable : ".$variablename."\n";
        $directory = $this->configuration["LogDirectories"]["HumidityLog"];
        $dosOps= new dosOps();              
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Feuchtigkeit.csv";
        return($this->NachrichtenID);               // nur als Private deklariert
        }

    /* Initialisierung für Sensor 
     * das ist die allgemeine Funktion für viele Sensoren aller Art
     *
     * vorher wird constructfirst, do_init aufgerufen. Hier wird weiters noch angelegt:
     *  DetectHandler       wenn installiert
     *  variablename        abgeleitet aus dem Variablennamen oder aus der Config
     *
     *
     */

    public function do_init_sensor($variable, $variablename)
        {
        /**************** installierte Module und verfügbare Konfigurationen herausfinden 
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $this->installedmodules=$moduleManager->GetInstalledModules();  */

        if (isset ($this->installedmodules["DetectMovement"]))
            {
            /* Detect Movement kann auch Sensorwerte agreggieren */
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            $this->DetectHandler = new DetectSensorHandler();                            // zum Beispiel für die Evaluierung der Mirror Register
            }

        $this->variablename = $this->getVariableName($variable, $variablename);           // function von IPSComponent_Logger, $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden 		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     //   <--- change here 
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);  */
        $name="SensorMirror_".$this->variablename;
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float */
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 2 float */
        echo "Sensor_Logging:do_init_sensor construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData)."/".IPS_GetName(IPS_GetParent($this->CategoryIdData))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($this->CategoryIdData))).")\n";
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "POWER":           /* Power Wirkleistung und Wirkenergie von AMIS, oder aus einem Homematic Register direkt */
            case "ENERGY":
                echo "   do_init_sensor, Create Mirror Register $name as Integer und ".$this->variableProfile."\n";
                if ($this->variableProfile == "~Watt.3680")
                    {
                    /* Watt in kWatt umrechnen */
                    $this->variableProfile = "~Power";          // hier log Register auf Standardprofil umfirmieren
                    }
                elseif ( ($this->variableProfile <> "~Power") && ($this->variableProfile <> "~Electricity") )
                    {                
                    echo "   do_init_sensor Warning, Create Auswertung Register $name as Float with Profile ".$this->variableProfile." not supported.\n";
                    IPSLogger_Wrn(__file__, "do_init_sensor, Create Auswertung Register $name as Float with Profile ".$this->variableProfile." not supported.");
                    }
                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 1 integer für Typ CO2 */
                break;
            case "DATA":
            default:
                /*echo "Create Mirror Register $name as ".$this->variableType." und ".$this->variableProfile."\n";
                IPSLogger_Wrn(__file__, "do_init_sensor, Create Mirror Register $name as ".$this->variableType." with Profile ".$this->variableProfile.", TypeReg is ".$this->variableTypeReg.".");
                $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       // 2 float für Default*/
                break;
            }        
        echo "   Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".\n";
        //IPSLogger_Wrn(__file__, "do_init_sensor, Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".");
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->mirrorType,$this->mirrorProfile);             /* Selbe Werte wie geloggte Variable als Default übernehmen*/

        /* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Sensor",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Sensor",$this->CategoryIdData);
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        //echo "Uebergeordnete Variable : ".$this->variablename."\n";
        $directory = $this->configuration["LogDirectories"]["SensorLog"];
        $dosOps= new dosOps(); 
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Sensor.csv";
        return($this->NachrichtenID);               // nur als Private deklariert
        }

    /* Initialisierung für Counter 
     * das ist die allgemeine Funktion für viele Counter aller Art, muss Umrechnen in Profile
     *
     * vorher wird constructfirst, do_init aufgerufen. Hier wird weiters noch angelegt:
     *  DetectHandler       wenn installiert
     *  variablename        abgeleitet aus dem Variablennamen oder aus der Config
     *  mirrorNameID
     *  NachrichtenID, AuswertungID, filename
     *
     * erstellt die folgenden Variablen basierend auf $variableTypeReg
     *      Mirror      CounterMirror_$variablename     $mirrorType     $mirrorProfil
     *      Log         $variablename     $variableType     $variabelProfil
     *
     */

    public function do_init_counter($variable, $variablename)
        {
        if (isset ($this->installedmodules["DetectMovement"]))
            {
            /* Detect Movement kann auch Counterwerte agreggieren */
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            $this->DetectHandler = new DetectCounterHandler();                            // zum Beispiel für die Evaluierung der Mirror Register
            }

        $this->variablename = $this->getVariableName($variable, $variablename);           // function von IPSComponent_Logger, $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
        $name="CounterMirror_".$this->variablename;
        echo "    Counter_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData)."/".IPS_GetName(IPS_GetParent($this->CategoryIdData))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($this->CategoryIdData))).") Type is ".$this->variableTypeReg."\n";
        echo "    Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile." \n";
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->mirrorType,$this->mirrorProfile);             /* Selbe Werte wie geloggte Variable als Default übernehmen*/

        /* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Counter",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Counter",$this->CategoryIdData);
        echo "    Create Logging Register ".$this->variablename." as ".$this->variableType." with Profile ".$this->variableProfile." \n";
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        //echo "Uebergeordnete Variable : ".$this->variablename."\n";
        $directory = $this->configuration["LogDirectories"]["CounterLog"];
        $dosOps= new dosOps(); 
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Counter.csv";
        return($this->NachrichtenID);               // nur als Private deklariert
        }

    /* Initialisierung für besonderen Climate Sensor 
     * Aufgerufen von IpsComponentSensor_Remote, getroffene besondere Auswahl anstelle Sensor wenn BAROPRESSURE oder CO2
     * Es werden folgende class Register beschrieben:
     *    variablename          die schwierigste Funktion, benötige einen eindeutigen Namen
     *    mirrorNameID (mirrorCatID, CategoryTdData) 
     *    NachrichtenID, AuswertungID
     *    VariableLogID     von do_setVariableLogID ermittelt, mit dem variablenname in der Auswertung Kategorie mit Type und profile
     *
     */

    public function do_init_climate($variable, $variablename)
        {
        /**************** installierte Module und verfügbare Konfigurationen herausfinden 
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $this->installedmodules=$moduleManager->GetInstalledModules();  */
        if (isset ($this->installedmodules["DetectMovement"]))
            {
            //Detect Movement kann auch Sensorwerte agreggieren 
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            $this->DetectHandler = new DetectClimateHandler();                            // zum Beispiel für die Evaluierung der Mirror Register       */
            }

        $this->variablename = $this->getVariableName($variable, $variablename);           // function von IPSComponent_Logger, $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden 		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');    //   <--- change here 
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);   */
        echo "do_init_climate($variable, $variablename). [".$this->variableTypeReg.",".$this->variableType.",".$this->variableProfile."]\n";
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "CO2":
                $this->variablename = $this->variablename."CO2";
                $name="ClimateMirror_".$this->variablename;   // manchmal gibt es unterschiedliche Typen in einem Gerät des selben Components;
                echo "do_init_climate, Create Auswertung Register ".$this->variablename." as Integer und ".$this->variableProfile."\n";
                if ($this->variableProfile <> "Netatmo.CO2") IPSLogger_Wrn(__file__, "do_init_climate, Create Auswertung Register ".$this->variablename." as Integer with Profile ".$this->variableProfile." instead of \"Netatmo.CO2\".necessary.");
                if ($this->variableType <> 1) IPSLogger_Wrn(__file__, "do_init_climate, Create Auswertung Register ".$this->variablename." as Type ".$this->variableType." instead of Type 1 necessary.");
                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       // 1 integer für Typ CO2 
                $this->variableProfile="CO2";   // MirorRegister ist wie die Variable, Variable in der Auswertung wird vereinheitlicht.
                $this->variableType=1;
                break;
            case "BAROPRESSURE":
                $this->variablename = $this->variablename."BARO";
                $name="ClimateMirror_".$this->variablename;   // manchmal gibt es unterschiedliche Typen in einem Gerät des selben Components;
                echo "do_init_climate, Create Mirror Register ".$this->variablename." as Integer und ".$this->variableProfile."\n";
                if ($this->variableProfile <> "Netatmo.Pressure") IPSLogger_Wrn(__file__, "do_init_climate, Create Auswertung Register ".$this->variablename." as Integer with Profile ".$this->variableProfile." instead of \"Netatmo.CO2\".necessary.");
                if ($this->variableType <> 2) IPSLogger_Wrn(__file__, "do_init_climate, Create Auswertung Register ".$this->variablename." as Type ".$this->variableType." instead of Type 1 necessary.");
                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       // 1 integer für Typ CO2 
                $this->variableProfile = "Pressure";
                $this->variableType=2;
                break;
            default:
                $name="ClimateMirror_".$this->variablename;   // manchmal gibt es unterschiedliche Typen in einem Gerät des selben Components;
                echo "Create Auswertung Register $name as Float und ".$this->variableProfile." unknown TypeReg is ".$this->variableTypeReg."\n";
                IPSLogger_Wrn(__file__, "do_init_climate, Create Auswertung Register ".$this->variablename." as Type ".$this->variableType." with Profile ".$this->variableProfile.", TypeReg is ".$this->variableTypeReg.".");
                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       // 2 float für Default
                break;
            }
        echo "Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".\n";
        //IPSLogger_Wrn(__file__, "do_init_climate, Create Mirror Register $name as ".$this->mirrorType." with Profile ".$this->mirrorProfile.", Type is ".$this->variableTypeReg.".");
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->mirrorType,$this->mirrorProfile);             /* Selbe Werte wie geloggte Variable als Default übernehmen*/


        echo "    Climate_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData)."/".IPS_GetName(IPS_GetParent($this->CategoryIdData))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($this->CategoryIdData))).")\n";
        
        /* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Climate",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Climate",$this->CategoryIdData);
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, mit class register Variablenname, AuswertungID, variableType, variableProfile  

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        //echo "Uebergeordnete Variable : ".$this->variablename."\n";
        $directory = $this->configuration["LogDirectories"]["ClimateLog"];        
        $dosOps= new dosOps(); 
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Sensor.csv";
        return($this->NachrichtenID);               // nur als Private deklariert
        }

    /* wird beim construct aufgerufen, wenn keine Variable übergeben wurde.
     * Klasse wird für Statistische Auswertungen verwendet.
     */

    public function do_init_statistics($debug=false)
        {
        if ($debug) echo "      Logging::do_init_statistics, Aufruf:\n";

        /* wenn die Register Gesamt_Ereignis... nicht angelegt sind kann writeEvents nicht arbeiten */
        if (isset ($this->installedmodules["DetectMovement"]))
            {
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            $this->DetectHandler = new DetectMovementHandler();  // für getVariableName benötigt
            $CategoryIdData     = $this->DetectHandler->Get_CategoryData();
            $name="Motion-Detect";
            $mdID=@IPS_GetObjectIDByName($name,$CategoryIdData);
            if ($mdID==false)
                {
                echo "Create Motion-Detect Kategorie in $CategoryIdData.\n";
                $mdID = IPS_CreateCategory();
                IPS_SetParent($mdID, $CategoryIdData);
                IPS_SetName($mdID, $name);
                IPS_SetInfo($mdID, "this category was created by script. ");
                }
            $this->motionDetect_DataID=$mdID;
            /* CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0) */
            $this->GesamtID=CreateVariableByName($this->motionDetect_DataID,"Gesamt_Ereignisspeicher",3, '', null,10000,null );
            $this->GesamtCountID=CreateVariableByName($this->motionDetect_DataID,"Gesamt_Ereigniszaehler",1, '', null,10000,null );
            }

        /**************** Speicherort für Nachrichten und Spiegelregister definieren */		
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Statistik",$this->CategoryIdData);
        //$this->AuswertungID=$this->CreateCategoryAuswertung("Helligkeit",$this->CategoryIdData);;
        
        $directory = $this->configuration["LogDirectories"]["MotionLog"];
        $dosOps= new dosOps();
        $dosOps->mkdirtree($directory);
        $this->filename=$directory."Statistik.csv";   
        return($this->NachrichtenID);               // nur als Private deklariert           
        }

    /* allgemeine Initialisierung. Hier auf die Sub Initialisierungen  wie zB do_init_counter aufteilen.
     * gemeinsame Teile die für alle gleich sind hier bearbeiten. 
     *
     * Funktion Kann mit ID der variable oder mit false aufgerufen werden, variable=false wird für die Statistik Funktion verwendet, es wird do_init_statistic aufgerufen 
     * Es werden folgende Paraeter übergeben
     *      variable        ID der variable false oder nicht angegeben dann wird nur die Statistik Funktion benötigt, aber keine Variablen geloggt
     *      variablename    wenn es besondere Vorstellungen dazu bereits gibt
     *      value           für debug Zwecke
     *      typedev         als iPSComponent Parameter übergeben, trotzdem Datenbank befragen
     *
     * für die genaue Ermittlung des gewünschten Werte für variableTypeReg wird in dieser Reihenfolge abgefragt
     *      MySQL Datenbank
     *      typedev Wert, der wurde als Parameter des IPSComponent übergeben -> bevorzugte Variante
     *      irgendwie aus dem variableProfil,variableType erraten, noch nicht fertig programmiert
     *
     * Initialisisert wird hier - nach constructFirst noch
     * CategoryIdData,mirrorCatID
     * variable,variableProfil,variableType  iD der variable und ausgelesenes profil (entweder standard oder custom), Werte sind Sensor und Gerätespezifisch
     * mirrorType,mirrorProfil  werden von der Variable übernommen
     *
     * Ermittelt wird
     * variableTypeReg
     *
     *
     */

    protected function do_init($variable=false,$variablename=NULL,$value, $typedev, $debug=false)
            {
            $debugSql=false;
            $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
            $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
            $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);

            /**************** installierte Module und verfügbare Konfigurationen herausfinden 
             if (isset ($this->installedmodules["DetectMovement"]))
                {
                // Detect Movement agreggiert die Bewegungs Ereignisse (oder Verknüpfung) 
                IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
                IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');              
                }      */       
            if ($variable!==false)
                {
                if ($debug) 
                    {
                    echo "-------------------------------------------------------------------------\n";
                    if ($typedev==Null) echo "Sensor_Logging::do_init für Variable $variable ohne Type aufgerufen.\n";
                    else echo "Sensor_Logging::do_init für Variable $variable mit Type $typedev aufgerufen.\n";                    
                    }
                $this->$variable=$variable;
                $this->variableProfile=IPS_GetVariable($variable)["VariableProfile"];
                if ($this->variableProfile=="") $this->variableProfile=IPS_GetVariable($variable)["VariableCustomProfile"];
                $this->variableType=IPS_GetVariable($variable)["VariableType"];
                $this->mirrorType=$this->variableType;$this->mirrorProfile=$this->variableProfile;          // mirror immer gleich wie Variable anlegen
                //echo "do_init machen und getfromDatabase aufrufen.\n";                                
                $rows=getfromDatabase("COID",$variable,false,$debugSql);                // Bestandteil der MySQL_Library, false Alternative
                if ( ($rows === false) || (sizeof($rows) != 1) )
                    {
                    if ($typedev==Null)
                        {
                        $vartype=IPS_GetVariable($variable)["VariableType"];
                        if ($vartype==0) $this->variableTypeReg = "MOTION";            // kann STATE auch sein, tut aber nichts zur Sache, Boolean = MOTION
                        elseif ($vartype==1) $this->variableTypeReg = "BRIGHTNESS";     // Integer ist Brightness
                        else $this->variableTypeReg = "DATA";                           // Rest ist Data
                        IPSLogger_Inf(__file__, "Logging::do_init,getfromDatabase für ".IPS_GetName($variable)." ohne Ergebnis, selber bestimmen aufgrund des Typs $vartype geht nicht mehr. Annahme:".$this->variableTypeReg);
                        if ($debug) echo "    do_init,getfromDatabase ohne Ergebnis, selber bestimmen aufgrund des Typs : $vartype => ".$this->variableTypeReg."\n";    
                        }
                    else
                        {
                        if ($debug) echo "    do_init,getfromDatabase ohne Ergebnis, dann übergebenes typedev ($typedev) nehmen.\n";    
                        switch (strtoupper($typedev))
                            {
                            case "CO2":
                            case "BAROPRESSURE":
                            case "MOTION":
                            case "DIRECTION":                       // Durchgangssensoren
                            case "BRIGHTNESS":
                            case "CONTACT":
                            case "POWER":
                            case "HUMIDITY":
                            case "TEMPERATUR":
                            case "TEMPERATURE":
                            case "ENERGY":
                            case "RAIN_COUNTER":
                                $this->variableTypeReg = strtoupper($typedev);
                                break;                                 
                            default: 
                                $this->variableTypeReg = "DATA";
                                echo "*****************\n";
                                echo "do_init,getfromDatabase ohne Ergebnis und dann noch typedev mit einem unbekannten Typ ($typedev) übergeben -> Fehler.\n";    
                                IPSLogger_Err(__file__, "Logging::do_init,getfromDatabase ohne Ergebnis und dann noch typedev mit einem unbekannten Typ ($typedev) übergeben. Annahme:".$this->variableTypeReg);

                                break;
                            }    
                        }
                    }
                else    // getfromDatabase
                    {
                    //print_r($rows);   
                    $this->variableTypeReg = $rows[0]["TypeRegKey"];
                    if ($debug) echo "------\nAus der Datenbank ausgelesen: Register Typ ist \"".$this->variableTypeReg."\". Variable Typ unverändert \"".$this->variableType."\". Jetzt unterschiedliche Initialisierungen machen.\n";    
                    }
                switch (strtoupper($this->variableTypeReg))
                    {
                    case "CO2":
                    case "BAROPRESSURE":                        
                        $this->Type=2;      // Float für Alle
                        $NachrichtenID = $this->do_init_climate($variable, $variablename);
                        break;
                    case "MOTION":
                    case "DIRECTION":                       // Durchgangssensoren                    
                        $this->Type=0;      // Motion und DIRECTION ist boolean
                        $NachrichtenID=$this->do_init_motion($variable, $variablename, $value, $debug);
                        break;
                    case "CONTACT":
                        $this->Type=0;      // Contact ist boolean
                        $NachrichtenID=$this->do_init_contact($variable, $variablename,$value,$debug);
                        break;
                    case "BRIGHTNESS":
                        IPSLogger_Not(__file__, "Logging::do_init_brightness,getfromDatabase ".$this->variableTypeReg);
                        $this->Type=1;  // Brightness ist Integer
                        $NachrichtenID=$this->do_init_brightness($variable, $variablename,$value,$debug);
                        break;
                    case "TEMPERATURE":
                    case "TEMPERATUR":                    
                        $NachrichtenID = $this->do_init_temperature($variable, $variablename);
                        break;
                    case "HUMIDITY":
                        $NachrichtenID = $this->do_init_humidity($variable, $variablename);
                        break;
                    case "POWER":
                    case "DATA":            // neuer Default Typ
                    case "ENERGY":    
                        $NachrichtenID = $this->do_init_sensor($variable, $variablename);
                        break;
                    case "RAIN_COUNTER":    
                        $NachrichtenID = $this->do_init_counter($variable, $variablename);
                        break;
                    default:
                        $NachrichtenID = $this->do_init_sensor($variable, $variablename);                    
                        if ($this->variableTypeReg != "") 
                            {
                            echo "Fehler, do_init, kenne den Variable Typ (".$this->variableTypeReg.") nicht. Typ in die Function übernehmen. Aufgerufen mit ($variable, $variablename,$value,$typedev,...)\n";
                            IPSLogger_Wrn(__file__, "Logging::do_init, kenne den Variable Typ (".$this->variableTypeReg.") nicht. Typ in die Function übernehmen. Aufgerufen mit ($variable, $variablename,$value,$typedev,...)");
                            //$NachrichtenID=false;
                            }
                        break;
                    }
                }
            else $NachrichtenID=$this->do_init_statistics($debug);  
            if ($debug) 
                {
                echo "---------do_init abgeschlossen. Nachrichten werden hier gelogged: $NachrichtenID (";
                if ($NachrichtenID != "Ohne") echo IPS_GetName($NachrichtenID)."/".IPS_GetName(IPS_GetParent($NachrichtenID)).")";
                echo "\n";
                }            
            return ($NachrichtenID);    // damit die Nachrichtenanzeige richtig aufgesetzt wird 
            }

    /* Logging:getVariableName
     *
     * wird in construct und Set_LogValue verwendet, aufgerufen im jeweiligen construct über do_init_xxxx
     * wenn DetectMovement installiert ist und der DetectHandler ermittelt werden konnte:
     *     wird die ID des Mirror Registers mit DetectHandler->getMirrorRegister bzw getMirrorRegisterName aufgerufen 
     *     wenn variablename Null ist und getMirrorRegister ein Ergebnis geliefert hat, wird der Name von moid übernommen 
     *
     * wenn DetectMovement installiert ist und der DetectHandler nicht ermittelt werden konnte gibt es einen Fehler.
     *
     * wenn DetectMovement NICHT installiert ist:
     *     wird variablename übernommen 
     *
     * wenn Variablename immer noch Null ist
     *          wenn Parent Instanz ist, wird Name der Instanz übernommen 
     *          wenn der Name der Variable Cam_Motion ist wird der Name des Parent übernommen
     *          sonst der Name der Variable
     */

    public function getVariableName($variable,$variablename=Null,$debug=false)    
        {
        if ($debug) 
            {
            echo "Logging:getVariableName aufgerufen.\n"; 
            //print_r($this->installedmodules); 
            //print_r($this->DetectHandler);
            }
        /****************** Variablennamen für Spiegelregister von DetectMovement übernehmen oder selbst berechnen */
        if ( (isset ($this->installedmodules["DetectMovement"])) && ($this->DetectHandler !== Null) )
            {
            if ($debug) echo "Aufruf DetectHandler->getMirrorRegister($variable).\n";
            $moid=$this->DetectHandler->getMirrorRegister($variable,$debug);
            if ( ($variablename==Null) && ($moid !== false) ) 
                {
                $variablename=IPS_GetName($moid);
                if ($debug) echo "      getVariableName: DetectMovement installiert. Spiegelregister Name : \"$variablename/".IPS_GetName(IPS_GetParent($moid))."\" $moid   (from config)\n";
                }
            elseif ($debug) echo "      getVariableName: DetectMovement installiert. Spiegelregister Name : \"$variablename\" $moid  (default)\n";
            }
        elseif (isset ($this->installedmodules["DetectMovement"]) ) 
            {
            echo "Unknown DetectHandler.\n";
            IPSLogger_Err(__file__, "Logging::getVariableName, unknown DetectHandler for $variable $variablename.");
            }
        else echo "DetectMovement Module not installed.\n";

        if ($variablename==Null)
            {
            $result=IPS_GetObject($variable);
            $ParentId=(integer)$result["ParentID"];
            $object=IPS_GetObject($ParentId);
            if ( $object["ObjectType"] == 1)
                {				
                $variablename=IPS_GetName($ParentId);			// Variablenname ist der Parent Name wenn nicht anders angegeben, und der Parent eine Instanz ist.
                }
            elseif (IPS_GetName($variable)=="Cam_Motion")					/* was ist mit den Kameras, wird auch bei Temperatur und den anderen verwendet damit einheitlich ist  */
                {
                $variablename=IPS_GetName($ParentId);
                }
            else
                {
                $variablename=IPS_GetName($variable);			// Variablenname ist der Variablen Name wenn der Parent KEINE Instanz ist.
                }
            } 
        return ($variablename);
        }


    /* do_setVariableLogID, nutzt setVariableLogId aus der Logging class und die nutzt setVariableID
     * alle diesselbe class, ausser sie wird von der children class überschrieben
     *
     * wenn nicht variable NUll ist wird die LogVariablen ID rückgemeldet und angelegt, es wird auch variable übernommen, sonst wird nur variableLogID zurück gemeldet 
     * für die Rückverfolgung wird hidden auf false gesetzt
     *
     * setVariableLogId legt die Variable und die Log Variable variableLogID an und benötigt dafür variable, variablename, AuswertungID, variableType, variableProfile
     *
     * Ermittelt Werden
     *    variable, variableLogID 
     */

    private function do_setVariableLogID($variable,$debug=false)
        {
        if ($variable<>Null)
            {
            $this->variable=$variable;
            $this->variableLogID=$this->setVariableLogId($this->variable,$this->variablename,$this->AuswertungID,$this->variableType,$this->variableProfile,$debug);                   // $this->variableLogID schreiben
            if ($debug) echo "Aufruf setVariableLogId(".$this->variable.",".$this->variablename.",".$this->AuswertungID.") mit Ergebnis ".$this->variableLogID."\n";
            IPS_SetHidden($this->variableLogID,false);
            }
        else 
            {
            echo "do_setVariableLogID failed.\n";
            IPSLogger_Err(__file__, "Logging::do_setVariableLogID failed.");
            }
        return ($this->variableLogID);
        }

    /*
     * wird in construct und Set_LogValue verwendet, von do_setVariableLogID aufgerufen
     * verändert keine class register
     *
     * variable wird nur als Referenz übergeben, ruft setVariableId mit den anderen Parametern auf
     *      $variablename wird in der Kategorie $AuswertungID mit $type und $profile angelegt  
     * wenn Logging Status falsch ist oder Variable noch nicht angelegt ist nachbessern
     */

    public function setVariableLogId($variable, $variablename, $AuswertungID,$type,$profile,$debug=false)    
        {
        /* einfaches Logging, formattiert oder nicht */
        if ($debug) echo '    Logging:setVariableLogId Spiegelregister erstellen, Basis ist '.$variable.' Name "'.$variablename.'" in '.$AuswertungID." (".IPS_GetName($AuswertungID).") mit $type und $profile mit Wert ".GetValueIfFormatted($variable)."\n";
        IPSLogger_Dbg(__file__, 'CustomComponent Motion_Logging Construct: Spiegelregister erstellen, Basis ist '.$variable.' Name "'.$variablename.'" in '.$AuswertungID." mit Wert ".GetValueIfFormatted($variable));	

        /* lokale Spiegelregister aufsetzen */  
        $variableLogID=$this->setVariableId($variablename, $AuswertungID,$type,$profile,$debug);
        return($variableLogID);                    
        }

    /* wie setVariableLogId nur ohne echo Wert $variable */

    public function setVariableId($variablename, $AuswertungID,$type,$profile,$debug=false)    
        {
        if ($debug) echo '    Logging:setVariableId Spiegelregister erstellen mit Name "'.$variablename.'" in '.$AuswertungID." (".IPS_GetName($AuswertungID).") mit $type und $profile.\n";
        /* lokale Spiegelregister aufsetzen */  
        $variableLogID=CreateVariableByName($AuswertungID,$variablename,$type,$profile,null, 10,null );
        if ( (AC_GetLoggingStatus($this->archiveHandlerID,$variableLogID)==false) || (AC_GetAggregationType($this->archiveHandlerID,$variableLogID) != 0) )
            {                                  			
            AC_SetLoggingStatus($this->archiveHandlerID,$variableLogID,true);
            AC_SetAggregationType($this->archiveHandlerID,$variableLogID,0);      /* normaler Wwert */
            IPS_ApplyChanges($this->archiveHandlerID);
            }
        return($variableLogID);                    
        }

    /*
     * Wert auf die konfigurierten remoteServer laden, gemeinsame Funktion im Component
     *  remServer   ist die Liste der Remote Server mit Url und Passwort
     *  RemoteOID   ist die mit Doppelpunkt dargestellte Liste mit ServerName und Remote OID
     *
     * Anhand der MessageHandler Config wird der Parameter übergeben, da sind insgesamt 3 Parametzer drinnen, es muss der richtige Parameter extrahiert werden
     */

    public function RemoteLogValue($value, $remServer, $RemoteOID, $debug=false )
        {
        //if ($debug) echo "RemoteLogValue aufgerufen:\n";            
        if ($RemoteOID != Null)
            {
            if ($debug) echo "RemoteLogValue($value,".json_encode($remServer).",$RemoteOID) aufgerufen.\n";
            $params= explode(';', $RemoteOID);
            foreach ($params as $val)
                {
                $para= explode(':', $val);
                if ($debug) echo "Wert :".$val." Anzahl ",count($para)." -> sollen 2 sein.\n";
                if (count($para)==2)
                    {
                    $Server=$remServer[$para[0]]["Url"];
                    if ($remServer[$para[0]]["Status"]==true)
                        {
                        $rpc = new JSONRPC($Server);
                        $roid=(integer)$para[1];
                        //echo "Server : ".$Server." Name ".$para[0]." Remote OID: ".$roid."\n";
                        $rpc->SetValue($roid, $value);
                        }
                    }
                }
            }
        elseif ($debug) echo "RemoteLogValue mit RemoteOID==Null aufgerufen. Keine RemoteServer konfiguriert.\n"; 
        }

    /*****************************************************************************************/

    function LogConfig()
        {
        return($this->config);
        }

    /* Ausgabe message in einem File mit dem Namen log_File
     */

	function LogMessage($message, $debug=false)
		{
        if ($debug) echo "LogMessage, Nachricht in File: ".$this->log_File." mit text \"$message\" schreiben\n";
		if ($this->log_File != "No-Output")
			{
			$handle3=fopen($this->log_File, "a");
			fwrite($handle3, date("d.m.y H:i:s").";".$this->prefix.$message."\r\n");
			fclose($handle3);
			//echo ""LogMessage: Schreibe in Datei ".$this->log_File." die Zeile ".$message."\n";
			}
		}

    /* Ausgabe message in einem Nachrichtenspeicher, abhängig von config entweder als html Tabelle oder Register basierter Tabelle
     * Tabelle wird identifiziert mit nachrichteninput_id
     * für html wird auch storeTableID als Datenspeicher verwendet
     */

	function LogNachrichten($message, $debug=false)
		{
        if ($this->config["HTMLOutput"])
            {
            $sumTableID = @IPS_GetObjectIDByName("MessageTable", $this->nachrichteninput_Id); 
            if ($sumTableID===false) echo "LogNachrichten  ".$this->nachrichteninput_Id."\n";
            else
                {
                if ($this->storeTableID)
                    {
                    $table=GetValue($this->storeTableID);
                    if ($table=="") $messages=array();                      // empty at start, detect and clear array
                    else $messages = json_decode($table,true);
                    //$messages = json_decode(GetValue($this->storeTableID),true);
                    $latestTime=time();
                    foreach ($messages as $timeStamp => $entry) if ($timeStamp>$latestTime) $latestTime=$timeStamp; 
                    if (isset($messages[$latestTime])) $messages[($latestTime+1)]=$message;                                                 //nur eine Nachricht pro Sekunde, kleiner Workaround
                    else $messages[$latestTime]=$message;                                                 
                    krsort($messages);
                    if (count($messages)>50)
                        {
                        end( $messages );
                        $key = key( $messages );
                        unset ($messages[$key]);
                        }
                    SetValue($this->storeTableID,json_encode($messages));
                    }    
                SetValue($sumTableID,$this->PrintNachrichten(true));                //html Formatierung
                }
            if ($debug) echo "LogNachrichten ".$this->nachrichteninput_Id.",html Text \"$message\" schreiben. Speicherort Tabelle ist $sumTableID. Daten sind hier gespeichert: ".$this->storeTableID." \n";
            }   
        else
            {         								
            if ($debug) echo "LogNachrichten ".$this->nachrichteninput_Id." in die erste Zeile ".$this->zeile1." (".IPS_GetName($this->zeile1)."/".IPS_GetName(IPS_GetParent($this->zeile1)).") den Wert $message speichern. \n"; 
            //IPSLogger_Dbg(__file__, "LogNachrichten ".$this->nachrichteninput_Id." in die erste Zeile ".$this->zeile1." (".IPS_GetName($this->zeile1)."/".IPS_GetName(IPS_GetParent($this->zeile1)).") den Wert $message speichern");	
            if ($this->nachrichteninput_Id != "Ohne")
                {
                SetValue($this->zeile16,GetValue($this->zeile15));
                SetValue($this->zeile15,GetValue($this->zeile14));
                SetValue($this->zeile14,GetValue($this->zeile13));
                SetValue($this->zeile13,GetValue($this->zeile12));
                SetValue($this->zeile12,GetValue($this->zeile11));
                SetValue($this->zeile11,GetValue($this->zeile10));
                SetValue($this->zeile10,GetValue($this->zeile9));
                SetValue($this->zeile9,GetValue($this->zeile8));
                SetValue($this->zeile8,GetValue($this->zeile7));
                SetValue($this->zeile7,GetValue($this->zeile6));
                SetValue($this->zeile6,GetValue($this->zeile5));
                SetValue($this->zeile5,GetValue($this->zeile4));
                SetValue($this->zeile4,GetValue($this->zeile3));
                SetValue($this->zeile3,GetValue($this->zeile2));
                SetValue($this->zeile2,GetValue($this->zeile1));
                SetValue($this->zeile1,date("d.m.y H:i:s")." : ".$message);
                }
            else
                {
                SetValue($this->zeile16,GetValue($this->zeile15));
                SetValue($this->zeile15,GetValue($this->zeile14));
                SetValue($this->zeile14,GetValue($this->zeile13));
                SetValue($this->zeile13,GetValue($this->zeile12));
                SetValue($this->zeile12,GetValue($this->zeile11));
                SetValue($this->zeile11,GetValue($this->zeile10));
                SetValue($this->zeile10,GetValue($this->zeile9));
                SetValue($this->zeile9,GetValue($this->zeile8));
                SetValue($this->zeile8,GetValue($this->zeile7));
                SetValue($this->zeile7,GetValue($this->zeile6));
                SetValue($this->zeile6,GetValue($this->zeile5));
                SetValue($this->zeile5,GetValue($this->zeile4));
                SetValue($this->zeile4,GetValue($this->zeile3));
                SetValue($this->zeile3,GetValue($this->zeile2));
                SetValue($this->zeile2,GetValue($this->zeile1));
                SetValue($this->zeile1,date("d.m.y H:i:s")." : ".$message);
                if (isset ($this->installedmodules["DetectMovement"]))
                    {
                    SetValue($this->zeile16DM,GetValue($this->zeile15DM));
                    SetValue($this->zeile15DM,GetValue($this->zeile14DM));
                    SetValue($this->zeile14DM,GetValue($this->zeile13DM));
                    SetValue($this->zeile13DM,GetValue($this->zeile12DM));
                    SetValue($this->zeile12DM,GetValue($this->zeile11DM));
                    SetValue($this->zeile11DM,GetValue($this->zeile10DM));
                    SetValue($this->zeile10DM,GetValue($this->zeile09DM));
                    SetValue($this->zeile09DM,GetValue($this->zeile08DM));
                    SetValue($this->zeile08DM,GetValue($this->zeile07DM));
                    SetValue($this->zeile07DM,GetValue($this->zeile06DM));
                    SetValue($this->zeile06DM,GetValue($this->zeile05DM));
                    SetValue($this->zeile05DM,GetValue($this->zeile04DM));
                    SetValue($this->zeile04DM,GetValue($this->zeile03DM));
                    SetValue($this->zeile03DM,GetValue($this->zeile02DM));
                    SetValue($this->zeile02DM,GetValue($this->zeile01DM));
                    SetValue($this->zeile01DM,date("d.m.y H:i:s")." : ".$message);
                    echo "    Detect Movement Ausgabe zusätzlich in ".$this->zeile01DM." \n";
                    }
                }
            }

		}

    /* alle Zeilen entweder als text oder html Tabelle ausgeben */

	function PrintNachrichten($html=false)
		{
		$result=false;
        $PrintHtml="";
        $PrintHtml.='<style>';             
        $PrintHtml.='.messagy table,td {align:center;border:1px solid white;border-collapse:collapse;}';
        $PrintHtml.='.messagy table    {table-layout: fixed; width: 100%; }';
        $PrintHtml.='.messagy td:nth-child(1) { width: 30%; }';
        $PrintHtml.='.messagy td:nth-child(2) { width: 70%; }';
        $PrintHtml .= "table.statiSub { border-collapse: collapse; border: 1px solid #ddd; width:100%; align:center; }";                   // Untergruppe, tabelle in Tabelle
        $PrintHtml .= ".statiSub th, .statiSub td { padding: 5px; border: solid 1px #777; width:20%; font-style:italic;}";        
        $PrintHtml.='</style>';        
        $PrintHtml.='<table class="messagy">';
        if ($this->config["HTMLOutput"] && $this->storeTableID)
            {
            $messageJson=GetValue($this->storeTableID);
            $messages = json_decode($messageJson,true);
            //IPSLogger_Inf(__file__, "Logging:PrintNachrichten ".$messageJson."   ".$this->log_File."   ".$this->zeile1);
            $PrintHtml .= '<tr><td>Date</td><td>Message</td></tr>';
            if (is_array($messages))
                {
                if (count($messages)>0) 
                    {
                    foreach ($messages as $timeIndex => $message)
                        {
                        $PrintHtml .= '<tr><td>'.date("d.m H:i:s",$timeIndex).'</td><td>'.$message.'</td></tr>';
                        }
                    }
                }
            }  
		elseif ($this->nachrichteninput_Id != "Ohne")
		    {
            $result="";
            $count=sizeof($this->zeile);
            if ($count>1)
                {
                for ($i=1;$i<=$count;$i++)
                    {
                    $result    .= GetValue($this->zeile[$i])."\n";
                    //$PrintHtml .= '<tr><td>'.str_pad($i, 2 ,'0', STR_PAD_LEFT).'</td><td>'.GetValue($this->zeile[$i]).'</td></tr>';
                    $PrintHtml .= '<tr><td>'.GetValue($this->zeile[$i]).'</td></tr>';
                    }
                }
            else $result=GetValue($this->zeile1)."\n".GetValue($this->zeile2)."\n".GetValue($this->zeile3)."\n".GetValue($this->zeile4)."\n".GetValue($this->zeile5)."\n".GetValue($this->zeile6)."\n".GetValue($this->zeile7)."\n".GetValue($this->zeile8)."\n".GetValue($this->zeile9)."\n".GetValue($this->zeile10)."\n".GetValue($this->zeile11)."\n".GetValue($this->zeile12)."\n".GetValue($this->zeile13)."\n".GetValue($this->zeile14)."\n".GetValue($this->zeile15)."\n".GetValue($this->zeile16)."\n";
			}
        $PrintHtml.='</table>';        

		if ($html) return ($PrintHtml);
        else return $result;
		}

    function CreateZeilen($oid, $count=16)
        {
        $zeile=array();
        for ($i=1;$i<=$count;$i++)
            {
            $zeile[$i] = CreateVariable("Zeile".str_pad($i, 2 ,'0', STR_PAD_LEFT),3,$oid, ($i*10) );
            }
        return ($zeile);
        }

    function shiftZeile($message, $zeile=false, $count=16)
        {
        if ($zeile===false) $zeile=$this->zeile;
        if (count($zeile) != $count) 
            {
            echo "shiftZeile: Warnung, Groesse des Nachrichtenspeichers passt nicht zusammen.\n";
            $count = count($zeile);
            }
        //print_r($zeile);
        $ct=$count;
        for ($i=1;$i<$count;$i++) 
            {
            echo str_pad($i, 2 ,'0', STR_PAD_LEFT)."   ".GetValue($zeile[$ct])."\n";
            SetValue($zeile[$ct],GetValue($zeile[$ct-1]));
            $ct--;
            }
        echo str_pad($i, 2 ,'0', STR_PAD_LEFT)."   ".GetValue($zeile[$ct])."\n";
        SetValue($zeile[$ct],$message);
        }

    function shiftZeileDebug()
        {
        $this->shiftZeile("", $this->zeile, 16);
        echo $this->PrintNachrichten(true);
        }


	function IPSpathinfo($InputID="")
		{
		if ($InputID=="") $InputID=$this->nachrichteninput_Id;
		$path="";
		$oid=$InputID;
		do {	
			if ($path=="") $path=IPS_GetName($oid);
			else $path=IPS_GetName($oid).".".$path;
			echo ">>".$path."\n";
			$oid=IPS_GetParent($oid);	
		} while ($oid <> 0);
	
		return $path;
		}

	function status()
	   {
	   return true;
	   }
		
	}

/********************** Routine nur zum Spass eingefuegt, DEPRECIATED  
 *
 * wird nicht verwendet
 *
 */
	
	class IPSComponentLogger {


		private $tempObject;
		private $RemoteOID;
		private $tempValue;

		/**
		 * @public
		 *
		 * Initialisierung eines IPSModuleSensor_IPStemp Objektes
		 *
		 * @param string $tempObject Licht Object/Name (Leuchte, Gruppe, Programm, ...)
		 * @param integer $RemoteOID OID die gesetzt werden soll
		 * @param string $tempValue Wert für Beleuchtungs Änderung
		 */
		public function __construct($var1=null, $lightObject=null, $lightValue=null) {
			$this->tempObject   = $lightObject;
			$this->RemoteOID    = $var1;
			$this->tempValue    = $lightValue;
			IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
			$this->remServer    = RemoteAccess_GetConfigurationNew();
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
		public function HandleEvent($variable, $value, IPSModuleSensor $module){
			echo "Bewegungs Message Handler für VariableID : ".$variable." mit Wert : ".$value." \n";

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
			return get_class($this);
		}

	}
	
	

/**********************************************************
 *
 * class ipsobject (DEPRICIATED)
 *
 * erster Versuch einer Klasse zum Suchen und Ausgeben von Objekten. Wird nicht mehr oft verwendet.
 *
 * nicht für neue Verwendung geeignet
 *
 *  __construct
 *  oprint
 *  oparent
 *  osearch
 *
 **************************************************************/

class ipsobject
	{
	var $object_ID=0;

	function __construct($objectid=0)
	   {
	   $this->object_ID=$objectid;
		//echo "Init ".get_class($this)." : ";
		//var_dump($this);
		}

	function oprint($item="")
		{
		//echo "Hallo";
		//var_dump($this);
		$result=IPS_GetObject($this->object_ID);
		echo $this->object_ID." \"".$result["ObjectName"]."\" ".$result["ParentID"]."\n";
		$childrenIds=$result["ChildrenIDs"];
		foreach ($childrenIds as $childrenId)
			{
			$result=IPS_GetObject($childrenId);
			$resultname=$result["ObjectName"];
			if ($item != "")
			   {
				if (strpos($resultname,$item)===false)
					{
					$nachrichtok="";
					}
				else
					{
					$nachrichtok="gefunden";
					$NachrichtenscriptID=$childrenId;
					}
				}
			echo "  ".$childrenId."  \"".$resultname."\" ";
			switch ($result["ObjectType"])
			   {
			   case "6": echo "Link"; break;
			   case "5": echo "Media"; break;
			   case "4": echo "Ereignis"; break;
			   case "3": echo "Skript"; break;
			   case "2": echo "Variable"; break;
			   case "1": echo "Instanz"; break;
			   case "0": echo "Kategorie"; break;
			   }
			if ($item != "")
				{
				echo " ".$nachrichtok." \n";
				}
			else
				{
				echo " \n";
				}
			}
		}

	function oparent()
		{
		$result=IPS_GetObject($this->object_ID);
		return $result["ParentID"];
		}

	function osearch($item="")
		{
		$result=IPS_GetObject($this->object_ID);
		//echo $this->object_ID." \"".$result["ObjectName"]."\" ".$result["ParentID"]."\n";
		$childrenIds=$result["ChildrenIDs"];
		foreach ($childrenIds as $childrenId)
			{
			$result=IPS_GetObject($childrenId);
			$resultname=$result["ObjectName"];
			if (strpos($resultname,$item)===false)
				{
				$nachrichtok="";
				}
			else
				{
				$nachrichtok="gefunden";
				return $NachrichtenscriptID=$childrenId;
				}
			//echo "  ".$childrenId."  \"".$resultname."\" ";
			/* switch ($result["ObjectType"])
			   {
			   case "6": echo "Link"; break;
			   case "5": echo "Media"; break;
			   case "4": echo "Ereignis"; break;
			   case "3": echo "Skript"; break;
			   case "2": echo "Variable"; break;
			   case "1": echo "Instanz"; break;
			   case "0": echo "Kategorie"; break;
			   } */
			}
		}


	}
	
	

	/** @}*/
?>