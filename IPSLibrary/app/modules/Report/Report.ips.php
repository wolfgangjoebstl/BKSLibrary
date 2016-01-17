<?

/*


erstellt verschiedene Reports aus den vorhandenen Daten
benötigt HighCharts


*/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('Report_Configuration.inc.php', 'IPSLibrary::config::modules::Report');

/**************************************** INIT *********************************************************/


	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		//echo 'ModuleManager Variable not set --> Create "default" ModuleManager'."\n";
		$moduleManager = new IPSModuleManager('Report',$repository);
	}
	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	//echo "App  ID:  ".$CategoryIdApp."\n";
	//echo "Data ID:  ".$CategoryIdData."\n";
	$ReportPageTypeID = CreateVariableByName($CategoryIdData, "ReportPageType", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$ReportTimeTypeID = CreateVariableByName($CategoryIdData, "ReportTimeType", 1);   /* 0 Boolean 1 Integer 2 Float 3 String */
	$contentvar_ID  = CreateVariable("Uebersicht", 3 /*String*/,  $CategoryIdData, 40, '~HTMLBox', null,null,"");

/**************************************** PROGRAM *********************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);

	}

//if ($_IPS['SENDER']=="Execute")
	{
	//if (IPS_GetMediaID('Highcharts')==0)
	$result=@IPS_GetMediaIDbyName('Highcharts',0);
	if ($result===false)
	   {
		$MediaID = IPS_CreateMedia(4);   /* Chart medienobjekt anlegen */
		IPS_SetName($MediaID, "Highcharts");
		}
	else
	   {
	   $MediaID = $result;
    }
	
	
	$report_config=Report_GetConfiguration();
	SetValue($contentvar_ID,"<iframe src='/User/IPSHighcharts/IPSChart.php?VarID=".$contentvar_ID."&ChartID=".$MediaID." height='400' width='100%' frameborder='0' scrolling='no'></iframe>");
	// Configuration
	$CfgDaten = array();

	$displaypanel=getValueFormatted($ReportPageTypeID);
	
	if ($_IPS['SENDER']=="Execute")
	   {
		echo "Ausgabe der folgenden Werte : ".getValueFormatted($ReportPageTypeID)."\n";
		echo "MediaID ist ".$MediaID." \n";
		}

	//if (false)
   if (isset($report_config[$displaypanel]))
      {
		$yaxis=array();
		if ($_IPS['SENDER']=="Execute")
			{
			echo "Es wird nun die Displaydarstellung von ".$displaypanel." bearbeitet.\n";
			print_r($report_config[$displaypanel]);
			}
      $CfgDaten['title']['text'] = $report_config[$displaypanel]['title'];
		$i=0; $j=0;
		foreach ($report_config[$displaypanel]['series'] as $name=>$serie)
		   {
			if ($_IPS['SENDER']=="Execute")
				{
			   echo "Kurve : ".$name." \n";
			   print_r($serie); echo "\n";
			   }
		 	$serie['name'] = $name;
		 	if ($serie['Unit']=='$')            /* Statuswerte */
		 	   {
		    	$serie['step'] = 'right';
		    	$serie['ReplaceValues'] = array(0=>$j,1=>$j+1);
		    	$j+=2;
			 	if (isset($yaxis[$serie['Unit']]))
				 	{
				 	
				 	}
				else
			 	   {
				 	$serie['yAxis'] = $i;
					$yaxis[$serie['Unit']]= $i;
				 	$i++;
				 	}
		 	   }
		 	else
		 	   {
			 	if (isset($yaxis[$serie['Unit']])) {}
				else
			 	   {
				 	$serie['yAxis'] = $i;
					$yaxis[$serie['Unit']]= $i;
				 	$i++;
				 	}
				}
	    	$serie['marker']['enabled'] = false;
	    	$CfgDaten['series'][] = $serie;
			}
     	$CfgDaten['chart']['alignTicks'] = true;
     	$i=0;
  		$CfgDaten['yAxis'][$i]['opposite'] = false;
  		$CfgDaten['yAxis'][$i]['gridLineWidth'] = 0;
		foreach ($yaxis as $unit=>$index)
		   {
			if ($_IPS['SENDER']=="Execute")
				{
			   echo "**Bearbeitung von ".$unit." und ".$index." \n";
			   }
			if ($unit=='°C')
			   {
		     	$CfgDaten['yAxis'][$index]['title']['text'] = "Temperaturen";
   		 	$CfgDaten['yAxis'][$index]['Unit'] = '°C';
		    	//$CfgDaten['yAxis'][$i]['tickInterval'] = 5;
   		 	//$CfgDaten['yAxis'][$i]['min'] = -20;
	  		 	//$CfgDaten['yAxis'][$i]['max'] = 50;
	  	 		//$CfgDaten['yAxis'][$i]['ceiling'] = 50;
	    	   }
 			if ($unit=='$')         /* Statuswerte */
			   {
		     	$CfgDaten['yAxis'][$index]['title']['text'] = "Status";
   		 	$CfgDaten['yAxis'][$index]['Unit'] = '$';
		    	//$CfgDaten['yAxis'][$i]['tickInterval'] = 5;
   		 	$CfgDaten['yAxis'][$index]['min'] = 0;
	   	 	//$CfgDaten['yAxis'][$i]['offset'] = 100;
		  	 	//$CfgDaten['yAxis'][$i]['max'] = 100;
	   	   }
 			if ($unit=='%')
			   {
		     	$CfgDaten['yAxis'][$index]['title']['text'] = "Feuchtigkeit";
   		 	$CfgDaten['yAxis'][$index]['Unit'] = '%';
	    		$CfgDaten['yAxis'][$index]['opposite'] = true;
		    	//$CfgDaten['yAxis'][$i]['tickInterval'] = 5;
   		 	//$CfgDaten['yAxis'][$index]['min'] = 0;
	   	 	//$CfgDaten['yAxis'][$i]['offset'] = 100;
		  	 	//$CfgDaten['yAxis'][$i]['max'] = 100;
	   	   }
		 	} /* ende foreach */
      }
   else
      {

		}

	if (getValueFormatted($ReportTimeTypeID)=="Tag")
	   {
		$jetzt=time();
		$starttime=$jetzt-24*60*60;
		//echo "Aktuelle Zeit :".$jetzt."\n";
		// Zeitraum welcher dargestellt werden soll (kann durch die Zeitvorgaben in den Serien verändert werden)
		//$CfgDaten['StartTime'] = mktime(0,0,0, date("m", time()), date("d",time())-1, date("Y",time())); // ab 00:00 Uhr von vor 10 Tagen
		//$CfgDaten['StartTime'] = mktime(date("H", $jetzt),date("i", $jetzt),date("s", $jetzt), date("m", $jetzt), date("d",$jetzt), date("Y",$jetzt)); // ab 00:00 Uhr von vor 10 Tagen
		//$CfgDaten['EndTime'] = mktime(23,59,59, date("m", time()), date("d",time()), date("Y",time())); // ab heute 23:59 Uhr, oder
		$CfgDaten['StartTime'] = $starttime;
		$CfgDaten['EndTime'] = $jetzt;   // = bis jetzt
		}

	if (getValueFormatted($ReportTimeTypeID)=="Woche")
	   {
		$jetzt=time();
		$CfgDaten['StartTime'] = $jetzt-7*24*60*60;
		$CfgDaten['EndTime'] = $jetzt;   // = bis jetzt
		}

	if (getValueFormatted($ReportTimeTypeID)=="Monat")
	   {
		$jetzt=time();
		$CfgDaten['StartTime'] = $jetzt-30*24*60*60;
		$CfgDaten['EndTime'] = $jetzt;   // = bis jetzt
		}

	if (getValueFormatted($ReportTimeTypeID)=="Jahr")
	   {
		$jetzt=time();
		$CfgDaten['StartTime'] = $jetzt-365*24*60*60;
		$CfgDaten['EndTime'] = $jetzt;   // = bis jetzt
		}

//$chart_style='spline'; /*    */
$chart_style='line';



// damit wird die Art des Aufrufes festgelegt
$CfgDaten['RunMode'] = "script";     // file, script, popup

if ($CfgDaten['RunMode'] == "popup")
    {
    $CfgDaten['WebFrontConfigId'] = 26841;
    $CfgDaten['WFCPopupTitle'] = "Ich bin der Text, welcher als Überschrift im Popup gezeigt wird";
    }

// IPS Variablen ID´s

$CfgDaten["ArchiveHandlerId"]= IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

$CfgDaten["ContentVarableId"]= $contentvar_ID; // ID der Content-Variable
$CfgDaten["HighChartScriptId"]= 11712;                  // ID des Highcharts Scripts

// Übergabe als File oder ScriptID
$CfgDaten["File"]= true;

// Alle     $CfgDaten["HighChart"] Parameter werden an das IP_Template übergeben
$CfgDaten["HighChart"]["Theme"]="IPS.js";   // created by KHC

$CfgDaten["PlotType"]= $chart_style;

// Highcharts oder Highstock (default = Highcharts
$CfgDaten['Ips']['ChartType'] = 'Highcharts';

// Zeitraum welcher dargestellt werden soll (kann durch die Zeitvorgaben in den Serien verändert werden)
//$CfgDaten['StartTime'] = mktime(0,0,0, date("m", time()), date("d",time())-10, date("Y",time())); // ab 00:00 Uhr von vor 10 Tagen
//$CfgDaten['EndTime'] = mktime(23,59,59, date("m", time()), date("d",time()), date("Y",time())); // ab heute 23:59 Uhr, oder //$CfgDaten['EndTime'] = time();   // = bis jetzt

// add your Variables
//$Dataset["Data"][1] = $variable_ID;
//$Dataset["Data"][2] = 52093;
//$Dataset["Data"][3] = 24533;
//$Dataset["Data"][4] = 52466;
//$Dataset["Data"][5] = 48116;
//$Dataset["Data"][6] = 22648;

/*
$CfgDaten["Daten"]=Null;
$CfgDaten["Categories"]=Null;


foreach ($Dataset["Data"] as $tmp)
{
$CfgDaten["Daten"] = $CfgDaten["Daten"].'["'.IPS_GetName($tmp).'",'.GetValue($tmp).'],'."\n";
$CfgDaten["Categories"] = $CfgDaten["Categories"].'"'.IPS_GetName($tmp).'",'."\n";
}
*/

/*
   $CfgDaten["AllConfigString"] = '
    title: {
                text: "'.$CfgDaten["Title"].'",
                style: {
                      font: "17px Arial, sans-serif",
               },
                    align: "left",
                    x: -10,

        },

   subtitle: {
                text: "'.$CfgDaten["SubTitle"].'",
                x: -10,
            y: 20
        },

    yAxis: {
         showLastLabel: true,
           },


     xAxis: {
        lineWidth: 1,
        title: {
            text:""
        },

        categories: ['.$CfgDaten["Categories"].'],

    },


     plotOptions: {
       bar: {
             showInLegend: false,
               allowPointSelect: true,

            series: {

        },
                dataLabels: {
                        enabled: true,
                    color: "#CCC",
                        formatter: function() {
                    return this.point.y.toPrecision(3) + " Std.";
                        }
                },


                 },



        pie: {
         size: 110,
             showInLegend: true,
            enableMouseTracking: true,
                allowPointSelect: true,
            slicedOffset: 20,
            dataLabels: {
                        enabled: true,
                    color: "#CCC",
                        formatter: function() {
                    return this.point.name + "  " + (parseFloat(this.point.y)*0.14).toPrecision(2)  + "Euro";
                        }
                },

             point: {
                events: {
                    legendItemClick: function() {
                     var point = chart.series[0].data[this.x],y, y_tmp;

                          if (typeof point.y_tmp == "number" && point.y == 0) {
                     y = point.y_tmp;
                            y_tmp =  null;
                                $(point.dataLabel.element).show();
                            $(point.connector.element).show();   ;
                            }
                     else
                            {
                            y = 0;
                          y_tmp = point.y;
                            $(point.dataLabel.element).hide();
                            $(point.connector.element).hide();
                            };
                             chart.series[0].data[this.x].update({y: y, y_tmp: y_tmp});
                          },
                },
                },
             },
          },

   exporting: {
        enabled: false,
        },

    tooltip: {
               backgroundColor: {
                  linearGradient: [0, 0, 10, 40],
                  stops: [
                     [0, "rgba(166, 160, 150, .7)"],
                     [1, "rgba(29, 27, 21, .4)"]
                  ]
               },
               borderWidth: 0,
               style: {
                  color: "#FFF"
               },
               formatter: function() {
                  return "<b>"+ this.point.name +"</b><br/>"+
                      this.percentage.toPrecision(2) +"%";
               }
   },

     series: [{
     type: "'.$CfgDaten["PlotType"].'",
      data: ['.$CfgDaten["Daten"].'],

         }]
     });
 ';

 $CfgDaten["AllConfigString"];

*/

    // Serienübergreifende Einstellung für das Laden von Werten
    $CfgDaten['AggregatedValues']['HourValues'] = 4;      // ist der Zeitraum größer als X Tage werden Stundenwerte geladen, -1 alle Werte
    $CfgDaten['AggregatedValues']['DayValues'] = 100;       // ist der Zeitraum größer als X Tage werden Tageswerte geladen
    $CfgDaten['AggregatedValues']['WeekValues'] = -1;      // ist der Zeitraum größer als X Tage werden Wochenwerte geladen
    $CfgDaten['AggregatedValues']['MonthValues'] = -1;      // ist der Zeitraum größer als X Tage werden Monatswerte geladen
    $CfgDaten['AggregatedValues']['YearValues'] = -1;          // ist der Zeitraum größer als X Tage werden Jahreswerte geladen
    $CfgDaten['AggregatedValues']['NoLoggedValues'] = 1000;     // ist der Zeitraum größer als X Tage werden keine Boolean Werte mehr geladen, diese werden zuvor immer als Einzelwerte geladen    $CfgDaten['AggregatedValues']['MixedMode'] = false;     // alle Zeitraumbedingungen werden kombiniert
    $CfgDaten['AggregatedValues']['MixedMode'] = false;
    // Systematik funktioniert jetzt additiv. D.h. die angegebenen Werte gehen ab dem letzten Wert
    //
    //            -5 Tage           -3 Tage                        EndTime
    // |           |                  |                             |
    // |           |DayValue = 2     |HourValues = 3          |
    // |Tageswerte |Stundenwerte     |jeder geloggte Wert     |

    // **************************************************************************************
    // *** Highcharts Options ***
    // **************************************************************************************
    // Ab hier werden die Bereiche des Highchart-Objektes parametriert.
    // Dieser Bereich wurde (soweit möglich) identisch der Originalstruktur gehalten.
    // Informationen über die Parametrierung findet man unter http://www.highcharts.com/ref/

    // **************************************************************************************
    // *** chart *** http://www.highcharts.com/ref/#chart
    // **************************************************************************************
    // $CfgDaten['chart']['zoomType'] = "'x'";            //default: $CfgDaten['chart']['zoomType'] = "'xy'";

    // **************************************************************************************
    // *** credits *** siehe http://www.highcharts.com/ref/#credits
    // **************************************************************************************
    // $CfgDaten['credits']['text'] = "used by IPS";
    // $CfgDaten['credits']['href'] = "http://www.ip-symcon.de/forum/f53/highcharts-multigraph-v1-0-a-17625/#post120721";

    // **************************************************************************************
    // *** title *** siehe http://www.highcharts.com/ref/#title
    // **************************************************************************************
    // $CfgDaten['title']['text'] = "Chart-Überschrift";  // Überchrift des gesamten Charts
    //        -> veraltet: 'Title' -> verwende ['title']['text']

    //$CfgDaten['title']['text'] = "Chart-Überschrift";

    // **************************************************************************************
    // *** subtitle *** siehe http://www.highcharts.com/ref/#subtitle
    // **************************************************************************************
    // $CfgDaten['subtitle']['text'] = "Zeitraum: %STARTTIME% - %ENDTIME%" // Sub-Überschrift. Wenn nichts angegeben wird wird dieser String als Default verwendet
    //        -> veraltet: 'SubTitle' -> verwende ['subtitle']['text']
    // $CfgDaten['subtitle']['Ips']['DateTimeFormat'] = "(D) d.m.Y H:i"    // z.B.: "(D) d.m.Y H:i" (wird auch als Default herangezogen wenn nichts konfiguriert wurde)
    //        -> veraltet: 'SubTitleDateTimeFormat' -> verwende ['subtitle']['Ips']['DateTimeFormat']
    //    -> entfallen: 'SubTitleFormat' -> unnötiger Paramter, wird jetzt in ['subtitle']['text'] angegeben

    $CfgDaten['subtitle']['text'] = "Zeitraum: %STARTTIME% - %ENDTIME%";
    $CfgDaten['subtitle']['Ips']['DateTimeFormat'] = "(D) d.m.Y H:i";

    // **************************************************************************************
    // *** tooltip *** http://www.highcharts.com/ref/#tooltip
    // **************************************************************************************
    // $CfgDaten['tooltip']['enabled'] = false;
    // $CfgDaten['tooltip']['formatter'] = Null; // IPS erstellt selbständig einen Tooltip
    // $CfgDaten['tooltip']['formatter'] = ""; // Standard - Highcharts Tooltip

    // **************************************************************************************
    // *** exporting *** http://www.highcharts.com/ref/#exporting
    // **************************************************************************************
    // $CfgDaten['exporting']['enabled'] = true;

    // **************************************************************************************
    // *** lang *** http://www.highcharts.com/ref/#lang
    // **************************************************************************************
    // $CfgDaten['lang']['resetZoom'] = "Zoom zurücksetzten";

    // **************************************************************************************
    // *** legend *** http://www.highcharts.com/ref/#legend
    // **************************************************************************************
    // $CfgDaten['legend']['backgroundColor'] = '#FCFFC5';

    // **************************************************************************************
    // *** xAxis *** http://www.highcharts.com/ref/#xAxis
    // **************************************************************************************
    // $CfgDaten['xAxis']['lineColor'] = '#FF0000';
    // $CfgDaten['xAxis']['plotBands'][] = array("color"=>'#FCFFC5',"from"=> "@Date.UTC(2012, 3, 29)@","to"=> "@Date.UTC(2012, 3, 30)@");

    // **************************************************************************************
    // *** yAxis *** http://www.highcharts.com/ref/#yAxis
    // **************************************************************************************
    // $CfgDaten['yAxis'][0]['title']['text'] = "Temperaturen"; // Bezeichnung der Achse
    //        -> veraltet: 'Name' und 'TitleText' -> verwende ['title']['text']
    // $CfgDaten['yAxis'][0]['Unit'] = "°C";    // Einheit für die Beschriftung die Skalenwerte
    //    $CfgDaten['yAxis'][0]['min'] = 0; // Achse beginnt bei Min (wenn nichts angegeben wird wird der Min der Achse automatisch eingestellt)
    //    $CfgDaten['yAxis'][0]['max'] = 40; // Achse geht bis Max (wenn nichts angegeben wird wird der Max der Achse automatisch eingestellt)
    //        -> veraltet: 'Min' und 'Max'
    //    $CfgDaten['yAxis'][0]['opposite'] = false; // Achse wird auf der rechten (true) oder linken Seite (false) des Charts angezeigt (default = false)
    //        -> veraltet: 'Opposite'
    //    $CfgDaten['yAxis'][0]['tickInterval'] = 5; // Skalenwerte alle x (TickInterval)
    //        -> veraltet: 'TickInterval'
    //    -> entfallen: 'PlotBands' -> verwende ['yAxis'][0]['plotBands'],  (siehe Beispiel 'cfg - drehgriff und tf-kontakt')
    //    -> entfallen: 'YAxisColor' -> verwende ['yAxis'][0]['title']['style']
    //    -> entfallen: 'TitleStyle'-> verwende ['yAxis'][0]['title']['style']

	/*
    $CfgDaten['yAxis'][0]['title']['text'] = "Temperaturen";
    $CfgDaten['yAxis'][0]['Unit'] = "°C";
    $CfgDaten['yAxis'][0]['opposite'] = false;
    $CfgDaten['yAxis'][0]['tickInterval'] = 5;
    $CfgDaten['yAxis'][0]['min'] = 0;
    $CfgDaten['yAxis'][0]['max'] = 40;
	*/
	
    //    $CfgDaten['yAxis'][1]['title']['text'] = "Heizungssteller / Luftfeuchte";
    //    $CfgDaten['yAxis'][1]['Unit'] = "%";
    //    $CfgDaten['yAxis'][1]['opposite'] = true;

    //    $CfgDaten['yAxis'][2]['title']['text'] = "Drehgriffkontakte / Türkontakte";
    //    $CfgDaten['yAxis'][2]['labels']['formatter'] = "@function() { if (this.value == 0.5) return 'geschlossen'; if (this.value == 1) return 'gekippt';if (this.value == 2) return 'geöffnet' }@";
    //    $CfgDaten['yAxis'][2]['allowDecimals'] = true;
    //    $CfgDaten['yAxis'][2]['showFirstLabel '] = false;
    //    $CfgDaten['yAxis'][2]['showLastLabel '] = false;
    //    $CfgDaten['yAxis'][2]['opposite'] = true;
    //    $CfgDaten['yAxis'][2]['labels']['rotation'] = 90;

    //    $CfgDaten['yAxis'][3]['title']['text'] = "Columns";
    //    $CfgDaten['yAxis'][3]['Unit'] = "kWh";

    // **************************************************************************************
    // *** series *** http://www.highcharts.com/ref/#series
    // **************************************************************************************
    // $serie['name'] = "Temperatur; // Name der Kurve (Anzeige in Legende und Tooltip)
    //        -> veraltet: 'Name' -> verwende [series']['name']
    // $serie['Unit'] = "°C"; // Anzeige in automatisch erzeugtem Tooltip
    //     wenn $serie['Unit'] = NULL; // oder Unit wird gar nicht definiert, wird versucht die Einheit aus dem Variablenprofil automatisch auszulesen
    // $serie['ReplaceValues'] = false; // Werte werden wie geloggt übernommen
    //     $serie['ReplaceValues'] = array(0=>0.2,1=>10) // der Wert 0 wird in 0.2 geändert, der Wert 1 wird in 10 geändert
    //       das macht für die Darstellung von Boolean Werte Sinn, oder für Drehgriffkontakte (Werte 0,1,2)
    // $serie['type'] = 'spline'; // Festlegung des Kuventypes (area, areaspline, line, spline, pie, Column)
    // $serie['yAxis'] = 0; // Nummer welche Y-Achse verwendet werden soll (ab 0)
    //     -> veraltet: 'Param' -> verwende die Highcharts Parameter - sollte eigentlich noch so funktionieren wie in IPS-Highcharts V1.x
    // $serie['AggType'] = 0 // Festlegung wie die Werte gelesen werden soll (0=Hour, 1=Day, 2=Week, 3=Month, 4=Year), hat Vorrang gegenüber den Einstellungen in AggregatedValues
    //    wird kein AggType definiert werden alle gelogten Werte angezeigt
    // $serie['AggNameFormat'] = "d.m.Y H:i"; // (gilt nur bei den Pies, wenn eine Id verwendet wird), entspricht dem PHP-date("xxx") Format, welches das Format der Pie Namen festlegt, wenn keine Eingabe werden Default Werte genommen
    // $serie['Offset'] = 24*60*60; hiermit können Kurven unterschiedlicher Zeiträume in einem Chart dargestellt. Angabe ist in Minuten
    //    $serie['StartTime'] = mktime(0,0,0,1,1,2012);     // wird für die entsprechende Serie eine Anfangs- und/oder Endzeitpunkt festgelegt wird dieser verwendet. Ansonsten wird
    // $serie['EndTime'] = mktime(0,0,0,2,1,2012);          // der Zeitpunkt der Zeitpunkt aus den $CfgDaten genommen
    // $serie['ScaleFactor'] = 10; // Skalierungsfaktor mit welchem der ausgelesene Werte multipliziert wird
    // $serie['RoundValue'] = 1; // Anzahl der Nachkommastellen
    //    $serie['AggValue'] ='Min' // über AggValue kann Min/Max oder Avg vorgewählt werden (Default bei keiner Angabe ist Avg)
    //        ist sinnvoll wenn nicht Einzelwerte sondern Stundenwerte, Tageswerte, usw. ausgelesen werden
    // $serie['data'] = array('TimeStamp'=> time(),'Value'=12) // hier kann ein Array an eigenen Datenpunkten übergeben werden.
	 //        In diesem Fall werden für diese Serie keine Daten aus der Variable gelesenen.



/*    $serie = array();
    $serie['type'] = $chart_style;

	 /* wenn Werte für die Serie aus der geloggten Variable kommen : */
/*	 $serie['name'] = 'Aussen-Ostseite-Temperatur';
	 $serie['Unit'] = "°C";
    $serie['Id'] = 47591 ;

    /* oder wenn Daten selbst eingegeben werden : */
    	//$serie['data'][] = array('name'=>'Wohnzimmer-Temperatur', 'Id' => 43217, 'Unit'=>"°C");
    	//$serie['data'][] = array('name'=>'Wintergarten-Temperatur', 'Id' => 52093, 'Unit'=>"°C");
    	//$serie['data'][] = array('name'=>'Luftfeuchte', 'Id' => 17593, 'Unit'=>"%");

    //$serie['allowPointSelect'] = true;
    //$serie['cursor'] = 'pointer';
    //$serie['center'] = array(300,100);
    //$serie['size'] = 100;
/*    $serie['marker']['enabled'] = false;
    //$serie['dataLabels']['enabled'] = true;   /* zeigt jeden einzelnen Wert an */
/*    $CfgDaten['series'][] = $serie;

	/* Anzeige von Boolean Werten im Graphen als spezifische Werte */
   // $CfgDaten["Series"][] = array("Id"=>45373, "Name" =>"Licht","Unit"=>NULL, "ReplaceValues"=>array(0=>11,1=>14),
   //     "Param" =>"type:'line', step: true, yAxis: 0, shadow: true,lineWidth: 1, states: {hover:{lineWidth: 2}}, marker: { enabled: false, states: { hover: { enabled: true, symbol: 'circle', radius: 4, lineWidth: 1}}}");

/*	 $serie = array();
    $serie['name'] = "Wintergarten-Temperatur";
    $serie['Id'] = 52093 ;
/*    $serie['Unit'] = "°C";
    $serie['ReplaceValues'] = false;
    $serie['RoundValue'] = 0;
    $serie['type'] = $chart_style;
    $serie['yAxis'] = 0;
    $serie['marker']['enabled'] = false;
    $serie['shadow'] = true;
    $serie['lineWidth'] = 1;
    $serie['states']['hover']['lineWidth'] = 2;
    $serie['marker']['states']['hover']['enabled'] = true;
    $serie['marker']['states']['hover']['symbol'] = 'circle';
    $serie['marker']['states']['hover']['radius'] = 4;
    $serie['marker']['states']['hover']['lineWidth'] = 1;
    $CfgDaten['series'][] = $serie;

	 $serie = array();
    $serie['name'] = "Aussentemperatur";
    $serie['Id'] = 28314 ;
/*    $serie['Unit'] = "°C";
    $serie['type'] = $chart_style;
    $serie['marker']['enabled'] = false;
    $CfgDaten['series'][] = $serie;


    $serie = array();
    $serie['name'] = "Luftfeuchte";
    $serie['Id'] =  ;
    $serie['Unit'] = "%";
    $serie['ReplaceValues'] = false;
    $serie['type'] = "spline";
    $serie['step'] = false;
    $serie['yAxis'] = 1;
    $serie['shadow'] = true;
    $serie['lineWidth'] = 1;
    $serie['states']['hover']['lineWidth'] = 2;
    $serie['marker']['enabled'] = false;
    $serie['marker']['states']['hover']['enabled'] = true;
    $serie['marker']['states']['hover']['symbol'] = 'circle';
    $serie['marker']['states']['hover']['radius'] = 4;
    $serie['marker']['states']['hover']['lineWidth'] = 1;
    $CfgDaten['series'][] = $serie;
	*/


    //    $serie = array();
    //    $serie['name'] = "Drehgriffkontakt";
    //    $serie['Id'] = 44451;
    //    $serie['Unit'] = array(0=>'geschlossen', 1=>'gekippt', 2=>'geöffnet');
    //    $serie['ReplaceValues'] = array(0=>0.5, 1=>1, 2=>2);
    //    $serie['type'] = "line";
    //    $serie['step'] = true;
    //    $serie['yAxis'] = 2;
    //    $serie['shadow'] = true;
    //    $serie['lineWidth'] = 1;
    //    $serie['states']['hover']['lineWidth'] = 2;
    //    $serie['marker']['enabled'] = false;
    //    $serie['marker']['states']['hover']['enabled'] = true;
    //    $serie['marker']['states']['hover']['symbol'] = 'circle';
    //    $serie['marker']['states']['hover']['radius'] = 4;
    //    $serie['marker']['states']['hover']['lineWidth'] = 1;
    //    $CfgDaten['series'][] = $serie;

    //    $serie = array();
    //    $serie['name'] = "Column";
    //    $serie['Id'] = 29842;
    //    $serie['Unit'] = "kWh";
    //    $serie['ReplaceValues'] =false;
    //    $serie['type'] = "column";
    //    $serie['step'] = false;
    //    $serie['yAxis'] = 3;
    //    $serie['shadow'] = true;
    //    $serie['states']['hover']['lineWidth'] = 2;
    //    $serie['marker']['enabled'] = false;
    //    $serie['marker']['states']['hover']['enabled'] = true;
    //    $serie['marker']['states']['hover']['symbol'] = 'circle';
    //    $serie['marker']['states']['hover']['radius'] = 4;
    //    $serie['marker']['states']['hover']['lineWidth'] = 1;
    //    $CfgDaten['series'][] = $serie;


    // Highcharts-Theme
    //    $CfgDaten['HighChart']['Theme']="grid.js";   // von Highcharts mitgeliefert: dark-green.js, dark-blue.js, gray.js, grid.js
    $CfgDaten['HighChart']['Theme']="ips.js";   // IPS-Theme muss per Hand in in Themes kopiert werden....

    // Abmessungen des erzeugten Charts
    $CfgDaten['HighChart']['Width'] = 0;             // in px,  0 = 100%
    $CfgDaten['HighChart']['Height'] = 600;         // in px

  	// Create Chart with Config File
  	IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");

  	$CfgDaten    = CheckCfgDaten($CfgDaten);
  	$sConfig     = CreateConfigString($CfgDaten);
  	$tmpFilename = CreateConfigFile($sConfig, 'IPSPowerControl');
	if ($_IPS['SENDER']=="Execute")
		{
		echo "-----------------------------------------------------\n";
		echo "Debug Highchart Config-Daten:\n";
	  	print_r($CfgDaten);
  		echo "\n";
		echo "-----------------------------------------------------\n";
		echo "Filename : ".$tmpFilename."\n";
		$str2 = str_replace("\\n","\n",$sConfig);
		$str2 = str_replace("\\r","\r",$str2);
		$str2 = str_replace("\\t","\t",$str2);
		$str2 = preg_replace('/(,)([a-z])/','$1'."\n".'$2',$str2);
		$str2 = str_replace("[{","\n[\n{\n",$str2);
		$str2 = str_replace("}]","\n}\n]\n",$str2);
		$tmpFilename = IPS_GetKernelDir()."test.tmp" ;
		$handle = fopen($tmpFilename,"w");
		fwrite($handle, "*********************************************\n".$str2."\n\n\n");
		fclose($handle);
		}
  	WriteContentWithFilename ($CfgDaten, $tmpFilename);

	if ($_IPS['SENDER']=="Execute")
	   {
	  	echo "Filename fuer Aufruf :".$tmpFilename."\n";
	  	}

}  /* ende von komentierte if Execute */

?>
