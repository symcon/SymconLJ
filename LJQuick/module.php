<?php

declare(strict_types=1);

class LJQuick extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterTimer('DateTimeTimer', 10 * 60 * 1000, 'LJ_SendDateTime($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Set receive filter to something that will never match
        $this->SetReceiveDataFilter('(?!)');

        //Connect to available splitter or create a new one
        $this->ConnectParent('{1C902193-B044-43B8-9433-419F09C641B8}');
    }

    public function GetConfigurationForm(): string
    {
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        $devices = ['Switch', 'Dimming', 'Shutter', 'Energy', 'WaterGasOil', 'HeatQuantity', 'Temperature', 'TemperatureHumidity'];

        $tree = [];
        $id = 0;
        foreach ($devices as $device) {
            $id++;
            $tree[] = [
                'name' => $device,
                'id'   => $id,
            ];
            $parent = $id;
            for ($i = 10; $i < 16; $i++) {
                $id++;
                $tree[] = [
                    'name'   => $this->Translate('Group' . ' ' . strtoupper(dechex($i))),
                    'parent' => $parent,
                    'id'     => $id,
                    'create' => [
                        'moduleID'     => '{FB223058-3084-C5D0-C7A2-3B8D2E73FE8A}',
                        'configuration'=> [
                            'GroupAddresses' => match ($device) {
                                'Switch'              => $this->GenerateSwitch($i),
                                'Dimming'             => $this->GenerateDim($i),
                                'Shutter'             => $this->GenerateShutter($i),
                                'Energy'              => $this->GenerateCounter($i, 0),
                                'WaterGasOil'         => $this->GenerateCounter($i, 1),
                                'HeatQuantity'        => $this->GenerateCounter($i, 2),
                                'Temperature'         => $this->GenerateHeating($i, 0),
                                'TemperatureHumidity' => $this->GenerateHeating($i, 1),
                            },
                            'TTLID'          => ''
                        ]
                    ]
                ];
            }
        }

        $form['elements'][0]['values'] = $tree;
        //  var_dump($form);
        return json_encode($form);
    }

    public function GenerateSwitch(int $Group): string
    {
        $addresses = [];
        for ($Channel = 0; $Channel <= 9; $Channel++) {
            $mapping = [];
            if ($Channel > 0) {
                $mapping[] = [
                    'Address1' => 15,
                    'Address2' => 0,
                    'Address3' => $Group * 16
                ];
                $mapping[] = [
                    'Address1' => 15,
                    'Address2' => 1,
                    'Address3' => ($Group * 16) + $Channel
                ];
                $mapping[] = [
                    'Address1' => 15,
                    'Address2' => 0,
                    'Address3' => 240
                ];
                $mapping[] = [
                    'Address1' => 15,
                    'Address2' => 0,
                    'Address3' => 240 + $Channel
                ];
            }
            $addresses[] = [
                'Address1'           => 15,
                'Address2'           => 0,
                'Address3'           => ($Group * 16) + $Channel, 'Tag' => 'electrical',
                //'Name' => 'Switch Channel'. $Channel,
                'SubTag'             => '',
                'Type'               => 1,
                'Dimension'          => 1,
                'Mapping'            => $mapping,
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(false)
            ];
        }
        return json_encode($addresses);
    }

    public function GenerateDim(int $Group)
    {
        $addresses = [];
        for ($Channel = 0; $Channel <= 9; $Channel++) {
            $mapping = [];
            if ($Channel > 0) {
                $mapping[] = [
                    'Address1' => 15,
                    'Address2' => 4,
                    'Address3' => $Group * 16
                ];
                $mapping[] = [
                    'Address1' => 15,
                    'Address2' => 6,
                    'Address3' => ($Group * 16) + $Channel
                ];
                $mapping[] = [
                    'Address1' => 15,
                    'Address2' => 4,
                    'Address3' => 240
                ];
                $mapping[] = [
                    'Address1' => 15,
                    'Address2' => 4,
                    'Address3' => 240 + $Channel
                ];
            }
            $addresses[] = [
                'Address1'           => 15,
                'Address2'           => 4,
                'Address3'           => ($Group * 16) + $Channel,
                'Tag'                => 'electrical',
                'SubTag'             => '',
                'Type'               => 5,
                'Dimension'          => 1,
                'Mapping'            => $mapping,
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];
        }
        return json_encode($addresses);
    }

    public function GenerateShutter(int $Group)
    {
        $qid = @IPS_GetObjectIDByIdent('KNXQuick', 0);
        if ($qid === false) {
            $qid = IPS_CreateCategory();
            IPS_SetName($qid, 'KNX quick');
            IPS_SetIdent($qid, 'KNXQuick');
        }

        $sid = @IPS_GetObjectIDByIdent('Shutter', $qid);
        if ($sid === false) {
            $sid = IPS_CreateCategory();
            IPS_SetName($sid, 'Shutter');
            IPS_SetIdent($sid, 'Shutter');
            IPS_SetParent($sid, $qid);
            IPS_SetPosition($sid, 2);
        }

        for ($Channel = 0; $Channel <= 9; $Channel++) {
            $mappingMove = [];
            $mappingStop = [];
            if ($Channel > 0) {

                $mappingMove[] = [
                    'Address1' => 14,
                    'Address2' => 0,
                    'Address3' => $Group * 16
                ];
                $mappingMove[] = [
                    'Address1' => 14,
                    'Address2' => 0,
                    'Address3' => 240
                ];
                $mappingMove[] = [
                    'Address1' => 14,
                    'Address2' => 0,
                    'Address3' => 240 + $Channel
                ];
                $mappingStop[] = [
                    'Address1' => 14,
                    'Address2' => 1,
                    'Address3' => $Group * 16
                ];
                $mappingStop[] = [
                    'Address1' => 14,
                    'Address2' => 1,
                    'Address3' => 240
                ];
                $mappingStop[] = [
                    'Address1' => 14,
                    'Address2' => 1,
                    'Address3' => 240 + $Channel
                ];
            }

            $addresses = [[
                'Address1'           => 14,
                'Address2'           => 0,
                'Address3'           => ($Group * 16) + $Channel,
                'Tag'                => 'shutter',
                'SubTag'             => '',
                'Type'               => 1,
                'Dimension'          => 1,
                'Mapping'            => $mappingMove,
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ], [
                'Address1'           => 15,
                'Address2'           => 0,
                'Address3'           => ($Group * 16) + $Channel, 'Tag' => 'electrical',
                'SubTag'             => '',
                'Type'               => 1,
                'Dimension'          => 1,
                'Mapping'            => $mappingStop,
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ]];
        }
        return json_encode($addresses);
    }

    /**
     *
     * Type:
     *
     * 	0 = Energy
     * 	1 = Water, Gas, Oil
     *  2 = Heat Quantity
     *
     */
    public function GenerateCounter(int $Group, int $Type)
    {
        switch ($Type) {
            case 0:
                $TypeName = 'Energy';
                break;
            case 1:
                $TypeName = 'Water, Gas, Oil';
                break;
            case 2:
                $TypeName = 'Heat Quantity';
                break;
            default:
                die('Invalid type!');
        }

        $addresses = [];
        for ($Channel = 1; $Channel <= 9; $Channel++) {
            //SerialNumber (S/N)
            $addresses[] = [
                'Address1'           => 11,
                'Address2'           => 6,
                'Address3'           => ($Group * 16) + $Channel,
                'Type'               => 12,
                'Dimension'          => 0,
                'Tag'                => 'electrical',
                'SubTag'             => '',
                'Mapping'            => [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];

            //Status
            $addresses[] = [
                'Address1'           => 11,
                'Address2'           => 7,
                'Address3'           => ($Group * 16) + $Channel,
                'Type'               => 1,
                'Dimension'          => 0,
                'Tag'                => 'electrical',
                'SubTag'             => '',
                'Mapping'            => [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];

            //Read Meter
            $addresses[] = [
                'Address1'           => 14,
                'Address2'           => 7,
                'Address3'           => ($Group * 16) + $Channel,
                'Type'               => 1,
                'Dimension'          => 0,
                'Tag'                => 'electrical',
                'SubTag'             => '',
                'Mapping'            => [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];

            if ($Type == 0 /* Energy */) {

                //Power Forward (W)
                $addresses[] = [
                    'Address1'           => 11,
                    'Address2'           => 0,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 14,
                    'Dimension'          => 0,
                    'Tag'                => 'electrical',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Power Reverse (W)
                $addresses[] = [
                    'Address1'           => 11,
                    'Address2'           => 1,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 14,
                    'Dimension'          => 0,
                    'Tag'                => 'electrical',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Energy Forward (Wh)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 0,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'electrical',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Energy Forward (kWh)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 1,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'electrical',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Energy Reverse (Wh)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 3,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'electrical',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Energy Reverse (kWh)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 4,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'electrical',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];
            }

            if ($Type == 1 /* Water, Gas, Oil */ || $Type == 2 /* Heat Quantity */) {

                //Volume (l)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 6,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'domesticHotWater',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Volume (m^3)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 7,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'domesticHotWater',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];
            }

            if ($Type == 2 /* Heat Quantity */) {

                //Power (W)
                $addresses[] = [
                    'Address1'           => 11,
                    'Address2'           => 0,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 14,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Flow (m^3/h)
                $addresses[] = [
                    'Address1'           => 11,
                    'Address2'           => 2,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 14,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Temperature Forward (°C)
                $addresses[] = [
                    'Address1'           => 11,
                    'Address2'           => 4,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 14,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Temperature Reverse (°C)
                $addresses[] = [
                    'Address1'           => 11,
                    'Address2'           => 5,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 9,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Energy Heat (kWh)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 1,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Energy Heat (MWh)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 2,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Energy Cool (kWh)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 4,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];

                //Energy Cool (MWh)
                $addresses[] = [
                    'Address1'           => 12,
                    'Address2'           => 5,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 12,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];
            }
        }
        return json_encode($addresses);
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     *
     * Type:
     *
     * 	0 = Temperature
     * 	1 = Temperature/Humidity
     *
     * LJ_GenerateHeating($id, $Group, $Type);
     *
     */
    public function GenerateHeating(int $Group, int $Type)
    {
        switch ($Type) {
            case 0:
                $TypeName = 'Temperature';
                break;
            case 1:
                $TypeName = 'Temperature/Humidity';
                break;
            default:
                die('Invalid type!');
        }
        $addresses = [];
        for ($Channel = 0; $Channel <= 9; $Channel++) {

            //Status
            $addresses[] = [
                'Address1'           => 13,
                'Address2'           => 7,
                'Address3'           => ($Group * 16) + $Channel,
                'Type'               => 1,
                'Dimension'          => 0,
                'Tag'                => 'heating',
                'SubTag'             => '',
                'Mapping'            => [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];

            //Temperature
            $addresses[] = [
                'Address1'           => 13,
                'Address2'           => 5,
                'Address3'           => ($Group * 16) + $Channel,
                'Type'               => 9,
                'Dimension'          => 0,
                'Tag'                => 'heating',
                'SubTag'             => '',
                'Mapping'            => [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];

            if ($Type == 1 /* Temperature/Humidity */) {

                //Humidity
                $addresses[] = [
                    'Address1'           => 13,
                    'Address2'           => 6,
                    'Address3'           => ($Group * 16) + $Channel,
                    'Type'               => 9,
                    'Dimension'          => 0,
                    'Tag'                => 'heating',
                    'SubTag'             => '',
                    'Mapping'            => [],
                    'CapabilityRead'     => boolval(false),
                    'CapabilityWrite'    => boolval(true),
                    'CapabilityReceive'  => boolval(true),
                    'CapabilityTransmit' => boolval(false),
                    'EmulateStatus'      => boolval(true)
                ];
            }
        }
        return json_encode($addresses);
    }

    public function SendDateTime()
    {

        //Require at least Version 5.1 from 01.05.2019
        if (IPS_GetKernelDate() <= 1556734554) {
            return;
        }

        $data = "\x80" .
            chr(100 + intval(date('y'))) .
            chr(intval(date('m'))) .
            chr(intval(date('d'))) .
            chr((intval(date('N')) << 5) + intval(date('H'))) .
            chr(intval(date('i'))) .
            chr(intval(date('s'))) .
            chr(intval(date('I')) ? 1 : 0) .
            chr(0);

        if (floatval(IPS_GetKernelVersion()) >= 5.4) {
            $json = [
                'DataID'        => '{42DFD4E4-5831-4A27-91B9-6FF1B2960260}',
                'Address1'      => 30,
                'Address2'      => 3,
                'Address3'      => 254,
                'Data'          => utf8_encode($data)
            ];
        } else {
            $header = "\x00\x00\xF3\xFE"; //30/3/254
            $json = [
                'DataID' => '{42DFD4E4-5831-4A27-91B9-6FF1B2960260}',
                'Header' => utf8_encode($header),
                'Data'   => utf8_encode($data)
            ];
        }

        if ($this->HasActiveParent()) {
            $this->SendDataToParent(json_encode($json));
        }
    }

}
