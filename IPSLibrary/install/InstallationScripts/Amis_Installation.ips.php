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
		echo "Retro \n";
		}

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');


	/******************* Variable Definition **********************/
	
	$ReadMeterID = CreateVariableByName($CategoryIdData, "ReadMeter", 0);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$TimeSlotReadID = CreateVariableByName($CategoryIdData, "TimeSlotRead", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$AMISReceiveID = CreateVariableByName($CategoryIdData, "AMIS Receive", 3);
	
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
	   print_r(IPS_GetVariableProfile($pname));
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
	   print_r(IPS_GetVariableProfile($pname));
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
	   print_r(IPS_GetVariableProfile($pname));
	   }
	   
	/******************* Timer Definition *******************************/
	
	$scriptIdMomAbfrage   = IPS_GetScriptIDByName('MomentanwerteAbfragen', $CategoryIdApp);
	IPS_SetScriptTimer($scriptIdMomAbfrage, 60);  /* alle Minuten */

	/******************* Module richtig einstellen *******************************/

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

	
?>
