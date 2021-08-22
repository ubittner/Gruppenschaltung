<?php

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Gruppenschaltung/tree/main/Gruppenschaltung
 */

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class Gruppenschaltung extends IPSModule
{
    //Helper
    use GS_backupRestore;
    use GS_controlGroup;
    use GS_triggerVariable;

    //Constants
    private const LIBRARY_GUID = '{90BFC35E-83A6-6A64-9516-CD79E4A45C3B}';
    private const MODULE_NAME = 'Gruppenschaltung';
    private const MODULE_PREFIX = 'UBGS';
    private const DELAY_MILLISECONDS = 250;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyBoolean('MaintenanceMode', false);
        $this->RegisterPropertyString('Variables', '[]');
        $this->RegisterPropertyString('TriggerVariables', '[]');

        //Variables
        $this->RegisterVariableBoolean('GroupSwitch', 'Gruppenschaltung', '~Switch', 10);
        $this->EnableAction('GroupSwitch');

        //Attribute
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

        $this->WriteAttributeBoolean('DisableUpdateMode', false);

        //Validation
        if ($this->ValidateConfiguration()) {
            //Register references and update messages
            $this->SendDebug(__FUNCTION__, 'Referenzen und Nachrichten werden registriert.', 0);
            $propertyNames = ['Variables', 'TriggerVariables'];
            foreach ($propertyNames as $propertyName) {
                foreach (json_decode($this->ReadPropertyString($propertyName)) as $variable) {
                    if ($variable->Use) {
                        $id = $variable->ID;
                        if ($id != 0 && @IPS_ObjectExists($id)) {
                            $this->RegisterReference($id);
                            $this->RegisterMessage($id, VM_UPDATE);
                        }
                    }
                }
            }

            $this->UpdateGroup();
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'Message from SenderID ' . $SenderID . ' with Message ' . $Message . "\r\n Data: " . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }
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

                if ($this->CheckMaintenanceMode()) {
                    return;
                }

                //Check variable
                if (array_search($SenderID, array_column(json_decode($this->ReadPropertyString('Variables'), true), 'ID')) !== false) {
                    $scriptText = self::MODULE_PREFIX . '_UpdateGroup(' . $this->InstanceID . ');';
                    @IPS_RunScriptText($scriptText);
                }

                //Check trigger variable
                if (array_search($SenderID, array_column(json_decode($this->ReadPropertyString('TriggerVariables'), true), 'ID')) !== false) {
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

    public function GetConfigurationForm()
    {
        $form = [];

        #################### Elements

        ########## Functions

        ##### Functions panel

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Wartungsmodus',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'MaintenanceMode',
                    'caption' => 'Wartungsmodus'
                ]
            ]
        ];

        ########## Group

        $variables = [];
        foreach (json_decode($this->ReadPropertyString('Variables')) as $variable) {
            $rowColor = '#FFC0C0'; # red
            $id = $variable->ID;
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $rowColor = '#DFDFDF'; # grey
                if ($variable->Use) {
                    $rowColor = '#C0FFC0'; # light green
                }
            }
            $variables[] = ['rowColor' => $rowColor];
        }

        ##### Group panel

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Gruppe',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'Variables',
                    'caption'  => '',
                    'rowCount' => 10,
                    'add'      => true,
                    'delete'   => true,
                    'sort'     => [
                        'column'    => 'Order',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'Use',
                            'caption' => 'Aktiviert',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'Order',
                            'caption' => 'Reihenfolge',
                            'width'   => '130px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'minimum' => 0,
                                'maximum' => 255
                            ]
                        ],
                        [
                            'name'    => 'Description',
                            'caption' => 'Bezeichnung',
                            'width'   => '350px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'name'    => 'ID',
                            'caption' => 'Variable',
                            'width'   => 'auto',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_EnableConfigurationButton($id, $Variables["ID"], "GroupVariableConfigurationButton", 0);',
                            'edit'    => [
                                'type' => 'SelectVariable'
                            ]
                        ],
                        [
                            'name'    => 'SwitchingDelay',
                            'caption' => 'Schaltverzögerung',
                            'width'   => '180px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'NumberSpinner',
                                'suffix'  => ' Millisekunden',
                                'minimum' => 0,
                                'maximum' => 5000
                            ]
                        ]
                    ],
                    'values' => $variables,
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'GroupVariableConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'enabled'  => false,
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        ########## Trigger variables

        $triggerVariables = [];
        foreach (json_decode($this->ReadPropertyString('TriggerVariables')) as $variable) {
            $rowColor = '#FFC0C0'; # red
            $id = $variable->ID;
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $rowColor = '#DFDFDF'; # grey
                if ($variable->Use) {
                    $rowColor = '#C0FFC0'; # light green
                }
            }
            $triggerVariables[] = ['rowColor' => $rowColor];
        }

        ##### Trigger variables panel

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Auslöser',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'TriggerVariables',
                    'caption'  => '',
                    'rowCount' => 10,
                    'add'      => true,
                    'delete'   => true,
                    'sort'     => [
                        'column'    => 'ID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'Use',
                            'caption' => 'Aktiviert',
                            'width'   => '100px',
                            'add'     => true,
                            'edit'    => [
                                'type' => 'CheckBox'
                            ]
                        ],
                        [
                            'name'    => 'ID',
                            'caption' => 'Auslösende Variable',
                            'width'   => 'auto',
                            'add'     => 0,
                            'onClick' => self::MODULE_PREFIX . '_EnableConfigurationButton($id, $TriggerVariables["ID"], "TriggerVariableConfigurationButton", 0);',
                            'edit'    => [
                                'type' => 'SelectVariable'
                            ]
                        ],
                        [
                            'name'    => 'Info',
                            'caption' => 'Info',
                            'width'   => '160px',
                            'add'     => '',
                            'visible' => false,
                            'edit'    => [
                                'type'    => 'Button',
                                'onClick' => self::MODULE_PREFIX . '_ShowVariableDetails($id, $ID);'
                            ]
                        ],
                        [
                            'name'    => 'TriggerType',
                            'caption' => 'Auslöseart',
                            'width'   => '280px',
                            'add'     => 7,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Bei Änderung',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Bei Aktualisierung',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Bei Grenzunterschreitung (einmalig)',
                                        'value'   => 2
                                    ],
                                    [
                                        'caption' => 'Bei Grenzunterschreitung (mehrmalig)',
                                        'value'   => 3
                                    ],
                                    [
                                        'caption' => 'Bei Grenzüberschreitung (einmalig)',
                                        'value'   => 4
                                    ],
                                    [
                                        'caption' => 'Bei Grenzüberschreitung (mehrmalig)',
                                        'value'   => 5
                                    ],
                                    [
                                        'caption' => 'Bei bestimmtem Wert (einmalig)',
                                        'value'   => 6
                                    ],
                                    [
                                        'caption' => 'Bei bestimmtem Wert (mehrmalig)',
                                        'value'   => 7
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name'    => 'TriggerValue',
                            'caption' => 'Auslösewert',
                            'width'   => '160px',
                            'add'     => '',
                            'edit'    => [
                                'type' => 'ValidationTextBox'
                            ]
                        ],
                        [
                            'name'    => 'TriggerAction',
                            'caption' => 'Auslöseaktion',
                            'width'   => '200px',
                            'add'     => 0,
                            'edit'    => [
                                'type'    => 'Select',
                                'options' => [
                                    [
                                        'caption' => 'Gruppe ausschalten',
                                        'value'   => 0
                                    ],
                                    [
                                        'caption' => 'Gruppe einschalten',
                                        'value'   => 1
                                    ],
                                    [
                                        'caption' => 'Gruppe umschalten',
                                        'value'   => 2
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'values' => $triggerVariables,
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'TriggerVariableConfigurationButton',
                    'caption'  => 'Bearbeiten',
                    'enabled'  => false,
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        #################### Actions

        ##### Configuration panel

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Konfiguration',
            'items'   => [
                [
                    'type'    => 'Button',
                    'caption' => 'Neu einlesen',
                    'onClick' => self::MODULE_PREFIX . '_ReloadConfiguration($id);'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'SelectCategory',
                            'name'    => 'BackupCategory',
                            'caption' => 'Kategorie',
                            'width'   => '600px'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Sichern',
                            'onClick' => self::MODULE_PREFIX . '_CreateBackup($id, $BackupCategory);'
                        ]
                    ]
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'SelectScript',
                            'name'    => 'ConfigurationScript',
                            'caption' => 'Konfiguration',
                            'width'   => '600px'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'PopupButton',
                            'caption' => 'Wiederherstellen',
                            'popup'   => [
                                'caption' => 'Konfiguration wirklich wiederherstellen?',
                                'items'   => [
                                    [
                                        'type'    => 'Button',
                                        'caption' => 'Wiederherstellen',
                                        'onClick' => self::MODULE_PREFIX . '_RestoreConfiguration($id, $ConfigurationScript);'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        ##### Test center panel

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Schaltfunktion',
            'items'   => [
                [
                    'type' => 'TestCenter',
                ]
            ]
        ];

        #################### Status

        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $version = '[Version ' . $library['Version'] . '-' . $library['Build'] . ' vom ' . date('d.m.Y', $library['Date']) . ']';

        $form['status'] = [
            [
                'code'    => 101,
                'icon'    => 'active',
                'caption' => self::MODULE_NAME . ' wird erstellt',
            ],
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => self::MODULE_NAME . ' ist aktiv (ID ' . $this->InstanceID . ') ' . $version,
            ],
            [
                'code'    => 103,
                'icon'    => 'active',
                'caption' => self::MODULE_NAME . ' wird gelöscht (ID ' . $this->InstanceID . ') ' . $version,
            ],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => self::MODULE_NAME . ' ist inaktiv (ID ' . $this->InstanceID . ') ' . $version,
            ],
            [
                'code'    => 200,
                'icon'    => 'inactive',
                'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug! (ID ' . $this->InstanceID . ') ' . $version
            ]
        ];
        return json_encode($form);
    }

    public function ReloadConfiguration()
    {
        $this->ReloadForm();
    }

    public function ShowVariableDetails(int $VariableID): void
    {
        if ($VariableID == 0 || !@IPS_ObjectExists($VariableID)) {
            return;
        }
        //Variable
        echo 'ID: ' . $VariableID . "\n";
        echo 'Name: ' . IPS_GetName($VariableID) . "\n";
        $variable = IPS_GetVariable($VariableID);
        if (!empty($variable)) {
            $variableType = $variable['VariableType'];
            switch ($variableType) {
                case 0:
                    $variableTypeName = 'Boolean';
                    break;

                case 1:
                    $variableTypeName = 'Integer';
                    break;

                case 2:
                    $variableTypeName = 'Float';
                    break;

                case 3:
                    $variableTypeName = 'String';
                    break;

                default:
                    $variableTypeName = 'Unbekannt';
            }
            echo 'Variablentyp: ' . $variableTypeName . "\n";
        }
        //Profile
        $profile = @IPS_GetVariableProfile($variable['VariableProfile']);
        if (empty($profile)) {
            $profile = @IPS_GetVariableProfile($variable['VariableCustomProfile']);
        }
        if (!empty($profile)) {
            $profileType = $variable['VariableType'];
            switch ($profileType) {
                case 0:
                    $profileTypeName = 'Boolean';
                    break;

                case 1:
                    $profileTypeName = 'Integer';
                    break;

                case 2:
                    $profileTypeName = 'Float';
                    break;

                case 3:
                    $profileTypeName = 'String';
                    break;

                default:
                    $profileTypeName = 'Unbekannt';
            }
            echo 'Profilname: ' . $profile['ProfileName'] . "\n";
            echo 'Profiltyp: ' . $profileTypeName . "\n\n";
        }
        if (!empty($variable)) {
            echo "\nVariable:\n";
            print_r($variable);
        }
        if (!empty($profile)) {
            echo "\nVariablenprofil:\n";
            print_r($profile);
        }
    }

    public function EnableConfigurationButton(int $ObjectID, string $ButtonName, int $Type): void
    {
        //Variable
        $description = 'ID ' . $ObjectID . ' bearbeiten';
        //Instance
        if ($Type == 1) {
            $description = 'ID ' . $ObjectID . ' konfigurieren';
        }
        $this->UpdateFormField($ButtonName, 'caption', $description);
        $this->UpdateFormField($ButtonName, 'visible', true);
        $this->UpdateFormField($ButtonName, 'enabled', true);
        $this->UpdateFormField($ButtonName, 'objectID', $ObjectID);
    }

    #################### Request Action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'GroupSwitch':
                $this->ToggleGroup($Value);
                break;

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
        $maintenance = $this->CheckMaintenanceMode();
        if ($maintenance) {
            $result = false;
            $status = 104;
        }
        IPS_SetDisabled($this->InstanceID, $maintenance);
        $this->SetStatus($status);
        return $result;
    }

    private function CheckMaintenanceMode(): bool
    {
        $result = $this->ReadPropertyBoolean('MaintenanceMode');
        if ($result) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, der Wartungsmodus ist aktiv!', 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . __FUNCTION__ . ', Abbruch, der Wartungsmodus ist aktiv!', KL_WARNING);
        }
        return $result;
    }
}