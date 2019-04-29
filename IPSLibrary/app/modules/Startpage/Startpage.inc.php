<?

/*
	 * @defgroup Startpage Include
	 *
	 * Include Script zur Ansteuerung der Startpage
	 *
	 *
	 * @file          Startpage.inc.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

/* alter Inhalt von startpage.inc.php ist jetzt im startpage_copyfiles.ips.php file enthalten
 *
 *
 *
 *
 *
 */

	// Confguration Property Definition

	/* das sind die Variablen die in der data.Startpage angelegt werden soll */

	define ('STARTPAGE_VAR_ACTION',				'Action');
	define ('STARTPAGE_VAR_MODULE',				'Module');
	define ('STARTPAGE_VAR_INFO',				'Info');
	define ('STARTPAGE_VAR_HTML',				'HTML');

	define ('STARTPAGE_ACTION_OVERVIEW',			'Overview');
	define ('STARTPAGE_ACTION_UPDATES',			'Updates');
	define ('STARTPAGE_ACTION_LOGS',				'Logs');
	define ('STARTPAGE_ACTION_LOGFILE',			'LogFile');
	define ('STARTPAGE_ACTION_MODULE',				'Module');
	define ('STARTPAGE_ACTION_WIZARD',				'Wizard');
	define ('STARTPAGE_ACTION_NEWMODULE',			'NewModule');
	define ('STARTPAGE_ACTION_STORE',				'Store');
	define ('STARTPAGE_ACTION_STOREANDINSTALL',	'StoreAndInstall');


	IPSUtils_Include ("IPSLogger.inc.php",                      "IPSLibrary::app::core::IPSLogger");

	/**
	 * Setz eine bestimmte Seite in der Startpage
	 *
	 * @param string $action Action String
	 * @param string $module optionaler Module String
	 * @param string $info optionaler Info String
	 */
	function Startpage_SetPage($action, $module='', $info='') {
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');

		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_ACTION, $baseId), $action);
		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_MODULE, $baseId), $module);
		SetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_INFO, $baseId), $info);
		$typeId = IPS_GetObjectIDByName("Startpagetype", $baseId);
		return ($typeId);		
	}

	/**
	 * Refresh der Startpage
	 *
	 */
	function Startpage_Refresh() {
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
		$variableIdHTML = IPS_GetObjectIDByIdent(STARTPAGE_VAR_HTML, $baseId);
		SetValue($variableIdHTML, GetValue($variableIdHTML));
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

    function getStartpageConfiguration()
        {
        $configuration=startpage_configuration();
        if (!isset($configuration["Display"])) $configuration["Display"]["Weathertable"]="Inactive";
        return ($configuration);
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

	/*************************************************************************************************/

	function configWeather($configuration)
		{
		$weather=array();
		$weather["Active"]=false;
		$weather["Source"]="WU";
		if ( isset ($configuration["Display"]["Weathertable"]) == true ) { if ( $configuration["Display"]["Weathertable"] == "Active" ) { $weather["Active"]=true; } }
		if ( isset ($configuration["Display"]["Weather"]) == true ) 
			{
			/* mehr Parameter verfügbar */
			if ( isset ($configuration["Display"]["Weather"]["Weathertable"]) == true ) { if ( $configuration["Display"]["Weather"]["Weathertable"] == "Active" ) { $weather["Active"]=true; } }
			if ( isset ($configuration["Display"]["Weather"]["WeatherSource"]) == true ) { if ( $configuration["Display"]["Weather"]["WeatherSource"] != "WunderGround" ) { $weather["Source"]="OWD"; } }
			}
		return($weather);
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

		$modulhandling = new ModuleHandling();		// true bedeutet mit Debug
		$OWDs=$modulhandling->getInstances('OpenWeatherData');
	    $result=array();
    	foreach ($OWDs as $OWD) $result[$OWD]=OpenWeatherData_UpdateHourlyForecast($OWD);
	    //print_r($result);
		if ($debug) echo "\n"; 
	    $startTime=time();
	    $endTime=time();
		$daily=array();
		foreach ($OWDs as $OWD)
			{
			$beginn=findeVarSerie("Beginn",$OWD);
			$minimale=findeVarSerie("minimale",$OWD);
			$maximale=findeVarSerie("maximale",$OWD);
			$clouds=findeVarSerie("Bew",$OWD);
			$rains=findeVarSerie("Regenm",$OWD);
            $symbols=findeVarSerie("Wetterbedingung-Sym",$OWD);
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
					SetValue($TodayIcon,findIcon($cloudy,$rainy));
					break;
				case 1:
					if ($debug) echo "Morgen : Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".($cloudy)."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).".\n";
					SetValue($Forecast1DayOfWeek,$day["Date"]);
					SetValue($Forecast1TempMin,$day["minTemp"]);
					SetValue($Forecast1TempMax,$day["maxTemp"]);
					SetValue($Forecast1Icon,findIcon($cloudy,$rainy));
					break;
				case 2:
					if ($debug) echo "Morgen1: Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".($cloudy)."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).".\n";
					SetValue($Forecast2DayOfWeek,$day["Date"]);
					SetValue($Forecast2TempMin,$day["minTemp"]);
					SetValue($Forecast2TempMax,$day["maxTemp"]);
					SetValue($Forecast2Icon,findIcon($cloudy,$rainy));
					break;
				case 3:
					if ($debug) echo "Morgen2: Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".($cloudy)."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).".\n";
					SetValue($Forecast3DayOfWeek,$day["Date"]);
					SetValue($Forecast3TempMin,$day["minTemp"]);
					SetValue($Forecast3TempMax,$day["maxTemp"]);
					SetValue($Forecast3Icon,findIcon($cloudy,$rainy));
					break;
				case 4:
					if ($debug) echo "Morgen3: Temperatur von ".$day["minTemp"]."°C bis ".$day["maxTemp"]."°C Bewölkung ".($cloudy)."% Regen ".$day["Rains"]." mm Regenstaerke ".$rainy."% ".findIcon($cloudy,$rainy).".\n";
					SetValue($Forecast4DayOfWeek,$day["Date"]);
					SetValue($Forecast4TempMin,$day["minTemp"]);
					SetValue($Forecast4TempMax,$day["maxTemp"]);
					SetValue($Forecast4Icon,findIcon($cloudy,$rainy));
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

    	/*
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

	    foreach ($beginn as $index => $value)
		    {
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

    	// Create Chart with Config File
  	    IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");

      	$CfgDaten    = CheckCfgDaten($CfgDaten);
	    //print_r($CfgDaten);
	    echo "Create Config String.\n";
	    $sConfig     = CreateConfigString($CfgDaten);
  
  	    $tmpFilename = CreateConfigFile($sConfig, 'Openweather');
      	WriteContentWithFilename ($CfgDaten, $tmpFilename);

	    echo "Filename fuer Aufruf :".$tmpFilename."\n";

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

		}

	
    function additionalTableLines($configuration,$format="")
        {
        $wert="";
        if ( (isset($configuration["AddLine"])) && (sizeof($configuration["AddLine"])>0) )
            {
            foreach($configuration["AddLine"] as $tablerow)
                {
                //echo "   Eintrag : ".$tablerow["Name"]."  ".$tablerow["OID"]."  ".$tablerow["Icon"]."\n";
    			$wert.='<td '.$format.' bgcolor="#c1c1c1"><addText>'.$tablerow["Name"].'</addText></td><td  bgcolor="#c1c1c1"><addText>'.number_format(GetValue($tablerow["OID"]), 1, ",", "" ).'°C</addtext></td>';
                }
            //print_r($configuration["AddLine"]);
			//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
            //echo $wert;
            }
        return ($wert);
        }

    function bottomTableLines($configuration)
        {
        $wert="";
        if ( (isset($configuration["BottomLine"])) && (sizeof($configuration["BottomLine"])>0) )
            {
            $wert.='<tr>';
            foreach($configuration["BottomLine"] as $tableEntry)
                {
                //echo "   Eintrag : ".$tablerow["Name"]."  ".$tablerow["OID"]."  ".$tablerow["Icon"]."\n";
    			$wert.='<td><addText>'.$tableEntry["Name"].'</addText></td><td><addText>'.number_format(GetValue($tableEntry["OID"]), 1, ",", "" ).'°C</addtext></td>';
                }
            $wert.='</tr>';
            //print_r($configuration["AddLine"]);
			//$wert.='<tr><td>'.number_format($temperatur, 1, ",", "" ).'°C</aussen></td><td align="center"> <innen>'.number_format($innentemperatur, 1, ",", "" ).'°C</innen> </td></tr>';
            //echo $wert;
            }
        return ($wert);            
        }

	/*************************************************************************************************/

    function controlMonitor($status,$configuration)
	    {
    	/* aus Konfiguration lernen ob Remote oder lokal zu schalten ist */
	    $lokal=true;
    	if (isset($configuration["Monitor"]["Remote"]) == true )
	    	{
		    if ( ( strtoupper($configuration["Monitor"]["Remote"])=="ACTIVE" ) && ( isset ($configuration["Monitor"]["Address"]) ) ) $lokal=false; 
    		$url=$configuration["Monitor"]["Address"];
	    	$oid=$configuration["Monitor"]["ScriptID"];
		    }
    	if ($lokal)
	    	{	/* Remote Config nicht ausreichen, lokal probieren */ 
		    switch ($status)
			    {
    			case "on":
	    			IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, 1);
		    		break;
			    case "off":
				    IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "monitor off", false, false, 1);
    				break;
	    		case "FullScren":
		    	default:
			    	IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, 1);
				    break;
    			}	
	    	}
    	else
	    	{	/* remote ansteuern */
		    $rpc = new JSONRPC($url);
    		switch ($status)
	    		{
		    	case "on":
			    	$monitor=array("Monitor" => "on");
				    $rpc->IPS_RunScriptEx($oid,$monitor);
    				break;
	    		case "off":
		    		$monitor=array("Monitor" => "off");
			    	$rpc->IPS_RunScriptEx($oid,$monitor);
				    break;
    			case "FullScren":
	    		default:
		    		$monitor=array("Monitor" => "FullScreen");
				    $rpc->IPS_RunScriptEx($oid,$monitor);
    				break;
	    		}			
		    }																
    	}

?>