<?

	/**@defgroup ipstwilight IPSTwilight
	 * @ingroup modules_weather
	 * @{
	 *
	 * Script zur Ansteuerung der Giessanlage in BKS
	 *
	 *
	 * @file          Gartensteuerung_Installation.ips.php
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.44, 07.08.2014<br/>
	 **/
	 
	//$repository = 'https://10.0.1.6/user/repository/';
	$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
	if (!isset($moduleManager)) {
		IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

		echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
		$moduleManager = new IPSModuleManager('Gartensteuerung',$repository);
	}

	$moduleManager->VersionHandler()->CheckModuleVersion('IPS','2.50');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSModuleManager','2.50.3');
	$moduleManager->VersionHandler()->CheckModuleVersion('IPSLogger','2.50.2');

	echo "\nKernelversion : ".IPS_GetKernelVersion();
	$ergebnis=$moduleManager->VersionHandler()->GetScriptVersion();
	echo "\nIPS Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetModuleState();
	echo " ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('IPSModuleManager');
	echo "\nIPSModulManager Version : ".$ergebnis;
	$ergebnis=$moduleManager->VersionHandler()->GetVersion('Gartensteuerung');
	echo "\nGartensteuerung Version : ".$ergebnis;
	
	IPSUtils_Include ("IPSInstaller.inc.php",                       "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSModuleManagerGUI.inc.php",                "IPSLibrary::app::modules::IPSModuleManagerGUI");
	IPSUtils_Include ("IPSModuleManagerGUI_Constants.inc.php",      "IPSLibrary::app::modules::IPSModuleManagerGUI");

	$RemoteVis_Enabled    = $moduleManager->GetConfigValue('Enabled', 'RemoteVis');

	$WFC10_Enabled        = $moduleManager->GetConfigValue('Enabled', 'WFC10');
	$WFC10_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10');

	$WFC10User_Enabled    = $moduleManager->GetConfigValue('Enabled', 'WFC10User');
	$WFC10User_Path        	 = $moduleManager->GetConfigValue('Path', 'WFC10User');

	$Mobile_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Mobile');
	$Mobile_Path        	 = $moduleManager->GetConfigValue('Path', 'Mobile');
	
	$Retro_Enabled        = $moduleManager->GetConfigValue('Enabled', 'Retro');
	$Retro_Path        	 = $moduleManager->GetConfigValue('Path', 'Retro');

	$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
	$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
	
	/*
	echo "\nRegister \"Allgemeine Definitionen\"";
	$scriptName="Allgemeine Definitionen";
	$file="AllgemeineDefinitionen.inc.php";
	$categoryId=0;
	CreateScript($scriptName, $file, $categoryId);

	echo "\nRegister \"Logging Class\"\n";
	$scriptName="Logging Class";
	$file="_include/Logging.class.php";
	$categoryid  = IPSUtil_ObjectIDByPath('Program');
	CreateCategory('_include', $categoryid, 0);
	$categoryid  = IPSUtil_ObjectIDByPath('Program._include');
	CreateScript($scriptName, $file, $categoryid);
	*/
	
	$categoryId_Gartensteuerung  = CreateCategory('Gartensteuerung', $CategoryIdData, 10);
	$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf-Garten',   $CategoryIdData, 20);
	$scriptIdGartensteuerung   = IPS_GetScriptIDByName('Gartensteuerung', $CategoryIdApp);
	$scriptIdNachrichtenverlauf   = IPS_GetScriptIDByName('Nachrichtenverlauf-Garten', $CategoryIdApp);

   $includefile="<?";
   //$includefile="";
   $includefile.="\n".'function ParamList() {
		return array('."\n";
	$name="GiessAnlage";
	
	$pname="GiessAnlagenProfil";
	if (IPS_VariableProfileExists($pname) == false)
		{
	   //Var-Profil erstellen
		IPS_CreateVariableProfile($pname, 1); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
		IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
	   IPS_SetVariableProfileValues($pname, 0, 2, 1); //PName, Minimal, Maximal, Schrittweite
	   IPS_SetVariableProfileAssociation($pname, 0, "Aus", "", 0x481ef1); //P-Name, Value, Assotiation, Icon, Color=grau
  	   IPS_SetVariableProfileAssociation($pname, 1, "EinmalEin", "", 0xf13c1e); //P-Name, Value, Assotiation, Icon, Color
  	   IPS_SetVariableProfileAssociation($pname, 2, "Auto", "", 0x1ef127); //P-Name, Value, Assotiation, Icon, Color
  	   //IPS_SetVariableProfileAssociation($pname, 3, "Picture", "", 0xf0c000); //P-Name, Value, Assotiation, Icon, Color
	   echo "Profil erstellt;\n";
		}


   // CreateVariable2($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
   $GiessAnlageID 		= CreateVariable3($name, 1, $categoryId_Gartensteuerung, 0, "GiessAnlagenProfil",$scriptIdGartensteuerung,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessCountID			= CreateVariable3("GiessCount",1,$categoryId_Gartensteuerung, 10, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessAnlagePrevID 	= CreateVariable3("GiessAnlagePrev",1,$categoryId_Gartensteuerung, 20, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */
	$GiessTimeID			= CreateVariable3("GiessTime",1,$categoryId_Gartensteuerung,  30, "",null,null,"" ); /* 0 Boolean 1 Integer 2 Float 3 String */

	//function CreateVariable ($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='') {

	$input = CreateVariable3("Nachricht_Garten_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
	$zeile1 = CreateVariable3("Nachricht_Garten_Zeile01",3,$categoryId_Nachrichten, 10, "",null,null,""  );
	$zeile2 = CreateVariable3("Nachricht_Garten_Zeile02",3,$categoryId_Nachrichten, 20, "",null,null,""  );
	$zeile3 = CreateVariable3("Nachricht_Garten_Zeile03",3,$categoryId_Nachrichten, 30, "",null,null,""  );
	$zeile4 = CreateVariable3("Nachricht_Garten_Zeile04",3,$categoryId_Nachrichten, 40, "",null,null,""  );
	$zeile5 = CreateVariable3("Nachricht_Garten_Zeile05",3,$categoryId_Nachrichten, 50, "",null,null,""  );
	$zeile6 = CreateVariable3("Nachricht_Garten_Zeile06",3,$categoryId_Nachrichten, 60, "",null,null,""  );
	$zeile7 = CreateVariable3("Nachricht_Garten_Zeile07",3,$categoryId_Nachrichten, 70, "",null,null,"" );
	$zeile8 = CreateVariable3("Nachricht_Garten_Zeile08",3,$categoryId_Nachrichten, 80, "",null,null,""  );
	$zeile9 = CreateVariable3("Nachricht_Garten_Zeile09",3,$categoryId_Nachrichten, 90, "",null,null,""  );
	$zeile10 = CreateVariable3("Nachricht_Garten_Zeile10",3,$categoryId_Nachrichten, 100, "",null,null,""  );
	$zeile11 = CreateVariable3("Nachricht_Garten_Zeile11",3,$categoryId_Nachrichten, 110, "",null,null,""  );
	$zeile12 = CreateVariable3("Nachricht_Garten_Zeile12",3,$categoryId_Nachrichten, 120, "",null,null,""  );
	$zeile13 = CreateVariable3("Nachricht_Garten_Zeile13",3,$categoryId_Nachrichten, 130, "",null,null,""  );
	$zeile14 = CreateVariable3("Nachricht_Garten_Zeile14",3,$categoryId_Nachrichten, 140, "",null,null,""  );
	$zeile15 = CreateVariable3("Nachricht_Garten_Zeile15",3,$categoryId_Nachrichten, 150, "",null,null,""  );
	$zeile16 = CreateVariable3("Nachricht_Garten_Zeile16",3,$categoryId_Nachrichten, 160, "",null,null,""  );

	$includefile.=');'."\n";
	$includefile.='}'."\n".'?>';
	//echo ".....".$includefile."\n";

	if ($RemoteVis_Enabled==false)
	   { /* keine Remote Visualisierung, daher inc File für andere schreiben */
		$filename=IPS_GetKernelDir()."scripts\IPSLibrary/app/modules/Gartensteuerung/Gartensteuerung.inc.php";
		if (!file_put_contents($filename, $includefile)) {
      	  throw new Exception('Create File '.$filename.' failed!');
    			}
	   echo "\nFilename:".$filename;
		}
		
	// Add Scripts, they have auto install
	IPS_RunScript($scriptIdGartensteuerung);
	IPS_RunScript($scriptIdNachrichtenverlauf);
	
	echo "\nData Kategorie : ".$CategoryIdData;
	echo "\nApp  Kategorie : ".$CategoryIdApp;
	echo "\nScriptID #1    : ".$scriptIdGartensteuerung;
	echo "\nScriptID #2    : ".$scriptIdNachrichtenverlauf;
	echo "\n";

	/*----------------------------------------------------------------------------------------------------------------------------
	 *
	 * WebFront Installation
	 *
	 * ----------------------------------------------------------------------------------------------------------------------------*/
	if ($WFC10_Enabled)
		{
		echo "\nWebportal Administrator installieren in: ".$WFC10_Path." \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10_Path);
		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFront,  10);
		CreateLinkByDestination('GiessCount', $GiessCountID,    $categoryId_WebFront,  20);
		CreateLinkByDestination('GiessAnlagePrev', $GiessAnlagePrevID,    $categoryId_WebFront,  30);
		CreateLinkByDestination('GiessTime', $GiessTimeID,    $categoryId_WebFront,  40);
		CreateLinkByDestination('Nachricht_Zeile01', $zeile1,    $categoryId_WebFront,  110);
		CreateLinkByDestination('Nachricht_Zeile02', $zeile2,    $categoryId_WebFront,  120);
		CreateLinkByDestination('Nachricht_Zeile03', $zeile3,    $categoryId_WebFront,  130);
		CreateLinkByDestination('Nachricht_Zeile04', $zeile4,    $categoryId_WebFront,  140);
		CreateLinkByDestination('Nachricht_Zeile05', $zeile5,    $categoryId_WebFront,  150);
		CreateLinkByDestination('Nachricht_Zeile06', $zeile6,    $categoryId_WebFront,  160);
		CreateLinkByDestination('Nachricht_Zeile07', $zeile7,    $categoryId_WebFront,  170);
		CreateLinkByDestination('Nachricht_Zeile08', $zeile8,    $categoryId_WebFront,  180);
		CreateLinkByDestination('Nachricht_Zeile09', $zeile9,    $categoryId_WebFront,  190);
		CreateLinkByDestination('Nachricht_Zeile10', $zeile10,    $categoryId_WebFront,  200);
		CreateLinkByDestination('Nachricht_Zeile11', $zeile11,    $categoryId_WebFront,  210);
		CreateLinkByDestination('Nachricht_Zeile12', $zeile12,    $categoryId_WebFront,  220);
		CreateLinkByDestination('Nachricht_Zeile13', $zeile13,    $categoryId_WebFront,  230);
		CreateLinkByDestination('Nachricht_Zeile14', $zeile14,    $categoryId_WebFront,  240);
		CreateLinkByDestination('Nachricht_Zeile15', $zeile15,    $categoryId_WebFront,  250);
		CreateLinkByDestination('Nachricht_Zeile16', $zeile16,    $categoryId_WebFront,  260);
		}
		
	if ($WFC10User_Enabled)
		{
		echo "\nWebportal User installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($WFC10User_Path);
		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFront,  10);
		}

	if ($Mobile_Enabled)
		{
		echo "\nWebportal Mobile installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Mobile_Path);
		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFront,  10);
		}

	if ($Retro_Enabled)
		{
		echo "\nWebportal Retro installieren: \n";
		$categoryId_WebFront         = CreateCategoryPath($Retro_Path);
		CreateLinkByDestination('GiessAnlage', $GiessAnlageID,    $categoryId_WebFront,  10);
		}

/************************************************************************************************/

function CreateVariable3($Name, $Type, $ParentId, $Position=0, $Profile="", $Action=null, $ValueDefault='', $Icon='')
	{
	global $includefile;
	$oid=CreateVariable($Name, $Type, $ParentId, $Position, $Profile, $Action, $ValueDefault, $Icon);
	$includefile.='"'.$Name.'" => array("OID"     => \''.$oid.'\','."\n".
					'                       "Name"    => \''.$Name.'\','."\n".
					'                       "Type"    => \''.$Type.'\','."\n".
					'                       "Profile" => \''.$Profile.'\'),'."\n";
	return $oid;
	}

?>