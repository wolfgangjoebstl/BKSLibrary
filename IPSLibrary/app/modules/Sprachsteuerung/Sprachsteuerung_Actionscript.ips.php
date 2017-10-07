<?

/***********************************************************************
 *
 *    Sprachsteuerung
 *
 *
 * gibt über den entsprechenden Lautsprecherausgang Musik, Hinweistöne oder Text aus
 * Lautsprecherausgang wird von MP Ton definiert
 *
 ***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ("Sprachsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Sprachsteuerung");
IPSUtils_Include ("Sprachsteuerung_Library.class.php","IPSLibrary::app::modules::Sprachsteuerung");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
}

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('Sprachsteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Sprachsteuerung'));
echo "Category App ID:".$CategoryIdApp."\n";
echo "Category Script ID:".$scriptId."\n";

$scriptIdSprachsteuerung   = IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);
$id_sk1_musik = IPS_GetInstanceIDByName("MP Musik", $scriptIdSprachsteuerung);
$id_sk1_ton = IPS_GetInstanceIDByName("MP Ton", $scriptIdSprachsteuerung);
$id_sk1_tts = IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);
$id_sk1_counter = CreateVariable("Counter", 1, $scriptIdSprachsteuerung , 0, "",0,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
echo "Text To Speech Werte\n";
echo "  TTSAudioOutput:".IPS_GetProperty($id_sk1_tts,"TTSAudioOutput")."\n";
echo "  TTSEngine     :".IPS_GetProperty($id_sk1_tts,"TTSEngine")."\n\n";
echo "Tonausgabe Werte\n";
echo "  DeviceName    :".IPS_GetProperty($id_sk1_ton,"DeviceName")."\n";
echo "  DeviceNum     :".IPS_GetProperty($id_sk1_ton,"DeviceNum")."\n";
echo "  UpdateInterval:".IPS_GetProperty($id_sk1_ton,"UpdateInterval")."\n";
echo "  DeviceDriver  :".IPS_GetProperty($id_sk1_ton,"DeviceDriver")."\n\n";
echo "Musikausgabe Werte\n";
echo "  DeviceName    :".IPS_GetProperty($id_sk1_musik,"DeviceName")."\n";
echo "  DeviceNum     :".IPS_GetProperty($id_sk1_musik,"DeviceNum")."\n";
echo "  UpdateInterval:".IPS_GetProperty($id_sk1_musik,"UpdateInterval")."\n";
echo "  DeviceDriver  :".IPS_GetProperty($id_sk1_musik,"DeviceDriver")."\n\n";
echo "Einstellungen\n";
echo "  Status        :".IPS_GetVariableIDByName("Status", $id_sk1_musik)."\n";
echo "  Lautstärke    :".IPS_GetVariableIDByName("Lautstärke", $id_sk1_musik)."\n";

//echo "TTSAudioOutput :".IPS_GetProperty($id_sk1_ton,"TTSAudioOutput")."\n";
//echo "TTSEngine :".IPS_GetProperty(50984,"TTSEngine")."\n";

 //Fügen Sie hier Ihren Skriptquellcode ein

//wird in das Standard Include script kopiert

if (isset($_IPS['Text']))
	{
   tts_play(1,$_IPS['Text'],'',2);
   }
else
	{
	tts_play(1,'','hinweis',2);      /* erste Position ist die Soundkarte, steht immer auf 1, was anderes haben wir noch nicht programmiert. */
	//tts_play(1,'Hallo Claudia ich liebe dich so sehr','',2);
	//tts_play(1,'Hello Wolfgang How are you ?','',2);
	}

/*

Routine tts_play ist schon in der Library definiert

Allerdings die Installation der Mediaplayer funktioniert noch nicht muessen haendisch angelegt werden


*/





/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */

	echo "Alle \"Text To Speech\" Instanzen.\n";
	//$TextToSpeachID = @IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);
	$TextToSpeach = IPS_GetInstanceListByModuleID("{684CC410-6777-46DD-A33F-C18AC615BB94}");
	foreach ($TextToSpeach as $TextToSpeachID)
		{
		echo "   ".$TextToSpeachID." ".IPS_GetName($TextToSpeachID)."\n";
		echo "         ".IPS_GetConfiguration($TextToSpeachID)."\n"; 	
		}

	echo "Alle \"Media Player\" Instanzen.\n";
	$MediaPlayer = IPS_GetInstanceListByModuleID("{2999EBBB-5D36-407E-A52B-E9142A45F19C}");
	foreach ($MediaPlayer as $MediaPlayerID)
		{
		echo "   ".$MediaPlayerID." ".IPS_GetName($MediaPlayerID)."\n";
		echo "         ".IPS_GetConfiguration($MediaPlayerID)."\n"; 
		}

	$SprachConfig=Sprachsteuerung_Configuration();
	echo $SprachConfig["Engine".$SprachConfig["Language"]]."\n";
	$TextToSpeachID = @IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);

	IPS_SetProperty($TextToSpeachID,"TTSEngine",$SprachConfig["Engine".$SprachConfig["Language"]]);
	IPS_ApplyChanges($TextToSpeachID);
	
	tts_play(1,'Hallo Claudia ich liebe dich so sehr','',2);
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{

	}


/*********************************************************************************************/


/*********************************************************************************************/




?>