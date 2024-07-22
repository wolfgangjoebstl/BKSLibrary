<?php

    /*
	 * @defgroup Startpage Copy Files
	 *
	 * Script zur Ansteuerung der Startpage, Kopiert die Bilddateien in das Webfront
	 *
	 *
	 * @file          Startpage_copyfiles.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
    */


    /*
    * wie der Name startpage_copyfiles schon sagt die Dateien aus dem Dropbox/Synology Drive Laufwerk in die Webfront Umgebung kopieren
    * aus Sicherheitsgründen dürfen die Browser nicht extern zugreifen
    * Ab IPS 7 hat sich das Zielverzeichnis von webfront/user auf /user geändert
    *
    * wird alle 28.800 Sekunden aufgerufen - also alle 8 Stunden
    *
    * Kopiert wird:
    *       Bilder
    *       Image
    *       Icons
    *
    * Zusätzlich wird die im Werkzeugschlüssel dargestellte Tabelle upgedated
    *
    */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");

    IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
    IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
    IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    $moduleManager    = new ModuleManagerIPS7('Startpage',$repository);    
    $installedModules = $moduleManager->GetInstalledModules();

    /****************************************
    *
    *  INITIALISIERUNG
    *
    *
    *  Bilderverzeichnis initialisieren 
    *  file:///C|/Users/Wolfgang/Dropbox/Privat/IP-Symcon/pictures/07340IMG_1215.jpg
    *
    *****************************/
 
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');

    $startpage = new StartpageHandler();         
   	$configuration = $startpage->getStartpageConfiguration();
	$bilderverzeichnis=$configuration["Directories"]["Pictures"];
	$imageverzeichnis=$configuration["Directories"]["Images"];
	$iconverzeichnis=$configuration["Directories"]["Icons"];

    $dosOps= new dosOps();
    $ipsTables = new ipsTables();               // fertige Routinen für eine Tabelle in der HMLBox verwenden

    /* Zielverzeichnis für die Startpage Bilder erstellen */
   	$picturedir = $startpage->picturedir;
	$dosOps->mkdirtree($picturedir);

   	$imagedirM = $startpage->imagedir."/mond/";
	$dosOps->mkdirtree($imagedirM);
   	$imagedirMT = $startpage->imagedir."/mondtransparent/";
	$dosOps->mkdirtree($imagedirMT);

    $icondir = $startpage->icondir."/";
	$dosOps->mkdirtree($icondir);
    $iconStartdir = $icondir."Start/";
	$dosOps->mkdirtree($icondir);
    $iconClockdir = $iconStartdir."clock/";
	$dosOps->mkdirtree($iconClockdir);
    $iconWeatherdir = $iconStartdir."weather/";         // Zielverzeichnis
	$dosOps->mkdirtree($iconWeatherdir);

	//$picturedir=IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\pictures\\";
    //echo "Zum Vergleich $picturedir   und $check \n";


    /***************************************************************************************************
    *
    *  Bilderverzeichnis initialisieren 
    *  file:///C|/Users/Wolfgang/Dropbox/Privat/IP-Symcon/pictures/07340IMG_1215.jpg
    *
    *******************************/

    $dosOps->copyIfNeeded($iconverzeichnis."/",$icondir);           // true debug

    $iconStartVerzeichnis = $iconverzeichnis."/start/";
    $dosOps->copyIfNeeded($iconStartVerzeichnis,$iconStartdir);           // true debug

    $iconClockVerzeichnis = $iconverzeichnis."/clock/";
    $dosOps->copyIfNeeded($iconClockVerzeichnis,$iconClockdir);           // true debug

    //$iconWeatherVerzeichnis = $iconverzeichnis."/Weather/gross/";           // Quellverzeichnis
    $iconWeatherVerzeichnis = $iconverzeichnis."/Weather/";           // Quellverzeichnis für kleine Wettericons
    $dosOps->copyIfNeeded($iconWeatherVerzeichnis,$iconWeatherdir);           // true debug

    $imageverzeichnisMond = $imageverzeichnis."/mond/";                       // mond und mondtransparent
    $dosOps->copyIfNeeded($imageverzeichnisMond,$imagedirM);           // true debug

    $imageverzeichnisMondTransparent = $imageverzeichnis."/mondtransparent/";                       // mond und mondtransparent
    $dosOps->copyIfNeeded($imageverzeichnisMondTransparent,$imagedirMT);           // true debug

    $dosOps->copyIfNeeded($bilderverzeichnis,$picturedir);           // true debug

    //if (false)          // Update SQL Table
        {
        if (isset($installedModules["EvaluateHardware"]))
            {
            IPSUtils_Include ('MySQL_Library.inc.php', 'IPSLibrary::app::modules::EvaluateHardware');
            $moduleManagerEH    = new ModuleManagerIPS7('EvaluateHardware',$repository);
            $CategoryIdDataEH   = $moduleManagerEH->GetModuleCategoryID('data'); 
            $categoryId_BrowserCookies = getCategoryIdByName($CategoryIdDataEH, "WebfrontCookies");                  // Achtung false ist wie 0
            $webbrowserCookiesTableID  = getVariableIDByName($categoryId_BrowserCookies, "BrowserCookieTable");
            if ($webbrowserCookiesTableID)
                {
                /* MySQL Database von Synology verfügbar. Modul dazu installiert:
                *   https://github.com/demel42/IPSymconMySQL/blob/master/README.md
                */

                $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
                $oidResult = $modulhandling->getInstances('MySQL');
                if (sizeof($oidResult)>0) 
                    {
                    $oid=$oidResult[0];           // ersten treffer new_checkbox_tree_get_multi_selection
                    echo "sqlHandle: new $oid (".IPS_GetName($oid).") for MySQL Database found.\n";
                    $status=IPS_GetInstance($oid)["InstanceStatus"];
                    if ($status != 102) echo "Instanz Konfiguration noch nicht abgeschlossen, oder Instanz fehlerhaft. Status is $status.\n";
                    echo "-------------------\n";            
                    //$sqlHandle = new sqlHandle(false,true);           // default MySQL Instanz mit Debug
                    //if ($sqlHandle->available === false) echo "\nWarning, no SQL Handle available.\n";    
                    $full=false;
                    $tableArray = false;
                    $sqlHandle = new sqlHandle(false);           // false, default MySQL Instanz, true debug
                    if ($sqlHandle->available !==false)
                        {
                        $sqlHandle->useDatabase("ipsymcon");    // USE DATABASE ipsymcon
                        $targetTable = "webfrontAccess";        // Zugriff mit Webfront Share Cookies

                        //$tables=array("webfrontAccess"=>"eventName");
                        $tables=array("webfrontAccess"=>true);
                        echo "Show from all of these tables ".json_encode($tables)." the content:\n";
                        foreach ($tables as $table => $active)
                            {
                            if ($active !== false)
                                {
                                echo "<br>\n---------------------------------------------------------------------------------<br>\n";
                                echo "Echo Values from MariaDB Database $table:<br>\n"; 
                                if ($active !=1) $sql = "SELECT * FROM $table WHERE nameOfID='".$action."' AND eventName='TopologyReceiver' ORDER BY $active;";
                                else $sql = "SELECT * FROM $table;";
                                echo "$sql<br>\n";
                                $result1=$sqlHandle->query($sql);
                                $tableArray = $result1->fetchSelect();
                                $result1->result->close();                      // erst am Ende, sonst ist mysqli_result bereits weg !
                                }
                            }
                        }
                    $config=array();
                    $config["html"]    = true;
                    $config["insert"]["Header"]    = true;
                    $config["sort"] = "nameOfID";
                    $html = $ipsTables->showTable($tableArray, false ,$config,false);                // true Debug
                    SetValue($webbrowserCookiesTableID,$html);
                    //print_r($config);
                    }
                else echo "no SQL Instanz implementiert.\n";
                }
            }
        }



if (false)
	{

	/**************************************************************************************************************************
	 *
	 *   Netplayer, derzeit deaktiviert
	 *
	 */
  
	include_once IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\NetPlayer\NetPlayer.inc.php";

	NetPlayer_Power(true);
	$value=0;   /* MP3 Player */
	$value=1;   /* Radio */
	NetPlayer_SetSource($value);
	
	/* einmal aufrufen, damit Radiosenderliste übernommen wird */
	NetPlayer_NavigateRadioForward(NP_COUNT_RADIOVARIABLE);
	
	$radioName = array();
	$profileData   = IPS_GetVariableProfile('NetPlayer_RadioList');
	$associations  = $profileData['Associations'];
	foreach ($associations as $idx=>$association)
		{
		echo "Radiosender : ".$idx." ".$association['Name']."\n";
		//print_r($association);
	   if ($association['Value']==$value)
			{
	      $radioName = $association['Name'];
	   	}
	   }
	$radioList = NetPlayer_GetRadioList();
	$radioUrl  = $radioList[$radioName];
	NetPlayer_PlayRadio($radioUrl, $radioName);
	echo "Es spielt jetzt ".$radioName." von ".$radioUrl."\n";
	}
//NetPlayer_Power(false);

if (false)      // siehe dosOps dorthin übernommen
	{
	/**************************************************************************************************************************
	 *
	 *   praktische Functions, so ähnlich auch in AllgemeineDefinitionen
	 *
	 */

    /* Verzeichnis erstellen oder Dateien aus dem Verzeichnis einlesen */

    function readSourceDir($bilderverzeichnis,$debug=false)
        {
        $dosOps= new dosOps();
        $file=array();
        if ( is_dir ( $bilderverzeichnis ) )
            {
            $handle=opendir ($bilderverzeichnis);
            $i=0;
            while ( false !== ($datei = readdir ($handle)) )
                {
                if ($debug)
                    {
                    if (is_dir($bilderverzeichnis.$datei)) echo "Dir  | $datei\n";
                    else echo "File | $datei\n";
                    }
                if ( ($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($bilderverzeichnis.$datei) == false) )  
                    {
                    $i++;
                    $file[$i]=$datei;
                    }
                }
            closedir($handle);
            //print_r($file);
            }/* ende if isdir */
        else
            {
            echo "Kein Verzeichnis mit dem Namen \"".$bilderverzeichnis."\" vorhanden.\n";
            $dirstruct=explode("/",$bilderverzeichnis);
            //print_r($dirstruct);
            $directoryPath="";
            foreach ($dirstruct as $directory)
                {
                $directoryOK=$directoryPath;
                $directoryPath.=$directory."/";
                if ( is_dir ( $directoryPath ) ) {;}
                else
                    {
                    if ($directory !=="") echo "Error : ".$directory." is not in ".$directoryOK."\n";
                    }
                //echo $directoryPath."\n";
                } 
            $dosOps->mkdirtree($bilderverzeichnis);	
            }
        return ($file);
        }

    /* read dir to check */
    function readDirtoCheck($picturedir)
        {
        $check=array();
        $handle=opendir ($picturedir);
        while ( false !== ($datei = readdir ($handle)) )
            {
            if (($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($picturedir.$datei) == false)) 
                {
                $check[$datei]=true;
                }
            }
        closedir($handle);
        return ($check);
        }

    function copyIfNeeded($bilderverzeichnis,$picturedir,$debug=false)
        {
        $dosOps=new dosOps();    
        $bilderverzeichnis = $dosOps->correctDirName($bilderverzeichnis); 
        
        $bilderverzeichnis = str_replace(['\\','//','\\\\','\/'],'/',$bilderverzeichnis);
        $picturedir = str_replace(['\\','//','\\\\','\/'],'/',$picturedir);

        $file = readSourceDir($bilderverzeichnis);
        echo "Insgesamt ".count($file)." Files aus dem Verzeichnis $bilderverzeichnis eingelesen, wenn notwendig nach $picturedir synchronisieren:\n";
        //print_R($file);
        $check = readDirtoCheck($picturedir);

        foreach ($file as $filename)
            {
            if (isset($check[$filename]))
                {
                $check[$filename]=false;
                if ($debug) echo "Datei ".$filename." in beiden Verzeichnissen. nichts tun\n";
                }
            elseif ( is_file($bilderverzeichnis.$filename)==true )
                {	
                echo "   copy ".$bilderverzeichnis.$filename." nach ".$picturedir.$filename." \n";	
                copy($bilderverzeichnis.$filename,$picturedir.$filename);
                }
            }

        //if ($debug) echo "Verzeichnis für Anzeige auf Startpage synchronisieren:\n";	
        $i=0;
        foreach ($check as $filename => $delete)
            {
            if ($delete == true)
                {
                echo "     delete Datei ".$filename." \n";
                }
            else
                {
                //if ($debug) echo "   ".$filename."\n";
                $i++;		
                }	
            }	
        //if ($debug) echo "insgesamt ".$i." Dateien.\n";
        }
    }

?>