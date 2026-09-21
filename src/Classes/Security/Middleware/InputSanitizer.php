<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Middleware;

/**
 * Input data sanitizer
 */
class InputSanitizer
{
    private $filters = [
        'string' => FILTER_SANITIZE_STRING,
        'email' => FILTER_SANITIZE_EMAIL,
        'url' => FILTER_SANITIZE_URL,
        'int' => FILTER_SANITIZE_NUMBER_INT,
        'float' => FILTER_SANITIZE_NUMBER_FLOAT,
        'special_chars' => FILTER_SANITIZE_SPECIAL_CHARS,
        'full_special_chars' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'encoded' => FILTER_SANITIZE_ENCODED,
        'magic_quotes' => FILTER_SANITIZE_MAGIC_QUOTES,
        'add_slashes' => FILTER_SANITIZE_ADD_SLASHES
    ];

    private $htmlPurifier;

    public function __construct()
    {
        
        if (class_exists('HTMLPurifier')) {
            $config = \HTMLPurifier_Config::createDefault();
            $this->htmlPurifier = new \HTMLPurifier($config);
        }
    }

    /**
     * Sanitize data
     */
    public function sanitize($data): mixed
    {
        if (is_array($data)) {
            return $this->sanitizeArray($data);
        } elseif (is_string($data)) {
            return $this->sanitizeString($data);
        } elseif (is_numeric($data)) {
            return $this->sanitizeNumeric($data);
        } else {
            return $data;
        }
    }

    /**
     * Sanitize array
     */
    private function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeString($key);
            $sanitized[$sanitizedKey] = $this->sanitize($value);
        }
        
        return $sanitized;
    }

    /**
     * Sanitize string
     */
    private function sanitizeString(string $data): string
    {
        
        $data = trim($data);
        $data = stripslashes($data);
        
        
        $data = strip_tags($data);
        
        
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        
        $data = $this->preventSqlInjection($data);
        
        
        $data = $this->preventXss($data);
        
        return $data;
    }

    /**
     * Sanitize numeric value
     */
    private function sanitizeNumeric($data): mixed
    {
        if (is_int($data)) {
            return (int) $data;
        } elseif (is_float($data)) {
            return (float) $data;
        } elseif (is_numeric($data)) {
            return is_int($data + 0) ? (int) $data : (float) $data;
        }
        
        return $data;
    }

    /**
     * Prevent SQL injection
     */
    private function preventSqlInjection(string $data): string
    {
        
        $dangerous = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'EXEC', 'EXECUTE', 'UNION', 'SCRIPT', 'SCRIPT>', '<SCRIPT',
            'javascript:', 'vbscript:', 'onload=', 'onerror=', 'onclick='
        ];
        
        foreach ($dangerous as $keyword) {
            $data = str_ireplace($keyword, '', $data);
        }
        
        return $data;
    }

    /**
     * Prevent XSS
     */
    private function preventXss(string $data): string
    {
        
        if ($this->htmlPurifier) {
            return $this->htmlPurifier->purify($data);
        }
        
        
        $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
        $data = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $data);
        $data = preg_replace('/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi', '', $data);
        $data = preg_replace('/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi', '', $data);
        
        return $data;
    }

    /**
     * Sanitize email
     */
    public function sanitizeEmail(string $email): string
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize URL
     */
    public function sanitizeUrl(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize integer
     */
    public function sanitizeInt($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float
     */
    public function sanitizeFloat($value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        
        
        $filename = trim($filename, '.');
        
        return $filename;
    }

    /**
     * Sanitize HTML (uses HTMLPurifier if available)
     */
    public function sanitizeHtml(string $html): string
    {
        if ($this->htmlPurifier) {
            return $this->htmlPurifier->purify($html);
        }
        
        
        return strip_tags($html, '<p><br><strong><em><ul><ol><li><a><img>');
    }

    /**
     * Sanitize JSON
     */
    public function sanitizeJson(string $json): string
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }
        
        $sanitized = $this->sanitize($data);
        
        return json_encode($sanitized, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Apply custom filter
     */
    public function applyFilter($data, int $filter, $options = null): mixed
    {
        return filter_var($data, $filter, $options);
    }

    /**
     * Apply multiple filters
     */
    public function applyFilters($data, array $filters): mixed
    {
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                $data = $this->applyFilter($data, $filter[0], $filter[1] ?? null);
            } else {
                $data = $this->applyFilter($data, $filter);
            }
        }
        
        return $data;
    }

    /**
     * Set sanitization rules
     */
    public function setSanitizationRules(array $rules): self
    {
        foreach ($rules as $field => $rule) {
            $this->rules[$field] = $rule;
        }
        
        return $this;
    }

    /**
     * Validate sanitized data
     */
    public function validateSanitizedData(array $data, array $rules): ValidationResult
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field][] = "Field '{$field}' is required";
            }
            
            if (!empty($value)) {
                if (isset($rule['type'])) {
                    if (!$this->validateType($value, $rule['type'])) {
                        $errors[$field][] = "Field '{$field}' must be of type {$rule['type']}";
                    }
                }
                
                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[$field][] = "Field '{$field}' must be at least {$rule['min_length']} characters";
                }
                
                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field][] = "Field '{$field}' must be no more than {$rule['max_length']} characters";
                }
                
                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $errors[$field][] = "Field '{$field}' format is invalid";
                }
            }
        }
        
        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Validate data type
     */
    private function validateType($value, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'int':
                return is_int($value) || (is_string($value) && is_numeric($value));
            case 'float':
                return is_float($value) || (is_string($value) && is_numeric($value));
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'boolean':
                return is_bool($value) || in_array($value, ['0', '1', 'true', 'false', 'yes', 'no']);
            default:
                return true;
        }
    }
}

