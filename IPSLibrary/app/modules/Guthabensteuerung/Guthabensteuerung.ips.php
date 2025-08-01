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

    /* Guthabensteuerung
     *
     * soll das verbleibende Guthaben von SIM Karten von einer Webseite herausfinden
     * unterschiedliche Strategien, Betriebsarten: Aufruf mit
     *
     *      iMacro          leider nur mehr mittels Lizenzzahlung unterstützt, daher Fokus jetzt auf Selenium
     *      Selenium
     *
     * Dieses Script macht alle drei Anwendungsmöglichkeiten
     *      Init
     *      Init Timer
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
     * Es gibt auch webfront/user Funktionen gemeinsam mit dem AMIS Modul
     * in der Tabelle sind Links versteckt
     *
     *
     * verwendet ipsTables für:
     *      webfront yahoofinance in $ Tab
     *
     * andere webfront Tabellen
     *      Darstellung Chromedriver versionen
     *
     */

    ini_set('memory_limit', '128M');       //usually it is 32/16/8/4MB 

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
    IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(100);                              // kein Abbruch vor dieser Zeit, funktioniert nicht für linux basierte Systeme

    if ($_IPS['SENDER']=="Execute") $debug=true;            // Mehr Ausgaben produzieren
	else $debug=false;
    //$debug   = false;                           // zusätzliche Ausgaben unterdrücken, ist auch die Webfront ActionScript Routine hier

    $doQuery        = false;                             // Abfrage mit Selenium Host starten
    $yahooApiAvail  = false;                                // yahoo hat ein wunderschönes API, allerdings ist der Zugang nur mehr authorisiert möglich

    $startexec=microtime(true);    
    //echo "Abgelaufene Zeit : ".exectime($startexec)." Sek. Max Scripttime is 100 Sek \n";         //keine Ausgabe da auch vom Webfront aufgerufen 

/******************************************************
 *
 *               INIT
 *
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
    $webOps = new webOps();                                     // Webfront Operationen

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
            if ($debug) echo "Do Init for Operating Mode Selenium.\n";
            $seleniumOperations = new SeleniumOperations();        
            $CategoryId_Mode            = CreateCategory('Selenium',                $CategoryIdData, 20);
            $startImacroID              = IPS_GetObjectIdByName("StartSelenium",            $CategoryId_Mode);	
            $SeleniumStatusID           = IPS_GetObjectIdByName("SeleniumStatus",           $CategoryId_Mode);
            $SeleniumHtmlStatusID       = IPS_GetObjectIdByName("Status",                   $CategoryId_Mode);          // html Darstellung der geladenen verfügbaren Chromedriver Versionen
            $startActionID              = IPS_GetObjectIdByName("StartAction",              $CategoryId_Mode);	
            $startActionGroupID         = IPS_GetObjectIdByName("StartGroupCall",           $CategoryId_Mode);
            $updateChromedriverID       = IPS_GetObjectIdByName("UpdateChromeDriver",       $CategoryId_Mode);
            $startstoppChromedriverID   = IPS_GetObjectIdByName("StartStoppChromeDriver",   $CategoryId_Mode); 

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
            if (isset($installedModules["OperationCenter"]))
                {
                IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
                IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                $seleniumChromedriverUpdate = new seleniumChromedriverUpdate();     // OperationCenter former Watchdog class
                $processDir = $seleniumChromedriverUpdate->getprocessDir();
                if ($debug) echo "Watchdog Directory : $processDir\n";            

                $seleniumChromedriver=new SeleniumChromedriver();         // SeleniumChromedriver.OperationCenter Child
                $selDir        = $seleniumChromedriverUpdate->getSeleniumDirectory();
                $selDirContent = $seleniumChromedriverUpdate->getSeleniumDirectoryContent();            // erforderlich für version
                if ($debug) echo "get filesize of all chromedriver versions.\n";
                $versionOnShare       = $seleniumChromedriver->getListAvailableChromeDriverVersion();          // alle bekannten Versionen von chromedriver aus dem Verzeichnis erfassen 
                $actualVersion = $seleniumChromedriverUpdate->identifyFileByVersion("chromedriver.exe",$versionOnShare);
                if ($actualVersion===false) 
                    {
                    echo "Probleme mit der Erkennung der aktuellen chromedriver.exe Version.\n";
                    // chromedriver.exe --version ausprobieren, siehe rename_Chromedriver in der Watchdog Library
                    $status=$seleniumChromedriverUpdate->readChromedriverVersion(false,true);
                    if (strlen($status)>6)
                        {
                        $actualVersion=intval(substr($status,0,3));
                        echo "ChromeDriver Version direkt ausgelesen, Version $status : $actualVersion\n";                
                        }
                    }
                if ($actualVersion===false) 
                    {
                    $updateChromedriver=GetValueFormatted($updateChromedriverID);
                    $sourceFile = $seleniumChromedriver->getFilenameOfVersion($updateChromedriver);         // file Adresse erforderlich Quelldatei ermitteln
                    $status=$seleniumChromedriverUpdate->copyChromeDriver($sourceFile,$selDir); 
                    echo "Probleme mit Erkennung, Update Chromedriver mit selber Versionsnummer aufrufen. $updateChromedriver $sourceFile $status\n";
                    $seleniumChromedriverUpdate->deleteChromedriverBackup();
                    $seleniumChromedriverUpdate->stoppSelenium();   
                    $seleniumChromedriverUpdate->renameChromedriver();              // Filename zum umbenennen kommt als copyChromedriver
                    $seleniumChromedriverUpdate->startSelenium();   
                    $log_Guthabensteuerung->LogNachrichten("Update Chromedriver auf selbe Version $updateChromedriver abgeschlossen. Selenium läuft wieder.");
                    $actualVersion = $seleniumChromedriverUpdate->identifyFileByVersion("chromedriver.exe",$versionOnShare,$debug);           // aus der Watchdog Library, issues with detection
                    }
                if ($debug) echo "Chromedriver activated.\n";
                }
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
    
    if ( (isset($GuthabenAllgConfig["Selenium"]["getChromeDriver"])) && ($GuthabenAllgConfig["Selenium"]["getChromeDriver"]) )
        {
        $getChromedriverID          = IPS_GetObjectIdByName("GetChromeDriver",      $CategoryId_Mode);
        }
    else $getChromedriverID=false;

    // Wenn YahooApi aufgebaut wird
    if (isset($GuthabenAllgConfig["Api"]))
        {
        if ($debug) echo "Use Money $ Tab for additional Tables.\n";
        $CategoryId_Finance  = IPS_GetObjectIdByName('Finance',$CategoryIdData);                    // Kategorie, wenn Kategorie nicht gefunden wird ist diese Variable und alle abhängigen davon danach false 
        $vertiButtons=["Update","Calculate","Sort","TargetValues"];
        $webOps->setSelectButtons($vertiButtons,$CategoryId_Finance);

        $updateApiTableID    = @IPS_GetObjectIdByName("Update",$CategoryId_Finance);           // button 
        $calculateApiTableID = @IPS_GetObjectIdByName("Calculate",$CategoryId_Finance);           // button
        $sortApiTableID      = @IPS_GetObjectIdByName("Sort",$CategoryId_Finance);           // button 

        $financeTableID      = @IPS_GetVariableIDByName("YahooFinanceTable", $CategoryId_Finance);		// wenn false nicht gefunden
        $depotTableID        = @IPS_GetVariableIDByName("DepotTable", $CategoryId_Finance);		// wenn false nicht gefunden
        $depotGraphID        = @IPS_GetVariableIDByName("DepotGraph", $CategoryId_Finance);
        }
    else 
        {
        $updateApiTableID=false;                // Defaultwerte für die Verarbeitung    
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

    if ($debug) echo "initialize Timer.\n";
    $tim1ID = IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
    if ($tim1ID==false) echo "Fehler Timer Aufruftimer nicht definiert.\n";
    $tim12ID = IPS_GetEventIDByName("Lunchtimer", $_IPS['SELF']);
    if ($tim12ID==false) echo "Fehler Timer Lunchtimer nicht definiert.\n";
    $tim2ID = @IPS_GetEventIDByName("Exectimer", $GuthabensteuerungID);
    if ($tim2ID==false) echo "Fehler Timer Exectimer nicht definiert.\n";
    $tim3ID = @IPS_GetEventIDByName("EveningCallTimer", $GuthabensteuerungID);
    if ($tim3ID==false) echo "Fehler Timer EveningCallTimer nicht definiert.\n";
    $tim4ID = @IPS_GetEventIDByName("AufrufMorgens", $GuthabensteuerungID);
    if ($tim4ID==false) echo "Fehler Timer AufrufMorgens nicht definiert.\n";
    $tim5ID = @IPS_GetEventIDByName("Tasktimer", $GuthabensteuerungID);
    if ($tim5ID==false) echo "Fehler Timer Tasktimer nicht definiert.\n";
    $tim22ID=false;                                                                     // checks availability of Selenium Webdriver, independent process

    $phoneID=$guthabenHandler->getPhoneNumberConfiguration();
    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {
        case "IMACRO":
            break;
        case "SELENIUM":        
            $guthabenHandler->extendPhoneNumberConfiguration($phoneID,$seleniumOperations->getCategory("DREI"));            // phoneID is return parameter, erweitert um  [LastUpdated] und [OID]   
            $tim22ID   = @IPS_GetEventIDByName("CheckAvailtimer", $GuthabensteuerungID);
            break;
        }

    $maxcount=count($phoneID);
    if ($debug) echo "Init finished.\n";

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
 * tim12
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
                    case "EVN":
                        $configTemp["EVN"] = $configTabs["Hosts"]["EVN"];
                        $configTemp["Logging"]=$log_Guthabensteuerung;
                        $seleniumOperations->automatedQuery($webDriverName,$configTemp,false);          // true debug
                        $log_Guthabensteuerung->LogNachrichten("Manually requested Selenium Hosts Query for \"$reguestedAction\", Exectime : ".exectime($startexec)." Sekunden");
                        // Auswertung
                        $result=$seleniumOperations->readResult("EVN","Result",true);                  // true Debug
                        //print_R($result);
                        echo "Letztes Update ".date("d.m.Y H:i:s",$result["LastChanged"])."\n";
                        $log_Guthabensteuerung->LogNachrichten("Parse EVN Ergebnis from ".date("d.m.Y H:i:s",$result["LastChanged"]).".");  
                        echo "--------\n";
                        // no parsing, always done in IPS_RunScript($ParseGuthabenID);     
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
                        // no parsing, always done in IPS_RunScript($ParseGuthabenID);     
                        //$checkArchive=$archiveOps->getComponentValues($oid,20,false);                 // true mit Debug
                        //$seleniumLogWien = new SeleniumLogWien();
                        //$seleniumLogWien->writeEnergyValue($result["Value"],"EnergyCounter");                    
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
                //$configTabs=false;                // sonst override von den morning etc tabs        
                IPS_SetEventActive($tim5ID,false);
                break;      // Ende Timer5
            case $tim22ID:                              // check availability of Selenium driver, dauert so lange darum in einen Timer ausgelagert
                // change from Watchdog Module, check
                $processes    = $seleniumChromedriverUpdate->getActiveProcesses();
                $processStart = $seleniumChromedriverUpdate->checkAutostartProgram($processes);
                $SeleniumOnID           = IPS_GetObjectIdByName("SeleniumRunning", $CategoryId_Mode);
                if (isset($processStart["selenium"])) 
                    {
                    $date=date("d.m.Y H:i:s");
                    if ($processStart["selenium"]=="Off") SetValue($SeleniumOnID,"Active since $date");
                    else SetValue($SeleniumOnID,"Idle since $date");
                    }
                break;
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
 *      $startstoppChromedriverID   Start Stopp Reset Chromedriver            
 *      $startImacroID              "StartSelenium",    $CategoryId_Mode
 *      $startActionID              "StartAction",      $CategoryId_Mode
 *      $startActionGroupID         "StartGroupCall",   $CategoryId_Mode
 *      $updateApiTableID           "Update",           $CategoryId_Finance                         $reguestedActionApi
 *      $updateChromedriverID       "UpdateChromeDriver",   "119","120","121"   $CategoryId_Mode
 *      $getChromedriverID          "GetChromeDriver",      "Get"               $CategoryId_Mode    
 *
 *************************************************************/

    $reguestedAction    = false;               // Vereinheitlichung der Actions
    $reguestedActionApi = false;               // Vereinheitlichung der Actions
    $updateChromedriver = false;                // gleich gelöst, wäre aber nicht notwendig
    $getChromedriver    = false;                // gleich gelöst, damit nicht unübersichtlich im Case
    $selectButton       = false;                // setzt reguestedActionApi für die Auswahl der linken Buttons

    if ($_IPS['SENDER']=="WebFront")
        {
        /* vom Webfront aus gestartet */

        SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
        $value=$_IPS['VALUE']; $variable=$_IPS['VARIABLE'];
        if ($variable)
            {
            switch ($variable)
                {
                case ($startstoppChromedriverID):            //Start , Stopp , Reset Chromedriver
                    $action=GetValueFormatted($startstoppChromedriverID); 
                    switch ($action)
                        {
                        case "Start":
                            $seleniumChromedriverUpdate->startSelenium(); 
                            break;
                        case "Stopp":
                            $seleniumChromedriverUpdate->stoppSelenium(); 
                            break;
                        case "Reset":
                            $seleniumChromedriverUpdate->stoppSelenium(); 
                            $seleniumChromedriverUpdate->startSelenium(); 
                            break;
                        case "Check":
                            //echo "Requested Action $action <br>";   
                            $statusRequestedAction=$seleniumChromedriverUpdate->activeSelenium(); 
                            $content = str_replace(array("\r\n", "\n\r", "\r"), "\n", $statusRequestedAction);                     // Zeilenumbruch vereinheitlichen
                            $content = explode("\n", $content);                                         // und in Array umrechnen
                            $firstline=false;
                            $fileOps=new fileOps();

                            $userName=$fileOps->analyseContent($content, "BENUTZERNAME");
                            $process=$fileOps->analyseContent($content, "CommandLine",2);

                            $id="a1234"; $class="maindiv1";
                            $text = "<style>";
                            $text.='#'.$id.' table { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; ';
                            $text.='font-size:12px; max-width: 900px ';        // responsive font size
                            $text.='color:black; border-collapse: collapse;  }';
                            $text.='#'.$id.' td, #customers th { border: 1px solid #ddd; padding: 8px; }';
                            $text.='#'.$id.' tr:nth-child(even){background-color: #f2f2f2;color:black;}';
                            $text.='#'.$id.' tr:nth-child(odd){background-color: #e2e2e2;color:black;}';
                            $text.='#'.$id.' tr:hover {background-color: #ddd;}';
                            $text.='#'.$id.' th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; word-wrap: break-word; white-space: normal;}';

                            // div darunter übereinander rechtsbündig darstellen
                            $text.=".".$class." { width: 100%; height: 100%;          
                                display: flex;  flex-direction: column; flex-wrap: wrap;        
                                justify-content: space-between;
                                align-items: flex-start; align-content: flex-start;
                                box-sizing: border-box; padding: 1px 1px 1px 1px;		}";
                            // zusaetzliche Formatierung für die divs unter maindiv1 :
                            $text.=".".$class."1>* { position: relative;	z-index: 1; }"; 
                            // div darunter für left-aligned, center-aligned und right aligned
                            $text.=".".$class."2 { width: 100%; height: 90%;           
                                display: flex; position: relative; 
                                justify-content: space-between;
                                align-items: center;
                                box-sizing: border-box;	padding: 0px 0px 5px 0px;	}";
                            $text.="</style>";
                            
                            $html=$text;
                            $html .= "<div class=".$class."1 id=$id>";
                            /* html ohne html block aber mit style und unterschiedlicher id
                            */
                            $html .= "<div class=".$class."2 >";

                            $ipsTables = new ipsTables();             // create classes used in this class, standard creation of tables
                            $config=array();
                            $config["html"]    = 'html'; 
                            $config["insert"]["Header"]    = true;
                            $config["format"]["class-id"]=false;                // ein div ohne Formatierungen
                            $html.=$ipsTables->showTable($userName,false,$config);
                            //print_r($config);                   // die config wird erweitert, sozusagen als Ergebnis
                            $html .= "</div>";

                            $html .= "<div class=".$class."2 >";

                            $config=array();
                            $config["html"]    = 'html'; 
                            $config["insert"]["Header"]    = true;
                            $config["format"]["class-id"]=false;
                            $html.=$ipsTables->showTable($process,false,$config);

                            $html .= "</div>";
                            $html .= "</div>";

                            $SeleniumActiveID = IPS_GetObjectIdByName("SeleniumActive", $CategoryId_Mode);          
                            if ($SeleniumActiveID) SetValue($SeleniumActiveID,$html);
                            break;
                        }
                    break;
                case ($startImacroID):                      // StartSelenium in eigenem Webfront Guthabensteuerung
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
                case $startActionID:                                                // StartAction im Selenium/Tools , ein bestimmter Einzel Aufruf aus Hosts für Selenium
                    $reguestedAction=GetValueFormatted($startActionID);
                    break;
                case $startActionGroupID:                                           // StartGroupCall im Selenium/Tools , ein bestimmter Gruppen Aufruf aus Hosts für Selenium
                    $reguestedAction=GetValueFormatted($startActionGroupID);
                    break;
                case $updateApiTableID:
                    $reguestedActionApi=GetValueFormatted($updateApiTableID);              // Update Button in $ YahooFinance
                    break;
                case $updateChromedriverID:                                                     // UpdateChromeDriver Button im Selenioum/Tools
                    $updateChromedriver=GetValueFormatted($updateChromedriverID);
                    //echo "go for $updateChromedriver";
                    break;
                case $getChromedriverID:                                        //echo "get new Chromedriver Versions\n";
                    $getChromedriver    = true;
                    break;
                default:
                    $buttonsId = $webOps->getSelectButtons(); 
                    $selectButton=false;
                    foreach ($buttonsId as $id => $button)
                        {
                        if ($variable == $button["ID"]) 
                            { 
                            $selectButton=$id;  
                            $webOps->selectButton($id);             // umfärben wenn gedrückt wurde
                            //print_r($buttonsId[$id]);
                            break; 
                            }
                        } 
                    if ($selectButton===false) echo "GuthabenSteuerung, unknown ActionID Variable : $variable";
                    break;
                }            //end switch
            }


        }           // ende if Webfront

    if ($selectButton)
        {
            switch ($selectButton)
                {
                case 0:
                    $reguestedActionApi="Update";
                    break;
                case 1:
                    $reguestedActionApi="Calculate";
                    break;   
                case 2:
                    $reguestedActionApi="Sort";
                    break;   
                case 3:
                    $reguestedActionApi="TargetValues";
                    break;   
                default:
                    echo "unknown";
                    break;             
                }

        }

    if ($reguestedAction)                           // StartAction im Selenium/Tools , ein bestimmter Einzel Aufruf aus Hosts für Selenium
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

    if ($reguestedActionApi)                             // Update Button in $ YahooFinance
        {
        $yahoofin = new SeleniumYahooFin();                     // benutzt yahoofin class Funktionen egal ob yahoo Api funktioniert oder nicht
        $seleniumEasycharts = new SeleniumEasycharts();
        $ipsTables = new ipsTables();
        $archiveOps = new archiveOps();  
        switch ($reguestedActionApi)
            {
            case "Update":
                $log_Guthabensteuerung->LogNachrichten("Manually requested Api Query for \"$reguestedActionApi\" .");
                $yahooApi  = new yahooApi();
                //$data = $yahoofin->getSymbolsfromConfig();

                $depotbook=$seleniumEasycharts->createDepotBookfromOrderBook();         // actualDepot, Angabe Subdepot möglich
                $actualDepotShorts=array(); $actualDepot=array();
                foreach ($depotbook as $item) 
                    {
                    $actualDepotShorts[]=$item["Short"];                    // welche Ticker sollen angezeigt werden, das sind die die ich nachkaufen oder verkaufen sollte
                    $actualDepot[$item["Short"]]=$item;                         // indexiert das Depotbook actualDepot nach Tickersymbolen (anstelle von Aktien Index)
                    }
                $data = $actualDepotShorts;                             // definiert nach welchen Tickersymbolen gesucht werden soll
                if ($yahooApiAvail)                         // API braucht zusätzliche Authentifizierung, wie wenn ein Webinterface verwendet wird
                    {
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
                    }
                else
                    {
                    $shares = $seleniumEasycharts->getResultConfiguration("actualDepot");
                    $orderbook=$seleniumEasycharts->getEasychartConfiguration();
                    $resultShares=array();
                    $config["KIfeature"]="Shares";          // aktiviert eigene Berechnungen
                    $countShares=0; $maxShares=1000;
                    foreach($shares as $index => $share)                    //zuerst die aktuellen Daten holen, samt Auswertung
                        {
                        $config1=$config;
                        if (isset($share["Split"]))           //getValues braucht die Split Anweisungen extra als Configuration
                            {
                            $config1["Split"]=$share["Split"];        // Split muss berücksichtigt werden
                            }
                        $result = $archiveOps->getValues($share["OID"],$config1);                 // true mit Debug, nur den OID Wert des Archives übergeben, Zusatzinfos in der Config
                        $resultShares[$share["ID"]]=$result;            // für EvaluateDepotbook
                        $resultShares[$share["ID"]]["Info"]=$share;

                        if (isset($orderbook[$share["ID"]])) $resultShares[$share["ID"]]["Order"]=$orderbook[$share["ID"]];
                        if ($countShares++>$maxShares) break;
                        } 
                    $eventLog=array();
                    foreach ($resultShares as $index => $array) 
                        {
                        foreach ($array["Description"]["eventLog"] as $timestamp => $event)
                            {
                            $eventLog[$timestamp][$index]=$event;    
                            }
                        }  
                    $depotbook = $seleniumEasycharts->createDepotBookfromOrderBook();                   // aus dem Orderbook den aktuellen Stand des Depots ermitteln
                    $status=$seleniumEasycharts->evaluateDepotBook($depotbook,$resultShares, false);                        //true mit Debug, Ausgabe unvollständig
                    $ipsTables = new ipsTables();
                    $config=array(                          // alte Parameterierung für Berechnung Depot
                            "sort"      =>  "change",
                            "header"    =>  true,
                            "html"      =>  'html',
                            "sum"       =>  ["cost","value"],           // wird nicht mehr dargestellt
                    );
                    $config["insert"]["Header"]    = true;

                    $display=array(
                            "ID"                =>  ["header"=>"ID","format"=>""],
                            "Name"              =>  ["header"=>"Name","format"=>""],
                            "priceBuy"          =>  ["header"=>"priceBuy","format"=>"€"],
                            "cost"              =>  ["header"=>"cost","format"=>"€"],
                            "pcs"               =>  ["header"=>"pcs","format"=>""],
                            "priceActualValue"  =>  ["header"=>"actual","format"=>"€"],
                            "priceActualTimeStamp"=>["header"=>"TimeStamp","format"=>"DateTime"],
                            "change"            =>  ["header"=>"change","format"=>"%"],
                            "value"             =>  ["header"=>"value","format"=>"€"],
                    );
                    $displayDraft = $ipsTables->checkDisplayConfig($ipsTables->getColumnsName($depotbook["Table"]));
                    //print_R($displayDraft);
                    $html = $ipsTables->showTable($depotbook["Table"], $display,$config,false);     // true für debug
                    SetValue($depotTableID,$html);
                    // Ausgabe des Charts
                    $charts = new ipsCharts();
                    $specialConf =  array ( 
                                                    "Depotwert"         => array(         // unterschiedlicher Name erforderlich, so heisst das Highcharts Template
                                                            "Duration"     => (400*60*60*24),
                                                            "Unit"      => "Euro",                                            
                                                            "Size"      => "3x",
                                                            "Style"     => "area",
                                                            "Step"      => "left",                          // do not spline the points, make steps
                                                            "Aggregate" => false,
                                                            "backgroundColor" => 0x050607,
                                                                    ) ,
                                                            );
                    $html=$charts->createChartFromArray($depotGraphID,$specialConf,$depotbook["Value"],false);
                    SetValue($depotGraphID,$html);
                    }
                break;
            case "TargetValues":
                $targets = $yahoofin->getResult("TargetValue");                //true,1 oder 2 für Debug, die einzelnen Targets raussuchen und dann in getResultHistory mit getValues analysieren
                $config=array();
                $config["Split"] = $seleniumEasycharts->getEasychartSplitConfiguration("Short");
                $config["Aggregated"]="monthly";
                $config["StartTime"]=time()-365*24*60*60;
                $result = $yahoofin->getResultHistory("TargetValue", $config);                //false,true,2,3 für Debug, ruft getValues auf für die Analyse mit der selben Debug Einstellung, Index ist Short
                $config["ShowTable"]["output"]="realTable";                         // keine echo textausgabe mehr
                $config["ShowTable"]["align"]="daily";                   // beinhaltet auch aggregate 
                $resultShow = $yahoofin->archiveOps->showValues(false,$config,$debug);

                $display=array(); $first=true;
                $displayCols = $ipsTables->checkDisplayConfig($ipsTables->getColumnsName($resultShow,$debug),$debug);
                foreach ($displayCols as $id => $entry)
                    {
                    if ($first || ($id=="TimeStamp"))
                        {
                        $display["TimeStamp"] = array("header"=>"Date","format"=>"Date",);
                        $first=false;
                        } 
                    if ($id!="TimeStamp") $display[$id]=$entry;
                    }
                $config=array();
                $config["html"]=true;
                $config["text"]=false;                          //kein echo bei der Anzeige der TargetValues
                $config["insert"]["Header"]    = true;
                $config["transpose"]=true;
                $html = $ipsTables->showTable($resultShow, $display,$config,false);     // true/2 für debug , braucht einen Zeilenindex
                SetValue($financeTableID,$html);
                break;
            default:
                break;        
            }
        }

    /* eine neue Version des chromedriver einspielen, angeforderte Version (integer) ist in der Variable
     * zuerst die Source checken, ein Cloudlaufwerk, welche Versionen sind vorhanden, Namenskonvention chromedriver_".$version.".exe
     * dann das Zielverzeichnis untersuchen, herausfinden aufgrund der Filegroesse welche version die Datei chromedriver.exe hat
     * dann weitermachen wenn unterschiedlich
     * stopp Selenium
     *  
     */
    if ($updateChromedriver)            
        {
        // change from Watchdog Module, check
        if (isset($installedModules["OperationCenter"])) 
            {
            $log_Guthabensteuerung->LogNachrichten("Update Chromedriver auf Version $updateChromedriver gestartet");
            //echo "OperationCenter Module ist installiert, zusätzliche Funktionen zur Automatisierung Update Chromedriver machen.\n"; 
            $seleniumChromedriver=new SeleniumChromedriver();         // SeleniumChromedriver.OperationCenter Child
            $latestVersion=array_key_last($versionOnShare);
            $sourceFile = $seleniumChromedriver->getFilenameOfVersion($updateChromedriver);         // file Adresse erforderlich Quelldatei ermitteln

            $selDirContent = $seleniumChromedriverUpdate->getSeleniumDirectoryContent();
            $log_Guthabensteuerung->LogNachrichten("Update Chromedriver aktuelle Version \"$actualVersion\" identifiziert.");       // kann auch false sein wenn nicht identifiziert
            if ($actualVersion==$updateChromedriver) 
                {
                if ($debug) echo "already done on $updateChromedriver";
                }
            else 
                {
                //echo "Aktuelles chromedriver.exe gefunden. Version $actualVersion. Neue version ist hier : $sourceFile\n";
                $status=$seleniumChromedriverUpdate->copyChromeDriver($sourceFile,$selDir);                                                 // Watchdog Library
                if ($status==102) $log_Guthabensteuerung->LogNachrichten("Neues Chromedriver File wurde bereits in Zielverzeichnis $selDir kopiert");
                else $log_Guthabensteuerung->LogNachrichten("Neues Chromedriver File $sourceFile in Zielverzeichnis $selDir kopiert. Status $status.");
                $seleniumChromedriverUpdate->deleteChromedriverBackup();
                $log_Guthabensteuerung->LogNachrichten("Update Chromedriver, Selenium wird jetzt gestoppt.");
                $seleniumChromedriverUpdate->stoppSelenium();   
                $log_Guthabensteuerung->LogNachrichten("Update Chromedriver, Selenium wurde gestoppt.");
                $seleniumChromedriverUpdate->renameChromedriver();              // Filename zum umbenennen kommt als copyChromedriver
                $log_Guthabensteuerung->LogNachrichten("Update Chromedriver, Selenium wird jetzt gestartet.");
                $seleniumChromedriverUpdate->startSelenium();   
                $log_Guthabensteuerung->LogNachrichten("Update Chromedriver auf Version $updateChromedriver abgeschlossen. Selenium läuft wieder.");
                }
            $SeleniumUpdate = new SeleniumUpdate();
            $tabs=$SeleniumUpdate->findTabsfromVersion($versionOnShare, $actualVersion);

            SetValue($SeleniumStatusID,"Active Selenium version is $actualVersion . Latest version available $latestVersion ");
            $pname="UpdateChromeDriver";                                         // keine Standardfunktion, da Inhalte Variable
            $webOps->createActionProfileByName($pname,$tabs,0);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
            }

        }

    if ( ($_IPS['SENDER']=="Execute") )         // && false
        {
        echo "====================Execute Section in Script Guthabensteuerung called, getChromedriver from Webpage:\n";
        $getChromedriver=true; $debug=true;
        }

    /* letzte verfügbare Chromedriver Version von ftp Server laden
     *      dazu rausfinden welche Versionen es am Server gibt mit und in result speichern
     *      dann mit der aktuell eingesetzten Version vergleichen, nur die größeren Versionsnummern vergleichen
     *
     *      Ergebnis als html zusammenfassen, beinhaltet Ergebnis und Tabelle
     *          SeleniumUpdate::installEnvironment  für TrgetDir und Status 7za.exe
     *          Tabelle mit Spalte Versionsnummer, alte Versionsbezeichnung, neue Versionsbezeichnung
     */
    if ($getChromedriver)               
        {
        $html="";
        // change from Watchdog Module, check
        if (isset($installedModules["OperationCenter"])) 
            {
            $SeleniumUpdate = new SeleniumUpdate();
            $result = $SeleniumUpdate->installEnvironment($GuthabenAllgConfig["Selenium"]["DownloadDir"]);      // gibt targetdir und den status von 7za.exe aus.
            $html .= $result;

            $seleniumChromedriver=new SeleniumChromedriver();         // SeleniumChromedriver.OperationCenter Child
            $selDirContent = $seleniumChromedriverUpdate->getSeleniumDirectoryContent();            // erforderlich für version
            $versionOnShare    = $seleniumChromedriver->getListAvailableChromeDriverVersion();          // alle bekannten Versionen von chromedriver aus dem Verzeichnis erfassen 

            //print_R($versionOnShare);          // Version Nummer, Filename, Size in Bytes
            $actualVersion = $seleniumChromedriverUpdate->identifyFileByVersion("chromedriver.exe",$versionOnShare,$debug);           // aus der Watchdog Library, issues with detection

            // neue Chromedriver Versionen finden, wenn neue version gefunden wird diese zum Download anmerken, wird in config gespeichert                                
            $log_Guthabensteuerung->LogNachrichten("Get Chromedriver Versionen von Webpage gestartet");
            $configChromedriverID       = IPS_GetObjectIdByName("ConfigChromeDriver",$CategoryId_Mode); 
            $sysOps = new sysOps();
            $curlOps = new curlOps();    

            $html="";
            $result = $seleniumChromedriver->getListDownloadableChromeDriverVersion();          // reads internal class variable
            $config=$guthabenHandler->getConfigChromedriver();
            $configAdd = $seleniumChromedriver->getUpdateNewVersions($config,$html,$actualVersion,$debug);          // first two parameters are updated, getupdatedVersions from Web            

            if ($debug) echo "---------------\n".$html."\n--------------\n";
    
            $dir=$GuthabenAllgConfig["Selenium"]["DownloadDir"];  
            $execDir=$seleniumChromedriver->get_ExecDir();
            //echo "Bestehende chromedriver Versionen ausgeben. Verzeichnis Synology/Shared Drive : ".$execDir."\n";

            $filename="chromedriver-win64.zip";  
            $success=true;
            if (sizeof($configAdd)==0) 
                {
                $html .= "No copy to sharedrive : $execDir ".'<br>';    
                $success=false;
                }
            foreach ($configAdd as $version => $entry)
                {
                //echo "Version $version bearbeiten : \n";
                if ($success==false) echo "no success !\n";
                if (isset($result[$version]["url"]))
                    {
                    $log_Guthabensteuerung->LogNachrichten("Url ".$result[$version]["url"]." laden");
                    $curlOps->downloadFile($result[$version]["url"],$dir);
                    $files = $dosOps->writeDirToArray($dir);        // bessere Funktion
                    $file = $dosOps->findfiles($files,$filename,true);       //Debug
                    if ($file) 
                        {
                        //echo "   --> Datei $filename für version $version gefunden.\n";
                        $commandName="unzip_chromedriver.bat";
                        $ergebnis = "not started";
                        $ergebnis = $sysOps->ExecuteUserCommand($dir.$commandName,"",true,true,-1,true);             // parameter show wait -1 debug
                        //echo "Execute Batch $dir$filename \"$ergebnis\"\n";
                        $dirname=$dir."chromedriver-win64/";
                        if (is_dir($dirname))
                            {
                            //echo "Process result of unzip in $dirname.\n";
                            $files = $dosOps->writeDirToArray($dirname);        // bessere Funktion
                            $dosOps->writeDirStat($dirname);                    // Ausgabe Directory ohne Debug bei writeDirToArray einzustellen
                            //echo "moveFile ".$dirname."chromedriver.exe to ".$dir."chromedriver_$version.exe\n";
                            copy($dirname."chromedriver.exe",$dir."chromedriver_$version.exe");
                            $dosOps->rrmdir($dirname);
                            //echo "    -> finished, result and $dirname deleted.\n";
                            }
                        else 
                            {
                            //echo "Dir $dirname not found.\n"; 
                            $success=false;
                            }
                        $dosOps->deleteFile($dir.$filename);
                        $files = $dosOps->writeDirToArray($dir);        // bessere Funktion
                        }
                    else $success=false;
                    }
                else $success=false;
                }
            if ($success)
                {
                $log_Guthabensteuerung->LogNachrichten("Update Config.Succesful Operation");
                $configString = json_encode($configNew);
                SetValue($configChromedriverID,$configString);  
                $html .= "Copy to sharedrive $execDir the following files: <br>"; 
                $i=1;                 
                foreach ($config as $version => $entry)
                    { 
                    if (file_exists($execDir."chromedriver_$version.exe")) echo "Version $version bereits vorhanden, wird auf neueste Version überschrieben.\n";
                    copy ($dir."chromedriver_$version.exe",$execDir."chromedriver_$version.exe"); 
                    $html .= str_pad($i,2," ",STR_PAD_LEFT)."    chromedriver_$version.exe<br>"; 
                    $i++;                 
                    }
                $seleniumChromedriver->update_ExecDirContent();
                $versionOnShare    = $seleniumChromedriver->getListAvailableChromeDriverVersion();          // alle bekannten Versionen von chromedriver aus dem Verzeichnis execdir erfassen 
                }  
            }
        // Update Tabs
        $latestVersion=array_key_last($versionOnShare);
        $tabs=$SeleniumUpdate->findTabsfromVersion($versionOnShare, $actualVersion);
        SetValue($SeleniumStatusID,"Active Selenium version is $actualVersion . Latest version available $latestVersion ");
        $pname="UpdateChromeDriver";                                         // keine Standardfunktion, da Inhalte Variable
        $webOps->createActionProfileByName($pname,$tabs,0);
        $html .= "Tabs for updating chromedriver has been set to the latest setup of $actualVersion ... $latestVersion.<br>";
        SetValue($SeleniumHtmlStatusID,$html);          // Status Window update
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
    $timerOps->getEventData($tim1ID);
    $timerOps->getEventData($tim2ID);
    $timerOps->getEventData($tim12ID);
    $timerOps->getEventData($tim3ID);
    $timerOps->getEventData($tim4ID);
    $timerOps->getEventData($tim5ID);
    $timerOps->getEventData($tim22ID);

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
            if ($webDriverName) echo "WebDriverName: $webDriverName\n";
            else echo "WebDriverName: Default\n";
            echo "GuthabenHandler Aktive Selenium Konfiguration:\n";
            $configSelenium = $guthabenHandler->getSeleniumWebDriverConfig($webDriverName);       // ist jetzt immer false, könnte aber auch ein Name sein     
            print_r($configSelenium);
            echo "GuthabenHandler Selenium Hosts Konfiguration:\n";
            $configSeleniumHosts = $guthabenHandler->getSeleniumHostsConfig();
            print_r($configSeleniumHosts);

			if ($ScriptCounter < $maxcount) $value=$ScriptCounter;
            else { $value=0; SetValue($ScriptCounterID,0); }
            echo "============================================================================\n";
            if (isset($phoneID[$value]["Nummer"]))  // nur wenn Nummern eingegeben, drei abfragen
                {
                echo "Aufruf Selenium von ".$phoneID[$value]["Nummer"]." mit Index $value/$maxcount.\n";
                $config["DREI"]["CONFIG"]["Username"]=$phoneID[$value]["Nummer"];
                $config["DREI"]["CONFIG"]["Password"]=$phoneID[$value]["Password"]; 
                }
            /* die Abendabfrage dazumergen */  
            $configTabs = $guthabenHandler->getSeleniumHostsConfig();
            //print_R($configTabs);
            //unset($configTabs["Hosts"]["DREI"]);                // DREI ist nur default, durch echte Werte ersetzen
            unset($configTabs["Hosts"]["EASY"]);                // EASY einmal weglassen
            unset($configTabs["Hosts"]["LogWien"]);             // LogWien einmal weglassen, wenn alle ist zu langsam
            unset($configTabs["Hosts"]["YAHOOFIN"]);            // Yahoo Finance einmal weglassen, das ist nicht das API 
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