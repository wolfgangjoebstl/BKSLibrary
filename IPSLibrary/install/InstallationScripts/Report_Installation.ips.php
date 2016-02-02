<?

	/**@defgroup ipstwilight IPSTwilight
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script zur Weiterleitung von Daten an einen Visualisierungsserver in BKS
	 *
	 *
	 * @file          RemoteAccess_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Report',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Report');
	echo "\nReport Version : ".$ergebnis;

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                      "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",               "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",     "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("Report_Constants.inc.php",      				"IPSLibrary::app::modules::Report");
	
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
		echo "WF10User \n";
		}

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled==true)
	   {
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile \n";
		}

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled==true)
	   {
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		}
	
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	// ----------------------------------------------------------------------------------------------------------------------------
	// Custom Installation
	// ----------------------------------------------------------------------------------------------------------------------------

	$ReportPageTypeID = CreateVariableByName($CategoryIdData, "ReportPageType", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$ReportTimeTypeID = CreateVariableByName($CategoryIdData, "ReportTimeType", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$variableIdHTML  = CreateVariable("Uebersicht", 3 /*String*/,  $CategoryIdData, 40, '~HTMLBox', null,null,"");

	IPSUtils_Include ('Report_Configuration.inc.php', 'IPSLibrary::config::modules::Report');

	$pname="ReportPageControl";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		}
  	$report_config=Report_GetConfiguration();
  	$count=0;
	foreach ($report_config as $displaypanel=>$values)
		{
	   echo "Erstellen von Profileintrag ".$displaypanel." mit Farbe ".$values['color'].". \n";
	   IPS_SetVariableProfileAssociation($pname, $count, $displaypanel, "", $values['color']); //P-Name, Value, Assotiation, Icon, Color
	   $count++;
  		}
   IPS_SetVariableProfileValues($pname, 0, $count, 1); //PName, Minimal, Maximal, Schrittweite
	 echo "Profil erstellt mit ".$count. " Einträgen.\n";
	IPS_SetVariableCustomProfile($ReportPageTypeID,$pname); // Ziel-ID, P-Name


/*	$pname="ReportTimeControl";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); // PName, Typ 0 Boolean 1 Integer 2 Float 3 String
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues($pname, 0, 3, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation($pname, 0, "Tag", "", 0xc0c0c0); //P-Name, Value, Assotiation, Icon, Color=grau
  	   IPS_SetVariableProfileAssociation($pname, 1, "Woche", "", 0x00f0c0); //P-Name, Value, Assotiation, Icon, Color
  	   IPS_SetVariableProfileAssociation($pname, 2, "Monat", "", 0xf040f0); //P-Name, Value, Assotiation, Icon, Color
  	   IPS_SetVariableProfileAssociation($pname, 3, "Jahr", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil erstellt;\n";
		}
	IPS_SetVariableCustomProfile($ReportTimeTypeID,$pname); // Ziel-ID, P-Name    */

	$associationsPeriodAndCount  = array(
	                              //IPSPC_PERIOD_HOUR     => 'Stunde',
	                              IPSRP_PERIOD_DAY      => 'Tag',
	                              IPSRP_PERIOD_WEEK     => 'Woche',
	                              IPSRP_PERIOD_MONTH    => 'Monat',
	                              IPSRP_PERIOD_YEAR     => 'Jahr',
	                              IPSRP_COUNT_SEPARATOR => ' ',
	                              IPSRP_COUNT_MINUS     => '-',
	                              IPSRP_COUNT_VALUE     => '1',
	                              IPSRP_COUNT_PLUS      => '+',
	                              );
	CreateProfile_Associations ('ReportTimeControl',   $associationsPeriodAndCount);

	// Add Scripts, they have auto install
	$scriptIdReport   = IPS_GetScriptIDByName('Report', $CategoryIdApp);
	IPS_SetVariableCustomAction($ReportPageTypeID, $scriptIdReport);
	IPS_SetVariableCustomAction($ReportTimeTypeID, $scriptIdReport);


	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren auf ".$WFC10_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		CreateLinkByDestination('Uebersicht', $variableIdHTML,    $categoryId_WebFront,  20);
		CreateLinkByDestination('ReportPageType', $ReportPageTypeID,    $categoryId_WebFront,  10);
		CreateLinkByDestination('ReportTimeType', $ReportTimeTypeID,    $categoryId_WebFront,  11);
		}
		
	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren auf ".$WFC10User_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);

		}

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren auf ".$Mobile_Path.": \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);

		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren auf ".$Retro_Path.": \n";
		createPortal($Retro_Path);
		}


/** Anlegen eines Profils mit Associations
	 *
	 * der Befehl legt ein Profile an und erzeugt für die übergebenen Werte Assoziationen
	 *
	 * @param string $Name Name des Profiles
	 * @param string $Associations[] Array mit Wert und Namens Zuordnungen
	 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
	 * @param integer $Color[] Array mit Farbwerten im HTML Farbcode (z.b. 0x0000FF für Blau). Sonderfall: -1 für Transparent
	 * @param boolean $DeleteProfile Profile löschen und neu generieren
	 *
	 *   function CreateProfile_Associations ($Name, $Associations, $Icon="", $Color=-1, $DeleteProfile=true)
	 */


?>
