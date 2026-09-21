<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

return [
    'any' => '^.*$',
    'digit' => '^\d{1,}$',
    'word' => '^\w{1,}$',
    'alphabet' => '^[a-zA-Z]{1,}$',
    'hiraganaorkatakana' => '^[ぁ-んァ-ン]{1,}$',
    'french' => '^[a-zàâçéèêëîïôûùüÿñæœ .-]*$',
    'hiragana' => '^[ぁ-ん]{1,}$',
    'katakana' => '^([ァ-ヶー]+)$',
    'korean' => '^[\u3131-\uD79D]{1,}$',
    'url' => '^(http\:\/\/)*[.a-zA-Z0-9-]+\.[a-zA-Z]+$',
    'email' => '^[^"\'@]+@[._a-zA-Z0-9-]+\.[a-zA-Z]+$',
    'query_parameter' => '([^=&?]+)=([^&#]*)',
    'whitespace' => '[\\x20\\t\\r\\n\\f]'
];
