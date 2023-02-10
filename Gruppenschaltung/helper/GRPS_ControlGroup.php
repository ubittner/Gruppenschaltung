<?php

/**
 * @project       Gruppenschaltung/Gruppenschaltung
 * @file          GRPS_ControlGroup.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpVoidFunctionResultUsedInspection */

declare(strict_types=1);

trait GRPS_ControlGroup
{
    /**
     * Toggled the group of variables.
     *
     * @param bool $State
     * false =  off
     * true =   on
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     *
     * @throws Exception
     */
    public function ToggleGroup(bool $State): bool
    {
        if ($State) {
            if ($this->CheckMaintenance()) {
                return false;
            }
        }
        $result = true;
        $this->SetValue('GroupSwitch', $State);
        $variables = json_decode($this->ReadPropertyString('Variables'), true);
        $order = array_column($variables, 'Order');
        if (count(array_unique($order)) === 1 && end($order) === 0) {
            //Random mode
            shuffle($variables);
        } else {
            //Sort to order
            array_multisort(array_column($variables, 'Order'), SORT_ASC, $variables);
        }
        foreach ($variables as $variable) {
            if ($variable['Use']) {
                $id = $variable['ID'];
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    $this->WriteAttributeBoolean('DisableUpdateMode', true);
                    //Command control
                    $commandControl = $this->ReadPropertyInteger('CommandControl');
                    if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) { //0 = main category, 1 = none
                        $commands = [];
                        $value = 'false';
                        if ($State) {
                            $value = 'true';
                        }
                        IPS_Sleep($variable['SwitchingDelay']);
                        $commands[] = '@RequestAction(' . $id . ', ' . $value . ');';
                        $this->SendDebug(__FUNCTION__, 'Befehl: ' . json_encode(json_encode($commands)), 0);
                        $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                        $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                        $result = @IPS_RunScriptText($scriptText);
                    } else {
                        IPS_Sleep($variable['SwitchingDelay']);
                        $response = @RequestAction($id, $State);
                        if (!$response) {
                            //Retry
                            IPS_Sleep(self::DELAY_MILLISECONDS);
                            $response = @RequestAction($id, $State);
                            if (!$response) {
                                //Last retry
                                IPS_Sleep(self::DELAY_MILLISECONDS);
                                $response = @RequestAction($id, $State);
                            }
                        }
                        if (!$response) {
                            $result = false;
                        }
                    }
                }
            }
        }
        $this->WriteAttributeBoolean('DisableUpdateMode', false);
        $this->UpdateGroup();
        return $result;
    }

    /**
     * Updates the group switch.
     *
     * @return bool
     * false =  an error occurred
     * true =   successful
     *
     * @throws Exception
     */
    public function UpdateGroup(): bool
    {
        if ($this->CheckMaintenance()) {
            return false;
        }
        if ($this->ReadAttributeBoolean('DisableUpdateMode')) {
            return false;
        }
        $result = false;
        $state = false;
        foreach (json_decode($this->ReadPropertyString('Variables'), true) as $variable) {
            if ($variable['Use']) {
                $id = $variable['ID'];
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $result = true;
                    if (GetValueBoolean($variable['ID'])) {
                        $state = true;
                    }
                }
            }
        }
        $this->SetValue('GroupSwitch', $state);
        return $result;
    }
}