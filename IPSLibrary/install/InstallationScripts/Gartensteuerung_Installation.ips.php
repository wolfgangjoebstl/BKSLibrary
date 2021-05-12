<?

	/**@defgroup Gartensteuerung
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS oder statistische Auswertungen in LBG
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

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
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

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
 	//read_wfc();
	
	echo "\n=============================================\n";
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."\n";
		}

/*******************************
 *
 * Webfront Konfiguration einlesen
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
	 
	echo "\n";
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled==true)
		{
      	$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10');
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10');
		$WFC10_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10');
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10');
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10');
		$WFC10_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10');
		$WFC10_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10');
		$WFC10_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10');
		$WFC10_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10');
		echo "WF10 Administrator\n";
		echo "  Path          : ".$WFC10_Path."\n";
		echo "  ConfigID      : ".$WFC10_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10_ConfigId)).".".IPS_GetName($WFC10_ConfigId).")\n";		
		echo "  TabPaneItem   : ".$WFC10_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10_TabItem."\n";
		echo "  TabName       : ".$WFC10_TabName."\n";
		echo "  TabIcon       : ".$WFC10_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10_TabOrder."\n";		
		}
		
	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];		
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValue('TabPaneItem', 'WFC10User');
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValue('TabPaneParent', 'WFC10User');
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValue('TabPaneName', 'WFC10User');
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValue('TabPaneIcon', 'WFC10User');
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueInt('TabPaneOrder', 'WFC10User');
		$WFC10User_TabItem        = $moduleManager->GetConfigValue('TabItem', 'WFC10User');
		$WFC10User_TabName        = $moduleManager->GetConfigValue('TabName', 'WFC10User');
		$WFC10User_TabIcon        = $moduleManager->GetConfigValue('TabIcon', 'WFC10User');
		$WFC10User_TabOrder       = $moduleManager->GetConfigValueInt('TabOrder', 'WFC10User');
		echo "WF10 User \n";
		echo "  Path          : ".$WFC10User_Path."\n";
		echo "  ConfigID      : ".$WFC10User_ConfigId."  (".IPS_GetName(IPS_GetParent($WFC10User_ConfigId)).".".IPS_GetName($WFC10User_ConfigId).")\n";
		echo "  TabPaneItem   : ".$WFC10User_TabPaneItem."\n";
		echo "  TabPaneParent : ".$WFC10User_TabPaneParent."\n";
		echo "  TabPaneName   : ".$WFC10User_TabPaneName."\n";
		echo "  TabPaneIcon   : ".$WFC10User_TabPaneIcon."\n";
		echo "  TabPaneOrder  : ".$WFC10User_TabPaneOrder."\n";
		echo "  TabItem       : ".$WFC10User_TabItem."\n";
		echo "  TabName       : ".$WFC10User_TabName."\n";
		echo "  TabIcon       : ".$WFC10User_TabIcon."\n";
		echo "  TabOrder      : ".$WFC10User_TabOrder."\n";		
		}		

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	if ($Mobile_Enabled==true)
		{	
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile \n";
		echo "  Path          : ".$Mobile_Path."\n";		
		}
	
	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	if ($Retro_Enabled==true)
		{	
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		echo "  Path          : ".$Retro_Path."\n";		
		}	

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
    $gartensteuerung = new Gartensteuerung(0,0,true);   // default, default, debug=false
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

	$pname="GiessAnlagenProfil";
	if (IPS_VariableProfileExists($pname) == false)
		{		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	IPS_SetVariableProfileAssociation($pname, 1, "EinmalEin", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
  	   	IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
  	   	//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   	echo "Profil Giessanlagen erstellt;\n";
		}
		
	$pname="GiessConfigProfil";
	if (IPS_VariableProfileExists($pname) == false)
		{		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		//IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	//IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, 0, "Morgen", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	IPS_SetVariableProfileAssociation($pname, 1, "Abend", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
	   	echo "Profil Giessanlagen Konfiguration erstellt;\n";
		}		

	$pname="GiessKreisProfil";
	if (IPS_VariableProfileExists($pname) == false)
		{		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   	IPS_SetVariableProfileValues($pname, 1, 6, 1); //PName, Minimal, Maximal, Schrittweite
	   	IPS_SetVariableProfileAssociation($pname, 1, "1", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   	IPS_SetVariableProfileAssociation($pname, 2, "2", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
  	   	IPS_SetVariableProfileAssociation($pname, 3, "3", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
	   	IPS_SetVariableProfileAssociation($pname, 4, "4", "", 0xF6E3CE); //P-Name, Value, Assotiation, Icon, Color=orange
  	   	IPS_SetVariableProfileAssociation($pname, 5, "5", "", 0x2EFE64); //P-Name, Value, Assotiation, Icon, Color=grassgruen
  	   	IPS_SetVariableProfileAssociation($pname, 6, "6", "", 0xB40486); //P-Name, Value, Assotiation, Icon, Color=violett		
	   	echo "Profil GiessKreis erstellt;\n";
		}
		
	$pname="Minuten";
	if (IPS_VariableProfileExists($pname) == false)
		{
		echo "Profile existiert nicht \n";
 		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
  		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
  		IPS_SetVariableProfileText($pname,'','Min');
	   	echo "Profil Minuten erstellt;\n";		
		//print_r(IPS_GetVariableProfile($pname));
		}	

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Variablen für Modul anlegen ind en Kategorien
     *      Gartensteuerung-Auswertung
     *      Gartensteuerung-Register
	 *      Nachrichtenverlauf-Gartensteuerung
     *      Statistiken                         Regenkalender und Regenereignisse
     *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	
	$categoryId_Gartensteuerung  	= CreateCategory('Gartensteuerung-Auswertung', $CategoryIdData, 10);
	$categoryId_Register    		= CreateCategory('Gartensteuerung-Register',   $CategoryIdData, 200);
	
	$scriptIdGartensteuerung   		= IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);
	$scriptIdWebfrontControl   		= IPS_GetScriptIDByName('WebfrontControl', $CategoryIdApp);

	$categoryId_Nachrichten			= CreateCategory('Nachrichtenverlauf-Gartensteuerung',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	/* Nachrichtenzeilen werden automatisch von der Logging Klasse beim ersten Aufruf gebildet */

	$CategoryId_Statistiken			= CreateCategory('Statistiken',   $CategoryIdData, 200);
	$StatistikBox1ID				= CreateVariable("Regenmengenkalender"   ,3,$CategoryId_Statistiken,  40, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$StatistikBox2ID				= CreateVariable("Regendauerkalender"   ,3,$CategoryId_Statistiken,  40, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$StatistikBox3ID				= CreateVariable("Regenereignisse" ,3,$CategoryId_Statistiken,  20, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */

	$includefile="<?";
	/*  Kommentar muss sein sonst funktioniert Darstellung vom Editor nicht */
	$includefile.="\n".'function ParamList() {
		return array('."\n";
		
	// CreateVariable2($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
	/* CreateVariable3 wie unten legt automatisch die Infos im Include File an */
	$GiessAnlageID 		= CreateVariable3("GiessAnlage", 1, $categoryId_Gartensteuerung, 0, "GiessAnlagenProfil",$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessKreisID		= CreateVariable3("GiessKreis",1,$categoryId_Gartensteuerung, 10, "GiessKreisProfil",$scriptIdWebfrontControl,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessTimeID		= CreateVariable3("GiessTime",1,$categoryId_Gartensteuerung,  30, "Minuten",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessTimeRemainID	= CreateVariable3("GiessTimeRemain",1,$categoryId_Gartensteuerung,  30, "Minuten",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessKreisInfoID	= CreateVariable3("GiessKreisInfo",3,$categoryId_Gartensteuerung,  40, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessDauerInfoID	= CreateVariable3("GiessDauerInfo",3,$categoryId_Gartensteuerung,  50, "~HTMLBox",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */

	$GiessCountID		= CreateVariable3("GiessCount",1,$categoryId_Register, 10, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessCountOffsetID	= CreateVariable3("GiessCountOffset",1,$categoryId_Register, 210, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessPauseID 		= CreateVariable3("GiessPause",1,$categoryId_Register, 20, "Minuten",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessAnlagePrevID 	= CreateVariable3("GiessAnlagePrev",1,$categoryId_Register, 200, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessKonfigID 		= CreateVariable3("GiessStartzeitpunkt",0,$categoryId_Register, 300, "GiessConfigProfil",$scriptIdWebfrontControl,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	
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
        IPS_SetEventActive($eid2,true);
        IPS_SetEventActive($eid2m,true);
        }
    else    
        {
        IPS_SetEventActive($eid2,false);
        IPS_SetEventActive($eid2m,false);
        IPS_SetEventActive($eid3,false);
        IPS_SetEventActive($eid4,false);
        IPS_SetEventActive($eid5,false);
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

		$categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "====================================================================================\n";
		echo "Webportal Administrator :Gartensteuerung Kategorie installieren in: ".$categoryId_AdminWebFront." ".IPS_GetName($categoryId_AdminWebFront)."/".IPS_GetName(IPS_GetParent($categoryId_AdminWebFront))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($categoryId_AdminWebFront)))."\n";
		echo "    Gartensteuerung Kategorie installieren als: ".$WFC10_Path." und Inhalt löschen und dann verstecken.\n";		

		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
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

		$tabItem = $WFC10_TabPaneItem.$WFC10_TabItem;
		if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
		 	{
			echo "      löscht TabItem ID ".$tabItem."\n";
			DeleteWFCItems($WFC10_ConfigId, $tabItem);
			}
		else
			{
			echo "      TabItem ID ".$tabItem." nicht mehr vorhanden.\n";
			}	

        /* Webfront für Giessanlage anlegen */
        if ($GartensteuerungConfiguration["Configuration"]["Irrigation"]=="ENABLED")
            {
            echo "        erzeugt TabItem :".$WFC10_TabPaneItem." in ".$WFC10_TabPaneParent."\n";
            CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon); /* Autosteuerung Haeuschen */
            echo "        erzeugt Split TabItem :".$tabItem." mit Name ".$WFC10_TabName." in ".$WFC10_TabPaneItem." und darunter die Items Left und Right.\n";
            CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem,           $WFC10_TabPaneItem,    $WFC10_TabOrder,     $WFC10_TabName,     $WFC10_TabIcon, 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
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

        if ($GartensteuerungConfiguration["Configuration"]["Statistics"]=="ENABLED")
            {
            $categoryIdLeft0  = CreateCategory('Left0',  $categoryId_WebFrontAdministrator, 10);
            $categoryIdRight0 = CreateCategory('Right0', $categoryId_WebFrontAdministrator, 20);
            CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."0",           $WFC10_TabPaneItem,    100,     "Statistik",     "Rainfall", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem."0".'_Left',   $tabItem."0",   10, '', '', $categoryIdLeft0   /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem."0".'_Right',  $tabItem."0",   20, '', '', $categoryIdRight0  /*BaseId*/, 'false' /*BarBottomVisible*/);
            CreateLinkByDestination("Regenmengenkalender", $StatistikBox1ID ,    $categoryIdLeft0,  140);
            CreateLinkByDestination("Regendauerkalender", $StatistikBox2ID ,    $categoryIdLeft0,  140);
            CreateLinkByDestination("Regenereignisse", $StatistikBox3ID ,    $categoryIdRight0,  150);
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

		$categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
		echo "====================================================================================\n";
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installieren in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";
		CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);

		@WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
		@WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);

		/*************************************/

		/* Neue Tab für untergeordnete Anzeigen wie eben Autosteuerung und andere schaffen */
		echo "\nWebportal User.Autosteuerung Datenstruktur installieren in: ".$WFC10User_Path." \n";
		$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
		EmptyCategory($categoryId_WebFrontUser);
		echo "Kategorien erstellt, Main: ".$categoryId_WebFrontUser."\n";
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontUser, true); //Objekt verstecken		

		/*************************************/
		
		$tabItem = $WFC10User_TabPaneItem.$WFC10User_TabItem;
		if ( exists_WFCItem($WFC10User_ConfigId, $tabItem) )
		 	{
			echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10User_ConfigId).")  löscht TabItem : ".$tabItem."\n";
			DeleteWFCItems($WFC10User_ConfigId, $tabItem);
			}
		else
			{
			echo "Webfront ".$WFC10User_ConfigId." (".IPS_GetName($WFC10User_ConfigId).")  TabItem : ".$tabItem." nicht mehr vorhanden.\n";
			}	
		echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$WFC10User_TabPaneItem." in ".$WFC10User_TabPaneParent."\n";
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
		CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem,               $WFC10User_TabPaneItem,    $WFC10User_TabOrder,     $WFC10User_TabName,     $WFC10User_TabIcon);		

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
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);
		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFront,  10);
		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Retro_Path);
		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFront,  10);
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