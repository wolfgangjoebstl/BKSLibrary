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

    /*******************************************************
    * umgestellt auf IPS7, Verzeichnis webfront\user wird durch user ersetzt
    * OperationCenter um den ModulMagerIps7 und HighchartIps7 erweitert 
    *
    * Umstellung auf moderne Darstellung im Laufen
    *  22  Frame, Standarddarstellung
    *  23  Station, Wetterstation
    *
    * ein iFrame wird von einigen script Befehlen vorangestellt. Diese übernehmen die Ermittlung der Windows Size, der Browser Version und die Umschaltung des Browsers in den Full Mode 
    *
    *----------------------------
    * schreiben des Startpage html Strings in der htmlbox der Startpage
    *
    * es gibt verschiedene Darstellungsarten, diese können zentral für alle Webfronts per Einstellung geändert werden
    * Bei der Darstellung Frame kann die Formatierung der Seite pro Browser konfiguriert werden
    * es werden dazu browser cookies in der Datenbank gespeichert.
    *
    * die Darstellung selbst erfolgt über die Library mit $startpage->StartPageWrite :
    * alte Implementierung Startpage:
    * ---------------------
    * Das StartPage_Schreiben Script wird alle 8 Minuten vom Timer aufgerufen
    * Routine bearbeitet auch die Tastendrücke am Webfront
    *
    * Bilder im Verzeichnis werden verkleinert um die Darstellung im Webfront zu beschleunigen
    *
    * die Wetterfunktion wird bearbeitet und mit aggregateOpenWeather in eine schöne Form gebracht
    *
    * neue Implementierung:
    * ----------------------
    * die Startpage wird umgestellt werden auf in frame Navigation ohne regelmaessige Updates. Eigenes Item Frame dazu eingeführt
    * verweist auf den iFrame mit src StartpageTopology
    * iframe ist ein unabhängiger Frame in der Webfront Umgebung mit Höhe und Weite 100%
    * die Höhe ist responsive, iframe ist in div eingebunden, verwendet inline Formatierungen
    * die Kommunikation erfolgt über Javascript (jquery und ajax)
    * der iFrame verwendet seine eigenen css stylesheets
    * Zur Strukturierung der Webpages nur div styles verwenden, Tables sind zu langsam und nicht responsive 
    *
    *
    * Verschiedene Ansätze
    * simpel:       Bildlaufleiste wenn Tabelle zu breit und sich nicht teilen lässt <div style="overflow-x:auto;"><table.....
    * EinsZuEins:   <td> wird zu <div class='table_cell'> <tr> zu <div class='table_row'> usw. die styles mit .table_cell {display: table-cell;
    * Kompliziert:
    *
    * responsive iFrame, iFrame ist eine eigene Webpage, beginne html5 Dokument mit
    * <meta name="viewport" content="width=device-width, initial-scale=1.0">
    * 
    * { box-sizing: border-box;}          damit die Größe auf die Box geht und nicht auf den Inhalt
    * meta kann man nutzen für die Erkennung von kleinen Bildschirmen
    * @media only screen and (max-width: 768px) { [class*="col-"] { width: 100%; }  bislang hatten alle cols unterschiedliche Breiten
    * <img src="img_girl.jpg" style="max-width:100%;height:auto;">
    * Das Bild wird immer auf maximale Breite synchronisiert, 
    * Wenn mehrere Objekte nebeneinander, irgendwann untereinander darstellen, vorher synchron verkleinern
    * ist aber in einem Div untergebracht 
    *
    * Eigentlich wird der Bildschirm gedrittelt, jedes Drittel wiederum für sich geviertelt 
    *       3/3 PC
    *       2/3 Pad
    *       1/3 Phone
    *
    * classes definieren für jedes div, es gibt zumindest die classes
    *       header, menu (1/3), main (2/3), footer
    *       big (2/3)  small (1/3)
    *       section1 section2 section3
    *       col-span  span 1..12
    * .menu { width: 25%;   float: left;   padding: 15px;   border: 1px solid red; } 
    * .col-1 {width: 8.33%;} .col-2 {width: 16.66%;} .col-3 {width: 25%;}
    * [class*="col-"] { float: left; padding: 15px;  border: 1px solid red; }
    * .row::after {  content: "";   clear: both;   display: table; }    // Objekte immer nebeneinander, wenn nicht übereinander dargestellt
    * .menu li:hover {  background-color: #0099cc;}
    * <div class="row">  <div class="col-3">...</div> <div class="col-9">...</div>   </div>
    *
    * Herausforderungen, Stylerules, div im 16/9 Format macht das erste Problem, iFrame nutzt den zur Verfügung gestellten Platz, sonst default size 150x100
    *
    * <div style="padding-bottom:56.25%; position:relative; display:block; width: 100%">
    *       <iframe width="100%" height="100%  name="StartPage" src="../user/Startpage/StartpageTopology.php" frameborder="0" allowfullscreen="" style="position:absolute; top:0; left: 0"></iframe></div>
    * Bild und Wettertabelle teilen sich den verfügbaren Platz
    * das Bild hat einen fixen Bereich reserviert, 75%, die Tabelle 25%
    * Wettertabelle hat Vorrang, bleibt als Tabelle formatiert
    * 
    * aktuell ist die Wettertabelle nicht oben angeordnet, sondern unten
    *
    * weitere Informationen:
    *
    * Unterstützt extra Debug für manuelles script execute
    * unbeschränkten Arbeitsspeicher
    * Modulmanager für IPS7
    * classes: ipsOps, webOps, jsSnippets
	*
    **************************************/

    $debug=false;
    if ($_IPS['SENDER']=="Execute") 
        {
        echo "Script Execute, Darstellung automatisch mit Debug aktiviert.\n";
        $debug=true;
        }


    /********************************************* CONFIG *******************************************************/

    ini_set('memory_limit', '-1');          // memory unbeschränkt um die Bildbearbeitung zu ermöglichen

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
    IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
    IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
    IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');
    IPSUtils_Include ("ModuleManagerIps7.class.php","IPSLibrary::app::modules::OperationCenter");

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    $moduleManager    = new ModuleManagerIPS7('Startpage',$repository);

 	$installedModules = $moduleManager->GetInstalledModules();
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');

    //$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
    //IPS_SetScriptTimer($_IPS['SELF'], 8*60);  /* wenn keine Veränderung einer Variablen trotzdem updaten */

    if (isset($installedModules["DetectMovement"]))
        {
        /* Detect Movement kann auch Temperaturen agreggieren */
        IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
        IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
        }

    $webOps = new webOps();
    $dosOps = new dosOps();
    $js = new jsSnippets();

    $startpage = new StartpageHandler();
    $configuration=$startpage->getStartpageConfiguration();

    $bilderverzeichnis=$configuration["Directories"]["Pictures"];

    $StartPageTypeID = IPS_getObjectIdByName("Startpagetype", $startpage->CategoryIdData);   /* 0 Boolean 1 Integer 2 Float 3 String */
    if ($debug) 
        {
        echo "   StartpageTypeID : ".$StartPageTypeID." (".IPS_GetName($StartPageTypeID)."/".IPS_GetName(IPS_GetParent($StartPageTypeID))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($StartPageTypeID)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($StartPageTypeID)))).") ".GetValue($StartPageTypeID)."\n";
        //echo "Kategorienvergleich: ".$startpage->CategoryIdData."   $CategoryIdData  \n";
        }
    $variableIdHTML  = CreateVariable("Uebersicht",    3 /*String*/, $CategoryIdData, 40, '~HTMLBox', null,null,"");
    $AstroLinkID     = CreateVariable("htmlAstroTable",3           , $CategoryIdData,100, "~HTMLBox", null,null,"");
    $mobileContentID = CreateVariable("htmlMobileTable",3          , $CategoryIdData,0,"~HTMLBox",null,null,"");                    // Bottomline für Mobile Webfront

    $switchScreenID    = IPS_GetVariableIDByName("SwitchScreen",$CategoryIdData);
    $switchSubScreenID = IPS_GetVariableIDByName("SwitchSubScreen",$CategoryIdData);  

    $showfile=false;            // dann wird auch wenn nicht übergeben es automatisch generiert

    /**************************************** Tastendruecke aus dem Webfront abarbeiten *********************************************************/
    
    $Verzeichnis=$dosOps->correctDirName($dosOps->getWorkDirectory());
    $Verzeichnis       = $dosOps->correctDirName($startpage->getPictureDirectory());
    $VerzeichnisBilder = $dosOps->correctDirName($startpage->getPictureDirectory()."SmallPics");

 if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */
    $variableID=$_IPS['VARIABLE'];

    $pname="StartpageControl";
    $profiles=$webOps->getActionProfileByName($pname,true);     // verkürzt als Array

    switch ($variableID)          // Value formatted : Explorer Fullscreen Station Picture Topologoe Off
        {
        case ($switchScreenID):
            /* andere Zuordnung, Auswahlfeld ist breiter geworden, erfordert
             * Zuordnung zu write Startpage
             *      0       Explorer
             *      1       Full Screen
             *      2       Station             2  / 23
             *      3       Media               5
             *      4       Frame               22   html box wird anders beschrieben
             *      5       Picture             1
             *      6       Topologie           3
             *      7       Hierarchie          4
             *      8       Graph               6
             *      9       Off
             */
        	switch ($profiles[$_IPS['VALUE']])
		        {
        		case "Off":	/* Monitor off/on, Off */
		        	controlMonitor("off",$configuration);
        			break;
                case "Hierarchie":   /* Hierarchy, new one with picture drawing of geographical position*/
        			SetValue($StartPageTypeID,4);
 					break;
                case "Topologie":   /* Topologie, new one with picture drawing of geographical position*/
        			SetValue($StartPageTypeID,3);
 					break;
    	        case "Picture":  	/* Bildschirmschoner, Picture */
        			SetValue($StartPageTypeID,1);
 		        	break;
    	        case "Frame":  	/* Frame, test javascript framing */
        			SetValue($StartPageTypeID,22);
		        	break;
    	        case "Graph":  	/* Frame, test svg drawing */
        			SetValue($StartPageTypeID,6);
		        	break;
    	        case "Media":  	/* Bildschirmschoner, Media */
        			SetValue($StartPageTypeID,5);
 		        	break;
        		case "Station":  	/* Wetterstation, Station */
		        	//SetValue($StartPageTypeID,2);                             // Station alte Formatierung
		        	SetValue($StartPageTypeID,23);
                    SetValue($switchSubScreenID,GetValue($switchSubScreenID)+1);
        			break;
        		case "Fullcreen":  	/* Full Screen ein, Fullscreen */
		        case "Explorer":  	/* Full Screen aus, Explorer */
			        controlMonitor("FullScreen",$configuration);
        			//IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, -1);
		        	break;
		        }
            SetValue($variableID,$_IPS['VALUE']);                
            break;

        default:
           	SetValue($variableID,$_IPS['VALUE']);
            break;
        }    

	}

/******************************************* Bilder initialisieren und bearbeiten ******************************************************/

if (GetValue($StartPageTypeID)==1)      // nur die Fotos von gross auf klein konvertieren und aussuchen wenn die Darstellung auch auf Pictures ist
    {

    /* 
    * Das Archiv für die Fotos ist das bilderverzeichnis, picturedir wird für die Darstellung aus dem Webfront verwendet
    * da eine relative Adressierung auf den Server adressiert und eine absolute Adressierung auf den Client geht.
    *
    */

    $files=$startpage->readPicturedir();
    $maxcount=count($files);
    if ($maxcount>0)
        {
        $showfile=rand(1,$maxcount-1);
        if ($debug) 
            {
            echo "   StartpageTypeID ist 1. Parameter : Bilder zur Anzeige  $maxcount Datei Index dafür ausgesucht $showfile \n";
            echo "Bilder resample, Größe verkleinern, Bild zur Darstellung ausgesucht $showfile:\n";
            }
        //print_r($file);

        if ( is_dir($VerzeichnisBilder) ==  false ) mkdir($VerzeichnisBilder);
        $datei=$files[$showfile];

        if (file_exists($VerzeichnisBilder.$datei))
            {
            if ($debug)
                {
                echo "   Datei ist ".$VerzeichnisBilder.$datei."\n";
                $notouch=true;
                foreach ($files as $index=> $file )
                    {
                    if (file_exists($VerzeichnisBilder.$file)===false) 
                        {
                        $datei=$file; 
                        $notouch=false;  
                        break;
                        }
                    }
                if ($notouch) echo "   Datei ist immer noch ".$VerzeichnisBilder.$datei.", keine Bilder zum bearbeiten.\n"; 
                else echo "   Neue Datei gefunden ".$VerzeichnisBilder.$datei.", mehr Bilder zum bearbeiten.\n";                   
                }
            }
        if (file_exists($VerzeichnisBilder.$datei)===false)
            {
            // Get new dimensions
            list($width, $height) = getimagesize($Verzeichnis.$datei);
            if ($debug) echo "Resample Picture $datei (".$width." x ".$height.") from ".$Verzeichnis.$datei." to ".$VerzeichnisBilder.$datei.".\n";

            $new_width=1920;
            $percent=$new_width/$width;
            $new_height = floor($height * $percent);
            if ($new_height > 1080) 
                { 
                if ($debug) echo "Status zu hoch : ".$new_width."  ".$new_height."   \n";
                $new_height=1080;
                $percent=$new_height/$height;
                $new_width = floor($width * $percent);
                }
            if ($debug) echo "New Size : (".$new_width." x ".$new_height.").\n";

            // Resample
            $image_p = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromjpeg($startpage->picturedir.$datei);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

            // Output
            imagejpeg($image_p, $startpage->picturedir."SmallPics/".$datei, 60);
            }
        }
    }
        
/**************************************** und jetzt sich auch noch um das Wetter kuemmern *********************************************************/

    /* wenn OpenWeather installiert ist dieses für die Startpage passend aggregieren, die Werte werden automatisch abgeholt */

    if ($debug) echo "aggregate Openweather.\n";
    $startpage->aggregateOpenWeather();						// die Zusammenfassung für die Wetter-Tabelle auf der Startpage machen
    $startpage->displayOpenWeather();                       // die Highcharts Darstellung huebsch machen,
    if ($debug) echo "write Summary Openweather.\n";
    $startpage->writeOpenweatherSummarytoFile();			// es gibt eine lange html Zusammenfassung, die man am besten in einen iFrame mit scroll Funktion packt		

    /**************************************** und jetzt die Startpage darstellen *********************************************************/

    /* mit der Funktion StartPageWrite wird die html Information für die Startpage aufgebaut */

    $startPageType=GetValue($StartPageTypeID);
    if ($debug) echo "Aufruf StartpageWrite in Startpage Class Library.Start Page Type ist $startPageType.\n";
    if ($startPageType<21)
        {
        SetValue($variableIdHTML,$startpage->StartPageWrite(GetValue($StartPageTypeID),$showfile,$debug));
        SetValue($mobileContentID,  $startpage->bottomTableLines());                                                        // Mobile Webfron Statuszeile/tabelle
        }
    else        // 21 free 22 Frame 23 Station
        {   //  frameborder=0  width="80%" height="1000"  border:none frameborder:0
        // Table wird jetzt unten angezeigt, irgendwas mit den beiden styles
        //SetValue($variableIdHTML,'<div><iframe width="100%" height=200%  name="StartPage" src="../user/Startpage/StartpageTopology.php" frameborder="0" allowfullscreen="" style="position:absolute; top:0; left: 0"></iframe></div>');
        //SetValue($variableIdHTML,'<div style="box-sizing: border-box;"><iframe style="box-sizing: border-box; width:100%; height:100%; " name="StartPage" src="../user/Startpage/StartpageTopology.php" frameborder="0" allowfullscreen="" style="position:absolute; top:0; left: 0"></iframe></div>');
        //SetValue($variableIdHTML,'<div style="box-sizing: border-box;"><iframe width="100%" height="100%";  name="StartPage" src="../user/Startpage/StartpageTopology.php" frameborder="0" allowfullscreen="" style="position:absolute; top:0; left: 0"></iframe></div>');
        //SetValue($variableIdHTML,'<div style="box-sizing: border-box;"><iframe width="100%" height="1000px";  name="StartPage" src="../user/Startpage/StartpageTopology.php" frameborder="0" allowfullscreen="" style="position:absolute; top:0; left: 0"></iframe></div>');
        //SetValue($variableIdHTML,'<div style="padding-bottom:56.25%; position:relative; display:block; width: 100%"><iframe width="100%" height="100%  name="StartPage" src="../user/Startpage/StartpageTopology.php" frameborder="0" allowfullscreen="" style="position:absolute; top:0; left: 0"></iframe></div>');
        //SetValue($variableIdHTML,'<div class="cuw-container"><iframe class="cuw-responsive-iframe" width="100%" height="800px" frameborder:0 src="../user/Startpage/StartpageTopology.php"></iframe></div>');          
        //SetValue($variableIdHTML,'<div class="cuw-container"><iframe class="cuw-responsive-iframe" style="width:100%; height:800px; border:none;" name="StartPage" src="../user/Startpage/StartpageTopology.php"></iframe></div>'); 
        
        //$html  = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>';
        $htmlScript  = '<script type="text/javascript">';
        
        /*$html .= '  var intervalId = window.setInterval(function(){
                        reportWindowSize();
                        }, 5000);
                    function reportWindowSize () {
                        let varheight=(window.innerHeight * 0.9);
                        document.getElementById("frame-status").innerHTML = "<p>Size " + window.innerHeight + " (" + varheight + ") x " + window.innerWidth + "  " + Date() + "<br>Viewport: " + $(window).width() + " x " + $(window).height() + "</p>";
                        $("#frame-start").css("height",varheight);
                        };
                    window.onresize = reportWindowSize;                     
                    ';  */
        $ready  = 'function reportWindowSize () {
                        let varheight=Math.round(window.innerHeight * 0.85);
                        document.getElementById("frame-status").innerHTML = "Size " + window.innerHeight + " (" + varheight + ") x " + window.innerWidth + "  " + Date();
                        }'."\n";
        $ready .= 'document.querySelector("#frame-status").addEventListener("click", (e) => {            
                        reportWindowSize ();
                        });'."\n";
        $ready .= 'window.onresize = reportWindowSize;  '."\n";   
        $ready .= 'function reportBrowserVersion() {
                        var Sys = {};  
                        var ua = navigator.userAgent.toLowerCase();  
                        var s;  
                        (s = ua.match(/msie ([\d.]+)/)) ? Sys.ie = s[1] :  
                        (s = ua.match(/firefox\/([\d.]+)/)) ? Sys.firefox = s[1] :  
                        (s = ua.match(/chrome\/([\d.]+)/)) ? Sys.chrome = s[1] :  
                        (s = ua.match(/opera.([\d.]+)/)) ? Sys.opera = s[1] :  
                        (s = ua.match(/version\/([\d.]+).*safari/)) ? Sys.safari = s[1] : 0; 
                        if (Sys.ie) return ("IE: " + Sys.ie);  
                        if (Sys.firefox) return ("Firefox: " + Sys.firefox);  
                        if (Sys.chrome) return ("Chrome: " + Sys.chrome);  
                        if (Sys.opera) return ("Opera: " + Sys.opera);  
                        if (Sys.safari) return ("Safari: " + Sys.safari); 
                        }  '."\n";                
        $ready .= 'document.querySelector("#frame-browser").addEventListener("click", (e) => {                         
                        document.getElementById("frame-browser").innerHTML = reportBrowserVersion ();
                        });  '."\n";
        $ready .= 'var fullScreen=0;
                    function toggleFullScreen(elem) {
                        if (fullScreen==0) { elem.requestFullscreen(); fullScreen=1; return ("Full Screen"); }
                        else { document.exitFullscreen(); fullScreen=0; return ("Standard Screen"); } 
                        }   '."\n"; 
        $ready .= 'document.querySelector("#frame-fullscreen").addEventListener("click", (e) => {
                        document.getElementById("frame-fullscreen").innerHTML =  toggleFullScreen(document.documentElement);
                        });  '."\n";                        

        $htmlScript .= $js->ready($ready);
        $htmlScript .= '</script>';
        
        $html = "";
        $html .= $htmlScript;
        $html .= '<div style="box-sizing: border-box;">';
        //$html .= '  <iframe id="frame-start" name="StartPage" src="../user/Startpage/StartpageTopology.php" frameborder="0" allowfullscreen="" style="width:100%; height:1000px; position:absolute; top:0; left: 0">';
        switch ($startPageType)
            {
            case 23:        // Status
                $html .= '  <iframe id="status-start" name="StartStatus" src="../user/Startpage/StartpageStatus.php" style="width:100%; height:85vh; ">';
                break;
            case 22:        // Frame
                $html .= '  <iframe id="frame-start" name="StartPage" src="../user/Startpage/StartpageTopology.php" style="width:100%; height:85vh; ">';
                break;
            default:        // leer, Fehler mglw.
                $html .= '  <iframe id="frame-start" name="StartDefault" style="width:100%; height:85vh; ">';
                break;
            }
        $html .= '      </iframe>';
        $html .= '  </div>';
        $html .= '<div id="frame-status" style="font-size: 1hm; display:inline; float:left;">Statusangaben hier clicken</div>';        
        $html .= '<div id="frame-browser" style="font-size: 1hm; display:inline; padding: 5px;">Browser hier clicken</div>';        
        $html .= '<div id="frame-fullscreen" style="font-size: 1hm; display:inline; float:right;">Fullscreen hier clicken</div>';  
        SetValue($variableIdHTML,$html);
        }

    /**************************************** PROGRAM EXECUTE *********************************************************/

 if ($_IPS['SENDER']=="Execute")
	{
    echo "\n================================================================\n"; 
	echo "Execute aufgerufen durch manuellen Start des Scripts:\n";
    echo "Startpage wird mit folgenden Parametern aufgerufen : Modus:".GetValue($StartPageTypeID)." ShowFile:".($showfile?"true":"false").".\n";
	//echo "\nKonfigurationseinstellungen:\n"; print_r($configuration);

	$pname="StartpageControl";
    echo "Variable SwitchScreen mit Profil \"$pname\" hat OID: $switchScreenID \n";
    $profiles=$webOps->getActionProfileByName($pname,true);     // verkürzt als Array
    //print_r($profiles);
    if ($profiles)
        {
        //foreach ($profiles as $index => $profil) echo "  ".$index."  ".$profil["Value"]."  ".$profil["Name"]."\n";
        foreach ($profiles as $index => $profil) echo "  $index  $profil\n";
        }

	echo "Switch on Monitor, look for :".$configuration["Directories"]["Scripts"].'nircmd.exe'."\n"; 
	IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, -1);	
	
	$Config=$startpage->configWeather();
	$noweather=!$Config["Active"]; 
    print_r($Config);

    if (false) // Verzeichnisse auslesen für besseren Überblick
        {
        $dosOps->writeDirStat($Verzeichnis);
        $dosOps->writeDirStat($VerzeichnisBilder);

        $files = $dosOps->readDirToArray($VerzeichnisBilder);
        $maxcount=count($files);
        $files1 = $dosOps->readDirToArray($Verzeichnis);                    // Input Directory
        $maxcount1=count($files1);
        echo "Bildanzeige, es gibt insgesamt ".$maxcount."/".$maxcount1." Bilder (Ziel/Quelle) auf dem angegebenen Laufwerk.\n";

        $fileName=array(); $fileName1=array();
        foreach ($files as $name)  
            {
            if (is_dir($VerzeichnisBilder.$name)===false) $fileName[$name]  = filesize($VerzeichnisBilder.$name);
            }
        $maxcount=count($fileName);        
        foreach ($files1 as $name) 
            {
            if (is_dir($Verzeichnis.$name)===false) $fileName1[$name] = filesize($Verzeichnis.$name);
            }
        $maxcount1=count($fileName1); 
        echo "Bildanzeige, es gibt insgesamt ".$maxcount."/".$maxcount1." Bilder (Ziel/Quelle) auf dem angegebenen Laufwerk.\n";           
        
        if ($maxcount1>$maxcount)
            {
            foreach ($fileName as $name => $size) unset($fileName1[$name]);
            echo "Files that have not been converted from $Verzeichnis to $VerzeichnisBilder.\n";
            print_R($fileName1);
            }
        }

    if (false)
        {
        echo "Startpage wird mit folgenden Parametern aufgerufen : Modus:".GetValue($StartPageTypeID)." ShowFile:".($showfile?"true":"false").".\n";
        echo "Darstellung Startpage, Darstellung der links zu Bildern ist nicht möglich.\n";
        echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
        echo $startpage->StartPageWrite(2,false,true);                      // true mit Debug
        }

    echo "Erzeugung des Wetter Meteograms:\n";
    $startpage->displayOpenWeather(true);           //true für Debug

    echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
    echo "Durchlauf Startpage Write mit Debug ein:\n";
    $startpage->StartPageWrite(GetValue($StartPageTypeID),$showfile,true);      // true für Debug

    echo "Vergleich der Fotos am Sharedrive mit den anderen Fotos:\n";

    $picturedir = $startpage->picturedir;
    //print_R($configuration["Directories"]);
    $smallpicsdir = $picturedir."SmallPics/";
    $filesTable=array();
    echo "Bilderverzeichnis Source $bilderverzeichnis Target $picturedir Small Pics $smallpicsdir\n";
    //$files=$dosOps->readdirToArray($bilderverzeichnis,["recursive"=>false,"detailed"=>true],false,true);
    $files = $dosOps->writeDirToArray($bilderverzeichnis);        // bessere Funktion
    $filesFiltered = $dosOps->findfiles($files,"*.jpg",false);       //Debug
    foreach ($filesFiltered as $index => $filename)
        {
        $filesTable[$filename]["Source"]="ok";
        }
    //print_R($files);
    //$dosOps->writeDirStat($bilderverzeichnis);
    //$files=$dosOps->readdirToArray($picturedir);
    //print_R($files);
    echo "$bilderverzeichnis, insgesamt ".sizeof($files)." Dateien.\n";
    //echo "\n";
    //$files=$dosOps->readdirToArray($picturedir);
    //$dosOps->writeDirStat($picturedir);
    $files = $dosOps->writeDirToArray($picturedir);        // bessere Funktion
    $filesFiltered = $dosOps->findfiles($files,"*.jpg",true);       //Debug
    foreach ($filesFiltered as $index => $filename)
        {
        $filesTable[$filename]["Target"]="ok";
        }
    echo "$picturedir, insgesamt ".sizeof($files)." Dateien.\n";
    //echo "\n";
    //$files=$dosOps->readdirToArray($picturedir."SmallPics/");
    //$dosOps->writeDirStat($picturedir."SmallPics/");
    $files = $dosOps->writeDirToArray($smallpicsdir);
    echo "Small Pics Dir $smallpicsdir, insgesamt ".sizeof($files)." Dateien.\n";
    //print_R($files);
    $filesFiltered = $dosOps->findfiles($files,"*.jpg",true);       //Debug
    //print_R($filesFiltered);
    foreach ($filesFiltered as $index => $filename)
        {
        $filesTable[$filename]["SmallPics"]="ok";
        }    
    echo "Ergebnistabelle der Auswertung:\n";
    //print_R($filesTable);

        $html = "<table>";
        foreach ($filesTable as $filename=>$status)
            {
            $html .= "<tr>";
            $html .= "<td>$filename</td>";
            if (isset($status["Source"])) $html .= "<td>".$status["Source"]."</td>";
            else $html .= "<td></td>";
            if (isset($status["Target"])) 
                {
                $html .= "<td>".$status["Target"]."</td>";
                list($width, $height) = getimagesize($startpage->picturedir.$filename);
                $html .= "<td>".$width."</td><td>".$height."</td>";
                }
            else $html .= "<td></td><td></td><td></td>";
            if (isset($status["SmallPics"])) 
                {
                $html .= "<td>".$status["SmallPics"]."</td>";
                list($width, $height) = getimagesize($smallpicsdir.$filename);
                $html .= "<td>".$width."</td><td>".$height."</td>";
                }
            else $html .= "<td></td><td></td><td></td>";
            $html .= "</tr>";
            }
        $html .= "<table>";
        echo $html;
  
    }



?>