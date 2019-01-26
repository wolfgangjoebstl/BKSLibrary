<?


/********************************************************************************************
 *
 * generische Routine, funktioniert nur wenn Sprachsteuerungs Modul auch installiert
 *
 * selbe Routine auch in OperationCenter Library, aber diese erscheint neueren Datums
 *
 *
 *  sk    soundkarte   es gibt immer nur 1, andere kann man implementieren
 *
 * 	modus == 1 ==> Sprache = on / Ton = off / Musik = play / Slider = off / Script Wait = off
 * 	modus == 2 ==> Sprache = on / Ton = on / Musik = pause / Slider = off / Script Wait = on
 * 	modus == 3 ==> Sprache = on / Ton = on / Musik = play  / Slider = on  / Script Wait = on
 *
 * zum Beispiel  tts_play(1,$speak,'',2);  // Soundkarte 1, mit diesem Ansagetext, kein Ton, Modus 2
 * 
 ***********************************************/

	function tts_play($sk,$ansagetext,$ton,$modus)
 		{
		$tts_status=true;
		echo "Aufgerufen als Teil der Library der Sprachsteuerung.\n";
		//echo "tts_play, Textausgabe, Soundkarte : ".$sk.".\n";
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager))
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Sprachsteuerung',$repository);
			}
		$sprachsteuerung=false; $remote=false;
		$knownModules     = $moduleManager->VersionHandler()->GetKnownModules();
		$installedModules = $moduleManager->VersionHandler()->GetInstalledModules();
		foreach ($knownModules as $module=>$data)
			{
			$infos   = $moduleManager->GetModuleInfos($module);
			if (array_key_exists($module, $installedModules))
				{
				if ($module=="Sprachsteuerung") 
					{
					$sprachsteuerung=true;
					IPSUtils_Include ("Sprachsteuerung_Configuration.inc.php","IPSLibrary::config::modules::Sprachsteuerung");					
					$CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
					$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
					$config=Sprachsteuerung_Configuration();
					if ( (isset($config["RemoteAddress"])) && (isset($config["ScriptID"])) ) { $remote=true; $url=$config["RemoteAddress"]; $oid=$config["ScriptID"]; }
					
					$object_data= new ipsobject($CategoryIdData);
					$object_app= new ipsobject($CategoryIdApp);

					$NachrichtenID = $object_data->osearch("Nachricht");
					$NachrichtenScriptID  = $object_app->osearch("Nachricht");
					echo "Nachrichten gibt es auch : ".$NachrichtenID ."  (".IPS_GetName($NachrichtenID).")   ".$NachrichtenScriptID." \n";

					if (isset($NachrichtenScriptID))
						{
						$object3= new ipsobject($NachrichtenID);
						$NachrichtenInputID=$object3->osearch("Input");
						$log_Sprachsteuerung=new Logging("C:\Scripts\Sprachsteuerung\Log_Sprachsteuerung.csv",$NachrichtenInputID);
						$log_Sprachsteuerung->LogNachrichten("Sprachsteuerung: Ausgabe von \"".$ansagetext."\"");
						}
					}
				}
			}
		$CategoryIdApp      = $moduleManager->GetModuleCategoryID('app');
		$scriptIdSprachsteuerung   = @IPS_GetScriptIDByName('Sprachsteuerung', $CategoryIdApp);
		if ($scriptIdSprachsteuerung==false) $sprachsteuerung=false;
		
		/* Sprachausgabe nur dann durchführen wenn IPS Modul Sprachsteuerung installiert ist und das Script Sprachsteuerung vorhanden ist. */
		if ( ($sprachsteuerung==true) && ($remote==false) )
			{
			echo "Sprache lokal ausgeben.\n";
			$id_sk1_musik = IPS_GetInstanceIDByName("MP Musik", $scriptIdSprachsteuerung);
			$id_sk1_ton = IPS_GetInstanceIDByName("MP Ton", $scriptIdSprachsteuerung);
			$id_sk1_tts = IPS_GetInstanceIDByName("Text to Speach", $scriptIdSprachsteuerung);
			$id_sk1_musik_status = IPS_GetVariableIDByName("Status", $id_sk1_musik);
			$id_sk1_musik_vol = IPS_GetVariableIDByName("Lautstärke", $id_sk1_musik);
			$id_sk1_ton_status = IPS_GetVariableIDByName("Status", $id_sk1_ton);
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
			switch ($sk)   /* Switch unterschiedliche Routinen anhand der Spoundkarten ID, meistens eh nur eine */
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
					$ton_status           = GetValueInteger($id_sk1_ton_status);

					//echo "Modus : ".$modus."\n";
					if($modus == 2)  /* Musik vom Mp3 Player pausieren */
						{
						if($musik_status == 1)
							{
							/* Wenn Musik Wiedergabe auf Play steht dann auf Pause druecken */
							WAC_Pause($id_sk1_musik);
							}
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
						if($ton_status == 1)
							{
							/* Wenn Ton Wiedergabe auf Play steht dann auf Stopp druecken */
							WAC_Stop($id_sk1_ton);
							WAC_SetRepeat($id_sk1_ton, false);
							WAC_ClearPlaylist($id_sk1_ton);
							}
						if (isset($wav[$ton])==true)
							{
							WAC_AddFile($id_sk1_ton,$wav[$ton]);
							echo "Check SoundID: ".$id_sk1_ton." Ton: ".$wav[$ton]." Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n";
							while (@WAC_Next($id_sk1_ton)==true) { echo " Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n"; }
							WAC_Play($id_sk1_ton);
							//solange in Schleife bleiben wie 1 = play
							sleep(1);
							$status = getvalue($id_sk1_ton_status);
							while ($status == 1)	$status = getvalue($id_sk1_ton_status);
							}
						}

					/* hier die Sprachausgabe vorbereiten */
					if($ansagetext !="")
						{
						if($ton_status == 1)
							{
							/* Wenn Ton Wiedergabe auf Play steht dann auf Stopp druecken */
							WAC_Stop($id_sk1_ton);
							WAC_SetRepeat($id_sk1_ton, false);
							WAC_ClearPlaylist($id_sk1_ton);
							echo "Tonwiedergabe auf Stopp stellen \n";
							}
						$status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
						if (!$status) { echo "Error Erzeugung Sprachfile gescheitert.\n"; $tts_status=false; }
						WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
						echo "Check SoundID: ".$id_sk1_ton." Ton: ".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav  Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n";
						while (@WAC_Next($id_sk1_ton)==true) { echo " Playlistposition : ".WAC_GetPlaylistPosition($id_sk1_ton)."/".WAC_GetPlaylistLength($id_sk1_ton)."\n"; }
						$status=@WAC_Play($id_sk1_ton);
						if (!$status) { echo "Fehler WAC_play nicht ausführbar.\n"; $tts_status=false; }
						
  						WAC_Stop($id_sk1_ton);
						WAC_SetRepeat($id_sk1_ton, false);
						WAC_ClearPlaylist($id_sk1_ton);
						$status=TTS_GenerateFile($id_sk1_tts, $ansagetext, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav",39);
						if (!$status) echo "Error";
		     			WAC_AddFile($id_sk1_ton, IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav");
		     			echo "---------------------------".IPS_GetKernelDir()."media/wav/sprache_sk1_" . $sk1_counter . ".wav\n";
						WAC_Play($id_sk1_ton);						
						}

					//Script solange angehalten wie Sprachausgabe läuft
					if($modus != 1)
						{
						if (GetValueInteger($id_sk1_ton_status) == 1) echo "Noch warten bis Status des Ton Moduls ungleich 1 :";
						while (GetValueInteger($id_sk1_ton_status) == 1)
							{
							sleep(1);
							echo ".";
							}
						echo "\nLänge der Playliste : ".WAC_GetPlaylistLength($id_sk1_ton)." Position : ".WAC_GetPlaylistPosition($id_sk1_ton)."\n";
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
						if($musik_status == 1)
							{
							/* Wenn Musik Wiedergabe auf Play steht dann auf Pause druecken */
							WAC_Pause($id_sk1_musik);
							echo "Musikwiedergabe auf Pause stellen \n";
							}
						}
					break;

				//---------------------------------------------------------------------

				//Hier können weitere Soundkarten eingefügt werden
				case '2':
					//entsprechende Werte bitte anpassen
					echo "Fehler: Soundkarte 2 nicht definiert.\n";
					break;
				default:
				break;
				}  //end switch
			} // endif sprachsteuerungs Modul richtig konfiguriert
		if ( ($sprachsteuerung==true) && ($remote==true) )
			{
			echo "Sprache remote auf ".$url." ausgeben. verwende Script mit OID : ".$oid."\n";
			$rpc = new JSONRPC($url);
			$monitor=array("Text" => $ansagetext);
			$rpc->IPS_RunScriptEx($oid,$monitor);
			}

		return ($tts_status);
 		}   //end function


/*********************************************************************************************/




?>