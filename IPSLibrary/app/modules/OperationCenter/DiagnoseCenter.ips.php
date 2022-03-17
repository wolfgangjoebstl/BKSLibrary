<?

/***********************************************************************
 *
 * DiagnoseCenter
 *
 * Allerlei betriebliche Abfragen und Wartungsmassnahmen
 *
 * derzeit nur evaluate traceRoute einmal am Tag bis zu Google verfolgen.
 *
 ***********************************************************/

//Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

/******************************************************

				INIT

*************************************************************/

    // max. Scriptlaufzeit definieren
    $dosOps = new dosOps();
    $dosOps->setMaxScriptTime(900);                              // kein Abbruch vor dieser Zeit, nicht für linux basierte Systeme
    $startexec=microtime(true);

$dir655=false;

$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager))
	{
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('OperationCenter',$repository);
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
$scriptId  = IPS_GetObjectIDByIdent('OperationCenter', IPSUtil_ObjectIDByPath('Program.IPSLibrary.app.modules.OperationCenter'));
echo "Category App ID:".$CategoryIdApp."\n";
echo "Category Script ID:".$scriptId."\n\n";

$scriptIdDiagnoseCenter   = IPS_GetScriptIDByName('DiagnoseCenter', $CategoryIdApp);

echo "Folgende Module werden von OperationCenter bearbeitet:\n";
if (isset ($installedModules["IPSLight"])) { 			echo "  Modul IPSLight ist installiert.\n"; } else { echo "Modul IPSLight ist NICHT installiert.\n"; }
if (isset ($installedModules["IPSPowerControl"])) { 	echo "  Modul IPSPowerControl ist installiert.\n"; } else { echo "Modul IPSPowerControl ist NICHT installiert.\n"; }
if (isset ($installedModules["IPSCam"])) { 				echo "  Modul IPSCam ist installiert.\n"; } else { echo "Modul IPSCam ist NICHT installiert.\n"; }
if (isset ($installedModules["RemoteAccess"])) { 		echo "  Modul RemoteAccess ist installiert.\n"; } else { echo "Modul RemoteAccess ist NICHT installiert.\n"; }
echo "\n";

/* PC Daten wie zB Trace regelmaessig auslesen */

$tim1ID = @IPS_GetEventIDByName("DiagnoseAufruftimer", $_IPS['SELF']);
if ($tim1ID==false)
	{
	$tim1ID = IPS_CreateEvent(1);
	IPS_SetParent($tim1ID, $_IPS['SELF']);
	IPS_SetName($tim1ID, "DiagnoseAufruftimer");
	IPS_SetEventCyclic($tim1ID,0,0,0,0,0,0);
	IPS_SetEventCyclicTimeFrom($tim1ID,1,40,0);  /* immer um 1:40 */
	}
IPS_SetEventActive($tim1ID,true);

/*********************************************************************************************/



/*********************************************************************************************/


if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="Execute")
	{
	echo "\nVon der Konsole aus gestartet.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

	/********************************************************
   	Über eigene Ip Adresse auf Gateway Adresse schliessen
	**********************************************************/

	echo "\nGateway Adresse und Trace ermitteln.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";

	/* vorerst lassen wir es haendisch, spaeter kann man es auch aus ipconfig ableiten
		Gateway kann mit tracert 8.8.8.8 rausgefunden werden, die ersten zeilen sind die bekannten Gateways

	*/
	
	evaluate_trace($CategoryIdData);

	echo "\nEnde Execute.      Aktuell vergangene Zeit : ".(microtime(true)-$startexec)." Sekunden\n";
	
	} /* ende Execute */

	
/*********************************************************************************************/


if ($_IPS['SENDER']=="Variable")
	{

	}

/*********************************************************************************************/


if ($_IPS['SENDER']=="TimerEvent")
	{
	switch ($_IPS['EVENT'])
	   {
	   case $tim1ID:        /* einmal am Tag */
  			IPSLogger_Dbg(__file__, "TimerEvent from ".$_IPS['EVENT']." Evaluate Trace Route");
		   evaluate_trace($CategoryIdData);
	      break;
		default:
		   break;
		}
	}

/*********************************************************************************************/
/*********************************************************************************************/
/*********************************************************************************************/
/*********************************************************************************************/


function evaluate_trace($CategoryIdData)
	{
	$categoryId_Route    = CreateCategory('TraceRouteVerlauf',   $CategoryIdData, 20);
	for ($i=1; $i<=20;$i++)
		{
		$input = CreateVariable("RoutePoint".$i,3,$categoryId_Route, $i*5, "",null,null,""  );  /* Name Type ParentID Position */
		SetValue($input,"");
		}
	$EvalTimeID = CreateVariableByName($CategoryIdData, "EvalTime", 1);
	IPS_SetVariableCustomProfile($EvalTimeID,"~UnixTimestamp");
	SetValue($EvalTimeID,time());

	$catch="";
	exec('tracert 8.8.8.8',$catch);   /* ohne all ist es eigentlich ausreichend Information, doppelte Eintraege werden vermieden */

	$googleroute=array();

	foreach($catch as $line)
		{
		$trace=(integer)substr($line,0,3);
		if ($trace>0)
			{
			/* Eine Zeile mit einer Zahl in den ersten drei Buchstaben */
			/* entweder gibt es hier eine IP Adresse oder auch einen Namen und eine IP Adresse in eckicgen Klammern */
			if (strpos($line,'[')==false)
				{
				/* kein Domainname */
				$result = extractIPaddress(substr($line,32));
				//echo $trace."***".$result."***".$line."\n";
      	   		$googleroute[$trace]["IP"]=$result;
      	   		}
      		else
      	   		{
				$result = extractIPaddress(substr($line,strpos($line,'[')));
      	   		$googleroute[$trace]["IP"]=$result;
      	   		$domain=substr($line,32,strpos($line,'[')-32);
      	   		$googleroute[$trace]["NAME"]=$domain;
      	   		}
			}  /* ende trace */
	  	}
		//print_r($googleroute);

		/*
		if ($ipall == "") {$ipall="unknown";}

		echo "\n";
		echo "Hostname ist          : ".$hostname."\n";
		echo "Eigene IP Adresse ist : ".$ipall."\n";
		echo "\n";

		foreach ($ipports as $ip => $data)
			{
			//echo "IP Adresse ".$ip." und im Longformat : ".ip2long($ip)."\n";
			printf("Port %s hat IP Adresse %s und Gateway %s Ip Adresse im Longformat : %u\n", $data["Name"],$ip,$data["Gateway"],ip2long($ip));
			}
		*/
		$i=1;
		$categoryId_Route    = CreateCategory('TraceRouteVerlauf',   $CategoryIdData, 20);
		foreach ($googleroute as $trace=>$ip)
			{
			$traceID=CreateVariableByName($categoryId_Route,"RoutePoint".$i,3);
			$i++;
			if (isset($ip["NAME"]))
		   		{
				echo "Station : ".$trace." mit ".$ip["IP"]." und ".$ip["NAME"]."\n";
				SetValue($traceID,$ip["IP"]." und ".$ip["NAME"]);
				}
			else
				{
				if ($ip["IP"] != "unknown")
			   		{
					$url="http://iplocationtools.com/".$ip["IP"].".html";
					$result=get_data($url);
					$result=substr($result,strpos($result,$ip["IP"]),180);
					$result=substr($result,0,strpos($result,"The area code"));
					echo "Station : ".$trace." mit ".$ip["IP"]." und ".$result."\n";
					SetValue($traceID,$ip["IP"]." und ".$result);
			    	}
				else
			   		{
					$unknown="Unknown";
					echo "  Counter ".$i."  ".$traceID."\n";
				   	SetValue($traceID,$unknown);
			   		}   
				}
			}

		return($googleroute);
	}


?>