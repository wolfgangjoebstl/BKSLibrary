<?php


	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */
	 
/*********************************************************************************************/
/*********************************************************************************************/
/*                                                                                           */
/*                              Functions, Klassendefinitionen                               */
/*                                                                                           
 * Zusammenfassung nutzvoller Funktionen in einer Klasse
 *      __construct
 *      setConfiguration
 *      getConfiguration
 *      getActiveProcesses
 *      checkAutostartProgram
 *
 */

/*********************************************************************************************/
/*********************************************************************************************
 *
 * Konfigurationsverwaltung
 *
		   "Software" => array(
                "Watchdog"  =>  array (
                        "Directory"        		=> 'C:/IP-Symcon/',
                        "Autostart"             => 'Yes',
                                        ),
                "VMware"  =>  array (
                        "Directory"        		=> 'C:/Program Files (x86)/VMware/VMware Player/',
                        "DirFiles"        		=> 'c:/VirtualMachines/Windows 10 x64 Initial/',
                        "FileName"              => 'Windows 10 x64.vmx',
                        "Autostart"             => 'Yes',
                                        ),
                "iTunes"  =>  array (
                    "Directory"        		=> 'C:/Program Files/iTunes/',
                    "Autostart"             => 'Yes',
                    "SoapIP"                => '10.0.0.34',
                                        ),	
                "Selenium"  =>  array (
                    "Directory"        		=> 'C:/Scripts/Selenium/',
                    "Autostart"             => 'Yes',
                    "Execute"                => 'selenium-server-standalone-3.141.59.jar',
                                        ),                                        										
                "Firefox"  =>  array (
                    "Directory"        		=> 'C:/Program Files (x86)/Mozilla Firefox/',
                    "Url"                   => ['http://10.0.0.34:3777','http://10.0.0.124:3777'],
                    "Autostart"             => 'Yes',
                                        ),
				       ),
			"RemoteShutDown"     => array(
				"Server"  =>	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.0.124:3777/api/',
				                  ),
            "WatchDogDirectory" 			=> "/process/", 	// Verzeichnis für Webergebnisse 
							); 
 *
 */

class watchDog
    {

    protected $configuration;
    protected $sysOps,$dosOps;

    function __construct()
        {
        $this->configuration=$this->setConfiguration();    
        $this->sysOps = new sysOps();    
        $this->dosOps = new dosOps();    
        }

    /* watchDog::setConfiguration
     * überprüfen der Konfiguration 
     */
    function setConfiguration()
        {
        $config=array();
        if ((function_exists("Watchdog_Configuration"))===false) IPSUtils_Include ("Watchdog_Configuration.inc.php","IPSLibrary::config::modules::Watchdog");				
        if (function_exists("Watchdog_Configuration")) $configInput=Watchdog_Configuration();
        else echo "*************Fehler, Watchdog_Configuration.inc.php Konfig File nicht included oder Funktion Watchdog_Configuration() nicht vorhanden. Es wird mit Defaultwerten gearbeitet.\n";

        configfileParser($configInput, $configSoftware, ["Software","SOFTWARE","software"],"Software",null);    
        configfileParser($configInput, $config, ["RemoteShutDown","REMOTESHUTDOWN","remoteshutdown"],"RemoteShutDown",null);                // null Index wird trotzdem übernommen

        configfileParser($configInput, $config, ["WatchDogDirectory","WATCHDOGDIRECTORY","watchdogdirectory"],"WatchDogDirectory","/process/");                // null Index wird trotzdem übernommen
        $this->dosOps = new dosOps();
        $systemDir     = $this->dosOps->getWorkDirectory(); 
        if (strpos($config["WatchDogDirectory"],"C:/Scripts/")===0) $config["WatchDogDirectory"]=substr($config["WatchDogDirectory"],10);      // Workaround für C:/Scripts"
        $config["WatchDogDirectory"] = $this->dosOps->correctDirName($systemDir.$config["WatchDogDirectory"]);
        $this->dosOps->mkdirtree($config["WatchDogDirectory"]);

        /* check Selenium */
        configfileParser($configSoftware["Software"], $configSelenium, ["Selenium","SELENIUM","selenium"],"Selenium",null);    
        //print_r($configSelenium);
        configfileParser($configSelenium["Selenium"], $config["Software"]["Selenium"], ["Directory","DIRECTORY","directory"],"Directory","/Selenium/");    
        configfileParser($configSelenium["Selenium"], $config["Software"]["Selenium"], ["Autostart","AUTOSTART","autostart"],"Autostart","no");    
        configfileParser($configSelenium["Selenium"], $config["Software"]["Selenium"], ["Execute"],"Execute","selenium-server-standalone-3.141.59.jar");    
        if (strpos($config["Software"]["Selenium"]["Directory"],"C:/Scripts/")===0) $config["Software"]["Selenium"]["Directory"]=substr($config["Software"]["Selenium"]["Directory"],10);      // Workaround für C:/Scripts"
        $config["Software"]["Selenium"]["Directory"] = $this->dosOps->correctDirName($systemDir.$config["Software"]["Selenium"]["Directory"]);
        $this->dosOps->mkdirtree($config["Software"]["Selenium"]["Directory"]);

        /* check Firefox */
        configfileParser($configSoftware["Software"], $configFirefox, ["Firefox","FIREFOX","firefox"],"Firefox",null);    
        //print_r($configFirefox);
        configfileParser($configFirefox["Firefox"], $config["Software"]["Firefox"], ["Directory","DIRECTORY","directory"],"Directory","C:/Program Files/Mozilla Firefox/");    
        configfileParser($configFirefox["Firefox"], $config["Software"]["Firefox"], ["Autostart","AUTOSTART","autostart"],"Autostart","no");    
        configfileParser($configFirefox["Firefox"], $config["Software"]["Firefox"], ["Url"],"Url","http://localhost:3777/");    

       /* check Chrome */
        configfileParser($configSoftware["Software"], $configChrome, ["Chrome","CHROME","chrome"],"Chrome",null);           // C:\Program Files (x86)\Google\Chrome\Application
        //print_r($configFirefox);
        configfileParser($configChrome["Chrome"], $config["Software"]["Chrome"], ["Directory","DIRECTORY","directory"],"Directory","C:/Program Files (x86)/Google/Chrome/Application/");    
        configfileParser($configChrome["Chrome"], $config["Software"]["Chrome"], ["Autostart","AUTOSTART","autostart"],"Autostart","no");    
        configfileParser($configChrome["Chrome"], $config["Software"]["Chrome"], ["Url"],"Url","http://localhost:3777/");    

        /* check iTunes */
        configfileParser($configSoftware["Software"], $configTunes, ["iTunes","ITUNES","itunes","Itunes"],"iTunes",null);    
        configfileParser($configTunes["iTunes"], $config["Software"]["iTunes"], ["Directory","DIRECTORY","directory"],"Directory","C:/Program Files/iTunes/");    
        configfileParser($configTunes["iTunes"], $config["Software"]["iTunes"], ["Autostart","AUTOSTART","autostart"],"Autostart","no");    
        configfileParser($configTunes["iTunes"], $config["Software"]["iTunes"], ["SoapIP"],"SoapIP","localhost");    

        /* check VmWare */
        configfileParser($configSoftware["Software"], $configVMware, ["VMware"],"VMware",null);    
        configfileParser($configVMware["VMware"], $config["Software"]["VMware"], ["Directory","DIRECTORY","directory"],"Directory","C:/Program Files (x86)/VMware/VMware Player/");    
        configfileParser($configVMware["VMware"], $config["Software"]["VMware"], ["DirFiles","DIRFILES","dirfiles"],"DirFiles","C:/VirtualMachines/Windows 10 x64 Initial/");    
        configfileParser($configVMware["VMware"], $config["Software"]["VMware"], ["Autostart","AUTOSTART","autostart"],"Autostart","no");    
        configfileParser($configVMware["VMware"], $config["Software"]["VMware"], ["FileName","FILENAME","filename"],"FileName","Windows 10 x64.vmx");    

        /* check Watchdog nicht mehr implementiert */
        
        return ($config);    
        }

    /* watchDog::getConfiguration
     * Ausgabe der Konfiguration 
     */
    function getConfiguration()
        {
        return($this->configuration);
        }

    function getSeleniumConfiguration()
        {
        return($this->configuration["Software"]["Selenium"]);
        }

    /* watchDog::getActiveProcesses
     * die ganze Routine um rauszufinden welche Prozesse gerade laufen
     *
     */
    function getActiveProcesses($debug=false)
        {
        $verzeichnis=$this->configuration["WatchDogDirectory"];
        $unterverzeichnis="";
        $dosOps = new dosOps();        
        $sysOps = new sysOps();

        // hier folgt eine ausführliche Auswertung der Prozesse, besonders der Java Applikationen wie Selenium
        // ohne Selenium kann man diese Auswerung einfach weglassen

        $dosOps->deleteFile($verzeichnis.$unterverzeichnis."username.txt");      // nur einen Eintrag pro Datei
        $dosOps->deleteFile($verzeichnis.$unterverzeichnis."jps.txt");      // nur einen Eintrag pro Datei
        $dosOps->deleteFile($verzeichnis.$unterverzeichnis."tasklist.txt");      // nur einen Eintrag pro Datei
        $dosOps->deleteFile($verzeichnis.$unterverzeichnis."processlist.txt");      // nur einen Eintrag pro Datei
        $dosOps->deleteFile($verzeichnis.$unterverzeichnis."wmic.txt");      // nur einen Eintrag pro Datei

        if ($debug) echo "Aufruf script $verzeichnis$unterverzeichnis"."read_username.bat.\n";
        /*$handle1=fopen($verzeichnis.$unterverzeichnis."read_username.bat","r");
        while (($result=fgets($handle1)) !== false) 
            {
            echo   "   $result";
            }
        fclose($handle1);
        echo "-----------\n";   */
        // ExecuteUserCommand($command,$path,$show=false,$wait=false,$session=-1)
        $sysOps->ExecuteUserCommand($verzeichnis.$unterverzeichnis."read_username.bat","", true, true,-1);
        //IPS_ExecuteEx($verzeichnis.$unterverzeichnis."read_username.bat","", true, true,-1);  /* warten dass fertig, sonst wird alter Wert ausgelesen, aufpassen kann länger dauern */

        $handle3=fopen($verzeichnis.$unterverzeichnis."username.txt","r");
        if ($debug) echo "Username von dem aus IP Symcon zugreift ist : ".fgets($handle3);
        fclose($handle3);

        $file=array();
        $file["Tasklist"] = $verzeichnis.$unterverzeichnis."tasklist.txt";
        $file["Processlist"] = $verzeichnis.$unterverzeichnis."processlist.txt";
        $file["Javalist"] = $verzeichnis.$unterverzeichnis."jps.txt";                           // wichtig für Selenium, darf nur einmal gestartet werden, sonst gibt es einen anderen unbekannten Port
        $file["Wmiclist"] = $verzeichnis.$unterverzeichnis."wmic.txt";                           // wichtig für Selenium, darf nur einmal gestartet werden, sonst gibt es einen anderen unbekannten Port
        
        $processes = $sysOps->getProcessListFull($file,$debug);

        if ($debug) 
            {
            echo "Print Process and Programlist including Java processes:\n";
            print_r($processes);
            echo "-------------------------------------\n";
            }

        return($processes);
        }

    /* watchDog::isSeleniumServer
     * die ganze Routine um rauszufinden welche Prozesse gerade laufen
     *
     */
    public function isSeleniumServer($execDir,$debug=false)
        {
        $result=false;
        $configSeleniumWeb=$this->configuration["Software"]["Selenium"];
        if (strtoupper($configSeleniumWeb["Autostart"])=="YES" )
            {
            if ( ($this->isSeleniumServerAtDir($configSeleniumWeb["Directory"])) == true )
                {                
                return (true);
                }
            else
                {
                echo "No jar file. Do copy ".$configSeleniumWeb["Directory"].$configSeleniumWeb["Execute"]." from Synology Executes.\n";
                if ($this->isSeleniumServerAtDir($execDir))         // an der Quelle ein jar File, dann kopieren
                    {
                    if ($debug) echo "do copy to ".$configSeleniumWeb["Directory"].$configSeleniumWeb["Execute"]."\n";
                    if (!copy($execDir.$configSeleniumWeb["Execute"],$configSeleniumWeb["Directory"].$configSeleniumWeb["Execute"])) echo "failed to copy ".$execDir.$configSeleniumWeb["Execute"]." ...\n";
                    else return (true);
                    }
                }
            }
        return ($result);
        }

    public function isSeleniumServerAtDir($execdir=false)
        {
        if ($execdir===false) $execdir=$this->configuration["Software"]["Selenium"]["Directory"];
        return ($this->dosOps->fileAvailable($this->configuration["Software"]["Selenium"]["Execute"],$execdir));
        }

    /* check ob die Prozesse laufen und ob sie entsprechend Konfiguration neu gestartet werden müssen 
     * Es werden nur die folgenden Programme unterstützt:
     *      Selenium
     *      VMWare
     *      iTunes
     *      Firefox
     */

    function checkAutostartProgram($processesFound=array(),$debug=false)
        {
    	 /* feststellen ob Prozesse schon laufen, dann muessen sie nicht mehr gestartet werden */
        $processStart=array("selenium" => "On","vmplayer" => "On", "iTunes" => "On", "Firefox" => "On", "Chrome" => "On");
        $processStart=$this->sysOps->checkProcess($processStart,$processesFound,$debug);        // true wenn Debug

        /* Extra Checks für Zusatzprogramme */

        if (strtoupper($this->configuration["Software"]["Selenium"]["Autostart"])=="YES" )
            {
            if ( ($this->dosOps->fileAvailable($this->configuration["Software"]["Selenium"]["Execute"],$this->configuration["Software"]["Selenium"]["Directory"])) == false )
                {
                echo "Keine Installation von Java Selenium vorhanden, check for ".$this->configuration["Software"]["Selenium"]["Directory"].$this->configuration["Software"]["Selenium"]["Execute"]."\n";
                IPSLogger_Err(__file__, "watchDogAutoStart::checkAutostartProgram: Keine Installation von Java Selenium vorhanden, check for ".$this->configuration["Software"]["Selenium"]["Directory"].$this->configuration["Software"]["Selenium"]["Execute"]);
                $processStart["selenium"]="Off";
                }
            }
        else
            {
            $processStart["selenium"]="Off";
            }

        if (strtoupper($this->configuration["Software"]["VMware"]["Autostart"])=="YES" )
            {
            if ( ($this->dosOps->fileAvailable("vmplayer.exe",$this->configuration["Software"]["VMware"]["Directory"])) == false )
                {
                echo "Keine Installation von VMware vorhanden, check ".$this->configuration["Software"]["VMware"]["Directory"]."vmplayer.exe.\n";
                IPSLogger_Err(__file__, "watchDogAutoStart::checkAutostartProgram: Keine Installation von VMWare vorhanden, check ".$this->configuration["Software"]["VMware"]["Directory"]."vmplayer.exe");
                $processStart["vmplayer"]="Off";
                }
            if ( ($this->dosOps->fileAvailable("*.vmx",$this->configuration["Software"]["VMware"]["DirFiles"])) == false )
                {
                echo "Keine Images für VMPlayer in ".$this->configuration["Software"]["VMware"]["DirFiles"]." vorhanden.\n";
                IPSLogger_Err(__file__, "watchDogAutoStart::checkAutostartProgram: Keine Installation von VMWare Player Images vorhanden, check here for vmx file ".$this->configuration["Software"]["VMware"]["DirFiles"]);
                $processStart["vmplayer"]="Off";
                }
            }
        else
            {
            $processStart["vmplayer"]="Off";
            }

        if (strtoupper($this->configuration["Software"]["iTunes"]["Autostart"])=="YES" )
            {
            if ( ($this->dosOps->fileAvailable("iTunes.exe",$this->configuration["Software"]["iTunes"]["Directory"])) == false )
                {
                echo "Keine Installation von iTunes in ".$this->configuration["Software"]["iTunes"]["Directory"]."vorhanden.\n";
                $processStart["iTunes"]="Off";
                }
            }
        else
            {
            $processStart["iTunes"]="Off";
            }

        if (strtoupper($this->configuration["Software"]["Firefox"]["Autostart"])=="YES" )
            {
            if ( ($this->dosOps->fileAvailable("firefox.exe",$this->configuration["Software"]["Firefox"]["Directory"])) == false )
                {
                echo "Keine Installation von Firefox in ".$this->configuration["Software"]["Firefox"]["Directory"]." vorhanden.\n";
                $processStart["Firefox"]="Off";
                }
            }
        else
            {
            $processStart["Firefox"]="Off";
            }

        if (strtoupper($this->configuration["Software"]["Chrome"]["Autostart"])=="YES" )
            {
            if ( ($this->dosOps->fileAvailable("chrome.exe",$this->configuration["Software"]["Chrome"]["Directory"])) == false )
                {
                echo "Keine Installation von Chrome in ".$this->configuration["Software"]["Chrome"]["Directory"]." vorhanden.\n";
                $processStart["Chrome"]="Off";
                }
            }
        else
            {
            $processStart["Chrome"]="Off";
            }

        return ($processStart);            
        }

    }

/* same as class watchDog
 * but make it more easy to extend
 */

class watchDogAutoStart extends watchDog
    {

    function createCmdFileActiveProcesses($verzeichnis)
        {
        //echo "Write check username and active processes including java to script ".$verzeichnis."read_username.bat\n";
        $handle2=fopen($verzeichnis."read_username.bat","w");
        fwrite($handle2,'cd '.$verzeichnis."\r\n");
        fwrite($handle2,'echo %username% >>username.txt'."\r\n");
        fwrite($handle2,'wmic process list >>processlist.txt'."\r\n");                          // sehr aufwendige Darstellung der aktiven Prozesse
        fwrite($handle2,'tasklist >>tasklist.txt'."\r\n");
        fwrite($handle2,'jps >>jps.txt'."\r\n");  
        //fwrite($handle2,'wmic Path win32_process Where "CommandLine Like \'%selenium%\'" >>wmic.txt');
        fwrite($handle2,'wmic Path win32_process >>wmic.txt'."\r\n");
        //fwrite($handle2,"pause\r\n");
        fclose($handle2);
        }

    function createCmdFileSelfShutdown($verzeichnis)
        {
        //echo "Write Shutdown procedure to script ".$verzeichnis."self_shutdown.bat\n";
        $handle2=fopen($verzeichnis."self_shutdown.bat","w");
        fwrite($handle2,'net stop IPSServer'."\r\n");
        fwrite($handle2,'shutdown /s /t 150 /c "Es erfolgt ein Shutdown in 2 Minuten'."\r\n");
        fwrite($handle2,'pause'."\r\n");
        fwrite($handle2,'shutdown /a'."\r\n");
        fclose($handle2);
        }

    function createCmdFileSelfRestart($verzeichnis)
        {
        //echo "Write Self Restart procedure to script ".$verzeichnis."self_restart.bat\n";
        $handle2=fopen($verzeichnis."self_restart.bat","w");
        fwrite($handle2,'net stop IPSServer'."\r\n");
        fwrite($handle2,'shutdown /r /t 150 /c "Es erfolgt ein Restart in 2 Minuten'."\r\n");
        fwrite($handle2,'pause'."\r\n");
        fwrite($handle2,'shutdown /a'."\r\n");
        fclose($handle2);
        }



    }

/* class handles about Slenium Chromedriver Update
 * uses config from this class
 */

class seleniumChromedriverUpdate extends watchDog
    {

    var $debug;
    var $ipsOps,$dosOps;                 // class zur sofortigen verwendung
    var $selDir;
    var $selDirContent = array();
    var $filename;                      // Filename des neuen Chromdrivers

	public function __construct($debug=false)
		{
        if ($debug) echo "class SeleniumChromedriverUpdate, Construct Parent class watchDog.\n";
        $this->debug=$debug;
        $this->ipsOps = new ipsOps();   
        $this->dosOps = new dosOps();        
        parent::__construct();                       // sonst sind die Config Variablen noch nicht eingelesen
        }

    function getprocessDir()
        {
        $configWatchdog = $this->getConfiguration();            
        $processDir=$this->dosOps->correctDirName($configWatchdog["WatchDogDirectory"]);
        //echo "Watchdog Directory : $processDir\n";            
        return ($processDir);
        }

    function createCmdFileStartSelenium($verzeichnis)
        {
        //echo "Write Selenium Startup Script to ".$verzeichnis."start_Selenium.bat\n";
        $configWD = $this->getConfiguration();
        if ( ((isset($configWD["Software"]["Selenium"]["Directory"])) && (isset($configWD["Software"]["Selenium"]["Execute"]))) === false) return (false);
        $handle2=fopen($verzeichnis."start_Selenium.bat","w");
        fwrite($handle2,'# written '.date("H:m:i d.m.Y")."\r\n");
        fwrite($handle2,'cd '.$configWD["Software"]["Selenium"]["Directory"]."\r\n");
        fwrite($handle2,'java -jar '.$configWD["Software"]["Selenium"]["Execute"]."\r\n");
        /*  cd C:\Scripts\Selenium\ 
            java -jar selenium-server-standalone-3.141.59.jar
            pause       */
        fwrite($handle2,'pause'."\r\n");
        fclose($handle2);
        return (true);
        }

  function createCmdFileStoppSelenium($verzeichnis=false,$debug=false)
        {
        if ($verzeichnis===false) $processDir = $this->getprocessDir();
        else $processDir=$verzeichnis;
        if ($debug) echo "Stopp Selenium. Write cmd file in $processDir.\n";
        $handle2=fopen($processDir."stopp_Selenium.bat","w");
        fwrite($handle2,"wmic process where \"commandline like '%%java%%'\" delete\r\n");
        fclose($handle2); 
        }

    /* setzt selDir und ermittelt selDirContent
     */
    function getSeleniumDirectoryContent()
        {
        $debug=$this->debug;
        $configWatchdog = $this->getConfiguration();            
        if (isset($configWatchdog["Software"]["Selenium"]["Directory"]))
            {
            $this->selDir=$this->dosOps->correctDirName($configWatchdog["Software"]["Selenium"]["Directory"]);
            if ($debug) 
                {
                echo "Watchdog config defines where Selenium is operating: ".$this->selDir." \n";
                $this->dosOps->writeDirStat($this->selDir);                    // Ausgabe eines Verzeichnis 
                }
            $this->selDirContent = $this->dosOps->readdirToArray($this->selDir);                   // Inhalt Verzeichnis als Array
            return ($this->selDirContent);
            }
        return (false);
        }

    /* setzt selDir und ermittelt selDirContent
     */
    function getSeleniumDirectory()
        {
        $debug=$this->debug;
        $configWatchdog = $this->getConfiguration();            
        if (isset($configWatchdog["Software"]["Selenium"]["Directory"]))
            {
            $this->selDir=$this->dosOps->correctDirName($configWatchdog["Software"]["Selenium"]["Directory"]);
            return ($this->selDir);
            }
        return (false);
        }

    /* check Chromedriver Version
     * anhand des array version wird der Index ausgewählt
     */
    function identifyFileByVersion($file,$version, $debugInput=false)
        {
        $result=false;
        $debug=$this->debug || $debugInput;
        if ($debug) echo "identifyFileByVersion($file,..). Input array to identify version has ".count($version)." entries.\n";
        if ($this->selDirContent)
            {
            $found = $this->dosOps->findfiles($this->selDirContent,$file);
            if ($found)
                {
                $size=filesize($this->selDir.$file);
                if ($debug) echo "   Actual chromedriver.exe found. Compare versions fo find size $size.\n";   
                foreach ($version as $index => $info)
                    {
                    if ($info["Size"]==$size) 
                        {
                        if ($debug) echo "   Found $index, successful.\n";
                        return ($index); 
                        }
                    }
                if ($debug) echo "File with $size not found in inventory.\n";
                }
            else echo "File $file not found.\n";
            }
        else echo "Inhalt vom selDir nicht vorhanden selDirContent.\n";
        if ($debug) echo "   Not found, version maybe older or not latest of its kind.\n";
        return ($result); 
        }

    /* ein Script aufrufen mit dem Selenium gestoppt wird. Üblicherweise werden dabei alle java processe gekillt
     */
    function stoppSelenium($debug=false)
        {
        $status=false;
        $processDir = $this->getprocessDir();
        $command = $processDir."stopp_Selenium.bat";
        if ($debug) 
            {
            echo "Stopp Selenium. Start cmd file stopp_Selenium.bat in $processDir.\n";
            $this->dosOps->readFile($processDir."stopp_Selenium.bat");
            }
        if ($debug<2) $status = $this->sysOps->ExecuteUserCommand($command,"",true,false);                   // false do not show, true wait wir brauchen es aber andersrum, sonst bleibt das script hängen
        return ($status);
        }


    /* ein Script aufrufen mit dem Selenium gestartet wird. 
     * Wenn Debug 2 dann das Exe nicht aufrufen, erweiterte Fehlersuche
     */
    function startSelenium($debug=false)
        {
        $status=false;
        $processDir = $this->getprocessDir();
        $command = $processDir."start_Selenium.bat";
        if ($debug) 
            {
            echo "Start Selenium. Execute cmd file start_Selenium.bat in $processDir.\n";
            $this->dosOps->readFile($processDir."start_Selenium.bat");
            echo "Command File: $command.\n";
            }
        if ($debug<2)       // true ist nicht kleiner zwei, muss 1 sein
            {
            echo "execute show and do not wait\n";
            $status = $this->sysOps->ExecuteUserCommand($command,"",true,false,-1,$debug);                   // false do not show true wait, heoir andersrum da selenium offen bleibt  $command,$parameter="",$show=true,$wait=false,$session=-1,$debug=false
            }
        return ($status);
        }

    /* seleniumChromedriverUpdate::copyChromeDriver
     * return status:
     *      false   Fehler beim Abarbeiten des Commandfiles
     *      true    Commandfile abgearbeitet
     *      101     kein Target Dir
     *      102     Target bereits vorhanden
     *      103     kein Target File vorhanden
     */
    function copyChromeDriver($sourceFile,$targetDir,$debug=false)
        {
        $targetDir = $this->dosOps->correctDirName($targetDir);                 // TargetDir muss Windows naming haben wenn Windows, also Backslash Dir Names
        $targetDir = $this->dosOps->convertDirName($targetDir,true);            // immer DOS style, Windows command kann nur backslash
        $path=pathinfo($sourceFile);                // sourceFile aufdroeseln
        $filename=$path['basename'];
        $sourceDir=$this->dosOps->correctDirName($path['dirname']);
        $targetDir=$this->dosOps->correctDirName($targetDir,$debug);                      // true debug

        if ($debug) echo "copyChromeDriver($sourceFile,$targetDir) aufgerufen.\n";
        if ($targetDir===false) return (101);
        if (file_exists($targetDir.$filename)===false)
            {
            //$selDir = 'C:\\Scripts\\Selenium\\';
            $processDir = $this->getprocessDir();
            if ($debug) echo "   write copy routine to script copyChromeDriver.bat, located at $processDir.\n   Source Dir is $sourceDir. Target Dir is $targetDir. Fileneame is $filename.\n";
            $handle2=fopen($processDir."copyChromeDriver.bat","w");
            fwrite($handle2,'cd '.$sourceDir."\r\n");
            fwrite($handle2,'copy '.$filename.' '.$targetDir."\r\n");
            fclose($handle2); 
            $sourceFile = $sourceDir.$filename;
            if ($debug) echo "   Copy Latest ChromeDriver \"$sourceFile\" to Selenium Directory \"$targetDir\".\n";
            //$status = $this->dosOps->moveFile($sourceFile,$selDir.$latestChromeDriver);
            //$command = '"copy '.$sourceFile.' '.$selDir.$latestChromeDriver.'"';
            $command = $processDir."copyChromeDriver.bat";
            if ($debug) $this->dosOps->readFile($processDir."copyChromeDriver.bat");
            $status = $this->sysOps->ExecuteUserCommand($command,"",false,true);                   // false do not show true wait
            if ($debug) 
                {
                echo "Status on copying $sourceFile with command $command : \"$status\" \n";
                if (file_exists($sourceFile)) echo "  Source file available.\n";
                if (file_exists($targetDir.$filename)) echo "Copy ".$targetDir.$filename." successfull.\n"; 
                else echo "What happend now ?\n";
                }
            if (file_exists($targetDir.$filename)===false) {$filename=false; $status=103;}
            $this->filename=$filename;
            return ($status);
            }
        else 
            {
            $this->filename=$filename;
            //echo "copyChromedriver ($sourceFile,$targetDir) bereits durchgeführt. \n";
            return (102);
            }
        }

    function deleteChromedriverBackup()
        {
        $found = $this->dosOps->findfiles($this->selDirContent,"chromedriver_alt.exe");
        if ($found)
            {
            //echo "Altes chromedriver-alt.exe gefunden. Loeschen \"".$found[0]."\"\n";   
            $this->dosOps->deleteFile($this->selDir.$found[0]);
            return(true);
            }
        return(false);
        }

    /* aktuellen chromedriver umbennen auf alt und neuen auf chromedriver ohne Versionsangabe
     */
    function renameChromedriver($latestChromeDriver=false,$debug=false)
        {
        if ($latestChromeDriver===false) $latestChromeDriver=$this->filename;
        if ($latestChromeDriver===false) return(false);
        $processDir = $this->getprocessDir();
        if ($debug) echo "Selenium had been stopped. Rename Chromedrivers. Write cmd file in $processDir.\n";
        $handle2=fopen($processDir."rename_Chromedriver.bat","w");
        fwrite($handle2,"cd ".$this->selDir."\r\n");
        fwrite($handle2,"rename chromedriver.exe chromedriver_alt.exe\r\n");
        fwrite($handle2,"rename $latestChromeDriver chromedriver.exe\r\n");
        fclose($handle2);                 

        $command = $processDir."rename_Chromedriver.bat";
        if ($debug) $this->dosOps->readFile($processDir."rename_Chromedriver.bat");
        $status = $this->sysOps->ExecuteUserCommand($command,"",false,true);                   // false do not show true wait
        if ($debug) echo "Status on renaming $latestChromeDriver with chromedriver.exe and Status : $status \n";
        return ($status);
        }

    }


?>