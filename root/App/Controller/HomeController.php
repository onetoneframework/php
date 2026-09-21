<?php

namespace App\Controller;

use Clover\Annotation;
use Clover\Annotation\Autowiring;
use Clover\Classes\HTTP\Request;
use Clover\Classes\Layout\Menu;
use Clover\Classes\Markdown\Markdown;
use Clover\Framework\Component\LayoutComponentController;
use Clover\Framework\Component\Response;
use Clover\Plugin\KakaoLogin;
use Clover\Plugin\NaverLogin;
use Clover\Classes\File\Handler as FileHandler;
use function sprintf;
use function is_file;

#[Annotation\Prefix('/')]
class HomeController extends LayoutComponentController
{
    #[Autowiring]
    public static KakaoLogin $kakaoLogin;

    #[Autowiring]
    public static NaverLogin $naverLogin;

    private static $templateData;

    public function __construct(\Clover\Classes\DependencyInjection\Container|null $container = null)
    {
        $menu = new Menu();

        $menu->addItem("Products", "/products")
            ->addChild("Framework", "/products/framework")
            ->addChild("Onetone Cloud", "/products/cloud")
            ->addChild("Server Management", "/products/server")
            ->addChild("Analytics", "/products/analytics");

        $menu->addItem("Docs", "/docs")
            ->addChild("Getting Started", "/docs/installation")
            ->addChild("Architecture", "/docs/architecture")
            ->addChild("Components", "/docs/components")
            ->addChild("API Reference", "/docs/api");

        $menu->addItem("Resources", "/resources")
            ->addChild("Tutorials", "/resources/tutorials")
            ->addChild("Certification", "/resources/certification")
            ->addChild("Podcast", "/resources/podcast");

        $menu->addItem("Ecosystem", "/ecosystem")
            ->addChild("Packages", "/ecosystem/packages")
            ->addChild("Themes", "/ecosystem/themes")
            ->addChild("Extensions", "/ecosystem/extensions");

        $menu->addItem("News", "/news")
            ->addChild("Blog", "/news/blog")
            ->addChild("Release Notes", "/news/releases")
            ->addChild("Events", "/news/events");

        $menu->addItem("Partners", "/partners")
            ->addChild("Find a Partner", "/partners/find")
            ->addChild("Become a Partner", "/partners/join");

        $menu->addItem("Enterprise", "/enterprise")
            ->addChild("Support", "/enterprise/support")
            ->addChild("Consulting", "/enterprise/consulting");

        $menu->addItem("Shop", "/shop")
            ->addChild("Merch", "/shop/merch")
            ->addChild("Donations", "/shop/donate");

        $menu->addItem("Community", "/community")
            ->addChild("Forum", "/community/forum")
            ->addChild("Discord", "/community/discord")
            ->addChild("Contribute", "/community/contribute");

        $menu->addItem("About", "/about")
            ->addChild("Story", "/about/story")
            ->addChild("Team", "/about/team")
            ->addChild("Careers", "/about/careers")
            ->addChild("Brand Assets", "/about/brand");

        $templateData = [];
        $templateData['fold'] = true;
        $templateData['menus'] = $menu->toArray();

        $templateData['logo'] = '/App/View/Logo.png';
        $templateData['subject'] = 'The PHP Framework for Web Artisans';
        $templateData['description'] = 'Onetone is a web application framework with expressive, elegant syntax. We’ve already laid the foundation — freeing you to create without sweating the small things.';

        self::$templateData = $templateData;

        parent::__construct($container);
    }

    private function prepareSitePageAssets(): void
    {
        $skin = 'MenuShadow';
        $absolutePath = sprintf('/App/Frontend/Layout/%s', $skin);
        $this->addCssFileToHead('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css');
        $this->addCssFileToHead(sprintf('%s/about.css', $absolutePath));
        $this->addCssFileToHead(sprintf('%s/site-pages.css', $absolutePath));
    }

    private function siteNotFoundResponse(): Response
    {
        $this->prepareSitePageAssets();
        $this->setTitle('Not Found · Onetone');

        return $this->layout('MenuShadow', '/App/View/site/not_found.php', self::$templateData)
            ->setNotFoundStatus();
    }

    /**
     * @param string $viewPath e.g. App/View/site/products.php (no leading slash)
     */
    private function renderSitePhp(string $viewPath, string $title): Response
    {
        $absolutePath = sprintf('%s/%s', BASE_PATH, $viewPath);
        if (!is_file($absolutePath)) {
            return $this->siteNotFoundResponse();
        }

        $this->prepareSitePageAssets();
        $this->setTitle($title);

        return $this->layout('MenuShadow', '/' . $viewPath, self::$templateData);
    }

    #[Annotation\Route(method: 'GET', pattern: '/')]
    public function index(Request $request): Response
    {
        $skin = 'MenuShadow';
        $absolutePath = sprintf("App/Frontend/Layout/%s", $skin);

		$this->addCssFileToHead("/App/Resource/js/highlight/styles/base16/grayscale-dark.min.css");
        $this->addCssFileToHead("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css");
		$this->addJsFileToHead("/App/Resource/js/highlight/highlight.min.js");
        $this->addCssFileToHead(sprintf("%s/about.css", $absolutePath));

        $this->setTitle('Onetone Framework');
        return $this->layout($skin, '/App/View/introduce.php', self::$templateData);
    }

    #[Annotation\Route(method: 'GET', pattern: '/naverLogin')]
    public function naverLogin(Request $request): Response
    {
        $loginUrl = self::$naverLogin->getLoginUrl();

        return $this->redirect($loginUrl);
    }

    #[Annotation\Route(method: 'GET', pattern: '/xml')]
    public function test(Request $request): Response
    {
        return $this->responseXml(['a' => 'test']);
    }

    #[Annotation\Route(method: 'GET', pattern: '/kakaoLogin')]
    public function kakaoLogin(Request $request): Response
    {
        $loginUrl = self::$kakaoLogin->getLoginUrl();

        return $this->redirect($loginUrl);
    }

    #[Annotation\Route(method: 'GET', pattern: '/naverAuth')]
    public function naverAuth(Request $request): Response
    {
        $code = Request::getQueryParameter('code');
        $token = self::$naverLogin->getToken($code);

        if ($token->isContainKey('error_code')) {
            return $this->redirect("http://localhost:8080/signin");
        }

        $accessToken = $token['access_token'];
        $refreshToken = $token['refresh_token'];

        return $this->redirect("http://localhost:8080/signin");
    }

    #[Annotation\Route(method: 'GET', pattern: '/kakaoAuth')]
    public function kakaoAuth(Request $request): Response
    {
        $code = Request::getQueryParameter('code');
        $token = self::$kakaoLogin->getToken($code);

        if (isset($token['error_code'])) {
            return $this->redirect("http://localhost:8080/signin");
        }

        $accessToken = $token['access_token'];
        $refreshToken = $token['refresh_token'];

        return $this->redirect("http://localhost:8080/signin");
    }

    private function buildDocumentationResponse(string $markdownFile, string $activeDoc, string $pageTitle): Response
    {
        $path = sprintf('%s/App/View/markdown/%s', BASE_PATH, $markdownFile);
        $markdownContent = FileHandler::read($path);
        $markdown = new Markdown();
        $content = $markdown->convert($markdownContent);

        $skin = 'MenuShadow';
        $absolutePath = sprintf("/App/Frontend/Layout/%s", $skin);
        $this->addCssFileToHead(sprintf("%s/about.css", $absolutePath));
		$this->addCssFileToHead("/App/Resource/js/highlight/styles/base16/grayscale-dark.min.css");
        $this->addJsFileToHead("https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js");
        $this->addCssFileToHead(sprintf("%s/markdown.css", $absolutePath));

        $this->setTitle($pageTitle);
        return $this->layout($skin, '/App/View/documentation.php', self::$templateData, [
            'content' => $content,
            'activeDoc' => $activeDoc,
        ]);
    }

    #[Annotation\Route(method: 'GET', pattern: '/documentation')]
    public function documentation(Request $request): Response
    {
        return $this->buildDocumentationResponse('interpreter.md', 'interpreter', 'Interpreter · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/documentation/php')]
    public function documentationPhp(Request $request): Response
    {
        return $this->buildDocumentationResponse('php.md', 'php', 'PHP platform · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/documentation/frida')]
    public function documentationFrida(Request $request): Response
    {
        return $this->buildDocumentationResponse('frida.md', 'frida', 'Frida tools · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/documentation/typescript')]
    public function documentationTypescript(Request $request): Response
    {
        return $this->buildDocumentationResponse('typescript.md', 'typescript', 'TypeScript platform · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/documentation/android')]
    public function documentationAndroid(Request $request): Response
    {
        return $this->buildDocumentationResponse('android.md', 'android', 'Android platform · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/documentation/java')]
    public function documentationJava(Request $request): Response
    {
        return $this->buildDocumentationResponse('java.md', 'java', 'Java platform · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/documentation/python')]
    public function documentationPython(Request $request): Response
    {
        return $this->buildDocumentationResponse('python.md', 'python', 'Python platform · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/products')]
    public function products(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/products.php', 'Products · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/products/framework')]
    public function productsFramework(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/products/framework.php', 'Framework · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/products/cloud')]
    public function productsCloud(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/products/cloud.php', 'Onetone Cloud · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/products/server')]
    public function productsServer(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/products/server.php', 'Server management · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/products/analytics')]
    public function productsAnalytics(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/products/analytics.php', 'Analytics · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/docs')]
    public function docs(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/docs.php', 'Documentation · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/docs/installation')]
    public function docsInstallation(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/docs/installation.php', 'Getting started · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/docs/architecture')]
    public function docsArchitecture(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/docs/architecture.php', 'Architecture · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/docs/components')]
    public function docsComponents(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/docs/components.php', 'Components · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/docs/api')]
    public function docsApi(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/docs/api.php', 'API reference · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/resources')]
    public function resources(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/resources.php', 'Resources · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/resources/tutorials')]
    public function resourcesTutorials(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/resources/tutorials.php', 'Tutorials · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/resources/certification')]
    public function resourcesCertification(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/resources/certification.php', 'Certification · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/resources/podcast')]
    public function resourcesPodcast(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/resources/podcast.php', 'Podcast · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/ecosystem')]
    public function ecosystem(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/ecosystem.php', 'Ecosystem · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/ecosystem/packages')]
    public function ecosystemPackages(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/ecosystem/packages.php', 'Packages · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/ecosystem/themes')]
    public function ecosystemThemes(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/ecosystem/themes.php', 'Themes · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/ecosystem/extensions')]
    public function ecosystemExtensions(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/ecosystem/extensions.php', 'Extensions · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/news')]
    public function news(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/news.php', 'News · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/news/blog')]
    public function newsBlog(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/news/blog.php', 'Blog · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/news/releases')]
    public function newsReleases(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/news/releases.php', 'Release notes · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/news/events')]
    public function newsEvents(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/news/events.php', 'Events · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/partners')]
    public function partners(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/partners.php', 'Partners · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/partners/find')]
    public function partnersFind(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/partners/find.php', 'Find a partner · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/partners/join')]
    public function partnersJoin(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/partners/join.php', 'Become a partner · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/enterprise')]
    public function enterprise(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/enterprise.php', 'Enterprise · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/enterprise/support')]
    public function enterpriseSupport(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/enterprise/support.php', 'Enterprise support · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/enterprise/consulting')]
    public function enterpriseConsulting(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/enterprise/consulting.php', 'Consulting · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/shop')]
    public function shop(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/shop.php', 'Shop · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/shop/merch')]
    public function shopMerch(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/shop/merch.php', 'Merch · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/shop/donate')]
    public function shopDonate(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/shop/donate.php', 'Donations · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/community')]
    public function community(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/community.php', 'Community · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/community/forum')]
    public function communityForum(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/community/forum.php', 'Forum · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/community/discord')]
    public function communityDiscord(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/community/discord.php', 'Discord · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/community/contribute')]
    public function communityContribute(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/community/contribute.php', 'Contribute · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/about')]
    public function about(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/about.php', 'About · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/about/story')]
    public function aboutStory(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/about/story.php', 'Our story · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/about/team')]
    public function aboutTeam(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/about/team.php', 'Team · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/about/careers')]
    public function aboutCareers(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/about/careers.php', 'Careers · Onetone');
    }

    #[Annotation\Route(method: 'GET', pattern: '/about/brand')]
    public function aboutBrand(Request $request): Response
    {
        return $this->renderSitePhp('App/View/site/about/brand.php', 'Brand assets · Onetone');
    }

    /**
     * Liveness probe for orchestrators and load balancers (lightweight, no external dependencies).
     */
    #[Annotation\Route(method: 'GET', pattern: '/health')]
    public function health(Request $request): Response
    {
        return $this->responseJson([
            'status' => 'ok',
            'time' => gmdate('c'),
        ]);
    }

    /**
     * Readiness probe; extend with dependency checks when wiring production databases or queues.
     */
    #[Annotation\Route(method: 'GET', pattern: '/ready')]
    public function ready(Request $request): Response
    {
        return $this->responseJson([
            'status' => 'ready',
            'time' => gmdate('c'),
            'checks' => [
                'php' => true,
            ],
        ]);
    }
}
