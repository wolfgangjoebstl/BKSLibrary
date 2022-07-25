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
     *      iMacro
     *      Selenium
     *
     * Dieses Script macht alle drei Anwendungsmöglichkeiten
     *      Timer
     *      Webfront
     *      Execute
     *
     * Der Timer wird immer um 22:16 und um 2:27 aufgerufen. Für die Erfassung der Drei Guthaben gibt es eine Verlängerungsmöglichkeit mit Aufruf alle 150 Sekunden
     * Bei der Abendabfrage wird der Drei Host definitiv ausgeschlossen. Die Drei Guthabenabfrage funktioniert immer um 2:27.
     * Am Abend wird nur $seleniumOperations->automatedQuery($webDriverName,$configTabs["Hosts"]... aufgerufen
     *
     * Aktuell implementiert:  Drei, Easy, LogWien
     *
     */



    //Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
    IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(100);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme
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
    $installedModules = $moduleManager->GetInstalledModules();
    $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
    $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $ipsOps = new ipsOps();    
    $dosOps = new dosOps();
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
            break;
        case "NONE":
            $DoInstall=false;
            break;
        default:    
            echo "Guthaben Mode \"".$GuthabenAllgConfig["OperatingMode"]."\" not supported.\n";
            break;
        }
    $statusReadID       = CreateVariable("StatusWebread", 3, $CategoryId_Mode,1010,"~HTMLBox",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    //$testInputID        = CreateVariable("TestInput", 3, $CategoryId_iMacro,1020,"",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
    $startActionID      = IPS_GetObjectIdByName("StartAction", $CategoryId_Mode);	


    $ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
    $checkScriptCounterID=CreateVariableByName($CategoryIdData,"checkScriptCounter",1);
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	/*****************************************************
	 *
	 * initialize Timer 
	 *
	 ******************************************************************/

    $tim1ID = IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
    if ($tim1ID==false) echo "Fehler Timer Aufruftimer nicht definiert.\n";
    $tim3ID = @IPS_GetEventIDByName("EveningCallTimer", $GuthabensteuerungID);
    if ($tim3ID==false) echo "Fehler Timer EveningCallTimer nicht definiert.\n";

    $tim2ID = @IPS_GetEventIDByName("Exectimer", $_IPS['SELF']);
    if ($tim2ID==false)
        {
        $tim2ID = IPS_CreateEvent(1);
        IPS_SetParent($tim2ID, $_IPS['SELF']);
        IPS_SetName($tim2ID, "Exectimer");
        IPS_SetEventCyclic($tim2ID,2,1,0,0,1,150);      /* alle 150 sec */
        //IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
        }

    $tim4ID = $timerOps->CreateTimerHour("AufrufMorgens",4,55,$_IPS['SELF']);

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
 * tim1 ist der Aufruftimer um 2:27 Uhr morgens. Setzt die beiden Counter Register zurück udn startet den Ausführtimer tim2 zu starten
 *
 * tim2 ist ausgelegt alle 150 Sekunden aufgerufen zu werden bis die Aufgabe erledigt ist
 *      ScriptCounter muss maxcount erreichen
 *      wenn der Scriptcounter nicht maxcount erreicht wird nach der doppelten Anzahl an versuchen abgebrochen, ein Eintrag ins Nachrichten log erstellt und der Timer deaktiviert
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
 *************************************************************/

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
		case $tim2ID:               // Exectimer alle 150 Sekunden wenn aktivivert
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
                        $seleniumOperations->automatedQuery($webDriverName,$config);          // true debug      
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
		case $tim3ID:               // immer am späten Abend um 22:16, auch wenn er Evening heisst
            switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                {
                case "SELENIUM":
                    $startexec=microtime(true);
                    $configTabs = $guthabenHandler->getSeleniumHostsConfig("evening");              // Filter evening
                    unset($configTabs["Hosts"]["DREI"]);                // DREI ist nur default, daher löschen
                    $seleniumOperations->automatedQuery($webDriverName,$configTabs["Hosts"],true);          // true debug
                    echo "Aktuell vergangene Zeit für AutomatedQuery: ".exectime($startexec)." Sekunden\n";
                    echo "--------\n";
                    $log_Guthabensteuerung->LogNachrichten("Automated Selenium Hosts Query, Exectime : ".exectime($startexec)." Sekunden"); 
                    // parse wird gemeinsam mit dem drei Guthaben aufgerufen, wenns nicht klappt gibts einen zweiten versuch um 4:55                     
                    break;
                }
            break;
		case $tim4ID:               // immer am frühen morgen um 4:55, macht das Selbe wie am Späten Abend
            $log_Guthabensteuerung->LogNachrichten("ParseDreiGuthaben $ParseGuthabenID called from Guthabensteuerung.");  
            switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                {
                case "SELENIUM":
                    $startexec=microtime(true);
                    $configTabs = $guthabenHandler->getSeleniumHostsConfig("morning");                              // Filter morning
                    unset($configTabs["Hosts"]["DREI"]);                // DREI ist nur default, daher löschen
                    $seleniumOperations->automatedQuery($webDriverName,$configTabs["Hosts"],true);          // true debug
                    echo "Aktuell vergangene Zeit für AutomatedQuery: ".exectime($startexec)." Sekunden\n";
                    echo "--------\n";
                    $log_Guthabensteuerung->LogNachrichten("Automated Selenium Hosts Query, Exectime : ".exectime($startexec)." Sekunden");                      
                    break;
                }
            $log_Guthabensteuerung->LogNachrichten("ParseDreiGuthaben $ParseGuthabenID called from Guthabensteuerung.");  
            IPS_RunScript($ParseGuthabenID);            // ParseDreiGuthaben wird aufgerufen
            break;
		default:
			break;
		}
	}

/******************************************************

				Webfront

*************************************************************/

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	$value=$_IPS['VALUE']; $variable=$_IPS['VARIABLE'];
    switch ($variable)
        {
        case ($startImacroID):
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
        case $startActionID:
            $startexec=microtime(true);
            $configTabs = $guthabenHandler->getSeleniumHostsConfig();
            if ($value==0) $configTemp["EASY"] = $configTabs["Hosts"]["EASY"];
            else
                {
                unset($configTabs["Hosts"]["DREI"]);                // DREI ist nur default, daher löschen
                $configTemp = $configTabs["Hosts"];
                } 
            $seleniumOperations->automatedQuery($webDriverName,$configTemp,false);          // true debug
            $log_Guthabensteuerung->LogNachrichten("Requested Selenium Hosts Query, Exectime : ".exectime($startexec)." Sekunden");   
            /* Auswertung */
            $configTabs = $guthabenHandler->getSeleniumTabsConfig("EASY");
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
        default:
            echo "GuthabenSteuerung, unknown ActionID Variable : $variable";
            break;
        }            //end switch
	}           // ende if

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
            //print_R($configTabs);
            $config = array_merge($configTabs["Hosts"],$config);          // array merge ersetzt die entsprechenden keys, der letzte Eintrag überschreibt die vorangehenden
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
                    default:
                        echo "    $host ".json_encode($entry)."\n";
                        break;    
                    }    
                }
            /*************************** Abfrage über Selenium aus dem Internet, hier gehts los*/
            echo "============================================================================\n";            
            $seleniumOperations = new SeleniumOperations();                             // macht nichts, erst mit automated query gehts los
            $seleniumOperations->automatedQuery($webDriverName,$config,true);          // true debug, config für Drei und Easy      

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
                        $seleniumEasycharts = new SeleniumEasycharts();

                        echo "Selenium Operations, read Result from EASY:\n";
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