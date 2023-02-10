<?php

/**
 * @project       Gruppenschaltung/Gruppenschaltung
 * @file          GRPS_TriggerVariables.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFunctionInspection */

declare(strict_types=1);

trait GRPS_triggerVariable
{
    /**
     * Checks the trigger variable.
     *
     * @param int $VariableID
     *
     * @param bool $ValueChanged
     * false =  value hasn't changed
     * true =   value changed
     *
     * @return void
     *
     * @throws Exception
     */
    public function CheckTriggerVariable(int $VariableID, bool $ValueChanged): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Sender: ' . $VariableID, 0);
        $valueChangedText = 'nicht ';
        if ($ValueChanged) {
            $valueChangedText = '';
        }
        $this->SendDebug(__FUNCTION__, 'Der Wert hat sich ' . $valueChangedText . 'geändert', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($variables as $key => $variable) {
            if (!$variable['Use']) {
                continue;
            }
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $triggerVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($VariableID == $triggerVariableID) {
                            $this->SendDebug(__FUNCTION__, 'Listenschlüssel: ' . $key, 0);
                            if (!$variable['UseMultipleAlerts'] && !$ValueChanged) {
                                $this->SendDebug(__FUNCTION__, 'Abbruch, die Mehrfachauslösung ist nicht aktiviert!', 0);
                                continue;
                            }
                            $execute = true;
                            //Check primary condition
                            if (!IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                                $execute = false;
                            }
                            //Check secondary condition
                            if (!IPS_IsConditionPassing($variable['SecondaryCondition'])) {
                                $execute = false;
                            }
                            if (!$execute) {
                                $this->SendDebug(__FUNCTION__, 'Abbruch, die Bedingungen wurden nicht erfüllt!', 0);
                            } else {
                                $this->SendDebug(__FUNCTION__, 'Die Bedingungen wurden erfüllt.', 0);
                                switch ($variable['TriggerAction']) {
                                    case 0: # toggle group off
                                        $this->SendDebug(__FUNCTION__, 'Aktion: Gruppe ausschalten wird ausgeführt.', 0);
                                        $this->ToggleGroup(false);
                                        break;

                                    case 1: # toggle group on
                                        $this->SendDebug(__FUNCTION__, 'Aktion: Gruppe einschalten wird ausgeführt.', 0);
                                        $this->ToggleGroup(true);
                                        break;

                                    case 2: # toggle group
                                        $this->SendDebug(__FUNCTION__, 'Aktion: Gruppe umschalten wird ausgeführt.', 0);
                                        $this->ToggleGroup(!$this->GetValue('GroupSwitch'));
                                        break;

                                }
                            }
                        }
                    }
                }
            }
        }
    }
}