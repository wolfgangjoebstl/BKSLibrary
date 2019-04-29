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

	/**@defgroup OperationCenter_Installation
	 *
	 * Script zur Unterstützung der Betriebsführung, installiert das OperationCenter
	 *
	 *
	 * @file          OperationCenter_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.1, 07.12.2014<br/>
	 **/


	/******************************************************
	 *
	 * INIT, Init
	 *
	 * Setup, define basic includes and variables, general for all modules
	 * besides the include files
	 *
	 *************************************************************/

    $startexec=microtime(true);     /* Laufzeitmessung */

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\OperationCenter\OperationCenter_Configuration.inc.php");
	IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
	
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('OperationCenter',$repository);
		}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');
	echo "IP Symcon Daten:\n";
	echo "  Kernelversion : ".IPS_GetKernelVersion()."\n";;
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "  IPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " Status ".$ergebnis."\n";
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "  IPSModulManager Version : ".$ergebnis."\n";
	$ergebnisVersion=$moduleManager->VersionHandler()->GetVersion('OperationCenter');
	echo "  OperationCenter Version : ".$ergebnisVersion."\n";

 	$installedModules = $moduleManager->GetInstalledModules();
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.="   ".str_pad($name,30)." ".$modules."\n";
		}
	//echo $inst_modules;
	
	$Heute=time();
	//$HeuteString=date("jnY_Hi",$Heute);
	$HeuteString=date("jnY",$Heute);
	echo "Heute  Datum ".$HeuteString."\n";
	
	if (isset ($installedModules["OperationCenter"])) 
		{
		$log_Install=new Logging("C:\Scripts\Install\Install".$HeuteString.".csv");
		$log_Install->LogMessage("Install Module OperationCenter. Aktuelle Version ist $ergebnisVersion.");
		}
		
	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * Evaluierung starten
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	$moduleManagerEH = new IPSModuleManager('EvaluateHardware',$repository);
	$CategoryIdAppEH      = $moduleManagerEH->GetModuleCategoryID('app');	
	$scriptIdEvaluateHardware   = IPS_GetScriptIDByName('EvaluateHardware', $CategoryIdAppEH);
	echo "\n";
	echo "Die Scripts sind auf               ".$CategoryIdAppEH."\n";
	echo "Evaluate Hardware hat die ScriptID ".$scriptIdEvaluateHardware." und wird jetzt gestartet. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	IPS_RunScriptWait($scriptIdEvaluateHardware);
	echo "Script Evaluate Hardware gestartet wurde mittlerweile abgearbeitet. Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";	
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

	$scriptIdOperationCenter   = IPS_GetScriptIDByName('OperationCenter', $CategoryIdApp);
	$scriptIdDiagnoseCenter   = IPS_GetScriptIDByName('DiagnoseCenter', $CategoryIdApp);

	$archiveHandlerID=IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

	/******************************************************
	 *
	 * Webfront Config einlesen
	 *
	 *************************************************************/    

	$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

	/******************************************************
	 *
	 *				INIT, Timer
	 *
	 * Timer so konfigurieren dass sie sich nicht in die Quere kommen. Es gibt
	 * mittlerweile 11 Timer die der Reihe nach ab ca. 1 Uhr aufgerufen werden. 
	 *
	 *************************************************************/

	echo "\nTimer programmieren :\n";
	
	$timer = new TimerHandling();
	//print_r($timer->listScriptsUsed());
	
	$tim4ID = @IPS_GetEventIDByName("SysPingTimer", $scriptIdOperationCenter);
	if ($tim4ID==false)
		{
		$tim4ID = IPS_CreateEvent(1);
		IPS_SetParent($tim4ID, $scriptIdOperationCenter);
		IPS_SetName($tim4ID, "SysPingTimer");
		IPS_SetEventCyclic($tim4ID,0,1,0,0,2,60);      /* alle 60 Minuten , Tägliche Ausführung, keine Auswertung, Datumstage, Datumstageintervall, Zeittyp-2-alle x Minute, Zeitintervall */
		IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);
		IPS_SetEventActive($tim4ID,true);
		echo "   Timer Event SysPingTimer neu angelegt. Timer 60 Minuten ist aktiviert.\n";
		}
	else
		{
		echo "   Timer Event SysPingTimer bereits angelegt. Timer 60 Minuten ist aktiviert.\n";
  		IPS_SetEventActive($tim4ID,true);
		IPS_SetEventCyclicTimeFrom($tim4ID,0,4,0);
  		}
  		
	$tim5ID = @IPS_GetEventIDByName("CyclicUpdate", $scriptIdOperationCenter);
	if ($tim5ID==false)
		{
		$tim5ID = IPS_CreateEvent(1);
		IPS_SetParent($tim5ID, $scriptIdOperationCenter);
		IPS_SetName($tim5ID, "CyclicUpdate");
		IPS_SetEventCyclic($tim5ID,4,1,0,12,0,0);    /* jeden 12. des Monats , Monatliche Ausführung, alle 1 Monate, Datumstage, Datumstageintervall,  */
		echo "   Timer Event CyclicUpdate neu angelegt. Timer jeden 12. des Monates ist aktiviert.\n";
		}
	else
		{
		echo "   Timer Event CyclicUpdate bereits angelegt. Timer jeden 12. des Monates ist aktiviert.\n";
  		IPS_SetEventActive($tim5ID,true);
  		}

	
	$tim1ID=$timer->CreateTimerOC("RouterAufruftimer",00,20);				/* Eventuell Router regelmaessig auslesen */	

	$tim10ID=$timer->CreateTimerOC("Maintenance",01,20);						/* Starte Maintanenance Funktionen */	
	$tim11ID=$timer->CreateTimerSync("MoveLogFiles",150);						/* Maintanenance Funktion: Move Log Files */	

	$tim2ID=$timer->CreateTimerSync("MoveCamFiles",150);
	$tim3ID=$timer->CreateTimerSync("RouterExectimer",150);
		
	$tim6ID=$timer->CreateTimerOC("CopyScriptsTimer",02,20);	
	$tim7ID=$timer->CreateTimerOC("FileStatus",03,50);
	$tim8ID=$timer->CreateTimerOC("SystemInfo",02,30);
	
	$tim9ID=$timer->CreateTimerOC("Homematic",02,40);	
  		
	/******************************************************

				INIT, iMacro Router auslesen

	*************************************************************/

	$OperationCenterConfig = OperationCenter_Configuration();
	$OperationCenterSetup = OperationCenter_Setup();
	//print_r($OperationCenterConfig);
	echo "\nRouter Erstellung der iMacro Programmierung:\n";
	foreach ($OperationCenterConfig['ROUTER'] as $router)
		{
        if ( (isset($router['STATUS'])) && ((strtoupper($router['STATUS']))!="ACTIVE") )
            {

            }
        else
            {
    		echo "  Router \"".$router['NAME']."\" vom Typ ".$router['TYP']." von ".$router['MANUFACTURER']." wird bearbeitet.\n";
	    	//print_r($router);
            switch strtoupper(($router["TYP"]))
                {
			    case 'MR3420':
                    echo "      iMacro Command-File für Router Typ MR3420 wird hergestellt.\n";
                    $handle2=fopen($OperationCenterSetup["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");
                    fwrite($handle2,'VERSION BUILD=8961227 RECORDER=FX'."\n");
                    fwrite($handle2,'TAB T=1'."\n");
                    fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
                    fwrite($handle2,'SET !ENCRYPTION NO'."\n");
                    fwrite($handle2,'ONLOGIN USER='.$router['USER'].' PASSWORD='.$router['PASSWORD']."\n");
                    fwrite($handle2,'URL GOTO=http://'.$router['IPADRESSE']."\n");
                    fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:System<SP>Tools'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:-<SP>Statistics'."\n");
                    fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=SELECT FORM=NAME:sysStatic ATTR=NAME:Num_per_page CONTENT=%100'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:NextPage'."\n");
                    fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:Refresh'."\n");
                    //fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");  /* Textfile speichert nicht die komplette Struktur */
                    fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
                    fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:Status'."\n");
                    fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."_Statistics\n");
                    fwrite($handle2,'TAB CLOSE'."\n");
                    fclose($handle2);
                    break;
		        case 'B2368':
                    echo "      iMacro Command-File für Router Typ B2368 wird hergestellt.\n";
                    $handle2=fopen($OperationCenterSetup["MacroDirectory"]."router_".$router['TYP']."_".$router['NAME'].".iim","w");
                    fwrite($handle2,'VERSION BUILD=8970419 RECORDER=FX'."\n");
                    fwrite($handle2,'TAB T=1'."\n");
                    //fwrite($handle2,'SET !EXTRACT_TEST_POPUP NO'."\n");
                    fwrite($handle2,'SET !ENCRYPTION NO'."\n");
                    //fwrite($handle2,'ONLOGIN USER=admin PASSWORD=cloudg06'."\n");
                    fwrite($handle2,'URL GOTO=http://'.$router['IPADRESSE']."\n");
                    //fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
                    fwrite($handle2,'REFRESH'."\n");
                    //fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:System<SP>Tools'."\n");
                    //fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:-<SP>Statistics'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:TEXT FORM=ID:login ATTR=ID:username CONTENT='.$router['USER']."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:PASSWORD FORM=ID:login ATTR=ID:userpassword CONTENT='.$router['PASSWORD']."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:BUTTON FORM=ID:login ATTR=*'."\n");
                    fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=SELECT FORM=NAME:sysStatic ATTR=NAME:Num_per_page CONTENT=%100'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:NextPage'."\n");
                    fwrite($handle2,'FRAME NAME="mainFrame"'."\n");
                    fwrite($handle2,'TAG POS=2 TYPE=A ATTR=TXT:'."\n");
                    //fwrite($handle2,'TAG POS=1 TYPE=INPUT:SUBMIT FORM=NAME:sysStatic ATTR=NAME:Refresh'."\n");
                    //fwrite($handle2,'SAVEAS TYPE=TXT FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");  /* Textfile speichert nicht die komplette Struktur */
                    //fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
                    fwrite($handle2,'SAVEAS TYPE=CPL FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."\n");
                    //fwrite($handle2,'FRAME NAME="bottomLeftFrame"'."\n");
                    fwrite($handle2,'FRAME F=0'."\n");
                    //fwrite($handle2,'TAG POS=1 TYPE=A ATTR=TXT:Status'."\n");
                    //fwrite($handle2,'SAVEAS TYPE=HTM FOLDER=* FILE=report_router_'.$router['TYP']."_".$router['NAME']."_Statistics\n");
                    fwrite($handle2,'TAG POS=1 TYPE=LI ATTR=TITLE:Logout&&CLASS:logout-icon<SP>logoutBtn&&TXT:'."\n");
                    fwrite($handle2,'TAG POS=1 TYPE=BUTTON ATTR=TXT:OK'."\n");
                    fwrite($handle2,'TAB CLOSE'."\n");
                    fclose($handle2);          
                    break;          
                //SetValue($ScriptCounterID,1);
                //IPS_SetEventActive($tim3ID,true);
                }
			}
		}

	/******************************************************

				INIT, Nachrichtenspeicher

	*************************************************************/


	IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
	$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);

	if ($_IPS['SENDER']=="Execute")
		{
		echo "\nNachrichtenspeicher ausgedruckt:\n";
		echo 	$log_OperationCenter->PrintNachrichten();
		}


	/******************************************************

				INIT, TraceRouteSpeicher

	*************************************************************/

	$categoryId_Route    = CreateCategory('TraceRouteVerlauf',   $CategoryIdData, 20);
	for ($i=1; $i<=20;$i++)
	   {
		$input = CreateVariable("RoutePoint".$i,3,$categoryId_Route, $i*5, "",null,null,""  );  /* Name Type ParentID Position */
		}

	/******************************************************

				INIT, SystemInfo

	*************************************************************/

	$categoryId_SystemInfo    = CreateCategory('SystemInfo',   $CategoryIdData, 230);

	/*******************************
     *
     * Webfront Vorbereitung, hier werden keine Webfronts mehr installiert, nur mehr konfigurierte ausgelesen
     *
     ********************************/

	echo "\n";
	
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
	$WFC10_ConfigId       = $moduleManager->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
	echo "Default WFC10_ConfigId fuer OperationCenter, wenn nicht definiert : ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.")\n\n";
	if (IPS_GetName($WFC10_ConfigId) != "Administrator")
		{
		$WFC10_ConfigId=$WebfrontConfigID["Administrator"];
		$WFC10User_ConfigId=$WebfrontConfigID["User"];
		echo "Default WFC10_ConfigId fuer OperationCenter auf ".IPS_GetName($WFC10_ConfigId)."  (".$WFC10_ConfigId.") geändert.\n\n";
		}
	echo "\n";

	/******************************************************
	 *
	 *  Webfront zusammenräumen
	 *
	 *******************************************************/
	
    if (isset($installedModules["IPSLight"])==true)
	    {  /* das IPSLight Webfront ausblenden, es bleibt nur die Glühlampe stehen */
    	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
	    $pos=strpos($WFC10_Path,"OperationCenter");
    	$ipslight_Path=substr($WFC10_Path,0,$pos)."IPSLight";
	    $categoryId_WebFront = CreateCategoryPath($ipslight_Path);
    	IPS_SetPosition($categoryId_WebFront,998);
	    IPS_SetHidden($categoryId_WebFront,true);
	    echo "   Administrator Webfront IPSLight auf : ".$ipslight_Path." mit OID : ".$categoryId_WebFront."\n";
	    }

    if (isset($installedModules["IPSPowerControl"])==true)
	    {  /* das IPSPower<Control Webfront ausblenden, es bleibt nur die Glühlampe stehen */
	    $WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');
	    $pos=strpos($WFC10_Path,"OperationCenter");
	    $ipslight_Path=substr($WFC10_Path,0,$pos)."IPSPowerControl";
	    $categoryId_WebFront = CreateCategoryPath($ipslight_Path);
	    IPS_SetPosition($categoryId_WebFront,997);
	    IPS_SetHidden($categoryId_WebFront,true);
	    echo "   Administrator Webfront IPSPowerControl auf : ".$ipslight_Path." mit OID : ".$categoryId_WebFront."\n";
	    }

	/******************************************************
	 *
	 *			INIT, Webcams FTP Folder auslesen und auswerten
	 *				Auch die Datenstruktur für den CamOverview und den Snapshot Overview hier erstellen
	 *				Webfront siehe weiter unten
	 *
	 *************************************************************/

	if (isset ($installedModules["IPSCam"]))
		{
		echo "\n"; 
		echo "=====================================================================================\n"; 
		echo "Modul IPSCam installiert. Im Verzeichnis Data die Variablen für Übersichtsdarstellungen Pics und Movies anlegen:\n"; 
		
		/* es werden für alle in IPSCam registrierten Webcams auch Zusammenfassungsdarstellungen angelegt.
		   OperationCenter kopiert alle 150 Sekunden die verfügbaren Cam Snapshot in ein eigenes für die Darstellung im
		   Webfront geeignetes Verzeichnis */ 

		$CategoryIdDataOverview=CreateCategory("Cams",$CategoryIdData,2000,"");
		echo $CategoryIdDataOverview."  ".IPS_GetName($CategoryIdDataOverview)."/".IPS_GetName(IPS_GetParent($CategoryIdDataOverview))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($CategoryIdDataOverview)))."/".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($CategoryIdDataOverview))))."\n";
		$CamTablePictureID=CreateVariable("CamTablePicture",3, $CategoryIdDataOverview,0,"~HTMLBox",null,null,"");
		$CamTableMovieID=CreateVariable("CamTableMovie",3, $CategoryIdDataOverview,0,"~HTMLBox",null,null,"");

		$repositoryIPS = 'https://raw.githubusercontent.com/brownson/IPSLibrary/Development/';
		$moduleManagerCam = new IPSModuleManager('IPSCam',$repositoryIPS);
		$ergebnisCam=$moduleManagerCam->VersionHandler()->GetVersion('IPSCam');
		echo "  IPSCam Module Version : ".$ergebnisCam."\n";
		$WFC10Cam_Enabled	= $moduleManagerCam->GetConfigValueDef('Enabled', 'WFC10',false);				

		/* Das ist das html Objekt das in den Wefront Frame eingebunden wird:
		
		<iframe frameborder="0" width="100%" height="542px"  src="../user/IPSCam/IPSCam_Camera.php"</iframe> 
		
		im verlinkten webfront wird zwischen mobile und normalem webfront unterschieden. Die Nummer der Kamera wird als erster Parameter mitgegben.
		Die Routine generiert mit echo den html code.
		
		$camManager->GetHTMLWebFront(cameraIdx, Size, ShowPreDefPosButtons, ShowCommandButtons, ShowNavigationButtons)

		*/
		$html="";
		SetValue($CamTablePictureID,$html);

		echo "\nFtp Folder für Webcams zusammenräumen.\n";

		IPSUtils_Include ("IPSCam_Constants.inc.php",         "IPSLibrary::app::modules::IPSCam");
		IPSUtils_Include ("IPSCam_Configuration.inc.php",     "IPSLibrary::config::modules::IPSCam");

		if (isset ($OperationCenterConfig['CAM']))
			{
			/* möglicherweise sind keine FTP Folders zum zusammenräumen definiert */
			foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
				{
				echo "Bearbeite Kamera : ".$cam_name." im Verzeichnis ".$cam_config['FTPFOLDER']."\n";
				$verzeichnis = $cam_config['FTPFOLDER'];
				$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
				if ($cam_categoryId==false)
				   {
					$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
					IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
					IPS_SetParent($cam_categoryId,$CategoryIdData);
					}
				$WebCam_LetzteBewegungID = CreateVariableByName($cam_categoryId, "Cam_letzteBewegung", 3); /* 0 Boolean 1 Integer 2 Float 3 String */
				$WebCam_PhotoCountID = CreateVariableByName($cam_categoryId, "Cam_PhotoCount", 1);
				AC_SetLoggingStatus($archiveHandlerID,$WebCam_PhotoCountID,true);
				AC_SetAggregationType($archiveHandlerID,$WebCam_PhotoCountID,1);      /* 0 normaler Wert 1 Zähler */
				IPS_ApplyChanges($archiveHandlerID);

				$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
				AC_SetLoggingStatus($archiveHandlerID,$WebCam_MotionID,true);
				AC_SetAggregationType($archiveHandlerID,$WebCam_MotionID,0);      /* normaler Wwert */
				IPS_ApplyChanges($archiveHandlerID);

				// Test, ob ein Verzeichnis angegeben wurde
				if ( is_dir ( $verzeichnis ))
					{
	   		 	// öffnen des Verzeichnisses
   		 		if ( $handle = opendir($verzeichnis) )
		    			{
	   	 			$count=0; $list="";
		        		/* einlesen des Verzeichnisses        	*/
			        	while (($file = readdir($handle)) !== false)
	   		     		{
   	   		  		if (is_dir($verzeichnis.$file)==false)
	        				   {
		        				$count++;
	   	     				$list .= $file."\n";
			   	     		}
							}
						echo "   Im Cam FTP Verzeichnis ".$verzeichnis." gibt es ".$count." neue Dateien.\n";
						echo "   Letzter Eintrag von ".GetValue($WebCam_LetzteBewegungID)."\n";
						//echo $list."\n";
						}
					} /* ende ifisdir */
				}  /* ende foreach */
			}
		}

	/******************************************************

				INIT SysPing Variablen und auf Archivierung setzen

	*************************************************************/

	echo "===========================================\n";
	echo "Sysping Variablen anlegen.\n";
	$subnet="10.255.255.255";
	$OperationCenter=new OperationCenter($subnet);
	$OperationCenterConfig = $OperationCenter->oc_Configuration;

	$categoryId_SysPing    = CreateCategory('SysPing',   $CategoryIdData, 200);

	if (isset ($installedModules["IPSCam"]))
		{
		foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
			{
			$StatusID = CreateVariableByName($categoryId_SysPing, "Cam_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	if (isset ($installedModules["LedAnsteuerung"]))
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\LedAnsteuerung\LedAnsteuerung_Configuration.inc.php");
		$device_config=LedAnsteuerung_Config();
		foreach ($device_config as $name => $config)
			{
			$StatusID = CreateVariableByName($categoryId_SysPing, "LED_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	if (isset ($installedModules["DENONsteuerung"]))
		{
		Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\DENONsteuerung\DENONsteuerung_Configuration.inc.php");
		$device_config=Denon_Configuration();
		foreach ($device_config as $name => $config)
			{
			$StatusID = CreateVariableByName($categoryId_SysPing, "Denon_".$name, 0); /* Category, Name, 0 Boolean 1 Integer 2 Float 3 String */
			AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
			AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
			}
		}

	foreach ($OperationCenterConfig['ROUTER'] as $cam_name => $cam_config)
		{
		$StatusID = CreateVariableByName($categoryId_SysPing, "Router_".$cam_name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
		AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
		}

	foreach ($OperationCenterConfig['INTERNET'] as $name => $config)
		{
		$StatusID = CreateVariableByName($categoryId_SysPing, "Internet_".$name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
		AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
		}

	if (isset ($installedModules["IPSWeatherForcastAT"]))
		{
		$StatusID = CreateVariableByName($categoryId_SysPing, "Server_Wunderground", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
		AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
		AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
		}

	if (isset ($installedModules["RemoteAccess"]))
		{
		echo "    Remote Access Server Status Information anlegen.\n";
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$remServer    = RemoteAccess_GetConfigurationNew();	// das wären nur die Server mit STATUS active und LOGGING enabled
		$remServer    = RemoteAccess_GetServerConfig();
		foreach ($remServer as $Name => $Server)
			{
			if (strtoupper($Server["STATUS"])=="ACTIVE") 
				{
				echo "       Server Name : ".$Name."\n";
				$StatusID = CreateVariableByName($categoryId_SysPing, "Server_".$Name, 0); /* 0 Boolean 1 Integer 2 Float 3 String */
				AC_SetLoggingStatus($archiveHandlerID,$StatusID,true);
				AC_SetAggregationType($archiveHandlerID,$StatusID,0);      /* normaler Wwert */
				}
			}
		}
	IPS_ApplyChanges($archiveHandlerID);

	/******************************************************

				INIT Homematic RSSI Read

	 * @author        Andreas Brauneis, mit Modifikationen Wolfgang JÖBSTL
	 * @version
	 *  Version 2.50.1, 02.07.2012<br/>
	 *
	*************************************************************/

	echo "===========================================\n";
	echo "Homematic RSSI Variablen anlegen.\n";

	IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");
	IPSUtils_Include ("Homematic_Library.class.php","IPSLibrary::app::modules::OperationCenter");
	
	$CategoryIdHardware = CreateCategoryPath('Hardware.Homematic');
	$CategoryIdRSSIHardware = CreateCategoryPath('Hardware.HomematicRSSI');
	
	$CategoryIdHomematicErreichbarkeit = CreateCategoryPath('Program.IPSLibrary.data.modules.OperationCenter.HomematicRSSI');
	$HomematicErreichbarkeit = CreateVariable("ErreichbarkeitHomematic",   3 /*String*/,  $CategoryIdHomematicErreichbarkeit, 50 , '~HTMLBox',null,null,"");
	$UpdateErreichbarkeit = CreateVariable("UpdateErreichbarkeit",   1 /*Integer*/,  $CategoryIdHomematicErreichbarkeit, 500 , '~UnixTimestamp',null,null,"");
    
    $ExecuteRefreshID = CreateVariable("UpdateDurchfuehren",   0 /*Boolean*/,  $CategoryIdHomematicErreichbarkeit, 400 , '~Switch',$scriptIdOperationCenter,null,"");

	$CategoryIdHomematicGeraeteliste = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicDeviceList');
	$HomematicGeraeteliste = CreateVariable("HomematicGeraeteListe",   3 /*String*/,  $CategoryIdHomematicGeraeteliste, 50 , '~HTMLBox',null,null,"");

	$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
	$HomematicInventory = CreateVariable("HomematicInventory",   3 /*String*/,  $CategoryIdHomematicInventory, 60 , '~HTMLBox',null,null,"");

	$homematic=HomematicList();
	$seriennumernliste=array();
	foreach ($homematic as $instance => $entry)
		{
		$adresse=explode(":",$entry["Adresse"])[0];
		if ( isset($seriennumernliste[$adresse])!=true )
			{
			$seriennumernliste[$adresse]["Adresse"]=$adresse;
			$seriennumernliste[$adresse]["Name"]=$entry["Name"];			
			if (isset($entry["Type"])==true) $seriennumernliste[$adresse]["Type"]=$entry["Type"];	
			else $seriennumernliste[$adresse]["Type"]="             ";		
			if (isset($entry["Device"])==true) $seriennumernliste[$adresse]["Device"]=$entry["Device"];
			else $seriennumernliste[$adresse]["Device"]="              ";
			$seriennumernliste[$adresse]["Channel"]=explode(":",$entry["Adresse"])[1];
			if ($entry["Protocol"]=="Funk") $seriennumernliste[$adresse]["Protocol"]=HM_PROTOCOL_BIDCOSRF;
			else $seriennumernliste[$adresse]["Protocol"]=HM_PROTOCOL_BIDCOSWI;
			}		
		}
	echo "Es gibt ".sizeof($seriennumernliste)." Seriennummern also Homematic Geräte in der Liste.\n";	
	foreach ($seriennumernliste as $zeile)
		{
		if (trim($zeile["Type"])=="") 
			{
			echo "---> kein RSSI Monitoring : ";
			unset($seriennumernliste[$zeile["Adresse"]]);
			}
		echo "   ".$zeile["Adresse"]."  ".$zeile["Name"]."  ".$zeile["Type"]."  ".$zeile["Device"]."  \n";
		}
	echo "\n";
	echo "Davon sind noch ".sizeof($seriennumernliste)." Geraete entweder Button, Switch oder Dimmer.\n";
	$homematicConfiguration=array();
	foreach ($seriennumernliste as $zeile)
		{
		echo "   ".$zeile["Adresse"]."  ".$zeile["Name"]."  \n";
		$name=explode(":",$zeile["Name"])[0];
		$homematicConfiguration[$name][]=$zeile["Adresse"];
		$homematicConfiguration[$name][]=$zeile["Channel"];
		$homematicConfiguration[$name][]=$zeile["Protocol"];
		$homematicConfiguration[$name][]=$zeile["Type"];				
		}

	foreach ($homematicConfiguration as $component=>$componentData) 
		{
		$propertyAddress  = $componentData[0];
		$propertyChannel  = $componentData[1];
		$propertyProtocol = $componentData[2];
		$propertyType     = $componentData[3];
		$propertyName     = $component;
		
		$install=true;
		foreach (IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}") as $HomematicModuleId ) 
			{
			$HMAddress = HM_GetAddress($HomematicModuleId);
			if ($HMAddress=="$propertyAddress:$propertyChannel") 
				{
				//echo "Found existing HomaticModule '$propertyName' Address=$propertyAddress, Channel=$propertyChannel, Protocol=$propertyProtocol\n";
				$install=false;
				}
			}
		if ($install==true)		/* kein Device gefunden */
			{
			echo "HomaticModule '$propertyName' muss komplett neu installiert werden.\n";
			$moduleManager->LogHandler()->Log("Create NEW HomaticModule '$propertyName' Address=$propertyAddress, Channel=$propertyChannel, Protocol=$propertyProtocol");
			$DeviceId = CreateHomematicInstance($moduleManager,
                                            $propertyAddress,
                                            $propertyChannel,
                                            $propertyName,
                                            $CategoryIdHardware,
                                            $propertyProtocol);
			}
		$SystemId = CreateHomematicInstance($moduleManager,
                                            $propertyAddress,
                                            0,
                                            $propertyName.'#',
                                            $CategoryIdRSSIHardware,
                                            $propertyProtocol);
		if ($propertyType==HM_TYPE_SMOKEDETECTOR) 
			{
			$variableId = IPS_GetVariableIDByName('STATE', $DeviceId);
			CreateEvent ($propertyName, $variableId, $scriptIdSmokeDetector);
			} 
		}
		
	/********************************************************
	 *
	 *		INIT Detect Movement Event Darstellung und Auswertung
	 *
	 * Auswertung mit DetectMovement/Testmovement, alle anderen mit Autosteuerung/Webfront_Control
	 *
	 ***************************************************/

	/* Autosteuerung und Detectmovement verwenden folgendes Profil um die Event tabellen zu sortieren. */
		$pname="SortTableEvents";
		if (IPS_VariableProfileExists($pname) == false)
			{
			//Var-Profil erstellen
			IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
			IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
			IPS_SetVariableProfileValues($pname, 0, 10, 1); //PName, Minimal, Maximal, Schrittweite
			IPS_SetVariableProfileAssociation($pname, 0, "Event#", "", 	0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
			IPS_SetVariableProfileAssociation($pname, 1, "ID", "", 	0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 2, "Name", "", 		0x4e3127); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 3, "Pfad", "", 		0x4e7127); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 4, "Objektname", "", 		0x1ef1f7); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 5, "Module", "", 		0x1ef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 6, "Funktion", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 7, "Konfiguration", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 8, "Homematic", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 9, "DetectMovement", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color
			IPS_SetVariableProfileAssociation($pname, 10, "Autosteuerung", "", 		0xaef177); //P-Name, Value, Assotiation, Icon, Color			
			echo "Profil ".$pname." erstellt;\n";
			}
			
	if (isset ($installedModules["DetectMovement"]))
		{
	    echo "===========================================\n";
	    echo "DetectMovement Variablen für Webfront anlegen.\n";		
		$moduleManagerDM = new IPSModuleManager('DetectMovement',$repository);
		$CategoryIdDataDM     = $moduleManagerDM->GetModuleCategoryID('data');
		$CategoryIdAppDM      = $moduleManagerDM->GetModuleCategoryID('app');
		$scriptId  = IPS_GetObjectIDByIdent('TestMovement', $CategoryIdAppDM);	

		$categoryId_DetectMovement    = CreateCategory('DetectMovement',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_DetectMovement, true); 		// in der normalen Viz Darstellung Kategorie verstecken
		$TableEventsDM_ID=CreateVariable("TableEvents",3, $categoryId_DetectMovement,0,"~HTMLBox",null,null,"");
		$SchalterSortDM_ID=CreateVariable("Tabelle sortieren",1, $categoryId_DetectMovement,0,"SortTableEvents",$scriptId,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
		}
		
	/********************************************************
	 *
	 *		INIT Autosteuerung Event Darstellung und Auswertung
	 *
	 * Auswertung mit Autosteuerung/WebfrontControl
	 *
	 ***************************************************/

	if (isset ($installedModules["Autosteuerung"]))
		{
	    echo "===========================================\n";
	    echo "Autosteuerung Variablen für Webfront anlegen.\n";		
        $moduleManagerAS = new IPSModuleManager('Autosteuerung',$repository);
		$CategoryIdDataAS     = $moduleManagerAS->GetModuleCategoryID('data');
		$CategoryIdAppAS      = $moduleManagerAS->GetModuleCategoryID('app');
		$scriptId  = IPS_GetObjectIDByIdent('WebfrontControl', $CategoryIdAppAS);		

		$categoryId_Autosteuerung    = CreateCategory('Autosteuerung',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_Autosteuerung, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsAS_ID=CreateVariable("TableEvents",3, $categoryId_Autosteuerung,0,"~HTMLBox",null,null,"");
		$SchalterSortAS_ID=CreateVariable("Tabelle sortieren",1, $categoryId_Autosteuerung,0,"SortTableEvents",$scriptId,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')

	    echo "===========================================\n";
	    echo "Alexa Variablen für Webfront anlegen.\n";		

		$categoryId_AutosteuerungAlexa    = CreateCategory('Alexa',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_AutosteuerungAlexa, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsAlexa_ID=CreateVariable("TableEvents",3, $categoryId_AutosteuerungAlexa,0,"~HTMLBox",null,null,"");
		$SchalterSortAlexa_ID=CreateVariable("Tabelle sortieren",1, $categoryId_AutosteuerungAlexa,0,"SortTableEvents",$scriptId,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')

	    echo "===========================================\n";
	    echo "Zusammenfassung Taster anzeigen.\n";		

		$categoryId_AutosteuerungButton    = CreateCategory('ButtonTasks',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_AutosteuerungButton, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsButton_ID=CreateVariable("TableEvents",3, $categoryId_AutosteuerungButton,0,"~HTMLBox",null,null,"");

	    echo "===========================================\n";
	    echo "Zusammenfassung Timer anzeigen.\n";		

		$categoryId_AutosteuerungSimulation    = CreateCategory('TimerSimulation',   $CategoryIdData, 150);
        IPS_SetHidden($categoryId_AutosteuerungSimulation, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsButton_ID=CreateVariable("TableEvents",3, $categoryId_AutosteuerungSimulation,0,"~HTMLBox",null,null,"");

	/********************************************************
	 *
	 *		INIT Geraete Darstellung und Auswertung
	 *
	 ***************************************************/

	    echo "===========================================\n";
	    echo "Device Management Variablen für Webfront anlegen.\n";		

		$categoryId_DeviceManagement    = CreateCategory('DeviceManagement',   $CategoryIdData, 150);
		IPS_SetHidden($categoryId_DeviceManagement, true); 		// in der normalen Viz Darstellung Kategorie verstecken        
		$TableEventsDevMan_ID=CreateVariable("TableEvents",3, $categoryId_DeviceManagement,0,"~HTMLBox",null,null,"");
		$SchalterSortDevMan_ID=CreateVariable("Tabelle sortieren",1, $categoryId_DeviceManagement,0,"SortTableEvents",$scriptId,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
		}
		
	/********************************************************
	 *
	 *		INIT HUE Module Geraete Darstellung und Bedienung vom HUE Modul 
	 *
	 ***************************************************/
		
	$modulhandling = new ModuleHandling();
	$HUE=$modulhandling->getInstances('HUEBridge');	
	$countHue = sizeof($HUE);
	echo "Es gibt insgesamt ".$countHue." SymCon Hue Instanzen.\n";
	if ($countHue>0)
		{
		$configHue=IPS_GetConfiguration($modulhandling->getInstances("HUEBridge")[0]);
		echo "   ".$configHue."\n";
		$categoryId_Hue = CreateCategoryPath('Hardware.HUE');		
		}
				
	/********************************************************
	 *
	 *		INIT HM Inventory Homematic Geraete Darstellung 
	 *
	 ***************************************************/
    
    /* Config des Homematic inventory creators für die Formattierung der Homematic tabellen 
        0 - HM address (default)
        1 - HM device type
        2 - HM channel type
        3 - IPS device name
        4 - HM device name	                                            */

	$pname="SortTableHomematic";
	if (IPS_VariableProfileExists($pname) == false)
		{
		//Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
		IPS_SetVariableProfileValues($pname, 0, 5, 1); //PName, Minimal, Maximal, Schrittweite
		IPS_SetVariableProfileAssociation($pname, 0, "Adresse", "", 	0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
		IPS_SetVariableProfileAssociation($pname, 1, "DeviceType", "", 	0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 2, "ChannelType", "", 		0x4e3127); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 3, "Pfad", "", 		0x4e7127); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 4, "IPSDeviceName", "", 		0x1ef1f7); //P-Name, Value, Assotiation, Icon, Color
		IPS_SetVariableProfileAssociation($pname, 5, "DeviceName", "", 		0x1ef177); //P-Name, Value, Assotiation, Icon, Color
		echo "Profil ".$pname." erstellt;\n";
		}
	$order=1000;	
	$HMIs=$modulhandling->getInstances('HM Inventory Report Creator');		
	$countHMI = sizeof($HMIs);
	echo "Es gibt insgesamt ".$countHMI." SymCon Homematic Inventory Instanzen. Entspricht üblicherweise der Anzahl der CCUs.\n";
	if ($countHMI>0)
		{		
		/* Webfront Darstellung erfolgt im User Verzeichnis, dieses erstellen */
		$Verzeichnis="user/OperationCenter/Homematics/";
		$Verzeichnis=IPS_GetKernelDir()."webfront/".$Verzeichnis;
		$Verzeichnis = str_replace('\\','/',$Verzeichnis);
		if ( is_dir ( $Verzeichnis ) == false ) mkdirtree($Verzeichnis);
		
		$CategoryIdHomematicInventory = CreateCategoryPath('Program.IPSLibrary.data.hardware.IPSHomematic.HomematicInventory');
				
		foreach ($HMIs as $HMI)
			{
			$configHMI=IPS_GetConfiguration($HMI);
			echo "\n-----------------------------------\n";
			echo "Konfiguration für HMI Report Creator : ".$HMI."\n";
			echo $configHMI."\n";
			$configStruct=json_decode($configHMI,true);
			//print_r($configStruct);
			$aktVerzeichnis=IPS_GetProperty($HMI,"OutputFile");
			$neuVerzeichnis=$Verzeichnis.$HMI.'/HM_inventory.html';
			if ( is_dir ( $Verzeichnis.$HMI.'/' ) == false ) 
				{
				echo "Verzeichnis $neuVerzeichnis existiert noch nicht. Daher erstellen:\n";
				mkdirtree($Verzeichnis.$HMI.'/');
				}
			echo "Ausgabe Speicher Verzeichnis :".$aktVerzeichnis."\n";
			if ( $aktVerzeichnis != $neuVerzeichnis)
				{
				echo "Verzeichnis auf Webfront verschieben. In das Verzeichnis ".$neuVerzeichnis."\n";
				IPS_SetProperty($HMI,"OutputFile",$neuVerzeichnis);
				IPS_ApplyChanges($HMI);
				}
			$CategoryIdHomematicCCU=CreateCategory("HomematicInventory_".$HMI,$CategoryIdHomematicInventory,$order+5);
			// function CreateVariableByName($id, $name, $type, $profile="", $ident="", $position=0, $action=0)
			$HomematicInventory = CreateVariableByName($CategoryIdHomematicCCU,IPS_GetName($HMI),3,"~HTMLBox","",$order+5);		// String
			$SortInventory = CreateVariableByName($CategoryIdHomematicCCU,"Sortieren",1,"SortTableHomematic","",$order,$scriptIdOperationCenter);		// String
            $html='<iframe frameborder="0" width="100%" height="4000px"  src="../user/OperationCenter/Homematics/'.$HMI.'/HM_inventory.html"</iframe>';
			//HMI_CreateReport($HMI);	SetValue($HomematicInventory,$html);			
			$order +=10;
			}
		}
	
																																
	// ----------------------------------------------------------------------------------------------------------------------------
	// WebFront Installation
	// ----------------------------------------------------------------------------------------------------------------------------
	
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		CreateLinkByDestination('OperationCenter', $CategoryIdData,    $categoryId_WebFront,  10);
		if (isset ($installedModules["Autosteuerung"]))
            {
            CreateLinkByDestination('Alexa', $categoryId_AutosteuerungAlexa,    $categoryId_WebFront,  80);		
            CreateLinkByDestination('Autosteuerung', $categoryId_Autosteuerung,    $categoryId_WebFront,  80);		
            CreateLinkByDestination('TasterDarstellung', $categoryId_AutosteuerungButton,    $categoryId_WebFront,  80);		
            CreateLinkByDestination('TimerSimulation', $categoryId_AutosteuerungSimulation,    $categoryId_WebFront,  80);		
            }
		if ($countHue>0)	CreateLinkByDestination('HUE', $categoryId_Hue,    $categoryId_WebFront,  120);			
		if (isset ($installedModules["DetectMovement"]))	CreateLinkByDestination('DetectMovement', $categoryId_DetectMovement,    $categoryId_WebFront,  90);		
		CreateLinkByDestination('Nachrichtenverlauf', $categoryId_Nachrichten,    $categoryId_WebFront,  200);
		CreateLinkByDestination('SystemInfo', $categoryId_SystemInfo,    $categoryId_WebFront,  800);
		CreateLinkByDestination('TraceRouteVerlauf', $categoryId_Route,    $categoryId_WebFront,  900);

		$categoryId_Hardware = CreateCategory("Hardware",  $categoryId_WebFront, 20);
		CreateLinkByDestination('HomematicErreichbarkeit', $CategoryIdHomematicErreichbarkeit,    $categoryId_Hardware,  100);		// Link auf eine Kategorie, daher neues Tab
		CreateLinkByDestination('HomematicGeraeteliste', $CategoryIdHomematicGeraeteliste,    $categoryId_Hardware,  110);
		CreateLinkByDestination('HomematicInventory', $CategoryIdHomematicInventory,    $categoryId_Hardware,  120);
	
		/* Zusammenräumen, alte Ordnung eliminieren */
		$linkId=@IPS_GetLinkIDByName('HomematicErreichbarkeit', $categoryId_WebFront);
		if ($linkId) IPS_DeleteLink($linkId); 
		$linkId=@IPS_GetLinkIDByName('HomematicGeraeteliste', $categoryId_WebFront);
		if ($linkId) IPS_DeleteLink($linkId); 
		}

	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);

		}

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);

		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Retro_Path);

		}

	/***********************************************************************
	 *
	 * fuer IPSCam verschiedene Overview Darstellungen machen
	 * 
	 * Snapshot Darstellung der in der IPSCam erstellten Snapshot Dateien (current0 bis x), Zeitraster einstellbar
	 * Live Overview für jeweils 4 Cameras machen, Overview1 usw. sollte sich den lokalen Stream holen, anstelle extern, 
	 *		abhängig von der verfügbaren Bandbreite
	 * Capture Darstellung, aus dem FTP Verzeichnissen entsprechende Bilder auswählen
	 *
	 * es gibt zwei Kamera Config Files, für das IPSCam Modul und innerhalb des OperationCenter Config Teil für die CAMs
	 *		IPSCam sind alle Kameras, lokal und remote
	 *		OC Cams sind nur die lokalen Kameras, die teilweise, muessen nicht alle sein auf einem FTP Verzeichis Alarm-Capture Bilder ablegen 
	 *
	 ******************************************************************/


	if ( isset ($installedModules["IPSCam"] ) ) 
		{
		echo "\n";
		echo "=====================================================================================\n"; 
		echo "Modul IPSCam installiert. Die Überblickdarstellung im WebCam Frontend wenn gewünscht anlegen:\n"; 

		if ($WFC10Cam_Enabled)
			{
			echo "IPSCam Überblickdarstellung für Administrator im WebCam Frontend anlegen:\n";
			
			// ----------------------------------------------------------------------------------------------------------------------------
			// Program Installation
			// ----------------------------------------------------------------------------------------------------------------------------
			
			$CategoryIdCamData  		= $moduleManagerCam->GetModuleCategoryID('data');
			$CategoryIdCamApp   		= $moduleManagerCam->GetModuleCategoryID('app');
			$categoryIdCams     		= CreateCategory('Cams',    $CategoryIdCamData, 20);
			$scriptIdActionScript   = IPS_GetScriptIDByName('IPSCam_ActionScript', $CategoryIdCamApp);			
			
			// ===================================================================================================
			// Add Camera Devices
			// ===================================================================================================
			
			IPSUtils_Include ("IPSCam_Constants.inc.php",      "IPSLibrary::app::modules::IPSCam");
			IPSUtils_Include ("IPSCam_Configuration.inc.php",  "IPSLibrary::config::modules::IPSCam");
			$camConfig = IPSCam_GetConfiguration();
			$result=array();
			
			/* der iFrame für die Movie Darstellung wird von IPSCam übernommen, damit wird ein eigenen Cam.php File aufgerufen */

			foreach ($camConfig as $idx=>$data) 
				{
				$categoryIdCamX      = CreateCategory($idx, $categoryIdCams, $idx);
				$variableIdCamHtmlX  = IPS_GetObjectIDByIdent(IPSCAM_VAR_CAMHTML, $categoryIdCamX);
				echo "\nKamera ".$idx." (".$data["Name"].") auf Kategorie : ".$categoryIdCamX." (".IPS_GetName($categoryIdCamX).") mit HTML Objekt auf : ".$variableIdCamHtmlX."\n";
				//print_r($data);
				$result[$idx]["OID"]=$variableIdCamHtmlX;
				$result[$idx]["Name"]=$data["Name"];
				$cam_name="Cam_".$data["Name"];
				$cam_categoryId=@IPS_GetObjectIDByName($cam_name,$CategoryIdData);
				if ($cam_categoryId==false) echo "   Name ungleich zu OperationCenter.\n";
				}
			$anzahl=sizeof($result);
			echo "Es werden im Snapshot Overview insgesamt ".$anzahl." Live Cameras (lokal und remote) angezeigt.\n";

			$WFC10Cam_Path        	 = $moduleManagerCam->GetConfigValue('Path', 'WFC10');
			$WFC10Cam_TabPaneItem    = $moduleManagerCam->GetConfigValue('TabPaneItem', 'WFC10');
			$WFC10Cam_TabPaneParent  = $moduleManagerCam->GetConfigValue('TabPaneParent', 'WFC10');
			$WFC10Cam_TabPaneName    = $moduleManagerCam->GetConfigValue('TabPaneName', 'WFC10');
			$WFC10Cam_TabPaneIcon    = $moduleManagerCam->GetConfigValue('TabPaneIcon', 'WFC10');
			$WFC10Cam_TabPaneOrder   = $moduleManagerCam->GetConfigValueInt('TabPaneOrder', 'WFC10');
			$WFC10Cam_TabItem        = $moduleManagerCam->GetConfigValue('TabItem', 'WFC10');
			$WFC10Cam_TabName        = $moduleManagerCam->GetConfigValue('TabName', 'WFC10');
			$WFC10Cam_TabIcon        = $moduleManagerCam->GetConfigValue('TabIcon', 'WFC10');
			$WFC10Cam_TabOrder       = $moduleManagerCam->GetConfigValueInt('TabOrder', 'WFC10');
			echo "WF10 Administrator\n";
			echo "  Path          : ".$WFC10Cam_Path."\n";
			echo "  TabPaneItem   : ".$WFC10Cam_TabPaneItem."\n";
			echo "  TabPaneParent : ".$WFC10Cam_TabPaneParent."\n";
			echo "  TabPaneName   : ".$WFC10Cam_TabPaneName."\n";
			echo "  TabPaneIcon   : ".$WFC10Cam_TabPaneIcon."\n";
			echo "  TabPaneOrder  : ".$WFC10Cam_TabPaneOrder."\n";
			echo "  TabItem       : ".$WFC10Cam_TabItem."\n";
			echo "  TabName       : ".$WFC10Cam_TabName."\n";
			echo "  TabIcon       : ".$WFC10Cam_TabIcon."\n";
			echo "  TabOrder      : ".$WFC10Cam_TabOrder."\n";
									
			/************************
			 *
			 * Anlegen des Capture Overviews von allen Kameras
			 * einzelne Tabs pro Kamera mit den interessantesten Bildern der letzten Stunden oder Tage
			 * die Daten werden aus den FTP Verzeichnissen gesammelt.
			 *
			 ************************/
							
			echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: \"".$WFC10Cam_Path."_Capture\"\n";			
			$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path."_Capture");
			EmptyCategory($categoryId_WebFrontAdministrator);
			IPS_SetHidden($categoryId_WebFrontAdministrator, true); 		// in der normalen Viz Darstellung Kategorie verstecken

			//CreateWFCItemTabPane   ($WFC10User_ConfigId, $WFC10User_TabPaneItem, $WFC10User_TabPaneParent,  $WFC10User_TabPaneOrder, $WFC10User_TabPaneName, $WFC10User_TabPaneIcon);
			CreateWFCItemTabPane  ($WFC10_ConfigId, "CamCapture", $WFC10Cam_TabPaneItem, ($WFC10Cam_TabOrder+1000), 'CamCapture', $WFC10Cam_TabIcon);
			if (isset ($OperationCenterConfig['CAM']))
				{
				$i=0;
				foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
					{
					$i++;
					echo "  Webfront Tabname für ".$cam_name." \n";
					$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$CategoryIdData);
					if ($cam_categoryId==false)
						{
						$cam_categoryId = IPS_CreateCategory();       // Kategorie anlegen
						IPS_SetName($cam_categoryId, "Cam_".$cam_name); // Kategorie benennen
						IPS_SetParent($cam_categoryId,$CategoryIdData);
						}
					$categoryIdCapture  = CreateCategory("Cam_".$cam_name,  $categoryId_WebFrontAdministrator, 10*$i);
					CreateWFCItemCategory  ($WFC10_ConfigId, "Cam_".$cam_name,  "CamCapture",    (10*$i),  "Cam_".$cam_name,     $WFC10Cam_TabIcon, $categoryIdCapture /*BaseId*/, 'false' /*BarBottomVisible*/);
					echo "     CreateWFCItemCategory  ($WFC10_ConfigId, Cam_$cam_name,  CamCapture,    ".(10*$i).",  Cam_$cam_name,     $WFC10Cam_TabIcon, $categoryIdCapture, false);\n";
					$pictureFieldID = CreateVariable("pictureField",   3 /*String*/,  $categoryIdCapture, 50 , '~HTMLBox',null,null,"");
					$box='<iframe frameborder="0" width="100%">     </iframe>';
					SetValue($pictureFieldID,$box);
					}
				}
				
			/************************
			 *
			 * Anlegen des Picture Overviews von allen Kameras
			 * ein Tab für alle Kameras, es wird nicht der Livestream 
			 * sondern Bilder die regelmäßig per Button aktualisiert werden müssen in einer gemeinsamen html Tabelle angezeigt
			 *
			 ************************/
													
			echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: \"".$WFC10Cam_Path.".Pictures\"\n";
			$categoryId_WebFrontPictures         = CreateCategoryPath($WFC10Cam_Path."Pictures");
			EmptyCategory($categoryId_WebFrontPictures);				// ausleeren und neu aufbauen, die Geschichte ist gelöscht !
			IPS_SetHidden($categoryId_WebFrontPictures, true); 		// in der normalen Viz Darstellung Kategorie verstecken
				
			/* TabPaneItem anlegen und wenn vorhanden vorher loeschen */
			$tabItem = $WFC10Cam_TabPaneItem.$WFC10Cam_TabItem."Pics";
			if ( exists_WFCItem($WFC10_ConfigId, $WFC10Cam_TabPaneItem."Pics") )
		 		{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  löscht TabItem : ".$WFC10Cam_TabPaneItem.".Pics\n";
				DeleteWFCItems($WFC10_ConfigId, $WFC10Cam_TabPaneItem."Pics");
				}
			else
				{
				echo "Webfront ".$WFC10_ConfigId." (".IPS_GetName($WFC10_ConfigId).")  TabItem : ".$WFC10Cam_TabPaneItem.".Pics nicht mehr vorhanden.\n";
				}	
			echo "Webfront ".$WFC10_ConfigId." erzeugt TabItem :".$WFC10Cam_TabPaneItem." in ".$WFC10Cam_TabPaneParent."\n";
			//CreateWFCItemTabPane   ($WFC10_ConfigId,"CamPictures" ,$WFC10Cam_TabPaneItem, $WFC10Cam_TabPaneOrder, $WFC10Cam_TabPaneName, $WFC10Cam_TabPaneIcon);
			CreateWFCItemCategory  ($WFC10_ConfigId, "CamPictures" ,$WFC10Cam_TabPaneItem,   10, 'CamPictures', $WFC10Cam_TabPaneIcon, $categoryId_WebFrontPictures   /*BaseId*/, 'false' /*BarBottomVisible*/);
			/* im TabPane entweder eine Kategorie oder ein SplitPane und Kategorien anlegen */
			//CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem,   "CamPictures",   10, '', '', $categoryId_WebFrontPictures   /*BaseId*/, 'false' /*BarBottomVisible*/);

			// definition CreateLinkByDestination ($Name, $LinkChildId, $ParentId, $Position, $ident="") {
			CreateLinkByDestination("Pictures", $CamTablePictureID, $categoryId_WebFrontPictures,  10,"");								
				
			/* zuerst die Kategorien in Visualization aufbauen */
			$tabs=(integer)($anzahl/4);
			if ($tabs>0)
				{
				/* mehr als 4 Kameras, zusaetzliche Tabs eröffnen */
				for ($i=0;$i<=$tabs;$i++)
					{
					if ($i==0) $ext="";
					else $ext=(string)$i;

					echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: ".$WFC10Cam_Path.$ext." \n";					
					$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path.$ext);
					EmptyCategory($categoryId_WebFrontAdministrator);
        			IPS_SetHidden($categoryId_WebFrontAdministrator, true); 		// in der normalen Viz Darstellung Kategorie verstecken
                    
					$categoryIdLeftUp  = CreateCategory('LeftUp',  $categoryId_WebFrontAdministrator, 10);
					$categoryIdRightUp = CreateCategory('RightUp', $categoryId_WebFrontAdministrator, 20);						
					$categoryIdLeftDn  = CreateCategory('LeftDn',  $categoryId_WebFrontAdministrator, 30);
					$categoryIdRightDn = CreateCategory('RightDn', $categoryId_WebFrontAdministrator, 40);						

					$tabItem = $WFC10Cam_TabPaneItem.'Ovw'.$ext;																				
					CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10Cam_TabPaneItem, ($WFC10Cam_TabOrder+100), "Overview", $WFC10Cam_TabIcon, 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
					CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Left", $tabItem, 10, "Left", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
					CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Right", $tabItem, 20, "Right", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			
					CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Left', $tabItem."_Left", 10, '', '', $categoryIdLeftUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
					CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Right', $tabItem."_Right", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
					CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Left', $tabItem."_Left", 20, '', '', $categoryIdLeftDn   /*BaseId*/, 'false' /*BarBottomVisible*/);
					CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Right', $tabItem."_Right", 20, '', '', $categoryIdRightDn   /*BaseId*/, 'false' /*BarBottomVisible*/);

					if (sizeof($result)>($i*4)) CreateLink($result[($i*4)+0]["Name"], $result[($i*4)+0]["OID"], $categoryIdLeftUp, 10);
					if (sizeof($result)>(($i*4)+1)) CreateLink($result[($i*4)+1]["Name"], $result[($i*4)+1]["OID"], $categoryIdRightUp, 10);
					if (sizeof($result)>(($i*4)+2)) CreateLink($result[($i*4)+2]["Name"], $result[($i*4)+2]["OID"], $categoryIdLeftDn, 10);
					if (sizeof($result)>(($i*4)+3)) CreateLink($result[($i*4)+3]["Name"], $result[($i*4)+3]["OID"], $categoryIdRightDn, 10);
					}
				}
			else
				{
				echo "\nWebportal Administrator.IPSCam.Overview Datenstruktur installieren in: ".$WFC10Cam_Path." \n";
				$categoryId_WebFrontAdministrator         = CreateCategoryPath($WFC10Cam_Path);
				EmptyCategory($categoryId_WebFrontAdministrator);
				$categoryIdLeftUp  = CreateCategory('LeftUp',  $categoryId_WebFrontAdministrator, 10);
				$categoryIdRightUp = CreateCategory('RightUp', $categoryId_WebFrontAdministrator, 20);						
				$categoryIdLeftDn  = CreateCategory('LeftDn',  $categoryId_WebFrontAdministrator, 30);
				$categoryIdRightDn = CreateCategory('RightDn', $categoryId_WebFrontAdministrator, 40);						

				$tabItem = $WFC10Cam_TabPaneItem.'Ovw';																				
				CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem, $WFC10Cam_TabPaneItem, ($WFC10Cam_TabOrder+100), "Overview", $WFC10Cam_TabIcon, 1 /*Vertical*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
				CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Left", $tabItem, 10, "Left", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
				CreateWFCItemSplitPane ($WFC10_ConfigId, $tabItem."_Right", $tabItem, 20, "Right", "", 0 /*Horizontal*/, 50 /*Width*/, 0 /*Target=Pane1*/, 0/*UsePixel*/, 'true');
			
				CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Left', $tabItem."_Left", 10, '', '', $categoryIdLeftUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
				CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Up_Right', $tabItem."_Right", 10, '', '', $categoryIdRightUp   /*BaseId*/, 'false' /*BarBottomVisible*/);
				CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Left', $tabItem."_Left", 20, '', '', $categoryIdLeftDn   /*BaseId*/, 'false' /*BarBottomVisible*/);
				CreateWFCItemCategory  ($WFC10_ConfigId, $tabItem.'Dn_Right', $tabItem."_Right", 20, '', '', $categoryIdRightDn   /*BaseId*/, 'false' /*BarBottomVisible*/);

				if (sizeof($result)>0) CreateLink($result[0]["Name"], $result[0]["OID"], $categoryIdLeftUp, 10);
				if (sizeof($result)>1) CreateLink($result[1]["Name"], $result[1]["OID"], $categoryIdRightUp, 10);
				if (sizeof($result)>2) CreateLink($result[2]["Name"], $result[2]["OID"], $categoryIdLeftDn, 10);
				if (sizeof($result)>3) CreateLink($result[3]["Name"], $result[3]["OID"], $categoryIdRightDn, 10);
				}			
			}
			
		}

	if (isset ($installedModules["OperationCenter"])) 
		{
		$log_Install->LogMessage("Install Module OperationCenter abgeschlossen.");
		}

	// ----------------------------------------------------------------------------------------------------------------------------
	// Local Functions
	// ----------------------------------------------------------------------------------------------------------------------------


	function CreateHomematicInstance($moduleManager, $Address, $Channel, $Name, $ParentId, $Protocol='BidCos-RF') 
        {
		foreach (IPS_GetInstanceListByModuleID("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}") as $HomematicModuleId ) 
            {
			$HMAddress = HM_GetAddress($HomematicModuleId);
			if ($HMAddress=="$Address:$Channel") 
                {
				$moduleManager->LogHandler()->Log("Found existing HomaticModule '$Name' Address=$Address, Channel=$Channel, Protocol=$Protocol");
				return $HomematicModuleId;
			    }
		    }
        echo "Create HomaticModule '$Name' Address=$Address, Channel=$Channel, Protocol=$Protocol\n";
		$moduleManager->LogHandler()->Log("Create HomaticModule '$Name' Address=$Address, Channel=$Channel, Protocol=$Protocol");
		$HomematicModuleId = IPS_CreateInstance("{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}");
		IPS_SetParent($HomematicModuleId, $ParentId);
		IPS_SetName($HomematicModuleId, $Name);
		HM_SetAddress($HomematicModuleId, $Address.':'.$Channel);
		if ($Protocol == 'BidCos-RF') 
			{
			$Protocol = 0; echo "Funk";
			}
		else 
			{
			$Protocol = 1; echo "Draht";
			}
		HM_SetProtocol($HomematicModuleId, $Protocol);
		HM_SetEmulateStatus($HomematicModuleId, true);
		// Apply Changes
		IPS_ApplyChanges($HomematicModuleId);

		return $HomematicModuleId;
	}	

	/*
	 * Bei jedem Bild als html Verzeichnis und alternativem Bildtitel darstellen
	 *
	 */

	function imgsrcstring($imgVerzeichnis,$filename,$title)
		{
		return ($imgVerzeichnis."\\".$filename.'" title="'.$title.'" alt="'.$filename);
		}
	
	
	
?>