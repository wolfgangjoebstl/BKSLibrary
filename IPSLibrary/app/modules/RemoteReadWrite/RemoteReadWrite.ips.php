<?

 //Fügen Sie hier Ihren Skriptquellcode ein

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");
//include(IPS_GetKernelDir()."scripts\_include\Logging.class.php");
IPSUtils_Include ("EvaluateHardware.inc.php","IPSLibrary::app::modules::RemoteReadWrite");
IPSUtils_Include ("RemoteReadWrite_Configuration.inc.php","IPSLibrary::config::modules::RemoteReadWrite");

/******************************************************

				INIT

*************************************************************/

//$repository = 'https://10.0.1.6/user/repository/';
$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
if (!isset($moduleManager)) {
	IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

	echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
	$moduleManager = new IPSModuleManager('RemoteReadWrite',$repository);
}
$gartensteuerung=false;
$installedModules = $moduleManager->GetInstalledModules();
$inst_modules="\nInstallierte Module:\n";
foreach ($installedModules as $name=>$modules)
	{
	$inst_modules.=str_pad($name,20)." ".$modules."\n";
	if ($name=="Gartensteuerung") { $gartensteuerung=true; }
	}
echo $inst_modules."\n\n";

$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.RemoteReadWrite');
echo "BaseID :".$baseId."\n\n";

/* Typ 0 Boolean 1 Integer 2 Float 3 String */
$StatusID = CreateVariableByName($baseId, "StatusReadWrite-BKS", 0);
$letzterWertID = CreateVariableByName($baseId, "LetzterWert-BKS", 3);

/*
$Homematic = HomematicList();
$FHT = FHTList();
//print_r($Homematic);

foreach ($Homematic as $Key)
	{
	if (isset($Key["COID"]["TEMPERATURE"])==true)
	   {
      $oid=(integer)$Key["COID"]["TEMPERATURE"]["OID"];
		//print_r($Key["COID"]["TEMPERATURE"]);
		echo $Key["COID"]["TEMPERATURE"]["OID"]." ";
		echo date("d.m h:i",IPS_GetVariable($oid)["VariableChanged"])." ";
		echo $Key["Name"].".".$Key["COID"]["TEMPERATURE"]["Name"]." = ".GetValueFormatted($oid)."\n";

		}
	}

foreach ($FHT as $Key)
	{
	if (isset($Key["COID"]["TemeratureVar"])==true)
	   {
      $oid=(integer)$Key["COID"]["TemeratureVar"]["OID"];
		//print_r($Key["COID"]["TEMPERATURE"]);
		echo $Key["COID"]["TemeratureVar"]["OID"]." ";
		echo date("d.m h:i",IPS_GetVariable($oid)["VariableChanged"])." ";
		echo $Key["Name"].".".$Key["COID"]["TemeratureVar"]["Name"]." = ".GetValueFormatted($oid)."\n";

		}
	}
*/

/*****************************************************************************************************************************************************

Alle mit Evaluate Hardware gefundenen Module mit Status ausgeben.


Registriert keine Events. Funktioniert derzeit nur mit Remote Access ......

RemoteAccess für Motion


**********************************************************************************************************************************************************/


	$Homematic = HomematicList();
	$FHT = FHTList();
	$FS20= FS20List();

	echo "\nHomematic und FS20 Temperaturwerte:\n";
	echo ReadTemperaturWerte()."\n\n";
	
	/******************************************************************************************************************************************/
		
	echo "\nHomematic Feuchtigkeitswerte:\n";
	foreach ($Homematic as $Key)
		{
		/* alle Feuchtigkeitswerte ausgeben */
		if (isset($Key["COID"]["HUMIDITY"])==true)
	   	{
	      $oid=(integer)$Key["COID"]["HUMIDITY"]["OID"];
			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}

	echo "\n";

	echo "\nFS20 Statuswerte:\n";
	foreach ($FS20 as $Key)
		{
		/* alle Statuswerte ausgeben */
		if (isset($Key["COID"]["StatusVariable"])==true)
		   {
      	$oid=(integer)$Key["COID"]["StatusVariable"]["OID"];
			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}
	echo "\nHomematic Statuswerte:\n";
	foreach ($Homematic as $Key)
		{
		/* alle Statuswerte ausgeben */
		if (isset($Key["COID"]["STATE"])==true)
	   	{
	      $oid=(integer)$Key["COID"]["STATE"]["OID"];
			echo str_pad($Key["Name"],30)." = ".GetValueFormatted($oid)."   (".date("d.m H:i",IPS_GetVariable($oid)["VariableChanged"]).")\n";
			}
		}
		
	/* Typ 0 Boolean 1 Integer 2 Float 3 String */
	$InnnenTempID = CreateVariableByName($baseId, "Innentemperatur-BKS", 3);
	$AussenTempID = CreateVariableByName($baseId, "Aussentemperatur-BKS", 3);
	$KellerMinTempID = CreateVariableByName($baseId, "KellerMintemperatur-BKS", 3);
	$HeizleistungID = CreateVariableByName($baseId, "Heizleistung-BKS", 3);


if ((IPS_GetName(0)=="LBG70") or (IPS_GetName(0)=="BKS01"))
	{
	}
else
	{
	//echo "Connect to http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/";
	$rpc = new JSONRPC("http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.6:82/api/");
	
	if ($gartensteuerung==true)
		{
		IPSUtils_Include ("Gartensteuerung.inc.php","IPSLibrary::app::modules::Gartensteuerung");
		$ParamList = ParamList();
		//print_r($ParamList);

		$ReadWriteList=array();
		SetValueBoolean($StatusID,true);

		foreach ($ParamList as $Key)
			{
			//print_r($Key);

			$typ=(integer)$Key["Type"];
			$oid=(integer)$Key["OID"];
			if ($Key["Profile"]=="")
		   	{ /* keine Formattierung */
		   	$vid = CreateVariableByName($baseId, $Key["Name"], $typ);
			   }
			else
	   		{
			   $vid = CreateVariableByName($baseId, $Key["Name"], 3);
			   }
			$ReadWriteList[$Key["Name"]]=array("OID" => $Key["OID"],
												"Name" => $Key["Name"],
												"Profile" => $Key["Profile"],
												"Type" => $Key["Type"],
												"LOID" => $vid);

			echo "Variabe ausgelesen : ".$oid." : ".$Key["Name"]." Typ : ".$Key["Type"]." Profile : ".$Key["Profile"]." und gespeichert auf : ".$vid."\n";
			if ($Key["Profile"]=="")
				{
				$ergebnis=$rpc->GetValue($oid);
				}
			else
				{
				$ergebnis=$rpc->GetValueFormatted($oid);
				}
			if ($ergebnis)
				{
				SetValue($vid,$ergebnis);
				SetValue($letzterWertID,date("d.m.y H:i:s").":".$Key["Name"]);
				}
			else
		   	{
				SetValueBoolean($StatusID,false);
				}
			}

		print_r($ReadWriteList);
		}

	$ergebnis=$rpc->GetValueFormatted(56688);
	if ($ergebnis)
		{
		SetValueBoolean($StatusID,true);
		SetValue($InnnenTempID,$ergebnis);
		SetValue($letzterWertID,date("d.m.y H:i:s").": Innentemperatur");
		}
	else
		{
		SetValueBoolean($StatusID,false);
		}

	$ergebnis=$rpc->GetValueFormatted(21416);
	if ($ergebnis)
		{
		SetValueBoolean($StatusID,true);
		SetValue($AussenTempID,$ergebnis);
		SetValue($letzterWertID,date("d.m.y H:i:s").": Aussentemperatur");
		}
	else
		{
		SetValueBoolean($StatusID,false);
		}

	$ergebnis=$rpc->GetValueFormatted(52129);
	if ($ergebnis)
		{
		SetValueBoolean($StatusID,true);
		SetValue($KellerMinTempID,$ergebnis);
		SetValue($letzterWertID,date("d.m.y H:i:s").": Keller Minimum Temperatur (gestern)");
		}
	else
		{
		SetValueBoolean($StatusID,false);
		}

	$ergebnis=$rpc->GetValueFormatted(34354);
	if ($ergebnis)
		{
		SetValueBoolean($StatusID,true);
		SetValue($HeizleistungID,$ergebnis);
		SetValue($letzterWertID,date("d.m.y H:i:s").": Heizleistung");
		}
	else
		{
		SetValueBoolean($StatusID,false);
		}
	}



?>
