<?

/*

Itunes Ansteuerung und Ueberwachung, macht ein kleines Lautsprechersymbol im Webfront, oben in der Schnellauswahl
Übernimmt auch das Install des Netplayers, da dieser mittlerweile etwas buggy geworden ist.

Modifiziert auf IPS Library und kleine Anpassungen von Wolfgang Joebstl


Funktionen:

Erst-Installation:

Installation (erneut/Update)



*/

/*******************************
 *
 * Initialisierung, Modul Handling Vorbereitung
 *
 ********************************/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\iTunesSteuerung\iTunes.Configuration.inc.php");

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('iTunesSteuerung',$repository);
	}

$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nIP Symcon Kernelversion    : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPS ModulManager Version   : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Stromheizung');
	echo "\nModul iTunesSteuerung Version : ".$ergebnis."   Status : ".$moduleManager->VersionHandler()->GetModuleState()."\n";

	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.=str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n";

	echo "Variablen vorbereiten.\n";

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$categoryId_iTunes  = CreateCategory("iTunes", $CategoryIdData, 10);

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf-iTunesSteuerung',   $CategoryIdData, 20);
	$iTunes_NachrichtenInput = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );

	$scriptIdWebfrontControl   = IPS_GetScriptIDByName('iTunes.ActionScript', $CategoryIdApp);

	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$moduleManagerNP = new IPSModuleManager('NetPlayer');

	IPSUtils_Include ("IPSInstaller.inc.php",            "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSMessageHandler.class.php",     "IPSLibrary::app::core::IPSMessageHandler");
	IPSUtils_Include ("NetPlayer_Constants.inc.php",     "IPSLibrary::app::modules::NetPlayer");
	IPSUtils_Include ("NetPlayer_Configuration.inc.php", "IPSLibrary::config::modules::NetPlayer");

	$CategoryIdDataNP     = $moduleManagerNP->GetModuleCategoryID('data');
	$CategoryIdAppNP      = $moduleManagerNP->GetModuleCategoryID('app');
	$CategoryIdHw       = CreateCategoryPath('Hardware.NetPlayer');

/*******************************
 *
 * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
 *
 ********************************/

	echo "\n";
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	
	$WebfrontConfigID=array();
	$alleInstanzen = IPS_GetInstanceListByModuleID('{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}');
	foreach ($alleInstanzen as $instanz)
		{
		$result=IPS_GetInstance($instanz);
		$WebfrontConfigID[IPS_GetName($instanz)]=$result["InstanceID"];
		echo "Webfront Konfigurator Name : ".str_pad(IPS_GetName($instanz),20)." ID : ".$result["InstanceID"]."  (".$instanz.")\n";
		//echo "  ".$instanz." ".IPS_GetProperty($instanz,'Address')." ".IPS_GetProperty($instanz,'Protocol')." ".IPS_GetProperty($instanz,'EmulateStatus')."\n";
		/* alle Instanzen dargestellt */
		//echo IPS_GetName($instanz)." ".$instanz." ".$result['ModuleInfo']['ModuleName']." ".$result['ModuleInfo']['ModuleID']."\n";
		//print_r($result);
		}
	echo "\n";
	
/*******************************
 *
 * Webfront Konfiguration einlesen
 
[RemoteVis]
Enabled=false

[WFC10]
Enabled=true
Path=Visualization.WebFront.Administrator.iTunes
TabPaneItem=iTunesTPA
TabPaneParent=roottp
TabPaneName=
TabPaneOrder=500
TabPaneIcon=
TabPaneExclusive=false
TabItem=Details
TabName="Lautsprecher"
TabIcon=
TabOrder=20

[WFC10User]
Enabled=true
Path=Visualization.WebFront.User.iTunes
TabPaneItem=iTunesTPU
TabPaneParent=roottp
TabPaneName=
TabPaneOrder=500
TabPaneIcon=
TabPaneExclusive=false
TabItem=Details
TabName="Lautsprecher"
TabIcon=
TabOrder=20

[Mobile]
Enabled=true
Path=Visualization.Mobile.iTunes

[Retro]
Enabled=false
Path=Visualization.Mobile.iTunes 
 
 *
 ********************************/	
	
	$RemoteVis_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'RemoteVis',false);

	$WFC10_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'WFC10',false);
	if ($WFC10_Enabled==true)
		{
		$WFC10_ConfigId       = $WebfrontConfigID["Administrator"];
		$WFC10_Path           = $moduleManager->GetConfigValue('Path', 'WFC10');
		$WFC10_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10',"iTunesTPA");
		$WFC10_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10',"roottp");
		$WFC10_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10',"");
		$WFC10_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10',"Music");
		$WFC10_TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10',200);
		$WFC10_TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10',"");
		$WFC10_TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10',"");
		$WFC10_TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10',"");
		$WFC10_TabOrder       = $moduleManager->GetConfigValueDef('TabOrder', 'WFC10',"");
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

	echo "\n";

	$WFC10User_Enabled    = $moduleManager->GetConfigValueDef('Enabled', 'WFC10User',false);
	if ($WFC10User_Enabled==true)
		{
		$WFC10User_ConfigId       = $WebfrontConfigID["User"];
		$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');
		$WFC10User_TabPaneItem    = $moduleManager->GetConfigValueDef('TabPaneItem', 'WFC10User',"iTunesTPU");
		$WFC10User_TabPaneParent  = $moduleManager->GetConfigValueDef('TabPaneParent', 'WFC10User',"roottp");
		$WFC10User_TabPaneName    = $moduleManager->GetConfigValueDef('TabPaneName', 'WFC10User',"");
		$WFC10User_TabPaneIcon    = $moduleManager->GetConfigValueDef('TabPaneIcon', 'WFC10User',"Music");
		$WFC10User_TabPaneOrder   = $moduleManager->GetConfigValueDef('TabPaneOrder', 'WFC10User',"");
		$WFC10User_TabItem        = $moduleManager->GetConfigValueDef('TabItem', 'WFC10User',"");
		$WFC10User_TabName        = $moduleManager->GetConfigValueDef('TabName', 'WFC10User',"");
		$WFC10User_TabIcon        = $moduleManager->GetConfigValueDef('TabIcon', 'WFC10User',"");
		$WFC10User_TabOrder       = $moduleManager->GetConfigValueIntDef('TabOrder', 'WFC10User',"");
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

	$Mobile_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Mobile',false);
	if ($Mobile_Enabled==true)
		{	
		$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
		echo "Mobile \n";
		echo "  Path          : ".$Mobile_Path."\n";		
		}

	$Retro_Enabled        = $moduleManager->GetConfigValueDef('Enabled', 'Retro',false);
	if ($Retro_Enabled==true)
		{	
		$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');
		echo "Retro \n";
		echo "  Path          : ".$Retro_Path."\n";		
		}	

	/*******************************
	 *
	 * Variablen Profile Vorbereitung
	 *
	 ********************************/

	$pname="AusEinAuto";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
		//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." erstellt;\n";
		}

	$pname="AusEin";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 1, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, 1, "Ein", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		//IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." erstellt;\n";
		}

	/******************************************************
	 *
	 * Timer Konfiguration
	 *
	 * Wecker programmierung ist bei GutenMorgen Funktion
	 *
	 ***********************************************************************/


	/*******************************
	 *
	 * Links für Webfront identifizieren
	 *
	 * Webfront Links werden für alle Autosteuerungs Default Funktionen erfasst. Es werden auch gleich die 
	 * Default Variablen dazu angelegt
	 *
	 *
	 * funktioniert für jeden beliebigen Vartiablennamen, zumindest ein/aus Schalter wird angelegt
	 * [Auswertung] [ANchrichten] für Administrator Splitpane Links und rechts, User TabPane nur mit links
	 * [Name] oder [Name][Subname] kann vorher gesetzt werden
	 *
	 * Startpunkt ist ein TabPane auf der Hauptleiste oder einer Subleiste, definiert mit dem ini File und den 
	 *    Variablen $WFC10_TabPaneItem und $WFC10_TabPaneParent. Bei der Hauptleiste sollte nur ein Icon angegeben werden.
	 *    es kann aber auch bei einer bestehenden Hauptleiste untergemietet werden
	 *
	 ********************************/

	echo "\n";
	echo "===================================================\n";
	echo "Konfiguration aus iTunes_Configuration ausgeben:\n";
	$config=iTunes_Configuration();
	print_r($config);

	$webfront_links=array();
    if (isset($config["Media"]))			// war früher in der Config iTunes
        {
        $order=10;            
    	foreach ($config["Media"] as $name => $entry)
	    	{
		    if (isset($entry["NAME"])==false) $entry["NAME"]=$name;
    		if (isset($entry["PROFILE"])==false) $entry["PROFILE"]="";
            $tabname="Media";
	    	$AutosteuerungID = CreateVariable($entry["NAME"], 1, $categoryId_iTunes, 1, $entry["PROFILE"],$scriptIdWebfrontControl,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
		    $webfront_links[$tabname]["Auswertung"][$AutosteuerungID]["TAB"]="iTunes";
		    $webfront_links[$tabname]["Auswertung"][$AutosteuerungID]["NAME"]=$entry["NAME"];
            $webfront_links[$tabname]["Auswertung"][$AutosteuerungID]["ORDER"]=$order;

    		$webfront_links[$tabname]["Auswertung"][$AutosteuerungID]["ADMINISTRATOR"]=true;
	    	$webfront_links[$tabname]["Auswertung"][$AutosteuerungID]["USER"]=true;
		    $webfront_links[$tabname]["Auswertung"][$AutosteuerungID]["MOBILE"]=true;
            $order+=10;
		    }
    	$webfront_links[$tabname]["Nachrichten"][$iTunes_NachrichtenInput]["NAME"]="Nachrichten";
	    $webfront_links[$tabname]["Nachrichten"][$iTunes_NachrichtenInput]["ORDER"]=10;            

	    $webfront_links[$tabname]["Nachrichten"][$iTunes_NachrichtenInput]["ADMINISTRATOR"]=true;        
	    $webfront_links[$tabname]["Nachrichten"][$iTunes_NachrichtenInput]["USER"]=false;        
	    $webfront_links[$tabname]["Nachrichten"][$iTunes_NachrichtenInput]["MOBILE"]=false;        
        }
    if ( (isset($config["Alexa"])) && isset($installedModules["iTunesSteuerung"]) )         // Modul und Config müssen passen
        {
        $order=20;            
    	foreach ($config["Alexa"] as $name => $entry)
			{
        	if (isset($entry["NAME"])==false) $config["Alexa"]["NAME"]="Alexa";            
			}
		if (isset($config["Alexa"]["NAME"])==false) $config["Alexa"]["NAME"]="Alexa"; 	 
        $categoryId_Alexa  = CreateCategory("Alexa", $CategoryIdData, $order); 
        $modulhandling = new ModuleHandling();		// true bedeutet mit Debug
        $echos=$modulhandling->getInstances('EchoRemote');
        echo "Alexa Echo Geräte anlegen.\n";
        foreach ($echos as $echo)
            {
            $name=IPS_GetName($echo);
            echo "   ".$echo."  (".$name.")";
            if ($name=="Dieses Gerät")
                {
                echo "   ->   eigene IP Symcon Instanz nicht visualisieren.";    
                }
            else
                {    /* es werden derzeit nur Index, NAME und ORDER ausgewertet */
                $order=100;                        
	            $tabname="Alexa";
                $webfront_links[$tabname][$name]["Auswertung"][$echo]["NAME"]=$name;        
            	$webfront_links[$tabname][$name]["Auswertung"][$echo]["ADMINISTRATOR"]=true;
	            $webfront_links[$tabname][$name]["Auswertung"][$echo]["USER"]=false;
        		$webfront_links[$tabname][$name]["Auswertung"][$echo]["MOBILE"]=false;                  
                $webfront_links[$tabname][$name]["Auswertung"][$echo]["ORDER"]=$order;
                $order+=10;
                }
            echo "\n";    
            }
        }
    if ( (isset($config["NetPlayer"])) && isset($installedModules["NetPlayer"]) )
		{
        $order=30;            
    	foreach ($config["NetPlayer"] as $name => $entry)
			{
        	if (isset($entry["NAME"])==false) $config["NetPlayer"]["NAME"]="NetPlayer";            
			}
		if (isset($config["NetPlayer"]["NAME"])==false) $config["NetPlayer"]["NAME"]="NetPlayer"; 	 
		$NetPlayerID  = $CategoryIdDataNP;
	    $tabname="NetPlayer";
		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["TAB"]="NetPlayer";
		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["NAME"]=$config["NetPlayer"]["NAME"];
        $webfront_links[$tabname]["Auswertung"][$NetPlayerID]["ORDER"]=$order;

		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["ADMINISTRATOR"]=true;
		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["USER"]=true;
		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["MOBILE"]=true;
		}
	if (isset($config["iTunesSteuerung"]))			// war früher in der Config iTunes
        {
		/* damit kann man iTunes fernsteuern */
		
		}
    if (true)
        {
        /*
        $order=30;            
    	foreach ($config["NetPlayer"] as $name => $entry)
			{
        	if (isset($entry["NAME"])==false) $config["NetPlayer"]["NAME"]="NetPlayer";            
			}
		if (isset($config["NetPlayer"]["NAME"])==false) $config["NetPlayer"]["NAME"]="NetPlayer"; 	 
		$NetPlayerID  = $CategoryIdDataNP;
	    $tabname="NetPlayer";
		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["TAB"]="NetPlayer";
		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["NAME"]=$config["NetPlayer"]["NAME"];
        $webfront_links[$tabname]["Auswertung"][$NetPlayerID]["ORDER"]=$order;

		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["ADMINISTRATOR"]=true;
		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["USER"]=true;
		$webfront_links[$tabname]["Auswertung"][$NetPlayerID]["MOBILE"]=true;
        */
        }
	echo "Webfront Visualisierungskonfiguration ausgeben:\n"; print_r($webfront_links);
	
	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Administrator Installation
     *
     * Algorithmen siehe auch CustomComponent_Installation 
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	 
	if ($WFC10_Enabled)
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen, redundant sollte in allen Install sein um gleiche Strukturen zu haben 
		 *
		 * typische Struktur, festgelegt im ini File:
		 *
		 * roottp/AutoTPA (Autosteuerung)/AutoTPADetails und /AutoTPADetails2
		 *
		 */
		
		echo "====================================================================================\n";		
        $categoryId_AdminWebFront=CreateCategoryPath("Visualization.WebFront.Administrator");
		echo "Webportal Administrator im Webfront Konfigurator ID ".$WFC10_ConfigId." installiert in Kategorie ". $categoryId_AdminWebFront." (".IPS_GetName($categoryId_AdminWebFront).")\n";

		/*************************************
		 * Ordnung machen, hat sicher bereits das CustomComponent_Installation Modul bereits erledigt */

		  //Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		  //CreateWFCItemCategory  ($WFC10_ConfigId, 'Admin',   "roottp",   10, IPS_GetName(0).'-Admin', '', $categoryId_AdminWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);
		  //@WFC_UpdateVisibility ($WFC10_ConfigId,"root",false	);				
		  //@WFC_UpdateVisibility ($WFC10_ConfigId,"dwd",false	);

		/*************************************/

		/* Parameter WebfrontConfigId, TabName, TabPaneItem,  Position, TabPaneName, TabPaneIcon, $category BaseI, BarBottomVisible */
		echo "Webfront TabPane mit    Parameter ConfigID:".$WFC10_ConfigId.",Item:".$WFC10_TabPaneItem.",Parent:".$WFC10_TabPaneParent.",Order:".$WFC10_TabPaneOrder.",Name:".$WFC10_TabPaneName.",Icon:".$WFC10_TabPaneIcon."\n";        
		echo "***** Tabpane ".$WFC10_TabPaneItem." erzeugen in ".$WFC10_TabPaneParent."\n";
        CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);    /* macht den Notenschlüssel in die oberste Leiste */

		/* Neue Tab für diese untergeordnete Anzeigen schaffen */
		echo "Webportal Administrator.iTunes Steuerung Datenstruktur installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10_Path);
		//EmptyCategory($categoryId_WebFrontAdministrator);
		/* in der normalen Viz Darstellung verstecken */
		IPS_SetHidden($categoryId_WebFrontAdministrator, true); //Objekt verstecken

		/*************************************/

        if (array_key_exists("Auswertung",$webfront_links) ) 
            {

            }
        else
            {    
    		foreach ($webfront_links as $Name => $webfront_group)
	    	    {
		    	/* Das erste Arrayfeld bestimmt die Tabs in denen jeweils ein linkes und rechtes Feld erstellt werden: Bewegung, Feuchtigkeit etc.
			     * Der Name für die Felder wird selbst erfunden.
    			 */

                echo "\n**** iTunes Visualization, erstelle Kategorie ".$Name." in ".$categoryId_WebFrontAdministrator." (".IPS_GetName($categoryId_WebFrontAdministrator)."/".IPS_GetName(IPS_GetParent($categoryId_WebFrontAdministrator)).").\n";
		    	$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontAdministrator, 10);
			    EmptyCategory($categoryId_WebFrontTab);   
                echo "Kategorien erstellt, Main install for ".$Name." : ".$categoryId_WebFrontTab." in ".$categoryId_WebFrontAdministrator." Kategorie Inhalt geloescht.\n";

	    		$tabItem = $WFC10_TabPaneItem.$Name;				/* Netten eindeutigen Namen berechnen */
                deletePane($WFC10_ConfigId, $tabItem);              /* Spuren von vormals beseitigen */

				//print_r($webfront_group);
                if (array_key_exists("Auswertung",$webfront_group) ) 
                    {
  			    	echo "**Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";
					if (array_key_exists("Nachrichten",$webfront_group) )
						{
                    	createSplitPane($WFC10_ConfigId,$webfront_group,$Name,$tabItem,$WFC10_TabPaneItem,$categoryId_WebFrontTab,"Administrator");
						}
					else
						{	
	                    //CreateWFCItemTabPane   ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,  $WFC10_TabPaneOrder, $Name, "");    
						foreach ($webfront_group as $Group => $webfront_link)
							{
							foreach ($webfront_link as $OID => $link)
								{
								/* Hier erfolgt die Aufteilung auf linkes und rechtes Feld
						 		 * Auswertung kommt nach links und Nachrichten nach rechts
						 		 */	
								//echo $OID."  "; print_r($link);				
								echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
								if ($Group=="Auswertung")
							 		{
			 						echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryId_WebFrontTab."\n";
									CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontTab,  $link["ORDER"]);						
									CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,   $WFC10_TabPaneOrder, '', '', $categoryId_WebFrontTab   /*BaseId*/, 'false' /*BarBottomVisible*/);
									}
								}
							}
						}
                    }
                else
                    {
        		    foreach ($webfront_group as $SubName => $webfront_subgroup)
	        	        {                    
                        /* noch eine Zwischenebene an Tabs einführen */
                        echo "\n  **iTunes Visualization, erstelle Sub Kategorie ".$SubName." in ".$categoryId_WebFrontTab.".\n";
			            $categoryId_WebFrontSubTab         = CreateCategory($SubName,$categoryId_WebFrontTab, 10);
			            EmptyCategory($categoryId_WebFrontSubTab);   
                        echo "Kategorien erstellt, Sub install for ".$SubName." : ".$categoryId_WebFrontSubTab." in ".$categoryId_WebFrontTab." Kategorie Inhalt geloescht.\n";

            			$tabSubItem = $WFC10_TabPaneItem.$Name.$SubName;				/* Netten eindeutigen Namen berechnen */
                        deletePane($WFC10_ConfigId, $tabSubItem);              /* Spuren von vormals beseitigen */

                		echo "***** Tabpane ".$tabItem." erzeugen in ".$WFC10_TabPaneItem."\n";
                        CreateWFCItemTabPane   ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,  $WFC10_TabPaneOrder, $Name, "");    /* macht den Notenschlüssel in die oberste Leiste */

			            echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$tabSubItem." in ".$tabItem."\n"; 
                        createSplitPane($WFC10_ConfigId,$webfront_subgroup,$SubName,$tabSubItem,$tabItem,$categoryId_WebFrontSubTab,"Administrator");    
                        }
                    }    
    			}  // ende foreach
            }    
		}

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront User Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	 
	if ( ($WFC10User_Enabled) )
		{
		/* Kategorien werden angezeigt, eine allgemeine für alle Daten in der Visualisierung schaffen */

		echo "====================================================================================\n";
		$categoryId_UserWebFront=CreateCategoryPath("Visualization.WebFront.User");
		echo "\nWebportal User Kategorie im Webfront Konfigurator ID ".$WFC10User_ConfigId." installiert in: ". $categoryId_UserWebFront." ".IPS_GetName($categoryId_UserWebFront)."\n";

        /*************************************
         * Ordnung machen wahrscheinlich schon in custom_components erledigt */
		  //CreateWFCItemCategory  ($WFC10User_ConfigId, 'User',   "roottp",   0, IPS_GetName(0).'-User', '', $categoryId_UserWebFront   /*BaseId*/, 'true' /*BarBottomVisible*/);
		  //@WFC_UpdateVisibility ($WFC10User_ConfigId,"root",false	);				
          //@WFC_UpdateVisibility ($WFC10User_ConfigId,"dwd",false	);
		
		/* Neue Tab für untergeordnete Anzeigen wie eben LocalAccess und andere schaffen */
		echo "Webfront TabPane mit    Parameter ConfigID:".$WFC10User_ConfigId.",Item:".$WFC10User_TabPaneItem.",Parent:".$WFC10User_TabPaneParent.",Order:".$WFC10User_TabPaneOrder."Name:".$WFC10User_TabPaneName.",Icon:".$WFC10User_TabPaneIcon."\n";        
		echo "***** Tabpane ".$WFC10User_TabPaneItem." erzeugen in ".$WFC10User_TabPaneParent."\n";        
		CreateWFCItemTabPane   ($WFC10User_ConfigId,  $WFC10User_TabPaneItem, $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);     /* macht den Notenschlüssel in die oberste Leiste */

		$categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
		IPS_SetHidden($categoryId_WebFrontUser,true);
		
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_WebFrontUser, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

			$tabItem = $WFC10User_TabPaneItem.$Name;
            deletePane($WFC10User_ConfigId, $tabItem);              /* Spuren von vormals beseitigen */

            if (array_key_exists("Auswertung",$webfront_group) ) 
                {
			    echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$tabItem." in ".$WFC10_TabPaneItem."\n";
                createSplitPane($WFC10User_ConfigId,$webfront_group,$Name,$tabItem,$WFC10User_TabPaneItem,$categoryId_WebFrontTab,"User");
                }
            else
                {
    		    foreach ($webfront_group as $SubName => $webfront_subgroup)
	    	        {                    
                    /* noch eine Zwischenebene an Tabs einführen */
                    echo "\n  **** iTunes Visualization, erstelle Sub Kategorie ".$SubName." in ".$categoryId_WebFrontTab.".\n";
			        $categoryId_WebFrontSubTab         = CreateCategory($SubName,$categoryId_WebFrontTab, 10);
			        EmptyCategory($categoryId_WebFrontSubTab);   
                    echo "Kategorien erstellt, Sub install for ".$SubName." : ".$categoryId_WebFrontSubTab." in ".$categoryId_WebFrontTab." Kategorie Inhalt geloescht.\n";

        			$tabSubItem = $WFC10User_TabPaneItem.$Name.$SubName;				/* Netten eindeutigen Namen berechnen */
                    deletePane($WFC10User_ConfigId, $tabSubItem);              /* Spuren von vormals beseitigen */

            		echo "***** Tabpane ".$tabItem." erzeugen in ".$WFC10_TabPaneItem."\n";
                    CreateWFCItemTabPane   ($WFC10User_ConfigId, $tabItem, $WFC10User_TabPaneItem,  $WFC10User_TabPaneOrder, $Name, "");    /* macht den Notenschlüssel in die oberste Leiste */

			        echo "Webfront ".$WFC10User_ConfigId." erzeugt TabItem :".$tabSubItem." in ".$tabItem."\n"; 
                    createSplitPane($WFC10User_ConfigId,$webfront_subgroup,$SubName,$tabSubItem,$tabItem,$categoryId_WebFrontSubTab,"User");    
                    }
                }    
			}
		}
	else
	   {
	   /* User not enabled, alles loeschen 
	    * leider weiss niemand so genau wo diese Werte gespeichert sind. Schuss ins Blaue mit Fehlermeldung, da Variablen gar nicht definiert isnd
		*/
	   DeleteWFCItems($WFC10User_ConfigId, "HouseTPU");
       $categoryId_WebFrontUser         = CreateCategoryPath($WFC10User_Path);
	   EmptyCategory($categoryId_WebFrontUser);
	   }

	if ( ($Mobile_Enabled) )
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_MobileWebFront         = CreateCategoryPath($Mobile_Path);
		IPS_SetHidden($categoryId_MobileWebFront,false);	/* mus dargestellt werden, sonst keine Anzeige am Mobiltelefon */	
			
		foreach ($webfront_links as $Name => $webfront_group)
		   {
			$categoryId_WebFrontTab         = CreateCategory($Name,$categoryId_MobileWebFront, 10);
			EmptyCategory($categoryId_WebFrontTab);
			echo "Kategorien erstellt, Main für ".$Name." : ".$categoryId_WebFrontTab."\n";

            if (array_key_exists("Auswertung",$webfront_group) ) 
                {
    			foreach ($webfront_group as $Group => $webfront_link)
	    			 {
		    		foreach ($webfront_link as $OID => $link)
			    		{
				    	echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					    if ($Group=="Auswertung")
				 		    {
    				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryId_WebFrontTab."\n";
	    					CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontTab,  20);
		    		 		}
			    		}
    			    }
                }
            else
                {
    		    foreach ($webfront_group as $SubName => $webfront_subgroup)
	    	        {                    
                    /* noch eine Zwischenebene an Tabs einführen */
                    echo "\n  **** iTunes Visualization, erstelle Sub Kategorie ".$SubName." in ".$categoryId_WebFrontTab.".\n";
			        $categoryId_WebFrontSubTab         = CreateCategory($SubName,$categoryId_WebFrontTab, 10);
			        EmptyCategory($categoryId_WebFrontSubTab);   
                    echo "Kategorien erstellt, Sub install for ".$SubName." : ".$categoryId_WebFrontSubTab." in ".$categoryId_WebFrontTab." Kategorie Inhalt geloescht.\n";
        			foreach ($webfront_subgroup as $Group => $webfront_link)
	    			    {
		    		    foreach ($webfront_link as $OID => $link)
			    		    {
				    	    echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					        if ($Group=="Auswertung")
				 		        {
    				 		    echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryId_WebFrontSubTab."\n";
	    					    CreateLinkByDestination($link["NAME"], $OID,    $categoryId_WebFrontSubTab,  20);
		    		 		    }   
    			    		}
        			    }

                    }
                }        
			}
		}
	else
	   {
	   /* Mobile not enabled, alles loeschen */
	   }

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_RetroWebFront         = CreateCategoryPath($Retro_Path);
		}
	else
	   {
	   /* Retro not enabled, alles loeschen */
	   }

	ReloadAllWebFronts();

/****************************************************************************************************************
 *
 *  Übernommen vom Netplayer, war nicht mehr richtig programmiert
 *
 ****************************************************************************************************************/


	// ----------------------------------------------------------------------------------------------------------------------------
	// Program Installation
	// ----------------------------------------------------------------------------------------------------------------------------

	echo "\n";
	echo "--- Create NetPlayer -------------------------------------------------------------------\n";
	echo "\n";
	

	// Scripts
	$actionScriptId = IPS_GetScriptIDByName('NetPlayer_ActionScript',  $CategoryIdAppNP);
	$eventScriptId  = IPS_GetScriptIDByName('NetPlayer_EventScript',   $CategoryIdAppNP);

	// Controls
	CreateProfile_Associations ('NetPlayer_Category',    array('Root'));
	CreateProfile_Associations ('NetPlayer_CDAlbumList', array('...'));
	CreateProfile_Associations ('NetPlayer_CDAlbumNav',  array('<<','>>'));
	CreateProfile_Associations ('NetPlayer_CDTrackList', array('...'));
	CreateProfile_Associations ('NetPlayer_CDTrackList2', array('xx', 'yyy'));
	CreateProfile_Associations ('NetPlayer_CDTrackNav',  array('<<','>>'));
	CreateProfile_Associations ('NetPlayer_RadioList',   array('...'));
	CreateProfile_Associations ('NetPlayer_RadioNav',    array('<<','>>'));
	CreateProfile_Associations ('NetPlayer_Control',     array('Play','Pause','Stop','<<','>>'));
	CreateProfile_Associations ('NetPlayer_Source',      array('CD Player','Radio Player'));

	// MP3 Player
	$mp3PlayerInstanceId     = CreateInstance("CDPlayer", $CategoryIdDataNP, "{485D0419-BE97-4548-AA9C-C083EB82E61E}",1000);
	$categoryId       = CreateVariable("Category",        1 /*Integer*/,  $CategoryIdDataNP, 150 , 'NetPlayer_Category', $actionScriptId, 0);
	$cdCategoryNameId = CreateVariable("CategoryName",    3 /*String*/,  $mp3PlayerInstanceId, 10 , '~TextBox', null/*NoAS*/, "");
	$cdIdxId          = CreateVariable("DirectoryIdx",    1 /*Integer*/, $mp3PlayerInstanceId, 20 , '',         null/*NoAS*/,  0);
	$cddirectoryPath  = CreateVariable("DirectoryPath",   3 /*String*/,  $mp3PlayerInstanceId, 30 , '~TextBox');
	$cddirectoryName  = CreateVariable("DirectoryName",   3 /*String*/,  $mp3PlayerInstanceId, 40 , '~TextBox');
	$cdTrackListHtmlId= CreateVariable("TrackListHtml",   3 /*String*/,  $mp3PlayerInstanceId, 50 , '~HTMLBox');
	$cdTrackIdxId     = CreateVariable("TrackIdx",        1 /*Integer*/, $mp3PlayerInstanceId, 60 , '',         null/*NoAS*/, 0);

	// WebRadio
	$webRadioInstanceId     = CreateInstance("RadioPlayer", $CategoryIdDataNP, "{485D0419-BE97-4548-AA9C-C083EB82E61E}",1010);
	$radioNameId     = CreateVariable("Name", 3 /*String*/,   $webRadioInstanceId, 10 , '~TextBox');
	$radioUrlId      = CreateVariable("Url",  3 /*String*/,   $webRadioInstanceId, 20 , '~TextBox');
	$radioIdxId      = CreateVariable("Idx",  1 /*Integer*/,  $webRadioInstanceId, 30 , '', null/*NoAS*/, 0);

	$powerId               = CreateVariable("Power",           0 /*Boolean*/,  $CategoryIdDataNP, 100 , '~Switch', $actionScriptId, 0);
	$sourceId              = CreateVariable("Source",          1 /*Integer*/,  $CategoryIdDataNP, 110 , 'NetPlayer_Source', $actionScriptId, 0 /*CD*/);
	$controlId             = CreateVariable("Control",         1 /*Integer*/,  $CategoryIdDataNP, 120 , 'NetPlayer_Control', $actionScriptId, 2 /*Stop*/);
	$albumId               = CreateVariable("Album",           3 /*String*/,   $CategoryIdDataNP, 130, '');
	$interpretId           = CreateVariable("Interpret",       3 /*String*/,   $CategoryIdDataNP, 140, '');
	$categoryId            = CreateVariable("Category",        1 /*Integer*/,  $CategoryIdDataNP, 150 , 'NetPlayer_Category', $actionScriptId, 0);
	$cdAlbumNavId          = CreateVariable("CDAlbumNav",      1 /*Integer*/,  $CategoryIdDataNP, 160 , 'NetPlayer_CDAlbumNav', $actionScriptId, -1);
	$cdAlbumListId         = CreateVariable("CDAlbumList",     1 /*Integer*/,  $CategoryIdDataNP, 170 , 'NetPlayer_CDAlbumList', $actionScriptId, -1);
	$cdTrackNavId          = CreateVariable("CDTrackNav",      1 /*Integer*/,  $CategoryIdDataNP, 180 , 'NetPlayer_CDTrackNav', $actionScriptId, -1);
	$cdTrackListId         = CreateVariable("CDTrackList",     1 /*Integer*/,  $CategoryIdDataNP, 190 , 'NetPlayer_CDTrackList', $actionScriptId, -1);
	$radioNavId            = CreateVariable("RadioNav",        1 /*Integer*/,  $CategoryIdDataNP, 200 , 'NetPlayer_RadioNav', $actionScriptId, -1);
	$radioListId           = CreateVariable("RadioList",       1 /*Integer*/,  $CategoryIdDataNP, 210 , 'NetPlayer_RadioList', $actionScriptId,-1);
	$controlTypeId         = CreateVariable("ControlType",     1 /*Integer*/,  $CategoryIdDataNP, 300 , '', null, 0);
	$remoteControlId       = CreateVariable("RemoteControl",   3 /*String*/,   $CategoryIdDataNP, 310 , '~HTMLBox', null, '<iframe frameborder="0" width="100%" src="../user/NetPlayer/NetPlayer_MP3Control.php" height=255px </iframe>');
	$mobileControlId       = CreateVariable("MobileControl",   3 /*String*/,   $CategoryIdDataNP, 320 , '~HTMLBox', null, '<iframe frameborder="0" width="100%" src="../user/NetPlayer/NetPlayer_Mobile.php" height=1000px </iframe>');

    echo "SetVariableConstant Netplayer ID Constants.\n";

	// Register Variable Constants
	SetVariableConstant ("NP_ID_CDCATEGORYNAME",  $cdCategoryNameId,       'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDDIRECTORYPATH", $cddirectoryPath,        'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDDIRECTORYNAME", $cddirectoryName,        'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDDIRECTORYIDX",  $cdIdxId,                'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDTRACKLISTHTML", $cdTrackListHtmlId,      'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDTRACKIDX",      $cdTrackIdxId,           'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');

	SetVariableConstant ("NP_ID_RADIONAME",       $radioNameId,            'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_RADIOURL",        $radioUrlId,             'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_RADIOIDX",        $radioIdxId,             'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');

	SetVariableConstant ("NP_ID_POWER",           $powerId,                'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_REMOTECONTROL",   $remoteControlId,        'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_MOBILECONTROL",   $mobileControlId,        'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CONTROLTYPE",     $controlTypeId,          'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ('"NP_ID_CDALBUM"',       $albumId,                'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDINTERPRET",     $interpretId,            'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');

	SetVariableConstant ("NP_ID_CATEGORYLIST",    $categoryId,             'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDALBUMLIST",     $cdAlbumListId,          'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDALBUMNAV",      $cdAlbumNavId,           'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ('"NP_ID_CDTRACKLIST"',   $cdTrackListId,          'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_CDTRACKNAV",      $cdTrackNavId,           'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_RADIOLIST",       $radioListId,            'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_RADIONAV",        $radioNavId,             'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ("NP_ID_SOURCE",          $sourceId,               'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');
	SetVariableConstant ('"NP_ID_CONTROL"',       $controlId,              'NetPlayer_IDs.inc.php', 'IPSLibrary::app::modules::NetPlayer');

    echo "Installation of Components:\n";

	// Installation of Components
	IPSUtils_Include ("NetPlayer_Constants.inc.php",     "IPSLibrary::app::modules::NetPlayer");
	IPSUtils_Include ("NetPlayer_Configuration.inc.php", "IPSLibrary::config::modules::NetPlayer");

	$params = explode(',',NETPLAYER_COMPONENT);
	if ($params[0] == 'IPSComponentPlayer_Mediaplayer') {
        print_r($params);
	    if (!is_numeric($params[1])) {
	        $pathItems = explode('.',$params[1]);
	        $mediaPlayerName = $pathItems[count($pathItems)-1];
	        unset($pathItems[count($pathItems)-1]);
	        $path = implode('.', $pathItems);
			$categoryId  = CreateCategoryPath($path);

			// Create MediaPlayer
            echo "Create Media Player $mediaPlayerName in Category $categoryId . Soundcards available :\n";
			$Position=0;
   		    //$mediaPlayerInstanceId   = CreateMediaPlayer($mediaPlayerName, $categoryId, $Position);
			$MediaPlayerInstanceId = CreateInstance($mediaPlayerName, $categoryId, "{2999EBBB-5D36-407E-A52B-E9142A45F19C}",$Position);
			echo "   Mediaplayer Instance created : $MediaPlayerInstanceId \n"; 
			//$SoundCards = WAC_GetDevices($MediaPlayerInstanceId);
			$result = Array();
			$forms=IPS_GetConfigurationForm($MediaPlayerInstanceId);
			$forms=utf8_encode($forms);			
			$json = json_decode($forms);
			echo "   Ergebnis json_decode ConfigurationForm ";
		    switch (json_last_error()) 
				{
        		case JSON_ERROR_NONE:
		            echo ' - No errors';
			        break;
		        case JSON_ERROR_DEPTH:
		            echo ' - Maximum stack depth exceeded';
			        break;
		        case JSON_ERROR_STATE_MISMATCH:
		            echo ' - Underflow or the modes mismatch';
			        break;
		        case JSON_ERROR_CTRL_CHAR:
		            echo ' - Unexpected control character found';
		    	    break;
		        case JSON_ERROR_SYNTAX:
		            echo ' - Syntax error, malformed JSON';
		        	break;
		        case JSON_ERROR_UTF8:
		            echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
		        	break;
		        default:
		            echo ' - Unknown error';
		        	break;
		    	}
			echo "\n";
			
/*			$jsonIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator(json_decode($json, TRUE)),RecursiveIteratorIterator::SELF_FIRST);
			foreach ($jsonIterator as $key => $val) {
			    if(is_array($val)) {
      		  		echo "$key:\n";
    			} else {
        			echo "$key => $val\n";
    			}
			}  */
			
			foreach($json->elements as $element)
				{
				if(isset($element->name) && ($element->name == "Device"))
					{
					foreach($element->options as $option)
						$result[] = $option->label;
					}
				}
			$SoundCards=$result;
			echo "Soundcards:\n";
			print_r($SoundCards);
			foreach ($SoundCards as $Idx=>$SoundCard) 
				{
				if ($SoundCard <> "No sound") {
				Debug ("Set Soundcard $SoundCard");
				//WAC_SetDeviceID($MediaPlayerInstanceId, $Idx);
				
				$searchByName = function($properties, $name) {
					foreach($properties as $property) {
						if($property->name == $name) {
							return $property->value;
						}
					}
				};
		
				foreach($json->elements as $element)
					{
					if(isset($element->name) && ($element->name == "Device"))
						{
						$option = $element->options[$Idx];
						IPS_SetProperty($MediaPlayerInstanceId, 'DeviceNum', $searchByName($option->value, 'DeviceNum'));
						IPS_SetProperty($MediaPlayerInstanceId, 'DeviceDriver', $searchByName($option->value, 'DeviceDriver'));
						IPS_SetProperty($MediaPlayerInstanceId, 'DeviceName', $searchByName($option->value, 'DeviceName'));
						}
					}	
				}
			}
			echo "Set Update interval:\n";
			WAC_SetUpdateInterval($MediaPlayerInstanceId, 1);
			IPS_ApplyChanges($MediaPlayerInstanceId);
			
            echo "   Media Player Instance created : $MediaPlayerInstanceId \n";
   		    $mediaPlayerTitel        = IPS_GetVariableIDByName('Titel', $MediaPlayerInstanceId);

   		    // Register Message Handler
            echo "Register Message Handler :\n";
			IPSMessageHandler::RegisterOnChangeEvent($mediaPlayerTitel/*Var*/, 'IPSComponentPlayer_MediaPlayer,'.$MediaPlayerInstanceId, 'IPSModulePlayer_NetPlayer');
	    }    
	}





/****************************************************************************************************************/

    /* Erzeuge ein Splitpane mit Name und den Links die in webfront_group angelegt sind in WFC10_TabPaneItem*/

    function createSplitPane($WFC10_ConfigId, $webfront_group, $Name, $tabItem, $WFC10_TabPaneItem,$categoryId_WebFrontSubTab,$scope="Administrator")
        {

		$categoryIdLeft  = CreateCategory('Left',  $categoryId_WebFrontSubTab, 10);
		$categoryIdRight = CreateCategory('Right', $categoryId_WebFrontSubTab, 20);
		echo "Kategorien erstellt, SubSub install for Left: ".$categoryIdLeft. " Right : ".$categoryIdRight."\n"; 

			echo "**** Splitpane $tabItem erzeugen in $WFC10_TabPaneItem:\n";
			/* @param integer $WFCId ID des WebFront Konfigurators
			 * @param string $ItemId Element Name im Konfigurator Objekt Baum
			 * @param string $ParentId Übergeordneter Element Name im Konfigurator Objekt Baum
			 * @param integer $Position Positionswert im Objekt Baum
			 * @param string $Title Title
			 * @param string $Icon Dateiname des Icons ohne Pfad/Erweiterung
			 * @param integer $Alignment Aufteilung der Container (0=horizontal, 1=vertical)
			 * @param integer $Ratio Größe der Container
			 * @param integer $RatioTarget Zuordnung der Größenangabe (0=erster Container, 1=zweiter Container)
			 * @param integer $RatioType Einheit der Größenangabe (0=Percentage, 1=Pixel)
	 		 * @param string $ShowBorder Zeige Begrenzungs Linie
			 */
			//CreateWFCItemTabPane   ($WFC10_ConfigId, $WFC10_TabPaneItem, $WFC10_TabPaneParent,  $WFC10_TabPaneOrder, $WFC10_TabPaneName, $WFC10_TabPaneIcon);
			CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10_TabPaneItem,    0,     $Name,     "", 1 /*Vertical*/, 40 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Left',   $tabItem,   10, '', '', $categoryIdLeft   /*BaseId*/, 'false' /*BarBottomVisible*/);
			CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'_Right',  $tabItem,   20, '', '', $categoryIdRight  /*BaseId*/, 'false' /*BarBottomVisible*/);            

			foreach ($webfront_group as $Group => $webfront_link)
				{
				//print_r($webfront_link );
				foreach ($webfront_link as $OID => $link)
					{
					/* Hier erfolgt die Aufteilung auf linkes und rechtes Feld
			 		 * Auswertung kommt nach links und Nachrichten nach rechts
			 		 */	
					//echo $OID."  "; print_r($link);				
					echo "  bearbeite Link ".$Name.".".$Group.".".$link["NAME"]." mit OID : ".$OID."\n";
					if ($Group=="Auswertung")
				 		{
                        if ( (($scope=="Administrator") && $link["ADMINISTRATOR"]) || (($scope=="User") && $link["USER"]) || (($scope=="Mobile") && $link["MOBILE"]) )
                            {
				 		    echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdLeft."\n";
						    CreateLinkByDestination($link["NAME"], $OID,    $categoryIdLeft,  $link["ORDER"]);
                            }
				 		}
				 	if ($Group=="Nachrichten")
				 		{
                        if ( (($scope=="Administrator") && $link["ADMINISTRATOR"]) || (($scope=="User") && $link["USER"]) || (($scope=="Mobile") && $link["MOBILE"]) )
                            {
    				 		echo "erzeuge Link mit Name ".$link["NAME"]." auf ".$OID." in der Category ".$categoryIdRight."\n";
	    					CreateLinkByDestination($link["NAME"], $OID,    $categoryIdRight,  $link["ORDER"]);
                            }
						}
					} // ende foreach
                }  // ende foreach  
        }

    function deletePane($WFC10_ConfigId, $tabItem)
        {
			if ( exists_WFCItem($WFC10_ConfigId, $tabItem) )
			 	{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).") löscht TabItem : ".$tabItem."\n";
				DeleteWFCItems($WFC10_ConfigId, $tabItem);
				}
			else
				{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).") TabItem : ".$tabItem." nicht mehr vorhanden.\n";
				}	
			IPS_ApplyChanges ($WFC10_ConfigId);   /* wenn geloescht wurde dann auch uebernehmen, sonst versagt das neue Anlegen ! */
        }


/****************************************************************************************************************/
/****************************************************************************************************************/
/****************************************************************************************************************/


?>