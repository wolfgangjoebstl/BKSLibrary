<?

//Autostart

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\Watchdog\Watchdog_Configuration.inc.php");
	
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Watchdog',$repository);
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$scriptIdStartWD    = IPS_GetScriptIDByName('StartIPSWatchDog', $CategoryIdApp);
	$scriptIdStopWD     = IPS_GetScriptIDByName('StopIPSWatchDog', $CategoryIdApp);
	$scriptIdAliveWD    = IPS_GetScriptIDByName('IWDAliveFileSkript', $CategoryIdApp);
	echo "Die Scripts sind auf              : ".$CategoryIdApp."\n";
	echo "StartIPSWatchDog hat die ScriptID : ".$scriptIdStartWD." \n";
	echo "StopIPSWatchDog hat die ScriptID  : ".$scriptIdStopWD." \n";
	echo "Alive WatchDog hat die ScriptID   : ".$scriptIdAliveWD." \n";

	echo "\nEigenen Logspeicher für Watchdog vorbereiten.\n";
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_Watchdog=new Logging("C:\Scripts\Log_Watchdog.csv",$input);

	echo "Logspeicher für OperationCenter mitnutzen.\n";
	$moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
	$CategoryIdDataOC     = $moduleManager->GetModuleCategoryID('data');
	$categoryId_NachrichtenOC    = CreateCategory('Nachrichtenverlauf',   $CategoryIdDataOC, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenOC, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
	
	/*********************************************************************/
	writeLogEvent("Autostart (Beginn)");
	IPSLogger_Dbg(__file__, "Autostart: Prozedur beginnt");

	$log_OperationCenter->LogMessage('Lokaler Server wird hochgefahren');
	$log_OperationCenter->LogNachrichten('Lokaler Server wird hochgefahren');

	/********************************************************************
	 *
	 * Init
	 *
	 **********************************************************************/

	$config=Watchdog_Configuration();
	//print_r($config);
	
	$tim2ID = @IPS_GetEventIDByName("KeepAlive", $scriptIdAliveWD);
	$tim3ID = @IPS_GetEventIDByName("StartWD", $scriptIdStartWD);


	/********************************************************************
	 *
	 * feststellen ob Prozesse schon laufen, dann muessen sie nicht mehr gestartet werden
	 *
	 **********************************************************************/

	echo "\n";
	$processStart=array("IPSWatchDog.exe" => "On","vmplayer.exe" => "On", "iTunes.exe" => "On");
	$processStart=checkProcess($processStart);
	echo "Die folgenden Programme muessen gesstartet (wenn On) werden:\n";
	print_r($processStart);
	
	if ( (fileAvailable("IPSWatchDog.exe",$config["Software"]["Watchdog"]["Directory"])) == false )
	   {
	   echo "Keine Installation von IPSWatchdog vorhanden.\n";
	   $processStart["IPSWatchDog.exe"]=="Off";
		}

	$handle2=fopen("c:/scripts/process_username.bat","w");
	fwrite($handle2,'echo %username% >>username.txt'."\r\n");
	//fwrite($handle2,"pause\r\n");
	fclose($handle2);
	IPS_ExecuteEx("c:/scripts/process_username.bat","", true, false,-1);
	$handle3=fopen("c:/scripts/username.txt","r");
	echo "Username von dem aus IP Symcon zugreift ist : ".fgets($handle3);
	fclose($handle3);

	if ($processStart["IPSWatchDog.exe"] == "On")
	   {
	   echo "IPSWatchdog.exe wird neu gestartet.\n";
		IPSLogger_Dbg(__file__, "Autostart: Watchdog wird gestartet");
	
		/*********************************************************************/
		writeLogEvent("Autostart (Watchdog)");

		IPS_EXECUTEEX("C:/IP-Symcon/IPSWatchDog.exe","",true,true,-1);   /* Watchdog starten */
		
	 	// Parent-ID der Kategorie ermitteln
		$parentID = IPS_GetObject($IPS_SELF);
		$parentID = $parentID['ParentID'];

		// ID der Skripte ermitteln
		$IWDAliveFileSkriptScID = IPS_GetScriptIDByName("IWDAliveFileSkript", $parentID);
		$IWDSendMessageScID = IPS_GetScriptIDByName("IWDSendMessage", $parentID);

		IPS_RunScript($IWDAliveFileSkriptScID);
	 	IPS_RunScriptEx($IWDSendMessageScID, Array('state' =>  'start'));
		}
	else
	   {
	   echo "IPSWatchdog.exe muss daher nicht erneut gestartet werden.\n";
	   }

	if ( (fileAvailable("vmplayer.exe",$config["Software"]["VMware"]["Directory"])) == false )
	   {
	   echo "Keine Installation von VMware vorhanden.\n";
	   $processStart["vmplayer.exe"]=="Off";
		}
		
	if ( (fileAvailable("*.vmx",$config["Software"]["VMware"]["DirFiles"])) == false )
	   {
	   echo "Keine Images für VMPlayer vorhanden.\n";
	   $processStart["vmplayer.exe"]=="Off";
		}

	if ($processStart["vmplayer.exe"] == "On")
	   {
		writeLogEvent("Autostart (VMPlayer)");
		IPSLogger_Dbg(__file__, "Autostart: VMWare Player wird gestartet");
	
		/*********************************************************************/

		IPS_EXECUTEEX("C:/Program Files (x86)/VMware/VMware Player/vmplayer.exe",'"c:\Scripts\Windows 7 IPS\Windows 7 IPS.vmx"',true,false,-1);
		}
	else
	   {
	   echo "vmplayer.exe muss daher nicht erneut gestartet werden.\n";
	   }

	$result=IPS_EXECUTE("c:/windows/system32/tasklist.exe","/APPS", true, true);
	echo $result;

//if (GetValueBoolean(46719))
	   	{
			echo "Itunes Ausschalten und gleich wieder einschalten, wie auch immer um Mitternacht.\n";
   		/* iTunes ausschalten */
			$handle2=fopen("c:/scripts/process_kill_itunes.bat","w");
			fwrite($handle2,'c:/Windows/System32/taskkill.exe /im itunes.exe');
			fwrite($handle2,"\r\n");
			//fwrite($handle2,"pause\r\n");
			fclose($handle2);
			IPS_ExecuteEx("c:/scripts/process_kill_itunes.bat","", true, true,-1); // Warten auf true gesetzt, das ist essentiell
			IPS_ExecuteEx("c:/Program Files/iTunes/iTunes.exe","",true,false,-1);  // C:\Program Files\iTunes
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
			IPS_ExecuteEx("c:/scripts/process_kill_java.bat","", true, true,-1);  // Warten auf true gesetzt, das ist essentiell
			IPS_ExecuteEx("c:/Scripts/Startsoap.bat","",true,false,-1);
			writeLogEvent("Autostart (SOAP)");
			}
/* ftp Server wird nun automatisch mit der IS Umgebung von Win 10 gestartet, keine Fremd-Software mehr erforderlich */
//IPS_ExecuteEx("c:/Users/wolfg_000/Downloads/Programme/47 ftp server/ftpserver31lite/ftpserver.exe","", true, false,1);
//writeLogEvent("Autostart (ftpserverlite)");

	writeLogEvent("Autostart (Firefox)");

IPS_EXECUTEEX("C:/Program Files (x86)/Mozilla Firefox/firefox.exe",'http://10.0.1.20:88/',true,false,-1);

//IPS_EXECUTEEX("C:/Program Files (x86)/Mozilla Firefox/firefox.exe",'http://10.0.1.20:88/',true,false,-1);
//IPS_EXECUTEEX("C:/Program Files (x86)/Mozilla Firefox/firefox.exe","https://127.0.0.1:82/",true,false,1);
/* ab und zu Fehlermeldung Warning: There were no token found for specified session: 1 */

writeLogEvent("Autostart (Ende)");



?>
