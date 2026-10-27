<?php

namespace App\Support;

/**
 * Bundled list of phone models sold in Japan and Indonesia (2019 to October 2026), used by
 * SyncPhoneModels (CUST-12). Customers can still type any model that is not listed.
 * Add new releases here; the next sync adds them without touching models edited by the admin.
 */
class PhoneCatalog
{
    /** @var array<string, list<string>> Brand name => model names, newest first. */
    public const MODELS = [
        'Apple' => [
            'iPhone Duo', 'iPhone 18 Pro Max', 'iPhone 18 Pro',
            'iPhone 17e', 'iPhone 17 Pro Max', 'iPhone 17 Pro', 'iPhone Air', 'iPhone 17',
            'iPhone 16e', 'iPhone 16 Pro Max', 'iPhone 16 Pro', 'iPhone 16 Plus', 'iPhone 16',
            'iPhone 15 Pro Max', 'iPhone 15 Pro', 'iPhone 15 Plus', 'iPhone 15',
            'iPhone 14 Pro Max', 'iPhone 14 Pro', 'iPhone 14 Plus', 'iPhone 14',
            'iPhone SE (3rd generation)', 'iPhone 13 Pro Max', 'iPhone 13 Pro', 'iPhone 13', 'iPhone 13 mini',
            'iPhone 12 Pro Max', 'iPhone 12 Pro', 'iPhone 12', 'iPhone 12 mini',
            'iPhone SE (2nd generation)', 'iPhone 11 Pro Max', 'iPhone 11 Pro', 'iPhone 11',
            'iPhone XS Max', 'iPhone XS', 'iPhone XR', 'iPhone X',
        ],
        'Samsung' => [
            'Samsung Galaxy Z Fold8 Ultra', 'Samsung Galaxy Z Fold8', 'Samsung Galaxy Z Flip8',
            'Samsung Galaxy S26 Ultra', 'Samsung Galaxy S26+', 'Samsung Galaxy S26',
            'Samsung Galaxy S25 FE', 'Samsung Galaxy Z Fold7', 'Samsung Galaxy Z Flip7', 'Samsung Galaxy Z Flip7 FE',
            'Samsung Galaxy S25 Edge', 'Samsung Galaxy S25 Ultra', 'Samsung Galaxy S25+', 'Samsung Galaxy S25',
            'Samsung Galaxy S24 FE', 'Samsung Galaxy Z Fold6', 'Samsung Galaxy Z Flip6',
            'Samsung Galaxy S24 Ultra', 'Samsung Galaxy S24+', 'Samsung Galaxy S24',
            'Samsung Galaxy S23 FE', 'Samsung Galaxy Z Fold5', 'Samsung Galaxy Z Flip5',
            'Samsung Galaxy S23 Ultra', 'Samsung Galaxy S23+', 'Samsung Galaxy S23',
            'Samsung Galaxy Z Fold4', 'Samsung Galaxy Z Flip4',
            'Samsung Galaxy S22 Ultra', 'Samsung Galaxy S22+', 'Samsung Galaxy S22',
            'Samsung Galaxy S21 FE', 'Samsung Galaxy Z Fold3', 'Samsung Galaxy Z Flip3',
            'Samsung Galaxy S21 Ultra', 'Samsung Galaxy S21+', 'Samsung Galaxy S21',
            'Samsung Galaxy S20 FE', 'Samsung Galaxy S20 Ultra', 'Samsung Galaxy S20+', 'Samsung Galaxy S20',
            'Samsung Galaxy A57', 'Samsung Galaxy A37', 'Samsung Galaxy A17', 'Samsung Galaxy A07',
            'Samsung Galaxy A56', 'Samsung Galaxy A36', 'Samsung Galaxy A26', 'Samsung Galaxy A16', 'Samsung Galaxy A06',
            'Samsung Galaxy A55', 'Samsung Galaxy A35', 'Samsung Galaxy A25', 'Samsung Galaxy A15', 'Samsung Galaxy A05s', 'Samsung Galaxy A05',
            'Samsung Galaxy A54', 'Samsung Galaxy A34', 'Samsung Galaxy A24', 'Samsung Galaxy A14', 'Samsung Galaxy A04',
            'Samsung Galaxy A53', 'Samsung Galaxy A33', 'Samsung Galaxy A23', 'Samsung Galaxy A13', 'Samsung Galaxy A03',
            'Samsung Galaxy A52s', 'Samsung Galaxy A52', 'Samsung Galaxy A32', 'Samsung Galaxy A22', 'Samsung Galaxy A12',
            'Samsung Galaxy A51', 'Samsung Galaxy A21', 'Samsung Galaxy A11',
            'Samsung Galaxy M55', 'Samsung Galaxy M35', 'Samsung Galaxy M15', 'Samsung Galaxy M14',
        ],
        'Google' => [
            'Google Pixel 11 Pro Fold', 'Google Pixel 11 Pro XL', 'Google Pixel 11 Pro', 'Google Pixel 11',
            'Google Pixel 10a', 'Google Pixel 10 Pro Fold', 'Google Pixel 10 Pro XL', 'Google Pixel 10 Pro', 'Google Pixel 10',
            'Google Pixel 9a', 'Google Pixel 9 Pro Fold', 'Google Pixel 9 Pro XL', 'Google Pixel 9 Pro', 'Google Pixel 9',
            'Google Pixel 8a', 'Google Pixel 8 Pro', 'Google Pixel 8',
            'Google Pixel Fold', 'Google Pixel 7a', 'Google Pixel 7 Pro', 'Google Pixel 7',
            'Google Pixel 6a', 'Google Pixel 6 Pro', 'Google Pixel 6',
            'Google Pixel 5a', 'Google Pixel 5', 'Google Pixel 4a (5G)', 'Google Pixel 4a',
        ],
        'Sony' => [
            'Sony Xperia 10 VII', 'Sony Xperia 1 VII',
            'Sony Xperia 10 VI', 'Sony Xperia 1 VI', 'Sony Xperia 5 V', 'Sony Xperia 10 V', 'Sony Xperia 1 V',
            'Sony Xperia 5 IV', 'Sony Xperia 10 IV', 'Sony Xperia 1 IV', 'Sony Xperia Ace III',
            'Sony Xperia 5 III', 'Sony Xperia 10 III', 'Sony Xperia 1 III', 'Sony Xperia Ace II',
            'Sony Xperia 5 II', 'Sony Xperia 10 II', 'Sony Xperia 1 II',
        ],
        'Sharp' => [
            'AQUOS sense10', 'AQUOS R10', 'AQUOS wish5',
            'AQUOS sense9', 'AQUOS R9 pro', 'AQUOS R9', 'AQUOS wish4',
            'AQUOS sense8', 'AQUOS R8 pro', 'AQUOS R8', 'AQUOS wish3',
            'AQUOS sense7 plus', 'AQUOS sense7', 'AQUOS R7', 'AQUOS wish2', 'AQUOS wish',
            'AQUOS sense6s', 'AQUOS sense6', 'AQUOS zero6', 'AQUOS R6',
            'AQUOS sense5G', 'AQUOS sense4 lite', 'AQUOS sense4', 'AQUOS R5G',
        ],
        'FCNT' => [
            'arrows Alpha', 'arrows We2 Plus', 'arrows We2', 'arrows N', 'arrows We', 'arrows 5G', 'arrows NX9', 'arrows Be4 Plus',
        ],
        'Kyocera' => [
            'Kyocera TORQUE G06', 'Kyocera TORQUE 5G', 'Kyocera BASIO active2', 'Kyocera BASIO active', 'Kyocera DIGNO SX3',
        ],
        'Xiaomi' => [
            'Xiaomi 17 Ultra', 'Xiaomi 17',
            'Xiaomi 15T Pro', 'Xiaomi 15T', 'Xiaomi 15 Ultra', 'Xiaomi 15',
            'Xiaomi 14T Pro', 'Xiaomi 14T', 'Xiaomi 14 Ultra', 'Xiaomi 14',
            'Xiaomi 13T Pro', 'Xiaomi 13T', 'Xiaomi 13 Ultra', 'Xiaomi 13',
            'Xiaomi 12T Pro', 'Xiaomi 12T', 'Xiaomi 12', 'Xiaomi 11T Pro', 'Xiaomi 11T', 'Xiaomi Mi 11 Lite 5G',
            'Redmi Note 15 Pro+', 'Redmi Note 15 Pro', 'Redmi Note 15',
            'Redmi Note 14 Pro+', 'Redmi Note 14 Pro', 'Redmi Note 14', 'Redmi Note 14 5G',
            'Redmi Note 13 Pro+', 'Redmi Note 13 Pro', 'Redmi Note 13 Pro 5G', 'Redmi Note 13', 'Redmi Note 13 5G',
            'Redmi Note 12 Pro+', 'Redmi Note 12 Pro', 'Redmi Note 12', 'Redmi Note 12 5G',
            'Redmi Note 11 Pro+ 5G', 'Redmi Note 11 Pro', 'Redmi Note 11S', 'Redmi Note 11',
            'Redmi Note 10 Pro', 'Redmi Note 10S', 'Redmi Note 10', 'Redmi Note 10 JE',
            'Redmi Note 9 Pro', 'Redmi Note 9S', 'Redmi Note 9T', 'Redmi Note 9',
            'Redmi 15C', 'Redmi 15', 'Redmi A5', 'Redmi 14C', 'Redmi 13', 'Redmi 13C', 'Redmi A3',
            'Redmi 12', 'Redmi 12 5G', 'Redmi 12C', 'Redmi A2', 'Redmi 10C', 'Redmi 10', 'Redmi 10A',
            'Redmi 9T', 'Redmi 9C', 'Redmi 9A', 'Redmi 9',
            'POCO F7 Ultra', 'POCO F7 Pro', 'POCO F7', 'POCO X7 Pro', 'POCO X7', 'POCO M7 Pro', 'POCO C75',
            'POCO F6 Pro', 'POCO F6', 'POCO X6 Pro', 'POCO X6', 'POCO M6 Pro', 'POCO M6', 'POCO C65',
            'POCO F5 Pro', 'POCO F5', 'POCO X5 Pro', 'POCO X5', 'POCO F4', 'POCO X4 Pro', 'POCO M4 Pro', 'POCO X3 Pro', 'POCO M3',
        ],
        'OPPO' => [
            'OPPO Find X9 Pro', 'OPPO Find X9', 'OPPO Reno14 Pro', 'OPPO Reno14', 'OPPO Reno14 F', 'OPPO Reno13 A',
            'OPPO Find N5', 'OPPO Find X8 Pro', 'OPPO Find X8', 'OPPO Reno13 Pro', 'OPPO Reno13', 'OPPO Reno13 F',
            'OPPO Reno12 Pro', 'OPPO Reno12', 'OPPO Reno12 F', 'OPPO Reno11 A', 'OPPO Reno11 Pro', 'OPPO Reno11', 'OPPO Reno11 F',
            'OPPO Reno10 Pro 5G', 'OPPO Reno10 Pro', 'OPPO Reno10', 'OPPO Reno9 A', 'OPPO Reno8 T', 'OPPO Reno8', 'OPPO Reno7 A',
            'OPPO Reno7', 'OPPO Reno6', 'OPPO Reno5 A', 'OPPO Reno5',
            'OPPO A6 Pro', 'OPPO A5 Pro', 'OPPO A5', 'OPPO A3 Pro', 'OPPO A3', 'OPPO A3x', 'OPPO A79', 'OPPO A78', 'OPPO A60',
            'OPPO A58', 'OPPO A38', 'OPPO A18', 'OPPO A98', 'OPPO A77', 'OPPO A57', 'OPPO A17', 'OPPO A96', 'OPPO A76',
            'OPPO A55', 'OPPO A54', 'OPPO A16', 'OPPO A74', 'OPPO A73 5G', 'OPPO A15',
        ],
        'vivo' => [
            'vivo X300 Pro', 'vivo X300', 'vivo V60', 'vivo X200 Pro', 'vivo X200', 'vivo V50', 'vivo V40', 'vivo V30', 'vivo V29', 'vivo V27',
            'vivo V25', 'vivo V23', 'vivo T4', 'vivo Y39', 'vivo Y29', 'vivo Y19s', 'vivo Y28', 'vivo Y18', 'vivo Y17s', 'vivo Y04',
            'vivo Y03', 'vivo Y36', 'vivo Y27', 'vivo Y22', 'vivo Y21', 'vivo Y16', 'vivo Y12s',
        ],
        'realme' => [
            'realme GT 7 Pro', 'realme GT 7', 'realme 15 Pro', 'realme 15', 'realme 14 Pro+', 'realme 14 Pro', 'realme 14',
            'realme 13 Pro+', 'realme 13 Pro', 'realme 13', 'realme 12 Pro+', 'realme 12 Pro', 'realme 12', 'realme 11 Pro+', 'realme 11 Pro', 'realme 11',
            'realme GT 6', 'realme Note 60', 'realme Note 50', 'realme C85', 'realme C75', 'realme C71', 'realme C67', 'realme C65',
            'realme C63', 'realme C61', 'realme C55', 'realme C53', 'realme C51', 'realme C35', 'realme C33', 'realme C31', 'realme 10', 'realme 9 Pro+', 'realme 9',
        ],
        'Motorola' => [
            'Motorola razr 60 ultra', 'Motorola razr 60', 'Motorola edge 60 pro', 'Motorola edge 60', 'Motorola moto g66j',
            'Motorola moto g86', 'Motorola moto g56', 'Motorola moto g05', 'Motorola moto g15',
            'Motorola razr 50 ultra', 'Motorola razr 50', 'Motorola edge 50 pro', 'Motorola edge 50 fusion', 'Motorola moto g85', 'Motorola moto g64',
            'Motorola moto g64y', 'Motorola moto g24', 'Motorola razr 40 ultra', 'Motorola razr 40', 'Motorola edge 40 neo', 'Motorola edge 40',
            'Motorola moto g84', 'Motorola moto g54', 'Motorola moto g53y', 'Motorola moto g53j', 'Motorola moto g13', 'Motorola moto g52j',
        ],
        'Nothing' => [
            'Nothing Phone (3)', 'Nothing Phone (3a) Pro', 'Nothing Phone (3a)', 'CMF Phone 2 Pro', 'Nothing Phone (2a) Plus',
            'Nothing Phone (2a)', 'CMF Phone 1', 'Nothing Phone (2)', 'Nothing Phone (1)',
        ],
        'ASUS' => [
            'ASUS Zenfone 12 Ultra', 'ASUS ROG Phone 9 Pro', 'ASUS ROG Phone 9', 'ASUS Zenfone 11 Ultra', 'ASUS ROG Phone 8 Pro',
            'ASUS ROG Phone 8', 'ASUS Zenfone 10', 'ASUS Zenfone 9', 'ASUS ROG Phone 7', 'ASUS Zenfone 8',
        ],
        'Infinix' => [
            'Infinix GT 30 Pro', 'Infinix Note 50 Pro', 'Infinix Note 50', 'Infinix Hot 60 Pro', 'Infinix Hot 60', 'Infinix Smart 10',
            'Infinix GT 20 Pro', 'Infinix Note 40 Pro', 'Infinix Note 40', 'Infinix Hot 50 Pro', 'Infinix Hot 50', 'Infinix Smart 9',
            'Infinix Hot 40 Pro', 'Infinix Hot 40', 'Infinix Smart 8', 'Infinix Note 30 Pro', 'Infinix Note 30', 'Infinix Hot 30',
        ],
        'Tecno' => [
            'Tecno Camon 40 Pro', 'Tecno Camon 40', 'Tecno Spark 40 Pro', 'Tecno Spark 40', 'Tecno Pova 7',
            'Tecno Camon 30', 'Tecno Spark 30 Pro', 'Tecno Spark 30', 'Tecno Pova 6 Pro', 'Tecno Spark 20 Pro', 'Tecno Spark 20',
        ],
        'Huawei' => [
            'Huawei nova 13', 'Huawei nova 12', 'Huawei nova 11', 'Huawei P30 lite', 'Huawei P30',
        ],
        'Honor' => [
            'Honor 400', 'Honor Magic7 Pro', 'Honor 200 Pro', 'Honor 200', 'Honor X9c', 'Honor X9b', 'Honor Magic6 Pro',
        ],
        'OnePlus' => [
            'OnePlus 15', 'OnePlus 13', 'OnePlus 13R', 'OnePlus Nord 5', 'OnePlus Nord CE5', 'OnePlus 12', 'OnePlus Nord 4', 'OnePlus 11',
        ],
        'Rakuten' => [
            'Rakuten Hand 5G', 'Rakuten Hand', 'Rakuten BIG s',
        ],
    ];
}
