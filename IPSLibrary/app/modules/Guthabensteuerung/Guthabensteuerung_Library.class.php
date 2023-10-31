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
	 
    /*
     * Klassen, die hier abgebildet sind 
     *  GuthabenHandler     macht Guthabenabfragen mit Selenium und ien paar Routinen auch mit iMacro
     *  yahooApi            verwendet yahoo finance API, aber es gibt Einschränkungen
     *  zamgApi             versucht sich an historischen Wetterdaten (schrecklich ungenau) von Geosphere/zamg Austria
     *
     *
     */

    /* Klasse GuthabenHandler
     * sammelt alle Routinen für die Verwaltung von prepaid Guthaben
     * es ist noch nicht gelungen die Webseiten ohne iMacro auszulesen, deshalb wird mit einer alten Mozilla Firefox Version (47)
     * und iMacro (ebenfalls alt) weiterhin der Inhalt der Seiten gespeichert und nachträglich analysiert
     *
     *  __construct         drei Parameter zur Steuerung
     *  getCategoryIdData                   GuthabenSteuerung data Kategorie übergeben
     *  getCategoryIdSelenium               Selenium GuthabenSteuerung data Kategorie übergeben
     *  getConfiguration
     *  setConfiguration                    die gesamte Konfiguration einlesen und eventuell anpassen, prüfen und bearbeiten
     *  updateConfiguration
     *  getContractsConfiguration
     *  getPhoneNumberConfiguration
     *  getGuthabenConfiguration
     *  printAllContractsConfiguration
     *  getSeleniumSessionID
     *  setSeleniumHandler
     *  getSeleniumWebDrivers
     *  getSeleniumWebDriverConfig          Selenium spezifische Konfigurtion ausgeben
     *  extendPhoneNumberConfiguration
     *  createVariableGuthaben
     *  createVariableGuthabenNummer
     *  getVariableGuthabenNummer
     *  parsetxtfile
     *  getfromFileorArray
     *
     *
     */

	class GuthabenHandler 
		{

		private $configuration = array();				// die angepasste, standardisierte Konfiguration
		private $CategoryIdData, $CategoryIdApp, $CategoryIdSelenium;			// die passenden Kategorien
        private $CategoryIdData_Guthaben, $CategoryIdData_GuthabenArchive;			// noch ein paar passenden Kategorien, für die Speicherung der Daten
		private $wfcHandling,$dosOps;                           // external classes made by inside

        private $repository;                            // einheitlicher Ort dafür, gesetzt bei construct

        /**
		 * @public
		 *
		 * Initialisierung des IPSMessageHandlers
		 *
		 */
		public function __construct($ausgeben=false,$ergebnisse=false,$speichern=false)
			{
            $this->wfcHandling = new wfcHandling();
            $this->dosOps        = new dosOps();
        
        	/* standardize configuration */
			$this->configuration = $this->setConfiguration($ausgeben,$ergebnisse,$speichern);       // Übergabe von drei Control Flags

            
            //print_r($this->configuration);

			/* get Directories */

			$this->repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('GuthabenSteuerung',$this->repository);

			$this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
			$this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');		
            $this->CategoryIdSelenium  = @IPS_GetObjectIDByName("Selenium", $this->CategoryIdData);
            $this->CategoryIdData_Guthaben        = @IPS_GetObjectIDByName("Guthaben", $this->CategoryIdData);
            $this->CategoryIdData_GuthabenArchive = @IPS_GetObjectIDByName("GuthabenArchive", $this->CategoryIdData);
    		}
			
        /* Kategorie für Daten ist gekapselt, hier  ausgeben */

		public function getCategoryIdData()
	        {
	        return ($this->CategoryIdData);
	        }

        /* Kategorie für Selenium Daten ist gekapselt, hier  ausgeben */

		public function getCategoryIdSelenium()
	        {
	        return ($this->CategoryIdSelenium);
	        }
            
        /* Konfiguration aus get_GuthabenConfiguration und get_GuthabenAllgemeinConfig ist gekapselt, 
         * hier die gesamte Konfiguration ausgeben, wird von setConfiguration ermittelt, 
         * Teil von construct als Aufruf von setConfiguration 
         */

		public function getConfiguration()
	        {
	        return ($this->configuration);
	        }

        /* die gesamte Konfiguration einlesen und eventuell anpassen, prüfen und bearbeiten */

		public function setConfiguration($ausgeben,$ergebnisse,$speichern)
	        {
            if ( ((function_exists("get_GuthabenConfiguration"))===false) || ((function_exists("get_GuthabenAllgemeinConfig"))===false) ) IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
            $phoneID=array();  $config=array();
            /* nur die aktiven Telefonnummern in die Konfiguration übernehmen */
            if (function_exists("get_GuthabenConfiguration")) 
                {
                $i=0;
                foreach (get_GuthabenConfiguration() as $TelNummerInput)
                    {
                    configfileParser($TelNummerInput, $TelNummer, ["STATUS","Status","status" ],"Status" ,"Active");  
                    configfileParser($TelNummerInput, $TelNummer, ["NUMMER","Nummer","nummer" ],"Nummer" ,"");  
                    configfileParser($TelNummerInput, $TelNummer, ["NAME","Name","name" ],"Name" ,"");  
                    configfileParser($TelNummerInput, $TelNummer, ["PASSWORD","Password","password" ],"Password" ,"");  
                    configfileParser($TelNummerInput, $TelNummer, ["TARIF","Tarif","tarif" ],"Tarif" ,"");  
                    configfileParser($TelNummerInput, $TelNummer, ["TYP","Typ","typ" ],"Typ" ,"Drei");  
                    //echo "Telefonnummer ".$TelNummer["Nummer"]." und Status ".$TelNummer["Status"]."\n";
                    if ($TelNummer["Status"]=="Active")
                        {
                        $phoneID[$i]=$TelNummer;
                        $phoneID[$i]["Short"]=substr($TelNummer["Nummer"],(strlen($TelNummer["Nummer"])-3),10);
                        $i++;
                        }
                    } // ende foreach
                }
                $configuration["CONTRACTS"] = $phoneID;

            if (function_exists("get_GuthabenAllgemeinConfig")) 
                {
                $configInput=get_GuthabenAllgemeinConfig();
                /* Web result Directory */
                configfileParser($configInput, $config, ["WebResultDirectory","Webresultdirectory","webresultdirectory" ],"WebResultDirectory" ,"/Guthaben/"); 
               	$systemDir     = $this->dosOps->getWorkDirectory(); 
                if (strpos($config["WebResultDirectory"],"C:/Scripts/")===0) $config["WebResultDirectory"]=substr($config["WebResultDirectory"],10);      // Workaround für C:/Scripts"
                $config["WebResultDirectory"] = $this->dosOps->correctDirName($systemDir.$config["WebResultDirectory"]);
                $this->dosOps->mkdirtree($config["WebResultDirectory"]);
                /* Operating Mode : kein Eintrag bedeutet iMacroDefault, sonst iMacro (veraltet) oder Selenium */
                configfileParser($configInput, $config, ["OPERATINGMODE","OperatingMode","Operatingmode","operatingmode" ],"OperatingMode" ,"iMacroDefault");  
                if ( (strtoupper($config["OperatingMode"]))=="IMACRODEFAULT") 
                    {       /* bestehende alte Konfiguration vor Bearbeitung ummodeln */
                    $configInput["iMacro"]=$configInput;
                    $config["OperatingMode"]="iMacro";
                    }
                if ( (strtoupper($config["OperatingMode"]))=="SELENIUM")
                    {
                    //if ($ausgeben) echo "Operating Mode is Selenium WebDriver.\n";
                    configfileParser($configInput, $configSelenium, ["Selenium","SELENIUM","selenium" ],"Selenium" , null);     //kompletten Inhalt von Selenium nach configSelenium kippen  
                    //print_r($configSelenium["Selenium"]);
                    
                    configfileParser($configSelenium["Selenium"], $config["Selenium"], ["WEBDRIVERS","WebDrivers","webdrivers" ],"WebDrivers" , null);  

                    configfileParser($configSelenium["Selenium"], $config["Selenium"], ["BROWSER","Browser","browser" ],"Browser" , "Chrome");  
                    configfileParser($configSelenium["Selenium"], $config["Selenium"], ["WEBDRIVER","WebDriver","Webdriver","webdriver" ],"WebDriver" , 'http://10.0.0.34:4444/wd/hub');

                    configfileParser($configSelenium["Selenium"], $config["Selenium"], ["HOSTS","Hosts","Host","hosts" ],"Hosts" , null);
                    configfileParser($configSelenium["Selenium"], $config["Selenium"], ["WebfrontPane","webfrontpane","Webfrontpane","webfrontpane" ],"WebfrontPane" , "Selenium");
                    configfileParser($configSelenium["Selenium"], $config["Selenium"], ["DownloadDir","downloadDir","DownLoadDir","Downloaddir","DOWNLOADDIR","downloaddir" ],"DownloadDir" , "/download/");
                    if (strpos($config["Selenium"]["DownloadDir"],"C:/Scripts/")===0) $config["Selenium"]["DownloadDir"]=substr($config["Selenium"]["Scripts"],10);      // Workaround für C:/Scripts"
                    $config["Selenium"]["DownloadDir"] = $this->dosOps->correctDirName($systemDir.$config["Selenium"]["DownloadDir"]);                    //print_r($config);
                    configfileParser($configSelenium["Selenium"], $config["Selenium"], ["GETCHROMEDRIVER","getchromedrive","getChromedriver","getChromeDriver" ],"getChromeDriver" , false);
                    }
                elseif  ( (strtoupper($config["OperatingMode"]))=="IMACRO")
                    {
                    //echo "Operating Mode is iMacro.\n";
                    configfileParser($configInput, $config, ["iMacro","IMACRO","imacro" ],"iMacro" , null);  

                    }
                elseif  ( (strtoupper($config["OperatingMode"]))=="NONE")
                    {
                    }
                else echo "ERROR GuthabenHandler::setConfiguration, Do not know the Operating Mode ".$config["OperatingMode"]."\n";

                /* API Handler */
                configfileParser($configInput, $configApi, ["Api","API","api" ],"Api" , null);
                if (isset($configApi["Api"]))
                    {
                    configfileParser($configApi["Api"], $config["Api"], ["WebfrontPane","webfrontpane","Webfrontpane","webfrontpane" ],"WebfrontPane" , null);  
                    configfileParser($configApi["Api"], $config["Api"], ["YahooApi","yahooapi","YAHOOAPI" ],"YahooApi" , null);
                    }
                /* Webfront Konfiguration kontrollieren und ergänzen */
                $WebfrontConfigID = $this->wfcHandling->get_WebfrontConfigID(); 
                configfileParser($configInput, $configWf, ["Webfront","WEBFRONT","webfront" ],"Webfront" ,[]); 
                if (count($configWf["Webfront"])>0)
                    {
                    foreach ($configWf["Webfront"] as $name => $configEntry )
                        {
                        if (isset($WebfrontConfigID[$name])===false) unset($configWf["Webfront"][$name]);
                        else 
                            {
                            $configWfEntry=array();
                            foreach ($configEntry as $item => $configSubEntry)
                                {    
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["Enabled","ENABLED","enabled"],"Enabled" ,false); 
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["Path","PATH","path"],"Path" ,null); 
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["ParentModule","PARENTMODULE","parentmodule","Parentmodule"],"ParentModule" ,null); 
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["TabPaneItem","TABPANEITEM","tabpaneitem"],"TabPaneItem" ,null); 
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["TabItem","TABITEM","tabitem"],"TabItem" ,null); 
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["TabPaneOrder","TABPANEORDER","tabpaneorder"],"TabPaneOrder" ,null); 
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["TabPaneIcon"],"TabPaneIcon","");
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["TabPaneParent"],"TabPaneParent",null);
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["TabPaneName"],"TabPaneName","");
                                configfileParser($configEntry[$item], $configWfEntry[$item], ["configID","ConfigID","configid","ConfigId" ],"ConfigId" ,$WebfrontConfigID[$name]); 
                                }
                            $config["Webfront"][$name]=$configWfEntry;
                            }   
                        }
                    }
                }           // get GuthabenAllg

            $configuration["CONFIG"]    = $config;

            $configuration["EXECUTE"]["AUSGEBEN"]=$ausgeben;
            $configuration["EXECUTE"]["ERGEBNISSE"]=$ergebnisse;
            $configuration["EXECUTE"]["SPEICHERN"]=$speichern;                
	        return ($configuration);
	        }

        /* nur die Ausgabeoptionen updaten */

        public function updateConfiguration($ausgeben,$ergebnisse,$speichern)
	        {
            $this->configuration["EXECUTE"]["AUSGEBEN"]=$ausgeben;
            $this->configuration["EXECUTE"]["ERGEBNISSE"]=$ergebnisse;
            $this->configuration["EXECUTE"]["SPEICHERN"]=$speichern; 
            return($this->configuration);
            }

        /* die optionalen Webfronts für Selenium und Money sind jetzt konfigurierbar
        */
        public function getWebfrontsConfiguration($tabPane,$debug=false)
            {
            $webfrontConfig=false;
            $GuthabenAllgConfig=$this->configuration["CONFIG"];
            $ipsOps = new ipsOps();
            if ($debug) print_r($GuthabenAllgConfig);
            // WebfrontConfig für Selenium
            if (isset($GuthabenAllgConfig[$tabPane]["WebfrontPane"]))
                {
                $webfrontPane = $GuthabenAllgConfig[$tabPane]["WebfrontPane"];
                if ($debug) echo "WebfrontPane für Selenium : $webfrontPane\n";
                if (isset($GuthabenAllgConfig["Webfront"]["Administrator"][$webfrontPane]))
                    {
                    $webfrontConfig=$GuthabenAllgConfig["Webfront"]["Administrator"][$webfrontPane];
                    if ($webfrontConfig["Enabled"]==1)
                        {
                        if (isset($webfrontConfig["ParentModule"]))   
                            {
                            if ($debug) echo "Parent Module ".$webfrontConfig["ParentModule"]." berücksichtigen.\n";
                            $moduleManagerGUI = new IPSModuleManager($webfrontConfig["ParentModule"],$this->repository);
                            $configWFrontGUI=$ipsOps->configWebfront($moduleManagerGUI,false);     // wenn true mit debug Funktion  
                            $tabPaneParent=$configWFrontGUI["Administrator"]["TabPaneItem"];
                            if ($debug) echo "  Selenium Module Überblick im Administrator Webfront $tabPaneParent abspeichern.\n";
                            $webfrontConfig["TabPaneParent"]=$tabPaneParent;
                            }
                        if ($debug) print_R($webfrontConfig);
                        }
                    }
                }
            return($webfrontConfig);
            }

        /* nur die SIM Karten Informationen ausgeben, zusaetztlich
         * nach einer bestimmten telefonnummer suchen und nur diese ausgeben
         */ 

		public function getContractsConfiguration($telNummerFind="")
	        {
            if ($telNummerFind=="") return ($this->configuration["CONTRACTS"]);
            else
                {
                //echo "getContractsConfiguration für $telNummerFind :\n";
                $result=array();
            	foreach ($this->configuration["CONTRACTS"] as $TelNummer)
		            {
                    //print_r($TelNummer);
                    if ($TelNummer["Nummer"] == $telNummerFind)  $result=$TelNummer;
                    }
                return $result;
                }
	        }

        /* nur die Telefonnummern als Array ausgeben, Informationen aus CONTRACTS zusammenfassen 
         *
         */

        public function getPhoneNumberConfiguration()
            {
            $phoneID=array();
            $i=0;
            foreach ($this->configuration["CONTRACTS"] as $TelNummer)
                {
                //echo "Telefonnummer ".$TelNummer["NUMMER"]."\n";
                if ($TelNummer["Status"]=="Active")
                    {
                    $phoneID[$i]["Short"]=substr($TelNummer["Nummer"],(strlen($TelNummer["Nummer"])-3),10);
                    $phoneID[$i]["Nummer"]=$TelNummer["Nummer"];
                    $phoneID[$i]["Password"]=$TelNummer["Password"];
                    if (isset($TelNummer["Typ"])) $phoneID[$i]["Typ"]=$TelNummer["Typ"];
                    else $phoneID[$i]["Typ"]="Drei";
                    $i++;
                    }
                } // ende foreach
            return ($phoneID);
            }

		public function getGuthabenConfiguration()
	        {
	        return ($this->configuration["CONFIG"]);
	        }

		public function printAllContractsConfiguration()
	        {
            if ( ((function_exists("get_GuthabenConfiguration"))===false) || ((function_exists("get_GuthabenAllgemeinConfig"))===false) ) IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
            if ( (function_exists("get_GuthabenConfiguration")) && (function_exists("get_GuthabenAllgemeinConfig")) )
                {
                foreach (get_GuthabenConfiguration() as $TelNummerInput)
                    {
                    configfileParser($TelNummerInput, $TelNummer, ["STATUS","Status","status" ],"Status" ,"Active");  
                    configfileParser($TelNummerInput, $TelNummer, ["NUMMER","Nummer","nummer" ],"Nummer" ,"");  
                    configfileParser($TelNummerInput, $TelNummer, ["NAME","Name","name" ],"Name" ,"");  
                    configfileParser($TelNummerInput, $TelNummer, ["PASSWORD","Password","password" ],"Password" ,"");  
                    configfileParser($TelNummerInput, $TelNummer, ["TARIF","Tarif","tarif" ],"Tarif" ,"");  
                    configfileParser($TelNummerInput, $TelNummer, ["TYP","Typ","typ" ],"Typ" ,"");  

                    echo "Telefonnummer ".$TelNummer["Nummer"]." Name ".str_pad($TelNummer["Name"],20)." Typ ".str_pad($TelNummer["Typ"],12)." und Status ".$TelNummer["Status"]."\n";
                    } // ende foreach
                }
	        }

        /* die SessionID zeigt auf die Variable die die Session des Selenium Webdrivers anzeigt 
         * wenn es die Session noch gibt wird kein neues Fenster aufgemacht
          */

        public function getSeleniumSessionID($webDriverName=false)
            {
            if ( (strtoupper($this->configuration["CONFIG"]["OperatingMode"]))!="SELENIUM") return (false);
            //$categoryId_Selenium    = $this->getCategoryIdSelenium();
            if ( ($webDriverName) && ($webDriverName != "default") )
                {
                $categoryId_Named = @IPS_GetObjectIDByName($webDriverName, $this->CategoryIdSelenium);
                if ($categoryId_Named===false) return (false);                    
                $sessionID              = @IPS_GetObjectIDByName("SessionId", $categoryId_Named); 
                }
            else $sessionID              = @IPS_GetObjectIDByName("SessionId", $this->CategoryIdSelenium);             
            return ($sessionID);
            }

        /* jedes Fenster hat eine eigene WindowId
         * diese Window ID gemeinsam mit einem passenden Index serialisieren und wegspeichern
         */

        public function setSeleniumHandler($handler,$webDriverName=false)
            {
            if ( (strtoupper($this->configuration["CONFIG"]["OperatingMode"]))!="SELENIUM") return (false);
            //$categoryId_Selenium  = @IPS_GetObjectIDByName("Selenium", $this->CategoryIdData);
            if ($this->CategoryIdSelenium===false) return (false);  
            if ( ($webDriverName) && ($webDriverName != "default") )
                {
                $categoryId_Named = @IPS_GetObjectIDByName($webDriverName, $this->CategoryIdSelenium);
                if ($categoryId_Named===false) return (false);    
                $handlerID            = @IPS_GetObjectIDByName("HandleId", $categoryId_Named);                                   
                }
            else
                {
                $handlerID            = @IPS_GetObjectIDByName("HandleId", $this->CategoryIdSelenium); 
                }
            if ($handlerID)
                {
                SetValue($handlerID,json_encode($handler));
                return (true);
                }
            else return (false);
            }

        public function getSeleniumHandler($webDriverName=false, $debug=false)
            {
            if ( (strtoupper($this->configuration["CONFIG"]["OperatingMode"]))!="SELENIUM") return (false);
            //$categoryId_Selenium  = @IPS_GetObjectIDByName("Selenium", $this->CategoryIdData);
            if ($this->CategoryIdSelenium===false) return (false);  
            if ( ($webDriverName) && ($webDriverName != "default") )
                {
                $categoryId_Named = @IPS_GetObjectIDByName($webDriverName, $this->CategoryIdSelenium);
                if ($categoryId_Named===false) return (false);  
                $handlerID            = @IPS_GetObjectIDByName("HandleId", $categoryId_Named);   
                }
            else
                {                      
                $handlerID            = @IPS_GetObjectIDByName("HandleId", $this->CategoryIdSelenium); 
                }
            if ($debug) echo "getSeleniumHandler Werte gespeichert in $handlerID.\n";
            if ($handlerID)
                {
                if ($debug) echo "  --> Wert ist ".GetValue($handlerID)."\n";
                return (GetValue($handlerID));
                }
            else return (false);
            }

        /* Routinen für mehrere Webdriver */

        public function getSeleniumWebDrivers()
            {
            $result = array();
            if ( (strtoupper($this->configuration["CONFIG"]["OperatingMode"]))!="SELENIUM") return (false); 
            if (isset($this->configuration["CONFIG"]["Selenium"]["WebDrivers"]))
                {
                foreach ($this->configuration["CONFIG"]["Selenium"]["WebDrivers"] as $name => $entry)
                    {
                    $result[]=$name;
                    }
                } 
            else $result[]="default";
            return ($result);
            }

        /* aus der Config die Configuration für den richtigen WebDriver raussuchen */

        public function getSeleniumWebDriverConfig($name=false)
            {
            $result = array();
            if ( (strtoupper($this->configuration["CONFIG"]["OperatingMode"]))!="SELENIUM") return (false);
            //$categoryId_Selenium  = @IPS_GetObjectIDByName("Selenium", $this->CategoryIdData);
            if ($this->CategoryIdSelenium===false) return (false);
            if ( ($name) && ($name != "default") && (isset($this->configuration["CONFIG"]["Selenium"]["WebDrivers"][$name])))
                {
                $result = $this->configuration["CONFIG"]["Selenium"]["WebDrivers"][$name];
                }
            else
                {
                $result["WebDriver"] = $this->configuration["CONFIG"]["Selenium"]["WebDriver"];
                $result["Browser"]   = $this->configuration["CONFIG"]["Selenium"]["Browser"];
                }
            return ($result);
            }

        /* aus der Config die Configuration für die Auslesung der Hosts raussuchen 
         * Select kann all, morning, evening 
         */

        public function getSeleniumHostsConfig($select="ALL",$debug=false)
            {
            $result = array();
            if ( (strtoupper($this->configuration["CONFIG"]["OperatingMode"]))!="SELENIUM") return (false);
            if ($this->CategoryIdSelenium===false) return (false);
            $result["Hosts"] = $this->configuration["CONFIG"]["Selenium"]["Hosts"];
            
            // Filter noch ausprobieren
            $configResult=array();
            foreach ($result["Hosts"] as $host => $entry)
                {
                if ($debug) echo "check $host\n";
                $found=false;
                if (strtoupper($select)=="ALL") $found=true;
                if ( (isset($entry["CONFIG"])) && (isset($entry["CONFIG"]["ExecTime"])) )
                    { 
                    if ( (strtoupper($select)) == (strtoupper($entry["CONFIG"]["ExecTime"])) ) $found=true;
                    }
                else $found=true;
                if ($found) $configResult["Hosts"][$host]=$result["Hosts"][$host];
                }    
            return ($configResult);
            }

        /* aus der Config die Configuration für die Auslesung der Tabs raussuchen 
         * $tabs bestimmt den jeweiligen Host
         */

        public function getSeleniumTabsConfig($tabs,$debug=false)
            {
            $result = array();
            if ( (strtoupper($this->configuration["CONFIG"]["OperatingMode"]))!="SELENIUM") return (false);
            if ($this->CategoryIdSelenium===false) return (false);
            if (isset($this->configuration["CONFIG"]["Selenium"]["Hosts"][$tabs]["CONFIG"]))
                {
                return ($this->configuration["CONFIG"]["Selenium"]["Hosts"][$tabs]["CONFIG"]);
                }
            else return (false);
            }

        /* extend phoneID array with Selenium results information
        *
        */

        public function extendPhoneNumberConfiguration(&$phoneID,$category,$debug=false)
            {
            $registers=array();
            $childrens=IPS_GetChildrenIDs($category);
            foreach ($childrens as $children) 
                {
                $name = IPS_GetName($children);
                //echo "  $name   \n";
                $register[$name]["LastUpdated"]=IPS_GetVariable($children)["VariableUpdated"];
                $register[$name]["OID"]=$children;
                }
            if ($debug) echo "Register in IP Symcon:\n";
            //print_R($register);
            foreach ($phoneID as $index => $entry)
                {
                if ($debug) echo "   ".$entry["Nummer"]."    : ";
                if (isset($register[$entry["Nummer"]])) 
                    {
                    if ($debug) echo "available, last update was ".date("d.m.Y H:i:s",$register[$entry["Nummer"]]["LastUpdated"])."\n";
                    $phoneID[$index]+=$register[$entry["Nummer"]];
                    }
                else if ($debug) echo "NOT available\n";
                }
            }

        /* wird von Execute verwendet, ermittelt die Dateien im Download Verzeichnis und gibt diese als Text und
         * als Array aus
         */

        function readDownloadDirectory($verzeichnis=false)
            {
            if ($verzeichnis===false) $verzeichnis=$GuthabenAllgConfig["DownloadDirectory"];        
            $dir=array(); $count=0; 
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
                            if ($dateityp == "file")			// alternativ dir für Verzeichnis
                                {
                                //echo "   Erfasse Verzeichnis ".$verzeichnis.$file."\n";
                                $dir[$count]["Name"]=$verzeichnis.$file;
                                $dir[$count++]["Date"]=date ("d.m.Y H:i:s.", filemtime($verzeichnis.$file));
                                }
                            }	
                        //echo "    ".$file."    ".$dateityp."\n";
                        } /* Ende while */
                    //echo "   Insgesamt wurden ".$count." Verzeichnisse entdeckt.\n";	
                    closedir($handle);
                    } /* end if dir */
                }/* ende if isdir */
            else
                {
                echo "Kein Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
                }	
            //print_r($dir);
            echo "Dateien im Download Verzeichnis.\n";
            foreach ($dir as $entry) echo "   ".$entry["Name"]."  zuletzt geändert am ".$entry["Date"]."\n";
            return ($dir);
            }

        /******************************************************************************/

        public function createVariableGuthaben($lookfor="")
            {
            $result=array();
            if ($lookfor=="")    
                {
                $config=$this->getContractsConfiguration();
                $result=array();
            	foreach ($this->configuration["CONTRACTS"] as $TelNummer)
		            {
               		if ( (isset($TelNummer["STATUS"])) && (strtoupper($TelNummer["STATUS"]) == "ACTIVE") )
                        {
                        //print_r($TelNummer);
            		    if (isset($TelNummer["NUMMER"])) $result[$TelNummer["NUMMER"]]=$this->createVariableGuthabenNummer($TelNummer["NUMMER"]);
                        }
                    }                
                }
            else
                {
                $config=$this->getContractsConfiguration($lookfor);
                $result[$config["NUMMER"]]=$this->createVariableGuthabenNummer($config["NUMMER"]);
                }      
            return($result);              
            }    

        /* Die Daten für eine SIM Karte anlgen und auslesen als result array */

        private function createVariableGuthabenNummer($lookfor="")
            {
            if ($lookfor=="") return (false);
            $config=$this->getContractsConfiguration($lookfor);
            $nummer=$config["NUMMER"];
            $result=array();
            echo "createVariableGuthaben für Nummer $nummer in Category Guthaben (".$this->CategoryIdData_Guthaben.")\n";
            $phone1ID = CreateVariableByName($this->CategoryIdData_Guthaben, "Phone_".$nummer, 3);
            $phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Summary", 3);
            $phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_User", 3);
            $phone_Status_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Status", 3);
            $phone_Date_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Date", 3);
            $phone_loadDate_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_loadDate", 3);
            $phone_unchangedDate_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_unchangedDate", 3);
            $phone_Bonus_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Bonus", 3);
            $phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Volume", 2);
            $phone_VolumeCumm_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_VolumeCumm", 2);
            $phone_nCost_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Cost", 2);
            $phone_nLoad_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Load", 2);
            $result["SUMMARY"]=GetValue($phone1ID);
            $result["SUMMARY2"]=GetValue($phone_Summ_ID);
            $result["USER"]=GetValue($phone_User_ID);
            $result["STATUS"]=GetValue($phone_Status_ID);
            $result["DATE"]=GetValue($phone_Date_ID);
            $result["LOADDATE"]=GetValue($phone_loadDate_ID);
            $result["UNCHANGEDDATE"]=GetValue($phone_unchangedDate_ID);
            $result["BONUS"]=GetValue($phone_Bonus_ID);
            $result["VOLUME"]=GetValue($phone_Volume_ID);
            $result["VOLUMECUMM"]=GetValue($phone_VolumeCumm_ID);
            $result["COST"]=GetValue($phone_nCost_ID);
            $result["LOAD"]=GetValue($phone_nLoad_ID);
            return($result);
            }


		/*************************************************************************************************/

        public function getVariableGuthabenNummer($lookfor="")
            {
            if ($lookfor=="") return (false);
            $config=$this->getContractsConfiguration($lookfor);
            $nummer=$config["NUMMER"];
            $result=array();
            echo "getVariableGuthaben für Nummer $nummer in Category Guthaben (".$this->CategoryIdData_Guthaben.")\n";
            $phone1ID = IPS_GetObjectIDByName("Phone_".$nummer,$this->CategoryIdData_Guthaben);

            $phone_Summ_ID = IPS_GetObjectIDByName("Phone_".$nummer."_Summary", $phone1ID);
            $phone_User_ID = IPS_GetObjectIDByName("Phone_".$nummer."_User", $phone1ID);
            $phone_Status_ID = IPS_GetObjectIDByName("Phone_".$nummer."_Status", $phone1ID);
            $phone_Date_ID = IPS_GetObjectIDByName("Phone_".$nummer."_Date", $phone1ID);
            $phone_loadDate_ID = IPS_GetObjectIDByName("Phone_".$nummer."_loadDate", $phone1ID);
            $phone_unchangedDate_ID = IPS_GetObjectIDByName("Phone_".$nummer."_unchangedDate", $phone1ID);
            $phone_Bonus_ID = IPS_GetObjectIDByName("Phone_".$nummer."_Bonus", $phone1ID);
            $phone_Volume_ID = IPS_GetObjectIDByName("Phone_".$nummer."_Volume", $phone1ID);
            $phone_VolumeCumm_ID = IPS_GetObjectIDByName("Phone_".$nummer."_VolumeCumm", $phone1ID);
            $phone_nCost_ID = IPS_GetObjectIDByName("Phone_".$nummer."_Cost", $phone1ID);
            $phone_nLoad_ID = IPS_GetObjectIDByName("Phone_".$nummer."_Load", $phone1ID);
            $result["SUMMARY"]=GetValue($phone1ID);
            $result["SUMMARY2"]=GetValue($phone_Summ_ID);
            $result["USER"]=GetValue($phone_User_ID);
            $result["STATUS"]=GetValue($phone_Status_ID);
            $result["DATE"]=GetValue($phone_Date_ID);
            $result["LOADDATE"]=GetValue($phone_loadDate_ID);
            $result["UNCHANGEDDATE"]=GetValue($phone_unchangedDate_ID);
            $result["BONUS"]=GetValue($phone_Bonus_ID);
            $result["VOLUME"]=GetValue($phone_Volume_ID);
            $result["VOLUMECUMM"]=GetValue($phone_VolumeCumm_ID);
            $result["COST"]=GetValue($phone_nCost_ID);
            $result["LOAD"]=GetValue($phone_nLoad_ID);
            return($result);
            }

        /*************************************************************************************************
        *
        * Function Parse textfile, drei Optionen Ausgeben, Speichern, Ergebnis
        *
        * bestimmte Textfelder/Marker finden und denn Wert dahinter auslesen und einer Variablen zuordnen
        * Inputvariablen: lookfor   Nummer/username die gesucht wird, Verzeichnis/Filename für das Input Dokument, type kann array/file sein
        *
        * bei type=="array" werden die Daten aus dem array mit dem Namen Verzeichnis herausgesucht, es gilt pro Eintrag eine Zeile 
        *
        * return ist ein Ergebnis String als lesbarer text
        * Phone_$nummer_Summary         Speicherort für selben Ergebnis String
        * Phone_$nummer
        * Phone_$nummer_Summary
        * Phone_$nummer_User
        * Phone_$nummer_Date
        * Phone_$nummer_loadDate
        * Phone_$nummer_unchangedDate
        * Phone_$nummer_Bonus
        * Phone_$nummer_Volume
        * Phone_$nummer_VolumeCumm
        * Phone_$nummer_Cost
        * Phone_$nummer_Load
        * Phone_Cost
        * Phone_Load
        * Phone_CL_Change
        *
        * $result1 	Username
        * $result2 	Telefonnummer
        * $result3 	Datum der letzten Aktualisierung (wenn vorhanden)
        * $result4v	MB verbraucht
        * $tarif1  	Name des Tarifs
        * $lastbill 	letzte Rechnungsperiode
        * $result5 	Guthaben oder Bertrag aktuelle Rechnung
        * $result7 	Gültigkeit des aktuellen Guthabens, bei Prepaid zb bis zu 6 Monate ab der letzten Aufladung
        *
        * Wie erfolgt die Suche in den einzelnen Zeilen, nach welchen kriterien:
        *
        *
        *
        *
        *
        ************************************************************************************************************/

        function parsetxtfile($lookfor="",$verzeichnis=false,$filename=false,$type="file",$debug=false)
            {
            echo "Parsetxtfile wurde in der Betriebsart $type aufgerufen:\n";
            if ($lookfor == "") return (false);
            if ( ($type == "file") && ($verzeichnis === false) ) 
                {
                $config=$this->getGuthabenConfiguration();
                if (isset($config["iMacro"]["DownloadDirectory"])) $verzeichnis=$config["iMacro"]["DownloadDirectory"];
                else return (false);
                }
            $config=$this->getContractsConfiguration($lookfor);
            //print_r($config);
            $nummer=$config["Nummer"];
            $typ=strtoupper($config["Typ"]);
            if ( ($type == "file") && ($filename === false) ) $filename = "/report_dreiat_".$nummer.".txt";

            // Ergebnisvariablen, IDs anlegen
            echo "Ergebnisvariablen, IDs anlegen in Category Guthaben (".$this->CategoryIdData_Guthaben.")\n";
            $phone1ID               = IPS_GetObjectIDByName("Phone_".$nummer,           $this->CategoryIdData_Guthaben);
            $phone_Summ_ID          = IPS_GetObjectIDByName("Phone_".$nummer."_Summary",$phone1ID);
            $phone_User_ID          = IPS_GetObjectIDByName("Phone_".$nummer."_User",   $phone1ID);
            $phone_Date_ID          = IPS_GetObjectIDByName("Phone_".$nummer."_Date",   $phone1ID);
            $phone_loadDate_ID      = IPS_GetObjectIDByName("Phone_".$nummer."_loadDate",$phone1ID);
            $phone_unchangedDate_ID = IPS_GetObjectIDByName("Phone_".$nummer."_unchangedDate",$phone1ID);
            $phone_Bonus_ID         = IPS_GetObjectIDByName("Phone_".$nummer."_Bonus",  $phone1ID);
            $phone_Volume_ID        = IPS_GetObjectIDByName("Phone_".$nummer."_Volume", $phone1ID);
            $phone_VolumeCumm_ID    = IPS_GetObjectIDByName("Phone_".$nummer."_VolumeCumm",$phone1ID);
            $phone_nCost_ID         = IPS_GetObjectIDByName("Phone_".$nummer."_Cost",   $phone1ID);
            $phone_nLoad_ID         = IPS_GetObjectIDByName("Phone_".$nummer."_Load",   $phone1ID);
            $phone_Cost_ID          = IPS_GetObjectIDByName("Phone_Cost",               $this->CategoryIdData);
            $phone_Load_ID          = IPS_GetObjectIDByName("Phone_Load",               $this->CategoryIdData);
            $phone_CL_Change_ID     = IPS_GetObjectIDByName("Phone_CL_Change",          $this->CategoryIdData);

            //$startdatenguthaben=7;
            $startdatenguthaben=0;
            $ausgeben   = $this->configuration["EXECUTE"]["AUSGEBEN"];
            $ergebnisse = $this->configuration["EXECUTE"]["ERGEBNISSE"];
            $speichern  = $this->configuration["EXECUTE"]["SPEICHERN"];
            echo "parsetxtfile : Ausgeben $ausgeben Ergebnisse $ergebnisse Speichern $speichern \n";

            if ($type=="file") 
                {
                if ($ausgeben) echo "Parse Textfile $filename / ".$config["Name"]." für $lookfor in Verzeichnis $verzeichnis . Typ ist $typ :\n";
                $handle = @fopen($verzeichnis.$filename, "r");
                }
            else $handle=$verzeichnis;      //dann ist Verzeichnis ein Zeilen Array

            $result1="";$result2="";$result3="";$result4="";$result5="";$result6="";
            $result4g="";$result4v="";$result4f="";  $result4unlimited = false; $result7=""; $result7i=""; $result8="";
            $entgelte=false;
            unset($tarif); $tarif1="";
            $postpaid=false;

            if ($handle)
                {
                if ($ausgeben) 
                    {
                    //echo "Rückmeldung fopen : "; print_r($handle); echo "\n";
                    //print_r($config);
                    echo "Aufruf von parsetxtfile mit folgender Config: ".$config["Name"]." / ".$config["Nummer"]." (".$config["Password"].")  -> ".$config["Tarif"]."   ";
                    if ($typ=="DREI") echo "Drei prepaid oder postpaid Karte.\n";
                    else echo "Alternative erkannt, UPC.\n";
                    if ($ergebnisse) echo "==========================================================================================================================\n";
                    //print_r($config);
                    }
                $nobreak=true; $line=0;         // array oder file Zeile für Zeile durchgehen
                do  {
                    if  (($buffer = $this->getfromFileorArray($handle, $type, $line)) === false) break;            // line ist ein Pointer               

                    /* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */
            
                    /********** zuerst den User ermitteln, steht hinter Willkommen 
                    *
                    */
                    if ($ausgeben) echo str_pad($line,3)."|".$buffer."\n";			// zeilenweise ausgeben
                    if(preg_match('/Willkommen/i',$buffer))
                        {
                        $pos=strpos($buffer,"kommen");
                        if (($pos!=false) && !(preg_match('/Troy/i',$buffer)))
                            {
                            $posEnde=strpos($buffer,"Abmelden");
                            if ($posEnde !== false) $result1=trim(substr($buffer,$pos+7,($posEnde-7-$pos)));		/* UPC klebt am Ende des Usenamens ein Abmelden dran */
                            else $result1=trim(substr($buffer,$pos+7,200));
                            if ($ergebnisse) echo "*********Ausgabe User : ".$result1."\n<br>";
                            }
                        }

                    /********** dann die rufnummer, am einfachsten zu finden mit der 0660er (Drei) oder 0676er (TMobile) oder 0678 (UPC) Kennung 
                    *
                    */
                    if ( (preg_match('/0660/i',$buffer)) or (preg_match('/0676/i',$buffer)) or (preg_match('/0678/i',$buffer)) )
                        /* manchmal haben wir die Rufnummer mitgenommen, geht auch jetzt für UPC */
                        {
                        $result2=trim($buffer);
                        $fnd1=strpos($result2,"0");
                        if ($fnd1>0) $result2=substr($result2,$fnd1);
                        if ($ergebnisse) echo "*********Ausgabe Nummer : ".$result2."\n<br>";
                        }

                    /********* dann das Datum der letzten Aktualisierung, zu finden nach Aktualisierung
                    *         bei postpaid kommt noch der Begriff Abrechnung als Endemarkierung hinzu
                    *
                    *         in den nächsten drei Zeilen wäre beim ersten mal der Tarif beschrieben, Zeilenvorschub auch rausnehmen
                    *         beim zweiten mal wäre es das Guthaben
                    *
                    *********************/
                    if(preg_match('/Aktualisierung/i',$buffer))
                        {
                        $pos=strpos($buffer,"Aktualisierung");
                        $Ende=strpos($buffer,"\n");
                        if ($Ende===false) $Ende = strlen($buffer);
                        //echo "***Aktualisierung gefunden : ".$pos."  ".$Ende."\n";
                        if (strpos($buffer,"Abrechnung")!==false)
                            {
                            $Ende=strpos($buffer,"Abrechnung");
                            $postpaid=true;
                            }
                        if ($pos!=false)        // Aktualisiserung gefunden, pos bekannt
                            {
                            $result3=trim(substr($buffer,$pos+16,$Ende-$pos-16));
                            //echo "Datum suchen ab ".($pos+16)." für ".($Ende-$pos-16)." Zeichen. Ende ist $Ende.\n";
                            if ($ergebnisse) echo "*********Letzte Aktualisierung : ".$result3."\n";
                            }
                        elseif (isset($tarif)==false) /* nur beim ersten mal machen, hier könnte auch der Tarif stehen, aber nicht wenn vorher schon das Datum eingelesen wurde */
                            {
                            if  (($buffer = $this->getfromFileorArray($handle, $type, $line)) === false) break;	// hier koennte auch das Datum der letzten Aufladung stehen, danach gleich bearbeiten
                            if ($ausgeben) echo $buffer;
                            if ( !(preg_match('/Aufladung/i',$buffer)) )
                                {
                                if  (($buffer2 = $this->getfromFileorArray($handle, $type, $line)) === false) break;
                                if ($ausgeben) echo $buffer2;
                                if  (($buffer3 = $this->getfromFileorArray($handle, $type, $line)) === false) break;
                                if ($ausgeben) echo $buffer3;

                                $tarif=json_encode($buffer.$buffer2.$buffer3);
                                //echo "****Tarif :".$buffer.$buffer2.$buffer3;
                                $order   = array('\r\n', '\n', '\r');
                                $replace = '';
                                $tarif1 = json_decode(str_replace($order, $replace, $tarif));
                                if ($ergebnisse) echo "********* Tarif : ".$tarif1."\n";
                                }
                            }
                        }

                    /* Trigger Guthaben */

                    if ( (preg_match('/Guthaben/i',$buffer)) && ($result5 !== "") )     // zweites Mal Guthaben ist der Tarif
                        {
                        if ($tarif1 == "")
                            {
                            echo "***\"Guthaben\" gefunden.\n";
                            if  (($buffer2 = $this->getfromFileorArray($handle, $type, $line)) === false) break;
                            if ($ausgeben) echo "   |".$buffer2."\n";
                            if  (($buffer3 = $this->getfromFileorArray($handle, $type, $line)) === false) break;
                            if ($ausgeben) echo "   |".$buffer3."\n";
                            $tarif1=$buffer2." ".$buffer3;
                            if ($ergebnisse) echo "********* Tarif : ".$tarif1."\n";
                            $buffer=""; //keine weitere Analyse mit $buffer == Guthaben
                            }
                        elseif ($ergebnisse) echo "********* bereits gefunden Tarif : \"".$tarif1."\"\n";
                        }
                    /********* dann der Name des Tarifs
                    *
                    *********************/				
                    //echo "-----------------------------------------\n";
                    //echo $buffer;
                    if ( (preg_match('/Wertkarte/i',$buffer)) && !(preg_match('/Wertkarte im/i',$buffer)) )
                        {
                        if (strpos($buffer,"Wertkarte")==0)
                            {
                            if  (($buffer = $this->getfromFileorArray($handle, $type, $line)) === false) break; 
                            if ($ausgeben) echo $buffer;
                            $tarif1=trim($buffer);
                            if ($ergebnisse) echo "********* Tarif : ".$tarif1."\n";
                            }
                        }
                    $posTarif=strpos($buffer,"Tarif:");
                    if ($posTarif !== false)
                        {  // anscheind etwas gefunden, Tarif: wird bei UPC verwendet */	
                        $tarif1=trim(substr($buffer,6,100));
                        if ($ergebnisse) echo "********* Tarif : ".$tarif1."\n";				
                        }
                        
                    /********* dann das Datum der letzten Aufladung
                    *
                    *********************/
                    if (preg_match('/Aufladung/i',$buffer))
                        {
                        if ($result8=="")               // nur  wenn nicht schon einmal gefunden
                            {
                            $pos=strpos($buffer,"Aufladung:");
                            if ($pos===false) $pos=strpos($buffer,"Aufladung");
                            $Ende=strpos($buffer,"\n");
                            if ($Ende===false) $Ende = strlen($buffer); 
                            $length=($Ende-$pos-11);                       
                            //echo "***Aufladung gefunden : hier ".$pos." Ende ist hier ".$Ende."  Substring ab ".($pos+11)." mit Länge $length\n";
                            if ( ($pos!==false) && ($length>6) )      // pos kann auch 0 sein
                                {
                                $result8=trim(substr($buffer,$pos+11,$length));
                                }
                            if ( ($pos!==false) && ($length<=6) )               // naechste Zeile ausprobieren
                                {
                                //echo "neue Zeile laden";
                                if  (($buffer = $this->getfromFileorArray($handle, $type, $line)) === false) break;
                                if ($ausgeben) echo $buffer;
                                $result8=trim($buffer);     // str_replace of cr or lf
                                }
                            if ($ergebnisse) echo "********* letzte Aufladung am : ".$result8." \n";
                            }
                        }

                    /************ Ermittlung verfügbares Datenguthaben
                    *            Suchen nach erstem Auftreten von MB, 
                    *            die MBit und den Roaming Disclaimer und die Tarifinfo ausnehmen
                    *
                    * result4g gekaufte Datenmenge, Paket
                    * result4v verbrauchte Datenmenge
                    * result4f noch zur Verfügung stehendes Datenvolumen
                    *
                    *****************/
                    //if (preg_match('/MB/i',$buffer))

                    if ( ( (preg_match('/MB verbr/i',$buffer)) or (preg_match('/MB gesamt verbr/i',$buffer)) or (preg_match('/MB verbraucht Inland/i',$buffer)) ) && 
                            !( (preg_match('/MB verbraucht EU/i',$buffer)) ) )
                        {
                        $pos=strpos($buffer,"MB");
                        if ($pos)
                            {
                            $i=$pos-2;
                            while ( ( (is_numeric($buffer[$i])) or ($buffer[$i]==".") ) && ($i != 0)) $i--;
                            //echo "***".$i."  ".$pos."\n";
                            $result4v=trim(substr($buffer,$i,$pos-$i+3));
                            if ($ergebnisse) echo "*********verbraucht : ".$result4v."\n<br>";
                            }
                        }

                    /************************** Ermittlung verfügbares Datenguthaben */
                    elseif (preg_match('/MB frei/i',$buffer))                       /* verbrauchtes Datenvolumen, das heisst was habe ich noch */
                        {
                        $result4f=trim(substr($buffer,$startdatenguthaben,200));
                        if ($ergebnisse) echo "*********frei : ".$result4f."\n<br>";
                        }

                    /* die spezielleren Abfragen vorher machen */                        
                    elseif ( (preg_match('/MB/i',$buffer)) and ($result4g=="") and !preg_match('/MBit/i',$buffer) and !preg_match('/MB,/i',$buffer) 
                            and !preg_match('/MMS/i',$buffer) and !preg_match('/Taktung/i',$buffer) )         /* verfügbares Datenvolumen, was gibt es grundsaetzlich, erstes MB, aber nicht MBit */
                        {
                        $pos=strpos($buffer,"MB");
                        if ($pos)
                            {
                            $i=$pos-2;
                            while ( (is_numeric($buffer[$i]) or ($buffer[$i]==".")) && ($i != 0)) $i--;
                            //echo "***".$i."  ".$pos."\n";
                            $result4g=trim(substr($buffer,$i,$pos-$i+2));
                            if (preg_match('/Datenmenge/i',$result4g))
                                {
                                $result4g=substr($result4g,10,40);
                                }
                            if ($ergebnisse) echo "*********Datenmenge Ticket: ".$result4g."\n<br>";
                            }
                        }
                        
                    if (preg_match('/unlimitiert/i',$buffer))
                        {
                        $result4g="99999 MB";
                        $result4f="99999 MB frei";
                        if ($result4v=="") $result4v=" 0 MB verbraucht";
                        $result4unlimited=true;
                        if ($ergebnisse) echo "*********frei : ".$result4f."\n<br>";
                        }

                    /************************ Gültigkeit des Guthabens */
                    if (preg_match('/bis:/i',$buffer))
                        {
                        if ($result7=="")   // nur das erste Mal 
                            {
                            $pos=strpos($buffer,"bis:");
                            $result7=trim(substr($buffer,$pos+4,200));
                            $pos1=strpos($result7,".")+1;
                            $pos2=strpos(substr($result7,$pos1),".")+1;
                            if ($ergebnisse) echo "*** erstes bis: ".$result7."   ".$pos."  ".$pos1."  ".$pos2."\n";
                            if ( ($pos1) && ($pos2) )
                                {
                                $result7=substr($result7,0,$pos1+$pos2+4);
                                if ($ergebnisse) echo "*********Gültig bis : ".$result7."\n<br>";
                                }
                            }
                        elseif ($ergebnisse) echo "***bis: erkannt. Bereits in $result7. \n<br>";

                        }
                        
                    /************************ Erkennung Postpaidvertrag 
                     * mittlerweile wird der Abrechnungszeitraum nur mehr Prepaidkarten ausgegeben. Die Postpaid haben ein Abrechnung:
                     */	
                    if (preg_match('/Abrechnung:/i',$buffer))
                        {
                        if ($ergebnisse) echo "***Postpaid Abrechnung\n<br>";
                        $postpaid=true;
                        }

                    if (preg_match('/Abrechnungszeitraum:/i',$buffer))
                        {
                        $pos=strpos($buffer,"-");
                        if ($pos && ($postpaid==false))
                            { // wenn ein bis Zeichen ist und postpaid noch nicht vorher erkannt wurde, ist das ein hinweis auf postpaid System
                            $result7i=trim(substr($buffer,$pos+1,200));
                            if ($ergebnisse) echo "*********Abrechnungszeitraum bis : ".$result7i."\n<br>";
                            if (false)          // Postpaiderkennung nun anders gelöst
                                {
                                $postpaid=true;
                                if ($ergebnisse) echo "*********Postpaidvertrag (1).\n";
                                }
                            }
                        }

                    $posPostpaid=strpos($buffer,"Verbleibende Tage:");
                    if ($posPostpaid !== false)
                        {  // bei UPC gibt es kein Ende der Abrechnungsperiode, aber verbleibende Tage, auch gut 
                        $pos=strpos($buffer,":");
                        if ($pos)
                            { // kein hinweis auf postpaid System, aber Abrechnungsperiode gültig bis
                            $tage=(integer)trim(substr($buffer,$pos+1,4));
                            $result7=date("d.m.Y",(time()+(60*60*24*$tage)) );
                            if ($ergebnisse) 
                                {
                                echo "*********Gültig bis : ".$result7."\n<br>";
                                echo "*********Postpaidvertrag (3).\n";
                                }
                            }
                        }

                    $posPostpaid=strpos($buffer,"Rechnung");
                    if ($posPostpaid !== false)
                        {  // anscheind etwas gefunden, Rechnung wird bei UPC verwendet */	
                        $posDatum=strpos($buffer,"Datum zeit");
                        if ($posDatum !== false)				
                            { /* interessant, uebernaechste Zeile holen */
                            if  (($buffer = $this->getfromFileorArray($handle, $type, $line)) === false) break; 
                            if ($ausgeben) echo $buffer;
                            if  (($buffer = $this->getfromFileorArray($handle, $type, $line)) === false) break; 
                            if ($ausgeben) echo $buffer;
                            $pos=strpos($buffer,"-");
                            if ($pos)
                                { // wenn ein bis Zeichen ist das ein hinweis auf postpaid System
                                $lastbill=trim(substr($buffer,$pos+1,200));
                                $postpaid=true;
                                if ($ergebnisse) 
                                    {
                                    echo "*********Gültig bis : ".$lastbill."\n<br>";
                                    echo "*********Postpaidvertrag (2).\n";
                                    }
                                }
                            }
                        }
                        

                    /************************** 
                    * Ermittlung des Guthabens, oder zusätzlicher Verbindungsentgelte 
                    *
                    * entweder wird haben: oder Guthaben gefunden, Bearbeitung eigentlich ähnlich
                    *
                    *******************/
                    //if (preg_match('/Guthaben/i',$buffer)) echo "***Guthaben gefunden\n";
                    if (preg_match('/haben:/i',$buffer))
                        {
                        echo "***\"Guthaben:\" gefunden\n";
                        $pos=strpos($buffer,"haben:");
                        $Ende=strpos($buffer,",");       /* Eurozeichen laesst sich nicht finden */
                        if ($pos!=false)
                            {
                            $pos=$pos+6;
                            $result5=trim(substr($buffer,$pos,$Ende-$pos+3));
                            }
                        if ($Ende === false)   // manchmal steht das Guthaben auch in der nächsten Zeile
                            {
                            if  (($buffer = $this->getfromFileorArray($handle, $type, $line)) === false) break;
                            if ($ausgeben) echo $buffer;
                            $Ende=strpos($buffer,",")-3;       /* Eurozeichen laesst sich nicht finden */
                            if ($Ende<0) $Ende=0;
                            $result5=trim(substr($buffer,$Ende,6));
                            if ($ergebnisse) echo "*********Geldguthaben : ".$result5." \n<br>";
                            }
                        }
                    if ( (preg_match('/Guthaben/i',$buffer)) && !(preg_match('/Guthaben laden/i',$buffer)) )
                        {
                        echo "***\"Guthaben\" gefunden\n";                            
                        //echo $buffer;
                        $pos=strpos($buffer,"haben:");
                        $Ende=strpos($buffer,",");       /* Eurozeichen laesst sich nicht finden */
                        if ($pos!=false)
                            {
                            $pos=$pos+6;
                            $result5=trim(substr($buffer,$pos,$Ende-$pos+3));
                            }
                        if ($Ende === false)   // manchmal steht das Guthaben auch in der nächsten Zeile
                            {
                            if  (($buffer = $this->getfromFileorArray($handle, $type, $line)) === false) break;
                            if ($ausgeben) echo $buffer;
                            $pos=strpos($buffer,",");       /* Eurozeichen laesst sich nicht finden */
                            $pos1=strpos($buffer,"€");
                            $i=0;
                            if ($pos1==false)           // kein Eurozeichen
                                {
                                if ($pos!=false)        // wenn Komma aber kein Eurozeichen
                                    {
                                    $i=$pos;
                                    $len=3;     // Komma plus zwei Kommastellen
                                    }
                                }
                            else                        // Eurozeichen gefunden 
                                {
                                if ($pos==false)        // wenn kein Komma aber Eurozeichen
                                    {
                                    $len=0;         
                                    if ($pos1<3) 
                                        { 
                                        $result5="0"; 
                                        $i=0; 
                                        if ($ergebnisse) echo "*********Guthaben : ".$result5." \n<br>";
                                        }
                                    else $i=--$pos1;
                                    $pos=$pos1;
                                    }
                                else                    // Eurozeichen und Komma gefunden
                                    {
                                    //if ($ergebnisse) echo "*** Eurozeichen und Komma gefunden.\n";
                                    $len=3;
                                    $i=$pos;
                                    }
                                }
                            if ($i>0)
                                {
                                while ( ( (is_numeric($buffer[$i]))  or ($buffer[$i]==",") ) && ($i != 0)) 
                                    {
                                    $i--;
                                    //echo "***".$i."  ".$pos."\n";
                                    }
                                //if ($ergebnisse) echo "***".$i."  ".$pos."   ".$len."\n";
                                $result5=trim(substr($buffer,$i,$pos-$i+$len));
                                if ($ergebnisse) echo "*********Guthaben : ".$result5." \n<br>";
                                }
                            }
                        }
                    if ($entgelte==true)
                        {
                        $entgelte=false;
                        $Ende=strpos($buffer,",");
                        $result5=trim(substr($buffer,0,$Ende+3));
                        if ($ergebnisse) echo "*********Geldguthaben : ".$result5." \n<br>";
                        }
                    if (preg_match('/Verbindungsentgelte:/i',$buffer))
                        {
                        $entgelte=true;
                        }
                    if (preg_match('/Aktuelle Kosten:/i',$buffer))
                        {
                        $pos=strpos($buffer,"Kosten:");
                        $Ende=strpos($buffer,",");
                        if ( ($pos>0) && ($Ende >0) )
                            {
                            $result5=trim(substr($buffer,$pos+7,$Ende-$pos+3+7));
                            if ($ergebnisse) echo "*********Rechnung : ".$result5." \n<br>";
                            }
                        }				
                        
                    }  while ($nobreak); /* ende while buffer schleife */
                    
                /* Ende Auslesung file, register/array, ein paar kosmetische Operationen */

                if ($result1=="") $result1=$config["Tarif"];	// wenn der Username nicht gefunden wurde einen Ersatzwert nehmen
                else $result1.=$tarif1;							// wenn Username gefunden wurde gleich auch mit dem ermittelten Tarif zusammanhaengen

                if ($result7=="") $result7=$result7i;           // Abrechnungszeitraum mit - erkannt      
                
                //$ergebnis="User:".$result1." Nummer:".$result2." Status:".$result4." Wert vom:".$result3." Guthaben:".$result5."\n";
                if ($ausgeben) echo "\n-----------------------------\n";
                if ($ergebnisse) 
                    {
                    echo "User:".$result1." Nummer:".$result2." Wert vom:".$result3." Letzte Aufladung ".$result8." Guthaben:".$result5." Tarif: ".$tarif1."\n";
                    echo "Status:".$result4."   ".$result6." Gesamt Ticket : ".$result4g." Frei : ".$result4f." Verbraucht : ".$result4v." Gültig bis : ".$result7."\n";
                    echo "\n-----------------------------\n";
                    }
                    
                //$ergebnis="User:".$result1." Status:".$result4." Guthaben:".$result5." Euro\n";
                SetValue($phone_User_ID,$result1);
                if ($speichern) echo "--> ".IPS_GetName($phone_User_ID)." : ".$result1."\n";
                //SetValue($phone_Status_ID,$result4);   /* die eigentlich interessante Information */
                //echo ":::::".$result4."::::::\n";
                SetValue($phone_Date_ID,$result3);
                if ($speichern) echo "--> ".IPS_GetName($phone_Date_ID)." : ".$result3."\n";
                $old_cost=(float)GetValue($phone_Bonus_ID);
                $new_cost=(float)$result5;
                SetValue($phone_CL_Change_ID,$new_cost-$old_cost);
                if ($new_cost < $old_cost)
                    {
                    SetValue($phone_Cost_ID, GetValue($phone_Cost_ID)+$old_cost-$new_cost);
                    SetValue($phone_nCost_ID, GetValue($phone_nCost_ID)+$old_cost-$new_cost);
                    SetValue($phone_unchangedDate_ID,date("d.m.Y"));
                    }
                if ($new_cost > $old_cost)
                    {
                    SetValue($phone_Load_ID, GetValue($phone_Cost_ID)-$old_cost+$new_cost);
                    SetValue($phone_nLoad_ID, GetValue($phone_nLoad_ID)-$old_cost+$new_cost);
                    SetValue($phone_unchangedDate_ID,date("d.m.Y"));
                    }
                SetValue($phone_Bonus_ID,$result5);
                if ($speichern) 
                    {
                    echo "--> ".IPS_GetName($phone_CL_Change_ID)." : ".GetValue($phone_CL_Change_ID)."\n";
                    echo "--> ".IPS_GetName($phone_Cost_ID)." : ".GetValue($phone_Cost_ID)."\n";
                    echo "--> ".IPS_GetName($phone_nCost_ID)." : ".GetValue($phone_nCost_ID)."\n";
                    echo "--> ".IPS_GetName($phone_unchangedDate_ID)." : ".GetValue($phone_unchangedDate_ID)."\n";
                    echo "--> ".IPS_GetName($phone_Bonus_ID)." : ".$result5."\n";
                    }						
                                
                if ($result8!="")
                    {
                    SetValue($phone_loadDate_ID,$result8);
                    if ($speichern) echo "--> ".IPS_GetName($phone_loadDate_ID)." : ".$result8."\n";
                    }

                /* Datenvolumen Auswertung, result4 ist geschrieben wenn in einer Zeile gespeichert und die Auswertung am Ende hier gemacht werden kann
                *
                */
                
                if ($result4!="")
                    {
                    $Anfang=strpos($result4,"verbraucht")+10;
                    $Ende=strpos($result4,"frei");
                    $result6=trim(substr($result4,($Anfang),($Ende-$Anfang)));

                    $Anfang=strpos($result4,"bis:")+5;
                    $result7=trim(substr($result4,($Anfang),20));
                    }

                /* Datenvolumen des Tickets wurde ermittelt, in result6 eine Zusammenfassung erstellen. 
                *	 
                *	$result4g="99999 MB";
                *	$result4f="99999 MB frei";
                *	$result4v=" 0 MB verbraucht";
                *
                */

                //echo "------->Analyse \"$result4g\" und \"$result4f\"\n";
                if ($result4g!="")
                    {
                    if ($result4f!="")		
                        {    // noch freies Datenvolumen (Restvolumen) wurde angegeben
                        /*`hier wird das aktuelle Datenvolumen geschrieben */
                        //$result6=" von ".$result4g." wurden ".$result4v." verbraucht und daher sind  ".$result4f.".noch frei.";
                        $result6=" von ".$result4g." sind ".$result4f;
                        $Ende=strpos($result4f,"MB");
                        $restvolumen=(float)trim(substr($result4f,0,($Ende-1)));
                        }
                    else
                        {
                        //echo "------->Analyse \"$result4g\" und \"$result4v\"\n";
                        $Ende=strpos($result4g,"MB");
                        $ticketvolumen=(float)trim(substr($result4g,0,($Ende-1)));
                        $Ende=strpos($result4v,"MB");
                        $verbrauchtesvolumen=(float)trim(substr($result4v,0,($Ende-1)));
                        $restvolumen=$ticketvolumen-$verbrauchtesvolumen;
                        if ($restvolumen<0) $result6=" verbraucht sind ".$result4v." ";
                        else $result6=" von ".$result4g." sind ".$restvolumen." MB frei";
                        }
                    if ($ergebnisse) echo "Restvolumen ist : ".$restvolumen." MB \n";
                    $bisherVolumen=GetValue($phone_Volume_ID);
                    SetValue($phone_Volume_ID,$restvolumen);
                    if (($bisherVolumen-$restvolumen)>0)
                        {
                        SetValue($phone_VolumeCumm_ID,$bisherVolumen-$restvolumen);
                        }
                    else
                        {
                        /* guthaben wurde aufgeladen */
                        SetValue($phone_VolumeCumm_ID,$bisherVolumen);
                        }				
                    }
                else
                    {
                    if ($result4f!="")		
                        {    // noch freies Datenvolumen (Restvolumen) wurde angegeben                        
                        $result6=" verfügbar sind ".$result4f;
                        }
                    elseif ($result4v!="")		
                        {    // noch freies Datenvolumen (Restvolumen) wurde angegeben                        
                        $result6=" verbraucht sind ".$result4v;
                        }
                    else $result6="";
                    }	
                if ($speichern) 
                    {
                    echo "--> ".IPS_GetName($phone_Volume_ID)." : ".GetValue($phone_Volume_ID)."\n";
                    echo "--> ".IPS_GetName($phone_VolumeCumm_ID)." : ".GetValue($phone_VolumeCumm_ID)."\n";
                    }

                /********
                * textuelles Ergebnis zusammenfassen beginnen
                *
                * unterscheiden zwischen postpaid und prepaid: postpaid hat fixe Abrechnungsperiode, prepaid wenn aktiv eine Gültigkeitsdauer bis
                *
                *************************************/

                //echo $result1.":".$result6."bis:".$result7.".\n";
                if ($postpaid==true) /* postpaid tarif, extra anmerken */
                    {
                    if ($result5 != "") $result5=" Rechnung:".$result5." Euro";
                    if ($result6=="")
                        {
                        $ergebnis=$nummer." ".str_pad("(".$result1.")",30)." Postpaid ".$result5;
                        }
                    else
                        {
                        if ($result4unlimited) $ergebnis=$nummer." ".str_pad("(".$result1.")",30)." Postpaid Unlimitiert, Verbraucht : ".$result4v.$result5;
                        else $ergebnis=$nummer." ".str_pad("(".$result1.")",30)." Postpaid ".$result6." bis ".$result7.$result5;
                        }
                    }
                else   /* prepaid tarif */
                    {
                    //echo "Prepaid : ".$nummer."  ".$result7."\n";
                    if ($result6=="")
                        {
                        if ($result7=="")  // Nutzungszeit abgelaufen
                            {                            
                            $ergebnis=$nummer." ".str_pad("(".$result1.")",30)."  Guthaben:".$result5." Euro";
                            }
                        else
                            {
                            if ($result8=="")
                                {
                                $ergebnis=$nummer." ".str_pad("(".$result1.")",30)." gültig bis ".$result7." Guthaben:".$result5." Euro";                                
                                }
                            else 
                                {                                
                                $ergebnis=$nummer." ".str_pad("(".$result1.")",30)." Letzte Aufladung ".$result8.", gültig bis ".$result7." Guthaben:".$result5." Euro";                                
                                }
                            }
                        }
                    else
                        {
                        $ergebnis=$nummer." ".str_pad("(".$result1.")",30)." ".$result6." bis ".$result7." Guthaben:".$result5." Euro";
                        }
                    if ($result7=="")  // Nutzungszeit abgelaufen
                        {
                        if ($result4g=="")
                            {
                            $ergebnis=$nummer." ".str_pad("(".$result1.")",30)."  Guthaben:".$result5." Euro";
                            }
                        else
                            {	
                            $ergebnis=$nummer." ".str_pad("(".$result1.")",30)."  Datenmenge : ".$result4g." Guthaben:".$result5." Euro";
                            }
                        }			
                    }
                if ($type == "file")
                    {
                    if (!feof($handle))
                        {
                        $ergebnis="Fehler: unerwarteter fgets() Fehlschlag\n";
                        }	
                    fclose($handle);
                    }
                }
            else
                {
                $ergebnis="Handle nicht definiert. Kein Ergebnis des Macroscripts erhalten.\n";
                }
            //$ergebnis.=$result4g." ".$result4v." ".$result4f;

            if ($speichern) echo "--> ".IPS_GetName($phone_Summ_ID)." : ".$ergebnis."\n";
            SetValue($phone_Summ_ID,$ergebnis);
            return $ergebnis;
            }

        /* der selbe Text parser soll für Dateien und Register funktionieren 
         * wenn type file ist dann wird einfach die nächste Zeile aus dem File eingelesen
         * sonst geht man davon aus dass $handle ein Array ist und line der Index dafür
         */

        function getfromFileorArray($handle, $type, &$line)
            {
            if  ($type == "file") $buffer = fgets($handle, 4096);/* liest bis zum Zeilenende */
            else 
                {
                if (($line+1)>(count($handle))) return (false);
                $buffer=$handle[$line];
                $line++;
                }
            return($buffer);
            }

        }   // Ende class Guthabenhandler

    /* class API von YahooFinance
     *  __construct
     *  extractModule                               allgemeine Funktion, extract Modul data from larger array
     *  extractIncomeStatementHistory
     *  extractIncomeStatementHistoryQuarterly
     *  extractPrice
     *  extractSummaryDetail
     *
     *  getDataYahooApi
     *
     *  calcAddColumnsOfHistoryQuarterly
     *  combineTablesOfHistory
     *  copyLatestEndDateOfHistory
     *  addTransformColumnsfromtables
     *  doRatingOfTables
     *  rating
     *
     */

    class yahooApi
        {

        /* construct class yahooApi 
         */
        function __construct()
            {
            $displayAvail=array();
            }

        /* general extract
         * Daten die von getDataYahooApi vom Modul parameter modul kommen herausnehmen
         * das bedeutet im result array gibt es einen Index mit dem ticker und dann ein Index mit dem jeweiligen Modul
         * alle Indexe in dem Modul werden gespeichert
         * pro Ticker wird eine Zeile erstellt, eine Spalte heisst dann Ticker
         */

        function extractModule($result,$modul,$debug=false)
            {
            if ($debug) echo "extractModule $modul :\n";
            $inputData=array();
            $indexRow=0;
            foreach ($result as $ticker => $item)
                {
                if ($debug) echo "   Ticker ".str_pad($ticker,10)." :   ";
                foreach ($item[$modul] as $index => $entry)
                    {
                    if ($debug) echo " $index ";

                    $inputData[$indexRow][$index] = $entry;
                    //$displayAvail[$modul][$indexSub]=true;                    
                    }
                $inputData[$indexRow]["Ticker"] = $ticker;
                $indexRow++;                        
                if ($debug) echo "\n";
                }
            return ($inputData);
            }

        /* extractIncomeStatementHistory, Daten die von getDataYahooApi vom Modul incomeStatementHistory kommen herausnehmen
         * das bedeutet im result array gibt es einen Index mit dem ticker und dann ein Index mit dem jeweiligen Modul
         * die Auswertung funktioniert so dass alle Indexe des Moduls durchgegangen werden und auch die Subindexe wenn ein array
         * hier gibt es noch ein unterarray da es historische Werte der letzten 4 Jahre sind
         * pro Ticker wird eine Zeile erstellt, eine Spalte heisst dann Ticker
         *
         */

        function extractIncomeStatementHistory($result,$debug=false)
            {
            if ($debug) echo "extractIncomeStatementHistory :\n";
            $inputData=array();
            $indexRow=0;
            foreach ($result as $ticker => $item)
                {

                //$inputData[$indexRow] = $item["summaryDetail"];               // wir brauchen eine Zeile zumindest
                //print_r($item["incomeStatementHistory"]);
                if (isset($item["incomeStatementHistory"]["incomeStatementHistory"]))
                    {
                    if ($debug) echo "   Ticker ".str_pad($ticker,10)." :   ";
                    foreach ($item["incomeStatementHistory"]["incomeStatementHistory"] as $index => $entry)
                        {
                        if ($debug) echo " $index \n";
                        //print_R($entry);
                        foreach ($entry as $indexSub => $entrySub)
                            {
                            if (is_array($entrySub))
                                {
                                if (isset($entrySub["raw"]))
                                    {
                                    $inputData[$indexRow][$indexSub] = $entrySub["raw"];
                                    $displayAvail["incomeStatementHistory"][$indexSub]="raw";
                                    }
                                else $inputData[$indexRow][$indexSub] = "";
                                }
                            else 
                                {
                                $inputData[$indexRow][$indexSub] = $entrySub;
                                $displayAvail["incomeStatementHistory"][$indexSub]=true;
                                }
                            }
                        $inputData[$indexRow]["Ticker"] = $ticker;
                        $indexRow++;                        
                        }                        
                    if ($debug) echo "\n";
                    }
                }
            return ($inputData);
            } 


        /* extractIncomeStatementHistoryQuarterly, Daten die von getDataYahooApi vom Modul incomeStatementHistoryQuarterly kommen herausnehmen
         * das bedeutet im result array gibt es einen Index mit dem ticker und dann ein Index mit dem jeweiligen Modul
         * die Auswertung funktioniert so dass alle Indexe des Moduls durchgegangen werden und auch die Subindexe wenn ein array
         * hier gibt es noch ein unterarray da es historische Werte sind
         * pro Ticker wird eine Zeile erstellt, eine Spalte heisst dann Ticker
         *
         */

        function extractIncomeStatementHistoryQuarterly($result,$debug=false)
            {
            if ($debug) echo "extractIncomeStatementHistoryQuarterly :\n";
            $inputData=array();
            $indexRow=0;
            foreach ($result as $ticker => $item)
                {
                if (isset($item["incomeStatementHistoryQuarterly"]))
                    {
                    if ($debug) echo "   Ticker ".str_pad($ticker,10)." :   ";
                    //$inputData[$indexRow] = $item["summaryDetail"];               // wir brauchen eine Zeile zumindest
                    //print_r($item["incomeStatementHistoryQuarterly"]);
                    foreach ($item["incomeStatementHistoryQuarterly"] as $index => $entry)
                        {
                        if ($debug) echo " $index \n";
                        //print_R($entry);
                        foreach ($entry as $indexSub => $entrySub)
                            {
                            if (is_array($entrySub))
                                {
                                if (isset($entrySub["raw"]))
                                    {
                                    $inputData[$indexRow][$indexSub] = $entrySub["raw"];
                                    $displayAvail["incomeStatementHistoryQuarterly"][$indexSub]="raw";
                                    }
                                else $inputData[$indexRow][$indexSub] = "";
                                }
                            else 
                                {
                                $inputData[$indexRow][$indexSub] = $entrySub;
                                $displayAvail["incomeStatementHistoryQuarterly"][$indexSub]=true;
                                }
                            }
                        $inputData[$indexRow]["Ticker"] = $ticker;
                        $indexRow++;                        
                        }                        
                    if ($debug) echo "\n";
                    }
                }
            return ($inputData);
            } 


        /* extractPrice, Daten die von getDataYahooApi vom Modul price kommen herausnehmen
        * aus dem json_encoded array die relevanten Daten als array herauskopieren
        * Jeder Ticker, Aktienname Short bekommt eine Zeile
        */

        function extractPrice($result,$debug=false)
            {
            if ($debug) echo "extractPrice :\n";
            $inputData=array();
            $indexRow=0;
            foreach ($result as $ticker => $item)
                {
                if ($debug) echo "   Ticker ".str_pad($ticker,10)." :   ";
                //$inputData[$indexRow] = $item["summaryDetail"];               // wir brauchen eine Zeile zumindest
                foreach ($item["price"] as $index => $entry)
                    {
                    if ($debug) echo " $index ";
                    if (is_array($entry))
                        {
                        if (isset($entry["raw"]))
                            {
                            $inputData[$indexRow][$index] = $entry["raw"];
                            $displayAvail[$index]="raw";
                            }
                        else $inputData[$indexRow][$index] = "";
                        }
                    else 
                        {
                        $inputData[$indexRow][$index] = $entry;
                        $displayAvail["summaryDetail"][$index]=true;
                        }
                    }                        
                if ($debug) echo "\n";
                $inputData[$indexRow]["Ticker"] = $ticker;
                $indexRow++;
                }
            return ($inputData);
            }

        /* extractSummaryDetail, Daten die von getDataYahooApi vom Modul summaryDetail kommen herausnehmen
        * aus dem json_encoded array die relevanten Daten als array herauskopieren
        * Jeder Ticker, Aktienname Short bekommt eine Zeile
        */

        function extractSummaryDetail($result,$debug=false)
            {
            if ($debug) echo "extractSummaryDetail :\n";
            $inputData=array();
            $indexRow=0;
            foreach ($result as $ticker => $item)
                {
                if (isset($item["summaryDetail"]))
                    {
                    if ($debug) echo "   Ticker ".str_pad($ticker,10)." :   ";
                    //$inputData[$indexRow] = $item["summaryDetail"];               // wir brauchen eine Zeile zumindest
                    foreach ($item["summaryDetail"] as $index => $entry)
                        {
                        if ($debug) echo " $index ";
                        if (is_array($entry))
                            {
                            if (isset($entry["raw"]))
                                {
                                $inputData[$indexRow][$index] = $entry["raw"];
                                $displayAvail[$index]="raw";
                                }
                            else $inputData[$indexRow][$index] = "";
                            }
                        else 
                            {
                            $inputData[$indexRow][$index] = $entry;
                            $displayAvail["summaryDetail"][$index]=true;
                            }
                        }                        
                    if ($debug) echo "\n";
                    $inputData[$indexRow]["Ticker"] = $ticker;
                    $indexRow++;
                    }
                }
            return ($inputData);
            }

        /* getDataYahooApi
         * die Daten von der Yahoo Api holen
         *  data        array aus Tickersymbolen
         *  modul       array aus Modulen
         *  connfig
         *
            $modules = [
                'assetProfile', 'balanceSheetHistory', 'balanceSheetHistoryQuarterly', 'calendarEvents',
                'cashflowStatementHistory', 'cashflowStatementHistoryQuarterly', 'defaultKeyStatistics', 'earnings',
                'earningsHistory', 'earningsTrend', 'financialData', 'fundOwnership', 'incomeStatementHistory',
                'incomeStatementHistoryQuarterly', 'indexTrend', 'industryTrend', 'insiderHolders', 'insiderTransactions',
                'institutionOwnership', 'majorDirectHolders', 'majorHoldersBreakdown', 'netSharePurchaseActivity', 'price', 'quoteType',
                'recommendationTrend', 'secFilings', 'sectorTrend', 'summaryDetail', 'summaryProfile', 'symbol', 'upgradeDowngradeHistory',
                'fundProfile', 'topHoldings', 'fundPerformance'];
        *
        * es gibt probleme mit Authorisierung. Workaround 1: https://query1.finance.yahoo.com/v1/test/getcrumb zusätzlicher Parameter :   &crumb=crumb_that_i_just_got
        * https://github.com/ranaroussi/yfinance/issues/1592
        *
        */


        function getDataYahooApi($data,$modul,$config=false,$debug=false)
            {
            $result=array();
            if ($config === false) $config=array();
            if (isset($config["preProcess"])) $preProc=$config["preProcess"];
            else $preProc=false;
            if ((is_array($modul))===false) $modul = [ $modul ];
            $modules="";
            $first=true;
            foreach ($modul as $entry)
                {
                if ($first) 
                    {
                    $modules .= $entry;
                    $first=false;
                    }
                else $modules .= '%2C'.$entry;
                }
            if ($debug>1) echo "----Debug-----$modules\n";
            $initOnce=true;
            foreach ($data as $ticker) 
                {
                //$url = "https://query2.finance.yahoo.com/v10/finance/quoteSummary/$ticker?modules=defaultKeyStatistics%2CassetProfile%2CtopHoldings%2CfundPerformance%2CfundProfile%2CesgScores&ssl=true";
                $url = "https://query2.finance.yahoo.com/v10/finance/quoteSummary/$ticker?modules=".$modules; 
                //$url = "https://query2.finance.yahoo.com/v10/finance/quoteSummary/$ticker?modules=financialData";
                if ($debug) echo "getDataYahooApi, get Data from Yahoos Url $url \n";
                $dataReceived = json_decode(file_get_contents($url), true); 
                //print_r($dataReceived["quoteSummary"]["result"][0]["summaryDetail"]);
                //print_r($dataReceived);
                if ($initOnce)
                    {
                    if ($debug>1) echo "The following information has been requested and received:\n";
                    $i=0;
                    foreach ($dataReceived["quoteSummary"]["result"][0] as $index => $entry)
                        {
                        if ($debug>1) echo "$i   ".$index."\n";
                        $i++;
                        }
                    $initOnce=false;
                    }
                if ($preProc)
                    {
                    if ($debug>1) echo "We do preprocessing of received data:\n";
                    foreach ($modul as $entry) 
                        {
                        if ($debug>1) echo "    Modul $entry\n";
                        switch ($entry)
                            {
                            case "summaryDetail": 
                                if (isset($dataReceived["quoteSummary"]["result"][0]["summaryDetail"]))
                                     { 
                                    if ($debug>1) echo "      found index :";
                                    foreach ($dataReceived["quoteSummary"]["result"][0]["summaryDetail"] as $index => $item)
                                        {
                                        if ($debug>1) echo " $index ";
                                        if (isset($item["raw"])) $result[$ticker][$entry][$index] = $item["raw"];
                                        else $result[$ticker][$entry][$index] = $item;    
                                        }                        
                                    if ($debug>1) echo "\n";
                                     }
                                else echo "summaryDetail, Warning, not available for $ticker.\n";
                                break;
                            case "incomeStatementHistoryQuarterly":
                                if (isset($dataReceived["quoteSummary"]["result"][0]["incomeStatementHistoryQuarterly"]))
                                    {
                                    foreach ($dataReceived["quoteSummary"]["result"][0]["incomeStatementHistoryQuarterly"]["incomeStatementHistory"] as $index => $item)
                                        {
                                        if ($debug>1) echo "      found index : $index : ";
                                        foreach ($dataReceived["quoteSummary"]["result"][0]["incomeStatementHistoryQuarterly"]["incomeStatementHistory"][$index] as $indexSub => $itemSub)
                                            {
                                            if ($debug>1) echo " $indexSub ";
                                            if (isset($itemSub["raw"])) $result[$ticker][$entry][$index][$indexSub] = $itemSub["raw"];
                                            else $result[$ticker][$entry][$index][$indexSub] = $itemSub;    
                                            }                        
                                        if ($debug>1) echo "\n";
                                        }                        
                                    }
                                else echo "incomeStatementHistoryQuarterly, Warning, not available for $ticker.\n";
                                //$result[$ticker][$entry]=$dataReceived["quoteSummary"]["result"][0][$entry]["incomeStatementHistory"];                              
                                break;
                            default:
                                if (isset($dataReceived["quoteSummary"]["result"][0][$entry]))
                                    {
                                    if ($debug)  echo "      found index :"; 
                                    foreach ($dataReceived["quoteSummary"]["result"][0][$entry] as $index => $item)
                                        {
                                        if ($debug) echo " $index ";
                                        }
                                    if ($debug) echo "\n";                                    
                                    $result[$ticker][$entry]=$dataReceived["quoteSummary"]["result"][0][$entry];
                                    }
                                else echo "$entry, Warning, not available for $ticker.\n";
                                break;
                            }
                        }
                    }
                else $result[$ticker]=$dataReceived["quoteSummary"]["result"][0];
                }
            return($result);
            }

       /* addColumns, $inputDataHistory wie $calc
        * gleiche Werte in Spalten zusammenzählen
        * Ticker muss gleich sein, ist Index des Ergebnis, 
        * plausi: in endDate wird das letzte Datum eines Ergebnisses gespeichert, in countCalc die Anzahl der summierten Werte von totalRevenue
        */

        public function calcAddColumnsOfHistoryQuarterly($inputDataHistory,$calc,$debug=false)
            {
            if ($debug) echo "calcAddColumnsOfHistoryQuarterly aufgerufen, Berechnung nach ".json_encode($calc).":\n";
            $inputDataWork=array();           
            foreach ($inputDataHistory as $rowInput => $entry)
                {
                if (isset($entry["Ticker"]))
                    {
                    $row=$entry["Ticker"];         // Zeile mit Tickersymbol als Key
                    $date=$entry["endDate"];
                    if ($debug) echo "$row:".date("d.m.Y",$date)." ";
                    if (isset($inputDataWork[$row]["endDate"]))
                        {
                        if ($inputDataWork[$row]["endDate"]<$date) $inputDataWork[$row]["endDate"] = $date;            // jedes mal schauen ob es ein späteres Datum gibt
                        }
                    else $inputDataWork[$row]["endDate"] = $date;
                    if ($debug) echo date("d.m.Y",$inputDataWork[$row]["endDate"])." ";

                    foreach ($calc as $key => $rule)
                        {
                        if (isset($entry[$key]))            // config aus keys
                            {
                            if ($key=="totalRevenue") 
                                { 
                                if (is_numeric($entry[$key]))                   // > 0 funktioniert nicht, wir machen auch negative Gewinne
                                    {
                                    if (isset($inputDataWork[$row]["countCalc"])) $inputDataWork[$row]["countCalc"]++;
                                    else $inputDataWork[$row]["countCalc"]=1;
                                    } 
                                elseif ($debug)  echo "no num ".$entry[$key]." ";                                    
                                }
                            if (isset($inputDataWork[$row][$key])) 
                                {
                                if (is_numeric($entry[$key]))  $inputDataWork[$row][$key] += $entry[$key];
                                elseif ($debug)  echo "no num ".$entry[$key]." ";                                }
                            else
                                {
                                $inputDataWork[$row]["Ticker"] = $entry["Ticker"];
                                if (is_numeric($entry[$key]))  $inputDataWork[$row][$key] = $entry[$key];
                                elseif ($debug) echo "no num ".$entry[$key]." ";
                                }
                            }
                        }           // ende foreach
                    if ($debug) echo "\n";
                    }           // isset entry ticker
                }
            return ($inputDataWork);
            }

        /* Combine added Quarterlys and yearly Statements
        *
        */
        function combineTablesOfHistory($inputDataWork,$inputDataIncome)      
            {
            $inputDataMerged=array(); $row=0;                             // aggregierte Quartalswerte hinzufügen
            foreach ($inputDataWork as $ticker => $entry)
                {
                if ( (isset($entry["countCalc"])) && ($entry["countCalc"]>3) ) 
                    {
                    $inputDataMerged[$row]=$entry;
                    $row++;
                    }
                } 
            foreach ($inputDataIncome as $ticker => $entry)
                {
                $inputDataMerged[$row]=$entry;
                $row++;
                }
            return($inputDataMerged);
            }

    
        /* generate inputDataRevenue from inputDataMerged
         * overwrite entries as long we have a younger higher value in time)
         */
        function copyLatestEndDateOfHistory($inputDataMerged)
            {
            $inputDataRevenue=array();
            foreach ($inputDataMerged as $row => $entry)
                {
                //echo "Zeile $row : ";
                $ticker=$entry["Ticker"];
                $date=$entry["endDate"];
                if (isset($inputDataRevenue[$ticker])) 
                    {
                    if ($inputDataRevenue[$ticker]["endDate"]<$date) $inputDataRevenue[$ticker]=$entry;
                    }
                else $inputDataRevenue[$ticker]=$entry;
                }
            return ($inputDataRevenue);
            }

        /* add inputDataRevenue.ticker.totalRevenue to inputData.row , add inputDataPrice.row.shortName,regularMarketTime,regularMarketPrice based on Ticker */
        function addTransformColumnsfromtables(&$inputData,$inputDataPrice,$inputDataRevenue,$config=false)
            {
            $inputDataPriceTicker=array();
            foreach ($inputDataPrice as $rowInput => $entry) $inputDataPriceTicker[$entry["Ticker"]]=$entry; 

            // add inputDataRevenue.ticker.totalRevenue to inputData.row , add inputDataPrice.ticker.shortName,regularMarketTime,regularMarketPrice 
            foreach ($inputData as $row => $entry)              // die arrays die angehängt werden, müssen auf ticker indexieren
                {
                $ticker=$entry["Ticker"];
                if (isset($inputDataRevenue[$ticker])) $inputData[$row]["totalRevenue"]=$inputDataRevenue[$ticker]["totalRevenue"]; 
                if (isset($inputDataPriceTicker[$ticker]))   
                    {
                    // add
                    $inputData[$row]["shortName"]=$inputDataPriceTicker[$ticker]["shortName"]; 
                    $inputData[$row]["regularMarketTime"]=$inputDataPriceTicker[$ticker]["regularMarketTime"];
                    $inputData[$row]["regularMarketPrice"]=$inputDataPriceTicker[$ticker]["regularMarketPrice"];
                    // calc
                    $inputData[$row]["regularChange"]=round(($inputData[$row]["regularMarketPrice"]/$inputData[$row]["previousClose"]-1),3);            // *100 in der Formatierung
                    $inputData[$row]["outstandingMShares"]=round(($inputDataPriceTicker[$ticker]["marketCap"]/$inputDataPriceTicker[$ticker]["regularMarketPrice"]/1000),0)/1000;
                    // price compare to 52week borders, 0 = 10% below low , 1 = 10% above high, marketprice is percentage 1,1*high=100%,0.9*low=0%, marketprice is percentage of price*high*1,1/low*0,9
                    $reallyLow=$inputData[$row]["fiftyTwoWeekLow"]*0.9;
                    $rangeLowHigh=($inputData[$row]["fiftyTwoWeekHigh"]*1.1)-$reallyLow;
                    $inputData[$row]["priceto52weekRange"]=($inputData[$row]["regularMarketPrice"]-$reallyLow)/$rangeLowHigh;           // add to rate
                    $inputData[$row]["rangeToPrice"]=($rangeLowHigh/$inputData[$row]["regularMarketPrice"]);           // add to rate
                    // range compared to price, small is 1 and big is 0
                    }
                } 
            }

        /* Spaltenweise Auswertungen, Statistik, inputData
        * 
        */
        function doRatingOfTables(&$inputData,$configRating)
            {
            //echo "doRatingOfTables, Statistics:\n";
            $statistics = new statistics();        
            //$config = $statistics->setConfiguration($configInput);        // Konfiguration setzen


            $maxmin=array();         // Speicherplatz Ergebnis zur Verfügung stellen
            $maxminClass=array();         // Speicherplatz Berechnung zur Verfügung stellen

            foreach ($configRating as $key=>$resultkey)
                {
                $maxminClass[$key]   = new maxminCalc($maxmin,$key);       	                        // Instanz hat variablen Namen
                }

            foreach ($inputData as $row => $entry)              // zeilenweise durchgehen, Statistik anwenden
                {
                foreach ($configRating as $key=>$resultkey)
                    {
                    $maxminClass[$key]->addValue($entry[$key]);      // kann auch skalare Werte
                    }
                }
            foreach ($configRating as $key=>$resultkey)
                {
                $maxminClass[$key]->calculate(); 
                }
            //print_R($maxmin);
            //--------------------------
            $this->rating($inputData,$configRating,$maxminClass);

            foreach ($inputData as $row => $entry)              // die arrays die angehängt werden, müssen auf ticker indexieren
                {
                $inputData[$row]["rate"]=0.3*$inputData[$row]["ratePriceToSalesTrailing12Months"]+0.4*$inputData[$row]["rateforwardPE"]+0.3*$inputData[$row]["rateVolatility"];
                }
            }

        function rating(&$inputData,$keys,$maxminClass)
            {
            if (is_array($keys)===false) return false;
            foreach ($keys as $key=>$resultkey)
                {
                foreach ($inputData as $row => $entry)              // zeilenweise durchgehen, Statistik anwenden, wenn max 1, wenn min 0
                    {
                    $ergebnis =         $maxminClass[$key]->rating($entry[$key]); 
                    $inputData[$row][$resultkey]=$ergebnis;
                    }
                }
            }



        }       // ende class yahooApi

    /* class API von Zamg, Geosphere
     *  __construct
     *
     *  getDataZamgApi
     *
     *
     */

    class zamgApi
        {

        protected $data=array();

        /* zamgApi::getDataZamgApi
         * stores as data in class, analysis takes place in other functions
         *
         *
         * die Daten von der Zamg Api holen
         *  data        array aus Tickersymbolen
         *  modul       array aus Modulen
         *  config
         *
         */
        function getDataZamgApi($data,$modul,$config=false,$debug=false)
            {
            if ($debug) echo "getDataZamgApi(\n";
            if (isset($config["StartTime"])) $start = "&start=".date("Y-m-d\TH:i:s.000\Z",$config["StartTime"]);
            else $start = "&start=2022-01-01T00:00:00.000Z";
            if (isset($config["EndTime"]))   $end =   "&end=".date("Y-m-d\TH:i:s.000\Z",$config["EndTime"]);
            else $end =   "&end=".date("Y-m-d\TH:i:s.000\Z");

            $url='https://dataset.api.hub.geosphere.at/v1'.$modul;
            //parameters=RR&parameters=SA&parameters=TN&parameters=TX
            $parameters="?";
            foreach ($data as $data) $parameters .= "&parameters=".$data;
            //$ch = curl_init('https://dataset.api.hub.geosphere.at/v1/grid/historical/spartacus-v2-1d-1km?parameters=RR&parameters=SA&parameters=TN&parameters=TX&start=2022-02-01T00%3A00%3A00.000Z&end=2023-10-22T00%3A00%3A00.000Z&bbox=48.25%2C16.30%2C48.39%2C16.37&output_format=geojson&filename=SPARTACUS+-+Spatial+Dataset+for+Climate+in+Austria+Datensatz_20220201_20231022');
            //$ch = curl_init($url.'?parameters=RR&parameters=SA&parameters=TN&parameters=TX&start=2022-02-01T00%3A00%3A00.000Z&end=2023-10-22T00%3A00%3A00.000Z&bbox=48.25%2C16.30%2C48.39%2C16.37&output_format=geojson&filename=SPARTACUS+-+Spatial+Dataset+for+Climate+in+Austria+Datensatz_20220201_20231022');
            //$ch = curl_init($url.'?'.$parameters.'&start=2022-02-01T00%3A00%3A00.000Z&end=2023-10-22T00%3A00%3A00.000Z&bbox=48.25%2C16.30%2C48.39%2C16.37&output_format=geojson&filename=SPARTACUS+-+Spatial+Dataset+for+Climate+in+Austria+Datensatz_20220201_20231022');
            $ch = curl_init($url.'?'.$parameters.$start.$end.'&bbox=48.25%2C16.30%2C48.39%2C16.37&output_format=geojson&filename=SPARTACUS+-+Spatial+Dataset+for+Climate+in+Austria+Datensatz_20220201_20231022');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $this->data=json_decode($result,true);
            return($result);
            }

        /* when grid da ist fetched, it is possible to find the index of the grid based data bear our pos
         */
        function getIndexNearPosOfGrid(&$pos1)
            {
            foreach ($this->data as $index=>$entry) 
                {
                $d=0; $dmax=2;
                if ( ($index=="features") )     // der Reihe nach die Punkte
                    {
                    foreach ($entry as $subindex => $subentry)
                        {
                        //echo "$subindex ".$subentry["type"]."\n";
                        foreach ($pos1 as $posIndex => $pos)
                            {
                            $abstand = abs($subentry["geometry"]["coordinates"][1]-$pos1[$posIndex]["north"])+abs($subentry["geometry"]["coordinates"][0]-$pos1[$posIndex]["east"]);
                            if (( (isset($pos1[$posIndex]["diff"]["min"])) && ($abstand<$pos1[$posIndex]["diff"]["min"]) ) || (isset($pos1[$posIndex]["diff"]["min"])===false) )
                                {
                                $pos1[$posIndex]["diff"]["min"]=$abstand;
                                $pos1[$posIndex]["diff"]["subindex"]=$subindex;
                                }
                            }
                        //echo "$subindex ".$subentry["geometry"]["type"]." N".nf($subentry["geometry"]["coordinates"][1],4)."  E".nf($subentry["geometry"]["coordinates"][0],4);
                        //echo "     $abstand    ".$pos1["north"]."  ".$pos1["east"]."   ".($subentry["geometry"]["coordinates"][1]-$pos1["north"])." * ".($subentry["geometry"]["coordinates"][0]-$pos1["east"]);
                        //echo "\n";
                        }
                    //echo "\n";
                    }
                }
            return (true);
            }

        /* zamg hat einen Überblick über alle Module
         */
        function getAvailableModules()
            {
            $ch = curl_init('https://dataset.api.hub.geosphere.at/v1/datasets');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $config = json_decode($result,true);
            return($config);                
            }

        /* einen Überblick bekommen über die gespeicherten Daten
         */
        function showData()
            {
            echo "showData fetched from web:\n";
            $pos1=array();
            //print_r($this->data);             // zu gross
            foreach ($this->data as $index=>$entry)             // modul /grid/historical/spartacus-v2-1d-1km
                {
                echo "$index ";
                if ( ($index=="media_type") || ($index=="type") || ($index=="version") || ($index=="bbox") )
                    {
                    print_R($entry);
                    }
                echo "\n";
                }
            // creates timeSeries
            foreach ($this->data as $index=>$entry) 
                {
                $d=0; $dmax=10;
                if ( ($index=="timestamps") )
                    {
                    echo "$index \n";
                    foreach ($entry as $subindex => $subentry)
                        {
                        $timeSeries[$subindex]["TimeStamp"]=strtotime($subentry);
                        if ($d<$dmax) 
                            {
                            echo "   $subindex ";
                            print_R($subentry);
                            echo "\n";
                            }
                        if ($d==$dmax) echo "   ....\n";
                        $d++;
                        }
                    echo "\n";        
                    }
                }
            // creates posSeries    
            foreach ($this->data as $index=>$entry) 
                {
                $d=0; $dmax=2;
                if ( ($index=="features") )     // der Reihe nach die Punkte
                    {
                    echo "$index [n] geometry\n";
                    foreach ($entry as $subindex => $subentry)          // number, Grid Position, alle Positionen durchgehen
                        {
                        //echo "$subindex ".$subentry["type"]."\n";
                        $posSeries[$subindex]["north"]=$subentry["geometry"]["coordinates"][1];
                        $posSeries[$subindex]["east"]=$subentry["geometry"]["coordinates"][0];
                        foreach ($pos1 as $posIndex => $pos)
                            {
                            $abstand = abs($subentry["geometry"]["coordinates"][1]-$pos1[$posIndex]["north"])+abs($subentry["geometry"]["coordinates"][0]-$pos1[$posIndex]["east"]);
                            if (( (isset($pos1[$posIndex]["diff"]["min"])) && ($abstand<$pos1[$posIndex]["diff"]["min"]) ) || (isset($pos1[$posIndex]["diff"]["min"])===false) )
                                {
                                $pos1[$posIndex]["diff"]["min"]=$abstand;
                                $pos1[$posIndex]["diff"]["subindex"]=$subindex;
                                }
                            }
                        echo "$subindex ".$subentry["geometry"]["type"]." N".nf($subentry["geometry"]["coordinates"][1],4)."  E".nf($subentry["geometry"]["coordinates"][0],4);
                        //echo "     $abstand    ".$pos1["north"]."  ".$pos1["east"]."   ".($subentry["geometry"]["coordinates"][1]-$pos1["north"])." * ".($subentry["geometry"]["coordinates"][0]-$pos1["east"]);
                        echo "\n";
                        }
                    echo "\n";
                    }
                }

            foreach ($this->data as $index=>$entry) 
                {
                $d=0; $dmax=2;
                if ( ($index=="features") )     // der Reihe nach die Punkte
                    {
                    echo "$index [n] properties parameters\n";
                    $once=true;
                    foreach ($entry as $subindex => $subentry)
                        {
                        //echo "    $subindex \n";              // Grid Position
                        if ($once)
                            {
                            foreach ($subentry["properties"]["parameters"] as $ref => $refentry) 
                                {
                                echo "     $ref\n";
                                foreach ($refentry as $subref=>$subrefdata) 
                                    {
                                    echo "         $subref  ";
                                    if ( ($subref == "name") || ($subref == "unit") ) echo   $subrefdata;
                                    echo "\n";
                                    }                           
                                }
                            $once=false;
                            }
                        }
                    }       // endif
                }

            }
        
        /* getDataAsTimeSeries, aus internem Speicher analysieren und ausgeben
         * data format, wir brauchen nur die folgenden indexe
         *      timestamps->subindex
         *      features  ->subindex->properties->parameters->RR->data->ref->value      // monthly Table
         *
         */
        function getDataAsTimeSeries($data,$indexToFetch,$debug=false) 
            {
            if ($data=="") $data=["RR"];
            // creates timeSeries
            $timeSeries=array();
            foreach ($this->data as $index=>$entry) 
                {
                if ( ($index=="timestamps") )
                    {
                    foreach ($entry as $subindex => $subentry)
                        {
                        $timeSeries[$subindex]["TimeStamp"]=strtotime($subentry);
                        }
                    }
                }
            $series=array();            //return data as series
            if ($debug) 
                {
                echo "getDataAsTimeSeries Data ".json_encode($data)." Index ".json_encode($indexToFetch)."\n";
                echo "  Dataformat Overview:\n";
                foreach ($this->data as $index=>$entry) echo "   $index \n";
                }
            foreach ($this->data as $index=>$entry) 
                {
                $d=0; $dmax=2;
                if ( ($index=="features") )     // der Reihe nach die Punkte
                    {
                    if ($debug) echo "$index \n";
                    foreach ($data as $dataSelected)
                        {
                        if ($debug) echo "Select $dataSelected from stored Database:\n";
                        foreach ($entry as $subindex => $subentry)
                            {
                            foreach ($subentry["properties"]["parameters"][$dataSelected]["data"] as $ref => $value) 
                                {
                                foreach ($indexToFetch as $subref => $subindexToSearch)
                                    {
                                    if ( $subindex==$subindexToSearch )
                                        {
                                        $series[$dataSelected][$subindex][$ref]["Value"]=$value;   
                                        $series[$dataSelected][$subindex][$ref]["TimeStamp"]=$timeSeries[$ref]["TimeStamp"];
                                        if ($debug)
                                            {
                                            echo "------------------------------------------------\n";
                                            echo "    $subindex \n";
                                            }
                                        if ( ($d<$dmax) && $debug)
                                            {
                                            print_R($subentry["properties"]["parameters"][$dataSelected]["data"]);
                                            echo "\n";
                                            }
                                        $d++;
                                        }
                                    }
                                }
                            }
                        }
                    if ($debug) echo "\n";
                    }       // endif
                }
            return($series);
            }



        }

?>