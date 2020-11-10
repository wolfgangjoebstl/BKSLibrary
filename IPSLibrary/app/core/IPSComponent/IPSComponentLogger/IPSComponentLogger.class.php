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

	IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');

/**********************************************************
 *
 * class ipsobject
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

/******************************************************
 *
 * class logging
 *
 * Speicherung von Nachrichten, als Einträge in einem File, als Werte in Objekten und Ausgabe als html tabelle und als echo
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
 *  GetComponentParams
 *  GetComponent
 *  GetEreignisID
 *  CreateCategoryAuswertung
 *  CreateCategoryNachrichten
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

    /* init at construct */
    protected       $installedmodules;
	private         $prefix;							/* Zuordnung File Log Data am Anfang nach Zeitstempel */
	private         $log_File="Default";
	private         $nachrichteninput_Id="Default";
    private         $config=array();                    /* interne Konfiguration */
    private         $zeile=array();                     /* Nachrichteninput Objekte OIDs */
    private         $zeileDM=array();                   /* Nachrichteninput Objekte OIDs, eigenes für Device Management */
    private         $storeTableID = false;              /* ermöglicht längere Speichertiefen für Nachrichten */

    /* init at do_init_xxxx */
    protected       $configuration;
    // $variable                                        // definiert in children class
    protected       $variablename;
    protected       $CategoryIdData;
	protected       $mirrorCatID, $mirrorNameID;            // Spiegelregister in CustomComponent um eine Änderung zu erkennen


	private $script_Id="Default";


    /* wird bereits in den children classes verwendet und dort initialisiert */

    protected $DetectHandler;               /* DetectMovement/Humidity ... ist auch ein Teil der Aktivitäten */
    protected $archiveHandlerID;                    /* Zugriff auf Archivhandler iD, muss nicht jedesmal neu berechnet werden */ 

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
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);			
        $this->installedmodules=$moduleManager->GetInstalledModules();			
		//echo "Logfile Construct\n";
		$this->prefix=$prefix;
		//$this->log_File=$logfile;
		$this->log_File=str_replace(array('<', '>', ':', '"', '/', '\\', '|', '?', '*'), '', $logfile);             // ales wegloeschen das einem korrekten Filenamen widerspricht
		$this->nachrichteninput_Id=$nachrichteninput_Id;
        $this->config["Prefix"]=$prefix;
        $this->config["HTMLOutput"]=$html;
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
		if ($this->nachrichteninput_Id != "Ohne")
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
            if ($this->config["HTMLOutput"]) 
                {
                $sumTableID = CreateVariable("MessageTable", 3,  $this->nachrichteninput_Id, 900 , '~HTMLBox',null,null,""); // obige Informationen als kleine Tabelle erstellen
                $this->storeTableID = CreateVariable("MessageStorage", 3,  $this->nachrichteninput_Id, 910 , '',null,null,""); // die Tabelle in einem größerem Umfeld speichern
                SetValue($sumTableID,$this->PrintNachrichten(true));
                }
			}
		else
			{
			$moduleManager_CC = new IPSModuleManager('CustomComponent');
			$CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
			echo "  Kategorien im Datenverzeichnis Custom Components: ".$CategoryIdData."   (".IPS_GetName($CategoryIdData).")\n";
			$name="Bewegung-Nachrichten";
			$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);
			if ($vid==0) $vid = CreateCategory($name,$CategoryIdData, 10);
            $this->config["MessageInputID"]=$vid; 
            $this->zeile = $this->CreateZeilen($vid);
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
			if (isset ($this->installedmodules["DetectMovement"]))
				{
				/* nur wenn Detect Movement installiert zusaetzlich ein Motion Log fuehren */
				$moduleManager_DM = new IPSModuleManager('DetectMovement');     /*   <--- change here */
				$CategoryIdData     = $moduleManager_DM->GetModuleCategoryID('data');
				//echo "  Kategorien im Datenverzeichnis Detect Movement :".$CategoryIdData."   ".IPS_GetName($CategoryIdData)."\n";
				$name="Motion-Nachrichten";
				$vid=@IPS_GetObjectIDByName($name,$CategoryIdData);	
                $this->zeileDM = $this->CreateZeilen($vid);		
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

    public function GetEreignisID() {
        return ($this->EreignisID);
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


    /* wird beim construct aufgerufen, wenn keine Datanbank angelegt wurde
        * kann auch direkt für die Speicherung der Daten in der Datenbank verwendet werden. 
        */

    public function do_init_motion($variable, $variablename, $value,$debug=false)
        {
        if ($debug) echo "IPSComponentSensor_Motion, HandleEvent für Motion VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Wert : ".($value?"Bewegung":"Still")." \n";
        IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für Motion VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert '.($value?"Bewegung":"Still"));
        if ($debug) echo "      Aufruf do_init_motion:\n";
        if (isset ($this->installedmodules["DetectMovement"])) $this->DetectHandler = new DetectMovementHandler();  // für getVariableName benötigt 
        $this->variablename = $this->getVariableName($variable, $variablename);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

        /* Konfiguration einlesen, ob zusätzliche Spiegelregister mit Delay notwendig sind */ 
        $this->configuration=get_IPSComponentLoggerConfig();

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        //echo "  Kategorien im Datenverzeichnis : ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData).").\n";
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
        $name="MotionMirror_".$this->variablename;
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,0,$this->variableProfile);       /* 0 boolean */

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
            $erID=CreateVariableByName($this->motionDetect_DataID,"Gesamt_Ereignisspeicher",3, '', null,10000,null );
            $this->GesamtID=$erID;
            //echo "  Gesamt Ereignisspeicher aufsetzen : ".$erID." \n";
            $erID=CreateVariableByName($this->motionDetect_DataID,"Gesamt_Ereigniszaehler",1, '', null,10000,null );
            $this->GesamtCountID=$erID;
            //echo "  Gesamt Ereigniszähler aufsetzen   : ".$erID." \n";
            }

        $directories=get_IPSComponentLoggerConfig();
        if (isset($directories["LogDirectories"]["MotionLog"]))	$directory=$directories["LogDirectories"]["MotionLog"];
        else $directory="C:/Scripts/Switch/";
        $dosOps= new dosOps();
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Bewegung.csv";                
        return("Ohne");
        }

    /* wird beim construct aufgerufen, wenn keine Datanbank angelegt wurde
        * kann auch direkt für die Speicherung der Daten in der Datenbank verwendet werden. 
        */

    public function do_init_brightness($variable, $variablename,$value, $debug=false)
        {
        if ($debug) echo "IPSComponentSensor_Motion, HandleEvent für Brightness VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Wert $value : ".GetValueIfFormatted($variable)." \n";
        IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für Brightness VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert $value : '.GetValueIfFormatted($variable));
        if (isset ($this->installedmodules["DetectMovement"])) $this->DetectHandler = new DetectBrightnessHandler();  // für getVariableName benötigt 
        $this->variablename = $this->getVariableName($variable, $variablename, $debug);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
        if ($debug) echo "   Aufruf do_init_brightness Variablenname abgeändert von $variablename auf ".$this->variablename.":\n";
        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        //echo "  Kategorien im Datenverzeichnis : ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData).").\n";
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
        $name="HelligkeitMirror_".$this->variablename;
        if ($debug) echo "      CreateVariableByName at ".$this->mirrorCatID." (".IPS_GetName($this->mirrorCatID).") mit Name \"$name\" Type ".$this->variableType." Profile ".$this->variableProfile." Variable available : ".(@IPS_GetVariableIDByName($name, $this->mirrorCatID)?"Yes":"No")." \n";
        //CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,1,$this->variableProfile);       /* 1 integer */

        if ($debug) echo "      Create Category to store the Move-LogNachrichten und Spiegelregister in ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData)."):\n";	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Helligkeit",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Helligkeit",$this->CategoryIdData);;
        if ($debug) echo "         done ".$this->NachrichtenID. "(".IPS_GetName($this->NachrichtenID).") und ".$this->AuswertungID." (".IPS_GetName($this->AuswertungID).").\n";
        
        echo "lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen:\n";
        $this->do_setVariableLogID($variable,$debug);

        $directories=get_IPSComponentLoggerConfig();
        if (isset($directories["LogDirectories"]["MotionLog"]))	$directory=$directories["LogDirectories"]["MotionLog"];
        else $directory="C:/Scripts/Switch/";
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
        if ($debug) echo "IPSComponentSensor_Motion, HandleEvent für Contact VariableID : ".$variable." (".IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).") mit Wert $value: ".GetValueIfFormatted($variable)." \n";
        IPSLogger_Dbg(__file__, 'IPSComponentSensor_Motion, HandleEvent: für Contact VariableID '.$variable.'('.IPS_GetName(IPS_GetParent($variable)).'.'.IPS_GetName($variable).') mit Wert $value: '.GetValueIfFormatted($variable));
        if (isset ($this->installedmodules["DetectMovement"])) $this->DetectHandler = new DetectContactHandler();  // für getVariableName benötigt   <--- change here 
        $this->variablename = $this->getVariableName($variable, $variablename, $debug);           // $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen
        if ($debug) echo "   Aufruf do_init_contact Variablenname abgeändert von $variablename auf ".$this->variablename.":\n";
        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        //echo "  Kategorien im Datenverzeichnis : ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData).").\n";
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
        $name="KontaktMirror_".$this->variablename;
        if ($debug) echo "      CreateVariableByName at ".$this->mirrorCatID." (".IPS_GetName($this->mirrorCatID).") mit Name \"$name\" Type ".$this->variableType." Profile ".$this->variableProfile." Variable available : ".(@IPS_GetVariableIDByName($name, $this->mirrorCatID)?"Yes":"No")." \n";
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float ~Temperature*/
        $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,1,$this->variableProfile);       /* 1 integer */

        if ($debug) echo "      Create Category to store the Move-LogNachrichten und Spiegelregister in ".$this->CategoryIdData." (".IPS_GetName($this->CategoryIdData)."):\n";	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Kontakt",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Kontakt",$this->CategoryIdData);;
        if ($debug) echo "         done ".$this->NachrichtenID. "(".IPS_GetName($this->NachrichtenID).") und ".$this->AuswertungID." (".IPS_GetName($this->AuswertungID).").\n";
        
        echo "lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen:\n";
        $this->do_setVariableLogID($variable,$debug);

        $directories=get_IPSComponentLoggerConfig();
        if (isset($directories["LogDirectories"]["MotionLog"]))	$directory=$directories["LogDirectories"]["MotionLog"];
        else $directory="C:/Scripts/Switch/";
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

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);

        $name="TemperatureMirror_".$this->variablename;
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "TEMPERATURE":
                echo "do_init_temperature, Create Mirror Register $name as Float and ".$this->variableProfile."\n";
                if ( ($this->variableProfile <> "~Temperature") && ($this->variableProfile <> "Netatmo.Temperatur") )
                    {
                    IPSLogger_Wrn(__file__, "do_init_temperature, Create Mirror Register $name as Float with Profile ".$this->variableProfile." instead of \"~Temperature\".");
                    }
                $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,"~Temperature");       /* 2 float Netatmo und Homematic bekommen das selbe Profil */
                break;
            default:
                echo "Create Mirror Register $name as Float und ".$this->variableProfile."\n";
                IPSLogger_Wrn(__file__, "do_init_temperature, Create Mirror Register $name as Float with Profile ".$this->variableProfile.", TypeReg is ".$this->variableTypeReg.".");
                $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 2 float für Default*/
                break;
            }
        if ($this->debug) echo "    Temperatur_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData).")\n";
        
        /* Create Category to store the LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Temperatur",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Temperatur",$this->CategoryIdData);
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        if ($this->debug) echo "   Uebergeordnete Variable : ".$this->variablename."\n";
        $directories=get_IPSComponentLoggerConfig();
        if (isset($directories["LogDirectories"]["TemperatureLog"]))
                { $directory=$directories["LogDirectories"]["TemperatureLog"]; }
        else {$directory="C:/Scripts/Temperature/"; }	
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

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);                
        $name="HumidityMirror_".$this->variablename;
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float */
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 2 float */
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "CONTACT1":
                echo "do_init_humidity, Create Mirror Register $name as Integer und ".$this->variableProfile."\n";
                IPSLogger_Wrn(__file__, "do_init_humidity, Create Mirror Register $name as Integer with Profile ".$this->variableProfile." if necessary.");

                //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,1,$this->variableProfile);       /* 1 integer für Typ CO2 */
                break;
            default:
                echo "Create Mirror Register $name as Float und ".$this->variableProfile."\n";
                IPSLogger_Wrn(__file__, "do_init_humidity, Create Mirror Register $name as ".$this->variableType." with Profile ".$this->variableProfile.", TypeReg is ".$this->variableTypeReg.".");
                $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float für Default*/
                break;
            }
        /* Create Category to store the LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Feuchtigkeit",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Feuchtigkeit",$this->CategoryIdData);
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        //echo "Uebergeordnete Variable : ".$variablename."\n";
        $directories=get_IPSComponentLoggerConfig();
        if (isset($directories["LogDirectories"]["HumidityLog"]))
                { $directory=$directories["LogDirectories"]["HumidityLog"]; }
        else {$directory="C:/Scripts/Sensor/"; }	
        $dosOps= new dosOps();              
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Feuchtigkeit.csv";
        return($this->NachrichtenID);               // nur als Private deklariert
        }

    /* Initialisierung für Sensor */

    public function do_init_sensor($variable, $variablename)
        {
        /**************** installierte Module und verfügbare Konfigurationen herausfinden */
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $this->installedmodules=$moduleManager->GetInstalledModules();

        if (isset ($this->installedmodules["DetectMovement"]))
            {
            /* Detect Movement kann auch Sensorwerte agreggieren */
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            $this->DetectHandler = new DetectSensorHandler();                            // zum Beispiel für die Evaluierung der Mirror Register
            }

        $this->variablename = $this->getVariableName($variable, $variablename);           // function von IPSComponent_Logger, $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
        $name="SensorMirror_".$this->variablename;
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float */
        //$this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 2 float */
        echo "    Sensor_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData)."/".IPS_GetName(IPS_GetParent($this->CategoryIdData))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($this->CategoryIdData))).")\n";
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "POWER":           /* Power Wirkleistung und Wirkenergie von AMIS */
            case "ENERGY":
                echo "do_init_sensor, Create Mirror Register $name as Integer und ".$this->variableProfile."\n";
                if ( ($this->variableProfile <> "~Power") && ($this->variableProfile <> "~Electricity") )
                    {                
                    IPSLogger_Wrn(__file__, "do_init_sensor, Create Mirror Register $name as Float with Profile ".$this->variableProfile." not supported.");
                    }
                $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 1 integer für Typ CO2 */
                break;
            default:
                echo "Create Mirror Register $name as ".$this->variableType." und ".$this->variableProfile."\n";
                IPSLogger_Wrn(__file__, "do_init_sensor, Create Mirror Register $name as ".$this->variableType." with Profile ".$this->variableProfile.", TypeReg is ".$this->variableTypeReg.".");
                $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,$this->variableType,$this->variableProfile);       /* 2 float für Default*/
                break;
            }        
        /* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Sensor",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Sensor",$this->CategoryIdData);
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        //echo "Uebergeordnete Variable : ".$this->variablename."\n";
        $directories=get_IPSComponentLoggerConfig();
        if (isset($directories["LogDirectories"]["SensorLog"]))
                { $directory=$directories["LogDirectories"]["SensorLog"]; }
        else {$directory="C:/Scripts/Sensor/"; }	
        $dosOps= new dosOps(); 
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Sensor.csv";
        return($this->NachrichtenID);               // nur als Private deklariert
        }

    /* Initialisierung für besonderen Climate Sensor */

    public function do_init_climate($variable, $variablename)
        {
        /**************** installierte Module und verfügbare Konfigurationen herausfinden */
        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);
        $this->installedmodules=$moduleManager->GetInstalledModules();

        if (isset ($this->installedmodules["DetectMovement"]))
            {
            /* Detect Movement kann auch Sensorwerte agreggieren */
            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            $this->DetectHandler = new DetectClimateHandler();                            // zum Beispiel für die Evaluierung der Mirror Register
            }

        $this->variablename = $this->getVariableName($variable, $variablename);           // function von IPSComponent_Logger, $this->variablename schreiben, entweder Wert aus DetectMovement Config oder selber bestimmen

        /**************** Speicherort für Nachrichten und Spiegelregister herausfinden */		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
        $name="ClimateMirror_".$this->variablename;
        echo "do_init_climate($variable, $variablename). [".$this->variableTypeReg.",".$this->variableType.",".$this->variableProfile."]\n";
        switch ($this->variableTypeReg)                 // alternativ vom Inputregister abhängig machen
            {
            case "CO2":
                echo "do_init_climate, Create Mirror Register $name as Integer und ".$this->variableProfile."\n";
                if ($this->variableProfile <> "Netatmo.CO2") IPSLogger_Wrn(__file__, "do_init_climate, Create Mirror Register $name as Integer with Profile ".$this->variableProfile." instead of \"Netatmo.CO2\".necessary.");
                $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,1,$this->variableProfile);       /* 1 integer für Typ CO2 */
                break;
            default:
                echo "Create Mirror Register $name as Float und ".$this->variableProfile."\n";
                IPSLogger_Wrn(__file__, "do_init_climate, Create Mirror Register $name as Float with Profile ".$this->variableProfile.", TypeReg is ".$this->variableTypeReg.".");
                $this->mirrorNameID=CreateVariableByName($this->mirrorCatID,$name,2,$this->variableProfile);       /* 2 float für Default*/
                break;
            }
        echo "    Climate_Logging:construct Kategorien im Datenverzeichnis:".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData)."/".IPS_GetName(IPS_GetParent($this->CategoryIdData))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($this->CategoryIdData))).")\n";
        
        /* Create Category to store the Move-LogNachrichten und Spiegelregister*/	
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Climate",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Climate",$this->CategoryIdData);
        $this->do_setVariableLogID($variable);            // lokale Spiegelregister mit Archivierung aufsetzen, als Variablenname wird, wenn nicht übergeben wird, der Name des Parent genommen 

        /* Filenamen für die Log Eintraege herausfinden und Verzeichnis bzw. File anlegen wenn nicht vorhanden */
        //echo "Uebergeordnete Variable : ".$this->variablename."\n";
        $directories=get_IPSComponentLoggerConfig();
        if (isset($directories["LogDirectories"]["ClimateLog"]))
                { $directory=$directories["LogDirectories"]["ClimateLog"]; }
        else {$directory="C:/Scripts/Sensor/"; }	
        $dosOps= new dosOps(); 
        $dosOps->mkdirtree($directory);
        $this->filename=$directory.$this->variablename."_Sensor.csv";
        return($this->NachrichtenID);               // nur als Private deklariert
        }

    /* wird beim construct aufgerufen, wenn keine Variable übergeben wurde.
        * Klasse wird für Statistische Auswertungen verwendet.
        */

    public function do_init_statistics()
        {
        echo "      Aufruf do_init_statistics:\n";
        /**************** Speicherort für Nachrichten und Spiegelregister definieren */		
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $this->CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        $this->mirrorCatID  = CreateCategoryByName($this->CategoryIdData,"Mirror",10000);
        $this->NachrichtenID=$this->CreateCategoryNachrichten("Helligkeit",$this->CategoryIdData);
        $this->AuswertungID=$this->CreateCategoryAuswertung("Helligkeit",$this->CategoryIdData);;
        $directories=get_IPSComponentLoggerConfig();
        if (isset($directories["LogDirectories"]["MotionLog"]))	$directory=$directories["LogDirectories"]["MotionLog"];
        else $directory="C:/Scripts/Switch/";
        $dosOps= new dosOps();
        $dosOps->mkdirtree($directory);
        $this->filename=$directory."Statistik.csv";      
        }

    /* do_setVariableLogID, nutzt setVariableLogId aus der Logging class 
    * kann nicht diesselbe class sein, da this verwendet wird
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
        else echo "do_setVariableLogID failed.\n";
        return ($this->variableLogID);
        }

    /*
        * wird in construct und Set_LogValue verwendet
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
        elseif (isset ($this->installedmodules["DetectMovement"]) ) echo "Unknown DetectHandler.\n";
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


    /*
     * wird in construct und Set_LogValue verwendet
     */

    public function setVariableLogId($variable, $variablename, $AuswertungID,$type,$profile,$debug=false)    
        {
        /* einfaches Logging, formattiert oder nicht */
        if ($debug) echo '    Logging:setVariableLogId Spiegelregister erstellen, Basis ist '.$variable.' Name "'.$variablename.'" in '.$AuswertungID." (".IPS_GetName($AuswertungID).") mit $type und $profile ";
        $variabletyp=IPS_GetVariable($variable);
        if ($variabletyp["VariableProfile"]!="")
            {  /* Formattierung vorhanden */
            if ($debug) echo " mit Wert ".GetValueFormatted($variable)."\n";
            IPSLogger_Dbg(__file__, 'CustomComponent Motion_Logging Construct: Spiegelregister erstellen, Basis ist '.$variable.' Name "'.$variablename.'" in '.$AuswertungID." mit Wert ".GetValueFormatted($variable));
            }
        else
            {
            if ($debug) echo " mit Wert ".GetValue($variable)."\n";
            IPSLogger_Dbg(__file__, 'CustomComponent Motion_Logging Construct: Spiegelregister erstellen, Basis ist '.$variable.' Name "'.$variablename.'" in '.$AuswertungID." mit Wert ".GetValue($variable));
            }	

        /* lokale Spiegelregister aufsetzen */  
        $variableLogID=@IPS_GetObjectIDByName($variablename,$AuswertungID);
        if ( ($variableLogID===false) || (AC_GetLoggingStatus($this->archiveHandlerID,$variableLogID)==false) || (AC_GetAggregationType($this->archiveHandlerID,$variableLogID) != 0) )
            {                                  			
            $variableLogID=CreateVariableByName($AuswertungID,$variablename,$type,$profile,null, 10,null );
            AC_SetLoggingStatus($this->archiveHandlerID,$variableLogID,true);
            AC_SetAggregationType($this->archiveHandlerID,$variableLogID,0);      /* normaler Wwert */
            IPS_ApplyChanges($this->archiveHandlerID);
            }
        return($variableLogID);                    
        }

    /* wie setVariableLogId nur ohne echo Wert $variable */

    public function setVariableId($variablename, $AuswertungID,$type,$profile)    
        {
        echo '    Logging:setVariableId Spiegelregister erstellen mit Name "'.$variablename.'" in '.$AuswertungID." (".IPS_GetName($AuswertungID).") mit $type und $profile.\n";
        /* lokale Spiegelregister aufsetzen */  
        $variableLogID=@IPS_GetObjectIDByName($variablename,$AuswertungID);
        if ( ($variableLogID===false) || (AC_GetLoggingStatus($this->archiveHandlerID,$variableLogID)==false) || (AC_GetAggregationType($this->archiveHandlerID,$variableLogID) != 0) )
            {                                  			
            $variableLogID=CreateVariableByName($AuswertungID,$variablename,$type,$profile,null, 10,null );
            AC_SetLoggingStatus($this->archiveHandlerID,$variableLogID,true);
            AC_SetAggregationType($this->archiveHandlerID,$variableLogID,0);      /* normaler Wwert */
            IPS_ApplyChanges($this->archiveHandlerID);
            }
        return($variableLogID);                    
        }

    /*
        * Wert auf die konfigurierten remoteServer laden, gemeinsame Funktion im Component
        */

    public function RemoteLogValue($value, $remServer, $RemoteOID )
        {
        if ($RemoteOID != Null)
            {
            $params= explode(';', $RemoteOID);
            foreach ($params as $val)
                {
                $para= explode(':', $val);
                //echo "Wert :".$val." Anzahl ",count($para)." \n";
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
        }



	function LogMessage($message)
		{
        //echo "LogMessage: ".$this->log_File."  $message \n";
		if ($this->log_File != "No-Output")
			{
			$handle3=fopen($this->log_File, "a");
			fwrite($handle3, date("d.m.y H:i:s").";".$this->prefix.$message."\r\n");
			fclose($handle3);
			//echo ""LogMessage: Schreibe in Datei ".$this->log_File." die Zeile ".$message."\n";
			}
		}

	function LogNachrichten($message, $debug=false)
		{
        if ($debug) echo "LogNachrichten ".$this->nachrichteninput_Id." in die erste Zeile ".$this->zeile1." (".IPS_GetName($this->zeile1)."/".IPS_GetName(IPS_GetParent($this->zeile1)).") den Wert $message speichern. \n"; 
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
        if ($this->config["HTMLOutput"]) 
            {
            $sumTableID = IPS_GetObjectIDByName("MessageTable", $this->nachrichteninput_Id); 
            if ($this->storeTableID)
                {
                $messages = json_decode(GetValue($this->storeTableID),true);
                $messages[time()]=$message;
                krsort($messages);
                if (count($messages)>50)
                    {
                    end( $messages );
                    $key = key( $messages );
                    unset ($messages[$key]);
                    }
                SetValue($this->storeTableID,json_encode($messages));
                }    
            SetValue($sumTableID,$this->PrintNachrichten(true));
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

    function shiftZeile($message, $zeile, $count=16)
        {
        //print_r($zeile);
        for ($i=1;$i<=$count;$i++) echo str_pad($i, 2 ,'0', STR_PAD_LEFT)."   ".GetValue($zeile[$i])."\n";
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

/********************** Routine nur zum Spass eingefuegt */
	
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
	
	
	
	

	/** @}*/
?>