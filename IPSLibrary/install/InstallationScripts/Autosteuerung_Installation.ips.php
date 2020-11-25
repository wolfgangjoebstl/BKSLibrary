<?

	/**@defgroup Autosteuerung
	 *
	 * Script um automatisch irgendetwas ein und auszuschalten
	 *
	 *
	 * @file          Autosteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/

    $startexec=microtime(true);     /* Laufzeitmessung */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    IPSUtils_Include ('Autosteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Autosteuerung');
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Autosteuerung\Autosteuerung_Configuration.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung_Class.inc.php");

	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	$ergebnis1=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	$ergebnis2=$moduleManager->VersionHandler()->GetVersion('Autosteuerung');
	//echo "\nIP Symcon Kernelversion    : ".IPS_GetKernelVersion();
	//echo "\nIPS ModulManager Version   : ".$ergebnis1;
	//echo "\nModul Autosteuerung Version : ".$ergebnis2."   Status : ".$moduleManager->VersionHandler()->GetModuleState()."\n";
	
 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	//echo $inst_modules."\n";

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$scriptIdWebfrontControl   = IPS_GetScriptIDByName('WebfrontControl', $CategoryIdApp);
	$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);
	$scriptIdHeatControl   = IPS_GetScriptIDByName('Autosteuerung_HeatControl', $CategoryIdApp);
	$scriptIdAlexaControl   = IPS_GetScriptIDByName('Autosteuerung_AlexaControl', $CategoryIdApp);
	
	$eventType='OnChange';
	$categoryId_Autosteuerung  = CreateCategory("Ansteuerung", $CategoryIdData, 10);            // Unterfunktionen wie Stromheizung, Anwesenheitsberechnung sind hier
	$categoryId_Status  = CreateCategory("Status", $CategoryIdData, 100);                 // ein paar Spiegelregister für die Statusberechnung, Debounce Funktion

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
	
	$modulhandling = new ModuleHandling();
	$Alexa=$modulhandling->getInstances("Alexa");
	$countAlexa = sizeof($Alexa);
	echo "Es gibt insgesamt ".$countAlexa." Alexa Instanzen.\n";
	if ($countAlexa>0)
		{
		$config=IPS_GetConfiguration($modulhandling->getInstances("Alexa")[0]);
		echo "   ".$config."\n";
		}	

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId fuer Autosteuerung, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."  (".$instanz.")\n";
		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r($result);
		}
	echo "\n";

/*******************************
 *
 * Webfront Konfiguration aus ini Datei einlesen
 *
 ********************************/
 	
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled==true)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10',"AutoTPA");
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10',"roottp");
		$WFC10_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10',"");
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10',"Car");
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10');
		$WFC10_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10');
		$WFC10_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10');
		$WFC10_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10');
		$WFC10_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10');
		echo "WF10 Administrator\n";
		echo "  Path          : ".$WFC10_Path."\n";
		echo "  ConfigID      : ".$WFC10_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10_ConfigId)).".".IPS_GetName($WFC10_ConfigId).")\n";		
		echo "  TabPaneItem   : ".$WFC10_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10_TabItem."\n";
		echo "  TabName       : ".$WFC10_TabName."\n";
		echo "  TabIcon       : ".$WFC10_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10_TabOrder."\n";
		}

	echo "\n";
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10User',"AutoTPU");
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10User',"roottp");
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10User',"");
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10User',"Car");
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10User');
		$WFC10User_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10User');
		$WFC10User_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10User');
		$WFC10User_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10User');
		$WFC10User_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10User');
		echo "WF10 User \n";
		echo "  Path          : ".$WFC10User_Path."\n";
		echo "  ConfigID      : ".$WFC10User_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10User_ConfigId)).".".IPS_GetName($WFC10User_ConfigId).")\n";
		echo "  TabPaneItem   : ".$WFC10User_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10User_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10User_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10User_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10User_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10User_TabItem."\n";
		echo "  TabName       : ".$WFC10User_TabName."\n";
		echo "  TabIcon       : ".$WFC10User_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10User_TabOrder."\n";
		}		

	echo "\n";
	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
	if ($Mobile_Enabled==true)
		{	
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile \n";
		echo "  Path          : ".$Mobile_Path."\n";		
		}

	$Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);
	if ($Retro_Enabled==true)
		{	
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		echo "  Path          : ".$Retro_Path."\n";		
		}	

/*******************************
 *
 * Variablen Profile Vorbereitung
 *
 ********************************/

	$name="Bedienung";
	$pname="AusEinAuto";
	if (IPS_VariableProfileExists($pname) == false)
		{
			//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
		//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." erstellt;\n";
		}
	$pname="AusEin";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   	//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	IPS_SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	IPS_SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
	   	echo "Profil ".$pname." erstellt;\n";
		}
	$pname="AusEin-Boolean";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   	//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	IPS_SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, false, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	IPS_SetVariableProfileAssociation($pname, true, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
	   	echo "Profil ".$pname." erstellt;\n";
		}
		
	$pname="NeinJa";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   	//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	IPS_SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, 0, "Nein", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	IPS_SetVariableProfileAssociation($pname, 1, "Ja", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
  	   	//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   	echo "Profil ".$pname." erstellt;\n";
		}
	$pname="Null";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   	//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	IPS_SetVariableProfileValues($pname, 0, 0, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, 0, "Null", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   	echo "Profil ".$pname." erstellt;\n";
		}
	$pname="SchlafenAufwachenMunter";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   	//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, 0, "Schlafen", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	IPS_SetVariableProfileAssociation($pname, 1, "Aufwachen", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
  	   	IPS_SetVariableProfileAssociation($pname, 2, "Munter", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
  	   	//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   	echo "Profil ".$pname." erstellt;\n";
		}
	$pname="AusEinAutoP1P2P3P4";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   	//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	IPS_SetVariableProfileValues($pname, 0, 6, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	IPS_SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
  	   	IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x615c6e); //P-Name, Value, Assotiation, Icon, Color             
  	   	IPS_SetVariableProfileAssociation($pname, 3, "Profil 1", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
  	   	IPS_SetVariableProfileAssociation($pname, 4, "Profil 2", "", 0x3ec127); //P-Name, Value, Assotiation, Icon, Color
  	   	IPS_SetVariableProfileAssociation($pname, 5, "Profil 3", "", 0x5ea147); //P-Name, Value, Assotiation, Icon, Color
  	   	IPS_SetVariableProfileAssociation($pname, 6, "Profil 4", "", 0x7ea167); //P-Name, Value, Assotiation, Icon, Color
  	   	//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   	echo "Profil ".$pname." erstellt;\n";
		}


/*******************************
 *
 * Links für Webfront identifizieren
 *
 * Webfront Links werden für alle Autosteuerungs Default Funktionen erfasst. Es werden auch gleich die 
 * Default Variablen dazu angelegt
 *
 * Anwesenheitserkennung, Alarmanlage, GutenMorgenWecker, SilentMode, Ventilatorsteuerung, Stromheizung
 *
 * funktioniert für jeden beliebigen Vartiablennamen, zumindest ein/aus Schalter wird angelegt
 *
 * Folgende Gruppen können in Autosteuerung_SetSwitches() definiert werden:
 *
 *    Anwesenheitssimulation, Anwesenheitserkennung, Alarmanlage,  Ventilatorsteuerung, GutenMorgenWecker, SilentMode, Stromheizung, Alexa
 *
 * Für jede dieser Gruppen kann festgelegt werden ob es ein Eigenes Tab gibt oder die Informationen auf einer Seite zusammengefasst werden.
 *
 ********************************/
    
    echo "\n";
    echo "====================================================================\n";
    echo "\n";

	$AutoSetSwitches = Autosteuerung_SetSwitches();
    //print_r($AutoSetSwitches);
    $register=new AutosteuerungHandler($scriptIdAutosteuerung);
	
	$setup = Autosteuerung_Setup();
    if ( isset($setup["LogDirectory"]) == false ) $setup["LogDirectory"]="C:/Scripts/Autosteuerung/";
	
    /* verschiedene Loggingspeicher initialisieren, damit kein Fehler wenn nicht installiert wegen Konfiguration aber referenziert da im Code */
    $categoryId_NachrichtenAuto    = CreateCategory('Nachrichtenverlauf-Autosteuerung',   $CategoryIdData, 20);
	$inputAuto = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenAuto, 0, "",null,null,""  );   /* Nachrichtenzeilen werden automatisch von der Logging Klasse gebildet */
    $log_Autosteuerung=new Logging($setup["LogDirectory"]."Autosteuerung.csv",$inputAuto,IPS_GetName(0).";Autosteuerung;");

    $categoryId_NachrichtenAnwe    = CreateCategory('Nachrichtenverlauf-AnwesenheitErkennung',   $CategoryIdData, 20);
    $inputAnwe = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenAnwe, 0, "",null,null,""  );     /* Nachrichtenzeilen werden automatisch von der Logging Klasse gebildet */
    $log_Anwesenheitserkennung=new Logging($setup["LogDirectory"]."Anwesenheitserkennung.csv",$inputAnwe,IPS_GetName(0).";Anwesenheitserkennung;");

	$categoryId_Schaltbefehle = CreateCategory('Schaltbefehle-Anwesenheitssimulation',   $CategoryIdData, 20);
    $inputSchalt=CreateVariable("Schaltbefehle",3,$categoryId_Schaltbefehle, 0,'',null,'');
    //$categoryId_Wochenplan = CreateCategory('Wochenplan-Stromheizung',   $CategoryIdData, 20);
    //$inputWoche=IPS_GetVariableIDByName("Wochenplan",$categoryId_Wochenplan);							// nicht generieren, wird erst später von den Routinen erstellt
    $categoryId_Alexa = CreateCategory('Nachrichten-Alexa',   $CategoryIdData, 20);
    $inputAlexa=CreateVariable("Nachrichten",3,$categoryId_Alexa, 0,'',null,'');
	$categoryId_Control = CreateCategory('ReglerAktionen-Stromheizung',   $CategoryIdData, 20);
	$inputControl=CreateVariable("ReglerAktionen",3,$categoryId_Control, 0,'',null,'');
    
    $log_Anwesenheitserkennung->LogMessage('Autosteuerung Installation aufgerufen');
    $log_Anwesenheitserkennung->LogNachrichten('Autosteuerung Installation aufgerufen');      

	$webfront_links=array();
	foreach ($AutoSetSwitches as $AutoSetSwitch)
		{
        // CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false)
        if (strtoupper($AutoSetSwitch["PROFIL"])=="NULL")       // leere Optionen als String anlegen, damit sie nicht eine falsche 0 anzeigen
            { 
		    $AutosteuerungID = CreateVariableByName($categoryId_Autosteuerung,$AutoSetSwitch["NAME"], 3, "", false,  0, $scriptIdWebfrontControl);   /* 0 Boolean 1 Integer 2 Float 3 String */
            SetValue($AutosteuerungID,"");
            }
		else $AutosteuerungID = CreateVariableByName($categoryId_Autosteuerung, $AutoSetSwitch["NAME"], 1, $AutoSetSwitch["PROFIL"], false, 0, $scriptIdWebfrontControl );  /* 0 Boolean 1 Integer 2 Float 3 String */        
		echo "-------------------------------------------------------\n";
		echo "Bearbeite Autosetswitch : ".$AutoSetSwitch["NAME"]."  Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";
		$webfront_links[$AutosteuerungID]["TAB"]="Autosteuerung";
		$webfront_links[$AutosteuerungID]["OID_L"]=$AutosteuerungID;
        $webfront_links[$AutosteuerungID]["OID_R"]=$inputAuto;

		/* Spezialfunktionen hier abarbeiten, default am Ende des Switches */
    	switch (strtoupper($AutoSetSwitch["NAME"]))
			{
			case "ANWESENHEITSERKENNUNG":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Erkennung'));            
				
                /* Setup Standard Variables */
                echo "   Variablen für Anwesenheitserkennung in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";			
				$StatusAnwesendID=CreateVariable("StatusAnwesend",0, $AutosteuerungID,0,"~Presence",null,null,"");
				$StatusAnwesendZuletztID=CreateVariable("StatusAnwesendZuletzt",0, $AutosteuerungID,0,"~Presence",null,null,"");
				IPS_SetHidden($StatusAnwesendZuletztID,true);
				$register->registerAutoEvent($StatusAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

				if ($countAlexa>0) 	$StatusSchalterAnwesendID=CreateVariable("SchalterAnwesend",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdAlexaControl,null,"");	
				else $StatusSchalterAnwesendID=CreateVariable("SchalterAnwesend",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdWebfrontControl,null,"");			
				$register->registerAutoEvent($StatusSchalterAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusSchalterAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusSchalterAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);				
                
                /* Geofency Variables */
                $operate=new AutosteuerungOperator();
                $geofencies=$operate->getGeofencyInformation();    
                if (sizeof($geofencies)>0)
                    {
                    $order=100;
                    foreach ($geofencies as $geofency => $Address)
                        {
                        CreateLinkByDestination("Geofency-".IPS_GetName($geofency), $geofency,    $AutosteuerungID, $order);
                        $order+=10;
                        }
                    }
					
				/* einfache Visualisierung der Bewegungswerte, testweise */
				$StatusTableMapHtml   = CreateVariable("StatusTableView",   3 /*String*/,  $AutosteuerungID, 1010, '~HTMLBox');

                $operate=new AutosteuerungOperator();
                $geofencies=$operate->getGeofencyInformation();
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {
    				$webfront_links[$AutosteuerungID]["OID_R"]=$inputAnwe;				
                    /* mehr Informationen anzeigen, wenn wir einen eigenen Tab haben. */
                    }
				break;
			case "ALARMANLAGE":
				echo "   Variablen für Alarmanlage in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";
				$StatusAnwesendID=CreateVariable("StatusAlarmanlage",0, $AutosteuerungID,0,"~Presence",null,null,"");
				$StatusAnwesendZuletztID=CreateVariable("StatusAlarmanlageZuletzt",0, $AutosteuerungID,0,"~Presence",null,null,"");
				IPS_SetHidden($StatusAnwesendZuletztID,true);
				$register->registerAutoEvent($StatusAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				
				if ($countAlexa>0) 	$StatusSchalterAnwesendID=CreateVariable("SchalterAlarmanlage",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdAlexaControl,null,"");	
				else $StatusSchalterAnwesendID=CreateVariable("SchalterAlarmanlage",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdWebfrontControl,null,"");			
				$register->registerAutoEvent($StatusSchalterAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusSchalterAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusSchalterAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
                break;										
			case "GUTENMORGENWECKER":
				echo "   Variablen für GutenMorgenWecker in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";		
	   			// CreateVariable($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')					
				$WeckerID = CreateVariable("Wecker", 1, $AutosteuerungID, 0, "SchlafenAufwachenMunter",null,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
				$register->registerAutoEvent($WeckerID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$WeckerID,true);
				AC_SetAggregationType($archiveHandlerID,$WeckerID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
								
				$Wochenplan_ID = @IPS_GetEventIDByName("WeckerKalender", $WeckerID);
 				if ($Wochenplan_ID === false)
					{
					/* Wochenplan muss entweder ueber einer Variable oder über einem Script angeordnet sein.
					 *   wenn über Variable, dann gibt es ACtive Scripts im Action Table zum Eintragen 
					 *   wenn über einem Script muss IPS_Target ausgewertet werden. -> flexibler ...
					 */
					$Wochenplan_ID = IPS_CreateEvent(2);                  //Wochenplan Ereignis
					IPS_SetEventScheduleGroup($Wochenplan_ID, 0, 1); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 1, 2); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 2, 4); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 3, 8); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 4, 16); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 5, 32); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)
					IPS_SetEventScheduleGroup($Wochenplan_ID, 6, 64); //Mo - So (1 + 2 + 4 + 8 + 16+ 32 + 64)

			    	IPS_SetEventScheduleAction($Wochenplan_ID, 0, "Schlafen",   8048584, "SetValue(".(string)$WeckerID.",0)");
			    	IPS_SetEventScheduleAction($Wochenplan_ID, 1, "Aufwachen", 16750848, "SetValue(".(string)$WeckerID.",1)");
			    	IPS_SetEventScheduleAction($Wochenplan_ID, 2, "Munter",    32750848, "SetValue(".(string)$WeckerID.",2)");

					IPS_SetParent($Wochenplan_ID, $WeckerID);         //Ereignis zuordnen
					IPS_SetName($Wochenplan_ID,"WeckerKalender");
					IPS_SetEventActive($Wochenplan_ID, true);
					}
				else
					{
					/*gruendlich loeschen */
					for ($j=0;$j<8;$j++)
						{
						for ($i=0;$i<100;$i++)
							{
							@IPS_SetEventScheduleGroupPoint($Wochenplan_ID, $j /*Gruppe*/, $i /*Schaltpunkt*/, -1/*H*/, 0/*M*/, 0/*s*/, 0 /*Aktion*/);
							}
						}  
					//echo "Wochenplan Config ausgeben:\n";
					//$result_Wp=IPS_GetEvent($Wochenplan_ID);
					//print_r($result_Wp["ScheduleGroups"]);
					IPS_SetEventScheduleAction($Wochenplan_ID, 0, "Schlafen",   8048584, "SetValue(".(string)$WeckerID.",0);");
					IPS_SetEventScheduleAction($Wochenplan_ID, 1, "Aufwachen", 16750848, "SetValue(".(string)$WeckerID.",1);");
					IPS_SetEventScheduleAction($Wochenplan_ID, 2, "Munter",    32750848, "SetValue(".(string)$WeckerID.",2);");
					}
				if (true)
					{			
					$i=0;
				//Montag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 0 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 0/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 0 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 0/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 0 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 0/*M*/, 0/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 0 /*Gruppe*/, $i++ /*Schaltpunkt*/, 22/*H*/, 20/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Dienstag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 1 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 1/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 1 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 1/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 1 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 1/*M*/, 1/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 1 /*Gruppe*/, $i++ /*Schaltpunkt*/, 22/*H*/, 20/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Mittwoch
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 2/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 2/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 2/*M*/, 2/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i++ /*Schaltpunkt*/, 22/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Donnerstag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 3 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 3/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 3 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 3/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 3 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 3/*M*/, 3/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 3 /*Gruppe*/, $i++ /*Schaltpunkt*/, 22/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Freitag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 4 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 3/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 4 /*Gruppe*/, $i++ /*Schaltpunkt*/, 5/*H*/, 30/*M*/, 4/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 4 /*Gruppe*/, $i++ /*Schaltpunkt*/, 9/*H*/, 0/*M*/, 4/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 4 /*Gruppe*/, $i++ /*Schaltpunkt*/, 23/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Samstag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 5 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 3/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 5 /*Gruppe*/, $i++ /*Schaltpunkt*/, 8/*H*/, 30/*M*/, 5/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 5 /*Gruppe*/, $i++ /*Schaltpunkt*/, 10/*H*/, 0/*M*/, 5/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 5 /*Gruppe*/, $i++ /*Schaltpunkt*/, 23/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
				//Sonntag
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 6 /*Gruppe*/, $i++ /*Schaltpunkt*/, 0/*H*/, 0/*M*/, 3/*s*/, 0 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 6 /*Gruppe*/, $i++ /*Schaltpunkt*/, 8/*H*/, 30/*M*/, 6/*s*/, 1 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 6 /*Gruppe*/, $i++ /*Schaltpunkt*/, 12/*H*/, 0/*M*/, 6/*s*/, 2 /*Aktion*/);
					IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 6 /*Gruppe*/, $i++ /*Schaltpunkt*/, 23/*H*/, 30/*M*/, 0/*s*/, 0 /*Aktion*/);
					}
					
				if (false)		/* for test purposes only */
					{
					for ($i = 0; $i < 20; $i++) 
						{
						IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, $i*3 /*Schaltpunkt*/, 18/*H*/, $i*3/*M*/, 2/*s*/, 0 /*Aktion*/);	
						IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, ($i*3)+1 /*Schaltpunkt*/, 18/*H*/, ($i*3)+1/*M*/, 2/*s*/, 1 /*Aktion*/);
						IPS_SetEventScheduleGroupPoint($Wochenplan_ID, 2 /*Gruppe*/, ($i*3)+2 /*Schaltpunkt*/, 18/*H*/, ($i*3)+2/*M*/, 2/*s*/, 2 /*Aktion*/);					
						}
					}	
						
				CreateLinkByDestination("WeckerKalender", $Wochenplan_ID, $AutosteuerungID,  10);
				$EventInfos = IPS_GetEvent($Wochenplan_ID);
				//print_r($EventInfos);
				break;
			case "VENTILATORSTEUERUNG":	
                echo "   Variablen für Ventilatorsteuerung in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";	
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')		
				$TemperaturID = CreateVariable("Temperatur", 2, $AutosteuerungID, 0, "",null,0,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */			
				$TemperaturZuletztID = CreateVariable("TemperaturZuletzt", 2, $AutosteuerungID, 0, "",null,0,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */			
				break;
			case "ANWESENHEITSSIMULATION":
                $AnwesenheitssimulationID = CreateVariableByName($categoryId_Autosteuerung,"Anwesenheitssimulation",1,"AusEinAuto",null,0,$scriptIdWebfrontControl);    
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Schaltbefehle'));
				$simulation=new AutosteuerungAnwesenheitssimulation();
                echo " --> AutosteuerungAnwesenheitssimulation erfolgreich aufgerufen.\n";
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {                
				    $webfront_links[$AutosteuerungID]["OID_R"]=$inputSchalt;									
                    }
				break;	
			case "STROMHEIZUNG":
				/* Stromheizung macht ein Progamm für die nächsten 14 Tage. Funktioniert derzeit für die Stromheizung und den eigenen Temperaturregler
				 * Neue Funktion befüllt die Tabelle automatisch. Es gibt eine eigene Klasse für diese Funktion: AutosteuerungStromheizung
				 * der Heizungsregler funktioniert in der Klasse AutosteuerungRegler
				 */
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Wochenplan'));
				echo "    AutosteuerungRegler initialisieren.\n";
				$kalender=new AutosteuerungStromheizung();
				echo "    Wochenkalender neu aufsetzen.\n";
				$kalender->SetupKalender(0,"~Switch");	/* Kalender neu aufsetzen, alle Werte werden geloescht, immer bei Neuinstallation */
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
				$categoryId_Wochenplan = IPS_getCategoryIdByName('Wochenplan-Stromheizung',   $CategoryIdData);
				$inputWoche=IPS_GetVariableIDByName("Wochenplan",$categoryId_Wochenplan);				
				echo "    noch ein paar Variablen dazu in $inputWoche anlegen. Action Script $scriptIdHeatControl\n";
				$oid=CreateVariable("AutoFill",1,$inputWoche, 1000,'AusEinAutoP1P2P3P4',$scriptIdHeatControl,null,'');  // $Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon=''
				$descrID=CreateVariable("Beschreibung",3,$inputWoche, 1010,'',null,null,'');  // $Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon=''
				echo "    und eine neue Kategorie in $CategoryIdData.\n";				
				$categoryId_Schaltbefehle = CreateCategory('ReglerAktionen-Stromheizung',   $CategoryIdData, 20);
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')				
				$vid=CreateVariable("ReglerAktionen",3,$categoryId_Schaltbefehle, 0,'',null,'');
				$simulation=new AutosteuerungRegler();
				echo "    webfrontlinks noch setzen und fertig.\n";													
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {                
	    			$webfront_links[$AutosteuerungID]["OID_R"]=$inputWoche;
                    }
				break;	
			case "GARTENSTEUERUNG":
				if ( isset( $installedModules["Gartensteuerung"] ) == true )
					{
                    $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Gartensteuerung'));                        
                    $moduleManagerGS = new IPSModuleManager('Gartensteuerung',$repository);
					$CategoryIdDataGS     = $moduleManagerGS->GetModuleCategoryID('data');
					$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdDataGS, 10);
					$SubCategory=IPS_GetChildrenIDs($categoryId_Gartensteuerung);
					foreach ($SubCategory as $SubCategoryId)
						{
						CreateLinkByDestination(IPS_GetName($SubCategoryId), $SubCategoryId,    $AutosteuerungID,  10);
						}					
					$categoryId_Register    		= CreateCategory('Gartensteuerung-Register',   $CategoryIdDataGS, 200);
					$SubCategory=IPS_GetChildrenIDs($categoryId_Register);
					foreach ($SubCategory as $SubCategoryId)
						{
						CreateLinkByDestination(IPS_GetName($SubCategoryId), $SubCategoryId,    $AutosteuerungID,  10);
						}					
                    if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                        {                
    					$object2= new ipsobject($CategoryIdDataGS);
	    				$object3= new ipsobject($object2->osearch("Nachricht"));
		    			$NachrichtenInputID=$object3->osearch("Input");
		    			$webfront_links[$AutosteuerungID]["OID_R"]=$NachrichtenInputID;
                        }
					echo "****Modul Gartensteuerung konfiguriert und erkannt.\n";
					}
                break;		
			case "ALEXA":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Alexa'));            
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')				
				$alexa=new AutosteuerungAlexa();	
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
                    $webfront_links[$AutosteuerungID]["OID_R"]=$inputAlexa;											/* Darstellung rechts im Webfront */				
                    }
                break;
			case "PRIVATE":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Private'));             
                // OWNTAB nicht unterstützt
				//$StatusPrivateID=CreateVariable("StatusPrivate",1, $AutosteuerungID,0,"",null,null,"");
				break;
			case "SILENTMODE":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'SilentMode'));             
                // OWNTAB nicht unterstützt
				$PushSoundID=CreateVariable("PushSound",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdWebfrontControl,null,"");
				break;
			case "CONTROL":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'Control'));             
                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
			    	$webfront_links[$AutosteuerungID]["OID_R"]=$inputControl;											/* Darstellung rechts im Webfront */				
                    }
				break;
			case "MONITORMODE":
                $webfront_links[$AutosteuerungID]=array_merge($webfront_links[$AutosteuerungID],defineWebfrontLink($AutoSetSwitch,'MonitorMode'));             

                /* Setup Standard Variables */
                echo "   Variablen für MonitorStatus Steuerung in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";			
				$StatusMonitorID=CreateVariable("StatusMonitor",0, $AutosteuerungID,0,"AusEin-Boolean",null,null,"");
				$StatusMonitorZuletztID=CreateVariable("StatusMonitorZuletzt",0, $AutosteuerungID,0,"AusEin-Boolean",null,null,"");
				IPS_SetHidden($StatusMonitorZuletztID,true);
				$register->registerAutoEvent($StatusMonitorID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusMonitorID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusMonitorID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

				if ($countAlexa>0) 	$MonitorSchalterID=CreateVariable("SchalterMonitor",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdAlexaControl,null,"");	
				else $MonitorSchalterID=CreateVariable("SchalterMonitor",0, $AutosteuerungID,0,"AusEin-Boolean",$scriptIdWebfrontControl,null,"");			
				$register->registerAutoEvent($MonitorSchalterID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$MonitorSchalterID,true);
				AC_SetAggregationType($archiveHandlerID,$MonitorSchalterID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);				

                if (isset ($webfront_links[$AutosteuerungID]["TABNAME"]) )      /* eigener Tab, eigene Nachrichtenleiste */
                    {  				
			    	$webfront_links[$AutosteuerungID]["OID_R"]=$inputControl;											/* Darstellung rechts im Webfront */				
                    }
				break;
			default:
				break;
			}
		$register->registerAutoEvent($AutosteuerungID, $eventType, "par1", "par2");         // class AutosteuerungHandler
		$webfront_links[$AutosteuerungID]["NAME"]=$AutoSetSwitch["NAME"];
		$webfront_links[$AutosteuerungID]["ADMINISTRATOR"]=$AutoSetSwitch["ADMINISTRATOR"];
		$webfront_links[$AutosteuerungID]["USER"]=$AutoSetSwitch["USER"];
		$webfront_links[$AutosteuerungID]["MOBILE"]=$AutoSetSwitch["MOBILE"];
		echo "Register Webfront Events : ".$AutoSetSwitch["NAME"]." with ID : ".$AutosteuerungID."\n";
		}
	echo "-------------------------------------------------------\n";		
	//print_r($AutoSetSwitches);
    //print_r($webfront_links);


	/*
   $AutosteuerungID = CreateVariable("Ventilatorsteuerung", 1, $categoryId_Autosteuerung, 0, "AutosteuerungProfil",$scriptIdWebfrontControl,null,""  );  
	registerAutoEvent($AutosteuerungID, $eventType, "par1", "par2");

   $AnwesenheitssimulationID = CreateVariable("Anwesenheitssimulation", 1, $categoryId_Autosteuerung, 0, "AutosteuerungProfil",$scriptIdWebfrontControl,null,""  );  
	registerAutoEvent($AnwesenheitssimulationID, $eventType, "par1", "par2");
	*/
	
	/* Programme für Schalter registrieren nach OID des Events */
	/*
	 * war schon einmal ausgeklammert, wird aber intuitiv von der Install Routine erwartet dass auch die Events registriert werden
	 *
	 */
    echo "\n";
    echo "====================================================================\n";
    echo "\n";

	echo "\nProgramme für Schalter registrieren nach OID des Events.  Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";

	$AutoConfiguration = Autosteuerung_GetEventConfiguration();
	foreach ($AutoConfiguration as $variableId=>$params)
		{
        if (IPS_ObjectExists($variableId))
            {
            echo "   Create Event für ID : ".$variableId."   ".IPS_GetName($variableId)." \n";
            $register->CreateEvent($variableId, $params[0], $scriptIdAutosteuerung);
            }
        else echo "   Delete Event für ID : ".$variableId."  does no loger exists !!! \n";
		}



	/******************************************************
	 *
	 * Timer Konfiguration
	 *
	 * Wecker programmierung ist bei GutenMorgen Funktion
	 *
	 ***********************************************************************/
		
	$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $scriptIdAutosteuerung);
	if ($tim1ID==false)
		{
		$tim1ID = IPS_CreateEvent(1);
		IPS_SetParent($tim1ID, $scriptIdAutosteuerung);
		IPS_SetName($tim1ID, "Aufruftimer");
		IPS_SetEventCyclic($tim1ID,0,0,0,0,2,5);		/* alle 5 Minuten */
		//IPS_SetEventCyclicTimeFrom($tim1ID,1,40,0);  /* immer um 02:20 */
		}
	IPS_SetEventActive($tim1ID,true);
		
	$tim3ID = @IPS_GetEventIDByName("Anwesendtimer", $scriptIdAutosteuerung);
	if ($tim3ID==false)
		{
		$tim3ID = IPS_CreateEvent(1);
		IPS_SetParent($tim3ID, $scriptIdAutosteuerung);
		IPS_SetName($tim3ID, "Anwesendtimer");
		IPS_SetEventCyclic($tim3ID,0,0,0,0,1,60);		/* alle 60 Sekunden , kein Datumstyp, 0, 0 ,0 2 minütlich/ 1 sekündlich */
		}
	IPS_SetEventActive($tim3ID,true);

    /* für die Heizungsregelung zusaetzlich einen reset einbauen */

	$tim2ID = @IPS_GetEventIDByName("KalenderTimer", $scriptIdHeatControl);
	if ($tim2ID==false)
		{
		$tim2ID = IPS_CreateEvent(1);
		IPS_SetParent($tim2ID, $scriptIdHeatControl);
		IPS_SetName($tim2ID, "KalenderTimer");
		IPS_SetEventCyclicTimeFrom($tim2ID,0,0,10);  /* immer um 00:00:10 */
		}
	IPS_SetEventActive($tim2ID,true);




	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Installation
	 *
	 * es werden die Kategorien erstellt, es werden die tabs für das Webfront erstellt und auf die Kategorien verlinkt
	 * das ganze wird für den Administrator und den User gemacht.
	 *
	 * WFC10 Path ist der Administrator.Gartensteuerung
	 * das webfront für diese Kategorien ist immer admin
	 * es werden zusätzliche Tabs festgelegt, diese haben nur ein Icon.
	 * aus TabPaneItem.TabPaneItemTabItem wird eine passende Unterkategorie im Webfront generiert	 
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

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

	echo "\nWebfront Konfiguration für Administrator User usw, geordnet nach data.OID  \n";
	print_r($webfront_links);
	$tabs=array();
	foreach ($webfront_links as $OID => $webfront_link)
		{
		$tabs[$webfront_link["TAB"]]=$webfront_link["TAB"];
		}
	echo "\nWebfront Tabs anlegen:\n";
	print_r($tabs);	
			
	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben 
		 *
		 * typische Struktur, festgelegt im ini File:
		 *
		 * roottp/AutoTPA (Autosteuerung)/AutoTPADetails und /AutoTPADetails2
		 *
		 */
		
		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
        echo "Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";
		echo "Webportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in: ". $categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."\n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);
		
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

		/*************************************/

		/* Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen */
		echo "\nWebportal Administrator.Autosteuerung Datenstruktur installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
		EmptyCategory($categoryId_WebFrontAdministrator);
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontAdministrator, true); //Objekt verstecken

		/*************************************/
		
		/* TabPaneItem anlegen, etwas kompliziert geloest */
		$tabItem = $WFC10_TabPaneItem.$WFC10_TabItem;
		if ( exists_WFCItem($WFC10_ConfigId, $WFC10_TabPaneItem) )
		 	{
			echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  löscht TabItem : ".$WFC10_TabPaneItem."\n";
			DeleteWFCItems($WFC10_ConfigId, $WFC10_TabPaneItem);
			}
		else
			{
			echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  TabItem : ".$WFC10_TabPaneItem." nicht mehr vorhanden.\n";
			}	
		echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$WFC10_TabPaneItem." in ".$WFC10_TabPaneParent."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);

        /* Abgleich mit der neuen Webfront Tabelle, leider zwei Tabellen, die zu koordinieren sind. */
        $webFrontConfiguration = Autosteuerung_GetWebFrontConfiguration()["Administrator"];

		$i=0;
		foreach ($tabs as $tab)
			{
			//$categoryIdTab  = CreateCategory($tab,  $categoryId_WebFrontAdministrator, 100);
			//$categoryIdLeft  = CreateCategory($tabItem.$i.'_Left',  $categoryIdTab, 10);
			//$categoryIdRight = CreateCategory($tabItem.$i.'_Right', $categoryIdTab, 20);

			//CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem.$i,           $WFC10_TabPaneItem,    $WFC10_TabOrder+$i,     $tab, '', 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			//CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.$i.'_Left',   $tabItem.$i,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			//CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.$i.'_Right',  $tabItem.$i,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

			$i++;
																																								
			foreach ($webfront_links as $OID => $webfront_link)
				{
				if ($webfront_link["ADMINISTRATOR"]==true)
					{
					if ($webfront_link["TAB"]==$tab)
						{
                        echo "\n---------------------------------------\n";
						echo $tab." CreateLinkByDestination : ".$webfront_link["NAME"]."   ".$OID."   \n";
						$categoryIdTab  = CreateCategory($tab,  $categoryId_WebFrontAdministrator, 100);
                        if (isset($webFrontConfiguration[$tab])) 
                            {
                            echo "Webfront Config vorhanden.\n"; 
                            //print_r($webFrontConfiguration[$tab]); 
                            echo "\n";
                            $order=0;
                            foreach ($webFrontConfiguration[$tab] as $WFCItem) 
                                {
                                print_r($WFCItem); 
				                $order = $order + 10;
				                switch($WFCItem[0]) 
					                {
					                case IPSHEAT_WFCSPLITPANEL:
                                    	//$categoryIdPanel  = CreateCategory($WFCItem[1],  $categoryIdTab, 100);
						                break;
					                case IPSHEAT_WFCCATEGORY:
										if (strpos($WFCItem[1],'_Left')) $categoryIdLeft  = CreateCategory($WFCItem[1],  $categoryIdTab, 10);
			                            if (strpos($WFCItem[1],'_Right'))$categoryIdRight = CreateCategory($WFCItem[1], $categoryIdTab, 20);
                                        break;
                                    }
                                }
                            }
						CreateLinkByDestination($webfront_link["NAME"], $OID,    $categoryIdLeft,  10);
						if ( isset( $webfront_link["OID_R"]) == true )
							{
							CreateLinkByDestination("Nachrichtenverlauf", $webfront_link["OID_R"],    $categoryIdRight,  20);
							}
						}
					}
				}
			echo ">>Kategorien erstellt fuer ".$tab.", Main: ".$categoryIdTab." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n\n";
			}
		}

	/*******************************************************
	 *
	 * es gibt keine automatisierte Webfront Erstellung mehr die anhand der Kategorien, die gerade angelegt worden sind erstellt wird 
	 *
	 * Es werden Kategorien AutoTPADetailsX mit laufender Nummer angelegt, beginnend bei 0, nicht aendern und in Config uebernehmen
	 *
	 *		'Alexa' => array(
	 *			array(IPSHEAT_WFCSPLITPANEL, 'AutoTPADetails3',        'AutoTPA',        'Alexa','Eyes',1,40,0,0,'true'),
	 *			array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails3_Left',  'AutoTPADetails3', null,null),
	 *			array(IPSHEAT_WFCCATEGORY,       'AutoTPADetails3_Right',  'AutoTPADetails3', null,null),
	 *			),
	 *
	 *****************************************************************************/

if (true) {

	$webFrontConfiguration = Autosteuerung_GetWebFrontConfiguration();
	if ($WFC10_Enabled) 
		{
		if ( isset($webFrontConfiguration["Administrator"]) == true )
			{	/* neue Art der Konfiguration */
			$webFrontConfig=$webFrontConfiguration["Administrator"];
			echo "Neue Webfront Konfiguration mit Unterscheidung Administrator, User und Mobile.\n";
			}
		else
			{	/* alte Art der Konfiguration */
			echo "Alte Webfront Konfiguration nur für Administrator.\n";
			$webFrontConfig=$webFrontConfiguration;
			}
		/* Default Path ist Visualization.WebFront.Administrator.Autosteuerung, die folgenden beiden Befehle wurden weiter oben bereits durchgeführt */
		//$categoryId_WebFrontAdministartor                = CreateCategoryPath($WFC10_Path);
		//CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem,  $WFC10_TabPaneParent, $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);

		$order = 10;
		foreach($webFrontConfig as $tabName=>$tabData) 
			{
			/* tabname muss einer der oben kreierten Tabs sein, sonst Fehler */
			if (isset($tabs[$tabName])===false) { echo "\nFalsche Konfiguration in Autosteuerung. Tabname ".$tabName." stimmt nicht mit WebConfiguration ueberein.\n"; break; } 
			$tabCategoryId	= CreateCategory($tabName, $categoryId_WebFrontAdministrator, $order);			
			foreach($tabData as $WFCItem) {
				$order = $order + 10;
				switch($WFCItem[0]) 
					{
					case IPSHEAT_WFCSPLITPANEL:
						CreateWFCItemSplitPane ($WFC10_ConfigId, $WFCItem[1], $WFCItem[2]/*Parent*/,$order,$WFCItem[3],$WFCItem[4],(int)$WFCItem[5],(int)$WFCItem[6],(int)$WFCItem[7],(int)$WFCItem[8],$WFCItem[9]);
						break;
					case IPSHEAT_WFCCATEGORY:
						$categoryId	= CreateCategory($WFCItem[1], $tabCategoryId, $order);
						CreateWFCItemCategory ($WFC10_ConfigId, $WFCItem[1], $WFCItem[2]/*Parent*/,$order, $WFCItem[3]/*Name*/,$WFCItem[4]/*Icon*/, $categoryId, 'false');
						break;
					case IPSHEAT_WFCGROUP:
					case IPSHEAT_WFCLINKS:
						echo "  WFCLINKS : ".$WFCItem[2]."   ".$WFCItem[3]."\n";
						$categoryId = IPS_GetCategoryIDByName($WFCItem[2], $tabCategoryId);
						if ($WFCItem[0]==IPSHEAT_WFCGROUP) {
							$categoryId = CreateDummyInstance ($WFCItem[1], $categoryId, $order);
						}
						$links      = explode(',', $WFCItem[3]);
						$names      = $links;
						if (array_key_exists(4, $WFCItem)) {
							$names = explode(',', $WFCItem[4]);
						}
						foreach ($links as $idx=>$link) {
							$order = $order + 1;
							// CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="")
							CreateLinkByDestination($names[$idx], getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $categoryId, $order);
						}
						break;
					default:
						trigger_error('Unknown WFCItem='.$WFCItem[0]);
			   	    }
				}
			}
		}
		




	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront User Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	if ($WFC10User_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen 
		 *
		 * typische Struktur, festgelegt im ini File:
		 *
		 * roottp/AutoTPU (Autosteuerung)/AutoTPUDetails
		 *
		 */

		$categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
		echo "====================================================================================\n";
		echo "Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";
        echo "Webportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
		CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);

		/*************************************/

		/* Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen */
		echo "\nWebportal User.Autosteuerung Datenstruktur installieren in: ".$WFC10User_Path." \n";
		$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
		EmptyCategory($categoryId_WebFrontUser);
		echo "Kategorien erstellt, Main: ".$categoryId_WebFrontUser."\n";
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontUser, true); //Objekt verstecken		

		/*************************************/
		
		$tabItem = $WFC10User_TabPaneItem.$WFC10User_TabItem;
		if ( exists_WFCItem($WFC10User_ConfigId, $tabItem) )
		 	{
			echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10User_ConfigId).")  löscht TabItem : ".$tabItem."\n";
			DeleteWFCItems($WFC10User_ConfigId, $tabItem);
			}
		else
			{
			echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10User_ConfigId).")  TabItem : ".$tabItem." nicht mehr vorhanden.\n";
			}	
		echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$WFC10User_TabPaneItem." in ".$WFC10User_TabPaneParent."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
		
		/* wenn nur ein Tab benötigt wird, ohne Teilung */
		CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem,   $WFC10User_TabPaneItem,    $WFC10User_TabOrder,     $WFC10User_TabName,     $WFC10User_TabIcon, $categoryId_WebFrontUser /*BaseId*/, 'false' /*BarBottomVisible*/);

		if (false)
			{
			CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem,               $WFC10User_TabPaneItem,    $WFC10User_TabOrder,     $WFC10User_TabName,     $WFC10User_TabIcon);
			$categoryId_WebFrontTab = $categoryId_WebFrontUser;
			CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);
			}

		foreach ($webfront_links as $OID => $webfront_link)
			{
			if ($webfront_link["USER"]==true)
				{
				echo "User CreateLinkByDestination : ".$webfront_link["NAME"]."   ".$OID."   ".$categoryId_WebFrontUser."\n";
				CreateLinkByDestination($webfront_link["NAME"], $OID,    $categoryId_WebFrontUser,  10);
				}
			}
		}

	if ($Mobile_Enabled)
		{
		$categoryId_MobileWebFront=CreateCategoryPath("Visualization.Mobile");
		echo "====================================================================================\n";		
		echo "Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";
		echo "Webportal Mobile Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in der Kategorie ". $categoryId_MobileWebFront." (".IPS_GetName($categoryId_MobileWebFront).")\n";
		echo "Webportal Mobile.Autosteuerung Datenstruktur installieren in: ".$Mobile_Path." \n";
		$categoryId_WebFrontMobile         = CreateCategoryPath($Mobile_Path);
		EmptyCategory($categoryId_WebFrontMobile);
		$i=0;
		foreach ($tabs as $tab)
			{
			$categoryIdTab  = CreateCategory($tab,  $categoryId_WebFrontMobile, 100);
			$i++;
			foreach ($webfront_links as $OID => $webfront_link)
				{
				if ( ($webfront_link["MOBILE"]==true) && ($webfront_link["TAB"]==$tab) )
					{
					echo $tab." CreateLinkByDestination : ".$webfront_link["NAME"]."   ".$OID."   ".$categoryIdTab."\n";
					CreateLinkByDestination($webfront_link["NAME"], $OID,    $categoryIdTab,  10);
					if ( isset( $webfront_link["OID_R"]) == true )
						{
						echo "Link aufbauen von ".$webfront_link["OID_R"]." in ".$categoryIdTab." Name Quelle: ".IPS_GetName($webfront_link["OID_R"])."\n";
						if (IPS_GetName($webfront_link["OID_R"])=="Wochenplan")
							{
							$nachrichteninput_Id=@IPS_GetObjectIDByName("Wochenplan-Stromheizung",$CategoryIdData);
							for ($i=1;$i<17;$i++)
								{
								$zeile = IPS_GetVariableIDbyName("Zeile".$i,$nachrichteninput_Id);
								CreateLinkByDestination(date("D d",time()+(($i-1)*24*60*60)), $zeile, $categoryIdTab,  $i*10);
								}
							}
						else CreateLinkByDestination("Nachrichtenverlauf", $webfront_link["OID_R"],    $categoryIdTab,  20);
						}
					}
				}
			echo ">>Kategorien fertig gestellt fuer ".$tab." (".$categoryIdTab.") \n\n";
			}	
		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_RetroWebFront         = CreateCategoryPath($Retro_Path);
		}

	ReloadAllWebFronts(); /* es wurde das Autosteuerung Webfront komplett geloescht und neu aufgebaut, reload erforderlich */


	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Installation für IPS Light wenn User konfiguriert
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	echo "\n======================================================\n";
	echo "Webportal User für IPS Light installieren:  Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.";
	echo "\n======================================================\n\n";
	
	$moduleManagerLight = new IPSModuleManager('IPSLight');

	$moduleManagerLight->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManagerLight->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.2');
	$moduleManagerLight->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');
	$moduleManagerLight->VersionHandler()->CheckModuleVersion('IPSComponent','2.50.1');
	$moduleManagerLight->VersionHandler()->CheckModuleVersion('IPSMessageHandler','2.50.1');

	IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSLight.inc.php",                "IPSLibrary::app::modules::IPSLight");
	IPSUtils_Include ("IPSLight_Constants.inc.php",      "IPSLibrary::app::modules::IPSLight");
	IPSUtils_Include ("IPSLight_Configuration.inc.php",  "IPSLibrary::config::modules::IPSLight");

	$CategoryIdData     = $moduleManagerLight->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManagerLight->GetModuleCategoryID('app');

	$categoryIdSwitches = CreateCategory('Switches', $CategoryIdData, 10);
	$categoryIdGroups   = CreateCategory('Groups',   $CategoryIdData, 20);
	$categoryIdPrograms = CreateCategory('Programs', $CategoryIdData, 30);
	
	echo " Category IDs:\n";
	echo "    Data         :".$CategoryIdData."\n";
	echo "    App          :".$CategoryIdApp."\n";
	echo "    Switches     :".$categoryIdSwitches."\n";	
	echo "    Groups       :".$categoryIdGroups."\n";		
	echo "    Programs     :".$categoryIdPrograms."\n\n";	

	echo " Webfront Configurations:\n";		
	$WFC10User_Enabled    		= $moduleManagerLight->GetConfigValueDef('Enabled', 'WFC10User',false);
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_ConfigId       	= $WebfrontConfigID["User"];		
		$WFC10User_Path    	 		= $moduleManagerLight->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    	= $moduleManagerLight->GetConfigValue('TabPaneItem', 'WFC10User');
		$WFC10User_TabPaneParent  	= $moduleManagerLight->GetConfigValue('TabPaneParent', 'WFC10User');
		$WFC10User_TabPaneName    	= $moduleManagerLight->GetConfigValue('TabPaneName', 'WFC10User');
		$WFC10User_TabPaneIcon    	= $moduleManagerLight->GetConfigValue('TabPaneIcon', 'WFC10User');
		$WFC10User_TabPaneOrder   	= $moduleManagerLight->GetConfigValueInt('TabPaneOrder', 'WFC10User');	
		echo "WF10 User \n";
		echo "  Path          : ".$WFC10User_Path."\n";
		echo "  ConfigID      : ".$WFC10User_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10User_ConfigId)).".".IPS_GetName($WFC10User_ConfigId).")\n";
		echo "  TabPaneItem   : ".$WFC10User_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10User_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10User_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10User_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10User_TabPaneOrder."\n";
	
		}	


	if ($WFC10User_Enabled) {
		$categoryId_WebFrontUser                = CreateCategoryPath($WFC10User_Path);
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontUser, true); //Objekt verstecken	
		EmptyCategory($categoryId_WebFrontUser);
		echo "================= ende empty categories \ndelete ".$WFC10User_TabPaneItem."\n";	
		DeleteWFCItems($WFC10User_ConfigId, $WFC10User_TabPaneItem);
		echo "================= ende delete ".$WFC10User_TabPaneItem."\n";			
		echo " CreateWFCItemTabPane : ".$WFC10User_ConfigId. " ".$WFC10User_TabPaneItem. " ".$WFC10User_TabPaneParent. " ".$WFC10User_TabPaneOrder. " ".$WFC10User_TabPaneName. " ".$WFC10User_TabPaneIcon."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem,  $WFC10User_TabPaneParent, $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
		echo "================ende create Tabitem \n";
		$webFrontConfig = IPSLight_GetWebFrontUserConfiguration();
		$order = 10;
		foreach($webFrontConfig as $tabName=>$tabData) {
			echo "================create ".$tabName."\n";
			$tabCategoryId	= CreateCategory($tabName, $categoryId_WebFrontUser, $order);
			foreach($tabData as $WFCItem) {
				$order = $order + 10;
				switch($WFCItem[0]) {
					case IPSLIGHT_WFCSPLITPANEL:
						CreateWFCItemSplitPane ($WFC10User_ConfigId, $WFCItem[1], $WFCItem[2]/*Parent*/,$order,$WFCItem[3],$WFCItem[4],(int)$WFCItem[5],(int)$WFCItem[6],(int)$WFCItem[7],(int)$WFCItem[8],$WFCItem[9]);
						break;
					case IPSLIGHT_WFCCATEGORY:
						$categoryId	= CreateCategory($WFCItem[1], $tabCategoryId, $order);
						CreateWFCItemCategory ($WFC10User_ConfigId, $WFCItem[1], $WFCItem[2]/*Parent*/,$order, $WFCItem[3]/*Name*/,$WFCItem[4]/*Icon*/, $categoryId, 'false');
						break;
					case IPSLIGHT_WFCGROUP:
					case IPSLIGHT_WFCLINKS:
						$categoryId = IPS_GetCategoryIDByName($WFCItem[2], $tabCategoryId);
						if ($WFCItem[0]==IPSLIGHT_WFCGROUP) {
							$categoryId = CreateDummyInstance ($WFCItem[1], $categoryId, $order);
						}
						$links      = explode(',', $WFCItem[3]);
						$names      = $links;
						if (array_key_exists(4, $WFCItem)) {
							$names = explode(',', $WFCItem[4]);
						}
						foreach ($links as $idx=>$link) {
							$order = $order + 1;
							CreateLinkByDestination($names[$idx], getVariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $categoryId, $order);
						}
						break;
					default:
						trigger_error('Unknown WFCItem='.$WFCItem[0]);
			   }
			}
		}
	}


	ReloadAllWebFronts(); /* es wurde das Autosteuerung Webfront komplett geloescht und neu aufgebaut, reload erforderlich */
		
	echo "================= ende webfront installation. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.\n";



/***************************************************************************************/

}

	
?>