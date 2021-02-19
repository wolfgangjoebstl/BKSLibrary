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
	 
	/**@defgroup Guthabensteuerung
	 * @{
	 *
	 * Script um herauszufinden ob die Guthaben der Simkarten schon abgelaufen sind
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

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
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

	$categoryId_Nachrichten     = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 100);
    $DoInstall=true;

    switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
        {
        case "IMACRO":
           	$categoryId_Guthaben        = CreateCategory('Guthaben',        $CategoryIdData, 10);
            $categoryId_iMacro          = CreateCategory('iMacro',          $CategoryIdData, 90);
            $categoryId_GuthabenArchive = CreateCategory('GuthabenArchive', $CategoryIdData, 900);
            $statusReadID       = CreateVariable("StatusWebread", 3, $CategoryId_iMacro,1010,"~HTMLBox",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            $testInputID        = CreateVariable("TestInput", 3, $CategoryId_iMacro,1020,"",$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
	        $startImacroID      = CreateVariable("StartImacro", 1, $CategoryId_iMacro,1000,$pname,$GuthabensteuerungID,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            break;
        case "SELENIUM":
           	$categoryId_Guthaben        = CreateCategory('Guthaben',        $CategoryIdData, 10);
            $categoryId_Selenium        = CreateCategory('Selenium',        $CategoryIdData, 20);
            $categoryId_GuthabenArchive = CreateCategory('GuthabenArchive', $CategoryIdData, 900);
            //$sessionID          = CreateVariable("SessionId", 3, $categoryId_Selenium,1000,"",null,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
            $sessionID          = CreateVariableByName($categoryId_Selenium,"SessionId", 3);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
            $handleID           = CreateVariableByName($categoryId_Selenium,"HandleId", 3);                        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
            break;
        case "NONE":
            $DoInstall=false;
            break;
        default:    
            echo "Guthaben Mode \"".$GuthabenAllgConfig["OperatingMode"]."\" not supported.\n";
        }
	

    $ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];
	
    if ($DoInstall)
        {
        $phoneID=array();           // wird für die Links im Webfront verwendet, nur die aktiven SIM Karten bekommen einen Link
        $i=0;
        foreach ($GuthabenConfig as $TelNummer)
            {   /* nur für die noch aktiven Nummern die Scripts anlegen und auch im Webfront darstellen */	
            if ((strtoupper($GuthabenAllgConfig["OperatingMode"]))=="SELENIUM")
                {

                }
            else
                {
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
                }	
            if 	( (strtoupper( $TelNummer["Status"])) == "ACTIVE")
                {
                $phone1ID = CreateVariableByName($categoryId_Guthaben, "Phone_".$TelNummer["Nummer"], 3);
                $phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Summary", 3);
                $phoneID[$i]["Nummer"]=$TelNummer["Nummer"];
                $phoneID[$i]["Short"]=substr($TelNummer["Nummer"],(strlen($TelNummer["Nummer"])-3),10);
                $phoneID[$i]["Summ"]=$phone_Summ_ID;
                $phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_User", 3);
                $phone_Status_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Status", 3);
                $phone_Date_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Date", 3);
                $phone_unchangedDate_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_unchangedDate", 3);
                $phone_Bonus_ID = CreateVariableByName($phone1ID, "Phone_".$TelNummer["Nummer"]."_Bonus", 3);

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
		echo "Profil ".$pname." überarbeitet;\n";		
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
		echo "Timer erstellen.\n";
		$tim1ID = IPS_CreateEvent(1);
		IPS_SetParent($tim1ID, $GuthabensteuerungID);
		IPS_SetName($tim1ID, "Aufruftimer");
		IPS_SetEventCyclic($tim1ID,2,1,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim1ID,2,rand(1,59),0);  /* immer um 02:xx , nicht selnbe Zeit damit keien zugriffsverletzungen auf der Drei Homepage entstehen */
		}
	IPS_SetEventActive($tim1ID,true);

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


	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_Guthabensteuerung.csv",$input);

	if ($_IPS['SENDER']=="Execute")
		{
        echo "\n";
        echo "---------Nachrichtenspeicher------------------\n";
		echo 	$log_OperationCenter->PrintNachrichten();
        echo "-----------------------------------------------\n";
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
		$phone_summary_ID = CreateVariableByName($categoryId_WebFront, "Summary", 3);
		foreach ($phoneID as $phone)
			{
	   		CreateLinkByDestination(IPS_GetName($phone["Summ"]), $phone["Summ"],    $phone_summary_ID,  10);
			}
		CreateLinkByDestination(IPS_GetName($phone_Cost_ID), $phone_Cost_ID, $categoryId_WebFront,  20);
        switch (strtoupper($GuthabenAllgConfig["OperatingMode"]))
            {
            case "IMACRO":        
        		CreateLinkByDestination(IPS_GetName($startImacroID), $startImacroID, $categoryId_WebFront,  30);
                CreateLinkByDestination(IPS_GetName($statusReadID), $statusReadID, $categoryId_WebFront,  40);
                CreateLinkByDestination(IPS_GetName($testInputID), $testInputID, $categoryId_WebFront,  50);
                break;
            }
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