<?

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

				INIT

*************************************************************/

$cutter=true;


	/******************** Defaultprogrammteil ********************/
	 
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Amis');       /*   <--- change here */
	echo "\nAmis Version : ".$ergebnis;    										/*   <--- change here */
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	/*********************** Webfront GUID herausfinden **************************/
	
	echo "\n\n";
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r($result);
		}

	echo "\nWebuser activated : ";
	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];	
	if ($WFC10_Enabled)
		{
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

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_ConfigId       = $WebfrontConfigID["User"];	
	if ($WFC10User_Enabled)
		{
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		echo "WF10 User \n";
		}

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled)
		{
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile \n";
		}

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled)
		{
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		}
	echo "\n";
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
	$scriptIdAmis   = IPS_GetScriptIDByName('Amis', $CategoryIdApp);
	
	/******************* Profile Definition **********************/

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
		
	/******************* Variable Definition **********************/
	
	/* Damit kann das Auslesen der Zähler Allgemein gestoppt werden */
	//$MeterReadID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
	/* 	function CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') */
	$MeterReadID = CreateVariable("ReadMeter", 0, $CategoryIdData, 0, "AusEin-Boolean",$scriptIdAmis,0,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */	

	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];

	IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
	$MeterConfig = get_MeterConfiguration();
	//print_r($MeterConfig);
	
	/* Links für Webfront identifizieren 
	 *  Struktur [Tab] [Left, Right] [LINKID] ["NAME"]="Name"
	 *  umgesetzt auf [AMIS,Homematic, HomematicIP etc] 
	 */
	$webfront_links=array();
	
	foreach ($MeterConfig as $identifier => $meter)
		{
		echo"\n-------------------------------------------------------------\n";
		echo "Create Variableset for : ".$meter["TYPE"]." ".$meter["NAME"]." mit ID : ".$identifier." \n";
		$ID = CreateVariableByName($CategoryIdData, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
		if ($meter["TYPE"]=="Homematic")
			{
			/* Variable ID selbst bestimmen */
			$variableID = CreateVariableByName($ID, 'Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
			IPS_SetVariableCustomProfile($variableID,'~Electricity');
			AC_SetLoggingStatus($archiveHandlerID,$variableID,true);
			AC_SetAggregationType($archiveHandlerID,$variableID,1);      /* Zählerwert */
			IPS_ApplyChanges($archiveHandlerID);
			
			$LeistungID = CreateVariableByName($ID, 'Wirkleistung', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
			IPS_SetVariableCustomProfile($LeistungID,'~Power');
			AC_SetLoggingStatus($archiveHandlerID,$LeistungID,true);
			AC_SetAggregationType($archiveHandlerID,$LeistungID,0);
			IPS_ApplyChanges($archiveHandlerID);
			
			$HM_EnergieID = CreateVariableByName($ID, 'Homematic_Wirkenergie', 2);   /* 0 Boolean 1 Integer 2 Float 3 String */
			IPS_SetVariableCustomProfile($HM_EnergieID,'kWh');
	      
			SetValue($MeterReadID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */
			
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$variableID]["NAME"]="Wirkenergie";
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$LeistungID]["NAME"]="Wirkleistung";			
			}
		if ($meter["TYPE"]=="Amis")
			{
			$scriptIdAMIS   = IPS_GetScriptIDByName('AmisCutter', $CategoryIdApp);
			echo "\nScript ID für Register Variable :".$scriptIdAMIS."\n";

			if ($meter["PORT"] == "Bluetooth")
				{
				$SerialComPortID = @IPS_GetInstanceIDByName($identifier." Bluetooth COM", 0);

				if(!IPS_InstanceExists($SerialComPortID))
					{
					echo "\nAMIS Bluetooth Port mit Namen \"".$identifier." Bluetooth COM\" erstellen !";
					$SerialComPortID = IPS_CreateInstance("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // Comport anlegen
					IPS_SetName($SerialComPortID, $identifier." Bluetooth COM");

					COMPort_SetPort($SerialComPortID, $meter["COMPORT"]); // ComNummer welche dem PC-Interface zugewiesen ist!
					COMPort_SetBaudRate($SerialComPortID, '115200');
					COMPort_SetDataBits($SerialComPortID, '8');
					COMPort_SetStopBits($SerialComPortID, '1');
					COMPort_SetParity($SerialComPortID, 'None');
					COMPort_SetOpen($SerialComPortID, true);			  /* macht Fehlermeldung, wenn Port nicht offen */
			 		IPS_ApplyChanges($SerialComPortID);
					echo "Comport Bluetooth aktiviert. \n";
					$SerialComPortID = @IPS_GetInstanceIDByName($identifier." Bluetooth COM", 0);
					}
				//echo "\nCom Port : ".$com_Port." PortID: ".$SerialComPortID."\n";
				SPRT_SendText($SerialComPortID ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */
				}
			if ($meter["PORT"] == "Serial")
				{
				$SerialComPortID = @IPS_GetInstanceIDByName($identifier." Serial Port", 0);
		
				if(!IPS_InstanceExists($SerialComPortID))
					{
					echo "\nAMIS Serial Port mit Namen \"".$identifier." Serial Port\"erstellen !";
					$SerialComPortID = IPS_CreateInstance("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // Comport anlegen
					IPS_SetName($SerialComPortID, $identifier." Serial Port");
					COMPort_SetPort($SerialComPortID, $meter["COMPORT"]); // ComNummer welche dem PC-Interface zugewiesen ist!
					COMPort_SetBaudRate($SerialComPortID, '300');
					COMPort_SetDataBits($SerialComPortID, '7');
		 			COMPort_SetStopBits($SerialComPortID, '1');
	  	 			COMPort_SetParity($SerialComPortID, 'Even');
			  		COMPort_SetOpen($SerialComPortID, true);
		  	 		IPS_ApplyChanges($SerialComPortID);
  					echo "Comport Serial aktiviert. \n";
				 	$SerialComPortID = @IPS_GetInstanceIDByName($identifier." Serial Port", 0);
					}
				IPS_SetProperty($SerialComPortID, 'Open', true);   //false für aus
				IPS_ApplyChanges($SerialComPortID);
				SPRT_SetDTR($SerialComPortID, true);   /* Wichtig sonst wird der Lesekopf nicht versorgt */
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

			$AmisID = CreateVariableByName($ID, "AMIS", 3);
			$AmisReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$TimeSlotReadID = CreateVariableByName($AmisID, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
			$SendTimeID = CreateVariableByName($AmisID, "SendTime", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */	
								// Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
			$AMISReceiveCharID = CreateVariableByName($AmisID, "AMIS ReceiveChar", 3);
			$AMISReceiveChar1ID = CreateVariableByName($AmisID, "AMIS ReceiveChar1", 3);
			
			$wirkenergie1_ID = CreateVariableByName($AmisID,'Wirkenergie', 2);
  	      	IPS_SetVariableCustomProfile($wirkenergie1_ID,'~Electricity');
	      	AC_SetLoggingStatus($archiveHandlerID,$wirkenergie1_ID,true);
			AC_SetAggregationType($archiveHandlerID,$wirkenergie1_ID,1);
			IPS_ApplyChanges($archiveHandlerID);

			$aktuelleLeistungID = CreateVariableByName($AmisID, "Wirkleistung", 2);
  	      	IPS_SetVariableCustomProfile($aktuelleLeistungID,'~Power');
	      	AC_SetLoggingStatus($archiveHandlerID,$aktuelleLeistungID,true);
			AC_SetAggregationType($archiveHandlerID,$aktuelleLeistungID,0);
			IPS_ApplyChanges($archiveHandlerID);

			// Uebergeordnete Variable unter der alle ausgewerteten register eingespeichert werden
			$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
			$variableID = CreateVariableByName($zaehlerid,'Wirkenergie', 2);

			SetValue($AmisReadMeterID,true);  /* wenn Werte parametriert, dann auch regelmaessig auslesen */
			
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$wirkenergie1_ID]["NAME"]="Wirkenergie";
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$aktuelleLeistungID]["NAME"]="Wirkleistung";
			$webfront_links[$meter["TYPE"]][$meter["NAME"]][$zaehlerid]["NAME"]="Zaehlervariablen";						
			}
		print_r($meter);

		$PeriodenwerteID = CreateVariableByName($ID, "Periodenwerte", 3);
	   $KostenID = CreateVariableByName($ID, "Kosten kWh", 2);

		$letzterTagID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzterTag", 2);
   		IPS_SetVariableCustomProfile($letzterTagID,'kWh');
		IPS_SetPosition($letzterTagID, 100);
		$letzte7TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte7Tage", 2);
	   	IPS_SetVariableCustomProfile($letzte7TageID,'kWh');
  		IPS_SetPosition($letzte7TageID, 110);
		$letzte30TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte30Tage", 2);
   		IPS_SetVariableCustomProfile($letzte30TageID,'kWh');
	  	IPS_SetPosition($letzte30TageID, 120);
		$letzte360TageID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_letzte360Tage", 2);
	   	IPS_SetVariableCustomProfile($letzte360TageID,'kWh');
  		IPS_SetPosition($letzte360TageID, 130);

		$letzterTagEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzterTag", 2);
	   	IPS_SetVariableCustomProfile($letzterTagEurID,'Euro');
  		IPS_SetPosition($letzterTagEurID, 200);
		$letzte7TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte7Tage", 2);
   		IPS_SetVariableCustomProfile($letzte7TageEurID,'Euro');
	  	IPS_SetPosition($letzte7TageEurID, 210);
		$letzte30TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte30Tage", 2);
	   	IPS_SetVariableCustomProfile($letzte30TageEurID,'Euro');
  		IPS_SetPosition($letzte30TageEurID, 220);
		$letzte360TageEurID = CreateVariableByName($PeriodenwerteID, "Wirkenergie_Euro_letzte360Tage", 2);
   		IPS_SetVariableCustomProfile($letzte360TageEurID,'Euro');
	  	IPS_SetPosition($letzte360TageEurID, 230);
	  	
   	}  // ende foreach
	
	
	/******************* Profile Definition **********************/

	$pname="Zaehlt";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen

		IPS_SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Idle", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  		IPS_SetVariableProfileAssociation($pname, 1, "Active", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color

		print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }
	if (isset($AmisReadMeterID)==true) { IPS_SetVariableCustomProfile($AmisReadMeterID,'Zaehlt'); }
	IPS_SetVariableCustomProfile($MeterReadID,'Zaehlt');

	$pname="kWh";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','kWh');
		print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="Wh";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','Wh');
		print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

	$pname="kW";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','kW');
		print_r(IPS_GetVariableProfile($pname));
		}
	else
	   {
	   //print_r(IPS_GetVariableProfile($pname));
	   }

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

	/******************* Timer Definition *******************************/
	
	$scriptIdMomAbfrage   = IPS_GetScriptIDByName('MomentanwerteAbfragen', $CategoryIdApp);
	IPS_SetScriptTimer($scriptIdMomAbfrage, 60);  /* alle Minuten */


	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------

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
		CreateWFCItemTabPane   ($WFC10_ConfigId, "HouseTP", $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, "", "HouseRemote");  /* macht das Haeuschen in die oberste Leiste */
		CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, "HouseTP", 30, $WFC10_TabPaneName, $WFC10_TabPaneIcon);    /* macht die zweite Zeile unter Haeuschen, mehrere Anzeigemodule vorsehen */

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
			echo "Webfront ".$WFC10_ConfigId." löscht TabItem :".$tabItem."\n";
			DeleteWFCItems($WFC10_ConfigId, $tabItem);
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
						}
					else
						{
			 			echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft." / ".$categoryIdGroup."\n";
						CreateLinkByDestination($link["NAME"], $OID,    $categoryIdGroup,  20);
						}
					}
    			}
			}
		}
	else
	   {
	   /* Admin not enabled, alles loeschen */
		DeleteWFCItems($WFC10_ConfigId, "HouseTP");
	   }









?>