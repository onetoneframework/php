<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security;

use Clover\Classes\Security\Auth\AuthenticationManager;
use Clover\Classes\Security\Guard\RBACManager;
use Clover\Classes\Security\Guard\AuthorizationGuard;

/**
 * Security Auditor - Logs and analyzes security events
 */
class SecurityAuditor
{
    private $logger;
    private $events = [];
    private $suspiciousActivities = [];
    private $failedAttempts = [];
    private $rateLimitViolations = [];

    public function __construct($logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Log an authentication attempt
     */
    public function logAuthenticationAttempt(string $username, string $ip, bool $success, string $method = 'password'): void
    {
        $event = [
            'type' => 'authentication_attempt',
            'username' => $username,
            'ip' => $ip,
            'success' => $success,
            'method' => $method,
            'timestamp' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];

        $this->events[] = $event;
        $this->logEvent($event);

        if (!$success) {
            $this->trackFailedAttempt($ip, $username);
        }
    }

    /**
     * Log an authorization attempt
     */
    public function logAuthorizationAttempt(string $userId, string $permission, bool $success, ?string $resource = null): void
    {
        $event = [
            'type' => 'authorization_attempt',
            'user_id' => $userId,
            'permission' => $permission,
            'resource' => $resource,
            'success' => $success,
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        $this->events[] = $event;
        $this->logEvent($event);

        if (!$success) {
            $this->trackSuspiciousActivity($userId, 'unauthorized_access_attempt', $event);
        }
    }

    /**
     * Log a rate limit violation
     */
    public function logRateLimitViolation(string $ip, string $endpoint, int $attempts, int $limit): void
    {
        $event = [
            'type' => 'rate_limit_violation',
            'ip' => $ip,
            'endpoint' => $endpoint,
            'attempts' => $attempts,
            'limit' => $limit,
            'timestamp' => time()
        ];

        $this->events[] = $event;
        $this->logEvent($event);
        $this->rateLimitViolations[] = $event;
    }

    /**
     * Log a CSRF token violation
     */
    public function logCSRFViolation(string $ip, string $endpoint, ?string $token = null): void
    {
        $event = [
            'type' => 'csrf_violation',
            'ip' => $ip,
            'endpoint' => $endpoint,
            'token' => $token,
            'timestamp' => time()
        ];

        $this->events[] = $event;
        $this->logEvent($event);
        $this->trackSuspiciousActivity($ip, 'csrf_attack', $event);
    }

    /**
     * Log a SQL injection attempt
     */
    public function logSQLInjectionAttempt(string $ip, string $query, string $endpoint): void
    {
        $event = [
            'type' => 'sql_injection_attempt',
            'ip' => $ip,
            'query' => $query,
            'endpoint' => $endpoint,
            'timestamp' => time()
        ];

        $this->events[] = $event;
        $this->logEvent($event);
        $this->trackSuspiciousActivity($ip, 'sql_injection_attempt', $event);
    }

    /**
     * Log an XSS attempt
     */
    public function logXSSAttempt(string $ip, string $payload, string $endpoint): void
    {
        $event = [
            'type' => 'xss_attempt',
            'ip' => $ip,
            'payload' => $payload,
            'endpoint' => $endpoint,
            'timestamp' => time()
        ];

        $this->events[] = $event;
        $this->logEvent($event);
        $this->trackSuspiciousActivity($ip, 'xss_attempt', $event);
    }

    /**
     * Log a file upload attempt
     */
    public function logFileUploadAttempt(string $userId, string $filename, string $mimeType, bool $success, ?string $reason = null): void
    {
        $event = [
            'type' => 'file_upload_attempt',
            'user_id' => $userId,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'success' => $success,
            'reason' => $reason,
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        $this->events[] = $event;
        $this->logEvent($event);

        if (!$success) {
            $this->trackSuspiciousActivity($userId, 'malicious_file_upload', $event);
        }
    }

    /**
     * Log a session hijacking attempt
     */
    public function logSessionHijackingAttempt(string $userId, string $oldIp, string $newIp, string $userAgent): void
    {
        $event = [
            'type' => 'session_hijacking_attempt',
            'user_id' => $userId,
            'old_ip' => $oldIp,
            'new_ip' => $newIp,
            'user_agent' => $userAgent,
            'timestamp' => time()
        ];

        $this->events[] = $event;
        $this->logEvent($event);
        $this->trackSuspiciousActivity($userId, 'session_hijacking', $event);
    }

    /**
     * Track failed attempts
     */
    private function trackFailedAttempt(string $ip, string $username): void
    {
        $key = $ip . ':' . $username;
        
        if (!isset($this->failedAttempts[$key])) {
            $this->failedAttempts[$key] = [
                'count' => 0,
                'first_attempt' => time(),
                'last_attempt' => time()
            ];
        }

        $this->failedAttempts[$key]['count']++;
        $this->failedAttempts[$key]['last_attempt'] = time();

        
        if ($this->failedAttempts[$key]['count'] >= 5) {
            $this->trackSuspiciousActivity($ip, 'multiple_failed_attempts', [
                'attempts' => $this->failedAttempts[$key]['count'],
                'username' => $username
            ]);
        }
    }

    /**
     * Track suspicious activity
     */
    private function trackSuspiciousActivity(string $identifier, string $activity, array $details = []): void
    {
        $activity = [
            'identifier' => $identifier,
            'activity' => $activity,
            'details' => $details,
            'timestamp' => time(),
            'severity' => $this->getActivitySeverity($activity)
        ];

        $this->suspiciousActivities[] = $activity;
        $this->logEvent($activity, 'WARNING');
    }

    /**
     * Determine activity severity
     */
    private function getActivitySeverity(string $activity): string
    {
        $highSeverity = [
            'sql_injection_attempt',
            'xss_attempt',
            'session_hijacking',
            'malicious_file_upload'
        ];

        $mediumSeverity = [
            'unauthorized_access_attempt',
            'multiple_failed_attempts',
            'csrf_attack'
        ];

        if (in_array($activity, $highSeverity)) {
            return 'HIGH';
        } elseif (in_array($activity, $mediumSeverity)) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    /**
     * Log an event via configured logger
     */
    private function logEvent(array $event, string $level = 'INFO'): void
    {
        if ($this->logger) {
            $message = sprintf(
                '[%s] %s: %s',
                $level,
                $event['type'] ?? 'unknown',
                json_encode($event)
            );
            
            $this->logger->log($level, $message);
        }
    }

    /**
     * Generate a security report
     */
    public function generateSecurityReport(int $hours = 24): array
    {
        $cutoff = time() - ($hours * 3600);
        $recentEvents = array_filter($this->events, fn($event) => $event['timestamp'] >= $cutoff);
        $recentSuspicious = array_filter($this->suspiciousActivities, fn($activity) => $activity['timestamp'] >= $cutoff);

        return [
            'period' => $hours . ' hours',
            'total_events' => count($recentEvents),
            'suspicious_activities' => count($recentSuspicious),
            'failed_attempts' => count($this->failedAttempts),
            'rate_limit_violations' => count($this->rateLimitViolations),
            'events_by_type' => $this->groupEventsByType($recentEvents),
            'top_ips' => $this->getTopIPs($recentEvents),
            'security_score' => $this->calculateSecurityScore($recentEvents, $recentSuspicious),
            'recommendations' => $this->generateRecommendations($recentEvents, $recentSuspicious)
        ];
    }

    /**
     * Group events by type
     */
    private function groupEventsByType(array $events): array
    {
        $grouped = [];
        foreach ($events as $event) {
            $type = $event['type'] ?? 'unknown';
            $grouped[$type] = ($grouped[$type] ?? 0) + 1;
        }
        return $grouped;
    }

    /**
     * Get top IP addresses by event count
     */
    private function getTopIPs(array $events, int $limit = 10): array
    {
        $ips = [];
        foreach ($events as $event) {
            $ip = $event['ip'] ?? 'unknown';
            $ips[$ip] = ($ips[$ip] ?? 0) + 1;
        }
        
        arsort($ips);
        return array_slice($ips, 0, $limit, true);
    }

    /**
     * Calculate security score
     */
    private function calculateSecurityScore(array $events, array $suspicious): int
    {
        $score = 100;
        
        
        foreach ($suspicious as $activity) {
            switch ($activity['severity']) {
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
        
        
        $failedAuth = array_filter($events, fn($event) => $event['type'] === 'authentication_attempt' && !$event['success']);
        $score -= count($failedAuth) * 2;
        
        return max(0, $score);
    }

    /**
     * Generate security recommendations
     */
    private function generateRecommendations(array $events, array $suspicious): array
    {
        $recommendations = [];
        
        
        $highSeverity = array_filter($suspicious, fn($activity) => $activity['severity'] === 'HIGH');
        if (!empty($highSeverity)) {
            $recommendations[] = 'High severity security incidents detected. Immediate investigation required.';
        }
        
        
        $failedAuth = array_filter($events, fn($event) => $event['type'] === 'authentication_attempt' && !$event['success']);
        if (count($failedAuth) > 10) {
            $recommendations[] = 'High number of failed authentication attempts. Consider implementing account lockout.';
        }
        
        
        if (count($this->rateLimitViolations) > 5) {
            $recommendations[] = 'Frequent rate limit violations. Consider adjusting rate limits or implementing additional protection.';
        }
        
        
        $csrfAttacks = array_filter($events, fn($event) => $event['type'] === 'csrf_violation');
        if (!empty($csrfAttacks)) {
            $recommendations[] = 'CSRF attacks detected. Ensure CSRF protection is properly configured.';
        }
        
        return $recommendations;
    }

    /**
     * Get blocked IP recommendations
     */
    public function getBlockedIPRecommendations(): array
    {
        $recommendations = [];
        
        foreach ($this->failedAttempts as $key => $attempts) {
            if ($attempts['count'] >= 10) {
                list($ip, $username) = explode(':', $key, 2);
                $recommendations[] = [
                    'ip' => $ip,
                    'username' => $username,
                    'attempts' => $attempts['count'],
                    'reason' => 'Multiple failed authentication attempts'
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Return all recorded events
     */
    public function getAllEvents(): array
    {
        return $this->events;
    }

    /**
     * Return suspicious activities
     */
    public function getSuspiciousActivities(): array
    {
        return $this->suspiciousActivities;
    }

    /**
     * Return failed attempts
     */
    public function getFailedAttempts(): array
    {
        return $this->failedAttempts;
    }

    /**
     * Clear all recorded events
     */
    public function clearEvents(): void
    {
        $this->events = [];
        $this->suspiciousActivities = [];
        $this->failedAttempts = [];
        $this->rateLimitViolations = [];
    }
}

