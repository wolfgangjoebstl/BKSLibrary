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

/* Selenium Handler
 * Selenium läuft normalerweise am Server und nicht in der vM Ware.
 * wird über die IP ADresse mit einer Portnummer 4444 kontaktiert.
 *
 * für die Automatisiserung der Abfragen gibt es verschieden Klasssen
 *      SeleniumHandler     managed die Handles des Selenium Drivers, offene Tabs, werden von den folgenden Klassen erweitert
 *
 *      SeleniumYahooFin    extends SeleniumHandler, de.finance.yahoo.com Abfrage
 *      SeleniumLogWien
 *      SeleniumEVN         Smart Meter reading from Niederösterreich Netz Portal 
 *      SeleniumDrei
 *      SeleniumIiyama
 *      SeleniumEasycharts
 *
 *      SeleniumOperations  damit werden die Funktionen hergestellt
 *
 *      SeleniumUpdate
 *
 * SeleniumDrei sorgt für die individuellen States und SeleniumOperations für die Statemachine
 *
 * Unterschiedliche herangehensweisen für function runAutomatic
 *      YahooFin    ConsentWindow, click when avalable
 *      LogWien     Logout, then Login
 *      EVN         ConsentWindow, Logout, then Login
 *
 */

//namespace Facebook\WebDriver;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

//require_once 'vendor/autoload.php';
require_once(IPS_GetKernelDir().'scripts\vendor\autoload.php');

/* wenn der Selenium Handler gestartet wird können die Tabs schon offen sein
 * dann diese wiederverwenden, sonst neu aufmachen
 *
 * construct
 * setConfiguration     spezifische konfiguration speichern
 * getConfiguration     konfiguration lesen
 * syncHandles          handle ist gespeichert mit Index als webIndex und der Wert dem kurzen Namen, dem identifier, Tab
 * isTab
 * readFailure
 * updateHandles        schreibt $handle in die class 
 * analyseFailure
 * initHost
 * getHost
 * updateUrl
 * getUrl
 * check Url
 * maximize
 * pressButtonIf
 * pressSubmitIf
 * sendKeysToFormIf
 * getTextIf
 * getInnerHtml
 * analyseHtml
 * DOMinnerHTML
 * queryFilterHtml
 * extractFilterHtml
 * getHtmlIf
 * quitHost
 *
 *
 */

class SeleniumHandler
    {

    protected static   $webDriver;
    protected   $active;
    protected   $ipsOps;
    protected   $CategoryIdData;            /* die RESULT Kategorie, da sind alle Ergebnisse pro Klasse drinnen */

    public      $title;                  /* history of Titels */
    private     $index;             /* actual index of title */

    public      $handle;                /* Id of each Tab */
    private     $tab;
    private     $failure;

    function __construct($active=true)
        {
        $this->active=$active;   
        $this->failure="";
        $this->ipsOps = new ipsOps();    

        $guthabenHandler = new GuthabenHandler(true,true,true);         // Steuerung für parsetxtfile
        $categoryId = $guthabenHandler->getCategoryIdSelenium();
        $this->CategoryIdData =IPS_GetObjectIdByName("RESULT",$categoryId);        

        }

    /* Typische Konfiguration, die übergeben wird und in jeder Klasse gespeichert werden:
        "DREI"       =>  array (
        "URL"       => "www.drei.at",
        "CLASS"     => "SeleniumDrei",
        "CONFIG"    => array(
                    "WebResultDirectory"    => $config["WebResultDirectory"],
                                ),                            
                        ),                                            
     */

    public function setConfiguration($configuration)
        {
        $this->configuration = $configuration;
        }

    public function getConfiguration()
        {
        return($this->configuration);
        }

    /* InputCsv unterstützen
     * Input ist config aus inputCsv, Beispielroutine    
     $config = $guthabenHandler->getSeleniumHostsConfig()["Hosts"];
     foreach ($config as $host => $entry)     {
        switch (strtoupper($host))        {
            case "LOGWIEN"
                ->getInputCsvFiles($entry["INPUTCSV"])
     */
    function getInputCsvFiles($config,$debug=false)
        {
        if ($debug) echo "getInputCsvFiles(".json_encode($config)."\n";
        $dosOps = new dosOps();
        $archiveOps = new archiveOps();
        if ( ($inputDir = $this->getDirInputCsv($config,$debug))===false) return(false);                        //Input Verzeichnis suchen 
        if ( ($filesToRead = $this->getFilesInputCsv($config,$debug))===false) return (false);

        if ($debug) echo "    Files to Read from : ".json_encode($filesToRead)."<br>";
        $config=$archiveOps->setConfigForAddValues($config);           //parse config file, done twice, also in readFileCsv
        $indexCols=$config["Index"];
        $debug1=false;
        $fileinfo = $dosOps->writeDirToArray($inputDir, $filesToRead); 
        //print_R($fileinfo);                                                     // das sind nur die Filenamen und Directories
        //$files = $fileinfo;                     // files wird von readFileCsv ergänzt um Einzelwerte
        foreach ($filesToRead as $file)
            {
            $result=array();                                // keine Summe aus allen Dateien machen, sondern Datei für Datei auswerten, daher vorher immer init
            $dateityp=@filetype( $inputDir.$file );     
            if ($debug) echo "    Check $dateityp $inputDir$file \n";
            if ($dateityp == "file")
                {
                $index=false;
                foreach ($fileinfo as $index => $entry) 
                    {
                    if (isset($entry["Filename"]))
                        {
                        if ($entry["Filename"] == $file) break;     
                        }
                    else echo "No Filename as index where we can add information : ".json_encode($entry)."\n";
                    }
                if ($debug) echo "    Filename $file als $index gefunden.\n";               
                $fileOps = new fileOps($inputDir.$file);             // Filenamen gleich mit übergeben, Datei bleibt in der Instanz hinterlegt
                //$index=[];                            // erste Zeile als Index übernehmen
                //$index=["Date","Time","Value"];         // Date und Time werden gemerged
                //if (isset($config$index=["DateTime","Value","Estimate","Dummy"];                                             // Spalten die nicht übernommen werden sollen sind mit Indexwert false
                //$index=["DateTime","Value","Estimate","Dummy"];
                                            // Ergebnis
                //if ($debug1) echo "readFileCsv mit Config ".json_encode($config)." und Index ".json_encode($indexCols)." aufgerufen.\n";       // readFileCsv(&$result, $key="", $index=array(), $filter=array(), $debug=false)
                $status = $fileOps->readFileCsv($result,$config,$indexCols,[],$debug1);                  // Archive als Input, status liefert zu wenig Informationen
                //print_r($fileOps->analyze);
                //print_R($status);
                //echo "-----------\n";  print_r($status["columns"]);
                $firstDate = array_key_first($status["lines"]);
                $lastDate  = array_key_last ($status["lines"]);
                $periode = $lastDate-$firstDate;
                $count=sizeof($status["lines"]);
                $interval=$periode/($count-1);
                if ($debug) echo "beinhaltet $count Werte von ".date("d.m.Y H:i",$firstDate)." bis ".date("d.m.Y H:i",$lastDate)." , Periode ".nf($periode,"s")." Intervall ".nf($interval,"s")." \n";
                $fileinfo[$index]["Count"] = $count;
                $fileinfo[$index]["FirstDate"] = $firstDate;
                $fileinfo[$index]["LastDate"]  = $lastDate;
                $fileinfo[$index]["Periode"]  = $periode;
                $fileinfo[$index]["Interval"]  = $interval;
                $fileinfo[$index]["Analyze"]  = $fileOps->analyze;
                }
            }
        return ($fileinfo);
        }
    /* files anhand der Config bestimmen
     */
    function getFilesInputCsv($config,$debug=false)
        {
        if ($debug) echo "getFilesInputCsv(".json_encode($config)."\n";
        $dosOps = new dosOps();
        if ($inputDir = $this->getDirInputCsv($config,$debug))                        //Input Verzeichnis suchen 
            {
            if ($debug) 
                {
                echo "Look for Input files in ";
                $dosOps->writeDirStat($inputDir);                    // Ausgabe eines Verzeichnis   
                }
            $files=$dosOps->readdirToArray($inputDir);                                      
            }
        else return (false);         // nicht weitermachen wenn diser Input Parameter fehlt                    
        if (isset($config["InputFile"]))
            {
            $filename=$config["InputFile"];
            if ($debug) echo "Input Filename is \"$filename\".\n";
            $filesToRead = $dosOps->findfiles($files,$filename,false);           // true für Debug
            if ($filesToRead==false) return (false);
            }
        else return (false);         // nicht weitermachen wenn diser Input Parameter fehlt       
        return ($filesToRead);
        }

    /* Dir anhand der Config bestimmen
     */
    function getDirInputCsv($config,$debug=false)
        {
        if ($debug) echo "getDirInputCsv(".json_encode($config)."\n";
        $dosOps = new dosOps();
        if (isset($config["InputDir"]))                        //Input Verzeichnis suchen 
            {
            $inputDir=$config["InputDir"];
            $verzeichnis=$dosOps->getWorkDirectory();
            return ($dosOps->correctDirName($verzeichnis.$inputDir));          // richtiges Abschlusszeichen / oder \
            }
        else return (false);         // nicht weitermachen wenn diser Input Parameter fehlt                    
        }

    /* TargetId anhand der Config bestimmen
     */
    function getTargetIdInputCsv($config,$debug=false)
        {
        $oid=false;
        if ($debug) echo "getTargetIdInputCsv(".json_encode($config)."\n";
        if (isset($config["Target"]["Name"]))
            {
            $targetName = $config["Target"]["Name"];
            if ($debug) echo "   Get the new register, Modul $modul mit Category $categoryIdResult und Register Name $targetName.\n";
            $oid = IPS_GetObjectIDByName($targetName,$targetCategory);         // kein Identifier, darf in einer Ebene nicht gleich sein
            if ($debug) echo "   Use the Register mit OID $oid und Name ".IPS_GetName($oid).".\n";
            }
        elseif (isset($config["Target"]["OID"]))
            {
            $oid=$config["Target"]["OID"];
            if ($debug) echo "   Get and use the Register mit OID $oid und Name ".IPS_GetName($oid).".\n";
            } 
        else return(false);
        return($oid);
        }

    /* handle ist gespeichert mit Index als webIndex und der Wert dem kurzen Namen, dem iodentifier, Tab
     */

    function syncHandles($handles, $debug=false)
        {
        $result=array();
        foreach ($handles as $entry)    // index ist 0...x
            {
            if ($debug) echo "look for \"$entry\" in ".json_encode($this->handle)."\n";
            if (isset($this->handle[$entry])) $result[$this->handle[$entry]] = $entry;
            else if ($debug) echo "unknown $entry.\n";
            }
       //echo "syncHandles return ".json_encode($result)."\n";
        return($result);
        }

    /* feststellen ob ein key bereits als Tab verwendet wird */

    function isTab($tab)
        {
        if ($this->handle)
            {            
            foreach ($this->handle as $key => $entry)    // index ist 0...x
                {
                if ($entry == $tab) return($key);
                }
            }
        return(false);
        }


    /* einen abgespeicherten Fehler vollständig auslesen */

    function readFailure()
        {
        return($this->failure);
        }

    /* schreibt $handle in die class */

    function updateHandles($handle, $debug=false)
        {
        if ($debug) echo "updateHandles, with basic check of validity: $handle\n";
        $input=json_decode($handle,true);            // handle:  index => entry   Beispiel ORF => CDwindow-342C42117187FA5E15E8A961F380A715 , decode erzeugt ein array oder false
        if (is_array($input))
            {
            foreach ($input as $index => $entry)
                {
                if (strlen($entry)>20)      // Enträge für handle kleiner 20 Zeichen ignorieren
                    {
                    if ((isset($this->handle[$entry])) && ($this->handle[$entry] != $index) ) 
                        {
                        if ($debug) echo "update of $entry needed with $index former was ".$this->handle[$entry].".\n";
                        $this->handle[$entry]=$index;
                        }
                    else $this->handle[$entry]=$index;
                    }
                }
            }
        else echo "Fehler, Handle \"$handle\" ist kein json array. \n";            
        if ($debug) 
            {
            print_R($this->handle);
            echo "----------------------------\n";
            }
        }

    /* initHost und andere try catch Fehlermeldungen von Selenium analysieren und in eine leserliche Form bringen
     *
     */

    function analyseFailure($failure,$debug=false)
        {
        $this->failure = $failure;
        $pos1=strpos($this->failure,"No active session with ID");
        $failureShort=false;
        if ($pos1===0) $failureShort=$this->failure;                    // "No active session samt ID ist die Fehlermeldung
        else
            {
            $pos1=strpos($this->failure,"session not created: This version of");
            if ($pos1===0)
                {
                $pos2=strpos($this->failure,"Build info:");
                if ($pos2>$pos1) $failureShort = substr($this->failure,$pos1,$pos2-$pos1);   
                }
            if ($failureShort === false) $failureShort = substr($this->failure,0,6000);
            }
        if ($debug) echo "analyseFailure, Failure short : \"$failureShort\"   \n";

        return($failureShort);
        }

    /* $hostIP      'http://10.0.0.34:4444/wd/hub'
     * browser   Chrome
     *
     * es wird eine SessionID übergeben, das ist aber nur die ID der Variable die die SessionID beinhaltet
     * webDriver ist static und steht allen übergeordneten classes zur Verfügung
     * 
     */
    function initHost($hostIP,$browser="Chrome",$sessionID=false, $debug=false)
        {
        $result=array();
        if ($this->active===false) return;
        if ( (strtoupper($browser))=="CHROME") $capabilities = DesiredCapabilities::chrome();
        if ($sessionID && (GetValue($sessionID)!="") )
            {
            $session=GetValue($sessionID);
            if ($debug) echo "SeleniumHandler::initHost($hostIP,$browser) mit Session $session aufgerufen.\n";
            // static RemoteWebDriver createBySessionID($session_id, $selenium_server_url = 'http://localhost:4444/wd/hub')
            try { 
                $webDriver = RemoteWebDriver::createBySessionID($session, $hostIP);
                $session = $webDriver->getSessionID();
                $handles = $webDriver->getWindowHandles();
                }
            catch (Exception $e) 
                { 
                $failureShort = $this->analyseFailure($e->getMessage());
                if ($failureShort=="Curl error thrown fo") echo "Es sieht so aus als wäre Selenium nicht gestartet.\n";
                elseif ($failureShort=="session not created: This version of ChromeDriver only suppo") echo "Neuesten ChromeDriver laden.\n";
                else 
                    {
                    //echo "initHost Fehler erkannt :\"$failureShort\"\n";
                    if ($debug) echo "  -->selenium Webdriver mit Session $session nicht gestartet. Noch einmal ohne Session probieren. Fehlermeldung $failureShort n";
                    }
                $this->handle=array();      // Handle array loeschen
                try { 
                    $webDriver = RemoteWebDriver::create($hostIP, $capabilities); 
                    }
                catch (Exception $e) 
                    { 
                    $failureShort = $this->analyseFailure($e->getMessage());
                    if ($debug) echo "  -->selenium Webdriver nicht gestartet. Fehlermeldung \"$failureShort\" Bitte starten.\n"; 
                    $result["Failure"]["Full"]  = $this->failure;
                    $result["Failure"]["Short"] = $failureShort;
                    return ($result); 
                    }
                }
            static::$webDriver = $webDriver; 
            }
        else
            {
            if ($debug) echo "SeleniumHandler::initHost($hostIP,$browser) aufgerufen.\n";
            try { 
                $webDriver = RemoteWebDriver::create($hostIP, $capabilities); 
                static::$webDriver = $webDriver; 
                }
            catch (Exception $e) 
                { 
                $failureShort = $this->analyseFailure($e->getMessage());
                //echo "Failure short : \"$failureShort\"   \n";
                if ($failureShort=="Curl error thrown fo") echo "Es sieht so aus als wäre Selenium nicht gestartet.\n";
                elseif ($failureShort=="session not created: This version of ChromeDriver only suppo") echo "Neuesten ChromeDriver laden.\n";
                else 
                    {
                    //echo "Fehler erkannt :\"$failureShort\"\n";
                    if ($debug) echo "  -->selenium Webdriver nicht gestartet. Fehlermeldung ".$e->getMessage()." Bitte starten.\n"; 
                    }
                $result["Failure"]["Full"]  = $this->failure;
                $result["Failure"]["Short"] = $failureShort;
                return ($result); 
                }
            }
        if ($sessionID) 
            {
            $session = static::$webDriver->getSessionID();
            SetValue($sessionID,$session);
            if ($debug) echo "  -->selenium Webdriver erfolgreich gestartet. Session: $session\n";
            }
        else if ($debug) echo "  -->selenium Webdriver erfolgreich gestartet.\n";
        $this->title=array();
        $this->index=0;
        //$this->handle=array();

        return($this->syncHandles(static::$webDriver->getWindowHandles()));
        }

    /* eine Webseite als Tab öffnen, 
     * zur Wiedererkennung von bereits geöffneten Tabs gibt es einen Index (einfacher Name)
     * Intern in der class gespeichert ist im array handle eine Tabelle mit handle => tab index
     * wenn der tab index bereits bekannt ist wird zum Handle gesprungen.
     * Rückgabewert ist der Handle, entweder der bekannte oder ein neuer
     *
     */

    function getHost($host,$tab=false,$debug=false)
        {
        if ($this->active===false) return;
        $host = $this->checkUrl($host);
        if ($debug) echo "getHost für $host und Index $tab:\n";
        //print_R($this->handle);
        if  ($tab !== false) 
            {
            $tabHandle=$this->isTab($tab); 
            if ($tabHandle)
                {
                if ($debug) echo "Handle für $host schon bekannt, switchTo $tab ($tabHandle).\n";
                static::$webDriver->switchTo()->window($tabHandle);
                }
            else
                {
                if ( ($this->handle) && ((count($this->handle))>0) )
                    {
                    /* noch einen Tab aufmachen */
                    if ($debug) echo "Handle für $host nicht bekannt, oeffne neuen Tab $tab.\n";                
                    $before = static::$webDriver->getWindowHandles();
                    $result = static::$webDriver->executeScript("window.open('about:blank','_blank');", array());
                    //$this->webDriver->wait()->until(WebDriverExpectedCondition::numberOfWindowsToBe(count($this->handle)+1));
                    //sleep(1);
                    $after = static::$webDriver->getWindowHandles();
                    $result = array_diff($after,$before);
                    foreach ($result as $tabHandle);
                    //print_R($result); print_R($after); print_r($before);
                    $this->handle[$tabHandle]=$tab;
                    static::$webDriver->switchTo()->window($tabHandle); 
                    static::$webDriver->get($host);
                    if ($debug) echo "   -> $tabHandle\n";                                
                    }
                else 
                    {
                    if ($debug) echo "Handle für $host nicht bekannt, oeffnen, erster Tab $tab.\n";                
                    static::$webDriver->get($host);
                    $tabHandle = static::$webDriver->getWindowHandle();
                    $this->handle[$tabHandle]=$tab;
                    if ($debug) echo "   -> $tabHandle=>$tab\n";
                    }
                }
            }
        else
            {
            if ($debug) echo "getHost $host\n";
            static::$webDriver->get($host);
            $tabHandle="unknown";
            }
        $this->title[$this->index++]=static::$webDriver->getCurrentURL()." | ".static::$webDriver->getTitle();      
        return ($tabHandle);
        }

    /* wenn bereits richtiger Tab, dann nur Url updaten */

    function updateUrl($url,$debug=false)
        {
        $url = $this->checkUrl($url);
        if ($debug) echo "update Url $url.\n";
        static::$webDriver->get($url);
        }

    function getUrl()
        {
        return(static::$webDriver->getCurrentURL());    
        }

    function checkUrl($url)
        {
        if (strpos($url,"http")===0) return($url);
        else return ("http://".$url);
        }

    function maximize()
        {
        static::$webDriver->manage()->window()->maximize();            
        }

    /* pressButtonIf
     * sucht einen xpath und überprüft:
     * wenn vorhanden               findElements
     * wenn clickable/visible       elementToBeClickable
     * drückt er da darauf den Button
     * webDriver muss bereits angelegt sein 
     *
     * false wenn der Button nicht gefunden wird
     * true wenn er gefunden wurde udn erfolgreich gedrückt ist und wenn es zweimal versucht wurde ....
     */

    function pressButtonIf($xpath,$debug=false)
        {        
        //$element = $webDriver->findElement(WebDriverBy::xpath('/html/body/div[4]/div[2]/div/div/div[2]/div/div/button'));
        if ($debug) echo "pressButtonIf($xpath):\n";
        //$result=$this->webDriver->findElements(WebDriverBy::xpath($xpath)); print_R($result);
        $count=count(static::$webDriver->findElements(WebDriverBy::xpath($xpath)));
        if ( $count === 0) {
            if ($debug) echo "   -->Button not found.\n";
            }
        else
            {
            if ($count>1) if ($debug) echo "   -->check, found in total $count times.\n";                
            $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));
            if ($element) {
                $this->title[$this->index++]=static::$webDriver->getTitle();
                $statusEnabled=$element->isEnabled();
                $statusDisplayed=$element->isDisplayed();
                if ($debug) echo "Element Status Enabled $statusEnabled Displayed $statusDisplayed\n";
                if ($statusEnabled)
					{
					if ($statusDisplayed)
						{
                        //Only Click when it's clickable gonna works
                        static::$webDriver->wait()->until(
                            function () use ($element) 
                                {
                                try 
                                    {
                                    static::$webDriver->executeScript('console.log("clicking");');
                                    echo "try click\n";
                                    $element->click();
                                    }
                                catch (WebDriverException $e) 
                                    {
                                    return false;
                                    }
                                static::$webDriver->executeScript('console.log("clickable");');
                                return true;
                                }
                            );
						if ($debug) print "found xpath , waited and click Button\n";
						return (true);                                          // nur hier erfolgreich
						}
					}
                //print_r($element);
                }
            }
        return (false);            
        }

    /* diffizile Weise Elemente clickbar zu machen, es gibt nicht einfach einen Button sondern ein hinterlagertes script
     * Beispiel 
     */

    function pressElementIf($xpath,$debug=false)
        {        
        //$element = $webDriver->findElement(WebDriverBy::xpath('/html/body/div[4]/div[2]/div/div/div[2]/div/div/button'));
        if ($debug) echo "pressElementIf($xpath):\n";
        //$result=$this->webDriver->findElements(WebDriverBy::xpath($xpath)); print_R($result);
        $count=count(static::$webDriver->findElements(WebDriverBy::xpath($xpath)));                             // xpath finden oder 
        if ( $count === 0) {
            if ($debug) echo "   -->Element to Click not found.\n";
            }
        else
            {
            if ($count>1) if ($debug) echo "   -->check, found in total $count times.\n";                
            $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));
            //$action = new WebDriverActions(static::$webDriver);
            if ($element) 
                {
                $action = static::$webDriver->action();
                echo "Element gefunden.\n";
                //$action->moveToElement($element)->perform();                                                        // mit hinbewegen ???
                echo "hinbewegen erfolgreich\n";
                $elements = static::$webDriver->findElements(WebDriverBy::xpath($xpath));
                static::$webDriver->executeScript('arguments[0].click();',$elements);                               // muss array sein)
                echo "draufgedrückt \n";
                return (true);                      // erfolgreich wenn bis hierher
                /*
                $this->title[$this->index++]=static::$webDriver->getTitle();
                $statusEnabled=$element->isEnabled();
                $statusDisplayed=$element->isDisplayed();
                if ($debug) echo "Element Status Enabled $statusEnabled Displayed $statusDisplayed\n";
                if ($statusEnabled)
					{
					if ($statusDisplayed)
						{
                        //Only Click when it's clickable gonna works
                        static::$webDriver->wait()->until(
                            function () use ($element) 
                                {
                                try 
                                    {
                                    static::$webDriver->executeScript('console.log("clicking");');
                                    echo "try click\n";
                                    $element->click();
                                    }
                                catch (WebDriverException $e) 
                                    {
                                    return false;
                                    }
                                static::$webDriver->executeScript('console.log("clickable");');
                                return true;
                                }
                            );
						if ($debug) print "found xpath , waited and click Button\n";
						return (true);                                          // nur hier erfolgreich
						}
					}
                //print_r($element); */
                }
            }
        return (false);            
        }

    /* pressSubmitIf
     * sucht einen xpath und überprüft:
     * wenn vorhanden               findElements
     * wenn clickable/visible       elementToBeClickable
     * drückt er da darauf den Button
     * webDriver muss bereits angelegt sein
     *
     * xpath kann gar nicht, einmal oder mehrmals gefunden werden
     * bei gar nicht keine Funktion, bei mehrmals wird der erste genommen, aber eine echo Ausgabe gemacht
     * 
     */

    function pressSubmitIf($xpath,$debug=false)
        {        
        //$element = $webDriver->findElement(WebDriverBy::xpath('/html/body/div[4]/div[2]/div/div/div[2]/div/div/button'));
        if ($debug) echo "pressSubmitIf($xpath):\n";
        //$result=$this->webDriver->findElements(WebDriverBy::xpath($xpath)); print_R($result);
        $count=count(static::$webDriver->findElements(WebDriverBy::xpath($xpath)));
        if ( $count === 0) {
            if ($debug) echo "   -->Button not found.\n";
            }
        else
            {
            if ($count>1) if ($debug) echo "   -->check, found in total $count times.\n";                
            $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));
            if ($element) 
                {
                $this->title[$this->index++]=static::$webDriver->getTitle();
                $statusEnabled=$element->isEnabled();
                $statusDisplayed=$element->isDisplayed();
                if ($debug) echo "Element Status Enabled $statusEnabled Displayed $statusDisplayed\n";
                if ($statusEnabled)
					{
					if ($statusDisplayed)
						{
                        //do submit instead of click
                        $element->submit();
						}
					}
                //print_r($element);
                }
            }
        return (false);            
        }

    function sendKeysToFormIf($xpath,$keys,$debug=false)
        {
        if (count(static::$webDriver->findElements(WebDriverBy::xpath($xpath))) === 0) {
            if ($debug) echo "   -->Form not found.\n";
            }
        else
            {            
            $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));        // relative path
            if ($element) 
                {
                try {$element->clear();  }                // empty field, throws sometimes an exception if field is not ready
                catch (Exception $e) { if ($debug) echo "  -->Element not ready to be cleared. Fehlermeldung ".$e->getMessage().".\n"; return (false); }                
                $element->sendKeys($keys); 
                return (true);             
                }
            }
        return (false);
        }

    /* textfeld laden , referenziert wird über xpath
     * if am Ende des Funktionsnamen bedeutet, es wird zuerst geschaut ob das Element vorhanden ist
     * wenn nicht führt das zu einem retun mit false
     * wenn schon wird das Element abgefragt
     *
     * nicht mehr verwendet, da von getHtmlIf ebenfalls abgedeckt
     *
     */
    function getTextIf($xpath,$debug=false)
        {
        if ($debug) echo "getTextIf($xpath):\n";            
        if (count(static::$webDriver->findElements(WebDriverBy::xpath($xpath))) === 0) {
            if ($debug) echo "   -->Text Field not found.\n";
            }
        else
            {            
            $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));        // relative path
            if ($element) 
                {
                $page = $element->GetText();        // Schreibweise ???
                return ($page);
                }
            }
        return (false);
        }

    /* SeleniumHandler::innerHtml lesen
     * es gibt auf Windows keinen vernünftigen Befehl, es wird immer nur Text gelesen
     * Abhilfe ist über ein gestartetes javascript die Abfrage zu machen. 
     *
     * nachdem ich den queryselector noch nicht bedienen kann wird der ganze body abgeholt und zurück geliefert.
     * So sollte es funktionieren ?
     *
        $page = static::$webDriver->executeScript('return document.querySelector(".selector").innerHTML');
        $elements = static::$webDriver->findElements(WebDriverBy::xpath($xpath));
        $page = static::$webDriver->executeScript('return arguments[0].innerHtml',$elements);
        print_r($page);
        $children  = $elements->childNodes();
     *
     * so gehts leider nicht:
        $page = $element->getAttribute('innerHTML');                    // funktioniert in php nicht
        $page = $element->getDomProperty('innerHTML');                // Befehl geht nicht
     */

    function getInnerHtml()
        {
        $page = static::$webDriver->executeScript('return document.body.innerHTML;');
        return ($page);
        }

    /* SeleniumHandler::analyseHtml ein html analysieren, ist nur eine Orientierungsfunktion, besser DOM verwenden
     * es wird zeichenweise analysiert
     *
     * hier werden die bekannten Search Algorithmen unterstützt
     *
     * false DIV :
     * <div style="width: 608px">
     */

    function analyseHtml($page,$displayMode=false,$commandEntry=false)
        {
        $pageLength = strlen($page);
        echo "analyseHtml Size of Input : ".nf($pageLength,"Bytes")."\n";
        //echo $page;
        $lineShow=false;
        if ($commandEntry !== false) $commandDisplay=strtoupper($commandEntry);
        else $commandDisplay="UNKNOWN";
        if ($displayMode===false) $display = false;
        elseif (is_array($displayMode))
            {

            }
        elseif ($displayMode>1)
            {
            $display  = false;
            $lineShow = $displayMode;
            }
        else $display = true;
        $zeile=0; $until=0;

        /* ausgabe i erfolgt auf 0 und dann nicht mehr */
        $pos=false; $ident=0; $end=false; 
        for ($i = 0; $i < $pageLength; $i++)                //html parser
            {
            if ($i<$until) 
                {
                echo htmlentities($page[$i]);           // funktioniert gut < wird in &lt; umgewandelt
                //echo "$i(".ord($page[$i]).") ";
                //echo $i.":".$page[$i].".";
                }
            if ($page[$i]=="<") 
                {
                $pos=$i;
                $ident++;
                }
            if (($page[$i]=="/") && ($pos !== false) ) { $ident--; $end=true; }
            if (($page[$i]=="\n") || ($page[$i]=="\r")) 
                {
                $zeile++;
                if ($zeile>$lineShow) $display=true;
                }
            if ((($page[$i]==" ") || ($page[$i]=="\n") || ($page[$i]=="\r") || ($page[$i]==">")) && ($pos !== false) )           // ein Trennzeichen, pos=0 akzeptieren, pos=false nicht, logischerweise fangt es mit einem  < an
                {
                $epos=strpos($page,">",$pos);
                $command=strtoupper(substr($page,$pos+1,$i-$pos-1));
                //if ($command == "SYMBOL") $display=false;
                if ($command == $commandDisplay) $display=true;
                if ( ($command != "BR") && ($command != "IMG") )
                    {
                    if ($display)
                        {
                        //if ($command == "BUTTON")
                            {
                            if ( ($ident<100) && ($ident>0) )  for ($p=0;$p<$ident;$p++) echo " ";
                            echo strtoupper($command);
                            // i steht erst bei dem ersten trennzeichen, epos beim Ende
                            //echo "check $i $epos ";
                            if ( ($epos)>($i) ) echo ": ".substr($page,$i,$epos-$i)." ";
                            //else echo ($epos-$pos+1)."<=".($i-$pos-1);
                            //for ($p=$pos;$p<=$i;$p++) echo ord($page[$p]).".";
                            echo "    ($i $pos ".($epos-$pos)." $zeile)\n";

                            }
                        }
                    if ($end) {$ident--; $end=false;}
                    }
                else $ident--;
                $pos=false; 
                if ($command == "/".$commandDisplay) $display=false;
                //if ($command == "/SYMBOL") $display=true;
                }
            }

        }

    /* innerHtml funktioniert bei PhP nicht
     * Routine nicht verwendet, für später um DOM auszuprobieren
     */

    function DOMinnerHTML(DOMNode $element) 
        { 
        $innerHTML = ""; 
        $children  = $element->childNodes;

        foreach ($children as $child) 
            { 
            $innerHTML .= $element->ownerDocument->saveHTML($child);
            }

        return $innerHTML; 
        } 

    /* query and filter an html with DOM
        $textfeld    = GetValue($easyResultID);
        $xPathQuery  = '//div[@*]';
        $filterAttr  = 'style';
        $filterValue ="width: 608px";
     * resultat sind alle Ergebnisse hintereinander in einem DOM, also arbeitet wie ein Filter
     *
     * DomDocument ist das xml/html Objekt mit den passenden Methoden dafür, dom in diesem Fall der Speicher, mit dem auch andere Operationen durchgeführt werden können
     */

    function queryFilterHtml($textfeld,$xPathQuery,$filterAttr,$filterValue,$debug=false)
        {
        $innerHTML="";
        if ($debug) echo "---- queryFilterHtml(...,$xPathQuery,$filterAttr,$filterValue)\n";
        //echo "$textfeld\n";                                                                       // lieber nicht verwenden, wird wahrscheinlich sehr unübersichtlich

        //$dom = DOMDocument::loadHTML($textfeld);          // non static forbidden
        $dom = new DOMDocument;     
        libxml_use_internal_errors(true);               // Header, Nav and Section are html5, may cause a warning, so suppress     
        $dom->loadHTML($textfeld);                  // den Teil der Homepage hineinladen
        libxml_use_internal_errors(false);
        $xpath = new DOMXpath($dom);
        //echo $dom->saveHTML();
        //echo "-------Auswertungen--------------->\n";

        /*  
        $links = $dom->getElementsByTagName('div');
        foreach ($links as $book) 
            {
            echo $book->nodeValue, PHP_EOL;
            }  */

        // Tutorial query https://www.w3schools.com/xml/xpath_syntax.asp
        //$links = $xpath->query('//div[@class="blog"]//a[@href]');
        $links = $xpath->query($xPathQuery);
        if ($debug) echo "Insgesamt ".count($links)." gefunden für \"$xPathQuery\"\n";

        $count=0; $maxcount=5;          // max 5 Einträge im debug darstellen, aber alle einsammeln
        $result=array();                // für walkHtml Auswertung

        // alle Ergebnisse in ein neues DOM speichern 
        $tmp_doc = new DOMDocument();   
        foreach ($links as $a) 
            {
            //$attribute=$a->getAttribute('@*');  if ($attribute != "") echo $attribute."\n";
            //echo $a->textContent,PHP_EOL;
            $xmlArray = new App_Convert_XmlToArray();           // class aus AllgemeineDefinitionen, convert html/xml printable string to an array

            $style = $a->getAttribute($filterAttr);
            if ( ($filterValue=="") || (strpos($filterValue,"*")!==false) || ($style == $filterValue) )
                {
                $found=true;
                if (strpos($filterValue,"*")!==false)           // * als Einzelchar oder am Ende eines Suchstrings wie ein Filter
                    {
                    $pos = strpos($filterValue,"*");
                    //if ($pos>0) echo substr($style,0,$pos)." == ".substr($filterValue,0,$pos)."\n";
                    if ( ($pos==0) || (substr($style,0,$pos) == substr($filterValue,0,$pos)) )
                        {
                        // gefunden, andernfalls wird found doch wieder false
                        }
                    else $found=false;
                    }
                if ($found)
                    {
                    if ($debug) echo "   -> found:  ".$a->nodeName." , ".$a->nodeType." $style\n";
                    $result[$count] = $xmlArray->walkHtml($a,false);                                        // Ergebnis noch etwas unklar, wir sammlen mal
                    //if (($count)<$maxcount) if ($debug) print_R($result[($count)]);
                    $count++;
                    $tmp_doc->appendChild($tmp_doc->importNode($a,true));                 
                    //echo $innerHTML;
                    }
                }
            }
        $innerHTML .= $tmp_doc->saveHTML();                      
        return ($innerHTML);
        }

    /* den Style als Array rausziehen 
     * input ist ein DOM, wahrscheinlich von queryFilterHtml erzeugt
     * aus der Sammlung von Objekten jetzt die benötigten Einträge rausziehen
     * jetzt geht es auf die Attribute, sonst wäre ja kein innerhtml notwendig und wir könnten gleich den text verwenden, text hat aber keine xml Namen mehr dabei
     *
     */

    function extractFilterHtml($textfeld,$xPathQuery,$filterAttr,$debug=false)
        {
        $result=array();
        if ($debug) echo "---- extractFilterHtml(...,$filterAttr)\n";
        $dom = new DOMDocument; 
        $dom->loadHTML($textfeld);
        $xpath = new DOMXpath($dom);
        $links = $xpath->query($xPathQuery);
        echo "Insgesamt ".count($links)." gefunden für \"$xPathQuery\"\n";
        foreach ($links as $a) 
            {
            //$attribute=$a->getAttribute('@*');  if ($attribute != "") echo $attribute."\n";
            //echo $a->textContent,PHP_EOL;
            $result[] = $a->getAttribute($filterAttr);
            }
        return ($result);
        }

    /* html code laden , referenziert wird über xpath
     * if am Ende des Funktionsnamen bedeutet, es wird zuerst geschaut ob das Element vorhanden ist
     * wenn nicht führt das zu einem retun mit false
     * wenn schon wird das Element abgefragt, zuerst wird das innerhtml versucht und wenn nicht dann die Textfelder
     *
     */
    function getHtmlIf($xpath,$debug=false)
        {
        if ($debug) echo "getHtmlIf($xpath):\n";  
        $count=count(static::$webDriver->findElements(WebDriverBy::xpath($xpath)));
        if ( $count === 0) {                  
            if ($debug)
                { 
                echo "   -->Text Field not found at $xpath.\n";
                while ($count===0)
                    {
                    $count=1;
                    $pos = strrpos($xpath,"/");
                    if ($pos!==false) 
                        {
                        $xpath = substr($xpath,0,$pos);
                        echo "look for $xpath now\n";
                        $count=count(static::$webDriver->findElements(WebDriverBy::xpath($xpath)));
                        if ($count >0) 
                            {
                            echo "----> found\n";
                            }
                        }
                    }
                }
            }
        else
            {            
            $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));        // relative path
            if ($element) 
                {
                if ($debug) 
                    {
                    echo "   -->found [$count], look for innerHtml.\n";
                    //print_R($element);
                    //print_r(static::$webDriver);          // keine Ausgabe
                    }
                //if (strlen($page)>10) return ($page);
                //if ($debug) echo "   -->result too short \"$page\", then try text.\n";
                $page = $element->getText();
                if ( (strlen($page)<=10) && ($debug) ) echo "   -->result of getText still too short \"$page\".\n";                
                return ($page);
                }
            }
        return (false);
        }

    function quitHost()
        {
        if ($this->active===false) return;

        static::$webDriver->quit();

        }


    }           // ende class SeleniumHandler



/*  SeleniumYahooFin
 *  Finanz Nachrichten und historische Kurse auslesen 
 *  finance yahoo bietet target Werte der Analysten, an denn kann festgestellt werden ob eine Aktie Potential hat oder nicht
 *
 *      __construct
 *      getResultCategory
 *      setConfiguration
 *      getSymbolsfromConfig            aus dem Easycharts Configuration File, dem Orderbook die passenden Shortnames für yahoo finance herauslesen
 *      updateSymbols
 *      getIndexToSymbols 
 *      addIndexToSymbols
 *
 *      getErgebnis
 *      parseResult
 *      writeResult
 *      getResult
 *      getResultHistory
 *      processResultHistory
 *
 *      runAutomatic
 *      pressConsentButtonIf
 *      goToShareLink
 *      getJahresKursziel
 *
 *
 */

class SeleniumYahooFin extends SeleniumHandler
    {
    private $configuration;                 //array mit Datensaetzen
    private $CategoryIdDataYahooFin;         // sub Kategorie YAHOOFIN in RESULT
    private $duetime,$retries;              //für retry timer
    private $symbols,$index;                       // die auf Finance abufragenden Symbole
    protected $debug;

    /* Initialisierung der Register die für die Ablaufsteuerung zuständig sind
     */
    function __construct($debug=false)
        {
        $this->duetime=microtime(true);
        $this->retries=0;
        $this->debug=$debug;
        $this->updateSymbols();

        parent::__construct();          // nicht vergessen den parent construct auch aufrufen
        $this->CategoryIdDataYahooFin = IPS_GetObjectIdByName("YAHOOFIN",$this->CategoryIdData);        
        $this->IndexToSymbols = CreateVariableByName($this->CategoryIdDataYahooFin,"IndexToSymbols",1);        
        $this->index=0;             // fängt immer mit Null an
        }

    function getResultCategory()
        {
        return($this->CategoryIdDataYahooFin);
        }


    /* Konfiguration ausgeben, min Function
     */
    public function setConfiguration($configuration)
        {
        //echo "YahooFin setConfiguration \n";
        $this->configuration = $configuration;
        $this->updateSymbols();
        }

    /* aus dem Easycharts Configuration File, dem Orderbook die passenden Shortnames für yahoo finance herauslesen
     * eigentlich müsste man die Easycharts Klasse dazu aufrufen, aktuell vereinfacht implementiert
     *
     */
    public function getSymbolsfromConfig($debug=false)
        {
        $configShares = get_EasychartConfiguration();
        if ($debug) echo "getSymbolsfromConfig, ".json_encode($this->configuration)." ";        //print_r($this->configuration);
        if (isset($this->configuration["SourceForShort"]))
            {
            if ($debug) echo "Source for Short given";
            $seleniumEasycharts = new SeleniumEasycharts();

            switch (strtoupper($this->configuration["SourceForShort"]))
                {
                case "ALL":
                    if ($debug) echo "YahooFin getSymbolsfromConfig for All Shorts.\n";
                    $configShares = $seleniumEasycharts->createJointConfiguration($debug);
                    break;
                case "ORDERBOOK":
                    $configShares = $seleniumEasycharts->getEasychartConfiguration();
                    break;
                case "SHAREBOOK":
                    $configShares = $seleniumEasycharts->getEasychartSharesConfiguration();
                    break;
                default:
                    $configShares = get_EasychartConfiguration();
                    break;    
                }     
            }
        $result=array();
        foreach ($configShares as $index=>$entry)
            {
            if (isset($entry["Short"])) $result[$index] = $entry["Short"]; 
            }   
        return ($result);
        }

    /* Update Symbols wenn sich die Configuration ändert
     *
     */
     private function updateSymbols($debug=false)
        {
        $num=0;
        $this->symbols=array();                                         // eigentlich kein update sondern ein create
        foreach ($this->getSymbolsfromConfig($debug) as $index=>$short) 
            {
            $this->symbols[$num]["Short"]=$short;
            $this->symbols[$num]["Index"]=$index;
            $num++;
            }
        }

    /* Wert von indexToSymbols ausgeben zur Überwachung
     * Es kann sein dass der Index zu gross ist, abhängig von der config ist Symbols größer oder kleiner
     */
    public function getIndexToSymbols($debug=false)
        {
        $size=count($this->symbols);
        $index=GetValue($this->IndexToSymbols);
        if ($debug) 
            {
            echo "Aktueller Target Index $index:\n";
            foreach ($this->symbols as $indexSymbols => $entry) 
                {
                if ($index == $indexSymbols) echo "*"; else echo " ";
                echo $indexSymbols."  ".json_encode($entry)."   \n";
                }
            }
        return ($index);
        }

    /* Symbols hat x Einträge, wenn x überschritten wird, wieder am Anfang weitermachen
     * this->index nicht überschreiben
     */
    private function addIndexToSymbols($add)
        {
        $size=count($this->symbols);
        $index=GetValue($this->IndexToSymbols)+$add;
        if ($index>=$size) $index-=$size; 
        SetValue($this->IndexToSymbols,$index);
        }

    /*
    */
    public function getErgebnis($debug=false)
        {
        $this->updateSymbols($debug);
        return ($this->symbols);
        }

    /*  YahooFin, Nachdem bereits als json encoded array übergeben wird gibt es nicht alzuviel zu tun
     *  also Ausgabe der Kursziele und das wars auch schon wieder  
     *
     */

    function parseResult($input, $debug=false)
        {
        $data = json_decode($input["Value"],true);               //true speichern als Array, String Wert ist bereits historisiert
        return($data);
        }


    /* YAHOOFIN, Werte in einer Category speichern 
     * die ermittelten Werte als Archiv speichern
     * wird nicht von automatedQuery verwendet
     * shares übergibt Short und Target
     *
     */

    function writeResult($shares, $name="TargetValue", $debug=false)
        {
        $componentHandling = new ComponentHandling();           // um Logging mit Historisietrung (Archive) zu setzen
        $categoryIdResult = $this->getResultCategory();
        if ($debug) echo "seleniumYahooFin::writeResult, store the updated target values, Category YahooFin RESULT $categoryIdResult mit Name $name.\n";
    
        /*function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false) */
        $oid = CreateVariableByName($categoryIdResult,$name,3,'',"",1000);         // kein Identifier, darf in einer Ebene nicht gleich sein

        $parent=$oid;                               // Parent ist TargetValue, jetzt die shares Liste durchgehen und den jeweiligen TargetValue abspeichern, Name ist Short
        foreach($shares as $index => $share)
            {
            if ( (isset($share["Short"])) && (isset($share["Target"])) )
                { 
                $oid = CreateVariableByName($parent,$share["Short"],2,'Euro',"",100);     //gleiche Identifier in einer Ebene gehen nicht
                $componentHandling->setLogging($oid);

                $value = $share["Target"];
                if (GetValue($oid) != $value) SetValue($oid,$value);
                elseif ($debug)
                    {
                    echo "gleicher Wert $value at ".IPS_GetName($oid)." ($oid)  last changed: ".date("d.m.Y H:i:s",IPS_GetVariable($oid)["VariableChanged"])."   ";
                    echo "\n"; 
                    }
                }
            }
        }

    /* YAHOOFIN, alle Werte aus der Kategorie lesen 
     * die ermittelten Werte als Array speichern, Basis sind die, die in getErgebnis stehen
     *
     */

    function getResult($name="TargetValue", $debug=false)
        {
        $result=array();
        $archiveOps=new archiveOps();
        $categoryIdResult = $this->getResultCategory();
        if ($debug) echo "seleniumYahooFin::getResult, get the target values with name \"$name\" from $categoryIdResult and write them to an array.\n";
    
        $config=array();
        $parent = IPS_GetObjectIdByName($name,$categoryIdResult);         // kein Identifier, darf in einer Ebene nicht gleich sein
        $childrens = IPS_GetChildrenIDs($parent);
        foreach($childrens as $children)
            {
            //$archiveOps->getValues($children,$config,$debug);                 // true mit Debug    
            $result[IPS_GetName($children)]=GetValue($children);
            }
        //if ($debug) print_r($result);       // Input für Ergebnis

        // Ergebnis erstellen und ausgeben, verwendet this->symbols
        $ergebnis=array();
        foreach ($this->getErgebnis($debug) as $index => $entry)
            {
            if ($debug) echo $index."  ";
            if ( (isset($entry["Short"])) && (isset($entry["Index"])) ) 
                {
                if ($debug) echo $entry["Short"]."   ".$entry["Index"]."\n";
                $ergebnis[$entry["Index"]]["Short"]=$entry["Short"];
                if (isset($result[$entry["Short"]])) $ergebnis[$entry["Index"]]["Target"] = $result[$entry["Short"]];
                }
            //print_R($entry);
            }
        return ($ergebnis);
        }

    /* YAHOOFIN, alle Werte aus der Kategorie lesen, die ermittelten Werte als Array speichern
     * debug und configParameter durchreichen für getValues
     * auch target Values brauchen die Split Bearbeitung in GetValues
     */

    function getResultHistory($name="TargetValue", $config=array(), $debug=false)
        {
        $result=array();
        $archiveOps=new archiveOps();
        $archiveID = $archiveOps->getArchiveID();
        $seleniumEasycharts = new SeleniumEasycharts();
        $categoryIdResult = $this->getResultCategory();

        //Debug
        $debugTarget=false;
        if ($debug) 
            {
            echo "seleniumYahooFin::getResultHistory, get the target values with name \"$name\" from $categoryIdResult and write them to an array.";
            if ($debug>10) 
                { 
                $debugTarget=$debug; 
                $debug=true; 
                echo " Debuglevel $debug. DebugTarget $debugTarget \n";
                }
            else echo " Debuglevel $debug. \n";
            }
    
        $configGetV=array();
        $configGetV["manAggregate"]  = false;      // sonst tägliche Aggregation mit unerwartetem Ausgang
        if (isset($config["Interpolate"])) $configGetV["Interpolate"]   = $config["Interpolate"];
        $parent = IPS_GetObjectIdByName($name,$categoryIdResult);         // kein Identifier, darf in einer Ebene nicht gleich sein
        $childrens = IPS_GetChildrenIDs($parent);
        foreach($childrens as $children)
            {
            if (isset($config["Split"]))
                {
                $short=IPS_GetName($children);
                //echo "Look for Split of $children ($short).";              // Targetnaming is Short, need Index
                if (isset($config["Split"][$short])) 
                    {
                    echo "Split of $children ($short) -> found as ".json_encode($config["Split"][$short]).".\n";
                    $configGetV["Split"]=$config["Split"][$short];
                    //$debug=2;
                    }
                //$split = $seleniumEasycharts->getSplitfromOid($children);                   // sucht die OID in den Depot Configurations, targets sind aber nicht im Depotconfig
                }    
            $status = AC_GetLoggingStatus($archiveID,$children);
            echo "getValues für $children (".IPS_GetName($children).") mit Status ".($status?"Logged":"NoLog")." aufrufen:\n";
            if ($children==$debugTarget) $ergebnis = $archiveOps->getValues($children,$configGetV,5);                                   // 5 analysiert die Mittelwertbildung
            else $ergebnis = $archiveOps->getValues($children,$configGetV,$debug);                 // true mit Debug    
            //$result[IPS_GetName($children)]["Actual"]=GetValue($children);
            //$result[IPS_GetName($children)]["History"]=$ergebnis;
            $result[IPS_GetName($children)]=$ergebnis;
            }
        //if ($debug) print_r($result);       // Input für Ergebnis
        return ($result);
        }

    /* YAHOOFIN, processResultHistory, 
     * vorher wurden target Werte mit History von getResultHistory ermittelt
     * dazu jetzt target mit den Values von result anreichern wenn Bezeichnung Short gleich ist
     * target hat die Struktur short, target, values jeweils für einen AktienIndex
     * der Wert Target sollte der letzte Wert in der History von Values sein
     *
     * diese Werte aufbereiten 
     * Ausgabe als Text wenn Debug aktiviert ist, Debug Level false, true/1 ,2 
     * und als Array savedEntry im return:
     *  Short
     *  Target
     *  Values      Array
     *  MeansRoll   Array
     *  Description Array
     *
     *
     */

    public function processResultHistory($result,$target,$debug=false)
        {
        if ($debug) echo "processResultHistory with debug Level $debug.\n";

        // target mit result anreichern, 
        foreach ($result as $short => $entry)
            {
            if ($debug===2) echo $short."  ";
            foreach ($target as $id => $share)
                {
                if ($debug===2) echo "compare ".json_encode($share)." with $short.\n";
                if ( isset($share["Short"]) && ($share["Short"] == $short) )
                    {
                    //$target[$id]=$entry;
                    foreach ($entry as $index=>$values) 
                        {
                        $target[$id][$index] = $values;
                        }
                    break;                      // die nächsten gar nicht mehr anschauen
                    }
                }
            if ($debug===2) echo "\n";
            }
        //print_R($target);
        if ($debug===2) echo "-------------------------------------------\n";

        // aus dem result den trend berechnen, nach id durchgehen, benötigt ein Target und bereits historisierte Werte
        $largeCount=false;          // die maximale Anzahl an historisierten Werten ermitteln
        $savedEntry=array();            // Ergebnis
        foreach ($target as $id => $share)          // Target.Id.Trend hinzufügen, einfach ersten und letzten Wert vergleichen, oder gibt es bessere Werte
            {
            if ($debug) echo "Bearbeite $id (".$share["Short"].") : ";
            if ( (isset($share["Target"])) && ($share["Target"] != 0) )
                {
                if ($debug) echo "Target ".$share["Target"]."  ";
                //print_R($share);
                $trend=false;
                if (isset($share["Values"]))
                    {
                    $oldestTime=false; $newestTime=false; $oldestValue=0; $newestValue=0;
                    $values=""; 
                    foreach ($share["Values"] as $entryId=>$entry) 
                        {
                        if ($oldestTime===false) { $oldestTime=$entry["TimeStamp"]; $oldestValue=$entry["Value"]; }
                        if ($newestTime===false) { $newestTime=$entry["TimeStamp"]; $newestValue=$entry["Value"]; }
                        if ($entry["TimeStamp"]<$oldestTime) { $oldestTime=$entry["TimeStamp"]; $oldestValue=$entry["Value"]; }
                        if ($entry["TimeStamp"]>$newestTime) { $newestTime=$entry["TimeStamp"]; $newestValue=$entry["Value"]; }
                        $values .= "   ".date("d.m.Y H:i:s",$entry["TimeStamp"])."  ".$entry["Value"]."  \n";
                        }
                    $trend = ($newestValue/$oldestValue-1)*100;
                    if ($debug) 
                        {
                        if ($trend != 0) echo "Trend ".nf($trend,"%")."\n"; 
                        else echo "\n";
                        echo $values;
                        }
                    $target[$id]["Trend"]=$trend;            
                    $count = count($share["Values"]); 
                    if ( ($largeCount === false) || ($count>$largeCount) )  $largeCount=$count;
                    $savedEntry[$id]=$target[$id];                                                          // nur wenn historische Werte zur Verfügung stehen
                    }
                else 
                    {
                    $target[$id]["Trend"]=$trend;              // speichert ein false
                    if ($debug) echo "no Values found\n"; 
                    }
                }
            else            // if no Target
                {
                if ($debug) echo "no Target found\n";      
                }
            }       // ende foreach
        return($savedEntry);
        }

    /* YAHOOFIN Statemachine abarbeiten, Daten aus YahooFin entsprechend der Tabelle für die Short Bezeichnungen abfragen
     * step gibt den aktuellen Schritt vor, zählt von 0 aufwärts
     * index ist die interene Steuerung der Aktivitäten je Short
     * num ausgelesen von IndextoSymbols gibt das aktuelle Short aus der Tabelle vor
     *
     */
    public function runAutomatic($step=0)
        {
        if (isset($this->configuration["MaxCall"])) $maxCall=$this->configuration["MaxCall"]*2;
        else $maxCall=5;
        echo "*** runAutomatic SeleniumYahooFin Step $step. ".$this->index."/$maxCall.\n";
        switch ($step)
            {
            case 0:         // yahoo consent
                echo "--------\n0: check if there is consent window, accept.\n";
                $result=$this->pressConsentButtonIf();          // den Consent Button clicken, wenn er noch da ist, spätestens nach 2 Sekunden aufgeben
                if  ($result === false) echo "Consent Button not found.";

                break;
            case ($this->index+1):
                echo "--------\n".($this->index+1).": ";
                $num=GetValue($this->IndexToSymbols);
                if ( ($num>(count($this->symbols))) || (isset($this->symbols[($num)])) ) 
                    { 
                    $this->addIndexToSymbols(1);                // index um eins weiterzählen
                    $num=GetValue($this->IndexToSymbols); 
                    }
                echo json_encode($this->symbols[$num])."  $num ".$this->index."<$maxCall\n";
                if ( (isset($this->symbols[($num)]["Short"])) && ($this->index<$maxCall) ) 
                    {
                    $shareID=$this->symbols[($num)]["Short"];
                    echo "go to url with \"$shareID\".\n";
                    //$this->index++;
                    $result=$this->goToShareLink($shareID);
                    echo $result;
                    }
                else $this->index+=3;
                break;
            case ($this->index+2):
                echo "--------\n";
                echo ($this->index+2).": get Ergebnis.\n";
                $ergebnis = $this->getJahresKursziel();
                $num=GetValue($this->IndexToSymbols);
                $this->symbols[($num)]["Target"]=$ergebnis;
                $this->index+=2;
                $this->addIndexToSymbols(1);                // index um eins weiterzählen
                //echo $ergebnis;
                $result=json_encode($this->symbols);
                return(["Ergebnis" => $result]);                                //Zwischenergebnis wegspeichern 
                break;
            case ($this->index+3):
                echo "--------\n";
                echo ($this->index+3).": ready.\n";
                $result=json_encode($this->symbols);
                return(["Ergebnis" => $result]); 
                break;               
            default:
                return (false);
            }

        }

    /* Consent on Privacy, press Button
     */
    function pressConsentButtonIf()
        {        
        /* Consent Window click xpath
         * /html/body/div/div/div/div/form/div[2]/div[2]/button
         */
        $xpath='/html/body/div/div/div/div/form/div[2]/div[2]/button';
        return($this->pressButtonIf($xpath));
        }

    /* goto share link
     *  das ist ein Link hier hin: https://de.finance.yahoo.com/quote/SAP?p=SAP
     *
     */
    function goToShareLink($shareID)
        {
        $url='de.finance.yahoo.com/quote/'.$shareID.'?p='.$shareID;
        return($this->updateUrl($url));
        } 

    /* get 1 Jahres Kursziel
     * /html/body/div[1]/div/div/div[1]/div/div[3]/div[1]/div/div[1]/div/div[2]/div[2]/table/tbody/tr[8]/td[2]
     * //*[@id="quote-summary"]/div[2]/table/tbody/tr[8]/td[2]
     */
    function getJahresKursziel()
        {
        //$xpath='/html/body/div[1]/div/div/div[1]/div/div[3]/div[1]/div/div[1]/div/div[2]/div[2]/table/tbody/tr[8]/td[2]';
        $xpath='//*[@id="quote-summary"]/div[2]/table/tbody/tr[8]/td[2]';
        //$ergebnis = $this->getHtmlIf($xpath,$this->debug);  
        $ergebnis = $this->getTextIf($xpath,$this->debug);
        if ($ergebnis !== false)
            {
            if ($this->debug) echo "found fetch data, length is ".strlen($ergebnis)."\n";  
            if ((strlen($ergebnis))>3) 
                {
                if(strstr($ergebnis, ",")) 
                    {
                    $ergebnis = str_replace(".", "", $ergebnis); // replace dots (thousand seps) with blancs
                    $ergebnis = str_replace(",", ".", $ergebnis); // replace ',' with '.' 
                    }                   
                $result = floatval($ergebnis);
                echo "\"$ergebnis\" ergibt $result";
                return($result);
                }
            else echo $ergebnis;
            }
        return (false);
        }

    }




/* 
 * Bei LogWien einlogen und die aktuellen Zählerstände auslesen
 * RunAutomatic legt die einzelnen Schritte bis zur Auslesung des täglichen Stromzählerstandes fest
 *
 *
 *  __construct
 *  getResultCategory
 *  setConfiguration
 *  writeEnergyValue
 *  getEnergyValueId
 *  getInputCsvFiles
 *
 *  runAutomatic            Aufruf der einzelnen Steps, Statemachine
 *      getTextValidLoggedInIf
 *      getTextValidLogInIf
 *      enterLogInButtonIf
 *      enterLoginEmail
 *      enterLoginPassword
 *      enterSliderIf
 *      clickImageIf
 *      clickSMLinkIf
 *      getEnergyValueIf
 *
 *
 */

class SeleniumLogWien extends SeleniumHandler
    {
    private $configuration;                     //array mit Datensaetzen
    protected $CategoryIdDataLoGWien;           // Kategrie als Speicherort
    private $duetime,$retries;              //für retry timer
    protected $debug;

    /* Initialisierung der Register die für die Ablaufsteuerung zuständig sind
     */
    function __construct($debug=false)
        {
        $this->duetime=microtime(true);
        $this->retries=0;
        $this->debug=$debug;
        parent::__construct();
        $this->CategoryIdDataLogWien = IPS_GetObjectIdByName("LogWien",$this->CategoryIdData);        
        }

    function getResultCategory()
        {
        return($this->CategoryIdDataLogWien);
        }

    public function setConfiguration($configuration)
        {
        $this->configuration = $configuration;
        }

    /* LogWien Energiewert abspeichern und archivieren
     *
     */
    function writeEnergyValue($value,$name="EnergyCounter")
        {
        $componentHandling = new ComponentHandling();           // um Logging mit Historisietrung (Archive) zu setzen
        $categoryIdResult = $this->getResultCategory();
        echo "Store the new values, Category LogWien RESULT $categoryIdResult.\n";
    
        /*function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false) */
        $oid = CreateVariableByName($categoryIdResult,$name,2,'kWh',"",1000);         // kein Identifier, darf in einer Ebene nicht gleich sein
        $componentHandling->setLogging($oid);

        //$value = "10.962";                            // nur der . wird zum Komma umgerechnet
        $value = str_replace(",",".",$value);           // Beistrich auf . umrechnen
        $wert=floatval($value);         // Komma wird nicht richtig interpretiert
        //echo "Umgewandelt ist $value dann : $wert \n";
        SetValue($oid,$wert);
        return(["Value"=>$wert,"OID"=>$oid]);
        }

   /*  Energiewert auslesen vorbereiten, OID holen
     *
     */
    function getEnergyValueId($name="EnergyCounter")
        {
        $componentHandling = new ComponentHandling();           // um Logging zu setzen
        $categoryIdResult = $this->getResultCategory();
        echo "getEnergyValueId, Werte aus Kategorie ".IPS_GetName($categoryIdResult)."\n";
        $oid = IPS_GetObjectIdByName($name,$categoryIdResult);
        return ($oid);
        }


    /*  Statemachine, der reihe nach Logwien abfragen
     *      (1) check if not logged in
     *      (2) log in
     *
     */

    public function runAutomatic($step=0)
        {
        echo "runAutomatic SeleniumLogWien Step $step.\n";
        switch ($step)
            {
            case 0:
                echo "--------\n0: check if not logged in, then log in.\n";
                if ($this->getTextValidLoggedInIf()!==false)  return(["goto" => 4]);                // brauche ein LogIn Fenster, weiter wenn vollständig eingelogged
                if ($this->getTextValidLogInIf()!==false)  return(["goto" => 6]);
                break;
            case 1:
                echo "--------\n1: check if angular has already started and there is login window, otherwise press Button.\n";
                $result=$this->enterLogInButtonIf();
                //if ($result === false)  return(["goto" => 3]);                // brauche ein LogIn Fenster
                break;
            case 2:
                echo "--------\n2: enter log in email Adresse.\n";
                $result = $this->enterLoginEmail($this->configuration["Username"]);            
                if ($result === false) 
                    {
                    echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 3:
                echo "--------\n3: enter log in Password.\n";            
                $result = $this->enterLoginPassword($this->configuration["Password"]);            
                if ($result === false) 
                    {
                    echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 4:
                echo "--------\n4: Press Slider.\n";            
                $result = $this->enterSliderIf();
                if ($result === false) 
                    {
                    echo "   --> failed, try if allready on Smart meter Portal\n";
                    return(["goto" => 6]);
                    }
                break;   
            case 5:
                echo "--------\n5: Press Slider.\n";             
                $result = $this->clickImageIf();
                if ($result === false) 
                    {
                    echo "   --> failed, press slider again\n";
                    return(["goto" => 4]);
                    }
                break;  
            case 6:
                echo "--------\n6: Goto Smart Meter Portal.\n";   
                $this->duetime=microtime(true)+4;       // dieses delay wird erst im nächsten Schritt geprüft          
                $result = $this->clickSMLinkIf();
                if ($result === false) 
                    {
                    echo "   --> failed, continue nevertheless\n";
                    }
                break; 
            case 7:
                echo "--------\n7: wait 4 seconds until Smart Meter Portal has loaded.\n";              
                if ($this->debug) echo "continue to wait 4 seconds for first Window";            
                if (microtime(true)<$this->duetime) 
                    {
                    if ($this->debug) echo "\n";
                    return("retry");                
                    }            
                break;    
            case 8:
                echo "--------\n8: Find Popup Window with Privacy Ino to Accept and press Biutton. if not continue.\n";             
                $result = $this->getPrivacyIf();
                break;  
            case 9:
                echo "--------\n9: Find Popup Window with Gelesen and press Biutton. if not continue.\n";             
                $result = $this->getGelesenIf();
                break;  
            case 10:
                echo "--------\n10: Read Energy Value.\n";             
                $result = $this->getEnergyValueIf(true);                // true für Debug
                if ($result===false) return ("retry");
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                return(["Ergebnis" => $result]);
                break;                                
            default:
                return (false);
            }

        }

    /* get text , find out whether being logged in 
     * if so skip login session
     * /html/body/div/div[2]/div[1]/div[4]/div[1]/span
     * nur der Button Slider
     * /html/body/app-root/app-start/div/main/div/div/div/div[1]/c-slider/div/div[1]/div[1]/button[2]
     * das ganze Datenfeld
     * /html/body/app-root/app-start/div/main/div/div/div/div[1]/c-slider/div/div[1]/div[3]
     */
    function getTextValidLoggedInIf()
        {
        if ($this->debug) echo "getTextValidLoggedInIf ";
        $xpath='/html/body/app-root/app-start/div/main/div/div/div/div[1]/c-slider/div/div[1]/div[3]';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)."\n";
            print $result;
            }
            
        return ($result);
        }     

    /* Smart Meter Portal already selectable        
     * $xpath='/html/body/app-root/app-partner-detail/div/main/div/div/div[2]/div/div/div[3]/div[2]/p'; 
     */

    function getTextValidLogInIf()
        {
        if ($this->debug) echo "getTextValidLogInIf ";
        $xpath='/html/body/app-root/app-partner-detail/div/main/div/div/div[2]/div/div/div[3]/div[2]/p';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)."\n";
            print $result;
            }
        return ($result);

        }

    /*  check if log.wien available and structure
     *  wenn noch kein Login Window erschienen ist: 
     *  /html/body/app-root/app-start/div/app-header/header/div/button
     *  sonst gilt:
     *  /html/body/div/main/form/fieldset/section[2]/c-textfield/input
     *
     *  /html/body/app-root/app-partner-detail/div/main/div/div/div[2]/div/div/div[3]/div[2]/p
     */

    function enterLogInButtonIf()
        {
        if ($this->debug) echo "enterLogInButton ";
        $xpath='/html/body/app-root/app-start/div/app-header/header/div/button';
        $result = $this->getHtmlIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)."\n";
            print $result;
            $this->pressButtonIf($xpath,true);                                                      // true debug  
            } 
        else echo "Button not pressed.\n";
        return ($result);
        }

    /* login entry username und password
     * 
     * /html/body/div/main/form/fieldset/section[2]/c-textfield/input
     * 
     */
    function enterLoginEmail($username)
        {
        /*  <button   /html/body/app-root/app-start/div/app-header/header/div/button 
           //*[@id="top"]/app-header/header/div/button   
        $this->sendKeysToFormIf($xpath,$password);              // cloudg06        */
        //$xpath = '/html/body/app-root/app-start/div/app-header/header/div/button';              // Login Button Top Right
        //$xpath = '//*[@id="top"]/app-header/header/div/button';
        //$this->pressButtonIf($xpath,true);                                                      // true debug  
        $xpath = '/html/body/div/main/form/fieldset/section[2]/c-textfield/input';              // extra Form username
        $this->sendKeysToFormIf($xpath,$username);
        /* press Button Weiter 
         * /html/body/div/main/form/fieldset/section[3]/div[1]/button[1]
         */
        $xpath = '/html/body/div/main/form/fieldset/section[3]/div[1]/button[1]';
        $this->pressButtonIf($xpath,true);          
        }

    /* login entry username und password
     * 
     * /html/body/div/main/form/fieldset/section[2]/c-textfield/input
     * 
     */
    function enterLoginPassword($password)
        {
        /*  /html/body/div/main/form/fieldset/section[2]/div[3]/c-textfield/input
         *  /html/body/div/main/form/fieldset/section[2]/c-textfield[2]/input
         */
        $xpath = '/html/body/div/main/form/fieldset/section[2]/c-textfield[2]/input';
        if (($this->sendKeysToFormIf($xpath,$password))===false)
            {
            $xpath = '/html/body/div/main/form/fieldset/section[2]/div[3]/c-textfield/input';              // extra Form username+password, older version
            $this->sendKeysToFormIf($xpath,$password);
            }

        /* press Button Weiter 
         * /html/body/div/main/form/fieldset/section[3]/div[1]/button[2]
         * 
         */
        $xpath = '/html/body/div/main/form/fieldset/section[3]/div[1]/button[2]';           // Button ist gleicg geblieben
        $this->pressButtonIf($xpath,true);          
        }

    /* was war das, hier die richtige Applikation aussuchen, ist jetzt eine Jumppage ohne schöne Bilder, hjüpft auf 6, clickSMLinkIf
     *
     * /html/body/app-root/app-start/div/main/div/div/div/div[1]/c-slider/div/div[1]/div[1]/button[2]
     */
    private function enterSliderIf()
        {
        $xpath='/html/body/app-root/app-start/div/main/div/div/div/div[1]/c-slider/div/div[1]/div[1]/button[2]';
        $status=$this->pressButtonIf($xpath);             
        return ($status);                    
        }

    private function clickImageIf()
        {
        /* /html/body/app-root/app-start/div/main/div/div/div/div[1]/c-slider/div/div[1]/div[3]/ul/app-service-card[8]/div/a/div[1]/img
         */
        $xpath='/html/body/app-root/app-start/div/main/div/div/div/div[1]/c-slider/div/div[1]/div[3]/ul/app-service-card[8]/div/a/div[1]/img';
        $status=$this->pressButtonIf($xpath);             
        return ($status);                    
        }

    /* goto Smart meter App
     *
     * /html/body/app-root/app-partner-detail/div/main/div/div/div[2]/div/div/div[3]/div[2]/a
     * /html/body/app-root/app-start/div/app-startpage-loggedin/main/div/div[2]/div[1]/app-recently-used/div/div/div/div[1]/div[2]/ul/li[1]/ul/li/a
     */

    private function clickSMLinkIf()
        {
        $xpath1='/html/body/app-root/app-partner-detail/div/main/div/div/div[2]/div/div/div[3]/div[2]/a';
        $xpath2='/html/body/app-root/app-start/div/app-startpage-loggedin/main/div/div[2]/div[1]/app-recently-used/div/div/div/div[1]/div[2]/ul/li[1]/ul/li/a';
        $status=$this->pressButtonIf($xpath1); 
        if ($status===false)            // alternativen Link probieren, Web Layout wurde gestrafft
            {
            $status=$this->pressButtonIf($xpath2); // das dauert aber jetzt zum Laden
            }
        return ($status);                    
        }

    /* Privacy Information to click away
     * /html/body/div[2]/div[2]/div/div[2]/div[3]/div[1]/button[3]
     *
     */
    private function getPrivacyIf($debug=false)
        {
        if ($this->debug) echo "getPrivacyIf\n";
        $xpath='/html/body/div[2]/div[2]/div/div[2]/div[3]/div[1]/button[3]';
        $ergebnis = $this->getTextIf($xpath,$this->debug);    
        if ((strlen($ergebnis))>3) 
            {
            $status=$this->pressButtonIf($xpath); // das dauert aber jetzt zum Laden
            return($ergebnis);
            }
        else echo "kein Popup Window, alles in Ordnung \n";
        }     

    /* ein gelesen Pop up Window tritt in Erscheinung um über eien Wartung zu informieren 
     * //*[@id="mat-dialog-0"]/app-general-info--dialog/div/div[2]/section[2]/button
     * oder vollständig
     * /html/body/div[10]/div[2]/div/mat-dialog-container/app-general-info--dialog/div/div[2]/section[2]/button
     */

    private function getGelesenIf($debug=false)
        {
        if ($this->debug) echo "getGelesenIf\n";
        $xpath='/html/body/div[10]/div[2]/div/mat-dialog-container/app-general-info--dialog/div/div[2]/section[2]/button';
        $ergebnis = $this->getTextIf($xpath,$this->debug);    
        if ((strlen($ergebnis))>3) 
            {
            $status=$this->pressButtonIf($xpath); // das dauert aber jetzt zum Laden
            return($ergebnis);
            }
        else echo "kein Popup Window, alles in Ordnung \n";
        }

    /* /html/body/div[1]/app-root/div/div[1]/app-new-page-layout/main/div/app-smp-welcome/div/div[2]/app-meter-stats/div/div/app-meter-stats-verbrauch/div/div/div[1]/div[1]/span[1]
     */

    private function getEnergyValueIf($debug=false)
        {
        if ($this->debug) echo "getEnergyValueIf\n";
        $xpath='/html/body/div[1]/app-root/div/div[1]/app-new-page-layout/main/div/app-smp-welcome/div/div[2]/app-meter-stats/div/div/app-meter-stats-verbrauch/div/div/div[1]/div[1]/span[1]';
        $ergebnis = $this->getTextIf($xpath,$this->debug);    
        if ((strlen($ergebnis))>3) return($ergebnis);
        else echo "Fehler, keinen Vernünftigen Wert eingelesen: $ergebnis \n";
        }

    }

/* 
 * Bei EVN NetzNÖ einloggen und die aktuellen Zählerstände auslesen
 * RunAutomatic legt die einzelnen Schritte bis zur Auslesung des täglichen Stromzählerstandes fest
 * das Ergebnis wird in RESULT gespeichert, eine Auswerteroutine ist aktuell nur extern, sollte aber inbound kommen
 *
 *  __construct
 *  getResultCategory       
 *  setConfiguration        default, wie im SeleniumHandler
 *  writeEnergyValue 
 *  parseResult
 *  getKnownData  
 *  filterNewData  
 *  getEnergyValueId    
 *
 *  runAutomatic            Aufruf der einzelnen Steps, Statemachine
 *      checkCookiesButtonIf
 *      getTextValidLoggedInIf
 *      getTextValidLogInIf
 *      enterLoginName
 *      enterLoginPassword
 *      enterSliderIf
 *      clickImageIf
 *      clickSMLinkIf
 *      getEnergyValueIf
 *
 *
 */

class SeleniumEVN extends SeleniumHandler
    {
    private $configuration;                     //array mit Datensaetzen
    protected $CategoryIdDataEVN;           // Kategrie als Speicherort
    private $duetime,$retries;              //für retry timer
    protected $debug;

    /* Initialisierung der Register die für die Ablaufsteuerung zuständig sind
     */
    function __construct($debug=false)
        {
        $this->duetime=microtime(true);
        $this->retries=0;
        $this->debug=$debug;
        parent::__construct();
        $this->CategoryIdDataEVN = IPS_GetObjectIdByName("EVN",$this->CategoryIdData);        
        }

    function getResultCategory()
        {
        return($this->CategoryIdDataEVN);
        }

    /* im Handler vorhanden, muss überschreiben werden */

    public function setConfiguration($configuration)
        {
        $this->configuration = $configuration;
        }       

    /* **check  Energiewert abspeichern und archivieren
     *
     */
    function writeEnergyValue($value,$name="EnergyCounter")
        {
        $componentHandling = new ComponentHandling();           // um Logging mit Historisietrung (Archive) zu setzen
        $categoryIdResult = $this->getResultCategory();
        echo "Store the new values, Category LogWien RESULT $categoryIdResult.\n";
    
        /*function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false) */
        $oid = CreateVariableByName($categoryIdResult,$name,2,'kWh',"",1000);         // kein Identifier, darf in einer Ebene nicht gleich sein
        $componentHandling->setLogging($oid);

        //$value = "10.962";                            // nur der . wird zum Komma umgerechnet
        $value = str_replace(",",".",$value);           // Beistrich auf . umrechnen
        $wert=floatval($value);         // Komma wird nicht richtig interpretiert
        //echo "Umgewandelt ist $value dann : $wert \n";
        SetValue($oid,$wert);
        return($wert);
        }

    /* EVN, parse result register to werte
     * zuerst den String in ein Array mit einem Eintrag pro Zeile aufteilen
     * dann jede Zeile in durch blank getrennte Einträge als Spalten aufteilen
     * Spalte 0 ist die Zeit und Spalte 2 ist der Wert
     * Zeit wird TimeStamp, Wert wird Value (Komma durch Punkt ersetzen damit Wandlung auf einen Floatwert funktioniert)
     *
     * Ablauf in etwas so:
     *      $werte = $evn->parseResult($result); 
     *      $knownTimeStamps = $evn->getKnownData($LeistungID);
     *      $input = $evn->filterNewData($werte,$knownTimeStamps);
     *
     */
    function parseResult($result)
        {
        $werte=array();
        $werte=explode("\n",$result["Value"]);
        foreach ($werte as $index => $wert)
            {
            $werte[$index]=explode(" ",$wert);
            foreach ($werte[$index] as $column => $entries)
                {
                if ($column==0)  
                    {
                    $dateObj = DateTimeImmutable::createFromFormat("d.m.Y G:i",$werte[$index][$column]." ".$werte[$index][$column+1]);
                    //var_dump($dateObj);
                    if ($dateObj !== false)
                        {
                        $werte[$index]["TimeStamp"] = $dateObj->getTimestamp();
                        //echo date("d.m.Y H:i:s",$werte[$index]["TimeStamp"])."\n";
                        }
                    }
                if ($column==2)  
                    {
                    $value = str_replace(",",".",$werte[$index][$column]);
                    if (is_numeric($value)) $werte[$index]["Value"] = (float)$value;
                    }
                }
            }
        return($werte);
        }

    /* Leistungswerte aus dem Archive holen, 
     * aktuell keine zeitliche Beschränkung
     * damit Vergleich funktionieren kann anders anordnen, Index ist TimeStamp als Integer 
     */

    function getKnownData($LeistungID)
        {
        $archiveOps = new archiveOps();             
        $config=array();
        //$config["DataType"]="Array";
        $config["Aggregated"]=false;            // tägliche Werte, false geloggte Werte auslesen
        $config["manAggregate"]=false;            // daily, weeklytägliche Werte, false geloggte Werte auslesen
        $config["EventLog"]=false;      //kein EventLog analysieren
        //$config["LogChange"]=["pos"=>0.5,"neg"=>0.5,"time"=>(3600*3)];          // 3 Stunden Intervall für Wetterbeobachtungen, in Prozent auf den Vorwert, rund 5 mBar
        //$config["StartTime"]=strtotime("1.5.2022"); $config["EndTime"]=0;  
        //echo "StartTime ".date("d.m.Y H:i:s",$config["StartTime"])." EndTime ".date("d.m.Y H:i:s",$config["EndTime"])." \n";
        $werteArchived=$archiveOps->getValues($LeistungID,$config,2);                //1,2 für Debug
        //print_r($werteArchived["Values"]);
        $timeStampknown=array();
        if ((isset($werteArchived["Values"])) && (count($werteArchived["Values"])>0)) foreach ($werteArchived["Values"] as $wert) $timeStampknown[$wert["TimeStamp"]]=$wert["Value"];
        echo "--------\n";  
        return ($timeStampknown);
        }

    /* Werte vergleichen zwischen Array 1 und 2
     * wenn Timestamp eines Wertes in werte bereits in timeStampknown vorhanden ist nicht als neu identifizieren
     */

    function filterNewData($werte,$timeStampknown)
        {
        $archiveOps = new archiveOps(); 
        return ($archiveOps->filterNewData($werte,$timeStampknown));
        }



   /* check Energiewert auslesen vorbereiten, OID holen
     *
     */
    function getEnergyValueId($name="EnergyCounter")
        {
        $componentHandling = new ComponentHandling();           // um Logging zu setzen
        $categoryIdResult = $this->getResultCategory();
        $oid = IPS_GetObjectIdByName($name,$categoryIdResult);
        return ($oid);
        }


    /*  Statemachine, der reihe nach EVN NetzNÖ Portal abfragen
     *      (1) pres privacy button if its there 
     check if not logged in
     *      (2) prss LogOn Button
     *      (3) enter log in name
     *
     *
     *
     */

    public function runAutomatic($step=0)
        {
        echo "runAutomatic SeleniumEVN Step $step.\n";
        switch ($step)
            {
            case 0:
                echo "--------\n0: check if cookies button, if so press.\n";
                $status = $this->checkCookiesButtonIf();
                if ($status) echo "gefunden und gedrückt !\n";
                break;
            case 1:
                echo "--------\n1: check wether already logged in.\n";
                $result = $this->getTextValidLoggedInIf();
                if ($result===false) return(["goto" => 3]);  
                else echo "\nim nächsten Schritt ausloggen.\n";                 // neue Zeile, da noch textausgabe
                break;
            case 2:
                echo "--------\n2: Try to press Abmelden.\n";  
                $result = $this->goToLinkLogOutIf();                            // etwas schwierig, führt zu Fehlermeldung kann ncicht clicken
                echo "Ergebnis $result \n";
                break;
            case 3:
                echo "--------\n3: enter log in name wolfgangjoebstl, waltraudjoebstl etc..\n";
                $result = $this->enterLoginName($this->configuration["Username"]);            
                if ($result === false) 
                    {
                    echo "   --> failed\n";   
                    //return("retry");                      // nicht abbrechen, vielleicht schon eingelogged                               
                    }
                break;
            case 4:
                echo "--------\n4: enter log in Password and press Button.\n";            
                $this->duetime=microtime(true)+4;       // dieses delay wird erst im nächsten Schritt geprüft
                $result = $this->enterLoginPassword($this->configuration["Password"]);            
                if ($result === false) // kein Button gefunden oder nicht drückbar
                    {
                    echo "   --> failed\n";
                    //return("retry");                
                    }
                break;
            case 5:
                echo "--------\n5: wait 4 seconds.\n";              
                if ($this->debug) echo "continue to wait 4 seconds for first Window";            
                if (microtime(true)<$this->duetime) 
                    {
                    if ($this->debug) echo "\n";
                    return("retry");                
                    }            
                break;            
            case 6:
                echo "--------\n6: Goto Smart Meter Webportal, Press Link.\n";             
                $result = $this->gotoWebportalLinkIf();
                if ($result === false) 
                    {
                    echo "   --> failed, continue nevertheless\n";
                    return("retry"); 
                    }
                break;   
            case 7:
                echo "--------\n7: Press Button to Detailed Data.\n";            
                $result = $this->clickDetailedDataIf();
                if ($result === false) 
                    {
                    echo "   --> failed, press slider again\n";
                    return("retry");
                    }
                break; 
            case 8:
                echo "--------\n8: Read Energy Value.\n";             
                $result = $this->getEnergyValueIf(true);                // true für Debug
                if ($result===false) return (["goto" => 7]);
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                return(["Ergebnis" => $result]);
                break;            
            case 9:
                echo "--------\n9: Try to press Abmelden.\n";  
                $result = $this->goToLinkLogOutIf();                            // etwas schwierig, führt zu Fehlermeldung kann nicht clicken
                echo "Ergebnis Abmelden : $result \n";
                if ($result==false)  return("retry");
                else return (false);
                break;  
            default:
                return (false);
            }

        }

    /* check if there is a Cookie Button, if so press
     * es reicht pressButtonIf($xpath
     * /html/body/ngb-modal-window/div/div/app-cookie-consent-modal/div[2]/div/div/button[1]
     * alternative Implementierung mit vorher fragen ob da
     *
     * Anderer Ort ebenfalls check
     * /html/body/div[2]/div/div[2]/a[3]
     *
     */

    function checkCookiesButtonIf()
        {
        if ($this->debug) echo "CheckCookiesButtonIf ";
        $xpath='/html/body/ngb-modal-window/div/div/app-cookie-consent-modal/div[2]/div/div/button[1]';
        //return($this->pressButtonIf($xpath,true));
        // alternativ mit Abfrage ob der Taster überhaupt da iost
        $result = $this->getHtmlIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)."\n";
            //print $result;
            $result = $this->pressButtonIf($xpath,true);                                                      // true debug  
            } 
        else echo "Button not pressed.\n";

        $xpath='/html/body/div[2]/div/div[2]/a[3]';
        $result = $this->getHtmlIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)."\n";
            //print $result;
            $result = $this->pressButtonIf($xpath,true);                                                      // true debug  
            } 
        else echo "Button on another place also not pressed.\n";

        return ($result);
        }

    /* get text , find out whether being logged in 
     * if so skip login session
     * /html/body/app-root/div/app-navbar/div/div/div[2]/div[2]
     */
    function getTextValidLoggedInIf()
        {
        if ($this->debug) echo "getTextValidLoggedInIf : ";
        $xpath='/html/body/app-root/div/app-navbar/div/div/div[2]/div[2]';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)."\n";
            print $result;
            }
        return ($result);
        }     

    /* log out
     * /html/body/app-root/div/app-navbar/div/div/div[2]/div[2]/a[1] 
     */

    function goToLinkLogOutIf()
        {
        if ($this->debug) echo "goToLinkLogOutIf : ";
        $xpath='/html/body/app-root/div/app-navbar/div/div/div[2]/div[2]/a[1]';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)." : $result\n";
            $this->pressElementIf($xpath,true);                                                      // true debug , there is catch but still error
            //$this->pressButtonIf($xpath,true);
            //$this->pressSubmitIf($xpath,true);                                                      // true debug , error 
            }
        return ($result);

        }

    /* login entry username , look for xpath with input
     * /html/body/app-root/div/div/app-main/div/div[3]/div[1]/app-login/div/div/form/div[1]/label/input
     * es muss kein Button gedrückt werden
     * 
     */
    function enterLoginName($username)
        {
        $xpath = '/html/body/app-root/div/div/app-main/div/div[3]/div[1]/app-login/div/div/form/div[1]/label/input';              // extra Form username
        $this->sendKeysToFormIf($xpath,$username);
        /* press Button Weiter 
         * /html/body/div/main/form/fieldset/section[3]/div[1]/button[1]
         *
        $xpath = '/html/body/div/main/form/fieldset/section[3]/div[1]/button[1]';
        $this->pressButtonIf($xpath,true);         */ 
        }

    /* login entry password , look for xpath with input
     * /html/body/app-root/div/div/app-main/div/div[3]/div[1]/app-login/div/div/form/div[2]/label/input
     * then press button after password entry, look for button
     * /html/body/app-root/div/div/app-main/div/div[3]/div[1]/app-login/div/div/form/button
     * 
     */
    function enterLoginPassword($password)
        {
        $xpath = '/html/body/app-root/div/div/app-main/div/div[3]/div[1]/app-login/div/div/form/div[2]/label/input';              // extra Form password
        $this->sendKeysToFormIf($xpath,$password);
        // press Button Weiter 
        $xpath = '/html/body/app-root/div/div/app-main/div/div[3]/div[1]/app-login/div/div/form/button';
        return($this->pressButtonIf($xpath,true));          
        }

    /*
     * /html/body/app-root/div/div/app-consumption/div[3]/div[2]/div[11]/div[1]
     */
    private function clickDetailedDataIf()
        {
        echo "clickDetailedDataIf aufgerufen : \n";
        $xpath='/html/body/app-root/div/div/app-consumption/div[3]/div[2]/div[11]/div[1]';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            $status=$this->pressElementIf($xpath);  
            echo "Status $status \n";
            return ($status);                    
            }           
        $xpath='/html/body/app-root/div/div/app-consumption/div/div[2]/div[5]/div';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            $status=$this->pressElementIf($xpath);  
            echo "Status 2nd Try $status \n";
            return ($status);                    
            }           
        return (false);        
        }

    /* goto Smart meter App, Press Link for Webportal, look for a
     * /html/body/app-root/div/div/app-main/div/div[2]/div[1]/div[1]/div[3]/a
     * goto link
     */

    private function gotoWebportalLinkIf()
        {
        //$xpath='/html/body/app-root/div/div/app-main/div/div[2]/div[1]/div[1]/div[3]/a';
        //$status=$this->pressButtonIf($xpath);             // der Link ist nicht clickable, führt zu einem Fehler, daher die Url aufrufen  
        //return ($status);                    
        $url='https://smartmeter.netz-noe.at/#/verbrauch';
        return($this->updateUrl($url));
        }

    /* suche eine Tabelle in einem div
     * /html/body/app-root/div/div/app-consumption/div[3]/div[2]/div[11]/div[2]/div
     * /html/body/app-root/div/div/app-consumption/div[3]/div[2]/div[11]/div[2]/div/table
     *
     * /html/body/app-root/div/div/app-consumption/div/div[2]/div[5]/div[2]/app-consumption-records-table/div/table
     *      
     */

    private function getEnergyValueIf($debug=false)
        {
        if ($this->debug) echo "getEnergyValueIf\n";
        $xpath='/html/body/app-root/div/div/app-consumption/div[3]/div[2]/div[11]/div[2]/div';
        $ergebnis = $this->getTextIf($xpath,$this->debug);    
        if ((strlen($ergebnis))>3) return($ergebnis);
        else 
            {
            $xpath='/html/body/app-root/div/div/app-consumption/div/div[2]/div[5]/div[2]/app-consumption-records-table/div';
            $ergebnis = $this->getTextIf($xpath,$this->debug);    
            if ((strlen($ergebnis))>3) return($ergebnis);
            else  echo "Fehler, keinen Vernünftigen Wert eingelesen: $ergebnis \n";
            }
        return (false);
        }

    }


/* reading and Writing results to IP Symcon Registers 
 *
 * individuelle Funktionen um die www.drei.at Homepage vernünftig abzufragen
 * Wir benötigen zumindestens
 *  Startseite
 *  Rückfallsseite wenn der Prozess beim vorigen Durchlauf hängengeblieben ist
 *  Seite nach dem Login von dem verschiedene Abfragen ausgelöst werden können
 *  Seite oder Befehl zum Logout
 *
 *
 * es gibt folgende Funktionen
 *  construct
 *  runAutomatic
 *  pressLogoutButtonIf
 *  pressPrivacyButtonIf
 *  goToLoginLink
 *  goToLogoutLink
 *  goToLoginWithLogoutLink
 *  goToKostenLink
 *  enterLoginIf
 *
 */

class SeleniumDrei extends SeleniumHandler
    {
    protected $configuration;                 //array mit Datensaetzen
    private $duetime,$retries;              //für retry timer
    protected $debug;

    function __construct($debug=false)
        {
        $this->duetime=microtime(true);
        $this->retries=0;
        $this->debug=$debug;
        parent::__construct();          // nicht vergessen den parent construct auch aufrufen
        }

    /* seleniumDrei::runAutomatic
     * function is part of a state machine. State Machine is in upper section, state machine operates on responses
     * SeleniumOperations calls this function
     *
     * die Startseite wird automatisch über die Url aufgerufen, wenn es noch keine Session gibt, wenn eine Session offen ist, wird dort weitergemacht
     * Aufruf Startseite www.drei.at
     * (0) start waiting 2 seconds
     * (1) check if waiting time finished
     * (2) Privacy Butto suchen und drücken, timeout 2 secs ist schon abgelaufen
     * (3) Logout wenn erforderlich, timeout 4 secs
     * 
     *
     */

    public function runAutomatic($step=0)
        {
        if ($this->debug) echo "runAutomatic SeleniumDrei Step $step:";
        switch ($step)
            {
            case 0:     // beim ersten Mal ist öffnen, das kann länger dauern, zum Init von Timern verwenden
                $this->duetime=microtime(true)+2;       // dieses delay wird erst im nächsten Schritt geprüft
                if ($this->debug) echo "wait always 2 seconds for first Window, until ".date("i:s",$this->duetime);
                break;
            case 1:
                if ($this->debug) echo "continue to wait 2 seconds for first Window";            
                if (microtime(true)<$this->duetime) 
                    {
                    if ($this->debug) echo "\n";
                    return("retry");                
                    }            
                break;
            case 2:
                if ($this->debug) echo "wait up to 2 seconds for Privacy Button";
                $result=$this->pressPrivacyButtonIf();          // den Privacy Button clicken, wenn er noch da ist, spätestens nach 2 Sekunden aufgeben
                if ( ($result === false) && (microtime(true)<$this->duetime) )
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                $this->duetime=microtime(true)+4;                                    // vielelicht sind manche Server viel langsamer
                break;
            case 3:
                if ($this->debug) echo "go to Logout Link";
                $result=$this->goToLogoutLink();
                if ( ($result === false) && (microtime(true)<$this->duetime) )
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 4:
                if ($this->debug) echo "press Logout Button";            
                $this->pressLogoutButtonIf();
                if ($this->debug) echo "\n";
                break;
            case 5:
                if ($this->debug) echo "go to Login Link";
                $result=$this->goToLoginLink();
                //$result=$this->goToLoginWithLogoutLink();                
                if ( ($result === false) && (microtime(true)<$this->duetime) )
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 6:
                if ($this->debug) echo "Login in Url \"".$this->getUrl()."\"\n";
                $result = $this->enterLoginIf($this->configuration["Username"],$this->configuration["Password"]);
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                $this->duetime=microtime(true)+2;           // zumindest 2 Sekunden einräumen
                break;
            case 7:
                /* //*[@id="site-wrapper"]/header/ul[2]/li[6]/ul/li[3]/a */
                if ($this->debug) echo "go to Kosten Link, warte ca. 2 Sekunden";
                $result=$this->goToKostenLink();
                if ( ($result === false) && (microtime(true)<$this->duetime) )
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    } 
                else if ($this->debug) echo "  Zeit bis Duetime ".($this->duetime-microtime(true))."\n";
                break;
            case 8:
                if ($this->debug) echo "fetch Guthaben Data from Host";            
                $result=$this->fetchGuthabenDatafromHost();
                if ($result===false) return ("retry");
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                $filename = $this->configuration["Username"].".txt";               
                if (strlen($result)>10) file_put_contents ( $this->configuration["WebResultDirectory"].$filename, $result);
                //$guthabenHandler->parsetxtfile($nummer,$config["WebResultDirectory"],$filename);
                return(["Ergebnis" => $result]);
                break;
            case 9:
                if ($this->debug) echo "go to Login Link";            
                $this->goToLoginLink();                 // zurück zum Login Link für personal Information
                break;
            case 10:
                if ($this->debug) echo "press Logout Button";            
                $this->pressLogoutButtonIf();
                break;
            default:
                return (false);                
            }
        if ($this->debug) echo "\n";            
        }

    function pressLogoutButtonIf()
        {        
        /* //*[@id="logout"]
         * //*[@id="ssoLoginForm"]/div/a
        * https://www.drei.at/selfcare/restricted/prepareMyProfile.do
        */
        $xpath='//*[@id="logout"]';
        $xpath2 = '//*[@id="ssoLoginForm"]/div/a';
        $result = $this->pressButtonIf($xpath);
        if ($result === false ) $result = $this->pressButtonIf($xpath2);
        return ($result);
        }

    function pressPrivacyButtonIf()
        {        
        /* Banner click xpath
        * /html/body/div[4]/div[2]/div/div/div[2]/div/div/button
        * /html/body/div[5]/div[2]/div/div/div[2]/div/div/button
        * <div id="onetrust-consent-sdk">
        * https://www.drei.at/selfcare/restricted/prepareMyProfile.do
        */
        $xpath='/html/body/div[@id="onetrust-consent-sdk"]/div[2]/div/div/div[2]/div/div/button';
        return($this->pressButtonIf($xpath));
        }

    /* press link button, Personenzeichen
     *  //*[@id="site-wrapper"]/header/ul[2]/li[9]/ul/li[1]/a
     * Manchmal ist der link nicht displayed (?), direkt auf den Link gehen
     *  das ist ein Link hier hin: https://www.drei.at/de/login/?retUrl=https://www.drei.at/selfcare/restricted/prepareMyProfile.do
     *
     */
    function goToLoginLink()
        {
        $xpath='//*[@id="site-wrapper"]/header/ul[2]/li[9]/ul/li[1]/a';
        $url='https://www.drei.at/de/login/?retUrl=https://www.drei.at/selfcare/restricted/prepareMyProfile.do';
        $status=$this->pressButtonIf($xpath);             
        if ($status===false) return($this->updateUrl($url));
        return ($status);
        } 

    /* Logout erzwingen
     */
    function goToLogoutLink()
        {
        $url='https://www.drei.at/selfcare/restricted/prepareMyProfile.do';
        return($this->updateUrl($url));
        }

    /* press link butto, Personenzeichen
     *  //*[@id="site-wrapper"]/header/ul[2]/li[9]/ul/li[1]/a
     * Manchmal ist der link nicht displayed (?), direkt auf den Link gehen
     *  das ist ein Link hier hin: https://www.drei.at/de/login/?retUrl=https://www.drei.at/selfcare/restricted/prepareMyProfile.do
     *
     */
    function goToLoginWithLogoutLink()
        {
        $xpath = '//*[@id="ssoLoginForm"]/div/a';
        $url='https://www.drei.at/de/logout/?retUrl=https%3A%2F%2Fwww.drei.at%2Fde%2Flogin%2F%3FretUrl%3Dhttps%3A%2F%2Fwww.drei.at%2Fselfcare%2Frestricted%2FprepareMyProfile.do';
        $status=$this->pressButtonIf($xpath);             
        if ($status===false) return($this->updateUrl($url));
        return ($status);
        } 

    /* goto Kosten Link
     * //*[@id="site-wrapper"]/header/ul[2]/li[6]/ul/li[3]/a
     */
    function goToKostenLink()
        {
        $xpath='//*[@id="site-wrapper"]/header/ul[2]/li[6]/ul/li[3]/a';
        $url = 'https://www.drei.at/selfcare/restricted/prepareCoCo.do';
        $status= $this->pressButtonIf($xpath);                                      // erst den Button für den Link drücken. Wenn nicht erfolgreich
        if ($status===false) return($this->updateUrl($url));                        // dann den dahinterliegenden link aufrufen
        return ($status);                  
        } 


    /* login entry username und password
     * ensure that logout was done before
     * "https://www.drei.at/de/logout/?retUrl=https%3A%2F%2Fwww.drei.at%2Fde%2Flogin%2F%3FretUrl%3Dhttps%3A%2F%2Fwww.drei.at%2Fselfcare%2Frestricted%2FprepareMyProfile.do
     */
    function enterLoginIf($username,$password)
        {
        if ($this->debug) echo "enter login with $username ($password) \n";
        $xpath = '//*[@id="ssoUsername"]';        // relative path
        $result = $this->sendKeysToFormIf($xpath,$username);              // admin
        if ($result)                // nur weitermachen, wenn es das Form gibt)
            {
            if ($this->debug) echo "   -> enter Password.\n";
            $xpath = '//*[@id="ssoPassword"]';        // relative path
            $this->sendKeysToFormIf($xpath,$password);              // admin        
            if ($this->debug) echo "   -> press Submit Button\n";
            $xpath = '//*[@id="ssoSubmitButton"]';
            $this->pressSubmitIf($xpath,$this->debug);        
            if ($this->debug) echo "   -> Button pressed\n";
            }
        return($result);
        }

    /* das Guthaben und die anderen Informationen können auf zwei verschiedenen Orten stehen - alle probieren
     * Datenabfrage mit getHtmlIf($xpath, es kommen entweder html oder wenn nicht vorhanden textpassagen zurück
     */
    function fetchGuthabenDatafromHost()
        {   
        if ($this->active===false) return;

        /* guthaben auslesen , immer vorher warten bis sich die Seite aufgebaut hat, da arbeiten langsame Scripts im Hintergrund
        * //*[@id="site-wrapper"]/main/div[5]/div[5]/div[1]/div
        * //*[@id="link_coco_box"]/div/div[1]/div[1]/h1 
        * //*[@id="link_coco_box"]/div/div[2] 
        * //*[@id="link_coco_box"]/div/div[2] 
        *
        *//*[@id="site-wrapper"]/main
        *
        *//*[@id="site-wrapper"]/main/div[3]/div[2]
        *
        * //*[@id="current-cost"]
        *
        * /html/body/div[3]/main/div[4]
        * //*[@id="site-wrapper"]/main/div[4]
        *
        */

        $tryXpath = array(
                0 => '//*[@id="site-wrapper"]/main/div[4]',
                1 => '//*[@id="site-wrapper"]/main/div[5]',
                2 => '//*[@id="site-wrapper"]/main/div[5]/div[3]',
                );

        foreach ($tryXpath as $index => $xpath)
            {
            if ($this->debug) echo "Try xpath $index :\n";
            $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
            if ((strlen($ergebnis))>10) return($ergebnis);
            else echo $ergebnis;
            }

        if (false)
            {        
            if ($this->debug) echo "Try site-wrapper/main/div[5]/div[3]\n";
            $xpath='//*[@id="site-wrapper"]/main/div[5]/div[3]';
            $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
            if ((strlen($ergebnis))>10) return($ergebnis);
            else echo $ergebnis;

            if ($this->debug) echo "Try current-cost\n";
            $xpath = '//*[@id="current-cost"]';
            $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
            if ((strlen($ergebnis))>10) return($ergebnis);
            else echo $ergebnis;

            if ($this->debug) echo "Try coco_box\n";
            $xpath='//*[@id="link_coco_box"]';     
            //$ergebnis = $this->getTextIf($xpath);
            $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
            //if ($ergebnis === false) return (false);          // nicht gleich aufgeben, erst anderen Link probieren
            if ((strlen($ergebnis))>10) return($ergebnis);
            
            if ($this->debug) echo "Try site-wrapper/main\n";
            $xpath='//*[@id="site-wrapper"]/main';     
            $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
            }        
        return($ergebnis);

        }
    }           // ende class


/* Selenium Anpassungen für den Iiyama Monitor
 * Ansteuerung geht möglicherweise auch über curl
 * erst einmal so lassen
 *
 * zusaetzliche Funktionen sind
 *
 *
 */
class SeleniumIiyama extends SeleniumHandler
    {
    protected $configuration;                 //array mit Datensaetzen
    private $duetime,$retries;              //für retry timer
    protected $debug;

    function __construct($debug=false)
        {
        $this->duetime=microtime(true);
        $this->retries=0;
        $this->debug=$debug;
        parent::__construct();          // nicht vergessen den parent construct auch aufrufen
        }

    /* SeleniumIiyama::runAutomatic
     *
     * Montor Ein/Aus Schalten
     *
     */

    public function runAutomatic($step=0)
        {
        if ($this->debug) echo "runAutomatic SeleniumIiyama Step $step.\n";
        switch ($step)
            {
            case 0:
                if ($this->debug) echo "go to Information Link";
                $result = $this->gotoInformationLink();
                break;               
            case 1:  
                if ($this->debug) echo "fetch Information from Host";              
                $result=$this->fetchInformationfromHost(); 
                if ($result===false) return ("retry");
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                return(["Ergebnis" => $result]);                         
                break;
            case 2:  
                if ($this->debug) echo "go to Control Link";              
                $result=$this->goToControlLink();
                break;
            case 3:
                print_r($this->configuration);
                if (isset($this->configuration["Power"])) $power=$this->configuration["Power"];
                else $power = "On";
                if ($power=="On")
                    {
                    $result=$this->pressButtonPowerOn();
                    }
                else
                    {
                    $result=$this->pressButtonPowerOff();
                    }
                if ($result===false) return ("retry");                    
                break;
            case 4:
                if ($this->debug) echo "go to Information Link";
                $result = $this->gotoInformationLink();
                break;                 
            case 5:  
                if ($this->debug) echo "fetch Information from Host";              
                $result=$this->fetchInformationfromHost(); 
                if ($result===false) return ("retry");
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                return(["Ergebnis" => $result]);                         
                break;                
            default:
                return (false);                
            }        
        }

    /* Status lesen
     */
    private function goToInformationLink()
        {
        echo "goto Information Link:\n";
        //print_r($this->configuration); echo "--\n";
        $url = $this->checkUrl($this->configuration["URL"]);
        $url .= '/home.htm';
        return($this->updateUrl($url));
        }

    /* Power On/off
     */
    private function goToControlLink()
        {
        echo "goto Control Link:\n";
        //print_r($this->configuration); echo "--\n";
        $url = $this->checkUrl($this->configuration["URL"]);
        //$url .= '/tgi/control.tgi';
        $url .= '/control.htm';
        return($this->updateUrl($url));
        }

    //*[@id="table5"]/tbody/tr[2]/td[2]/font/input[2]

    private function fetchInformationfromHost()
        {
        if ($this->active===false) return;          // Variable Selenium Handler

        /* 
         * /html
         * /html/body/table/tbody/tr[2]/td/table 
         * /html
         * /html/frameset/frame[2]
         * body > table > tbody > tr:nth-child(2) > td > table
         */
        $tryXpath = array(
                0 => '/html',
                );

        foreach ($tryXpath as $index => $xpath)
            {
            if ($this->debug) echo "Try xpath $index :\n";
            $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
            if ((strlen($ergebnis))>10) return($ergebnis);
            else echo $ergebnis;
            }
        return($ergebnis);            
        }

    private function pressButtonPowerOn()
        {
        /* //*[@id="table5"]/tbody/tr[2]/td[2]/font/input[2]
         *   //*[@id="table5"]/tbody/tr[2]/td[2]/font/input[1]
         *   /html/body/form/table/tbody/tr[2]/td/table/tbody/tr/td[1]/table/tbody/tr[2]/td[2]/font/input[1]
         */
        if ($this->debug) echo "pressButtonPowerOn";                   
        $xpath=' //*[@id="table5"]/tbody/tr[2]/td[2]/font/input[2]';
        return($this->pressButtonIf($xpath,$this->debug));


        }

    private function pressButtonPowerOff()
        {
            /* //*[@id="table5"]/tbody/tr[2]/td[2]/font/input[1]  
             */
        if ($this->debug) echo "pressButtonPowerOff";               
        $xpath=' //*[@id="table5"]/tbody/tr[2]/td[2]/font/input[1]';
        return($this->pressButtonIf($xpath,$this->debug));


        }

    }           // ende class

/* ORF Fernsehprogramm abfragen
 *
 * wenig Stress da kein einloggen notwendig ist, aber die Auswertung der Anzeige der Fernsehprogramme ist schwieriger geworden
 * runAutomatic fahrt die einzelnen Abfragen durch
 * der Aufruf erfolgt von automatedQuery , zuerst wird die Instanz erstellt und dann wenn vorhanden die Config gesetzt
 * runAutomatic geth Schritt für Schritt durch
 */

class SeleniumORF extends SeleniumHandler
    {
    protected $configuration;                 //array mit Datensaetzen
    private $duetime,$retries;              //für retry timer
    private $orfName;                               // index für OrfSender
    protected $debug;

    function __construct($debug=false)
        {
        $this->duetime=microtime(true);
        $this->retries=0;
        $this->orfName=0;                   //erster Index
        $this->debug=$debug;
        parent::__construct();          // nicht vergessen den parent construct auch aufrufen
        }

    public function runAutomatic($step=0)
        {
        $ergebnis=array();
        echo "runAutomatic SeleniumORF Step $step.\n";
        switch ($step)
            {
            case 0:
                $this->pressPrivacyButtonIf();
                break;
            case 1:
                /* https://tv.orf.at/all/20210302/filter?cat=Film   nur die Filme abfragen */
                //$this->goToNavBarItem(1);            // select TV Programm by click
                //$url="http://tv.orf.at/all/".date("Ymd")."/filter?cat=Film";
                $url="https://tv.orf.at/program/orf1/index.html";                       // ORF umgestellt auf https
                $this->goToUrl($url);
                break;
            case 2:
                $result = $this->fetchMoviesfromActualDay(true);            // true get html
                /*echo "Ergebnis ------------------\n";
                //echo "$result\n-----------------\n"; 
                // ungewöhnlich, alle Einträge enden mit \n etwas zusammenfassen
                $display=explode("\n",$result);
                foreach ($display as $index=>$item)
                    {
                    if (strpos($item,":") == 2) 
                        {
                        //echo "   ".strpos($item,":");
                        echo "\n";          // vielleicht, sehr wahrscheinlich eine Uhrzeit
                        }
                    echo $item." ";
                    //if (fmod($index+3,4)==0) echo "\n";           // leider unregeklmaessige Anzahl Einträge
                     }   */
                echo "Step 3. Anhand Config abspeichern und weiter machen, oder aufhören.\n";  
                if (isset($this->configuration["tv"][$this->orfName]))
                    {
                    echo "Configuration found, speichere TV Programm in ".$this->configuration["tv"][$this->orfName]."   Index ist aktuell : ".$this->orfName." .\n"; 
                    $ergebnis["Name"] = $this->configuration["tv"][$this->orfName]; 
                    $this->orfName++;                 
                    } 
                else $ergebnis["goto"] = 5;
                $ergebnis["Ergebnis"] = $result;
                return($ergebnis); 
                break;
            case 3:
                $url="https://tv.orf.at/program/orf2/index.html";                       // ORF umgestellt auf https
                $this->goToUrl($url);               
                break; 
            case 4:
                $result = $this->fetchMoviesfromActualDay(true);            // true get html
                /*echo "Ergebnis ------------------\n";
                //echo "$result\n-----------------\n"; 
                // ungewöhnlich, alle Einträge enden mit \n etwas zusammenfassen
                $display=explode("\n",$result);
                foreach ($display as $index=>$item)
                    {
                    if (strpos($item,":") == 2) 
                        {
                        //echo "   ".strpos($item,":");
                        echo "\n";          // vielleicht, sehr wahrscheinlich eine Uhrzeit
                        }
                    echo $item." ";
                    //if (fmod($index+3,4)==0) echo "\n";           // leider unregeklmaessige Anzahl Einträge
                     }   */
                if (isset($this->configuration["tv"][$this->orfName]))
                    {
                    echo "Configuration found, speichere TV Programm in ".$this->configuration["tv"][$this->orfName]."   Index ist aktuell : ".$this->orfName." .\n"; 
                    $ergebnis["Name"] = $this->configuration["tv"][$this->orfName]; 
                    $this->orfName++;                 
                    } 
                else $ergebnis["goto"] = 5;
                $ergebnis["Ergebnis"] = $result;
                return($ergebnis);                
                break;
            case 5:                
                $url="https://wetter.orf.at/wien/prognose";                       // ORF umgestellt auf https, jetzt noch gleich das Wetter für Wien aufrufen
                $this->goToUrl($url);               
                break;
            case 6:
                $result = $this->fetchWeatherPrognose(false);            // true get html , false get text               
                $ergebnis["Ergebnis"] = $result;
                return($ergebnis);                
                break;
            default:
                return (false);                
            }
        }

    public function getSteps()
        {


        }

    /* Steps
     *
     * //*[@id="ds-accept"]/span        Button Accept Cookies
     *
     */

    function pressPrivacyButtonIf()
        {        
        $xpath='//*[@id="ds-accept"]/span';
        $result = $this->pressButtonIf($xpath);
        if ($result) echo "pressed PrivacyButton.\n";
        }     

    function goToUrl($url)
        {
        $this->updateUrl($url);
        }

    /* press link to TV Page, on https://www.orf.at/ press first link Fernsehen, results into https://tv.orf.at/
     *  //*[@id="ss-networkNavigation"]/li[1]/a         der erste Link ist die TV Programm seite, besser direkte Navigation auf Url
     */

    function goToNavBarItem($i=1)
        {
        "goTo NavBar Item $i \n";
        $xpath='//*[@id="ss-networkNavigation"]/li['.$i.']/a';
        $this->pressButtonIf($xpath);             
        }         

    /* das Filmprogramm runterladen 
     * //*[@id="content"]/div[1] 
     * //*[@id="broadcast-list"]/li[2]
     * //*[@id="broadcast-list"]                        abgeänderte Struktur, liest die Programme und nicht die Navigation
     */

    function fetchMoviesfromActualDay($style=false,$debug=false)
        {
        $xpath='//*[@id="broadcast-list"]';  
        if ($style) 
            {
            $ergebnis = $this->getHtmlIf($xpath);    
            $page=$this->getInnerHtml();                     
            //$this->analyseHtml($page,true);                                     // nicht mehr verwendet, Parameter $displayMode,$commandEntry bei displayMode true wird angezeigt, bei false erst ab commandEntry "DIV"
            /* besser queryFilterHtml verwenden, Eintraege entsprechend https://www.w3schools.com/xml/xpath_syntax.asp erstellen
             *      nodename	Selects all nodes with the name "nodename"
             *      /	Selects from the root node
             *      //	Selects nodes in the document from the current node that match the selection no matter where they are
             *      .	Selects the current node
             *      ..	Selects the parent of the current node
             *      @	Selects attributes
             *
             */

            $dom = new DOMDocument;     
            libxml_use_internal_errors(true);               // Header, Nav and Section are html5, may cause a warning, so suppress     
            $dom->loadHTML($page);                  // den Teil der Homepage hineinladen
            libxml_use_internal_errors(false);
            $xpath = new DOMXpath($dom);
            $links = $xpath->query('//li[@*]');
            if ($debug) echo "Insgesamt ".count($links)." Links gefunden für \"'//li[@*]'\"\n";
            
            // bis hier gleich wie mit queryFilterHtml, dann aber fortschrittlicher/anders da walkhtml verwendet wird

            $count=0; $maxcount=5;          // max 5 Einträge einsammeln, dann isses gut
            $result=array();

            $tmp_doc = new DOMDocument();   // das Ergebnis Dokumnet, nur die links speichern die passen könnten
            foreach ($links as $link)                       //ein DomElement nach dem anderen durchgehen
                {
                $xmlArray = new App_Convert_XmlToArray();           // class aus AllgemeineDefinitionen, convert html/xml printable string to an array
                $style = $link->getAttribute('class');
                if ($style == 'broadcast') 
                    {
                    if ($debug) echo "   -> found:  ".$link->nodeName." , ".$link->nodeType." $style\n"; 
                    $result[$count] = $xmlArray->walkHtml($link,false);                                         
                    if (($count++)<$maxcount) 
                        {
                        //var_dump($link);
  
                        if ($debug) print_R($result[($count-1)]);
                        }
                    $tmp_doc->appendChild($tmp_doc->importNode($link,true));                 
                    }
                }
            $ergebnis="";       //der tatsächliche return Wert
            $deleteIndex = array("class","xmlns","width","height","viewbox","transform","d");
            foreach ($result as $index => $entry)
                {
                foreach ($deleteIndex as $item)
                    {
                    if (isset($entry[$item])) unset($result[$index][$item]);
                    }
                //if (isset($entry["broadcast"])) echo $entry["broadcast"]."\n";
                if (isset($entry["broadcast-date"])) $ergebnis .= $entry["broadcast-date"];
                if (isset($entry["broadcast-titles"])) $ergebnis .= $entry["broadcast-titles"]."\n";
                }
            /* print_R($result);


            function dom_dump($obj) 
                {
                echo "dom_dump aufgerufen.\n";
                if ($classname = get_class($obj)) 
                    {
                    $retval = "Instance of $classname, node list: \n";
                    switch (true) 
                        {
                        case ($obj instanceof DOMDocument):
                            $retval .= "XPath: {$obj->getNodePath()}\n".$obj->saveXML($obj);
                            break;
                        case ($obj instanceof DOMElement):
                            $retval .= "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
                            break;
                        case ($obj instanceof DOMAttr):
                            $retval .= "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
                            //$retval .= $obj->ownerDocument->saveXML($obj);
                            break;
                        case ($obj instanceof DOMNodeList):
                            for ($i = 0; $i < $obj->length; $i++) 
                                {
                                $retval .= "Item #$i, XPath: {$obj->item($i)->getNodePath()}\n"."{$obj->item($i)->ownerDocument->saveXML($obj->item($i))}\n";
                                }
                            break;
                        default:
                            return "Instance of unknown class";
                        }
                    } 
                else 
                    {
                    return 'no elements...';
                    }
                return htmlspecialchars($retval);
                }

            function getArray($node)
                {
                $array = false;
                echo "getArray nodeName ".$node->nodeName."  ";
                if ($node->hasAttributes())
                    {
                    foreach ($node->attributes as $attr)
                        {
                        echo " Attr ".$attr->nodeName." = ".$attr->nodeValue."   ";
                        $array[$attr->nodeName] = $attr->nodeValue;
                        }
                    }

                if ($node->hasChildNodes())
                    {
                    if ($node->childNodes->length == 1)
                        {
                        $array[$node->firstChild->nodeName] = $node->firstChild->nodeValue;
                        }
                    else
                        {
                        foreach ($node->childNodes as $childNode)
                            {
                            echo "\n childNode ".$childNode->nodeName." :";
                            if ($childNode->nodeType != XML_TEXT_NODE)
                                {
                                $array[$childNode->nodeName][] = getArray($childNode);
                                }
                            }
                        }
                    }
                return ($array);
                }

            foreach ($tmp_doc as $node) 
                {
                echo "nodeName ".$node->nodeName."  \n";
                dom_dump($node);
                }       
            $ergebnis = $tmp_doc->saveHTML(); 
            $array = App_Convert_XmlToArray::XmlToArray($ergebnis);            // input muss string sein
            print_R($array);        */
            }
        else $ergebnis = $this->getTextIf($xpath);
        if ((strlen($ergebnis))>10) return($ergebnis);            
        }

    /* das Wetter runterladen 
     * //*[@id="content"]/div[3]
     * //*[@id="ss-storyText"]/div
     */

    function fetchWeatherPrognose($style=false,$debug=false)
        {
        echo "Fetch Wetterprognose:\n";
        //$xpath='//*[@class="fullTextWrapper"]';  
        $xpath='//*[@id="ss-storyText"]/div';
        if ($style) 
            {
            $ergebnis = $this->getHtmlIf($xpath);    
            $page=$this->getInnerHtml();                     
            $this->analyseHtml($page,true);                                     // nicht mehr verwendet, Parameter $displayMode,$commandEntry bei displayMode true wird angezeigt, bei false erst ab commandEntry "DIV"
            }
        else $ergebnis = $this->getTextIf($xpath,true);
        if ((strlen($ergebnis))>10) return($ergebnis);            
        else echo "noch kein Ergebnis gefunden. Ergebnis ist immer ein String.\n";
        }


    }           // ende class



/* Bei Easycharts einlogen und die aktuellen Portfolio Kurse auslesen
 * zusaetzliche Funktionen sind
 *      __construct                         OID von Kategorie EASY in data herausfinden und speichern
 *      getResultCategory                   OID von Kategorie EASY in data ausgeben
 *      getEasychartConfiguration           Easychart Configuration und Orderbook 
 *      getEasychartOrderConfiguration      Easychart Orderbook ohne Short und andere Indexes, Orders Index ebenfalls entfernt
 *      getEasychartSharesConfiguration     die bereits ausgeführten Splits um die Aktienkurse zurückzurechnen
 *      parseResult
 *      evaluateResult
 *      evaluateValue
 *      writeResult                         die Ergebnisse in spezielle Register schreiben
 *      getDebotBooksfromConfig             aus der Guthaben Configuration für die Arbeit von Selenium
 *      writeResultConfiguration
 *      updateResultConfigurationSplit      in die interne Konfiguration auch Split Werte dazunehmen
 *      getResultConfiguration
 *      createJointConfiguration            eine gemeinsame Konfiguration aus den Daten im Config File und aus Easycharts erstellen und ausgeben
 *      showDepotConfiguration
 *      getSplitfromOid
 *      showDepotConfigurations             es werden auch mehrer Depots ausgegeben wo lookup passt, es gibt einen index
 *      getDepotConfigurations              wie showDepotConfigurations mit lookup false
 *      writeResultAnalysed
 *      calcOrderBook
 *      createDepotBook
 *      evaluateDepotBook
 *
 * Funktionen zum Einlesen des Host
 *      runAutomatic
 *      getTextValidLoggedInIf
 *      enterLogin
 *      gotoLinkMusterdepot
 *      readTableMusterdepot
 *      gotoLinkMusterdepot3
 *      getTablewithCharts
 *
 */

 /* Easycharts Neu, Webpage hat komplette neue Struktur
  *
  *
  */
class SeleniumEasycharts extends SeleniumEasychartModul
    {


    /* EasyCharts, hier wird Schritt für Schritt das Automatische Abfragen der Easychart Webseite abgearbeitet
     * die alten Routinen sind noch in EaysychartModul
     *  (0) Cookies auf Easybank.at bestätigen wenn notwendig, wenn Url bereits auf plus dann auf 6, wenn bereits auf musterdepot dann auf 7
     *  (1) Goto to Markets (https://www.easybank.at/markets/wertpapiere/intro)
     *  (2) Cookies erneut abfragen
     *  (3) Goto persönliches profil MyMarkets
     *  (4) nur vorbereiten, hier passiert nichts
     *  (5) Username, Passwort eingeben, Login Buttorn drücken, einloggen es öffnet sich die Profileingabe 
     *  (6) goto Musterdepot Link
     *  (7) Dropdown mit vorhandenen Musterdepots auswerten
     *  (8) erstes oder nächstes konfiguriertes Musterdepot auswählen und goto
     *  (9) Musterdepot Tabelle einlesen, neues Format, wird als result gespeichert
     *
     * löst writeResult mit Depotname unter Category Easy aus. Kann mit readResult und Depotname wieder gelesen werden
     * Es gibt eine Konfiguration Depot, diese gibt den Namen des auszulesenden Depots an
     * Wert kann ein Array sein mit mehren Werten.
     *
     */
    public function runAutomatic($step=0)
        {
        if ($this->debug) echo "runAutomatic SeleniumEasycharts Step $step.\n";
        switch ($step)
            {
            case 0:     // check cookies for privacy and jump if already logged in or at musterdepot
                $this->depotCount=1;              // es können mehrere Depots in einem Durchgang abgefragt werden, gesamte Anzahl hier speichern
                if (isset($this->configuration["Depot"])) 
                    {
                    if (is_array($this->configuration["Depot"]))
                        {
                        $this->depotCount=count($this->configuration["Depot"]);    
                        }
                    }
                if ($this->debug) echo "--------\n$step: check if cookies button, if so press. ".$this->depotCount." Depots are read.\n";

                $status = $this->checkCookiesButtonIf();        // kann sowohl für easycharts als auch easybank web adresse verwendet werden
                if ($status) echo "gefunden und gedrückt !\n";
                $actualUrl=$this->getUrl();
                echo "Wir sind jetzt auf dieser Url : $actualUrl Shortcuts sind möglich.\n";
                if ($actualUrl == 'https://www.easybank.at/markets/plus/profile') return(["goto" => 6]);
                if (strpos($actualUrl,'https://www.easybank.at/markets/musterdepot/')===0) return(["goto" => 7]);
                break;
            case 1:     // goto Market, Button upper right            
                if ($this->debug) echo "--------\n$step: goto Link Market.\n";
                $result = $this->gotoLinkMarketIf();                // Market Button rechts oben, jump to https://www.easybank.at/markets/wertpapiere/intro
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                echo "Wir sind jetzt auf dieser Url : ".$this->getUrl()."\n";
                break;
            case 2:     // check cookies for privacy once more
                if ($this->debug) echo "--------\n$step: check if cookies button, if so press.\n";
                $status = $this->checkCookiesButtonIf();
                if ($status) echo "gefunden und gedrückt !\n";
                break;
            case 3:     // goto MyMarkets, personal Profile
                if ($this->debug) echo "--------\n$step: go to personal Link MyMarkets and Login. Start from Url : ".$this->getUrl()."\n";
                $result = $this->gotoLinkMyMarketIf();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                echo "Wir sind jetzt auf dieser Url : ".$this->getUrl()." Result $result\n";
                break;
            case 4:     // just prepare
                $this->depotCount=1;              // es können mehrere Depots in einem Durchgang abgefragt werden
                if (isset($this->configuration["Depot"])) 
                    {
                    if (is_array($this->configuration["Depot"]))
                        {
                        $this->depotCount=count($this->configuration["Depot"]);    
                        }
                    }
                if ($this->debug) echo "--------\n$step: check if not logged in, then log in. ".$this->depotCount." Depots are read. \n";
                echo "Wir sind jetzt auf dieser Url : ".$this->getUrl()."\n";
                //if ($this->getTextValidLoggedInIf()!==false)  return(["goto" => 6]);                // brauche ein LogIn Fenster
                break;
            case 5:     // enter login form, password form, enter Login Button
                if ($this->debug) echo "--------\n$step: enter form log in.\n";
                echo "Wir sind jetzt auf dieser Url : ".$this->getUrl()."\n";
                $result = $this->enterLoginElement($this->configuration["Username"]);            
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                if ($this->debug) echo "--------\n$step: enter form Password.\n";
                $result = $this->enterPasswordElement($this->configuration["Password"]);            
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                if ($this->debug) echo "--------\n$step: press button log in.\n";
                $result = $this->pressLoginButtonIf();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                echo "Wir sind jetzt auf dieser Url : ".$this->getUrl()."\n";
                break;
            case 6:     // goto musterdepot area
                if ($this->debug) echo "--------\n$step: we are logged in, go to portfolio.\n";
                echo "Wir sind jetzt eingelogged und befinden uns auf dieser Url : ".$this->getUrl()."\n";
                // look for dropdown        /html/body/div[1]/main/div/section/div[1]/button
                // read innerhtml of this   /html/body/div[1]/main/div/section/div[1]/div[1]
                $result = $this->pressMusterDepotLinkIf();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 7:     // Dropdown Feld mit einzelnen Links zu den Portfolios auswerten
                $actualUrl = $this->getUrl();
                if ($this->debug) echo "--------\n$step: we are logged in, and on portfolio $actualUrl.\n";
                $this->tableOverview = $this->readDropDownMusterdepot();         // die Namen aller Musterdepots rausbekommen, mit der Liste kann dann ein Dropdown angesteuert werden
                // array hat index 0..n für die portfolios, gleich wie dropdown list und verweist mit .name auf den Portfolio Namen und mit .link auf den Link der angesprungen werden muss
                if ($this->debug) echo "Ergebnis ------------------\n".json_encode($this->tableOverview)."\n";                 
                break;
            case 8:     // goto to first or next musterdepot given from configuration
                if ($this->debug) echo "--------\n$step: goto Link Musterdepot.\n";
                $found=false;
                if (isset($this->configuration["Depot"])) 
                    {
                    $depot=$this->configuration["Depot"][($this->depotCount-1)];
                    foreach ($this->tableOverview as $index => $entry)
                        {
                        $pos1=strpos($entry["name"],$depot);
                        if ($pos1 !== false) $found = $index;
                        }
                    echo "Gesuchtes Musterdepot $depot ist auf Position $found \n";                            
                    }
                if ($found !== false) $result = $this->gotoLinkMusterdepotNum($found);            // zum jeweiligen Musterdepot wechseln
                else $result = $this->gotoLinkMusterdepotNum();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 9:     // get Musterdepot, return Ergebnis und Musterdepot to automatedQuery in seleniumOperations
                $actualUrl = $this->getUrl();
                if ($this->debug) echo "--------\n$step: we are at Musterdepot Link $actualUrl.\n";
                $result = $this->getResponsiveTablewithCharts();
                if ($result===false) return ("retry");
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                if (isset($this->configuration["Depot"])) return(["Ergebnis" => $result, "Depot" => $this->configuration["Depot"][($this->depotCount-1)]]);
                else return(["Ergebnis" => $result]);
                break;
            case 10:
                if ($this->debug) echo "--------\n$step: go back to Link Musterdepot if necessary.\n";
                $result = $this->pressMusterDepotLinkIf();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    } 
                $this->depotCount--;
                if ($this->depotCount>0) return(["goto" => 8]);
                break;  
            default:
                return (false);
            }
        }

    /* goto market page, wir drücken auf einen Link, Link Url könnte aber auch direkt eingeben werden
     * https://www.easybank.at/markets/wertpapiere/intro
     *
     * /html/body/header/div/div/div[1]/div[2]/div[2]/a[2]          alter Link ohne Werbeseite am Anfang
     * /html/body/header/div[1]/div/div/div[4]/div/a[1]
     */
    function goToLinkMarketIf()
        {
        if ($this->debug) echo "goToLinkMarketIf : ";
        $url='https://www.easybank.at/markets/wertpapiere/intro';
        return($this->updateUrl($url));

        /* es wird ein neues Fenster aufgemacht, dem können wir nicht folgen, daher siehe oben direkt zum Link */
        //$xpath='/html/body/header/div/div/div[1]/div[2]/div[2]/a[2]';
        $xpath='/html/body/header/div[1]/div/div/div[4]/div/a[1]';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)." : $result\n";
            $this->pressElementIf($xpath,true);                                                      // true debug , there is catch but still error
            //$this->pressButtonIf($xpath,true);
            //$this->pressSubmitIf($xpath,true);                                                      // true debug , error 
            }
        return ($result);
        }

    /* press Cookie Button if available
     *  /html/body/div[4]/div[2]/div/div/div[2]/div/div/button[1]
     *  /html/body/div[5]/div[2]/div/div/div[2]/div/div/button[1]
     */
    function checkCookiesButtonIf()
        {
        if ($this->debug) echo "CheckCookiesButtonIf ";
        $xpath1='/html/body/div[4]/div[2]/div/div/div[2]/div/div/button[1]';
        $xpath2='/html/body/div[5]/div[2]/div/div/div[2]/div/div/button[1]';
        $result = $this->getHtmlIf($xpath1,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)."\n";
            //print $result;
            $result = $this->pressButtonIf($xpath1,true);                                                      // true debug  
            } 
        else 
            {
            $result = $this->getHtmlIf($xpath2,$this->debug);
            if ($result !== false)
                {
                echo "found fetch data, length is ".strlen($result)."\n";
                //print $result;
                $result = $this->pressButtonIf($xpath2,true);                                                      // true debug  
                } 
            else echo "Button not pressed.\n";
            }
        return ($result);
        }

    /* click on MyMarket to go to personalized Portal
     * das ist der Button ganz unten
     *
     * /html/body/div[1]/nav/div/div/ul[2]/li[7]/a
     * /html/body/div[1]/nav/div/div/ul[2]/li[7]/a/span[1]
     * /html/body/div[1]/nav/div/div/ul[2]/li[7]/a
     */
    function gotoLinkMyMarketIf()
        {
        $xpath='/html/body/div[1]/nav/div/div/ul[2]/li[7]/a';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)." : $result\n";
            $this->pressElementIf($xpath,true);                                                      // true debug , there is catch but still error
            //$this->pressButtonIf($xpath);
            }
        else echo "Button not pressed.\n";
        return($result);        
        }

    /* enter Login
     * /html/body/div[1]/main/div/div/div[1]/section/div/form/div/div[1]/input
     */
    function enterLoginElement($username)
        {
        $xpath = '/html/body/div[1]/main/div/div/div[1]/section/div/form/div/div[1]/input';        // relative path
        $this->sendKeysToFormIf($xpath,$username);              // wjobstl
        }

    /* enter Password
     * /html/body/div[1]/main/div/div/div[1]/section/div/form/div/div[2]/input
     */
    function enterPasswordElement($password)
        {
        $xpath = '/html/body/div[1]/main/div/div/div[1]/section/div/form/div/div[2]/input';        // relative path
        $this->sendKeysToFormIf($xpath,$password);              // wjobstl
        }

    /* press Button LogIn (JetztEinloggen)
     * /html/body/div[1]/main/div/div/div[1]/section/div/form/button
     */
    function pressLoginButtonIf()
        {
        if ($this->debug) echo "pressLoginButtonIf ";
        $xpath = '/html/body/div[1]/main/div/div/div[1]/section/div/form/button';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)." result $result\n";
            //print $result;
            $this->pressElementIf($xpath,true);                                                      // true debug , there is catch but still error
            //$result = $this->pressButtonIf($xpath,true);                                                      // true debug  
            } 
        else echo "Button not pressed.\n";
        return ($result);
        }

    /* goto Link Portfolios
     * /html/body/div[1]/nav/div/div/ul[2]/li[3]/a
     */
    function pressMusterDepotLinkIf()
        {
        if ($this->debug) echo "pressMusterDepotLinkIf ";
        $xpath = '/html/body/div[1]/nav/div/div/ul[2]/li[3]/a';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            echo "found fetch data, length is ".strlen($result)." result $result\n";
            //print $result;
            $this->pressElementIf($xpath,true);                                                      // true debug , there is catch but still error
            //$result = $this->pressButtonIf($xpath,true);                                                      // true debug  
            } 
        else echo "Link Element not pressed.\n";
        return ($result);
        }

    /* read drop down field to discover configured portfolios
     * /html/body/div[1]/main/div/section/div[1]/div[1]
     */
    private function readDropDownMusterdepot() 
        {
        $result=false;
        if ($this->debug) echo "readDropDownMusterdepot ";
        //$xpath='/html/body/div[1]/main/div/section/div[1]/div[1]';
        $xpath='/html/body/div[1]/main/div/section/div[1]';
        $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
        if ((strlen($ergebnis))>10) 
            {
            echo "found fetch data, length is ".strlen($ergebnis)." result $ergebnis\n";
            $page=$this->getInnerHtml();
            $debugVariable=CreateVariableByName($this->getResultCategory(),"Debug",3);
            SetValue($debugVariable,$page);
            $xPathQuery  = '//div[@*]';
            $filterAttr  = 'class';
            $filterValue ="dropdown-menu wmax*";

            $innerHtml = $this->queryFilterHtml($page,$xPathQuery,$filterAttr,$filterValue,$this->debug);            // das ganze Textfeld nach dem xpath mit den nachstehenden Attributen filtern und ausgeben
            //echo $innerHtml;
    
            $xmlArray = new App_Convert_XmlToArray;
            $array = $xmlArray->XmlToArray($innerHtml);            // input muss string sein, das Ergebnis in ein Array wandeln
            //print_R($array);   

            $result=array();
            if (isset($array["body"]["div"]["a"]))
                {
                print_r($array["body"]["div"]["a"]);
                foreach ($array["body"]["div"]["a"] as $index => $entry)
                    {
                    $result[$index]["name"]=$entry["@content"];
                    $result[$index]["link"]=$entry["@attributes"]["href"];
                    }
                }
            }
        return($result);
        }

    /* Tabelle einzelner Musterdepots auslesen, wenn kein Index angegeben wurde dann  
     * das ist jetzt ein Dropdown Feld mit Referenz auf Links
     *
     *
     */
    private function gotoLinkMusterdepotNum($id=0)
        {
        $url = 'https://www.easybank.at'.$this->tableOverview[$id]["link"];
        $status = $this->updateUrl($url);
        return ($status);                    
        }

    /* die ganze Tabelle einlesen, ist zwar immer noch als Tabelle organisiert aber um einges moderner
     * /html/body/div[1]/main/div/section/table
     *
     */
    private function getResponsiveTablewithCharts()
        {
        if ($this->debug) echo "Get Full Table of Depot\n";
        $xpath='/html/body/div[1]/main/div/section/table';
        $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
        if ((strlen($ergebnis))>10) 
            {
            if ($this->debug) echo "   ---> table found.\n";
            $page=$this->getInnerHtml();
            $debugVariable=CreateVariableByName($this->getResultCategory(),"Debug",3);
            SetValue($debugVariable,$page);

            return($ergebnis);
            }
        else echo $ergebnis;
        return (false);
        }

    /* Easychart2, extract table, function to get fixed elements of a table line per line     
     * input ist array mit Eintraegen pro Spalte (umgewandelt aus dem resultat mit zeilenumbrüchen)
     * hier sind die neuen Formatierungsanweisungen für die responsive version von Easycharts: 
     *      start   die ersten zwei Zeilen überspringen
     *      columns es gibt 11 Spalten
     * column 0 ist der erste Eintrag, bei Debug Ausgabe die alte Zeile abschliessen
     * column 2 ist ein Name mit Blanks, solange zusammenfassen bis ein Währungszeichen koomt
     *
     *
     * nachher evaluateResult
     */

    function parseResult($lines, $debug=false)
        {
        $config=array(
            "start"     => 2,
            "columns"   => 12,
        );

        $config=array(
            "start"     => 0,
            "columns"   => ["head" => 12, "body" => 9],
            "insert"    => array(
                "head"    => array(
                    "1"         => "Index",
                ),
                "body"      => array(
                ),
            ),
            "split"    => array(
                "head"    => array(
                    "10"         => "KaufMarkt",
                ),
                "body"      => array(
                    "3"         => "Whg",
                    "4"         => "Börse",
                    "5"         => "Zeit",
                    "6"         => "DiffKauf",
                    "7"         => "KaufMarkt",
                ),
            ),
        );

        if ($debug) echo "parseResult für die neue responsive Version von Easycharts:\n";

        $start=$config["start"];                               // jetzt gleich, alte Version die ersten beiden Zeilen überspringen
        if (isset($config["columns"]["head"])) $columns=$config["columns"]["head"];                            // alle 11 Zeilen ist gleich Spalten gibt es einen Zeilenumbruch
        elseif (isset($config["columns"])) $columns = $config["columns"];
        else $columns=11; 
        echo "columns to start : $columns\n";
        if (isset($config["columns"]["body"])) $columnsUpd=$config["columns"]["body"];
        $count=0;                               // Zeilenzähler
        $join=0;                                // damit können zeilen wieder verbunden werden
                            
        $data=array();                          // Eintraege pro Zeile und Analyse der max Spaltenenlänge, Returnwert
        $zeile=0; $spalte=0;
        foreach ($lines as $index => $line)
            {
            if ($count >= $start)           // ab welcher Zeile gehts los
                {
                $length=strlen($line);
                $column = ($count-$start)%$columns;         // die Start Zeilen werden nicht mitgezählt
                if ($column==0) 
                    {
                    if ($debug) echo "\n".$this->ipsOps->mb_str_pad($zeile,3);                  // 0 ist der erste Eintrag, letzten Eintrag mit neuer Zeile abschliessen
                    $zeile++;
                    $spalte=0;
                    }
                else 
                    {
                    //if ($debug) echo $this->ipsOps->str_pad($column,2)." ";                  // 0 ist der erste Eintrag, letzten Eintrag mit neuer Zeile abschliessen                    
                    $spalte++;
                    }
            if ( ($zeile==2) &&  $columnsUpd ) 
                {
                //echo "columns to go : $column = $count - $start % $columns \n";
                $start+=$count;
                $columns=$columnsUpd;   // body anders als head, alle x Zeilen ist gleich Spalten gibt es einen Zeilenumbruch
                $columnsUpd=false;    
                //echo str_pad($zeile,3)."columns to go : $column = $count - $start % $columns \n";
                //echo str_pad($zeile,3);
                }

                /*if ($column==2)                             // in der Spalte 2 solange zusammenfassen bis ein Währungszeichen kommt
                    {
                    switch ($lines[$index+1])
                        {
                        case "EUR":
                        case "USD":
                        case "RAT":
                        case "XPP":
                            $join=0;
                            break;
                        default:
                            $join=strlen($line);
                            break;
                        }
                    }
                if ($column==11)
                    {
                    if ($debug) echo "*";    
                    }
                */
                if ($join && ($column==2) )         // Spalte 2 und kein Währungszeichen als nächstes
                    {
                    if ($debug) echo $line;
                    $data["Data"][$zeile][$spalte]=$line;                
                    }
                else            // default
                    {
                    if ($join)                      // weiter zusammenfassen
                        {
                        if ($debug) echo $this->ipsOps->mb_str_pad($line,32-$join-3).$this->ipsOps->mb_str_pad($join,2)."|";
                        $length += $join;
                        $join=0;
                        $count--; $spalte--;
                        $data["Data"][$zeile][$spalte].=$line;
                        }
                    else                // Normale Bearbeitung
                        {
                        // insert    
                        if ($zeile==1)          // erste Zeile, Spaltenüberschriften 
                            {
                            if ($column==1)          // charts2 ergänzen, add index wenn Typ
                                {
                                $addline="Index";
                                if ($debug) echo $this->ipsOps->mb_str_pad($addline,31)."|";
                                $data["Data"][$zeile][$spalte]=$addline;
                                $spalte++;
                                }
                            }
                        //split
                        $numElements=1;         // init für split head und body
                        if (($zeile==1) && (isset($config["split"]["head"])) )  //  body, tabelleneinzträge auf emhrere Spalten aufteilen 
                            {
                            $found=false;
                            foreach ($config["split"]["head"] as $col => $cell) 
                                {
                                if ($col==$column) $found=$cell;
                                }
                            if ($found)
                                {
                                echo "*";
                                $entries=explode(" ",$line);
                                $numElements=sizeof($entries);
                                }    
                            }
                        if (($zeile>1) && (isset($config["split"]["body"])) )  //  body, tabelleneinzträge auf emhrere Spalten aufteilen 
                            {
                            $found=false;
                            foreach ($config["split"]["body"] as $col => $cell) 
                                {
                                if ($col==$column) $found=$cell;
                                }
                            if ($found)
                                {
                                echo "*";
                                $entries=explode(" ",$line);
                                $numElements=sizeof($entries);
                                if (($found=="Zeit") && ($numElements==3))         // combine 2 und 3
                                    {
                                    $numElements=2;
                                    $entries[1].=$entries[2];
                                    unset ($entries[2]);
                                    //echo json_encode($entries)."  ";
                                    }
                                }    
                            }
                        if (false && ( ($spalte==4) || ($spalte==8) || ($spalte==10) || ($spalte==12)) )                        //chart Split eines Eintrages auf mehrer Spalten
                            {
                            $entries=explode(" ",$line);
                            $numElements=sizeof($entries);
                            }
                        if ($numElements==2)
                            {
                            $first = $entries[0];
                            $last  = $entries[1];
                            }
                        if ($numElements>2)
                            {
                            //echo $numElements." ".json_encode($entries);
                            $first=""; 
                            for ($i=0;($i<($numElements-1));$i++) $first.=$entries[$i]." ";
                            $last=$entries[$numElements-1];
                            }
                        if ($numElements>1)     
                            {
                            if ($debug) echo $this->ipsOps->mb_str_pad($first,31)."|";
                            $data["Data"][$zeile][$spalte]=$first;         // erster Teil wird eigene Spalte
                            $length=strlen($first);
                            if (isset($data["Size"][$spalte]))
                                {
                                if ($length>$data["Size"][$spalte]) $data["Size"][$spalte]=$length;
                                }
                            else $data["Size"][$spalte]=$length;
                            $length=strlen($last);
                            $line=$last;
                            $spalte++;
                            if ($debug) echo $this->ipsOps->mb_str_pad($line,31)."|";
                            $data["Data"][$zeile][$spalte]=$line;                                
                            }
                        else            // alles in ordnung, einfach abspeichern
                            {
                            if ($debug) echo $this->ipsOps->mb_str_pad($line,31)."|";
                            $data["Data"][$zeile][$spalte]=$line;
                            }
                        }
                    }

                if (isset($data["Size"][$spalte]))
                    {
                    if ($length>$data["Size"][$spalte]) $data["Size"][$spalte]=$length;
                    }
                else $data["Size"][$spalte]=$length;

                }
            $count++;           // zeilensprung
            }
        return($data);
        }
    }

/* Basisfunktion von Easychart, der Webspezifische Teil sollte in Easycharts sein
 *
 */

class SeleniumEasychartModul extends SeleniumHandler
    {
    protected $configuration;               //array mit Datensaetzen
    protected $CategoryIdDataEasy;          // Kategorie Easy
    private $duetime,$retries;              //für retry timer
    protected $debug;

    private $tableOverview;                 // das Table array wird einmal eingelesen
    private $depotCount;                    // zählt runter, bei 0 ist man fertig

    /* Initialisierung der Register die für die Ablaufsteuerung zuständig sind
     */
    function __construct($debug=false)
        {
        $this->duetime=microtime(true);
        $this->retries=0;
        $this->debug=$debug;
        parent::__construct();
        $this->CategoryIdDataEasy = @IPS_GetObjectIdByName("EASY",$this->CategoryIdData);           // wenn kein Easy da gibt es halt false in der Category
        }

    /* Kapselung der internen Daten und Konfigurationen */

    function getResultCategory()
        {
        return($this->CategoryIdDataEasy);
        }

    /* Easychart Configuration und Orderbook 
     * function get_EasychartConfiguration()
		    {
            $orderbook=array(
                "AT000000STR1" => array(                                // Strabag, closed
                    "Short"    => "STR.VI",
                    "Depot"     =>  "actualDepot.Easy",                                     // Depot and SubDepot                    
                    "Orders"   => array(
                        "20210302" => array(
                            "pcs" => 300,"price" => 30.7,"currency" => "USD", "exrate" => 1.09),                                  // 45,2
                        "20220812" => array(
                            "pcs" => -300,"price" => 40),                                   // Verkauf vor squeezeout im Feb 2023
                                            ),                                              // https://de.finance.yahoo.com/quote/STR.VI?p=STR.VI&.tsrc=fin-srch
                                        ),
     * zwei unterschiedliche Formatierungen, angleichen auf Standard flexibles Format
     * Orderbook ist mit Index Orders und Short
     */
    function getEasychartConfiguration()
        {
        $orderbook=array();    
        if ( (function_exists("get_EasychartConfiguration")) && (@sizeof(get_EasychartConfiguration())) )
            {
            foreach (get_EasychartConfiguration() as $index => $entry)
                {
                // parse configuration, entry ist der Input und orderbook der angepasste Output
                configfileParser($entry, $orderbook[$index], ["ORDERS","Orders","Order","orders" ],"Orders" , null); 
                if ((isset($orderbook[$index]["Orders"]))===false) $orderbook[$index]["Orders"]=$entry;
                configfileParser($entry, $orderbook[$index], ["Short","SHORT","short","Shortname","ShortName"],"Short" , null); 
                configfileParser($entry, $orderbook[$index], ["Depot","DEPOT","depot"],"Depot" , null); 
                }
            }
        return ($orderbook);
        }

    /* Easychart Configuration und Orderbook 
     * zwei unterschiedliche Formatierungen, angleichen auf Standard flexibles Format
     * Orderbook ist ohne Index Orders und Short
     */
    function getEasychartOrderConfiguration()
        {
        $orderbook=array();    
        if (function_exists("get_EasychartConfiguration"))
            {
            $orderConfig=get_EasychartConfiguration();
            if ( (is_array($orderConfig)) && (count($orderConfig)>0) )          // geht auch effizienter, siehe oben
                {
                foreach (get_EasychartConfiguration() as $index => $entry)
                    {
                    // parse configuration, entry ist der Input und order der angepasste Output
                    configfileParser($entry, $order, ["ORDERS","Orders","Order","orders" ],"Orders" , null); 
                    if ((isset($order["Orders"]))===false) $order["Orders"]=$entry;
                    $orderbook[$index]=$order["Orders"];
                    }
                }
            }                
        return ($orderbook);
        }
    
    /* get_EasychartSharesConfiguration() fokussiert sich auf die anderen Aktien die nicht in meinem Depot waren und die die in meinem Besitz sind und liefert zusätzliche Informationen : Split
     * d.h. weitere Konfiguration, hier stehen alle Splits drinnen, aber kann auch mehr Information sein
     */

    function getEasychartSharesConfiguration($debug=false)
        {
        $config=get_EasychartSharesConfiguration();
        if ($debug) print_R($config);
        return ($config);
        }
        
    /* Get the Split Info out of the Shares Info. 
     * Split is stored in the Depot Configuration. Not in Library up to now.
     */

    function getEasychartSplitConfiguration($indexKey="ID",$debug=false)
        {
        $configSplit=array();
        $config=get_EasychartSharesConfiguration();
        // Formattierung der Tabelle rausfinden, mit Split/Short etc oder eben alt ohne Index Split
        $splitIndexUsed=false;
        foreach ($config as $index=>$entry) if (isset($entry["Split"])) $splitIndexUsed=true;           // einer genügt        
        switch (strtoupper($indexKey))
            {
            case "ID":
                foreach ($config as $index=>$entry)
                    {
                    if ($splitIndexUsed)
                        {
                        if (isset($entry["Split"])) $configSplit[$index]=$entry["Split"];
                        }
                    else $configSplit[$index]=$entry;
                    }
                break;
            case "SHORT":
                foreach ($config as $index=>$entry)
                    {
                    if (isset($entry["Short"]))
                        {
                        $short=$entry["Short"];
                        if (isset($entry["Split"])) $configSplit[$short]=$entry["Split"];
                        }
                    }
                break;                
            default:
                echo "do not know Index $indexKey.\n";
                return(false);
                break;
            }
        if ($debug) print_R($configSplit);
        return ($configSplit);
        }
        

    /* Easychart, extract table, function to get fixed elements of a table line per line     
     * input ist array mit Eintraegen pro Spalte (umgewandelt aus dem resultat mit zeienumbrüchen)
     * hier sind die Formatierungsanweisungen: 
     *      start   die ersten zwei Zeilen überspringen
     *      columns es gibt 11 Spalten
     * column 0 ist der erste Eintrag, bei Debug Ausgabe die alte Zeile abschliessen
     * column 2 ist ein Name mit Blanks, solange zusammenfassen bis ein Währungszeichen koomt
     *
     *
     * nachher evaluateResult
     */

    function parseResult($lines, $debug=false)
        {
        $start=2;                               // die ersten beiden Zeilen überspringen
        $columns=11;                            // alle 11 Zeilen ist gleich Spalten gibt es einen Zeilenumbruch

        $count=0;                               // Zeilenzähler
        $join=0;                                // damit können zeilen wieder verbunden werden
                            
        $data=array();                          // Eintraege pro Zeile und Analyse der max Spaltenenlänge, Returnwert
        $zeile=0; $spalte=0;
        foreach ($lines as $index => $line)
            {
            if ($count >= $start)
                {
                $length=strlen($line);
                $column = ($count-$start)%$columns;         // die Start Zeilen werden nicht mitgezählt
                if ($column==0) 
                    {
                    if ($debug) echo "\n";                  // 0 ist der erste Eintrag, letzten Eintrag mit neuer Zeile abschliessen
                    $zeile++;
                    $spalte=0;
                    }
                else $spalte++;
                if ($column==2)                             // in der Spalte 2 solange zusammenfassen bis ein Währungszeichen kommt
                    {
                    switch ($lines[$index+1])
                        {
                        case "EUR":
                        case "USD":
                        case "RAT":
                        case "XPP":
                            $join=0;
                            break;
                        default:
                            $join=strlen($line);
                            break;
                        }
                    }
                if ($column==11)
                    {
                    if ($debug) echo "*";    
                    }
                if ($join && ($column==2) )         // Spalte 2 und kein Währungszeichen als nächstes
                    {
                    if ($debug) echo $line;
                    $data["Data"][$zeile][$spalte]=$line;                
                    }
                else 
                    {
                    if ($join)                      // weiter zusammenfassen
                        {
                        if ($debug) echo str_pad($line,32-$join-3).str_pad($join,2)."|";
                        $length += $join;
                        $join=0;
                        $count--; $spalte--;
                        $data["Data"][$zeile][$spalte].=$line;
                        }
                    else                // Normale Bearbeitung
                        {
                        if ( ($spalte==4) || ($spalte==8) || ($spalte==10) || ($spalte==12))                        //Split
                            {
                            $entries=explode(" ",$line);
                            $numElements=sizeof($entries);
                            if ($numElements==2)
                                {
                                $first = $entries[0];
                                $last  = $entries[1];
                                }
                            if ($numElements>2)
                                {
                                //echo $numElements." ".json_encode($entries);
                                $first=""; 
                                for ($i=0;($i<($numElements-1));$i++) $first.=$entries[$i]." ";
                                $last=$entries[$numElements-1];
                                }
                            if ($numElements>1)     
                                {
                                if ($debug) echo str_pad($first,31)."|";
                                $data["Data"][$zeile][$spalte]=$first;         // erster Teil wird eigene Spalte
                                $length=strlen($first);
                                if (isset($data["Size"][$spalte]))
                                    {
                                    if ($length>$data["Size"][$spalte]) $data["Size"][$spalte]=$length;
                                    }
                                else $data["Size"][$spalte]=$length;
                                $length=strlen($last);
                                $line=$last;
                                $spalte++;
                                }
                            if ($debug) echo str_pad($line,31)."|";
                            $data["Data"][$zeile][$spalte]=$line;                                
                            }
                        else
                            {
                            if ($debug) echo str_pad($line,31)."|";
                            $data["Data"][$zeile][$spalte]=$line;
                            }
                        }
                    }

                if (isset($data["Size"][$spalte]))
                    {
                    if ($length>$data["Size"][$spalte]) $data["Size"][$spalte]=$length;
                    }
                else $data["Size"][$spalte]=$length;

                }
            $count++; 
            }
        return($data);
        }


    /* Easychart, evaluate line/column table to array, supports function to calculate the value of the depot
     * Kursänderung für den Tag wird ermittelt, daas Ergebnis wird absteigend sortiert
     *
     * input kommt von parseResult, Routine baut die schöne Tabelle mit den | als Trennzeichen
     * data hat zwei Tabellen: data und size für die Grösse der Spalten
     * data ist in Zeilen und Spalten organisiert
     * size hat nur die Spalten mit der Tabellenbreite
     *  0    ID
     *  1    Aktie,Index,Fonds
     *  2    Name
     *  3    EUR,USD
     *
     * neues responsive Format
                         [0] => Name
                    [1] => Index
                    [2] => Typ
                    [3] => Stück
                    [4] => Whg
                    [5] => Börse
                    [6] => Aktuell
                    [7] => Zeit
                    [8] => Diff. Vortag
                    [9] => Diff. Kauf
                    [10] => Einstandskurs
                    [11] => Kaufsumme
                    [12] => Marktwert
                    [13] => Anteil
     *
     * nachher evaluateValue
     */

    function evaluateResult($data, $debug=false)
        {
        if ($debug) echo "evaluateResult aufgerufen, Ausgabe Tabelle:\n";
        //print_R($data["Data"]);
        /* rebuild table */
        $shares=array();
        $count=0;
        foreach ($data["Data"] as $lineNumber => $line)
            {
            $entry=array();
            $correct=false;
            foreach ($line as $columnNumber => $column)
                {
                if ($debug) echo str_pad($column,$data["Size"][$columnNumber])."|";
                /*if ($columnNumber==0) $entry["ID"]=$column;
                if ($columnNumber==2) $entry["Name"]=$column;
                if ($columnNumber==3) $entry["Currency"]=$column;
                if ($columnNumber==5) $entry["Stueck"]=floatval(str_replace(',', '.', str_replace('.', '', $column)));
                if ($columnNumber==6) $entry["Kurs"]=floatval(str_replace(',', '.', str_replace('.', '', $column)));
                if ($columnNumber==8) $entry["Kursaenderung"]=floatval(str_replace(',', '.', str_replace('.', '', $column))); */
                if ($columnNumber==1) $entry["ID"]=$column;
                if ($columnNumber==0) $entry["Name"]=$column;
                if ($columnNumber==4) $entry["Currency"]=$column;
                if ($columnNumber==3) $entry["Stueck"]=floatval(str_replace(',', '.', str_replace('.', '', $column)));
                if ($columnNumber==6) $entry["Kurs"]=floatval(str_replace(',', '.', str_replace('.', '', $column)));
                if ($columnNumber==8) $entry["Kursaenderung"]=floatval(str_replace(',', '.', str_replace('.', '', $column)));
                }
            // check and store    
            if ( (isset($entry["Kurs"])) && ($entry["Kurs"] != 0) ) $correct=true;
            if ($correct)
                {
                $shares[$count]=$entry;    
                $count++;
                }
            if ($debug) echo "\n";
            }
        if ($debug) echo "\n";
        $sortTable="Kursaenderung";
        $this->ipsOps->intelliSort($shares,$sortTable,SORT_DESC);    
        //print_r($shares);
        if ($debug) echo "\n";
        return($shares);
        }

    /* Summe der Aktienwerte ausrechnen und in der richtigen Reihenfolge darstellen 
     * zum Vergleich Depotwert mit Easycharts
     *
     * einen Umrechnungskurs suchen: EU0009652759
     */

    public function evaluateValue($shares, $debug=false)
        {
        if ($debug) echo "evaluateValue aufgerufen, Ausgabe Tabelle:\n";            
        $value=0;
        $eurusd=1;
        foreach ($shares as $index => $entry)
            {
            $index=$entry["ID"];
            if ($index=="EU0009652759") 
                {
                $eurusd=$entry["Kurs"];
                }
            }
        if ($debug) echo "Umrechnung USD auf EUR : ".$eurusd."\n";            
        foreach ($shares as $index => $entry)
            {
            //echo json_encode($entry)."\n";
            if (isset($entry["Currency"])) $currency=$entry["Currency"];
            else $currency="EUR";
            switch ($currency)
                {
                case "EUR":
                case "USD":
                    if ($debug) echo str_pad($entry["ID"],14).str_pad($entry["Name"],41).str_pad($entry["Stueck"],8," ", STR_PAD_LEFT);
                    if ($currency=="USD") $kurs=$entry["Kurs"]/$eurusd;
                    else $kurs=$entry["Kurs"];
                    if ($debug) echo str_pad(number_format($entry["Kurs"],2,",","."),12," ", STR_PAD_LEFT);
                    if (isset($entry["Currency"])) if ($debug) echo str_pad($entry["Currency"],6," ", STR_PAD_LEFT);
                    if ($debug) echo str_pad(number_format($entry["Kursaenderung"],3,",",".")."%",9," ", STR_PAD_LEFT)." ";
                    $shareValue=$entry["Stueck"]*$kurs;
                    if ($debug) echo str_pad(number_format($shareValue,2,",",".")." Euro",18," ", STR_PAD_LEFT)."\n";
                    $value += $shareValue;
                    break;
                default:
                    break;
                }
            }
        if ($debug) echo "-----------------------------------------------------------------------------------------------------------\n";
        if ($debug) echo "                                                                                             ".str_pad(number_format($value,2,",",".")." Euro",18," ", STR_PAD_LEFT)."   \n";
        return ($value);            
        }

    /* EASY, Werte im Data Block speichern 
     * die ermittelten Werte im Archiv speichern, festellen wenn etwas nicht stimmt
     * es kann ein vorher ermittelter Depotwert geschrieben werden
     *
     */

    function writeResult(&$shares, $nameDepot="MusterDepot",$value=0, $debug=false)
        {
        $componentHandling = new ComponentHandling();           // um Logging zu setzen

        $categoryIdResult = $this->getResultCategory();
        if ($debug) echo "seleniumEasycharts::writeResult, store the new values, Category Easy RESULT $categoryIdResult.\n";
    
        $share=array();
        if ($value != 0)                      // Default Wert
            {
            $share["ID"]     = $nameDepot;
            $share["Parent"] = $categoryIdResult;
            $share["Stueck"] = 1;
            $share["Kurs"]   = $value;
            $shares[]=$share;
            }

        /*function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false) */
        $oid = CreateVariableByName($categoryIdResult,$nameDepot,2,'Euro',"",1000);         // kein Identifier, darf in einer Ebene nicht gleich sein
        $componentHandling->setLogging($oid);

        /* only once per day, If there is change */
        if (GetValue($oid) != $value) SetValue($oid,$value);
        
        //print_R($shares);
        $parent=$oid;
        foreach($shares as $index => $share)
            {
            $value=$share["Kurs"];
            $oid = CreateVariableByName($parent,$share["ID"],2,'Euro',"",1000);     //gleiche Identifier in einer Ebene gehen nicht
            if (isset($shares[$index]["Parent"])===false) $shares[$index]["Parent"]=$parent;
            $shares[$index]["OID"]=$oid;
            $componentHandling->setLogging($oid);                                   // Logging einschalten, das wäre eine Install Funktion 
            //print_r($share);
            /* only once per day, If there is change */
            if (GetValue($oid) != $value) SetValue($oid,$value);
            elseif ($debug)
                {
                echo "gleicher Wert $value at ".IPS_GetName($oid)." ($oid)  last changed: ".date("d.m.Y H:i:s",IPS_GetVariable($oid)["VariableChanged"])."   ";
                //print_r($share);
                if (isset($share["Name"])) echo $share["Name"];
                echo "\n"; 
                }
            }
        }

    /* Easycharts muss mit Selenium zwei Depots auslesen
     *
     */ 
    function getDebotBooksfromConfig($configTabs)
        {
        //$configTabs = $guthabenHandler->getSeleniumTabsConfig("EASY");
        //print_R($configTabs);
        $depotRegister=["RESULT"];
        if (isset($configTabs["Depot"]))
            {
            if (is_array($configTabs["Depot"]))
                {
                $depotRegister=$configTabs["Depot"];    
                }
            else $depotRegister=[$configTabs["Depot"]];
            }
        return ($depotRegister);
        }

    /* Easycharts speichert die Konfiguration des Musterdepots
     * etwas kompliziert. Es gibt ein Musterdepot in dem alle Werte gespeichert sind
     * zusätzlich wird die Konfiguration für ein Musterdepot abgespeichert. Also welche ID, Name und wieviel Stück.
     * es können so auch neue Zusammensetzungen mit anderen Aktien kreiert werden
     *
     * Inputformat:  ohne Key als Index
     * Writeformat:  Key ist der Börsenname "USxxx"
     *
     * Folgende Felder werden übernommen:
     *  OID, Name (optional), Stueck, Kosten (optional), Currency (optional)
     *
     */

    function writeResultConfiguration(&$shares, $nameDepot="MusterDepot",$debug=false)
        {
        $categoryIdResult = $this->getResultCategory();
        $oid = CreateVariableByName($categoryIdResult,"Config".$nameDepot,3);
        $config=array();
        foreach ($shares as $share)
            {
            $config[$share["ID"]]["OID"]=$share["OID"];
            $config[$share["ID"]]["Stueck"]=$share["Stueck"];
            if (isset($share["Kosten"])) $config[$share["ID"]]["Kosten"]=$share["Kosten"];
            if (isset($share["Currency"])) $config[$share["ID"]]["Currency"]=$share["Currency"];
            if (isset($share["Name"])) $config[$share["ID"]]["Name"]=$share["Name"];
            if (isset($share["Split"])) $config[$share["ID"]]["Split"]=$share["Split"];
            }
        SetValue($oid,json_encode($config));
        if ($debug) echo "Konfiguration Depot $nameDepot: ".GetValue($oid)."\n";
        }

    /* Split Konfiguration dazunehmen, da die Konfiguration immer aus Easycharts wieder überschrieben wird
     * ist auch in Gutahabensteuerung_configuration gespeichert
     */

    function updateResultConfigurationSplit(&$shares,$split=false,$debug=false)
        {
        if ($split===false)
            {
            $split = $this->getEasychartSplitConfiguration("ID");          //             "US0231351067" => array( "20220606" => array( "Split" => 20,),  sort according to ID
            if ($debug)
                {
                echo "updateResultConfigurationSplit, split Entries taken from Config.\n";
                print_R($split);
                }
            }
        foreach ($shares as $id => $share)
            {
            $shareID=$share["ID"];                          // $id und shareId sind das selbe
            if (isset($split[$shareID]))                    // split ist nach der Aktien ID strukturiert, Treffer
                {
                $overwrite=true;                    
                if (isset($shares[$id]["Split"])) 
                    {
                    $newSplit=json_encode($split[$shareID]);
                    $oldSplit=json_encode($shares[$id]["Split"]);
                    if ($oldSplit != $newSplit) 
                        {                        
                        if ($debug) echo "Update Split, already set $oldSplit overwrite with $newSplit  \n";
                        unset($shares[$id]["Split"]);
                        }
                    else $overwrite=false;                        
                    if ($overwrite) unset($shares[$id]["Split"]);
                    }
                else
                    {
                    if ($debug) echo "New Split of $id set.\n";
                    }                    
                if ($overwrite) $shares[$id]["Split"]=$split[$shareID];
                }
            }        
        }

    /* Easycharts speichert die Konfiguration des Musterdepots
     * als ein json in einer Variablen die mit Config beginnt
     * die Konfiguration für ein Musterdepot wieder abrufen und etwas anpassen, index ist der index
     *
     * Bei Änderungen die Konfiguration mit writeResultConfiguration wieder speichern
     */

    function getResultConfiguration($nameDepot="MusterDepot")
        {
        $oid=false;
        if (is_numeric($nameDepot))
            {
            $oid=intval($nameDepot);                                                        // oder die OID ermitteln
            if ($oid>65535) $oid=false;                                                     // Plausi Check
            }
        if ($oid===false)
            {
            $categoryIdResult = $this->getResultCategory();
            $oid = @IPS_GetObjectIdByName("Config".$nameDepot,$categoryIdResult);            // entweder den Namen suchen
            if ($oid===false)
                {
                echo "Config".$nameDepot." in $categoryIdResult nicht gefunden.\n";    
                return (false);
                }
            }
        //echo "getResultConfiguration from $oid \n";
        $shares = json_decode(GetValue($oid),true);                   // anspeichern als array
        $config=array();
        foreach ($shares as $index => $share)               // anders anordnen
            {
            $config[$index]=$share;
            $config[$index]["ID"]=$index;
            }
        //print_R($config);
        return($config);
        }

    /* aus den beiden Configfiles in Guthabensteuerung_Configuration und den Depoteinträgen aus Easycharts eine gemeinsame Configuration erstellen
     */

    function createJointConfiguration($debug=false)
        {
        $config = $this->getEasychartSharesConfiguration();
        $orders = $this->getEasychartConfiguration();
        //print_r($orders);
        $depotRegister = $this->getDepotConfigurations();    
        foreach ($depotRegister as $index => $depot)
            {
            //print_R($entry); 
            $depotName = $depot["Depot"]["Name"];
            $shares    = $this->getResultConfiguration($depotName);           
            foreach($shares as $index => $share)                    //haben immer noch eine gute Reihenfolge, wird auch in resultShares übernommen
                {
                if (isset($configShares[$index]))
                    {
                    if ($debug) echo "Index $index doppelt.\n";
                    if (isset($configShares[$index]["Depot"][$depotName])) if ($debug)  echo "Depot $depotName doppelt.\n";     //gleicher Eintrag im Depot unwahrscheinlich
                    else $configShares[$index]["Depot"][$depotName]=array();
                    }
                else $configShares[$index]["Depot"][$depotName]=array();
                if (isset($config[$index]))
                    {
                    if (isset($config[$index]["Short"])) $configShares[$index]["Short"] = $config[$index]["Short"];  
                    if (isset($config[$index]["Split"])) $configShares[$index]["Split"] = $config[$index]["Split"]; 
                    }
                if (isset($orders[$index]))
                    {
                    if (isset($orders[$index]["Short"])) 
                        {
                        if (isset($configShares[$index]["Short"]))
                            {
                            if ($configShares[$index]["Short"] != $orders[$index]["Short"]) if ($debug) echo "Short Name different for Index $index.\n";
                            }
                        else $configShares[$index]["Short"] = $orders[$index]["Short"];  
                        }
                    if (isset($orders[$index]["Orders"])) $configShares[$index]["Orders"] = $orders[$index]["Orders"]; 
                    }
                if ( (isset($share["Name"])) && ($share["Name"] != "") ) 
                    {
                    $configShares[$index]["Name"]=$share["Name"];
                    }
                if (isset($share["OID"])) $configShares[$index]["OID"]=$share["OID"];
                if (isset($share["Currency"])) $configShares[$index]["Currency"]=$share["Currency"];
                }
            }
        return($configShares); 
        } 

    /* eine Depot Konfiguration finden mit Lookup als Eintrag
     * zuerst werden die Depotnamen Depotkonfigurationen gesucht die einen Eintrag von Lookup haben
     * alternativ kann man eine Depotnamen bereits angeben
     *
     */

    function showDepotConfiguration($lookup,$nameDepot=false,$debug=false)
        {
        if ($debug) echo "showDepotConfiguration($lookup,$nameDepot,...) aufgerufen.\n";
        $result=false;
        if ($nameDepot===false)
            {
            if ($debug) echo "Ein Depot finden in dem OID $lookup gelistet ist:\n";
            $resultDepotArray=$this->showDepotConfigurations($lookup,$debug);            // es werden auch mehrer Depots ausgegeben, es gibt einen index
            //print_R($resultDepotArray);
            if (is_array($resultDepotArray))
                {
                foreach ($resultDepotArray as $resultDepot)
                    {
                    if (isset($resultDepot["Depot"])) $nameDepot = $resultDepot["Depot"]["Name"];
                    }
                }
            }
        if ($nameDepot!==false)
            {
            $config = $this->getResultConfiguration($nameDepot);
            foreach ($config as $id => $configEntry)
                {
                $found=false;
                if ($configEntry["OID"]==$lookup) $found=true;;
                if (isset($configEntry["Name"])) 
                    {
                    if ($lookup===false) $found=true;
                    elseif (is_numeric($lookup))
                        {
                        if ($lookup == $configEntry["OID"]) $found=true;    
                        }
                    else
                        {
                        $pos2=strpos($configEntry["Name"],$lookup);
                        if ($pos2 !==false) $found=$id;
                        $pos2=strpos(strtoupper($configEntry["Name"]),strtoupper($lookup));
                        if ($pos2 !==false) $found=$id;
                        }                    
                    }
                if ($found) return ($configEntry);
                }
            }
        return($result);
        }

    /* für eine DepotKonfiguration die mit der OID erkenntlich gemacht wurde
     *
     */

    function getSplitfromOid($oid,$debug=false)
        {
        if ($debug) echo "getSplitfromOid($oid,..) aufgerufen.\n";
        $configuration = $this->showDepotConfiguration($oid,false,$debug);                           // eine Depot Konfiguration finden
        if ($debug) print_r($configuration);
        if (isset($configuration["Split"])) return $configuration["Split"];
        else 
            {
            if ($debug) print_r($configuration);
            return false;
            }
        }

    /* Easycharts speichert die Konfiguration des Musterdepots im Namen beginnend mit Config im EASY Result
     * In den Depotkonfigurationen ein Musterdepot mit einer speziellen Aktie suchen
     * lookup kann false, ein String oder eine OID Zahl sein
     * bei false werden alle, sonnst nur die bei denen die OID oder der Name gleich ist
     * Ausgabe als ein Array mit einem Array Eintrag [Depot] mit Name und OID
     */

    function showDepotConfigurations($lookup=false,$debug=false)
        {
        if ($debug) echo "showDepotConfigurations($lookup,...) aufgerufen.\n";
        $result=false;
        $categoryIdResult = $this->getResultCategory();
        $childrens=IPS_GetChildrenIDs($categoryIdResult);
        $index=0;           //  mehrer Depots als Ergebnis, index vergeben          
        //print_r($childrens);
        foreach ($childrens as $children) 
            {
            $name=IPS_GetName($children);
            $pos1 = strpos($name,"Config");
            if ($pos1===0)                                              //Children fangt mit Config an
                {
                $name=substr($name,$pos1+strlen("Config"));             //Name ohne Config am Anfang ermitteln
                $shares = json_decode(GetValue($children),true);                   // Config auslesen und anspeichern als array
                $shareInfo=""; $found=false;
                foreach ($shares as $id=>$share)
                    {
                    if (isset($share["Name"])) 
                        {
                        $shareInfo .= $share["Name"]." "; 
                        if ($lookup===false) $found=true;
                        elseif (is_numeric($lookup))
                            {
                            if ($lookup == $share["OID"]) $found=true;    
                            }
                        else
                            {
 
                            $pos2=strpos($share["Name"],$lookup);
                            if ($pos2 !==false) $found=$id;
                            $pos2=strpos(strtoupper($share["Name"]),strtoupper($lookup));
                            if ($pos2 !==false) $found=$id;
                            }
                        }
                    //print_R($share);
                    //$pos2=
                    }
                if ($debug) echo str_pad($name,32).count($shares)." \n";
                if ($found)
                    {                    
                    if ($debug) echo $found."     ".$shareInfo."\n";
                    $result[$index]["Depot"]["Name"]=$name;
                    $result[$index]["Depot"]["OID"]=$children;
                    $index++;
                    }
                //print_R($shares);
                }
            }
        return($result);
        }

    /* getDepotConfigurations, wie showDepotConfigurations mit lookup=false
     *
     */

    function getDepotConfigurations($debug=false)
        {
        if ($debug) echo "getDepotConfigurations() aufgerufen.\n";
        $result=false;
        $categoryIdResult = $this->getResultCategory();
        $childrens=IPS_GetChildrenIDs($categoryIdResult);
        $index=0;           //  mehrer Depots als Ergebnis, index vergeben          
        //if ($debug) print_r($childrens);
        foreach ($childrens as $children) 
            {
            $name=IPS_GetName($children);
            $pos1 = strpos($name,"Config");
            if ($pos1===0)                                              //Children fangt mit Config an
                {
                $name=substr($name,$pos1+strlen("Config"));             //Name ohne Config am Anfang ermitteln
                $shares = json_decode(GetValue($children),true);                   // Config auslesen und anspeichern als array
                $result[$index]["Depot"]["Name"]=$name;
                $result[$index]["Depot"]["OID"]=$children;
                $index++;                
                if ($debug) 
                    {
                    echo str_pad($name,32).count($shares)." \n";
                    foreach ($shares as $id=>$share)
                        {
                        echo "  $id  ";
                        if (isset($share["Name"])) echo $share["Name"];
                        echo "   ".json_encode($share)." \n";
                        }
                    }
                }
            }
        return($result);
        }



    /* Easycharts writeResultAnalysed analysiert und ermittelt verschiedene darstellbare Ergebnisse, Ausgabe als Tabelle
     * zwei Darstellungsformate, echo Text Tabelle oder html formatierte Tabelle
     * Bei html kann man sich mehrere erweiterte Tabellen für die Darstellung unter den Reports aussuchen,
     * Mit Size werden verschiedene Darstellungsgroessen und Detailierungen für die html Tabelle ausgewählt
     *  -x  Limitierung der Zeilen 
     *   0  einfache Tabelle, geeignet für halb- oder viertelseitige Darstellungen
     *   1  zusätzliche informationen
     *   2  erweiterte Darstellung mit mehr Fokus auf Win/Loss des eigenen Depots, Monats/wochen/Tagestrend
     *   3  
     *
     * Wie der Name schon sagt gibt es verschiedene Analyse Funktionen für die Visualisiserung
     *          Anzeige des Tageskurses mit Rotem oder grünen Hintergrund
     *              Positiver  Spread (Abstand Max zu Mittelwert) größer 10% und Tageskurs > 97% vom Maxwert
     *              Negativer Spread (Abstand Min zu Mittelwert) größer 10% und Tageskurs < 97% vom Minwert
     *          Bearbeitung der Description, letze Spalte in der Tabelle
     *
     * Benötigter Input, Indexes in resultShares
     *      ["Info"]["Name"]                        Name der Aktie
     *      ["Description"]
     *          ["Change"]                          Sortieren der Tabelle nach der Tagesveränderung
     *          ["StdDevRel"]                       relative Standradabweichtung kleiner 5 für besseres rating
     *          ["Interval"]["Week"]["Trend"]       trendPerc, Simple Einschätzung Buy, Hold, Sell
     *          ["Max"],["Min"],["Means"]
     *          ["StdDevPos"],["StdDevNeg"]
     *
     *      ["Order"]
     *
     *      ["Analyst"]["Target"]
     */

    function writeResultAnalysed($resultShares,$html=false, $size=0,$debug=false)
        {
        $ipsOps = new ipsOps();
        $table=false; $wert = "";
        $sort="Change";
        $lines=false; $countLines=0;
        if (is_array($size)) echo "Angabe der Konfiguration mit einem Array. Umwandeln.\n";
        else
            {
            if ($size<0) 
                {
                $lines=abs($size);
                $linesBegin=round($lines/2);        // keine Kommas, half up
                $linesEnd=$linesBegin;
                $size=0;
                }
            }
        if ($debug) echo "writeResultAnalysed, Ausgabe Tabelle für ".count($resultShares)." Werte mit Größe der Darstellung $size und Lines $lines aufgerufen.\n";

        if ($html) 
            {
            $wert.="<style>";
            $wert.='#easycharts { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; ';
            if ($size==0) $wert.='font-size: 100%; ';
            else $wert.='font-size: 150%; ';
            $wert.='color:black; border-collapse: collapse; width: 100%; }';
            $wert.='#easycharts td, #customers th { border: 1px solid #ddd; padding: 8px; }';
            $wert.='#easycharts tr:nth-child(even){background-color: #f2f2f2;}';
            $wert.='#easycharts tr:nth-child(odd){background-color: #e2e2e2;}';
            $wert.='#easycharts tr:hover {background-color: #ddd;}';
            $wert.='#easycharts th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; }';
            $wert.="</style>";                
            //echo $size;
            $wert .= '<font size="1" face="Courier New" ><table id="easycharts"><tr><td>ID/Name</td><td>';
            if ($size>2) $wert .= '';           // mehr externe Analytics als eigene Auswertungen anzeigen 
            else 
                {
                $wert .= 'Standard<br>Abw</td><td>Spread Max/Min</td><td>';
                if ($size>0) $wert .= '++/--</td><td>';
                }
            if ($size>0) $wert .= 'Min</td><td>Max</td><td>Order</td><td>Win/Loss</td><td>';
            else $wert .= 'Mittelwert</td><td>';
            if ($size>2) $wert .= 'Target</td><td>';           // Show Analytic Information from YahooFin or Others
            if ($size>1) $wert .= 'Letzter Wert</td><td>Month Trend</td>';
            else $wert .= 'Letzter Wert</td>';
            $wert .= '<td>Week Trend</td><td>Day Change</td><td>Recommendation</td><td>Qual</td></tr>';
            }
        else echo "ID            Name                       StandardAbw  Spread Max/Min        Mittelwert      Trend      Letzter Wert  Result\n";

        $sortTable=array();
        foreach ($resultShares as $index => $share)
            {
            if (isset($share["Description"]["Change"])) $sortTable[$index] = $share["Description"]["Change"];           // wenn kein Change existiert erfolgt auch kein Eintrag in der Tabelle
            }
        arsort($sortTable);
        //print_R($sortTable);
        //echo "Anzahl Zeilen : ".count($sortTable)." \n";
        
        /* entsprechend der Sortierung die Tabelle anzeigen 
         * Rating Engine:
         *          Evaluation      Result rating  strength
         *          init                    0       1,6
         *          stdDevRel<5             +1      1,2                 geringe relative Standardabweichung bedeutet Stabilität des Kurses           
         *          Volatile to High        +1
         * strength für Bewertung stddevpos stddevneg relativ zueinander strength*pos>neg ist Volatile to High, strength*neg>pos is Volatile to Low
         * 
         *
         * ["Description"]["Interval"]["Week"]["Trend"] = trendPerc
         *
         */
        foreach ($sortTable as $index => $line)
            {
            if (isset($resultShares[$index]["Info"]["Name"])) $name=$resultShares[$index]["Info"]["Name"];
            else $name="";
            //rating Engine
            $rating=0;
            if ( (isset($resultShares[$index]["Description"]["StdDevRel"])) && ($resultShares[$index]["Description"]["StdDevRel"]<5) )
                {
                $rating += 1;
                $strength=1.2;          // Bewertung StDevPos zu StdDevNeg
                }
            else $strength=1.6; 
            if (isset($resultShares[$index]["Description"]["Interval"]["Week"]["Trend"])) $trendPerc = $resultShares[$index]["Description"]["Interval"]["Week"]["Trend"];
            else $trendPerc=0;
            $spreadPlus  = ($resultShares[$index]["Description"]["Max"]/$resultShares[$index]["Description"]["Means"]-1)*100;
            $spreadMinus = (1-$resultShares[$index]["Description"]["Min"]/$resultShares[$index]["Description"]["Means"])*100;

            $description="";
            if (isset($resultShares[$index]["Info"]["Currency"])) $currency=$this->getCurrencySymbol($resultShares[$index]["Info"]["Currency"]); 
            else $currency="€";
            if (isset($resultShares[$index]["Analyst"]["Target"]))
                {
                if (isset($resultShares[$index]["Analyst"]["Target"]["Trend"]))
                    {
                    $targetTrend=$resultShares[$index]["Analyst"]["Target"]["Trend"];
                    $description .= " ".nf($targetTrend,"%");
                    if ($targetTrend>10) $description .= "Hot ";
                    elseif ($targetTrend>3) $description .= "Top ";
                    elseif ($targetTrend<-10) $description .= "Garb ";
                    elseif ($targetTrend<-3) $description .= "Flop ";
                    if ($debug) 
                        {
                        echo "Target Trend ".$resultShares[$index]["Analyst"]["Target"]["Trend"]."\n"; 
                        }
                    }
                }

            if ( (isset($resultShares[$index]["Description"]["StdDevPos"])) && (($resultShares[$index]["Description"]["StdDevPos"]+$resultShares[$index]["Description"]["StdDevNeg"])>5) )           // 5% in welchem Zeitraum, bei Report der eingestellten Dauer, zB 3 Monate, sonst alle
                {
                if ($resultShares[$index]["Description"]["StdDevPos"]>($strength*$resultShares[$index]["Description"]["StdDevNeg"])) 
                    {
                    $description .= "Volatile to high";
                    $rating += 1;
                    }
                if (($strength*$resultShares[$index]["Description"]["StdDevPos"])<$resultShares[$index]["Description"]["StdDevNeg"]) $description .= "Volatile to low";
                }
            $targetSell=1.2; $targetSellNow=1.3;

            if (isset($resultShares[$index]["Order"])) 
                {
                $orderBook=$resultShares[$index]["Order"];
                if (isset($resultShares[$index]["Split"])) 
                    { 
                    if ($debug)  echo "  Split   : ".json_encode($resultShares[$index]["Split"])."\n";
                    foreach ($orderBook as $dateOrder => $order)
                        {
                        $orderTime=$ipsOps->strtotimeFormat($dateOrder,"Ymd",false);
                        foreach ($resultShares[$index]["Split"] as $dateSplit => $split)
                            {
                            $splitTime=$ipsOps->strtotimeFormat($dateSplit,"Ymd",false);
                            if ($splitTime>$orderTime) 
                                {  
                                $orderBook[$dateOrder]["pcs"] = $orderBook[$dateOrder]["pcs"] * $split["Split"];
                                $orderBook[$dateOrder]["price"] = $orderBook[$dateOrder]["price"] / $split["Split"];
                                }
                            else if ($debug) echo " $splitTime > $orderTime nicht eingetroffen\n";
                            }
                        //print_r($resultShares[$index]["Order"]);
                        }
                    }
                $result=$this->calcOrderBook($orderBook);
                if ($result["pcs"]>0)
                    {
                    //echo "orderbook full.\n";
                    if (($resultShares[$index]["Description"]["Latest"]["Value"]*$targetSell)<$resultShares[$index]["Description"]["Interval"]["Full"]["Max"]) 
                        {
                        if ($trendPerc < (-10)) $description .= " Sell!";
                        elseif ($trendPerc < (-5)) 
                            {
                            if (($resultShares[$index]["Description"]["Latest"]["Value"]*$targetSellNow)<$resultShares[$index]["Description"]["Interval"]["Full"]["Max"]) $description .= " Sell";
                            else $description .= " Hold";
                            }
                        else $description .= " Buy";
                        }
                    if ($resultShares[$index]["Description"]["Latest"]["Value"]>($resultShares[$index]["Description"]["Interval"]["Full"]["Min"]*$targetSell)) $description .= " Sell";
                    }
                }
            if ( (isset($resultShares[$index]["Order"])===false) || ($result["pcs"]==0))
                {
                // alles verkauft, oder kein Orderbuch angelegt

                }

            /* Show in Tab, eine html oder text Zeile schreiben
             *      Name
             *      StdDev (Volatilität)
             *      StdDevPos/StdDevNeg
             *          Means                           --> nicht in der erweiterten Darstellung
             *      CountPos/CountNeg               positive/negative change in a row maximum
             *      Min                             über gesamte Periode
             *      Max                             über gesamte Periode
             *      Order
             *
             *      Latest                          Algorithmus, wenn in der Nähe (3%) von Max oder Min, verfärbe rot oder grün wenn der Abstand zum Mittelwert größer 10% ist
             *
             */
            if ($html) 
                {
                $zeile="";
                if ($name=="") $zeile .= '<tr><td>'.$resultShares[$index]["Info"]["ID"].'</td>';              // ein Tab reicht für beide
                else $zeile .= '<td>'.$name.'</td>';
                if ($debug) echo "$name \n";
                if ($size>2) $zeile .= '';           // erweiterte Darstellung, mehr externe Analytics statt eigen Auswertungen
                else
                    {
                    if (isset($resultShares[$index]["Description"]["StdDevRel"])) $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["StdDevRel"],2,",",".")."%".'</td>';
                    else $zeile .= '<td></td>';
                    //$zeile .= '<td>'.number_format($spreadPlus,2,",",".")."/".number_format($spreadMinus,2,",",".")."%".'</td>';                   // nur Prozent PosMax/negMax zu Mittelwert ist uns zu wenig
                    if (isset($resultShares[$index]["Description"]["StdDevPos"])) $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["StdDevPos"],2,",",".")."/".number_format($resultShares[$index]["Description"]["StdDevNeg"],2,",",".")."%".'</td>';
                    else $zeile .= '<td></td>';
                    if ($size>0)            // erweiterte Darstellung
                        {
                        if (isset($resultShares[$index]["Description"]["CountPos"]))$zeile .= '<td>'.$resultShares[$index]["Description"]["CountPos"]."/".$resultShares[$index]["Description"]["CountNeg"].'</td>';
                        else $zeile .= '<td></td>';
                        }
                    }
                if ($size>0)            // erweiterte Darstellung
                    {
                    $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["Interval"]["Full"]["Min"],2,",",".").$currency.'</td>';
                    $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["Interval"]["Full"]["Max"],2,",",".").$currency.'</td>';
                    $order=""; $winLoss="";
                    if (isset($resultShares[$index]["Order"])) 
                        {
                        if ($result["pcs"]>0)
                            {
                            $winLoss = ($resultShares[$index]["Description"]["Latest"]["Value"]/($result["cost"]/$result["pcs"])-1)*100;   // in Prozent
                            $winLoss = number_format($winLoss,2,",",".")."%";
                            }
                        $orderEntry=$orderBook[array_key_last($orderBook)];
                        if ($debug) 
                            {
                            echo "  Book   : ".json_encode($orderBook)."\n";  
                            //print_R($orderEntry);
                            }
                        if (isset($orderEntry["price"])) 
                            {
                            if ($orderEntry["pcs"]<0) $order ="s ";
                            else $order ="b ";
                            $order .= number_format($orderEntry["price"],2,",",".").$currency;
                            }
                        }
                    $zeile .= '<td>'.$order.'</td>'; 
                    $zeile .= '<td>'.$winLoss.'</td>';   
                    }
                else $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["Means"],2,",",".").$currency.'</td>';
                if ($size>2)            // Show Analytic Information from YahooFin or Others
                    {
                    if (isset($resultShares[$index]["Description"]["Target"])) 
                        {
                        if ($debug) echo "  Target : ".$resultShares[$index]["Description"]["Target"]."\n";
                        $bgcolor="";
                        if ($resultShares[$index]["Description"]["Latest"]["Value"]>(0.97*$resultShares[$index]["Description"]["Target"])) $bgcolor='bgcolor="green"';
                        $zeile .= '<td '.$bgcolor.'>'.number_format($resultShares[$index]["Description"]["Target"],2,",",".").$currency.'</td>';
                        }
                    else $zeile .= '<td>'.'</td>';   
                    }
                // Darstellung letzter Wert, also der aktuelle Börsenkurs
                $bgcolor="";
                if ( ($spreadPlus >10) && ($resultShares[$index]["Description"]["Latest"]["Value"]>(0.97*$resultShares[$index]["Description"]["Interval"]["Full"]["Max"])) ) $bgcolor='bgcolor="green"';
                if ( ($spreadMinus>10) && ($resultShares[$index]["Description"]["Latest"]["Value"]<(1.03*$resultShares[$index]["Description"]["Interval"]["Full"]["Min"])) ) $bgcolor='bgcolor="red"';
                $zeile .= '<td '.$bgcolor.'>'.number_format($resultShares[$index]["Description"]["Latest"]["Value"],2,",",".").$currency.'</td>';
                if ($trendPerc != 0) $trend = number_format($trendPerc,2,",",".")."%";
                else $trend="";
                if ($size>1)            // Show Month, Week and Day Trend, otherwise it shows week and day trend only
                    {
                    if ( (isset($resultShares[$index]["Description"]["Interval"]["Month"]["Trend"])) && (isset($resultShares[$index]["Description"]["Interval"]["Week"]["Trend"])) && (isset($resultShares[$index]["Description"]["Interval"]["Day"]["Trend"])) )
                        {
                        // Analyse
                        $bgcolor="";
                        $monthTrend = $resultShares[$index]["Description"]["Interval"]["Month"]["Trend"];
                        $weekTrend  = $resultShares[$index]["Description"]["Interval"]["Week"]["Trend"];
                        $dayTrend   = $resultShares[$index]["Description"]["Interval"]["Day"]["Trend"];
                        if ( ($monthTrend>0) && ($weekTrend>0) && ($dayTrend>0) ) $bgcolor='bgcolor="green"';
                        if ( ($monthTrend<0) && ($weekTrend<0) && ($dayTrend<0) ) $bgcolor='bgcolor="red"';
                        }
                    
                    if (isset($resultShares[$index]["Description"]["Interval"]["Month"]["Trend"])) $zeile .= '<td '.$bgcolor.'>'.number_format($resultShares[$index]["Description"]["Interval"]["Month"]["Trend"],2,",",".")."%".'</td>';
                    else $zeile .= '<td></td>';
                    // $zeile .= '<td>('.number_format($resultShares[$index]["Description"]["Interval"]["Week"]["Trend"],2,",",".")."%".')</td>';
                    $zeile .= '<td '.$bgcolor.'>'.$trend.'</td>';
                    if (isset($resultShares[$index]["Description"]["Interval"]["Day"]["Trend"])) $zeile .= '<td '.$bgcolor.'>'.number_format($resultShares[$index]["Description"]["Interval"]["Day"]["Trend"],2,",",".")."%".'</td>';
                    else $zeile .= '<td></td>';
                    }
                else 
                    {
                    $zeile .= '<td>'.$trend.'</td>';                 // compare last 2 Day Week Month
                    $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["Change"],2,",",".")."%".'</td>';
                    }
                if ($size>0) $zeile .= '<td>'.$rating." ".$description.'</td>';
                $zeile .= '<td>'.$resultShares[$index]["Description"]["Count"].'</td>';
                $zeile .= '</tr>';
                if ($lines)
                    {
                    if ($countLines<$lines) 
                        {
                        if ( ($countLines<=$linesBegin) || ($countLines>($lines-$linesEnd)) ) $wert .= $zeile;
                        } 
                    } 
                else $wert .= $zeile;
                $countLines++;
                }
            else            // Darstellung als Text Tabelle mit fixer Anzahl von Leerzeichen
                {
                echo $share["Info"]["ID"]."  ";
                echo str_pad($name,23);
                echo str_pad(number_format($resultShares[$index]["Description"]["StdDevRel"],2,",",".")."%",12," ", STR_PAD_LEFT);                
                echo str_pad(number_format($spreadPlus,2,",",".")."/".number_format($spreadMinus,2,",",".")."%",18," ", STR_PAD_LEFT);
                echo str_pad(number_format($resultShares[$index]["Description"]["Means"],2,",",".")." Euro",18," ", STR_PAD_LEFT);
                if (isset($resultShares[$index]["Description"]["Trend"],)) echo str_pad(number_format($resultShares[$index]["Description"]["Trend"],2,",",".")."%",12," ", STR_PAD_LEFT);
                else echo "             ";
                echo str_pad(number_format($resultShares[$index]["Description"]["Latest"]["Value"],2,",",".")." Euro",18," ", STR_PAD_LEFT);
                echo str_pad(number_format($resultShares[$index]["Description"]["Change"],2,",",".")."%",12," ", STR_PAD_LEFT);
                echo "   $description ";
                echo "\n";
                }
            $table=true;
            }
        if ($html && $table)
            {
            $wert .= '</table></font>';                
            return($wert);
            }            
        return ($table);
        }

    /* 
     *
     */
     
     private function getCurrencySymbol($currencyName)
        {
        switch ($currencyName)
            {
            case "EUR":
                $currency="€";
                break;
            case "USD":
                $currency="$";
                break; 
            case "XPP":
                $currency="%";
                break;                                               
            default:
                $currency="";
                break;
            }
        return ($currency);
        }   

    /*
     *
     */

    public function prepareShares($shares,$debug=false)
        {
        $archiveOps = new archiveOps();              
        $resultShares=array();
        $config=array();    
        $orderbook = $this->getEasychartOrderConfiguration();
                    
        $yahoofin = new SeleniumYahooFin();
        $guthabenHandler = new GuthabenHandler(true,true,false); 
        $configs = $guthabenHandler->getSeleniumTabsConfig("YAHOOFIN",false);            // true for Debug
        $yahoofin->setConfiguration($configs);
        $targets = $yahoofin->getResult("TargetValue", false);                //true für Debug

        foreach($shares as $index => $share)                    //zuerst die aktuellen Daten holen, samt Auswertung
            {
            if ($debug)
                {
                if (isset($share["Name"])) echo "----------------------------\n".$share["Name"];
                if (isset($share["Currency"])) echo " | ".$share["Currency"];
                echo "\n";  
                }
            //Analyse
            $config1=$config;   
            if (isset($share["Split"])) $config1["Split"]=$share["Split"];                      
            $result = $archiveOps->analyseValues($share["OID"],$config1,$debug);                 // true mit Debug, routine übernimmt die Split Config
            $resultShares[$share["ID"]]=$result;                                        // Ergebnise der Analyse werden im Array abgespeichert
            //Datenanreicherung
            $resultShares[$share["ID"]]["Info"]=$share;

            if (isset($orderbook[$share["ID"]])) $resultShares[$share["ID"]]["Order"]=$orderbook[$share["ID"]];
            $archiveOps->addInfoValues($share["OID"],$share);
            if (isset($targets[$index])) 
                {
                if (isset($targets[$index]["Target"]))
                    {
                    $resultShares[$index]["Description"]["Target"]=$targets[$index]["Target"];
                    //echo "Target $index ";
                    }
                }
            if (isset($share["Split"])) $resultShares[$share["ID"]]["Split"]=$share["Split"];         // aus der Depotkonfig nehmen, alternativ auch aus dem Array            
            } 
        return($resultShares);
        }

    /* Orderbook Evaluation of a single share
     * Structure is index date with entries pcs and price
     * Result is amount of pcs and cost/price
     *
     * Achtung, berücksichtigt keinen Split, das muss vorher darüberlaufen
     */

    public function calcOrderBook($orderbook,$debug=false)
        {
        if (isset($orderbook["Orders"])) $orderbook = $orderbook["Orders"];         // kompatibilität mit alter Darstellung
        $pcs=0; $pcs1=0;    
        $cost = 0; $cost1 = 0; 
        foreach ($orderbook as $date => $order)
            {
            if ($debug) echo "    $date   ".str_pad(json_encode($order),70)."|\n";
            $pcsA  = $order["pcs"];
            $costA = $order["pcs"]*$order["price"];
            if ($order["pcs"]>0) 
                {
                $cost1 += $order["pcs"]*$order["price"];
                $pcs1 += $order["pcs"];
                }
            $cost += $costA;
            $pcs  += $pcsA;
            }
        if (($pcs>0) && $debug) echo "    $date             ".str_pad($pcs,18, " ", STR_PAD_LEFT).str_pad(nf($cost,"€"),18, " ", STR_PAD_LEFT)." \n";  
        return(["pcs"=>$pcs,"cost"=>$cost]);
        }

    /* createDepotBookfromOrderBook, Depotbook create from Orderbook
     * createDepotBook macht etwas ähnliches, benötigt aber aktuelle Kurswerte, hier geht es mit createJointConfiguration
     * verwendet calcOrderbook um die Summe cost und pcs zu bekommen und nicht die Kosten und Stück aus der Depot konfiguration
     * berücksichtigt auch Splits
     */

    public function createDepotBookfromOrderBook($debug=false)
        {
        $ipsOps = new ipsOps();            
        if ($debug) echo "Verarbeitung des Orderbooks aus dem Guthabenhandler für das Depotregister \"actualDepot\" (easycharts):\n";
        $shares = $this->createJointConfiguration();
        //print_r($shares);
        $orderbook=$this->getEasychartConfiguration();
        foreach ($orderbook as $id => $book)
            {
            // Split Bearbeitung anfangen
            if (isset($shares[$id]["Split"])) 
                { 
                if ($debug)  echo "  Split   : ".json_encode($shares[$id]["Split"])."\n";
                //print_R($book);
                foreach ($book["Orders"] as $dateOrder => $order)
                    {
                    $orderTime=$ipsOps->strtotimeFormat($dateOrder,"Ymd",false);
                    foreach ($shares[$id]["Split"] as $dateSplit => $split)
                        {
                        $splitTime=$ipsOps->strtotimeFormat($dateSplit,"Ymd",false);
                        if ($splitTime>$orderTime) 
                            {  
                            $book["Orders"][$dateOrder]["pcs"] = $order["pcs"] * $split["Split"];
                            $book["Orders"][$dateOrder]["price"] = $order["price"] / $split["Split"];
                            }
                        elseif ($debug) echo " $splitTime > $orderTime nicht eingetroffen\n";
                        }
                    //print_r($resultShares[$index]["Order"]);
                    }
                }
            $result=$this->calcOrderBook($book);            // für jede Aktie
            if ($debug) echo str_pad($id,15).str_pad($result["pcs"],7);
            if ($result["pcs"]>0) 
                {
                if (isset($shares[$id]["Name"])) 
                    {
                    $depotbook[$id]["Name"]=$shares[$id]["Name"];      // funktioniert nicht wenn neue Aktien dazukommen
                    if ($debug) echo str_pad($shares[$id]["Name"],25);
                    }
                elseif ($debug)  echo str_pad("   do not find ",25);
                $kursKauf=$result["cost"]/$result["pcs"];
                if ($debug)echo str_pad(nf($kursKauf,"€"),14, " ", STR_PAD_LEFT).str_pad(nf($result["cost"],"€"),14, " ", STR_PAD_LEFT);      
                $depotbook[$id]["Stueck"] = $result["pcs"];
                $depotbook[$id]["Kosten"] = $result["cost"];
                $depotbook[$id]["ID"]=$id;
                if (isset($shares[$id]["Currency"])) $depotbook[$id]["Currency"] = $shares[$id]["Currency"];
                if (isset($shares[$id]["Short"])) $depotbook[$id]["Short"] = $shares[$id]["Short"];
                if (isset($shares[$id]["OID"])) $depotbook[$id]["OID"]=$shares[$id]["OID"];      // funktioniert nicht wenn neue Aktien dazukommen
                elseif ($debug) echo "   do not find ";
                }
            if ($debug) echo "\n";
            }
        return ($depotbook);  
        }

    /* createDepotBook, ***************depriciated*************
     *
     * use createDepotBookfromOrderBook and evaluateDepotBook instead
     *
     * Depotbook create from Orderbook and evaluate
     * verwendet calcOrderbook um die Summe cost und pcs zu bekommen und nicht die Kosten und Stück aus der Depot konfiguration
     *
     */

    public function createDepotBook(&$orderbook,$resultShares, $debug=false)
        {
        $spend=0; $actual=0;
        if ($debug)
            {
            echo "createDepotBook form orderbook use resultshares for actual value:\n";
            echo "Erklärung, nimmt die Kauf und Verkaufswerte aus dem orderbook und vergleicht die Kosten mit dem aktuellen Wert.\n";
            echo "\n";
            echo str_pad("",105)."|           Geld         Geld Acc         Wert         Gewinn\n";
            }
        $depotbook=array();
        $contMax=false;
        foreach ($orderbook as $id => $book)
            {
            $latest=0; $kursKauf=0;
            if (isset($resultShares[$id]))              // wenn nicht mehr im actualDepot keine Ausgabe
                {
                if ($debug)
                    {
                    echo str_pad($id,15);
                    if (isset($resultShares[$id]["Info"]["Name"]))  
                        {
                        echo str_pad($resultShares[$id]["Info"]["Name"],50);
                        echo str_pad($resultShares[$id]["Description"]["Count"],6, " ", STR_PAD_LEFT)." ";
                        }
                    else  echo str_pad("",57);
                    }
                $result=$this->calcOrderBook($book);            // es gibt pcs und cost, ausgerechnet aus allen Transaktionen
                $orderbook[$id]["Summary"]=$result;
                //print_R($result);
                if ($result["pcs"]>0) 
                    {
                    $kursKauf=$result["cost"]/$result["pcs"];
                    //echo " ".$result["pcs"]." : ".$result["cost"]." ".str_pad(nf(($result["cost"]/$result["pcs"]),"€"),18, " ", STR_PAD_LEFT);        
                    if ($debug) echo str_pad(nf($kursKauf,"€"),14, " ", STR_PAD_LEFT);        
                    $depotbook[$id]["Stueck"] = $result["pcs"];
                    $depotbook[$id]["Kosten"] = $result["cost"];
                    if (isset($resultShares[$id]["Info"]["Name"])) $depotbook[$id]["Name"]=$resultShares[$id]["Info"]["Name"];
                    $depotbook[$id]["ID"]=$id;
                    $depotbook[$id]["OID"]=$resultShares[$id]["Info"]["OID"];
                    }
                else 
                    {
                    //unset ($orderbook[$id]);                            //cleanup orderbook
                    }
                $spend += $result["cost"]; 
                if (isset($resultShares[$id]["Description"]["Latest"])) 
                    {
                    $latest = $resultShares[$id]["Description"]["Latest"]["Value"];
                    //echo $latest." (".date("d.m.Y H:i:s",$resultShares[$id]["Description"]["Latest"]["TimeStamp"]).") ";
                    if ($result["pcs"] != 0) $actual += $latest*$result["pcs"];
                    if ( ($debug) && ($kursKauf>0) && ($latest>0) ) 
                        {
                        echo "->";
                        echo str_pad(nf($latest,"€"),16, " ", STR_PAD_LEFT)." (".date("d.m.Y H:i:s",$resultShares[$id]["Description"]["Latest"]["TimeStamp"]).") ";
                        echo "  ".str_pad(nf(($latest/$kursKauf-1)*100,"%"),10, " ", STR_PAD_LEFT);
                        echo str_pad(nf($result["cost"],"€"),16, " ", STR_PAD_LEFT).str_pad(nf($spend,"€"),16, " ", STR_PAD_LEFT);
                        echo str_pad(nf($latest*$result["pcs"],"€"),16, " ", STR_PAD_LEFT).str_pad(nf($actual,"€"),16, " ", STR_PAD_LEFT);
                        echo "\n";
                        }
                    }
                else 
                    {
                    echo "Description Latest ist nicht vorhanden:\n";
                    }
                }
            else echo "createDepotBook, Eintrag in Orderbook für ID $id aber nicht in resultshares.\n";
            }
        if ($debug) 
            {
            echo "Gesamtergebnis Depot ist Kosten ".nf($spend,"€")." und Wert ".nf($actual,"€")."\n";
            }

        return($depotbook);
        }

    /* evaluate Depotbook verwendet Kosten und Stueck um den Wert des Depots zu berechnen
     * calcOrderbook nicht mehr notwendig, es gibt bereits Stueck und Kosten
     * createDepotBook wird vorher aufgerufen und generiert das depotbook
     * Depotbook wird um zusätzliche Daten erweitert
     *
     */

    public function evaluateDepotBook(&$depotbook,$resultShares, $debug=false)
        {
        $spend=0; $actual=0;
        if ($debug)
            {
            echo "\n";
            echo "evaluateDepotBook, es gibt ".count($depotbook)." Einträge:\n";
            }
        $countMax=false;

        /* die Depotwerte zu jedem Zeitpunkt berechnen, Grundgerüst anlegen und vorhandene Werte auswerten */
        echo "Grundgeruest anlegen:\n";
        $valuebook=array();
        $failures=array();
        $resultValues=array();
        foreach ($depotbook as $id => $book)
            {
            //echo $id."  ";
            if (isset($resultShares[$id]["Values"]))              // wenn nicht mehr im actualDepot keine Ausgabe
                {
                //echo ":";
                foreach ($resultShares[$id]["Values"] as $index=>$wert)
                    {
                    //echo ".";
                    $indexTimeDay=date("ymd",$wert["TimeStamp"]);
                    $resultValues[$indexTimeDay]["TimeStamp"]=$wert["TimeStamp"];
                    $resultValues[$indexTimeDay]["Value"] = 0;
                    $valuebook[$id]["Values"][$indexTimeDay]=$wert;
                    if (isset($resultValues[$indexTimeDay]["ID"])) 
                        {
                        $pos1=strpos($resultValues[$indexTimeDay]["ID"],$id);
                        if ($pos1 !==false) 
                            {
                            //echo "doppelter Eintrag für ID $id am $indexTimeDay !\n";
                            if (isset($failure[$indexTimeDay]["ID"]["Double"])) $failure[$indexTimeDay]["ID"]["Double"] .= " ".$id;
                            else $failure[$indexTimeDay]["ID"]["Double"] = $id;
                            }
                        else $resultValues[$indexTimeDay]["ID"] .= " ".$id;
                        }
                    else 
                        {
                        $resultValues[$indexTimeDay]["ID"] = $id;
                        }

                    }
                }
            else echo "evaluateDepotBook, Fehler, keine Werte vorhanden für $id obwohl im Depotbook.\n";
            }
        ksort($resultValues);
        $countMax=36;
        //echo "\n";

        /* Tabelle Darstellung in depotTable damit auch als html angezeigt werden kann
         * Werte vom depotbook werden nur übernommen wenne s einen Kurs in resultshares gibt
         */
        echo "Tabelle berechnen:\n";
        $knownValues=array();
        $depotTable=array();
        if ($debug) echo str_pad("",130)."|           Geld         Geld Acc         Wert         Wert Acc\n";
        $row=0;
        foreach ($depotbook as $id => $book)
            {
            $latest=0; $kursKauf=0;
            if (isset($resultShares[$id]))              // wenn nicht mehr im actualDepot keine Ausgabe
                {
                $depotTable[$row]["ID"]=$id;
                if ($debug)echo str_pad($id,15);
                if (isset($resultShares[$id]["Info"]["Name"]))  
                    {
                    $depotTable[$row]["Name"]=$resultShares[$id]["Info"]["Name"];   
                    if ($debug)
                        {
                        echo str_pad($resultShares[$id]["Info"]["Name"],50);
                        echo str_pad($resultShares[$id]["Description"]["Count"],6, " ", STR_PAD_LEFT)." ";
                        }
                    }
                elseif ($debug)  echo str_pad("",57);
                    
                //print_R($book);
                if ($book["Stueck"]>0)                // unwahrscheinlich, dass keine Stueck hier vorkommen
                    {
                    $kursKauf=$book["Kosten"]/$book["Stueck"];
                    $depotTable[$row]["priceBuy"]=$kursKauf;
                    $depotTable[$row]["cost"]=$book["Kosten"];
                    $depotTable[$row]["pcs"]=$book["Stueck"];
                    if ($debug) echo str_pad(nf($kursKauf,"€"),14, " ", STR_PAD_LEFT);        
                    if (isset($resultShares[$id]["Info"]["Name"])) $depotbook[$id]["Name"]=$resultShares[$id]["Info"]["Name"];
                    }
                else 
                    {
                    echo "evaluateDepotBook, Fehler Depotbook ist leer für $id\n";
                    }
                $spend += $book["Kosten"]; 
                if (isset($resultShares[$id]["Description"]["Latest"])) 
                    {
                    $latest = $resultShares[$id]["Description"]["Latest"]["Value"];
                    $knownValues[$id]=$resultShares[$id]["Description"]["Latest"];                  // wenn kein aktueller Wert vorhanden ist
                    //echo $latest." (".date("d.m.Y H:i:s",$resultShares[$id]["Description"]["Latest"]["TimeStamp"]).") ";
                    if ($book["Stueck"] != 0) $actual += $latest*$book["Stueck"];
                    if ( ($kursKauf>0) && ($latest>0) ) 
                        {
                        //$depotTable[$row]["priceActual"]=["Value"=>$latest,"TimeStamp"=>$resultShares[$id]["Description"]["Latest"]["TimeStamp"]];
                        $depotTable[$row]["priceActualValue"]=$latest;
                        $depotTable[$row]["priceActualTimeStamp"]=$resultShares[$id]["Description"]["Latest"]["TimeStamp"];
                        $depotTable[$row]["change"]=($latest/$kursKauf-1);
                        $depotTable[$row]["value"]=$latest*$book["Stueck"];
                        echo "->";
                        echo str_pad(nf($latest,"€"),16, " ", STR_PAD_LEFT)." (".date("d.m.Y H:i:s",$resultShares[$id]["Description"]["Latest"]["TimeStamp"]).") ";
                        echo "  ".str_pad(nf(($latest/$kursKauf-1)*100,"%"),10, " ", STR_PAD_LEFT);
                        echo str_pad(nf($book["Kosten"],"€"),16, " ", STR_PAD_LEFT).str_pad(nf($spend,"€"),16, " ", STR_PAD_LEFT);
                        echo str_pad(nf($latest*$book["Stueck"],"€"),16, " ", STR_PAD_LEFT).str_pad(nf($actual,"€"),16, " ", STR_PAD_LEFT);
                        echo "\n";
                        }
                    }
                else 
                    {
                    echo "Description Latest ist nicht vorhanden:\n";
                    }
                for ($i=0;$i<$countMax;$i++)
                    {
                    $indexTimeDay=date("ymd",$resultShares[$id]["Values"][$i]["TimeStamp"]);
                    if (isset($valuebook[$indexTimeDay]))
                        {
                //$valuebook[$indexTimeDay][$i]["Value"] += $array["Info"]["Stueck"]*$resultShares[$index]["Values"][$i]["Value"];
                //$actual[$i]["TimeStamp"] = $resultShares[$index]["Values"][$i]["TimeStamp"];
                        }
                    }
                $row++;
                }
            else echo "evaluateDepotBook, Fehler Eintrag in Depotbook aber nicht in resultshares.\n";
            }
        if ($debug) 
            {
            echo "Gesamtergebnis Depot ist Kosten ".nf($spend,"€")." und Wert ".nf($actual,"€")."\n";
            }

        /* wir fangen mit dem ältesten Datum an und arbeiten uns in die Gegenwart, 
         * dazu gehen wir das Depotbook durch und schauen ob es einen Eintrag in Resultshares gibt
         * eigentlich wird valuebook genommen, das wurde vorher aus resultshares erzeugt, hat Index YYmmdd 
         * ab und zu fehlen Einträge, diese sollten vorher vervollständigt werden, hier werden bereits bekannte werte als knownValues geführt
         * knownValues sind die latestWerte ausser sie wurden berits einmal erkannt
         */  
        echo "Deoptbook Wert zeitlicher Verlauf:\n";
        echo "Index      ";
        foreach ($depotbook as $id => $book)
            {
            if ($book["Stueck"]>0)  echo str_pad($book["Name"],22);                
            }
        echo "\n";
        $once=false;            // false, keinen Verlauf ausgeben
        foreach ($resultValues as $indexTimeDay => $entry)
            {
            echo $indexTimeDay."   ";
            foreach ($depotbook as $id => $book)
                {
                if ($book["Stueck"]>0) 
                    {
                    if (isset($valuebook[$id]["Values"][$indexTimeDay]))              // wenn nicht mehr im actualDepot oder im depotbook keine Stück erfolgt keine Ausgabe 
                        {
                        //echo str_pad($book["Name"],22);
                        if ($once) { print_R($valuebook[$id]["Values"]); $once=false; }
                        $wert = $valuebook[$id]["Values"][$indexTimeDay]["Value"]*$book["Stueck"];
                        $resultValues[$indexTimeDay]["Value"] += $wert;
                        $knownValues[$id]=$valuebook[$id]["Values"][$indexTimeDay];
                        }
                    else
                        {
                        //echo str_pad("",22);
                        $wert=$knownValues[$id]["Value"]*$book["Stueck"];
                        $resultValues[$indexTimeDay]["Value"] += $wert;;
                        if (isset($failure[$indexTimeDay]["ID"]["Missing"])) $failure[$indexTimeDay]["ID"]["Missing"] .= " ".$id;
                        else $failure[$indexTimeDay]["ID"]["Missing"] = $id;                        
                        }
                    echo str_pad(nf($wert,""),22);
                    }
                }
            echo nf($resultValues[$indexTimeDay]["Value"],"")."\n";
            }
        ksort($failure);
        //if ($debug) print_R($failure);
        $depotbook["Value"]=$resultValues;
        $depotbook["Table"]=$depotTable;
        return(true);
        }



    /* EasyCharts, hier wird Schritt für Schritt das Automatische Abfragen der Easychart Webseite abgearbeitet
     *  (1) Abfrage ob bereits eingelogged
     *  (2) einloggen
     *  (3) goto Musterdepot
     *  (4) Musterdepot 3 auswählen
     *  (5) Tabelle einlesen
     *
     * Es gibt eine Konfiguration Depot, diese gibt den Namen des auszulesenden Depots an
     * Wert kann ein Array sein mit mehren Werten.
     *
     */
    public function runAutomatic($step=0)
        {
        if ($this->debug) echo "runAutomatic SeleniumEasycharts Step $step.\n";
        switch ($step)
            {
            case 0:
                $this->depotCount=1;              // es können mehrere Depots in einem Durchgang abgefragt werden
                if (isset($this->configuration["Depot"])) 
                    {
                    if (is_array($this->configuration["Depot"]))
                        {
                        $this->depotCount=count($this->configuration["Depot"]);    
                        }
                    }
                if ($this->debug) echo "--------\n0: check if not logged in, then log in. ".$this->depotCount." Depots are read. \n";
                if ($this->getTextValidLoggedInIf()!==false)  return(["goto" => 2]);                // brauche ein LogIn Fenster
                break;
            case 1:
                if ($this->debug) echo "--------\n1: enter log in.\n";
                $result = $this->enterLogin($this->configuration["Username"],$this->configuration["Password"]);            
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 2:
                if ($this->debug) echo "--------\n2: goto Link Musterdepot.\n";
                $result = $this->gotoLinkMusterdepot();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 3:
                if ($this->debug) echo "--------\n3: read Table Musterdepot Uebersicht\n";
                $this->tableOverview = $this->readTableMusterdepot();                                            //result ist ein array mit den Titel der Links in der Tabelle 
                if ($this->debug) echo "Ergebnis ------------------\n".json_encode($this->tableOverview)."\n";                 
                break;
            case 4:
                if ($this->debug) echo "--------\n4: goto Link Musterdepot3.\n";
                $found=false;
                if (isset($this->configuration["Depot"])) 
                    {
                    $depot=$this->configuration["Depot"][($this->depotCount-1)];
                    foreach ($this->tableOverview as $index => $name)
                        {
                        $pos1=strpos($name,$depot);
                        if ($pos1 !== false) $found = $index+1;
                        }
                    echo "Gesuchtes Musterdepot ist auf Position $found \n";                            
                    }
                if ($found !== false) $result = $this->gotoLinkMusterdepot3($found);            // zum jeweiligen Musterdepot wechseln
                else $result = $this->gotoLinkMusterdepot3();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 5:
                if ($this->debug) echo "--------\n5: get Table with Charts.\n";
                $result = $this->getTablewithCharts();
                if ($result===false) return ("retry");
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                if (isset($this->configuration["Depot"])) return(["Ergebnis" => $result, "Depot" => $this->configuration["Depot"][($this->depotCount-1)]]);
                else return(["Ergebnis" => $result]);
                break;   
            case 6:
                if ($this->debug) echo "--------\n6: go back to Link Musterdepot.\n";
                $result = $this->gotoLinkMusterdepot();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    } 
                $this->depotCount--;
                if ($this->depotCount>0) return(["goto" => 4]);
                break;
            default:
                return (false);
            }

        }

    /* Easycharts, get text , find out wheter being logged in 
     * /html/body/div/div[2]/div[1]/div[4]/div[1]/span
     */
    function getTextValidLoggedInIf()
        {
        if ($this->debug) echo "getTextValidLoggedInIf ";
        $xpath='/html/body/div/div[2]/div[1]/div[4]/div[1]/span';
        $result = $this->getTextIf($xpath,$this->debug);
        if ($result !== false)
            {
            if ($this->debug) echo "found fetch data, length is ".strlen($result)."\n";
            print $result;
            }
        return ($result);
        }     

    /* login entry username und password
     * /html/body/div/div[2]/div[1]/div[5]/div[2]/form/table/tbody/tr[1]/td[2]/input            form username
     * /html/body/div/div[2]/div[1]/div[5]/div[2]/form/table/tbody/tr[3]/td[2]/input            form password
     * /html/body/div/div[2]/div[1]/div[5]/div[2]/table/tbody/tr/td[1]/a/span           button 
     */
    function enterLogin($username,$password)
        {
        $xpath = '/html/body/div/div[2]/div[1]/div[5]/div[2]/form/table/tbody/tr[1]/td[2]/input';        // relative path
        $this->sendKeysToFormIf($xpath,$username);              // wjobstl
        $xpath = '/html/body/div/div[2]/div[1]/div[5]/div[2]/form/table/tbody/tr[3]/td[2]/input';        // relative path
        $this->sendKeysToFormIf($xpath,$password);              // cloudg06        
        $xpath='/html/body/div/div[2]/div[1]/div[5]/div[2]/table/tbody/tr/td[1]/a/span';
        $this->pressButtonIf($xpath);        
        }

    /* links unten gibt es ein Feld mit Musterdepot, Kaufoption etc.
     * hier Musterdepot auswählen
     */

    private function gotoLinkMusterdepot()
        {
        /* //*[@id="7_1_link"]
         */
        $xpath='//*[@id="7_1_link"]';
        $status=$this->pressButtonIf($xpath);             
        //if ($status===false) return($this->updateUrl($url));
        return ($status);                    
        }

    /* Tabelle aller Musterdepots auslesen 
     * /html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody
     *
     * es wird eine ganze Tabelle ausgelesen, wenn man auch die html Seperatoren haben will braucht man einen Workaround
     * innerHtml wird noch auf einen selector umgestellt, liefert aktuell den ganzen Body
     * dieser wird in Debug abgespeichert
     *
     */

    private function readTableMusterdepot() 
        {
        $xpath='/html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody';
        $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
        if ((strlen($ergebnis))>10) 
            {
            $page=$this->getInnerHtml();                     
            //$this->analyseHtml($page,false,"DIV");                                     // nicht mehr verwendet, Parameter $displayMode,$commandEntry
            $debugVariable=CreateVariableByName($this->getResultCategory(),"Debug",3);
            SetValue($debugVariable,$page);
            $innerHtml = $this->queryFilterHtml($page,'//div[@*]','style',"width: 608px",$this->debug);         //  suche in der aktuellen Position nach div mit beliebigen Attributen
            $ergebnis = $this->extractFilterHtml($innerHtml,'//a[@*]','title',true);             // true für Debug            
            //$this->analyseHtml($page,true);                 // true alles anzeigen
            return($ergebnis);
            }
        }

    /* Tabelle einzelner Musterdepots auslesen 
     * Musterdepot 3
     * /html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody/tr[8]/td[1]/a
     * Kaufoption
     * /html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody/tr[4]/td[1]/a
     */

    private function gotoLinkMusterdepot3($id=0)
        {
        /* /html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody/tr[8]/td[1]/a
         */
        switch ($id)
            {
            case 3:
                $xpath='/html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody/tr[4]/td[1]/a';
                break;
            default:
                $xpath='/html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody/tr[8]/td[1]/a';
                break;
            }
        $status=$this->pressButtonIf($xpath);             
        //if ($status===false) return($this->updateUrl($url));
        return ($status);                    
        }


    private function getTablewithCharts()
        {
        /* /html/body/div/div[3]/div/div[2]/table/tbody/tr/td/form/table[1]/tbody/tr/td/table[1]
         */
        if ($this->debug) echo "Try /html/body/div/div[3]/div/div[2]/table/tbody/tr/td/form/table[1]/tbody/tr/td/table[1]\n";
        $xpath='/html/body/div/div[3]/div/div[2]/table/tbody/tr/td/form/table[1]/tbody/tr/td/table[1]';
        $ergebnis = $this->getHtmlIf($xpath,$this->debug);    
        if ((strlen($ergebnis))>10) return($ergebnis);
        else echo $ergebnis;
        }
    }

/* Selenium Webdriver automatisiert bedienen
 * Aufruf mit automatedQuery
 *
 * andere Routinen für SeleniumOperations
 *  __construct
 *  initResultStorage
 *  getCategory
 *  getCategories
 *  defineTargetID
 *  writeResult             allgemeine Schreibroutine
 *  readResult              allgemeine Leseroutine
 *  automatedQuery
 *  storeString
 *  findTag
 *
 */

class SeleniumOperations
    {

    private $guthabenHandler;
    private $CategoryId, $CategoryIdData;                   // Kategorien, Selenium und Selenium.RESULT

    /* Initialisiserung */

    public function __construct()
        {
        $this->guthabenHandler = new GuthabenHandler(true,true,true);         // Steuerung für parsetxtfile
        $this->CategoryId = $this->guthabenHandler->getCategoryIdSelenium();
        $this->CategoryIdData =CreateCategoryByName($this->CategoryId,"RESULT");
        }

    /* wird von automatedQuery aufgerufen, legt alle Kategorien an für die Hosts
     * geht auch mit getCategory[Host]
     */

    function initResultStorage($configuration)
        {
        if (isset($configuration["Hosts"])) $configuration = $configuration["Hosts"];
        foreach ($configuration as $index => $entry)
            {
            CreateCategoryByName($this->CategoryIdData,$index);
            }
        }

    /* getCategory
     * ohne Parameter wird es data.modules.Guthabensteuerung.Selenium.RESULT
     * mit Parameter  wird es data.modules.Guthabensteuerung.Selenium.RESULT.$sub
     */
    function getCategory($sub=false)
        {
        if ($sub === false) return ($this->CategoryIdData);
        else
            {
            //echo "Get Category $sub in ".$this->CategoryIdData."\n";    
            $categoryID = @IPS_GetObjectIDByName($sub, $this->CategoryIdData);
            if ($categoryID===false) $categoryID = CreateCategoryByName($this->CategoryIdData,$sub);            // wenn noch nicht da halt anlegen
            return ($categoryID);
            }
        }

    /* getCategories
     * alle children als array ausgeben
     * 
     */
    function getCategories()
        {
        $oid=$this->getCategory();
        $result=IPS_getChildrenIDs($oid);
        foreach ($result as $index => $childID) echo "  $index  $childID (".IPS_GetName($childID).") \n";
        return ($result);
        }

    /* in der Selenium Config gibt es auch die Möglichkeit eine Result targetID vorzugeben
     * im normalen result werden die Werte nicht historisiert
     */
    function defineTargetID($sub,$config,$debug=false)
        {
        $ipsOps = new ipsOps();  
        $archiveOps = new archiveOps();              
        $archiveID = $archiveOps->getArchiveId();  
        if ($debug)
            {
            echo "defineTargetID, eine Variable festlegen in dem die Ergebnisse archiviert werden.\n";
            print_R($config);
            }
        $TargetID=false;
        if (isset($config["ResultTarget"]))
            {
            if (is_array($config["ResultTarget"])) 
                {
                if (isset($config["ResultTarget"]["OID"])) $TargetID = $config["ResultTarget"]["OID"];              // Wenn die OID angegeben wurde, ist die Variable bereits woanders definiert worden
                /*if ($TargetID === false)      // workaround, nicht mehr verwenden
                    {
                    if (isset($installedModules["Amis"])===false) echo "unknown Module.\n";
                    else    
                        {
                        // ********************* noch nicht richtig implmentiert, sucht nicht sondern definiert Test-BKS01 
                        $ID = CreateVariableByName($CategoryIdDataAmis, "Test-BKS01", 3);           // Name neue Variablen
                        SetValue($ID,"nur testweise den EVN Smart Meter auslesen und speichern");
                        $TargetID = CreateVariableByName($ID, 'Wirkleistung', 2);   // 0 Boolean 1 Integer 2 Float 3 String 
                        }
                    }  */
                }
            else 
                {
                $targetName =$config["ResultTarget"];
                $categoryID=$this->getCategory($sub);
                $TargetID = CreateVariableByName($categoryID, $targetName, 2);                          // float einmal als default annehmen

                }
            }
        if ($TargetID)
            {   
            if (AC_GetLoggingStatus($archiveID, $TargetID)==false) 
                {
                if ($debug) echo "   Werte wird noch nicht im Archive gelogged. Jetzt als Logging konfigurieren. \n";
                AC_SetLoggingStatus($archiveID,$TargetID,true);           // daraus eine geloggte Variable machen
                }
            $type=AC_GetAggregationType ($archiveID,$TargetID);
            if ($debug) echo "   Archivierte Werte erfassen, bearbeiten und speichern in $TargetID (".$ipsOps->path($TargetID).") :  Type is ".($type?"Zaehler":"Werte")."\n";
            }
        return ($TargetID);
        }


    /* speichern von Ergebnissen bei der Auslesung von Webinhalten
     * Ort ist data.Guthabensteuerung.Selenium.RESULT (CategoryIdData)
     * unter index ist bereits eine Unterkategorie vorhanden, siehe Index von der Abfrage Konfiguration EASY, ORF etc.
     * dort wird eine neue Stringvariable mit dem Namen result erstellt
     * dort hin wird das Ergebnis in result gespeichert
     */

    function writeResult($index,$result,$name="Result", $debug=false)
        {
        echo "SeleniumOperations::writeResult($index,..,$name).\n";
        $categoryID = @IPS_GetObjectIDByName($index, $this->CategoryIdData);                // index ist Easy, Drei, YahooFin, Orf etc.
        if ($categoryID===false) return(false);
        $variableID = CreateVariableByName($categoryID, $name, 3);                          // name ist result, und wenn mehrere Durchläufe dann personalifiziert
        SetValue($variableID,$result);

        }

    /* SeleniumOperations, einfach von einem Module (Easy, Drei etc) nur RESULT auslesen und mit Value und LastChanged speichern
     * es gibt ein paar Zusatzfunktionen: 
     *  ohne index/false wird eine Übersicht der in Data vorhandenen Module, ID und Anzahl Ergebnisregister gegeben
     *  name ist der Name des Ergebnisregisters, entwerde defalut Result oder der Name oder mehrere Namen durch & getrennt
     *
     */

    function readResult($index=false,$name="Result", $debug=false)
        {
        $result=array();
        if ($index==false) 
            {
            if ($debug) echo "SeleniumOperations::readResult ohne Zielangabe aufgerufen.\n";
            echo "Vorhandene Module, ID und Anzahl Ergebnisregister:\n";
            $childrens=IPS_getChildrenIDs($this->CategoryIdData);
            foreach ($childrens as $children)
                {
                $objectType=IPS_getObject($children)["ObjectType"];
                $Name=IPS_GetName($children);
                if ($objectType==0)     // nur die Kategorien anzeigen 
                    {
                    $countData=sizeof(IPS_getChildrenIDs($children));
                    echo "   $children   ".str_pad($Name,40)."  Registeranzahl : $countData   \n";
                    }
                }
            return($this->CategoryIdData);
            }
        elseif ($debug) echo "SeleniumOperations::readResult mit Zielangabe \"$index\" aufgerufen.\n";

        $categoryID = @IPS_GetObjectIDByName($index, $this->CategoryIdData);
        if ($categoryID===false)
            {
            echo "readResult, Fehler, $index in ".$this->CategoryIdData." nicht gefunden.\n";
            return (false);
            }
        $more=explode("&",$name);
        if (sizeof($more)>1)
            {
            print_r($more);
            $variableNameID = @IPS_GetObjectIDByName($more[1], $categoryID);
            if ($variableNameID===false) return(false);
            $variableName = GetValue($variableNameID);
            echo "readResult   $name = $variableName\n";
            $variableID = @IPS_GetObjectIDByName($variableName, $categoryID);
            if ($variableID===false) return(false);
            }
        else
            {
            //$variableID = CreateVariableByName($categoryID, $name, 3);
            $variableID = @IPS_GetObjectIDByName($name, $categoryID);
            echo "readResult, suche $name als OID:$variableID in $categoryID ($index in ".$this->CategoryIdData.").\n";
            if ($variableID===false) return(false);
            }
        $result["Value"]=GetValue($variableID);
        $result["LastChanged"]=IPS_GetVariable($variableID)["VariableChanged"];
        return ($result);
        }

    /* nur die Result ID ausgeben
     */
    function getResultID($index=false,$name="Result", $debug=false)
        {
        $result=array();
        if ($index==false) 
            {
            if ($debug) echo "SeleniumOperations::readResult ohne Zielangabe aufgerufen.\n";
            echo "Vorhandene Module, ID und Anzahl Ergebnisregister:\n";
            $childrens=IPS_getChildrenIDs($this->CategoryIdData);
            foreach ($childrens as $children)
                {
                $objectType=IPS_getObject($children)["ObjectType"];
                $Name=IPS_GetName($children);
                if ($objectType==0)     // nur die Kategorien anzeigen 
                    {
                    $countData=sizeof(IPS_getChildrenIDs($children));
                    echo "   $children   ".str_pad($Name,40)."  Registeranzahl : $countData   \n";
                    }
                }
            return($this->CategoryIdData);
            }
        elseif ($debug) echo "SeleniumOperations::readResult mit Zielangabe \"$index\" aufgerufen.\n";

        $categoryID = @IPS_GetObjectIDByName($index, $this->CategoryIdData);
        if ($categoryID===false)
            {
            echo "readResult, Fehler, $index in ".$this->CategoryIdData." nicht gefunden.\n";
            return (false);
            }
        $more=explode("&",$name);
        if (sizeof($more)>1)
            {
            print_r($more);
            $variableNameID = @IPS_GetObjectIDByName($more[1], $categoryID);
            if ($variableNameID===false) return(false);
            $variableName = GetValue($variableNameID);
            echo "readResult   $name = $variableName\n";
            $variableID = @IPS_GetObjectIDByName($variableName, $categoryID);
            if ($variableID===false) return(false);
            }
        else
            {
            //$variableID = CreateVariableByName($categoryID, $name, 3);
            $variableID = @IPS_GetObjectIDByName($name, $categoryID);
            echo "readResult, suche $name als OID:$variableID in $categoryID ($index in ".$this->CategoryIdData.").\n";
            if ($variableID===false) return(false);
            }
        return ($variableID);
        }
 
    /* alle Homepages gleichzeitig abfragen 
     *
     * Index von Configtabs gibt die einzelnen Seiten/Tabs vor
     * mit Url wird der Tab benannt und wieder erkannt, wenn keine Class definiert ist, bleibts auch beim Seitenaufruf
     * sonst wird im Step 0 die class initialisiert
     * es gibt einen gesamten Step counter step und einen individuellen steps für die einzelnen Webserver, steps wird im step 0 initaialisisert
     * in jedem Step wird zwischen allen konfigurierten Fenster umgeschalten und entsprechend dem individuellen Stepcounter weitergemacht
     * im array handler sind die Fenster gespeichert, in runSelenium die einzelnen Klassen, in steps der individuelle Schritt
     * wenn als Antwort retry zurückkommt wird im selben Schritt geblieben, sonst um eins weitergezählt
     *
     * Ablauf Überblick:
     *  Init SessionId, WebDriverUrl, Status (webDriverStatusId)
     *  update Handles der einzelnen Webseiten
     *  WebDriver starten mit initHosts
     *  Schleife bis alle Webseiten schrittweise abgearbeitet wurden
     *      -
     *
     * Aus dem configTabs wird die Configuration genommen:
     *  Index (Drei, Orf etc.)
     *      URL         diese Url wird als erstes aufgerufen, es können danach weitere Urls angesteuert werden, Index wird ignoriert wenn nicht gesetzt
     *      CLASS       es wird für die Bearbeitung die class genutzt, wenn nicht gesetzt erfolgt nur getHost mit der Url
     *      TYPE
     *      CONFIG      wenn gesetzt wird mit dem Inhalt aus der class setConfiguration aufgerufen
     *  Spezialfall wenn Index Logging ist, das ist eine übergeordnete Konfiguration und kein Host
     *
     * Wenn CLASS geesetzt wird wird die class mit new initialisiert, die instanz im Array gespeichert, Parameter ist debug   
     * runAutomatic wird für jede class mit dem entsprechendem Step aufgerufen, die function liefert ein Ergebnis zurück und ermöglicht ide Steuerung der Statemachine
     *      false   failed erhöhen
     *      retry   nächste Instanz, aber hier beim gleichen Step bleiben
     *      Array, kann mehrere Befehle gleichzeitig, ZB Ergebnis und goTo
     *
     */

    function automatedQuery($webDriverName,$configTabs,$debug=false)
        {
        $guthabenHandler = new GuthabenHandler();       // keine besonderen Betriebsarten 
        $sessionID      = $guthabenHandler->getSeleniumSessionID($webDriverName);
        $seleniumHandler = new SeleniumHandler();           // Selenium Test Handler, false deaktiviere Ansteuerung von webdriver für Testzwecke vollstaendig
        
        $configSelenium = $guthabenHandler->getSeleniumWebDriverConfig($webDriverName);
        $webDriverUrl   = $configSelenium["WebDriver"];

        /* Ausgabe in StatusVariable, zum debuggen während der Runtime */
        if ( ($webDriverName==false) || ($webDriverName=="false") ) $webDriverName2="Default";
        else $webDriverName2=$webDriverName; 
        $webDriverStatusId=IPS_GetObjectIDByName("StatusWebDriver".$webDriverName2,$this->CategoryId);
        $status="$webDriverName2 unter $webDriverUrl Browser: ".$configSelenium["Browser"];
        SetValue($webDriverStatusId,$status);

        if (isset($configTabs["Logging"])) 
            {
            $logging=$configTabs["Logging"];
            unset($configTabs["Logging"]);              // Routinen erwarten das Format [Hosts] als Index
            }
        else $logging=false;

        if ($debug) 
            {
            echo "automatedQuery: $webDriverName, Aufruf mit Configuration:\n";
            print_R($configTabs);
            $startexec=microtime(true);

            if ( ($sessionID) && ($sessionID != "") ) echo "SessionID in Datenbank verfügbar :".$sessionID." mit Wert \"".GetValue($sessionID)."\"\n";
            echo "WebDriver ausgewählt: $webDriverName2 unter $webDriverUrl Browser: ".$configSelenium["Browser"]."  \n"; 
            echo "Ergebnisse auch hier speichern : $webDriverStatusId (".IPS_GetName($webDriverStatusId).")\n";           
            }


        /* Handler abgleichen */
        $handler      = $guthabenHandler->getSeleniumHandler($webDriverName);                         // den Selenium Handler vom letzten Mal wieder aus der IP Symcon Variable holen. php kann sich von Script zu Script nix merken
        if ($handler) 
            {
            if ($debug) echo "Handler sind bereits in der Datenbank gespeichert und verfügbar. Sync with Selenium\n";    
            $seleniumHandler->updateHandles($handler);
            }

        /* WebDriver starten */
        $result = $seleniumHandler->initHost($webDriverUrl,$configSelenium["Browser"],$sessionID,$debug);          // result sind der Return wert von syncHandles
        if ( ($result === false) || (isset($result["Failure"])) )
            {
            if ($result === false) echo "Kann Webdriver $webDriverUrl nicht finden.\n";
            else 
                {
                //echo "Fehler erkannt, schreiben.\n";
                SetValue($webDriverStatusId,date("d.m.Y H:i:s")." : ".$result["Failure"]["Short"]);
                //print_r($result["Failure"]);
                }
            } 
        else            
            {
            if ($debug)
                {
                echo "initHost für $webDriverUrl abgeschlossen. Resultat verfügbare bekannte Handles ".json_encode($result)."\n";
                echo "Sync jetzt aktuelle verfügbare WebDriver Handles with Selenium\n";    
                }
            $seleniumHandler->updateHandles(json_encode($result));
            $this->initResultStorage( $configTabs);           // Kategorien passend zur Konfiguration für Ergebnisse aufbauen
            $handler=array();
            $runSelenium=array();                   // array of classes
            $steps = array();                       // individual State machine
            $step=0;  $maxStep=20;                      // Schrittweise bis fertig oder maxStep
            if (count($configTabs)<4) $delay=1;
            else $delay=0;
            do
                {
                $done=0; $failed=0;
                foreach ($configTabs as $index => $entry)
                    {
                    $date = new DateTime("NOW");            // aktuelle Uhrzeit
                    if ($debug) 
                        {
                        if ($step==0) echo "======================================Start with Index $index, ".$date->format("H:i:s.v")." Laufzeit bis jetzt: ".round(microtime(true)-$startexec,2)." Sekunden\n";
                        else         echo "================================Continue [$step] with Index $index, ".$date->format("H:i:s.v")." Laufzeit bis jetzt: ".round(microtime(true)-$startexec,2)." Sekunden\n";
                        }
                    if (isset($entry["URL"]))
                        {
                        $url=$entry["URL"];
                        if ($step == 0) 
                            {
                            if ($debug) echo "automatedQuery: Öffne Seite $url :\n";
                            $steps[$index]=0;
                            $debugAll=true;
                            }
                        else $debugAll=false;
                        $handler[$index] = $seleniumHandler->getHost($url,$index,$debug);          // öffnen einer url oder umschalten ohne neue url
                        echo "getHost($url,$index,...) erledigt. \n";
                        if (isset($entry["CLASS"]))
                            {
                            // benannte Class öffnen und eine benannte Routine ausführen
                            if ($step == 0) 
                                {
                                if ($debug) 
                                    {
                                    if (isset($entry["CLASS"])) echo "New Class ".$entry["CLASS"]."\n";
                                    else echo "Error, class not defined.\n";
                                    }
                                $runSelenium[$index] = new $entry["CLASS"]($debug);
                                $config=array();
                                if (isset($entry["CONFIG"])) 
                                    {
                                    $config=$entry["CONFIG"];
                                    if (isset($entry["URL"])) $config["URL"]=$entry["URL"];                                    
                                    $runSelenium[$index]->setConfiguration($config);
                                    }
                                }
                            // ------> hier der eigentliche Aufruf des einzelnen Schritts der Webautomatisiserung <--------------
                            $result = $runSelenium[$index]->runAutomatic($steps[$index]);
                            if ($debug) echo "Aufruf ".$steps[$index]." erfolgt. Rückmeldung ist ".json_encode($result)."\n";
                            if (is_Array($result))
                                {       // komplexe Rückmeldung, Ergebnis speichern in den Variablen Username und Nummer
                                if (isset($result["Ergebnis"]))
                                    {
                                    switch (strtoupper($index))
                                        {
                                        case "DREI":
                                            $this->writeResult($index,$result["Ergebnis"],$entry["CONFIG"]["Username"]);            // das Ergebnis ist in der variable mit dem Usernamen, nicht Result !
                                            $this->writeResult($index,$entry["CONFIG"]["Username"],"Nummer");     // letzte Nummer die bearbeitet wurde zusaetzlich abspeichern
                                            break;
                                        case "EASY":
                                            if (isset($result["Depot"]))
                                                {
                                                echo "Easy, alternative storage of Variable : ".$result["Depot"]."\n";
                                                $this->writeResult($index,$result["Ergebnis"],$result["Depot"]);            // das Ergebnis ist in der variable mit dem Usernamen, nicht Result !
                                                }
                                            else                                             
                                                {
                                                $this->writeResult($index,$result["Ergebnis"]);             //Default Name is Result                                        
                                                }
                                            break;    
                                        case "YAHOOFIN":
                                            $ergebnis="";   
                                            //result[Ergebnis] ist json encoded
                                           foreach (json_decode($result["Ergebnis"],true) as $entry)
                                                {
                                                //print_r($entry);
                                                if (isset($entry["Target"])) $ergebnis .= $entry["Short"]."=".$entry["Target"]." ";
                                                } 
                                            if ($logging) $logging->LogNachrichten("Ergebnis $index : $ergebnis.");
                                            $this->writeResult($index,$result["Ergebnis"],"Result",false);             //Default Name is Result, writeResult von dieser class aufgerufen, nicht verwechseln, true für Debug
                                            break;                                             
                                        default:
                                            echo "Result Ergebnis ist definiert ".json_encode($result)."\n";
                                            if (isset($result["Name"])) 
                                                {
                                                $this->writeResult($index,$result["Ergebnis"],$result["Name"]);              // class gibt einen Namen vor
                                                }
                                            else $this->writeResult($index,$result["Ergebnis"]);                                                    // Default Name is Result                                        
                                            break;
                                        }
                                    $steps[$index]++;  
                                    }
                                elseif (isset($result["goto"]))     // goto zum steps überspringen
                                    {
                                    $steps[$index] = $result["goto"];
                                    }
                                }
                            elseif ($result === false)  $failed++;
                            elseif ($result != "retry") $steps[$index]++;
                            }
                        else $failed++;
                        $guthabenHandler->setSeleniumHandler($handler,$webDriverName);                     // nur den Selenium Handler im IP Symcon als Variable abspeichern
                        //print_r($handler);
                        }
                    else echo "Fehler entry Url not set in configuration.".json_encode($entry)."\n";
                    $done++;
                    }
                $step++;
                if ($delay) sleep($delay);
                } while ( ($step < $maxStep) && ($done != $failed) );
            $guthabenHandler->setSeleniumHandler($handler);                                 // nur den Selenium Handler im IP Symcon als Variable abspeichern
            if ($debug)
                {
                echo "-----Handler------------\n";
                print_R($handler);
                echo "-----Title------------\n";
                print_R($seleniumHandler->title);
                }
            }

        }

    function storeString($string)
        {
        return(str_replace("\n","",$string));
        }

    /* findTag
     * rekursive Funktion zum analysieren von html Strings
     * $line, index und Abbruchzähler, nicht mehr als 25 Eintraege
     * $pos, aktuelle Position im String
     * $depth ist der Tiefengliederungszähler
     * Routine sucht nächste Vorkommen von tag, wenn zwischen tag und /tag keine weiteren Tags sind wird dieses tag gespeichert
     * danach wird nach /tag weitergesucht bis das Ende des Strings erreicht wurde oder keine gültige tag Kombi gefunden wurde
     */

    function findTag(&$textfeld,$tag,$pos,&$lines,&$line,$depth,$debug=false)
        {
        $maxlen=strlen($textfeld);
        do {
            if ($line>205) return(false);
            $ident=""; for ($i=0;$i<$depth;$i++) $ident .= "  "; 
            $pos1 = strpos($textfeld,"<$tag",$pos);     // nächster Anfang von tag
            $pos11 = strpos($textfeld,"<$tag",$pos1+1); // übernächster Anfang von tag
            $pos2 = strpos($textfeld,"</$tag",$pos1+1); // nächstes Ende von tag nach dem Anfang
            $pos21 = strpos($textfeld,"</$tag",$pos);   // nächstes Ende von tag
            /* kein Anfang, kein Ende, kein Ende nach einem Anfang gefunden, raus hier !
             * pos21 < pos1 Ende vor Anfang gefunden, eine Ebene heraus
             * pos11 < pos2  noch ein Anfang vor dem Ende, eine Ebene hinein
             */
            if (($pos21===false) || ($pos1===false) || ($pos2===false)) return (false);
            if ($pos21<$pos1) return ($pos21+1);
            if (($pos1-$pos)>0) 
                {
                $lines[$line++]=$ident.$this->storeString(substr($textfeld,$pos,$pos1-$pos));       // innerhtml bis zum ANfang ausgeben
                if ($debug) echo "---$pos ".substr($textfeld,$pos,$pos1-$pos)."\n";
                }
            if ( ($pos11 !== false) && ($pos11 < $pos2) )
                {       // nested zuerst bearbeiten
                $lines[$line++]=$ident.$this->storeString(substr($textfeld,$pos1,$pos11-$pos1));                    // innerhtml vom Anfang bis zum nächsten Anfang ausgeben
                if ($debug) echo "---$pos1 ".substr($textfeld,$pos1,$pos11-$pos1)."\n";
                if (($result = $this->findTag($textfeld,$tag,$pos11,$lines,$line,$depth+1)) === false) return (false);         // return false hier wenn von Funktion kein Anfang, kein Ende, kein Ende nach einem Anfang gefunden wurde
                else $pos=$result;
                } 
            else    // innerhtml tag bis /tag gefunden
                {
                if ($debug) echo "$line|$depth: $pos1 $pos2 ".substr($textfeld,$pos1,$pos2-$pos1+6)."\n";
                $lines[$line++]=$ident.$this->storeString(substr($textfeld,$pos1,$pos2-$pos1+6));
                $pos=$pos2+3+strlen($tag);  // 3 ist </  >
                }
            } while ($pos<$maxlen);
        return ($pos);
        }

    }

/* SeleniumUpdate, nutzt Funktion von Watchdog und OperationCenter Library
 */

class SeleniumUpdate
    {

    /* version sind die verfügbaren versionen als array, actualVersion ist die aktuell installierte version
     * es werden alle version beginnend von actualVersion übernommen, wenn weniger zwei werden zumindest zwei versionen übernommen
     */
    function findTabsfromVersion($version,$actualVersion,$debug=false)
        {
        if ($debug) 
            {
            echo "SeleniumUpdate::findTabsfromVersion aufgerufen.\n";
            print_r($version);
            }
        $tab=array(); 
        if ($version===false) return (false);
        $count = sizeof($version);
        $indexStart=$count-5;                       // ein paar alte Versionen mit suchen
        $start=false;
        $index=0;
        foreach ($version as $num => $entry)
            {
            if ($num===$actualVersion) $start=true;
            if ($index>$indexStart) $start=true;
            if ($start) $tab[]=(string)$num;     
            $index++;    
            }
        //print_R($tab);
        return ($tab);
        }

    /* von den Servern das Environment für das Herunterladen von aktuellen Chromeversionen installieren
     * Ergebnis ist ein html string
     * Erzeugt das Targetdir wenn erforderlich und gibt es im html aus, es werden gleich die Inhalte des targetdirs geladen udn verglichen, um rauszufinden was bereits erledigt ist
     * Benötigt 7zr.exe und 7z2301-extra.7z, wenn nicht vorhanden download 7z2301-extra.7z
     * Benötigt unzip_7za.bat, wenn nicht schreibe das batch file selbst, zerlegt das 7z file
     * Benötigt 7za.exe, wenn nicht wird unzip_7za.bat gestartet um diese Datei durch Aufruf von 7zr.exe zu erhalten
     * als positives Feedback wird berichtet das 7za.exe vorhanden ist.
     */
    function installEnvironment($targetDir,$debug=false)
        {
        $html = "";
        $dosOps = new dosOps();
            $sysOps = new sysOps();
            $curlOps = new curlOps();             

            $dosOps->mkdirtree($targetDir);
            if (is_dir($targetDir)) 
                {
                if ($debug) echo "Verzeichnis für Selenium downloads verfügbar: $targetDir\n";
                }
            else return (false);
            //echo "Zieldatei downloaden und abspeichern: $targetDir\n";
            $html .= "Selenium ChromeDriver Download verzeichnis : $targetDir <br>";                  // das ist das Arbeitsverzeichnis, nicht das Sync drive 
            //echo "Was ist schon alles im Targetverzeichnis gespeichert $targetDir:\n";
            $files = $dosOps->writeDirToArray($targetDir);        // bessere Funktion
            if ($debug) $dosOps->writeDirStat($targetDir);                    // Ausgabe Directory ohne Debug bei writeDirToArray einzustellen
            //print_R($files);

            $filename="7zr.exe";
            $file = $dosOps->findfiles($files,$filename,$debug);       //Debug
            if ($file) 
                {
                if ($debug) echo "   --> Datei $filename gefunden.\n";
                }
            else $curlOps->downloadFile("https://www.7-zip.org/a/7z2301-extra.7z",$targetDir);    

            $filename="7z2301-extra.7z";
            $file = $dosOps->findfiles($files,$filename,$debug);       //Debug
            if ($file) 
                {
                if ($debug) echo "   --> Datei $filename gefunden.\n";
                }
            else $curlOps->downloadFile("https://www.7-zip.org/a/7z2301-extra.7z",$targetDir);    

            $filename="unzip_7za.bat";
            $file = $dosOps->findfiles($files,$filename,$debug);       //Debug
            if ($file) 
                {
                if ($debug) echo "   --> Datei $filename gefunden.\n";
                //$lines = file($dir."unzip_7za.bat");
                //foreach ($lines as $line) echo $line;        
                }
            else
                {
                $filenameProcess = "7z2301-extra.7z";
                echo "Schreibe Batchfile zum automatischen Unzip der 7za Version.\n";
                $handle2=fopen($dir."unzip_7za.bat","w");        
                fwrite($handle2,'echo written '.date("H:m:i d.m.Y")."\r\n");
                $command='7zr.exe x '.$filenameProcess."\r\n";
                fwrite($handle2,$command);
                //$command="pause\r\n";
                //fwrite($handle2,$command);
                fclose($handle2);
                }

            $filename="7za.exe";
            $file = $dosOps->findfiles($files,$filename,$debug);       //Debug
            if ($file) 
                {
                if ($debug) echo "   --> Datei $filename gefunden.\n";
                $html .= "Unzip Programm available : $filename <br>";                  // unzip programm steht zur Verfügung 
                }
            else
                {
                $ergebnis = "not started";
                $commandName="unzip_7za.bat";
                $ergebnis = $sysOps->ExecuteUserCommand($targetDir.$commandName,"",true,true,-1,true);             // parameter show wait -1 debug
                echo "Execute Batch $dir$commandName um File $dir$filename zu erhalten : \"$ergebnis\"\n";
                $html .= "Unzip Programm not available, maybe next time when script is started by pressing GET. <br>";
                }
        return ($html);
        }


    }



?>