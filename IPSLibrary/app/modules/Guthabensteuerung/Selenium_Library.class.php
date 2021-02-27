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

    function syncHandles($handles)
        {
        $result=array();
        foreach ($handles as $entry)    // index ist 0...x
            {
            //echo "look for \"$entry\" in ".json_encode($this->handle)."\n";
            if (isset($this->handle[$entry])) $result[$this->handle[$entry]] = $entry;
            else echo "unknown $entry.\n";
            }
        //print_R($result);
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

    function updateHandles($handle)
        {
        echo "updateHandles, with basic check of validity: $handle\n";
        $input=json_decode($handle);            // handle:  index => entry   Beispiel ORF => CDwindow-342C42117187FA5E15E8A961F380A715
        foreach ($input as $index => $entry)
            {
            if (strlen($entry)>20)      // Enträge für handle kleiner 20 Zeichen ignorieren
                {
                if ((isset($this->handle[$entry])) && ($this->handle[$entry] != $index) ) 
                    {
                    echo "update of $entry needed with $index former was ".$this->handle[$entry].".\n";
                    $this->handle[$entry]=$index;
                    }
                else $this->handle[$entry]=$index;
                }
            }
        print_R($this->handle);
        echo "----------------------------\n";
        }


    /* $hostIP      'http://10.0.0.34:4444/wd/hub'
     * browser   Chrome
     *
     * es wird eine SessionID übergeben, das ist aber nur die ID der Variable die die SessionID beinhaltet
     * webDriver ist static und steht allen übergeordneten classes zur Verfügung
     * 
     */
    function initHost($hostIP,$browser="Chrome",$sessionID=false)
        {
        if ($this->active===false) return;
        if ( (strtoupper($browser))=="CHROME") $capabilities = DesiredCapabilities::chrome();
        if ($sessionID && (GetValue($sessionID)!="") )
            {
            $session=GetValue($sessionID);
            echo "SeleniumHandler::initHost($hostIP,$browser) mit Session $session aufgerufen.\n";
            // static RemoteWebDriver createBySessionID($session_id, $selenium_server_url = 'http://localhost:4444/wd/hub')
            try { 
                $webDriver = RemoteWebDriver::createBySessionID($session, $hostIP);
                $session = $webDriver->getSessionID();
                $handles = $webDriver->getWindowHandles();
                }
            catch (Exception $e) 
                { 
                echo "  -->selenium Webdriver mit Session $session nicht gestartet. Noch einmal ohne Session probieren.\n";
                $this->handle=array();      // Handle array loeschen
                try { 
                    $webDriver = RemoteWebDriver::create($hostIP, $capabilities); 
                    }
                catch (Exception $e) { echo "  -->selenium Webdriver nicht gestartet. Fehlermeldung ".$e->getMessage()." Bitte starten.\n"; return (false); }
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
            catch (Exception $e) { echo "  -->selenium Webdriver nicht gestartet. Fehlermeldung ".$e->getMessage()." Bitte starten.\n"; return (false); }
            }
        if ($sessionID) 
            {
            $session = static::$webDriver->getSessionID();
            SetValue($sessionID,$session);
            echo "  -->selenium Webdriver erfolgreich gestartet. Session: $session\n";
            }
        else echo "  -->selenium Webdriver erfolgreich gestartet.\n";
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

    function getHost($host,$tab=false)
        {
        if ($this->active===false) return;
        echo "getHost für $host und Index $tab:\n";
        print_R($this->handle);
        if  ($tab !== false) 
            {
            $tabHandle=$this->isTab($tab); 
            if ($tabHandle)
                {
                echo "Handle für $host schon bekannt, switchTo $tab ($tabHandle).\n";
                static::$webDriver->switchTo()->window($tabHandle);
                }
            else
                {
                if ( ($this->handle) && ((count($this->handle))>0) )
                    {
                    /* noch einen Tab aufmachen */
                    echo "Handle für $host nicht bekannt, oeffne neuen Tab $tab.\n";                
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
                    echo "   -> $tabHandle\n";                                
                    }
                else 
                    {
                    echo "Handle für $host nicht bekannt, oeffnen, erster Tab $tab.\n";                
                    static::$webDriver->get($host);
                    $tabHandle = static::$webDriver->getWindowHandle();
                    $this->handle[$tabHandle]=$tab;
                    echo "   -> $tabHandle=>$tab\n";
                    }
                }
            }
        else
            {
            echo "getHost $host\n";
            static::$webDriver->get($host);
            $tabHandle="unknown";
            }
        $this->title[$this->index++]=static::$webDriver->getTitle();     
        return ($tabHandle);
        }

    function maximize()
        {
        static::$webDriver->manage()->window()->maximize();            
        }

    /* pressButtonIf
     * sucht einen xpath
     * wenn vorhanden drückt er da darauf den Button
     * webDriver muss bereits angelegt sein 
     */

    function pressButtonIf($xpath)
        {        
        //$element = $webDriver->findElement(WebDriverBy::xpath('/html/body/div[4]/div[2]/div/div/div[2]/div/div/button'));
        echo "pressButtonIf($xpath):\n";
        //$result=$this->webDriver->findElements(WebDriverBy::xpath($xpath)); print_R($result);
        if (count(static::$webDriver->findElements(WebDriverBy::xpath($xpath))) === 0) {
            echo 'Button not found';
            }
        else
            {
            $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));
            if($element) {
                $this->title[$this->index++]=static::$webDriver->getTitle();
                $element->click();
                print "found xpath , click Button\n";
                //print_r($element);
                }
            }
        }

    function loginHost($host,$username,$password)
        {
        if ($this->active===false) return;

        /* Goto Login Page
        * /html/body/div[1]/div/form/div[2]/label[1]/input
        * //*[@id="ssoUsername"]
        */
        $this->webDriver->get($host);
        sleep(3);
        $this->title[$this->index++]=static::$webDriver->getTitle();     
        //$element = $webDriver->findElement(WebDriverBy::id("ssoUsername"));
        $element = static::$webDriver->findElement(WebDriverBy::xpath('//*[@id="ssoUsername"]'));        // relative path
        if ($element) 
            {
            $element->sendKeys($username);              // "06606980204"
            //$element->submit();
            }
        sleep(1);
        $element = static::$webDriver->findElement(WebDriverBy::id("ssoPassword"));
        if($element) {
            $element->sendKeys($password);           //Cloudg0606
            //$element->submit();
        }
        
        sleep(1);
        
        $element = static::$webDriver->findElement(WebDriverBy::id("ssoSubmitButton"));
        if($element) {
            $this->title[$this->index++]=static::$webDriver->getTitle();         
            $element->click();
            print "we are in \n";
            }
        }
        
    function fetchDatafromHost($xpath)
        {   
        if ($this->active===false) return;

        $element = static::$webDriver->findElement(WebDriverBy::xpath($xpath));        // relative path
        if($element) {
            $this->title[$this->index++]=static::$webDriver->getTitle();         
            $page = $element->GetText();
            echo "found fetch data, length is ".strlen($page)."\n";
            print $page;
        }
        
        /*
        print_r($title);
        echo "\nHello World.\n";
        */
        return ($page);
        }

    function quitHost()
        {
        if ($this->active===false) return;

        static::$webDriver->quit();

        }

    }





?>