<?php

declare(strict_types=1);

use Clover\Classes\AI\Orchestration\AgentRegistry;
use Clover\Classes\AI\Orchestration\Orchestrator;
use Clover\Classes\Database\ActiveRecord;
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\Scheduler\Scheduler;
use Clover\Framework\Component\AppUrl;
use Clover\Framework\Component\DatabaseConfig;
use Clover\Plugin\KakaoLogin;
use Clover\Plugin\NaverLogin;

/**
 * App-level container configuration for {@see Mapper::setContainer()}.
 *
 * Symfony-like style:
 * - register services via Container::set()
 * - set aliases via Container::bind()
 *
 * @return callable(Container): void
 */
return static function (Container $container): void {
    $env = static function (string $key, ?string $default = ''): string {
        $value = $_ENV[$key] ?? getenv($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : (string) $default;
    };

    $container->set(KakaoLogin::class, static function (Container $container) use ($env): KakaoLogin {
        $kakaoLogin = new KakaoLogin();
        $kakaoLogin->setClientSecret($env('KAKAO_LOGIN_CLIENT_SECRET', ''));
        $kakaoLogin->setClientId($env('KAKAO_RESTFUL_API_KEY', ''));
        $kakaoLogin->setRedirectUrl($env('KAKAO_REDIRECT_URL', AppUrl::absolute('/kakaoAuth')));

        return $kakaoLogin;
    });

    $container->set(NaverLogin::class, static function (Container $container) use ($env): NaverLogin {
        $naverLogin = new NaverLogin();
        $naverLogin->setClientSecret($env('NAVER_CLIENT_SECRET', ''));
        $naverLogin->setClientId($env('NAVER_CLIENT_ID', ''));
        $naverLogin->setRedirectUrl($env('NAVER_REDIRECT_URL', AppUrl::absolute('/naverAuth')));

        return $naverLogin;
    });

	$agentRegistry = new AgentRegistry();
	$container->set(AgentRegistry::class, $agentRegistry);
	$container->set(Orchestrator::class, new Orchestrator($agentRegistry));

    $container->set(PHPDataObject::class, static function (Container $container): PHPDataObject {
        $db = new PHPDataObject();
        DatabaseConfig::fromEnv()->apply($db);
        $db->createConnection();

        ActiveRecord::setDatabaseConnection($db);

        return $db;
    });

    $container->bind('db', PHPDataObject::class);

    // Teach ActiveRecord how to obtain its connection.
    //
    // ActiveRecord keeps its handle in a static that setDatabaseConnection() populates, and the
    // factory above is what calls it — but a factory registered with Container::set() only runs
    // when something resolves it, and nothing in the application ever resolves 'db' or
    // PHPDataObject::class. Entities reach the database through the static, never through the
    // container, so every query used to throw "Database connection not set" regardless of how the
    // MYSQL_* environment was configured.
    //
    // Registered as a resolver rather than resolved here, so a request that touches no entity never
    // opens a connection and booting the container stays free of I/O.
    ActiveRecord::setConnectionResolver(static function () use ($container): void {
        $container->get(PHPDataObject::class);
    });
	$container->bind('ai.agents', AgentRegistry::class);
	$container->bind('ai.orchestrator', Orchestrator::class);
    $container->bind('kakao.login', KakaoLogin::class);
    $container->bind('naver.login', NaverLogin::class);

    // Task scheduler: a single Scheduler instance is shared across the
    // request lifecycle so CLI commands and HTTP handlers see the same
    // set of registered tasks.
    $container->set(Scheduler::class, static function (Container $container) use ($env): Scheduler {
        $timezoneName = $env('APP_TIMEZONE', 'UTC');

        try {
            $timezone = new \DateTimeZone($timezoneName);
        } catch (\Throwable) {
            $timezone = new \DateTimeZone('UTC');
        }

        $scheduler = new Scheduler($timezone);

        // Optional: users can declare tasks in App/Configure/schedule.php.
        // The file must return a callable that receives the Scheduler
        // and registers one or more tasks on it.
        $scheduleFile = sprintf('%s/App/Configure/schedule.php', BASE_PATH);
        if (FileHandler::isExists($scheduleFile)) {
            $definitions = require $scheduleFile;

            if (is_callable($definitions)) {
                $definitions($scheduler, $container);
            }
        }

        return $scheduler;
    });

    $container->bind('scheduler', Scheduler::class);
};
