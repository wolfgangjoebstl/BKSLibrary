<?

/*
	 * @defgroup ipstwilight IPSTwilight
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 *
	 *
	 * @file          Gartensteuerung.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

if (isset($_IPS['Monitor']))
	{
   if ($_IPS['Monitor']=="on")
		{
		IPS_ExecuteEX("c:/Scripts/nircmd.exe", "sendkeypress F11", false, false, 1);
		tts_play(1,'Monitor ein','',2);
		}
   if ($_IPS['Monitor']=="off")
		{
		IPS_ExecuteEX("c:/Scripts/nircmd.exe", "monitor off", false, false, 1);
		tts_play(1,'Monitor aus','',2);
		}
   }


if ($_IPS['SENDER']=="Execute")
	{
	//IPS_ExecuteEX("c:/Scripts/nircmd.exe", "sendkeypress F11", false, false, 1);
	//IPS_ExecuteEX("c:/Scripts/nircmd.exe", "monitor off", false, false, 1);
	tts_play(1,'Monitor aus','',2);

	}

?>
