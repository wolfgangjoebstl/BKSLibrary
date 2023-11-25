<?php

/*
 * SNMP Objekte auslesen. eine Klasse pro Router
 *
 * benötigt Exe File mit CLI oder das IPS SNMP Modul
 * abhängig von Verfügbarkeit wird entweder die eine oder andere Methode aufgerufen
 * Beim IPS Modul gehen mehr Funktionalitäten, wie zB Tabellen auslesen oder Walk
 *
 * getSNMPType funktioniert nur mit Commandline
 *
 * Basisifunktion ist die verwendung von registerSNMPObject und update.
 * Immer zuerst Klasse anlegen, dann SNMP Variablen registrieren und am Schluss update aufrufen
 *
 *  _construct
 *  createVariableProfil
 *
 *  registerSNMPObj
 *  update
 *  getSNMPType
 *  getSNMPObject
 *  walkSNMP
 *  walkSNMPresult
 *  searchArray
 *  walkSNMPcsv
 *  getifTable
 *  printifTable
 *  print_snmpobj
 *
 *  CapacityRater
 *  SpeedRater
 *  TimeRater
 *  convertCapacity
 *  convertTemperature
 *  convertFanSpeed
 *  convertSmartStatus
 *
 */

class SNMP_OperationCenter
    {
    //Objekteigenschaften
    protected 	$host;                   //SNMP Serveradresse
    protected 	$community;              //SNMP Community
    protected 	$binary;                 //Dateipfad zur ssnmpq.exe
    public 		$debug = false;             //Bei true werden Debuginformationen ausgegeben
    protected 	$snmpobj=array();    		//array registrierter snmp objekte welche beim server abgefragt werden
    private 	$lastwalk=array();         //hier Ergebnis vom letzten Walk Befehl hineinschreiben als array
    private 	$lastwalk_csv="";          //hier Ergebnis vom letzten Walk Befehl hineinschreiben als csv

    private 	$lastTable=array();         //hier Ergebnis vom letzten ifTable Befehl hineinschreiben als array

    private 	$CategoryIdData;
	private 	$SNMPmodul=false;				// true wenn ein SNMP Modul installiert ist
	private 	$SNMPRead=false;				// true wenn SNMP Read unterstützt wird

	private 	$SNMPinstanz=false;				// Instanz des passenden SNMP Moduls

    //IPS Datentypen
    const tBOOL         = 0;
    const tINT          = 1;
    const tFLOAT        = 2;
    const tSTRING       = 3;
    const tLONG         = 4;          /* auf mehrere Integer Werte aufteilen, wenn notwendig , also wenn kein 64 Bit System */

	/*
	 *  Konstruktor
	 */

	public function __construct($CategoryIdData, $host, $community, $binary, $debug=false, $useSnmpLib=false)
		{
		$this->host         	= $host;
		$this->community     	= $community;
		$this->binary         	= $binary;
		$this->debug         	= $debug;
		$this->CategoryIdData 	= $CategoryIdData;
		$this->SNMPRead			= true;
		
		// prüfe ob ein SNMP Modul mit der geünschten IP Adresse installiert ist, damit können Abfragen schneller erledigt werden
		$this->SNMPinstanz=$this->findSNMPModul("Babenschneider Symcon Modules","IPSSNMP",$host);
		if ($this->SNMPinstanz !== false) 
			{
			$this->SNMPmodul=true;
			echo "SNMP Modul verwenden. Instanz ID : ".$this->SNMPinstanz."   (".IPS_GetName($this->SNMPinstanz).")\n";
			}
		else 
			{
			$this->SNMPmodul=false;
			/* wenn schon kein SNMP Modul geladen wurde, dann zumindest schauen ob die Script Datei dabei ist */
			if ( (is_file($binary)) && ($useSnmpLib==false) )
				{
				echo "SNMPQ Commandline verwenden. Datei im Verzeichnis $binary vorhanden.\n";
				}
			else 
				{
				if (!is_file($binary)) echo "Fehlerbehandlung erforderlich, SNMPQ Datei im Verzeichnis $binary NICHT vorhanden.\n";
				$this->SNMPRead=false;
				return;
				}
			}
			
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
		//echo "registerSNMPObj wurde aufgerufen.\n";
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
  		        IPS_SetHidden($ips_vare, true);
  		        //IPS_SetPosition($ips_vare,20);
				AC_SetLoggingStatus($archiveHandlerID,$ips_vare,true);
				AC_SetAggregationType($archiveHandlerID,$ips_vare,0);  /* 0 Standard 1 Zähler */
				IPS_ApplyChanges($archiveHandlerID);

	            $ips_varc = IPS_CreateVariable(1);  			/* Variable Typ Integer anlegen */
	            IPS_SetName($ips_varc, $desc."_chg");
		        IPS_SetParent($ips_varc, $parentID);
  		        IPS_SetHidden($ips_varc, true);
				//IPS_SetPosition($ips_varc,10);
				AC_SetLoggingStatus($archiveHandlerID,$ips_varc,true);
				AC_SetAggregationType($archiveHandlerID,$ips_varc,0);  /* 0 Standard 1 Zähler */
				IPS_ApplyChanges($archiveHandlerID);

	            $ips_vars = IPS_CreateVariable(1);  			/* Variable Typ Integer anlegen */
	            IPS_SetName($ips_vars, $desc."_speed");					// speed macht nicht bei jedem Counter Sinn, wäre aber die Veränderung pro Sekunde
		        IPS_SetParent($ips_vars, $parentID);
  		        //IPS_SetPosition($ips_varc,10);
				AC_SetLoggingStatus($archiveHandlerID,$ips_vars,true);
				AC_SetAggregationType($archiveHandlerID,$ips_vars,0);  /* 0 Standard 1 Zähler */
				IPS_ApplyChanges($archiveHandlerID);
				echo "Variable ".IPS_GetName($ips_vars)." mit OID $ips_vars in ".IPS_GetName($parentID)." mit OID $parentID angelegt.\n";

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
	 * startet eine Abfrage am SNMP Server und aktualisiert die IPS-Variablen der registrierten SNMP Objekte
     * die ausgelesenen Werte werden verarbeitet
     *
     */
    
	public function update($nolog=false, $download="", $upload="")
		{

		$localDebug=true;
		$download_val=0; $upload_val=0;

		if($this->debug) 
			{
			echo "SNMP Library, Updating ". count($this->snmpobj) ." variable(s)\n";
			if ($localDebug) print_r($this->snmpobj);
			}
		foreach($this->snmpobj as $obj)
			{
      		$oid = ltrim($obj->OID,".");
			$obj->value=$this->getSNMPObject($oid);
			//print_r($obj->value);
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
				/* Probleme mit Long Integer, auf 16 Bit Systemen kann der Wert nicht richtig verarbeitet werden. Daher eine raffinierte Umrechnung bauen 
				 * Der COUNTER32 String wird bewertet, dann wird bei der hoechsten Zahl begonnen, alter Wert*10+neuer Wert
				 * Die Ergebnisse werden nach z geschrieben. Begonnen wird bei 0 und um so größer die Zahl um so mehr Einträge gibt es in z.
				 * Zwei Stellen sind immer in der Übertragslogik enthalten. Daher nachher den Wert auf zwei Stellen normieren.
				 *
				 * vorher bestimmen wieviele Stellen es sein werden und rechtzeitig den Übertrag machen. Dafür mit i als Startwert rechnen. Wert hochzählen und wenn größer den Übertrag machen
				 *
				 */
				$intl=PHP_INT_SIZE*2;		// PHP_INT_SIZE Die Größe einer Ganzzahl in Bytes in diesem Build von PHP. Üblicherweise 2 bei 16 Bit, 4 bei 32 Bit und 8 bei aktuellen 64 Bit Systemen
				if ($intl>8) $intl=8;		// bloederweise kann IP Symcon nur 32 Bit.
				$j=strlen($obj->value);
				$z=array(); 
				//$z[0]=0; $z[1]=0;
				$i=$intl - ($j % $intl);	// schlampig umgerechnet, 4 Bytes sind 8 Hexstellen sind ca. 8 Dezimalstellen, bei 7 Zeichen wird i auf 1 gesetzt, bei 6 auf 2 , Sonderregelung bei 8 auf 0
				if ($i==8) $i=0; 
				$k=0;
				if (($this->debug)  && $localDebug) echo "Counter32 Umrechnung: String ".$obj->value." ist ".$j." Zeichen lang. PHP Integer kann max Zahl ".PHP_INT_MAX.", das sind ".PHP_INT_SIZE." Bytes. Auf ".$intl." Stellen begrenzen. i beginnt mit $i.\n";
				while ($k<$j)
					{
					$zi=$i - ($i % $intl);			// Zi faengt bei 0 an und wird erst erhoeht wenn i groesser intl ist, zB 16
					$zii=intdiv($zi,$intl);			// damit kein seltsames verhalten entsteht, Integer Divison
					if (isset($z[$zii])==true)
						{
						$z[$zii]=$z[$zii]*10+(integer)substr($obj->value,$k,1);
						}
					else
					   	{
						$z[$zii]=	(integer)substr($obj->value,$k,1);
						}
					if (($this->debug)  && $localDebug) echo "** char pos ".$k." ".$i." ".$zi." Zahl wird abgespeichert auf Key ".$zii." : ".$z[$zii]."\n";
					$i++;$k++;
					}
				//print_r($z);
				if (sizeof($z)>1) 
					{
					if ($this->debug) 
						{
						echo "Die Zahl ist groesser als PHP/IP Symcon verarbeiten kann, daher aufgeteilt auf ".sizeof($z)." Positionen/Subzahlen : ";
						echo $z[0]." ".$z[1]."\n";
						}
					}
				elseif (sizeof($z)>0) 
					{
					$z[1]=$z[0]; $z[0]=0;		//  den Wert auf zwei Stellen normieren
					}
                else
					{
					$z[0]=0; $z[1]=$z[0]; 		//  defaultwert setzen, kein Ergebnis erhalten
					}
				$parentID=IPS_GetParent($obj->ips_var);					
				$ips_vare=IPS_GetObjectIDByName((IPS_GetName($obj->ips_var)."_ext"),$parentID);/* Erweiterung, wenn Counter32 sich mit Integer nicht ausgeht */
				$ips_varc=IPS_GetObjectIDByName((IPS_GetName($obj->ips_var)."_chg"),$parentID); /* Der Diff-Wert zwischen letzter und dieser Ablesung */
				$ips_vars=@IPS_GetObjectIDByName((IPS_GetName($obj->ips_var)."_speed"),$parentID); /* Der Diff-Wert zwischen letzter und dieser Ablesung mal 8 pro Sekunde => Bit/s */
				if ($ips_vars==false)
					{
					$ips_vars = IPS_CreateVariable(1);  			/* Variable Typ Integer anlegen */
	            	IPS_SetName($ips_vars, IPS_GetName($obj->ips_var)."_speed");					// speed macht nicht bei jedem Counter Sinn, wäre aber die Veränderung pro Sekunde
			        IPS_SetParent($ips_vars, $parentID);
  			        //IPS_SetPosition($ips_varc,10);
					$archiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];					
					AC_SetLoggingStatus($archiveHandlerID,$ips_vars,true);
					AC_SetAggregationType($archiveHandlerID,$ips_vars,0);  /* 0 Standard 1 Zähler */
					IPS_ApplyChanges($archiveHandlerID);
					echo "Variable ".IPS_GetName($ips_vars)." mit OID $ips_vars in ".IPS_GetName($parentID)." mit OID $parentID angelegt.\n";
					}
				
				$ips_download=IPS_GetObjectIDByName("Download",IPS_GetParent($obj->ips_var)); /* Der Diff-Wert zwischen letzter und dieser Ablesung */
				$ips_upload  =IPS_GetObjectIDByName("Upload"  ,IPS_GetParent($obj->ips_var)); /* Der Diff-Wert zwischen letzter und dieser Ablesung */
				$ips_total   =IPS_GetObjectIDByName("Total"   ,IPS_GetParent($obj->ips_var)); /* Der Diff-Wert zwischen letzter und dieser Ablesung */

				//echo "Alter Wert : ".str_pad(GetValue($obj->ips_var),$intl," ",STR_PAD_LEFT)." |ext| ".str_pad(GetValue($ips_vare),$intl," ",STR_PAD_LEFT)."  OID $obj->ips_var und $ips_vare.\n";
				//echo "Neuer Wert : ".str_pad($z[1],$intl," ",STR_PAD_LEFT)." |ext| ".str_pad($z[0],$intl," ",STR_PAD_LEFT)."        DezHex Umrechnung alter Wert : ".dechex(GetValue($obj->ips_var))."\n";
                $propertyVar=IPS_GetVariable($obj->ips_var);
                //print_r($propertyVar);
				$lastUpdate=$propertyVar["VariableUpdated"]; $timeForSpeed=time()-$lastUpdate;
				//echo "letztes Update war am/um ".date("D m Y H:i",$lastUpdate).". das sind $timeForSpeed vergangene Sekunden.\n";
				
				if ($z[0]>=GetValue($ips_vare))	/* Übertrag muss mit berücksichtigt werden */
					{ 
				   	$a=($z[0]-GetValue($ips_vare));
				   	for ($i=0;$i<$intl;$i++) $a*=10;
				   	$a+=($z[1]-GetValue($obj->ips_var));
				   	$aMByte=$a/1024/1024;					/* Differenzbetrag in Mbyte betrachten */
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
			   		echo "******* Kein Übertrag passiert a: ".$a." b: ".$b."  Summe: ".($a+$b)."\n";
			   		$a+=$b;
				   	$aMByte=$a/1024/1024;
		         	}
				if ($timeForSpeed>0) 
					{
					$speed=$a/$timeForSpeed*8;			// Wert in Bit/s
					if ($this->debug)
						{
						echo "           Alter Wert          : ".str_pad(GetValue($ips_vare),6," ",STR_PAD_LEFT).substr(("0000000000000".(string)GetValue($obj->ips_var)),-$intl)." \n";
						echo "           Übertrag, neuer Wert: ".str_pad($z[0],6," ",STR_PAD_LEFT).substr(("0000000000000".(string)$z[1]),-$intl)."\n";
						echo "           Differenz           : ".str_pad($a,6," ",STR_PAD_LEFT)."   ".round($aMByte,2)." MByte. Geschwindigkeit durchschnittlich : $speed Bit/s.\n";
						echo "In den letzten ".$this->TimeRater($timeForSpeed)." wurden ".$this->CapacityRater($a)." Daten übertragen. Durchschnittliche Geschwindigkeit ".$this->SpeedRater($speed)."\n";
						}
					if ( ($a>99999999) || ($a<0) ) IPSLogger_Inf(__file__, "In den letzten ".$this->TimeRater($timeForSpeed)." wurden ".$this->CapacityRater($a)." Daten übertragen. Durchschnittliche Geschwindigkeit ".$this->SpeedRater($speed));
					
					if ($a>2147483647) { $a=2147483647; }  /* es können maximal 2 GByte Daten pro Tag in IP Symcon Integer Variablen dargestellt werden */
	
					if ($nolog==false)
						{
						SetValue($obj->ips_var, $z[1]);
			        	SetValue($ips_vare,$z[0]);
		            	SetValue($ips_varc,$a);
						SetValue($ips_vars,$speed);
						
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
					} 		// zwei Abfragen kurz hintereinander abfangen
        	    $obj->change = $aMByte;
    			}
			else
			   	{
	         	SetValue($obj->ips_var, $obj->value);
	         	}
			if ($this->debug) echo "======================================\n";
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
		$value="";
		$oid = ltrim($oid,".");
		if ( ($this->SNMPmodul))
			{
            //echo "Abfrage von $oid von ".$this->SNMPinstanz."  ";
			$valueObj=IPSSNMP_ReadSNMP($this->SNMPinstanz, $oid);
            //print_r($valueObj);
			if (isset($valueObj[".".$oid])) $value=$valueObj[".".$oid];          // alte Darstellung mit . am Anfang
            else $value=$valueObj[$oid];
			echo "getSNMObject, Wert von IPS SNMP Modul für $oid empfangen : ".$value."\n";
			}
		elseif ($this->SNMPRead)
			{	
			$exec_param =" /h:". $this->host ." /c:". $this->community ." /o:". $oid ." /v";
			$value = trim(IPS_Execute($this->binary, $exec_param, false, true));
			if($this->debug) echo "Execute SNMP-Query: ". $this->binary, " ".$exec_param."  ===> ".$value."\n";			
			echo "getSNMObject, Wert von SSNMPQ script für $oid empfangen : ".$value."\n";
			}
        else echo "Fehler, gar nix empfangen. SNMP Modul und SNMP Script Selektor undefiniert.\n";
		return ($value);
		}
		
	/* walkSNMP, Durchlauf der SNMP Objekte
	 * läuft einen kompletten SNMP Pfad durch, praktisch für Tabellen, diese werden automatisch umgewandelt
	 * als return Wert wird oidTableShort geliefert. Das ist SNMP Id plus Wert, ein Wert nach dem anderen.als Array
	 * zusätzlich wird auch in der Klasse $this->lastwalk geschrieben. Kann man mit SNMPWalkResult ausgeben.
	 *
	 * Wir machen ein parse des textfiles das von snmp query ausgegeben wird. Es werden Zeilen gesucht die mit OID beginnen.
	 *
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
		$result = IPS_Execute($this->binary, $exec_param, false, true);			// vorletzte Variable ist dummy, wenn letzte true wird auf das Ergebins gewartet 
		if($this->debug) 
			{
			echo "Execute SNMP-Query: ". $this->binary, " ".$exec_param."  ===> \n".$result."\n";
			echo ">>>>>>>>>>>>>>>>>>parse\n";
			}
			
		/* verschiedene Ausgabeformate vorbereiten:  */
		$csv="";
		$oidTable=array();
		$oidTableShort=array();
		$oidTableIndex=array();
		while ($start=stripos($result,"OID="))		// weitermachen solange OID= gefunden wird
			{
			$stop=strpos($result,"\n",$start);
			$oiditem=substr($result,$start+4,$stop-$start-4);
			if($this->debug) echo "gefunden auf ".$start." und ende auf ".$stop."  Oiditem:   ".$oiditem."  Auszug String:  ".substr($result,$start,100)."\n";
			$csv.=$oiditem.";";
			$oidTable[$oiditem]["Oid"]=substr($oiditem,strlen($oid));
			$result=substr($result,$stop);

			$start=stripos($result,"Path=");
			if ($start===false) 
				{
				echo "Ausgabe commandline nicht vollständig.\n";
				break;
				}
			$stop=strpos($result,"\n",$start);
			$path=substr($result,$start+5,$stop-$start-5);
			if($this->debug) echo "gefunden auf ".$start." und ende auf ".$stop."    ".$path."   ".substr($result,$start,100)."\n";			
			$csv.=$path.";";
			$oidTable[$oiditem]["Path"]=$path;
			$result=substr($result,$stop);

			$pathItem=explode(".",$path);
			if($this->debug) print_r($pathItem);
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
			$oidTableShort[".".$oiditem]=$valueCorr;		/* Kompatibilität mit IPSSNMP Modul */
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

    /* von walkSNMPResult aufgerufen
     *
     */
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
	 *		der Spaltenfilter selektiert die Spalten die ausgewertet sollen, diese werden dann in der ifTable zusammengefasst
     *		der Zeilenfilter definiert key => item Paare die in der Zeile vorkommen müssen das die Zeile übernommen wird
	 *
     * verwendet wird entweder das Commandline Tool oder das IPS SNMP Nodul
     *
	 */

	function getifTable($oidP = "1.3.6.1.2.1.2", $filterLineP = false, $filterColumnP = false)
		{
		/* Strukturierung der SNMP Tabelle:
		 * .1.3.6.1.2.1.2.2.1.22.17
         *
         * .1.3.6.1.2.1 - SNMP MIB-2
		 * .1.3.6.1.2.1.2   System interfaces
         * .1.3.6.1.2.1.2.1 - ifNumber
         * .1.3.6.1.2.1.2.2 - ifTable 
         * .1.3.6.1.2.1.2.2.1 - ifEntry
         *      .1 ifIndex
         *      .2 ifDescr
         *      .3 ifType
		 */
		if ($this->SNMPmodul)
			{
			if ($this->debug) echo "Ausgabe als Walk für Interface Tabelle mit IPS SNMP Modul beginnend ab $oidP :\n";
			$ifTableSnmp=IPSSNMP_WalkSNMP($this->SNMPinstanz, $oidP); //ausgabe als Array wobei der Key die OID ist.
			}
		elseif ($this->SNMPRead)
			{			
			if ($this->debug) echo "Ausgabe als Walk für Interface Tabelle mit SNMP Commandline Tool beginnend ab $oidP :\n";
			$ifTableSnmp = $this->walkSNMP($oidP);	/* die OID jedes Eintrags muss am Anfang auch einen Punkt haben, wurde angepasst an das IPSSNMP modul */
			}
		//echo "Resultat von walk ifTable:\n";
		//print_r($ifTableSnmp);

		$ifTable=array();
		$needle=".".$oidP.".2.1.";
		//echo "Wir suchen die Needle \"$needle\" .\n";
		foreach ($ifTableSnmp as $oid => $entry)
			{
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
        if ($this->debug) 
            {
            echo "Zusammgefasste ausgelesene ifTable nach Spalten:\n";
		    print_r($ifTable);
            }

		$collumnsR=array();	
		foreach ($this->collumns as $key => $item) 
			{
			//echo "   ".$item."   ".$key."\n";
			$collumnsR[$item]=$key;
			} 
		//print_r($collumnsR);

		if ($filterLineP===false)
            {
			$filterLine=array("ifType" => "6");
			}
		else $filterLine=$filterLineP;

		if ($filterColumnP===false)
            {
            $filterCol=array(		/* kopiere die Spalten die enthalten sein sollen von collums */
                "1" => "ifIndex",
                "2" => "ifDescr",
                //"3" => "ifType",			// 6 ist ein echtes Port
                "4" => "ifMTU",
                "5" => "ifSpeed",
                "6" => "ifPhysAddress",
                //"7" => "ifAdminStatus",
                "8" => "ifOperStatus",			// 1 ist aktiv
                "9" => "ifLastChange",
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
            }
        else $filterCol=$filterColumnP;
        print_r($filterCol);
		
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
					if (isset($filterLine["AND"])) 
						{
						$filterLineAnd=$filterLine["AND"];
						$gefundenZiel=sizeof($filterLineAnd);
						$gefunden=0;
						$print=false;
						if ($gefundenZiel>0)
							{					
							foreach ($filterLineAnd as $key => $item)
								{
								if (isset($collumnsR[$key])==true) 
									{
									//echo "*".$collumnsR[$key]." (".$key.")==".$item;
									if ($ifTable[$collumnsR[$key]][$j]==$item) 
										{
										$gefunden++;
										}
									}
								//if ($ifTableR[0][$j]==$key) echo "*"; 
								} 
							}
						else $print=true;
						if ($gefunden==$gefundenZiel) $print=true;
						}
					else
						{
						$print=false;
						if (sizeof($filterLine)>0)
							{					
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
							}
						else $print=true;
						}
					if ($print) $ifTableR[$j][$i]=$entry;
					}
				} 
			//echo "\n";	
			}
		echo "Ausgabe reversierte Tabelle nach Spalten- und Zeilenfilter.\n";	
		print_r($ifTableR);

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

    /* Ausgabe der ifTable
     * bearbeitet lastTable aus der Klasse
     */

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

    /*
     */

	function print_snmpobj()
		{
		print_r($this->snmpobj);
		}
		
	/* Auswertungen lesbarer machen
	 * Datenvolumen verstaendlich machen
	 *	
	 */

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

	/* Auswertungen lesbarer machen
	 * Datenübertragungsgeschwindigkeit verstaendlich machen
	 *	
	 */

	function SpeedRater($input)
	 	{
	 	$unit="Bit/s";
	 	if ($input > 1024)
	 	   {
	 		$unit="kBit/s";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="MBit/s";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="GBit/s";
	 		$input/=1024;
	 		}
	 	if ($input > 1024)
	 	   {
	 		$unit="TBit/s";
	 		$input/=1024;
	 		}
	 	return ((string)round($input,3)." ".$unit);
     }

	/* Auswertungen lesbarer machen
	 * Datenvolumen verstaendlich machen
	 *	
	 */

	function TimeRater($input)
	 	{
	 	$unit="Sekunden";
	 	if ($input > 60)
	 	   {
	 		$unit="Minuten";
	 		$input/=60;
	 		}
	 	if ($input > 60)
	 	   {
	 		$unit="Stunden";
	 		$input/=60;
	 		}
	 	if ($input > 24)
	 	   {
	 		$unit="Tage";
	 		$input/=24;
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
		
    /*
     *  rausfinden ob das IPS SNMP Modul aus der Babenschneider Bibliothek vorhanden ist.
     *  wenn die IP Adresse mitgeliefert wird, auch die Konfiguration vergleichen, es ist ja möglich das mehrere Module angelegt wurden
     *  wird gleich bei construct aufgerufen.
     */

	public function findSNMPModul($findlibrary="Babenschneider Symcon Modules",$findname="IPSSNMP",$IPAdresse="default",$localDebug=false)
		{
		$localDebug=false;
		if (($this->debug) && $localDebug) 
            {
            echo "Übersicht der verwendeten Bibliotheken, suche \"$findlibrary\", markiere mit **:\n";
            $librarylist=IPS_GetLibraryList(); 
            foreach ($librarylist as $libraryID)
                {
                $Leintrag=IPS_GetLibrary($libraryID);
                if ($Leintrag["Name"]==$findlibrary) 
                    {
                    $foundlibraryID=$libraryID;
                    if (($this->debug)  && $localDebug) echo "** ".$libraryID."   ".str_pad($Leintrag["Name"],30)."   ".$Leintrag["URL"]."\n";
                    }
                else
                    {
                    if (($this->debug) && $localDebug) echo "   ".$libraryID."   ".str_pad($Leintrag["Name"],30)."   ".$Leintrag["URL"]."\n";
                    }
                }
			echo "Modulliste für Bibliothek \"".$findlibrary."\"  ".$foundlibraryID.", suche Modul \"".$findname."\", markiere mit **:\n";
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
		foreach ($instanzlist as $instanzID)                    // jetzt wirklich die ganze Instanzliste durchgehen
			{
			$werte=IPS_GetInstance($instanzID);
			$instanzname=IPS_GetName($instanzID);
			$modulname=$werte["ModuleInfo"]["ModuleName"];
			//if (($this->debug) && $localDebug) echo "     ".$instanzID."   ".$modulname."\n";
			//print_r($werte); break;
			if (isset($modul[$modulname])==true) $modul[$modulname].="|".$instanzname;
			else $modul[$modulname]=$instanzname;
			if ($modulname==$findname)
				{
                if (($this->debug) && $localDebug) echo "     ".$instanzID."   ".$modulname."\n";
				$configuration=IPS_GetConfiguration($instanzID);
				$confObject=json_decode($configuration);
                if (($this->debug)  && $localDebug)
                    {
				    echo " ModuleName:".$modulname." (".$werte["ModuleInfo"]["ModuleID"].") hat Instanz:".$instanzname." \n";
				    echo " Module mit Name \"".$modulname."\" hat Instanz: ".$instanzname." (".$instanzID.")\n";
				    echo " Konfiguration :".$configuration."\n";
				    echo "IP Adresse : ".$confObject->SNMPIPAddress."\n";
                    }
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

/*
 * Die Objekteigenschaften eines SNMP Objektes-Sozusagen als Struktur definiert.
 * wird in der oberen Klasse von registerSNMPObject benötigt.
 *
 *
 */


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