<?php

declare(strict_types=1);
ini_set('ips.output_buffer', 2 * 1024 * 1024);

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
        $fastMatcher = [];
        foreach ($devices as $device) {
            $id++;
            $tree[] = [
                'name' => $this->Translate($device),
                'id'   => $id,
            ];
            $parent = $id;
            for ($group = 1; $group < 16; $group++) { // Groups
                $id++;
                $groupParent = $id;
                $tree[] = [
                    'name'   => $this->Translate('Group') . ' ' . strtoupper(dechex($group)),
                    'id'     => $id,
                    'parent' => $parent
                ];
                $isCounter = match ($device) {
                    'Energy', 'WaterGasOil', 'HeatQuantity' => true,
                    default => false
                };
                for ($channel = !$isCounter ? 0 : 1; $channel <= 9; $channel++) {
                    $id++;
                    $addresses = match ($device) {
                        'Switch'              => $this->getSwitchAddresses($group, $channel),
                        'Dimming'             => $this->getDimAddresses($group, $channel),
                        'Shutter'             => $this->getShutterAddresses($group, $channel),
                        'Energy'              => $this->getCounterAddresses($group, 0, $channel),
                        'WaterGasOil'         => $this->getCounterAddresses($group, 1, $channel),
                        'HeatQuantity'        => $this->getCounterAddresses($group, 2, $channel),
                        'Temperature'         => $this->getHeatingAddresses($group, 0, $channel),
                        'TemperatureHumidity' => $this->getHeatingAddresses($group, 1, $channel),
                        default               => '[]'
                    };
                    $tree[] = [
                        'name'   => $this->Translate('Channel') . ' ' . $channel,
                        'parent' => $groupParent,
                        'id'     => $id,
                        'create' => [
                            'moduleID'     => '{FB223058-3084-C5D0-C7A2-3B8D2E73FE8A}',
                            'name'         => sprintf('%s (%s %X, %s %d)', $this->Translate($device), $this->Translate('Group'), $group, $this->Translate('Channel'), $channel),
                            'configuration'=> [
                                'GroupAddresses' => $addresses,
                                'TTLID'          => ''
                            ],
                            'location' => [
                                'KNX quick',
                                $this->Translate($device),
                                $this->Translate('Group') . ' ' . strtoupper(dechex($group))
                            ]
                        ]
                    ];
                    //get the send addresses
                    $addresses = json_decode($addresses, true);
                    $sendAddresses = [];
                    foreach ($addresses as $address) {
                        $sendAddresses[] = $address['Address1'] . '/' . $address['Address2'] . '/' . $address['Address3'];
                    }

                    $fastMatcher[$id] = $sendAddresses;
                }
            }
        }

        //Insert the Instance IDS
        //Get the Instances of the KNX Device
        $availableInstances = IPS_GetInstanceListByModuleID('{FB223058-3084-C5D0-C7A2-3B8D2E73FE8A}');

        //Look if the Addresses Matches
        foreach ($availableInstances as $available) {
            $instance = json_decode(IPS_GetConfiguration($available), true);
            //Get instance addresses
            $addresses = [];
            $groupAddresses = json_decode($instance['GroupAddresses'], true);
            foreach ($groupAddresses as $address) {
                $addresses[] = $address['Address1'] . '/' . $address['Address2'] . '/' . $address['Address3'];
            }
            if ($instance['GroupAddresses'] !== '[]') {
                $treeID = array_search($addresses, $fastMatcher);
                //Get the corresponding tree id
                $treeKey = array_search($treeID, array_column($tree, 'id'));
                if (array_key_exists('create', $tree[$treeKey])) {
                    $tree[$treeKey]['instanceID'] = $available;
                }
            }
        }

        $form['elements'][0]['values'] = $tree;
        return json_encode($form);
    }

    public function SendDateTime()
    {
        $data = "\x80" .
            chr(100 + intval(date('y'))) .
            chr(intval(date('m'))) .
            chr(intval(date('d'))) .
            chr((intval(date('N')) << 5) + intval(date('H'))) .
            chr(intval(date('i'))) .
            chr(intval(date('s'))) .
            chr(intval(date('I')) ? 1 : 0) .
            chr(0);

        $json = [
            'DataID'        => '{42DFD4E4-5831-4A27-91B9-6FF1B2960260}',
            'Address1'      => 30,
            'Address2'      => 3,
            'Address3'      => 254,
            'Data'          => utf8_encode($data)
        ];
        if ($this->HasActiveParent()) {
            $this->SendDataToParent(json_encode($json));
        }
    }

    private function getSwitchAddresses(int $group, int $channel): string
    {

        return json_encode(
            [[
                'Address1'           => 15,
                'Address2'           => 0,
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Switch'),
                'Tag'                => 'lighting',
                'SubTag'             => '',
                'Type'               => 1,
                'Dimension'          => 1,
                'Mapping'            => $channel > 0 ? [[
                    'Address1' => 15,
                    'Address2' => 0,
                    'Address3' => $group * 16
                ], [
                    'Address1' => 15,
                    'Address2' => 1,
                    'Address3' => ($group * 16) + $channel
                ], [
                    'Address1' => 15,
                    'Address2' => 0,
                    'Address3' => 240
                ], [
                    'Address1' => 15,
                    'Address2' => 0,
                    'Address3' => 240 + $channel
                ]] : [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(false)
            ]]
        );
    }

    private function getDimAddresses(int $group, int $channel): string
    {
        return json_encode(
            [[
                'Address1'           => 15,
                'Address2'           => 4,
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Value'),
                'Tag'                => 'lighting',
                'SubTag'             => '',
                'Type'               => 5,
                'Dimension'          => 1,
                'Mapping'            => $channel > 0 ? [[
                    'Address1' => 15,
                    'Address2' => 4,
                    'Address3' => $group * 16
                ], [
                    'Address1' => 15,
                    'Address2' => 6,
                    'Address3' => ($group * 16) + $channel
                ], [
                    'Address1' => 15,
                    'Address2' => 4,
                    'Address3' => 240
                ], [
                    'Address1' => 15,
                    'Address2' => 4,
                    'Address3' => 240 + $channel
                ]] : [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ]]
        );
    }

    private function getShutterAddresses(int $group, int $channel)
    {
        return json_encode([
            [
                'Address1'           => 14,
                'Address2'           => 0,
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Up/Down'),
                'Tag'                => 'shading',
                'SubTag'             => '',
                'Type'               => 1,
                'Dimension'          => 1,
                'Mapping'            => $channel > 0 ? [[
                    'Address1' => 14,
                    'Address2' => 0,
                    'Address3' => $group * 16
                ], [
                    'Address1' => 14,
                    'Address2' => 0,
                    'Address3' => 240
                ], [
                    'Address1' => 14,
                    'Address2' => 0,
                    'Address3' => 240 + $channel
                ]] : [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ],
            [
                'Address1'           => 14,
                'Address2'           => 1,
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Lamella'),
                'Tag'                => 'shading',
                'SubTag'             => 'lamella',
                'Type'               => 1,
                'Dimension'          => 1,
                'Mapping'            => $channel > 0 ? [[
                    'Address1' => 14,
                    'Address2' => 1,
                    'Address3' => $group * 16
                ], [
                    'Address1' => 14,
                    'Address2' => 1,
                    'Address3' => 240
                ], [
                    'Address1' => 14,
                    'Address2' => 1,
                    'Address3' => 240 + $channel
                ]] : [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ]
        ]);
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
    private function getCounterAddresses(int $group, int $type, int $channel): string
    {
        switch ($type) {
            case 0:
                $tag = 'electrical';
                break;
            case 1:
                $tag = 'heating';
                break;
            case 2:
                $tag = 'domesticHotWater';
                break;
            default:
                die('Invalid type!');
        }

        //SerialNumber (S/N)
        $addresses = [[
            'Address1'           => 11,
            'Address2'           => 6,
            'Address3'           => ($group * 16) + $channel,
            'InitialName'        => $this->Translate('Serial Number'),
            'Type'               => 12,
            'Dimension'          => 0,
            'Tag'                => $tag,
            'SubTag'             => '',
            'Mapping'            => [],
            'CapabilityRead'     => boolval(false),
            'CapabilityWrite'    => boolval(true),
            'CapabilityReceive'  => boolval(true),
            'CapabilityTransmit' => boolval(false),
            'EmulateStatus'      => boolval(true)
        ]];

        //Status
        $addresses[] = [
            'Address1'           => 11,
            'Address2'           => 7,
            'Address3'           => ($group * 16) + $channel,
            'InitialName'        => $this->Translate('Status'),
            'Type'               => 1,
            'Dimension'          => 0,
            'Tag'                => $tag,
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
            'Address3'           => ($group * 16) + $channel,
            'InitialName'        => $this->Translate('Read Meter'),
            'Type'               => 1,
            'Dimension'          => 0,
            'Tag'                => $tag,
            'SubTag'             => '',
            'Mapping'            => [],
            'CapabilityRead'     => boolval(false),
            'CapabilityWrite'    => boolval(true),
            'CapabilityReceive'  => boolval(true),
            'CapabilityTransmit' => boolval(false),
            'EmulateStatus'      => boolval(true)
        ];

        if ($type == 0 /* Energy */) {

            //Power Forward (W)
            $addresses[] = [
                'Address1'           => 11,
                'Address2'           => 0,
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Power Forward (W)'),
                'Type'               => 14,
                'Dimension'          => 56,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Power Reverse (W)'),
                'Type'               => 14,
                'Dimension'          => 56,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Energy Forward (Wh)'),
                'Type'               => 13,
                'Dimension'          => 10,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Energy Forward (kWh)'),
                'Type'               => 13,
                'Dimension'          => 13,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Energy Reverse (Wh)'),
                'Type'               => 13,
                'Dimension'          => 10,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Energy Reverse (kWh)'),
                'Type'               => 13,
                'Dimension'          => 13,
                'Tag'                => $tag,
                'SubTag'             => '',
                'Mapping'            => [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];
        }

        if ($type == 1 /* Water, Gas, Oil */ || $type == 2 /* Heat Quantity */) {

            //Volume (l)
            $addresses[] = [
                'Address1'           => 12,
                'Address2'           => 6,
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Volume (l)'),
                'Type'               => 12,
                'Dimension'          => 1200,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Volume (m³)'),
                'Type'               => 12,
                'Dimension'          => 1201,
                'Tag'                => $tag,
                'SubTag'             => '',
                'Mapping'            => [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];
        }

        if ($type == 2 /* Heat Quantity */) {

            //Power (W)
            $addresses[] = [
                'Address1'           => 11,
                'Address2'           => 0,
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Power (W)'),
                'Type'               => 14,
                'Dimension'          => 36,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Flow (m³/h)'),
                'Type'               => 14,
                'Dimension'          => 1200,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Temperature Forward (°C)'),
                'Type'               => 14,
                'Dimension'          => 1200,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Temperature Reverse (°C)'),
                'Type'               => 9,
                'Dimension'          => 1,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Energy Heat (kWh)'),
                'Type'               => 13,
                'Dimension'          => 13,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Energy Heat (MWh)'),
                'Type'               => 13,
                'Dimension'          => 16,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Energy Cool (kWh)'),
                'Type'               => 13,
                'Dimension'          => 13,
                'Tag'                => $tag,
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
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Energy Cool (MWh)'),
                'Type'               => 13,
                'Dimension'          => 16,
                'Tag'                => $tag,
                'SubTag'             => '',
                'Mapping'            => [],
                'CapabilityRead'     => boolval(false),
                'CapabilityWrite'    => boolval(true),
                'CapabilityReceive'  => boolval(true),
                'CapabilityTransmit' => boolval(false),
                'EmulateStatus'      => boolval(true)
            ];
        }

        return json_encode($addresses);
    }

    /**
     * Type:
     *
     * 	0 = Temperature
     * 	1 = Temperature/Humidity
     *
     */
    private function getHeatingAddresses(int $group, int $type, int $channel)
    {
        //Status
        $addresses = [[
            'Address1'           => 13,
            'Address2'           => 7,
            'Address3'           => ($group * 16) + $channel,
            'InitialName'        => $this->Translate('Status'),
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
        ]];

        //Temperature
        $addresses[] = [
            'Address1'           => 13,
            'Address2'           => 5,
            'Address3'           => ($group * 16) + $channel,
            'InitialName'        => $this->Translate('Temperature'),
            'Type'               => 9,
            'Dimension'          => 1,
            'Tag'                => 'heating',
            'SubTag'             => '',
            'Mapping'            => [],
            'CapabilityRead'     => boolval(false),
            'CapabilityWrite'    => boolval(true),
            'CapabilityReceive'  => boolval(true),
            'CapabilityTransmit' => boolval(false),
            'EmulateStatus'      => boolval(true)
        ];

        if ($type == 1 /* Temperature/Humidity */) {

            //Humidity
            $addresses[] = [
                'Address1'           => 13,
                'Address2'           => 6,
                'Address3'           => ($group * 16) + $channel,
                'InitialName'        => $this->Translate('Humidity'),
                'Type'               => 9,
                'Dimension'          => 7,
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

        return json_encode($addresses);
    }

}
