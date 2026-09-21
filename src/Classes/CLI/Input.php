<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\CLI;

use Clover\Classes\BaseClass;
use FFI;
use function count;

class Input extends BaseClass
{
    public string $command;
    public array $args = [];
    public array $options = [];

    // Windows Console Mode flags
    private const ENABLE_LINE_INPUT = 0x0002;
    private const ENABLE_ECHO_INPUT = 0x0004;
    private const ENABLE_PROCESSED_INPUT = 0x0001;
    private const ENABLE_MOUSE_INPUT = 0x0010;
    private const ENABLE_EXTENDED_FLAGS = 0x0080;
    private const ENABLE_VIRTUAL_TERMINAL_INPUT = 0x0200;
    private const ENABLE_QUICK_EDIT_MODE = 0x0040;
    private const STD_INPUT_HANDLE = 0xFFFFFFF6;
    private const STD_OUTPUT_HANDLE = 0xFFFFFFF5;

    /** Byte accumulator for VT escape sequence parsing */
    private string $inputBuffer = '';

    /**
     * Parses positional arguments and long options from the raw argv array using command metadata.
     *
     * @param array<int, string> $argv        Raw PHP argv (script at index 0).
     * @param array<int, object> $argDefs     Argument definitions with name/default properties.
     * @param array<int, object> $optionDefs Option definitions with name/default properties.
     */
    public function __construct(array $argv, array $argDefs = [], array $optionDefs = [])
    {
        $this->command = $argv[1] ?? 'list';

		$positionalValues = [];
		for ($index = 2; $index < count($argv); $index++) {
			$token = $argv[$index];
			if (str_starts_with($token, '--')) {
				$pair = explode('=', substr($token, 2), 2);
				$this->options[$pair[0]] = $pair[1] ?? true;
				continue;
			}

			$positionalValues[] = $token;
		}

		foreach ($argDefs as $index => $argumentDefinition) {
			$this->args[$argumentDefinition->name] = $positionalValues[$index] ?? $argumentDefinition->default;
		}

        foreach ($optionDefs as $opt) {
            if (!isset($this->options[$opt->name])) {
                $this->options[$opt->name] = $opt->default;
            }
        }
    }

    /**
     * Returns a positional argument value resolved by name or numeric index.
     *
     * @param int|string $key Argument name or zero-based index.
     *
     * @return mixed Stored value or null when missing.
     */
    public function getArgument(int|string $key): mixed
    {
		if (is_int($key)) {
			return array_values($this->args)[$key] ?? null;
		}

		return $this->args[$key] ?? null;
    }

    /**
     * Returns a long option value parsed from `--name=value` tokens.
     *
     * @param string $key Option name without leading dashes.
     *
     * @return mixed Stored value or null when unset.
     */
    public function getOption(string $key): mixed
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Returns whether a long option was provided or has a default binding.
     *
     * @param string $key Option name without leading dashes.
     *
     * @return bool
     */
    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }

    /**
     * Detects Visual Studio Code integrated terminal environment variables.
     *
     * @return bool
     */
    private function isVSCode(): bool
    {
        return getenv('TERM_PROGRAM') === 'vscode' || getenv('VSCODE_PID') !== false || getenv('VSCODE_IPC_HOOK_CLI') !== false;
    }

    /**
     * Prompts the user to pick an option by index, using mouse input on Windows when available.
     *
     * @param array<int, string> $options      Display labels.
     * @param string             $prompt       Prompt text printed before STDIN read.
     * @param string|null        $numberColor  Optional color name for numeric prefixes.
     * @param bool               $underInput   When true, prints a secondary prompt line.
     *
     * @return int Zero-based selected index, or -1 when cancelled.
     */
    public function choose(array $options = [], string $prompt = "Enter the number of the item you want : ", ?string $numberColor = null, bool $underInput = false): int
    {
        if (PHP_OS_FAMILY === 'Windows' && $this->isMouseSupported() && !$this->isVSCode()) {
            return $this->chooseWithMouse($options, $numberColor);
        }

        foreach ($options as $i => $option) {
            if ($numberColor) {
                echo ColorText::color((string) ($i + 1), $numberColor);
                echo ". $option\n";
            } else {
                echo ($i + 1) . ". $option\n";
            }
        }

        if ($prompt) {
            echo PHP_EOL . $prompt;
        }
        if ($underInput) {
            echo PHP_EOL . '> ';
        }

        $choice = trim(fgets(STDIN));
        $index = (int) $choice - 1;
        echo PHP_EOL;
        return $index;
    }

    /**
     * Reads a single line from STDIN after echoing the prompt.
     *
     * @param string $prompt Prompt text.
     *
     * @return string Trimmed user input.
     */
    public function getPrompt(string $prompt = "Enter the prompt : "): string
    {
        echo $prompt;
        return trim(fgets(STDIN));
    }

    // =========================================================================
    // Mouse-click selection
    // =========================================================================

    /**
     * Interactive picker using Windows mouse events or VT sequences.
     *
     * @param array<int, string> $options      Display labels.
     * @param string|null        $numberColor  Optional color name for numeric prefixes.
     *
     * @return int Selected zero-based index or -1 when cancelled.
     */
    private function chooseWithMouse(array $options, ?string $numberColor): int
    {
        sapi_windows_vt100_support(STDOUT, true);
        $this->inputBuffer = '';

        $isConPTY = $this->isConPTY();

        if ($isConPTY) {
            $originalMode = $this->setRawConsolMode();

            fwrite(STDOUT, "\033[?1000h\033[?1006h");
            fflush(STDOUT);

            $startRow = $this->queryCursorRowVT();

            foreach ($options as $i => $option) {
                echo ($numberColor ? ColorText::color($i + 1, $numberColor) : ($i + 1)) . ". $option\n";
            }
            $count = count($options);
            fwrite(STDOUT, "\n  [Click an option  /  Press 1-{$count}  /  ESC to cancel]\n");
            fflush(STDOUT);

            $selected = 0;
            try {
                $ffi = $this->getKernelFFI();
                $hStdin = $ffi->GetStdHandle(self::STD_INPUT_HANDLE);

                while (true) {
                    $byte = $this->readRawByte($ffi, $hStdin);
                    if ($byte === false || $byte === '') {
                        continue;
                    }

                    $this->inputBuffer .= $byte;
                    $event = $this->parseInputBuffer();
                    if ($event === null) {
                        continue;
                    }

                    if ($event['type'] === 'mouse' && $event['action'] === 'press' && $event['button'] === 0) {
                        $idx = $event['row'] - $startRow;
                        if ($idx >= 0 && $idx < $count) {
                            $selected = $idx;
                            $this->highlightRow($event['row'], $options[$idx]);
                            break;
                        }
                    }

                    if ($event['type'] === 'key') {
                        $ch = $event['char'];
                        if ($ch >= '1' && $ch <= '9') {
                            $idx = (int) $ch - 1;
                            if ($idx < $count) {
                                $selected = $idx;
                                break;
                            }
                        }
                        if ($ch === "\x1b" || $ch === "\x03") {
                            $selected = -1;
                            break;
                        }
                    }
                }
            } finally {
                fwrite(STDOUT, "\033[?1006l\033[?1000l");
                fflush(STDOUT);
                $this->restoreConsoleMode($originalMode);
            }
        } else {
            [$ffi, $hStdin, $hStdout, $originalMode] = $this->enableRawMode();

            $startRow = $this->queryCursorRow($ffi, $hStdout);

            foreach ($options as $i => $option) {
                echo ($numberColor ? ColorText::color($i + 1, $numberColor) : ($i + 1)) . ". $option\n";
            }
            $count = count($options);
            echo "\n  [Click an option  /  Press 1-{$count}  /  ESC to cancel]\n";

            $selected = 0;
            try {
                while (true) {
                    $event = $this->readEvent($ffi, $hStdin);
                    if ($event === null) {
                        continue;
                    }

                    if ($event['type'] === 'mouse' && $event['action'] === 'press' && $event['button'] === 0) {
                        $idx = $event['row'] - $startRow;
                        if ($idx >= 0 && $idx < $count) {
                            $selected = $idx;
                            $this->highlightRow($event['row'], $options[$idx]);
                            break;
                        }
                    }

                    if ($event['type'] === 'key') {
                        $ch = $event['char'];
                        if ($ch >= '1' && $ch <= '9') {
                            $idx = (int) $ch - 1;
                            if ($idx < $count) {
                                $selected = $idx;
                                break;
                            }
                        }

                        if ($ch === "\x1b" || $ch === "\x03") {
                            $selected = -1;
                            break;
                        }
                    }
                }
            } finally {
                $this->restoreMode($ffi, $hStdin, $originalMode);
            }
        }

        echo "\n";
        return $selected;
    }

    /**
     * Returns whether the Windows Terminal ConPTY session flag is present.
     *
     * @return bool
     */
    private function isConPTY(): bool
    {
        return getenv('WT_SESSION') !== false;
    }

    /**
     * Reads a single byte from a Windows console input handle.
     *
     * @param FFI   $ffi    FFI instance with ReadFile bound.
     * @param mixed $hStdin stdin handle from GetStdHandle.
     *
     * @return string|null The byte as a character or null on failure.
     */
    private function readRawByte(FFI $ffi, mixed $hStdin): ?string
    {
        $buf = $ffi->new('unsigned char[1]');
        $numRead = $ffi->new('unsigned int');

        $ok = $ffi->ReadFile($hStdin, $buf, 1, FFI::addr($numRead), null);

        if (!$ok || !$numRead->cdata) {
            return null;
        }

        return chr((int) $buf[0]->cdata);
    }

    /**
     * Disables line buffering and echo on STDIN, returning the previous console mode.
     *
     * @return int Original mode flags for {@see restoreConsoleMode()}.
     */
    private function setRawConsolMode(): int
    {
        $ffi = $this->getKernelFFI();
        $hStdin = $ffi->GetStdHandle(self::STD_INPUT_HANDLE);

        $modePtr = $ffi->new('unsigned int');
        $ffi->GetConsoleMode($hStdin, FFI::addr($modePtr));
        $original = $modePtr->cdata;

        $ffi->SetConsoleMode($hStdin, $original & ~self::ENABLE_LINE_INPUT & ~self::ENABLE_ECHO_INPUT & ~self::ENABLE_PROCESSED_INPUT);

        return $original;
    }

    /**
     * Restores STDIN console mode flags after raw input handling.
     *
     * @param int $originalMode Value returned from {@see setRawConsolMode()}.
     *
     * @return void
     */
    private function restoreConsoleMode(int $originalMode): void
    {
        $ffi = $this->getKernelFFI();
        $hStdin = $ffi->GetStdHandle(self::STD_INPUT_HANDLE);
        $ffi->SetConsoleMode($hStdin, $originalMode);
    }

    private ?FFI $kernelFFI = null;

    /**
     * Lazily loads a minimal kernel32 FFI binding for console APIs.
     *
     * @return FFI
     */
    private function getKernelFFI(): FFI
    {
        if ($this->kernelFFI !== null) {
            return $this->kernelFFI;
        }
        $this->kernelFFI = FFI::cdef(
            'typedef unsigned short WORD;
         typedef unsigned int   DWORD;
         typedef int            BOOL;
         typedef void*          HANDLE;
         HANDLE GetStdHandle(DWORD nStdHandle);
         BOOL   GetConsoleMode(HANDLE hConsoleHandle, DWORD *lpMode);
         BOOL   SetConsoleMode(HANDLE hConsoleHandle, DWORD  dwMode);
         BOOL   ReadFile(HANDLE hFile, void *lpBuffer, DWORD nNumberOfBytesToRead,
                         DWORD *lpNumberOfBytesRead, void *lpOverlapped);',
            'kernel32.dll'
        );
        return $this->kernelFFI;
    }

    /**
     * Queries the cursor row using the CPR VT sequence (ConPTY path).
     *
     * @return int One-based row index, or 1 when parsing fails.
     */
    private function queryCursorRowVT(): int
    {
        $ffi = $this->getKernelFFI();
        $hStdin = $ffi->GetStdHandle(self::STD_INPUT_HANDLE);

        fwrite(STDOUT, "\033[6n");
        fflush(STDOUT);

        $resp = '';
        $deadline = microtime(true) + 0.5;

        while (microtime(true) < $deadline) {
            $ch = $this->readRawByte($ffi, $hStdin);
            if ($ch === null) {
                continue;
            }

            $resp .= $ch;
            if (str_ends_with($resp, 'R')) {
                break;
            }
        }

        if (preg_match('/\x1b\[(\d+);\d+R/', $resp, $m)) {
            return (int) $m[1];
        }
        return 1;
    }

    /**
     * Read one raw INPUT_RECORD from the console.
     * If it yields a usable byte, append to inputBuffer and try to parse.
     *
     * INPUT_RECORD (Windows, 20 bytes, little-endian):
     *   [0-1]   WORD  EventType
     *   [2-3]   WORD  padding          ← required for 4-byte alignment of union
     *   KEY_EVENT_RECORD (union at offset 4):
     *     [0-3]  BOOL  bKeyDown
     *     [4-5]  WORD  wRepeatCount
     *     [6-7]  WORD  wVirtualKeyCode
     *     [8-9]  WORD  wVirtualScanCode
     *     [10]   CHAR  AsciiChar
     *     [12-15] DWORD dwControlKeyState
     *   MOUSE_EVENT_RECORD (union at offset 4):
     *     [0-1]  SHORT X (col, 0-based)
     *     [2-3]  SHORT Y (row, 0-based)
     *     [4-7]  DWORD dwButtonState
     *     [8-11] DWORD dwControlKeyState
     *     [12-15] DWORD dwEventFlags
     */
    private function readEvent(FFI $ffi, mixed $hStdin): ?array
    {
        $record = $ffi->new('INPUT_RECORD');
        $numRead = $ffi->new('unsigned int');

        $ffi->ReadConsoleInputA($hStdin, FFI::addr($record), 1, FFI::addr($numRead));

        if (!$numRead->cdata) {
            return null;
        }

        $eventType = (int) $record->EventType;

        // ── Native MOUSE_EVENT (real console / Windows Terminal) ─────────────
        if ($eventType === 0x0002) {
            $eventFlags = self::leDword($record->Event, 12);
            if ($eventFlags !== 0x0000 && $eventFlags !== 0x0002) {
                return null;
            }

            $buttonState = self::leDword($record->Event, 4);
            if ($buttonState & 0x0001) {
                $button = 0;
            } elseif ($buttonState & 0x0002) {
                $button = 1;
            } elseif ($buttonState & 0x0004) {
                $button = 2;
            } else {
                $button = -1;
            }

            return [
                'type' => 'mouse',
                'action' => $buttonState !== 0 ? 'press' : 'release',
                'button' => $button,
                'col' => self::leShort($record->Event, 0) + 1,
                'row' => self::leShort($record->Event, 2) + 1,
            ];
        }

        // ── KEY_EVENT ────────────────────────────────────────────────────────
        if ($eventType === 0x0001) {
            $keyDown = self::leDword($record->Event, 0);
            if (!$keyDown) {
                return null;
            }

            $ascii = $ffi->cast('unsigned char', $record->Event[10])->cdata;
            if ($ascii === 0) {
                return null;
            }

            $this->inputBuffer .= chr($ascii);
            return $this->parseInputBuffer();
        }

        return null;
    }

    /**
     * Parse the leading bytes of inputBuffer into a normalised event.
     *
     * Handled sequences:
     *   SGR mouse press   \033[<btn;col;rowM
     *   SGR mouse release \033[<btn;col;rowm
     *   Bare printable / control character
     *
     * Returns null if the buffer contains an incomplete escape sequence
     * (need more bytes before we can decide).
     */
    private function parseInputBuffer(): ?array
    {
        if ($this->inputBuffer === '') {
            return null;
        }

        // Complete SGR mouse sequence  \033[<btn;col;rowM  or  ...m
        if (preg_match('/^\x1b\[<(\d+);(\d+);(\d+)([Mm])/', $this->inputBuffer, $m)) {
            $this->inputBuffer = substr($this->inputBuffer, strlen($m[0]));
            return [
                'type' => 'mouse',
                'action' => $m[4] === 'M' ? 'press' : 'release',
                'button' => (int) $m[1],   // 0=left, 1=middle, 2=right (SGR encoding)
                'col' => (int) $m[2],
                'row' => (int) $m[3],
            ];
        }

        // Buffer starts with ESC but sequence is not yet complete – wait
        if ($this->inputBuffer[0] === "\x1b" && strlen($this->inputBuffer) < 20) {
            return null;
        }

        // Ordinary character (or a lone ESC that didn't grow into a sequence)
        $ch = $this->inputBuffer[0];
        $this->inputBuffer = substr($this->inputBuffer, 1);
        return ['type' => 'key', 'char' => $ch];
    }

    /**
     * Reads the cursor row from the console screen buffer info structure.
     *
     * @param FFI   $ffi     FFI with GetConsoleScreenBufferInfo.
     * @param mixed $hStdout stdout handle.
     *
     * @return int One-based row index.
     */
    private function queryCursorRow(FFI $ffi, mixed $hStdout): int
    {
        $csbi = $ffi->new('CONSOLE_SCREEN_BUFFER_INFO');
        return $ffi->GetConsoleScreenBufferInfo($hStdout, FFI::addr($csbi)) ? (int) $csbi->dwCursorPosition->Y + 1 : 1;
    }

    /**
     * Briefly inverts the option row to acknowledge a mouse selection.
     *
     * @param int    $row  One-based terminal row.
     * @param string $text Option label text.
     *
     * @return void
     */
    private function highlightRow(int $row, string $text): void
    {
        echo "\033[s";
        echo "\033[{$row};1H";
        echo "\033[7m  {$text}  \033[0m";
        usleep(150_000);
        echo "\033[{$row};1H\033[2K  {$text}";
        echo "\033[u";
    }

    /**
     * Returns whether FFI and VT100 support are available for mouse input.
     *
     * @return bool
     */
    private function isMouseSupported(): bool
    {
        return extension_loaded('ffi')
            && function_exists('sapi_windows_vt100_support')
            && sapi_windows_vt100_support(STDOUT);
    }

    /**
     * Enables mouse input on the console and returns handles for event polling.
     *
     * @return array{
     *  0: FFI, 
     *  1: mixed, 
     *  2: mixed, 
     *  3: int
     * } FFI instance, stdin, stdout, original mode.
     */
    private function enableRawMode(): array
    {
        $ffi = FFI::cdef(
            // ----------------------------------------------------------------
            // INPUT_RECORD must include the 2-byte padding word between
            // EventType and the union, otherwise all field offsets are wrong.
            //
            // Real layout (20 bytes):
            //   WORD  EventType    (offset  0, 2 bytes)
            //   WORD  wPadding     (offset  2, 2 bytes)  ← alignment padding
            //   BYTE  Event[16]    (offset  4, 16 bytes) ← the union
            // ----------------------------------------------------------------
            'typedef unsigned short WORD;
             typedef unsigned int   DWORD;
             typedef int            BOOL;
             typedef void*          HANDLE;
             typedef struct { short X; short Y; } COORD;
             typedef struct {
                 COORD dwSize;
                 COORD dwCursorPosition;
                 WORD  wAttributes;
                 struct { short Left; short Top; short Right; short Bottom; } srWindow;
                 COORD dwMaximumWindowSize;
             } CONSOLE_SCREEN_BUFFER_INFO;
             typedef struct {
                 WORD          EventType;
                 WORD          wPadding;
                 unsigned char Event[16];
             } INPUT_RECORD;

             HANDLE GetStdHandle(DWORD nStdHandle);
             BOOL   GetConsoleMode(HANDLE hConsoleHandle, DWORD *lpMode);
             BOOL   SetConsoleMode(HANDLE hConsoleHandle, DWORD  dwMode);
             BOOL   GetConsoleScreenBufferInfo(HANDLE hConsoleOutput, CONSOLE_SCREEN_BUFFER_INFO *lpConsoleScreenBufferInfo);
             BOOL   ReadConsoleInputA(HANDLE hConsoleInput, INPUT_RECORD *lpBuffer, DWORD nLength, DWORD *lpNumberOfEventsRead);',
            'kernel32.dll'
        );

        $hStdin = $ffi->GetStdHandle(self::STD_INPUT_HANDLE);
        $hStdout = $ffi->GetStdHandle(self::STD_OUTPUT_HANDLE);

        $modePtr = $ffi->new('unsigned int');
        $ffi->GetConsoleMode($hStdin, FFI::addr($modePtr));
        $originalMode = $modePtr->cdata;

        $ffi->SetConsoleMode($hStdin, ($originalMode | self::ENABLE_MOUSE_INPUT | self::ENABLE_EXTENDED_FLAGS) & ~self::ENABLE_QUICK_EDIT_MODE & ~self::ENABLE_VIRTUAL_TERMINAL_INPUT);

        return [$ffi, $hStdin, $hStdout, $originalMode];
    }

    /**
     * Restores the original console input mode after raw mouse handling.
     *
     * @param FFI   $ffi          FFI with SetConsoleMode.
     * @param mixed $hStdin       stdin handle.
     * @param int   $originalMode Prior mode bitmask.
     *
     * @return void
     */
    private function restoreMode(FFI $ffi, mixed $hStdin, int $originalMode): void
    {
        $ffi->SetConsoleMode($hStdin, $originalMode);
    }

    // =========================================================================
    // Little-endian byte helpers  (operate on FFI unsigned char arrays)
    // =========================================================================

    /**
     * Reads a 16-bit little-endian word from a byte buffer.
     *
     * @param mixed $buf FFI-backed byte array.
     * @param int   $off Byte offset.
     *
     * @return int
     */
    private static function leWord(mixed $buf, int $off): int
    {
        return FFI::cast('unsigned char', $buf[$off])->cdata
            | (FFI::cast('unsigned char', $buf[$off + 1])->cdata << 8);
    }

    /**
     * Reads a signed 16-bit little-endian integer from a byte buffer.
     *
     * @param mixed $buf FFI-backed byte array.
     * @param int   $off Byte offset.
     *
     * @return int
     */
    private static function leShort(mixed $buf, int $off): int
    {
        $v = self::leWord($buf, $off);
        return ($v & 0x8000) ? $v - 0x10000 : $v;
    }

    /**
     * Reads a 32-bit little-endian dword from a byte buffer.
     *
     * @param mixed $buf FFI-backed byte array.
     * @param int   $off Byte offset.
     *
     * @return int
     */
    private static function leDword(mixed $buf, int $off): int
    {
        return FFI::cast('unsigned char', $buf[$off])->cdata
            | (FFI::cast('unsigned char', $buf[$off + 1])->cdata << 8)
            | (FFI::cast('unsigned char', $buf[$off + 2])->cdata << 16)
            | (FFI::cast('unsigned char', $buf[$off + 3])->cdata << 24);
    }
}
