<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\BaseClass;
use Clover\Classes\Data\{ArrayObject, JSONHandler};
use Clover\Classes\DependencyInjection\Container;
use Clover\Implement\ControllerInterface;

use function sprintf;

/**
 * Base Controller
 * 
 * The BaseController serves as a foundational class for all controllers in the application. It provides common functionalities such as generating different types of responses (HTML, JSON, XML, images), managing resources (like CSS and JavaScript files), and adding various meta tags to the response. By extending this BaseController, developers can easily create specific controllers for handling different routes and actions while leveraging the shared functionalities provided by the base class.
 */
class BaseController extends BaseClass implements ControllerInterface
{
    /**
     * Constructor
     *
     * @param Container|null $container
     */
    public function __construct(protected ?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Debug response
     *
     * @param string|null $body
     * @param array $resource
     *
     * @return Response
     */
    public function debug(?string $body = null, array $resource = []): Response
    {
        return new Response('<div style="white-space: break-spaces;font-size: 11px;">' . print_r($body, true) . '</div>', $resource);
    }

    /**
     * Response image
     *
     * @param string|null $path
     *
     * @return Response
     */
    public function responseImage(?string $path = null): Response
    {
        return new Response($path, [], 'image');
    }

    /**
     * Response XML
     *
     * @param array|null $array
     *
     * @return Response
     */
    public function responseXml(?array $array = null): Response
    {
        return new Response($array, [], 'xml');
    }

    /**
     * Response text
     *
     * @param mixed $body
     *
     * @return Response
     */
    public function responseText(mixed $body = null): Response
    {
        return new Response($body, [], 'text');
    }

    /**
     * Response html
     *
     * @param mixed $body HTML body content
     * @param array $resource HTML resource
     *
     * @return Response
     */
    public function response(mixed $body, array $resource = []): Response
    {
        return new Response($body, $resource, 'html');
    }

    /**
     * Add CSS file to head
     *
     * @param string|null $filename Public web path (e.g. /App/Frontend/dist/app.css) or {@see addCssFileToHeadFromBase}
     *
     * @return void
     */
    public function addCssFileToHead(?string $filename = null): void
    {
        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $resource->addGenericCssFile($filename);
    }

    /**
     * Add CSS file to head using a path relative to the app root (BASE_PATH), e.g. App/Frontend/Layout/MenuShadow/about.css
     *
     * @param string $relativeToBasePath Same segments as under {@code res/Platform/PHP/root/} for IDE path completion
     *
     * @return void
     */
    public function addCssFileToHeadFromBase(string $relativeToBasePath): void
    {
        $this->addCssFileToHead(PublicPath::fromBase($relativeToBasePath));
    }

    /**
     * Add CSS data to head
     *
     * @param array $map CSS file path and attributes
     *
     * @return void
     */
    public function addCssDataToHead(array $map = []): void
    {
        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $resource->addCssFile($map);
    }

    /**
     * Add webpack assets to head
     *
     * @param string|null $path
     *
     * @return void
     */
    public function addWebpackAssetsToHead(?string $path = null): void
    {
        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $assets = $this->getWebpackAssets(sprintf("%s%s", BASE_PATH, $path));

        /** @var string $asset */
        foreach ($assets as $asset) {
            $dotPosition = strrpos($asset, '.');

            if ($dotPosition === false) {
                continue;
            }

            $extension = substr($asset, $dotPosition + 1);
            $assetPath = sprintf("%s%s", dirname($path), $asset);

            if (array_search($extension, ['js'], true) !== false) {
                $resource->addGenericJavascriptFile($assetPath);
            } elseif (array_search($extension, ['css'], true) !== false) {
                $resource->addGenericCssFile($assetPath);
            }
        }
    }

    /**
     * Add various link meta tags
     *
     * @param string|null $href
     * @param string|null $title
     *
     * @return void
     */
    public function addSearchLinkMetaTag(?string $href = null, ?string $title = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'search',
            'type' => 'application/opensearchdescription+xml',
            'href' => $href,
            'title' => $title
        ]);
    }

    /**
     * Add manifest link meta tag
     *
     * @param string|null $href
     *
     * @return void
     */
    public function addManifestLinkMetaTag(?string $href = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'manifest',
            'href' => $href,
            'crossorigin' => 'use-credentials'
        ]);
    }

    /**
     * Add preload link meta tag
     *
     * @param string|null $url
     *
     * @return void
     */
    public function addPreloadLinkMetaTag(?string $url = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'preload',
            'href' => $url,
            'as' => 'fetch'
        ]);
    }

    /**
     * Add canonical link meta tag
     *
     * @param string|null $url
     *
     * @return void
     */
    public function addCanonicalLinkMetaTag(?string $url = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'canonical',
            'href' => $url
        ]);
    }

    /**
     * Add alternate link meta tag
     *
     * @param string|null $url HTML href attribute value
     * @param string|null $media HTML media attribute value
     *
     * @return void
     */
    public function addAlternateLinkMetaTag(?string $url = null, ?string $media = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'alternate',
            'media' => $media,
            'href' => $url
        ]);
    }

    /**
     * Add title link meta tag
     *
     * @param string|null $title HTML title tag content
     *
     * @return void
     */
    public function addTitleLinkMetaTag(?string $title = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'title',
            'content' => $title
        ]);
    }

    /**
     * Add shortlink link meta tag
     *
     * @param string|null $url
     *
     * @return void
     */
    public function addShortlinkLinkMetaTag(?string $url = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'shortlinkUrl',
            'href' => $url
        ]);
    }

    /**
     * Add app link meta tag
     *
     * @param string|null $url
     * @param string|null $platform
     *
     * @return void
     */
    public function addAppLinkMetaTag(?string $url = null, ?string $platform = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'alternate',
            'href' => $url,
            'platform' => $platform
        ]);
    }

    /**
     * Add oembed link meta tag
     *
     * @param string|null $url
     * @param string|null $format
     * @param string|null $title
     *
     * @return void
     */
    public function addOembedLinkMetaTag(?string $url = null, ?string $format = 'json', ?string $title = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'alternate',
            'type' => "application/{$format}+oembed",
            'href' => $url,
            'title' => $title
        ]);
    }

    /**
     * Add image src link meta tag
     *
     * @param string|null $imageUrl
     *
     * @return void
     */
    public function addImageSrcLinkMetaTag(?string $imageUrl = null): void
    {
        $this->addCssDataToHead([
            'rel' => 'image_src',
            'href' => $imageUrl
        ]);
    }

    /**
     * Add itemprop link meta tag
     *
     * @param string|null $url
     * @param string|null $name
     *
     * @return void
     */
    public function addItemPropLinkMetaTag(?string $url = null, ?string $name = null): void
    {
        $this->addCssDataToHead([
            'itemprop' => 'url',
            'href' => $url
        ]);
        $this->addCssDataToHead([
            'itemprop' => 'name',
            'content' => $name
        ]);
    }

    /**
     * Add thumbnailUrl link meta tag
     *
     * @param string|null $imageUrl
     *
     * @return void
     */
    public function addThumbnailUrlLinkMetaTag(?string $imageUrl = null): void
    {
        $this->addCssDataToHead([
            'itemprop' => 'thumbnailUrl',
            'href' => $imageUrl
        ]);
    }

    /**
     * Add json ld script link tag
     *
     * @param string|null $nonce
     * @param string|null $json
     *
     * @return void
     */
    public function addJsonLdScriptLinkTag(?string $nonce = null, ?string $json = null): void
    {
        $this->addCssDataToHead([
            'type' => 'application/ld+json',
            'nonce' => $nonce,
            'content' => $json
        ]);
    }

    /**
     * Get webpack assets from manifest file
     *
     * @param string|null $path
     *
     * @return ArrayObject|null
     */
    public function getWebpackAssets(?string $path = null): ArrayObject|null
    {
        $fileContents = file_get_contents($path);

        if ($fileContents === false) {
            return null;
        }

        $manifest = JSONHandler::decode($fileContents, true);

        return $manifest;
    }

    /**
     * Add viewport meta tag
     *
     * @param string $content
     *
     * @return void
     */
    public function addViewportMetaTag(string $content = "width=device-width, initial-scale=1, user-scalable=yes, minimum-scale=1"): void
    {
        $this->addMetaTag([
            'name' => 'viewport',
            'content' => $content
        ]);
    }

    /**
     * Add robots meta tag
     *
     * @param string|null $content
     *
     * @return void
     */
    public function addRobotsMetaTag(?string $content = "noarchive"): void
    {
        $this->addMetaTag([
            'rel' => 'robots',
            'href' => $content
        ]);
    }

    /**
     * Add chrome origin trial meta tag
     *
     * @param string|null $token
     *
     * @return void
     */
    public function addChromeOriginTag(?string $token = null): void
    {
        $this->addMetaTag([
            'http-equiv' => 'origin-trial',
            'content' => $token
        ]);
    }

    /**
     * Add charset meta tag
     *
     * @param string $charset
     *
     * @return void
     */
    public function addCharsetMetaTag(string $charset = 'UTF-8'): void
    {
        $this->addMetaTag([
            'charset' => $charset
        ]);
    }

    /**
     * Add description meta tag
     *
     * @param string|null $description
     *
     * @return void
     */
    public function addDescriptionMetaTag(?string $description = null): void
    {
        $this->addMetaTag([
            'name' => 'description',
            'content' => $description
        ]);
    }

    /**
     * Add theme color meta tag
     *
     * @param string|null $color
     *
     * @return void
     */
    public function addThemeColorMetaTag(?string $color = null): void
    {
        $this->addMetaTag([
            'name' => 'theme-color',
            'content' => $color
        ]);
    }

    /**
     * Add open graph meta tags
     *
     * @param string|null $siteName
     *
     * @return void
     */
    public function addOgSiteNameMetaTag(?string $siteName = null): void
    {
        $this->addMetaTag([
            'property' => 'og:site_name',
            'content' => $siteName
        ]);
    }

    /**
     * Add open graph url meta tag
     *
     * @param string|null $url
     *
     * @return void
     */
    public function addOgUrlMetaTag(?string $url = null): void
    {
        $this->addMetaTag([
            'property' => 'og:url',
            'content' => $url
        ]);
    }

    /**
     * Add open graph title meta tag
     *
     * @param string|null $title
     *
     * @return void
     */
    public function addOgTitleMetaTag(?string $title = null): void
    {
        $this->addMetaTag([
            'property' => 'og:title',
            'content' => $title
        ]);
    }

    /**
     * Add open graph image meta tag
     *
     * @param string|null $imageUrl
     *
     * @return void
     */
    public function addOgImageMetaTag(?string $imageUrl = null): void
    {
        $this->addMetaTag([
            'property' => 'og:image',
            'content' => $imageUrl
        ]);
    }

    /**
     * Add apple itunes app meta tag
     *
     * @param string|null $content
     *
     * @return void
     */
    public function addAppleItunesAppMetaTag(?string $content = null): void
    {
        $this->addMetaTag([
            'property' => 'apple-itunes-app',
            'content' => $content
        ]);
    }

    /**
     * Add og image width meta tag
     *
     * @param int|null $width
     *
     * @return void
     */
    public function addOgImageWidthMetaTag(?int $width = null): void
    {
        $this->addMetaTag([
            'property' => 'og:image:width',
            'content' => $width
        ]);
    }

    /**
     * Add og image height meta tag
     *
     * @param int|null $height
     *
     * @return void
     */
    public function addOgImageHeightMetaTag(?int $height = null): void
    {
        $this->addMetaTag([
            'property' => 'og:image:height',
            'content' => $height
        ]);
    }

    /**
     * Add generator meta tag
     *
     * @param string|null $title
     *
     * @return void
     */
    public function addGeneratorMetaTag(?string $title = null): void
    {
        $this->addMetaTag([
            'rel' => 'title',
            'content' => $title
        ]);
    }

    /**
     * Add naver site verification meta tag
     *
     * @param string|null $verification
     *
     * @return void
     */
    public function addNaverSiteVerificationMetaTag(?string $verification = null): void
    {
        $this->addMetaTag([
            'property' => 'naver-site-verification',
            'content' => $verification
        ]);
    }

    /**
     * Add app meta tag
     *
     * @param string|null $platform
     * @param string|null $key
     * @param string|null $value
     *
     * @return void
     */
    public function addAppMetaTag(?string $platform = null, ?string $key = null, ?string $value = null): void
    {
        $this->addMetaTag([
            'property' => "al:{$platform}:{$key}",
            'content' => $value
        ]);
    }

    /**
     * Add og video tag meta tag
     *
     * @param string|null $tag
     *
     * @return void
     */
    public function addOgVideoTagMetaTag(?string $tag = null): void
    {
        $this->addMetaTag([
            'property' => 'og:video:tag',
            'content' => $tag
        ]);
    }

    /**
     * Add keywords meta tag
     *
     * @param array $keywords
     *
     * @return void
     */
    public function addKeywordsMetaTag(array $keywords = []): void
    {
        $this->addMetaTag([
            'name' => 'keywords',
            'content' => implode(', ', (array) $keywords)
        ]);
    }

    /**
     * Add microsoft application navigation button color meta tag
     *
     * @param string|null $color
     *
     * @return void
     */
    public function addMicrosoftApplicationNavigationButtonColorMetaTag(?string $color = ""): void
    {
        $this->addMetaTag([
            'name' => 'msapplication-navbutton-color',
            'content' => $color
        ]);
    }

    /**
     * Add google site verification meta tag
     *
     * @param string|null $verification
     *
     * @return void
     */
    public function addGoogleSiteVerificationMetaTag(?string $verification = ""): void
    {
        $this->addMetaTag([
            'name' => 'google-site-verification',
            'content' => $verification
        ]);
    }

    /**
     * Add apple mobile web app title meta tag
     *
     * @param string|null $title
     *
     * @return void
     */
    public function addAppleMobileWebAppTitleMetaTag(?string $title = null): void
    {
        $this->addMetaTag([
            'name' => 'apple-mobile-web-app-title',
            'content' => $title
        ]);
    }

    /**
     * Add apple mobile web app status bar style meta tag
     *
     * @param string|null $style
     *
     * @return void
     */
    public function addAppleMobileWebAppStatusBarStyleMetaTag(?string $style = null): void
    {
        $this->addMetaTag([
            'name' => 'apple-mobile-web-app-status-bar-style',
            'content' => $style
        ]);
    }

    /**
     * Add apple mobile web app capable meta tag
     *
     * @param bool $capable
     *
     * @return void
     */
    public function addAppleMobileWebAppCapableMetaTag(bool $capable = false): void
    {
        $this->addMetaTag([
            'name' => 'apple-mobile-web-app-capable',
            'content' => $capable ? 'yes' : 'no'
        ]);
    }

    /**
     * Add twitter image meta tag
     *
     * @param string|null $content
     *
     * @return void
     */
    public function addTwitterImageMetaTag(?string $content = null): void
    {
        $this->addTwitterMetaTag('image', $content);
    }

    /**
     * Add twitter meta tag
     *
     * @param string|null $name
     * @param string|null $content
     *
     * @return void
     */
    public function addTwitterMetaTag(?string $name = null, ?string $content = null): void
    {
        $this->addMetaTag([
            'name' => "twitter:$name",
            'content' => $content
        ]);
    }

    /**
     * Add open graph meta tag
     *
     * @param string|null $property
     * @param string|null $content
     *
     * @return void
     */
    public function addOpenGraphMetaTag(?string $property = null, ?string $content = null): void
    {
        $this->addMetaTag([
            'property' => "og:$property",
            'content' => $content
        ]);
    }

    /**
     * Add og description meta tag
     *
     * @param string|null $ogDescription
     *
     * @return void
     */
    public function addOgDescriptionMetaTag(?string $ogDescription = null): void
    {
        $this->addMetaTag([
            'property' => 'og:description',
            'content' => $ogDescription
        ]);
    }

    /**
     * Add og locale meta tag
     *
     * @param string|null $ogType
     *
     * @return void
     */
    public function addOgLocaleMetaTag(?string $ogType = null): void
    {
        $this->addMetaTag([
            'property' => 'og:locale',
            'content' => $ogType
        ]);
    }

    /**
     * Add og type meta tag
     *
     * @param string|null $ogType
     *
     * @return void
     */
    public function addOgTypeMetaTag(?string $ogType = null): void
    {
        $this->addMetaTag([
            'property' => 'og:type',
            'content' => $ogType
        ]);
    }

    /**
     * Add og video url meta tag
     *
     * @param string|null $videoUrl
     *
     * @return void
     */
    public function addOgVideoUrlMetaTag(?string $videoUrl = null): void
    {
        $this->addMetaTag([
            'property' => 'og:video:url',
            'content' => $videoUrl
        ]);
    }

    /**
     * Add og video secure url meta tag
     *
     * @param string|null $secureUrl
     *
     * @return void
     */
    public function addOgVideoSecureUrlMetaTag(?string $secureUrl = null): void
    {
        $this->addMetaTag([
            'property' => 'og:video:secure_url',
            'content' => $secureUrl
        ]);
    }

    /**
     * Add og video type meta tag
     *
     * @param string|null $videoType
     *
     * @return void
     */
    public function addOgVideoTypeMetaTag(?string $videoType = null): void
    {
        $this->addMetaTag([
            'property' => 'og:video:type',
            'content' => $videoType
        ]);
    }

    /**
     * Add og video width meta tag
     *
     * @param int|null $videoWidth
     *
     * @return void
     */
    public function addOgVideoWidthMetaTag(?int $videoWidth = null): void
    {
        $this->addMetaTag([
            'property' => 'og:video:width',
            'content' => $videoWidth
        ]);
    }

    /**
     * Add og video height meta tag
     *
     * @param int|null $videoHeight
     *
     * @return void
     */
    public function addOgVideoHeightMetaTag(?int $videoHeight = null): void
    {
        $this->addMetaTag([
            'property' => 'og:video:height',
            'content' => $videoHeight
        ]);
    }

    /**
     * Add twitter card meta tag
     *
     * @param string|null $cardType
     *
     * @return void
     */
    public function addTwitterCardMetaTag(?string $cardType = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:card',
            'content' => $cardType
        ]);
    }

    /**
     * Add twitter site meta tag
     *
     * @param string|null $site
     *
     * @return void
     */
    public function addTwitterSiteMetaTag(?string $site = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:site',
            'content' => $site
        ]);
    }

    /**
     * Add twitter url meta tag
     *
     * @param string|null $url
     *
     * @return void
     */
    public function addTwitterUrlMetaTag(?string $url = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:url',
            'content' => $url
        ]);
    }

    /**
     * Add twitter title meta tag
     *
     * @param string|null $title
     *
     * @return void
     */
    public function addTwitterTitleMetaTag(?string $title = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:title',
            'content' => $title
        ]);
    }

    /**
     * Add twitter description meta tag
     *
     * @param string|null $description
     *
     * @return void
     */
    public function addTwitterDescriptionMetaTag(?string $description = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:description',
            'content' => $description
        ]);
    }

    /**
     * Add twitter app name iphone meta tag
     *
     * @param string|null $appName
     *
     * @return void
     */
    public function addTwitterAppNameIphoneMetaTag(?string $appName = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:name:iphone',
            'content' => $appName
        ]);
    }

    /**
     * Add twitter app id iphone meta tag
     *
     * @param string|null $appId
     *
     * @return void
     */
    public function addTwitterAppIdIphoneMetaTag(?string $appId = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:id:iphone',
            'content' => $appId
        ]);
    }

    /**
     * Add twitter app name ipad meta tag
     *
     * @param string|null $appName
     *
     * @return void
     */
    public function addTwitterAppNameIpadMetaTag(?string $appName = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:name:ipad',
            'content' => $appName
        ]);
    }

    /**
     * Add twitter app id ipad meta tag
     *
     * @param string|null $appId
     *
     * @return void
     */
    public function addTwitterAppIdIpadMetaTag(?string $appId = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:id:ipad',
            'content' => $appId
        ]);
    }

    /**
     * Add twitter app url iphone meta tag
     *
     * @param string|null $appUrl
     *
     * @return void
     */
    public function addTwitterAppUrlIphoneMetaTag(?string $appUrl = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:url:iphone',
            'content' => $appUrl
        ]);
    }

    /**
     * Add twitter app url ipad meta tag
     *
     * @param string|null $appUrl
     *
     * @return void
     */
    public function addTwitterAppUrlIpadMetaTag(?string $appUrl = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:url:ipad',
            'content' => $appUrl
        ]);
    }

    /**
     * Add twitter app url googleplay meta tag
     *
     * @param string|null $appName
     *
     * @return void
     */
    public function addTwitterAppNameGoogleplayMetaTag(?string $appName = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:name:googleplay',
            'content' => $appName
        ]);
    }

    /**
     * Add twitter app id googleplay meta tag
     *
     * @param string|null $appId
     *
     * @return void
     */
    public function addTwitterAppIdGoogleplayMetaTag(?string $appId = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:id:googleplay',
            'content' => $appId
        ]);
    }

    /**
     * Add twitter app url googleplay meta tag
     *
     * @param string|null $appUrl
     *
     * @return void
     */
    public function addTwitterAppUrlGoogleplayMetaTag(?string $appUrl = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:app:url:googleplay',
            'content' => $appUrl
        ]);
    }

    /**
     * Add twitter player meta tag
     *
     * @param string|null $player
     *
     * @return void
     */
    public function addTwitterPlayerMetaTag(?string $player = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:player',
            'content' => $player
        ]);
    }

    /**
     * Add twitter player width meta tag
     *
     * @param int|null $width
     *
     * @return void
     */
    public function addTwitterPlayerWidthMetaTag(?int $width = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:player:width',
            'content' => $width
        ]);
    }

    /**
     * Add twitter player height meta tag
     *
     * @param int|null $height
     *
     * @return void
     */
    public function addTwitterPlayerHeightMetaTag(?int $height = null): void
    {
        $this->addMetaTag([
            'name' => 'twitter:player:height',
            'content' => $height
        ]);
    }

    /**
     * Add interaction statistic meta tag
     *
     * @param int|null $userInteractionCount
     *
     * @return void
     */
    public function addInteractionStatisticMetaTag(?int $userInteractionCount = null): void
    {
        $this->addMetaTag([
            'itemprop' => 'interactionStatistic',
            'itemscope' => '',
            'itemtype' => 'https://schema.org/InteractionCounter',
            'content' => $userInteractionCount
        ]);
    }

    /**
     * Add date published meta tag
     *
     * @param string|null $datePublished
     *
     * @return void
     */
    public function addDatePublishedMetaTag(?string $datePublished = null): void
    {
        $this->addMetaTag([
            'itemprop' => 'datePublished',
            'content' => $datePublished
        ]);
    }

    /**
     * Add upload date meta tag
     *
     * @param string|null $uploadDate
     *
     * @return void
     */
    public function addUploadDateMetaTag(?string $uploadDate = null): void
    {
        $this->addMetaTag([
            'itemprop' => 'uploadDate',
            'content' => $uploadDate
        ]);
    }

    /**
     * Add genre meta tag
     *
     * @param string|null $genre
     *
     * @return void
     */
    public function addGenreMetaTag(?string $genre = null): void
    {
        $this->addMetaTag([
            'itemprop' => 'genre',
            'content' => $genre
        ]);
    }

    /**
     * Add meta tag to head
     *
     * @param array $tag
     *
     * @return void
     */
    public function addMetaTag(array $tag = []): void
    {
        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $resource->addMetaTag($tag);
    }

    /**
     * Add JS file to head
     *
     * @param string|null $filename Public web path or {@see addJsFileToHeadFromBase}
     *
     * @return void
     */
    public function addJsFileToHead(?string $filename = null): void
    {
        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $resource->addGenericJavascriptFile($filename);
    }

    /**
     * Add JS file to head using a path relative to the app root (BASE_PATH)
     *
     * @param string $relativeToBasePath Same segments as under {@code res/Platform/PHP/root/} for IDE path completion
     *
     * @return void
     */
    public function addJsFileToHeadFromBase(string $relativeToBasePath): void
    {
        $this->addJsFileToHead(PublicPath::fromBase($relativeToBasePath));
    }

    /**
     * Add module JS file to head
     *
     * @param string|null $filename
     *
     * @return void
     */
    public function addModuleJsFileToHead(?string $filename = null): void
    {
        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $resource->addModuleJavascriptFile($filename);
    }

    /**
     * Add module JS file to head using a path relative to the app root (BASE_PATH)
     *
     * @param string $relativeToBasePath Same segments as under the web app root for IDE path completion
     *
     * @return void
     */
    public function addModuleJsFileToHeadFromBase(string $relativeToBasePath): void
    {
        $this->addModuleJsFileToHead(PublicPath::fromBase($relativeToBasePath));
    }

    /**
     * Set a title in browser
     *
     * @param string $title
     *
     * @return void
     */
    public function setTitle(?string $title = null): void
    {
        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $resource->setTitle($title);
    }

    /**
     * Redirect to location
     *
     * @param string|null $location
     *
     * @return Response
     */
    public function redirect(?string $location = null): Response
    {
        return new Response($location, [], 'redirect');
    }

    /**
     * Response JSON
     *
     * @param array|ArrayObject $json
     * @param array $resource
     *
     * @return Response
     */
    public function responseJson(array|ArrayObject $json = [], array $resource = []): Response
    {
        $json = $json instanceof ArrayObject ? $json->toPHPObject() : $json;

        $encoded = JSONHandler::encode($json);

        if (!JSONHandler::isJSON($encoded)) {
            throw new \Exception("Invalid json format");
        }

        return new Response($encoded, $resource, 'json');
    }

    /**
     * Render text content
     *
     * @param string|null $content
     *
     * @return Response
     */
    public function renderText(?string $content = null): Response
    {
        return $this->response($content);
    }

    /**
     * Render a C template
     *
     * @param string|null $template
     * @param array|ArrayObject $data
     *
     * @return Response
     */
    public function renderWithCTemplate(?string $template = null, ArrayObject|array $data = []): Response
    {
        $data = $data instanceof ArrayObject ? $data->toPHPObject() : $data;

        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $extractedRecources = $resource->extract();

        /** @var Renderer $renderer */
        $renderer = $this->container->get(Renderer::class);

        $renderedContent = $renderer->renderWithCTemplate(BASE_PATH . $template, $data);

        return $this->response($renderedContent, $extractedRecources);
    }

    /**
     * Render a template
     *
     * @param string|null $template
     * @param array|ArrayObject $data
     *
     * @return Response
     */
    public function render(?string $template = null, ArrayObject|array $data = []): Response
    {
        $data = $data instanceof ArrayObject ? $data->toPHPObject() : $data;

        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);

        $extractedRecources = $resource->extract();

        /** @var Renderer $renderer */
        $renderer = $this->container->get(Renderer::class);

        $renderedContent = $renderer->render(BASE_PATH . $template, $data);

        return $this->response($renderedContent, $extractedRecources);
    }
}
