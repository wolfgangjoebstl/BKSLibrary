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
     * Hauptroutine ist StartPageWrite mit den 4 unterschiedlichen Darstellungen
     *
     * _construct
     * getWorkDirectory
     * setStartpageConfiguration
     * getStartpageConfiguration
     * getOWDs
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
     * Startpage_Refresh
     *
     */

	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	class StartpageHandler 
		{

		private $configuration = array();				// die angepasste, standardisierte Konfiguration
		private $aussentemperatur, $innentemperatur;
		
		public $picturedir, $imagedir, $icondir;			// hier sind alle Bilder für die Startpage abgelegt
        public $workdir;            // Arbeitsverzeichnis, zB VLC Start Scripts

        protected $scriptHighchartsID;                      // für Higcharts, die IPSHighcharts script ID
        private $contentID;                             // für Highcharts als Dummy
        private $installedModules;                      // welche Module sind installiert

		public $CategoryIdData, $CategoryIdApp;			// die passenden Verzeichnisse
		
		private $OWDs;				// alle Openweather Instanzen

		/**
		 * @public
		 *
		 * Initialisierung des IPSMessageHandlers
		 *
		 */
		public function __construct($debug=false)
			{
			/* standardize configuration */
			
			$this->configuration=$this->setStartpageConfiguration($debug);
			
			/* get Directories */

			$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			$moduleManager = new IPSModuleManager('Startpage',$repository);
	        $this->installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
            //print_R($installedModules);

			$moduleManagerHC = new IPSModuleManager('IPSHighcharts',"");
            $categoryHighchartsID = $moduleManagerHC->GetModuleCategoryID('app');	
            $this->scriptHighchartsID = @IPS_GetScriptIDByName("IPSHighcharts", $categoryHighchartsID);
            //echo "StartpageHandler, construct, Highcharts App Category : $categoryHighchartsID and ScriptID : $this->scriptHighchartsID\n";

			$this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
			$this->CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');		
			
			$this->picturedir = IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\pictures\\";
            $this->imagedir   = IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\images\\";                   // Astronomy Path to Moon Pic: user/Startpage/user/images/mond/mond357.gif
            $this->icondir    = IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\icons\\";

			$this->contentID=CreateVariable("htmlChartTable",3, $this->CategoryIdData,0,"~HTMLBox",null,null,"Graph");

            $dosOps = new dosOps();
            $verzeichnis=$dosOps->getWorkDirectory();
            if ($verzeichnis===false) echo "Fehler, Work directory nicht verfügbar. bitte erstellen.\n";
            else
                {
                $this->workdir = $verzeichnis."process/";
                if (is_dir($this->workdir))
                    {
                    }
                else
                    {
                    mkdir($this->workdir);	
                    }
                }
											
			$modulhandling = new ModuleHandling();		// true bedeutet mit Debug
			$this->OWDs=$modulhandling->getInstances('OpenWeatherData');
			}


		/*
		 * Abstrahierung der Startpage Konfiguration
		 *
		 */

        function getWorkDirectory()
            {
            return($this->workdir);
            }

		/*
		 * Abstrahierung der Startpage Konfiguration
		 * Einlesen aus der Datei und Abspeichern in der Class
		 */

		function setStartpageConfiguration($debug=false)
	        {
            $config=array(); $configInput=array();
            $dosOps = new dosOps();
            $systemDir     = $dosOps->getWorkDirectory(); 

            if ((function_exists("startpage_configuration"))===false) IPSUtils_Include ("Startpage_Configuration.inc.php","IPSLibrary::config::modules::Startpage");				
            if (function_exists("startpage_configuration"))  $configInput = startpage_configuration();
            else echo "*************Fehler, Startpage Konfig File nicht included oder Funktion startpage_configuration() nicht vorhanden. Es wird mit Defaultwerten gearbeitet.\n";

            if ($debug) 
                {
                echo "setStartpageConfiguration aufgerufen. Eingelesene Konfiguration:";
                print_R($configInput);
                }

            /* Root der Konfig durchgehen, es wird das ganze Unterverzeichnis übernommen */
            configfileParser($configInput, $config, ["Directories"],"Directories","[]");                // null es wird als Default zumindest ein Indexknoten angelegt
            configfileParser($configInput, $config, ["Display"],"Display","[]");    
            configfileParser($configInput, $configWidget, ["Widgets"],"Widgets","[]");                  // wenn Subverarbeitung ansteht dann leeres Array
            configfileParser($configInput, $config, ["Monitor"],"Monitor",null); 

            /* Sub Directories */
            configfileParser($configInput["Directories"], $config["Directories"], ["Pictures"],"Pictures",null);                // null es wird als Default zumindest ein Indexknoten angelegt
            if (strpos($config["Directories"]["Pictures"],"C:/Scripts/")===0) 
                {
                $config["Directories"]["Pictures"]=substr($config["Directories"]["Pictures"],10);      // Workaround für C:/Scripts"
                $config["Directories"]["Pictures"] = $dosOps->correctDirName($systemDir.$config["Directories"]["Pictures"]);
                }
            configfileParser($configInput["Directories"], $config["Directories"], ["Images"],"Images",null);                // null es wird als Default zumindest ein Indexknoten angelegt
            configfileParser($configInput["Directories"], $config["Directories"], ["Icons"],"Icons",null);                      // null es wird als Default zumindest ein Indexknoten angelegt
            configfileParser($configInput["Directories"], $config["Directories"], ["Scripts"],"Scripts",null);                // null es wird als Default zumindest ein Indexknoten angelegt
            if (strpos($config["Directories"]["Scripts"],"C:/Scripts/")===0) $config["Directories"]["Scripts"]=substr($config["Directories"]["Scripts"],10);      // Workaround für C:/Scripts"
            $config["Directories"]["Scripts"] = $dosOps->correctDirName($systemDir.$config["Directories"]["Scripts"]);

            /* Sub Display */
            configfileParser($configInput["Display"], $config["Display"], ["Weather"],"Weather","[]"); 
            configfileParser($configInput["Display"], $config["Display"], ["BottomLine"],"BottomLine","[]"); 
            configfileParser($configInput["Display"], $config["Display"], ["WidgetStyle"],"WidgetStyle",'{"RowMax":2,"ColMax":3,"Screens":1}');             // bereits als json_encode übergeben

            /* Sub Sub Display */
            configfileParser($configInput["Display"]["Weather"], $config["Display"]["Weather"], ["Weathertable"],"Weathertable","Active"); 
            configfileParser($configInput["Display"]["WidgetStyle"], $config["Display"]["WidgetStyle"], ["RowMax"],"RowMax",2); 
            configfileParser($configInput["Display"]["WidgetStyle"], $config["Display"]["WidgetStyle"], ["ColMax"],"ColMax",3); 
            configfileParser($configInput["Display"]["WidgetStyle"], $config["Display"]["WidgetStyle"], ["Screens"],"Screens",1); 


            /* Sub Widgets */
            $config["Widgets"] = $this->transformConfigWidget($configWidget["Widgets"],$config["Display"]["WidgetStyle"], $debug);       // mit oder ohne Debug, return output array, input array, widget config

            /* 
            configfileParser($configInput, $config, ["Test"],"Test",null); 
            configfileParser($configInput["Test"], $config["Test"], ["Subtest"],"Subtest","Active"); 
            */
                
            if ($debug && true) 
                {
                echo "==============================\n";
                print_R($config);
                echo "==============================\n";
                }
	        return ($config);
	        }

        /* Konfiguration gekapselt */

		function getStartpageConfiguration()
	        {
 	        return ($this->configuration);
	        }

		function getStartpageDisplayConfiguration()
	        {
 	        return ($this->configuration["Display"]);
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
         * Weathertable mit externer Frage deaktivieren:
         *	"Display"    => array (
		 *		"Weathertable"	=> 'InActive', 		// Active or InActive 
		 *							),	
		 *
		 *********************************************************************************************/

		function configWeather($debug=false)
			{
            if ($debug) echo "configWeather aufgerufen.\n";                
			$weather=array();
			$weather["Active"]=false;           // keine externen Wetterdaten verwenden
			$weather["Source"]="WU";
			if ( isset ($this->configuration["Display"]["Weathertable"]) == true ) 
                { 
                if ( $this->configuration["Display"]["Weathertable"] == "Active" ) { $weather["Active"]=true; } 
                }
			if ( isset ($this->configuration["Display"]["Weather"]) == true ) 
				{
				/* mehr Parameter verfügbar */
				if ( isset ($this->configuration["Display"]["Weather"]["Weathertable"]) == true ) 
                    { 
                    if ( $this->configuration["Display"]["Weather"]["Weathertable"] == "Active" ) { $weather["Active"]=true; } 
                    }
				if ( isset ($this->configuration["Display"]["Weather"]["WeatherSource"]) == true ) 
                    { 
                    if ( $this->configuration["Display"]["Weather"]["WeatherSource"] != "WunderGround" ) 
                        { 
                        $weather["Source"]="OWD";
                        if (count($this->getOWDs())==0) $weather["Active"]=false; 
                        }
                    elseif ( (isset($this->installedModules["IPSWeatherForcastAT"])) === false ) $weather["Active"]=false; 
                    }
                else $weather["Active"]=false;
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


        /* StartPageWrite, die Startpage vollständig schreiben, erstellt eine html Tabelle
         *
         * Parameter:
         *       PageType    4 Hierarchie, 3 Topologie, 2 Station, 1 Picture
         *       Showfile
         *
         * aufgerufen werden dazu analog die folgenden Funktionen:   showHierarchy, [showPictureWidget,showTopology], [showDisplayStation, bottomTableLines], [showPictureWidget,showWeatherTemperatureWidget,bottomTableLines]
         *
         * Bei PageType Picture erfolgt eine zweispaltige Tabelle, mit links einem Bild aus der Library, es gibt auch eine Bottomline
         *    Aufruf der folgenden Module:   showPictureWidget($showfile), showWeatherTemperatureWidget(), showWeatherTable(), bottomTableLines() 
         *
         * Bei PageType Station erfolgt eine vorerst fixe 3 spaltige und 2 zeilige Tabelle auf der einzelnen Widgets platziert werden
         * die Platzierung ist vorerst statisch kann aber konfiguriert werden
         *
         * noch abhängig vom Noweather Parameter
         *
         *
         */

		function StartPageWrite($PageType,$showfile=false,$debug=false)
			{
			$Config=$this->configWeather();
			$noweather=!$Config["Active"];
            if ($debug)
                {
                echo "StartPageWrite aufgerufen für Seite $PageType:\n";
                //secho "Weather Konfiguration: ".json_encode($Config)."\n";
                }                
	    	/* html file schreiben, Anfang Style für alle gleich */
			$wert="";
		    $wert.= $this->writeStartpageStyle();
            switch ($PageType)
                {
                case 4:        // Hierarchie
                    if ($debug) echo "Page Type Style is Hierarchy.\n";
                    //echo "Hierarchiedarstellung erster Entwurf, verwendet showHierarchy.\n";
                    //$wert.='<div style="width: 400px; height: 200px; overflow: scroll;">';
                    //$wert.='<div style="overflow-x:auto;">';        // funktioniert nur wenn y nicht zu gross
                    $wert.='<div style="overflow:scroll; height:900px;">';
                    $wert.='<table id="startpage">';
                    $wert.='<tr>';
                    //$wert.= $this->showPictureWidget($showfile);
                    $wert.= $this->showHierarchy();
                    $wert.='</tr></table>';
                    $wert.='</div>';
                    break;
                case 3:        // Topologie
                    if ($debug) echo "Page Type Style is Topology.\n";
                    //echo "Topologiedarstellung erster Entwurf, verwendet showPictureWidget und showTopology.\n";
                    $wert.='<table id="startpage">';
                    $wert.='<tr>';
                    $wert.='<td>';
                    $wert.= $this->showPictureWidget($showfile);
                    $wert.='</td>';

                    $wert.= $this->showTopology($debug);
                    $wert.='</tr></table>';
                    break;
                case 2:   //echo "NOWEATHER false. PageType 2. NoPicture.\n";  
                    $switchSubScreenID = IPS_GetVariableIDByName("SwitchSubScreen",$this->CategoryIdData);  
                    $subscreen=GetValue($switchSubScreenID);
                    if ( ($subscreen>2) || ($subscreen<1) ) $subscreen=1;                     // Wert geht von 1 weg
                    SetValue($switchSubScreenID,$subscreen);
                    $configDisplay=$this->getStartpageDisplayConfiguration();

                    if ($debug) echo "Page Type Style is Station. Subscreen Nummer ist $subscreen. Widget Style ist ".json_encode($configDisplay["WidgetStyle"])."\n";
                    $wert.='<table id="startpage">';

                    if ( $noweather==true )
                        {
                        if ($debug) echo "   ** No Weather No Station just Picture.\n";                            
                        $file=$this->readPicturedir();
                        $maxcount=count($file);
                        if ($showfile===false) $showfile=rand(1,$maxcount-1);

                        //echo "NOWEATHER true.\n";
                        $wert.='<tr>';                        
                        $wert.='<td>';
                        if ($maxcount >0)
                            {
                            $wert.='<img src="user/Startpage/user/pictures/'.$file[$showfile].'" width="67%" height="67%" alt="Heute" align="center">';
                            }		
                        $wert.='</td></tr></table>';
                        }
                    else        // Anzeige der Wetterdaten
                        {
                        $wert .= $this->showDisplayStation($subscreen, $debug);
                        $wert.='<tr>';                                                   // komplette Zeile, diese fällt richtig dick aus  
                        $wert.='<td colspan="'.$configDisplay["WidgetStyle"]["ColMax"].'">';                    
                        $wert.=$this->bottomTableLines($debug);                // komplette zweite Zeile, ist wesentlich dünner
                        $wert.='</td>';
                        $wert.='</tr>';

                        $wert.='</table>';  
                        //echo "Anzeige Startpage Typ 2";   */
                        }
                    break;
                case 1:
                    /*******************************************
                     *
                     * PageType==1,Diese Art der Darstellung der Startpage wird Bildschirmschoner genannt , Standard und bewährte Darstellung
                     * Bild und Wetterstation als zweispaltige Tabelle gestalten
                     *
                     *************************/
                    if ($debug) echo "Page Type Style is Picture.\n";
                    $wert.='<table id="startpage">';
                    //$wert.='<tr><th>Bild</th><th>Temperatur und Wetter</th></tr>';  /* Header für Tabelle */
                    //$wert.='<td><img id="imgdisp" src="'.$filename.'" alt="'.$filename.'"></td>';
                    $wert.='<tr>';                                                   // komplette Zeile, diese fällt richtig dick aus  
                    $wert.='<td height="40%">';     // sonst zu gross
                    $wert.= $this->showPictureWidget($showfile);                          // erste Zelle, 
                    if ( $noweather==false ) 
                        {
                        $wert.= $this->showWeatherTemperatureWidget($debug);     // zweite Zelle, eine dritte gibt es nicht
                        }
                    elseif ($debug) echo "no weather Display configured.\n";
                    $wert.='</td>';
                    $wert.='</tr>';
                    $wert.='<tr>';                                                   // komplette Zeile, diese fällt richtig dick aus  
	                $wert.='<td colspan="2">';                    
                    $wert.=$this->bottomTableLines();                // komplette zweite Zeile, ist wesentlich dünner
                    $wert.='</td>';
                    $wert.='</tr>';
                    $wert.='</table>';
                    break;
                default:
                    break;    
                }
			return $wert;
			}


        /* Station Display
         *
         * Darstelllung als x mal y Widgets am Bildschirm. Möglichst vielfältige Auswahl ist geplant.
         * Die Darstellung sollte soweit möglich responsive sein und konfigurierbar.
         * WidgetsConfig wird transformiert
         *
         */

		function showDisplayStation($subscreen=1, $debug=false)
			{
	        if (isset($this->configuration["Widgets"]) ) $config=$this->configuration["Widgets"];
            else $config=array();
            if ($debug) 
                {
                echo "   showDisplayStation: \n";
                //echo "showDisplayStation: ".json_encode($config)."\n";
                //print_R($config);
                }
            $wert = "";
            foreach ($config as $row => $config2)
                {
                if ($debug) echo "   Row $row\n";
                $wert.='<tr>';                        
                foreach ($config2 as $column => $screens)
                    {
                    if (isset($screens[$subscreen])) 
                        {
                        $entry=$screens[$subscreen];
                
                        if ($debug) 
                            {
                            echo "     Col $column Show ".str_pad($entry["Type"],25)."   ".json_encode($entry)."\n";
                            print_R($entry);
                            }
                        $tdformat='bgcolor="'.$entry["Format"]["BGColor"].'"';

                        switch (strtoupper($entry["Type"]))
                            {
                            case "ASTRONOMY":
                                //$wert.='<td width="100%">';
                                //$wert.='<td '.$tdformat.' width="600px">';
                                $wert.='<td '.$tdformat.' width="100%">';
                                $wert.=$this->showAstronomyWidget("CHART",$entry,$debug);
                                $wert.='</td>';
                                break;
                            case "MOON":
                                $wert.='<td '.$tdformat.'>';
                                $wert.=$this->showAstronomyWidget("MOON",$entry,$debug);
                                $wert.='</td>';
                                break;
                            case "WEATHER":
                                $wert.='<td '.$tdformat.'>';
                                $wert.='<table border="0" bgcolor="#f1f1f1">';
                                $wert .= $this->showWeatherTable(false,$debug);     // statt false können die Wetterdaten übergeben werden
                                $wert.='</table>';
                                $wert.='</td>';
                                break;
                            case "GROUPTEMP":               // Verschiedene Gruppen von Temperaturwerten anzeigen
                                $wert .= '<td '.$tdformat.'>';
                                $wert .= $this->showTempGroupWidget($entry,$debug);
                                $wert .= '</td>';             
                                break;
                            case "HEATING":               // Heizungsfunktion nachweisen und illustrieren
                                $wert .= '<td '.$tdformat.'>';
                                $wert .= $this->showHeatingWidget($entry,$debug);
                                $wert .= '</td>';             
                                break;
                            case "PICTURE":
                                $wert .= '<td '.$tdformat.'>';
                                $wert.= $this->showPictureWidget(false,$debug);     // statt false könnte das Bild übergeben werden
                                $wert .= '</td>';             
                                break;
                            case "SPECIALREGS":
                                $wert .= '<td '.$tdformat.'>';
                                $wert.= $this->showSpecialRegsWidget($entry,$debug);
                                $wert .= '</td>';             
                                break;
                            case "TEMPERATURE":
                                $wert.='<td '.$tdformat.'><table border="0" bgcolor="#f1f1f1">';
                                $wert .= $this->showTemperatureTable("",$entry,$debug);         // erster Parameter ist colspan als config für table
                                $wert .= '</table></td>';
                                break;
                            case "EMPTY":
                                $wert.='<td><table border="0" bgcolor="#f1f1f1">';
                                $wert .= "<td>intentionally left empty</td>";         // erster Parameter ist colspan als config für table
                                $wert .= '</table></td>';
                                break;
                            default:
                                if ($debug) echo "   Col $column ".$entry["Type"]."\n";                        
                                break;
                            }
                        }
                    else 
                        {
                                $wert.='<td><table border="0" bgcolor="#f1f1f1">';
                                $wert .= "<td>intentionally left empty</td>";         // erster Parameter ist colspan als config für table
                                $wert .= '</table></td>';

                        }
                    }
                $wert .= '</tr>';    
                }

            return ($wert);
            }


        /* Astronomy Widget
         *
         * depending on Display Option different ways of display 
         * 
         * Definition ist eine eigenständige Zelle, typischerweise eine Zelle von 6 : 2 Reihen a 3 Zellen
         * angenommen wird dass diese htmlBox innerhalb einer Zelle von <td>   und   </td> ist,#.
         *
         */

		function showAstronomyWidget($displayType=false,$config=false,$debug=false)
			{
            $wert="";
            $modulname="Astronomy";
            //echo "Rausfinden ob Instanz $modulname verfügbar:\n";
            $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
            $Astronomy=$modulhandling->getInstances($modulname);
            if (count($Astronomy)>0)
                {
                $instanzID=$Astronomy[0];    
                //$instanzname=IPS_GetName($instanzID);
                $wert.='<table width="100%">';

                $moonPicId=@IPS_GetObjectIDByName("Mond Ansicht",$instanzID);
                if ($moonPicId !== false) 
                    {
                    $htmlpicMoon=IPS_GetMedia($moonPicId)["MediaFile"]; 
                    $pos1=strpos($htmlpicMoon,"Astronomy");
                    if ($pos1) $htmlpicMoon = 'user/Startpage/user'.substr($htmlpicMoon,$pos1+9);
                    else $htmlpicMoon=false;   
                    }
                $sunriseID=@IPS_GetObjectIDByName("Sonnenaufgang Uhrzeit",$instanzID);
                if ($sunriseID !== false) $sunrise = GetValueIfFormatted($sunriseID);
                else $sunrise="";
                $sunsetID=@IPS_GetObjectIDByName("Sonnenuntergang Uhrzeit",$instanzID);
                if ($sunsetID !== false) $sunset = GetValueIfFormatted($sunsetID);
                else $sunset="";
                $fullMoonID=@IPS_GetObjectIDByName("Vollmond",$instanzID);
                if ($fullMoonID !== false) $fullMoon = GetValue($fullMoonID);
                else $fullMoon="";

                $iconPic = 'user/Startpage/user/icons/Flower.svg';
                    
                switch (strtoupper($displayType))
                    {
                    case "CHART":
                        $wert.='<tr><td>'.$this->showAstronomy().'</td></tr>';
                        break;
                    case "MOON":
                        $wert.='<tr><td align="center">';
                        if ($htmlpicMoon) $wert.='<img src="'.$htmlpicMoon.'" alt="Bild der Mondphase">';
                        $wert.='</td></tr>';
                        $wert.='<tr><td><table>';
                        $wert.='<tr><td>Sonnenaufgang</td><td>'.$sunrise.'</td></tr>';
                        $wert.='<tr><td>Sonnenuntergang</td><td>'.$sunset.'</td></tr>';
                        $wert.='<tr><td>Nächster Vollmond</td><td>'.$fullMoon.'</td></tr>';
                        $wert.='<tr><td align="center">';
                        if ($iconPic) $wert.='<img src="'.$iconPic.'" alt="Flower Icon">';
                        $wert.='</td></tr>';
                        $wert.='</table></td></tr>';
                        break;
                    case "ALL":
                    default:
                        $wert.='<tr><td colspan="2" >'.$this->showAstronomy().'</td></tr>';
                        //$wert.='<tr><td align="center"><iframe><img src="'.$htmlpicMoon.'" alt="Bild der Mondphase"></iframe></td></tr>';
                        $wert.='<tr><td align="center">';
                        if ($htmlpicMoon) $wert.='<img src="'.$htmlpicMoon.'" alt="Bild der Mondphase">';
                        $wert.='</td><td><table>';
                        $wert.='<tr><td>Sonnenaufgang</td><td>'.$sunrise.'</td></tr>';
                        $wert.='<tr><td>Sonnenuntergang</td><td>'.$sunset.'</td></tr>';
                        $wert.='</table></td></tr>';
                        break;
                    }

                $wert.='</table>';
                }
            else $wert.="Astronomy not available";
            return ($wert);
            }

        /********************
         *
         * Zelle Tabelleneintrag für die Darstellung von astronomischen Informationen 
         *
         **************************************/

		function showAstronomy($debug=false)
			{
            $htmlAstro="";

            //echo "Rausfinden ob Instanz $modulname verfügbar:\n";
            $modulname="Astronomy";
            $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
            $Astronomy=$modulhandling->getInstances($modulname);
            if (count($Astronomy)>0)
                {
                $instanzID=$Astronomy[0];    
                $instanzname=IPS_GetName($instanzID);
                //echo " ModuleName:".$modulname." hat Instanz:".$instanzname." (".$instanzID.")\n";
                //echo " Konfiguration :".IPS_GetConfiguration($instanzID)."\n";
                $childs=IPS_GetChildrenIDs($instanzID);
                //Print_R($childs);

                $foundHtmlBox=false; 
                $foundPicMoon=false; $htmlpicMoon="";
                foreach ($childs as $child) 
                    {
                    $childName=IPS_GetName($child);
                    //echo "  $child ($childName)   \n";
                    //echo GetValue($child)."\n";
                    if ($childName=="Position Sonne und Mond") $foundHtmlBox=$child;
                    }
                if ($foundHtmlBox !== false) $htmlAstro=GetValue($foundHtmlBox);              // das ist ein iframe mit 
                //echo $html;
                //SetValue($AstroLinkID,$html);
                }
            return ($htmlAstro);
            }

        /********************
         *
         * Zelle Tabelleneintrag für die Darstellung der Topologie mit aktuellen Werten
         *
         * Get Topology Liste aus EvaluateHardware_Configuration
         * die Topologie mit den Geräten anreichen. Es gibt Links zu INSTANCES and OBJECTS 
         *
         **************************************/

		function showTopology($debug=false)
			{
            $wert="";
            
            $config=array();
            $config["Cell"]="Table";
            $config["Headline"]="";
            //$config["Scale"]=2;

            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
            IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');            

            /* Get Topology Liste aus EvaluateHardware_Configuration */
            $DetectDeviceHandler = new DetectDeviceHandler();
            $topology=$DetectDeviceHandler->Get_Topology();

            /* die Topologie mit den Geräten anreichen:
             *    wir starten mit Name, Parent, Type, OID, Children  
             * Es gibt Links zu Chíldren, INSTANCE und OBJECT 
             *    Children, listet die untergeordneten Eintraege
             *    OBJECT sind dann wenn das Gewerk in der Eventliste angegeben wurde, wie zB Temperature, Humidity aso
             *    INSTANCE ist der vollständigkeit halber für die Geräte
             *
             * Damit diese Tabelle funktioniert muss der DetDeviceHandler fleissig register definieren
             */

            $topologyPlusLinks=$this->mergeTopologyObjects($topology,IPSDetectDeviceHandler_GetEventConfiguration(),$debug);

            if ($debug) 
                {
                echo "=====================================================================================\n";
                print_r($topologyPlusLinks);
                echo "=====================================================================================\n";
                echo "Berechnung Topology Status, dann Ausgabe:\n";
                }

            /* Konfiguration aus der Topologie in eine Struktur mit aktuellen Werten bringen 
             * Zusatzkonfigurationen, wie die Position und Groesse auf der Anzeige, jetzt übernehmen
             * INSTANCE wird ignoriert, es wird nur OBJECT ausgewertet
             * OBJECT wird 1:1 aus der vorigen Struktur übernommen
             */

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
                    if (isset($place["Background"])) $topologyStatus[$y][$x]["Background"]=$place["Background"];                    
                    if (isset($place["OBJECT"])) 
                        {
                        $topologyStatus[$y][$x]["Status"]=$place["OBJECT"];
                        if ($debug)             /* eigentlich nur die Anzeige in diesem Status, die auf hierarchische Gewerk Strukturen angepasst wurde */
                            {
                            foreach ($place["OBJECT"] as $type => $objName) 
                                {
                                echo "  $type : ";
                                switch ($type)
                                    {
                                    case "Weather":         // Überbegriff, mögliche Hierarchie 
                                        foreach ($objName as $subtype => $objName2)
                                            {
                                            foreach ($objName2 as $oid => $name) echo "  $subtype:$oid (".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName($oid).") => $name  ";    
                                            }
                                        break;
                                    default:    
                                        foreach ($objName as $oid => $name) echo "  $oid (".IPS_GetName(IPS_GetParent(IPS_GetParent($oid))).".".IPS_GetName(IPS_GetParent($oid)).".".IPS_GetName($oid).") => $name  ";
                                        break;
                                    }
                                echo "\n";
                                }
                            echo "\n";
                            }           // Ende Debug
                        }
                    }
                }  // ende foreach


            if ($debug) 
                {
                echo "=====================================================================================\n";
                echo "Status Topologie für Ausgabe vorbereitet:\n";
                //print_r($topologyStatus);
                }

            ksort($topologyStatus);
            $wert.="<style>";
            //$wert.="#topology { border-collapse: collapse; border: 1px solid #ddd;   }";
            //$wert.="#topology td, #topology th { border: 1px solid #ddd; text-align: center; height: 50px; width: 50px; }";
            //$wert.="#topology p { color:lightblue; margin:0;   }";

            $wert.="#topology { border-collapse: collapse; border: 1px solid #ddd; background-color:#020304; color:#c3d4e5; }";
            $wert.="#topology td, #topology th { border: 1px solid #ddd; text-align:center; height:50px; width:100px; }";
            $wert.="#topology p { color:lightblue; margin:0;   }";
            $wert.="#valuecell { width:100%; height:100%; border-collapse: collapse; color:orange;  }";
            $wert.="#valuecell td { border-style:dotted; border-color:111111; }";

            $wert.="</style>";
            $wert.="<td>";
            $wert .= $this->writeTable($topologyStatus, $config, $debug);
            $wert.="</td>";
            return ($wert);
            }

        /*
         * Verbindung der Topologie mit der Object und instamnzen Konfiguration
         * es können jetzt auch mehrstufige hierarchische Gewerke aufgebaut werden
         * zB Weather besteht aus Temperatur und Feuchtigkeit
         */

        function mergeTopologyObjects($topology, $objectsConfig, $debug=false)
            {
            if ($debug) echo "mergeTopologyObjects mit informationen aus IPSDetectDeviceHandler_GetEventConfiguration() aufgerufen:\n";
            $text="";                
            $topologyPlusLinks=$topology;
            foreach ($objectsConfig as $index => $entry)
                {
                if ($debug) 
                    {
                    $newText=$entry[0]."|".$entry[1]."|".$entry[2];
                    if ($newText != $text) echo "$index   \"$newText\"\n";
                    $text=$newText;
                    }
                $name=IPS_GetName($index);
                $entry1=explode(",",$entry[1]);		/* Zuordnung Gruppen, es können auch mehrere sein, das ist der Ort zB Arbeitszimmer */
                $entry2=explode(",",$entry[2]);		/* Zuordnung Gewerke, eigentlich sollte pro Objekt nur jeweils ein Gewerk definiert sein. Dieses vorrangig anordnen */
                if (sizeof($entry1)>0)
                    {
                    foreach ($entry1 as $place)
                        {
                        if ( isset($topology[$place]["OID"]) != true ) 
                            {
                            if ($debug) echo "   Fehler, zumindest erst einmal die Kategorie \"$place\" anlegen.\n";
                            }
                        else
                            {
                            $oid=$topology[$place]["OID"];
                            //print_r($topology[$place]);
                            $size=sizeof($entry2);
                            if ($entry2[0]=="") $size=0;
                            if ($size == 1) 
                                {	/* es wurde ein Gewerk angeben, zB Temperatur, vorne einsortieren */
                                if ($debug) echo "   erzeuge OBJECT Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid).") ".$entry[2]."\n";
                                //CreateLinkByDestination($name, $index, $oid, 10);	
                                $topologyPlusLinks[$place]["OBJECT"][$entry2[0]][$index]=$name;       // nach OBJECT auch das Gewerk als Identifier nehmen
                                }
                            elseif ($size == 2)
                                {
                                /* eine zusätzliche Hierarchie einführen, der zweite Wert ist die Übergruppe */
                                if ($debug) echo "   erzeuge OBJECT Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid).") ".$entry[2]."\n";
                                //CreateLinkByDestination($name, $index, $oid, 10);	
                                $topologyPlusLinks[$place]["OBJECT"][$entry2[1]][$entry2[0]][$index]=$name;       // nach OBJECT auch das Gewerk als Identifier nehmen
                                } 
                            else
                                {	/* eine Instanz, dient nur der Vollstaendigkeit */
                                if ($debug) echo "   erzeuge INSTANCE Link mit Name ".$name." auf ".$index." der Category $oid (".IPS_GetName($oid)."), wird nachrangig einsortiert.".$entry[2]."\n";						
                                //CreateLinkByDestination($name, $index, $oid, 1000);						
                                $topologyPlusLinks[$place]["INSTANCE"][$index]=$name;
                                }
                            }
                        }
                    //print_r($entry1);
                    }
                }  // ende foreach
            return ($topologyPlusLinks);
            }

       /******************************************************************
        *
        * umwandeln in eine Pos orientierte Tabelle
        * Starting point is indexname and widget
        * return is [posY][posX]=>Widget 
        *
        */

        function transformConfigWidget($widgetsConf,&$widgetsStyle, $debug=false)
            {
            $config=array();
            if ($debug)  echo "transformConfigWidget aufgerufen: ".json_encode($widgetsStyle)."  ".json_encode($widgetsConf)."\n";
            $maxX=3; $x=0; $y=0;        // automatische Aufteilung ohne Pos
            $maxScreens=$widgetsStyle["Screens"];
            foreach ($widgetsConf as $name => $widget)              // alle Widgets Speks durchgehen, können auf mehrere Screens verteilt sein !
                {
                $configWidget=array();   
                configfileParser($widget, $configWidget, ["TYPE","Type"],"Type" ,$name);  
                configfileParser($widget, $configWidget, ["NAME","Name"],"Name" ,$name);  
                configfileParser($widget, $configWidget, ["FORMAT","Format"],"Format" ,'{"BGColor":"#1f242e","width":"500px"}'); 
                configfileParser($widget, $configWidget, ["SCREEN","Screen"],"Screen" ,1);                  
                configfileParser($widget, $configWidget, ["CONFIG","Config"],"Config" ,"[]");                   // output array ist configWidget, input array ist widget
                configfileParser($widget, $configWidget, ["POS","Pos"],"Pos" ,null);                            // default keien ANgabe, d.h. der Reihe nach
                /*if ($debug) print_r($configWidget);
                if (isset($widget["Type"])) $wType = $widget["Type"]; else $wType = $name;
                if (isset($widget["Name"])) $wName = $widget["Name"];
                else $wName = $name;*/
                if (isset($configWidget["Pos"])) 
                    { 
                    $wX=$configWidget["Pos"][0]; 
                    $wY=$configWidget["Pos"][1]; 
                    }
                else 
                    {
                    $wX=$x; $wY=$y;
                    $x++;
                    if ($x==$maxX) {$x=0; $y++; }    
                    }
                //$configWidget["Type"]=$wType;       // Widget Type    
                //$configWidget["Name"]=$wName;       // referenziert auf die Config des Widgets
                $configWidget["Pos"]["X"]=$wX;
                $configWidget["Pos"]["Y"]=$wY;
                if ($configWidget["Screen"]<1) $configWidget["Screen"]=1;
                elseif ($configWidget["Screen"]>$maxScreens)
                    {
                    $maxScreens=$configWidget["Screen"]; 
                    $widgetsStyle["Screens"]= $maxScreens; 
                    }
                if ($debug) echo "   ".str_pad($configWidget["Type"]."::".$configWidget["Name"],32)." ist auf Pos $wX | $wY von Screen ".$configWidget["Screen"].".\n";
                $config[$wY][$wX][$configWidget["Screen"]]=$configWidget;
                }
            //print_R($config);
            $showDisplayConfig=array(); 
            $rowmax=$widgetsStyle["RowMax"]; $colmax=$widgetsStyle["ColMax"];
            for ($row=0;$row<$rowmax;$row++)
                {
                for ($col=0;$col<$colmax;$col++)
                    {
                    for ($screen=1;$screen<=$maxScreens;$screen++)
                        {
                        if (isset($config[$row+1][$col+1][$screen]))                            // es gibt nicht alle alle Werte
                            {
                            $screenConfig = $config[$row+1][$col+1][$screen]["Screen"];          // Einstellung ist immer gesetzt, Default ist 1
                            if ($screenConfig==$screen) 
                                {
                                if ($debug) echo "Row $row Col $col Screen $screen von $maxScreens : ".$config[$row+1][$col+1][$screen]["Type"]."\n";
                                $showDisplayConfig[$row][$col][$screen] = $config[$row+1][$col+1][$screen];
                                }
                            }
                        else $showDisplayConfig[$row][$col][$screen] = ["Type" => "Empty","Format" => ["BGColor"=>"darkblue","width"=>"500px"]];
                        }
                    }
                } 
            //if ($debug) print_r($showDisplayConfig);           
            return ($showDisplayConfig);
            }

       /******************************************************************
        *
        * rekursive(?) Routine um eine Tabelle zu zeichnen, es soll auch eine Tabelle in einer Zelle abgebildet werden
        * die Rekursivität der Funktion erfolgt über die Html Struktur, dazu sind zB style und andere Deklarationen ausserhalb
        * 
        * Für besondere Konfigurationen wird ein eigenes array verwendet. $config
        *   $config["Scale"]   Faktor zum vergrößern der Tabelle. statt einer zellgröße von 1x1 wird mit Scale=3 eine Zelle von 3x3.
        *
        * Übergeben wir $topologyStatus als zweidimensionales array [y][x]  y sind die Zeilen, x sind die Spalten
        *    Elemente sind  Size mit l,h   ShortName, Status mit Humidity,Temperature, 
        *
        * zuerst max Spalten und Zeilenanzahl ermitteln
        * <table id=topology> anlegen
        *
        ********/

        function writeTable($topologyStatusInput, $config=array(), $debug=false)
            {
            if ($debug) 
                {
                echo "**************************************************************************************\n";
                echo "Aufruf writeTable, Darstellung der Informationen innerhalb einer Topologie: ".json_encode($config)."\n";
                //print_r($topologyStatusInput);
                }
            $topologyStatus=array();
            if (isset($config["Scale"])) $scale=$config["Scale"]; 
            else $scale=1;
            foreach ($topologyStatusInput as $y=>$line)
                {
                $yNew=($y-1)*$scale+1;            /*   1..1, 2..3, 3..5 usw */
                foreach ($line as $x => $status) 
                    {
                    $xNew=($x-1)*$scale+1;            /*   1..1, 2..3, 3..5 usw */
                    if ($debug) echo "New Index :  $y x $x transformed to $yNew x $xNew :\n";
                    foreach ($status as $key=>$entry)
                        {
                        if ($debug) echo "    ".$key."\n";
                        switch ($key)
                            {
                            case "Size":
                                if ($debug) print_r($entry);
                                $lNew=1; $hNew=1;
                                if (isset($entry["l"])) $lNew=$entry["l"]*$scale;
                                if (isset($entry["h"])) $hNew=$entry["h"]*$scale;
                                $topologyStatus[$yNew][$xNew][$key]["l"]=$lNew;
                                $topologyStatus[$yNew][$xNew][$key]["h"]=$hNew;
                                break;
                            default:
                                $topologyStatus[$yNew][$xNew][$key]=$entry;
                                break;
                            }
                        }
                    }
                }

            /* Analyse der übergebenen Daten */
            $maxx=0; $maxy=0; $minx=1000; $miny=1000;
            $lsum=array();
            foreach ($topologyStatus as $y => $line)        // jede Zeilen und Spalteneintrag durchgehen, min/max finden, leere Zeilen/Zellen nicht anschauen
                {
                if ($y < $miny) $miny=$y;
                if ($y > $maxy) $maxy=$y;
                foreach ($line as $x => $status)            // in einer Zeile die Zellen im Detail anschauen, die groesse der Zelle hat Einfluss auf max
                    {
                    if (isset($status["Size"]["l"])) $l=$status["Size"]["l"]-1;         /* Zellengroesse in die Breite mit berücksichtigen */
                    else $l=0;
                    if (isset($status["Size"]["h"])) $h=$status["Size"]["h"]-1;         /* Zellengroesse in die Höhe mit berücksichtigen, eventuell maxy anpassen */
                    else $h=0;
                    if (($x) < $minx) $minx=$x; 
                    if (($x+$l) > $maxx) $maxx=$x+$l; 
                    if (($y+$h) > $maxy) $maxy=$y+$h;
                    }
                }
            if ($debug) echo "Evaluierung Tabellengroesse: min/maximale Anzahl an Tabelleneinträgen in x Richtung : $minx/$maxx , in y Richtung : $miny/$maxy.\n";
            /* lsum array anlegen, für jede Zeile lsum[y]["line"], für jede Zelle lsum[y][x] mit 0 eintragen */
            for ($yCount=$miny; $yCount<=$maxy; $yCount++)
                {
                if (isset($topologyStatus[$yCount])) $line=$topologyStatus[$yCount];
                else $line=array();         // fehlende Zeilen einfügen
                $lsum[$yCount]["line"]=0;                                                   // Summenzähler für übergrosse Zellen in Richtung x anlegen
                foreach ($line as $x => $status) $lsum[$yCount][$x]=0;                      // x Merker für übergrosse Zellen die über mehrere Zeilen gehen setzen
                }
            if ($debug) { echo "Evaluierung mit übergrossen Zellen\n"; print_r($lsum); }

            $html="";
            $html.="<table id=topology>";
            $html.='<tr><td colspan="'.$maxx.'">Info-Überschrift '."$maxy x $maxx".'</td></tr>';
            for ($yCount=$miny; $yCount<=$maxy; $yCount++)          /* jede Zeile durchgehen, leere Zeilen/Zellen auch bearbeiten */
                {
                $html.="<tr>";
                //foreach ($topologyStatus as $y => $line)   Befehl vorher
                /* für Leerzeilen die erwarteten Daten einfügen */
                if (isset($topologyStatus[$yCount])) { $y= $yCount; $line=$topologyStatus[$yCount]; }
                else { $y= $yCount; $line=array(); } 
                if ($debug) { echo "   Zeile $y abarbeiten:\n"; print_r($line); }
                $maxxActual=$maxx; $shiftleft=0;                    /* neues maxxActual für grosse Zellen, wird jedesmal bei einer grossen Zelle reduziert, shiftleft zeigt auf den neuen reduzierten Spaltenzähler */
                /* x und y sind immer die Koordinaten auf der Matrix und innerhalb des arrays
                 * Tabelleneintraege in der gleichen Zeile muessen übersprungen werden wenn Zellen breiter (l) als 1 sind 
                 * Tabelleneintraege in den Zeilen darunter muessen übersprungen werden wenn Zellen höher (h) als 1 sind 
                 *
                 * Merker für breite Zellen sind in lsum[line]
                 * Merker für hohe Zellen sind in lsum[y][x]=l
                 */
                for ($i=$minx;$i<=$maxxActual;$i++)                 /* jede Spalte durchgehen, maxxActual wird um die grossen Zellen reduziert */
                    {
                    $x=$i+$lsum[$y]["line"];
                    if ($debug) echo "      Zelle mit Koordinaten Zeile $y Spalte $x abarbeiten. ".$lsum[$y]["line"]." Zellen überspringen:\n";
                    $text=""; $starttext=""; $midtext="";  $endtext="";              // Default Text, Starttext und Endtext zum Öffnen und Schliessen von zusaetzlichen Formatierungen
                    //$text=$i; 
                    if ( (isset($lsum[$y][$x])) && ($lsum[$y][$x]>0) )     /* wenn eine Zelle höher ist als 1, steht hier der Merker, die breite steht im array */
                        {
                        /* $lsum[$y]["line"] nicht anpassen, Darstellung anders gewählt, bei x Koordinaten wird die Versetzung bereits bei der Eingabe in config berücksichtigt */
                        $maxxActual = $maxxActual-$lsum[$y][$x]+1;            // hier haengt noch ein Spalte von oben herunter, wird beim Tabellenzeichnen ignoriert und einfach daneben angefangen
                        $shiftleft=$shiftleft+$lsum[$y][$x]-1;                  // obsolet
                        $lsum[$y]["line"] = $lsum[$y]["line"] + $lsum[$y][$x]-1;
                        if ($debug) echo "         Marker gefunden, hier haengt eine Zelle von oben herunter. Debug lsum ".$lsum[$y][$x]." maxxActual $maxxActual shiftleft $shiftleft \n";
                        }
                    elseif (isset($line[$x]))        /* Eintrag für eine Zelle vorhanden, shiftleft ist am Anfang noch Null, nur von überhängenden hohen Zellen benutzt. */
                        {
                        if (isset($line[$x]["ShortName"])) 
                            {
                            $shortname=$line[$x]["ShortName"];
                            }
                        else $shortname="";
                        if (isset($line[$x]["Size"]["l"])) $l=$line[$x]["Size"]["l"];          /* Zelle hat wahrscheinlich Sondermasse, entweder h oder l ist größer 1 */
                        else $l=1;
                        if (isset($line[$x]["Size"]["h"])) $h=$line[$x]["Size"]["h"];         /* Zelle hat wahrscheinlich Sondermasse, entweder h oder l ist größer 1 */
                        else $h=1;
                        if ($debug) 
                            {
                            echo "         Eintrag auf ".($x)." ist dran mit Size (hxb) $h x $l Name : $shortname\n"; 
                            //print_r($line[$i-$shiftleft]);
                            }
                        if ($l>1)           /* Zelle ist breiter als 1 */
                            {
                            $maxxActual = $maxxActual-$l+1;                         /* um die zusaetzliche Breite früher aufhören */
                            $lsum[$y]["line"] = $lsum[$y]["line"] + $l-1;           /* auch in lsum[y][line] die zusaetzliche Breite mitführen, damit wird das naechste x uebersprungen */
                            }
                        if ($h>1)           /* Zelle ist höher als 1, in lsum für die nächsten Höhe-1 Zeilen lsum[y][x]=l, also die Breite der Zelle eintragen */
                            {
                            for ($j=1;$j<$h;$j++) $lsum[$y+$j][$x]=$l;
                            }
                        if (isset($config["Cell"])) 
                            {
                            //echo "Cell Konfiguriert.\n"; print_r($line[$x]);
                            if (isset($line[$x]["Background"])) 
                                {
                                $background="background-color:".$line[$x]["Background"];
                                //echo "**** Background Parameter gefunden.\n";
                                }
                            else $background="";
                            $starttext='<table id=valuecell style="'.$background.';border-style:none;"><tr>';
                            $midtext  =  '<td>m00</td><td>m01</td></tr>';
                            $midtext .= '<tr><td>m10</td><td>m11';
                            $endtext  = '</td><td></td></tr><tr><td></td><td></td><td></td></tr></table>';
                            $texte=array();                 // Array mit bis zu 9 Eintraegen
                            $texte[1]='<td>'.$shortname.'</td>';
                            $texte[2]='<td>t02</td>';$texte[3]='<td>t03</td>';$texte[4]='<td></td>';$texte[5]='<td></td>';$texte[6]='<td></td>';$texte[7]='<td></td>';$texte[8]='<td></td>';$texte[9]='<td></td>';
                            $shortname=$texte[1];
                            }                        
                        if (isset($line[$x]["Status"])) 
                            {
                            $newStatus=$this->transformStatus($line[$x]["Status"], $debug);             /* Sortierung nach Kriterien, wird neu indexiert */
                            /*
                            if ($line[$i]["Status"]==2) $html.='<td bgcolor="00FF00"> '.$text.' </td>'; 
                            elseif ($line[$i]["Status"]==1) $html.='<td bgcolor="00FFFF"> '.$text.' </td>';			
                            else $html.='<td bgcolor="0000FF"> '.$text.' </td>';
                            */
                            //foreach ($line[$i-$shiftleft]["Status"] as $type => $object)
                            $first=true;
                            foreach ($newStatus as $entry)                                              /* geht jetzt 0,1,2,3 usw. nicht mehr nach Keys */
                                {
                                if ($first) $first=false;
                                else $text .= "<br>";
                                foreach ($entry as  $type => $object)                                   /* type ist HUMIDITY, TEMPERATURE aber auch Gruppen wie WEATHER */
                                    {
                                    if (count($object)>1) $long=true; else $long=false;
                                    switch ($type)
                                        {
                                        case "Weather":
                                            if ($debug) echo "writeTable,Status: Untergruppe $type erkannt :\n"; print_r($object);
                                            $humidity=""; $temperature=""; $other="";
                                            foreach ($object as $index => $name) 
                                                {
                                                foreach ($name as $subvalueID => $subname) 
                                                    {
                                                    if ($debug) echo "   Status Weather, Bearbeite $subvalueID:$index und $subname:\n";
                                                    switch ($index)
                                                        {
                                                        case "Temperature":
                                                            $temperature .= $this->writeValue($subvalueID, $index)." ";
                                                            break;
                                                        case "Humidity":
                                                            $humidity .= $this->writeValue($subvalueID, $index)." ";
                                                            break;
                                                        default:
                                                            $other .= $this->writeValue($subvalueID, $index)." ";
                                                            break;
                                                        }
                                                    }
                                                }
                                            if ($long) $subtext = $temperature."/ ".$humidity."/ ".$other." ($subname)";
                                            else $subtext = $temperature."/ ".$humidity."/ ".$other;
                                            if (isset($config["Cell"])) $texte[2]='<td>'.$subtext.'</td>';
                                            else $text .= $subtext;
                                            break;
                                        default:
                                            foreach ($object as $index => $name) 
                                                {
                                                if ($debug) echo "writeTable,Status: Bearbeite $index in $type:\n";
                                                if ($long) $text .= " ".$this->writeValue($index,$type)." ($name)";
                                                else $text .= " ".$this->writeValue($index,$type);
                                                }
                                            break;
                                        }       // Ende switch type
                                    }       // ende foreach entry
                                }       // ende foreach newStatus
                            /* der Inhalt der Zellen steht in text */   
                            if (isset($config["Cell"])) 
                                {
                                $texte[5]='<td>'.$text.'</td>';;
                                $html.='<td colspan="'.$l.'" rowspan="'.$h.'" style="min-width:100px;background-color:#122232;color:white">'.$starttext;
                                $k=1;
                                foreach ($texte as $cell) 
                                    {
                                    if ( ($k == 4) || ($k==7) ) $html .= '<tr>';
                                    $html .= $cell;
                                    if ( ($k==3) || ($k == 6) || ($k==9) ) $html .= '</tr>';
                                    $k++;
                                    }
                                $html.='</table></td>';
                                }
                            else $html.='<td colspan="'.$l.'" rowspan="'.$h.'" style="min-width:100px;background-color:#122232;color:white">'.$starttext.$shortname.$midtext.$text.$endtext.'</td>';                            
                            }   // Ende es ist ein Statuseintrag vorhanden
                        else $html.='<td colspan="'.$l.'" rowspan="'.$h.'" style="background-color:#122232;color:white"> '.$starttext.$shortname.$midtext.$text.$endtext.' </td>';           // nur zum Beispiel Shortname ausgeben
                        }   // ende Eintrag für eine Zelle ist vorhanden
                    else $html.='<td style="background-color:#020304;color:#322212"> '.$text.' </td>';        // nur Default Text ausgeben
                    }           // ende alle Spalten durchgehen
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
        * Darstellung der Werte, Sortierung der einzelnen OBJECTs abhängig von vorgegbenen Reihenfolgen
        * hierarchische Gruppierung werden gleich behandelt
        *
        *
        ********/

        function transformStatus($status, $debug=false)
            {
            if ($debug) { echo "transformStatus: Input Werte\n"; print_r($status); }
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
            if ($debug) print_r($keys);
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
            if ($debug) print_r($result);
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
                    if (IPS_VariableExists($valueID)) return ($this->writeMovement($valueID));
                    else return("");
                case "Temperature":
                    if (IPS_VariableExists($valueID)) return ($this->writeTemperature($valueID));
                    else return("");
                case "Humidity":
                    if (IPS_VariableExists($valueID)) return ($this->writeHumidity($valueID));
                    else return("");
                default:
                    if (IPS_VariableExists($valueID)) return("<p>$type".GetValue($valueID)."</p>");
                    else return("");
                }
            }

        function writeMovement($valueID, $debug=false)
            {
            $timeSinceUpdate=time()-IPS_GetVariable($valueID)["VariableUpdated"];
            if ($timeSinceUpdate > (24*60*60))
                {
                if ($debug) echo IPS_GetName($valueID)."  last update ".date ("d.m.Y H.i.s",IPS_GetVariable($valueID)["VariableUpdated"])."\n";
                return ('<span style="color:red">Move '.(GetValue($valueID)?"Yes":"No")."</span>"); 
                }
            else return ("<span>Move ".(GetValue($valueID)?"Yes":"No")."</span>"); 
            }

        function writeTemperature($valueID, $debug=false)
            {
            return ("<span>".GetValue($valueID)." °C</span>"); 
            }

        function writeHumidity($valueID, $debug=false)
            {
            return ("<span>".GetValue($valueID)." %</span>"); 
            }
    

          /********************
         *
         * Zelle Tabelleneintrag für die Darstellung der Topologie mit aktuellen Werten
         *
         *
         **************************************/

		function showHierarchy($debug=false)
			{
            $wert="";

            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
            IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');   

			/* get Directories */

			$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			$moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
	        $WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
    		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');            
            $categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
    		$worldID=IPS_GetObjectIDByName("World",  $categoryId_WebFrontAdministrator);
            
            /* Get Topology Liste aus EvaluateHardware_Configuration */
            $DetectDeviceHandler = new DetectDeviceHandler();
            $topology=$DetectDeviceHandler->Get_Topology();

            $wert  = "";
            $wert .= "<style>";
            $wert .= "#topology { border-collapse: collapse; border: 1px solid #ddd;   }";
            $wert .= "#topology table { border-collapse: separate; border: 1px solid #ddd;   }";
            $wert .= "#topology td { border: 1px solid blue; text-align: center; height: 50px; width: 50px; background-color: lightblue; margin-top: 1%; vertical-align: top;}";
            $wert .= "#topology caption { font: 20px arial, bold, sans-serif; border: 1px solid black; text-align: left; text-indent: 50px; padding-top: 10px; padding-bottom: 10px; height: auto; background-color: silver;}";
            $wert .= "#topology th { border: 1px solid black; text-align: left; height: 50px; width: 50px; background-color: silver; margin-top: 1%; padding-top: 0px; colspan: 100%;}";
            $wert .= "#topology p { color:lightblue; margin:0;   }";
            $wert .= "</style>"; 

            $config=array();
            $config["View"]="All";
            //$config["View"]="Category";
            $config["Detail"]="NoDevices";
            $config["Columns"]=3;
            $config["StyleFont"]=["Decrease"=>true,"Start"=>20,"Stopp"=>10];
            $config["ValueFormatted"]=true;

            $wert .= $this->drawTable($worldID, $config);            

            return ($wert);
            }

        /* 
         * Tabelle mit Topologie zeichnen. Einstiegsroutine für rekursiven Aufruf von drawCell
         */

        function drawTable($oid,&$config)
            {
            $html = "";
            $level=0;

            $this->drawCell($oid,$html,$level,$config);

            return($html);
            }

        /* 
         * rekursive Funktion zum Tabellen zeichnen 
         *
         * für die übergebene OID wird überprüft ob es Children gibt
         *
         * es gibt immer einen Header. Zellen darunter so anordnen das eventuell weiter unterlagerte Tabellen am Anfang kommen
         * Tabellen mit vielen Einträgen auf mehrere Zeilen aufteilen  
         *
         */

        function drawCell($oid,&$html,$level,&$config)
            {
            $debug=false;
            $ipsOps=new ipsOps();
            $entries=IPS_getChildrenIDs($oid);
            $count=count($entries);

            if ( ($entries !== false) && ($count>0) )
                {
                /* es gibt einen oder mehrere Tabelleneinträge, d.h. es gibt Childrens */

                $count=$this->totalChildren($oid);
                $totalcount=$count["Total"];
                if ($debug)
                    {
                    for ($a=0;$a<$level;$a++) echo " ";
                    echo "drawTable: Aufruf mit $oid (".IPS_GetName($oid).") $level:$totalcount --> $count   \n";
                    }
                //$html .= '<table id=topology><tr><th style="colspan:100%;">'.IPS_GetName($oid)." ($level:$totalcount)</th></tr>";  
                //$html .= '<table id=topology><caption>'.IPS_GetName($oid)." ($level:$totalcount)</caption>";  
                if ( (isset($config["StyleFont"])) && ($config["StyleFont"]["Decrease"]==true) )
                    {
                    $height = ($config["StyleFont"]["Start"]-$level);
                    if ($height <= $config["StyleFont"]["Stopp"]) $height = $config["StyleFont"]["Stopp"];
                    }
                else $height=20;
                $indent=$height;
                $html .= '<table id=topology style=";"><caption style="font:'.$height.'px arial; text-indent: '.$indent.'px;">'.IPS_GetName($oid)." ($level:$totalcount)  ".$count["Entry"]."/".$count["Category"]."</caption>";
                $i=0; $tr=0;
                if (isset($config["Columns"])) $r=$config["Columns"];
                else $r=4;
                /*if ($count>$r) 
                    {
                    $html .= "<tr>"; 
                    $tr++;
                    } */
                /* zuerst Eintraege ausgeben, die selbst Childrens haben */    
                foreach ($entries as $entry)
                    {
                    $subEntries=IPS_getChildrenIDs($entry);
                    $subCount=count($subEntries);
                    if ( ($subEntries !== false) && ($subCount>0) )  // Kategorie mit Childrens 
                        {
                        //$html .= "<td>".IPS_GetName($entry)."</td>";
                        if ($i % $r == 0)
                            {
                            if ($debug) echo "*Zeilenanfang*\n";
                            $html .= "<tr>"; 
                            $tr++;
                            }
                        $html .= "<td>";
                        //for ($a=0;$a<$level;$a++) echo " ";
                        //echo "  $entry with Children: ".IPS_GetName($entry)."\n";  
                        $this->drawCell($entry,$html,$level+1,$config);             // changes $html
                        $html .= "</td>";                    
                        if ($i % $r == ($r-1))
                            {
                            $html .= "</tr>"; 
                            $tr++;
                            }                                        
                        $i++;  
                        }
                    }
                if (strtoupper($config["View"])=="ALL")            
                    {
                    foreach ($entries as $entry)
                        {
                        $subEntries=IPS_getChildrenIDs($entry);
                        $subCount=count($subEntries);
                        if ($subEntries == false)  // keine Childrens 
                            {
                            //$html .= "<td>".IPS_GetName($entry)."</td>";
                            if ($debug) { for ($a=0;$a<$level;$a++) echo " "; }
                            $type=IPS_GetObject($entry)["ObjectType"];
                            if ($type==6)
                                {
                                $target=IPS_GetLink($entry)["TargetID"];
                                if (IPS_GetObject($target)["ObjectType"]==1) 
                                    {
                                    if ($debug) echo "  $entry (Link to Instanz $target) ".$ipsOps->path($target)."\n";
                                    if ( strtoupper($config["Detail"]) != "NODEVICES" )
                                        {
                                        if ($i % $r == 0)
                                            {
                                            if ($debug) echo "*Zeilenanfang*\n";
                                            $html .= "<tr>"; 
                                            $tr++;
                                            }                                    
                                        //$html .= "<td>".IPS_GetName($entry)."</td>";
                                        $html .= "<td>".IPS_GetName($target)."</td>";
                                        if ($i % $r == ($r-1))
                                            {
                                            if ($debug) echo "*Zeilenende*\n";
                                            $html .= "</tr>"; 
                                            $tr++;
                                            }                                        
                                        $i++;                                              
                                        }
                                    }
                                else 
                                    {
                                    if ($i % $r == 0)
                                        {
                                        if ($debug) echo "*Zeilenanfang*\n";
                                        $html .= "<tr>"; 
                                        $tr++;
                                        }                                    
                                    if ($debug) echo "  $entry (Link to $target) ".$ipsOps->path($target)."\n";
                                    //$html .= "<td>".IPS_GetName($entry)."</td>";
                                    if ( (isset($config["ValueFormatted"])) && ($config["ValueFormatted"]) )
                                        {
                                        $html .= "<td>".IPS_GetName($target)."  ".GetValueFormatted($target)."</td>";
                                        //$html .= '<td><table style="border-collapse: collapse;"><tr><td>'.IPS_GetName($target)."</td></tr><tr><td>".GetValueFormatted($target)."</td></tr></table></td>";
                                        }
                                    else
                                        {    
                                        $html .= "<td>".IPS_GetName($target)."  ".GetValue($target)."</td>";
                                        }
                                    if ($i % $r == ($r-1))
                                        {
                                        if ($debug) echo "*Zeilenende*\n";
                                        $html .= "</tr>"; 
                                        $tr++;
                                        }                                        
                                    $i++;                                    
                                    }
                                }
                            else 
                                {
                                if ($debug) echo "  $entry ".$ipsOps->path($entry)."\n";  
                                }
  
                            }   // ende no subentries
                        } // ende foreach                 
                    }  
                if ($tr) $html .= "</tr>"; 
                $html .= "</table>";                     
                }
            else 
                {
                /* keine Children, als Zelle ausgeben */
                $html .= '<td style="background-color=lightred;">:'.IPS_GetName($oid)."</td>";
                if ($debug)
                    {
                    for ($a=0;$a<$level;$a++) echo " ";
                    echo '  $entry "ende"'.IPS_GetName($entry)."\n";  
                    }
                }
            }

        /* Hilfsfunktionen, Analyse Datenstamm */

        function totalChildren($oid)
            {
            $count=array();
            $count["Total"]=0;
            $count["Category"]=0;
            $count["Entry"]=0;
            $this->countChildren($oid,$count);
            return ($count);
            }

        function countChildren($oid,&$count)
            {
            $entries=IPS_getChildrenIDs($oid);
            $countEntry=count($entries);
            if ( ($entries !== false) && ($countEntry>0) )
                {
                $count["Category"]++;               // das ist sicher eine Kategorie, da sie mehrere Children hat
                foreach ($entries as $entry)
                    {
                    $this->countChildren($entry,$count);
                    }
                }
            else 
                {
                $count["Total"]++;
                if (IPS_GetObject($oid)["ObjectType"]==6)
                    {
                    $target=IPS_GetLink($oid)["TargetID"];
                    if (IPS_GetObject($target)["ObjectType"] != 1) 
                        {                
                        $count["Entry"]++;                 
                        }
                    }
                }
            }



        /********************
         *
         * Zelle Tabelleneintrag für die Darstellung eines BestOf Bildes
         *
         *
         **************************************/

		function showPictureWidget($showfile=false, $debug=false)
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
                if ($debug) echo "Filename vorhanden - Groesse ".$filegroesse." MB.\n";
                }
            //echo "NOWEATHER false. PageType 1. Picture. ".$filename."\n\n";   
            $wert.='<div class="container"><img src="'.$filename.'" alt="'.$filename.'" class="image">';
            $wert.='<div class="middle"><div class="text">'.$filename.'<br>'.$filegroesse.' MB '.$info[3].'</div>';
            $wert.='</div>';
            return ($wert);
            }


        /********************
         *
         * Zelle Tabelleneintrag für die Wettertabelle
         * macht 4 Zeilen mit jeweils 2 oder 3 Zellen
         *
         **************************************/

		function showWeatherTable($weather=false, $debug=false)
            {
            $wert="";
            if ($weather==false) $weather=$this->getWeatherData();
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
            return ($wert);
            }

        /********************
         *
         * Zelle Tabelleneintrag für die Tabelle für Heating, Illustration der Heizungsfunktion
         *
         *
         **************************************/

		function showHeatingWidget($configInput=false,$debug=false)
            {
            $wert="";
            if ($configInput===false) $heatingConf = $this->getConfigHeatingWidget(false,$debug);
            else $heatingConf=$this->getConfigHeatingWidget($configInput,$debug);                               // Config wird im getConfig behandelt, aber dann nicht zurückgegeben
            if ($debug) echo "showHeatingWidget ".json_encode($heatingConf)." \n";

            $wert .= '<table>';
            $wert .= '<tr>'; 
            $wert .= '<td><table>';
            foreach ($heatingConf["Values"] as $room=>$values)
                {
                $wert .= '<tr>';
                $wert .= '<td>'.$room.'</td>';
                foreach ($values as $value) $wert .= '<td>'.GetValueIfFormatted($value).'</td>';
                $wert .= '</tr>';
                } 
            //$wert .= '<td>'.json_encode($config).'</td><td>'.'</td></tr>';
            $wert .= '</table></td>';   
            $wert .= '</tr></table>'; 
            return ($wert);                                
            }

        /* read configuration for showHeatingWidget, eliminate unknown indizes */

        function getConfigHeatingWidget($groupsInput=false,$debug=false)
            { 
            if ($groupsInput===false)
                {
                if (isset($this->configuration["Heating"]) ) $heatingConf=$this->configuration["Heating"];
                else $heatingConf=array();
                }
            else $heatingConf = $groupsInput;                

            //$heatingConf=array();       // Test
            if ($debug) echo "getConfigHeatingWidget Configuration analysieren: ".json_encode($heatingConf)."\n";
            configfileParser($heatingConf, $config, ["Config","Configuration","config","CONFIGURATION"],"Config",["Values" => array()]);         //wenn config fehlt nur Values
            $heatingConf=$config["Config"]; $config=array();
            configfileParser($heatingConf, $config, ["Values","VALUES"],"Values",array());
            configfileParser($heatingConf, $config, ["Auto","AUTO"],"Auto",null);
            if ($config["Auto"] != null) 
                {
                if (isset($this->installedModules["DetectMovement"])) 
                    {
                    if ($debug) echo "DetectMovement installiert, automatische Erkennung verwenden\n";    
                    $DetectTemperatureHandler  = new DetectTemperatureHandler();	
                    $DetectHeatSetHandler      = new DetectHeatSetHandler();	
                    $DetectHeatControlHandler  = new DetectHeatControlHandler();	
                    $configurationSet = $DetectHeatSetHandler->Get_EventConfigurationAuto();
                    $configurationTemp = $DetectTemperatureHandler->Get_EventConfigurationAuto();
                    $configurationLevel = $DetectHeatControlHandler->Get_EventConfigurationAuto();
                    $fullpicture=array();
                    foreach ($configurationLevel as $oid => $entry) 
                        {
                        $fullpicture[IPS_GetParent($oid)]["Level"]=$oid;
                        //echo "   $oid  ".str_pad(IPS_getName($oid)."/".IPS_getName(IPS_GetParent($oid)),60)." ".GetValue($oid)."   \n"; 
                        }
                    foreach ($configurationTemp as $oid => $entry) 
                        {
                        if (isset($fullpicture[IPS_GetParent($oid)])) $fullpicture[IPS_GetParent($oid)]["Temperature"]=$oid;
                        }
                    foreach ($configurationSet as $oid => $entry) 
                        {
                        if (isset($fullpicture[IPS_GetParent($oid)])) $fullpicture[IPS_GetParent($oid)]["Set_Temperature"]=$oid;
                        }
                    foreach ($fullpicture as $index => $values)
                        {
                        if (sizeof($values)>2)      // drei Werte
                            {
                            $config["Values"][IPS_GetName($index)]=[$values["Temperature"],$values["Set_Temperature"],$values["Level"],];
                            }
                        }
                    //print_r($fullpicture);
                    }
                }
            return($config);
            }

        /********************
         *
         * Zelle Tabelleneintrag für die Tabelle für Gruppen Temperaturwerte
         * macht 2 Zeilen mit jeweils 2 Zellen
         *
         * class DetectTemperatureHandler muss existieren, also DetectMovement Modul installiert sein
         *
         **************************************/

		function showTempGroupWidget($config=false,$debug=false)
            {
            $wert="";
            if (class_exists("DetectTemperatureHandler"))
                {    
                $wert .= '<table>';
                $DetectTemperatureHandler = new DetectTemperatureHandler();
                if ($config===false) $groups = $this->getConfigTempGroupWidget();
                else $groups=$this->getConfigTempGroupWidget($config["Config"]);
                if ($debug) 
                    {
                    echo "showTempGroupWidget, DetectTemperatureHandler exists. Konfig is for Group: ".json_encode($groups)."\n";
                    print_R($groups);
                    //print_r($groupConf);
                    //print_r($this->configuration);
                    }
                $wert .= '<tr>';    
                foreach ($groups as $index => $groupConf)
                    {
                    $wert .= '<td><table>';
                    $group=$groupConf["Group"];
                    $unit=$groupConf["Unit"];
                    $config=$DetectTemperatureHandler->ListEvents($group);
                    if ($debug) echo "    Gruppe \"$group\": ".json_encode($config)."\n";
                    $status=(float)0;
                    $count=0;
                    $roomList=array();
                    /* Untergruppen ermiotteln und zuordnen, wenn es keine gibt der Gruppe none zuordnen */
                    foreach ($config as $oid=>$params)
                        {
                        $variableProps=IPS_GetVariable($oid);
                        $lastChanged=date("d.m.Y H:i:s",$variableProps["VariableChanged"]);
                        $roomStr=$DetectTemperatureHandler->getRoomNamefromConfig($oid,$group);
                        $roomRay=explode(",",$roomStr);            // Liste der Gruppen die noch zusätzlich zugeordnet wurden
                        if ( ((count($roomRay))>0) && ($roomRay[0] != "") )
                            {
                            foreach ($roomRay as $room) $roomList[$room][]=$oid;
                            }
                        else $roomList[$group][]=$oid;
                        $status+=GetValue($oid);
                        $count++;
                        }
                    $roomCount=array();
                    foreach ($roomList as $room => $oid)
                        {
                        $roomCount[$room]["Count"]=count($roomList[$room]);
                        $roomCount[$room]["Value"]=0;
                        }
                    $status=(float)0;
                    $count=0;
                    /* config id oid => Gruppe, der Reihe die Werte durchgehen und Mittelwert ausrechnen */
                    foreach ($config as $oid=>$params)
                        {
                        $roomStr=$DetectTemperatureHandler->getRoomNamefromConfig($oid,$group);
                        $roomRay=explode(",",$roomStr);            // Liste der Gruppen die noch zusätzlich zugeordnet wurden
                        //echo "behandle für $oid $roomStr :\n"; 
                        if ( ((count($roomRay))>0) && ($roomRay[0] != "") )
                            {
                            $status+=GetValue($oid);
                            $count++;
                            $roomAll="";
                            foreach ($roomRay as $room) 
                                {
                                if (isset($roomCount[$room]["Count"])) $div=$roomCount[$room]["Count"];
                                else $div=1;
                                $value=GetValue($oid)/$div;
                                $roomCount[$room]["Value"]+=$value;
                                $roomAll.=" $room";
                                }
                            }
                        else 
                            {
                            $status+=GetValue($oid);
                            $count++;
                            if (isset($roomCount[$room]["Count"])) $div=$roomCount[$room]["Count"];
                            else $div=1;
                            $value=GetValue($oid)/$div;
                            $roomCount[$group]["Value"]+=$value;
                            }
                        }
                    $ipsOps = new ipsOps();
                    if ($groupConf["Sort"] != "No") $ipsOps->intelliSort($roomCount,"Value");    
                    if ($debug) 
                        {
                        echo "Sortierung angefordert mit :".json_encode($groupConf)." \n";    
                        echo "Folgende Werte werden so angezeigt. Es kann nach dem Temperaturwert sortiert werden. Index => Count,Value \n";    
                        print_r($roomCount);
                        }
                    foreach ($roomCount as $room => $entry) 
                        {
                        //echo "   ".str_pad($room,35)."   ".$entry["Value"]."\n";
                        $wert .= '<tr><td>'.$room.'</td><td>'.$this->formatEntry((float)$entry["Value"],$unit).'</td></tr>';
                        }

                    $wert .= '</table></td>';
                    //$wert="showTempGroupTable ".json_encode($config);
                    }
                $wert .= '</tr></table>';
                }
            else $wert .= "not available,DetectTemperatureHandler not installed.";
            return ($wert);
            }

        /* read configuration for showTempGroupWidget 
         *
         *
         *
         */

        function getConfigTempGroupWidget($groupsInput=false,$debug=false)
            { 
            if ( ($groupsInput===false) || ($groupsInput===null) )
                {
                if (isset($this->configuration["GroupTemp"]) ) $groupsConf=$this->configuration["GroupTemp"];
                else $groupsConf=array();
                }
            else $groupsConf = $groupsInput;

            $groups=array();
            //print_r($groupsConf);
            //if ( (is_array($groupsConf)) && (count($groupsConf)>0) )
                {
                foreach ($groupsConf as $index => $groupConf)
                    {
                    if (isset($groupConf["GROUP"])) 
                        {
                        $groups[$index]["Group"]=$groupConf["GROUP"];
                        if (isset($groupConf["UNIT"])) $groups[$index]["Unit"]=$groupConf["UNIT"];
                        else $groups[$index]["Unit"]="";
                        if (isset($groupConf["SORT"])) $groups[$index]["Sort"]=$groupConf["SORT"];
                        else $groups[$index]["Sort"]="No";
                        }
                    }
                }
            return ($groups);
            }

        /********************
         *
         * Zelle Tabelleneintrag für die Tabelle für die Anzeige von speziellen Registern
         * Register werden mit Highcharts angezteigt
         *
         **************************************/

		function showSpecialRegsWidget($configInput=false,$debug=false)
            {
            $wert = "";
            $wert .= "<table><tr>";
            if ($configInput===false) $specialRegsConf = $this->getConfigSpecialRegsWidget(false,$debug);
            else $specialRegsConf=$this->getConfigSpecialRegsWidget($configInput["Config"],$debug);

            //if ($debug) { echo "Special Regs Aufruf mit:\n"; print_R($specialRegsConf); }
            if ($this->scriptHighchartsID)      // ohne Script gehts nicht */
                {
                foreach ($specialRegsConf as $indexChart => $config)
                    {
                    if ($debug) echo "showSpecialRegsWidget: Highcharts Ausgabe von $indexChart (".json_encode($config).") : \n"; 

                    $endTime=time();
                    $startTime=$endTime-$config["Duration"];     /* drei Tage ist Default */
                    $chart_style=$config["Style"];            // line spline area gauge            gauge benötigt eine andere Formatierung

                    // Create Chart with Config File
                    // IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");               // ohne class, needs Charts
                    IPSUtils_Include ('Report_class.php', 					'IPSLibrary::app::modules::Report');

                    $CfgDaten=array();
                    //$CfgDaten['HighChartScriptId']= IPS_GetScriptIDByName("HC", $_IPS['SELF'])
                    //$CfgDaten["HighChartScriptId"]  = 11712;                  // ID des Highcharts Scripts
                    $CfgDaten["HighChartScriptId"]  = $this->scriptHighchartsID;                  // ID des Highcharts Scripts

                    $CfgDaten["ArchiveHandlerId"]   = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                    $CfgDaten['ContentVarableId']   = $this->contentID;
                    $CfgDaten['HighChart']['Theme'] ="ips.js";   // IPS-Theme muss per Hand in in Themes kopiert werden....
                    $CfgDaten['StartTime']          = $startTime;
                    $CfgDaten['EndTime']            = $endTime;

                    $CfgDaten['Ips']['ChartType']   = 'Highcharts';           // Highcharts oder Highstock default = Highcharts
                    $CfgDaten['RunMode']            = "file";     // file nur statisch über .tmp,     script, popup  ist interaktiv und flexibler
                    $CfgDaten["File"]               = true;        // Übergabe als File oder ScriptID

                    // Abmessungen des erzeugten Charts
                    $CfgDaten['HighChart']['Width'] = 0;             // in px,  0 = 100%
                    $CfgDaten['HighChart']['Height'] = 300;         // in px, keine Angabe in Prozent möglich
                    
                    $CfgDaten['title']['text']      = "";                           // weglassen braucht zuviel Platz
                    //$CfgDaten['subtitle']['text']   = "great subtitle";         // hioer steht der Zeitraum, default als Datum zu Datum Angabe
                    $CfgDaten['subtitle']['text']   = "Zeitraum ".nf($config["Duration"],"s");         // hier steht nmormalerweise der Zeitraum, default als Datum zu Datum Angabe
                    
                    //$CfgDaten["PlotType"]= "Gauge"; 
                    $CfgDaten['plotOptions']['spline']['color']     =	 '#FF0000';
                    $CfgDaten['plotOptions']['area']['stacking']     =	 'normal';

                    if ($config["Aggregate"]) $CfgDaten['AggregatedValues']['HourValues']     = 0; 
                    //if ($config["Step"]) $CfgDaten['plotOptions'][$chart_style]['step']     =	 $config["Step"];               // false oder left , in dieser Highcharts Version noch nicht unterstützt

                    $CfgDaten['plotOptions']['series']['connectNulls'] = true;                      // normalerweise sind Nullen unterbrochene Linien, es wird nicht zwischen null und 0 unterschieden
                    $CfgDaten['plotOptions']['series']['cursor'] = "pointer";

                    /* floating legend
                    $CfgDaten['legend']['floating']      = true;                   
                    $CfgDaten['legend']['align']         = 'left';
                    $CfgDaten['legend']['verticalAlign'] = 'top';
                    $CfgDaten['legend']['x']             = 100;
                    $CfgDaten['legend']['y']             = 70;  */

                    $CfgDaten['tooltip']['enabled']             = true;
                    $CfgDaten['tooltip']['crosshairs']             = [true, true];                  // um sicherzugehen dass es ein mouseover gibt
                    //$CfgDaten['tooltip']['shared']             = true;                        // nur für Tablets, braucht update

                    $CfgDaten['chart']['type']      = $chart_style;                                     // neue Art der definition
                    $CfgDaten['chart']['backgroundColor']   = $config["backgroundColor"];                // helles Gelb ist Default

                    foreach($config["OID"] as $index => $oid)
                        {
                        $serie = array();
                        $serie['type']                  = $chart_style;                 // muss enthalten sein
                        if ($config["Step"]) $serie['step'] = $config["Step"];                // false oder left

                        /* wenn Werte für die Serie aus der geloggten Variable kommen : */
                        if (isset($config["Name"][$index])) $serie['name'] = $config["Name"][$index];
                        else $serie['name'] = $config["Name"][0];
                        //$serie['marker']['enabled'] = false;                  // keine Marker
                        $serie['Unit'] = $config["Unit"];                            // sieht man wenn man auf die Linie geht
                        $serie['Id'] = $oid;
                        //$serie['Id'] = 28664 ;
                        $CfgDaten['series'][] = $serie;
                        }
                    $highCharts = new HighCharts();
                    $CfgDaten    = $highCharts->CheckCfgDaten($CfgDaten);
                    $sConfig     = $highCharts->CreateConfigString($CfgDaten);
                    $tmpFilename = $highCharts->CreateConfigFile($sConfig, "WidgetGraph_$indexChart");
                    if ($tmpFilename != "")
                        {
                        if ($debug) echo "Ausgabe Highcharts:\n";
                        $chartType = $CfgDaten['Ips']['ChartType'];
                        $height = $CfgDaten['HighChart']['Height'] + 16;   // Prozentangaben funktionieren nicht so richtig,wird an verschiedenen Stellen verwendet, iFrame muss fast gleich gross sein
                        $callBy="CfgFile";
                        if (is_array($config["Size"]))          // Defaultwert
                            {
                            $wert .= '<td>';                                
                            $wert .= "<iframe src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy="	. $tmpFilename . "' " ."width='%' height='". $height ."' frameborder='0' scrolling='no'></iframe>";                        
                            }
                        elseif (strpos($config["Size"],"x")) 
                            {
                            $multiplier=(integer)substr($config["Size"],0,strpos($config["Size"],"x"));
                            $widthInteger=$CfgDaten['HighChart']['Height']*$multiplier;
                            // Height wird wirklich so übernommen, nur mehr 316px hoch
                            $width=$widthInteger."px";
                            //echo "Neue Width ist jetzt ".$CfgDaten['HighChart']['Height']."*$multiplier=$width.\n";
                            //$height='700px';                            
                            $wert .= '<td style="width:'.$width.'px;height:'.$height.'px;background-color:#3f1f1f">';           // width:100%;height:500px; funktioniert nicht, ist zu schmal
                            //$width="100%";
                            //$width="auto"; 
                            //$height="auto";
                            //$height="100%"; 


                            $wert .= '<iframe style="width:'.$width.';height:'.$height.'"'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."' height='".$height."' frameborder='0' scrolling='no'></iframe>";                        
                            //$wert .= '<iframe style="height:'.$height.'"'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."' frameborder='0' scrolling='no'></iframe>";                        
                            //$wert .= '<iframe'." src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy=".$tmpFilename."'></iframe>";                        
                            }
                        else 
                            {
                            //print_R($config["Size"]);
                            $wert .= '<td???>';                                
                            $wert .= "<iframe src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy="	. $tmpFilename . "' " ."width='%' height='". $height ."' frameborder='0' scrolling='no'></iframe>";                        
                            }
                        
                        //$wert .= $tmpFilename;
                        }
                    $wert .= "</td>";
                    }
                }
            $wert .= "</tr></table>";
            return ($wert);
            }


        /* read configuration for showTempGroupWidget, eliminate unknown indizes */

        function getConfigSpecialRegsWidget($groupsInput=false,$debug=false)
            { 
            if ($groupsInput===false)
                {
                if (isset($this->configuration["SpecialRegs"]) ) $specialRegsConf=$this->configuration["SpecialRegs"];
                else $specialRegsConf=array();
                }
            else $specialRegsConf = $groupsInput;                

            if ($debug) echo "getConfigSpecialRegsWidget Configuration analysieren: ".json_encode($specialRegsConf)."\n";
            $specialRegs=array();
            foreach ($specialRegsConf as $index => $regsConf)
                {
                configfileParser($specialRegsConf[$index], $specialRegs[$index], ["OID","Oid","oid"],"OID",null);           // input ist $specialRegsConf, bereinigt dann in $specialRegs
                if ($specialRegs[$index]["OID"] != null) 
                    {
                    configfileParser($specialRegsConf[$index], $specialRegs[$index], ["Dauer","Duration","duration","DURATION"],"Duration",259200);         //3 Tage ist Default
                    configfileParser($specialRegsConf[$index], $specialRegs[$index], ["Name","NAME","name"]     ,"Name",$index);        
                    configfileParser($specialRegsConf[$index], $specialRegs[$index], ["Unit","UNIT","unit"]     ,"Unit","values");        
                    configfileParser($specialRegsConf[$index], $specialRegs[$index], ["Style","STYLE","style"]  ,"Style","line");        
                    configfileParser($specialRegsConf[$index], $specialRegs[$index], ["Step","STEP","step"]     ,"Step",false);                               // stufenweise Darstellung, statt die Punkte Verbindung, aktiv ist left
                    configfileParser($specialRegsConf[$index], $specialRegs[$index], ["AGGREGATE","Aggregate","aggregate"]     ,"Aggregate",false);  
                    configfileParser($specialRegsConf[$index], $specialRegs[$index], ["BackgroundColor","backgroundColor"]   ,"backgroundColor",'#FCFFC5');         // helles Gelb  
                    configfileParser($specialRegsConf[$index], $specialRegs[$index], ["Size","SIZE"]     ,"Size","[]");        
                    if ((is_array($specialRegs[$index]["OID"]))==false) $specialRegs[$index]["OID"]=[$specialRegs[$index]["OID"]];          // als array umspeichern
                    if ((is_array($specialRegs[$index]["Name"]))==false) $specialRegs[$index]["Name"]=[$specialRegs[$index]["Name"]];       // als array umpseichern
                    }
                }
            if ($debug) print_r($specialRegs);
            return ($specialRegs);
            }



        /********************
         *
         * Zelle Tabelleneintrag für die Tabelle für Innen und Aussentemperatur
         * macht zumindest 2 Zeilen mit jeweils 2 Zellen
         * ausgelegt als Aufruf als Widget
         *
         * es gibt auch eine Konfiguration
         *
         **************************************/

		function showTemperatureTable($colspan="",$config=false,$debug=false)
            {
            if ($config===false) $tempTableConf = $this->getTempTableConf(false,$debug);
            else $tempTableConf=$this->getTempTableConf($config["Config"],$debug);

            //if ($debug) echo "showTemperatureTable aufgerufen Config: ".json_encode($tempTableConf)."\n";

            $wert="";
            $wert.='<tr><td '.$colspan.'bgcolor="#c1c1c1"> <img src="user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>';
            $wert.='<td bgcolor="#ffffff"><img src="user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur"></td></tr>';

            if ( ($tempTableConf===null) || (count($tempTableConf)==0) )
                {
                if ($debug) echo "Keine Konfiguration angegeben. So wie früher die beiden functions innentemperatur() und aussentemperatur() einlesen.\n";    
                /* get Variables */
                $innen="innentemperatur";
                if (function_exists($innen)) $this->innentemperatur=$innen();				
                $aussen="aussentemperatur";
                if (function_exists($aussen)) $this->aussentemperatur=$aussen();
                $wert.='<tr><td '.$colspan.' bgcolor="#c1c1c1"><aussen>'.number_format($this->aussentemperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($this->innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
                if ($debug) echo "Aussen ".number_format($this->aussentemperatur, 1, ",", "" )."°C Innen ".number_format($this->innentemperatur,1, ",", "" )."°C \n";
                }
            else
                {    
                //print_R($tempTableConf);
                foreach ($tempTableConf as $entry) 
                    {
                    //print_r($entry);
                    $aussen="unknown";$innen="unknown";
                    if (isset($entry["Aussen"]["FUNCTION"])) $aussenWert=$entry["Aussen"]["FUNCTION"]();
                    if (isset($entry["Aussen"]["OID"])) $aussenWert=GetValue($entry["Aussen"]["OID"]);
                    if (isset($entry["Innen"]["FUNCTION"])) $innenWert=$entry["Innen"]["FUNCTION"]();
                    if (isset($entry["Innen"]["OID"])) $innenWert=GetValue($entry["Innen"]["OID"]);
                    $unit=$entry["Unit"];
                    $size=strtoupper($entry["Size"]);
                    if ($size == "LARGE") { $aussen="aussen"; $innen="innen"; }
                    elseif ($size == "MED")  { $aussen="aussenMed"; $innen="innenMed"; }
                    else { $aussen="aussenSmall"; $innen="innenSmall"; }
                    $wert.='<tr><td '.$colspan.' bgcolor="#c1c1c1"><'.$aussen.'>'.$aussenWert.$unit.'</'.$aussen.'></td><td align="center"> <'.$innen.'>'.$innenWert.$unit.'</'.$innen.'> </td></tr>';
                    }
                }
            return ($wert);
            }

        /* read configuration for showTempGroupWidget */

        function getTempTableConf($groupsInput=false,$debug=false)
            { 
            if ($groupsInput===false)
                {
                if (isset($this->configuration["Temperature"]) ) $tempConf=$this->configuration["Temperature"];
                else $tempConf=array();
                }
            else $tempConf = $groupsInput;                

            if ($debug) echo "getTempTableConf Configuration analysieren: ".json_encode($tempConf)."\n";
            $config=array();
            $indexNum = 0;

            if ($groupsInput===null) return ($config);

            foreach ($tempConf as $index => $regsConf)
                {
                $config[$indexNum]["name"] = $index;
                configfileParser($tempConf[$index], $config[$indexNum], ["Innen"],"Innen",null);
                configfileParser($tempConf[$index], $config[$indexNum], ["Aussen"],"Aussen",null);
                configfileParser($tempConf[$index], $config[$indexNum], ["Unit","UNIT"],"Unit","");
                configfileParser($tempConf[$index], $config[$indexNum], ["Size","SIZE"],"Size","Large");
                $config[$indexNum]["Innen"] = $this->analyseEntry($config[$indexNum]["Innen"]);
                $config[$indexNum]["Aussen"] = $this->analyseEntry($config[$indexNum]["Aussen"]);
                $indexNum++; 
                }
            if ($debug) print_r($config);
            return ($config);
            }

        function analyseEntry($value)
            {
            $analyze=array();
            if (is_numeric($value)) 
                {
                if (IPS_ObjectExists ($value)) $analyze["OID"]=$value;
                //echo "Wert ist Numerisch : \n";
                }
            elseif (function_exists($value)) $analyze["FUNCTION"]=$value;
            else echo "Nicht bekannter Wert, vielleicht IPSHeat ?\n";
            return ($analyze); 
            }


        /********************
         *
         * Zelle Tabelleneintrag für die Wettertabelle als Widget gemeinsam mit Innen/Aussentemperarur und einer ZusatzZeile
         *
         **************************************/

		function showWeatherTemperatureWidget($debug=false)
			{
            $wert="";
            $weather=$this->getWeatherData();

            if ($weather["todayDate"] != "") { $tableSpare='<td bgcolor="#c1c1c1"></td>'; $colspan='colspan="2" '; }
            else { $tableSpare=''; $colspan=""; }

            $wert.='<td><table id="nested">';

            $wert .= $this->showTemperatureTable($colspan,false,$debug);            // keine Config übergeben
            $wert.= '<tr>'.$this->additionalTableLines($colspan).'</tr>';
            $wert .= $this->showWeatherTable($weather);

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
         * macht eine html Zeile mit zwei oder drei Zellen in der Wettertabelle
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
	        $wert.='innenMed { color:black; background-color: #ffffff; height:100px; font-size: 50px; }';
	        $wert.='aussenMed { color:black; background-color: #c1c1c1; bgcolor: #c1c1c1; height:100px; font-size: 50px; }';
	        $wert.='innenSmall { color:black; background-color: #ffffff; height:100px; font-size: 30px; }';
	        $wert.='aussenSmall { color:black; background-color: #c1c1c1; bgcolor: #c1c1c1; height:100px; font-size: 30px; }';
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
            if (count($this->getOWDs())==0) return (false); 
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
			

        /*
         * additional Table Lines werden zwischen temperatur und Wetteranzeige eingebaut
         *
         */
			
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
	
        /* bottomTableLines()
         * die Bottom Table Line ist am unteren Ende des Bild und Wetter Bildschirms angesiedelt
         *
         * es wird eine eigene Tabellenzeile aufgebaut, die Zellen von darüber werden zusammengefasst und eine neue Tabelle in einer Zeile aufgebaut
         * Input pro Eintrag ist immer die Objekt OID
         *
         *
         */
			
	    function bottomTableLines($debug=false)
	        {
	        $wert="";
	        if ( (isset($this->configuration["Display"]["BottomLine"])) && (sizeof($this->configuration["Display"]["BottomLine"])>0) )
	            {
                $wert.='<table><tr>';
	            foreach($this->configuration["Display"]["BottomLine"] as $tableEntry)       // jeden Eintrag durchgehen
	                {
                    /* check ob eine oder mehrere OIDs angegeben wurden */
                    configfileParser($tableEntry, $config["Display"]["BottomLine"], ["OID","Oid"],"OID",false);                 //configfile als tableEntry vereinheitlichen, überprüfen, Wert für OID muss vorhanden sein und das Objekt erreichbar
                    if (isset($config["Display"]["BottomLine"]["OID"])) $oid=$config["Display"]["BottomLine"]["OID"];
                    if ($oid !== false)
                        {
                        if (is_array($oid)) $oidArray=$oid;                     // auch Arrays zulassen, dann sollten zwei Werte nebeneinander stehen
                        else $oidArray=[$oid];
                        /* check ob die Objekte vorhanden sind */
                        $objectExists=false;
                        $entries=count($oidArray);
                        if ($debug) echo "--------BottomLine ($entries) \n";
                        foreach ($oidArray as $oid) if (IPS_ObjectExists($oid)) $objectExists=true;         // wenn zumindest eines der Objekte existiert weitermachen
                        if ($objectExists)
                            {
                            $wert.='<td>';
                            configfileParser($tableEntry, $config["Display"]["BottomLine"], ["Name","NAME"],"Name",IPS_GetName($oid)); 
                            configfileParser($tableEntry, $config["Display"]["BottomLine"], ["Icon","ICON"],"Icon","IPS");
                            configfileParser($tableEntry, $config["Display"]["BottomLine"], ["Integrate","INTEGRATE","integrate"],"Integrate",false);
                            configfileParser($tableEntry, $config["Display"]["BottomLine"], ["Property","PROPERTY","property"],"Property",null);            // default kein Eintrag wenn kein Eintrag
                            if ($debug) 
                                {
                                $i=1;
                                if (count($oidArray)==1) echo "   Eintrag : Name ".$config["Display"]["BottomLine"]["Name"]." OID $oid Icon ".$config["Display"]["BottomLine"]["Icon"]." Value ".GetValue($oid)."\n";
                                else foreach ($oidArray as $oid) echo "   ".$i++.":Eintrag : Name ".$config["Display"]["BottomLine"]["Name"]." OID $oid Icon ".$config["Display"]["BottomLine"]["Icon"]." Value ".GetValue($oid)."\n";
                                }
                            $wert.='<img src="user/Startpage/user/icons/'.$config["Display"]["BottomLine"]["Icon"].'.svg" alt="'.$config["Display"]["BottomLine"]["Icon"].' Icon">';
                            $wert.='</td><td>';
                            $init=true; $addInfo="";
                            foreach ($oidArray as $oid)
                                {
                                $archiveID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                                if (isset($tableEntry["Property"])) 
                                    {
                                    $variableChanged=IPS_GetVariable($oid)["VariableChanged"];
                                    $timeGone=time()-$variableChanged;
                                    $startOfToday=mktime(0,0,0,date("m"), date("d"), date("Y"));
                                    if ($timeGone<(time()-$startOfToday))                   $addInfo=date("H:i",$variableChanged);
                                    elseif ($timeGone<(time()-($startOfToday-7*24*60*60)))  $addInfo=date("D H:i",$variableChanged);
                                    elseif ($timeGone<(time()-($startOfToday-60*24*60*60))) $addInfo=date("d.m H:i",$variableChanged); 
                                    else                                                    $addInfo=date("d.m.Y H:i",$variableChanged);
                                    if ($debug) 
                                        {
                                        echo "      ".date("d.m.Y H:i:s",$startOfToday)."\n";
                                        echo "      Variable Changed $addInfo, got from Property:".$tableEntry["Property"]."   ".json_encode(IPS_GetVariable($oid))."\n";
                                        $endtime=time();
                                        $starttime=$endtime-$timeGone-(10*60*60);
                                        $werteLog  = @AC_GetLoggedValues($archiveID,$oid,$starttime,$endtime,0);                                    
                                        if ($werteLog===false) ;        // $oid bleibt unverändert
                                        else
                                            {
                                            $count=0; $sum=0; $init=true;
                                            foreach ($werteLog as $eintrag)
                                                {
                                                if ($init) { $start=$eintrag["Value"]; $init=false; }
                                                echo "             ".date("d.m.Y H:i",$eintrag["TimeStamp"])."    ".$eintrag["Value"]."\n";
                                                $count++;
                                                }
                                            //$value = (float)$sum/$count;
                                            //if ($debug) echo "     Integrate Values from last ".$config["Display"]["BottomLine"]["Integrate"]." seconds. Results into Value ".number_format($value,0,",",".")."\n";
                                            //$oid=$value;            // formatEntry erkennt oid (wenn integer) und oid als value
                                            }
                                        }
                                    }
                                if ($config["Display"]["BottomLine"]["Integrate"]>59) 
                                    {
    		                        $endtime=time();
	    	                        $starttime=$endtime-$config["Display"]["BottomLine"]["Integrate"];   // die Werte entsprechend dem angegebenen Zeitraum laden
                                    $werteLog  = @AC_GetLoggedValues($archiveID,$oid,$starttime,$endtime,0);                                    
                                    if ($werteLog===false) ;        // $oid bleibt unverändert
                                    else
                                        {
                                        $count=0; $sum=0;
                                        foreach ($werteLog as $eintrag)
                                            {
                                            $sum += $eintrag["Value"];
                                            $count++;
                                            }
                                        $value = (float)$sum/$count;
                                        if ($debug) echo "     Integrate Values from last ".$config["Display"]["BottomLine"]["Integrate"]." seconds. Results into Value ".number_format($value,0,",",".")."\n";
                                        $oid=$value;            // formatEntry erkennt oid (wenn integer) und oid als value
                                        }
                                    }
                                if (isset($tableEntry["Profile"]))                          // es gibt ein IP Symcon Profil
                                    {
                                    $profileConfig=IPS_GetVariableProfile ($tableEntry["Profile"]);
                                    $color="F1F1F1";                                // default color
                                    if (isset($profileConfig["Associations"]))  
                                        {
                                        foreach ($profileConfig["Associations"] as $index => $association)
                                            {
                                            if ($association["Value"]<=GetValue($oid))                  // Wert groesser 0 color setzen usw.
                                                {
                                                //print_R($association);
                                                $color = "000000".dechex($association["Color"]);
                                                $color = substr($color,-6);
                                                if ($debug) echo "     Farbe Association ist #$color  (".$association["Color"].")\n";
                                                //if (hexdec($color) > 1000000) $color="1F2F1F";
                                                }
                                            }
                                        //$result='<p style="background-color:black;color:#'.$color.'";>'.$result.'</p>';
                                        //$result='<p style="background-color:'.$color.';color:white;">'.$result.'</p>';
                                        }
                                    if ($debug) 
                                        {
                                        print_R($profileConfig);
                                        echo "Letzte Farbe Association ist #$color\n";
                                        }
                                    if ($init) { $wert .='<addText style="background-color:#'.$color.';color:darkgrey;">'.$config["Display"]["BottomLine"]["Name"].'   '; $init=false; }
                                    else $wert .= '<addText style="background-color:#'.$color.';color:darkgrey;">|';
                                    $wert .= $this->formatEntry($oid, $tableEntry["UNIT"]).'</addtext>';
                                    }
                                elseif (isset($tableEntry["UNIT"]))                         // es gibt eine Einheitsbezeichnung
                                    {
                                    if ($init) { $wert .= '<addText>'.$config["Display"]["BottomLine"]["Name"].'   ';$init=false; }
                                    else $wert .= '<addText>|';
                                    $wert .= $this->formatEntry($oid, $tableEntry["UNIT"]).'</addtext>';
                                    }
                                else                                                        // es gibt nix
                                    {
                                    if ($init) { $wert .= '<addText>'.$config["Display"]["BottomLine"]["Name"].'   ';$init=false; }
                                    else $wert .= '<addText>|';
                                    $wert .= $this->formatEntry($oid, "").'</addtext>';
                                    }
                                if ($addInfo != "") $wert .= '<addText>    '.$addInfo.'</addtext>';
                                }
                            $wert.='</td>';                                
                            }
                        }
	                }
                $wert.='</tr></table>';
	            //print_r($this->configuration["AddLine"]);
				//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
	            //echo $wert;
	            }
	        return ($wert);            
	        }

        /* formatting with hints 
         * es wird die OID übergeben. Wenn die OID nicht integer ist dann als Wert betrachten. Übergabe in diesem Fall als float
         * return ist der formattierte Wert
         * 
         */

        function formatEntry($oid, $format)
            {
            $wert="";
            switch (strtoupper($format))
                {
                case "PPM":
                    if (is_integer($oid)) $wert.=number_format(GetValue($oid), 0, ",", "" ).$format;
                    else $wert.=number_format($oid, 0, ",", "" ).$format;
                    break;
                case "AUTO":
                    if (is_integer($oid))$wert.=GetValueIfFormatted($oid);
                    else $wert.=number_format($oid, 0, ",", "" );
                    break;
                case "":
                case "TEMP":
                    if (is_integer($oid)) $wert.=number_format(GetValue($oid), 1, ",", "" ).'°C';                    
                    else $wert.=number_format($oid, 1, ",", "" ).'°C';
                    break;
                case "MM":
                    if (is_integer($oid)) $wert.=number_format(GetValue($oid), 1, ",", "" ).'mm';                    
                    else $wert.=number_format($oid, 1, ",", "" ).'mm';
                    break;                    
                case "BPS":
                    if (is_integer($oid)) $value=GetValue($oid);
                    else $value=$oid;
                    if ($value > 10000) $wert.=number_format($value/1000, 0, ",", "." )."kbps";
                    else $wert.=number_format($value, 0, ",", "." )."bps";               // schöne grosse Integer Zahlen klar darstellen
                    break;
                default:   
                    if (is_integer($oid)) $wert.=number_format(GetValue($oid), 3, ",", "." ).$format;               // schöne grosse Integer Zahlen klar darstellen
                    else $wert.=number_format($oid, 3, ",", "" ).$format;
                    break;
                }
            return($wert);
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