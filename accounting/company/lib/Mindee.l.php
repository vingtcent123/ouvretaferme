<?php
namespace company;

Class MindeeLib {

	const API_URL = 'https://api.mindee.net/v1/products/mindee/invoices/v4/predict';

	public static function getInvoiceData(string $filepath): array {


		return json_decode('{
    "extras": {},
    "finished_at": "2025-06-25T08:08:41.724243",
    "is_rotation_applied": true,
    "pages": [
        {
            "extras": {},
            "id": 0,
            "orientation": {"value": 0},
            "prediction": {
                "billing_address": {
                    "address_complement": null,
                    "city": null,
                    "confidence": 0,
                    "country": null,
                    "po_box": null,
                    "polygon": [],
                    "postal_code": null,
                    "state": null,
                    "street_name": null,
                    "street_number": null,
                    "value": null
                },
                "category": {
                    "confidence": 0.9,
                    "value": "accommodation"
                },
                "customer_address": {
                    "address_complement": null,
                    "city": "SAINT-AMANT-TALLENDE",
                    "confidence": 1,
                    "country": null,
                    "po_box": null,
                    "polygon": [
                        [0.645, 0.099],
                        [0.86, 0.099],
                        [0.86, 0.121],
                        [0.645, 0.121]
                    ],
                    "postal_code": "63450",
                    "state": null,
                    "street_name": "RUE DE PAGNAT",
                    "street_number": "21",
                    "value": "21 RUE DE PAGNAT 63450 SAINT-AMANT-TALLENDE"
                },
                "customer_company_registrations": [],
                "customer_id": {
                    "confidence": 1,
                    "polygon": [
                        [0.738, 0.159],
                        [0.802, 0.159],
                        [0.802, 0.166],
                        [0.738, 0.166]
                    ],
                    "value": "1643193B"
                },
                "customer_name": {
                    "confidence": 0.99,
                    "polygon": [
                        [0.644, 0.085],
                        [0.76, 0.085],
                        [0.76, 0.09],
                        [0.644, 0.09]
                    ],
                    "raw_value": "MME GUTH ELISE",
                    "value": "MME GUTH ELISE"
                },
                "date": {
                    "confidence": 0.99,
                    "polygon": [
                        [0.671, 0.052],
                        [0.751, 0.052],
                        [0.751, 0.062],
                        [0.671, 0.062]
                    ],
                    "value": "2025-03-31"
                },
                "document_type": {
                    "value": "INVOICE"
                },
                "document_type_extended": {
                    "confidence": 0.98,
                    "value": "INVOICE"
                },
                "due_date": {
                    "confidence": 0.99,
                    "is_computed": false,
                    "polygon": [
                        [0.553, 0.354],
                        [0.635, 0.354],
                        [0.635, 0.365],
                        [0.553, 0.365]
                    ],
                    "value": "2027-03-31"
                },
                "invoice_number": {
                    "confidence": 1,
                    "polygon": [
                        [0.477, 0.054],
                        [0.634, 0.054],
                        [0.634, 0.06],
                        [0.477, 0.06]
                    ],
                    "value": "F265 2479102-25/002"
                },
                "line_items": [
                    {
                        "confidence": 0.98,
                        "description": "Smartphone XIAOMI Redmi Note 14 Noir",
                        "polygon": [
                            [
                                0.311,
                                0.296
                            ],
                            [
                                0.977,
                                0.296
                            ],
                            [
                                0.977,
                                0.301
                            ],
                            [
                                0.311,
                                0.301
                            ]
                        ],
                        "product_code": "0001216819",
                        "quantity": 1,
                        "tax_amount": null,
                        "tax_rate": 20,
                        "total_amount": 268.92,
                        "unit_measure": null,
                        "unit_price": 268.92
                    },
                    {
                        "confidence": 0.96,
                        "description": "ECO-PART DEEE",
                        "polygon": [
                            [
                                0.31,
                                0.309
                            ],
                            [
                                0.977,
                                0.309
                            ],
                            [
                                0.977,
                                0.319
                            ],
                            [
                                0.31,
                                0.319
                            ]
                        ],
                        "product_code": null,
                        "quantity": null,
                        "tax_amount": null,
                        "tax_rate": null,
                        "total_amount": 3.07,
                        "unit_measure": null,
                        "unit_price": null
                    }
                ],
                "locale": {
                    "confidence": 0.94,
                    "country": "FR",
                    "currency": "EUR",
                    "language": "fr",
                    "value": "fr-FR"
                },
                "orientation": {
                    "confidence": 0.99,
                    "degrees": 0
                },
                "payment_date": {
                    "confidence": 0.99,
                    "polygon": [
                        [0.553, 0.354],
                        [0.635, 0.354],
                        [0.635, 0.365],
                        [0.553, 0.365]
                    ],
                    "value": "2027-03-31"
                },
                "po_number": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "reference_numbers": [
                    {
                        "confidence": 1,
                        "polygon": [
                            [
                                0.138,
                                0.964
                            ],
                            [
                                0.138,
                                0.969
                            ],
                            [
                                0.165,
                                0.969
                            ],
                            [
                                0.165,
                                0.964
                            ]
                        ],
                        "value": "L.223-1"
                    }
                ],
                "shipping_address": {
                    "address_complement": null,
                    "city": null,
                    "confidence": 0,
                    "country": null,
                    "po_box": null,
                    "polygon": [],
                    "postal_code": null,
                    "state": null,
                    "street_name": null,
                    "street_number": null,
                    "value": null
                },
                "subcategory": {
                    "confidence": 0.9,
                    "value": null
                },
                "supplier_address": {
                    "address_complement": null,
                    "city": "Lesquin",
                    "confidence": 1,
                    "country": null,
                    "po_box": null,
                    "polygon": [
                        [0.309, 0.703],
                        [0.459, 0.703],
                        [0.459, 0.724],
                        [0.309, 0.724]
                    ],
                    "postal_code": "59810",
                    "state": null,
                    "street_name": "Avenue de la Motte",
                    "street_number": null,
                    "value": "Avenue de la Motte 59810 Lesquin"
                },
                "supplier_company_registrations": [
                    {
                        "confidence": 1,
                        "polygon": [
                            [
                                0.72,
                                0.718
                            ],
                            [
                                0.86,
                                0.718
                            ],
                            [
                                0.86,
                                0.724
                            ],
                            [
                                0.72,
                                0.724
                            ]
                        ],
                        "type": "VAT NUMBER",
                        "value": "FR78347384570"
                    },
                    {
                        "confidence": 1,
                        "polygon": [
                            [
                                0.361,
                                0.129
                            ],
                            [
                                0.475,
                                0.129
                            ],
                            [
                                0.475,
                                0.136
                            ],
                            [
                                0.361,
                                0.136
                            ]
                        ],
                        "type": "SIRET",
                        "value": "34738457001613"
                    },
                    {
                        "confidence": 1,
                        "polygon": [
                            [
                                0.746,
                                0.703
                            ],
                            [
                                0.835,
                                0.703
                            ],
                            [
                                0.835,
                                0.709
                            ],
                            [
                                0.746,
                                0.709
                            ]
                        ],
                        "type": "SIREN",
                        "value": "347384570"
                    }
                ],
                "supplier_email": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "supplier_name": {
                    "confidence": 0.99,
                    "polygon": [
                        [0.021, 0.109],
                        [0.292, 0.109],
                        [0.292, 0.145],
                        [0.021, 0.145]
                    ],
                    "raw_value": "boulanger",
                    "value": "BOULANGER"
                },
                "supplier_payment_details": [],
                "supplier_phone_number": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "supplier_website": {
                    "confidence": 1,
                    "polygon": [
                        [0.324, 0.964],
                        [0.421, 0.964],
                        [0.421, 0.98],
                        [0.324, 0.98]
                    ],
                    "value": "www.bloctel.gouv.fr"
                },
                "taxes": [
                    {
                        "base": 226.66,
                        "confidence": 1,
                        "polygon": [
                            [
                                0.638,
                                0.446
                            ],
                            [
                                0.638,
                                0.453
                            ],
                            [
                                0.977,
                                0.453
                            ],
                            [
                                0.977,
                                0.446
                            ]
                        ],
                        "rate": 20,
                        "value": 45.33
                    }
                ],
                "total_amount": {
                    "confidence": 1,
                    "polygon": [
                        [0.93, 0.491],
                        [0.977, 0.491],
                        [0.977, 0.498],
                        [0.93, 0.498]
                    ],
                    "value": 271.99
                },
                "total_net": {
                    "confidence": 1,
                    "polygon": [
                        [0.93, 0.416],
                        [0.977, 0.416],
                        [0.977, 0.422],
                        [0.93, 0.422]
                    ],
                    "value": 226.66
                },
                "total_tax": {
                    "confidence": 1,
                    "polygon": [
                        [0.938, 0.446],
                        [0.977, 0.446],
                        [0.977, 0.452],
                        [0.938, 0.452]
                    ],
                    "value": 45.33
                }
            }
        },
        {
            "extras": {},
            "id": 1,
            "orientation": {"value": 0},
            "prediction": {
                "billing_address": {
                    "address_complement": null,
                    "city": null,
                    "confidence": 0,
                    "country": null,
                    "po_box": null,
                    "polygon": [],
                    "postal_code": null,
                    "state": null,
                    "street_name": null,
                    "street_number": null,
                    "value": null
                },
                "category": {
                    "confidence": 0.51,
                    "value": "transport"
                },
                "customer_address": {
                    "address_complement": null,
                    "city": null,
                    "confidence": 0,
                    "country": null,
                    "po_box": null,
                    "polygon": [],
                    "postal_code": null,
                    "state": null,
                    "street_name": null,
                    "street_number": null,
                    "value": null
                },
                "customer_company_registrations": [],
                "customer_id": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "customer_name": {
                    "confidence": 0,
                    "polygon": [],
                    "raw_value": null,
                    "value": null
                },
                "date": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "document_type": {
                    "value": "INVOICE"
                },
                "document_type_extended": {
                    "confidence": 0.52,
                    "value": "INVOICE"
                },
                "due_date": {
                    "confidence": 0,
                    "is_computed": false,
                    "polygon": [],
                    "value": null
                },
                "invoice_number": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "line_items": [],
                "locale": {
                    "confidence": 0.94,
                    "country": "FR",
                    "currency": "EUR",
                    "language": "fr",
                    "value": "fr-FR"
                },
                "orientation": {
                    "confidence": 0.99,
                    "degrees": 0
                },
                "payment_date": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "po_number": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "reference_numbers": [],
                "shipping_address": {
                    "address_complement": null,
                    "city": null,
                    "confidence": 0,
                    "country": null,
                    "po_box": null,
                    "polygon": [],
                    "postal_code": null,
                    "state": null,
                    "street_name": null,
                    "street_number": null,
                    "value": null
                },
                "subcategory": {
                    "confidence": 0.51,
                    "value": "taxi"
                },
                "supplier_address": {
                    "address_complement": null,
                    "city": null,
                    "confidence": 0,
                    "country": null,
                    "po_box": null,
                    "polygon": [],
                    "postal_code": null,
                    "state": null,
                    "street_name": null,
                    "street_number": null,
                    "value": null
                },
                "supplier_company_registrations": [],
                "supplier_email": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "supplier_name": {
                    "confidence": 0,
                    "polygon": [],
                    "raw_value": null,
                    "value": null
                },
                "supplier_payment_details": [],
                "supplier_phone_number": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "supplier_website": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "taxes": [],
                "total_amount": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "total_net": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                },
                "total_tax": {
                    "confidence": 0,
                    "polygon": [],
                    "value": null
                }
            }
        }
    ],
    "prediction": {
        "billing_address": {
            "address_complement": null,
            "city": null,
            "confidence": 0,
            "country": null,
            "page_id": null,
            "po_box": null,
            "polygon": [],
            "postal_code": null,
            "state": null,
            "street_name": null,
            "street_number": null,
            "value": null
        },
        "category": {
            "confidence": 0.9,
            "value": "accommodation"
        },
        "customer_address": {
            "address_complement": null,
            "city": "SAINT-AMANT-TALLENDE",
            "confidence": 1,
            "country": null,
            "page_id": 0,
            "po_box": null,
            "polygon": [
                [0.645, 0.099],
                [0.86, 0.099],
                [0.86, 0.121],
                [0.645, 0.121]
            ],
            "postal_code": "63450",
            "state": null,
            "street_name": "RUE DE PAGNAT",
            "street_number": "21",
            "value": "21 RUE DE PAGNAT 63450 SAINT-AMANT-TALLENDE"
        },
        "customer_company_registrations": [],
        "customer_id": {
            "confidence": 1,
            "page_id": 0,
            "polygon": [
                [0.738, 0.159],
                [0.802, 0.159],
                [0.802, 0.166],
                [0.738, 0.166]
            ],
            "value": "1643193B"
        },
        "customer_name": {
            "confidence": 0.99,
            "page_id": 0,
            "polygon": [
                [0.644, 0.085],
                [0.76, 0.085],
                [0.76, 0.09],
                [0.644, 0.09]
            ],
            "raw_value": "MME GUTH ELISE",
            "value": "MME GUTH ELISE"
        },
        "date": {
            "confidence": 0.99,
            "page_id": 0,
            "polygon": [
                [0.671, 0.052],
                [0.751, 0.052],
                [0.751, 0.062],
                [0.671, 0.062]
            ],
            "value": "2025-03-31"
        },
        "document_type": {
            "value": "INVOICE"
        },
        "document_type_extended": {
            "confidence": 0.98,
            "value": "INVOICE"
        },
        "due_date": {
            "confidence": 0.99,
            "is_computed": false,
            "page_id": 0,
            "polygon": [
                [0.553, 0.354],
                [0.635, 0.354],
                [0.635, 0.365],
                [0.553, 0.365]
            ],
            "value": "2027-03-31"
        },
        "invoice_number": {
            "confidence": 1,
            "page_id": 0,
            "polygon": [
                [0.477, 0.054],
                [0.634, 0.054],
                [0.634, 0.06],
                [0.477, 0.06]
            ],
            "value": "F265 2479102-25/002"
        },
        "line_items": [
            {
                "confidence": 0.98,
                "description": "Smartphone XIAOMI Redmi Note 14 Noir",
                "page_id": 0,
                "polygon": [
                    [0.311, 0.296],
                    [0.977, 0.296],
                    [0.977, 0.301],
                    [0.311, 0.301]
                ],
                "product_code": "0001216819",
                "quantity": 1,
                "tax_amount": null,
                "tax_rate": 20,
                "total_amount": 268.92,
                "unit_measure": null,
                "unit_price": 268.92
            },
            {
                "confidence": 0.96,
                "description": "ECO-PART DEEE",
                "page_id": 0,
                "polygon": [
                    [0.31, 0.309],
                    [0.977, 0.309],
                    [0.977, 0.319],
                    [0.31, 0.319]
                ],
                "product_code": null,
                "quantity": null,
                "tax_amount": null,
                "tax_rate": null,
                "total_amount": 3.07,
                "unit_measure": null,
                "unit_price": null
            }
        ],
        "locale": {
            "confidence": 0.94,
            "country": "FR",
            "currency": "EUR",
            "language": "fr",
            "value": "fr-FR"
        },
        "payment_date": {
            "confidence": 0.99,
            "page_id": 0,
            "polygon": [
                [0.553, 0.354],
                [0.635, 0.354],
                [0.635, 0.365],
                [0.553, 0.365]
            ],
            "value": "2027-03-31"
        },
        "po_number": {
            "confidence": 0,
            "page_id": null,
            "polygon": [],
            "value": null
        },
        "reference_numbers": [
            {
                "confidence": 1,
                "page_id": 0,
                "polygon": [
                    [0.138, 0.964],
                    [0.138, 0.969],
                    [0.165, 0.969],
                    [0.165, 0.964]
                ],
                "value": "L.223-1"
            }
        ],
        "shipping_address": {
            "address_complement": null,
            "city": null,
            "confidence": 0,
            "country": null,
            "page_id": null,
            "po_box": null,
            "polygon": [],
            "postal_code": null,
            "state": null,
            "street_name": null,
            "street_number": null,
            "value": null
        },
        "subcategory": {
            "confidence": 0.9,
            "value": null
        },
        "supplier_address": {
            "address_complement": null,
            "city": "Lesquin",
            "confidence": 1,
            "country": null,
            "page_id": 0,
            "po_box": null,
            "polygon": [
                [0.309, 0.703],
                [0.459, 0.703],
                [0.459, 0.724],
                [0.309, 0.724]
            ],
            "postal_code": "59810",
            "state": null,
            "street_name": "Avenue de la Motte",
            "street_number": null,
            "value": "Avenue de la Motte 59810 Lesquin"
        },
        "supplier_company_registrations": [
            {
                "confidence": 1,
                "page_id": 0,
                "polygon": [
                    [0.72, 0.718],
                    [0.86, 0.718],
                    [0.86, 0.724],
                    [0.72, 0.724]
                ],
                "type": "VAT NUMBER",
                "value": "FR78347384570"
            },
            {
                "confidence": 1,
                "page_id": 0,
                "polygon": [
                    [0.361, 0.129],
                    [0.475, 0.129],
                    [0.475, 0.136],
                    [0.361, 0.136]
                ],
                "type": "SIRET",
                "value": "34738457001613"
            },
            {
                "confidence": 1,
                "page_id": 0,
                "polygon": [
                    [0.746, 0.703],
                    [0.835, 0.703],
                    [0.835, 0.709],
                    [0.746, 0.709]
                ],
                "type": "SIREN",
                "value": "347384570"
            }
        ],
        "supplier_email": {
            "confidence": 0,
            "page_id": null,
            "polygon": [],
            "value": null
        },
        "supplier_name": {
            "confidence": 0.99,
            "page_id": 0,
            "polygon": [
                [0.021, 0.109],
                [0.292, 0.109],
                [0.292, 0.145],
                [0.021, 0.145]
            ],
            "raw_value": "boulanger",
            "value": "BOULANGER"
        },
        "supplier_payment_details": [],
        "supplier_phone_number": {
            "confidence": 0,
            "page_id": null,
            "polygon": [],
            "value": null
        },
        "supplier_website": {
            "confidence": 1,
            "page_id": 0,
            "polygon": [
                [0.324, 0.964],
                [0.421, 0.964],
                [0.421, 0.98],
                [0.324, 0.98]
            ],
            "value": "www.bloctel.gouv.fr"
        },
        "taxes": [
            {
                "base": 226.66,
                "confidence": 1,
                "page_id": 0,
                "polygon": [
                    [0.638, 0.446],
                    [0.638, 0.453],
                    [0.977, 0.453],
                    [0.977, 0.446]
                ],
                "rate": 20,
                "value": 45.33
            }
        ],
        "total_amount": {
            "confidence": 1,
            "page_id": 0,
            "polygon": [
                [0.93, 0.491],
                [0.977, 0.491],
                [0.977, 0.498],
                [0.93, 0.498]
            ],
            "value": 271.99
        },
        "total_net": {
            "confidence": 1,
            "page_id": 0,
            "polygon": [
                [0.93, 0.416],
                [0.977, 0.416],
                [0.977, 0.422],
                [0.93, 0.422]
            ],
            "value": 226.66
        },
        "total_tax": {
            "confidence": 1,
            "page_id": 0,
            "polygon": [
                [0.938, 0.446],
                [0.977, 0.446],
                [0.977, 0.452],
                [0.938, 0.452]
            ],
            "value": 45.33
        }
    },
    "processing_time": 2.654,
    "product": {
        "features": [
            "locale",
            "invoice_number",
            "po_number",
            "reference_numbers",
            "date",
            "due_date",
            "payment_date",
            "total_net",
            "total_amount",
            "total_tax",
            "taxes",
            "supplier_payment_details",
            "supplier_name",
            "supplier_company_registrations",
            "supplier_address",
            "supplier_phone_number",
            "supplier_website",
            "supplier_email",
            "customer_name",
            "customer_company_registrations",
            "customer_address",
            "customer_id",
            "shipping_address",
            "billing_address",
            "document_type",
            "document_type_extended",
            "subcategory",
            "category",
            "orientation",
            "line_items"
        ],
        "name": "mindee/invoices",
        "type": "standard",
        "version": "4.11"
    },
    "started_at": "2025-06-25T08:08:39.070055"
}', TRUE);

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Token '.\Setting::get('company\mindeeApiKey'),
				'Content-Type: multipart/form-data',
				CURLOPT_RETURNTRANSFER => true,
			],
		];


		$params = [
			'document' => new \CURLFile($filepath, 'pdf', 'invoice.pdf')
		];
		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(self::API_URL, $params, 'POST', $options), TRUE);

		if($data['api_request']['error']) {
			throw new \NotExpectedAction('Unable to read invoice : '.json_encode($data['api_request']['error']));
		}
		$document = $data['document'];
		dd($data);

	}

}
