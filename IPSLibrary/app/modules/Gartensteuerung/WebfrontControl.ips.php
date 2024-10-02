<?php

/*
	 * @defgroup Gartensteuerung
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 * Webfront Interface für Tastendrücke
	 *
	 * @file          Gartensteuerung.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

/************************************************************
 *
 * Webfront Aufruf
 *
 * Webfront Variablen erkennen und bearbeiten
 *
 * Besondere Funktion:
 *
 * wenn man bei EinmalEin nocheinmal die selbe Taste gedrückt wird der Giesskreis weitergeschaltet 
 *
 * Die Ansteuerung und Configuration erfolgt mit standardisierten Methoden. Es ist Homematic oder IPSHeat aktuell unterstützt.
 *
 * Giessmode, Zwei Betriebsarten:
 *
 * EinmalEin unterschiedlich ob Switch oder Auto Mode
 * Im SwitchMode kann der Giesskreis direkt selektiert werden
 *
 * Statistik
 *
 *
 *
 * DataQuality, Reportfunktionen:
 *
 * Es gibt Buttons untereinander, das sind die Konfigurationen/Dashboards. 
 * Die Auswahl welche Dashboards/Reports verfügbar sind erfolgt in der Konfiguration, Type und Name unterstützt bei Auswahl, Bezeichnung und Darstellung
 * Jeder Report hat Zusatzbefehle Rechts oben, und machmal gibt es auch eine Auswahlfunktion für den Zeitbereich.
 * 
 *
 ****************************************************************/

    ini_set('memory_limit', '1024M');                        // können grosse Dateien werden
    
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
    IPSUtils_Include ('IPSComponentSensor.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
    IPSUtils_Include ('IPSComponentSensor_Counter.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentSensor');
    IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');            

	IPSUtils_Include ('Gartensteuerung_Configuration.inc.php', 'IPSLibrary::config::modules::Gartensteuerung');
    IPSUtils_Include ('Gartensteuerung_Library.class.ips.php', 'IPSLibrary::app::modules::Gartensteuerung');

    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
    IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

    IPSUtils_Include ("EvaluateHardware_DeviceList.inc.php","IPSLibrary::config::modules::EvaluateHardware");              

	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
		}

    $installedModules   = $moduleManager->GetInstalledModules();
    if (isset($installedModules["OperationCenter"]))   
        {
        IPSUtils_Include ('OperationCenter_Library.class.php', 'IPSLibrary::app::modules::OperationCenter');            
        }   

    if ($_IPS['SENDER']=="Execute") $debug=true;            // Mehr Ausgaben produzieren
	else $debug=false;

    /******************************************************
     *
     *               Variablen initialisieren
     *               
     *************************************************************/

    $webOps = new webOps();

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');	
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
	$categoryId_Register    		= CreateCategory('Gartensteuerung-Register',   $CategoryIdData, 200);
    $categoryIdSelectReports        = CreateCategory('SelectReports',              $CategoryIdData, 300);
    $CategoryId_Statistiken			= CreateCategory('Statistiken',   $CategoryIdData, 200);

	$GiessAnlagePrevID 	= @IPS_GetVariableIDByName("GiessAnlagePrev",$categoryId_Register);
	$GiessCountID		= @IPS_GetVariableIDByName("GiessCount",$categoryId_Register);
	$GiessCountOffsetID	= @IPS_GetVariableIDByName("GiessCountOffset",$categoryId_Register);
	$GiessAnlageID		= @IPS_GetVariableIDByName("GiessAnlage",$categoryId_Gartensteuerung);
	$GiessKreisID		= @IPS_GetVariableIDByName("GiessKreis",$categoryId_Gartensteuerung);
	$GiessKreisInfoID	= @IPS_GetVariableIDByName("GiessKreisInfo",$categoryId_Gartensteuerung);
	$GiessTimeID	    = @IPS_GetVariableIDByName("GiessTime", $categoryId_Gartensteuerung); 
	$GiessPauseID 	    = @IPS_GetVariableIDByName("GiessPause",$categoryId_Register);
	$GiessTimeRemainID	= @IPS_GetVariableIDByName("GiessTimeRemain", $categoryId_Gartensteuerung); 

    $StatistikBox3ID			= @IPS_GetVariableIDByName("Regenereignisse" ,$CategoryId_Statistiken); 
    $UpdateRainEventsSelectorID = @IPS_GetVariableIDByName("UpdateRainEvents",$categoryIdSelectReports);            // Regenereignisse in Form bekommen

    $AnotherSelectorID 	= @IPS_GetVariableIDByName("AnotherSelector", $categoryIdSelectReports);            // Auswahl Darstellungsoptionen
    $ReportSelectorID   = @IPS_GetVariableIDByName("ReportSelector", $categoryIdSelectReports);            // Auswahl Periode für Darstellung

    //$PeriodeSelectorID  = @IPS_GetVariableIDByName("PeriodAndCount", $categoryIdSelectReports);            // Auswahl Periode für Darstellung
    $PeriodeSelectorID 	= $webOps->createNavigation($categoryIdSelectReports);            // Auswahl Dashboards

	$tableID	        = @IPS_GetVariableIDByName("Tabelle", $categoryIdSelectReports); 
	$table1ID	        = @IPS_GetVariableIDByName("Tabelle1", $categoryIdSelectReports);
	$table2ID	        = @IPS_GetVariableIDByName("Tabelle2", $categoryIdSelectReports);
	$chartID	        = @IPS_GetVariableIDByName("Chart",  $categoryIdSelectReports);
	$mapID	            = @IPS_GetVariableIDByName("GoogleMap", $categoryIdSelectReports);

    $WebConfigLinkIdsID 	= @IPS_GetVariableIDByName("WebConfigLinkIds", $categoryIdSelectReports); 
    //echo "Webfront LinkTable for hiding variables by hiding links:\n";
    $linkTableSummary = json_decode(GetValue($WebConfigLinkIdsID),true);
    foreach ($linkTableSummary as $parent => $linkTable) ;                          // linkTable extrahieren und hoffen es gibt nur einen Parent

	$GartensteuerungScriptID   		= IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);

    $dosOps = new dosOps();    
    $systemDir     = $dosOps->getWorkDirectory(); 

    $archiveOps = new archiveOps();
    $archiveHandlerID=$archiveOps->getArchiveID();

    $geoOps = new geoOps();
    $ipsTables = new ipsTables();

	$NachrichtenID  = IPS_GetCategoryIDByName("Nachrichtenverlauf-Gartensteuerung",$CategoryIdData);
    $NachrichtenInputID = IPS_GetVariableIDByName("Nachricht_Input",$NachrichtenID);
    $log_Giessanlage        = new Logging($systemDir."Log_Giessanlage2.csv",$NachrichtenInputID,IPS_GetName(0).";Gartensteuerung;");

	$timerDawnID = @IPS_GetEventIDByName("Timer3", $GartensteuerungScriptID);
	$UpdateTimerID = @IPS_GetEventIDByName("UpdateTimer", $GartensteuerungScriptID);

    $gartensteuerung = new Gartensteuerung();   // default, default, debug=false
    $GartensteuerungConfiguration =	$gartensteuerung->getConfig_Gartensteuerung();
    $configuration=$GartensteuerungConfiguration["Configuration"];                          // Abkürzung

    $gartensteuerungStatistics  = new GartensteuerungStatistics(false);   // debug=false
    $gartensteuerungMaintenance = new GartensteuerungMaintenance(false);   // debug=false

    // Schöne Karte der abgefragten Messpunkte zeichnen
	$modulhandling = new ModuleHandling();		// true bedeutet mit Debug
	$GMs=$modulhandling->getInstances('GoogleMaps');
	//print_r($GMs);
    $count=0;
    foreach ($GMs as $GoogleMapInstance)
        {
        if ($debug) echo "    ".$GoogleMapInstance."   ".IPS_GetName($GoogleMapInstance)." / ".IPS_GetName(IPS_GetParent($GoogleMapInstance))."\n";
        if ($count==0) $mapsID=$GoogleMapInstance;
        if ( ($count>0) && (IPS_GetParent($GoogleMapInstance)=="Startpage") ) $mapsID=$GoogleMapInstance;
        $count++;
        }
    if ($debug) echo "GoogleMaps Instance ($count): $mapsID \n";

    $oids = $modulhandling->getInstances('Location Control');           // jede IP Symcon Instanz hat ihre geografischen Ort hinterlegt, wenn der User es eingegeben hat
    $pos1=array();
    //$pos1[0]=["north"=>48.3806,"east"=>16.3056,"name"=>"Feldweg1"];
    //$pos1[1]=["north"=>48.2443,"east"=>16.3762,"name"=>"LorenzBoehlerGasse70"];
    foreach ($oids as $oid) $config=json_decode(IPS_GetConfiguration($oid),true);
    $pos=json_decode($config["Location"],true);            // als array
    //print_r($pos);
    $pos1[]=["north"=>$pos["latitude"],"east"=>$pos["longitude"],"name"=>IPS_GetName(0)];           // eigenen Standort verwenden

    $controlDataQuality=false;
    $useExistingData=false;

    if (strtoupper($GartensteuerungConfiguration["Configuration"]["DataQuality"])=="ENABLED") 
        {
        $associationsValues=$gartensteuerungMaintenance->getAssociations($GartensteuerungConfiguration["Configuration"]["Reports"]);
        }

    if ($debug) 
        {
        $controlDataQuality="TempTage";             // Simulate KeyPress  
        $controlDataQuality="RegenTage";             // Simulate KeyPress  
        }

    /* es kommt ein Webfront Tastendruck rein, hier bearbeiten
     * anhand der Variable iD bestimmen
     *  Giessanlage Auto, EinmalEin, Aus
     *  Giesskreis
     *
     *
     */

    if ($_IPS['SENDER']=="WebFront")
        {    
        $switchMode=false; $pauseTime=1;                                    //Defaultwerte
        if (isset ($configuration["PAUSE"])) $pauseTime=$configuration["PAUSE"]; 
        if ( (isset($configuration["Mode"])) && ($configuration["Mode"]=="Switch") ) $switchMode=true;
        SetValue($GiessPauseID,$pauseTime);
        //echo "PauseTime : ".$pauseTime;
        
        /* vom Webfront aus gestartet, folgende tasten werden unterstützt
        *      GiessAnlage     Giessanlage Betriebsart Umschaltung bearbeiten
        *      Giesskreis      durch Drücken der Giesskreis ID im Switch Mode den richtigen Giesskreis schalten
        */
        $samebutton=false;
        $variableID=$_IPS['VARIABLE'];
        switch ($variableID)
            {
            case $GiessAnlageID: 
                //echo "Giessanlage Betriebsart Umschaltung bearbeiten."; 
                $value=$_IPS['VALUE'];
                if (GetValue($variableID)==$value)
                    { /* die selbe Taste nocheinmal gedrückt */
                    $samebutton=true;
                    }
                else
                    {  /* andere Taste als vorher */
                    SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
                    SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
                    }
                switch ($_IPS['VALUE'])
                    {
                    case "2":  /* Auto */
                    case "-1":  /* Auto */
                        IPS_SetEventActive($UpdateTimerID,false);
                        IPS_SetEventActive($timerDawnID,true);
                        SetValue($GiessTimeRemainID ,0);				
                        $log_Giessanlage->LogMessage("Gartengiessanlage auf Auto gesetzt");
                        $log_Giessanlage->LogNachrichten("Gartengiessanlage auf Auto gesetzt");
                        $gartensteuerung->control_waterPump(false);                                     // sicherheitshalber hier immer nur ausschalten
                        //$failure=set_gartenpumpe(false);
                        //$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
                        /* Vorgeschichte egal, nur bei einmal ein wichtig */
                        SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
                        break;
                    case "1":  /* Einmal Ein */
                        // damit auch wenn noch kein Wetter zum Giessen, gegossen werden kann, Giesszeit manuell auf 10 setzen, Giesscount=1 oder bei nocheinmal drücken Giesscount++
                        if ($samebutton==true)
                            { // gleiche Taste heisst weiter, Giesscount++, Wasserpumpe ausschalten damit AUtúto weiterschaltet
                            SetValue($GiessTimeID,10);
                            SetValue($GiessTimeRemainID ,0);				
                            IPS_SetEventActive($UpdateTimerID,true);				
                            IPS_SetEventCyclicTimeBounds($UpdateTimerID,time(),0);  /* damit alle Timer gleichzeitig und richtig anfangen und nicht zur vollen Stunde */
                            IPS_SetEventActive($timerDawnID,false);
                            SetValue($GiessCountID,GetValue($GiessCountID)+1);
                            $log_Giessanlage->LogMessage("Gartengiessanlage Weiter geschaltet");
                            $log_Giessanlage->LogNachrichten("Gartengiessanlage Weiter geschaltet");
                            $gartensteuerung->control_waterPump(false);
                            //$failure=set_gartenpumpe(false);
                            //$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); // sicherheitshalber !!! 
                            }
                        else
                            { // oder wenn zum ersten mal, Aufruf der Giessfunktion, mit Pause beginnen, Start 
                            SetValue($GiessTimeID,10);
                            SetValue($GiessTimeRemainID ,0);				
                            IPS_SetEventActive($UpdateTimerID,true);				
                            IPS_SetEventCyclicTimeBounds($UpdateTimerID,time(),0);  /* damit alle Timer gleichzeitig und richtig anfangen und nicht zur vollen Stunde */
                            IPS_SetEventActive($timerDawnID,false);
                            SetValue($GiessCountID,1);
                            $log_Giessanlage->LogMessage("Gartengiessanlage auf EinmalEin gesetzt.");
                            $log_Giessanlage->LogNachrichten("Gartengiessanlage auf EinmalEin gesetzt.");
                            $gartensteuerung->control_waterPump(false);
                            //$failure=set_gartenpumpe(false);
                            //$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
                            }
                        break;
                    case "0":  /* Aus */
                        IPS_SetEventActive($timerDawnID,false);
                        IPS_SetEventActive($UpdateTimerID,false);				
                        SetValue($GiessCountID,0);
                        SetValue($GiessTimeRemainID ,0);				
                        $log_Giessanlage->LogMessage("Gartengiessanlage auf Aus gesetzt");
                        $log_Giessanlage->LogNachrichten("Gartengiessanlage auf Aus gesetzt");
                        $gartensteuerung->control_waterPump(false);
                        //$failure=set_gartenpumpe(false);
                        //$failure=HM_WriteValueBoolean($gartenpumpeID,"STATE",false); /* sicherheitshalber !!! */
                        /* Vorgeschichte egal, nur bei einmal ein wichtig */
                        SetValue($GiessAnlagePrevID,GetValue($GiessAnlageID));
                        break;
                    default:
                        break;	
                    } /* ende switch value */
                break;
            case $GiessKreisID:                                 // durch Drücken der Giesskreis ID im Switch Mode den richtigen Giesskreis schalten, Gartenpumpe muss bereits eingeschaltet sein (EinmalEin)
                $value=$_IPS['VALUE'];
                SetValue($_IPS['VARIABLE'],$value);				
                SetValue($GiessKreisInfoID,$GartensteuerungConfiguration["Configuration"]["KREIS".(string)GetValue($GiessKreisID)]);
                if ($switchMode)
                    {
                    $giessCount=$value*2;
                    SetValue($GiessCountID,$giessCount);
                    $gartensteuerung->control_waterValves($giessCount);                      // umrechnen auf fiktiven $GiessCount    
                    }
                break;
            case $UpdateRainEventsSelectorID:
                $regenereignis=array();
                $gartensteuerungStatistics->getRainEventsFromIncrements($regenereignis,[],false);             // regenereignis ist der Retourwert, false, true, 2 ist der Debugmode
                $html = $gartensteuerungStatistics->writeRainEventsHtml($regenereignis);
                SetValue($StatistikBox3ID,$html);
                break;
            case $AnotherSelectorID:                        // das ist der Selektor Rechts oben, macht unterschiedliches abhängig vom ausgewählten Dashboard/Report
                $value=$_IPS['VALUE'];
                SetValue($_IPS['VARIABLE'],$value);
                $controlDataQuality = GetValue($ReportSelectorID);	
                if ( ($controlDataQuality === false) || (in_array($controlDataQuality,$associationsValues)===false) )           // 0 ist möglich
                    {
                    echo "unknown $controlDataQuality ";
                    }
                else    
                    {
                    $useExistingData=true;
                    //echo "AnotherSelector $value for $controlDataQuality ";
                    }
                //$PeriodeSelectorID 	= $webOps->createNavigation($categoryIdSelectReports,$_IPS['SELF']);            // Auswahl für ein Dashboard, Periode bestimmen, Init Profile

                break; 
            case $PeriodeSelectorID:                // Periode Stunde - bis Jahr und plus/minus für jede Periode/Größenaordnung, siehe Report
                $value=$_IPS['VALUE'];
                $webOps->doNavigation($_IPS['VARIABLE'],$_IPS['VALUE']);            // übernimmt SetValue
                $controlDataQuality = GetValue($ReportSelectorID);	
                if ( ($controlDataQuality === false) || (in_array($controlDataQuality,$associationsValues)===false) )           // 0 ist möglich
                    {
                    echo "unknown $controlDataQuality ";
                    }
                else    
                    {
                    $useExistingData=true;
                    //echo "AnotherSelector $value for $controlDataQuality ";
                    }
                //echo "Navigation";
                break;
            default:
                SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);		
                break;
            }  /* ende switch variable ID */

        // DataQuality Buttons
        $buttonIds=false;
        if (strtoupper($GartensteuerungConfiguration["Configuration"]["DataQuality"])=="ENABLED") 
            {
            //$associationsValues=$gartensteuerungMaintenance->getAssociations($GartensteuerungConfiguration["Configuration"]["Reports"]);          // im Initbereich gesetzt
            $webOps->setSelectButtons($associationsValues,$categoryIdSelectReports);
            $buttonsId = $webOps->getSelectButtons(); 

            //echo $variableID;
            $selectButton=false;
            foreach ($buttonsId as $id => $button)
                {
                if ($variableID == $button["ID"]) 
                    { 
                    $selectButton=$id;  
                    $webOps->selectButton($id);             // umfärben wenn gedrückt wurde
                    //print_r($buttonsId[$id]);
                    break; 
                    }
                } 
            if ($selectButton !== false)            // wenn false wurde der Select Button nicht gedrückt, sondern irgendeine andere taste 
                {
                if (isset($associationsValues[$selectButton]))            // 0 ist möglich
                    {
                    //echo "selected Button : $selectButton ".$associationsValues[$selectButton]."\n";
                    $controlDataQuality=$associationsValues[$selectButton];
                    SetValue($ReportSelectorID,$controlDataQuality);                                    // nur setzen wenn bekannt  
                    }
                else 
                    {
                    echo "unknown $selectButton , ";           // sollte nicht ausgegeben werden
                    $controlDataQuality=false;
                    }
                }
            }        
        }           // Ende if Webfront Aufruf

    /* Abarbeitung der befehle die über das Webfront hereingekommen sind
     *
     */
    if ($controlDataQuality)            // zamg Reports mit statistischen Daten erzeugen
        {
        //echo "Aufruf controlDataQuality";
        ini_set('memory_limit', '1024M');                        // können grosse Dateien werden
        $archiveOps = new archiveOps();
        $zamg = new zamgApi();        
        $geoOps = new geoOps();
        $charts = new ipsCharts();
        //$pos1=array();
        // Feldweg 1   N48.3806, E16.3056 
        // Lorenz Böhler Gasse 6 N48.2443, E16.37622
        //$pos1[0]=["north"=>48.3806,"east"=>16.3056,"name"=>"Feldweg1"];
        //$pos1[1]=["north"=>48.2443,"east"=>16.3762,"name"=>"LorenzBoehlerGasse70"];
        $points=array();
        foreach ($pos1 as $index => $entry)
            {
            $ddgms=$geoOps->PosDDDegMinSec($entry);
            $points[$index]['lat']=$entry["north"];
            $points[$index]['lng']=$entry["east"];
            }    
        $module="/grid/historical/spartacus-v2-1m-1km";
        $config["StartTime"]=strtotime("1.1.1990");
        $config["Pos"]=$pos1;
        $config["Dif"]=0.007;           // 4 Punkte werden in der Umgebung gefunden
        $result = $zamg->getDataZamgApi(["RR","SA","TM"],$module, $config, false);           // true debug, RR,SA,TN,TX or TM
        //$useExistingData=true;    // Daten werden jedes mal neu geladen
        $pointGrid=$zamg->getPosOfGrid(false);          // false no Debug

        $map = [];
        $map['zoom'] = 11;          // 20  5 ist ganz Europa
        $map['size'] = '640x640';
        $map['scale'] = 1;
        $map['maptype'] = 'satellite';

        $map['restrict_points'] = false; // Anzahl der Punkte beschränken auf die zulässige Größe der URL
        $map['skip_points'] = 1; // nur jeden x'ten Punkt ausgeben, GoogleMap interpoliert

        $middle=round(sizeof($pointGrid)/2,0);
        $map['center'] = $pointGrid[$middle];

            $markers[] = [
                'color'     => '0x0000ff',
                'size'      => 'tiny',
                'points'    => $pointGrid,
            ];
            $markers[] = [
                'color'     => '0x00ff00',
                'size'      => 'tiny',
                'points'    => $points,
            ];
        $map['markers'] = $markers;

        $url = GoogleMaps_GenerateStaticMap($mapsID /* ID von GoogleMaps-Instanz */, json_encode($map));
        $html = '<img width="500", height="500" src="' . $url . '" />';
        SetValueString($mapID /* ID von HtmlBox-Variable */, $html);

        $zamg->getIndexNearPosOfGrid($pos1);            // erweitert $pos1
        $subindex=array();
        foreach ($pos1 as $id=>$pos) 
            {
            //echo " $id : ".str_pad($pos["name"],50)."   ".$pos["diff"]["subindex"]."  ".$pos["diff"]["min"]."\n";    
            $subindex[]=$pos["diff"]["subindex"];
            $index=$pos["diff"]["subindex"];    	        // wir haben nur eine Linie aber mehrere Punkte, schneller Workaround
            }
        $series = $zamg->getDataAsTimeSeries(["RR","SA","TM"],$subindex);

        switch ($controlDataQuality)
            {
            case "RegenMonat":
                if ($debug) echo "DataQuality Regenmonat:\n";        
                $pname="AnotherSelector";
                $tabs =  ["Update"];
                //$color = [0x481ef1,0xf13c1e,0x1ef127];
                $color = [0x481ef1];
                $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
                IPS_SetHidden($linkTable[$AnotherSelectorID],false);
                IPS_SetHidden($linkTable[$PeriodeSelectorID],true);

                $html = $gartensteuerung->writeOverviewMonthsHtml($series["RR"][$index],["mode"=>2,"type"=>"sum"]);          // sollte beide Formate können, mode=2 ist [TimeStamp/Value]
                $specialConf =  array (                 
                    "ZamgRegenDekaden"         => array(                         // unterschiedlicher Name erforderlich, so heisst das Highcharts Template pro Tabellenzelle
                            "Duration"     => (35*365*60*60*24),               // 10 Jahre     
                            "Size"      => "5x",  
                            "Style"     => "area", 
                            "Series"    => array(                           // mehrere Kurven in einem Chart
                                "Regen"      => array(
                                    "Index"     => "RR",                                // jeder Eintrag hat einen Index in den übergebenen Daten
                                    "Unit"      => "mm", 
                                            ),
                                        ),
                            "Step"      => "left",                          // do not spline the points, make steps
                            "Name"      => "Regen",
                            "Aggregate" => false,
                            "backgroundColor" => 0x050607,
                                    ) ,
                                                );
                $configArchive=array();
                $configArchive["DataType"]="Array";                    // muss übergeben werden, keine Autoerkennung hierfür
                $configArchive["EventLog"]=false;                          // keine Auswertung, nur Bloedsinn
                $configArchive["StartTime"]="1.1.1990";
                $result=$archiveOps->getValues($series["RR"][$index],$configArchive,false);
                $data=array();
                $data["RR"]=$result["Values"];
                $htmlc=$charts->createChartFromArray($chartID,$specialConf,$data,false);
                SetValue($chartID,$htmlc);
                //echo "update RegenMonat done";
                break;
            case "TempMonat":
                if ($debug) echo "DataQuality TempMonat:\n"; 
                $pname="AnotherSelector";
                $tabs =  ["WertImJahr","Jahresmittelwert","Monatswerte"];
                $color = [0x481ef1,0xf13c1e,0x1ef127];
                $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
                IPS_SetHidden($linkTable[$AnotherSelectorID],false);
                IPS_SetHidden($linkTable[$PeriodeSelectorID],true);

                $html = $gartensteuerung->writeOverviewMonthsHtml($series["TM"][$index],["mode"=>2,"type"=>"mean"]);          // sollte beide Formate können, mode=2 ist [TimeStamp/Value]
                $specialConf =  array (                 
                    "ZamgTempDekaden"         => array(                         // unterschiedlicher Name erforderlich, so heisst das Highcharts Template pro Tabellenzelle
                            "Duration"     => (35*365*60*60*24),               // 10 Jahre     
                            "Size"      => "5x",  
                            "Style"     => "area", 
                            "Series"    => array(                           // mehrere Kurven in einem Chart
                                "Temp"      => array(
                                    "Enable"    => false,
                                    "Index"     => "TM",                                // jeder Eintrag hat einen Index in den übergebenen Daten
                                    "Unit"      => "Degree",
                                            ),
                                "Mittel"      => array(
                                    "Enable"    => false,
                                    "Index"     => "MW",                                // jeder Eintrag hat einen Index in den übergebenen Daten
                                    "Unit"      => "Degree", 
                                            ),
                                "ImJahr"      => array(
                                    "Enable"    => false,
                                    "Index"     => "TM",                                // jeder Eintrag hat einen Index in den übergebenen Daten
                                    "Unit"      => "Degree",
                                    "MapData"   => "Year",                                         
                                        ),
                                    ),
                            "Step"      => "left",                          // do not spline the points, make steps
                            "Name"      => "Temperatur",
                            "Aggregate" => false,
                            "backgroundColor" => 0x050607,
                                    ) ,
                                                );
                $report = GetValueIfFormatted($AnotherSelectorID);          // verschiedene Darstellung im Chart, specialConf für createChartFromArray                                        
                switch ($report) 
                    {
                    case "Monatswerte":
                        $specialConf["ZamgTempDekaden"]["Series"]["Temp"]["Enable"]=true;
                        $specialConf["ZamgTempDekaden"]["Duration"]=(35*365*60*60*24);
                        unset($specialConf["ZamgTempDekaden"]["StartTime"]);
                        break;     
                    case "Jahresmittelwert":
                        $specialConf["ZamgTempDekaden"]["Series"]["Mittel"]["Enable"]=true;
                        $specialConf["ZamgTempDekaden"]["Duration"]=(35*365*60*60*24);
                        unset($specialConf["ZamgTempDekaden"]["StartTime"]);
                        break;  
                    case "WertImJahr":
                        $specialConf["ZamgTempDekaden"]["Series"]["ImJahr"]["Enable"]=true;
                        $specialConf["ZamgTempDekaden"]["Duration"]=(1*365*60*60*24);
                        $specialConf["ZamgTempDekaden"]["StartTime"]= "1.1.1990";
                        break;  
                    }                     

                $configArchive=array();
                $configArchive["DataType"]="Array";                    // muss übergeben werden, keine Autoerkennung hierfür
                $configArchive["EventLog"]=false;                          // keine Auswertung, nur Bloedsinn
                $configArchive["meansRoll"]["count"]=12;
                $configArchive["meansRoll"]["name"]="Jahresmittel";
                $configArchive["StartTime"]="1.1.1990";
                $result=$archiveOps->getValues($series["TM"][$index],$configArchive,false);
                //print_R($result);

                $data=array();
                //$data["TM"]=$series["TM"][86];
                $data["TM"]=$result["Values"];
                $data["MW"]=$result["MeansRoll"]["Jahresmittel"];
                $htmlc=$charts->createChartFromArray($chartID,$specialConf,$data,false);
                SetValue($chartID,$htmlc);

                break;        
            case "RegenTage":
                if (isset($configuration["Reports"]["RegenTage"]))
                    {
                    if ($debug) echo "Wir stellen zusätzliche Auswertungen zur Verfügung. Konfigurierbar ist :\n";
                    IPS_SetHidden($linkTable[$AnotherSelectorID],true);
                    IPS_SetHidden($linkTable[$PeriodeSelectorID],false);

                    $configRegenTage=$configuration["Reports"]["RegenTage"]["data"];
                    $startTime=Time()-$webOps->getNavPeriode($PeriodeSelectorID);
                    $config=array();
                    $config["StartTime"]=$startTime;                // kann Unixtime und string Time
                    $config["Warning"]=false;
                    $configCleanUpData = array();
                    $configCleanUpData["range"] = ["max" => 190, "min" => 0,];
                    $configCleanUpData["SuppressZero"]=true;
                    $configCleanUpData["deleteSourceOnError"]=true;             // true Fehler nicht in die Werte übernehmen
                    $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
                    $config["CleanUpData"] = $configCleanUpData;    
                    //$config["ShowTable"]["align"]="monthly";

                    $debug1=false;
                    $pos=array();
                    foreach ($configRegenTage as $entry)
                        {
                        //print_R($entry);
                        if (isset($entry["data"]))
                            {
                            if ($debug) echo "Read Data from ".$entry["name"]." of ".$entry["data"].":\n----------------------------------------\n";
                            $inputID=$entry["data"];
                            $config["OIdtoStore"]=$entry["OIDtoStore"];
                            $archiveOps->getValues($inputID,$config,$debug1);
                            }
                        elseif (isset($entry["increment"]))
                            {
                            if ($debug) echo "Read Increment from ".$entry["name"]." of ".$entry["increment"].":\n----------------------------------------\n";
                            $resultIncrement=$gartensteuerung->getConfig_raincounterID($entry["increment"]);            // neu initialisiseren, noch empty from remote fetch
                            $inputID=$resultIncrement["IncrementID"];
                            $config["OIdtoStore"]=$entry["OIDtoStore"];
                            $archiveOps->getValues($inputID,$config,$debug1);
                            }
                        elseif (isset($entry["zamg"]))
                            {
                            if ($debug) echo "Read zamg data from ".$entry["name"]." of ".json_encode($entry["pos"])."for ".json_encode($entry["zamg"]).":\n----------------------------------------\n";
                            $module=$entry["module"];
                            $dataset=$entry["zamg"];
                            //$config["StartTime"]=strtotime("1.1.2022");
                            $config["Dif"]=0.01;
                            $config["Pos"][]=$entry["pos"];
                            $result = $zamg->getDataZamgApi($dataset,$module, $config, $debug1);           // true debug, RR,SA,TN,TX or TM
                            $pos1=array();
                            $pos1[]=$entry["pos"];
                            $pos[]=$entry["pos"];           // alle Positionen abspeichern
                            $zamg->getIndexNearPosOfGrid($pos1,$debug1);           //true für Debug
                            //print_R($pos1);
                            $subindex=array();
                            foreach ($pos1 as $id=>$pos) 
                                {
                                //print_r($pos["diff"]);
                                if ($debug) echo " $id : ".str_pad($pos["name"],50)."   ".$pos["diff"]["subindex"]."  ".$pos["diff"]["min"]."\n";
                                $subindex[$id]=$pos["diff"]["subindex"];
                                }
                            //print_r($subindex);
                            if  ((count($subindex)==1) && (isset($subindex[0])) )
                                {
                                $series = $zamg->getDataAsTimeSeries(["RR"],$subindex);          // true Debug
                                $config["DataType"]="Array";
                                $config["OIdtoStore"]=$entry["OIDtoStore"];
                                $archiveOps->getValues($series["RR"][$subindex[0]],$config,false);
                                }
                            else print_r($subindex);
                            }
                        }

                    $config["ShowTable"]["output"]="realTable";                         // keine echo textausgabe mehr
                    $config["ShowTable"]["align"]="daily";                   // beinhaltet auch aggregate 
                    $result = $archiveOps->showValues(false,$config);

                    $display1=array();
                    $display1["TimeStamp"] = ["header"=>"Date","format"=>"Date"];
                    foreach ($configRegenTage as $index => $entry)
                        {
                        $display1[$entry["OIDtoStore"]]["format"]="mm";
                        $display1[$entry["OIDtoStore"]]["header"]=$entry["name"];
                        }

                    /*
                    $display = $ipsTables->checkDisplayConfig($ipsTables->getColumnsName($result,$debug),$debug);
                    $displayAlt = [
                                "TimeStamp"                        => ["header"=>"Date","format"=>"Date"],
                                "LBG70alt"                        => ["header"=>"lbgNetamo","format"=>"mm"],
                                "LBG70neu"                        => ["header"=>"lbgHomematic","format"=>"mm"],
                                "LorenzBoehlerGasse70"          => ["header"=>"lbg70-Gasse","format"=>"mm"],
                                "BKS01alt"         => ["header"=>"bksHMalt","format"=>"mm"],
                                "BKS01neu"         => ["header"=>"bksHMneu","format"=>"mm"],
                                "Feldweg1"                      => ["header"=>"bksFeldweg","format"=>"mm"],
                                ];   */

                    //print_R($display1); 
                    $config=array();
                    $config["html"]=true;
                    //$config["text"]=true;
                    $config["insert"]["Header"]    = true;
                    //$config["transpose"]=true;
                    $config["reverse"]=true;          // die Tabelle in die andere Richtung sortieren

                    $html = $ipsTables->showTable($result, $display1,$config,false);
                                SetValue($tableID, $html);
                                }
                    echo "done";
                break;
            case "TempTage":
                if ($debug) echo "DataQuality TempTage:\n";
                $transpose=true;
                $pname="AnotherSelector";
                $tabs =  ["Transpose"];
                //$color = [0x481ef1,0xf13c1e,0x1ef127];
                $color = [0x481ef1];
                $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
                IPS_SetHidden($linkTable[$AnotherSelectorID],false);
                IPS_SetHidden($linkTable[$PeriodeSelectorID],false);

                $rainRegs=$gartensteuerung->getRainRegisters();
                $variableTempID   = $configuration["AussenTemp"];
                $variableTempName = IPS_GetName($variableTempID);

                //$startTime=time()-60*60*24*20;  /* die letzten 20 Tage, für Auswertung nebeneinander */
                $periode=$webOps->getNavPeriode($PeriodeSelectorID);
                if ($periode>(20*24*60*60)) $transpose=false;
                $startTime=time()-$periode;

                // Regenwerte
                $config=array();
                $config["StartTime"]=$startTime;
                $configCleanUpData = array();
                $configCleanUpData["range"] = ["max" => 60, "min" => 0,];
                $configCleanUpData["SuppressZero"]=true;
                $configCleanUpData["deleteSourceOnError"]=true;             // true Fehler nicht in die Werte übernehmen
                $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
                //$config["Aggregated"]="daily";                     // verwendet archivierte Daten stt manuell zu integrieren, zu viele Daten, zusammenfassen, 0 stündlich, 1 täglich, 2 wöchentlich
                $config["OIdtoStore"]="Rain";             // oid wird auch ausserhalb geändert
                //$config["manAggregate"]="daily";                // aggregiert geloogte Werte ohne Aggregation manuell
                $config["CleanUpData"] = $configCleanUpData;    
                //$config["ShowTable"]["align"]="minutely";    
                $archiveOps->getValues($rainRegs["IncrementID"],$config,$debug);          // true,2 Debug, Werte einlesen

                //Temperaturwerte
                $config=array();
                $config["StartTime"]=$startTime;
                $config["OIdtoStore"]="Temp";             // oid wird auch ausserhalb geändert
                $config["Aggregated"]="daily";                     // verwendet archivierte Daten statt manuell zu integrieren, zu viele Daten, zusammenfassen, 0 stündlich, 1 täglich, 2 wöchentlich
                $config["ShowTable"]["align"]="daily";
                $archiveOps->getValues($variableTempID,$config,$debug);          // true,2 Debug, Werte einlesen

                // Zusammenfassen
                $config["AggregatedValue"]=["Avg","Min","MinTime","Max","MaxTime"];                   // es werden immer alle Werte eingelesen
                $config["ShowTable"]["output"]="realTable";
                $result = $archiveOps->showValues(false,$config);

                $display = $ipsTables->checkDisplayConfig($ipsTables->getColumnsName($result,false),false);

                $display = [
                    "TimeStamp"                    => ["header"=>"Date",        "format"=>"DayMonth"],
                    "Temp"                        => ["header"=>"MittelTemp",  "format"=>"°"],
                    "TempMin"                     => ["header"=>"MinTemp",     "format"=>"°"],
                    "TempMinTime"                 => ["header"=>"MinTime",     "format"=>"HourMin"],
                    "TempMax"                     => ["header"=>"MaxTemp",     "format"=>"°"],
                    "TempMaxTime"                 => ["header"=>"MaxTime",     "format"=>"HourMin"],
                    "Rain"                        => ["header"=>"Regen",       "format"=>"mm"],
                            ];
                $config=array();
                $config["html"]=true;
                $config["text"]=false;
                $config["insert"]["Header"]    = true;
                $config["transpose"]=$transpose;
                $config["reverse"]=true;          // die Tabelle in die andere Richtung sortieren
                ksort($result);
                $html = $ipsTables->showTable($result, $display,$config,false);     // true/2 für debug , braucht einen Zeilenindex
                SetValue($tableID, $html);
                break;
            case "GeoSphere":
                if (isset($configuration["Reports"]["GeoSphere"]))          // Index ist jetzt der Type
                    {
                    //echo "Geosphere Type hier duchführen:\n";
                    if (isset($configuration["Reports"]["GeoSphere"]["Pos"])) 
                        {
                        //echo "Positionen zum Auswählen machen, sonst default Position aus dem System.\n";
                        $positionen=$configuration["Reports"]["GeoSphere"]["Pos"];
                        }
                    else 
                        {
                        //print_r($configuration["Reports"]["GeoSphere"]);
                        $positionen=$pos1;
                        }
                    //print_r($configuration["Reports"]["GeoSphere"]["Pos"]);
                    } 
                $tabs=array();
                foreach ($positionen as $index => $entry) $tabs[]=$entry["name"];
                //print_R($tabs);
                $pname="AnotherSelector";
                $color = [0x481ef1,0xf13c1e,0x1ef127];
                $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
                        
                IPS_SetHidden($linkTable[$AnotherSelectorID],false);
                IPS_SetHidden($linkTable[$PeriodeSelectorID],false);

                $periode=$webOps->getNavPeriode($PeriodeSelectorID);
                if ($periode>(20*24*60*60)) $transpose=false;
                $startTime=time()-$periode;

                $module="/grid/historical/spartacus-v2-1d-1km";             // verfügbare Module siehe vorherige Routine

                $config=array();
                $config["StartTime"]=$startTime;
                $config["Dif"]=0.04;
                $config["Pos"][0]=$positionen[(integer)GetValue($AnotherSelectorID)];                   // es braucht einen Index, auch wenn es nur eine Position ist
                $result = $zamg->getDataZamgApi(["RR","SA","TN","TX"],$module, $config, false);           // true debug, RR,SA,TN,TX or TM
                //$zamg->showData();                // ur für Debug, keine zusätzliche funktion

                $zamg->getIndexNearPosOfGrid($positionen);            // erweitert $pos1
                $subindex=array();
                foreach ($positionen as $id=>$pos) 
                    {
                    //echo " $id : ".str_pad($pos["name"],50)."   ".$pos["diff"]["subindex"]."  ".$pos["diff"]["min"]."\n";    
                    $subindex[]=$pos["diff"]["subindex"];
                    $index=$pos["diff"]["subindex"];
                    }
                $series = $zamg->getDataAsTimeSeries(["RR","SA","TN","TX"],$subindex);      // keine mittlere Temperatur bei Tageswerten nur min und Max ohne Zeitstempel

                $debug=false;
                $config=array();    
                $config["AggregatedValue"]=["Avg","Min","MinTime","Max","MaxTime"];                   // es werden immer alle Werte eingelesen
                $config["ShowTable"]["output"]="realTable";
                $config["OIdtoStore"]="TN";             // oid wird auch ausserhalb geändert
                $archiveOps->quickStore($series["TN"],$config,$debug);               // muss das richtige Format haben
                $config["OIdtoStore"]="TX";             // oid wird auch ausserhalb geändert
                $archiveOps->quickStore($series["TX"],$config,$debug);               // muss das richtige Format haben
                $config["OIdtoStore"]="RR";             // oid wird auch ausserhalb geändert
                $archiveOps->quickStore($series["RR"],$config,$debug);               // muss das richtige Format haben
                $result = $archiveOps->showValues(false,$config,$debug);         //true für debug

                $display = $ipsTables->checkDisplayConfig($ipsTables->getColumnsName($result,$debug),$debug);
                //print_R($display);

                $display = [
                            "TimeStamp"                        => ["header"=>"Date","format"=>"DayMonth"],
                            "TN"                        => ["header"=>"MinTemp","format"=>"°"],
                            "TX"                        => ["header"=>"MaxTemp","format"=>"°"],
                            "RR"                        => ["header"=>"Regen","format"=>"mm"],
                            ];
                $config=array();
                $config["html"]=true;
                //$config["text"]=true;             // sonst zusätzlich als echo
                $config["insert"]["Header"]    = true;
                $config["transpose"]=false;
                $config["reverse"]=true;          // die Tabelle in die andere Richtung sortieren
                ksort($series);    
                //echo "-----------------\n";
                $html = $ipsTables->showTable($result, $display,$config,false);     // true/2 für debug , braucht einen Zeilenindex
                SetValue($table2ID, $html);

                //echo "ask zamg for place ".GetValueIfFormatted($AnotherSelectorID)." and for periode ".nf($periode,"sec");
                break;
            case "DataQuality":   
                $html=$gartensteuerungStatistics->showDataQualityRegs();         
                SetValue($table2ID,$html);  
                break; 
            case "Maintenance";                 // Überblick bekommen über Regenregister
                $resultRain=$gartensteuerungMaintenance->getRainRegsFromComponent();
                $count=(sizeof($resultRain));
                $html='<table frameborder="1" width="100%">';
                $unreferenced=false;
                foreach ($resultRain as $name => $entry)
                    {
                    $log = new Counter_Logging($entry["COID"],null,$entry["KEY"]);
                    if ($unreferenced==false)
                        {
                        $CounterAuswertungID = $log->getCategoryAuswertung();
                        $childs = IPS_GetChildrenIDs($CounterAuswertungID);
                        $unreferenced=array();
                        foreach ($childs as $child) $unreferenced[$child]=$child;
                        }
                    $html .= '<tr style="background-color:blue;color:white;"><td>'.IPS_GetName($entry["COID"])."</td><td>".$entry["COID"]."</td></tr>";         // Leerzeile zur Trennung
                    $html .= "<tr><td>Source Variable Id       </td><td>".$log->getVariableID()."</td><td>".str_pad(IPS_GetName($log->getVariableID())."/".IPS_GetName(IPS_GetParent($log->getVariableID())),70)."</td><td>".$archiveOps->getStatus($entry["COID"])."</td></tr>";
                    $html .= "<tr><td>Increment Log Variable Id</td><td>".$log->getVariableLogID()."</td><td>".str_pad(IPS_GetName($log->getVariableLogID())."/".IPS_GetName(IPS_GetParent($log->getVariableLogID())),70)."</td><td>".$archiveOps->getStatus($entry["COID"])."</td></tr>";
                    $html .= "<tr><td>Counter Log Variable Id  </td><td>".$log->getCounterLogID()."</td><td>".str_pad(IPS_GetName($log->getCounterLogID())."/".IPS_GetName(IPS_GetParent($log->getCounterLogID())),70)."</td><td>".$archiveOps->getStatus($entry["COID"])."</td></tr>";
                    if (isset($unreferenced[$log->getVariableLogID()])) unset($unreferenced[$log->getVariableLogID()]);
                    if (isset($unreferenced[$log->getCounterLogID()])) unset($unreferenced[$log->getCounterLogID()]);
                    }
                $html .= '<tr style="background-color:blue;color:white;"><td>Targetspeicher</td></tr>';         // Leerzeile zur Trennung
                $oid = CreateVariableByName($CounterAuswertungID, "Wetterstation", 2, "~Rainfall", false, 900, false, false);	        // Position ganz hinten, nur Custom profiles
                if (isset($unreferenced[$oid])) unset($unreferenced[$oid]);
                //$componentHandling->setLogging($oid);         // Teil der Config
                $html .= "<tr><td>Target Variable Id       </td><td>".$oid."</td><td>".str_pad(IPS_GetName($oid)."/".IPS_GetName(IPS_GetParent($oid)),70)."</td><td>".$archiveOps->getStatus($oid)."</td></tr>";
                $html .= '<tr style="background-color:blue;color:white;"><td>Maintain following ones</td></tr>';         // Leerzeile zur Trennung
                foreach ($unreferenced as $childID)
                    {
                    //if ( ($childID != $log->getVariableID()) && ($childID !=$log->getVariableLogID()) && ($childID != $log->getCounterLogID()) && ($childID !=$oid) )
                    $html .= "<tr><td>unreferenced Id  </td><td>".str_pad("$childID",10)."</td><td>".str_pad(IPS_GetName($childID),50)."</td><td>".$archiveOps->getStatus($childID)."</td></tr>";              // wir haben strict, also int in string wandeln
                    }

                //siehe Regenermittlung für letzte Version    
                
                $archiveHandlerID=$archiveOps->getArchiveID();

                //$variableId=$entry["COID"];
                $variableId = $log->getVariableID();                // direkt die Variable aus der Hardware, bei HomematicIP ein Counter
                $variable1Id = $log->getVariableLogID();            // der Delta, Increment Wert
                //$variable2Id = $log->getCounterLogID();             // der Counter Wert
                $variable2Id = $oid;

                //echo "Hardware Source, Counter     $variableId  \n";        // and others, not relevant, but for overview of data quality
                //echo "Register Source, Increment   $variable1Id \n";
                //echo "Target Register, Increment   $variable2Id \n";
                $config=array();
                $config["StartTime"]="1.7.2024";
                $config["Integrate"]=true;                          // das ist ein Counter
                $configProcessOnData = ["Delta"=>true];
                $config["ProcessOnData"] = $configProcessOnData;                 
                $configCleanUpData = array();
                $configCleanUpData["range"] = ["max" => 60, "min" => 0,];
                $configCleanUpData["deleteSourceOnError"]=true;             // das array wird gelöscht aber nicht das Archive
                $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
                $config["ShowTable"]["align"]="minutely";    
                $config["CleanUpData"] = $configCleanUpData;   
                $config["OIdtoStore"]="Hardware";   
                $result = $archiveOps->getValues($variableId,$config,$debug);          // true,2 Debug, Werte einlesen
                //foreach ($result as $index => $entry) echo "$index   \n";
                if ($debug && (isset($result["CleanUp"]["delete"])))
                    {
                    $countDel=count($result["CleanUp"]["delete"]);
                    echo "We found $countDel CleanUp issues:\n";
                    //print_r($result["CleanUp"]["delete"]);
                    $deleteMax=round($countDel*0.1,0); $i=1;
                    if ($deleteMax<2) $deleteMax=2; 
                    foreach ($result["CleanUp"]["delete"] as $index => $entry)
                        {
                        echo $entry."  ".date("d.m.Y H:i:s",$entry)." Delete Value in Archive\n";
                        //AC_DeleteVariableData ($archiveHandlerID,12713,$entry,$entry);
                        if ($i++>=$deleteMax) break;
                        }
                    }

                //echo "get VariableID from latest Logging Instance operated: $variable1Id ".str_pad(IPS_GetName($variable1Id)."/".IPS_GetName(IPS_GetParent($variable1Id)),70)."   ".$archiveOps->getStatus($variable1Id)."\n";
                $config["Integrate"]=false;                          // das sind Deltawerte 
                $configProcessOnData = array();
                $config["ProcessOnData"] = $configProcessOnData; 
                $configCleanUpData["deleteSourceOnError"]=true;
                $config["CleanUpData"] = $configCleanUpData;    
                $config["OIdtoStore"]="Source";        
                $result = $archiveOps->getValues($variable1Id,$config,$debug);          // true,2 Debug, Werte einlesen
                
                //$config["Integrate"]=true;                          // das ist ein Counter
                //$configProcessOnData = ["Delta"=>true];
                //$config["ProcessOnData"] = $configProcessOnData;        
                $config["OIdtoStore"]="Target"; 
                $result = $archiveOps->getValues($variable2Id,$config,$debug);          // true,2 Debug, Werte einlesen

                $config["ShowTable"]["output"]="realTable";                              // alles was nicht realTable ist produziert ein echo
                // showValues produziert auch Werte die addiert werden müssen
                $result = $archiveOps->showValues(false,$config);                   // true Debug

                $display = $ipsTables->checkDisplayConfig($ipsTables->getColumnsName($result,false),false);
                //print_r($display);

                $display = [
                    "TimeStamp"                    => ["header"=>"TimeStamp",        "format"=>"DateTime"],                     // DayMonth
                    "Hardware"                        => ["header"=>"Hardware",  "format"=>"mm"],
                    "Source"                     => ["header"=>"Source",     "format"=>"mm"],
                    "Target"                 => ["header"=>"Target",     "format"=>"mm"],
                            ];

                $config=array();
                $config["html"]=true;
                $config["text"]=false;
                $config["insert"]["Header"]    = true;
                //$config["transpose"]=true;
                //$config["reverse"]=true;          // die Tabelle in die andere Richtung sortieren
                ksort($result);
                $html1 = "";
                $html1 .= $ipsTables->showTable($result, $display,$config,false);     // true/2 für debug , braucht einen Zeilenindex


                if ($debug)        // Are there Registers to add
                    {
                    $targetAdd=$variable2Id; $sourceAdd=$variable1Id;
                    echo "Registers to add at $targetAdd \n";
                    if (isset($result["add"][$targetAdd]))
                        {
                        foreach ($result["add"][$targetAdd] as $pull => $entry)
                            {
                            echo $pull."\n";
                            foreach ($entry as $index => $wert)
                                {
                                echo "   ".str_pad("$index",10).date("d.m.Y H:i:s",$wert['TimeStamp']).nf($wert["Value"],"",20)."\n";
                                }
                            if ($pull==$sourceAdd) AC_AddLoggedValues($archiveHandlerID,$targetAdd,$result["add"][$targetAdd][$sourceAdd]);
                            }
                        }        
                    }                                                                   
                            // $tableID wird immer geschrieben, die anderen sind optional 
                SetValue($table1ID,$html1); 
                SetValue($table2ID,""); 
                SetValue($chartID,"");
                SetValue($mapID,"");
                break;
            }
        SetValue($tableID,$html);
        }               // Ende Abarbeitung DataQuality Tab

    /* zusätzliche Funktionen, wenn direkt aufgerufen
     *
     *
     */
    if ( ($_IPS['SENDER']=="Execute") && false)
        {
        $buttonIds=false;
        if (strtoupper($GartensteuerungConfiguration["Configuration"]["DataQuality"])=="ENABLED") 
            {
            echo "Buttons zur Anzeige von historischen Daten:\n";
            $gartensteuerungReports=$GartensteuerungConfiguration["Configuration"]["Reports"];
            $count=0;
            $associationsValues = array();
            foreach ($gartensteuerungReports as $displaypanel=>$values)
                {
                echo "     Profileintrag $count : ".$displaypanel."  \n";
                $associationsValues[$count]=$displaypanel;
                $count++;
                }        
            $webOps = new webOps(true);
            $webOps->setSelectButtons($associationsValues,$categoryIdSelectReports);
            $buttonsId = $webOps->getSelectButtons(); 
            print_r($buttonsId);         
            $selectButton=false;
            foreach ($buttonsId as $id => $button)
                {
                //if ($variableID == $button["ID"]) 
                    { 
                    $selectButton=$id;  
                    $webOps->selectButton($id);             // umfärben wenn gedrückt wurde
                    break; 
                    }
                } 

            /* alternative Datenermittlung aus Klimatabellen ZAMG
            * TAWES, sind die tatsächlichen Stationen, zu ungenau für die Ermittlung von tatsächlichen Niederschlägen
            * https://data.hub.geosphere.at/dataset/spartacus-v2-1m-1km   API Beschreibung Monatswerte, diesmal mit einem Temperaturmittelwert
            * 
            */
            ini_set('memory_limit', '1024M');                        // können grosse Dateien werden
                IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");
            IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");
            $debug=false;
            $zamg = new zamgApi();
            if ($debug) 
                {
                $modules=$zamg->getAvailableModules();
                //print_R($modules);
                echo "available Modules:\n";
                ksort ($modules);
                foreach ($modules as $index => $entry) echo "  $index \n";
                }
            //$module="/grid/historical/spartacus-v2-1d-1km";
            $module="/grid/historical/spartacus-v2-1m-1km";
            echo "read this module : $module \n";
            $config["StartTime"]=strtotime("1.1.1990");
            $result = $zamg->getDataZamgApi(["RR","SA","TM"],$module, $config, true);           // true debug, RR,SA,TN,TX or TM
            $zamg->showData();
            //echo $result;

            // Feldweg 1   N48.3806, E16.3056 
            // Lorenz Böhler Gasse 6 N48.2443, E16.37622
            $pos1[0]=["north"=>48.3806,"east"=>16.3056,"name"=>"Feldweg1"];
            $pos1[1]=["north"=>48.2443,"east"=>16.3762,"name"=>"LorenzBoehlerGasse70"];
            
            echo "getIndex of features Data:\n";
            $zamg->getIndexNearPosOfGrid($pos1);
            //print_R($pos1);
            $subindex=array();
            foreach ($pos1 as $id=>$pos) 
                {
                echo " $id : ".str_pad($pos["name"],50)."   ".$pos["diff"]["subindex"]."  ".$pos["diff"]["min"]."\n";    
                $subindex[]=$pos["diff"]["subindex"];
                }
            //print_r($subindex);
            $series = $zamg->getDataAsTimeSeries(["RR","SA","TM"],$subindex);
            //print_r($series);
            $archiveOps = new archiveOps();
            if (false)
                {
                echo "Use ArchiveOps to show:\n";
                $archiveOps->quickStore($series["TM"]);               // muss das richtige Format haben
                $archiveOps->showValues(false,[],true);         //true für debug
                }
            echo "---\n";    //print_R($series["TM"][60]);         // Input für Ausgabe Tabelle Index ist MM.YY
            echo $gartensteuerungStatistics->writeOverviewMonthsHtml($series["TM"][86],["mode"=>2,"type"=>"mean"]);          // sollte beide Formate können, mode=2 ist [TimeStamp/Value]
            }               // ende Dataquality Tab enabled
        }               // ende Execute Mode

?>