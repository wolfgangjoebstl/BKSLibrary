<?

	/**@defgroup Stromheizung
	 *
	 * Script um automatisch irgendetwas ein und auszuschalten
	 *
	 *
	 * @file          Autosteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Autosteuerung\Autosteuerung_Configuration.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Autosteuerung\Autosteuerung.class.php");


	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Autosteuerung');
	echo "\nAutosteuerung Version : ".$ergebnis;

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$scriptIdWebfrontControl   = IPS_GetScriptIDByName('WebfrontControl', $CategoryIdApp);
	$scriptIdAutosteuerung   = IPS_GetScriptIDByName('Autosteuerung', $CategoryIdApp);

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

	$eventType='OnChange';

	$categoryId_Autosteuerung  = CreateCategory("Ansteuerung", $CategoryIdData, 10);

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf-Autosteuerung',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	/* Nachrichtenzeilen werden automatisch von der Logging Klasse gebildet */
/*	$zeile1 = CreateVariable("Nachricht_Zeile01",3,$categoryId_Nachrichten, 10, "",null,null,""  );
	$zeile2 = CreateVariable("Nachricht_Zeile02",3,$categoryId_Nachrichten, 20, "",null,null,""  );
	$zeile3 = CreateVariable("Nachricht_Zeile03",3,$categoryId_Nachrichten, 30, "",null,null,""  );
	$zeile4 = CreateVariable("Nachricht_Zeile04",3,$categoryId_Nachrichten, 40, "",null,null,""  );
	$zeile5 = CreateVariable("Nachricht_Zeile05",3,$categoryId_Nachrichten, 50, "",null,null,""  );
	$zeile6 = CreateVariable("Nachricht_Zeile06",3,$categoryId_Nachrichten, 60, "",null,null,""  );
	$zeile7 = CreateVariable("Nachricht_Zeile07",3,$categoryId_Nachrichten, 70, "",null,null,"" );
	$zeile8 = CreateVariable("Nachricht_Zeile08",3,$categoryId_Nachrichten, 80, "",null,null,""  );
	$zeile9 = CreateVariable("Nachricht_Zeile09",3,$categoryId_Nachrichten, 90, "",null,null,""  );
	$zeile10 = CreateVariable("Nachricht_Zeile10",3,$categoryId_Nachrichten, 100, "",null,null,""  );
	$zeile11 = CreateVariable("Nachricht_Zeile11",3,$categoryId_Nachrichten, 110, "",null,null,""  );
	$zeile12 = CreateVariable("Nachricht_Zeile12",3,$categoryId_Nachrichten, 120, "",null,null,""  );
	$zeile13 = CreateVariable("Nachricht_Zeile13",3,$categoryId_Nachrichten, 130, "",null,null,""  );
	$zeile14 = CreateVariable("Nachricht_Zeile14",3,$categoryId_Nachrichten, 140, "",null,null,""  );
	$zeile15 = CreateVariable("Nachricht_Zeile15",3,$categoryId_Nachrichten, 150, "",null,null,""  );
	$zeile16 = CreateVariable("Nachricht_Zeile16",3,$categoryId_Nachrichten, 160, "",null,null,""  ); */

	$AutoSetSwitches = Autosteuerung_SetSwitches();
	$register=new AutosteuerungHandler($scriptIdAutosteuerung);
	$webfront_links=array();
	foreach ($AutoSetSwitches as $AutoSetSwitch)
		{
	   // CreateVariable($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
	   $AutosteuerungID = CreateVariable($AutoSetSwitch["NAME"], 1, $categoryId_Autosteuerung, 0, $AutoSetSwitch["PROFIL"],$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
		$register->registerAutoEvent($AutosteuerungID, $eventType, "par1", "par2");
		$webfront_links[$AutosteuerungID]["NAME"]=$AutoSetSwitch["NAME"];
		$webfront_links[$AutosteuerungID]["ADMINISTRATOR"]=$AutoSetSwitch["ADMINISTRATOR"];
		$webfront_links[$AutosteuerungID]["USER"]=$AutoSetSwitch["USER"];
		$webfront_links[$AutosteuerungID]["MOBILE"]=$AutoSetSwitch["MOBILE"];
		echo "Register Webfront Events : ".$AutoSetSwitch["NAME"]." with ID : ".$AutosteuerungID."\n";
		}
	//print_r($AutoSetSwitches);

	/*
   $AutosteuerungID = CreateVariable("Ventilatorsteuerung", 1, $categoryId_Autosteuerung, 0, "AutosteuerungProfil",$scriptIdWebfrontControl,null,""  );  
	registerAutoEvent($AutosteuerungID, $eventType, "par1", "par2");

   $AnwesenheitssimulationID = CreateVariable("Anwesenheitssimulation", 1, $categoryId_Autosteuerung, 0, "AutosteuerungProfil",$scriptIdWebfrontControl,null,""  );  
	registerAutoEvent($AnwesenheitssimulationID, $eventType, "par1", "par2");
	*/
	
	/* Programme f�r Schalter registrieren nach OID des Events */
	/*
	 * war schon einmal ausgeklammert, wird aber intuitiv von der Install Routine erwartet dass auch die Events registriert werden
	 *
	 */
	echo "\nProgramme f�r Schalter registrieren nach OID des Events.\n";

	$AutoConfiguration = Autosteuerung_GetEventConfiguration();
	foreach ($AutoConfiguration as $variableId=>$params)
		{
		echo "Create Event f�r ID : ".$variableId."   ".IPS_GetName($variableId)." \n";
		$register->CreateEvent($variableId, $params[0], $scriptIdAutosteuerung);
		}

	/*******************************************************/
		
	$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $scriptIdAutosteuerung);
	if ($tim1ID==false)
		{
		$tim1ID = IPS_CreateEvent(1);
		IPS_SetParent($tim1ID, $scriptIdAutosteuerung);
		IPS_SetName($tim1ID, "Aufruftimer");
		IPS_SetEventCyclic($tim1ID,0,0,0,0,2,5);
		//IPS_SetEventCyclicTimeFrom($tim1ID,1,40,0);  /* immer um 02:20 */
		}
	IPS_SetEventActive($tim1ID,true);
		

	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------

	print_r($webfront_links);
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
      IPS_SetPosition($categoryId_WebFront,700);
		foreach ($webfront_links as $OID => $webfront_link)
		   {
		   if ($webfront_link["ADMINISTRATOR"]==true)
				{
				CreateLinkByDestination($webfront_link["NAME"], $OID,    $categoryId_WebFront,  10);
				}
			}
		CreateLinkByDestination("Nachrichtenverlauf", $categoryId_Nachrichten,    $categoryId_WebFront,  20);
		}

	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);
		}

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);
		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Retro_Path);
		}

/***************************************************************************************/


?>
