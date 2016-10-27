<?
// Dieses Skript erstellt die Alive-Datei zur Übermittlung der Alive-Meldung
// an IPSWatchDog per Datei

// Hier wird der Dateiname der Alive-Datei festgelegt.
// Er ist standardmäßig auf "alive.ips" gesetzt und muss, falls geändert, im
// Setup von IPSWatchDog angepasst werden!

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	$moduleManager = new IPSModuleManager('Watchdog',$repository);
	$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();

	$DateiName = IPS_GetKernelDir().'alive.ips';
	$DateiNameSelfMonitor = IPS_GetKernelDir().'monitor.ips';
	$result="";

	$inhalt = date("d.m.y - H:i");
	$inhalt_unix = date("l, d-M-Y H:i:s T");

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

	$dateifehlt = !file_exists($DateiNameSelfMonitor);

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
		}
	$result_num=time()-strtotime($result);
	if ($result_num > 60)
	   {
		echo "Ergebnis \"".$result."\" ".$result_num." Sekunden\n";
    	if (isset ($installedModules["OperationCenter"]))
		   {
			echo "Logspeicher für OperationCenter mitnutzen.\n";
			$moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
			$CategoryIdDataOC     = $moduleManager->GetModuleCategoryID('data');
			$categoryId_NachrichtenOC    = CreateCategory('Nachrichtenverlauf',   $CategoryIdDataOC, 20);
			$input = CreateVariable("Nachricht_Input",3,$categoryId_NachrichtenOC, 0, "",null,null,""  );
			$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

			$log_OperationCenter->LogMessage('Watchdog seit '.$result_num.' Sekunden ausgefallen');
			$log_OperationCenter->LogNachrichten('Watchdog seit '.$result_num.' Sekunden ausgefallen');
			}
		}


		
?>