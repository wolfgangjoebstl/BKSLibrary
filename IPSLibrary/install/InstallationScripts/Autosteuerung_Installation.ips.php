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

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Autosteuerung\Autosteuerung_Configuration.inc.php");
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

	echo "\nIP Symcon Kernelversion    : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPS ModulManager Version   : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Stromheizung');
	echo "\nModul Autosteuerung Version : ".$ergebnis."   Status : ".$moduleManager->VersionHandler()->GetModuleState()."\n";
	
 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n";
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId fuer IPS_light, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
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
 * Webfront Konfiguration einlesen
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

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$scriptIdWebfrontControl   = IPS_GetScriptIDByName('WebfrontControl', $CategoryIdApp);
	$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);
	$scriptIdHeatControl   = IPS_GetScriptIDByName('Autosteuerung_HeatControl', $CategoryIdApp);

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

	$eventType='OnChange';

	$categoryId_Autosteuerung  = CreateCategory("Ansteuerung", $CategoryIdData, 10);

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf-Autosteuerung',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	/* Nachrichtenzeilen werden automatisch von der Logging Klasse gebildet */

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
 *
 ********************************/

	$AutoSetSwitches = Autosteuerung_SetSwitches();
	$register=new AutosteuerungHandler($scriptIdAutosteuerung);
	$webfront_links=array();
	foreach ($AutoSetSwitches as $AutoSetSwitch)
		{
		// CreateVariable($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
		$AutosteuerungID = CreateVariable($AutoSetSwitch["NAME"], 1, $categoryId_Autosteuerung, 0, $AutoSetSwitch["PROFIL"],$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
		echo "-------------------------------------------------------\n";
		echo "Bearbeite Autosetswitch : ".$AutoSetSwitch["NAME"]."\n";
		$webfront_links[$AutosteuerungID]["TAB"]="Autosteuerung";
		$webfront_links[$AutosteuerungID]["OID_L"]=$AutosteuerungID;
		
		switch (strtoupper($AutoSetSwitch["NAME"]))
			{
			case "ANWESENHEITSERKENNUNG":
				echo "   Variablen für Anwesenheitserkennung in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";			
				$StatusAnwesendID=CreateVariable("StatusAnwesend",0, $AutosteuerungID,0,"~Presence");
				$StatusAnwesendZuletztID=CreateVariable("StatusAnwesendZuletzt",0, $AutosteuerungID,0,"~Presence");
				IPS_SetHidden($StatusAnwesendZuletztID,true);
				$register->registerAutoEvent($StatusAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				
				$StatusSchalterAnwesendID=CreateVariable("SchalterAnwesend",0, $AutosteuerungID,0,"AusEin-Boolean");				
				$register->registerAutoEvent($StatusSchalterAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusSchalterAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusSchalterAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				/* wird als Unterelement automatisch gelinked */
				//$webfront_links[$StatusAnwesendID]["NAME"]="StatusAnwesend";
				//$webfront_links[$StatusAnwesendID]["ADMINISTRATOR"]=$AutoSetSwitch["ADMINISTRATOR"];
				//$webfront_links[$StatusAnwesendID]["USER"]=$AutoSetSwitch["USER"];
				//$webfront_links[$StatusAnwesendID]["MOBILE"]=$AutoSetSwitch["MOBILE"];	
				$webfront_links[$AutosteuerungID]["OID_R"]=$input;				
				break;
			case "ALARMANLAGE":
				echo "   Variablen für Alarmanlage in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";
				$StatusAnwesendID=CreateVariable("StatusAlarmanlage",0, $AutosteuerungID,0,"~Presence");
				$StatusAnwesendZuletztID=CreateVariable("StatusAlarmanlageZuletzt",0, $AutosteuerungID,0,"~Presence");
				IPS_SetHidden($StatusAnwesendZuletztID,true);
				$register->registerAutoEvent($StatusAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				
				$StatusSchalterAnwesendID=CreateVariable("SchalterAlarmanlage",0, $AutosteuerungID,0,"AusEin-Boolean");				
				$register->registerAutoEvent($StatusSchalterAnwesendID, $eventType, "", "");
				AC_SetLoggingStatus($archiveHandlerID,$StatusSchalterAnwesendID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusSchalterAnwesendID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);
				break;										
			case "GUTENMORGENWECKER":
				echo "   Variablen für GutenMorgenWecker in ".$AutosteuerungID."  ".IPS_GetName($AutosteuerungID)."\n";		
	   			// CreateVariable($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')					
				$WeckerID = CreateVariable("Wecker", 1, $AutosteuerungID, 0, "SchlafenAufwachenMunter",null,"",""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
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
				$webfront_links[$AutosteuerungID]["TAB"]="Anwesenheit";
				if ( isset( $AutoSetSwitch["OWNTAB"] ) == true )
					{
					$webfront_links[$AutosteuerungID]["TAB"]=$AutoSetSwitch["OWNTAB"];
					if ( isset( $AutoSetSwitch["TABNAME"] ) == true )
						{
						$webfront_links[$AutosteuerungID]["TABNAME"]=$AutoSetSwitch["TABNAME"];
						}
					else $webfront_links[$AutosteuerungID]["TABNAME"]='Schaltbefehle'; 						
					}
				$categoryId_Schaltbefehle = CreateCategory('Schaltbefehle-Anwesenheitssimulation',   $CategoryIdData, 20);
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')				
				$vid=CreateVariable("Schaltbefehle",3,$categoryId_Schaltbefehle, 0,'',null,'');
				$simulation=new AutosteuerungAnwesenheitssimulation();
				$simulation->InitMesagePuffer();								
				$webfront_links[$AutosteuerungID]["OID_R"]=$vid;									
				break;	
			case "STROMHEIZUNG":
				$webfront_links[$AutosteuerungID]["TAB"]="Autosteuerung";
				if ( isset( $AutoSetSwitch["OWNTAB"] ) == true )
					{
					$webfront_links[$AutosteuerungID]["TAB"]=$AutoSetSwitch["OWNTAB"];
					if ( isset( $AutoSetSwitch["TABNAME"] ) == true )
						{
						$webfront_links[$AutosteuerungID]["TABNAME"]=$AutoSetSwitch["TABNAME"];
						}
					else $webfront_links[$AutosteuerungID]["TABNAME"]='Wochenplan'; 						
					}
				$categoryId_Wochenplan = CreateCategory('Wochenplan-Stromheizung',   $CategoryIdData, 20);
				// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')				
				$vid=CreateVariable("Wochenplan",3,$categoryId_Wochenplan, 0,'',null,'');				
				$webfront_links[$AutosteuerungID]["OID_R"]=$vid;
				$kalender=new AutosteuerungStromheizung();
				$kalender->SetupKalender();	/* Kalender neu aufsetzen, alle Werte werden geloescht, immer bei Neuinstallation */									
				break;	
			default:
				break;
			}
		$register->registerAutoEvent($AutosteuerungID, $eventType, "par1", "par2");
		$webfront_links[$AutosteuerungID]["NAME"]=$AutoSetSwitch["NAME"];
		$webfront_links[$AutosteuerungID]["ADMINISTRATOR"]=$AutoSetSwitch["ADMINISTRATOR"];
		$webfront_links[$AutosteuerungID]["USER"]=$AutoSetSwitch["USER"];
		$webfront_links[$AutosteuerungID]["MOBILE"]=$AutoSetSwitch["MOBILE"];
		echo "Register Webfront Events : ".$AutoSetSwitch["NAME"]." with ID : ".$AutosteuerungID."\n";
		}
	echo "-------------------------------------------------------\n";		
	//print_r($AutoSetSwitches);

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
	echo "\nProgramme für Schalter registrieren nach OID des Events.\n";

	$AutoConfiguration = Autosteuerung_GetEventConfiguration();
	foreach ($AutoConfiguration as $variableId=>$params)
		{
		echo "Create Event für ID : ".$variableId."   ".IPS_GetName($variableId)." \n";
		$register->CreateEvent($variableId, $params[0], $scriptIdAutosteuerung);
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
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	echo "\nWebfront Konfiguration für Administraor User usw, geordnet nach data.OID  \n";
	print_r($webfront_links);
	
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
		echo "\nWebportal Administrator Kategorie im Webfront Konfigurator ID ".$WFC10_ConfigId." installieren in: ". $categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."\n";
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
		if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
		 	{
			echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  löscht TabItem : ".$tabItem."\n";
			DeleteWFCItems($WFC10_ConfigId, $tabItem);
			}
		else
			{
			echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  TabItem : ".$tabItem." nicht mehr vorhanden.\n";
			}	
		echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$WFC10_TabPaneItem." in ".$WFC10_TabPaneParent."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);

		$tabs=array();
		foreach ($webfront_links as $OID => $webfront_link)
			{
			$tabs[$webfront_link["TAB"]]=$webfront_link["TAB"];
			}
		echo "\nWebfront Tabs anlegen:\n";
		print_r($tabs);	
		$i=0;
		foreach ($tabs as $tab)
			{
			$categoryIdTab  = CreateCategory($tab,  $categoryId_WebFrontAdministrator, 100);
			$categoryIdLeft  = CreateCategory('Left',  $categoryIdTab, 10);
			$categoryIdRight = CreateCategory('Right', $categoryIdTab, 20);

			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem.$i,           $WFC10_TabPaneItem,    $WFC10_TabOrder+$i,     $tab, '', 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.$i.'_Left',   $tabItem.$i,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.$i.'_Right',  $tabItem.$i,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

			echo "Kategorien erstellt, Main: ".$categoryIdTab." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n";
			$i++;
																																								
			foreach ($webfront_links as $OID => $webfront_link)
				{
				if ($webfront_link["ADMINISTRATOR"]==true)
					{
					if ($webfront_link["TAB"]==$tab)
						{
						echo $tab." CreateLinkByDestination : ".$webfront_link["NAME"]."   ".$OID."   ".$categoryIdLeft."\n";
						CreateLinkByDestination($webfront_link["NAME"], $OID,    $categoryIdLeft,  10);
						if ( isset( $webfront_link["OID_R"]) == true )
							{
							CreateLinkByDestination("Nachrichtenverlauf", $webfront_link["OID_R"],    $categoryIdRight,  20);
							}
						}
					}
				}

			}


		ReloadAllWebFronts();

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
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
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
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_MobileWebFront         = CreateCategoryPath($Mobile_Path);
		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_RetroWebFront         = CreateCategoryPath($Retro_Path);
		}


	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Installation für IPS Light wenn User konfiguriert
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	echo "\n======================================================\n";
	echo "Webportal User für IPS Light installieren: ";
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
							CreateLinkByDestination($names[$idx], get_VariableId($link,$categoryIdSwitches,$categoryIdGroups,$categoryIdPrograms), $categoryId, $order);
						}
						break;
					default:
						trigger_error('Unknown WFCItem='.$WFCItem[0]);
			   }
			}
		}
	}

	echo "================= ende webfront installation \n";



/***************************************************************************************/

	// ----------------------------------------------------------------------------------------------------------------------------
	function get_VariableId($name, $switchCategoryId, $groupCategoryId, $categoryIdPrograms) 
		{
		$childrenIds = IPS_GetChildrenIDs($switchCategoryId);
		foreach ($childrenIds as $childId) 
			{
			if (IPS_GetName($childId)==$name) 
				{
				return $childId;
				}
			}
		$childrenIds = IPS_GetChildrenIDs($groupCategoryId);
		foreach ($childrenIds as $childId) {
			if (IPS_GetName($childId)==$name) {
				return $childId;
			}
		}
		$childrenIds = IPS_GetChildrenIDs($categoryIdPrograms);
		foreach ($childrenIds as $childId) {
			if (IPS_GetName($childId)==$name) {
				return $childId;
			}
		}
		trigger_error("$name could NOT be found in 'Switches' and 'Groups'");
		}
	
?>