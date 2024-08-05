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
 * Variablen erkennen und bearbeiten
 *
 * Besondere Funktion:
 *
 * wenn man bei EinmalEin nocheinmal die selbe Taste gedrückt wird der Giesskreis weitergeschaltet 
 *
 * Die Ansteuerung und Configuration erfolgt mit standardisierten Methoden. Es ist Homematic oder IPSHeat aktuell unterstützt.
 *
 * Zwei Betriebsarten:
 *
 * EinmalEin unterschiedlich ob Switch oder Auto Mode
 * Im SwitchMode kann der Giesskreis direkt selektiert werden
 *
 ****************************************************************/

    ini_set('memory_limit', '1024M');                        // können grosse Dateien werden
    
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	
    IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
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

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');	
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
	$categoryId_Register    		= CreateCategory('Gartensteuerung-Register',   $CategoryIdData, 200);
    $categoryIdSelectReports        = CreateCategory('SelectReports',              $CategoryIdData, 300);

	$GiessAnlagePrevID 	= @IPS_GetVariableIDByName("GiessAnlagePrev",$categoryId_Register);
	$GiessCountID		= @IPS_GetVariableIDByName("GiessCount",$categoryId_Register);
	$GiessCountOffsetID	= @IPS_GetVariableIDByName("GiessCountOffset",$categoryId_Register);
	$GiessAnlageID		= @IPS_GetVariableIDByName("GiessAnlage",$categoryId_Gartensteuerung);
	$GiessKreisID		= @IPS_GetVariableIDByName("GiessKreis",$categoryId_Gartensteuerung);
	$GiessKreisInfoID	= @IPS_GetVariableIDByName("GiessKreisInfo",$categoryId_Gartensteuerung);
	$GiessTimeID	    = @IPS_GetVariableIDByName("GiessTime", $categoryId_Gartensteuerung); 
	$GiessPauseID 	    = @IPS_GetVariableIDByName("GiessPause",$categoryId_Register);
	$GiessTimeRemainID	= @IPS_GetVariableIDByName("GiessTimeRemain", $categoryId_Gartensteuerung); 

    $AnotherSelectorID 	= @IPS_GetVariableIDByName("AnotherSelector", $categoryIdSelectReports);
    $ReportSelectorID 	= @IPS_GetVariableIDByName("ReportSelector",  $categoryIdSelectReports);

	$tableID	        = @IPS_GetVariableIDByName("Tabelle", $categoryIdSelectReports); 
	$table1ID	        = @IPS_GetVariableIDByName("Tabelle1", $categoryIdSelectReports);
	$table2ID	        = @IPS_GetVariableIDByName("Tabelle2", $categoryIdSelectReports);
	$chartID	        = @IPS_GetVariableIDByName("Chart",  $categoryIdSelectReports);
	$mapID	            = @IPS_GetVariableIDByName("GoogleMap", $categoryIdSelectReports);

	$GartensteuerungScriptID   		= IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);

    $dosOps = new dosOps();    
    $systemDir     = $dosOps->getWorkDirectory(); 

    $archiveOps = new archiveOps();
    $archiveHandlerID=$archiveOps->getArchiveID();

    $webOps = new webOps();
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

    $gartensteuerungStatistics = new GartensteuerungStatistics(false);   // debug=false

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

    if ($debug) 
        {
        $controlDataQuality="TempTage";             // Simulate KeyPress  
        }

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
        case $AnotherSelectorID:
            $value=$_IPS['VALUE'];
			SetValue($_IPS['VARIABLE'],$value);
            $controlDataQuality = GetValue($ReportSelectorID);				
            $useExistingData=true;
            break;    
		default:
			SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);		
			break;
		}  /* ende switch variable ID */

    // DataQuality Buttons
    $buttonIds=false;
    if (strtoupper($GartensteuerungConfiguration["Configuration"]["DataQuality"])=="ENABLED") 
        {
        $gartensteuerungReports=$GartensteuerungConfiguration["Configuration"]["Reports"];
        $count=0;
        $associationsValues = array();
        foreach ($gartensteuerungReports as $displaypanel=>$values)
            {
            $associationsValues[$count]=$displaypanel;
            $count++;
            }        

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
        if ($selectButton !== false)            // 0 ist möglich
            {
            //echo $selectButton;
            switch ($selectButton)
                {
                case 0:
                    $controlDataQuality="RegenMonat";
                    break;
                case 1:
                    $controlDataQuality="TempMonat";
                    break;   
                case 2:
                    $controlDataQuality="RegenTage";
                    break;   
                case 3:
                    $controlDataQuality="TempTage";
                    break;   
                case 4:
                    $controlDataQuality="DataQuality";
                    break;
                default:
                    echo "unknown";
                    break;             
                }
            }
        SetValue($ReportSelectorID,$controlDataQuality);    
        }        

	}
if ($controlDataQuality)            // zamg Reports mit statistischen Daten erzeugen
    {
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
            break;
        case "TempMonat":
            if ($debug) echo "DataQuality TempMonat:\n"; 
            $pname="AnotherSelector";
            $tabs =  ["WertImJahr","Jahresmittelwert","Monatswerte"];
            $color = [0x481ef1,0xf13c1e,0x1ef127];
            $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor

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
            $report = GetValueIfFormatted($AnotherSelectorID);                                              
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
                echo "Wir stellen zusätzliche Auswertungen zur Verfügung. Konfigurierbar ist :\n";
                $configRegenTage=$configuration["Reports"];

                $config=array();
                $config["StartTime"]="1.6.2023";
                $configCleanUpData = array();
                $configCleanUpData["range"] = ["max" => 190, "min" => 0,];
                $configCleanUpData["SuppressZero"]=true;
                $configCleanUpData["deleteSourceOnError"]=true;             // true Fehler nicht in die Werte übernehmen
                $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
                $config["CleanUpData"] = $configCleanUpData;    
                //$config["ShowTable"]["align"]="monthly";

                $pos=array();
                foreach ($configRegenTage["RegenTage"] as $entry)
                    {
                    if (isset($entry["data"]))
                        {
                        //echo "Read Data from ".$entry["name"]." of ".$entry["data"].":\n----------------------------------------\n";
                        $inputID=$entry["data"];
                        $config["OIdtoStore"]=$entry["OIdtoStore"];
                        $archiveOps->getValues($inputID,$config,$debug);
                        }
                    elseif (isset($entry["increment"]))
                        {
                        //echo "Read Increment from ".$entry["name"]." of ".$entry["increment"].":\n----------------------------------------\n";
                        $resultIncrement=$gartensteuerung->getConfig_raincounterID($entry["increment"]);            // neu initialisiseren, noch empty from remote fetch
                        $inputID=$resultIncrement["IncrementID"];
                        $config["OIdtoStore"]=$entry["OIdtoStore"];
                        $archiveOps->getValues($inputID,$config,$debug);
                        }
                    elseif (isset($entry["zamg"]))
                        {
                        //echo "Read zamg data from ".$entry["name"]." of ".json_encode($entry["pos"])."for ".json_encode($entry["zamg"]).":\n----------------------------------------\n";
                        $module=$entry["module"];
                        $dataset=$entry["zamg"];
                        //$config["StartTime"]=strtotime("1.1.2022");
                        $config["Dif"]=0.01;
                        $config["Pos"][]=$entry["pos"];
                        $result = $zamg->getDataZamgApi($dataset,$module, $config, $debug);           // true debug, RR,SA,TN,TX or TM
                        $pos1=array();
                        $pos1[]=$entry["pos"];
                        $pos[]=$entry["pos"];           // alle Positionen abspeichern
                        $zamg->getIndexNearPosOfGrid($pos1,$debug);           //true für Debug
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
                            $config["OIdtoStore"]=$entry["OIdtoStore"];
                            $archiveOps->getValues($series["RR"][$subindex[0]],$config,false);
                            }
                        else print_r($subindex);
                        }
                    }

                $config["ShowTable"]["output"]="realTable";                         // keine echo textausgabe mehr
                $config["ShowTable"]["align"]="daily";                   // beinhaltet auch aggregate 
                $result = $archiveOps->showValues(false,$config);


                $display = $ipsTables->checkDisplayConfig($ipsTables->getColumnsName($result,$debug),$debug);
                //print_R($display);
                // Noch nicht fertig programmiert !!!!!!!!!!!!!!!
                $display = [
                            "TimeStamp"                        => ["header"=>"Date","format"=>"Date"],
                            "LBG70alt"                        => ["header"=>"lbgNetamo","format"=>"mm"],
                            "LBG70neu"                        => ["header"=>"lbgHomematic","format"=>"mm"],
                            "LorenzBoehlerGasse70"          => ["header"=>"lbg70-Gasse","format"=>"mm"],
                            "BKS01alt"         => ["header"=>"bksHMalt","format"=>"mm"],
                            "BKS01neu"         => ["header"=>"bksHMneu","format"=>"mm"],
                            "Feldweg1"                      => ["header"=>"bksFeldweg","format"=>"mm"],
                            ];
                $config=array();
                $config["html"]=true;
                //$config["text"]=true;
                $config["insert"]["Header"]    = true;
                //$config["transpose"]=true;
                $config["reverse"]=true;          // die Tabelle in die andere Richtung sortieren

                $html = $ipsTables->showTable($result, $display,$config,false);
                            SetValue($tableID, $html);
                            }
            break;
        case "TempTage":
            if ($debug) echo "DataQuality TempTage:\n";
            $rainRegs=$gartensteuerung->getRainRegisters();
            $variableTempID   = $configuration["AussenTemp"];
            $variableTempName = IPS_GetName($variableTempID);

            $endtime=time();
            $starttime=$endtime-60*60*24*20;  /* die letzten 20 Tage, für Auswertung nebeneinander */

            // Regenwerte
            $config=array();
            $config["StartTime"]=$starttime;
            $configCleanUpData = array();
            $configCleanUpData["range"] = ["max" => 60, "min" => 0,];
            $configCleanUpData["SuppressZero"]=true;
            $configCleanUpData["deleteSourceOnError"]=true;             // true Fehler nicht in die Werte übernehmen
            $configCleanUpData["maxLogsperInterval"]=false;           //unbegrenzt übernehmen
            //$config["Aggregated"]="daily";                     // verwendet archivierte Daten stt manuell zu integrieren, zu viele Daten, zusammenfassen, 0 stündlich, 1 täglich, 2 wöchentlich
            $config["OIdtoStore"]="Rain";             // oid wird auch ausserhalb geändert
            $config["manAggregate"]="daily";                // aggregiert geloogte Werte ohne Aggregation manuell
            $config["CleanUpData"] = $configCleanUpData;    
            $config["ShowTable"]["align"]="minutely";    
            $archiveOps->getValues($rainRegs["IncrementID"],$config,$debug);          // true,2 Debug, Werte einlesen

            //Temperaturwerte
            $config=array();
            $config["StartTime"]=$starttime;
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
            $config["transpose"]=true;
            $config["reverse"]=true;          // die Tabelle in die andere Richtung sortieren
            ksort($result);
            $html = $ipsTables->showTable($result, $display,$config,false);     // true/2 für debug , braucht einen Zeilenindex
            SetValue($tableID, $html);
            break;
        case "DataQuality":   
            $html=$gartensteuerungStatistics->showDataQualityRegs();         
            SetValue($table2ID,$html);  
            break;          
        }
    SetValue($tableID,$html);
    }

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









        }
    }

?>