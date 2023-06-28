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

		<link rel="stylesheet" type="text/css" href="/user/Guthabensteuerung/Guthabensteuerung.css" />

		<script type="text/javascript" src="jquery.min.js"></script>

		<script type="text/javascript" charset="ISO-8859-1" >
			function trigger_button(action, module, info) {
				var id         = $(this).attr("id");
				var WFC10Path             = $("#WFC10Path").val();
				
				document.getElementById('demo').innerHTML = Date()+" action="+action+" module="+module+" id="+id+" Path="+WFC10Path;
				$.ajax({type: "POST",
						url: "/user/Guthabensteuerung/GuthabensteuerungReceiver.php",
						data: "id="+id+"&action="+action+"&module="+module+"&info="+info});
			}

			function trigger_button2(action, module, info) {
				var id                    = $(this).attr("id");
				var WFC10Enabled          = $("#WFC10Enabled").is(':checked');
				var WFC10TabPaneExclusive = $("#WFC10TabPaneExclusive").is(':checked');
				var WFC10Path             = $("#WFC10Path").val();
				var WFC10ID               = $("#WFC10ID").val();
				var WFC10TabPaneParent    = $("#WFC10TabPaneParent").val();
				var WFC10TabPaneItem      = $("#WFC10TabPaneItem").val();
				var WFC10TabPaneIcon      = $("#WFC10TabPaneIcon").val();
				var WFC10TabPaneName      = $("#WFC10TabPaneName").val();
				var WFC10TabPaneOrder     = $("#WFC10TabPaneOrder").val();
				var WFC10TabItem          = $("#WFC10TabItem").val();
				var WFC10TabIcon          = $("#WFC10TabIcon").val();
				var WFC10TabName          = $("#WFC10TabName").val();
				var WFC10TabOrder         = $("#WFC10TabOrder").val();
	
				var MobileEnabled         = $("#MobileEnabled").is(':checked');
				var MobilePath            = $("#MobilePath").val();
				var MobilePathIcon        = $("#MobilePathIcon").val();
				var MobilePathOrder       = $("#MobilePathOrder").val();
				var MobileName            = $("#MobileName").val();
				var MobileIcon            = $("#MobileIcon").val();
				var MobileOrder           = $("#MobileOrder").val();

				$.ajax({type: "POST",
						url: "/user/Guthabensteuerung/GuthabensteuerungReceiver.php",
						contentType:"application/x-www-form-urlencoded; charset=ISO-8859-1",
						data: "id="+encodeURIComponent(id)
						       +"&action="+encodeURIComponent(action)
						       +"&module="+encodeURIComponent(module)
						       +"&info="+encodeURIComponent(info)+
						       +"&WFC10Enabled="+encodeURIComponent(WFC10Enabled)
						       +"&WFC10TabPaneExclusive="+encodeURIComponent(WFC10TabPaneExclusive)
						       +"&WFC10Path="+encodeURIComponent(WFC10Path)
						       +"&WFC10ID="+encodeURIComponent(WFC10ID)
						       +"&WFC10TabPaneParent="+encodeURIComponent(WFC10TabPaneParent)
						       +"&WFC10TabPaneItem="+encodeURIComponent(WFC10TabPaneItem)
						       +"&WFC10TabPaneIcon="+encodeURIComponent(WFC10TabPaneIcon)
						       +"&WFC10TabPaneName="+encodeURIComponent(WFC10TabPaneName)
						       +"&WFC10TabPaneOrder="+encodeURIComponent(WFC10TabPaneOrder)
						       +"&WFC10TabItem="+encodeURIComponent(WFC10TabItem)
						       +"&WFC10TabIcon="+encodeURIComponent(WFC10TabIcon)
						       +"&WFC10TabName="+encodeURIComponent(WFC10TabName)
						       +"&WFC10TabOrder="+encodeURIComponent(WFC10TabOrder)
						       +"&MobileEnabled="+encodeURIComponent(MobileEnabled)
						       +"&MobilePath="+encodeURIComponent(MobilePath)
						       +"&MobilePathIcon="+encodeURIComponent(MobilePathIcon)
						       +"&MobilePathOrder="+encodeURIComponent(MobilePathOrder)
						       +"&MobileName="+encodeURIComponent(MobileName)
						       +"&MobileIcon="+encodeURIComponent(MobileIcon)
						       +"&MobileOrder="+encodeURIComponent(MobileOrder)
						});
						
			}


		</script>

	</head>
	<body>
		<?php
			IPSUtils_Include ("Guthabensteuerung_Include.inc.php", "IPSLibrary::app::modules::Guthabensteuerung");

			$htmlID=false;
			$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
			$moduleManager = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
			$installedModules = $moduleManager->GetInstalledModules();
			if (isset($installedModules["Amis"])) {
				$moduleManagerAmis = new IPSModuleManager('Amis',$repository);     /*   <--- change here */
				$CategoryIdDataAmis     = $moduleManagerAmis->GetModuleCategoryID('data');
				$categoryId_SmartMeter        = IPS_GetCategoryIDByName('SmartMeter', $CategoryIdDataAmis);		
				$htmlID = IPS_GetObjectIDByIdent(GUTHABEN_VAR_HTML, $categoryId_SmartMeter);				
			}					
			
			$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung.Webfront');
			//echo "Base ID für dei Variablen ist $baseId.";
			$infoID  = IPS_GetObjectIDByIdent(GUTHABEN_VAR_INFO,   $baseId);
			$action  = GetValue(IPS_GetObjectIDByIdent(GUTHABEN_VAR_ACTION, $baseId));
			$module  = GetValue(IPS_GetObjectIDByIdent(GUTHABEN_VAR_MODULE, $baseId));
			$info    = GetValue($infoID);

			$amisSM = new AmisSmartMeter();
			$header = $amisSM->writeSmartMeterCsvInfoToHtml("header");
			
			$selenium = new SeleniumHandler();
			
			
			$html="";
			switch($action) {
				case 'header':
				    echo "Header $info";
					break;
				case 'files':
					$guthabenHandler = new GuthabenHandler();
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
					echo "unknown";
					//trigger_error('Unknown Action '.$action);
			}
			if ($htmlID) {
				// $html .= $amisSM->writeSmartMeterDataToHtml();
				// $html .= "<br>";
				$html .= $amisSM->writeSmartMeterCsvInfoToHtml(["Sort"=>$info]);					
				echo $html;	
			}
			else echo "htmlID not set";
			echo "Befehle $action $module $info";
		?>
		<p id="demo"></p>
	</body>
</html>


