<?


/*********************************************************************************************/
/*********************************************************************************************/
/*                                                                                           */
/*                              Functions                                                    */
/*                                                                                           */
/*********************************************************************************************/
/*********************************************************************************************/


function move_camPicture($verzeichnis,$WebCamWZ_LetzteBewegungID)
	{
	$count=100;
	//echo "<ol>";

	// Test, ob ein Verzeichnis angegeben wurde
	if ( is_dir ( $verzeichnis ))
		{
    	// öffnen des Verzeichnisses
    	if ( $handle = opendir($verzeichnis) )
    		{
        	/* einlesen der Verzeichnisses
			nur count mal Eintraege
        	*/
        	while ((($file = readdir($handle)) !== false) and ($count > 0))
        		{
				$dateityp=filetype( $verzeichnis.$file );
            if ($dateityp == "file")
            	{
					$count-=1;
					$unterverzeichnis=date("Ymd", filectime($verzeichnis.$file));
					$letztesfotodatumzeit=date("d.m.Y H:i", filectime($verzeichnis.$file));
            	if (is_dir($verzeichnis.$unterverzeichnis))
            		{
            		}
            	else
						{
            		mkdir($verzeichnis.$unterverzeichnis);
            		}
            	rename($verzeichnis.$file,$verzeichnis.$unterverzeichnis."\\".$file);
            	//echo "Datei: ".$verzeichnis.$unterverzeichnis."\\".$file." verschoben.\n";
		  		   SetValue($WebCamWZ_LetzteBewegungID,$letztesfotodatumzeit);
         		}
      	  	} /* Ende while */
	     	closedir($handle);
   		} /* end if dir */
		}/* ende if isdir */
	else
	   {
	   echo "Kein FTP Verzeichnis mit dem Namen \"".$verzeichnis."\" vorhanden.\n";
		}
	return(100-$count);
	}



/*********************************************************************************************/

function get_data($url) {
	$ch = curl_init($url);
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);           // return web page
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HEADER, false);                    // don't return headers
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);          // follow redirects, wichtig da die Root adresse automatisch umgeleitet wird
   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 (FM Scene 4.6.1)"); // who am i

	/*   CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => "LOOKUPADDRESS=".$argument1,  */

	$data = curl_exec($ch);

	/* Curl Debug Funktionen */
	/*
	echo "Channel :".$ch."\n";
  	$err     = curl_errno( $ch );
   $errmsg  = curl_error( $ch );
   $header  = curl_getinfo( $ch );

	echo "Fehler ".$err." von ";
	print_r($errmsg);
	echo "\n";
	echo "Header ";
	print_r($header);
	echo "\n";
	*/

	curl_close($ch);

	return $data;
}

/*********************************************************************************************/

function extractIPaddress($ip)
	{
		$parts = str_split($ip);   /* String in lauter einzelne Zeichen zerlegen */
		$first_num = -1;
		$num_loc = 0;
		foreach ($parts AS $a_char)
			{
			if (is_numeric($a_char))
				{
				$first_num = $num_loc;
				break;
				}
			$num_loc++;
			}
		if ($first_num == -1) {return "unknown";}

		/* IP adresse Stelle fuer Stelle dekodieren, Anhaltspunkt ist der Punkt */
		$result=substr($ip,$first_num,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_1=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_2=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$pos=strpos($result,".");
		$result_3=substr($result,0,$pos);
		$result=substr($result,$pos+1,20);
		//echo "Result :".$result."\n";
		$parts = str_split($result);   /* String in lauter einzelne Zeichen zerlegen */
		$last_num = -1;
		$num_loc = 0;
		foreach ($parts AS $a_char)
			{
			if (is_numeric($a_char))
				{
				$last_num = $num_loc;
				}
			$num_loc++;
			}
		$result=substr($result,0,$last_num+1);
		//echo "-------------------------> externe IP Adresse in Einzelteilen:  ".$result_1.".".$result_2.".".$result_3.".".$result."\n";
		return($result_1.".".$result_2.".".$result_3.".".$result);
	}

/*********************************************************************************************/


class parsefile
	{

	private $dataID;

	public function __construct($moduldataID)
		{
		//echo "Parsefile construct mit Data ID des aktuellen Moduls: ".$moduldataID."\n";
		$this->dataID=$moduldataID;
		}

	function parsetxtfile($verzeichnis, $name)
		{
		$ergebnis_array=array();

		echo "Data ID des aktuellen Moduls: ".$this->dataID." für den folgenden Router: ".$name."\n";
      if (($CatID=@IPS_GetCategoryIDByName($name,$this->dataID))==false)
         {
			$CatID = IPS_CreateCategory();       // Kategorie anlegen
			IPS_SetName($CatID, $name); // Kategorie benennen
			IPS_SetParent($CatID, $this->dataID); // Kategorie einsortieren unter dem Objekt mit der ID "12345"
			}
		echo "Datenkategorie für den Router ".$name."  : ".$CatID." existiert.\n";
		$handle = @fopen($verzeichnis."SystemStatisticRpm.htm", "r");
		if ($handle)
			{
			echo "Ergebnisfile gefunden.\n";
			$ok=true;
   		while ((($buffer = fgets($handle, 4096)) !== false) && $ok) /* liest bis zum Zeilenende */
				{
				/* fährt den ganzen Textblock durch, Werte die früher detektiert werden, werden ueberschrieben */
				//echo $buffer;
	      	if(preg_match('/statList/i',$buffer))
		   		{
		   		do {
		   		   if (($buffer = fgets($handle, 4096))==false) {	$ok=false; }
			      	if ((preg_match('/script/i',$buffer))==true) {	$ok=false; }
						if ($ok)
						   {
							//echo "       ".$buffer;
					  		$pos1=strpos($buffer,"\"");
							if ($pos1!=false)
								{
						  		$pos2=strpos($buffer,"\"",$pos1+1);
						  		$ipadresse=substr($buffer,$pos1+1,$pos2-$pos1-1);
						  		$ergebnis_array[$ipadresse]['IPAdresse']=substr($buffer,$pos1+1,$pos2-$pos1-1);
								$buffer=trim(substr($buffer,$pos2+1,200));
								//echo "       **IP Adresse: ".$ergebnis_array[$ipadresse]['IPAdresse']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
								//echo "       **1:".$buffer."\n";
						  		$pos1=strpos($buffer,"\"");
								if ($pos1!=false)
									{
							  		$pos2=strpos($buffer,"\"",$pos1+1);
							  		$ergebnis_array[$ipadresse]['MacAdresse']=substr($buffer,$pos1+1,$pos2-$pos1-1);
									$buffer=trim(substr($buffer,$pos2,200));
									//echo "       **MAC Adresse: ".$ergebnis_array[$ipadresse]['MacAdresse']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
									//echo "       **2:".$buffer."\n";
							  		$pos1=strpos($buffer,',');
									if ($pos1!=false)
										{
								  		$pos2=strpos($buffer,',',$pos1+1);
								  		$ergebnis_array[$ipadresse]['Packets']=(integer)substr($buffer,$pos1+1,$pos2-$pos1-1);
										$buffer=trim(substr($buffer,$pos2,200));
										//echo "       **Packets: ".$ergebnis_array[[$ipadresse]['Packets']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
										//echo "       **3:".$buffer."\n";
								  		$pos1=strpos($buffer,',');
										if ($pos1!==false)
											{
									  		$pos2=strpos($buffer,',',$pos1+1);
									  		$ergebnis_array[$ipadresse]['Bytes']=(integer)substr($buffer,$pos1+1,$pos2-$pos1-1);
											$buffer=trim(substr($buffer,$pos2,200));
											//echo "       **Bytes: ".$ergebnis_array[$ipadresse]['Bytes']." liegt zwischen ".($pos1+1)." und ".$pos2." \n";
											//echo "       **4:".$buffer."\n";
											}
										}
									}
								}
						   }
		   		   } while ($ok==true);
					}
				}
			}
	return $ergebnis_array;
	}

	} /* Ende class */


/*********************************************************************************************/


function dirToArray($dir)
	{
   $result = array();

   $cdir = scandir($dir);
   foreach ($cdir as $key => $value)
   {
      if (!in_array($value,array(".","..")))
      {
         if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
         {
            $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
         }
         else
         {
            $result[] = $value;
         }
      }
   }

   return $result;
	}

/*********************************************************************************************/

function dirToArray2($dir)
	{
   $result = array();

   $cdir = scandir($dir);
   foreach ($cdir as $key => $value)
   {
      if (!in_array($value,array(".","..")))
      {
         if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
         {
            //$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
         }
         else
         {
            $result[] = $value;
         }
      }
   }

   return $result;
	}


/*********************************************************************************************/

function tts_play($sk,$ansagetext,$ton,$modus)
 	{

  	/*
		modus == 1 ==> Sprache = on / Ton = off / Musik = play / Slider = off / Script Wait = off
		modus == 2 ==> Sprache = on / Ton = on / Musik = pause / Slider = off / Script Wait = on
		modus == 3 ==> Sprache = on / Ton = on / Musik = play  / Slider = on  / Script Wait = on
		*/

		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');

			echo 'ModuleManager Variable not set --> Create "default" ModuleManager';
			$moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
			}
		$sprachsteuerung=false;
		$knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
		$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
		foreach ($knownModules as $module=>$data)
			{
			$infos   = $moduleManager->GetModuleInfos($module);
			if (array_key_exists($module, $installedModules))
				{
				if ($module=="Sprachsteuerung") $sprachsteuerung=true;
				}
			}
		$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
		$scriptIdSprachsteuerung   = IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);

		$id_sk1_musik = IPS_GetInstanceIDByName("MP Musik", $scriptIdSprachsteuerung);
		$id_sk1_ton = IPS_GetInstanceIDByName("MP Ton", $scriptIdSprachsteuerung);
		$id_sk1_tts = IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);
		$id_sk1_musik_status = IPS_GetVariableIDByName("Status", $id_sk1_musik);
		$id_sk1_ton_status = IPS_GetVariableIDByName("Status", $id_sk1_ton);
		$id_sk1_musik_vol = IPS_GetVariableIDByName("Lautstärke", $id_sk1_musik);
	   $id_sk1_counter = CreateVariable("Counter", 1, $scriptIdSprachsteuerung , 0, "",0,null,""  );  /* 0 Boolean 1 Integer 2 Float 3 String */
		echo "\nAlle IDs :".$id_sk1_musik." ".$id_sk1_musik_status." ".$id_sk1_musik_vol." ".$id_sk1_ton." ".$id_sk1_ton_status." ".$id_sk1_tts."\n";

		$wav = array
		(
      "hinweis"  => IPS_GetKernelDir()."media/wav/hinweis.wav",
      "meldung"  => IPS_GetKernelDir()."media/wav/meldung.wav",
      "abmelden" => IPS_GetKernelDir()."media/wav/abmelden.wav",
      "aus"      => IPS_GetKernelDir()."media/wav/aus.wav",
      "coin"     => IPS_GetKernelDir()."media/wav/coin-fall.wav",
      "thunder"  => IPS_GetKernelDir()."media/wav/thunder.wav",
      "clock"    => IPS_GetKernelDir()."media/wav/clock.wav",
      "bell"     => IPS_GetKernelDir()."media/wav/bell.wav",
      "horn"     => IPS_GetKernelDir()."media/wav/horn.wav",
      "sirene"   => IPS_GetKernelDir()."media/wav/sirene.wav"
		);
		switch ($sk)
		{
			//---------------------------------------------------------------------
			case '1':

			  		$status = GetValueInteger($id_sk1_ton_status);
				   while ($status == 1)	$status = GetValueInteger($id_sk1_ton_status);

			      $sk1_counter = GetValueInteger($id_sk1_counter);
   	 			$sk1_counter++;
			      SetValueInteger($id_sk1_counter, $sk1_counter);
					if($sk1_counter >= 9) SetValueInteger($id_sk1_counter, $sk1_counter = 0);

				 	if($ton == "zeit")
 						{
						$time = time();
						// die Integer-Wandlung dient dazu eine führende Null zu beseitigen
	   				$hrs = (integer)date("H", $time);
   					$min = (integer)date("i", $time);
	   				$sec = (integer)date("s", $time);
   					// "kosmetische Behandlung" für Ein- und Mehrzahl der Minutenangabe
   					if($hrs==1) $hrs = "ein";
	   				$minuten = "Minuten";
   					if($min==1)
   						{
      					$min = "eine";
	      				$minuten = "Minute";
			   			}
   					// Zeitansage über Text-To-Speech
  	 					$ansagetext = "Die aktuelle Uhrzeit ist ". $hrs. " Uhr und ". $min. " ". $minuten;
			  	 		$ton        = "";
					 	}

			   	//Lautstärke von Musik am Anfang speichern
					$merken = $musik_vol = GetValue($id_sk1_musik_vol);
      			$musik_status 			 = GetValueInteger($id_sk1_musik_status);

					if($modus == 2)
						{
					   if($musik_status != 2)	WAC_Pause($id_sk1_musik);
						}


					if($modus == 3)
						{
						//Slider
		  			 	for ($musik_vol; $musik_vol>=1; $musik_vol--)
   					  	{
		      			WAC_SetVolume ($id_sk1_musik, $musik_vol);
      			   	$slider = 3000; //Zeit des Sliders in ms
							if($merken>0) $warten = $slider/$merken; else $warten = 0;
							IPS_Sleep($warten);
			     			}
     					}

					if($ton != "" and $modus != 1)
						{
  	   				WAC_Stop($id_sk1_ton);
		      		WAC_SetRepeat($id_sk1_ton, false);
     					WAC_ClearPlaylist($id_sk1_ton);
     					WAC_AddFile($id_sk1_ton,$wav[$ton]);
		     			WAC_Play($id_sk1_ton);
		            //solange in Schleife bleiben wie 1 = play
		   	  		sleep(1);
      			  $status = getvalue($id_sk1_ton_status);
  	   			  while ($status == 1)	$status = getvalue($id_sk1_ton_status);
			 		  }

					if($ansagetext !="")
						{
  						WAC_Stop($id_sk1_ton);
			      	WAC_SetRepeat($id_sk1_ton, false);
			         WAC_ClearPlaylist($id_sk1_ton);
   			      $status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
						if (!$status) echo "Error";
		     			WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
		     			echo "---------------------------".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav\n";
						WAC_Play($id_sk1_ton);
						}

					//Script solange anghalten wie Sprachausgabe läuft
					if($modus != 1)
						{
			   		sleep(1);
						$status = GetValueInteger($id_sk1_ton_status);
   	  				while ($status == 1)	$status = GetValueInteger($id_sk1_ton_status);
			   		}

			 		if($modus == 3)
						{
			   		$musik_vol = GetValueInteger($id_sk1_musik_vol);
		   			for ($musik_vol=1; $musik_vol<=$merken; $musik_vol++)
		      			{
				         WAC_SetVolume ($id_sk1_musik, $musik_vol);
      	   		   $slider = 3000; //Zeit des Sliders in ms
							if($merken>0) $warten = $slider/$merken; else $warten = 0;
							IPS_Sleep($warten);
      					}
      				}
					if($modus == 2)
						{
				   	if($musik_status != 2)	WAC_Pause($id_sk1_musik);
				   	}
					break;

			//---------------------------------------------------------------------

			//Hier können weitere Soundkarten eingefügt werden
			//case '2':
			//entsprechende Werte bitte anpassen

		}  //end switch
 	}   //end function


/*********************************************************************************************/




?>
