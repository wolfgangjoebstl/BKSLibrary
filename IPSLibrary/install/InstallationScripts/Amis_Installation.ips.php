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

	/**@defgroup 
	 * @ingroup 
	 * @{
	 *
	 * Script zur 
	 *
	 *
	 * @file          
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.08.2014<br/>
	 **/

/******************************************************
 *
 * AMIS, abgeleitet vom Siemens AMIS Zähler. Routine liest AMIS Zähler,
 * Homematic Register aber auch normale Register zB aus RemoteAccess aus
 * und verarbeitet sie.
 * Als neue Funktion gibt es auch eine Mathematische Summenfunktion
 *
 **************************************************************/

/******************************************************
 *
 *				INIT
 *
 * Die AMIS zähler können über den IPS integrierten Cutter oder über eine
 * im PHP erstellte Routine ausgelesen werden.
 *
 * Script MomentanwerteAbfragen wird jede Minute aufgerufen
 *
 * Webfront: 
 * es werden Subtabs erstellt. Zumindest nach den Zählwerttypen meter["TYPE"] : Homematic, Register, Summe, Amis
 * zusätzlich gibt es den Tab Zusammenfassung und Kurven
 * Steuerung erfolgt über webfront_links
 *
 *************************************************************/

$cutter=true;


	/******************** Defaultprogrammteil ********************/
	 
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');	
	IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
		}

 	$installedModules = $moduleManager->GetInstalledModules();
	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nIPS aktuelle Kernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nMinimal erforderliche IPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Amis');       /*   <--- change here */
	echo "\nAmis Modul Version : ".$ergebnis."\n\n";    										/*   <--- change here */
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

    if (isset($installedModules["Stromheizung"])==true)
	    {
	    IPSUtils_Include ("IPSHeat.inc.php",                "IPSLibrary::app::modules::Stromheizung");
	    IPSUtils_Include ("IPSHeat_Constants.inc.php",      "IPSLibrary::app::modules::Stromheizung");
    	}
    else
	    {
    	// Confguration Property Definition
	    define ('IPSHEAT_WFCSPLITPANEL',		'WFCSplitPanel');
    	define ('IPSHEAT_WFCCATEGORY',			'WFCCategory');
	    define ('IPSHEAT_WFCGROUP',			'WFCGroup');
	    define ('IPSHEAT_WFCLINKS',			'WFCLinks');
	    }

    $ipsOps = new ipsOps();
    $dosOps = new dosOps();
	$wfcHandling = new WfcHandling();		// für die Interoperabilität mit den alten WFC Routinen nocheinmal mit der Instanz als Parameter aufrufen
    $profileOps = new profileOps();             // Profile verwalten, local geht auch remote

/***********************
 *
 * Webfront GUID herausfinden und Konfiguratoren anlegen
 * 
 **************************/
	
	$WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();
	if ( isset($WebfrontConfigID["Administrator"]) == false )       /* webfront Administrator Configuratoren anlegen, wenn noch nicht vorhanden */
		{
    	//$AdministratorID = @IPS_GetInstanceIDByName("Administrator", 0);
	    //if(!IPS_InstanceExists($AdministratorID))		echo "\nWebfront Configurator Administrator  erstellen !\n";
		$AdministratorID = IPS_CreateInstance("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}"); // Administrator Webfront Configurator anlegen
		IPS_SetName($AdministratorID, "Administrator");
		$config = IPS_GetConfiguration($AdministratorID);
		echo " Konfig: ".$config."\n";

		IPS_ApplyChanges($AdministratorID);
		$WebfrontConfigID["Administrator"]=$AdministratorID;
		echo "Webfront Configurator Administrator aktiviert. \n";
		}
	else                                                        /* webfront Administrator Configurator ID nur speichern, da schon vorhanden */
		{
		$AdministratorID = $WebfrontConfigID["Administrator"];
		}		

	if ( isset($WebfrontConfigID["User"]) == false )                /* webfront User Configuratoren anlegen, wenn noch nicht vorhanden */
		{
		//$UserID = @IPS_GetInstanceIDByName("User", 0);
		//if(!IPS_InstanceExists($UserID))
		echo "\nWebfront Configurator User  erstellen !\n";
		$UserID = IPS_CreateInstance("{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}"); // Administrator Webfront Configurator anlegen
		IPS_SetName($UserID, "User");
		$config = IPS_GetConfiguration($UserID);
		echo "Konfig : ".$config."\n";

		IPS_ApplyChanges($UserID);
		$WebfrontConfigID["User"]=$UserID;
		echo "Webfront Configurator User aktiviert. \n";
		}
	else                                                         /* webfront User Configurator ID nur speicher, da schon vorhanden */
		{
		$UserID = $WebfrontConfigID["User"];
		}	

	//echo "\nAdministrator ID : ".$AdministratorID." User ID : ".$UserID."\n\n";
	
/***********************
 *
 * Webfront Konfigurationen herausfinden
 * 
 **************************/

    $configWFront=$ipsOps->configWebfront($moduleManager);
    //print_r($configWFront);

	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
    $Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);

	if ($WFC10_Enabled==true)   	    $WFC10_ConfigId       = $WebfrontConfigID["Administrator"];	
	if ($WFC10User_Enabled==true)		$WFC10User_ConfigId       = $WebfrontConfigID["User"];        
	if ($Mobile_Enabled==true)  		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');	
	if ($Retro_Enabled==true)   		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
	$scriptIdAmis   = IPS_GetScriptIDByName('Amis', $CategoryIdApp);

/***********************
 *
 *					Profile Definition
 * 
 **************************/	

    echo "Darstellung der benötigten Variablenprofile im lokalem Bereich, wenn fehlt anlegen:\n";
	$profilname=array("AusEin-Boolean"=>"update","Zaehlt"=>"update","kWh"=>"update","Wh"=>"update","kW"=>"update","Euro"=>"update");
    $profileOps->synchronizeProfiles($profilname);

    /*echo "Profile Definition für AMIS Modul:\n";
	$pname="AusEin-Boolean";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 0); 
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, false, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, true, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." erstellt.\n";
		}
    else echo "   Profil ".$pname." vorhanden.\n";
		
	$pname="Zaehlt";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 0); 
  		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen

		IPS_SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, false, "Idle", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  		IPS_SetVariableProfileAssociation($pname, true, "Active", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." erstellt.\n";
		//print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
       echo "   Profil ".$pname." vorhanden.\n";           
	   //print_r(IPS_GetVariableProfile($pname));
	   }
	   
	$pname="kWh";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); 
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','kWh');
		echo "Profil ".$pname." erstellt.\n";          
		print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
       echo "   Profil ".$pname." vorhanden.\n";             
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="Wh";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); 
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','Wh');
		echo "Profil ".$pname." erstellt.\n";          
        //print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
       echo "   Profil ".$pname." vorhanden.\n";             
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="kW";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); 
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','kW');
		echo "Profil ".$pname." erstellt.\n";          
		//print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
       echo "   Profil ".$pname." vorhanden.\n";             
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="Euro";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); 
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','Euro');
		echo "Profil ".$pname." erstellt.\n";          
		//print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
       echo "   Profil ".$pname." vorhanden.\n";             
	   //print_r(IPS_GetVariableProfile($pname));
	   }
		
		
/******************* 
 * 
 *				Variable Definition aus dem Config File auslesen
 *
 *	zwei Config Functions:  
 * 	get_MeterConfiguration()
 *		get_AmisConfiguration (alt, ergänzend, mit STATUS kann die Ablesung defaultmäßig ein und ausgeschaltet werden) 
 *
 * es gibt mehrer TYPEs of Meters: HOMEMATIC, REGISTER, AMIS und SUMME
 *   Homematic ist das Energieregister der Homeatic Serie, Register ein Wert von RemoteAccess, AMIS die Auslesunmg des AMIS Zählers 
 *   und SUMME eine kalkulatorische Berechnung immer dann wenn sich ein Wert aendert. 
 *
 ************************************************/
	


	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	$Amis = new Amis();
	$MeterConfig = $Amis->getMeterConfig();

	/* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
	$MeterReadDefault=true;
	if ( function_exists("get_AmisConfiguration") )
		{
		$MeterStatusConfig=get_AmisConfiguration();
		if ( isset($MeterStatusConfig["Status"]) )
			{
			if ( Strtoupper($MeterStatusConfig["Status"]) != "ACTIVE" ) 	$MeterReadDefault=false;
			}
		}
	//print_r($MeterConfig);
	
	//$MeterReadID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
	/* 	 CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') 
         CreateVariableByName($parentID, $name, $type, $profile=false, $ident="", $position=0, $action=0, $default=false)                           */
    echo "\nVariable Definition für AMIS Module:\n";
	$MeterReadID = CreateVariableByName($CategoryIdData, "ReadMeter", 0, "Zaehlt","",0,$scriptIdAmis,$MeterReadDefault);  /* 0 Boolean 1 Integer 2 Float 3 String */		
	SetValue($MeterReadID,$MeterReadDefault);
	
	/*************************************************************************************************************
     * Links für Webfront identifizieren 
	 *  Struktur [Tab] [Left, Right] [LINKID] ["NAME"]="Name"
	 *  umgesetzt auf [AMIS,Homematic,Register,Summe] und ein Tab für die Zusammenfassung
	 *****************************************************************************************************************/
     
	$webfront_links=array();
	$pos=100;
	foreach ($MeterConfig as $identifier => $meter)
		{
		echo"\n-------------------------------------------------------------\n";
		echo "Create Variableset for : ".$meter["TYPE"]." ".$meter["NAME"]." mit ID : ".$identifier." \n";
		$ID = CreateVariableByName($CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetPosition($ID,$pos);
		$pos +=100;
		
		/*****************************
		*
		* Variablenstruktur sollte immer gleich sein:
		*
		* Kategorie=Name des Messgeraetes
		*    Wirkenergie, Profil ~Electricity
		*    Wirkleistung, Profil ~Power
		*    Periodenwerte (Kategorie)
		*
		* bei Amis Zählern werden noch die tatsächlichen Messwerte unter Zaehlervariablen (Kategorie) abgespeichert
		* bei Homematic werden Stromausfälle die zum Reset des Energieregisters führen mit einem Offsetregister kompensiert.
		*
		*************************************************/
		
        switch (strtoupper($meter["TYPE"]))
            {
            case "HOMEMATIC":               		/***********************Homematic Zähler */
                /* Variable ID selbst bestimmen */
                $variableID = CreateVariableByName($ID, 'Wirkenergie', 2, '~Electricity');   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($variableID,'~Electricity');
                AC_SetLoggingStatus($archiveHandlerID,$variableID,true);
                AC_SetAggregationType($archiveHandlerID,$variableID,1);      /* Zählerwert */
                IPS_ApplyChanges($archiveHandlerID);
                
                $LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2,'~Power');   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($LeistungID,'~Power');
                AC_SetLoggingStatus($archiveHandlerID,$LeistungID,true);
                AC_SetAggregationType($archiveHandlerID,$LeistungID,0);
                IPS_ApplyChanges($archiveHandlerID);
                
                $HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2,'kWh');   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($HM_EnergieID,'kWh');
            
                $chartID = CreateVariableByName($ID, "Chart", 3,'~HTMLBox');

                SetValue($MeterReadID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */

                // Homematic
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$variableID]["NAME"]="Wirkenergie";
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$variableID]["PANE"]=true;				            // linkes Tab, Anordnung in gemeinsamer gruppe
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$LeistungID]["NAME"]="Wirkleistung";
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$LeistungID]["PANE"]=true;		
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$chartID]["NAME"]="Kurve";						
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$chartID]["PANE"]=false;              		
            	break;
		    case "REGISTER":       /*********************** Irgendein Register Zähler, wahrscheinlich von Remote Access uebermittelt */
            case "DAILYREAD":
            case "DAILYLPREAD":
                /* Variable ID selbst bestimmen */
                $variableID = CreateVariableByName($ID, 'Wirkenergie', 2,'~Electricity');   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($variableID,'~Electricity');
                AC_SetLoggingStatus($archiveHandlerID,$variableID,true);
                AC_SetAggregationType($archiveHandlerID,$variableID,1);      /* Zählerwert */
                // AC_SetAggregationType($archiveHandlerID,$variableID,0);                                            /* Registerwert aus dem Smart Meter Webportal muss in Guthaben stehen */
                IPS_ApplyChanges($archiveHandlerID);
                
                $LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2,'~Power');   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($LeistungID,'~Power');
                AC_SetLoggingStatus($archiveHandlerID,$LeistungID,true);
                AC_SetAggregationType($archiveHandlerID,$LeistungID,0);
                IPS_ApplyChanges($archiveHandlerID);
                
                $chartID = CreateVariableByName($ID, "Chart", 3,'~HTMLBox');

                //$HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($HM_EnergieID,'kWh');
            
                SetValue($MeterReadID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */
                
                // Register
                $webfront_links["Register"][$meter["NAME"]][$variableID]["NAME"]="Wirkenergie";
                $webfront_links["Register"][$meter["NAME"]][$variableID]["PANE"]=true;				            // linkes Tab, Anordnung in gemeinsamer gruppe	
                $webfront_links["Register"][$meter["NAME"]][$LeistungID]["NAME"]="Wirkleistung";
                $webfront_links["Register"][$meter["NAME"]][$LeistungID]["PANE"]=true;	
                $webfront_links["Register"][$meter["NAME"]][$chartID]["NAME"]="Kurve";						
                $webfront_links["Register"][$meter["NAME"]][$chartID]["PANE"]=false;             	
                break;
		    case "SUMME":                /*********************** aus mehreren Werten eine Berechnung anstellen 
                                        * links die Werte Wirkenergie und Leistung pro Gruppe meter Name
                                        * rechts das passende chart
                                        */
                /* Variable ID selbst bestimmen */
                $variableID = CreateVariableByName($ID, 'Wirkenergie', 2,'~Electricity');   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($variableID,'~Electricity');
                AC_SetLoggingStatus($archiveHandlerID,$variableID,true);
                AC_SetAggregationType($archiveHandlerID,$variableID,1);      /* Zählerwert */
                IPS_ApplyChanges($archiveHandlerID);
                
                $LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2, '~Power');   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($LeistungID,'~Power');
                AC_SetLoggingStatus($archiveHandlerID,$LeistungID,true);
                AC_SetAggregationType($archiveHandlerID,$LeistungID,0);
                IPS_ApplyChanges($archiveHandlerID);

                $chartID = CreateVariableByName($ID, "Chart", 3,'~HTMLBox');
                
                //$HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($HM_EnergieID,'kWh');
            
                SetValue($MeterReadID,true);  // wenn Werte parametriert, dann auch regelmaessig auslesen 

                // SUMME Gruppe Meter Name, Links Wirkenergie, Wirkleistung
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$variableID]["NAME"]="Wirkenergie";
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$variableID]["PANE"]=true;				            // linkes Tab, Anordnung in gemeinsamer gruppe	
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$LeistungID]["NAME"]="Wirkleistung";
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$LeistungID]["PANE"]=true;		
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$chartID]["NAME"]="Kurve";						
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$chartID]["PANE"]=true;                        			
                break;
			
		    case "AMIS":                /************************** und ein AMIS Zähler mit dem auslesen über die serielle Schnittstelle 
                                        *
                                        */	
                /* zuerst einmal klären wie die Daten denn reinkommen, seriell USB oder über Bluetooth, Zerlegung selbst oder ueber Cutter */
                $scriptIdAMIS   = IPS_GetScriptIDByName('AmisCutter', $CategoryIdApp);
                echo "Meter Type AMIS:\n";
                echo "   Script ID für Register Variable :".$scriptIdAMIS."\n";

                if ($meter["PORT"] == "Bluetooth")
                    {
                    echo "  Port is Bluetooth.\n";
                    $PortConfig=array($meter["COMPORT"],"115200","8","1","None");
                    $result=$Amis->configurePort($identifier." Bluetooth COM",$PortConfig);
                    if ( $result == false) 
                        { 
                        $result = $Amis->configurePort($identifier." Bluetooth COM",$PortConfig);     // noch einmal probieren
                        echo " Noch einmal probiert.\n";
                        }	
                    $SerialComPortID = @IPS_GetInstanceIDByName($identifier." Bluetooth COM", 0);
                    //echo "\nCom Port : ".$com_Port." PortID: ".$SerialComPortID."\n";				
                    if ($result == false) { echo "*****************Abbruch, Fehler bei Open Port.\n\n"; }
                    else 
                        {	
                        SPRT_SendText($SerialComPortID ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */
                        }
                    }
                if ($meter["PORT"] == "Serial")
                    {
                    echo "  Port is Serial.\n";
                    $PortConfig=array($meter["COMPORT"],"300","7","1","Even");
                    $result=$Amis->configurePort($identifier." Serial Port",$PortConfig);
                    if ( $result == false) 
                        { 
                        $result = $Amis->configurePort($identifier." Serial Port",$PortConfig);     // noch einmal probieren
                        echo " Serial Port Configure fehlerhaft. Noch einmal probiert. Ergebnis $result.\n";
                        }	
                    $SerialComPortID = IPS_GetInstanceIDByName($identifier." Serial Port", 0);
                    if ($result == false) { echo "*****************Abbruch, Fehler bei Open Port.\n\n"; }
                    else 
                        {	
                        SPRT_SetDTR($SerialComPortID, true);   /* Wichtig sonst wird der Lesekopf nicht versorgt */
                        }
                    }
                
                if ($cutter == true)
                    {
                    $CutterID = @IPS_GetInstanceIDByName($identifier." Cutter", 0);
                    if(!IPS_InstanceExists($CutterID))
                        {
                        echo "\nAMIS Cutter mit Namen \"".$identifier." Cutter\"erstellen !\n";
                        $CutterID = IPS_CreateInstance("{AC6C6E74-C797-40B3-BA82-F135D941D1A2}"); // Cutter anlegen
                        IPS_SetName($CutterID, $identifier." Cutter");
                        IPS_SetProperty($CutterID,"LeftCutChar",chr(02));
                        IPS_SetProperty($CutterID,"RightCutChar",chr(03));
                        IPS_ConnectInstance($CutterID, $SerialComPortID);										
                        IPS_ApplyChanges($CutterID);					
                        }
                    else
                        {
                        echo "\nAMIS Cutter mit Namen \"".$identifier." Cutter\" existiert bereits !\n";
                        if ($SerialComPortID>0)
                            {
                            @IPS_DisconnectInstance($CutterID);
                            IPS_ConnectInstance($CutterID, $SerialComPortID);
                            }					
                        $config=IPS_GetConfiguration($CutterID);
                        echo "    ".$config."\n";					
                        }
                    $regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$CutterID);
                    if(!IPS_InstanceExists($regVarID))
                        {
                        $regVarID = IPS_CreateInstance("{F3855B3C-7CD6-47CA-97AB-E66D346C037F}"); // Registervariable anlegen
                        IPS_SetName($regVarID, "AMIS RegisterVariable");
                        IPS_SetParent($regVarID, $CutterID);
                        RegVar_SetRXObjectID($regVarID, $scriptIdAMIS);
                        IPS_ConnectInstance($regVarID, $CutterID);
                        IPS_ApplyChanges($regVarID);
                        }	
                    else
                        {
                        echo "\nAMIS RegisterVariable mit Namen \"".$regVarID."\" existiert bereits !\n";
                        @IPS_DisconnectInstance($regVarID);					
                        IPS_ConnectInstance($regVarID, $CutterID);				
                        }														
                    }
                else
                    {
                    $regVarID = @IPS_GetInstanceIDByName("AMIS RegisterVariable", 	$SerialComPortID);
                    if(!IPS_InstanceExists($regVarID))
                        {
                        $regVarID = IPS_CreateInstance("{F3855B3C-7CD6-47CA-97AB-E66D346C037F}"); // Registervariable anlegen
                        IPS_SetName($regVarID, "AMIS RegisterVariable");
                        IPS_SetParent($regVarID, $SerialComPortID);
                        RegVar_SetRXObjectID($regVarID, $scriptIdAMIS);
                        IPS_ConnectInstance($regVarID, $SerialComPortID);
                        IPS_ApplyChanges($regVarID);
                        }				
                    }								
                
                /* dann erst die Struktur zum Abspeichern anlegen */
                $AmisID = CreateVariableByName($ID, "AMIS", 3);
                //$AmisReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0, 0, "Zaehlt");   /* 0 Boolean 1 Integer 2 Float 3 String */
                $AmisReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0, "Zaehlt");   /* 0 Boolean 1 Integer 2 Float 3 String */
                //$TimeSlotReadID = CreateVariableByName($AmisID, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
                $AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);

                $ReceiveTimeID = CreateVariableByName($AmisID, "ReceiveTime", 1,'~UnixTimestamp');   /* 0 Boolean 1 Integer 2 Float 3 String */
                //IPS_SetVariableCustomProfile($ReceiveTimeID,'~UnixTimestamp');
                $SendTimeID = CreateVariableByName($AmisID, "SendTime", 1,'~UnixTimestamp');   /* 0 Boolean 1 Integer 2 Float 3 String */	
                //IPS_SetVariableCustomProfile($SendTimeID,'~UnixTimestamp');					

                // Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
                $AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);
                $AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);
                
                $wirkenergie1_ID = CreateVariableByName($AmisID,'Wirkenergie', 2,'~Electricity');
                //IPS_SetVariableCustomProfile($wirkenergie1_ID,'~Electricity');
                AC_SetLoggingStatus($archiveHandlerID,$wirkenergie1_ID,true);
                AC_SetAggregationType($archiveHandlerID,$wirkenergie1_ID,1);
                IPS_ApplyChanges($archiveHandlerID);

                $aktuelleLeistungID = CreateVariableByName($AmisID, "Wirkleistung", 2,'~Power');
                //IPS_SetVariableCustomProfile($aktuelleLeistungID,'~Power');
                AC_SetLoggingStatus($archiveHandlerID,$aktuelleLeistungID,true);
                AC_SetAggregationType($archiveHandlerID,$aktuelleLeistungID,0);
                IPS_ApplyChanges($archiveHandlerID);

                $chartID = CreateVariableByName($AmisID, "Chart", 3,'~HTMLBox');

                // Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
                $zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
                $variableID = CreateVariableByName($zaehlerid,'Wirkenergie', 2,'~Electricity');
                //IPS_SetVariableCustomProfile($variableID,'~Electricity');			

                SetValue($AmisReadMeterID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */
                if ( isset($meter["STATUS"]) )
                    {
                    if (strtoupper($meter["STATUS"]) != "ACTIVE" ) SetValue($AmisReadMeterID,false);
                    }	
                $webfront_links["Control"]["Read Meter"][$AmisReadMeterID]["NAME"]="ReadAMISMeter";		            // Read Meter ist die Gruppe
                $webfront_links["Control"]["Read Meter"][$AmisReadMeterID]["PANE"]=false;                           // notwendig, entweder ist der Name Auswertung oder Nachrichten, oder PANE wird definiert

                // AMIS, Gruppe meter NAME, Wirkenergie, Leistung, Andere Seite Zählervariablen			
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$wirkenergie1_ID]["NAME"]="Wirkenergie";
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$wirkenergie1_ID]["PANE"]=true;						
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$aktuelleLeistungID]["NAME"]="Wirkleistung";
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$aktuelleLeistungID]["PANE"]=true;						
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$zaehlerid]["NAME"]="Zaehlervariablen";						
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$zaehlerid]["PANE"]=false;	
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$chartID]["NAME"]="Kurve";						
                $webfront_links[$meter["TYPE"]][$meter["NAME"]][$chartID]["PANE"]=false;					
                break;
            default:
                break;
			}
		
        print_r($meter);

        //$webfront_links["Control"]["Read Meter"][$MeterReadID]["NAME"]="ReadMeter";           // wurde schon beschrieben
		//CreateLinkByDestination("Read Meter", $MeterReadID,    $categoryIdLeft,  0);



		/********************************
		 *
		 * für alle Zählertypen gemeinsam die Periodenwerte 
		 *
		 *********************************************/

		$PeriodenwerteID = CreateVariableByName($ID, "Periodenwerte", 3);
		$KostenID = CreateVariableByName($ID, "Kosten kWh", 2);

		$letzterTagID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzterTag", 2,'kWh',false,100);
		//IPS_SetVariableCustomProfile($letzterTagID,'kWh');
		//IPS_SetPosition($letzterTagID, 100);
		$letzte7TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte7Tage", 2,'kWh',false,110);
		//IPS_SetVariableCustomProfile($letzte7TageID,'kWh');
		//IPS_SetPosition($letzte7TageID, 110);
		$letzte30TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte30Tage", 2,'kWh',false,120);
		//IPS_SetVariableCustomProfile($letzte30TageID,'kWh');
		//IPS_SetPosition($letzte30TageID, 120);
		$letzte360TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte360Tage", 2,'kWh',false,130);
		//IPS_SetVariableCustomProfile($letzte360TageID,'kWh');
		//IPS_SetPosition($letzte360TageID, 130);

		$letzterTagEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzterTag", 2,'Euro',false,200);
		//IPS_SetVariableCustomProfile($letzterTagEurID,'Euro');
		//IPS_SetPosition($letzterTagEurID, 200);
		$letzte7TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte7Tage", 2,'Euro',false,210);
		//IPS_SetVariableCustomProfile($letzte7TageEurID,'Euro');
		//IPS_SetPosition($letzte7TageEurID, 210);
		$letzte30TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte30Tage", 2,'Euro',false,220);
		//IPS_SetVariableCustomProfile($letzte30TageEurID,'Euro');	
		//IPS_SetPosition($letzte30TageEurID, 220);
		$letzte360TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte360Tage", 2,'Euro',false,230);
		//IPS_SetVariableCustomProfile($letzte360TageEurID,'Euro');
		//IPS_SetPosition($letzte360TageEurID, 230);
	  	
   	}  // ende foreach
	
	/* Tab Zusammenfassung
     * html basierte Tabellen ebenfalls anzeigen, Name Zaehlervariablen als Identifier für rechtes Tab
     * es werden Variablen verwendet, keine Kategorien
     */

	$ID = CreateVariableByName($CategoryIdData, "Zusammenfassung", 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	IPS_SetPosition($ID,9990);
	$tableID = CreateVariableByName($ID, "Historie-Energie", 3);
	IPS_SetVariableCustomProfile($tableID,'~HTMLBox');			
	$webfront_links["Zusammenfassung"]["Energievorschub der letzten Tage"][$tableID]["NAME"]="Zaehlervariablen";
	$webfront_links["Zusammenfassung"]["Energievorschub der letzten Tage"][$tableID]["PANE"]=false;
		
	$regID = CreateVariableByName($ID, "Aktuelle-Energie", 3);
	IPS_SetVariableCustomProfile($regID,'~HTMLBox');			
	$webfront_links["Zusammenfassung"]["Energieregister"][$regID]["NAME"]="Zaehlervariablen";	
	$webfront_links["Zusammenfassung"]["Energieregister"][$regID]["PANE"]=false;	
	
	/* Tab Kurven
     * html basierte Kurven ebenfalls anzeigen, Name Zaehlervariablen als Identifier für rechtes Tab 
     */
	$KurvenID = CreateVariableByName($CategoryIdData, "Kurven", 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	IPS_SetPosition($ID,9991);

    echo "-----------------------------------------*\n";

	$Meter=$Amis->writeEnergyRegistertoArray($MeterConfig,true);
	SetValue($tableID,$Amis->writeEnergyRegisterTabletoString($Meter));

    echo "-----------------------------------------*\n";
	SetValue($regID,$Amis->writeEnergyRegisterValuestoString($Meter));


/******************* Timer Definition ******************************
 *
 *   Momentanwerte Abfragen alle 60 Sekunden machen
 *   Die Periodenwerte einmal am Tag updaten
 *
 */
	
	$scriptIdMomAbfrage   = IPS_GetScriptIDByName('MomentanwerteAbfragen', $CategoryIdApp);
	IPS_SetScriptTimer($scriptIdMomAbfrage, 60);  /* alle Minuten */

	$BerechnePeriodenwerteID=IPS_GetScriptIDByName('BerechnePeriodenwerte',$CategoryIdApp);
	$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $BerechnePeriodenwerteID);
	if ($tim1ID==false)
		{
		echo "Timer Aufruftimer erstellen.\n";
		$tim1ID = IPS_CreateEvent(1);
		IPS_SetParent($tim1ID, $BerechnePeriodenwerteID);
		IPS_SetName($tim1ID, "Aufruftimer");
		IPS_SetEventCyclic($tim1ID,2,1,0,0,0,0);
		IPS_SetEventCyclicTimeFrom($tim1ID,1,rand(1,59),0);  /* immer um 01:xx , nicht selbe Zeit damit keine Zugriffsverletzungen auf der Drei Homepage entstehen */
		}
	IPS_SetEventActive($tim1ID,true);
    
/* ----------------------------------------------------------------------------------------------------------------------------
 * WebFront Installation
 *  ----------------------------------------------------------------------------------------------------------------------------
 */
	foreach ($webfront_links as $Name => $webfront_group)
	   	{
        //$webfront_links[$Name]["STYLE"]=true;                   // für easySetupWebfront
        $webfront_links[$Name]["CONFIG"]=array(IPSHEAT_WFCSPLITPANEL);
           }
    echo "****************Ausgabe Webfront Links               ";    
	print_r($webfront_links);

	if ($WFC10_Enabled)
		{
        $categoryId_WebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
        $configWf=$configWFront["Administrator"];
        /* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
        CreateWFCItemCategory  ($configWf["ConfigId"], 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_WebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

        if (true)          // neue Webfront Erstellung
            {
            $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID

            $wfcHandling->CreateWFCItemTabPane("HouseTPA", $configWf["TabPaneParent"],  $configWf["TabPaneOrder"], "", "HouseRemote");  /* macht das Haeuschen in die oberste Leiste */
            $wfcHandling->CreateWFCItemTabPane($configWf["TabPaneItem"], "HouseTPA", 30, $configWf["TabPaneName"], $configWf["TabPaneIcon"]);    /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

            $configWf["TabPaneParent"] = "HouseTPA"; $configWf["TabPaneOrder"] = 30;        // das Haeuschen ist dazwischen geschoben
            //$configWf["Path"] .="Test";            // sonst loescht er immer die aktuellen Kategorien
            $wfcHandling->easySetupWebfront($configWf,$webfront_links, "Administrator", true);

            //$wfc=$wfcHandling->read_wfc(1);
            $wfc=$wfcHandling->read_wfcByInstance(false,1);                 // false interne Datanbank für Config nehmen
            foreach ($wfc as $index => $entry)                              // Index ist User, Administrator
                {
                echo "\n------$index:\n";
                $wfcHandling->print_wfc($wfc[$index]);
                } 
            $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       
            }
        else               // alte Webfront erstellung
            {
            /* Kategorien für Administrator werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */

            $configWf=$configWFront["Administrator"];
            echo "====================================================================================\n";
            /* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
            CreateWFCItemCategory  ($configWf["ConfigId"], 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_WebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

            /* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */

            echo "\nWebportal LocalAccess TabPane installieren in: ".$configWf["Path"]." \n";
            /* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
            echo "Webfront TabPane mit Parameter : ".$configWf["ConfigId"]." ".$configWf["TabPaneItem"]." ".$configWf["TabPaneParent"]." ".$configWf["TabPaneOrder"]." ".$configWf["TabPaneName"]." ".$configWf["TabPaneIcon"]."\n";
            CreateWFCItemTabPane   ($configWf["ConfigId"], "HouseTPA", $configWf["TabPaneParent"],  $configWf["TabPaneOrder"], "", "HouseRemote");  /* macht das Haeuschen in die oberste Leiste */
            CreateWFCItemTabPane   ($configWf["ConfigId"],$configWf["TabPaneItem"], "HouseTPA", 30, $configWf["TabPaneName"], $configWf["TabPaneIcon"]);    /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

            $categoryId_WebFrontAdministrator         = CreateCategoryPath($configWf["Path"]);
            IPS_SetHidden($categoryId_WebFrontAdministrator,true);
            //EmptyCategory($categoryId_WebFrontAdministrator);

            foreach ($webfront_links as $Name => $webfront_group)
                {
                /* Das erste Arrayfeld bestimmt die Tabs in denen jeweils ein linkes und rechtes Feld erstellt werden: AMIS, Homematic etc.
                * Der Name für die Felder wird selbst erfunden.
                */			
                $categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontAdministrator, 10);    /* Unterverzeichnis unter AMIS, zB pro Typ */
                $categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFrontTab, 10);			/* Zwei Seiten */
                $categoryIdRight = CreateCategory('Right', $categoryId_WebFrontTab, 20);
                //EmptyCategory($categoryIdLeft);
                //EmptyCategory($categoryIdRight);
                //EmptyCategory($categoryId_WebFrontTab);
                echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n";

                $tabItem = $configWf["TabPaneItem"].$Name;
                if ( exists_WFCItem($configWf["ConfigId"], $tabItem) )
                    {
                    echo "Webfront ".$configWf["ConfigId"]." (".IPS_GetName($configWf["ConfigId"]).")  Gruppe ".$Name." löscht TabItem : ".$tabItem."\n";
                    DeleteWFCItems($configWf["ConfigId"], $tabItem);
                    }
                else
                    {
                    echo "Webfront ".$configWf["ConfigId"]." (".IPS_GetName($configWf["ConfigId"]).")  Gruppe ".$Name." TabItem : ".$tabItem." nicht mehr vorhanden.\n";
                    }				
                IPS_ApplyChanges($configWf["ConfigId"]);
                echo "Webfront ".$configWf["ConfigId"]." erzeugt TabItem :".$tabItem." in ".$configWf["TabPaneItem"]."\n";
                //CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
                CreateWFCItemSplitPane ($configWf["ConfigId"], $tabItem, $configWf["TabPaneItem"],    0,     $Name,     "", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
                CreateWFCItemCategory  ($configWf["ConfigId"], $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
                CreateWFCItemCategory  ($configWf["ConfigId"], $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

                //CreateLinkByDestination("Read Meter", $MeterReadID,    $categoryIdLeft,  0);
                foreach ($webfront_group as $Group => $webfront_link)
                    {
                    //if left
                    //$categoryIdGroup  = CreateCategory($Group,  $categoryIdLeft, 10);
                    $categoryIdGroup  = CreateVariableByName($categoryIdLeft, $Group, 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
                    EmptyCategory($categoryIdGroup);	
                    if (is_array($webfront_link))			
                        {
                        foreach ($webfront_link as $OID => $link)
                            {
                            //echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
                            if ( (isset($link["NAME"])) && ( $link["NAME"]=="Zaehlervariablen" ))
                                {
                                echo "erzeuge Link mit Name ".$Group."-".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdRight."\n";
                                CreateLinkByDestination($Group."-".$link["NAME"], $OID,    $categoryIdRight,  20);
                                echo "\n";
                                }
                            elseif (isset($link["NAME"]))
                                {
                                echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft." / ".$categoryIdGroup."\n";
                                CreateLinkByDestination($link["NAME"], $OID,    $categoryIdGroup,  20);
                                echo "\n";
                                }
                            }
                        }
                    }
                }
            }
        }
    else
        {
        /* Admin not enabled, alles loeschen */
            DeleteWFCItems($WFC10_ConfigId, "HouseTPA");
        }

	if ($WFC10User_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */

		$categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
        $configWf=$configWFront["User"];
		echo "====================================================================================\n";
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$configWf["ConfigId"]." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
		CreateWFCItemCategory  ($configWf["ConfigId"], 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($configWf["ConfigId"],"root",false	);				
		@WFC_UpdateVisibility ($configWf["ConfigId"],"dwd",false	);

		echo "\nWebportal LocalAccess TabPane installieren in: ".$configWf["Path"]." \n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$configWf["ConfigId"]." ".$configWf["TabPaneItem"]." ".$configWf["TabPaneParent"]." ".$configWf["TabPaneOrder"]." ".$configWf["TabPaneName"]." ".$configWf["TabPaneIcon"]."\n";
		CreateWFCItemTabPane   ($configWf["ConfigId"], "HouseTPU", $configWf["TabPaneParent"], $configWf["TabPaneOrder"], "", "HouseRemote");  /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($configWf["ConfigId"],$configWf["TabPaneItem"], "HouseTPU", 20, $configWf["TabPaneName"], $configWf["TabPaneIcon"]);    /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

		/*************************************/

		$categoryId_WebFrontUser         = CreateCategoryPath($configWf["Path"]);
		IPS_SetHidden($categoryId_WebFrontUser,true);
		
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontUser, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			$tabItem = $configWf["TabPaneItem"].$Name;
			if ( exists_WFCItem($configWf["ConfigId"], $tabItem) )
			 	{
				echo "Webfront ".$configWf["Path"]." (".IPS_GetName($configWf["ConfigId"]).")  Gruppe ".$Name." löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($configWf["ConfigId"], $tabItem);
				}
			else
				{
				echo "Webfront ".$configWf["ConfigId"]." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}	
			IPS_ApplyChanges($configWf["ConfigId"]);							
			echo "Webfront ".$configWf["ConfigId"]." erzeugt TabItem :".$tabItem." in ".$configWf["TabPaneItem"]."\n";
			CreateWFCItemTabPane   ($configWf["ConfigId"], $tabItem, $configWf["TabPaneItem"], 0, $Name, "");
			CreateWFCItemCategory  ($configWf["ConfigId"], $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);

			foreach ($webfront_group as $Group => $webfront_link)
				 {
				foreach ($webfront_link as $OID => $link)
					{
					//echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ( (isset($link["NAME"])) && ($Group=="Auswertung") )
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontTab,  20);
				 		}
					}
    			}
			}
		}
	else
	   {
	   /* User not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert isnd
		*/
	   DeleteWFCItems($configWf["Path"], "HouseTPU");
	   EmptyCategory($categoryId_WebFrontUser);
	   }

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_MobileWebFront         = CreateCategoryPath($Mobile_Path);
		IPS_SetHidden($categoryId_MobileWebFront,true);	
			
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_MobileWebFront, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			foreach ($webfront_group as $Group => $webfront_link)
				 {
				foreach ($webfront_link as $OID => $link)
					{
					//echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ( (isset($link["NAME"])) && ($Group=="Auswertung") )
				 		{
				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontTab,  20);
				 		}
					}
    			}
			}
		}
	else
	   {
	   /* Mobile not enabled, alles loeschen */
	   }

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_RetroWebFront         = CreateCategoryPath($Retro_Path);
		}
	else
	   {
	   /* Retro not enabled, alles loeschen */
	   }
    

    echo "=================================================================\n";

    $webOps = new webOps();
    $categoryId_SmartMeter        = CreateCategory('SmartMeter',        $CategoryIdData, 80);
    $pnames = ["Update","Calculate","Sort"];
    $buttonsId = $webOps->createSelectButtons($pnames,$categoryId_SmartMeter, $scriptIdAmis);
    $statusSmartMeterID = CreateVariableByName($categoryId_SmartMeter, "SmartMeterStatus", 3,'~HTMLBox');

    // function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
	$variableIdInterActiveHTML = CreateVariableByName($categoryId_SmartMeter, "InterActive", 3 , '~HTMLBox', 'Information', 300,  false, '<iframe frameborder="0" width="100%" height="600px"  src="../user/Guthabensteuerung/GuthabensteuerungReceiver.php"</iframe>' );

	$webfront_links=array(
        "SmartMeter"     => array(
            "Left"          => array(),
            "Select"         => array(),
            "@CONFIG"       => array(
                "style"         =>  "WFCSplitPanel",
                "width"         =>  10,
                "right"         => "Select",
                "left"          => "Left",
                            ),
                        ),
        "@CONFIG" => array( ),                // sonst wird Smart Meter ein Category Pane und kein wie gewollt Splitpane
                );

        $webfront_links["SmartMeter"]["Left"]        = array(
                $statusSmartMeterID => array(
                    "NAME"              => "Status",
                    "ORDER"             => 10,
                    "ADMINISTRATOR"     => true,
                    "PANE"              => true,
                            ),
                $variableIdInterActiveHTML => array(
                    "NAME"              => "InterActive",
                    "ORDER"             => 100,
                    "ADMINISTRATOR"     => true,
                    "PANE"              => true,
                            ),
                        );

        $webfront_links["SmartMeter"]["Select"]        = array(
                $buttonsId[0]["ID"] => array(
                    "NAME"              => " ",
                    "ORDER"             => 200,
                    "ADMINISTRATOR"     => true,
                    "PANE"              => true,
                            ),
                $buttonsId[1]["ID"] => array(
                    "NAME"              => " ",
                    "ORDER"             => 210,
                    "ADMINISTRATOR"     => true,
                    "PANE"              => true,
                            ),
                $buttonsId[2]["ID"] => array(
                    "NAME"              => " ",
                    "ORDER"             => 220,
                    "ADMINISTRATOR"     => true,
                    "PANE"              => true,
                            ),
                        );

	if ($WFC10_Enabled) 
		{
        $categoryId_WebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
        $configWf=$configWFront["Administrator"];
        $wfcHandling->read_WebfrontConfig($WFC10_ConfigId);         // register Webfront Confígurator ID

        $configWf["TabPaneParent"] = "HouseTPA"; $configWf["TabPaneOrder"] = 30;        // das Haeuschen ist dazwischen geschoben
        $wfcHandling->easySetupWebfront($configWf,$webfront_links, "Administrator", true);

        $wfcHandling->write_WebfrontConfig($WFC10_ConfigId);       // nur hier wird geschrieben
        }

    echo "=================================================================\n";
    echo "AMIS Installation erfolgreich abgeschlossen.\n";



?>