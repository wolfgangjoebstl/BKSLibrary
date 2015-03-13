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
	IPS_SetEventCyclic($tim1ID,0,0,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
	}
IPS_SetEventActive($tim1ID,true);

$tim2ID = @IPS_GetEventIDByName("Exectimer", $_IPS['SELF']);
if ($tim2ID==false)
	{
	$tim2ID = IPS_CreateEvent(1);
	IPS_SetParent($tim2ID, $_IPS['SELF']);
	IPS_SetName($tim2ID, "Exectimer");
	IPS_SetEventCyclic($tim2ID,2,1,0,0,1,150);      /* alle 150 sec */
	//IPS_SetEventCyclicTimeFrom($tim1ID,2,10,0);  /* immer um 02:10 */
	}

$parentid1  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Guthabensteuerung');
$ParseGuthabenID=IPS_GetScriptIDByName('ParseDreiGuthaben',$parentid1);

	$GuthabenConfig = get_GuthabenConfiguration();
	$GuthabenAllgConfig = get_GuthabenAllgemeinConfig();

	$phone=array();
	$i=1;
	foreach ($GuthabenConfig as $TelNummer)
		{
		//echo "Telefonnummer ".$TelNummer["NUMMER"]."\n";
		$phone[$i++]=$TelNummer["NUMMER"];
		}
	$maxcount=$i;
		
if ($_IPS['SENDER']=="TimerEvent")
	{
	//IPSLogger_Dbg(__file__, "TimerEvent from :".$_IPS['EVENT']);
	switch ($_IPS['EVENT'])
	   {
	   case $tim1ID:
	      break;
	   case $tim2ID:
			//IPSLogger_Dbg(__file__, "TimerExecEvent from :".$_IPS['EVENT']." ScriptcountID:".GetValue($ScriptCounterID)." von ".$maxcount);
			SetValue($ScriptCounterID,GetValue($ScriptCounterID)+1);
		   //IPS_SetScriptTimer($_IPS['SELF'], 150);
		   if (GetValue($ScriptCounterID) < $maxcount)
				{
			   IPS_ExecuteEX(ADR_Programs."Mozilla Firefox/firefox.exe", "imacros://run/?m=dreiat_".$phone[GetValue($ScriptCounterID)].".iim", false, false, 1);
  	   		}
			else
				{
				IPS_RunScript($ParseGuthabenID);
		      SetValue($ScriptCounterID,0);
      		//IPS_SetScriptTimer($_IPS['SELF'], 0);
		      IPS_SetEventActive($tim2ID,false);
				}
			break;
		default:
		   break;
		}
	}


if (($_IPS['SENDER']=="Execute") or ($_IPS['SENDER']=="WebFront"))
	{
	echo "Verzeichnis für Macros    :".$GuthabenAllgConfig["MacroDirectory"]."\n";
	echo "Verzeichnis für Ergebnisse:".$GuthabenAllgConfig["DownloadDirectory"]."\n\n";
	
	print_r($phone);

	echo "Stand ScriptCounter :".GetValue($ScriptCounterID)." von max ".$maxcount."\n";
   SetValue($ScriptCounterID,0);

	//IPS_SetScriptTimer($_IPS['SELF'], 1);
	IPS_SetEventActive($tim2ID,true);
   echo "Exectimer gestartet, Auslesung beginnt ....\n";
   echo "Timer täglich ID:".$tim1ID."   ".$tim2ID."\n";
   //echo ADR_Programs."Mozilla Firefox/firefox.exe";
   
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
