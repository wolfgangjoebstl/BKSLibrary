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


	/******************** Defaultprogrammteil ********************/
	 
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
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

	echo "\nWebuser activated : ";
	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	if ($WFC10_Enabled)
		{
		$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
		echo "Admin ";
		}


	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	if ($WFC10User_Enabled)
		{
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		echo "User ";
		}

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled)
		{
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile ";
		}

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled)
		{
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro ";
		}
	echo "\n";
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');


	/******************* Variable Definition **********************/

	$parentid1  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
	
	$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
	$archiveHandlerID = $archiveHandlerID[0];

	IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
	$MeterConfig = get_MeterConfiguration();
	//print_r($MeterConfig);
	
	$installAmis=false;  /* nur installieren wenn auch in Config enthalten */
	
	foreach ($MeterConfig as $meter)
		{
		echo"\n-------------------------------------------------------------\n";
		echo "Create Variableset for : ".$meter["TYPE"]." ".$meter["NAME"]." \n";
		$ID = CreateVariableByName($parentid1, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
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
		   }
		if ($meter["TYPE"]=="Amis")
		   {
		   $installAmis=true;
		   /* kann derzeit nur ein AMIS Modul installieren */
			$variableID = $meter["WirkenergieID"];
			$AmisID = CreateVariableByName($ID, "AMIS", 3);
			$ReadMeterID = CreateVariableByName($AmisID, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$TimeSlotReadID = CreateVariableByName($AmisID, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
			$AMISReceiveID = CreateVariableByName($AmisID, "AMIS Receive", 3);
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

	/******************* Module richtig einstellen *******************************/

	if ( $installAmis == true )
	   {
		/* Bluetooth oder Serial Port */
   	IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
	   $AmisConfig = get_AmisConfiguration();

	  	if ($AmisConfig["Type"] == "Bluetooth")
		   {
	   	$SerialComPortID = @IPS_GetInstanceIDByName("AMIS Bluetooth COM", 0);

	      if(!IPS_InstanceExists($SerialComPortID))
		      {
     			echo "\nAMIS Blutooth Port erstellen !";
	      	$SerialComPortID = IPS_CreateInstance("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // Comport anlegen
	     		IPS_SetName($SerialComPortID, "AMIS Bluetooth COM");

			   COMPort_SetPort($SerialComPortID, 'COM3'); // ComNummer welche dem PC-Interface zugewiesen ist!
  				COMPort_SetBaudRate($SerialComPortID, '115200');
			   COMPort_SetDataBits($SerialComPortID, '8');
  				COMPort_SetStopBits($SerialComPortID, '1');
		    	COMPort_SetParity($SerialComPortID, 'Keine');
  				COMPort_SetOpen($SerialComPortID, true);
	   	 	IPS_ApplyChanges($SerialComPortID);
     			echo "Comport Bluetooth aktiviert. \n";
			   $SerialComPortID = @IPS_GetInstanceIDByName("AMIS Bluetooth COM", 0);
		      }
			//echo "\nCom Port : ".$com_Port." PortID: ".$SerialComPortID."\n";
	      COMPort_SendText($SerialComPortID ,"\xFF0");   /* Vogts Bluetooth Tastkopf auf 300 Baud umschalten */
			}

		if ($AmisConfig["Type"] == "Serial")
		   {
	   	$SerialComPortID = @IPS_GetInstanceIDByName("AMIS Serial Port", 0);
	  		//$com_Port = $SerialComPortID[0];

	   	if(!IPS_InstanceExists($SerialComPortID))
	      	{
		      echo "AMIS Serial Port erstellen !";
   		   $SerialComPortID = IPS_CreateInstance("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // Comport anlegen
      		IPS_SetName($SerialComPortID, "AMIS Serial Port");
			   COMPort_SetPort($SerialComPortID, 'COM3'); // ComNummer welche dem PC-Interface zugewiesen ist!
   		 	COMPort_SetBaudRate($SerialComPortID, '300');
		   	COMPort_SetDataBits($SerialComPortID, '7');
		   	COMPort_SetStopBits($SerialComPortID, '1');
	   	 	COMPort_SetParity($SerialComPortID, 'Even');
		    	COMPort_SetOpen($SerialComPortID, true);
	   	 	IPS_ApplyChanges($SerialComPortID);
   	   	echo "Comport Serial aktiviert. \n";
			   $SerialComPortID = @IPS_GetInstanceIDByName("AMIS Serial Port", 0);
  				//$com_Port = $SerialComPortID[0];
	      	}
			COMPort_SetOpen($SerialComPortID, true); //false für aus
			IPS_ApplyChanges($SerialComPortID);
			COMPort_SetDTR($SerialComPortID , true); /* Wichtig sonst wird der Lesekopf nicht versorgt */
			}

		$scriptIdAMIS   = IPS_GetScriptIDByName('AmisCutter', $CategoryIdApp);
		echo "\nScript ID für Register Variable :".$scriptIdAMIS."\n";

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
		} // nur wenn AMIS zum installieren
	
?>
