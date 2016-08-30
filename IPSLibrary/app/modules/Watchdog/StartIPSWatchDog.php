<?

//Autostart

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	echo "Logspeicher vorbereiten.\n";

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

	/*********************************************************************/
	writeLogEvent("Autostart (Beginn)");

	$log_OperationCenter->LogMessage('Lokaler Server wird hochgefahren');
	$log_OperationCenter->LogNachrichten('Lokaler Server wird hochgefahren');

	$startWD=false;
	$verzeichnis='C:/IP-Symcon/';
	if ( is_dir ( $verzeichnis ))
		{
    	// öffnen des Verzeichnisses
    	if ( $handle = opendir($verzeichnis) )
    		{
        	while (($file = readdir($handle)) !== false)
        		{
				$dateityp=filetype( $verzeichnis.$file );
            if ($dateityp == "file")
            	{
            	if ($file == "IPSWatchDog.exe")
						 {
						 echo "IWD Watchdog bereits installiert.\n";
						 $startWD=true;
						 }
            	//echo $file."\n";
         		}
      	  	} /* Ende while */
	     	closedir($handle);
   		} /* end if dir */
		}/* ende if isdir */
	else
	   {
	   echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
		}

	$handle2=fopen("c:/scripts/process_username.bat","w");
	fwrite($handle2,'echo %username% >>username.txt'."\r\n");
	//fwrite($handle2,"pause\r\n");
	fclose($handle2);
	IPS_ExecuteEx("c:/scripts/process_username.bat","", true, false,-1);
	$handle3=fopen("c:/scripts/username.txt","r");
	echo "Username von dem aus IP Symcon zugreift ist : ".fgets($handle3);
	fclose($handle3);

	if ($startWD == true)
	   {
		/*********************************************************************/
		writeLogEvent("Autostart (Watchdog)");

		IPS_EXECUTEEX("C:/IP-Symcon/IPSWatchDog.exe","",true,false,-1);   /* Watchdog starten */
		
	 	// Parent-ID der Kategorie ermitteln
		$parentID = IPS_GetObject($IPS_SELF);
		$parentID = $parentID['ParentID'];

		// ID der Skripte ermitteln
		$IWDAliveFileSkriptScID = IPS_GetScriptIDByName("IWDAliveFileSkript", $parentID);
		$IWDSendMessageScID = IPS_GetScriptIDByName("IWDSendMessage", $parentID);

		IPS_RunScript($IWDAliveFileSkriptScID);
	 	IPS_RunScriptEx($IWDSendMessageScID, Array('state' =>  'start'));
		}

	$startVM=false;
	$verzeichnis='C:/Program Files (x86)/VMware/VMware Player/';
	if ( is_dir ( $verzeichnis ))
		{
    	// öffnen des Verzeichnisses
    	if ( $handle = opendir($verzeichnis) )
    		{
        	while (($file = readdir($handle)) !== false)
        		{
				$dateityp=filetype( $verzeichnis.$file );
            if ($dateityp == "file")
            	{
            	if ($file == "vmplayer.exe")
						 {
						 echo "VMware Player bereits installiert.\n";
						 $startVM=true;
						 }
            	//echo $file."\n";
         		}
      	  	} /* Ende while */
	     	closedir($handle);
   		} /* end if dir */
		}/* ende if isdir */
	else
	   {
	   echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
		}


	$verzeichnis='c:/Scripts/Windows 7 IPS/';
	if ( is_dir ( $verzeichnis ))
		{
    	// öffnen des Verzeichnisses
    	if ( $handle = opendir($verzeichnis) )
    		{
        	while (($file = readdir($handle)) !== false)
        		{
				$dateityp=filetype( $verzeichnis.$file );
            if ($dateityp == "file")
            	{
            	if ($file == "vmplayer.exe")
						 {
						 echo "VMware Player bereits installiert.\n";
						 }
            	echo $file."\n";
         		}
      	  	} /* Ende while */
	     	closedir($handle);
   		} /* end if dir */
		}/* ende if isdir */
	else
	   {
	   echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
		}


	IPS_EXECUTEEX("C:/Program Files (x86)/VMware/VMware Player/vmplayer.exe",'"c:\Scripts\Windows 7 IPS\Windows 7 IPS.vmx"',true,false,-1);

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
			IPS_ExecuteEx("c:/scripts/process_kill_itunes.bat","", true, false,-1);
			IPS_ExecuteEx("c:/Program Files/iTunes/iTunes.exe","",true,false,-1);
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
			IPS_ExecuteEx("c:/scripts/process_kill_java.bat","", true, false,-1);
			IPS_ExecuteEx("c:/Scripts/Startsoap.bat","",true,false,-1);
			writeLogEvent("Autostart (SOAP)");
			}
/* ftp Server wird nun automatisch mit der IS Umgebung von Win 10 gestartet, keine Fremd-Software mehr erforderlich */
//IPS_ExecuteEx("c:/Users/wolfg_000/Downloads/Programme/47 ftp server/ftpserver31lite/ftpserver.exe","", true, false,1);
//writeLogEvent("Autostart (ftpserverlite)");

	writeLogEvent("Autostart (Firefox)");


IPS_EXECUTEEX("C:/Program Files (x86)/Mozilla Firefox/firefox.exe",'http://10.0.1.20:88/',true,false,-1);
//IPS_EXECUTEEX("C:/Program Files (x86)/Mozilla Firefox/firefox.exe","https://127.0.0.1:82/",true,false,1);
/* ab und zu Fehlermeldung Warning: There were no token found for specified session: 1 */

writeLogEvent("Autostart (Ende)");
?>
