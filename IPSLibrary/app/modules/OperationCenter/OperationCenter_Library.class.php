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
 * diese Klassen werden hier behandelt:
 *
 * OperationCenter
 *      BackupIpsymcon          extend OperationCenter
 *      LogFileHandler          extend OperationCenter
 * DeviceManagement
 * statusDisplay
 * parsefile
 * TimerHandling
 *
 * und einige Allgemeine Funktionen
 */




/***************************************************************************************************************
 *
 * Routinen für Klasse  OperationCenter
 *
 * Herausfinden der eigenen externen und lokalen IP Adresse
 *   whatismyIPaddress1   verwendet http://whatismyipaddress.com/
 *   whatismyIPaddress2
 *   ownIPaddress
 *
 * sys device ping IP Adresse von LED Modul oder DENON Receiver
 * Wenn device_ping zu oft fehlerhaft ist wird das Gerät rebootet, erfordert einen vorgelagerten Schalter und eine entsprechende Programmierung
 * Erreichbarkeit der Remote Server zum Loggen
 *
 *   device_ping  
 *   server_ping
 *   writeServerPingResults
 *
 *   SysPingAllDevices
 *   writeSysPingResults
 *
 * systeminfo 			systeminfo vom PC auslesen und lokal die relevanten Daten speichern
 * readSystemInfo		die lokalen Daten als text ausgeben
 *
 * device_checkReboot	wenn device_ping zu oft gescheitert ist
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
 * showCamCaptureFiles	es werden von den ftp Verzeichnissen ausgewählte Dateien in das Webfront/user verzeichnis für die Darstellung im Webfront kopiert
 *
 * imgsrcstring, extracttime	Hilfsroutinen
 * DirLogs, readdirToArray		Hilfsroutinen
 *
 * CopyScripts			Scriptfiles auf Dropbox kopieren
 * FileStatus			Statusfiles auf Dropbox kopieren
 * FileStatusDir		StatusFiles in Verzeichnisse verschieben
 * FileStatusDelete		verschobene Files gemeinsam mit den Verzeichnisssen loeschen
 * getFileStatusDir
 *
 * getIPSLoggerErrors	aus dem HTML Info Feld des IPS Loggers die Errormeldungen wieder herausziehen
 * stripHTMLTags
 *
 * andere classes, siehe weiter unten
 *
 ****************************************************************************************************************/

class OperationCenter
	{

    protected $dosOps;                        /* verwendete andere Klassen */
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
	
	/**
	 * @public
	 *
	 * Initialisierung des OperationCenter Objektes
	 *
	 */
	public function __construct($subnet='10.255.255.255',$debug=false)
		{
        $this->debug=$debug;
		IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");

        $this->dosOps = new dosOps();     // create classes used in this class
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

        if (isset($this->installedModules["IPSCam"]))
            {
            /* wird zB von copyCamSnapshot verwendet */
    		$repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
	    	$this->moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);            
            }
        else $this->moduleManagerCam=false;

		$this->AllHostnames = LogAlles_Hostnames();
		}
		
    /****************************************************************************************************************/


    public function setSetup()
        {
        $config=array();
        if ((function_exists("OperationCenter_SetUp"))===false) IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");				
        if (function_exists("OperationCenter_SetUp")) $configInput=OperationCenter_SetUp();
        else echo "*************Fehler, OperationCenter_Configuration.inc.php Konfig File nicht included oder Funktion OperationCenter_SetUp() nicht vorhanden. Es wird mit Defaultwerten gearbeitet.\n";

        configfileParser($configInput, $config, ["DropboxDirectory"],"DropboxDirectory",'C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/scripts/');    
        configfileParser($configInput, $config, ["DropboxStatusDirectory"],"DropboxStatusDirectory",'C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/Status/');   
        configfileParser($configInput, $config, ["DropboxStatusMaxFileCount"],"DropboxStatusMaxFileCount",100);

        configfileParser($configInput, $config, ["Synology"],"Synology",'{"Directory":false,"StatusDirectory":false,"StatusMaxFileCount":100}');
        configfileParser($configInput["Synology"], $config["Synology"], ["Directory"],"Directory",false); 
        configfileParser($configInput["Synology"], $config["Synology"], ["StatusDirectory"],"StatusDirectory",false); 
        configfileParser($configInput["Synology"], $config["Synology"], ["StatusMaxFileCount"],"StatusMaxFileCount",100); 

        configfileParser($configInput, $config, ["MacroDirectory"],"MacroDirectory",false);
        configfileParser($configInput, $config, ["DownloadDirectory"],"DownloadDirectory",false);
        configfileParser($configInput, $config, ["FirefoxDirectory"],"FirefoxDirectory",false);

        configfileParser($configInput, $config, ["CONFIG","Config","Configuration"],"CONFIG",'{"MOVELOGS":true,"PURGELOGS":true,"PURGESIZE":10}');    
        configfileParser($configInput["CONFIG"], $config["CONFIG"], ["MOVELOGS"],"MOVELOGS",true);    
        configfileParser($configInput["CONFIG"], $config["CONFIG"], ["PURGELOGS"],"PURGELOGS",true);    
        configfileParser($configInput["CONFIG"], $config["CONFIG"], ["PURGESIZE"],"PURGESIZE",10);    

		
		/* Defaultwerte vergeben, falls nicht im Configfile eingestellt 
		if (isset($this->oc_Setup['DropboxDirectory'])===false) {$this->oc_Setup['DropboxDirectory']='C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/scripts/';}
		if (isset($this->oc_Setup['DropboxStatusDirectory'])===false) {$this->oc_Setup['DropboxStatusDirectory']='C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/Status/';}
		if (isset($this->oc_Setup['CONFIG'])===false) 
			{
			$this->oc_Setup['CONFIG']= array("MOVELOGS"  => true,"PURGELOGS" => true,"PURGESIZE"  => 10,);
			}		
		else
			{
			if (isset($this->oc_Setup['CONFIG']['MOVELOGS'])===false) {$this->oc_Setup['CONFIG']['MOVELOGS']=true;}
			if (isset($this->oc_Setup['CONFIG']['PURGELOGS'])===false) {$this->oc_Setup['CONFIG']['PURGELOGS']=true;}
			if (isset($this->oc_Setup['CONFIG']['PURGESIZE'])===false) {$this->oc_Setup['CONFIG']['PURGESIZE']=10;}
			}	*/

        configfileParser($configInput, $config, ["BACKUP","Backup"],"BACKUP",'{"Directory":"/Backup/","FREQUENCE":"Day","FULL":["Mon","Wed"],"KEEPDAY":10,"KEEPMONTH":10,"KEEPYEAR":2}');  
        // es werden alle Subkonfigurationen kopiert, wenn das nicht sein soll einmal umsetzen
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["Status","STATUS","status"], "Status","disabled"); 
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["Directory"], "Directory","/Backup/IpSymcon");  
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["FREQUENCE", "Frequence"], "FREQUENCE","Day");  
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["FULL", "Full"], "FULL",'{"Mon","Wed"}');  
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["KEEPDAY", "KeepDay","Keepday"], "KEEPDAY",10);
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["KEEPMONTH", "KeepMonth","Keepmonth"], "KEEPMONTH",10);
        configfileParser($configInput["BACKUP"], $config["BACKUP"], ["KEEPYEAR", "KeepYear","Keepyear"], "KEEPYEAR",2);

        //$this->oc_Setup = $config;       
        return ($config);
        }

    public function getSetup()
        {
        return ($this->oc_Setup);
        }


    public function setConfiguration()
        {
        $config=array();
        if ((function_exists("OperationCenter_Configuration"))===false) IPSUtils_Include ('OperationCenter_Configuration.inc.php', 'IPSLibrary::config::modules::OperationCenter');				
        if (function_exists("OperationCenter_Configuration"))
            {
            $configInput = OperationCenter_Configuration();            
            configfileParser($configInput, $config, ["INTERNET" ],"INTERNET" ,"[]");  
            configfileParser($configInput, $config, ["ROUTER" ],"ROUTER" ,"[]");  
            configfileParser($configInput, $config, ["CAM" ],"CAM" ,"[]");  
            configfileParser($configInput, $config, ["LED" ],"LED" ,"[]");  
            configfileParser($configInput, $config, ["DENON" ],"DENON" ,"[]");  

            }
        //$this->oc_Configuration = $config;
        return ($config);
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

	/**
	 * @public
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
	 
	function whatismyIPaddress1()
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

		echo "\n";
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

	/**
	 * @public
	 *
	 * ownIPaddress liefert eigene IP Adresse
	 *
	 * Output ist ein array mit allen gefundenen IP Adressen und deren Ports
	 *
	 */
	 
	function ownIPaddress($debug=false)
		{
	   
		/********************************************************
	   	Eigene Ip Adresse immer ermitteln
		**********************************************************/

		if ($debug) echo "\nIPConfig Befehl liefert ...\n";
		$ipall=""; $hostname="unknown"; $lookforgateway=false;
		exec('ipconfig /all',$catch);   /* braucht ein MSDOS Befehl manchmal laenger als 30 Sekunden zum abarbeiten ? */
		//exec('ipconfig',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden, allerdings fehlt dann der Hostname */

		$ipports=array();

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

	/**
	 * @public
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

	/**
	 * @public
	 *
	 * rpc call (sys ping) der IP Adresse von bekannten IP Symcon Servern
	 *
	 * Verwendet selbes Config File wie für die Remote Log Server, es wurden zusätzliche Parameter zur Unterscheidung eingeführt
	 *
	 * Wenn der Remote Server erreichbar ist werden Kernel Version und Uptime abgefragt und lokal gespeichert
	 *
	 */
	function server_ping()
		{
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetServerConfig();     /* es werden alle Server abgefragt, im STATUS und LOGGING steht wie damit umzugehen ist */
		$RemoteServer=array();
		//print_r($remServer);
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
					echo "   Server : ".$UrlAddress." mit Name: ".$Name." Fehler Context: ".$context." nicht erreicht.\n";
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
					$ServerName=$rpc->IPS_GetName(0);
					$ServerUptime=$rpc->IPS_GetKernelStartTime();
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
			else
				{
				echo "   Server : ".$UrlAddress." mit Name: ".$Name." nicht auf active konfiguriert.\n";
				}	
			}
			return ($RemoteServer);
		}
		
	/*****************************************************************************
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

	/*************************************************************************************************************
	 *
	 * function SysPingAllDevices
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

	function SysPingAllDevices($log_OperationCenter, $debug=false)
		{
		echo "Sysping All Devices. Subnet : ".$this->subnet."\n";

		$OperationCenterConfig = $this->oc_Configuration;
		//print_r($OperationCenterConfig);
        if (isset($_IPS['EVENT'])) $ipsEvent="TimerEvent from :".$_IPS['EVENT']." ";
        else $ipsEvent="";

        $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$this->categoryId_SysPing);
		
		$SysPingStatusID = IPS_GetObjectIDByName("SysPingExectime",$categoryId_SysPingControl); /* exec time zum besseren Überwachen der Funktion */
		SetValue($SysPingStatusID,time());
        $hourPassed=false; $fourHourPassed=false;
        if ($debug) { $hourPassed=true; $fourHourPassed=true; }
        $SysPingCountID = IPS_GetObjectIDByName("SysPingCount",$categoryId_SysPingControl); /* exec time zum besseren Überwachen der Funktion */
        $SysPingCount   = GetValue($SysPingCountID);
        $SysPingCount++;
        if (($SysPingCount%12)==0)                           // alle 60 Minuten, 0,1,2,3,4,5,6,7,8,9,10,
            {
            if ($SysPingCount>=48) 
                {
                $SysPingCount=0;
                $fourHourPassed=true;
       			if ($debug==false) IPSLogger_Inf(__file__, "$ipsEvent SysPingAllDevices every four hour.");
                }
   			elseif ($debug==false) IPSLogger_Inf(__file__, "$ipsEvent SysPingAllDevices every hour.");
            $hourPassed=true;
            }
        elseif ($debug==false) IPSLogger_Inf(__file__, "$ipsEvent SysPingAllDevices: $SysPingCount every 5 Minutes");
        SetValue($SysPingCountID,$SysPingCount);

        $SysPingTableID = @IPS_GetObjectIDByName("SysPingTable",$categoryId_SysPingControl);
        $SysPingActivityTableID = @IPS_GetObjectIDByName("SysPingActivityTable",$categoryId_SysPingControl); 
        $SysPingResult=array();

		/************************************************************************************
		 * Erreichbarkeit IPCams
		 *************************************************************************************/
		 
		if ( (isset ($this->installedModules["IPSCam"])) && $hourPassed )
			{
   			//IPSLogger_Inf(__file__, "SysPingAllDevices: Check the Cams.");
			$mactable=$this->get_macipTable($this->subnet);
			//print_r($mactable);
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
                if ( (isset($cam_config['IPADRESSE']) ) || (isset($cam_config['MAC'])) )
                    {
                   // echo "IPADRESSE oder MAC Adresse sollte bekannt sein.\n";
                    $CamStatusID = CreateVariableByName($this->categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
                    $ipadresse=""; $macadresse="";
                    if (isset($cam_config['IPADRESSE'])) $ipadresse=$cam_config['IPADRESSE'];
                    if (isset($mactable[$cam_config['MAC']])) 
                        {
                        $macadresse=$cam_config['MAC'];
                        if ($ipadresse == "") $ipadresse=$mactable[$macadresse];
                        elseif ($ipadresse != $mactable[$macadresse]) echo "Kenn mich nicht aus, zwei unterschiedliche IP Adressen.\n";
                        }
                    if ($ipadresse != "")
                        {    
                        echo str_pad("Sys_ping Kamera : ".$cam_name." mit MAC Adresse ".$cam_config['MAC']." und IP Adresse $ipadresse",110);
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
                        echo "Sys_ping Kamera : ".$cam_name." mit Mac Adresse ".$cam_config['MAC']." nicht bekannt.\n";
                        }
                    }   // nur die Einträge in der Konfiguration, bei denen es auch eine MAC Adresse gibt bearbeiten
				} /* Ende foreach */
			}

		/************************************************************************************
		 * Erreichbarkeit LED Ansteuerungs WLAN Geräte
		 *************************************************************************************/
		if ( (isset ($this->installedModules["LedAnsteuerung"])) && $hourPassed )
			{
			Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
			$device_config=LedAnsteuerung_Config();
			$device="LED"; $identifier="IPADR"; /* IP Adresse im Config Feld */
			$SysPingResult += $this->device_ping($device_config, $device, $identifier, $hourPassed, $debug);                  // debug setzt weiter oben auch hourspassed
			$this->device_checkReboot($OperationCenterConfig['LED'], $device, $identifier, $debug);
			}

		/************************************************************************************
		 * Erreichbarkeit Denon Receiver
		 *************************************************************************************/
		if ( (isset ($this->installedModules["DENONsteuerung"])) && $hourPassed )
			{
			Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
			$device_config=Denon_Configuration();
			$deviceConfig=array();
			foreach ($device_config as $name => $config)
				{
				if ( $name != "Netplayer" ) { $deviceConfig[$name]=$config; }
				if ( isset ($config["TYPE"]) ) { if ( strtoupper($config["TYPE"]) == "DENON" ) $deviceConfig[$name]=$config; }
				}
			$device="DENON"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
			$SysPingResult += $this->device_ping($deviceConfig, $device, $identifier, $hourPassed, $debug);
			$this->device_checkReboot($OperationCenterConfig['DENON'], $device, $identifier, $debug);
			}

        if (isset($OperationCenterConfig['INTERNET'])) 
            {
            /************************************************************************************
            * Erreichbarkeit Internet, alle 5 Minuten aufrufen, Routine ignoriert selbst wenn nur jede Stunde notwendig
            *************************************************************************************/
            $device="Internet"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
            $SysPingResult += $this->device_ping($OperationCenterConfig['INTERNET'], $device, $identifier, $hourPassed, $debug);
            $this->device_checkReboot($OperationCenterConfig['INTERNET'], $device, $identifier, $debug);
            }

        if ( $hourPassed )
            {
            /************************************************************************************
            * Erreichbarkeit Router
            *************************************************************************************/
            $device="Router"; $identifier="IPADRESSE";   /* IP Adresse im Config Feld */
            $SysPingResult += $this->device_ping($OperationCenterConfig['ROUTER'], $device, $identifier, $hourPassed, $debug);
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
		if ( (isset ($this->installedModules["IPSWeatherForcastAT"])) && $hourPassed )
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
        if ($fourHourPassed)
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

	/*************************************************************************************************************
	 *
	 * writeSysPingStatistics() als html Tabelle die Ergebnisse über die Erreichbarkeit von Geräten ausgeben
	 *
	 * es werden die Dateneintraege analysiert und ausgegeben
	 *   Erreichbarkeit IPCams
	 *	 writeServerPingResults()
     *   etc.
     *
	 *
	 *
	 **************************************************************************************************************/

	function writeSysPingStatistics($categoryId_SysPing=0,$debug=false)
		{
        if ($debug) echo "writeSysPingStatistics aufgerufen für Darstellung der Erreichbarkeit der IP fähigen Geräte:\n";
        if ($categoryId_SysPing==0) $categoryId_SysPing=$this->categoryId_SysPing;        
        $categoryId_SysPingControl = @IPS_GetObjectIDByName("SysPingControl",$categoryId_SysPing);
        $SysPingTableID            = @IPS_GetObjectIDByName("SysPingTable",$categoryId_SysPingControl);
        $SysPingSortTableID        = @IPS_GetObjectIDByName("SortPingTable",$categoryId_SysPingControl);

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
            foreach ($childrens as $children)
                {
                $varname=IPS_GetName($children);
                switch ($varname)
                    {
                    case "SysPingTable":                                // ignore known entries that are not relevant for statistics
                    case "SysPingActivityTable":
                    case "SysPingExectime":
                    case "SysPingCount":
                    case "SysPingControl":                
                        break;    
                    default:
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
            $PrintHtml.='<tr><td align="right" colspan="3"><font size="-1">last update on '.date("m.d.Y H:i:s").'</font></td></tr>';    
            $PrintHtml.='</table>';    
            SetValue($SysPingTableID,$PrintHtml);
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
			if (AC_GetLoggingStatus($this->archiveHandlerID,$oid))          // sollten schon ausgefiltert sein, sicherheitshalber
		   		{
        		$werte = AC_GetLoggedValues($this->archiveHandlerID,$oid, time()-30*24*60*60, time(),1000); 
		   		//print_r($werte);
				$status=getValue($oid); $first=true; $timeok=time(); 
                $max=0; $count=0;   // keine Ausgabe der gespeicherte Log EIntraege wenn $max kleiner gleich 1 ist
                $offTime=0; $onTime=0;
                $maxend=time()-(30*24*60*60);
                $size=sizeof($werte);
		   		if ($debug) echo "   ".IPS_GetName($oid)." Variable wird gelogged, in den letzten 30 Tagen (bis ".date("d.m. H:i:s",$maxend).") $size Werte. Aktueller Status Available:".($status?"Yes":"No")."\n";
                if ($size==0)
                    {
                    $lastWert=$status;
                    $lastTime=time();
                    }
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
    						//echo "            *********************** keine Aenderung, erster Eintrag im Logfile, so sollte es sein.\n";
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
                        else echo "      *************** hier sollte niemand vorbeikommen, sonst fehlen die Zeiten.\n";
						}	
					$timeok=$wert["TimeStamp"];
		   	   		//echo "       Wert : ".str_pad($wert["Value"],12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])." mit Abstand von ".str_pad($wert["Duration"],12," ",STR_PAD_LEFT)."\n";
		   	   		//echo "       Wert : ".str_pad(($wert["Value"] ? "Ein" : "Aus"),12," ",STR_PAD_LEFT)." vom ".date("d.m H:i:s",$wert["TimeStamp"])."\n";
                    $lastTime=$wert["TimeStamp"]; $lastWert=$wert["Value"];
		   	   		}
                $dauer=$this->Dauer($lastTime,$maxend,$maxend);
                echo "Dauer ".$this->MinOrHoursOrDays($dauer)."  Check ".(($onTime+$offTime+$dauer)/60/24)."\n";
                If ($lastWert==true) $onTime += $dauer; 
                If ($lastWert==false) $offTime += $dauer;                
				$result1[IPS_GetName($oid)]["OID"]=$oid;
                $available=round((1-($offTime/($onTime+$offTime)))*100,1);
                echo "   -> Gesamtauswertung ".IPS_GetName($oid)." ontime $onTime offtime $offTime Availability $available %.  Check ".(($onTime+$offTime)/60/24)."\n";
                $result1[IPS_GetName($oid)]["AVAILABILITY"]=$available;
                $result1[IPS_GetName($oid)]["ONTIME"]=$this->MinOrHoursOrDays($onTime);
                $result1[IPS_GetName($oid)]["OFFTIME"]=$this->MinOrHoursOrDays($offTime);
                echo "   -> Gesamtauswertung ".IPS_GetName($oid)." ontime ".$result1[IPS_GetName($oid)]["ONTIME"]." offtime ".$result1[IPS_GetName($oid)]["OFFTIME"]." Availability ".$available."%.\n";
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
            $PrintHtml.='<tr><td align="right" colspan="4"><font size="-1">last update on '.date("m.d.Y H:i:s").'</font></td></tr>';    
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

    /****************************************************************************************
     * 
     * getLoggedValues, nur Variablen werden als Children zugelassen
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

	/**
	 * @public
	 *
	 * liest systeminfo aus und speichert die relevanten Daten als Register
	 *
	 */
	 function SystemInfo($debug=false)
	 	{

		$HostnameID   		= IPS_GetObjectIdByName("Hostname", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
		$SystemNameID		= IPS_GetObjectIdByName("Betriebssystemname", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */		
		$SystemVersionID	= IPS_GetObjectIdByName("Betriebssystemversion", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$HotfixID			= IPS_GetObjectIdByName("Hotfix", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$MemoryID			= IPS_GetObjectIdByName("Memory", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	

		$ExternalIP			= IPS_GetObjectIdByName("ExternalIP", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		
		$UptimeID			= IPS_GetObjectIdByName("IPS_UpTime", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		$VersionID			= IPS_GetObjectIdByName("IPS_Version", $this->categoryId_SysInfo); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */	
		
        /* zusaetzlich Table mit IP Adressen auslesen und in einem html Table darstellen */

        $ipTableHtml        = IPS_GetObjectIdByName("TabelleGeraeteImNetzwerk", $this->categoryId_SysInfo);     // ipTable am Schluss anlegen
        $sumTableHtmlID     = IPS_GetObjectIdByName("SystemInfoOverview", $this->categoryId_SysInfo);           // obige Informationen als kleine Tabelle erstellen

		$result=array();	/* fuer Zwischenberechnungen */
		$results=array();
		$results2=array();
	
		$PrintSI="";
		$PrintLines="";		
		
		exec('systeminfo',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden */
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
		if ($debug) echo "Ausgabe direkt:\n".$PrintLines."\n";		

		//print_r($results);
		//print_r($results2);
		//echo $PrintSI;
		
		//$IPAdresse=$this->whatismyIPaddress2();
		//$results["ExterneIP"]=$IPAdresse;
		$IPAdresse=$this->whatismyIPaddress1()[0]["IP"];
		if (GetValue($ExternalIP) !== $IPAdresse) SetValue($ExternalIP,$IPAdresse);
		$results["ExterneIP"]=$IPAdresse;

		$ServerUptime=date("D d.m.Y H:i:s",IPS_GetKernelStartTime());
		$results["IPS_UpTime"]=$ServerUptime;

		$ServerVersion=IPS_GetKernelVersion();
		$results["IPS_Version"]=$ServerVersion;
		
		SetValue($HostnameID,$results["Hostname"]);
		SetValue($SystemNameID,$results["Betriebssystemname"]);
		SetValue($SystemVersionID,trim(substr($results["Betriebssystemversion"],0,strpos($results["Betriebssystemversion"]," "))));
		SetValue($HotfixID,trim(substr($results["Hotfix(es)"],0,strpos($results["Hotfix(es)"]," "))));
		SetValue($UptimeID,$ServerUptime);
		SetValue($VersionID,$ServerVersion);
		SetValue($MemoryID,$results["Verfuegbarer physischer Speicher"]." von ".$results["Gesamter physischer Speicher"]." verfuegbar. ".$results["Virtueller Arbeitsspeicher"]." Virtualisiert.");

        /* ipTable noch zusaetlich beschreiben */

        $macTable=$this->get_macipdnsTable();

		$collumns=["index","IP","DNSname","Shortname","IP_Adresse","Hostname"];
		$str=$this->writeIpTable($macTable,$collumns);
        SetValue($ipTableHtml,$str);

        $html=true;
        $sumTableHtml=$this->readSystemInfo($html);             // die Systeminfo als html Tabelle zusammenstellen
        SetValue($sumTableHtmlID, $sumTableHtml);

		return $results;
		}	

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

	
	/*******************
	 *
	 * Die von SystemInfo aus dem PC (via systeminfo) ausgelesenen und gespeicherten Daten werden für die Textausgabe formatiert und angereichert
	 *
	 ***************************************************/
									
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
		$Version=explode(".",getValue($SystemVersionID));
		//print_r($Version);
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
			default: $Codename=$Version[2];break;
			}			
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
		$PrintLn.="   ".str_pad("IPS Uptime",30)." = ".str_pad(GetValue($UptimeID),30)."   (".date("d.m H:i",IPS_GetVariable($UptimeID)["VariableChanged"]).") \n";
		$PrintLn.="   ".str_pad("IPS Version",30)." = ".str_pad(GetValue($VersionID),30)."   (".date("d.m H:i",IPS_GetVariable($VersionID)["VariableChanged"]).") \n";

        $PrintHtml .= '<tr><td>Hostname</td><td>'.GetValue($HostnameID).'</td><td></tr>';
        $PrintHtml .= '<tr><td>Betriebssystem Name</td><td>'.GetValue($SystemNameID).'</td><td></tr>';
        $PrintHtml .= '<tr><td>Betriebssystem Version</td><td>'.GetValue($SystemVersionID).'</td><td></tr>';
        $PrintHtml .= '<tr><td>Betriebssystem Codename</td><td>'.$Codename.'</td><td></tr>';
        $PrintHtml .= '<tr><td>Anzahl Hotfix</td><td>'.GetValue($HotfixID).'</td><td></tr>';
        $PrintHtml .= '<tr><td>External IP Adresse</td><td>'.GetValue($ExternalIP).'</td><td></tr>';
        $PrintHtml .= '<tr><td>IPS Uptime</td><td>'.GetValue($UptimeID).'</td><td></tr>';
        $PrintHtml .= '<tr><td>IPS Version</td><td>'.GetValue($VersionID).'</td><td></tr>';
        $PrintHtml.='</table>';            
        
		if ($html) return ($PrintHtml);
        else return ($PrintLn);
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
    							include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
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
    							include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\Stromheizung\IPSHeat.inc.php");
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
     *
     * wird von construct bereits aufgerufen
	 *
	 */
	function create_macipTable($subnet,$printHostnames=false)
		{
		$subnetok=substr($subnet,0,strpos($subnet,"255"));
		//echo "Finde in ".$subnet." den ersten 255er :".strpos($subnet,"255")."\n";
		$ergebnis=""; $print_table="";
		$ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeinde Definitionen */
		unset($catch);
		exec('arp -a',$catch);
		foreach($catch as $line)
   			{
   			if (strlen($line)>0)
				{
			   	$result=trim($line);
	   			$result1=substr($result,0,strpos($result," ")); /* zuerst IP Adresse */
		   		$result=trim(substr($result,strpos($result," "),100));
	   			$result2=substr($result,0,strpos($result," ")); /* danach MAC Adresse */
		   		$result=trim(substr($result,strpos($result," "),100));
				if ($result1=="10.0.255.255") { break; }
				//echo "*** ".$line." Result:  ".$result1." SubnetOk: ".$subnetok." SubNet: ".$subnet." ".strlen($result1)."\n";
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
		  }
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
	 * Alle IP Adressen und Domain Names herausfinden mit nslookup
	 *
	 */

	function get_macipdnsTable()
		{
        $ipTable=array();
        $macTable=array();
        foreach ($this->mactable as $mac => $ip)
            {
            //echo "     ".$mac."   ".$ip."   \n";
            unset($catch);
            $ergebnis=array();
            exec('nslookup '.$ip,$catch);
            foreach ($catch as $entry)
                {
                $entries=explode(":",$entry);
                if (sizeof($entries)>1)
                    {
                    $ergebnis[$entries[0]]=$entries[1];
                    }
                }
            //print_r($ergebnis);
            //print_r($entries);
            //print_r($catch);

            $macAdresse=strtoupper($mac);
            if (isset($ergebnis["Name"]) )
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
	 * Initialisierung des OperationCenter Objektes
	 *
	 */
	function find_HostNames()
		{
		$ergebnis="";
		$ipadressen=LogAlles_Hostnames();   /* lange Liste in Allgemeinde Definitionen */
		$manufacturers=LogAlles_Manufacturers();   /* lange Liste in Config */
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
    	   if (isset ($manufacturers[$manuID])==true) { $manuID=$manufacturers[$manuID]; }
		   echo "   ".$mac."   ".str_pad($ip,12)." ".str_pad($result,12)." ".str_pad($result2,20)."  ".$manuID."\n";
		   $ergebnis.="   ".$mac."   ".str_pad($ip,12)." ".str_pad($result,12)." ".str_pad($result2,20)."  ".$manuID."\n";
		   }
		echo "\n\n";
		return ($ergebnis);
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
        $actionButton[$SysPingSortTableID]["Monitor"]["SysPingTable"]=true;
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


	/******************************
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
            $picVerzeichnisFull=IPS_GetKernelDir()."webfront/".$picVerzeichnis;
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

	/******************************
	 *
	 * es wird die Visualisierung für die Snapshots pro Kamera erstellt, muss in IPSCam eingeschaltet sein
     *
     *
	 */
	function showCamSnapshots($camConfig=array(), $debug=false)
		{
        if ($debug) echo "showCamSnapshots aufgerufen für insgesamt ".sizeof($camConfig)." Kameras.\n";            
        $status=false;  // Rückmeldecode
        if (isset($this->installedModules["IPSCam"]))
            {
            if (sizeof($camConfig)==0)
                {
                IPSUtils_Include ("IPSCam_Constants.inc.php",      "IPSLibrary::app::modules::IPSCam");
                IPSUtils_Include ("IPSCam_Configuration.inc.php",  "IPSLibrary::config::modules::IPSCam");
                $camConfig = IPSCam_GetConfiguration();			
                //if ($debug) echo "Kein Configarray als Übergabeparameter, sich selbst eines überlegen: ".json_encode($camConfig)."\n";
                }
            if ($debug) echo "IPSCam installiert. showCamSnapshots aufgerufen mit ".json_encode($camConfig).".\n";            
            $categoryIdCams     		= CreateCategory('Cams',    $this->CategoryIdData, 20);
        
            /* Zielverzeichnis für Anzeige ermitteln */
            $picVerzeichnis="user/OperationCenter/AllPics/";
            $picVerzeichnisFull=IPS_GetKernelDir()."webfront/".$picVerzeichnis;
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
            }
        return ($status);
		}


	/******************************
	 *
	 * es werden von den ftp Verzeichnissen ausgewählte Dateien in das Webfront/user verzeichnis für die Darstellung im Webfront kopiert
     *  Zielverzeichnis:  IPS_KernelDir/webfront/user/OperationCenter/Cams/"Cam_name"/
	 * 
	 */
	
	function showCamCaptureFiles($ocCamConfig,$debug=false)
		{
        $status=false;
        if ($this->moduleManagerCam)
            {
            /*
            $WFC10Cam_Path        	 = $this->moduleManagerCam->GetConfigValue('Path', 'WFC10');
            $categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path."_Capture");
            if ($debug) echo "showCamCaptureFiles: started, Webfont Path of IPSCam Module in $WFC10Cam_Path\n";
            */
            $categoryId_WebFrontAdministrator=$this->getWebfrontID();
            $childrens = IPS_GetChildrenIDs($categoryId_WebFrontAdministrator);
            $camsFound=array();
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
                        $imgVerzeichnisFull=IPS_GetKernelDir()."webfront/".$imgVerzeichnis;
                        $imgVerzeichnisFull = str_replace('\\','/',$imgVerzeichnisFull);
                        //echo "Quellverzeichnis : ".$verzeichnis."   Zielverzeichnis : ".$imgVerzeichnisFull."\n";
                        if ( is_dir ( $imgVerzeichnisFull ) == false ) $this->dosOps->mkdirtree($imgVerzeichnisFull);
                    
                        $picdir=$this->readdirToArray($verzeichnis,false,-500);
                        if ($debug) 
                            {
                            echo "Files aus dem Verzeichnis $verzeichnis werden kopiert.\n";
                            //print_r($picdir);         // doppelte Ausgabe, nicht notwendig
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
        return ($status);
		}	

    /*                     */
    
    public function getWebfrontID($debug=false)
        {
        $WFC10Cam_Path        	 = $this->moduleManagerCam->GetConfigValue('Path', 'WFC10');
        if ($debug) echo "getWebfrontID from Webfont Path of IPSCam Module in $WFC10Cam_Path\n";
        return (CreateCategoryPath($WFC10Cam_Path."_Capture"));
        }

    /*                     */

    public function createVariablePictureField($categoryIdCapture)
        {
        return ( CreateVariable("pictureField",   3 /*String*/,  $categoryIdCapture, 50 , '~HTMLBox'));            
        }

    /*                     */
    
    public function getPictureFieldIDs($ocCamConfig, $debug=false)
        {
        $pictureFieldIDs=array(); $index=0;
        if ($this->moduleManagerCam)
            {
            $categoryId_WebFrontAdministrator=$this->getWebfrontID(); 
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

    /* get Category IDS like they are instances
     *
     *
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


    /**************************
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
	function correctDirName($verzeichnis)
		{
		$len=strlen($verzeichnis); $pos1=strrpos($verzeichnis,"\\"); $pos2=strrpos($verzeichnis,"/");
		if ( ($pos1) && ($pos1<($len-1)) ) $verzeichnis .= "\\";
		if ( ($pos2) && ($pos2<($len-1)) ) $verzeichnis .= "/";		
		return ($verzeichnis);
		}
    */


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
	
	public function readdirToArray($dir,$recursive=false,$newest=0)
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

	function CopyScripts()
		{
		/* sicherstellen dass es das Dropbox Verzeichnis auch gibt */
		echo "CopyScripts, relevante Configuration mit Dropbox Verzeichnissen:\n";
		print_r($this->oc_Setup);
		$DIR_copyscriptsdropbox = $this->oc_Setup['DropboxDirectory'].IPS_GetName(0).'/';

		$this->dosOps->mkdirtree($DIR_copyscriptsdropbox);

		$count=0;

		$alleSkripte = IPS_GetScriptList();
		//print_r($alleSkripte);

		/* ein includefile mit allen Dateien erstellen, als Inhaltsverzeichnis */
		$includefile='<?'."\n".'$fileList = array('."\n";

		echo "Alle Scriptfiles werden vom IP Symcon Scriptverzeichnis auf ".$DIR_copyscriptsdropbox." kopiert und in einen Dropbox lesbaren Filenamen umbenannt.\n";
		echo "\n";

		foreach ($alleSkripte as $value)
			{
			/*
			 *  Script Files auf die Dropbox kopieren 
			 */
			$filename=IPS_GetScriptFile($value);	/* von hier wird kopiert -> source */
			$name=IPS_GetName($value);
			$trans = array("," => "", ";" => "", ":" => "", "/" => ""); /* falsche zeichen aus filenamen herausnehmen */
			$name=strtr($name, $trans);
			$destination=$name."-".$value.".php";		/* name der als Ziel Filename verwendet wird */
			//echo "-Copy File: ".IPS_GetKernelDir().'scripts/'.$filename." : ".$name." : ".$DIR_copyscriptsdropbox.$destination."\n";
			copy(IPS_GetKernelDir().'scripts/'.$filename,$DIR_copyscriptsdropbox.$destination);
			
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
			echo $check." ".$count.": OID: ".$value." Verzeichnis ".$filename."   ".$destname."\n";
			$includefile.='\''.$destname.'\','."\n";
			$count+=1;
			}

		$includefile.=');'."\n".'?>';

		echo "\n";
		echo "-------------------------------------------------------------\n\n";
		echo "Insgesamt ".$count." Scripts kopiert.\n";
		return ($includefile);
		}

	/*
	 * Statusinfo von AllgemeineDefinitionen in einem File auf der Dropbox abspeichern
	 *
	 */

	function FileStatus($filename="", $debug=false)
		{
        if ($debug)
            {
            echo "OperationCenter::FilesStatus aufgerufen. Filename \"$filename\"\n";
		    print_r($this->oc_Setup);
            }
		/* sicherstellen dass es das Dropbox Verzeichnis auch gibt */
		$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';
        if ($debug)echo "Name Directory für Ergebnisse von send_status ist $DIR_copystatusdropbox.\n";
		$this->dosOps->mkdirtree($DIR_copystatusdropbox);
        if ($debug) 
            {
            echo "===================================\n";
            echo "Send_status aktuelle Werte berechnen:\n\n";
            }
        /* Aufruf send_status aktuell/historisch, Zeit für execute time Berechnung, Debug */ 
		$event1=date("D d.m.y h:i:s")." Die aktuellen Werte aus der Hausautomatisierung: \n\n".send_status(true, 0, $debug).
			"\n\n************************************************************************************************************************\n";
        if ($debug) 
            {
            echo "===================================\n";
            echo "Send_status historische Werte berechnen:\n";
            }
		$event2=date("D d.m.y h:i:s")." Die historischen Werte aus der Hausautomatisierung: \n\n".send_status(false, 0, $debug).
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

	function FileStatusDir()
		{
		echo "FileStatusDir: Konfiguration:\n";
		print_r($this->oc_Setup);
		echo "Verzeichnis für Status Files:\n";
		$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';	
		echo $DIR_copystatusdropbox."\n";   
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
		if ( isset($this->oc_Setup['DropboxStatusMaxFileCount']) == true )
			{
			echo "Wenn mehr als ".$this->oc_Setup['DropboxStatusMaxFileCount']." Status Dateien gespeichert, diese loeschen.\n";
			if ( $this->oc_Setup['DropboxStatusMaxFileCount'] > 0)
				{
				$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';
	   			$statusdir=$this->readdirToArray($DIR_copystatusdropbox);
				if ($statusdir !== false)	// Verzeichnis vorhanden
					{
					rsort($statusdir);
					$i=0; 			   
					foreach ($statusdir as $index => $name)
						{
						if ($i > $this->oc_Setup['DropboxStatusMaxFileCount'])
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
		$DIR_copystatusdropbox = $this->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';	
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
        $backupDrive=$this->dosOps->correctDirName($configuration["Directory"],$debug);
        if (isset($configuration["Drive"]))
            {
            if ($debug) echo "es gibt einen Drive Letter. Backup Konfiguration erweitern.\n";
            $backupDrive = $configuration["Drive"].":".$backupDrive;
            }
        if (is_dir($backupDrive)) if ($debug) echo "Backup Drive vorhanden.\n";
        else
            {
            $location = $configuration["Network"];
            $user = $configuration["User"];
            $pass = $configuration["Password"];
            $letter = $configuration["Drive"];

            // Map the drive
            if ($debug) echo "Map the drive system(net use ".$letter.": \"".$location."\" ".$pass." /user:".$user." /persistent:no>nul 2>&1)\n";
            system("net use ".$letter.": \"".$location."\" ".$pass." /user:".$user." /persistent:no>nul 2>&1");
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
     *
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
                else die ("Backup nicht möglich, kein Laufwerk zum Speichern vorhanden.");
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
        if ($debug) echo "getBackupLogTable aufgerufen.\n";
        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist
        $dir=$this->readdirToArray($BackupDrive);
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

        $BackupDrive=$this->getBackupDrive();
        $BackupDrive = $this->dosOps->correctDirName($BackupDrive);			// sicherstellen das ein Slash oder Backslash am Ende ist

        /* alle Verzeichnisse im Backup */
        $BackupDirs=$this->getBackupDirectories();        
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
            $backupSourceDirs=array("db","media","modules","scripts","webfront","settings.json");
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
 *  MoveFiles
 *  MoveCamFiles
 *
 *
 **************************************************************************************************************************/

class LogFileHandler extends OperationCenter
	{

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
 * DeviceManagement
 * ================ 
 *
 * __construct          
 *      HMIs aus dem HomeMatic Inventory Handler
 *      HomematicAddressesList 
 *      HomematicSerialNumberList
 *
 * HardwareStatus                       Statusinfo von Hardware, auslesen der Sensoren und Alarm wenn laenger keine Aktion.
 * Verwenden gemeinsames Array $HomematicSerialNumberList:
 * getHomematicSerialNumberList		erfasst alle Homematic Geräte anhand der Seriennumme und erstellt eine gemeinsame liste die mit anderen Funktionen erweiterbar ist
 * get_ActionButton                     Standardfunktion für die Activities aus dem Webfront
 * getHomematicAddressList
 *
 * addHomematicSerialList_Typ		die Homematic Liste wird um weitere Informationen erweitert:  Typ
 * addHomematicSerialList_RSSI
 * addHomematicSerialList_DetectMovement
 *
 * writeHomematicSerialNumberList	Ausgabe der Liste
 * tableHomematicSerialNumberList
 *
 *                      "Type"    = $DeviceManager->getHomematicType($instanz);           wird für Homematic IPS Light benötigt 
 *                      "Device"  = $DeviceManager->getHomematicDeviceType($instanz);     wird für CustomComponents verwendet, gibt als echo auch den Typ aus 
 *                      "HMDevice"= $DeviceManager->getHomematicHMDevice($instanz);
 *
 * getHomematicDeviceList
 * getHomematicType
 * HomematicDeviceType
 * getHomematicDeviceType
 * getHomematicHMDevice
 * getFS20Type
 * getFS20DeviceType
 *
 * HomematicFehlermeldungen
 * getHomematicDeviceList
 *
 *
 **************************************************************************************************************************/

class DeviceManagement
	{

	var $CategoryIdData       	= 0;
	var $archiveHandlerID     	= 0;

    private $debug                  = false;            /* wenig Debug Info ausgeben */
    private $dosOps;                        /* verwendete andere Klassen */
    private $systemDir;              // das SystemDir, gemeinsam für Zugriff zentral gespeichert

	var $log_OperationCenter  	= array();
	var $oc_Configuration     	= array();
	var $oc_Setup			    = array();			/* Setup von Operationcenter, Verzeichnisse, Konfigurationen */

	var $installedModules     	= array();
	
	var $HomematicSerialNumberList	= array();
	var $HomematicAddressesList	= array();
	
	var $HMIs = array();							/* Zusammenfassung aller Homatic Inventory module */
    var $HMI_ReportStatusID   = 0;                  /* der HMI_CreateReport wird regelmaessig aufgerufen, diesen auch überwachen. */
	
	/**
	 * @public
	 *
	 * Initialisierung des DeviceManagement Class Objektes
	 *
	 */
	public function __construct($debug=false)
		{
        $this->debug=$debug;
        $this->dosOps = new dosOps();     // create classes used in this class
        $this->systemDir     = $this->dosOps->getWorkDirectory();

		IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");

		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('OperationCenter',$repository);
			}
		$this->CategoryIdData=$moduleManager->GetModuleCategoryID('data');
		$this->installedModules = $moduleManager->GetInstalledModules();

		$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $this->CategoryIdData, 20);
		$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
		$this->log_OperationCenter=new Logging($this->systemDir."Log_OperationCenter.csv",$input);

		$categoryId_DeviceManagement    = IPS_GetObjectIDByName('DeviceManagement',$this->CategoryIdData);
        $this->HMI_ReportStatusID       = IPS_GetObjectIDByName("HMI_ReportStatus",$categoryId_DeviceManagement);
        if ($debug) echo "found $categoryId_DeviceManagement und ".$this->HMI_ReportStatusID."\n";
        
		$this->archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
		
        $this->oc_Configuration = OperationCenter_Configuration();
		$this->oc_Setup = OperationCenter_SetUp();
		
		/* Defaultwerte vergeben, falls nicht im Configfile eingestellt */
		if (isset($this->oc_Setup['DropboxDirectory'])===false) {$this->oc_Setup['DropboxDirectory']='C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/scripts/';}
		if (isset($this->oc_Setup['DropboxStatusDirectory'])===false) {$this->oc_Setup['DropboxStatusDirectory']='C:/Users/Wolfgang/Dropbox/PrivatIPS/IP-Symcon/Status/';}
		if (isset($this->oc_Setup['CONFIG'])===false) 
			{
			$this->oc_Setup['CONFIG']= array("MOVELOGS"  => true,"PURGELOGS" => true,"PURGESIZE"  => 10,);
			}		
		else
			{
			if (isset($this->oc_Setup['CONFIG']['MOVELOGS'])===false) {$this->oc_Setup['CONFIG']['MOVELOGS']=true;}
			if (isset($this->oc_Setup['CONFIG']['PURGELOGS'])===false) {$this->oc_Setup['CONFIG']['PURGELOGS']=true;}
			if (isset($this->oc_Setup['CONFIG']['PURGESIZE'])===false) {$this->oc_Setup['CONFIG']['PURGESIZE']=10;}
			}
        if ($debug) 
            { 
            echo "Aufbau des OperationCenter Setups. Fehlende Werte in der Konfiguration ersetzen.\n";
            print_r($this->oc_Setup);
            }

        $this->getHomematicSerialNumberList();

        if ($debug) echo "ModulHandling aufrufen:\n";
		$modulhandling = new ModuleHandling();
		$this->HMIs=$modulhandling->getInstances('HM Inventory Report Creator');	

        if ($debug) echo "getHomematicAddressList aufrufen:\n";
        $this->HomematicAddressesList=$this->getHomematicAddressList(false,$debug);         // benötigt die HMIs, kommt in einen eigenen Timer
        if ($debug) echo "DeviceManagement Modul vollständig initialisiert.\n";
		}
		
    /****************************************************************************************************************/


	/*
	 * Statusinfo von Hardware, auslesen der Sensoren und Alarm wenn laenger keine Aktion.
	 *
	 * Parameter:
	 * -----------
	 * Default: Ausgabe als Textfile für zB send_status, gibt alle Geräte der Reihe nach als text aus. 
	 * Wenn Parameter true dann Ausgabe als Array mit den Fehlermeldungen wenn Geräte über längeren Zeitraum nicht erreichbar sind
	 *
	 */

	function HardwareStatus($text=false)
		{
		$resultarray=array(); $index=0;
		$resulttext="";
        $oldstyle=false;
		//print_r($this->installedModules);
		if (isset($this->installedModules["EvaluateHardware"])==true)
			{
			/* es gibt nur mehr eine Instanz für die Evaluierung der Hardware und die ist im Modul EvaluateHardware */
			
			//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
            IPSUtils_Include ("EvaluateHardware_Devicelist.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            $componentHandling=new ComponentHandling();
            echo "Geräte mit getComponent suchen, geht jetzt mit HarwdareList und DeviceList.\n";
            //$result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "HUMIDITY"],"Install");
            $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_TEMPERATURE","REGISTER" => "TEMPERATURE"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $count=(sizeof($result));				
            //echo "Insgesamt $count Register für die Temperature Component Installation gefunden.\n";
            //foreach ($result as $oid) echo "   ".$oid."    ".IPS_GetName($oid)."   \n";           // wenn kein Install
            $resulttext.="Alle Temperaturwerte ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                //echo "   ".json_encode($Key)."   \n";
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                //echo "   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                $resulttext.= "\n"; 
                $this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                /*if ((time()-IPS_GetVariable($Key["COID"])["VariableChanged"])>(60*60*24*2)) 
                    {  
                    $result[$index]["Name"]=$Key["Name"];
                    $result[$index]["OID"]=$Key["COID"];
                    $index++;					
                    $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                    $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');             
                    }*/
                }

            $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_HUMIDITY","REGISTER" => "HUMIDITY"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $count=(sizeof($result));				
            $resulttext.="Alle Feuchtigkeitswerte ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueIfFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                }

            $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_CLIMATE","REGISTER" => "BRIGHTNESS"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $result += $componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "BRIGHTNESS"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $count=(sizeof($result));				
            $resulttext.="Alle Helligkeitswerte ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueIfFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                }

            $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_MOTION","REGISTER" => "MOTION"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $count=(sizeof($result));				
            $resulttext.="Alle Bewegungsmelder ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                }

            $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_CONTACT","REGISTER" => "CONTACT"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $count=(sizeof($result));				
            $resulttext.="Alle Kontakte ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                }

            $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_METER_POWER","REGISTER" => "ENERGY"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $count=(sizeof($result));				
            $resulttext.="Alle Energieregister ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                $this->checkVariableChanged($resultarray,$index,$Key);                              // erste zwei Variable als Pointer übergeben                
                }

            $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_THERMOSTAT","REGISTER" => "SET_TEMPERATURE"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $count=(sizeof($result));				
            $resulttext.="Alle Sollwerte Thermostate ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                }

            $result=$componentHandling->getComponent(deviceList(),["TYPECHAN" => "TYPE_ACTUATOR","REGISTER" => "VALVE_STATE"],"Install");                        // bei Devicelist brauche ich TYPECHAN und REGISTER, ohne Install werden nur die OIDs ausgegeben
            $count=(sizeof($result));				
            $resulttext.="Alle Stellwerte Aktuatoren ausgeben ($count):\n";            
            foreach ($result as $Key) 
                {
                $resulttext.= "   ".str_pad($Key["Name"],50)." = ".str_pad(GetValueFormatted($Key["COID"]),20)."   (".date("d.m H:i",IPS_GetVariable($Key["COID"])["VariableChanged"]).") ";
                $resulttext.= "\n";                
                }

            if ($oldstyle)
                {
                IPSUtils_Include ("EvaluateHardware_include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
                $Homematic = HomematicList();
                $FS20= FS20List();
                
                $resulttext.="Alle Temperaturwerte ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    $homematicerror=false;
                    /* alle Homematic Temperaturwerte ausgeben */
                    if (isset($Key["COID"]["TEMPERATURE"])==true)
                        {
                        $oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).") ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2)) $homematicerror=true;
                        }
                    elseif ( (isset($Key["COID"]["MOTION"])==true) )	
                        {
                        }				
                    elseif (isset($Key["COID"]["HUMIDITY"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["RSSI_DEVICE"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["STATE"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["CURRENT"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["CURRENT_ILLUMINATION"])==true)
                        {
                        }	
                    elseif (isset($Key["COID"]["PRESS_LONG"])==true)
                        {
                        }	
                    elseif (isset($Key["COID"]["DIRECTION"])==true)
                        {
                        }
                    elseif (isset($Key["COID"]["ACTUAL_TEMPERATURE"])==true)
                        {
                        $oid=(integer)$Key["COID"]["ACTUAL_TEMPERATURE"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2)) $homematicerror=true;
                        }
                    elseif (sizeof($Key["COID"])==0) 
                        {
                        }																								
                    else
                        {
                        $resulttext.="**********Homematic Temperatur Geraet unbekannt : ".str_pad($Key["Name"],30)."\n";					
                        print_r($Key);
                        }
                    if ($homematicerror == true)		
                        {
                        $result[$index]["Name"]=$Key["Name"];
                        $result[$index]["OID"]=$oid;
                        $index++;					
                        $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                        $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                        }
                    }

                $FHT = FHTList();
                foreach ($FHT as $Key)
                    {
                    /* alle FHT Temperaturwerte ausgeben */
                    if (isset($Key["COID"]["TemeratureVar"])==true)
                        {
                        $oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;	
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                $resulttext.="Alle Feuchtigkeitswerte ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Feuchtigkeitswerte ausgeben */
                    if (isset($Key["COID"]["HUMIDITY"])==true)
                        {
                        $oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                $resulttext.="Alle Bewegungsmelder ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Bewegungsmelder ausgeben */
                    if ( (isset($Key["COID"]["MOTION"])==true) )
                        {
                        /* alle Bewegungsmelder */
                        //print_r($Key);
                        $oid=(integer)$Key["COID"]["MOTION"]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";
                                                
                        /* es kann laenger sein dass keine Bewegung, aber Helligkeitsaenderungen sind immer */	
                        if (isset($Key["COID"]["BRIGHTNESS"]["OID"])==true)
                            {
                            $oid=(integer)$Key["COID"]["BRIGHTNESS"]["OID"];
                            }
                        elseif (isset($Key["COID"]["ILLUMINATION"]["OID"])==true)
                            {
                            $oid=(integer)$Key["COID"]["ILLUMINATION"]["OID"];
                            }					 
                        else	
                            {
                            echo "Bewegungsmelder ohne Helligkeitssensor gefunden:\n";
                            print_r($Key);						
                            }		
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }	
                        }
                    }
                    
                $resulttext.="Alle Kontakte ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    /* alle Homematic Kontakte ausgeben */
                    if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["LOWBAT"])==true) )
                        {
                        //print_r($Key);
                        $oid=(integer)$Key["COID"]["STATE"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }
                    
                $resulttext.="Alle Energiewerte ausgeben :\n";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Energiesensoren ausgeben */
                    if ( (isset($Key["COID"]["VOLTAGE"])==true) )
                        {
                        /* alle Energiesensoren */

                        $oid=(integer)$Key["COID"]["ENERGY_COUNTER"]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";						
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                $pad=50;
                $resulttext.="Aktuelle Heizungswerte ausgeben:\n";
                $varname="SET_TEMPERATURE";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Stellwerte ausgeben */
                    if ( (isset($Key["COID"][$varname])==true) && !(isset($Key["COID"]["VALVE_STATE"])==true) )
                        {
                        /* alle Stellwerte der Thermostate */
                        //print_r($Key);

                        $oid=(integer)$Key["COID"][$varname]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";						
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                $varname="SET_POINT_TEMPERATURE";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Stellwerte ausgeben */
                    if ( (isset($Key["COID"][$varname])==true) && !(isset($Key["COID"]["VALVE_STATE"])==true) )
                        {
                        /* alle Stellwerte der Thermostate */
                        //print_r($Key);
                        $oid=(integer)$Key["COID"][$varname]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";						
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                foreach ($FHT as $Key)
                    {
                    /* alle FHT Temperaturwerte ausgeben */
                    if (isset($Key["COID"]["TargetTempVar"])==true)
                        {
                        $oid=(integer)$Key["COID"]["TargetTempVar"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        }
                    }			
                    
                $resulttext.="Aktuelle Heizungs-Aktuatorenwerte ausgeben:\n";
                $varname="VALVE_STATE";
                foreach ($Homematic as $Key)
                    {
                    /* Alle Homematic Stellwerte ausgeben */
                    if ( (isset($Key["COID"][$varname])==true) )
                        {
                        /* alle Stellwerte der Thermostate */
                        //print_r($Key);
                        if ( (isset($Key["COID"]["LEVEL"])==true) ) $oid=(integer)$Key["COID"]["LEVEL"]["OID"];
                        else $oid=(integer)$Key["COID"][$varname]["OID"];
                        $variabletyp=IPS_GetVariable($oid);
                        if ($variabletyp["VariableProfile"]!="")
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        else
                            {
                            $resulttext.="   ".str_pad($Key["Name"],$pad)." = ".str_pad(GetValue($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                            }
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";						
                        if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                            {
                            $result[$index]["Name"]=$Key["Name"];
                            $result[$index]["OID"]=$oid;
                            $index++;
                            $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                            }
                        }
                    }

                foreach ($FHT as $Key)
                    {
                    /* alle FHT Temperaturwerte ausgeben */
                    if (isset($Key["COID"]["PositionVar"])==true)
                        {
                        $oid=(integer)$Key["COID"]["PositionVar"]["OID"];
                        $resulttext.="   ".str_pad($Key["Name"],30)." = ".str_pad(GetValueFormatted($oid),30)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")  ";
                        $resulttext.="   ".$Key["Device"]."  ".explode(":",$Key["Adresse"])[0]."  ";
                        $resulttext.="\n";					
                        }
                    if ((time()-IPS_GetVariable($oid)["VariableChanged"])>(60*60*24*2))
                        {
                        $result[$index]["Name"]=$Key["Name"];
                        $result[$index]["OID"]=$oid;
                        $index++;
                        $this->log_OperationCenter->LogMessage('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                        $this->log_OperationCenter->LogNachrichten('Homematic Gerät '.$Key["Name"].' meldet sich nicht');
                        }					
                    }
                }               // oldStyle HardwareStatus
			}
		
		if ($text==true) return($resulttext); 
		else return($resultarray);
		}

    /* einheitliche Überprüfung ob schon länger keine Änderung mehr war */

    private function checkVariableChanged(&$result,&$index,$Key)
        {
        if ((time()-IPS_GetVariable($Key["COID"])["VariableChanged"])>(60*60*24*2)) 
            {           
            $result[$index]["Name"]=$Key["Name"];
            $result[$index]["OID"]=$Key["COID"];
            $index++;					
            $this->log_OperationCenter->LogMessage('HardwareStatus Gerät '.$Key["Name"].' meldet sich nicht');
            $this->log_OperationCenter->LogNachrichten('HardwareStatus Gerät '.$Key["Name"].' meldet sich nicht');
            return (false);
            }
        return (true);
        }

	/********************************************************************
	 *
	 * erfasst alle Homematic Geräte anhand der Seriennummer und erstellt eine gemeinsame liste 
     * wird bei construct bereits gestartet als gemeinsames Datenobjekt
	 *
	 *****************************************************************************/

	function getHomematicSerialNumberList($debug=false)
		{
		$guid = "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}";
		//Auflisten
		$alleInstanzen = IPS_GetInstanceListByModuleID($guid);
		if ($debug)
			{
			echo "\nHomematic Instanzen: ".sizeof($alleInstanzen)." \n";
			echo "Werte geordnet und angeführt nach Instanzen, es erfolgt keine Zusammenfassung auf Geräte/Seriennummern.\n";
			echo "Children der Instanzen werden nur angeführt wenn die Zeit ungleich 0 ist.\n\n";
			}
		$serienNummer=array();
		foreach ($alleInstanzen as $instanz)
			{
			$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanz)['ConnectionID']);
			switch (IPS_GetProperty($instanz,'Protocol'))
				{
				case 0:
					$protocol="Funk";
					break;
				case 1:
				    $protocol="Wired";
    				break;
	    		case 2:
		    		$protocol="IP";
			    	break;
                default:
	    			$protocol="Unknown";
				break;
				}
			$HM_Adresse=IPS_GetProperty($instanz,'Address');
			$result=explode(":",$HM_Adresse);
			$sizeResult=sizeof($result);
			//print_r($result);
			if ($debug) echo str_pad(IPS_GetName($instanz),40)." ".$instanz." ".$HM_Adresse." ".str_pad($protocol,6)." ".str_pad(IPS_GetProperty($instanz,'EmulateStatus'),3)." ".$HM_CCU_Name."\n";
			if (isset($serienNummer[$HM_CCU_Name][$result[0]]))
				{
				$serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]+=1;
				}
			else
				{
				$serienNummer[$HM_CCU_Name][$result[0]]["Anzahl"]=1;
				$serienNummer[$HM_CCU_Name][$result[0]]["Values"]="";
				}
			$serienNummer[$HM_CCU_Name][$result[0]]["Name"]=IPS_GetName($instanz);
			$serienNummer[$HM_CCU_Name][$result[0]]["Protokoll"]=$protocol;
			if ($sizeResult>1)
				{
				$serienNummer[$HM_CCU_Name][$result[0]]["OID:".$result[1]]=$instanz;
				$serienNummer[$HM_CCU_Name][$result[0]]["Name:".$result[1]]=IPS_GetName($instanz);
				}
			else { if ($debug) echo "Fehler mit ".$result[0]."\n"; }			
			$cids = IPS_GetChildrenIDs($instanz);
			if ( isset($serienNummer[$HM_CCU_Name][$result[0]]["Update"]) == true) $update=$serienNummer[$HM_CCU_Name][$result[0]]["Update"];
			else $update=0;
			foreach($cids as $cid)
				{
				$o = IPS_GetObject($cid);
				if (IPS_GetVariable($cid)["VariableChanged"] != 0) 
					{
					if (IPS_GetVariable($cid)["VariableChanged"]>$update) $update=IPS_GetVariable($cid)["VariableChanged"];
					if ($debug) echo "   CID : ".$cid."  ".IPS_GetName($cid)."  ".date("d.m H:i",IPS_GetVariable($cid)["VariableChanged"])."   \n";
					}
				if($o['ObjectIdent'] != "")
					{
					$serienNummer[$HM_CCU_Name][$result[0]]["Values"].=$o['ObjectIdent']." ";
					}
		    	}
			$serienNummer[$HM_CCU_Name][$result[0]]["Update"] = $update;	
			}
		$this->HomematicSerialNumberList=$serienNummer;
		return ($serienNummer);
		}
	
	/*
	 * Zusammenfassung aller ActionButtons in dieser Klasse
	 *
	 */
	 
	function get_ActionButton()
		{
		$countHMI = sizeof($this->HMIs);
		//echo "Es gibt insgesamt ".$countHMI." SymCon Homematic Inventory Instanzen. Entspricht üblicherweise der Anzahl der CCUs.\n";
	    $ActionButton=array();
		if ($countHMI>0)
	        {
			$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
			foreach ($this->HMIs as $HMI)
	            {
				$CategoryIdHomematicCCU=IPS_GetCategoryIdByName("HomematicInventory_".$HMI,$CategoryIdHomematicInventory);
	            $SortInventoryId = IPS_GetVariableIdByName("Sortieren",$CategoryIdHomematicCCU);
	   			$HomematicInventoryId = IPS_GetVariableIdByName(IPS_GetName($HMI),$CategoryIdHomematicCCU);
	
	            $ActionButton[$SortInventoryId]["DeviceManagement"]["HMI"]=$HMI;
	            $ActionButton[$SortInventoryId]["DeviceManagement"]["HtmlBox"]=$HomematicInventoryId;
	            }            
	        }
		return($ActionButton);
		}
		
	/********************************************************************
	 *
	 * erfasst alle Homematic Geräte anhand der Hardware Addressen und erstellt eine gemeinsame liste mit dem DeviceTyp aus HM Inventory 
     * wird bei construct bereits gestartet als gemeinsames Datenobjekt
     *
     * etwas komplizierte Überwachung der HMI_CreateReport Funktion:
     *
     * wenn länger als einen Tag kein Update dann wird das Update neu angefordert
     * wenn das Update angefordert wurde und noch nicht erfolgt ist gibt es eine Fehlermeldung und es wird nicht mehr angefordert
	 *
	 *****************************************************************************/

	function getHomematicAddressList($callCreateReport=false, $debug=false)
		{
        //if ($debug) echo "DeviceManagement::getHomematicAddressList aufgerufen.\n";
		$countHMI = sizeof($this->HMIs);
		$addresses=array();
		if ($countHMI>0)
			{
			$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
			foreach ($this->HMIs as $HMI)
				{
				$configHMI=IPS_GetConfiguration($HMI);
				if ($debug)             // no information available in configuration wether creation of report as variable is activated
					{
					echo "\n-----------------------------------\n";
					echo "Konfiguration für HMI Report Creator : ".$HMI." (".IPS_GetName($HMI).")\n";
					echo $configHMI."\n";
					}
	            $childrens=IPS_GetChildrenIDs($HMI);
    	        if (isset($childrens[0]))
        	        {
                    if (IPS_GetName($childrens[0]) != "Device Liste") echo "   Name of HMI_CreateReport children not \"Device Liste\", is \"".IPS_GetName($childrens[0])."\"   \n";
                    else
                        {
                        $lastUpdate=IPS_GetVariable($childrens[0])["VariableChanged"];
                        $noUpdate=time()-$lastUpdate;
                        if ( $noUpdate > (48*60*60) )           // Abfragen für Fehlermeldungen etwas entschärft
                            {
                            if ( $noUpdate > (100*60*60) )           // schwerer Fehler, wenn das Update mehrere Tage lang nicht durchgeht
                                {
                                IPSLogger_Err(__file__, "HMI_CreateReport needs update. Last update was ".date("d.m.y H:i:s",$lastUpdate).". CCU might had crashed. Please check.");
                                }
                            else
                                {
                                $message = "HMI_CreateReport needs update. Last update was ".date("d.m.y H:i:s",$lastUpdate).". Do right now.";
                                if ($debug) echo "     $message\n";
                                if ( $noUpdate > (25*60*60) )
                                    {
                                    SetValue($this->HMI_ReportStatusID,$message);
                                    $callCreateReport=true;
                                    }
                                else
                                    {
                                    $hoursnok=round($noUpdate/60/60);
                                    if (GetValue($this->HMI_ReportStatusID)==$message) IPSLogger_Err(__file__, "HMI_CreateReport did not execute for $hoursnok hours. CCU might had crashed. Please check.");
                                    else 
                                        {
                                        SetValue($this->HMI_ReportStatusID,$message);
                                        $callCreateReport=true;
                                        }
                                    }
                                }
                            }
                        else
                            {
                            if ($debug) echo "    HMI_CreateReport wurde zuletzt am ".date("d.m.y H:i:s",$lastUpdate)." upgedatet.\n";
                            SetValue($this->HMI_ReportStatusID,"HMI_CreateReport wurde zuletzt am ".date("d.m.y H:i:s",$lastUpdate)." upgedatet.");
                            }
                        //print_r($childrens);
                        //echo GetValue($childrens[0]);
                        $HomeMaticEntries=json_decode(GetValue($childrens[0]),true);
                        if ( ( ( (is_array($HomeMaticEntries)) && (sizeof($HomeMaticEntries)>0) ) === false) || $callCreateReport)
                            {
                            if ($debug) echo "     HMI_CreateReport($HMI) aufrufen:";   
                            HMI_CreateReport($HMI);  
                            if ($debug) echo "  --> done\n";
                            $HomeMaticEntries=json_decode(GetValue($childrens[0]),true);                                               
                            }                    
                        foreach ($HomeMaticEntries as $HomeMaticEntry)
                            {
                            if (isset($HomeMaticEntry["HM_address"])) 
                                {
                                if ($debug) echo "Addresse: ".$HomeMaticEntry["HM_address"]." Type ".$HomeMaticEntry["HM_device"]." Devicetyp ".$HomeMaticEntry["HM_devtype"]."\n";
                                $addresses[$HomeMaticEntry["HM_address"]]=$HomeMaticEntry["HM_device"];
                                //print_r($HomeMaticEntry);
                                }
                            }
                        }
                	}
	            else echo "HM Inventory, Abspeicherung in einer Variable wurde nicht konfiguriert\n";    
				}					
			}
		if ($debug)
			{
			echo "Ausgabe Adressen versus DeviceType für insgesamt ".sizeof($addresses)." Instanzen (Geräte/Kanäle).\n";	
			print_r($addresses);
			}
		return($addresses);
		}


	/********************************************************************
	 *
	 * Wenn Debug gib die erfasst Liste aller Homematic Geräte mit der Seriennummer als
	 * formatierte liste aus 
	 *
	 * die Homematic Liste wird um weitere Informationen erweitert:  Typ
	 *
	 *****************************************************************************/

	function addHomematicSerialList_Typ($debug=false)
		{
		if ($debug) echo "\nInsgesamt gibt es ".sizeof($this->HomematicSerialNumberList)." Homematic CCUs.\n";
        $serials=array();       /* eventuell doppelte Eintraege finden */
		foreach ($this->HomematicSerialNumberList as $ccu => $geraete)
 			{
			if ($debug) 
				{
				echo "-------------------------------------------\n";
			 	echo "  CCU mit Name :".$ccu."\n";
 				echo "    Es sind ".sizeof($geraete)." Geraete angeschlossen. (Zusammenfassung nach Geräte, Seriennummer)\n";
				}
			foreach ($geraete as $name => $anzahl)
				{
				//echo "\n *** ".$name."  \n";
				//print_r($anzahl);
                if ( isset($serials[$name])==true ) echo "  addHomematicSerialList_Typ, Fehler !!! Doppelter Eintrag in HomematicSerialNumberList für $name.\n";
				else $serials[$name]=$anzahl["Name"];
				$register=explode(" ",trim($anzahl["Values"]));


				if ($debug) echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".str_pad($name,20)." Inst: ".str_pad($anzahl["Anzahl"],4)." Child: ".str_pad(sizeof($register),6)." ";
			    if (sizeof($register)>1) 
				    { /* es gibt Childrens zum analysieren, zuerst gleiche Werte unterdruecken */
				    if ($debug) echo $this->HomematicDeviceType($register,2)."\n";
                    $this->HomematicSerialNumberList[$ccu][$name]["Type"]=$this->HomematicDeviceType($register,2);
					} 
				else
					{	
					if ($debug)
						{ 
						echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".$name." Inst: ".$anzahl["Anzahl"]." Child: ".sizeof($register)." ";
						echo "not installed\n";
						}
					}	
				}
			}
        return($serials);            
		}

	/********************************************************************
	 *
	 * die Homematic Liste der Seriennummern wird um weitere Informationen erweitert:  RSSI
	 *
	 *****************************************************************************/

	function addHomematicSerialList_RSSI($debug=false)
		{
		/* Tabelle vorbereiten, RSSI Werte ermitteln */
	
		IPSUtils_Include ('Homematic_Library.class.php',      'IPSLibrary::app::modules::OperationCenter');

		$homematicManager = new Homematic_OperationCenter();
		$homematicManager->RefreshRSSI($debug);

		$categoryIdHtml     = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.hardware.IPSHomematic.StatusMessages');
		$variableIdRssi       = IPS_GetObjectIDByIdent(HM_CONTROL_RSSI, $categoryIdHtml);
		if ($debug) echo GetValue($variableIdRssi);	// output Table

		$instanceIdList = $homematicManager->GetMaintainanceInstanceList($debug);
		$rssiDeviceList = array();
		$rssiPeerList   = array();
		foreach ($instanceIdList as $instanceId) {
			$variableId = @IPS_GetVariableIDByName('RSSI_DEVICE', $instanceId);
			if ($variableId!==false) {
				$rssiValue = GetValue($variableId);
				if ($rssiValue<>-65535) {
					$rssiDeviceList[$instanceId] = $rssiValue;
					}
				}
			}
		arsort($rssiDeviceList, SORT_NATURAL);

		foreach ($instanceIdList as $instanceId) {
			$variableId = @IPS_GetVariableIDByName('RSSI_PEER', $instanceId);
			if ($variableId!==false) {
				$rssiValue = GetValue($variableId);
				if ($rssiValue<>-65535) {
					$rssiPeerList[$instanceId] = $rssiValue;
					}
				}
			}
			
		if ($debug) echo "\n\nAusgabe RSSI Werte pro Seriennummer (Anreicherung der serienNummer Tabelle):\n\n";	
		foreach($rssiDeviceList as $instanceId=>$value) 
			{
			$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanceId)['ConnectionID']);
			if ($debug) echo "    ".$HM_CCU_Name."     ".IPS_GetName($instanceId)."    ".HM_GetAddress($instanceId)."    ".$value."\n";
			$HMaddress=explode(":",HM_GetAddress($instanceId));
			$this->HomematicSerialNumberList[$HM_CCU_Name][$HMaddress[0]]["RSSI"]=$value;
			}			
		}

	/********************************************************************
	 *
	 * die Homematic Liste der Seriennummern wird um weitere Informationen erweitert:  Detect Movement
	 *
	 *****************************************************************************/

	function addHomematicSerialList_DetectMovement($debug=false)
		{
		if (isset($this->installedModules["DetectMovement"])==true)
			{
			/* DetectMovement */
			IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

			if (function_exists('IPSDetectMovementHandler_GetEventConfiguration')) 		$movement_config=IPSDetectMovementHandler_GetEventConfiguration();
			else $movement_config=array();
			if (function_exists('IPSDetectTemperatureHandler_GetEventConfiguration'))	$temperature_config=IPSDetectTemperatureHandler_GetEventConfiguration();
			else $temperature_config=array();
			if (function_exists('IPSDetectHumidityHandler_GetEventConfiguration'))		$humidity_config=IPSDetectHumidityHandler_GetEventConfiguration();
			else $humidity_config=array();
			if (function_exists('IPSDetectHeatControlHandler_GetEventConfiguration'))	$heatcontrol_config=IPSDetectHeatControlHandler_GetEventConfiguration();
			else $heatcontrol_config=array();


			}
		}
	
	/********************************************************************
	 *
	 * Die erfasst Liste aller Homematic Geräte mit der Seriennummer als
	 *  formatierte liste ausgeben mit echo 
	 *
	 *****************************************************************************/

	function writeHomematicSerialNumberList()
		{
		$instanzCount=0;
		$channelCount=0;
		echo "\nInsgesamt gibt es ".sizeof($this->HomematicSerialNumberList)." Homematic CCUs.\n";
		foreach ($this->HomematicSerialNumberList as $ccu => $geraete)
 			{
			echo "-------------------------------------------\n";
		 	echo "  CCU mit Name :".$ccu."\n";
			echo "    Es sind ".sizeof($geraete)." Geraete angeschlossen. (Zusammenfassung nach Geräte, Seriennummer)\n";
			foreach ($geraete as $name => $anzahl)
				{
				$register=explode(" ",trim($anzahl["Values"]));
				if ( isset($anzahl["Typ"]) == true )
					{
					echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".$name." Inst: ".$anzahl["Anzahl"]." Child: ".sizeof($register)." ".$anzahl["Typ"]."\n";
					}
				else
					{
					echo "     ".str_pad($anzahl["Name"],40)."  S-Num: ".$name." Inst: ".$anzahl["Anzahl"]." Child: ".sizeof($register)." ********** Typ nicht bekannt \n";
					}
				$instanzCount+=$anzahl["Anzahl"];
				$channelCount+=sizeof($register);	
				}
			}
		echo "\nEs wurden insgesamt ".$instanzCount." Geraeteinstanzen mit total ".$channelCount." Kanälen/Registern ausgegeben.\n";
		}

	/********************************************************************
	 *
	 * Die erfasst Liste aller Homematic Geräte mit der Seriennummer als
	 * html formatierte liste ausgeben (echo) 
	 *
	 *****************************************************************************/

	function tableHomematicSerialNumberList($columns=array(),$sort=array())
		{
		//print_r($columns);
		if (isset($columns["Channels"])==true) $showChannels=$columns["Channels"]; else $showChannels=false;
		if (isset($sort["Serials"])==true) $sortSerials=$sort["Serials"]; else $sortSerials=false;
		$str="";
		$ccuNum=1;	
		foreach ($this->HomematicSerialNumberList as $ccu => $geraete)
 			{
			$str .= "<table width='90%' align='center'>"; 
			$str .= "<tr><td><b>".$ccu."</b></td></tr>";
			$str .= "<tr><td><b>Seriennummer</b></td>";
			if ($showChannels) $str .= "<td><b>Kanal</b></td>";
			$str .= "<td><b>GeräteName</b></td><td><b>Protokoll</b></td><td><b>GeraeteTyp</b></td><td><b>UpdateTime</b></td><td><b>RSSI</b></td></tr>";
			if ($sortSerials) ksort($geraete);
			foreach ($geraete as $name => $geraet)
				{
				$str .= "<tr><td>".$name."</td>";			// Name ist die Seriennummer
				if ($showChannels) $str .= "<td></td>";		// eventuell Platz lassen für Kanalnummer	
				if (isset($geraet["Typ"])==true)
					{
					if (isset($geraet["RSSI"])==true)
						{
						$str .= "<td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td><td>".$geraet["Typ"]."</td><td>".
						date("d.m H:i",$geraet["Update"])."</td><td>".$geraet["RSSI"]."</td></tr>";
						}
					else
						{	
						$str .= "<td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td><td>".$geraet["Typ"]."</td><td>".
						date("d.m H:i",$geraet["Update"])."</td></tr>";
						}
					}
				else
					{
					$str .= "<td>   </td><td>      </td></tr>";				
					//$str .= "<tr><td>".$name."</td><td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td></tr>";				
					}
				$strChannel=array();	
				if ($showChannels) 
					{
					foreach ($geraet as $id => $channels)
						{
						$channel=explode(":",$id);
						if (sizeof($channel)==2) 
							{
							if ($channel[0]=="Name")
								{
								$strChannel[$channel[1]] = "<tr><td></td><td>".$channel[1]."</td><td>".$channels."</td></tr>";
								}
							}	
						}
					ksort($strChannel);	
					foreach ($strChannel as $index => $line) { $str .= $line; }					
					}
				}		
			$ccuNum++;
			}
		echo $str; 
		return ($str);		
		}
	
	/********************************************************************
	 *
	 * alle Homematic Geräte erfassen und in einer grossen Tabelle ausgeben
	 *
	 *****************************************************************************/

	function getHomematicDeviceList($debug=false)
		{

		//$this->getHomematicSerialNumberList($debug);			// gleich die Liste die in der Klasse gespeichert wird nehmen
		$this->addHomematicSerialList_Typ($debug);
		//$this->writeHomematicSerialNumberList();						// Die Geräte schön formatiert als Liste ausgeben


		$serienNummer=$this->HomematicSerialNumberList;
		/* Tabelle vorbereiten, RSSI Werte ermitteln */
	
		IPSUtils_Include ('Homematic_Library.class.php',      'IPSLibrary::app::modules::OperationCenter');

		$homematicManager = new Homematic_OperationCenter();
		$homematicManager->RefreshRSSI();

			$categoryIdHtml     = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.hardware.IPSHomematic.StatusMessages');
			$variableIdRssi       = IPS_GetObjectIDByIdent(HM_CONTROL_RSSI, $categoryIdHtml);
			echo GetValue($variableIdRssi);	// output Table

			$instanceIdList = $homematicManager->GetMaintainanceInstanceList();
			$rssiDeviceList = array();
			$rssiPeerList   = array();
			foreach ($instanceIdList as $instanceId) {
				$variableId = @IPS_GetVariableIDByName('RSSI_DEVICE', $instanceId);
				if ($variableId!==false) {
					$rssiValue = GetValue($variableId);
					if ($rssiValue<>-65535) {
						$rssiDeviceList[$instanceId] = $rssiValue;
					}
				}
			}
			arsort($rssiDeviceList, SORT_NATURAL);

			foreach ($instanceIdList as $instanceId) {
				$variableId = @IPS_GetVariableIDByName('RSSI_PEER', $instanceId);
				if ($variableId!==false) {
					$rssiValue = GetValue($variableId);
					if ($rssiValue<>-65535) {
						$rssiPeerList[$instanceId] = $rssiValue;
					}
				}
			}
			
		echo "\n\nAusgabe RSSI Werte pro Seriennummer (Anreicherung der serienNummer Tabelle):\n\n";	
			foreach($rssiDeviceList as $instanceId=>$value) 
				{
				$HM_CCU_Name=IPS_GetName(IPS_GetInstance($instanceId)['ConnectionID']);
				echo "    ".$HM_CCU_Name."     ".IPS_GetName($instanceId)."    ".HM_GetAddress($instanceId)."    ".$value."\n";
				$HMaddress=explode(":",HM_GetAddress($instanceId));
				$serienNummer[$HM_CCU_Name][$HMaddress[0]]["RSSI"]=$value;
				}			
        
        print_r($serienNummer);

        /* Tabelle indexiert nach Seriennummern ausgeben, es wird pro Homematic Socket eine eigene Tabelle erstellt */

        $str="";
        $ccuNum=1;	
        foreach ($serienNummer as $ccu => $geraete)
            {
            $str .= "<table width='90%' align='center'>"; 
            $str .= "<tr><td><b>".$ccu."</b></td></tr>";
            $str .= "<tr><td><b>Seriennummer</b></td><td><b>GeräteName</b></td><td><b>Protokoll</b></td><td><b>GeraeteTyp</b></td><td><b>UpdateTime</b></td><td><b>RSSI</b></td></tr>";
            foreach ($geraete as $name => $geraet)
                {
                if (isset($geraet["Typ"])==true)
                    {
                    if (isset($geraet["RSSI"])==true)
                        {
                        $str .= "<tr><td>".$name."</td><td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td><td>".$geraet["Typ"]."</td><td>".
                            date("d.m H:i",$geraet["Update"])."</td><td>".$geraet["RSSI"]."</td></tr>";
                        }
                    else
                        {	
                        $str .= "<tr><td>".$name."</td><td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td><td>".$geraet["Typ"]."</td><td>".
                            date("d.m H:i",$geraet["Update"])."</td></tr>";
                        }
                    }
                else
                    {
                    $str .= "<tr><td>".$name."</td><td>   </td><td>      </td></tr>";				
                    //$str .= "<tr><td>".$name."</td><td>".$geraet["Name"]."</td><td>".$geraet["Protokoll"]."</td></tr>";				
                    }		
                }		
            $ccuNum++;
            }
        echo $str; 		

    	$CategoryIdHomematicGeraeteliste = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicDeviceList');
	    $HomematicGeraeteliste = CreateVariable("HomematicGeraeteListe",   3 /*String*/,  $CategoryIdHomematicGeraeteliste, 50 , '~HTMLBox');
	    SetValue($HomematicGeraeteliste,$str);
		}

    /*****************************************************
    *
    * HM_TYPE für Homematic feststellen
    *
    * anhand einer Homatic Instanz ID ermitteln 
    * um welchen Typ von Homematic Geraet es sich handeln koennte,
    * es wird nur HM_TYPE_BUTTON, HM_TYPE_SWITCH, HM_TYPE_DIMMER, HM_TYPE_SHUTTER unterschieden
    */

    function getHomematicType($instanz)
        {
        $cids = IPS_GetChildrenIDs($instanz);
        //print_r($cids);
        $homematic=array();
        foreach($cids as $cid)
            {
            $homematic[]=IPS_GetName($cid);
            }
        sort($homematic);
        //print_r($homematic);
        /* 	define ('HM_TYPE_LIGHT',					'Light');
        define ('HM_TYPE_SHUTTER',					'Shutter');
        define ('HM_TYPE_DIMMER',					'Dimmer');
        define ('HM_TYPE_BUTTON',					'Button');
        define ('HM_TYPE_SMOKEDETECTOR',			'SmokeDetector');
        define ('HM_TYPE_SWITCH',					'Switch'); */
        $type=""; echo "       ";
        if ( isset ($homematic[0]) ) /* es kann auch Homematic Variablen geben, die zwar angelegt sind aber die Childrens noch nicht bestimmt wurden. igorieren */
            {
            switch ($homematic[0])
                {
                case "ERROR":
                    //echo "Funk-Tür-/Fensterkontakt\n";
                    break;
                case "INSTALL_TEST":
                    if ($homematic[1]=="PRESS_CONT")
                        {
                        //echo "Taster 6fach\n";
                        }
                    else
                        {
                        //echo "Funk-Display-Wandtaster\n";
                        }
                    $type="HM_TYPE_BUTTON";
                    break;
                case "ACTUAL_HUMIDITY":
                    //echo "Funk-Wandthermostat\n";
                    break;
                case "ACTUAL_TEMPERATURE":
                    //echo "Funk-Heizkörperthermostat\n";
                    break;
                case "BRIGHTNESS":
                    //echo "Funk-Bewegungsmelder\n";
                    break;
                case "DIRECTION":
                    if ($homematic[1]=="ERROR_OVERHEAT")
                        {
                        //echo "Dimmer\n";
                        $type="HM_TYPE_DIMMER";						
                        }
                    else
                        {
                        //echo "Rolladensteuerung\n";
                        }
                    break;
                case "PROCESS":
                case "INHIBIT":
                    //echo "Funk-Schaltaktor 1-fach\n";
                    $type="HM_TYPE_SWITCH";
                    break;
                case "BOOT":
                    //echo "Funk-Schaltaktor 1-fach mit Energiemessung\n";
                    $type="HM_TYPE_SWITCH";
                    break;
                case "CURRENT":
                    //echo "Energiemessung\n";
                    break;
                case "HUMIDITY":
                    //echo "Funk-Thermometer\n";
                    break;
                case "CONFIG_PENDING":
                    if ($homematic[1]=="DUTYCYCLE")
                        {
                        //echo "Funkstatusregister\n";
                        }
                    elseif ($homematic[1]=="DUTY_CYCLE")
                        {
                        //echo "IP Funkstatusregister\n";
                        }
                    else
                        {
                        //echo "IP Funk-Schaltaktor\n";
                        $type="HM_TYPE_SWITCH";
                        }
                    //print_r($homematic);
                    break;					
                default:
                    //echo "unknown\n";
                    //print_r($homematic);
                    break;
                }
            }
        else
            {
            //echo "   noch nicht angelegt.\n";
            }			

        return ($type);
        }


    /*********************************
     * 
     * Homematic Device Type, genaue Auswertung nur mehr an einer, dieser Stelle machen 
     *
     * Übergabe ist ein array aus Variablennamen/Children einer Instanz oder die Sammlung aller Instanzen die zu einem Gerät gehören
     * übergeben wird das Array das alle auch doppelte Eintraege hat. Folgende Muster werden ausgewertet:
     *
     * VALVE_STATE                                  Stellmotor, (IP) Funk Stellmotor, TYPE_ACTUATOR
     * ACTIVE_PROFILE oder WINDOW_OPEN_REPORTING    Wandthermostat, (IP) Funk Wandthermostat, TYPE_THERMOSTAT
     * TEMPERATURE und HUMIDITY                     Temperatursensor, (IP) Funk Temperatursensor, TYPE_METER_TEMPERATURE
     * PRESS_SHORT                                  Taster x-fach, (IP) Funk Tast x-fach, TYPE_BUTTON
     * STATE
     *
     * erkannte Device Typen (unabhängig ob Homematic, Evaluierung von oben nach unten
     *  TYPE_ACTUATOR               => VALVE_STATE
     *  TYPE_THERMOSTAT             => ACTIVE_PROFILE || WINDOW_OPEN_REPORTING
     *  TYPE_METER_TEMPERATURE      => TEMPERATURE && HUMIDITY
     *  TYPE_METER_HUMIDITY         => HUMIDITY
     *  TYPE_BUTTON                 => PRESS_SHORT
     *  TYPE_SWITCH                 => STATE && (PROCESS || WORKING)
     *  TYPE_CONTACT                => STATE
     *  TYPE_DIMMER                 => LEVEL && DIRECTION && ERROR_OVERLOAD
     *  TYPE_SHUTTER                => LEVEL && DIRECTION
     *  TYPE_MOTION                 => MOTION
     *  TYPE_RSSI                   => RSSI
     *  TYPE_METER_POWER
     *
     * Es gibt unterschiedliche Arten der Ausgabe, eingestellt mit outputVersion
     *   false   die aktuelle Kategorisierung
     *
     * abhängig vom Gerätetyp bzw. den Instanzeigenschaften werden für die Instanz die Register jeweils mit Typ und Parameter ermittelt
     *      $resultType[i] = "TYPE_METER_TEMPERATURE";            
     *      $resultReg[i]["TEMPERATURE"]="TEMPERATURE";
     *      $resultReg[i]["HUMIDITY"]="HUMIDITY";
     *
     *
     *
     ****************************************/

    private function HomematicDeviceType($register, $outputVersion=false, $debug=false)
        {
		sort($register);
        $registerNew=array();
    	$oldvalue="";        
        /* gleiche Einträge eliminieren */
	    foreach ($register as $index => $value)
		    {
	    	if ($value!=$oldvalue) {$registerNew[]=$value;}
		    $oldvalue=$value;
			}         
        $found=true; 
        if ($debug) echo "             HomematicDeviceType: Info mit Debug aufgerufen. Parameter ".json_encode($registerNew)."\n";

        /*--Stellmotor-----------------------------------*/
        if ( array_search("VALVE_STATE",$registerNew) !== false)            /* Stellmotor */
            {
            //print_r($registerNew);
            //echo "Stellmotor gefunden.\n";
            if (array_search("ACTIVE_PROFILE",$registerNew) !== false) 
                {
                $result[1]="IP Funk Stellmotor";
                }
            else 
                {
                $result[1]="Funk Stellmotor";
                }                         
            $result[0]="Stellmotor";   
            $i=0;                            
            $resultType[$i]="TYPE_ACTUATOR";
            if (array_search("LEVEL",$registerNew) !== false) $resultReg[$i]["VALVE_STATE"]="LEVEL"; 
            else $resultReg[$i]["VALVE_STATE"]="VALVE_STATE";
            if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_TEMPERATURE";
                $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                }
            }
        /*-----Wandthermostat--------------------------------*/
        elseif ( (array_search("ACTIVE_PROFILE",$registerNew) !== false) || (array_search("WINDOW_OPEN_REPORTING",$registerNew) !== false) )   /* Wandthermostat */
            {
            if (array_search("WINDOW_OPEN_REPORTING",$registerNew) !== false)
                {
                $result[1]="Funk Wandthermostat";
                }
            else 
                {
                $result[1]="IP Funk Wandthermostat";
                }
            $result[0] = "Wandthermostat";
            $i=0;
            $resultType[$i]="TYPE_THERMOSTAT";
            if (array_search("SET_TEMPERATURE",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="SET_TEMPERATURE";
            if (array_search("SET_POINT_TEMPERATURE",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="SET_POINT_TEMPERATURE";
            if (array_search("TargetTempVar",$registerNew) !== false) $resultReg[$i]["SET_TEMPERATURE"]="TargetTempVar";
            //echo "Wandthermostat erkannt \n"; print_r($registerNew); echo "\n";
            if ( (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) && (array_search("QUICK_VETO_TIME",$registerNew) !== false) )
                {
                $i++;
                $resultType[$i]= "TYPE_METER_TEMPERATURE";
                $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                }
            if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY"; 
                }
            if (array_search("HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                }
            }                    
        /*-----Temperatur Sensor--------------------------------*/
        elseif ( (array_search("TEMPERATURE",$registerNew) !== false) && (array_search("HUMIDITY",$registerNew) !== false) )   /* Temperatur Sensor */
            {
            $result[1] = "Funk Temperatursensor";
            $result[0] = "Temperatursensor";
            $i=0;
            $resultType[$i] = "TYPE_METER_TEMPERATURE";            
            $resultReg[$i]["TEMPERATURE"]="TEMPERATURE";
            $resultReg[$i]["HUMIDITY"]="HUMIDITY";
            if (array_search("HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                }
            }                    
        /*------Taster-------------------------------*/
        elseif (array_search("PRESS_SHORT",$registerNew) !== false) /* Taster */
            {
            $anzahl=sizeof(array_keys($register,"PRESS_SHORT")); 
            if (array_search("INSTALL_TEST",$registerNew) !== false) 
                {
                $result[1]="Funk-Taster ".$anzahl."-fach";
                }
            else 
                {
                $result[1]="IP Funk-Taster ".$anzahl."-fach";
                }
            $result[0]="Taster ".$anzahl."-fach";
            $resultType[0] = "TYPE_BUTTON";            
            if (array_search("PRESS_SHORT",$registerNew) !== false) $resultReg[0]["PRESS_SHORT"]="PRESS_SHORT";
            if (array_search("PRESS_LONG",$registerNew) !== false) $resultReg[0]["PRESS_LONG"]="PRESS_LONG";
            if ($debug) echo "-----> Taster : ".$resultType[0]." ".json_encode($registerNew).json_encode($resultReg[0])."\n";
            }
        /*-------Schaltaktor oder Kontakt------------------------------*/
        elseif ( array_search("STATE",$registerNew) !== false) /* Schaltaktor oder Kontakt */
            {
            //print_r($registerNew);
            $anzahl=sizeof(array_keys($register,"STATE"));                     
            if ( (array_search("PROCESS",$registerNew) !== false) || (array_search("WORKING",$registerNew) !== false) )     // entweder PROCESS oder WORKING gefunden
                {
                $result[0]="Schaltaktor ".$anzahl."-fach";
                if ( (array_search("BOOT",$registerNew) !== false) || (array_search("LOWBAT",$registerNew) !== false) )     //entweder Boot oder LOWBAT gefunden
                    {
                    $result[1]="Funk-Schaltaktor ".$anzahl."-fach";
                    }
                /* "SECTION_STATUS" ist bei den neuen Schaltern auch dabei. Die neuen HomematicIP Schalter geben den Status insgesamt dreimal zurück, Selektion mus ich wohl wo anders machen */
                else    
                    {
                    $result[1]="IP Funk-Schaltaktor ".$anzahl."-fach";
                    }
                if (array_search("ENERGY_COUNTER",$registerNew) !== false) 
                    {
                    $result[0] .= " mit Energiemesung";
                    $result[1] .= " mit Energiemesung";
                    }
                $resultType[0] = "TYPE_SWITCH";            
                $resultReg[0]["STATE"]="STATE";
                }
            else 
                {
                $result[0] = "Tuerkontakt";
                $result[1] = "Funk-Tuerkontakt";
                $resultType[0] = "TYPE_CONTACT";            
                $resultReg[0]["CONTACT"]="STATE";                
                }
            }
        /*-----Dimmer--------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("DIRECTION",$registerNew) !== false) && ( array_search("ERROR_OVERLOAD",$registerNew) !== false) )/* Dimmer */
            {
            //print_r($registerNew);                
            $result[0] = "Dimmer";
            $result[1] = "Funk-Dimmer";
            $resultType[0] = "TYPE_DIMMER"; 
            $resultReg[0]["LEVEL"]="LEVEL";                       
            }                    
        /*-------------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("LEVEL_STATUS",$registerNew) !== false) )/* HomematicIP Dimmer */
            {
            //print_r($registerNew);                
            $result[0] = "Dimmer";
            $result[1] = "Funk-Dimmer";
            $resultType[0] = "TYPE_DIMMER"; 
            $resultReg[0]["LEVEL"]="LEVEL";                       
            }         
        /*------Rolladensteuerung-------------------------------*/
        elseif ( ( array_search("LEVEL",$registerNew) !== false) && ( array_search("DIRECTION",$registerNew) !== false) )                   /* Rollladensteuerung/SHUTTER */
            {
            //print_r($registerNew);                
            $result[0] = "Rollladensteuerung";
            $result[1] = "Funk-Rollladensteuerung";
            $resultType[0] = "TYPE_SHUTTER";    
            $resultReg[0]["HEIGHT"]="LEVEL";              // DIRECTION INHIBIT LEVEL WORKING
            }                    
        /*-------Bewegung------------------------------*/
        elseif ( array_search("MOTION",$registerNew) !== false) /* Bewegungsmelder */
            {
            //print_r($registerNew);    
            $result[0] = "Bewegungsmelder";
            $result[1] = "Funk-Bewegungsmelder";
            $resultType[0] = "TYPE_MOTION";            
            $resultReg[0]["MOTION"]="MOTION";
            if ( array_search("BRIGHTNESS",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="BRIGHTNESS";
            if ( array_search("ILLUMINATION",$registerNew) !== false) $resultReg[0]["BRIGHTNESS"]="ILLUMINATION";
            }
        /*-------RSSI------------------------------*/
        elseif ( array_search("RSSI_DEVICE",$registerNew) !== false) /* nur der Empfangswert */
            {
            $result[0] = "RSSI Wert";
            if ( array_search("DUTY_CYCLE",$registerNew) !== false) $result[1] = "IP Funk RSSI Wert";
            else $result[1] = "Funk RSSI Wert";
            $resultType[0] = "TYPE_RSSI";             
            $resultReg[0]["RSSI"] = "";
            }            
        /*-------Energiemessgerät------------------------------*/
        elseif ( array_search("CURRENT",$registerNew) !== false) /* Messgerät */
            {
            $result[0] = "Energiemessgeraet";
            if ( array_search("BOOT",$registerNew) !== false) $result[1] = "Funk Energiemessgeraet";
            else $result[1] = "IP Funk Energiemessgeraet";
            $resultType[0] = "TYPE_METER_POWER";             
            $resultReg[0]["ENERGY"]="ENERGY_COUNTER";          
            }          
        /*-------Helligkeitssensor------------------------------*/
        elseif ( array_search("CURRENT_ILLUMINATION",$registerNew) !== false)     /* Helligkeitssensor */
            {
            $result[0] = "Helligkeitssensor";
            $result[1] = "IP Funk Helligkeitssensor";
            $resultType[0] = "TYPE_METER_CLIMATE";             
            $resultReg[0]["BRIGHTNESS"]="CURRENT_ILLUMINATION";          
            }
        /*-----Wetterstation--------------------------------*/
        elseif  (array_search("RAIN_COUNTER",$registerNew) !== false)    /* neue HomematicIP Wetterstation  */
            {
            $result[0] = "Wetterstation";
            $result[1]="Funk Wetterstation";

            $i=0;
            $resultType[$i]="TYPE_METER_CLIMATE";
            $resultReg[$i]["RAIN_COUNTER"]="RAIN_COUNTER";
            $resultReg[$i]["RAINING"]="RAINING";
            $resultReg[$i]["WIND_SPEED"]="WIND_SPEED";
            if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i]= "TYPE_METER_TEMPERATURE";
                $resultReg[$i]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY";           //Homematic
                elseif (array_search("HUMIDITY",$registerNew) !== false) $resultReg[$i]["HUMIDITY"]="HUMIDITY";                     //HomematicIP 
                }
            if (array_search("ACTUAL_HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="ACTUAL_HUMIDITY"; 
                }
            elseif (array_search("HUMIDITY",$registerNew) !== false) 
                {
                $i++;
                $resultType[$i] = "TYPE_METER_HUMIDITY";
                $resultReg[$i]["HUMIDITY"]="HUMIDITY"; 
                }

            }                      
        else 
            {
            $found=false;
            if ($debug)
                { 
                echo "             HomematicDeviceType: kein bekanntes Muster für ein gerät entdeckt. Wirklich so schwierig ?\n";
                print_r($registerNew);
                }
            }

        if ($found) 
            {
            $result[2]                = $resultType[0];
            $result[3]["Type"]        = $resultType[0];
            $result[3]["Register"]    = $resultReg[0];
            $result[3]["RegisterAll"] = $registerNew;
            $result[4]["TYPECHAN"]    = "";
            $first=true;
            foreach ($resultType as $index => $type)            // normalerweise wird nur [0] befüllt, wenn mehrere Register Sets verfügbar auch mehrere
                {
                if ($first) $first=false;
                else $result[4]["TYPECHAN"] .= ",";
                $result[4]["TYPECHAN"] .= $type;
                $result[4][$type]   = $resultReg[$index];
                }
            $result[4]["RegisterAll"] = $registerNew;

            if ($outputVersion==false) return($result[2]);
            elseif ($outputVersion==2) return ($result[1]);
            elseif ($outputVersion==3) return ($result[3]);
            elseif ($outputVersion==4) 
                {       
                /* bei Output Version 4 mehrere TYPECHANs zulassen 
                if ($resultType[0]=="TYPE_ACTUATOR")
                    {
                    if (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) 
                        {
                        $result[4]["TYPECHAN"]    .= ",TYPE_METER_TEMPERATURE";
                        $result[4]["TYPE_METER_TEMPERATURE"]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                        }
                    }
                elseif ($resultType[0]=="TYPE_THERMOSTAT")
                    {
                    //echo "Wandthermostat erkannt \n"; print_r($registerNew); echo "\n";
                    if ( (array_search("ACTUAL_TEMPERATURE",$registerNew) !== false) && (array_search("QUICK_VETO_TIME",$registerNew) !== false) )
                        {
                        $result[4]["TYPECHAN"]    .= ",TYPE_METER_TEMPERATURE";
                        $result[4]["TYPE_METER_TEMPERATURE"]["TEMPERATURE"]="ACTUAL_TEMPERATURE"; 
                        }
                    }*/
                return ($result[4]);
                } 
			else return ($result[0]);
            }
        else 
            {
            if ($outputVersion>100) 
                {
                $result = "";
                foreach ($registerNew as $entry) $result .= $entry." ";
                return ($result);
                }
            else return (false);
            }
        }


    /*********************************
     *
     * gibt für eine Homematic Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     *
     * Es gibt für ein Homematic Gerät mehrere Instanzen/Channels. Nicht alle sind relevant. Daher ausklammern.
     * Routine ermittelt alle Children eines Objektes und übergibt sie als array zur Prüfung
     * ruft HomematicDeviceType auf, es gibt verschieden Ausgabeformate
     *   0   Beispiel  "Bewegungsmelder";
     *   1   Beispiel  "Funk-Bewegungsmelder";
     *   2   Beispiel  TYPE_MOTION
     *   3   { "Type"=>TYPE_MOTION,"Register"=> $resultReg[0],"RegisterAll"=>  }
     *   4
     *
     *  0/false ist Default
     *
     ***********************************************/

    function getHomematicDeviceType($instanz, $outputVersion=false, $debug=false)
	    {
        if ($debug) echo "          getHomematicDeviceType : $instanz  \"".IPS_GetName($instanz)."\" Modus : $outputVersion\n";
    	$cids = IPS_GetChildrenIDs($instanz);
	    $homematic=array();
    	foreach($cids as $cid)
	    	{
		    $homematic[$cid]=IPS_GetName($cid);
    		}
    	return ($this->HomematicDeviceType($homematic,$outputVersion, $debug));
    	}

    /*********************************
     *
     * gibt für eine Homematic Instanz/Kanal eines Gerätes den Device Typ aus HM Inventory aus
     * Voraussetzung ist das das Homematic Inventory Handler Modul installiert ist. Sonst wird ein leerer String zurückgegeben
     * Abhängig ob der zweite Parameter default oder 1 ist wird entweder der Standard Homematic Name oder eine deutsprachige Beschreibung ausgegeben.
     *
     ***********************************************/
	 		
	function getHomematicHMDevice($instanz, $output=false, $debug=false)
		{
        $matrix=false;
        $key=IPS_GetProperty($instanz,"Address");
        if ($debug) echo "Aufruf getHomematicHMDevice mit $instanz die hat Adresse \"$key\"\n";
        //print_R($this->HomematicAddressesList);
		if (isset($this->HomematicAddressesList[$key]) ) 
            {
            //echo "getHomematicHMDevice , $instanz $key in HMI Report gefunden.\n";
            if ($output == false) return($this->HomematicAddressesList[$key]);
            else
                {
                switch ($this->HomematicAddressesList[$key])
                    {
                    case "HM-PB-6-WM55":
                    case "HmIP-WRC6":
                        $result="Taster 6-fach";
                        $matrix=[0,2,2,2,2,2,2,1];                        
                        break;

                    case "HM-PB-4Dis-WM":
                        $result="Taster 4-fach";
                        $matrix=[0,2,2,2,2,1,1,1];                        
                        break;

                    case "HM-PB-2-WM55":
                    case "HM-PB-2-WM55-2":
                    case "HM-LC-Sw2-PB-FM":                 // Doppel Taster als Einbauvariante mit Schalter
                        $result="Taster 2-fach";
                        $matrix=[0,2,2,1,1,1,1,1];                        
                        break;
                    
                    case "HM-Sec-SC":
                    case "HM-Sec-SC-2":
                    case "HMIP-SWDO":
                    case "HmIP-SWDM":                   // magnetischer Sensor

                        $result="Tuerkontakt";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HMIP-eTRV":
                    case "HmIP-eTRV-B":
                    case "HmIP-eTRV-2":
                    case "HM-CC-RT-DN":
                        $result="Stellmotor";
                        $matrix=[0,2,1,1,2,1,1,1];                        
                        break;

                    case "HmIP-SMI":
                    case "HM-Sec-MDIR":
                    case "HM-Sec-MDIR-2":
                    case "HM-Sen-MDIR-O-2":  
                    case "HM-Sen-MDIR-O":
                        $result="Bewegungsmelder";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HM-TC-IT-WM-W-EU":
                        $result="Wandthermostat";
                        $matrix=[0,2,2,1,1,1,1,1];                        // die Homematic Variante hat zwei Kanäle
                        break;
                    case "HMIP-WTH":
                    case "HmIP-WTH-2":
                    case "HmIP-WTH-B":
                        $result="Wandthermostat";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;
                        
                    case "HM-LC-Sw1-FM":
                    case "HM-LC-Sw1-Pl":
                    case "HM-LC-Sw1-Pl-2":
                    case "HM-LC-Sw1-Pl-DN-R1":
                        $result="Schaltaktor 1-fach";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HM-LC-Sw4-DR":
                    case "HM-LC-Sw4-DR-2":
                        $matrix=[0,2,2,2,2,1,1,1];                        
                        $result="Schaltaktor 4-fach";
                        break;
                    
                    case "HM-ES-PMSw1-Pl":
                    case "HM-ES-PMSw1-DR":                                  // die Hutschienen Variante dazu 
                        $result="Schaltaktor 1-fach Energiemessung";
                        $matrix=[0,2,2,1,1,1,1,1];
                        break;
                    
                    case "HMIP-PSM":
                        $result="Schaltaktor 1-fach Energiemessung";
                        $matrix=[0,2,1,2,1,1,2,1,1];
                        break;

                    case "HmIP-FSM16":                                      // Einbauvariante mit Energiemessung
                        $result="Schaltaktor 1-fach Energiemessung";
                        $matrix=[0,2,1,2,1,2,1,1];
                        break;

                    case "HM-LC-Dim1T-FM":
                    case "HM-LC-Dim1T-Pl":
                    case "HM-LC-Dim1L-Pl":
                        $result="Dimmer 1-fach";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HmIP-PDT":
                        $result="Dimmer 1-fach";                            // wie HomematicIP Steckdosen Schalter, aber mit Dimmer und ohne Energiemessung
                        $matrix=[0,2,1,2,1,1,1,1];                        
                        break;

                    case "HM-LC-Bl1-FM"                        :
                        $result="Rolladensteuerung";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HM-WDS10-TH-O":
                    case "HM-WDS40-TH-I":
                    case "HmIP-STHD":                                   // sieht aus wie ein Thermostat und hat auch versteckte Thermostatfunktionen
                        $result="Temperatur und Feuchtigkeitssensor";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HM-WDS100-C6-O":
                    case "HmIP-SWO-PR":                    
                        $result="Wetterstation";
                        $matrix=[0,2,1,1,1,1,1,1];                        
                        break;

                    case "HmIP-RCV-50":
                    case "HM-RCV-50":
                        $result="Receiver for internal messages, 50 channels";
                        $matrix=[0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1];            // alle Tasten ignorieren             
                        break;

                    case "HM-RC-19-SW":
                        $result="RemoteControl, 19 channels";
                        $matrix=[0,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2];                        
                        break;

                    case "HMIP-SLO":
                    case "HmIP-SLO":
                        $result="Helligkeitssensor";
                        $matrix=$matrix=[0,2,1,1,1,1,1,1];      // Standard Matrix, Infos in Kanal 1
                        break;                        
                    case "-":
                        echo "getHomematicHMDevice: $instanz ".IPS_GetName($instanz)."/".IPS_GetName(IPS_GetParent($instanz))." Gerät wurde gelöscht. Bitte auch manuell in IP Symcon loeschen.\n";
                        return(false);          // nicht hier weiter machen
                    default:
                        echo "getHomematicHMDevice: $instanz ".IPS_GetName($instanz)."/".IPS_GetName(IPS_GetParent($instanz)).", result is default, not known case key of \"".$this->HomematicAddressesList[$key]."\" for key $key.\n";
                        return ($this->HomematicAddressesList[$key]);
                        break;
                    }
                if ($output == 1) return($result);
                else return($matrix);               // wenn $output 2 ist
                }
            }
		else 
            {
            echo "getHomematicHMDevice , Instanz $instanz Key $key in HMI Report NICHT gefunden. Run HMI_CreateReport again. Ensure that Gateway is correctly configured to get updated values.\n";
            return("");
            }
		}	

    /*****************************************************
    *
    * HM_TYPE für FS20, FS20EX oder FHT Instanz feststellen
    *
    * anhand einer FS20, FS20EX oder FHT Instanz ID ermitteln 
    * um welchen Typ von Gerät es sich handeln koennte,
    * es wird nur HM_TYPE_BUTTON, HM_TYPE_SWITCH, HM_TYPE_DIMMER, HM_TYPE_SHUTTER unterschieden
    *
    *******************************************************************/

    function getFS20Type($instanz)
        {
        $cids = IPS_GetChildrenIDs($instanz);
        //print_r($cids);
        $homematic=array();
        foreach($cids as $cid)
            {
            $homematic[]=IPS_GetName($cid);
            }
        sort($homematic);
        //print_r($homematic);
        /* 	define ('HM_TYPE_LIGHT',					'Light');
        define ('HM_TYPE_SHUTTER',					'Shutter');
        define ('HM_TYPE_DIMMER',					'Dimmer');
        define ('HM_TYPE_BUTTON',					'Button');
        define ('HM_TYPE_SMOKEDETECTOR',			'SmokeDetector');
        define ('HM_TYPE_SWITCH',					'Switch'); */
        $type=""; echo "       ";
        if ( isset ($homematic[0]) ) /* es kann auch Homematic Variablen geben, die zwar angelegt sind aber die Childrens noch nicht bestimmt wurden. igorieren */
            {
            switch ($homematic[0])
                {
                case "ERROR":
                    //echo "Funk-Tür-/Fensterkontakt\n";
                    break;
                case "INSTALL_TEST":
                    if ($homematic[1]=="PRESS_CONT")
                        {
                        //echo "Taster 6fach\n";
                        }
                    else
                        {
                        //echo "Funk-Display-Wandtaster\n";
                        }
                    $type="HM_TYPE_BUTTON";
                    break;
                case "ACTUAL_HUMIDITY":
                    //echo "Funk-Wandthermostat\n";
                    break;
                case "ACTUAL_TEMPERATURE":
                    //echo "Funk-Heizkörperthermostat\n";
                    break;
                case "BRIGHTNESS":
                    //echo "Funk-Bewegungsmelder\n";
                    break;
                case "DIRECTION":
                    if ($homematic[1]=="ERROR_OVERHEAT")
                        {
                        //echo "Dimmer\n";
                        $type="HM_TYPE_DIMMER";						
                        }
                    else
                        {
                        //echo "Rolladensteuerung\n";
                        }
                    break;
                case "PROCESS":
                case "INHIBIT":
                    //echo "Funk-Schaltaktor 1-fach\n";
                    $type="HM_TYPE_SWITCH";
                    break;
                case "BOOT":
                    //echo "Funk-Schaltaktor 1-fach mit Energiemessung\n";
                    $type="HM_TYPE_SWITCH";
                    break;
                case "CURRENT":
                    //echo "Energiemessung\n";
                    break;
                case "HUMIDITY":
                    //echo "Funk-Thermometer\n";
                    break;
                case "CONFIG_PENDING":
                    if ($homematic[1]=="DUTYCYCLE")
                        {
                        //echo "Funkstatusregister\n";
                        }
                    elseif ($homematic[1]=="DUTY_CYCLE")
                        {
                        //echo "IP Funkstatusregister\n";
                        }
                    else
                        {
                        //echo "IP Funk-Schaltaktor\n";
                        $type="HM_TYPE_SWITCH";
                        }
                    //print_r($homematic);
                    break;					
                default:
                    //echo "unknown\n";
                    //print_r($homematic);
                    break;
                }
            }
        else
            {
            //echo "   noch nicht angelegt.\n";
            }			

        return ($type);
        }

    /*********************************
     *
     * gibt für eine FS20 Instanz/Kanal eines Gerätes den Typ aus
     * zB TYPE_METER_TEMPERATURE
     *
     ***********************************************/

    function getFS20DeviceType($instanz)
        {
        $cids = IPS_GetChildrenIDs($instanz);
        $homematic=array();
        foreach($cids as $cid)
            {
            $homematic[]=IPS_GetName($cid);
            }
        sort($homematic);
        $type=""; echo "       ";
        if ( isset ($homematic[0]) ) /* es kann auch Homematic Variablen geben, die zwar angelegt sind aber die Childrens noch nicht bestimmt wurden. igorieren */
            {
            if (strpos($homematic[0],"(") !== false) 	$auswahl=substr($homematic[0],0,(strpos($homematic[0],"(")-1));
            else $auswahl=$homematic[0];
            echo "Auf ".$auswahl." untersuchen.\n";
            switch ($auswahl)
                {
                case "ERROR":
                    echo "Funk-Tür-/Fensterkontakt\n";
                    $type="TYPE_CONTACT";
                    break;
                case "Gerät":
                    echo "Funk-Display-Wandtaster\n";
                    $type="TYPE_BUTTON";
                    break;
                case "Batterie":
                    echo "Funk-Wandthermostat\n";
                    $type="TYPE_THERMOSTAT";
                    break;
                case "ACTIVE_PROFILE":
                    if ($homematic[15]=="VALVE_ADAPTION")
                        {
                        echo "Stellmotor\n";
                        $type="TYPE_ACTUATOR";
                        }
                    else
                        {
                        echo "Wandthermostat (IP)\n";
                        $type="TYPE_THERMOSTAT";
                        }
                    break;
                case "ACTUAL_TEMPERATURE":
                    echo "Funk-Heizkörperthermostat\n";
                    $type="TYPE_ACTUATOR";
                    break;
                case "ILLUMINATION":
                case "BRIGHTNESS":
                    echo "Funk-Bewegungsmelder\n";
                    $type="TYPE_MOTION";
                    break;
                case "DIRECTION":
                    if ($homematic[1]=="ERROR_OVERHEAT")
                        {
                        echo "Dimmer\n";
                        $type="TYPE_DIMMER";						
                        }
                    else
                        {
                        echo "Rolladensteuerung\n";
                        }
                    break;
                case "Daten":
                    echo "Funk-Schaltaktor 1-fach\n";
                    $type="TYPE_SWITCH";
                    break;
                case "BOOT":
                    echo "Funk-Schaltaktor 1-fach mit Energiemessung\n";
                    $type="TYPE_SWITCH";
                    break;
                case "CURRENT":
                    echo "Energiemessung\n";
                    $type="TYPE_METER_POWER";
                    break;
                case "HUMIDITY":
                    echo "Funk-Thermometer\n";
                    $type="TYPE_METER_TEMPERATURE";
                    break;
                case "CONFIG_PENDING":
                    if ($homematic[1]=="DUTYCYCLE")
                        {
                        echo "Funkstatusregister\n";
                        }
                    elseif ($homematic[1]=="DUTY_CYCLE")
                        {
                        echo "IP Funkstatusregister\n";
                        }
                    else
                        {
                        echo "IP Funk-Schaltaktor\n";
                        $type="TYPE_SWITCH";
                        }
                    //print_r($homematic);
                    break;					
                default:
                    echo "unknown\n";
                    print_r($homematic);
                    break;
                }
            }
        else
            {
            echo "   noch nicht angelegt.\n";
            }			
        return ($type);
        }



    /*****************************************************************
    *
    * den Status der HomematicCCU auslesen, alle Fehlermeldungen
    *
    * funktioniert für CCU2 und CCU3
    * alle echo Meldungen werden im String alleHM_errors gesammelt
    *
    **************/

    function HomematicFehlermeldungen($mode=false, $debug=false)
	    {
        if ($debug) 
            {
            echo "HomematicFehlermeldungen für die Ausgabe der aktuellen Fehlermeldungen der Homematic Funkkommunikation aufgerufen. Ausgabeart :";
            if ($mode) echo "Array\n";
            else "Text\n";
            }
		$alleHM_Errors="\nAktuelle Fehlermeldungen der Homematic Funkkommunikation:\n";
        $arrHM_Errors=array();
		$texte = Array(
		    "CONFIG_PENDING" => "Konfigurationsdaten stehen zur Übertragung an",
		    "LOWBAT" => "Batterieladezustand gering",
		    "STICKY_UNREACH" => "Gerätekommunikation war gestört",
		    "UNREACH" => "Gerätekommunikation aktuell gestört"
			);

		$ids = IPS_GetInstanceListByModuleID("{A151ECE9-D733-4FB9-AA15-7F7DD10C58AF}");
		$HomInstanz=sizeof($ids);
		if($HomInstanz == 0)
		    {
		    //die("Keine HomeMatic Socket Instanz gefunden!");
		    $alleHM_Errors.="  ERROR: Keine HomeMatic Socket Instanz gefunden!\n";
            return (false);     // nicht so wichtig nehmen, es müssen nicht unbedingt Homematic Geräte installiert sein            
		    }
		else
		    {
			/* Homematic Instanzen vorhanden, sind sie aber auch aktiv ? */
			$aktiv=false;
            if ($debug) echo "  Es wurden insgesamt $HomInstanz CCUs gefunden.\n";            
            $alleHM_Errors.="  Es wurden insgesamt $HomInstanz CCUs gefunden.\n";
			foreach ($ids as $id)
	   		    {
				$HM_Config=IPS_GetConfiguration($id);
				if ($debug) echo "      Homematic Socket : ".IPS_GetName($id)."  Konfig : ".$HM_Config."\n";
                $CCUconfig=json_decode($HM_Config);
                //print_r($CCUconfig);
				if ( $CCUconfig->Open==false )
				    {
                    if ($debug) echo "               Homematic Socket ID ".$id." / ".IPS_GetName($id)."   -> im IO Socket Homematic Funk nicht aktiviert.\n";
					$alleHM_Errors.="\nHomematic Socket ID ".$id." / ".IPS_GetName($id)."   -> Port nicht aktiviert.\n";
                    $arrHM_Errors[$id]["ErrorMessage"]="Homematic Socket ID ".$id." / ".IPS_GetName($id)."   -> Port nicht aktiviert.";
					}
				else
				    {
            		$ccu_name=IPS_GetName($id);
            		$alleHM_Errors.="\nHomatic Socket ID ".$id." / ".$ccu_name."   ".sizeof($this->HomematicSerialNumberList[$ccu_name])." Endgeräte angeschlossen.\n";  
					$msgs = @HM_ReadServiceMessages($id);
					if($msgs === false)
					    {
						//die("Verbindung zur CCU fehlgeschlagen");
					    $alleHM_Errors.="  ERROR: Verbindung zur CCU fehlgeschlagen!\n";
                        echo "  ERROR: Verbindung zur CCU $id fehlgeschlagen!\n";
                        $HM_Status=IPS_GetInstance($id);
                        //print_r($HM_Status);
                        if ($HM_Status["InstanceStatus"] != 102) echo "    Instanz $id nicht aktiv.\n";
                        print_r($HM_Config);
                        $arrHM_Errors[$id]["ErrorMessage"]="Verbindung zur CCU $id fehlgeschlagen";
					    }
					if ($msgs != Null)
						{
						if(sizeof($msgs) == 0)
						    {
							//echo "Keine Servicemeldungen!\n";
					   	    $alleHM_Errors.="OK, keine Servicemeldungen!\n";
							}
                        //print_r($msgs);
						foreach($msgs as $msg)
						    {
				   		    if(array_key_exists($msg['Message'], $texte))
								{
      					  	    $text = $msg['Address']."   ".$texte[$msg['Message']]."(".$msg['Value'].")";
		   					    }
							else
								{
	      	  				    $text = $msg['Address']."   ".$msg['Message']."(".$msg['Value'].")";
			        			}
						    $HMID = GetInstanceIDFromHMID($msg['Address']);
					    	if(IPS_InstanceExists($HMID))
							 	{
        						$name = IPS_GetLocation($HMID);
					   		    }
							else
								{
			      	  		    $name = "Gerät nicht in IP-Symcon eingerichtet";
    							}
			  				//echo "Name : ".$name."  ".$msg['Address']."   ".$text." \n";
						  	$alleHM_Errors.="  NACHRICHT : ".str_pad($name,60)."  $text \n";
                            $arrHM_Errors[$HMID]["ErrorMessage"]="$name $text";
							}
						}
					}
				}
			}
		if ($mode) return($arrHM_Errors);
        else return($alleHM_Errors);
    	}

    } /* ende class DeviceManagement */

/*********************************************************************************************
 *
 *
 * 
 *
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
				$log_Sprachsteuerung=new Logging($this->systemDir."Sprachsteuerung\Log_Sprachsteuerung.csv",$NachrichtenInputID);
				if ($sk<10) $log_Sprachsteuerung->LogNachrichten("Sprachsteuerung $sk mit \"".$ansagetext."\"");
				else $log_Sprachsteuerung->LogNachrichten("Sprachsteuerung $sk (".IPS_GetName($sk).") mit \"".$ansagetext."\"");
				}
			}
		$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');           // App Kategorie der Sprachsteuerung
		$scriptIdSprachsteuerung   = @IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);
		if ($scriptIdSprachsteuerung==false) $sprachsteuerung=false;
		if ( ($sprachsteuerung==true) && ($remote==false) )
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