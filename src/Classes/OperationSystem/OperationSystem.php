<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use Clover\Enumeration\TimeZone;
use Exception;
use function defined;
use function is_string;
use function in_array;
use function intval;
use function array_key_exists;

class OperationSystem
{
	private static $stack = [];

	/**
	 * Check whether a function is disabled in the PHP settings
	 *
	 * @param string $function the name of the function to check
	 * @return bool
	 */
	public static function isFunctionDisabled(string $function): bool
	{
		static $list;

		if (null === $list) {
			$str = trim(ini_get('disable_functions') . ',' . ini_get('suhosin.executor.func.blacklist'), ',');
			$list = $str ? array_flip(preg_split('/\s*,\s*/', $str)) : [];
		}

		return array_key_exists($function, $list);
	}

	/**
	 * Limits the maximum execution time
	 * 
	 * @param int $seconds
	 * 
	 * @return bool
	 */
	public static function setMaximumExecutionTimeLimit(int $seconds): bool
	{
		return set_time_limit($seconds);
	}

	public static function getLoadedINIFiles(): bool|string
	{
		return php_ini_loaded_file();
	}

	public static function getScannedINIFiles(): bool|string
	{
		return php_ini_scanned_files();
	}

	public static function clearLibxmlErrors(): void
	{
		libxml_clear_errors();
	}

	/**
	 * Disable libxml errors and allow user to fetch error information as needed
	 * 
	 * @param ?bool $use_errors
	 * 
	 * @return bool
	 */
	public static function disableLibXmlInternalErrors(?bool $use_errors = null): bool
	{
		return libxml_use_internal_errors($use_errors);
	}

	/**
	 * Find out whether an extension is loaded
	 * 
	 * @param string $extension
	 * 
	 * @return bool
	 */
	public static function isExtensionLoaded(string $extension): bool
	{
		return extension_loaded($extension);
	}

	/**
	 * Sets the default timezone used by all date/time functions in a script
	 * 
	 * @param string|TimeZone $timeZoneId
	 * 
	 * @return bool
	 */
	public static function setDefaultDateTimeZone(string|TimeZone $timeZoneId = TimeZone::UTC): bool
	{
		return date_default_timezone_set($timeZoneId);
	}

	public static function setDisplayStatupErrors(bool $displayErrors): string|bool
	{
		return ini_set('display_startup_errors', $displayErrors ? 'On' : 'Off');
	}

	public static function setDisplayErrors(bool|int $displayErrors): string|bool
	{
		return ini_set('display_errors', $displayErrors);
	}

	public static function restorePrevErrorReporting(): int
	{
		if (!empty(self::$stack)) {
			return error_reporting(array_pop(self::$stack));
		}

		return -1;
	}

	public static function suppressErrorReporting(?int $mask = null): int
	{
		if (!isset($mask)) {
			$mask = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED;
		}

		$old = error_reporting();
		self::$stack[] = $old;
		error_reporting($old & ~$mask);

		return $old;
	}

	public static function setErrorReportingLevel(int $level): int
	{
		return error_reporting($level);
	}

	public static function getPHPVersion(): bool|string
	{
		$version = phpversion();

		if (empty($version) && defined('PHP_VERSION')) {
			$version = PHP_VERSION;
		}

		return $version;
	}

	public static function evaluateByOperator(int|float $a, int|float $b, string $operator): bool
	{
		if ($a >= $b && $operator === '>=') {
			return true;
		} else if ($a == $b && $operator === '==') {
			return true;
		} else if ($a === $b && $operator === '===') {
			return true;
		} else if ($a > $b && $operator === '>') {
			return true;
		} else if ($a < $b && $operator === '<') {
			return true;
		} else if ($a <= $b && $operator === '<=') {
			return true;
		} else if ($a <> $b && $operator === '<>') {
			return true;
		} else if ($a <=> $b && $operator === '<=>') {
			return true;
		} else if ($a != $b && $operator === '!=') {
			return true;
		} else if ($a !== $b && $operator === '!==') {
			return true;
		}

		return false;
	}

	public static function comparePHPVersion(string $compareVersion = '8.0.0', string $operator = '>='): bool|int
	{
		$version = self::getPHPVersion();

		if (!$version) {
			$compareVersion = preg_replace('/([^0-9])/ui', '0', $compareVersion);
			$compareVersion = intval($compareVersion);

			return self::evaluateByOperator(PHP_VERSION_ID, $compareVersion, $operator);
		}

		return version_compare($version, $compareVersion, $operator);
	}

	public static function isCommandLineInterface(): bool
	{
		return (php_sapi_name() === 'cli');
	}

	public static function isPHPDebugMode(): bool
	{
		return (php_sapi_name() === 'phpdbg');
	}

	public static function getBuiltOperationSystemString(): string
	{
		return PHP_OS;
	}

	public static function getIntergerSize(): int
	{
		return PHP_INT_SIZE;
	}

	public static function isMouseSupported(): bool
	{
		return extension_loaded('ffi') && function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT);
	}

	public static function isWindowsTerminal(): bool
	{
		return getenv('WT_SESSION') !== false;
	}

	public static function isConPTY(): bool
	{
		if (getenv('TERM_PROGRAM') === 'vscode') {
			return true;
		}

		if (getenv('WT_SESSION') !== false) {
			return true;  // Windows Terminal
		}

		if (getenv('ConEmuPID') !== false) {
			return true;
		}

		if (getenv('VSCODE_PID') !== false) {
			return true;
		}

		if (getenv('VSCODE_IPC_HOOK_CLI') !== false) {
			return true;
		}

		return false;
	}

	public static function getMaximumIntergerSize(): int
	{
		return PHP_INT_MAX;
	}

	public static function is4BitOSBitOS(): bool
	{
		if (self::getMaximumIntergerSize() == 0x7) // Maximum value of 4-bit sign integer
		{
			return true;
		}

		return false;
	}

	public static function is8BitOSBitOS(): bool
	{
		if (self::getMaximumIntergerSize() == 0x7F) // Maximum value of 8-bit sign integer
		{
			return true;
		}

		return false;
	}

	public static function is16BitOS(): bool
	{
		if (self::getIntergerSize() == 2 || self::getMaximumIntergerSize() == 0x7FFF) // Maximum value of 16-bit sign integer
		{
			return true;
		}

		return false;
	}

	public static function is32BitOS(): bool
	{
		if (self::getIntergerSize() == 4 || self::getMaximumIntergerSize() == 0x7FFFFFFF) // Maximum value of 32-bit sign integer
		{
			return true;
		}

		return false;
	}

	public static function is64BitOS(): bool
	{
		if (self::getIntergerSize() == 8 || self::getMaximumIntergerSize() == 0x7FFFFFFFFFFFFFFF) // Maximum value of 64-bit sign integer
		{
			return true;
		}

		return false;
	}

	public static function isIIS(): bool
	{
		return (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);
	}

	public static function getHomePath(): mixed
	{
		return $_SERVER['HOME'] ?? "";
	}

	public static function getGatewayInterface(): string
	{
		return $_SERVER['GATEWAY_INTERFACE'];
	}

	public static function getMainServerSoftware(): string
	{
		return ucfirst(array_key_first(self::parseServerServerSoftware()));
	}

	public static function parseServerServerSoftware(): mixed
	{
		$software = self::getServerSoftware();

		$softwares = explode(' ', $software);

		return array_reduce($softwares, function ($res, $el) {
			list($software, $version) = array_merge(explode('/', $el), [1]);
			$res[$software] = (float) $version;
			return $res;
		}, []);
	}

	public static function getServerSoftware(): string
	{
		return $_SERVER['SERVER_SOFTWARE'] ?? "";
	}

	public static function getShortOperationSystemString(): string
	{
		return strtoupper(substr(self::getBuiltOperationSystemString(), 0, 3));
	}

	public static function isMachitosh(): bool
	{
		return 'Darwin' === self::getFamily();
	}

	public static function executeShellAndPrint(string $command, ?int &$resultCode = null): bool|null
	{
		return passthru($command, $resultCode);
	}

	public static function executeExternal(string $command, int $resultCode, bool $silence = false): bool|string|null
	{
		$result = [];

		try {
			if ($silence) {
				$flag = OperationSystem::isWindows() ? "2>&1" : "2>/dev/null";
				@exec($command . " {$flag}", $result, $resultCode);
			} else {
				@exec($command, $result, $resultCode);
			}
		} catch (\Throwable $e) {
		}

		return is_array($result) ? $result[0] : $result;
	}

	public static function executeShell(string $command): bool|string|null
	{
		$result = null;

		try {
			$result = @shell_exec($command);
		} catch (\Throwable $e) {
		}

		return $result;
	}

	public static function isWindows(): bool
	{
		return (self::getShortOperationSystemString() === 'WIN') || defined('PHP_WINDOWS_VERSION_BUILD');
	}

	public static function isDocker(): bool
	{
		if ((bool) ini_get('open_basedir')) {
			return false;
		}

		if (file_exists('/.dockerenv') || file_exists('/run/.containerenv') || file_exists('/var/run/.containerenv')) {
			return true;
		}

		$cgroups = [
			'/proc/self/mountinfo',
			'/proc/1/cgroup',
		];

		foreach ($cgroups as $cgroup) {
			if (!is_readable($cgroup)) {
				continue;
			}

			try {
				$data = @file_get_contents($cgroup);
			} catch (\Throwable $e) {
				break;
			}

			if (!is_string($data)) {
				continue;
			}

			if (str_contains($data, '/var/lib/docker/') || str_contains($data, '/io.containerd.snapshotter')) {
				return true;
			}
		}

		return false;
	}

	public static function getDevNull(): string
	{
		if (self::isWindows()) {
			return 'NUL';
		}

		return '/dev/null';
	}

	public static function isTty($fd = null): bool
	{
		if ($fd === null) {
			$fd = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');
			if ($fd === false) {
				return false;
			}
		}

		if (in_array(strtoupper((string) $_SERVER['MSYSTEM']), ['MINGW32', 'MINGW64'], true)) {
			return true;
		}

		if (function_exists('stream_isatty')) {
			return stream_isatty($fd);
		}


		if (function_exists('posix_isatty') && posix_isatty($fd)) {
			return true;
		}

		$stat = @fstat($fd);
		if ($stat === false) {
			return false;
		}

		return 0020000 === ($stat['mode'] & 0170000);
	}

	public static function isSessionUseCookies(): bool|string
	{
		return ini_get('session.use_cookies');
	}

	public static function getHHVMVersion(): bool
	{
		return defined('HHVM_VERSION');
	}

	public static function getMaxPostSize(): bool|string
	{
		return ini_get('post_max_size');
	}

	public static function getMaxUploadFileSize(): bool|string
	{
		return ini_get('upload_max_filesize');
	}

	public static function isShortOpenTagAllowed(): bool
	{
		return ini_get('short_open_tag') == 1;
	}

	public static function isFileUploadAllowed(): bool
	{
		return ini_get('file_uploads') == 1;
	}

	public static function getLoadAverage(): array|bool
	{
		return sys_getloadavg();
	}

	public static function getUptime(): bool|string
	{
		return system("uptime");
	}

	public static function getMemoryUsage(): int
	{
		return memory_get_usage();
	}

	public static function getFamily(): string
	{
		return PHP_OS_FAMILY;
	}

	public static function getMaximumPathLength(): int
	{
		return PHP_MAXPATHLEN;
	}

	public function detectPlatform(): string
	{
		$os = strtolower(PHP_OS_FAMILY);
		return match (true) {
			str_contains($os, 'darwin') => 'mac',
			str_contains($os, 'win') => 'windows',
			default => 'linux',
		};
	}
	/**
	 * macOS: parse IOPlatformUUID from the ioreg output.
	 *
	 * @throws Exception
	 */
	public static function getMacUUIDFromIoreg(): string
	{
		$out = shell_exec('ioreg -rd1 -c IOPlatformExpertDevice 2>/dev/null') ?? '';
		if (preg_match('/"IOPlatformUUID"\s*=\s*"([^"]+)"/', $out, $m)) {
			return $m[1];
		}

		throw new Exception('Could not read IOPlatformUUID from ioreg. Is this macOS?');
	}


	/**
	 * Retrieve the hardware-bound device UUID using a platform-specific method.
	 *
	 *   macOS   → ioreg -rd1 -c IOPlatformExpertDevice  (IOPlatformUUID field)
	 *   Windows → reg.exe query HKLM\...\Cryptography   (MachineGuid value)
	 *             Fallback: wmic csproduct get UUID
	 *   Linux   → /etc/machine-id  or  /var/lib/dbus/machine-id
	 *             Raw 32-hex IDs are reformatted as 8-4-4-4-12 UUID strings.
	 *
	 * @return string  The UUID string (format may vary by platform).
	 * @throws Exception  If the UUID cannot be determined.
	 */
	public function getDeviceUuid(): string
	{
		return match ($this->detectPlatform()) {
			'mac' => self::getMacUUIDFromIoreg(),
			'windows' => self::getWindowsUUIDFromRegistry(),
			default => self::getLinuxUUIDFromMachineId(),
		};
	}

	/**
	 * Linux: read the machine ID file and normalize to UUID format.
	 *
	 * The machine-id file contains a plain 32-hex string (no dashes).
	 * It is reformatted to the standard 8-4-4-4-12 UUID layout so that
	 * it matches the format KakaoTalk expects on this platform.
	 *
	 * @throws Exception
	 */
	public static function getLinuxUUIDFromMachineId(): string
	{
		foreach (['/etc/machine-id', '/var/lib/dbus/machine-id'] as $path) {
			if (!is_readable($path)) {
				continue;
			}

			$id = trim((string) file_get_contents($path));
			if ($id === '') {
				continue;
			}

			// Reformat 32-char plain hex → 8-4-4-4-12 UUID
			if (preg_match('/^[0-9a-f]{32}$/', $id)) {
				$id = substr($id, 0, 8) . '-' . substr($id, 8, 4) . '-' . substr($id, 12, 4) . '-' . substr($id, 16, 4) . '-' . substr($id, 20);
			}

			return strtoupper($id);
		}

		throw new Exception('Could not read machine-id from /etc/machine-id or /var/lib/dbus/machine-id.');
	}

	/**
	 * Windows: read MachineGuid from the registry via reg.exe.
	 * Falls back to `wmic csproduct get UUID` if reg.exe is unavailable.
	 *
	 * @throws Exception
	 */
	public static function getWindowsUUIDFromRegistry(): string
	{
		// Primary: reg.exe query
		$out = shell_exec(
			'reg query "HKLM\\SOFTWARE\\Microsoft\\Cryptography" /v MachineGuid 2>NUL'
		) ?? '';
		if (preg_match('/MachineGuid\s+REG_SZ\s+([^\r\n]+)/i', $out, $m)) {
			return trim($m[1]);
		}

		// Fallback: wmic csproduct UUID
		$out = shell_exec('wmic csproduct get UUID 2>NUL') ?? '';
		if (preg_match('/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12})/', $out, $m)) {
			return strtoupper(trim($m[1]));
		}

		throw new Exception('Could not read MachineGuid from the Windows registry. Ensure reg.exe or wmic is available.');
	}

	public static function getCpuNumbers(): int
	{
		$cpu_numbers = 0;

		if (self::getFamily() == 'Windows') {
			$cpu_numbers = getenv("NUMBER_OF_PROCESSORS") + 0;
		} else {
			$cpu_numbers = substr_count(file_get_contents("/proc/cpuinfo"), "processor");
		}

		return (int) $cpu_numbers;
	}

	public static function beep(): string|bool|null
	{
		return shell_exec('powershell -c "[console]::beep(800,300)"');
	}

	public static function getPeakMemoryUsage(): int
	{
		return memory_get_peak_usage(true);
	}

}
