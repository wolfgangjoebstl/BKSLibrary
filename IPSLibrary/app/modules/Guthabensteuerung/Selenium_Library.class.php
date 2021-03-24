<?



//namespace Facebook\WebDriver;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

//require_once 'vendor/autoload.php';
require_once(IPS_GetKernelDir().'scripts\vendor\autoload.php');

class SeleniumHandler
    {

    protected static   $webDriver;
    protected   $active;

    public      $title;                  /* history of Titels */
    private     $index;             /* actual index of title */

    public      $handle;                /* Id of each Tab */
    private     $tab;

    function __construct($active=true)
        {
        $this->active=$active;   

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
                if ($debug) echo "  -->selenium Webdriver mit Session $session nicht gestartet. Noch einmal ohne Session probieren.\n";
                $this->handle=array();      // Handle array loeschen
                try { 
                    $webDriver = RemoteWebDriver::create($hostIP, $capabilities); 
                    }
                catch (Exception $e) { if ($debug) echo "  -->selenium Webdriver nicht gestartet. Fehlermeldung ".$e->getMessage()." Bitte starten.\n"; return (false); }
                }
            static::$webDriver = $webDriver; 
            }
        else
            {
            echo "SeleniumHandler::initHost($hostIP,$browser) aufgerufen.\n";
            try { 
                $webDriver = RemoteWebDriver::create($hostIP, $capabilities); 
                static::$webDriver = $webDriver; 
                }
            catch (Exception $e) { if ($debug) echo "  -->selenium Webdriver nicht gestartet. Fehlermeldung ".$e->getMessage()." Bitte starten.\n"; return (false); }
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
     * sucht einen xpathund überprüft:
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
                if ($statusDisplayed)
                    {
                    $element->click();
                    if ($debug) print "found xpath , click Button\n";
                    return (true);                                          // nur hier erfolgreich
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
                $page = $element->GetText();
                return ($page);
                }
            }
        return (false);
        }

    function getHtmlIf($xpath,$debug=false)
        {
        if ($debug) echo "getHtmlIf($xpath):\n";            
        if (count(static::$webDriver->findElements(WebDriverBy::xpath($xpath))) === 0) {
            if ($debug) echo "   -->Text Field not found.\n";
            }
        else
            {            
            $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));        // relative path
            if ($element) 
                {
                $page = $element->getAttribute('innerHTML');
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

/* reading and Writing results to IP Symcon Registers */



class SeleniumDrei extends SeleniumHandler
    {
    private $configuration;                 //array mit Datensaetzen
    private $duetime,$retries;              //für retry timer
    protected $debug;

    function __construct($debug=false)
        {
        $this->duetime=microtime(true);
        $this->retries=0;
        $this->debug=$debug;
        }

    public function setConfiguration($configuration)
        {
        $this->configuration = $configuration;
        }

    public function runAutomatic($step=0)
        {
        if ($this->debug) echo "runAutomatic SeleniumDrei Step $step.\n";
        switch ($step)
            {
            case 0:     // beim ersten Mal ist öffnen, das kann länger dauern, zum Init von Timern verwenden
                break;
                $this->duetime=microtime(true)+2;               
            case 1:
                $result=$this->pressPrivacyButtonIf();          // den Privacy Button clicken, wenn er noch da ist, spätestens nach 2 Sekunden aufgeben
                if ( ($result === false) && (microtime(true)<$this->duetime) )
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                $this->duetime=microtime(true)+4;                                    // vielelicht sind manche Server viel langsamer
                break;
            case 2:
                $result=$this->goToLoginLink();
                if ( ($result === false) && (microtime(true)<$this->duetime) )
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                break;
            case 3:
                if ($this->debug) echo "Login in Url \"".$this->getUrl()."\"\n";
                $result = $this->enterLoginIf($this->configuration["Username"],$this->configuration["Password"]);
                if ($result === false) 
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    }
                $this->duetime=microtime(true)+2;           // zumindest 2 Sekunden einräumen
                break;
            case 4:
                /* //*[@id="site-wrapper"]/header/ul[2]/li[6]/ul/li[3]/a */
                $result=$this->goToKostenLink();
                if ( ($result === false) && (microtime(true)<$this->duetime) )
                    {
                    if ($this->debug) echo "   --> failed\n";
                    return("retry");                
                    } 
                else if ($this->debug) echo "  Zeit bis Duetime ".($this->duetime-microtime(true))."\n";
                break;
            case 5:
                $result=$this->fetchGuthabenDatafromHost();
                if ($result===false) return ("retry");
                if ($this->debug) echo "Ergebnis ------------------\n$result\n-----------------\n"; 
                $filename = $this->configuration["Username"].".txt";               
                if (strlen($result)>10) file_put_contents ( $this->configuration["WebResultDirectory"].$filename, $result);
                //$guthabenHandler->parsetxtfile($nummer,$config["WebResultDirectory"],$filename);
                return(["Ergebnis" => $result]);
                break;
            case 6:
                $this->goToLoginLink();                 // zurück zum Login Link für personal Information
                break;
            case 7:
                $this->pressLogoutButtonIf();
                break;
            default:
                return (false);                
            }
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

    /* press link butto, Personenzeichen
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

    /* das Guthaben kann auf zwei verschiedenen Orten stehen. beide probieren
     */

    function fetchGuthabenDatafromHost()
        {   
        if ($this->active===false) return;

        /* guthaben auslesen , immer warten bis sich die Seite aufgebaut hat, da arbeiten langsame Scripts im Hintergrund
        * //*[@id="link_coco_box"]/div/div[1]/div[1]/h1 
        * //*[@id="link_coco_box"]/div/div[2] 
        * //*[@id="link_coco_box"]/div/div[2] 
        *
        *//*[@id="site-wrapper"]/main
        *
        *//*[@id="site-wrapper"]/main/div[3]/div[2]
        */
        $xpath='//*[@id="link_coco_box"]';     
        //$ergebnis = $this->getTextIf($xpath);
        $ergebnis = $this->getHtmlIf($xpath);    
        //if ($ergebnis === false) return (false);          // nicht gleich aufgeben, erst anderen Link probieren
        if ((strlen($ergebnis))>10) return($ergebnis);
        
        $xpath='//*[@id="site-wrapper"]/main';     
        //$ergebnis = $this->getTextIf($xpath);
        $ergebnis = $this->getHtmlIf($xpath);    
        
        return($ergebnis);

        }
    }           // ende class


/* Selenium Webdriver automatisisert bedienen
 *
 *
 */

class SeleniumOperations
    {

    private $guthabenHandler;
    private $CategoryIdData;

    /* Initialisiserung */

    public function __construct()
        {
        $this->guthabenHandler = new GuthabenHandler(true,true,true);         // Steuerung für parsetxtfile
        $categoryId = $this->guthabenHandler->getCategoryIdSelenium();
        $this->CategoryIdData =CreateCategoryByName($categoryId,"RESULT");
        }

    function initResultStorage($configuration)
        {
        if (isset($configuration["Hosts"])) $configuration = $configuration["Hosts"];
        foreach ($configuration as $index => $entry)
            {
            CreateCategoryByName($this->CategoryIdData,$index);
            }
        }

    function getCategory($sub=false)
        {
        if ($sub === false) return ($this->CategoryIdData);
        else
            {
            $categoryID = @IPS_GetObjectIDByName($sub, $this->CategoryIdData);
            return ($categoryID);
            }
        }

    function writeResult($index,$result,$name="Result")
        {
        $categoryID = @IPS_GetObjectIDByName($index, $this->CategoryIdData);
        $variableID = CreateVariableByName($categoryID, $name, 3);
        SetValue($variableID,$result);

        }

    function readResult($index,$name="Result")
        {
        $result=array();
        $categoryID = @IPS_GetObjectIDByName($index, $this->CategoryIdData);
        $variableID = CreateVariableByName($categoryID, $name, 3);
        $result["Value"]=GetValue($variableID);
        $result["LastChanged"]=IPS_GetVariable($variableID)["VariableChanged"];
        return ($result);
        }

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
            echo "WebDriver ausgewählt: $webDriverName unter $webDriverUrl Browser: ".$configSelenium["Browser"]."  \n";            
            }

        /* Handler abgleichen */
        $handler      = $guthabenHandler->getSeleniumHandler($webDriverName);                         // den Selenium Handler vom letzten Mal wieder aus der IP Symcon Variable holen. php kann sich von Script zu Script nix merken
        if ($handler) 
            {
            if ($debug) echo "Handler sind bereits in der Datenbank gespeichert und verfügbar. Sync with Selenium\n";    
            $seleniumHandler->updateHandles($handler);
            }

        /* WebDriver starten */
        $result = $seleniumHandler->initHost($webDriverUrl,$configSelenium["Browser"],$sessionID);          // ersult sind der Return wert von syncHandles
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
                    if ($debug) echo "======================================".(microtime(true)-$startexec)." Sekunden\n";

                    if (isset($entry["URL"]))
                        {
                        $url=$entry["URL"];
                        if ($step == 0) 
                            {
                            if ($debug) echo "Öffne Seite $url :\n";
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
                                $runSelenium[$index] = new $entry["CLASS"]();
                                $config=array();
                                if (isset($entry["CONFIG"])) 
                                    {
                                    $config=$entry["CONFIG"];
                                    $runSelenium[$index]->setConfiguration($config);
                                    }
                                }

                            $result = $runSelenium[$index]->runAutomatic($steps[$index]);
                            if (is_Array($result))
                                {       // komplexe Rückmeldung
                                if (isset($result["Ergebnis"]))
                                    {
                                    $this->writeResult($index,$result["Ergebnis"],$entry["CONFIG"]["Username"]);            // das Ergebnis ist in der variable mit dem Usernamen, nicht Result !
                                    if ($index=="DREI") $this->writeResult($index,$entry["CONFIG"]["Username"],"Nummer");     // Nummer zusaetzlich abspeichern
                                    $steps[$index]++;  
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
        else echo "Kann Webdriver nicht finden.\n";
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