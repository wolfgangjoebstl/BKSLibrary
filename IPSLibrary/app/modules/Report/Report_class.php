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


	/**
	 * @class Report_Class
	 *
	 *
	 * @author Wolfgang Jöbstl
	 * @version
	 *   Version 2.50.1, 2.02.2016<br/>
	 */
	class ReportControl_Manager {

		/**
		 * @private
		 * ID Kategorie für die berechneten Werte
		 */
		private $categoryIdValues;

		/**
		 * @private
		 * ID Kategorie für allgemeine Steuerungs Daten
		 */
		private $categoryIdCommon;


		/**
		 * @private
		 * Konfigurations Daten Array der Sensoren
		 */
		private $sensorConfig;

		/**
		 * @private
		 * Konfigurations Daten Array der berechneten Werte
		 */
		private $valueConfig;

		/**
		 * @public
		 *
		 * Initialisierung des IPSReport_Manager Objektes
		 *
		 */
		public function __construct() {
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Report');
			$this->categoryIdValues   = IPS_GetObjectIDByIdent('Values', $baseId);
			$this->categoryIdCommon   = IPS_GetObjectIDByIdent('Common', $baseId);
			//$this->sensorConfig       = IPSPowerControl_GetSensorConfiguration();
			$this->valueConfig        = Report_GetValueConfiguration();
		}

		/**
		 * @public
		 *
		 * Modifiziert einen Variablen Wert der Kamera Steuerung
		 *
		 * @param integer $variableId ID der Variable die geändert werden soll
		 * @param variant $value Neuer Wert der Variable
		 */
		public function ChangeSetting($variableId, $value) {
			$variableIdent = IPS_GetIdent($variableId);
			//echo "VariableID : ".$variableId."Variableident : ".$variableIdent." mit Wert ".$value."   \n";
			if (substr($variableIdent,0,-1)==IPSRP_VAR_SELECTVALUE)
				{   /* bei SelectValue die Zahl am Ende wegnehmen und als Power Index speichern */
				$powerIdx      = substr($variableIdent,-1,-1);
				$variableIdent = substr($variableIdent,0,-1);
				//echo "Select Value mit ID ".$powerIdx."\n";
				}
			if (substr($variableIdent,0,-2)==IPSRP_VAR_SELECTVALUE) {
				$powerIdx      = substr($variableIdent,-1,-2);
				$variableIdent = substr($variableIdent,0,-2);
			}
			/* der Identifier von SelectValue 0 .. 99 wird herausgearbeitet und zusätzlich nach poweridx indexiert
			   sonst wird entsprechend der gedrückten Variable auf die Funktion aufgeteilt
			*/
			switch ($variableIdent) {
				case IPSRP_VAR_SELECTVALUE:         /* Änderung der Variable SelectValue, Auswahlfeld links */
					SetValue($variableId, $value);
					$this->CheckValueSelection($variableId);
					$this->RebuildGraph();
					break;
				case IPSRP_VAR_TYPEOFFSET:          /* Änderung der Variable TypeandOffset, Auswahlfeld erste Zeile */
				case IPSRP_VAR_PERIODCOUNT:         /* Änderung der Variable PeriodandCount, Auswahlfeld zweite Zeile */
					$this->Navigation($variableId, $value);
					$this->RebuildGraph();
					break;
				case IPSRP_VAR_VALUEKWH:
				case IPSRP_VAR_VALUEWATT:
				case IPSRP_VAR_CHARTHTML:
				case IPSRP_VAR_TIMEOFFSET:
				case IPSRP_VAR_TIMECOUNT:
					trigger_error('Variable'.$variableIdent.' could NOT be modified!!!');
					break;
				default:
					trigger_error('Unknown VariableID'.$variableId);
			}
		}

		private function CheckValueSelection($variableId)
			{
			$valueSelected = false;
			foreach ($this->valueConfig as $valueIdx=>$valueData)
				{
				if ($valueData[IPSRP_PROPERTY_DISPLAY])
					{
					$variableIdValueDisplay = IPS_GetVariableIDByName(IPSRP_VAR_SELECTVALUE.$valueIdx, $this->categoryIdCommon);
					if (($variableId!=$variableIdValueDisplay))
						{
						SetValue($variableIdValueDisplay,false);
						//echo "change ".$variableIdValueDisplay."\n";
						}
					$valueSelected = ($valueSelected or GetValue($variableIdValueDisplay));
					//echo "Check ".$valueIdx."  ".$variableIdValueDisplay."   ".GetValue($variableIdValueDisplay)."\n";
					}
				}
			if (!$valueSelected)
				{
				SetValue(IPS_GetVariableIDByName(IPSRP_VAR_SELECTVALUE.'0', $this->categoryIdCommon), true);
				}
			}

		private function Navigation($variableId, $value)
			{
			/* Wert 10 ist Stunde, 11 Tag, 12 Woche */
			$lastValue = GetValue($variableId);
			$variableIdOffset = IPS_GetObjectIDByIdent(IPSRP_VAR_TIMEOFFSET, $this->categoryIdCommon);
			$variableIdCount  = IPS_GetObjectIDByIdent(IPSRP_VAR_TIMECOUNT,  $this->categoryIdCommon);
			SetValue($variableId, $value);
			$restoreOldValue = false;
			Switch($value) {
				case IPSRP_COUNT_MINUS:
					if (GetValue($variableIdCount) > 1) {
						SetValue($variableIdCount, GetValue($variableIdCount) - 1);
					}
					IPS_SetVariableProfileAssociation('IPSReport_PeriodAndCount', IPSRP_COUNT_VALUE, GetValue($variableIdCount), "", -1);
					$restoreOldValue = true;
					break;
				case IPSRP_COUNT_PLUS:
					SetValue($variableIdCount, GetValue($variableIdCount) + 1);
					IPS_SetVariableProfileAssociation('IPSReport_PeriodAndCount', IPSRP_COUNT_VALUE, GetValue($variableIdCount), "", -1);
					$restoreOldValue = true;
					break;
				case IPSRP_OFFSET_PREV:
					SetValue($variableIdOffset, GetValue($variableIdOffset) - 1);
					IPS_SetVariableProfileAssociation('IPSReport_TypeAndOffset', IPSRP_OFFSET_VALUE, GetValue($variableIdOffset), "", -1);
					$restoreOldValue = true;
					break;
				case IPSRP_OFFSET_NEXT:
					if (GetValue($variableIdOffset) < 0) {
						SetValue($variableIdOffset, GetValue($variableIdOffset) + 1);
					}
					IPS_SetVariableProfileAssociation('IPSReport_TypeAndOffset', IPSRP_OFFSET_VALUE, GetValue($variableIdOffset), "", -1);
					$restoreOldValue = true;
					break;
				case IPSRP_OFFSET_VALUE:
				case IPSRP_OFFSET_SEPARATOR:
				case IPSRP_COUNT_VALUE:
				case IPSRP_COUNT_SEPARATOR:
					SetValue($variableId, $lastValue);
					break;
				default:
					// other Values
			}
			if ($restoreOldValue)  {
				IPS_Sleep(200);
				SetValue($variableId, $lastValue);
			}
			//echo "Neuer Wert ist ".GetValue($variableId)."\n";
		}
		
		/**
		 * @public
		 *
		 * Diese Funktion wird beim Auslösen eines Timers aufgerufen
		 *
		 * @param integer $timerId ID des Timers
		 */
		public function ActivateTimer($timerId) {
			$timerName = IPS_GetName($timerId);
			switch($timerName) {
				case 'CalculateWattValues';
					$this->CalculateWattValues();
					break;
				case 'CalculateKWHValues';
					$this->CalculateKWHValues();
					break;
				default:
					trigger_error('Unknown Timer '.$timerName.'(ID='.$timerId.')');
			}
		}

		private function GetGraphStartTime() {
			$variableIdOffset  = IPS_GetObjectIDByIdent(IPSRP_VAR_TIMEOFFSET,  $this->categoryIdCommon);
			$variableIdCount   = IPS_GetObjectIDByIdent(IPSRP_VAR_TIMECOUNT,   $this->categoryIdCommon);
			$variableIdPeriod  = IPS_GetObjectIDByIdent(IPSRP_VAR_PERIODCOUNT, $this->categoryIdCommon);

			$offset = abs(GetValue($variableIdOffset));
			$count  = GetValue($variableIdCount);
			$return = mktime(0,0,0, date("m", time()), date("d",time()), date("Y",time())); 
			switch(GetValue($variableIdPeriod)) {
				case IPSRP_PERIOD_HOUR:
					$return = strtotime('-'.($offset+$count).' hour');
					break;
				case IPSRP_PERIOD_DAY:
					$return = strtotime('-'.($offset+$count-1).' day', $return);
					break;
				case IPSRP_PERIOD_WEEK:
					$return = strtotime('-'.($offset+$count).' week', $return);
					break;
				case IPSRP_PERIOD_MONTH:
					$return = strtotime('-'.($offset+$count).' month', $return);
					break;
				case IPSRP_PERIOD_YEAR:
					$return = strtotime('-'.($offset+$count).' year', $return);
					break;
				default:
					trigger_error('Unknown Period '.GetValue($variableIdPeriod));
			}
			//echo "Startzeit ".date("d.m.Y H:i",$return)."\n";
			return $return;
		}

		private function GetGraphEndTime() {
			$variableIdOffset  = IPS_GetObjectIDByIdent(IPSRP_VAR_TIMEOFFSET,  $this->categoryIdCommon);
			$variableIdCount   = IPS_GetObjectIDByIdent(IPSRP_VAR_TIMECOUNT,   $this->categoryIdCommon);
			$variableIdPeriod  = IPS_GetObjectIDByIdent(IPSRP_VAR_PERIODCOUNT, $this->categoryIdCommon);

			$offset=abs(GetValue($variableIdOffset));
			$return = mktime(23,59,59, date("m", time()), date("d",time()), date("Y",time()));
			switch(GetValue($variableIdPeriod)) {
				case IPSRP_PERIOD_HOUR:
					$return = strtotime('-'.($offset).' hour');
					break;
				case IPSRP_PERIOD_DAY:
					$return = strtotime('-'.($offset).' day', $return);
					break;
				case IPSRP_PERIOD_WEEK:
					$return = strtotime('-'.($offset).' week', $return);
					break;
				case IPSRP_PERIOD_MONTH:
					$return = strtotime('-'.($offset).' month', $return);
					break;
				case IPSRP_PERIOD_YEAR:
					$return = strtotime('-'.($offset).' year', $return);
					break;
				default:
					trigger_error('Unknown Period '.$GetValue($variableIdPeriod));
			}
			//echo "Endzeit ".date("d.m.Y H:i",$return)."\n";
			return $return;
		}

		private function RebuildGraph ()
			{
			$report_config=Report_GetConfiguration();
			$count=0;
			$associationsValues = array();
			foreach ($report_config as $displaypanel=>$values)
				{
			   $associationsValues[$count]=$displaypanel;
			   $count++;
				}

			$variableIdChartType = IPS_GetObjectIDByIdent(IPSRP_VAR_TYPEOFFSET, $this->categoryIdCommon);
			$variableIdPeriod    = IPS_GetObjectIDByIdent(IPSRP_VAR_PERIODCOUNT, $this->categoryIdCommon);
			$variableIdChartHTML = IPS_GetObjectIDByIdent(IPSRP_VAR_CHARTHTML,  $this->categoryIdCommon);

			$periodList = array (	IPSRP_PERIOD_HOUR      		=> 'Stunde',
									IPSRP_PERIOD_DAY          	=> 'Tag',
			                     	IPSRP_PERIOD_WEEK         	=> 'Woche',
			                     	IPSRP_PERIOD_MONTH        	=> 'Monat',
			                     	IPSRP_PERIOD_YEAR         	=> 'Jahr');

			$valueTypeList = array (IPSRP_TYPE_KWH         		=> 'kWh',
			                        IPSRP_TYPE_EURO        		=> 'Euro',
			                        IPSRP_TYPE_WATT        		=> 'Watt',
			                        IPSRP_TYPE_STACK       		=> 'Details',
			                        IPSRP_TYPE_STACK2      		=> 'Total',
			                        IPSRP_TYPE_OFF         		=> 'Off',
			                        IPSRP_TYPE_PIE         		=> 'Pie');

			if (!array_key_exists(GetValue($variableIdChartType), $valueTypeList)) {
				SetValue($variableIdChartType, IPSRP_TYPE_KWH);
			}
			if (!array_key_exists(GetValue($variableIdPeriod), $periodList)) {
				SetValue($variableIdPeriod, IPSRP_PERIOD_DAY);
			}

			$archiveHandlerList = IPS_GetInstanceListByModuleID ('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
			$archiveHandlerId   = $archiveHandlerList[0];
			$chartType          = GetValue($variableIdChartType);

			$CfgDaten['ContentVarableId'] = $variableIdChartHTML ;
			$CfgDaten['Ips']['ChartType'] = 'Highcharts'; // Highcharts oder Highstock (default = Highcharts)
			$CfgDaten['StartTime']        = $this->GetGraphStartTime();
			$CfgDaten['EndTime']          = $this->GetGraphEndTime();
			$CfgDaten['RunMode']          = "file"; 	// file, script, popup

			// Serienübergreifende Einstellung für das Laden von Werten
			$CfgDaten['AggregatedValues']['HourValues']     = -1;      // ist der Zeitraum größer als X Tage werden Stundenwerte geladen
			$CfgDaten['AggregatedValues']['DayValues']      = -1;      // ist der Zeitraum größer als X Tage werden Tageswerte geladen
			$CfgDaten['AggregatedValues']['WeekValues']     = -1;      // ist der Zeitraum größer als X Tage werden Wochenwerte geladen
			$CfgDaten['AggregatedValues']['MonthValues']    = -1;      // ist der Zeitraum größer als X Tage werden Monatswerte geladen
			$CfgDaten['AggregatedValues']['YearValues']     = -1;      // ist der Zeitraum größer als X Tage werden Jahreswerte geladen
			$CfgDaten['AggregatedValues']['NoLoggedValues'] = 1000;    // ist der Zeitraum größer als X Tage werden keine Boolean Werte mehr geladen, diese werden zuvor immer als Einzelwerte geladen	$CfgDaten['AggregatedValues']['MixedMode'] = false;     // alle Zeitraumbedingungen werden kombiniert
			$CfgDaten['AggregatedValues']['MixedMode']      = false;
			$CfgDaten['title']['text']    = "";
			$CfgDaten['subtitle']['text'] = "Zeitraum: %STARTTIME% - %ENDTIME%";
			$CfgDaten['subtitle']['Ips']['DateTimeFormat'] = "(D) d.m.Y H:i";
			$CfgDaten['HighChart']['Theme']  = "ips.js";
			$CfgDaten['HighChart']['Width']  = 0; 			// in px,  0 = 100%
			$CfgDaten['HighChart']['Height'] = 700; 		// in px
			//$CfgDaten['HighChart']['Height'] = 'Auto'; 		// in px

			switch (GetValue($variableIdPeriod)) {
				case IPSRP_PERIOD_HOUR:	 $aggType = -1; break;	// es werden alle Werte bearbeitet
				case IPSRP_PERIOD_DAY:   $aggType = -1; break;   // es werden alle Werte bearbeitet
				case IPSRP_PERIOD_WEEK:  $aggType = 0; break;   // es wird vorher stündlich aggregiert 
				case IPSRP_PERIOD_MONTH: $aggType = 1; break;	
				case IPSRP_PERIOD_YEAR:  $aggType = 1; break;	// es wird vorher täglich aggregiert
				default:
				   trigger_error('Unknown Period '.GetValue($variableIdPeriod));
			}

			foreach ($this->valueConfig as $valueIdx=>$valueData)
				{
				/* Komplette GetValueConfiguration durchgehen, das sind die linken Auswahlfelder, hier sollte nur eines aktiv sein !!!!
				 * valueIdx geht vpn 0 bis x und valuedata ist die aktuelle config des kanals mit
				 *    IPSRP_PROPERTY_NAME			Name
				 *    IPSRP_PROPERTY_DISPLAY  	true,false ob Anzeige
				 *		IPSRP_PROPERTY_VALUETYPE	ValueType Total, Detail, Other aber auch Einheiten für die Anzeige
				 *
				 */
				$valueType = $valueData[IPSRP_PROPERTY_VALUETYPE];
				if ($valueData[IPSRP_PROPERTY_DISPLAY])            /* in der Config Tabelle aktiv eingestellt */
					{   /* hier sollte nur einmal eine Anzeige aktiv sein */
					$variableIdValueDisplay   = IPS_GetVariableIDByName(IPSRP_VAR_SELECTVALUE.$valueIdx, $this->categoryIdCommon);  /* auslesen vom linken Auswahlfeld */
					$variableIdValueKWH       = @IPS_GetVariableIDByName(IPSRP_VAR_VALUEKWH.$valueIdx,  $this->categoryIdValues);
					$variableIdValueWatt      = @IPS_GetVariableIDByName(IPSRP_VAR_VALUEWATT.$valueIdx, $this->categoryIdValues);
					$variableIdValueM3        = @IPS_GetVariableIDByName(IPSRP_VAR_VALUEM3.$valueIdx, $this->categoryIdValues);

					$serie = array();
					$serie['type']          = 'column';
					$serie['ReplaceValues'] = false;
					$serie['step']          = false;
					$serie['shadow']        = true;
					if ($aggType>=0) $serie['AggType'] = $aggType;		// für kleine Bereiche nicht angeben, dann geht es automatisch und es werden alle Werte dargestellt
					$serie['AggValue']      = 'Avg';
					$serie['yAxis']         = 0;
					$serie['zIndex']        = 110;
					$serie['step']          = false;
					$serie['visible']       = true;
					$serie['showInLegend']  = true;
					$serie['allowDecimals'] = false;
					$serie['enableMouseTracking'] = true;
					$serie['states']['hover']['lineWidth'] = 2;
					$serie['marker']['enabled'] = false;
					$serie['marker']['states']['hover']['enabled']   = true;
					$serie['marker']['states']['hover']['symbol']    = 'circle';
					$serie['marker']['states']['hover']['radius']    = 4;
					$serie['marker']['states']['hover']['lineWidth'] = 1;

					//echo "Anzeige vom Chart Typ : ".$chartType."   ".$valueIdx."   ";
					//print_r($valueData);
					
					switch ($chartType) /* Auswahlfeld erste Zeile */
						{
						case IPSRP_TYPE_OFF:
							if (GetValue($variableIdValueDisplay))    /* im linken Auswahlfeld selektiert */
								{
								SetValue($variableIdChartHTML, '');
								}
							return;
						case IPSRP_TYPE_STACK:
						case IPSRP_TYPE_STACK2:
							if (GetValue($variableIdValueDisplay))    /* im linken Auswahlfeld selektiert */
								{
								$serie['Unit']        = "kWh";
								$serie['ScaleFactor'] = 1;
								$serie['name']        = $valueData[IPSRP_PROPERTY_NAME];
								$serie['Id']          = $variableIdValueKWH;
								if ($variableIdValueKWH!==false and $valueData[IPSRP_PROPERTY_VALUETYPE]==IPSRP_VALUETYPE_TOTAL and $chartType==IPSRP_TYPE_STACK2)
									{
									$serie['zIndex']  = 100;
									$serie['stack']  = 'Total';
									$CfgDaten['series'][] = $serie;
									}
								elseif ($variableIdValueKWH!==false and $valueData[IPSRP_PROPERTY_VALUETYPE]==IPSRP_VALUETYPE_DETAIL)
									{
									$serie['zIndex']  = 110;
									$serie['stack']  = 'Detail';
									$CfgDaten['series'][] = $serie;
									}
								else
									{
									}
								$CfgDaten['yAxis'][0]['title']['text'] = "Verbrauch";
								$CfgDaten['yAxis'][0]['stackLabels']['enabled']    = true;
								$CfgDaten['yAxis'][0]['stackLabels']['formatter']  = "@function() { return this.total.toFixed(1) }@";
								$CfgDaten['yAxis'][0]['Unit'] = "kWh";
								$CfgDaten['plotOptions']['column']['stacking']     = "normal";
								$CfgDaten['plotOptions']['column']['borderColor']  = "#666666";
								$CfgDaten['plotOptions']['column']['borderWidth']  = 0;
								$CfgDaten['plotOptions']['column']['shadow']       = true;
								}
 							break;
						case IPSRP_TYPE_PIE:
							if (GetValue($variableIdValueDisplay))    /* im linken Auswahlfeld selektiert */
								{
								$serie['type'] = 'pie';
								if ($variableIdValueKWH!==false and $valueData[IPSRP_PROPERTY_VALUETYPE]==IPSRP_VALUETYPE_DETAIL)
									{
									$data_array	= AC_GetAggregatedValues($archiveHandlerId, $variableIdValueKWH, $aggType, $CfgDaten["StartTime"],$CfgDaten["EndTime"], 100);
									$value=0;
									for($i=0;$i<count($data_array)-1;$i++)
										{
										$value = $value + round($data_array[$i]['Avg'], 1);
										}
									$CfgDaten['series'][0]['ScaleFactor'] = 1;
									$CfgDaten['series'][0]['name']        = 'Aufteilung';
									$CfgDaten['series'][0]['Unit'] = '';
									$CfgDaten['series'][0]['type'] = 'pie';
									$CfgDaten['series'][0]['data'][] = [$valueData[IPSRP_PROPERTY_NAME],   $value];
									$CfgDaten['series'][0]['allowPointSelect'] = true;
									$CfgDaten['series'][0]['cursor'] = 'pointer';
									$CfgDaten['series'][0]['size'] = 200;
									$CfgDaten['series'][0]['dataLabels']['enabled'] = true;
									}
								}
							break;
						case IPSRP_TYPE_WATT:
							if (GetValue($variableIdValueDisplay))    /* im linken Auswahlfeld selektiert */
								{
								if ($variableIdValueWatt!==false and GetValue($variableIdValueDisplay))
									{
									$serie['Unit']        = "Watt";
									$serie['ScaleFactor'] = 1;
									$serie['name']        = $valueData[IPSRP_PROPERTY_NAME];
									$serie['Id']          = $variableIdValueWatt;
									$CfgDaten['series'][] = $serie;
									$CfgDaten['yAxis'][0]['title']['text'] = "Verbrauch";
									$CfgDaten['yAxis'][0]['Unit'] = "Watt";
									}
								elseif ($variableIdValueWatt===false and GetValue($variableIdValueDisplay))
									{
									SetValue($variableIdValueDisplay, false);
									}
								}
							break;
						case IPSRP_TYPE_EURO:								/* mit Tachoanzeigen, Test */
							if (GetValue($variableIdValueDisplay))    /* im linken Auswahlfeld selektiert */
								{
								$i=0; $j=0;
								$displaypanel=$associationsValues[$valueIdx];   /* welches Feld in getConfiguration */
								foreach ($report_config[$displaypanel]['series'] as $name=>$defserie)
								   {
								   /* wenn ich index erhöhe mache ich mehrere pies */
									$serie['type'] = 'pie'; // $defserie['type']
									$CfgDaten['series'][0]['ScaleFactor'] = 1;
									$CfgDaten['series'][0]['name']        = $valueData[IPSRP_PROPERTY_NAME];
									$CfgDaten['series'][0]['Unit'] = '';
									$CfgDaten['series'][0]['type'] = 'pie';
									$CfgDaten['series'][0]['data'][$i] = [$name, GetValue($defserie['Id'])];
									$CfgDaten['series'][0]['allowPointSelect'] = true;
									$CfgDaten['series'][0]['cursor'] = 'pointer';
									$CfgDaten['series'][0]['size'] = 200;
									$CfgDaten['series'][0]['dataLabels']['enabled'] = true;
								   $i++;
								   }
								}
							break;
						case IPSRP_TYPE_KWH:                         /* Graphendarstellung */
							if (GetValue($variableIdValueDisplay))    /* im linken Auswahlfeld selektiert */
								{
								$yaxis=array();                        /* Einstellungen der yaxis für alle einzelne Graphen sammeln */
								$i=0; $j=0;
								//echo " ---wird angezeigt ...\n";
								$displaypanel=$associationsValues[$valueIdx];   /* welches Feld in getConfiguration */
								$CfgDaten['title']['text']    = $displaypanel;

								/* zuerst yaxis erfassen und schreiben */
								foreach ($report_config[$displaypanel]['series'] as $name=>$defserie)
								   {
								   /* Name sind die einzelnen Kurven und defserie die Konfiguration der einzelnen Kurven */
								   
								   //echo "Kurve : ".$name." \n";
								   //print_r($defserie); echo "\n";
								 	$serie['name'] = $name;
    								$serie['type'] = $defserie['type'];
    								$serie['Id'] = $defserie['Id'];
								 	//if ($defserie['Unit']=='$')            /* Statuswerte */
									if ($defserie[IPSRP_PROPERTY_VALUETYPE]==IPSRP_VALUETYPE_STATE)
								 	   {
								 	   $serie['Unit']='$';
								    	$serie['step'] = 'right';
								    	$serie['ReplaceValues'] = array(0=>$j,1=>$j+1);
								    	$j+=2;
									 	if (isset($yaxis[$serie['Unit']]))
										 	{

										 	}
										else
									 	   {
										 	$serie['yAxis'] = $i;
											$yaxis[$serie['Unit']]= $i;
				 							$i++;
				 							}
		 	   						}
		 							else
		 	   						{
		 	   						/* wenn nicht Status alle Einheiten im array yaxis sammeln */
			 							if (isset($yaxis[$defserie[IPSRP_PROPERTY_VALUETYPE]])) {}
										else
			 	   						{
										 	$serie['yAxis'] = $i;
											$yaxis[$defserie[IPSRP_PROPERTY_VALUETYPE]]= $i;
										 	$i++;
										 	}
										}
							    	$serie['marker']['enabled'] = false;
							    	$CfgDaten['series'][] = $serie;
									}   /* ende foreach */
									
							   $i=0;
						  		$CfgDaten['yAxis'][$i]['opposite'] = false;
						  		$CfgDaten['yAxis'][$i]['gridLineWidth'] = 0;

								/* dann yaxis auswerten, und eventuell zwei Achsen links und rechts aufbauen
								 * die erste Achse kommt links, alle weiteren rechts , bei Status immer alle links
								 */
								$opposite=false;
								foreach ($yaxis as $unit=>$index)
								   {
								   //echo "**Bearbeitung von ".$unit." und ".$index." \n";
									if ($unit==IPSRP_VALUETYPE_TEMPERATURE)
									   {
								     	$CfgDaten['yAxis'][$index]['title']['text'] = "Temperaturen";
						   		 	$CfgDaten['yAxis'][$index]['Unit'] = '°C';
							    		$CfgDaten['yAxis'][$index]['opposite'] = $opposite;
							    		if ($opposite==false) { $opposite_=true; }
								    	//$CfgDaten['yAxis'][$i]['tickInterval'] = 5;
						   		 	//$CfgDaten['yAxis'][$i]['min'] = -20;
							  		 	//$CfgDaten['yAxis'][$i]['max'] = 50;
							  	 		//$CfgDaten['yAxis'][$i]['ceiling'] = 50;
							    	   }
						 			if ($unit==IPSRP_VALUETYPE_STATE)         /* Statuswerte */
									   {
								     	$CfgDaten['yAxis'][$index]['title']['text'] = "Status";
						   		 	$CfgDaten['yAxis'][$index]['Unit'] = '$';
								    	//$CfgDaten['yAxis'][$i]['tickInterval'] = 5;
						   		 	$CfgDaten['yAxis'][$index]['min'] = 0;
							   	 	//$CfgDaten['yAxis'][$i]['offset'] = 100;
								  	 	//$CfgDaten['yAxis'][$i]['max'] = 100;
							   	   }
						 			if ($unit==IPSRP_VALUETYPE_HUMIDITY)
									   {
								     	$CfgDaten['yAxis'][$index]['title']['text'] = "Feuchtigkeit";
						   		 	$CfgDaten['yAxis'][$index]['Unit'] = '%';
							    		$CfgDaten['yAxis'][$index]['opposite'] = $opposite;
							    		if ($opposite==false) { $opposite_=true; }
								    	//$CfgDaten['yAxis'][$i]['tickInterval'] = 5;
						   		 	//$CfgDaten['yAxis'][$index]['min'] = 0;
							   	 	//$CfgDaten['yAxis'][$i]['offset'] = 100;
								  	 	//$CfgDaten['yAxis'][$i]['max'] = 100;
							   	   }
						 			if ($unit==IPSRP_VALUETYPE_LENGTH)
									   {
								     	$CfgDaten['yAxis'][$index]['title']['text'] = "Regenmenge";
						   		 	$CfgDaten['yAxis'][$index]['Unit'] = 'mm';
							    		$CfgDaten['yAxis'][$index]['opposite'] = $opposite;
							    		if ($opposite==false) { $opposite_=true; }
							   	   }
						 			if ($unit==IPSRP_VALUETYPE_CURRENT)
									   {
								     	$CfgDaten['yAxis'][$index]['title']['text'] = "Strom";
						   		 	$CfgDaten['yAxis'][$index]['Unit'] = 'A';
							    		$CfgDaten['yAxis'][$index]['opposite'] = $opposite;
							    		if ($opposite==false) { $opposite_=true; }
							   	   }
						 			if ($unit==IPSRP_VALUETYPE_POWER)
									   {
								     	$CfgDaten['yAxis'][$index]['title']['text'] = "Leistung";
								     	/* in der Report_getconfiguration können unterschiedliche Werte stehen, zB W und kW, hier vereinheitlichen */
						   		 	$CfgDaten['yAxis'][$index]['Unit'] = 'kW';
							    		$CfgDaten['yAxis'][$index]['opposite'] = $opposite;
							    		if ($opposite==false) { $opposite_=true; }
							   	   }
						 			if ($unit==IPSRP_VALUETYPE_ENERGY)
									   {
								     	$CfgDaten['yAxis'][$index]['title']['text'] = "Energie";
						   		 	$CfgDaten['yAxis'][$index]['Unit'] = 'kWh';
							    		$CfgDaten['yAxis'][$index]['opposite'] = $opposite;
							    		if ($opposite==false) { $opposite_=true; }
							   	   }
								 	} /* ende foreach */

								/*
								if ($valueType==IPSRP_VALUETYPE_GAS)
									{
									$yAxisText = ($chartType==IPSRP_TYPE_EURO)?"Euro":"Gas / Wasser";
									$yAxisIdx  = $this->GetYAxisIdx($CfgDaten, $yAxisText);
									$serie['Unit']        = ($chartType==IPSRP_TYPE_EURO)?"Euro":"m³";
									$serie['Id']          = $variableIdValueM3;
									$serie['ScaleFactor'] = ($chartType==IPSRP_TYPE_EURO)?(IPSRP_GASRATE_KWH*IPSRP_GASRATE_EURO/100):1;
									$serie['yAxis']       = $yAxisIdx;
									$CfgDaten['series'][] = $serie;
									$CfgDaten['yAxis'][$yAxisIdx]['title']['text'] = $yAxisText;
									$CfgDaten['yAxis'][$yAxisIdx]['Unit']          = $serie['Unit'];
									$CfgDaten['yAxis'][$yAxisIdx]['stackLabels']['enabled']    = true;
									$CfgDaten['yAxis'][$yAxisIdx]['stackLabels']['formatter']  = "@function() { return this.total.toFixed(1) }@";
									}
								elseif ($valueType==IPSRP_VALUETYPE_WATER)
									{
									$yAxisText = ($chartType==IPSRP_TYPE_EURO)?"Euro":"Gas / Wasser";
									$yAxisIdx  = $this->GetYAxisIdx($CfgDaten, $yAxisText);
									$serie['Unit']        = ($chartType==IPSRP_TYPE_EURO)?"Euro":"m³";
									$serie['Id']          = $variableIdValueM3;
									$serie['ScaleFactor'] = ($chartType==IPSRP_TYPE_EURO)?(IPSRP_WATERRATE/100):1;
									$serie['yAxis']       = $yAxisIdx;
									$CfgDaten['series'][] = $serie;
									$CfgDaten['yAxis'][$yAxisIdx]['title']['text'] = $yAxisText;
									$CfgDaten['yAxis'][$yAxisIdx]['Unit']          = $serie['Unit'];
									$CfgDaten['yAxis'][$yAxisIdx]['stackLabels']['enabled']    = true;
									$CfgDaten['yAxis'][$yAxisIdx]['stackLabels']['formatter']  = "@function() { return this.total.toFixed(1) }@";
									}
								else
									{
									$yAxisText = ($chartType==IPSRP_TYPE_EURO)?"Euro":"Strom";
									$yAxisIdx  = $this->GetYAxisIdx($CfgDaten, $yAxisText);
									$serie['Unit']        = ($chartType==IPSRP_TYPE_EURO)?"Euro":"kWh";
									$serie['Id']          = $variableIdValueKWH;
									$serie['ScaleFactor'] = ($chartType==IPSRP_TYPE_EURO)?(IPSRP_ELECTRICITYRATE/100):1;
									$serie['yAxis']       = $yAxisIdx;
									$CfgDaten['series'][] = $serie;
									$CfgDaten['yAxis'][$yAxisIdx]['title']['text'] = $yAxisText;
									$CfgDaten['yAxis'][$yAxisIdx]['Unit']          = $serie['Unit'];
									$CfgDaten['yAxis'][$yAxisIdx]['stackLabels']['enabled']    = true;
									$CfgDaten['yAxis'][$yAxisIdx]['stackLabels']['formatter']  = "@function() { return this.total.toFixed(1) }@";
									}
								*/
							}
							break;
						default:
					}
				}
			}
			//print_r($CfgDaten);
			if (!array_key_exists('series', $CfgDaten)) {
				SetValue($variableIdChartHTML, '');
				return;
			}

			// Create Chart with Config File
			IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");
			$CfgDaten    = CheckCfgDaten($CfgDaten);
			//print_r($CfgDaten);
			$sConfig     = CreateConfigString($CfgDaten);            
			//echo $sConfig;
			$tmpFilename = CreateConfigFile($sConfig, 'IPSPowerControl');    
			WriteContentWithFilename ($CfgDaten, $tmpFilename);      
		}

		private function GetYAxisIdx($CfgDaten, $text) {
			$maxIdx = -1;
			if (array_key_exists('yAxis', $CfgDaten)) {
				foreach ($CfgDaten['yAxis'] as $idx=>$data) {
					$maxIdx = $idx;
					if ($data['title']['text'] == $text) {
						return $idx;
					}
				}
			}
			return ($maxIdx+1);
		}
		
		private function CalculateKWHValues () {
			// Prepare Value Lists for Callback
			$sensorValuesKWH = array();
			$calcValuesKWH   = array();
			foreach ($this->sensorConfig as $sensorIdx=>$sensorData) {
				$sensorValue = 0;
				if (array_key_exists(IPSRP_PROPERTY_VARKWH, $sensorData) and $sensorData[IPSRP_PROPERTY_VARKWH] <> null) {
					$variableIdKWH = IPSUtil_ObjectIDByPath($sensorData[IPSRP_PROPERTY_VARKWH]);
					$sensorValue    = GetValue($variableIdKWH);
					//echo 'SensorValue'.$sensorIdx.' '.$variableIdKWH.'='.$sensorValue.PHP_EOL;
				} elseif (array_key_exists(IPSRP_PROPERTY_VARM3, $sensorData) and $sensorData[IPSRP_PROPERTY_VARM3] <> null) {
					$variableIdm3 = IPSUtil_ObjectIDByPath($sensorData[IPSRP_PROPERTY_VARM3]);
					$sensorValue    = GetValue($variableIdm3);
				}
				$sensorValuesKWH[$sensorIdx] = $sensorValue;
			}
			foreach ($this->valueConfig as $valueIdx=>$valueData) {
				$calcValuesKWH2[$valueIdx] = 0;
				$variableId = @IPS_GetObjectIDByIdent(IPSRP_VAR_VALUEKWH.$valueIdx, $this->categoryIdValues);
				if ($variableId!==false) {
					$calcValuesKWH2[$valueIdx] = GetValue($variableId);
				} else {
					$variableId = @IPS_GetObjectIDByIdent(IPSRP_VAR_VALUEM3.$valueIdx, $this->categoryIdValues);
					if ($variableId!==false) {
						$calcValuesKWH2[$valueIdx] = GetValue($variableId);
					}
				}
			}

			// Calculate Value
			$calcValuesKWH = IPSPowerControl_CalculateValuesKWH($sensorValuesKWH, $calcValuesKWH2);
			
			// Write Values
			foreach ($this->valueConfig as $valueIdx=>$valueData) {
				echo 'Write '.$valueData[IPSRP_PROPERTY_NAME].'='.$calcValuesKWH[$valueIdx].', Old='.$calcValuesKWH2[$valueIdx].', Diff='.($calcValuesKWH[$valueIdx]-$calcValuesKWH2[$valueIdx]).PHP_EOL;
				$variableId = @IPS_GetObjectIDByIdent(IPSRP_VAR_VALUEKWH.$valueIdx, $this->categoryIdValues);
				if ($variableId!==false) {
					SetValue($variableId, $calcValuesKWH[$valueIdx]);
				} else {
					$variableId = @IPS_GetObjectIDByIdent(IPSRP_VAR_VALUEM3.$valueIdx, $this->categoryIdValues);
					if ($variableId!==false) {
						SetValue($variableId, $calcValuesKWH[$valueIdx]);
					}
				}
			}
		}
		
		private function CalculateWattValues () {
			// Prepare Value Lists for Callback
			$sensorValuesWatt = array();
			$calcValuesWatt   = array();
			foreach ($this->sensorConfig as $sensorIdx=>$sensorData) {
				$sensorValue = 0;
				if (array_key_exists(IPSRP_PROPERTY_VARWATT, $sensorData) and $sensorData[IPSRP_PROPERTY_VARWATT] <> null) {
					$variableIdWatt = IPSUtil_ObjectIDByPath($sensorData[IPSRP_PROPERTY_VARWATT]);
					$sensorValue    = GetValue($variableIdWatt);
				}
				$sensorValuesWatt[$sensorIdx] = $sensorValue;
			}
			foreach ($this->valueConfig as $valueIdx=>$valueData) {
				$calcValuesWatt[$sensorIdx] = 0;
			}
			// Calculate Value
			$calcValuesWatt = IPSPowerControl_CalculateValuesWatt($sensorValuesWatt, $calcValuesWatt);
			// Write Values
			foreach ($this->valueConfig as $valueIdx=>$valueData) {
				$variableId = @IPS_GetObjectIDByIdent(IPSRP_VAR_VALUEWATT.$valueIdx, $this->categoryIdValues);
				if ($variableId!==false) {
					echo 'Write '.$variableId.'='.$calcValuesWatt[$valueIdx].', Name='.$valueData[IPSRP_PROPERTY_NAME].PHP_EOL;
					SetValue($variableId, $calcValuesWatt[$valueIdx]);
				}
			}
		}

	}


	/** @}*/
?>