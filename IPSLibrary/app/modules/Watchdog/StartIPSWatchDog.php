<?

//Autostart

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	echo "Logspeicher vribereiten.\n";

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

	writeLogEvent("Autostart (Beginn)");

	$log_OperationCenter->LogMessage('Lokaler Server wird hochgefahren');
	$log_OperationCenter->LogNachrichten('Lokaler Server wird hochgefahren');

	IPS_EXECUTEEX("C:/IP-Symcon/IPSWatchDog.exe","",true,false,1);   /* Watchdog starten */



SetValue(27651,date("d.m.y H:i:s")." : Reboot PC oder IPS ");
IPS_RunScript(48195);

 // Parent-ID der Kategorie ermitteln
$parentID = IPS_GetObject($IPS_SELF);
$parentID = $parentID['ParentID'];

// ID der Skripte ermitteln
$IWDAliveFileSkriptScID = IPS_GetScriptIDByName("IWDAliveFileSkript", $parentID);
$IWDSendMessageScID = IPS_GetScriptIDByName("IWDSendMessage", $parentID);

 IPS_RunScript($IWDAliveFileSkriptScID);
 IPS_RunScriptEx($IWDSendMessageScID, Array('state' =>  'start'));
 
writeLogEvent("Autostart (Firefox)");

IPS_EXECUTEEX("C:/Program Files (x86)/VMware/VMware Player/vmplayer.exe",'"c:\Scripts\Windows 7 IPS\Windows 7 IPS.vmx"',true,false,1);
writeLogEvent("Autostart (VMware)");

//if (GetValueBoolean(46719))
	   	{
			echo "Itunes Ausschalten und gleich wieder einschalten, wie auch immer um Mitternacht.\n";
   		/* iTunes ausschalten */
			$handle2=fopen("c:/scripts/process_kill_itunes.bat","w");
			fwrite($handle2,'c:/Windows/System32/taskkill.exe /im itunes.exe');
			fwrite($handle2,"\r\n");
			//fwrite($handle2,"pause\r\n");
			fclose($handle2);
			IPS_ExecuteEx("c:/scripts/process_kill_itunes.bat","", true, false,1);
			IPS_ExecuteEx("c:/Program Files/iTunes/iTunes.exe","",true,false,1);
			writeLogEvent("Autostart (iTunes)");
			}

//if (GetValueBoolean(50871))
		   {
			echo "SOAP Ausschalten und gleich wieder einschalten, wie auch immer um Mitternacht.\n";
	   	/* Soap ausschalten */
			$handle2=fopen("c:/scripts/process_kill_java.bat","w");
			fwrite($handle2,'c:/Windows/System32/taskkill.exe /f /im java.exe');
			fwrite($handle2,"\r\n");
			//fwrite($handle2,"pause\r\n");
			fclose($handle2);
			IPS_ExecuteEx("c:/scripts/process_kill_java.bat","", true, false,1);
			IPS_ExecuteEx("c:/Scripts/Startsoap.bat","",true,false,1);
			writeLogEvent("Autostart (SOAP)");
			}
/* ftp Server wird nun automatisch mit der IS Umgebung von Win 10 gestartet, keine Fremd-Software mehr erforderlich */
//IPS_ExecuteEx("c:/Users/wolfg_000/Downloads/Programme/47 ftp server/ftpserver31lite/ftpserver.exe","", true, false,1);
//writeLogEvent("Autostart (ftpserverlite)");

writeLogEvent("Autostart (Watchdog)");

IPS_EXECUTEEX("C:/Program Files (x86)/Mozilla Firefox/firefox.exe",'http://10.0.1.20:88/',true,false,1);
//IPS_EXECUTEEX("C:/Program Files (x86)/Mozilla Firefox/firefox.exe","https://127.0.0.1:82/",true,false,1);
/* ab und zu Fehlermeldung Warning: There were no token found for specified session: 1 */

writeLogEvent("Autostart (Ende)");
?>
