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

/* Selenium Handler
 * Selenium läuft normalerweise am Server und nicht in der vM Ware.
 * wird über die IP ADresse mit einer Portnummer 4444 kontaktiert.
 *
 * für die Automatisiserung der Abfragen gibt es verschieden Klasssen
 *      SeleniumHandler     managed die Handles des Selenium Drivers, offene Tabs, werden von den folgenden Klassen erweitert
 *
 *      SeleniumDrei
 *      SeleniumIiyama
 *      SeleniumEasycharts
 *
 *      SeleniumOperations
 *
 * SeleniumDrei sorgt für die individuellen States und SeleniumOperations für die Statemachine
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
 * syncHandles
 * isTab
 * updateHandles
 * initHost
 * getHost
 * updateUrl
 * getUrl
 * check Url
 * maximize
 * pressButtonIf
 * sendKeysToFormIf
 * getTextIf
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

    /* schreibt $handle */

    function updateHandles($handle, $debug=false)
        {
        if ($debug) echo "updateHandles, with basic check of validity: $handle\n";
        $input=json_decode($handle);            // handle:  index => entry   Beispiel ORF => CDwindow-342C42117187FA5E15E8A961F380A715
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
     * Abhilfe is über ein gestartetes javascrip die Abfrage zu machen. 
     * nachdem ich den queryselector noch nicht bedienen kann wird der ganze body abgeholt und zurück geliefert
     *
        $page = $element->getAttribute('innerHTML');                    // funktioniert in php nicht
        $page = $element->getDomProperty('innerHTML');                // Befehl geht nicht
        $page = static::$webDriver->executeScript('return document.querySelector(".selector").innerHTML');
        $elements = static::$webDriver->findElements(WebDriverBy::xpath($xpath));
        $page = static::$webDriver->executeScript('return arguments[0].innerHtml',$elements);
        print_r($page);
        $children  = $elements->childNodes();
     *
     */

    function getInnerHtml($displayMode=false,$commandEntry=false)
        {
        $page = static::$webDriver->executeScript('return document.body.innerHTML;');
        //$this->analyseHtml($page,$displayMode,$commandEntry);
        return ($page);
        }

    /* SeleniumHandler::analyseHtml ein html analysieren, ist nur eine Orientierungsfunktion, besser DOM verwenden
     * hier werden die bekannten Search Algorithmen unterstützt
     *
     * false DIV :
     * <div style="width: 608px">
     */

    function analyseHtml($page,$displayMode=false,$commandEntry=false)
        {
        $pageLength = strlen($page);
        echo "analyseHtml Size of Input : $pageLength \n";
        //echo $page;
        if ($commandEntry !== false) $commandDisplay=strtoupper($commandEntry);
        else $commandDisplay="UNKNOWN";
        $display = $displayMode;

        $pos=false; $ident=0; $end=false; 
        for ($i = 0; $i < $pageLength; $i++) 
            {
            if ($page[$i]=="<") 
                {
                $pos=$i;
                $ident++;
                }
            if (($page[$i]=="/") && $pos) { $ident--; $end=true; }
            if ((($page[$i]==" ") || ($page[$i]=="\n") || ($page[$i]=="\r") || ($page[$i]==">")) && $pos)
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
                            echo substr($page,$pos,$epos-$pos+1);
                            //for ($p=$pos;$p<=$i;$p++) echo ord($page[$p]).".";
                            echo "  ".strtoupper($command)."\n";
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
     */

    function queryFilterHtml($textfeld,$xPathQuery,$filterAttr,$filterValue,$debug=false)
        {
        $innerHTML="";
        if ($debug) echo "---- queryFilterHtml(...,$xPathQuery,$filterAttr,$filterValue)\n";
        //echo "$textfeld\n";

        //$dom = DOMDocument::loadHTML($textfeld);          // non static forbidden
        $dom = new DOMDocument; 
        $dom->loadHTML($textfeld);
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

        $tmp_doc = new DOMDocument();   
        foreach ($links as $a) 
            {
            //$attribute=$a->getAttribute('@*');  if ($attribute != "") echo $attribute."\n";
            //echo $a->textContent,PHP_EOL;
            $style = $a->getAttribute($filterAttr);
            if ( ($filterValue=="") || ($filterValue=="*") || ($style == $filterValue) )
                {
                if ($debug) echo "   -> found:  ".$a->nodeName." , ".$a->nodeType." $style\n";
                $tmp_doc->appendChild($tmp_doc->importNode($a,true));                 
                //echo $innerHTML;
                }
            }
        $innerHTML .= $tmp_doc->saveHTML();                      
        return ($innerHTML);
        }

    /* den Style als Array rausziehen */

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
     * wenn schon wird das Elemnt abgefragt, zuerst wird das innerhtml versucht und wenn nicht dann die Textfelder
     *
     */
    function getHtmlIf($xpath,$debug=false)
        {
        if ($debug) echo "getHtmlIf($xpath):\n";  
        $count=count(static::$webDriver->findElements(WebDriverBy::xpath($xpath)));
        if ( $count === 0) {                  
            if ($debug) echo "   -->Text Field not found.\n";
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
                if ( (strlen($page)<=10) && ($debug) ) echo "   -->result still too short \"$page\".\n";                
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



/* 
 * Bei LogWien einlogen und die aktuellen Zählerstände auslesen
 * RunAutomatic legt die einzelnen Schritte bis zur Auslesung des täglichen Stromzählerstandes fest
 *
 *
 *  __construct
 *  setConfiguration
 *  writeEnergyValue
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

    /*  Energiewert abspeichern und archivieren
     *
     */
    function writeEnergyValue($value,$name="EnergyCounter")
        {
        $componentHandling = new ComponentHandling();           // um Logging zu setzen
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

   /*  Energiewert auslesen vorbereiten, OID holen
     *
     */
    function getEnergyValueId($name="EnergyCounter")
        {
        $componentHandling = new ComponentHandling();           // um Logging zu setzen
        $categoryIdResult = $this->getResultCategory();
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
                $result = $this->clickSMLinkIf();
                if ($result === false) 
                    {
                    echo "   --> failed, continue nevertheless\n";
                    }
                break;  
            case 7:
                echo "--------\n7: Read Energy Value.\n";             
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
         */
        $xpath = '/html/body/div/main/form/fieldset/section[2]/div[3]/c-textfield/input';              // extra Form username+password
        $this->sendKeysToFormIf($xpath,$password);
        /* press Button Weiter 
         * /html/body/div/main/form/fieldset/section[3]/div[1]/button[2]
         */
        $xpath = '/html/body/div/main/form/fieldset/section[3]/div[1]/button[2]';
        $this->pressButtonIf($xpath,true);          
        }

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
     */

    private function clickSMLinkIf()
        {
        $xpath='/html/body/app-root/app-partner-detail/div/main/div/div/div[2]/div/div/div[3]/div[2]/a';
        $status=$this->pressButtonIf($xpath);             
        return ($status);                    
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
        }

    /* seleniumDrei::runAutomatic
     * function is part of a state machine. State Machine is in upper section, state machine operates on responses
     * SeleniumOperations calls this function
     *
     * die Startseite wird automatisch über die Url aufgerufen, wenn es noch keine Session gibt, wenn eine Session offen ist, wird dort weitergemacht
     * (1) Aufruf Startseite www.drei.at
     * (2) Logout wenn erforderlich
     * (2) Privacy Button wegclicken
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

/* Bei Easycharts einlogen und die aktuellen Portfolio Kurse auslesen
 * zusaetzliche Funktionen sind
 *      __construct
 *      getResultCategory
 *      getEasychartConfiguration
 *      parseResult
 *      evaluateResult
 *      evaluateValue
 *      writeResult         die Ergebnisse in spezielle Register schreiben
 *      writeResultConfiguration
 *      getResultConfiguration
 *      writeResultAnalysed
 *      calcOrderBook
 *      runAutomatic
 *
 * Funktionen zum Einlesen des Host
 *      getTextValidLoggedInIf
 *      enterLogin
 *      gotoLinkMusterdepot
 *      readTableMusterdepot
 *      gotoLinkMusterdepot3
 *      getTablewithCharts
 *
 */
class SeleniumEasycharts extends SeleniumHandler
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
        $this->CategoryIdDataEasy = IPS_GetObjectIdByName("EASY",$this->CategoryIdData);
        }

    /* Kapselung der internen Daten und Konfigurationen */

    function getResultCategory()
        {
        return($this->CategoryIdDataEasy);
        }

    function getEasychartConfiguration()
        {
        return (get_EasychartConfiguration());
        }

   /* extract table, function to get fixed elements of a table line per line     
     * input ist array mit Eintraegen pro Spalte (umgewandelt aus dem resultat mit zeienumbrüchen)
     * hier sind die Formatierungsanweiseungen: 
     *      start   die ersten zwei Zeilen überspringen
     *      columns es gibt 11 Spalten
     *
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
                $column = ($count-$start)%$columns;         // die Start Zielen werden nicht mitgezählt
                if ($column==0) 
                    {
                    if ($debug) echo "\n";                  // 0 ist der erste Eintrag, letzten Eintrag mit neuer Zeile abschliessen
                    $zeile++;
                    $spalte=0;
                    }
                else $spalte++;
                if ($column==2) 
                    {
                    if ( ($lines[$index+1] != "EUR") && ($lines[$index+1] != "USD") )
                        {
                        $join=strlen($line);
                        }
                    else $join=0;
                    }
                if ($column==11)
                    {
                    if ($debug) echo "*";    
                    }
                if ($join && ($column==2) )
                    {
                    if ($debug) echo $line;
                    $data["Data"][$zeile][$spalte]=$line;                
                    }
                else 
                    {
                    if ($join) 
                        {
                        if ($debug) echo str_pad($line,32-$join-3).str_pad($join,2)."|";
                        $length += $join;
                        $join=0;
                        $count--; $spalte--;
                        $data["Data"][$zeile][$spalte].=$line;
                        }
                    else 
                        {
                        if ( ($spalte==4) || ($spalte==8) || ($spalte==10) || ($spalte==12))                        //Split
                            {
                            $entries=explode(" ",$line);
                            if (sizeof($entries)>1)
                                {
                                if ($debug) echo str_pad($entries[0],31)."|";
                                $data["Data"][$zeile][$spalte]=$entries[0];
                                $length=strlen($entries[0]);
                                if (isset($data["Size"][$spalte]))
                                    {
                                    if ($length>$data["Size"][$spalte]) $data["Size"][$spalte]=$length;
                                    }
                                else $data["Size"][$spalte]=$length;
                                $length=strlen($entries[1]);
                                $line=$entries[1];
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


    /* evaluate table, function to calculate the value of the depot
     *
     */

    function evaluateResult($data, $debug=false)
        {
        //print_R($data["Data"]);
        /* rebuild table */
        $shares=array();
        $count=0;
        foreach ($data["Data"] as $lineNumber => $line)
            {
            foreach ($line as $columnNumber => $column)
                {
                echo str_pad($column,$data["Size"][$columnNumber])."|";
                if ($columnNumber==0) $shares[$count]["ID"]=$column;
                if ($columnNumber==2) $shares[$count]["Name"]=$column;
                if ($columnNumber==5) $shares[$count]["Stueck"]=floatval(str_replace(',', '.', str_replace('.', '', $column)));
                if ($columnNumber==6) $shares[$count]["Kurs"]=floatval(str_replace(',', '.', str_replace('.', '', $column)));
                if ($columnNumber==8) $shares[$count]["Kursaenderung"]=floatval(str_replace(',', '.', str_replace('.', '', $column)));
                }
            $count++;
            echo "\n";

            }

        echo "\n";
        $sortTable="Kursaenderung";
        $this->ipsOps->intelliSort($shares,$sortTable,SORT_DESC);    
        //print_r($shares);
        echo "\n";
        return($shares);
        }

    /* Summe der Aktienwerte ausrechnen und in der richtigen Reihenfolge darstellen */

    public function evaluateValue($shares)
        {
        $value=0;

        foreach ($shares as $index => $entry)
            {
            echo str_pad($entry["ID"],14).str_pad($entry["Name"],31).str_pad($entry["Stueck"],8," ", STR_PAD_LEFT).str_pad(number_format($entry["Kurs"],2,",","."),12," ", STR_PAD_LEFT).str_pad(number_format($entry["Kursaenderung"],3,",",".")."%",9," ", STR_PAD_LEFT)." ";
            $shareValue=$entry["Stueck"]*$entry["Kurs"];
            echo str_pad(number_format($shareValue,2,",",".")." Euro",18," ", STR_PAD_LEFT)."\n";
            $value += $shareValue;
            }
        echo "------------------------------------------------------------------------------------------------\n";
        echo "                                                                             ".str_pad(number_format($value,2,",",".")." Euro",18," ", STR_PAD_LEFT)."   \n";
        return ($value);            
        }

    /* EASY, Werte im Data Block speichern */

    function writeResult(&$shares, $nameDepot="MusterDepot",$value=0)
        {
        $componentHandling = new ComponentHandling();           // um Logging zu setzen

        $categoryIdResult = $this->getResultCategory();
        echo "Store the new values, Category Easy RESULT $categoryIdResult.\n";
    
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
            }
        }


    /* Easycharts speichert die Konfiguration des Musterdepots
     * etwas kompliziert. Es gibt ein Musterdepot in dem alle Werte gespeichert sind
     * zusätzlich wird die Konfiguration für ein Musterdepot abgespeichert. Also welche ID, Name und wieviel Stück.
     * es können so auch neue Zusammensetzungen mit anderen Aktien kreiert werden
     *
     * Inputformat:  ohne Key als Index
     * Writefromat:  Key ist der Börsenname "USxxx"
     *
     * Folgende Felder werden übernommen:
     *  OID, Name (optional), Stueck, Kosten (optional)
     *
     */

    function writeResultConfiguration(&$shares, $nameDepot="MusterDepot")
        {
        $categoryIdResult = $this->getResultCategory();
        $oid = CreateVariableByName($categoryIdResult,"Config".$nameDepot,3);
        $config=array();
        foreach ($shares as $share)
            {
            $config[$share["ID"]]["OID"]=$share["OID"];
            $config[$share["ID"]]["Stueck"]=$share["Stueck"];
            if (isset($share["Kosten"])) $config[$share["ID"]]["Kosten"]=$share["Kosten"];
            if (isset($share["Name"])) $config[$share["ID"]]["Name"]=$share["Name"];
            }
        SetValue($oid,json_encode($config));
        echo "Konfiguration Depot $nameDepot: ".GetValue($oid)."\n";
        }

    /* Easycharts speichert die Konfiguration des Musterdepots
     * die Konfiguration für ein Musterdepot wieder abrufen
     *
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
            $oid = IPS_GetObjectIdByName("Config".$nameDepot,$categoryIdResult);            // entweder den Namen suchen
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


    /* Easycharts writeResultAnalysed analysiert und ermittelt verschiedene darstellbare Ergebnisse
     * zwei Darstellungsformen, echo Text Tabelle oder html formatierte Tabelle
     * Bei html kann man sich eine erweiterte Tabell für die Darstellung unter den Reports aussuchen
     *
     * Mit Size werden verschiedene Darstellungsgroessen und Detailierungen für die html Tabelle ausgewählt
     *  -x  Limitierung der Zeilen 
     *   0  einfache Tabelle, geeignet für halb- oder viertelseitige Darstellungen
     *   1  zusätzliche informationen
     *   2  erweiterte Darstellung mit mehr Fokus auf Win/Loss des eigenen Depots, Monats/wochen/Tagestrend
     *
     * Wie der Name schon sagt gibt es verschiedene Analyse Funktionen für die Visualisiserung
     *          Anzeige des Tageskurses mit Rotem oder grünen Hintergrund
     *              Positiver  Spread (Abstand Max zu Mittelwert) größer 10% und Tageskurs > 97% vom Maxwert
     *              Negativer Spread (Abstand Min zu Mittelwert) größer 10% und Tageskurs < 97% vom Minwert
     *          Bearbeitung der Description, letze Spalte in der Tabelle
     *
     */

    function writeResultAnalysed($resultShares,$html=false, $size=0,$debug=false)
        {
        $table=false; $wert = "";
        $sort="Change";
        $lines=false; $countLines=0;
        if (is_array($size)) echo "Angabe er Konfiguration mit einem Array. Umwandeln.\n";
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
            $wert .= '<font size="1" face="Courier New" ><table id="easycharts"><tr><td>ID/Name</td><td>Standard<br>Abw</td><td>Spread Max/Min</td><td>';
            if ($size>0) $wert .= '++/--</td><td>Min</td><td>Max</td><td>Order</td><td>Win/Loss</td><td>';
            else $wert .= 'Mittelwert</td><td>';
            if ($size>1) $wert .= 'Letzter Wert</td><td>Month Trend</td><td>Week Trend</td><td>Day Change</td><td>Recommendation</td></tr>';
            else $wert .= 'Letzter Wert</td><td>Week Trend</td><td>Day Change</td><td>Recommendation</td></tr>';
            }
        else echo "ID            Name                       StandardAbw  Spread Max/Min        Mittelwert      Trend      Letzter Wert  Result\n";

        $sortTable=array();
        foreach ($resultShares as $index => $share)
            {
            $sortTable[$index] = $share["Description"]["Change"];
            }
        arsort($sortTable);
        //print_R($sortTable);
        //echo "Anzahl Zeilen : ".count($sortTable)." \n";
        
        /* entsprechend der Sortierung die Tabelle anzeigen 
         * Rating Engine:
         *          stdDev<5
         *
         */
        foreach ($sortTable as $index => $line)
            {
            $rating=0;
            if (isset($resultShares[$index]["Info"]["Name"])) $name=$resultShares[$index]["Info"]["Name"];
            else $name="";
            if ($resultShares[$index]["Description"]["StdDevRel"]<5) 
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
            if (($resultShares[$index]["Description"]["StdDevPos"]+$resultShares[$index]["Description"]["StdDevNeg"])>5)           // 5% in welchem Zeitraum, bei Report der eingestellten Dauer, zB 3 Monate, sonst alle
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
                $result=$this->calcOrderBook($resultShares[$index]["Order"]);
                if ($result["pcs"]>0)
                    {
                    if (($resultShares[$index]["Description"]["Latest"]*$targetSell)<$resultShares[$index]["Description"]["Interval"]["Full"]["Max"]) 
                        {
                        if ($trendPerc < (-10)) $description .= " Sell!";
                        elseif ($trendPerc < (-5)) 
                            {
                            if (($resultShares[$index]["Description"]["Latest"]*$targetSellNow)<$resultShares[$index]["Description"]["Interval"]["Full"]["Max"]) $description .= " Sell";
                            else $description .= " Hold";
                            }
                        else $description .= " Buy";
                        }
                    if ($resultShares[$index]["Description"]["Latest"]>($resultShares[$index]["Description"]["Interval"]["Full"]["Min"]*$targetSell)) $description .= " Sell";
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
                $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["StdDevRel"],2,",",".")."%".'</td>';
                //$zeile .= '<td>'.number_format($spreadPlus,2,",",".")."/".number_format($spreadMinus,2,",",".")."%".'</td>';                   // nur Prozent PosMax/negMax zu Mittelwert ist uns zu wenig
                $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["StdDevPos"],2,",",".")."/".number_format($resultShares[$index]["Description"]["StdDevNeg"],2,",",".")."%".'</td>';
                if ($size>0)            // erweiterte Darstellung
                    {
                    $zeile .= '<td>'.$resultShares[$index]["Description"]["CountPos"]."/".$resultShares[$index]["Description"]["CountNeg"].'</td>';
                    $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["Interval"]["Full"]["Min"],2,",",".")."€".'</td>';
                    $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["Interval"]["Full"]["Max"],2,",",".")."€".'</td>';
                    $order=""; $winLoss="";
                    if (isset($resultShares[$index]["Order"])) 
                        {
                        $orderBook=$resultShares[$index]["Order"];
                        if ($result["pcs"]>0)
                            {
                            $winLoss = ($resultShares[$index]["Description"]["Latest"]/($result["cost"]/$result["pcs"])-1)*100;   // in Prozent
                            $winLoss = number_format($winLoss,2,",",".")."%";
                            }
                        $orderEntry=$orderBook[array_key_last($orderBook)];
                        //echo "Letzte Veränderung, Order Book:\n";  print_R($orderEntry);
                        if (isset($orderEntry["price"])) 
                            {
                            if ($orderEntry["pcs"]<0) $order ="s ";
                            else $order ="b ";
                            $order .= number_format($orderEntry["price"],2,",",".")."€";
                            }
                        }
                    $zeile .= '<td>'.$order.'</td>'; 
                    $zeile .= '<td>'.$winLoss.'</td>';   
                    }
                else $zeile .= '<td>'.number_format($resultShares[$index]["Description"]["Means"],2,",",".")."€".'</td>';

                // Darstellung letzter Wert, also der aktuelle Börsenkurs
                $bgcolor="";
                if ( ($spreadPlus >10) && ($resultShares[$index]["Description"]["Latest"]>(0.97*$resultShares[$index]["Description"]["Interval"]["Full"]["Max"])) ) $bgcolor='bgcolor="green"';
                if ( ($spreadMinus>10) && ($resultShares[$index]["Description"]["Latest"]<(1.03*$resultShares[$index]["Description"]["Interval"]["Full"]["Min"])) ) $bgcolor='bgcolor="red"';
                $zeile .= '<td '.$bgcolor.'>'.number_format($resultShares[$index]["Description"]["Latest"],2,",",".")."€".'</td>';
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
                echo str_pad(number_format($resultShares[$index]["Description"]["Latest"],2,",",".")." Euro",18," ", STR_PAD_LEFT);
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

    /* Orderbook Evaluation of a single share
     * Structure is index date with entries pcs and price
     * Result is amount of pcs and cost/price
     *
     */

    public function calcOrderBook($orderbook,$debug=false)
        {
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

    /* Depotbook create from Orderbook
     * verwendet calcOrderbook um die Summe cost und pcs zu bekommen und nicht die Kosten und Stück aus der Depot konfiguration
     *
     */

    public function createDepotBook(&$orderbook,$resultShares, $debug=false)
        {
        $spend=0; $actual=0;
        if ($debug)
            {
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
     * Depotbook wird um zusätzliche Daten erweitert
     *
     */

    public function evaluateDepotBook(&$depotbook,$resultShares, $debug=false)
        {
        $spend=0; $actual=0;
        if ($debug)
            {
            echo "\n";
            echo "evaluateDepotBook:\n";
            echo str_pad("",105)."|           Geld         Geld Acc         Wert         Gewinn\n";
            }
        $countMax=false;

        $valuebook=array();
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
                    $valuebook[$indexTimeDay]["TimeStamp"]=$wert["TimeStamp"];
                    $valuebook[$indexTimeDay]["Value"] = 0;
                    if (isset($valuebook[$indexTimeDay]["ID"])) 
                        {
                        $pos1=strpos($valuebook[$indexTimeDay]["ID"],$id);
                        if ($pos1 !==false) echo "doppelter Eintrag für ID $id am $indexTimeDay !\n";
                        else $valuebook[$indexTimeDay]["ID"] .= " ".$id;
                        }
                    else 
                        {
                        $valuebook[$indexTimeDay]["ID"] = $id;
                        }

                    }
                }
            }
        ksort($valuebook);
        $depotbook["Value"]=$valuebook;
        $countMax=36;
        //echo "\n";

        $knownValues=array();
        foreach ($depotbook as $id => $book)
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
                //print_R($book);
                if ($book["Stueck"]>0)                // unwahrscheinlich, dass keine Stueck hier vorkommen
                    {
                    $kursKauf=$book["Kosten"]/$book["Stueck"];
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

                }
            else echo "evaluateDepotBook, Fehler Eintrag in Depotbook aber nicht in resultshares.\n";
            }
        if ($debug) 
            {
            echo "Gesamtergebnis Depot ist Kosten ".nf($spend,"€")." und Wert ".nf($actual,"€")."\n";
            }

        return(true);
        }



    /* hier wird Schritt für Schritt das Automatische Abfragen der Easychart Webseite abgearbeitet
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
            $page=$this->getInnerHtml(false,"DIV");
            $debugVariable=CreateVariableByName($this->getResultCategory(),"Debug",3);
            SetValue($debugVariable,$page);
            $innerHtml = $this->queryFilterHtml($page,'//div[@*]','style',"width: 608px",$this->debug);
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
 *  initResultStorage
 *  getCategory
 *  writeResult             allgemeine Schreibroutine
 *  readResult              allgemeine Leseroutine
 *  automatedQuery
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

    /* speichern von Ergebnissen bei der Auslesung von Webinhalten
     * Ort ist data.Guthabensteuerung.Selenium.RESULT (CategoryIdData)
     * unter index ist bereits eine Unterkategorie vorhanden, siehe Index von der Abfrage Konfiguration EASY, ORF etc.
     * dort wird eine neue Stringvariable mit dem Namen result erstellt
     * dort hin wird das Ergebnis in result gespeichert
     */

    function writeResult($index,$result,$name="Result", $debug=false)
        {
        echo "SeleniumOperations::writeResult($index,..,$name).\n";
        $categoryID = @IPS_GetObjectIDByName($index, $this->CategoryIdData);
        if ($categoryID===false) return(false);
        $variableID = CreateVariableByName($categoryID, $name, 3);
        SetValue($variableID,$result);

        }

    function readResult($index=false,$name="Result", $debug=false)
        {
        $result=array();
        if ($index==false) 
            {
            if ($debug) echo "SeleniumOperations::readResult ohne Zielangabe aufgerufen.\n";
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

 
    /* alle Homepages gleichzeitig abfragen 
     * Index von Configtabs gibt die einzelnen Seiten/Tabs vor
     * mit Url wird der Tab benannt und wieder erkannt, wenn keine Class definiert ist, bleibts auch beim Seitenaufruf
     * sonst wird im Step 0 die class initialisiert
     * es gibt einen gesamten Step counter step und einen individuellen steps für die einzelnen Webserver, steps wird im step 0 initaialisisert
     * in jedem Step wird zwischen allen konfigurierten Fenster umgeschalten und entsprechend dem individuellen Stepcounter weitergemacht
     * im array handler sind die Fenster gespeichert, in runSelenium die einzelnen Klassen, in steps der individuelle Schritt
     * wenn als Antwort retry zurückkommt wird im selben Schritt geblieben, sonst um eins weitergezählt
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
                        if (isset($entry["CLASS"]))
                            {
                            // benannte Class öffnen und eine benannte Routine ausführen
                            if ($step == 0) 
                                {
                                $runSelenium[$index] = new $entry["CLASS"]($debug);
                                $config=array();
                                if (isset($entry["CONFIG"])) 
                                    {
                                    $config=$entry["CONFIG"];
                                    if (isset($entry["URL"])) $config["URL"]=$entry["URL"];                                    
                                    $runSelenium[$index]->setConfiguration($config);
                                    }
                                }

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
                                        default:
                                            $this->writeResult($index,$result["Ergebnis"]);             //Default Name is Result                                        
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






?>