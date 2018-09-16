<?

/*
	 * @defgroup Startpage
	 *
	 * Script zur Ansteuerung der Startpage
	 *
	 *
	 * @file          Startpage.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');

	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) 
		{
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Startpage',$repository);
		}

/***************************************************************************************************
 *
 *  Bilderverzeichnis initialisieren 
 *  file:///C|/Users/Wolfgang/Dropbox/Privat/IP-Symcon/pictures/07340IMG_1215.jpg
 *
 *******************************/

	$configuration=startpage_configuration();
	$bilderverzeichnis=$configuration["Directories"]["Pictures"];
	$picturedir=IPS_GetKernelDir()."webfront\\user\\pictures\\";
	
	$file=array();
	$handle=opendir ($bilderverzeichnis);
	$i=0;
	while ( false !== ($datei = readdir ($handle)) )
		{
		if ( $datei != "." && $datei != ".." && $datei != "Thumbs.db" && (is_dir($bilderverzeichnis.$datei)==false) ) 
			{
			$i++;
 			$file[$i]=$datei;
			}
		}
	closedir($handle);

	$check=array();
	$handle=opendir ($picturedir);
	while ( false !== ($datei = readdir ($handle)) )
		{
		if ($datei != "." && $datei != ".." && $datei != "Thumbs.db") 
			{
			$check[$datei]=true;
			}
		}
	closedir($handle);


	foreach ($file as $filename)
		{
		if ( isset($check[$filename]) == true )
			{
			$check[$filename]=false;
			//echo "Datei ".$filename." in beiden Verzeichnissen.\n";
			}
		if ( is_file($bilderverzeichnis.$filename)==true )
			{	
			echo "copy from ".$bilderverzeichnis.$filename." to ".$picturedir.$filename."\n";	
			copy($bilderverzeichnis.$filename,$picturedir.$filename);
			}
		}

	echo "Verzeichnis für Anzeige auf Startpage:\n";	
	$i=0;
	foreach ($check as $filename => $delete)
		{
		if ($delete == true)
			{
			echo "Datei ".$filename." wird gelöscht.\n";
			}
		else
			{
			echo "   ".$filename."\n";
			$i++;		
			}	
		}	
	echo "insgesamt ".$i." Dateien.\n";

/**************************************************************************************************************************
 *
 *   Netplayer, derzeit deaktiviert
 *
 */
  
include_once IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\NetPlayer\NetPlayer.inc.php";

if (false)
	{
	NetPlayer_Power(true);
	$value=0;   /* MP3 Player */
	$value=1;   /* Radio */
	NetPlayer_SetSource($value);
	
	/* einmal aufrufen, damit Radiosenderliste übernommen wird */
	NetPlayer_NavigateRadioForward(NP_COUNT_RADIOVARIABLE);
	
	$radioName = array();
	$profileData   = IPS_GetVariableProfile('NetPlayer_RadioList');
	$associations  = $profileData['Associations'];
	foreach ($associations as $idx=>$association)
		{
		echo "Radiosender : ".$idx." ".$association['Name']."\n";
		//print_r($association);
	   if ($association['Value']==$value)
			{
	      $radioName = $association['Name'];
	   	}
	   }
	$radioList = NetPlayer_GetRadioList();
	$radioUrl  = $radioList[$radioName];
	NetPlayer_PlayRadio($radioUrl, $radioName);
	echo "Es spielt jetzt ".$radioName." von ".$radioUrl."\n";
	}
//NetPlayer_Power(false);

?>