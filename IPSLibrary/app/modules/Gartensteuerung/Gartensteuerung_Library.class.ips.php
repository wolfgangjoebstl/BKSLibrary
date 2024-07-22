<?php

/*
	 * @defgroup Gartensteuerung_Library
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 *
	 *
	 * @file          Gartensteuerung_library.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/


/************************************************************
 *
 * Gartensteuerung Library
 *
 * vorhandene classes
 *      Gartensteuerung
 *      GartensteuerungControl extends Gartensteuerung
 *      GartensteuerungStatistics
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
     *      __construct
     *      getCategoryRegisterID                       von CustomComponents die Counter Category finden
     *
     *      getConfig_aussentempID                      tempId aus dem Ergebnis der Auswertung von setGartensteuerungConfiguration
     *      getConfig_raincounterID                     siehe oben
     *      getRainRegisters
     *      getConfig_xID
     *      getConfig_waterPumpID
     *      getConfig_valveControlIDs
     *      getConfig_RemoteAccess_Address
     *
     *      setGartensteuerungConfiguration
     *      getConfig_Gartensteuerung
     *
     *      control_xID
     *      control_waterPump
     *      getKreisfromCount
     *      control_waterValves
     *
     *      fromdusktilldawn
     *      Giessdauer
     *
     *      listEvents
     *      getRainValues
     *      writeKalendermonateHtml         Ausgabe der Regenereignisse (ermittelt mit listrainevents) mit Beginn, Ende, Dauer etc als html Tabelle zum speichern in einer hml Box
     *      writeOverviewMonthsHtml
     *
     *  andere functions wurden in statistik übernommen
     *
     * Aufrufparameter: starttime, starttime2 
     * Default ist Auswertung der letzten 2 und 10 Tage, kann beim Aufruf angepasst werden. 
     * Eingabe für Defaultwerte ist 0
     * 
	 ************************************************/

class Gartensteuerung
	{
	var         $installedmodules;                                  // welche Module sind installiert und können verwendet werden
	
	protected 	$archiveHandlerID;
	protected	$debug;

	protected	$tempwerte, $tempwerteLog, $werteLog, $werte;       // temperatur und regen

	protected	$variableTempID, $variableID;                       // Aussentemperatur und Regensensor
	protected 	$GartensteuerungConfiguration;
	
	public 		$regenStatistik;
	public 		$letzterRegen, $regenStand2h, $regenStand48h;
	
	public      $categoryId_Auswertung, $categoryId_Register,$categoryId_Statistiken;
    public 		$GiessTimeID,$GiessDauerInfoID;
    public      $StatistikBox1ID, $StatistikBox2ID, $StatistikBox3ID;                   // html boxen für statistische Auswertungen
	
	public 		$log_Giessanlage;									// logging Library class

    protected   $RainRegisterIncrementID,$RainRegisterCounterID;                           // in CustomComponent gibt es einen Incremental Wert und einen Counter, Incremental ist nur die Änderung, Counter die Gesamtzahl seit Beginn
    protected   $categoryRegisterID;                                // die Category für die Regenregister, Counter of CustomComponents
    public      $DauerKalendermonate, $RegenKalendermonate;         // Ausertung Regendauer (wenn inkrementell da) und Regenmenge der letzten 10 Jahre

	protected   $heatManager;                                               // Einbindung des Stromheizung Modul, mit den Schaltgruppen
	protected   $CategoryId_Stromheizung;
	protected   $switchCategoryHeatId, $groupCategoryHeatId , $prgCategoryHeatId;
    protected   $CategoryIdData,$CategoryIdApp;


	public function __construct($starttime=0,$starttime2=0,$debug=false)
		{
        $dosOps = new dosOps();
        $ipsOps = new ipsOps();

		$this->debug=$debug;
        if ($this->debug) echo "Gartensteuerung: construct aufgerufen. Debug Mode.\n";
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager)) 
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
			}
		$this->installedModules 				= $moduleManager->GetInstalledModules();

		if ( isset($this->installedModules["Stromheizung"] ) )
			{
            if ($this->debug) echo "    Modul Stromheizung installiert, Schalten Gartenpumpe über dieses Modul möglich.\n";
			include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");						
			$this->CategoryId_Stromheizung			= @IPS_GetObjectIDByName("Stromheizung",$this->CategoryId_Ansteuerung);
			$this->heatManager = new IPSHeat_Manager();
			
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Stromheizung');
			$this->switchCategoryHeatId  = IPS_GetObjectIDByIdent('Switches', $baseId);
			$this->groupCategoryHeatId   = IPS_GetObjectIDByIdent('Groups', $baseId);
			$this->programCategoryHeatId = IPS_GetObjectIDByIdent('Programs', $baseId);			
			}	

		$this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
        $this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

		$this->categoryId_Auswertung  	= CreateCategory('Gartensteuerung-Auswertung', $this->CategoryIdData, 10);
		$this->categoryId_Register  	= CreateCategory('Gartensteuerung-Register', $this->CategoryIdData, 200);
        $this->categoryId_Statistiken	= CreateCategory('Statistiken',   $this->CategoryIdData, 200);
        
		$this->GiessTimeID	            = @IPS_GetVariableIDByName("GiessTime", $this->categoryId_Auswertung); 
		$this->GiessDauerInfoID	        = @IPS_GetVariableIDByName("GiessDauerInfo",$this->categoryId_Auswertung);
		
        /* Statistikmodul */
	    $this->StatistikBox1ID			= @IPS_GetVariableIDByName("Regenmengenkalender"   , $this->categoryId_Statistiken); 
	    $this->StatistikBox2ID			= @IPS_GetVariableIDByName("Regendauerkalender"   , $this->categoryId_Statistiken); 
	    $this->StatistikBox3ID			= @IPS_GetVariableIDByName("Regenereignisse" , $this->categoryId_Statistiken);   

		$this->GartensteuerungConfiguration=$this->setGartensteuerungConfiguration();
        
        $NachrichtenID    = $ipsOps->searchIDbyName("Nachrichtenverlauf-Gartensteuerung",$this->CategoryIdData);                // needle ist Nachricht
        $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);

        $systemDir     = $dosOps->getWorkDirectory();         
		$this->log_Giessanlage=new Logging($systemDir."Log_Giessanlage2.csv",$NachrichtenInputID,IPS_GetName(0).";Gartensteuerung;");

        if ($this->debug)
            {
            echo "Gartensteuerung Konfiguration :\n";    
            echo "    Kategorie Data ist : ".$this->CategoryIdData."   (".IPS_GetName($this->CategoryIdData).")\n";
            echo "    Kategorie Data.Gartensteuerung-Register ist : ".$this->categoryId_Register."   (".IPS_GetName($this->categoryId_Register).")\n";
            echo "    NachrichtenInput ID :  $NachrichtenInputID  (".IPS_GetName($NachrichtenInputID)."/".IPS_GetName(IPS_GetParent($NachrichtenInputID)).") \n";
            echo "    Logging in diesem File : ".$systemDir."Log_Giessanlage2.csv\n";
            //echo "GiesstimeID ist ".$this->GiessTimeID."\n";
            echo "-------\n";
            }        

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
            echo "  Variable im Archive mit Temperaturwerten die Aggregation unterstützt ". $this->variableTempID." (".IPS_GetName($this->variableTempID)."/".IPS_GetName(IPS_GetParent($this->variableTempID)).") \n";
            echo "  Variable im Archive mit Regenwerten die Aggregation unterstützt ". $this->variableID." (".IPS_GetName($this->variableID)."/".IPS_GetName(IPS_GetParent($this->variableID)).")\n";
			}
        $this->tempwerteLog = array();
        $this->tempwerte    = array();
        $this->werteLog     = array();
        $this->werte        = array();
        $this->werteStore   = array();

        /*    
		If ( ($Server=="") || ($Server==false) )
			{
  			//$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];             // lokaler ArchiveHandler definiert
            if ($this->variableTempID)
                {
			    $this->tempwerteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->variableTempID, $starttime, $endtime,0);		
	   		    $this->tempwerte = AC_GetAggregatedValues($this->archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);	// Tageswerte agreggiert 
                }
            if ($this->variableID)
                {
			    $this->werteLog = AC_GetLoggedValues($this->archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
		   	    $this->werte = AC_GetAggregatedValues($this->archiveHandlerID, $this->variableID, 1, $starttime2, $endtime,0);	    // Tageswerte agreggiert
                print_r($this->werte);
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

		// Letzen Regen ermitteln, alle Einträge der letzten 10 Tage durchgehen n getRainevents
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
			// Regenstand innerhalb der letzten 2 Stunden ermitteln 
			if (((time()-$wert["TimeStamp"])/60/60)<2)
				{
				$this->regenStand2h=$regenStand-$wert["Value"];
				}
			// Regenstand innerhalb der letzten 48 Stunden ermitteln
			if (((time()-$wert["TimeStamp"])/60/60)<48)
				{
				$this->regenStand48h=$regenStand-$wert["Value"];
				}
			}  // Regenwerte der Reihe nach durchgehen
			
		if ($this->debug) { echo "\n\n"; }  */
		}

    /* Gartensteuerung::getCategoryRegisterID
     * von CustomComponents die Counter Category finden 
     */
     public function getCategoryRegisterID($find="Counter")
        {
        $moduleManager_CC = new IPSModuleManager('CustomComponent');     /*   <--- change here */
        $CategoryIdData     = $moduleManager_CC->GetModuleCategoryID('data');
        return(@IPS_GetObjectIDByName($find."-Auswertung",$CategoryIdData));
        }               

	/******************************************************************
	 * Gartensteuerung::getConfig_aussentempID
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

	public function getConfig_raincounterID($config=false,$mode="Increment",$debug=false)
		{
        $resultArray=false;  
        if ($debug || $this->debug) $debug=true;   
        if ($debug) echo "getConfig_raincounterID aufgerufen. Mode is $mode. Config ist ".json_encode($config)."\n";  
        $CounterAuswertungID=$this->getCategoryRegisterID();          
        if ($config===false) 
            {
            if ($debug) echo "Read Configuration direkt from GartensteuerungConfiguration.\n";
            $Configuration = $this->GartensteuerungConfiguration;
            if (isset($Configuration["Configuration"])) $Configuration = $Configuration["Configuration"];
            //print_R($Configuration);
            return (["IncrementID"=>$this->RainRegisterIncrementID,"CounterID"=>$this->RainRegisterCounterID]);
            }
        elseif (is_array($config)) $Configuration = $config;
        else
            {   // String Variable, wahrscheinlich im Format Server::VariableName
            $result = explode("::",$config);
            $size = sizeof($result);
            if ($size==2)
                {
                $Configuration=array();
                $remServer	  = RemoteAccessServerTable(2,true);
                if (isset($remServer[$result[0]])) $url=$remServer[$result[0]]["Url"];
                else return (false);
                $rpc = new JSONRPC($url);
                echo "Server $url : ".$rpc->IPS_GetName(0)."\n";
                $prgID=$rpc->IPS_GetObjectIDByName("Program",0);
                $ipsID=$rpc->IPS_GetObjectIDByName("IPSLibrary",$prgID);
                $dataID=$rpc->IPS_GetObjectIDByName("data",$ipsID);
                $coreID=$rpc->IPS_GetObjectIDByName("core",$dataID);
                $compID=$rpc->IPS_GetObjectIDByName("IPSComponent",$coreID);
                $CounterAuswertungID=$rpc->IPS_GetObjectIDByName("Counter-Auswertung",$compID);
                $Configuration["RAINCOUNTER"]=$result[1];
                echo "   ".$Configuration["RAINCOUNTER"]." $CounterAuswertungID/$compID  \n";
                $RainRegisterIncrementID=@$rpc->IPS_GetObjectIDByName($Configuration["RAINCOUNTER"],$CounterAuswertungID);
                $RainRegisterCounterID=@$rpc->IPS_GetObjectIDByName($Configuration["RAINCOUNTER"]."_Counter",$CounterAuswertungID);
                return (["IncrementID"=>$RainRegisterIncrementID,"CounterID"=>$RainRegisterCounterID,"Url"=>$url]);
                }
            elseif ($size==1)
                {
                $Configuration["RAINCOUNTER"]=$result[0]; 
                $resultArray=true;  
                }
            else return(false);
            }

		if ( isset($Configuration["RainCounter"])==true) $Configuration["RAINCOUNTER"]=$Configuration["RainCounter"];
		if ( isset($Configuration["Raincounter"])==true) $Configuration["RAINCOUNTER"]=$Configuration["Raincounter"];
		if ( isset($Configuration["raincounter"])==true) $Configuration["RAINCOUNTER"]=$Configuration["raincounter"];

        $this->RainRegisterIncrementID = false;                         // wird nur mehr beim ersten Mal richtig berechnet, dann ist RAINCOUNTER eine OID
        $this->RainRegisterCounterID   = false;
		if ( ( (isset($Configuration["RAINCOUNTER"]))==true) && ($Configuration["RAINCOUNTER"]!==false) )
			{
            if ($debug) echo "[Raincounter=>".$Configuration["RAINCOUNTER"]."]\n";
            /* wenn die Variable in der Config angegeben ist diese nehmen, sonst die eigene Funktion aufrufen */
    		if ((integer)$Configuration["RAINCOUNTER"]==0) 
	    		{
                /* wenn sich der String als integer Zahl auflösen lässt, auch diese Zahl nehmen, Achtung bei Zahlen im String !!! */
    			if ($debug) echo "Alternative Erkennung des Regensensors, String als OID Wert für den RAINCOUNTER angegeben: \"".$Configuration["RAINCOUNTER"]."\". Jetzt in CustomComponents schauen ob vorhanden.\n";
                $RainRegisterIncrementID=@IPS_GetObjectIDByName($Configuration["RAINCOUNTER"],$CounterAuswertungID);
	    		if ($debug) echo "Check : Counter Kategorie   ".$CounterAuswertungID."  Incremental Counter Register  ".$this->RainRegisterIncrementID."\n";
		    	if ( ($CounterAuswertungID==false) || ($RainRegisterIncrementID==false) ) $fatalerror=true;
           		
                $RegisterCounterID=@IPS_GetObjectIDByName($Configuration["RAINCOUNTER"]."_Counter",$CounterAuswertungID);
	        	if ($debug) echo "Check : Counter Kategorie   ".$CounterAuswertungID."  Counter Register  ".$RegisterCounterID."\n";
    			if ( ($CounterAuswertungID==false) || ($RegisterCounterID==false) ) $fatalerror=true;
                else $RainRegisterCounterID = $RegisterCounterID;

                if (strtoupper($mode)=="INCREMENT") $variableID = $RainRegisterIncrementID;
                else $variableID = $RegisterCounterID;
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
        if ($resultArray) return (["IncrementID"=>$RainRegisterIncrementID,"CounterID"=>$RainRegisterCounterID]);
        elseif (IPS_VariableExists($variableID)) 
            {
            $this->RainRegisterCounterID=$RainRegisterCounterID;
            $this->RainRegisterIncrementID=$RainRegisterIncrementID;
            return($variableID); 		
            }
        else return (false);
		}
    
    // Register anzeigen und ausgeben
    function getRainRegisters($index=false,$debug=false)
        {
        if ($debug) {
            echo "Regenregister:\n";
            echo "   Inkrementelle Werte : ".$this->RainRegisterIncrementID."\n";
            echo "   Counter       Werte : ".$this->RainRegisterCounterID."\n";
        }
        if ($index===false) return (["IncrementID"=>$this->RainRegisterIncrementID,"CounterID"=>$this->RainRegisterCounterID]);
        elseif (strtoupper("IncrementID")=="Increment") return $this->RainRegisterIncrementID;
        else return $this->RainRegisterCounterID; 
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

    /* immer gleiche Vereinheitlichung der Auswertung für OIDs
     * kann ein Integerwert oder ein String sein
     * wenn String kann es ein Wert aus IPSHeat sein
     */

    private function getConfig_xID($configID,$debug=false)
        {
        $result=false;    
        if ((integer)$configID==0) 
            {
            if (isset($this->installedModules["Stromheizung"] ))
                {
                /* wenn sich der String als integer Zahl auflösen lässt, auch diese Zahl nehmen, Achtung bei Zahlen im String !!! */
                if ($debug) echo "   Alternative Erkennung der von \"$configID\", String als OID Wert angegeben, jetzt in Stromheizung/IPLights schauen ob vorhanden.\n";
                $result=array();
                $lightName=$configID;
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
                }
            }    
        else 
            {
            $result=(integer)$configID;
            if ($result !==false) 
                {
                if (@IPS_VariableExists($result)) return($result);
                if (@IPS_ObjectExists($result)) return($result);                     // die Switching Instanz
                return (false);                                             // vielleicht eine korrekte Evaluierung der Zahl, aber die Variable gibt es nicht 
                }		
            }
        return ($result);
        }

    /*******************
     * 
     * übernimmt oder liest die Konfiguration von $this->GartensteuerungConfiguration und liefert die OID der Wasserpunmpe
     * wenn liest, dann verschiedene Darstellungsformen der Konfiguration gleich machen
     * gültig mit Kategorie Configuration oder ohne
     * setConfiguration ändert auf WaterPump, getConfig hier wieder für die Vereinheitlichung zurück auf WATERPUMP 
     *
     * Mehrere Schreibweisen für Waterpump berücksichtigen, 
     *      ID kann Integer oder ein Zahl als String sein
     *      wenn ein alphanumerischer String dann Stromheizung fragen ob ein Schalter, Gruppe oder Programm so heisst
     * wenn es gar keinen Eintrag für WaterPump gibt, dann vielleicht die function set_gartenpumpe
     * dann noch die Plausichecks ob es die Variable auch gibt, sonst halt false als return Wert
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
            $result = $this->getConfig_xID($Configuration["WATERPUMP"]); 
            }
        else    
			{	
            if (function_exists("set_gartenpumpe")) return("set_gartenpumpe");
            else 
                {
                echo "*** Fehler getConfig_waterPumpID, keine Configuration für WATERPUMP und function set_gartenpumpe ist auch nicht vorhanden.\n";
                print_r($Configuration);
                $result=false;
                }
			}
        return ($result);
		}

    /*******************
     * 
     * übernimmt oder liest die Konfiguration von $this->GartensteuerungConfiguration und liefert Angabe des Register für die Ventilsteuerung
     * wenn liest, dann verschiedene Darstellungsformen der Konfiguration gleich machen
     * gültig mit Kategorie Configuration oder ohne
     * setConfiguration ändert auf ValveControl so wie auch getConfig 
     *
     * Mehrere Schreibweisen für ValveControl berücksichtigen, grundsätzlich muss es false oder ein array sein
     * jeder einzelne Eintrag als ID kann 
     *      Integer oder ein Zahl als String sein
     *      wenn ein alphanumerischer String dann Stromheizung fragen ob ein Schalter, Gruppe oder Programm so heisst
     * dann noch die Plausichecks ob es die Variable auch gibt, sonst halt false als return Wert
     *
     */

	function getConfig_valveControlIDs($config=false,$debug=false)
		{
        $failure=true;
        $confResult=Array();
        if ($debug || $this->debug) echo "getConfig_valveControlIDs aufgerufen.\n";            
        if ($config===false) 
            {
            if ($debug) echo "Read Configuration direkt from GartensteuerungConfiguration.\n";
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
                 * den Typ der Variable rausfinden, allgemeine Routine, eigentlich 
                 */
                $confResult[$index] = $this->getConfig_xID($valve); 
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

    /* getConfig_Modes
     * alle config Routinen haben selbe Parameter, lokale config bearbeiten, debug
     *
     */
    public function getConfig_Modes($config=false,$debug=false) 
        {
        if ($debug || $this->debug) 
            {
            echo "getConfig_Modes aufgerufen.\n";
            $debug = $debug || $this->debug;
            }
        if ($config===false) $Configuration = $this->GartensteuerungConfiguration;
        else $Configuration = $config;
        $text="";

        $text .= "Betriebsart: ";
        if ($Configuration["Configuration"]["Statistics"]=="ENABLED") $text .= "Statistik ";
        if (strtoupper($Configuration["Configuration"]["DataQuality"])=="ENABLED") $text .= "Datenqualität ";

        if ($Configuration["Configuration"]["Irrigation"]=="ENABLED") 
            {
            $text .= ",Bewässerung ";
            if ($Configuration["Configuration"]["PowerPump"]=="ENABLED") 
                {
                $text .= "mit Energiemessung ";
                if ( (isset($Configuration["Configuration"]["CheckPower"])) && ($Configuration["Configuration"]["CheckPower"]!==null) )
                    {
                    $text .= "Register : ".$Configuration["Configuration"]["CheckPower"]."  ";
                    }
                }
            if ($Configuration["Configuration"]["Mode"]=="Switch") $text .= "und Ventilsteuerung";
            }
        $text .= "---Ende\n";
        return ($text);
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

    function setGartensteuerungConfiguration($debug=false)
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
            // Darstellung Webfronts, individuelle Tabs einstellen
            configfileParser($configConf["Configuration"], $config["Configuration"], ["STATISTICS","Statistics","statistics"],"Statistics" ,"ENABLED");  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["IRRIGATION","Irrigation","irrigation"],"Irrigation" ,"ENABLED");  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["POWERPUMP","Powerpump","powerpump","PowerPump",],"PowerPump" ,"ENABLED");  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["DATAQUALITY","Dataquality","dataquality","DataQuality",],"DataQuality" ,"ENABLED");  

            configfileParser($configConf["Configuration"], $config["Configuration"], ["MODE","Mode","mode"],"Mode" ,"Switch");          //Default, mit automatischen Regenkreisumschalter  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["WATERPUMP","WaterPump","Waterpump","waterpump"],"WaterPump" ,false);  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["VALVECONTROL","ValveControl","Valvecontrol","valvecontrol"],"ValveControl" ,false);  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["CHECKPOWER","CheckPower","Checkpower","checkpower"],"CheckPower" ,null);  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["RAINCOUNTER","Raincounter","RainCounter","raincounter"],"RainCounter" ,false);  
            configfileParser($configConf["Configuration"], $config["Configuration"], ["RAINCOUNTERHISTORY","Raincounterhistory","RainCounterHistory","raincounterhistory"],"RainCounterHistory",null);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["AUSSENTEMP","Aussentemp","AusenTemp","aussentemp"],"AussenTemp",null);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["REMOTEACCESSADR","RemoteAccessAdr","Remoteaccessadr","remoteaccessadr"],"RemoteAccessAdr",null);

            configfileParser($configConf["Configuration"], $config["Configuration"], ["REPORTS","Reports","reports","Report"],"Reports",[]);

            /* courtesy for old functions */
            configfileParser($configConf["Configuration"], $config["Configuration"], ["DEBUG","Debug","debug"],"DEBUG",false);
            configfileParser($configConf["Configuration"], $config["Configuration"], ["PUMPE","Pumpe","pumpe"],"PUMPE",null);           // nur wenn vorhanden übernehmen, sonst Waterpump, OID Parameter für den Fall setGartenpumpe(state,["PUMPE"])

            configfileParser($configConf["Configuration"], $config["Configuration"], ["KREISE","Kreise","kreise"],"KREISE",0);
            for ($i=1;$i<=$config["Configuration"]["KREISE"];$i++)
                {
                configfileParser($configConf["Configuration"], $config["Configuration"], ["KREIS".$i,"Kreis".$i,"kreis".$i],"KREIS".$i,"unknown description");
                if ($debug) echo"   $i:".$config["Configuration"]["KREIS".$i]."\n";
                }

            $config["Configuration"]["RainCounterInput"]=$config["Configuration"]["RainCounter"];
            $config["Configuration"]["RainCounter"]=$this->getConfig_raincounterID($config["Configuration"], "Increment", $debug);
            $config["Configuration"]["RainCounterIncrement"]=$config["Configuration"]["RainCounter"];
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
            $configInput["RAINCOUNTER"]=$this->getConfig_raincounterID($configInput, "Counter", $debug);
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

    function getConfig_Gartensteuerung()
        {
        return ($this->GartensteuerungConfiguration);   
        }

    /* allgemeine Ansteuerung entsprechend der Configuration
     *
     */

    private function control_xID($configuration,$state)
        {
        $result=true;
        $debug=$this->debug;
        if (isset($configuration["ID"]))                // hier nur mehr Stromheizung Schalter bedienen 
            {
            switch ($configuration["TYP"])
                {
                case "GROUP":
                    $message = "Schalte Gruppe ".$configuration["NAME"]." auf Status ".($state?"Ein":"Aus").".";
                    IPSHeat_SetGroupByName($configuration["NAME"],$state);
                    if ($debug) echo "    IPS Heat Group \"".$configuration["NAME"]."\"\n";
                    break;
                case "SWITCH":
                default:
                    $message = "Schalter ".$configuration["NAME"]." auf Status ".($state?"Ein":"Aus").".";
                    IPSHeat_SetSwitchByName($configuration["NAME"],$state);
                    if ($debug) echo "    IPS Heat Switch \"".$configuration["NAME"]."\"\n";
                    break; 
                } 
            $this->log_Giessanlage->LogMessage($message);
            $this->log_Giessanlage->LogNachrichten($message);
            return ($result);    
            }
        elseif ((integer)$configuration != 0)             // homematic direktansteuerung
            {
            $oid=$configuration;
            $message = "Homematic ".IPS_GetName($oid)." ($oid) auf Status ".($state?"Ein":"Aus").".";
            $failure=HM_WriteValueBoolean($oid,"STATE",$state);
            if ($debug) echo "    Homematic \"".IPS_GetName($oid)."\" ($oid) auf Status ".($state?"Ein":"Aus").".\n";
            $this->log_Giessanlage->LogMessage($message);
            $this->log_Giessanlage->LogNachrichten($message);
            return ($failure);    
            }
        }

    /******************
     * einheitliche Ansteuerung der Wasserpumpe
     *  Waterpump   gibt die OID eines Homematic Switches oder den Namen eines IPSHeat Objektes vor    
     *
     * Wenn PUMPE definiert ist, immer die function set_gartenpumpe aufrufen, muss in Gartensteuerung_Configuration definiert sein
     *
     *********************************/

    function control_waterPump($state,$debug=false)
        {
        if ($debug || $this->debug) 
            {
            echo "control_waterPump($state) aufgerufen.\n";
            $debug=($debug || $this->debug);
            }     
        $result=false;
        $config=$this->getConfig_Gartensteuerung()["Configuration"];
        if (isset($config["WaterPump"]))
            {
            $configuration=$config["WaterPump"];
            $result=$this->control_xID($configuration,$state);
            if ($result) return($result);    
            }
        if ( (function_exists("set_gartenpumpe")) || ((isset($config["WaterPump"])) && ($config["WaterPump"]=="set_gartenpumpe") ))
            {
            if (isset($config["PUMPE"])==true) 
                {
                $result=set_gartenpumpe($state,$config["PUMPE"]);
                if ($debug) echo "    set_gartenpumpe(".($state?"Ein":"Aus").",".$config["PUMPE"].") \n";
                }
            else 
                {
                $result=set_gartenpumpe($state);
                if ($debug) echo "    set_gartenpumpe(".($state?"Ein":"Aus").") \n";
                }
            }
        return ($result);
        }

     /* die einzelnen Pumpen sind mit KREIS1 bis KREISx bezeichnet
      * aus einem Count von 0 bis x-1 den String berechnen der für den Index in der Config verwendet wird, zentral hier am besten   
      */

    function getKreisfromCount($Count)
        {
        $kreis = "KREIS".(string)($Count+1);            
        return ($kreis);
        }

    /******************
     * wenn es Ventile gibt, diese hier einheitlich ansteuern
     * Routine wird auch aufgerufen wenn es keine Ventile zum Schalten gibt, Mode Auto statt Switch
     * GiessCount wird alle x Minuten um 1 erhöht, jedes zweite Mal wird weitergeschaltet dazwischen gibt es eine Pause 
     * bem Automode war eine Pause nötig damit der Umschalter Zeit zum weiterschalten hatte, die Pumpe wurde angeschaltet und der Mechanismus schaltet damit automatisch um eins weiter
     *      0/1 ist das erste Ventil, 1 (?) wäre die Pause ohne Pumpleistung
     *      2/3 das zweite Ventil
     *      usw.
     * GiessCount                                   0,     1,     2,     3,     4,   5,6...n        (n=KREISE*2+1, Beispiel für 2 Kreise ist n=5
     * Count = floor(GiessCount/2)                  0,     0,     1,     1,     2...n/2    
     * kreis = KREIS.(Count+1)                      KREIS1,KREIS1,KREIS2,KREIS2 ....
     * kreisOld wird gespeichert sobald Count>0,    false, false,KREIS1, KREIS1,..... 
     *********************************/

    function control_waterValves($GiessCount,$debug=false)            
        {
        if ($debug || $this->debug) 
            {
            echo "control_waterValves($GiessCount aufgerufen.\n";
            $debug=($debug || $this->debug);
            }     
        $result=false;
        $config=$this->getConfig_Gartensteuerung()["Configuration"];        
        $Count=floor($GiessCount/2);                                    // 0 oder 1 ist das erste Ventil, berechnet zu 0
        $kreis = $this->getKreisfromCount($Count);     // 0 wird zu 1 und ergänzt auf KREIS1
        if ($Count>0) $kreisOld = "KREIS".(string)($Count);                  // aus KREIS2 wird KREIS1
        else $kreisOld=false;
        if ($GiessCount==(($config["KREISE"]*2)+1))             // wir sind am Ende angelangt, letztes oder besser alle Ventile ausschalten
            {
            $state=false;
            for ($i=0;$i<(floor($GiessCount/2));$i++)
                {
                if (isset($config["ValveControl"][$this->getKreisfromCount($i)]))         // das wäre jetzt 0...floor(n/2) bei 2 Kreisen 0,1, bei 4 Kreisen 0..3
                    {
                    $configuration=$config["ValveControl"][$this->getKreisfromCount($i)];
                    $result=$this->control_xID($configuration,$state);              // egal wie das Objekt dargetellt wird es wird gesschlatet, Venti1..4 oder Pumpe
                    }
                elseif (function_exists("set_ventile")) set_ventil($state,$oid);
                else echo "No Config for setting valves.\n";
                }
            }
        else                                                    // wir sind dazwischen, immer gerade/ungerade, 0/1 wird zu KREIS1, 2/3 zu KREIS 2
            {
            $state=true;
            if (isset($config["ValveControl"][$kreis]))
                {
                $configuration=$config["ValveControl"][$kreis];
                $result=$this->control_xID($configuration,$state);
                }
            elseif (function_exists("set_ventile")) set_ventil($state,$oid);
            else echo "No Config for setting valves.\n";
            if ($kreisOld)                                                                   // KREIS1 wurde bereits bearbeitet
                {
                $state=false;
                if (isset($config["ValveControl"][$kreisOld]))
                    {
                    $configuration=$config["ValveControl"][$kreisOld];
                    $result=$this->control_xID($configuration,$state);
                    }
                elseif (function_exists("set_ventile")) set_ventil($state,$oid);
                else echo "No Config for setting valves.\n";
                }
            }
        return ($result);
        }

	/******************************************************************
	 *
	 * Berechnung Giessbeginn morgens und abends
	 *
	 ***************************************************************************/

    function fromdusktilldawn($duskordawn=true)      // default ist Abends
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

	function Giessdauer($debug=false)
		{
		if ($this->debug || $debug) $debug=true;

		//global $log_Giessanlage;
        $variableTempID=$this->variableTempID;
        $variableRainID=$this->variableID;
		$giessdauerVar=0;
        $endtime=time();
        $starttime=$endtime-60*60*24*2;         // die letzten zwei Tage für Temperatur 
        $starttime2=$endtime-60*60*24*10;       // die letzten 10 Tage für Regen

		$Server=RemoteAccess_Address();
		if ($debug) echo "Gartensteuerung::Giessdauer, Aufruf für Berechnung der Giessdauer : 0,10 oder 20 Minuten\n";
			
		If ($Server=="")
			{
            $archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$variableTempName = IPS_GetName($variableTempID);
			$variableRainName = IPS_GetName($variableRainID);
    		if ($debug) echo "Regen und Temperaturdaten, lokale Daten -> Temp: $variableTempName ($variableTempID) Rain: $variableRainName ($variableRainID) \n\n";		
            $tempwerte = AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);                // 1 für tägliche Aggregation der Temperaturwerte
            $werteLog = AC_GetLoggedValues($archiveHandlerID, $variableRainID, $starttime2, $endtime,0);
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$variableTempName = $rpc->IPS_GetName($variableTempID);
			$variableRainName = $rpc->IPS_GetName($variableRainID);
			if ($debug) echo "   Daten vom Server : $Server -> Temp: $variableTempName ($variableTempID) Rain: $variableRainName ($variableRainID)\n";
            $archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $tempwerte = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);
            $werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $variableRainID, $starttime2, $endtime,0);
			}

		//$AussenTemperaturGesternMax=get_AussenTemperaturGesternMax();
		$AussenTemperaturGesternMax=$tempwerte[1]["Max"];
		//$AussenTemperaturGestern=AussenTemperaturGestern();
		$AussenTemperaturGestern=$tempwerte[1]["Avg"];
	
		$letzterRegen=0;
		$regenStand=0;
		$regenStand2h=0;
		$regenStand48h=0;
		$regenStandAnfang=0;  /* für den Fall dass gar keine Werte gelogged wurden */
		$regenStandEnde=0;
		$RegenGestern=0;
		
        // Letzen Regen ermitteln, alle Einträge der letzten 48 Stunden durchgehen, Berechnung geht von einem Counter aus 
		foreach ($werteLog as $wert)
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
		if ($debug) echo "Regenstand 2h : ".$regenStand2h." 48h : ".$regenStand48h." 10 Tage : ".$regenStand." mm.\n";


		if ($debug)
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
		if ($debug) echo $textausgabe;
		return $giessdauerVar;
		}


	/*
	 *   Ausgabe der Events/Nachrichten über die Giessanlage, wann wie lange eingeschaltet
	 *
	 */

	public function listEvents()
		{
		return ($this->log_Giessanlage->PrintNachrichten());
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

    /* Tempwerte in die Class einlesen
     *
     */
    public function getTempRainValues($debug=false)
        {
        $endtime=time();
        $starttime=$endtime-60*60*24*7;  /* die letzten zwei Tage, für Auswertung sieben Tage nehmen */
        $starttime2=$endtime-60*60*24*10;  /* die letzten 10 Tage */

        $Server=RemoteAccess_Address();
        If ($Server=="")
            {
            echo "Regen und Temperaturdaten, lokale Daten: Temp $variableTempID Rain $variableID \n\n";		
            //AC_ReAggregateVariable ($archiveHandlerID, $variableID);  
            //$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $tempwerte = AC_GetAggregatedValues($archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);                // 1 für tägliche Aggregation der Temperaturwerte
            $variableTempName = IPS_GetName($variableTempID);
            $werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
            $werte = AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime2, $endtime,0);	/* Tageswerte agreggiert */
            $werteStd = AC_GetAggregatedValues($archiveHandlerID, $variableID, 0, $starttime2, $endtime,0);	/* Stundenwerte agreggiert */
            $variableName = IPS_GetName($variableID);
            if (count($tempwerte)<2) AC_ReAggregateVariable ($archiveHandlerID, $variableTempID);  
            }
        else
            {
            echo "Regen und Temperaturdaten vom Server : ".$Server."\n\n";
            $rpc = new JSONRPC($Server);
            $archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $tempwerte = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableTempID, 1, $starttime, $endtime,0);
            $tempwerteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $variableTempID, $starttime, $endtime,0);			
            $variableTempName = $rpc->IPS_GetName($variableTempID);
            $werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
            //$werte = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableID, 1, $starttime2, $endtime,0);
            //$werteStd = $rpc->AC_GetAggregatedValues($archiveHandlerID, $variableID, 0, $starttime2, $endtime,0);  // funktioniert nicht kann nicht zusammenzaehlen, mittelt immer
            $variableName = $rpc->IPS_GetName($variableID);
            }
        }

	/*******************
     * Gartensteuerung::writeKalendermonateHtml                 depricated, wird nicht mehr eingesetzt
     * Ausgabe der Regenereignisse (ermittelt mit listrainevents) mit Beginn, Ende, Dauer etc als html Tabelle zum speichern in einer hml Box
     * Spalten:  Regenbeginn | Regenende | Dauer Min | Menge mm  |  max mm/Stunde
     *
	 */

	public function writeKalendermonateHtml($kalendermonate)
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
     * Gartensteuerung::writeOverviewMonthsHtml
     * Ausgabe der Regenmenge/dauer (ermittelt mit listrainevents) als html Tabelle Jahre versus Monate zum speichern/anzeigen in einer hml Box
     * Input ist ein Array mit Index MM.JJ oder Index und Value/Timestamp
     * verschieden Datenwuellen möglich, unterscheidung mit Mode
     * mode=1   Index ist bereits MM.YY, Wert ist ein scalar und kann direkt verwendet werden
     * mode=2   Index ist ein aufsteigender Wert, Wert ist ein array mit Value/TimeStamp
     *
	 */
	public function writeOverviewMonthsHtml($monthlyValues=array(),$mode=1,$debug=false)
		{
        // umgestellt auf intern 4stellige Jahreszahlen
        $startjahr=(integer)date("Y",time());
        $minjahr=$startjahr;
        //print_r($monthlyValues);
        $type="sum";                   // default is mode=1 und type=sum
        $dv=10000;    // sum10k 
        $summe=array();
        if (is_array($mode))
            {
            if (isset($mode["type"])) $type = $mode["type"];
            else $type="sum";
            if (isset($mode["mode"])) $mode = $mode["mode"];
            else $mode=1;
            }
        if ($debug) 
            {
            echo "\n";
            echo "writeOverviewMonthsHtml Mode $mode Type $type \n";
            }
        if ($mode==1)           // Index ist bereits MM.YY
            {
            foreach ($monthlyValues as $date=>$regenmenge)
                {
                $MonatJahr=explode(".",$date);
                $monat=(integer)$MonatJahr[0]; $jahr=(integer)$MonatJahr[1];
                if ($jahr<100) $jahr += 2000;
                if ($jahr<$minjahr) $minjahr=$jahr;
                $tabelle[$monat][$jahr]=$regenmenge;

                if (isset($summe[$jahr])) 
                    {
                    $summe[$jahr]["value"] += $regenmenge;
                    $summe[$jahr]["count"]++;
                    }
                else 
                    {
                    $summe[$jahr]["value"] = $regenmenge;
                    $summe[$jahr]["count"] = 1;
                    }
                }
            }
        elseif ($mode==2)                // Index ist ein aufsteigender Wert, Wert ist ein array mit Value/TimeStamp
            {
            foreach ($monthlyValues as $index=>$entry)
                {
                $date=date("m.Y",$entry["TimeStamp"]);
                $regenmenge=$entry["Value"];
                $MonatJahr=explode(".",$date);              // index 0 ist Monat, 1 ist Jahr
                $monat=(integer)$MonatJahr[0]; $jahr=(integer)$MonatJahr[1];
                if ($jahr<$minjahr) $minjahr=$jahr;
                $tabelle[$monat][$jahr]=$regenmenge;                
                
                if (isset($summe[$jahr])) 
                    {
                    $summe[$jahr]["value"] += $regenmenge;
                    $summe[$jahr]["count"]++;
                    }
                else 
                    {
                    $summe[$jahr]["value"] = $regenmenge;
                    $summe[$jahr]["count"] = 1;
                    }
                }
            }
        else            // Index ist ein aufsteigender Wert, aber es gibt noch einen Kanal identifier danach
            {
            foreach ($monthlyValues as $index=>$entry)
                {
                foreach ($entry as $channel=>$subentry)
                    {
                    $date=date("m.Y",$subentry["TimeStamp"]);
                    $regenmenge=$subentry["Value"];
                    $MonatJahr=explode(".",$date);              // index 0 ist Monat, 1 ist Jahr
                    $monat=(integer)$MonatJahr[0]; $jahr=(integer)$MonatJahr[1];
                    if ($jahr<$minjahr) $minjahr=$jahr;
                    $tabelle[$monat][$jahr]=$regenmenge;                
                    
                    if (isset($summe[$jahr])) 
                        {
                        $summe[$jahr]["value"] += $regenmenge;
                        $summe[$jahr]["count"]++;
                        }
                    else 
                        {
                        $summe[$jahr]["value"] = $regenmenge;
                        $summe[$jahr]["count"] = 1;
                        }
                    }
                }
            }
        if ($debug) print_R($tabelle);
        $html="";
        $html.='<table frameborder="1" width="100%">';
    	$html.="<th> <tr> <td> Monat </td> ";
        for ($i=$startjahr;$i>=$minjahr;$i--) {$html.="<td> ".$i." </td>";}
        $html.= " </tr> </th>";
	    for ($j=1;$j<=12;$j++)          // Monate sind die zeilen
            {
            $html.="<tr> <td>".$j."</td>";
            for ($i=$startjahr;$i>=$minjahr;$i--) 
                {
                if (isset($tabelle[$j][$i])==true) 
                    {
                    if ($type=="sum10k") $html.="<td> ".number_format($tabelle[$j][$i]/$dv,1,",","")." </td>";
                    else $html.="<td> ".number_format($tabelle[$j][$i],1,",","")." </td>";
                    }
                else $html.="<td> </td>";
                }
            $html.= " </tr> ";
    	    }
        // Summe/Mean
        $html.="<tr> <td>Summe</td>";
        for ($i=$startjahr;$i>=$minjahr;$i--) 
            {
            if (isset($summe[$i])==true)
                {
                if ($type=="sum")        $html.="<td> ".number_format($summe[$i]["value"],1,",","")." </td>";
                elseif ($type=="sum10k") $html.="<td> ".number_format($summe[$i]["value"]/$dv,0,",","")." </td>";
                else                     $html.="<td> ".number_format(($summe[$i]["value"]/$summe[$i]["count"]),1,",","")." </td>";
                }
            else $html.="<td> </td>";
            }
        $html.= " </tr> ";

   	    $html.="</table>";
        return($html);
        }   

	}  /* Ende class Gartensteuerung */

/************************************************************
 *
 * GartensteuerungControl
 *
 * Umsetzung von Befehlen
 *
 *
 * Funktionen
 *      __construct
 *      start
 *
 */

class GartensteuerungControl extends Gartensteuerung
	{

    protected $GiessTimeRemainID,$GiessCountID;
    protected $timerDawnID,$UpdateTimerID;

    function __construct($debug=false)
        {
		$this->debug=$debug;		
        parent::__construct();          // nicht vergessen den parent construct auch aufrufen
        $this->GiessTimeRemainID           = @IPS_GetVariableIDByName("GiessTimeRemain", $this->categoryId_Auswertung);                 // sonst ist die category noch nicht bekannt
        $this->GiessCountID		= @IPS_GetVariableIDByName("GiessCount",$this->categoryId_Register);

        $GartensteuerungScriptID   		= IPS_GetScriptIDByName('Gartensteuerung', $this->CategoryIdApp);
	    $this->timerDawnID = @IPS_GetEventIDByName("Timer3", $GartensteuerungScriptID);
	    $this->UpdateTimerID = @IPS_GetEventIDByName("UpdateTimer", $GartensteuerungScriptID);	    
        }

    public function start()
        {
        if ($this->debug) echo "start aufgerufen.\n";
        echo "here is ".$this->GiessTimeRemainID."\n";
        SetValue($this->GiessTimeID,10);
        SetValue($this->GiessTimeRemainID ,0);				
        IPS_SetEventActive($this->UpdateTimerID,true);				
        IPS_SetEventCyclicTimeBounds($this->UpdateTimerID,time(),0);  /* damit alle Timer gleichzeitig und richtig anfangen und nicht zur vollen Stunde */
        IPS_SetEventActive($this->timerDawnID,false);
        SetValue($this->GiessCountID,1);
        }

    }

/************************************************************
 *
 * GartensteuerungStatistics, Klimaberechnungen auf Basis Daten von Zamg/geosphere
 * erstellt die Tabellen mit der Übersicht der gemessenen Werte
 *
 *  __construct
 *  setWerteStore               verwendet einen internen werte store, also array auf das alle routinen zugreifen können
 *  getRainStatistics
 *  getRainEventsFromIncrements
 *  getRainAmountSince
 *  getRainValuesFromIncrements
 *  listRainEvents
 *  listRainDays
 *  getRainevents
 *  writeRainEventsHtml
 *
 *
 *
 *
 */

class GartensteuerungStatistics extends Gartensteuerung
	{
    protected $archiveOps;                              // class for archives, general use
    protected $ipsTables;                       // schöne Tabelle zeichnen
    protected $werteStore = array();                  // Inputwete für Berechnung, nur einmal erfassen

    function __construct($debug=false)
        {
        $this->werteStore = array();
        $this->archiveOps = new archiveOps();
        $this->ipsTables = new ipsTables();

        if ($debug) echo "GartensteuerungStatistics construct aufgerufen. Debug Mode.\n";

        parent::__construct(0,0,$debug);                    // keine Start und Endtime übergeben
        }


    /* GartensteuerungStatistics::setWerteStore
     * nur in dieser class, es gibt einen werte Speicher
     * und verschiedene Möglichkeiten die Daten dorthin einzulesen
     */
    public function setWerteStore($rainregs=false,$mode="INCREMENT",$debug=false)
        {
        $endtime=time();
        $starttime=$endtime-60*60*24*100;                // 10 Tage
        if (is_array($rainregs))
            {
            if (isset($rainregs["Url"]))            // manuelles overwrite remote
                {
                $url=$rainregs["Url"];
                echo "setWerteStore, remote Server mit url $url \n";
                $rpc = new JSONRPC($url);
			    $archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                if ( (strtoupper($mode)=="INCREMENT") && (isset($rainregs["IncrementID"])) )
                    {
                    $variableID = $rainregs["IncrementID"];
                    }
                elseif (isset($rainregs["CounterID"]))
                    {
                    $variableID = $rainregs["CounterID"];
                    }
                echo "get Werte from Remote Server, last 100 days, data $archiveHandlerID, $variableID , $starttime, $endtime \n";
                $werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $variableID , $starttime, $endtime,0);
                if ($debug) print_R($werteLog);
                }
            else                // manuelles overwrite lokal
                {
                $archiveHandlerID=$this->archiveOps->getArchiveID();   
                if ( (strtoupper($mode)=="INCREMENT") && (isset($rainregs["IncrementID"])) )
                    {
                    $variableID = $rainregs["IncrementID"];
                    }
                elseif (isset($rainregs["CounterID"]))
                    {
                    $variableID = $rainregs["CounterID"];
                    }
                $werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID , $starttime, $endtime,0);
                if ($debug) print_R($werteLog);
                }
            }
        else            // erster Wert ist false oder kein Array, default
            {
            $archiveHandlerID=$this->archiveOps->getArchiveID();   
			$werteLog = AC_GetLoggedValues($archiveHandlerID, $this->variableID, $starttime, $endtime,0);
            $this->tempwerte = AC_GetAggregatedValues($archiveHandlerID, $this->variableTempID, 1, $starttime, $endtime,0);                // 1 für tägliche Aggregation der Temperaturwerte
            }
        $this->werteStore =  $werteLog;   
        }

    /* show Werte Store
     *
     */
    public function showWerteStore($debug=false)
        {
        //print_R($this->werteStore);
        $config=array();
        $config["DataType"]="Array";
        $config["OIdtoStore"]="Rain";
        $config["manAggregate"]="daily";                     // verwendet archivierte Daten statt manuell zu integrieren, zu viele Daten, zusammenfassen, 0 stündlich, 1 täglich, 2 wöchentlich
        $config["ShowTable"]["align"]="daily";
        $this->archiveOps->getValues($this->werteStore,$config,$debug);          // true,2 Debug, Werte einlesen

        $config["OIdtoStore"]="Temp";
        $config["Aggregated"]="daily";                     // verwendet archivierte Daten statt manuell zu integrieren, zu viele Daten, zusammenfassen, 0 stündlich, 1 täglich, 2 wöchentlich
        $this->archiveOps->getValues($this->tempwerte,$config,$debug);          // true,2 Debug, Werte einlesen

        $config["AggregatedValue"]=["Avg","Min","MinTime","Max","MaxTime"];                   // es werden immer alle Werte eingelesen
        $config["ShowTable"]["output"]="realTable";
        $result = $this->archiveOps->showValues(false,$config);
        $display = $this->ipsTables->checkDisplayConfig($this->ipsTables->getColumnsName($result,$debug),$debug);
        print_R($display);
        $display = [
                    "TimeStamp"                        => ["header"=>"Date","format"=>"DayMonth"],
                    //"29877"                        => ["header"=>"MittelTemp","format"=>"°"],
                    "TempMin"                        => ["header"=>"MinTemp","format"=>"°"],
                    "TempMinTime"                        => ["header"=>"MinTime","format"=>"HourMin"],
                    "TempMax"                        => ["header"=>"MaxTemp","format"=>"°"],
                    "TempMaxTime"                        => ["header"=>"MaxTime","format"=>"HourMin"],
                    "Rain"                        => ["header"=>"Regen","format"=>"mm"],
                    ];
        $config=array();
        $config["html"]=false;
        $config["text"]=true;
        $config["insert"]["Header"]    = true;
        $config["transpose"]=false;
        $config["reverse"]=true;          // die Tabelle in die andere Richtung sortieren
        ksort($result);
        $html = $this->ipsTables->showTable($result, $display,$config,false);     // true/2 für debug , braucht einen Zeilenindex

        }



	/* GartensteuerungStatistics::getRainStatistics
     * verwendet getRainEventsFromIncrements, getRainValuesFromIncrements
     * beide Routinen ahben selbe Datenbasis, wird nicht mehr upgedated und ist Teil der class
	 *  Allerlei Auswertungen mit der Regenmenge.
     *  verwendet getRainEventsFromIncrements, übergibt die Ereignisse in der ersten Variable 
	 *  Ergebnisse der statistischen Auswertungen werden in der Klasse gespeichert und sind dann auch verfügbar
     *  Arrays:  $this->RegenKalendermonate, $this->DauerKalendermonate
     * 
     *  Beispiel Ausgabe: Auswertung 1: 0,0mm  7: 4,4mm 30: 17,7mm 30: 37,8mm 360: 442,2mm 360: 0,0mm
     *
	 */

	public function getRainStatistics($input=array(),$debug=false)
		{
        echo "GartensteuerungStatistics::getRainStatistics, getRainEventsFromIncrements wird aufgerufen.\n";            
        $regenereignis=array();            // regenereignis ist ein pointer, input die Liste der Variablen die in die Statistik eingehen sollen
        $regenMenge = $this->getRainEventsFromIncrements($regenereignis,$input,false);         // true Debug
        //print_R($regenereignis);        
        echo "    Regenmenge im gesamten Zeitraum ist $regenMenge mm. Variablen ausgewertet: ".json_encode($input)."\n";
        $kalendermonate=array();
        if (sizeof($regenereignis)==0) 
            {
            echo "Fehler, Regenereignis ist leer, alternative Berechnung aus der Parent class.\n";
            $regenereignis=$this->listRainEvents(1000);          // wir haben hier noch eine Alternativberechungsmethode
            }
        foreach ($regenereignis as $eintrag) 
            {
            //print_r($eintrag);
            $dauer = ($eintrag["Ende"]-$eintrag["Beginn"])/60/60;           // Dauer in Stunden
            $monat=date("m.y",$eintrag["Ende"]);
            if (isset($kalendermonate[$monat])) $kalendermonate[$monat] += $dauer;
            else $kalendermonate[$monat] = $dauer;
                
            if ($dauer>0)
                {
                /* einmalige Ereignise, 03 mm in 2 Stunden ingnorieren */
                if ($debug > 1) echo "Anfang ".date("D d.m.y H:i",$eintrag["Beginn"])." bis Ende ".date("D d.m H:i",$eintrag["Ende"])." mit insgesamt "
                        .number_format($eintrag["Regen"], 1, ",", "")." mm Dauer ".number_format($dauer, 1, ",", "")." Stunden  Max :".number_format($eintrag["Max"], 1, ",", "").
                        " l/Std\n";
                }
            }  
        $this->DauerKalendermonate=$kalendermonate;     // Ergebnis in der Klasse abspeichern

        // from register_Counter, das sind die Zählerstaende, die Regenwerte pro Tag ermiteln, letzer Zählerstand pro Tag            
        $regenWerte = $this->getRainValuesFromIncrements($regenMenge,$input,$debug);                           
        if ($debug>1)
            {
            echo "Regenwerte in getRainStatiscs ermittelt, insgesamt ".count($regenWerte)." Werte, hier ausgeben:\n";
            //print_R($regenWerte);
            foreach ($regenWerte as $time=>$regenWert) echo "   ".date("d.m.Y H:i:s",$time)."  $regenWert mm\n";
            }

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


    /* GartensteuerungStatistics::
     * noch eine Routine um die Regenereignisse herauszuarbeiten
     * Die Variable die im Namen ein _Counter am Ende hat ist das Input Register mit einem Zählerstand, 
     * Counter2 sollte der Zählerstand sein der um den rewset des registers durch einen Stromausfall bereinigt ist und den Zählerstand richtig weiterschreibt.
     *
     * da das alles viel zu kompliziert ist den incrementellen registerwert nehmen. Im einfachen Registernamen gespeichert:
     *
     * rainRegs ist ein array mit Inputregistern die miteinenader die Niederschlagswerte ergeben
     *
     * BKS:
     * 12713 >>> alte Werte von 2016 bis Jänner 2020
     * 38316  >>> neuer Wert ab August 2020
     *
     * Ergebnis sind regenereignis als array und pointer
     *
     * den Werte Log durchgehen, wenn Value>0 ist zur Gesamtmenge Regen dazuzaehlen. Bei Value=0 nichts machen
     * beim ersten Mal ein regenereignis Ende mit diesem timestamp anlegen, dabei sich von der Jetztzeit zurückarbeiten
     * wenn die Zeit zwischen zwei Regenmesswerten > 60 Minuten betraegt, den Beginn eintragen und ein neues regenereignis beginnen
     *
     ***/

    public function getRainEventsFromIncrements(&$regenereignis, $rainRegs=array(),$debug=false)
        {
        if ($debug) echo "getRainEventsFromIncrements mit ".json_encode($rainRegs)." Register aufgerufen. Rain Register from class ID ".$this->RainRegisterIncrementID." .\n";
        //$debug=false;
        //if ($this->RainRegisterIncrementID>0) $rainRegs[0]=$this->RainRegisterIncrementID;
        if (($this->RainRegisterIncrementID>0) && (sizeof($rainRegs)==0)) $rainRegs[0]=$this->RainRegisterIncrementID;          // Default register verwenden
        $init=true;             // beim ersten Mal
        $i=0;                   // Zaehler Regenereignisse
        $lasttime=time();       // von der Jetztzeit zurückarbeiten
        $regenMenge=false;
        echo "getRainEventsFromIncrements, diese Register als Input verwenden: ".json_encode($rainRegs)."\n";

        $archiveOps = new archiveOps();
        $archiveHandlerID=$archiveOps->getArchiveID();
        $config = $archiveOps->getConfig();
        $configCleanUpData = array();
        $configCleanUpData["deleteSourceOnError"]=false;
        $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
        $config["CleanUpData"] = $configCleanUpData;    

        foreach ($rainRegs as $rainReg)
            {
            /* Es gibt ein inkrementales Regenregister in der CustomComponents Auswertung. Da kann ich dann schön die Regendauer pro Monat auswerten
             * es werden auch die Regenereignisse gefunden.
    		$endtime=time();
	    	$starttime2=$endtime-60*60*24*3650;   // die letzten 10 Jahre Niederschlag
  		    //$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
    		$werteLog = AC_GetLoggedValues($this->archiveHandlerID, $rainReg, $starttime2, $endtime,0);
            /* Auswertung von Agreggierten Werten funktioniert leider bei inkrementellen Countern nicht */

            if (sizeof($this->werteStore)==0)
                {
                echo "ArchiveOps::getValues($rainReg \n";
                $result = $archiveOps->getValues($rainReg,$config,2);          // true,2 Debug, Werte einlesen
                $this->werteStore = $result["Values"];
                }
            else echo "Bereits Werte im Speicher, insgesamt ".sizeof($this->werteStore)."\n";
            $regen=0;               // Regenmenge während einem Regenereignis
            $lastwert=0;   $zeit=0;
            $ende=true; $maxmenge=0;
			foreach ($this->werteStore as $wert)
				{
                if ($wert["Value"]!=0)              // nur positive Regenmengen akzeptieren, Warnung bei größer 10
                    {
                    if ($wert["Value"]<0) 
                        {
                        echo "   Fehler, negativer Wert: ".$wert["Value"]." vom ".date("D d.m.Y H:i",$wert["TimeStamp"])."\n";
                        }
                    else
                        {
                        if ($wert["Value"]>10) echo "     Warnung, ungewöhnlicher Wert: ".$wert["Value"]." vom ".date("D d.m.Y H:i",$wert["TimeStamp"])."\n";
                        $regen+=$wert["Value"];
                        if ($init===true)               // beim aller-ersten Mal ist es true, wir starten mit i=0
                            {
                            if ($debug>1) echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.Y H:i",$wert["TimeStamp"])."    ";
                            //$regenereignis[$i]["Ende"]=$wert["TimeStamp"];
                            $regenereignisEnde=$wert["TimeStamp"];
                            $regenMenge=0;          // Unterschied false und 0
                            $init=false;    
                            }
                        else 
                            {
                            $zeit= ($lasttime-$wert["TimeStamp"])/60;     // vergangene Zeit zwischen zwei Regenwerten in Minuten messen                    
                            $menge=60/$zeit*$wert["Value"];
                            $regenMenge+=$wert["Value"];
                            if ($menge>$maxmenge) $maxmenge=$menge;
                            if ($debug>1) 
                                {
                                echo number_format($regenMenge,1,",","")."mm | ".number_format($zeit,1,",","")."Min ".number_format($menge,1,",","")."l/Std   ".$regen."\n";
                                echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.Y H:i",$wert["TimeStamp"])."    ";                                            // für die naechste Zeile
                                }
                            }       
                        if ((abs($zeit))>60)                                   // Zeit zwischen Regenwerten, 60 Minuten überschritten, ein regenereignis wurde gefunden
                            {
                            $regenereignis[$i]["Regen"]=$regen;
                            $regenereignis[$i]["Max"]=$maxmenge;
                            if ($zeit>0)
                                {
                                $regenereignis[$i]["Beginn"]=$lasttime;     // letzter Wert ist der Beginnwert
                                $regenereignis[$i]["Ende"]=$regenereignisEnde;
                                $dauer = ($regenereignis[$i]["Ende"]-$regenereignis[$i]["Beginn"])/60;      // in Minuten
                                }
                            else
                                {
                                $regenereignis[$i]["Ende"]=$lasttime;     // letzter Wert ist der Beginnwert
                                $regenereignis[$i]["Beginn"]=$regenereignisEnde;
                                $dauer = ($regenereignis[$i]["Ende"]-$regenereignis[$i]["Beginn"])/60;      // in Minuten
                                }
                            if ( ($debug) && ($regen>1) )           // mehr als 1mm, es gibt viele Einzelwerte
                                {
                                echo "Beginn ".date("d.m.Y H:i:s",$regenereignis[$i]["Beginn"])."  Ende ".date("d.m.Y H:i:s",$regenereignis[$i]["Ende"])." Dauer ".nf($dauer,"min",14)." Regen ".nf($regenereignis[$i]["Regen"],"mm")." Max ".nf($regenereignis[$i]["Max"],"mm")."\n";     
                                }
                            $regen=0; $i++;
                            $regenereignisEnde=$wert["TimeStamp"]; 
                            $maxmenge=0; 
                            }
                        $lastwert=$wert["Value"]; 
                        $lasttime=$wert["TimeStamp"]; 
                        }
                    }                                               // ende Werte != 0
				}                                           // ende foreach werte
            $regenereignis[$i]["Regen"]=$regen;
            if ($zeit>0)
                {
                $regenereignis[$i]["Beginn"]=$lasttime; 
                $regenereignis[$i]["Ende"]=$regenereignisEnde;  
                }
            else
                {
                $regenereignis[$i]["Ende"]=$lasttime; 
                $regenereignis[$i]["Beginn"]=$regenereignisEnde;  
                }
            $regenereignis[$i]["Max"]=$maxmenge;
            if ($debug) echo "\n";
    		}                                       // ende foreach rainregs
        return ($regenMenge);
        }

    /* getRainAmountSince, verwendet this->werteStor mit Increments also Regenmengen Deltas	
     * werteStore kann leer sein, dann wird er neu aufgefüllt	
     * das Regenmengen Increments Register kann mit rainRegs angefragt werden
     * $this->regenStand2h, $this->regenStand48h ermitteln/updaten
     *
     */
    public function getRainAmountSince($debug=false)
        {
        $rainReg = $this->getRainRegisters("Increment");
        if (sizeof($this->werteStore)==0)
            {
            $config = $this->archiveOps->getConfig();
            $configCleanUpData = array();
            $configCleanUpData["deleteSourceOnError"]=false;
            $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
            $config["CleanUpData"] = $configCleanUpData;                   
            if ($debug) echo "ArchiveOps::getValues($rainReg \n";
            $result = $this->archiveOps->getValues($rainReg,$config,$debug);          // true,2 Debug, Werte einlesen, Werte sind auch in der archiveOps class
            $this->werteStore = $result["Values"];
            }
        else {
            if ($debug)
                {
                echo "Werte von ".date("d.m.Y H:i:s",$this->werteStore[array_key_first($this->werteStore)])." bis ".date("d.m.Y H:i:s",$this->werteStore[array_key_last($this->werteStore)])."\n";
                echo "Bereits Werte im Speicher, insgesamt ".sizeof($this->werteStore)."\n";
                }
            }

		$this->regenStand2h=0;
		$this->regenStand48h=0;

        $regenStand=0;			// der erste Regenwerte, also aktueller Stand 
		$vorwert=0; 
		foreach ($this->werteStore as $wert)
			{
			if ($vorwert==0) $regenStand = $wert["Value"];
            else $regenStand += $wert["Value"];
			/* Regenstand innerhalb der letzten 2 Stunden ermitteln */
			if (((time()-$wert["TimeStamp"])/60/60)<2)
				{
				$this->regenStand2h=$regenStand;
				}
			/* Regenstand innerhalb der letzten 48 Stunden ermitteln */
			if (((time()-$wert["TimeStamp"])/60/60)<48)
				{
				$this->regenStand48h=$regenStand;
				}
			}  // Regenwerte der Reihe nach durchgehen

        } 


    /* wie getRainValues nur von den Increments, also nicht Zählerständen
     * übernimmt die gesamte Regenmenge von zuvor und zählt anhand der Increments wieder zurück
     *
     */

    public function getRainValuesFromIncrements($regenMenge,$rainRegs=array(),$debug=false)
        {
        if ($debug) echo "getRainValuesFromIncrements, Regenmenge $regenMenge mm , Mit Rain Register : ".json_encode($rainRegs)." als Input aufgerufen. Rain Register from class ID ".$this->RainRegisterIncrementID." .\n";
        //$debug=false;
        if (($this->RainRegisterIncrementID>0) && (sizeof($rainRegs)==0)) $rainRegs[0]=$this->RainRegisterIncrementID;            
    	$endtime=time();
		$starttime2=$endtime-60*60*24*3650;   /* die letzten 3650 Tage Niederschlag */
        $init=true;
        $regenWerte=array();
        $zeit=date("d.m.y",time()); 
        
        echo "getRainValuesFromIncrements, Regenmenge bisher : $regenMenge mm, diese Register als Input verwenden: ".json_encode($rainRegs)."\n";
        $archiveOps = new archiveOps();
        $archiveHandlerID=$archiveOps->getArchiveID();
        $config = $archiveOps->getConfig();
        $configCleanUpData = array();
        $configCleanUpData["deleteSourceOnError"]=false;
        $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
        $config["CleanUpData"] = $configCleanUpData;    

        foreach ($rainRegs as $rainReg)
            {
            if (sizeof($this->werteStore)==0)         // nur Nachladen wenn noch keine Werte im Speicher
                {            
                $result = $archiveOps->getValues($rainReg,$config,2);          // true,2 Debug, Werte einlesen
                $this->werteStore = $result["Values"];
                }
            else echo "Bereits Werte im Speicher, insgesamt ".sizeof($this->werteStore)."\n";                
            /*
            $werteLog = AC_GetLoggedValues($this->archiveHandlerID, $rainReg, $starttime2, $endtime,0);
            //echo "Die insgesamt ".sizeof($werteLog)." Counter Werte anschauen und auswerten nach Kalendermonaten (12 Monate) und eine 1/7/30/30/360/360 Auswertung:\n";
            /* Regenwert pro Tag ermitteln. Gleiche Werte am selben Tag igorienen, spätester Wert pro Tag bleibt. */ 
            foreach ($this->werteStore as $wert)
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
                        if ($debug>1) echo "***Wert : ".number_format($neuewert, 1, ",", "")."/".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
                        $zeit=$neuezeit; 
                        $lastwert=$neuewert;
                        $regenWerte[$wert["TimeStamp"]]=$neuewert;                 
                        }
                    else                // selber Tag
                        {
                        if ($debug>1) echo "   Wert : ".number_format($neuewert, 1, ",", "")."/".number_format($wert["Value"], 1, ",", "")."mm   ".date("D d.m.y H:i",$wert["TimeStamp"])."    \n";
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

	function listRainEvents($events=10,$variableID=false,$debug=false)
		{
        if ($variableID==false) $variableID = $this->variableID;                // nur für die lokalen Variablen
        if ($this->debug) $debug=$this->debug;
        $days=100;                                          // Anzahl der Events in den letzten 100 Tagen suchen
		$endtime=time();
		$starttime2=$endtime-60*60*24*$days;   /* die letzten x (default 10) Tage Niederschlag*/

		//$Server=$this->getConfig_RemoteAccess_Address();
        $Server                 = $this->GartensteuerungConfiguration["Configuration"]["RemoteAccessAdr"];
		If ($Server=="")
			{
			$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
            if ($debug)
                {
                echo "\n";
                echo "--------Function listRainEvents from loval OID $variableID : ".sizeof($werteLog)." Werte\n";
                }		
        	}
		else
			{
			$rpc = new JSONRPC($Server);
			$this->archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
			if ($this->debug)
				{
                echo "\n";
                echo "--------Function listRainEvents from Server : ".$Server." OID $variableID : ".sizeof($werteLog)." Werte\n";
				}
			}
        $this->regenStatistik = $this->getRainevents($werteLog,$events);
		return ($this->regenStatistik);
		}

    /* GartensteuerungStatistics::listRainDays
     * die letzten Regentage ermitteln, dazu die Regenwerte einlesen
     *
     */
	function listRainDays($days=10,$variableID=false,$debug=false)
		{
        if ($variableID==false) $variableID = $this->variableID;                // nur für die lokalen Variablen
        if ($this->debug) $debug=$this->debug;
		$endtime=time();
		$starttime2=$endtime-60*60*24*$days;   /* die letzten x (default 10) Tage Niederschlag*/

		//$Server=$this->getConfig_RemoteAccess_Address();
        $Server                 = $this->GartensteuerungConfiguration["Configuration"]["RemoteAccessAdr"];
		If ($Server=="")
			{
			$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = AC_GetLoggedValues($archiveHandlerID, $variableID, $starttime2, $endtime,0);
    		if ($debug) echo"--------Function List RainDays:   $days  $variableID (".IPS_GetName($variableID).") : ".sizeof($werteLog)." Dateneinträge\n";
			}
		else
			{
			$rpc = new JSONRPC($Server);
			$this->archiveHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
			$werteLog = $rpc->AC_GetLoggedValues($archiveHandlerID, $this->variableID, $starttime2, $endtime,0);
    		if ($debug) echo"--------Function List RainDays:   $days  $Server ".$this->variableID." (".IPS_GetName($variableID).") : ".sizeof($werteLog)." Dateneinträge\n";
			}
        $regenStatistik = $this->getRainevents($werteLog);
		return ($regenStatistik);
		}

    /* GartensteuerungStatistics::getRainevents
     *
     * Routinen nur mehr an einem Ort. liefert Regenstatistik und ändert einen class parameter: letzterRegen
     * benötigt ein inkrementelles Regenregister, Counter mit steigenden Werten, array mit [Value,TimeStamp], der erste Wert ist der höchste Wert
     * wird von listRainDays aufgerufen, das die Daten bereit stellt
     *
     * Rain2h oder 48h extra berechnen.
     *
     * return Wert (regenStatistik)
     *  Index ist regenAnfangZeit, kann auch i sein
     *      Beginn
     *      Ende
     *      Regen
	 *      Max
     * in der class werden upgedated:
     *      letzterRegen
     *
     */

    protected function getRainevents($werteLog,$events=0)
        {
		/* Letzen Regen ermitteln, alle Einträge der letzten x (default 10) Tage Niederschlag durchgehen */
        $debug=$this->debug;
        //$debug=true;                  // Debug override
        if ($debug) echo "\ngetRainevents mit ".count($werteLog)." Werten aufgerufen:\n";
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
				if ($debug) {	echo "   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				$regenStand=$wert["Value"];
				}
			else 
				{
				/* die erste Zeile erst mit dem zweiten Eintrag auffuellen ... */
				$regenMenge=round(($vorwert-$wert["Value"]),1);
				$regenDauer=round(($vorzeit-$wert["TimeStamp"])/60,0);
				if ($debug) 
					{
					if ( $regenDauer>(60*24*2) ) echo " ".$regenMenge."mm/>2Tage  "; 
					else echo str_pad(" ".$regenMenge."mm/".$regenDauer."min  ",20); 
					}

                if ($regenDauer==0) 
                    {
                    //if ($debug) echo "Fehler, Regendauer $vorzeit - ".$wert["TimeStamp"]." ist ".($vorzeit-$wert["TimeStamp"])." Sekunden. Aufrunden auf eine Minute. \n";
                    $regenDauer=1;
                    }
                if (($regenMenge/$regenDauer*60)>$regenMaxStd) {$regenMaxStd=$regenMenge/$regenDauer*60;} // maximalen Niederschlag pro Stunde ermitteln

				if ( ($regenMenge<0.4) and ($regenDauer>60) ) 
					{
					/* gilt nicht als Regen, ist uns zu wenig, mehr ein nieseln */
					if ($debug) echo "  kurzes Nieseln ";
					if ($regenEndeZeit != 0)
						{ 
						/* gilt auch als Regenanfang wenn ein Regenende erkannt wurde*/
						$regenAnfangZeit=$vorzeit;
						if ($debug) 
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
						if ($debug) { echo "* "; }
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
						if ($debug) { echo str_pad($regenMengeAcc."mm ".$regenDauerAcc."min ",20); }
						if ($regenEndeZeit==0)
							{
							$regenStandEnde=$vorwert;
							$regenEndeZeit=$vorzeit;
							if ($debug) { echo "  Regenende : ".date("d.m H:i",$regenEndeZeit)."   ".round($regenStandEnde,1)."  ";	}
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
							if ($debug) 
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
							if ($debug) { echo "* "; }
							}	
						} 								
					}
                If ( ($this->letzterRegen==0) && (round($wert["Value"]) > 0) )
                    {
                    $this->letzterRegen=$wert["TimeStamp"];
                    $regenStandEnde=$wert["Value"];
                    if ($debug) { echo "Letzter Regen !"; }
                    }				

				if ($debug) { echo "\n   Wert : ".number_format($wert["Value"], 1, ",", "")."mm   ".date("d.m H:i",$wert["TimeStamp"])." "; }
				}
			$vorwert=$wert["Value"];	
			$vorzeit=$wert["TimeStamp"];
			}
		return ($regenStatistik);            
        }

	/* GartensteuerungStatistics::writeRainEventsHtml
     * Ausgabe der Regenereignisse ermittelt mit listRainEvents und darin aufgerufen getRainevents) 
     * mit Beginn, Ende, Dauer etc als html Tabelle zum speichern in einer hml Box
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

    /* GartensteuerungStatistics::writeOverviewIntervalHtml depricated besser ipsTables verwenden
     * soll Tage und Monate darstellen
     * Ausgabe der Regenmenge/dauer (ermittelt mit listrainevents) als html Tabelle Jahre versus Monate zum speichern/anzeigen in einer hml Box
     * Input ist ein Array mit Index MM.JJ oder Index und Value/Timestamp
     *
     * verschieden Datenquellen möglich, unterscheidung mit Mode/Config
     *
     * mode=1   Index ist bereits MM.YY, Wert ist ein scalar und kann direkt verwendet werden
     * mode=2   Index ist ein aufsteigender Wert, Wert ist ein array mit Value/TimeStamp
     * mode=3   Darstellung Tage über Werte
     * type     sum
     *
	 */
	public function writeOverviewIntervalHtml($Values=array(),$mode=1,$debug=false)
		{
        // umgestellt auf intern 4stellige Jahreszahlen
        $startjahr=(integer)date("Y",time());
        $minjahr=$startjahr;
        //print_r($Values);
        $type="sum";                   // default is mode=1 und type=sum
        $table="months";
        $dv=10000;    // sum10k 
        $summe=array();
        if (is_array($mode))
            {
            if (isset($mode["type"])) $type = $mode["type"];
            else $type="sum";
            if (isset($mode["mode"])) $mode = $mode["mode"];
            else $mode=1;
            if (isset($mode["table"])) $table = $mode["table"];
            else $table="months";
            }
        if ($debug) 
            {
            echo "\n";
            echo "writeOverviewMonthsHtml Table $table Mode $mode Type $type \n";
            }
        if ($table=="months")
            {
            if ($mode==1)           // Index ist bereits MM.YY
                {
                foreach ($Values as $date=>$regenmenge)
                    {
                    $MonatJahr=explode(".",$date);
                    $monat=(integer)$MonatJahr[0]; $jahr=(integer)$MonatJahr[1];
                    if ($jahr<100) $jahr += 2000;
                    if ($jahr<$minjahr) $minjahr=$jahr;
                    $tabelle[$monat][$jahr]=$regenmenge;

                    if (isset($summe[$jahr])) 
                        {
                        $summe[$jahr]["value"] += $regenmenge;
                        $summe[$jahr]["count"]++;
                        }
                    else 
                        {
                        $summe[$jahr]["value"] = $regenmenge;
                        $summe[$jahr]["count"] = 1;
                        }
                    }
                }
            elseif ($mode==2)                // Index ist ein aufsteigender Wert, Wert ist ein array mit Value/TimeStamp
                {
                foreach ($Values as $index=>$entry)
                    {
                    $date=date("m.Y",$entry["TimeStamp"]);
                    $regenmenge=$entry["Value"];
                    $MonatJahr=explode(".",$date);              // index 0 ist Monat, 1 ist Jahr
                    $monat=(integer)$MonatJahr[0]; $jahr=(integer)$MonatJahr[1];
                    if ($jahr<$minjahr) $minjahr=$jahr;
                    $tabelle[$monat][$jahr]=$regenmenge;                
                    
                    if (isset($summe[$jahr])) 
                        {
                        $summe[$jahr]["value"] += $regenmenge;
                        $summe[$jahr]["count"]++;
                        }
                    else 
                        {
                        $summe[$jahr]["value"] = $regenmenge;
                        $summe[$jahr]["count"] = 1;
                        }
                    }
                }
            else            // Index ist ein aufsteigender Wert, aber es gibt noch einen Kanal identifier danach
                {
                foreach ($Values as $index=>$entry)
                    {
                    foreach ($entry as $channel=>$subentry)
                        {
                        $date=date("m.Y",$subentry["TimeStamp"]);
                        $regenmenge=$subentry["Value"];
                        $MonatJahr=explode(".",$date);              // index 0 ist Monat, 1 ist Jahr
                        $monat=(integer)$MonatJahr[0]; $jahr=(integer)$MonatJahr[1];
                        if ($jahr<$minjahr) $minjahr=$jahr;
                        $tabelle[$monat][$jahr]=$regenmenge;                
                        
                        if (isset($summe[$jahr])) 
                            {
                            $summe[$jahr]["value"] += $regenmenge;
                            $summe[$jahr]["count"]++;
                            }
                        else 
                            {
                            $summe[$jahr]["value"] = $regenmenge;
                            $summe[$jahr]["count"] = 1;
                            }
                        }
                    }
                }
            if ($debug) print_R($tabelle);
            $html="";
            $html.='<table frameborder="1" width="100%">';
            $html.="<th> <tr> <td> Monat </td> ";
            for ($i=$startjahr;$i>=$minjahr;$i--) {$html.="<td> ".$i." </td>";}
            $html.= " </tr> </th>";
            for ($j=1;$j<=12;$j++)          // Monate sind die zeilen
                {
                $html.="<tr> <td>".$j."</td>";
                for ($i=$startjahr;$i>=$minjahr;$i--) 
                    {
                    if (isset($tabelle[$j][$i])==true) 
                        {
                        if ($type=="sum10k") $html.="<td> ".number_format($tabelle[$j][$i]/$dv,1,",","")." </td>";
                        else $html.="<td> ".number_format($tabelle[$j][$i],1,",","")." </td>";
                        }
                    else $html.="<td> </td>";
                    }
                $html.= " </tr> ";
                }
            // Summe/Mean
            $html.="<tr> <td>Summe</td>";
            for ($i=$startjahr;$i>=$minjahr;$i--) 
                {
                if (isset($summe[$i])==true)
                    {
                    if ($type=="sum")        $html.="<td> ".number_format($summe[$i]["value"],1,",","")." </td>";
                    elseif ($type=="sum10k") $html.="<td> ".number_format($summe[$i]["value"]/$dv,0,",","")." </td>";
                    else                     $html.="<td> ".number_format(($summe[$i]["value"]/$summe[$i]["count"]),1,",","")." </td>";
                    }
                else $html.="<td> </td>";
                }
            $html.= " </tr> ";

            $html.="</table>";
            }
        else
            {
            /* Ausgabe der Tabelle nach Spalten zweiter index und Zeilen erster Index
             * hier werden die ersten 30 Tage zurückgerechnet ab heute übernommen, Routine beginnt ab Beginn rückwärts zu zählen  
             */
            foreach ($Values as $index=>$entry)
                    {
                    $date=date("m.Y",$entry["TimeStamp"]);
                    $regenmenge=$entry["Value"];
                    $MonatJahr=explode(".",$date);              // index 0 ist Monat, 1 ist Jahr
                    $monat=(integer)$MonatJahr[0]; $jahr=(integer)$MonatJahr[1];
                    if ($jahr<$minjahr) $minjahr=$jahr;
                    $tabelle[$monat][$jahr]=$regenmenge;                
                    
                    if (isset($summe[$jahr])) 
                        {
                        $summe[$jahr]["value"] += $regenmenge;
                        $summe[$jahr]["count"]++;
                        }
                    else 
                        {
                        $summe[$jahr]["value"] = $regenmenge;
                        $summe[$jahr]["count"] = 1;
                        }
                    }
      
            }
        return($html);
        }   

    }
	
?>