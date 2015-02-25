<?

/***********************************************************************

Automatisches Ansteuern der Heizung, durch Timer, mit Overwrite etc.

zB durch wenn die FS20-STR einen Heizkoerper ansteuert, gleich wieder den Status aendern

funktioniert nur mit elektrischen Heizkoerpern

***********************************************************/

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

include_once(IPS_GetKernelDir()."scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php");
IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");

/******************************************************

				INIT

*************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('Autosteuerung',$repository);
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
$scriptId  = IPS_GetObjectIDByIdent('Autosteuerung', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.Autosteuerung'));
echo "Category App ID:".$CategoryIdApp."\n";
echo "Category Script ID:".$scriptId."\n";

$name="Ventilator_Steuerung";
$categoryId_Autosteuerung  = CreateCategory($name, $CategoryIdData, 10);
$AnwesenheitssimulationID = IPS_GetObjectIDByName("Anwesenheitssimulation",$categoryId_Autosteuerung);

$configuration = Autosteuerung_GetEventConfiguration();
$scenes=Autosteuerung_GetScenes();
//print_r($configuration);


/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	/* von der Konsole aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{
	/* eine Variablenaenderung ist aufgetreten */

	if (array_key_exists($_IPS['VARIABLE'], $configuration))
		{
		$params=$configuration[$_IPS['VARIABLE']];
		$wert=$params[1];
  		//tts_play(1,$_IPS['VARIABLE'].' and '.$wert,'',2);
		switch ($wert)
		   {
		   case "Anwesenheit":
		      If (GetValue($AnwesenheitssimulationID)>0)
		         {
		         //Script alle 5 Minuten ausführen
		 			IPS_SetScriptTimer($_IPS['SELF'], 5*60);
		         }
				else
				   {
				   //Script nicht merh automatisch ausführen
		 			IPS_SetScriptTimer($_IPS['SELF'], 0);
				   }
		      break;
		   default:
				eval($params[1]);
				break;
			}
		}
	else
	   {
  		tts_play(1,'Button pressed'.$_IPS['VARIABLE'],'',2);
  		}
  		
	if (false)
		   {
			$remServer=array(
				"BKS-Server"           	=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/',
						);
			foreach ($remServer as $Server)
				{
				$rpc = new JSONRPC($Server);
				}
			$rpc->IPS_RunScript(10004);
		   }
	
	switch ($_IPS['VARIABLE'])
			{

			/* Positionswerte geändert */

		   case "32688": /* Arbeitszimmer Pos Aenderung Heizung*/
				break;

			case "10884": /* Kellerzimmer Pos Aenderung Heizung*/
				break;

			case "17661": /* Wohnzimmer Pos Aenderung Heizung*/
				break;
			}
	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{

	}


/*********************************************************************************************/


	if (GetValue($AnwesenheitssimulationID)>0)
 		{//Anwesenheitssimulation aktiv
		echo "\nAnwesenheitssimulation eingeschaltet. \n";
		print_r($scenes);
	   foreach($scenes as $scene){

       	$actualTime = explode("-",$scene["ACTIVE_FROM_TO"]);
        	$actualTimeStart = explode(":",$actualTime[0]);
        	$actualTimeStartHour = $actualTimeStart[0];
        	$actualTimeStartMinute = $actualTimeStart[1];
        	$actualTimeStop = explode(":",$actualTime[1]);
        	$actualTimeStopHour = $actualTimeStop[0];
        	$actualTimeStopMinute = $actualTimeStop[1];

        	$timeStart = mktime($actualTimeStartHour,$actualTimeStartMinute);
        	$timeStop = mktime($actualTimeStopHour,$actualTimeStopMinute);
      	$now = time();

       	if ($now > $timeStart && $now < $timeStop)
			 	{
			 	echo "Es ist Zeit für Szene ".$scene['NAME']."\n";
          	$minutesRange = ($timeStop-$timeStart)/60;
          	$actionTriggerMinutes = 5;
            $rndVal = rand(1,100);
				echo "Zufallszahl:".$rndVal."\n";
            if($rndVal < $scene["EVENT_CHANCE"])
					{
					echo "Jetzt wird der Timer gesetzt .\n";
               IPSLight_SetGroupByName($scene["EVENT_IPSLIGHT_GRP"], true);
               $EreignisID = @IPS_GetEventIDByName($scene["NAME"]."_EVENT", IPS_GetParent($_IPS['SELF']));
               if ($EreignisID === false)
						{ //Event nicht gefunden > neu anlegen
                  $EreignisID = IPS_CreateEvent(1);
                  IPS_SetName($EreignisID,$scene["NAME"]."_EVENT");
                  IPS_SetParent($EreignisID, IPS_GetParent($_IPS['SELF']));
               	}
               IPS_SetEventActive($EreignisID,true);
               IPS_SetEventCyclic($EreignisID, 1, 0, 0, 0, 0,0);
               IPS_SetEventCyclicTimeBounds($EreignisID,$now+$scene["EVENT_DURATION"]*60,0);
               IPS_SetEventCyclicDateBounds($EreignisID,$now+$scene["EVENT_DURATION"]*60,0);
               IPS_SetEventScript($EreignisID,
                                                "include(\"scripts\IPSLibrary\app\modules\IPSLight\IPSLight.inc.php\");\n".
                                                "IPSLight_SetGroupByName(\"".$scene["EVENT_IPSLIGHT_GRP"]."\", false);");

            	}
        		}
		   }

		 }
	else
		{
    	//Anwesenheitssimulation nicht aktiv
    	//Alle Timer deaktivieren
    	foreach($scenes as $scene)
		 	{
      	$EreignisID = @IPS_GetEventIDByName($scene["NAME"]."_EVENT", IPS_GetParent($_IPS['SELF']));
        	if ($EreignisID != false)
			  	{
         	IPS_SetEventActive($EreignisID,false);
        		}
    		}
 		}






/*********************************************************************************************/




?>
