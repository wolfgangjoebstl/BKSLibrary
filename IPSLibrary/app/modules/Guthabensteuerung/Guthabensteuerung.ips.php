<?

 //Fügen Sie hier Ihren Skriptquellcode ein

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

/******************************************************

				INIT

*************************************************************/

$ScriptCounterID=CreateVariableByName($_IPS['SELF'],"ScriptCounter",1);

if ($_IPS['SENDER']=="TimerEvent")
	{
	SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1);
   IPS_SetScriptTimer($_IPS['SELF'], 150);
 	switch(GetValue($ScriptCounterID))
		 {
   	 case 1:
		   //IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=drei_06607625474.iim", false, false, 1);
        	break;
   	 case 2:
		   //IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=drei_06602765645.iim", false, false, 1);
   	   break;
   	 case 3:
		   //IPS_ExecuteEX(ADR_Programs."/Mozilla Firefox/firefox.exe", "imacros://run/?m=drei_06603192670.iim", false, false, 1);
   	   break;
   	 case 4:
		   //IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=drei_06603404350.iim", false, false, 1);
   	   break;
   	 case 5:
		   //IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=drei_06605960456.iim", false, false, 1);
   	   break;
   	 case 6:
		   //IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=drei_06603404332.iim", false, false, 1);
   	   break;
   	 case 7:
  	   	IPS_RunScript(38018);
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
   //echo ADR_Programs."Mozilla Firefox/firefox.exe";
   
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
