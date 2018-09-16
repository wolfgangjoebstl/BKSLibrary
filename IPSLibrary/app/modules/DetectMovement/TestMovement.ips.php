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
IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");

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
<<<<<<< HEAD
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
=======
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
>>>>>>> df9531406147b75ff6e25e9bc86a789befba2a80
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
		if (function_exists('IPSDetectTemperatureHandler_GetEventConfiguration'))	$temperature_config=IPSDetectTemperatureHandler_GetEventConfiguration();
		else $temperature_config=array();
		if (function_exists('IPSDetectHumidityHandler_GetEventConfiguration'))		$humidity_config=IPSDetectHumidityHandler_GetEventConfiguration();
		else $humidity_config=array();
		if (function_exists('IPSDetectHeatControlHandler_GetEventConfiguration'))	$heatcontrol_config=IPSDetectHeatControlHandler_GetEventConfiguration();
		else $heatcontrol_config=array();
<<<<<<< HEAD
		
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

=======
	
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

		
		if (false) {		
		$i=0;
		$eventlistDelete=array();		// Sammlung der Events für die es kein Objekt mehr dazu gibt
	
		/* der Reihe nach die Events die unter dem Handler haengen durchgehen und plausibilisieren 
		 *
		 * dabei die Erfassung, Speicherung, Bearbeitung von der Visualisiserung trennen
		 *
		 *******************************/
	
		$html="";
		$html.="<style>";
		$html.='#customers { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; font-size: 12px; color:black; border-collapse: collapse; width: 100%; }';
		$html.='#customers td, #customers th { border: 1px solid #ddd; padding: 8px; }';
		$html.='#customers tr:nth-child(even){background-color: #f2f2f2;}';
		$html.='#customers tr:nth-child(odd){background-color: #e2e2e2;}';
		$html.='#customers tr:hover {background-color: #ddd;}';
		$html.='#customers th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; }';
		$html.="</style>";
	
		$html.='<table id="customers" >';
		$html.="<tr><th>Event #</th><th>ID</th><th>Name</th><th>ObjektID</th><th>Module</th><th>Objektpfad</th><th>Objektname</th><th>Funktion</th><th>Konfiguration</th><th>Homematic</th><th>Detect Movement</th><th>Autosteuerung</th></tr>";
		$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
		$children=IPS_GetChildrenIDs($scriptId);		// alle Events des IPSMessageHandler erfassen				
		foreach ($children as $childrenID)
			{
			$name=IPS_GetName($childrenID);
			//echo $name."\n";
			$eventID_str=substr($name,Strpos($name,"_")+1,10);
			$eventID=(integer)$eventID_str;
			if (substr($name,0,1)=="O")									// sollte mit O anfangen
				{
				$html.="<tr><td>".$i."</td><td>".$childrenID."</td><td>".IPS_GetName($childrenID)."</td><td>".$eventID."</td>";
				if (IPS_ObjectExists($eventID)==false)
					{
					/* Objekt für das Event existiert nicht */
					$html.='<td bgcolor="#00FF00">does not exists any longer. Event has to be deleted ***********.</td>';
					$eventlistDelete[$eventID_str]=1;
					}	
				else
					{
					/* Objekt für das Event existiert, den Pfad dazu ausgeben */
					$instanzID=IPS_GetParent($eventID);
					if ($debug) echo "Objekt : ".$eventID." Instanz : ".IPS_GetName($instanzID)." Type : ";
					$instanz="";
					switch (IPS_GetObject($instanzID)["ObjectType"])
						{
						/* 0: Kategorie, 1: Instanz, 2: Variable, 3: Skript, 4: Ereignis, 5: Media, 6: Link */
						case 0:
							if ($debug) echo "Kategorie";
							break;
						case 1:
							$instanz=IPS_GetInstance($instanzID)["ModuleInfo"]["ModuleName"];
							if ($debug) echo "Instanz ";
							break;
						case 2:
							if ($debug) echo "Variable";
							break;
						case 3:	
							if ($debug) echo "Skript";
							break;
						case 4:
							if ($debug) echo "Ereignis";
							break;
						case 5:
							if ($debug) echo "Media";
							break;
						case 6:
							if ($debug) echo "Link";
							break;
						default:
							echo "unknown";
							break;
						}
					if (IPS_GetObject($eventID)["ObjectType"]==2) 	
						{
						if ($debug) echo $instanz."\n";
						}
					else 	
						{
						echo "Fehler, Objekt ist vom Typ keine Variable.   ";
						echo "Objekt : ".$eventID." Instanz : ".IPS_GetName($instanzID)." \n ";
						}
					//$eventlistConfig[$eventID_str]["Instanz"]=$instanz;
					//print_r($eventlistConfig);
					$html.="<td>".IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($eventID))))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($eventID)))."/".IPS_GetName(IPS_GetParent($eventID))."</td><td>".
					IPS_GetName($eventID)."</td><td>".$instanz."</td>";
					if (isset($movement_config[$eventID_str]))
						{	/* kommt in der Detect Movement Config vor */
						if (isset($eventlistConfig[$eventID_str]))
							{
  							$html.="<td>Movement</td><td>".$eventlistConfig[$eventID_str][1]."</td>";
  							//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							$html.="<td>Movement</td><td>Error no Configuration available **************</td>";
							$eventlistDelete[$eventID_str]=2;						
							}
						}
					elseif (isset($temperature_config[$eventID_str]))
						{	/* kommt in der Detect Temperature Config vor */
						if (isset($eventlistConfig[$eventID_str]))
							{
  							$html.="<td>Temperatur</td><td>".$eventlistConfig[$eventID_str][1]."</td>";
	  						//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							$html.="<td>Temperatur</td><td>Error no Configuration available **************</td>";
							$eventlistDelete[$eventID_str]=2;
							}
						}	
					elseif (isset($humidity_config[$eventID_str]))
						{	/* kommt in der Detect Humidity Config vor */
						if (isset($eventlistConfig[$eventID_str]))
							{
  							$html.="<td>Humidity</td><td>".$eventlistConfig[$eventID_str][1]."</td>";
	  						//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							$html.="<td>Humidity</td><td>Error no Configuration available **************</td>";
							$eventlistDelete[$eventID_str]=2;
							}
						}	
					elseif (isset($heatcontrol_config[$eventID_str]))
						{	/* kommt in der Detect Heatcontrol Config vor */
						if (isset($eventlistConfig[$eventID_str]))
							{
							$html.="<td>HeatControl</td><td>".$eventlistConfig[$eventID_str][1]."</td>";
	  						//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							$html.="<td>HeatContol</td><td>Error no Configuration available **************</td>";
							$eventlistDelete[$eventID_str]=2;
							}
						}	
					else
						{	/* kommt in keiner Detect Config vor */
						if (isset($eventlistConfig[$eventID_str]))
							{
  							$html.="<td></td><td>".$eventlistConfig[$eventID_str][1]."</td>";
	  						//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							$html.="<td></td><td>Error no Configuration available **************</td>";
							$eventlistDelete[$eventID_str]=2;
							}
						}	
						
					if (isset($motionDevice[$eventID])==true)
						{ 
						$html.="<td>Homematic Motion</td>";
						$motionDevice[$eventID]=false;
						}
					else $html.="<td></td>";	
					
					if (isset($movement_config[$eventID])==true)
						{ 
						$html.="<td>".$movement_config[$eventID][1]."</td>";
						$movement_config[$eventID][4]="found";
						}
					else $html.="<td></td>";	
					
					if (isset($autosteuerung_config[$eventID])==true)
						{ 
						$html.="<td>".$autosteuerung_config[$eventID][1]."</td>";
						}
					else $html.="<td></td>";	
					}		// ende Objekt existiert
				$html.="</tr>";	 
				}			// ende check substring fangt mit 0 an	
			else $html.="     Event not automatically generated, does not fit to Standards.</tr>";	
			$i++;
			IPS_SetPosition($childrenID,$eventID);
			}			// ende foreach children
		$html.="</table>";
		} // ende false
	
>>>>>>> df9531406147b75ff6e25e9bc86a789befba2a80
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
		echo "\nPlease check this Homematic Devices, Bewegungsmelder ohne Custom Components Eintrag:\n";		
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
<<<<<<< HEAD
=======
				}
			
		$html=$detectMovement->writeEventListTable();
		echo $html;
		SetValue($TableEventsID,$html);
		} 		// ende kein fatal error			
	}

/*****************************************************************************************/

class TestMovement
	{
	
	private $debug;
	public $eventlist;
	public $eventlistDelete;

	
	/**********************************
	 *
	 * der Reihe nach die Events die unter dem Handler haengen durchgehen und plausibilisieren 
	 *
	 * dabei die Erfassung, Speicherung, Bearbeitung von der Visualisiserung trennen
	 *
	 *******************************/
	
	public function __construct($debug) 
		{	
		$this->debug=$debug;
		if ($debug) echo "TestMovement Construct, zusätzliche Checks bei der Eventbearbeitung:\n";

		/* Autosteuerung */
		IPSUtils_Include ("Autosteuerung_Configuration.inc.php","IPSLibrary::config::modules::Autosteuerung");
		$autosteuerung_config=Autosteuerung_GetEventConfiguration();
	
		/* IPSComponent mit CustomComponent */ 	
 		$eventlistConfig = IPSMessageHandler_GetEventConfiguration();

		$motionDevice=$this->findMotionDetection();								

		$delete=0;			// mitzaehlen wieviele events geloescht werden muessen 

		$i=0;
		$eventlist=array();
		$this->eventlistDelete=array();		// Sammlung der Events für die es kein Objekt mehr dazu gibt
		$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
		$children=IPS_GetChildrenIDs($scriptId);		// alle Events des IPSMessageHandler erfassen
		foreach ($children as $childrenID)
			{
			$name=IPS_GetName($childrenID);
			$eventID_str=substr($name,Strpos($name,"_")+1,10);
			$eventID=(integer)$eventID_str;
			if (substr($name,0,1)=="O")									// sollte mit O anfangen
				{
				$eventlist[$i]["OID"]=$childrenID;				
				$eventlist[$i]["Name"]=IPS_GetName($childrenID);
				$eventlist[$i]["EventID"]=$eventID;
				if (IPS_ObjectExists($eventID)==false)
					{ /* Objekt für das Event existiert nicht */
					$delete++;
					if ($debug) echo "Objekt : ".$eventID." existiert nicht.\n";
					$eventlist[$i]["Fehler"]='does not exists any longer. Event has to be deleted ***********.';
					$this->eventlistDelete[$eventID_str]["Fehler"]=1;
					$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;
					if (isset($eventlistConfig[$eventID_str])) echo "**** und Event ".$eventID_str." auch aus der Config Datei loeschen.: ".$eventlistConfig[$eventID_str][1].$eventlistConfig[$eventID_str][2]."\n";
					}	
				else
					{ /* Objekt für das Event existiert, den Pfad dazu ausgeben */
					$instanzID=IPS_GetParent($eventID);
					if ($debug) echo "Objekt : ".$eventID." Instanz : ".IPS_GetName($instanzID)." Type : ";
					$instanz="";
					switch (IPS_GetObject($instanzID)["ObjectType"])
						{
						/* 0: Kategorie, 1: Instanz, 2: Variable, 3: Skript, 4: Ereignis, 5: Media, 6: Link */
						case 0:
							if ($debug) echo "Kategorie";
							break;
						case 1:
							$instanz=IPS_GetInstance($instanzID)["ModuleInfo"]["ModuleName"];
							if ($debug) echo "Instanz ";
							break;
						case 2:
							if ($debug) echo "Variable";
							break;
						case 3:	
							if ($debug) echo "Skript";
							break;
						case 4:
							if ($debug) echo "Ereignis";
							break;
						case 5:
							if ($debug) echo "Media";
							break;
						case 6:
							if ($debug) echo "Link";
							break;
						default:
							echo "unknown";
							break;
						}
					if (IPS_GetObject($eventID)["ObjectType"]==2) 	
						{
						if ($debug) echo $instanz."\n";
						}
					else 	
						{
						echo "Fehler, Objekt ist vom Typ keine Variable.   ";
						echo "Objekt : ".$eventID." Instanz : ".IPS_GetName($instanzID)." \n ";
						}
					$eventlist[$i]["Pfad"]=IPS_GetName(IPS_GetParent(IPS_GetParent(IPS_GetParent($eventID))))."/".IPS_GetName(IPS_GetParent(IPS_GetParent($eventID)))."/".IPS_GetName(IPS_GetParent($eventID));
					$eventlist[$i]["NameEvent"]=IPS_GetName($eventID);
					$eventlist[$i]["Instanz"]=$instanz;
					if (isset($movement_config[$eventID_str]))
						{	/* kommt in der Detect Movement Config vor */
  						$eventlist[$i]["Typ"]="Movement";
						if (isset($eventlistConfig[$eventID_str]))
							{
							$eventlist[$i]["Config"]=$eventlistConfig[$eventID_str][1];
  							//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							if ($debug) echo "Objekt : ".$eventID." Konfiguration nicht vorhanden.\n";
							$eventlist[$i]["Config"]='Error no Configuration available **************.';
							$this->eventlistDelete[$eventID_str]["Fehler"]=2;
							$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;						
							}
						}
					elseif (isset($temperature_config[$eventID_str]))
						{	/* kommt in der Detect Temperature Config vor */
						$eventlist[$i]["Typ"]="Temperatur";							
						if (isset($eventlistConfig[$eventID_str]))
							{
  							$eventlist[$i]["Config"]=$eventlistConfig[$eventID_str][1];
	  						//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							if ($debug) echo "Objekt : ".$eventID." Konfiguration nicht vorhanden.\n";
							$eventlist[$i]["Config"]="Error no Configuration available **************";
							$this->eventlistDelete[$eventID_str]["Fehler"]=2;
							$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;
							}
						}	
					elseif (isset($humidity_config[$eventID_str]))
						{	/* kommt in der Detect Humidity Config vor */
						$eventlist[$i]["Typ"]="Humidity";
						if (isset($eventlistConfig[$eventID_str]))
							{
							$eventlist[$i]["Config"]=$eventlistConfig[$eventID_str][1];
	  						//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							if ($debug) echo "Objekt : ".$eventID." Konfiguration nicht vorhanden.\n";
							$eventlist[$i]["Config"]="Error no Configuration available **************";
							$this->eventlistDelete[$eventID_str]["Fehler"]=2;
							$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;
							}
						}	
					elseif (isset($heatcontrol_config[$eventID_str]))
						{	/* kommt in der Detect Heatcontrol Config vor */
						$eventlist[$i]["Typ"]="HeatControl";
						if (isset($eventlistConfig[$eventID_str]))
							{
							$eventlist[$i]["Config"]=$eventlistConfig[$eventID_str][1];
	  						//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							if ($debug) echo "Objekt : ".$eventID." Konfiguration nicht vorhanden.\n";
							$eventlist[$i]["Config"]="Error no Configuration available **************";
							$this->eventlistDelete[$eventID_str]["Fehler"]=2;
							$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;
							}
						}	
					else
						{	/* kommt in keiner Detect Config vor */
						$eventlist[$i]["Typ"]="";
						if (isset($eventlistConfig[$eventID_str]))
							{
  							$eventlist[$i]["Config"]=$eventlistConfig[$eventID_str][1];
	  						//print_r($eventlistConfig[$eventID_str]);
							}
						else
							{
							if ($debug) echo "Objekt : ".$eventID." Konfiguration nicht vorhanden.\n";
							$eventlist[$i]["Config"]="Error no Configuration available **************";
							$this->eventlistDelete[$eventID_str]["Fehler"]=2;
							$this->eventlistDelete[$eventID_str]["OID"]=$childrenID;
							}
						}	
						
					if (isset($motionDevice[$eventID])==true)
						{ 
						$eventlist[$i]["Homematic"]="Homematic Motion";
						$motionDevice[$eventID]=false;
						}
					else $eventlist[$i]["Homematic"]="";	
					
					if (isset($movement_config[$eventID])==true)
						{ 
						$eventlist[$i]["DetectMovement"]=$movement_config[$eventID][1];
						$movement_config[$eventID][4]="found";
						}
					else $eventlist[$i]["DetectMovement"]="";	
					
					if (isset($autosteuerung_config[$eventID])==true)
						{ 
						$eventlist[$i]["Autosteuerung"]=$autosteuerung_config[$eventID][1];
						}
					else $eventlist[$i]["Autosteuerung"]="";	
					
					}				
				}
			$i++;
			IPS_SetPosition($childrenID,$eventID);				
			}
		$this->eventlist=$eventlist;
		if ($delete>0) echo "****Es muessen insgesamt ".$delete." Events geloescht werden, das Objekt auf das sie verweisen gibt es nicht mehr.\n";
		}	
	
	public function writeEventListTable($eventlist=array())
		{
		if (sizeof($eventlist)==0) $eventlist=$this->eventlist;
		$html="";
		$html.="<style>";
		$html.='#customers { font-family: "Trebuchet MS", Arial, Helvetica, sans-serif; font-size: 12px; color:black; border-collapse: collapse; width: 100%; }';
		$html.='#customers td, #customers th { border: 1px solid #ddd; padding: 8px; }';
		$html.='#customers tr:nth-child(even){background-color: #f2f2f2;}';
		$html.='#customers tr:nth-child(odd){background-color: #e2e2e2;}';
		$html.='#customers tr:hover {background-color: #ddd;}';
		$html.='#customers th { padding-top: 10px; padding-bottom: 10px; text-align: left; background-color: #4CAF50; color: white; }';
		$html.="</style>";
	
		$html.='<table id="customers" >';
		$html.="<tr><th>Event #</th><th>ID</th><th>Name</th><th>ObjektID</th><th>Objektpfad/Fehler</th><th>Objektname</th><th>Module</th><th>Funktion</th><th>Konfiguration</th><th>Homematic</th><th>Detect Movement</th><th>Autosteuerung</th></tr>";
		foreach ($eventlist as $index=>$childrenID)
			{
			$html.="<tr><td>".$index."</td><td>".$childrenID["OID"]."</td><td>".$childrenID["Name"]."</td><td>".$childrenID["EventID"]."</td>";
			if (isset ($childrenID["Fehler"]) )	$html.='<td bgcolor=#00FF00">"'.$childrenID["Fehler"].'</td>';
			else
				{
				$html.="<td>".$childrenID["Pfad"]."</td><td>".$childrenID["NameEvent"]."</td><td>".$childrenID["Instanz"]."</td>";
				$html.="<td>".$childrenID["Typ"]."</td><td>".$childrenID["Config"]."</td>";
				$html.="<td>".$childrenID["Homematic"]."</td><td>".$childrenID["DetectMovement"]."</td>";
				$html.="<td>".$childrenID["Autosteuerung"]."</td>";
				$html.="</tr>";	 
				}			// ende check substring fangt mit 0 an	
			}			// ende foreach children
		$html.="</table>";
		return($html);
		}	// ende function

	public function findMotionDetection()
		{
		//$alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
		$Homematic = HomematicList();
		$FS20= FS20List();
	
		$motionDevice=array();
	
		//echo "\n===========================Alle Homematic Bewegungsmelder ausgeben.\n";
		foreach ($Homematic as $Key)
			{
			/* Alle Homematic Bewegungsmelder ausgeben */
			if ( (isset($Key["COID"]["MOTION"])==true) )
				{
				/* alle Bewegungsmelder */
				$oid=(integer)$Key["COID"]["MOTION"]["OID"];
				$motionDevice[$oid]=true;
				//$log=new Motion_Logging($oid);
				//$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
				{
				/* alle Kontakte */
				$oid=(integer)$Key["COID"]["STATE"]["OID"];
				$motionDevice[$oid]=true;
				//$log=new Motion_Logging($oid);
				//$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			}
		//echo "\n===========================Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein.\n";
		IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
		$TypeFS20=RemoteAccess_TypeFS20();
		foreach ($FS20 as $Key)
			{
			/* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
			if ( (isset($Key["COID"]["MOTION"])==true) )
				{
				/* alle Bewegungsmelder */
				$oid=(integer)$Key["COID"]["MOTION"]["OID"];
				$motionDevice[$oid]=true;
				//$log=new Motion_Logging($oid);
				//$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
				}
			/* Manche FS20 Variablen sind noch nicht umprogrammiert daher mit Config Datei verknüpfen */
			if ((isset($Key["COID"]["StatusVariable"])==true))
				{
				foreach ($TypeFS20 as $Type)
					{
					if (($Type["OID"]==$Key["OID"]) and ($Type["Type"]=="Motion"))
						{
						$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
						$variabletyp=IPS_GetVariable($oid);
						IPS_SetName($oid,"MOTION");
						$motionDevice[$oid]=true;
						//$log=new Motion_Logging($oid);
						//$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
						}
					}
>>>>>>> df9531406147b75ff6e25e9bc86a789befba2a80
				}
			}
		//echo "\n===========================Alle IPCam Bewegungsmelder ausgeben.\n";
		if (isset ($installedModules["IPSCam"]))
			{	
			IPSUtils_Include ("IPSCam.inc.php",     "IPSLibrary::app::modules::IPSCam");
			$camManager = new IPSCam_Manager();
			$config     = IPSCam_GetConfiguration();
			echo "Folgende Kameras sind im Modul IPSCam vorhanden:\n";
			foreach ($config as $cam)
				{
				//echo "   Kamera : ".$cam["Name"]." vom Typ ".$cam["Type"]."\n";
				}
			if (isset ($installedModules["OperationCenter"]))
				{
				//echo "IPSCam und OperationCenter Modul installiert. \n";
				IPSUtils_Include ("OperationCenter_Configuration.inc.php",     "IPSLibrary::config::modules::OperationCenter");
				$OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
				$OperationCenterConfig=OperationCenter_Configuration();
				if (isset ($OperationCenterConfig['CAM']))
					{
					foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
						{
						$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$OperationCenterDataId);
						$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
						//echo "   Bearbeite Kamera : ".$cam_name." Cam Category ID : ".$cam_categoryId."  Motion ID : ".$WebCam_MotionID."\n";
						$motionDevice[$WebCam_MotionID]=true;
						//$log=new Motion_Logging($WebCam_MotionID);
						//$alleMotionWerte.="********* ".$cam_name."\n".$log->writeEvents()."\n\n";
						}
					}  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
				}     /* isset OperationCenter */
			}     /* isset IPSCam */
			
<<<<<<< HEAD
		$html=$detectMovement->writeEventListTable();
		echo $html;
		SetValue($TableEventsID,$html);
		} 		// ende kein fatal error			
	}


=======
		//$alleMotionWerte.="********* Gesamtdarstellung\n".$log->writeEvents(true,true)."\n\n";
		//echo $alleMotionWerte;
			
		return($motionDevice);
		}

	public function sortEventList($on)
		{
		$order=SORT_ASC;
		$new_array = array();
		$sortable_array = array();
		$array=$this->eventlist;
		if (count($array) > 0) 
			{
			foreach ($array as $k => $v) 
				{
				if (is_array($v)) 
					{
					foreach ($v as $k2 => $v2) 
						{
						if ($k2 == $on) 
							{
							$sortable_array[$k] = $v2;
							}
						}
					} 
				else 
					{
					$sortable_array[$k] = $v;
					}
				}
			switch ($order) 
				{
				case SORT_ASC:
					asort($sortable_array);
					break;
				case SORT_DESC:
					arsort($sortable_array);
					break;
				}
			foreach ($sortable_array as $k => $v) 
				{
				$new_array[$k] = $array[$k];
				}
			}
		return $new_array;
		}
		
	}		// ende class
>>>>>>> df9531406147b75ff6e25e9bc86a789befba2a80


/********
 *
 * getEvenConfiguration, delete Event and store EventConfiguration again
 *
 ********************/

function deleteEventConfigurationAuto()
	{
	
	
	
	}


?>