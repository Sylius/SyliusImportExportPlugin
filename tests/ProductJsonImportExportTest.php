<?php

declare(strict_types=1);

namespace Tests\FriendsOfSylius\SyliusImportExportPlugin;

final class ProductJsonImportExportTest extends AbstractJsonImportExportTest
{
    protected function provideName(): string
    {
        return 'product';
    }

    protected function provideJsonData(): string
    {
        return <<<LOL
[
    {
        "Code": "aafdf5ca-653e-3165-8aeb-c1440739fa9b",
        "MainTaxon": "womens_t_shirts",
        "Enabled": true,
        "VariantSelectionMethod": "match",
        "AverageRating": 0,
        "Translations": {
            "en_US": {
                "Name": "T-Shirt \"dicta\"",
                "Slug": "t-shirt-dicta",
                "Description": "Nihil in ullam itaque rerum doloremque qui quia.",
                "ShortDescription": "Qui dolorum sint ut minima reprehenderit tenetur.",
                "MetaKeywords": null,
                "MetaDescription": null
            }
        },
        "Taxons": [
            {
                "Taxon": "t_shirts",
                "Position": 7
            },
            {
                "Taxon": "womens_t_shirts",
                "Position": 2
            }
        ],
        "Channels": [
            "US_WEB"
        ],
        "Images": [
            {
                "Type": "main",
                "Path": "0d\/e5\/72ef9f324b7862835324643a0161.jpeg"
            },
            {
                "Type": "thumbnail",
                "Path": "49\/d8\/d9d0e42d5d7dfefebed7348983dc.jpeg"
            }
        ],
        "Associations": [
            {
                "Type": "similar_products",
                "Products": [
                    "a77722c5-8482-3995-b279-9da18a2416d4"
                ]
            }
        ],
        "Attributes": [
            {
                "Attribute": "t_shirt_brand",
                "Locale": "en_US",
                "Value": "JKM-476 Streetwear"
            },
            {
                "Attribute": "t_shirt_collection",
                "Locale": "en_US",
                "Value": "Sylius Autumn 2011"
            }
        ],
        "Options": [
            "t_shirt_color",
            "t_shirt_size"
        ],
        "Variants": [
            {
                "Code": "aafdf5ca-653e-3165-8aeb-c1440739fa9b-variant-0",
                "Position": 0,
                "Version": 1,
                "OnHold": 0,
                "OnHand": 4,
                "Tracked": false,
                "Width": null,
                "Height": null,
                "Depth": null,
                "Weight": null,
                "ShippingRequired": true,
                "TaxCategory": null,
                "ShippingCategory": "HEAVY",
                "Images": [],
                "Translations": {
                    "en_US": {
                        "Name": "distinctio"
                    }
                },
                "ChannelPricings": {
                    "US_WEB": {
                        "Price": 675,
                        "OriginalPrice": null
                    }
                },
                "Options": [
                    "t_shirt_color_red",
                    "t_shirt_size_s"
                ]
            },
            {
                "Code": "aafdf5ca-653e-3165-8aeb-c1440739fa9b-variant-1",
                "Position": 1,
                "Version": 1,
                "OnHold": 0,
                "OnHand": 1,
                "Tracked": false,
                "Width": null,
                "Height": null,
                "Depth": null,
                "Weight": null,
                "ShippingRequired": false,
                "TaxCategory": "clothing",
                "ShippingCategory": "LIGHT",
                "Images": [],
                "Translations": {
                    "en_US": {
                        "Name": "dolor"
                    }
                },
                "ChannelPricings": {
                    "US_WEB": {
                        "Price": 675,
                        "OriginalPrice": null
                    }
                },
                "Options": [
                    "t_shirt_color_red",
                    "t_shirt_size_m"
                ]
            }
        ]
    },
    {
        "Code": "a77722c5-8482-3995-b279-9da18a2416d4",
        "MainTaxon": "books",
        "Enabled": false,
        "VariantSelectionMethod": "match",
        "AverageRating": 3.42,
        "Translations": {
            "en_US": {
                "Name": "Book \"aliquam\" by Carolina Christiansen III",
                "Slug": "book-aliquam-by-carolina-christiansen-iii",
                "Description": "Repellat beatae vitae et totam.",
                "ShortDescription": "Et odio voluptatem error maiores.",
                "MetaKeywords": "book,aliquam",
                "MetaDescription": "Book aliquam."
            }
        },
        "Taxons": [
            {
                "Taxon": "books",
                "Position": 0
            }
        ],
        "Channels": [
            "US_WEB"
        ],
        "Images": [
            {
                "Type": "main",
                "Path": "18\/c3\/3b7da99aec67b2db21f0ee154940.jpeg"
            },
            {
                "Type": "thumbnail",
                "Path": "03\/a0\/d7e004ec1efaaf7e3b8f37a520f0.jpeg"
            }
        ],
        "Associations": [
            {
                "Type": "similar_products",
                "Products": [
                    "aafdf5ca-653e-3165-8aeb-c1440739fa9b"
                ]
            }
        ],
        "Attributes": [
            {
                "Attribute": "book_author",
                "Locale": "en_US",
                "Value": "Carolina Christiansen III"
            }
        ],
        "Options": [],
        "Variants": [
            {
                "Code": "a77722c5-8482-3995-b279-9da18a2416d4-variant-0",
                "Position": 0,
                "Version": 1,
                "OnHold": 0,
                "OnHand": 8,
                "Tracked": true,
                "Width": 10,
                "Height": 20,
                "Depth": 5,
                "Weight": 3.2,
                "ShippingRequired": true,
                "TaxCategory": null,
                "ShippingCategory": null,
                "Images": [],
                "Translations": {
                    "en_US": {
                        "Name": "totam"
                    }
                },
                "ChannelPricings": {
                    "US_WEB": {
                        "Price": 675,
                        "OriginalPrice": null
                    }
                },
                "Options": []
            }
        ]
    }
]
LOL;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->loadJsonFixtures('taxon', <<<LOL
[
    {
        "Code": "t_shirts",
        "Parent": "",
        "Translations": {
            "en_US": {
                "Name": "T-Shirts",
                "Slug": "tshirts",
                "Description": "T-Shirts"
            }
        },
        "Position": 0
    },
    {
        "Code": "books",
        "Parent": "",
        "Translations": {
            "en_US": {
                "Name": "Books",
                "Slug": "books",
                "Description": "Books"
            }
        },
        "Position": 1
    },
    {
        "Code": "womens_t_shirts",
        "Parent": "t_shirts",
        "Translations": {
            "en_US": {
                "Name": "Women T-Shirts",
                "Slug": "women-tshirts",
                "Description": "Women T-Shirts"
            }
        },
        "Position": 0
    }
]
LOL
        );

        $this->loadJsonFixtures('currency', <<<LOL
[
    {
        "Code": "USD"
    },
    {
        "Code": "PLN"
    }
]
LOL
        );
        $this->loadJsonFixtures('locale', <<<LOL
[
    {
        "Code": "en_US"
    },
    {
        "Code": "pl_PL"
    }
]
LOL
        );
        $this->loadJsonFixtures('zone', <<<LOL
[
    {
        "Code": "NA",
        "Name": "North America",
        "Type": "country",
        "Scope": "all",
        "Members": ["US", "CA"]
    }
]
LOL
        );

        $this->loadJsonFixtures('channel', <<<LOL
[
    {
        "Code": "US_WEB",
        "Name": "US Web Store",
        "Description": "sample_description",
        "Hostname": "localhost",
        "Color": "GreenYellow",
        "Enabled": true,
        "Currencies": ["PLN"],
        "Locales": ["pl_PL"],
        "BaseCurrency": "PLN",
        "DefaultLocale": "pl_PL",
        "DefaultTaxZone": "NA",
        "TaxCalculationStrategy": "order_items_based",
        "ThemeName": "default",
        "ContactEmail": "sylius@example.com",
        "SkippingShippingStepAllowed": false,
        "SkippingPaymentStepAllowed": false,
        "AccountVerificationRequired": true,
        "ShopBillingData" : {
            "City": "sample_city",
            "Street": "sample_street",
            "Country": "sample_country",
            "TaxId": "sample_tax_id",
            "Company": "sample_company",
            "Postcode": "11-111"
        }
    }
]
LOL
        );

        $this->loadJsonFixtures('product_association_type', <<<LOL
[
    {
        "Code": "similar_products",
        "Translations": {
            "en_US": {
                "Name": "Similar products"
            }
        }
    }
]
LOL
        );

        $this->loadJsonFixtures('product_attribute', <<<LOL
[
    {
        "Code": "t_shirt_brand",
        "Type": "text",
        "StorageType": "text",
        "Configuration": [],
        "Position": 0,
        "Translations": {
            "en_US": {
                "Name": "T-Shirt Brand"
            }
        }
    },
    {
        "Code": "t_shirt_collection",
        "Type": "text",
        "StorageType": "text",
        "Configuration": [],
        "Position": 1,
        "Translations": {
            "en_US": {
                "Name": "T-Shirt Collection"
            }
        }
    },
    {
        "Code": "book_author",
        "Type": "text",
        "StorageType": "text",
        "Configuration": [],
        "Position": 2,
        "Translations": {
            "en_US": {
                "Name": "Book Author"
            }
        }
    }
]
LOL
        );

        $this->loadJsonFixtures('product_option', <<<LOL
[
    {
        "Code": "t_shirt_color",
        "Position": 0,
        "Translations": {
            "en_US": {
                "Name": "T-Shirt Color"
            }
        },
        "Values": [
            {
                "Code": "t_shirt_color_red",
                "Translations": {
                    "en_US": {
                        "Value": "Red"
                    }
                }
            }
        ]
    },
    {
        "Code": "t_shirt_size",
        "Position": 0,
        "Translations": {
            "en_US": {
                "Name": "T-Shirt Size"
            }
        },
        "Values": [
            {
                "Code": "t_shirt_size_s",
                "Translations": {
                    "en_US": {
                        "Value": "S"
                    }
                }
            },
            {
                "Code": "t_shirt_size_m",
                "Translations": {
                    "en_US": {
                        "Value": "M"
                    }
                }
            }
        ]
    }
]
LOL
        );

        $this->loadJsonFixtures('tax_category', <<<LOL
[
    {
        "Code": "clothing",
        "Name": "Clothing",
        "Description": "Corrupti dolorem ut qui et voluptatem. Repellendus sint omnis exercitationem ut. Quas soluta omnis quae tenetur consequatur voluptate."
    }
]
LOL
        );

        $this->loadJsonFixtures('shipping_category', <<<LOL
[
    {
        "Code": "LIGHT",
        "Name": "Light",
        "Description": "Light products."
    },
    {
        "Code": "HEAVY",
        "Name": "Heavy",
        "Description": "Heavy products."
    }
]
LOL
        );
    }
}
