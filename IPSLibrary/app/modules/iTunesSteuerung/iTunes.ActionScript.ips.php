<?


/*

iTunes.ActionScript

Funktionen:

lokale Mediafunktionen umsetzen. Es kann auch Autostuerung eingesetzt werden.

*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\Configuration.inc.php");
Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\config\modules\iTunesSteuerung\iTunes.Configuration.inc.php");

IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');

/****************************************************************/

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
	$moduleManager = new IPSModuleManager('iTunesSteuerung',$repository);
	}

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');

$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

/****************************************************************
 *
 *  Init
 *
 */

$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');

$scriptIdiTunesSteuerung   = IPS_GetScriptIDByName('iTunes.ActionScript', $CategoryIdApp);

$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);

$object_data= new ipsobject($CategoryIdData);
$object_app= new ipsobject($CategoryIdApp);

$NachrichtenID = $object_data->osearch("Nachricht");
$NachrichtenScriptID  = $object_app->osearch("Nachricht");

$fatalerror=false;
if (isset($NachrichtenScriptID))
	{
	$object3= new ipsobject($NachrichtenID);
	$NachrichtenInputID=$object3->osearch("Input");
	//$object3->oprint();
	/* logging in einem File und in einem String am Webfront */
	$log_iTunes=new Logging("C:\Scripts\iTunes\Log_iTunes.csv",$NachrichtenInputID);
	}
else $fatalerror=true;

/****************************************************************
 *
 *  Konfiguration
 *
 */
	
	$config=iTunes_Configuration();
	
/****************************************************************/

if ($_IPS['SENDER'] == "Execute")
	{
	echo "Script wurde direkt aufgerufen.\n";
	echo "\n";
	echo "Category App           ID: ".$CategoryIdApp."\n";
	echo "Category Data          ID: ".$CategoryIdData."\n";
	echo "Webfront Administrator ID: ".$categoryId_WebFront."     ".$WFC10_Path."\n";
	echo "Nachrichten Script     ID: ".$NachrichtenScriptID."\n";
	echo "Nachrichten      Input ID: ".$NachrichtenInputID."\n\n";
		
	echo "Fix Befehle für das Fernsehen mit VLC absetzen.\n";
	if ( isset($config["iTunes"]["Fernsehen"])==true )
		{
		$configTunes=$config["iTunes"]["Fernsehen"];
		if ( isset($configTunes["EXECUTE"])==true )		/*Wenn Execute gesetzt ist kann etwas unternommen werden */
			{
			if (is_array($configTunes["EXECUTE"]) == true) 		/* neuerdings kann der Befehl auch ein Array sein. Sonst entweder leer oder ein Exec Befehl */
				{
				echo "Erweiterte Konfiguration von EXECUTE Parameter. Um Start/Stop erweitern.\n";
				$configTunes["EXECUTE"]["StartStop"]="Start";
				$configCommand=json_encode($configTunes["EXECUTE"]);
				$Kommando=json_decode($configCommand);
				if (isset($Kommando->Command)==true) echo "Befehl ".$Kommando->Command."\n";
				if (isset($Kommando->Parameter)==true) echo "Parameter ".$Kommando->Parameter."\n";				
				}
			else
				{
				$configCommand=$configTunes["EXECUTE"];
				echo "Standard Konfiguration von EXECUTE mit Parameter ".$configCommand.".\n";
				print_r(json_decode($configCommand));
				if (json_decode($configCommand)==Null) echo "keine json decodierung.\n";
				}			
		$Server=getHostAddress();
		if ($Server=="")
			{
			IPS_ExecuteEX($configTunes["EXECUTE"], "", false, false, 1);	
			}
		else
			{
			echo "Verfügbare RemoteAccess Server:\n";
			print_r($Server);		
			$rpc = new JSONRPC($Server);
			print_r($configTunes);
			//$rpc->IPS_ExecuteEX($configTunes["EXECUTE"], "", false, false, 1);  Remote Access von IPS_ExecuteEx funktioniert aus Sicherheitsgründen nicht mehr
			$monitorID=getMonitorID($rpc,$configTunes);
							if ($monitorID !== false)
								{
								$monitor=array("VLC" => $configCommand);
								$rpc->IPS_RunScriptEx($monitorID,$monitor);					
								}			
			}
		}
		}

	$log_iTunes->LogMessage("Script wurde direkt aufgerufen");
	$log_iTunes->LogNachrichten("Script wurde direkt aufgerufen");
	}

if ($_IPS['SENDER'] == "WebFront")
	{
	//echo "Script wurde über Webfront aufgerufen.\n";
	$oid=$_IPS['VARIABLE'];
	$name=IPS_GetName($oid);
	$category=IPS_GetName(IPS_GetParent($oid));
	$module=IPS_GetName(IPS_GetParent(IPS_GetParent($oid)));
	$log_iTunes->LogMessage("Script wurde über Webfront von Variable ID :".$oid." aufgerufen.");
	$log_iTunes->LogNachrichten("Variable ID :".$oid." ".$name."/".$category."/".$module." aufgerufen.");
	/* Bearbeitung anhand vom Namen der Variable unterschiedlich */
	switch ($name)			
		{
		case "Fernsehen":
			if ( isset($config["iTunes"][$name])==true )		// Konfig Eintrag vorhanden
				{
				$configTunes=$config["iTunes"][$name];
				if ( isset($configTunes["EXECUTE"])==true )
					{
					if (is_array($configTunes["EXECUTE"]) == true) 
						{
						if ($_IPS['VALUE']>0) $configTunes["EXECUTE"]["StartStop"]="Start";
						else $configTunes["EXECUTE"]["StartStop"]="Stop";
						$configCommand=json_encode($configTunes["EXECUTE"]);
						$log_iTunes->LogNachrichten("Config Eintrag für EXECUTE als array vorhanden. Encoded : ".$configCommand.".");
						if (isset($configTunes["EXECUTE"]["Command"])==true) $command = $configTunes["EXECUTE"]["Command"];
						if (isset($configTunes["EXECUTE"]["Parameter"])==true) $playlist = $configTunes["EXECUTE"]["Parameter"];
						}
					else 
						{
						$configCommand=$configTunes["EXECUTE"];
						$playlist="";
						$log_iTunes->LogNachrichten("Config Eintrag für EXECUTE mit Wert ".$configCommand."vorhanden.");
						}
					$Server=getHostAddress();	
					if ($Server=="")
						{
						IPS_ExecuteEX($configCommand, $playlist, false, false, 1);	
						}
					else
						{
						$rpc = new JSONRPC($Server);
						//$rpc->IPS_ExecuteEX($configTunes["EXECUTE"], "", false, false, 1);  Remote Access von IPS_ExecuteEx funktioniert aus Sicherheitsgründen nicht mehr
						$monitorID=getMonitorID($rpc,$configTunes);
						if ($monitorID !== false)
							{
							$monitor=array("VLC" => $configCommand);
							$rpc->IPS_RunScriptEx($monitorID,$monitor);					
							}
						}
					}
				}
			break;
		default:
			break;
		}		
	SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
	}
	
	/***************************************************************************************************/
	
	function getMonitorID($rpc,$configTunes)
		{
		$monitorID=false;
		if ( isset($configTunes["STARTPAGE"])==true )
			{
			if ($configTunes["STARTPAGE"]=="VLC")
					{
					/* In Modul Startpage ist ein Aufruf des VLC Players integriert, bislang nur für Monitor Ein/Aus verwendet */
					$ServerName=$rpc->IPS_GetName(0);
					if ($ServerName !== false)
						{
						//echo "Zugriff auf Server mit Namen ".$ServerName."\n";
						$ProgramID=@$rpc->IPS_GetObjectIDByName ( "Program", 0 );
						if ($ProgramID !== false)
							{
							//echo "ProgramID ist : ".$ProgramID."\n";
							$LibraryID=@$rpc->IPS_GetObjectIDByName ( "IPSLibrary", $ProgramID );
							if ($LibraryID !== false)
								{
								//echo "IPSLibraryID ist : ".$LibraryID."\n";
								$appID=@$rpc->IPS_GetObjectIDByName ( "app", $LibraryID );
								if ($appID !== false)
									{					
									//echo "appID ist : ".$appID."\n";
									$modulesID=@$rpc->IPS_GetObjectIDByName ( "modules", $appID );
									if ($modulesID !== false)
										{					
										//echo "modulesID ist : ".$modulesID."\n";
										$startpageID=@$rpc->IPS_GetObjectIDByName ( "Startpage", $modulesID );
										if ($startpageID !== false)
											{					
											//echo "startpageID ist : ".$startpageID."\n";
											$monitorID=@$rpc->IPS_GetObjectIDByName ( "Monitor_OnOff", $startpageID );
											if ($monitorID !== false)
												{					
												//echo "monitorID ist : ".$monitorID."\n";
												$monitor=array("VLC" => $configTunes["EXECUTE"]);
												//print_r($monitor);												
												}
											}
										}
									}
								}
							}
						}	
					}
				}
 		return($monitorID);
		}
		
		
?>