<?


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
/*                                                                                           */
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

class watchDogAutoStart
    {

    protected $configuration;

    function __construct()
        {
        $this->configuration=$this->setConfiguration();    
        }

    function setConfiguration()
        {
        $config=array();
        if ((function_exists("Watchdog_Configuration"))===false) IPSUtils_Include ("Watchdog_Configuration.inc.php","IPSLibrary::config::modules::Watchdog");				
        if (function_exists("Watchdog_Configuration")) $configInput=Watchdog_Configuration();
        else echo "*************Fehler, Watchdog_Configuration.inc.php Konfig File nicht included oder Funktion Watchdog_Configuration() nicht vorhanden. Es wird mit Defaultwerten gearbeitet.\n";

        configfileParser($configInput, $configSoftware, ["Software","SOFTWARE","software"],"Software",null);    
        configfileParser($configInput, $config, ["RemoteShutDown","REMOTESHUTDOWN","remoteshutdown"],"RemoteShutDown",null);                // null Index wird trotzdem übernommen

        configfileParser($configInput, $config, ["WatchDogDirectory","WATCHDOGDIRECTORY","watchdogdirectory"],"WatchDogDirectory","/process/");                // null Index wird trotzdem übernommen
        $dosOps = new dosOps();
        $systemDir     = $dosOps->getWorkDirectory(); 
        if (strpos($config["WatchDogDirectory"],"C:/Scripts/")===0) $config["WatchDogDirectory"]=substr($config["WatchDogDirectory"],10);      // Workaround für C:/Scripts"
        $config["WatchDogDirectory"] = $dosOps->correctDirName($systemDir.$config["WatchDogDirectory"]);
        $dosOps->mkdirtree($config["WatchDogDirectory"]);

        /* check Selenium */
        configfileParser($configSoftware["Software"], $configSelenium, ["Selenium","SELENIUM","selenium"],"Selenium",null);    
        //print_r($configSelenium);
        configfileParser($configSelenium["Selenium"], $config["Software"]["Selenium"], ["Directory","DIRECTORY","directory"],"Directory","/Selenium/");    
        configfileParser($configSelenium["Selenium"], $config["Software"]["Selenium"], ["Autostart","AUTOSTART","autostart"],"Autostart","no");    
        configfileParser($configSelenium["Selenium"], $config["Software"]["Selenium"], ["Execute"],"Execute","selenium-server-standalone-3.141.59.jar");    
        if (strpos($config["Software"]["Selenium"]["Directory"],"C:/Scripts/")===0) $config["Software"]["Selenium"]["Directory"]=substr($config["Software"]["Selenium"]["Directory"],10);      // Workaround für C:/Scripts"
        $config["Software"]["Selenium"]["Directory"] = $dosOps->correctDirName($systemDir.$config["Software"]["Selenium"]["Directory"]);
        $dosOps->mkdirtree($config["Software"]["Selenium"]["Directory"]);

        /* check Firefox */
        configfileParser($configSoftware["Software"], $configFirefox, ["Firefox","FIREFOX","firefox"],"Firefox",null);    
        //print_r($configFirefox);
        configfileParser($configFirefox["Firefox"], $config["Software"]["Firefox"], ["Directory","DIRECTORY","directory"],"Directory","C:/Program Files/Mozilla Firefox/");    
        configfileParser($configFirefox["Firefox"], $config["Software"]["Firefox"], ["Autostart","AUTOSTART","autostart"],"Autostart","no");    
        configfileParser($configFirefox["Firefox"], $config["Software"]["Firefox"], ["Url"],"Url","http://localhost:3777/");    

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

    function getConfiguration()
        {
        return($this->configuration);
        }



    }




?>