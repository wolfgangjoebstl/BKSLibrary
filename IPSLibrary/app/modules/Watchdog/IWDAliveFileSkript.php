<?

/***************************************************************************************
 * Dieses Skript erstellt die Alive-Datei zur Übermittlung der Alive-Meldung an IPSWatchDog per Datei
 * die Datei heisst alive.ips und beinhaltet das aktuelle Datum und Zeit und wird vom Watchdog Programm regelmaessig geloescht.
 * Das Watchdog Programm schreibt einen Fehler wenn die datei nicht rgelmaessig erzeugt wird.
 *
 *
 ********************************************************************************************/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');


	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Watchdog',$repository);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');	
	//echo "\nIWDAliveFileSkript : Eigenen Logspeicher für Watchdog und OperationCenter vorbereiten.\n";
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_Watchdog=new Logging("C:\Scripts\Log_Watchdog.csv",$input);

	if (isset ($installedModules["OperationCenter"]))
	   {
		//echo "Logspeicher für OperationCenter mitnutzen.\n";
		$moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
		$CategoryIdDataOC     = $moduleManagerOC->GetModuleCategoryID('data');
		$categoryId_NachrichtenOC    = CreateCategory('Nachrichtenverlauf',   $CategoryIdDataOC, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenOC, 0, "",null,null,""  );
		$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
		}

	//Hier wird der Dateiname der Alive-Datei festgelegt.
	// Er ist standardmäßig auf "alive.ips" gesetzt und muss, falls geändert, im
	// Setup von IPSWatchDog angepasst werden!
	
	$DateiName = IPS_GetKernelDir().'alive.ips';


	$inhalt = date("d.m.y - H:i");

	//Datei vorhanden?
	$dateifehlt = !file_exists($DateiName);

	//falls die Datei fehlt, neu anlegen
	if ($dateifehlt)
		{
		echo "Alive Datei fehlt, neue anlegen.\n";
		$inhalt = date("d.m.y - H:i");
		$datei=fopen ($DateiName,"w");
		fwrite ($datei, $inhalt);
		fclose($datei);
		}

	/* parallel auch noch einen Absturz mitueberwachen, geschieht mit Datei monitor.ips */

	$DateiNameSelfMonitor = IPS_GetKernelDir().'monitor.ips';
	$dateifehlt = !file_exists($DateiNameSelfMonitor);
	$inhalt_unix = date("l, d-M-Y H:i:s T");
	$result_num=0;
	
	//falls die Datei fehlt, neu anlegen
	if ($dateifehlt)
		{
		echo "Monitor Datei fehlt, neue anlegen.\n";
		$datei=fopen ($DateiNameSelfMonitor,"w");
		fwrite ($datei, $inhalt_unix);
		fclose($datei);
		}
	else
	   {
		$datei3=fopen ($DateiNameSelfMonitor,"r");
		$result = fread ($datei3, 100);
		fclose($datei3);
		$datei2=fopen ($DateiNameSelfMonitor,"w");
		fwrite ($datei2, $inhalt_unix);
		fclose($datei2);
		$result_num=time()-strtotime($result);		
		//echo "Monitor Datei vorhanden, Wert ist : ".$result." (".strtotime($result).") ".$result_num." Sekunden seit dem letzten Mal vergangen\n";
		}
	if ($result_num > 60)
	   {
		echo "Ergebnis \"".$result."\" ".$result_num." Sekunden\n";
    	if (isset ($installedModules["OperationCenter"]))
		   {
			echo "Logspeicher für OperationCenter mitnutzen.\n";
			$log_OperationCenter->LogMessage('Watchdog seit '.time2string($result_num).' ausgefallen');
			$log_OperationCenter->LogNachrichten('Watchdog seit '.time2string($result_num).' ausgefallen');
			
			$logFile 							= IPS_GetKernelDir() . "logs\logfile.log";  	// Pfad- und File-Angabe (Standard: "logs\logfile.log") 
 
			// Logfile auslesen +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			// letzte Eintraege wegspeichern

			$f = fopen($logFile, "r");
			$ln  = 0;
			$lni = 0;
	
			while ($line= fgets ($f))
				{
	   		++$ln;
				++$lni;
				if ($line===FALSE)
					{
					print ("FALSE\n");
					}
	   		else
					{
					$temp[] = $line;
				   }
				if ($lni>1000)
					{
         		$temp = array_slice($temp, -500); // Array auf die jüngsten 21 Datensätze eingrenzen
					$lni=0;
					}			
				}
			fclose ($f); 
			echo "Es wurden insgesamt ".$ln." Zeilen bearbeitet.\n";

			$datei2=fopen (IPS_GetKernelDir()."logs/".date("YmdHis")."crash.log","w");
			foreach ($temp as $line)
				{
				echo $line;
				fwrite ($datei2, $line);
				}
			fclose($datei2);   			
			}
		}

function time2string($timeline) 
	{
	$periods = array('day' => 86400, 'hour' => 3600, 'minute' => 60, 'second' => 1);
	$ret="";
	foreach($periods AS $name => $seconds)
		{
		$num = floor($timeline / $seconds);
      $timeline -= ($num * $seconds);
      $ret .= $num.' '.$name.(($num > 1) ? 's' : '').' ';
    	}
	return trim($ret);
}


		
?>