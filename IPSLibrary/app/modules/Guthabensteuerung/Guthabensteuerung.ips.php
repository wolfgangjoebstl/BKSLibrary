<?

 //Fügen Sie hier Ihren Skriptquellcode ein

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

/******************************************************

				INIT

*************************************************************/

$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
$ScriptCounterID=CreateVariableByName($parentid,"ScriptCounter",1);

$tim1ID = @IPS_GetEventIDByName("Aufruftimer", $_IPS['SELF']);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "Aufruftimer");
	IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
	}
IPS_SetEventActive($tim1ID,true);

$parentid1  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Guthabensteuerung');
$ParseGuthabenID=IPS_GetScriptIDByName('ParseDreiGuthaben',$parentid1);


if ($_IPS['SENDER']=="TimerEvent")
	{
	SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1);
   IPS_SetScriptTimer($_IPS['SELF'], 150);
 	switch(GetValue($ScriptCounterID))
		 {
   	 case 1:
		   IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=dreiat_06607625474.iim", false, false, 1);
        	break;
   	 case 2:
		   IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=dreiat_06602765645.iim", false, false, 1);
   	   break;
   	 case 3:
		   IPS_ExecuteEX(ADR_Programs."/Mozilla Firefox/firefox.exe", "imacros://run/?m=dreiat_06603192670.iim", false, false, 1);
   	   break;
   	 case 4:
		   IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=dreiat_06603404350.iim", false, false, 1);
   	   break;
   	 case 5:
		   IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=dreiat_06605960456.iim", false, false, 1);
   	   break;
   	 case 6:
		   IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=dreiat_06603404332.iim", false, false, 1);
   	   break;
   	 case 7:
  	   	IPS_RunScript($ParseGuthabenID);
		 default:
         SetValue($ScriptCounterID,0);
         IPS_SetScriptTimer($_IPS['SELF'], 0);
		   break;
		}

	}


if (($_IPS['SENDER']=="Execute") or ($_IPS['SENDER']=="WebFront"))
	{
   SetValue($ScriptCounterID,0);
   IPS_SetScriptTimer($_IPS['SELF'], 1);
   
   echo "timer gestartet, Auslesung beginnt ....\n";
   //echo ADR_Programs."Mozilla Firefox/firefox.exe";
   
   /*
	iPos iMacros legt die macros auf folgendem Verzeichnis ab

	C:\Users\Wolfgang\Documents\iMacros\Macros

	Die Macros schauen so aus und werden mit drei_telnummer.iim angespeichert

VERSION BUILD=8300326 RECORDER=FX
TAB T=1
SET !EXTRACT_TEST_POPUP NO
SET !EXTRACT NULL

SET !VAR0 06602765645
ADD !EXTRACT {{!VAR0}}
URL GOTO=https://www.drei.at/portal/de/privat/index.html
TAG POS=1 TYPE=A ATTR=ID:nav_user
TAG POS=1 TYPE=INPUT:TEXT FORM=NAME:loginForm ATTR=ID:userName CONTENT={{!VAR0}}
SET !ENCRYPTION NO
TAG POS=1 TYPE=INPUT:PASSWORD FORM=NAME:loginForm ATTR=ID:password CONTENT=cloudg06
TAG POS=1 TYPE=BUTTON ATTR=TXT:Einloggen
TAG POS=1 TYPE=DIV ATTR=ID:account-balance EXTRACT=TXT
TAG POS=1 TYPE=A ATTR=ID:Link_B2C_CoCo
SAVEAS TYPE=TXT FOLDER=* FILE=report_{{!VAR0}}
'Ausloggen
FRAME NAME="topbar"
TAG POS=1 TYPE=A ATTR=ID:nav_user
TAB CLOSE

	*/

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
   
   //$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Guthabensteuerung',$repository);
	}
	$gartensteuerung=false;
	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,20)." ".$modules."\n";
		}
	echo $inst_modules."\n\n";
   
	}



?>
