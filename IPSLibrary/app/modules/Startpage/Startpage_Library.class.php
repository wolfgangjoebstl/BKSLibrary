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
		 * aus der Stratpage Konfiguration die Einstellung ableiten ob
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
	
			$file=$this->readPicturedir();
			$maxcount=count($file);
			if ($showfile===false) $showfile=rand(1,$maxcount-1);
		
			/* Wenn Configuration verfügbar und nicht Active dann die rechte Tabelle nicht anzeigen */	
			$Config=$this->configWeather();
			//print_r($Config);
			$noweather=!$Config["Active"];
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
		
			/* html file schreiben, Anfang Style für alle gleich */

			$wert="";
		    $wert.= $this->writeStartpageStyle();

			if ( $noweather==true )
				{
		        //echo "NOWEATHER true.\n";
				$wert.='<table id="startpage">';
		        $wert.='<tr><td>';
   				if ($maxcount >0)
		   			{
					$wert.='<img src="user/Startpage/user/pictures/'.$file[$showfile].'" width="67%" height="67%" alt="Heute" align="center">';
					}		
				$wert.='</td></tr></table>';
				}
			else
				{
		        if ($PageType==3)           // Topologie
					{
					//echo "Topologiedarstellung fehlt noch.\n";
        		    }
				elseif ($PageType==2)
					{
					//echo "NOWEATHER false. PageType 2. NoPicture.\n";            	
					$wert.='<table <table border="0" height="220px" bgcolor="#c1c1c1" cellspacing="10"><tr><td>';
					$wert.='<table border="0" bgcolor="#f1f1f1"><tr><td align="center"> <img src="'.$today.'" alt="Heute" > </td></tr>';
					$wert.='<tr><td align="center"> <img src="'.$tomorrow.'" alt="Heute" > </td></tr>';
					$wert.='<tr><td align="center"> <img src="'.$tomorrow1.'" alt="Heute" > </td></tr>';
					$wert.='</table></td><td><img src="user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td><td><strg>'.number_format($this->aussentemperatur, 1, ",", "" ).'°C</strg></td>';
		            $wert.='<td> <table border="0" bgcolor="#ffffff" cellspacing="5" > <tablestyle><tr> <td> <img src="user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur">  </td> </tr>';
        		    $wert.='<tr> <td align="center"> <innen>'.number_format($this->innentemperatur, 1, ",", "" ).'°C</innen> </td> </tr></tablestyle> </table> </td></tr>';
		            $wert.='</table>';
					}
				else
					{
					/*******************************************
					 *
					 * PageType==1,Diese Art der Darstellung der Startpage wird Bildschirmschoner genannt 
					 * Bild und Wetterstation als zweispaltige Tabelle gestalten
					 *
					 *************************/
					$filename = 'user/Startpage/user/pictures/SmallPics/'.$file[$showfile];
					$filegroesse=number_format((filesize(IPS_GetKernelDir()."webfront/".$filename)/1024/1024),2);
					$info=getimagesize(IPS_GetKernelDir()."webfront/".$filename);
					if (file_exists(IPS_GetKernelDir()."webfront/".$filename)) 
						{
						//echo "Filename vorhanden - Groesse ".$filegroesse." MB.\n";
						}
					//echo "NOWEATHER false. PageType 1. Picture. ".$filename."\n\n";            	                
					$wert.='<table id="startpage">';
					if ($todayDate!="") { $tableSpare='<td bgcolor="#c1c1c1"></td>'; $colspan='colspan="2" '; }
					else { $tableSpare=''; $colspan=""; }
					//$wert.='<tr><th>Bild</th><th>Temperatur und Wetter</th></tr>';  /* Header für Tabelle */
					//$wert.='<td><img id="imgdisp" src="'.$filename.'" alt="'.$filename.'"></td>';
					$wert.='<tr><td><div class="container"><img src="'.$filename.'" alt="'.$filename.'" class="image">';
					$wert.='<div class="middle"><div class="text">'.$filename.'<br>'.$filegroesse.' MB '.$info[3].'</div>';
					$wert.='</div></td>';
					$wert.='<td><table id="nested">';
					$wert.='<tr><td '.$colspan.'bgcolor="#c1c1c1"> <img src="user/Startpage/user/icons/Start/Aussenthermometer.jpg" alt="Aussentemperatur"></td>';
					$wert.='<td bgcolor="#ffffff"><img src="user/Startpage/user/icons/Start/FHZ.png" alt="Innentemperatur"></td></tr>';
					$wert.='<tr><td '.$colspan.' bgcolor="#c1c1c1"><aussen>'.number_format($this->aussentemperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($this->innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
		            $wert.= '<tr>'.$this->additionalTableLines($colspan).'</tr>';
		//			$wert.='<tr id="temp"><td><temperatur>'.number_format($todayTempMin, 1, ",", "" ).'°C<br>'.number_format($todayTempMax, 1, ",", "" ).'°C</temperatur></td>';
		//			$wert.='<td align="center"> <img src="'.$today.'" alt="Heute" > </td></tr>';
		//			$wert.='<tr id="temp"><td><temperatur>'.number_format($tomorrowTempMin, 1, ",", "" ).'°C<br>'.number_format($tomorrowTempMax, 1, ",", "" ).'°C</temperatur></td>';
		//			$wert.='<td align="center"> <img src="'.$tomorrow.'" alt="Heute" > </td></tr>';
		//			$wert.='<tr id="temp"><td> <temperatur>'.number_format($tomorrow1TempMin, 1, ",", "" ).'°C<br>'.number_format($tomorrow1TempMax, 1, ",", "" ).'°C</temperatur></td>';
		//			$wert.='<td align="center"> <img src="'.$tomorrow1.'" alt="Heute" > </td></tr>';
		//			$wert.='<tr id="temp"><td> <temperatur>'.number_format($tomorrow2TempMin, 1, ",", "" ).'°C<br>'.number_format($tomorrow2TempMax, 1, ",", "" ).'°C</temperatur></td>';
		//			$wert.='<td align="center"> <img src="'.$tomorrow2.'" alt="Heute" > </td></tr></table></td></tr>';
					if ($todayDate=="")
						{
						$wert.= $this->tempTableLine($todayTempMin, $todayTempMax, $today);
						$wert.= $this->tempTableLine($tomorrowTempMin, $tomorrowTempMax, $tomorrow);
						$wert.= $this->tempTableLine($tomorrow1TempMin, $tomorrow1TempMax, $tomorrow1);
						$wert.= $this->tempTableLine($tomorrow2TempMin, $tomorrow2TempMax, $tomorrow2);
						}
					else
						{
						$wert.= $this->tempTableLine($todayTempMin, $todayTempMax, $today,$todayDate);
						$wert.= $this->tempTableLine($tomorrowTempMin, $tomorrowTempMax, $tomorrow, $tomorrowDate);
						$wert.= $this->tempTableLine($tomorrow1TempMin, $tomorrow1TempMax, $tomorrow1, $tomorrow1Date);
						$wert.= $this->tempTableLine($tomorrow2TempMin, $tomorrow2TempMax, $tomorrow2, $tomorrow2Date);
						}
					$wert.='</table></td></tr>';
		            $wert.=$this->bottomTableLines();
		            $wert.='</table>';
		            }
		        }    
			return $wert;
			}

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