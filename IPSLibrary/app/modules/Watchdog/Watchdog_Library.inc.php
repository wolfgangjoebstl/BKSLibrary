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
	 
/****************
 *
 * candidated for depricated module, main function not needed
 *
 * class
 *      watchDog
 *          watchDogAutoStart extends watchDog
 *          seleniumChromedriverUpdate
 *
 *
 *
 *
 *
 *******************************************************************/

/********************************************************************************************
 *                                                                                           
 *                              Functions, Klassendefinitionen                               
 *   watchDog
 *                                                                                        
 * Zusammenfassung nutzvoller Funktionen in einer Klasse
 *      __construct
 *      setConfiguration
 *      getConfiguration
 *      getSeleniumConfiguration
 *      getActiveProcesses
 *      isSeleniumServer
 *      isSeleniumServerAtDir
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
 *
 *  createCmdFileActiveProcesses
 *  createCmdFileSelfShutdown
 *  createCmdFileSelfRestart
 *
 *
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


?>