<?

/*

Script von www.raketenschnecke

Modifiziert auf IPS Library und kleine Anpassungen von Wolfgang Joebstl


Funktionen:
	*legt "DENON Client Socket" Instanz an und konfiguriert diese
		(bereits existente Instanz wird nur neu konfiguriert)
	*legt "DENON Cutter" Instanz an und konfiguriert diese
		(bereits existente Instanz wird nur neu konfiguriert)
	*legt "DENON Register Variable" Instanz an und konfiguriert diese
		(bereits existente Instanz wird nur neu konfiguriert)
	*legt Kategorie "DENON" im Root-Ordner an
	*legt Kategorie "DENON Webfront" in Kategorie "DENON" an
	*legt Kategorie "DENON Scripts" in Kategorie "DENON" an
	*legt Scripte "DENON.Install_Library.ips.php", "DENON.ActionScript.ips.php"
		und"DENON.Functions.ips.php in Kategori "DENON Scritpe" an
	* legt Script "DENON.CommandReceiver.ips.php" unterhalb der RegisterVariablen "Denon Register Variable" an
	* legt Dummy-Instanzen "Main Zone", "Zone2", "Zone2", "Steuerung", "Display" in Kategorie "DENON" an (bestehende Instanzen werden nicht gelöscht)
	* legt Dummy-Instanzen "Main Zone", "Zone2", "Zone2", "Steuerung", "Display" in Kategorie "DENON Webfront" an (bestehende Instanzen werden nicht gelöscht)

Erst-Installation:
	*dieses Script in IPS hochladen
	*dieses Script ausführen

Installation (erneut/Update)
	* bereits bestehende Kategorieen, Instanzen, Links und Variablen müssen vor Ausführung des Sripts nicht
		zwingend gelöscht werden (bestehende Kategorieen werden nicht verändert)
	* existierende Variablenprofile werden nicht gelöscht (sollen diese gelöscht werden
		bitte vor Ausführung des DENON.Installers das Script DENON.ProfileCleaner ausführen
		(Script liegt im Bojektbaum unter "DENON/DENON Scripts")
	*bestehende Scripte (vorherige Verisionen) werden gelöscht und neu angelegt
*/

IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ("DENONsteuerung_Configuration.inc.php","IPSLibrary::config::modules::DENONsteuerung");
IPSUtils_Include ("DENONsteuerung.Library.inc.php","IPSLibrary::app::modules::DENONsteuerung");

    IPSUtils_Include ('EvaluateHardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
    IPSUtils_Include ('Hardware_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');

    echo "\nAlle installierten Discovery Instances mit zugehörigem Modul und Library:\n";
	$modulhandling = new ModuleHandling();	                	            // in AllgemeineDefinitionen, alles rund um Bibliotheken, Module und Librariestrue bedeutet mit Debug
    $topologyLibrary = new TopologyLibraryManagement();                     // in EvaluateHardware Library, neue Form des Topology Managements

    $discovery = $modulhandling->getDiscovery();
    $hardware = $topologyLibrary->get_HardwareList($discovery);
    print_r($hardware["DenonAVR"]);

    $hardwareDenon = new HardwareDenonAVR();
    $socketID = $hardwareDenon->getSocketID();
    $validModule = @IPS_GetModule($socketID)["ModuleName"];
    echo "$validModule \n";
    $sockets=$modulhandling->getInstances($socketID);
    print_R($sockets);
    $devices=$hardwareDenon->getDeviceIDInstances();
    print_R($devices);
    
	echo "\n==========================================\n\n";

	echo "Die Module von der Bibliothek \"SymconHUE\" ausgeben : \n";
	//$modulhandling->printModules('{128B5E62-33BB-40A7-923C-A9AB903F8272}');
	$modulhandling->printModules("SymconHUE");

	echo "\nDie Module von der Bibliothek \"Denon/Marantz AV Receiver\" ausgeben : \n";
	//$modulhandling->printModules('{128B5E62-33BB-40A7-923C-A9AB903F8272}');
	$modulhandling->printModules("Denon/Marantz AV Receiver");

	echo "\n";
	echo "Hier die Instanzen des Moduls DenonAVRHTTP:\n";
	$modulhandling->printInstances("DenonAVRHTTP");
	//$modulhandling->printInstances("HUEBridge");

	echo "\n==========================================\n\n";

$installDENON = new installDENON();

echo "\n";
echo "Denon Konfiguration:\n";
$configuration=Denon_Configuration();
print_r($configuration);


foreach ($configuration as $Denon => $config)
	{
	switch ($config["TYPE"])
		{
		case "Denon":
			//$installDENON->setupDENON($Denon,$config);
			break;
		case "SamsungTV":
			$installDENON->setupSamsung($Denon,$config);
			break;
		case "HarmonyHub":
			$installDENON->setupHarmony($Denon,$config);
			break;
		case "Netplayer":
			break;			
		default:
			echo "UNKNOWN TYPE detected.\n";
			break;	
		}
   }  /* ende foreach Denon Device */

echo "\nInstallation DENONsteuerung abgeschlossen\n\n";


/****************************************************************************************************************/
/****************************************************************************************************************/
	
?>