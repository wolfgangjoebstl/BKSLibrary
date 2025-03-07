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
	 
	/**@defgroup Guthabensteuerung::Guthabensteuerung_Installation
	 * @{
	 *
	 * Script um herauszufinden ob die Guthaben der Simkarten schon abgelaufen sind
     * erweitert um die Funktionen von Selenium und chromedriver
     * Abhängig von der Betriebsart wird iMacro (nur mehr gegen Geld nutzbar) oder Selenium initialisiert
     *
	 * Installationsroutine, Eigenes Tab im SystemTP für Selenium Status
     * Selenium Funktion wird hier immer mehr ausgeweitet
     * Selenium benötigt aktuelle chromedriver Versionen, diese werden über Synology Drive verteilt
     * Aus Synology Drive die chromedriver_xxx xxx version katalogisieren
     * über Tastendruck kann Selemium aktualisisert werden, erfordert Aufruf Installation
     *
     * Zusätzlich werden jetzt auch die letztgültigen chromedriver versionen in das Download Verzeichnis geladen
     * erfordert json download der versionierung und link, unzip und Speicherung mit _xxx
     *
     * Allgemeine Funktion Money mit dem Dollarzeichen
     * Darstellung von Guthaben, aktuellem Depotwert, Analyseergebnisse von Yahoo Finance API
     * nachdem diese API nur mehr intern für Webbrowser Aufrufe funktioniert und systematisch für externe Aufrufe geblockt wird
     * wird die API Schnittstelle deaktiviert und um eine Selenium Schnittstelle erweitert 
     *
     * Installationsschritte:
     *      INIT, generell, check ob AMIS Modul
     *      INIT, Variablen anlegen
     *      Setup YahooApi und vielleicht auch andere, die Variablen initialisisern
     *      Setup Selenium or iMacro Environment installieren
     *          iMacro   ein paar variablen auifsetzen
     *          Selenium:
     *
     *
     *      initialize Timer
     *      initialize Profile
     *      Selenium Webdriver 
     *      if doinstall, iMacro Telefonnummern abfragen
     *      WebFront Installation, display of Guthabensteuerung Information
     *      INIT, Nachrichtenspeicher, Ausgeben wenn Execute
     *      Selenium Webfront initialisieren, display of Guthabensteuerung Information
	 *
	 * @file          Guthabensteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

/********************************************************
 *
 * INIT, generell, 
 *
 * überprüft ob AMIS installiert ist, zusätzliche Kategorie
 *
 *******************************************************************/

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");    
    IPSUtils_Include ('Guthabensteuerung_Include.inc.php', 'IPSLibrary::app::modules::Guthabensteuerung');

	// max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(400); 
	$startexec=microtime(true);
    ini_set('memory_limit', '128M');          // memory

    $errorWarning=false;            // Zusammenfassung ob es etwas zum anschauen gibt oder nicht

    $DoInstall=true; 
    $DoWatchdogProcessActiveCheck = false;      // check ob Selenium läuft, dauert zu lange
    $copyToSharedDrive=false;                   // nicht zurückspeichern auf den Shared drive
    $DoDelete=false;                            // Alle Webfronts beginnend mit Money löschen

    if ($_IPS['SENDER']=="Execute") 
        {
        $debug=true;            // Mehr Ausgaben produzieren
        $DoWatchdogProcessActiveCheck = true;      // check ob Selenium läuft, wenn script direkt aufgerufen wurde
        }
	else $debug=false;

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	$ergebnis1=$moduleManager->VersionHandler()->GetScriptVersion();
	$ergebnis2=$moduleManager->VersionHandler()->GetModuleState();
	$ergebnis3=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	$ergebnis4=$moduleManager->VersionHandler()->GetVersion('Guthabensteuerung');

    $systemDir     = $dosOps->getWorkDirectory(); 

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="Installierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.="   ".str_pad($name,37)." ".$modules."\n";
		}
	if ($debug)
        {
        echo str_pad("Kernelversion : ",40).IPS_GetKernelVersion()."\n";
        echo str_pad("IPS Version : ",40).$ergebnis1." ".$ergebnis2."\n";
        echo str_pad("IPSModulManager Version : ",40).$ergebnis3."\n";
        echo str_pad("Guthabensteuerung Version : ",40).$ergebnis4."\n";  
        echo "\n";      
        echo $inst_modules."\n";
        echo "systemdir : ".$systemDir."\n";
        }

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $ipsOps = new ipsOps();    
    $webOps = new webOps();                                     // Webfront Operationen
    $profileOps = new profileOps();             // Profile verwalten, local geht auch remote

	$modulhandling = new ModuleHandling();	                    // aus AllgemeineDefinitionen

    if (isset($installedModules["Amis"]))
        {
        echo "Amis installiert.\n";
		$moduleManagerAmis = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
    	$CategoryIdDataAmis     = $moduleManagerAmis->GetModuleCategoryID('data');
        $categoryId_SmartMeter        = CreateCategory('SmartMeter',        $CategoryIdDataAmis, 80);

        }

/********************************************************
 *
 * INIT, Variablen anlegen
 *
 *******************************************************************/

    $guthabenHandler = new GuthabenHandler(true,true,true);         // true,true,true Steuerung für parsetxtfile
	$GuthabenConfig         = $guthabenHandler->getContractsConfiguration();            // get_GuthabenConfiguration();
	$GuthabenAllgConfig     = $guthabenHandler->getGuthabenConfiguration();                              //aus get_GuthabenAllgemeinConfig() entspricht $this->configuration["CONFIG"]
	//print_r($GuthabenConfig);

    /* ScriptIDs finden für Timer */
	$ParseGuthabenID=IPS_GetScriptIDByName('ParseDreiGuthaben',$CategoryIdApp);
	$GuthabensteuerungID=IPS_GetScriptIDByName('Guthabensteuerung',$CategoryIdApp);
	echo "Guthabensteuerung ScriptID:".$GuthabensteuerungID."\n";
    
    /* Kategorien anlegen, je nach Betriebsart, 
     * default ist none, dann wird nichts ausser dem Nachrichtenverlauf angelegt und gemacht 
     */

    $categoryId_Guthaben        = CreateCategory('Guthaben',        $CategoryIdData, 20);
    $categoryId_GuthabenArchive = CreateCategory('GuthabenArchive', $CategoryIdData, 1000);
    $categoryId_Webfront        = CreateCategory('Webfront',        $CategoryIdData, 1010);
  
	/* 
	 * Variablen für die externe user/Webfront Darstellung generieren, abgeleitet vom Webfront des IPSModuleManagerGUI 
	 * Verwendet den Identifier zum finden der Variablen, Namen nur zufällig gleich
     * function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false) 
     */

	$variableIdStatus        = CreateVariableByName($categoryId_Webfront, GUTHABEN_VAR_ACTION,      3 /*String*/, '',  GUTHABEN_VAR_ACTION,  10, '',   'View1');
	$variableIdModule        = CreateVariableByName($categoryId_Webfront, GUTHABEN_VAR_MODULE,      3 /*String*/, '',  GUTHABEN_VAR_MODULE,  20, '',   '');
	$variableIdInfo          = CreateVariableByName($categoryId_Webfront, GUTHABEN_VAR_INFO,        3 /*String*/, '',  GUTHABEN_VAR_INFO,    30, '',   '');
    if (isset($categoryId_SmartMeter))
        {
    	$variableIdHTML          = CreateVariableByName($categoryId_SmartMeter, GUTHABEN_VAR_HTML, 3 , '~HTMLBox', GUTHABEN_VAR_HTML, 300, '', '<iframe frameborder="0" width="100%" height="600px"  src="../user/Guthabensteuerung/Guthabensteuerung.php"</iframe>' );
        echo "Category SmartMeter vorhanden. Html wird erzeugt : $variableIdHTML\n";
        }
    SetValue($variableIdStatus,'View1');

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$categoryId_Nachrichten     = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 100);
	$input                      = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter        = new Logging($systemDir."Log_Guthabensteuerung.csv",$input);

    $NachrichtenID      = $ipsOps->searchIDbyName("Nachricht",$CategoryIdData);
    $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);
    
    
/********************************************************
 *
 * Setup YahooApi und vielleicht auch andere, die Variablen initialisisern
 *
 *
 *******************************************************************/
    
    if (isset($GuthabenAllgConfig["Api"]))
        {
        $CategoryId_Finance     = CreateCategoryByName($CategoryIdData,'Finance',200);           // sucht oder legt an,($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
        $financeTableID         = CreateVariableByName($CategoryId_Finance,"YahooFinanceTable", 3, "~HTMLBox",false, 10);		// CreateVariable ($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
        $depotTableID           = CreateVariableByName($CategoryId_Finance,"DepotTable", 3, "~HTMLBox",false, 20);		// CreateVariable ($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
        $depotGraphID           = CreateVariableByName($CategoryId_Finance,"DepotGraph", 3, "~HTMLBox",false, 30);		// CreateVariable ($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)

        $vertiButtons=["Update","Calculate","Sort","TargetValues"];
        $buttonIds = $webOps->createSelectButtons($vertiButtons,$CategoryId_Finance, $GuthabensteuerungID);
        /*
        $profilName=$webOps->createButtonProfileByName("Update");
        $updateApiTableID          = CreateVariableByName($CategoryId_Finance,"Update", 1,$profilName,"",10,$GuthabensteuerungID);           // button profile is Integer
        $profilName=$webOps->createButtonProfileByName("Calculate");
        $calculateApiTableID          = CreateVariableByName($CategoryId_Finance,"Calculate", 1,$profilName,"",20,$GuthabensteuerungID);           // button profile is Integer
        $profilName=$webOps->createButtonProfileByName("Sort");
        $sortApiTableID          = CreateVariableByName($CategoryId_Finance,"Sort", 1,$profilName,"",30,$GuthabensteuerungID);           // button profile is Integer
        */
        }

/********************************************************
 *
 * Setup Selenium or iMacro Environment
 *
 * Selenium braucht Zusatzmodule: OperationCenter, Watchdog
 * und includes: "Selenium_Library.class.php"
 * $GuthabenAllgConfig["Selenium"]["WebDrivers"] mehrere Webdriver zum Auswählen
 * $GuthabenAllgConfig["Selenium"]["WebDriver"] oder ein default Webdriver oder beides zusammen
 * nur die Programme ausgeben die gestartet werden müssen, je nachdem wird status selenium eingestellt: SeleniumRunning=[Idle, Active]
 * die Profile bestimmen  UpdateChromeDriver=[None], StartStoppChromeDriver=["Start","Stopp","Reset"]
 *
 *
 *******************************************************************/

    echo "\n";
    echo "Setup Selenium oder imacro Environment. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

    $seleniumWeb=false;
    $SeleniumOnID=false;                            // für den Fall es gibt kein Watchdog Modul
    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {
        case "IMACRO":
            echo "   --> starten mit Installation/Inbetriebnahme iMacro:\n";
            $CategoryId_Mode          = CreateCategory('iMacro',          $CategoryIdData, 90);
            $statusReadID       = CreateVariable("StatusWebread", 3, $CategoryId_iMode,1010,"~HTMLBox",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            $testInputID        = CreateVariable("TestInput", 3, $CategoryId_iMode,1020,"",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            break;
        case "SELENIUM":
            echo "   --> starten mit Installation/Inbetriebnahme Selenium:\n";
            IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
            //echo "Do Init for Operating Mode Selenium.\n";
            $seleniumOperations = new SeleniumOperations();            
            $CategoryId_Mode    = CreateCategory('Selenium',        $CategoryIdData, 90);
            $statusReadID       = CreateVariable("StatusWebread", 3, $CategoryId_Mode,1010,"~HTMLBox",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            if (isset($GuthabenAllgConfig["Selenium"]["WebDrivers"])) 
                {
                echo "Mehrere Webdriver Server konfiguriert.\n";
                $pos=10;
                foreach ($GuthabenAllgConfig["Selenium"]["WebDrivers"] as $category => $entry)
                    {
                    $seleniumWeb=true;
                    $categoryId_WebDriver        = CreateCategory($category,        $CategoryId_Mode, $pos);
                    $sessionID          = CreateVariableByName($categoryId_WebDriver,"SessionId", 3);                       
                    $handleID           = CreateVariableByName($categoryId_WebDriver,"HandleId", 3);  
                    $statusID           = CreateVariable("StatusWebDriver".$category, 3, $categoryId_WebDriver,1010,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')                     
                    SetValue($statusID,"updated");
                    $pos=$pos+10;
                    }
                }
            if (isset($GuthabenAllgConfig["Selenium"]["WebDriver"])) 
                {
                echo "Ein Default Webdriver Server konfiguriert.\n";
                $seleniumWeb=true;
                //$sessionID          = CreateVariable("SessionId", 3, $categoryId_Selenium,1000,"",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
                $sessionID          = CreateVariableByName($CategoryId_Mode,"SessionId", 3);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
                $handleID           = CreateVariableByName($CategoryId_Mode,"HandleId", 3);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
                $statusID           = CreateVariable("StatusWebDriverDefault", 3, $CategoryId_Mode,1010,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')                     
                SetValue($statusID,"updated");
                }
            if ( (isset($installedModules["OperationCenter"])) && (isset($installedModules["Watchdog"])) )
                {
                IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
                IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");

                IPSUtils_Include ("Watchdog_Configuration.inc.php","IPSLibrary::config::modules::Watchdog");
                IPSUtils_Include ("Watchdog_Library.inc.php","IPSLibrary::app::modules::Watchdog");

                $seleniumChromedriverUpdate = new seleniumChromedriverUpdate();     // Watchdog class
                $processDir = $seleniumChromedriverUpdate->getprocessDir();

                $watchDog = new watchDogAutoStart();
                $config = $watchDog->getConfiguration();

                // check if Selenium Java process is running, will be done automatically with timer interogation
                if ($DoWatchdogProcessActiveCheck)                              // nur machen wenn Zeit ist, default maeßig ausgeschaltet
                    {
                    $processes    = $watchDog->getActiveProcesses();
                    $processStart = $watchDog->checkAutostartProgram($processes);
                    echo "Die folgenden Programme muessen gestartet (wenn On) werden:\n";
                    print_r($processStart);
                    }
                    
                $SeleniumStatusID       = CreateVariableByName($CategoryId_Mode,"SeleniumStatus",  3, ""        , "", 120);
                $SeleniumOnID           = CreateVariableByName($CategoryId_Mode,"SeleniumRunning", 3, "",         "", 110);
                $SeleniumHtmlStatusID   = CreateVariableByName($CategoryId_Mode,"Status",          3, "~HTMLBox", "", 600);
                $pname="UpdateChromeDriver";                                         // keine Standardfunktion, da Inhalte Variable
                $nameID=["None"];
                echo "Update Profile $pname with ".json_encode($nameID)."\n";
                $webOps->createActionProfileByName($pname,$nameID,0);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
                $updateChromedriverID          = CreateVariableByName($CategoryId_Mode,"UpdateChromeDriver", 1,$pname,"",120,$GuthabensteuerungID);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)

                $pname="StartStoppChromeDriver";                                         // keine Standardfunktion, da Inhalte Variable
                $nameID=["Start","Stopp","Reset"];
                echo "Update Profile $pname with ".json_encode($nameID)."\n";
                $webOps->createActionProfileByName($pname,$nameID,0);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
                $startstoppChromedriverID      = CreateVariableByName($CategoryId_Mode,"StartStoppChromeDriver", 1,$pname,"",200,$GuthabensteuerungID);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)

                if (isset($processStart["selenium"])) 
                    {
                    if ($processStart["selenium"]=="Off") SetValue($SeleniumOnID,"Active");
                    else SetValue($SeleniumOnID,"Idle");
                    }
                $configWatchdog = $watchDog->getConfiguration();            
                $processDir=$dosOps->correctDirName($configWatchdog["WatchDogDirectory"]);
                echo "Watchdog Directory : $processDir\n";            
                
            //$categoryDreiID = $seleniumOperations->getCategory("DREI");                
            //echo "Category DREI : $categoryDreiID (".IPS_GetName($categoryDreiID).") in ".IPS_GetName(IPS_GetParent($categoryDreiID))."\n";  

                echo "\n";
                echo "OperationCenter Module ist installiert, zusätzliche Funktionen zur Automatisierung Update Chromedriver machen.\n"; 
                $seleniumChromedriver=new SeleniumChromedriver();         // SeleniumChromedriver.OperationCenter Child
                $selDirContent = $seleniumChromedriverUpdate->getSeleniumDirectoryContent();            // erforderlich für version
                $version    = $seleniumChromedriver->getListAvailableChromeDriverVersion();          // alle bekannten Versionen von chromedriver aus dem Verzeichnis erfassen 
                //print_R($version);          // Version Nummer, Filename, Size in Bytes

                $latestVersion=array_key_last($version);
                $cdVersion=(string)$latestVersion;
                $actualVersion = $seleniumChromedriverUpdate->identifyFileByVersion("chromedriver.exe",$version);
                if ($actualVersion != $latestVersion) echo "Update with latest available Chromedriver version \"$cdVersion\" recommended. Actual version is \"$actualVersion\".\n";

                if (isset($configWatchdog["Software"]["Selenium"]["Directory"]))
                    {
                    $selDir=$dosOps->correctDirName($configWatchdog["Software"]["Selenium"]["Directory"]);
                    echo "Watchdog config defines where Selenium is operating: $selDir \n";
                    $dosOps->writeDirStat($selDir);                    // Ausgabe eines Verzeichnis 
                    $selDirContent = $dosOps->readdirToArray($selDir);                   // Inhalt Verzeichnis als Array
                    //print_R($selDirContent);
                    $found = $dosOps->findfiles($selDirContent,"chromedriver-alt.exe");                // true debug, wir suchen ein altes file mit dem minus als Trennzeichen zur Version nicht _
                    if ($found)
                        {
                        echo "Altes chromedriver-xxx.exe gefunden. Wegen Kompatibilität jetzt loeschen \"".$found[0]."\"\n";   
                        $dosOps->deleteFile($selDir.$found[0]);
                        }
                    $found = $dosOps->findfiles($selDirContent,"chromedriver.exe");
                    if ($found)
                        {
                        echo "Datei chromedriver.exe gefunden. Versuche Version zu bestimmen.\n";   
                        $SeleniumUpdate = new SeleniumUpdate();
                        $tabs=$SeleniumUpdate->findTabsfromVersion($version, $actualVersion);
                        echo "   Active Selenium version is $actualVersion . Latest version available $latestVersion \n";
                        SetValue($SeleniumStatusID,"Active Selenium version is $actualVersion . Latest version available $latestVersion ");
                        $pname="UpdateChromeDriver";                                         // keine Standardfunktion, da Inhalte Variable
                        $webOps->createActionProfileByName($pname,$tabs,0);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
                        }
                    }                   // aktuellen Selenium Driver rausfinden 
                }               // OperationCenter ist installiert 
                
            $html = "";                     // Init, auch wenn kein get from google download vorhanden
            $getChromedriverID=false;   
            if ( (isset($GuthabenAllgConfig["Selenium"]["getChromeDriver"])) && ($GuthabenAllgConfig["Selenium"]["getChromeDriver"]) )
                {
                echo "\n";
                echo "Chromedriver automatisch und manuell von Webpage laden.\n";
                $pname="GetChromeDriver";                                         // keine Standardfunktion, da Inhalte Variable
                $nameID=["Get"];
                echo "Update Profile $pname with ".json_encode($nameID)."\n";
                $webOps->createActionProfileByName($pname,$nameID,0);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
                $getChromedriverID          = CreateVariableByName($CategoryId_Mode,"GetChromeDriver", 1,$pname,"",125,$GuthabensteuerungID);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
                // vorhandene Versionen, Details über heruntergeladene Version hier speichern
                $configChromedriverID       = CreateVariableByName($CategoryId_Mode,"ConfigChromeDriver", 3,"","",124);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)


                /**************************** es werden chromedriver Versionen automatisch geladen, 
                * geht auch über GetChromeDriver Taste Get und Guthabensteuerung Script, siehe Zeile if ($getChromedriver)
                * Funktion über Taste ist optimiert und verwendet das Array in json Variable ConfigChromeDriver um die aktuelle gespeicherten Versionen herauszufinden
                *
                * hier das Verzeichnis download dafür machen, Target verzeichnis                   
                * im target Verzeichnis 7zip installieren, implizierte Statemaschine duch mehrmaliges Aufrufen
                *          download 7zr 
                *          download 7za Archiv
                *          erstellen Batchfile zum entpacken 7za
                *          unzip 7za mit Batchfile, dient für entpacken von .zip Files
                *
                ***************************************/

                $html = "";
                $dosOps = new dosOps();
                $sysOps = new sysOps();
                $curlOps = new curlOps();             

                $SeleniumUpdate = new SeleniumUpdate();
                echo "\n";
                echo "Install Environment: unzip programme 7zr, 7za und unzip_chromedriver.bat \n";
                $result = $SeleniumUpdate->installEnvironment($GuthabenAllgConfig["Selenium"]["DownloadDir"],$debug);              // Selenium Library function
                $html .= $result;
                
                $dir=$GuthabenAllgConfig["Selenium"]["DownloadDir"];

                $execDir=$seleniumChromedriver->get_ExecDir();
                if ($watchDog->isSeleniumServer($execDir)) echo "Selenium Server jar bereits vorhanden.\n";
                
                echo "Bestehende chromedriver Versionen aus dem shared drive ausgeben. Verzeichnis Synology/Shared Drive : ".$execDir."\n";
                $dosOps->writeDirStat($execDir);                    // Ausgabe Directory ohne Debug bei writeDirToArray einzustellen

                //verfügbare chromedriver versionen herunterladen
                $result = $seleniumChromedriver->getListDownloadableChromeDriverVersion();

                echo "Aktuelles lokales Downloaddir: $dir\n";
                $dosOps->writeDirStat($dir);

                echo "Ergebnisse mit detaillierten Informationen über den Inhalt der Downloadpage abspeichern:\n";
                //foreach ($result as $nr => $entry) echo str_pad($nr,5).str_pad($entry["version"],25)."\n";          // url zweiter Parameter
                //print_r($result);
                $extraDebug=false;
                foreach ($result as $version => $entry)
                    {
                    echo "   Check Version ".str_pad($version,5).str_pad($entry["version"],25)."  ";
                    $files = $dosOps->writeDirToArray($dir);        // bessere Funktion
                    if ($extraDebug) $dosOps->writeDirStat($dir);                    // Ausgabe Directory ohne Debug bei writeDirToArray einzustellen
                    $filename="chromedriver-win64.zip";
                    $file = $dosOps->findfiles($files,$filename,($extraDebug>1));       //Debug
                    if ($file) 
                        {
                        echo "    ---> delete file.\n";
                        $dosOps->deleteFile($dir.$filename);
                        }
                    $file = $dosOps->findfiles($files,"chromedriver_$version.exe",($extraDebug>1));       //Debug
                    if ($file) 
                        {
                        echo "    --> File $version vorhanden.\n";
                        $html .= "Chromedriver File available : $version <br>";                  // das ist das Arbeitsverzeichnis, nicht das Sync drive 
                        }
                    else
                        {
                        echo "Url laden.\n";
                        $curlOps->downloadFile($entry["url"],$dir);
                        $files = $dosOps->writeDirToArray($dir);        // bessere Funktion
                        $file = $dosOps->findfiles($files,$filename,true);       //Debug
                        if ($file) 
                            {
                            echo "   --> Datei $filename für version $version gefunden.\n";
                            $commandName="unzip_chromedriver.bat";
                            $ergebnis = "not started";
                            $ergebnis = $sysOps->ExecuteUserCommand($dir.$commandName,"",true,true,-1,true);             // parameter show wait -1 debug
                            echo "Execute Batch $dir$filename \"$ergebnis\"\n";
                            $dirname=$dir."chromedriver-win64/";
                            if (is_dir($dirname))
                                {
                                echo "Process result of unzip in $dirname.\n";
                                $files = $dosOps->writeDirToArray($dirname);        // bessere Funktion
                                $dosOps->writeDirStat($dirname);                    // Ausgabe Directory ohne Debug bei writeDirToArray einzustellen
                                echo "moveFile ".$dirname."chromedriver.exe to ".$dir."chromedriver_$version.exe\n";
                                //$dosOps->moveFile($dirname."chromedriver.exe",$dir."chromedriver_$version.exe");
                                if (!copy($dirname."chromedriver.exe",$dir."chromedriver_$version.exe")) echo "failed to copy ".$dirname."chromedriver.exe...\n";
                                //echo "rename  ".$dir."chromedriver.exe to ".$dir."chromedriver_$version.exe\n";
                                //rename($dir."chromedriver.exe",$dir."chromedriver_".$version.".exe");
                                $dosOps->rrmdir($dirname);
                                echo "    -> finished, result and $dirname deleted.\n";
                                }
                            else 
                                {
                                echo "Dir $dirname not found. Try to create.\n"; 
                                $dosOps->mkdirtree($dirname);
                                }
                        
                            $dosOps->deleteFile($dir.$filename);
                            $files = $dosOps->writeDirToArray($dir);        // bessere Funktion
                            }
                        }
                    }       // ende foreach

                //print_R($result);
                if ($copyToSharedDrive)
                    {
                    $html .= "Copy to sharedrive possible : $execDir <br>";                  
                    foreach ($result as $version => $entry)
                        {
                        if (file_exists($execDir."chromedriver_$version.exe")) echo "Version $version bereits vorhhanden, nicht überschreiben.\n";
                        else copy ($dir."chromedriver_$version.exe",$execDir."chromedriver_$version.exe");
                        }
                    }
                else $html .= "Copy to sharedrive not activated : $execDir <br>";  
                }
            else $html .= "Get from Googles Chromedriver Downloadpage not activated, set Selenium->getChromeDriver <br>";                 
            break;
        case "NONE":
            $DoInstall=false;
            break;
        default:    
            echo "Guthaben Mode \"".$GuthabenAllgConfig["OperatingMode"]."\" not supported.\n";
            break;
        }
	SetValue($SeleniumHtmlStatusID,$html);

/*****************************************************
 *
 * initialize Timer 
 *
 ******************************************************************/


    // Timer installieren
	$timer = new timerOps();
    
    $tim1ID   = $timer->CreateTimerHour("Aufruftimer",2,rand(1,59),$GuthabensteuerungID);
    $tim12ID  = $timer->CreateTimerHour("Lunchtimer",13,rand(1,59),$GuthabensteuerungID);
    $tim2ID   = $timer->CreateTimerSync("Exectimer",150,$GuthabensteuerungID);
    $tim3ID   = $timer->CreateTimerHour("EveningCallTimer",22,rand(1,59),$GuthabensteuerungID);
    $tim4ID   = $timer->CreateTimerHour("AufrufMorgens",4,55,$GuthabensteuerungID);
    $tim5ID   = $timer->CreateTimerSync("Tasktimer",50,$GuthabensteuerungID);        // Tasks wie YahooFin ein wenig entkoppeln, 310 Sekundne war zu lange

    $ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];


/****************************************************************
 *
 * Initialisiere Profile
 *
 ************************************************************************/

	$profileOps->createKnownProfilesByName("Euro");
	$profileOps->createKnownProfilesByName("MByte");

	/* Create Web Pages */
    echo "Installed Webpages: ";
	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	if ($WFC10_Enabled==true)
		{
		$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
        $WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
		echo "\nWF10 ";
		}

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		echo "WF10User ";
		}
		
	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled==true)
		{
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile ";
		}

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled==true)
		{
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro ";
		}
    echo "\n";
	//echo "Test";

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

/*****************************************************
 *
 * Selenium Webdriver initilisieren
 *
 ******************************************************************/

    if ($seleniumWeb)
        {
        echo "\n";
        echo " Selenium Webdriver initialisieren. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
        // Es gibt Selenium Webdriver, die kann man wieder starten, so wie bei Guthaben StartSelenium,  CreateVariable("StartSelenium", 1, $CategoryId_Mode,1000,$pname,$GuthabensteuerungID,null,""
        $pname="SeleniumAktionen";                                         // keine Standardfunktion, da Inhalte Variable

        $configSeleniumHosts = $guthabenHandler->getSeleniumHostsConfig();
        $configTabs = $configSeleniumHosts["Hosts"];
        $nameID=array();
        foreach ($configTabs as $tab => $config) $nameID[]=$tab;
        //$nameID=["Easy","YahooFin", "EVN"];

        $webOps->createActionProfileByName($pname,$nameID,0);               // erst das Profil, dann die Variable, 0 ohne Selektor
        $actionWebID          = CreateVariableByName($CategoryId_Mode,"StartAction", 1,$pname,"",1000,$GuthabensteuerungID);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)

        $pname="SeleniumGruppen";                                         // keine Standardfunktion, da Inhalte Variable
        $nameID=["morning","lunchtime", "evening"];
        $webOps->createActionProfileByName($pname,$nameID,0);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
        $actionGroupWebID          = CreateVariableByName($CategoryId_Mode,"StartGroupCall", 1,$pname,"",1010,$GuthabensteuerungID);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)

        echo " Config der einzelenen Hosts durchgehen und eventuell Verzeichnissse erzeugen:\n";

        $runSelenium=array();
        foreach ($configSeleniumHosts["Hosts"] as $host=>$config)
            {
            $categoryID = $seleniumOperations->getCategory($host);          // legt auch gleichzeitig die Kategorie an
            echo "Host ".str_pad($host,22)."| $categoryID  ".json_encode($config)."\n";
            $runSelenium[$host] = new $config["CLASS"]();
            $runSelenium[$host]->setConfiguration($config);
            }
        echo "=========================================\n";        
        foreach ($configSeleniumHosts["Hosts"] as $host=>$config)
            {
            $config=$runSelenium[$host]->getConfiguration();
            echo "Host ".str_pad($host,22)."|   ".json_encode($config)."\n";
            $configInput=false;
            if (isset($config["INPUTJSON"]["InputDir"])) $configInput=$config["INPUTJSON"];
            if (isset($config["INPUTCSV"]["InputDir"])) $configInput=$config["INPUTCSV"];
            if ($configInput)
                {
                $inputDir = $runSelenium[$host]->getDirInputJson($configInput,$debug);
                $status=is_dir($inputDir);
                echo "Directory found: $inputDir ".($status?"Available":"Not Avail")."\n";
                if ($status===false) $dosOps->mkdirtree($inputDir);
                }
            }                
        }

/*****************************************************
 *
 * if doinstall, iMacro Telefonnummern abfragen 
 *
 ******************************************************************/

    if ($DoInstall)         // siehe weiter oben, lokaler Switch
        {
        /* die Simkartendaten in Archive und Guthaben speichern bzw aus dem Data dorthin verschieben */
        
        $phoneID=array();           // wird für die Links im Webfront verwendet, nur die aktiven SIM Karten bekommen einen Link
        $i=0;
        echo "Folgende Telefonnummer haben aktiven Status und werden bearbeitet:\n";
        foreach ($GuthabenConfig as $TelNummer)
            {   /* nur für die noch aktiven Nummern die Scripts anlegen und auch im Webfront darstellen */
            switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
                {
                case "SELENIUM":
                    /* verkuerzte Installation pro Telefonnummer aus der GuthabenConfig ohne iMacro iim files etc */
                    break;
                default:
                    $handle2=fopen($GuthabenAllgConfig["MacroDirectory"]."dreiat_".$TelNummer["Nummer"].".iim","w");
                    fwrite($handle2,'VERSION BUILD=8970419 RECORDER=FX'."\n");
                    fwrite($handle2,'TAB T=1'."\n");
                    fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
                    fwrite($handle2,'SET !EXTRACT NULL'."\n");
                    fwrite($handle2,'SET !VAR0 '.$TelNummer["NUMMER"]."\n");
                    fwrite($handle2,'ADD !EXTRACT {{!VAR0}}'."\n");
                    if ( strtoupper($TelNummer["Typ"]) == "DREI" )
                        {
                        //fwrite($handle2,'URL GOTO=https://www.drei.at/'."\n");
                        fwrite($handle2,'URL GOTO=https://www.drei.at/selfcare/restricted/prepareMyProfile.do'."\n");			
                        //fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:Kundenzone'."\n");		// alte version vor Sep 2018
                        fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:Kundenzone'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=ID:loginForm ATTR=ID:userName CONTENT='.$TelNummer["Nummer"]."\n");
                        fwrite($handle2,'SET !ENCRYPTION NO'."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=ID:loginForm ATTR=ID:password CONTENT='.$TelNummer["Password"]."\n");
                        fwrite($handle2,'TAG POS=1 TYPE=BUTTON FORM=ID:loginForm ATTR=TXT:Login'."\n");
                        fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_dreiat_{{!VAR0}}'."\n");
                        fwrite($handle2,'\'Ausloggen'."\n");
                        fwrite($handle2,'URL GOTO=https://www.drei.at/selfcare/restricted/prepareMainPage.do'."\n");
                        fwrite($handle2,'TAG POS=2 TYPE=A ATTR=TXT:Kundenzone'."\n");			
                        fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:logout'."\n");
                        }
                    else		// UPC oder anderer Anbieter
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
                        }	
                    fwrite($handle2,'TAB CLOSE'."\n");
                    fwrite($handle2,'TAB CLOSE'."\n");
                    fclose($handle2);
                    break;
                }	// ende switch

            //$guthabenHandler->createVariableGuthaben($TelNummer["Nummer"]));       //alle aktiven Variablen anlegen und Ergebnisse sammeln

            if 	( (strtoupper( $TelNummer["Status"])) == "ACTIVE")          // egal ob Selenium oder Imacro
                {
                $phone1ID = CreateVariableByName($categoryId_Guthaben, "Phone_".$TelNummer["Nummer"], 3);
                $phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Summary", 3);
                $phoneID[$i]["Nummer"]=$TelNummer["Nummer"];
                $phoneID[$i]["Short"]=substr($TelNummer["Nummer"],(strlen($TelNummer["Nummer"])-3),10);
                $phoneID[$i]["Summ"]=$phone_Summ_ID;
                echo "   $i : ".$TelNummer["Nummer"]."   $phone_Summ_ID   abgespeichert in $phone1ID      \n";	                    
                $phone_User_ID          = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_User", 3);
                $phone_Status_ID        = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Status", 3);
                $phone_Date_ID          = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Date", 3);
                $phone_unchangedDate_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_unchangedDate", 3);
                $phone_Bonus_ID         = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Bonus", 3);
                $ldateID                = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_loadDate", 3);

                $phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Volume", 2);
                IPS_SetVariableCustomProfile($phone_Volume_ID,'MByte');
                AC_SetLoggingStatus($archiveHandlerID,$phone_Volume_ID,true);
                AC_SetAggregationType($archiveHandlerID,$phone_Volume_ID,0);
                IPS_ApplyChanges($archiveHandlerID);

                $phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_VolumeCumm", 2);
                IPS_SetVariableCustomProfile($phone_VolumeCumm_ID,'MByte');
                AC_SetLoggingStatus($archiveHandlerID,$phone_VolumeCumm_ID,true);
                AC_SetAggregationType($archiveHandlerID,$phone_VolumeCumm_ID,0);
                IPS_ApplyChanges($archiveHandlerID);

                $phone_nCost_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Cost", 2);
                IPS_SetVariableCustomProfile($phone_nCost_ID,'Euro');
                IPS_SetPosition($phone_nCost_ID, 130);
                AC_SetLoggingStatus($archiveHandlerID,$phone_nCost_ID,true);
                AC_SetAggregationType($archiveHandlerID,$phone_nCost_ID,0);
                IPS_ApplyChanges($archiveHandlerID);

                $phone_nLoad_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Load", 2);
                IPS_SetVariableCustomProfile($phone_nLoad_ID,'Euro');
                IPS_SetPosition($phone_nLoad_ID, 140);
                AC_SetLoggingStatus($archiveHandlerID,$phone_nLoad_ID,true);
                AC_SetAggregationType($archiveHandlerID,$phone_nLoad_ID,0);
                IPS_ApplyChanges($archiveHandlerID);
                }
            $i++;
            }
        $maxcount=$i;
        echo "Insgesamt $maxcount Einträge.\n";
        
        $phone_CL_Change_ID = CreateVariableByName($CategoryIdData, "Phone_CL_Change", 2);
        IPS_SetVariableCustomProfile($phone_CL_Change_ID,'Euro');
        
        $phone_Cost_ID = CreateVariableByName($CategoryIdData, "Phone_Cost", 2);
        IPS_SetVariableCustomProfile($phone_Cost_ID,'Euro');
        AC_SetLoggingStatus($archiveHandlerID,$phone_Cost_ID,true);
        AC_SetAggregationType($archiveHandlerID,$phone_Cost_ID,0);
        IPS_ApplyChanges($archiveHandlerID);
        
        $phone_Load_ID = CreateVariableByName($CategoryIdData, "Phone_Load", 2);
        IPS_SetVariableCustomProfile($phone_Load_ID,'Euro');
        AC_SetLoggingStatus($archiveHandlerID,$phone_Load_ID,true);
        AC_SetAggregationType($archiveHandlerID,$phone_Load_ID,0);
        IPS_ApplyChanges($archiveHandlerID);

        $pname="GuthabenKonto";                                         // keine Statndardfunktion, da Inhalte Variable
        $nameID=array();
        for ($i=0;$i<$maxcount;$i++)
            {
            $nameID[$i]=$phoneID[$i]["Short"];
            }
        $i++;       // sonst wird letzter Wert überschrieben	
        $nameID[$i++]="Alle";
        $nameID[$i++]="Test";
        $webOps->createActionProfileByName($pname,$nameID);

        if ((strtoupper($GuthabenAllgConfig["OperatingMode"]))=="SELENIUM")
            {
            echo "Start Selenium Webdriver Running check Timer setup.\n";
            $tim22ID   = $timer->setTimerPerMinute("CheckAvailtimer",$GuthabensteuerungID,180);
            $startImacroID      = CreateVariable("StartSelenium", 1, $CategoryId_Mode,1000,$pname,$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            }
        else
            {
            $startImacroID      = CreateVariable("StartImacro", 1, $CategoryId_Mode,1000,$pname,$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            }
        }

/******************************************************
 *
 *		WebFront Installation, display of Guthabensteuerung Information
 *
 *************************************************************/	

    if ($DoInstall && ($GuthabenAllgConfig["EvaluateGuthaben"]))          // nur wenn auch explizit eine Auswertung der Tel Nummernkonten erwünscht ist
        {
        if ($WFC10_Enabled) 
            {
            echo "\nWebportal Administrator installieren auf ".$WFC10_Path.": \n";
            $categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
            $phone_summary_ID=@IPS_GetVariableIDByName("Summary",$categoryId_WebFront);
            if ($phone_summary_ID !== false)
                {
                echo "Variable Summary loeschen.\n";
                EmptyCategory($phone_summary_ID);
                IPS_DeleteVariable($phone_summary_ID);
                }
            else
                {
                echo "Variable Summary neu anlegen.\n";
                }			
            EmptyCategory($categoryId_WebFront);											
            $phone_summary_ID = CreateVariableByName($categoryId_WebFront, "Summary", 3);
            foreach ($phoneID as $phone)
                {
                CreateLinkByDestination(IPS_GetName($phone["Summ"]), $phone["Summ"],    $phone_summary_ID,  10);
                }
            CreateLinkByDestination(IPS_GetName($phone_Cost_ID), $phone_Cost_ID, $categoryId_WebFront,  20);
                                                                    
                
                                    
            CreateLinkByDestination(IPS_GetName($startImacroID), $startImacroID, $categoryId_WebFront,  30);
            CreateLinkByDestination(IPS_GetName($statusReadID), $statusReadID, $categoryId_WebFront,  40);
            //CreateLinkByDestination(IPS_GetName($testInputID), $testInputID, $categoryId_WebFront,  50);
                        
            }

        if ($WFC10User_Enabled) 
            {
            echo "\nWebportal User installieren auf ".$WFC10User_Path.": \n";
            $categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);
            $phone_summary_ID=@IPS_GetVariableIDByName("Summary",$categoryId_WebFront);
            if ($phone_summary_ID !== false)
                {
                echo "Variable Summary loeschen.\n";
                EmptyCategory($phone_summary_ID);
                IPS_DeleteVariable($phone_summary_ID);
                }
            else
                {
                echo "Variable Summary neu anlegen.\n";
                }			
            $phone_summary_ID = CreateVariableByName($categoryId_WebFront, "Summary", 3);
            foreach ($phoneID as $phone)
                {
                CreateLinkByDestination(IPS_GetName($phone["Summ"]), $phone["Summ"],    $phone_summary_ID,  10);
                }
            CreateLinkByDestination(IPS_GetName($phone_Cost_ID), $phone_Cost_ID,    $categoryId_WebFront,  20);
            }

        if ($Mobile_Enabled) 
            {
            echo "\nWebportal Mobile installieren auf ".$Mobile_Path.": \n";
            $categoryId_WebFront         = CreateCategoryPath($Mobile_Path);
            $phone_summary_ID=@IPS_GetVariableIDByName("Summary",$categoryId_WebFront);
            if ($phone_summary_ID !== false)
                {
                echo "Variable Summary loeschen.\n";
                EmptyCategory($phone_summary_ID);
                IPS_DeleteVariable($phone_summary_ID);
                }
            else
                {
                echo "Variable Summary neu anlegen.\n";
                }			
            $phone_summary_ID = CreateVariableByName($categoryId_WebFront, "Summary", 3);
            foreach ($phoneID as $phone)
                {
                CreateLinkByDestination(IPS_GetName($phone["Summ"]), $phone["Summ"],    $phone_summary_ID,  10);
                }
            CreateLinkByDestination(IPS_GetName($phone_Cost_ID), $phone_Cost_ID,    $categoryId_WebFront,  20);
            }

        if ($Retro_Enabled) 
            {
            echo "\nWebportal Retro installieren auf ".$Retro_Path.": \n";
            createPortal($Retro_Path);
            }
        }           // nur wenn Auswertung telnummern

/******************************************************
 *
 *			INIT, Nachrichtenspeicher, Ausgeben wenn Execute
 *
 *************************************************************/

	if ($_IPS['SENDER']=="Execute")
		{
        echo "\n";
        echo "---------Nachrichtenspeicher------------------\n";
		echo 	$log_OperationCenter->PrintNachrichten();
        echo "-----------------------------------------------\n";
		}



/**************************************************
 *
 * Selenium Webfront initialisieren
 *
 * Guthabensteuerung und Selenium wird hier überwacht, Anzeige erfolgt im SystemTP
 * erfordert (strtoupper($GuthabenAllgConfig["OperatingMode"]))=="SELENIUM")
 * zwei Webfronts:
 *      benötigt upgedatetes Orderbook
 *      Parameter für Webfront von hier : $configWF=$guthabenHandler->getWebfrontsConfiguration("Selenium");
 *      am Ende Aufruf easySetupWebfront($configWF,$webfront_links
 * zweites Webfront:
 *      Webfront Api
 *      Parameter für Webfront von hier :    $configWF=$guthabenHandler->getWebfrontsConfiguration("Api"
 *      am Ende Aufruf easySetupWebfront($configWF,$webfront_links
 *
 **********************************************************/

    echo " Selenium Webfront initialisieren. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

    /*
    echo "\n";
    echo "Status Evaluierung, check ob Guthabensteuerung und Selenium vorhanden sind:\n";
    if (isset ($installedModules["Guthabensteuerung"])) 
        { 
        echo "Modul Guthabensteuerung ist installiert.\n"; 
        IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
        IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
        IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

        $guthabenHandler = new GuthabenHandler(true,true,true);         // Steuerung für parsetxtfile
        $GuthabenAllgConfig     = $guthabenHandler->getGuthabenConfiguration();                              //get_GuthabenAllgemeinConfig();
        }
    else 
        { 
        echo "Modul Guthabensteuerung ist NICHT installiert.\n"; 
        }
     */

    //$wfcHandling =  new WfcHandling($WFC10_ConfigId);         // alte legacy Applikation, es werden die WFC_ Befehle aufgerurfen

    $wfcHandling =  new WfcHandling();                                  // neue Verarbeitung, flexibler beim Update von CategoryID
    /* Workaround wenn im Webfront die Root fehlt 
     */
    $WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();   

    if ((strtoupper($GuthabenAllgConfig["OperatingMode"]))=="SELENIUM")
        {
        // actualDepot is created from order book
        $seleniumEasycharts = new SeleniumEasycharts();
        $orderbook=$seleniumEasycharts->getEasychartOrderConfiguration();
        if (count($orderbook)>0)
            {
            $depotbook=$seleniumEasycharts->createDepotBookfromOrderBook();
            $seleniumEasycharts->updateResultConfigurationSplit($depotbook);    
            $seleniumEasycharts->writeResultConfiguration($depotbook,"actualDepot");          //    freigeben wenn orderbook upgedated wurde        
            }
            
        /* wird auch für die nächste Abfrage benötigt 
        * zuerst aus dem ModulManager die Konfig von IPSModuleManagerGUI abrufen 
        $moduleManagerGUI = new IPSModuleManager('IPSModuleManagerGUI',$repository);
        $configWFrontGUI=$ipsOps->configWebfront($moduleManagerGUI,false);     // wenn true mit debug Funktion
        $tabPaneParent="roottp";                        // Default Wert

        $configWF=array();                                      // für die Verwendung vorbereiten
        if (isset($configWFrontGUI["Administrator"]))
            {
            $tabPaneParent=$configWFrontGUI["Administrator"]["TabPaneItem"];
            echo "  Selenium Module Überblick im Administrator Webfront $tabPaneParent abspeichern.\n";
            //print_r($configWFrontGUI["Administrator"]);   

            // es gibt kein Module mit Selenium ini Dateien, daher etwas improvisieren und fixe Namen nehmen 
            $configWF["Enabled"]=true;
            $configWF["Path"]="Visualization.WebFront.Administrator.Selenium";
            $configWF["ConfigId"]=$WebfrontConfigID["Administrator"];              
            $configWF["TabPaneParent"]=$tabPaneParent;
            $configWF["TabPaneItem"]="Selenium"; 
            $configWF["TabPaneOrder"]=1010;                                          
            } */

        $configWF=$guthabenHandler->getWebfrontsConfiguration("Selenium");
        if ($configWF)
            {
            echo "Selenium Webfront augestalten, Dateien sind in Kategorie $CategoryId_Mode:\n";
            /* Selenium Stationen auswerten */
            $webfront_links=array(
                        "Selenium" => array(
                            "Auswertung" => array(),
                            "Nachrichten" => array(),
                            // kein Config wenn eh schon Auswertung und Nachrichten vorhanden
                                ),
                        // kein Config damit ein Tabpane entsteht
                );
            $webfront_links["Selenium"]["Nachrichten"] = array(
                $NachrichtenInputID => array(
                        "NAME"				=> "Nachrichten",
                        "ORDER"				=> 10,
                        "ADMINISTRATOR" 	=> true,
                        "USER"				=> false,
                        "MOBILE"			=> false,
                            ),
                        );	
            if (isset($GuthabenAllgConfig["Selenium"]["WebDrivers"])) 
                {
                $order=100;
                foreach ($GuthabenAllgConfig["Selenium"]["WebDrivers"] as $category => $entry)
                    {
                    $categoryId_WebDriver        = CreateCategory($category,        $CategoryId_Mode, $pos);                    
                    $statusID           = IPS_GetObjectIdByName("StatusWebDriver".$category,$categoryId_WebDriver);
                    echo "      Untergruppe StatusWebDriver".$category." :  $statusID in $categoryId_WebDriver\n";  
                    $webfront_links["Selenium"]["Auswertung"][$statusID]["NAME"]="StatusWebDriver".$category;
                    $webfront_links["Selenium"]["Auswertung"][$statusID]["ORDER"]=$order;
                    $webfront_links["Selenium"]["Auswertung"][$statusID]["ADMINISTRATOR"]=true;
                    $order=$order+10;
                    }
                }
            if (isset($GuthabenAllgConfig["Selenium"]["WebDriver"])) 
                {
                echo "     StatusWebDriverDefault    $statusID\n";
                $statusID           = IPS_GetObjectIdByName("StatusWebDriverDefault",$CategoryId_Mode);
                $webfront_links["Selenium"]["Auswertung"][$statusID]["NAME"]="StatusWebDriverDefault";
                $webfront_links["Selenium"]["Auswertung"][$statusID]["ORDER"]=90;
                $webfront_links["Selenium"]["Auswertung"][$statusID]["ADMINISTRATOR"]=true;
                }
            if ($seleniumWeb)
                {
                $statusID           = IPS_GetObjectIdByName("StartAction",$CategoryId_Mode);
                echo "     StatusWebDriverDefault   $statusID\n";
                $webfront_links["Selenium"]["Auswertung"][$statusID]["NAME"]="StartAction";
                $webfront_links["Selenium"]["Auswertung"][$statusID]["ORDER"]=200;
                $webfront_links["Selenium"]["Auswertung"][$statusID]["ADMINISTRATOR"]=true;
                $statusID           = IPS_GetObjectIdByName("StartGroupCall",$CategoryId_Mode);
                echo "     StartGroupCall   $statusID\n";
                $webfront_links["Selenium"]["Auswertung"][$statusID]["NAME"]="StartGroupCall";
                $webfront_links["Selenium"]["Auswertung"][$statusID]["ORDER"]=210;
                $webfront_links["Selenium"]["Auswertung"][$statusID]["ADMINISTRATOR"]=true;
                }
            if ($SeleniumOnID)          // wie SeleniumWeb, aber nicht false wenn Watchdog Modul vorhanden 
                {
                echo "     Selenium Process active : ".GetValue($SeleniumOnID)."\n";
                $webfront_links["Selenium"]["Auswertung"][$SeleniumOnID]["NAME"]="Selenium Process Active";
                $webfront_links["Selenium"]["Auswertung"][$SeleniumOnID]["ORDER"]=10;
                $webfront_links["Selenium"]["Auswertung"][$SeleniumOnID]["ADMINISTRATOR"]=true;

                $webfront_links["Selenium"]["Auswertung"][$SeleniumStatusID]["NAME"]="Selenium Status Information";
                $webfront_links["Selenium"]["Auswertung"][$SeleniumStatusID]["ORDER"]=20;
                $webfront_links["Selenium"]["Auswertung"][$SeleniumStatusID]["ADMINISTRATOR"]=true;

                $webfront_links["Selenium"]["Auswertung"][$updateChromedriverID]["NAME"]="Update Chromedriver to";
                $webfront_links["Selenium"]["Auswertung"][$updateChromedriverID]["ORDER"]=30;
                $webfront_links["Selenium"]["Auswertung"][$updateChromedriverID]["ADMINISTRATOR"]=true;                

                $webfront_links["Selenium"]["Auswertung"][$SeleniumHtmlStatusID]["NAME"]="Status Informationen über Selenium";
                $webfront_links["Selenium"]["Auswertung"][$SeleniumHtmlStatusID]["ORDER"]=40;
                $webfront_links["Selenium"]["Auswertung"][$SeleniumHtmlStatusID]["ADMINISTRATOR"]=true;     

                $webfront_links["Selenium"]["Auswertung"][$startstoppChromedriverID]["NAME"]="StartStopp Chromedriver";
                $webfront_links["Selenium"]["Auswertung"][$startstoppChromedriverID]["ORDER"]=800;
                $webfront_links["Selenium"]["Auswertung"][$startstoppChromedriverID]["ADMINISTRATOR"]=true;           
                }
            if ($getChromedriverID)          // Chromedriver versionen nachladen in Synology Drive nicht auf jedem PC 
                {
                $webfront_links["Selenium"]["Auswertung"][$getChromedriverID]["NAME"]="Load New Chromedriver Versions from Webpage";
                $webfront_links["Selenium"]["Auswertung"][$getChromedriverID]["ORDER"]=700;
                $webfront_links["Selenium"]["Auswertung"][$getChromedriverID]["ADMINISTRATOR"]=true;                
                }
            echo "Konfigurierte Webdriver, überpüfen ob vorhanden und aktiv :\n";
            $webDrivers=$guthabenHandler->getSeleniumWebDrivers();   
            print_R($webDrivers);
            
            $configSelenium = $guthabenHandler->getSeleniumWebDriverConfig();
            $webDriverUrl   = $configSelenium["WebDriver"];
            echo "Default Web Driver Url : $webDriverUrl\n";
            
            /* WebDriver starten */
            $seleniumHandler = new SeleniumHandler();           // Selenium Test Handler, false deaktiviere Ansteuerung von webdriver für Testzwecke vollstaendig
            $result = $seleniumHandler->initHost($webDriverUrl,$configSelenium["Browser"]);          // ersult sind der Return wert von syncHandles
            if ($result === false) echo "---------\n".$seleniumHandler->readFailure()."\n---------------------\n";
            else echo "Selenium Webdriver ordnungsgemaess gestartet.\n";

            echo "Webfront Selenium do install:\n";
            $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, kein interop mode, ist in der Kopie der Config in der class

            if ($DoDelete)          // alles was mit Money anfängt löschen
                {
                echo "Delete Panes starting with ".$configWF["TabPaneItem"]."\n";
                $wfcHandling->deletePane($configWF["TabPaneItem"]);              /* alle Spuren von vormals beseitigen */
                }
            //print_R($webfront_links);

            $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            //true für Debug
            $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);                    // funktioniert, Ergebnis der Änderungen wird abgespeichert
            echo "Webfront Selenium successfull installed.\n";
            }

        $configWF=$guthabenHandler->getWebfrontsConfiguration("Api",false);            // true für Debug
        //print_R($configWF); 
        if ($configWF)
            {
            $webfront_links=array(
                            "Finance" => array(
                                "Left" => array(),
                                "Right" => array(),
                                "CONFIG" => array(
                                            "right" => "Right",
                                            "left" => "Left",
                                            "width" => 10,
                                                ), 
                                    ),
                            "CONFIG" => array(

                            ),                // sonst wird Finance ein Category Pane und kein wie gewollt Splitpane
                    );
            if ($buttonIds)                 // es wurden Buttons für die Linke Seite erstellt
                {
                $order=200;    
                foreach ($buttonIds as $id => $button)
                    {
                    $order += 10;
                    $webfront_links["Finance"]["Right"][$button["ID"]]["NAME"]=" ";
                    $webfront_links["Finance"]["Right"][$button["ID"]]["ORDER"]=$order;
                    $webfront_links["Finance"]["Right"][$button["ID"]]["ADMINISTRATOR"]=true;
                    }

                /*  $webfront_links["Finance"]["Right"][$updateApiTableID]["NAME"]=" ";
                    $webfront_links["Finance"]["Right"][$updateApiTableID]["ORDER"]=200;
                    $webfront_links["Finance"]["Right"][$updateApiTableID]["ADMINISTRATOR"]=true;

                    $webfront_links["Finance"]["Right"][$calculateApiTableID]["NAME"]=" ";
                    $webfront_links["Finance"]["Right"][$calculateApiTableID]["ORDER"]=210;
                    $webfront_links["Finance"]["Right"][$calculateApiTableID]["ADMINISTRATOR"]=true;

                    $webfront_links["Finance"]["Right"][$sortApiTableID]["NAME"]=" ";
                    $webfront_links["Finance"]["Right"][$sortApiTableID]["ORDER"]=220;
                    $webfront_links["Finance"]["Right"][$sortApiTableID]["ADMINISTRATOR"]=true;*/
                }
            $webfront_links["Finance"]["Left"][$financeTableID]["NAME"]="FinanceTable";
            $webfront_links["Finance"]["Left"][$financeTableID]["ORDER"]=200;
            $webfront_links["Finance"]["Left"][$financeTableID]["ADMINISTRATOR"]=true;

            $webfront_links["Finance"]["Left"][$depotTableID]["NAME"]="DepotTable";
            $webfront_links["Finance"]["Left"][$depotTableID]["ORDER"]=220;
            $webfront_links["Finance"]["Left"][$depotTableID]["ADMINISTRATOR"]=true;

            $webfront_links["Finance"]["Left"][$depotGraphID]["NAME"]="DepotGraph";
            $webfront_links["Finance"]["Left"][$depotGraphID]["ORDER"]=240;
            $webfront_links["Finance"]["Left"][$depotGraphID]["ADMINISTRATOR"]=true;


            $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID, kein interop mode, ist in der Kopie der Config in der class
            
            if (false)          // comment to view structure of webfront
                {
                $wfc=$wfcHandling->read_wfcByInstance(false,1);                 // false interne Datanbank für Config nehmen
                foreach ($wfc as $index => $entry)                              // Index ist User, Administrator
                    {
                    echo "\n------$index:\n";
                    $wfcHandling->print_wfc($wfc[$index]);
                    }  
                }
            if ($DoDelete)          // alles was mit Money anfängt löschen
                {
                echo "Delete Panes starting with ".$configWF["TabPaneItem"]."\n";
                $wfcHandling->deletePane($configWF["TabPaneItem"]);              /* alle Spuren von vormals beseitigen */
                }

            // in roottp selber installieren
            $wfcHandling->CreateWFCItemTabPane($configWF["TabPaneItem"], $configWF["TabPaneParent"],  $configWF["TabPaneOrder"], $configWF["TabPaneName"], $configWF["TabPaneIcon"]);
            $configWF["TabPaneParent"]=$configWF["TabPaneItem"];          // überschreiben wenn roottp, wir sind jetzt bereits eins drunter  
            $configWF["TabPaneItem"] = $configWF["TabItem"];
            
            echo "Webfront Api Money do install:\n";
            $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            //true für Debug

            if (false)          // comment to view structure of webfront
                {
                $wfc=$wfcHandling->read_wfcByInstance(false,1);                 // false interne Datanbank für Config nehmen
                foreach ($wfc as $index => $entry)                              // Index ist User, Administrator
                    {
                    echo "\n------$index:\n";
                    $wfcHandling->print_wfc($wfc[$index]);
                    }        
                }
            $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);                    // funktioniert, Ergebnis der Änderungen wird abgespeichert
            echo "Webfront Api Money successfull installed.\n";
            }
        }

    echo "Installation Guthabensteuerung abgeschlossen. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
    if ($errorWarning) echo "check log, maybe there is an error or warning in the log\n";



?>