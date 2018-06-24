<?

/*
	 * @defgroup Monitor_onoff Stzartpage
	 *
	 * Script zur Ansteuerung des Monitors ueber Modul Startpage und OperationCenter
	 *
	 *
	 * @file          Monitor_OnOff.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');

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

/******
 *
 * Konfiguration
 *
 ********/

$configuration=startpage_configuration();

if (isset($_IPS['Monitor']))
	{
	if ($_IPS['Monitor']=="on")
		{
		IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "sendkeypress F11", false, false, 1);
		if (isset($installedModules["OperationCenter"])==true)
			{  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
			IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
			tts_play(1,'Monitor ein','',2);
			}
		}
	if ($_IPS['Monitor']=="off")
		{
		IPS_ExecuteEX($configuration["Directories"]["Scripts"].'nircmd.exe', "monitor off", false, false, 1);
		if (isset($installedModules["OperationCenter"])==true)
			{  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
			IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
			tts_play(1,'Monitor aus','',2);
			}
		}
	}

if (isset($_IPS['VLC']))
	{
	if ($_IPS['VLC']!="")
		{
		if (isset($configuration["Directories"]["Playlist"]) == false) $configuration["Directories"]["Playlist"]="";
		if (isset($configuration["Directories"]["ViedeoLan"]) == true)
			{
			IPS_ExecuteEX('"'.$configuration["Directories"]["ViedeoLan"].'vlc.exe"', $configuration["Directories"]["Playlist"], false, false, 1);
			}
		else IPS_ExecuteEX($_IPS['VLC'], "", false, false, 1);
		}
	}

if ($_IPS['SENDER']=="Execute")
	{
	//IPS_ExecuteEX("c:/Scripts/nircmd.exe", "sendkeypress F11", false, false, 1);
	//IPS_ExecuteEX("c:/Scripts/nircmd.exe", "monitor off", false, false, 1);
	if (isset($installedModules["OperationCenter"])==true)
		{  /* nur wenn OperationCenter vorhanden auch die lokale Soundausgabe starten*/
		IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
		tts_play(1,'Ausgabe Monitor Status wird unterstützt','',2);
		}
	}

?>