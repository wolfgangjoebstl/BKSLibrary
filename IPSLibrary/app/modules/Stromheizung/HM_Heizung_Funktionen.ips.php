<?php
Function HM_WochenTempProfil_html($ID_tempProfile, $day_hl=99)
	{

	// Diese Funktion Wandelt ein mittels HMXML_getTempProfile ausgelesenes Temperatur Profil eines
	// HM Wandthermostaten Zwecks Anzeige in eine Web-Tabelle um

	//$ID_tempProfile=26666 ;
	//$ID_html=56170 ;

	$dayArray = array("MONDAY","TUESDAY","WEDNESDAY","THURSDAY","FRIDAY","SATURDAY","SUNDAY");
	$dayArray_germ = array("Mo","Di","Mi","Do","Fr","Sa","So");

	if (is_int($ID_tempProfile) and strlen($ID_tempProfile)==5) // nur wenn $tempProfile eine ID ist
	   {
 		$tempProfile=unserialize(Getvalue($ID_tempProfile));
		}
		else
		{
		$tempProfile=unserialize($ID_tempProfile);
		}
		
	$bgcolor[1]='#0101DF'; // #3399CC'; // kalt <= 15
	$bgcolor[2]='#01DF3A'; // #33CC66'; // Absenktemperatur grün <= 17
	$bgcolor[3]='#D7DF01'; // #FFCC66'; // Komforttemperatur <=23
	$bgcolor[4]='#DF0101'; // #FF3366'; // heiß >23

	$bgcolor_hl[1]='#08088A'; // #339966'; // kalt <= 15
	$bgcolor_hl[2]='#088A29'; // #009966'; // Absenktemperatur grün <= 17
	$bgcolor_hl[3]='#868A08'; // #FF9966'; // Komforttemperatur <=23
	$bgcolor_hl[4]='#8A0808'; // #FF33FF'; // heiß >23



	//*********************
	// tempProfile zerlegen
	//*********************


	Foreach ($dayArray as $daykey => $day)
		{
		$time1='00:00';
		Foreach ($tempProfile[$dayArray[$daykey]]['EndTimes'] as $time2)
			{
			$times[$day][]=(strtotime("01.01.2001 ". $time2)-strtotime("01.01.2001 ". $time1))/60; // Speicherslots des HM WT in min
			$time1=$time2;
			}
			Foreach ($tempProfile[$dayArray[$daykey]]['Values'] as $Value)
				{
				If ($Value<=15)  // kalt
					{
					$temp[$day][]=1;
					}
					elseif ($Value <=17)  // Absenktemperatur
						{
						$temp[$day][]=2;
						}
						elseif ($Value <=22)  // Komforttemperatur
							{
							$temp[$day][]=3;
							}
							else   // heiß
								{
								$temp[$day][]=4;
								}
					}
		}

	//print_r ($times[0]);
	//print_r ($temp[0]);

	//************************
	// html Tabelle generieren
	//************************

	$str = "<table border=0 cellpadding=0 cellspacing=0 width=100% align='center'>
			 <tr height=35 align='right'> <td align='left' width=4%> </td>";

	for($i=1; $i <= 24; $i++)
		{
	   $str .="<td colspan=12 width=4% >$i</td>";
		}
	$str .=" </tr>";


	Foreach ($dayArray as $daykey => $day)
		{
		$str .="<tr height=25> <td>". $dayArray_germ[$daykey] ."</td>";
		Foreach ($times[$day] as $key => $time)
			{
			If ($day_hl<>$daykey) // wurde Wochentag mit übergeben (0=Mo, 1=Di ... 99=kein Wochentag vorgegeben)
			   {
			   $str .="<td colspan=". ($time/5) ." bgcolor=". $bgcolor[$temp[$day][$key]] ."> </td>";
				}
			  else
				{
				$str .="<td colspan=". ($time/5) ." bgcolor=". $bgcolor_hl[$temp[$day][$key]] ."> </td>";
				}
			}
		$str .="</tr>";
		}
   $str .="</table> <br>";
   
   $str .= "<table border=0 cellpadding=3 cellspacing=5 width=100% align='center'>";
	$str .= "<tr height=25 > ";
	$str .= "<td width=8% align='left'> Index: </td>";
	$str .= "<td width=23% bgcolor= $bgcolor[1] align='center'> <= 15°C</td>";
   $str .= "<td width=23% bgcolor= $bgcolor[2] align='center'> 15.1°C - 17°C </td>";
   $str .= "<td width=23% bgcolor= $bgcolor[3] align='center'> 17.1°C - 22°C </td>";
   $str .= "<td width=23% bgcolor= $bgcolor[4] align='center'> > 22°C </td>";

	$str .="</table>";
	return $str;
	}


Function HM_TagesTempProfil_html($Tages_Profil, $slot_hl=99)
	{
	$start="00:00";
	$str = "<table align='center' border=0 cellpadding=3 cellspacing=3 width=90%>
								<tr height=30 ><th> Slot </th><th> Start </th><th> Ende </th><th> SollWert</th></tr>";

	Foreach ($Tages_Profil['EndTimes'] as $key=> $EndTime)
			 {
			 $SollWert = number_format( $Tages_Profil['Values'][$key], 1,",",".");
			 If ($key<>$slot_hl-1)
				 {
				 $str .= "<tr  align='center' height=25> <td width=25%> Slot " .($key+1) ."</td><td width=25%> $start </td><td width=25%> $EndTime </td><td width=25%> $SollWert °C</td></tr>";
				 }
				else
				 {
				 $str .= "<tr  bgcolor=#1A2B3C align='center' height=25> <td width=25%> Slot " .($key+1) ."</td><td width=25%> $start </td><td width=25%> $EndTime </td><td width=25%> $SollWert °C</td></tr>";
				 }
			 $start=$EndTime;
			 }

	$str .= "</table>";

	// Auswahl-Zeit-Slot in Webfront Anzeige auf Anzahl der belegten Slots im HM WT beschränken
	IPS_SetVariableProfileValues ("HM_Heizung_Slot", 1 , $key+1 , 1 );

	return $str;
	}

Function HM_WochenTempProfil_prüfen($ID_tempProfile)
	{
	$Ergebnis="OK";
	$dayArray = array("MONDAY","TUESDAY","WEDNESDAY","THURSDAY","FRIDAY","SATURDAY","SUNDAY");
	$dayArray_germ = array("Mo","Di","Mi","Do","Fr","Sa","So");
	$tempProfile=unserialize(Getvalue($ID_tempProfile));

	Foreach ($dayArray as $daykey => $day)
		{
  		$time1='00:00';
		Foreach ($tempProfile[$dayArray[$daykey]]['EndTimes'] as $Slotkey => $time2)
			{
         If (strtotime($time2)<= strtotime($time1)) // Speicherslots des HM WT in min
				{
				$Slot=$Slotkey+1;
				$Ergebnis="Fehler im Tagesprofil $dayArray_germ[$daykey] (Slot: $Slot Start: $time1 - Ende: $time2 )";
				break;
				}
			$time1=$time2;
			}
		}
	 return $Ergebnis;
	}



/**************************
    Kategorie anlegen
***************************/
function SetKatByName($parentID, $name, $ident, $position)
    {
	 $kid = @IPS_GetCategoryIDByName ($name, $parentID );
	 if($kid === false)
        {
			$kid = IPS_CreateCategory ( );       //Kategorie anlegen
			IPS_SetName($kid, $name); 		//Kategorie benennen
			IPS_SetParent($kid, $parentID);
         IPS_SetPosition ($kid, $position);
         if($ident !=="") {IPS_SetIdent ($kid , $ident );}
		  }
    return $kid;
	 }




/**************************
    Timer anlegen
***************************/
//0	Legt ein ausgelöstes Ereignis an
//1	Legt ein zyklisches Ereignis an

function SetTimerByName($parentID, $name, $ausloeser)
    {
     $eid = @IPS_GetEventIDByName($name, $parentID);
     if($eid === false)
        {
            $eid = IPS_CreateEvent(0);                  //Ausgelöstes Ereignis
				IPS_SetEventTrigger($eid, 1, $ausloeser);        //Bei Änderung von Variable mit ID 15754
				IPS_SetParent($eid, $parentID);         //Ereignis zuordnen
            IPS_SetName($eid, $name);
            IPS_SetInfo($eid, "this timer was created by script #".$_IPS['SELF']);
				IPS_SetEventActive($eid, true);             //Ereignis aktivieren
        }
		return $eid;
    }

/**************************
    Link anlegen
***************************/
function SetLinkByName($parentID, $name, $targetID, $position)
    {
	 $lid = @IPS_GetLinkIDByName ($name, $parentID );
	 if($lid === false)
        {
			$LinkID = IPS_CreateLink();       //Link anlegen
			IPS_SetName($LinkID, $name); 		//Link benennen
			IPS_SetLinkTargetID($LinkID, $targetID);
         IPS_SetParent($LinkID, $parentID);
         IPS_SetPosition ($LinkID, $position);
		  }
    return $lid;
	 }
	 
/**************************
   Dummy anlegen
***************************/
function SetDummyByName($parentID, $name, $ident, $position)
    {
	 $Iid = @ IPS_GetInstanceIDByName($name, $parentID );
	 if($Iid === false)
        {
			$Iid=IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}"); // Dummy anlegen
			IPS_SetName($Iid, $name); 		
			IPS_SetParent($Iid, $parentID);
         IPS_SetPosition ($Iid, $position);
         if($ident !=="") {IPS_SetIdent ($Iid, $ident);}
		  }
    return $Iid;
	 }


/*******************************************************
Heizung_Auswahl Profil mit Raumkategorien syncronisieren
*******************************************************/

Function Profil_anlegen($Profil)
	{
   include "HM_Heizung_Konfig.ips.php";
   
	Switch ($Profil)
	   {

		Case "HM_Heizung_Auswahl":

			If (IPS_VariableProfileExists ($Profil))
				{
	   	   $Associations=IPS_GetVariableProfile ($Profil)['Associations'];  // existierende Associationen im Profil
				}
				else
				{
				IPS_CreateVariableProfile ( $Profil , 1);
            $Associations=IPS_GetVariableProfile ($Profil)['Associations'];  // existierende Associationen im Profil
				}

			If (Count($Zimmer) != Count($Associations))
			   {
			   Foreach ($Associations as $ass)
			         {
			         IPS_SetVariableProfileAssociation ( $Profil , $ass['Value'] , "", "", 0 ); // zunächst alle Assosiationen löschen
			         }
            IPS_SetVariableProfileAssociation($Profil,0, " ", "", -1);  // erste Assosiation =0

				Foreach ($Zimmer as $key=>$Raum)
						{
						IPS_SetVariableProfileAssociation ( $Profil , $key , $Raum, "", -1 );
						}

				}
				else
				{
				Foreach ($Zimmer as $key=>$Raum)
						{
						If (@$Associations[$key]['Name'] != $Raum)
						   {
						   IPS_SetVariableProfileAssociation ( $Profil , $key , $Raum, "", -1 );
						   }

						}
    			}

		}

   //$Associations=IPS_GetVariableProfile ($Profil)['Associations'];  // existierende Associationen im Profil
	//print_r($Zimmer);
	//print_r ($Associations);

	return $Profil;
	}

/*****************************************************************************
   	      Funktionen für Raumsteuerung
*****************************************************************************/

Function Set_Praesenz_Profil($Raum, $Profil )
		{
      include "HM_Heizung_Konfig.ips.php";
		global $HM_Heizung_Wochenprofil_ID, $HM_Heizung_WochenProfil_Anzeige_html_ID;
      $gefunden="";


		Foreach ($Zimmer as $key=> $Zim)
         {
         If ($Raum == $Zim)
				{
			   $gefunden="OK";
				break;
				}
			}
      
      If ($gefunden == "OK")
         {
         If ($HM_Typ[$key]=="HM-CC-TC")
			   {
			   Raumsteuerung_HM_CC_TC($key);
			   }
			If ($HM_Typ[$key]=="HM-CC-RT-DN")
			   {
			   Raumsteuerung_HM_CC_RT_DN($key);
			   }

         $ProfilDaten=unserialize(GetValue($HM_Heizung_Wochenprofil_ID));
         $tmp=HMXML_setTempProfile($IPS_HM_DeviceID[$key], $ProfilDaten[$Profil]);

         $tempProfile = HMXML_getTempProfile($IPS_HM_DeviceID[$key],false, false);
         SetValue($HM_Heizung_WochenProfil_Anzeige_html_ID, HM_WochenTempProfil_html(serialize($tempProfile)));
         return $tmp;
         }
         else
         {
         return "Raum unbekannt";
         }
		}

Function Raumsteuerung_HM_TC_IT_WM_W_EU($Count)
	{
   include "HM_Heizung_Konfig.ips.php";

		global $HM_ParentID, $HM_Modus_ID, $HM_Heizung_Wochenprofil_ID, $HM_Wochenprofil_auslesen_ID, $HM_Heizung_WochenProfil_Anzeige_html_ID, $Tigger_WochenProfil_ID, $HM_Praesenz_Profil_Auswahl_ID, $HM_Wochenprofil_speichern_ID;

		$HM_ParentID=SetKatByName(IPS_GetParent (IPS_GetParent ($_IPS['SELF'])), $Zimmer[$Count], $Zimmer[$Count], $Count*10); //SetKatByName($HM_ParentID, $name, $ident, $position)
		$HM_Modus_ID=CreateVariableByName($HM_ParentID, "Modus-SOLL", 1, "HM_Heizung_Steuerung_RT-DN", "HMTCITModus", 1);
					IPS_SetVariableCustomAction($HM_Modus_ID, $_IPS['SELF']);
		$HM_Heizung_Wochenprofil_ID=CreateVariableByName($HM_ParentID, "HM_Wochenprofil_Daten", 3, "~String", "HMWochenprofilDaten", 2); // aktuelles Wochenprofil im HM
		$HM_Wochenprofil_auslesen_ID=CreateVariableByName($HM_ParentID, "HM_WochenProfil_auslesen", 1, "HM_Wochenprofil_aktualisieren", "HMWochenprofilaktualisieren", 6);
            IPS_SetVariableCustomAction($HM_Wochenprofil_auslesen_ID, $_IPS['SELF']);
		$HM_Wochenprofil_speichern_ID=CreateVariableByName($HM_ParentID, "HM_WochenProfil_speichern", 1, "HM_Heizung_Profil_speichern", "HMWochenprofilspeichern", 9);
            IPS_SetVariableCustomAction($HM_Wochenprofil_speichern_ID, $_IPS['SELF']);
		$HM_Heizung_WochenProfil_Anzeige_html_ID=CreateVariableByName($HM_ParentID, "HM_Heizung_WochenProfil_Anzeige_html", 3, "~HTMLBox", "HMWochenprofilhtml", 7);
		$HM_Praesenz_Profil_Auswahl_ID=CreateVariableByName($HM_ParentID, "HM_Praesenz_Profil_Auswahl_ID", 1, "Praesenz", "HMPraesenzProfilAuswahl", 8);
			  IPS_SetVariableCustomAction($HM_Praesenz_Profil_Auswahl_ID, $_IPS['SELF']);
		$Tigger_WochenProfil_ID= SetTimerByName($_IPS['SELF'], ("Tigger_WochenProfil_".$Zimmer[$Count]), $HM_Heizung_Wochenprofil_ID);


		SetLinkByName($HM_Wfe_ID[$Count], "HM-TC-IT - Modus - IST", IPS_GetObjectIDByIdent ("CONTROL_MODE", $IPS_HM_DeviceID[$Count]), 1);
		SetLinkByName($HM_Wfe_ID[$Count], "Modus-SOLL", $HM_Modus_ID, 2);
		SetLinkByName($HM_Wfe_ID[$Count], "IST", IPS_GetObjectIDByIdent ("ACTUAL_TEMPERATURE", $IPS_HM_DeviceID[$Count]), 3);
		SetLinkByName($HM_Wfe_ID[$Count], "SOLL", IPS_GetObjectIDByIdent ("SET_TEMPERATURE", $IPS_HM_DeviceID[$Count]), 4);
      SetLinkByName($HM_Wfe_ID[$Count], "Luftfeuchte", IPS_GetObjectIDByIdent ("ACTUAL_HUMIDITY", $IPS_HM_DeviceID[$Count]), 5);
		//SetLinkByName($HM_Wfe_ID[$Count], "Ventil", IPS_GetObjectIDByIdent ("VALVE_STATE", $IPS_HM_DeviceID[$Count]), 5);
		//SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil auslesen", $HM_Wochenprofil_auslesen_ID, 6);
		SetLinkByName($HM_Wfe_ID[$Count], "Profil Auswahl", $HM_Praesenz_Profil_Auswahl_ID, 7);
		SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil", $HM_Heizung_WochenProfil_Anzeige_html_ID, 8);
		SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil speichern", $HM_Wochenprofil_speichern_ID, 9);


		If (IPS_GetVariable(IPS_GetObjectIDByIdent ("CONTROL_MODE", $IPS_HM_DeviceID[$Count]))['VariableCustomProfile'] != "HM_Heizung_Steuerung_RT-DN")
		   {
		   IPS_SetVariableCustomProfile ( IPS_GetObjectIDByIdent ("CONTROL_MODE", $IPS_HM_DeviceID[$Count]) , "HM_Heizung_Steuerung_RT-DN" );
		   }

	}


Function Raumsteuerung_HM_CC_RT_DN($Count)
	{
   include "HM_Heizung_Konfig.ips.php";

		global $HM_ParentID, $HM_Modus_ID, $HM_Heizung_Wochenprofil_ID, $HM_Wochenprofil_auslesen_ID, $HM_Heizung_WochenProfil_Anzeige_html_ID, $Tigger_WochenProfil_ID, $HM_Praesenz_Profil_Auswahl_ID, $HM_Wochenprofil_speichern_ID;

		$HM_ParentID=SetKatByName(IPS_GetParent (IPS_GetParent ($_IPS['SELF'])), $Zimmer[$Count], $Zimmer[$Count], $Count*10); //SetKatByName($HM_ParentID, $name, $ident, $position)
		$HM_Modus_ID=CreateVariableByName($HM_ParentID, "Modus-SOLL", 1, "HM_Heizung_Steuerung_RT-DN", "HMRTModus", 1);
					IPS_SetVariableCustomAction($HM_Modus_ID, $_IPS['SELF']);
		$HM_Heizung_Wochenprofil_ID=CreateVariableByName($HM_ParentID, "HM_Wochenprofil_Daten", 3, "~String", "HMWochenprofilDaten", 2); // aktuelles Wochenprofil im HM
		$HM_Wochenprofil_auslesen_ID=CreateVariableByName($HM_ParentID, "HM_WochenProfil_auslesen", 1, "HM_Wochenprofil_aktualisieren", "HMWochenprofilaktualisieren", 6);
            IPS_SetVariableCustomAction($HM_Wochenprofil_auslesen_ID, $_IPS['SELF']);
		$HM_Wochenprofil_speichern_ID=CreateVariableByName($HM_ParentID, "HM_WochenProfil_speichern", 1, "HM_Heizung_Profil_speichern", "HMWochenprofilspeichern", 9);
            IPS_SetVariableCustomAction($HM_Wochenprofil_speichern_ID, $_IPS['SELF']);
		$HM_Heizung_WochenProfil_Anzeige_html_ID=CreateVariableByName($HM_ParentID, "HM_Heizung_WochenProfil_Anzeige_html", 3, "~HTMLBox", "HMWochenprofilhtml", 7);
		$HM_Praesenz_Profil_Auswahl_ID=CreateVariableByName($HM_ParentID, "HM_Praesenz_Profil_Auswahl_ID", 1, "Praesenz", "HMPraesenzProfilAuswahl", 8);
			  IPS_SetVariableCustomAction($HM_Praesenz_Profil_Auswahl_ID, $_IPS['SELF']);
		$Tigger_WochenProfil_ID= SetTimerByName($_IPS['SELF'], ("Tigger_WochenProfil_".$Zimmer[$Count]), $HM_Heizung_Wochenprofil_ID);


		SetLinkByName($HM_Wfe_ID[$Count], "HM-CC-RT-DN - Modus - IST", IPS_GetObjectIDByIdent ("CONTROL_MODE", $IPS_HM_DeviceID[$Count]), 1);
		SetLinkByName($HM_Wfe_ID[$Count], "Modus-SOLL", $HM_Modus_ID, 2);
		SetLinkByName($HM_Wfe_ID[$Count], "IST", IPS_GetObjectIDByIdent ("ACTUAL_TEMPERATURE", $IPS_HM_DeviceID[$Count]), 3);
		SetLinkByName($HM_Wfe_ID[$Count], "SOLL", IPS_GetObjectIDByIdent ("SET_TEMPERATURE", $IPS_HM_DeviceID[$Count]), 4);
		SetLinkByName($HM_Wfe_ID[$Count], "Ventil", IPS_GetObjectIDByIdent ("VALVE_STATE", $IPS_HM_DeviceID[$Count]), 5);
		SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil auslesen", $HM_Wochenprofil_auslesen_ID, 6);
		SetLinkByName($HM_Wfe_ID[$Count], "Profil Auswahl", $HM_Praesenz_Profil_Auswahl_ID, 7);
		SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil", $HM_Heizung_WochenProfil_Anzeige_html_ID, 8);
		SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil speichern", $HM_Wochenprofil_speichern_ID, 9);
		// Aktion bei Temperaturänderung hinterlegen
//		If (IPS_GetVariable(IPS_GetObjectIDByIdent ("SET_TEMPERATURE", $IPS_HM_DeviceID[$Count]))['VariableCustomAction'] != $_IPS['SELF'])
//			{
//			IPS_SetVariableCustomAction (IPS_GetObjectIDByIdent ("SET_TEMPERATURE", $IPS_HM_DeviceID[$Count]), $_IPS['SELF'] );
//			}

		If (IPS_GetVariable(IPS_GetObjectIDByIdent ("CONTROL_MODE", $IPS_HM_DeviceID[$Count]))['VariableCustomProfile'] != "HM_Heizung_Steuerung_RT-DN")
		   {
		   IPS_SetVariableCustomProfile ( IPS_GetObjectIDByIdent ("CONTROL_MODE", $IPS_HM_DeviceID[$Count]) , "HM_Heizung_Steuerung_RT-DN" );
		   }

	}


Function Raumsteuerung_HM_CC_TC($Count)
	{
      include "HM_Heizung_Konfig.ips.php";

		global $HM_ParentID, $HM_Modus_ID, $HM_Heizung_Wochenprofil_ID, $HM_Wochenprofil_auslesen_ID, $HM_Heizung_WochenProfil_Anzeige_html_ID, $Tigger_WochenProfil_ID, $HM_Praesenz_Profil_Auswahl_ID, $HM_Wochenprofil_speichern_ID;

		$HM_ParentID=SetKatByName(IPS_GetParent (IPS_GetParent ($_IPS['SELF'])), $Zimmer[$Count], $Zimmer[$Count], $Count*10); //SetKatByName($HM_ParentID, $name, $ident, $position)
		$HM_Modus_ID=CreateVariableByName($HM_ParentID, "HM_Heizung_Modus", 1, "HM_Heizung_Steuerung", "HMTCModus", 1);
					IPS_SetVariableCustomAction($HM_Modus_ID, $_IPS['SELF']);

		$HM_Heizung_Wochenprofil_ID=CreateVariableByName($HM_ParentID, "HM_Wochenprofil_Daten", 3, "~String", "HMWochenprofilDaten", 2); // aktuelles Wochenprofil im HM
  		$HM_Wochenprofil_auslesen_ID=CreateVariableByName($HM_ParentID, "HM_WochenProfil_auslesen", 1, "HM_Wochenprofil_aktualisieren", "HMWochenprofilaktualisieren", 6);
            IPS_SetVariableCustomAction($HM_Wochenprofil_auslesen_ID, $_IPS['SELF']);
		$HM_Wochenprofil_speichern_ID=CreateVariableByName($HM_ParentID, "HM_WochenProfil_speichern", 1, "HM_Heizung_Profil_speichern", "HMWochenprofilspeichern", 9);
            IPS_SetVariableCustomAction($HM_Wochenprofil_speichern_ID, $_IPS['SELF']);
		$HM_Heizung_WochenProfil_Anzeige_html_ID=CreateVariableByName($HM_ParentID, "HM_Heizung_WochenProfil_Anzeige_html", 3, "~HTMLBox", "HMWochenprofilhtml", 7);
		$HM_Praesenz_Profil_Auswahl_ID=CreateVariableByName($HM_ParentID, "HM_Praesenz_Profil_Auswahl_ID", 1, "Praesenz", "HMPraesenzProfilAuswahl", 8);
			  IPS_SetVariableCustomAction($HM_Praesenz_Profil_Auswahl_ID, $_IPS['SELF']);
		$Tigger_WochenProfil_ID= SetTimerByName($_IPS['SELF'], ("Tigger_WochenProfil_".$Zimmer[$Count]), $HM_Heizung_Wochenprofil_ID);

	   SetLinkByName($HM_Wfe_ID[$Count], "Modus", $HM_Modus_ID, 1);
   	SetLinkByName($HM_Wfe_ID[$Count], "IST", IPS_GetObjectIDByIdent ("TEMPERATURE", $HM_ID_W[$Count]), 2);
		SetLinkByName($HM_Wfe_ID[$Count], "SOLL", IPS_GetObjectIDByIdent ("SETPOINT", $IPS_HM_DeviceID[$Count]), 3);
		SetLinkByName($HM_Wfe_ID[$Count], "Luftfeuchte", IPS_GetObjectIDByIdent ("HUMIDITY", $HM_ID_W[$Count]), 4);
		SetLinkByName($HM_Wfe_ID[$Count], "Ventil", IPS_GetObjectIDByIdent ("VALVE_STATE", $HM_ID_VD[$Count]), 5);
		SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil auslesen", $HM_Wochenprofil_auslesen_ID, 6);
      SetLinkByName($HM_Wfe_ID[$Count], "Profil Auswahl", $HM_Praesenz_Profil_Auswahl_ID, 7);
		SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil", $HM_Heizung_WochenProfil_Anzeige_html_ID, 7);
		SetLinkByName($HM_Wfe_ID[$Count], "Wochenprofil speichern", $HM_Wochenprofil_speichern_ID, 9);
	}



?>