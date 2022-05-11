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
     * für die Darstellung von Highcharts Grafiken im Webfront. Aufruf erfolgt über ActionManaager:
     *		$variableId   = $_IPS['VARIABLE'];  $value        = $_IPS['VALUE'];
     *		$pcManager = new ReportControl_Manager(); $pcManager->ChangeSetting($variableId, $value);
     *
     * der ReportControl_Manager hat nur eine Funktion
     *      ChangeSetting       für die Aktivitäten wenn eine Variable im Webfront umgestellt wird. Zentrale function, Übergabe variableID und Wert
     *          abhängig vom Identifier und seinem angehängtem Index wird 
     *          CheckValueSelection     die Einträge überpüft
	 *		    RebuildGraph            und den Graphen neu zeichnet
     *          compileConfiguration    die gemeinsame Konfiguration erstellen, soweit für alle Darstellungsformen gleich
     *
     *      __construct
     *      setConfiguration
     *      setValueConfiguration    
     *      getValueConfiguration 
     *      getConfiguration
     *      getcategoryIdCommon
     *      ChangeSetting     
     *      CheckValueSelection
     *      Navigation
	 *      ActivateTimer
     *      GetGraphStartTime
     *      GetGraphEndTime
     *      RebuildGraph
     *      GetYAxisIdx
     *      CalculateKWHValues
     *      CalculateWattValues
     *      
     * Report zeigt die verschiedenen Kurven und bei Easycharts auch Tabellen an. Auswahl über SChalter auf der linken Seite
     *
     *
	 * @author Wolfgang Jöbstl
	 * @version
	 *   Version 2.50.1, 2.02.2016<br/>
	 */
     
	class ReportControl_Manager {

		private $categoryIdValues;              // * ID Kategorie für die berechneten Werte
		private $categoryIdCommon;  	        // * ID Kategorie für allgemeine Steuerungs Daten
        private $categoryIdData;  	              // * ID Kategorie des Parents
        private $visualizationCategoryID;           // hier sind die Links die auf die einzelnen Datenobjekte verweisen, notwendig um ein paar ein oder auszublenden
		private $sensorConfig;                  // * Konfigurations Daten Array der Sensoren
		private $valueConfig;                   // * Konfigurations Daten Array der berechneten Werte
        private $configuration;                 // Gesamtkonfiguration
        private $debug;                         // aditional echo logging 

		/**
		 * @public
		 *
		 * Initialisierung des IPSReport_Manager Objektes
		 *
		 */
		public function __construct($debug=false) 
            {
            $this->debug=$debug;
			$baseId = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Report');
			$this->categoryIdValues   = IPS_GetObjectIDByIdent('Values', $baseId);
			$this->categoryIdCommon   = IPS_GetObjectIDByIdent('Common', $baseId);
            $this->categoryIdData     = $baseId;
            $this->visualizationCategoryID = IPS_GetObjectIdByName("VisualizationCategory",$this->categoryIdData);
			//$this->sensorConfig       = IPSPowerControl_GetSensorConfiguration();
			$this->valueConfig        = $this->setValueConfiguration();                                 // pro Report eine Index 0..x aufsteigend, kontinuierlich, Name ist der Verweis
            $this->configuration      = $this->setConfiguration();                                      // auf den Key/Index für die detaillierte Konfiguration des Reports
		    }


        private function setConfiguration()
            {
            $config = Report_GetConfiguration();                            // detaillierte Konfiguration für jeden einzelnen Report, Index ist ein eindeutiger Name
            $configValue = $this->setValueConfiguration();                  // Anordnung der einzelnen reports, aufsteigend, Key ist eine Zahl
            /* check ob die Indexe der Reports durchgängig sind */
            foreach ($configValue as $entry)
                {
                //print_R($entry);
                if (isset($entry["Name"])) 
                    {
                    if (isset($config[$entry["Name"]]))
                        {
                        //echo $entry["Name"]."   \n";
                        //'color'     => 0x008000,
                        //'title' wird zum Index
                        
                        }
                    else 
                        {
                        echo "Fehler, ".$entry["Name"]." in Report_GetValueConfiguration hat keine Konfiguration inReport_GetConfiguration.  \n";
                        print_R($entry);
                        }
                    }
                }
            return($config); 
            }

        private function setValueConfiguration()
            {
            $config = Report_GetValueConfiguration();
            return($config); 
            }


        public function getValueConfiguration()
            {
            return($this->valueConfig); 
            }

        public function getConfiguration()
            {
            return ($this->configuration);
            }

        public function getcategoryIdCommon()
            {
            return ($this->categoryIdCommon);
            } 

		/**
		 * @public
		 *
		 * Modifiziert einen Variablen Wert der Report Steuerung
         * die Variablenamen sind beliebig. Wichtig ist der identifier für die Steuerung der weiteren Tätigkeiten.
         *
         * Für das linke Auswahlfenster gilt: 
         *    Die letzte Zahl aus dem identifier, es ist 0-9 und 10-99 möglich, wird als Index (pweridx) herausgenommen.
		 *    Davor steht "SelectValue" also zB SelectValue12 oder SelectValue3
         *    es wird CheckValueSelection vor dem Zeichnen aufgerufen
         *
         * Für das rechte Auswahlfenster gilt:
         *    es wird Navigation aufgerufen
         *
         *
         *
		 * @param integer $variableId ID der Variable die geändert werden soll
		 * @param variant $value Neuer Wert der Variable
		 */
		public function ChangeSetting($variableId, $value) 
            {
			$variableIdent = IPS_GetIdent($variableId);

			if ($this->debug) echo "ReportControl_Manager->ChangeSetting,VariableID : ".$variableId." Variableident : ".$variableIdent." mit Wert ".$value."   \n";
			if (substr($variableIdent,0,-1)==IPSRP_VAR_SELECTVALUE)                                         // entweder die letzte Zahl ist der Index
				{   /* bei SelectValue die Zahl am Ende wegnehmen und als Power Index speichern */
                //echo "go1   $variableIdent Look for: ".IPSRP_VAR_SELECTVALUE;
				$powerIdx      = intval(substr($variableIdent,-1,1));          // powerIdx ist die letzte Stelle
				$variableIdent = substr($variableIdent,0,-1);
				//echo "Select Value mit ID ".$powerIdx."\n";
				}
			if (substr($variableIdent,0,-2)==IPSRP_VAR_SELECTVALUE)                         // oder die letzten beiden Zahlen ist der Index 
                {
                //echo "go2   $variableIdent ";                    
				$powerIdx      = intval(substr($variableIdent,-2,2));           // powerIdx sind die letzten beiden Stellen
				$variableIdent = substr($variableIdent,0,-2);
                //echo " \"$powerIdx\"  \"$variableIdent\" ";
			    }
			/* der Identifier von SelectValue 0 .. 99 wird herausgearbeitet und zusätzlich nach poweridx indexiert
			   sonst wird entsprechend der gedrückten Variable auf die Funktion aufgeteilt
			*/

            /* 
            $value_config=$pcManager->getValueConfiguration();          // rausfinden welcher Report selektiert wurde
            $config=$pcManager->getConfiguration();
            print_R($value_config);
            $result=false;
            foreach ($value_config as $valueIdx => $valueEntry)
                {
                $variableIdValueDisplay = IPS_GetVariableIDByName(IPSRP_VAR_SELECTVALUE.$valueIdx, $categoryIdCommon);   
                if (GetValue($variableIdValueDisplay))                  // nur einer der Schalter ist auf 1
                    {
                    echo "Select $valueIdx \n";
                    $resultIdx=$valueIdx;
                    if (isset($valueEntry["Name"]))
                        {
                        $name=$valueEntry["Name"];
                        if (isset($config[$name])) $resultConfig=$config[$name];
                        }
                    }
                }
            // Type is Chart, abhängig von der Konfiguration wird die Formatierung der Auswahlvariable geändert 
            if (isset($value_config[$resultIdx][IPSRP_PROPERTY_VALUETYPE]))
                {
                if ($value_config[$resultIdx][IPSRP_PROPERTY_VALUETYPE] == IPSRP_VALUETYPE_CHART)
                    {
                    // es muss der Typ Chart sein
                    echo json_encode($resultConfig)."\n";


                    }
                }            
            if (isset($report_config["configuration"]))
                {
                unset ($report_config["series"]);                                       // selber erstellen
                $selectAssociation=array();
                $ReportDataSelectorID = IPS_GetObjectIdByName("ReportDataSelector", $this->categoryIdData); 
                $select=GetValue($ReportDataSelectorID);
                if ($this->debug) echo "Alternative Configuration for Series Display detected, create series from config var, use selection $select:\n";
                foreach ($report_config["configuration"] as $index=>$configID)
                    {
                    $selectAssociation[]=$index;    
                    }
                CreateProfile_Associations ('ReportDataSelect',     $selectAssociation);
                if (isset($report_config[IPSRP_PROPERTY_VALUETYPE])) $valueType=$report_config[IPSRP_PROPERTY_VALUETYPE];
                else $valueType="Euro";
                $config=GetValue($report_config["configuration"][$selectAssociation[$select]]);
                $configArray=json_decode($config,true);
                //print_R($configArray);
                foreach ($configArray as $index => $entry)
                    {
                    if ( (isset($entry["Name"])) && ($entry["Name"] != "") ) $name=$entry["Name"];
                    else $name=$index;
                    $result["Id"]=$entry["OID"];
                    $result[IPSRP_PROPERTY_VALUETYPE]=$valueType;
                    $report_config["series"][$name]=$result;
                    }
                }*/

			switch ($variableIdent) {
				case IPSRP_VAR_SELECTVALUE:         /* Änderung der Variable SelectValue, Auswahlfeld links */
                    $ReportDataSelectorID = IPS_GetObjectIdByName("ReportDataSelector", $this->categoryIdData);  
                    $this->visualizationCategoryID = IPS_GetObjectIdByName("VisualizationCategory",$this->categoryIdData);
                    if ($this->visualizationCategoryID) 
                        { 
                        $AddSelectorID = IPS_GetObjectIdByName("AddSelector", GetValue($this->visualizationCategoryID));
                        $DataTableID   = IPS_GetObjectIdByName("DataTable",   GetValue($this->visualizationCategoryID)); 
                        }

                    $valueConfig=$this->getValueConfiguration();
                    if (isset($valueConfig[$powerIdx][IPSRP_PROPERTY_VALUETYPE]))
                        {
                        switch ($valueConfig[$powerIdx][IPSRP_PROPERTY_VALUETYPE])
                            {
                            case IPSRP_VALUETYPE_CHART:
                            case IPSRP_VALUETYPE_TREND:
                                $name=$valueConfig[$powerIdx][IPSRP_PROPERTY_NAME];
                                $config=$this->getConfiguration()[$name]; 
                                if (isset($config["configuration"])) $configAssoc = $config["configuration"];                              
                                if (isset($config["configSeries"])) $configAssoc = $config["configSeries"];
                                $selectAssociation=array();
                                foreach ($configAssoc as $index=>$configID)
                                    {
                                    $selectAssociation[]=$index;    
                                    } 
                                if ($this->debug) echo "   CreateProfile_Associations ReportDataSelect with ".json_encode($selectAssociation)." \n";
                                CreateProfile_Associations ('ReportDataSelect',     $selectAssociation);  
                                IPS_SetVariableCustomProfile ($ReportDataSelectorID, 'ReportDataSelect');                                                           
                                if ($AddSelectorID) IPS_SetHidden($AddSelectorID,false);          // die Variable darunter sichtbar oder nicht machen
                                if ($DataTableID)   IPS_SetHidden($DataTableID,  false);          // die Variable darunter sichtbar oder nicht machen
                                //echo $ReportDataSelectorID;
                                //echo json_encode($valueConfig[$powerIdx]);   
                                break;
                            default:
                                if ($AddSelectorID) IPS_SetHidden($AddSelectorID,true);
                                if ($DataTableID)   IPS_SetHidden($DataTableID,  true);
                                break;
                            }
                        }                                         
					SetValue($variableId, $value);
					$this->CheckValueSelection($variableId);                // die anderen auf 0 setzen
					$this->RebuildGraph();
					break;
				case IPSRP_VAR_TYPEOFFSET:          /* Änderung der Variable TypeandOffset, Auswahlfeld erste Zeile */
				case IPSRP_VAR_PERIODCOUNT:         /* Änderung der Variable PeriodandCount, Auswahlfeld zweite Zeile */
                    //echo "ANvigattion Rechts oben, sowohl Darstellungsart als auch Zeitraum";
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
            if ($this->debug) echo "CheckValueSelection aufgerufen für $variableId.\n";
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

        /* eine etwas schräge und sehr kompakte Darstellungsform.
         * Es gibt zwei Profile die individuell zur Runtime angepasst werden
         * Bearbeitet werden dadurch folgende Einstellungen
         *      $variableIdCount  aus der Zeile PeriodAndCount
         *      $variableIdOffset aus der Zeile TypeAndOffset
         * die anderen Werte werden geradlinig transparent abgespeichert
         *
         */

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

        /* RebuildGraph, damit macht man die Darstellung entsprechen 
         *      $variableIdChartType                Watt, Euro
         *      $variableIdPeriod                   Day,Week,Month
         *
         * die User Konfiguration befindet sich in $report_config
         *      für jeden Report gibt es eine eigene Konfiguration
         *      die Zeilennummer (index) verweist auf die Detailkonfiguration, 
         *      wegen $associationsValues muss die Nummer nicht chronologisch sein
         *
         * die Highcharts Konfiguration wird in cfgDaten schrittweise erstellt
         * der Abschluss über einen Filenamen ist immer gleich:
         *
		 *	IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");
		 *	$CfgDaten    = CheckCfgDaten($CfgDaten);
		 *	$sConfig     = CreateConfigString($CfgDaten);           // IPSHighCharts function        
		 *	$tmpFilename = CreateConfigFile($sConfig, 'IPSPowerControl');    
	     *  WriteContentWithFilename ($CfgDaten, $tmpFilename);      
         *
         * es sieht so aus wenn man unterschiedliche Konfigurationen verwendet dass sie sich auch untereinander auch bei unterschiedlichen Filenamen beeinflussen.
         *
         */

		public function RebuildGraph ()
			{
            //echo "RebuildGraph";
			$report_config=$this->getConfiguration();
			$count=0;
			$associationsValues = array();
			foreach ($report_config as $displaypanel=>$values)
				{
			    $associationsValues[$count]=$displaypanel;
			    $count++;
				}
            if ($this->debug) 
                {
                //print_r($associationsValues);           // das sind die einzelnen Darstellungsvarianten der Graphen
                echo "RebuildGraph for $count avialable Association, graphs (".json_encode($associationsValues)."):\n";
                foreach ($this->valueConfig as $valueIdx=>$valueData) echo "    $valueIdx ".json_encode($valueData)."\n";
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

            if ($this->debug) echo "Einstellungen ChartType : ".GetValue($variableIdChartType)."  Period : ".GetValue($variableIdPeriod)."\n";
            /* Plausicheck and if not part of configuration go back to default values */
			if (!array_key_exists(GetValue($variableIdChartType), $valueTypeList)) 
                {
				SetValue($variableIdChartType, IPSRP_TYPE_KWH);
			    }
			if (!array_key_exists(GetValue($variableIdPeriod), $periodList)) 
                {
				SetValue($variableIdPeriod, IPSRP_PERIOD_DAY);
			    }
            if ($this->debug) echo "Einstellungen ChartType : ".GetValue($variableIdChartType)."  Period : ".GetValue($variableIdPeriod)."  (after justification)\n";

			$archiveHandlerList = IPS_GetInstanceListByModuleID ('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
			$archiveHandlerId   = $archiveHandlerList[0];
			$chartType          = GetValue($variableIdChartType);

            /* hier beginnt die Erstellung der Highcharts Konfigration */

            $CfgDaten=array();
            //$CfgDaten['chart']['type']  = "line";         // später setzen

            $CfgDaten['chart']['plotBackgroundColor']  = "#cccccc";         // wirklich den Bereich auf den die Kurven geplottet werden einfärben
            $CfgDaten['chart']['backgroundColor']  = "#ddeedd";
			//$CfgDaten['legend']['backgroundColor']  = "#f6f6f6";
            $CfgDaten['legend']['align']  = "center";
            if ($this->debug) echo "Background set\n";

	    	//$CfgDaten['plotOptions']['spline']['color']     =	 '#FF0000';

			$CfgDaten['ContentVarableId'] = $variableIdChartHTML ;
			$CfgDaten['Ips']['ChartType'] = 'Highcharts'; // Highcharts oder Highstock (default = Highcharts)
			$CfgDaten['StartTime']        = $this->GetGraphStartTime();
			$CfgDaten['EndTime']          = $this->GetGraphEndTime();
			$CfgDaten['RunMode']          = "file"; 	// file, script, popup

			// Serienübergreifende Einstellung für das Laden von Werten
			$CfgDaten['AggregatedValues']['HourValues']     = -1;      // ist der Zeitraum größer als X Tage werden Stundenwerte geladen, -1 immer alle Wete laden


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
            if ($this->debug) 
                {
                echo "Chart Default Configuration : \n";
                print_r($CfgDaten);
                }
			switch (GetValue($variableIdPeriod)) {
				case IPSRP_PERIOD_HOUR:
                    if ($this->debug) echo "IPSRP_PERIOD_HOUR\n";	 
                    $aggType = -1; 
                    break;	// es werden alle Werte bearbeitet
				case IPSRP_PERIOD_DAY:   
                    if ($this->debug) echo "IPSRP_PERIOD_DAY\n";
                    $aggType = -1; 
                    break;   // es werden alle Werte bearbeitet
				case IPSRP_PERIOD_WEEK:  
                    if ($this->debug) echo "IPSRP_PERIOD_WEEK\n";
                    $aggType = 0; 
                    break;   // es wird vorher stündlich aggregiert 
				case IPSRP_PERIOD_MONTH: 
                    if ($this->debug) echo "IPSRP_PERIOD_MONTH\n";
                    $aggType = 1; 
                    break;	
				case IPSRP_PERIOD_YEAR:  
                    if ($this->debug) echo "IPSRP_PERIOD_YEAR\n";
                    $aggType = 1; 
                    break;	// es wird vorher täglich aggregiert
				default:
				   trigger_error('Unknown Period '.GetValue($variableIdPeriod));
			}

            /* Komplette GetValueConfiguration durchgehen, das sind die linken Auswahlfelder, hier sollte nur eines aktiv sein !!!!
             * valueIdx geht vpn 0 bis x und valuedata ist die aktuelle config des kanals mit
             *    IPSRP_PROPERTY_NAME			Name
             *    IPSRP_PROPERTY_DISPLAY  	true,false ob Anzeige
             *		IPSRP_PROPERTY_VALUETYPE	ValueType Total, Detail, Other aber auch Einheiten für die Anzeige
             *
             * die eigentliche Configuration für das Zeichnen der Graphen wird in CompileConfiguration erstellt
             *
             */
			foreach ($this->valueConfig as $valueIdx=>$valueData)
				{
                if ($this->debug && false) 
                    {
                    echo "foreach evaluate ".$valueData[IPSRP_PROPERTY_VALUETYPE]."   ".$valueData[IPSRP_PROPERTY_DISPLAY]." (1 continue setup serie)\n";
                    print_r($valueData);
                    }
				$valueType = $valueData[IPSRP_PROPERTY_VALUETYPE];
				if ($valueData[IPSRP_PROPERTY_DISPLAY])            /* in der Config Tabelle aktiv eingestellt */
					{   /* hier sollte nur einmal eine Anzeige aktiv sein */
                    if ($this->debug) echo "-------------------------------\n";
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

					if ($this->debug) echo str_pad($valueData["Name"],35)." Anzeige vom Chart Typ : ".$chartType."   ".$valueIdx." durchgehen. ".(GetValue($variableIdValueDisplay)?"für Anzeige konfiguriert":"")."  \n";
					//print_r($valueData);
					
					switch ($chartType) /* Auswahlfeld erste Zeile */
						{
						case IPSRP_TYPE_OFF:
							if (GetValue($variableIdValueDisplay))    /* im linken Auswahlfeld selektiert */
							  	{
                                if ($this->debug) echo "selected IPSRP_TYPE_OFF:\n";
								SetValue($variableIdChartHTML, '');
								}
							return;
						case IPSRP_TYPE_STACK:
						case IPSRP_TYPE_STACK2:
							if (GetValue($variableIdValueDisplay))    /* im linken Auswahlfeld selektiert */
								{
                                if ($this->debug) echo "selected IPSRP_TYPE_STACK/STACK2:\n";
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
                                if ($this->debug) echo "selected IPSRP_TYPE_PIE:\n";
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
                            if (GetValue($variableIdValueDisplay)) 
                                {
                                $CfgDaten['plotOptions']['series']['stacking']     =	 'normal';
                                $displaypanel=$associationsValues[$valueIdx];   /* welches Feld in getConfiguration */
                                $this->compileConfiguration($CfgDaten,$report_config[$displaypanel], $chartType);          // CfgDaten is pointer
                                }                         
							if (GetValue($variableIdValueDisplay) && false)    /* im linken Auswahlfeld selektiert */
								{
                                if ($this->debug) echo "selected IPSRP_TYPE_WATT:\n";
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
                            if (GetValue($variableIdValueDisplay)) 
                                {
                                $displaypanel=$associationsValues[$valueIdx];   /* welches Feld in getConfiguration */
                                $this->compileConfiguration($CfgDaten,$report_config[$displaypanel], $chartType);          // CfgDaten is pointer
                                }                        
							if (GetValue($variableIdValueDisplay) && false)    /* im linken Auswahlfeld selektiert */
								{
                                if ($this->debug) echo "selected IPSRP_TYPE_EURO:\n";
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
                            if (GetValue($variableIdValueDisplay)) 
                                {
                                $displaypanel=$associationsValues[$valueIdx];   /* welches Feld in getConfiguration */
                                $this->compileConfiguration($CfgDaten,$report_config[$displaypanel], $chartType);          // CfgDaten is pointer
                                }

							if (GetValue($variableIdValueDisplay) && false)    /* im linken Auswahlfeld selektiert */
								{
								$yaxis=array();                        /* Einstellungen der yaxis für alle einzelne Graphen sammeln */
								$i=0; $j=0;
								//echo " ---wird angezeigt ...\n";
								$displaypanel=$associationsValues[$valueIdx];   /* welches Feld in getConfiguration */
                                if ($this->debug) echo "selected IPSRP_TYPE_KWH (".json_encode($report_config[$displaypanel]).") : -> implemented\n";
								
                                if (isset($report_config[$displaypanel]["title"])) $CfgDaten['title']['text']    = $report_config[$displaypanel]["title"];
                                else $CfgDaten['title']['text']    = $displaypanel;

                                if (isset($report_config[$displaypanel]["type"])) $CfgDaten['chart']['type'] = $report_config[$displaypanel]["type"];
                                else $CfgDaten['chart']['type'] = "line";

								//$CfgDaten['plotOptions']['series']['step']     = "left";                                // plotOptions: {    series: {         step: 'left' // or 'center' or 'right'     }    }

								/* zuerst yaxis erfassen und schreiben */
								foreach ($report_config[$displaypanel]['series'] as $name=>$defserie)
								    {
								    /* Name sind die einzelnen Kurven und defserie die Konfiguration der einzelnen Kurven */
                                    if ($this->debug)
                                        { 
								        echo "     Kurve : ".$name." \n";
								        print_r($defserie); echo "\n";
                                        }
                                    $serie['name'] = $name;

                                    if (isset($report_config[$displaypanel]["step"])) $serie['step'] = $report_config[$displaypanel]["step"];

                                    if (isset($report_config[$displaypanel]["type"])) $serie['type'] = $report_config[$displaypanel]["type"];
    								else $serie['type'] = $defserie['type'];
    								
                                    $serie['Id']   = $defserie['Id'];
								 	//if ($defserie['Unit']=='$')            /* Statuswerte */
									if ($defserie[IPSRP_PROPERTY_VALUETYPE]==IPSRP_VALUETYPE_STATE)
								 	    {
                                        if ($this->debug) echo "Statuswerte von ".$serie['Id']." Werte werden automatisch ergänzt.\n";
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
							    }           // ende if selected Channel
							break;
						default:
                            break;
					    }           // ende switch charttype
				    }           //  ende if Channel in der Config Tabelle aktiv eingestellt 
			    }       // ende foreach
			if ($this->debug) 
                {
                echo "Evaluiere CfgDaten in (".$CfgDaten["ContentVarableId"].")  ".IPS_GetName($CfgDaten["ContentVarableId"]).": result in ".$CfgDaten["RunMode"]."\n";
                echo "   Darstellung von ".date("d.m.y H:i:s",$CfgDaten["StartTime"])." bis ".date("d.m.y H:i:s",$CfgDaten["EndTime"])." \n";
                //print_r($CfgDaten["series"]);
                foreach ($CfgDaten["series"] as $index => $SerienEintrag)
                    {
                    if (isset($SerienEintrag["Id"])) echo "   Serie ".$index."  ".$SerienEintrag["Id"]."    ".IPS_GetName($SerienEintrag["Id"])."/".IPS_GetName(IPS_GetParent($SerienEintrag["Id"]))."\n";    
                    else 
                        {
                        echo "    Serie ".$index."  mit einzelnen Datenobjekten. Insgesamt ".count($SerienEintrag["data"])." Eintraege. \n";
                        //print_R($SerienEintrag);
                        }
                    }  
                print_r($CfgDaten);
                }
			if (!array_key_exists('series', $CfgDaten)) 
                {
				SetValue($variableIdChartHTML, '');
				return;
			    }

			// Create Chart with Config File
			IPSUtils_Include ("IPSHighcharts.inc.php", "IPSLibrary::app::modules::Charts::IPSHighcharts");
			$CfgDaten    = CheckCfgDaten($CfgDaten);
			//if ($this->debug) print_r($CfgDaten);                 // zuviele Daten
			$sConfig     = CreateConfigString($CfgDaten);           // IPSHighCharts function        
			$tmpFilename = CreateConfigFile($sConfig, 'IPSPowerControl');    
			if ($this->debug) 
                {
                echo $sConfig;            // String im Highcharts Format, die CfgDaten wurden bereits angereichert um die Daten (Zeitreihe) der Serien IDs
                // $temp = json_decode($sConfig, true);  echo "\n\nAusgabe von Sconfig als array :\n";  print_r($temp);   // laesst sich nicht dekodieren
                echo "\n\n Obiger String wird unter $tmpFilename gespeichert.\n";
                }
			WriteContentWithFilename ($CfgDaten, $tmpFilename);      
		    }

        /* compile the Highchart Config File 
         * CfgDaten             wird als Pointer übergeben, ist die Highcharts Config
         * $report_config       ist die eigentliche Konfiguration aus dem Konfig File
         *
         *  beinhaltet:
         *      title
         *      type
         *      aggregate
         *      series array
         *          name array
         *              Id
         *
         * $displaypanel        =$associationsValues[$valueIdx];   welches Feld in getConfiguration 
         *
         * $report_config[] steuert die Anzeige
         *      configuration
         *      configseries
         *      Module
         *      series
         *      title,type,aggregate
         *
         * Bei Easy und Eaysytrend wird eine zusätzliche Tabelle geschrieben
         *
         *
         */
        
        private function compileConfiguration(&$CfgDaten,$report_config,$chartType=IPSRP_TYPE_KWH)
            {
            /* Konfiguration vorverarbeiten, series kann automatisch erstellt werden 
             * funktioniert für EASYCHART und EASYTREND
             * EASYCHART verweist in einem Array auf anzuzeigende Depotnamen. Der Link geht je Depotnamen auf die Depotkonfiguration als jso encoded. Diese könnten auch variable erstellt werden
             *
             */
            $ReportDataSelectorID = IPS_GetObjectIdByName("ReportDataSelector", $this->categoryIdData); 
            $select=GetValue($ReportDataSelectorID);
            $selectAssociation=array();

            if (isset($report_config["configuration"]))                                 // die Serien können für die Charts Darstellung  manuell ausgewählt werden, keine vorkonfigurierte verwenden
                {
                unset ($report_config["series"]);                                       // selber erstellen
                if ($this->debug) echo "compileConfiguration: alternative Configuration at Easycharts for Series Display detected, create series from config var, use selection $select:\n";
                foreach ($report_config["configuration"] as $index=>$configID)
                    {
                    $selectAssociation[]=$index;    
                    }
                if (isset($report_config[IPSRP_PROPERTY_VALUETYPE])) $valueType=$report_config[IPSRP_PROPERTY_VALUETYPE];
                else $valueType="Euro";
                if (isset($selectAssociation[$select])===false) 
                    {
                    if ($this->debug) echo "Kein Eintrag, Select $select falsch. Mit 0 beginnen. ".json_encode($selectAssociation)."\n";
                    $select=0;
                    }
                $config=GetValue($report_config["configuration"][$selectAssociation[$select]]);         // config bzw configArray verweist auf das ausgewählte Musterdepot
                $configArray=json_decode($config,true);
                //print_R($configArray);
                foreach ($configArray as $index => $entry)
                    {
                    if ( (isset($entry["Name"])) && ($entry["Name"] != "") ) $name=$entry["Name"];
                    else $name=$index;
                    $result["Id"]=$entry["OID"];
                    $result[IPSRP_PROPERTY_VALUETYPE]=$valueType;
                    $report_config["series"][$name]=$result;
                    }
                }
            if (isset($report_config["configSeries"]))                              // die Serie kann für die Charts Darstellung  manuell ausgewählt werden, keine vorkonfigurierte verwenden
                {
                unset ($report_config["series"]);                                       // selber erstellen
                if ($this->debug) echo "compileConfiguration: alternative Configuration at Easytrend for Series Display detected, create series from config var, use selection $select:\n";
                foreach ($report_config["configSeries"] as $index=>$configID)
                    {
                    $selectAssociation[]=$index;    
                    }
                if (isset($report_config[IPSRP_PROPERTY_VALUETYPE])) $valueType=$report_config[IPSRP_PROPERTY_VALUETYPE];
                else $valueType="Euro";  
                if (isset($selectAssociation[$select])===false) 
                    {
                    if ($this->debug) echo "Kein Eintrag, Select $select falsch. Mit 0 beginnen. ".json_encode($selectAssociation)."\n";
                    $select=0;
                    }                
                $config=array();              
                $config[$selectAssociation[$select]]=$report_config["configSeries"][$selectAssociation[$select]];
                //print_R($config);
                foreach ($config as $index => $entry)
                    {
                    if ( (isset($entry["Name"])) && ($entry["Name"] != "") ) $name=$entry["Name"];
                    else $name=$index;
                    $result["Id"]=$entry["Id"];                                     // muss mindestens vorhandens ein
                    $result[IPSRP_PROPERTY_VALUETYPE]=$valueType;

                    $result["display"]="normal";                                    // normale Kurve
                    $report_config["series"][$name]=$result;    
                    $result["display"]="meansroll";                                 // Mittelwert Woche
                    $report_config["series"][$name."-means"]=$result;
                    $result["display"]="meansrollmonth";                            // noch eine Kurve dazunehmen, Mittelwert Monat
                    $report_config["series"][$name."-meansmonth"]=$result;                    
                    $result["display"]="trendmonth";                            // noch eine Kurve dazunehmen, Mittelwert Monat
                    $report_config["series"][$name."-trendmonth"]=$result;                     
                    }
                }

            $yaxis=array();                        /* Einstellungen der yaxis für alle einzelne Graphen sammeln */

            $i=0; $j=0;
            if ($this->debug) 
                {
                echo "compileConfiguration aufgerufen:\n";
                if (isset($report_config["title"])) echo "selected Report ".$report_config["title"].".\n";
                else echo "selected Report (".json_encode($report_config).") : -> implemented\n";
                print_R($CfgDaten);
                echo "Es wurden folgende Linien definiert:\n";
                print_R($report_config['series']);
                }
            
            if (isset($report_config["title"])) $CfgDaten['title']['text']    = $report_config["title"];
            else $CfgDaten['title']['text']    = "unknown title";

            if (isset($report_config["type"])) $CfgDaten['chart']['type'] = $report_config["type"];
            else $CfgDaten['chart']['type'] = "line";

			// Serienübergreifende Einstellung für das Laden von Werten
			if ( (isset($report_config["aggregate"])) || ($chartType == IPSRP_TYPE_WATT) ) $CfgDaten['AggregatedValues']['HourValues']     = 0;      // ist der Zeitraum größer als X Tage werden Stundenwerte geladen, -1 immer alle Wete laden
            if ( (isset($report_config["aggregate"])) && ($report_config["aggregate"]==false) ) $CfgDaten['AggregatedValues']['HourValues']     = -1;
            
            //$CfgDaten['plotOptions']['series']['step']     = "left";                                // plotOptions: {    series: {         step: 'left' // or 'center' or 'right'     }    }
 
            $moduleDefault="default";
            if (isset($report_config["Module"])) $moduleDefault=strtoupper($report_config["Module"]);

            $archiveOps = new archiveOps();  

            /* zuerst yaxis erfassen und schreiben */
            $index=0;
            foreach ($report_config['series'] as $name=>$defserie)
                {
                $serie=array();         // imer neu initialisiseren
                $scale=0;
                /* Name sind die einzelnen Kurven und defserie die Konfiguration der einzelnen Kurven */
                if ($this->debug)
                    { 
                    echo "     Kurve : ".$name." \n";
                    print_r($defserie); echo "\n";
                    }
                $serie['name'] = $name;

                if (isset($report_config["step"])) $serie['step'] = $report_config["step"];

                if (isset($report_config["type"])) $serie['type'] = $report_config["type"];
                else $serie['type'] = $defserie['type'];
                
                if (isset($defserie["Module"])) $module=strtoupper($defserie["Module"]);
                else $module=$moduleDefault;

                $analyseConfig = ["StartTime"=>$CfgDaten["StartTime"],"EndTime"=>$CfgDaten["EndTime"]];
                switch ($module)                    /* für EASY und EASYTREND die Serie der Daten selbst schreiben und nicht auf ein Archiv verweisen */
                    {
                    case "EASYTREND":
                    case "EASY":
                        $oid=$defserie['Id'];
                        $resultAll = $archiveOps->analyseValues($oid,$analyseConfig,$this->debug);
                        if ($module=="EASYTREND") 
                            {
                            //if ($this->debug) print_r($resultAll);

                            if (strtoupper($defserie["display"])=="MEANSROLL") 
                                {
                                if ($this->debug) echo "Modul Easytrend ".count($result)." Werte aus dem laufenden Mittelwert für Wochen verarbeiten.\n";
                                $result=$resultAll["Description"]["MeansRoll"];
                                }
                            elseif (strtoupper($defserie["display"])=="MEANSROLLMONTH") 
                                {
                                if ($this->debug) echo "Modul Easytrend ".count($result)." Werte aus dem laufenden Mittelwert für Monateverarbeiten.\n";
                                $result=$resultAll["Description"]["MeansRollMonth"];
                                }
                            elseif (strtoupper($defserie["display"])=="TRENDMONTH") 
                                {
                                if ($this->debug) echo "Modul Easytrend ".count($result)." Werte aus dem laufenden Mittelwert für Monateverarbeiten.\n";
                                $result=$resultAll["Description"]["Interval"]["Month"]["MeansVar"];
                                //print_r($result);
                                }
                            else 
                                {
                                $result=$resultAll["Values"];                 // true mit Debug, aus dem Archiv geladene Datenserie nicht einschränken
                                if ($this->debug) echo "Modul Easytrend ".count($result)." Werte verarbeiten.\n";
                                }
                            $scale=1;
                            }
                        else 
                            {
                            $result=$resultAll["Values"];
                            $letzte=array_key_last($result);
                            $scale=100/$result[$letzte]["Value"];
                            }                            
                        if ($this->debug) echo "      Darstellung der Daten für die Anzeige von $module:\n";

                        foreach ($result as $entry)
                            {
                            //if ($scale==0) $scale=100/$entry["Value"];
                            if ($this->debug) echo "      ".date("d.m. H:i:s",$entry["TimeStamp"])."  ".($entry["Value"]*$scale)."\n";
                            $serie['data'][] = ["TimeStamp" =>  $entry["TimeStamp"],"y" => ($entry["Value"]*$scale)];
                            }
                        //print_R($result);
                        //$serie['Id']   = $defserie['Id'];
                        break;
                    default:
                        if (IPS_ObjectExists($defserie['Id']))
                            {
                            $serie['Id']   = $defserie['Id'];
                            }
                        else echo "Fehler, ".$defserie['Id']." nicht vorhanden.\n";
                        break;
                    }
                //if ($defserie['Unit']=='$')            /* Statuswerte */
                if ($defserie[IPSRP_PROPERTY_VALUETYPE]==IPSRP_VALUETYPE_STATE)
                    {
                    if ($this->debug) echo "Statuswerte von ".$serie['Id']." Werte werden automatisch ergänzt.\n";
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
                if ($this->debug) echo "Serie $index ermittelt, wird abgespeichert.\n";
                $CfgDaten['series'][$index++] = $serie;
                }   /* ende foreach */

            /* Plot Bands für bessere Lesbarkeit  ["StartTime"=>$CfgDaten["StartTime"],"EndTime"=>$CfgDaten["EndTime"]] 
                * Starttime, Endtime, periode
                * minimum Display resolution is day, if periode is week, start end of week is necessary
                * Weekday when week starts
                */
            $time=$CfgDaten["StartTime"]; $endTime = $CfgDaten["EndTime"]; 
            $daysToDisplay=round(($endTime-$time)/60/60/24,0);
            //echo $daysToDisplay;
            if ($daysToDisplay>20) $periode="Week";              // is Day/Week/Month  , abhängig von der Auswahl der Periode, oder Abstand Starttime-Endtime in Tagen: zB 20
            else $periode="Day"; 
            $index=0; $color=['#dFdFdF','#eFeFeF'];
            $startOfThisDay = $time; 
            $CfgDaten['xAxis']['type'] = "datetime";
            do                                                  // plot bands for days or weeks (weeks have less intervals) time has to be increased accordingly
                {
                $nextday=$time+60*60*24;
                $startOfNextDay=mktime(0,0,0,date("m",$nextday), date("d",$nextday), date("Y",$nextday)); 
                $startOfNextWeek=$startOfNextDay; $nextweek=$nextday;
                $weekDay = date("D",$startOfNextDay);           // get week day of next day                       
                while (date("D",$startOfNextWeek)!= "Sun")
                    {
                    $startOfNextWeek = $startOfNextWeek + 60*60*24;  
                    $nextweek = $nextweek +60*60*24;
                    }
                if ($periode != "Day") 
                    {
                    $startOfNextDay = $startOfNextWeek;
                    $nextday   = $nextweek;
                    }                        
                if ($startOfNextDay>$endTime) $startOfNextDay=$endTime;

                $CfgDaten['xAxis']['plotBands'][$index]['from'] = "@" . $this->CreateDateUTC($startOfThisDay) ."@";
                $CfgDaten['xAxis']['plotBands'][$index]['to'] = "@" . $this->CreateDateUTC($startOfNextDay) ."@";
                $CfgDaten['xAxis']['plotBands'][$index]['color'] = $color[($index % 2)];
                if ($periode == "Day") $CfgDaten['xAxis']['plotBands'][$index]['label']['text'] = date("D",$time);
                else $CfgDaten['xAxis']['plotBands'][$index]['label']['text'] = date("W",$time);
                $CfgDaten['xAxis']['plotBands'][$index]['zIndex'] = 3;                                                          // how far in the foreground is shall be plotted

                $time=$nextday; $index++;
                $startOfThisDay = $startOfNextDay;
                } while ($time < ($endTime));
                //} while ($time < ($endTime+60*60*2));
            //echo "Index $index";                                                // is this what you see, ende plot bands

            /* für EASY eine zusätzliche Tabelle schreiben, bei EASYTREND bleibt die letzte Tabelle stehen, reine Selenium Easycharts Funktion 
             * der Zeitraum für die Analyse wird duch den Zeitraum der Spanne für die Darstellung gewählt 
             *      $configArray            Auswahl des Musterdepots für die Darstellung in der Tabelle
             *
             *
             */
            switch ($module)                    
                {
                case "EASY": 

                    IPSUtils_Include ("Guthabensteuerung_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");                
                    IPSUtils_Include ("Selenium_Library.class.php","IPSLibrary::app::modules::Guthabensteuerung");                
                    $seleniumEasycharts = new SeleniumEasycharts();                         
                    $orderbook=$seleniumEasycharts->getEasychartConfiguration();
                    $shares=array();
                    foreach ($configArray as $index => $share)
                        {
                        $shares[$index]=$share;
                        $shares[$index]["ID"]=$index;
                        }
                    $resultShares=array();
                    foreach($shares as $index => $share)                    //haben immer noch eine gute Reihenfolge, wird auch in resultShares übernommen
                        {
                        //print_R($share);
                        $oid = $share["OID"];
                        $result = $archiveOps->analyseValues($oid,$analyseConfig,$this->debug);                 // true mit Debug
                        if ($this->debug) 
                            {
                            if ( (isset($share["Name"])) && ($share["Name"] != "") ) echo "Bearbeite Share \"".$share["Name"]."\" :\n";
                            else echo "Bearbeite Share \"".$share["ID"]."\" :\n";
                            print_R($result["Description"]["Interval"]);
                            }
                        $resultShares[$share["ID"]]=$result;
                        $resultShares[$share["ID"]]["Info"]=$share;
                        if (isset($orderbook[$share["ID"]])) $resultShares[$share["ID"]]["Order"]=$orderbook[$share["ID"]];
                        $archiveOps->addInfoValues($oid,$share);
                        } 
                    //print_r($resultShares);
                    $wert = $seleniumEasycharts->writeResultAnalysed($resultShares,true,2);                       // true for html, 1 as size
                    $DataTableID   = IPS_GetObjectIdByName("ReportDataTable",   $this->categoryIdData);  
                    //echo "gefunden $DataTableID" ;                  
                    SetValue($DataTableID,$wert);                                                                           // eine schöne Tablee schreiben
                    break;
                default:
                    break;
                }
            
            
            
            
            
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
                if ($unit==IPSRP_VALUETYPE_CONTROL)
                    {
                    $CfgDaten['yAxis'][$index]['title']['text'] = "Stellwert";
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


            }

		// ------------------------------------------------------------------------
		// CreateDateUTC, hier in der Klasse noch einmal definiert, gibt es in IPSHighcharts.inc
		//    Erzeugen des DateTime Strings für Highchart-Config
		//    IN: $timeStamp = Zeitstempel
		//    OUT: Highcharts DateTime-Format als UTC String ... Date.UTC(1970, 9, 27, )
		//       Achtung! Javascript Monat beginnt bei 0 = Januar
		// ------------------------------------------------------------------------
		function CreateDateUTC($timeStamp)
			{
			$monthForJS = ((int)date("m", $timeStamp))-1 ;	// Monat -1 (PHP->JS)
			return "Date.UTC(" . date("Y,", $timeStamp) .$monthForJS. date(",j,H,i,s", $timeStamp) .")";
			}

		private function GetYAxisIdx($CfgDaten, $text) 
            {
			$maxIdx = -1;
			if (array_key_exists('yAxis', $CfgDaten)) 
                {
				foreach ($CfgDaten['yAxis'] as $idx=>$data) 
                    {
					$maxIdx = $idx;
					if ($data['title']['text'] == $text) 
                        {
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





	/**@defgroup ipshighcharts IPSHighcharts
	 * @ingroup charts
	 * @{
	 *
	 * @file          IPSHighcharts.ips.php
	 * @author        khc
	 * @version
	 *  Version 2.50.0, 01.06.2012 khc: Initiale Version 2.02 im Forums Thread<br/>
	 *  Version 2.50.1, 05.10.2012  ab: Integration IPSLibrary<br/>
	 *
	 * IPSHighcharts, ermöglich Darstellung von Charts im Webfront mit Hilfe von "Highcharts" (www.highcharts.com)
	 *
     *
     */

	//ToDo:
	// FEATURE: Plotbands. Timestamp in From und To
	// Fehlerquelle: AggType ist "Größer" als der angezeigte Zeitraum
	// vielleicht alles als cfg direkt json_encoden und nicht jedes Teil einzeln
	//--------------------------------------------------------------------------------------------------------------------------------
	// Für die Darstellung der Graphen wird das HTML5/JS Framework "Highcharts" der Fa. Highslide Software verwendet (www.highcharts.com)
	// Alle Rechte dieses Frameworks liegen bei Highslide Software.
	// 'Highcharts' kann unter folgenden Bedinungen kostenlos eingesetzt werden:
	//  * Namensnennung - Sie müssen den Namen des Autors/Rechteinhabers in der von ihm festgelegten Weise nennen.
	//  * Keine kommerzielle Nutzung - Dieses Werk bzw. dieser Inhalt darf nicht für kommerzielle Zwecke verwendet werden.
	// Download: wwww.highcharts.com/download/ ... und die Dateien einfach in das Webfront (Es sollte ein V 2.2 oder höher verwendet werden.
	// Demos: http://www.highcharts.com/demo/
	// API: http://www.highcharts.com/ref/
	//--------------------------------------------------------------------------------------------------------------------------------
	// Changelog:
	// --- V2.00 ---------------------------------------------------------------------------------------------------------------------
	// 10/2012     AB    NEU      Integration IPSLibrary
	// 04/2012     KHC   REFACT   Umfangreiches Überarbeiten der Highchart-Script Funktionen.
	// bis                        Integration der meisten Original-Highcharts-Options als PHP-Array (siehe http://www.highcharts.com/ref)
	// 05/2012                    Highcharts-Options "lang" aus IPS_Template.php in Highcharts-Script verschoben
	//	--- V2.01 ---------------------------------------------------------------------------------------------------------------------
	// 07.05.2012  KHC   NEU      Test mit Integration Highstock. Neuer Parameter ['Ips']['ChartType'] = 'Highcharts' oder 'Highstock'
	// 07.05.2012  KHC   NEU      IPS_Template.php auf jquery 1.7.2 geändert
	// 07.05.2012  KHC   FIX      krsort durch array_reverse getauscht, da krsort Probleme beim json_encode macht
	// 08.05.2012  KHC   REFACT   intern noch mehr auf Arrays umgestellt und etwas umstrukturiert
	// 09.05.2012  KHC   NEU      über 'CreateConfigFileByPathAndFilename($stringForCfgFile, $path, $filename)' kann eine Tmp_datei mit bel. Namen geschrieben werden
	// 10.05.2012  KHC   FIX      Fehler beim Auswerten der AggregatedValues behoben (ReadDataFromDBAndCreateDataArray)
	// 12.05.2012  KHC   FIX      Tooltip für "ReplaceValues" korrigiert
	// 12.05.2012  KHC   CHANGE   Start- und Endzeitpunkt der X-Achse wurde automatisch um 5 Minuten korrigiert -> dies wurde entfernt
	// 12.05.2012  KHC   NEU      mit ['xAxis']['min']=false und ['xAxis']['min']=false kann festeglegt werden dass Min oder Max nicht autom. festgelegt werden
	//	--- V2.02 ---------------------------------------------------------------------------------------------------------------------
	// 13.05.2012  KHC   FIX      RunType=file: Wenn Highstock vorgewählt wurde wurde das tmp File nicht in die Highstock-Verzeichnis geschrieben
	// 16.05.2012  KHC   NEU      Integration Highstock: ['Navigator'], ['rangeSelector'] und ['scrollbar']
	// 18.05.2012  KHC   FIX      Integration Highstock: Zusätzliche series.type 'candlestick' und 'ohlc' erlauben
	// 19.05.2012  KHC   NEU      Neue Parameter ['Ips']['Dashboard'] für die Darstellung im Dashboard

	//--------------------------------------------------------------------------------------------------------------------------------

    class HighCharts
        {

        private $version = "2.03"; 
        private $versionDate = "05.10.2012";

        function __construct()
            {


            }

        // ------------------------------------------------------------------------
        // WriteContentWithFilename
        //    Mit dieser Funktion wird der Content-String geschrieben.
        //    IN: $cfg = ..
        //    IN: $tmpFilename = Der Dateiname welche die Config Daten enthält
        // ------------------------------------------------------------------------
        function WriteContentWithFilename($cfg, $tmpFilename)
            {
            $this->DebugModuleName($cfg,"WriteContentWithFilename");

            if ($tmpFilename != "")
                {
                SetValue($cfg['ContentVarableId'],
                            $this->GetContentVariableString ($cfg, "CfgFile", $tmpFilename));
                }
            else
                SetValue($cfg['ContentVarableId'], 'Falsche Parameter beim Funktionsaufruf "WriteContentTextbox"');
            }

        // ------------------------------------------------------------------------
        // WriteContentWithScriptId
        //    Mit dieser Funktion wird der Content-String geschrieben.
        //    IN: $cfg = ..
        //    IN: $scriptId = Die Script Id welche den ConfigString enthält.
        // ------------------------------------------------------------------------
        function WriteContentWithScriptId($cfg, $scriptId)
            {
            $this->DebugModuleName($cfg,"WriteContentWithScriptId");

            if ($cfg['RunMode'] == "popup")
                {
                WFC_SendPopup($cfg['WebFrontConfigId'],
                            $cfg['WFCPopupTitle'] ,
                            $this->GetContentVariableString ($cfg, "ScriptId", $scriptId));
                }
            else
                {
                SetValue($cfg['ContentVarableId'],
                    $this->GetContentVariableString ($cfg, "ScriptId", $scriptId));
                }
            }
        
        // ------------------------------------------------------------------------
        // 05.10.2012  ab:  Adapted Path to Templates
        function GetContentVariableString($cfg, $callBy, $callIdent)
            {
            $chartType = $cfg['Ips']['ChartType'];
            $height = $cfg['HighChart']['Height'] + 16;

            if (isset($cfg['Ips']['Dashboard']['Ip']) && isset($cfg['Ips']['Dashboard']['Port']))
                {
                $s = "http://" . $cfg['Ips']['Dashboard']['Ip'] . ":" . $cfg['Ips']['Dashboard']['Port'] .
                    "/user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy="	. $callIdent . " " .
                    "width='100%' height='". $height ."' frameborder='1' scrolling='no'";
                }
            else
                {
                $s = "<iframe src='./user/IPSHighcharts/IPSTemplates/$chartType.php?$callBy="	. $callIdent . "' " .
                    "width='100%' height='". $height ."' frameborder='0' scrolling='no'></iframe>";
                }
            return $s;
            }

        // ------------------------------------------------------------------------
        // CreateConfigFile
        //    Erzeugt das tmp-Highcharts Config-File mit der $id als Dateinamen
        //    IN: $stringForCfgFile = String welcher in das File geschrieben wird
        // ------------------------------------------------------------------------
        function CreateConfigFile($stringForCfgFile, $id, $charttype = 'Highcharts')
        {
            $path = "webfront\user\IPSHighcharts\\" . $charttype;
            $filename = $charttype . "Cfg$id.tmp";

            return $this->CreateConfigFileByPathAndFilename($stringForCfgFile, $path, $filename);
        }

        // ------------------------------------------------------------------------
        // CreateConfigFileByPathAndFilename
        //    Erzeugt das tmp-Highcharts Config-File
        //    IN: $stringForCfgFile = String welcher in das File geschrieben wird
        // 		 $path, $filename = Pfad un Name des Tmp-Files welches erzeugt werden soll
        // ------------------------------------------------------------------------
        function CreateConfigFileByPathAndFilename($stringForCfgFile, $path, $filename)
        {
            // Standard-Dateiname .....
            $tmpFilename = IPS_GetKernelDir() . $path . "\\" . $filename;

            // schreiben der Config Daten
            $handle = fopen($tmpFilename,"w");
            fwrite($handle, $stringForCfgFile);
            fclose($handle);

            return $tmpFilename;
        }

        // ------------------------------------------------------------------------
        // CheckCfgDaten
        //    Aufruf bei jedem Cfg-Start
        //    IN: $cfg = ..
        //    OUT: korrigierte cfg
        // ------------------------------------------------------------------------
        function CheckCfgDaten($cfg)
            {
            $this->DebugModuleName($cfg,"CheckCfgDaten");

            global $_IPS;

            // Debugging
            $this->IfNotIssetSetValue($cfg['Ips']['Debug']['Modules'], 			false);
            $this->IfNotIssetSetValue($cfg['Ips']['Debug']['ShowJSON'], 			false);
            $this->IfNotIssetSetValue($cfg['Ips']['Debug']['ShowJSON_Data'], 	false);
            $this->IfNotIssetSetValue($cfg['Ips']['Debug']['ShowCfg'], 			false);

            // ChartType
            $this->IfNotIssetSetValue($cfg['Ips']['ChartType'], 'Highcharts');

        if ($cfg['Ips']['ChartType'] != 'Highcharts' && $cfg['Ips']['ChartType'] != 'Highstock')
            die ("Abbruch! Es sind nur 'Highcharts' oder 'Highstock' als ChartType zulässig");

            // über WebInterface kommt der Aufruf wenn die Content-Variable aktualisiert wird
            if ($_IPS['SENDER'] != "WebInterface" && $cfg['RunMode'] != "popup")
                $cfg = $this->Check_ContentVariable($cfg, $_IPS['SELF']);

        return $cfg;
        }

        // ------------------------------------------------------------------------
        // CreateConfigString
        //    Erzeugt den für Higcharts benötigten Config String und gibt diesen als String zurück
        //    IN: $cfg = ..
        //    OUT: der erzeugte Config String
        // ------------------------------------------------------------------------
        function CreateConfigString($cfg)
            {
            $this->DebugModuleName($cfg,"CreateConfigString");

            $cfg = $this->CompatibilityCheck($cfg);
            $cfg = $this->CheckCfg($cfg);

            $cfgString = $this->GetHighChartsCfgFile($cfg);

            // Zusätzliche Config in Highchart Config hinzufügen
            $cfgString = $this->ReadAdditionalConfigData($cfg) . "\n|||\n" .  $cfgString;

            // Language Options aus IPS_Template.php hierher verschoben
            $cfgString .=  "\n|||\n". $this->GetHighChartsLangOptions($cfg);;

            return $cfgString;
            }

        function CompatibilityCheck($cfg)
            {
            $this->DebugModuleName($cfg,"CompatibilityCheck");

            // Series
            if (isset($cfg['Series']) && isset($cfg['series']))
                die ("Abbruch - Es düfen nicht gleichzeitig 'Series' und 'series' definiert werden.");
            if (isset($cfg['Series']) && !isset($cfg['series']))
                $cfg['series'] = $cfg['Series'];
            unset ($cfg['Series']);

            // Title
            if (isset($cfg['Title']) && !isset($cfg['title']['text']))
                $cfg['title']['text'] = $cfg['Title'];
            unset ($cfg['Title']);

            // SubTitle
            if (isset($cfg['SubTitle']) && !isset($cfg['subtitle']['text']))
                $cfg['subtitle']['text'] = $cfg['SubTitle'];
            unset ($cfg['SubTitle']);

            // SubTitleDateTimeFormat
            if (isset($cfg['SubTitleDateTimeFormat']) && !isset($cfg['subtitle']['Ips']['DateTimeFormat']))
                $cfg['subtitle']['Ips']['DateTimeFormat'] = $cfg['SubTitleDateTimeFormat'];
            unset ($cfg['SubTitleDateTimeFormat']);

            // yAxis
            if (isset($cfg['yAxis']))
            {
                $axisArr = array();
                foreach ($cfg['yAxis'] as $Axis)
                {
                    $cfgAxis = $Axis;

                    // Name
                    if (isset($Axis['Name']) && !isset($cfgAxis['title']['text']))
                        $cfgAxis['title']['text'] = $Axis['Name'];
                    unset ($cfgAxis['Name']);

                    // TickInterval
                    if (isset($Axis['TickInterval']) && !isset($cfgAxis['tickinterval']))
                            $cfgAxis['tickinterval'] = $Axis['TickInterval'];
                    unset ($cfgAxis['TickInterval']);

                    // Opposite
                    if (isset($Axis['Opposite']) && !isset($cfgAxis['opposite']))
                            $cfgAxis['opposite'] = $Axis['Opposite'];
                    unset ($cfgAxis['Opposite']);

                $axisArr[] = $cfgAxis;
                }
            $cfg['yAxis'] = $axisArr;
            }
            return $cfg;
        }

        // ------------------------------------------------------------------------
        // CheckCfg
        //    Prüft daKonfiguration und korrigiert und Vervollständigtdiese zum Teil
        //    IN: $cfg = ..
        //    OUT: der erzeugte Config String
        // ------------------------------------------------------------------------
        function CheckCfg($cfg)
            {
            $this->DebugModuleName($cfg,"CheckCfg");

            $cfg = $this->CheckCfg_Common($cfg);
            $cfg = $this->CheckCfg_AreaHighChart($cfg);
            $cfg = $this->CheckCfg_AggregatedValues($cfg);
            $cfg = $this->CheckCfg_StartEndTime($cfg);
            $cfg = $this->CheckCfg_Series($cfg);

            return $cfg;
        }

        // ------------------------------------------------------------------------
        // CheckCfg_Common
        //    wenn RunMode=Popup, prüfen der dazugehörigen Daten wie WebfrontConfigId, usw.
        //		und wenn RunMode=Popup, prüfen der dazugehörigen Daten wie WebfrontConfigId, usw.
        //    IN: $cfg
        //    OUT: korrigiertes $cfg
        // ------------------------------------------------------------------------
        function CheckCfg_Common($cfg)
            {
            $this->DebugModuleName($cfg,"CheckCfg_Common");

            if (!isset($cfg['series']))
                die ("Abbruch - Es wurden keine Serien definiert.");

            // Id des ArchiveHandler auslesen
            if (!isset($cfg['ArchiveHandlerId']) || $cfg['ArchiveHandlerId'] == -1)
            {
            $instances = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
                $cfg['ArchiveHandlerId'] = $instances[0];
            }
            // Prüfen des ArchiveHandlers
            $instance = @IPS_GetInstance($cfg['ArchiveHandlerId']);
            if ($instance['ModuleInfo']['ModuleID'] != "{43192F0B-135B-4CE7-A0A7-1475603F3060}")
                die ("Abbruch - 'ArchiveHandlerId' (".$cfg['ArchiveHandlerId'].") ist keine Instance eines ArchiveHandler.");

            if ($cfg['RunMode'] == "popup")
            {
                // keine Webfront Id
                if (!isset($cfg['WebFrontConfigId']))
                    die ("Abbruch - Konfiguration von 'WebFrontConfigId' fehlt.");

                // prüfen ob die übergebene Id ein WebFront ist
                $instance = @IPS_GetInstance($cfg['WebFrontConfigId']);
                if ($instance['ModuleInfo']['ModuleID'] != "{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}")
                    die ("Abbruch - 'WebFrontConfigId' ist keine WebFrontId");

                $this->IfNotIssetSetValue($cfg['WFCPopupTitle'], "");
            }

        return $cfg;
        }


        // ------------------------------------------------------------------------
        // Check_ContentVariable
        //    prüfen ob Angaben der Content Variable stimmen oder ob es das übergeordnete Element ist
        //    IN: $cfg
        //    OUT: korrigiertes $cfg
        // ------------------------------------------------------------------------
        function Check_ContentVariable($cfg, $scriptId)
            {
            $this->DebugModuleName($cfg,"Check_ContentVariable");

            // wenn keine Id übergeben wurde wird das übergeordnete Objekt als Content verwendet
            if (!isset($cfg['ContentVarableId']) || $cfg['ContentVarableId'] <= 0)
                $cfg['ContentVarableId'] = IPS_GetParent($scriptId);

            $variable = @IPS_GetVariable($cfg['ContentVarableId']);
            if ($variable == false)
            die ("Abbruch - Content-Variable nicht gefunden.");

            if (isset($variable['VariableValue']['ValueType']) && ($variable['VariableValue']['ValueType'] != 3))
            die ("Abbruch - Content-Variable ist keine STRING-Variable.");

            if (isset($variable['VariableType']) && ($variable['VariableType'] != 3))
            die ("Abbruch - Content-Variable ist keine STRING-Variable.");
    
            if ($variable['VariableCustomProfile'] != "~HTMLBox")
                die ("Abbruch - Content-Variable muss als Profil '~HTMLBox' verwenden.");

            return $cfg;
            }

        // ------------------------------------------------------------------------
        // CheckCfg_AreaHighChart
        //
        //    IN: $cfg
        //    OUT: korrigiertes $cfg
        // ------------------------------------------------------------------------
        function CheckCfg_AreaHighChart($cfg)
            {
            $this->DebugModuleName($cfg,"CheckCfg_AreaHighChart");

            $this->IfNotIssetSetValue($cfg['HighChart']['Theme'], "");
            $this->IfNotIssetSetValue($cfg['HighChart']['Width'], 0);
            $this->IfNotIssetSetValue($cfg['HighChart']['Height'], 400);

            return $cfg;
        }

        // ------------------------------------------------------------------------
        // CheckCfg_StartEndTime
        //    Start- und Endzeit des gesamten Charts errechnen, und an jede Serie übergeben
        //    IN: $cfg
        //    OUT: korrigiertes $cfg
        // ------------------------------------------------------------------------
        function CheckCfg_StartEndTime($cfg)
            {
            $this->DebugModuleName($cfg,"CheckCfg_StartEndTime");

            $cfg['Ips']['ChartStartTime'] = $cfg['StartTime'];
            $cfg['Ips']['ChartEndTime'] = $cfg['EndTime'];

            $offsetExistsAtSerie = false;
            $Count = count($cfg['series']);

            for ($i = 0; $i < $Count; $i++)
            {
                $Serie = $cfg['series'][$i];

                // wenn für die Serie keine Start oder Endzeit übergeben würde wird der Standardwert genommen
                $this->IfNotIssetSetValue($Serie['StartTime'], $cfg['StartTime']);
                $this->IfNotIssetSetValue($Serie['EndTime'], $cfg['EndTime']);

                if ($Serie['StartTime'] < $cfg['Ips']['ChartStartTime'])
                    $cfg['Ips']['ChartStartTime'] = $Serie['StartTime'];
                if ($Serie['EndTime'] > $cfg['Ips']['ChartEndTime'])
                    $cfg['Ips']['ChartEndTime'] = $Serie['EndTime'];

                $Serie['Ips']['EndTimeString'] = date("/r", $Serie['EndTime']);
                $Serie['Ips']['StartTimeString']= date("/r", $Serie['StartTime']);

                $cfg['series'][$i] = $Serie;

                if (isset($Serie['Offset']) && $Serie['Offset'] != 0)
                    $offsetExistsAtSerie =true;
            }

            // wenn ein Offset definiert wurde gilt nur der global eingestellte Start und Endzeitpunkt
            if ($offsetExistsAtSerie = true)
        {
            $cfg['Ips']['ChartStartTime'] = $cfg['StartTime'];
                $cfg['Ips']['ChartEndTime'] = $cfg['EndTime'];
        }

            return $cfg;

        }

        // ------------------------------------------------------------------------
        // CheckCfg_Series
        //    prüfen der Serien
        //    IN: $cfg
        //    OUT: korrigiertes $cfg
        // ------------------------------------------------------------------------
        function CheckCfg_Series($cfg)
            {
            $this->DebugModuleName($cfg,"CheckCfg_Series");

            $Id_AH = $cfg['ArchiveHandlerId'];

            $series = array();
            foreach ($cfg['series'] as $Serie)
            {
                $VariableId = @$Serie['Id'];

                // hier wird nur geprüft ob Wert von Eingabe passen könnte (wenn vorhanden)
                if  (isset($Serie['AggType']) && ($Serie['AggType']<0 || $Serie['AggType']>4) )
                    die ("Abbruch - 'AggType' hat keinen korrekten Wert");

                $Serie['Ips']['IsCounter'] = $VariableId && (@AC_GetAggregationType($Id_AH, $VariableId) == 1);

                // über AggValue kann Min/Max oder Avg vorgewählt werden (zum Lesen der AggregValues)
                $this->IfNotIssetSetValue($Serie['AggValue'], "Avg");

                if ($Serie['AggValue'] != "Avg"
                    && $Serie['AggValue'] != "Min"
                    && $Serie['AggValue'] != "Max")
                    die ("Abbruch - 'AggValue' hat keinen gültigen Wert");

                // Offset für Darstellung von z.B. Monate und Vormonat in einem Chart
                $this->IfNotIssetSetValue($Serie['Offset'], 0);

                $this->IfNotIssetSetValue($Serie['ReplaceValues'], false);

                // Name (Kompatibilität aus V1.x)
                if (isset($Serie['Name']) && !isset($Serie['name']))
                    $Serie['name'] = $Serie['Name'];
                unset($Serie['Name']);

                $this->IfNotIssetSetValue($Serie['name'], "");

                // type & Parameter
                if (isset($Serie['type']) && isset($Serie['Param']))
                    die ("Abbruch - Definition von 'Param' und 'type' in Serie gleichzeitig nicht möglich.");
                if (!isset($Serie['type']) && !isset($Serie['Param']))
                    die ("Abbruch - Serie muss Definition von 'Param' oder 'type' enthalten.");

                // Mögliche Charttypen
                $allowedSeriesTypes = array();
                if ($cfg['Ips']['ChartType'] == 'Highcharts')
                    $allowedSeriesTypes = array('area','areaspline','bar','column','line','pie','scatter','spline');
                else if ($cfg['Ips']['ChartType'] == 'Highstock')
                    $allowedSeriesTypes = array('area','areaspline','bar','column','line','pie','scatter','spline','ohlc','candlestick');

                if (!isset($Serie['type']) && isset($Serie['Param']))
                {
                    // type aus Param übernehmen
                    foreach($allowedSeriesTypes as $item)
                    {
                        if (strrpos($Serie['Param'],"'$item'") > 0)
                        $Serie['Ips']['Type'] = $item;
                    }
                }
                else
                {
                    if (!in_array($Serie['type'], $allowedSeriesTypes))
                        die ("Abbruch - Serien-Type (" . $Serie['type'] .  ") nicht erkennbar.");
                    else
                        $Serie['Ips']['Type'] = $Serie['type'];
                }
                if (!isset($Serie['Ips']['Type']))
                    die ("Abbruch - Serien-Type nicht erkennbar.");

                // data
                if (isset($Serie['Data']) && isset($Serie['data']))
                    die ("Abbruch - Definition von 'Data' und 'data' in ein und derselben Serie nicht mölglich.");
                if (!isset($Serie['data']) && isset($Serie['Data']))
            {
                $Serie['data'] = $Serie['Data'];
                unset($Serie['Data']);
                }

                // diverse Prüfungen bei PIE-Charts
                if ($Serie['Ips']['Type'] == 'pie')
                {
                if (isset($Serie['Id']))
                {
                        if (!isset($Serie['AggType']))
                            die ("Abbruch - Wird ein Pie über Id definiert muss auch AggType parametriert werden");

                        // wenn nichts angegeben wird 'AggNameFormat: automatisch abhängig vom 'AggType' berechnet
                        if (!isset($Serie['AggNameFormat']))
                        {
                            if ($Serie['AggType'] == 0)   //0=Hour
                                $Serie['AggNameFormat'] = "d.m.Y H:i";
                            else if ($Serie['AggType'] == 1) //1=Day
                                $Serie['AggNameFormat'] = "d.m.Y";
                            else if ($Serie['AggType'] == 2) //2=Week
                                $Serie['AggNameFormat'] = "\K\WW Y";
                            else if ($Serie['AggType'] == 3) //3=Month
                                $Serie['AggNameFormat'] = "M Y";
                            else if ($Serie['AggType'] == 4) //4=Year
                                $Serie['AggNameFormat'] = "Y";
                        }
                }
                else if (isset($Serie['data']))
                {
                foreach($Serie['data'] as $data)
                {
                    if (isset($data['Id']) && isset($data['y']))
                        die ("Abbruch - Pie['data']: Id und y sind als gleichzeitige Parameter nicht möglich.");
                    //if (!isset($data['Id']) && !isset($data['y']))
                        // 	die ("Abbruch - Pie['data']: Id oder y muss definiert sein");
                        // kann man so nicht prüfen
                }
                }
                else
                    {
                        die ("Abbruch - Pie kann nie Daten besitzen. Es muss entweder über 'Id' oder über 'data' definiert werden.");
                    }

                }

                // geänderte Werte wieder zurückschreiben
                $series[] = $Serie;
            }
            // geänderte Werte wieder zurückschreiben

            $cfg['series'] = $series;
            return $cfg;
        }

        // ------------------------------------------------------------------------
        // CheckCfg_AggregatedValues
        //    prüfen der AggregatedValues und Übernahme dieser in die Serien
        //    IN: $cfg
        //    OUT: korrigiertes $cfg
        // ------------------------------------------------------------------------
        function CheckCfg_AggregatedValues($cfg)
            {
            $this->DebugModuleName($cfg,"CheckCfg_AggregatedValues");

            if (!isset($cfg['AggregatedValues']))
                $cfg['AggregatedValues'] = array();

            // Default - wenn nichts vorbelegt
            $this->IfNotIssetSetValue($cfg['AggregatedValues']['MixedMode'], false);
            $this->IfNotIssetSetValue($cfg['AggregatedValues']['HourValues'], -1);
            $this->IfNotIssetSetValue($cfg['AggregatedValues']['DayValues'], -1);
            $this->IfNotIssetSetValue($cfg['AggregatedValues']['WeekValues'], -1);
            $this->IfNotIssetSetValue($cfg['AggregatedValues']['MonthValues'], -1);
            $this->IfNotIssetSetValue($cfg['AggregatedValues']['YearValues'], -1);
            $this->IfNotIssetSetValue($cfg['AggregatedValues']['NoLoggedValues'], 100);

            $series = array();
            foreach ($cfg['series'] as $Serie)
                {

                // prüfen ob für die Serie Einstellungen für AggregatedValues vorhanden sind,
                // wenn nicht Übernahme aus cfg
                if (isset($Serie['AggregatedValues']))
                    {
                    $this->IfNotIssetSetValue($Serie['AggregatedValues']['MixedMode'], $cfg['AggregatedValues']['MixedMode']);
                    $this->IfNotIssetSetValue($Serie['AggregatedValues']['HourValues'], $cfg['AggregatedValues']['HourValues']);
                    $this->IfNotIssetSetValue($Serie['AggregatedValues']['DayValues'], $cfg['AggregatedValues']['DayValues']);
                    $this->IfNotIssetSetValue($Serie['AggregatedValues']['WeekValues'], $cfg['AggregatedValues']['WeekValues']);
                    $this->IfNotIssetSetValue($Serie['AggregatedValues']['MonthValues'], $cfg['AggregatedValues']['MonthValues']);
                    $this->IfNotIssetSetValue($Serie['AggregatedValues']['YearValues'], $cfg['AggregatedValues']['YearValues']);
                    $this->IfNotIssetSetValue($Serie['AggregatedValues']['NoLoggedValues'], $cfg['AggregatedValues']['NoLoggedValues']);
                    }
                else	// nein -> Daten aus übergeordneter cfg übernehmen
                    $Serie['AggregatedValues'] = $cfg['AggregatedValues'];

                // Umrechnen der Tage in Sekunden ... für direktes addieren zum Timestamp
                $MinPerTag = 24*60*60;

                if ($Serie['AggregatedValues']['HourValues'] != -1)
                    $Serie['AggregatedValues']['HourValues'] *= $MinPerTag;
                if ($Serie['AggregatedValues']['DayValues'] != -1)
                    $Serie['AggregatedValues']['DayValues'] *= $MinPerTag;
                if ($Serie['AggregatedValues']['WeekValues'] != -1)
                    $Serie['AggregatedValues']['WeekValues'] *= $MinPerTag;
                if ($Serie['AggregatedValues']['MonthValues'] != -1)
                    $Serie['AggregatedValues']['MonthValues'] *= $MinPerTag;
                if ($Serie['AggregatedValues']['YearValues'] != -1)
                    $Serie['AggregatedValues']['YearValues'] *= $MinPerTag;
                if ($Serie['AggregatedValues']['NoLoggedValues'] != -1)
                    $Serie['AggregatedValues']['NoLoggedValues'] *= $MinPerTag;

                // geänderte Werte wieder zurückschreiben
                $series[] = $Serie;
            }
            // geänderte Werte wieder zurückschreiben
            $cfg['series'] = $series;

            // die sind jetzt nicht mehr nötig.....
            unset($cfg['AggregatedValues']);

            return $cfg;
        }



        // ------------------------------------------------------------------------
        // ReadAdditionalConfigData
        //    zusätzliche Daten für File (hat jetzt aber nichts mit den eigentlichen Highchart Config String zu tun
        //    IN: $cfg
        //    OUT: der String welcher dann in das IPS_Template geschrieben wird.
        // ------------------------------------------------------------------------
        function ReadAdditionalConfigData($cfg)
            {
            $this->DebugModuleName($cfg,"ReadAdditionalConfigData");

            // z.B.: Breite und Höhe für Container
            // Breite und Höhe anpassen für HTML Ausgabe
            $s['Theme'] = $cfg['HighChart']['Theme'];
            if ($cfg['HighChart']['Width'] == 0)
                $s['Width'] = "100%";
            else
                $s['Width'] = $cfg['HighChart']['Width']. "px";

            $s['Height'] = $cfg['HighChart']['Height']. "px";

            return trim(print_r($s, true), "Array\n()") ;
        }

    // ***************************************************************************************************************************

        // ------------------------------------------------------------------------
        // GetHighChartsCfgFile
        //    Falls nicht konfiguriert, wird dies als Default String genommen
        //    OUT: natürlich den String ....
        // ------------------------------------------------------------------------
        function GetHighChartsCfgFile($cfg)
            {
            $this->DebugModuleName($cfg,"GetHighChartsCfgFile");

            $cfgArr['chart'] = $this->CreateArrayForChart($cfg);

            if (isset($cfg['colors']))
                $cfgArr['colors'] = $cfg['colors'];

            $cfgArr['credits'] = $this->CreateArrayForCredits($cfg);

            if (isset($cfg['global']))
                $cfgArr['global'] = $cfg['global'];

            if (isset($cfg['labels']))
                $cfgArr['labels'] = $cfg['labels'];

            // $cfg['lang'])) werden seperat behandelt

            if (isset($cfg['legend']))
                $cfgArr['legend'] = $cfg['legend'];

            if (isset($cfg['loading']))
                $cfgArr['loading'] = $cfg['loading'];

            if (isset($cfg['plotOptions']))
                $cfgArr['plotOptions'] = $cfg['plotOptions'];

            $cfgArr['exporting'] = $this->CreateArrayForExporting($cfg);

            if (isset($cfg['symbols']))
                $cfgArr['symbols'] = $cfg['symbols'];

            $cfgArr['title'] = $this->CreateArrayForTitle($cfg);
            $cfgArr['subtitle'] = $this->CreateArrayForSubTitle($cfg);

            $cfgArr['tooltip'] = $this->CreateArrayForTooltip($cfg);

            $cfgArr['xAxis'] = $this->CreateArrayForXAxis($cfg);
            $cfgArr['yAxis'] = $this->CreateArrayForYAxis($cfg);

            if ($cfg['Ips']['ChartType'] == 'Highstock')
            {
                if (isset($cfg['navigator']))
                    $cfgArr['navigator'] = $cfg['navigator'];
                if (isset($cfg['rangeSelector']))
                    $cfgArr['rangeSelector'] = $cfg['rangeSelector'];
                if (isset($cfg['scrollbar']))
                    $cfgArr['scrollbar'] = $cfg['scrollbar'];
            }


            if ($cfg['Ips']['Debug']['ShowJSON'])
                $this->DebugString($this->my_json_encode($cfgArr));

            $cfgArr['series'] = $this->CreateArrayForSeries($cfg) ;

            if ($cfg['Ips']['Debug']['ShowJSON_Data'])
                $this->DebugString($this->my_json_encode($cfgArr));

            // Array in JSON wandeln
            $s = $this->my_json_encode($cfgArr);

            // ersetzten des 'Param'-Parameters (Altlast aus V1.x)
            $s = str_replace(",Param@@@:",",",$s);
            $s = trim($s, "{");
            $s .= ");";

            return $s;
        }

        // ------------------------------------------------------------------------
        // GetHighChartsLangOptions
        //
        //    IN: $cfg
        //    OUT: JSON Options String für den Bereich 'lang'
        // ------------------------------------------------------------------------
        function GetHighChartsLangOptions($cfg)
            {
            $this->DebugModuleName($cfg,"GetHighChartsLangOptions");

            // Default
            $this->IfNotIssetSetValue($cfg['lang']['decimalPoint'], ",");
            $this->IfNotIssetSetValue($cfg['lang']['thousandsSep'], ".");

            $this->IfNotIssetSetValue($cfg['lang']['months'], ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember']);
            $this->IfNotIssetSetValue($cfg['lang']['shortMonths'], ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez']);
            $this->IfNotIssetSetValue($cfg['lang']['weekdays'], ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag']);

            $s = "lang:" . $this->my_json_encode($cfg['lang']);

            return $s;
            }


        // ------------------------------------------------------------------------
        // CreateArrayForSeries
        //
        //    IN: $cfg
        //    OUT: der String welcher dann in das IPS_Template geschrieben wird.
        // ------------------------------------------------------------------------
        function CreateArrayForSeries($cfg)
            {
            $this->DebugModuleName($cfg,"CreateArrayForSeries");

            // Daten für einzelne Serien erzeugen
            $dataArr = array();
            foreach ($cfg['series'] as $Serie)
                {
                if ($Serie['Ips']['Type'] == 'pie')
                {
                    $Serie['data'] = $this->CreateDataArrayForPie($cfg, $Serie);
                }
                else
                {
                // Daten wurden von extern übergeben
                    if (isset($Serie['data']))
                    {
                        if (is_array($Serie['data']))
                            $Serie['data'] = $this->CreateDataArrayFromExternalData($Serie['data'], $Serie);
                        else
                            $Serie['data'] = $Serie['data'];

                    }
                    // Daten werden aus DB gelesen
                    else
                        $Serie['data'] = $this->ReadDataFromDBAndCreateDataArray($cfg, $Serie);
                }

                // ... aus Serie umkopieren
                $serieArr = $Serie;

                // nicht für JSON benötigte Parameter löschen
                unset($serieArr['Param']);
                unset($serieArr['AggregatedValues']);
                unset($serieArr['Unit']);
                unset($serieArr['StartTime']);
                unset($serieArr['EndTime']);
                unset($serieArr['ReplaceValues']);
                unset($serieArr['Ips']);
                unset($serieArr['Offset']);
                unset($serieArr['AggValue']);
                unset($serieArr['AggType']);
                unset($serieArr['AggNameFormat']);
                unset($serieArr['ScaleFactor']);
                unset($serieArr['RoundValue']);

                // ersetzten des 'Param'-Parameters (Altlast aus V1.x)
                if (isset($Serie['Param']))
                    $serieArr['Param@@@'] = "@" . $Serie['Param'] . "@";

                $dataArr[] = $serieArr;
            }

            return $dataArr;
        }

        // ------------------------------------------------------------------------
        // PopulateDate
        //
        //    IN: $dt
        //			 $serie
        //    OUT: Date-Value für Data-String
        // ------------------------------------------------------------------------
        function PopulateDate($dt, $serie)
        {
            if ($dt < $serie['StartTime'])
                $dt = $serie['StartTime'] ;

            // z.B.: Date.UTC(2011,4,27,19,42,19),23.4
            return  $this->CreateDateUTC($dt + $serie['Offset']);
        }

        // ------------------------------------------------------------------------
        // PopulateValue
        //
        //    IN: $val
        //			 $serie
        //    OUT: korrigiertes $cfg
        // ------------------------------------------------------------------------
        function PopulateValue($val, $serie)
        {
            // Werte ersetzten (sinnvoll für Boolean, oder Integer - z.B.: Tür/Fenster-Kontakt oder Drehgriffkontakt)
            if ($serie['ReplaceValues'] != false)
            {
                if (isset($serie['ReplaceValues'][$val]))
                    $val = $serie['ReplaceValues'][$val];
            }

            // Skalieren von Loggingdaten
            if (isset($serie['ScaleFactor']))
                $val = $val * $serie['ScaleFactor'];

            // Rounden von Nachkommastellen
            if (isset($serie['RoundValue']))
                $val = round($val, $serie['RoundValue']);


            return $val;
        }

        // ------------------------------------------------------------------------
        // CreateDataArrayForPie
        //    Liest die aktuellen Werte aus den übergebenen Variablen und erzeugt die Daten für das PIE
        //    IN: $cfg, $Serie
        //    OUT: der Data String
        // ------------------------------------------------------------------------
        function CreateDataArrayForPie($cfg, $serie)
        {
            $this->DebugModuleName($cfg,"CreateDataArrayForPie");

            if (isset($serie['Id']))
            {
                return $this->ReadPieDataById($cfg, $serie);
            }
            else if (isset($serie['data']))
            {
                $result = array();
                foreach($serie['data'] as $item)
                {
                if (isset($item['Id']))
                {
                    $currentValue = $this->ReadCurrentValue($item['Id']);
                    $item['y'] = $this->PopulateValue($currentValue['Value'], $serie) ;
                }
                $result[] = $item;
                }
                return $result;
            }
            else
            {
            Die ("Abbruch - Pie-Definition nicht korrekt");
            }
            return $Data;
        }

        // ------------------------------------------------------------------------
        // ReadPieDataById
        //    liest die Aggregated-Werte einer einer Vriablen aus und erzeugt das entsprechende Array
        //    IN: $cfg, $serie
        //    OUT: Config Array
        // ------------------------------------------------------------------------
        function ReadPieDataById($cfg, $serie)
        {
            $id_AH = $cfg['ArchiveHandlerId'];

            $tempData = @AC_GetAggregatedValues($id_AH, $serie['Id'], $serie['AggType'], $serie['StartTime'], $serie['EndTime'], 0);
            $tempData = array_reverse($tempData);

            $result = array();
            foreach ($tempData as $ValueItem)
        {
                $item['name'] = $this->ReplaceToGermanDate(date($serie['AggNameFormat'], $ValueItem['TimeStamp']));
                $item['y'] = $this->PopulateValue($ValueItem[$serie['AggValue']], $serie);
                $result[] = $item;
        }
        unset ($tempData);

        return $result;
        }

        // ------------------------------------------------------------------------
        // CalculateStartAndEndTimeForAggreagtedValues
        //       Liest den Start- und Endzeitpunkt des angefragten Bereiches
        //    IN: $Serie, $search : "" für alle Werte, "Hour", "Day", usw
        //    OUT: Array(StartTime,EndTime)
        // ------------------------------------------------------------------------
        function CalculateStartAndEndTimeForAggreagtedValues($Serie, $search ="")
        {
            $start = -1;		$ende = -1;
            $trap = false;
            $sum = 0;

            if ($search == "")
            {
            $search =="Values";
            $start = 0;
            $trap = true;
            }
            foreach($Serie['AggregatedValues'] as $key => $value)
            {
                if (strrpos ($key, "Values") != false)
                {
                    if ($value > 0)
                        $sum += $value;

                    if (strrpos ($key, $search) !== false)
                    {
                        $trap = true;
                        if ($value == -1)
                        return false;
                    }

                    if (!$trap)
                    continue;

                if ($value < 0)
                    continue;

                    if ($start == -1)
                    {
                        $start =  $sum;
                    continue;
                    }

                    if ($start != -1 && $ende ==-1)
                    {
                        $ende =  $sum;
                        break;
                    }
                }
            }

            $result = false;
            if ($start != -1)
            {
            $result["EndTime"] = $Serie["EndTime"] - $start;
                if ($ende == -1)
                $result["StartTime"] = $Serie["StartTime"];
                else
                    $result["StartTime"] = $Serie["EndTime"] - $ende;

                if ($result["StartTime"] < $Serie["StartTime"])
                    $result["StartTime"] = $Serie["StartTime"];

                if ($result["StartTime"] == $Serie["EndTime"])
                    $result = false;
            }

            return $result;
        }

        // ------------------------------------------------------------------------
        // ReadDataFromDBAndCreateDataArray
        //    Liest die Series-Daten aus der DB und schreibt sie in den DataString
        //    IN: $cfg, $Serie
        //    OUT: der Data String
        // ------------------------------------------------------------------------
        function ReadDataFromDBAndCreateDataArray($cfg, $Serie)
            {
            $this->DebugModuleName($cfg,"ReadDataFromDBAndCreateDataArray");

            if (!isset($Serie['Id']))
            return "";

            // errechne die Zeitspanne
            if ($Serie['EndTime'] > time())
                $Diff = time() - $Serie['StartTime'];
            else
                $Diff = $Serie['EndTime'] - $Serie['StartTime'];



            $Id_AH = $cfg['ArchiveHandlerId'];
            $dataArray = array();
            $VariableId = (int)$Serie['Id'];
            $Agg = -1;
            $ReadCurrentValue = true;

            // wenn ReplaceValues definiert wurden werden nur geloggte und keine Aggregated Werte gelesen
            if ($Serie['ReplaceValues'] != false)
            {
                if ($Diff > $Serie['AggregatedValues']['NoLoggedValues'])
                {
                    $Serie['StartTime'] = $Serie['EndTime'] - $Serie['AggregatedValues']['NoLoggedValues'];
                }

                // Einzelwerte lesen
            $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, -1 , $Serie["StartTime"], $Serie["EndTime"], "Value", $Serie);
            }
            else if ($Serie['AggregatedValues']['MixedMode'])    // im MixedMode werden anfangs alle Werte, dann die Stunden- und zuletzt Tageswerte ausgelesen
            {
                // zuerst Einzelwerte
                $result = $this->CalculateStartAndEndTimeForAggreagtedValues($Serie, "");
                if ($result != false)
                {
                    if ($Serie['Ips']['IsCounter']) 						// wenn Zähler dann immer Agg.Values
                        $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, 0, $result["StartTime"], $result["EndTime"], $Serie['AggValue'], $Serie);
                    else
                $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, -1 , $result["StartTime"], $result["EndTime"], "Value", $Serie);
                }

                // -> Stundenwerte
                $result = $this->CalculateStartAndEndTimeForAggreagtedValues($Serie,"Hour");
                if ($result != false)
                    $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, 0, $result["StartTime"], $result["EndTime"], $Serie['AggValue'], $Serie);

                // -> Tageswerte
                $result = $this->CalculateStartAndEndTimeForAggreagtedValues($Serie,"Day");
                if ($result != false)
                    $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, 1, $result["StartTime"], $result["EndTime"], $Serie['AggValue'], $Serie);

                // -> Wochenwerten
                $result = $this->CalculateStartAndEndTimeForAggreagtedValues($Serie,"Week");
                if ($result != false)
                    $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, 2, $result["StartTime"], $result["EndTime"], $Serie['AggValue'], $Serie);

                // -> Monatswerte
                $result = $this->CalculateStartAndEndTimeForAggreagtedValues($Serie,"Month");
                if ($result != false)
                    $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, 3, $result["StartTime"], $result["EndTime"], $Serie['AggValue'], $Serie);

                // -> Jahreswerte
                $result = $this->CalculateStartAndEndTimeForAggreagtedValues($Serie,"Year");
                if ($result != false)
                    $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, 4, $result["StartTime"], $result["EndTime"], $Serie['AggValue'], $Serie);
            }
            else
            {
                $Agg = -1;	// ->  AC_GetLoggedValues

            if  (isset($Serie['AggType']))   // wenn 'AggType' definiert wurde, wird dies vorrangig bearbeitet
            {
                $Agg = $Serie['AggType'];
            }
                elseif ($Serie['AggregatedValues']['YearValues']!= -1 && $Diff > $Serie['AggregatedValues']['YearValues'])
                    $Agg = 4;	//  -> AC_GetAggregatedValues [0=Hour, 1=Day, 2=Week, 3=Month, 4=Year]
                elseif ($Serie['AggregatedValues']['MonthValues']!= -1 && $Diff > $Serie['AggregatedValues']['MonthValues'])
                    $Agg = 3;	//  -> AC_GetAggregatedValues [0=Hour, 1=Day, 2=Week, 3=Month, 4=Year]
                elseif ($Serie['AggregatedValues']['WeekValues']!= -1 && $Diff > $Serie['AggregatedValues']['WeekValues'])
                    $Agg = 2;	//  -> AC_GetAggregatedValues [0=Hour, 1=Day, 2=Week, 3=Month, 4=Year]
                elseif ($Serie['AggregatedValues']['DayValues']!= -1 && $Diff > $Serie['AggregatedValues']['DayValues'])
                    $Agg = 1;	//  -> AC_GetAggregatedValues [0=Hour, 1=Day, 2=Week, 3=Month, 4=Year]
                else if ($Serie['AggregatedValues']['HourValues']!= -1 && $Diff > $Serie['AggregatedValues']['HourValues'])
                    $Agg = 0;	//  -> AC_GetAggregatedValues [0=Hour, 1=Day, 2=Week, 3=Month, 4=Year]

                // es wurde noch nichts definiert und es handelt sich um einen Zähler --> Tageswerte
            if ($Agg == -1 && $Serie['Ips']['IsCounter'])
                $Agg = 0;

                if ($Agg == -1)
                {
                    // Zeitraum ist zu groß -> nur bis max. Zeitraum einlesen
                    if ($Diff > $Serie['AggregatedValues']['NoLoggedValues'])
                        $Serie['StartTime'] = $Serie['EndTime'] - $Serie['AggregatedValues']['NoLoggedValues'];

                    // Alle Werte
                $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, -1 , $Serie["StartTime"], $Serie["EndTime"], "Value", $Serie);
                }
                else
                {
                    $dataArray = $this->ReadAndAddToLoggedData($dataArray, $Id_AH, $VariableId, $Agg, $Serie["StartTime"], $Serie["EndTime"], $Serie['AggValue'], $Serie);
                    $ReadCurrentValue = false;
                }
            }

            // sortieren, so , dass der aktuellste Wert zuletzt kommt
            $dataArray = array_reverse($dataArray);

            // aktuellen Wert der Variable noch in Array aufnehmen
            if ($ReadCurrentValue
                //&& $Serie['EndTime'] >= time()    			// nicht wenn Endzeitpunkt vor NOW ist
                && !$Serie['Ips']['IsCounter'])				// nicht bei Zählervariablen
                {
    //                $curValue = ReadCurrentValue($VariableId);
                    $curValue    = $this->ReadLoggedValue($Id_AH, $VariableId, $Serie['EndTime']);
                    $dataArray[] = $this->CreateDataItem($curValue['TimeStamp'], $curValue['Value'], $Serie);
                }


            return $dataArray ;
        }

        // ------------------------------------------------------------------------
        // ReadLoggedValue
        //    IN: $instanceID, $VariableId, $time
        //    OUT: Aktueller Wert
        // ------------------------------------------------------------------------
        function ReadLoggedValue($instanceID, $variableId, $time)
        {
            if ($time > time()) $time = time();
            $values = AC_GetLoggedValues($instanceID, $variableId, 0, $time+1, 1);
            $currentVal['Value']= $values[0]['Value'];
            $currentVal['TimeStamp'] = $time;

            return $currentVal;
        }  

        // ------------------------------------------------------------------------
        // ReadCurrentValue
        //    IN: $VariableId
        //    OUT: Aktueller Wert
        // ------------------------------------------------------------------------
        function ReadCurrentValue($variableId)
        {
            $currentVal['Value']= GetValue($variableId);
            $currentVal['TimeStamp'] = time();

            return $currentVal;
        }

        // ------------------------------------------------------------------------
        // ReadAndAddToLoggedData
        //    IN: siehe Parameter
        //    OUT: Vervollständigte Logged Data
        // ------------------------------------------------------------------------
        function ReadAndAddToLoggedData($loggedData, $id_AH, $variableId, $aggType, $startTime, $endTime, $aggValueName, $serie)
        {
            
            $cfg['Ips']['Debug']['Modules'] = true;
            $loggedData=array();

            if ($aggType >= 0)
                $tempData = @AC_GetAggregatedValues($id_AH, $variableId, $aggType, $startTime, $endTime, 0);
            else
                {
                //$tempData = @AC_GetLoggedValues($id_AH, $variableId, $startTime, $endTime, 0 );
                //echo "keine Agreggierung vornehmen.\n";
                $tempData = @$this->AC_GetLoggedValuesCompatibility($id_AH, $variableId, $startTime, $endTime, 0 );
                }
            if ($tempData)
                {
                //echo "Highcharts: Datenelemente ausgeben von ID :".$variableId."  ".IPS_GetName(IPS_GetParent($variableId))."/".IPS_GetName($variableId)."\n";
                //echo "   Aggreggationstyp : ".$aggType." mit insgesamt ".sizeof($tempData)." Werten. \n";
                //print_r($tempData);

                foreach ($tempData as $item)
                        {
                        $loggedData[] = $this->CreateDataItem($item['TimeStamp'], $item[$aggValueName], $serie);
                        //echo "    ".$item['TimeStamp']."   ".$item[$aggValueName]."\n";
                        }

                unset ($tempData);
                }
            return $loggedData;
        }

        //Hilfsfunktion, die die Funktionsweise von IP-Symcon 2.x nachbildet
        function AC_GetLoggedValuesCompatibility($instanceID, $variableID, $startTime, $endTime, $limit) {
            $values = AC_GetLoggedValues($instanceID, $variableID, $startTime, $endTime, $limit );
            if((sizeof($values) == 0) || (end($values)['TimeStamp'] > $startTime)) {
                $previousRow = AC_GetLoggedValues($instanceID, $variableID, 0, $startTime - 1, 1 );
                $values = array_merge($values, $previousRow);
            }
            return $values;
        }
        
        function CreateDataItem($dt, $val, $serie)
        {
            // Wert anpassen (Round, Scale)
            $val = $this->PopulateValue($val, $serie);

            // z.B.: Date.UTC(2011,4,27,19,42,19),23.4
            $dtUTC = $this->PopulateDate($dt, $serie);

            return array("@$dtUTC@", $val);
        }

        // ------------------------------------------------------------------------
        // CreateDataArrayFromExternalData
        //    Umwandeln der externen Daten in ein Daten Array
        //    IN: $arr = Aus IPS-Datenbank ausgelesenen Daten (LoggedData)
        //        $Serie = Config Daten der aktuellen Serie
        //    OUT: Highcharts ConfigString für Series-Data
        // ------------------------------------------------------------------------
        function CreateDataArrayFromExternalData($arr, $Serie)
        {
            $result = array();
            foreach($Serie['data'] as $item)
            {
                if (is_array($item))
                {
                if (isset($item['TimeStamp']) && !isset($item['x']))
                {
                    $item['x'] = "@" . PopulateDate($item['TimeStamp'], $Serie) . "@";
                    unset($item['TimeStamp']);
                }
                if (isset($item['Value']) && !isset($item['y']))
                {
                    $item['y'] = $item['Value'];
                    unset($item['Value']);
                }
                if (isset($item['y']))
                        $item['y'] = PopulateValue($item['y'], $Serie);

                $result[] = $item;
            }
                else
                    $result[] = $item;
            }

            return $result;
        }

        // ------------------------------------------------------------------------
        // CreateTooltipFormatter
        //    Auslesen von immer wieder benötigten Werten aus der Variable
        //    IN: $cfg = Alle Config Daten
        //    OUT: Highcharts ConfigString für Tooltip-Formatter (Interaktive Anzeige des Wertes)
        // ------------------------------------------------------------------------
        function CreateTooltipFormatter($cfg)
            {
            $this->DebugModuleName($cfg,"CreateTooltipFormatter");

            //ToDo: da sollten wir etwas lesbarer arbeiten
            $s = "";
            $offset ="";

            foreach ($cfg['series'] as $Serie )
            {
                if ($Serie['Ips']['Type'] == 'pie')
                {
                    if (isset($Serie['data']))
                    {
                        $s .= "[";
                        foreach($Serie['data'] as $data)
                        {
                            $unit = @$Serie['Unit'];
                            if (isset($data['Unit']))
                                $unit = $data['Unit'];

                            $s .= "this.y +' " . $unit . "',";
                        }
                        $s = trim($s,",");
                        $s .= "][this.point.x],";
                    }
                    else
                    {
                        $unit = @$Serie['Unit'];
                        $s .= "[this.y + ' " . $unit . "'],";
                    }
                    $offset .= "0,";  // pies haben nie einen Offset
                }
                else
                {
                // hier wird das VariableCustomProfile aus IPS übernommen
                if (!isset($Serie['Unit']))
                    {
                        // hole das Variablen Profil
                        $IPSProfil = @GetIPSVariableProfile($Serie['Id']);
                        if ($IPSProfil != false)
                        {
                            if (array_key_exists("Associations",$IPSProfil) && count($IPSProfil['Associations'])>0)
                            {
                                $Arr = array();
                                foreach($IPSProfil['Associations'] as $Item)
                                {
                                $Arr[$Item['Value']] = $Item['Name'];
                                }

                                if (!is_array($Serie['ReplaceValues']))         // erzeuge Tooltips vollständig aus VariablenProfil
                                    $s .= $this->CreateTooltipSubValues($Arr, array_keys($Arr));
                                else  														// oder nehme ReplaceValues zur Hilfe
                                    $s .= $this->CreateTooltipSubValues($Arr, $Serie['ReplaceValues']);
                            }
                            else
                            {
                                // Suffix als Einheit übernehmen
                                $Serie['Unit'] = trim($IPSProfil['Suffix'], " ");
                                $s .= "[this.y + ' ". $Serie['Unit']."'],";
                            }
                        }
                        else  // falls VariablenId nicht existiert
                        {
                            $s .= "[this.y ],";
                        }
                    }
                    // es wurden Unit und ReplaceValues übergeben
                    else if (is_array($Serie['Unit']) && is_array($Serie['ReplaceValues']))
                    {
                        $s .= $this->CreateTooltipSubValues($Serie['Unit'],$Serie['ReplaceValues']);
                    }
                    else		// Einheit aus übergebenem Parmeter Unit
                    {
                    $s .= "[this.y + ' ". $Serie['Unit']."'],";
                }
                    $offset .= $Serie['Offset'] . ",";
            }

            }

            $s = trim($s , "," );
            $offset = trim($offset , "," );

            //*1000 da JS in [ms] angebgeben wird un php in [s]
    /*		$TooltipString="function() {
                                    var serieIndex = this.series.index;

                                    if (this.series.type == 'pie')
                                    {
                                var pointIndex = this.point.x;
                                        var unit = [".$s. "][serieIndex][pointIndex];

                                        if (!unit)
                                        unit = [".$s. "][serieIndex][0];

                                        return '<b>' + this.point.name +': </b> '+ unit +'<br/>= ' + this.percentage.toFixed(1) + ' %';
                                    }
                                    else
                            {
                                var pointIndex = 0;
                                        var unit = [".$s. "][serieIndex][pointIndex];
                                        var offset = [".$offset. "][serieIndex] * 1000;

                                        var offsetInfo ='';
                                        if (offset != 0)
                                            offsetInfo = '<br/>(Achtung Zeitwert hat einen Offset)';
                                        else
                                            offsetInfo ='';

                                        return '<b>' + this.series.name + ': </b> '+ unit + '<br/>'
                                            + Highcharts.dateFormat('%A %d.%m.%Y %H:%M', this.x - offset)
                                            + offsetInfo;


                                    }
                            } ";
    */
            $TooltipString="function() {
                                    var serieIndex = this.series.index;
                                    var unit = [".$s. "][serieIndex];
                                    var offset = [".$offset. "][serieIndex] * 1000;
                                    var offsetInfo ='';

                                    if (offset != 0)
                                        offsetInfo = '<br/>(Achtung Zeitwert hat einen Offset)';
                                    else
                                        offsetInfo ='';

                                    if (this.series.type == 'pie')
                                    {
                                        return '<b>' + this.point.name +': </b> '+ unit +'<br/>= ' + this.percentage.toFixed(1) + ' %';
                                    }
                                    else
                            {
                                        return '<b>' + this.series.name + ': </b> '+ unit + '<br/>'
                                            + Highcharts.dateFormat('%A %d.%m.%Y %H:%M', this.x - offset)
                                            + offsetInfo;
                                    }
                            } ";



            return $TooltipString;
        }

        // ------------------------------------------------------------------------
        // CreateTooltipSubValues
        //    Erzeugt den Tooltip für Unter-Elemente
        //    IN: shownTooltipArr = Array der Werte (Synonyme) welche im Tooltip angezeigt werden sollen
        //        chartValueArr = Array der Werte welche im Chart eingetragen werden
        //    OUT: Tooltip String
        // ------------------------------------------------------------------------
        function CreateTooltipSubValues($shownTooltipArr, $chartValueArr)
        {
            $s="{";
            $Count = count($shownTooltipArr);
            for ($i = 0; $i < $Count ; $i++)
            {
                if (isset($chartValueArr[$i]) && isset($shownTooltipArr[$i]))
                $s .= $chartValueArr[$i] .": '" . $shownTooltipArr[$i] ."'," ;
            }
            $s = trim($s, ",") . "}";

            return $s ."[this.y],";
        }

        // ------------------------------------------------------------------------
        // GetIPSVariableProfile
        //    Liest das Variablen Profil der übergeben Variable aus
        //    Versucht zuerst das eigene und wenn nicht verfügbar das Standar Profil auszulesen
        //    IN: variableId = Id der Variablen
        //    OUT: Variablen Profil
        // ------------------------------------------------------------------------
        function GetIPSVariableProfile($variableId)
        {
            $var = @IPS_GetVariable($variableId);
            if ($var == false) // Variabel existiert nicht
            return false;

            $profilName = $var['VariableCustomProfile']; 	// "Eigenes Profil"

            if ($profilName == false)                     	// "Standard" Profil
                $profilName = $var['VariableProfile'];

            if ($profilName != false)
                return IPS_GetVariableProfile($profilName);  // und jetzt die dazugehörigen Daten laden
            else
                return false;
        }



        // ------------------------------------------------------------------------
        // CreateArrayForChart
        //
        //    IN: $cfg
        //    OUT: Config Array für den Bereich 'chart'
        // ------------------------------------------------------------------------
        function CreateArrayForChart($cfg)
        {
            if (!isset($cfg['chart']))
                $cfg['chart'] = array();

            //Default
            $this->IfNotIssetSetValue($cfg['chart']['renderTo'], "container");
            $this->IfNotIssetSetValue($cfg['chart']['zoomType'], "xy");

            return $cfg['chart'];
        }

        // ------------------------------------------------------------------------
        // CreateArrayForCredits
        //
        //    IN: $cfg
        //    OUT: Config Array für den Bereich 'credits'
        // ------------------------------------------------------------------------
        function CreateArrayForCredits($cfg)
        {
            if (!isset($cfg['credits']))
                $cfg['credits'] = array();

            //Default
            $this->IfNotIssetSetValue($cfg['credits']['enabled'], false);

            return $cfg['credits'];
        }

        // ------------------------------------------------------------------------
        // CreateArrayForTitle
        //
        //    IN: $cfg
        //    OUT: Config Array für den Bereich 'title'
        // ------------------------------------------------------------------------
        function CreateArrayForTitle($cfg)
        {
            if (!isset($cfg['title']))
                $cfg['title'] = array();

            return $cfg['title'];
        }

        // ------------------------------------------------------------------------
        // CreateArrayForExporting
        //
        //    IN: $cfg
        //    OUT: Config Array für den Bereich 'exporting'
        // ------------------------------------------------------------------------
        function CreateArrayForExporting($cfg)
        {
            if (!isset($cfg['exporting']))
                $cfg['exporting'] = array();

            //Default
            $this->IfNotIssetSetValue($cfg['exporting']['buttons']['printButton']['enabled'], false);

            return $cfg['exporting'];
        }

        // ------------------------------------------------------------------------
        // CreateArrayForTooltip
        //
        //    IN: $cfg
        //    OUT: Config Array für den Bereich 'tooltip'
        // ------------------------------------------------------------------------
        function CreateArrayForTooltip($cfg)
        {
            if (!isset($cfg['tooltip']))
                $cfg['tooltip'] = array();

            //Default
            // wenn not isset -> autom. erzeugen durch IPS
            if (!isset($cfg['tooltip']['formatter']))
            $cfg['tooltip']['formatter'] = "@" . $this->CreateTooltipFormatter($cfg) . "@";
            // wenn "" -> default by highcharts
            else if ($cfg['tooltip']['formatter'] == "")
            {
            // do nothing
            }

            return $cfg['tooltip'];
        }

        // ------------------------------------------------------------------------
        // CreateArrayForSubTitle
        //
        //    IN: $cfg
        //    OUT: Config Array für den Bereich subtitle
        // ------------------------------------------------------------------------
        function CreateArrayForSubTitle($cfg)
        {
            if (!isset($cfg['subtitle']))
                $cfg['subtitle'] = array();

            //Default
            $this->IfNotIssetSetValue($cfg['subtitle']['text'], "Zeitraum: %STARTTIME% - %ENDTIME%");
            $this->IfNotIssetSetValue($cfg['subtitle']['Ips']['DateTimeFormat'], "(D) d.m.Y H:i");

            $s = $cfg['subtitle']['text'];
            $s = str_ireplace("%STARTTIME%", date($cfg['subtitle']['Ips']['DateTimeFormat'], $cfg['Ips']['ChartStartTime']), $s);
            $s = str_ireplace("%ENDTIME%", date($cfg['subtitle']['Ips']['DateTimeFormat'], $cfg['Ips']['ChartEndTime']), $s);
            $cfg['subtitle']['text'] = $this->ReplaceToGermanDate($s);

            unset($cfg['subtitle']['Ips']);

            return $cfg['subtitle'];
        }
        // ------------------------------------------------------------------------
        // CreateArrayForXAxis
        //    Erzeugen das ArrX-Achsen Strings für Highchart-Config
        //    IN: $cfg
        //       es besteht die Möglichkeit den Achsen String bereits im Highchart Format zu hinterlegen
        //       oder die folgenden Parameter als Array einzustellen: Name, Min, Max, TickInterval, Opposite, Unit
        //    OUT: Highcharts String für die Achsen
        // ------------------------------------------------------------------------
        function CreateArrayForXAxis($cfg)
        {
            if (!isset($cfg['xAxis']))
                $cfg['xAxis'] = array();

            //Default
            $this->IfNotIssetSetValue($cfg['xAxis']['type'], "datetime");
            $this->IfNotIssetSetValue($cfg['xAxis']['dateTimeLabelFormats']['second'], "%H:%M:%S");
            $this->IfNotIssetSetValue($cfg['xAxis']['dateTimeLabelFormats']['minute'], "%H:%M");
            $this->IfNotIssetSetValue($cfg['xAxis']['dateTimeLabelFormats']['hour'], "%H:%M");
            $this->IfNotIssetSetValue($cfg['xAxis']['dateTimeLabelFormats']['day'], "%e. %b");
            $this->IfNotIssetSetValue($cfg['xAxis']['dateTimeLabelFormats']['week'], "%e. %b");
            $this->IfNotIssetSetValue($cfg['xAxis']['dateTimeLabelFormats']['month'], "%b %y");
            $this->IfNotIssetSetValue($cfg['xAxis']['dateTimeLabelFormats']['year'], "%Y");

            $this->IfNotIssetSetValue($cfg['xAxis']['allowDecimals'], false);

            if (isset($cfg['xAxis']['min']) && $cfg['xAxis']['min'] == false)
                unset($cfg['xAxis']['min']);
            else
                $this->IfNotIssetSetValue($cfg['xAxis']['min'], "@" . $this->CreateDateUTC($cfg['Ips']['ChartStartTime']) ."@");

            if (isset($cfg['xAxis']['max']) && $cfg['xAxis']['max'] == false)
                unset($cfg['xAxis']['max']);
            else
                $this->IfNotIssetSetValue($cfg['xAxis']['max'], "@" . $this->CreateDateUTC($cfg['Ips']['ChartEndTime'])."@");



            return $cfg['xAxis'];
        }

        // ------------------------------------------------------------------------
        // CreateArrayForYAxis
        //    Erzeugen der Y-Achsen Strings für Highchart-Config
        //    IN: $cfg
        //       es besteht die Möglichkeit den Achsen String bereits im Highchart Format zu hinterlegen
        //       oder die folgenden Parameter als Array einzustellen: Name, Min, Max, TickInterval, Opposite, Unit
        //    OUT: Highcharts String für die Achsen
        // ------------------------------------------------------------------------
        function CreateArrayForYAxis($cfg)
        {
            if (!isset($cfg['yAxis']))
                return null;

            $result = array();

            foreach ($cfg['yAxis'] as $Axis )
            {
                // erst mal alles kopieren
                $cfgAxis = $Axis;

                if (!isset($cfgAxis['labels']['formatter']) && isset($Axis['Unit']))
                    $cfgAxis['labels']['formatter'] ="@function() { return this.value +' ". $Axis['Unit']."'; }@";

            $result[] = $cfgAxis;
            }

            return $result;
        }

        // ------------------------------------------------------------------------
        // CreateDateUTC
        //    Erzeugen des DateTime Strings für Highchart-Config
        //    IN: $timeStamp = Zeitstempel
        //    OUT: Highcharts DateTime-Format als UTC String ... Date.UTC(1970, 9, 27, )
        //       Achtung! Javascript Monat beginnt bei 0 = Januar
        // ------------------------------------------------------------------------
        function CreateDateUTC($timeStamp)
        {
            $monthForJS = ((int)date("m", $timeStamp))-1 ;	// Monat -1 (PHP->JS)
            return "Date.UTC(" . date("Y,", $timeStamp) .$monthForJS. date(",j,H,i,s", $timeStamp) .")";
        }

        // ------------------------------------------------------------------------
        // ReplaceToGermanDate
        //    Falls nicht konfiguriert, wird dies als Default String genommen
        //    IN: String mit englischen Wochentagen, bzw. Monaten
        //    OUT: der String übersetzt ins Deutsche
        // ------------------------------------------------------------------------
        function ReplaceToGermanDate($value)
        {
                $trans = array(
                    'Monday'    => 'Montag',
                    'Tuesday'   => 'Dienstag',
                    'Wednesday' => 'Mittwoch',
                    'Thursday'  => 'Donnerstag',
                    'Friday'    => 'Freitag',
                    'Saturday'  => 'Samstag',
                    'Sunday'    => 'Sonntag',
                    'Mon'       => 'Mo',
                    'Tue'       => 'Di',
                    'Wed'       => 'Mi',
                    'Thu'       => 'Do',
                    'Fri'       => 'Fr',
                    'Sat'       => 'Sa',
                    'Sun'       => 'So',
                    'January'   => 'Januar',
                    'February'  => 'Februar',
                    'March'     => 'März',
                    'May'       => 'Mai',
                    'June'      => 'Juni',
                    'July'      => 'Juli',
                    'October'   => 'Oktober',
                    'December'  => 'Dezember',
                    'Mar'     	 => 'Mär',
                    'May'       => 'Mai',
                    'Oct'   	 => 'Okt',
                    'Dec'  		 => 'Dez',
            );
            return  strtr($value, $trans);
        }


        // ------------------------------------------------------------------------
        // my_json_encode
        //
        //    IN: PHP-Array
        //    OUT: JSON String
        // ------------------------------------------------------------------------
        function my_json_encode($cfgArr)
            {
            array_walk_recursive($cfgArr, array($this, 'CheckArrayItems'));

            $s = json_encode($cfgArr);

            // alle " entfernen
            $s = str_replace('"', '',$s);

            // Zeilenumbruch, Tabs, etc entfernen ... bin mir nicht so sicher ob das so gut ist
            $s = $this->RemoveUnsupportedStrings($s);

            return $s;
            }

        // ------------------------------------------------------------------------
        // CheckArrayItems
        //
        //    IN: Array-Item
        //    OUT:
        // ------------------------------------------------------------------------
        function CheckArrayItems(&$item)
        {
            if (is_string($item))
            {
                if ($item == "@" || $item == "@@" )
                {
                    $item = "'" . $item . "'";
                }
                else if ((substr($item,0,1) == "@" && substr($item,-1) == "@"))
                {
                    $item = trim($item, "@");
                }
    /*			else if ((substr($item,0,1) == "$" && substr($item,-1) == "$"))
                {

                    $item = trim($item, "$");
                }*/
                else
                {
                    $item = "'" . trim($item, "'") . "'";
                }

                if (mb_detect_encoding($item, 'UTF-8', true) === false) {       
                    $item = utf8_encode($item);   
                }  			

            }
        }

        // ------------------------------------------------------------------------
        // RemoveUnsupportedStrings
        //    Versuchen Sonderzeichen wie Zeilenumbrüche, Tabs, etc. aus dem übergebenen String zu entfernen
        //    IN: $str
        //    OUT: $str
        // ------------------------------------------------------------------------
        function RemoveUnsupportedStrings($str)
        {

            $str = str_replace("\\t","",$str);
            $str = str_replace("\\n","",$str);
            $str = str_replace("\\r","",$str);
            $str = str_ireplace("\\\u00","\\u00",$str);  // da muss man nochmals checken
            $str = str_replace("\\\\","",$str);

            return $str;
        }

        // ------------------------------------------------------------------------
        // IfNotIssetSetValue
        //    pfüft ob isset($item), wenn nicht wird $value in &$item geschrieben
        //    IN: &$item, $value
        //    OUT: &$item
        // ------------------------------------------------------------------------
        function IfNotIssetSetValue(&$item, $value )
        {
            if (!isset($item)
                || (is_string($item) && $item == ""))   // zusätzliche Abfrage in 2.01
            {
                $item = $value;
                return false;
            }

            return true;
        }

        // ------------------------------------------------------------------------
        // getmicrotime
        //
        //    IN:
        //    OUT:
        // ------------------------------------------------------------------------
        function getmicrotime($short = false)
        {
        list($usec,$sec)=explode(" ", microtime());

            if ($short )
            return (float)$usec + (float)substr($sec,-1);
            else
            return (float)$usec + (float)$sec;
        }

        // ------------------------------------------------------------------------
        // DebugString
        //
        //    IN:
        //    OUT:
        // ------------------------------------------------------------------------
        function DebugString($str)
        {
        $s = $this->RemoveUnsupportedStrings($str);
        echo $s;
        }

        // ------------------------------------------------------------------------
        // DebugModuleName
        //
        //    IN:
        //    OUT:
        // ------------------------------------------------------------------------
        function DebugModuleName($cfg, $name)
            {
            if (isset($cfg['Ips']['Debug']['Modules']) && $cfg['Ips']['Debug']['Modules'])
                {
                global $_IPS;

                IPS_LogMessage($_IPS['SENDER'] ." - " .getmicrotime(true) , "Highcharts $this->version ($this->versionDate) - $name");
                }
            }

    } // ende class HighCharts


	/** @}*/
?>