<?

/*
	 * @defgroup Gartensteuerung
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 *
	 *
	 * @file          Gartensteuerung.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/


/************************************************************
 *
 * Gartensteuerung Library
 *
 * verschiedene Funktionen um innerhalb und ausserhalb des Moduls Autosteuerung eine Giessanlage anzusteuern
 *
 * construct		mit bereits zahlreichen Ermittlungen, wie zB die regenStatistik, regenStand2h, regenStand48h
 *
 * getConfig_aussentempID()         $this->variableTempID=get_aussentempID();  oder eine OID 
 * getConfig_raincounterID()        $this->variableID=get_raincounterID();     oder eine OID oder ein Wert aus CustomComponents
 *
 * Giessdauer		Berechnung der Giessdauer anhand der Durchschnittstemperatur und Regenmenge
 *
 * listRainEvents	Zusammenfassung der vergangenen Regenschauer in lesbare Funktionen/Zeilen
 *                  es können auch mehr als 10 Tage angefordert wereden, bleiben aber nicht als Werte der Class
 * listEvents		Ausgabe der Nachrichten Events
 *
 * zusätzliche Funktionen für Regen Statistik (immer 10 Jahre Werte):
 *
 * Auswertung Regenmenge als 1/7/30/30/360/360 Statistik 
 * Auswertung Regenmenge als Aggregation pro Monat
 * Auswertung Regendauer (benötigt inkrementelle Werte aus CustomComponents)
 *
 ****************************************************************/
 
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

class Gartensteuerung
	{
	var         $installedmodules;                                  // welche Module sind installiert und können verwendet werden
	
	private 	$archiveHandlerID;
	private		$debug;
	private		$tempwerte, $tempwerteLog, $werteLog, $werte;
	private		$variableTempID, $variableID;                       // Aussentemperatur und Regensensor
	private 	$GartensteuerungConfiguration;
	
	public 		$regenStatistik;
	public 		$letzterRegen, $regenStand2h, $regenStand48h;
	
	public      $categoryId_Auswertung, $categoryId_Register,$categoryId_Statistiken;
    public 		$GiessTimeID,$GiessDauerInfoID;
    public      $StatistikBox1ID, $StatistikBox2ID, $StatistikBox3ID;                   // html boxen für statistische Auswertungen
	
	public 		$log_Giessanlage;									// logging Library class

    private     $RainRegisterIncrementID;                           // in CustomComponent gibt es einen Incremental Counter
    private     $categoryRegisterID;                                // die Category für die Regenregister, Counter of CustomComponents
    public      $DauerKalendermonate, $RegenKalendermonate;         // Ausertung Regendauer (wenn inkrementell da) und Regenmenge der letzten 10 Jahre

	var $heatManager;                                               // Einbindung des Stromheizung Modul, mit den Schaltgruppen
	var $CategoryId_Stromheizung;
	var $switchCategoryHeatId, $groupCategoryHeatId , $prgCategoryHeatId;

	/******************
	 *
	 * Vorbereitung und Ermittlung der Basisinformationen wie 
	 *
	 * Übergabe der Konfiguration, als Funktion, hat den Vorteil und funktioniert auch mit RemoteAccess
	 *   $this->variableTempID=get_aussentempID();  oder eine OID 
	 *   $this->variableID=get_raincounterID();     oder eine OID oder ein Wert aus CustomComponents
	 *
	 * $this->regenStatistik	wird zweimal berechnet, in construct und in listRainEvents
     * hier berechnen um für die Giessdauerberechung bereits den Wert für letzten Regen die letzten 2 und 48 Stunden zu haben
     *
     * aus dem Archive werden sich nur die letzten 2 Tage für Temperatur und die letzten 10 Tage für Regen genommen
	 *
     * es werden in Program.IPSLibrary.data.modules.Gartensteuerung Kategorien angelegt
     *          Gartensteuerung-Auswertung
     *          Statistiken
     *
     * Funktionen, Überblick:
     *  getConfig_aussentempID
     *  getConfig_raincounterID
     *  getConfig_RemoteAccess_Address
     *  setGartensteuerungConfiguration
     *  getConfig_Gartensteuerung
     *  fromdusktilldawn
     *  Giessdauer
     *  listRainEvents
     *  writeRainEventsHtml
     *  listEvents
     *  getRainStatistics
     *  writeKalendermonateHtml
     *  writeOverviewMonthsHtml
     *
     *
     *
	 ************************************************/
		
	public function __construct($starttime=0,$starttime2=0,$debug=false)
		{
		$this->debug=$debug;
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager)) 
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
			}
		$this->installedModules 				= $moduleManager->GetInstalledModules();


		if ( isset($this->installedModules["Stromheizung"] ) )
			{
			include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");						
			$this->CategoryId_Stromheizung			= @IPS_GetObjectIDByName("Stromheizung",$this->CategoryId_Ansteuerung);
			$this->heatManager = new IPSHeat_Manager();
			
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');
			$this->switchCategoryHeatId  = IPS_GetObjectIDByIdent('Switches', $baseId);
			$this->groupCategoryHeatId   = IPS_GetObjectIDByIdent('Groups', $baseId);
			$this->programCategoryHeatId = IPS_GetObjectIDByIdent('Programs', $baseId);			
			}	

		$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
		$this->categoryId_Auswertung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
		$this->categoryId_Register  	= CreateCategory('Gartensteuerung-Register', $CategoryIdData, 200);
        $this->categoryId_Statistiken	= CreateCategory('Statistiken',   $CategoryIdData, 200);
        
		$this->GiessTimeID	            = @IPS_GetVariableIDByName("GiessTime", $this->categoryId_Auswertung); 
		$this->GiessDauerInfoID	        = @IPS_GetVariableIDByName("GiessDauerInfo",$this->categoryId_Auswertung);
		
        /* Statistikmodul */
	    $this->StatistikBox1ID			= @IPS_GetVariableIDByName("Regenmengenkalender"   , $this->categoryId_Statistiken); 
	    $this->StatistikBox2ID			= @IPS_GetVariableIDByName("Regendauerkalender"   , $this->categoryId_Statistiken); 
	    $this->StatistikBox3ID			= @IPS_GetVariableIDByName("Regenereignisse" , $this->categoryId_Statistiken);   

		$this->GartensteuerungConfiguration=$this->setGartensteuerungConfiguration();
        
        if ($this->debug)
            {
            echo "Gartensteuerung: construct aufgerufen. Debug Mode.\n";
            echo "Gartensteuerung Kategorie Data ist : ".$CategoryIdData."   (".IPS_GetName($CategoryIdData).")\n";
            echo "Gartensteuerung Kategorie Data.Gartensteuerung-Register ist : ".$this->categoryId_Register."   (".IPS_GetName($this->categoryId_Register).")\n";
            //echo "GiesstimeID ist ".$this->GiessTimeID."\n";
            echo "-------\n";
            }        
		$object2= new ipsobject($CategoryIdData);
		$object3= new ipsobject($object2->osearch("Nachricht"));
		$NachrichtenInputID=$object3->osearch("Input");
		$this->log_Giessanlage=new Logging("C:\Scripts\Log_Giessanlage2.csv",$NachrichtenInputID,IPS_GetName(0).";Gartensteuerung;");

		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		$endtime=time();
		if ($starttime==0)  { $starttime=$endtime-60*60*24*2; }  /* die letzten zwei Tage Temperatur*/
		if ($starttime2==0) { $starttime2=$endtime-60*60*24*10; }  /* die letzten 10 Tage Niederschlag*/

		/* getConfiguration */
        $this->categoryRegisterID = $this->getCategoryRegisterID();
		//$this->variableTempID = $this->getConfig_aussentempID();                                // wenn keine Konfig vorhanden wird false gemeldet
		//$this->variableID = $this->getConfig_raincounterID();           // benötigt categoryRegisterID, wenn keine Konfig vorhanden wird false gemeldet
        $this->variableTempID   = $this->GartensteuerungConfiguration["Configuration"]["AussenTemp"];
        $this->variableID       = $this->GartensteuerungConfiguration["Configuration"]["RainCounter"];
		//$Server=$this->getConfig_RemoteAccess_Address();                // wenn keine Konfig vorhanden wird false gemeldet
        $Server                 = $this->GartensteuerungConfiguration["Configuration"]["RemoteAccessAdr"];

		if ($this->debug)
			{
			echo"--------Class Construct Giessdauerberechnung:\n";
			}
        $this->tempwerteLog = array();
        $this->tempwerte    = array();
        $this->werteLog     = array();
        $this->werte        = array();

		If ( ($Server=="") || ($Server==false) )
			{
  			//$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];             // lokaler ArchiveHandler definiert
            if ($this->variableTempID)
                {
			    $this->tempwerteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->variableTempID, $starttime, $endtime,0);		
	   		    $this->tempwerte = AC_GetAggregatedValues($this->archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);	/* Tageswerte agreggiert */
                }
            if ($this->variableID)
                {
			    $this->werteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
		   	    $this->werte = AC_GetAggregatedValues($this->archiveHandlerID, $this->variableID, 1, $starttime2, $endtime,0);	/* Tageswerte agreggiert */
                }
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$this->archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];         // lokaler ArchiveHandler wird vom Remote Hander überschrieben
			$this->tempwerteLog = $rpc->AC_GetLoggedValues($this->archiveHandlerID, $this->variableTempID, $starttime, $endtime,0);		
   			$this->tempwerte = $rpc->AC_GetAggregatedValues($this->archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);
			$this->werteLog = $rpc->AC_GetLoggedValues($this->archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
			$this->werte = $rpc->AC_GetAggregatedValues($this->archiveHandlerID, $this->variableID, 1, $starttime2, $endtime,0);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}

		/* Letzen Regen ermitteln, alle Einträge der letzten 10 Tage durchgehen n getRainevents*/
        $this->regenStatistik = $this->getRainevents($this->werteLog);              // fertige Routine, alle übergebenen Werte bearbeiten. Zweiter Parameter begrenzt Anzahl der gefundenen Events 

		$this->regenStand2h=0;
		$this->regenStand48h=0;

        $regenStand=0;			// der erste Regenwerte, also aktueller Stand 
		$vorwert=0; 
		foreach ($this->werteLog as $wert)
			{
			if ($vorwert==0) 
		   		{ 
				$regenStand=$wert["Value"];
				}
			/* Regenstand innerhalb der letzten 2 Stunden ermitteln */
			if (((time()-$wert["TimeStamp"])/60/60)<2)
				{
				$this->regenStand2h=$regenStand-$wert["Value"];
				}
			/* Regenstand innerhalb der letzten 48 Stunden ermitteln */
			if (((time()-$wert["TimeStamp"])/60/60)<48)
				{
				$this->regenStand48h=$regenStand-$wert["Value"];
				}
			}  // Regenwerte der Reihe nach durchgehen
			
		if ($this->debug) { echo "\n\n"; }
		}

    /* von CustomComponents die Counter Category finden */
	
     public function getCategoryRegisterID($find="Counter")
        {
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        return(@IPS_GetObjectIDByName($find."-Auswertung",$CategoryIdData));
        }               

	/******************************************************************
	 *
	 * Einlesen der Konfiguration, manche Parameter können auch komplizierter als Funktion (customizing) 
	 * berechnet oder eingelesen werden. Wenn kein Eintrag in der Konfiguration dann Funktion aufrufen
	 *
	 ***************************************************************************/

	function getConfig_aussentempID($config=false,$debug=false)
		{
        if ($debug || $this->debug) 
            {
            echo "getConfig_aussentempID aufgerufen.\n";
            $debug = $debug || $this->debug;
            }
        if ($config===false) $Configuration = $this->GartensteuerungConfiguration;
        else $Configuration = $config;

		if ( isset($Configuration["AussenTemp"])==true) $Configuration["AUSSENTEMP"]=$Configuration["AussenTemp"];
		if ( isset($Configuration["Aussentemp"])==true) $Configuration["AUSSENTEMP"]=$Configuration["Aussentemp"];                
		if ( (isset($Configuration["AUSSENTEMP"])==true) && (($Configuration["AUSSENTEMP"]) !== false) )
			{
            /* wenn die Variable in der Config angegeben ist diese nehmen, sonst die eigene Funktion aufrufen */
    		if ((integer)$Configuration["AUSSENTEMP"]==0) 
	    		{
                /* wenn sich der String als integer Zahl auflösen lässt, auch diese Zahl nehmen, Achtung bei Zahlen im String !!! */
    			if ($debug) echo "Alternative Erkennung des Tempoeratursensors, String als OID Wert für den AUSSENTEMP angegeben: \"".$Configuration["AUSSENTEMP"]."\". Jetzt in CustomComponents schauen ob vorhanden.\n";
			    $TempAuswertungID=$this->getCategoryRegisterID("Temperatur");
                $RegisterTempID=@IPS_GetObjectIDByName($Configuration["AUSSENTEMP"],$TempAuswertungID);
	    		if ($debug) echo "Check : Temperatur Kategorie   ".$TempAuswertungID."  Temperatur Register  ".$RegisterTempID."\n";
		    	if ( ($TempAuswertungID==false) || ($RegisterTempID==false) ) $fatalerror=true;
                $variableTempID=$RegisterTempID;
                }    
			else $variableTempID=(integer)$Configuration["AUSSENTEMP"];
			}
		else 
			{
            if (function_exists("get_aussentempID")) $variableTempID=get_aussentempID();
            else 
                {
                echo "Fehler, keine Configuration für AUSSENTEMP und function get_aussentempID ist auch nicht vorhanden.\n";
                print_r($this_>GartensteuerungConfiguration);
                $variableTempID=false;
                }
			}
		return($variableTempID); 		
		}

    /*******************
     * 
     * übernimmt oder liest die Konfiguration
     * Angabe des Register für den RainCounters - Regenmessstandes als OID oder Name. Nach der Bearbeitung den Namen auf OID umstellen
     *
     * Index kann RAINCOUNTER, Raincounter, RainCounter oder raincounter sein
     *
     * Speicherort in dem der Namen gesucht wird ist data von CustomComponents
     * wenn gar keine Angabe, die alte Funktion suchen
     *
     * Es gibt neben dem normalen Regenstand (Counter) auch noch ein inkrementelles Register 
     * 
     * tatsächlich ein Counter:       $Configuration["RAINCOUNTER"]."_Counter"
     * Wert wenn es regnet ist hier:  $Configuration["RAINCOUNTER"]
     *
     * CustomComponent für die Berechnung
     *
     */

	function getConfig_raincounterID($config=false,$debug=false)
		{
        if ($debug || $this->debug) $debug=true;   
        if ($debug) echo "getConfig_raincounterID aufgerufen.\n";            
        if ($config===false) 
            {
            if ($debug) echo "Read Configuration direkt from GartensteuerungConfiguration.\n";
            $Configuration = $this->GartensteuerungConfiguration;
            if (isset($Configuration["Configuration"])) $Configuration = $Configuration["Configuration"];
            }
        else $Configuration = $config;

		if ( isset($Configuration["RainCounter"])==true) $Configuration["RAINCOUNTER"]=$Configuration["RainCounter"];
		if ( isset($Configuration["Raincounter"])==true) $Configuration["RAINCOUNTER"]=$Configuration["Raincounter"];
		if ( isset($Configuration["raincounter"])==true) $Configuration["RAINCOUNTER"]=$Configuration["raincounter"];

        //$this->RainRegisterIncrementID=0;                         // wird nur mehr beim ersten Mal richtig berechnet, dann ist RAINCOUNTER eine OID
		if ( ( (isset($Configuration["RAINCOUNTER"]))==true) && ($Configuration["RAINCOUNTER"]!==false) )
			{
            /* wenn die Variable in der Config angegeben ist diese nehmen, sonst die eigene Funktion aufrufen */
    		if ((integer)$Configuration["RAINCOUNTER"]==0) 
	    		{
                /* wenn sich der String als integer Zahl auflösen lässt, auch diese Zahl nehmen, Achtung bei Zahlen im String !!! */
    			if ($debug) echo "Alternative Erkennung des Regensensors, String als OID Wert für den RAINCOUNTER angegeben: \"".$Configuration["RAINCOUNTER"]."\". Jetzt in CustomComponents schauen ob vorhanden.\n";
			    $CounterAuswertungID=$this->getCategoryRegisterID();
                $this->RainRegisterIncrementID=@IPS_GetObjectIDByName($Configuration["RAINCOUNTER"],$CounterAuswertungID);
	    		if ($debug) echo "Check : Counter Kategorie   ".$CounterAuswertungID."  Incremental Counter Register  ".$this->RainRegisterIncrementID."\n";
		    	if ( ($CounterAuswertungID==false) || ($this->RainRegisterIncrementID==false) ) $fatalerror=true;
           		
                $RegisterCounterID=@IPS_GetObjectIDByName($Configuration["RAINCOUNTER"]."_Counter",$CounterAuswertungID);
	        	if ($debug) echo "Check : Counter Kategorie   ".$CounterAuswertungID."  Counter Register  ".$RegisterCounterID."\n";
    			if ( ($CounterAuswertungID==false) || ($RegisterCounterID==false) ) $fatalerror=true;
                $variableID=$RegisterCounterID;
                }    
			else $variableID=(integer)$Configuration["RAINCOUNTER"];
			}
		else 
			{	
            if (function_exists("get_raincounterID")) $variableID=get_raincounterID();
            else 
                {
                echo "***Fehler getConfig_raincounterID, keine Configuration für RAINCOUNTER oder Wert false und function get_raincounterID ist auch nicht vorhanden.\n";
                print_r($Configuration);
                $variableID=false;
                }
			}
        if (IPS_VariableExists($variableID)) return($variableID); 		
        else return (false);
		}

    /* Wenn einmal der RainCounter ermittelt wurde ist diese Funktion nicht mehr notwendig.
     * nur wenn die OID noch nicht feststeht
     *
	function getConfig_rainMeterID($config,$debug=false)
		{
        $CounterAuswertungID=$this->getCategoryRegisterID();
        $meterID=@IPS_GetObjectIDByName($config["RAINCOUNTER"],$CounterAuswertungID);
		return($meterID); 		
		}  */


    /*******************
     * 
     * übernimmt oder liest die Konfiguration
     *
     */

	function getConfig_waterPumpID($config=false,$debug=false)
		{
        if ($debug || $this->debug) 
            {
            $debug=true;
            echo "getConfig_waterPumpID aufgerufen.\n";            
            }
        if ($config===false) 
            {
            echo "Read Configuration direkt from GartensteuerungConfiguration.\n";
            $Configuration = $this->GartensteuerungConfiguration;
            if (isset($Configuration["Configuration"])) $Configuration = $Configuration["Configuration"];
            }
        else $Configuration = $config;
        
		if ( isset($Configuration["WaterPump"])==true) $Configuration["WATERPUMP"]=$Configuration["WaterPump"];
		if ( isset($Configuration["Waterpump"])==true) $Configuration["WATERPUMP"]=$Configuration["Waterpump"];
		if ( isset($Configuration["waterpump"])==true) $Configuration["WATERPUMP"]=$Configuration["waterpump"];

		if ( ( (isset($Configuration["WATERPUMP"]))==true) && ($Configuration["WATERPUMP"]!==false) )
			{
            if ($debug) echo "   Wert übergeben ist : ".$Configuration["WATERPUMP"]."\n";                
            /* wenn die Variable in der Config angegeben ist diese nehmen, sonst die eigene Funktion aufrufen */
    		if ((integer)$Configuration["WATERPUMP"]==0) 
	    		{
                if (isset($this->installedModules["Stromheizung"] ))
                    {
                    /* wenn sich der String als integer Zahl auflösen lässt, auch diese Zahl nehmen, Achtung bei Zahlen im String !!! */
    			    if ($debug) echo "   Alternative Erkennung der Wasserpumpe, String als OID Wert für den WATERPUMP angegeben: \"".$Configuration["WATERPUMP"]."\". Jetzt in Stromheizjng/IPLights schauen ob vorhanden.\n";
                    $result=array();
                    $lightName=$Configuration["WATERPUMP"];
                    $switchId = @$this->heatManager->GetSwitchIdByName($lightName);
                    $groupId = @$this->heatManager->GetGroupIdByName($lightName);
                    $programId = @$this->heatManager->GetProgramIdByName($lightName);
                    //echo "IPSHeat Switch ".$switchId." Group ".$groupId." Program ".$programId."\n";
                    if ($switchId)
                        {
                        $result["ID"]=$switchId;
                        $result["TYP"]="Switch";
                        $result["NAME"]=$lightName;
                        $result["MODULE"]="IPSHeat";
                        }
                    elseif ($groupId)
                        {	
                        $result["ID"]=$groupId;
                        $result["TYP"]="Group";
                        $result["NAME"]=$lightName;
                        $result["MODULE"]="IPSHeat";
                        }
                    elseif ($programId)
                        {	
                        $result["ID"]=$programId;
                        $result["TYP"]="Program";
                        $result["NAME"]=$lightName;
                        $result["MODULE"]="IPSHeat";
                        }
                    else $variableID=false;
                    }
                else $variableID=false;
                }    
			else $variableID=(integer)$Configuration["WATERPUMP"];
			}
		else 
			{	
            if (function_exists("set_gartenpumpe")) return("set_gartenpumpe");
            else 
                {
                echo "*** Fehler getConfig_waterPumpID, keine Configuration für WATERPUMP und function set_gartenpumpe ist auch nicht vorhanden.\n";
                print_r($Configuration);
                $variableID=false;
                }
			}
        if (isset($result["ID"])) return ($result);
        if ( ($variableID !==false) && (IPS_VariableExists($variableID)) ) return($variableID); 		
        else return (false);
		}

    /*******************
     * 
     * übernimmt oder liest die Konfiguration
     * Angabe des Register für die Ventilsteuerung
     *
     */

	function getConfig_valveControlIDs($config=false,$debug=false)
		{
        $failure=true;
        $confResult=Array();
        if ($debug || $this->debug) echo "getConfig_valveControlIDs aufgerufen.\n";            
        if ($config===false) 
            {
            echo "Read Configuration direkt from GartensteuerungConfiguration.\n";
            $Configuration = $this->GartensteuerungConfiguration;
            if (isset($Configuration["Configuration"])) $Configuration = $Configuration["Configuration"];
            }
        else $Configuration = $config;
        
		if ( isset($Configuration["VALVECONTROL"])==true) $Configuration["ValveControl"]=$Configuration["VALVECONTROL"];
		if ( isset($Configuration["Valvecontrol"])==true) $Configuration["ValveControl"]=$Configuration["Valvecontrol"];
		if ( isset($Configuration["valvecontrol"])==true) $Configuration["ValveControl"]=$Configuration["valvecontrol"];

        //print_R($Configuration);
		if ( ( (isset($Configuration["ValveControl"]))==true) && ($Configuration["ValveControl"]!==false) && (is_array($Configuration["ValveControl"])) )
			{
            foreach ($Configuration["ValveControl"] as $index => $valve)
                {
                if ($debug) echo "     $index  : $valve    ";
                /* wenn der Name aus der Stromheizung in der Config angegeben ist diese nehmen, sonst die OID mit Homematic Funktion aufrufen 
                 * die Giesskreisinfo anhand dem Index suchen, mitabspeichern
                 * den Typ der Variable rausfinden,allgemeine Routine, eigentlich 
                 */
                if ((integer)$valve==0) 
                    {
                    if ($debug) echo "String";
                    if (isset($this->installedModules["Stromheizung"] ))
                        {
                        /* wenn sich der String als integer Zahl auflösen lässt, auch diese Zahl nehmen, Achtung bei Zahlen im String !!! */
                        if ($debug) echo "   Alternative Erkennung der Wasserpumpe, String als OID Wert für den WATERPUMP angegeben: \"".$Configuration["WATERPUMP"]."\". Jetzt in Stromheizjng/IPLights schauen ob vorhanden.\n";
                        $result=array();
                        $lightName=$valve;
                        $switchId = @$this->heatManager->GetSwitchIdByName($lightName);
                        $groupId = @$this->heatManager->GetGroupIdByName($lightName);
                        $programId = @$this->heatManager->GetProgramIdByName($lightName);
                        //echo "IPSHeat Switch ".$switchId." Group ".$groupId." Program ".$programId."\n";
                        if ($switchId)
                            {
                            $confResult[$index]["ID"]=$switchId;
                            $confResult[$index]["TYP"]="Switch";
                            $confResult[$index]["NAME"]=$lightName;
                            $confResult[$index]["MODULE"]="IPSHeat";
                            }
                        elseif ($groupId)
                            {	
                            $confResult[$index]["ID"]=$groupId;
                            $confResult[$index]["TYP"]="Group";
                            $confResult[$index]["NAME"]=$lightName;
                            $confResult[$index]["MODULE"]="IPSHeat";
                            }
                        elseif ($programId)
                            {	
                            $confResult[$index]["ID"]=$programId;
                            $confResult[$index]["TYP"]="Program";
                            $confResult[$index]["NAME"]=$lightName;
                            $confResult[$index]["MODULE"]="IPSHeat";
                            }
                        else $variableID=false;
                        }
                    else $variableID=false;
                    }  
                else
                    {
                    if ($debug) echo "Integer,";    
                    //$available = @IPS_VariableExists($valve);                 // die Variable für einen Wert
                    $available = @IPS_ObjectExists($valve);                     // die Switching Instanz
                    if ($available) 
                        {
                        if ($debug) echo IPS_GetName($valve);
                        $confResult[$index]=$valve;
                        }
                    else 
                        {
                        if ($debug) echo "Variable NICHT vorhanden";		
                        $failure=false;
                        }
                    }
                if (isset($Configuration[$index])) if ($debug) echo ",".$Configuration[$index];
                else 
                    {
                    if ($debug) echo ",Index $index nicht in Konfiguration angelegt";
                    }
                if ($debug) echo "\n";
                }
			}
		else 
			{	

			}
        return ($confResult);
		}

    /*******************
     * 
     * übernimmt oder liest die Konfiguration
     * Angabe des Register für die RemoteAccessAdr
     *
     */

	function getConfig_RemoteAccess_Address($config=false,$debug=false)
		{
        if ($debug || $this->debug) echo "getConfig_RemoteAccess_Address aufgerufen.\n";     
        if ($config===false) $Configuration = $this->GartensteuerungConfiguration;
        else $Configuration = $config;

		if ( isset($Configuration["RemoteAccessAdr"])==true) $Configuration["REMOTEACCESSADR"]=$Configuration["RemoteAccessAdr"];
		if ( isset($Configuration["Remoteaccessadr"])==true) $Configuration["REMOTEACCESSADR"]=$Configuration["Remoteaccessadr"];                  
		if ( (isset($Configuration["REMOTEACCESSADR"])==true)  && ($Configuration["REMOTEACCESSADR"]!==false) )
			{
			$Server=$Configuration["REMOTEACCESSADR"];
			}
		else 
			{			
            if (function_exists("get_RemoteAccess_Address")) $this->variableID=get_RemoteAccess_Address();
            else 
                {
                echo "Fehler, getConfig_RemoteAccess_Address: keine Configuration für REMOTEACCESSADR und function get_RemoteAccess_Address ist auch nicht vorhanden.\n";
                print_r($this->GartensteuerungConfiguration);
                $Server=false;
                }
			}
		return ($Server);
		}

    /* Konfiguration auswerten und standardisiseren 
			"KREISE" => 5,
			"TEMPERATUR-MITTEL" => 19,    // Wenn Aussentemperatur im Mittel ueber diesen Wert UND Niederschlag kleiner REGN48H dann Giessen 
			"TEMPERATUR-MAX" => 28,			// wenn es in den letzten  10 Tage weniger als REGEN10T geregnet hat ODER die Maiximaltemperatur den Wert TEMPERATUR-MAX ueberschreitet doppelt so lange giessen 
			"REGEN48H" => 3,              // Wenn Aussentemperatur im Mittel ueber TEMPERATUR-MITTEL UND Niederschlag kleiner diesen Wert dann Giessen 
			"REGEN10T" => 20,             // wenn es in den letzten  10 Tage weniger als REGEN10T geregnet hat ODER die Maximaltemperatur den Wert TEMPERATUR-MAX ueberschreitet doppelt so lange giessen 
			"DEBUG" => false,               // bei false weniger Debug Nachrichten generieren 
			"PAUSE" => 5,					// Pause zwischen den Beregnungszyklen, um dem Gardena Umschalter Zeit zur Entspannung zu geben 
			"KREIS1" => "Kreis1:West<br>Beregner bei den suedlichen und westlichen Hausecken",		//  beim Rosentunnel          
			"KREIS2" => "Kreis2:Nord<br>zwei Beregner bei der Nachbarseite und der Spritzer beim Wald",			//  die zwei Beregner bei der Nachbarseite und der Spritzer beim Wald          
			"KREIS3" => "Kreis3:Mitte<br>Eingang und Brunnen",			//  Brunnen und Giesschlauch          
			"KREIS4" => "Kreis4:Sued<br>Beregner bei den Birken",			//  Sued Beregner bei Birken          
			"KREIS5" => "Kreis5:Einfahrt<br>Beregner und Giessschlauch",
			"KREIS6" => "Kreis6:",			// frei, da Auslastungen der anderen Kreise ok         
			"PUMPE" 			=> ?7228,
			//"RAINCOUNTER" 		=> ?0417,
			//"RAINCOUNTER" 		=> "Wetterstation",  // CustomComponent Value
   			"RAINCOUNTER" 		=> "Garten-Wetterstation:Messwerte",  // CustomComponent Value
            "RainCounterHistory"    => [38316,12713],                                   // alte Sensoren auch in die Tabelle übernehmen
			"AUSSENTEMP" 		=> 41941,
			"REMOTEACCESSADR" 	=> "",
			"ENERGYREGID"		=> 12345,    
    
    
    
    */

    public function setGartensteuerungConfiguration($debug=false)
        {
        $config=array();
        if ((function_exists("getGartensteuerungConfiguration"))===false) IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');				
        if (function_exists("getGartensteuerungConfiguration"))	$configInput = getGartensteuerungConfiguration();  

        /* vernünftiges Logdirectory aufsetzen */    
        configfileParser($configInput, $config, ["LogDirectory" ],"LogDirectory" ,"/Gartensteuerung/");  
        $dosOps = new dosOps();
        $systemDir     = $dosOps->getWorkDirectory(); 
        if (strpos($config["LogDirectory"],"C:/Scripts/")===0) $config["LogDirectory"]=substr($config["LogDirectory"],10);      // Workaround für C:/Scripts"
        $config["LogDirectory"] = $dosOps->correctDirName($systemDir.$config["LogDirectory"]);
        configfileParser($configInput, $configConf, ["Configuration","Config","configuration","CONFIG","CONFIGURATION"],"Configuration" ,null);  
        if (($configConf["Configuration"] != null) && (count($configConf["Configuration"])>0))          //sizeof ist zu spezialisisert
            {
            /* Sub Configuration abarbeiten */
            if ($debug) echo "setGartensteuerungConfiguration: moderne Darstellung der Konfiguration mit Unterpunkt Configuration: \n";
            configfileParser($configConf["Configuration"], $config["Configuration"], ["STATISTICS","Statistics","statistics"],"Statistics" ,"ENABLED");  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["IRRIGATION","Irrigation","irrigation"],"Irrigation" ,"ENABLED");  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["MODE","Mode","mode"],"Mode" ,"Switch");          //Default, mit automatischen Regenkreisumschalter  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["WATERPUMP","WaterPump","Waterpump","waterpump"],"WaterPump" ,false);  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["VALVECONTROL","ValveControl","Valvecontrol","valvecontrol"],"ValveControl" ,false);  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["CHECKPOWER","CheckPower","Checkpower","checkpower"],"CheckPower" ,null);  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["RAINCOUNTER","Raincounter","RainCounter","raincounter"],"RainCounter" ,false);  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["RAINCOUNTERHISTORY","Raincounterhistory","RainCounterHistory","raincounterhistory"],"RainCounterHistory",null);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["AUSSENTEMP","Aussentemp","AusenTemp","aussentemp"],"AussenTemp",null);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["REMOTEACCESSADR","RemoteAccessAdr","Remoteaccessadr","remoteaccessadr"],"RemoteAccessAdr",null);

            /* courtesy for old functions */
            configfileParser($configConf["Configuration"], $config["Configuration"], ["DEBUG","Debug","debug"],"DEBUG",false);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["PUMPE","Pumpe","pumpe"],"PUMPE",null);           // nur wenn vorhanden übernehmen, sonst Waterpump

            configfileParser($configConf["Configuration"], $config["Configuration"], ["KREISE","Kreise","kreise"],"KREISE",0);
            for ($i=1;$i<=$config["Configuration"]["KREISE"];$i++)
                {
                configfileParser($configConf["Configuration"], $config["Configuration"], ["KREIS".$i,"Kreis".$i,"kreis".$i],"KREIS".$i,"unknown description");
                if ($debug) echo"   $i:".$config["Configuration"]["KREIS".$i]."\n";
                }

            $config["Configuration"]["RainCounter"]=$this->getConfig_raincounterID($config["Configuration"], $debug);
            $config["Configuration"]["AussenTemp"]=$this->getConfig_aussentempID($config["Configuration"], $debug);
            $config["Configuration"]["RemoteAccessAdr"]=$this->getConfig_RemoteAccess_Address($config["Configuration"], $debug);   
            $config["Configuration"]["WaterPump"]=$this->getConfig_waterPumpID($config["Configuration"], $debug);
            $config["Configuration"]["ValveControl"]=$this->getConfig_valveControlIDs($config["Configuration"], $debug);

            configfileParser($configConf["Configuration"], $config["Configuration"], ["TEMPERATUR-MITTEL","TemperaturMittel","Temperaturmittel"],"TEMPERATUR-MITTEL",19);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["TEMPERATUR-MAX","TemperaturMax","Temperaturmax"],"TEMPERATUR-MAX",28);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["REGEN48H","Regen48h"],"REGEN48H",3);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["REGEN10T","Regen10t"],"REGEN10T",20);

            configfileParser($configConf["Configuration"], $config["Configuration"], ["PAUSE","Pause","pause"],"PAUSE",2);

            configfileParser($configConf["Configuration"], $config["Configuration"], ["KREIS1","Kreis1"],"KREIS1","Kreis1:");       // max 6 Giesskreise, aktuell hard coded
            configfileParser($configConf["Configuration"], $config["Configuration"], ["KREIS2","Kreis2"],"KREIS2","Kreis2:");
            configfileParser($configConf["Configuration"], $config["Configuration"], ["KREIS3","Kreis3"],"KREIS3","Kreis3:");
            configfileParser($configConf["Configuration"], $config["Configuration"], ["KREIS4","Kreis4"],"KREIS4","Kreis4:");
            configfileParser($configConf["Configuration"], $config["Configuration"], ["KREIS5","Kreis5"],"KREIS5","Kreis5:");
            configfileParser($configConf["Configuration"], $config["Configuration"], ["KREIS6","Kreis6"],"KREIS6","Kreis6:");

            }
        else 
            {
            $configInput["RAINCOUNTER"]=$this->getConfig_raincounterID($configInput, $debug);
            $config = $configInput;
            $configInput["RainCounter"] = $configInput["RAINCOUNTER"];
            $configInput["AussenTemp"] = $configInput["AUSSENTEMP"];
            $configInput["RemoteAccessAdr"] = $configInput["REMOTEACCESSADR"];
            $config["Configuration"] = $configInput;
            echo "*************Ändere Konfiguration Gartensteuerung. Muss Sub Kategorie Configuration enthalten.\n";
            }
        if ($debug) 
            {
            echo "setGartensteuerungConfiguration: Ergebnis\n";
            print_r($config);
            }
        //$config["RAINMETER"]  =$this->getConfig_rainMeterID($config);
        return ($config);   
        }

    /* die überarbeitete Konfiguration ausgeben */

    public function getConfig_Gartensteuerung()
        {
        return ($this->GartensteuerungConfiguration);   
        }

    /******************
     * einheitliche Ansteuerung der Wasserpumpe
     * Wenn PUMPE definiert ist, immer die function set_gartenpumpe aufrufen, muss in Gartensteuerung_Configzuration definiert sein
     *
     *********************************/

    public function control_waterPump($state)
        {
        $failure=true;
        $config=$this->getConfig_Gartensteuerung()["Configuration"];
        if (function_exists("set_gartenpumpe"))
            {
            if (isset($config["PUMPE"])==true) $failure=set_gartenpumpe($state,$config["PUMPE"]);
            else $failure=set_gartenpumpe($state);
            }
        elseif (isset($config["Waterpump"])==true)
            {
            /* hier nur mehr Stromheizung Schalter bedienen */
            if (isset($config["Waterpump"]["ID"]))
                {
                switch ($config["Waterpump"]["TYP"])
                    {
                    case "GROUP":
                        IPSHeat_SetGroupByName($config["Waterpump"]["NAME"],$state);
                        break;
                    case "SWITCH":
                    default:
                        IPSHeat_SetSwitchByName($config["Waterpump"]["NAME"],$state);
                        break;
                    }
                }
            }
        return ($failure);
        }

    /******************
     * wenn es Ventile gibt, diese hier ansteuern
     *
     *********************************/

    public function control_waterValves($GiessCount)             // ($state,$oid)
        {
        $failure=true;
        $config=$this->getConfig_Gartensteuerung()["Configuration"];        
        $Count=floor($GiessCount/2);                        // 0 oder 1 ist das erste Ventil
        $oid = $config["ValveControl"]["KREIS".(string)($Count+1)];        
        if ($GiessCount==(($GartensteuerungConfiguration["Configuration"]["KREISE"]*2)+1))
            {
            $message = "Ventil ".IPS_GetName($oid)." ($oid) auf aus";
            if (is_array($oid))
                {
                switch ($oid["TYP"])
                    {
                    case "GROUP":
                        IPSHeat_SetGroupByName($oid["NAME"],$state);
                        break;
                    case "SWITCH":
                    default:
                        IPSHeat_SetSwitchByName($oid["NAME"],$state);
                        break;
                    }
                }
            elseif (function_exists("set_ventile")) set_ventil(false,$oid);
            }
        else
            {
            $message = "Ventil ".IPS_GetName($oid)." ($oid) auf ein";
            if (is_array($oid))
                {
                switch ($oid["TYP"])
                    {
                    case "GROUP":
                        IPSHeat_SetGroupByName($oid["NAME"],$state);
                        break;
                    case "SWITCH":
                    default:
                        IPSHeat_SetSwitchByName($oid["NAME"],$state);
                        break;
                    }
                }
            elseif (function_exists("set_ventile")) set_ventil(true,$oid);                
            $this->log_Giessanlage->LogMessage($message);
            $this->log_Giessanlage->LogNachrichten($message);
            if ($Count>0)
                {
                $poid = $config["Configuration"]["ValveControl"]["KREIS".(string)($Count)];
                $message="Ventil ".IPS_GetName($poid)." ($poid) aus.\n";
                if (is_array($poid))
                    {
                    switch ($oid["TYP"])
                        {
                        case "GROUP":
                            IPSHeat_SetGroupByName($oid["NAME"],$state);
                            break;
                        case "SWITCH":
                        default:
                            IPSHeat_SetSwitchByName($oid["NAME"],$state);
                            break;
                        }
                    }
                elseif (function_exists("set_ventile")) set_ventil(false,$poid);
                }
            }
        $this->log_Giessanlage->LogMessage($message);
        $this->log_Giessanlage->LogNachrichten($message);                
        return ($failure);
        }

	/******************************************************************
	 *
	 * Berechnung Giessbeginn morgens und abends
	 *
	 ***************************************************************************/

    public function fromdusktilldawn($duskordawn=true)      // default ist Abends
        {
        $fatalerror=false;
		/* Beginnzeit Timer für morgen ausrechnen */
		$riseID = @IPS_GetObjectIDByName("Program",0);
		$riseID = @IPS_GetObjectIDByName("IPSLibrary",$riseID);
		$riseID = @IPS_GetObjectIDByName("data",$riseID);
		$riseID = @IPS_GetObjectIDByName("modules",$riseID);
		$riseID = @IPS_GetObjectIDByName("Weather",$riseID);
		$riseID = @IPS_GetObjectIDByName("IPSTwilight",$riseID);
		$riseID = @IPS_GetObjectIDByName("Values",$riseID);
		//$dawnID = @IPS_GetObjectIDByName("SunriseEndLimited",$dawnID);
        if ($duskordawn==true) $dawnID = @IPS_GetObjectIDByName("SunriseEnd",$riseID);
        else $dawnID = @IPS_GetObjectIDByName("SunriseBegin",$riseID);

		if ($dawnID > 0)
			{
			$dawn=GetValue($dawnID);
			$pos=strrpos($dawn,":");
			if ($pos==false) 
                {
                $fatalerror=true;
                }
            else 
                {
				$hour=(integer)substr($dawn,0,$pos);
				$minute=(integer)substr($dawn,$pos+1,10);
				if ($duskordawn==true) $startminuten=$hour*60+$minute-90;
                else $startminuten=$hour*60+$minute;
				$calcminuten=$startminuten-5;
				}
            }    
		if ( ($dawnID === false) || ($fatalerror) )    /* keine Dämmerungszeit verfügbar, default 16:00 */
			{
			$startminuten=16*60;
			$calcminuten=$startminuten-5;
			}
        return($startminuten);
        }	
		
	/******************************************************************
	 *
	 * Berechnung der Giessdauer 0, 10 oder 20 Minuten
	 *
	 ***************************************************************************/

	public function Giessdauer($GartensteuerungConfiguration)
		{

		//global $log_Giessanlage;

		$giessdauerVar=0;
	
		$Server=RemoteAccess_Address();
		if ($this->debug)
			{
			echo"--------Giessdauerberechnung:\n";
			}
		If ($Server=="")
			{
			$variableTempName = IPS_GetName($this->variableTempID);
			$variableName = IPS_GetName($this->variableID);
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$variableTempName = $rpc->IPS_GetName($this->variableTempID);
			$variableName = $rpc->IPS_GetName($this->variableID);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}

		//$AussenTemperaturGesternMax=get_AussenTemperaturGesternMax();
		$AussenTemperaturGesternMax=$this->tempwerte[1]["Max"];
		//$AussenTemperaturGestern=AussenTemperaturGestern();
		$AussenTemperaturGestern=$this->tempwerte[1]["Avg"];
	
		$letzterRegen=0;
		$regenStand=0;
		$regenStand2h=0;
		$regenStand48h=0;
		$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogged wurden */
		$regenStandEnde=0;
		$RegenGestern=0;
		/* Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen */

		foreach ($this->werteLog as $wert)
			{
    		//echo "Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
			$regenStandAnfang=$wert["Value"];
			If (($letzterRegen==0) && ($wert["Value"]>0))
				{
				$letzterRegen=$wert["TimeStamp"];
				$regenStandEnde=$wert["Value"];
				}
			if (((time()-$wert["TimeStamp"])/60/60)<2)
	   			{
				$regenStand2h=$regenStandEnde-$regenStandAnfang;
				}
			if (((time()-$wert["TimeStamp"])/60/60)<48)
				{
				$regenStand48h=$regenStandEnde-$regenStandAnfang;
				}
			$regenStand=$regenStandEnde-$regenStandAnfang;
			}
		if ($this->debug)
			{
			echo "Regenstand 2h : ".$regenStand2h." 48h : ".$regenStand48h." 10 Tage : ".$regenStand." mm.\n";
			}
		$letzterRegen=0;
		$RefWert=0;
		foreach ($this->werte as $wert)
			{
			if ($RefWert == 0) { $RefWert=round($wert["Avg"]); }
			if ( ($letzterRegen==0) && (($RefWert)-round($wert["Avg"])>0) )
				{
		   		$letzterRegen=$wert["MaxTime"]; 		/* MaxTime ist der Wert mit dem groessten Niederschlagswert, also am Ende des Regens, und MinTime daher immer am Anfang des Tages */
				}
			}
		if ( isset($werte[1]["Avg"]) == true ) {	$RegenGestern=$werte[1]["Avg"]; }
		$letzterRegenStd=(time()-$letzterRegen)/60/60;

		if ($this->debug)
			{
			echo "Letzter erfasster Regenwert : ".date("d.m H:i",$letzterRegen)." also vor ".$letzterRegenStd." Stunden.\n";
	 		echo "Aussentemperatur Gestern : ".number_format($AussenTemperaturGestern, 1, ",", "")." Grad (muss > ".$GartensteuerungConfiguration["TEMPERATUR-MITTEL"]."° sein).\n";
 			if ($AussenTemperaturGesternMax>($GartensteuerungConfiguration["TEMPERATUR-MAX"]))
 				{
 				echo "Doppelte Giesszeit da Maximumtemperatur  : ".number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad groesser als ".$GartensteuerungConfiguration["TEMPERATUR-MAX"]." Grad ist.\n";
				}
			if (($letzterRegenStd/60/60)<50)
				{
				echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
					" mm und letzter Regen war aktuell vor ".number_format(($letzterRegenStd), 1, ",", "")." Stunden.\n";
				}
			else
				{
				echo "Regen Gestern : ".number_format($RegenGestern, 1, ",", "").
					" mm und letzter Regen war aktuell vor länger als 48 Stunden.\n";
				}
			echo "Regen letzte 2/48 Stunden : ".$regenStand2h." mm / ".$regenStand48h." mm \n\n";
			if ($regenStand48h<($GartensteuerungConfiguration["REGEN48H"]))
				{
				echo "Regen in den letzten 48 Stunden weniger als ".$GartensteuerungConfiguration["REGEN48H"]."mm.\n";
				}
			if ($regenStand<($GartensteuerungConfiguration["REGEN10T"]))
				{
				echo "Regen in den letzten 10 Tagen weniger als ".$GartensteuerungConfiguration["REGEN10T"]."mm.\n";
				}
			}

		if (($regenStand48h<($GartensteuerungConfiguration["REGEN48H"])) && ($AussenTemperaturGestern>($GartensteuerungConfiguration["TEMPERATUR-MITTEL"])))
			{ /* es hat in den letzten 48h weniger als xx mm geregnet und die mittlere Aussentemperatur war groesser xx Grad*/
			if (($regenStand2h)==0)
				{ /* und es regnet aktuell nicht */
				if ( ($AussenTemperaturGesternMax>($GartensteuerungConfiguration["TEMPERATUR-MAX"])) || ($regenStand<($GartensteuerungConfiguration["REGEN10T"])) )
					{ /* es war richtig warm */
					$giessdauerVar=20;
					}
				else
					{ /* oder nur gleichmässig warm */
					$giessdauerVar=10;
					}
				}
			}
		$textausgabe="Giessdauer:".GetValue($this->GiessTimeID)
			." Min. Regen 2/48/max Std:".number_format($regenStand2h, 1, ",", "")."mm/".number_format($regenStand48h, 1, ",", "")."mm/".number_format($regenStand, 1, ",", "")."mm. Temp mit/max: "
			.number_format($AussenTemperaturGestern, 1, ",", "")."/"
			.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
		$textausgabe2="Giessdauer:".GetValue($this->GiessTimeID)
			." Min. <br>Regen 2/48/max Std:".number_format($regenStand2h, 1, ",", "")."mm/".number_format($regenStand48h, 1, ",", "")."mm/".number_format($regenStand, 1, ",", "")."mm. <br>Temp mit/max: "
			.number_format($AussenTemperaturGestern, 1, ",", "")."/"
			.number_format($AussenTemperaturGesternMax, 1, ",", "")." Grad.";
		SetValue($this->GiessDauerInfoID,$textausgabe2);
		if ($this->debug==false)
			{
			//$log_Giessanlage->message($textausgabe);
			}
		else
			{
			echo $textausgabe;
			}
		return $giessdauerVar;
		}

	/******************************************************************
	 *
	 * Ermittlung und Ausgabe der Regenschauer in den letzten Tagen
     * selbe Funktion wie getRainEvents
	 *
	 * soll auch in construct verwendet werden
	 * beschreibt und überschreibt $this->regenStatistik	
	 *
	 * Übergabeparameter, Anzahl der Kalendertage die evaluiert werden sollen
	 *
	 * es wird der Registerstand des Regensensors des aktuellen Eintrages mit dem Vorwert hinsichtlich Menge und Zeitabstand verglichen
	 * damit wird Regendauer und Regenmenge ermittelt
	 * Regenmenge und Regendauer bestimmt den maximalen Niederschlag pro Minute, hochgerechnet auf 1 Stunde
	 * eine Regenmenge <0.4 und Regendauer >60 Minuten wird ignoriert - Messfehler oder Nieseln
	 * beim Ersten erkannten Regen (wir bearbeiten zurück in die Verangenheit) wird das Regenende festgelegt
	 *
	 */

	public function listRainEvents($events=10,$variableID=false,$debug=false)
		{
        if ($variableID==false) $variableID = $this->variableID;                // nur für die lokalen Variablen
        if ($this->debug) $debug=$this->debug;
        $days=100;                                          // Anzahl der Events in den letzten 100 Tagen suchen
		$endtime=time();
		$starttime2=$endtime-60*60*24*$days;   /* die letzten x (default 10) Tage Niederschlag*/

		//$Server=$this->getConfig_RemoteAccess_Address();
        $Server                 = $this->GartensteuerungConfiguration["Configuration"]["RemoteAccessAdr"];
		if ($debug)
			{
			echo "\n";
			echo"--------Function List RainEvents:\n";
			}
		If ($Server=="")
			{
			$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$this->archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}
        $regenStatistik = $this->getRainevents($werteLog,$events);
		return ($regenStatistik);
		}

	public function listRainDays($days=10,$variableID=false,$debug=false)
		{
        if ($variableID==false) $variableID = $this->variableID;                // nur für die lokalen Variablen
        if ($this->debug) $debug=$this->debug;
		$endtime=time();
		$starttime2=$endtime-60*60*24*$days;   /* die letzten x (default 10) Tage Niederschlag*/

		//$Server=$this->getConfig_RemoteAccess_Address();
        $Server                 = $this->GartensteuerungConfiguration["Configuration"]["RemoteAccessAdr"];
		if ($debug)
			{
			echo "\n";
			echo"--------Function List RainEvents:\n";
			}
		If ($Server=="")
			{
			$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$this->archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
			if ($this->debug)
				{
				echo "   Daten vom Server : ".$Server."\n";
				}
			}
        $regenStatistik = $this->getRainevents($werteLog);
		return ($regenStatistik);
		}

    /* Routinen nur mehr an einem Ort. liefert Regenstatistik und ändert einen class parameter: letzterRegen
     * benötigt ein inkrementelles Regenregister, Counter mit steigenden Werten
     *
     * Rain2h oder 48h extra berechnen.
     *
     */

    private function getRainevents($werteLog,$events=0)
        {
		/* Letzen Regen ermitteln, alle Einträge der letzten x (default 10) Tage Niederschlag durchgehen */
        $this->letzterRegen=0;
		$regenStand=0;			/* der erste Regenwerte, also aktueller Stand */
		$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogget wurden */
		$regenAnfangZeit=0;
		$regenStandEnde=0;
		$regenEndeZeit=0;
		$regenMenge=0; $regenMengeAcc=0;
		$regenDauer=0; $regenDauerAcc=0;
		$vorwert=0; $vorzeit=0;
		$regenStatistik=array();
		$regenMaxStd=0;
        $eventCount=1;
		foreach ($werteLog as $wert)	/* Regenwerte Eintrag für Eintrag durchgehen */
			{
			if ($vorwert==0) 
				{ 
				if ($this->debug) {	echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				$regenStand=$wert["Value"];
				}
			else 
				{
				/* die erste Zeile erst mit dem zweiten Eintrag auffuellen ... */
				$regenMenge=round(($vorwert-$wert["Value"]),1);
				$regenDauer=round(($vorzeit-$wert["TimeStamp"])/60,0);
				if ($this->debug) 
					{
					if ( $regenDauer>(60*24*2) ) echo " ".$regenMenge."mm/>2Tage  "; 
					else echo str_pad(" ".$regenMenge."mm/".$regenDauer."min  ",20); 
					}
				if (($regenMenge/$regenDauer*60)>$regenMaxStd) {$regenMaxStd=$regenMenge/$regenDauer*60;} // maximalen Niederschlag pro Stunde ermitteln
				if ( ($regenMenge<0.4) and ($regenDauer>60) ) 
					{
					/* gilt nicht als Regen, ist uns zu wenig, mehr ein nieseln */
					if ($this->debug) echo "  kurzes Nieseln ";
					if ($regenEndeZeit != 0)
						{ 
						/* gilt auch als Regenanfang wenn ein Regenende erkannt wurde*/
						$regenAnfangZeit=$vorzeit;
						if ($this->debug) 
							{ 
							echo str_pad($regenMengeAcc."mm ".$regenDauerAcc."min ",20);						
							echo "  Regenanfang : ".date("d.m H:i",$regenAnfangZeit)."   ".round($vorwert,1)."  ".round($regenMaxStd,1)."mm/Std ";	
							}
						$regenStatistik[$regenAnfangZeit]["Beginn"]=$regenAnfangZeit;
						$regenStatistik[$regenAnfangZeit]["Ende"]  =$regenEndeZeit;
						$regenStatistik[$regenAnfangZeit]["Regen"] =$regenMengeAcc;
						$regenStatistik[$regenAnfangZeit]["Max"]   =$regenMaxStd;					
						$regenEndeZeit=0; $regenStandEnde=0; $regenMaxStd=0;
                        if ($eventCount==$events) break;
                        $eventCount++;
						}
					else
						{
						if ($this->debug) { echo "* "; }
						}	
					$regenMenge=0; $regenDauerAcc=0; $regenMengeAcc=0;
					} 
				else
					{
					if ($regenDauer<60)
						{  
						/* es regnet */
						$regenMengeAcc+=$regenMenge;
						$regenDauerAcc+=$regenDauer;				
						if ($this->debug) { echo str_pad($regenMengeAcc."mm ".$regenDauerAcc."min ",20); }
						if ($regenEndeZeit==0)
							{
							$regenStandEnde=$vorwert;
							$regenEndeZeit=$vorzeit;
							if ($this->debug) { echo "  Regenende : ".date("d.m H:i",$regenEndeZeit)."   ".round($regenStandEnde,1)."  ";	}
							}
						}
					else
						{
						/* es hat gerade begonnen zu regnen, gleich richtig mit Wert > 0.3mm */
						if ($regenEndeZeit != 0)
							{ 
							/* gilt auch als Regenanfang wenn ein Regenende erkannt wurde*/
							$regenMengeAcc+=$regenMenge;							
							$regenAnfangZeit=$vorzeit;
							if ($this->debug) 
								{ 
								echo str_pad($regenMengeAcc."mm ".$regenDauerAcc."min ",20);						
								echo "  Regenanfang : ".date("d.m H:i",$regenAnfangZeit)."   ".round($vorwert,1)."  ".round($regenMaxStd,1)."mm/Std ";	
								}
							$regenStatistik[$regenAnfangZeit]["Beginn"]=$regenAnfangZeit;
							$regenStatistik[$regenAnfangZeit]["Ende"]  =$regenEndeZeit;
							$regenStatistik[$regenAnfangZeit]["Regen"] =$regenMengeAcc;
							$regenStatistik[$regenAnfangZeit]["Max"]   =$regenMaxStd;					
							$regenEndeZeit=0; $regenStandEnde=0; $regenMaxStd=0;
                            if ($eventCount==$events) break;
                            $eventCount++;
							}
						else
							{
							if ($this->debug) { echo "* "; }
							}	
						} 								
					}
                If ( ($this->letzterRegen==0) && (round($wert["Value"]) > 0) )
                    {
                    $this->letzterRegen=$wert["TimeStamp"];
                    $regenStandEnde=$wert["Value"];
                    if ($this->debug) { echo "Letzter Regen !"; }
                    }				

				if ($this->debug) { echo "\n   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				}
			$vorwert=$wert["Value"];	
			$vorzeit=$wert["TimeStamp"];
			}
		return ($regenStatistik);            
        }

	/*******************
     *
     * Ausgabe der Regenereignisse (ermittelt mit listrainevents) mit Beginn, Ende, Dauer etc als html Tabelle zum speichern in einer hml Box
	 */

	public function writeRainEventsHtml($rainevents)
		{
        $html="";
    	$html.='<table frameborder="1" width="100%">';
	    $html.="<th> <tr> <td> Regenbeginn </td> <td> Regenende </td> <td> Dauer Min</td> <td> Menge mm </td> <td> max mm/Stunde </td> </tr> </th>";
    	//echo "\nRegenstatistik, letzte 100 Eintraege :\n";
	    foreach ($rainevents as $regeneintrag)
		    {
    		//echo "  Regenbeginn ".date("d.m H:i",$regeneintrag["Beginn"])."  Regenende ".date("d.m H:i",$regeneintrag["Ende"])." mit insgesamt ".number_format($regeneintrag["Regen"], 1, ",", "")." mm Regen. Max pro Stunde ca. ".number_format($regeneintrag["Max"], 1, ",", "")."mm/Std.\n";
		    $html.="<tr> <td>".date("D d.m H:i",$regeneintrag["Beginn"])."</td> <td>".date("d.m H:i",$regeneintrag["Ende"])."</td> <td>"
		        .number_format(($regeneintrag["Ende"]-$regeneintrag["Beginn"])/60, 0, ",", "")."</td> <td>".number_format($regeneintrag["Regen"], 1, ",", "")
    			."</td> <td>".number_format($regeneintrag["Max"], 1, ",", "")."</td> </tr>";
	    	}
    	$html.="</table>";
	    //echo "Und als html Tabelle :\n"; echo $html;	
        return($html);
        }

	/*
	 *   Ausgabe der Events/Nachrichten über die Giessanlage, wann wie lange eingeschaltet
	 *
	 */

	public function listEvents()
		{
		return ($this->log_Giessanlage->PrintNachrichten());
		}

    /* noch eine Routine um die Regenereignisse herauszuarbeiten
     * Die Variable die ein _Counter am Ende hat ist das Input Register mit einem Zählerstand, Counter2 sollte der Zählerstand sein der um einen Stromausfall bereinigt ist und den Zählerstand richtig weiterschreibt.
     * da das alles viel zu kompliziert ist den incrementellen registerwert nehmen. Im einfachen Registernamen gespeichert:
     *
     * 12713 >>> alte Werte von 2016 bis Jänner 2020
     * 38316  >>> neuer Wert ab August 2020
     *
     * Ergebnis sind regenereignisse
     * den Werte Log durchgehen, wenn Value>0 ist zur Gesamtmenge regen dazuzaehlen. Bei Value=0 nichts machen
     * beim ersten Mal ein regenereignis Ende mit diesem timestamp anlegen, dabei sich von der Jetztzeit zurückarbeiten
     * wenn die Zeit zwischen zwei Regenmesswerten > 60 Minuten betraegt, den Beginn eintragen und ein neues regenereignis beginnen
     *
     ***/

    public function getRainEventsFromIncrements(&$regenereignis, $rainRegs=array(),$debug=false)
        {
        if ($debug) echo "getRainEvents mit ID ".$this->RainRegisterIncrementID." aufgerufen.\n";
        $debug=false;
        if ($this->RainRegisterIncrementID>0) $rainRegs[0]=$this->RainRegisterIncrementID;
        $init=true;             // beim ersten Mal
        $i=0;                   // Zaehler Regenereignisse
        $lasttime=time();       // von der Jetztzeit zurückarbeiten
        $regenMenge=false;
        foreach ($rainRegs as $rainReg)
            {
            /* Es bibt ein inkrementales regenregister in der CustomComponents Auswertung. Da kann ich dann schön die Regendauer pro Monat auswerten
             * es werden auch die Regenereignisse gefunden.
             */
    		$endtime=time();
	    	$starttime2=$endtime-60*60*24*3650;   /* die letzten 10 Jahre Niederschlag*/
  		    //$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
    		$werteLog = AC_GetLoggedValues($this->archiveHandlerID, $rainReg, $starttime2, $endtime,0);
            /* Auswertung von Agreggierten Werten funktioniert leider bei inkrementellen Countern nicht */

  /*    $zeit=date("d.m.y",time()); 
        $init=true;
        $regenWerte=array();
		foreach ($werteLog as $wert)
			{
            if ($init) {$init=false; $lastwert=$wert["Value"];}     // lastwert ist der Regenzählerstand
            $neuezeit=date("d.m.y",$wert["TimeStamp"]);             // der passende Tag zum Zählerstand
            if ($zeit != $neuezeit)                                 / wir fahren von heute zurück in die Vergangenheit, wenn sich der Tag ändert etwas tun
                 {
                 if ($wert["Value"]>$lastwert) 
                     {
                     echo "*********".$wert["Value"]."*********";
                     foreach ($regenWerte as $timestamp => $entry) 
                          {
                          $regenWerte[$timestamp]+=$wert["Value"];
                          //echo $date."   ".$regenWerte[$date]."\n";
                          } 
                      }
                 echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
                 $zeit=$neuezeit; $lastwert=$wert["Value"];
                 $regenWerte[$wert["TimeStamp"]]=$wert["Value"];
                 }
            } */

            $regen=0;               // Regenmenge während einem Regenereignis
            $lastwert=0;   $zeit=0;
            $ende=true; $maxmenge=0;
			foreach ($werteLog as $wert)
				{
                if ($wert["Value"]!=0)
                    {
                    if ($wert["Value"]<0) 
                        {
                        echo "\n>>>>>>>Fehler negativer Wert: ".$wert["Value"]."\n";
                        }
                    else
                        {
                        $regen+=$wert["Value"];
                        if ($init===true) 
                            {
                            if ($debug) echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.Y H:i",$wert["TimeStamp"])."    ";
                            $regenereignis[$i]["Ende"]=$wert["TimeStamp"];
                            $regenMenge=0;          // Unterschied false und 0
                            $init=false;    
                            }
                        else 
                            {
                            $zeit= ($lasttime-$wert["TimeStamp"])/60;     // vergangene Zeit zwischen zwei Regenwerten in Minuten messen                    
                            $menge=60/$zeit*$wert["Value"];
                            $regenMenge+=$wert["Value"];
                            if ($wert["Value"]<0) echo "Fehler negativer Wert\n";
                            if ($menge>$maxmenge) $maxmenge=$menge;
                            if ($debug) 
                                {
                                echo number_format($regenMenge,1,",","")."mm | ".number_format($zeit,1,",","")."Min ".number_format($menge,1,",","")."l/Std   ".$regen."\n";
                                echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.Y H:i",$wert["TimeStamp"])."    ";                                            // für die naechste Zeile
                                }
                            }       
                        if ($zeit>60) 
                            {
                            $regenereignis[$i]["Regen"]=$regen;
                            $regenereignis[$i]["Beginn"]=$lasttime;
                            $regenereignis[$i]["Max"]=$maxmenge;
                            $regen=0; $i++;
                            $regenereignis[$i]["Ende"]=$wert["TimeStamp"]; $maxmenge=0; 
                            }
                        $lastwert=$wert["Value"]; 
                        $lasttime=$wert["TimeStamp"]; 
                        }
                    }
				}
            $regenereignis[$i]["Regen"]=$regen;
            $regenereignis[$i]["Beginn"]=$lasttime;   
            $regenereignis[$i]["Max"]=$maxmenge;
            if ($debug) echo "\n";
    		}
        return ($regenMenge);
        }

    /*
     *  einen Regenwert pro Tag als Array zurückgeben
     *
     */

    public function getRainValues($debug=false)
        {
    	$RegisterCounterID=$this->variableID;
    	$endtime=time();
		$starttime2=$endtime-60*60*24*3650;   /* die letzten 3650 Tage Niederschlag */
        unset($werteLog);
		$werteLog = AC_GetLoggedValues($this->archiveHandlerID, $RegisterCounterID, $starttime2, $endtime,0);
        if ($debug) echo "getRainValues, die insgesamt ".sizeof($werteLog)." Counter Werte anschauen und für eine Auswertung nach Kalendermonaten (12 Monate) und eine 1/7/30/30/360/360 Auswertung vorbereiten:\n";
        /* Regenwert pro Tag ermitteln. Gleiche Werte am selben Tag igorienen, spätester Wert pro Tag bleibt. */ 
        $zeit=date("d.m.y",time()); $init=true;
        $regenWerte=array();
		foreach ($werteLog as $wert)
			{
            if ($init) {$init=false; $lastwert=$wert["Value"];}
            $neuezeit=date("d.m.y",$wert["TimeStamp"]);
            if ($zeit != $neuezeit)
                {
                if ($wert["Value"]>$lastwert)          // da ist was verkehrt
                     {
                     if ($debug) echo "*********".$wert["Value"]."*********";
                     foreach ($regenWerte as $timestamp => $entry) 
                          {
                          $regenWerte[$timestamp]+=$wert["Value"];
                          //echo $date."   ".$regenWerte[$date]."\n";
                          } 
                      }
                //if ($debug) echo "***Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
                $zeit=$neuezeit; $lastwert=$wert["Value"];
                $regenWerte[$wert["TimeStamp"]]=$wert["Value"];                 
                }
            else
                {
                //if ($debug) echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
                }
            }

        return ($regenWerte);
        }

    /* wie getRainValues nur von den Increments, also nicht Zählerständen
     *
     *
     */

    public function getRainValuesFromIncrements($regenMenge,$rainRegs=array(),$debug=false)
        {
        if ($this->RainRegisterIncrementID>0) 
            {
            $rainRegs[0]=$this->RainRegisterIncrementID;            
    	    //$rainRegs[0]=$this->variableID;                           // wenn counter
            }
    	$endtime=time();
		$starttime2=$endtime-60*60*24*3650;   /* die letzten 3650 Tage Niederschlag */
        $init=true;
        $regenWerte=array();
        $zeit=date("d.m.y",time()); 
        foreach ($rainRegs as $rainReg)
            {
            unset($werteLog);
            $werteLog = AC_GetLoggedValues($this->archiveHandlerID, $rainReg, $starttime2, $endtime,0);
            //echo "Die insgesamt ".sizeof($werteLog)." Counter Werte anschauen und auswerten nach Kalendermonaten (12 Monate) und eine 1/7/30/30/360/360 Auswertung:\n";
            /* Regenwert pro Tag ermitteln. Gleiche Werte am selben Tag igorienen, spätester Wert pro Tag bleibt. */ 
            foreach ($werteLog as $wert)
                {
                if ($wert["Value"]>=0)
                    {
                    if ($init) 
                        {
                        $init=false; 
                        $neuewert=$regenMenge; $lastwert=$neuewert;
                        //$lastwert=$wert["Value"];                     // wenn counter
                        }
                    $neuewert -= $wert["Value"];
                    //$neuewert=$wert["Value"]                              // wenn counter
                    $neuezeit=date("d.m.y",$wert["TimeStamp"]);
                    if ($zeit != $neuezeit)
                        {
                        if ($neuewert>$lastwert)          // da ist was verkehrt
                            {
                            if ($debug) echo "*********".$wert["Value"]."*********";
                            foreach ($regenWerte as $timestamp => $entry) 
                                {
                                $regenWerte[$timestamp]+=$neuewert;
                                //echo $date."   ".$regenWerte[$date]."\n";
                                } 
                            }
                        if ($debug) echo "***Wert : ".number_format($neuewert, 1, ",", "")."/".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
                        //if ($debug) echo "***Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";              // wenn counter
                        $zeit=$neuezeit; 
                        $lastwert=$neuewert;
                        $regenWerte[$wert["TimeStamp"]]=$neuewert;                 
                        }
                    else                // selber Tag
                        {
                        if ($debug) echo "   Wert : ".number_format($neuewert, 1, ",", "")."/".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
                        //if ($debug) echo "   Wert : ".number_format($neuewert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";          // wenn counter
                        }
                    }
                else
                    {
                    echo "Fehler negativer Wert ".$wert["Value"]."\n";    
                    }
                }
            }
        return ($regenWerte);
        }


	/*
	 *  Allerlei Auswertungen mit der Regenmenge. 
	 *  Ergebnisse der statistischen Auswertungen werden in der Klasse gespeichert und sind dann auch verfügbar
     *  Arrays:  $this->RegenKalendermonate, $this->DauerKalendermonate
     * 
     *  Beispiel Ausgabe: Auswertung 1: 0,0mm  7: 4,4mm 30: 17,7mm 30: 37,8mm 360: 442,2mm 360: 0,0mm
     *
	 */

	public function getRainStatistics($input=array(),$debug=false)
		{
        $regenereignis=array();            
        $regenMenge = $this->getRainEventsFromIncrements($regenereignis,$input,$debug);         // regenereignis ist ein pointer, input die Liste der Variablen die in die Statistik eingehen sollen
        echo "getRainStatistics, Regenmenge im gesamten Zeitraum ist $regenMenge mm. Variablen ausgewertet: ".json_encode($input)."\n";
        if (false)
            {
            if ($this->RainRegisterIncrementID>0)
                {
                /* Es bibt ein inkrementales regenregister in der CustomComponents Auswertung. Da kann ich dann schön die Regendauer pro Monat auswerten
                * es werden auch die Regenereignisse gefunden.
                */
                $endtime=time();
                $starttime2=$endtime-60*60*24*3650;   /* die letzten 10 Jahre Niederschlag*/
                //$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                $werteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->RainRegisterIncrementID, $starttime2, $endtime,0);
                /* Auswertung von Agreggierten Werten funktioniert leider bei inkrementellen Countern nicht */

                $lasttime=time(); $lastwert=0; $init=true; $regen=0; $zeit=0;
                $i=0; $ende=true; $maxmenge=0;
                foreach ($werteLog as $wert)
                    {
                    if ($wert["Value"]!=0)
                    {
                    $regen+=$wert["Value"];
                    if ($init) 
                            {
                            //echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m H:i",$wert["TimeStamp"])."    ";
                            $regenereignis[$i]["Ende"]=$wert["TimeStamp"];
                            $init=false;    
                            }
                    else 
                            {
                            $zeit= ($lasttime-$wert["TimeStamp"])/60;                         
                            $menge=60/$zeit*$wert["Value"];
                            if ($menge>$maxmenge) $maxmenge=$menge;
                            //echo number_format($zeit,1,",","")."Min ".number_format($menge,1,",","")."l/Std   ".$regen."\n   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m H:i",$wert["TimeStamp"])."    "; 
                            }       
                        if ($zeit>60) 
                            {
                            $regenereignis[$i]["Regen"]=$regen;
                            $regenereignis[$i]["Beginn"]=$lasttime;
                            $regenereignis[$i]["Max"]=$maxmenge;
                            $regen=0; $i++;
                            $regenereignis[$i]["Ende"]=$wert["TimeStamp"]; $maxmenge=0; 
                            }
                        $lastwert=$wert["Value"]; $lasttime=$wert["TimeStamp"]; 
                        }
                    }
                $regenereignis[$i]["Regen"]=$regen;
                $regenereignis[$i]["Beginn"]=$lasttime;   
                $regenereignis[$i]["Max"]=$maxmenge;
                echo "\n";
                }
            }            
        $kalendermonate=array();
        if (sizeof($regenereignis)==0) 
            {
            echo "Fehler, alternative Berechnung.\n";
            $regenereignis=$this->listRainEvents(1000);          // wir haben hier noch eine Alternativberechungsmethode
            }
        foreach ($regenereignis as $eintrag) 
            {
            //print_r($eintrag);
            $dauer = ($eintrag["Ende"]-$eintrag["Beginn"])/60/60;
            $monat=date("m.y",$eintrag["Ende"]);
            if (isset($kalendermonate[$monat])) $kalendermonate[$monat]+= $dauer;
            else $kalendermonate[$monat] = $dauer;
                
            if ($dauer>0)
                {
                /* einmalige Ereignise, 03 mm in 2 Stunden ingnorieren */
                if ($debug && false) echo "Anfang ".date("D d.m.y H:i",$eintrag["Beginn"])." bis Ende ".date("D d.m H:i",$eintrag["Ende"])." mit insgesamt "
                        .number_format($eintrag["Regen"], 1, ",", "")." mm Dauer ".number_format($dauer, 1, ",", "")." Stunden  Max :".number_format($eintrag["Max"], 1, ",", "").
                        " l/Std\n";
                }
            }  
        $this->DauerKalendermonate=$kalendermonate;

        if (false)
            {
            $RegisterCounterID=$this->variableID;
            $endtime=time();
            $starttime2=$endtime-60*60*24*3650;   /* die letzten 3650 Tage Niederschlag */
            $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            unset($werteLog);
            $werteLog = AC_GetLoggedValues($archiveHandlerID, $RegisterCounterID, $starttime2, $endtime,0);
            //echo "Die insgesamt ".sizeof($werteLog)." Counter Werte anschauen und auswerten nach Kalendermonaten (12 Monate) und eine 1/7/30/30/360/360 Auswertung:\n";
            /* Regenwert pro Tag ermitteln. Gleiche Werte am selben Tag igorienen, spätester Wert pro Tag bleibt. */ 
            $zeit=date("d.m.y",time()); $init=true;
            $regenWerte=array();
            foreach ($werteLog as $wert)
                {
                if ($init) {$init=false; $lastwert=$wert["Value"];}
                $neuezeit=date("d.m.y",$wert["TimeStamp"]);
                if ($zeit != $neuezeit)
                    {
                    if ($wert["Value"]>$lastwert) 
                        {
                        //echo "*********".$wert["Value"]."*********";
                        foreach ($regenWerte as $timestamp => $entry) 
                            {
                            $regenWerte[$timestamp]+=$wert["Value"];
                            //echo $date."   ".$regenWerte[$date]."\n";
                            } 
                        }
                    //echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
                    $zeit=$neuezeit; $lastwert=$wert["Value"];
                    $regenWerte[$wert["TimeStamp"]]=$wert["Value"];
                    }
                }
            }
        $regenWerte = $this->getRainValuesFromIncrements($regenMenge,$input,$debug);                           // from register_Counter, das sind die Zählerstaende, die Regenwerte pro Tag ermiteln, letzer Zählerstand pro Tag
        echo "Regenwerte in getRainStatiscs ermittelt, insgesamt ".count($regenWerte)." Werte, hier ausgeben:\n";
        //print_R($regenWerte);
        foreach ($regenWerte as $time=>$regenWert) echo "   ".date("d.m.Y H:i:s",$time)."  $regenWert mm\n";

        $init=true; $tageswert=false; $wochenwert=false; $monatswert=false; $monat2wert=false; $jahreswert=false; $jahr2wert=false;
        $tag=0; $woche=0; $monat=0; $monat2=0; $jahr=0; $jahr2=0;
        $kalendermonate=array();
        foreach ($regenWerte as $zeit => $regen)                    // regenwerte sind absolut, ein Wert pro Tag und immer der späteste
             {
             if ($init)         // beim ersten Wert, also dem jüngsten, Absprungpunkt ist jetzt und der letzte Regenstand
                  {
                  $lasttime=$zeit; $lastwert=$regen; $init=false; 
                  $inittime=time(); $initwert=$regen;
                  $startmonat=$regen; $aktmonat=date("m.y",$zeit);
                  }
             else
                  {        
                  //echo "  ".date("D d.m.y H:i",$lasttime)."   ".number_format(($lastwert-$regen), 1, ",", "");
                  $dauer=(($inittime-$zeit)/60/60/24);                                      // Dauer in Tagen zwischen zwei Tagesendstaenden
                  //echo "    Wert für ".number_format((($inittime-$zeit)/60/60/24), 1, ",", "")." Tage ".($initwert-$lastwert);
                  //echo "\n";
                  if ( ($dauer>1) && !($tageswert) ) {$tageswert=true; $tag=$initwert-$lastwert; }
                  if ( ($dauer>7) && !($wochenwert) ) {$wochenwert=true; $woche=$initwert-$lastwert; }
                  if ( ($dauer>30) && !($monatswert) ) {$monatswert=true; $monat=$initwert-$lastwert; $init2wert=$lastwert; }
                  if ( ($dauer>60) && !($monat2wert) ) {$monat2wert=true; $monat2=$init2wert-$lastwert;  }
                  if ( ($dauer>360) && !($jahreswert) ) {$jahreswert=true; $jahr=$initwert-$lastwert; $init2wert=$lastwert; }
                  if ( ($dauer>720) && !($jahr2wert) ) {$jahr2wert=true; $jahr2=$init2wert-$lastwert;  }
                  if ($aktmonat != (date("m.y",$zeit))) {$kalendermonate[$aktmonat]=$startmonat-$regen; $startmonat=$regen; $aktmonat=date("m.y",$zeit);    }
                  $lasttime=$zeit; $lastwert=$regen;
                  }
            } 
        if ($jahr==0) { $jahr=$initwert-$lastwert; $init2wert=$lastwert;}   
        if ($jahr2==0) { $jahr2=$init2wert-$lastwert; }    
        $ergebnis="Auswertung 1: ".number_format($tag, 1, ",", "")."mm  7: ".number_format($woche, 1, ",", "")."mm 30: ".number_format($monat, 1, ",", "")."mm 30: "
                        .number_format($monat2, 1, ",", "")."mm 360: ".number_format($jahr, 1, ",", "")."mm 360: ".number_format($jahr2, 1, ",", "")."mm\n";      
        $this->RegenKalendermonate=$kalendermonate;
	    return ($ergebnis);
        }

	/*******************
     *
     * Ausgabe der Regenereignisse (ermittelt mit listrainevents) mit Beginn, Ende, Dauer etc als html Tabelle zum speichern in einer hml Box
	 */

	public function writeKalendermonateHtml()
		{
        $html="";
    	$html.='<table frameborder="1" width="100%">';
	    $html.="<th> <tr> <td> Regenbeginn </td> <td> Regenende </td> <td> Dauer Min</td> <td> Menge mm </td> <td> max mm/Stunde </td> </tr> </th>";
	    foreach ($kalendermonate as $regeneintrag)
		    {
    		//echo "  Regenbeginn ".date("d.m H:i",$regeneintrag["Beginn"])."  Regenende ".date("d.m H:i",$regeneintrag["Ende"])." mit insgesamt ".number_format($regeneintrag["Regen"], 1, ",", "")." mm Regen. Max pro Stunde ca. ".number_format($regeneintrag["Max"], 1, ",", "")."mm/Std.\n";
		    $html.="<tr> <td>".date("D d.m H:i",$regeneintrag["Beginn"])."</td> <td>".date("d.m H:i",$regeneintrag["Ende"])."</td> <td>"
		        .number_format(($regeneintrag["Ende"]-$regeneintrag["Beginn"])/60, 0, ",", "")."</td> <td>".number_format($regeneintrag["Regen"], 1, ",", "")
    			."</td> <td>".number_format($regeneintrag["Max"], 1, ",", "")."</td> </tr>";
	    	}
    	$html.="</table>";
	    //echo "Und als html Tabelle :\n"; echo $html;	
        return($html);
        }

	/*******************
     *
     * Ausgabe der Regenmenge/dauer (ermittelt mit listrainevents) als html Tabelle zum speichern in einer hml Box
	 */

	public function writeOverviewMonthsHtml($monthlyValues=array())
		{
        $startjahr=(integer)date("y",time());
        $minjahr=$startjahr;
        //print_r($monthlyValues);
        foreach ($monthlyValues as $date=>$regenmenge)
            {
            $MonatJahr=explode(".",$date);
            if ((integer)$MonatJahr[1]<$minjahr) $minjahr=(integer)$MonatJahr[1];
            $tabelle[(integer)$MonatJahr[0]][(integer)$MonatJahr[1]]=$regenmenge;
            }
        $html="";
        $html.='<table frameborder="1" width="100%">';
    	$html.="<th> <tr> <td> Monat </td> ";
        for ($i=$startjahr;$i>=$minjahr;$i--) {$html.="<td> ".$i." </td>";}
        $html.= " </tr> </th>";
	    for ($j=1;$j<=12;$j++)
            {
            $html.="<tr> <td>".$j."</td>";
            for ($i=$startjahr;$i>=$minjahr;$i--) 
                {
                if (isset($tabelle[$j][$i])==true) $html.="<td> ".number_format($tabelle[$j][$i],1,",","")." </td>";
                else $html.="<td> </td>";
                }
            $html.= " </tr> ";
    	    }
   	    $html.="</table>";
        return($html);
        }   

	}  /* Ende class Gartensteuerung */



	
?>