<?
include "HM_Heizung_Konfig.ips.php";
include "HM_Heizung_Funktionen.ips.php";
include "hmxml.inc.php";

//******************************************************************************

If ($_IPS['SENDER'] == "Execute")  // manuelles Ausführen zur Instalation
	{
	Foreach ($Zimmer as $key=> $Raum)
	      {
			If ($HM_Typ[$key]=="HM-CC-TC")
			   {
			   Raumsteuerung_HM_CC_TC($key);
			   }
			If ($HM_Typ[$key]=="HM-CC-RT-DN")
			   {
			   Raumsteuerung_HM_CC_RT_DN($key);
			   }
			If ($HM_Typ[$key]=="HM-TC-IT-WM-W-EU")
			   {
            Raumsteuerung_HM_TC_IT_WM_W_EU($key);
            }
			}
	}

//*****************************************************************************

If ($_IPS['SENDER'] != "Execute") // Ausführung erfolgt über WebFrond oder Tigger
	{
	$gefunden="";
	$HM_Raum=  IPS_GetObject(IPS_GetParent($_IPS['VARIABLE']))['ObjectName'];
	Foreach ($Zimmer as $key=> $Raum)
	  {
	  If ($Raum== $HM_Raum)
	     {
        $gefunden="OK";
		  break;
	     }
	  }

	If ($gefunden== "OK")
	   {
	   If ($HM_Typ[$key]=="HM-CC-TC")
			   {
			   Raumsteuerung_HM_CC_TC($key);
			   }
		If ($HM_Typ[$key]=="HM-CC-RT-DN")
			   {
			   Raumsteuerung_HM_CC_RT_DN($key);
			   }
		If ($HM_Typ[$key]=="HM-TC-IT-WM-W-EU")
			   {
			   Raumsteuerung_HM_TC_IT_WM_W_EU($key);
			   }

		/****************************************************************************
	   	                    Aktionen Auswerten
		*****************************************************************************/

		//**********************************
		// neues Ventil-Thermostat
		//**********************************
		IF ($HM_Typ[$key]=="HM-CC-RT-DN")
			{
			Switch ($_IPS['SENDER'])
				{

				Case 'WebFront':
     			  // ****************************
	           // Aktion für Variable Modus
	           // ****************************
				  if (@$_IPS['VARIABLE'] == $HM_Modus_ID)
						{
						Switch ($_IPS['VALUE'])
							   {
							   case -1:
					      	    HM_WriteValueFloat($IPS_HM_DeviceID[$key],"MANU_MODE", 100);
					         	 break;
							   case 0:
						          HM_WriteValueBoolean($IPS_HM_DeviceID[$key],"AUTO_MODE", true);
							       break;
							   case 1:
							       HM_WriteValueFloat($IPS_HM_DeviceID[$key],"MANU_MODE", GetValue(IPS_GetObjectIDByIdent ("SET_TEMPERATURE", $IPS_HM_DeviceID[$key])));
							       break;
			   				case 3:
							       HM_WriteValueBoolean($IPS_HM_DeviceID[$key],"BOOST_MODE", true);
							       break;
								}

						SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
   			      break;
						}

				 //********************************************************************
	   	    // wird Temperatur im Webfrond geändert  ... nur bei neuem Thermostat
				 //********************************************************************

				 //if (@$_IPS['VARIABLE'] == IPS_GetObjectIDByIdent ("SET_TEMPERATURE", $IPS_HM_DeviceID[$key]))
				 //    {
				//	  HM_WriteValueFloat($IPS_HM_DeviceID[$key],"SET_TEMPERATURE", $_IPS['VALUE']);
				//	  break;
				//	  }

				 //**********************************
				 // Profil auswählen / setzen
				 //**********************************
					if ($_IPS['VARIABLE'] == $HM_Praesenz_Profil_Auswahl_ID)
					   {
						$tmp=GetValue($_IPS['VARIABLE']);
						SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
						$WP=unserialize(GetValue($HM_Heizung_Wochenprofil_ID));
						if (@array_key_exists(GetValueFormatted($HM_Praesenz_Profil_Auswahl_ID ),$WP ))
							{
							Set_Praesenz_Profil($Zimmer[$key], GetValueFormatted($HM_Praesenz_Profil_Auswahl_ID ));
							}
							else
							{
							SetValue($_IPS['VARIABLE'],$tmp);
							}
						break;
					   }

				 //**********************************
				 // Profil in IPS abspeichern
				 //**********************************
					if ($_IPS['VARIABLE'] == $HM_Wochenprofil_speichern_ID)
					   {
                  switch (GetValue($_IPS['VARIABLE']))
							 {
							 case 1:
							     SetValue($_IPS['VARIABLE'],0); // Speicherbutton sperren
							     break;

                      default:
							     SetValue($_IPS['VARIABLE'],1); // Speicherbutton freigeben
							 }
						 break;
					   }



		  		 //**********************************
	 			 // Aktion für Wochenprofil auslesen
  				 //**********************************
               if ($_IPS['VARIABLE'] == $HM_Wochenprofil_auslesen_ID)
					   {
					   $tempProfile = HMXML_getTempProfile($IPS_HM_DeviceID[$key],false, false);
					   SetValue($HM_Heizung_WochenProfil_Anzeige_html_ID, HM_WochenTempProfil_html(serialize($tempProfile)));

 					   $Profile=unserialize(GetValue($HM_Heizung_Wochenprofil_ID));
					   $gefunden="";
					   $i=0;
					   if (is_array($Profile))
					      {
							Foreach($Profile as $Profil)
							   {
						   	If ($Profil == $tempProfile)
									{
									$gefunden="OK";
									break;
									}
         		         $i++;
               			}

								If ($gefunden== "OK")
			                  {
      			            SetValue($HM_Praesenz_Profil_Auswahl_ID, $i);
         	   		      //IPS_LOGMESSAGE("HM","OK ....".$i);
									}
		         	         else
      		      	      {
            		   	   SetValue($HM_Praesenz_Profil_Auswahl_ID, -1); // das im HM gepseicherte Profil ist  unbekannt
			           			}
							 }
							else
							 {
							 SetValue($HM_Praesenz_Profil_Auswahl_ID, -1);  // IPS kennt (noch) keine Profile für das HM
							 }
						 
						 if (GetValue($HM_Wochenprofil_speichern_ID)==1)  /// ausgelesenes Profil wird gespeichert in Profilvariable "Normal"
   	                  {
								SetValue($HM_Praesenz_Profil_Auswahl_ID, 0);
								$tmp[GetValueFormatted($HM_Praesenz_Profil_Auswahl_ID )]=$tempProfile;
            	         Setvalue($HM_Heizung_Wochenprofil_ID, serialize($tmp));
            	         SetValue($HM_Wochenprofil_speichern_ID,0 ); // Speicherbutton sperren
               	      }
						 }
				break;


			 Case 'Variable':
 				  //**********************************
				  //Aktion für Anzeige Wochenprofil
				  //**********************************
				  if ($_IPS['VARIABLE'] == $HM_Heizung_Wochenprofil_ID)
					  {


					  }
			     break;
			 }
		  }


		//**********************************
		// neues WandThermostat
		//**********************************
		IF ($HM_Typ[$key]=="HM-TC-IT-WM-W-EU")
			{
			Switch ($_IPS['SENDER'])
				{

				Case 'WebFront':
     			  // ****************************
	           // Aktion für Variable Modus
	           // ****************************
				  if (@$_IPS['VARIABLE'] == $HM_Modus_ID)
						{
						Switch ($_IPS['VALUE'])
							   {
							   case -1:
					      	    HM_WriteValueFloat($IPS_HM_DeviceID[$key],"MANU_MODE", 100);
					         	 break;
							   case 0:
						          HM_WriteValueBoolean($IPS_HM_DeviceID[$key],"AUTO_MODE", true);
							       break;
							   case 1:
							       HM_WriteValueFloat($IPS_HM_DeviceID[$key],"MANU_MODE", GetValue(IPS_GetObjectIDByIdent ("SET_TEMPERATURE", $IPS_HM_DeviceID[$key])));
							       break;
			   				case 3:
							       HM_WriteValueBoolean($IPS_HM_DeviceID[$key],"BOOST_MODE", true);
							       break;
								}

						SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);
   			      break;
						}

       		   //**********************************
 				   // Profil auswählen / setzen
				   //**********************************
					if ($_IPS['VARIABLE'] == $HM_Praesenz_Profil_Auswahl_ID)
					   {
						SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
						
                  HMXML_setParamInt($IPS_HM_DeviceID[$key],"WEEK_PROGRAM_POINTER", $_IPS['VALUE']);

						$tempProfile = HMXML_getTempProfile($IPS_HM_DeviceID[$key],false, false);
						//print_r($tempProfile);
						$P[0]=$tempProfile["P1"];
						$P[1]=$tempProfile["P2"];
						$P[2]=$tempProfile["P3"];
						SetValue($HM_Heizung_WochenProfil_Anzeige_html_ID, HM_WochenTempProfil_html(serialize($P[GetValue($HM_Praesenz_Profil_Auswahl_ID)])));


						$Profile=unserialize(GetValue($HM_Heizung_Wochenprofil_ID));
					   $gefunden=0;
					   $i=0;

						if (is_array($Profile))
					      {
							If ($Profile[array_keys($Profile)[0]] == $P[0] and
                         $Profile[array_keys($Profile)[1]] == $P[1] and
                         $Profile[array_keys($Profile)[2]] == $P[2])    // stimmen die in IPS gespeicherten Profile mit dem aus dem HM Device überein ?
                         
                         {
								 SetValue($HM_Wochenprofil_speichern_ID,0); // Speicherbutton sperren
								 }
								else
								 {
                         SetValue($HM_Wochenprofil_speichern_ID,1); // Speicherbutton freigeben
								 }
							 }
							else
							 {
							 SetValue($HM_Wochenprofil_speichern_ID,1); // Speicherbutton freigeben
							 }
						  break;
					    }

			    //**********************************
				 // Profil in IPS abspeichern
 				 //**********************************
				 if ($_IPS['VARIABLE'] == $HM_Wochenprofil_speichern_ID)
				     {
						if (GetValue($HM_Wochenprofil_speichern_ID)==1)  /// ist der Speicherbutton freigegeben ?
   	                  {
								$tempProfile = HMXML_getTempProfile($IPS_HM_DeviceID[$key],false, false);
								//print_r($tempProfile);
								$P[0]=$tempProfile["P1"];
								$P[1]=$tempProfile["P2"];
								$P[2]=$tempProfile["P3"];

                        $VP=IPS_GetVariable($HM_Praesenz_Profil_Auswahl_ID)['VariableCustomProfile'];
								$Profile=IPS_GetVariableProfile($VP)['Associations'];

								$PN[0]= $Profile[0]['Name'];
                        $PN[1]= $Profile[1]['Name'];
                        $PN[2]= $Profile[2]['Name'];
                        
                        $tmp[$PN[0]]=$P[0];
                        $tmp[$PN[1]]=$P[1];
                        $tmp[$PN[2]]=$P[2];
                        
            	         Setvalue($HM_Heizung_Wochenprofil_ID, serialize($tmp));
            	         SetValue($HM_Wochenprofil_speichern_ID,0 ); // Speicherbutton sperren
								}
 				     break;
				     }


  				}
			}

		//**********************************
		// altes Thermostat
		//**********************************
		IF ($HM_Typ[$key]=="HM-CC-TC")
			{
			Switch ($_IPS['SENDER'])
				{

		  	    Case 'WebFront':
					//**********************************
					// Aktion für Variable Modus
					//**********************************
					if (@$_IPS['VARIABLE'] == $HM_Modus_ID)
						{
						IF ($_IPS['VALUE'] == -1)
			   			{
							HMXML_setTCMode($IPS_HM_DeviceID[$key], 0);
							HM_WriteValueFloat($IPS_HM_DeviceID[$key], "SETPOINT", 100.00);
							If (GetValue($_IPS['VARIABLE'])== 1)
					  			{
								SetValue($_IPS['VARIABLE'],-1);
								}
						   }
						  else
							{
							HMXML_setTCMode($IPS_HM_DeviceID[$key], $_IPS['VALUE']);
							// $IPS_DeviceID: IPS Instance ID
							// $nMode: INTEGER - Mode 0 = MANUAL, 1 = AUTO, 2=CENTRAL, 3 = PARTY
							if (HMXML_getTCMode($IPS_HM_DeviceID[$key])==$_IPS['VALUE'])
								{
								SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
								}
						 	}
	      	      break;
				  	   }
					//**********************************
					// Profil auswählen / setzen
					//**********************************
				  if ($_IPS['VARIABLE'] == $HM_Praesenz_Profil_Auswahl_ID)
					   {
						$tmp=GetValue($_IPS['VARIABLE']);
						SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
						$WP=unserialize(GetValue($HM_Heizung_Wochenprofil_ID));
						if (@array_key_exists(GetValueFormatted($HM_Praesenz_Profil_Auswahl_ID ),$WP ))							{
							Set_Praesenz_Profil($Zimmer[$key], GetValueFormatted($HM_Praesenz_Profil_Auswahl_ID ));
							}
							else
							{
							SetValue($_IPS['VARIABLE'],$tmp);
							}
						break;
					   }

				  //**********************************
				  // Profil in IPS abspeichern
				  //**********************************
					if ($_IPS['VARIABLE'] == $HM_Wochenprofil_speichern_ID)
					   {
                  switch (GetValue($_IPS['VARIABLE']))
							 {
							 case 1:
							     SetValue($_IPS['VARIABLE'],0); // Speicherbutton sperren
							     break;

                      default:
							     SetValue($_IPS['VARIABLE'],1); // Speicherbutton freigeben
							 }
						 break;
					   }


					//**********************************
					// Aktion für Wochenprofil auslesen
				   //**********************************
               if ($_IPS['VARIABLE'] == $HM_Wochenprofil_auslesen_ID)
					   {
					   $tempProfile = HMXML_getTempProfile($IPS_HM_DeviceID[$key],false, false);
					   SetValue($HM_Heizung_WochenProfil_Anzeige_html_ID, HM_WochenTempProfil_html(serialize($tempProfile)));

 					   $Profile=unserialize(GetValue($HM_Heizung_Wochenprofil_ID));
					   $gefunden="";
					   $i=0;
					   if (is_array($Profile))
					      {
							Foreach($Profile as $Profil)
							   {
						   	If ($Profil == $tempProfile)
									{
									$gefunden="OK";
									break;
									}
         		         $i++;
               			}

								If ($gefunden== "OK")
			                  {
      			            SetValue($HM_Praesenz_Profil_Auswahl_ID, $i);
         	   		      //IPS_LOGMESSAGE("HM","OK ....".$i);
									}
		         	         else
      		      	      {
            		   	   SetValue($HM_Praesenz_Profil_Auswahl_ID, -1); // das im HM gepseicherte Profil ist  unbekannt
			           			}
							 }
							else
							 {
							 SetValue($HM_Praesenz_Profil_Auswahl_ID, -1);  // IPS kennt (noch) keine Profile für das HM
							 }

						 if (GetValue($HM_Wochenprofil_speichern_ID)==1)  /// ausgelesenes Profil wird gespeichert in Profilvariable "Normal"
   	                  {
								SetValue($HM_Praesenz_Profil_Auswahl_ID, 0);
								$tmp[GetValueFormatted($HM_Praesenz_Profil_Auswahl_ID )]=$tempProfile;
            	         Setvalue($HM_Heizung_Wochenprofil_ID,serialize($tmp));
              	         SetValue($HM_Wochenprofil_speichern_ID,0 ); // Speicherbutton sperren
               	      }
						 }
				break;


			  Case 'Variable':

				  //**********************************
				  //Aktion für AnNzeige Wochenprofil ... für neues und altes Thermostat
  				  //**********************************

				  if ($_IPS['VARIABLE'] == $HM_Heizung_Wochenprofil_ID)
					  {


					  }
				 break;
				}
			 }
		}
	}




?>