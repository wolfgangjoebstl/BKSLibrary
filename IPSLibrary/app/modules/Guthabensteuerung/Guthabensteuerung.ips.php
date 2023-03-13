<?


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

    /* Guthabensteuerung
     *
     * soll das verbleibende Guthaben von SIM Karten herausfinden
     * unterschiedliche Strategien, Betriebsarten: Aufruf mit
     *
     *      iMacro          leider nur mehr mittels Lizenzzahlung unterstützt, daher Fokus jetzt auf Selenium
     *      Selenium
     *
     * Dieses Script macht alle drei Anwendungsmöglichkeiten
     *      Timer
     *      Webfront, Tastendruck
     *      Execute
     *
     * Der Timer wird um 2:27 aufgerufen und wird ausschliesslich für die Erfassung der Drei Guthaben verwendet. Es gibt es eine Verlängerungsmöglichkeit mit Aufruf alle 150 Sekunden
     * Bei den anderen Abfrage (Morgen, Abend ...) wird der Drei Host definitiv ausgeschlossen. 
     * Dann wird nur $seleniumOperations->automatedQuery($webDriverName,$configTabs["Hosts"]... aufgerufen
     * Zusätzlich gibt es einen Tasktimer, der nach dem Start alle 310 Sekundn aktiv wird
     *
     * Aktuell implementiert:  Drei, Easy, LogWien, YahooFin
     *
     * Webfront übernimmt den Tastendruck
     *
     */



    //Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
    IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(100);                              // kein Abbruch vor dieser Zeit, funktioniert nicht für linux basierte Systeme

    $doQuery=false;                             // Abfrage mit Selenium Host starten
    $startexec=microtime(true);    
    //echo "Abgelaufene Zeit : ".exectime($startexec)." Sek. Max Scripttime is 100 Sek \n";         //keine Ausgabe da auch vom Webfront aufgerufen 

    /******************************************************

                    INIT

    *************************************************************/

    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    If (!isset($moduleManager)) {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
    }
    $installedModules   = $moduleManager->GetInstalledModules();
    $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
    $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $ipsOps = new ipsOps();    
    $timerOps = new timerOps();

    $systemDir     = $dosOps->getWorkDirectory();     

    $NachrichtenID      = $ipsOps->searchIDbyName("Nachricht",$CategoryIdData);
    $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);
    /* logging in einem File und in einem String am Webfront */
    //$log_Guthabensteuerung=new Logging("C:\Scripts\Guthabensteuerung\Log_Guthaben.csv",$NachrichtenInputID);
    $log_Guthabensteuerung=new Logging($systemDir."Guthabensteuerung/Log_Guthaben.csv",$NachrichtenInputID);


    $guthabenHandler = new GuthabenHandler(true,true,true);         // true,true,true Steuerung für parsetxtfile
	$GuthabenConfig         = $guthabenHandler->getContractsConfiguration();            // get_GuthabenConfiguration();
	$GuthabenAllgConfig     = $guthabenHandler->getGuthabenConfiguration();                              //get_GuthabenAllgemeinConfig();

    /* ScriptIDs finden für Timer */
    $ParseGuthabenID		= IPS_GetScriptIDByName('ParseDreiGuthaben',$CategoryIdApp);
    $GuthabensteuerungID	= IPS_GetScriptIDByName('Guthabensteuerung',$CategoryIdApp);

    /*
   	$categoryId_Guthaben        = CreateCategory('Guthaben',        $CategoryIdData, 10);
    $categoryId_GuthabenArchive = CreateCategory('GuthabenArchive', $CategoryIdData, 900);
    */
    $categoryId_Guthaben        = $CategoryIdData;

    /* Vorbereitungen abhängig von der Betriebsart */
    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {
        case "IMACRO":
            $CategoryId_Mode        = CreateCategory('iMacro',          $CategoryIdData, 90);
            $startImacroID          = IPS_GetObjectIdByName("StartImacro",$CategoryId_Mode);
            $firefox=$GuthabenAllgConfig["FirefoxDirectory"]."firefox.exe";
            //echo "Firefox verzeichnis : ".$firefox."\n";				
            /*  $firefox=ADR_Programs."Mozilla Firefox/firefox.exe";
                echo "Firefox Verzeichnis (old style aus AllgemeineDefinitionen): ".$firefox."\n";  */
            break;
        case "SELENIUM":
            IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
            //echo "Do Init for Operating Mode Selenium.\n";
            $seleniumOperations = new SeleniumOperations();        
            $CategoryId_Mode        = CreateCategory('Selenium',        $CategoryIdData, 20);
            $startImacroID          = IPS_GetObjectIdByName("StartSelenium", $CategoryId_Mode);	
            $sessionID      = $guthabenHandler->getSeleniumSessionID();
            $config=array(
                        "DREI"       =>  array (
                            "URL"       => "www.drei.at",
                            "CLASS"     => "SeleniumDrei",
                            "CONFIG"    => array(
                                        "WebResultDirectory"    => $GuthabenAllgConfig["WebResultDirectory"],
                                                    ),              
                                            ),
                        );
            $webDriverName=false;
            if (isset($installedModules["Watchdog"]))
                {
                IPSUtils_Include ("Watchdog_Configuration.inc.php","IPSLibrary::config::modules::Watchdog");
                IPSUtils_Include ("Watchdog_Library.inc.php","IPSLibrary::app::modules::Watchdog");
                $watchDog = new watchDogAutoStart();
                $processes    = $watchDog->getActiveProcesses();
                $processStart = $watchDog->checkAutostartProgram($processes);
                $SeleniumOnID           = IPS_GetObjectIdByName("SeleniumRunning", $CategoryId_Mode);
                if (isset($processStart["selenium"])) 
                    {
                    $date=date("d.m.Y H:i:s");
                    if ($processStart["selenium"]=="off") SetValue($SeleniumOnID,"Active since $date");
                    else SetValue($SeleniumOnID,"Idle since $date");
                    }
                }
            else $SeleniumOnID=false;
            break;
        case "NONE":
            $DoInstall=false;
            break;
        default:    
            echo "Guthaben Mode \"".$GuthabenAllgConfig["OperatingMode"]."\" not supported.\n";
            break;
        }

    // Wenn Selenium Webfront aufgebaut wird
    $statusReadID       = CreateVariable("StatusWebread", 3, $CategoryId_Mode,1010,"~HTMLBox",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    //$testInputID        = CreateVariable("TestInput", 3, $CategoryId_iMacro,1020,"",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    $startActionID      = IPS_GetObjectIdByName("StartAction", $CategoryId_Mode);	
    $startActionGroupID = IPS_GetObjectIdByName("StartGroupCall", $CategoryId_Mode);

    // Wenn YahooApi aufgebaut wird
    if (isset($GuthabenAllgConfig["Api"]))
        {
        $CategoryId_Finance  = IPS_GetObjectIdByName('Finance',$CategoryIdData);                    // Kategorie, wenn Kategorie nicht gefunden wird ist diese Variable und alle abhängigen davon danach false 
    
        $updateApiTableID    = @IPS_GetObjectIdByName("Update",$CategoryId_Finance);           // button 
        $calculateApiTableID = @IPS_GetObjectIdByName("Calculate",$CategoryId_Finance);           // button
        $sortApiTableID      = @IPS_GetObjectIdByName("Sort",$CategoryId_Finance);           // button 

        $financeTableID       =   @IPS_GetVariableIDByName("YahooFinanceTable", $CategoryId_Finance);		// wenn false nicht gefunden
        }
        
    $ScriptCounterID      = CreateVariableByName($CategoryIdData,"ScriptCounter",1);                    // wir zählen die erfolgreichen Aufrufe von Timer2
    $checkScriptCounterID = CreateVariableByName($CategoryIdData,"checkScriptCounter",1);               // wir zählen alle Aufrufe von Timer2
    $ScriptTimerID        = CreateVariableByName($CategoryIdData,"ScriptTimer",3);                      // ein Name für ein Script das vom Timer5 aufgerufen wird

	$archiveHandlerID     = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	/*****************************************************
	 *
	 * initialize Timer 
	 *
	 ******************************************************************/

    $tim1ID = IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
    if ($tim1ID==false) echo "Fehler Timer Aufruftimer nicht definiert.\n";
    $tim12ID = IPS_GetEventIDByName("Lunchtimer", $_IPS['SELF']);
    if ($tim12ID==false) echo "Fehler Timer Lunchtimer nicht definiert.\n";
    $tim3ID = @IPS_GetEventIDByName("EveningCallTimer", $GuthabensteuerungID);
    if ($tim3ID==false) echo "Fehler Timer EveningCallTimer nicht definiert.\n";

    $tim2ID = $timerOps->CreateTimerSync("Exectimer",150,$_IPS['SELF']);

    /* $tim2ID = @IPS_GetEventIDByName("Exectimer", $_IPS['SELF']);
    if ($tim2ID==false)
        {
        $tim2ID = IPS_CreateEvent(1);
        IPS_SetParent($tim2ID, $_IPS['SELF']);
        IPS_SetName($tim2ID, "Exectimer");
        IPS_SetEventCyclic($tim2ID,2,1,0,0,1,150);      // alle 150 sec 
        //IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  // immer um 02:10 
        } */

    $tim4ID = $timerOps->CreateTimerHour("AufrufMorgens",4,55,$_IPS['SELF']);
    $tim5ID = $timerOps->CreateTimerSync("Tasktimer",310,$_IPS['SELF']);        // Tasks wie YahooFin ein wenig entkoppeln

    $phoneID=$guthabenHandler->getPhoneNumberConfiguration();
    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {
        case "IMACRO":
            break;
        case "SELENIUM":        
            $guthabenHandler->extendPhoneNumberConfiguration($phoneID,$seleniumOperations->getCategory("DREI"));            // phoneID is return parameter, erweitert um  [LastUpdated] und [OID]   
            break;
        }

    $maxcount=count($phoneID);


/******************************************************
 *
 *				TIMER
 *
 * mehrere TimerEvents. Zwei einmal am Tag (morgens und Abends) und das andere alle 150 Sekunden
 *
 * tim1 ist der Aufruftimer um 2:27 Uhr morgens. Setzt die beiden Counter Register zurück und startet den Ausführtimer tim2 
 *
 * tim2 ist ausgelegt alle 150 Sekunden aufgerufen zu werden bis die Aufgabe erledigt ist
 *      ScriptCounter muss maxcount erreichen
 *      wenn der Scriptcounter nicht maxcount erreicht wird nach der doppelten Anzahl an Versuchen abgebrochen, ein Eintrag ins Nachrichten log erstellt und der Timer deaktiviert
 *      es wir für alle Rufnummern einzeln die Drei Guthaben abfrage gestartet, entweder als Selenium oder wenn noch funktioniert als imacro
 *      danach wird ParseDreiGuthaben aufgerufen
 *      und bei iMacro geprüft ob die ergebnisdateien jetzt da sind
 *
 * tim3 ist alternativ am Abend um 22:16 für Selenium Operationen
 *      DREI wird immer morgens gemacht, mehrmalige Aufrufe je Nummer, daher Abends rausnehmen, wird dezidiert aus der Config gelöscht,
 *      am Abend die anderen Hosts abfragen, wenn evening oder morning dann nur die jeweilige Abfrage machen
 *
 * tim4, neu ruft morgens um 4:55 ein paar Funktionen auf
 *      jetzt auch für Selenium Morning Funktionen verwendbar
 *
 * tim5, Timer wie tim2, ausgelegt alle 310 Sekunden aufgerufen zu werden, soll yahooFin unterstützen
 *
 *************************************************************/

$configTabs = false;            // Vereinheitlichen mit einer gemeinsamen Variable

if ($_IPS['SENDER']=="TimerEvent")
	{
	//IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']);

	switch ($_IPS['EVENT'])
		{
		case $tim1ID:               // Aufruftimer immer um 2:27
			IPS_SetEventActive($tim2ID,true);
			SetValue($statusReadID,"");			// Beim Erstaufruf Html Log loeschen.
			SetValue($checkScriptCounterID,0);  // mit 0 wieder beginnen, neuer Tag neues Glück
			SetValue($ScriptCounterID,0);       // mit 0 wieder beginnen, wenn die Abfrage fehlerhaft ist wird dieser Wert nicht inkrementiert    
			break;
		case $tim2ID:               // Exectimer alle 150 Sekunden wenn aktivivert, für DREI reserviert
			//IPSLogger_Dbg(__file__, "TimerExecEvent from :".$_IPS['EVENT']." ScriptcountID:".GetValue($ScriptCounterID)." von ".$maxcount);
			$ScriptCounter=GetValue($ScriptCounterID);
			$checkScriptCounter=GetValue($checkScriptCounterID);
            //$log_Guthabensteuerung->LogNachrichten("Script Counter $ScriptCounter, Check Script Counter $checkScriptCounter > ".($maxcount*2));

            if ($checkScriptCounter>($maxcount*2)) 
                {
                $log_Guthabensteuerung->LogNachrichten("Guthabensteuerung tim2, CheckScriptCounter $checkScriptCounter exceeded Maxcount of ".($maxcount*2));  
				IPS_SetEventActive($tim2ID,false);
                break;                  // 100% Fehler durch Abbrüche zulassen, dann Funktion einstellen
                }
			SetValue($checkScriptCounterID,$checkScriptCounter+1);

			//IPS_SetScriptTimer($_IPS['SELF'], 150);
            $note="";            
			if ($ScriptCounter < $maxcount)                                     // normale Abfrage, Nummer für Nummer bis fertig
				{
                switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                    {
                    case "IMACRO":                    
                        // keine Anführungszeichen verwenden
                        IPS_ExecuteEX($firefox, "imacros://run/?m=dreiat_".$phoneID[$ScriptCounter]["Nummer"].".iim", false, false, -1);
                        if ($ScriptCounter>0) 
                            {
                            $fileName=$GuthabenAllgConfig["DownloadDirectory"]."report_dreiat_".$phoneID[($ScriptCounter-1)]["Nummer"].".txt";
                            if (is_file($fileName))
                                {
                                $filedate=date ("d.m.Y H:i:s.", filemtime($fileName) );
                                $note="iMacro letzte Abfrage war um ".date("d.m.Y H:i:s")." für dreiat_".$phoneID[($ScriptCounter)]["Nummer"].".iim. Letztes Ergebnis für ".$phoneID[($ScriptCounter-1)]["Nummer"]." mit Datum ".$filedate." ";
                                }
                            }
                        else 	$note="iMacro letzte Abfrage war um ".date("d.m.Y H:i:s")." für dreiat_".$phoneID[($ScriptCounter)]["Nummer"].".iim.";
                        $log_Guthabensteuerung->LogNachrichten($note);
                        SetValue($statusReadID,GetValue($statusReadID)."<br>".$note);	
                        break;
                     case "SELENIUM":
                        $config["DREI"]["CONFIG"]["Username"]=$phoneID[$ScriptCounter]["Nummer"];          // von 0 bis maxcount-1 durchgehen
                        $config["DREI"]["CONFIG"]["Password"]=$phoneID[$ScriptCounter]["Password"];
                        $seleniumOperations->automatedQuery($webDriverName,$config);          // true debug    für DREI only  
                        $note="Selenium Abfrage war um ".date("d.m.Y H:i:s")." für ".$phoneID[($ScriptCounter)]["Nummer"]."($ScriptCounter/$maxcount,".exectime($startexec)." Sek)";
                        $log_Guthabensteuerung->LogNachrichten($note);
                        SetValue($statusReadID,GetValue($statusReadID)."<br>".$note);	                           


                        break;
                    default:
                        break;
                    }
			    SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1);                	
				}
			else            // alles abgefragt, was ist mit der Auswertung, immer noch eigenes Script ParseGuthaben
				{
                $log_Guthabensteuerung->LogNachrichten("ParseDreiGuthaben $ParseGuthabenID called from Guthabensteuerung.");  
				IPS_RunScript($ParseGuthabenID);            // ParseDreiGuthaben wird aufgerufen
                switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                    {
                    case "IMACRO":
                        $fileName=$GuthabenAllgConfig["DownloadDirectory"]."report_dreiat_".$phoneID[($ScriptCounter-1)]["Nummer"].".txt";
                        if (file_exists($fileName)==true)
                            {
                            $fileMTimeDatei=filemtime($fileName);   // Liefert Datum und Uhrzeit der letzten Dateiänderung
                            $filedate=date ("d.m.Y H:i:s.", $fileMTimeDatei);
                            $note="Parse Files war um ".date("d.m.Y H:i:s").". Letztes Ergebnis für ".$phoneID[($ScriptCounter-1)]["Nummer"]." mit Datum ".$filedate." ";
                            }
                        else $note="File ".$fileName." does not exists.";
                        break;
                    case "SELENIUM":
                        //$seleniumOperations->automatedQuery($webDriverName,$config);          // true debug       
                        break;
                    default:
                        break;                        
                    }
                SetValue($statusReadID,GetValue($statusReadID)."<br>".$note);		
				SetValue($ScriptCounterID,0);
				//IPS_SetScriptTimer($_IPS['SELF'], 0);
				IPS_SetEventActive($tim2ID,false);
				}
			break;
		case $tim12ID:               // immer zu Mittag um 13:xx, heisst LunchTime
            switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                {
                case "SELENIUM":
                    $configTabs = $guthabenHandler->getSeleniumHostsConfig("lunchtime");              // Filter lunchtime, immer klein geschrieben
                    break;
                }
            break;
		case $tim3ID:               // immer am späten Abend um 22:16, auch wenn er Evening heisst
            switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                {
                case "SELENIUM":
                    $configTabs = $guthabenHandler->getSeleniumHostsConfig("evening");              // Filter evening
                    break;
                }
            break;
		case $tim4ID:               // immer am frühen morgen um 4:55, macht das Selbe wie am Späten Abend
            switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                {
                case "SELENIUM":
                    $configTabs = $guthabenHandler->getSeleniumHostsConfig("morning");                              // Filter morning
                    break;
                }
            break;
		case $tim5ID:               // Tasktimer alle 310 Sekunden wenn aktiviert
            // Abfrage nach angeforderter Aktion, Befehl wird so übergeben
            $startexec=microtime(true);
            $configTabs = $guthabenHandler->getSeleniumHostsConfig();
            $reguestedAction=GetValue($ScriptTimerID);
            $log_Guthabensteuerung->LogNachrichten("Timer5 called from Webfront. Requested Action $reguestedAction.");  
            switch ($reguestedAction)
                {
                case "EASY":
                    $seleniumEasycharts = new SeleniumEasycharts();
                    $configTemp["EASY"] = $configTabs["Hosts"]["EASY"];
                    $seleniumOperations->automatedQuery($webDriverName,$configTemp,false);          // true debug
                    $log_Guthabensteuerung->LogNachrichten("Manually requested Selenium Hosts Query for \"$reguestedAction\", Exectime : ".exectime($startexec)." Sekunden");
                    $configTabs = $guthabenHandler->getSeleniumTabsConfig($reguestedAction);
                    $depotRegister=["RESULT"];
                    if (isset($configTabs["Depot"]))
                        {
                        if (is_array($configTabs["Depot"]))
                            {
                            $depotRegister=$configTabs["Depot"];    
                            }
                        else $depotRegister=[$configTabs["Depot"]];
                        }
                    foreach ($depotRegister as $depot)
                        {
                        $result=$seleniumOperations->readResult("EASY",$depot,true);                  // true Debug   
                        $lines = explode("\n",$result["Value"]);    
                        $data=$seleniumEasycharts->parseResult($lines,false);             // einlesen, true debug
                        $shares=$seleniumEasycharts->evaluateResult($data);
                        $depotName=str_replace(" ","",$depot);                      // Blanks weg
                        if ($depotName != $depot)               
                            {
                            $value=$seleniumEasycharts->evaluateValue($shares);         // Summe ausrechnen
                            $seleniumEasycharts->writeResult($shares,"Depot".$depotName,$value);                         // die ermittelten Werte abspeichern, shares Array etwas erweitern                                
                            }
                        else
                            {
                            $seleniumEasycharts->writeResult($shares,"Depot".$depotName);                         // die ermittelten Werte abspeichern, shares Array etwas erweitern
                            }
                        $seleniumEasycharts->updateResultConfigurationSplit($shares);                // Die wunderschöne split Konfiguration wird hier wieder zunichte gemacht da sie aus der Konfiguration für das Depot , daher aus dem Konfig auslesen       
                        $seleniumEasycharts->writeResultConfiguration($shares, $depotName);                                    
                        }
                    break;
                case "YAHOOFIN":
                    $configTemp["YAHOOFIN"] = $configTabs["Hosts"]["YAHOOFIN"];
                    $configTemp["Logging"]=$log_Guthabensteuerung;
                    $seleniumOperations->automatedQuery($webDriverName,$configTemp,false);          // true debug
                    $log_Guthabensteuerung->LogNachrichten("Manually requested Selenium Hosts Query for \"$reguestedAction\", Exectime : ".exectime($startexec)." Sekunden");
                    // Auswertung
                    $result=$seleniumOperations->readResult("YAHOOFIN");                  // true Debug   , lest RESULT als Egebnis Variable, wenn zweite Variable ausgefüllt ist es das entsprechende register
                    $log_Guthabensteuerung->LogNachrichten("Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"]));       
                    $yahoofin = new SeleniumYahooFin();
                    $ergebnis = $yahoofin->parseResult($result);                        // eigentlich nur json_decode auf ein array
                    //echo "Ergebnis: ".json_encode($ergebnis)."  \n";
                    foreach ($ergebnis as $index => $entry)
                        {
                        if (isset($entry["Target"])) $log_Guthabensteuerung->LogNachrichten("$index ".$entry["Short"]."    ".$entry["Target"]);
                        }
                    $yahoofin->writeResult($ergebnis,"TargetValue",true);           // echte YahooFin writeresult, sonst nur bei Webfront nur Standard
                    break;
                case "LOGWIEN":
                    $configTemp["LOGWIEN"] = $configTabs["Hosts"]["LogWien"];
                    $configTemp["Logging"]=$log_Guthabensteuerung;
                    $seleniumOperations->automatedQuery($webDriverName,$configTemp,false);          // true debug
                    $log_Guthabensteuerung->LogNachrichten("Manually requested Selenium Hosts Query for \"$reguestedAction\", Exectime : ".exectime($startexec)." Sekunden");
                    // Auswertung
                    $result=$seleniumOperations->readResult("LogWien","Result",true);                  // true Debug
                    //print_R($result);
                    echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";
                    $log_Guthabensteuerung->LogNachrichten("Parse Log.Wien Ergebnis from ".date("d.m.Y H:i:s",$result["LastChanged"]).".");  
                    echo "--------\n";
                    //$checkArchive=$archiveOps->getComponentValues($oid,20,false);                 // true mit Debug
                    $seleniumLogWien = new SeleniumLogWien();
                    $seleniumLogWien->writeEnergyValue($result["Value"],"EnergyCounter");                    
                    break;                    
                case "morning":
                case "lunchtime":
                case "evening":
                case "MORNING":
                case "LUNCHTIME":
                case "EVENING":
                    $configTabs = $guthabenHandler->getSeleniumHostsConfig($reguestedAction);                              // Filter morning
                    break;
                default:
                    break;
                }
            $configTabs=false;        
            IPS_SetEventActive($tim5ID,false);
            break;      // Ende Timer5
		default:                    // kein bekannter Timer
			break;
		}
	}

if ($configTabs)
    {
    $startexec=microtime(true);
    unset($configTabs["Hosts"]["DREI"]);                                                // DREI ist nur default, daher löschen
    $seleniumOperations->automatedQuery($webDriverName,$configTabs["Hosts"],true);          // true debug
    echo "Aktuell vergangene Zeit für AutomatedQuery: ".exectime($startexec)." Sekunden\n";
    echo "--------\n";
    $log_Guthabensteuerung->LogNachrichten("ParseDreiGuthaben $ParseGuthabenID called from Guthabensteuerung.");  
    IPS_RunScript($ParseGuthabenID);            // ParseDreiGuthaben wird aufgerufen
    $log_Guthabensteuerung->LogNachrichten("Automated Selenium Hosts Query, Exectime : ".exectime($startexec)." Sekunden"); 
    }


/******************************************************
 *
 *				Webfront
 *
 * es gibt verschiedene Taster die hier zusammengeführt werden.                
 *
 *
 *************************************************************/

$reguestedAction    = false;               // Vereinheitlichung der Actions
$reguestedActionApi = false;               // Vereinheitlichung der Actions

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	$value=$_IPS['VALUE']; $variable=$_IPS['VARIABLE'];
    if ($variable)
        {
        switch ($variable)
            {
            case ($startImacroID):
                //echo "Taste Macro gedrückt";
                switch ($value)
                    {
                    case $maxcount:			// Alle
                        IPS_SetEventActive($tim2ID,true);
                        SetValue($ScriptCounterID,0);
                        SetValue($statusReadID,"Abfrage für Alle um ".date("d.m.Y H:i:s")." gestartet.");			// Beim Erstaufruf Html Log loeschen.					
                        //echo "Ja Alle";
                        break;
                    case ($maxcount+1):		// Test, Sepzialroutine, sozusagen On Demand
                        switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                            {    
                            case "IMACRO":
                                $handle2=fopen($GuthabenAllgConfig["MacroDirectory"]."dreiat_test.iim","w");
                                fwrite($handle2,'VERSION BUILD=8970419 RECORDER=FX'."\n");
                                fwrite($handle2,'TAB T=1'."\n");
                                fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
                                fwrite($handle2,'SET !EXTRACT NULL'."\n");
                                fwrite($handle2,'SET !VAR0 '.$phoneID[6]["Nummer"]."\n");
                                fwrite($handle2,'ADD !EXTRACT {{!VAR0}}'."\n");
                                if (false)
                                    {
                                    //fwrite($handle2,'URL GOTO=http://www.drei.at/'."\n");
                                    fwrite($handle2,'URL GOTO=https://www.drei.at/selfcare/restricted/prepareMyProfile.do'."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:Kundenzone'."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=ID:loginForm ATTR=ID:userName CONTENT='.$phoneID[0]["Nummer"]."\n");
                                    fwrite($handle2,'SET !ENCRYPTION NO'."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=ID:loginForm ATTR=ID:password CONTENT='.$phoneID[0]["Password"]."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=BUTTON FORM=ID:loginForm ATTR=TXT:Login'."\n");
                                    fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_dreiat_{{!VAR0}}'."\n");
                                    fwrite($handle2,'\'Ausloggen'."\n");
                                    fwrite($handle2,'URL GOTO=https://www.drei.at/selfcare/restricted/prepareMainPage.do'."\n");
                                    fwrite($handle2,'TAG POS=2 TYPE=A ATTR=TXT:Kundenzone'."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:logout'."\n");
                                    fwrite($handle2,'TAB CLOSE'."\n");					
                                    }
                                else
                                    {
                                    fwrite($handle2,'URL GOTO=https://service.upc.at/myupc/portal/mobile'."\n");
                                    //fwrite($handle2,'URL GOTO=https://service.upc.at/login/?TAM_OP=login&USERNAME=unauthenticated&ERROR_CODE=0x00000000&URL=%2Fmyupc%2Fportal%2Fmobile&REFERER=&OLDSESSION='."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=ACTION:/pkmslogin.form ATTR=ID:username CONTENT=wolfgangjoebstl@yahoo.com'."\n");
                                    fwrite($handle2,'SET !ENCRYPTION NO'."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=ACTION:/pkmslogin.form ATTR=ID:password CONTENT=##cloudG06##'."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=SPAN ATTR=ID:lbl_login_signin'."\n");
                                    fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_dreiat_{{!VAR0}}'."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=SPAN ATTR=ID:MYUPC_child.logout_dsLoggedInAs'."\n");
                                    fwrite($handle2,'TAG POS=1 TYPE=STRONG ATTR=TXT:Abmelden'."\n");						
                                    fwrite($handle2,'TAB CLOSE'."\n");							
                                    }	
                                
                                fclose($handle2);
                                IPS_ExecuteEX($firefox, "imacros://run/?m=dreiat_test.iim", false, false, -1);
                                break;
                            case "SELENIUM":
                                break;
                            default:
                                break;	
                            }
                        break;			// ende case maxcount
                    default:
                        switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                            {    
                            case "IMACRO":                
                                //echo "ImacroAufruf von ".$phoneID[$value]["Nummer"]." mit Index ".$value.".\n";
                                IPS_ExecuteEX($firefox, "imacros://run/?m=dreiat_".$phoneID[$value]["Nummer"].".iim", false, false, -1);
                                break;
                            case "SELENIUM":
                                SetValue($statusReadID,"Selenium Read started on ".date("d.m.Y H:i:s").". Nummer ist ".$phoneID[$value]["Nummer"]." ($value).\n");
                                //echo "Aufruf Selenium von ".$phoneID[$value]["Nummer"]." mit Index $value/$maxcount.\n";
                                $config["DREI"]["CONFIG"]["Username"]=$phoneID[$value]["Nummer"];
                                $config["DREI"]["CONFIG"]["Password"]=$phoneID[$value]["Password"];
                                $seleniumOperations = new SeleniumOperations();            
                                $seleniumOperations->automatedQuery($webDriverName,$config);          // true debug         

                                break;
                            default:
                                break;
                            }
                        break;	
                    }       // end switch
                break;
            case $startActionID:                                                // ein bestimmter Einzel Aufruf aus Hosts für Selenium
                $reguestedAction=GetValueFormatted($startActionID);
                break;
            case $startActionGroupID:                                           // ein bestimmter Gruppen Aufruf aus Hosts für Selenium
                $reguestedAction=GetValueFormatted($startActionGroupID);
                break;
            case $updateApiTableID:
                $reguestedActionApi=GetValueFormatted($updateApiTableID);              // Update
                break;
            default:
                echo "GuthabenSteuerung, unknown ActionID Variable : $variable";
                break;
            }            //end switch
        }
	}           // ende if


    if ($reguestedAction)
        {
            $startexec=microtime(true);
            $configTabs = $guthabenHandler->getSeleniumHostsConfig();
            SetValue($ScriptTimerID,strtoupper($reguestedAction));
            switch (strtoupper($reguestedAction))
                {
                case "EVN":
                case "EASY":
                case "YAHOOFIN":
                case "LOGWIEN":
                case "MORNING":
                case "LUNCHTIME":
                case "EVENING":
                    IPS_SetEventActive($tim5ID,true);
                    $log_Guthabensteuerung->LogNachrichten("Manually requested Selenium Hosts Query for \"$reguestedAction\" with Timer5 within next 310 Seconds");
                    break;
                case "ORF":             // gleich Abfragen
                    $configTemp["ORF"] = $configTabs["Hosts"]["ORF"];
                    $configTemp["Logging"]=$log_Guthabensteuerung;
                    $seleniumOperations->automatedQuery($webDriverName,$configTemp,false);          // true debug
                    $log_Guthabensteuerung->LogNachrichten("Manually requested Selenium Hosts Query for \"$reguestedAction\", Exectime : ".exectime($startexec)." Sekunden");
                    // Auswertung
                    $result=$seleniumOperations->readResult("ORF","Result");  
                        if (isset($installedModules["Startpage"]))
                            {
                            IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
                            IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
                            IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');                                
                            $moduleManagerSP    = new IPSModuleManager('Startpage',$repository);
                            $CategoryIdDataSP   = $moduleManagerSP->GetModuleCategoryID('data');
                            $categoryId_OrfWeather = IPS_GetObjectIDByName('OrfWeather',$CategoryIdDataSP); 
                            $variableIdOrfText   = IPS_GetObjectIDByName("OrfWeatherReportHTML",$categoryId_OrfWeather);        
                            $text="<div>".$result["Value"]."<br>ORF Update from ".date("d.m.Y H:i",$result["LastChanged"])."</div>";
                            $text = str_replace("\n","<br>",$text);
                            SetValue($variableIdOrfText,$text); 
                            }



                    //echo $result;
                    break; 
                default:
                    $log_Guthabensteuerung->LogNachrichten("Taste Action mit Wert $reguestedAction gedrückt, kenn ich nicht. Alles abfragen.");                
                    unset($configTabs["Hosts"]["DREI"]);                // DREI ist nur default, daher löschen
                    $configTemp = $configTabs["Hosts"];
                    $seleniumOperations->automatedQuery($webDriverName,$configTemp,false);          // true debug
                    break;    
                }

         /* if (strtoupper($reguestedAction) != "YAHOOFIN")             // YAHOOFIN testweise im Timer machen
                {
                }
            // Auswertung 
            switch (strtoupper($reguestedAction))
                {
                case "EASY":
                    $configTabs = $guthabenHandler->getSeleniumTabsConfig($reguestedAction);
                    $depotRegister=["RESULT"];
                    if (isset($configTabs["Depot"]))
                        {
                        if (is_array($configTabs["Depot"]))
                            {
                            $depotRegister=$configTabs["Depot"];    
                            }
                        else $depotRegister=[$configTabs["Depot"]];
                        }
                    foreach ($depotRegister as $depot)
                        {
                        $result=$seleniumOperations->readResult("EASY",$depot,true);                  // true Debug   
                        $lines = explode("\n",$result["Value"]);    
                        $data=$seleniumEasycharts->parseResult($lines,false);             // einlesen, true debug
                        $shares=$seleniumEasycharts->evaluateResult($data);
                        $depotName=str_replace(" ","",$depot);                      // Blanks weg
                        if ($depotName != $depot)               
                            {
                            $value=$seleniumEasycharts->evaluateValue($shares);         // Summe ausrechnen
                            $seleniumEasycharts->writeResult($shares,"Depot".$depotName,$value);                         // die ermittelten Werte abspeichern, shares Array etwas erweitern                                
                            }
                        else
                            {
                            $seleniumEasycharts->writeResult($shares,"Depot".$depotName);                         // die ermittelten Werte abspeichern, shares Array etwas erweitern
                            }
                        $seleniumEasycharts->updateResultConfigurationSplit($shares);                // Die wunderschöne split Konfiguration wird hier wieder zunichte gemacht da sie aus der Konfiguration für das Depot , daher aus dem Konfig auslesen       
                        $seleniumEasycharts->writeResultConfiguration($shares, $depotName);                                    
                        }
                    break;
                case "YAHOOFIN":
        			IPS_SetEventActive($tim5ID,true);
                    $log_Guthabensteuerung->LogNachrichten("Start Timer in the next 310 Secoonds.");
                    break;
                default:
                    break;                    
                }   */
        }

    if ($reguestedActionApi)
        {
        switch ($reguestedActionApi)
            {
            case "Update":
                $log_Guthabensteuerung->LogNachrichten("Manually requested Api Query for \"$reguestedActionApi\" .");
                $ipsTables = new ipsTables();
                $yahooApi  = new yahooApi();
                $yahoofin = new SeleniumYahooFin();
                //$data = $yahoofin->getSymbolsfromConfig();

                $seleniumEasycharts = new SeleniumEasycharts();
                $depotbook=$seleniumEasycharts->createDepotBookfromOrderBook();         // actualDepot, Angabe Subdepot möglich
                $actualDepotShorts=array(); $actualDepot=array();
                foreach ($depotbook as $item) 
                    {
                    $actualDepotShorts[]=$item["Short"];                    // welche Ticker sollen angezeigt werden, das sind die die ich nachkaufen oder verkaufen sollte
                    $actualDepot[$item["Short"]]=$item;                         // indexiert das Depotbook actualDepot nach Tickersymbolen (anstelle von Aktien Index)
                    }
                $data = $actualDepotShorts;                             // definiert nach welchen Tickersymbolen gesucht werden soll

                $modules = [
                    'assetProfile', 'balanceSheetHistory', 'balanceSheetHistoryQuarterly', 'calendarEvents',
                    'cashflowStatementHistory', 'cashflowStatementHistoryQuarterly', 'defaultKeyStatistics', 'earnings',
                    'earningsHistory', 'earningsTrend', 'financialData', 'fundOwnership', 'incomeStatementHistory',
                    'incomeStatementHistoryQuarterly', 'indexTrend', 'industryTrend', 'insiderHolders', 'insiderTransactions',
                    'institutionOwnership', 'majorDirectHolders', 'majorHoldersBreakdown', 'netSharePurchaseActivity', 'price', 'quoteType',
                    'recommendationTrend', 'secFilings', 'sectorTrend', 'summaryDetail', 'summaryProfile', 'symbol', 'upgradeDowngradeHistory',
                    'fundProfile', 'topHoldings', 'fundPerformance'];
                $modul = ["incomeStatementHistoryQuarterly","summaryDetail","assetProfile","incomeStatementHistory","price"];
                $config = ["preProcess" => true];
                $result = $yahooApi->getDataYahooApi($data,$modul,$config,false);            // false, kein Debug, von yahoo die Daten der Module abholen
                
                $inputDataPrice = $yahooApi->extractPrice($result,false);                         // true für Debug
                // Marktpreis aktuell mit den Kosten/Stueck aus dem Depot verschneiden, es gibt Kosten und Preis
                $inputDataPortfolio=array(); $row=0;          
                foreach ($inputDataPrice as $rowInput => $entry)
                    {
                    if (isset($entry["Ticker"]))
                        {
                        $inputDataPortfolio[$row]=$entry;
                        if (isset($actualDepot[$entry["Ticker"]]["Kosten"])) $inputDataPortfolio[$row]["cost"]=(float)$actualDepot[$entry["Ticker"]]["Kosten"];
                        if (isset($actualDepot[$entry["Ticker"]]["Stueck"])) $inputDataPortfolio[$row]["price"]=(float)$actualDepot[$entry["Ticker"]]["Stueck"]*$entry["regularMarketPrice"];
                        $row++;
                        }
                    }

                $inputDataIncome = $yahooApi->extractIncomeStatementHistory($result,false);                         // true für Debug, Jahreswerte, letzte 4 Jahre

                $inputDataHistory = $yahooApi->extractIncomeStatementHistoryQuarterly($result);                    // letztes Jahr, quartalswerte
                $calc = [
                                "totalRevenue"                  => "",
                                "costOfRevenue"                 => "",
                                "grossProfit"                   => "",
                                "interestExpense"               => "",
                                "netIncome"                     => "",
                            ];
                $inputDataWork = $yahooApi->calcAddColumnsOfHistoryQuarterly($inputDataHistory,$calc);
                $inputDataMerged = $yahooApi->combineTablesOfHistory($inputDataWork,$inputDataIncome);      //Combine added Quarterlys and yearly Statements
                $inputDataRevenue=$yahooApi->copyLatestEndDateOfHistory($inputDataMerged);                          // copy as long as we have the youngest entry

                // summary Detail 
                $inputData = $yahooApi->extractSummaryDetail($result);   
                $configTransform = array(                                                         // for information only, not ready
                        "shortName"             => "add from inputDataPriceTicker", 
                        "regularMarketTime"    => "add from inputDataPriceTicker",
                        "regularMarketPrice"    => "add from inputDataPriceTicker",
                                // calc
                        "regularChange"         => "round 2Komma (inputData.regularMarketPrice/inputData.previousClose-1)*100",
                        "outstandingMShares"    => "round -3Komma (inputDataPriceTicker.marketCap/inputDataPriceTicker.regularMarketPrice",
                                // price compare to 52week borders, 0 = 10% below low , 1 = 10% above high, marketprice is percentage 1,1*high=100%,0.9*low=0%, marketprice is percentage of price*high*1,1/low*0,9
                                //$reallyLow=$inputData[$row]["fiftyTwoWeekLow"]*0.9;
                                //$rangeLowHigh=($inputData[$row]["fiftyTwoWeekHigh"]*1.1)-$reallyLow;
                        "priceto52weekRange"    => "(inputData.regularMarketPrice-reallyLow)/rangeLowHigh",           // add to rate
                        "rangeToPrice"         => "(rangeLowHigh/inputData.regularMarketPrice",
                        );           // add to rate
                $yahooApi->addTransformColumnsfromtables($inputData,$inputDataPrice,$inputDataRevenue);         // result in inputData
                $configRating = array(
                        "priceToSalesTrailing12Months" => "ratePriceToSalesTrailing12Months",
                        "forwardPE"                    => "rateforwardPE",
                        "rangeToPrice"                 => "rateVolatility",            // Scale Max=1 Min=0, 0,5 is Means
                    );
                $yahooApi->doRatingOfTables($inputData,$configRating);

                $config["sort"]="rate";
                $config["header"]=true;
                $config["html"]='html';

                $displayInput = [
                                "Ticker"                        => "",
                                "shortName"                     => "",
                                "regularMarketTime"             => ["header"=>"regular Market Time","format"=>"DateTime"],
                                "regularMarketPrice"            => ["header"=>"regular Market Price","format"=>"<currency>","compare"=>"<previousClose>"],
                                "regularChange"                 => ["header"=>"regular Change Day","format"=>"%"],
                                "previousClose"                 => "<currency>",                // sucht sich Feld currency und übernimmt Wert
                                "open"                          => "<currency>",
                                "dayLow"                        => "<currency>",
                                "dayHigh"                       => "<currency>",
                                "priceToSalesTrailing12Months"  => ["header"=>"price To Sales Trailing 12Months","format"=>""],
                                "rate"                          => "",                  // mühsam errechnet
                                "forwardPE"                     => "",
                                "trailingPE"                    => "",
                                "fiftyDayAverage"               => ["header"=>"Average 50days","format"=>""],
                                "twoHundredDayAverage"          => ["header"=>"Average 200days","format"=>""],
                                "fiftyTwoWeekLow"               => ["header"=>"Low 52weeks","format"=>"<currency>"],
                                "fiftyTwoWeekHigh"              => ["header"=>"High 52weeks","format"=>"<currency>"],
                                "exDividendDate"                => "Date",
                                "dividendRate"                  => "",
                                "totalRevenue"                  => "<currency>",
                                "marketCap"                     => "<currency>",
                                "outstandingMShares"            => "",   
                            ]; 
                $display=array();                                       // leere Einträge gibt es nicht mehr, es muss zumindest format stehen
                foreach ($displayInput as $index=>$entry)
                    {
                    if (is_array($entry)) $display[$index] = $entry;
                    else
                        {
                        $display[$index]["format"] = $entry;
                        }    
                    }                               
                $result = $ipsTables->showTable($inputData, $display,$config,false);     // true für debug
                SetValue($financeTableID,$result);
                break;
            default:
                break;        
            }
        }

/******************************************************

				Execute

*************************************************************/

if ( ($_IPS['SENDER']=="Execute") )         // && false
	{
    echo "====================Execute Section in Script Guthabensteuerung called:\n";
	echo "Category Data ID            : ".$CategoryIdData."\n";
	echo "Category App ID             : ".$CategoryIdApp."\n";

    $tim1ID = IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
    $tim2ID = @IPS_GetEventIDByName("Exectimer", $_IPS['SELF']);

	echo "Timerprogrammierung: \n";
	//echo "  Timer 1 ID : ".$tim1ID."   ".(IPS_GetEvent($tim1ID)["EventActive"]?"Ein":"Aus")."\n";
    //echo "  Timer 2 ID : ".$tim2ID."   ".(IPS_GetEvent($tim2ID)["EventActive"]?"Ein":"Aus")."\n";
    //echo "  Timer 3 ID : ".$tim3ID."   ".(IPS_GetEvent($tim3ID)["EventActive"]?"Ein":"Aus")."\n";
    $timerOps->getEventData($tim1ID);
    $timerOps->getEventData($tim2ID);
    $timerOps->getEventData($tim3ID);
    $timerOps->getEventData($tim4ID);

	$ScriptCounter=GetValue($ScriptCounterID);
	$checkScriptCounter=GetValue($checkScriptCounterID);
    echo "Check Script Counter        : ".$checkScriptCounter."\n";
    echo "Script Counter (aktuell)    : ".$ScriptCounter."\n";
    echo "Webfront MacroID            : ".$startImacroID."\n";
    echo "Operating Mode              : ".(strtoupper($GuthabenAllgConfig["OperatingMode"]))."\n";
    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {  
        case "SELENIUM":
            $debug=true;
            echo "============================================================================\n";
            if ($webDriverName) echo "WebDriverName: Default\n";
            else echo "WebDriverName:  $webDriverName\n";
            echo "GuthabenHandler Aktive Selenium Konfiguration:\n";
            $configSelenium = $guthabenHandler->getSeleniumWebDriverConfig($webDriverName);       // ist jetzt immer false, könnte aber auch ein Name sein     
            print_r($configSelenium);
            echo "GuthabenHandler Selenium Hosts Konfiguration:\n";
            $configSeleniumHosts = $guthabenHandler->getSeleniumHostsConfig();
            print_r($configSeleniumHosts);

			if ($ScriptCounter < $maxcount) $value=$ScriptCounter;
            else { $value=0; SetValue($ScriptCounterID,0); }
            echo "============================================================================\n";
            echo "Aufruf Selenium von ".$phoneID[$value]["Nummer"]." mit Index $value/$maxcount.\n";
            $config["DREI"]["CONFIG"]["Username"]=$phoneID[$value]["Nummer"];
            $config["DREI"]["CONFIG"]["Password"]=$phoneID[$value]["Password"]; 

            /* die Abendabfrage dazumergen */  
            $configTabs = $guthabenHandler->getSeleniumHostsConfig();
            //print_R($configTabs);
            unset($configTabs["Hosts"]["DREI"]);                // DREI ist nur default, durch echte Werte ersetzen
            unset($configTabs["Hosts"]["EASY"]);                // EASY einmal weglassen
            unset($configTabs["Hosts"]["LogWien"]);             // LogWien einmal weglassen, wenn alle ist zu langsam
            //print_R($configTabs);
            //$config = array_merge($configTabs["Hosts"],$config);          // array merge ersetzt die entsprechenden keys, der letzte Eintrag überschreibt die vorangehenden
            $config = $configTabs["Hosts"];                 //nur YahooFin, keine einzelne Drei Telefonnummer, kein LogWien, kein Easy, Drei braucht aktuell lange
            /******************* Vorbereitung */
            foreach ($config as $host => $entry)
                {
                switch (strtoupper($host))
                    {
                    case "DREI":
                        echo "    $host ".json_encode($entry)."\n";
                        $guthabenHandler->readDownloadDirectory($GuthabenAllgConfig["WebResultDirectory"]);
                        $guthabenHandler->extendPhoneNumberConfiguration($phoneID,$seleniumOperations->getCategory("DREI"));            // phoneID is return parameter
                        echo "       Guthaben Register in der Kategorie:\n";
                        foreach ($phoneID as $entry)
                            {
                            if (isset($entry["OID"]))
                                {
                                echo "       ".$entry["Nummer"]."    : register (".$entry["OID"].") available, last update was ".date("d.m.Y H:i:s",$entry["LastUpdated"])."\n";
                                //$result=GetValue($entry["OID"]);          // letztes Ergebnis interessiert keinen
                                }
                            }
                        break;
                    case "EASY":
                        echo "    $host ".json_encode($entry)."\n";
                        /*
                        echo "           Selenium Operations, read Result from EASY:\n";                // schenbar nicht ehr aktuel, hier kommt immer der 26.1.2022
                        $result=$seleniumOperations->readResult("EASY","Result",true);                  // true Debug
                        //print_R($result);
                        echo "           Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";  */
                        break;
                    case "YAHOOFIN":
                        echo "    $host ".json_encode($entry)."\n";  
                        $configs = $guthabenHandler->getSeleniumTabsConfig("YAHOOFIN",false);            // true for Debug
                        $yahoofin = new SeleniumYahooFin(); 
                        $yahoofin->setConfiguration($configs);                          // alle Symbols oder nur einige wenige
                        echo "Auswewrtung startet mit ".$yahoofin->getIndexToSymbols(true)."\n";
                        break;
                    default:
                        echo "    $host ".json_encode($entry)."\n";
                        break;    
                    }    
                }

            /*************************** Abfrage über Selenium aus dem Internet, hier gehts los*/
            echo "============================================================================\n";            
            $seleniumOperations = new SeleniumOperations();                             // macht nichts, erst mit automated query gehts los
            if ($doQuery) $seleniumOperations->automatedQuery($webDriverName,$config,true);          // true debug, config für Drei und Easy      

            /***************************** Auswertung */
            $hosts="";
            foreach ($config as $host=>$entry) $hosts .= $host." ";
            echo "Aktuell vergangene Zeit für AutomatedQuery ( $hosts): ".exectime($startexec)." Sekunden\n";            // Zeit von Anfang an
            echo "--------\n";

            $indexDrei=$value;
            foreach ($config as $host => $entry)
                {
                switch (strtoupper($host))
                    {
                    case "DREI":                        // Konkurrenz für ParseDreiGuthaben Script
                        $ergebnis1=""; $value=$indexDrei;
                        echo "\n";
                        echo "Parsetxtfile:\n";        
                        echo "--------------------------------------------------\n   ".$phoneID[$value]["Nummer"]."    : ";
                        if (isset($phoneID[$value]["OID"])) 
                            {
                            echo "register (".$phoneID[$value]["OID"].") available";
                            if (isset($phoneID[$value]["LastUpdated"])) 
                                {
                                echo ", last update was ".date("d.m.Y H:i:s",$phoneID[$value]["LastUpdated"]);                    
                                echo "\n";
                                $result=GetValue($phoneID[$value]["OID"]);
                                //echo "$result\n";
                                $lines = explode("\n",$result);
                                $ergebnis1=$guthabenHandler->parsetxtfile($phoneID[$value]["Nummer"],$lines,false,"array");                    
                                }
                            else echo "\n";
                            }
                        else echo "\n"; 
                        //print_r($phoneID[$value]);
                        echo "Ergebnis DREI: $ergebnis1.\n";
                        //SetValue($phone1ID,$ergebnis1);

                        break;
                    case "EASY":
                        echo "=======================================================\n"; 
                        echo "Selenium Operations, read Result from EASY:\n";
                        $seleniumEasycharts = new SeleniumEasycharts();
                        $result=$seleniumOperations->readResult("EASY","Result",true);                  // true Debug
                        //print_R($result);
                        echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";
                        echo "Aktuell vergangene Zeit : ".exectime($startexec)." Sekunden,".exectime($startexec)."\n";
                        echo "--------\n";
                        $log_Guthabensteuerung->LogNachrichten("Execute ViewResult, EASY letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"]));    
                        $lines = explode("\n",$result["Value"]);                       // die Zeilen als einzelne Eintraeg im array abspeichern */

                        $data=$seleniumEasycharts->parseResult($lines);
                        $shares=$seleniumEasycharts->evaluateResult($data);
                        $value=$seleniumEasycharts->evaluateValue($shares);
                        $seleniumEasycharts->writeResult($shares,"MusterDepot3",$value);
         
                        if (false)
                            {
                            $categoryIdResult = $seleniumEasycharts->getResultCategory();
                            echo "Category Easy RESULT $categoryIdResult.\n";
                        
                            $componentHandling = new ComponentHandling();
                        
                            /*function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false) */
                            $oid = CreateVariableByName($categoryIdResult,"MusterDepot3",2,'Euro',"Depot",1000);
                            $componentHandling->setLogging($oid);

                            /* only once per day, If there is change */
                            if (GetValue($oid) != $value) SetValue($oid,$value);

                            //print_R($shares);
                            $parent=$oid;
                            foreach($shares as $share)
                                {
                                $value=$share["Kurs"];
                                $oid = CreateVariableByName($parent,$share["ID"],2,'Euro',"",1000);     //gleiche Identifier in einer Ebene gehen nicht
                                $componentHandling->setLogging($oid);
                                //print_r($share);
                                /* only once per day, If there is change */
                                if (GetValue($oid) != $value) SetValue($oid,$value);
                                }
                            }                        

                        break;
                    case "YAHOOFIN":     
                        echo "=======================================================\n";                                       
                        echo "Auswertung für YahooFin machen, read Result:\n";
                        $result=$seleniumOperations->readResult("YAHOOFIN");                  // true Debug   , lest RESULT als Egebnis Variable, wenn zweite Variable ausgefüllt ist es das entsprechende register
                        echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";       
                        //$yahoofin = new SeleniumYahooFin();
                        $ergebnis = $yahoofin->parseResult($result);                        // eigentlich nur json_decode auf ein array
                        //echo "Ergebnis: ".json_encode($ergebnis)."  \n";
                        foreach ($ergebnis as $index => $entry)
                            {
                            if (isset($entry["Target"])) echo "$index ".$entry["Short"]."    ".$entry["Target"]."\n";
                            }
                        $yahoofin->writeResult($ergebnis,"TargetValue",true);

                        $seleniumEasycharts = new SeleniumEasycharts();
                        $target = $yahoofin->getResult("TargetValue", false);                //true für Debug
                        $config=array();
                        $config["Split"] = $seleniumEasycharts->getEasychartSplitConfiguration("Short");
                        $result = $yahoofin->getResultHistory("TargetValue", $config, false);                         
                        $savedEntry = $yahoofin->processResultHistory($result,$target);
                        //print_R($ergebnis);                        
                        break;
                    default:

                        break;    
                    }    
                }


            echo "============================================================================\n";
            SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1); 
            break;  
        case "IMACRO":
            echo "Verzeichnis für Macros :     ".$GuthabenAllgConfig["MacroDirectory"]."\n";
            echo "Verzeichnis für Ergebnisse : ".$GuthabenAllgConfig["DownloadDirectory"]."\n";
            echo "Verzeichnis für Mozilla exe: ".$firefox."\n\n";
	
        	//print_r($phone);

            $guthabenHandler->readDownloadDirectory();

            $ScriptCounter=GetValue($ScriptCounterID);
            $fileName=$GuthabenAllgConfig["DownloadDirectory"]."report_dreiat_".$phoneID[($ScriptCounter-1)]["Nummer"].".txt";
            echo "Stand ScriptCounter :  $ScriptCounter von max $maxcount   für File $fileName.\n";
            if (is_file($fileName))
                {
                $filedate=date ("d.m.Y H:i:s.", filemtime($fileName) );
                $note="Letzte Abfrage war um ".date("d.m.Y H:i:s")." für dreiat_".$phoneID[($ScriptCounter)]["Nummer"].".iim. Letztes Ergebnis für ".$phoneID[($ScriptCounter-1)]["Nummer"]." mit Datum ".$filedate." ";
                echo $note;
                }

            if (false)		// Jetzt das automatisches Auslesen über Webfront steuern
                {
                SetValue($ScriptCounterID,0);
                //IPS_SetScriptTimer($_IPS['SELF'], 1);
                IPS_SetEventActive($tim2ID,true);
                echo "Exectimer gestartet, Auslesung beginnt ....\n";
                echo "Timer täglich ID:".$tim1ID." und alle 150 Sekunden ID: ".$tim2ID."\n";
                //echo ADR_Programs."Mozilla Firefox/firefox.exe";
        
                echo "\n\nGuthabensteuerung laeuft nun, da sie haendisch mit Aufruf dieses Scripts ausgelöst wurde.\n";
                //IPS_SetEventActive($tim2ID,true); /* siehe weiter oben ...*/
                }

            echo "\n-----------------------------------------------------\n";
            echo "Historie der Guthaben und verbrauchten Datenvolumen.\n";
            //$variableID=get_raincounterID();
            $endtime=time();
            $starttime=$endtime-60*60*24*2;  /* die letzten zwei Tage */
            $starttime2=$endtime-60*60*24*100;  /* die letzten 100 Tage */

            foreach ($GuthabenConfig as $TelNummer)
                {
                $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
                $phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
                $phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_Volume", 2);
                $phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_User", 3);
                $phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["NUMMER"]."_VolumeCumm", 2);
                echo "\n".$TelNummer["NUMMER"]." ".str_pad($TelNummer["STATUS"],10)."  ".str_pad(GetValue($phone_User_ID),40)." : ".GetValue($phone_Volume_ID)."MB und kummuliert ".GetValue($phone_VolumeCumm_ID)."MB \n";
                if (AC_GetLoggingStatus($archiveHandlerID, $phone_VolumeCumm_ID)==false)
                {
                echo "Werte wird noch nicht gelogged.\n";
                }
                else
                    {
                    $werteLog = AC_GetLoggedValues($archiveHandlerID, $phone_VolumeCumm_ID, $starttime2, $endtime,0);
                    $werteLog = AC_GetLoggedValues($archiveHandlerID, $phone_Volume_ID, $starttime2, $endtime,0);
                    $werte = AC_GetAggregatedValues($archiveHandlerID, $phone_Volume_ID, 1, $starttime2, $endtime,0);
                    foreach ($werteLog as $wert)
                        {
                        echo "Wert : ".number_format($wert["Value"], 1, ",", "")."   ".date("d.m H:i",$wert["TimeStamp"])."\n";
                        }
                    }
                //$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
                //$ergebnis1=parsetxtfile($GuthabenAllgConfig["DownloadDirectory"],$TelNummer["NUMMER"]);
                //SetValue($phone1ID,$ergebnis1);
                //$ergebnis.=$ergebnis1."\n";
                }

            if (false)
                {
                echo "\n-----------------------------------------------------\n";
                echo "Aufruf von iMacro oder Parseguthaben wenn Abfragen bereits abgeschlossen.\n";
                SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1);
                //IPS_SetScriptTimer($_IPS['SELF'], 150);
                if (GetValue($ScriptCounterID) < $maxcount)
                    {
                    // keine Anführungszeichen verwenden
                    echo "Aufruf von :".$firefox." imacros://run/?m=dreiat_".$phoneID[GetValue($ScriptCounterID)]["Nummer"].".iim"."\n";
                    IPS_ExecuteEX($firefox, "imacros://run/?m=dreiat_".$phoneID[GetValue($ScriptCounterID)]["Nummer"].".iim", false, false, -1);		
                    }
                else
                    {
                    IPS_RunScript($ParseGuthabenID);
                    SetValue($ScriptCounterID,0);
                    //IPS_SetScriptTimer($_IPS['SELF'], 0);
                    IPS_SetEventActive($tim2ID,false);
                    }
                }
            break;
        default:
            echo "Fehler, Operating Mode \"".$GuthabenAllgConfig["OperatingMode"]."\" not known.\n";
            break;
        }
	}

 

?>