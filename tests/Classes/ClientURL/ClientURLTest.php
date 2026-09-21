<?php

declare(strict_types=1);

namespace Clover\Tests;

use Clover\Classes\ClientURLErrorResponse;
use Clover\Classes\Mock\MockClientURL;
use Clover\Classes\Mock\MockClientURLLastTransferInformation;
use Clover\Classes\Mock\MockClientURLOption;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * ClientURLTest
 *
 * Full unit-test suite for MockClientURL, MockClientURLOption,
 * MockClientURLLastTransferInformation, and ClientURLErrorResponse.
 *
 * Test groups
 * -----------
 *  Group A  – MockClientURLOption: option recording
 *  Group B  – MockClientURLLastTransferInformation: preset transfer info
 *  Group C  – MockClientURL (instance): execute, errors, detailed responses
 *  Group D  – MockClientURL (static): GET, POST, PUT, PATCH, DELETE, JSON, etc.
 *  Group E  – ClientURLErrorResponse: pure-logic error/HTTP classification
 *  Group F  – Integration scenarios: retry queue, auth, pagination, diagnostics
 */
final class ClientURLTest extends TestCase
{
    // ------------------------------------------------------------------ //
    //  Shared fixtures                                                      //
    // ------------------------------------------------------------------ //

    private MockClientURL $mock;
    private MockClientURLOption $optionMock;
    private MockClientURLLastTransferInformation $infoMock;
    private ClientURLErrorResponse $errorResponse;

    protected function setUp(): void
    {
        $this->mock = new MockClientURL('https://api.example.com/test');
        $this->optionMock = new MockClientURLOption();
        $this->infoMock = new MockClientURLLastTransferInformation();
        $this->errorResponse = new ClientURLErrorResponse();

        // Always start with a clean static registry
        MockClientURL::clearStaticMock();
    }

    protected function tearDown(): void
    {
        MockClientURL::clearStaticMock();
    }

    // ==================================================================== //
    //  GROUP A – MockClientURLOption                                         //
    // ==================================================================== //

    /** @test */
    public function optionRecordsSetUrl(): void
    {
        $this->optionMock->setURL('https://example.com');
        self::assertSame('https://example.com', $this->optionMock->getURL());
    }

    /** @test */
    public function optionRecordsGetMethod(): void
    {
        $this->optionMock->setGetMethod(true);
        self::assertSame('GET', $this->optionMock->getMethod());
    }

    /** @test */
    public function optionRecordsPostMethod(): void
    {
        $this->optionMock->setPostMethod(true);
        self::assertSame('POST', $this->optionMock->getMethod());
    }

    /** @test */
    public function optionRecordsPutMethod(): void
    {
        $this->optionMock->setPutMethod();
        self::assertSame('PUT', $this->optionMock->getMethod());
    }

    /** @test */
    public function optionRecordsCustomMethod(): void
    {
        $this->optionMock->setCustomRequest('DELETE');
        self::assertSame('DELETE', $this->optionMock->getMethod());
    }

    /** @test */
    public function optionRecordsHeader(): void
    {
        $this->optionMock->setHeader('Authorization', 'Bearer token123');
        self::assertSame('Bearer token123', $this->optionMock->getHeader('Authorization'));
    }

    /** @test */
    public function optionRecordsMultipleHeaders(): void
    {
        $this->optionMock
            ->setHeader('Accept', 'application/json')
            ->setHeader('X-Custom', 'value');

        self::assertSame('application/json', $this->optionMock->getHeader('Accept'));
        self::assertSame('value', $this->optionMock->getHeader('X-Custom'));
    }

    /** @test */
    public function optionSetsJsonContentType(): void
    {
        $this->optionMock->setContentTypeApplicationJson();
        self::assertSame('application/json', $this->optionMock->getHeader('Content-Type'));
    }

    /** @test */
    public function optionSetsFormContentType(): void
    {
        $this->optionMock->setContentTypeFormUrlEncoded();
        self::assertSame(
            'application/x-www-form-urlencoded',
            $this->optionMock->getHeader('Content-Type')
        );
    }

    /** @test */
    public function optionRecordsSSLVerifyPeer(): void
    {
        $this->optionMock->setSSLVerifyPeer(false);
        self::assertFalse($this->optionMock->isSSLVerifyPeer());
    }

    /** @test */
    public function optionRecordsReturnTransfer(): void
    {
        $this->optionMock->setReturnTransfer(true);
        self::assertTrue($this->optionMock->isReturnTransfer());
    }

    /** @test */
    public function optionRecordsFollowRedirects(): void
    {
        $this->optionMock->setFollowRedirects(true);
        self::assertTrue($this->optionMock->isFollowRedirects());
    }

    /** @test */
    public function optionRecordsTimeout(): void
    {
        $this->optionMock->setTimeout(30);
        self::assertSame(30, $this->optionMock->getTimeout());
    }

    /** @test */
    public function optionRecordsPostField(): void
    {
        $body = '{"name":"Alice"}';
        $this->optionMock->setPostField($body);
        self::assertSame($body, $this->optionMock->getPostField());
    }

    /** @test */
    public function optionResetClearsAllState(): void
    {
        $this->optionMock
            ->setURL('https://example.com')
            ->setPostMethod(true)
            ->setHeader('Accept', 'application/json')
            ->setTimeout(60);

        $this->optionMock->reset();

        self::assertNull($this->optionMock->getURL());
        self::assertSame('GET', $this->optionMock->getMethod());
        self::assertEmpty($this->optionMock->getHeaders());
        self::assertNull($this->optionMock->getTimeout());
    }

    /** @test */
    public function optionFluentChainReturnsInstance(): void
    {
        $result = $this->optionMock->setURL('https://x.com')->setGetMethod(true);
        self::assertSame($this->optionMock, $result);
    }

    /** @test */
    public function optionGetCurlOptionsReturnsRecordedConstants(): void
    {
        $this->optionMock
            ->setURL('https://example.com')
            ->setReturnTransfer(true)
            ->setSSLVerifyPeer(false);

        $opts = $this->optionMock->getCurlOptions();

        self::assertArrayHasKey(CURLOPT_URL, $opts);
        self::assertArrayHasKey(CURLOPT_RETURNTRANSFER, $opts);
        self::assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $opts);
    }

    /** @test */
    public function optionReturnsNullForUnknownHeader(): void
    {
        self::assertNull($this->optionMock->getHeader('X-Not-Set'));
    }

    // ==================================================================== //
    //  GROUP B – MockClientURLLastTransferInformation                        //
    // ==================================================================== //

    /** @test */
    public function infoDefaultStatusCodeIs200(): void
    {
        self::assertSame(200, $this->infoMock->getStatusCode());
    }

    /** @test */
    public function infoReportsSuccess(): void
    {
        $this->infoMock->setStatusCode(200);
        self::assertTrue($this->infoMock->isSuccessful());
    }

    /** @test */
    public function infoReportsSuccessFor201(): void
    {
        $this->infoMock->setStatusCode(201);
        self::assertTrue($this->infoMock->isSuccessful());
    }

    /** @test */
    public function infoReportsNotSuccessForClientError(): void
    {
        $this->infoMock->setStatusCode(404);
        self::assertFalse($this->infoMock->isSuccessful());
    }

    /** @test */
    public function infoDetectsClientError(): void
    {
        $this->infoMock->setStatusCode(400);
        self::assertTrue($this->infoMock->isClientError());
        self::assertFalse($this->infoMock->isServerError());
    }

    /** @test */
    public function infoDetectsServerError(): void
    {
        $this->infoMock->setStatusCode(500);
        self::assertTrue($this->infoMock->isServerError());
        self::assertFalse($this->infoMock->isClientError());
    }

    /** @test */
    public function infoReturnsConfiguredContentType(): void
    {
        $this->infoMock->setContentType('application/json');
        self::assertSame('application/json', $this->infoMock->getContentType());
    }

    /** @test */
    public function infoReturnsTotalTimeMs(): void
    {
        $this->infoMock->setTotalTime(0.5);
        self::assertEqualsWithDelta(500.0, $this->infoMock->getTotalTimeMs(), 0.001);
    }

    /** @test */
    public function infoDetectsRedirect(): void
    {
        $this->infoMock->setRedirectCount(2);
        self::assertTrue($this->infoMock->hasRedirect());
        self::assertSame(2, $this->infoMock->getRedirectCount());
    }

    /** @test */
    public function infoNoRedirectByDefault(): void
    {
        self::assertFalse($this->infoMock->hasRedirect());
    }

    /** @test */
    public function infoTimingBreakdownHasAllKeys(): void
    {
        $timing = $this->infoMock->getTimingBreakdown();

        self::assertArrayHasKey('dns_lookup', $timing);
        self::assertArrayHasKey('connect', $timing);
        self::assertArrayHasKey('app_connect', $timing);
        self::assertArrayHasKey('pre_transfer', $timing);
        self::assertArrayHasKey('start_transfer', $timing);
        self::assertArrayHasKey('redirect', $timing);
        self::assertArrayHasKey('total', $timing);
    }

    /** @test */
    public function infoSummaryHasAllKeys(): void
    {
        $summary = $this->infoMock->getSummary();

        foreach ([
            'url',
            'status_code',
            'content_type',
            'total_time',
            'download_size',
            'upload_size',
            'download_speed',
            'upload_speed',
            'redirect_count',
            'primary_ip',
            'primary_port'
        ] as $key) {
            self::assertArrayHasKey($key, $summary);
        }
    }

    /** @test */
    public function infoSizeInfoHasAllKeys(): void
    {
        $sizes = $this->infoMock->getSizeInfo();

        foreach ([
            'header_size',
            'request_size',
            'download_size',
            'upload_size',
            'download_content_length',
            'upload_content_length'
        ] as $key) {
            self::assertArrayHasKey($key, $sizes);
        }
    }

    /** @test */
    public function infoConnectionInfoHasAllKeys(): void
    {
        $conn = $this->infoMock->getConnectionInfo();

        foreach (['primary_ip', 'primary_port', 'local_ip', 'local_port', 'num_connects'] as $key) {
            self::assertArrayHasKey($key, $conn);
        }
    }

    /** @test */
    public function infoDownloadedSizeReflectsSetValue(): void
    {
        $this->infoMock->setDownloadedSize(2048.0);
        self::assertSame(2048.0, $this->infoMock->getDownloadedSize());
    }

    /** @test */
    public function infoFormatBytesUnder1024(): void
    {
        self::assertSame('512 B', MockClientURLLastTransferInformation::formatBytes(512));
    }

    /** @test */
    public function infoFormatBytesKilobyte(): void
    {
        self::assertSame('1 KB', MockClientURLLastTransferInformation::formatBytes(1024));
    }

    /** @test */
    public function infoFormatBytesMegabyte(): void
    {
        self::assertSame('1 MB', MockClientURLLastTransferInformation::formatBytes(1024 * 1024));
    }

    /** @test */
    public function infoGetFormattedDownloadSize(): void
    {
        $this->infoMock->setDownloadedSize(2048.0);
        self::assertSame('2 KB', $this->infoMock->getFormattedDownloadSize());
    }

    /** @test */
    public function infoGetAllContainsHttpCode(): void
    {
        $this->infoMock->setStatusCode(301);
        $all = $this->infoMock->getAll();
        self::assertSame(301, $all['http_code']);
    }

    // ==================================================================== //
    //  GROUP C – MockClientURL instance                                      //
    // ==================================================================== //

    /** @test */
    public function executeReturnsConfiguredBody(): void
    {
        $this->mock->setMockResponse('Hello World', 200);
        self::assertSame('Hello World', $this->mock->execute());
    }

    /** @test */
    public function executeReturnsJsonBody(): void
    {
        $json = '{"id":42,"name":"Alice"}';
        $this->mock->setMockResponse($json, 200, ['Content-Type' => 'application/json']);
        self::assertSame($json, $this->mock->execute());
    }

    /** @test */
    public function executeThrowsOnCurlError(): void
    {
        $this->mock->setMockError(7, 'Could not connect to server');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Could not connect to server/');

        $this->mock->execute();
    }

    /** @test */
    public function executeThrowsOnTimeoutError(): void
    {
        $this->mock->setMockError(28, 'Operation timed out');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Operation timed out/');

        $this->mock->execute();
    }

    /** @test */
    public function getLastHttpCodeReflectsPresetStatus(): void
    {
        $this->mock->setMockResponse('', 404);
        self::assertSame(404, $this->mock->getLastHttpCode());
    }

    /** @test */
    public function isLastRequestSuccessfulReturnsTrueFor200(): void
    {
        $this->mock->setMockResponse('ok', 200);
        self::assertTrue($this->mock->isLastRequestSuccessful());
    }

    /** @test */
    public function isLastRequestSuccessfulReturnsTrueFor201(): void
    {
        $this->mock->setMockResponse('created', 201);
        self::assertTrue($this->mock->isLastRequestSuccessful());
    }

    /** @test */
    public function isLastRequestSuccessfulReturnsFalseFor404(): void
    {
        $this->mock->setMockResponse('not found', 404);
        self::assertFalse($this->mock->isLastRequestSuccessful());
    }

    /** @test */
    public function isLastRequestSuccessfulReturnsFalseFor500(): void
    {
        $this->mock->setMockResponse('error', 500);
        self::assertFalse($this->mock->isLastRequestSuccessful());
    }

    /** @test */
    public function isLastRequestSuccessfulReturnsFalseWhenCurlError(): void
    {
        $this->mock->setMockError(6, 'Could not resolve host');
        self::assertFalse($this->mock->isLastRequestSuccessful());
    }

    /** @test */
    public function getLastErrorMessageReflectsPresetError(): void
    {
        $this->mock->setMockError(28, 'Timeout');
        self::assertSame('Timeout', $this->mock->getLastErrorMessage());
    }

    /** @test */
    public function getLastErrorNumberReflectsPresetCode(): void
    {
        $this->mock->setMockError(6, 'DNS failure');
        self::assertSame(6, $this->mock->getLastErrorNumber());
    }

    /** @test */
    public function wasExecutedReturnsFalseBeforeCall(): void
    {
        self::assertFalse($this->mock->wasExecuted());
    }

    /** @test */
    public function wasExecutedReturnsTrueAfterExecute(): void
    {
        $this->mock->setMockResponse('data');

        try {
            $this->mock->execute();
        } catch (Exception) {
            // ignore
        }

        self::assertTrue($this->mock->wasExecuted());
    }

    /** @test */
    public function executeCallCountTracksMultipleCalls(): void
    {
        $this->mock->setMockResponse('ok');
        $this->mock->execute();
        $this->mock->execute();
        self::assertSame(2, $this->mock->getExecuteCallCount());
    }

    /** @test */
    public function resetClearsExecuteState(): void
    {
        $this->mock->setMockResponse('ok');
        $this->mock->execute();
        $this->mock->reset();

        self::assertFalse($this->mock->wasExecuted());
        self::assertSame(0, $this->mock->getExecuteCallCount());
    }

    /** @test */
    public function responseHeadersAreAccessibleAfterSetup(): void
    {
        $this->mock->setMockResponse('body', 200, [
            'Content-Type' => 'application/json',
            'X-Rate-Limit' => '100',
        ]);

        self::assertSame('application/json', $this->mock->getResponseHeader('Content-Type'));
        self::assertSame('100', $this->mock->getResponseHeader('X-Rate-Limit'));
    }

    /** @test */
    public function responseHeaderReturnsNullForMissingKey(): void
    {
        self::assertNull($this->mock->getResponseHeader('X-Not-Present'));
    }

    // ------------------------------------------------------------------ //
    //  executeDetailed                                                      //
    // ------------------------------------------------------------------ //

    /** @test */
    public function executeDetailedReturnsSuccessForOkResponse(): void
    {
        $this->mock->setMockResponse('{"ok":true}', 200, ['Content-Type' => 'application/json']);
        $result = $this->mock->executeDetailed();

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
        self::assertSame('{"ok":true}', $result['body']);
        self::assertNull($result['error']);
        self::assertSame(0, $result['error_code']);
        self::assertArrayHasKey('total', $result['timing']);
    }

    /** @test */
    public function executeDetailedReturnsFalseSuccessFor500(): void
    {
        $this->mock->setMockResponse('Internal Error', 500);
        $result = $this->mock->executeDetailed();

        self::assertFalse($result['success']);
        self::assertSame(500, $result['status']);
    }

    /** @test */
    public function executeDetailedReturnsFalseSuccessOnCurlError(): void
    {
        $this->mock->setMockError(7, 'Connection refused');
        $result = $this->mock->executeDetailed();

        self::assertFalse($result['success']);
        self::assertSame(7, $result['error_code']);
        self::assertSame('Connection refused', $result['error']);
        self::assertNull($result['body']);
    }

    /** @test */
    public function executeDetailedTimingContainsAllKeys(): void
    {
        $this->mock->setMockResponse('ok');
        $result = $this->mock->executeDetailed();

        foreach (['total', 'dns', 'connect', 'ttfb'] as $key) {
            self::assertArrayHasKey($key, $result['timing']);
        }
    }

    // ------------------------------------------------------------------ //
    //  executeAndDiagnose                                                   //
    // ------------------------------------------------------------------ //

    /** @test */
    public function executeAndDiagnoseSuccessfulRequest(): void
    {
        $this->mock->setMockResponse('OK', 200);
        $result = $this->mock->executeAndDiagnose();

        self::assertTrue($result['success']);
        self::assertSame(200, $result['status']);
        self::assertNull($result['curl_error']);
        self::assertNull($result['http_error']);
        self::assertStringContainsString('successfully', $result['summary']);
    }

    /** @test */
    public function executeAndDiagnoseReportsHttpError(): void
    {
        $this->mock->setMockResponse('Not Found', 404);
        $result = $this->mock->executeAndDiagnose();

        self::assertFalse($result['success']);
        self::assertNotNull($result['http_error']);
        self::assertStringContainsString('error', strtolower($result['summary']));
    }

    /** @test */
    public function executeAndDiagnoseReportsCurlError(): void
    {
        $this->mock->setMockError(6, 'Could not resolve host');
        $result = $this->mock->executeAndDiagnose();

        self::assertFalse($result['success']);
        self::assertNotNull($result['curl_error']);
        self::assertStringContainsString('failed', strtolower($result['summary']));
    }

    // ------------------------------------------------------------------ //
    //  Response queue                                                       //
    // ------------------------------------------------------------------ //

    /** @test */
    public function queuedResponsesAreConsumedInOrder(): void
    {
        $this->mock->queueResponses([
            ['body' => 'first', 'statusCode' => 200],
            ['body' => 'second', 'statusCode' => 201],
            ['body' => 'third', 'statusCode' => 202],
        ]);

        self::assertSame('first', $this->mock->execute());
        self::assertSame('second', $this->mock->execute());
        self::assertSame('third', $this->mock->execute());
    }

    /** @test */
    public function queuedResponseWithErrorThrows(): void
    {
        $this->mock->queueResponses([
            ['body' => '', 'statusCode' => 200, 'errorCode' => 28, 'errorMessage' => 'Timeout'],
        ]);

        $this->expectException(Exception::class);
        $this->mock->execute();
    }

    /** @test */
    public function queuedStatusCodesAreSet(): void
    {
        $this->mock->queueResponses([
            ['body' => 'ok', 'statusCode' => 200],
            ['body' => 'error', 'statusCode' => 503],
        ]);

        $this->mock->execute();
        self::assertSame(200, $this->mock->getLastHttpCode());

        $this->mock->execute();
        self::assertSame(503, $this->mock->getLastHttpCode());
    }

    // ------------------------------------------------------------------ //
    //  Option wiring through the mock                                       //
    // ------------------------------------------------------------------ //

    /** @test */
    public function mockOptionIsAccessibleAndFluentlyChainable(): void
    {
        $result = $this->mock->option
            ->setURL('https://example.com')
            ->setGetMethod(true)
            ->setReturnTransfer(true)
            ->setSSLVerifyPeer(false);

        self::assertSame($this->mock->option, $result);
        self::assertSame('https://example.com', $this->mock->option->getURL());
        self::assertFalse($this->mock->option->isSSLVerifyPeer());
    }

    /** @test */
    public function mockOptionRecordsAuthorizationHeader(): void
    {
        $this->mock->option->setHeader('Authorization', 'Bearer my-token');
        self::assertSame('Bearer my-token', $this->mock->option->getHeader('Authorization'));
    }

    // ==================================================================== //
    //  GROUP D – MockClientURL static methods                               //
    // ==================================================================== //

    /** @test */
    public function staticGetReturnsPresetBody(): void
    {
        MockClientURL::setStaticMockResponse('{"users":[]}', 200);
        $body = MockClientURL::get('https://api.example.com/users');
        self::assertSame('{"users":[]}', $body);
    }

    /** @test */
    public function staticGetThrowsOnCurlError(): void
    {
        MockClientURL::setStaticMockError(6, 'DNS failure');

        $this->expectException(Exception::class);
        MockClientURL::get('https://api.example.com/users');
    }

    /** @test */
    public function staticPostReturnsPresetBody(): void
    {
        MockClientURL::setStaticMockResponse('{"created":true}', 201);
        $body = MockClientURL::post('https://api.example.com/users', ['name' => 'Alice']);
        self::assertSame('{"created":true}', $body);
    }

    /** @test */
    public function staticPutReturnsPresetBody(): void
    {
        MockClientURL::setStaticMockResponse('{"updated":true}', 200);
        $body = MockClientURL::put('https://api.example.com/users/1', ['name' => 'Bob']);
        self::assertSame('{"updated":true}', $body);
    }

    /** @test */
    public function staticPatchReturnsPresetBody(): void
    {
        MockClientURL::setStaticMockResponse('{"patched":true}', 200);
        $body = MockClientURL::patch('https://api.example.com/users/1', ['email' => 'new@example.com']);
        self::assertSame('{"patched":true}', $body);
    }

    /** @test */
    public function staticDeleteReturnsPresetBody(): void
    {
        MockClientURL::setStaticMockResponse('', 204);
        $body = MockClientURL::delete('https://api.example.com/users/1');
        self::assertSame('', $body);
    }

    /** @test */
    public function staticJsonDecodesResponseAsAssociativeArray(): void
    {
        MockClientURL::setStaticMockResponse('{"id":7,"role":"admin"}', 200);
        $result = MockClientURL::json('https://api.example.com/me', 'GET');

        self::assertIsArray($result);
        self::assertSame(7, $result['id']);
        self::assertSame('admin', $result['role']);
    }

    /** @test */
    public function staticJsonDecodesResponseAsObject(): void
    {
        MockClientURL::setStaticMockResponse('{"name":"Alice"}', 200);
        $result = MockClientURL::json('https://api.example.com/me', 'GET', [], [], false);

        self::assertIsObject($result);
        self::assertSame('Alice', $result->name);
    }

    /** @test */
    public function staticJsonReturnsRawStringForNonJson(): void
    {
        MockClientURL::setStaticMockResponse('plain text', 200);
        $result = MockClientURL::json('https://api.example.com/text', 'GET');
        self::assertSame('plain text', $result);
    }

    /** @test */
    public function staticJsonThrowsOnCurlError(): void
    {
        MockClientURL::setStaticMockError(7, 'Connection refused');

        $this->expectException(Exception::class);
        MockClientURL::json('https://api.example.com/data');
    }

    /** @test */
    public function staticPostFormReturnsPresetBody(): void
    {
        MockClientURL::setStaticMockResponse('OK', 200);
        $result = MockClientURL::postForm('https://api.example.com/form', ['field' => 'value']);
        self::assertSame('OK', $result);
    }

    /** @test */
    public function staticWithBearerTokenReturnsPresetBody(): void
    {
        MockClientURL::setStaticMockResponse('{"auth":true}', 200);
        $result = MockClientURL::withBearerToken('https://api.example.com/secure', 'secret-token');
        self::assertSame(true, $result['auth']);
    }

    /** @test */
    public function staticWithBearerTokenThrowsOnCurlError(): void
    {
        MockClientURL::setStaticMockError(6, 'DNS failure');

        $this->expectException(Exception::class);
        MockClientURL::withBearerToken('https://api.example.com/secure', 'token');
    }

    /** @test */
    public function staticWithBasicAuthReturnsPresetBody(): void
    {
        MockClientURL::setStaticMockResponse('{"auth":true}', 200);
        $result = MockClientURL::withBasicAuth(
            'https://api.example.com/secure',
            'admin',
            'password'
        );
        self::assertSame('{"auth":true}', $result);
    }

    /** @test */
    public function staticRequestReturnsStructuredArray(): void
    {
        MockClientURL::setStaticMockResponse(
            '{"data":"value"}',
            200,
            ['Content-Type' => 'application/json']
        );

        $result = MockClientURL::request('https://api.example.com/data', 'GET');

        self::assertSame(200, $result['status']);
        self::assertSame('{"data":"value"}', $result['body']);
        self::assertArrayHasKey('headers', $result);
        self::assertArrayHasKey('info', $result);
    }

    /** @test */
    public function staticRequestThrowsOnCurlError(): void
    {
        MockClientURL::setStaticMockError(28, 'Timeout');

        $this->expectException(Exception::class);
        MockClientURL::request('https://api.example.com/slow');
    }

    /** @test */
    public function staticHeadReturnsHeadersAndInfo(): void
    {
        MockClientURL::setStaticMockResponse('', 200, ['ETag' => '"abc123"']);

        $result = MockClientURL::head('https://api.example.com/resource');

        self::assertArrayHasKey('headers', $result);
        self::assertArrayHasKey('info', $result);
        self::assertSame('"abc123"', $result['headers']['ETag']);
    }

    /** @test */
    public function staticHeadThrowsOnCurlError(): void
    {
        MockClientURL::setStaticMockError(7, 'Connection refused');

        $this->expectException(Exception::class);
        MockClientURL::head('https://api.example.com/resource');
    }

    /** @test */
    public function staticOptionsReturnsAllowedMethods(): void
    {
        MockClientURL::setStaticMockResponse(
            '',
            200,
            ['Allow' => 'GET, POST, OPTIONS']
        );

        $result = MockClientURL::options('https://api.example.com/users');

        self::assertContains('GET', $result['allowed_methods']);
        self::assertContains('POST', $result['allowed_methods']);
        self::assertContains('OPTIONS', $result['allowed_methods']);
    }

    /** @test */
    public function staticOptionsReturnsEmptyAllowedWhenHeaderAbsent(): void
    {
        MockClientURL::setStaticMockResponse('', 200, []);
        $result = MockClientURL::options('https://api.example.com/users');
        self::assertEmpty($result['allowed_methods']);
    }

    /** @test */
    public function staticOptionsThrowsOnCurlError(): void
    {
        MockClientURL::setStaticMockError(6, 'DNS failure');

        $this->expectException(Exception::class);
        MockClientURL::options('https://api.example.com/users');
    }

    /** @test */
    public function clearStaticMockRemovesPresetResponse(): void
    {
        MockClientURL::setStaticMockResponse('preset', 200);
        MockClientURL::clearStaticMock();

        // After clearing, static GET returns the instance default (empty)
        $body = MockClientURL::get('https://example.com');
        self::assertSame('', $body);
    }

    // ==================================================================== //
    //  GROUP E – ClientURLErrorResponse (pure-logic, no cURL)               //
    // ==================================================================== //

    /** @test */
    public function errorResponseReturnsKnownCurlMessage(): void
    {
        $msg = $this->errorResponse->getErrorMessageFromCode(7);
        self::assertSame('CURLE_COULDNT_CONNECT', $msg);
    }

    /** @test */
    public function errorResponseReturnsNullForUnknownCode(): void
    {
        $msg = $this->errorResponse->getErrorMessageFromCode(999);
        self::assertNull($msg);
    }

    /** @test */
    public function errorResponseReturnsHttpStatusMessage(): void
    {
        self::assertSame('OK', $this->errorResponse->getHTTPStatusMessage(200));
        self::assertSame('Not Found', $this->errorResponse->getHTTPStatusMessage(404));
        self::assertSame('Internal Server Error', $this->errorResponse->getHTTPStatusMessage(500));
    }

    /** @test */
    public function errorResponseReturnsNullForUnknownHttpCode(): void
    {
        self::assertNull($this->errorResponse->getHTTPStatusMessage(999));
    }

    /** @test */
    public function errorResponseDetectsRecoverableError(): void
    {
        // Code 28 = CURLE_OPERATION_TIMEDOUT – recoverable
        self::assertTrue($this->errorResponse->isRecoverable(28));
    }

    /** @test */
    public function errorResponseDetectsNonRecoverableError(): void
    {
        // Code 3 = CURLE_URL_MALFORMAT – not recoverable
        self::assertFalse($this->errorResponse->isRecoverable(3));
    }

    /** @test */
    public function errorResponseDetectsSslError(): void
    {
        // Code 35 = CURLE_SSL_CONNECT_ERROR
        self::assertTrue($this->errorResponse->isSSLError(35));
    }

    /** @test */
    public function errorResponseDetectsNonSslError(): void
    {
        self::assertFalse($this->errorResponse->isSSLError(7));
    }

    /** @test */
    public function errorResponseIsHTTPSuccessFor200(): void
    {
        self::assertTrue($this->errorResponse->isHTTPSuccess(200));
        self::assertTrue($this->errorResponse->isHTTPSuccess(204));
    }

    /** @test */
    public function errorResponseIsHTTPSuccessIsFalseFor400(): void
    {
        self::assertFalse($this->errorResponse->isHTTPSuccess(400));
    }

    /** @test */
    public function errorResponseDetectsHTTPClientError(): void
    {
        self::assertTrue($this->errorResponse->isHTTPClientError(404));
        self::assertFalse($this->errorResponse->isHTTPClientError(200));
    }

    /** @test */
    public function errorResponseDetectsHTTPServerError(): void
    {
        self::assertTrue($this->errorResponse->isHTTPServerError(503));
        self::assertFalse($this->errorResponse->isHTTPServerError(200));
    }

    /** @test */
    public function errorResponseIsHTTPRetryableFor429(): void
    {
        self::assertTrue($this->errorResponse->isHTTPRetryable(429));
    }

    /** @test */
    public function errorResponseIsHTTPRetryableFor503(): void
    {
        self::assertTrue($this->errorResponse->isHTTPRetryable(503));
    }

    /** @test */
    public function errorResponseIsNotRetryableFor200(): void
    {
        self::assertFalse($this->errorResponse->isHTTPRetryable(200));
    }

    /** @test */
    public function errorResponseIsNotRetryableFor404(): void
    {
        self::assertFalse($this->errorResponse->isHTTPRetryable(404));
    }

    /** @test */
    public function errorResponseGetHTTPRetryAfterForNonRetryableIsZero(): void
    {
        self::assertSame(0, $this->errorResponse->getHTTPRetryAfter(200));
    }

    /** @test */
    public function errorResponseGetHTTPRetryAfterIncreasesWithAttempt(): void
    {
        $first = $this->errorResponse->getHTTPRetryAfter(503, 1);
        $second = $this->errorResponse->getHTTPRetryAfter(503, 2);
        self::assertGreaterThan($first, $second);
    }

    /** @test */
    public function errorResponseGetHTTPErrorDetailsContainsAllKeys(): void
    {
        $details = $this->errorResponse->getHTTPErrorDetails(404);

        foreach (['code', 'message', 'category', 'retryable', 'retry_after', 'is_error'] as $key) {
            self::assertArrayHasKey($key, $details);
        }

        self::assertSame(404, $details['code']);
        self::assertTrue($details['is_error']);
    }

    /** @test */
    public function errorResponseGetHTTPStatusCategoryForClientError(): void
    {
        $cat = $this->errorResponse->getHTTPStatusCategory(404);
        self::assertSame('client_error', $cat);
    }

    /** @test */
    public function errorResponseGetHTTPStatusCategoryForServerError(): void
    {
        $cat = $this->errorResponse->getHTTPStatusCategory(500);
        self::assertSame('server_error', $cat);
    }

    /** @test */
    public function errorResponseGetHTTPStatusCategoryForSuccess(): void
    {
        $cat = $this->errorResponse->getHTTPStatusCategory(200);
        self::assertSame('success', $cat);
    }

    /** @test */
    public function errorResponseGetHTTPStatusCategoryForRedirect(): void
    {
        $cat = $this->errorResponse->getHTTPStatusCategory(301);
        self::assertSame('redirect', $cat);
    }

    /** @test */
    public function errorResponseGetHTTPStatusCategoryForInformational(): void
    {
        $cat = $this->errorResponse->getHTTPStatusCategory(100);
        self::assertSame('informational', $cat);
    }

    /** @test */
    public function errorResponseIsKnownHTTPStatus(): void
    {
        self::assertTrue($this->errorResponse->isKnownHTTPStatus(200));
        self::assertFalse($this->errorResponse->isKnownHTTPStatus(999));
    }

    /** @test */
    public function errorResponseIsKnownCurlError(): void
    {
        self::assertTrue($this->errorResponse->isKnownCurlError(6));
        self::assertFalse($this->errorResponse->isKnownCurlError(999));
    }

    /** @test */
    public function errorResponseGetHumanReadableErrorForTimeout(): void
    {
        $msg = $this->errorResponse->getHumanReadableError(28);
        self::assertStringContainsStringIgnoringCase('timed out', $msg);
    }

    /** @test */
    public function errorResponseGetHumanReadableErrorForUnknownCode(): void
    {
        $msg = $this->errorResponse->getHumanReadableError(999);
        self::assertStringContainsString('999', $msg);
    }

    /** @test */
    public function errorResponseGetDiagnosticReportOnSuccess(): void
    {
        $report = $this->errorResponse->getDiagnosticReport(0, 200);

        self::assertTrue($report['overall_success']);
        self::assertFalse($report['has_curl_error']);
        self::assertFalse($report['has_http_error']);
        self::assertNull($report['curl_error']);
        self::assertStringContainsString('successfully', $report['summary']);
    }

    /** @test */
    public function errorResponseGetDiagnosticReportOnHttpError(): void
    {
        $report = $this->errorResponse->getDiagnosticReport(0, 500);

        self::assertFalse($report['overall_success']);
        self::assertTrue($report['has_http_error']);
        self::assertFalse($report['has_curl_error']);
        self::assertStringContainsString('error', strtolower($report['summary']));
    }

    /** @test */
    public function errorResponseGetDiagnosticReportOnCurlError(): void
    {
        $report = $this->errorResponse->getDiagnosticReport(6, 0);

        self::assertFalse($report['overall_success']);
        self::assertTrue($report['has_curl_error']);
        self::assertNotNull($report['curl_error']);
        self::assertStringContainsString('failed', strtolower($report['summary']));
    }

    /** @test */
    public function errorResponseGetAllHTTPStatusCodesIsNonEmpty(): void
    {
        $codes = $this->errorResponse->getAllHTTPStatusCodes();
        self::assertContains(200, $codes);
        self::assertContains(404, $codes);
        self::assertContains(500, $codes);
    }

    /** @test */
    public function errorResponseGetHTTPStatusByCategoryFiltersCorrectly(): void
    {
        $clientErrors = $this->errorResponse->getHTTPStatusByCategory('client_error');

        foreach (array_keys($clientErrors) as $code) {
            self::assertGreaterThanOrEqual(400, $code);
            self::assertLessThan(500, $code);
        }
    }

    /** @test */
    public function errorResponseSuggestExceptionClassFor404(): void
    {
        self::assertSame(
            'NotFoundException',
            $this->errorResponse->suggestExceptionClass(404)
        );
    }

    /** @test */
    public function errorResponseSuggestExceptionClassFor429(): void
    {
        self::assertSame(
            'TooManyRequestsException',
            $this->errorResponse->suggestExceptionClass(429)
        );
    }

    /** @test */
    public function errorResponseSuggestExceptionClassFor500(): void
    {
        self::assertSame(
            'InternalServerErrorException',
            $this->errorResponse->suggestExceptionClass(500)
        );
    }

    /** @test */
    public function errorResponseGetCurlErrorsByCategoryReturnsSslErrors(): void
    {
        $sslErrors = $this->errorResponse->getCurlErrorsByCategory('ssl');
        self::assertNotEmpty($sslErrors);

        // Code 35 (CURLE_SSL_CONNECT_ERROR) must be in the SSL category
        self::assertArrayHasKey(35, $sslErrors);
    }

    // ==================================================================== //
    //  GROUP F – Integration / scenario tests                               //
    // ==================================================================== //

    /**
     * @test
     * Simulate a client that retries on 503 and eventually succeeds.
     */
    public function retryScenarioEventuallySucceeds(): void
    {
        $this->mock->queueResponses([
            ['body' => '', 'statusCode' => 503],
            ['body' => '', 'statusCode' => 503],
            ['body' => 'data', 'statusCode' => 200],
        ]);

        $maxAttempts = 3;
        $response = null;
        $lastStatus = 0;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $response = $this->mock->execute();
            $lastStatus = $this->mock->getLastHttpCode();

            if ($lastStatus >= 200 && $lastStatus < 300) {
                break;
            }
        }

        self::assertSame('data', $response);
        self::assertSame(200, $lastStatus);
        self::assertSame(3, $this->mock->getExecuteCallCount());
    }

    /**
     * @test
     * Simulate a paginated API: three pages of results.
     */
    public function paginationScenarioFetchesAllPages(): void
    {
        $this->mock->queueResponses([
            ['body' => '{"data":[1,2],"next":true}', 'statusCode' => 200],
            ['body' => '{"data":[3,4],"next":true}', 'statusCode' => 200],
            ['body' => '{"data":[5,6],"next":false}', 'statusCode' => 200],
        ]);

        $allItems = [];
        $page = 1;

        do {
            $rawBody = $this->mock->execute();
            $decoded = json_decode($rawBody, true);
            $allItems = array_merge($allItems, $decoded['data']);
            $hasNext = $decoded['next'];
            $page++;
        } while ($hasNext);

        self::assertSame([1, 2, 3, 4, 5, 6], $allItems);
        self::assertSame(3, $this->mock->getExecuteCallCount());
    }

    /**
     * @test
     * Bearer token is included in the Authorization header.
     */
    public function bearerTokenIsSetInHeader(): void
    {
        $this->mock->option->setHeader('Authorization', 'Bearer my-secret');
        $this->mock->setMockResponse('{"user":"admin"}', 200);
        $this->mock->execute();

        self::assertSame('Bearer my-secret', $this->mock->option->getHeader('Authorization'));
    }

    /**
     * @test
     * POST with JSON body: content-type and body are recorded correctly.
     */
    public function postJsonBodyIsRecorded(): void
    {
        $body = json_encode(['name' => 'Alice', 'role' => 'admin']);

        $this->mock->option
            ->setPostMethod(true)
            ->setPostField($body)
            ->setContentTypeApplicationJson()
            ->setReturnTransfer(true);

        $this->mock->setMockResponse('{"id":1}', 201);
        $response = $this->mock->execute();

        self::assertSame('{"id":1}', $response);
        self::assertSame(201, $this->mock->getLastHttpCode());
        self::assertSame($body, $this->mock->option->getPostField());
        self::assertSame('application/json', $this->mock->option->getHeader('Content-Type'));
    }

    /**
     * @test
     * Recoverable cURL error classification drives a retry decision.
     */
    public function recoverableErrorTriggersRetry(): void
    {
        $errorCode = 28; // CURLE_OPERATION_TIMEDOUT
        $isRetryable = $this->errorResponse->isRecoverable($errorCode);

        $this->mock->queueResponses([
            ['body' => '', 'statusCode' => 0, 'errorCode' => 28, 'errorMessage' => 'Timeout'],
            ['body' => 'done', 'statusCode' => 200],
        ]);

        $response = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = $this->mock->execute();
                break;
            } catch (Exception $e) {
                if (!$isRetryable || $attempt === 3) {
                    throw $e;
                }
            }
        }

        self::assertSame('done', $response);
    }

    /**
     * @test
     * Non-recoverable error does not prompt retry.
     */
    public function nonRecoverableErrorIsNotRetried(): void
    {
        $errorCode = 3; // CURLE_URL_MALFORMAT
        $isRetryable = $this->errorResponse->isRecoverable($errorCode);

        self::assertFalse($isRetryable);

        $this->mock->setMockError($errorCode, 'URL malformed');

        $this->expectException(Exception::class);
        $this->mock->execute();
    }

    /**
     * @test
     * 429 response triggers expected retry delay calculation.
     */
    public function tooManyRequestsResponseHasExpectedRetryDelay(): void
    {
        $this->mock->setMockResponse('{"error":"rate_limit"}', 429);

        $statusCode = $this->mock->getLastHttpCode();
        $delay = $this->errorResponse->getHTTPRetryAfter($statusCode, 1);

        self::assertSame(429, $statusCode);
        self::assertGreaterThan(0, $delay);
    }

    /**
     * @test
     * Complete flow: set options → execute → inspect information.
     */
    public function fullRequestLifecycle(): void
    {
        $url = 'https://api.example.com/products';

        $this->mock->option
            ->setURL($url)
            ->setGetMethod(true)
            ->setSSLVerifyPeer(false)
            ->setFollowRedirects(true)
            ->setReturnTransfer(true)
            ->setHeader('Accept', 'application/json')
            ->setTimeout(15);

        $this->mock->setMockResponse(
            '[{"id":1},{"id":2}]',
            200,
            ['Content-Type' => 'application/json'],
            0.08
        );

        $body = $this->mock->execute();

        // Response assertions
        self::assertSame('[{"id":1},{"id":2}]', $body);
        self::assertSame(200, $this->mock->getLastHttpCode());
        self::assertTrue($this->mock->isLastRequestSuccessful());

        // Option assertions
        self::assertSame($url, $this->mock->option->getURL());
        self::assertSame('GET', $this->mock->option->getMethod());
        self::assertFalse($this->mock->option->isSSLVerifyPeer());
        self::assertTrue($this->mock->option->isFollowRedirects());
        self::assertSame(15, $this->mock->option->getTimeout());
        self::assertSame('application/json', $this->mock->option->getHeader('Accept'));

        // Transfer information assertions
        self::assertTrue($this->mock->information->isSuccessful());
        self::assertEqualsWithDelta(0.08, $this->mock->information->getTotalTransferTime(), 0.001);
        self::assertSame('application/json', $this->mock->information->getContentType());
        self::assertSame($url, $this->mock->information->getEffectiveURL());
    }
}
