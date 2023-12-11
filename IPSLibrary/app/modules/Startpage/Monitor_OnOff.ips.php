<?php

/*
 * @defgroup Monitor_onoff Startpage
 *
 * Script zur Ansteuerung des Monitors ueber Modul Startpage und OperationCenter
 *
 * Mit dem Übergabeparameter _IPS['Monitor'] kann der monitor lokal ein und ausgeschaltet werden. 
 * zusätzlich ist die Umschaltung von und in den Vollbildmodus implementiert.
 *
 * Für das Starten anderer lokaler Programme gibt es die Variable _IPS['VLC']
 * Damit kann die Media Software gestartet werden, aber auch beliebige andere Befehle am Host-PC gestartet werden (Sicherheit ?)
 * Erstmals optionale Übergabe eine json enkodiertem arrays
 *
 * Zum Thema Sicherheit, wenn man einmal am Server ist kann man alles umprogrammieren ...
 *
 * @file          Monitor_OnOff.ips.php
 * @author        Wolfgang Joebstl
 */

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');
IPSUtils_Include ('Startpage_Include.inc.php', 'IPSLibrary::app::modules::Startpage');
IPSUtils_Include ('Startpage_Library.class.php', 'IPSLibrary::app::modules::Startpage');

IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');	
	
/******
 *
 * Initialisierung
 *
 ********/
 
    $repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
    if (!isset($moduleManager)) 
        {
        IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
        $moduleManager = new IPSModuleManager('Startpage',$repository);
        }
    $installedModules = $moduleManager->GetInstalledModules();

    $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');

    $dosOps = new dosOps();
    $startpage = new StartpageHandler();         
    $unterverzeichnis=$startpage->getWorkDirectory();           // ersteöllt auch das verzeichnis falls erforderlich


/******
 *
 * Konfiguration
 *
 ********/

$configuration=startpage_configuration();
IPSLogger_Dbg(__file__,"Monitor on/off empfaengt von Sender ".$_IPS['SENDER']." einen Wert.\n");

if ($_IPS['SENDER'] == "RunScript")
	{
	if (isset($_IPS['Monitor']))
		{
		if ($_IPS['Monitor']=="on")
			{
			IPSLogger_Dbg(__file__,"Monitor on/off empfaengt von Sender ".$_IPS['SENDER']." den Wert \"on\".\n");		
			IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, 1);
			if (isset($installedModules["OperationCenter"])==true)
				{  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
				IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
				tts_play(1,'Monitor ein','',2);
				}
			}
		if ($_IPS['Monitor']=="off")
			{
			IPSLogger_Dbg(__file__,"Monitor on/off empfaengt von Sender ".$_IPS['SENDER']." den Wert \"off\".\n");			
			IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "monitor off", false, false, 1);
			if (isset($installedModules["OperationCenter"])==true)
				{  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
				IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
				tts_play(1,'Monitor aus','',2);
				}
			}
		if ($_IPS['Monitor']=="FullScreen")
			{
			IPSLogger_Dbg(__file__,"Monitor on/off empfaengt von Sender ".$_IPS['SENDER']." den Wert \"FullScreen\".\n");			
			IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, 1);  // oder -1 ?
			if (isset($installedModules["OperationCenter"])==true)
				{  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
				IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
				tts_play(1,'Monitor Vollbild Modus umschalten','',2);
				}
			}	
		}

	if (isset($_IPS['VLC']))
		{	
		IPSLogger_Dbg(__file__,"Monitor on/off empfaengt von Sender ".$_IPS['SENDER']." einen Befehl für VLC mit dem Wert \"".$_IPS['VLC']."\".\n");		
		/* Defaultwerte, wenn nicht übermittelt oder lokal vorhanden */
		$command=$_IPS['VLC'];
		$playlist="";		
		$Befehl="Start";
		if ( json_decode($_IPS['VLC'])!==Null ) 
			{
			IPSLogger_Dbg(__file__,"Monitor on/off empfaengt von Sender ".$_IPS['SENDER']." json enkodierte Werte .\n");		
			$Kommando=json_decode($_IPS['VLC']);
			if (isset($Kommando->Command)==true) 
				{
				echo "Kommando ".$Kommando->Command." erhalten.\n";
				$command=$Kommando->Command;
				}
			else $command='C:\Program Files\VideoLAN\VLC\VLC.exe';		/* auf Verdacht */ 
			if (isset($Kommando->Parameter)==true) 
				{
				echo "Parameter ".$Kommando->Parameter."\n";
				$playlist=$Kommando->Parameter;
				}
			else $playlist="";			
			if (isset($Kommando->StartStop)==true) 
				{
				echo "Befehl ".$Kommando->StartStop."\n";
				$Befehl=$Kommando->StartStop;
				}
			else $Befehl="Start";			
			}
		else
			{	
			if ($_IPS['VLC']=="")
				{
				if (isset($configuration["Directories"]["Playlist"]) == false) 
					{
					$configuration["Directories"]["Playlist"]="";
					$playlist="";
					}
				else $playlist=$configuration["Directories"]["Playlist"];
				if (isset($configuration["Directories"]["VideoLan"]) == true)
					{
					$command=$configuration["Directories"]["VideoLan"].'vlc.exe';
					}
				else $command='C:\Program Files\VideoLAN\VLC\VLC.exe';		/* auf Verdacht */ 
				}	
			else 
				{
				$command=$_IPS['VLC'];
				$playlist="";
				}
			}		
		IPSLogger_Dbg(__file__,"Monitor on/off ruft Programm ".$command." mit Parameter".$playlist." und Befehl ".$Befehl." auf.\n");					
		//IPS_ExecuteEX('"'.$command.'"', $playlist, false, false, 1);
		//IPS_ExecuteEX($command, $playlist, false, false, -1);
        if (($dosOps->getOperatingSystem()) == "WINDOWS")
            {

            if ($Befehl=="Start")
                {
                $handle2=fopen($unterverzeichnis."start_vlc.bat","w");
                fwrite($handle2,'"'.$command.'"  "'.$playlist.'"'."\r\n");
                fwrite($handle2,'pause'."\r\n");
                fclose($handle2);
                IPS_ExecuteEX($unterverzeichnis."start_vlc.bat",$playlist, false, false, -1);
                }
            else IPS_ExecuteEX($unterverzeichnis."kill_vlc.bat","", false, false, -1);	
            if (isset($installedModules["OperationCenter"])==true)
                {  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
                IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
                tts_play(1,'Fernseher ein oder ausschalten.','',2);
                }		
            }
		}
	}

if ($_IPS['SENDER']=="Execute")
	{
    echo "Execute of Script requested:\n";
    if (($dosOps->getOperatingSystem()) == "WINDOWS")
        {
        echo "   Operation System is Windows, thats good.\n";
        if ( isset($configuration["Directories"]["VideoLan"]) == true ) $command=$configuration["Directories"]["VideoLan"];
        else $command='C:/Program Files/VideoLAN/VLC/VLC.exe';
        if ( isset($configuration["Directories"]["Playlist"]) == true ) $playlist=$configuration["Directories"]["Playlist"];	
        else $playlist=" C:\Scripts\Fernsehprogramme\Technisat.m3u";
       
        echo "   Call IPS_ExecuteEX($unterverzeichnis"."start_vlc.bat,$playlist,...)\n";
        //IPS_ExecuteEX('"'.$command.'"', $playlist, false, false, 1);	
        //IPS_ExecuteEX($command, $playlist, false, false, -1);
        IPS_ExecuteEX($unterverzeichnis."start_vlc.bat",$playlist, false, false, -1);
        //IPS_ExecuteEX("c:/Scripts/nircmd.exe", "sendkeypress F11", false, false, 1);
        //IPS_ExecuteEX("c:/Scripts/nircmd.exe", "monitor off", false, false, 1);
        
        //print_r($configuration);

        if (isset($installedModules["OperationCenter"])==true)
            {  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
            echo "   check tts_play\n";
            IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
            tts_play(1,'Ausgabe Monitor Status wird unterstützt','',2,true);            //true für Debug wenn aus OperationCenter_Library
            }
        }
	}

?>