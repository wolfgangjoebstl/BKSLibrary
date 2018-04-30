<?

/*
	 * @defgroup Startpage Copy Files
	 *
	 * Script zur Ansteuerung der Startpage, Kopiert die Bilddateien in das Webfront
	 *
	 *
	 * @file          Startpage.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
	IPSUtils_Include ('Startpage_Configuration.inc.php', 'IPSLibrary::config::modules::Startpage');

	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager('Startpage',$repository);
	}

/****************************************
 *
 *  INITIALISIERUNG
 *
 *****************************/
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');

	$configuration=startpage_configuration();
	$bilderverzeichnis=$configuration["Directories"]["Pictures"];
	$picturedir=IPS_GetKernelDir()."webfront\\user\\Startpage\\user\\pictures\\";
	mkdirtree($picturedir);


/***************************************************************************************************
 *
 *  Bilderverzeichnis initialisieren 
 *  file:///C|/Users/Wolfgang/Dropbox/Privat/IP-Symcon/pictures/07340IMG_1215.jpg
 *
 *******************************/

echo "Bilderverzeichnis auslesen und kopieren : ".$bilderverzeichnis."\n";
$bilderverzeichnis = str_replace('\\','/',$bilderverzeichnis);

$file=array();
if ( is_dir ( $bilderverzeichnis ) )
	{
	$handle=opendir ($bilderverzeichnis);
	$i=0;
	while ( false !== ($datei = readdir ($handle)) )
		{
		if ( ($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($bilderverzeichnis.$datei) == false) )  
			{
			$i++;
	 		$file[$i]=$datei;
			}
		}
	closedir($handle);
	//print_r($file);
	}/* ende if isdir */
else
	{
	echo "Kein Verzeichnis mit dem Namen \"".$bilderverzeichnis."\" vorhanden.\n";
	$dirstruct=explode("/",$bilderverzeichnis);
	//print_r($dirstruct);
	$directoryPath="";
	foreach ($dirstruct as $directory)
		{
		$directoryOK=$directoryPath;
		$directoryPath.=$directory."/";
		if ( is_dir ( $directoryPath ) ) {;}
		else
			{
			if ($directory !=="") echo "Error : ".$directory." is not in ".$directoryOK."\n";
			}
		//echo $directoryPath."\n";
		} 			   
	}

$check=array();
$handle=opendir ($picturedir);
while ( false !== ($datei = readdir ($handle)) )
	{
	if (($datei != ".") && ($datei != "..") && ($datei != "Thumbs.db") && (is_dir($picturedir.$datei) == false)) 
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
	//echo "copy ".$bilderverzeichnis.$filename." nach ".$picturedir.$filename." \n";	
	copy($bilderverzeichnis.$filename,$picturedir.$filename);
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



if (false)
	{

	/**************************************************************************************************************************
	 *
	 *   Netplayer, derzeit deaktiviert
	 *
	 */
  
	include_once IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\NetPlayer\NetPlayer.inc.php";

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