<?

	/**@defgroup DetectMovement
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script um Herauszufinden ob wer zu Hause ist
	 *
	 *
	 * @file          DetectMovement_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
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
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Guthabensteuerung');
	echo "\nRemoteAccess Version : ".$ergebnis;

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	echo $inst_modules;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	/* Create Variables */

	$ScriptCounterID=CreateVariableByName($CategoryIdData,"ScriptCounter",1);
	$ParseGuthabenID=IPS_GetScriptIDByName('ParseDreiGuthaben',$CategoryIdApp);

	IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

	$GuthabenConfig = get_GuthabenConfiguration();
	print_r($GuthabenConfig);

	foreach ($GuthabenConfig as $TelNummer)
		{
		$handle2=fopen("c:/Users/Wolfgang/Documents/iMacros/Macros/dreiat_".$TelNummer["NUMMER"].".iim","w");
      fwrite($handle2,'VERSION BUILD=8300326 RECORDER=FX'."\n");
      fwrite($handle2,'TAB T=1'."\n");
      fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
      fwrite($handle2,'SET !EXTRACT NULL'."\n");
      fwrite($handle2,'SET !VAR0 '.$TelNummer["NUMMER"]."\n");
      fwrite($handle2,'ADD !EXTRACT {{!VAR0}}'."\n");
      fwrite($handle2,'URL GOTO=https://www.drei.at/portal/de/privat/index.html'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:nav_user'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:loginForm ATTR=ID:userName CONTENT={{!VAR0}}'."\n");
      fwrite($handle2,'SET !ENCRYPTION NO'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=NAME:loginForm ATTR=ID:password CONTENT='.$TelNummer["PASSWORD"]."\n");
      fwrite($handle2,'TAG POS=1 TYPE=BUTTON ATTR=TXT:Einloggen'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=DIV ATTR=ID:account-balance EXTRACT=TXT'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:Link_B2C_CoCo'."\n");
      fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_dreiat_{{!VAR0}}'."\n");
      fwrite($handle2,'\'Ausloggen'."\n");
      fwrite($handle2,'FRAME NAME="topbar"'."\n");
      fwrite($handle2,'TAG POS=1 TYPE=A ATTR=ID:nav_user'."\n");
      fwrite($handle2,'TAB CLOSE'."\n");
		fclose($handle2);
      }




	/* Create Web Pages */

?>
