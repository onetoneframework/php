<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\URLObject;
use function is_array;
use function implode;
use function http_build_query;
use function rtrim;

/**
 * USDA FoodData Central food search over HTTPS — API key required.
 *
 * Use {@see self::fetchFromUrl()} with any full URL, or convenience helpers that
 * default to `https://api.nal.usda.gov` (documented at
 * https://app.swaggerhub.com/apis/fdcnal/food-data_central_api/1.0.1 ).
 *
 * @package Clover\Plugin
 * @link https://fdc.nal.usda.gov/
 */
final class FoodDataCentral
{
    public const DEFAULT_BASE_URL = 'https://api.nal.usda.gov';

    private const FOOD_SEARCH_PATH = '/fdc/v1/foods/search';
    private const FOOD_DETAIL_PATH = '/food-details/';
    private const FOOD_DETAIL_NUTRIENTS_PATH = '/nutrients';
    public const WEBSITE_URL = 'https://fdc.nal.usda.gov/fdc-app.html#';

    private const PAGE_SIZE = '20';
    private const DATA_TYPE_FOUNDATION = 'Foundation';
    private const DATA_TYPE_SR_LEGACY = 'SR Legacy';
    private const SORT_ORDER_ASCENDING = 'asc';

    private const DATA_TYPE_PARAMS = [
        self::DATA_TYPE_FOUNDATION,
        self::DATA_TYPE_SR_LEGACY,
    ];

    private const QUERY_SEARCH = 'query';
    private const QUERY_PAGE_SIZE = 'pageSize';
    private const QUERY_DATA_TYPE = 'dataType';
    private const QUERY_SORT_ORDER = 'sortOrder';
    private const QUERY_API_KEY = 'api_key';

    public const DEFAULT_UNIT = 'g/ml';

    // Energy
    public const NUTRIMENT_TOTAL_KILOCALORIES_ID = 1008;
    public const NUTRIMENT_KILOCALORIES_ATWATER_GENERAL_ID = 957;
    public const NUTRIMENT_KILOCALORIES_ATWATER_SPECIFIC_ID = 958;

    // Macronutrients
    public const NUTRIMENT_TOTAL_CARBOHYDRATES_ID = 1005;
    public const NUTRIMENT_TOTAL_FAT_ID = 1004;
    public const NUTRIMENT_TOTAL_PROTEINS_ID = 1003;
    public const NUTRIMENT_TOTAL_SUGAR_ID = 1063;
    public const NUTRIMENT_TOTAL_DIETARY_FIBER_ID = 1079;

    // Lipid profile
    public const NUTRIMENT_TOTAL_SATURATED_FAT_ID = 1258;
    public const NUTRIMENT_MONOUNSATURATED_FAT_ID = 645;
    public const NUTRIMENT_POLYUNSATURATED_FAT_ID = 646;
    public const NUTRIMENT_TRANS_FAT_ID = 605;
    public const NUTRIMENT_CHOLESTEROL_ID = 601;

    // Minerals
    public const NUTRIMENT_SODIUM_ID = 307;
    public const NUTRIMENT_POTASSIUM_ID = 306;
    public const NUTRIMENT_MAGNESIUM_ID = 304;
    public const NUTRIMENT_CALCIUM_ID = 301;
    public const NUTRIMENT_IRON_ID = 303;
    public const NUTRIMENT_ZINC_ID = 309;
    public const NUTRIMENT_PHOSPHORUS_ID = 305;

    // Vitamins
    public const NUTRIMENT_VITAMIN_A_ID = 318;  // µg RAE
    public const NUTRIMENT_VITAMIN_C_ID = 401;  // mg
    public const NUTRIMENT_VITAMIN_D_ID = 328;  // µg
    public const NUTRIMENT_VITAMIN_B6_ID = 415;  // mg
    public const NUTRIMENT_VITAMIN_B12_ID = 418;  // µg
    public const NUTRIMENT_NIACIN_ID = 406;  // mg (B3)

    // Portion descriptor IDs
    public const PORTION_SERVING_ID = 1049;
    public const PORTION_UNKNOWN_ID = 9999;

    /** @var array<int, string> Measure unit map  (FDC portion-descriptor ID => human-readable label)*/
    public const MEASURE_UNITS = [
        1000 => 'cup',
        1001 => 'tablespoon',
        1002 => 'teaspoon',
        1003 => 'liter',
        1004 => 'milliliter',
        1005 => 'cubic inch',
        1006 => 'cubic centimeter',
        1007 => 'gallon',
        1008 => 'pint',
        1009 => 'fluid ounce',
        1010 => 'paired cooked weight',
        1011 => 'paired raw weight',
        1012 => 'dripping weight',
        1013 => 'bar',
        1014 => 'bird',
        1015 => 'biscuit',
        1016 => 'bottle',
        1017 => 'box',
        1018 => 'breast',
        1019 => 'can',
        1020 => 'chicken',
        1021 => 'chop',
        1022 => 'cookie',
        1023 => 'container',
        1024 => 'cracker',
        1025 => 'drink',
        1026 => 'drumstick',
        1027 => 'fillet',
        1028 => 'fruit',
        1029 => 'large',
        1030 => 'pound',
        1031 => 'leaf',
        1032 => 'leg',
        1033 => 'link',
        1034 => 'links',
        1035 => 'loaf',
        1036 => 'medium',
        1037 => 'muffin',
        1038 => 'ounce',
        1039 => 'package',
        1040 => 'packet',
        1041 => 'patty',
        1042 => 'patties',
        1043 => 'piece',
        1044 => 'pieces',
        1045 => 'quart',
        1046 => 'roast',
        1047 => 'sausage',
        1048 => 'scoop',
        1049 => 'serving',
        1050 => 'slice',
        1051 => 'slices',
        1052 => 'small',
        1053 => 'stalk',
        1054 => 'steak',
        1055 => 'stick',
        1056 => 'strip',
        1057 => 'tablet',
        1058 => 'thigh',
        1059 => 'unit',
        1060 => 'wedge',
        1061 => 'original cooked grams',
        1062 => 'original raw grams',
        1063 => 'medallion',
        1064 => 'pie',
        1065 => 'wing',
        1066 => 'back',
        1067 => 'olive',
        1068 => 'pocket',
        1069 => 'order',
        1070 => 'shrimp',
        1071 => 'each',
        1072 => 'filet',
        1073 => 'plantain',
        1074 => 'nugget',
        1075 => 'pretzel',
        1076 => 'corndog',
        1077 => 'spear',
        1078 => 'sandwich',
        1079 => 'tortilla',
        1080 => 'burrito',
        1081 => 'taco',
        1082 => 'tomatoes',
        1083 => 'chips',
        1084 => 'shell',
        1085 => 'bun',
        1086 => 'crust',
        1087 => 'sheet',
        1088 => 'bag',
        1089 => 'bagel',
        1090 => 'bowl',
        1091 => 'breadstick',
        1092 => 'bulb',
        1093 => 'cake',
        1094 => 'carton',
        1095 => 'chunk',
        1096 => 'contents',
        1097 => 'cutlet',
        1098 => 'doughnut',
        1099 => 'egg',
        1100 => 'fish',
        1101 => 'foreshank',
        1102 => 'frankfurter',
        1103 => 'fries',
        1104 => 'head',
        1105 => 'jar',
        1106 => 'loin',
        1107 => 'pancake',
        1108 => 'pizza',
        1109 => 'rack',
        1110 => 'ribs',
        1111 => 'roll',
        1112 => 'shank',
        1113 => 'shoulder',
        1114 => 'skin',
        1115 => 'wafers',
        1116 => 'wrap',
        1117 => 'bunch',
        1118 => 'tablespoons',
        1119 => 'banana',
        1120 => 'onion',
        9999 => 'portion', // undetermined
    ];

    public function __construct(
        private string $apiKey,
        private string $apiBaseUrl = self::DEFAULT_BASE_URL,
    ) {
    }

    /**
     * GET a FoodData Central-compatible JSON document and map `foods`
     *
     * Expected JSON keys: `totalHits`, `foods` (array of food objects).
     * 
     * @param string $url Full URL to fetch, including query parameters.
     */
    public function fetchFromUrl(string $url)
    {
        $requestUrl = new URLObject($url);
        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod()
            ->setFollowRedirects(true);

        $decoded = $cURL->executeWithDecode();
        $httpStatus = $cURL->getLastHttpCode();
        $payload = $this->payloadToArray($decoded);

        $rawFoods = $payload['foods'] ?? [];
        if (!is_array($rawFoods)) {
            $rawFoods = [];
        }

        $stack = [];
        foreach ($rawFoods as $food) {
            $foodId = (string) ($food['fdcId'] ?? '');
            if ($foodId === '') {
                continue;
            }
            $stack[$foodId] = $food;
        }

        $totalHits = isset($payload['totalHits']) ? (int) $payload['totalHits'] : count($stack);

        return [$stack, $totalHits, $httpStatus];
    }

    /**
     * Search foods by keyword.
     *
     * Calls `GET {base}/fdc/v1/foods/search?query=…&dataType=Foundation,SR+Legacy&…`
     *
     * @param string $searchString Free-text search query.
     */
    public function searchByWord(string $searchString)
    {
        $query = [
            self::QUERY_SEARCH => $searchString,
            self::QUERY_PAGE_SIZE => self::PAGE_SIZE,
            self::QUERY_DATA_TYPE => implode(',', self::DATA_TYPE_PARAMS),
            self::QUERY_SORT_ORDER => self::SORT_ORDER_ASCENDING,
            self::QUERY_API_KEY => $this->apiKey,
        ];

        $url = rtrim($this->apiBaseUrl, '/') . self::FOOD_SEARCH_PATH . '?' . http_build_query($query);

        return $this->fetchFromUrl($url);
    }

    /**
     * Return the human-readable website URL for a food detail page.
     *
     * @param string|null $foodId FDC food identifier; pass null to fall back to the plain base URL.
     */
    public static function getFoodDetailUrl(?string $foodId): string
    {
        if ($foodId === null) {
            return self::DEFAULT_BASE_URL;
        }

        return self::WEBSITE_URL . self::FOOD_DETAIL_PATH . $foodId . self::FOOD_DETAIL_NUTRIENTS_PATH;
    }

    /**
     * Resolve a FDC measure-unit ID to its human-readable label.
     *
     * @param int $unitId FDC portion-descriptor ID.
     * @return string Label, or `'portion'` when unknown.
     */
    public static function resolveMeasureUnit(int $unitId): string
    {
        return self::MEASURE_UNITS[$unitId] ?? self::MEASURE_UNITS[self::PORTION_UNKNOWN_ID];
    }

    /**
     * Convert the decoded cURL response into an array of foods.
     *
     * @param mixed $decoded Result of {@see ClientURL::executeWithDecode()}
     * @return array<string, mixed>
     */
    private function payloadToArray(mixed $decoded): array
    {
        if ($decoded instanceof ArrayObject) {
            $raw = $decoded->toPHPObject();

            return is_array($raw) ? $raw : [];
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }
}
