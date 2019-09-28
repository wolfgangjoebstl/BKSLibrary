<?

/*
	 * @defgroup Startpage
	 *
	 * Sammlung class Routines der Startpage
	 *
	 *
	 * @file          Startpage_Library.class.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/


    /*
     * Klasse StartpageHandler
     *
     * sammelt alle Routinen für die Erstellung und Verwaltung der Startpage/Dashboard
     * mit der Absage des IPSWeather Moduls wurden die Wetter Aktivitäten hier her verlagert.
     *
     * _construct
     * getStartpageConfiguration
     * configWeather
     * readPicturedir
     * StartPageWrite
     * tempTableLine
     *
     * writeStartpageStyle
     * findIcon
     * findeVarSerie
     * aggregateOpenWeather
     * additionalTableLines
     * bottomTableLines
     *
     * Andere Funktionen, ausserhalb der Klasse
	 *
     * controlMonitor
     * Startpage_SetPage
     * Startpage_Refresh*
     */

	class StartpageHandler 
		{

		private $configuration = array();				// die angepasste, standardisierte Konfiguration
		private $aussentemperatur, $innentemperatur;
		
		public $picturedir;			// hier sind alle Bilder für die Startpage abgelegt
		public $CategoryIdData, $CategoryIdApp;			// die passenden Verzeichnisse
		
		private $OWDs;				// alle Openweather Instanzen

		/**
		 * @public
		 *
		 * Initialisierung des IPSMessageHandlers
		 *
		 */
		public function __construct()
			{
			/* standardize configuration */
			
			$this->configuration=startpage_configuration();
	        if (!isset($this->configuration["Display"])) $this->configuration["Display"]["Weathertable"]="Inactive";	
			
			/* get Directories */

			$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Startpage',$repository);

			$this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
			$this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');		
			
			$this->picturedir=IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\pictures\\";
			
			/* get Variables */
			
			$this->aussentemperatur=temperatur();
			$this->innentemperatur=innentemperatur();				
											
			$modulhandling = new ModuleHandling();		// true bedeutet mit Debug
			$this->OWDs=$modulhandling->getInstances('OpenWeatherData');
			}
		
		/*
		 * Abstrahierung der Startpage Konfiguration
		 *
		 */
		 		
		function getStartpageConfiguration()
	        {
	        return ($this->configuration);
	        }

		/*
		 * Abstrahierung der OpenWeather Modul Konfiguration
		 *
		 */
		 		
		function getOWDs()
	        {
	        return ($this->OWDs);
	        }

		/*
		 * aus der Startpage Konfiguration die Einstellung ableiten ob
		 *
		 *     ob eine Wettertabelle angezeigt werden soll
		 *     ob Wunderground oder OpenWeatherTable verwendet werden soll
		 *
		 *********************************************************************************************/

		function configWeather()
			{
			$weather=array();
			$weather["Active"]=false;
			$weather["Source"]="WU";
			if ( isset ($this->configuration["Display"]["Weathertable"]) == true ) { if ( $this->configuration["Display"]["Weathertable"] == "Active" ) { $weather["Active"]=true; } }
			if ( isset ($this->configuration["Display"]["Weather"]) == true ) 
				{
				/* mehr Parameter verfügbar */
				if ( isset ($this->configuration["Display"]["Weather"]["Weathertable"]) == true ) { if ( $this->configuration["Display"]["Weather"]["Weathertable"] == "Active" ) { $weather["Active"]=true; } }
				if ( isset ($this->configuration["Display"]["Weather"]["WeatherSource"]) == true ) { if ( $this->configuration["Display"]["Weather"]["WeatherSource"] != "WunderGround" ) { $weather["Source"]="OWD"; } }
				}
			return($weather);
			}
		
		/*
		 * rausfinden wo die Bilder, die angezeigt werden sollen, abgespeichert sind
		 * das Verzeichnis dafür einlesen
		 *
		 */
		
		function readPicturedir()
			{
			$file=array();
			$handle=opendir ($this->picturedir);
			//echo "Verzeichnisinhalt:<br>";
			$i=0;
			while ( false !== ($datei = readdir ($handle)) )
				{
				if ($datei != "." && $datei != ".." && $datei != "Thumbs.db") 
					{
			        if (is_dir($this->picturedir.$datei)==true ) 
            			{
			            //echo "Verzeichnis ".$picturedir.$datei." gefunden.\n";
            			}
			        else
            			{            
					    $i++;
 		    			$file[$i]=$datei;
            			}
					}
				}
			closedir($handle);			
			return($file);
			}

	
		/**************************************** FUNCTIONS *********************************************************/

		function StartPageWrite($PageType,$showfile=false)
			{
			$Config=$this->configWeather();
			$noweather=!$Config["Active"];                
	    	/* html file schreiben, Anfang Style für alle gleich */
			$wert="";
		    $wert.= $this->writeStartpageStyle();
            switch ($PageType)
                {
                case 3:        // Topologie
                    //echo "Topologiedarstellung erster Entwurf, verwendet showPicture und showTopology.\n";
                    $wert.='<table id="startpage">';
                    $wert.='<tr>';
                    $wert.= $this->showPicture($showfile);
                    $wert.= $this->showTopology();
                    $wert.='</tr></table>';
                    break;
                case 2:   //echo "NOWEATHER false. PageType 2. NoPicture.\n";            	
                    if ( $noweather==true )
                        {
                        $file=$this->readPicturedir();
                        $maxcount=count($file);
                        if ($showfile===false) $showfile=rand(1,$maxcount-1);

                        //echo "NOWEATHER true.\n";
                        $wert.='<table id="startpage">';
                        $wert.='<tr><td>';
                        if ($maxcount >0)
                            {
                            $wert.='<img src="user/Startpage/user/pictures/'.$file[$showfile].'" width="67%" height="67%" alt="Heute" align="center">';
                            }		
                        $wert.='</td></tr></table>';
                        }
                    else        // Anzeige der Wetterdaten
                        {
                        $weather=$this->getWeatherData();                                
                        $wert.='<table <table border="0" height="220px" bgcolor="#c1c1c1" cellspacing="10"><tr><td>';
                        $wert.='<table border="0" bgcolor="#f1f1f1"><tr><td align="center"> <img src="'.$weather["today"].'" alt="Heute" > </td></tr>';
                        $wert.='<tr><td align="center"> <img src="'.$weather["tomorrow"].'" alt="Heute" > </td></tr>';
                        $wert.='<tr><td align="center"> <img src="'.$weather["tomorrow1"].'" alt="Heute" > </td></tr>';
                        $wert.='</table></td><td><img src="user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td><td><strg>'.number_format($this->aussentemperatur, 1, ",", "" ).'°C</strg></td>';
                        $wert.='<td> <table border="0" bgcolor="#ffffff" cellspacing="5" > <tablestyle><tr> <td> <img src="user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur">  </td> </tr>';
                        $wert.='<tr> <td align="center"> <innen>'.number_format($this->innentemperatur, 1, ",", "" ).'°C</innen> </td> </tr></tablestyle> </table> </td></tr>';
                        $wert.='</table>';
                        }
                    break;
                case 1:
                    /*******************************************
                     *
                     * PageType==1,Diese Art der Darstellung der Startpage wird Bildschirmschoner genannt 
                     * Bild und Wetterstation als zweispaltige Tabelle gestalten
                     *
                     *************************/
                    $wert.='<table id="startpage">';
                    //$wert.='<tr><th>Bild</th><th>Temperatur und Wetter</th></tr>';  /* Header für Tabelle */
                    //$wert.='<td><img id="imgdisp" src="'.$filename.'" alt="'.$filename.'"></td>';
                    $wert.='<tr>';
                    $wert.= $this->showPicture($showfile);
                    $wert.= $this->showWeatherTable();
                    $wert.='</tr>';
                    $wert.=$this->bottomTableLines();
                    $wert.='</table>';
                    break;
                default:
                    break;    
                }
			return $wert;
			}

        /********************
         *
         * Zelle Tabelleneintrag für die Darstellung der Topologie mit aktuellen Werten
         *
         *
         **************************************/

		function showTopology($debug=false)
			{
            $wert="";

            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
            IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
            IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');            

            /* Get Topology Liste aus EvaluateHardware_Configuration */
            $Handler = new DetectDeviceHandler();
            $topology=$Handler->Get_Topology();

            /* die Topologie mit den Geräten anreichen. Es gibt Links zu INSTANCES and OBJECTS 
            * OBJECTS sind dann wenn das Gewerk in der Eventliste angegeben wurde, wie zB Temperature, Humidity aso
            *
            */

            $topologyPlusLinks=$topology;
            foreach (IPSDetectDeviceHandler_GetEventConfiguration() as $index => $entry)
                {
                if ($debug) echo $entry[0]."|",$entry[1]."|",$entry[2]."\n";
                $name=IPS_GetName($index);
                $entry1=explode(",",$entry[1]);		/* Zuordnung Gruppen, es können auch mehrere sein, das ist der Ort zB Arbeitszimmer */
                $entry2=explode(",",$entry[2]);		/* Zuordnung Gewerke, eigentlich sollte pro Objekt nur jeweils ein Gewerk definiert sein. Dieses vorrangig anordnen */
                if (sizeof($entry1)>0)
                    {
                    foreach ($entry1 as $place)
                        {
                        if ( isset($topology[$place]["OID"]) != true ) 
                            {
                            if ($debug) echo "   Kategorie $place anlegen.\n";
                            }
                        else
                            {
                            $oid=$topology[$place]["OID"];
                            //print_r($topology[$place]);
                            $size=sizeof($entry2);
                            if ($entry2[0]=="") $size=0;
                            if ($size > 0) 
                                {	/* es wurde ein Gewerk angeben, zB Temperatur, vorne einsortieren */
                                if ($debug) echo "   erzeuge Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid).") ".$entry[2]."\n";
                                //CreateLinkByDestination($name, $index, $oid, 10);	
                                $topologyPlusLinks[$place]["OBJECT"][$entry2[0]][$index]=$name;       // nach OBJECT auch das Gwerk als Identifier nehmen
                                }
                            else
                                {	/* eine Instanz, dient nur der Vollstaendigkeit */
                                if ($debug) echo "   erzeuge Instanz Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid)."), wird nachrangig einsortiert.".$entry[2]."\n";						
                                //CreateLinkByDestination($name, $index, $oid, 1000);						
                                $topologyPlusLinks[$place]["INSTANCE"][$index]=$name;
                                }
                            }
                        }
                    //print_r($entry1);
                    }
                }  // ende foreach

            if ($debug) 
                {
                print_r($topologyPlusLinks);
                echo "=====================================================================================\n";
                echo "Topology Status Ausgabe:\n";
                }

            $topologyStatus=array();
            foreach ($topologyPlusLinks as $name => $place)
                {
                if ( (isset($place["x"])) && (isset($place["y"])) ) 
                    {
                    $x=$place["x"]; $y=$place["y"];
                    if ( isset($place["l"]) ) $l=$place["l"]; else $l=1;
                    if ( isset($place["h"]) ) $h=$place["h"]; else $h=1;
                    $topologyStatus[$y][$x]["Size"]=["l"=>$l,"h"=>$h];
                    if ($debug) echo "$name is located on $x und $y. Size is $l x $h.\n"; 
                    if (isset($place["ShortName"])) $topologyStatus[$y][$x]["ShortName"]=$place["ShortName"];
                    if (isset($place["OBJECT"])) 
                        {
                        $topologyStatus[$y][$x]["Status"]=$place["OBJECT"];
                        if ($debug) 
                            {
                            foreach ($place["OBJECT"] as $type => $objName) 
                                {
                                echo "  $type : ";
                                foreach ($objName as $oid => $name) echo "  $oid (".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName($oid).") => $name  ";
                                echo "\n";
                                }
                            echo "\n";
                            }
                        }
                    }
                }  // ende foreach


            if ($debug) 
                {
                echo "=====================================================================================\n";
                echo "Status Topologie für Ausgabe vorbereitet:\n";
                print_r($topologyStatus);
                }

            ksort($topologyStatus);
            $wert.="<style>";
            $wert.="#topology { border-collapse: collapse; border: 1px solid #ddd;   }";
            $wert.="#topology td, #topology th { border: 1px solid #ddd; text-align: center; height: 50px; width: 50px; }";
            $wert.="#topology p { color:lightblue; margin:0;   }";
            $wert.="</style>";
            $wert.="<td>";
            $wert .= $this->writeTable($topologyStatus, $debug);
            $wert.="</td>";
            return ($wert);
            }

       /* rekursive Routine um eine Tabelle zu zeichnen 
        * es kann auch eine Tabelle in einer Zelle abgebildet werden, daher rekursives Modell
        * 
        *
        *
        ********/

        function writeTable($topologyStatus, $debug=false)
            {
            if ($debug) 
                {
                echo "**************************************************************************************\n";
                echo "Aufruf writeTable, Darstellung der Informationen innerhalb einer Topologie.\n";
                print_r($topologyStatus);
                }

            /* Analyse der übergebenen Daten */
            $maxx=1; $maxy=0;
            $lsum=array();
            foreach ($topologyStatus as $y => $line)	
                {
                $lsum[$y]["line"]=0;        // Summenzähler für übergrosse Zellen
                $maxy++;    
                foreach ($line as $x => $status) 
                    {
                    $lsum[$y][$x]=0;        // Merker für übergrosse Zellen die über mehrere Zeilen gehen
                    if ($x > $maxx) $maxx=$x; 
                    }
                }
            if ($debug) echo "Evaluierung Tabellengroesse: maximale Anzahl an Tabelleneinträgen in x Richtung : $maxx , in y Richtung : $maxy.\n";

            $html="";
            $html.="<table id=topology>";
            foreach ($topologyStatus as $y => $line)
                {
                $html.="<tr>";
                //echo "   Zeile $y abarbeiten:\n";
                $maxxActual=$maxx; $shiftleft=0;
                for ($i=1;$i<=$maxxActual;$i++)
                    {
                    $x=$i+$lsum[$y]["line"];
                    if ($debug) echo "   Zelle mit Koordinaten Zeile $y Spalte $x abarbeiten:\n";
                    $text=$i;               // Default Text
                    if ( (isset($lsum[$y][$x])) && ($lsum[$y][$x]==1) ) 
                        {
                        /* $lsum[$y]["line"] nicht anpassen, Darstellung anders gewählt, bei x Koordinaten wird die Versetzung bereits bei der Eingabe in config berücksichtigt */
                        $maxxActual = $maxxActual-1;            // hier haengt noch ein Spalte von oben herunter, wird beim Tabellenzeichnen ignoriert und einfach daneben angefangen
                        $shiftleft++;
                        }
                    elseif (isset($line[$i-$shiftleft]))
                        {
                        if (isset($line[$i-$shiftleft]["ShortName"])) $text=$line[$i-$shiftleft]["ShortName"];
                        if (isset($line[$i-$shiftleft]["Size"])) { $l=$line[$i-$shiftleft]["Size"]["l"]; $h=$line[$i-$shiftleft]["Size"]["h"]; } else { $l=1; $h=1; }
                        if ($debug) 
                            {
                            echo "      Eintrag $i ist dran mit Size (hxb) $h x $l :\n"; 
                            print_r($line[$i-$shiftleft]);
                            }
                        if ($l>1) 
                            {
                            $maxxActual = $maxxActual-$l+1;
                            $lsum[$y]["line"] = $lsum[$y]["line"] + $l-1;
                            }
                        if ($h>1) 
                            {
                            for ($j=1;$j<$h;$j++) $lsum[$y+$j][$x]=1;
                            }
                        if (isset($line[$i-$shiftleft]["Status"])) 
                            {
                            $newStatus=$this->transformStatus($line[$i-$shiftleft]["Status"]);
                            /*
                            if ($line[$i]["Status"]==2) $html.='<td bgcolor="00FF00"> '.$text.' </td>'; 
                            elseif ($line[$i]["Status"]==1) $html.='<td bgcolor="00FFFF"> '.$text.' </td>';			
                            else $html.='<td bgcolor="0000FF"> '.$text.' </td>';
                            */
                            //foreach ($line[$i-$shiftleft]["Status"] as $type => $object)
                            foreach ($newStatus as $entry)
                                {
                                foreach ($entry as  $type => $object)
                                    {
                                    if (count($object)>1) $long=true; else $long=false;
                                    $first=true;
                                    foreach ($object as $index => $name) 
                                        {
                                        if ($first) $first=false;
                                        else $text .= "<br>";
                                        if (IPS_VariableExists($index)) 
                                            {
                                            if ($long) $text .= " ".$this->writeValue($index,$type)." ($name)";
                                            else $text .= " ".$this->writeValue($index,$type);
                                            }
                                        }
                                    }
                                }
                            $html.='<td colspan="'.$l.'" rowspan="'.$h.'" style="min-width:100px;background-color:#122232;color:white"> '.$text.' </td>';                            
                            }
                        else $html.='<td colspan="'.$l.'" rowspan="'.$h.'" style="bgcolor:#FFFFFF"> '.$text.' </td>';
                        }
                    else $html.='<td bgcolor="FFFFFF"> '.$text.' </td>';
                    }
                $html.="</tr>";
                }       // ende for schleife für Zeilen
            $html.="</table>";		
            
            if ($debug) 
                {
                // echo "Hilfestellung für Darstellung :\n"; print_r($lsum);
                }

            return ($html);
            }


        /******************************
        * 
        * Darstellung der Werte, es wird writeCell aufgerufen
        *
        *
        ********/

        function transformStatus($status)
            {
            echo "transformStatus: Input Werte\n";
            print_r($status);
            $result=array();
            $count=count($status);
            $keys=array();
            foreach ($status as $index => $entry)
                {
                switch (strtoupper($index))
                    {
                    case "TEMPERATURE":
                        $keys["TEMPERATURE"]=10;
                        break;
                    case "HUMIDITY":
                        $keys["HUMIDITY"]=20;
                        break;
                    case "MOVEMENT":
                        $keys["MOVEMENT"]=30;
                        break;
                    default:
                        $keys[strtoupper($index)]=100;
                        break;
                    }
                }
            asort($keys);
            print_r($keys);
            $i=0; 
            foreach ($keys as $key => $num) 
                {
                foreach ($status as $index => $entry)
                    {
                    if (strtoupper($index) == $key) 
                        {
                        $result[$i][$index]=$entry;
                        $i++;
                        }
                    }
                }
            //print_r($status);
            print_r($result);
            return ($result);
            }

        /******************************
        * 
        * Darstellung der Werte, es wird writeCell aufgerufen
        *
        *
        ********/

        function writeCell()
            {


            }

        /******************************
        * 
        * Darstellung der Werte, es wird writeValue aufgerufen
        *
        *
        ********/
        function writeValue($valueID, $type)
            {
            switch ($type)
                {
                case "Movement":
                    return ($this->writeMovement($valueID));
                case "Temperature":
                    return ($this->writeTemperature($valueID));
                case "Humidity":
                    return ($this->writeHumidity($valueID));
                default:
                    return("<p>$type".GetValue($valueID)."</p>");
                }
            }

        function writeMovement($valueID, $debug=false)
            {
            $timeSinceUpdate=time()-IPS_GetVariable($valueID)["VariableUpdated"];
            if ($timeSinceUpdate > (24*60*60))
                {
                if ($debug) echo IPS_GetName($valueID)."  last update ".date ("d.m.Y H.i.s",IPS_GetVariable($valueID)["VariableUpdated"])."\n";
                return ('<p style="color:red">Move '.(GetValue($valueID)?"Yes":"No")."</p>"); 
                }
            else return ("<p>Move ".(GetValue($valueID)?"Yes":"No")."</p>"); 
            }

        function writeTemperature($valueID, $debug=false)
            {
            return ("<p>".GetValue($valueID)." °C</p>"); 
            }

        function writeHumidity($valueID, $debug=false)
            {
            return ("<p>".GetValue($valueID)." %</p>"); 
            }
    
            
        /********************
         *
         * Zelle Tabelleneintrag für die Darstellung eines BestOf Bildes
         *
         *
         **************************************/

		function showPicture($showfile=false)
			{
            $wert="";
            $file=$this->readPicturedir();
            $maxcount=count($file);
            if ($showfile===false) $showfile=rand(1,$maxcount-1);
            $filename = 'user/Startpage/user/pictures/SmallPics/'.$file[$showfile];
            $filegroesse=number_format((filesize(IPS_GetKernelDir()."webfront/".$filename)/1024/1024),2);
            $info=getimagesize(IPS_GetKernelDir()."webfront/".$filename);
            if (file_exists(IPS_GetKernelDir()."webfront/".$filename)) 
                {
                //echo "Filename vorhanden - Groesse ".$filegroesse." MB.\n";
                }
            //echo "NOWEATHER false. PageType 1. Picture. ".$filename."\n\n";   
            $wert.='<td><div class="container"><img src="'.$filename.'" alt="'.$filename.'" class="image">';
            $wert.='<div class="middle"><div class="text">'.$filename.'<br>'.$filegroesse.' MB '.$info[3].'</div>';
            $wert.='</div></td>';
            return ($wert);
            }

        /********************
         *
         * Zelle Tabelleneintrag für die Wettertabelle
         *
         **************************************/

		function showWeatherTable()
			{
            $wert="";
            $weather=$this->getWeatherData();

            if (false)
                {
                /* Wenn Configuration verfügbar und nicht Active dann die rechte Tabelle nicht anzeigen */	
                $Config=$this->configWeather();
                //print_r($Config);
                $noweather=!$Config["Active"];
                if ( $noweather==false )
                    {
                    if ($Config["Source"]=="WU")
                        {
                        $todayID=get_ObjectIDByPath("Program.IPSLibrary.data.modules.Weather.IPSWeatherForcastAT");
                        if ($todayID == false)
                            {
                            //echo "weatherforecast nicht installiert.\n";
                            $noweather=true;
                            }
                        else
                            {
                            $today = GetValue(@IPS_GetObjectIDByName("TodayIcon",$todayID));
                            $todayTempMin = GetValue(@IPS_GetObjectIDByName("TodayTempMin",$todayID));
                            $todayTempMax = GetValue(@IPS_GetObjectIDByName("TodayTempMax",$todayID));
                            $tomorrow = GetValue(@IPS_GetObjectIDByName("TomorrowIcon",$todayID));
                            $tomorrowTempMin = GetValue(@IPS_GetObjectIDByName("TomorrowTempMin",$todayID));
                            $tomorrowTempMax = GetValue(@IPS_GetObjectIDByName("TomorrowTempMax",$todayID));
                            $tomorrow1 = GetValue(@IPS_GetObjectIDByName("Tomorrow1Icon",$todayID));
                            $tomorrow1TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMin",$todayID));
                            $tomorrow1TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMax",$todayID));
                            $tomorrow2 = GetValue(@IPS_GetObjectIDByName("Tomorrow2Icon",$todayID));
                            $tomorrow2TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMin",$todayID));
                            $tomorrow2TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMax",$todayID));

                            $todayDate="";		/* keine Openweather Darstellung, verwendet als Unterscheidung */
                            }
                        }
                    else			/* nicht Weather Wunderground, daher Openwewather */
                        {
                        $todayID=get_ObjectIDByPath("Program.IPSLibrary.data.modules.Startpage.OpenWeather");		
                        if ($todayID == false)
                            {
                            //echo "weatherforecast nicht installiert.\n";
                            $noweather=true;
                            }
                        else
                            {
                            //echo "OpenWeatherData mit Daten von $todayID wird verwendet.\n";
                            $todayDate    = GetValue(@IPS_GetObjectIDByName("TodayDay",$todayID));
                            $today        = GetValue(@IPS_GetObjectIDByName("TodayIcon",$todayID));
                            $todayTempMin = GetValue(@IPS_GetObjectIDByName("TodayTempMin",$todayID));
                            $todayTempMax = GetValue(@IPS_GetObjectIDByName("TodayTempMax",$todayID));
                            $tomorrowDate = GetValue(@IPS_GetObjectIDByName("TomorrowDay",$todayID));
                            $tomorrow     = GetValue(@IPS_GetObjectIDByName("TomorrowIcon",$todayID));
                            $tomorrowTempMin = GetValue(@IPS_GetObjectIDByName("TomorrowTempMin",$todayID));
                            $tomorrowTempMax = GetValue(@IPS_GetObjectIDByName("TomorrowTempMax",$todayID));
                            $tomorrow1Date = GetValue(@IPS_GetObjectIDByName("Tomorrow1Day",$todayID));
                            $tomorrow1 = GetValue(@IPS_GetObjectIDByName("Tomorrow1Icon",$todayID));
                            $tomorrow1TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMin",$todayID));
                            $tomorrow1TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMax",$todayID));
                            $tomorrow2Date = GetValue(@IPS_GetObjectIDByName("Tomorrow2Day",$todayID));
                            $tomorrow2 = GetValue(@IPS_GetObjectIDByName("Tomorrow2Icon",$todayID));
                            $tomorrow2TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMin",$todayID));
                            $tomorrow2TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMax",$todayID));
                            $tomorrow3Date = GetValue(@IPS_GetObjectIDByName("Tomorrow3Day",$todayID));
                            $tomorrow3 = GetValue(@IPS_GetObjectIDByName("Tomorrow3Icon",$todayID));
                            $tomorrow3TempMin = GetValue(@IPS_GetObjectIDByName("Tomorrow3TempMin",$todayID));
                            $tomorrow3TempMax = GetValue(@IPS_GetObjectIDByName("Tomorrow3TempMax",$todayID));
                            }		
                        }
                    }    // ende weather aktiviert
                }
            
            if ($weather["todayDate"] != "") { $tableSpare='<td bgcolor="#c1c1c1"></td>'; $colspan='colspan="2" '; }
            else { $tableSpare=''; $colspan=""; }

            $wert.='<td><table id="nested">';
            $wert.='<tr><td '.$colspan.'bgcolor="#c1c1c1"> <img src="user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>';
            $wert.='<td bgcolor="#ffffff"><img src="user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur"></td></tr>';
            $wert.='<tr><td '.$colspan.' bgcolor="#c1c1c1"><aussen>'.number_format($this->aussentemperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($this->innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
            $wert.= '<tr>'.$this->additionalTableLines($colspan).'</tr>';
            if ($weather["todayDate"]=="")
                {
                $wert.= $this->tempTableLine($weather["todayTempMin"], $weather["todayTempMax"], $weather["today"]);
                $wert.= $this->tempTableLine($weather["tomorrowTempMin"], $weather["tomorrowTempMax"], $weather["tomorrow"]);
                $wert.= $this->tempTableLine($weather["tomorrow1TempMin"], $weather["tomorrow1TempMax"], $weather["tomorrow1"]);
                $wert.= $this->tempTableLine($weather["tomorrow2TempMin"], $weather["tomorrow2TempMax"], $weather["tomorrow2"]);
                }
            else
                {
                $wert.= $this->tempTableLine($weather["todayTempMin"], $weather["todayTempMax"], $weather["today"],$weather["todayDate"]);
                $wert.= $this->tempTableLine($weather["tomorrowTempMin"], $weather["tomorrowTempMax"], $weather["tomorrow"], $weather["tomorrowDate"]);
                $wert.= $this->tempTableLine($weather["tomorrow1TempMin"], $weather["tomorrow1TempMax"], $weather["tomorrow1"], $weather["tomorrow1Date"]);
                $wert.= $this->tempTableLine($weather["tomorrow2TempMin"], $weather["tomorrow2TempMax"], $weather["tomorrow2"], $weather["tomorrow2Date"]);
                }
            $wert.='</table></td>';
            return ($wert);
            }

        /********************
         *
         * holt die Wetterdaten, abhängig von der benutzten App sind die Daten auf verschiedenen Orten gespeichert
         *
         **************************************/

		function getWeatherData()
            {
            $result=array();
			$Config=$this->configWeather();
			if ( $Config["Active"] )
				{
                if ($Config["Source"]=="WU")        // WeatherUnderground
                    {
                    $todayID=get_ObjectIDByPath("Program.IPSLibrary.data.modules.Weather.IPSWeatherForcastAT");
                    if ($todayID == false)
                        {
                        //echo "weatherforecast nicht installiert.\n";
                        $noweather=true;
                        }
                    else
                        {
                        $result["today"] = GetValue(@IPS_GetObjectIDByName("TodayIcon",$todayID));
                        $result["todayTempMin"] = GetValue(@IPS_GetObjectIDByName("TodayTempMin",$todayID));
                        $result["todayTempMax"] = GetValue(@IPS_GetObjectIDByName("TodayTempMax",$todayID));
                        $result["tomorrow"] = GetValue(@IPS_GetObjectIDByName("TomorrowIcon",$todayID));
                        $result["tomorrowTempMin"] = GetValue(@IPS_GetObjectIDByName("TomorrowTempMin",$todayID));
                        $result["tomorrowTempMax"] = GetValue(@IPS_GetObjectIDByName("TomorrowTempMax",$todayID));
                        $result["tomorrow1"] = GetValue(@IPS_GetObjectIDByName("Tomorrow1Icon",$todayID));
                        $result["tomorrow1TempMin"] = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMin",$todayID));
                        $result["tomorrow1TempMax"] = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMax",$todayID));
                        $result["tomorrow2"] = GetValue(@IPS_GetObjectIDByName("Tomorrow2Icon",$todayID));
                        $result["tomorrow2TempMin"] = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMin",$todayID));
                        $result["tomorrow2TempMax"] = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMax",$todayID));

                        $result["todayDate"]="";		/* keine Openweather Darstellung, verwendet als Unterscheidung */
                        }
                    }
                else			/* nicht Weather Wunderground, daher Openwewather */
                    {
                    $todayID=get_ObjectIDByPath("Program.IPSLibrary.data.modules.Startpage.OpenWeather");		
                    if ($todayID == false)
                        {
                        //echo "weatherforecast nicht installiert.\n";
                        $noweather=true;
                        }
                    else
                        {
                        //echo "OpenWeatherData mit Daten von $todayID wird verwendet.\n";
                        $result["todayDate"]    = GetValue(@IPS_GetObjectIDByName("TodayDay",$todayID));
                        $result["today"]        = GetValue(@IPS_GetObjectIDByName("TodayIcon",$todayID));
                        $result["todayTempMin"] = GetValue(@IPS_GetObjectIDByName("TodayTempMin",$todayID));
                        $result["todayTempMax"] = GetValue(@IPS_GetObjectIDByName("TodayTempMax",$todayID));
                        $result["tomorrowDate"] = GetValue(@IPS_GetObjectIDByName("TomorrowDay",$todayID));
                        $result["tomorrow"]     = GetValue(@IPS_GetObjectIDByName("TomorrowIcon",$todayID));
                        $result["tomorrowTempMin"] = GetValue(@IPS_GetObjectIDByName("TomorrowTempMin",$todayID));
                        $result["tomorrowTempMax"] = GetValue(@IPS_GetObjectIDByName("TomorrowTempMax",$todayID));
                        $result["tomorrow1Date"] = GetValue(@IPS_GetObjectIDByName("Tomorrow1Day",$todayID));
                        $result["tomorrow1"] = GetValue(@IPS_GetObjectIDByName("Tomorrow1Icon",$todayID));
                        $result["tomorrow1TempMin"] = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMin",$todayID));
                        $result["tomorrow1TempMax"] = GetValue(@IPS_GetObjectIDByName("Tomorrow1TempMax",$todayID));
                        $result["tomorrow2Date"] = GetValue(@IPS_GetObjectIDByName("Tomorrow2Day",$todayID));
                        $result["tomorrow2"] = GetValue(@IPS_GetObjectIDByName("Tomorrow2Icon",$todayID));
                        $result["tomorrow2TempMin"] = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMin",$todayID));
                        $result["tomorrow2TempMax"] = GetValue(@IPS_GetObjectIDByName("Tomorrow2TempMax",$todayID));
                        $result["tomorrow3Date"] = GetValue(@IPS_GetObjectIDByName("Tomorrow3Day",$todayID));
                        $result["tomorrow3"] = GetValue(@IPS_GetObjectIDByName("Tomorrow3Icon",$todayID));
                        $result["tomorrow3TempMin"] = GetValue(@IPS_GetObjectIDByName("Tomorrow3TempMin",$todayID));
                        $result["tomorrow3TempMax"] = GetValue(@IPS_GetObjectIDByName("Tomorrow3TempMax",$todayID));
                        }		
                    }
                }
            return($result);
            }

        /********************
         *
         * macht eine Zeile in der Wettertabelle
         *
         **************************************/

		function tempTableLine($TempMin, $TempMax, $imageSrc, $date="")
			{
			$wert="";
			if ($date=="")
				{
				$wert.='<tr id="temp"><td><temperatur>'.number_format($TempMin, 1, ",", "" ).'°C<br>'.number_format($TempMax, 1, ",", "" ).'°C</temperatur></td>';
				$wert.='<td align="center"> <img src="'.$imageSrc.'" alt="Heute" > </td></tr>';	
				}
			else	
				{
				$wert.='<tr id="temp"><td><datum>'.$date.'</datum></td>';
				$wert.='<td><temperatur>'.number_format($TempMin, 1, ",", "" ).'°C<br>'.number_format($TempMax, 1, ",", "" ).'°C</temperatur></td>';
				$wert.='<td align="center"> <img src="'.$imageSrc.'" alt="Heute" > </td></tr>';			
				}
			return ($wert);
			}
		
        /********************
         *
         * Die Tabelle benötigt einen gemeinsamen Style, diesen hier zusammenfassen
         *
         **************************************/

	    function writeStartpageStyle()
	        {
	    	$wert='<style>';
	        $wert.='kopf { background-color: red; height:120px;  }';        // define element selectors
	        $wert.='strg { height:280px; color:black; background-color: #c1c1c1; font-size: 12em; }';
	        $wert.='innen { color:black; background-color: #ffffff; height:100px; font-size: 80px; }';
	        $wert.='aussen { color:black; background-color: #c1c1c1; bgcolor: #c1c1c1; height:100px; font-size: 80px; }';
	        $wert.='addText { color:black; background-color: #c1c1c1; height:100px; font-size: 24px; align:center; }';
	        $wert.='temperatur { color:black; height:100px; font-size: 28px; align:center; }';
	        $wert.='datum { color:black; height:100px; font-size: 28px; align:center; }';
	        $wert.='infotext { color:white; height:100px; font-size: 12px; }';
		    $wert.='#nested { border-collapse: collapse; border: 2px solid white; background-color: #f1f1f1; width: auto;  }';
	        $wert.='#nested td { border: 1px solid white; }';		  
	        $wert.='#temp td { background-color:#ffefef; }';                // define ID Selectors
	        $wert.='#imgdisp { border-radius: 8px;  max-width: 100%; height: auto;  }';
	        $wert.='#startpage { border-collapse: collapse; border: 2px dotted white; width: 100%; }';
	        $wert.='#startpage td { border: 1px dotted DarkSlateGrey; }';	 
	        $wert.='.container { width: auto; height: auto; max-height:95%; max-width: 100% }';
	        $wert.='.image { opacity: 1; display: block; width: auto; height: auto; max-height: 90%; max-width: 80%; object-fit: contain; transition: .5s ease; backface-visibility: hidden; padding: 5px }';
	        $wert.='.middle { transition: .5s ease; opacity: 0; position: absolute; top: 90%; left: 30%; transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%) }';
	        $wert.='.container:hover .image { opacity: 0.8; }';             // define classes
	        $wert.='.container:hover .middle { opacity: 1; }';
	        $wert.='.text { background-color: #4CAF50; color: white; font-size: 16px; padding: 16px 32px; }';
	        $wert.='</style>';
	        return($wert);
	        }
	
		/*************************************************************************************************
	     *
	     * Funktion für OpenweatherMap Darstellung
	     *
	     *
	     */
	
		function findIcon($cloudy,$rainy)
			{
			$icon="user/IPSWeatherForcastAT/icons/";
			if ($cloudy < 11)
				{
				if ($rainy>0) $icon.="chance_of_rain";
				else $icon.="sunny";			}
			elseif ($cloudy < 25)
				{
				if ($rainy>0) $icon.="chance_of_rain";
				else $icon.="mostly_sunny";			
				}								
			elseif ($cloudy < 51)
				{			
				if ($rainy>0) $icon.="chance_of_rain";
				else $icon.="partly_cloudy";
				}								
			elseif ($cloudy < 85)
				{
				if ($rainy<20) $icon.="mostly_cloudy";
				elseif ($rainy<50) $icon.="chance_of_rain";
				elseif ($rainy<70) $icon.="showers";
				else $icon.="rain";
				}
			else
				{
				if ($rainy<20) $icon.="cloudy";			
				elseif ($rainy<50) $icon.="chance_of_rain";
				elseif ($rainy<70) $icon.="showers";
				else $icon.="rain";
				}																		
			return ($icon.".png");
			}
	
		/*
		 * finde zu einem Begriff die komplette Serie von Variablen 
		 *
		 */
	
		function findeVarSerie($find="Beginn",$OWD)
			{
			$gefunden=false;
			//echo "Instanz : ".$OWD."   ".IPS_GetName($OWD)."\n";
			$childrens=IPS_GetChildrenIDs($OWD);
			foreach($childrens as $children)
				{
				//echo "Vergleiche ".IPS_GetName($children)."\n";
				if ( (strpos(IPS_GetName($children),$find)) !== false ) 
					{
					$varName=explode("#",IPS_GetName($children));
					if (sizeof($varName)>1)
						{
						//print_r($varName);
						$index=str_pad($varName[1], 2 ,'0', STR_PAD_LEFT);
						//echo $index."  ";
						$gefunden[$index]["Wert"]=GetValue($children);
						$gefunden[$index]["Name"]=IPS_GetName($children);
						}
					}
				}
			return($gefunden);
			}
	
		/* 
		 * den letzten Treffer in dem der Auszug von dem Parameter "finde" im Variablennamen enthalten ist ausgeben
		 *
		 *
		 */
		
		function findeVariableName($find="Beginn",$OWD)
			{
			$gefunden=false;
			//echo "Instanz : ".$OWD."   ".IPS_GetName($OWD)."\n";
			$childrens=IPS_GetChildrenIDs($OWD);
			foreach($childrens as $children)
				{
				//echo "Vergleiche ".IPS_GetName($children)."\n";
				$pos=strpos(IPS_GetName($children),$find);
				if ( $pos !== false ) $gefunden=$children;
				}
			return($gefunden);
			}	
	
		function aggregateOpenWeather($debug=false)
			{
			$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			$moduleManagerSP = new IPSModuleManager('StartPage',$repository);
			$CategoryIdDataSP     = $moduleManagerSP->GetModuleCategoryID('data');
		    $categoryId_OpenWeather = CreateCategory('OpenWeather',   $CategoryIdDataSP, 2000);
		
			// Create Variable IDs
			$LastRefreshDateTime     = IPS_GetObjectIDByName("LastRefreshDateTime",  $categoryId_OpenWeather);
			$LastRefreshTime         = IPS_GetObjectIDByName("LastRefreshTime"   ,  $categoryId_OpenWeather);
			$TodaySeaLevel           = IPS_GetObjectIDByName("SeaLevel"          , $categoryId_OpenWeather);
			$TodayAirHumidity        = IPS_GetObjectIDByName("AirHumidity",  $categoryId_OpenWeather);
			$TodayWind               = IPS_GetObjectIDByName("Wind",         $categoryId_OpenWeather);
	
			$TodayDayOfWeek          = IPS_GetObjectIDByName("TodayDay",                 $categoryId_OpenWeather);
			$TodayTempCurrent        = IPS_GetObjectIDByName("TodayTempCurrent",        $categoryId_OpenWeather);
			$TodayTempMin            = IPS_GetObjectIDByName("TodayTempMin",            $categoryId_OpenWeather);
			$TodayTempMax            = IPS_GetObjectIDByName("TodayTempMax",            $categoryId_OpenWeather);
			$TodayIcon               = IPS_GetObjectIDByName("TodayIcon",                $categoryId_OpenWeather);
			$TodayTextShort          = IPS_GetObjectIDByName("TodayForecastLong",        $categoryId_OpenWeather);
			$TodayTextLong           = IPS_GetObjectIDByName("TodayForecastShort",       $categoryId_OpenWeather);
	
			$Forecast1DayOfWeek       = IPS_GetObjectIDByName("TomorrowDay",             $categoryId_OpenWeather);
			$Forecast1TempMin         = IPS_GetObjectIDByName("TomorrowTempMin",        $categoryId_OpenWeather);
			$Forecast1TempMax         = IPS_GetObjectIDByName("TomorrowTempMax",        $categoryId_OpenWeather);
			$Forecast1TextShort       = IPS_GetObjectIDByName("TomorrowForecastLong",    $categoryId_OpenWeather);
			$Forecast1TextLong        = IPS_GetObjectIDByName("TomorrowForecastShort",   $categoryId_OpenWeather);
			$Forecast1Icon            = IPS_GetObjectIDByName("TomorrowIcon",            $categoryId_OpenWeather);
	
			$Forecast2DayOfWeek       = IPS_GetObjectIDByName("Tomorrow1Day",            $categoryId_OpenWeather);
			$Forecast2TempMin         = IPS_GetObjectIDByName("Tomorrow1TempMin",       $categoryId_OpenWeather);
			$Forecast2TempMax         = IPS_GetObjectIDByName("Tomorrow1TempMax",       $categoryId_OpenWeather);
			$Forecast2TextShort       = IPS_GetObjectIDByName("Tomorrow1ForecastLong",   $categoryId_OpenWeather);
			$Forecast2TextLong        = IPS_GetObjectIDByName("Tomorrow1ForecastShort",  $categoryId_OpenWeather);
			$Forecast2Icon            = IPS_GetObjectIDByName("Tomorrow1Icon",           $categoryId_OpenWeather);
	
			$Forecast3DayOfWeek       = IPS_GetObjectIDByName("Tomorrow2Day",            $categoryId_OpenWeather);
			$Forecast3TempMin         = IPS_GetObjectIDByName("Tomorrow2TempMin",       $categoryId_OpenWeather);
			$Forecast3TempMax         = IPS_GetObjectIDByName("Tomorrow2TempMax",       $categoryId_OpenWeather);
			$Forecast3TextShort       = IPS_GetObjectIDByName("Tomorrow2ForecastLong",   $categoryId_OpenWeather);
			$Forecast3TextLong        = IPS_GetObjectIDByName("Tomorrow2ForecastShort",  $categoryId_OpenWeather);
			$Forecast3Icon            = IPS_GetObjectIDByName("Tomorrow2Icon",           $categoryId_OpenWeather);
		
			$Forecast4DayOfWeek       = IPS_GetObjectIDByName("Tomorrow3Day",            $categoryId_OpenWeather);
			$Forecast4TempMin         = IPS_GetObjectIDByName("Tomorrow3TempMin",       $categoryId_OpenWeather);
			$Forecast4TempMax         = IPS_GetObjectIDByName("Tomorrow3TempMax",       $categoryId_OpenWeather);
			$Forecast4TextShort       = IPS_GetObjectIDByName("Tomorrow3ForecastLong",   $categoryId_OpenWeather);
			$Forecast4TextLong        = IPS_GetObjectIDByName("Tomorrow3ForecastShort",  $categoryId_OpenWeather);
			$Forecast4Icon            = IPS_GetObjectIDByName("Tomorrow3Icon",           $categoryId_OpenWeather);
	
			if ($debug) echo "Satz von Variablen für die Startpage ist auf $categoryId_OpenWeather.\n";
	
			$WU_today_id=get_ObjectIDByPath("Program.IPSLibrary.data.modules.Weather.IPSWeatherForcastAT");
			$OW_today_id=get_ObjectIDByPath("Program.IPSLibrary.data.modules.Startpage.OpenWeather");
			if ($debug) echo "WU Today ID ist : ".$WU_today_id."    OW Today ist ".$OW_today_id."\n";
	
		    $result=array();
	    	//foreach ($this->OWDs as $OWD) $result[$OWD]=OpenWeatherData_UpdateHourlyForecast($OWD);
			foreach ($this->OWDs as $OWD) $result[$OWD]=OpenWeatherData_UpdateData($OWD);			// gleich alles updaten
		    //print_r($result);
			if ($debug) echo "\n"; 
		    $startTime=time();
		    $endTime=time();
			$daily=array();
			foreach ($this->OWDs as $OWD)
				{
				$beginn=$this->findeVarSerie("Beginn",$OWD);
				$minimale=$this->findeVarSerie("minimale",$OWD);
				$maximale=$this->findeVarSerie("maximale",$OWD);
				$clouds=$this->findeVarSerie("Bew",$OWD);
				$rains=$this->findeVarSerie("Regenm",$OWD);
	            $symbols=$this->findeVarSerie("Wetterbedingung-Sym",$OWD);
				//print_r($clouds);
				if ($debug) echo "Beginn des Vorhersagezeitraums ist : \n";
				ksort($beginn);
				//foreach ($minimale as $index => $value)  echo "   ".$index."  =>   ".$value["Name"]." = ".$value["Wert"]."\n";
				//print_r($gefunden);
				foreach ($beginn as $index => $value)
					{
					$day=date("d",$value["Wert"]);
					if (isset($daily[$day]))
						{
						if ($daily[$day]["minTemp"]>$minimale[$index]["Wert"]) $daily[$day]["minTemp"]=$minimale[$index]["Wert"];
						if ($daily[$day]["maxTemp"]<$maximale[$index]["Wert"]) $daily[$day]["maxTemp"]=$maximale[$index]["Wert"];				
						$daily[$day]["CloudSum"]+=$clouds[$index]["Wert"];
						$daily[$day]["Count"]++;
						$daily[$day]["Rains"]+=$rains[$index]["Wert"];
						if ($rains[$index]["Wert"] > 0) $daily[$day]["RainPeriode"]++;
						}
					else
						{
						$daily[$day]["Date"]=date("D d",$value["Wert"]);;
						$daily[$day]["minTemp"]=$minimale[$index]["Wert"];
						$daily[$day]["maxTemp"]=$maximale[$index]["Wert"];
						$daily[$day]["CloudSum"]=$clouds[$index]["Wert"];
						$daily[$day]["Count"]=1;
						$daily[$day]["Rains"]=$rains[$index]["Wert"];
						if ($rains[$index]["Wert"] > 0) $daily[$day]["RainPeriode"]=1;
						else $daily[$day]["RainPeriode"]=0;
						}
					
					if ($debug) echo "   ".$index."  =>   ".str_pad($value["Name"],50)." = ".date("D d H:i",$value["Wert"])." : ".str_pad($minimale[$index]["Wert"]." bis ".$maximale[$index]["Wert"]." °C ",30)."Bewölkung ".$clouds[$index]["Wert"]."%\n";
	    	        if ($value["Wert"]>$endTime) $endTime=$value["Wert"];
					}
				}
	    	if ($debug) { echo "Zeitreihe verfügbar von ".date("d H:i",$startTime)." bis ".date("d H:i",$endTime).".\n"; print_r($daily); }
	
			$i=0;
			foreach ($daily as $day)
				{
				$cloudy = $day["CloudSum"]/$day["Count"];
				$rainy  = $day["RainPeriode"]/$day["Count"]*100;
				switch ($i)
					{
					case 0:
						if ($debug) echo "Heute  : Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".$cloudy."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).". \n";
						SetValue($TodayDayOfWeek,$day["Date"]);
						SetValue($TodayTempMin,$day["minTemp"]);
						SetValue($TodayTempMax,$day["maxTemp"]);
						SetValue($TodayIcon,$this->findIcon($cloudy,$rainy));
						break;
					case 1:
						if ($debug) echo "Morgen : Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".($cloudy)."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).".\n";
						SetValue($Forecast1DayOfWeek,$day["Date"]);
						SetValue($Forecast1TempMin,$day["minTemp"]);
						SetValue($Forecast1TempMax,$day["maxTemp"]);
						SetValue($Forecast1Icon,$this->findIcon($cloudy,$rainy));
						break;
					case 2:
						if ($debug) echo "Morgen1: Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".($cloudy)."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).".\n";
						SetValue($Forecast2DayOfWeek,$day["Date"]);
						SetValue($Forecast2TempMin,$day["minTemp"]);
						SetValue($Forecast2TempMax,$day["maxTemp"]);
						SetValue($Forecast2Icon,$this->findIcon($cloudy,$rainy));
						break;
					case 3:
						if ($debug) echo "Morgen2: Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".($cloudy)."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).".\n";
						SetValue($Forecast3DayOfWeek,$day["Date"]);
						SetValue($Forecast3TempMin,$day["minTemp"]);
						SetValue($Forecast3TempMax,$day["maxTemp"]);
						SetValue($Forecast3Icon,$this->findIcon($cloudy,$rainy));
						break;
					case 4:
						if ($debug) echo "Morgen3: Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".($cloudy)."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).".\n";
						SetValue($Forecast4DayOfWeek,$day["Date"]);
						SetValue($Forecast4TempMin,$day["minTemp"]);
						SetValue($Forecast4TempMax,$day["maxTemp"]);
						SetValue($Forecast4Icon,$this->findIcon($cloudy,$rainy));
						break;		
					}
				$i++;
				}
	
	        /* zusaetzlich auch ein huebsches Meteogram erstellen */
	
	        $variableIdMeteoChartHtml   = IPS_GetObjectIDByName("OpenWeatherMeteoHTML", $categoryId_OpenWeather);
	
		    $CfgDaten=array();
		    $CfgDaten['ContentVarableId'] = $variableIdMeteoChartHtml;
	        $CfgDaten['HighChart']['Theme']="ips.js";   // IPS-Theme muss per Hand in in Themes kopiert werden....
	
		    $CfgDaten['StartTime']        = $startTime;
		    $CfgDaten['EndTime']          = $endTime;
	
		    $CfgDaten['RunMode'] = "file";     // file nur statisch über .tmp,     script, popup  ist interaktiv und flexibler
	
	        // Abmessungen des erzeugten Charts
	        $CfgDaten['HighChart']['Width'] = 0;             // in px,  0 = 100%
	        $CfgDaten['HighChart']['Height'] = 600;         // in px
	
		    $CfgDaten['title']['text']    = "Meteogram, Darstellung komplett";
		    $CfgDaten['subtitle']['text'] = "Dargestellter Zeitraum: %STARTTIME% - %ENDTIME%";
		    $CfgDaten['subtitle']['Ips']['DateTimeFormat'] = "(D) d.m.Y H:i";	
	
	
			/* Debugging Einstellungen, werden automatisch von CheckCfgDaten hinzugefügt, wenn Abänderung auf true gibt es Funktion  
			$CfgDaten['Ips']['Debug']['Modules'] = false;
			$CfgDaten['Ips']['Debug']['ShowJSON'] = false;
			$CfgDaten['Ips']['Debug']['ShowJSON_Data'] = false;
			$CfgDaten['Ips']['Debug']['ShowCfg']   = false;
			$CfgDaten['Ips']['ChartType'] = 'Highcharts';
																			*/
			
			$CfgDaten['xAxis']['type'] = "datetime";
			$CfgDaten['xAxis']['plotBands']['from'] = "@" . $this->CreateDateUTC($startTime+60*60*24) ."@";
			$CfgDaten['xAxis']['plotBands']['to'] = "@" . $this->CreateDateUTC($endTime-60*60*24) ."@";
			$CfgDaten['xAxis']['plotBands']['color'] = '#2F2F2F';
			$CfgDaten['xAxis']['plotBands']['label']['text'] = 'Montag';
	
	    	/******************************************************************************************
             *
		     * mit einem Linechart für Min/Max Temperatur und den Wettersymbolen beginnen (series=0,1,2 und yaxis=0)
	    	 *
		     */

	    	$CfgDaten['plotOptions']['spline']['color']     =	 '#FF0000';
		    $series=0;
	
	    	$CfgDaten['yAxis'][0]['title']['text'] = "Temperatur";
		    $CfgDaten['yAxis'][0]['Unit'] = "°";    
	    	$CfgDaten['yAxis'][0]['min'] = -10;
		    $CfgDaten['yAxis'][0]['minrange'] = 8;
	    	$CfgDaten['yAxis'][0]['opposite']=false;
	
	    	$CfgDaten['series'][$series]['type'] = 'line';
		    $CfgDaten['series'][$series]['ScaleFactor'] = 1;
	    	$CfgDaten['series'][$series]['name']        = 'Minimale Temperatur';
		    $CfgDaten['series'][$series]['Unit'] = '';
	    	$CfgDaten['series'][$series]['yAxis']         = 0;
		    $CfgDaten['series'][$series]['visible']       = true;
	        $CfgDaten['series'][$series]['marker']['symbol']       = 'square';
	
		    $CfgDaten['series'][$series+1]['type'] = 'line';
	    	$CfgDaten['series'][$series+1]['ScaleFactor'] = 1;
		    $CfgDaten['series'][$series+1]['name']        = 'Maximale Temperatur';
	        $CfgDaten['series'][$series+1]['Unit'] = '';
	    	$CfgDaten['series'][$series+1]['yAxis']         = 0;
		    $CfgDaten['series'][$series+1]['color']         = '#FF0000';
	    	$CfgDaten['series'][$series+1]['visible']       = true;
	
	    	$CfgDaten['series'][$series+2]['type'] = 'line';
		    $CfgDaten['series'][$series+2]['ScaleFactor'] = 1;
		    $CfgDaten['series'][$series+2]['name']        = 'Symbole';
	    	$CfgDaten['series'][$series+2]['Unit'] = '';
		    $CfgDaten['series'][$series+2]['yAxis']         = 0;
	    	$CfgDaten['series'][$series+2]['color']         = '#101010';
	    	$CfgDaten['series'][$series+2]['visible']       = true;
	        $CfgDaten['series'][$series+2]['opacity']       = 0;
	
	        /* Series benötigt Timestamp/y als Keys sonst wird nicht richtig umgesetzt
	         */
            $tempforWeekdays=0;          
		    foreach ($beginn as $index => $value)
			    {
                if ($maximale[$index]["Wert"]>$tempforWeekdays) $tempforWeekdays=$maximale[$index]["Wert"];
	            $CfgDaten['series'][$series]['data'][] = ["TimeStamp" => $value["Wert"],"y" => $minimale[$index]["Wert"]];
	            $CfgDaten['series'][$series+1]['data'][] = ["TimeStamp" => $value["Wert"],"y" => $maximale[$index]["Wert"]];
	            $CfgDaten['series'][$series+2]['data'][] = ["TimeStamp" => $value["Wert"],"y" => ($maximale[$index]["Wert"]+2),"marker" => ["symbol" => 'url(http://openweathermap.org/img/w/'.$symbols[$index]["Wert"].'.png)']];
	    		}
		
	    	/*******************************************************************************************************************************************
	         *
		     * und dann mit einem Barchart für die Regenmenge, series=3, yaxis=1
		     *
	    	 */
	
	    	$CfgDaten['plotOptions']['column']['color']     =	 '#0000AA';     // blau
		    $series=3;
	
	    	$CfgDaten['yAxis'][1]['title']['text'] = "Regenmenge";
		    $CfgDaten['yAxis'][1]['Unit'] = 'mm';    
	    	$CfgDaten['yAxis'][1]['min'] = 0;
	    	$CfgDaten['yAxis'][1]['minRange'] = 3;
            
            //$CfgDaten['yAxis'][1]['floor'] = 3;           /* y Achse zumindest für 3 mm auslegen, damit werden die Niedeeschlagsmengen im Bedarfsfall nicht so unverhältnismaessig gross */
	    	//$CfgDaten['yAxis'][1]['max'] = 3;
            //$CfgDaten['yAxis'][1]['ceiling'] = 3;

		    $CfgDaten['yAxis'][1]['opposite']=true;
	
	    	$CfgDaten['series'][$series]['type'] = 'column';
			$CfgDaten['series'][$series]['ScaleFactor'] = 1;
		    $CfgDaten['series'][$series]['name']        = 'Regen';
	    	$CfgDaten['series'][$series]['Unit'] = '';
		    $CfgDaten['series'][$series]['yAxis']         = 1;
	    	$CfgDaten['series'][$series]['visible']       = true;
	
		    foreach ($beginn as $index => $value)
			    {
	            $CfgDaten['series'][$series]['data'][] = ["TimeStamp" => $value["Wert"],"y" => $rains[$index]["Wert"]];
			    }
	
	    	/******************************************************************************************************** 
             *
		     * mit einem Linechart für die Wochentage weitermachen (series=4 und yaxis=0)
	    	 *
		     */
	    	$CfgDaten['plotOptions']['spline']['color']     =	 '#FF0000';
		    $series=4;
	
	    	$CfgDaten['series'][$series]['type'] = 'line';
		    $CfgDaten['series'][$series]['ScaleFactor'] = 1;
	    	$CfgDaten['series'][$series]['name']        = 'Wochentage';
		    $CfgDaten['series'][$series]['Unit'] = '';
	    	$CfgDaten['series'][$series]['yAxis']         = 0;
	    	$CfgDaten['series'][$series]['color']         = '#101010';
		    $CfgDaten['series'][$series]['visible']       = true;
	        $CfgDaten['series'][$series]['opacity']       = 50;
	
	        /* Series benötigt Timestamp/y als Keys sonst wird nicht richtig umgesetzt
	         */
            $tempforWeekdays+=5;    /* 5 Grad noch dazu damit sich oben eine Wochentagsliste ausgeht */
		    foreach ($beginn as $index => $value)
			    {
                $stunde=(integer)date("H",$value["Wert"]);
                if ($stunde<4)  // je nach Sommer oder Winterzeit 2 oder 3
                    {
                    //echo "Zeitstempel : ".$stunde."\n";
                    $CfgDaten['series'][$series]['data'][] = ["Name" => "hallo","TimeStamp" => $value["Wert"],"y" => $tempforWeekdays];
                    $i=1;
                    }
	    		}

	    	// Create Chart with Config File
	  	    IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");
	
	      	$CfgDaten    = CheckCfgDaten($CfgDaten);
		    //print_r($CfgDaten);

		    //echo "Create Config String.\n";
		    $sConfig     = CreateConfigString($CfgDaten);
	  
	  	    $tmpFilename = CreateConfigFile($sConfig, 'Openweather');
	      	WriteContentWithFilename ($CfgDaten, $tmpFilename);
	
		    //echo "Filename fuer Aufruf :".$tmpFilename."\n";
	
	    	$s = explode("|||" , $sConfig);
		
		    if (count($s) >= 2)
			    {
			    $TempString = trim($s[0],"\n ");
	    		$JavaScriptConfigForHighchart = $s[1];	
			
		    	$LangOptions="lang: {}";
			    if (count($s) > 2)	$LangOptions = trim($s[2],"\n ");
			
	    		// aus den Daten ein schönes Array machen
		    	$TempStringArr = explode("\n", $TempString);
			    foreach($TempStringArr as $Item)
				    {
	    			$KeyValue = explode("=>", $Item);
		    		$AdditionalConfigData[trim($KeyValue[0]," []")] = trim($KeyValue[1]," ");
			    	}
			
	    		// Verzeichnis + Theme
		    	if ($AdditionalConfigData['Theme'] != '')
			    	$AdditionalConfigData['Theme']= '/user/IPSHighcharts/Highcharts/js/themes/' . $AdditionalConfigData['Theme'];
	
			    }
			return($CfgDaten);
			}

		// ------------------------------------------------------------------------
		// CreateDateUTC, hier in der Klasse noch einmal definiert, gibt es in IPSHighcharts.inc
		//    Erzeugen des DateTime Strings für Highchart-Config
		//    IN: $timeStamp = Zeitstempel
		//    OUT: Highcharts DateTime-Format als UTC String ... Date.UTC(1970, 9, 27, )
		//       Achtung! Javascript Monat beginnt bei 0 = Januar
		// ------------------------------------------------------------------------
		function CreateDateUTC($timeStamp)
			{
			$monthForJS = ((int)date("m", $timeStamp))-1 ;	// Monat -1 (PHP->JS)
			return "Date.UTC(" . date("Y,", $timeStamp) .$monthForJS. date(",j,H,i,s", $timeStamp) .")";
			}
			
			
	    function additionalTableLines($format="")
	        {
	        $wert="";
	        if ( (isset($this->configuration["Display"]["AddLine"])) && (sizeof($this->configuration["Display"]["AddLine"])>0) )
	            {
	            foreach($this->configuration["Display"]["AddLine"] as $tablerow)
	                {
	                //echo "   Eintrag : ".$tablerow["Name"]."  ".$tablerow["OID"]."  ".$tablerow["Icon"]."\n";
	    			$wert.='<td '.$format.' bgcolor="#c1c1c1"><addText>'.$tablerow["Name"].'</addText></td><td  bgcolor="#c1c1c1"><addText>'.number_format(GetValue($tablerow["OID"]), 1, ",", "" ).'°C</addtext></td>';
	                }
	            //print_r($cthis->onfiguration["AddLine"]);
				//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
	            //echo $wert;
	            }
	        return ($wert);
	        }
	
	    function bottomTableLines()
	        {
	        $wert="";
	        if ( (isset($this->configuration["Display"]["BottomLine"])) && (sizeof($this->configuration["Display"]["BottomLine"])>0) )
	            {
	            $wert.='<tr>';
	            foreach($this->configuration["Display"]["BottomLine"] as $tableEntry)
	                {
	                //echo "   Eintrag : ".$tablerow["Name"]."  ".$tablerow["OID"]."  ".$tablerow["Icon"]."\n";
	    			$wert.='<td><addText>'.$tableEntry["Name"].'</addText></td><td><addText>'.number_format(GetValue($tableEntry["OID"]), 1, ",", "" ).'°C</addtext></td>';
	                }
	            $wert.='</tr>';
	            //print_r($this->configuration["AddLine"]);
				//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
	            //echo $wert;
	            }
	        return ($wert);            
	        }

		/*
		 * OpenWeatherTable generiert ein html file
		 * dieses html Objekt regelmaessig als File in der Startpage abspeichern
		 *
		 *
		 *
		 */

		function writeOpenweatherSummarytoFile()
			{
			$html    = '<?php'."\n";             // Anfang für das Include als iFrame, hier könnte der php code stehen
			$html   .= "\n".'?>'."\n";             // Abschluss für das Include als iFrame					
			$html   .= '<html><body>'."\n";
			foreach ($this->OWDs as $OWD)
				{
				$html .= GetValue($this->findeVariableName("Zusammenfassung",$OWD))."\n\n";
				}
			$html    .= "\n".'</body>'."\n".'</html>';             // Abschluss für das Include als iFrame					
			$filename=IPS_GetKernelDir().'webfront\user\Startpage\Startpage_Openweather.php';
			if (!file_put_contents($filename, $html)) {
		        throw new Exception('Create File '.$filename.' failed!');
		    		}			
			
			}


		}		// ende class
		
		


?>