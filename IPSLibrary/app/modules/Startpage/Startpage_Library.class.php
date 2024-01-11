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

    /* Implementierte Klassen
     *      StartpageHandler
     *      StartpageWidgets
     *
     */

    /*
     * Klasse StartpageHandler
     *
     * sammelt alle Routinen für die Erstellung und Verwaltung der Startpage/Dashboard
     * mit der Absage des IPSWeather Moduls wurden die Wetter Aktivitäten hier her verlagert.
     *
     * es werden die Versuche verstärkt das Webdesign auf eine responsive Darstellung zu erneuern
     *
     * Hauptroutine ist StartPageWrite mit den 4 unterschiedlichen Darstellungen
     * aufgerufen werden dazu analog die folgenden Funktionen:   showHierarchy, [showPictureWidget,showTopology], [showDisplayStation, bottomTableLines], [showPictureWidget,showWeatherTemperatureWidget,bottomTableLines]     
     *
     * _construct
     * getWorkDirectory
     * getAvailableIcons
     * getHighchartsID
     *
     * setStartpageConfiguration            Konfiguration vereinheitlichen und intern abspeichern
     * getStartpageConfiguration
     * getStartpageDisplayConfiguration
     * getOWDs
     * configWeather
     * readPicturedir
     * getPictureDirectory
     *
     * StartPageWrite                   Darstellung der Startpage am Webfront
     * showDisplayStation
     * showAstronomyWidget
     * showAstronomy
     * showTopology
     * mergeTopologyObjectsSP               Deprectaed, siehe AllgemeineDefinitionen
     * transformConfigWidget
     * writeTable
     * transformStatus
     * writeCell
     * writeValue
     * writeMovement
     * writeTemperature
     * writeHumidity
     *
     * showGraphWidget
     *
     * showHierarchy
     * drawTable
     * drawCell
     * totalChildren
     * countChildren
     *
     * showMediaWidget
     *
     * showPictureWidget
     *
     * showWeatherTable
     *
     *
     *
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

		protected $configuration = array();				// die angepasste, standardisierte Konfiguration
		protected $aussentemperatur, $innentemperatur;
		
        public $newstyle;                               // wenn true, IPS 7 oder größer, kein Webfront Verzeichnis, alles im User anlegen
		public $picturedir, $imagedir, $icondir;			// hier sind alle Bilder für die Startpage abgelegt
        public $workdir;            // Arbeitsverzeichnis, zB VLC Start Scripts

        protected $scriptHighchartsID;                      // für Higcharts, die IPSHighcharts script ID
        protected $contentID;                             // für Highcharts als Dummy
        protected $installedModules;                      // welche Module sind installiert

		public $CategoryIdData, $CategoryIdApp;			// die passenden Verzeichnisse

        protected $dosOps,$ipsOps;                                // ein paar Routinen ohne jedesmal new zu machen
		
		protected $OWDs;				// alle Openweather Instanzen

		/**
		 * @public
		 *
		 * Initialisierung des IPSMessageHandlers
		 *
		 */
		public function __construct($debug=false)
			{
			/* standardize configuration */
			
			$this->dosOps = new dosOps();  
            $this->ipsOps = new ipsOps();
                                                     // wird überall verwendet
			$this->setStartpageConfiguration(false,$debug);
			
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

            $picDir =    $this->dosOps->correctDirName(IPS_GetKernelDir()."user\\Startpage\\user\\pictures\\"); 
            $newstyle =  is_dir($picDir);
            $altPicDir = $this->dosOps->correctDirName(IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\pictures\\");
            $oldstyle =  is_dir($altPicDir);

            $ipsVersion = $this->ipsOps->ipsVersion();
            if ($debug)
                {
                if ($ipsVersion["Major"]>6) 
                    {
                    echo "IPS Version 7 oder größer, neue Verzeichnisstrukturen anstelle webfront : $picDir \n";
                    if ($oldstyle && $newstyle) echo "   bisheriges und neues Verzeichnis vorhanden.\n"; 
                    elseif ($newstyle) echo "   neues Verzeichnis vorhanden. Alles in Ordnung.\n";
                    elseif ($oldstyle) echo "   bisheriges Verzeichnis vorhanden.\n";
                    else               echo "   neues und bisheriges Verzeichnis NICHT vorhanden.\n";
                    }
                else 
                    {
                    echo "IPS Version 6 oder früher, bestehende Verzeichnisstrukturen mit webfront.\n";    
                    echo "Verzeichnis für IPS Version 6 oder früher : $altPicDir \n";
                    if ($oldstyle) echo "   Verzeichnis vorhanden.\n";
                    else           echo "   Verzeichnis NICHT vorhanden.\n";
                    }
                }
            if ($oldstyle && $newstyle) { $oldstyle=false; }
            if ($oldstyle)
                {
                $this->picturedir = $this->dosOps->correctDirName(IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\pictures\\");
                $this->imagedir   = $this->dosOps->correctDirName(IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\images\\");                   // Astronomy Path to Moon Pic: user/Startpage/user/images/mond/mond357.gif
                $this->icondir    = $this->dosOps->correctDirName(IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\icons\\");
                }
            if ($newstyle)
                {
                $this->picturedir = $this->dosOps->correctDirName(IPS_GetKernelDir()."user\\Startpage\\user\\pictures\\");
                $this->imagedir   = $this->dosOps->correctDirName(IPS_GetKernelDir()."user\\Startpage\\user\\images\\");                   // Astronomy Path to Moon Pic: user/Startpage/user/images/mond/mond357.gif
                $this->icondir    = $this->dosOps->correctDirName(IPS_GetKernelDir()."user\\Startpage\\user\\icons\\");
                }

			$this->contentID=CreateVariable("htmlChartTable",3, $this->CategoryIdData,0,"~HTMLBox",null,null,"Graph");

            $verzeichnis=$this->dosOps->getWorkDirectory();
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
			$this->newStyle = $newstyle;                                        // wenn true kein webfront verzeichnis mehr
            }


		/*
		 * Abstrahierung der Startpage Konfiguration
		 *
		 */

        function getWorkDirectory()
            {
            return($this->workdir);
            }

        public function getAvailableIcons()
            {
            return($this->dosOps->readdirToArray($this->icondir));
            }

        public function getHighchartsID() : int                                 // integer zurückgeben
            {
            return ($this->scriptHighchartsID);
            }

		/*
		 * Abstrahierung der Startpage Konfiguration
		 * Einlesen aus der Datei und Abspeichern in der Class
         * configInput -> config
         *
         * Configuration Struktur:
         *      Directories
         *          Pictures
         *          Images
         *          Icons
         *          Scripts
         *      Display
         *          Weather
         *              Weathertable
         *          BottomLine
         *          AddLine
         *          WidgetStyle
         *              RowMax
         *              ColMax
         *              Screens
         *          MediaObject
         *
         *      Monitor
         *      Widgets Sub
         * oder
         *      SpecialRegs
         *      Temperature
         *
		 */

		function setStartpageConfiguration($config=false,$debug=false)
	        {
            $configInput=array();
            $systemDir     = $this->dosOps->getWorkDirectory(); 

            if ((function_exists("startpage_configuration"))===false) IPSUtils_Include ("Startpage_Configuration.inc.php","IPSLibrary::config::modules::Startpage");				
            if (function_exists("startpage_configuration"))  $configInput = startpage_configuration();
            else echo "*************Fehler, Startpage Konfig File nicht included oder Funktion startpage_configuration() nicht vorhanden. Es wird mit Defaultwerten gearbeitet.\n";

            if (($config !== false) && (is_array($config))) $configInput=$config;       // Config Overwrite, Testfunktion für externe Werte

            $config=array();
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

            /* Default Configs wenn kein Widget */
            configfileParser($configInput, $config, ["SpecialRegs"],"SpecialRegs",null);                 // Default, wenn es nur eines gibt
            configfileParser($configInput, $config, ["Temperature"],"Temperature",null);                 // Default, wenn es nur eines gibt

            /* Sub Directories */
            configfileParser($configInput["Directories"], $config["Directories"], ["Pictures"],"Pictures",null);                // null es wird als Default zumindest ein Indexknoten angelegt
            if (strpos($config["Directories"]["Pictures"],"C:/Scripts/")===0) 
                {
                $config["Directories"]["Pictures"]=substr($config["Directories"]["Pictures"],10);      // Workaround für C:/Scripts"
                $config["Directories"]["Pictures"] = $this->dosOps->correctDirName($systemDir.$config["Directories"]["Pictures"]);
                }
            configfileParser($configInput["Directories"], $config["Directories"], ["Images"],"Images",null);                // null es wird als Default zumindest ein Indexknoten angelegt
            configfileParser($configInput["Directories"], $config["Directories"], ["Icons"],"Icons",null);                      // null es wird als Default zumindest ein Indexknoten angelegt
            configfileParser($configInput["Directories"], $config["Directories"], ["Scripts"],"Scripts",null);                // null es wird als Default zumindest ein Indexknoten angelegt
            if (strpos($config["Directories"]["Scripts"],"C:/Scripts/")===0) $config["Directories"]["Scripts"]=substr($config["Directories"]["Scripts"],10);      // Workaround für C:/Scripts"
            $config["Directories"]["Scripts"] = $this->dosOps->correctDirName($systemDir.$config["Directories"]["Scripts"]);

            /* Sub Display */
            configfileParser($configInput["Display"], $config["Display"], ["Weather"],"Weather","[]"); 
            configfileParser($configInput["Display"], $config["Display"], ["BottomLine"],"BottomLine","[]"); 
            configfileParser($configInput["Display"], $config["Display"], ["AddLine"],"AddLine","[]"); 
            configfileParser($configInput["Display"], $config["Display"], ["WidgetStyle"],"WidgetStyle",'{"RowMax":2,"ColMax":3,"Screens":1}');             // bereits als json_encode übergeben
            configfileParser($configInput["Display"], $config["Display"], ["Mediaobject","mediaobject","MEDIAOBJECT","MediaObject"],"MediaObject",null); 
            configfileParser($configInput["Display"], $config["Display"], ["Topology","topology","TOPOLOGY"],"Topology","[]");          // leeres array ist default

            /* Sub Sub Display Weather */
            configfileParser($configInput["Display"]["Weather"], $config["Display"]["Weather"], ["Weathertable"],"Weathertable","Active"); 

            /* Sub Sub Display Widgetstyle */
            configfileParser($configInput["Display"]["WidgetStyle"], $config["Display"]["WidgetStyle"], ["RowMax"],"RowMax",2); 
            configfileParser($configInput["Display"]["WidgetStyle"], $config["Display"]["WidgetStyle"], ["ColMax"],"ColMax",3); 
            configfileParser($configInput["Display"]["WidgetStyle"], $config["Display"]["WidgetStyle"], ["Screens"],"Screens",1); 

            /* Sub Sub Display AddLine */
            $configAddLine=array(); 
            if (sizeof($config["Display"]["AddLine"])>0)
                {
                foreach ($config["Display"]["AddLine"] as $name => $widget)              // alle Widgets Speks durchgehen, können auf mehrere Screens verteilt sein !
                    {
                    configfileParser($widget, $configAddLine[$name], ["TYPE","Type"],"Type" ,$name);  
                    configfileParser($widget, $configAddLine[$name], ["NAME","Name"],"Name" ,$name); 
                    $configAddLine[$name]["Type"] = strtoupper($configAddLine[$name]["Type"]);
                    switch ($configAddLine[$name]["Type"])
                        {
                        case "INFOFIELD":
                            break;
                        default: 
                            configfileParser($widget, $configAddLine[$name], ["OID","Oid", "oid"],"OID" ,false); 
                            break;
                        }
                    }
                $config["Display"]["AddLine"]=$configAddLine;
                }

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
            $this->configuration=$config;                
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

        /* Kapselung des Bilder Verzeichnisses 
         * selbes Wording wie für WorkDirectory
         */
		function getPictureDirectory()
			{
            return ($this->picturedir);
            }
	
		/**************************************** FUNCTIONS *********************************************************/


        /* StartPageWrite, die Startpage vollständig schreiben, erstellt eine html Tabelle
         * anhand von Pagetype wird eine von mehreren möglichen Darstellungen ausgewählt. createActionProfileByName("StartpageControl")
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
         * Bei PageType Station erfolgt die Darstellung mit showDisplayStation 
         * Es wird ein Canvas erstellt mit einer vorerst fixen 3 spaltigen und 2 zeiligen Tabelle auf der einzelnen Widgets platziert werden
         * die Platzierung ist vorerst statisch kann aber konfiguriert werden
         *
         * noch abhängig vom Noweather Parameter
         *
         *
         */

		function StartPageWrite($PageType=1,$showfile=false,$debug=false)
			{
			$Config=$this->configWeather();
			$noweather=!$Config["Active"];
            if ($debug)
                {
                echo "StartPageWrite aufgerufen für Seite mit PageType $PageType, Debug aktiviert:\n";
                //secho "Weather Konfiguration: ".json_encode($Config)."\n";
                }                
	    	/* html file schreiben, Anfang Style für alle gleich */
			$wert="";
		    $wert.= $this->writeStartpageStyle("");
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
                    $wert.= $this->showHierarchy($debug);
                    $wert.='</tr></table>';
                    $wert.='</div>';
                    break;
                case 3:        // Topologie
                    if ($debug) echo "Page Type Style is Topology.\n";
                    //echo "Topologiedarstellung erster Entwurf, verwendet showPictureWidget und showTopology.\n";
                    $wert.='<table id="startpage">';
                    $wert.='<tr>';
                    $wert.='<td>';
                    $wert.= $this->showPictureWidget($showfile,$debug);
                    $wert.='</td>';

                    $configTopology=$this->getStartpageDisplayConfiguration()["Topology"];
                    
                    $wert.= $this->showTopology(false,$configTopology,$debug);          // false internes topology array erzeugen, debug
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
                case 5:         // mit Media auf der linken Seite (OE3 Player)
                case 1:         // mit Picture
                    /*******************************************
                     *
                     * PageType==1,Diese Art der Darstellung der Startpage wird Bildschirmschoner genannt , Standard und bewährte Darstellung
                     * Bild und Wetterstation als zweispaltige Tabelle gestalten oder neu die Möglichkeit für Formatierung als Div Frames
                     * <div id=“resp-table” style="width: 100%; display: table;">               oder im css #resp-table { ... }
                         <div class=“resp-table-row” style="display: table-row;">               oder im css .resp-table-row{ ... }
                            <div class=“table-body-cell” style="display: table-cell;"> 
                                Cell 1–1
                                </div>
                            </div>
                     *    </div>
                     *
                     * showPictureWidget ergänzt um zwei div frames die eingebettet werden können
                     *
                     *************************/
                    //$wert.='<div id=“resp-table” style="padding-bottom: 56%;; width: 100%; display: table;">'; 
                    $wert.='<div id=“resp-table” style="display: table; height:800px; width:auto">'; 
                    $wert .=    '<div class=“resp-table-row” style="display: table-row;">';               
                    $wert .=        '<div class=“table-body-cell” style="display: table-cell;">'; 
                    if ($PageType==1) 
                            {
                            if ($debug) echo "Page Type Style is Picture.\n"; 
                            $wert .=     $this->showPictureWidget($showfile);	
                            }
                        else 
                            {
                            if ($debug) echo "Page Type Style is Media.\n";
                            $switchMediaID = IPS_GetVariableIDByName("SwitchMedia",$this->CategoryIdData);  
                            $mediaScreen=GetValue($switchMediaID);
                            $wert.= $this->showMediaWidget($mediaScreen,$debug);                   // wenn mehrere Frames einfach durchschalten
                            SetValue($switchMediaID,$mediaScreen);  
                            }	
                    $wert .=			'</div>';
                    $wert .=		'<div class=“table-body-cell” style="display: table-cell; background-color:#202420; vertical-align:top;">';                
                    $wert .= 			$this->showWeatherTemperatureWidget(false,$debug);              // true inline block, false table
                    $wert .=			'</div>';                                                                  
                    $wert .=		'</div>';                                               
                    $wert .=    '<div class=“resp-table-row” style="display: table-row;">';                                 
                    $wert .= '<table><tr>';
                    $wert.='<td colspan="2">';                    
                    $wert.=$this->bottomTableLines($debug);                // komplette zweite Zeile, ist wesentlich dünner
                    $wert.='</td>';
                    $wert .= '</tr></table>';
                    $wert .=		'</div>';                                               
                    $wert .=	'</div>';
                    if (false)
                        {
                        $wert.='<table id="startpage">';
                        //$wert.='<tr><th>Bild</th><th>Temperatur und Wetter</th></tr>';  /* Header für Tabelle */
                        //$wert.='<td><img id="imgdisp" src="'.$filename.'" alt="'.$filename.'"></td>';
                        $wert.='<tr>';                                                   // komplette Zeile, diese fällt richtig dick aus  
                        $wert.='<td height="40%">';     // sonst zu gross
                        if ($PageType==1) 
                            {
                            if ($debug) echo "Page Type Style is Picture.\n";
                            $wert.= $this->showPictureWidget($showfile);                          // erste Zelle, 
                            }
                        else 
                            {
                            if ($debug) echo "Page Type Style is Media.\n";
                            $switchMediaID = IPS_GetVariableIDByName("SwitchMedia",$this->CategoryIdData);  
                            $mediaScreen=GetValue($switchMediaID);
                            $wert.= $this->showMediaWidget($mediaScreen,$debug);                   // wenn mehrere Frames einfach durchschalten
                            SetValue($switchMediaID,$mediaScreen);  
                            }
                        if ( $noweather==false ) 
                            {
                            $wert.= $this->showWeatherTemperatureWidget(false,$debug);     // zweite Zelle, eine dritte gibt es nicht
                            }
                        elseif ($debug) echo "no weather Display configured.\n";
                        $wert.='</td>';
                        $wert.='</tr>';
                        $wert.='<tr>';                                                   // komplette Zeile, diese fällt richtig dick aus  
                        $wert.='<td colspan="2">';                    
                        $wert.=$this->bottomTableLines($debug);                // komplette zweite Zeile, ist wesentlich dünner
                        $wert.='</td>';
                        $wert.='</tr>';
                        $wert.='</table>';
                        }
                    break;
                case 6:             // Graph
                    /*******************************************
                     *
                     * Bild und Wetterstation als zweispaltige Tabelle mit responsive Mode und Formatierung als Div Frames
                     * <div id=“resp-table” style="width: 100%; display: table;">               oder im css #resp-table { ... }
                         <div class=“resp-table-row” style="display: table-row;">               oder im css .resp-table-row{ ... }
                            <div class=“table-body-cell” style="display: table-cell;"> 
                                Cell 1–1
                                </div>
                            </div>
                     *    </div>
                     *
                     * showPictureWidget ergänzt um zwei div frames die eingebettet werden können
                     *
                     * Javascript Funktionen
                     *
                     * viewportHandler(event)       Darstellung anpassen anhand der Breite des Viewport
                     *
                     * enterFullscreen              requestFullscreen für unterschiedliche Browser, teil des Scripts bevor html iFrame gestartet wird, sonst wird nur dieser iFrame gross
                     *
                     *
                     *
                     *******ACHTUNG WIRD NICHT VERWENDET, CHECK PHP FILE in WEBFRONT******************/ 
                    $style="full";
                    if ($style=="full")
                        {
                        $wert .= '<div id=“resp-table” style="width: 100%; display: table;">';            // display type Table
                        $wert .=            $this->showGraphWidget($style, $debug);
                        $wert .= '</div>';
                        }
                    else    
                        {
                        $wert .= '<div id=“resp-table” style="width: 100%; display: table;">';            // display type Table
                        $wert .= '   <div class=“resp-table-row” style="display: table-row;">';                // display type  Row
                        $wert .= '      <div class=“table-body-cell” style="width: 70%; display: table-cell;">';                 // display type Cell
                        $wert .=            $this->showGraphWidget($style, $debug);
                        $wert .= '      </div>';
                        $wert .= '      <div class=“table-body-cell” style="width: 30%; display: table-cell;">';                 // Cell
                        $wert .=            $this->showWeatherTemperatureWidget(true,$debug);                   // responsive Design
                        $wert .= '      </div>';                                                                    // ende Cell
                        $wert .= '    </div>';                                                                    // Ende Row
                        $wert .= '</div>';
                        }
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
         * verfügbare Widgets sind weiter unten inline gelistet
         *
         */

		function showDisplayStation($subscreen=1, $debug=false)
			{
	        if (isset($this->configuration["Widgets"]) ) $config=$this->configuration["Widgets"];
            else $config=array();
            if ($debug) 
                {
                echo "   showDisplayStation aufgerufen: \n";
                //echo "showDisplayStation: ".json_encode($config)."\n";
                //print_R($config);
                foreach ($config as $row => $config2) 
                    {
                    foreach ($config2 as $column => $screens)
                        {                        
                        echo "    $row    $column    ";
                        if (isset($screens[$subscreen])) echo json_encode($screens[$subscreen]);
                        echo "\n";
                        }
                    }
                }
            $wert = "";
            foreach ($config as $row => $config2)
                {
                $wert.='<tr>';                        
                foreach ($config2 as $column => $screens)               // Zeilen und Spalten durchgehen, wenn es ein Widget für den aktuellen Screeen gibt bearbeiten
                    {
                    if (isset($screens[$subscreen])) 
                        {
                        $entry=$screens[$subscreen];
                        if ($debug) 
                            {
                            echo "   Row $row Col $column Show ".str_pad($entry["Type"],25)."   ".json_encode($entry)."\n";
                            //print_R($entry);
                            }
                        $tdformat='bgcolor="'.$entry["Format"]["BGColor"].'"';

                        /* verfügbare Widgets:
                         *  Astronomy               showAstronomyWidget
                         *  Moon                    showAstronomyWidget
                         *  Weather                 showWeatherTable
                         *  Grouptemp               showTempGroupWidget
                         *  Heating                 showHeatingWidget
                         *  Picture                 showPictureWidget
                         *  Specialregs             showSpecialRegsWidget                   Register werden mit Highcharts angezeigt
                         *  Temperature             showTemperatureTable
                         *  empty
                         *  Rainmeter               showRainmeterWidget
                         *  Charts                  showChartsWidget                        Tabelle mit Börsenkursen
                         *
                         */
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
                            case "RAINMETER":
                                $wert.='<td><table border="0" bgcolor="#f1f1f1">';
                                $wert .= $this->showRainmeterWidget($entry,$debug);
                                $wert .= '</table></td>';
                                break;
                            case "CHARTS":
                                $wert.='<td><table border="0" bgcolor="#f1f1f1">';
                                $wert .= $this->showChartsWidget($entry,$debug);
                                $wert .= '</table></td>';
                                break;
                            default:
                                if ($debug) echo " Fehler  Row $row Col $column Widget ".$entry["Type"]." nicht verfügbar\n"; 
                                $wert.='<td><table border="0" bgcolor="#f1ff11">';
                                $wert .= "<td>reserved for Widget Type ".$entry["Type"].", available soon.</td>";         // erster Parameter ist colspan als config für table
                                $wert .= '</table></td>';                                                       
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

        /* showTopology, Darstellung der Topoologie als Tabelle 
         *
         * Übernimmt $topologyWithLinks für die Darstellung der Topologie mit aktuellen Werten, sonst werden die Werte direkt aus EvaluateHardware_Configuration erzeugt
         * Zusatzparameter im configInput
         *  Cell
         *  Headline    erste Zeile in der Tabelle
         *  Baseline    topology beginnt mit diesem Place für die Visualisierung
         *
         * Get Topology Liste aus EvaluateHardware_Configuration
         * die Topologie mit den Geräten anreichen. Es gibt Links zu INSTANCES and OBJECTS 
         *
         **************************************/

		function showTopology($topologyWithLinks=false,$configInput=false, $debug=false)
			{
            if ($debug) echo "showTopology aufgerufen:\n";
            $wert="";

            $config=array();
            if (is_array($configInput)===false) $configInput=array(); 
            configfileParser($configInput, $config, ["Cell","cell","CELL"],"Cell","Table");          // leeres array ist default
            configfileParser($configInput, $config, ["Headline","headline","HEADLINE"],"Headline","");          // empty string ist default
            configfileParser($configInput, $config, ["Baseline","baseline","BASELINE"],"Baseline",false);          // false ist default

            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
            IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');            

            if (is_array($topologyWithLinks))
                {
                $topologyPlusLinks=$topologyWithLinks;
                }
            else
                {              // Get Topology Liste aus EvaluateHardware_Configuration and add information needed
                $DetectDeviceHandler = new DetectDeviceHandler($debug);
                $topology           = $DetectDeviceHandler->Get_Topology();
                $eventConfiguration = $DetectDeviceHandler->Get_EventConfigurationAuto();        // IPSDetectDeviceHandler_GetEventConfiguration()

                /* die Topologie mit den Geräten anreichen:
                *    wir starten mit Name, Parent, Type, OID, Children  
                * Es gibt Links zu Chíldren, INSTANCE und OBJECT 
                *    Children, listet die untergeordneten Eintraege
                *    OBJECT sind dann wenn das Gewerk in der Eventliste angegeben wurde, wie zB Temperature, Humidity aso
                *    INSTANCE ist der vollständigkeit halber für die Geräte
                *
                * Damit diese Tabelle funktioniert muss der DetDeviceHandler fleissig register definieren
                */

                $topologyPlusLinks=$DetectDeviceHandler->mergeTopologyObjects($topology,$eventConfiguration,$debug);
                }

            if ($debug) 
                {
                if ($debug>1)
                     {
                     echo "=====================================================================================\n";
                     print_r($topologyPlusLinks);
                     } 
                echo "=====================================================================================\n";
                echo "Berechnung Topology Status, dann Ausgabe:\n";
                }

            /* Konfiguration aus der Topologie in eine Struktur mit aktuellen Werten bringen 
             * Zusatzkonfigurationen, wie die Position und Groesse auf der Anzeige, jetzt übernehmen
             * INSTANCE wird ignoriert, es wird nur OBJECT ausgewertet
             * OBJECT wird 1:1 aus der vorigen Struktur übernommen
             * nur übernehmen wenn Baseline in der Path Struktur enthalten
             *
             * key => Type
             *        Configuration
             *
             * topologyStatus 
             *      y,x => Size
             *
             */

            $topologyStatus=array();
            foreach ($topologyPlusLinks as $key => $place)
                {
                if (isset($place["Configuration"])) $configuration = $place["Configuration"];
                elseif (isset($place["Config"])) $configuration = $place["Config"];
                else $configuration = $place;
                //print_R($configuration);
                if ( (isset($configuration["x"])) && (isset($configuration["y"])) ) 
                    {
                    $goodtogo=true;
                    if (isset($place["Path"])) 
                        {
                        if ($config["Baseline"])
                            {
                            $pos1 = strpos($place["Path"], $config["Baseline"]);
                            if ($pos1===false) $goodtogo=false;
                            }
                        }
                    if ($goodtogo)
                        {
                        if ( ($debug) && (isset($place["Path"])) ) echo str_pad($place["Path"],55);
                        $x=$configuration["x"]; $y=$configuration["y"];
                        if ( isset($configuration["l"]) ) $l=$configuration["l"]; else $l=1;
                        if ( isset($configuration["h"]) ) $h=$configuration["h"]; else $h=1;
                        $topologyStatus[$y][$x]["Size"]=["l"=>$l,"h"=>$h];
                        if (isset($place["Name"])) $name=$place["Name"]; 
                        else $name=$key;
                        if ($debug) echo "$name is located on $x und $y. Size is $l x $h.\n";           // name ist eigentlich der Index
                        if (isset($configuration["ShortName"])) $topologyStatus[$y][$x]["ShortName"]=$configuration["ShortName"];
                        if (isset($configuration["Background"])) $topologyStatus[$y][$x]["Background"]=$configuration["Background"];                    
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
                        }           // ende goodtogo
                    }
                }  // ende foreach


            ksort($topologyStatus);         // nach x Koordinaten
            if ($debug) 
                {
                echo "=====================================================================================\n";
                echo "Status Topologie für Ausgabe mit writeTable vorbereitet:\n";
                if ($debug>1) print_r($topologyStatus);
                }

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

        /* DEPRECATED, see DeviceManagementLib
         *
         * Verbindung der Topologie mit der Object und Instanzen Konfiguration
         * es können jetzt auch mehrstufige hierarchische Gewerke aufgebaut werden
         * zB Weather besteht aus Temperatur und Feuchtigkeit
         */

        function mergeTopologyObjectsSP($topology, $objectsConfig, $debug=false)
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
        * Übergeben wir $topologyStatus als zweidimensionales array [y][x]  y sind die Zeilen, x sind die Spalten, immer [1..n], 0 nicht
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
            /* Scale anwenden : Zeilen von 1..3 auf (0..2*scale)+1 anpassen , Spalten ebenfalls gleich anpassen
             * 
             */
            if ($debug) echo "Tabellengroesse mit $scale multiplizieren.\n";
            foreach ($topologyStatusInput as $y=>$line)
                {
                $yNew=($y-1)*$scale+1;            /*   1..1, 2..3, 3..5 usw */
                foreach ($line as $x => $status) 
                    {
                    $xNew=($x-1)*$scale+1;            /*   1..1, 2..3, 3..5 usw */
                    if ($debug) echo "  New Index :  $y x $x transformed to $yNew x $xNew :\n";
                    foreach ($status as $key=>$entry)
                        {
                        if ($debug>1) echo "    ".$key."\n";              // Status ist Size, ShortName etc. Size auch mit Scale barbeiten
                        switch ($key)
                            {
                            case "Size":
                                if ($debug>2) print_r($entry);
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

            /* Tabelle zeilenweise zeichnen von minx bis maxx und miny bis maxy
             *
             */
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
    
        /* showGraphWidget
         *
         * neuer Versuch der Darstellung in der html box mit SVG Objekten
         * diese sollen interactive sein, Achtung das Naming der id muss unique sein, sonst werden andere Routinen aufgerufen
         *
         * unique ID, start all ids here with graph-
         * on load, there is no good on load criterion to setup addEventListener, do it manual with two buttons, Edit and Save
         * initial events are based on jquery
         *
         * Tabelleneintrag für die Darstellung der Hierarchie mit aktuellen Werten
         * Bestandteil einer übergeordneten Tabelle für die Darstellung in der Startpage
         * verwendet Get Topology Liste aus EvaluateHardware_Configuration
         * drawTable zeichnet die Topologie mit rekursiven Aufrufen von drawCell als Hierarchie
         *
         * style kann false, full, iframe sein, oiframe verweist auf php files im webfront
         *
         **************************************/

		function showGraphWidget($style=false,$debug=false)
			{
            if ($debug) echo "showGraphWidget mit Debug aufgerufen.\n";

            IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
            IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
            IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
            IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");
            IPSUtils_Include ('EvaluateHardware_Configuration.inc.php', 'IPSLibrary::config::modules::EvaluateHardware');   

			/* get Category Visualization.EvaluateHardware.World for perfect structure of Topology */

			$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			$moduleManager = new IPSModuleManager('EvaluateHardware',$repository);
	        $WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
    		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');            
            $categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
    		$worldID=IPS_GetObjectIDByName("World",  $categoryId_WebFrontAdministrator);
            
            /* Get Topology Liste aus EvaluateHardware_Configuration */
            $DetectDeviceHandler = new DetectDeviceHandler();
            $topology=$DetectDeviceHandler->Get_Topology();
            //$eventConfiguration = $DetectDeviceHandler->Get_EventConfigurationAuto();        // IPSDetectDeviceHandler_GetEventConfiguration()
            //$topologyPlusLinks=$DetectDeviceHandler->mergeTopologyObjects($topology,$eventConfiguration,$debug);

            /* Javascript in der html Box, es kann nur das Internet javascript eingebunden werden, da sonst Zugriffsschwierigkeiten auf lokale Resourcen
             * update onclick id frame-status, id frame-browser, id frame-fullscreen
             */

            $wert  = "";
            $wert .= '<style>';
            $wert .= '.static { 
                        cursor: not-allowed;
                        }
                      .draggable {
                        cursor: move;
                        }';     
            $wert .= '</style>';            
            $wert .= '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>';
            $wert .= '<script type="text/javascript">';
            $wert .= '  document.addEventListener("DOMContentLoaded", function () {
                            alert("dom content loaded");
                            });   ';
            $wert .= '  var selectedElement = null;
                        var offset;
                        var onloadSvg = false;
                        function onloadSvgShowAlert() {
                            const svgElem = document.getElementById("svgObj");
                            graphLog(" onloadSvgShowAlert addEvent " + onloadSvg);
                            if (onloadSvg==false) {
                                onloadSvg=true;
                                svgElem.addEventListener("click", (evt) => { 
                                    if (evt.target.classList.contains("draggable")) {
                                        graphLog(" onloadSvgShowAlert click ");
                                        }
                                    else alert("click svg not draggable");
                                    });
                                svgElem.addEventListener("mousedown", (evt) => { 
                                    if (evt.target.classList.contains("draggable")) {
                                        startDrag(evt);
                                        graphLog(" onloadSvgShowAlert mousedown start drag");
                                        }
                                    });
                                svgElem.addEventListener("mousemove", (evt) => { 
                                    if (selectedElement) {
                                        evt.preventDefault();
                                        var coord = getMousePosition(evt);
                                        selectedElement.setAttributeNS(null, "x", coord.x - offset.x);
                                        selectedElement.setAttributeNS(null, "y", coord.y - offset.y);  
                                        graphLog(" onloadSvgShowAlert mousemove drag " + selectedElement + " " + Math.round(coord.x) + " / " + Math.round(coord.y));
                                        } 
                                    });
                                svgElem.addEventListener("mouseup", (evt) => { 
                                    selectedElement = null;
                                    graphLog(" onloadSvgShowAlert mouseup end drag ");
                                    });
                                svgElem.addEventListener("mouseleave", (evt) => { 
                                    selectedElement = null;
                                    graphLog(" onloadSvgShowAlert mouseleave end drag ");                                
                                    });
                                }
                            } ';
             $wert .= '  function getMousePosition(evt) {
                            const svgElem = document.getElementById("svgObj");
                            var CTM = svgElem.getScreenCTM();
                            return {
                                x: (evt.clientX - CTM.e) / CTM.a,
                                y: (evt.clientY - CTM.f) / CTM.d
                                };
                            }
                        function startDrag(evt) {
                            if (evt.target.classList.contains("draggable")) {
                                selectedElement = evt.target;
                                offset = getMousePosition(evt);
                                offset.x -= parseFloat(selectedElement.getAttributeNS(null, "x"));
                                offset.y -= parseFloat(selectedElement.getAttributeNS(null, "y"));
                                } 
                            }                                                    
                        ';  
              $wert .= 'var logCount = 0;
                         function graphLog(text) {
                            const event = new Date();
                            logCount++;
                            if (logCount>20) {
                                logCount=0;
                                document.getElementById("graph-log").innerHTML = "";
                                }
                            document.getElementById("graph-log").innerHTML += event.toLocaleTimeString("it-IT") + text + "<br>";
                            }
                          ';  
        /*    $wert .= '  window.onload = onloadSvgShowAlert; 
                          function makeDraggable(evt) {
                            var svg = evt.target;
                            svg.addEventListener("mousedown", startDrag);
                            svg.addEventListener("mousemove", drag);
                            svg.addEventListener("mouseup", endDrag);
                            svg.addEventListener("mouseleave", endDrag);
                            var selectedElement = false;
                            alert("show svg");

                            function startDrag(evt) {
                                if (evt.target.classList.contains("draggable")) {
                                    selectedElement = evt.target;
                                    }                                
                                }
                            function drag(evt) {
                                if (selectedElement) {
                                    evt.preventDefault();
                                    var x = parseFloat(selectedElement.getAttributeNS(null, "x"));
                                    selectedElement.setAttributeNS(null, "x", x + 0.1);
                                    }                                
                                }
                            function endDrag(evt) {
                                selectedElement = null;
                                }
                            }';    */
            $wert .= '  function reportWindowSizeGraph () {
                            let varheight=Math.round(window.innerHeight * 0.85);
                            const event = new Date();
                            document.getElementById("graph-status").innerHTML = "Size " + window.innerHeight + " (" + varheight + ") x " + window.innerWidth + "  " + Date();
                            graphLog(" show windows size ");
                            };
                        $("#graph-status").on("click", function() {
                            reportWindowSizeGraph ();
                            });
                        $("#graph-circle").on("click", function() {
                            reportWindowSizeGraph ();
                            onloadSvgShowAlert ();
                            });                            

                        window.onresize = reportWindowSizeGraph;                     
                    '; 
            // report browser version  
            $wert .= '  function reportBrowserVersionGraph() {
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
                            Window.alert("Report Browser Version"); 
                            }                 
                        $("#graph-browser").on("click", function() {
                            document.getElementById("graph-browser").innerHTML = reportBrowserVersionGraph ();
                            });  
                    ';
            // switch fullscreen and show status draggable
            $wert .= '  var fullScreen=0;
                        function toggleFullScreen(elem) {
                            if (fullScreen==0) { elem.requestFullscreen(); fullScreen=1; return ("Full Screen"); }
                            else { document.exitFullscreen(); fullScreen=0; return ("Standard Screen"); } 
                            }    
                        $("#graph-fullscreen").on("click", function() {
                            document.getElementById("graph-fullscreen").innerHTML =  toggleFullScreen(document.documentElement);
                            });  
                        $(".draggable").on("click", function() {
                            document.getElementById("graph-browser").innerHTML = "draggable";
                            });  
                    ';                        
            $wert .= '';
            $wert .= '</script>';
            if ($style=="full")
                {
                $wert .= '<div class=“resp-table-row” style="display: table-row;">';                // display type  Row
                $wert .= '  <div class=“table-body-cell” style="width: 70%; display: table-cell;">';                 // display type Cell
                }
            else $wert .= '<div>';

            $wert .= '        <svg id="svgObj" viewBox="0 0 200 100" xmlns="http://www.w3.org/2000/svg">';
     /*     $wert .= '<script type="text/javascript">';            
            $wert .= '  const svgElm = document.getElementById("svgObj");
                            svgElm.addEventListener("load", () => { 
                            alert("load svg");
                            });';
            /*$wert .= '<![CDATA[';
            $wert .= '  function makeDraggable(evt) {
                            var svg = evt.target;
                            svg.addEventListener("mousedown", startDrag);
                            svg.addEventListener("mousemove", drag);
                            svg.addEventListener("mouseup", endDrag);
                            var selectedElement = null;

                            function startDrag(evt) {
                                if (evt.target.classList.contains("draggable")) {
                                    selectedElement = evt.target;
                                    }                                
                                }
                            function drag(evt) {
                                if (selectedElement) {
                                    evt.preventDefault();
                                    var x = parseFloat(selectedElement.getAttributeNS(null, "x"));
                                    selectedElement.setAttributeNS(null, "x", x + 1);
                                    }                                
                                }
                            function endDrag(evt) {
                                selectedElement = null;
                                }
                            } ';
            //$wert .= '    ]]>'; 
            $wert .= '</script>';   */
            $wert .= '        <rect x="0" y="0" width="200" height="100" fill="#fafafa"/>';
            $wert .= '        <rect id="graph-obj-001" class="draggable" x="40" y="50" width="8" height="10" fill="#007bff"/>';
            $wert .= '        <rect id="graph-obj-002" class="draggable" x="80" y="50" width="8" height="10" fill="#227bff"/>';
            $wert .= '        <rect id="graph-obj-003" class="draggable" x="120" y="50" width="8" height="10" fill="#227bff"/>';
            $wert .= '        <rect class="static" x="58" y="50" width="8" height="10"   fill="#888"/>';
            $wert .= '        <circle id="graph-circle" class="draggable" cx="10" cy="10" r="8" stroke="green" stroke-width="2" fill="yellow" />';
            $wert .= '      </svg>';
            $wert .= '   </div>';
            if ($style=="full")
                {
                $wert .= '      <div class=“table-body-cell” style="width: 30%; display: table-cell;">';                 // display type Cell
                $wert .= '         <div id="graph-log" style="font-size: 1hm; display:inline; float:left;"><br>Log evolves here<br></div>';        
                $wert .= '   </div>';
                $wert .= ' </div>';
                }
            $wert .= '<div id="graph-status" style="font-size: 1hm; display:inline; float:left;">Statusangaben hier clicken</div>';        
            $wert .= '<div id="graph-browser" style="font-size: 1hm; display:inline; padding: 5px;">Browser hier clicken</div>';        
            $wert .= '<div id="graph-fullscreen" style="font-size: 1hm; display:inline; float:right;">Fullscreen hier clicken</div>';

            return ($wert);
            }


        /* showHierarchy
         *
         * Tabelleneintrag für die Darstellung der Hierarchie mit aktuellen Werten
         * Bestandteil einer übergeordneten Tabelle für die Darstellung in der Startpage
         * verwendet Get Topology Liste aus EvaluateHardware_Configuration
         * drawTable zeichnet die Topologie mit rekursiven Aufrufen von drawCell als Hierarchie
         *
         *
         **************************************/

		function showHierarchy($debug=false)
			{
            if ($debug) echo "showHierarchy mit Debug aufgerufen.\n";
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
            //$eventConfiguration = $DetectDeviceHandler->Get_EventConfigurationAuto();        // IPSDetectDeviceHandler_GetEventConfiguration()
            //$topologyPlusLinks=$DetectDeviceHandler->mergeTopologyObjects($topology,$eventConfiguration,$debug);

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

            $wert .= $this->drawTable($worldID, $config, $debug);            

            return ($wert);
            }

        /* 
         * Tabelle mit Topologie zeichnen. Einstiegsroutine für rekursiven Aufruf von drawCell
         */

        function drawTable($oid,&$config, $debug=false)
            {
            $html = "";
            $level=0;

            $this->drawCell($oid,$html,$level,$config, $debug);

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

        function drawCell($oid,&$html,$level,&$config, $debug=false)
            {
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
                    echo "drawTable: Aufruf mit $oid (".IPS_GetName($oid).") $level:$totalcount --> ".json_encode($count)."   \n";
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
         * Zelle Tabelleneintrag für die Darstellung eines Mediaobjektes
         * Breite und Höhe für iFrame vorgeben, wird jetzt Teil der Konfig
         * Showfiile ist jetzt der Parameter welcher Frame angezeigt werden soll
         *
         **************************************/

		function showMediaWidget(&$showfile=false, $debug=false)
			{
            $wert="";
            if (isset($this->configuration["Display"]["MediaObject"]))
                {
                $config=$this->configuration["Display"]["MediaObject"];
                $max=sizeof($config);
                if ($debug) echo "ShowMediaWidget $showfile/$max Debug $debug Config ".json_encode($config)."\n";
                if ($showfile>=$max) $showfile=0; 

                $wert .='<iframe src="'.$config[$showfile]["html"].'" style="width:900px; height:800px; </iframe>';

                $showfile++;
                if ($showfile>=$max) $showfile=0;                    
                }
            else
                {
                $wert .='<iframe src="https://oe3.orf.at/player" width="900" height="800"
                        <p>Ihr Browser kann leider keine eingebetteten Frames anzeigen:
                            Sie können die eingebettete Seite über den folgenden Verweis aufrufen: 
                            <a href="https://wiki.selfhtml.org/wiki/Startseite">SELFHTML</a>
                        </p></iframe>';
                }
            return ($wert);
            }

        /********************
         * showPictureWidget
         * Zelle Tabelleneintrag für die Darstellung eines BestOf Bildes
         * class container = width: auto; height: auto; max-height:95%; max-width: 100%
         *
         **************************************/

		function showPictureWidget($showfile=false, $debug=false)
			{
            if ($debug) echo "showPictureWidget mit Parameter ShowFile $showfile aufgerufen.\n";
            $wert="";
            $verzeichnisWeb = "/user/Startpage/user/pictures/SmallPics/";
            $file=$this->readPicturedir();
            $maxcount=count($file);
            if ($maxcount==0) 
                {
                $wert.= '<div style="width:100%; background-color:#202024;">';
                $bilderverzeichnis = $this->configuration["Directories"]["Pictures"];
                $fileInput=$this->readPicturedir($bilderverzeichnis);
                $maxcountInput=count($fileInput);
                $wert.= '<p>SynologyDrive Source '.$bilderverzeichnis.' with '.$maxcountInput.' Files</p>';   // definiert die Größe des Bildes
                $wert.= '<p>Image Source '.$verzeichnisWeb.' empty</p>';   // definiert die Größe des Bildes
                $wert.= '</div>';
                }
            else
                {
                if ($showfile===false) $showfile=rand(1,$maxcount-1);
                $filename = $file[$showfile];
                if ($this->newStyle) $verzeichnis    = $this->dosOps->correctDirName(IPS_GetKernelDir()."/webfront".$verzeichnisWeb, $debug);
                else $verzeichnis    = $this->dosOps->correctDirName(IPS_GetKernelDir().$verzeichnisWeb, $debug);
                if ($debug) echo "showPictureWidget, Kernel Dir ".IPS_GetKernelDir()." Filename Dir $verzeichnis ,Filename $filename untersuchen.\n";
                if (file_exists($verzeichnis.$filename)) 
                    {
                    $filegroesse=number_format((filesize($verzeichnis.$filename)/1024/1024),2);
                    $info=getimagesize($verzeichnis.$filename);     // Index 3 ist eine Zeichenkette mit dem Attributen Breite und Höhe in der Form height="yyy" width="xxx" zur Verwendung in einem IMG-Tag.
                    if ($debug) echo "Filename vorhanden - Groesse ".$filegroesse." MB.\n";
                    }
                //echo "NOWEATHER false. PageType 1. Picture. ".$filename."\n\n";   
                //$wert.= '<div class="container" style="background:darkgrey;"'.$info[3].'>';
                //$wert.= '<div class="container" style="width:auto; background-color:#202024;">';
                $wert.= '<div style="width:100%; background-color:#202024;">';
                //$wert.=     '<img src="'.$verzeichnisWeb.$filename.'" alt="'.$filename.'" class="image" '.$info[3].'>';   // definiert die Größe des Bildes
                $wert.=     '<img src="'.$verzeichnisWeb.$filename.'" alt="'.$filename.'" style="width:100%; max-height:740px;">';   // definiert die Größe des Bildes
                //$wert.=     '<div class="middle">';                                                                                               // Position unklar
                //$wert.=         '<div class="text">'.$filename.'<br>'.$filegroesse.' MB '.$info[3].'</div>';
                //$wert.=         '</div>';
                $wert.=     '</div>';
                }
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
            if (isset($weather["todayDate"]))
                { 
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

        /********************
         *
         * Zelle Tabelleneintrag für die Tabelle für Darstellung der Regenmange
         *
         *
         **************************************/

		function showRainmeterWidget($configInput=false,$debug=false)
            {
            //$debug=true;
            $startexec=microtime(true); 
            $wert="";
            if ($configInput===false) $rainConfiguration = $this->getConfigRainmeterWidget(false,$debug);
            else $rainConfiguration=$this->getConfigRainmeterWidget($configInput,$debug);                               // Config wird im getConfig behandelt, aber dann nicht zurückgegeben
            if ($debug) echo "showHRainmeterWidget ".json_encode($rainConfiguration)." \n";

            $wert .= '<table>';
            $wert .= '<tr>'; 
            $wert .= '<td><table>';

            foreach ($rainConfiguration as $index => $rainConf)
                {
                switch (strtoupper($rainConf["Type"]))
                    {
                    case "RAINREG":
                        $oid=$rainConf["OID"];
                        $archiveID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
                        $variableChanged=IPS_GetVariable($oid)["VariableChanged"];
                        $timeGone=time()-$variableChanged;
                        $startOfToday=mktime(0,0,0,date("m"), date("d"), date("Y"));
                        if ($timeGone<(time()-$startOfToday))                   $addInfo=date("H:i",$variableChanged);
                        elseif ($timeGone<(time()-($startOfToday-7*24*60*60)))  $addInfo=date("D H:i",$variableChanged);
                        elseif ($timeGone<(time()-($startOfToday-60*24*60*60))) $addInfo=date("d.m H:i",$variableChanged); 
                        else                                                    $addInfo=date("d.m.Y H:i",$variableChanged);
                        $wert .= '<tr>';
                        $wert .= '<td>'.GetValueIfFormatted($oid).'</td>';
                        $wert .= '<td>'.$addInfo.'</td>';
                        $wert .= '</tr>';
                                    if ($debug) 
                                        {
                                        echo "      ".date("d.m.Y H:i:s",$startOfToday)."\n";
                                        //echo "      Variable Changed $addInfo, got from Property:".$tableEntry["Property"]."   ".json_encode(IPS_GetVariable($oid))."\n";
                                        $endtime=time();
                                        $starttime=$endtime-$timeGone-(10*60*60);           // 10 Tage nach dem letzten RegentempTableLine
                                        $werteLog  = @AC_GetLoggedValues($archiveID,$oid,$starttime,$endtime,0);          
                                        // AC_GetAggregatedValues (integer $InstanzID, integer $VariablenID, integer $Aggregationsstufe, integer $Startzeit, integer $Endzeit, integer $Limit)    
                                        $werteAgg  = @AC_GetAggregatedValues($archiveID,$oid,0,$starttime,$endtime,0);                   
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
                                            //print_r($werteAgg);

                                            foreach ($werteAgg as $eintrag)
                                                {
                                                if ($eintrag["Avg"] != $eintrag["Min"]) echo "             ".date("d.m.Y H:i",$eintrag["TimeStamp"])."    ".$eintrag["Avg"]."\n";
                                                }                                            
                                            //$value = (float)$sum/$count;
                                            //if ($debug) echo "     Integrate Values from last ".$config["Display"]["BottomLine"]["Integrate"]." seconds. Results into Value ".number_format($value,0,",",".")."\n";
                                            //$oid=$value;            // formatEntry erkennt oid (wenn integer) und oid als value
                                            }
                                        }

                        /*foreach ($heatingConf["Values"] as $room=>$values)
                            {
                            $wert .= '<tr>';
                            $wert .= '<td>'.$room.'</td>';
                            foreach ($values as $value) $wert .= '<td>'.GetValueIfFormatted($value).'</td>';
                            $wert .= '</tr>';
                            }*/ 
                        $wert .= '<td>'.json_encode($rainConf).'</td><td>'.'</td></tr>';
                        $wert .= '</table></td>';   
                        $wert .= '</tr>';
                        break;
                    case "RAINEVENT":
                        if (isset($this->installedModules["Gartensteuerung"])) 
                            {
                            if ($debug) echo "Modul Gartensteuerung verfügbar. Routinen aus der Library verwenden.\n";
                            IPSUtils_Include ('Gartensteuerung_Library.class.ips.php', 'IPSLibrary::app::modules::Gartensteuerung');                
                            $gartensteuerung = new Gartensteuerung(0,0,$debug);   // default, default, debug=false
                            /* listRainEvents liefert die regenereignisse als Array , writeRainEventsHtml schreibt diese als table */
                            $wert .= '<tr><td>'.$rainConf["Name"].'</td></tr>'; 
                            $wert .= '<tr><td>';                
                            if (strtoupper($rainConf["OID"])=="DEFAULT") $wert .= $gartensteuerung->writeRainEventsHtml($gartensteuerung->listRainEvents($rainConf["Count"]));          // ist in einen table eingebettet
                            else                                         $wert .= $gartensteuerung->writeRainEventsHtml($gartensteuerung->listRainEvents($rainConf["Count"],$rainConf["OID"]));          // ist in einen table eingebettet
                            $wert .= '</td>';   
                            $wert .= '</tr>';
                            }
                        else echo "Modul Gartensteuerung NICHT verfügbar. Routinen aus der Library können nicht verwendet werden.\n";
                        break;
                    default:
                        echo "Do not know !\n";
                        break;
                    }
                }

            $wert .= '</table>'; 
            if ($debug) echo "showRainmeterWidget Laufzeit ".(time()-$startexec)." Sekunden.\n";

            return ($wert);                                
            }

        /********************
         *
         * Zelle Tabelleneintrag für die Tabelle für Darstellung der Ergebnisse von Easychart 
         * Config aus dem Configfile wird als erster Eintrag übergeben.
         * Eine Tabelle ist einer Chartskonfiguration zugeordnet, auf diese wird gepointert
         *
         *
         **************************************/

		function showChartsWidget($configInput=false,$debug=false)
            {
            //$debug=true;
            $startexec=microtime(true); 
            $wert="";
            if ($configInput===false) $chartsConfiguration = $this->getConfigChartsWidget(false,$debug);
            else $chartsConfiguration=$this->getConfigChartsWidget($configInput,$debug);                               // Config wird im getConfig behandelt, aber dann nicht zurückgegeben
            if ($debug) echo "showChartsWidget ".json_encode($chartsConfiguration)." \n";

            $wert .= '<table>';
            $wert .= '<tr>'; 
            $wert .= '<td><table>';

            foreach ($chartsConfiguration as $index => $chartsConf)
                {
                IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");					// Library verwendet Configuration, danach includen                    
                IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");  
                $seleniumEasycharts = new SeleniumEasycharts();
                $archiveOps = new archiveOps();  

                $wert .= '<tr><td>'.$chartsConf["Name"].'</td></tr>'; 
                $wert .= '<tr><td>';                

                if ($chartsConf["OID"] != "default") $shares = $seleniumEasycharts->getResultConfiguration($chartsConf["OID"]);
                else $shares = $seleniumEasycharts->getResultConfiguration($chartsConf["Name"]);
                $resultShares=array();
                foreach($shares as $index => $share)                    //haben immer noch eine gute Reihenfolge, wird auch in resultShares übernommen
                    {
                    $oid = $share["OID"];
                    //echo "Infos ".$share["ID"]." :     ".$archiveOps->getStatus($oid)."   \n";
                    //$checkArchive=$archiveOps->getComponentValues($oid,10,false);                 // true mit Debug, nicht unbedingt notwendig
                    //echo $checkArchive;
                    $result = $archiveOps->analyseValues($oid,10,false);                 // true mit Debug
                    //print_r($result); 
                    $resultShares[$share["ID"]]=$result;
                    $resultShares[$share["ID"]]["Info"]=$share;
                    $archiveOps->addInfoValues($oid,$share);
                    } 
                //if ($debug) print_r($resultShares);  

                $wert .= $seleniumEasycharts->writeResultAnalysed($resultShares,true,-6,$debug);          // true für Ausgabe als html Tabelle, Size = -6 (not more than 6 lines) and true für Debug
                $wert .= '</td>';   
                $wert .= '</tr>';
                }
            $wert .= '</table>'; 
            if ($debug) echo "showChartsWidget Laufzeit ".(time()-$startexec)." Sekunden.\n";

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

        /* read configuration for showRainmeterWidget, eliminate unknown indizes */

        function getConfigRainmeterWidget($groupsInput=false,$debug=false)
            { 
            if ($groupsInput===false)
                {
                if (isset($this->configuration["Rainmeter"]) ) $rainConf=$this->configuration["Rainmeter"];
                else $rainConf=array();
                }
            else $rainConf = $groupsInput;                

            if ($debug) echo "getConfigRainmeterWidget Configuration analysieren: ".json_encode($rainConf)."\n";
            configfileParser($rainConf, $config, ["Config","Configuration","config","CONFIGURATION"],"Config",null);         //wenn config fehlt nur Values
            $rainConf=$config["Config"]; $config=array();
            configfileParser($rainConf, $config, ["OID","Oid","oid"],"OID",false);                 //configfile als tableEntry vereinheitlichen, überprüfen, Wert für OID muss vorhanden sein und das Objekt erreichbar
            if ($config["OID"]!==false) $rainconf["Regen"]=$rainConf;
            $config=array();
            foreach ($rainConf as $index => $regsConf)
                {
                configfileParser($rainConf[$index], $config[$index], ["OID","Oid","oid"],"OID","default");           // input ist $specialRegsConf, bereinigt dann in $specialRegs
                configfileParser($rainConf[$index], $config[$index], ["Name","NAME","name"]     ,"Name",$index);        
                configfileParser($rainConf[$index], $config[$index], ["Unit","UNIT","unit"]     ,"Unit","mm");        
                configfileParser($rainConf[$index], $config[$index], ["Icon","ICON","icon"]     ,"Icon","Rainfall");        
                configfileParser($rainConf[$index], $config[$index], ["Type","TYPE","type"]     ,"Type","Rainevent"); 
                configfileParser($rainConf[$index], $config[$index], ["Count","COUNT","count"]  ,"Count",3); 
                }
            if ($debug) { echo "RainmeterWidget Config ausgewertet:\n"; print_R($config); }
            return($config);
            }

        /* read configuration for showChartsWidget, eliminate unknown indizes 
         *
         */

        function getConfigChartsWidget($groupsInput=false,$debug=false)
            { 
            //$debug=true;
            //print_r($this->configuration);
            if ($groupsInput===false)
                {
                if ($debug) 
                    {
                    echo "getConfigChartsWidget, no input for Configuration File. Use Default Input for Startpage Config:\n";
                    print_r($this->configuration);
                    }
                if (isset($this->configuration["Easycharts"]) ) $chartsConf=$this->configuration["Easycharts"];
                elseif (isset($this->configuration["Widgets"]) )
                    {
                    echo "Keine Defaultkonfiguration, in Widgets suchen\n";
                    foreach ($this->configuration["Widgets"] as $pageIndex => $page)
                        {
                        foreach ($page as $rowIndex => $row)    
                            {
                            foreach ($row as $colIndex => $entry)    
                                {
                                //echo $entry["Type"]."\n";
                                if (strtoupper($entry["Type"])=="CHARTS")
                                    {
                                    //print_r($entry);
                                    $chartsConf=$entry;    
                                    }
                                }
                            }
                        }
                    }
                else $chartsConf=array();
                }
            else $chartsConf = $groupsInput;                

            if ($debug) echo "getConfigChartsWidget Configuration analysieren: ".json_encode($chartsConf)."\n";
            configfileParser($chartsConf, $config, ["Config","Configuration","config","CONFIGURATION"],"Config",null);         //wenn config fehlt nur Values
            if (isset($config["Config"])) $chartsConf=$config["Config"]; 
            else $chartsConf=array();
            $config=array();
            configfileParser($chartsConf, $config, ["OID","Oid","oid"],"OID",false);                 //configfile als tableEntry vereinheitlichen, überprüfen, Wert für OID muss vorhanden sein und das Objekt erreichbar
            $config=array();
            foreach ($chartsConf as $index => $regsConf)
                {
                configfileParser($chartsConf[$index], $config[$index], ["OID","Oid","oid"],"OID","default");           // input ist $specialRegsConf, bereinigt dann in $specialRegs
                configfileParser($chartsConf[$index], $config[$index], ["Name","NAME","name"]     ,"Name",$index);        
                //configfileParser($chartsConf[$index], $config[$index], ["Unit","UNIT","unit"]     ,"Unit","mm");        
                //configfileParser($chartsConf[$index], $config[$index], ["Icon","ICON","icon"]     ,"Icon","Rainfall");        
                //configfileParser($chartsConf[$index], $config[$index], ["Type","TYPE","type"]     ,"Type","Rainevent"); 
                //configfileParser($chartsConf[$index], $config[$index], ["Count","COUNT","count"]  ,"Count",3); 
                }
            if ($debug) { echo "ChartsWidget Config ausgewertet:\n"; print_R($config); }
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
         * Register werden mit Highcharts angezeigt
         *
         **************************************/

		function showSpecialRegsWidget($configInput=false,$debug=false)
            {
            $wert = "";
            $wert .= "<table><tr>";
            if ($configInput===false) $specialRegsConf = $this->getConfigSpecialRegsWidget(false,$debug);
            else $specialRegsConf=$this->getConfigSpecialRegsWidget($configInput["Config"],$debug);

            if ($debug) { echo "Special Regs Aufruf mit:\n"; print_R($specialRegsConf); }
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


        /* read configuration for showTempGroupWidget, eliminate unknown indizes 
         * übernimmt die configuration aus dem einzelnen Widget, es gibt aber auch die Möglichkeit ohne Widget Default Configs anzugeben
         *
         */

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
                if ( ($specialRegs[$index]["OID"] != null) )
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
                else 
                    {
                    echo "getConfigSpecialRegsWidget, Index $index: OID nicht richtig angegeben. OID Eintrag fehlt.\n";
                    var_dump($specialRegs[$index]);
                    }
                }
            if ($debug) print_r($specialRegs);
            return ($specialRegs);
            }



        /********************
         *
         * Zelle Tabelleneintrag für die Tabelle für Innen und Aussentemperatur
         * zeigt die beiden schönen Icons für Innen und Aussenthermometer an
         * macht zumindest 2 Zeilen mit jeweils 2 Zellen
         * ausgelegt als Aufruf als Widget
         *
         * es gibt auch eine Konfiguration, getTempTableConf zum Vorverarbeiten verwendet
         * Aufruf von StartpageWrite als Grossbild übergibt keine Konfiguration
         *
         **************************************/

		function showTemperatureTable($colspan="",$config=false,$debug=false)
            {
            if ($config===false) $tempTableConf = $this->getTempTableConf(false,$debug);
            else $tempTableConf=$this->getTempTableConf($config["Config"],$debug);

            if ($debug) 
                {
                echo "showTemperatureTable aufgerufen Config: ".json_encode($tempTableConf)."  \n";
                if ($config) "    Konfiguration wurde als [Config][Index] übergeben.\n";   
                }

            $wert="";
            $wert.='<tr><td '.$colspan.'bgcolor="#c1c1c1"> <img src="/user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>';
            $wert.='<td bgcolor="#ffffff"><img src="/user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur"></td></tr>';

            if ( ($tempTableConf===null) || (count($tempTableConf)==0) )            // schon wieder Defaultwerte
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
            else                        // entsprechend der Konfiguration
                {    
                //print_R($tempTableConf);
                foreach ($tempTableConf as $entry) 
                    {
                    //if ($debug) print_r($entry);
                    $aussen="unknown";$innen="unknown";
                    $aussenWert="unknown";
                    $unitAussen=$entry["Unit"]; $unitInnen=$unitAussen;
                    if (isset($entry["Aussen"]["FUNCTION"]))    $aussenWert = round($entry["Aussen"]["FUNCTION"](),1);
                    if (isset($entry["Aussen"]["OID"]))         $aussenWert = round(GetValue($entry["Aussen"]["OID"]),1);
                    if (isset($entry["Aussen"]["ARRAY"]))       { $aussenWert = $entry["Aussen"]["ARRAY"]["Value"]; $unitAussen=""; }
                    if (isset($entry["Innen"]["FUNCTION"]))     $innenWert  = round($entry["Innen"]["FUNCTION"](),1);
                    if (isset($entry["Innen"]["OID"]))          $innenWert  = round(GetValue($entry["Innen"]["OID"]),1);
                    if ($debug) echo " Innenwert: $innenWert und Aussenwert: $aussenWert wurden ermittelt.\n";
                    $size=strtoupper($entry["Size"]);
                    if ($size == "LARGE") { $aussen="aussen"; $innen="innen"; }
                    elseif ($size == "MED")  { $aussen="aussenMed"; $innen="innenMed"; }
                    else { $aussen="aussenSmall"; $innen="innenSmall"; }
                    $wert.='<tr><td '.$colspan.' bgcolor="#c1c1c1"><'.$aussen.'>'.$aussenWert.$unitAussen.'</'.$aussen.'></td><td align="center"> <'.$innen.'>'.$innenWert.$unitInnen.'</'.$innen.'> </td></tr>';
                    }
                }
            return ($wert);
            }

        /* read configuration for showTemperatureTable und showTemperatureTableValues wenn responsive
         * adapt configuration für interne Verarbeitung:
         *      IndexNum    Konfiguration von Key ist Name auf laufende Nummer ändern, wichtig für korrekte Reihenfolge
         *      Name        Key
         *      Innen       analyseEntry
         *      Aussen      analyseEntry
         *      Unit
         *      Size
         *
         * analyseEntry   schaut ob array(->ARRAY) ,numerischer Wert (->OID), string und eine gültiger functionname (->FUNCTION)
         * wenn array, evaluateEntry liefert VALUE, dazu wird displayValue(&wert) verwendet
         *
         * Aufruf von StartpageWrite als Grossbild übergibt keine Konfiguration, interne configuration["Temperature"] nehmen
         * 
         * für dieses Grossbild Widget gilt immer es gibt immer Innen und Aussen Werte
         *
         *
         *
         */

        function getTempTableConf($groupsInput=false,$altText=false,$debug=false)
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
                $config[$indexNum]["Innen"]  = $this->analyseEntry($config[$indexNum]["Innen"],"Innen",$debug);
                $config[$indexNum]["Aussen"] = $this->analyseEntry($config[$indexNum]["Aussen"],"Aussen",$debug);
                $indexNum++; 
                }
            if ($debug) print_r($config);
            return ($config);
            }

        /* jeder Innen oder Aussenwert kann auf unterschiedliche Weise dargestellt werden 
         *      Zahl        OID, Wert wird überprüft
         *      Funktion    FUNCTION, Wert wird überprüft
         * 
         */ 

        function analyseEntry($value,$altText=false, $debug)
            {
            $analyze=array();
            if (is_array($value))
                {
                $analyze["ARRAY"]=$this->evaluateEntry($value,$altText,$debug);
                if ($debug)
                    {
                    echo "analyseEntry ".json_encode($value)."\n";
                    //print_r($analyze);
                    }
                }  
            elseif (is_numeric($value)) 
                {
                if (IPS_ObjectExists ($value)) $analyze["OID"]=$value;
                //echo "Wert ist Numerisch : \n";
                }
            elseif (function_exists($value)) $analyze["FUNCTION"]=$value;
            else echo "Nicht bekannter Wert, vielleicht IPSHeat ?\n";
            return ($analyze); 
            }

        /* evaluateEntry, Darstellung von einem oder mehreren Werten in einer Tabelle/Zelle, zb Bottom Line mit displayValue
         *
         * Es wird immer nur ein Eintrag bearbeitet, OID kann ein Wert oder ein Array aus Werten sein
         * für jede oid prüfen ob das Objekt erreichbar ist, sonst aus der Tabelle von oidArray rausnehmen, es muss zumindest einen gültigen Wert geben
         *
         * Idee ist das Werte mit dem Parameter Aggregate ähnlich bearbeitet werden 
         * und das die Bearbeitung gleich mit der Berechnung des Wertes abschliesst
         *
         * Grundbedingung, mindestens enthaltene Keys
         *      OID     wird als oid bzw oidArray weiter bearbeitet
         *
         * OID wird evaluiert, es wird daraus wieder eine config ausgegeben
         * es gibt noch
         *      Name
         *      Icon
         *      Unit
         * Zusatzfunktionen
         *      Aggregate       MEANS,MAX,MIN aus mehreren Werte in einem Array
         *      Property        nette Darstellung wann letzte Änderung erfolgt ist
         *      Type
         *      Integrate       aus einem Wert mit archivierten Werten, kein Aggregate konfiguriert
         *      Show            Anzeigen wenn Wertebereich passt
         *
         * die Zusatzfunktionen vorbereiten und weiter an displayValue
         *
         */

        function evaluateEntry($tableEntry, $altText=false, $debug=false)
            {
            if ($debug) echo "      evaluateEntry aufgerufen für ".json_encode($tableEntry).".\n";
            /* check ob eine oder mehrere OIDs angegeben wurden */
            $typeOfObject="STANDARD";
            $wert="";
            $config=array();        // immer neu anfangen
            configfileParser($tableEntry, $config, ["OID","Oid"],"OID",false);                 //configfile als tableEntry vereinheitlichen, überprüfen, Wert für OID muss vorhanden sein und das Objekt erreichbar
            if (isset($config["OID"])) $oid=$config["OID"];
            if ($oid === false) 
                {
                if ($debug) echo "Konfiguration enthält keinen Key mit Namen OID, ".json_encode($config)."\n";
                $config["OID"] = $tableEntry;           // ShortCut, Workaround, alles andere bleibt im Default 
                $oid=$config["OID"];
                }
            if ($oid !== false)         // kein Defaultwert
                {
                if (is_array($oid)) $oidArray=$oid;                     // auch Arrays zulassen, dann sollten zwei Werte nebeneinander stehen
                else $oidArray=[$oid];
                /* check ob die Objekte vorhanden sind */
                $objectExists=false;
                $entries=count($oidArray);
                foreach ($oidArray as $key => $oid) 
                    {
                    if (IPS_ObjectExists($oid)) $objectExists=true;         // wenn zumindest eines der Objekte existiert weitermachen
                    else unset($oidArray[$key]);
                    }
                $entries2=count($oidArray);
                if ($debug) echo "--------evaluateEntry ($entries2 valid, out of $entries) \n";               // was blieb über nach dem check      
                // immer so tun als ob mehrere Werte zusammengefasst werden, selbe Routine
                if ($objectExists)          // es wird zumindest einen Eintrag geben 
                    {
                    // für jeden ConfigEintrag tableEntry die Config überprüfen und in $config abspeichern und diese Config verwenden
                    configfileParser($tableEntry, $config, ["Name","NAME"],"Name",IPS_GetName($oid)); 
                    configfileParser($tableEntry, $config, ["Icon","ICON"],"Icon","IPS");
                    configfileParser($tableEntry, $config, ["Integrate","INTEGRATE","integrate"],"Integrate",false);
                    configfileParser($tableEntry, $config, ["Property","PROPERTY","property"],"Property",null);            // default kein Eintrag wenn kein Eintrag
                    configfileParser($tableEntry, $config, ["Type","TYPE","type"],"Type",null);                             // default kein Eintrag wenn kein Eintrag
                    configfileParser($tableEntry, $config, ["AGGREGATE","Aggregate","aggregate"],"Aggregate",false); 
                    configfileParser($tableEntry, $config, ["PROFILE","Profile","profile"],"Profile",null); 
                    configfileParser($tableEntry, $config, ["UNIT","Unit","unit"],"Unit",""); 
                    configfileParser($tableEntry, $config, ["SHOW","Show","show"],"Show",null);             // Default ist kein Eintrag
                    if ($debug) // die Werte der OIDs ausgeben, einer oder mehrere
                        {
                        $i=1;
                        if (count($oidArray)==1) echo "   Eintrag : Name ".$config["Name"]." OID $oid Icon ".$config["Icon"]." Value ".GetValue($oid)."\n";
                        else foreach ($oidArray as $oid) echo "   ".$i++.":Eintrag : Name ".$config["Name"]." OID $oid Icon ".$config["Icon"]." Value ".GetValue($oid)."\n";
                        }

                    if ($altText) $config["altText"]=$altText;
                    $init=true; $addInfo="";                    // zuerst Aggregate bearbeiten wenn angefordert wurde
                    $result=true;                               // result wird false wenn die Darstellung nicht erfolgreich war
                    if ($config["Aggregate"] !== false)
                        {
                        // oidarray vor der Darstellung gemäß Type aggregieren
                        if ($debug) echo "   Aggregate ".$config["Aggregate"]." gefunden \n";
                        switch (strtoupper($config["Aggregate"]))
                            {
                            case "MEANS":
                                $sum=0; $sumCount=0;
                                foreach ($oidArray as $oid) 
                                    {
                                    $sum += GetValue($oid);
                                    $sumCount++;
                                    }
                                $value=$sum/$sumCount;
                                break;
                            case "MAX":
                                $max=0;
                                foreach ($oidArray as $oid) 
                                    {
                                    $value = GetValue($oid);
                                    if ($value > $max) $max=$value;
                                    }
                                $value=$max;
                                break;    
                            case "MIN":
                                $min=100;
                                foreach ($oidArray as $oid) 
                                    {
                                    $value = GetValue($oid);
                                    if ($value < $min) $min=$value;
                                    }
                                $value=$min;
                                break;    
                            default:
                                $value=0;  // unknown pocedure
                                break;
                            }
                        // wert hat bereits den Tabellenbeginn, und eventuell ein Icon, ab hier kommt der Name und der Wert
                        $result=$this->displayValue($wert,$value,$config,$init,$oid,$debug);          // $wert wird erweitert
                        $config["Value"]=$wert;
                        }
                    if ( ($config["Aggregate"] === false) || ($result==false) )            // kein Aggregate or Display failed
                        {                                
                        /* Unterschied ob mehrere Werte dargestellt oder vorher zusammengefasst werden sollen
                         * hier kein Aggregate, aber es können ein oder mehrere Werte sein
                         * hier wird behandelt
                         *      Property
                         *      Type
                         *      Integrate
                         *      Show
                         */
                        foreach ($oidArray as $oid)         // die Werte der Reihe nach durchgehen, jeder Wert hat einen eigenen Eintrag innerhalb der TAbelle
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
                                if ($addInfo != "") $config["AddInfo"]=$addInfo;
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
                                        $count=0; $sum=0; $init2=true;
                                        foreach ($werteLog as $eintrag)
                                            {
                                            if ($init2) { $start=$eintrag["Value"]; $init2=false; }
                                            echo "             ".date("d.m.Y H:i",$eintrag["TimeStamp"])."    ".$eintrag["Value"]."\n";
                                            $count++;
                                            }
                                        }
                                    }               // ende if debug
                                }
                            if (isset($config["Type"]))
                                {
                                $typeOfObject=strtoupper($config["Type"]);
                                if ($debug) echo "****Type available : $typeOfObject\n";                                    
                                } 
                            $value = GetValue($oid);
                            if ($config["Integrate"]>59)       // nur Werte ab einer Minute integrieren
                                {
                                /* Integrate mit einem Wert, es können aber auch mehrere Archive sein
                                 * wir lesen aus dem Archive die Zeitspanne in Sekunden zwischen der Jetztzeit und der Integrate Zeitspanne in Sekunden
                                 * nix machen wenn kein Archive oder keine Werte in der Zeitspanne ausgegeben werden
                                 * Bearbeitung erfolgt anhand von TYPE
                                 *  Standard
                                 *  Raincounter
                                 *  Amis
                                 */
                                $endtime=time();
                                $starttime=$endtime-$config["Integrate"];   // die Werte entsprechend dem angegebenen Zeitraum laden
                                $werteLog  = @AC_GetLoggedValues($archiveID,$oid,$starttime,$endtime,0);                                    
                                if ( ($werteLog===false) || (sizeof($werteLog)==0) ) ;        // $oid bleibt unverändert
                                else
                                    {
                                    switch (strtoupper($typeOfObject))
                                        {
                                        case "AMIS":
                                            if ($debug) echo "     Integrate ".sizeof($werteLog)." Values from last ".$config["Integrate"]." seconds for Type $typeOfObject. \n";
                                            IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');                                        
                                            $amis = new Amis();  
                                            $data=$amis->getArchiveData($oid, $starttime, $endtime, $config["Unit"], true); 
                                            if (isset($data["24h"]))
                                                {
                                                $value=$data["24h"]["Value"];
                                                $config["Unit"] .= "h";
                                                }
                                            break;
                                        case "STANDARD":
                                            $count=0; $sum=0; $max=0; $min=0;
                                            foreach ($werteLog as $eintrag)
                                                {
                                                //If ($debug) echo str_pad($count,6).str_pad($eintrag["Value"],15," ",STR_PAD_LEFT)."   ".date("H:i:s",$eintrag["TimeStamp"])."   \n";
                                                $sum += $eintrag["Value"];
                                                if ( ($min==0) || ($min>$eintrag["Value"]) ) $min=$eintrag["Value"];
                                                if             ($max<$eintrag["Value"])   $max=$eintrag["Value"];
                                                $count++;
                                                }
                                            $value = (float)$sum/$count;            // formatEntry erkennt oid (wenn integer) und oid als value 
                                            if ($debug) echo "     Integrate $count Values from last ".$config["Integrate"]." seconds. Results into Value ".number_format($value,0,",",".")." Min ".number_format($min,0,",",".")." Max ".number_format($max,0,",",".")."\n";
                                            break;
                                        case "RAINCOUNTER":
                                            if ($debug)
                                                {
                                                //print_r($werteLog); 
                                                echo "RainCounter gefunden :\n";
                                                $count=0;
                                                foreach ($werteLog as $eintrag)
                                                    {
                                                    echo str_pad($count,6).str_pad($eintrag["Value"],15," ",STR_PAD_LEFT)."   ".date("d.m.Y H:i",$eintrag["TimeStamp"])."   \n";                                                    
                                                    $count++;
                                                    }
                                                echo "------------\n";
                                                }
                                            $first = reset($werteLog);
                                            $last  = end($werteLog);
                                            // print_R($first);  print_R($last);
                                            $value = $first["Value"]-$last["Value"];
                                            if ($debug) echo "   First : ".$first["Value"]."    ".date("d.m.Y H:i",$first["TimeStamp"])."   Last:  ".$last["Value"]."    ".date("d.m.Y H:i",$last["TimeStamp"])." Wert ergibt sich mit $value.\n";
                                            break;
                                        }
                                    }
                                }
                            if (isset($config["Show"]))         //rausfinden ob die Anzeige nur wenn Bedingungen erfüllt werden erfolgen soll, wenn keine Anzeige erfolgen soll config=false
                                {
                                if ($debug) print_R($config);         //neue Konfig Keys auch in displayValue nachziehen
                                $min=100; $max=-100;
                                foreach ($oidArray as $oid) 
                                    {
                                    $value = GetValue($oid);
                                    if ($value < $min) $min=$value;
                                    if ($value > $max) $max=$value;
                                    }                      
                                if ($debug) echo "Show activated Min $min und Max $max\n";          
                                }    
                            $result=$this->displayValue($wert,$value,$config,$init,$oid,$debug);          // $wert wird erweitert                                    
                            $config["Value"]=$wert;
                            }                           // ende foreach
                        }                           // ende ifnot Aggregate
                    }                           // ende ifObjectExists
                else $config = false;
                }                           // ende OID nicht Default
            return $config;
            }

        /********************
         *
         * Zelle Tabelleneintrag für die Wettertabelle als Widget gemeinsam mit Innen/Aussentemperarur und einer ZusatzZeile
         * wenn parameter false liefert eine Table cell mit einem eingebetteten table mit id nested
         * bei true nur den table
         *
         **************************************/

		function showWeatherTemperatureWidget($config=false,$debug=false)
			{
            if ($debug) echo "showWeatherTemperatureWidget aufgerufen. Zeigt Bilder, Temperatur, Add lines und Wettertabelle:\n";
            if ($config) $useTable=false; else $useTable=true;
            $wert="";
            $weather=$this->getWeatherData();

            if ( (isset($weather["todayDate"])) && ($weather["todayDate"] != "") ) { $tableSpare='<td bgcolor="#c1c1c1"></td>'; $colspan='colspan="2" '; }
            else { $tableSpare=''; $colspan=""; }

            if ($useTable)
                {
                $wert.='<td valign="top" style="vertical-align:top">';
                $wert.='<table id="nested" style="vertical-align:top">';
                }
            else  
                {
                //$wert.='<table id="nested" style="position:absolute; top:0; left: 0">';
                $wert.='<table id="nested" style="display: inline-block;">';                        // aktive Verwendung von display, outer div bestimmt die positionierung
                }

            $wert .= $this->showTemperatureTable($colspan,false,$debug);            // keine Config übergeben
            $wert.= '<tr>'.$this->additionalTableLines($colspan).'</tr>';
            $wert .= $this->showWeatherTable($weather);

            $wert.='</table>';
            if ($useTable) $wert.='</td>';
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

	    function writeStartpageStyle($input="")
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
	        
	        $wert.='.innen-resp         { color:black; background-color: #ffffff; font-size:70px; display:flex; justify-content:center; }';                             // 70px zu 240 px
	        $wert.='.aussen-resp        { color:black; background-color: #c1c1c1; font-size:70px; display:flex; justify-content:space-around;}';
	        $wert.='.innenMed-resp      { color:black; background-color: #ffffff; font-size: 50px; }';                                      // height:100px;
	        $wert.='.aussenMed-resp     { color:black; background-color: #c1c1c1; font-size: 50px; background-color:#c1c1c1;}';
	        $wert.='.innenSmall-resp    { color:black; background-color: #ffffff; font-size: 30px; }';
	        $wert.='.aussenSmall-resp   { color:black; background-color: #c1c1c1; font-size: 30px; background-color:#c1c1c1; }';
	        $wert.='.addText-resp       { color:black; font-size: 24px; align:center; }';                                    // display style="background-color:7f8f9f;"
	        $wert.='.temperatur-resp    { color:black;  font-size: auto; align:center; font-size: 28px;}';
	        $wert.='.datum-resp         { color:black; font-size: auto; align:center; font-size: 28px;}';
            $wert.='.image-temp         { width:240px;}';  
            $wert.='.weather-item-left  { min-width:120px;}';                                                                       // Breite der beiden Icons ist fix, nicht responsive
            /*$wert.='.container-cmd      { display:flex; justify-content:space-between;}';
              $wert.='.image-pic0         { width:100%; max-height:700px; display:none;}';
              $wert.='.image-pic4         { width:50%; max-height:350px; display:none;}';
              $wert.='.image-pic9         { width:33%; max-height:240px; display:inline;}';           // width:33%; max-height:240px    
              $wert.='.button-resp        { color:black; font-size: 1.5vw; align:center; background-color:#a1b1c1;}';                      // font-size: 28px; font-size: auto;
              $wert.='.button-resp:hover  { background-color:#c1b1a1;}';                              // container cmd   
            $wert.='#sp-pic-orf >h2 {font-size: 24px; }';
            $wert.='#sp-pic-orf >p {font-size: 18px; }';            */

            $wert.='infotext { color:white; height:100px; font-size: 12px; }';
		    $wert.='#nested { border-collapse: collapse; border: 2px solid white; background-color: #f1f1f1; width: auto;  }';
	        $wert.='#nested td { border: 1px solid white; }';		  
	        $wert.='#temp td { background-color:#ffefef; }';                // define ID Selectors
	        $wert.='#imgdisp { border-radius: 8px;  max-width: 100%; height: auto;  }';
	        $wert.='#startpage { border-collapse: collapse; border: 2px dotted white; width: 100%;}';
	        $wert.='#startpage td { border: 1px dotted DarkSlateGrey; }';	 
	        $wert.='.container { width: auto; height: auto; max-height:95%; max-width: 100% }';
	        $wert.='.image { opacity: 1; display: block; width: auto; height: auto; max-height: 90%; max-width: 80%; object-fit: contain; transition: .5s ease; backface-visibility: hidden; padding: 5px }';
	        $wert.='.middle { transition: .5s ease; opacity: 0; position: absolute; top: 90%; left: 30%; transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%) }';
	        $wert.='.container:hover .image { opacity: 0.8; }';             // define classes
	        $wert.='.container:hover .middle { opacity: 1; }';
	        $wert.='.StartPageText { background-color: #4CAF50; color: white; font-size: 16px; padding: 16px 32px; }';          // was former .text only, this is used in standard formatting !!!
            $wert.=$input;
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
			$icon="/user/IPSWeatherForcastAT/icons/";
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
	
        /* Wetterdarstellung basierend auf OpenWeatherMap machen
         *
         * erstellt die entsprechend notwendigen Register in categoryId_OpenWeather
         * die erhaltenen Messwerte werden analysiert und in die bekannte 4 Tagevorschau aggregiert
         * danach wird die Erstellung des Meteograms aufgerufen.
         *
         */

		function aggregateOpenWeather($debug=false)
			{
            if (count($this->getOWDs())==0) return (false); 
		    $categoryId_OpenWeather = CreateCategory('OpenWeather',   $this->CategoryIdData, 2000);
		
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
	
            if (false)   // Routine zum Erstellen eines Highchart Diagrans
                {
                /* zusaetzlich auch ein huebsches Meteogram erstellen, benötigter Input ist
                * $categoryId_OpenWeather,$startTime, $endTime
                *
                */
        
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

                /* Create Chart with Config File. IPS Highcharts Library dafür verwenden
                * erst die Konfiguration überprüfen und dann daraus eine Datei erstellen
                * für die Darstellung wird eine Datei erzeugt:   Openweather
                *
                */

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
			}

        /* Meteogram von Openweather anzeigen
         * wurde aus dem AggregateOpenWeather herausgenommen, macht keinen Sinn
         *
         */

        function displayOpenWeather($debug=false)
            {
            if (count($this->getOWDs())==0) return (false);                 
		    $categoryId_OpenWeather = CreateCategory('OpenWeather',   $this->CategoryIdData, 2000);
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

            //if (false)   // Routine zum Erstellen eines Highchart Diagrans
                {
                /* zusaetzlich auch ein huebsches Meteogram erstellen, benötigter Input ist
                * $categoryId_OpenWeather,$startTime, $endTime
                *
                */
        
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
                /* Plot Bands für bessere Lesbarkeit */
                $time=$startTime; $startOfThisDay = $time; $index=0; $color=['#dFdFdF','#eFeFeF'];
                $CfgDaten['xAxis']['type'] = "datetime";
                do 
                    {
                    $nextday=$time+60*60*24;
                    $startOfNextDay=mktime(0,0,0,date("m",$nextday), date("d",$nextday), date("Y",$nextday));                        
                    if ($startOfNextDay>$endTime) $startOfNextDay=$endTime;

                    $CfgDaten['xAxis']['plotBands'][$index]['from'] = "@" . $this->CreateDateUTC($startOfThisDay) ."@";
                    $CfgDaten['xAxis']['plotBands'][$index]['to'] = "@" . $this->CreateDateUTC($startOfNextDay) ."@";
                    $CfgDaten['xAxis']['plotBands'][$index]['color'] = $color[($index % 2)];
                    $CfgDaten['xAxis']['plotBands'][$index]['label']['text'] = date("D",$time);
                    $CfgDaten['xAxis']['plotBands'][$index]['zIndex'] = 3;                                                          // how far in the foreground is shall be plotted

                    $time=$nextday; $index++;
                    $startOfThisDay = $startOfNextDay;

                    } while ($time < ($endTime+60*60*2));



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

                /* nur wenn kalt anzeigen tbd  

                $CfgDaten['yAxis'][0]['plotLines']['color'] = 'red';     // Color value
                $CfgDaten['yAxis'][0]['plotLines']['dashStyle'] = 'longdashdot';     // Style of the plot line. Default to solid
                $CfgDaten['yAxis'][0]['plotLines']['value'] = 0;       // Value of where the line will appear
                $CfgDaten['yAxis'][0]['plotLines']['width'] = 2; // Width of the line  
                //$CfgDaten['yAxis'][0]['plotLines']['zIndex'] = 3;                     
                */

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
                //$CfgDaten['series'][$series+2]['opacity']       = 0;
                $CfgDaten['series'][$series+2]['zIndex']       = 7;
        
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
        
                // Series benötigt Timestamp/y als Keys sonst wird nicht richtig umgesetzt

                $tempforWeekdays+=5;    // 5 Grad noch dazu damit sich oben eine Wochentagsliste ausgeht 
                foreach ($beginn as $index => $value)
                    {
                    $stunde=(integer)date("H",$value["Wert"]);
                    if ($stunde<4)  // je nach Sommer oder Winterzeit 2 oder 3
                        {
                        //echo "Zeitstempel : ".$stunde."\n";
                        $CfgDaten['series'][$series]['data'][] = ["Name" => "hallo","TimeStamp" => $value["Wert"],"y" => $tempforWeekdays];
                        $i=1;
                        }
                    }    */

                /* Create Chart with Config File. IPS Highcharts Library dafür verwenden
                * erst die Konfiguration überprüfen und dann daraus eine Datei erstellen
                * für die Darstellung wird eine Datei erzeugt:   Openweather
                *
                */

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
			

        /* additionalTableLines
         * additional Table Lines werden zwischen temperatur und Wetteranzeige eingebaut
         * mehrer Zeilen, index => [ Name, Type, OID,  ]
         */
			
	    function additionalTableLines($format="")
	        {
	        $wert="";
	        if ( (isset($this->configuration["Display"]["AddLine"])) && (sizeof($this->configuration["Display"]["AddLine"])>0) )
	            {
	            foreach($this->configuration["Display"]["AddLine"] as $tablerow)
	                {
                    if ( (isset($tablerow["Type"])) && (strtoupper($tablerow["Type"])=="INFOFIELD") ) ;     // do nothing
                    else   
                        {
                        //echo "   Eintrag : ".$tablerow["Name"]."  ".$tablerow["OID"]."  ".$tablerow["Icon"]."\n";
                        $wert.='<td '.$format.' bgcolor="#c1c1c1"><addText>'.$tablerow["Name"].'</addText></td><td  bgcolor="#c1c1c1"><addText">'.number_format(GetValue($tablerow["OID"]), 1, ",", "" ).'°C</addText></td>';
                        }
	                }
	            //print_r($this->configuration["AddLine"]);
				//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
	            //echo $wert;
	            }
	        return ($wert);
	        }
	
        /* bottomTableLines()
         * die Bottom Table Line ist am unteren Ende des Bild und Wetter Bildschirms angesiedelt
         * Darstellung vereinheitlicht mit this->evaluateEntry,  für Config dort schauen 
         * nur wenn evaluateEntry erfolgreich, wird ein Eintrag geschrieben
         *
         *      bottomTableLines.evaluateEntry
         *
         * es wird eine eigene Tabellenzeile aufgebaut, die Zellen von darüber werden zusammengefasst und eine neue Tabelle in einer Zeile aufgebaut
         * Input pro Eintrag ist immer die Objekt OID, diese kann ein oder mehrere Werte sein, wird immer als array bearbeitet
         * Konfiguration steht in configuration["Display"]["BottomLine"]
         *
         * Parameter für die Darstellung sind
         *          OID         Einzelwert oder array von OIDs
         *          Name
         *          Icon        wird immer dargestellt, zumindest das IPS Icon
         *          Profile     es gibt ein VAriablenprofil, das kann man auslesen und dann die Darstellung entsprechend nachempfinden
         *          Property    es wird zusätzlich noch das Datum der letzten Änderung hinzugefügt
         *          Type        Zusatzinfo über Art der Variable
         *          Integrate   Anzahl Werte zum integrieren, abhängig vom Type
         *          UNIT        Einheit am Ende des Wertes, vor addon wie in Property definiert
         *
         * die Darstellung erfolgt als Tabelle für jeden Wert für den es einen Konfigurationseintrag gibt
         * Pro Konfigeintrag gibt es ein OID, wenn OID ein Array ist werden mehrere Werte angezeigt oder eben zusammengefasst
         *
         */
			
	    function bottomTableLines($debug=false)
	        {
            //$debug=true;
            if ($debug) echo "bottomTableLines  mit Debug ".($debug?"ein":"aus")." aufgerufen:\n"; 
	        $wert=""; $typeOfObject="STANDARD";
	        if ( (isset($this->configuration["Display"]["BottomLine"])) && (sizeof($this->configuration["Display"]["BottomLine"])>0) )
	            {
                $wert.='<table><tr>';
	            foreach($this->configuration["Display"]["BottomLine"] as $tableEntry)       // jeden Eintrag durchgehen
	                {
                    $configBottomLine = $this->evaluateEntry($tableEntry, false, $debug);           //addText is used
                    if ($configBottomLine)
                        {
                        $wert.= '<td>';
                        $wert.='<img src="/user/Startpage/user/icons/'.$configBottomLine["Icon"].'.svg" alt="'.$configBottomLine["Icon"].' Icon">';
                        $wert.='</td><td>';
                        if ( (isset($configBottomLine["Value"])) && ($configBottomLine["Value"] !== false) )     $wert.= $configBottomLine["Value"];
                        //if ( (isset($configBottomLine["AddInfo"])) && ($configBottomLine["AddInfo"] !== false) ) $wert.= '<div class="addText">    '.$configBottomLine["AddInfo"].'</div>';
                        if ( (isset($configBottomLine["AddInfo"])) && ($configBottomLine["AddInfo"] !== false) ) $wert.= '<addText>    '.$configBottomLine["AddInfo"].'</addtext>';
                        $wert.='</td>';
                        }
                    }                           // ende foreach Einträge

                $wert.='</tr></table>';
	            //print_r($this->configuration["AddLine"]);
				//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
	            //echo $wert;
	            }               // ende es gibt eine bottom Line
	        return ($wert);            
	        }

        /* einen Wert am Webfront als Tabelleneintrag anzeigen , nutzt formatEntry
         * in der Config altText übergibt alternative html identifier und class Zeichen, sonst p class=addText
         *
         * der Wert wird als value übergeben, die Art der Darstellung steht in config und wert ist die Darstellung innerhalb einer html Tabelle
         * verwendet <addText> als Tag identifier, nicht empfohlen
         *
         * tableEntry ist Configuration stored in config["Display"]["BottomLine"] oder config["Temperature"]
         * Nachdem nur ein Wert und keine OID übergeben wird, funktioniert die Config ["Unit"]="AUTO" nicht, verwende string GetValueFormattedEx (integer $VariableID, variant $value)
         * mit &init wird gesteuert on am Anfang der Name ausgegeben werden soll: init=false nein, wenn ja wird nachher auf false gesetzt
         * Verwendet:  oder 
         *      Profile     für die Farbkodierung
         *      Unit        für die Einheit
         *      ---         normales formatEntry, auch in dieser class
         *
         * kann folgende Befehle nicht, returns false
         *
         */

        function displayValue(&$wert, float $value, $tableEntry, &$init, $oidAlt=false, $debug=false)
            {
            /* zur Darstellung des Wertes */
            if (isset($tableEntry["altText"])) { $addText=$tableEntry["altText"]; $addTextE=$tableEntry["altText"]; }
            //else $addText='p class="addText'; $addTextE='p';
            else $addText='addText'; $addTextE='addText';
            if ($debug) echo "   displayValue mit Konfiguration ".json_encode($tableEntry)." und Wert $value aufgerufen. Init ist ".($init?"aktiv":"nicht aktiv")."\n";
            //if ( (isset($tableEntry["Property"])) || (isset($tableEntry["Integrate"])) ) return (false);
            if (isset($tableEntry["Profile"])) 
                {
                $profileConfig=IPS_GetVariableProfile ($tableEntry["Profile"]);
                $color="F1F1F1";                                // default color
                if (isset($profileConfig["Associations"]))  
                    {
                    foreach ($profileConfig["Associations"] as $index => $association)
                        {
                        if ($association["Value"]<=$value)                  // Wert groesser 0 color setzen usw.
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
                    //print_R($profileConfig);
                    echo "displayValue: Profile, letzte Farbe Association ist #$color\n";
                    }
                if ($init) { $wert .='<'.$addText.' style="background-color:#'.$color.';color:darkgrey;">'.$tableEntry["Name"].'   '; $init=false; }
                else $wert .= '<'.$addText.' style="background-color:#'.$color.';color:darkgrey;">|';                    
                //$wert .='<addText style="background-color:#'.$color.';color:darkgrey;">'.$tableEntry["Name"].'   ';
                $wert .= $this->formatEntry($value, $tableEntry["Unit"],$oidAlt).'</'.$addTextE.'>';                   // wenn als float übergeben wird handelt es sich nicht um eine OID
                }
            elseif (isset($tableEntry["Unit"]))                         // es gibt eine Einheitsbezeichnung
                {
                if ($init) { $wert .= '<'.$addText.'>'.$tableEntry["Name"].'   ';$init=false; }
                else $wert .= '<'.$addText.'>|';                    
                //$wert .= '<addText>'.$tableEntry["Name"].'   ';
                $wert .= $this->formatEntry($value, $tableEntry["Unit"],$oidAlt).'</'.$addTextE.'>';
                }
            else                                                        // es gibt nix
                {
                if ($init) { $wert .= '<'.$addText.'>'.$tableEntry["Name"].'   ';$init=false; }
                else $wert .= '<'.$addText.'>|';                    
                //$wert .= '<addText>'.$tableEntry["Name"].'   ';
                $wert .= $this->formatEntry($value, "").'</'.$addtextE.'>';
                }
            return (true);
            }

        /* formatting with hints in format oder nutzt zusätzlich bei format=auto als Quelle eine andere oid, definiert mit oidAlt 
         * liefert string, kein html5
         * format unterstützt: ppm, auto, temp, mm, bps, presence, default
         *
         * es wird die OID übergeben. Wenn die OID nicht integer ist dann als Wert betrachten. Übergabe in diesem Fall als float oder boolean
         * displayValue verwendet zum Beispiel den Wert

         * return ist der formattierte Wert
         * 
         */

        function formatEntry($oid, $format, $oidAlt=false)
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
                    else 
                        {
                        if ($oidAlt !== false) $wert .= GetValueFormattedEx($oidAlt, $oid);
                        else $wert.=number_format($oid, 0, ",", "" );
                        }
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
                case "PRESENCE":
                    if (is_integer($oid)) $value=GetValue($oid);                // es wurde eine oid übergeben
                    else $value=$oid;
                    $wert.=($value?"Ava":"Off");            
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
                $zusammenfassungID=$this->findeVariableName("Zusammenfassung",$OWD);
				if ($zusammenfassungID) $html .= GetValue($zusammenfassungID)."\n\n";
                else echo "Fehler, Zusammenfassung des Wetters bei OpenWeather noch nicht aktiviert.\n";
				}
			$html    .= "\n".'</body>'."\n".'</html>';             // Abschluss für das Include als iFrame
            if ($this->newStyle) $verzeichnis = $this->dosOps->correctDirName(IPS_GetKernelDir().'user\Startpage');					
            else $verzeichnis = $this->dosOps->correctDirName(IPS_GetKernelDir().'webfront\user\Startpage');					
			$filename=$verzeichnis.'Startpage_Openweather.php';
			if (!file_put_contents($filename, $html)) {
		        throw new Exception('Create File '.$filename.' failed!');
		    		}			
			}


		}		// ende class
		
    /* Widgets zusammenfassen, alte Routinen im Handler überschreiben und responsive darstellen
     * Folgende neue Routinen wurden übernommen
     *  setDebugMode                                Debug ein/ausschalten, ohne Einzelparameter in jeder function
     *  selectPictures                              Auswahl von 1 bis max Bilder aus Smallpics
     *  showPictureWidgetResponsive                 diese anzeigen,1,4 oder 9 Stück
     *  showTemperatureTableIcons                   die Icons Aussen und Innen
     *  showTemperatureTableValues
     *  analyseEntryResponsive
     *  additionalTableLinesResponsive
     *  showWeatherTableResponsive
     *  tempTableLineResponsive
     *  bottomTableLinesResponsive
     *  infoTableLinesResponsive
     *  checkEntryConfig
     *  evaluateEntryResponsive
     *  displayValueResponsive
     *
     */    
    class StartpageWidgets extends StartpageHandler          
		{

        protected $debug=false;

        public function __construct()                 // alles responsive darstellen
            {
            parent::__construct();          // nicht vergessen den parent construct auch aufrufen

            }

        public function setDebugMode($debug)
            {
            $this->debug=$debug;
            }

        /* selectPictures
         * eine Auswahl von Bildern aus einer Serie herausnehmen
         * return als array von filenamen - ohne Verzeichnisangabe
         */
         public function selectPictures($max=1,$orientation="Landscape")
            {
            $result=array();
            $getPictureDir = $this->getPictureDirectory();
            $dosOps = new dosOps();
            $files = $dosOps->readdirToArray($getPictureDir."SmallPics/",["detailed"=>true,"filter"=>"*.jpg"]);           // case insensitive, nach jpeg kann nicht gleichzeitig gesucht werden
            if ($files) 
                {
                $maxcount=count($files);
                $showfile=rand(1,$maxcount-1);                    
                //echo "proceed, $maxcount files found, start with $showfile, get $max files.  \n";
                $files = $dosOps->writedirToArray($getPictureDir."SmallPics/",$files);      // true Debug
                //print_r($files);                                    // Liste von Filenamen
                foreach ($files as $index => $file)                 // Pano erkennen, Workaround
                    {
                    $ratio = round($file["Image"]["Width"]/$file["Image"]["Height"],2);
                    //echo str_pad($file["Filename"],80)."  ".str_pad($file["Image"]["Width"]."/".$file["Image"]["Height"],20)." Ratio : $ratio\n";
                    if ($ratio>1.55) $files[$index]["Image"]["Orientation"]="Panorama";
                    }

                $check=sizeof($files); $count=0; $i=0;
                if ($this->debug) 
                    {
                    echo "selectPictures($max, $orientation) out of ".sizeof($files)." available.\n";
                    //print_r($files);
                    }    
                do {
                    if ($this->debug) echo " Look for $showfile : ($i/$count/$max)";
                    $i++;
                    if (isset($files[$showfile])) 
                        {
                        if ( ($orientation) && (isset($files[$showfile]["Image"]["Orientation"])) && ($files[$showfile]["Image"]["Orientation"]==$orientation) )
                            {
                            $result[]=$files[$showfile];
                            $count++;                               // Bilderzähler
                            }
                        elseif ($this->debug) echo "$orientation != ".$files[$showfile]["Image"]["Orientation"];
                        $showfile++;
                        }
                    else 
                        { 
                        $showfile=0; 
                        if ($this->debug) echo "File not found start from 0";
                        }
                    if ($this->debug) echo "\n";
                    } while ( ($i < $check) && ($count<$max) );
                }
            return ($result);
            }

        /* StartpageWidgets::showPictureWidgetResponsive
         *
         * Zelle Tabelleneintrag für die Darstellung eines BestOf Bildes
         * width:100%, eingeschränkt vom übergeordneten div
         * es gibt ein Bild mit 100% und 4 Bilder mit 50%, man kann umschalten mit Hide
         *
         * #sp-pic-img-full #sp-pic-img-p0..n
         * .image-pic0   .image-pic4   .image-pic9
         *
         *
         ************************************/

		function showPictureWidgetResponsive($max=1,$config=false)
			{
            $wert=""; 
            $single=1; $quad=4; $ninth=9;                                       // responsive, switch between formatting
            $verzeichnis = $this->getPictureDirectory()."SmallPics/"; 
            $verzeichnisWeb = "/user/Startpage/user/pictures/SmallPics/";              
            if  (is_array($config))         //extra configuration
                {
                if (isset($config["fixed"]))                // not responsive
                    {
                    $single=1;
                    $quad=false;
                    $ninth=false;
                    }
                }
            /*
            $file=$this->readPicturedir();
            $maxcount=count($file);
            $showfile=rand(1,$maxcount-1);
            $filename = $file[$showfile];
            $verzeichnis    = $this->dosOps->correctDirName(IPS_GetKernelDir()."/webfront".$verzeichnisWeb);
            $info=getimagesize($verzeichnis.$filename);     // Index 3 ist eine Zeichenkette mit dem Attributen Breite und Höhe in der Form height="yyy" width="xxx" zur Verwendung in einem IMG-Tag.
            */
            $files=$this->selectPictures($max);
            if (sizeof($files)>0)
                {
                //$wert.= '<div id="sp-pic-img" style="width:100%; background-color:#202024;">';
                $i=0;
                foreach ($files as $file) 
                    {
                    $filename=$file["Filename"];                        
                    if (file_exists($verzeichnis.$filename)) 
                        {
                        /*if ($i==0)  
                            {
                            $wert.= '<div id="sp-pic-img-full" style="display:none;">';
                            $wert.=     '<img class="image-pic0" src="'.$verzeichnisWeb.$filename.'" alt="'.$filename.'" >';   // definiert die Größe des Bildes
                            $wert.=     '</div>';
                            } */
                        $filegroesse=number_format((filesize($verzeichnis.$filename)/1024/1024),2);
                        if ($this->debug) 
                            {
                            echo "Filename $filename vorhanden - Groesse ".$filegroesse." MB.\n";
                            print_r($file);
                            }
                        if (false)
                             {    
                            if ($i<$single)        // ein Bild
                                {
                                //$wert.= '<div id="sp-pic-img-p'.$i.'" style="display:block; width:100%; max-height:700px;">';
                                $wert.= '<div id="sp-pic-img-p'.$i.'-1" style="display:block;">';
                                $wert.=     '<img class="image-pic0 image-item-p'.$i.'" src="'.$verzeichnisWeb.$filename.'" alt="'.$filename.'" >';   // definiert die Größe des Bildes, unbedingt drinnen lassen
                                $wert.=     '</div>';
                                }
                            if ( ($i<($quad+2)) )     // vier/sechs Bilder
                                {
                                $wert.= '<div id="sp-pic-img-p'.$i.'-4" ';
                                if  ($i<$quad)  $wert.=         ' style="display:inline">';   
                                else            $wert.=         ' style="display:none">';
                                $wert.=     '<img class="image-pic4 image-item-p'.$i.'" src="'.$verzeichnisWeb.$filename.'" alt="'.$filename.'" >';
                                $wert.=     '</div>';
                                }
                            if ($i<$ninth)                 // neun Bilder
                                {
                                $wert.= '<div id="sp-pic-img-p'.$i.'-9" style="display:inline;";">';
                                $wert.=     '<img class="image-pic9 image-item-p'.$i.'" src="'.$verzeichnisWeb.$filename.'" alt="'.$filename.'" >';   // definiert die Größe des Bildes
                                $wert.=     '</div>';
                                }
                             }
                        if (true)
                            {
                            $wert.= '<div id="sp-pic-img-p'.$i.'" class="';
                            if ($i<1) $wert.=     'sp-image-item0 sp-image-item2 sp-image-item4 sp-image-item6 sp-image-item9"';
                            elseif ($i<2) $wert.= '               sp-image-item2 sp-image-item4 sp-image-item6 sp-image-item9"';
                            elseif ($i<4) $wert.= '                              sp-image-item4 sp-image-item6 sp-image-item9"';
                            elseif ($i<6) $wert.= '                                             sp-image-item6 sp-image-item9"';
                            elseif ($i<9) $wert.= '                                                            sp-image-item9"';
                            else          $wert.= '"';
                            $wert.= ' style="display:inline;">';
                            $wert.= '<img class="image-item-p'.$i.' ';
                            if ($i<1) $wert.=     'sp-image-pic0 sp-image-pic2 sp-image-pic4 sp-image-pic6 sp-image-pic9"';
                            elseif ($i<2) $wert.= '              sp-image-pic2 sp-image-pic4 sp-image-pic6 sp-image-pic9"';
                            elseif ($i<4) $wert.= '                            sp-image-pic4 sp-image-pic6 sp-image-pic9"';
                            elseif ($i<6) $wert.= '                                          sp-image-pic6 sp-image-pic9"';
                            elseif ($i<9) $wert.= '                                                        sp-image-pic9"';
                            else          $wert.= '"';
                            $wert.= ' src="'.$verzeichnisWeb.$filename.'" alt="'.$filename.'" style="width:33%; max-height:240px; display:inline;">';   // definiert die Größe des Bildes, unbedingt drinnen lassen
                            $wert.=     '</div>';
                            }
                        $i++;
                        }
                    }
               //$wert.=     '</div>';
                }
            return ($wert);
            }


        /********************
         *
         * Teil von showTemperatureTable für responsive Darstellung
         *
         * Zelle Tabelleneintrag für die Tabelle für Innen und Aussentemperatur
         * zeigt die beiden schönen Icons für Innen und Aussenthermometer an
         * macht zumindest 2 Zeilen mit jeweils 2 Zellen
         * ausgelegt als Aufruf als Widget
         *
         * es gibt auch eine Konfiguration, getTempTableConf zum Vorverarbeiten verwendet
         * Aufruf von StartpageWrite als Grossbild übergibt keine Konfiguration
         *
         **************************************/

		function showTemperatureTableIcons()
            {
            if ($this->debug) echo "showTemperatureTableIcons aufgerufen. \n";
            $wert="";
            $wert .=        '<div id="sp-table-aussen-jpg" style="grid-area:icon; background-color:#c1c1c1">'; 
            $wert .=            '<img class="image-temp" src="/user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"  >';               // style="width:100%;"
            $wert .=            '</div>';
            $wert .=        '<div id="sp-table-innen-jpg" style="background-color:#ffffff">'; 
            $wert .=            '<img class="image-temp" src="/user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur" >';                       // style="width:100%;"
            $wert .=            '</div>';
            return ($wert);
            }

        /* StartpageWidgets::showTemperatureTableValues
         * Teil von showTemperatureTable für responsive Darstellung
         */

		function showTemperatureTableValues($count=false)
            {
            $tempConf=$this->configuration["Temperature"];

            if ($this->debug) echo "showTemperatureTableValues aufgerufen, Configuration analysieren: ".json_encode($tempConf)."\n";
            $config=array();
            $indexNum = 0;
            foreach ($tempConf as $index => $regsConf)
                {
                $config[$indexNum]["name"] = $index;
                configfileParser($tempConf[$index], $config[$indexNum], ["Innen"],"Innen",null);
                configfileParser($tempConf[$index], $config[$indexNum], ["Aussen"],"Aussen",null);
                configfileParser($tempConf[$index], $config[$indexNum], ["Unit","UNIT"],"Unit","");
                configfileParser($tempConf[$index], $config[$indexNum], ["Size","SIZE"],"Size","Large");
                $config[$indexNum]["Innen"]  = $this->analyseEntryResponsive($config[$indexNum]["Innen"],false,false);              // kein altText keine class
                $config[$indexNum]["Aussen"] = $this->analyseEntryResponsive($config[$indexNum]["Aussen"],false,false);
                $indexNum++; 
                }
            $tempTableConf = $config;                

            $wert="";            
            if ($this->debug) 
                {
                echo "     Configuration abarbeiten: ";
                //echo " : ".json_encode($tempTableConf);
                echo " .\n";
                }
            $i=0;
            if ( ($tempTableConf===null) || (count($tempTableConf)==0) )            // schon wieder Defaultwerte
                {
                /* get Variables */
                $innen="innentemperatur";
                $aussen="aussentemperatur";
                if ($this->debug) echo "   Keine Konfiguration angegeben. So wie früher die beiden functions innentemperatur() $innen und aussentemperatur() $aussen einlesen.\n"; 
                if (function_exists($innen)) $this->innentemperatur=$innen();				
                if (function_exists($aussen)) $this->aussentemperatur=$aussen();

                $wert.='<div id="sp-table-aussen-temp" style="grid-area:span'.$i.'; background-color:#c1c1c1">';
                $wert.='    <div id="sp-table-aussen-temp-val" class="aussen-resp" >'.number_format($this->aussentemperatur, 1, ",", "" ).'°C';
                $wert.='        </div>';         // kein p besser div
                $wert.='    </div>';
                $wert.='<div id="sp-table-innen-temp" style="background-color:#ffffff">';
                $wert.='    <div id="sp-table-innen-temp-val" class="innen-resp" >'.number_format($this->innentemperatur, 1, ",", "" ).'°C';
                $wert.='        </div>';
                $wert.='    </div>';
                if ($this->debug) echo "Aussen ".number_format($this->aussentemperatur, 1, ",", "" )."°C Innen ".number_format($this->innentemperatur,1, ",", "" )."°C \n";
                if ($count !== false) $count++;
                $i++;
                }
            else                        // entsprechend der Konfiguration
                {    
                //print_R($tempTableConf);
                $i=0;
                foreach ($tempTableConf as $entry) 
                    {
                    if ($this->debug) echo "      ".json_encode($entry)."\n";
                    /* Darstellung mit $aussenWert und $unitAussen
                     * unit wird von Unit übernommen
                     * Wert kommt entweder von
                     *      FUNCTION        Aufruf der function, liefert Wert, unit extra
                     *      OID             Getvalue der OID, liefert Wert, unit extra
                     *      ARRAY           übernimmt VALUE samt Unit
                     */
                    $aussen="aussen";$innen="innen";
                    $aussenWert="unknown";
                    $unitAussen=$entry["Unit"]; $unitInnen=$unitAussen;
                    if (isset($entry["Aussen"]["FUNCTION"]))    $aussenWert = round($entry["Aussen"]["FUNCTION"](),1);
                    if (isset($entry["Aussen"]["OID"]))         $aussenWert = round(GetValue($entry["Aussen"]["OID"]),1);
                    if (isset($entry["Aussen"]["ARRAY"]))       { $aussenWert = $entry["Aussen"]["ARRAY"]["Value"]; $unitAussen=""; }
                    
                    if (isset($entry["Innen"]["FUNCTION"]))     $innenWert  = round($entry["Innen"]["FUNCTION"](),1);
                    if (isset($entry["Innen"]["OID"]))          $innenWert  = round(GetValue($entry["Innen"]["OID"]),1);
                    //if ($this->debug) echo " Innenwert: $innenWert und Aussenwert: $aussenWert wurden ermittelt.\n";
                    $size=strtoupper($entry["Size"]);
                    if ($size == "LARGE") { $aussen="aussen"; $innen="innen"; }
                    elseif ($size == "MED")  { $aussen="aussenMed"; $innen="innenMed"; }
                    else { $aussen="aussenSmall"; $innen="innenSmall"; }

                    $wert.='<div class="'.$aussen.'-resp" style="grid-area:span'.$i.'; background-color:#c1c1c1">';
                    $wert.='    <div>'.$aussenWert.$unitAussen;                // nur class definieren
                    $wert.='        </div>';
                    $wert.='    </div>';
                    $wert.='<div class="'.$innen.'-resp" style="background-color:#ffffff">';
                    $wert.='    <div>'.$innenWert.$unitInnen;
                    $wert.='        </div>';
                    $wert.='    </div>';
                    if ($count !== false) $count++; 
                    $i++;                   
                    }
                }
            if ($count===false) return ($wert);
            else return ($count);
            }


        /* jeder Innen oder Aussenwert kann auf unterschiedliche Weise dargestellt werden 
         *      Zahl        OID, Wert wird überprüft
         *      Funktion    FUNCTION, Wert wird überprüft
         * 
         */ 

        function analyseEntryResponsive($value,$altText=false,$altClass=false)
            {
            $analyze=array();
            if (is_array($value))           // ist eine Konfiguration
                {
                $value["Class"]=$altClass;
                $analyze["ARRAY"]=$this->evaluateEntryResponsive($value,$altText);
                if ($this->debug)
                    {
                    echo "analyseEntryResponsive ".json_encode($value)."\n";
                    //print_r($analyze);
                    }
                }  
            elseif (is_numeric($value)) 
                {
                if (IPS_ObjectExists ($value)) $analyze["OID"]=$value;
                //echo "Wert ist Numerisch : \n";
                }
            elseif (function_exists($value)) $analyze["FUNCTION"]=$value;
            else echo "Nicht bekannter Wert, vielleicht IPSHeat ?\n";
            return ($analyze); 
            }

        /* getOrfWeatherFromUrl
         *
         */
        function getOrfWeatherFromUrl()
            {
            $url = "https://wetter.orf.at/wien/prognose";
            $result = file_get_contents($url);

            $dom = new DOMDocument;
            libxml_use_internal_errors(true);               // Header, Nav and Section are html5, may cause a warning, so suppress 
            $dom->loadHTML($result);
            $storyWrapper=$dom->getElementById("ss-storyText");            //DOMElement
            $story = $storyWrapper->childNodes->item(1);

            $html = new App_Convert_XmlToArray();
            $storytale=$html->innerHTML($story);              
            /* start at h2, stopp at div */
            $pos1=strpos($storytale,"<h2");
            $pos2=strpos($storytale,"<div");
            $storyWeather=substr($storytale,$pos1,($pos2-$pos1));
            return ($storyWeather);
            }

        /* inclOrfWeather
         *
         */
        function inclOrfWeather()
            {
            $wert="";  
            $wert.='<div id="sp-pic-orf">';
            $wert.=$this->getOrfWeatherFromUrl();
            $wert.='</div>';
            return ($wert);
            }

       /* commandLineResponsive
         * somme Buttons to control Display with Javascript
         * 
         * html5: id=,2 class=
         */
	    function commandLineResponsive($count=false)               
	        {
	        $wert="";
            
            $button=["eins","zwei","drei","vier","fünf","sechs","sieben","acht","neun","null"];
            $itemMax=sizeof($button);
            for ($i=0; ($i<$itemMax); $i++)
                {
                $item=$i;
                $wert.='<div id="sp-cmd-item'.$item.'" class="button-resp" style="background-color:"#d1d1d1">'.$button[$item].'</div>';
                }
            
	        return ($wert);
	        }

        /* additionalTableLinesResponsive
         * additional Table Lines werden zwischen temperatur und Wetteranzeige eingebaut
         * es gibt einen Zeilenzähler, wenn dieser gesetzt ist werden anstelle des Wertes nur die Anzahl der Zeilen zurückgeliefert
         * verwendet aus der class configuration
         * 
         * html5: id=weather-addline-item1,2 class=addText-resp
         */
			
	    function additionalTableLinesResponsive($count=false)               
	        {
	        $wert=""; $countItems=0;
            if ($count !== false) $count=0;             // Init es gibt nur 0
            $configAddLine=array(); 
            foreach ($this->configuration["Display"]["AddLine"] as $name => $widget)              // alle Widgets Speks durchgehen, können auf mehrere Screens verteilt sein !
                {
                configfileParser($widget, $configAddLine[$name], ["TYPE","Type"],"Type" ,$name);  
                configfileParser($widget, $configAddLine[$name], ["NAME","Name"],"Name" ,$name);  
                //configfileParser($widget, $configWidget[$name], ["FORMAT","Format"],"Format" ,'{"BGColor":"#1f242e","width":"500px"}'); 
                //configfileParser($widget, $configWidget[$name], ["SCREEN","Screen"],"Screen" ,1);                  
                //configfileParser($widget, $configWidget[$name], ["CONFIG","Config"],"Config" ,"[]");                   // output array ist configWidget, input array ist widget
                //configfileParser($widget, $configWidget[$name], ["POS","Pos"],"Pos" ,null);                            // default keien ANgabe, d.h. der Reihe nach
                }

	        //if ( (isset($this->configuration["Display"]["AddLine"])) && (sizeof($this->configuration["Display"]["AddLine"])>0) )
            if (sizeof($configAddLine)>0)
	            {
	            foreach($configAddLine as $tablerow)
	                {
	                if ($this->debug) 
                        {
                        echo "   Eintrag : ".$tablerow["Name"];
                        if (isset($tablerow["OID"])) echo "  ".$tablerow["OID"];
                        if (isset($tablerow["Icon"])) echo "  ".$tablerow["Icon"];
                        echo "\n";
                        }
                    switch (strtoupper($tablerow["Type"]))
                        {
                        case "INFOFIELD":
                            $wert.='<div id="weather-addline-item1" class="addText-resp" style="grid-area:add0; background-color:#c1c1c1;">'.$tablerow["Name"].'</div>';
                            $wert.='<div id="weather-addline-item2" class="addText-resp" bgcolor="#c1c1c1"> </div>';                            
                            break;
                        default: 
                            $wert.='<div id="weather-addline-item1" class="addText-resp" style="grid-area:add0; background-color:#c1c1c1;">'.$tablerow["Name"].'</div>';
                            $wert.='<div id="weather-addline-item2" class="addText-resp" style="background-gcolor=#c1c1c1:">'.number_format(GetValue($tablerow["OID"]), 1, ",", "" ).'°C</div>';
                            break;
                        }
                    if ($count !== false) $count++;         // wenn false wird nicht gezählt
                    $countItems++;                          // für die Ausgabe von 0 Zeilen
	                }
	            //print_r($cthis->onfiguration["AddLine"]);
				//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
	            //echo $wert;
	            }
            if ($count !== false) return $count;
            //if ($count==0) { $wert.='<div id="weather-addline-item1" class="addText-resp" style="grid-area:span3">empty1</div><div id="weather-addline-item2" class="addText-resp">empty2</div>'; }
            //if ($countItems==0)  $wert.='<div id="weather-addline-item1" class="addText-resp" style="grid-area:add0"></div>';       // Dummy Zeile nicht mehr notwendig
	        return ($wert);
	        }

        /********************
         *
         * Zelle Tabelleneintrag für die Wettertabelle
         * macht 4 Zeilen mit jeweils 2 oder 3 Zellen
         *
         **************************************/

		function showWeatherTableResponsive($weather=false)
            {
            $wert="";
            if ($weather==false) $weather=$this->getWeatherData();
            if ($weather["todayDate"]=="")
                {
                $wert.= $this->tempTableLineResponsive($weather["todayTempMin"], $weather["todayTempMax"], $weather["today"]);
                $wert.= $this->tempTableLineResponsive($weather["tomorrowTempMin"], $weather["tomorrowTempMax"], $weather["tomorrow"]);
                $wert.= $this->tempTableLineResponsive($weather["tomorrow1TempMin"], $weather["tomorrow1TempMax"], $weather["tomorrow1"]);
                $wert.= $this->tempTableLineResponsive($weather["tomorrow2TempMin"], $weather["tomorrow2TempMax"], $weather["tomorrow2"]);
                }
            else
                {
                $wert.= $this->tempTableLineResponsive($weather["todayTempMin"], $weather["todayTempMax"], $weather["today"],$weather["todayDate"]);
                $wert.= $this->tempTableLineResponsive($weather["tomorrowTempMin"], $weather["tomorrowTempMax"], $weather["tomorrow"], $weather["tomorrowDate"]);
                $wert.= $this->tempTableLineResponsive($weather["tomorrow1TempMin"], $weather["tomorrow1TempMax"], $weather["tomorrow1"], $weather["tomorrow1Date"]);
                $wert.= $this->tempTableLineResponsive($weather["tomorrow2TempMin"], $weather["tomorrow2TempMax"], $weather["tomorrow2"], $weather["tomorrow2Date"]);
                }
            return ($wert);
            }

        /********************
         *
         * macht eine html Zeile mit zwei oder drei Zellen in der Wettertabelle
         *
         **************************************/

		function tempTableLineResponsive($TempMin, $TempMax, $imageSrc, $date="")
			{
			$wert="";
			if ($date=="")
				{
				$wert.='<div id="weather"><div class="temperatur-resp">'.number_format($TempMin, 1, ",", "" ).'°C<br>'.number_format($TempMax, 1, ",", "" ).'°C</div></div>';
				$wert.='<div align="center"> <img src="'.$imageSrc.'" alt="Heute" > </div>';	
				}
			else	
				{
				$wert.= '<div id="weather-date" class="weather-item-left" style="background-color:#ffffff">';
                $wert.=     '<div class="datum-resp">'.$date.'</div>';
                $wert.=     '</div>';
				$wert.= '<div id="weather-temp" class="weather-item-left" style="background-color:#ffffff">';
                $wert.=     '<div class="temperatur-resp">'.number_format($TempMin, 1, ",", "" ).'°C<br>'.number_format($TempMax, 1, ",", "" ).'°C</div>';
                $wert.=     '</div>';
				$wert.= '<div id="weather-icon" class="weather-item-right" align="center" style="background-color:#ffffff">';
                $wert.=     '<img src="'.$imageSrc.'" alt="Heute" width="auto">';
                $wert.=     '</div>';			
				}
			return ($wert);
			}

        /* bottomTableLinesResponsive() nutzt evaluateEntryResponsive
         *
         * die Bottom Table Line ist am unteren Ende des Bild und Wetter Bildschirms von Startpage angesiedelt
         * Darstellung vereinheitlicht mit this->evaluateEntryResponsive,  für Config dort schauen 
         * nur wenn evaluateEntryResponsive erfolgreich, wird ein Eintrag geschrieben
         *
         * html5 Darstellung in einem div mit id ..-index, darin bis zu 3 divs : Icon, Value, AddInfo
         *
         *      bottomTableLinesResponsive.evaluateEntryResponsive
         *
         * Input pro Eintrag ist immer die Objekt OID, diese kann ein oder mehrere Werte sein, wird immer als array bearbeitet
         * Konfiguration steht in configuration["Display"]["BottomLine"]
         *
         * Parameter für die Darstellung sind
         *          OID         Einzelwert oder array von OIDs
         *          Name
         *          Icon        wird immer dargestellt, zumindest das IPS Icon
         *          Profile     es gibt ein VAriablenprofil, das kann man auslesen und dann die Darstellung entsprechend nachempfinden
         *          Property    es wird zusätzlich noch das Datum der letzten Änderung hinzugefügt
         *          Type        Zusatzinfo über Art der Variable
         *          Integrate   Anzahl Werte zum integrieren, abhängig vom Type
         *          UNIT        Einheit am Ende des Wertes, vor addon wie in Property definiert
         *
         * die Darstellung erfolgt als Tabelle für jeden Wert für den es einen Konfigurationseintrag gibt
         * Pro Konfigeintrag gibt es ein OID, wenn OID ein Array ist werden mehrere Werte angezeigt oder eben zusammengefasst
         *
         */
			
	    function bottomTableLinesResponsive($count=false)
	        {
            //$debug=true;
            if ($this->debug) echo "bottomTableLinesResponsive  mit Debug ".($this->debug?"ein":"aus")." aufgerufen:\n"; 
	        $wert=""; $typeOfObject="STANDARD"; 
            $addClass = "addText-resp"; 
            $addText  = "addText-resp"; // html5 Id 
	        if ( (isset($this->configuration["Display"]["BottomLine"])) && (sizeof($this->configuration["Display"]["BottomLine"])>0) )
	            {
                // $wert.='<table><tr>';            // ein Div über die ganze Zeile
	            foreach($this->configuration["Display"]["BottomLine"] as $index => $tableEntry)       // jeden Eintrag durchgehen
	                {
                    $tableEntry["Class"]=$addClass;   
                    $configBottomLine = $this->evaluateEntryResponsive($tableEntry, $addText."-".$index, $this->debug);           //addText is used
                    if ($configBottomLine)
                        {
                        //$wert.= '<td>';
                        $wert.=     '<div class="sp-bot-grid-item" id="sp-bot-grid-'.$index.'" style="display:flex; float:left">';
                        if ( (isset($configBottomLine["Icon"])) && ($configBottomLine["Icon"] !== false) )  {
                            $wert.=     '<div style="background-color:7f8f9f;">';                // style="float:left"
                            $wert.='        <img src="/user/Startpage/user/icons/'.$configBottomLine["Icon"].'.svg" alt="'.$configBottomLine["Icon"].' Icon">';
                            $wert.=     '</div>';    }
                        if ( (isset($configBottomLine["Value"])) && ($configBottomLine["Value"] !== false) )  {                 // beinhaltet jede Menge html5 Formatierung
                            $wert.=     '<div style="display:flex; float:left; background-color:7f8f9f;">'.$configBottomLine["Value"];
                            $wert.=         '</div>';  }
                        if ( (isset($configBottomLine["AddInfo"])) && ($configBottomLine["AddInfo"] !== false) ) {              // keine Formatierung nur Wert, daher Referenz auf class
                            $wert.=     '<div class="'.$addClass.'" style="background-color:7f8f9f;" >    '.$configBottomLine["AddInfo"];
                            $wert.= '        </div>'; }
                        $wert.=         '</div>';
                        if ($count !==false) $count++;
                        }
                    }                           // ende foreach Einträge
	            }               // ende es gibt eine bottom Line
            if ($count !== false) return ($count);
	        return ($wert);            
	        }

        /* die letzte Zeile in der Frame Startpage, verwendet für javascript control
         * wir haben AddLine, BottomLine, InfoLine -> hier ist es InfoLine
         * als Parameter kann auch ein Identifier der als Cookie gespeichert wird übergeben werden
         */
			
	    function infoTableLinesResponsive($count=false)
	        {
            $config=array();
            if (is_array($count)) { $config=$count; $count=false; }
            //$debug=true;
            if ($this->debug) echo "infoTableLinesResponsive  mit Debug ".($this->debug?"ein":"aus")." aufgerufen:\n"; 
	        $wert=""; $typeOfObject="STANDARD"; 
            $addClass = "addText-resp"; 
            $addText  = "infoText-resp"; // html5 Id 
	        if ( (isset($this->configuration["Display"]["InfoLine"])) && (sizeof($this->configuration["Display"]["InfoLine"])>0) )
	            {
                // $wert.='<table><tr>';            // ein Div über die ganze Zeile
	            foreach($this->configuration["Display"]["InfoLine"] as $index => $tableEntry)       // jeden Eintrag durchgehen
	                {
                    $tableEntry["Class"]=$addClass;   
                    $configBottomLine = $this->evaluateEntryResponsive($tableEntry, $addText."-".$index, $this->debug);           //addText is used
                    if ($configBottomLine)
                        {
                        //$wert.= '<td>';
                        $wert.=     '<div id="sp-inf-txt">click here';
                        $wert.=         '</div>';
                        if ($count !==false) $count++;
                        }
                    }                           // ende foreach Einträge
	            }               // ende es gibt eine bottom Line
            if (isset($config["ID"])) 
            $wert.=     '<div id="sp-inf-cookie-id">'.$config["ID"];
            $wert.=         '</div>';
            if ($count !== false) return ($count);
	        return ($wert);            
	        }

        /* Zusammenfassen des Parameter checks
         * für jeden ConfigEintrag tableEntry die Config überprüfen und in $config abspeichern und diese Config verwenden
         *           
         */
        public function checkEntryConfig($tableEntry)
            {
            $config=array();        // immer neu anfangen
            $name="unknown";
            if (is_array($tableEntry))
                {
                configfileParser($tableEntry, $config, ["OID","Oid"],"OID",false);                 //configfile als tableEntry vereinheitlichen, überprüfen, Wert für OID muss vorhanden sein und das Objekt erreichbar
                configfileParser($tableEntry, $config, ["Type","TYPE","type"],"Type",null);                             // default kein Eintrag wenn kein Eintrag
                configfileParser($tableEntry, $config, ["UPDATEIGNORE","Updateignore","UpdateIgnore","updateignore"],"UpdateIgnore",(60*24*60*60));             //default 60 Tage
                // check OID
                $oid = $config["OID"];
                if ($oid !== false)
                    {
                    if (is_array($oid)) $oidArray=$oid;                     // auch Arrays zulassen, dann sollten zwei Werte nebeneinander stehen
                    else $oidArray=[$oid];
                    $config["OID-Requested"]=$oidArray;
                    /* check ob die Objekte vorhanden sind */
                    $objectExists=false; $firstOID=false;
                    $entries=count($oidArray);
                    foreach ($oidArray as $key => $oid) 
                        {
                        if (IPS_ObjectExists($oid)) 
                            {
                            $variableProps=IPS_GetVariable($oid);
                            $diff = $config["UpdateIgnore"] - (time()-$variableProps["VariableChanged"]); 
                            if ($diff>0) $objectExists=true;         // wenn zumindest eines der Objekte existiert und in letzter Zeit upgedated wurde weitermachen
                            else unset($oidArray[$key]);                    // dann auch nicht berücksichtigen
                            if ($firstOID===false) $firstOID=$oid;
                            }
                        else unset($oidArray[$key]);
                        }
                    if (sizeof($oidArray)) $config["OID"]=$oidArray;
                    else $config["OID"]=false;
                    if ($firstOID) $name=IPS_GetName($firstOID);
                    }
                configfileParser($tableEntry, $config, ["Name","NAME"],"Name",$name); 
                configfileParser($tableEntry, $config, ["Icon","ICON"],"Icon","IPS");
                configfileParser($tableEntry, $config, ["Integrate","INTEGRATE","integrate"],"Integrate",false);
                configfileParser($tableEntry, $config, ["Property","PROPERTY","property"],"Property",null);            // default kein Eintrag wenn kein Eintrag
                configfileParser($tableEntry, $config, ["AGGREGATE","Aggregate","aggregate"],"Aggregate",false); 
                configfileParser($tableEntry, $config, ["PROFILE","Profile","profile"],"Profile",null); 
                configfileParser($tableEntry, $config, ["UNIT","Unit","unit"],"Unit",""); 
                configfileParser($tableEntry, $config, ["SHOW","Show","show"],"Show",null);             // Default ist kein Eintrag  
                configfileParser($tableEntry, $config, ["CLASS","Class","class"],"Class",false);                // keine html Formatierung
                }
            else $config["OID"] = $tableEntry;           // ShortCut, Workaround, alles andere bleibt im Default  
            return($config);           
            }

        /* evaluateEntryResponsive, Darstellung von einem oder mehreren Werten in einer Tabelle/Zelle, zb Bottom Line mit displayValueResponsive
         *
         * Es wird immer nur ein Eintrag bearbeitet, OID kann ein Wert oder ein Array aus Werten sein
         * für jede oid prüfen ob das Objekt erreichbar ist, sonst aus der Tabelle von oidArray rausnehmen, es muss zumindest einen gültigen Wert geben
         *
         * Idee ist das Werte mit dem Parameter Aggregate ähnlich bearbeitet werden 
         * und das die Bearbeitung gleich mit der Berechnung des Wertes abschliesst
         *
         * Grundbedingung, mindestens enthaltene Keys
         *      OID     wird als oid bzw oidArray weiter bearbeitet
         *      Type    oder für eine alternative Darstellung ohne OID
         *
         * OID wird evaluiert, es wird daraus wieder eine config ausgegeben
         * es gibt dann noch
         *      Name
         *      Icon
         *      Unit
         *
         *      Aggregate       MEANS,MAX,MIN aus mehreren Werte in einem Array
         *      Integrate       aus einem Wert mit archivierten Werten, kein Aggregate konfiguriert
         *
         * Zusatzfunktionen
         *      Property        nette Darstellung wann letzte Änderung erfolgt ist
         *      Type
         *      Profile
         *      Show            Anzeigen wenn Wertebereich passt
         *
         * Ergebnis return ist dieselbe config, erweitert um 
         *      Value           der Wert als Ergebnis von displayValueResponsive
         *      altText         Parameter altText wird ein Config Eintrag, wird als id verwendet
         *      addInfo         wird mit Datum der letzten Änderung von oid gesetzt wenn Property in der Config
         * 
         * wert startet mit "", init mit rue, dann wird ein oder mehrere Values in wert dargestellt
         * die Zusatzfunktionen vorbereiten und weiter an displayValueResponsive
         *
         */

        function evaluateEntryResponsive($tableEntry, $altText=false)
            {
            if ($this->debug) echo "      evaluateEntryResponsive aufgerufen für ".json_encode($tableEntry).".\n";
            /* check ob eine oder mehrere OIDs angegeben wurden */
            $typeOfObject="STANDARD";
            $wert="";
            $config = $this->checkEntryConfig($tableEntry); 
            if (isset($config["Type"]))
                {
                if ( (strtoupper($config["Type"])) == "INFOFIELD") return $config;     
                }

            $oid=$config["OID"];                
            if ($oid === false) 
                {
                if ($this->debug) echo "Konfiguration enthält keinen Key mit Namen OID, ".json_encode($config)."\n";
                return (false);
                }
            if ($oid !== false)         // kein Defaultwert, hier die Bearbeitung beginnen
                {
                $oidArray=$oid;
                $entries=count($config["OID-Requested"]);
                $entries2=count($oid);
                if ($this->debug) echo "--------evaluateEntryResponsive ($entries2 valid out of $entries) \n";               // was blieb über nach dem check      
                // immer so tun als ob mehrere Werte zusammengefasst werden, selbe Routine
                if ($this->debug) // die Werte der OIDs ausgeben, einer oder mehrere
                    {
                    $i=1;
                    //echo json_encode($oidArray)."\n";
                    //if (sizeof($oidArray)==1) echo "   Eintrag : Name ".$config["Name"]." OID $oid Icon ".$config["Icon"]." Value ".GetValue($oid)."\n";
                    foreach ($oidArray as $oid) 
                        {
                        echo "   ".$i++.":Eintrag : Name ".$config["Name"].", OID: $oid ,Icon ".$config["Icon"].", Value ".GetValue($oid)." ".IPS_GetName($oid).".\n";
                        }
                    }

                if ($altText) $config["altText"]=$altText;
                $init=true; $addInfo="";                    // zuerst Aggregate bearbeiten wenn angefordert wurde
                $result=true;                               // result wird false wenn die Darstellung nicht erfolgreich war
                if ($config["Aggregate"] !== false)
                    {
                    // oidarray vor der Darstellung gemäß Type aggregieren
                    if ($this->debug) echo "   Aggregate ".$config["Aggregate"]." gefunden \n";
                    switch (strtoupper($config["Aggregate"]))
                        {
                        case "MEANS":
                            $sum=0; $sumCount=0;
                            foreach ($oidArray as $oid) 
                                {
                                $sum += GetValue($oid);
                                $sumCount++;
                                }
                            $value=$sum/$sumCount;
                            break;
                        case "MAX":
                            $max=0;
                            foreach ($oidArray as $oid) 
                                {
                                $value = GetValue($oid);
                                if ($value > $max) $max=$value;
                                }
                            $value=$max;
                            break;    
                        case "MIN":
                            $min=100;
                            foreach ($oidArray as $oid) 
                                {
                                $value = GetValue($oid);
                                if ($value < $min) $min=$value;
                                }
                            $value=$min;
                            break;    
                        default:
                            $value=0;  // unknown pocedure
                            break;
                        }
                    // wert hat bereits den Tabellenbeginn, und eventuell ein Icon, ab hier kommt der Name und der Wert
                    $result=$this->displayValueResponsive($wert,$value,$config,$init,$oid);          // $wert wird erweitert
                    $config["Value"]=$wert;
                    }
                if ( ($config["Aggregate"] === false) || ($result==false) )            // kein Aggregate or Display failed
                    {                                
                    /* Unterschied ob mehrere Werte dargestellt oder vorher zusammengefasst werden sollen
                        * hier kein Aggregate, aber es können ein oder mehrere Werte sein
                        * hier wird behandelt
                        *      Property
                        *      Type
                        *      Integrate
                        *      Show
                        */
                    foreach ($oidArray as $oid)         // die Werte der Reihe nach durchgehen, jeder Wert hat einen eigenen Eintrag innerhalb der TAbelle
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
                            if ($addInfo != "") $config["AddInfo"]=$addInfo;
                            if ($this->debug) 
                                {
                                echo "      ".date("d.m.Y H:i:s",$startOfToday)."\n";
                                echo "      Variable Changed $addInfo, got from Property:".$tableEntry["Property"]."   ".json_encode(IPS_GetVariable($oid))."\n";
                                $endtime=time();
                                $starttime=$endtime-$timeGone-(10*60*60);
                                $werteLog  = @AC_GetLoggedValues($archiveID,$oid,$starttime,$endtime,0);                                    
                                if ($werteLog===false) ;        // $oid bleibt unverändert
                                else
                                    {
                                    $count=0; $sum=0; $init2=true;
                                    foreach ($werteLog as $eintrag)
                                        {
                                        if ($init2) { $start=$eintrag["Value"]; $init2=false; }
                                        echo "             ".date("d.m.Y H:i",$eintrag["TimeStamp"])."    ".$eintrag["Value"]."\n";
                                        $count++;
                                        }
                                    }
                                }               // ende if debug
                            }
                        if (isset($config["Type"]))
                            {
                            $typeOfObject=strtoupper($config["Type"]);
                            if ($this->debug) echo "****Type available : $typeOfObject\n";                                    
                            } 
                        $value = GetValue($oid);
                        if ($config["Integrate"]>59)       // nur Werte ab einer Minute integrieren
                            {
                            /* Integrate mit einem Wert, es können aber auch mehrere Archive sein
                                * wir lesen aus dem Archive die Zeitspanne in Sekunden zwischen der Jetztzeit und der Integrate Zeitspanne in Sekunden
                                * nix machen wenn kein Archive oder keine Werte in der Zeitspanne ausgegeben werden
                                * Bearbeitung erfolgt anhand von TYPE
                                *  Standard
                                *  Raincounter
                                *  Amis
                                */
                            $endtime=time();
                            $starttime=$endtime-$config["Integrate"];   // die Werte entsprechend dem angegebenen Zeitraum laden
                            $werteLog  = @AC_GetLoggedValues($archiveID,$oid,$starttime,$endtime,0);                                    
                            if ( ($werteLog===false) || (sizeof($werteLog)==0) ) ;        // $oid bleibt unverändert
                            else
                                {
                                switch (strtoupper($typeOfObject))
                                    {
                                    case "AMIS":
                                        if ($this->debug) echo "     Integrate ".sizeof($werteLog)." Values from last ".$config["Integrate"]." seconds for Type $typeOfObject. \n";
                                        IPSUtils_Include ('Amis_class.inc.php', 'IPSLibrary::app::modules::Amis');                                        
                                        $amis = new Amis();  
                                        $data=$amis->getArchiveData($oid, $starttime, $endtime, $config["Unit"], true); 
                                        if (isset($data["24h"]))
                                            {
                                            $value=$data["24h"]["Value"];
                                            $config["Unit"] .= "h";
                                            }
                                        break;
                                    case "STANDARD":
                                        $count=0; $sum=0; $max=0; $min=0;
                                        foreach ($werteLog as $eintrag)
                                            {
                                            //If ($this->debug) echo str_pad($count,6).str_pad($eintrag["Value"],15," ",STR_PAD_LEFT)."   ".date("H:i:s",$eintrag["TimeStamp"])."   \n";
                                            $sum += $eintrag["Value"];
                                            if ( ($min==0) || ($min>$eintrag["Value"]) ) $min=$eintrag["Value"];
                                            if             ($max<$eintrag["Value"])   $max=$eintrag["Value"];
                                            $count++;
                                            }
                                        $value = (float)$sum/$count;            // formatEntry erkennt oid (wenn integer) und oid als value 
                                        if ($this->debug) echo "     Integrate $count Values from last ".$config["Integrate"]." seconds. Results into Value ".number_format($value,0,",",".")." Min ".number_format($min,0,",",".")." Max ".number_format($max,0,",",".")."\n";
                                        break;
                                    case "RAINCOUNTER":
                                        if ($this->debug)
                                            {
                                            //print_r($werteLog); 
                                            echo "RainCounter gefunden :\n";
                                            $count=0;
                                            foreach ($werteLog as $eintrag)
                                                {
                                                echo str_pad($count,6).str_pad($eintrag["Value"],15," ",STR_PAD_LEFT)."   ".date("d.m.Y H:i",$eintrag["TimeStamp"])."   \n";                                                    
                                                $count++;
                                                }
                                            echo "------------\n";
                                            }
                                        $first = reset($werteLog);
                                        $last  = end($werteLog);
                                        // print_R($first);  print_R($last);
                                        $value = $first["Value"]-$last["Value"];
                                        if ($this->debug) echo "   First : ".$first["Value"]."    ".date("d.m.Y H:i",$first["TimeStamp"])."   Last:  ".$last["Value"]."    ".date("d.m.Y H:i",$last["TimeStamp"])." Wert ergibt sich mit $value.\n";
                                        break;
                                    }
                                }
                            }
                        if (isset($config["Show"]))         //rausfinden ob die Anzeige nur wenn Bedingungen erfüllt werden erfolgen soll, wenn keine Anzeige erfolgen soll config=false
                            {
                            if ($this->debug) print_R($config);         //neue Konfig Keys auch in displayValueResponsive nachziehen
                            $min=100; $max=-100;
                            foreach ($oidArray as $oid) 
                                {
                                $value = GetValue($oid);
                                if ($value < $min) $min=$value;
                                if ($value > $max) $max=$value;
                                }                      
                            if ($this->debug) echo "Show activated Min $min und Max $max\n";          
                            }    
                        $result=$this->displayValueResponsive($wert,$value,$config,$init,$oid);          // $wert wird automatisch erweitert                                    
                        $config["Value"]=$wert;
                        }                           // ende foreach
                    }                           // ende ifnot Aggregate
                }                           // ende OID nicht Default
            return $config;
            }


        /* einen Wert am Webfront als Html5 Div Eintrag anzeigen , nutzt formatEntry
         * der Wert wird als value übergeben, die Art der Darstellung steht in config und wert ist die Darstellung innerhalb einer html Tabelle
         * 
         *

         * Option Profile/Unit oder none
         *  wenn $tableEntry.Profile : background color vom aktuellen Association Profil ableiten
         *  wenn Init                  Name anzeigen und Init zurücksetzen, damit kann man mehrere Werte anzeigen
         *  
         * div id=altText style background-color:color Name
         *
         * html5:  id=addText-resp oder tableEntry.altText
         *
         * tableEntry ist Configuration stored in config["Display"]["BottomLine"] oder config["Temperature"]
         *
         * Nachdem nur ein Wert und keine OID übergeben wird, funktioniert die Config ["Unit"]="AUTO" nicht, verwende string GetValueFormattedEx (integer $VariableID, variant $value)
         * mit &init wird gesteuert on am Anfang der Name ausgegeben werden soll: init=false nein, wenn ja wird nachher auf false gesetzt
         * Verwendet:  oder 
         *      Profile     für die Farbkodierung
         *      Unit        für die Einheit
         *      altText     übergibt alternative html identifier, sonst
         *      ---         normales formatEntry, auch in dieser class
         *
         * kann folgende Befehle nicht, returns false
         *
         */

        function displayValueResponsive(&$wert, float $value, $tableEntry, &$init, $oidAlt=false)
            {
            /* zur Darstellung des Wertes */
            if (isset($tableEntry["altText"])) { $addText=$tableEntry["altText"]; }
            else $addText='addText-resp';   
            if (isset($tableEntry["Class"])) { $addClass=$tableEntry["Class"]; }
            else $addClass='addText-resp';                                                // id statt tag
            if ($this->debug) echo "   displayValueResponsive mit Konfiguration ".json_encode($tableEntry)." und Wert $value aufgerufen. Init ist ".($init?"aktiv":"nicht aktiv")."\n";
            //if ( (isset($tableEntry["Property"])) || (isset($tableEntry["Integrate"])) ) return (false);
            if (isset($tableEntry["Profile"])) 
                {
                $profileConfig=IPS_GetVariableProfile ($tableEntry["Profile"]);
                $color="F1F1F1";                                // default color
                if (isset($profileConfig["Associations"]))  
                    {
                    foreach ($profileConfig["Associations"] as $index => $association)
                        {
                        if ($association["Value"]<=$value)                  // Wert groesser 0 color setzen usw.
                            {
                            //print_R($association);
                            $color = "000000".dechex($association["Color"]);
                            $color = substr($color,-6);
                            if ($this->debug) echo "     Farbe Association ist #$color  (".$association["Color"].")\n";
                            //if (hexdec($color) > 1000000) $color="1F2F1F";
                            }
                        }
                    //$result='<p style="background-color:black;color:#'.$color.'";>'.$result.'</p>';
                    //$result='<p style="background-color:'.$color.';color:white;">'.$result.'</p>';
                    }
                if ($this->debug)
                    {
                    //print_R($profileConfig);
                    echo "displayValueResponsive: Profile, letzte Farbe Association ist #$color\n";
                    }
                $ergebnis = $this->formatEntry($value, $tableEntry["Unit"],$oidAlt);
                $style='background-color:#'.$color.';color:darkgrey;';
                /*    if ($init) { $wert .='<div id="'.$addText.'" class="'.$addClass.'" style="background-color:#'.$color.';color:darkgrey;">'.$tableEntry["Name"].'   '; $init=false; }
                    else $wert .= '<div class="'.$addClass.'" style="background-color:#'.$color.';color:darkgrey;">|';                    
                    //$wert .='<addText style="background-color:#'.$color.';color:darkgrey;">'.$tableEntry["Name"].'   ';
                    $wert .= $this->formatEntry($value, $tableEntry["Unit"],$oidAlt).'</div>';                   // wenn als float übergeben wird handelt es sich nicht um eine OID */
                }
            elseif (isset($tableEntry["Unit"]))                         // es gibt eine Einheitsbezeichnung
                {
                $ergebnis = $this->formatEntry($value, $tableEntry["Unit"],$oidAlt);
                $style="";
                /*  if ($init) { $wert .= '<div id="'.$addText.'" class="'.$addClass.'">'.$tableEntry["Name"].'   ';$init=false; }
                    else $wert .= '<div class="'.$addClass.'">|';                    
                    //$wert .= '<addText>'.$tableEntry["Name"].'   ';
                    $wert .= $this->formatEntry($value, $tableEntry["Unit"],$oidAlt).'</div>';  */
                }
            else                                                        // es gibt nix
                {
                $ergebnis = $this->formatEntry($value, "");
                $style="";
                /*  if ($init) { $wert .= '<div id="'.$addText.'" class="'.$addClass.'">'.$tableEntry["Name"].'   ';$init=false; }
                    else $wert .= '<div class="'.$addClass.'">|';                    
                    //$wert .= '<addText>'.$tableEntry["Name"].'   ';
                    $wert .= $this->formatEntry($value, "").'</div>';   */
                }
            if ($addClass)
                {
                if ($init) { $wert .= '<div id="'.$addText.'" class="'.$addClass.'" style="'.$style.'">'.$tableEntry["Name"].'   ';$init=false; }
                else $wert .= '<div class="'.$addClass.'" style="'.$style.'">|';                    
                $wert .= $ergebnis.'</div>';
                }
            else $wert .= $ergebnis;
            return (true);
            }


        }

?>