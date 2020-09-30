<?

$startexec=microtime(true);
$fatalerror=false;
$debug=false;

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
//include(IPS_GetKernelDir()."scripts\_include\Logging.class.php");
//IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");

IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');

/******************************************************

				INIT

*************************************************************/

//$repository = 'https://10.0.1.6/user/repository/';
$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('DetectMovement',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();
$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
$scriptId  = IPS_GetObjectIDByIdent('TestMovement', $CategoryIdApp);

/*

Es wird für jeden Bewegungsmelder ein Event registriert. Das führt beim Message handler dazu das die class function handle event aufgerufen woird

Selbe Routine in RemoteAccess, allerdings wird dann auch auf einem Remote Server zusaetzlich geloggt

Wird von CustomComponents, RemoteAccess und DetectMovement genutzt.

*/

IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::config::modules::EvaluateHardware");

IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

$moduleManagerOC 	= new IPSModuleManager('OperationCenter',$repository);
$CategoryIdDataOC   = $moduleManagerOC->GetModuleCategoryID('data');
$categoryId_DetectMovement    = CreateCategory('DetectMovement',   $CategoryIdDataOC, 150);
$TableEventsID=CreateVariable("TableEvents",3, $categoryId_DetectMovement,0,"~HTMLBox",null,null,"");		

$detectMovement = new TestMovement($debug);

/****************************************************************************************************************/
/*                                                                                                              */
/*                                    Webfront Variablen setzen                                                 */
/*                                                                                                              */
/****************************************************************************************************************/

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	switch ($_IPS['VALUE'])
		{
		case 0:
			$html=$detectMovement->writeEventlistTable($detectMovement->eventlist);
			break;
		case 1:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("OID"));
			break;
		case 2:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Name"));
			break;
		case 3:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Pfad"));
			break;
		case 4:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("NameEvent"));
			break;
		case 5:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Instanz"));
			break;
		case 6:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Typ"));
			break;
		case 7:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Config"));
			break;
		case 8:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Homematic"));
			break;
		case 9:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("DetectMovement"));
			break;
		case 10:
			$html=$detectMovement->writeEventlistTable($detectMovement-> sortEventList("Autosteuerung"));
			break;
		default;
			break;	
		}
	SetValue($TableEventsID,$html);
	}

/****************************************************************************************************************/
/*                                                                                                              */
/*                                    Execute                                                                   */
/*                                                                                                              */
/****************************************************************************************************************/



if ($_IPS['SENDER']=="Execute")
	{
	$inst_modules="\nInstallierte Module:\n";
	foreach ($installedModules as $name=>$modules)
		{
		$inst_modules.="    ".str_pad($name,30)." ".$modules."\n";
		}
	echo $inst_modules."\n\n";

	if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; $fatalerror=true; }
	if (isset ($installedModules["EvaluateHardware"])) { echo "Modul EvaluateHardware ist installiert.\n"; } else { echo "Modul EvaluateHardware ist NICHT installiert.\n"; $fatalerror=true;}
	if (isset ($installedModules["RemoteReadWrite"])) { echo "Modul RemoteReadWrite ist installiert.\n"; } else { echo "Modul RemoteReadWrite ist NICHT installiert.\n"; $fatalerror=true;}
	if (isset ($installedModules["RemoteAccess"])) { echo "Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; $fatalerror=true;}
	if (isset ($installedModules["IPSMessageHandler"])) { echo "Modul IPSMessageHandler ist installiert.\n"; } else { echo "Modul IPSMessageHandler ist NICHT installiert.\n"; $fatalerror=true;}
	if (isset ($installedModules["CustomComponent"])) { echo "Modul CustomComponent ist installiert.\n"; } else { echo "Modul CustomComponent ist NICHT installiert.\n"; $fatalerror=true;}
	if (isset ($installedModules["Autosteuerung"])) { echo "Modul Autosteuerung ist installiert.\n"; } else { echo "Modul Autosteuerung ist NICHT installiert.\n"; $fatalerror=true;}
	if (isset ($installedModules["OperationCenter"])) { echo "Modul OperationCenter ist installiert.\n"; } else { echo "Modul OperationCenter ist NICHT installiert.\n"; $fatalerror=true;}
	if ($fatalerror==true)
		{
		echo "!!!!Fatal Error.!!!!\n";
		}
	else
		{	
		echo "\n";
		echo "Execute von TestMovement im Modul Detect Movement, zusaetzliche Auswertungen.\n\n";
		echo "ScriptID TestMovement : ".$scriptId." (".IPS_GetName($scriptId).") \n";

		echo"\n";		
		/* CustomComponents */
	 	$cuscompid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.core.IPSComponent');
		echo "Program.IPSLibrary.data.core.IPSComponent : ".$cuscompid."\n";
 	
		/* DetectMovement */
		if (function_exists('IPSDetectMovementHandler_GetEventConfiguration')) 		$movement_config=IPSDetectMovementHandler_GetEventConfiguration();
		else $movement_config=array();
		//print_r($movement_config);
		/* DetectContact */
		if (function_exists('IPSDetectContactHandler_GetEventConfiguration')) 		$movement_config=IPSDetectContactHandler_GetEventConfiguration();
		else $contact_config=array();
		/* DetectTemperature */
		if (function_exists('IPSDetectTemperatureHandler_GetEventConfiguration'))	$temperature_config=IPSDetectTemperatureHandler_GetEventConfiguration();
		else $temperature_config=array();
		if (function_exists('IPSDetectHumidityHandler_GetEventConfiguration'))		$humidity_config=IPSDetectHumidityHandler_GetEventConfiguration();
		else $humidity_config=array();
		if (function_exists('IPSDetectHeatControlHandler_GetEventConfiguration'))	$heatcontrol_config=IPSDetectHeatControlHandler_GetEventConfiguration();
		else $heatcontrol_config=array();
		/* DetectCounter */
		if (function_exists('IPSDetectCounterHandler_GetEventConfiguration')) 		$movement_config=IPSDetectCounterHandler_GetEventConfiguration();
		else $counter_config=array();
		
		/* Link Def ist auch in OperationCenter Installation im Script */
		$WFC10_PathOC        	 = $moduleManagerOC->GetConfigValue('Path', 'WFC10');				
		$categoryId_WebFrontOC         = CreateCategoryPath($WFC10_PathOC);
		CreateLinkByDestination('DetectMovement', $categoryId_DetectMovement,    $categoryId_WebFrontOC,  90);		
		
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
		else
			{
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
			}			
		$SchalterSortID=CreateVariable("Tabelle sortieren",1, $categoryId_DetectMovement,0,"SortTableEvents",$scriptId,null,"");		// CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
		
	 	//print_r($movement_config);

		/* Autosteuerung */
		IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
		$autosteuerung_config=Autosteuerung_GetEventConfiguration();
	
		/* IPSComponent mit CustomComponent */ 	
 		$eventlistConfig = IPSMessageHandler_GetEventConfiguration();
		if ($debug==true)
			{
			echo "\n";
			echo "Eventlist aus Configuration des IPSMessageHandler:\n";
			foreach ($eventlistConfig as $id => $event)
				{
				echo "   ".$id."   ".$event[0]."  ".$event[1]."  ".$event[2]."  \n";
				} 	
			//print_r($eventlistConfig);
			}
		
		/* Check ob für alle erkannten Bewegungsmelder auch ein Event registriert ist */
		$motionDevice=$detectMovement->findMotionDetection();								
		//print_r($motionDevice);

		if ($debug)
			{
			echo "\n";
			echo "Eventlist aus Evaluierung der (Event) Children des IPSMessageHandler:\n";
			print_r($detectMovement->eventlist);
			}

		echo "EventList Konfiguration hat ".sizeof($eventlistConfig)." Einträge. \n";
		//print_r($eventlistConfig);
					
		echo "Für die folgenden Events des IPSMessageHandler ist eine Lösung zu finden :\n";
		//print_r($detectMovement->eventlistDelete);
		foreach ($detectMovement->eventlistDelete as $eventID_str => $state)
			{
			$eventID=(integer)$eventID_str;
			//print_r($state);
			if ($state["Fehler"]==2)
				{
				echo "   ".$eventID_str."  no configuration Entry, Object available\n";
				}
			else
				{	
				echo "   ".$eventID_str." Objekt nicht mehr vorhanden -> ";
				if ( isset($eventlistConfig[$eventID]) ) 
					{  
					echo "hat aber noch einen konfigurationseintrag : ".$eventlistConfig[$eventID][0]."  ".$eventlistConfig[$eventID][1]."  ".$eventlistConfig[$eventID][2]."  \n";
					}
				else 
					{ /* wenn sie nicht in der Konfiguration sind gleich loeschen, ein Sicherheitsanker reicht */
					echo "und keine Konfiguration vorgesehen, Event wird automatisch gelöscht\n";
					IPS_DeleteEvent($state["OID"]);
					}
				}
			}
		echo "\nPlease check this Homematic Devices, Bewegungsmelder ohne Custom Components Eintrag (generated by testMovement::findMotionDetection):\n";		
		foreach ($motionDevice as $index => $entry) 
			{
			echo "   ".$index."    ".IPS_GetName($index)."/".IPS_GetName(IPS_GetParent($index))."\n";
			}
		

		echo "\nDetect Movement Auswertungen analysieren :\n";
			
			/* Routine in Log_Motion uebernehmen */
			IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
			IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
			$DetectMovementHandler = new DetectMovementHandler();
			echo "\nList Event Motion:\n";
			print_r($DetectMovementHandler->ListEvents("Motion"));
			echo "\nList Event Contact:\n";
			print_r($DetectMovementHandler->ListEvents("Contact"));

			$groups=$DetectMovementHandler->ListGroups();
			foreach($groups as $group=>$name)
				{
				echo "Gruppe ".$group." behandeln.\n";
				$config=$DetectMovementHandler->ListEvents($group);
				$status=false;
				foreach ($config as $oid=>$params)
					{
					$status=$status || GetValue($oid);
					echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)."Status: ".(integer)GetValue($oid)." ".(integer)$status."\n";
					}
				echo "Gruppe ".$group." hat neuen Status : ".(integer)$status."\n";
				//$log=new Motion_Logging($oid);
				//$class=$log->GetComponent($oid);
				//$statusID=CreateVariable("Gesamtauswertung_".$group,1,IPS_GetParent(intval($log->EreignisID)));
				//SetValue($statusID,(integer)$status);
				}

			
			foreach ($movement_config as $oid=>$params)
				{
				echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)." Type :".str_pad($params[0],15)."Status: ".(integer)GetValue($oid)." Gruppe ".$params[1]."\n";
				//$log=new Motion_Logging($oid);
				//$class=$log->GetComponent($oid);
				//print_r($class);
				//echo "ParentID:".IPS_GetParent(intval($log->EreignisID))." Name :","Gesamtauswertung_".$params[1]."\n";
				//$erID=CreateVariable("Gesamtauswertung_".$params[1],1,IPS_GetParent(intval($log->EreignisID)));
				}
			
		$html=$detectMovement->writeEventListTable();
		echo $html;
		SetValue($TableEventsID,$html);
		} 		// ende kein fatal error			
	}




/********
 *
 * getEvenConfiguration, delete Event and store EventConfiguration again
 *
 ********************/

function deleteEventConfigurationAuto()
	{
	
	
	
	}


?>