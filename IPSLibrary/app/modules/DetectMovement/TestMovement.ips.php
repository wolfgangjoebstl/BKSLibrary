<?

 //Fügen Sie hier Ihren Skriptquellcode ein
$startexec=microtime(true);

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

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('DetectMovement',$repository);
	}

$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,30)." ".$modules."\n";
	}
echo $inst_modules."\n\n";

if (isset ($installedModules["DetectMovement"])) { echo "Modul DetectMovement ist installiert.\n"; } else { echo "Modul DetectMovement ist NICHT installiert.\n"; break; }
if (isset ($installedModules["EvaluateHardware"])) { echo "Modul EvaluateHardware ist installiert.\n"; } else { echo "Modul EvaluateHardware ist NICHT installiert.\n"; break;}
if (isset ($installedModules["RemoteReadWrite"])) { echo "Modul RemoteReadWrite ist installiert.\n"; } else { echo "Modul RemoteReadWrite ist NICHT installiert.\n"; break;}
if (isset ($installedModules["RemoteAccess"])) { echo "Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; break;}
if (isset ($installedModules["IPSMessageHandler"])) { echo "Modul IPSMessageHandler ist installiert.\n"; } else { echo "Modul IPSMessageHandler ist NICHT installiert.\n"; break;}
if (isset ($installedModules["OperationCenter"])) { echo "Modul OperationCenter ist installiert.\n"; } else { echo "Modul OperationCenter ist NICHT installiert.\n"; break;}

/*

jetzt wird für jeden Bewegungsmelder ein Event registriert. Das führt beim Message handler dazu das die class function handle event aufgerufen woird

Selbe Routine in RemoteAccess, allerdings wird dann auch auf einem Remote Server zusaetzlich geloggt


*/

IPSUtils_Include ("IPSComponentSensor_Motion.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ("IPSComponentSensor_Temperatur.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ("IPSComponentSensor_Feuchtigkeit.class.php","IPSLibrary::app::core::IPSComponent::IPSComponentSensor");
IPSUtils_Include ('IPSMessageHandler.class.php', 'IPSLibrary::app::core::IPSMessageHandler');
IPSUtils_Include ("EvaluateHardware_Include.inc.php","IPSLibrary::app::modules::EvaluateHardware");

IPSUtils_Include ('IPSMessageHandler_Configuration.inc.php', 'IPSLibrary::config::core::IPSMessageHandler');

/****************************************************************************************************************/
/*                                                                                                              */
/*                                    Execute                                                                   */
/*                                                                                                              */
/****************************************************************************************************************/



if ($_IPS['SENDER']=="Execute")
	{
	$Homematic = HomematicList();
	$FS20= FS20List();
 	$cuscompid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.core.IPSComponent');
 	
 	$movement_config=IPSDetectMovementHandler_GetEventConfiguration();
 	//print_r($movement_config);
 	
 	$eventlist = IPSMessageHandler_GetEventConfiguration();
	//print_r($eventlist);

	$alleMotionWerte="\n\nHistorische Bewegungswerte aus den Logs der CustomComponents:\n\n";
   echo "\n";
   echo "Execute von Detect Movement, zusaetzliche Auswertungen.\n\n";
		   
	$scriptId  = IPS_GetObjectIDByIdent('IPSMessageHandler_Event', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.core.IPSMessageHandler'));
	echo"\n";
	echo "Zusätzliche Checks bei der Eventbearebitung:\n";
	echo "ScriptID der Eventbearbeitung : ".$scriptId." \n";
	echo"\n";
   $children=IPS_GetChildrenIDs($scriptId);
   $i=0;
   //print_r($children);
	foreach ($children as $childrenID)
		{
		$name=IPS_GetName($childrenID);
		$eventID_str=substr($name,Strpos($name,"_")+1,10);
		$eventID=(integer)$eventID_str;
		if (substr($name,0,1)=="O")
		   {
			if (isset($movement_config[$eventID_str]))
			   {
			   if (isset($eventlist[$eventID_str]))
			      {
  			   	echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)."   ".$eventID."  Movement: ".IPS_GetName(IPS_GetParent($eventID)).
					  			"  ".$eventlist[$eventID_str][1]."\n";
  			   	//print_r($eventlist[$eventID_str]);
			      }
			   else
			      {
			   	echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)."   ".$eventID."  Movement: ".IPS_GetName(IPS_GetParent($eventID))."\n";
			   	}
				}
			else
			   {
			   echo "Event ".str_pad($i,3)." mit ID ".$childrenID." und Name ".IPS_GetName($childrenID)."   ".$eventID."  \n";
				}
			}
		$i++;
		IPS_SetPosition($childrenID,$eventID);
		}
		
		
		
		
			echo "\n===========================Alle Homematic Bewegungsmelder ausgeben.\n";
			foreach ($Homematic as $Key)
				{
				/* Alle Homematic Bewegungsmelder ausgeben */
				if ( (isset($Key["COID"]["MOTION"])==true) )
		   		{
		   		/* alle Bewegungsmelder */

			      $oid=(integer)$Key["COID"]["MOTION"]["OID"];
					$log=new Motion_Logging($oid);
					$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
					}
				if ( (isset($Key["COID"]["STATE"])==true) and (isset($Key["COID"]["ERROR"])==true) )
	   			{
			   	/* alle Kontakte */
			      $oid=(integer)$Key["COID"]["STATE"]["OID"];
					$log=new Motion_Logging($oid);
					$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
					}
				}
			echo "\n===========================Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein.\n";
			IPSUtils_Include ("RemoteAccess_Configuration.inc.php","IPSLibrary::config::modules::RemoteAccess");
			$TypeFS20=RemoteAccess_TypeFS20();
			foreach ($FS20 as $Key)
				{
				/* Alle FS20 Bewegungsmelder ausgeben, Statusvariable muss schon umbenannt worden sein */
				if ( (isset($Key["COID"]["MOTION"])==true) )
		   		{
		   		/* alle Bewegungsmelder */

			      $oid=(integer)$Key["COID"]["MOTION"]["OID"];
					$log=new Motion_Logging($oid);
					$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
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
							$log=new Motion_Logging($oid);
							$alleMotionWerte.="********* ".$Key["Name"]."\n".$log->writeEvents()."\n\n";
		   		      }
		   	   	}
					}
				}
			echo "\n===========================Alle IPCam Bewegungsmelder ausgeben.\n";
			if (isset ($installedModules["IPSCam"]))
				{
				IPSUtils_Include ("IPSCam.inc.php",     "IPSLibrary::app::modules::IPSCam");

				$camManager = new IPSCam_Manager();
				$config     = IPSCam_GetConfiguration();
			   echo "Folgende Kameras sind im Modul IPSCam vorhanden:\n";
				foreach ($config as $cam)
			   	{
				   echo "   Kamera : ".$cam["Name"]." vom Typ ".$cam["Type"]."\n";
				   }
				if (isset ($installedModules["OperationCenter"]))
					{
					echo "IPSCam und OperationCenter Modul installiert. \n";
					IPSUtils_Include ("OperationCenter_Configuration.inc.php",     "IPSLibrary::config::modules::OperationCenter");
					$OperationCenterDataId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules'));
					$OperationCenterConfig=OperationCenter_Configuration();
   				if (isset ($OperationCenterConfig['CAM']))
						{
						foreach ($OperationCenterConfig['CAM'] as $cam_name => $cam_config)
							{
							$cam_categoryId=@IPS_GetObjectIDByName("Cam_".$cam_name,$OperationCenterDataId);
							$WebCam_MotionID = CreateVariableByName($cam_categoryId, "Cam_Motion", 0); /* 0 Boolean 1 Integer 2 Float 3 String */
							echo "   Bearbeite Kamera : ".$cam_name." Cam Category ID : ".$cam_categoryId."  Motion ID : ".$WebCam_MotionID."\n";;
							$log=new Motion_Logging($WebCam_MotionID);
							$alleMotionWerte.="********* ".$cam_name."\n".$log->writeEvents()."\n\n";
							}
						}  	/* im OperationCenter ist die Kamerabehandlung aktiviert */
					}     /* isset OperationCenter */
				}     /* isset IPSCam */


			$alleMotionWerte.="********* Gesamtdarstellung\n".$log->writeEvents(true,true)."\n\n";
			echo $alleMotionWerte;
			
			/* Detect Movement Auswertungen analysieren */
			
			/* Routine in Log_Motion uebernehmen */
			IPSUtils_Include ('DetectMovementLib.class.php', 'IPSLibrary::app::modules::DetectMovement');
			IPSUtils_Include ('DetectMovement_Configuration.inc.php', 'IPSLibrary::config::modules::DetectMovement');
		   $DetectMovementHandler = new DetectMovementHandler();
			print_r($DetectMovementHandler->ListEvents("Motion"));
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
				$log=new Motion_Logging($oid);
				$class=$log->GetComponent($oid);
				$statusID=CreateVariable("Gesamtauswertung_".$group,1,IPS_GetParent(intval($log->EreignisID)));
				SetValue($statusID,(integer)$status);
			   }

			
			foreach ($movement_config as $oid=>$params)
				{
				echo "OID: ".$oid." Name: ".str_pad(IPS_GetName(IPS_GetParent($oid)),30)." Type :".str_pad($params[0],15)."Status: ".(integer)GetValue($oid)." Gruppe ".$params[1]."\n";
				$log=new Motion_Logging($oid);
				$class=$log->GetComponent($oid);
				//print_r($class);
				echo "ParentID:".IPS_GetParent(intval($log->EreignisID))." Name :","Gesamtauswertung_".$params[1]."\n";
				$erID=CreateVariable("Gesamtauswertung_".$params[1],1,IPS_GetParent(intval($log->EreignisID)));
				}
			
			
	}

?>
