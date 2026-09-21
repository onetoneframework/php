<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security;

use Clover\Classes\ClientURL;

/**
 * Security Tester - Security vulnerability testing tools
 */
class SecurityTester
{
    private $targetUrl;
    private $results = [];
    private $vulnerabilities = [];

    public function __construct(string $targetUrl)
    {
        $this->targetUrl = rtrim($targetUrl, '/');
    }

    /**
     * Run full security test suite
     */
    public function runFullSecurityTest(): array
    {
        $this->results = [];
        $this->vulnerabilities = [];

        
        $this->testSQLInjection();
        
        
        $this->testXSS();
        
        
        $this->testCSRF();
        
        
        $this->testAuthenticationBypass();
        
        
        $this->testPrivilegeEscalation();
        
        
        $this->testFileUpload();
        
        
        $this->testDirectoryTraversal();
        
        
        $this->testInformationDisclosure();
        
        
        $this->testSessionSecurity();
        
        
        $this->testRateLimiting();

        return $this->generateReport();
    }

    /**
     * SQL injection tests
     */
    private function testSQLInjection(): void
    {
        $payloads = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "' UNION SELECT * FROM users --",
            "1' OR 1=1 --",
            "admin'--",
            "' OR 1=1#",
            "1' AND '1'='1",
            "' OR 'x'='x"
        ];

        $endpoints = [
            '/login',
            '/search',
            '/api/users',
            '/api/products'
        ];

        foreach ($endpoints as $endpoint) {
            foreach ($payloads as $payload) {
                $result = $this->sendRequest($endpoint, 'POST', [
                    'username' => $payload,
                    'password' => $payload,
                    'search' => $payload,
                    'id' => $payload
                ]);

                if ($this->isSQLInjectionVulnerable($result)) {
                    $this->vulnerabilities[] = [
                        'type' => 'SQL Injection',
                        'endpoint' => $endpoint,
                        'payload' => $payload,
                        'severity' => 'HIGH',
                        'description' => 'SQL injection vulnerability detected'
                    ];
                }
            }
        }
    }

    /**
     * XSS tests
     */
    private function testXSS(): void
    {
        $payloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            '<body onload=alert("XSS")>',
            '<input onfocus=alert("XSS") autofocus>',
            '<select onfocus=alert("XSS") autofocus>'
        ];

        $endpoints = [
            '/comment',
            '/profile',
            '/search',
            '/api/feedback'
        ];

        foreach ($endpoints as $endpoint) {
            foreach ($payloads as $payload) {
                $result = $this->sendRequest($endpoint, 'POST', [
                    'comment' => $payload,
                    'name' => $payload,
                    'search' => $payload,
                    'description' => $payload
                ]);

                if ($this->isXSSVulnerable($result, $payload)) {
                    $this->vulnerabilities[] = [
                        'type' => 'Cross-Site Scripting (XSS)',
                        'endpoint' => $endpoint,
                        'payload' => $payload,
                        'severity' => 'MEDIUM',
                        'description' => 'XSS vulnerability detected'
                    ];
                }
            }
        }
    }

    /**
     * CSRF tests
     */
    private function testCSRF(): void
    {
        $endpoints = [
            '/api/users/delete',
            '/api/profile/update',
            '/api/settings/change'
        ];

        foreach ($endpoints as $endpoint) {
            
            $result = $this->sendRequest($endpoint, 'POST', [
                'action' => 'delete',
                'id' => '1'
            ], false);

            if ($this->isCSRFVulnerable($result)) {
                $this->vulnerabilities[] = [
                    'type' => 'Cross-Site Request Forgery (CSRF)',
                    'endpoint' => $endpoint,
                    'severity' => 'MEDIUM',
                    'description' => 'CSRF protection missing or insufficient'
                ];
            }
        }
    }

    /**
     * Authentication bypass tests
     */
    private function testAuthenticationBypass(): void
    {
        $bypassAttempts = [
            ['username' => 'admin', 'password' => ''],
            ['username' => '', 'password' => 'admin'],
            ['username' => 'admin', 'password' => 'admin'],
            ['username' => 'administrator', 'password' => 'password'],
            ['username' => 'root', 'password' => 'root'],
            ['username' => 'admin', 'password' => '123456'],
            ['username' => 'admin', 'password' => 'password']
        ];

        foreach ($bypassAttempts as $attempt) {
            $result = $this->sendRequest('/login', 'POST', $attempt);

            if ($this->isAuthenticationBypassed($result)) {
                $this->vulnerabilities[] = [
                    'type' => 'Authentication Bypass',
                    'credentials' => $attempt,
                    'severity' => 'HIGH',
                    'description' => 'Weak authentication credentials detected'
                ];
            }
        }
    }

    /**
     * Privilege escalation tests
     */
    private function testPrivilegeEscalation(): void
    {
        $adminEndpoints = [
            '/admin/users',
            '/admin/settings',
            '/api/admin/users',
            '/api/admin/config'
        ];

        
        foreach ($adminEndpoints as $endpoint) {
            $result = $this->sendRequest($endpoint, 'GET', [], true, 'user');

            if ($this->isPrivilegeEscalated($result)) {
                $this->vulnerabilities[] = [
                    'type' => 'Privilege Escalation',
                    'endpoint' => $endpoint,
                    'severity' => 'HIGH',
                    'description' => 'Unauthorized access to admin endpoints'
                ];
            }
        }
    }

    /**
     * File upload tests
     */
    private function testFileUpload(): void
    {
        $maliciousFiles = [
            'test.php' => '<?php echo "Hacked"; ?>',
            'test.jsp' => '<% out.println("Hacked"); %>',
            'test.asp' => '<% Response.Write("Hacked") %>',
            'test.exe' => 'MZ',
            'test.sh' => '#!/bin/bash\necho "Hacked"'
        ];

        foreach ($maliciousFiles as $filename => $content) {
            $result = $this->uploadFile('/upload', $filename, $content);

            if ($this->isFileUploadVulnerable($result)) {
                $this->vulnerabilities[] = [
                    'type' => 'Malicious File Upload',
                    'filename' => $filename,
                    'severity' => 'HIGH',
                    'description' => 'Malicious file upload allowed'
                ];
            }
        }
    }

    /**
     * Directory traversal tests
     */
    private function testDirectoryTraversal(): void
    {
        $payloads = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\drivers\\etc\\hosts',
            '....//....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
            '..%252f..%252f..%252fetc%252fpasswd'
        ];

        $endpoints = [
            '/download',
            '/file',
            '/api/file',
            '/images'
        ];

        foreach ($endpoints as $endpoint) {
            foreach ($payloads as $payload) {
                $result = $this->sendRequest($endpoint . '?file=' . urlencode($payload), 'GET');

                if ($this->isDirectoryTraversalVulnerable($result)) {
                    $this->vulnerabilities[] = [
                        'type' => 'Directory Traversal',
                        'endpoint' => $endpoint,
                        'payload' => $payload,
                        'severity' => 'HIGH',
                        'description' => 'Directory traversal vulnerability detected'
                    ];
                }
            }
        }
    }

    /**
     * Information disclosure tests
     */
    private function testInformationDisclosure(): void
    {
        $sensitivePaths = [
            '/.env',
            '/config.php',
            '/database.yml',
            '/.git/config',
            '/backup.sql',
            '/error.log',
            '/debug.log',
            '/phpinfo.php',
            '/info.php',
            '/test.php'
        ];

        foreach ($sensitivePaths as $path) {
            $result = $this->sendRequest($path, 'GET');

            if ($this->isInformationDisclosed($result)) {
                $this->vulnerabilities[] = [
                    'type' => 'Information Disclosure',
                    'path' => $path,
                    'severity' => 'MEDIUM',
                    'description' => 'Sensitive information exposed'
                ];
            }
        }
    }

    /**
     * Session security tests
     */
    private function testSessionSecurity(): void
    {
        
        $result = $this->sendRequest('/login', 'POST', [
            'username' => 'test',
            'password' => 'test'
        ]);

        if ($this->isSessionFixed($result)) {
            $this->vulnerabilities[] = [
                'type' => 'Session Fixation',
                'severity' => 'MEDIUM',
                'description' => 'Session ID not regenerated after login'
            ];
        }

        
        if ($this->isSessionCookieInsecure($result)) {
            $this->vulnerabilities[] = [
                'type' => 'Insecure Session Cookie',
                'severity' => 'MEDIUM',
                'description' => 'Session cookie not marked as secure or httponly'
            ];
        }
    }

    /**
     * Rate limiting tests
     */
    private function testRateLimiting(): void
    {
        $endpoint = '/api/login';
        $requests = 0;
        $blocked = false;

        
        for ($i = 0; $i < 100; $i++) {
            $result = $this->sendRequest($endpoint, 'POST', [
                'username' => 'test',
                'password' => 'test'
            ]);

            $requests++;

            if ($result['status_code'] === 429) {
                $blocked = true;
                break;
            }
        }

        if (!$blocked) {
            $this->vulnerabilities[] = [
                'type' => 'Rate Limiting Bypass',
                'endpoint' => $endpoint,
                'requests' => $requests,
                'severity' => 'LOW',
                'description' => 'Rate limiting not properly implemented'
            ];
        }
    }

    /**
     * Send HTTP request
     */
    private function sendRequest(string $endpoint, string $method = 'GET', array $data = [], bool $authenticated = false, string $role = 'user'): array
    {
        $url = $this->targetUrl . $endpoint;
        $client = new ClientURL($url);
        
        $client->option->setMethod($method);
        
        if ($method === 'POST' && !empty($data)) {
            $client->option->setPostField(http_build_query($data));
        }
        
        if ($authenticated) {
            $client->option->setHeader('Authorization', 'Bearer ' . $this->getTestToken($role));
        }

        $response = $client->execute();
        
        return [
            'status_code' => $client->getHttpCode(),
            'body' => $response,
            'headers' => $client->getResponseHeaders()
        ];
    }

    /**
     * Upload file request
     */
    private function uploadFile(string $endpoint, string $filename, string $content): array
    {
        $url = $this->targetUrl . $endpoint;
        $client = new ClientURL($url);
        
        $client->option->setPostMethod();
        $client->option->setMultipartContentType();
        
        
        $postData = [
            'file' => $content,
            'filename' => $filename
        ];
        
        $client->option->setPostField(http_build_query($postData));
        
        $response = $client->execute();
        
        return [
            'status_code' => $client->getHttpCode(),
            'body' => $response,
            'headers' => $client->getResponseHeaders()
        ];
    }

    /**
     * Create test token
     */
    private function getTestToken(string $role): string
    {
        
        return 'test_token_' . $role;
    }

    /**
     * Detect SQL injection vulnerability
     */
    private function isSQLInjectionVulnerable(array $result): bool
    {
        $errorPatterns = [
            'mysql_fetch_array',
            'mysql_num_rows',
            'mysql_query',
            'ORA-01756',
            'Microsoft OLE DB Provider',
            'SQLServer JDBC Driver',
            'PostgreSQL query failed',
            'Warning: mysql_',
            'valid MySQL result',
            'MySqlClient\.',
            'SQL syntax.*MySQL',
            'Warning.*mysql_.*',
            'valid MySQL result',
            'MySQLSyntaxErrorException',
            'PostgreSQL.*ERROR',
            'Warning.*pg_.*',
            'valid PostgreSQL result',
            'Npgsql\.',
            'Microsoft.*ODBC.*SQL Server',
            'SQLServer JDBC Driver',
            'System\.Data\.SqlClient\.SqlException',
            'Unclosed quotation mark after the character string',
            "'80040e14'",
            'mssql_query()',
            'odbc_exec()',
            'Microsoft OLE DB Provider for SQL Server',
            'Microsoft OLE DB Provider for Oracle',
            'Microsoft OLE DB Provider for ODBC Drivers',
            'SQLServer JDBC Driver',
            'PostgreSQL query failed',
            'Warning.*oci_.*',
            'Warning.*ifx_.*',
            'Exception.*Informix',
            'Informix ODBC Driver',
            'ODBC SQL Server Driver',
            'Oracle error',
            'Oracle.*Driver',
            'Warning.*oci_.*',
            'Warning.*ifx_.*',
            'Exception.*Informix',
            'Informix ODBC Driver',
            'PostgreSQL.*ERROR',
            'Warning.*pg_.*',
            'valid PostgreSQL result',
            'Npgsql\.',
            'SQLSTATE.*SQL Server',
            'SQLServer JDBC Driver',
            'System\.Data\.SqlClient\.SqlException',
            'Unclosed quotation mark after the character string',
            "'80040e14'",
            'mssql_query()',
            'odbc_exec()',
            'Microsoft OLE DB Provider for SQL Server',
            'Microsoft OLE DB Provider for Oracle',
            'Microsoft OLE DB Provider for ODBC Drivers'
        ];

        $body = strtolower($result['body'] ?? '');
        
        foreach ($errorPatterns as $pattern) {
            if (strpos($body, strtolower($pattern)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect XSS vulnerability
     */
    private function isXSSVulnerable(array $result, string $payload): bool
    {
        $body = $result['body'] ?? '';
        
        
        return strpos($body, $payload) !== false;
    }

    /**
     * Detect CSRF vulnerability
     */
    private function isCSRFVulnerable(array $result): bool
    {
        
        return $result['status_code'] !== 403;
    }

    /**
     * Detect authentication bypass
     */
    private function isAuthenticationBypassed(array $result): bool
    {
        
        return $result['status_code'] === 200 && 
               (strpos($result['body'], 'success') !== false || 
                strpos($result['body'], 'welcome') !== false);
    }

    /**
     * Detect privilege escalation
     */
    private function isPrivilegeEscalated(array $result): bool
    {
        
        return $result['status_code'] === 200;
    }

    /**
     * Detect malicious file upload vulnerability
     */
    private function isFileUploadVulnerable(array $result): bool
    {
        
        return $result['status_code'] === 200 && 
               (strpos($result['body'], 'uploaded') !== false || 
                strpos($result['body'], 'success') !== false);
    }

    /**
     * Detect directory traversal vulnerability
     */
    private function isDirectoryTraversalVulnerable(array $result): bool
    {
        $body = $result['body'] ?? '';
        
        
        $systemFiles = [
            'root:x:0:0:',
            'daemon:x:1:1:',
            'bin:x:2:2:',
            '127.0.0.1',
            'localhost',
            'Microsoft Windows'
        ];

        foreach ($systemFiles as $file) {
            if (strpos($body, $file) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect information disclosure
     */
    private function isInformationDisclosed(array $result): bool
    {
        
        if ($result['status_code'] === 200) {
            $body = $result['body'] ?? '';
            
            $sensitivePatterns = [
                'password',
                'secret',
                'api_key',
                'database',
                'mysql',
                'postgresql',
                'mongodb',
                'redis',
                'aws_access_key',
                'private_key'
            ];

            foreach ($sensitivePatterns as $pattern) {
                if (strpos(strtolower($body), $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Detect session fixation
     */
    private function isSessionFixed(array $result): bool
    {
        
        $headers = $result['headers'] ?? [];
        return !isset($headers['Set-Cookie']) || 
               strpos($headers['Set-Cookie'], 'PHPSESSID') === false;
    }

    /**
     * Check session cookie security
     */
    private function isSessionCookieInsecure(array $result): bool
    {
        $headers = $result['headers'] ?? [];
        $setCookie = $headers['Set-Cookie'] ?? '';
        
        
        return strpos($setCookie, 'Secure') === false || 
               strpos($setCookie, 'HttpOnly') === false;
    }

    /**
     * Generate security test report
     */
    private function generateReport(): array
    {
        $severityCounts = [
            'HIGH' => 0,
            'MEDIUM' => 0,
            'LOW' => 0
        ];

        foreach ($this->vulnerabilities as $vulnerability) {
            $severityCounts[$vulnerability['severity']]++;
        }

        return [
            'target_url' => $this->targetUrl,
            'test_date' => date('Y-m-d H:i:s'),
            'total_vulnerabilities' => count($this->vulnerabilities),
            'severity_breakdown' => $severityCounts,
            'vulnerabilities' => $this->vulnerabilities,
            'security_score' => $this->calculateSecurityScore(),
            'recommendations' => $this->generateRecommendations()
        ];
    }

    /**
     * Calculate security score
     */
    private function calculateSecurityScore(): int
    {
        $score = 100;
        
        foreach ($this->vulnerabilities as $vulnerability) {
            switch ($vulnerability['severity']) {
                case 'HIGH':
                    $score -= 20;
                    break;
                case 'MEDIUM':
                    $score -= 10;
                    break;
                case 'LOW':
                    $score -= 5;
                    break;
            }
        }
        
        return max(0, $score);
    }

    /**
     * Generate security recommendations
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];
        
        $vulnerabilityTypes = array_column($this->vulnerabilities, 'type');
        
        if (in_array('SQL Injection', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement parameterized queries and input validation to prevent SQL injection attacks.';
        }
        
        if (in_array('Cross-Site Scripting (XSS)', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement proper output encoding and Content Security Policy (CSP) to prevent XSS attacks.';
        }
        
        if (in_array('Cross-Site Request Forgery (CSRF)', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement CSRF tokens for all state-changing operations.';
        }
        
        if (in_array('Authentication Bypass', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement strong authentication mechanisms and enforce password policies.';
        }
        
        if (in_array('Privilege Escalation', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement proper authorization checks for all sensitive endpoints.';
        }
        
        if (in_array('Malicious File Upload', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement file type validation and virus scanning for uploaded files.';
        }
        
        if (in_array('Directory Traversal', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement proper path validation and sanitization for file operations.';
        }
        
        if (in_array('Information Disclosure', $vulnerabilityTypes)) {
            $recommendations[] = 'Remove or secure sensitive files and implement proper error handling.';
        }
        
        if (in_array('Session Fixation', $vulnerabilityTypes) || in_array('Insecure Session Cookie', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement secure session management with proper cookie flags.';
        }
        
        if (in_array('Rate Limiting Bypass', $vulnerabilityTypes)) {
            $recommendations[] = 'Implement proper rate limiting to prevent abuse and DoS attacks.';
        }
        
        return $recommendations;
    }
}
