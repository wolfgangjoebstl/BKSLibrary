<?php 
	/**
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */

	/**
	  Guthabensteuerung und GuthabensteuerungReceiver
	  Nachdem php Client Seitig nicht geht, das streaming von Daten und damit die regelmaessige Aktualisiserung 
	  nicht für html Boxen geht probieren wir uns im user Teil des Webfronts. 
	  Das Wichtigste zuerst, die automatische Aktualisiserung geht so auch nicht, aber man kann die html box aktualisieren
	  gedrückte buttons führen zu einem Post zum nächsten php script im User bereich. Hier wird dann etwas abgearbeitet 
	  
	 */

	/** @}*/
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
		<meta http-equiv="Pragma" content="no-cache">
		<meta http-equiv="Expires" content="0">

		<link rel="stylesheet" type="text/css" href="/user/Amis/Amis.css" />

		<script type="text/javascript" src="jquery.min.js"></script>
		<script type="text/javascript" src="Amis.js" ></script>
	</head>


	<?php
		IPSUtils_Include ("Amis_Include.inc.php", "IPSLibrary::app::modules::Amis");

		$htmlID=false;
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
		$installedModules = $moduleManager->GetInstalledModules();
		if (isset($installedModules["Amis"])) {
			$moduleManagerAmis = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
			$CategoryIdDataAmis     = $moduleManagerAmis->GetModuleCategoryID('data');
			$categoryId_SmartMeter        = IPS_GetCategoryIDByName('SmartMeter', $CategoryIdDataAmis);		
			$htmlID = IPS_GetObjectIDByIdent(AMIS_VAR_HTML, $categoryId_SmartMeter);				
		}					
		
		$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung.Webfront');
		//echo "Base ID für die Variablen ist $baseId.";
		$infoID  = IPS_GetObjectIDByIdent(GUTHABEN_VAR_INFO,   $baseId);
		$action  = GetValue(IPS_GetObjectIDByIdent(GUTHABEN_VAR_ACTION, $baseId));
		$module  = GetValue(IPS_GetObjectIDByIdent(GUTHABEN_VAR_MODULE, $baseId));
		$info    = GetValue($infoID);
		$id="unknown";
		
		$amisSM = new AmisSmartMeter();
		$header = $amisSM->writeSmartMeterCsvInfoToHtml("header");
				
		$html="";
		switch($action) {							// dieser Teil ist nicht in Betrieb
			case 'header':
				echo "Header $info";
				break;
			case 'files':
				$config = $amisSM->writeSmartMeterCsvInfoToHtml("Config");					
				$inputDir = $amisSM->writeSmartMeterCsvInfoToHtml("inputDir");
				echo "Files ".$inputDir.GetValue($infoID)."   ".$action."<br>";
				$archiveOps = new archiveOps();                       
				$archiveID = $archiveOps->getArchiveID();   
				$oid = $amisSM->writeSmartMeterCsvInfoToHtml("targetID");
				$archiveOps->addValuesfromCsv($inputDir.GetValue($infoID),$oid,$config,false);
				AC_ReAggregateVariable($archiveID,$oid);      
				$action .= "->done";
				SetValue(IPS_GetObjectIDByIdent(GUTHABEN_VAR_ACTION, $baseId),$action);
				break;
			default:
				//echo "AmisTables.php, content of ".GUTHABEN_VAR_ACTION." : unknown = ".$action;				
				//trigger_error('Unknown Action '.$action);
		}
		if ($htmlID) {
			// $html .= $amisSM->writeSmartMeterDataToHtml();
			// $html .= "<br>";
			$html .= $amisSM->writeSmartMeterCsvInfoToHtml(["Sort"=>$info]);					
			echo $html;	
		}
		else echo "htmlID not set";
		echo "Befehle id=$id action=$action module=$module info=$info";
	?>

	<body>
		<!--
		<button id="niceButton" onClick="trigger_button_id(this.id, 'action', 'Guthabensteuerung', 'info')">Click Here</button>
		<p id="phide">here we go at AmisTables.php, click and hide me</p>
		<div id="guthbnField"><p>Welcher action Wert wurde mit der UrI übergeben ?</p></div>
		<div id="guthbnField-More"><p id="demotext">Überraschung ?</p></div>
		<div id="guthbnFieldAjaxFirst"><p id="demoAjax">Ajax an Erde, drücken Sie hier</p><table><td>Simple</td><td id="ajax_simple_result">Result</td></table></div>
		<div id="amis_send_full_ajax" class="cuw-quick">
			<table>
			<tr><td>Ajax Post an AmisReceiver, drücken Sie hier : </td><td id="ajax_result">Ergebnis von request hier</td></tr>
			<tr><td>Fehlermeldung   : </td><td id="ajax_fail">Ergebnis von Fehlermeldung hier</td></tr>
			<tr><td>Rückmeldung ID  : </td><td id="ajax_id">Ergebnis ID hier - selbe ID wie vom Absender</td></tr>
			</table></div>
		<div id="amis_table_csv" class="cuw-quick">
			<table>
				<thead id="amis_table_csv_head">
				</thead>
				<tbody id="amis_table_csv_body" >
				</tbody>				
			</table></div>	
		<p id="demo">Na ja irgendwas wird schon funktionieren</p>
		<p id="write">Da kann man alles mögliche hinschreiben (hat die id write) : </p>
		<h1>Hier steht das Ergebnis eines Ajax requests : </h1>
		<div id="ajaxResponse"><p>Ergebnis eines Ajax requests</p></div>
		<p id="demoText2">Ergebnis mit ID demotext</p>
		<table><td>Simple Ajax Post an AmisReceiver, drücken Sie hier : </td><td id="ajax_result2">Ergebnis von request hier</td></table>  
																																						Darstellung brauchma auch nicht   -->
	</body>
</html>


