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

IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");                 // dadurch leine Fehlermeldung ber RunScript

/*********************************************************************************************
 *
 *
 *
 *
 * diese Klassen werden hier behandelt, waren vorher in der OperationCenter_Library
 *
 *
 *
 */
 
 
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

        //echo "Construct BackupIpSymcon.\n";
        $configuration        = $this->getConfigurationBackup();                // direkter Zugriff auf Parent variablen sollte vermieden werden
        $BackupDrive          = $configuration["Directory"];                    // für vorher nachher vergleich
        $this->backupActive   = $this->setActive($configuration,$debug);
        $this->SourceDrive    = IPS_GetKernelDirEx();           // das alte C:/IP-Symcon  - sollte eigentlich IPS_GetKernelDir() sein

        if ($debug) 
            {
            echo "class BackupIpsymcon: _construct\n";
            echo "   work Dir is ".$this->systemDir."\n";
            echo "   symcon Dir is ".IPS_GetKernelDir()."\n";
            echo "   OperatingSystem is ".$this->dosOps->evaluateOperatingSystem()."\n";
            echo "   install Dir is ".IPS_GetKernelDirEx()."\n";
            echo "   IPS platform is ".IPS_GetKernelPlatform()."\n";
            echo "   Active           ".$this->backupActive."\n"; 
            }

		/* Allgemeine Variablen am Webfront, oder im Data Bereich */		
		$this->categoryId_BackupFunction	= IPS_GetObjectIdByName('Backup', $this->CategoryIdData);

        /* das sind die Schalter im Webfront für die Bedienung des Backups */
		$this->StatusSchalterBackupID		    = IPS_GetObjectIdByName("Backup-Funktion", $this->categoryId_BackupFunction);
		$this->StatusSchalterActionBackupID     = IPS_GetObjectIdByName("Backup-Actions", $this->categoryId_BackupFunction);
    	$this->StatusSchalterOverwriteBackupID  = IPS_GetObjectIdByName("Backup-Overwrite", $this->categoryId_BackupFunction);
        $this->StatusSliderMaxcopyID            = IPS_GetObjectIdByName("Maxcopy per Session", $this->categoryId_BackupFunction);

        $this->StatusBackupID				= IPS_GetObjectIdByName("Status", $this->categoryId_BackupFunction);
		if ($this->backupActive==false) 
            { 
            SetValue($this->StatusSchalterBackupID,0);   // keinen andern Statuzustand erlauben wenn keine Konfiguration vorhanden
            }
        else
            {
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
        if (isset($configuration["Status"])===false) return (false);
        if (is_bool($configuration["Status"]) && ($configuration["Status"]===true)) return (true);
        $status=strtoupper($configuration["Status"]);
        if ( ($status=="ACTIVE") || ($status=="ENABLED") ) return (true);       // das ist ein Wert für die Konfiguration im Configfile, aktiv oder disabled (true/false)
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

?>