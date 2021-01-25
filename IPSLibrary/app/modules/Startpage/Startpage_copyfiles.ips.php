<?

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
    * wie der Name scon sagt die Dateien aus dem Dropbox/Synology Drive Laufwerk in die Webfront Umgebung kopieren
    * aus Sicherheitsgründen dürfen die Browser nicht extern zugreifen
    *
    *
    */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	
    IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
    IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
    IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Startpage',$repository);
	}

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


	//$picturedir=IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\pictures\\";
    //echo "Zum Vergleich $picturedir   und $check \n";


    /***************************************************************************************************
    *
    *  Bilderverzeichnis initialisieren 
    *  file:///C|/Users/Wolfgang/Dropbox/Privat/IP-Symcon/pictures/07340IMG_1215.jpg
    *
    *******************************/

    copyIfNeeded($iconverzeichnis."/",$icondir);           // true debug

    $iconStartVerzeichnis = $iconverzeichnis."/start/";
    copyIfNeeded($iconStartVerzeichnis,$iconStartdir);           // true debug

    $iconClockVerzeichnis = $iconverzeichnis."/clock/";
    copyIfNeeded($iconClockVerzeichnis,$iconClockdir);           // true debug

    $imageverzeichnisMond = $imageverzeichnis."/mond/";                       // mond und mondtransparent
    copyIfNeeded($imageverzeichnisMond,$imagedirM);           // true debug

    $imageverzeichnisMondTransparent = $imageverzeichnis."/mondtransparent/";                       // mond und mondtransparent
    copyIfNeeded($imageverzeichnisMondTransparent,$imagedirMT);           // true debug

    copyIfNeeded($bilderverzeichnis,$picturedir);           // true debug


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

?>