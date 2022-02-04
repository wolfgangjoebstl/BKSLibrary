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
 *************************************************************/

$cutter=true;


	/******************** Defaultprogrammteil ********************/
	 
	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

	IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');	
	IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
		}

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

    $ipsOps = new ipsOps();
    $dosOps = new dosOps();
	$wfcHandling = new WfcHandling();		

	/***********************
	 *
	 * Webfront Konfiguration auslesen
	 * 
	 **************************/

    //$wfcHandling->read_wfc();

	/***********************
	 *
	 * Webfront GUID herausfinden und Konfiguratoren anlegen
	 * 
	 **************************/
	
	//echo "\n\n";
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
		if (false)
			{
			$config=json_decode(IPS_GetConfiguration($instanz));
			$configItems = json_decode(json_decode(IPS_GetConfiguration($instanz))->Items);
			print_r($configItems);	
			}	
		}
	//print_r($WebfrontConfigID);
	
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

	echo "\nWebuser activated : ";
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];			
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10');
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10');
		$WFC10_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10');
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10');
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10');
		$WFC10_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10');
		$WFC10_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10');
		$WFC10_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10');
		$WFC10_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10');
		echo "WF10 Administrator\n";
		echo "  Path          : ".$WFC10_Path."\n";
		echo "  ConfigID      : ".$WFC10_ConfigId."\n";
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

	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	if ($WFC10User_Enabled)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];	
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10User');
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10User');
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10User');
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10User');
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

	echo "\n";
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
	$scriptIdAmis   = IPS_GetScriptIDByName('Amis', $CategoryIdApp);

	/***********************
	 *
	 *					Profile Definition
	 * 
	 **************************/	

    echo "Profile Definition für AMIS Modul:\n";
	$pname="AusEin-Boolean";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
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
 		IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
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
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
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
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
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
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
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
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
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
	
	$Amis = new Amis();

	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];

	//$MeterConfig = get_MeterConfiguration();
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
	 *  umgesetzt auf [AMIS,Homematic, HomematicIP etc] 
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
		
		/***********************Homematic Zähler */
		
		if (strtoupper($meter["TYPE"])=="HOMEMATIC")
			{
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
	      
			SetValue($MeterReadID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */
			
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$variableID]["NAME"]="Wirkenergie";
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$LeistungID]["NAME"]="Wirkleistung";			
			}

		/*********************** Irgendein Register Zähler, wahrscheinlich von Remote Access uebermittelt */

		if (strtoupper($meter["TYPE"])=="REGISTER")
			{
			/* Variable ID selbst bestimmen */
			$variableID = CreateVariableByName($ID, 'Wirkenergie', 2,'~Electricity');   /* 0 Boolean 1 Integer 2 Float 3 String */
			//IPS_SetVariableCustomProfile($variableID,'~Electricity');
			AC_SetLoggingStatus($archiveHandlerID,$variableID,true);
			AC_SetAggregationType($archiveHandlerID,$variableID,1);      /* Zählerwert */
			IPS_ApplyChanges($archiveHandlerID);
			
			$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2,'~Power');   /* 0 Boolean 1 Integer 2 Float 3 String */
			//IPS_SetVariableCustomProfile($LeistungID,'~Power');
			AC_SetLoggingStatus($archiveHandlerID,$LeistungID,true);
			AC_SetAggregationType($archiveHandlerID,$LeistungID,0);
			IPS_ApplyChanges($archiveHandlerID);
			
			//$HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
			//IPS_SetVariableCustomProfile($HM_EnergieID,'kWh');
	      
			SetValue($MeterReadID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */
			
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$variableID]["NAME"]="Wirkenergie";
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$LeistungID]["NAME"]="Wirkleistung";			
			}	

		/*********************** aus mehreren Werten eine Berechnung anstellen */
			
		if (strtoupper($meter["TYPE"])=="SUMME")
			{
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
			
			//$HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
			//IPS_SetVariableCustomProfile($HM_EnergieID,'kWh');
	      
			SetValue($MeterReadID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */
			
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$variableID]["NAME"]="Wirkenergie";
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$LeistungID]["NAME"]="Wirkleistung";			
			}				
			
		/************************** und ein AMIS Zähler mit dem auslesen über die serielle Schnittstelle */	
											
		if (strtoupper($meter["TYPE"])=="AMIS")
			{
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

			// Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
			$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
			$variableID = CreateVariableByName($zaehlerid,'Wirkenergie', 2,'~Electricity');
			//IPS_SetVariableCustomProfile($variableID,'~Electricity');			

			SetValue($AmisReadMeterID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */
			if ( isset($meter["STATUS"]) )
				{
				if (strtoupper($meter["STATUS"]) != "ACTIVE" ) SetValue($AmisReadMeterID,false);
				}			
						
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$wirkenergie1_ID]["NAME"]="Wirkenergie";
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$aktuelleLeistungID]["NAME"]="Wirkleistung";
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$zaehlerid]["NAME"]="Zaehlervariablen";						
			}
		print_r($meter);

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
	
	/* html basierte Tabellen ebenfalls anzeigen, Name Zaehlervariablen als Identifier für rechtes Tab */
	$ID = CreateVariableByName($CategoryIdData, "Zusammenfassung", 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
	IPS_SetPosition($ID,9999);
	$tableID = CreateVariableByName($ID, "Historie-Energie", 3);
	IPS_SetVariableCustomProfile($tableID,'~HTMLBox');			
	$webfront_links["Zusammenfassung"]["Energievorschub der letzten Tage"][$tableID]["NAME"]="Zaehlervariablen";
		
	$regID = CreateVariableByName($ID, "Aktuelle-Energie", 3);
	IPS_SetVariableCustomProfile($regID,'~HTMLBox');			
	$webfront_links["Zusammenfassung"]["Energieregister"][$regID]["NAME"]="Zaehlervariablen";	
	
	$Meter=$Amis->writeEnergyRegistertoArray($MeterConfig);
	SetValue($tableID,$Amis->writeEnergyRegisterTabletoString($Meter));
	SetValue($regID,$Amis->writeEnergyRegisterValuestoString($Meter));
	
	/******************* Timer Definition ******************************
     *
     *   Momentanwerrte Abfragen alle 60 Sekunden machen
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
    
	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------

	echo "Entsprechend den Webfront Links wird das Webfront automatisch aufgebaut:\n";
	echo "  Tab Energiemessung\n";
	foreach ($webfront_links as $Name => $webfront_group)
	   	{
		echo "    Subtab:    ".$Name."\n";
		foreach ($webfront_group as $Group => $RegisterEntries)
			{
			echo "      Gruppe:  ".$Group."\n";
			foreach ($RegisterEntries as $OID => $Entries)
				{
				echo "        Register:  ".$OID."/".$Entries["NAME"]."\n";
				}
			}	
		}
	//print_r($webfront_links);
		
	if ($WFC10_Enabled)
		{
		/* Kategorien für Administrator werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */

		$categoryId_WebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   0, IPS_GetName(0).'-Admin', '', $categoryId_WebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */

		echo "\nWebportal LocalAccess TabPane installieren in: ".$WFC10_Path." \n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$WFC10_ConfigId." ".$WFC10_TabPaneItem." ".$WFC10_TabPaneParent." ".$WFC10_TabPaneOrder." ".$WFC10_TabPaneName." ".$WFC10_TabPaneIcon."\n";
		CreateWFCItemTabPane   ($WFC10_ConfigId, "HouseTPA", $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, "", "HouseRemote");  /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, "HouseTPA", 30, $WFC10_TabPaneName, $WFC10_TabPaneIcon);    /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
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

			$tabItem = $WFC10_TabPaneItem.$Name;
			if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
			 	{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($WFC10_ConfigId, $tabItem);
				}
			else
				{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}				
			IPS_ApplyChanges($WFC10_ConfigId);
			echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";
			//CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,    0,     $Name,     "", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

			CreateLinkByDestination("Read Meter", $MeterReadID,    $categoryIdLeft,  0);
			foreach ($webfront_group as $Group => $webfront_link)
				{
				//if left
				//$categoryIdGroup  = CreateCategory($Group,  $categoryIdLeft, 10);
				$categoryIdGroup  = CreateVariableByName($categoryIdLeft, $Group, 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
				EmptyCategory($categoryIdGroup);				
				foreach ($webfront_link as $OID => $link)
					{
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ( $link["NAME"]=="Zaehlervariablen" )
						{
						echo "erzeuge Link mit Name ".$Group."-".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdRight."\n";
						CreateLinkByDestination($Group."-".$link["NAME"], $OID,    $categoryIdRight,  20);
						echo "\n";
						}
					else
						{
			 			echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft." / ".$categoryIdGroup."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryIdGroup,  20);
						echo "\n";
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
		echo "====================================================================================\n";
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
		CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);
		
		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */
		echo "\nWebportal LocalAccess TabPane installieren in: ".$WFC10User_Path." \n";
		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit Parameter : ".$WFC10User_ConfigId." ".$WFC10User_TabPaneItem." ".$WFC10User_TabPaneParent." ".$WFC10User_TabPaneOrder." ".$WFC10User_TabPaneIcon."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, "HouseTPU", $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, "", "HouseRemote");     /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, "HouseTPU",  20, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);      /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

		/*************************************/

		$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
		IPS_SetHidden($categoryId_WebFrontUser,true);
		
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontUser, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			$tabItem = $WFC10User_TabPaneItem.$Name;
			if ( exists_WFCItem($WFC10User_ConfigId, $tabItem) )
			 	{
				echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($WFC10User_ConfigId, $tabItem);
				}
			else
				{
				echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  Gruppe ".$Name." TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}	
			IPS_ApplyChanges($WFC10User_ConfigId);							
			echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10User_TabPaneItem."\n";
			CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem, $WFC10User_TabPaneItem, 0, $Name, "");
			CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);

			foreach ($webfront_group as $Group => $webfront_link)
				 {
				foreach ($webfront_link as $OID => $link)
					{
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
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
	   DeleteWFCItems($WFC10User_ConfigId, "HouseTPU");
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
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
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
    echo "AMIS Installation erfolgreich abgeschlossen.\n";



?>