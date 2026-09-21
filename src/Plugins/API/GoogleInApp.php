<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Google\Service\AndroidPublisher\InAppProduct;

class GoogleInApp
{

    public function validatePurchase($productId, $purchaseToken, $packageName, $authConfigJson)
    {
        $googleInApp = new GoogleInApp();
        $googleInApp->setAuthConfigJson($authConfigJson);
        $googleInApp->setPackageName($packageName);
        $inAppProducts = $googleInApp->getInAppProducts();

        $amount = -1;
        $purchased_price = -1;

        foreach ($inAppProducts as $key => $productItem) {
            $sku = $productItem['sku'];
            $defaultLanguage = $productItem['defaultLanguage'];
            $packageName = $productItem['packageName'];
            $purchaseType = $productItem['purchaseType'];
            $status = $productItem['status'];

            if ($sku == $productId && $status == "active" && $packageName == $packageName) {
                $productTitle = $productItem['listings']['ko-KR']['title'];
                $purchased_price = $productItem['defaultPrice']['priceMicros'];
                $amount = preg_replace("/[^0-9]*/s", "", $productTitle);
                break;
            }
        }

        if ($amount == -1 || $purchased_price == -1) {
            $data = array(
                'code' => '400',
                'message' => 'There is an error in the Google Developer Console. Please contact the administrator.',
            );
            $httpDesc = 'Bad Request';
            return $this->jsonResult($response, 400, $httpDesc, $data);
        }

        $purchased_price /= 1000000;
        $purchase = $googleInApp->getPurchasesProducts($productId, $purchaseToken);
        $purchase_state = $purchase->getPurchaseState();
        $purchase_type = $purchase->getPurchaseType();
        $order_id = $purchase->getOrderId();

        if ($purchase_state == '1') { 
            $data = array(
                'code' => '400',
                'message' => 'This is an order with a canceled payment.',
            );
            $httpDesc = 'Bad Request';
            return $this->jsonResult($response, 400, $httpDesc, $data);
        }

    }

    public function isValid($packageName, $productId, $purchaseToken)
    {
        $client = new Google\Client();
        $client->setAuthConfig('./pc-api-7290957689874184372-114-1c34e1d4b7ac.json');
        $client->addScope("https://www.googleapis.com/auth/androidpublisher");

        $AndroidPublisherService = new Google_Service_AndroidPublisher($client);

        $list = $AndroidPublisherService->inappproducts->listInappproducts($packageName);

        foreach ($list as $key => $value) {
            if ($list[$key]['sku'] == $productId) {
                $str = $list[$key]['listings']['ko-KR']['title'];
                $purchased_price = $list[$key]['defaultPrice']['priceMicros'];
                $amount = preg_replace("/[^0-9]*/s", "", $str);
                break;
            }
        }

        $purchased_price /= 1000000;
        $purchase = $AndroidPublisherService->purchases_products->get($packageName, $productId, $purchaseToken);

        $purchase->getAutoRenewing(); 
        $purchase->getCancelReason(); 
        $purchase->getExpiryTimeMillis(); 
        $purchase->getStartTimeMillis(); 

        $purchase_state = $purchase->getPurchaseState();
        $purchase_type = $purchase->getPurchaseType();
        $order_id = $purchase->getOrderId();

    }

    public function insertItem($packageName, $authConfig, $title, $desc, $set_price, $sku, $purchaseType = "managedUser")
    {
        $client = new Google\Client();
        $client->setAuthConfig($authConfig);
        $client->addScope("https://www.googleapis.com/auth/androidpublisher");

        $AndroidPublisherService = new Google_Service_AndroidPublisher($client);

        $listItem = new Google_Service_AndroidPublisher_InAppProductListing();
        $listItem->setTitle($title);
        $listItem->setDescription($desc);

        $price = new Google_Service_AndroidPublisher_Price();
        $price->setCurrency("KRW");
        $price->setPriceMicros($set_price * 1000000);

        $myhashmap = array();
        $myhashmap['ko-KR'] = $listItem;

        $krPrice = new Google_Service_AndroidPublisher_Price();
        $krPrice->setCurrency("KRW");
        $krPrice->setPriceMicros($set_price * 1000000);

        $prices = array();
        $prices['KR'] = $krPrice;

        $inAppItem = new InAppProduct();
        $inAppItem->setDefaultLanguage("ko-KR");
        $inAppItem->setSku($sku);
        $inAppItem->setPackageName($packageName);
        $inAppItem->setListings($myhashmap);
        $inAppItem->setStatus("active");
        $inAppItem->setDefaultPrice($price);
        $inAppItem->setPrices($prices);

        $inAppItem->setPurchaseType($purchaseType);

        $AndroidPublisherService->inappproducts->insert($packageName, $inAppItem);
    }

}