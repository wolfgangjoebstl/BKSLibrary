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


    /* $hostIP      'http://10.0.0.34:4444/wd/hub'
     * browser   Chrome
     *
     * es wird eine SessionID übergeben, das ist aber nur die ID der Variable die die SessionID beinhaltet
     * webDriver ist static und steht allen übergeordneten classes zur Verfügung
     * 
     */
    function initHost($hostIP,$browser="Chrome",$sessionID=false, $debug=false)
        {
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
                $this->failure = $e->getMessage();
                $failureShort = substr($this->failure,0,60);
                if ($failureShort=="Curl error thrown fo") echo "Es sieht so aus als wäre Selenium nicht gestartet.\n";
                elseif ($failureShort=="session not created: This version of ChromeDriver only suppo") echo "Neuesten ChromeDriver laden.\n";
                else 
                    {
                    echo "Fehler erkannt :\"$failureShort\"\n";
                    if ($debug) echo "  -->selenium Webdriver mit Session $session nicht gestartet. Noch einmal ohne Session probieren. Fehlermeldung ".$e->getMessage()."\n";
                    }
                $this->handle=array();      // Handle array loeschen
                try { 
                    $webDriver = RemoteWebDriver::create($hostIP, $capabilities); 
                    }
                catch (Exception $e) 
                    { 
                    echo "  -->selenium Webdriver nicht gestartet. Fehlermeldung ".$e->getMessage()." Bitte starten.\n"; 
                    return (false); 
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
                $this->failure = $e->getMessage();
                $failureShort = substr($this->failure,0,60);
                if ($failureShort=="Curl error thrown fo") echo "Es sieht so aus als wäre Selenium nicht gestartet.\n";
                elseif ($failureShort=="session not created: This version of ChromeDriver only suppo") echo "Neuesten ChromeDriver laden.\n";
                else 
                    {
                    echo "Fehler erkannt :\"$failureShort\"\n";
                    if ($debug) echo "  -->selenium Webdriver nicht gestartet. Fehlermeldung ".$e->getMessage()." Bitte starten.\n"; 
                    }
                return (false); 
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
            if($element) {
                $this->title[$this->index++]=static::$webDriver->getTitle();
                $statusEnabled=$element->isEnabled();
                $statusDisplayed=$element->isDisplayed();
                if ($debug) echo "Element Status Enabled $statusEnabled Displayed $statusDisplayed\n";
                if ($statusEnabled)
					{
					if ($statusDisplayed)
						{
						$element->click();
						if ($debug) print "found xpath , click Button\n";
						return (true);                                          // nur hier erfolgreich
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
                if ($debug) echo "   -->found [$count], look for innerHtml.\n";
                $page = $element->getAttribute('innerHTML');
                if (strlen($page)>10) return ($page);
                if ($debug) echo "   -->result too short \"$page\", then try text.\n";
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
            $this->pressButtonIf($xpath);        
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
 *      parseResult
 *      writeResult         die Ergebnisse in spezielle Register schreiben
 *      runAutomatic
 *
 * Funktionen zum Einlesen des Host
 *      getTextValidLoggedInIf
 *      enterLogin
 *      gotoLinkMusterdepot
 *      gotoLinkMusterdepot3
 *      getTablewithCharts
 *
 */
class SeleniumEasycharts extends SeleniumHandler
    {
    protected $configuration;     //array mit Datensaetzen
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
        $this->CategoryIdDataEasy = IPS_GetObjectIdByName("EASY",$this->CategoryIdData);
        }

    function getResultCategory()
        {
        return($this->CategoryIdDataEasy);
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
                    if ($lines[$index+1] != "EUR") 
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

    /* Werte im Data Block speichern */

    function writeResult(&$shares, $nameDepot="MusterDepot",$value=0)
        {
        $componentHandling = new ComponentHandling();           // um Logging zu setzen

        $categoryIdResult = $this->getResultCategory();
        echo "Store the new values, Category Easy RESULT $categoryIdResult.\n";
    
        $share=array();
        $share["ID"]     = "MusterDepot3";
        $share["Parent"] = $categoryIdResult;
        $share["Stueck"] = 1;
        $share["Kurs"]   = $value;
        $shares[]=$share;

        /*function CreateVariableByName($parentID, $name, $type, $profile=false, $ident=false, $position=0, $action=false, $default=false) */
        $oid = CreateVariableByName($categoryIdResult,$share["ID"],2,'Euro',"Depot",1000);
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

    /* Easycharts ermittelt verschiedene darstellbare Ergebnisse
     *
     */

    function writeResultAnalysed($resultShares)
        {
        echo "ID            Name                       StandardAbw  Spread Max/Min        Mittelwert      Trend      Letzter Wert  Result\n";
        foreach ($resultShares as $share)
            {
            echo $share["Info"]["ID"]."  ";
            if (isset($share["Info"]["Name"])) $name=$share["Info"]["Name"];
            else $name="";
            echo str_pad($name,23);
            $spreadPlus  = ($share["Description"]["Max"]/$share["Description"]["Means"]-1)*100;
            $spreadMinus = (1-$share["Description"]["Min"]/$share["Description"]["Means"])*100;
            $description="";
            if (($spreadPlus+$spreadMinus)>5)
                {
                if ($spreadPlus>$spreadMinus) $description="Volatile to high";
                if ($spreadPlus<$spreadMinus) $description="Volatile to low";
                }
            echo str_pad(number_format($share["Description"]["StdDevRel"],2,",",".")."%",12," ", STR_PAD_LEFT);                
            echo str_pad(number_format($spreadPlus,2,",",".")."/".number_format($spreadMinus,2,",",".")."%",18," ", STR_PAD_LEFT);
            echo str_pad(number_format($share["Description"]["Means"],2,",",".")." Euro",18," ", STR_PAD_LEFT);
            if (isset($share["Description"]["Trend"],)) echo str_pad(number_format($share["Description"]["Trend"],2,",",".")."%",12," ", STR_PAD_LEFT);
            else echo "             ";
            echo str_pad(number_format($share["Description"]["Latest"],2,",",".")." Euro",18," ", STR_PAD_LEFT);
            echo "   $description ";
            echo "\n";
            }


        }

    public function runAutomatic($step=0)
        {
        if ($this->debug) echo "runAutomatic SeleniumEasycharts Step $step.\n";
        switch ($step)
            {
            case 0:
                if ($this->debug) echo "--------\n0: check if not logged in, then log in.\n";
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
                $result = $this->gotoLinkMusterdepot();
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 3:
                $result = $this->gotoLinkMusterdepot3();            // zum dritten Musterdepot wechseln
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 4:
                $result = $this->getTablewithCharts();
                if ($result===false) return ("retry");
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                return(["Ergebnis" => $result]);

                break;    // so erfolgt ein stopp
            default:
                return (false);
            }

        }

    /* get text , find out wheter being logged in 
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

    private function gotoLinkMusterdepot()
        {
        /* //*[@id="7_1_link"]
         */
        $xpath='//*[@id="7_1_link"]';
        $status=$this->pressButtonIf($xpath);             
        //if ($status===false) return($this->updateUrl($url));
        return ($status);                    
        }

    private function gotoLinkMusterdepot3()
        {
        /* /html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody/tr[8]/td[1]/a
         */
        $xpath='/html/body/div/div[3]/div/div[2]/table/tbody/tr/td[1]/div[2]/table/tbody/tr[8]/td[1]/a';
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

        if ($debug) 
            {
            echo "automatedQuery: $webDriverName, Aufruf mit Configuration:\n";
            print_R($configTabs);
            $startexec=microtime(true);

            if ( ($sessionID) && ($sessionID != "") ) echo "SessionID in Datenbank verfügbar :".$sessionID." mit Wert \"".GetValue($sessionID)."\"\n";
            if ( ($webDriverName==false) || ($webDriverName=="false") ) $webDriverName2="Default";
            else $webDriverName2=$webDriverName; 
            echo "WebDriver ausgewählt: $webDriverName2 unter $webDriverUrl Browser: ".$configSelenium["Browser"]."  \n"; 
            $webDriverStatusId=IPS_GetObjectIDByName("StatusWebDriver".$webDriverName2,$this->CategoryId);
            echo "Ergebnisse auch hier speichern : $webDriverStatusId \n";           
            }

        /* Handler abgleichen */
        $handler      = $guthabenHandler->getSeleniumHandler($webDriverName);                         // den Selenium Handler vom letzten Mal wieder aus der IP Symcon Variable holen. php kann sich von Script zu Script nix merken
        if ($handler) 
            {
            if ($debug) echo "Handler sind bereits in der Datenbank gespeichert und verfügbar. Sync with Selenium\n";    
            $seleniumHandler->updateHandles($handler);
            }

        /* WebDriver starten */
        $result = $seleniumHandler->initHost($webDriverUrl,$configSelenium["Browser"],$sessionID,$debug);          // ersult sind der Return wert von syncHandles
        if ($result !== false)  
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
        else echo "Kann Webdriver $webDriverUrl nicht finden.\n";
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