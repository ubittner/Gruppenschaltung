<?php

/**
 * @project       Gruppenschaltung/Gruppenschaltung
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/GRPS_autoload.php';

class Gruppenschaltung extends IPSModule
{
    //Helper
    use GRPS_Config;
    use GRPS_ControlGroup;
    use GRPS_triggerVariable;

    //Constants
    private const LIBRARY_GUID = '{90BFC35E-83A6-6A64-9516-CD79E4A45C3B}';
    private const MODULE_GUID = '{B5BC525B-3BC0-B081-A5A4-7FD75254B514}';
    private const MODULE_PREFIX = 'GRPS';
    private const ABLAUFSTEUERUNG_MODULE_GUID = '{0559B287-1052-A73E-B834-EBD9B62CB938}';
    private const ABLAUFSTEUERUNG_MODULE_PREFIX = 'AST';
    private const DELAY_MILLISECONDS = 250;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Note
        $this->RegisterPropertyString('Note', '');

        //Active
        $this->RegisterPropertyBoolean('EnableActive', false);

        //Group switch
        $this->RegisterPropertyBoolean('EnableGroupSwitch', true);

        //Group variables
        $this->RegisterPropertyString('Variables', '[]');

        //Trigger variables
        $this->RegisterPropertyString('TriggerList', '[]');

        //Command control
        $this->RegisterPropertyInteger('CommandControl', 0);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Group switch
        $this->RegisterVariableBoolean('GroupSwitch', 'Gruppenschaltung', '~Switch', 20);
        $this->EnableAction('GroupSwitch');

        ########## Attribute

        //Disable update
        $this->RegisterAttributeBoolean('DisableUpdateMode', false);
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all registrations
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        //Register references and update messages

        //Group variables
        foreach (json_decode($this->ReadPropertyString('Variables')) as $variable) {
            if ($variable->Use) {
                $id = $variable->ID;
                if ($id != 0 && @IPS_ObjectExists($id)) {
                    $this->RegisterReference($id);
                    $this->RegisterMessage($id, VM_UPDATE);
                }
            }
        }

        //Trigger list
        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            //Primary condition
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            $this->RegisterReference($id);
                            $this->RegisterMessage($id, VM_UPDATE);
                        }
                    }
                }
            }
            //Secondary condition, multi
            if ($variable['SecondaryCondition'] != '') {
                $secondaryConditions = json_decode($variable['SecondaryCondition'], true);
                if (array_key_exists(0, $secondaryConditions)) {
                    if (array_key_exists('rules', $secondaryConditions[0])) {
                        $rules = $secondaryConditions[0]['rules']['variable'];
                        foreach ($rules as $rule) {
                            if (array_key_exists('variableID', $rule)) {
                                $id = $rule['variableID'];
                                if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                                    $this->RegisterReference($id);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Reset attribute
        $this->WriteAttributeBoolean('DisableUpdateMode', false);

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('GroupSwitch'), !$this->ReadPropertyBoolean('EnableGroupSwitch'));

        //Validation
        if ($this->ValidateConfiguration()) {
            $this->UpdateGroup();
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'Message from SenderID ' . $SenderID . ' with Message ' . $Message . "\r\n Data: " . print_r($Data, true), 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:

                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp value changed
                //$Data[5] = timestamp last value

                if ($this->CheckMaintenance()) {
                    return;
                }

                //Check variable
                if (in_array($SenderID, array_column(json_decode($this->ReadPropertyString('Variables'), true), 'ID'))) {
                    $scriptText = self::MODULE_PREFIX . '_UpdateGroup(' . $this->InstanceID . ');';
                    @IPS_RunScriptText($scriptText);
                }

                //Check trigger variable
                $existing = false;
                $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
                foreach ($variables as $variable) {
                    if (array_key_exists('PrimaryCondition', $variable)) {
                        $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                        if ($primaryCondition != '') {
                            if (array_key_exists(0, $primaryCondition)) {
                                if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                    $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                    if ($id == $SenderID) {
                                        $existing = true;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($existing) {
                    $valueChanged = 'false';
                    if ($Data[1]) {
                        $valueChanged = 'true';
                    }
                    $scriptText = self::MODULE_PREFIX . '_CheckTriggerVariable(' . $this->InstanceID . ', ' . $SenderID . ', ' . $valueChanged . ');';
                    @IPS_RunScriptText($scriptText);
                }
                break;

        }
    }

    public function CreateCommandControlInstance(): void
    {
        $id = @IPS_CreateInstance(self::ABLAUFSTEUERUNG_MODULE_GUID);
        if (is_int($id)) {
            IPS_SetName($id, 'Ablaufsteuerung');
            echo 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            echo 'Instanz konnte nicht erstellt werden!';
        }
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        if ($Ident == 'GroupSwitch') {
            $this->ToggleGroup($Value);
        }
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function ValidateConfiguration(): bool
    {
        $result = true;
        $status = 102;
        //Maintenance mode
        if ($this->CheckMaintenance()) {
            $result = false;
            $status = 104;
        }
        $this->SetStatus($status);
        return $result;
    }

    private function CheckMaintenance(): bool
    {
        $result = false;
        if (!$this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Instanz ist inaktiv!', 0);
            $result = true;
        }
        return $result;
    }
}