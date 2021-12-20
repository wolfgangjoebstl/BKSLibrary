<?


    //Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    //include_once IPS_GetKernelDir().'scripts\\IPSLibrary\\app\\core\\IPSUtils\\IPSUtils.inc.php';   
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('IPSUtils.inc.php', 'IPSLibrary::app::core::IPSUtils');
 
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
   	IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");
   
   // max. Scriptlaufzeit definieren
    ini_set('max_execution_time', 800);
    $startexec=microtime(true);
    echo "Abgelaufene Zeit : ".exectime($startexec)." Sek. Max Scripttime is 800 Sek \n";
    $debug=false;

    $dosOps = new dosOps();
    $verzeichnis = "/ProgramData/Symcon/scripts/IPSLibrary/config/";
    $ls=$dosOps->readdirToArray($verzeichnis);
    if ($ls===false) echo "********Fehler Verzeichnis $verzeichnis nicht vorhanden.\n";
    else 
        {
        $filename="KnownRepositories.ini";
        $available=$dosOps->fileAvailable($filename,$verzeichnis);
        if ($available) 
            {
            if ($debug) echo "KnownRepositories.ini ist verfügbar. Inhalt ist:\n";
            $handle1=fopen($verzeichnis.$filename,"r");
            $file="";
            while (($result=fgets($handle1)) !== false) 
                {
                $file .= $result;
                //echo  "   $result";
                }
            fclose($handle1);
            if ($debug) echo "$file\n----------\n";
            $repo = strpos($file,"wolfgang");
            //echo "Repo is on $repo.\n";
            if ($repo===false) echo "********Fehler KnownRepositories.ini hat keine Refrenz auf https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/.\n";
            else echo "OK, KnownRepositories.ini hat eine Refrenz auf https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/.\n";
            }
        else echo "********Fehler KnownRepositories.ini ist NICHT verfügbar.\n";
        //print_R($ls);
        }

	// Repository
	$repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';

	$moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);

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
		$inst_modules = "Verfügbare Module und die installierte Version :\n\n";
		$inst_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";
	
		$upd_modules = "Module die upgedated werden müssen und die installierte Version :\n\n";
		$upd_modules.= "Modulname                  Version    Status/inst.Version         Beschreibung\n";
	
        $availableRepositories = $moduleManager->VersionHandler()->GetKnownRepositories();
        echo "Verfügbare Repositories: \n";
        print_r($availableRepositories);
        echo "   Ende.\n";

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
					$upd_modules .=  str_pad($module,26)." ".str_pad($infos['Version'],10)." ".str_pad($infos['CurrentVersion'],10)."   ".$infos['Description']."\n";
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
		echo "\n\n".$upd_modules;
		}	
	
	//print_r($loadfromrepository);
	



?>