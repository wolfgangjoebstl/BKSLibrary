<?php

/*

bringt alle Module egal von welchem Repository auf den letzten Stand

*/

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(800);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme
    $startexec=microtime(true);

	IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
    IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");
	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	
	// Repository
	$repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
	$repositoryJW="https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/";

	$moduleManager = new ModuleManagerIPS7('', '', sys_get_temp_dir(), true);

	$versionHandler = $moduleManager->VersionHandler();
	$versionHandler->BuildKnownModules();
	$knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
	if ( (isset($knownModules["AllgemeineDefinitionen"])) == false) 
		{
        echo "\n\n*********Fehler: Verzeichnis /ProgramData/Symcon/scripts/IPSLibrary/config\n";
		echo "Eigenes Repository in KnownRepositories übernehmen,\n";
        echo "es fehlt der Eintrag Repository[]=https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/  \n";
		}
    else
        {

        $installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
        $inst_modules = "\n=====================================================================\n";
        $inst_modules.= "Verfügbare Module und die installierte Version :\n\n";
        $inst_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";
        $loadfromrepository=array();
            
        foreach ($knownModules as $module=>$data)
            {
            $infos   = $moduleManager->GetModuleInfos($module);
            $inst_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10);
            if (array_key_exists($module, $installedModules))
                {
                //$html .= "installiert als ".str_pad($installedModules[$module],10)."   ";
                $inst_modules .= "installiert als ".str_pad($infos['CurrentVersion'],10)."   ";
                if ($infos['Version']!=$infos['CurrentVersion'])
                    {
                    $inst_modules .= "***";
                    $loadfromrepository[]=$module;
                    }
                }
            else
                {
                $inst_modules .= "nicht installiert            ";
            }
            $inst_modules .=  $infos['Description']."\n";
            }

        echo $inst_modules;
        echo "\n";
        
        $Heute=time();
        //$HeuteString=date("jnY_Hi",$Heute);
        $HeuteString=date("jnY",$Heute);
        echo "Heute  Datum ".$HeuteString."\n";
        
        if (isset ($installedModules["OperationCenter"]))
            {
            $log_Install=new Logging("C:\Scripts\Install\Install".$HeuteString.".csv");
            } 
        
    /*
        echo "-----------------------------------------------\n";
        echo "Update all Modules from Repository : ".$repositoryJW."\n";
    $moduleManager = new IPSModuleManager('',$repositoryJW);
    $moduleManager->UpdateAllModules();
    */

    print_r($loadfromrepository);

        /* dieser Block geht manchmal nicht da er direkt auf das Brownson Repository geht */
    /*
    $moduleManager = new IPSModuleManager();
    $moduleManager->UpdateAllModules();
    */
        $ende=true; 
        $log=array();
        $count=0; $countmax=1;
        
        foreach ($loadfromrepository as $upd_module)
        {
            $useRepository=$knownModules[$upd_module]['Repository'];
            echo "\n\n";
            echo "-----------------------------------------------------------------------------------------------------------------------------\n";
            echo "Update Module ".$upd_module." from Repository : ".$useRepository."    Aktuell vergangene Zeit : ".exectime($startexec)." Sekunden\n";
            echo "-----------------------------------------------------------------------------------------------------------------------------\n";
            echo "\n\n";		
            if (isset ($installedModules["OperationCenter"])) $log_Install->LogMessage("Update Module ".$upd_module." from Repository : ".$useRepository."    Aktuell vergangene Zeit : ".exectime($startexec)." Sekunden");
            $LBG_module = new ModuleManagerIPS7($upd_module,$useRepository);
            echo "-----------------------------------------------------------------------------------------------------------------------------\n";
            $LBG_module->LoadModule();
            $log[]="Load Module ".$upd_module." completed    Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden";   
            echo "-----------------------------------------------------------------------------------------------------------------------------\n";
            $LBG_module->InstallModule(true);
            $log[]="Install Module ".$upd_module." completed    Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden";
            if ($count++>=$countmax) 
                {
                echo "\nUpdate noch nicht abgeschlossen, noch einmal aufrufen.\n";
                if (isset ($installedModules["OperationCenter"])) $log_Install->LogMessage("Update noch nicht abgeschlossen, noch einmal aufrufen.");
                break;
                }           
            if (exectime($startexec) > 30 )
                {
                print_r($log);                    
                echo "\nUpdate noch nicht abgeschlossen, noch einmal aufrufen.\n";
                if (isset ($installedModules["OperationCenter"])) $log_Install->LogMessage("Update noch nicht abgeschlossen, noch einmal aufrufen.");
                $ende=false;
                break;
                }
            else if (isset ($installedModules["OperationCenter"])) $log_Install->LogMessage("Update $upd_module abgeschlossen.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden.");
                {
                }
                            
            }

        if ($ende==true) 
            {
            print_r($log);                
            echo "\nUpdate abgeschlossen.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";  
            if (isset ($installedModules["OperationCenter"])) $log_Install->LogMessage("Update abgeschlossen.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden");		
            }
        }

//$BackupCenter->updateSummaryofBackupFile(); 


?>