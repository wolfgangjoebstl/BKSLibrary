<?php
header('Content-Type: text/xml; charset=utf-8'); // sorgt für die korrekte XML-Kodierung
header('Cache-Control: must-revalidate, pre-check=0, no-store, no-cache, max-age=0, post-check=0'); // ist mal wieder wichtig wegen IE
// Alert Anzeige zeigt XML nicht an.
// FUNKTIONIERT ! XML

	/**
	 *
	 * Empfangs Script um Requests (JQuery) der HTML Seiten zu bearbeiten.
	 *
	 */

	IPSUtils_Include ("Guthabensteuerung_Include.inc.php", "IPSLibrary::app::modules::Guthabensteuerung");
	

	$module=false;
	$id=false;

if (isset($_POST["id"]))
	{
	$id       = $_POST['id'];
	$module   = "Amis";
	//$action   = $_POST['action'];
	//$module   = $_POST['module'];
	//$info     = $_POST['info'];
	}
elseif (isset($_POST["command"]))
	{
	$module="";			
		//echo "Es wurde POST verwendet";	
		
		// Command auslesen
		$command  = $_POST["command"];
		//getdataips($command);
				
		// Leerzeichen vor und hinter den namen entfernen, sowie alles zu Kleinschreibung ändern
		//$vorname  = trim(strtolower($vorname));
		//$nachname = trim(strtolower($nachname));
		
	}
//GET
elseif (isset($_GET["command"]))
	{
	$module="";			
		//echo "Es wurde GET verwendet";
		// Command auslesen
		
		$command = $_GET["command"];
		//getdataips($command);
		$response = "get erhalten";
		//echo json_encode($response);
	}
//kein GET oder POST
else 
	{
	$module="";			
	//echo "Es wurden keine Daten empfangen";

	}


	$moduleManagerGeneral = new IPSModuleManager('', '', sys_get_temp_dir(), true);
 	$installedModules = $moduleManagerGeneral->GetInstalledModules();
	if ( ($module<>'') && (isset($installedModules[$module])) )
        {
		$moduleInfos = $moduleManagerGeneral->GetModuleInfos($module);			// Allgemeine Implementierung wenn Kombi Modul/Repository nicht bekannt
		$repository  = $moduleInfos['Repository'];
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
		$moduleManager = new IPSModuleManager($module,$repository);     
        }

	if ( ($module=="Amis") && (isset($installedModules["Amis"])) ) {
		$moduleManagerAmis = new IPSModuleManager('Amis',$repository);     
		$CategoryIdDataAmis     = $moduleManagerAmis->GetModuleCategoryID('data');
		$categoryId_SmartMeter        = IPS_GetCategoryIDByName('SmartMeter', $CategoryIdDataAmis);			
		$amisSM = new AmisSmartMeter();
		$json = $amisSM->writeSmartMeterCsvInfoToHtml(["Html"=>"array"]);				// als array zusammenbauen und am Ende json kodieren

if (false)
	{			
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis.Webfront');	
		$infoID  = IPS_GetObjectIDByIdent(GUTHABEN_VAR_INFO,   $baseId);	
		$actionID = IPS_GetObjectIDByIdent(GUTHABEN_VAR_ACTION, $baseId);
		$htmlID = IPS_GetObjectIDByIdent(GUTHABEN_VAR_HTML, $categoryId_SmartMeter);
		$testAlotID = IPS_GetObjectIDByIdent("TestAlot", $categoryId_SmartMeter);
		
		$header = $amisSM->writeSmartMeterCsvInfoToHtml("header");
		if (in_array($action,$header)) { $info=$action; $action="header";  }
		$files  =  $amisSM->writeSmartMeterCsvInfoToHtml("files");
		if (in_array($action,$files)) { $info=$action; $action="files";  }

		
		switch ($action) 
			{

			default:
				//$result=Startpage_SetPage($action, $module, $info);
				//IPSLogger_Inf(__file__, 'GuthabensteuerungReceiver mit Wert '.$action.' und Ergebnis '.$result);			
			}
		echo "was here";
		SetValue($infoID,$info);
		SetValue($actionID,$action);				// genau die Action, da Abfrage im anderen file
		SetValue($htmlID,GetValue($htmlID));		// Aufruf Guthabensteuerung.php zum Update der Webpage

		SetValue($testAlotID,GetValue($testAlotID));		// Aufruf Guthabensteuerung.php zum Update der Webpage
		//StartpageOverview_Refresh();
		//IPS_Runscript(
		}
	}

	switch($id)
		{
		case "amis_send_full_ajax":
			$response = "get erhalten, Parameter $id for Module $module erkannt : ";		
			break;
		default:
			$response = "get erhalten, keine Parameter erkannt : ";
			break;
		}
	$result=array();
	$result["id"]=$id;
	if (isset($_GET)) $response .= json_encode($_GET);
	if (isset($_POST)) $response .= json_encode($_POST);
	$result["response"]=$json;
	//$result["response"]=["eins","zwei","drei"];
	//$result["response"]["new"]="neuerWert";
	//$result["response"]=$response;
	//$result=$response;
	//echo json_encode("Wert:".json_encode($result));			// Scalar mit encoded Object, wird gedruckt
	echo json_encode($result);									// Objekt, wird nur als object Object angedruckt



function sendstatusresponse($command, $status)
	{
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		echo "<statuslist>\n";
		echo "<status>\n";
		echo "<command>".$command."</command>\n";
		echo "<neostatus>".$status."</neostatus>\n";
		echo "</status>\n";
		echo "</statuslist>\n";
	}


	/** @}*/
?>