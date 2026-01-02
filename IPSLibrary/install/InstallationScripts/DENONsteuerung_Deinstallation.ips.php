<?php

/*

Script inspiriertvon www.raketenschnecke
Modifiziert auf IPS Library und kleine Anpassungen von Wolfgang Joebstl

Deinstallation



*/

$debug=true;

//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('DENONsteuerung',$repository);
	}

IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");
	
$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

echo "\nKernelversion : ".IPS_GetKernelVersion()."\n";
$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
echo "IPS Version     : ".$ergebnis."\n";
$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
echo "Status          : ".$ergebnis."\n";
$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
echo "IPSModulManager Version : ".$ergebnis."\n";


$installedModules = $moduleManager->GetInstalledModules();
if (isset ($installedModules["DENONsteuerung"]) )
	{
	print_r($installedModules);
	echo "\n";
	echo "Modul DENONsteuerung ist noch installiert.\n";
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('DENONsteuerung');
	echo "DENONsteuerung Version : ".$ergebnis."\n";

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	echo "\n";
	if ( file_exists(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php" ) && !($debug) )
		{
		//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
        IPSUtils_Include ("DENONsteuerung_Configuration.inc.php","IPSLibrary::config::modules::DENONsteuerung");
		echo "DENONsteuerung config File noch verfügbar.\n";
		$configuration=Denon_Configuration();
		foreach ($configuration as $Denon => $config)
			{
			if ($config["TYPE"]=="Denon")
				{
				$DENON_CS_ID = @IPS_GetObjectIDByName($config['INSTANZ']." Client Socket", 0);
				if ($DENON_CS_ID === false)
					{
					echo "DENON Client Socket \"".$config['INSTANZ']." Client Socket"."\" bereits geloescht.\n";
					}
				else
					{
					echo "DENON Client Socket \"".$config['INSTANZ']." Client Socket"."\" nun loeschen.\n";
					if ($debug==false)
						{			
   					IPS_DeleteInstance($DENON_CS_ID);
						}
					}

				$DENON_Cu_ID = @IPS_GetObjectIDByName($config['INSTANZ']." Cutter", 0);
				if ($DENON_Cu_ID == false)
					{
					echo "DENON Cutter \"".$config['INSTANZ']." Cutter"."\" bereits geloescht.\n";
					}
				else
					{
					echo "DENON Cutter \"".$config['INSTANZ']." Cutter"."\" nun loeschen.\n";
					if ($debug==false)
						{			
   					IPS_DeleteInstance($DENON_Cu_ID);
						}
					}

				$DENON_RegVar_ID = @IPS_GetObjectIDByName($config['INSTANZ']." Register Variable", $DENON_Cu_ID);
				if ($DENON_RegVar_ID == false)
					{
					echo "DENON Register Variable \"".$config['INSTANZ']." Register Variable"."\" bereits geloescht.\n";
					}
				else
					{
					echo "DENON Register Variable \"".$config['INSTANZ']." Register Variable"."\" nun loeschen.\n";
					if ($debug==false)
						{			
						IPS_DeleteInstance($DENON_RegVar_ID);
						}
					}
				}
			
			}		// ende foreach
		if ($debug==false)
			{	
			$moduleDENON = new IPSModuleManager('DENONSteuerung');
			$moduleDENON->DeleteModule();	
			}
		} // ende file_exists
	else
		{
		echo "DENONsteuerung config File nicht mehr verfügbar oder Debug Modus.\n";
		$serialPortIDs = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
		echo "\nAlle Seriellen Ports auflisten:\n";
		foreach ($serialPortIDs as $num => $serialPort)
			{
			echo "  Serial Port ".$num." mit OID ".$serialPort." und Bezeichnung ".IPS_GetName($serialPort)."\n";
			}
		echo "\nAlle Splitter Instanzen auflisten:\n";
		$alleInstanzen = IPS_GetInstanceListByModuleType(1); // nur Splitter Instanzen auflisten
		foreach ($alleInstanzen as $instanz)
			{
			$datainstanz=IPS_GetInstance($instanz);
			echo " ".$instanz." Name : ".IPS_GetName($instanz)."\n";
			}
		}
		echo "\nAlle Cutter Instanzen auflisten:\n";		
		$cutterIDs = IPS_GetInstanceListByModuleID("{AC6C6E74-C797-40B3-BA82-F135D941D1A2}");
		foreach ($cutterIDs as $num => $cutter)
			{
			echo "  Cutter ".$num." mit OID ".$cutter." und Bezeichnung ".IPS_GetName($cutter)."\n";
			}
		$clientSocketID = IPS_GetInstanceListByModuleID("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
		echo "\nAlle Client Sockets auflisten:\n";
		foreach ($clientSocketID as $num => $clientSocket)
			{
			$name=IPS_GetName($clientSocket);
			echo "  Client Socket ".$num." mit OID ".$clientSocket." und Bezeichnung ".$name."\n";
			}
		

	} // ende modul exists

?>