<?
	/*
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


class RemoteAccess
	{

	public $includefile;
	private $remServer=array();
	private $profilname=array("Temperatur","Humidity","Switch","Button","Contact","Motion");
	private $listofOIDs=array();

	/**
	 * @public
	 *
	 * Initialisierung des RemoteAccess Manager Objektes
	 *
	 */
	public function __construct()
		{
		$this->includefile='<?'."\n";

		/* Beispiel für RemoteAccess_GetConfiguration()
		 *		"BKS-VIS"           	=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06@10.0.1.20:88/api/',
		 *		"LBG-VIS"        		=> 	'http://wolfgangjoebstl@yahoo.com:cloudg06##@hupo35.ddns-instar.de:86/api/',
		 */
		$this->remServer=RemoteAccess_GetConfiguration();
		}
		
	/**
	 * @public
	 *
	 * zum Include File werden die Variablen der Guthabensteuerung hinzugefügt
	 *
	 */
	public function add_Guthabensteuerung()
		{
		$this->includefile.='function GuthabensteuerungList() { return array('."\n";
      $parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');
		echo "\nGuthabensteuerung Data auf :".$parentid."\n";
		$result=IPS_GetChildrenIDs($parentid);
		$count_phone=100;
		$count_var=500;
		foreach ($result as $variableID)
		   {
		   $children=IPS_HasChildren($variableID);
		   echo "  Variable ".IPS_GetName($variableID)."  ".$children;
		   if (IPS_GetObject($variableID)["ObjectType"]==2) // Variable
		      {
				if ($children)
				   {
				   $this->add_variable($variableID,$this->includefile,$count_phone);
				   $volumeID=IPS_GetVariableIDByName(IPS_GetName($variableID)."_Volume",$variableID);
				   $this->add_variable($volumeID,$his->includefile,$count_phone);
				   echo"  VolumeID :".$volumeID;
			      }
			   else
			      {
				   $this->add_variable($variableID,$includefile,$count_var);
					}
				echo "\n";
				}
			else
			   {
			   echo " keine Variable";
			   }
		   }
		$this->includefile.="\n      ".');}'."\n";
		}
		
	/**
	 * @public
	 *
	 * zum Include File werden die Variablen der Stromablesung hinzugefügt
	 *
	 */
	public function add_Amis()
		{
		IPSUtils_Include ('Amis_Configuration.inc.php', 'IPSLibrary::config::modules::Amis');
		$MeterConfig = get_MeterConfiguration();

		$this->includefile.="\n".'function AmisStromverbrauchList() { return array('."\n";
      $amisdataID  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
		echo "\nAmis Stromverbrauch Data auf :".$amisdataID."\n";

		$count_phone=100;
		$count_var=500;
		foreach ($MeterConfig as $meter)
			{
			echo "  Meter :".$meter["NAME"]."\n";
      	$meterdataID = CreateVariableByName($amisdataID, $meter["NAME"], 3);   /* 0 Boolean 1 Integer 2 Float 3 String */
  	      /* ID von Wirkenergie bestimmen */
			if ($meter["TYPE"]=="Amis")
			   {
				$AmisID = CreateVariableByName($meterdataID, "AMIS", 3);
				$zaehlerid = CreateVariableByName($AmisID, "Zaehlervariablen", 3);
				$energieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $zaehlerid );
				$leistungID = IPS_GetObjectIDByName ( 'Wirkleistung' , $zaehlerid );
				$StromL1ID = IPS_GetObjectIDByName ( 'Strom L1' , $zaehlerid );
				$StromL2ID = IPS_GetObjectIDByName ( 'Strom L2' , $zaehlerid );
				$StromL3ID = IPS_GetObjectIDByName ( 'Strom L3' , $zaehlerid );
				$this->add_variablewithname($energieID,$meter["NAME"]."_Wirkenergie",$this->includefile,$count_phone);
				$this->add_variablewithname($leistungID,$meter["NAME"]."_Wirkleistung",$this->includefile,$count_phone);
				$this->add_variablewithname($StromL1ID,$meter["NAME"]."_StromL1",$this->includefile,$count_phone);
				$this->add_variablewithname($StromL2ID,$meter["NAME"]."_StromL2",$this->includefile,$count_phone);
				$this->add_variablewithname($StromL3ID,$meter["NAME"]."_StromL3",$this->includefile,$count_phone);
		   	}
			if ($meter["TYPE"]=="Homematic")
		   	{
				$energieID = IPS_GetObjectIDByName ( 'Wirkenergie' , $meterdataID);
				$leistungID = IPS_GetObjectIDByName ( 'Wirkleistung' , $meterdataID);
				$this->add_variablewithname($energieID,$meter["NAME"]."_Wirkenergie",$this->includefile,$count_phone);
				$this->add_variablewithname($leistungID,$meter["NAME"]."_Wirkleistung",$this->includefile,$count_phone);
		   	}
		   }
		$this->includefile.="\n      ".');}'."\n";
		}

	/**
	 * @public
	 *
	 * zum Include File werden die OIDs der Kategorien der Remote Server hinzugefügt
	 *
	 *   legt function ROID_List() an
	 *
	 * und eventuell auch angelegt
	 *
	 */
	public function add_RemoteServer()
		{
		$this->includefile.="\n".'function ROID_List() { return array('."\n";
		foreach ($this->remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server);
			$this->includefile.='"'.$Name.'" => array('."\n         ".'"Adresse" => "'.$Server.'", ';

			$visrootID=RPC_CreateCategoryByName($rpc, 0,"Visualization");
			$visname=IPS_GetName(0);
			echo "Server : ".$Name."  ".$Server." OID = ".$visrootID." fuer Server ".$visname." \n";
			$this->includefile.="\n         ".'"VisRootID" => "'.$visrootID.'", ';

			$wfID=RPC_CreateCategoryByName($rpc, $visrootID, "WebFront");
			$this->includefile.="\n         ".'"WebFront" => "'.$wfID.'", ';

			$webID=RPC_CreateCategoryByName($rpc, $wfID, "Administrator");
			$this->includefile.="\n         ".'"Administrator" => "'.$webID.'", ';

			$raID=RPC_CreateCategoryByName($rpc, $webID, "RemoteAccess");
			$this->includefile.="\n         ".'"RemoteAccess" => "'.$raID.'", ';

			$servID=RPC_CreateCategoryByName($rpc, $raID,$visname);
			$this->includefile.="\n         ".'"ServerName" => "'.$servID.'", ';

			$this->listofOIDs["Temp"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Temperatur");
			$this->includefile.="\n         ".'"Temperatur" => "'.$this->listofOIDs["Temp"][$Name].'", ';

			$this->listofOIDs["Switch"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Schalter");
			$this->includefile.="\n         ".'"Schalter" => "'.$this->listofOIDs["Switch"][$Name].'", ';

			$this->listofOIDs["Contact"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Kontakte");
			$this->includefile.="\n         ".'"Kontakte" => "'.$this->listofOIDs["Contact"][$Name].'", ';

			$this->listofOIDs["Button"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Taster");
			$this->includefile.="\n         ".'"Taster" => "'.$this->listofOIDs["Button"][$Name].'", ';

			$this->listofOIDs["Motion"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Bewegungsmelder");
			$this->includefile.="\n         ".'"Bewegung" => "'.$this->listofOIDs["Motion"][$Name].'", ';

			$this->listofOIDs["Humidity"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Feuchtigkeit");
			$this->includefile.="\n         ".'"Humidity" => "'.$this->listofOIDs["Humidity"][$Name].'", ';

			$this->listofOIDs["Other"][$Name]=RPC_CreateCategoryByName($rpc, $servID, "Andere");
			$this->includefile.="\n         ".'"Andere" => "'.$this->listofOIDs["Other"][$Name].'", ';

			echo "  Remote VIS-ID                    ".$visrootID,"\n";
			echo "  Remote WebFront-ID               ".$wfID,"\n";
			echo "  Remote Administrator-ID          ".$webID,"\n";
			echo "  RemoteAccess-ID                  ".$raID,"\n";
			echo "  RemoteServer-ID                  ".$servID,"\n";

			$RPCHandlerID = $rpc->IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
			$RPCarchiveHandlerID[$Name] = $RPCHandlerID[0];
			$this->includefile.="\n         ".'"ArchiveHandler" => "'.$RPCarchiveHandlerID[$Name].'", ';
			$this->includefile.="\n             ".'	),'."\n";
			}
		$this->includefile.="      ".');}'."\n";
		}

	/**
	 * @public
	 *
	 * das Include File schreiben
	 *
	 *
	 */
	public function write_includeFile()
		{
		$this->includefile.="\n".'?>';
		$filename=IPS_GetKernelDir().'scripts\IPSLibrary\app\modules\RemoteAccess\EvaluateVariables.inc.php';
		if (!file_put_contents($filename, $this->includefile))
			{
        	throw new Exception('Create File '.$filename.' failed!');
    		}
		}

	/**
	 * @public
	 *
	 * Profile aus den Remote Servern lesen und anlegen
	 *
	 *
	 */
	public function rpc_showProfiles()
		{
		foreach ($this->remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server);
			echo "Server : ".$Name."   \n";

			foreach ($this->profilname as $pname)
			   {
				if ($rpc->IPS_VariableProfileExists($pname) == false)
					{
					echo "  Profil ".$pname." existiert nicht \n";
					}
				else
				   {
					echo "  Profil ".$pname." existiert. \n";
				   }
				}
			}
		}

	/**
	 * @public
	 *
	 * Profile aus den Remote Servern löschen
	 *
	 *
	 */
	public function rpc_deleteProfiles()
		{
		foreach ($this->remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server);
			echo "Server : ".$Name."   \n";

			foreach ($this->profilname as $pname)
			   {
				if ($rpc->IPS_VariableProfileExists($pname) == false)
					{
					echo "  Profil ".$pname." existiert nicht \n";
					}
				else
				   {
					echo "  Profil ".$pname." existiert, wird gelöscht. \n";
					$rpc->IPS_DeleteVariableProfile($pname);
				   }
				}
			}
		}

	/**
	 * @public
	 *
	 * Profile aus den Remote Servern anlegen
	 *
	 *
	 */
	public function rpc_createProfiles()
		{
		foreach ($this->remServer as $Name => $Server)
			{
			$rpc = new JSONRPC($Server);
			echo "Server : ".$Name."   \n";

			foreach ($this->profilname as $pname)
			   {
				if ($rpc->IPS_VariableProfileExists($pname) == false)
					{
					echo "  Profil ".$pname." existiert nicht \n";
					switch ($pname)
					   {
					   case "Temperatur":
					 		$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					  		$rpc->IPS_SetVariableProfileDigits($pname, 2); // PName, Nachkommastellen
					  		$rpc->IPS_SetVariableProfileText($pname,'',' °C');
					  		break;
						case "Humidity";
					 		$rpc->IPS_CreateVariableProfile($pname, 2); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					  		$rpc->IPS_SetVariableProfileDigits($pname, 0); // PName, Nachkommastellen
					  		$rpc->IPS_SetVariableProfileText($pname,'',' %');
					  		break;
						case "Switch";
					 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Aus","",0xff0000);   /*  Rot */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Ein","",0x00ff00);     /* Grün */
					  		break;
						case "Contact";
					 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Zu","",0xffffff);
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Offen","",0xffffff);
					  		break;
						case "Button";
					 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Ja","",0xffffff);
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Nein","",0xffffff);
					  		break;
						case "Motion";
					 		$rpc->IPS_CreateVariableProfile($pname, 0); /* PName, Typ 0 Boolean 1 Integer 2 Float 3 String */
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 0, "Ruhe","",0xffffff);
					 		$rpc->IPS_SetVariableProfileAssociation($pname, 1, "Bewegung","",0xffffff);
					  		break;
					   default:
					      break;
						}
					}
				else
				   {
					echo "  Profil ".$pname." existiert. \n";
				   }
				}
			}
		}
		
	/**
	 * @public
	 *
	 * alle Ergebnisse ausgeben
	 *
	 *
	 */
	public function write_classresult()
		{
		echo "\nOID          ";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($Name,10);
			}
			
		echo "\nTemperature  ";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Temp"][$Name],10);
			}
		echo "\nSwitch       ";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Switch"][$Name],10);
			}
		echo "\nKontakt      ";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Contact"][$Name],10);
			}
		echo "\nTaster      ";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Button"][$Name],10);
			}
		echo "\nBewegung     ";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Motion"][$Name],10);
			}
		echo "\nFeuchtigkeit ";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Humidity"][$Name],10);
			}
		echo "\nAndere       ";
		foreach ($this->remServer as $Name => $Server)
			{
			echo str_pad($this->listofOIDs["Other"][$Name],10);
			}
		echo "\n\n";
		}

	/******************************************************************/

	private function add_variable($variableID,&$includefile,&$count)
		{
		$includefile.='"'.IPS_GetName($variableID).'" => array('."\n         ".'"OID" => '.$variableID.', ';
		$includefile.="\n         ".'"Name" => "'.IPS_GetName($variableID).'", ';
		$variabletyp=IPS_GetVariable($variableID);
		//print_r($variabletyp);
		//echo "Typ:".$variabletyp["VariableValue"]["ValueType"]."\n";
		$includefile.="\n         ".'"Typ" => '.$variabletyp["VariableValue"]["ValueType"].', ';
		$includefile.="\n         ".'"Order" => "'.$count++.'", ';
		$includefile.="\n             ".'	),'."\n";
		}

	/******************************************************************/

	private function add_variablewithname($variableID,$name,&$includefile,&$count)
		{
		$includefile.='"'.$name.'" => array('."\n         ".'"OID" => '.$variableID.', ';
		$includefile.="\n         ".'"Name" => "'.$name.'", ';
		$variabletyp=IPS_GetVariable($variableID);
		//print_r($variabletyp);
		//echo "Typ:".$variabletyp["VariableValue"]["ValueType"]."\n";
		$includefile.="\n         ".'"Typ"      => '.$variabletyp["VariableValue"]["ValueType"].', ';
		$includefile.="\n         ".'"Profile"  => "'.$variabletyp["VariableCustomProfile"].'", ';
		$includefile.="\n         ".'"Order"    => "'.$count++.'", ';
		$includefile.="\n             ".'	),'."\n";
		}



	}  /* Ende class */
	/** @}*/
?>
