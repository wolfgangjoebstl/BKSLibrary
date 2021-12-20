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
	 
	/**@defgroup Guthabensteuerung::Guthabensteuerung_Installation
	 * @{
	 *
	 * Script um herauszufinden ob die Guthaben der Simkarten schon abgelaufen sind
	 * Installationsroutine, Eigenes Tab im SystemTP für Selenium Status
     *
	 *
	 * @file          Guthabensteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

/********************************************************
 *
 * INIT, generell
 *
 *******************************************************************/

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");    

	// max. Scriptlaufzeit definieren
	ini_set('max_execution_time', 500);
	$startexec=microtime(true);

	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	//echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	//echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	//echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	//echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Guthabensteuerung');
	//echo "\nGuthabensteuerung Version : ".$ergebnis;

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	//echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

    $dosOps = new dosOps();
    $ipsOps = new ipsOps();    
	$modulhandling = new ModuleHandling();	                    // aus AllgemeineDefinitionen

/********************************************************
 *
 * INIT, Variablen anlegen
 *
 *******************************************************************/

    $guthabenHandler = new GuthabenHandler(true,true,true);         // true,true,true Steuerung für parsetxtfile
	$GuthabenConfig         = $guthabenHandler->getContractsConfiguration();            // get_GuthabenConfiguration();
	$GuthabenAllgConfig     = $guthabenHandler->getGuthabenConfiguration();                              //get_GuthabenAllgemeinConfig();
	//print_r($GuthabenConfig);

    /* ScriptIDs finden für Timer */
	$ParseGuthabenID=IPS_GetScriptIDByName('ParseDreiGuthaben',$CategoryIdApp);
	$GuthabensteuerungID=IPS_GetScriptIDByName('Guthabensteuerung',$CategoryIdApp);

    /* Kategorien anlegen, je nach Betriebsart, 
     * default ist none, dann wird nichts ausser dem Nachrichtenverlauf angelegt und gemacht 
     */

    $categoryId_Guthaben        = CreateCategory('Guthaben',        $CategoryIdData, 20);
    $categoryId_GuthabenArchive = CreateCategory('GuthabenArchive', $CategoryIdData, 1000);

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$categoryId_Nachrichten     = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 100);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_Guthabensteuerung.csv",$input);

    $NachrichtenID      = $ipsOps->searchIDbyName("Nachricht",$CategoryIdData);
    $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);

    $DoInstall=true;

    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {
        case "IMACRO":
																								 
            $CategoryId_Mode          = CreateCategory('iMacro',          $CategoryIdData, 90);
																								  
            $statusReadID       = CreateVariable("StatusWebread", 3, $CategoryId_iMode,1010,"~HTMLBox",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            $testInputID        = CreateVariable("TestInput", 3, $CategoryId_iMode,1020,"",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            break;
        case "SELENIUM":
            IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
            //echo "Do Init for Operating Mode Selenium.\n";
            $seleniumOperations = new SeleniumOperations();            
            $CategoryId_Mode        = CreateCategory('Selenium',        $CategoryIdData, 90);
            $statusReadID       = CreateVariable("StatusWebread", 3, $CategoryId_Mode,1010,"~HTMLBox",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            if (isset($GuthabenAllgConfig["Selenium"]["WebDrivers"])) 
                {
                echo "Mehrere Webdriver Server konfiguriert.\n";
                $pos=10;
                foreach ($GuthabenAllgConfig["Selenium"]["WebDrivers"] as $category => $entry)
                    {
                    $categoryId_WebDriver        = CreateCategory($category,        $CategoryId_Mode, $pos);
                    $sessionID          = CreateVariableByName($categoryId_WebDriver,"SessionId", 3);                       
                    $handleID           = CreateVariableByName($categoryId_WebDriver,"HandleId", 3);  
                    $statusID           = CreateVariable("StatusWebDriver".$category, 3, $categoryId_WebDriver,1010,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')                     
                    $pos=$pos+10;
                    }
                }
            if (isset($GuthabenAllgConfig["Selenium"]["WebDriver"])) 
                {
                //$sessionID          = CreateVariable("SessionId", 3, $categoryId_Selenium,1000,"",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
                $sessionID          = CreateVariableByName($CategoryId_Mode,"SessionId", 3);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
                $handleID           = CreateVariableByName($CategoryId_Mode,"HandleId", 3);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
                $statusID           = CreateVariable("StatusWebDriverDefault", 3, $CategoryId_Mode,1010,"~HTMLBox",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')                     
                }
            $categoryDreiID = $seleniumOperations->getCategory("DREI");                
            echo "Category DREI : $categoryDreiID (".IPS_GetName($categoryDreiID).") in ".IPS_GetName(IPS_GetParent($categoryDreiID))."\n";                 
            break;
        case "NONE":
            $DoInstall=false;
            break;
        default:    
            echo "Guthaben Mode \"".$GuthabenAllgConfig["OperatingMode"]."\" not supported.\n";
            break;
        }
	

    $ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];
	
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

        $pname="GuthabenKonto";
        if (IPS_VariableProfileExists($pname) == false)
            {
                //Var-Profil erstellen
            IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
            IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
            IPS_SetVariableProfileValues($pname, 0, ($maxcount+1), 1); //PName, Minimal, Maximal, Schrittweite
            for ($i=0;$i<$maxcount;$i++)
                {
                IPS_SetVariableProfileAssociation($pname, $i, $phoneID[$i]["Short"], "", (1040+200*$i)); //P-Name, Value, Assotiation, Icon, Color=grau
                }
            $i++;       // sonst wird letzter Wert überschrieben	
            IPS_SetVariableProfileAssociation($pname, $i++, "Alle", "", (1040+200*$i)); //P-Name, Value, Assotiation, Icon, Color=grau			
            IPS_SetVariableProfileAssociation($pname, $i++, "Test", "", (1040+200*$i)); //P-Name, Value, Assotiation, Icon, Color=grau			
            echo "Profil ".$pname." erstellt;\n";
            }
        else
            {	/* profil sicherheitshalber überarbeiten */
            IPS_SetVariableProfileValues($pname, 0, ($maxcount+1), 1); //PName, Minimal, Maximal, Schrittweite
            for ($i=0;$i<$maxcount;$i++)
                {
                IPS_SetVariableProfileAssociation($pname, $i, $phoneID[$i]["Short"], "", (1040+200*$i)); //P-Name, Value, Assotiation, Icon, Color=grau
                }
            IPS_SetVariableProfileAssociation($pname, $i++, "Alle", "", (1040+200*$i)); //P-Name, Value, Assotiation, Icon, Color=grau			
            IPS_SetVariableProfileAssociation($pname, $i++, "Test", "", (1040+200*$i)); //P-Name, Value, Assotiation, Icon, Color=grau	
            echo "Profil ".$pname." überarbeitet. ;\n";		
            }


         if ((strtoupper($GuthabenAllgConfig["OperatingMode"]))=="SELENIUM")
            {
            $startImacroID      = CreateVariable("StartSelenium", 1, $CategoryId_Mode,1000,$pname,$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            }
        else
            {
            $startImacroID      = CreateVariable("StartImacro", 1, $CategoryId_Mode,1000,$pname,$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            }
        }

	/****************************************************************
	 *
	 * Initialisiere Profile
	 *
	 ************************************************************************/

	$pname="Euro";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
		IPS_SetVariableProfileText($pname,'','Euro');
		print_r(IPS_GetVariableProfile($pname));
		}
	else
		{
		//print_r(IPS_GetVariableProfile($pname));
		}

	$pname="MByte";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
		IPS_SetVariableProfileText($pname,'',' MByte');
		print_r(IPS_GetVariableProfile($pname));
		}
	else
		{
		//print_r(IPS_GetVariableProfile($pname));
		}


	/*****************************************************
	 *
	 * initialize Timer 
	 *
	 ******************************************************************/

	echo "Guthabensteuerung ScriptID:".$GuthabensteuerungID."\n";

	$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $GuthabensteuerungID);
	if ($tim1ID==false)
		{
		echo "Timer Aufruftimer erstellen.\n";
		$tim1ID = IPS_CreateEvent(1);
		IPS_SetParent($tim1ID, $GuthabensteuerungID);
		IPS_SetName($tim1ID, "Aufruftimer");
		IPS_SetEventCyclic($tim1ID,2,1,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim1ID,2,rand(1,59),0);  /* immer um 02:xx , nicht selbe Zeit damit keine Zugriffsverletzungen auf der Drei Homepage entstehen */
		}
	IPS_SetEventActive($tim1ID,true);

	$tim3ID = @IPS_GetEventIDByName("EveningCallTimer", $GuthabensteuerungID);
	if ($tim3ID==false)
		{
		echo "Timer EveningCallTimer erstellen.\n";
		$tim3ID = IPS_CreateEvent(1);
		IPS_SetParent($tim3ID, $GuthabensteuerungID);
		IPS_SetName($tim3ID, "EveningCallTimer");
		IPS_SetEventCyclic($tim3ID,2,1,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim3ID,22,rand(1,59),0);  /* immer um 02:xx , nicht selbe Zeit damit keine Zugriffsverletzungen auf der jeweiligen Homepage durch Zugriffe von mehreren Servern entstehen */
		}
	IPS_SetEventActive($tim3ID,true);

	/* Create Web Pages */

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	if ($WFC10_Enabled==true)
		{
		$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
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
		echo "Retro \n";
		}

	//echo "Test";

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	/******************************************************
	 *
	 *			INIT, Nachrichtenspeicher
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
     * Guthabensteuerung und Selnium wird hier überwacht, Anzeige erfolgt im SystemTP
     *
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

    $wfcHandling =  new WfcHandling();
    /* Workaround wenn im Webfront die Root fehlt */
    $WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();   

    if ((strtoupper($GuthabenAllgConfig["OperatingMode"]))=="SELENIUM")
        {
        /* wird auch für die nächste Abfrage benötigt 
        * zuerst aus dem ModulManager die Konfig von IPSModuleManagerGUI abrufen */
        $moduleManagerGUI = new IPSModuleManager('IPSModuleManagerGUI',$repository);
        $configWFrontGUI=$ipsOps->configWebfront($moduleManagerGUI,false);     // wenn true mit debug Funktion
        $tabPaneParent="roottp";                        // Default Wert

        $configWF=array();                                      // für die Verwendung vorbereiten
        if (isset($configWFrontGUI["Administrator"]))
            {
            $tabPaneParent=$configWFrontGUI["Administrator"]["TabPaneItem"];
            echo "  Selenium Module Überblick im Administrator Webfront $tabPaneParent abspeichern.\n";
            //print_r($configWFrontGUI["Administrator"]);   

            /* es gibt kein Module mit Selenium ini Dateien, daher etwas improvisieren und fixe Namen nehmen */
            $configWF["Enabled"]=true;
            $configWF["Path"]="Visualization.WebFront.Administrator.Selenium";
            $configWF["ConfigId"]=$WebfrontConfigID["Administrator"];              
            $configWF["TabPaneParent"]=$tabPaneParent;
            $configWF["TabPaneItem"]="Selenium"; 
            $configWF["TabPaneOrder"]=1010;                                          
            }

        /* Selenium Stationen auswerten */
        $webfront_links=array();
        $webfront_links["Selenium"]["Auswertung"]=array();
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
                $webfront_links["Selenium"]["Auswertung"][$statusID]["NAME"]="StatusWebDriver".$category;
                $webfront_links["Selenium"]["Auswertung"][$statusID]["ORDER"]=$order;
                $webfront_links["Selenium"]["Auswertung"][$statusID]["ADMINISTRATOR"]=true;
                $order=$order+10;
                }
            }
        if (isset($GuthabenAllgConfig["Selenium"]["WebDriver"])) 
            {
            $statusID           = IPS_GetObjectIdByName("StatusWebDriverDefault",$CategoryId_Mode);
            $webfront_links["Selenium"]["Auswertung"][$statusID]["NAME"]="StatusWebDriverDefault";
            $webfront_links["Selenium"]["Auswertung"][$statusID]["ORDER"]=90;
            $webfront_links["Selenium"]["Auswertung"][$statusID]["ADMINISTRATOR"]=true;
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

        $wfcHandling->easySetupWebfront($configWF,$webfront_links,"Administrator",true);            //true für Debug

        }



	/******************************************************
	 *
	 *		WebFront Installation
	 *
	 *************************************************************/	

	if ( ($WFC10_Enabled) && ($DoInstall) )
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

	if ( ($WFC10User_Enabled) && ($DoInstall) )
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

	if ( ($Mobile_Enabled) && ($DoInstall) )
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

	if ( ($Retro_Enabled) && ($DoInstall) )
		{
		echo "\nWebportal Retro installieren auf ".$Retro_Path.": \n";
		createPortal($Retro_Path);
		}






?>