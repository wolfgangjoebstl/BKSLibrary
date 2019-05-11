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
	
	
	 */

	/** @}*/
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
		<meta http-equiv="Pragma" content="no-cache">
		<meta http-equiv="Expires" content="0">

		<link rel="stylesheet" type="text/css" href="/user/Startpage/StartpageTopology.css" />'

		<script type="text/javascript" src="jquery.min.js"></script>

		<script type="text/javascript" charset="ISO-8859-1" >
			function trigger_button(action, module, info) {
				var id         = $(this).attr("id");

				$.ajax({type: "POST",
						url: "/user/Startpage/StartpageTopology_Receiver.php",
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
						url: "/user/Startpage/StartpageTopology_Receiver.php",
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
	<body >
		<a href="#" onClick=trigger_button('View1','','')>View1</a> |
		<a href="#" onClick=trigger_button('View2','','')>View2</a> |
		<a href="#" onClick=trigger_button('View3','','')>View3</a> |
		<a href="#" onClick=trigger_button('View4','','')>View4</a> |
		<a href="#" onClick=trigger_button('View5','','')>View5</a>
		<?php
			IPSUtils_Include ("Startpage_Include.inc.php", "IPSLibrary::app::modules::Startpage");

			$baseId  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Startpage');
			$action  = GetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_ACTION, $baseId));
			$module  = GetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_MODULE, $baseId));
			$info    = GetValue(IPS_GetObjectIDByIdent(STARTPAGE_VAR_INFO,   $baseId));

		?>
		<BR>
		<BR>
		<?php
			switch($action) {
				case 'View1':
					echo 'Hallo, hier ist View1 mit Temperaturwerten -check<br> ';
					$Werte=IPS_GetChildrenIDs(22334);
					foreach ($Werte as $Wert) echo "   ".$Wert."  ".IPS_GetName($Wert)."   ".GetValue($Wert)."<br>";
					SetValue(35191,"View1 gedrueckt");
					break;
				case 'View2':
					echo "Hallo, hier ist View2 mit Bewegunghswerten <br>";
					$Werte=IPS_GetChildrenIDs(22823);
					foreach ($Werte as $Wert) echo "   ".$Wert."  ".IPS_GetName($Wert)."   ".(GetValue($Wert)?"Ein":"Aus")."<br>";	
					SetValue(35191,"View2 gedrueckt");
					break;
				case 'View3':
					echo "Hallo, hier ist View3 mit den installierten Modulen <br>";					
					IPSUtils_Include ("IPSModuleManager.class.php", "IPSLibrary::install::IPSModuleManager");
					$moduleManager = new IPSModuleManager();
					$modules         = $moduleManager->GetInstalledModules();
					foreach ($modules as $name => $module) echo "   ".$name."  ".$module."<br>";					
					break;
				case 'View4':
					echo "Hallo, hier ist View4 mit der aktuellen Uhrzeit:<br>";
					echo 'Es ist heute '.date("D.m.Y H:i:s").' Refresh ?';  					
					break;
				case 'View5':
					echo "Hallo, hier ist View5";				
					break;
				default:
					trigger_error('Unknown Action '.$action);
			}
		?>

	</body>
</html>


