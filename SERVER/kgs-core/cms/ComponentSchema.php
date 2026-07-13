<?php

class ComponentSchema
{
    /**
     * Central registry of all component definitions
     * SINGLE SOURCE OF TRUTH: fields
     */
    public static function get(): array
    {
        return [

            'hero' => [
                'fields' => [
                    'image' => [
                        'type' => 'image',
                        'label' => 'Hero Image',
                        'default' => '',
                        'required' => false
                    ],
                    'title' => [
                        'type' => 'text',
                        'label' => 'Title',
                        'default' => '',
                        'required' => false
                    ],
                    'subtitle' => [
                        'type' => 'text',
                        'label' => 'Subtitle',
                        'default' => '',
                        'required' => false
                    ],
                    'overlay' => [
                        'type' => 'range',
                        'label' => 'Overlay Strength',
                        'default' => 0.3,
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                        'required' => false
                    ],
                    'text_position' => [
                        'type' => 'select',
                        'label' => 'Text Position',
                        'default' => 'center',
                        'options' => ['left', 'center', 'right'],
                        'required' => false
                    ],
                    'text_color' => [
                        'type' => 'select',
                        'label' => 'Text Color',
                        'default' => 'light',
                        'options' => ['light', 'dark'],
                        'required' => false
                    ]
                ]
            ],

            'live-feed' => [
                'fields' => [
                    'limit' => [
                        'type' => 'number',
                        'label' => 'Limit',
                        'default' => 5,
                        'required' => false
                    ]
                ]
            ],

			'rich-text' => [
				'fields' => [
					'title' => [
						'type' => 'text',
						'label' => 'Title',
						'default' => '',
						'required' => false
					],
					'content' => [
						'type' => 'textarea',
						'label' => 'Content (HTML allowed)',
						'default' => '',
						'required' => false
					],
					'align' => [
						'type' => 'select',
						'label' => 'Title Alignment',
						'default' => 'left',
						'options' => ['left', 'center', 'right'],
						'required' => false
					]
				]
			],
        ];
    }

    /**
     * Get schema for one component
     */
    public static function getSchema(string $type): array
    {
        $all = self::get();

        return $all[$type] ?? [
            'fields' => []
        ];
    }

    /**
     * Safe field extraction helper (prevents renderer breakage)
     */
    public static function getFields(string $type): array
    {
        return self::getSchema($type)['fields'] ?? [];
    }

    /**
     * Default values extractor (used by renderer/editor)
     */
    public static function getDefaults(string $type): array
    {
        $fields = self::getFields($type);
        $defaults = [];

        foreach ($fields as $key => $config) {
            $defaults[$key] = $config['default'] ?? null;
        }

        return $defaults;
    }

    /**
     * Required fields extractor (fixes renderer expectation mismatch)
     */
    public static function getRequired(string $type): array
    {
        $fields = self::getFields($type);
        $required = [];

        foreach ($fields as $key => $config) {
            if (!empty($config['required'])) {
                $required[] = $key;
            }
        }

        return $required;
    }
}