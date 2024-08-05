<?php

	/**@defgroup Gartensteuerung
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS oder statistische Auswertungen in LBG
     * hier ist das Installationsskript, um Variablen und Tabs vorzubereiten
     * es gibt bis zu 3 Tabs
     *      Gartensteuerung     das Hauptmodul für die Beregnung
     *      Statistik           Regenstatistik, Dauer, Intensität von lokaler Wetterstation
     *      Powerpump           Leistung und Energieverbrauch, Fehlermeldungen wenn kein Wasser etc.
     *
     *
	 *
	 * @file          Gartensteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.08.2014<br/>
	 **/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/

	//Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
    IPSUtils_Include ('Gartensteuerung_Library.class.ips.php', 'IPSLibrary::app::modules::Gartensteuerung');    	
		 
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Gartensteuerung');
	echo "\nGartensteuerung Version : ".$ergebnis;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

    $ipsOps = new ipsOps();    
    $webOps = new webOps();                     // Buttons anlegen, sind auch Profile, werden aber bei Install angelegt
    $profileOps = new profileOps();             // Profile verwalten, local geht auch remote
    
    $wfcHandling =  new WfcHandling();

    if ($_IPS['SENDER']=="Execute") $debug=true;            // Mehr Ausgaben produzieren
	else $debug=false;

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId fuer Autosteuerung, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
	$WebfrontConfigID = $wfcHandling->get_WebfrontConfigID();

/*******************************
 *
 * Webfront Konfiguration aus ini Datei einlesen
 *
 ********************************/

	/* 
	 *  neue Webfronts werden nicht mehr angelegt, wir gehen davon aus dass Administrator und User bereits bestehen 
	 *  zusaetzliche Webfront Variablen werden nicht ausgewertet. 
	 *
	 *  es wird automatisch eine eigene Hauptkategorie für die Gartensteuerung erstellt. Diese wird von diesem Install in das Autosteuerung Webfront als Unterwebfront erstellt und 
	 *        auf die beiden Unterkategorien verwiesen. Dieses Webfront wird doppelt zum Autosteuerung/Gartensteuerungs Webfront erstellt. 
	 *
	 *  Es gibt aber auch in der Autosteuerung eine eigene Unterkategorie für die Gartensteuerung die vom Install der Autosteuerung erstellt wird. 
	 *
	 *  in der Gartensteuerung Kategorie wird auch eine eigene Unterkategorie für die statistischen Auswertungen erstellt werden als zweite Unterkategorie für das eigene Giessanlagen Icon
	 *
	 */
	 
    $configWFront=$ipsOps->configWebfront($moduleManager,false);     // wenn true mit debug Funktion
    
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);
	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
    $Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);

	if ($WFC10_Enabled==true)		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];		
	if ($WFC10User_Enabled==true)   $WFC10User_ConfigId       = $WebfrontConfigID["User"];
  
    $ipsOps->writeConfigWebfrontAll($configWFront);             // Ausgabe ini Informationen

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
    $gartensteuerung = new Gartensteuerung(0,0,$debug);   // default, default, debug=false
    $GartensteuerungConfiguration =	$gartensteuerung->getConfig_Gartensteuerung();

	/*
	echo "\nRegister \"Allgemeine Definitionen\"";
	$scriptName="Allgemeine Definitionen";
	$file="AllgemeineDefinitionen.inc.php";
	$categoryId=0;
	CreateScript($scriptName, $file, $categoryId);

	echo "\nRegister \"Logging Class\"\n";
	$scriptName="Logging Class";
	$file="_include/Logging.class.php";
	$categoryid  = IPSUtil_ObjectIDByPath('Program');
	CreateCategory('_include', $categoryid, 0);
	$categoryid  = IPSUtil_ObjectIDByPath('Program._include');
	CreateScript($scriptName, $file, $categoryid);
	*/

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Variablen Profile für Modul anlegen
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    echo "\n";
    echo "Darstellung der benötigten Variablenprofile im lokalem Bereich, wenn fehlt anlegen:\n";
	$profilname=array("Minuten"=>"update");
    $profileOps->synchronizeProfiles($profilname);

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Buttons für Modul anlegen
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    $categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
	$categoryId_Register    		= CreateCategory('Gartensteuerung-Register',   $CategoryIdData, 200);
	$categoryIdSelectReports        = CreateCategory('SelectReports',              $CategoryIdData, 300);

	$scriptIdGartensteuerung   		= IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);
	$scriptIdWebfrontControl   		= IPS_GetScriptIDByName('WebfrontControl', $CategoryIdApp);

	$includefile="<?php";                      //  Kommentar muss sein sonst funktioniert Darstellung vom Editor nicht , verwendet von CreateVariable3
	$includefile.="\n".'function ParamList() {
		return array('."\n";

    // CreateVariable2($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
	/* CreateVariable3 wie unten legt automatisch die Infos im Include File an */

    echo "Create GiessAnlagenProfil und Variable GiessAnlage.\n";
	$pname="GiessAnlagenProfil";
    $tabs =  ["Aus","EinmalEin","Auto"];
    $color = [0x481ef1,0xf13c1e,0x1ef127];
    $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
	$GiessAnlageID 		= CreateVariable3("GiessAnlage", 1, $categoryId_Gartensteuerung, 0, $pname,$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */

    echo "Create GiessKreisProfil und Variable Giesskreis.\n";
	$pname="GiessKreisProfil";
    $tabsToChoose  = ["1","2","3","4","5","6","7","8"];
    $colorToChoose = [0x481ef1,0xf13c1e,0x1ef127,0xF6E3CE,0x2EFE64,0xB40486,0x04B486,0x8404B6];
    $i=0; $max = $GartensteuerungConfiguration["Configuration"]["KREISE"];
    $tabs=array(); $color=array();
    foreach ($tabsToChoose as $index => $entry)
        {
        $tabs[$i]  = $entry;
        $color[$i] = $colorToChoose[$index];
        if ($i>=($max-1)) break;                    // wenn die Anzahl der Kreise erreicht, unterbrechen
        $i++;
        }
    //print_r($tabs);
    $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor

	$GiessKreisID		= CreateVariable3("GiessKreis",1,$categoryId_Gartensteuerung, 10,  $pname,$scriptIdWebfrontControl,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */

	$pname="GiessConfigProfil";
    $tabs =  ["Morgen","Abend"];
    $color = [0x481ef1,0x1ef127];
    $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
	$GiessKonfigID 		= CreateVariable3("GiessStartzeitpunkt",0,$categoryId_Register, 300, $pname,$scriptIdWebfrontControl,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */

    // Umschalter auf der inken Seite wenn DataQuality Pane aktiviert ist

    $buttonIds=false;
    if (strtoupper($GartensteuerungConfiguration["Configuration"]["DataQuality"])=="ENABLED") 
        {
        echo "Buttons zur Anzeige von historischen Daten, Configuration DataQuality enabled:\n";
        $gartensteuerungReports=$GartensteuerungConfiguration["Configuration"]["Reports"];
        $count=0;
        $associationsValues = array();
        foreach ($gartensteuerungReports as $displaypanel=>$values)
            {
            echo "     Profileintrag $count : ".$displaypanel."  \n";
            $associationsValues[$count]=$displaypanel;
            $count++;
            } 
        if ($count==0) echo "No Reports configured in Configuration. Do at least RegenMonat, TempMonat, RegenTage, TempTage .\n";       
        $webOps = new webOps(true);
        $webOps->setConfigButtons(9000);                    // order in ID display
        $buttonIds = $webOps->createSelectButtons($associationsValues,$categoryIdSelectReports, $scriptIdWebfrontControl);

        $pname="AnotherSelector";
        $tabs =  ["Aus","Ein","Auto"];
        $color = [0x481ef1,0xf13c1e,0x1ef127];
        $webOps->createActionProfileByName($pname,$tabs,0,$color);                 // erst das Profil, dann die Variable initialisieren, , 0 ohne Selektor
	    $AnotherSelectorID 		= CreateVariable3("AnotherSelector", 1, $categoryIdSelectReports, 0, $pname,$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
	    $ReportSelectorID 		= CreateVariable3("ReportSelector",  3, $categoryIdSelectReports, 0, "" ,$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String  es wird in lesbaren Format die Betriebsart gespeichert */
		}      


	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Variablen für Modul anlegen in den Kategorien
     *      Gartensteuerung-Auswertung
     *      Gartensteuerung-Register
	 *      Nachrichtenverlauf-Gartensteuerung
     *      Statistiken                         Regenkalender und Regenereignisse
     *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
		
	$categoryId_Nachrichten			= CreateCategory('Nachrichtenverlauf-Gartensteuerung',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	/* Nachrichtenzeilen werden automatisch von der Logging Klasse beim ersten Aufruf gebildet */

	$CategoryId_Statistiken			= CreateCategory('Statistiken',   $CategoryIdData, 200);
	$StatistikBox1ID				= CreateVariable("Regenmengenkalender"   ,3,$CategoryId_Statistiken,  40, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$StatistikBox2ID				= CreateVariable("Regendauerkalender"   ,3,$CategoryId_Statistiken,  40, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$StatistikBox3ID				= CreateVariable("Regenereignisse" ,3,$CategoryId_Statistiken,  20, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */

		
	// CreateVariable2($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
	/* CreateVariable3 wie unten legt automatisch die Infos im Include File an */
	$GiessAnlageID 		= CreateVariable3("GiessAnlage", 1, $categoryId_Gartensteuerung, 0, "GiessAnlagenProfil",$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessKreisID		= CreateVariable3("GiessKreis",1,$categoryId_Gartensteuerung, 10, "GiessKreisProfil",$scriptIdWebfrontControl,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessTimeID		= CreateVariable3("GiessTime",1,$categoryId_Gartensteuerung,  30, "Minuten",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessTimeRemainID	= CreateVariable3("GiessTimeRemain",1,$categoryId_Gartensteuerung,  30, "Minuten",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessKreisInfoID	= CreateVariable3("GiessKreisInfo",3,$categoryId_Gartensteuerung,  40, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessDauerInfoID	= CreateVariable3("GiessDauerInfo",3,$categoryId_Gartensteuerung,  50, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */

	$tableID	        = CreateVariable3("Tabelle",  3,$categoryIdSelectReports,  30, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$table1ID	        = CreateVariable3("Tabelle1", 3,$categoryIdSelectReports,  540, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$table2ID	        = CreateVariable3("Tabelle2", 3,$categoryIdSelectReports,  550, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$chartID	        = CreateVariable3("Chart",    3,$categoryIdSelectReports,  100, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$mapID	            = CreateVariable3("GoogleMap",3,$categoryIdSelectReports,  500, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */

	$GiessCountID		= CreateVariable3("GiessCount",1,$categoryId_Register, 10, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessCountOffsetID	= CreateVariable3("GiessCountOffset",1,$categoryId_Register, 210, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessPauseID 		= CreateVariable3("GiessPause",1,$categoryId_Register, 20, "Minuten",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessAnlagePrevID 	= CreateVariable3("GiessAnlagePrev",1,$categoryId_Register, 200, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	
	//function CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') {

	$includefile.=');'."\n";
	$includefile.='}'."\n".'?>';
	//echo ".....".$includefile."\n";

	if ($RemoteVis_Enabled==false)
	   { /* keine Remote Visualisierung, daher inc File für andere schreiben */
		$filename=IPS_GetKernelDir()."scripts\IPSLibrary/app/modules/Gartensteuerung/Gartensteuerung.inc.php";
		if (!file_put_contents($filename, $includefile)) {
      	  throw new Exception('Create File '.$filename.' failed!');
    			}
	   echo "\nFilename:".$filename;
		}
		
	// Add Scripts, they have auto install
	//IPS_RunScript($scriptIdGartensteuerung);
	//IPS_RunScript($scriptIdNachrichtenverlauf);
	
	echo "\nData Kategorie : ".$CategoryIdData;
	echo "\nApp  Kategorie : ".$CategoryIdApp;
	echo "\nScriptID #1    : ".$scriptIdGartensteuerung;
	echo "\nScriptID #2    : ".$scriptIdWebfrontControl;
	echo "\n";

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Variablen für Darstellung evaluieren
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	 
	/* Links für Webfront identifizieren, alle Verzeichnisse in CustomComponents Data /core/IPSComponent Verzeichnis 
	 *
	 * Trennung in Kategorien erfolgt durch - Zeichen nach Auswertung und Nachrichten
	 */
	 
	echo "\nLinks für Webfront Administrator und User identifizieren :\n";
	$webfront_links=array();
	$Category=IPS_GetChildrenIDs($CategoryIdData);
	foreach ($Category as $CategoryId)
		{
		echo "  Category    ID : ".$CategoryId." Name : ".IPS_GetName($CategoryId)."\n";
		$Params = explode("-",IPS_GetName($CategoryId)); 
		$SubCategory=IPS_GetChildrenIDs($CategoryId);
		foreach ($SubCategory as $SubCategoryId)
			{
            if (sizeof($Params)>1)
                {
    			//echo "       ".IPS_GetName($SubCategoryId)."   ".$Params[0]."   ".$Params[1]."\n";
	    		$webfront_links[$Params[0]][$Params[1]][$SubCategoryId]["NAME"]=IPS_GetName($SubCategoryId);
                }
			}
		}
	echo "\n";
	//print_r($webfront_links);		// werden noch nicht ausgewertet
	//echo "\n";


	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Timer Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

    echo "Timer aufsetzen :\n";
    /* Timer zum Giessstopp, zu diesem Zeitpunkt alles ausschalten - sicherheitshalber */
    $eid2 = @IPS_GetEventIDByName("Giessstopp1", $scriptIdGartensteuerung);
    if ($eid2==false)
        {
        $eid2 = IPS_CreateEvent(1);
        IPS_SetParent($eid2, $scriptIdGartensteuerung);
        IPS_SetName($eid2, "Giessstopp1");
        IPS_SetEventCyclicTimeFrom($eid2,22,0,0);  /* immer um 22:00 */
        }
    $eid2m = @IPS_GetEventIDByName("Giessstopp2", $scriptIdGartensteuerung);
    if ($eid2m==false)
        {
        $eid2m = IPS_CreateEvent(1);
        IPS_SetParent($eid2m, $scriptIdGartensteuerung);
        IPS_SetName($eid2m, "Giessstopp2");
        IPS_SetEventCyclicTimeFrom($eid2m,10,0,0);  /* immer um 10:00 */
        }
    /* UpdateTimer übernimmt das Minutenupdate bei der Giesszeit und gleichzeitig auch das Umschalten zwischen den Giesskreisen */
    $eid5 = @IPS_GetEventIDByName("UpdateTimer", $scriptIdGartensteuerung);
    if ($eid5==false)
        {
        $eid5 = IPS_CreateEvent(1);
        IPS_SetParent($eid5, $scriptIdGartensteuerung);
        IPS_SetName($eid5, "UpdateTimer");
        IPS_SetEventCyclic($eid5, 0 /* Keine Datumsüberprüfung */, 0, 0, 2, 2 /* Minütlich */ , 1 /* Alle Minuten */);
        }

    /* UpdateTimerSlow übernimmt das Update der Regenstatistik */
    $eid6 = @IPS_GetEventIDByName("UpdateTimerHourly", $scriptIdGartensteuerung);
    if ($eid6==false)
        {
        $eid6 = IPS_CreateEvent(1);
        IPS_SetParent($eid6, $scriptIdGartensteuerung);
        IPS_SetName($eid6, "UpdateTimerHourly");
        IPS_SetEventCyclic($eid6, 0 /* Kein Datumstyp, tägliche Ausführung */, 0 /* kein Datumsintervall */, 0 /* keien Datumstage */, 0 /* kein Datumstageintervall */, 3 /* Stündlich */ , 1 /* Alle Stunden */);
        }

    $eid3 = @IPS_GetEventIDByName("Timer3", $scriptIdGartensteuerung);
    if ($eid3==false)
        {
        $eid3 = IPS_CreateEvent(1);
        IPS_SetParent($eid3, $scriptIdGartensteuerung);
        IPS_SetName($eid3, "Timer3");
        }

    $eid4 = @IPS_GetEventIDByName("Timer4", $scriptIdGartensteuerung);
    if ($eid4==false)
        {
        $eid4 = IPS_CreateEvent(1);
        IPS_SetParent($eid4, $scriptIdGartensteuerung);
        IPS_SetName($eid4, "Timer4");
        }

    if ($GartensteuerungConfiguration["Configuration"]["Irrigation"]=="ENABLED")
        {
        /* Giessstopp Timer einschalten */
        IPS_SetEventActive($eid2,true);             // Giesstopp 1
        IPS_SetEventActive($eid2m,true);            // Giesstopp 2
        IPS_SetEventActive($eid6,false);            // SlowUpdate nicht notwendig,wenn Giessteuerung akiviert
        }
    else    
        {
        IPS_SetEventActive($eid2,false);
        IPS_SetEventActive($eid2m,false);
        IPS_SetEventActive($eid3,false);
        IPS_SetEventActive($eid4,false);
        IPS_SetEventActive($eid5,false);
        IPS_SetEventActive($eid6,true);        
        }

    /* Alte Timer loeschen, damit sie nicht doppelt vorkommen, zumindest ein Timer muss aktiv sein damit Gartensteuerung zum ersten mal aufgerufen wird */
    $deltimerID = @IPS_GetEventIDByName("Timer2", $scriptIdGartensteuerung);	
    if ($deltimerID !== false) 
        {
        echo "Timer Event \"Timer2\" noch vorhanden : ".$deltimerID."   -> loeschen.\n";
        IPS_DeleteEvent($deltimerID);
        }

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Installation
	 *
	 * es werden die Kategorien erstellt, es werden die tabs für das Webfront erstellt und auf die Kategorien verlinkt
	 * das ganze wird für den Administrator und den User gemacht.
	 *
	 * WFC10 Path ist der Administrator.Gartensteuerung
	 * das webfront für diese Kategorien ist immer admin
	 * es werden zusätzliche Tabs festgelegt, diese haben nur ein Icon.
	 * aus TabPaneItem.TabPaneItemTabItem wird eine passende Unterkategorie im Webfront generiert
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	 
	if ($WFC10_Enabled)
		{
		/* Kategorien werden erstellt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben */
        $config = $configWFront["Administrator"];
		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "Webportal Administrator :Gartensteuerung Kategorie installieren in: ".$categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."/".IPS_GetName(IPS_GetParent($categoryId_AdminWebFront))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($categoryId_AdminWebFront)))."\n";
		echo "    Gartensteuerung Kategorie installieren als: ".$config["Path"]." und Inhalt löschen und dann verstecken.\n";		

		$categoryId_WebFrontAdministrator         = CreateCategoryPath($config["Path"]);
		EmptyCategory($categoryId_WebFrontAdministrator);
		$categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFrontAdministrator, 10);
		$categoryIdRight = CreateCategory('Right', $categoryId_WebFrontAdministrator, 20);
		echo "     Kategorien erstellt, Main: ".$categoryId_WebFrontAdministrator." Install Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n";
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontAdministrator, true); //Objekt verstecken
		
		/* Webfront Konfiguration erstellen */
		
		echo "\nWebportal Administrator:  in Webfront Konfigurator ID ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).") die ID Admin für die gesamte Kategorie Visualization installieren.\n";
		echo "       Create Admin in roottp (hardcoded).\n";
		CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   800, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		echo "       Delete/hide IDs root und dwd.\n";
		//DeleteWFCItems($WFC10_ConfigId, "root");
		@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);		
						
		/*************************************/
		/* Neue Tab für untergeordnete Anzeigen wie eben Gartensteuerung in AutoTPA und andere schaffen */
		/*************************************/
		//$tabItem = $WFC10_TabPaneItem.$WFC10_TabItem;
		$tabItem = $config["TabPaneItem"].$config["TabItem"];
		if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
		 	{
			echo "      löscht TabItem ID ".$tabItem."\n";
			DeleteWFCItems($WFC10_ConfigId, $tabItem);
			}
		else
			{
			echo "      TabItem ID ".$tabItem." nicht mehr vorhanden.\n";
			}	

        echo "   Webfront für Giessanlage entspreechend Konfiguration anlegen [Giessanlage|Statistik|Gartenpumpe]:\n";
        echo "     erzeugt TabPaneItem :".$config["TabPaneItem"]." in ".$config["TabPaneParent"]."\n";
        CreateWFCItemTabPane   ($WFC10_ConfigId, $config["TabPaneItem"], $config["TabPaneParent"],  $config["TabPaneOrder"], $config["TabPaneName"], $config["TabPaneIcon"]); /* Autosteuerung Haeuschen */
        if (strtoupper($GartensteuerungConfiguration["Configuration"]["Irrigation"])=="ENABLED")
            {
            echo "     ConfOption Giessanlage, erzeugt Split TabItem :".$tabItem." mit Name ".$config["TabName"]." in ".$config["TabPaneItem"]." und darunter die Items Left und Right.\n";
            CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem,           $config["TabPaneItem"],    $config["TabOrder"],     $config["TabName"],     $config["TabIcon"], 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);

            CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryIdLeft,  10);
            CreateLinkByDestination('GiessKreis', $GiessKreisID, $categoryIdLeft,  20);
            CreateLinkByDestination('GiessStartzeitpunkt', $GiessKonfigID, $categoryIdLeft,  30); 	
            CreateLinkByDestination('GiessTime', $GiessTimeID,    $categoryIdLeft,  40);
            CreateLinkByDestination('GiessTimeRemain', $GiessTimeRemainID,    $categoryIdLeft,  40);
            CreateLinkByDestination("GiessKreisInfo", $GiessKreisInfoID,    $categoryIdLeft,  50);
            CreateLinkByDestination("GiessDauerInfo", $GiessDauerInfoID,    $categoryIdLeft,  50);
                    
            CreateLinkByDestination('GiessCount', $GiessCountID,    $categoryIdLeft,  120);
            CreateLinkByDestination('GiessCountOffset', $GiessCountOffsetID,    $categoryIdLeft,  125);
            CreateLinkByDestination('GiessAnlagePrev', $GiessAnlagePrevID,    $categoryIdLeft,  130);
            CreateLinkByDestination("GiessPause", $GiessPauseID ,    $categoryIdLeft,  140);
            
            CreateLinkByDestination('Nachrichten', $input,    $categoryIdRight,  110);
            }

		/* zusaetzliches Webfront Tab für Statistik Auswertungen */

        if (strtoupper($GartensteuerungConfiguration["Configuration"]["Statistics"])=="ENABLED")                //tabItem0
            {
            echo "     ConfOption Statistik, erzeugt SplitPaneItem :".$tabItem."0 in ".$config["TabPaneItem"]."\n";
            $categoryIdLeft0  = CreateCategory('Left0',  $categoryId_WebFrontAdministrator, 10);
            $categoryIdRight0 = CreateCategory('Right0', $categoryId_WebFrontAdministrator, 20);
            CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."0",           $config["TabPaneItem"],    100,     "Statistik",     "Rainfall", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem."0".'_Left',   $tabItem."0",   10, '', '', $categoryIdLeft0   /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem."0".'_Right',  $tabItem."0",   20, '', '', $categoryIdRight0  /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateLinkByDestination("Regenmengenkalender", $StatistikBox1ID ,    $categoryIdLeft0,  140);
            CreateLinkByDestination("Regendauerkalender", $StatistikBox2ID ,    $categoryIdLeft0,  140);
            CreateLinkByDestination("Regenereignisse", $StatistikBox3ID ,    $categoryIdRight0,  150);
            }

		/* zusaetzliches Webfront Tab für Auswertungen über die Stromaufnahme der Gartenpumpe */

        if (strtoupper($GartensteuerungConfiguration["Configuration"]["PowerPump"])=="ENABLED")                 //tabItem1
            {
            echo "     ConfOption Gartenpumpe, erzeugt SplitPaneItem :".$tabItem."1 in ".$config["TabPaneItem"]."\n";                
            $categoryIdLeft1  = CreateCategory('Left1',  $categoryId_WebFrontAdministrator, 10);
            $categoryIdRight1 = CreateCategory('Right1', $categoryId_WebFrontAdministrator, 20);
            CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."1",           $config["TabPaneItem"],    100,     "PowerPump",     "Electricity", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem."1".'_Left',   $tabItem."1",   10, '', '', $categoryIdLeft1   /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem."1".'_Right',  $tabItem."1",   20, '', '', $categoryIdRight1  /*BaseId*/, 'false' /*BarBottomVisible*/);
            if ( (isset($GartensteuerungConfiguration["Configuration"]["CheckPower"])) && ($GartensteuerungConfiguration["Configuration"]["CheckPower"]!==null) )
                {
                echo "Gartenpumpe überprüft durch POWER Register : ".$GartensteuerungConfiguration["Configuration"]["CheckPower"]."\n";
                $powerID = $GartensteuerungConfiguration["Configuration"]["CheckPower"];
                CreateLinkByDestination("Leistung Gartenpumpe", $powerID ,    $categoryIdRight1,  150);

                }
            }
        if (strtoupper($GartensteuerungConfiguration["Configuration"]["DataQuality"])=="ENABLED") 
            {
            echo "     ConfOption Datenqualität, erzeugt SplitPaneItem :".$tabItem."2 in ".$config["TabPaneItem"]."\n";                
            $categoryIdLeft2  = CreateCategory('Left2',  $categoryId_WebFrontAdministrator, 10);
            $categoryIdRight2 = CreateCategory('Right2', $categoryId_WebFrontAdministrator, 20);
            CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."2",           $config["TabPaneItem"],    100,     "DataQuality",     "Stars", 1 /*Vertical*/, 10 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem."2".'_Left',   $tabItem."2",   10, '', '', $categoryIdLeft2   /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem."2".'_Right',  $tabItem."2",   20, '', '', $categoryIdRight2  /*BaseId*/, 'false' /*BarBottomVisible*/);    
            
            if ($buttonIds)                 // es wurden Buttons für die Linke Seite erstellt
                {
    	        $categoryIdButtonGroup  = CreateVariableByName($categoryIdLeft2, "Select Report", 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
                foreach ($buttonIds as $id => $button)
                    {
                    CreateLinkByDestination(" ", $button["ID"], $categoryIdButtonGroup, $id+100);         // kein Name, sonst zu Viel Platzbedarf, Profil hat einen Namen, geht nicht mit CreateLink
                    }
                CreateLinkByDestination("Selector", $AnotherSelectorID, $categoryIdRight2,   50);    
                CreateLinkByDestination("Tabelle",  $tableID ,          $categoryIdRight2,  150);
                CreateLinkByDestination("Tabelle1", $table1ID ,         $categoryIdRight2,  560);
                CreateLinkByDestination("Tabelle2", $table2ID ,         $categoryIdRight2,  570);
                CreateLinkByDestination("Chart",    $chartID ,          $categoryIdRight2,  250);
                CreateLinkByDestination("Map",      $mapID ,            $categoryIdRight2,  500);
                }
            }
		}

	ReloadAllWebFronts(); /* es wurde das Gartensteuerung Webfront komplett geloescht und neu aufgebaut, reload erforderlich */

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront User Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/

	if ($WFC10User_Enabled)
		{		
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */
        $config = $configWFront["User"];
		$categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
		echo "====================================================================================\n";
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
		CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);

		/*************************************/

		/* Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen */
		echo "\nWebportal User.Autosteuerung Datenstruktur installieren in: ".$config["Path"]." \n";
		$categoryId_WebFrontUser         = CreateCategoryPath($config["Path"]);
		EmptyCategory($categoryId_WebFrontUser);
		echo "Kategorien erstellt, Main: ".$categoryId_WebFrontUser."\n";
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontUser, true); //Objekt verstecken		

		/*************************************/
		
		$tabItem = $config["TabPaneItem"].$config["TabItem"];
		if ( exists_WFCItem($WFC10User_ConfigId, $tabItem) )
		 	{
			echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10User_ConfigId).")  löscht TabItem : ".$tabItem."\n";
			DeleteWFCItems($WFC10User_ConfigId, $tabItem);
			}
		else
			{
			echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10User_ConfigId).")  TabItem : ".$tabItem." nicht mehr vorhanden.\n";
			}	
		echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$config["TabPaneItem"]." in ".$config["TabPaneParent"]."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $config["TabPaneItem"], $config["TabPaneParent"],  $config["TabPaneOrder"], $config["TabPaneName"], $config["TabPaneIcon"]);
		echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem : $tabItem in ".$config["TabPaneItem"]."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem,               $config["TabPaneItem"],    $config["TabOrder"],     $config["TabName"],     $config["TabIcon"]);		

		/*************************************/
		$categoryId_WebFrontTab = $categoryId_WebFrontUser;
		CreateWFCItemCategory  ($WFC10User_ConfigId, $tabItem.'_Group',   $tabItem,   10, '', '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);

		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFrontTab,  10);
		CreateLinkByDestination('GiessKreis', $GiessKreisID, $categoryId_WebFrontTab,  20);
		CreateLinkByDestination('GiessTime', $GiessTimeID,    $categoryId_WebFrontTab,  40);
		CreateLinkByDestination('GiessTimeRemain', $GiessTimeRemainID,    $categoryId_WebFrontTab,  40);
		CreateLinkByDestination("GiessKreisInfo", $GiessKreisInfoID,    $categoryId_WebFrontTab,  50);
		CreateLinkByDestination("GiessDauerInfo", $GiessDauerInfoID,    $categoryId_WebFrontTab,  50);
		}
	else
	   {
	   /* User not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert isnd
		*/
	   DeleteWFCItems($WFC10User_ConfigId, "HouseTPU");
	   EmptyCategory($categoryId_WebFrontUser);
	   }


	if ($Mobile_Enabled)
		{
        $config = $configWFront["Mobile"];
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($config["Path"]);
		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFront,  10);
		}

	if ($Retro_Enabled)
		{
        $config = $configWFront["Retro"];            
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($config["Path"]);
		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFront,  10);
		}

    echo "Summary WFC Konfiguration : \n";
    $wfc=$wfcHandling->read_wfc(1,true);                             // 1 ist der level der angezeigt wird, true für Debug
    //$wfc=$wfcHandling->read_wfcByInstance(false,1);                 // false interne Datanbank für Config nehmen
    foreach ($wfc as $index => $entry)                              // Index ist User, Administrator
        {
        echo "\n------$index:\n";
        $wfcHandling->print_wfc($wfc[$index]);
        } 


/************************************************************************************************/

function CreateVariable3($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
	{
	global $includefile;
	$oid=CreateVariable($Name, $Type, $ParentId, $Position, $Profile, $Action, $ValueDefault, $Icon);
	$includefile.='"'.$Name.'" => array("OID"     => \''.$oid.'\','."\n".
					'                       "Name"    => \''.$Name.'\','."\n".
					'                       "Type"    => \''.$Type.'\','."\n".
					'                       "Profile" => \''.$Profile.'\'),'."\n";
	return $oid;
	}

?>