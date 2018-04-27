<?

/*
 * SNMP Objekte auslesen
 * benötigt Exe File mit CLI oder das IPS SNMP Modul
 * abhängig von Verfügbarkeit wird entweder die eine oder andere Methode aufgerufen
 * Beim IPS Modul gehen mehr Funktionalitäten, wie zB Tabellen auslesen oder Walk
 *
 * getSNMPType funktioniert nur mit Commandline
 *
 */

class SNMP_OperationCenter
{
    //Objekteigenschaften
    protected $host;                   //SNMP Serveradresse
    protected $community;              //SNMP Community
    protected $binary;                 //Dateipfad zur ssnmpq.exe
    public $debug = false;             //Bei true werden Debuginformationen ausgegeben
    protected $snmpobj=array();    		//array registrierter snmp objekte welche beim server abgefragt werden
    private $lastwalk=array();         //hier Ergebnis vom letzten Walk Befehl hineinschreiben als array
    private $lastwalk_csv="";          //hier Ergebnis vom letzten Walk Befehl hineinschreiben als csv

    private $lastTable=array();         //hier Ergebnis vom letzten ifTable Befehl hineinschreiben als array

    private $CategoryIdData;
	 private $SNMPmodul=false;				// true wenn ein SNMP Modul installiert ist
	 private $SNMPinstanz=false;				// Instanz des passenden SNMP Moduls

    //IPS Datentypen
    const tBOOL        = 0;
    const tINT        = 1;
    const tFLOAT    = 2;
    const tSTRING    = 3;
    const tLONG    = 4;          /* auf mehrere Integer Werte aufteilen, wenn notwendig , also wenn kein 64 Bit System */

	/*
	 *  Konstruktor
	 */

	public function __construct($CategoryIdData, $host, $community, $binary, $debug)
		{
		$this->host         = $host;
		$this->community     = $community;
		$this->binary         = $binary;
		$this->debug         = $debug;
		$this->CategoryIdData = $CategoryIdData;
		
		// prüfe ob ein SNMP Modul mit der geünschten IP Adresse installiert ist, damit können Abfragen schneller erledigt werden
		$this->SNMPinstanz=$this->findSNMPModul("Babenschneider Symcon Modules","IPSSNMP",$host);
		echo "SNMP Modul verwenden. Instanz ID : ".$this->SNMPinstanz."   (".IPS_GetName($this->SNMPinstanz).")\n";
		if ($this->SNMPinstanz !== false) $this->SNMPmodul=true;
			
		//Prüfe ob Variablenprofile existieren und erstelle diese wenn nötig
		$this->createVariableProfile("SNMP_CapacityMB", self::tFLOAT, "", " MB");
		$this->createVariableProfile("SNMP_CapacityGB", self::tFLOAT, "", " GB");
		$this->createVariableProfile("SNMP_CapacityTB", self::tFLOAT, "", " TB");
		$this->createVariableProfile("SNMP_Temperature", self::tINT, "", " °C");
		$this->createVariableProfile("SNMP_FanSpeed", self::tINT, "", " RPM");
		if(!IPS_VariableProfileExists("SNMP_SmartStatus"))
			{
			$this->createVariableProfile("SNMP_SmartStatus", self::tBOOL, "", "");
			IPS_SetVariableProfileAssociation("SNMP_SmartStatus", 1, "Gut", "", 0x00FF04);
			IPS_SetVariableProfileAssociation("SNMP_SmartStatus", 0, "Defekt", "", 0xFF0000);
			}
			
		$this->collumns = array(
			"1" => "ifIndex",
			"2" => "ifDescr",
			"3" => "ifType",
			"4" => "ifMTU",
			"5" => "ifSpeed",
			"6" => "ifPhysAddress",
			"7" => "ifAdminStatus",
			"8" => "ifOperStatus",
			"9" => "ifLastChange",
			"10" => "ifnOctets",
			"11" => "ifnUcastPkts",
			"12" => "ifnNUcastPkts",
			"13" => "ifnDiscards",
			"14" => "ifnErrors",
			"15" => "ifnUnknownProtos",
			"16" => "ifOutOctests",
			"17" => "ifOutUcastPkts",
			"18" => "ifOutNUcastPkts",
			"19" => "ifOutDiscards",
			"20" => "ifOutErrors",
			"21" => "ifOutQLen",
			"22" => "ifSpecific",
					);
			
		}

	/*
	 *  Variablenprofil erstellen wenn zB Variablen neu angelegt werden
	 */

    private function createVariableProfile($name, $type, $pre, $suff){
        if(!IPS_VariableProfileExists($name)){
            if($this->debug) echo "INFO - VariablenProfil $name existiert nicht und wird angelegt";
            IPS_CreateVariableProfile($name, $type);
            IPS_SetVariableProfileText($name, $pre, $suff);
        }
    }

	/*
	 *  Variable erstellen wenn zB Variablen neu angelegt werden
	 *  dafür den Variablentyp ermitteln
	 */

    public function registerSNMPObj($oid, $desc, $convertType = "none")
	 	{
		$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

      //prüfe auf doppelte Einträge beim Registrieren neuer SNMP Objekte
      foreach($this->snmpobj as $obj)
			{
         if($desc==$obj->desc)
				{
            if($this->debug) echo "ERROR - registerSNMPObj: Variable description '$desc' already exists, it must be unique!";
            exit;
            }
         if($oid==$obj->OID)
				{
            if($this->debug) echo "ERROR - registerSNMPObj: Variable OID '$oid' already exists, it must be unique!";
            exit;
            }
        }

		//prüfe ob IPS Variablen für SNMP Objekt existiert (Variablenname entspricht description)
      $parentID = $this->CategoryIdData;
      $ips_var = @IPS_GetVariableIDByName($desc, $parentID);
      if($ips_var == false)
			{
         if($this->debug) 							echo "Variable '$desc' not found - create IPSVariable\n";
         if($convertType == "none") 			$type = $this->getSNMPType($oid);
         if($convertType == "CapacityMB" || $convertType == "CapacityGB" || $convertType == "CapacityTB")     $type = self::tFLOAT;
         if($convertType == "Temperature")	$type = self::tINT;
         if($convertType == "FanSpeed")      $type = self::tINT;
         if($convertType == "SmartStatus")   $type = self::tBOOL;
         if($convertType == "Counter32")     $type = self::tLONG;
         if($this->debug) 							echo "Type of OID '$oid' is $type \n";

			if ($type==self::tLONG)
				   {
			      $convertType = "Counter32";  /* automatisch zuweisen */
	            $ips_var = IPS_CreateVariable(1);  			/* Variable Typ Integer anlegen */
	            IPS_SetName($ips_var, $desc);
  		         IPS_SetParent($ips_var, $parentID);
  		         //IPS_SetPosition($ips_var,20);
					AC_SetLoggingStatus($archiveHandlerID,$ips_var,true);
					AC_SetAggregationType($archiveHandlerID,$ips_var,0);  /* 0 Standard 1 Zähler */
					IPS_ApplyChanges($archiveHandlerID);

	            $ips_vare = IPS_CreateVariable(1);  			/* Variable Typ Integer anlegen */
	            IPS_SetName($ips_vare, $desc."_ext");
  		         IPS_SetParent($ips_vare, $parentID);
  		         //IPS_SetPosition($ips_vare,20);
					AC_SetLoggingStatus($archiveHandlerID,$ips_vare,true);
					AC_SetAggregationType($archiveHandlerID,$ips_vare,0);  /* 0 Standard 1 Zähler */
					IPS_ApplyChanges($archiveHandlerID);

	            $ips_varc = IPS_CreateVariable(1);  			/* Variable Typ Integer anlegen */
	            IPS_SetName($ips_varc, $desc."_chg");
  		         IPS_SetParent($ips_varc, $parentID);
  		         //IPS_SetPosition($ips_varc,10);
					AC_SetLoggingStatus($archiveHandlerID,$ips_varc,true);
					AC_SetAggregationType($archiveHandlerID,$ips_varc,0);  /* 0 Standard 1 Zähler */
					IPS_ApplyChanges($archiveHandlerID);

			      if (@IPS_GetVariableIDByName("Download", $parentID)==false)
						{
		            $ips_download = IPS_CreateVariable(2);  			/* Variable Typ Float anlegen */
		            IPS_SetName($ips_download, "Download");
  			         IPS_SetParent($ips_download, $parentID);
  		   	      IPS_SetPosition($ips_download,1000);
						AC_SetLoggingStatus($archiveHandlerID,$ips_download,true);
						AC_SetAggregationType($archiveHandlerID,$ips_download,0);  /* 0 Standard 1 Zähler */
						IPS_ApplyChanges($archiveHandlerID);
						}
			      if (@IPS_GetVariableIDByName("Upload", $parentID)==false)
						{
		            $ips_upload = IPS_CreateVariable(2);  			/* Variable Typ Float anlegen */
		            IPS_SetName($ips_upload, "Upload");
  			         IPS_SetParent($ips_upload, $parentID);
  		   	      IPS_SetPosition($ips_upload,1000);
						AC_SetLoggingStatus($archiveHandlerID,$ips_upload,true);
						AC_SetAggregationType($archiveHandlerID,$ips_upload,0);  /* 0 Standard 1 Zähler */
						IPS_ApplyChanges($archiveHandlerID);
						}
			      if (@IPS_GetVariableIDByName("Total", $parentID)==false)
						{
		            $ips_total = IPS_CreateVariable(2);  			/* Variable Typ Float anlegen */
		            IPS_SetName($ips_total, "Total");
  			         IPS_SetParent($ips_total, $parentID);
  		   	      IPS_SetPosition($ips_total,1010);
						AC_SetLoggingStatus($archiveHandlerID,$ips_total,true);
						AC_SetAggregationType($archiveHandlerID,$ips_total,0);  /* 0 Standard 1 Zähler */
						IPS_ApplyChanges($archiveHandlerID);
					   }
					}
				else
				   {
	            $ips_var = IPS_CreateVariable($type);
	            IPS_SetName($ips_var, $desc);
   	         IPS_SetParent($ips_var, $parentID);
	            }


         //Verknüpfe Variablenprofil mit neu erstellter Variable
        if($convertType == "CapacityMB")         	IPS_SetVariableCustomProfile($ips_var, "SNMP_CapacityMB");
        if($convertType == "CapacityGB")         	IPS_SetVariableCustomProfile($ips_var, "SNMP_CapacityGB");
        if($convertType == "CapacityTB") 	      	IPS_SetVariableCustomProfile($ips_var, "SNMP_CapacityTB");
        if($convertType == "Temperature")      	   IPS_SetVariableCustomProfile($ips_var, "SNMP_Temperature");
        if($convertType == "FanSpeed")            	IPS_SetVariableCustomProfile($ips_var, "SNMP_FanSpeed");
        if($convertType == "SmartStatus")         	IPS_SetVariableCustomProfile($ips_var, "SNMP_SmartStatus");
        }

    $count = count($this->snmpobj);
    array_push($this->snmpobj, new SNMPObj($oid, $desc, $convertType, $ips_var));
    $count = count($this->snmpobj);
    if($this->debug) echo "New SNMPObj (".$oid."/".$convertType.") registered, now monitoring '$count' snmp variables\n";
    }

	/*
	 * startet eine Abfrage am SNMP Server und aktualisiert die IPS-Variablen der registrierten
    * SNMP Objekte
    *
    */
    
	public function update($nolog=false, $download="", $upload="")
		{
		$download_val=0; $upload_val=0;

		if($this->debug) 
			{
			echo "Updating ". count($this->snmpobj) ." variable(s)\n";
			print_r($this->snmpobj);
			}
		foreach($this->snmpobj as $obj)
			{
      	$oid = ltrim($obj->OID,".");
			$obj->value=$this->getSNMPObject($oid);
			print_r($obj->value);
         //$obj->change = 0;
         if($this->debug) echo "Result of ". $obj->desc .": ". $obj->value ."\n";

         if($obj->convertType == "CapacityMB") 	$obj->value = $this->convertCapacity($obj->value, "MB");
         if($obj->convertType == "CapacityGB") 	$obj->value = $this->convertCapacity($obj->value, "GB");
         if($obj->convertType == "CapacityTB") 	$obj->value = $this->convertCapacity($obj->value, "TB");
         if($obj->convertType == "Temperature") $obj->value = $this->convertTemperature($obj->value);
         if($obj->convertType == "FanSpeed") 	$obj->value = $this->convertFanSpeed($obj->value);
         if($obj->convertType == "SmartStatus") $obj->value = $this->convertSmartStatus($obj->value);

			if($obj->convertType == "Counter32")
				{
				$intl=PHP_INT_SIZE*2;
				$j=strlen($obj->value);
				$z=array(); $z[0]=0; $z[1]=0;
				$i=$intl - ($j % $intl);
				$k=0;
				//if($this->debug) echo "Counter32 Umrechnung: String ".$obj->value." ist ".$j." Zeichen lang\n";
				while ($k<$j)
					{
					$zi=$i - ($i % $intl);
					$zii=$zi/$intl;
					if (isset($z[$zii])==true)
						{
						$z[$zii]=$z[$zii]*10+(integer)substr($obj->value,$k,1);
						}
					else
					   {
						$z[$zii]=	(integer)substr($obj->value,$k,1);
						}
					//if($this->debug) echo "**".$z[$zii]." ".$k." ".$i." ".$zi." ".$zii." \n";
					$i++;$k++;
					}
				$ips_vare=IPS_GetObjectIDByName((IPS_GetName($obj->ips_var)."_ext"),IPS_GetParent($obj->ips_var));/* Erweiterung, wenn Counter32 sich mit Integer nicht ausgeht */
				$ips_varc=IPS_GetObjectIDByName((IPS_GetName($obj->ips_var)."_chg"),IPS_GetParent($obj->ips_var)); /* Der Diff-Wert zwischen letzter und dieser Ablesung */
				$ips_download=IPS_GetObjectIDByName("Download",IPS_GetParent($obj->ips_var)); /* Der Diff-Wert zwischen letzter und dieser Ablesung */
				$ips_upload  =IPS_GetObjectIDByName("Upload"  ,IPS_GetParent($obj->ips_var)); /* Der Diff-Wert zwischen letzter und dieser Ablesung */
				$ips_total   =IPS_GetObjectIDByName("Total"   ,IPS_GetParent($obj->ips_var)); /* Der Diff-Wert zwischen letzter und dieser Ablesung */

				//echo "Alter Wert : ".GetValue($obj->ips_var).GetValue($ips_vare)."\n";
				if ($z[0]>=GetValue($ips_vare))
				   { /* kein Übertrag */
				   $a=($z[0]-GetValue($ips_vare));
				   for ($i=0;$i<$intl;$i++) $a*=10;
				   $a+=($z[1]-GetValue($obj->ips_var));
				   $aMByte=$a/1024/1024;
		         }
				else
		         {
		         /* Übertrag, zu schwierig zum nachdenken, Diff-Wert einfach auslassen, neue Werte trotzdem schreiben */
					$b=42-GetValue($ips_vare);
			   	for ($i=0;$i<$intl;$i++) $b*=10;
			   	$b+=94967296-GetValue($obj->ips_var);
			   	$a=$z[0];
			   	for ($i=0;$i<$intl;$i++) $a*=10;
			   	$a+=$z[1];
			   	//echo "******* a: ".$a." b: ".$b."  Summe: ".($a+$b)."\n";
			   	$a+=$b;
				   $aMByte=$a/1024/1024;
		         }
				echo "           Alter Wert          : ".str_pad(GetValue($ips_vare),6," ",STR_PAD_LEFT).substr(("0000000000000".(string)GetValue($obj->ips_var)),-8)." \n";
				echo "           Übertrag, neuer Wert: ".str_pad($z[0],6," ",STR_PAD_LEFT).substr(("0000000000000".(string)$z[1]),-8)."  Differenz : ".$a."   ".round($aMByte,2)." MByte. \n";
				if ($a>2147483647) { $a=2147483647; }  /* es können maximal 2 GByte Daten pro Tag in IP Symcon Integer Variablen dargestellt werden */

				if ($nolog==false)
					{
				   SetValue($obj->ips_var, $z[1]);
		         SetValue($ips_vare,$z[0]);
	            SetValue($ips_varc,$a);
	            /* download etc variablen setzen */
					if (IPS_GetName($obj->ips_var) == $download)
						{
		            SetValue($ips_download,$aMByte);
		            $download_val=$aMByte;
						}
					if (IPS_GetName($obj->ips_var) == $upload)
						{
		            SetValue($ips_upload,$aMByte);
		            $upload_val=$aMByte;
						}
	            }
            $obj->change = $aMByte;
    			}
			else
			   {
	         SetValue($obj->ips_var, $obj->value);
	         }
	   	}   /* ende foreach */
		if ($download != "")
		   {
         SetValue($ips_total,($upload_val+$download_val));
         echo "*** Gesamtübertragungsvolumen : ".($upload_val+$download_val)." MByte.\n";
	      }
		return ($this->snmpobj);
   	}

    /*
	  *  prüfe um welchen SNMP Rückgabetyp es sich handelt
     *  returns 3 => String / 1 => Integer / 2 => Float
     */

	function getSNMPType($oid)
		{
		$oid = ltrim($oid,".");
		$exec_param =" /h:". $this->host ." /c:". $this->community ." /o:". $oid;
		$result = IPS_Execute($this->binary, $exec_param, false, true);
		if($this->debug) echo "Execute SNMP-Query: ". $this->binary, " ".$exec_param."   ===>  ".$result."\n";
      $pos_start = stripos ($result, "Type=");
      $pos_end = $stop=strpos($result,"\n",$pos_start);
      $type = substr($result, $pos_start + 5, $pos_end - $pos_start-5);
		echo "Neue Variable mit Typ : \"".$type."\"\n";
      switch($type)
			{
         case "OctetString":
            return self::tSTRING;
         case "Integer":
            return self::tINT;
         case "TimeTicks":
            return self::tFLOAT;
         case "Counter32":
            return self::tLONG;
			default:
            return self::tSTRING;
        }
    	}

	/*
	 *  ein SNMP Objekt auslesen
	 *  host, community, binary werden bei construct festgelegt
	 * 	host 			IP Adresse
	 * 	community 	public
	 * 	binary		dateipfad zur snmpq command line inklusive Befehl
	 *
	 */

	function getSNMPObject($oid)
		{
		$oid = ltrim($oid,".");
		if ( ($this->SNMPmodul))
			{
			$valueObj=IPSSNMP_ReadSNMP($this->SNMPinstanz, $oid);
			$value=$valueObj[".".$oid];
			echo "Wert von IPS SNMP Modul empfangen : ".$value."\n";
			}
		else
			{	
			$exec_param =" /h:". $this->host ." /c:". $this->community ." /o:". $oid ." /v";
			$value = trim(IPS_Execute($this->binary, $exec_param, false, true));
			if($this->debug) echo "Execute SNMP-Query: ". $this->binary, " ".$exec_param."  ===> ".$value."\n";			
			}
		return ($value);
		}
		
	/*
	 *    läuft einen kompletten SNMP Pfad durch, praktisch für Tabellen, diese werden automatisch umgewandelt
	 */

	function walkSNMP($oid='')
		{
		if ($oid=='')
			{
			$exec_param =" /h:". $this->host ." /c:". $this->community;
			}
		else
			{
			$oid = ltrim($oid,".");   /* ersten Punkt entfernen */
			$exec_param =" /h:". $this->host ." /c:". $this->community ." /o:". $oid." /s";
			}
		$result = IPS_Execute($this->binary, $exec_param, false, true);
		if($this->debug) echo "Execute SNMP-Query: ". $this->binary, " ".$exec_param."  ===> ".$result."\n";
		//echo ">>>>>>>>>>>>>>>>>>parse\n";
		
		/* verschiedene Ausgabeformate vorbereiten:  */
		$csv="";
		$oidTable=array();
		$oidTableShort=array();
		$oidTableIndex=array();
		while ($start=stripos($result,"OID="))		// weitermachen solange OID= gefunden wird
			{
			$stop=strpos($result,"\n",$start);
			$oiditem=substr($result,$start+4,$stop-$start-4);
			//echo "gefunden auf ".$start." und ende auf ".$stop."    ".$oiditem."   ".substr($result,$start,100)."\n";
			$csv.=$oiditem.";";
			$oidTable[$oiditem]["Oid"]=substr($oiditem,strlen($oid));
			$result=substr($result,$stop);

			$start=stripos($result,"Path=");
			$stop=strpos($result,"\n",$start);
			$path=substr($result,$start+5,$stop-$start-5);
			//echo "gefunden auf ".$start." und ende auf ".$stop."    ".$path."   ".substr($result,$start,100)."\n";			
			$csv.=$path.";";
			$oidTable[$oiditem]["Path"]=$path;
			$result=substr($result,$stop);

			$pathItem=explode(".",$path);
			$anzahl=sizeof($pathItem)-1;
			$index1=$pathItem[$anzahl];
			$index2=$pathItem[$anzahl-1];
			//echo "Einzelne Objekte mit Grösse : ".($anzahl+1)."  letzter Eintrag : ".$pathItem[$anzahl]."\n";
			//print_r($pathItem);

			$start=stripos($result,"Type=");
			$stop=strpos($result,"\n",$start);
			$type=substr($result,$start+5,$stop-$start-5);
			$csv.=$type.";";
			$oidTable[$oiditem]["Type"]=$type;
			$result=substr($result,$stop);
			//echo substr($result,0,100);

			$start=stripos($result,"Value=");
			$stop=strpos($result,"\n",$start);
			$value=substr($result,$start+7,$stop-$start-8);       /* Anführungszeichen vorne und hinten eliminieren */
			//echo "Wert gefunden auf ".$start." und ende auf ".$stop."    ".$value."   ".substr($result,$start,100)."\n";			
			switch ($type)
		      {
            case "Integer":
            case "Gauge":
            case "Counter32":
               $valueCorr=(integer)$value;
               break;
		      default:
               $valueCorr=$value;
		         break;
		      }
			$csv.=$value."\n";
			$oidTable[$oiditem]["Value"]=$valueCorr;
			$oidTableShort[$oiditem]=$valueCorr;
			$oidTableIndex[$index1][$index2]=$valueCorr;

			$result=substr($result,$stop);
			}
		$this->lastwalk_csv=$csv;
		$this->lastwalk=$oidTableIndex;

      //print_r($oidTable);
      return $oidTableShort;
      }

	/* Ergebnisse des lastwalk als array ausgeben */

	 function walkSNMPresult($filter=array())
	 	{
	 	$size=sizeof($filter);
	 	//echo "Es sind ".($size/2)." Filter aktiviert.\n";
	 	if ($size==0)
	 	   {
			echo "Kein Filter definiert, das ganze Array ausgeben.\n";
		 	return $this->lastwalk;
		 	}
		else
		   {
			$i=0;
			$result=$this->lastwalk;
			while ($i<$size)
			   {
				//echo "   ** Iteration ".$i." mit OID : ".$filter[$i]." und Filter : ".$filter[$i+1]." auf diesem Array:\n";
				//print_r($result);
				$result=$this->searchArray($result,$filter[$i],$filter[$i+1]);
				$i+=2;
		      }
		   return $result;
		   }
	 	}

	private function searchArray($result,$oid,$filter)
	   {
	   $result1=array();
	   foreach($result as $entry)
		   {
		   if (isset($entry[$oid])==true)
		      {
		      if ((string)$entry[$oid]==$filter)
		         {
					//echo "gefunden";
			      //print_r($entry);
		         $result1[]=$entry;
		         }
		      }
		   }
		return $result1;
		}

	/* Ergebnisse des lastwalk als csv Formatierung ausgeben */

	 function walkSNMPcsv($filter=array())
	 	{
	 	return $this->lastwalk_csv;
	 	}

	/*
	 * die Default InterfaceTabelle auslesen: "1.3.6.1.2.1.2"
	 * es können Spalten und Zeilenfilter angewendet werden
	 *
	 */

	function getifTable($oid = "1.3.6.1.2.1.2")
		{
		echo "Ausgabe als Walk für Interface Tabelle:\n";
		$ifTableSnmp=IPSSNMP_WalkSNMP($this->SNMPinstanz, $oid); //ausgabe als Array wobei der Key die OID ist.
		/* Strukturierung der SNMP Tabelle:
		 * .1.3.6.1.2.1.2.2.1.22.17
		 * .1.3.6.1.2.1.2   System interfaces
		 *               .1  ifNumber
		 *               .2  ifTable 
		 */
		print_r($ifTableSnmp);
		$ifTable=array();
		foreach ($ifTableSnmp as $oid => $entry)
			{
			$needle=".1.3.6.1.2.1.2.2.1.";
			$pos=strpos($oid,$needle);
			if ($pos === 0) 
				{
				$index=explode(".",substr($oid,strlen($needle)));
				//echo str_pad($index[0]."|".$index[1],10)."  ".$entry."\n";
				$ifTable[$index[0]][$index[1]]=$entry;
				}
			else 
				{
				// echo $pos."  ".str_pad($oid,30)."  ".$entry."\n";
				}
			}
		//print_r($ifTable);

		$collumnsR=array();	
		foreach ($this->collumns as $key => $item) 
			{
			//echo "   ".$item."   ".$key."\n";
			$collumnsR[$item]=$key;
			} 
		//print_r($collumnsR);
		$filterLine=array("ifType" => "6");
		$filterCol=array(		/* kopiere die Spalten die enthalten sein sollen von collums */
			"1" => "ifIndex",
			"2" => "ifDescr",
			//"3" => "ifType",
			//"4" => "ifMTU",
			//"5" => "ifSpeed",
			"6" => "ifPhysAddress",
			//"7" => "ifAdminStatus",
			"8" => "ifOperStatus",
			//"9" => "ifLastChange",
			"10" => "ifnOctets",
			//"11" => "ifnUcastPkts",
			//"12" => "ifnNUcastPkts",
			//"13" => "ifnDiscards",
			//"14" => "ifnErrors",
			//"15" => "ifnUnknownProtos",
			"16" => "ifOutOctests",
			//"17" => "ifOutUcastPkts",
			//"18" => "ifOutNUcastPkts",
			//"19" => "ifOutDiscards",
			//"20" => "ifOutErrors",
			//"21" => "ifOutQLen",
			//"22" => "ifSpecific",
					);

		$ifTableR=array();
		foreach ($ifTable as $i => $Line) 	/* Spaltenvorschub */
			{
			echo "Bearbeite Spalte ".$i."   ".$this->collumns[$i].". Nur übernehmen wenn Filter gesetzt ist\n";
			if (isset($filterCol[$i])==true)
				{
				foreach ($Line as $j => $entry)		/* Zeilenvorschub */
					{
					/* Zeile nur Übernehmen wenn Filterkriterium zutrifft */
				
					//echo "   ".$i."   ".$j."   ".$entry."  \n";
					//echo $entry."  ";
					$print=false;
					foreach ($filterLine as $key => $item)
						{
						if (isset($collumnsR[$key])==true) 
							{
							//echo "*".$collumnsR[$key]." (".$key.")==".$item;
							if ($ifTable[$collumnsR[$key]][$j]==$item) 
								{
								$print=true;
								}
							}
						//if ($ifTableR[0][$j]==$key) echo "*"; 
						} 
					if ($print) $ifTableR[$j][$i]=$entry;
					}
				} 
			//echo "\n";	
			}
		//echo "Ausgabe reversierte Tabelle nach Spalten- und Zeilenfilter.\n";	
		$this->lastTable=$ifTableR;	
		$str = "<table width='90%' align='center'>"; 
		$head=true;
		foreach ($ifTableR as $i => $Line) 
			{
			if ($head)
				{
				foreach ($ifTableR[$i] as $j => $entry)
					{
					//echo $this->collumns[$j]."  ";
					$str.="<td><b>".$this->collumns[$j].'</b></td>';
					}
				$head=false;
				}			
			$str.='</tr>';	
			//echo "\n";		
			$str.="<tr>";	
			foreach ($Line as $j => $entry)
				{
				//echo $entry."  ";
				$str.="<td>".$entry.'</td>';
				}
			$str.='</tr>';	
			//echo "\n";	
			} 
		$str.='</table>';
		//echo $str."\n";
		return ($str);	
		}

	function printifTable($filter="")
		{
		if ($filter=="")
			{
			return ($this->lastTable);
			}
		else
			{
			$result=array();
			if ( is_array($filter)==true )
				{
				//echo "Ist ein Array.\n";
				foreach ($this->lastTable as $entry)
					{
					$str="";
					$found=false;
					foreach ($entry as $key => $object)
						{
						if (isset($filter[$this->collumns[$key]]) == true)
							if ($filter[$this->collumns[$key]] == $object) $found=true;
						$str.=$this->collumns[$key].":".$object." ";
						}
					if ($found) $result[]=$str."\n"; else $str="";
					}
				}
			else
				{	
				foreach ($this->lastTable as $entry)
					{
					foreach ($entry as $key => $object)
						{
						if ($this->collumns[$key]==$filter) $result[]=$object;
						}
					}
				}	
			return ($result);	
			}	
		}

	function print_snmpobj()
		{
		print_r($this->snmpobj);
		}

	function CapacityRater($input)
	 	{
	 	$unit="Byte";
	 	if ($input > 1024)
	 	   {
	 		$unit="kByte";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="MByte";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="GByte";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="TByte";
	 		$input/=1024;
	 		}
	 	return ((string)round($input,3)." ".$unit);
     }



    //returns -1 on error
    private function convertCapacity($input, $unit){
        $pos = stripos($input, "MB");
        if($pos === false) {
            $pos = stripos($input, "GB");
            if($pos === false) {
                $pos = stripos($input, "TB");
                if($pos === false) {
                    return -1;
                }else{
                    $funit = "TB";
                }
            } else{
                $funit = "GB";
            }
        }else{
            $funit = "MB";
        }

        $result = substr($input, 0, $pos);
        $result = trim($result);

        switch ($funit){
            case "GB":
            $result = $result*1000;
            break;
            case "TB":
            $result = $result*1000*1000;
            break;
        }

        switch($unit){
            case "MB":
            return round($result);
            case "GB":
            return round($result / 1000);
            case "TB";
            return round($result / 1000000);
        }
    }

    //returns -1 on error
    private function convertTemperature($input){
        $pos = stripos($input, "C");
        if($pos === false){
            $result = -1;
        }else{
            $result = substr($input, 0, $pos);
            $result = round(trim($result));
        }
        return $result;
    }

    //returns -1 on error
    private function convertFanSpeed($input){
        $pos = stripos($input, "RPM");
        if($pos === false){
            $result = -1;
        }else{
            $result = substr($input, 0, $pos);
            $result = round(trim($result));
        }
        return $result;
    }

    private function convertSmartStatus($input){
        $pos = stripos($input, "GOOD");
        if($pos === false){
            return false;
        }else{
            return true;
        }
    }

    function evalOID($oid)
	 	{
	 	$oid_pins=explode(".",$oid);
	 	$pinMax=sizeof($oid_pins)-1;
	 	$pos=$oid_pins[$pinMax]+$oid_pins[($pinMax-1)]*100;
		echo $oid." ".sizeof($oid_pins)." ".$pos."\n";
		print_r($oid_pins);
		}
		
	function sizeCounter($value)
		{
		$size=0;
		$i=128;
		do 
			{
			$i--;
			$size++;
			$value=$value/2;
			}	while ( ($i>0) && ($value>1) );
		return ($size);
		}				
		
	public function findSNMPModul($findlibrary="Babenschneider Symcon Modules",$findname="IPSSNMP",$IPAdresse="default")
		{
		//echo "Übersicht der verwendeten Bibliotheken:\n";
		$librarylist=IPS_GetLibraryList(); 
		foreach ($librarylist as $libraryID)
			{
			$Leintrag=IPS_GetLibrary($libraryID);
			if ($Leintrag["Name"]==$findlibrary) 
				{
				$foundlibraryID=$libraryID;
				//echo "** ".$libraryID."   ".str_pad($Leintrag["Name"],30)."   ".$Leintrag["URL"]."\n";
				}
			//else echo "   ".$libraryID."   ".str_pad($Leintrag["Name"],30)."   ".$Leintrag["URL"]."\n";
			}
		//echo "\n";
		if (false)
			{
			echo "Modulliste für Bibliothek \"".$findlibrary."\":   ".$foundlibraryID."\n";
			$modullist=IPS_GetLibraryModules ($foundlibraryID);
			foreach ($modullist as $modulID)
				{
				$Meintrag=IPS_GetModule($modulID);
				//print_r($Meintrag);
				if ($Meintrag["ModuleName"]==$findname) echo "** ".$modulID."    ".str_pad($Meintrag["ModuleName"],30)."\n"; 
				else echo "   ".$modulID."    ".str_pad($Meintrag["ModuleName"],30)."\n";
				}
			echo "\n";
			}
			
		$instanzlist = IPS_GetInstanceList();
		$modul=array();

		$instanzSNMPModuleID=false;
		foreach ($instanzlist as $instanzID)
			{
			$werte=IPS_GetInstance($instanzID);
			$instanzname=IPS_GetName($instanzID);
			$modulname=$werte["ModuleInfo"]["ModuleName"];
			//echo "     ".$instanzID."   ".$modulname."\n";
			//print_r($werte); break;
			if (isset($modul[$modulname])==true) $modul[$modulname].="|".$instanzname;
			else $modul[$modulname]=$instanzname;
			if ($modulname==$findname)
				{
				//echo " ModuleName:".$modulname." (".$werte["ModuleInfo"]["ModuleID"].") hat Instanz:".$instanzname." \n";
				//echo " Module mit Name \"".$modulname."\" hat Instanz: ".$instanzname." (".$instanzID.")\n";
				$configuration=IPS_GetConfiguration($instanzID);
				//echo " Konfiguration :".$configuration."\n";
				$confObject=json_decode($configuration);
				//echo "IP Adresse : ".$confObject->SNMPIPAddress."\n";
				if ($IPAdresse=="default")
					{
					$instanzSNMPModuleID=$instanzID;
					}
				else
					{
					if ($IPAdresse==$confObject->SNMPIPAddress) $instanzSNMPModuleID=$instanzID;
					}
				}
			}
		return($instanzSNMPModuleID);		
		}																
	} /* ende class */	

class SNMPObj
{
    //Objekteigenschaften
    public $OID;                     //SNMP Message ID
    public $desc;                    //Beschreibung
    public $value;                   //Wert
    public $convertType;          //Typ-Converter
    public $ips_var;                   //ID der IPS-Variable welche den SNMP Wert speichert

    public function __construct($OID, $desc, $convertType, $ips_var){
        $this->OID                    = $OID;
        $this->desc                    = $desc;
        $this->convertType    = $convertType;
        $this->ips_var             = $ips_var;
    }
}

?>