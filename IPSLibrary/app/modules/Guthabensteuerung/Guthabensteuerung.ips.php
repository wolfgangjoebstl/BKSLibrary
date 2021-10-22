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
     * unterschiedliche STrategien, Aufruf mit
     *      iMacro
     *      Selenium
     *
     */



    Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
    IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

    // max. Scriptlaufzeit definieren
    ini_set('max_execution_time', 100);
    $startexec=microtime(true);    
    echo "Abgelaufene Zeit : ".exectime($startexec)." Sek. Max Scripttime is 100 Sek \n";

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

    $ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	/*****************************************************
	 *
	 * initialize Timer 
	 *
	 ******************************************************************/

    $tim1ID = IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
    if ($tim1ID==false) echo "Fehler timer nicht definiert.\n";

    $tim2ID = @IPS_GetEventIDByName("Exectimer", $_IPS['SELF']);
    if ($tim2ID==false)
        {
        $tim2ID = IPS_CreateEvent(1);
        IPS_SetParent($tim2ID, $_IPS['SELF']);
        IPS_SetName($tim2ID, "Exectimer");
        IPS_SetEventCyclic($tim2ID,2,1,0,0,1,150);      /* alle 150 sec */
        //IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
        }

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
 * zwei TimerEvents. Eines einmal am Tag und das andere alle 150 Sekunden
 *
 *************************************************************/

if ($_IPS['SENDER']=="TimerEvent")
	{
	//IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']);

	switch ($_IPS['EVENT'])
		{
		case $tim1ID:
			IPS_SetEventActive($tim2ID,true);
			SetValue($statusReadID,"");			// Beim Erstaufruf Html Log loeschen.
			break;
		case $tim2ID:
			//IPSLogger_Dbg(__file__, "TimerExecEvent from :".$_IPS['EVENT']." ScriptcountID:".GetValue($ScriptCounterID)." von ".$maxcount);
			$ScriptCounter=GetValue($ScriptCounterID);
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
                                $note="Letzte Abfrage war um ".date("d.m.Y H:i:s")." für dreiat_".$phoneID[($ScriptCounter)]["Nummer"].".iim. Letztes Ergebnis für ".$phoneID[($ScriptCounter-1)]["Nummer"]." mit Datum ".$filedate." ";
                                }
                            }
                        else 	$note="Letzte Abfrage war um ".date("d.m.Y H:i:s")." für dreiat_".$phoneID[($ScriptCounter)]["Nummer"].".iim.";
                        SetValue($statusReadID,GetValue($statusReadID)."<br>".$note);	
                        break;
                     case "SELENIUM":
                        $config["DREI"]["CONFIG"]["Username"]=$phoneID[$ScriptCounter]["Nummer"];          // von 0 bis maxcount-1 durchgehen
                        $config["DREI"]["CONFIG"]["Password"]=$phoneID[$ScriptCounter]["Password"];
                        $seleniumOperations->automatedQuery($webDriverName,$config);          // true debug      
                        $note="Abfrage war um ".date("d.m.Y H:i:s")." für ".$phoneID[($ScriptCounter)]["Nummer"];
                        SetValue($statusReadID,GetValue($statusReadID)."<br>".$note);	                           
                        break;
                    default:
                        break;
                    }
			    SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1);                	
				}
			else
				{
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
                        // muss ich noch etwas machen        
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
	echo "  Timer 1 ID : ".$tim1ID."   ".(IPS_GetEvent($tim1ID)["EventActive"]?"Ein":"Aus")."\n";
    echo "  Timer 2 ID : ".$tim2ID."   ".(IPS_GetEvent($tim2ID)["EventActive"]?"Ein":"Aus")."\n";

	$ScriptCounter=GetValue($ScriptCounterID);
    echo "Script Counter (aktuell)    : ".$ScriptCounter."\n";
    echo "Webfront MacroID            : ".$startImacroID."\n";
    echo "Operating Mode              : ".(strtoupper($GuthabenAllgConfig["OperatingMode"]))."\n";
    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {  
        case "SELENIUM":
            //print_R($GuthabenAllgConfig);
            //echo "Ausgabe Konfiguration:\n"; print_r($GuthabenConfig);
            echo "\nEingedampft wird es dann hier:\n";
            readDownloadDirectory($GuthabenAllgConfig["WebResultDirectory"]);
            $guthabenHandler->extendPhoneNumberConfiguration($phoneID,$seleniumOperations->getCategory("DREI"));            // phoneID is return parameter
            /*
            $registers=array();
            $childrens=IPS_GetChildrenIDs($seleniumOperations->getCategory("DREI"));
            foreach ($childrens as $children) 
                {
                $name = IPS_GetName($children);
                //echo "  $name   \n";
                $register[$name]["LastUpdated"]=IPS_GetVariable($children)["VariableUpdated"];
                $register[$name]["OID"]=$children;
                }
            echo "Register in IP Symcon:\n";
            //print_R($register);
            foreach ($phoneID as $index => $entry)
                {
                echo "   ".$entry["Nummer"]."    : ";
                if (isset($register[$entry["Nummer"]])) 
                    {
                    echo "available, last update was ".date("d.m.Y H:i:s",$register[$entry["Nummer"]]["LastUpdated"])."\n";
                    $phoneID[$index]+=$register[$entry["Nummer"]];
                    }
                else echo "NOT available\n";
                }  */
            print_R($phoneID);
            foreach ($phoneID as $entry)
                {
                if (isset($entry["OID"]))
                    {
                    echo "   ".$entry["Nummer"]."    : register (".$entry["OID"].") available, last update was ".date("d.m.Y H:i:s",$entry["LastUpdated"])."\n";
                    $result=GetValue($entry["OID"]);
                    }

                }

			//IPS_SetScriptTimer($_IPS['SELF'], 150);
			if ($ScriptCounter < $maxcount) $value=$ScriptCounter;
            else { $value=0; SetValue($ScriptCounterID,0); }

            //$value=2;       // 0,1  ... (count-1)
            //$webDriverName="BKS-Server";
            //$debug=true;
            $debug=false;
            echo "============================================================================\n";
            echo "Aufruf Selenium von ".$phoneID[$value]["Nummer"]." mit Index $value/$maxcount.\n";
            $config["DREI"]["CONFIG"]["Username"]=$phoneID[$value]["Nummer"];
            $config["DREI"]["CONFIG"]["Password"]=$phoneID[$value]["Password"];   
            $seleniumOperations = new SeleniumOperations();                             // macht nichts, erst mit automated query gehts los
            $seleniumOperations->automatedQuery($webDriverName,$config,$debug);          // true debug         
            echo "============================================================================\n";
            SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1); 
            break;  
        case "IMACRO":
            echo "Verzeichnis für Macros :     ".$GuthabenAllgConfig["MacroDirectory"]."\n";
            echo "Verzeichnis für Ergebnisse : ".$GuthabenAllgConfig["DownloadDirectory"]."\n";
            echo "Verzeichnis für Mozilla exe: ".$firefox."\n\n";
	
        	//print_r($phone);

            readDownloadDirectory();

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


    function readDownloadDirectory($verzeichnis=false)
        {
        if ($verzeichnis===false) $verzeichnis=$GuthabenAllgConfig["DownloadDirectory"];        
            $dir=array(); $count=0; 
                // Test, ob ein Verzeichnis angegeben wurde
                if ( is_dir ( $verzeichnis ) )
                    {
                    // öffnen des Verzeichnisses
                    if ( $handle = opendir($verzeichnis) )
                        {
                        /* einlesen der Verzeichnisses	*/
                        while ((($file = readdir($handle)) !== false) )
                            {
                            if ($file!="." and $file != "..")
                                {	/* kein Directoryverweis (. oder ..), würde zu einer Fehlermeldung bei filetype führen */
                                //echo "Bearbeite ".$verzeichnis.$file."\n";
                                $dateityp=filetype( $verzeichnis.$file );
                                if ($dateityp == "file")			// alternativ dir für Verzeichnis
                                    {
                                    //echo "   Erfasse Verzeichnis ".$verzeichnis.$file."\n";
                                    $dir[$count]["Name"]=$verzeichnis.$file;
                                    $dir[$count++]["Date"]=date ("d.m.Y H:i:s.", filemtime($verzeichnis.$file));
                                    }
                                }	
                            //echo "    ".$file."    ".$dateityp."\n";
                            } /* Ende while */
                        //echo "   Insgesamt wurden ".$count." Verzeichnisse entdeckt.\n";	
                        closedir($handle);
                        } /* end if dir */
                    }/* ende if isdir */
                else
                    {
                    echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
                    }	
            //print_r($dir);
            echo "Dateien im Download Verzeichnis.\n";
            foreach ($dir as $entry) echo "   ".$entry["Name"]."  zuletzt geändert am ".$entry["Date"]."\n";

        }


?>