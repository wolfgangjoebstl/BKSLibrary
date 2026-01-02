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
/*                                                                                           */
/*********************************************************************************************/
/*********************************************************************************************
 *
 *
 * letzte Version 6.02.2024, Änderungen für Update auf IPS7
 *
 * webfront/user Verzeichnis auf user/ geändert wenn IPS7
 *
 * diese Klassen werden hier behandelt, Anordnung der Übersicht halber etwas angepasst:
 *
 * OperationCenterConfig
 *          SeleniumChromedriver            extend OperationCenterConfig
 *          seleniumChromedriverUpdate      extend OperationCenterConfig
 *
 *      OperationCenter     extend OperationCenterConfig
 *          BackupIpsymcon          extend OperationCenter
 *          LogFileHandler          extend OperationCenter
 *          HomematicOperation      extend OperationCenter
 *          PingOperation           extend OperationCenter
 *          CamOperation            extend OperationCenter
 *
 * statusDisplay
 * parsefile
 * TimerHandling
 *
 * und einige Allgemeine Funktionen
 *
 * in eine eigene Datei DeviceManagement_Library verschoben wurde:
 *
 *      DeviceManagement  
 *          DeviceManagement_FS20       extends DeviceManagement
 *          DeviceManagement_Homematic  extends DeviceManagement
 *          DeviceManagement_Hue        extends DeviceManagement
 *          DeviceManagement_HueV2      extends DeviceManagement_Hue
 *
 */


/* class zum Zusammenfassen der Konfigurationen
 * nur weil die selbe Konfiguration genutzt wird nicht gleich das ganze construct übernehmen
 *
 * verwaltet selbst keine Variablen !! daher nur set Operationen
 * braucht aber selbst zumindest this->dosOps
 *
 *  setSetup
 *  setConfigurationSoftware
 *  setConfiguration
 *
 * soll wie auch in anderen Modulen ein zentrale Verwaltung und Anpassung der Konfiguration an neue versionen des betriebssystem (auch Win oder Ubuntu) oder Symcon erleichtern
 *
 */
class OperationCenterConfig
	{

    /* OperationCenterConfig::setSetup Konfiguration schreiben
     * dazu OperationCenter_SetUp() aus dem Config File OperationCenter_Configuration.inc.php einlesen
     *
     *  CloudMode               Dropbox/Synology Switcher
     *  CONFIG                  log Handling
     *  SOFTWARE                Autostart, Selenium Handling
     *  FTP                     Status, Root Directory (if needed)
     *  SystemDirectory
     *  BACKUP
     *
     * return config with correct values
     */

    public function setSetup()
        {
        $config=array();
        $dosOps=new dosOps();
        if ((function_exists("OperationCenter_SetUp"))===false) IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");				
        if (function_exists("OperationCenter_SetUp")) $configInput=OperationCenter_SetUp();
        else echo "*************Fehler, OperationCenter_Configuration.inc.php Konfig File nicht included oder Funktion OperationCenter_SetUp() nicht vorhanden. Es wird mit Defaultwerten gearbeitet.\n";

        /* Dropbox/Synology Switcher */
        configfileParser($configInput, $config, ["CloudMode","CLOUDMODE","cloudmode"],"CloudMode",'Synology');

        /* Compatibility for Dropbox, to be removed sometimes */
        if (isset($config["DropboxDirectory"]))
            {
            configfileParser($configInput, $config, ["DropboxDirectory"],"DropboxDirectory",'C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/scripts/');    
            configfileParser($configInput, $config, ["DropboxStatusDirectory"],"DropboxStatusDirectory",'C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/Status/');   
            configfileParser($configInput, $config, ["DropboxStatusMaxFileCount"],"DropboxStatusMaxFileCount",100);
            }
        switch (strtoupper($config["CloudMode"]))
            {
            case "NONE":
            case "DEFAULT":
            case "DROPBOX":
                /* Dropbox Profile */

                /* Dropbox Profile, new Style */
                configfileParser($configInput, $config, ["Dropbox"],"Dropbox",'{"Directory":false,"StatusDirectory":false,"StatusMaxFileCount":100}');
                configfileParser($configInput["Dropbox"], $config["Cloud"], ["Directory"],"Directory",$config["DropboxDirectory"]); 
                configfileParser($configInput["Dropbox"], $config["Cloud"], ["StatusDirectory"],"StatusDirectory",$config["DropboxStatusDirectory"]); 
                configfileParser($configInput["Dropbox"], $config["Cloud"], ["StatusMaxFileCount"],"StatusMaxFileCount",$config["DropboxStatusMaxFileCount"]); 
                break;
            case "SYNOLOGY":
                /* Synology Profile*/
                configfileParser($configInput, $config, ["Synology"],"Synology",'{"Directory":false,"StatusDirectory":false,"StatusMaxFileCount":100}');
                configfileParser($configInput["Synology"], $config["Cloud"], ["Directory"],"Directory",false); 
                configfileParser($configInput["Synology"], $config["Cloud"], ["StatusDirectory"],"StatusDirectory",false); 
                configfileParser($configInput["Synology"], $config["Cloud"], ["StatusMaxFileCount"],"StatusMaxFileCount",100); 

                // Alternative, sich die entsprechenden Verzeichnisse selbst zusammenbauen
                configfileParser($configInput["Synology"], $config["Cloud"], ["SynologyCloudDirectory","CloudDirectory","clouddirectory"],"CloudDirectory",false); 
                configfileParser($configInput["Synology"], $config["Cloud"], ["Scripts"],"Scripts",false); 
                configfileParser($configInput["Synology"], $config["Cloud"], ["Executes"],"Executes",false); 
                configfileParser($configInput["Synology"], $config["Cloud"], ["Status"],"Status",false); 

                break;
            }

        /* iMacro, deprecated, not needed */
        configfileParser($configInput, $config, ["MacroDirectory"],"MacroDirectory",false);
        configfileParser($configInput, $config, ["DownloadDirectory"],"DownloadDirectory",false);
        configfileParser($configInput, $config, ["FirefoxDirectory"],"FirefoxDirectory",false);

        /* log Handling */
        configfileParser($configInput, $config, ["CONFIG","Config","Configuration"],"CONFIG",'{"MOVELOGS":true,"PURGELOGS":true,"PURGESIZE":10}');    
        configfileParser($configInput["CONFIG"], $config["CONFIG"], ["MOVELOGS"],"MOVELOGS",true);    
        configfileParser($configInput["CONFIG"], $config["CONFIG"], ["PURGELOGS"],"PURGELOGS",true);    
        configfileParser($configInput["CONFIG"], $config["CONFIG"], ["PURGESIZE"],"PURGESIZE",10);    

        /* FTP Server Configuration */
        configfileParser($configInput, $config, ["FTP","Ftp","ftp"],"FTP",null);
        if (isset($config["FTP"]))          // ohne FTP keine weiteren Defaults
            {    
            configfileParser($configInput["FTP"], $config["FTP"], ["Mode","MODE","mode"],"Mode","disabled");    
            configfileParser($configInput["FTP"], $config["FTP"], ["Directory","DIRECTORY","directory","dir"],"Directory","C:\Scripts");
            }    

        /* Autostart, Selenium Handling */
        configfileParser($configInput, $config, ["SOFTWARE","Software","software"],"Software",null);    

        /* system Directory for inquiries with cmd window */
        configfileParser($configInput, $config, ["SystemDirectory","Systemdirectory","systemdirectory"],"SystemDirectory","OperationCenter");
        $systemDir     = $dosOps->correctDirName($dosOps->getWorkDirectory()); 
        if ( (strpos($config["SystemDirectory"],"C:/Scripts")===0) || (strpos($config["SystemDirectory"],'C:\Scripts')===0) ) $config["SystemDirectory"]=substr($config["SystemDirectory"],10);      // Workaround für C:/Scripts
        $config["SystemDirectory"] = $dosOps->correctDirName($systemDir.$config["SystemDirectory"]);
        $dosOps->mkdirtree($config["SystemDirectory"]); 

        /* Logging Directory for activities to be noted */
        configfileParser($configInput, $config, ["LogDirectory","Logdirectory","logdirectory","Logging"],"LogDirectory","Logging");
        if ( (strpos($config["LogDirectory"],"C:/Scripts")===0) || (strpos($config["LogDirectory"],'C:\Scripts')===0) ) $config["LogDirectory"]=substr($config["LogDirectory"],10);      // Workaround für C:/Scripts
        $config["LogDirectory"] = $dosOps->correctDirName($systemDir.$config["LogDirectory"]);
        $dosOps->mkdirtree($config["LogDirectory"]);         

        /* Backup */
        configfileParser($configInput, $config, ["BACKUP","Backup"],"BACKUP",'{"Directory":"/Backup/","FREQUENCE":"Day","FULL":["Mon","Wed"],"KEEPDAY":10,"KEEPMONTH":10,"KEEPYEAR":2}');  
        // es werden alle Subkonfigurationen kopiert, wenn das nicht sein soll einmal umsetzen
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["Status","STATUS","status"], "Status","disabled"); 
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["Directory"], "Directory","/Backup/IpSymcon");  
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["FREQUENCE", "Frequence"], "FREQUENCE","Day");  
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["FULL", "Full"], "FULL",'["Mon","Wed"]');  
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["KEEPDAY", "KeepDay","Keepday"], "KEEPDAY",10);
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["KEEPMONTH", "KeepMonth","Keepmonth"], "KEEPMONTH",10);
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["KEEPYEAR", "KeepYear","Keepyear"], "KEEPYEAR",2);

        return ($config);
        }
    
    /* OperationCenterConfig::setConfigurationSoftware          formerly part of Watchdog, reads both config files in order Watchdog->OperationCenter
     * überprüfen der Konfiguration und Speichern
     * aufgerufen von
     *
     */
    function setConfigurationSoftware()
        {
        $config=array();
        $dosOps=new dosOps();
        $systemDir     = $dosOps->getWorkDirectory(); 

        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        $moduleManager = new IPSModuleManager('OperationCenter',$repository);
        $installedModules   = $moduleManager->GetInstalledModules();
        if (isset($installedModules["Watchdog"]))
            {
            if ((function_exists("Watchdog_Configuration"))===false) IPSUtils_Include ("Watchdog_Configuration.inc.php","IPSLibrary::config::modules::Watchdog");				
            }
        if (function_exists("Watchdog_Configuration")) $configInput=Watchdog_Configuration();
        else 
            {
            //echo "Warning, Watchdog_Configuration.inc.php Konfig File nicht included oder Funktion Watchdog_Configuration() nicht vorhanden. Es wird mit Werten aus dem OperationCenter gearbeitet.\n";
            $configInput=$this->setSetup();
            }

        configfileParser($configInput, $configSoftware, ["Software","SOFTWARE","software"],"Software",null);    

        configfileParser($configInput, $config, ["WatchDogDirectory","WATCHDOGDIRECTORY","watchdogdirectory","ProcessDirectory","PROCESS"],"WatchDogDirectory","/process/");                // null Index wird trotzdem übernommen
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

    /* Konfigurationen für IP Adressen etc.
     */
    public function setConfiguration()
        {
        $config=array();
        if ((function_exists("OperationCenter_Configuration"))===false) IPSUtils_Include ('OperationCenter_Configuration.inc.php', 'IPSLibrary::config::modules::OperationCenter');				
        if (function_exists("OperationCenter_Configuration"))
            {
            $configInput = OperationCenter_Configuration();            
            configfileParser($configInput, $config, ["INTERNET" ],"INTERNET" ,"[]");  

            /* Router, nur die aktiven, kopieren */
            configfileParser($configInput, $configRouter, ["ROUTER" ],"ROUTER" ,"[]");
            if (count($configRouter['ROUTER'])==0) 
                { 
                //echo "config Router empty.\n"; 
                $config["ROUTER"]=[]; 
                }
            foreach ($configRouter['ROUTER'] as $name => $router)
                {
                if (!( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") ))           // wenn Status und nicht als active gekennzeichnet, Konfig ist raus und wird nicht übernommen
                    {
                    $config["ROUTER"][$name]=$router;
                    }
                }

            configfileParser($configInput, $config, ["FTP" ],"FTP" ,["Mode"=>"disabled"]);  

            configfileParser($configInput, $configCam, ["CAM" ],"CAM" ,[]);  
            //print_r($configCam);
            
            $config["CAM"]=$this->setCamConfig($configCam,$config)["CAM"];
            configfileParser($configInput, $config, ["LED" ],"LED" ,[]);  
            
            configfileParser($configInput, $config, ["DENON" ],"DENON" ,[]);

            }
        //$this->oc_Configuration = $config;
        return ($config);
        }

    /*
     * aufgerufen von setConfiguration
     * nutzt Input von setSetup, wenn dort das FTP.Directory definiert ist, wird es als Ergänzung zum FTPFOLDER verwendet
     * alternativ kann es auch hier als zweiter Parameter übergeben werden
     */
    public function setCamConfig($camConfig,$setup=false)
        {
        if ($setup===false) $setup = $this->setSetup();
        $dosOps=new dosOps();
        $ftp=false;
        if (isset($setup["FTP"]["Directory"])) $ftp=$setup["FTP"];
        $config=array();
        foreach ($camConfig as $index=>$configInput);           // identify Index
        //echo "setCamConfig for $index : ";print_r($configInput);
        $output=array();
        foreach($configInput as $camName => $camera)
            {
            $config["NAME"]=$camName;
            configfileParser($camera, $config, ["STATUS" ],"STATUS" ,"enabled");                // default enabled
            configfileParser($camera, $config, ["FTP" ],"FTP" ,"disabled");
            configfileParser($camera, $config, ["FTPFOLDER" ],"FTPFOLDER" ,"");
            
            //echo "setCamConfig ftp directory calculated ".$config["FTPFOLDER"]."\n";
            if ($ftp) $config["FTPFOLDER"]=$dosOps->correctDirName($ftp["Directory"].$config["FTPFOLDER"]);
            else $config["FTPFOLDER"]=$dosOps->correctDirName($config["FTPFOLDER"]);;

            configfileParser($camera, $config, ["MAC" ],"MAC" ,"");
            configfileParser($camera, $config, ["IPADRESSE" ],"IPADRESSE" ,"");
            configfileParser($camera, $config, ["TYPE" ],"TYPE" ,"");
            configfileParser($camera, $config, ["DOMAINNAME" ],"DOMAINNAME" ,"");
            configfileParser($camera, $config, ["COMPONENT" ],"COMPONENT" ,"");
            configfileParser($camera, $config, ["STREAM" ],"STREAM" ,"disabled");
            configfileParser($camera, $config, ["FORMAT" ],"FORMAT" ,"");
            configfileParser($camera, $config, ["STREAMPORT" ],"STREAMPORT" ,"");
            configfileParser($camera, $config, ["USERNAME" ],"USERNAME" ,"");
            configfileParser($camera, $config, ["PASSWORD" ],"PASSWORD" ,"");
            configfileParser($camera, $config, ["MOVECAMFILES" ],"MOVECAMFILES" ,"");
            configfileParser($camera, $config, ["PURGECAMFILES" ],"PURGECAMFILES" ,"");
            configfileParser($camera, $config, ["PURGESIZE" ],"PURGESIZE" ,"");
            configfileParser($camera, $config, ["NOK_HOURS" ],"NOK_HOURS" ,"");
            configfileParser($camera, $config, ["DISPLAYCOLS" ],"DISPLAYCOLS" ,"");
            configfileParser($camera, $config, ["SELECTOUTOFF" ],"SELECTOUTOFF" ,"");
            configfileParser($camera, $config, ["SELECTNUM" ],"SELECTNUM" ,"");
            configfileParser($camera, $config, ["SELECTTRIGGER" ],"SELECTTRIGGER" ,"");

            $components=explode(",",$config["COMPONENT"]);
            if (sizeof($components)>1) 
                {
                //print_r($components);
                $config["COMPONENT"]=$components[0];
                $config["DOMAINNAME"]=$components[1];
                $config["USERNAME"]=$components[2];
                $config["PASSWORD"]=$components[3];
                }
            $output[$index][$camName]=$config;
            }
        if (sizeof($output)==0) return ["CAM"=>array()];
        
        //print_r($output);
        //$output[$index]=$configInput;
        return($output);
        }


    }



/***************************************************************************************************************
 *
 * Routinen für Klasse  OperationCenter, nachtraeglich aufgeteilt auf child classes
 * Übersicht über enthaltene Funktionen
 *      __construct
 *      setSetup
 *      setConfiguration
 *
 *      getSetup
 *      getConfiguration
 *      getCAMConfiguration, getLEDConfiguration, getINTERNETConfiguration, getROUTERConfiguration, getDENONConfiguration
 *
 *
 * Herausfinden der eigenen externen und lokalen IP Adresse
 *   whatismyIPaddress1   verwendet http://whatismyipaddress.com/
 *   whatismyIPaddress2
 *   ownIPaddress
 *
 *
 * systeminfo 			systeminfo vom PC auslesen und lokal die relevanten Daten speichern
 * readSystemInfo		die lokalen Daten als text ausgeben
 *
 * sys device ping IP Adresse von LED Modul oder DENON Receiver
 * Wenn device_ping zu oft fehlerhaft ist wird das Gerät rebootet, erfordert einen vorgelagerten Schalter und eine entsprechende Programmierung
 * Erreichbarkeit der Remote Server zum Loggen
 *
 *      device_ping  
 *      server_ping
 *      writeServerPingResults
 *      device_checkReboot	wenn device_ping zu oft gescheitert ist
 *
 * create_macipTable 	Verwalten einer MAC-IP Tabelle
 * get_macipTable
 * find_hostnames
 *
 * write_routerdata_MR3420	schreibt die gestrigen Download/Upload und Total Werte von einem MR3430 Router 
 * write_routerdata_MBRN3000
 * get_routerdata_MBRN3000
 * get_routerdata
 * get_routerdata_RT1900
 * get_routerdata_MR3420
 * get_router_history
 * get_data
 * sort_routerdata
 *
 * Bearbeiten von FTP Cam Files als auch Logdateien
 * MoveFiles			räumt die Dateien in ein Verzeichnisse pro Tag, einstellbar ist wieviele Tage zurückliegend damit begonnen werden soll
 * MoveCamFiles			MoveFiles mit den CamFiles. Es werden alle am FTP Laufwerk abgespeicherten Bilder sofort in die Tagesverzeichnissse geschlichtet
 * PurgeFiles			die älteren Verzeichnisse loeschen, wenn () keine Parameter dann die Funktion von PurgeLogs nachstellen
 *
 * CopyCamSnapshots		die Snapshots (zB alle Tage, Stunden, Minuten die von IPSCam erstellt werden im Webfront darstellen, und dazu wegkopieren
 * showCamCaptureFiles	es werden von den ftp Verzeichnissen ausgewählte Dateien in das Webfront/user oder neu user/ Verzeichnis für die Darstellung im Webfront kopiert
 *
 * imgsrcstring, extracttime	Hilfsroutinen
 * DirLogs, readdirToArray		Hilfsroutinen
 *
 * CopyScripts			Scriptfiles auf Cloud (Synology,Dropbox) kopieren
 * FileStatus			Statusfiles auf Cloud (Synology,Dropbox) kopieren
 * FileStatusDir		StatusFiles in Verzeichnisse verschieben
 * FileStatusDelete		verschobene Files gemeinsam mit den Verzeichnisssen loeschen
 * getFileStatusDir
 *
 * getIPSLoggerErrors	aus dem HTML Info Feld des IPS Loggers die Errormeldungen wieder herausziehen
 * stripHTMLTags
 *
 * andere classes, siehe weiter unten
 *
 * nutzt ipsTables, aber noch nicht implementiert, betrifft folgende html Tabellen:
 *          readSystemInfo
 *          writeIpTable
 *          deleteBackupStatusError
 *          writeTableStatus
 *
 *
 *
 ****************************************************************************************************************/

class OperationCenter extends OperationCenterConfig
	{
    static $hourPassed,$fourHourPassed;
    public $newstyle;                               // wenn true, IPS 7 oder größer, kein Webfront Verzeichnis, alles im User anlegen

    protected $dosOps,$sysOps;                        // verwendete andere Klassen 
    protected $ipsTables;                   // verwendete andere Klassen
    protected $systemDir;              // das SystemDir, gemeinsam für Zugriff zentral gespeichert
    private $debug;                 // zusaetzliche hilfreiche Debugs

	private $log_OperationCenter;

	protected $CategoryIdData, $categoryId_SysPing,$categoryId_RebootCtr,$categoryId_Access,$archiveHandlerID;
	
    var $subnet               	= "";

	var $mactable             	= array();
	var $oc_Configuration     	= array();
	var $oc_Setup			    = array();			/* Setup von Operationcenter, Verzeichnisse, Konfigurationen */
	var $AllHostnames         	= array();
	var $installedModules     	= array();

    var $moduleManagerCam;                           /* andere benutzte Module */
	
	var $HomematicSerialNumberList	= array();
	
	/* OperationCenter::__construct
	 *
	 * Initialisierung des OperationCenter Objektes
     *
     * subnet wird nicht so oft gebraucht, herausnehmen aus dem Standard:
     *      create_macipTable schreibt mactable für die IP Adressen die dem Subnet entsprechen
     *      get_macipTable liefert den mactable als Ergebnis
     *      get_macipdnsTable verwendet ebenfalls mactable, erweitert um Tabelle LogAlles_Hostnames oder Ergebnisse von nslookup
	 *      find_HostNames  anhand des macTables (arp Befehl im lokalen netzwerk) wird eine texttabelle erstellt und ausgegeben
     *      syspingCams verwendet get_macipTable mit param Subnet
     *      SystemInfo verwendet get_macipdnsTable
     *
	 */
	public function __construct($subnet='10.255.255.255',$debug=false)
		{
        $this->debug=$debug;
        $ips = new ipsOps();
        $this->newstyle = $ips->ipsVersion7check();

		IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
        $this->dosOps = new dosOps();                   // create classes used in this class
        $this->sysOps = new sysOps();                   // create classes used in this class
        $this->ipsTables = new ipsTables();             // create classes used in this class, standard creation of tables
        $this->systemDir     = $this->dosOps->getWorkDirectory();
        if ($debug && false)                                                        //false nicht ausgeben
            {
            echo "class OperationCenter: _construct\n";
            echo "   work Dir is ".$this->systemDir.", used for Logging files.\n";
            echo "   symcon Dir is ".IPS_GetKernelDir()."\n";
            echo "   OperatingSystem is ".$this->dosOps->evaluateOperatingSystem()."\n";
            echo "   install Dir is ".IPS_GetKernelDirEx()."\n";
            echo "   IPS platform is ".IPS_GetKernelPlatform()."\n";
            }
		$this->subnet=$subnet;
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

			//echo 'ModuleManager Variable not set --> Create "default" ModuleManager'."\n";
			$moduleManager = new IPSModuleManager('OperationCenter',$repository);
			}
		$this->CategoryIdData=$moduleManager->GetModuleCategoryID('data');
		$this->installedModules = $moduleManager->GetInstalledModules();

		$this->categoryId_SysPing    	= CreateCategory('SysPing',       	$this->CategoryIdData, 200);
		$this->categoryId_RebootCtr  	= CreateCategory('RebootCounter', 	$this->CategoryIdData, 210);
		$this->categoryId_Access  		= CreateCategory('AccessServer', 	$this->CategoryIdData, 220);
		$this->categoryId_SysInfo  		= CreateCategory('SystemInfo', 		$this->CategoryIdData, 230);
		
		//echo "Subnet ".$this->subnet."   ".$subnet."\n";		
		$this->mactable=$this->create_macipTable($this->subnet);
		$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $this->CategoryIdData, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
		$this->log_OperationCenter=new Logging($this->systemDir."Log_OperationCenter.csv",$input, "OperationCenter",true);       // File, Objekt und html Logging, und Prefix Classname
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

		$this->oc_Configuration = $this->setConfiguration();
		$this->oc_Setup = $this->setSetUp();
        //if ($debug) { echo "OperationCenter Konfigurationen: \n"; print_R($this->oc_Configuration); print_R($this->oc_Setup); }

		$this->AllHostnames = LogAlles_Hostnames();
		}
		
    /****************************************************************************************************************/

    /* OperationCenter::getSetup
     * Setup Config abfragen
     */
    public function getSetup()
        {
        return ($this->oc_Setup);
        }

    public function getConfiguration()
        {
        return ($this->oc_Configuration);
        }

    public function getCAMConfiguration()
        {
        return ($this->oc_Configuration["CAM"]);
        }

    public function getLEDConfiguration()
        {
        return ($this->oc_Configuration["LED"]);
        }

    public function getINTERNETConfiguration()
        {
        return ($this->oc_Configuration["INTERNET"]);
        }

    public function getROUTERConfiguration()
        {
        return ($this->oc_Configuration["ROUTER"]);
        }

    public function getDENONConfiguration()
        {
        return ($this->oc_Configuration["DENON"]);
        }

    public function getCategoryIdData()
        {
        return ($this->CategoryIdData);
        }

    public function getCategoryIdSysPing()
        {
        return ($this->categoryId_SysPing);
        }

    public function shiftZeileDebug()
        {
        $this->log_OperationCenter->shiftZeileDebug();    
        }

	/* OperationCenter::whatismyIPaddress1,whatismyIPaddress2
	 *
	 * what is my IP Adresse liefert eigene IP Adresse
	 *
	 * verwendet zwei unterschiedliche Methoden. 
	 *		address1 sucht nach Punkten mit kleiner 4 Zeichen Abstand, Ergebniss werden als Array ausgegeben pos und IP
	 *		address2 sucht nach einem Keywort. Ausgabe als String.
	 *
	 * Variante1 ist mehr generisch
	 *
	 */
	 
	function whatismyIPaddress1($debug=false)
		{
		$posP[0]["IP"]="unknown";
		$url="http://checkip.dyndns.com/";
		$posP[0]["Server"]=$url;
		
		//$url="http://whatismyipaddress.com/";  //auch gesperrt,  da html 1.1
		//$url="http://www.whatismyip.com/";  //gesperrt
		//$url="http://whatismyip.org/"; // java script
		//$url="http://www.myipaddress.com/show-my-ip-address/"; // check auf computerzugriffe
		//$url="http://www.ip-adress.com/"; //gesperrt

		/* ab und zu gibt es auch bei der whatismyipaddress url timeouts, 30sek maximum timeout 
		   d.h. Timeout: Server wird nicht erreicht
			Zustand false: kein Internet
		*/

		//$result=file_get_contents($url);
		$result1=get_data($url);

		/* letzte Alternative ist die Webcam selbst */

		if ($debug) echo "\n";
		if ($result1==false)
			{
			echo "Server reagiert nicht. Ip Adresse anders ermitteln.\n";
			return ($posP);
			}
		else	
			{
			$i=0;
			//echo "Variante wenn IPv4 Adresse als Tag im html steht :\n".$result1."\n";			
			$pos_start=strpos($result1,"whatismyipaddress.com/ip")+25;
			//echo "Suche Punkte:\n";
			$result2=$result1; $result=$result1;
			$pos=0; 
			while (strpos($result2,".")!==false)
				{
				$pos1=strpos($result2,".");
				$result2=substr($result2,$pos1+1);
				$pos+=$pos1+1; 
				//echo ":".$pos."(".substr($result,$pos-1,5).")";
				if ($pos1<4)  // zwei Punkte knapp beieinander
					{
					if (is_numeric(substr($result,$pos-$pos1-1,$pos1)) ) // das Ergebnis zwischen den Punkten ist numerisch, hurrah
						{
						//echo "    ".substr($result,$pos-$pos1-1,$pos1)." ist numerisch. zwei Punkte mit Abstand kleiner 4 gefunden. Wir sind auf Pos ".$pos."\n";
						// Was ist vor dem Punkt, auch eine Zahl ?
						$digits=0;
						//echo "|".substr($result,($pos-$pos1-5),16)."|";
						if ( is_numeric(substr($result,($pos-$pos1-3),1)) ) { $digits=1; }
						if ( is_numeric(substr($result,($pos-$pos1-4),1)) ) { $digits=2; }
						if ( is_numeric(substr($result,($pos-$pos1-5),1)) ) { $digits=3; }
						$posIP=$pos-($digits+$pos1+3);
						$result3=extractIPaddress(trim(substr($result,$posIP,20)));
						//echo "   Evaluate  ".($result3)."  Erste Zahl hat ".$digits." Stellen.\n";
						if (filter_var($result3, FILTER_VALIDATE_IP))
							{
							$found=false;
							if ($i>0)
								{
								if ($posP[$i-1]["IP"]==$result3)
									{
									//echo "IP Adresse schon einmal gefunden, nicht noch einmal speichern.\n";
									$found=true;
									}
								}
							if ($found == false)
								{		
								$posP[$i]["Pos"]=$posIP;
								$posP[$i++]["IP"]=$result3; 
								// "   IP Adresse gefunden   (Pos: ".$posIP.") :    ".trim(substr($result,$posIP,20))."\n";
								}
							}
						} 
					}
				}
			
			
			//echo "Variante wenn IPv4 Adresse als normaler Text im html steht :\n";			
			$result=strip_tags($result1);
			$result2=$result;
			$pos=0; 
			
			while (strpos($result2,".")!==false)
				{
				$pos1=strpos($result2,".");
				$result2=substr($result2,$pos1+1);
				$pos+=$pos1+1; 
				//echo ":".$pos."(".substr($result,$pos-1,5).")";
				if ($pos1<4)  // zwei Punkte knapp beieinander
					{
					if (is_numeric(substr($result,$pos-$pos1-1,$pos1)) ) // das Ergebnis zwischen den Punkten ist numerisch, hurrah
						{
						//echo "    ".substr($result,$pos-$pos1-1,$pos1)." ist numerisch. zwei Punkte mit Abstand kleiner 4 gefunden. Wir sind auf Pos ".$pos."\n";
						// Was ist vor dem Punkt, auch eine Zahl ?
						$digits=0;
						//echo "|".substr($result,($pos-$pos1-5),16)."|";
						if ( is_numeric(substr($result,($pos-$pos1-3),1)) ) { $digits=1; }
						if ( is_numeric(substr($result,($pos-$pos1-4),1)) ) { $digits=2; }
						if ( is_numeric(substr($result,($pos-$pos1-5),1)) ) { $digits=3; }
						$posIP=$pos-($digits+$pos1+3);
						$result3=extractIPaddress(trim(substr($result,$posIP,20)));
						//echo "   Evaluate  ".($result3)."  Erste Zahl hat ".$digits." Stellen.\n";
						if (filter_var($result3, FILTER_VALIDATE_IP))
							{
							$found=false;
							if ($i>0)
								{
								if ($posP[$i-1]["IP"]==$result3)
									{
									//echo "IP Adresse schon einmal gefunden, nicht noch einmal speichern.\n";
									$found=true;
									}
								}
							if ($found == false)
								{		
								$posP[$i]["Pos"]=$posIP;
								$posP[$i++]["IP"]=$result3; 
								// "   IP Adresse gefunden   (Pos: ".$posIP.") :    ".trim(substr($result,$posIP,20))."\n";
								}
							}
						} 
					}
				}
			//echo "\n";	
			return ($posP);
	   		}
		}

	function whatismyIPaddress2()
		{
		$url="http://whatismyipaddress.com/";  
		$result=get_data($url);
		if ($result==false)
			{
			echo "Whatismyipaddress reagiert nicht. Ip Adresse anders ermitteln.\n";
			return (false);
			}
		else	
			{
			$result=strip_tags($result);
			$pos_start=strpos($result,"Your IPv4 Address Is:")+21;		
			$subresult=trim(substr($result,$pos_start,40));
			//$pos_length=strpos($subresult,"\"");
			//$pos_length=strpos($subresult,chr(10));
			//echo "Startpos: ".$pos_start." Length: ".$pos_length." \n".$subresult."\n";
			//$subresult=substr($subresult,0,$pos_length);
			//echo "Whatismyipaddress liefert : ".$subresult."\n";
			if (filter_var($subresult, FILTER_VALIDATE_IP))
				{		
				//echo "Ausgabe ".$subresult."\n";	
				return ($subresult);
				}
			else
				{
				$result=self::whatismyIPaddress1();
				//echo "Ausgabe der Alternativwerte.\n";
				//print_r($result);
				if ( isset($result[0]["IP"]) ) { return ($result[0]["IP"]); }
				else { return ("unknown"); }
				}	
			}
		}	

	/** OperationCenter::ownIPaddress
	 *
	 * ownIPaddress liefert eigene IP Adresse, es können auch mehrere IP Adressen sein, wenn mehrere Ports
	 * verwendet in send_status, InstallHardware, OperationCenter
	 * Output ist ein array mit allen gefundenen IP Adressen und deren Ports
	 *    Array([100.66.204.72] => Array(
                    [Name] => Unbekannter Adapter Tailscale
                    [Gateway] => unknown   )
                [10.0.0.124] => Array (
                    [Name] => Ethernet-Adapter Ethernet
                    [Gateway] => ... ) )
     *
	 */
	 
	function ownIPaddress($debug=false)
		{
	   
		/********************************************************
	   	Eigene Ip Adresse immer ermitteln
		**********************************************************/

		$ipports=array();
        if (dosOps::getKernelPlattform()=="WINDOWS")            // does not use instantiated data, no construct needed, unix or windows file structure expected
            {
            if ($debug) echo "\nIPConfig Befehl liefert ...\n";
            $ipall=""; $hostname="unknown"; $lookforgateway=false;
            exec('ipconfig /all',$catch);   /* braucht ein MSDOS Befehl manchmal laenger als 30 Sekunden zum abarbeiten ? */
            //exec('ipconfig',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden, allerdings fehlt dann der Hostname */
            foreach($catch as $line)
                {
                if (strlen($line)>2)
                    {
                    //echo "  | ".$line."\n<br>";
                    if (substr($line,0,1)!=" ")
                        {
                        //echo "-------------------> Ueberschrift \n";
                        $portname=substr($line,0,strpos($line,":"));
                        }
                    if(preg_match('/IPv4-Adresse/i',$line))
                        {
                        //echo "Ausgabe catch :".$line."\n<br>";
                        list($t,$ip) = explode(':',$line);
                        $result = extractIPaddress($ip);
                        $ipports[$result]["Name"]=$portname;
                        $ipall=$ipall." ".$result;
                        $lookforgateway=true;
                        /* if(ip2long($ip > 0))
                            {
                            $ipports[]=$ip;
                            $ipall=$ipall." ".$ip;
                            $status2=true;
                            $pos=strpos($ipall,"(");  // bevorzugt eliminieren
                            $ipall=trim(substr($ipall,0,$pos));
                            }  */
                        }
                    if ($lookforgateway==true)
                        {
                        if(preg_match('/Standardgateway/i',$line))
                            {
                            //echo "Ausgabe catch :".$line."\n<br>";
                            list($t,$gw) = explode(':',$line);
                            $gw = extractIPaddress($gw);
                            $ipports[$result]["Gateway"]=$gw;
                            $lookforgateway=false;
                            }
                        }
                    if(preg_match('/Hostname/i',$line))
                        {
                        list($t,$hostname) = explode(':',$line);
                        $hostname = trim($hostname);
                        }
                    }  /* ende strlen */
                }
            if ($ipall == "") {$ipall="unknown";}
            }
        else
            {
            /* Linux version, does not report Gateway, but gets Prefix 32 means all Bits reserved for host 0 Bits for Network
             */
            $command = 'ip -j address show';
            $output = shell_exec($command);

            if (!$output) die("Fehler: Keine Ausgabe vom Befehl 'ip address show'.\n");         // Stelle sicher, dass etwas zurückkam

            $interfaces = json_decode($output, true);            // JSON parsen

            if (json_last_error() !== JSON_ERROR_NONE) die("Fehler beim Parsen von JSON: " . json_last_error_msg() . "\n");

            // Durch die Interfaces gehen und Infos anzeigen
            foreach ($interfaces as $iface) 
                {
                $ifname=$iface['ifname'];
                echo "Interface: $ifname link type ".$iface['link_type']."\n";
                switch ($iface['link_type'])
                    {
                    case "ether":
                    case "none":
                        if (!empty($iface['addr_info'])) {
                            foreach ($iface['addr_info'] as $addr) {
                                echo "  - Familie: " . $addr['family'] . "\n";
                                echo "    Adresse: " . $addr['local'] . "\n";
                                echo "    Prefix: /" . $addr['prefixlen'] . "\n";
                                if ($addr['family']=="inet")
                                    {
                                    $ipaddr=$addr['local']; 
                                    $ipports[$ipaddr]["Name"] = $ifname;
                                    $ipports[$ipaddr]["Gateway"] = "unknown";  
                                    }
                                if (isset($addr['scope'])) {
                                    echo "    Scope: " . $addr['scope'] . "\n";
                                }

                                if (isset($addr['label'])) {
                                    echo "    Label: " . $addr['label'] . "\n";
                                }
                            }
                        } else {
                            echo "  Keine Adressen vorhanden.\n";
                        }
                    break;
                    }
                echo "\n";
                }
            }
        if ($debug)
            { 
            echo "\n";
            echo "Hostname ist          : ".$hostname."\n";
            echo "Eigene IP Adresse ist : ".$ipall."\n";
            echo "\n";
            }
		//print_r($ipports);
		return ($ipports);
		}

    /****************************************************************************************************************/

	/** OperationCenter::device_ping
	 *
	 * sys ping IP Adresse von LED Modul, DENON Receiver oder einem anderem generischem Device
     * wird für Internet alle 5 Minuten, sonst jede Stunde aufgerufen
	 *
	 * es wird ein Statuseintrag und ein reboot Counter Eintrag erstellt und bearbeitet
	 * Eine Statusänderung erzeugt wenn nicht LOGGING auf false steht einen Eintrag im OperationCenter Sys-Logfile
     * es wird nicht gefiltert, also eine einmalige kurzfristige Nicht-Erreichbarkeit hat die gleiche Auswirkung
	 *
	 * config objekt wird übergeben, kann sein von INTERNET, LED oder DENON Ansteuerung, 
     *              Device LED oder DENON. Identifier IPADRESSE oder MAC
	 *
	 * ROUTER
	 * es wird die Konfiguration aus OperationCenter_Configuration()["ROUTER"] übergeben, identifier=IPADRESSE, device=router
	 *
	 */

	function device_ping($device_config, $device, $identifier, $hourPassed=true, $debug=false)
		{
        $status=array(); $i=0;
		foreach ($device_config as $name => $config)
			{
            if ( (isset($config["NOK_MINUTES"])) || $hourPassed || $debug)       /* alle 5 Minuten durchführen wenn NOK_MINUTES gesetzt wurde, sonst jede Stunde oder wen Debug */
                {
                //echo "device_ping:   $name\n"; print_r($config);
                $StatusID = @IPS_GetObjectIDByName($device."_".$name,$this->categoryId_SysPing);
                $RebootID = @IPS_GetObjectIDByName($device."_".$name,$this->categoryId_RebootCtr);
                /* Auto Install, neue ping Abfragen werden automatisch angelegt, geloeschte allerdings nicht entfernt ! */
                if ($StatusID===false) $StatusID = CreateVariableByName($this->categoryId_SysPing,   $device."_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
                if ($RebootID===false) $RebootID = CreateVariableByName($this->categoryId_RebootCtr, $device."_".$name, 1); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
				if (AC_GetLoggingStatus($this->archiveHandlerID,$StatusID) === false)
					{ // nachtraeglich Loggingstatus setzen
					AC_SetLoggingStatus($this->archiveHandlerID,$StatusID,true);
					AC_SetAggregationType($this->archiveHandlerID,$StatusID,0);
					IPS_ApplyChanges($this->archiveHandlerID);
                    }
                if (isset($config[$identifier])==true)      /* es gibt eine IP Adresse */
                    {
                    $ipAdressen=array();        // array immer neu loeschen
                    if (is_array($config[$identifier])) $ipAdressen=$config[$identifier];
                    else $ipAdressen[]=$config[$identifier];
                    //print_r($ipAdressen);
                    $status[$name]=false;
                    foreach ($ipAdressen as $ipAdresse)     // Oder Verknüpfung der Status Informationen
                        {
                        //echo "Sys_ping Led Ansteuerung : ".$name." mit MAC Adresse ".$cam_config['MAC']." und IP Adresse ".$mactable[$cam_config['MAC']]."\n";
                        if ($status[$name]==false)          // solange probieren bis ein gültiger Ping erhalten wurde
                            {
                            $status[$name]=Sys_Ping($ipAdresse,600);                 // IP Symcon Feature, Timeout 0,6 Sekunden
                            $objDateTime = new DateTime('NOW');
                            if ($debug) echo "    ".$objDateTime->format("H:i:s.v")." Try Sys_Ping ".$ipAdresse." with result Available : ".($status[$name]?"Yes":"No")."\n";
                            if ( (isset($config["RETRY"])) && ($config["RETRY"]>0) && ($status[$name]==false) )            // retries sind möglich und notwendig
                                {
                                for ($i=0;$i<$config["RETRY"];$i++)
                                    {
                                    //echo "    ".date("H:i:s.u")." Retry Sys_Ping ".$config[$identifier]." !!!\n";
                                    $status[$name]=Sys_Ping($ipAdresse,400);
                                    $objDateTime = new DateTime('NOW');
                                    if ($debug) echo "    ".$objDateTime->format("H:i:s.v")." Retry Sys_Ping ".$ipAdresse." with result Available : ".($status[$name]?"Yes":"No")."\n";
                                    if ($status[$name]) break;
                                    } 
                                }
                            }
                        }
                    if ($status[$name])         /* alles gut das Gerät wird erreicht */
                        {
                        if ($debug) echo "Sys_ping ".$device." Ansteuerung : ".$name." mit IP Adresse ".$ipAdresse."                   wird erreicht       !\n";
                        if (GetValue($StatusID)==false)
                            {  /* Statusänderung, Service ist zurück */
                            if ($debug) echo "SysPing Statusaenderung von ".$device.'_'.$name." auf Erreichbar.\n";
                            if ( (isset($config["LOGGING"])) && ($config["LOGGING"]==false) )
                                {
                                /* kein Logging gewünscht */
                                }
                            else
                                {
                                $this->log_OperationCenter->LogMessage('SysPing Statusaenderung von '.$device.'_'.$name.' auf Erreichbar');
                                $this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von '.$device.'_'.$name.' auf Erreichbar');
                                }
                            SetValue($StatusID,true);
                            SetValue($RebootID,0);
                            }
                        }
                    else                        /* nicht gut, Geraet nicht erreichbar */
                        {
                        $lastOK=time()-GetValue($RebootID)*60;
                        if ($debug) echo "Sys_ping ".$device." Ansteuerung : ".$name." mit IP Adresse ".$ipAdresse."                   wird NICHT erreicht! Zustand seit ".GetValue($RebootID)." Minuten, Reboot Counter since ".date("m.d.Y H:i:s",$lastOK)."\n";
                        if (GetValue($StatusID)==true)
                            {  /* Statusänderung */
                            if ($debug) echo "SysPing Statusaenderung von ".$device.'_'.$name." auf NICHT Erreichbar - mit ".($i+1)." Versuchen.\n";
                            if ( (isset($config["LOGGING"])) && ($config["LOGGING"]==false) )
                                {
                                /* kein Logging gewünscht */
                                }
                            else
                                {
                                $this->log_OperationCenter->LogMessage('SysPing Statusaenderung von '.$device.'_'.$name.' auf NICHT Erreichbar - mit '.($i+1).' Versuchen.');
                                $this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von '.$device.'_'.$name.' auf NICHT Erreichbar - mit '.($i+1).' Versuchen.');
                                }
                            SetValue($StatusID,false);
                            }
                        else            /* schon länger nicht erreichbar, Counter wird erhöht, kann 5 Minuten weise oder stundenweise erfolgen */
                            {
                            $lastChange = (time() - IPS_GetVariable($RebootID)["VariableChanged"])/60;
                            echo "Last Update of Reboot Counter was ".nf($lastChange,"m")." ago. ";
                            if (isset($config["NOK_MINUTES"])) 
                                {
                                echo "Config set to count in 5 minute Intervals.";
                                if ($debug == false) SetValue($RebootID,(GetValue($RebootID)+5));
                                }
                            else 
                                {
                                echo "Config set to count in 60 minute Intervals.";
                                if ($debug == false) SetValue($RebootID,(GetValue($RebootID)+60));
                                }
                            echo "\n";
                            }
                        }
                    }	        /* falsche Konfigurationen ignorieren, es gibt eine IP Adresse oder ein array von Adressen */
                }               /* wird nur jede Stunde oder alle 5 Minuten wenn Parameter vorhanden, aufgerufen */
			}       /* ende foreach device */
        return ($status);
		}

	/** OperationCenter::server_ping
     *
	 * wird in syspingAllDevices verwendet
	 * rpc call (sys ping) der IP Adresse von bekannten IP Symcon Servern
	 *
	 * Verwendet selbes Config File wie für die Remote Log Server, es wurden zusätzliche Parameter zur Unterscheidung eingeführt
	 *
	 * Wenn der Remote Server erreichbar ist werden Kernel Version und Uptime abgefragt und lokal gespeichert
     * $categoryId_SysPingControl    data.modules.OperationCenter.SysPing.SysPingControl
     *          $ServerUpdateStatusID       "ServerUpdate_".$Name
     * categoryId_SysPing           data.modules.OperationCenter.SysPing
     *          $ServerStatusID             "ServerUpdate_".$Name
	 * categoryId_Access            data.modules.OperationCenter.AccessServer
     *          $IPS_UpTimeID               $Name."_IPS_UpTime"                     wenn 0 ist der Server nicht erreichbar
     *
	 */
	function server_ping($debug=false)
		{
        $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$this->categoryId_SysPing);
        if ($categoryId_SysPingControl===false) return (false);
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
		$RemoteServer=array();
        if ($debug)
            {
            echo "Aufruf von OperationCenter::server_ping mit der Konfiguration aus RemoteAccess_GetServerConfig():\n";
		    print_r($remServer);
            $status = sys_ping("8.8.8.8",1000);
            if ($status===false) echo "    Google ping nicht erfolgreich. Internet möglicherweise nicht erreichbar.\n"; 
            }
		$method="IPS_GetName"; $params=array();

		foreach ($remServer as $Name => $Server)
			{
			//print_r($Server);
			$UrlAddress=$Server["ADRESSE"];
			if ($Server["STATUS"]=="Active")
				{
				$IPS_UpTimeID = CreateVariableByName($this->categoryId_Access, $Name."_IPS_UpTime", 1);
				IPS_SetVariableCustomProfile($IPS_UpTimeID,"~UnixTimestamp");
			
				$ServerStatusID = CreateVariableByName($this->categoryId_SysPing, "Server_".$Name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
				$ServerUpdateStatusID = CreateVariableByName($categoryId_SysPingControl, "ServerUpdate_".$Name, 1); 
				IPS_SetVariableCustomProfile($ServerUpdateStatusID,"~UnixTimestamp");
                if (GetValue($ServerStatusID)) $nextUpdate=0;
                else $nextUpdate=GetValue($ServerUpdateStatusID)+(time()-IPS_GetVariable($ServerStatusID)["VariableChanged"])/10;
                if ($debug) 
                    {
                    $lastUpdate=date("d.m.Y H:i",GetValue($ServerUpdateStatusID));
                    $lastChange = (time() - IPS_GetVariable($ServerStatusID)["VariableChanged"])/60;
                    echo "$Name : Status is ".(GetValue($ServerStatusID)?"available":"away").". Last Change of Status was ".nf($lastChange,"m")." ago. Last Update was on $lastUpdate . Next Update ".date("d.m.Y H:i",$nextUpdate).".\n";
                    }
                if (time()>$nextUpdate) // Server Erreichbarkeit beschleunigen, wenn erreichbar oft fragen, wenn nicht erreichbar immer seltener fragen
                    {
                    SetValue($ServerUpdateStatusID,time());

                    $RemoteServer[$Name]["Name"]=$UrlAddress;
                    $rpc = new JSONRPC($UrlAddress);
                    //echo "Server : ".$UrlAddress." hat Uptime: ".$rpc->IPS_GetUptime()."\n";
                    $data = @parse_url($UrlAddress);
                    if(($data === false) || !isset($data['scheme']) || !isset($data['host']))
                        throw new Exception("Invalid URL");
                    $url = $data['scheme']."://".$data['host'];
                    if(isset($data['port'])) $url .= ":".$data['port'];
                    if(isset($data['path'])) $url .= $data['path'];
                    if(isset($data['user']))
                        {
                        $username = $data['user'];
                        }
                    else
                        {
                        $username = "";
                        }
                    if(isset($data['pass']))
                        {
                        $password = $data['pass'];
                        }
                    else
                        {
                        $password = "";
                        }
                    if (!is_scalar($method)) {
                            throw new Exception('Method name has no scalar value');
                        }
                    if (!is_array($params)) {
                            throw new Exception('Params must be given as array');
                        }
                    //if ($debug) echo "    Ping ".json_encode($data).":\n";

                    $id = round(fmod(microtime(true)*1000, 10000));
                    $params = array_values($params);
                    $strencode = function(&$item, $key) {
                    if ( is_string($item) )
                            $item = utf8_encode($item);
                        else if ( is_array($item) )
                            array_walk_recursive($item, $strencode);
                        };
                    array_walk_recursive($params, $strencode);
                    $request = Array(
                                        "jsonrpc" => "2.0",
                                        "method" => $method,
                                        "params" => $params,
                                        "id" => $id
                                    );
                    $request = json_encode($request);
                    $header = "Content-type: application/json"."\r\n";
                    if(($username != "") || ($password != "")) {
                        $header .= "Authorization: Basic ".base64_encode($username.":".$password)."\r\n";
                        }
                    $options = Array(
                            "http" => array (
                            "method"  => 'POST',
                            "header"  => $header,
                            "content" => $request
                                            )
                                );
                    $context  = stream_context_create($options);

                    $response = @file_get_contents($url, false, $context);
                    if ($response===false)
                        {
                        $status = sys_ping($data["host"],1000); 
                        echo "   Server : ".$UrlAddress." mit Name: ".$Name." Fehler Context: ".$context." nicht erreicht. Ping $status\n";
                        SetValue($IPS_UpTimeID,0);
                        $RemoteServer[$Name]["Status"]=false;
                        if (GetValue($ServerStatusID)==true)
                            {  /* Statusänderung */
                            $this->log_OperationCenter->LogMessage('SysPing Statusaenderung von Server_'.$Name.' auf NICHT erreichbar');
                            $this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Server_'.$Name.' auf NICHT erreichbar');
                            SetValue($ServerStatusID,false);
                            }
                        }
                    else
                        {
                        $status = sys_ping($data["host"],1000); 
                        if ($status===false) echo "   Seltsam, ICMP Ping für ".$data["host"]." funktioniert nicht.\n";
                        $ServerName=$rpc->IPS_GetName(0);
                        sleep(1);
                        $ServerUptime=$rpc->IPS_GetKernelStartTime();
                        sleep(1);
                        $IPS_VersionID = CreateVariableByName($this->categoryId_Access, $Name."_IPS_Version", 3);
                        $ServerVersion=$rpc->IPS_GetKernelVersion();
                        echo "   Server : ".$UrlAddress." mit Name: ".$ServerName." und Version ".$ServerVersion." zuletzt rebootet: ".date("d.m H:i:s",$ServerUptime)."\n";
                        SetValue($IPS_UpTimeID,$ServerUptime);
                        SetValue($IPS_VersionID,$ServerVersion);
                        $RemoteServer[$Name]["Status"]=true;
                        if (GetValue($ServerStatusID)==false)
                            {  /* Statusänderung */
                            $this->log_OperationCenter->LogMessage('SysPing Statusaenderung von Server_'.$Name.' auf erreichbar');
                            $this->log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Server_'.$Name.' auf erreichbar');
                            SetValue($ServerStatusID,true);
                            }
                        }
                    }
			   }
			else
				{
				echo "   Server : ".$UrlAddress." mit Name: ".$Name." nicht auf active konfiguriert.\n";
				}	
			}
			return ($RemoteServer);
		}
		
	/* OperationCenter::writeServerPingResults
	 *
	 * Die Ergebnisse des Server Pings mit  RemoteAccess_GetServerConfig werden als String ausgeben
     * der String wird zB im Status textfile verwendet.
	 *
	 *******************************************************************************/	
		
	function writeServerPingResults()
		{

		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
		$result="Status der Remote Access Server (Name, Configuration, Logging und Status); Modul RenoteAccess ist installiert:\n\n";

		foreach ($remServer as $Name => $Server)
			{
			$ServerStatusID = CreateVariableByName($this->categoryId_SysPing, "Server_".$Name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			if ( GetValue($ServerStatusID)==true )
				{
				$result .= str_pad($Name,30).str_pad($Server["STATUS"],15).str_pad($Server["LOGGING"],15)."erreichbar\n";
				}
			else
				{
				$result .= str_pad($Name,30).str_pad($Server["STATUS"],15).str_pad($Server["LOGGING"],15)."abwesend\n";
				}

			}
		return($result);	
		}


    /* OperationCenter::setSysPingCount
     *
     * set SysPingCount for Debug Purposes
     *
     */
     public function setSysPingCount($SysPingCount)
        {
        $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$this->categoryId_SysPing);
        if ($categoryId_SysPingControl===false) return (false);
        $SysPingCountID = IPS_GetObjectIDByName("SysPingCount",$categoryId_SysPingControl); /* count 0f 5 Mins */
        SetValue($SysPingCountID,$SysPingCount);
        }

    /* count 5 Minutes
     *
     */
    public function count5mins($debug=false)
        {
        $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$this->categoryId_SysPing);
        if ($categoryId_SysPingControl===false) return (false);

		$SysPingStatusID = IPS_GetObjectIDByName("SysPingExectime",$categoryId_SysPingControl); /* exec time zum besseren Überwachen der Funktion */
		SetValue($SysPingStatusID,time());
        self::$hourPassed=false; self::$fourHourPassed=false;
        if ($debug) 
            { 
            self::$hourPassed=true; 
            self::$fourHourPassed=true; 
            }
        $SysPingCountID = IPS_GetObjectIDByName("SysPingCount",$categoryId_SysPingControl); /* count 0f 5 Mins */
        $SysPingCount   = GetValue($SysPingCountID);
        $SysPingCount++;
        if (($SysPingCount%12)==0)                           // alle 60 Minuten, 0,1,2,3,4,5,6,7,8,9,10,
            {
            if ($SysPingCount>=48) 
                {
                $SysPingCount=0;
                self::$fourHourPassed=true;
       			if ($debug==false) IPSLogger_Inf(__file__, "SysPingAllDevices every four hour.");
                }
   			elseif ($debug==false) IPSLogger_Inf(__file__, "SysPingAllDevices every hour.");
            self::$hourPassed=true;
            }
        if ($debug) 
            {
            echo "count5min, $SysPingStatusID is actual time of execution. $SysPingCountID is actual count : $SysPingCount . Hour passed: ". self::$hourPassed." Four Hours passed : ".self::$fourHourPassed."\n";
            }
        SetValue($SysPingCountID,$SysPingCount);
        }
    
    /* count 5 Minutes Status, no irregular debug activation
     *
     */
    public function count5minsStatus()
        {
        $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$this->categoryId_SysPing);
        if ($categoryId_SysPingControl===false) return (false);
        
		$SysPingStatusID = IPS_GetObjectIDByName("SysPingExectime",$categoryId_SysPingControl); 
        $SysPingCountID  = IPS_GetObjectIDByName("SysPingCount",$categoryId_SysPingControl); 
        $SysPingCount    = GetValue($SysPingCountID);
        if (($SysPingCount%12)==0) self::$hourPassed=true;
        if ($SysPingCount==0)      self::$fourHourPassed=true;

        echo "count5min, $SysPingStatusID is actual time of execution. $SysPingCountID is actual count : $SysPingCount .\n"; 
        echo "Hour passed: ".(self::$hourPassed?"Yes":"No")." Four Hours passed : ".(self::$fourHourPassed?"Yes":"No")."\n";
		echo "last time executed : ".date("d.m.Y H:i:s",GetValue($SysPingStatusID))."\n";
        return (["Count"=>$SysPingCount,1=>self::$hourPassed,4=>self::$fourHourPassed]);
        }

    /* OperationCenter::getLoggedValues
     * nur Variablen werden als Children zugelassen
     *
     */
    function getLoggedValues($objectID=false)
        {
        $result=array();
        if ($objectID===false) $objectID=$this->categoryId_SysPing;
        $childrens=IPS_GetChildrenIDs($objectID);
		foreach($childrens as $oid)
			{
            if (IPS_GetObject($oid)["ObjectType"]==2)
                {
                if (AC_GetLoggingStatus($this->archiveHandlerID,$oid)) 
                    {
                    $result[]=$oid;  
                    //echo "    $oid (".IPS_GetName($oid).")\n";
                    }
                }
            }
        return ($result);  
        }

    /*
     */

    function MinOrHoursOrDays($minutes)
        {
        if ($minutes <130 ) $result = round($minutes,2)." Minutes";
        elseif ($minutes < 4500) $result =round($minutes/60,2)." Hours";
        else $result =round($minutes/24/60,2)." Days";
        return ($result);
        }

    /*
     */

    function Dauer($start,$end,$maxend)
        {
        if ($end<$maxend) $end=$maxend;
        $dauer=(($start-$end)/60);
        return($dauer);
        }

    /**************************************************************************************************************/


	/* OperationCenter::SystemInfo
	 *
	 * liest systeminfo als IP Execute vom PC aus und speichert die relevanten Daten als Register
     * zusaetzlich wird versucht die Java execute zu finden, Ausgabe relevante Verzeichnisse
     * verwendet renameIndex um aus einer Zahl einen Arrayindex zu machen
     *
     * versucht mit whatismyIp Adress die eigene IP Adresse rauszubekommen
     * übergibt Symcon Daten wie IPS_GetKernelStartTime, IPS_GetKernelVersion
     * analysiert zusätzlich einige Verzeichnisse
     * MACIP Table
     *
	 * bei Windows wird Systemino ausfgerufen und besondere interessante Werte herausgefiltert.
     *      HostnameID          Hostname
		    SystemNameID        Betriebssystemname
		    SystemVersionID     Betriebssystemversion
		    HotfixID            Hotfix(es)
    		MemoryID            Verfuegbarer physischer Speicher von Gesamter physischer Speicher verfuegbar  Virtueller Arbeitsspeicher Virtualisiert.
            ExternalIP
            VersionJavaID
     *
     * bei Linux/Unix wird
     *
     *
     * es werden auch zwei Ausgabewerte geschrieben
     *      ipTableHtml die Mac Tabelle mit MAC, IP und Name wenn im nslookup oder LogAlles_Hostname enthalten ist
     *      sumTableHtmlID die SymstemInfo Tabelle
	 */
	 function SystemInfo($text=false,$debug=false)
	 	{
        $sysOps = new sysOps(); 
		$HostnameID   		= IPS_GetObjectIdByName("Hostname", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		$SystemNameID		= IPS_GetObjectIdByName("Betriebssystemname", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */		
		$SystemVersionID	= IPS_GetObjectIdByName("Betriebssystemversion", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
        $SystemCodenameID	= IPS_GetObjectIdByName("SystemCodename", $this->categoryId_SysInfo);

		$HotfixID			= IPS_GetObjectIdByName("Hotfix", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$MemoryID			= IPS_GetObjectIdByName("Memory", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	

		$ExternalIP			= IPS_GetObjectIdByName("ExternalIP", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		
		$UptimeID			= IPS_GetObjectIdByName("IPS_UpTime", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$VersionID			= IPS_GetObjectIdByName("IPS_Version", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		
        $VersionJavaID		= IPS_GetObjectIdByName("Java_Version", $this->categoryId_SysInfo); 	
        $tailScaleInfoID   	= IPS_GetObjectIdByName("TailScaleInfo", $this->categoryId_SysInfo); 

        /* zusaetzlich Table mit IP Adressen auslesen und in einem html Table darstellen */

        $ipTableHtml        = IPS_GetObjectIdByName("TabelleGeraeteImNetzwerk", $this->categoryId_SysInfo);     // ipTable am Schluss anlegen
        $sumTableHtmlID     = IPS_GetObjectIdByName("SystemInfoOverview", $this->categoryId_SysInfo);           // obige Informationen als kleine Tabelle erstellen

		$result=array();	/* fuer Zwischenberechnungen */
		$results=array();
		$results2=array();
	
		$PrintSI="";
		$PrintLines="";		
		
        /* exec Funktion von der Art wie IP Symcom gestartet wird abhängig, nur möglich wenn als System gestartet wurde 
         */
        $opSys     =  dosOps::getKernelPlattform();
        if ($debug) echo "Operating System evaluated out of IP Symcon Kerneldir : $opSys.\n";
        if ($opSys == "WINDOWS")
            {
            // exec('systeminfo',$catch);   // ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden 
            //$resultSystemInfo=IPS_EXECUTE("systeminfo","", true, true);
            if ($text===false) $resultSystemInfo=$this->sysOps->ExecuteUserCommand("C:/Windows/System32/systeminfo.exe","", false, true);           // gleicher Aufruf für alle
            else $resultSystemInfo=$text;
            $catch=explode("\x0A",$resultSystemInfo);             //zeilenweise als array speichern
            if ($debug) 
                {
                echo "Ergebnis der Abfrage mit Befehl systeminfo : \n";
                echo $resultSystemInfo;
                print_R($catch);
                }

            foreach($catch as $line)
                {
                if (strlen($line)>2)
                    {
                    //echo "  | ".$line."\n<br>";
                    $PrintLines.=$line."\n";
                    if (substr($line,0,1)!=" ")
                        {
                        /* Ueberschrift */
                        $pos1=strpos($line,":");
                        $VarName=trim(substr($line,0,$pos1));
                        $VarField=trim(substr($line,$pos1+1));
                        $result[1]="";$result[2]="";$result[3]="";
                        $result=explode(":",$line);
                        $results[$this->renameIndex($result[0])]=trim($result[1]);
                        
                        for ($i=2; $i<sizeof($result); $i++) { $results[$this->renameIndex($result[0])].=":".trim($result[$i]);  }
                        $results2[$VarName]=$VarField;
                        $PrintSI.="\n".$line;
                        }
                    else
                        {
                        /* Fortsetzung der Parameter Ausgabe, zurückgegeben wird results */
                        $PrintSI.=" ".trim($line);
                        $results[$this->renameIndex($result[0])].=" ".trim($line);
                        $results2[$VarName].=" ".trim($line);
                        }
                    }  /* ende strlen */
                }
            if ($debug>1) echo "Ausgabe direkt:\n".$PrintLines."\n";		

            /* auch Java ist in einem Windowsverzeichnis versteckt, nach Verzeichnis suchen 
            $dirs = ['C:/Program Files','C:/Program Files (x86)'];

            if ($debug) echo "Look for Java:\n";
            $found = $this->dosOps->dirAvailable('Java',$dirs,false);         // true mit Debug
            if ($found) 
                {
                if ($debug) echo "Java verzeichnis hier gefunden : $found.\n";
                $javadir=$found."Java\\";
                //$this->dosOps->writeDirStat($found.'Java');               // Java Version herausfinden
                $result = $this->dosOps->readdirToArray($found.'Java',["Detailed"=>false,"Dironly"=>true],0,$debug);
                //print_R($result);                                     // alle Unterverzeichnisse finden
                $javaVersion=false;
                foreach ($result as $entry) 
                    {
                    if ($javaVersion==false) $javaVersion=$entry;
                    else                         
                        {
                        if ($entry != "latest")
                            {
                            if ($debug) echo "more Java Versions are available. Here : $entry \n";
                            }
                        }
                    }
                if ($javaVersion && $debug)
                    {
                    $javadir.="$javaVersion\\bin\\";
                    $resultSystemInfo=$sysOps->ExecuteUserCommand($javadir."java.exe","-version", false, true);
                    echo "Execute this $javadir"."java.exe"." Result \n$resultSystemInfo \n";
                    }
                if ($debug) echo "Java version : $javaVersion.\n";                
                $results["Java_Version"]=$javaVersion;
                SetValue($VersionJavaID,$javaVersion);
                }
            */

            $dir=$this->appInstalledWin("Java");            // funktioniert nur für Windows
            if ($dir) 
                {
                if ($debug) echo "Java Installed at $dir \n";
                $javaVersion=$this->getJavaVersion($dir,$debug);
                if ($debug) echo "Java version : $javaVersion.\n"; 
                SetValue($VersionJavaID,$javaVersion);
                }

            $dir=$this->appInstalledWin("Tailscale");       // funktioniert nur für Windows
            if ($dir) 
                {
                echo "TailScale Installed at $dir \n";
                $status=json_encode($this->getTailScaleStatus($dir,$debug));
                //print_R($status);
                }
            else $staus="disabled";
            SetValue($tailScaleInfoID,$status);

            if (isset($results["Hostname"])) 
                {
                SetValue($HostnameID,$results["Hostname"]);
                SetValue($SystemNameID,$results["Betriebssystemname"]);
                SetValue($SystemVersionID,trim(substr($results["Betriebssystemversion"],0,strpos($results["Betriebssystemversion"]," "))));
                SetValue($HotfixID,trim(substr($results["Hotfix(es)"],0,strpos($results["Hotfix(es)"]," "))));
                SetValue($MemoryID,$results["Verfuegbarer physischer Speicher"]." von ".$results["Gesamter physischer Speicher"]." verfuegbar. ".$results["Virtueller Arbeitsspeicher"]." Virtualisiert.");
                }
            else 
                {
                echo "Hostname not evaluated out of SystemInfo, something might be wrong.\n";
                print_r($results);
                }
            }
        else
            {           // UNIX Betriebssystem
            // Hostname
            //Betriebssystemname
            //Betriebssystemversion
            unset($catch);
            $result=array();
            exec("lsb_release -a", $catch);           // ausgabe in Array geht schon wenn Parameter
            //print_r($catch);
            foreach ($catch as $line)
                {
                $data=explode(":",$line);
                $result[trim($data[0])]=trim($data[1]);
                }
            //print_r($result);
            $results["Hostname"]=exec("hostname");
            //echo "Hostname : ".$results["Hostname"]." \n";
            $results["Betriebssystemname"]=$result["Description"];
            $results["Betriebssystemversion"]=$result["Release"];
            $results["Hotfix(es)"]="unknown";
            $results["SystemCodename"]=$result["Codename"];

            //$catch=shell_exec("top -bn 1 -w 300;");             // oder df -h; free -m;  ps -ef;
            //print_r($catch);

            if (isset($results["Hostname"])) 
                {
                SetValue($HostnameID,$results["Hostname"]);
                SetValue($SystemNameID,$results["Betriebssystemname"]);
                SetValue($SystemVersionID,$results["Betriebssystemversion"]);
                SetValue($HotfixID,$results["Hotfix(es)"]);
                SetValue($SystemCodenameID,$results["SystemCodename"]);

                //SetValue($MemoryID,$results["Verfuegbarer physischer Speicher"]." von ".$results["Gesamter physischer Speicher"]." verfuegbar. ".$results["Virtueller Arbeitsspeicher"]." Virtualisiert.");
                }
            //$catch=shell_exec("top -bn 1 -w 300;");             // oder df -h; free -m;  ps -ef;
            //print_r($catch);
            }

		//print_r($results);
		//print_r($results2);
		//echo $PrintSI;
		
        /* eigene IP Adresse herausfinden
         */

		//$IPAdresse=$this->whatismyIPaddress2();
		//$results["ExterneIP"]=$IPAdresse;
		$IPAdresse=$this->whatismyIPaddress1()[0]["IP"];

		if (GetValue($ExternalIP) !== $IPAdresse) SetValue($ExternalIP,$IPAdresse);
		$results["ExterneIP"]=$IPAdresse;

		$ServerUptime=date("D d.m.Y H:i:s",IPS_GetKernelStartTime());
		$results["IPS_UpTime"]=$ServerUptime;

		$ServerVersion=IPS_GetKernelVersion();
		$results["IPS_Version"]=$ServerVersion;
		
        SetValue($UptimeID,$ServerUptime);
        SetValue($VersionID,$ServerVersion);

        /* MAC ipTable noch zusaetzlich beschreiben */

        $macTable=$this->get_macipdnsTable();

		$collumns=["index","IP","DNSname","Shortname","IP_Adresse","Hostname"];
		$str=$this->writeIpTable($macTable,$collumns);
        SetValue($ipTableHtml,$str);

        $html=true;
        $sumTableHtml=$this->readSystemInfo($html);             // die Systeminfo als html Tabelle zusammenstellen
        SetValue($sumTableHtmlID, $sumTableHtml);

		return $results;
		}	

    /* Umrechnung der Bezeichnung in SystemInfo in einen Array Index
     * trim, explode blank, wenn Verfügbar dann umschreiben in ue
     */
	 function renameIndex($origIndex)
	 	{
		$index=trim($origIndex);
		//echo "    $index\n";
		$words=explode(" ",$index);
		if ( (isset($words[2])) && ($words[2]=="Speicher"))
			{
			if (strpos($words[0],"Verf")===0) 
				{
				$index="Verfuegbarer physischer Speicher";
				//echo "  --> verfügbar gefunden \n";
				}
			//print_r($words);
			}
		
		
		return ($index);
		}

    /* OperationCenter::updateUser 
     * die aktuell eingeloggten User herausfinden, wer ist der Administrator
     * speichert die Werte für User (alle User json encoded) und die Administrator ID
     * aufgerufen von readSystemInfo, gibt eine Zeile davon aus
     *  User
     *	  *wolfg	2	Aktiv	13.04.2025 18:56
     *
     * unerlaubter Workaround:  wolfg wird als Administrator vorgeschlagen
     */
    function updateUser($debug=false)
        {
        if (dosOps::getKernelPlattform() == "WINDOWS")
            {
            $result=IPS_ExecuteEx('c:\windows\system32\query.exe', "user", false, true,-1);
            $content = str_replace(array("\r\n", "\n\r", "\r"), "\n", $result);                     // Zeilenumbruch vereinheitlichen
            $content = explode("\n", $content);                                         // und in Array umrechnen
            $firstline=false;
            $fileOps = new fileOps();
            $i=0; $result=array();
            foreach ($content as $index => $line)
                {
                if ($i==0) 
                    {
                    $delimiter = $fileOps->readFirstLineFixed($line,"ASCII",false);          // true für Debug
                    }
                else
                    {
                    $ergebnis = $fileOps->readFileFixedLine($line,$delimiter,false);            // andernfalls debug einsetzen
                    if (sizeof($ergebnis)>0) $result[$i]=$ergebnis;
                    }
                $i++;
                }
            // Ergebnis mit result[zeile]   ID  BENUTZERNAME  STATUS  ANMELDEZEIT
            $administrator="wolfg";                     // woher wissen wir wer der Administrator ist
            }
        else    
            {
            /* Alle user herausfinden, das ist eine lange Liste, aber nur wenige haben ein eigenes Home Directory
            * username       :password:UID :GID :comment         :home_directory       :shell
            * wolfgangjoebstl:x       :1000:1000:Wolfgang Joebstl:/home/wolfgangjoebstl:/bin/bash
            */
            exec("getent passwd", $catch);           // ausgabe in Array geht schon wenn Parameter
            //print_r($catch);
            $result=array(); $user=array(); $i=0;
            foreach ($catch as $entry)
                {
                $data = explode(":",$entry);
                // Ergebnis mit result[zeile]   ID (2)  BENUTZERNAME (0)  STATUS  ANMELDEZEIT
                //echo $data[5]."   ".strpos($data[5],"/home")."\n";
                if (strpos($data[5],"/home")===0) 
                    {
                    $result[$i]["BENUTZERNAME"]=$data[0];
                    $result[$i]["ID"]=$data[2];
                    $user[$data[0]]["Index"]=$i;
                    if ($debug) echo "Home User   : ".$result[$i]["BENUTZERNAME"]." \n";
                    $i++;
                    }
                }
            // whoami, unspektakulär, der Vollständigkeit halber
            unset ($catch);
            exec("whoami", $catch);           // ausgabe in Array, liefert höchstwahrscheinlich root als name
            //print_r($catch);
            if ($debug) echo "System User : ".$catch[0]."\n";
            $user[$catch[0]]["Status"]="active";
            $user[$catch[0]]["Index"]=$i;

            /* who
             *  username seat<n>|tty<n> anmeldezeitpunkt (anmeldeort)
             *
             * anhand von seat herausfinden welcher user active ist
             *
             */
            unset ($catch);
            exec("who", $catch);           // ausgabe in Array, , who gibt aktuell eingeloggte user aus, wenn user nicht eingeloggt ist, erscheint er nicht in der Tabelle, seat oder tty
            //print_r($catch);
            foreach ($catch as $entry)
                {
                foreach ($user as $name=>$index)        // alle angemeldeten user durchgehen und den zeitpunkt des Anmelden eintragen 
                    {
                    if (strpos($entry,$name)===0)
                        {
                        $pos1=strlen($name);
                        $entry1=substr($entry,$pos1);               // ab pos1 bis Ende
                        $pos2=strpos($entry1,"(")+1;                // pos2 ist die Klammer auf (, und damit der absolute Orientierungspunkt
                        $pos3=strpos($entry1,")");
                        //echo "User $name $pos1 $pos2 $pos3 \"$entry1\"\n";
                        if ($pos2<$pos3)
                            {
                            $application=substr($entry1,$pos2,$pos3-$pos2);
                            $datelen=18;                                                            // Datum, Zeit String
                            $lasttime=trim(substr($entry1,$pos2-$datelen,$datelen-1));
                            $seat=trim(substr($entry1,0,$pos2-$datelen));                           // seat ist der active user der Gnome benutzt
                            echo "     ".str_pad($name,30).str_pad($lasttime,20).str_pad($seat,12).$application."\n";
                            $user[$name]["LastTime"]=$lasttime;
                            $user[$name]["Application"]=$application;
                            }
                        }   
                    }             
                }
            //print_r($user);
            foreach ($user as $entry)
                {
                if (isset($result[$entry["Index"]]["BENUTZERNAME"]))
                    {
                    if (isset($entry["LastTime"])) $result[$entry["Index"]]["ANMELDEZEIT"]=$entry["LastTime"];  
                    else $result[$entry["Index"]]["ANMELDEZEIT"]="offline";

                    if (isset($entry["Status"])) $result[$entry["Index"]]["STATUS"]=$entry["Status"];  
                    else $result[$entry["Index"]]["STATUS"]="";
                    }
                }

            // getent group
            unset ($catch);
            exec("getent group", $catch);           // ausgabe in Array geht schon wenn Parameter
            //print_r($catch);
            foreach ($catch as $entry)
                {
                $data = explode(":",$entry);
                // group passwd id user   look for sudo
                if ($data[0]=="sudo")
                    {
                    //print_R($entry);
                    if ($debug) echo "Superuser   : ".$data[3]."\n";
                    $administrator=$data[3];                    // gehen wir mal davon aus dass es nur einen Administrator gibt
                    }

                }

            }
    
        $user=array();
        $administratorSessionId=false;
        $html="<td>User</td><td><table>";
        foreach ($result as $entry)
            {
            $user[$entry["ID"]]=$entry;
            $html .= "<tr><td>";
            if ($entry["BENUTZERNAME"]==$administrator)             // bei Unix gibts keinen Administrator
                {
                $user[$entry["ID"]]["ADMINISTRATOR"]=true; 
                $html .= "*";
                $administratorSessionId=$entry["ID"];
                }
            $html .= "</td><td>".$entry["BENUTZERNAME"]."</td><td>".$entry["ID"]."</td><td>".$entry["STATUS"]."</td><td>".$entry["ANMELDEZEIT"]."</td></tr>";
            }
        $html .= "</table></td>";
        //echo $html;
        $UserID 				= IPS_GetObjectIDByName("User", $this->categoryId_SysInfo); 	                        // json Tabelle für alle User
        $AdministratorID     	= IPS_GetObjectIDByName("AdministratorID", $this->categoryId_SysInfo); 	            // eine Zahl, aber lassen wir sie als String
        SetValue($UserID,json_encode($user));
        SetValue($AdministratorID,$administratorSessionId);
        return ($html);
        }
	
    function getUser()
        {
        $UserID 				= IPS_GetObjectIDByName("User", $this->categoryId_SysInfo); 	                        // json Tabelle für alle User
        return(json_decode(GetValue($UserID),true));
        }

    /* OperationCenter::updateAdministrator 
     * Übergabe der aktuell eingeloggten User, wer ist der Administrator, mit net user
     *
     */
    function updateAdministrator($user, $debug=false)
        {
        $administrator=false;
        if (dosOps::getKernelPlattform() == "WINDOWS")
            {
            $fileOps = new fileOps();
                foreach ($user as $entry)
                    {
                    if ($debug) echo $entry["BENUTZERNAME"]."\n";
                    $result=IPS_ExecuteEx('c:\windows\system32\net.exe', "user ".$entry["BENUTZERNAME"], false, true,-1);
                    $content = str_replace(array("\r\n", "\n\r", "\r"), "\n", $result);                     // Zeilenumbruch vereinheitlichen
                    $content = explode("\n", $content); 
                    //print_r($content);
                    $userName=$fileOps->analyseList($content, $debug);         // nur für tabellarische Anordnung mit Überschrift
                    //print_r($userName);
                    if (isset($userName["Lokale Gruppenmitgliedschaften"]))
                        {
                        if ( (strpos($userName["Lokale Gruppenmitgliedschaften"],"Administratoren")) > 0) $administrator=$entry["ID"];
                        }

                    }

            }
        return ($administrator);
        }

	/* OperationCenter::readSystemInfo
	 *
	 * Die von SystemInfo aus dem PC (via systeminfo) ausgelesenen und gespeicherten Daten werden für die Textausgabe formatiert und angereichert
	 * es werden keine Daten ausgelesen oder verarbeitet, hier erfolgt nur die Darstellung
     * verwendet werden aus der Category SysInfo :
     *      Hostname
     *      Betriebssystemname
     *      Betriebssystemversion, der CodeName wird aus der versionsnummer berechnet
     *      Hotfix
     *      ExternalIP
     *      IPS_UpTime
     *      IPS_Version
     *      call updateUser
     *
     *   Ausnahme: updateUser() soll für Windows und Unix funktionieren
     *
     *
     */								
	 function readSystemInfo($html=false)
	 	{
		$PrintLn="";
        $PrintHtml="";
        $PrintHtml.='<style> 
            table {width:100%}
            td {width:50%}						
            table,td {align:center;border:1px solid white;border-collapse:collapse;}
            </style>';
        $PrintHtml.='<table>';            
		$HostnameID   		= CreateVariableByName($this->categoryId_SysInfo, "Hostname", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		$SystemNameID		= CreateVariableByName($this->categoryId_SysInfo, "Betriebssystemname", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */		
		$SystemVersionID	= CreateVariableByName($this->categoryId_SysInfo, "Betriebssystemversion", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		$MemAvailableID	    = CreateVariableByName($this->categoryId_SysInfo, "Memory", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
        $tailScaleInfoID   	= CreateVariableByName($this->categoryId_SysInfo, "TailScaleInfo", 3); 
    	$SystemCodenameID	= IPS_GetObjectIdByName("SystemCodename",$this->categoryId_SysInfo);	
        $VersionJavaID		= IPS_GetObjectIdByName("Java_Version", $this->categoryId_SysInfo); 
        
        if (dosOps::getKernelPlattform() == "WINDOWS")
            {
            $Version=explode(".",getValue($SystemVersionID));
            //print_r($Version);
            if (isset($Version[2]))         // Windows System
                {
                switch ($Version[2])
                    {
                    /* siehe wikipedia Eintrag : https://en.wikipedia.org/wiki/Windows_10_version_history für die Uebersetzung der PC Versionen */
                    /* 18 Monate Support. d.h. max 3 Versionen kann man hinten sein */
                    case "10240": $Codename="RTM (Threshold 1)"; break;
                    case "10586": $Codename="November Update (Threshold 2)"; break;
                    case "14393": $Codename="Anniversary Update (Redstone 1)"; break;
                    case "15063": $Codename="Creators Update (Redstone 2)"; break;
                    case "16299": $Codename="Fall Creators Update (Redstone 3)"; break;
                    case "17134": $Codename="Spring Creators Update (Redstone 4)"; break;
                    case "17763": $Codename="Fall 2018 Update (Redstone 5)"; break;			        // long term maintenance release
                    case "18362": $Codename="May 2019 Update (19H1)"; break;
                    case "18363": $Codename="November 2019 Update (19H2)"; break;
                    case "19041": $Codename="May 2020 Update (20H1)"; break;            
                    case "19042": $Codename="October 2020 Update (20H2)"; break;            
                    case "19043": $Codename="May 2021 Update (21H1)"; break;
                    case "19044": $Codename="November 2021 Update (21H2)"; break;
                    case "19045": $Codename="2022 Update (22H2)"; break;

                    case "22000": $Codename="Win11 (21H2)"; break;
                    case "22621": $Codename="Win11 2022 Update (22H2)"; break;
                    case "22631": $Codename="Win11 2023 Update (22H2)"; break;
                    case "26100": $Codename="Win11 2024 Update (24H2)"; break;
                    default: $Codename=$Version[2];break;
                    }			
                }
            else $Codename="";			
            }
        else
            {
            $Codename=GetValue($SystemCodenameID);
            }
        $latestChange=date("d.m H:i",IPS_GetVariable($MemAvailableID)["VariableChanged"]);          // verändert sich dauernd

		$HotfixID			= CreateVariableByName($this->categoryId_SysInfo, "Hotfix", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$ExternalIP			= CreateVariableByName($this->categoryId_SysInfo, "ExternalIP", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$UptimeID			= CreateVariableByName($this->categoryId_SysInfo, "IPS_UpTime", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$VersionID			= CreateVariableByName($this->categoryId_SysInfo, "IPS_Version", 3); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$PrintLn.="   ".str_pad("Hostname",30)." = ".str_pad(GetValue($HostnameID),30)."   (".date("d.m H:i",IPS_GetVariable($HostnameID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("Betriebssystem Name",30)." = ".str_pad(GetValue($SystemNameID),30)."   (".date("d.m H:i",IPS_GetVariable($SystemNameID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("Betriebssystem Version",30)." = ".str_pad(GetValue($SystemVersionID),30)."   (".date("d.m H:i",IPS_GetVariable($SystemVersionID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("Betriebssystem Codename",30)." = ".str_pad($Codename,30)."   \n";
		$PrintLn.="   ".str_pad("Anzahl Hotfix",30)." = ".str_pad(GetValue($HotfixID),30)."   (".date("d.m H:i",IPS_GetVariable($HotfixID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("External IP Adresse",30)." = ".str_pad(GetValue($ExternalIP),30)."   (".date("d.m H:i",IPS_GetVariable($ExternalIP)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("Memory Available",30)." = ".str_pad(GetValue($MemAvailableID),30)."   (".date("d.m H:i",IPS_GetVariable($MemAvailableID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("IPS Uptime",30)." = ".str_pad(GetValue($UptimeID),30)."   (".date("d.m H:i",IPS_GetVariable($UptimeID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("IPS Version",30)." = ".str_pad(GetValue($VersionID),30)."   (".date("d.m H:i",IPS_GetVariable($VersionID)["VariableChanged"]).") \n";

        $PrintHtml .= '<tr><td>Hostname</td><td>'.GetValue($HostnameID).'</td></tr>';
        $PrintHtml .= '<tr><td>Betriebssystem Name</td><td>'.GetValue($SystemNameID).'</td></tr>';
        $PrintHtml .= '<tr><td>Betriebssystem Version</td><td>'.GetValue($SystemVersionID).'</td></tr>';
        $PrintHtml .= '<tr><td>Betriebssystem Codename</td><td>'.$Codename.'</td></tr>';
        $PrintHtml .= '<tr><td>Anzahl Hotfix</td><td>'.GetValue($HotfixID).'</td></tr>';
        $PrintHtml .= '<tr><td>External IP Adresse</td><td>'.GetValue($ExternalIP).'</td></tr>';
        $PrintHtml .= '<tr><td>Memory Available</td><td>'.GetValue($MemAvailableID).'</td></tr>';
        $PrintHtml .= '<tr><td>IPS Uptime</td><td>'.GetValue($UptimeID).'</td></tr>';
        $PrintHtml .= '<tr><td>IPS Version</td><td>'.GetValue($VersionID).'</td</tr>';
        $PrintHtml .= '<tr>'.$this->updateUser().'</tr>';                            // bei der Anzeige schnell ein paar Werte schreiben
        if (dosOps::getKernelPlattform() == "WINDOWS")
            {
            $PrintHtml .= '<tr><td>Java Version</td><td>'.GetValue($VersionJavaID).'</td></tr>';
    		$PrintLn   .= "   ".str_pad("Java Version",30)." = ".str_pad(GetValue($VersionJavaID),30)."   (".date("d.m H:i",IPS_GetVariable($VersionJavaID)["VariableChanged"]).") \n";
            }
        if (GetValue($tailScaleInfoID) != "disabled")
            {
            $PrintHtml .= '<tr><td>TailScale</td><td>installed</td></tr>';
    		$PrintLn   .= "   ".str_pad("TailScale",30)." = VPN installed   (".date("d.m H:i",IPS_GetVariable($VersionJavaID)["VariableChanged"]).") \n";
            }
        $PrintHtml .= '<tr><td align="right" colspan="2"><font size="-1">last update on '.$latestChange.'</font></td></tr>';    
        $PrintHtml .='</table>';
        
		if ($html) return ($PrintHtml);
        else return ($PrintLn);
		}

    /* Application installed
     * Check if Directory is available
     */
    public function appInstalledWin($app,$debug=false) 
        {
        $dirs = ['C:/Program Files','C:/Program Files (x86)'];
        if ($debug) echo "Look for $app:\n";
        $found = $this->dosOps->dirAvailable($app,$dirs,false);         // true mit Debug
        if ($found)
            {
            $appdir=$found.$app."\\"; 
            return ($appdir);               
            }
        else return (false);
        }

    /* get Java version primarily by reading dirs 
     * and if debug by calling java
     *
     */
    public function getJavaVersion($dir,$debug=false) 
        {
        $result = $this->dosOps->readdirToArray($dir,["Detailed"=>false,"Dironly"=>true],0,$debug);
        $javaVersion=false;
        foreach ($result as $entry) 
            {
            if ($javaVersion==false) $javaVersion=$entry;
            else                         
                {
                if ($entry != "latest")
                    {
                    echo "more Java Versions are available. Here : $entry \n";
                    }
                }
            }
        if ($javaVersion && $debug)
            {
            $javadir=$dir."$javaVersion\\bin\\";
            $resultSystemInfo=$this->sysOps->ExecuteUserCommand($javadir."java.exe","-version", false, true);
            echo "Execute this $javadir"."java.exe"." Result \n$resultSystemInfo \n";
            }
        return($javaVersion);            
        }

    /* get TailScale Status
     *
     */
    public function getTailScaleStatus($dir,$debug=false)
        {
        $resultSystemInfo=$this->sysOps->ExecuteUserCommand($dir."tailscale.exe","status", false, true);
        if ($debug) echo "Execute this $dir"."tailscale.exe"." Result \n$resultSystemInfo \n";

        $lines=explode("\n",$resultSystemInfo);
        //print_r($lines);
        $tailScaleServer=array();
        $num=0;
        foreach ($lines as $line)
            {
            $params=explode(" ",$line);
            foreach ($params as $id=>$param) 
                {
                //echo "\"$param\" ";
                if ($param=="") unset($params[$id]);
                }
            //print_r($params);
            $sub=0; $tailState=false;
            foreach ($params as $id=>$param) 
                {
                if ($debug) echo "\"$param\" ";
                switch ($sub)
                    {
                    case 0:
                        $tailScaleServer[$num]["IP"]=$param;
                        break;
                    case 1:
                        $tailScaleServer[$num]["NAME"]=$param;
                        break;
                    case 2:
                        $tailScaleServer[$num]["USER"]=$param;                            
                        break;
                    case 3:
                        $tailScaleServer[$num]["SYSTEM"]=$param;                            
                        break;
                    case 4:
                        //if ($debug) echo "Status of ".$tailScaleServer[$num]["NAME"]." is $param \n";
                        if ($param=="active;") $tailState="active";
                        else $tailState=$param; 
                        $tailScaleServer[$num]["STATUS"]=$tailState;                            
                        $tailScaleServer[$num]["INFO"]="";                            
                        break;                            
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                    case 10:
                        if ($tailState=="active") $tailScaleServer[$num]["INFO"].=$param." ";                                                    
                        break;
                    default:
                        break;
                    }
                $sub++;
                }
            if ($debug) echo "\n";
            $num++;
            }
        return($tailScaleServer);
        }

    /****************************************************************************************************************/

	/**
	 * @public
	 *
	 * Wenn device_ping zu oft fehlerhaft ist wird das Gerät rebootet, erfordert einen vorgelagerten Schalter und eine entsprechende Programmierung
	 *
	 * Übergabe nun das Config file vom Operation Center, LED oder DENON, identifier für IPADRESSE oder IPADR
     *    nur den Teil der Config übergeben: Operationcenter_Configuration["LED"]
     *
     * wie bei device_ping, kann alle 5 Minuten oder stundenweise aufgerufen werden
     * RebootCtr zählt nun in Minuten für mehr Transparenz
     * Logging kann ein/aus geschaltet sein
     *
     * wenn im Config neue Parameter hinzugefügt werden, sollten sich diese automatisch anlegen - no install
	 *
	 */
	function device_checkReboot($device_config, $device, $identifier, $debug=false)
		{
		foreach ($device_config as $name => $config)
			{
			//print_r($config);
			if ( (isset ($config["NOK_HOURS"])) || (isset ($config["NOK_MINUTES"])) )
				{
                $RebootID = @IPS_GetObjectIDByName($device."_".$name,$this->categoryId_RebootCtr);
                /* Auto Install, neue ping Abfragen werden automatisch angelegt, geloeschte allerdings nicht entfernt ! */
                if ($RebootID===false) $RebootID = CreateVariableByName($this->categoryId_RebootCtr, $device."_".$name, 1); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */                    
				if (AC_GetLoggingStatus($this->archiveHandlerID,$RebootID) === false)
					{ // nachtraeglich Loggingstatus setzen
					AC_SetLoggingStatus($this->archiveHandlerID,$RebootID,true);
					AC_SetAggregationType($this->archiveHandlerID,$RebootID,0);
					IPS_ApplyChanges($this->archiveHandlerID);
					}
                else
                    {
            		$werte = AC_GetLoggedValues($this->archiveHandlerID, $RebootID, time()-30*24*60*60, time(),1000); 
                    if ($debug) 
                        {
                        echo "Aufgezeichnete Werte für $name über das Verhalten des Reboot Switch Counters:\n";
                        //print_r($werte);
                        if (count($werte))
                            {
                            foreach ($werte as $wert)
                                {
                                //print_r($wert);
                                echo "    ".date("d.m.Y H:i:s",$wert["TimeStamp"]);
                                echo  "  ".$wert["Value"]."   ".$wert["Duration"]."\n";
                                }
                            }
                        }
                    }
				$reboot_ctr = GetValue($RebootID);
				if (isset ($config["NOK_MINUTES"])) /* der RebootCtr zählt nun in Minuten, damit wird die Funktion transparenter, maxCount entsprechend anpassen */
                    {
                    //$maxCount = (integer)ceil($config["NOK_MINUTES"]/5);
                    $maxCount = $config["NOK_MINUTES"];
                    }
                else $maxCount = $config["NOK_HOURS"]*60;
                if ($debug) echo "   Reboot ?  $reboot_ctr > $maxCount ?\n"; print_r($config);
				if ($reboot_ctr != 0)
					{
					if ($reboot_ctr > $maxCount)
						{
						if (isset ($config["REBOOTSWITCH"]))
							{
                            $oidSwitch=false; $doIpsHeat=false;
							$SwitchName = $config["REBOOTSWITCH"];
                            $forceInteger=(integer)$SwitchName;
                            if ((is_integer($SwitchName)) || ($forceInteger > 0)) 
                                {
                                $oidSwitch=true;
                                if ($forceInteger > 0) $SwitchName = $forceInteger;
                                }
                            if ($debug) echo "    Rebootswitch für $name ist gesetzt. Name \"$SwitchName\" \n";
                            if ($oidSwitch)
                                {
                                //IPSLogger_Inf(__file__, "OID.");    
                                if ($debug) echo "OID $SwitchName\n";
                                if (IPS_VariableExists($SwitchName))
                                    {
                                    IPSLogger_Inf(__file__, "Reboot Switch OID $SwitchName.");    
                                    SetValue($SwitchName,false);
                                    sleep(2);
                                    SetValue($SwitchName,true);
                                    }
                                }
                            elseif (isset ($installedModules["IPSLight"]))
                                {
                                //IPSLogger_Inf(__file__, "IPSLight.");    
    							//include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
                                IPSUtils_Include ("IPSLight.inc.php","IPSLibrary::app::modules::IPSLight");
                       			$lightManager = new IPSLight_Manager();
			                    $switchId = @$lightManager->GetSwitchIdByName($lightName);
                                if ($switchId)
                                    {
                                    IPSLogger_Inf(__file__, "Reboot Switch IPSLight $SwitchName.");    
	    						    IPSLight_SetSwitchByName($SwitchName,false);
		    					    sleep(2);
			    				    IPSLight_SetSwitchByName($SwitchName,true);
                                    }
                                else $doIpsHeat=true;           // IPSLight installiert aber der Variablenname ist nicht mehr definiert
                                }
                            else $doIpsHeat=true;               // kein IPSLight mehr installiert                                
                            if ($doIpsHeat)
                                {
    							//include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
                                IPSUtils_Include ("IPSHeat.inc.php","IPSLibrary::app::modules::Stromheizung");
                                IPSLogger_Inf(__file__, "Reboot Switch IPSHeat $SwitchName.");    
                                IPSHeat_SetSwitchByName($SwitchName,false);
                                sleep(2);
                                IPSHeat_SetSwitchByName($SwitchName,true);
                                }
                            IPSLogger_Inf(__file__, "-------------------Reboot Switch $SwitchName erfolgt.");    
							if (isset ($config["NOK_MINUTES"])) $logMessage = $device."_".$name." wird seit $reboot_ctr Minuten nicht erreicht. Reboot ".$SwitchName." gerade erfolgt.";
                            else $logMessage = $device."_".$name." wird seit ".round($reboot_ctr/60,0)." Stunden nicht erreicht. Reboot ".$SwitchName." gerade erfolgt";
                            if ($debug) echo $logMessage."\n";
                            $this->log_OperationCenter->LogMessage($logMessage);
                            $this->log_OperationCenter->LogNachrichten($logMessage);
							}
						else
							{
                            $escalation=ceil($maxCount/$reboot_ctr);
                            if ($escalation<5)
                                {   /* solange nicht 5 mal mehr überschritten, Nachricht jede Stunde/5Minuten ausgeben, dann nur mehr sechsmal am Tag/jede Stunde */
                                if (isset ($config["NOK_MINUTES"])) $logMessage=$device."_".$name." wird seit ".($reboot_ctr*5)." Minuten nicht erreicht.";
                                else $logMessage=$device."_".$name." wird seit ".$reboot_ctr." Stunden nicht erreicht.";
                                if ( (isset($config["LOGGING"])) && ($config["LOGGING"]==false) )
                                    {
                                    }
                                else
                                    {
                                    $this->log_OperationCenter->LogMessage($logMessage);
                                    $this->log_OperationCenter->LogNachrichten($logMessage);
                                    }
                                }
                            elseif ($escalation<25)
                                {   /* die nächsten 100 Stunden/500 Minuten, Nachricht jede vierte Stunde/jede Stunde ausgeben, dann nur mehr einmal am Tag/jede vierte Stunde */
                                if (($reboot_ctr%4)==0)
                                    {
                                    if (isset ($config["NOK_MINUTES"])) $logMessage=$device."_".$name." wird seit ".($reboot_ctr*5)." Minuten nicht erreicht.";
                                    else $logMessage=$device."_".$name." wird seit ".$reboot_ctr." Stunden nicht erreicht.";
                                    if ( (isset($config["LOGGING"])) && ($config["LOGGING"]==false) )
                                        {
                                        }
                                    else
                                        {
                                        $this->log_OperationCenter->LogMessage($logMessage);
                                        $this->log_OperationCenter->LogNachrichten($logMessage);							
                                        }
                                    }
                                }
                            elseif (($reboot_ctr%24)==0)            // nur mehr jeden Tag/alle 2 Stunden weitergeben
                                {
                                if (isset ($config["NOK_MINUTES"])) 
                                    {
                                    $hours=round($reboot_ctr/12,0);
                                    $logMessage=$device."_".$name." wird seit ".$hours." Stunden nicht erreicht.";
                                    }
                                else 
                                    {
                                    $days=round($reboot_ctr/24,0);
                                    $logMessage=$device."_".$name." wird seit ".$days." Tagen nicht erreicht.";                                
                                    }
                                if ( (isset($config["LOGGING"])) && ($config["LOGGING"]==false) )
                                    {
                                    }
                                else
                                    {
                                    $this->log_OperationCenter->LogMessage($logMessage);
                                    $this->log_OperationCenter->LogNachrichten($logMessage);                                
                                    }
                                }
							}	
						}
					else        /* maxcount Minuten noch nicht überschritten */
						{
						echo $device."_".$name." wird NICHT erreicht ! Zustand seit ".$reboot_ctr." Minuten. ".($maxCount-$reboot_ctr)." Minuten bis zum Reboot.\n";
						}
					}       // Reboot Counter zählt bereits hoch
				}           // NOK Auswertung in Stunden oder 5 Minuten angefordert
			}               // ende foreach
		}                   // ende function

    /***************************************************************************************************************
    *
    * Block im OperationCenter der MAC Adressen, IP Adressen und DomainNames behandelt
    *
    *
    *
    */

	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes macTable
     * verwendet arp -a Befehl, Ausgabe in etwa:
         [9] => Schnittstelle: 10.0.0.124 --- 0x10
        [10] =>   Internetadresse       Physische Adresse     Typ
        [11] =>   10.0.0.1              90-09-d0-29-c9-64     dynamisch
        [12] =>   10.0.0.35             00-11-32-4b-d0-86     dynamisch
     * wird von construct bereits aufgerufen
	 *
	 */
	function create_macipTable($subnet,$printHostnames=false,$debug=false)
		{
		$subnetok=substr($subnet,0,strpos($subnet,"255"));
		//echo "Finde in ".$subnet." den ersten 255er :".strpos($subnet,"255")."\n";
		$ergebnis=""; $print_table="";
		$ipadressen=LogAlles_Hostnames();   /* lange Liste in AllgemeineDefinitionen */
		unset($catch);
		exec('arp -a',$catch);
		foreach($catch as $line)
   			{
   			if (strlen($line)>0)            // Leerzeilen ignorieren
				{
			   	$result=trim($line);
	   			$result1=substr($result,0,strpos($result," ")); /* zuerst IP Adresse */
		   		$result=trim(substr($result,strpos($result," "),100));
	   			$result2=substr($result,0,strpos($result," ")); /* danach MAC Adresse */
		   		$result=trim(substr($result,strpos($result," "),100));
				if ($result1=="10.0.255.255") { break; }
				if ($debug) echo "*** ".str_pad($line,80)." Result:  ".$result1." SubnetOk: ".$subnetok." SubNet: ".$subnet." ".strlen($result1)."\n";
				if ( (strlen($result1)>0) && ((strlen($subnetok)>0) ) )
					{
					if (strpos($result1,$subnetok)===false)
					   	{
				   		}
					else
					   	{
			   			//echo $line."\n";
						if (is_numeric(substr($result1,-1)))   /* letzter Wert in der IP Adresse wirklich eine Zahl */
							{
							$ergebnis.=$result1.";".$result2;
							$print_table.=$line;
							$found=false;
							foreach ($ipadressen as $ip)
							   	{
						   		if ($result2==$ip["Mac_Adresse"])
		   				   			{
									$ergebnis.=";".$ip["Hostname"].",";
									$print_table.=" ".$ip["Hostname"]."\n";
									$found=true;
									}
								}
							if ($found==false)
								{
								$ergebnis.=";none,";
								$print_table.=" \n";
								}
							}
						}
					} // nur wenn Auswertung ueberhaupt Sinn macht
				}
		    }           // ende foreach
        if ($debug) echo $ergebnis;
		$ergebnis_array=explode(",",$ergebnis);
		$result_array=array();
		$mactable=array();
		foreach ($ergebnis_array as $ergebnis_line)
			{
			//echo $ergebnis_line."\n";
			$result_array=explode(";",$ergebnis_line);
			//print_r($result_array);
			if (sizeof($result_array)>2)
			   {
			   if ($result_array[1]!='ff-ff-ff-ff-ff-ff')
			      {
					$mactable[$result_array[1]]=$result_array[0];
					}
				}
			}
		if ($printHostnames==true)
		   {
			return ($print_table);
			}
		else
		   {
			return($mactable);
			}
		}

	/**
	 * @public
	 *
	 * Auslesen des OperationCenter Objektes macTable
	 *
	 */
	function get_macipTable()
		{
		return($this->mactable);
		}


	/**
	 *
	 * Alle IP Adressen und Domain Names herausfinden mit nslookup und LogAlles_Hostnames
	 * geht den Mac Table durch und macht für jede IP Adresse einen nslookup
     * nslookup für MAC Adressen von denen ich mit arp -a die IP Adressen herausgefunden habe ist relativ sinnlos
     * daher auch die selbstgeschriebene Tabelle konsultieren
     * Ergebnis ist eine Tabelle indexiert nach MAC Adressen mit den Spalten
     *      IP, DNSName, IP_Adresse, Hostname, Shortname 
     *    
	 */

	function get_macipdnsTable($debug=false)
		{
        $ipTable=array();
        $macTable=array();
        foreach ($this->mactable as $mac => $ip)            // die Übung mit nslookup für MAC Adressen von denen ich mit arp -a die IP Adressen herausgefunden habe ist relativ sinnlos
            {
            //echo "     ".$mac."   ".$ip."   \n";
            unset($catch);
            $ergebnis=array();
            exec('nslookup '.$ip,$catch);                   // lokale IP Adressen findet man selten im DNS Archiv des Routers        
            if ($debug) print_R($catch);
            foreach ($catch as $entry)
                {
                $entries=explode(":",$entry);
                if (sizeof($entries)>1)
                    {
                    $ergebnis[$entries[0]]=$entries[1];
                    }
                }
            if ($debug) print_r($ergebnis);         //     [Server] =>   at-vie12c-cns02.chello.at,     [Address] =>   212.186.211.21
            //print_r($entries);
            //print_r($catch);

            $macAdresse=strtoupper($mac);
            if (isset($ergebnis["Name"]) )                          // es kommt nur Server und Address zurück
                {
                $ipAdresse=trim($ergebnis["Address"]);
                $ipName=trim($ergebnis["Name"]);
                
                $ipTable[$ipAdresse]["DNSname"]=$ipName;
                $ipTable[$ipAdresse]["MAC"]=$macAdresse;
                
                $macTable[$macAdresse]["IP"]=$ipAdresse;
                $macTable[$macAdresse]["DNSname"]=$ipName;
                }
            else 
                {
                $ipTable[$ip]["MAC"]=$macAdresse;
                $macTable[$macAdresse]["IP"]=$ip;
                }	
            }
			
        //print_r($ipTable);

        $addIPtable=1;			/* 0 HostNamen Tabelle nicht dazunehmen 1 für bekannte MAC Adressen 2 für alle MAC Adressen */
        if ($addIPtable>0)
            {
            $ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeine Definitionen */
            foreach ($ipadressen as $shortname => $ipadresse)
                {
                $macAdresse=strtoupper($ipadresse["Mac_Adresse"]);
                if ( (isset($macTable[$macAdresse])) && ($addIPtable<2) )
                    {
                    $macTable[$macAdresse]["Shortname"]=$shortname;
                    $macTable[$macAdresse]["IP_Adresse"]=$ipadresse["IP_Adresse"];
                    $macTable[$macAdresse]["Hostname"]=$ipadresse["Hostname"];
                    }
                }
            //print_r($ipadressen);
            //print_r($macTable);
            }

        ksort($macTable);
	
        return($macTable);
		}

	/**
	 *
	 * Alle IP Adressen als html Tabelle ausgeben, returns string
	 * nutzt mactable variable
	 */

    function writeIpTable($macTable,$collumns) 
        {
		$str = "<table width='90%' align='center'>"; 
		$head=true;
		foreach ($macTable as $mac => $Line) 
			{
			if ($head)
				{
				$str.="<tr>";	
				foreach ($collumns as $j => $entry)
					{
					//echo $this->collumns[$j]."  ";
					$str.="<td><b>".$entry.'</b></td>';
					}
				$head=false;
				$str.='</tr>';	
				}			
			//echo "\n";		
			$str.="<tr>";	
			foreach ($collumns as $j => $entry)
				{
				//print_r($Line);
				if ( isset($Line[$entry]) )	$str.="<td>".$Line[$entry].'</td>';
				elseif ($entry=="index") $str.="<td>".$mac.'</td>';
				else $str.='<td></td>';
				}
			$str.='</tr>';	
			//echo "\n";	
			} 
		$str.='</table>';
        
        return ($str);
        }

	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes, verarbeitet die beiden Konfigurationstabellen
	 *          LogAlles_Hostnames
     *          LogAlles_Manufacturers
     * anhand des macTables (arp Befehl im lokalen netzwerk) wird ein texttabelle erstellt und ausgegeben
	 */
	function find_HostNames($debug=false)
		{
		$ergebnis="";
		$ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeinde Definitionen */
		$manufacturers=LogAlles_Manufacturers();   /* lange Liste in Config */
        if ($debug) $file = file_get_contents("https://gist.githubusercontent.com/aallan/b4bb86db86079509e6159810ae9bd3e4/raw/846ae1b646ab0f4d646af9115e47365f4118e5f6/mac-vendor.txt");
		foreach ($this->mactable as $mac => $ip )
		   {
		   $result="unknown"; $result2="";
		   foreach ($ipadressen as $name => $entry)
		      {
		      //echo "Vergleiche ".$entry["Mac_Adresse"]." mit ".$mac."\n";
		      if (strtoupper($entry["Mac_Adresse"])==strtoupper($mac))
					{
					$result=$name;
					$result2=$entry["Hostname"];
					}
    	      }
    	   $manuID=substr($mac,0,8);
           if ($debug)
                { 
                $vendor = $this->get_mac_vendor_simple($mac);
                echo "The vendor for MAC address $mac is: " . $vendor;
                $manufactorID=strtoupper(str_replace("-", "", $manuID));
                $pos=strpos($file,$manufactorID);
                echo "  $manuID   \"$manufactorID\"  $pos\n";
                }
    	   if (isset ($manufacturers[$manuID])==true) { $manuID=$manufacturers[$manuID]; }
		   if ($debug) echo "   ".$mac."   ".str_pad($ip,12)." ".str_pad($result,12)." ".str_pad($result2,20)."  ".$manuID."\n";
		   $ergebnis.="   ".$mac."   ".str_pad($ip,12)." ".str_pad($result,12)." ".str_pad($result2,20)."  ".$manuID."\n";
		   }
		if ($debug) echo "\n\n";
		return ($ergebnis);
	   }

    function get_mac_vendor_simple($mac_address) {
        // Construct the API URL
        $url = "https://api.macvendors.com/" . urlencode($mac_address);

        // Use file_get_contents to make the request
        $vendor_info = @file_get_contents($url);

        // Check for success or failure
        if ($vendor_info === FALSE) {
            // Handle API call failure (e.g., vendor not found)
            return "Vendor not found or API error";
        } else {
            return $vendor_info;
        }
    }


    /****************************************************************************************************************/

	/**
	 * Zusammenfassung der ActionButtons der class OperationCenter
	 *
	 * derzeit sind es die ActionButtons der SNMP Router Erfassung
	 *
	 */
	
	function get_ActionButton()
		{	
		$actionButton=array();
		
		foreach ($this->oc_Configuration['ROUTER'] as $router)
			{
	        if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
	            {
	
	            }
	        else
	            {
				//echo "get_ActionButton: Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";				
	        	$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
			    if ($router_categoryId !== false)
					{
		            switch (strtoupper($router["TYP"]))
	    	            {
				        case 'B2368':
			        	case 'RT1900AC':
							//print_r($router);
							if ( (isset($router["READMODE"])) && (strtoupper($router["READMODE"])=="SNMP") )			//  
								{	
								$fastPollId=@IPS_GetObjectIDByName("SnmpFastPoll",$router_categoryId);				// FastPoll Kategorie anlegen
								$SchalterFastPoll_ID=@IPS_GetObjectIDByName("SNMP Fast Poll",$fastPollId);		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
								$actionButton[$SchalterFastPoll_ID]["OperationCenter"]["ActivateTimer"]=true;
								}
	                    	break;
						}          // ende switch
	                }
				}
			}
        /* es gibt im SysTablePing noch einen ActionButton   */
    	$categoryId_SysPing         = @IPS_GetObjectIDByName('SysPing',   $this->CategoryIdData);
        $categoryId_SysPingControl  = @IPS_GetObjectIDByName('SysPingControl',   $categoryId_SysPing);
    	$SysPingSortTableID         = @IPS_GetObjectIDByName("SortPingTable", $categoryId_SysPingControl ); 
        $SysPingUpdateID            = @IPS_GetObjectIDByName("Update", $categoryId_SysPingControl); 
                
        if ($SysPingSortTableID)
            {
            $actionButton[$SysPingSortTableID]["Monitor"]["SysPingTable"]=true;
            $actionButton[$SysPingUpdateID]["Update"]["SysPingUpdate"]=true;
            }

        /* weitere Tabs, wie DoctorBag -> RemoteAccess */
        $categoryId_RemoteAccess    = @IPS_GetObjectIDByName('RemoteAccess',   $this->CategoryIdData); 
        $RemoteAccessUpdateID            = @IPS_GetObjectIDByName("Update", $categoryId_RemoteAccess);         
        if ($RemoteAccessUpdateID)
            {
            $actionButton[$RemoteAccessUpdateID]["RemoteAccess"]["Update"]=true;

            }
        return($actionButton);
		}

	/**
	 * schreibt die gestrigen Download/Upload und Total Werte von einem MR3430 Router
	 * dazu wird das von imacro eingelesene Textfile geparsed
	 *
	 * Werte werden aus dem vorher ausgelesenem html file verzeichnis ausgewertet
	 *
	 */
	 
	function write_routerdata_MR3420($router)
		{
        if (isset($router["DownloadDirectory"])) $downloadDir=$router["DownloadDirectory"];
        else $downloadDir = $this->oc_Setup["DownloadDirectory"];

	    $verzeichnis=$downloadDir."report_router_".$router['TYP']."_".$router['NAME']."_files/";
        echo "Aufruf write_routerdata_MR3420 , Datei in $verzeichnis : \n";            
		if ( is_dir ( $verzeichnis ))
			{
			echo "Auswertung Dateien aus Verzeichnis : ".$verzeichnis."\n";
			$parser=new parsefile($this->CategoryIdData);
			$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
			if ($router_categoryId==false)
			   {
				$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
				IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
				IPS_SetParent($router_categoryId,$this->CategoryIdData);
				}
			$ergebnis=array();
			$ergebnis=$parser->parsetxtfile($verzeichnis,$router['NAME']);
			//print_r($ergebnis);
			$summe=0;
			foreach ($ergebnis as $ipadresse)
			   {
			   $MBytes=(float)$ipadresse['Bytes']/1024/1024;
			   echo "       ".str_pad($ipadresse['IPAdresse'],18)." mit MBytes ".$MBytes."\n";
  				if (($ByteID=@IPS_GetVariableIDByName("MBytes_".$ipadresse['IPAdresse'],$router_categoryId))==false)
     				{
				  	$ByteID = CreateVariableByName($router_categoryId, "MBytes_".$ipadresse['IPAdresse'], 2);
					IPS_SetVariableCustomProfile($ByteID,'MByte');
					AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
					AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
					IPS_ApplyChanges($this->archiveHandlerID);
					}
			  	SetValue($ByteID,$MBytes);
				$summe += $MBytes;
				}
			echo "Summe   ".$summe."\n";
   			if (($ByteID=@IPS_GetVariableIDByName("MBytes_All",$router_categoryId))==false)
     			{
			  	$ByteID = CreateVariableByName($router_categoryId, "MBytes_All", 2);
				IPS_SetVariableCustomProfile($ByteID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
				AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
		  	SetValue($ByteID,$MBytes);
			}
	   	$verzeichnis=$downloadDir."report_router_".$router['TYP']."_".$router['NAME']."_Statistics_files/";
		if ( is_dir ( $verzeichnis ))
			{
			echo "Auswertung Dateien aus Verzeichnis : ".$verzeichnis."\n";
			$ergebnis=array();
			$ergebnis=$parser->parsetxtfile_statistic($verzeichnis,$router['NAME']);
			$summe=0;
			$MBytes=(float)$ergebnis['RxBytes']/1024/1024;
			echo "       RxBytes mit MBytes ".$MBytes."\n";
			if (($ByteID=@IPS_GetVariableIDByName("Download",$router_categoryId))==false)
  				{
			  	$ByteID = CreateVariableByName($router_categoryId, "Download", 2);
				IPS_SetVariableCustomProfile($ByteID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
				AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
		  	SetValue($ByteID,$MBytes);
			$summe += $MBytes;
			$MBytes=(float)$ergebnis['TxBytes']/1024/1024;
			echo "       TxBytes mit MBytes ".$MBytes."\n";
			if (($ByteID=@IPS_GetVariableIDByName("Upload",$router_categoryId))==false)
  				{
			  	$ByteID = CreateVariableByName($router_categoryId, "Upload", 2);
				IPS_SetVariableCustomProfile($ByteID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
				AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
		  	SetValue($ByteID,$MBytes);
			$summe += $MBytes;
			if (($ByteID=@IPS_GetVariableIDByName("Total",$router_categoryId))==false)
  				{
			  	$ByteID = CreateVariableByName($router_categoryId, "Total", 2);
				IPS_SetVariableCustomProfile($ByteID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$ByteID,true);
				AC_SetAggregationType($this->archiveHandlerID,$ByteID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
		  	SetValue($ByteID,$summe);
			}
		}

	/**
	 * schreibt die gestrigen Download/Upload und Total Werte von einem MBRN3000 Router
	 *
	 * Werte werden direkt aus dem Router ausgelesen
	 *
	 */
	function write_routerdata_MBRN3000($router, $debug=false)
		{
		$ergebnis=array();
		echo "  Daten vom Router ".$router['NAME']. " mit IP Adresse ".$router["IPADRESSE"]." einsammeln. Es werden die Tageswerte von gestern erfasst.\n";
		//$Router_Adresse = "http://admin:cloudg06##@www.routerlogin.com/";
		$Router_Adresse = "http://".$router["USER"].":".$router["PASSWORD"]."@".$router["IPADRESSE"]."/";
		echo "  Routeradresse die aufgerufen wird : ".$Router_Adresse." \n";
		//print_r($router);
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
			{
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		IPSLogger_Dbg(__file__, "Router MBRN3000 Auswertung gestartet, traffic meter von gestern holen.");
		$url=$Router_Adresse."traffic_meter.htm";
		$result=@file_get_contents($url);
		if ($result===false) {
		   echo "  -->Fehler beim holen der Webdatei. Noch einmal probieren. \n";
			$result=@file_get_contents($url);
			if ($result===false) 
				{
			   	echo "   Fehler beim holen der Webdatei. Abbruch. \n";
				IPSLogger_Dbg(__file__, "OperationCenter: Fehler beim Holen der Webdatei. Abbruch.");			   
			   	$fatalerror=true;
			   	}
	  		}
		$result=strip_tags($result);
		//#echo $result;
		$pos=strpos($result,"Period");
		if ($pos!=false)
			{
			$result1=substr($result,$pos,6);       /*  Period  */
	   		$result=substr($result,$pos+7,1500);
			$result1=$result1.";".trim(substr($result,20,20));    /* Connection Time  */
			$result=substr($result,140,1500);
			$result1=$result1.";".trim(substr($result,20,40));    /* Upload */
			$result=substr($result,40,1500);
			$result1=$result1.";".trim(substr($result,20,30));    /* Download  */
			$result=substr($result,30,1500);
			$result1=$result1.";".trim(substr($result,20,40))."\n";  /*  Total  */
			$result=substr($result,50,1500);
			$result1=$result1.trim(substr($result,10,30));        /* Today   */
			$result=substr($result,20,1500);
			$result1=$result1.";".trim(substr($result,20,30));    /* Today Connection Time */
			$result=substr($result,30,1500);
			$result1=$result1.";".trim(substr($result,10,30));    /* Today Upload */
			$result=substr($result,30,1500);
			$result1=$result1.";".trim(substr($result,10,30));    /* Today Download */
			$result=substr($result,30,1500);
			$result1=$result1.";".trim(substr($result,10,30))."\n";    /* Today Total */
			$result=substr($result,30,1500);
			$result1=$result1.trim(substr($result,10,30));        /* Yesterday */
			$result=substr($result,20,1500);

			if (($ConnTimeID=@IPS_GetVariableIDByName("ConnTime",$router_categoryId))==false)
  				{
			  	$ConnTimeID = CreateVariableByName($router_categoryId, "ConnTime", 1);
				//IPS_SetVariableCustomProfile($ConnTimeID,'MByte');
				//AC_SetLoggingStatus($this->archiveHandlerID,$ConnTimeID,true);
				//AC_SetAggregationType($this->archiveHandlerID,$ConnTimeID,0);
				//IPS_ApplyChanges($this->archiveHandlerID);
				}

			$result2=trim(substr($result,20,30));
		   $pos=strpos($result2,":");
			$conntime=(int)substr($result2,0,$pos);
			$conntime=$conntime*60+ (int) substr($result2,$pos+1,2);
			if ($debug==false) { SetValue($ConnTimeID,$conntime); }
			$ergebnis["ConnectionTime"]=$conntime;
			echo "    Connection Time in Minuten heute bisher : ".$conntime." sind ".($conntime/60)." Stunden.\n";

			$result1=$result1.";".$result2;    /* Yesterday Connection Time */
			$result=substr($result,30,1500);

			if (($UploadID=@IPS_GetVariableIDByName("Upload",$router_categoryId))==false)
  				{
			  	$UploadID = CreateVariableByName($router_categoryId, "Upload", 2);
				IPS_SetVariableCustomProfile($UploadID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$UploadID,true);
				AC_SetAggregationType($this->archiveHandlerID,$UploadID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}

			$result2=trim(substr($result,10,30));
		   $pos=strpos($result2,".");
			//if ($pos!=false)
			//	{
			//	$result2=substr($result2,0,$pos); /* .",".substr($result2,$pos+1,2);  keine Float Variable */
			//	}
			$Upload= (float) $result2;

			if ($debug==false) { SetValue($UploadID,$Upload); }
			$ergebnis["Upload"]=$Upload;
			echo "     Upload   Datenvolumen gestern ".$Upload." Mbyte \n";;

			$result1=$result1.";".$result2;    /* Yesterday Upload */
			$result=substr($result,30,1500);

			if (($DownloadID=@IPS_GetVariableIDByName("Download",$router_categoryId))==false)
  				{
			  	$DownloadID = CreateVariableByName($router_categoryId, "Download", 2);
				IPS_SetVariableCustomProfile($DownloadID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$DownloadID,true);
				AC_SetAggregationType($this->archiveHandlerID,$DownloadID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}

			$result2=trim(substr($result,10,30));
		   $pos=strpos($result2,".");
			$Download= (float) $result2;
			if ($debug==false) { SetValue($DownloadID,$Download); }
			$ergebnis["Download"]=$Download;
			echo "     Download Datenvolumen gestern ".$Download." MByte \n";
			
			if (($TotalID=@IPS_GetVariableIDByName("Total",$router_categoryId))==false)
  				{
			  	$DownloadID = CreateVariableByName($router_categoryId, "Total", 2);
				IPS_SetVariableCustomProfile($DownloadID,'MByte');
				AC_SetLoggingStatus($this->archiveHandlerID,$DownloadID,true);
				AC_SetAggregationType($this->archiveHandlerID,$DownloadID,0);
				IPS_ApplyChanges($this->archiveHandlerID);
				}
			if ($debug==false) { SetValue($TotalID,($Download+$Upload)); }
			$ergebnis["Total"]=($Download+$Upload);
			echo "     Gesamt Datenvolumen gestern ".($Download+$Upload)." MByte \n";
			}
		else
		   {
		   echo "Daten vom Router sind im falschen Format. Bitte überprüfen ob TrafficMeter am Router aktiviert ist.\n";
			$ergebnis["Fehler"]="Daten vom Router sind im falschen Format";
			}
		return $ergebnis;
		}

	/*
	 *  Routerdaten vom MBRN3000 auslesen, wird vom OperationCenter ausgelesen., eigentlicher AusleseModus wird hier bestimmt
	 *  --> derzeit nicht verwendet
	 *
	 */

	function read_routerdata_MBRN3000($router)
		{
		 /* siehe write_router weiter oben....   */
		 
		write_routerdata_MBRN3000($router);
		
		}

	/*
	 *  Routerdaten vom MR3420 auslesen, wird vom OperationCenter ausgelesen., eigentlicher AusleseModus wird hier bestimmt
	 *  --> derzeit nicht verwendet
	 *
	 */

	function read_routerdata_MR3420($router)
		{
		IPS_ExecuteEX($this->oc_Setup["FirefoxDirectory"]."firefox.exe", "imacros://run/?m=router_".$router['TYP']."_".$router['NAME'].".iim", false, false, 1);
		}

	/*
	 *  Routerdaten vom B2368 auslesen, wird vom OperationCenter ausgelesen., eigentlicher AusleseModus wird hier bestimmt
	 *  
	 *
	 */

	function read_routerdata_B2368($router_categoryId, $host, $community, $binary, $debug=false, $useSnmpLib=false)
		{
        $snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug, $useSnmpLib);							
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.1.0", "wan0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.1.0", "wan0_ifOutOctets", "Counter32");
        $snmp->update(false,"wan0_ifInOctets","wan0_ifOutOctets");  
		}

	/*
	 *  Routerdaten vom RT1900AC auslesen, wird vom OperationCenter ausgelesen., eigentlicher AusleseModus wird hier bestimmt
	 *  blöd ist das die Interface Nummern auch irgendwie von der eingestellten Konfiguration abhängen.
	 *  langfristig auf die Beschreibung wie zB eth0 ausweichen
	 *
	 */

	function read_routerdata_RT1900AC($router_categoryId, $host, $community, $binary, $debug=false, $useSnmpLib=false)
		{
		/* Interface Nummer 4,5,8   4 ist der Uplink */
        $snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug, $useSnmpLib);
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.8", "wlan0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.8", "wlan0_ifOutOctets", "Counter32");
        $snmp->update(false,"eth0_ifInOctets","eth0_ifOutOctets"); /* Parameter false damit Werte geschrieben werden und die beiden anderen Parameter geben an welcher Wert für download und upload verwendet wird */
		}

	function read_routerdata_RT2600AC($router_categoryId, $host, $community, $binary, $debug=false, $useSnmpLib=false)
		{
		/* Interface Nummer 4,6,15   4 ist der Uplink, 6 ist eth2 und Lankabel steckt auf Ethernet LAN Port 1 */
        $snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug, $useSnmpLib);
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");		// Uplink, WAN Port
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");		// Video or Ethernet
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.6", "eth2_ifInOctets", "Counter32");		// Ethernet

        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.6", "eth2_ifOutOctets", "Counter32");

        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.15", "wlan0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.15", "wlan0_ifOutOctets", "Counter32");
        $snmp->update(false,"eth0_ifInOctets","eth0_ifOutOctets"); /* Parameter false damit Werte geschrieben werden und die beiden anderen Parameter geben an welcher Wert für download und upload verwendet wird */
		}

    /* der neueste Synology Router
     *
     *
     */

	function read_routerdata_RT6600AX($router_categoryId, $host, $community, $binary, $debug=false, $useSnmpLib=false)
		{
		/* Interface Nummer 4,6,15   4 ist der Uplink, 6 ist eth2 und Lankabel steckt auf Ethernet LAN Port 1 */
        $snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug, $useSnmpLib);
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");		// Uplink, WAN Port
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.5", "eth1_ifInOctets", "Counter32");		// Uplink, WAN Port 2, Ethernet
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.6", "eth2_ifInOctets", "Counter32");		// Ethernet

        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.5", "eth1_ifOutOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.6", "eth2_ifOutOctets", "Counter32");

        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.15", "wlan0_ifInOctets", "Counter32");
        $snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.15", "wlan0_ifOutOctets", "Counter32");
        $snmp->update(false,"eth0_ifInOctets","eth0_ifOutOctets"); /* Parameter false damit Werte geschrieben werden und die beiden anderen Parameter geben an welcher Wert für download und upload verwendet wird */
		}

	/*
	 *  Routerdaten MBRN3000 direct aus dem Router auslesen,
	 *
	 *  mit actual wird definiert ob als return Wert die Gesamtwerte von heute oder gestern ausgegeben werden sollen
	 *
	 */

	function get_routerdata_MBRN3000($router,$actual=false)
		{
		echo "Daten direkt vom Router ".$router['NAME']. " mit IP Adresse ".$router["IPADRESSE"]." einsammeln. Es werden die aktuellen Tageswerte erfasst.\n";
		$Router_Adresse = "http://".$router["USER"].":".$router["PASSWORD"]."@".$router["IPADRESSE"]."/";
		//print_r($router);
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$url=$Router_Adresse."traffic_meter.htm";
		echo "    -> Routeradresse die aufgerufen wird : ".$url." \n";
		$result=@file_get_contents($url);
		if ($result===false) {
		   echo "Fehler beim holen der Webdatei. Noch einmal probieren. \n";
			$result=@file_get_contents($url);
			if ($result===false) {
			   echo "Fehler beim Holen der Webdatei. Abbruch. \n";
			   return(false);
			   }
	  		}
		$result=strip_tags($result);
		$pos=strpos($result,"Period");
		if ($pos!=false)
			{
			/* Überschriften aus der Tabelle einsammeln, mit Strichpunkt trennen */
			$result_l1=substr($result,$pos,6);       /*  Period  */
			$result=substr($result,$pos+7,1500);
			$result_l1=$result_l1.";".trim(substr($result,20,20));    /* Connection Time  */
			$result=substr($result,140,1500);
			$result_l1=$result_l1.";".trim(substr($result,20,40));    /* Upload */
			$result=substr($result,40,1500);
			$result_l1=$result_l1.";".trim(substr($result,20,30));    /* Download  */
			$result=substr($result,30,1500);
			$result_l1=$result_l1.";".trim(substr($result,20,40));  /*  Total  */
			
			/* jetzt die Werte von heute einsammeln */
			$result=substr($result,50,1500);
			$result_l2=trim(substr($result,10,30));        /* Today   */
			$result=substr($result,20,1500);
			$result2=trim(substr($result,20,30));
			$pos=strpos($result2,":");
			$conntime=(int)substr($result2,0,$pos);
			$conntime=$conntime*60+ (int) substr($result2,$pos+1,2);
			echo " Connection Time von Heute in Minuten : ".$conntime." sind ".round(($conntime/60),2)." Stunden.\n";
			$result_l2=$result_l2.";".trim(substr($result,20,30));    /* Today Connection Time */
			$result=substr($result,30,1500);
			$result2=trim(substr($result,10,30));
			$pos=strpos($result2,".");
			$Upload= (float) $result2;
			echo " Upload Datenvolumen Heute bisher ".$Upload." Mbyte \n";;
			$result_l2=$result_l2.";".trim(substr($result,10,30));    /* Today Upload */
			$result=substr($result,30,1500);
			$result2=trim(substr($result,10,30));
			$pos=strpos($result2,".");
			$Download= (float) $result2;
			echo " Download Datenvolumen Heute bisher ".$Download." MByte \n";
			$result_l2=$result_l2.";".trim(substr($result,10,30));    /* Today Download */
			$result=substr($result,30,1500);
			$result2=trim(substr($result,10,30));
			$pos=strpos($result2,".");
			$Today_Totalload= (float) $result2;
			echo " Gesamt Datenvolumen Heute bisher ".$Today_Totalload." Mbyte \n";
			$result_l2=$result_l2.";".trim(substr($result,10,30));    /* Today Total */

			/* und die Werte von gestern */
			$result=substr($result,30,1500);
			$result_l3=trim(substr($result,10,30));        /* Yesterday */
			$result=substr($result,20,1500);
				$result2=trim(substr($result,20,30));
		   	$pos=strpos($result2,":");
				$conntime=(int)substr($result2,0,$pos);
				$conntime=$conntime*60+ (int) substr($result2,$pos+1,2);
				echo " Connection Time von Gestern in Minuten : ".$conntime." sind ".round(($conntime/60),2)." Stunden.\n";
			$result_l3=$result_l3.";".$result2;    /* Yesterday Connection Time */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Upload= (float) $result2;
				echo " Upload Datenvolumen von Gestern ".$Upload." Mbyte \n";;
			$result_l3=$result_l3.";".$result2;    /* Yesterday Upload */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Download= (float) $result2;
				echo " Download Datenvolumen von Gestern ".$Download." Mbyte \n";
			$result_l3=$result_l3.";".trim(substr($result,10,30));    /* Yesterday Download */
			$result=substr($result,30,1500);
				$result2=trim(substr($result,10,30));
			   $pos=strpos($result2,".");
				$Yesterday_Totalload= (float) $result2;
				echo " Gesamt Datenvolumen gestern bisher ".$Yesterday_Totalload." Mbyte \n";
			$result_l3=$result_l3.";".trim(substr($result,10,30));    /* Today Total */

			echo "****** ".$result_l1." \n";
			echo "****** ".$result_l2." \n";
			echo "****** ".$result_l3." \n";

			if ($actual==false)
			   {
			   return ($Yesterday_Totalload);
			   }
			else
			   {
			   return ($Today_Totalload);
			   }
			}
		}

	/*
	 *  Routerdaten aus der allgemeinen Datenbank auslesen,
	 *
	 *  Allgemeine Routine, sucht die Daten im entsprechenden Verzeichnis, nur Ausgabe auf echo
	 *
	 */

	function get_routerdata($router,$actual=false)
		{
		$ergebnis=0;      // Gesamtdatenvolumen heute oder gestern
		
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "get_routerdata:Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		$result1=array();
		foreach($result as $oid)
			{
            echo "Behandle Variable $oid (".IPS_GetName($oid).":".IPS_GetName(IPS_GetParent($oid)).") Type ".IPS_GetObject($oid)["ObjectType"]."\n";
            if (IPS_GetObject($oid)["ObjectType"]==2)           // Unterkategorien nicht behandeln
                {
                if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
                    {
                    $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000); 
                    //print_r($werte);
                    echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
                    foreach ($werte as $wert)
                        {
                        //echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."\n";
                        echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
                        }
                    $result1[IPS_GetName($oid)]=$oid;
                    }
                else
                    {
                    echo "   ".IPS_GetName($oid)." Variable wird NICHT gelogged.\n";
                    }
                }
			}
		//ksort($result1);
		//print_r($result1);
		if ($actual==false)
			{
			return ($ergebnis);
			}
		}

	/*
	 * Routerdaten Synology RT1900ac/RT2600ac direct als SNMP Werte aus dem Router auslesen,
	 *
	 * mit actual wird definiert ob als return Wert die Gesamtwerte von heute oder gestern ausgegeben werden sollen
	 *
	 * derzeit gibt es keinen aktuellen Wert, da der immer vorher mit SNMP Aufrufen ausgelesen werden muesste
	 * es fehlt SNMP Aufruf ohne logging. Wird in der Aufrufroutine gemacht.
	 *
	 * Variablennamen in der Category sind kodiert und mit _ getrennt. Der erste Teil ist der Name des Ports und der letzte der Status
	 *
	 * Routine legt auch die Kategorie für den Router automatisch in data von OperationCenter an: Router_<RouterName>
	 * es werden alle Objekte in der Kategorie ausgelesen und geprüft ob sie gelogged werden 
	 * Ausgewertet werden sie dann wenn sie ein eth0, ein _ und ein chg im Namen haben. In und Out werden zusammengezählt.
	 * Zusätzlich werden die Archivdaten von Total ausgegeben
	 *
	 */

	function get_routerdata_RT1900($router,$actual=false)
		{
		$ergebnis=0;      // Gesamtdatenvolumen heute oder gestern

		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
			{
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "get_roterdata_RT1900: Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		if ($actual==false)
			{
			foreach($result as $oid)
				{
				$analyze=@AC_GetLoggingStatus($this->archiveHandlerID,$oid);
				//echo "   Analysiere $oid (".IPS_GetName($oid).")  : ".($analyze?"Ja":"Nein")."\n";
				if ($analyze)
					{
					$name=explode("_",IPS_GetName($oid));
					if ($name[sizeof($name)-1]=="chg")
						{
						if ($name["0"]=="eth0") /* In und out von eth0 zusammenzaehlen */
			            	{
				         	$ergebnis+=GetValue($oid)/1024/1024;
			            	$werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
					   		echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
					   		foreach ($werte as $wert)
				   		   		{
				   		   		//echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."  ".round(($wert["Value"]/1024/1024),2)."Mbyte bzw. ".round(($wert["Value"]/24/60/60/1024),2)." kBytes/Sek  \n";
				   		   		echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."  ".round(($wert["Value"]/1024/1024),2)."Mbyte bzw. ".round(($wert["Value"]/24/60/60/1024),2)." kBytes/Sek  \n";
				   	   			}
				         	}
						}
			      	if (IPS_GetName($oid)=="Total")
			      		{
		            	$werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
				   		echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
				   		foreach ($werte as $wert)
			   		   		{
			   		   		//echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."  ".round(($wert["Value"]),2)."Mbyte bzw. ".round(($wert["Value"]/24/60/60*1024),2)." kBytes/Sek  \n";
			   		   		echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."  ".round(($wert["Value"]),2)."Mbyte bzw. ".round(($wert["Value"]/24/60/60*1024),2)." kBytes/Sek  \n";
			   	   			}
						}
			   		}	// ende analyze
			   	}	// ende foreach

			echo "      Historischen Wert von gestern ausgeben, eth0 In und Out sind zusammgezählt : ".$ergebnis."\n";
			}
		else
			{
			$host          = $router["IPADRESSE"];
			$community     = "public";                                                                         // SNMP Community
			$binary        = $this->systemDir."ssnmpq\ssnmpq.exe";    // Pfad zur ssnmpq.exe
			$debug         = true;                                                                             // Bei true werden Debuginformationen (echo) ausgegeben
			$snmp=new SNMP_OperationCenter($router_categoryId, $host, $community, $binary, $debug);
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.10.4", "eth0_ifInOctets", "Counter32");
			$snmp->registerSNMPObj(".1.3.6.1.2.1.2.2.1.16.4", "eth0_ifOutOctets", "Counter32");
			$result=$snmp->update(true);  /* kein Logging */
			foreach ($result as $object)
				{
				$ergebnis+=$object->change;
				}
			echo "      Aktuellen Wert von heute ausgeben, eth0 In und Out sind zusammgezählt : ".$ergebnis."\n";
			}
		return (round($ergebnis,2));
		}


	/*
	 *  Routerdaten MR3420 aus dem datenobjekt statt direct aus dem Router auslesen,
	 *
	 *  Routine obsolet, wird durch get_router_history ersetzt
	 *
	 */

	function get_routerdata_MR3420($router)
		{
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		$ergebnis=0;
		foreach($result as $oid)
		   {
		   if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		      {
		      if (IPS_GetName($oid)=="Total")
		         {
		         $ergebnis=GetValue($oid);
	            $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
			   	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
			   	foreach ($werte as $wert)
		   		   {
		   		   //echo "       Wert : ".str_pad(round($wert["Value"],2),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
		   		   echo "       Wert : ".str_pad(round($wert["Value"],2),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
		   	   	}
					}
		   	}
		   }
		return $ergebnis;
		}

	/*
	 * Routerdaten direct aus dem Archiv auslesen
	 * nimmt die Router Kategorie und sucht nach einer archivierten Variable Total
	 *
	 */

	function get_router_history($router,$start,$duration)
		{
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "get_router_history:Routerdaten liegen in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		$ergebnisPrint="";
		$ergebnis=0;
		$dateOld="";
		foreach($result as $oid)
		   	{
			$analyze=@AC_GetLoggingStatus($this->archiveHandlerID,$oid);
			//echo "   Analysiere $oid (".IPS_GetName($oid).")  : ".($analyze?"Ja":"Nein")."\n";
			if ($analyze)
		      	{
		      	if (IPS_GetName($oid)=="Total")
		         	{
	            	$werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-($start+$duration)*24*60*60, time()-$start*24*60*60,1000);
			   		echo "   ".IPS_GetName($oid)." Variable wird gelogged, vor ".$start." Tagen fuer ".$duration." Tagen ".sizeof($werte)." Werte.\n";
			   		foreach ($werte as $wert)
		   		   		{
                  		if (date("d.m",$wert["TimeStamp"])==$dateOld)
                     		{
                     		//echo "Werte gleich : ".(date("d.m",$wert["TimeStamp"]))."\n";
	                  		}
                  		else
                     		{
     		   		   		//$ergebnisPrint.= "       Wert : ".str_pad(round($wert["Value"],2),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
     		   		   		$ergebnisPrint.= "       Wert : ".str_pad(round($wert["Value"],2),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
                     		$dateOld=date("d.m",$wert["TimeStamp"]);
                     		$ergebnis += $wert["Value"];
                     		}
						}
					}
		   		}
		   }
		echo $ergebnisPrint;
		return round($ergebnis,2);
		}

	/*
	 *
	 *
	 */

	function get_data($oid)
		{
      $werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000);
	  	echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen ".sizeof($werte)." Werte.\n";
	  	foreach ($werte as $wert)
	  	   {
	  	   //echo "       Wert : ".$wert["Value"]." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".$wert["Duration"]."\n";
	  	   echo "       Wert : ".$wert["Value"]." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
	  	   }
		}

	/*
	 *
	 *
	 */

	function sort_routerdata($router)
		{
		$router_categoryId=@IPS_GetObjectIDByName("Router_".$router['NAME'],$this->CategoryIdData);
		if ($router_categoryId==false)
		   {
			$router_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($router_categoryId, "Router_".$router['NAME']); // Kategorie benennen
			IPS_SetParent($router_categoryId,$this->CategoryIdData);
			}
		$result=IPS_GetChildrenIDs($router_categoryId);
		echo "Wir sortieren die Routerdaten in der Kategorie \"Router_".$router['NAME']."\" unter der OID: ".$router_categoryId." \n";
		/* alle mit Archivfunktion werden an den Anfang geschoben und entsprechend alphabetisch sortieren */
		$result1=array();
		foreach($result as $oid)
		   {
		   if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))
		      {
		      //echo "  --- ".substr(IPS_GetName($oid),0,4)."\n";
		      if ((substr(IPS_GetName($oid),0,4))=="MByt")
		         {
					$result1[IPS_GetName($oid)]=$oid;
					}
				else
				   {
					$result1["zzy".IPS_GetName($oid)]=$oid;
				   }
		   	}
		   else
		      {
				$result1["zzz".IPS_GetName($oid)]=$oid;
		      }
		   }
		$i=100;
		ksort($result1);
		foreach($result1 as $oid)
		   {
			IPS_SetPosition($oid,$i);
			$i+=10;
			}
        }
	
		
	private function extractTime($filename)
		{
		$time="default";
		$year=date("Y",time());
		$pos=strpos($filename,$year);
		if ($pos !== false)
			{
			$time=substr($filename,$pos+8,2).":".substr($filename,$pos+10,2).":".substr($filename,$pos+12,2);
			echo "extractTime, Suche : ".$year." in ".$filename." gefunden auf ".$pos." ergibt \"$time\"\n";
			}
        else 
            {
            echo "extractTime, keine Ahnung wo im Filenamen $filename eine Zeitangabe stecken sollte. Hab nach $year gesucht!\n";
            $time="12011966";
            }
		return ($time);
		}			

    function purgeCamFiles($cam_config,$debug=false)
        {
        /* Default Konfiguration herausarbeiten */
        if ( (isset($cam_config["PURGECAMFILES"])) && ($cam_config["PURGECAMFILES"]) )
            {    
            if (isset($cam_config['PURGESIZE'])) $remain=$cam_config['PURGESIZE'];
            else $remain = 14;
            if (isset($cam_config['PURGEAMOUNT'])) $remainFiles=$cam_config['PURGEAMOUNT'];
            else $remainFiles=1000*$remain;
            }
        else 
            {
            $remain=2;
            print_r($cam_config);
            }

        return ($this->purgeFiles($remain,$cam_config['FTPFOLDER'],$debug));
        }


	/*
	 *  Die in einem Ordner pro Tag zusammengefassten Logfiles loeschen.
	 *  Es werden die älteren Verzeichnisse bis zur Anzahl remain geloescht.
     *  Wenn kein Purge definiert ist einfach nur 2 Verzeichnisse stehen lassen. Sonst ist der Server schnell einmal überfüllt
	 * Zusätzlich die Anzahl der Captured Pictures auf 1.000 pro tag limitieren, oder Parameter
	 */

	function PurgeFiles($remain=false, $verzeichnis="", $debug=false)
		{
        /* Default Konfiguration für Purge LogFiles herausarbeiten */
        if ($remain===false)
            {
            if (isset($this->oc_Setup['CONFIG']['PURGESIZE'])) $remain=$this->oc_Setup['CONFIG']['PURGESIZE'];
            else $remain = 14;
            }
		if ($verzeichnis=="") $verzeichnis=IPS_GetKernelDir().'logs/';	

		echo "PurgeFiles: Überschüssige (>".$remain.") Sammel-Verzeichnisse von ".$verzeichnis." löschen. Die letzen ".$remain." Verzeichnisse bleiben.\n";
		//echo "Heute      : ".date("Ymd", time())."\n";
		//echo "Gestern    : ".date("Ymd", strtotime("-1 day"))."\n";

		$count=0;
		$dir=array();
        $dirSize=array();
		
		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $verzeichnis ) )
			{
			// öffnen des Verzeichnisses
			if ( $handle = opendir($verzeichnis) )
				{
				/* einlesen der Verzeichnisses	*/
				while ((($file = readdir($handle)) !== false) )
					{
					if ($file!="." and $file != "..")
						{	/* kein Directoryverweis (. oder ..), würde zu einer Fehlermeldung bei filetype führen */
						//echo "Bearbeite ".$verzeichnis.$file."\n";
						$dateityp=filetype( $verzeichnis.$file );
						if ($dateityp == "dir")
							{
							$count++;
							$dir[]=$verzeichnis.$file;
							if ($debug) echo "   Erfasse Verzeichnis ".$verzeichnis.$file;                              // aufteilen, für bessere Fehlererkennung
                            $dirSize[$file]=$this->dosOps->readdirtoStat($verzeichnis.$file,true);       // true rekursiv
                            if ($debug) 
                                {
                                echo " mit insgesamt ".$dirSize[$file]["files"]." gespeicherten Dateien.\n";                                    
                                //print_r($dirsize);
                                }                       
							}
						}	
					//echo "    ".$file."    ".$dateityp."\n";
					} /* Ende while */
				//echo "   Insgesamt wurden ".$count." Verzeichnisse entdeckt.\n";	

                /* zusätzlich haerausfinden ob die Anzahl von 14.000 Files überschritten wurde, dann auch mehr Verzeichnisse löschen */
                krsort($dirSize);                               // verkehrt rum zählen, die neuesten Dateien zuerst
                $filesize=0; $max=0;
                foreach ($dirSize as $entry)
                    {
                    $filesize += $entry["files"];
                    if ($filesize < 14000) $max++;          // Wenn filesize unter 14000 bleibt ist max gleich gross wie count
                    }
                if ($max < $remain) 
                    {
                    //print_r($dirSize);
                    $remain = $max;             // es gibt 14 Verzeichnisse (=count) aber max wurde nur 10 gross, dann auf 10 Verzeichnisse einkürzen, d.h. 4 verzeichnisse löschen
                    }
				if ($debug) 
                    {
                    echo "   Insgesamt wurden ".$count." Verzeichnisse entdeckt mit insgesamt $filesize Dateien. Es bleiben $remain Verzeichnisse.\n";	
                    } 
				closedir($handle);
				} /* end if dir */
			}/* ende if isdir */
		else
			{
			echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
			}
        if ($debug && false)
            {
            echo "Zusammenfasung, bevor es mit dem Löschen losgeht: ($count - $remain)>0 bedeutet Loeschen !!!\n";    
            print_r($dirSize);    
            }
		if (($count-$remain)>0) 
			{	
			echo "Loeschen von 0 bis ".($count-$remain)."\n";
			for ($i=0;$i<($count-$remain);$i++)
				{
				echo "    Loeschen von Verzeichnis ".$dir[$i]."\n";
				$this->dosOps->rrmdir($dir[$i]);
				}
    		return ($count-$remain);
			} 	
		else return (0);
		}
		
	/* gesammelte Funktionen zur Bearbeitung von Verzeichnissen 
	 *
	 * ein Verzeichnis einlesen und als Array zurückgeben 
	 *
	 */
	
	public function readdirToArray($dir,$recursive=false,$newest=0, $debug=false)
		{
	   	$result = array();

		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $dir ) )
			{		
			$cdir = scandir($dir);
			foreach ($cdir as $key => $value)
				{
				if (!in_array($value,array(".","..")))
					{
					if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
						{
						if ($recursive)
							{ 
							//echo "DirtoArray, vor Aufruf (".memory_get_usage()." Byte).\n";					
							$result[$value]=$this->dirToArray($dir . DIRECTORY_SEPARATOR . $value);
							//echo "  danach (".memory_get_usage()." Byte).  ".sizeof($result)."/".sizeof($result[$value])."\n";
							}
						else $result[] = $value;
						//$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
						}
					else
						{
						$result[] = $value;
						}
					}
				} // ende foreach
			} // ende isdir
		else return (false);
		if ($newest != 0)
			{
			if ($newest<0) 
				{
				rsort($result);
				$newest=-$newest;
				}				
			foreach ($result as $index => $entry)
				{
				if ($index>$newest) unset($result[$index]);
				}
			}
		return $result;
		}		

	/* Routine fürs rekursive aufrufen in readdirtoarray */
	
	private function dirToArray($dir)
		{
	   	$result = array();
	
		$cdir = scandir($dir);
		foreach ($cdir as $key => $value)
			{
			if (!in_array($value,array(".","..")))
				{
				if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
	         		{
					$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
	         		}
	         	else
	         		{
	            	$result[] = $value;
	         		}
	      		}
	   		}
		return $result;
		}
 

	/***************************************************
	 *
	 * kopiert die Scriptfiles auf ein Dropboxverzeichnis um die Files sicherheitshalber auch immer zur Verfügung zu haben
	 * auch wenn Github nicht mehr geht
	 *
	 */

	function CopyScripts($debug=false)
		{
		/* sicherstellen dass es das Dropbox oder das Synology Verzeichnis auch gibt */
		if ($this->oc_Setup['Cloud']['Directory']) $DIR_copyscriptsdropbox = $this->oc_Setup['Cloud']['Directory'];
        else  $DIR_copyscriptsdropbox = $this->oc_Setup['Cloud']['CloudDirectory'];
        $DIR_copyscriptsdropbox = $this->dosOps->correctDirName($DIR_copyscriptsdropbox);			// sicherstellen das ein Slash oder Backslah am Ende ist
        $DIR_copyscriptsdropbox .= 'scripts/'.IPS_GetName(0).'/';
        if ($debug)
            {
            echo "CopyScripts, relevante Configuration mit Cloud Verzeichnis:\n";
            print_r($this->oc_Setup["Cloud"]);
            echo "Zielverzeichnis ist $DIR_copyscriptsdropbox.\n";
            }
        if (strlen($DIR_copyscriptsdropbox)<20) 
            {
            echo "CopyScripts, das Verzeichnis für die Scriptkopien ist mit $DIR_copyscriptsdropbox zu kurz, Abbruch.\n";
            return(false);
            }
        /* sicherstellen dass es das Dropbox Verzeichnis auch gibt */
		$this->dosOps->mkdirtree($DIR_copyscriptsdropbox);

		$count=0;

		$alleSkripte = IPS_GetScriptList();
		//print_r($alleSkripte);


		/* ein includefile mit allen Dateien erstellen, als Inhaltsverzeichnis */
		$includefile='<?php'."\n";
        $includefile.='/* List of all files used in IP Symcon Installation '.IPS_GetName(0)."\n";
        $includefile.=' * generated by OperationCenter CopyScripts on '.date("d.m.Y H:i:s",time())."\n";
        $includefile.=' * used to include all files in Dropbox or Synology Cloud'."\n";
        $includefile.=' * you can include this file with include_once(\'/path/to/'.$DIR_copyscriptsdropbox.'allfiles.ips.php\');'."\n";
        $includefile.=' * then you have all files in your script'."\n";
        $includefile.=' * you can also use this file to find out which files are used'."\n";
        $includefile.=' * and where they are located'."\n";
        $includefile.=' */'."\n\n";
        $includefile.='function getAllFiles()'."\n";
        $includefile.='{'."\n";

        $changedfile='/* List of all changed files used in IP Symcon Installation '.IPS_GetName(0)."\n";
        $changedfile.=' * generated by OperationCenter CopyScripts on '.date("d.m.Y H:i:s",time())."\n";
        $changedfile.=' * used to include all changed files in Dropbox or Synology Cloud'."\n"; 
        $changedfile.=' * you can include this file with include_once(\'/path/to/'.$DIR_copyscriptsdropbox.'changedfiles.ips.php\');'."\n";
        $changedfile.=' * then you have all changed files in your script'."\n";
        $changedfile.=' * you can also use this file to find out which files are changed'."\n";
        $changedfile.=' * and where they are located'."\n";
        $changedfile.=' */'."\n\n";
        $changedfile.='function getChangedFiles()'."\n";
        $changedfile.='{'."\n";

        $includefile.='$fileList = array('."\n";
        $changedfile.='$changedfileList = array('."\n";  
		echo "Alle Scriptfiles werden vom IP Symcon Scriptverzeichnis auf ".$DIR_copyscriptsdropbox." kopiert und in einen Dropbox lesbaren Filenamen umbenannt.\n";
		echo "\n";

		foreach ($alleSkripte as $value)
			{
			/*
			 *  Script Files auf die Dropbox kopieren 
			 */
            $change=false;
			$filename=IPS_GetScriptFile($value);	/* von hier wird kopiert -> source */
			$name=IPS_GetName($value);
			$trans = array("," => "", ";" => "", ":" => "", "/" => ""); /* falsche zeichen aus filenamen herausnehmen */
			$name=strtr($name, $trans);
			$destination=$name."-".$value.".php";		/* name der als Ziel Filename verwendet wird */
			//copy(IPS_GetKernelDir().'scripts/'.$filename,$DIR_copyscriptsdropbox.$destination);
            $sourceLastChange=filemtime(IPS_GetKernelDir().'scripts/'.$filename);
            $destinationLastChange=filemtime($DIR_copyscriptsdropbox.$destination);
            if (!file_exists($DIR_copyscriptsdropbox.$destination))
                {
                echo "   $name : ".IPS_GetKernelDir().'scripts/'.$filename." copy to  $destination , new file \n";
                copy(IPS_GetKernelDir().'scripts/'.$filename,$DIR_copyscriptsdropbox.$destination);
                touch($DIR_copyscriptsdropbox.$destination,$sourceLastChange);   // Datum des Originals übernehmen
                $change=true;
                }
            else
                {
                if ($sourceLastChange > $destinationLastChange)
                    {
                    $yesterday=date("Ymd",time()-24*60*60);
                    if (is_dir($DIR_copyscriptsdropbox.$yesterday)===false) mkdir($DIR_copyscriptsdropbox.$yesterday);
                    echo "   $name : ".IPS_GetKernelDir().'scripts/'.$filename." copy to  $destination , changed file, old file at $yesterday \n";
                    copy($DIR_copyscriptsdropbox.$destination,$DIR_copyscriptsdropbox.$yesterday."/".$destination); // altes File sichern
                    touch($DIR_copyscriptsdropbox.$yesterday."/".$destination,$destinationLastChange);   // Datum des Originals übernehmen
                    copy(IPS_GetKernelDir().'scripts/'.$filename,$DIR_copyscriptsdropbox.$destination);
                    touch($DIR_copyscriptsdropbox.$destination,$sourceLastChange);   // Datum des Originals übernehmen
                    $change=true;
                    }
                else
                    {
                    //echo "   $name : ".IPS_GetKernelDir().'scripts/'.$filename." NOT copied, already exists with same or newer date.\n";
                    }
                }
			
			/* 
			 * Includefile mit allen Filenamen und dem Pfad herstellen 
			 */
			$value1=$value;
			$check="no ";
			/* herausfinden ob ein Dateiname nur eine Nummer ist, dann vollstaendigen Namen und Struktur geben */
			$zahl=array();
			if (preg_match('/\d+/',$filename,$zahl)==1)
				{
				if ($zahl[0]==$value)
					{
					$dir="";
					while (($parent=IPS_GetParent($value1))!=0)
						{
						$Struktur=IPS_GetObject($parent);
						if ($Struktur["ObjectType"]==0) {$dir=IPS_GetName($parent).'/'.$dir;}
						$value1=$parent;
						}
					$destname=$dir.$name.".ips.php";
					$trans = array("," => "", ";" => "", ":" => ""); /* falsche zeichen aus filenamen herausnehmen */
					$destname=strtr($destname, $trans);
					$check="yes";
					}
				else {  $destname=$filename;  	}	
				}
			else
			   {  $destname=$filename;  	}
			//echo $check." ".$count.": OID: ".$value." Verzeichnis ".$filename."   ".$destname."\n";
			$includefile.='\''.$destname.'\','."\n";
            if ($change) $changedfile.='\''.$destname.'\','."\n";
			$count+=1;
			}

		$includefile.=');'."\n";
        $includefile.='return($fileList);'."\n";
        $includefile.= '}'."\n";
        $includefile.="\n".$changedfile;
        $includefile.=');'."\n";
        $includefile.='return($changedfileList);'."\n";
        $includefile.= '}'."\n";
        $includefile.="\n".'?>';

		if ($debug) {
            echo "\n";
            echo "-------------------------------------------------------------\n\n";
            echo "Insgesamt ".$count." Scripts kopiert.\n";
        }
		return ($includefile);
		}

	/*
	 * Statusinfo von AllgemeineDefinitionen in einem File auf der Dropbox abspeichern
	 *
	 */
    /**
     * Saves the current and historical status information to a file in the cloud status directory.
     *
     * This function generates status reports using `send_status()` for both current and historical data,
     * and writes them to separate files in the configured cloud status directory. If a filename is provided,
     * both reports are concatenated and saved to that file. The function ensures the target directory exists.
     *
     * @param string $filename Optional. The full path to the file where the status should be saved. If empty, default filenames are used.
     * @param bool $debug Optional. If true, debug information will be printed.
     * @return string The concatenated status report content.
     * @throws Exception If file creation fails.
     */
	function FileStatus($filename="", $debug=false)
		{
        if ($this->oc_Setup['Cloud']['StatusDirectory']=="") $statusdir=$this->oc_Setup['Cloud']["CloudDirectory"];
        else $statusdir=$this->oc_Setup['Cloud']["StatusDirectory"];
        $statusdir=$this->dosOps->correctDirName($statusdir);
		$DIR_copystatusdropbox = $statusdir.IPS_GetName(0).'/';
        $DIR_copystatusdropbox=$this->dosOps->correctDirName($DIR_copystatusdropbox);

        $sendStatus = new SendStatus();
        if ($debug)
            {
            echo "FileStatus, CopyScripts, relevante Configuration mit Cloud Verzeichnis:\n";
            echo "Zielverzeichnis für Ergebnisse von send_status ist $DIR_copystatusdropbox.\n";
            if ($debug>1) print_r($this->oc_Setup["Cloud"]);
            }        
		/* sicherstellen dass es das Dropbox Verzeichnis auch gibt */
		$this->dosOps->mkdirtree($DIR_copystatusdropbox);
        if ($debug) 
            {
            echo "===================================\n";
            echo "Send_status aktuelle Werte berechnen:\n\n";
            }
        /* Aufruf send_status aktuell/historisch, Zeit für execute time Berechnung, Debug */ 
		$event1=date("D d.m.y h:i:s")." Die aktuellen Werte aus der Hausautomatisierung: \n\n".$sendStatus->send_status(true, 0, $debug).
			"\n\n************************************************************************************************************************\n";
        if ($debug) 
            {
            echo "===================================\n";
            echo "Send_status historische Werte berechnen:\n";
            }
		$event2=date("D d.m.y h:i:s")." Die historischen Werte aus der Hausautomatisierung: \n\n".$sendStatus->send_status(false, 0, $debug).
			"\n\n************************************************************************************************************************\n";
		
		if ($filename=="")		/* sonst filename übernehmen */
			{
			//$filename=IPS_GetKernelDir().'scripts\IPSLibrary\config\modules\EvaluateHardware\EvaluateHardware_Include.inc.php';
			$filenameAktuell=$DIR_copystatusdropbox.date("Ymd").'StatusAktuell.txt';
			$filenameHistorisch=$DIR_copystatusdropbox.date("Ymd").'StatusHistorie.txt';
			
			if (!file_put_contents($filenameAktuell, $event1)) {
                echo "Error, Create File $filename failed!\n";
    	  	  	throw new Exception('Create File '.$filename.' failed!');
    			}

			if (!file_put_contents($filenameHistorisch, $event2)) {
                echo "Error, Create File $filename failed!\n";
    	  	  	throw new Exception('Create File '.$filename.' failed!');
    			}
			}
		else
			{	
			if (!file_put_contents($filename, $event1.$event2)) {
                echo "Error, Create File $filename failed!\n";
    	  	  	throw new Exception('Create File '.$filename.' failed!');
    			}
			}
        return($event1.$event2);
		}	

	/*
	 * Anzahl der Status Files auf der Dropbox, werden gemeinsam mit der Purge Funktion der Logfiles geloescht
	 *
	 */

	function FileStatusDir($debug=false)
		{
		$DIR_copystatusdropbox = $this->oc_Setup['Cloud']['StatusDirectory'].IPS_GetName(0).'/';	
		if ($debug)
            {
            echo "FileStatusDir: Konfiguration:\n";
		    print_r($this->oc_Setup);
		    echo "Verzeichnis für Status Files:\n";
    		echo $DIR_copystatusdropbox."\n";   
            }
	   	$statusdir=$this->readdirToArray($DIR_copystatusdropbox);
		if ($statusdir !== false)	// Verzeichnis vorhanden
			{		
			rsort($statusdir);
			print_r($statusdir);
			$size=sizeof($statusdir);
			return($size);
			}
		else return (false);
		}
	
	/*
	 * Loescht bis auf die angegebenen letzte x Files alle Status Files auf der Dropbox, werden gemeinsam mit der Purge Funktion der Logfiles geloescht
	 *
	 */

	function FileStatusDelete()
		{
		$delete=0;	
		echo "FileStatusDelete: ";
		//print_r($this->oc_Setup);		
		if ( isset($this->oc_Setup['Cloud']['StatusMaxFileCount']) == true )
			{
			echo "Wenn mehr als ".$this->oc_Setup['Cloud']['StatusMaxFileCount']." Status Dateien gespeichert, diese loeschen.\n";
			if ( $this->oc_Setup['Cloud']['StatusMaxFileCount'] > 0)
				{
				$DIR_copystatusdropbox = $this->oc_Setup['Cloud']['StatusDirectory'].IPS_GetName(0).'/';
	   			$statusdir=$this->readdirToArray($DIR_copystatusdropbox);
				if ($statusdir !== false)	// Verzeichnis vorhanden
					{
					rsort($statusdir);
					$i=0; 			   
					foreach ($statusdir as $index => $name)
						{
						if ($i > $this->oc_Setup['Cloud']['StatusMaxFileCount'])
							{	/* delete File */
							echo "delete File :".$DIR_copystatusdropbox.$name."\n";
							unlink($DIR_copystatusdropbox.$name);
							$delete++;
							}
						$i++;	
						}
					}
				}
			}
		return($delete);			   	   
		}

	/*
	 * gibt das lokale Verzeichnis der Status Files auf der Dropbox zurück
	 *
	 */

	function getFileStatusDir()
		{
		$DIR_copystatusdropbox = $this->oc_Setup['Cloud']['StatusDirectory'].IPS_GetName(0).'/';	
		return($DIR_copystatusdropbox);
		}

	/*
	 * aus dem HTML Info Feld des IPS Loggers die Errormeldungen wieder herausziehen
	 *
	 */

	function getIPSLoggerErrors()
		{
		IPSUtils_Include ("IPSLogger_Constants.inc.php","IPSLibrary::app::core::IPSLogger");
		$htmlstring=GetValue(c_ID_HtmlOutMsgList);
		$delete_tags = array("style","colgroup");
		$strip_tags = array("table","tr","td");
		$result=$this->stripHTMLTags($htmlstring,$delete_tags, $strip_tags);
		$result2=$this->stripHTMLTags($result,$delete_tags, $strip_tags);
		$result3=$this->stripHTMLTags($result2,$delete_tags,$strip_tags);
		$result4=$this->stripHTMLTags($result3,$delete_tags,$strip_tags);
		$result5=str_replace("<BR>","\n",$result4);	
		$result5=str_replace("<DIV>"," ",$result5);
		$result5=str_replace("</DIV>","\n",$result5);
		return(trim($result5));	
		}

	/*
	 *
	 */

	private function stripHTMLTags($htmlstring,$delete_tags=array(), $strip_tags=array())
		{
		$len=strlen($htmlstring);
		$ignorestyle=false; $striptag=false;
		$ignore=0; $tag=""; $result="";
		for ($i=0; $i<$len; $i++)
			{
			switch ($htmlstring[$i])
				{
				case "<":
					/* Start eines Tags erkannt */
					$tagstart=$i+1;
					$ignore=255;
					break;
				case ">":
					/* Ende eines Tags erkannt */
					$text=substr($htmlstring,$tagstart,$i-$tagstart);
					if ( $text == ("/".$tag) ) 
						{  /* es wurde ein Ende Tag zu einem vor her erkanntem Tag erkannt */
						//echo "\nEnde Tag: </".$tag.">\n";
						if ( ($ignorestyle==true) && ($style==$tag) ) 
							{
							$ignorestyle=false;
							}
						else
							{
							if ($striptag==true)
								{
								$striptag=false;
								}
							else
								{	
								$result.="<".$text.">";
								}
							}	
						$tag="";
						$ignore=1;					}
					else
						{	/* es wurde das Ende eines Tags erkannt */
						if (in_array(strtolower($text), $delete_tags) && ($striptag==false))
							{
							/* den ganzen Text zwischen start und ende Tag eliminieren */
							//echo "Ignore ".$text." ";
							$ignorestyle=true;
							$style=$text;
							$ignore=255;
							if ($tag=="") 
								{
								$params=explode(" ",$text);
								//print_r($params);
								//echo "Start Tag: <".$params[0].">    ".$text."\n";
								$tag=$params[0];
								}
							else
								{		
								//echo "Tag: <".$text."> Start Tag unveraendert ".$tag."\n";
								$result.="<".$text.">";
								}
							}
						else
							{
							//echo "Start Tag: ".$text."\n";
							if ($ignorestyle==false)
								{
								if ($tag=="") 
									{
									$params=explode(" ",$text);
									//print_r($params);
									$tag=$params[0];
									if (in_array(strtolower($tag), $strip_tags))
										{			
										//echo "Strip Start Tag: <".$tag.">    ".$text."\n";
										$striptag=true;
										}
									else
										{						
										//echo "Start Tag: <".$tag.">    ".$text."\n";
										$result.="<".$text.">";
										}
									}
								else
									{		
									//echo "Tag: <".$text."> Start Tag unveraendert ".$tag."\n";
									$result.="<".$text.">";
									}								
								/* nur den Start und Ende tag selbst eliminieren */ 
								$ignore=1;
								}
							}
						}
					break;
				default:
					break;
				}
			if ($ignore>0) 
				{
				$ignore--;
				}
			else
				{	 
				//echo $htmlstring[$i];
				$result.=$htmlstring[$i];
				}
			}
		//$newresult=stripHTMLTags($result,$strip_tags);
		//if ($newresult==$result) echo "unveraendert !\n";	
		return ($result);
		}																
		
			
	}  /* ende class OperationCenter*/

/********************************************************************************************************
 *
 * BackupIpsymcon of OperationCenter
 * ================================= 
 *
 * extends OperationCenter because it is using same config file
 *
 * uses two different files backup.csv and summaryofbackup.csv
 * backup.csv is the file inventory of the full Backup Drive
 * summaryofbackup.csv combines summary of backup.csv with information about the status of the real backups
 * name.backup.csv is the individual file inventory of a single backup and is only created if the backup was successfull
 *    name is the name of the directory
 *
 * Backup wird mit start_backup(full|increment) aufgerufen
 *
 *
 *  __construct
 *
 *  Servicefunktionen, encapsulation
 *
 *  getActive, getBackupDrive, getSourceDrive, getBackupSwitch, getBackupSwitchId,  
 *  getOverwriteBackupId, getBackupActionSwitchId, getBackupStatus, setBackupStatus 
 *  getConfigurationStatus, setConfigurationStatus
 *  getMode, setExecTime, setTableStatus
 *  checkToken, cleanToken, get_ActionButton
 *  startBackup, startBackupIncrement, stoppBackup
 *  configBackup
 *
 *  Backup Funktion, Files kopieren
 *  ------------------------------------
 *  backupDirs, backupDir, copyFile
 *
 *  Support functions for Backup, Verzeichnisse mit Properties einlesen
 *  -------------------------------------------------------------------
 *  readBackupDir, readSourceDir, readSourceDirs, readFileProps
 *
 *  Statemachines for support IPS_GetFunctions
 *  -------------------------------------------
 *  getBackupDirectoryStatus        Statemachine to reload Backup.csv
 *
 *  getBackupDirectorySummaryStatus
 *
 *  getBackupDirectories, getBackupLogTable     Verzeichnisse und Backups (anhand logs) identifizieren
 *
 *  readBackupDirectorySummaryStatus
 *
 *  writeTableStatus                den Status des Backup Moduls in einen html table schreiben
 *  pathXinfo
 *
 **************************************************************************************************************************/

class BackupIpsymcon extends OperationCenter
	{
	//private $dosOps, 
    //private $systemDir;              // das SystemDir, gemeinsam für Zugriff zentral gespeichert
    protected $fileOps;                                                           /* genutzte Objekte/Klassen */
    protected $debug;

    var $BackupDrive, $SourceDrive;                                         /* Verzeichnisse für Backup Ort und Quelle */
    var $backupActive;        								                /* Backup Aktiv Status */
	var $categoryId_BackupFunction;                                          /* Datenspeicherorte */
    var $StatusSchalterBackupID, $StatusSchalterActionBackupID;				/* Schalter für Steuerung von Backup */
	var $StatusBackupID, $ConfigurationBackupID;                             /* Status und Configuration */ 
    var $StatusSchalterOverwriteBackupID,$StatusSliderMaxcopyID;             /* Besondere Einstellungen für Backup */ 
    var $TokenBackupID, $ErrorBackupID;                                     /* Token und Error dafür */
    var $ExecTimeBackupId, $TableStatusBackupId;                            /* Durchlaufzeit zuletzt anzeigen und eine fette Tabelle mit allerlei Nutzvollem */
	
	var $BackOverviewTable = array();										/* alle statistischen Daten über die Backups nach dem Auslesen

	
    /***********************************************************************
    *
    * Backup Configuration analysieren 
    *
    * zuerst die Konfiguration einlesen und herausfinden ob aktiviert und das Backupdrive herausfinden 
    * dann das Backupdrive einlesen:
    *
    *   es gibt pro Backup ein Laufwerk mit dem Datum, d.h.nur ein Backup pro Tag
    *	sobald das Backup fertiggestellt wurde, wird zusaetzlich ein File erstellt mit dem letzten Logfile	
    *   Name File ist Name des Verzeichnis plus _ plus full oder increment.backup.csv
    *
    * es werden pro Vorgang maximal x Dateien für das Backup verarbeitet. Bei 1000 Dateien kann beim ersten Mal ein Timeout ueberschritten werden.
    *
    */

	public function __construct($subnet='10.255.255.255',$debug=false)
		{
        if ($debug) echo "class BackupIpsymcon, Construct Parent class OperationCenter.\n";
        $this->debug=$debug;   
        parent::__construct($subnet,$debug);                       // sonst sind die Config Variablen noch nicht eingelesen

        $this->dosOps = new dosOps();     // create classes used in this class
        //$this->systemDir     = $this->dosOps->getWorkDirectory();
        if ($debug) 
            {
            echo "class BackupIpsymcon: _construct\n";
            echo "   work Dir is ".$this->systemDir."\n";
            echo "   symcon Dir is ".IPS_GetKernelDir()."\n";
            echo "   OperatingSystem is ".$this->dosOps->evaluateOperatingSystem()."\n";
            echo "   install Dir is ".IPS_GetKernelDirEx()."\n";
            echo "   IPS platform is ".IPS_GetKernelPlatform()."\n";
            }

        //echo "Construct BackupIpSymcon.\n";
        $configuration        = $this->getConfigurationBackup();                // direkter Zugriff auf Parent variablen sollte vermieden werden
        $BackupDrive          = $configuration["Directory"];                    // für vorher nachher vergleich
        $this->backupActive   = $this->setActive($configuration,$debug);
        $this->SourceDrive    = IPS_GetKernelDirEx();           // das alte C:/IP-Symcon  - sollte eigentlich IPS_GetKernelDir() sein
	
		/* Allgemeine Variablen am Webfront, oder im Data Bereich */		
		$this->categoryId_BackupFunction	= IPS_GetObjectIdByName('Backup', $this->CategoryIdData);

        /* das sind die Schalter im Webfront für die Bedienung des Backups */
		$this->StatusSchalterBackupID		    = IPS_GetObjectIdByName("Backup-Funktion", $this->categoryId_BackupFunction);
		$this->StatusSchalterActionBackupID     = IPS_GetObjectIdByName("Backup-Actions", $this->categoryId_BackupFunction);
    	$this->StatusSchalterOverwriteBackupID  = IPS_GetObjectIdByName("Backup-Overwrite", $this->categoryId_BackupFunction);
        $this->StatusSliderMaxcopyID            = IPS_GetObjectIdByName("Maxcopy per Session", $this->categoryId_BackupFunction);

        $this->StatusBackupID				= IPS_GetObjectIdByName("Status", $this->categoryId_BackupFunction);
		if ($this->backupActive==false) SetValue($this->StatusSchalterBackupID,0);   // keinen andern Statuzustand erlauben wenn keine Konfiguration vorhanden
		$this->ConfigurationBackupID		= IPS_GetObjectIdByName("Configuration", $this->categoryId_BackupFunction);
		
        $this->TokenBackupID		        = IPS_GetObjectIdByName("Token", $this->categoryId_BackupFunction);
		$this->ErrorBackupID                = IPS_GetObjectIdByName("LastErrorMessage", $this->categoryId_BackupFunction);
	    $this->ExecTimeBackupId             = IPS_GetObjectIdByName("ExecTime", $this->categoryId_BackupFunction);	
        $this->TableStatusBackupId          = IPS_GetObjectIdByName("StatusTable", $this->categoryId_BackupFunction);      /* man kann in einer tabelle alles mögliche darstellen */

        $this->BackupDrive                  = $this->getAccessToDrive($configuration, $debug);                                                    // wenn notwendig Zugriff auf ein Netzlaufwerk erreichen, wie auch immer
        if ((is_dir($this->BackupDrive))===false) $this->backupActive=false;
        if ($this->debug) echo "BackupDrive configured with: $BackupDrive results into ".$this->BackupDrive."  Backup ist : ".($this->BackupDrive?"AKTIV":"DEAKTIVIERT")."\n";
        $this->fileOps  = new fileOps($this->BackupDrive."Backup.csv"); 
        }

    /* adressing of local variables of class */

    public function getActive()
        {
        return ($this->backupActive);    			// das ist ein Wert für die Konfiguration im Configfile, aktiv oder disabled (true/false)
        }

    /* Backup aktiv hier ergründen */

    public function setActive($configuration,$debug=false)
        {
        if ($debug) print_r($configuration);
        $status=strtoupper($configuration["Status"]);
        if ( ($status=="ACTIVE") || ($status=="ENABLED") || ($status==true) ) return (true);       // das ist ein Wert für die Konfiguration im Configfile, aktiv oder disabled (true/false)
        else return (false);    			
        }

    /* adressing of local variables of class */

    public function getBackupDrive()
        {
        return ($this->BackupDrive);    			// das ist ein Wert für das Backup Verzeichnis als String
        }    

    public function getSourceDrive()
        {
        return ($this->SourceDrive);    			// das ist ein Wert für das Quellverzeichnis für das Backup als String
        } 

    public function getBackupSwitch()			    // Button im Webfront für Ein/Aus/Auto, wird auf Aus gestellt wenn Backup nicht konfiguriert, liefert den Wert
        {
        return (GetValue($this->StatusSchalterBackupID));    
        }    

    /* adressing of local class variables, here are the action button Ids */

    public function getBackupSwitchId()			    // Button im Webfront für Ein/Aus/Auto, nur die ID, für get_Action, dekativiert die gesamte Backup Funktion, wie ein Notaus
        {
        return ($this->StatusSchalterBackupID);    
        }    

    public function getOverwriteBackupId()			    // Button im Webfront für Overwrite/Keep, nur die ID, für get_Action
        {
        return ($this->StatusSchalterOverwriteBackupID);
        } 

    public function getStatusSliderMaxcopyId()			    // Button im Webfront für Overwrite/Keep, nur die ID, für get_Action
        {
        return ($this->StatusSliderMaxcopyID);
        } 

    public function getBackupActionSwitchId()			// Button im Webfront für Sonderbefehle (Full/Increment/Repair, nur die ID, für get_Action
        {
        return ($this->StatusSchalterActionBackupID);
        }

    /* bearbeiten von lokalen class Variablen */

    public function getBackupStatus()			// Statusanzeige im Webfront für Ein/Aus/Auto, wird auf Aus gestellt wenn Backup nicht konfiguriert
        {
        return (GetValue($this->StatusBackupID));    
        } 
		
    public function setBackupStatus($string)			// Statusanzeige im Webfront für Ein/Aus/Auto, wird auf Aus gestellt wenn Backup nicht konfiguriert
        {
		SetValue($this->StatusBackupID,$string);
        return ($this->StatusBackupID);    
        } 

    public function getConfigurationBackup()			// hier wird die Konfiguration für das Backup aus dem OperationCenter_Configuration File ausgelesen
        {
        $oc_setup=$this->getSetup();                // direkter Zugriff auf Parent Variablen sollte vermieden werden
        if (isset($oc_setup["BACKUP"])) return ($oc_setup["BACKUP"]);
        if (isset($oc_setup["Backup"])) return ($oc_setup["Backup"]);
        if (isset($oc_setup["backup"])) return ($oc_setup["backup"]);
        return (false);
        } 

    public function getConfigurationStatus($function="json")			// hier wird die Konfiguration für das Backup gespeichert
        {
        if ($function == "json") return (GetValue($this->ConfigurationBackupID));    
        else return (json_decode(GetValue($this->ConfigurationBackupID),true));
        } 
		
    public function setConfigurationStatus($string, $function="json")			// hier wird eine neue Konfiguration für das Backup gespeichert
        {
        if ($function == "json") 
            {    
    		SetValue($this->ConfigurationBackupID,$string);
            return ($this->ConfigurationBackupID);    
            }
        else
            {
            unset ($string["checkChange"]);
            $stringenc=json_encode($string);
    		SetValue($this->ConfigurationBackupID,$stringenc);
            return ($this->ConfigurationBackupID); 
            }
        } 

    /* Zugriff auf Netzlaufwerke etwas schwieriger, hier die Verbindung machen 
     * Normalerweise ist das Backuplaufwerk gleich  BACKUP::Directory
     * wenn zusätzlich 
                    "Network"       => "\\\\JOEBSTL24\Backup",
                    "User"          => "wolfgangjoebstl",
                    "Password"      => "##cloudg06##",
                    "Drive"         => "Y",  
     * definiert ist wird versucht mit IP Symcon System user eine Verbindung herzustellen, wenn nicht schon vorhanden.
     */

    public function getAccessToDrive($configuration,$debug)
        {
        if ($debug) echo "getAccessToDrive with configuration ".json_encode($configuration)." aufgerufen.\n";        
        $backupDrive=$this->dosOps->correctDirName($configuration["Directory"],$debug);
        if (isset($configuration["Drive"]))
            {
            if ($debug) echo "es gibt einen Drive Letter. Backup Konfiguration erweitern.\n";
            $backupDrive = $configuration["Drive"].":".$backupDrive;
            }
        if (is_dir($backupDrive)) 
            {
            if ($debug) echo "Backup Drive konfiguriert und vorhanden: $backupDrive\n";
            }        
        elseif (isset($configuration["Network"]))
            {
            $location = $configuration["Network"];
            $user = $configuration["User"];
            $pass = $configuration["Password"];
            $letter = $configuration["Drive"];

            // Map the drive
            if ($debug) echo "Map the drive system(net use ".$letter.": \"".$location."\" ".$pass." /user:".$user." /persistent:no>nul 2>&1)\n";
            system("net use ".$letter.": \"".$location."\" ".$pass." /user:".$user." /persistent:no>nul 2>&1");
            }
        else
            {
            if ($debug) echo "Warning, getAccessToDrive does not find any Backup Drive or is able to map one.\n";
            return (false);
            } 
        return ($backupDrive);
        }

    /* shall return "backup", "cleanup", "finished" */

    public function getMode()			// hier wird die Konfiguration für das Backup gespeichert
        {
        $statusMode="finished";      // default
        $status=$this->getConfigurationStatus("array")["status"];
        switch ($status)
            {
            case "started":
            case "maxcopy reached":
            case "maxtime reached":
                $statusMode="backup";
                break;
            case "cleanup-read":
            case "cleanup":
                $statusMode="cleanup";
                break;
            default:
                $statusMode="finished";
                break;
            }
        return ($statusMode);
        } 

    /* Abspeichern der Executiontime            */

    public function setExecTime($time, $runde=2, $debug=false)                      
        {
        SetValue($this->ExecTimeBackupId,round($time,$runde). " Sekunden");
        if ($debug) echo "Abgelaufene Zeit ".GetValue($this->ExecTimeBackupId)."\n";
        return ( $this->ExecTimeBackupId);      
        }

    /* abgelaufene zeit im Backup ermitteln und ausgeben */

    private function runTime($startTime)
        {
        return ((time()-$startTime)." Sekunden");    
        }

    /* abgelaufene zeit im Backup ermitteln und ausgeben */

    private function writeSpeed($startTime, $filesize)
        {
        $copytime=(time()-$startTime);
        if ($copytime>0) 
             {
             $speed=$filesize/$copytime;
             return ("$speed Byte/s");
             }   
        else return ("schnell");    
        }

    public function setTableStatus($html)                      // Abspeichern der Statustabelle
        {
        SetValue($this->TableStatusBackupId,$html);
        return ( $this->TableStatusBackupId);      
        }

    public function getToken()
        {
        return (GetValue($this->TokenBackupID));
        }

    public function checkToken()			// Mit einem Token die Backup Routinen vor einem parallelem Aufruf absichern
        {
		if (GetValue($this->TokenBackupID)=="busy") 
            {
            //echo "Token found busy ".date("Y.m.d H:i:s")."\n";
            SetValue($this->ErrorBackupID,"Token found busy ".date("Y.m.d H:i:s"));
            return ("busy");
            }
        else 
            {
            SetValue($this->TokenBackupID,"busy");
            return ("free");
            }    
        } 

    public function cleanToken($repair=false, $debug=false)			// diesen token am Ende des Aufrufs zurücksetzen oder reparieren
        {
        $lastchange=IPS_GetVariable($this->TokenBackupID,)["VariableUpdated"];
        if ($debug) echo "Last Change of Token was ".date("Y.m.d H:i:s",$lastchange);
        SetValue($this->TokenBackupID,"free");
        if ($repair) SetValue($this->ErrorBackupID,"Token clean, repaired ".date("Y.m.d H:i:s")." Last Change was ".date("Y.m.d H:i:s",$lastchange));  
        else SetValue($this->ErrorBackupID,"Token free since ".date("Y.m.d H:i:s")." Last Change was ".date("Y.m.d H:i:s",$lastchange));      
        } 

	/**
	 * Zusammenfassung der ActionButtons der class Backup, nicht gemeinsam mit OperationCenter
	 *
	 * 
	 *
	 */
	
	function get_ActionButton()
		{	
		$actionButton=array();

		$actionButton[$this->getBackupActionSwitchId()]["Backup"]["BackupActionSwitch"]=true;
		$actionButton[$this->getBackupSwitchId()]["Backup"]["BackupFunctionSwitch"]=true;
        $actionButton[$this->getOverwriteBackupId()]["Backup"]["BackupOverwriteSwitch"]=true;
        $actionButton[$this->getStatusSliderMaxcopyID()]["Backup"]["StatusSliderMaxcopy"]=true; 

		return($actionButton);
		}

    /* config Backup
     *
     *  ein parameter wird zur Config ($params) hinzugefügt oder upgedatet, table im html wird auch upgedatet
     *
     */

    function configBackup($mode)
        {
        $paramsJson=$this->getConfigurationStatus();
        $params=json_decode($paramsJson,true);
  
        //print_r($mode);
        foreach ($mode as $param => $entry)
            {
            $params[$param]=$entry;
            }

        $paramsJson=json_encode($params);
        $this->setConfigurationStatus($paramsJson);
        
        $this->writeTableStatus($params);            // ohne Parameter wird das html automatisch geschrieben            
        return($params);
        }

    /* write params to echo in readable version */

    function writeParams(&$params)
        {
        echo "Ausgesuchte Werte des Backup params Array :\n";
        echo "  Backup Status started/finished : ".$params["status"]."\n";
        echo "  TargetDir : ".$params["BackupTargetDir"]."\n";
        echo "  SourceDir : ".$params["BackupSourceDir"]."\n";
        echo "  Style full/increment : ".$params["style"]."\n";
        echo "  Art des Updates keep/overwrite : ".$params["update"]."\n";
        echo "  Anzahl file copies pro durchgang : ".$params["maxcopy"]."\n";

        echo "  Anzahl : ".$params["count"]."\n";
        echo "  Anzahl kopiert : ".$params["copied"]."\n";
        echo "  Groesse : ".$params["size"]."\n";

        if (isset($params["type"])) echo "  Type : ".$params["type"]."\n";
        if (isset($params["full"])) echo "  Name of latest Full Backup : ".$params["full"]."\n";
        echo "  Cleanup Status : ".$params["cleanup"]."\n";
        echo "  Latest Filedate : ".$params["latest"]."\n";
        echo "  BackupDrive : ".$params["BackupDrive"]."\n";
        if (isset($params["sizeInc"])) echo "  Groesse bei Increment : ".$params["sizeInc"]."\n";
        if (isset($params["countInc"])) echo "  Anzahl bei increment : ".$params["countInc"]."\n";
        echo "  Groesse Target wenn fertig : ".$params["sizeTarget"]."\n";
        echo "  Anzahl Target wenn fertig : ".$params["countTarget"]."\n";     
        }

    /*********************************************************************************************************
    *
    *   Steuerung der Backupfunktion
    */

    /* start Backup, either full or incement
     *
     * Backup Parameter ermitteln.
     *   bei increment noch herausfinden, welche Backups full + increment der Absprungpunkt sind
     */

    function startBackup($mode="", $debug=false)
        {
        $params=$this->getConfigurationStatus("array");     // gleich ein json decoded array ausgeben
  
        /* Backup Verzeichnis im Backup verzeichnis ermitteln. ist der aktuelle Tag */
        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist
        $BackupVerzeichnis=date("Ymd", time());
        $BackupToday=$BackupDrive.$BackupVerzeichnis;
        $params["BackupTargetDirs"]=[];

        /* Backup Parameter ermitteln. Bei increment muss noch zusätzlich
         *      der Absprung Punkt definiert werden 
         *      und der Name des Backupverzeichnisses erweitert werden damit klar ist dass increment und von wo an
         */
        switch ($mode)
            {
            case "full":
                if ($debug) echo "startBackup : Backup mit $mode Mode angefordert. Jetzt mit Backup starten.\n";
                $params["status"]="started";
                $params["style"]=$mode;
                $params["BackupTargetDir"]=$BackupToday;
                break;
            case "increment":
                if ($debug) echo "startBackup : Backup mit $mode Mode angefordert. Jetzt mit Backup starten.\n";
                $params["status"]="started";
                $params["style"]=$mode;
                $result=$this->startBackupIncrement($params, $debug);
                if ($result == false)       // es gibtr klein full um mit einem increment weiter zu machen 
                    {
                    if ($debug) echo "   Noch Kein full Backup vorhanden. Statt increment mit full Backup starten.\n";
                    $params["style"]="full";
                    $params["BackupTargetDir"]=$BackupToday;
                    }
                else $params["BackupTargetDir"]=$BackupToday."_".$params["style"]."_".$result["name"];
                break;
            default:
                $params["status"]="started";            
                if ($params["style"]=="full") $params["BackupTargetDir"]=$BackupToday;
                else
                    {
                    $result=$this->startBackupIncrement($params, $debug);
                    $params["BackupTargetDir"]=$BackupToday."_".$params["style"]."_".$result["name"];
                    } 
                break;
            }
        $params["size"]=0;  $params["count"]=0;
        $this->readSourceDirs($params,$result);
        $params["sizeTarget"]=$params["size"]; 
        $params["countTarget"]=$params["count"];
        $params["size"]=0;  $params["count"]=0;

        $paramsJson=json_encode($params);
        $this->setConfigurationStatus($paramsJson);
        
        $this->writeTableStatus($params);            // ohne Parameter wird das html automatisch geschrieben            
        }


    /* start Increment Backup
     * alles tun das noch zusätzlich für einen incrementellen Backup notwendig ist.
     * wird von startBackup aufgerufen
     *
     */

    function startBackupIncrement(&$params, $debug=false)        
        {
        $resultfull=array();
        $result = $this->readBackupDirectorySummaryStatus($resultfull, $debug);         // lese die Datei SummaryofBackup.csv, wenn nicht vorhanden $this->getBackupDirectorySummaryStatus("noread")
        if ($debug) 
            { 
            echo "Letztes Backup von SummaryofBackup.csv mit gültigen Status : \n"; 
            print_r($result);
            }
        $entry=false;                 // if result is empty dann return false
        foreach ($result as $entry)
            {
            if ($entry == "full") 
                {
                $params["full"]=$entry["logFilename"];                     
                break;
                }
            }
        
        $params["sizeInc"]=0;  $params["countInc"]=0;        
        //$result=$this->pathXinfo($params["full"]); echo "Incremental Backup to ".$result["Directory"]."   ".$result["Path"]."   ".$result["PathX"]."   ".$result["Filename"]."   \n";
        $params["checkChange"]=array();
        if ($debug) echo "startBackupIncrement abgeschlossen.\n";
        return($entry);
        }

    /* stopp Backup
     *
     *
     */

    function stoppBackup()
        {
        //echo "stoppBackup aufgerufen \n";
        $params=$this->getConfigurationStatus("array");
        $params["status"]="stopped";
        $this->setConfigurationStatus($params,"array");
        
        $this->writeTableStatus($params);            // ohne Parameter wird das html automatisch geschrieben            
        }



    /*********************************************************************************************************
    *
    * backup copy function , grundsaetzlicher Aufruf, rekursive Funktion des backups mit backupDir 
    *
    * es wird nur params für die parameter und logs für das Ergebnis log übergeben
    * erfolgt sowohl für Datei oder Verzeichnis mit Parameter:  quellfile/verzeichnis, Sourceverzeichnis, zielverzeichnis, log, params
    * das quellfile/verzeichnis wird relativ angegeben, Sourceverzeichnis ist relativ, zielverzeichnis sind absolut angegeben, ohne Backupdate/verzeichnis Name
    * das Zielverzeichnis ist das aktuelle Backupverzeichnis
    *
    * bei incremental Backup die Absprungbasis festlegen
    *
    */

    function backupDirs(&$log, &$params, $debug=false)
        {
        /* Init */
        $backupSourceDirs=$params["BackupDirectoriesandFiles"];
        $params["startTime"]=time();
        $params["size"]=0;  $params["count"]=0;
        if ($params["style"]=="increment")
            {
            echo "backupDirs: incremental backup requested. Read ".$params["full"]."\n";
            $count=0; $countmax=10; $fileCount=0;
            $result=$this->pathXinfo($params["full"]); 
            //print_r($result);              
            $handle1=fopen($params["full"],"r");
            while ( !feof($handle1) ) 
                {
                if (($input=fgets($handle1)) === FALSE) break;      // the while loop
                $inputArray=explode(";",$input);
                switch (sizeof($inputArray)) 
                    {
                    case 0:
                    case 1:
                        break;
                    case 2:
                        $filename='.\\'.substr($inputArray[0],(strrpos($result["PathX"],'\\')+1));                    
                        $params["checkChange"][$filename]=trim($inputArray[1]);
                        $fileCount++;
                        break;
                    case 3:
                        $filename='.\\'.substr($inputArray[0],(strrpos($result["PathX"],'\\')+1));                    
                        $dataArray=explode(":",$inputArray[2]);
                        if ( ($dataArray !== false) && (count($dataArray)>2) )
                            {
                            switch ($dataArray[0])
                                {
                                case "available":
                                case "copied":
                                    $params["checkChange"][$filename]=trim($dataArray[2]);
                                    break;
                                default:
                                    if ($count++ < $countmax) echo "   ".$inputArray[2]." \n";
                                    elseif ($count++ == $countmax) echo "---> more lines available.\n";  
                                    break;
                                }
                            }
                        $fileCount++;
                        break;
                    }
                /* if ($count++ < $countmax) 
                    {
                    echo "   ".count($inputArray)." entries : ";
                    //echo $input ;
                    echo $inputArray[0]."  ".$result["PathX"];
                    echo " $filename ".$params["checkChange"][$filename]."\n";
                    }
                elseif ($count++ == $countmax) echo "---> more lines availabel.\n";   */
                }
            fclose($handle1);
            echo "  in total $fileCount Files read from ".$params["full"]."\n";
            }

        /* excute */
        foreach ($backupSourceDirs as $backupSourceDir)
            {
            $dir=true; $file=false;
            $SourceVerzeichnis=$params["BackupSourceDir"].$backupSourceDir;
            if (is_dir($SourceVerzeichnis)) 
                {
                if ($debug) echo "   Backup von $backupSourceDir aus Verzeichnis ".$params["BackupSourceDir"]." nach ".$params["BackupTargetDir"]."  ".$this->runTime($params["startTime"])."\n";
                }
            else 
                {
                if (is_file($SourceVerzeichnis)) 
                    {
                    if ($debug) echo "   Backup von Datei $backupSourceDir aus Verzeichnis ".$params["BackupSourceDir"]." nach ".$params["BackupTargetDir"]."  ".$this->runTime($params["startTime"])."\n";
                    $dir=false; $file=true;
                    }
                else 
                    {
                    if ($debug) echo "   Verzeichnis/Datei ".$SourceVerzeichnis." nicht vorhanden. Backup nicht möglich\n";
                    $dir=false;
                    }
                
                }
            if ( ($dir) || ($file) )
                {
                //echo "backupDirs: Backup von $backupSourceDir aus Verzeichnis ".$params["BackupSourceDir"]." nach ".$params["BackupTargetDir"]."\n";                    
                $this->BackupDir($backupSourceDir,$params["BackupSourceDir"],$params["BackupTargetDir"], $log, $params, $debug);
                }
            }       // ende foreach
        }

    /*********************************************************************************************************
    *
    * recursive backup copy function , Aufruf mit Datei oder Verzeichnis, quellverzeichnis, zielverzeichnis, log, params
    *
    * wenn datei wird einfach nur kopiert, wenn verzeichnis muss auch im zielverzeichnis das verzeichnis angelegt werden
    *
    */

    function backupDir($sourceDir, $backupSourceDir, $TargetVerzeichnis, &$log, &$params, $debug=false)
        {
        $i=0;  $imax=100;     // Notbremse, deaktiviert
        if ( (isset($params["echo"])) === false) $params["echo"]=0;
        //echo "backupDir: $sourceDir copied ".$params["copied"]." > ".$params["maxcopy"]."\n";
        if ($params["copied"]>$params["maxcopy"]) 
            {
            $params["status"]="maxcopy reached";
            return;
            }
        if ((time()-$params["startTime"])>$params["maxtime"]) 
            {
            $params["status"]="maxtime reached";
            return;
            }
        if (isset($params["BackupTargetDirs"][$TargetVerzeichnis])) $params["BackupTargetDirs"][$TargetVerzeichnis]++;
        else $params["BackupTargetDirs"][$TargetVerzeichnis]=1;
        $backupSourceDir = $this->dosOps->correctDirName($backupSourceDir);			// sicherstellen das ein Slash oder Backslash am Ende ist
        $TargetVerzeichnis = $this->dosOps->correctDirName($TargetVerzeichnis);			// sicherstellen das ein Slash oder Backslah am Ende ist
        //echo "Backup $sourceDir von $backupSourceDir nach $TargetVerzeichnis.\n";
        $SourceVerzeichnis=$backupSourceDir.$sourceDir;
        /***************************************************************************/
        if (is_dir($SourceVerzeichnis))		// Directory wird bearbeitet
            {
            //echo "Backup Verzeichnis $sourceDir von $backupSourceDir nach $TargetVerzeichnis. rekursiver Aufruf erforderlich.\n";
            $dirChildren=$this->readdirToArray($SourceVerzeichnis);
            //print_r($dirChildren);
            if (is_dir($TargetVerzeichnis.$sourceDir))
                {
                //echo "      Verzeichnis $TargetVerzeichnis$sourceDir bereits angelegt.\n";
                } 
            else 
                {
                $this->dosOps->mkdirtree($TargetVerzeichnis.$sourceDir);               // muss rekursiv sein	
                }
            foreach ($dirChildren as $entry)
                {
                //if ($i++<100) 
                    {
                    //echo $i."    Aufruf backupdir rekursiv mit $entry.\n";
                    $this->backupDir($entry, $SourceVerzeichnis, $TargetVerzeichnis.$sourceDir, $log, $params, $debug);
                    }
                }
            }
        /***************************************************************************/
        else        // File wird kopiert
            {
            $fileTimeInt=filectime($SourceVerzeichnis);			// eindeutiger Identifier, beim Erstellen des Files festgelegt 
            $fileTime=date("YmdHis",$fileTimeInt);
            $filemTimeInt=filemtime($SourceVerzeichnis);		// Datum der letzten Aenderung, fuer Backup interessant, Zeitstempel darf nicht groesser als Backup sein
            $filemTime=date("YmdHis",$filemTimeInt);

            if ( $debug && ( ($params["count"] % 100) == 0) ) echo " ".$params["count"]."/".$params["copied"]."   ".$params["size"]."   ".$this->runTime($params["startTime"])."\n";

            if ($params["style"]=="increment")          // ********************* incremental backup
                {
                //$targetFilename='.\\'.$sourceDir; 
                $targetFilename='.\\'.substr($TargetVerzeichnis.$sourceDir,(strlen($this->dosOps->correctDirName($params["BackupTargetDir"]))));
                $params["size"]=$params["size"]+filesize($SourceVerzeichnis);
                $params["count"]=$params["count"]+1;                 // Anzahl bearbeiteter Dateien                
                //if ( $debug && ($params["echo"]++<$imax)) echo "    look for ".$targetFilename."\n";
                if (isset($params["checkChange"][$targetFilename])) 
                    {
                    //if ( $debug && ($params["echo"]++<$imax) ) echo "       found one ".$targetFilename."\n";
                    $targetTime=strtotime($params["checkChange"][$targetFilename]);
                    //if ( ($filemTimeInt>$targetTime) && ($i++ < 2) )              // do not know why only two copies
                    if ($filemTimeInt>$targetTime)                     
                        {
                        //if ($debug && ($params["echo"]++<$imax)) echo $params["echo"]."       copy it ".$targetFilename." $SourceVerzeichnis ($filemTime) > $TargetVerzeichnis$sourceDir (".$params["checkChange"][$targetFilename].") because Backup date ".date("d.m.Y H:m:s",$targetTime)." and Source date ".date("d.m.Y H:m:s",$filemTimeInt)."\n";
                        $params["sizeInc"]=$params["sizeInc"]+filesize($SourceVerzeichnis);
                        $params["countInc"]=$params["countInc"]+1;                 // Anzahl kopierter Dateien
                        $this->copyFile($SourceVerzeichnis,$TargetVerzeichnis.$sourceDir, $log, $params, $debug);
                        }
                    }
                else 
                    {
                    if ($debug && ($params["echo"]++<$imax)) echo $params["echo"]."NEW FILE, copy it ".$targetFilename."\n";
                    $params["sizeInc"]=$params["sizeInc"]+filesize($SourceVerzeichnis);
                    $params["countInc"]=$params["countInc"]+1;                 // Anzahl kopierter Dateien
                    $this->copyFile($SourceVerzeichnis,$TargetVerzeichnis.$sourceDir, $log, $params, $debug);
                    }
                }
            else                                        // ********************** full backup
                {
                //echo "       Datei copy $SourceVerzeichnis $TargetVerzeichnis.";
                $params["size"]=$params["size"]+filesize($SourceVerzeichnis);
                $params["count"]=$params["count"]+1;                 // Anzahl bearbeiteter Dateien
                $this->copyFile($SourceVerzeichnis,$TargetVerzeichnis.$sourceDir, $log, $params, $debug);
                }
            }	// ende file wird bearbeitet

        if ($debug && ($params["echo"]++<$imax) ) echo "            Backup $sourceDir von $backupSourceDir nach $TargetVerzeichnis. Copy ist ".$params["copied"]."/".$params["maxcopy"].". Size of Log ist ".$params["count"]."\n";
        }

    /*********************************************************************************************************
    *
    * Maintenance Funktionen, Löschen der Backup.csv Dateien und der Verzeichnisse dazu
    *
    * delete backups with status error
    * im Debug modus wird nicht gelöscht
    *
    * zusaetzliche Checks damit nicht die Source geloescht wird, oder etwas anderes als Backup:
    *
    */

    function deleteBackupStatusError($debug=false)
        {
        $resultfull=array();
        $result = $this->readBackupDirectorySummaryStatus($resultfull);         // lese die Datei SummaryofBackup.csv, wenn nicht vorhanden $this->getBackupDirectorySummaryStatus("noread")
        $increment=0; $full=0; $delete=false; 
        //$monthCount=0; $month=(integer)date("n");          // n ist monat ohne führende Nullen
        $monthSet=array();
        $monthFound=false;
        foreach ($resultfull as $BackupDirEntry => $entry)
            {
            if (isset($entry["logFilename"])) 
                {  // path rausrechnen
                $details=$this->pathXinfo($entry["logFilename"]);
                //print_r($details);
                if ( (isset($details["Date"])) && (isset($details["Type"][2])) )
                    {
                    $backupTime=strtotime($details["Date"]);
                    $days=round((time()-$backupTime)/24/60/60,0);
                    $monthBackup=(integer)date("n", $backupTime);
                    if ( (isset($monthSet[$monthBackup])==false) && (($entry["type"]) == "full") ) { $monthSet[$monthBackup]=$details["Date"]; $monthFound=true; }
                    else $monthFound=false;
                    echo "Zeit zum Backup in Tagen : ".$days." fuer ".$details["Date"]." ".$entry["type"]."   Monat $monthBackup ".($monthFound?"Neuer Monat":"")."\n";
                    if ($details["Type"][0] == "full") $full++;
                    elseif ($details["Type"][0] == "increment") $increment++;
                    echo "$full/$increment Zeit zum Backup in Tagen : ".$days." fuer ".$details["Date"]."\n";
                    if ($delete) 
                        {
                        if ( ($monthFound==false) ) $resultfull[$BackupDirEntry]["cleanup"]="delete";
                        //print_r($entry);
                        }
                    else
                        {
                        if ( ($full>=2) && (($full+$increment)>=10) ) 
                            {
                            echo "   Tagesbackups ausreichend vorhanden, von hier an loeschen.\n";
                            $delete=true;
                            }
                        }
                    }
                }
            }
        //print_r($monthSet);
        if ($debug) 
            { 
            //echo "Werte von SummaryofBackup.csv mit gültigen Status : \n"; 
            //print_r($result);
            //echo "Alle Werte : \n";
            //print_r($resultfull); 
            $html="";
            $html .= '<style>';
            $html .= 'table.quick { border:solid 5px #006CFF; margin:0px; padding:0px; border-spacing:0px; border-collapse:collapse; line-height:22px; font-size:13px;'; 
            $html .= ' font-family:Arial, Verdana, Tahoma, Helvetica, sans-serif; font-weight:400; text-decoration:none; color:#0018ff; white-space:pre-wrap; }';
            $html .= 'table.quick th { padding: 2px; background-color:#98dcff; border:solid 2px #006CFF; }';
            $html .= 'table.quick td { padding: 2px; border:solid 1px #006CFF; }';
            $html .= 'table.custom_class tr { margin:0; padding:4px; }';
            $html .= '.quick.green {border:solid green; color:green;}';
            $html .= '';
            $html .= '</style>';
            $html .= '<table class="quick">';
            foreach ($resultfull as $path => $entry)
                {
                $html.= '<tr><td>';
                //$html.= $path.'</td><td>';            // path ist redundant, nicht notwendig
                $html.= $entry["name"].'</td><td>';
                if (isset($entry["Size"])) $html.= number_format((floatval(str_replace(",",".",$entry["Size"]))/1024/1024),3,",",".")." MByte";
                $html.= '</td><td>';
                if (isset($entry["Filecount"])) $html.= $entry["Filecount"];
                $html.= '</td><td>';
                if (isset($entry["type"])) $html.= $entry["type"];
                $html.= '</td><td>';
                if (isset($entry["logFilename"])) 
                    {  // path rausrechnen
                    $details=$this->pathXinfo($entry["logFilename"]);
                    //print_r($details);
                    if ($debug) echo "writeTableStatus : ".$path."\n";
                    $html.= $details["Filename"];
                    }
                $html.= '</td><td>';
                if (isset($entry["status"])) $html.= $entry["status"];
                $html.= '</td><td>';
                if (isset($entry["logFiledate"])) $html.= $entry["logFiledate"];            
                $html.= '</td><td>';
                if (isset($entry["last"])) $html.= date("H:i:s d.m.Y",(integer)$entry["last"]);            
                $html.= '</td><td>';                
                if (isset($entry["first"])) $html.= date("H:i:s d.m.Y",(integer)$entry["first"]);            
                $html.= '</td><td>';                
                if (isset($entry["cleanup"])) $html.= $entry["cleanup"];            
                $html.= '</td></tr>'; 
                }
            $html.='</table><br>';
            $html .= '<table class="quick green">';
            foreach ($result as $path => $entry)
                {
                $html.= '<tr><td>';
                //$html.= $path.'</td><td>';            // path ist redundant, nicht notwendig
                $html.= $entry["name"].'</td><td>';
                if (isset($entry["Size"])) $html.= number_format((floatval(str_replace(",",".",$entry["Size"]))/1024/1024),3,",",".")." MByte";
                $html.= '</td><td>';
                if (isset($entry["Filecount"])) $html.= $entry["Filecount"];
                $html.= '</td><td>';
                if (isset($entry["type"])) $html.= $entry["type"];
                $html.= '</td><td>';
                if (isset($entry["logFilename"])) 
                    {  // path rausrechnen
                    $details=$this->pathXinfo($entry["logFilename"]);
                    if ($debug) echo "writeTableStatus : ".$path."\n";
                    $html.= $details["Filename"];
                    }
                $html.= '</td><td>';
                if (isset($entry["status"])) $html.= $entry["status"];
                $html.= '</td><td>';
                if (isset($entry["logFiledate"])) $html.= $entry["logFiledate"];            
                $html.= '</td><td>';
                if (isset($entry["last"])) $html.= date("H:i:s d.m.Y",(integer)$entry["last"]);            
                $html.= '</td><td>';                
                if (isset($entry["first"])) $html.= date("H:i:s d.m.Y",(integer)$entry["first"]);            
                $html.= '</td></tr>'; 
                }
            $html.='</table>';
            echo $html;
            }
        echo "\n"; // neue Zeile nach Backup Tabelle 
        foreach ($resultfull as $BackupDirEntry => $entry)
            {
            if (strpos($BackupDirEntry,"Source")===false)
                {
                if ( ((isset($entry["status"])) && ($entry["status"]=="error")) || ((isset($entry["cleanup"])) && ($entry["cleanup"]=="delete")) )
                    {
                    if ($debug !== true) 
                        {
                        echo " !!! Delete Funktion in BackupStatusError: Verzeichnis $BackupDirEntry wird gelöscht.\n";
                        $this->dosOps->rrmdir($BackupDirEntry);
                        }
                    else echo "Delete Verzeichnis $BackupDirEntry. Im Debug Modus wird nicht geloescht !\n";
                    }
                }
            }  
        }

    /*****************************
     *
     * Backup Verzeichnis auslesen, Array aller Logfiles von Backups (damit möglicherweise vollendete Backups ausgeben)
     * wenn kein Verzeichnis mehr zum Logfile vorhanden ist, auch dieses zum Loeschen anmerken.
     * Array mit allen zu löschenden xxx.Backup.csv Files übergeben
     *
     ***********/

    function cleanupBackupLogTable($debug=false)
        {
        if ($debug) echo "cleanupBackupLogTable aufgerufen.\n";
        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist
        $dir=$this->readdirToArray($BackupDrive);                           // nicht rekursiv nur aktuelle Ebene einlesen
        $BackupLogs=array();
        foreach ($dir as $entry)
            {
            if (is_file($BackupDrive.$entry)) $BackupLogs[]=$BackupDrive.$entry;        // wenn eine Datei dann im Array abspeichern
            }

        /* Backup Logfile Array erstellen, wird in gemeinsame Tabelle übernommen */
		$backUpLogTable=array();
		foreach ($BackupLogs as $BackupLog)
			{
			$Logfile=$this->pathXinfo($BackupLog); 
            //echo "    $BackupLog \n"; print_r($Logfile);
			if (isset($Logfile["DirectoryX"]))
                { 
                if (isset($Logfile["Type"][2])) 
                    {
                    if ( ($Logfile["Type"][2]=="csv") && ($Logfile["Type"][1]=="backup") )
                        {	// wahrscheinlich gültiger Filename
                        //echo "   gefunden $BackupLog\n";
                        $backUpLogTable[$Logfile["DirectoryX"]]["filename"]=$BackupLog;
                        $backUpLogTable[$Logfile["DirectoryX"]]["type"]=$Logfile["Type"][0];
                        $backUpLogTable[$Logfile["DirectoryX"]]["filedate"]=date("YmdHis",filemtime($BackupLog));
                        }
                    else if ($debug) echo "$BackupLog extensions nicht backup.csv \n";
                    }
                else if ($debug) echo "$BackupLog nicht gefunden Type 2\n";
                }
            else if ($debug) echo "$BackupLog kein Backup DirectoryX, Filesize : ".filesize($BackupLog)."\n";
			}

        $deleteCsvFiles=array(); 
        echo "Backup Log Dateien Übersicht :\n"; 
        foreach ($backUpLogTable as $directory => $entry)
            {
            $result=$this->dosOps->readdirToStat($BackupDrive.$directory,true);         // rekursiv auslesen
            //print_r($result);
            echo "   ".str_pad($entry["filename"],80)."   ".str_pad(filesize($entry["filename"]),12)."   ".str_pad($entry["type"],12)."   ".str_pad($entry["filedate"],20)." $directory  \n";
            echo "         ".str_pad($BackupDrive.$directory,50)."  ".$result["files"]."  ".$result["dirs"]."\n";
            if ($result["files"]==0) $deleteCsvFiles[]=$entry["filename"];
            }             
        return ($deleteCsvFiles);
        }

    /*********************************************************************************************
     *
     * copyFile Funktion für rekursive Funktion backupDir
     *
     * copies file from source to target, updates infos in params and logs
     * von der source wird das creation und modified Datum ermittelt
     *
     * wenn es die Zieldatei schon gibt, ermitteln ob sie überschrieben werden muss
     *      available:  nein, Datum Datei im Backupverzeichnis ist jünger als Quelle 
     *      copied:     ja, Datei wurde mit Quelle ueberschrieben, "OVERWRITE" parameter konfiguriert
     *      doUpdate:   ja, Datei wurde aber nicht mit Quelle ueberschrieben, "KEEP" parameter konfiguriert
     *
     * wenn es die Zieldatei noch nicht gibt, sicherstellen das es das Verzeichnis gibt
     *      copied:     ja, Datei wurde mit Quelle ueberschrieben
     *
     **************************************************************************/

    function copyFile($Source, $Target, &$log, &$params, $debug=false)
        {
        $fileTimeInt=filectime($Source);			// eindeutiger Identifier, beim Erstellen des Files festgelegt 
        $fileTime=date("YmdHis",$fileTimeInt);            
        $filemTimeInt=filemtime($Source);		// Datum der letzten Aenderung, fuer Backup interessant, Zeitstempel darf nicht groesser als Backup sein
        $filemTime=date("YmdHis",$filemTimeInt);            
        if (is_file($Target))
            {
            $targetFilemTimeInt=filemtime($Target);
            $targetFilemTime=date("YmdHis",$targetFilemTimeInt);                
            //echo " -> bereits vorhanden. Datum vom Backup ist $targetFilemTime.\n";
            if ($targetFilemTimeInt >= $filemTimeInt) $log[$Target]="available:$fileTime:$filemTime";
            else            // Source File hat sich geändert, hat neueres Datum als Target file 
                {
                //echo "$Target => Zeit Backupfile zu alt, hat sich mittlerweile geändert Source: $filemTime    Target: $targetFilemTime     \n";
                if ($params["update"]=="overwrite")
                    {
                    $startCopyTime=time();
                    $filesize=filesize($Source);
                    copy($Source,$Target);
                    if ($debug) echo "  copy/update $Source,$Target with $filesize ".$this->runTime($startCopyTime)." ".$this->writeSpeed($startCopyTime,$filesize)." \n";
                    $log[$Target]="copied:$fileTime:$filemTime";
                    $params["copied"]=$params["copied"]+1;                // Anzahl der kopierten Dateien, weniger wenn zB schon einmal vorher aufgerufen            
                    }
                else $log[$Target]="doUpdate:$fileTime:$targetFilemTime:$filemTime";
                }
            }
        else 
            {
            $startCopyTime=time(); $filesize=filesize($Source);
            $this->dosOps->mkdirtree($Target);
            copy($Source,$Target);
            if ($debug) echo "  copy/mkdirtree $Source,$Target with $filesize ".$this->runTime($startCopyTime)." ".$this->writeSpeed($startCopyTime,$filesize)." \n";
            $log[$Target]="copied:$fileTime:$filemTime";
            $params["copied"]=$params["copied"]+1;                // Anzahl der kopierten Dateien, weniger wenn zB schon einmal vorher aufgerufen            
            //echo "$Target copy \n";
            }
        }


    /************************************************************************************************************** 
     *
     * Groesse eines Backups feststellen 
     *
     * Routine ist langsam, deswegen auch eine Zusammenfassung als Cache erstellen
     * Parameter:
     *  Directory       Verzeichnis in dem das Backup gespeichert ist
     *  params          Die Parameter für die Ausführung der Routine
     *  $result         das Ergebnis, Zeile für Zeile, eine Zeile ist ein Filename
     *  Backup          der Name derf Spalte, wenn leer wird keien Spalte angelegt
     *
     * liest das Verzeichnis $Directory in das array $result ein. Die Dateien werden mit Dateiname = array Backup => ModifiedDate gespeichert 
     *
     */

    function readBackupDir($Directory,&$params,&$result,$Backup="",$mode="date",$debug=false, $indent=false)
        {
        //if ($debug) echo "readBackupDir aufgerufen für $Directory.\n";
        $i=0; $imax=5;
        if ($indent !== false) $indent="  ".$indent;
        if ( (isset($params["BackupDrive"]))===false) $params["BackupDrive"]="";

        $Directory = $this->dosOps->correctDirName($Directory);         // with slash or backslash at the end
        if ( (isset($params["size"])) === false) $params["size"]=0;
        if ( (isset($params["count"])) === false) $params["count"]=0;  
        $dirChildren=$this->dosOps->readdirToArray($Directory);
        if ( (count($dirChildren)) == 0)
            {
            if ($debug) echo "readBackupDir : ".count($dirChildren)." Eintraege im Verzeichnis $Directory.\n";
            }
        else
            {
            foreach ($dirChildren as $entry)
                {
                if ($debug && ($i++ < $imax)) echo "readBackupDir: $i Lese ".$Directory.$entry."  \n";
                if (is_dir($Directory.$entry))                
                    {
                    If ($indent) echo $indent.$Directory.$entry."\n"; 
                    $this->readBackupDir($Directory.$entry, $params, $result, $Backup, $mode, $debug, $indent);
                    }
                if (is_file($Directory.$entry))
                    {
                    if (strpos($Directory.$entry,$params["BackupDrive"])===false) echo "Fehler ".$params["BackupDrive"]." nicht in ".$Directory.$entry." gefunden.\n";   
                    $BackupFilename=substr($Directory.$entry,strlen($params["BackupDrive"]));
                    $value=$this->readFileProps($params, $mode, $Directory.$entry);
                    if ($Backup=="") $result[$BackupFilename] = $value;
                    else 
                        {
                        $result[$BackupFilename][$Backup] = $value;
                        if ($debug && ($i++ < $imax)) 
                            {
                            echo "  readBackupDir: $i Schreibe Zeile $BackupFilename Spalte $Backup mit $value  \n";
                            print_r($result[$BackupFilename]);
                            }
                        } 
                    }
                }	// ende foreach
            }
        }

    /* Groesse der Source eines Backups feststellen 
     *
     * Übergabe ist weiterhin das aktuelle Directory damit rekursive Aufrufe möglich sind
     *
     *
     *
     */

    function readSourceDir($Directory,&$params,&$result,$Backup="",$mode="date", $indent=false)
        {
        $i=0; $imax=100;
        if ($indent !== false) $indent="  ".$indent;
       
        $Directory = $this->dosOps->correctDirName($Directory);         // with slash or backslash at the end
        if ( (isset($params["size"])) === false) $params["size"]=0;
        if ( (isset($params["count"])) === false) $params["count"]=0;  
        $dirChildren=$this->dosOps->readdirToArray($Directory);
        foreach ($dirChildren as $entry)
            {
            //if ($i++ < $imax) echo "Lese ".$Directory.$entry."  \n";
            if (is_dir($Directory.$entry))                
                {
                If ($indent) echo $indent.$Directory.$entry."\n"; 
                $this->readSourceDir($Directory.$entry, $params, $result, $Backup, $mode, $indent);
                }
            if (is_file($Directory.$entry))
                {
                $BackupFilename=substr($Directory.$entry,strlen($params["BackupSourceDir"])-1);
                $value=$this->readFileProps($params, $mode, $Directory.$entry);
                $params["size"]  = $params["size"]+filesize($Directory.$entry);                   
                $params["count"] = $params["count"]+1;                   
                if ($Backup=="") $result[$BackupFilename] = $value;
                else $result[$BackupFilename][$Backup] = $value; 
                }
            }	// ende foreach
        }

    /* die Properties einer Datei mitschreiben */

    private function readFileProps(&$params, $mode, $filename)
        {
        if (is_file($filename))
            {
            $fileTimeInt=filemtime($filename);
            $FileSize=filesize($filename);			 
            $fileTime=date("YmdHis",$fileTimeInt);                    
            //$params["size"]  = $params["size"]+filesize($filename);                   
            //$params["count"] = $params["count"]+1;
            if ( (isset($params["latest"])) && (($params["latest"])>=$fileTimeInt) ) ;
            else $params["latest"]=$fileTimeInt;
            }
        else
            {
            $fileTime="na";  $fileTimeInt=0;  $FileSize=0;
            }
        switch ($mode)
            {
            case "date":    
                $value=$fileTime;
                break;
            case "date&size":
                $value=json_encode(["date"=> $fileTimeInt, "size" => $FileSize]);
                break;    
            }
        return ($value);    
        }

    /* Groesse der Source eines Backups feststellen, Gesamtroutine 
     * verwendet rekursives readSourceDir
     * 
     * params       die Parameter
     * result       das Ergebnis Array
     * mode         unterschiedliche Betriebsarten
     */

    function readSourceDirs(&$params,&$result,$mode="date",$debug=false)
        {
        $Backup="Source";
        $backupSourceDirs=$params["BackupDirectoriesandFiles"];
        $sourceDir=$params["BackupSourceDir"];  
        $params["size"]=0;  $params["count"]=0;
      
        if ($debug) echo "readSourceDirs $sourceDir.\n";
        //echo "printParams für die Auswertung der Source : ".$params["BackupSourceDir"]."\n";  print_r($params);
        $size=0; $count=0;
        foreach ($backupSourceDirs as $backupSourceDir)
            {
            if ($debug) echo "Source Dir evaluieren ".$sourceDir.$backupSourceDir."\n";
            if (is_dir($sourceDir.$backupSourceDir))		// Directory wird bearbeitet
                {
                /* readSourceDir($Directory,&$params,&$result,$Backup="",$mode="date", $indent=false) */
                $this->readSourceDir($sourceDir.$backupSourceDir,$params, $result, $Backup, $mode);  
                }
            if (is_file($sourceDir.$backupSourceDir))
                {
                $BackupFilename=substr($sourceDir.$backupSourceDir,strlen($params["BackupSourceDir"])-1);
                $value=$this->readFileProps($params, $mode, $sourceDir.$backupSourceDir);
                $params["size"]  = $params["size"]+filesize($sourceDir.$backupSourceDir);                   
                $params["count"] = $params["count"]+1;                
                if ($Backup=="") $result[$BackupFilename] = $value;
                else $result[$BackupFilename][$Backup] = $value; 
                }
            if ($debug) 
                {
                echo number_format((($params["size"]/1024/1024)-$size),3,",",".")." MByte ".($params["count"]-$count)." aktuell und ".number_format(($params["size"]/1024/1024),3,",",".")." MByte ".$params["count"]." Files insgesamt. \n";
                echo "Source Dir evaluieren ".$sourceDir.$backupSourceDir." : ".number_format(($params["size"]/1024/1024),3,",",".")." MByte Speicher und ".$params["count"]." Files insgesamt.\n"; 
                }
            $size=$params["size"]/1024/1024;
            $count=$params["count"];
            } 
        }

    /* Überprüfe Backup, Gesamtroutine 
     * verwendet rekursives checkSourceBackupDir
     * 
     * params       die Parameter
     * result       das Ergebnis Array
     * mode         unterschiedliche Betriebsarten, nur Datzum oder json encoded mehr Informationen
     *
     */

    function checkSourceBackupDirs(&$params,&$result,$mode="date", $debug=false)
        {
        $Backup="Source";
        $backupSourceDirs=$params["BackupDirectoriesandFiles"];
        $sourceDir=$this->dosOps->correctDirName($params["BackupSourceDir"]);        
        $targetDir=$this->dosOps->correctDirName($params["BackupTargetDir"]);
        $params["size"]=0; $params["sizeInc"]=0;
        $params["count"]=0; $params["countInc"]=0;  
        if ($debug) echo "Vergleiche Verzeichnis $sourceDir mit Backup $targetDir.\n";
        //echo "printParams für die Auswertung der Source : ".$params["BackupSourceDir"]."\n";  print_r($params);
        //echo "  Source Dir evaluieren ".$sourceDir." : ".number_format(($params["size"]/1024/1024),3,",",".")." MByte Speicher und ".$params["count"]." Files insgesamt.\n"; 
        //echo "  Target Dir evaluieren ".$sourceDir." : ".number_format(($params["sizeInc"]/1024/1024),3,",",".")." MByte Speicher und ".$params["countInc"]." Files insgesamt.\n"; 
        $size=0; $count=0;
        foreach ($backupSourceDirs as $backupSourceDir)
            {
            //echo "Source Dir evaluieren ".$sourceDir.$backupSourceDir."\n";
            if (is_dir($sourceDir.$backupSourceDir))		// Directory wird bearbeitet
                {
                /* readSourceDir($Directory,&$params,&$result,$Backup="",$mode="date", $indent=false) */
                $this->checkSourceBackupDir($sourceDir.$backupSourceDir, $backupSourceDir, $params, $result, $Backup, $mode, false, $debug);    // no indent
                }
            if (is_file($sourceDir.$backupSourceDir))
                {
                $BackupFilename=substr($sourceDir.$backupSourceDir,strlen($params["BackupSourceDir"])-1);
                $value1=$this->readFileProps($params, $mode, $sourceDir.$backupSourceDir);
                $value2=$this->readFileProps($params, $mode, $targetDir.$backupSourceDir);
                $params["size"]=$params["size"]+filesize($sourceDir.$backupSourceDir);
                $params["count"]=$params["count"]+1;                 // Anzahl bearbeiteter Dateien
                if ($value2 != "na")
                    {
                    $params["sizeInc"]=$params["sizeInc"]+filesize($targetDir.$backupSourceDir);
                    $params["countInc"]=$params["countInc"]+1;                 // Anzahl kopierter Dateien
                    }
                if ($Backup=="") $result[$BackupFilename] = json_encode(["Source" => $value1,"Target" => $value2]);
                else $result[$BackupFilename][$Backup] = json_encode(["Source" => $value1,"Target" => $value2]); 
                }
            //echo number_format((($params["size"]/1024/1024)-$size),3,",",".")." MByte ".($params["count"]-$count)." aktuell und ".number_format(($params["size"]/1024/1024),3,",",".")." MByte ".$params["count"]." Files insgesamt. \n";
            if ($debug) echo "  Source Dir evaluieren ".$sourceDir.$backupSourceDir." : ".number_format(($params["size"]/1024/1024),3,",",".")." MByte Speicher und ".$params["count"]." Files insgesamt.\n"; 
            if ($debug) echo "  Target Dir evaluieren ".$sourceDir.$backupSourceDir." : ".number_format(($params["sizeInc"]/1024/1024),3,",",".")." MByte Speicher und ".$params["countInc"]." Files insgesamt.\n"; 
            $size=$params["size"]/1024/1024;
            $count=$params["count"];
            } 
        }

    /* Überprüfe Backup, rekurive Routine 
     *
     * Übergabe ist weiterhin das aktuelle Directory damit rekursive Aufrufe möglich sind
     * zusaetzlich das Target für das Backup Directory mitgeben
     *
     *
     */

    function checkSourceBackupDir($Directory,$Target,&$params,&$result,$Backup="",$mode="date", $indent=false, $debug=false)
        {
        //if (isset($params["BackupSourceDir"])===false) print_r($params);
        //if ($debug) echo "      checkSourceBackupDir : echomax ".$params["echo"]." \n";
        $i=0; $imax=100;
        if ( (isset($params["echo"])) === false) $params["echo"]=0;
        if ($indent !== false) $indent="  ".$indent;
       
        $DirectorySource = $this->dosOps->correctDirName($Directory);         // with slash or backslash at the end
        $DiectoryBackup = $this->dosOps->correctDirName($params["BackupTargetDir"]);
        $DirectoryTarget = $this->dosOps->correctDirName($DiectoryBackup.$Target);         // with slash or backslash at the end

        //if ($debug && ($params["echo"]++ < $imax)) echo "  checkSourceBackupDir $DirectorySource with $DirectoryTarget for $Target\n";

        $dirChildren=$this->dosOps->readdirToArray($Directory);
        foreach ($dirChildren as $entry)
            {
            //if ($i++ < $imax) echo "Lese ".$Directory.$entry."  \n";
            if (is_dir($DirectorySource.$entry))                
                {
                If ($indent) echo $indent.$Directory.$entry."\n"; 
                $newTarget=substr($DirectoryTarget.$entry,strlen($DiectoryBackup));
                //if ($debug && ($params["echo"]++ < $imax)) echo "  checkSourceBackupDir $DirectorySource with $DirectoryTarget for $Target calls with $newTarget\n";
                $this->checkSourceBackupDir($DirectorySource.$entry,  $newTarget, $params, $result, $Backup, $mode, $indent, $debug);
                }
            if (is_file($DirectorySource.$entry))
                {
                $BackupFilename=substr($DirectorySource.$entry,strlen($params["BackupSourceDir"])-1);
                $value1=$this->readFileProps($params, $mode, $DirectorySource.$entry);
                $value2=$this->readFileProps($params, $mode, $DirectoryTarget.$entry);
                $params["size"]=$params["size"]+filesize($DirectorySource.$entry);
                $params["count"]=$params["count"]+1;                 // Anzahl bearbeiteter Dateien
                if ($value2 != "na")
                    {
                    if ($debug && ($params["echo"]++ < $imax))  echo "   $BackupFilename :    $value1   $value2                    \n";
                    $params["sizeInc"]=$params["sizeInc"]+filesize($DirectoryTarget.$entry);
                    $params["countInc"]=$params["countInc"]+1;                 // Anzahl kopierter Dateien
                    }
                else
                    {
                    $i=0;
                    while (isset($params["BackupTargetDirs"][$i]))
                        { 
                        $DirectorynewTarget=$this->dosOps->correctDirName($params["BackupTargetDirs"][$i]);
                        if ($debug && ($params["echo"]++ < $imax))  echo "   $BackupFilename : compare $DirectorySource$entry with $DirectoryTarget$entry, Ziel nicht vorhanden, probiere $DirectorynewTarget$entry .\n";
                        $value2=$this->readFileProps($params, $mode, $DirectorynewTarget.$entry); 
                        if ($value2 != "na")
                            {   
                            $params["sizeInc"]=$params["sizeInc"]+filesize($DirectorynewTarget.$entry);
                            $params["countInc"]=$params["countInc"]+1;                 // Anzahl kopierter Dateien
                            break;
                            }
                        elseif ($debug && ($params["echo"]++ < $imax)) echo "   $BackupFilename : compare $DirectorySource$entry with $DirectoryTarget$entry, Ziel nicht vorhanden, probiere $DirectorynewTarget$entry -> not found !!!! .\n";
                        }
                    }
                if ($Backup=="") $result[$BackupFilename] = json_encode(["Source" => $value1,"Target" => $value2]);
                else $result[$BackupFilename][$Backup] = json_encode(["Source" => $value1,"Target" => $value2]); 
                }
            }	// ende foreach
        }

    /* Zusammenfassung eines Backups als Logfile geben
     *
     * Inhalt von log in die Datei schreiben.
     * wird immer am Ende eines fertig gestellten Backups geschrieben, oder nach Cleanup
     *
     *
     */

    function writeBackupLogStatus(&$log, $debug=false)
        {
        $params=$this->getConfigurationStatus("array");
        $params["status"]="finished";
        $this->setConfigurationStatus($params, "array");
        $this->setBackupStatus("Status : ".$params["status"]."  ".date("Y:m:d H:i:s"));    

        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);      
        $fileName=$BackupDrive.pathinfo($params["BackupTargetDir"])["basename"]."_".$params["style"].".backup.csv";
        Echo "Backup Zusammenstellung in das File $fileName schreiben.\n"; 
        if (is_file($fileName)) unlink($fileName);
        $handle=fopen($fileName, "a");
        $count=0; $maxentry=100;
        foreach ($log as $file => $entry)
            {
            $entryArray=explode(":",$entry);
            if ($count++<$maxentry) 
                {
                echo $file." => ".$entryArray[1].";".$entry."\n";
                }
            fwrite($handle, $file.";".$entryArray[1].";".$entry."\n");
            }
        fclose($handle);
        }


    /*************************************************************************************************** 
	 *
	 * Zusammenfassung des Zustandes aller Backups im File Backup.csv geben
     * verwendet getBackupDrive, getBackupDirectories, getBackupLogTable für den Einstieg als Statusüberblick
     *
     *
	 * Ausführzeiten werden schnell sehr lange, analysiert jedes Verzeichnis im Backup Verzeichnis
     * daher reload modus für Statemachine und Mehrfachaufrufe
     * wenn fertig kann das SummaryofBackups.csv File mit den detaillierten Angeban über Anzahl und Groese der Dateien upgedatet werden
	 *
	 * verschiedene Parameter für die Gestaltung der Ausführung, 
     * verwendet im OperationCenter Timer für Cleanup : $result=$BackupCenter->getBackupDirectoryStatus("reload");
     * und nach einem fertig gestelltem Backup.
     *
	 * reload       das langfristigste Unterfangen alle Datei Modifizierungsdaten werden in eine Tabelle backup.csv eingetragen
	 *              es sind mehrere Aufrufe notwendig
	 * update       einmaliger Aufruf wenn ein Backup fertig gestellt wurde
     *
     */

    function getBackupDirectoryStatus($mode,$debug=false)
        {
        if ($debug) echo "getBackupDirectoryStatus mit Mode $mode aufgerufen.\n";

        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist

        /* alle Verzeichnisse im Backup */
        $BackupDirs=$this->getBackupDirectories($debug);        
        if ($debug) 
            { 
            echo "\ngetBackupDirectoryStatus Mode $mode : Backup Verzeichnisse in $BackupDrive auflisten :\n"; 
            //print_r($BackupDirs);
            echo "   Verzeichnis                            Anzahl Dateien  Verzeichnisse\n";
            foreach ($BackupDirs as $BackupDir)
                {
                $result=$this->dosOps->readdirToStat($BackupDir,true);
                //print_r($result);
                echo "   ".str_pad($BackupDir,50)."  ".$result["files"]."  ".$result["dirs"]."\n";
                }            
            }

        /* Backup Logfile Array erstellen, wird in gemeinsame Tabelle übernommen */
		$backUpLogTable=$this->getBackupLogTable($debug);        
		//if ($debug) { echo "Backup Logfile Table:\n"; print_r($backUpLogTable); }

        $result=array();            // alle Backups zusammenfassen in einem grossen array, index ist der Backupname oder Source als Referenz

        /*************************************************************
         *
         * und auch noch das grosse Backup.csv scheiben in dem alle Dateien mit dem im Verzeichnis gespeicherten Datum angelegt werden 
		 * geht sich nicht mehr in einem Durchlauf aus, daher einem nach den anderen Machen
		 * Der automatische Timer wird auf reload gestellt und ruft diese Routine auf. Es wird solange false als return gegeben bis die Datei vollstaendig erstellt wurde.
		 *
		 *************************************************/

	    if ( ($mode == "reload") || ($mode == "update") )
            {
            if ($debug) echo "getBackupDirectoryStatus mit Mode $mode aufgerufen.\n";
    		$params=$this->getConfigurationStatus("array");     // aktuellen Status auslesen, statemaschine ist auf cleanup
            if  ( ($mode == "reload") && ($params["cleanup"]=="cleanup-read") )         // war vorher started, wird aber nur bei Backup verwendet
                {
                if ($debug) echo "Backup.csv auf Backup.old.csv umbenennen und neu erstellen.\n";  
                $this->fileOps->backcupFileCsv();  
                $params["cleanup"]="ongoing";
                }
            if ( ($params["cleanup"]=="ongoing") || ($mode == "update") )
                {
                $resultBackupDirs=array();
                $result=$this->fileOps->readFileCsv($resultBackupDirs,"Filename",[],["Source","20190707"]);
                $indexCsvFile=$result["columns"];
                if ($debug) { echo "Backup.csv einlesen und darin gespeicherte Spalten herausfinden :\n"; print_r($indexCsvFile); }

                echo "Memorysize nach getusage true und false : ".getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb

                if ($debug) echo "Eingelesenes Array (in Memory) hat ".count($resultBackupDirs)." Einträge.\n";
                /* anhand der im $resultBackupDirs erkannten Spalten die noch benötigten Spalten ermitteln */
                $index=array();
                if (!in_array("Source",$indexCsvFile)) $index[]="Source";
                foreach ($BackupDirs as $BackupDirEntry)
                    {
                    //echo "  Groesse der bisher erstellten Backupverzeichnisse für $BackupDirEntry : \n";
                    $pathinfo=pathinfo($BackupDirEntry);
                    $backup=$pathinfo["filename"];
                    if ($debug) echo "    Suche $backup im array.\n";
                    if (in_array($backup,$indexCsvFile) ) { if ($debug) echo "   -> Spalte $backup wurde bereits eingelesen.\n"; }
                    else $index[]=$backup;
                    }  
                if ($debug) { echo "Folgende ".count($index)." Spalten müssen noch eingelesen werden :\n"; print_r($index); }
                if (count($index)>0)
                    {
                    //if ($debug) 
                        {
                        echo "\n-------------------------------------------------------------------------\n";
                        echo "Dann aus dem Backup Verzeichnis das Backup \"".$index[0]."\" einlesen.\n";
                        }
                    $params["BackupDirectoriesandFiles"]=array("db","media","modules","scripts","webfront","settings.json");
                    if ($index[0]=="Source")
                        {
                        if ($debug) echo "getBackupDirectoryStatus: Source Verzeichnis einlesen:\n";
                        $params["Statustext"]="getBackupDirectoryStatus : Source Verzeichnis einlesen.";
                        $params["BackupSourceDir"]=$this->dosOps->correctDirName($this->getSourceDrive());
                        $params["count"]=0;     /* Zähler zurücksetzen */
                        $params["size"]=0;
                        $this->readSourceDirs($params, $resultBackupDirs,"date&size");        // Übergabe in resultBackupDirs
                        }
                    else
                        {
                        $params["Statustext"]="getBackupDirectoryStatus : Backup Verzeichnis einlesen.";
                        $BackupDirEntry=$BackupDrive.$index[0];
                        $params["BackupDrive"]=$BackupDirEntry;
                        $params["count"]=0;       /* Zähler zurücksetzen */
                        $params["size"]=0;
                        //if ($debug) { echo "Backup Verzeichnis einlesen mit folgenden Parametern :\n"; print_r($params); }
                        /*     function readBackupDir($Directory,&$params,&$result,$Backup="",$mode="date",$debug=false, $indent=false) */
                        $this->readBackupDir($BackupDirEntry, $params, $resultBackupDirs, $index[0], "date&size", false);           // mit Debug
                        if ($debug) { echo "Ergebnis readBackupDir:\n"; print_r($params); }
                        if  ($params["count"]==0)
                            {
                            echo "Ein leeres Verzeichnis. Dummy Eintrag machen.\n"; 
                            $resultBackupDirs["."][$index[0]]=false;   
                            }                                            
                        echo "fertig gestellt.\n";    
                        }
                    echo "WriteFileCsv : \n"; 
                    //print_r($resultBackupDirs);
                    $this->fileOps->writeFileCsv($resultBackupDirs,true);
                    }
                else 
                    {   /* keine weiteren Verzeichnisse gefunden, SummaryofBackups.csv ergänzen */
                    unset ($resultBackupDirs);
                    echo getNiceFileSize(memory_get_usage(true),false)."/".getNiceFileSize(memory_get_usage(false),false)."\n"; // 123 kb
                    echo "SummaryofBackup.csv updaten.\n";  
                    $this->updateSummaryofBackupFile();  

                    $params["cleanup"]="finished";
                    $params["status"]="finished";
                    }
                }
            $this->writeTableStatus($params);            // ohne Parameter wird das html automatisch geschrieben
            $paramsJson=json_encode($params);
            $this->setConfigurationStatus($paramsJson);
            return ($params);

            }           // ende mode reload/update
        return ($this->BackOverviewTable);
        }

    /****************************
     *
     * analyse Backup.csv as input for SummaryofBackup.csv
     * in Backup.csv steht für jede Datei das letzte Modifikations-Datum und die Filegroesse
     * man benötigt nur mehr eine statistische Auswertung pro Backup verzeichnis
     *
     *****************************/

    function analyseBackupDirectoryStatus(&$ergebnis, $debug=false)
        {
        if ($debug) echo "Ermitteltes Ergebnis aus Backup.csv für SummaryofBackup.csv ausgeben:\n";
        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist

        $fileOps = new fileOps($BackupDrive."Backup.csv");
        $result=array();
        $fileOps->readFileCsv($result,"Filename");      // erste Spalte als Index nehmen
        //$index=$fileOps->readFileCsvFirstline(); print_r($index);
        foreach ($result as $file => $line)
            {
            foreach ($line as $backup =>$columns)
                {
                if (isset($ergebnis[$BackupDrive.$backup]["name"])) ;
                else $ergebnis[$BackupDrive.$backup]["name"]=$backup;           // damit auch Source einen Namen bekommt
                $columnsArray=json_decode($columns,true);
                $size = $columnsArray["size"];
                //echo $size." ";
                //if (!(is_numeric($size))) echo "Fehler, Size ist nicht numerisch\n";    
                //if (is_bool($size)) echo "Fehler, Size ist Boolean\n";    
                if ( ($columnsArray !== Null) && (count($columnsArray)>0) ) 
                    {
                    if (isset($ergebnis[$BackupDrive.$backup]["Filecount"]))
                        {
                        $ergebnis[$BackupDrive.$backup]["Filecount"]++;
                        }
                    else $ergebnis[$BackupDrive.$backup]["Filecount"]=1; 
                    if (isset($ergebnis[$BackupDrive.$backup]["Size"]))
                        {
                        $ergebnis[$BackupDrive.$backup]["Size"]=(integer)$ergebnis[$BackupDrive.$backup]["Size"]+$size;
                        //echo "Add ".$BackupDrive.$backup." : ".$ergebnis[$BackupDrive.$backup]["Size"]." von +=$size\n";
                        }
                    else 
                        {
                        $ergebnis[$BackupDrive.$backup]["Size"]=(integer)$size; 
                        //echo "Initialize  ".$BackupDrive.$backup." : ".$ergebnis[$BackupDrive.$backup]["Size"]." with $size\n";
                        }
                    if (isset($ergebnis[$BackupDrive.$backup]["first"]))
                        {
                        if ($columnsArray["date"] < $ergebnis[$BackupDrive.$backup]["first"]) $ergebnis[$BackupDrive.$backup]["first"]=$columnsArray["date"];
                        }
                    else $ergebnis[$BackupDrive.$backup]["first"]=$columnsArray["date"];
                    if (isset($ergebnis[$BackupDrive.$backup]["last"]))
                        {
                        if ($columnsArray["date"] > $ergebnis[$BackupDrive.$backup]["last"]) $ergebnis[$BackupDrive.$backup]["last"]=$columnsArray["date"];
                        }
                    else $ergebnis[$BackupDrive.$backup]["last"]=$columnsArray["date"];
                    //$ergebnis[$backup]=$columnsArray;    
                    }
                else 
                    {
                    if ( ($columns !== false) && ($columns != "") ) $ergebnis[$BackupDrive.$backup]["error"][] = $columns;
                    //if ($columns !== false) $ergebnis[$backup]["error"][$file][] = $columns;
                    }
                }    
            }
        return($ergebnis);    
        }


    /*****************************
     *
     * Backup Verzeichnis auslesen und alle Verzeichnisse (angefangene und vollendete Backups ausgeben)
     * holt mit getBackupDrive() das Verzeichnis
     * wenn noch kein Verzeichnis angelegt
     ***********/

    function getBackupDirectories($debug=false)
        {
        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist
        if (is_dir($BackupDrive)===false)
            {
            echo "   getBackupDirectories: verzeichnis $BackupDrive nicht vorhanden.\n";
            if (($this->dosOps->mkdirtree($BackupDrive,$debug))===false)
                {
                $backupConfig=$this->getConfigurationBackup();
                if ( (isset($backupConfig["MOUNT"])) && (isset($backupConfig["DRIVELETTER"])) && (isset($backupConfig["USERNAME"])) && (isset($backupConfig["PASSWORD"])) ) 
                    {
                    echo "   mkdirtree hat versagt, es ist komplizierter als gedacht, vielleicht als ".$backupConfig["MOUNT"]." mounten.\n";
                    $location = $backupConfig["MOUNT"];
                    $user     = $backupConfig["USERNAME"];
                    $pass     = $backupConfig["PASSWORD"];
                    $letter   = $backupConfig["DRIVELETTER"];
                    //print_R($backupConfig);
                    echo "Map the drive with net use $letter: \"$location\" $pass /user:$user /persistent:no>nul 2>&1\n";
                    system("net use ".$letter.": \"".$location."\" ".$pass." /user:".$user." /persistent:no>nul 2>&1");
                    if (is_dir("Z:")) echo "jetzt gefunden, erfolgreich.\n";
                    }
                else 
                    {
                    echo "Backup nicht möglich, kein Laufwerk zum Speichern vorhanden.\n";                 // vorher war die, etwas zu hart
                    return (false);
                    }
                }   
            }
        else if ($debug) echo "   getBackupDirectories: aufgerufen und verzeichnis $BackupDrive vorhanden.\n";        

        $dir=$this->dosOps->readdirToArray($BackupDrive);
        if ($debug) 
            {
            if ($dir===false) echo "$BackupDrive laesst sich nicht auslesen.\n";
            echo "   getBackupDirectories für $BackupDrive aufgerufen:\n";
            print_R($dir);
            }
        $BackupDirs=array();
        foreach ($dir as $entry)
            {
            //echo "$BackupDrive$entry   \n";
            if (is_dir($BackupDrive.$entry)) $BackupDirs[]=$BackupDrive.$entry;
            }
        if ($debug) { echo "\nBackup Verzeichnisse :\n"; print_r($BackupDirs); }
        return($BackupDirs);
        }

    /*****************************
     *
     * Backup Verzeichnis auslesen, Tabelle aller Logfiles von Backups (damit möglicherweise vollendete Backups ausgeben)
     *
     ***********/

    function getBackupLogTable($debug=false)
        {
        $debug=true;
        $BackupDrive=$this->getBackupDrive();
        if ($BackupDrive===false) return (false);
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist
        if ($debug) echo "getBackupLogTable aufgerufen. BackupDrive $BackupDrive \n";
        $dir=$this->readdirToArray($BackupDrive);
        if ($dir===false) 
            {
            echo "   Warning, getBackupLogTable, BackupDrive $BackupDrive not available.\n";
            return (false);
            }
        $BackupLogs=array();
        foreach ($dir as $entry)
            {
            if (is_file($BackupDrive.$entry)) $BackupLogs[]=$BackupDrive.$entry;  
            }
        /* if ($debug) 
            { 
            echo "Backup Log Dateien Übersicht :\n"; 
            //print_r($BackupLogs); 
            foreach ($BackupLogs as $entry)
                {
                echo "   ".str_pad($entry,80)."   ".str_pad(filesize($entry),12," ",STR_PAD_LEFT)." \n";
                }
            } *

        /* Backup Logfile Array erstellen, wird in gemeinsame Tabelle übernommen */
		$backUpLogTable=array();
		foreach ($BackupLogs as $BackupLog)
			{
			$Logfile=$this->pathXinfo($BackupLog); 
            //echo "    $BackupLog \n"; print_r($Logfile);
			if (isset($Logfile["DirectoryX"]))
                { 
                if (isset($Logfile["Type"][2])) 
                    {
                    if ( ($Logfile["Type"][2]=="csv") && ($Logfile["Type"][1]=="backup") )
                        {	// wahrscheinlich gültiger Filename
                        //echo "   gefunden $BackupLog\n";
                        $backUpLogTable[$Logfile["DirectoryX"]]["filename"]=$BackupLog;
                        $backUpLogTable[$Logfile["DirectoryX"]]["type"]=$Logfile["Type"][0];
                        $backUpLogTable[$Logfile["DirectoryX"]]["filedate"]=date("YmdHis",filemtime($BackupLog));
                        }
                    else if ($debug) echo "$BackupLog extensions nicht backup.csv \n";
                    }
                else if ($debug) echo "$BackupLog nicht gefunden Type 2\n";
                }
            else if ($debug) echo "$BackupLog kein Backup DirectoryX, Filesize : ".filesize($BackupLog)."\n";
			}
        if ($debug) 
            { 
            echo "Backup Log Dateien Übersicht :\n"; 
            foreach ($backUpLogTable as $directory => $entry)
                {
                $result=$this->dosOps->readdirToStat($BackupDrive.$directory,true);
                //print_r($result);
                echo "   ".str_pad($entry["filename"],80)."   ".str_pad(filesize($entry["filename"]),12)."   ".str_pad($entry["type"],12)."   ".str_pad($entry["filedate"],20)." $directory  \n";
                echo "         ".str_pad($BackupDrive.$directory,50)."  ".$result["files"]."  ".$result["dirs"]."\n";
                }             
            }    
        return ($backUpLogTable);
        }


    /***************************** 
     *
     * getBackupDirectorySummaryStatus:
     * updates $this->BackOverviewTable, can take too long time in "read" mode, read mode erzeugt ein vollstaendiges result file für backup.csv 
     * immer noread verwenden, ausser vielleicht beim ersten mal, oder wenn alle Verzeichnisse gelöscht sind 
     * see state machine driven version for creation/update of backup.csv
     *
     *   alle Backup verzeichnisse durchgehen
     *   der gemeinsame Zustand wird gecached in einer SummaryofBackups.csv Datei
     *
     ********************************************************/

    function getBackupDirectorySummaryStatus(&$result, $mode="noread", $debug=false)
        {

        /* mit read werden die Backupdirs einzeln gelesen, kann abhängig von der Anzahl der Backupdirs sehr lange dauern, daher andere Lösung
         * es wird auch das Arra result beschrieben
         */
        $debug=true;

        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist

        /* alle Verzeichnisse im Backup */
        $BackupDirs=$this->getBackupDirectories(); 
        if ( ($BackupDirs===false) || (sizeof($BackupDirs)==0) )
            {
            echo "   Warning, getBackupDirectorySummaryStatus, getBackupDirectories fails.\n";
            return (false);
            }       
        if ($debug) { echo "\nBackup Verzeichnisse :\n"; print_r($BackupDirs); }

        /* Backup Logfile Array erstellen, wird in gemeinsame Tabelle übernommen */
		$backUpLogTable=$this->getBackupLogTable($debug);        
		//if ($debug) print_r($backUpLogTable);

        $this->BackOverviewTable =array();		/* statistische Daten über die Backups speichern */
        $size=0; $count=0;
        foreach ($BackupDirs as $BackupDirEntry)
            {
            if ($debug) echo "  Groesse der bisher erstellten Backupverzeichnisse für $BackupDirEntry : \n";
            $pathinfo=pathinfo($BackupDirEntry);
            //print_r($pathinfo);
            $data=array();
            $data["BackupDrive"]=$BackupDirEntry;
            $backupName=$pathinfo["filename"];
            if ($mode != "noread") 
                {
                $this->readBackupDir($BackupDirEntry,$data, $result, $backupName);             
                $this->BackOverviewTable[$BackupDirEntry]["size"]=($data["size"]/1024/1024);
                $this->BackOverviewTable[$BackupDirEntry]["count"]=$data["count"];
                echo "      ".number_format(($data["size"]/1024/1024),3,",",".")." MByte ".$data["count"]." Files insgesamt. \n";
                }      
            $this->BackOverviewTable[$BackupDirEntry]["name"]=$pathinfo["filename"];
            if (isset($backUpLogTable[$backupName]))	/* wenn es kein Backup Logfile gibt ist das Backup nicht abgeschlossen */
                {
                $this->BackOverviewTable[$BackupDirEntry]["type"]=$backUpLogTable[$backupName]["type"];
                $this->BackOverviewTable[$BackupDirEntry]["logFilename"]=$backUpLogTable[$backupName]["filename"];
                $this->BackOverviewTable[$BackupDirEntry]["status"]="finished";
                }
            else $this->BackOverviewTable[$BackupDirEntry]["status"]="error";	
            /* echo "      ".number_format((($params["size"]/1024/1024)-$size),3,",",".")." MByte ".($params["count"]-$count)." aktuell und ".number_format(($params["size"]/1024/1024),3,",",".")." MByte ".$params["count"]." Files insgesamt. \n"; 
            $size+=$params["size"]/1024/1024;
            $count+=$params["count"];   */    
            }
        //echo "printParams am Ende der Auswertung der Backups :\n"; print_r($data);
        if ($mode != "noread") 
            {        
            $data=array();
            $sourceDir="C:\Ip-Symcon";
            $sourceDir = $this->dosOps->correctDirName($sourceDir);
            $data["BackupSourceDir"]=$sourceDir;
            if ($this->newstyle) $backupSourceDirs=array("db","media","modules","scripts","user","settings.json");            // ab IPS7 kein webfront Verzeichnis mehr, ersetzt durch user
            else $backupSourceDirs=array("db","media","modules","scripts","webfront","settings.json");
            $data["BackupDirectoriesandFiles"]=$backupSourceDirs;
            //echo "printParams für die Auswertung der Source : ".$data["BackupSourceDir"]."\n"; print_r($params);
            $this->readSourceDirs($data,$result,$mode="date");
            }
        return ($this->BackOverviewTable);
        }

    /******************************************************************************
     *
     * Zusammenfassung des Zustandes aller Backups geben
     *   der Zustand wird gecached in einer SummaryofBackups.csv Datei, siehe function read
     *   hier nur diese Datei auslesen, wenn nicht vorhanden $this->getBackupDirectorySummaryStatus("noread") starten
     *
     * result of function ist die nächste, jüngste Backup Datei
     * zusaetzlich $this->BackOverviewTable schreiben.
     * im Parameter ein array übergeben, das ist die selbe Tabelle wie in $this->BackOverviewTable, damit können result zusammengebaut werden
     *  
     */

    function readBackupDirectorySummaryStatus(&$result, $debug=false)
        {
        $resultfull=array();
        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist
        $fileName = $BackupDrive."SummaryofBackup.csv";

        $fileOps = new fileOps($BackupDrive."SummaryofBackup.csv");
        if ($fileOps->readFileCsv($result,"Filename")) 
            {
            if ($debug) echo "readBackupDirectorySummaryStatus : ".$BackupDrive."SummaryofBackup.csv.\n";
            }
        else 
            {
            if ($debug) echo "readBackupDirectorySummaryStatus : ".$BackupDrive."SummaryofBackup.csv nicht vorhanden, das Backup verzeichnis auslesen.\n"; 
            $result=$this->getBackupDirectorySummaryStatus($resultfull);
            if ($result === false) 
                {
                echo "Warning, no Backup Target Dir.\n";
                return (false);
                }
            }
        if ($debug) print_r($result);
        $this->BackOverviewTable=$result;
 
        /* filter finished, get last full */
        $resultFiltered=array();
        $full=0; $increment=0;
        krsort($result);
        foreach ($result as $path => $entry) 
            {
            if ( (isset($entry["status"])) && (isset($entry["type"])) ) 
                {
                if ( ($full==0) && ($entry["status"]=="finished") &&  ($entry["type"]=="full") ) 
                    {
                    $resultFiltered[$path]=$entry;
                    $result[$path]["cleanup"]="latest_full";
                    $full++;
                    }
                elseif ( ($full==0) && ($increment==0) && ($entry["status"]=="finished") &&  ($entry["type"]=="increment") ) 
                    {
                    $resultFiltered[$path]=$entry;
                    $result[$path]["cleanup"]="latest_inc";
                    $increment++;
                    }
                else 
                    {
                    if ($entry["type"]=="increment") { $result[$path]["cleanup"] = "i".$increment++; }
                    if ($entry["type"]=="full") { $result[$path]["cleanup"] = "f".$full++; }
                    }
                }
            }
        if ($debug) 
            {            
            echo "Werte von SummaryofBackup.csv mit gültigen Status : \n"; 
            print_r($resultFiltered);
            }
        return ($resultFiltered);
        }

    /* Zusammenfassung des Zustandes aller Backups in die SummaryofBackups.csv Datei schreiben, siehe function read
     *
     * Inhalt kommt aus $this->BackOverviewTable , in die Datei schreiben.
     * wird immer am Ende eines fertig gestellten Backups geschrieben, oder nach Cleanup
     *
     *
     */

    function writeBackupDirectorySummaryStatus($debug=false)
        {
        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist

        ksort($this->BackOverviewTable);            // sicherheitshalber aufsteigen, letztes Backup am Ende
        if ($debug) 
            {
            echo "writeBackupDirectorySummaryStatus with following information :\n";
            print_r($this->BackOverviewTable);
            }

        /* write summary csv file if rebuild if file is requested, usually it will only be extended */
        $fileName = $BackupDrive."SummaryofBackup.csv";
        if (is_file($fileName)) rename($fileName, $BackupDrive."SummaryofBackup.old.csv");            
        if ($debug) echo "Zusammenfassung der Backups ist in ".$fileName." gespeichert.\n";
        if (is_file($fileName)) unlink($fileName);

        $fileOps = new fileOps($fileName);
        $fileOps->writeFileCsv($this->BackOverviewTable);

        }

    /* Zusammenfassung des Zustandes aller Backups in die SummaryofBackups.csv Datei schreiben, siehe function read
     *
     * Inhalt kommt aus $this->BackOverviewTable , in die Datei schreiben.
     * wird immer am Ende eines fertig gestellten Backups geschrieben, oder nach Cleanup
     *
     * die Informationen aus dem Backup Verzeichnis werden um Informationen aus dem Backup.csv File und den logfiles angereichert.
     */

    function updateSummaryofBackupFile($debug=false)
        {
        //if ($debug) echo "Allocated Memory : ".getNiceFileSize(memory_get_usage(false),false)." / ".getNiceFileSize(memory_get_usage(true),false)."\n"; // true including unused pages
        $params=$this->getConfigurationStatus("array");
        $ergebnis=array();
        $this->analyseBackupDirectoryStatus($ergebnis);
        if ($debug) 
            {
            echo "Allocated Memory : ".getNiceFileSize(memory_get_usage(false),false)." / ".getNiceFileSize(memory_get_usage(true),false)."\n"; // true including unused pages    
            echo "updateSummaryofBackupFile: status von Backup aus Backup.csv eruieren (Rückmeldung analyseBackupDirectoryStatus) :\n";
            print_r($ergebnis);
            } 
        $backUpLogTable = $this->getBackupLogTable($debug);  
        if ($debug) 
            {
            echo "   vorhandene Datum_backup.csv Log Files:\n";
            print_r($backUpLogTable);         
            }
        foreach ($ergebnis as $BackupDirEntry => $entry)
            {
            $pathinfo=pathinfo($BackupDirEntry);
            $backupName=$pathinfo["filename"];
            if ($debug) echo "suche $backupName in backUpLogTable.\n";
            if (isset($backUpLogTable[$backupName]))	/* wenn es kein Backup Logfile gibt ist das Backup nicht abgeschlossen */
                {
                if ($debug) echo "  --> gefunden $backupName in backUpLogTable:\n"; print_r($backUpLogTable[$backupName]);
                $ergebnis[$BackupDirEntry]["type"]=$backUpLogTable[$backupName]["type"];
                $ergebnis[$BackupDirEntry]["logFilename"]=$backUpLogTable[$backupName]["filename"];
                $ergebnis[$BackupDirEntry]["logFiledate"]=$backUpLogTable[$backupName]["filedate"];         // zusaetzlich Datum in die Tabelle aufnehmen
                $ergebnis[$BackupDirEntry]["status"]="finished";
                }
            else $ergebnis[$BackupDirEntry]["status"]="error";
            }
        $this->BackOverviewTable=$ergebnis;
        if ($debug) 
            {
            echo "Ergebnis von updateSummaryofBackupFile, neuer BackOverviewTable:\n";
            print_r($ergebnis);
            }
        $this->writeBackupDirectorySummaryStatus($debug);
        $this->writeTableStatus($params);
        if ($debug) echo "Allocated Memory : ".getNiceFileSize(memory_get_usage(false),false)." / ".getNiceFileSize(memory_get_usage(true),false)."\n"; // true including unused pages
        }

    /*************************************************************+ 
     *
     * Zusammenfassung des Zustandes von Backup geben
     * dreispaltige Tabelle mit Untertabellen in das Webfront schreiben
     *
     * wird von Start und stopp_backup, config_mode und anderen aufgerufen. Soll schnell die html Style Tabelle updaten und keine echo Ausgaben machen  
     *
     */

    function writeTableStatus($params=false, $debug=false)
        {
        $resultfull=array();
        if ($params !== false) $tabledata=$params;
        else 
            {
            $tabledata=$this->getConfigurationStatus("array");                
            }
        /* geschrieben wird in $this->BackOverviewTable. Entweder aus der Cache Datei, oder neu ausgelesen ohne die Groesse des Directory zu erfassen aus der Filestruktur */
        $result=$this->readBackupDirectorySummaryStatus($resultfull, $debug);          // komplettes array ist in resultfull, zusaetzlich wird $this->BackOverviewTable upgedatet
        if ($debug) 
            {
            echo "writeTableStatus : read SummaryofBackup.csv and writes html Table.\n";
            print_r($this->BackOverviewTable);
            }
        $html='';                           /* html start */    
        $html.='<style>';
        $html.='.boxes={background-color: blue; color: white; margin: 20px; padding: 20px; border: 1px solid green;}';
        $html.='.subboxes={background-color: blue; color: white; margin: 20px; padding: 20px; border: 1px solid green;}';    
        $html.='table {border: 1px solid white; border-collapse: collapse; font-family: arial, sans-serif;} '; 
        $html.='td, th {border: 1px solid #dddddd; text-align: left; padding: 2px; } ';
        $html.='tr:nth-child(even) { background-color: #dddddd; color: black;} ';
        $html.='</style>';
        $html.='<table style="width:100%" >';
        $html.='<tr><td><table class="subboxes">';           /* in der ersten Tabelle eine Untertabelle anfangen */
        if (isset($tabledata["style"])) 
            {
            $html.= '<tr><td>Backup Style</td><td>'.$tabledata["style"].'</td></tr>'; 
            unset($tabledata["style"]);
            $html.= '<tr><td>Count act/target</td><td>'.number_format($tabledata["count"],0,",",".").' / '.number_format($tabledata["countTarget"],0,",",".").'</td></tr>'; 
            unset($tabledata["count"]);  unset($tabledata["countTarget"]);      
            $html.= '<tr><td>Size act/target</td><td>'.number_format($tabledata["size"],0,",",".").' / '.number_format($tabledata["sizeTarget"],0,",",".").'</td></tr>'; 
            unset($tabledata["size"]);  unset($tabledata["sizeTarget"]);                 
            }
        foreach ($tabledata as $name => $entry)
            {
            if (is_array($entry))
                {   /* untergeodnete Arrays im writeTable igorieren */
                //echo ">>".$name."\n"; print_r($entry); echo "\n";
                switch ($name)
                    {
                    case "BackupDirectoriesandFiles":
                    default:
                        break;
                    }
                }
            else $html.= '<tr><td>'.$name.'</td><td>'.$entry.'</td></tr>';
            }
        $html.='</table>';
        $html.='</td><td>|   ................   <>   ....................... |</td><td><table>';
		foreach ($this->BackOverviewTable as $path => $entry)
			{
            $html.= '<tr><td>';
            //$html.= $path.'</td><td>';            // path ist redundant, nicht notwendig
            $html.= $entry["name"].'</td><td>';
            if (isset($entry["Size"])) $html.= number_format((floatval(str_replace(",",".",$entry["Size"]))/1024/1024),3,",",".")." MByte";
            $html.= '</td><td>';
            if (isset($entry["Filecount"])) $html.= $entry["Filecount"];
            $html.= '</td><td>';
            if (isset($entry["type"])) $html.= $entry["type"];
            $html.= '</td><td>';
            if (isset($entry["logFilename"])) 
                {  // path rausrechnen
                if ($debug) echo "writeTableStatus : ".$path."\n";
                $len = strlen(pathinfo($path)["dirname"]);
                $logfilename=substr($entry["logFilename"],$len);
                $html.= $logfilename;
                }
            $html.= '</td><td>';
            if (isset($entry["status"])) $html.= $entry["status"];
            $html.= '</td><td>';
            if (isset($entry["logFiledate"])) $html.= $entry["logFiledate"];            
            $html.= '</td></tr>';
			}
		$html.='</table></td><td>#3</td></tr></table>';
        
        $this->setTableStatus($html);
        }

    /* zu einem Backup Log Filenamen relevante Informationen ableiten:
     *
     * Directory       der Input Parameter BackupLogFilename bestehend aus filename plus path mit einheitlichem backslash als Verzeichnis Seperatoren
     * DirectoryX      nur der Name des Backupverzeichnis, ohne backslash vorher und nachher
     * Path            der Pfad, also bis zu dem letztem Backslash im BackupLogFilename
     * Filename        der Filename, also ab dem letztem Backslash im BackupLogFilename
     *
     * Beispiel für E:\Backup\IpSymcon/20190812_full.backup.csv
     *      Directory   E:\Backup\IpSymcon\20190812_full.backup.csv
     *      DirectoryX  20190812
     *      Path        E:\Backup\IpSymcon\
     *      PathX       E:\Backup\IpSymcon\20190812
     *      Filename    20190812_full.backup.csv
     *
     */

    function pathXinfo($BackupLogFilename, $debug=false)
        {
        $result=array();
        $directory = str_replace('/','\\',$BackupLogFilename);      // alle einheitlich mit backslash getrennt
        /* Directory und Filenamen voneinander trennen */
        $path=substr($directory,0,strrpos($directory,'\\')+1);
        $filename=substr($directory,(strrpos($directory,'\\')+1));
        /* Filenamen analysieren */
        $pos1=strrpos($filename,"_");       // letztes underscore
        if ($pos1 != false)
            {
            $result["DirectoryX"]=substr($filename,0,$pos1);
            $result["PathX"]=$path.$result["DirectoryX"]."\\";
            $result["Type"]=explode(".",substr($filename,$pos1+1)); 
            }
        $pos2=strpos($filename,"_");        // erstes underscore
        if ($pos2 != false)
            {
            $result["Date"]=substr($filename,0,$pos2);
            }
        $pos3=strpos($path,"\\\\");        // erstes \\ für ein Netvolume
        //echo "Netzlaufwerk finden in $path, suche \\\\ auf $pos3 .\n";
        if (($pos3 !== false) && ($pos3==0))
            {
            if ($debug) echo "Netzlaufwerk gefunden, suche erstes \\.\n";
            $pos4=strpos(substr($path,2),"\\");
            if ($pos4 != false)
                {
                $result["PathV"]=substr($path,$pos4+2);     /* die Backslashe am Anfange entfernen */
                $result["NetVolume"]=substr($path,2,$pos4);
                }
            }
        $pos5=strpos($path,":");        // erstes \\ für ein Netvolume
        if ($debug) echo "Laufwerk finden in $path, suche : auf $pos5 .\n";
        if (($pos5 !== false) && ($pos5==1))
            {
            if ($debug) echo "Laufwerk gefunden, suche erstes \\.\n";
            $pos6=strpos($path,"\\");
            if ($pos6 != false)
                {
                $result["PathV"]=substr($path,$pos6);     /* die Backslashe am Anfange entfernen */
                $result["Volume"]=substr($path,0,$pos6);
                }
            }
        if (isset($result["PathV"]))
            {
            /* wenn es einen Pfad gibt, das erste Directory ermitteln */
            $pos7=strpos(substr($result["PathV"],1),"\\");
            if ($pos7 != false)
                {
                $result["Path1"]=substr($result["PathV"],1,$pos7);     /* die Backslashe am Anfange entfernen */
                }            
            }

        $result["Directory"]=$directory;
        $result["Path"]=$path;
        $result["Filename"]=$filename;
        return($result);
        }
	
	}

/********************************************************************************************************
 *
 * LogFileHandler of OperationCenter
 * ================================= 
 *
 * extends OperationCenter because it is using same config file
 * works with the generated logfiles and backups them and deletes them after some time
 *
 *      DirLogs
 *      MoveFiles
 *      MoveCamFiles
 *      flattenYearMonthDayDirectory
 *
 **************************************************************************************************************************/

class LogFileHandler extends OperationCenter
	{

    function readxmllogfile($filename)
        {
        //=;
        $debug=false;
        $content = utf8_encode(file_get_contents($filename));
        //$content = utf8_encode(file_get_contents($Verzeichnis.$lognametoday));
        $xml = simplexml_load_string($content);
        if ($xml !== false)
            {
            echo "full read of $file successful\n";
            }
        else    
            {
            echo "full read of $file NOT successful\n";            
            $content = utf8_encode(file_get_contents($filename));
            $input = explode("\n", $content);
            $lines=sizeof($input);
            echo "Read $lines Lines of File $logname. Switch off Html filtern in log display.\n";
            //$startatline=$lines-500; 
            $startatline=0;
            $printonce=true; $oldentry="";
            If ($startatline<0) $startatline=0;
            foreach ($input as $line => $entry)
                {
                if ($line>$startatline) 
                    {
                    $newentry=$oldentry.$entry;
                    $len = strlen($newentry);
                    //echo str_pad($line,10).$entry;
                    if ($pos1=strpos($newentry,"<") !== 0) 
                        {
                        if ($debug)
                            {
                            if ($len>3) echo str_pad($line,10)."Warning, No Opening < in line : $entry\n";
                            else echo str_pad($line,10)."Warning, Empty line : $entry\n";
                            }
                        }
                    else    
                        {
                        $pos2=strrpos($newentry,">");
                        if (($len-$pos2)>2)
                            {
                            if ($debug) echo str_pad($line,10)."Multiline Pos < is 0, Len is $len, Pos > is $pos2  $entry \n";
                            $oldentry=$newentry."\n";
                            }
                        else 
                            {
                            $oldentry="";
                            //echo "\n";
                            $api = @simplexml_load_string($newentry);
                            if ($api !== false)
                                {
                                $read = xmlToArray($api);
                                if ($printonce) 
                                    {
                                    echo str_pad($line,10).$newentry."\n"; 
                                    print_r($read); $printonce=false; 
                                    }
                                if ($read["event"]["attributes"]["level"]=="ERROR")         // filter
                                    {
                                    echo str_pad($line,10).$read["event"]["attributes"]["timestamp"]."  ";
                                    echo $read["event"]["attributes"]["logger"]."  \n";
                                    echo " ".$read["event"]["message"]["value"]."\n";
                                    }
                                }
                            else 
                                {
                                echo "Array not decoded ***************\n";
                                $errors = libxml_get_errors();

                                foreach ($errors as $error) {
                                    echo display_xml_error($error, $newentry);
                                    }                
                                }
                            }
                        }
                    //print_r($api->@attributes);            
                    }
                }
            }


        }

    /* standard dir read of verzeichnis
     * wenn ext false dann ganzes dir
     * wenn xml oder log dann wird gesucht
     */

	function readLogDir($Verzeichnis=false, $ext=false, $debug=false)
        {
        if ($Verzeichnis===false) $Verzeichnis = IPS_GetKernelDir()."logs/";
        $today = date("Ymd");
        $yesterday = date("Ymd",time() - (1 * 24 * 60 * 60));
        $file=array();
        $handle=opendir ($Verzeichnis);
        $i=0;
        while ( false !== ($datei = readdir ($handle)) )
            {
            if ( ($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($Verzeichnis.$datei) == false) )  
                {
                $filename=explode(".",$datei);
                if ($filename[1] == $ext)
                    {
                    $filedate=explode("_",$filename[0]);
                    if (isset($filedate[1]))
                        {
                        if ($filedate[1] == $today) { $file["lognametoday"]=$datei; }
                        if ($filedate[1] == $yesterday) { $file["logname"]=$datei; }
                        if ($debug) echo " $ext file found : ".$filename[0].".".$filename[1]." with log date ".$filedate[1]."\n";
                        }                    
                    }
                $i++;
                if ($ext===false) $file[$i]=$datei;
                }
            }
        closedir($handle);            
        return ($file);
        }	
        																								
	/*
	 *  Die oft umfangreichen Files die erstellt werden in einem Ordner pro Tag zusammenfassen, damit leichter gelogged und gelöscht
	 *	 werden kann.
	 *
	 */

	function DirLogs($verzeichnis="")
		{
		if ($verzeichnis=="") $verzeichnis=IPS_GetKernelDir().'logs/';
		$verzeichnis = $this->dosOps->correctDirName($verzeichnis);			// sicherstellen das ein Slash oder Backslah am Ende ist

		echo "DirLogs: Verzeichnis der Logfiles von ".$verzeichnis.".\n";
		$print=0;
		$dir=0; $totalsize=0; $warning=false;

		// Test, ob ein Verzeichnis angegeben wurde
		if ( is_dir ( $verzeichnis ) )
			{
			//echo "Konfiguration:\n";
			//print_r($this->oc_Setup);
			$logdir=$this->readdirToArray($verzeichnis);	// es gibt zweiten Parameter für rekursiv wenn true
			//print_r($logdir); // zu gross
			foreach ($logdir as $index => $entry)
				{
				if (is_dir($verzeichnis.$entry)==true) 
					{
					echo "  ".$index."  Directory  ".$entry."\n";
					$entries=$this->readdirToArray($verzeichnis.$entry);
					$size=sizeof($entries);	
					$dir++;
					$totalsize+=$size;							
					}
				else
					{	
					if (($print++)<1000) 
						{
						echo "  ".$index."  Datei     ".$entry."\n"; // aufpassen damit nicht zu gross
						}
					else
						{	
						if ($warning==false)
							{
							echo "**** es sind mehr Dateien als 1000 vorhanden- Ausgabe gestoppt.\n";
							$warning=true;
							}
						}	
					$totalsize++;
					}	
				}
			echo "Insgesamt wurden ".$totalsize. " Dateien und Verzeichnisse gefunden.\n";	
			}
		else
			{
			echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
			}
		return($dir);		
		}

	/*
	 *  Die oft umfangreichen Log-Files oder Captured Pics bei Alarmierung in einem gemeinsamen Ordner pro Tag zusammenfassen, 
	 *  damit können die Dateien leichter gelogged und gelöscht werden.
	 *	 
	 *  Verzeichnis: Ort der Dateien, days: wieviele Tage zurück werden Dateien erst zusammengeräumt, StatusID : OID in der der Zeitstempel der letzten Datei gespeichert wird.
	 *  days geht immer auf den Tag um 00:00 zurück. Sonst werden wenn die Dateien regelmaessig über den Tag erstellt wurden, auch dauernd Dateien verschoben  
	 *
	 *  kann für CamFTP Files als auch für Logs verwendet werden.
	 */

	function MoveFiles($verzeichnis="",$days=2,$statusID=0, $debug=false)
		{
		if ($verzeichnis=="") $verzeichnis=IPS_GetKernelDir().'logs';
		$verzeichnis = $this->dosOps->correctDirName($verzeichnis);			// sicherstellen das ein Slash oder Backslash am Ende ist

		if ($debug) echo "       MoveFiles: Alle Files von ".$verzeichnis." in eigene Verzeichnisse pro Tag verschieben.\n";

			$count=100;
			//echo "<ol>";

            if ($days >= 0)
                {
                //echo "Heute      : ".date("Ymd", time())."\n";
                //echo "Gestern    : ".date("Ymd", strtotime("-1 day"))."\n";
                //echo "Vorgestern : ".date("Ymd", strtotime("-2 day"))."\n";
			    $vorgestern = date("Ymd", strtotime("-".$days." day"));
			    $moveTime=strtotime($vorgestern."000000");
                //echo " Dateien bis ".$vorgestern." nicht verschieben. ".date("YmdHis",$moveTime)."\n";
                //echo " Dateien bis ".$vorgestern." nicht verschieben.\n";
                }
            else $moveTime=time();

			// Test, ob ein Verzeichnis angegeben wurde
			if ( is_dir ( $verzeichnis ) )
				{
				// öffnen des Verzeichnisses
				if ( $handle = opendir($verzeichnis) )
					{
					/* einlesen der Verzeichnisses
					   nur count mal Eintraege
					*/
                    if ($debug) echo "        Verzeichnis $verzeichnis eingelesen. Es werden max $count Eintraege die jünger als ".date("D d.m.Y H:i:s",$moveTime)." sind abgearbeitet.\n";
					while ((($file = readdir($handle)) !== false) and ($count > 0))
						{
						if ( ($file != ".") && ($file != "..") )
							{
							$dateityp=filetype( $verzeichnis.$file );
							if ($dateityp == "file")
								{
								$filemTimeInt=filemtime($verzeichnis.$file);
								$filemTime=date("YmdHis",$filemTimeInt);
								$filecTimeInt=filectime($verzeichnis.$file);
								$filecTime=date("YmdHis",$filecTimeInt);
								$unterverzeichnis=date("Ymd", $filecTimeInt);
								$letztesfotodatumzeit=date("d.m.Y H:i", $filecTimeInt);   // anderes Format fuer Status
								//if ($debug) echo "                 Bearbeite Datei $verzeichnis$file : $filemTime modified, $filecTime created. Move files younger than ".date("YmdHis",$moveTime)."\n";
								if ($filecTimeInt <= $moveTime)
									{
									$count-=1;
									if (is_dir($verzeichnis.$unterverzeichnis))
										{
										}
									else
										{
                                        //echo "Mkdirtree von ".$verzeichnis.$unterverzeichnis."\n";
										$this->dosOps->mkdirtree($this->dosOps->correctDirName($verzeichnis.$unterverzeichnis));
										}
                                    //echo "rename ".$verzeichnis.$file."   ,    ".$verzeichnis.$unterverzeichnis."\\".$file."\n";
									rename($verzeichnis.$file,$verzeichnis.$unterverzeichnis."\\".$file);
									if ($debug) echo "                   Datei: ".$verzeichnis.$file." auf ".$verzeichnis.$unterverzeichnis."\\".$file." verschoben.\n";
									if ($statusID != 0) SetValue($statusID,$letztesfotodatumzeit);
									}
								}
							}	
						} /* Ende while */
					closedir($handle);
					} /* end if dir */
				}/* ende if isdir */
			else
				{
				echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
				}
		return (100-$count);
		}
	/*
	 *  Die oft umfangreichen Fotos der Webcams in einem Ordner pro Tag zusammenfassen, damit leichter gelogged und gelöscht
	 *	 werden kann. Es werden die selben Move Operationen wie bei Logs verwendet.
	 *
	 */

	function MoveCamFiles($cam_config, $debug=false)
		{
		$count=0;
		$cam_name=$cam_config['CAMNAME'];		
		$verzeichnis = $cam_config['FTPFOLDER'];
		$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$this->CategoryIdData);
		if ($cam_categoryId==false)
			{
			$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
			IPS_SetParent($cam_categoryId,$this->CategoryIdData);
			}
		$WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3);
		$WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
		$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0, '~Motion', null ); /* 0 Boolean 1 Integer 2 Float 3 String */

        $this->flattenYearMonthDayDirectory($verzeichnis);

        echo "MoveCamFiles: for $cam_name from $verzeichnis.\n";
		$count=$this->MoveFiles($verzeichnis,-1,$WebCam_LetzteBewegungID,$debug);      /* in letzteBewegungID wird das Datum/Zeit des letzten kopierten Fotos geschrieben */
		$PhotoCountID = CreateVariableByName($this->CategoryIdData, "Webcam_PhotoCount", 1);
		SetValue($PhotoCountID,GetValue($PhotoCountID)+$count);                   /* uebergeordneten Counter und Cam spezifischen Counter nachdrehen */
		SetValue($WebCam_PhotoCountID,GetValue($WebCam_PhotoCountID)+$count);
		if ($count>0)
			{
			SetValue($WebCam_MotionID,true);
			}
		else
			{
			SetValue($WebCam_MotionID,false);
			}
		if ($debug) echo "    Anzahl verschobener Fotos für ".$cam_name." : ".$count."\n";
		return ($count);
		}

    /* die Reolink Kameras haben eine neue nicht konfigurierbare Ablagestruktur nach Jahr/Monat/Tag
     * diese Struktur wieder zurück in eine flache Ablage zurück verwandeln
     *
     */

    public function flattenYearMonthDayDirectory($verzeichnis, $debug=false)
        {
        $jahr=date("Y", time());
        $jahrAlt=date("Y", strtotime("-30 day"));
        if ($debug) echo "Jahre  $jahr  $jahrAlt  überprüfen:\n";
        if (is_dir($verzeichnis.$jahr) || is_dir($verzeichnis.$jahrAlt))
            {
            /* das ganze Verzeichnis auslesen. Mit den Jahren zB 2019, 2020 und einigen neuen Verzeichnissen die aus der Verdichtung entstanden sind */
                
            $alldir=$this->dosOps->readdirToArray($verzeichnis,true, 0 , true);
            //$alldir=$this->dosOps->readdirToStat($verzeichnis,true);            // rekursive Verzeichnisse
            //echo "Überblick Kameraverzeichnis $cam_name:\n";
            if ($debug) print_r($alldir);
            foreach ($alldir as $nameYear => $dir) 
                {
                if ($debug) echo "    ----$nameYear  \n";
                if ( ($nameYear==$jahr) || ($nameYear==$jahrAlt) )
                    {
                    $newVerzeichnis=$verzeichnis.$nameYear;
                    foreach ($dir as $month => $subdir)
                        {
                        $newVerzeichnis=$verzeichnis.$nameYear.$month;
                        if ($debug) echo "    -------M:$month  \n";
                        foreach ($subdir as $day => $subsubdir)
                            {
                            $newVerzeichnis=$verzeichnis.$nameYear.$month.$day;
                            if ($debug) echo "    ---------D:$day  ($newVerzeichnis)\n";
                            foreach ($subsubdir as $index => $file)
                                {
                                if ($debug) echo "    -----------------$index   $file\n";
                                rename($verzeichnis."\\".$nameYear."\\".$month."\\".$day."\\".$file,$verzeichnis.$file);
                                //echo "    ------------$index  (".json_encode($YmDdir).")\n";
                                //foreach ($YmDdir as $key => $file) echo "    ------------------------$key   $file\n";
                                }
                            }
                        }    
                    }
                } 
            }
        }

	}   // ende class

/********************************************************************************************************
 *
 * SeleniumChromedriver of OperationCenter
 * ================================= 
 *
 * extends OperationCenter because it is using same config file
 * provides information of chromedriver versions provided by cloud services
 *
 *      __construct                             execDir und $execDirContent schreiben
 *      get_ExecDir                             $OperationCenterSetup["Cloud"]["CloudDirectory"].$OperationCenterSetup["Cloud"]["Executes"]
 *      update_ExecDirContent                   obiges Verzeichnis noch einmal laden
 *      getListAvailableChromeDriverVersion     execDir einlesen und nach chromedriver filtern
 *      getFilenameOfVersion
 *      getListDownloadableChromeDriverVersion
 *
 *
 *
 *
 **************************************************************************************************************************/

class SeleniumChromedriver extends OperationCenterConfig
	{

    var $cdVersions=array();                // Überblick über alle chromedriver versionen am Cloud Drive und deren größe um die version zu erkennen
    var $execDirContent=array();            // Inhalt des Verzeichnis mit den Chromedrivern
    var $execDir;                           // Name des Verzeichnis mit den Chromedrivern
    var $debug;
    protected $listDownloadableChromeDriverVersion;     // array mit allen verfügbaren chromedriver Versionen

	public function __construct($subnet='10.255.255.255',$debug=false)
		{
        if ($debug) echo "class SeleniumChromedriver, Construct Parent class OperationCenter.\n";
        $this->debug=$debug; 
        $this->ipsOps = new ipsOps();   
        $this->dosOps = new dosOps();  
        $this->sysOps = new sysOps(); 
        $this->oc_Setup = $this->setSetUp();          
        $OperationCenterSetup = $this->getSetup();                       // die Verzeichnisse
        $cloudDir=$this->dosOps->correctDirName($OperationCenterSetup["Cloud"]["CloudDirectory"]);
        $this->execDir=$this->dosOps->correctDirName($cloudDir.$OperationCenterSetup["Cloud"]["Executes"]);         // alle chromedriver für die interne Verteilung)
        if ($debug)
            {
            echo "Cloud Verzeichnis für IP Symcon ist hier          : $cloudDir \n";
            echo "Cloud Verzeichnis für IP Symcon Executes ist hier : ".$this->execDir." \n";
            $this->dosOps->writeDirStat($this->execDir);                    // Ausgabe eines Verzeichnis 
            }        
        $this->execDirContent = $this->dosOps->readdirToArray($this->execDir);                   // Inhalt Verzeichnis als Array
        }


    /* SeleniumChromedriver::getSetup
     * Setup Config abfragen
     */
    public function getSetup()
        {
        return ($this->oc_Setup);
        }

    /* get_ExecDir
     */
    public function get_ExecDir()
        {
        return($this->execDir);    
        }

    /* update_ExecDirContent
     * damit getListAvailableChromeDriverVersion arbeiten kann
     */
    public function update_ExecDirContent()
        {
        $this->execDirContent = $this->dosOps->readdirToArray($this->execDir);
        return($this->execDirContent);    
        }

    /* SeleniumChromedriver, erstellt anhand des gespeicherten Inhalt des execdir Verzeichnis eine sortierte Liste von Chromdrivern mit Name und Filegröße
     * die Filegröße kann zum bestimmen der Versionsnummer verwendet werden
     */
    function getListAvailableChromeDriverVersion()
        {
        $version=array();
        foreach ($this->execDirContent as $fileName)
            {
            //$posNumeric=strpos()
            $match=array();
            $status=preg_match("/[0-9]{1,4}/", $fileName, $match);      // regex encoding [0-9] eine Ziffer, /^[0-9]{1,4}+\$/
            if ( ($status) && (count($match)==1) ) 
                {
                $matchFound=number_format($match[0]);
                if (strpos($fileName,"chromedriver")===0)
                    {
                    //echo "Gefunden in $fileName : $matchFound\n";
                    $version[$matchFound]["Name"]=$fileName;
                    $version[$matchFound]["Size"]=filesize($this->execDir.$fileName);
                    }
                }
            }
        ksort($version);
        //print_r($version);
        $this->cdVersion=$version;
        return ($version);
        }

    /* SeleniumChromedriver, get sourcefile with dedicated version from cloud directory
     */
    function getFilenameOfVersion($version=false,$debug=false)
        {
        if ($debug===false) $debug=$this->debug;
        if ($version===false) 
            {
            $version=array_key_last($this->cdVersion);
            if ($debug) echo "getFilenameOfVersion, update with latest Chromedriver version \"$version\".\n";
            }
        $version=(string)$version;
        $filenameChromeDriver = "chromedriver_".$version.".exe";
        $foundNew = $this->dosOps->findfiles($this->execDirContent,$filenameChromeDriver);
        if ($foundNew)
            {
            if ($debug) echo "Check if missing and then move ".$foundNew[0]." to Selenium Directory.\n";
            $sourceFile=$this->execDir.$foundNew[0];   
            }
        else $sourceFile=false;
        return ($sourceFile);                
        }

    /* SeleniumChromedriver::getListDownloadableChromeDriverVersion
     * get actual chromedriver versions with actual revision from google download page
     *
     */
    function getListDownloadableChromeDriverVersion($debug=false)
        {
        $url = "https://googlechromelabs.github.io/chrome-for-testing/known-good-versions-with-downloads.json";
        $type="chromedriver";           // oder chrome
        $platform="win64";              // oder win32 oder linux64
        if ($debug) echo "getListDownloadableChromeDriverVersion \n";
        $versions = file_get_contents($url);
        //echo $versions;
        $versionData=array();
        $versionData=json_decode($versions,true);
        //print_R($versionData);                        // Rodaten, alle Versionen

        /* original versions daten durchsuchen. Sie beginnt mit timestamp als scalar und versions als array
         *
         */
        $debug=false;
        $result=array(); // nach Versionsnummer indexiert
        foreach ($versionData as $intro => $fulldata)
            {
            if (is_array($fulldata)) 
                {
                $count=sizeof($fulldata);
                //echo " $intro   ($count)\n";              // intro versions
                foreach ($fulldata as $index => $data)      // index von 0 bis n
                    {
                    if (is_array($data)) 
                        {
                        $count=sizeof($data);    
                        $version=explode(".",$data["version"]);                                             // version index 0..3, 0 is major version
                        if ($debug) echo " ".str_pad($index,3)."   ($count)   ".str_pad($data["version"],20)."  ".str_pad(json_encode($version),30);
                        if (isset(($data["downloads"][$type])))
                            {
                            if ($debug) echo "$type\n";
                            foreach ($data["downloads"][$type] as $indexSub => $dataSub)
                                {
                                if ( (isset($dataSub["platform"])) && ($dataSub["platform"]==$platform) )
                                    {
                                    $result[$version[0]]["version"]=$data["version"];
                                    $result[$version[0]]["url"]=$dataSub["url"];
                                    if ($version[3]>0) 
                                        {
                                        $url = $dataSub["url"];                                 // defaultwert ist immer die letzte freigegebene Version
                                        $downloadVersion=$data["version"];
                                        }
                                    } 
                                }           // ende foreach
                            }                   // ende isset choromedriver
                        elseif ($debug)  echo "chrome only, $type not available\n";
                        }                   // ende is_array
                    else echo " $index   $data  Fehler ? \n";                 // Scalar Ausgabe 
                    }                   // ende foreach
                }                   // ende is_array
            elseif ($debug)  echo " $intro   $fulldata \n";                 // timestamp Ausgabe
            }
        return($result);
        }

    /* SeleniumChromedriver::getUpdateNewVersions
     * create a table with versions, find new versions, reduce size of table of versions
     *
     */
    public function getUpdateNewVersions(&$config,&$html,$actualVersion=false,$debug=false)
        {
        if ($debug) echo "getUpdateNewVersions : akuell $actualVersion \n";
        if (is_array($this->listDownloadableChromeDriverVersion)) $result = $this->listDownloadableChromeDriverVersion;
        else $result = $this->getListDownloadableChromeDriverVersion($debug);                  //   get from Web

        $configAdd=array();
        $configNew=array();             // die neu Konfiguration auf Basis von Old
        $html .= '<table>';

        foreach ($result as $version => $entry)         // alle Chromedriver Versionen als Tabelle ausgeben, Spalte Versionsnummer, alte Versionsbezeichnung, neue Versionsbezeichnung
            {
            if ($version >= ($actualVersion+1))
                {
                if ($debug) echo "   Version $version bearbeiten : \n";
                $html .= '<tr><td>'.$version.'</td>';
                //print_R($entry);
                $configNew[$version]["version"]=$entry["version"];      // aus $config wird configNew
                if (isset($config[$version]["version"])) 
                    {
                    if ($entry["version"] !== $config[$version]["version"])
                        {
                        $configAdd[$version]["version"]=$entry["version"];
                        //echo "Wir beginnen mit Version $version und neuer Revision ".$config[$version]["version"]." -> ".$entry["version"]."\n";
                        }    
                    else 
                        {
                        //echo "Die Version $version und Revision ".$entry["version"]." wurde bereits geladen\n";
                        }
                    $html .= '<td>'.$config[$version]["version"].'</td><td>'.$entry["version"].'</td>';
                    }
                else            // ganz neue Version
                    {
                    $configAdd[$version]["version"]=$entry["version"];
                    //echo "Wir beginnen mit neuer Version $version und Revision ".$config[$version]["version"]."\n";
                    $html .= '<td>n.a.</td><td>'.$entry["version"].'</td>';
                    }
                $html .= '</tr>';
                }
            //else $html .= '<td>'.$entry["version"].'</td><td>n.a.</td>';
            }
        $html .= '</table>';
        $config=$configNew;
        return ($configAdd);
        }
    }

/********************************************************************************************************
 *
 * HomematicOperation of OperationCenter
 *
 * extends OperationCenter because it is using same config file
 * provides information of homematic devices
 *
 *      get_CCUDevices
 *      ccuSocketStatus         CCU status availability
 *      ccuSocketDutyCycle
 *      sysStatusSockets
 *
 *
 **************************************************************************************************************************/

class HomematicOperation extends OperationCenter
	{

    protected $componentHandling;           // module

	public function __construct($subnet='10.255.255.255',$debug=false)
		{
        if ($debug) echo "class HomematicOperation, Construct Parent class OperationCenter.\n";
        $this->debug=$debug; 
        $this->componentHandling = new ComponentHandling();
  
        parent::__construct($subnet,$debug);                       // sonst sind die Config Variablen noch nicht eingelesen
        }

    /* HomematicOperation::get_CCUDevices
     */
    public function get_CCUDevices()
        {
        $this->CCUDevices=$this->componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_CCU","REGISTER" => "DUTY_CYCLE_LEVEL"],"Install",false);                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
        return($this->CCUDevices);    
        }

    /* HomematicOperation::ccuSocketStatus 
     * called every hour
     * creates a html table with connection status and last changed of each CCU Socket, additional status and renboot ctr is introduced
     */
    public function ccuSocketStatus($log_OperationCenter=false, $debug=false)
        {
        if ($log_OperationCenter===false) return(false);
        echo "ccuSocketStatus Hour passed: ".(parent::$hourPassed?"Yes":"No")." Four Hours passed : ".(parent::$fourHourPassed?"Yes":"No")."\n";
        $socketHtml="";
        $categoryId_Sockets = @IPS_GetObjectIDByName("SocketStatus",$this->categoryId_SysPing);
        if ($categoryId_Sockets && (parent::$hourPassed || $debug))
            {
            $variableSocketCCUHtmlId=IPS_GetObjectIdByName("StatusCCUConnected",$categoryId_Sockets );

            $socketHtml = $this->sysStatusSockets($log_OperationCenter, $debug);
            SetValue($variableSocketCCUHtmlId,$socketHtml);
            }
        return ($socketHtml);
        }

    /* HomematicOperation::ccuSocketDutyCycle 
     * needs more memory
     */
    public function ccuSocketDutyCycle($log_OperationCenter=false, $debug=false)
        {
        if ($log_OperationCenter===false) return(false);
        echo "ccuSocketStatus Hour passed: ".(parent::$hourPassed?"Yes":"No")." Four Hours passed : ".(parent::$fourHourPassed?"Yes":"No")."\n";
        $archiveOps = new archiveOps();
        $archiveHandlerID=$archiveOps->getArchiveID();
        $html="";
        $categoryId_Sockets = @IPS_GetObjectIDByName("SocketStatus",$this->categoryId_SysPing);
        if ($categoryId_Sockets && (parent::$hourPassed || $debug))
            {
            $variableSocketCCUHtmlId=IPS_GetObjectIdByName("StatusCCUConnected",$categoryId_Sockets );
            $variableSocketCCUDutyCycleHtmlId    = @IPS_GetObjectIDByName("StatusCCUDutyCycle",$categoryId_Sockets);

            $description=array();
            $config = array();
            //$config["StartTime"]="1.10.2023";
            $config["StartTime"]=time()-72*60*60;       // 72 Stunden zurück

            $configCleanUpData = array();
            $configCleanUpData["deleteSourceOnError"]=false;
            $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
            $config["CleanUpData"] = $configCleanUpData; 
            $config["ShowTable"]["align"]="hourly";                  
            
            $result=$this->get_CCUDevices();
            foreach ($result as $name => $entry)
                {
                $oid = $entry["COID"];
                $this->componentHandling->setLogging($oid);          // eigentlich Teil des Installs

                echo str_pad($name,45)."$oid   ".GetValueFormatted($oid)."     ".IPS_GetParent($oid)."\n";
                echo $archiveOps->getStatus($oid)."\n";
                $config["OIdtoStore"]=$name;
                $ergebnis = $archiveOps->getValues($oid,$config,false);          // true Debug
                $description[$oid] = $ergebnis["Description"];
                //foreach ($ergebnis["Description"] as $index=>$eintrag) echo "$index    \n";
                echo "done.\n";
                }

            $result = $archiveOps->showValues(false,$config,true);                   // true Debug
            

            //print_R($result);
            //foreach ($result as $index=>$entry) echo "$index    \n";

            //print_R($description);
            $html  = "";
            $html .= '<table>'; 
            //echo "MaxMin DutyCycle der letzten 72 Stunden: \n";
            $html .= '<tr><th colspan=5>MaxMin DutyCycle der letzten 72 Stunden, act ist '.date("D H:i:s").':</th></tr>';
            $html .= '<tr><th>OID</th><th>Name</th><th>Act</th><th>Max</th><th>Max Date</th><th>Min</th><th>Min Date</th></tr>';
            foreach ($description as $oid => $entry)
                {
                $maxmin = $entry["MaxMin"];
                //echo "   $oid  Max ".str_pad($maxmin["Max"]["Value"],12)." (".date("D H:i:s",$maxmin["Max"]["TimeStamp"]).") Min ".str_pad($maxmin["Min"]["Value"],12)." (".date("D H:i:s",$maxmin["Min"]["TimeStamp"]).")\n";
                $html .= '<tr><td>'.$oid.'</td><td>'.IPS_GetName(IPS_GetParent($oid)).'</td><td>'.GetValue($oid).'</td><td>'.$maxmin["Max"]["Value"].'</td><td>'.date("D H:i:s",$maxmin["Max"]["TimeStamp"]).'</td><td>'.$maxmin["Min"]["Value"].'</td><td>'.date("D H:i:s",$maxmin["Min"]["TimeStamp"]).'</td></tr>';
                }
            $html .= '</table>';

            SetValue($variableSocketCCUDutyCycleHtmlId,$html);
            }
        return ($html);
        }


    /* HomematicOperation::sysStatusSockets
     * Routinen von Syspingalldevices übersichtlicher machen, hier Sockets Homematic analysieren und ein html machen
     */
    public function sysStatusSockets($log_OperationCenter, $debug=false)
        {
        if ($debug) echo "   sysStatusSockets aufgerufen, alle installierten Discovery Instances mit zugehörigem Modul und Library:\n";
        $socketHtml="";
        $categoryId_Sockets = @IPS_GetObjectIDByName("SocketStatus",$this->categoryId_SysPing);
        if ($categoryId_Sockets)
            {
            $modulhandling = new ModuleHandling();

            $discovery = $modulhandling->getDiscovery();
            $topologyLibrary = new TopologyLibraryManagement();                     // in EvaluateHardware Library, neue Form des Topology Managements
            $socket=array();
            $socket = $topologyLibrary->get_SocketList($discovery);
            //print_r($socket);
            $socketHtml .= "<table>";
            foreach ($socket as $modul => $module) 
                {
                switch ($modul)
                    {
                    case "Homematic":
                        $socketHtml .=  '<tr><th>'.$modul." CCU Status".'</th><th>OID</th><th>Open</th><th>Status</th><th>last changed</th></tr>';
                        // Homematic CCU I/O Sockets, die gehen auf fail=false wenn es Probleme gibt
                        foreach ($module as $name => $entry) 
                            {
                            // verwendet $variableId, $instanceStatusId, $instanceNumStatusId, $StatusID, $RebootID
                            $SocketOpen=IPS_GetProperty($entry["OID"], "Open");         // eigentlich die falsche Information
                            $SocketStatus=($SocketOpen?"open":"closed");
                            $variableId=CreateVariableByName($categoryId_Sockets,$name."_"."Connected",3);      // als String, leichter lesbar
                            SetValue($variableId,$SocketStatus);
                            // instance Status
                            $instanceStatusId=CreateVariableByName($categoryId_Sockets,$name."_"."InstanceStatus",3);      // als String, leichter lesbar
                            $instanceNumStatusId=CreateVariableByName($categoryId_Sockets,$name."_"."InstanceStatusNum",1);      // als String, leichter lesbar
                            $config=json_decode(IPS_GetConfigurationForm($entry["OID"]),true);
                            $status=IPS_GetInstance($entry["OID"])["InstanceStatus"];
                            switch ($status)    
                                {
                                case 102:
                                    $statusinfo="Instanz ist aktiv";
                                    break;
                                case 101: 
                                    $statusinfo="Instanz wird erstellt";
                                    break;
                                case 103:
                                    $statusinfo="Instanz wird gelöscht";
                                    break;
                                case 104:
                                    $statusinfo="Instanz ist inaktiv";
                                    break;
                                case 105:
                                    $statusinfo="Instanz wurde nicht erstellt";
                                    break;
                                case 106:
                                    $statusinfo="Instanz ist im Standby";
                                    break;
                                default:                    // irgendetwas größer gleich 200
                                    $statusinfo="Instanz ist fehlerhaft, $status";
                                    break;
                                }
                            SetValue($instanceStatusId,$statusinfo);
                            // htmlinfo
                            $oldstatus=GetValue($instanceNumStatusId);
                            $changed = date("H:i:s d.m.Y",IPS_GetVariable($instanceNumStatusId)["VariableChanged"]);
                            $socketHtml .=  '<tr><td>'.$name.'</td><td>'.$entry["OID"].'</td><td>';
                            $socketHtml .=  $SocketStatus.'</td><td>'.$statusinfo.'</td><td>'.$changed.'</td></tr>'; //Ist die I/O Instanz aktiv?

                            SetValue($instanceNumStatusId,$status);
                            // logging und rebootCtr
                            $StatusID = @IPS_GetObjectIDByName($name."_"."Connected",$this->categoryId_SysPing);
                            $RebootID = @IPS_GetObjectIDByName($name."_"."Connected",$this->categoryId_RebootCtr);
                            if ($StatusID===false) 
                                {
                                $StatusID = CreateVariableByName($this->categoryId_SysPing,   $name."_"."Connected", 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
                                SetValue($StatusID,true);           // default value
                                }
                            if ($RebootID===false) 
                                {
                                $RebootID = CreateVariableByName($this->categoryId_RebootCtr, $name."_"."Connected", 1); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
                                SetValue($RebootID,0);
                                }
                            if (AC_GetLoggingStatus($this->archiveHandlerID,$StatusID) === false)           // stündliche Eintraege, Open als Instance parameter
                                { // nachtraeglich Loggingstatus setzen
                                AC_SetLoggingStatus($this->archiveHandlerID,$StatusID,true);
                                AC_SetAggregationType($this->archiveHandlerID,$StatusID,0);
                                IPS_ApplyChanges($this->archiveHandlerID);
                                }
                            if (AC_GetLoggingStatus($this->archiveHandlerID,$instanceNumStatusId) === false)           // stündliche Eintraege, Status/Error als Instance parameter
                                { // nachtraeglich Loggingstatus setzen
                                AC_SetLoggingStatus($this->archiveHandlerID,$instanceNumStatusId,true);
                                AC_SetAggregationType($this->archiveHandlerID,$instanceNumStatusId,0);
                                IPS_ApplyChanges($this->archiveHandlerID);
                                }                                
                            if ($status<200)            // true alles gut
                                {
                                if ($oldstatus != $status)      // Status Change
                                    {
                                    $log_OperationCenter->LogMessage('CCU Socket Statusaenderung von $oldstatus auf $instanceNumStatusId ');
                                    }
                                SetValue($StatusID,true);
                                SetValue($RebootID,0);
                                if ($debug) echo "      ".str_pad($name,30)."  Status ".(GetValue($StatusID)?"Active":"Failure").". Status Number : $status \n";                           

                                }
                            else                        // nicht gut, state machine, reboot ctr increase, by 60 Minutes
                                {
                                if ($oldstatus != $status)      // aha Fehler ist neu
                                    {
                                    $log_OperationCenter->LogMessage('CCU Socket Statusaenderung von $oldstatus auf Fehler $instanceNumStatusId ');                                    
                                    }
                                SetValue($RebootID,(GetValue($RebootID)+60));
                                SetValue($StatusID,false);
                                if ($debug) echo "      ".str_pad($name,30)."  Status ".(GetValue($StatusID)?"Active":"Failure")."  since ".GetValue($RebootID)."  Minutes. Failure Number : $status \n";                            }
                                }
                        break;
                    }
                }
            $socketHtml .= "</table>";
            $socketHtml .= "last updated on ".date("d.m.y H:i:s");
            }
        return ($socketHtml);
        }



    }

/********************************************************************************************************
 *
 * PingOperation of OperationCenter
 *
 * extends OperationCenter because it is using same config file
 * provides information of Sysping devices
 *
 *      SysPingAllDevices
 *      syspingCams
 *      writeSysPingStatistics
 *      writeSysPingActivity
 *
 * sys device ping IP Adresse von LED Modul oder DENON Receiver
 * Wenn device_ping zu oft fehlerhaft ist wird das Gerät rebootet, erfordert einen vorgelagerten Schalter und eine entsprechende Programmierung
 * Erreichbarkeit der Remote Server zum Loggen
 *
 *   device_ping  
 *   server_ping
 *   writeServerPingResults
 *
 *
 **************************************************************************************************************************/

class PingOperation extends OperationCenter
	{

	public function __construct($subnet='10.255.255.255',$debug=false)
		{
        if ($debug) echo "class HomematicOperation, Construct Parent class OperationCenter.\n";
        $this->debug=$debug;   
        parent::__construct($subnet,$debug);                       // sonst sind die Config Variablen noch nicht eingelesen
        }

	/* PingOperation::SysPingAllDevices
	 *
	 * Pingen von IP Geräten, Timer wird alle 5 Minuten aufgerufen, aber im Normalfall nur alle 60 Minuten ausgeführt
     *
     * Die Geräte sind aufgelistet im Konfigurationsfile:
	 *
	 * CAM config file, ping mit sysping
	 *
	 * LED config File, ping mit device_ping
	 *
	 * Denon
	 *
	 * Router, ping mit device_ping (echtes sys_ping aus IP Symcon Funktionsliste)
	 *				OperationCenterConfig['ROUTER']
	 *
	 * Wunderground
	 *
	 * localAccess Server, wie für die Remote Abfrage, den lokalen Wert setzen
	 *
	 * RemoteAccess Server, rpc call ping mit function server_ping
	 *	Aufruf von function server_ping()
	 *				RemoteAccess_Configuration.inc.php : RemoteAccess_GetServerConfig() wenn set to Active
	 *				
	 *
	 *
	 *
	 * in Data/OperationCenter/Sysping/ pro Gerät ein Status angelegt
	 * in Data/OperationCenter/RebootCounter/ pro Gerät ein Reboot Request Counter angelegt
	 *
	 ********************************************************************************************************************/

	function SysPingAllDevices($log_OperationCenter=false, $debug=false)
		{
        if ($log_OperationCenter===false) return(false);
		echo "Sysping All Devices. Subnet : ".$this->subnet."\n";

		$OperationCenterConfig = $this->oc_Configuration;
		//print_r($OperationCenterConfig);
        if (isset($_IPS['EVENT'])) $ipsEvent="TimerEvent from :".$_IPS['EVENT']." ";
        else $ipsEvent="";

        $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$this->categoryId_SysPing);
        $categoryId_Sockets        = @IPS_GetObjectIdByName("SocketStatus",$categoryId_SysPing);

        $SysPingTableID = @IPS_GetObjectIDByName("SysPingTable",$categoryId_SysPingControl);
        $SysPingActivityTableID = @IPS_GetObjectIDByName("SysPingActivityTable",$categoryId_SysPingControl); 
        $SysPingResult=array();

		/************************************************************************************
		 * Erreichbarkeit IPCams
		 *************************************************************************************/
		 
		if ( (isset ($this->installedModules["IPSCam"])) && parent::$hourPassed )
			{
            $this->syspingCams($OperationCenterConfig['CAM'],$log_OperationCenter, $debug);    
			}

		/************************************************************************************
		 * Erreichbarkeit LED Ansteuerungs WLAN Geräte
		 *************************************************************************************/
		if ( (isset ($this->installedModules["LedAnsteuerung"])) && parent::$hourPassed )
			{
			//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
            IPSUtils_Include ("LedAnsteuerung_Configuration.inc.php","IPSLibrary::config::modules::LedAnsteuerung");
			$device_config=LedAnsteuerung_Config();
			$device="LED"; $identifier="IPADR"; /* IP Adresse im Config Feld */
			$SysPingResult += $this->device_ping($device_config, $device, $identifier, parent::$hourPassed, $debug);                  // debug setzt weiter oben auch hourspassed
			$this->device_checkReboot($OperationCenterConfig['LED'], $device, $identifier, $debug);
			}

		/************************************************************************************
		 * Erreichbarkeit Denon Receiver
		 *************************************************************************************/
		if ( (isset ($this->installedModules["DENONsteuerung"])) && parent::$hourPassed )
			{
			//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
            IPSUtils_Include ("DENONsteuerung_Configuration.inc.php","IPSLibrary::config::modules::DENONsteuerung");            
			$device_config=Denon_Configuration();
			$deviceConfig=array();
			foreach ($device_config as $name => $config)
				{
				if ( $name != "Netplayer" ) { $deviceConfig[$name]=$config; }
				if ( isset ($config["TYPE"]) ) { if ( strtoupper($config["TYPE"]) == "DENON" ) $deviceConfig[$name]=$config; }
				}
			$device="DENON"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
			$SysPingResult += $this->device_ping($deviceConfig, $device, $identifier, parent::$hourPassed, $debug);
			$this->device_checkReboot($OperationCenterConfig['DENON'], $device, $identifier, $debug);
			}

        if (isset($OperationCenterConfig['INTERNET'])) 
            {
            /************************************************************************************
            * Erreichbarkeit Internet, alle 5 Minuten aufrufen, Routine ignoriert selbst wenn nur jede Stunde notwendig
            *************************************************************************************/
            $device="Internet"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
            $SysPingResult += $this->device_ping($OperationCenterConfig['INTERNET'], $device, $identifier, parent::$hourPassed, $debug);
            $this->device_checkReboot($OperationCenterConfig['INTERNET'], $device, $identifier, $debug);
            }

        if ( parent::$hourPassed )
            {
            /************************************************************************************
            * Erreichbarkeit Router
            *************************************************************************************/
            $device="Router"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
            $SysPingResult += $this->device_ping($OperationCenterConfig['ROUTER'], $device, $identifier, parent::$hourPassed, $debug);
            $this->device_checkReboot($OperationCenterConfig['ROUTER'], $device, $identifier, $debug);
            
            /********************************************************
                Sys Uptime lokaler Server ermitteln
            **********************************************************/

            echo "\nSind die LocalAccess Server erreichbar ....\n";

            $Access_categoryId=@IPS_GetObjectIDByName("AccessServer",$this->CategoryIdData);
            if ($Access_categoryId==false)
                {
                $Access_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                IPS_SetName($Access_categoryId, "AccessServer"); // Kategorie benennen
                IPS_SetParent($Access_categoryId,$this->CategoryIdData);
                }
            $IPS_UpTimeID = CreateVariableByName($Access_categoryId, IPS_GetName(0)."_IPS_UpTime", 1);
            IPS_SetVariableCustomProfile($IPS_UpTimeID,"~UnixTimestamp");
            SetValue($IPS_UpTimeID,IPS_GetKernelStartTime());
            echo "   Server : ".IPS_GetName(0)." zuletzt rebootet am: ".date("d.m H:i:s",GetValue($IPS_UpTimeID)).".\n";

            }

		/************************************************************************************
		 * Überprüfen ob Wunderground noch funktioniert.
		 *************************************************************************************/
		if ( (isset ($this->installedModules["IPSWeatherForcastAT"])) && parent::$hourPassed )
			{
			echo "\nWunderground API überprüfen.\n";
			IPSUtils_Include ("IPSWeatherForcastAT_Constants.inc.php",     "IPSLibrary::app::modules::Weather::IPSWeatherForcastAT");
			IPSUtils_Include ("IPSWeatherForcastAT_Configuration.inc.php", "IPSLibrary::config::modules::Weather::IPSWeatherForcastAT");
			IPSUtils_Include ("IPSWeatherForcastAT_Utils.inc.php",         "IPSLibrary::app::modules::Weather::IPSWeatherForcastAT");
			$urlWunderground      = 'http://api.wunderground.com/api/'.IPSWEATHERFAT_WUNDERGROUND_KEY.'/forecast/lang:DL/q/'.IPSWEATHERFAT_WUNDERGROUND_COUNTRY.'/'.IPSWEATHERFAT_WUNDERGROUND_TOWN.'.xml';
			IPSLogger_Trc(__file__, 'Load Weather Data from Wunderground, URL='.$urlWunderground);
			$urlContent = @Sys_GetURLContent($urlWunderground);
			$ServerStatusID = CreateVariableByName($this->categoryId_SysPing, "Server_Wunderground", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			if ($urlContent===false)
				{
				echo "Wunderground Key ist defekt oder überlastet.\n";
				if (GetValue($ServerStatusID)==true)
					{  /* Statusänderung */
					$log_OperationCenter->LogMessage('SysPing Statusaenderung von Server_Wunderground auf NICHT erreichbar');
					$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Server_Wunderground auf NICHT erreichbar');
					SetValue($ServerStatusID,false);
					}
				}
			else
				{
				echo "  -> APP ist okay !.\n";
				if (GetValue($ServerStatusID)==false)
					{  /* Statusänderung */
					$log_OperationCenter->LogMessage('SysPing Statusaenderung von Server_Wunderground auf Erreichbar');
					$log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Server_Wunderground auf Erreichbar');
					SetValue($ServerStatusID,true);
					}
				}
			$api = @simplexml_load_string($urlContent);
			//print_r($api);
			}

		/********************************************************
		Die entfernten logserver auf Erreichbarkeit prüfen
		**********************************************************/

		if ( (isset ($this->installedModules["RemoteAccess"])) )            // alle 5 Minuten überprüfen um die Anzahl der Fehlermeldungen zu reduzieren
			{
			echo "\nSind die RemoteAccess Server erreichbar ....\n";
			$result=$this->server_ping();
			}
        $this->writeSysPingStatistics($this->categoryId_SysPing,$debug);
        
        if (parent::$fourHourPassed)
            {
            echo "\n\n";
            echo "Zusammenfassung SyspingAllDevices als Activity Table:\n";
            $actual=false;
            $html=$this->writeSysPingActivity($actual, true, $debug);
            echo $this->writeSysPingActivity($actual, false, false);
            SetValue($SysPingActivityTableID,$html);                
            }            
        return($SysPingResult);
		}

    /* Routinen von Syspingalldevices übersichtlicher machen
     */
    public function syspingCams($config, $log_OperationCenter, $debug=false)
        {
        $SysPingResult=array();
        if ($debug) echo "syspingCams aufgerufen, Sys_ping Kamera :\n";
        $mactable=$this->get_macipTable($this->subnet);            
        foreach ($config as $cam_name => $cam_config)
            {
            if ( (isset($cam_config['IPADRESSE']) ) || (isset($cam_config['MAC'])) )
                {
                if (! ( (isset($cam_config["STATUS"])) && (strtoupper($cam_config["STATUS"])!="ENABLED") ) )            
                    {
                    // echo "IPADRESSE oder MAC Adresse sollte bekannt sein.\n";
                    $CamStatusID = CreateVariableByName($this->categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
                    $ipadresse=""; $macadresse="";
                    if (isset($cam_config['IPADRESSE'])) $ipadresse=$cam_config['IPADRESSE'];
                    if (isset($mactable[$cam_config['MAC']])) 
                        {
                        $macadresse=$cam_config['MAC'];
                        if ($ipadresse == "") $ipadresse=$mactable[$macadresse];
                        elseif ($ipadresse != $mactable[$macadresse]) echo "Mactable check, kenn mich nicht aus, zwei unterschiedliche IP Adressen.\n";
                        }
                    if ($ipadresse != "")
                        {    
                        if ($debug) echo str_pad("    ".$cam_name." mit MAC Adresse ".$cam_config['MAC']." und IP Adresse $ipadresse",110);
                        if ($macadresse != "") echo " vs ".$mactable[$cam_config['MAC']]."    ";
                        else echo "                  ";
                        $status=Sys_Ping($ipadresse,1000);
                        $SysPingResult[IPS_getName($CamStatusID)]=$status;
                        if ($status)
                            {
                            echo "--->  Kamera wird erreicht.\n";
                            if (GetValue($CamStatusID)==false)
                                {  /* Statusänderung */
                                $log_OperationCenter->LogMessage('SysPing Statusaenderung von Cam_'.$cam_name.' auf Erreichbar');
                                $log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Cam_'.$cam_name.' auf Erreichbar');
                                SetValue($CamStatusID,true);
                                }
                            }
                        else
                            {
                            echo "---> Kamera wird NICHT erreicht   !\n";
                            if (GetValue($CamStatusID)==true)
                                {  /* Statusänderung */
                                $log_OperationCenter->LogMessage('SysPing Statusaenderung von Cam_'.$cam_name.' auf NICHT Erreichbar');
                                $log_OperationCenter->LogNachrichten('SysPing Statusaenderung von Cam_'.$cam_name.' auf NICHT Erreichbar');
                                SetValue($CamStatusID,false);
                                }
                            }
                        }
                    else  /* mac adresse nicht bekannt */
                        {
                        echo "    ".$cam_name." mit IP oder Mac Adresse ".$cam_config['MAC']." nicht bekannt, Sys_Ping nur auf lokale IP Adressen möglich.\n";
                        }
                    }   
                else echo "    ".$cam_name." wurde deaktiviert, nicht abfragen.\n";
                }   // nur die Einträge in der Konfiguration, bei denen es auch eine MAC Adresse gibt bearbeiten
            else 
                {
                echo "     $cam_name, IP Adresse nicht verfügbar: ".json_encode($cam_config)."\n";
                }
            } /* Ende foreach */
        if ($debug) echo "Kameras wurden abgefragt.\n";
        return($SysPingResult);
        }


	/*PingOperation:: writeSysPingStatistics() 
     *
     * als html Tabelle die Ergebnisse über die Erreichbarkeit von Geräten ausgeben
	 *
	 * es werden die Dateneintraege analysiert und ausgegeben
	 *   Erreichbarkeit IPCams
	 *	 writeServerPingResults()
     *   etc.
     *
	 *
	 *
	 **************************************************************************************************************/

	function writeSysPingStatistics($sysPingCat=0,$debug=false)
		{
        if ($debug) echo "writeSysPingStatistics aufgerufen für Darstellung der Erreichbarkeit der IP fähigen Geräte:\n";
        if ($sysPingCat==0) $categoryId_SysPing=$this->categoryId_SysPing;        
        else $categoryId_SysPing=$sysPingCat;
        $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$categoryId_SysPing);
        $SysPingTableID            = @IPS_GetObjectIDByName("SysPingTable",$categoryId_SysPingControl);
        $SysPingSortTableID        = @IPS_GetObjectIDByName("SortPingTable",$categoryId_SysPingControl);

		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
        //print_r($remServer);

        if ($SysPingTableID !== false)
            {
            $ipsOps = new ipsOps();
            $sysPing=array();

            $PrintHtml="";
            $PrintHtml.='<style>'; 
            $PrintHtml.='.sturdy table,td {align:center;border:1px solid white;border-collapse:collapse;}';
            $PrintHtml.='.sturdy table    {table-layout: fixed; width: 100%; }';
            $PrintHtml.='.sturdy td:nth-child(1) { width: 70%; }';
            $PrintHtml.='.sturdy td:nth-child(2) { width: 10%; }';
            $PrintHtml.='.sturdy td:nth-child(3) { width: 20%; }';
            $PrintHtml.='</style>';        
            $PrintHtml.='<table class="sturdy"><tr><td>Name</td><td>State</td><td>Since</td></tr>';

            $childrens = IPS_GetChildrenIds($categoryId_SysPing);           // alle Status Variablen
            if ($debug) echo "alle ".sizeof($childrens)." Variablen in der Kategorie $categoryId_SysPing ausgeben:\n";
            foreach ($childrens as $children)
                {
                $varname=IPS_GetName($children);
                switch ($varname)
                    {
                    case "SysPingTable":                                // ignore known category entries that are not relevant for statistics
                    case "SysPingActivityTable":
                    case "SysPingExectime":
                    case "SysPingCount":
                    case "SysPingControl":                
                    case "SocketStatus":
                        break;    
                    default:
                        $serverName=substr($varname,strlen("Server_"));
                        if ( (isset($remServer[$serverName])) || ($sysPingCat !=0) )             // nur konfigurierte Variablen anzeigen
                            {
                            //echo $serverName."  ";
                            if ($debug) 
                                {
                                echo "   ".str_pad($varname,40)."    ";
                                if (isset($result[$varname])) echo "*";
                                else echo " ";
                                }
                            $timeChanged=IPS_GetVariable($children)["VariableChanged"];
                            $time=date("d.m.Y H:i:s",$timeChanged);
                            $timeDelay=(time()-IPS_GetVariable($children)["VariableChanged"]);
                            if ($debug) echo "     ".str_pad((GetValue($children)?"Ja":"Nein"),10)."$time  ".str_pad(nf($timeDelay,"s"),12);
                            if ($timeChanged==0) 
                                {
                                if ($debug) echo "   --> delete\n";
                                IPS_DeleteVariable($children);
                                } 
                            else 
                                {
                                if ($debug) echo "    \n";
                                $sysPing[$varname]["VarName"]=$varname;
                                $sysPing[$varname]["Status"]=GetValue($children);
                                $sysPing[$varname]["Delay"]=$timeDelay;
                                //$PrintHtml.='<tr><td>'.$varname.'</td><td>'.(GetValue($children)?"Ja":"Nein")."</td><td>".nf($timeDelay,"s")."</td></tr>";    
                                }
                            }
                        break;
                    }
                }

            if ($SysPingSortTableID !== false)
                {
                switch (GetValueIfFormatted($SysPingSortTableID))
                    {
                    case "Name":
                        $sortTable="VarName";
                        break;
                    case "State":
                        $sortTable="Status";
                        break;
                    case "Since":
                        $sortTable="Delay";
                        break;
                    default:
                        echo "not known.";
                        $sortTable="VarName";                        
                        break;
                    }
                }
            else $sortTable="VarName";
            //echo "Lookup Sort $SysPingSortTableID : ".GetValueIfFormatted($SysPingSortTableID)." => $sortTable   ";
            $ipsOps->intelliSort($sysPing,$sortTable);
            //$ipsOps->intelliSort($sysPing,"Delay");
            foreach ($sysPing as $entry)
                {
                $PrintHtml.='<tr><td>'.$entry["VarName"].'</td><td>'.($entry["Status"]?"Ja":"Nein")."</td><td>".nf($entry["Delay"],"s")."</td></tr>";    
                }
            $PrintHtml.='<tr><td align="right" colspan="3"><font size="-1">last update on '.date("d.m.Y H:i:s").'</font></td></tr>';    
            $PrintHtml.='</table>';    
            SetValue($SysPingTableID,$PrintHtml);
            return($PrintHtml);
            }
        else return (false);
        }

	/*************************************************************************************************************
	 *
	 * writeSysPingActivity() , in Textform die Ergebnisse über die Erreichbarkeit von Geräten ausgeben
	 *
	 * es werden die Dateneintraege analysiert und ausgegeben
	 *   Erreichbarkeit IPCams
	 *	 writeServerPingResults()
     *
     * Parameter actual wird nur bei den IPSCams verwendet
	 *
	 *
	 **************************************************************************************************************/

	function writeSysPingActivity($actual=true, $html=false, $debug=false)
		{
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */

		$result=""; 
        //$eDebug=$debug;
        $eDebug=false;

		$OperationCenterConfig = $this->oc_Configuration;
		//print_r($OperationCenterConfig);
		
        //$SysPingTableID = @IPS_GetObjectIDByName("SysPingTable",$this->categoryId_SysPing);
        //$SysPingActivityTableID = @IPS_GetObjectIDByName("SysPingActivityTable",$this->categoryId_SysPing);        

		/************************************************************************************
		 * Erreichbarkeit IPCams
		 *************************************************************************************/
		$result .= "Erreichbarkeit der IPCams:\n\n";
		 
		if (isset ($this->installedModules["IPSCam"]))
			{
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
                $CamStatusID = @IPS_GetObjectIDByName( "Cam_".$cam_name,$this->categoryId_SysPing);
				if ($CamStatusID==false) $CamStatusID = CreateVariableByName($this->categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
				if ( GetValue($CamStatusID)==true )
					{
					$result .= str_pad($cam_name,30)."erreichbar\n";
					}
				else
					{
					$result .= str_pad($cam_name,30)."abwesend\n";
					}
				if ( (AC_GetLoggingStatus($this->archiveHandlerID,$CamStatusID)) && ($actual==false) )
		   			{
					/* schauen ob sich etwas in der Vergangenheit getan hat */
					//echo "Loggingstatus aktiv.\n";
        			$werte = AC_GetLoggedValues($this->archiveHandlerID,$CamStatusID, time()-30*24*60*60, time(),1000);
					//print_r($werte);
					}
				} /* Ende foreach */
			}

		$result .= "\nAusfallsstatistik der konfigurierten Geraete:\n\n";			
		$childrens=$this->getLoggedValues($this->categoryId_SysPing);
		if ($debug) echo "Sysping Statusdaten liegen in der Kategorie SysPing unter der OID: ".$this->categoryId_SysPing." \n";
		$result1=array();
		foreach($childrens as $oid)
			{
            $type=IPS_GetObject($oid)["ObjectType"];
			if ( ($type==2) && (AC_GetLoggingStatus($this->archiveHandlerID,$oid)) )         // sollten schon ausgefiltert sein, sicherheitshalber
		   		{
        		$werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000); 
		   		//print_r($werte);
				$status=getValue($oid); $first=true; $timeok=time(); 
                $max=0; $count=0;   // keine Ausgabe der gespeicherte Log EIntraege wenn $max kleiner gleich 1 ist
                $offTime=0; $onTime=0;
                $maxend=time()-(30*24*60*60);
                $size=sizeof($werte);
                $serverName=substr(IPS_GetName($oid),strlen("Server_"));
                //echo "$serverName     \n";
                if ($size==0)
                    {
                    $lastWert=$status;
                    $lastTime=time();
                    }
                //else              // nutzt nix, keine Änderungen kann auch dauernd erreichbar bedeuten
                if (isset($remServer[$serverName]))
                    {
    		   		if ($debug) echo "$serverName   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen (bis ".date("d.m. H:i:s",$maxend).") $size Werte. Aktueller Status Available : ".($status? "Ein" : "Aus")."\n";
                    foreach ($werte as $wert)
                        {
                        if ($count++ < $max) print_r($wert);            // Debugausgabe von Werten zur Orientierung
                        $dauer=$this->Dauer($timeok,$wert["TimeStamp"],$maxend);
                        if ($status!==$wert["Value"])
                            {
                            if ($first==true)
                                {
                                echo "             ******************* Aenderung erster Eintrag im Logging zum aktuellem Status - Passen nicht zusammen..\n";
                                If ($wert["Value"]==true) $onTime += $dauer; 
                                If ($wert["Value"]==false) $offTime += $dauer;
                                if ( $debug && $eDebug)
                                    {
                                    echo "       !! Wert im Logging noch nicht aktualisiert.\n";
                                    If ($wert["Value"]==true) echo "         Zuletzt wiederhergestellt am ".date("d.m H:i:s",$wert["TimeStamp"])." ontime ".$this->MinOrHoursOrDays($onTime)." offtime ".$this->MinOrHoursOrDays($offTime)."   ";
                                    If ($wert["Value"]==false) echo "        Zuletzt ausgefallen am ".date("d.m H:i:s",$wert["TimeStamp"])." ontime ".$this->MinOrHoursOrDays($onTime)." offtime ".$this->MinOrHoursOrDays($offTime)."   ";
                                    echo " Unverändert seit ".((time()-$wert["TimeStamp"])/60)." Minuten.\n";
                                    }
                                $first=false;
                                }
                            else
                                {
                                /* hier ist die Routine eigentlich immer */
                                If ($wert["Value"]==true) 
                                    {
                                    $onTime += $dauer;
                                    if ($debug && $eDebug) echo "        Wiederhergestellt am ".date("d.m H:i:s",$wert["TimeStamp"])." Dauer online ".number_format($dauer,2)." Minuten. ontime ".$this->MinOrHoursOrDays($onTime)." offtime ".$this->MinOrHoursOrDays($offTime)."   \n";
                                    }
                                If ($wert["Value"]==false) 
                                    {
                                    $offTime += $dauer;
                                    if ($debug && $eDebug) echo "      Ausgefallen am ".date("d.m H:i:s",$wert["TimeStamp"])." Dauer offline ".number_format($dauer,2)." Minuten.  ontime ".$this->MinOrHoursOrDays($onTime)." offtime ".$this->MinOrHoursOrDays($offTime)."   \n";
                                    if ($dauer>100)	$result .= "          Ausfall länger als 100 Minuten am ".date("D d.m H:i:s",$wert["TimeStamp"])." fuer ".number_format($dauer,2)." Minuten.\n";
                                    }
                                //echo "  Check : ".$this->MinOrHoursOrDays($onTime+$offTime)."  und  ".$this->MinOrHoursOrDays((time()-$wert["TimeStamp"])/60)."   \n";
                                }	
                            $status=$wert["Value"];
                            }
                        else
                            {
                            if ($first==true)
                                {
                                if ($debug) echo "            *********************** keine Aenderung, erster Eintrag im Logfile, so sollte es sein.\n";
                                $dauer=$this->Dauer(time(),$wert["TimeStamp"],$maxend);
                                If ($wert["Value"]==true) 
                                    {
                                    $onTime += $dauer;                                    
                                    if ($debug && $eDebug) echo "       Zuletzt wiederhergestellt am ".date("d.m H:i:s",$wert["TimeStamp"])." ontime ".$this->MinOrHoursOrDays($onTime)." offtime ".$this->MinOrHoursOrDays($offTime)."   ";
                                    $result .= IPS_GetName($oid).": Verbindung zuletzt wiederhergestellt am ".date("D d.m H:i:s",$wert["TimeStamp"])."\n";
                                    }
                                If ($wert["Value"]==false) 
                                    {
                                    $offTime += $dauer;
                                    if ($debug && $eDebug) echo "       Zuletzt ausgefallen am ".date("d.m H:i:s",$wert["TimeStamp"])." ontime ".$this->MinOrHoursOrDays($onTime)." offtime ".$this->MinOrHoursOrDays($offTime)."   ";
                                    }
                                if ($debug && $eDebug) echo " Unverändert seit $dauer Minuten.\n";
                                $first=false;
                                }
                            else        // zweimal der selbe Wert hintereinander
                                {
                                If ($wert["Value"]==false) $offTime += $dauer;  
                                If ($wert["Value"]==true) $onTime += $dauer;                                  
                                //echo "      *************** hier sollte niemand vorbeikommen, sonst fehlen die Zeiten.\n";
                                }
                            }	
                        $timeok=$wert["TimeStamp"];
                        if ($debug>1) echo "       Wert : ".str_pad(($wert["Value"] ? "Ein" : "Aus"),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."\n";
                        //echo "       Wert : ".str_pad(($wert["Value"] ? "Ein" : "Aus"),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
                        $lastTime=$wert["TimeStamp"]; $lastWert=$wert["Value"];
                        }
                    $dauer=$this->Dauer($lastTime,$maxend,$maxend);
                    if ($debug) echo "Dauer ".$this->MinOrHoursOrDays($dauer)."  Check ".(($onTime+$offTime+$dauer)/60/24)."\n";
                    If ($lastWert==true) $onTime += $dauer; 
                    If ($lastWert==false) $offTime += $dauer;   
                    // Ergebnis schreiben             
                    $result1[IPS_GetName($oid)]["OID"]=$oid;
                    $available=round((1-($offTime/($onTime+$offTime)))*100,1);
                    if ($debug) echo "   -> Gesamtauswertung ".IPS_GetName($oid)." ontime $onTime offtime $offTime Availability $available %.  Check ".(($onTime+$offTime)/60/24)."\n";
                    $result1[IPS_GetName($oid)]["AVAILABILITY"]=$available;
                    $result1[IPS_GetName($oid)]["ONTIME"]=$this->MinOrHoursOrDays($onTime);
                    $result1[IPS_GetName($oid)]["OFFTIME"]=$this->MinOrHoursOrDays($offTime);
                    if ($debug) echo "   -> Gesamtauswertung ".IPS_GetName($oid)." ontime ".$result1[IPS_GetName($oid)]["ONTIME"]." offtime ".$result1[IPS_GetName($oid)]["OFFTIME"]." Availability ".$available."%.\n";
		   	   		}
		   		}
			else
		    	{
				echo "   ".IPS_GetName($oid)." Variable wird NICHT gelogged.\n";
		    	}
			}
		//print_r($result1);	//Ergebnis für html Tabelle
							
		$result .= "\n";

		/************************************************************************************
		 * Erreichbarkeit Remote Access Server, für die html Auswertung nicht Berücksichtigen
		 *************************************************************************************/
		
		if ($html)
            {
            ksort($result1);
            $PrintHtml="";
            $PrintHtml.='<style>';             
            $PrintHtml.='.staty table,td {align:center;border:1px solid white;border-collapse:collapse;}';
            $PrintHtml.='.staty table    {table-layout: fixed; width: 100%; }';
            $PrintHtml.='.staty td:nth-child(1) { width: 60%; }';
            $PrintHtml.='.staty td:nth-child(2) { width: 15%; }';
            $PrintHtml.='.staty td:nth-child(3) { width: 15%; }';
            $PrintHtml.='.staty td:nth-child(4) { width: 10%; }';
            $PrintHtml.='</style>';        
            $PrintHtml.='<table class="staty">';
            $PrintHtml.='<tr><td>Gerät</td><td>Ontime</td><td>Offtime</td><td>Availability</td></tr>';
            foreach ($result1 as $name => $entry)
                {
                $PrintHtml.='<tr><td>'.$name.'</td><td>'.$entry["ONTIME"].'</td><td>'.$entry["OFFTIME"].'</td><td>'.$entry["AVAILABILITY"].'%</td></tr>';
                }
            $PrintHtml.='<tr><td align="right" colspan="4"><font size="-1">last update on '.date("d.m.Y H:i:s").'</font></td></tr>';    
            $PrintHtml.='</table>';
    		return($PrintHtml);
            }
        else
            {
            if (isset ($this->installedModules["RemoteAccess"]))
                {		
                $result .= $this->writeServerPingResults();
                }
    		return($result);
            }
		}


    }

/********************************************************************************************************
 *
 * CamOperation of OperationCenter
 *
 * extends OperationCenter because it is using same config file
 * provides information of Sysping devices
 *
 *      
 *      
 *      
 *      
 *
 *
 **************************************************************************************************************************/

class CamOperation extends OperationCenter
	{

	public function __construct($module="IPSCam",$subnet='10.255.255.255',$debug=false)
		{
        if ($debug) echo "class CamOperation, Construct Parent class OperationCenter.\n";
        $this->debug=$debug;   
        parent::__construct($subnet,$debug);                       // sonst sind die Config Variablen noch nicht eingelesen
        
        if (($module=="IPSCam") && (isset($this->installedModules["IPSCam"])))
            {
            /* wird zB von copyCamSnapshot verwendet */
    		$repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
	    	$this->moduleManagerCam = new IPSModuleManager("IPSCam",$repositoryIPS);            
            }
        elseif (isset($this->installedModules[$module]))
            {
            /* wird zB von copyCamSnapshot verwendet */
            $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	    	$this->moduleManagerCam = new IPSModuleManager($module,$repository);            
            }            
        else $this->moduleManagerCam=false;
        }

    /*  CamOperation::selectFilesfromList
     *
     * aus einer Liste nach dem Mode ein paar Dateien aussuchen
     * picdir Fileliste die kopiert werden soll, hier für die Auswahl relevant
     *
     ********************************************/

    private function selectFilesfromList($picdir, $verzeichnis,$mode="standard", $debug=false)
        {
        $sysOps = new sysOps();
        if ($mode=="") $mode="standard";        // default mode
        $span=120;                              // Zeitspanne innerhalb der ein Bild ausgesucht wird
        /* picdir Fileliste die kopiert werden soll, vorhandene Dateien werden nicht kopiert, andere Dateien werden gelöscht */
        $size=sizeof($picdir);      // input ist picdir array, output ist logdir, parameter sind verzeichnis
        if ($debug) echo "selectFilesfromList, Liste mit $size Dateien übergeben. mode $mode.\n";

        /* picdir sortiern, entweder nach den Dateinamen, oder dem tatsächlichen File Create Datum */
        $datesort=array();
        foreach ($picdir as $pic) $datesort[filemtime($verzeichnis."\\".$pic)]=$pic;
        krsort($datesort); 
        //foreach ($datesort as $time => $pic) echo "    ".date("H:i:s",$time)."   $pic\n";

        $j=0;$k=0;$l=0; $timeWindow=0;
        $logdir=array();  // logdir loeschen, sonst werden die Filenamen vom letzten Mal mitgenommen
        foreach ($datesort as $time => $pic)
            {
            $path_parts = pathinfo($pic);
            if ($path_parts['extension']=="jpg")
                {
                if ($debug) echo "   ".str_pad($verzeichnis."\\".$pic,50)."   ".str_pad($sysOps->getNiceFileSize(filesize($verzeichnis."\\".$pic)),16)."    ".
                                    date("H:i:s",$time);
                if ($timeWindow==0) 
                    {
                    $timeWindow=$time;      // initialisieren, erstes Foto immer anzeigen
                    if ($debug) echo "  + init";  
                    $oldpic=$pic; 
                    $logdir[$j]=$pic;
                    $j++;
                    }
                elseif ($time<($timeWindow-$span))             // es geht rückwärts, 60 Sekundenfenster vorsehen, ausserhalb
                    {
                    if ($l<3) 
                        {
                        if ($debug) echo "  +";
                        if ($mode=="time")
                            {
                            $logdir[$j]=$oldpic;
                            $j++;
                            } 
                        }
                    else
                        {
                        if ($debug) echo "  \\";
                        }   
                    $timeWindow=$time;
                    $l=0;
                    }
                else                                            // im 60 Sekundenfenster
                    {
                    $l++;
                    if ($l==3)  
                        {
                        if ($debug) echo "  +"; 
                        if ($mode=="time")
                            {
                            $logdir[$j]=$pic;
                            $j++;
                            } 
                        }
                    else 
                        {
                        if ($debug) echo "  |";
                        }
                    }
                //echo "       Dirname: ".$path_parts['dirname'], "\n";
                //echo "       Basename: ".$path_parts['basename'], "\n";
                //echo "       Extension: ".$path_parts['extension'], "\n";
                //echo "       Filename: ".$path_parts['filename'], "\n"; // seit PHP 5.2.0			
                if (($k % 6)==2) 
                    { 
                    if ($mode=="standard")
                        {
                        $logdir[$j]=$pic;
                        $j++;
                        } 
                    if ($debug) echo "  *"; 
                    };
                $k++;		// eigener Index, da manche Files übersprungen werden
                if ($debug) echo "\n";
                $oldpic=$pic;           // manchmal wird das vorige Bild benötigt
                }       // if jpeg
            //if ($debug) echo "\n";
            }    // foreach
        echo "Im Quellverzeichnis ".$verzeichnis." sind insgesamt ".$size." Dateien :\n";
        echo "Es wird nur jeweils aus sechs jpg Dateien die dritte genommen.\n"; 	

        return($logdir);	
        }


	/* CamOperation::copyCamSnapshots
	 *
	 * es werden Snapshots pro Kamera erstellt, muss in IPSCam eingeschaltet sein
	 * diese werden wenn aufgerufen (regelmaessig) für ein Overview Webfront in das webfront/user Verzeichnis kopiert
     *  IPS_KernelDir/Cams/0-x als Quellverzeichnis 
     *  IPS_KernelDir/webfront/user/OperationCenter/AllPics/ als Zielverzeichnis
	 * 
	 * Übergabeparameter ist IPSCam Configfile aus IPSCam
	 */
	
	function copyCamSnapshots($camConfig=array(), $debug=false)
		{
        if ($debug) echo "copyCamSnapshots aufgerufen.\n";
        $status=false;  // Rückmeldecode
        if (isset($this->installedModules["IPSCam"]))
            {
            if (sizeof($camConfig)==0)
                {
                if ($debug) echo "Kein Configarray als Übergabeparameter, sich selbst eines überlegen.\n";
                IPSUtils_Include ("IPSCam_Constants.inc.php",      "IPSLibrary::app::modules::IPSCam");
                IPSUtils_Include ("IPSCam_Configuration.inc.php",  "IPSLibrary::config::modules::IPSCam");
                $camConfig = IPSCam_GetConfiguration();			
                }
            $categoryIdCams     		= CreateCategory('Cams',    $this->CategoryIdData, 20);
        
            $camVerzeichnis=IPS_GetKernelDir()."Cams/";
            $camVerzeichnis = str_replace('\\','/',$camVerzeichnis);

            /* Zielverzeichnis ermitteln */
            $picVerzeichnis="user/OperationCenter/AllPics/";
            if ($this->newstyle) $picVerzeichnisFull=IPS_GetKernelDir().$picVerzeichnis;            // ab IPS7 kein webfront/user Verzeichnis mehr
            else $picVerzeichnisFull=IPS_GetKernelDir()."webfront/".$picVerzeichnis;
            $picVerzeichnisFull = str_replace('\\','/',$picVerzeichnisFull);
            if ($debug) 
                {
                echo "copyCamSnapshots: Aufruf mit folgender Konfiguration:\n";
                print_r($camConfig);
                echo "Bilderverzeichnis der Kamera Standbilder: Quellverzeichnis ".$camVerzeichnis."   Zielverzeichnis ".$picVerzeichnisFull."\n";
                echo "---------------------------------------------------------\n";                
                }
            if ( is_dir ( $picVerzeichnisFull ) == false ) $this->dosOps->mkdirtree($picVerzeichnisFull);

            foreach ($camConfig as $index=>$data) 
                {
                $PictureTitleID=CreateVariable("CamPictureTitle".$index,3, $categoryIdCams,100,"",null,null,"");        // string
                $PictureTimeID =CreateVariable("CamPictureTime".$index,1, $categoryIdCams,101,"",null,null,"");         // integer, time
                $filename=$camVerzeichnis.$index."/Picture/Current.jpg";
                //if ($debug) echo "       Kamera ".$data["Name"]." bearbeite Filename $filename   ".date ("F d Y H:i:s.", filemtime($filename))."\n";
                if ( file_exists($filename) == true )
                    {
                    $filemtime=filemtime($filename);
                    if ($debug) echo "      Kamera ".$data["Name"]." :  copy ".$filename." nach ".$picVerzeichnisFull." File Datum vom ".date ("F d Y H:i:s.", $filemtime)."\n";	
                    copy($filename,$picVerzeichnisFull."Cam".$index.".jpg");
                    SetValue($PictureTitleID,$data["Name"]."   ".date ("F d Y H:i:s.", $filemtime));
                    SetValue($PictureTimeID,$filemtime);
                    }
                }
            $status=true;			
            }
        if ($debug) echo"\n";
        return ($status);
		}

	/* CamOperation::showCamSnapshots
	 *
	 * es wird die Visualisierung für die Snapshots pro Kamera erstellt, muss in IPSCam eingeschaltet sein
     *
     *
	 */
	function showCamSnapshots($camConfig=array(), $debug=false)
		{
        if ($debug) echo "showCamSnapshots aufgerufen für insgesamt ".sizeof($camConfig)." Kameras.\n";            
        $status=false;  // Rückmeldecode
        if (sizeof($camConfig)==0)
            {
            if (isset($this->installedModules["IPSCam"]))
                {
                IPSUtils_Include ("IPSCam_Constants.inc.php",      "IPSLibrary::app::modules::IPSCam");
                IPSUtils_Include ("IPSCam_Configuration.inc.php",  "IPSLibrary::config::modules::IPSCam");
                $camConfig = IPSCam_GetConfiguration();			
                //if ($debug) echo "Kein Configarray als Übergabeparameter, sich selbst eines überlegen: ".json_encode($camConfig)."\n";
                }
            }
        if ($debug) echo "IPSCam installiert. showCamSnapshots aufgerufen mit ".json_encode($camConfig).".\n";            
        $categoryIdCams     		= CreateCategory('Cams',    $this->CategoryIdData, 20);
    
        /* Zielverzeichnis für Anzeige ermitteln */
        $picVerzeichnis="user/OperationCenter/AllPics/";
        if ($this->newstyle) $picVerzeichnisFull=IPS_GetKernelDir().$picVerzeichnis;            // ab IPS7 kein webfront/user Verzeichnis mehr
        else $picVerzeichnisFull=IPS_GetKernelDir()."webfront/".$picVerzeichnis;
        $picVerzeichnisFull = str_replace('\\','/',$picVerzeichnisFull);

        $anzahl=sizeof($camConfig);
        $rows=(integer)(($anzahl/2)+1);
        if ($debug) 
            {
            $ipsOps = new ipsOps();                    
            echo "Es werden im Picture Overview insgesamt ".$anzahl." Bilder in ".$rows." Zeilen mal 2 Spalten aus $categoryIdCams (".$ipsOps->path($categoryIdCams).") angezeigt.\n";	
            //print_r($camConfig);                 // das sind die Cams die durchgegangen werden
            }	
        $CamTablePictureID=IPS_GetObjectIDbyName("CamTablePicture",$categoryIdCams);
        $CamMobilePictureID=IPS_GetObjectIDbyName("CamMobilePicture",$categoryIdCams);

        $html="";
        $html.='<style> 
                    table {width:100%}
                    table,td {align:center;border:1px solid white;border-collapse:collapse;}
                    .bildmittext {border: 5px solid darkslategrey; position: relative;}
                    .bildmittext img {display:block;}
                    .bildmittext span {background-color: darkslategrey; position: absolute;bottom: 0;width:100%;line-height: 2em;text-align: center;}
                    .bildmittext spanRed {background-color:darkred; position: absolute;bottom: 0;width:100%;line-height: 2em;text-align: center;}
                    </style>';

        $htmlWeb='<table>';
        $count=0; 
        if ($anzahl>6) $columns=3;
        else $columns=2;
        foreach ($camConfig as $index=>$data) 
            {
            If ( ($count % $columns) == 0) $htmlWeb.="<tr>";
            // CreateVariableByName($parentID, $name, $type, $profile="", $ident="", $position=0, $action=0)  sicherheitshalber einmal erzeugen
            $PictureTitleID = CreateVariableByName($categoryIdCams,"CamPictureTitle".$index, 3, "", "", 100, null);        // string
            $PictureTimeID  = CreateVariableByName($categoryIdCams,"CamPictureTime".$index, 1, "", "", 101, null);         // integer, time
            //$PictureTitleID = IPS_GetObjectIDByName("CamPictureTitle".$index,$categoryIdCams);        // string
            //$PictureTimeID  = IPS_GetObjectIDByName("CamPictureTime".$index,$categoryIdCams);         // integer, time
            $text      = GetValue($PictureTitleID);
            $filemtime = GetValue($PictureTimeID);
            /* Parameter imgsrcstring($imgVerzeichnis,$filename,$title,$text="",$span="span") */
            if ((time()-$filemtime)>60) $htmlWeb.='<td frameborder="1"> '.$this->imgsrcstring($picVerzeichnis,"Cam".$count.".jpg","Cam".$count.".jpg",$text,"spanRed").' </td>'; 			
            else $htmlWeb.='<td frameborder="1"> '.$this->imgsrcstring($picVerzeichnis,"Cam".$count.".jpg","Cam".$count.".jpg",$text).' </td>';
            If ( ($count % $columns) == ($columns-1) ) $htmlWeb.="</tr>";
            $count++;
            }
        If ( ($count % $columns) == 0) $htmlWeb.="<td> </td> </tr>";
        $htmlWeb.="<tr><td colspan=\"".$columns."\">Aktualisierte Snapshots direkt von der Cam, zuletzt aktualisiert ".date("d.m.Y H:i:s")."</td></tr>";
        $htmlWeb.="</table>";	

        $htmlMob='<table>';
        $count=0; $columns=1;
        foreach ($camConfig as $index=>$data) 
            {
            If ( ($count % $columns) == 0) $htmlMob.="<tr>";
            $PictureTitleID = IPS_GetObjectIDByName("CamPictureTitle".$index,$categoryIdCams);        // string
            $PictureTimeID  = IPS_GetObjectIDByName("CamPictureTime".$index,$categoryIdCams);         // integer, time
            $text      = GetValue($PictureTitleID);
            $filemtime = GetValue($PictureTimeID);
            /* Parameter imgsrcstring($imgVerzeichnis,$filename,$title,$text="",$span="span") */
            if ((time()-$filemtime)>60) $htmlMob.='<td frameborder="1"> '.$this->imgsrcstring($picVerzeichnis,"Cam".$count.".jpg","Cam".$count.".jpg",$text,"spanRed").' </td>'; 			
            else $htmlMob.='<td frameborder="1"> '.$this->imgsrcstring($picVerzeichnis,"Cam".$count.".jpg","Cam".$count.".jpg",$text).' </td>';
            If ( ($count % 2) == 1) $htmlMob.="</tr>";
            $count++;
            }
        If ( ($count % $columns) == 0) $htmlMob.="<td> </td> </tr>";
        $htmlMob.="</table>";

        SetValue($CamTablePictureID,$html.$htmlWeb);
        SetValue($CamMobilePictureID,$html.$htmlMob);

        $status=true;	

        return ($status);
		}


	/* CamOperation::showCamCaptureFiles
	 *
	 * es werden von den ftp Verzeichnissen ausgewählte Dateien in das Webfront/user verzeichnis für die Darstellung im Webfront kopiert
     *  Zielverzeichnis:  IPS_KernelDir/webfront/user/OperationCenter/Cams/"Cam_name"/
	 * 
	 */
	
	function showCamCaptureFiles($ocCamConfig,$debug=false)
		{
        $box=false;
        if ($this->moduleManagerCam)            // IPSCam oder eigenes Module wie WebCamera
            {
            $categoryId_WebFrontAdministrator=$this->getWebfrontCategoryID();           // erzeugt eine Kategorie im Webfront Visualisiserung
            $childrens = IPS_GetChildrenIDs($categoryId_WebFrontAdministrator);
            $camsFound=array();
            if ($debug) echo "Look for Webfront categories in ".IPS_GetName($categoryId_WebFrontAdministrator)."/".IPS_GetName(IPS_GetParent($categoryId_WebFrontAdministrator))."\n";
            foreach ($childrens as $children)
                {
                $camsFound[$children]=true;
                if ($debug) echo "   $children  ".IPS_GetName($children)."\n";
                }
            //if ($debug) print_r($childrens);         // alle Unterobjekte, Cameras ausgeben, nicht mehr verwendete muessen gelöscht werden

            $count=0; $index=0;		
            foreach ($ocCamConfig as $indexName => $cam_config)
                {
                if (isset($cam_config['NAME'])) $cam_name=$cam_config['NAME'];
                else $cam_name=$indexName;          //webcam (mit Index) oder operationcenter formatierung mit Name als index
                if (isset ($cam_config['FTPFOLDER']))         
                    {  
                    if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                        {                        
                        $index++;
                        if ($debug)
                            {
                            echo "\n  ---------------------------------------------------------\n";
                            echo "  Webfront Tabname für ".$cam_name." erstellen.\n";
                            }
                        $heute=date("Ymd", time());
                        //echo "    Heute      : ".$heute."    Gestern    : ".date("Ymd", strtotime("-1 day"))."\n";

                        /* in data/OperationCenter/ jeweils pro Camera eine Kategorie mit Cam_Name anlegen 
                        * sollte bereits vorhanden sein, hier werden die Infos über letzte Bewegung und Anzahl Capture Bilder gesammelt
                        *
                        */
                        $cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$this->CategoryIdData);
                        if ($cam_categoryId==false)
                            {
                            $cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
                            IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
                            IPS_SetParent($cam_categoryId,$this->CategoryIdData);
                            }

                        /* im Webfront visualization/adminstrator/ eine IPSCam_Capture Kategorie mit jeweils pro Kamera eigener Kategorie anlegen 
                        * dort wird die Variable für die html box gespeichert
                        *  ändern auf Link, damit später auch link von user geht
                        */	
                        $categoryIdCapture  = CreateCategory("Cam_".$cam_name,  $categoryId_WebFrontAdministrator, 10*$index);
                        if (isset($camsFound[$categoryIdCapture])) 
                            {
                            echo "  Camera Cam_$cam_name ($categoryIdCapture) in ".IPS_GetName($categoryId_WebFrontAdministrator)." ($categoryId_WebFrontAdministrator) found.\n";
                            unset ($camsFound[$categoryIdCapture]);
                            IPS_SetHidden($categoryIdCapture, false);
                            }
                    
                        /* hmtl box in Vizualization anlegen statt in data und einen Link darauf setzen $pictureFieldID = CreateVariable("pictureField",   3 ,  $categoryIdCapture, 50 , '~HTMLBox'); */
                        $pictureFieldID = $this->createVariablePictureField($categoryIdCapture);
                        /*$html.='<style> 
                                table {width:100%}
                                td {width:50%}						
                                table,td {align:center;border:1px solid white;border-collapse:collapse;}
                                .bildmittext {border: 5px solid red;position: relative;}
                                .bildmittext img {display:block;}
                                .bildmittext span {background-color: red;position: absolute;bottom: 0;width:100%;line-height: 2em;text-align: center;}
                                </style>';
                        $html.='<table>'; */
                    
                        $box="";
                        $box.='<style> 
                                table {width:100%}
                                td {width:20%}						
                                table,td {align:center;border:1px solid white;border-collapse:collapse;}
                                .bildmittext {border: 5px solid green;position: relative;}
                                .bildmittext img {display:block;}
                                .bildmittext span {background-color: grey;position: absolute;bottom: 0;width:100%;line-height: 2em;text-align: center;}
                                </style>';
                        $box.='<table>';		
                        /* $box.='<style> .container { position: relative; text-align: center; color: white; } 
                        .bottom-left { position: absolute; bottom: 8px; left: 16px; } 
                        .top-left {position: absolute; top: 8px; left: 16px; } 
                        .top-right { position: absolute; top: 8px; right: 16px; } 
                        .bottom-right { position: absolute; bottom: 8px; right: 16px; } 
                        .centered { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); } </style>';
                        $box.='<table frameborder="1" width="100%">'; */

                        $verzeichnis=$cam_config['FTPFOLDER'].$heute;
                    
                        /* Kamerabilderverzeichnis muss innerhalb Webfront entstehen, daher Bilder dorthin kopieren */
                        $imgVerzeichnis="user/OperationCenter/Cams/".$cam_name."/";
                        if ($this->newstyle) $imgVerzeichnisFull=IPS_GetKernelDir().$imgVerzeichnis;            // ab IPS7 kein webfront/user Verzeichnis mehr
                        else $imgVerzeichnisFull=IPS_GetKernelDir()."webfront/".$imgVerzeichnis;
                        $imgVerzeichnisFull = str_replace('\\','/',$imgVerzeichnisFull);
                        if ( is_dir ( $imgVerzeichnisFull ) == false ) $this->dosOps->mkdirtree($imgVerzeichnisFull);
                    
                        $picdir=$this->dosOps->readdirToArray($verzeichnis,false,-500);
                        if ($debug) 
                            {
                            echo "   Files aus dem Verzeichnis $verzeichnis werden in das Zielverzeichnis : ".$imgVerzeichnisFull." kopiert.\n";
                            }
                        if ($picdir !== false)			// ignorieren wenn picdir kein verzeichnis ist
                            {
                            //$mode="standard";       // jedes 6te Bild
                            $mode="time";         // ein Bild alle 120 Sekunden
                            $logdir= $this->selectFilesfromList($picdir, $verzeichnis,$mode, $debug);
                                
                            /* 
                            //Fileliste die kopiert werden soll, vorhandene Dateien werden nicht kopiert, andere Dateien werden gelöscht 
                            $size=sizeof($picdir);
                            $j=0;$k=0;$l=0; $timeWindow=0;
                            $logdir=array();  // logdir loeschen, sonst werden die Filenamen vom letzten Mal mitgenommen
                            for ($i=0;$i<$size;$i++)
                                {
                                //if ($debug) echo "   ".$picdir[$i];           // so sieht man die avi files auch
                                $path_parts = pathinfo($picdir[$i]);
                                if ($path_parts['extension']=="jpg")
                                    {
                                    if ($debug) echo "   ".str_pad($verzeichnis."\\".$picdir[$i],50)."   ".str_pad($sysOps->getNiceFileSize(filesize($verzeichnis."\\".$picdir[$i])),16)."    ".
                                                        date("H:i:s",filemtime($verzeichnis."\\".$picdir[$i]));
                                    $timeFile=filemtime($verzeichnis."\\".$picdir[$i]);
                                    if ($timeWindow==0) $timeWindow=$timeFile;      // initialisieren
                                    if ($timeFile<($timeWindow-60))             // es geht rückwärts also 60 Sekundenfenster vorsehen
                                        {
                                        if ($debug) echo "   ";   
                                        $timeWindow=$timeFile;
                                        $l=0;
                                        }
                                    else
                                        {
                                        $l++;
                                        if ($debug) 
                                            {
                                            if ($l==3) echo "  +"; 
                                            else echo "  |";
                                            }
                                        }
                                    //echo "       Dirname: ".$path_parts['dirname'], "\n";
                                    //echo "       Basename: ".$path_parts['basename'], "\n";
                                    //echo "       Extension: ".$path_parts['extension'], "\n";
                                    //echo "       Filename: ".$path_parts['filename'], "\n"; // seit PHP 5.2.0			
                                    if (($k % 6)==2) 
                                        { 
                                        $logdir[$j++]=$picdir[$i]; 
                                        if ($debug) echo "  *"; 
                                        };
                                    $k++;		// eigener Index, da manche Files übersprungen werden
                                    if ($debug) echo "\n";
                                    } 
                                //if ($debug) echo "\n";
                                }
                            echo "Im Quellverzeichnis ".$verzeichnis." sind insgesamt ".$size." Dateien :\n";
                            echo "Es wird nur jeweils aus sechs jpg Dateien die dritte genommen.\n"; 	
                            //print_r($logdir);	*/
                            $check=array();
                            $handle=opendir ($imgVerzeichnisFull);
                            while ( false !== ($datei = readdir ($handle)) )
                                {
                                if (($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($imgVerzeichnisFull.$datei) == false)) 
                                    {
                                    $check[$datei]=true;
                                    }
                                }
                            closedir($handle);
                            /* im array check steht für vorhandene Dateien ein true, wenn sie auch im Quellverzeichnis sind wird nicht kopiert */
                            $c=0;
                            foreach ($logdir as $filename)
                                {
                                if ( isset($check[$filename]) == true )
                                    {
                                    $check[$filename]=false;
                                    //if ($debug) echo "Datei ".$filename." in beiden Verzeichnissen.\n";
                                    }
                                else
                                    {	
                                    echo "copy ".$verzeichnis."\\".$filename." nach ".$imgVerzeichnisFull.$filename." \n";	
                                    copy($verzeichnis."\\".$filename,$imgVerzeichnisFull.$filename);
                                    $c++;
                                    }
                                }		
                            if ($debug) echo "Verzeichnis für Anzeige im Webfront: ".$imgVerzeichnisFull."\n";	
                            $i=0; $d=0;
                            foreach ($check as $filename => $delete)
                                {
                                if ($delete == true)
                                    {
                                    //if ($debug) echo "Datei ".$filename." wird gelöscht.\n";
                                    unlink($imgVerzeichnisFull.$filename);
                                    $d++;
                                    }
                                else
                                    {
                                    //if ($debug) echo "   ".$filename."\n";
                                    $i++;		
                                    }	
                                }	
                            echo "insgesamt ".$i." Dateien im Zielverzeichnis. Dazu wurden ".$c." Dateien kopiert und ".$d." Dateien im Zielverzeichnis ".$imgVerzeichnisFull." geloescht.\n";
                
                            /* ein schönes Tablearray machen mit 5 Spalten */
                            $end=sizeof($logdir);
                            if ($end>100) $end=100;		
                            for ($j=1; $j<$end;$j++)
                                {
                                /* nutzt imgsrcstring($imgVerzeichnis,$filename,$title,$text="",$span="span")
                                 */
                                //echo "Suche Datei hier: ".$imgVerzeichnisFull.$logdir[$j-1].":\n";
                                //$timeFile=@filemtime($imgVerzeichnisFull.$logdir[$j-1]);            // Fehlermeldung unterdrücken wenn es Datei zb nicht mehr gibt
                                echo "Suche Datei hier: ".$verzeichnis."\\".$logdir[$j-1].":";
                                $timeFile=@filemtime($verzeichnis."\\".$logdir[$j-1]);            // nicht die kopierte, sondern die originale Datei evaluieren
                                if ($timeFile) 
                                    {
                                    $timeToDisplay=date("d.m.Y H:i:s",$timeFile);
                                    echo "   ok      $j\n";
                                    }
                                else 
                                    {
                                    $timeToDisplay=$this->extractTime($logdir[$j-1]);
                                    echo "   extract $j\n";
                                    }
                                if (($j % 5)==0) { $box.='<td frameborder="1"> '.$this->imgsrcstring($imgVerzeichnis,$logdir[$j-1],"Wolfgang",$timeToDisplay).' </td> </tr>'; }
                                elseif (($j % 5)==1) { $box.='<tr> <td frameborder="1"> '.$this->imgsrcstring($imgVerzeichnis,$logdir[$j-1],"Claudia",$timeToDisplay).' </td>'; }
                                else { $box.='<td frameborder="1"> '.$this->imgsrcstring($imgVerzeichnis,$logdir[$j-1],"WeissNicht",$timeToDisplay).' </td>'; }
                                }
                
                            $box.='</table>';
                            SetValue($pictureFieldID,$box);
                            //echo $box;		
                            }                   /* ende ftp enabled */
                        else 
                            {
                            echo "Fehler Verzeichnis leer. Keine Bilder wurden heute kopiert.\n";
                            $this->dosOps->writeDirStat($cam_config['FTPFOLDER']);
                            $box.='<tr> <td frameborder="1">no pictures from today</td></tr>';
                            $box.='</table>';
                            SetValue($pictureFieldID,$box);
                            }
                        }                   /* ende ftpfolder */
                    }
                }           // ende foreach
            echo "Bilder für alle Kameras wurden kopiert. Die folgenden Kameras blieben unbehandelt und werden geloescht oder versteckt.\n";
            //print_r($camsFound);
            foreach ($camsFound as $camFound => $status)
                {
                if ($debug) echo "   $camFound  ".IPS_GetName($camFound)." wird versteckt.\n";
                IPS_SetHidden($camFound, true);
                }
            
            }
        return ($box);
		}	


    /* CamOperation::getWebfrontCategoryID
     * 
     *  erzeugt einen Category Pfad, $WFC10Cam_Path."_Capture"
     *   Name function ist iritierend
     */
    public function getWebfrontCategoryID($debug=false)
        {
        $WFC10Cam_Path        	 = $this->moduleManagerCam->GetConfigValue('Path', 'WFC10');
        if ($debug) echo "getWebfrontCategoryID from Webfont Path of IPSCam Module in $WFC10Cam_Path\n";
        return (CreateCategoryPath($WFC10Cam_Path."_Capture"));
        }

    /* CamOperation::createVariablePictureField
     *
     * create Variable picturefield
     *
     */
    public function createVariablePictureField($categoryIdCapture)
        {
        return ( CreateVariable("pictureField",   3 /*String*/,  $categoryIdCapture, 50 , '~HTMLBox'));            
        }

    /* CamOperation::getPictureFieldIDs 
     *
     * schöne Zusammenfassung als array, input is ocCamConfig
     *
     */
    public function getPictureFieldIDs($ocCamConfig, $debug=false)
        {
        $pictureFieldIDs=array(); $index=0;
        if ($this->moduleManagerCam)
            {
            $categoryId_WebFrontAdministrator=$this->getWebfrontCategoryID(); 
            $pictureFieldIDs["Base"] = $categoryId_WebFrontAdministrator;      
            if ($debug) echo "getPictureFieldIDs aufgerufen. Bearbeite die Kategorie ".IPS_GetName($categoryId_WebFrontAdministrator)."  ($categoryId_WebFrontAdministrator)\n";
            foreach ($ocCamConfig as $indexName => $cam_config)
                {
                if (isset($cam_config['NAME'])) $cam_name=$cam_config['NAME'];
                else $cam_name=$indexName;          //webcam (mit Index) oder operationcenter formatierung mit Name als index
                if (isset ($cam_config['FTPFOLDER']))         
                    { 
                    if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                        {                                    
                        $categoryIdCapture  = IPS_GetObjectIdByName("Cam_".$cam_name, $categoryId_WebFrontAdministrator);
                        $fieldID = $this->createVariablePictureField($categoryIdCapture);
                        $pictureFieldIDs[$index]["Data"] = $fieldID;
                        if ($debug) echo "   Camera $cam_name hat PictureFieldID $fieldID  \n";
                        $index++;
                        }
                    }           // ende isset ftpfolder
                }           // ende foreach
            }           // ende IPSCam
        return ($pictureFieldIDs);
        }        

    /* CamOperation::getPictureCategoryIDs
     *
     * get Category IDS like they are instances
     */
    
    public function getPictureCategoryIDs($ocCamConfig=false, $debug=false)
        {
        if ($ocCamConfig===false) $ocCamConfig=$this->getCAMConfiguration();
        $pictureIDs=array(); $index=0;
        if ($this->moduleManagerCam)
            {
            foreach ($ocCamConfig as $indexName => $cam_config)
                {
                if (isset($cam_config['NAME'])) $cam_name=$cam_config['NAME'];
                else $cam_name=$indexName;          //webcam (mit Index) oder operationcenter formatierung mit Name als index
                if (isset ($cam_config['FTPFOLDER']))         
                    { 
                    if ( (isset ($cam_config['FTP'])) && (strtoupper($cam_config['FTP'])=="ENABLED") )
                        {                                    
                        $categoryIdCapture  = IPS_GetObjectIdByName("Cam_".$cam_name, $this->CategoryIdData);
                        $pictureIDs[$index] = $categoryIdCapture;
                        if ($debug) echo "   Camera $cam_name hat PictureCategoryID $categoryIdCapture  \n";
                        $index++;
                        }
                    }           // ende isset ftpfolder
                }           // ende foreach
            }           // ende IPSCam
        return ($pictureIDs);
        }   


	/*
	 * Bei jedem Bild als html Verzeichnis und alternativem Bildtitel darstellen
     * nutzt class bildmittext bild ist $imgVerzeichnis.$filename
	 *
	 */

	private function imgsrcstring($imgVerzeichnis,$filename,$title,$text="",$span="span")
		{
		return ('<div class="bildmittext"> <img src="'.$imgVerzeichnis.$filename.'" title="'.$title.'" alt="'.$filename.'" width=100%> <'.$span.'>'.$text.'</'.$span.'></div>');
		//return ('<div class="bildmittext"> <img src="'.$imgVerzeichnis."\\".$filename.'" title="'.$title.'" alt="'.$filename.'" width=100%> <span>'.$text.'</span></div>');
		}						


    }


/*********************************************************************************************
 *
 * status_Display    schreibt den Status der AWS in die Tabelle vom OperationCenter
 * 
 *  __construct
 *  getCategory
 *  initSlider
 *  setStatus
 *
 ***********************************************************************************************/

class statusDisplay
	{
    private $dosOps;                        /* verwendete andere Klassen */
    private $systemDir;                     // das SystemDir, gemeinsam für Zugriff zentral gespeichert

    private $CategoryIdData,$categoryId_TimerSimulation,$archiveHandlerID;

 	private $log_OperationCenter, $auto;           // class declarations

	private $installedModules     	= array();          // koennte man auch static mit einer abstracten Klasse für alle machen
	private $ScriptsUsed = array();                     // alle Skripts für dieses Modul
	
	/**
	 * @public
	 *
	 * Initialisierung des statusDisplays Class Objektes
	 *
	 */

	public function __construct()
		{
		IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");

        $this->dosOps = new dosOps();     // create classes used in this class
        $this->systemDir     = $this->dosOps->getWorkDirectory();

		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('OperationCenter',$repository);
			}
		$this->CategoryIdData=$moduleManager->GetModuleCategoryID('data');
		$this->installedModules = $moduleManager->GetInstalledModules();

		$app_oid=$moduleManager->GetModuleCategoryID()."\n";
		$oid_children=IPS_GetChildrenIDs($app_oid);
		$result=array();
		//echo "  Alle Skript Files :\n";
		foreach($oid_children as $oid)
			{
			$result[IPS_GetName($oid)]=$oid;
			//echo "      OID : ".$oid." Name : ".IPS_GetName($oid)."\n";
			}
		$this->ScriptsUsed=$result;

		$this->categoryId_TimerSimulation    	= IPS_GetCategoryIDByName('TimerSimulation',$this->CategoryIdData);

		$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $this->CategoryIdData, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );

        //$moduleManagerAS = new IPSModuleManager('Autosteuerung',$repository);
        //print_r($this->installedModules);
        if (isset($this->installedModules["Autosteuerung"])) echo "Module Autosteuerung installiert.\n";
        $this->auto=new Autosteuerung();

		$this->log_OperationCenter=new Logging($this->systemDir."Log_OperationCenter.csv",$input,"",true);            // mit File, Objekt und html Logging, kein prefix
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		}

    function getCategory()
        {
        return ($this->categoryId_TimerSimulation);
        }

    function initSlider()
        {
        $variables=$this->auto->getScenes();
        foreach ($variables as $id => $value)
            {
            /* 	function CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') */
            $variableID=CreateVariable($id,1,$this->categoryId_TimerSimulation,100,"~Intensity.100",$this->ScriptsUsed["OperationCenter"],null,"");
            SetValue($variableID,$value);
            }
        }

    /**************************************************************
     *
     * schreibt den Status der AWS in die Tabelle vom OperationCenter
     *
     **********************************************************************/

    function setStatus()
        {
        $oid=IPS_GetVariableIDByName("TableEvents",$this->categoryId_TimerSimulation);
        SetValue($oid,$this->auto->statusAnwesenheitSimulation(true));
        }    

    } // ende class statusDisplay        
		
/****************************************************************************************************************/



/********************************************************************************************
 *
 * Klasse parsefile
 * ================
 *
 * Unterstützung beim filetieren von geladenen Web/Homepages
 *      __construct
 *      parsetxtfile
 *      parsetxtfile_Statistic
 *      parseWebpage
 *      removecomma
 *
 ***************************************************************************************************/


class parsefile
	{

	private $dataID;

	public function __construct($moduldataID)
		{
		//echo "Parsefile construct mit Data ID des aktuellen Moduls: ".$moduldataID."\n";
		$this->dataID=$moduldataID;
		}

	function parsetxtfile($verzeichnis, $name)
		{
		$ergebnis_array=array();

		echo "Data ID des aktuellen Moduls: ".$this->dataID." für den folgenden Router: ".$name."\n";
        if (($CatID=@IPS_GetCategoryIDByName($name,$this->dataID))==false)
         {
			echo "Datenkategorie für den Router ".$name."  : ".$CatID." existiert nicht, jetzt neu angelegt.\n";
			$CatID = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($CatID, $name); // Kategorie benennen
			IPS_SetParent($CatID, $this->dataID); // Kategorie einsortieren unter dem Objekt mit der ID "12345"
			}
		$handle = @fopen($verzeichnis."SystemStatisticRpm.htm", "r");
		if ($handle)
			{
			echo "Ergebnisfile ".$verzeichnis."SystemStatisticRpm.htm gefunden.\n";
			$ok=true;
   		    while ((($buffer = fgets($handle, 4096)) !== false) && $ok) /* liest bis zum Zeilenende */
				{
				/* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */
				//echo $buffer;
	      	    if(preg_match('/statList/i',$buffer))
		   		    {
		   		    do  {
		   		        if (($buffer = fgets($handle, 4096))==false) {	$ok=false; }
			      	    if ((preg_match('/script/i',$buffer))==true) {	$ok=false; }
						if ($ok)
						    {
							//echo "       ".$buffer;
					  		$pos1=strpos($buffer,"\"");
							if ($pos1!=false)
								{
						  		$pos2=strpos($buffer,"\"",$pos1+1);
						  		$ipadresse=substr($buffer,$pos1+1,$pos2-$pos1-1);
						  		$ergebnis_array[$ipadresse]['IPAdresse']=substr($buffer,$pos1+1,$pos2-$pos1-1);
								$buffer=trim(substr($buffer,$pos2+1,200));
								//echo "       **IP Adresse: ".$ergebnis_array[$ipadresse]['IPAdresse']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
								//echo "       **1:".$buffer."\n";
						  		$pos1=strpos($buffer,"\"");
								if ($pos1!=false)
									{
							  		$pos2=strpos($buffer,"\"",$pos1+1);
							  		$ergebnis_array[$ipadresse]['MacAdresse']=substr($buffer,$pos1+1,$pos2-$pos1-1);
									$buffer=trim(substr($buffer,$pos2,200));
									//echo "       **MAC Adresse: ".$ergebnis_array[$ipadresse]['MacAdresse']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
									//echo "       **2:".$buffer."\n";
							  		$pos1=strpos($buffer,',');
									if ($pos1!=false)
										{
								  		$pos2=strpos($buffer,',',$pos1+1);
								  		$ergebnis_array[$ipadresse]['Packets']=(integer)substr($buffer,$pos1+1,$pos2-$pos1-1);
										$buffer=trim(substr($buffer,$pos2,200));
										//echo "       **Packets: ".$ergebnis_array[[$ipadresse]['Packets']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
										//echo "       **3:".$buffer."\n";
								  		$pos1=strpos($buffer,',');
										if ($pos1!==false)
											{
									  		$pos2=strpos($buffer,',',$pos1+1);
									  		$ergebnis_array[$ipadresse]['Bytes']=(integer)substr($buffer,$pos1+1,$pos2-$pos1-1);
											$buffer=trim(substr($buffer,$pos2,200));
											//echo "       **Bytes: ".$ergebnis_array[$ipadresse]['Bytes']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
											//echo "       **4:".$buffer."\n";
											}
										}
									}
								}
						   }
		   		   } while ($ok==true);
					}
				}
			}
		return $ergebnis_array;
		}

	function parsetxtfile_Statistic($verzeichnis, $name)
		{
		$ergebnis_array=array();

		echo "Data ID des aktuellen Moduls: ".$this->dataID." für den folgenden Router: ".$name."\n";
      if (($CatID=@IPS_GetCategoryIDByName($name,$this->dataID))==false)
         {
			echo "Datenkategorie für den Router ".$name."  : ".$CatID." existiert nicht, jetzt neu angelegt.\n";
			$CatID = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($CatID, $name); // Kategorie benennen
			IPS_SetParent($CatID, $this->dataID); // Kategorie einsortieren unter dem Objekt mit der ID "12345"
			}
		/*  Routine sucht in einem File dass zeilenweise ausgelesen wird,
		 *   es wird zwischen dem Anfangsstring und dem Endstring ausgewertet
		 */
		$handle = @fopen($verzeichnis."StatusRpm.htm", "r");
		if ($handle)
			{
			echo "Ergebnisfile ".$verzeichnis."StatusRpm.htm gefunden.\n";
			$ok=true;
   		while ((($buffer = fgets($handle, 4096)) !== false) && $ok) /* liest bis zum Zeilenende */
				{
				/* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */
				//echo $buffer;
	      	if(preg_match('/statistList/i',$buffer))
		   		{
		   		do {
		   		   if (($buffer = fgets($handle, 4096))==false) {	$ok=false; }
			      	if ((preg_match('/script/i',$buffer))==true) {	$ok=false; }
						if ($ok)
						   {
						   /* nächste Zeile wurde ausgelesen, hier stehen die wichtigen Informationen */
					  		$pos1=strpos($buffer,'"');
							//echo "      |".$buffer."    | ".$pos1."  \n";
							if ($pos1!==false)
								{
						  		$pos2=strpos($buffer,'"',$pos1+1);
						  		//echo "Die ersten zwei Anführungszeichen sind auf Position ".$pos1." und ".$pos2." \n";
						  		$received_bytes=substr($buffer,$pos1+1,$pos2-$pos1-1);
						  		$ergebnis_array["RxBytes"]=$this->removecomma($received_bytes);
								$buffer=trim(substr($buffer,$pos2+1,200));
						  		$pos1=strpos($buffer,"\"");
								if ($pos1!=false)
									{
							  		$pos2=strpos($buffer,"\"",$pos1+1);
							  		$transmitted_bytes=substr($buffer,$pos1+1,$pos2-$pos1-1);
							  		$ergebnis_array["TxBytes"]=$this->removecomma($transmitted_bytes);
							  		$ok=false;
									}
								}
						   }
		   		   } while ($ok==true);
					}
				}
			}
		echo "Received Bytes : ".$ergebnis_array["RxBytes"]." Transmitted Bytes : ".$ergebnis_array["TxBytes"]." \n";
		return $ergebnis_array;
		}

    function parseWebpage($data,$len)
        {

        $dataOut=substr($data,0,$len);
        return ($dataOut);    
        }

	private function removecomma($number)
	   {
	   return str_replace(',','',$number);
	   }

	} /* Ende class parsefile*/

/********************************************************************************************
 *
 * Klasse TimerHandling
 * =====================
 * 
 ***************************************************************************************************/

class TimerHandling
	{

	private $ScriptsUsed = array();
	
	public function __construct()
		{
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		$moduleManager = new IPSModuleManager('OperationCenter',$repository);
		$app_oid=$moduleManager->GetModuleCategoryID()."\n";
		$oid_children=IPS_GetChildrenIDs($app_oid);
		$result=array();
		//echo "  Alle Skript Files :\n";
		foreach($oid_children as $oid)
			{
			$result[IPS_GetName($oid)]=$oid;
			//echo "      OID : ".$oid." Name : ".IPS_GetName($oid)."\n";
			}
		$this->ScriptsUsed=$result;
		}
		
	public function listScriptsUsed()
		{
		return ($this->ScriptsUsed);
		}		

	/***************************************************************************************/

	/* automatisch Timer kreieren, damit nicht immer alle Befehle kopiert werden müssen */

	function CreateTimerOC($name,$stunde,$minute)
		{
		/* EventHandler Config regelmaessig bearbeiten */
			
		$timID=@IPS_GetEventIDByName($name, $this->ScriptsUsed["OperationCenter"]);
		if ($timID==false)
			{
			$timID = IPS_CreateEvent(1);
			IPS_SetParent($timID, $this->ScriptsUsed["OperationCenter"]);
			IPS_SetName($timID, $name);
			IPS_SetEventCyclic($timID,0,0,0,0,0,0);
			IPS_SetEventCyclicTimeFrom($timID,$stunde,$minute,0);  /* immer um ss:xx */
			IPS_SetEventActive($timID,true);
			echo "   Timer Event ".$name." neu angelegt. Timer um ".$stunde.":".$minute." ist aktiviert.\n";
			}
		else
			{
			echo "   Timer Event ".$name." bereits angelegt. Timer um ".$stunde.":".$minute." ist aktiviert.\n";
			IPS_SetEventActive($timID,true);
			}
		return($timID);
		}

    /* TimerHandling::CreateTimerSync
     *
     */
	function CreateTimerSync($name,$sekunden)
		{
		$timID = @IPS_GetEventIDByName($name, $this->ScriptsUsed["OperationCenter"]);
		if ($timID==false)
			{
			$timID = IPS_CreateEvent(1);
			IPS_SetParent($timID, $this->ScriptsUsed["OperationCenter"]);
			IPS_SetName($timID, $name);
			IPS_SetEventCyclic($timID,0,1,0,0,1,$sekunden);      /* alle 150 sec */
			//IPS_SetEventActive($tim2ID,true);
			IPS_SetEventCyclicTimeFrom($timID,0,2,0);  /* damit die Timer hintereinander ausgeführt werden */
			echo "   Timer Event ".$name." neu angelegt. Timer $sekunden sec ist noch nicht aktiviert.\n";
			}
		else
			{
			echo "   Timer Event ".$name." bereits angelegt. Timer $sekunden sec ist noch nicht aktiviert.\n";
			IPS_SetEventCyclicTimeFrom($timID,0,2,0);  /* damit die Timer hintereinander ausgeführt werden */
			//IPS_SetEventActive($tim2ID,true);
			}
		return($timID);
		}		
	
	} /* Ende class timer */


/* class handles about Slenium Chromedriver Update
 * uses config from this class
 *
 *      __construct
 *      getprocessDir
 *      createCmdFileStartSelenium
 *      createCmdFileStoppSelenium
 *      getSeleniumDirectoryContent
 *      getSeleniumDirectory
 *      identifyFileByVersion
 *      stoppSelenium
 *      startSelenium
 *      copyChromeDriver
 *      deleteChromedriverBackup
 *      renameChromedriver
 *      readChromedriverVersion
 */

class seleniumChromedriverUpdate extends OperationCenterConfig
    {

    protected $debug;
    protected $ipsOps,$sysOps,$dosOps;                 // class zur sofortigen verwendung
    protected $configuration;
    protected $selDir;
    protected $selDirContent = array();
    protected $filename;                      // Filename des neuen Chromdrivers

	public function __construct($debug=false)
		{
        if ($debug) echo "class SeleniumChromedriverUpdate, works with Watchdog or OperationCenter Modul.\n";
        $this->debug=$debug;
        $this->ipsOps = new ipsOps();   
        $this->dosOps = new dosOps();  
        $this->configuration=$this->setConfigurationSoftware();    
        $this->sysOps = new sysOps();    
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

    function createCmdFileActiveSelenium($verzeichnis=false,$debug=false)
        {
        if ($verzeichnis===false) $processDir = $this->getprocessDir();
        else $processDir=$verzeichnis;
        if ($debug) echo "Active Selenium. Write cmd file in $processDir.\n";
        $handle2=fopen($processDir."active_Selenium.bat","w");
        fwrite($handle2,"query user\r\n");
        fwrite($handle2,"wmic process where name=\"java.exe\" get name,SessionId,ExecutablePath,CommandLine\r\n");
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

    /* ein Script aufrufen mit dem Selenium gestartet wird. 
     * Wenn Debug 2 dann das Exe nicht aufrufen, erweiterte Fehlersuche
     */
    function activeSelenium($session=-1,$debug=false)
        {
        $status=false;
        $processDir = $this->getprocessDir();
        $command = $processDir."active_Selenium.bat";
        if ($debug) 
            {
            echo "Active Selenium. Execute cmd file active_Selenium.bat in $processDir.\n";
            $this->dosOps->readFile($processDir."active_Selenium.bat");
            echo "Command File: $command.\n";
            }
        if ($debug<2)       // true ist nicht kleiner zwei, muss 1 sein
            {
            if ($debug) echo "activeSelenium, execute show and wait for $command\n";
            $status = $this->sysOps->ExecuteUserCommand($command,"",true,true,$session,$debug);                   // false do not show true wait, hier andersrum da selenium offen bleibt  $command,$parameter="",$show=true,$wait=false,$session=-1,$debug=false
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

    /* seleniumChromedriverUpdate::readChromedriverVersion
     * aktuellen chromedriver version auslesen
     */
    function readChromedriverVersion($latestChromeDriver=false,$debug=false)
        {
        if ($latestChromeDriver===false) $latestChromeDriver=$this->filename;
        if ($latestChromeDriver===false) return(false);
        $processDir = $this->getprocessDir();
        if ($debug) echo "Read Chromedriver Version. Write cmd file in $processDir.\n";
        $handle2=fopen($processDir."read_ChromedriverVersion.bat","w");
        fwrite($handle2,"cd ".$this->selDir."\r\n");
        fwrite($handle2,"chromedriver.exe --version\r\n");
        fclose($handle2);                 

        $command = $processDir."read_ChromedriverVersion.bat";
        if ($debug) $this->dosOps->readFile($processDir."read_ChromedriverVersion.bat");
        $status = $this->sysOps->ExecuteUserCommand($command,"",false,true);                   // false do not show true wait
        if ($debug) echo "Status on renaming $latestChromeDriver with chromedriver.exe and Status : $status \n";
        $pos1=strpos($status,"ChromeDriver ");
        if ($pos1===false) echo "Warning, no ChromeDriver Version output\n";
        $status=substr($status,$pos1+13,10);
        return ($status);
        }

    }

/* class handles data display in RemoteAccess Data Pane
 *      showSqlStatus
 *      showRemoteAcessStatus
 *      showTailscaleStatus
 *
 */

class RemoteAccessData
    {

    /* gibt nur die OID der ersten SQL Instanz zurück
     */
    function showSqlStatus($debug=false)
        {
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
        $oidResult = $modulhandling->getInstances('MySQL');
        if (sizeof($oidResult)>0) 
            {
            $oid=$oidResult[0];           // ersten treffer new_checkbox_tree_get_multi_selection
            if ($debug) echo "sqlHandle: new $oid (".IPS_GetName($oid).") for MySQL Database found.\n";
            return ($oid);
            }
        else 
            {
            if ($debug) echo "sqlHandle: OID einer Instance MySQL not found.\n";
            return (false);
            }
        }

    /* rausfinden wann die letzten Updates von IPS Version waren
     *
     */
    function showRemoteAcessStatus($debug=false)    
        {
        $ipsOps = new ipsOps();
        $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
        // ab IPS7 gibt es das webfront Verzeichnis nicht mehr verschoben in das user verzeichnis
        IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");
        $moduleManagerRA  = new ModuleManagerIPS7('RemoteAccess'   ,$repository);

        $WFC10_Enabled        = $moduleManagerRA->GetConfigValue('Enabled', 'WFC10');
        if ($WFC10_Enabled==true)
            {
            $WFC10_Path        	 = $moduleManagerRA->GetConfigValue('Path', 'WFC10');
            if ($debug) echo "\nWebportal RemoteAccess Administrator installieren auf lokalem Pfad : ".$WFC10_Path." Make Summary:\n";
            $categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
            
            $inputData=array();
            $serverName=array();
            $server = $ipsOps->getChildrenIDsOfType($categoryId_WebFront,0);
            $object = "IPS_Version";
            if ($debug) echo "   Search for Category SysInfo and Object IPS_Version:\n";
            $line=0;
            foreach($server as $entry)
                {
                $name=IPS_GetName($entry);
                $serverName[$entry]=$name; 
                $oid        = @IPS_GetCategoryIDByName($name, $categoryId_WebFront); 
                $vid        = @IPS_GetCategoryIDByName("SysInfo", $oid);
                $variableId = @IPS_GetObjectIDByName($object, $vid);
                if ($variableId) 
                    {
                    $data=IPS_GetVariable($variableId);
                    if ($debug) echo "    ".str_pad($name,20).str_pad($vid,10).str_pad(GetValue($variableId),15)."     ".date("d.m.Y H:i",$data["VariableChanged"])."   ".date("d.m.Y H:i",$data["VariableUpdated"])." \n"; 

                    $inputData[$line]["Server"]=$name;
                    $inputData[$line]["Version"]=GetValue($variableId);
                    $inputData[$line]["Object"]=$object; 
                    $inputData[$line]["VariableChanged"]=$data["VariableChanged"]; 
                    $inputData[$line]["VariableUpdated"]=$data["VariableUpdated"]; 

                    $line++;
                    }
                }
            //print_r($serverName);
            //print_R($inputData);

            // show inputData, zeilenweise strukturiert
            $ipsTables = new ipsTables();
            $config["html"]='html';  
            $config["insert"]["Header"]    = true;              // Header erste Zeile darstellen  
            $config["format"]["class-id"] = false;
            $display = [
                            "Server"                        => ["header"=>"Server","format"=>""],
                            "Object"                        => ["header"=>"Object","format"=>""],
                            "Version"                       => ["header"=>"Version","format"=>""],
                            "VariableChanged"               => ["header"=>"VariableChanged","format"=>"datetime"],
                            "VariableUpdated"               => ["header"=>"VariableUpdated","format"=>"datetime"],
                        ];
            $text = $ipsTables->showTable($inputData, $display ,$config,false);                // true Debug
            return ($text);  
            }
        else return (false);
        }

    /* show tailscale Status
     *
     */
    function showTailscaleStatus($resultSystemInfo,$debug=false)
        {
        $dosOps = new dosOps();
        $sysOps = new sysOps();

        $lines=explode("\n",$resultSystemInfo);
        //print_r($lines);
        $tailScaleServer=array();
        $num=0;
        foreach ($lines as $line)
            {
            $params=explode(" ",$line);
            foreach ($params as $id=>$param) 
                {
                //echo "\"$param\" ";
                if ($param=="") unset($params[$id]);
                }
            //print_r($params);
            $sub=0;
            foreach ($params as $id=>$param) 
                {
                //echo "\"$param\" ";
                switch ($sub)
                    {
                    case 0:
                        $tailScaleServer[$num]["IP"]=$param;
                        break;
                    case 1:
                        $tailScaleServer[$num]["NAME"]=$param;
                        break;
                    case 2:
                        $tailScaleServer[$num]["USER"]=$param;                            
                        break;
                    case 3:
                        $tailScaleServer[$num]["SYSTEM"]=$param;                            
                        break;
                    case 4:
                        if ($debug) echo "Status of ".$tailScaleServer[$num]["NAME"]." is $param \n";
                        //if 
                        $tailScaleServer[$num]["STATUS"]=$param;                            
                        break;                            
                    default:
                        break;
                    }
                $sub++;
                }
            $num++;
            }
        if ($debug) print_r($tailScaleServer);

        // fertige Routinen für eine Tabelle in der HMLBox verwenden
        $ipsTables = new ipsTables();
        $config["html"]='html';  
        $config["insert"]["Header"]    = true;              // Header erste Zeile darstellen  
        $config["format"]["class-id"] = false;
        //$config["format"]["reuse-styleid"] = "OpCent";
        $display = [
                "NAME"                        => ["header"=>"Name","format"=>""],
                "IP"                        => ["header"=>"IP Adr","format"=>""],
                "USER"                       => ["header"=>"User","format"=>""],
                "SYSTEM"                       => ["header"=>"System","format"=>""],
                "STATUS"                       => ["header"=>"Status","format"=>""],
            ];
        $text = $ipsTables->showTable($tailScaleServer, $display ,$config,false);                // true Debug
        return $text;   
                
        }

    } 

/***************************************************************************************************************
 *
 *  Allgemeine Funktionen
 *
 * Funktionen ausserhalb der Klassen
 *
 * move_camPicture
 * get_Data
 * extractIPaddress		
 * dirtoArray, dirtoArray2
 * tts_play				Ausgabe von Ton für Sprachansagen
 * CyclicUpdate 		updatet und installiert neue Versionen der Module 
 *
 *
 *****************************************************************************************************************/

    function move_camPicture($verzeichnis,$WebCam_LetzteBewegungID)
        {
        $count=100;
        //echo "<ol>";

        // Test, ob ein Verzeichnis angegeben wurde
        if ( is_dir ( $verzeichnis ))
            {
            // öffnen des Verzeichnisses
            if ( $handle = opendir($verzeichnis) )
                {
                /* einlesen der Verzeichnisses
                nur count mal Eintraege
                */
                while ((($file = readdir($handle)) !== false) and ($count > 0))
                    {
                    //echo "move_camPicture, Verzeichnis : ".$verzeichnis."  Filename : ".$file."\n";
                    if ( ($file != ".") && ($file != "..") )
                        {
                        $dateityp=filetype( $verzeichnis.$file );
                        if ($dateityp == "file")
                            {
                            $count-=1;
                            $unterverzeichnis=date("Ymd", filectime($verzeichnis.$file));
                            $letztesfotodatumzeit=date("d.m.Y H:i", filectime($verzeichnis.$file));
                            if (is_dir($verzeichnis.$unterverzeichnis))
                                {	
                                }
                            else
                                {
                                mkdir($verzeichnis.$unterverzeichnis);
                                }
                            rename($verzeichnis.$file,$verzeichnis.$unterverzeichnis."\\".$file);
                            //echo "Datei: ".$verzeichnis.$unterverzeichnis."\\".$file." verschoben.\n";
                            SetValue($WebCam_LetzteBewegungID,$letztesfotodatumzeit);
                            }
                        }
                    } /* Ende while */
                closedir($handle);
                } /* end if dir */
            }/* ende if isdir */
        else
            {
            echo "Kein FTP Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
            }
        return(100-$count);
        }



    /*********************************************************************************************/

    function get_data($url) {
        $ch = curl_init($url);
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);           // return web page
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, false);                    // don't return headers
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);          // follow redirects, wichtig da die Root adresse automatisch umgeleitet wird
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (FM Scene 4.6.1)"); // who am i

        /*   CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => "LOOKUPADDRESS=".$argument1,  */

        $data = curl_exec($ch);

        /* Curl Debug Funktionen */
        /*
        echo "Channel :".$ch."\n";
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );

        echo "Fehler ".$err." von ";
        print_r($errmsg);
        echo "\n";
        echo "Header ";
        print_r($header);
        echo "\n";
        */

        curl_close($ch);

        return $data;
        }

    /*********************************************************************************************/

    function extractIPaddress($ip)
        {
            $parts = str_split($ip);   /* String in lauter einzelne Zeichen zerlegen */
            $first_num = -1;
            $num_loc = 0;
            foreach ($parts AS $a_char)
                {
                if (is_numeric($a_char))
                    {
                    $first_num = $num_loc;
                    break;
                    }
                $num_loc++;
                }
            if ($first_num == -1) {return "unknown";}

            /* IP adresse Stelle fuer Stelle dekodieren, Anhaltspunkt ist der Punkt */
            $result=substr($ip,$first_num,20);
            //echo "Result :".$result."\n";
            $pos=strpos($result,".");
            $result_1=substr($result,0,$pos);
            $result=substr($result,$pos+1,20);
            //echo "Result :".$result."\n";
            $pos=strpos($result,".");
            $result_2=substr($result,0,$pos);
            $result=substr($result,$pos+1,20);
            //echo "Result :".$result."\n";
            $pos=strpos($result,".");
            $result_3=substr($result,0,$pos);
            $result=substr($result,$pos+1,20);
            //echo "Result :".$result."\n";
            $parts = str_split($result);   /* String in lauter einzelne Zeichen zerlegen */
            $last_num = -1;
            $num_loc = 0;
            foreach ($parts AS $a_char)
                {
                if (is_numeric($a_char))
                    {
                    $last_num = $num_loc;
                    }
                $num_loc++;
                }
            $result=substr($result,0,$last_num+1);
            //echo "-------------------------> externe IP Adresse in Einzelteilen:  ".$result_1.".".$result_2.".".$result_3.".".$result."\n";
            return($result_1.".".$result_2.".".$result_3.".".$result);
        }

    /**************************************************************/


    function dirToArray($dir)
        {
        $result = array();

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value)
            {
            if (!in_array($value,array(".","..")))
                {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                    {
                    $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                    }
                else
                    {
                    $result[] = $value;
                    }
                }
            }
        return $result;
        }

    /*********************************************************************************************/

    function dirToArray2($dir)
        {
        $result = array();

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value)
            {
            if (!in_array($value,array(".","..")))
                {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                    {
                    //$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                    }
                else
                    {
                    $result[] = $value;
                    }
                }
            }
        return $result;
        }


    /********************************************************************************************
    *
    * Ausgabe von Ton für Sprachansagen, kommt noch einmal in der Sprachsteuerungslibrary vor 
    * beide functions sind gleich gestellt.
    *
    *  sk    soundkarte   es gibt immer nur 1, andere bis 9 kann man implementieren
    *        größer 9 ist eine ID einer EchoControl Instanz (Amazon Echo Geräte)
    *
    * 	modus == 1 ==> Sprache = on / Ton = off / Musik = play / Slider = off / Script Wait = off
    * 	modus == 2 ==> Sprache = on / Ton = on / Musik = pause / Slider = off / Script Wait = on
    * 	modus == 3 ==> Sprache = on / Ton = on / Musik = play  / Slider = on  / Script Wait = on
    *
    * zum Beispiel  tts_play(1,$speak,'',2);  // Soundkarte 1, mit diesem Ansagetext, kein Ton, Modus 2 
    *
    *************************************************************/

        function tts_play($sk,$ansagetext,$ton,$modus,$debug=false)
            {
            $tts_status=true;		
            $sprachsteuerung=false; $remote=false;
            $dosOps = new dosOps();     // create classes used in this class
            $systemDir     = $dosOps->getWorkDirectory();

            if ($debug) echo "Aufgerufen als Teil der Library des OperationCenter.\n";
            $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
            if (!isset($moduleManager))
                {
                IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
                $moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
                }
            $installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
            if ( (isset($installedModules["Sprachsteuerung"]) )  && ($installedModules["Sprachsteuerung"] <>  "") ) 
                {
                $sprachsteuerung=true;
                IPSUtils_Include ("Sprachsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Sprachsteuerung");					
                $CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
                $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
                $config=Sprachsteuerung_Configuration();
                if ( (isset($config["RemoteAddress"])) && (isset($config["ScriptID"])) && ($sk<10) ) 
                    { 
                    $remote=true; 
                    $url=$config["RemoteAddress"]; 
                    $oid=$config["ScriptID"]; 
                    }					

                $ipsOps = new ipsOps();
                $NachrichtenID = $ipsOps->searchIDbyName("Nachricht",$CategoryIdData);
                $NachrichtenScriptID = $ipsOps->searchIDbyName("Nachricht",$CategoryIdApp);
                //echo "Found $NachrichtenID und $NachrichtenScriptID in $CategoryIdData when searching vor \"Nachricht\" : ".IPS_GetName($NachrichtenID)." und ".IPS_GetName($NachrichtenScriptID)."\n";

                if ($debug) echo "Nachrichten gibt es auch : ".$NachrichtenID ."  (".IPS_GetName($NachrichtenID).")   ".$NachrichtenScriptID." \n";

                if (isset($NachrichtenScriptID))
                    {
                    $NachrichtenInputID = $ipsOps->searchIDbyName("Input",$NachrichtenID);    
                    $log_Sprachsteuerung=new Logging($systemDir."Sprachsteuerung\Log_Sprachsteuerung.csv",$NachrichtenInputID);
                    if ($sk<10) $log_Sprachsteuerung->LogNachrichten("Sprachsteuerung $sk mit \"".$ansagetext."\"");
                    else $log_Sprachsteuerung->LogNachrichten("Sprachsteuerung $sk (".IPS_GetName($sk).") mit \"".$ansagetext."\"");
                    }
                }
            $CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');           // App Kategorie der Sprachsteuerung
            $scriptIdSprachsteuerung   = @IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);
            if ($scriptIdSprachsteuerung==false) $sprachsteuerung=false;
            if ( ($sprachsteuerung==true) && ($remote==false) )
                {
                if ($sk<10)             // die müssen nur angelegt werden wenn sie abgefragt werden, nicht für Ausgabe auf Echo
                    {
                    if ($debug) echo "Sprache lokal ausgeben.\n";	
                    $id_sk1_musik = IPS_GetInstanceIDByName("MP Musik", $scriptIdSprachsteuerung);
                    $id_sk1_ton = IPS_GetInstanceIDByName("MP Ton", $scriptIdSprachsteuerung);
                    $id_sk1_tts = IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);

                    $id_sk1_musik_status = IPS_GetVariableIDByName("Status", $id_sk1_musik);
                    $id_sk1_ton_status = IPS_GetVariableIDByName("Status", $id_sk1_ton);
                    $id_sk1_musik_vol = IPS_GetVariableIDByName("Lautstärke", $id_sk1_musik);
                    $id_sk1_counter = CreateVariable("Counter", 1, $scriptIdSprachsteuerung , 0, "",0,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
                    if ($debug) echo "\nAlle IDs -> Musik:".$id_sk1_musik." Status:".$id_sk1_musik_status." Vol:".$id_sk1_musik_vol." Ton:".$id_sk1_ton." TonStatus:".$id_sk1_ton_status." tts:".$id_sk1_tts."\n";
                    }
                $wav = array
                    (
                    "hinweis"  => IPS_GetKernelDir()."media/wav/hinweis.wav",
                    "meldung"  => IPS_GetKernelDir()."media/wav/meldung.wav",
                    "abmelden" => IPS_GetKernelDir()."media/wav/abmelden.wav",
                    "aus"      => IPS_GetKernelDir()."media/wav/aus.wav",
                    "coin"     => IPS_GetKernelDir()."media/wav/coin-fall.wav",
                    "thunder"  => IPS_GetKernelDir()."media/wav/thunder.wav",
                    "clock"    => IPS_GetKernelDir()."media/wav/clock.wav",
                    "bell"     => IPS_GetKernelDir()."media/wav/bell.wav",
                    "horn"     => IPS_GetKernelDir()."media/wav/horn.wav",
                    "sirene"   => IPS_GetKernelDir()."media/wav/sirene.wav"
                    );
                switch ($sk)		/* Switch unterschiedliche Routinen anhand der Soundkarten ID, meistens eh nur eine */
                    {
                    //---------------------------------------------------------------------
                    case '1':
                        $status = GetValueInteger($id_sk1_ton_status);
                        while ($status == 1)	$status = GetValueInteger($id_sk1_ton_status);
                        $sk1_counter = GetValueInteger($id_sk1_counter);
                        $sk1_counter++;
                        SetValueInteger($id_sk1_counter, $sk1_counter);
                        if($sk1_counter >= 9) SetValueInteger($id_sk1_counter, $sk1_counter = 0);
                        if($ton == "zeit")
                            {
                            $time = time();
                            // die Integer-Wandlung dient dazu eine führende Null zu beseitigen
                            $hrs = (integer)date("H", $time);
                            $min = (integer)date("i", $time);
                            $sec = (integer)date("s", $time);
                            // "kosmetische Behandlung" für Ein- und Mehrzahl der Minutenangabe
                            if($hrs==1) $hrs = "ein";
                            $minuten = "Minuten";
                            if($min==1)
                                {
                                $min = "eine";
                                $minuten = "Minute";
                                }
                            // Zeitansage über Text-To-Speech
                            $ansagetext = "Die aktuelle Uhrzeit ist ". $hrs. " Uhr und ". $min. " ". $minuten;
                            $ton        = "";
                            }
                        //Lautstärke von Musik am Anfang speichern
                        $merken = $musik_vol = GetValue($id_sk1_musik_vol);
                        $musik_status 			 = GetValueInteger($id_sk1_musik_status);
                        $ton_status           = GetValueInteger($id_sk1_ton_status);					

                        if($modus == 2)
                            {
                            if($musik_status == 1)
                                {
                                /* wenn der Musikplayer läuft, diesen auf Pause setzen */
                                WAC_Pause($id_sk1_musik);
                                }
                            }


                        if($modus == 3)
                            {
                            //Slider
                            for ($musik_vol; $musik_vol>=1; $musik_vol--)
                                {
                                WAC_SetVolume ($id_sk1_musik, $musik_vol);
                                $slider = 3000; //Zeit des Sliders in ms
                                if($merken>0) $warten = $slider/$merken; else $warten = 0;
                                IPS_Sleep($warten);
                                }
                            }

                        if($ton != "" and $modus != 1)
                            {
                            if($ton_status == 1)
                                {
                                /* Wenn Ton Wiedergabe auf Play steht dann auf Stopp druecken */
                                WAC_Stop($id_sk1_ton);
                                WAC_SetRepeat($id_sk1_ton, false);
                                WAC_ClearPlaylist($id_sk1_ton);
                                }
                            if (isset($wav[$ton])==true)
                                {
                                WAC_AddFile($id_sk1_ton,$wav[$ton]);
                                echo "Check SoundID: ".$id_sk1_ton." Ton: ".$wav[$ton]." Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n";
                                while (@WAC_Next($id_sk1_ton)==true) { echo " Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n"; }
                                WAC_Play($id_sk1_ton);
                                //solange in Schleife bleiben wie 1 = play
                                sleep(1);
                                $status = getvalue($id_sk1_ton_status);
                                while ($status == 1)	$status = getvalue($id_sk1_ton_status);
                                }						
                            }

                        if($ansagetext !="")
                            {
                            if($ton_status == 1)
                                {
                                /* Wenn Ton Wiedergabe auf Play steht dann auf Stopp druecken */
                                WAC_Stop($id_sk1_ton);
                                WAC_SetRepeat($id_sk1_ton, false);
                                WAC_ClearPlaylist($id_sk1_ton);
                                if ($debug) echo "Tonwiedergabe auf Stopp stellen \n";
                                }
                            if ($debug) echo "Für Ansagetext erzeuge Datei : ".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav\n";     
                            $dosOps->mkdirtree(IPS_GetKernelDir()."media/wav/");                                               
                            $status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
                            if (!$status) { echo "Error Erzeugung Sprachfile gescheitert.\n"; $tts_status=false; }
                            WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
                            if ($debug) echo "Check SoundID: ".$id_sk1_ton." Ton: ".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav  Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n";
                            while (@WAC_Next($id_sk1_ton)==true) 
                                { 
                                if ($debug) echo " Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n"; 
                                }
                            $status=@WAC_Play($id_sk1_ton);
                            if (!$status) 
                                { 
                                if ($debug) echo "Fehler WAC_play nicht ausführbar.\n"; 
                                $tts_status=false; 
                                }
                            
                            WAC_Stop($id_sk1_ton);
                            WAC_SetRepeat($id_sk1_ton, false);
                            WAC_ClearPlaylist($id_sk1_ton);
                            $status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
                            if (!$status) echo "Error";
                            WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
                            if ($debug) echo "---------------------------".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav\n";
                            WAC_Play($id_sk1_ton);
                            }

                        //Script solange anhalten wie Sprachausgabe läuft
                        if($modus != 1)
                            {
                            if (GetValueInteger($id_sk1_ton_status) == 1) echo "Noch warten bis Status des Ton Moduls ungleich 1 :";
                            while (GetValueInteger($id_sk1_ton_status) == 1)
                                {												
                                sleep(1);
                                if ($debug) echo ".";
                                }
                            if ($debug) echo "\nLänge der Playliste : ".WAC_GetPlaylistLength($id_sk1_ton)." Position : ".WAC_GetPlaylistPosition($id_sk1_ton)."\n";														
                            }

                        if($modus == 3)
                            {
                            $musik_vol = GetValueInteger($id_sk1_musik_vol);
                            for ($musik_vol=1; $musik_vol<=$merken; $musik_vol++)
                            {
                                WAC_SetVolume ($id_sk1_musik, $musik_vol);
                                $slider = 3000; //Zeit des Sliders in ms
                                if($merken>0) $warten = $slider/$merken; else $warten = 0;
                                IPS_Sleep($warten);
                                }
                            }
                        if($modus == 2)
                            {
                            if($musik_status == 1)
                                {
                                /* wenn der Musikplayer läuft, diesen auf Pause setzen */
                                WAC_Pause($id_sk1_musik);
                                if ($debug) echo "Musikwiedergabe auf Pause stellen \n";							
                                }
                            }
                        break;

                    //---------------------------------------------------------------------

                    //Hier können weitere Soundkarten eingefügt werden
                    case '2':
                        echo "Fehler: Soundkarte 2 nicht definiert.\n";
                        break;
                    default:
                        $modulhandling = new ModuleHandling($debug);
                        $echos=$modulhandling->getInstances('EchoRemote');
                        if (in_array($sk,$echos)) EchoRemote_TextToSpeech($sk, $ansagetext);
                        break;				

                    }  //end switch
                } //endif	sprachsteuerungs Modul richtig konfiguriert
                
            if ( ($sprachsteuerung==true) && ($remote==true) )
                {
                if ($debug)echo "Sprache remote auf ".$url." ausgeben. verwende Script mit OID : ".$oid."\n";
                $rpc = new JSONRPC($url);
                $monitor=array("Text" => $ansagetext);
                $rpc->IPS_RunScriptEx($oid,$monitor);
                }

            return ($tts_status);
        }   //end function

    /***************************************************
    *
    * updatet und installiert neue Versionen der Module
    *
    *******************************************/

    function CyclicUpdate()
        {
        // Repository
        $repository = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
        $repositoryJW="https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/";

        $moduleManager = new IPSModuleManager('', '', sys_get_temp_dir(), true);

        $versionHandler = $moduleManager->VersionHandler();
        $versionHandler->BuildKnownModules();
        $knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
        $installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
        $inst_modules = "Verfügbare Module und die installierte Version :\n\n";
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

        foreach ($loadfromrepository as $upd_module)
        {
            $useRepository=$knownModules[$upd_module]['Repository'];
            echo "-----------------------------------------------------------------------------------------------------------------------------\n";
            echo "Update Module ".$upd_module." from Repository : ".$useRepository."\n";
            $LBG_module = new IPSModuleManager($upd_module,$useRepository);
            $LBG_module->LoadModule();
        $LBG_module->InstallModule(true);
            }
        }
        

/****************************************************/


?>