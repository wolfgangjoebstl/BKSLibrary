<?

	Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

	IPSUtils_Include ("Guthabensteuerung_Configuration.inc.php","IPSLibrary::config::modules::Guthabensteuerung");

	$GuthabenConfig = get_GuthabenConfiguration();
	$GuthabenAllgConfig = get_GuthabenAllgemeinConfig();
	
	echo "Verzeichnis für Macros    :".$GuthabenAllgConfig["MacroDirectory"]."\n";
	echo "Verzeichnis für Ergebnisse:".$GuthabenAllgConfig["DownloadDirectory"]."\n\n";
	/* "C:/Users/Wolfgang/Documents/iMacros/Downloads/ */

	//print_r($GuthabenConfig);
	$ergebnis="";

	foreach ($GuthabenConfig as $TelNummer)
		{
		$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

		$phone1ID = CreateVariableByName($parentid, "Phone_".$TelNummer["NUMMER"], 3);
		$ergebnis1=parsetxtfile($GuthabenAllgConfig["DownloadDirectory"],$TelNummer["NUMMER"]);
		SetValue($phone1ID,$ergebnis1);
		$ergebnis.=$ergebnis1."\n";
		}

if ($_IPS['SENDER']=="Execute")
	   {
	   echo $ergebnis;
	   }



/**************************************************************************************************/

function parsetxtfile($verzeichnis, $nummer)
	{

	//$startdatenguthaben=7;
	$startdatenguthaben=0;
	$parentid  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Guthabensteuerung');

	$handle = @fopen($verzeichnis."/report_dreiat_".$nummer.".txt", "r");
	$result1="";$result2="";$result3="";$result4="";$result5="";$result6="";
	$result4g="";$result4v="";$result4f="";
	if ($handle)
		{
   	while (($buffer = fgets($handle, 4096)) !== false) /* liest bis zum Zeilenende */
			{
      	//echo $buffer;
      	if(preg_match('/Willkommen/i',$buffer))
	   		{
	   		$pos=strpos($buffer,"kommen");
				if ($pos!=false)
					{
					$result1=trim(substr($buffer,$pos+7,200));
					}
				//echo "*********Ausgabe User : ".$result1."\n<br>";
				}
      	if(preg_match('/660/i',$buffer))
	   		{
	   		$result2=trim($buffer);
	   		//echo "*********Ausgabe Nummer : ".$result2."\n<br>";
				}
      	if(preg_match('/Aktualisierung/i',$buffer))
	   		{
	   		$pos=strpos($buffer,"Aktualisierung");
				if ($pos!=false)
					{
					$result3=trim(substr($buffer,$pos+16,200));
					}
				//echo "*********Wert von : ".$result3."\n<br>";
				}
			//echo "-----------------------------------------\n";
			//echo $buffer;

      	if(preg_match('/MB/i',$buffer) and ($result4g==""))
      	//if (preg_match('/MB/i',$buffer))
	   		{
				$result4g=trim(substr($buffer,$startdatenguthaben,200));
	   		//echo "*********Datenmenge : ".$result4g."\n<br>";
				}

			if (preg_match('/MB verbr/i',$buffer))
	   		{
				$result4v=trim(substr($buffer,$startdatenguthaben,200));
	   		//echo "*********verbraucht : ".$result4v."\n<br>";
				}

			if (preg_match('/MB frei/i',$buffer))
	   		{
				$result4f=trim(substr($buffer,$startdatenguthaben,200));
	   		//echo "*********frei : ".$result4f."\n<br>";
				}

			if (preg_match('/bis:/i',$buffer))
	   		{
				$result7=trim(substr($buffer,12,200));
	   		//echo "*********Gültig bis : ".$result7."\n<br>";
				}


      	if (preg_match('/haben:/i',$buffer))
	   		{
	   		$pos=strpos($buffer,"haben:");
		  		$Ende=strpos($buffer,"€");
				if ($pos!=false)
					{
					$pos=$pos+6;
					$result5=trim(substr($buffer,$pos,$Ende-$pos));
					}
				//echo "*********Geldguthaben :".$result5."\n<br>";
    			}
	    	}
    	 //$ergebnis="User:".$result1." Nummer:".$result2." Status:".$result4." Wert vom:".$result3." Guthaben:".$result5."\n";
 		 $phone1ID = CreateVariableByName($parentid, "Phone_".$nummer, 3);
  		 $phone_Summ_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Summary", 3);
    	 $phone_User_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_User", 3);
     	 //$phone_Status_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Status", 3);
     	 $phone_Date_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Date", 3);
     	 $phone_unchangedDate_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_unchangedDate", 3);
     	 $phone_Bonus_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Bonus", 3);
     	 $phone_Volume_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Volume", 2);
     	 $phone_nCost_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Cost", 2);
     	 $phone_nLoad_ID = CreateVariableByName($phone1ID, "Phone_".$nummer."_Load", 2);
    	 $phone_Cost_ID = CreateVariableByName($parentid, "Phone_Cost", 2);
     	 $phone_Load_ID = CreateVariableByName($parentid, "Phone_Load", 2);
     	 $phone_CL_Change_ID = CreateVariableByName($parentid, "Phone_CL_Change", 2);
		 //$ergebnis="User:".$result1." Status:".$result4." Guthaben:".$result5." Euro\n";
		 SetValue($phone_User_ID,$result1);
		 //SetValue($phone_Status_ID,$result4);   /* die eigentlich interessante Information */
		 //echo ":::::".$result4."::::::\n";
 		 SetValue($phone_Date_ID,$result3);
 		 $old_cost=(float)GetValue($phone_Bonus_ID);
 		 $new_cost=(float)$result5;
	    SetValue($phone_CL_Change_ID,$new_cost-$old_cost);
 		 if ($new_cost < $old_cost)
 		   {
 		   SetValue($phone_Cost_ID, GetValue($phone_Cost_ID)+$old_cost-$new_cost);
 		   SetValue($phone_nCost_ID, GetValue($phone_nCost_ID)+$old_cost-$new_cost);
 		   SetValue($phone_unchangedDate_ID,date("m.d.Y"));
 		   }
 		 if ($new_cost > $old_cost)
 		   {
 		   SetValue($phone_Load_ID, GetValue($phone_Cost_ID)-$old_cost+$new_cost);
 		   SetValue($phone_nLoad_ID, GetValue($phone_nLoad_ID)-$old_cost+$new_cost);
 		   SetValue($phone_unchangedDate_ID,date("m.d.Y"));
 		   }
  		 SetValue($phone_Bonus_ID,$result5);

  		 if ($result4!="")
  		   {
	  		 $Anfang=strpos($result4,"verbraucht")+10;
  			 $Ende=strpos($result4,"frei");
  			 $result6=trim(substr($result4,($Anfang),($Ende-$Anfang)));

	  		 $Anfang=strpos($result4,"bis:")+5;
  			 $result7=trim(substr($result4,($Anfang),20));
			}

  		 if ($result4g!="")
			{
			//$result6=" von ".$result4g." wurden ".$result4v." und daher sind  ".$result4f.".";
			$result6=" von ".$result4g." sind ".$result4f;
			$Ende=strpos($result4,"MB");
			$restvolumen=(float)trim(substr($result4f,0,($Ende-1)));
		   //echo "Restvolumen ist : ".$restvolumen." MB \n";
		   SetValue($phone_Volume_ID,$restvolumen);
			}


  		 //echo $result1.":".$result6."bis:".$result7.".\n";
  		 if ($result6=="")
			{
		   $ergebnis=$nummer." (".$result1.") Guthaben:".$result5." Euro";
			}
		 else
		   {
		   $ergebnis=$nummer." (".$result1.")".$result6." bis ".$result7." Guthaben:".$result5." Euro";
			}

   	 if (!feof($handle))
		 	{
      	$ergebnis="Fehler: unerwarteter fgets() Fehlschlag\n";
	    	}
   	fclose($handle);
		}
	else
		{
      $ergebnis="Handle nicht definiert\n";
		}
	//$ergebnis.=$result4g." ".$result4v." ".$result4f;
	SetValue($phone_Summ_ID,$ergebnis);
	return $ergebnis;
	}



?>
