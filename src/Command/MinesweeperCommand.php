<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Classes\CLI\{ColorText, Input, InputOption};
use Clover\Classes\System\Output;
use Clover\Implement\CommandInterface;

/**
 * MinesweeperCommand Class
 *
 * Randomly generated Minesweeper for the CLI.
 *
 * Controls:
 *   W / ↑        : Cursor up
 *   S / ↓        : Cursor down
 *   A / ←        : Cursor left
 *   D / →        : Cursor right
 *   SPACE / Enter : Reveal cell
 *   F             : Toggle flag
 *   R             : New game (same difficulty)
 *   Q             : Quit
 */
class MinesweeperCommand implements CommandInterface
{
    // ─────────────────────────────────────────────────────────
    // CommandInterface
    // ─────────────────────────────────────────────────────────

    public array $arguments = [];
    public array $options   = [];

    public function getName(): string        { return 'game:minesweeper'; }
    public function getDescription(): string { return 'Randomly generated Minesweeper — clear the minefield!'; }

    public function configure(): void
    {
        $this->options[] = new InputOption('difficulty', 'easy | normal | hard | custom', 'normal');
        $this->options[] = new InputOption('width',      'Board width  (custom only, 9-40)', '16');
        $this->options[] = new InputOption('height',     'Board height (custom only, 9-20)', '16');
        $this->options[] = new InputOption('mines',      'Mine count   (custom only)',        '40');
    }

    // ─────────────────────────────────────────────────────────
    // Difficulty presets
    // ─────────────────────────────────────────────────────────

    private const PRESETS = [
        'easy'   => ['rows' =>  9, 'cols' =>  9, 'mines' =>  10],
        'normal' => ['rows' => 16, 'cols' => 16, 'mines' =>  40],
        'hard'   => ['rows' => 16, 'cols' => 30, 'mines' =>  99],
    ];

    // ─────────────────────────────────────────────────────────
    // Cell state flags  (bitmask per cell)
    // ─────────────────────────────────────────────────────────

    private const F_MINE     = 0b00001;   // has a mine
    private const F_REVEALED = 0b00010;   // revealed by player
    private const F_FLAGGED  = 0b00100;   // flagged by player

    // ─────────────────────────────────────────────────────────
    // Game state
    // ─────────────────────────────────────────────────────────

    /** @var array<array<int>>  bitmask grid [row][col] */
    private array $cells = [];

    /** @var array<array<int>>  adjacent mine counts [row][col]  (-1 = mine) */
    private array $counts = [];

    private int   $rows       = 16;
    private int   $cols       = 16;
    private int   $totalMines = 40;
    private int   $flags      = 0;
    private int   $revealed   = 0;
    private int   $stage      = 1;

    /** @var array{r:int,c:int}  cursor position */
    private array $cursor = ['r' => 0, 'c' => 0];

    private bool  $firstMove  = true;   // mines placed after first reveal
    private bool  $gameOver   = false;
    private bool  $win        = false;
    private float $startTime  = 0.0;

    private string $difficulty = 'normal';

    // ─────────────────────────────────────────────────────────
    // Entry point
    // ─────────────────────────────────────────────────────────

    public function run(Input $input): bool
    {
        $diff = strtolower(trim($input->getOption('difficulty') ?? 'normal'));

        if ($diff === 'custom') {
            $this->rows       = max(9,  min(20, (int)($input->getOption('height') ?? 16)));
            $this->cols       = max(9,  min(40, (int)($input->getOption('width')  ?? 16)));
            $this->totalMines = max(1,  min($this->rows * $this->cols - 9,
                                            (int)($input->getOption('mines') ?? 40)));
            $this->difficulty = 'custom';
        } else {
            $preset           = self::PRESETS[$diff] ?? self::PRESETS['normal'];
            $this->rows       = $preset['rows'];
            $this->cols       = $preset['cols'];
            $this->totalMines = $preset['mines'];
            $this->difficulty = $diff;
        }

        $this->showBanner();
        Output::printLine(ColorText::color(' Press any key to start… ', 'white', 'blue'));
        $this->readKey();

        $this->newGame();
        $this->gameLoop();

        return true;
    }

    // ─────────────────────────────────────────────────────────
    // Game loop
    // ─────────────────────────────────────────────────────────

    private function gameLoop(): void
    {
        while (true) {
            $this->render();

            if ($this->gameOver || $this->win) {
                $this->onResult();

                // Ask replay
                $answer = $this->readKey();
                if (strtolower($answer) === 'r') {
                    if ($this->win) {
                        $this->stage++;
                    }
                    $this->newGame();
                    continue;
                }

                $this->clearScreen();
                Output::printLine(ColorText::color(' 👋  Thanks for playing! ', 'white', 'blue'));
                echo PHP_EOL;
                return;
            }

            $key = $this->readKey();
            $this->handleKey($key);
        }
    }

    private function handleKey(string $key): void
    {
        switch (strtolower($key)) {
            // ── Cursor movement ────────────────────────────
            case 'w': case "\x1b[A":
                $this->cursor['r'] = max(0, $this->cursor['r'] - 1);
                break;
            case 's': case "\x1b[B":
                $this->cursor['r'] = min($this->rows - 1, $this->cursor['r'] + 1);
                break;
            case 'd': case "\x1b[C":
                $this->cursor['c'] = min($this->cols - 1, $this->cursor['c'] + 1);
                break;
            case 'a': case "\x1b[D":
                $this->cursor['c'] = max(0, $this->cursor['c'] - 1);
                break;

            // ── Reveal ────────────────────────────────────
            case ' ': case "\r": case "\n":
                $this->reveal($this->cursor['r'], $this->cursor['c']);
                break;

            // ── Flag ──────────────────────────────────────
            case 'f':
                $this->toggleFlag($this->cursor['r'], $this->cursor['c']);
                break;

            // ── New game ──────────────────────────────────
            case 'r':
                $this->newGame();
                break;

            // ── Quit ──────────────────────────────────────
            case 'q': case "\x03":
                $this->clearScreen();
                Output::printLine(ColorText::color(' 👋  Thanks for playing! ', 'white', 'blue'));
                echo PHP_EOL;
                exit(0);
        }
    }

    // ─────────────────────────────────────────────────────────
    // Board initialisation
    // ─────────────────────────────────────────────────────────

    private function newGame(): void
    {
        $this->cells     = [];
        $this->counts    = [];
        $this->flags     = 0;
        $this->revealed  = 0;
        $this->firstMove = true;
        $this->gameOver  = false;
        $this->win       = false;
        $this->startTime = microtime(true);
        $this->cursor    = ['r' => intdiv($this->rows, 2), 'c' => intdiv($this->cols, 2)];

        for ($r = 0; $r < $this->rows; $r++) {
            $this->cells[$r]  = array_fill(0, $this->cols, 0);
            $this->counts[$r] = array_fill(0, $this->cols, 0);
        }
    }

    /**
     * Place mines randomly, avoiding a 3×3 safe zone around the first click.
     */
    private function placeMines(int $safeR, int $safeC): void
    {
        $placed = 0;
        $safe   = [];
        for ($dr = -1; $dr <= 1; $dr++) {
            for ($dc = -1; $dc <= 1; $dc++) {
                $safe[] = ($safeR + $dr) . ',' . ($safeC + $dc);
            }
        }

        while ($placed < $this->totalMines) {
            $r = mt_rand(0, $this->rows - 1);
            $c = mt_rand(0, $this->cols - 1);

            if (in_array("{$r},{$c}", $safe, true)) {
                continue;
            }
            if ($this->cells[$r][$c] & self::F_MINE) {
                continue;
            }

            $this->cells[$r][$c] |= self::F_MINE;
            $placed++;
        }

        // Pre-compute adjacent counts
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                if ($this->cells[$r][$c] & self::F_MINE) {
                    $this->counts[$r][$c] = -1;
                    continue;
                }
                $n = 0;
                foreach ($this->neighbours($r, $c) as [$nr, $nc]) {
                    if ($this->cells[$nr][$nc] & self::F_MINE) {
                        $n++;
                    }
                }
                $this->counts[$r][$c] = $n;
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // Game actions
    // ─────────────────────────────────────────────────────────

    private function reveal(int $r, int $c): void
    {
        $cell = $this->cells[$r][$c];

        if ($cell & self::F_REVEALED) {
            // Chord: if flagged neighbours == count, reveal the rest
            $this->chord($r, $c);
            return;
        }
        if ($cell & self::F_FLAGGED) {
            return;
        }

        // First move — place mines now (safe zone around click)
        if ($this->firstMove) {
            $this->firstMove = false;
            $this->placeMines($r, $c);
            $this->startTime = microtime(true);
        }

        if ($this->counts[$r][$c] === -1) {
            // Hit a mine
            $this->cells[$r][$c] |= self::F_REVEALED;
            $this->gameOver = true;
            $this->revealAllMines();
            return;
        }

        // BFS flood-fill for blank cells
        $queue = [[$r, $c]];
        while (!empty($queue)) {
            [$qr, $qc] = array_shift($queue);

            if ($this->cells[$qr][$qc] & self::F_REVEALED) {
                continue;
            }
            if ($this->cells[$qr][$qc] & self::F_FLAGGED) {
                continue;
            }

            $this->cells[$qr][$qc] |= self::F_REVEALED;
            $this->revealed++;

            if ($this->counts[$qr][$qc] === 0) {
                foreach ($this->neighbours($qr, $qc) as [$nr, $nc]) {
                    if (!($this->cells[$nr][$nc] & self::F_REVEALED)) {
                        $queue[] = [$nr, $nc];
                    }
                }
            }
        }

        $this->checkWin();
    }

    /**
     * Chord reveal: if flagged count matches the cell number, reveal all
     * un-flagged neighbours.
     */
    private function chord(int $r, int $c): void
    {
        if (!($this->cells[$r][$c] & self::F_REVEALED)) {
            return;
        }
        $count = $this->counts[$r][$c];
        if ($count <= 0) {
            return;
        }

        $flagCount = 0;
        foreach ($this->neighbours($r, $c) as [$nr, $nc]) {
            if ($this->cells[$nr][$nc] & self::F_FLAGGED) {
                $flagCount++;
            }
        }

        if ($flagCount === $count) {
            foreach ($this->neighbours($r, $c) as [$nr, $nc]) {
                if (!($this->cells[$nr][$nc] & self::F_FLAGGED) &&
                    !($this->cells[$nr][$nc] & self::F_REVEALED)) {
                    $this->reveal($nr, $nc);
                }
            }
        }
    }

    private function toggleFlag(int $r, int $c): void
    {
        $cell = $this->cells[$r][$c];
        if ($cell & self::F_REVEALED) {
            return;
        }

        if ($cell & self::F_FLAGGED) {
            $this->cells[$r][$c] &= ~self::F_FLAGGED;
            $this->flags--;
        } else {
            $this->cells[$r][$c] |= self::F_FLAGGED;
            $this->flags++;
        }
    }

    private function revealAllMines(): void
    {
        for ($r = 0; $r < $this->rows; $r++) {
            for ($c = 0; $c < $this->cols; $c++) {
                if ($this->cells[$r][$c] & self::F_MINE) {
                    $this->cells[$r][$c] |= self::F_REVEALED;
                }
            }
        }
    }

    private function checkWin(): void
    {
        $safe = $this->rows * $this->cols - $this->totalMines;
        if ($this->revealed >= $safe) {
            $this->win = true;
        }
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    /**
     * Return valid (row, col) neighbours of (r, c).
     *
     * @return array<array{int,int}>
     */
    private function neighbours(int $r, int $c): array
    {
        $result = [];
        for ($dr = -1; $dr <= 1; $dr++) {
            for ($dc = -1; $dc <= 1; $dc++) {
                if ($dr === 0 && $dc === 0) {
                    continue;
                }
                $nr = $r + $dr;
                $nc = $c + $dc;
                if ($nr >= 0 && $nr < $this->rows && $nc >= 0 && $nc < $this->cols) {
                    $result[] = [$nr, $nc];
                }
            }
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────
    // Rendering
    // ─────────────────────────────────────────────────────────

    /** Number colours: 1=blue 2=green 3=red 4=darkblue 5=darkred 6=teal 7=black 8=grey */
    private const NUM_COLORS = ['', 'blue', 'green', 'red', 'magenta', 'red', 'cyan', 'white', 'white'];

    private function render(): void
    {
        $this->clearScreen();

        // ── Header ──────────────────────────────────────────
        $elapsed  = $this->firstMove ? 0 : (int)round(microtime(true) - $this->startTime);
        $mm       = str_pad((string)intdiv($elapsed, 60), 2, '0', STR_PAD_LEFT);
        $ss       = str_pad((string)($elapsed % 60),      2, '0', STR_PAD_LEFT);
        $minesLeft = $this->totalMines - $this->flags;
        $diffLabel = strtoupper($this->difficulty);

        Output::printLine(
            ColorText::color(' 💣 MINESWEEPER ',   'white', 'blue')  . ' ' .
            ColorText::color(" Stage {$this->stage} ", 'black', 'cyan')   . '  ' .
            ColorText::color(" {$diffLabel} ",      'black', 'yellow') . '  ' .
            ColorText::color(" 🚩 {$minesLeft} ",   'red')            . '  ' .
            ColorText::color(" ⏱  {$mm}:{$ss} ",    'magenta')
        );
        echo PHP_EOL;

        // ── Column index bar ────────────────────────────────
        $colBar = '    ';   // indent for row numbers
        for ($c = 0; $c < $this->cols; $c++) {
            $colBar .= $c % 10 === 0
                ? ColorText::color(str_pad((string)$c, 3), 'white')
                : '   ';
        }
        echo $colBar . PHP_EOL;

        // ── Board ────────────────────────────────────────────
        for ($r = 0; $r < $this->rows; $r++) {
            $line = ColorText::color(str_pad((string)$r, 3) . ' ', 'white');

            for ($c = 0; $c < $this->cols; $c++) {
                $isCursor = ($r === $this->cursor['r'] && $c === $this->cursor['c']);
                $line    .= $this->renderCell($r, $c, $isCursor);
            }

            echo $line . PHP_EOL;
        }

        // ── Footer ───────────────────────────────────────────
        echo PHP_EOL;
        echo ColorText::color('[W/A/S/D] Move',  'green')  . '  ' .
             ColorText::color('[Space] Reveal',   'cyan')   . '  ' .
             ColorText::color('[F] Flag',          'yellow') . '  ' .
             ColorText::color('[Space on #] Chord','white')  . '  ' .
             ColorText::color('[R] New  [Q] Quit', 'red')    . PHP_EOL;
    }

    /**
     * Render a single cell as a 3-char string.
     */
    private function renderCell(int $r, int $c, bool $isCursor): string
    {
        $cell  = $this->cells[$r][$c];
        $count = $this->counts[$r][$c];

        $isMine     = (bool)($cell & self::F_MINE);
        $isRevealed = (bool)($cell & self::F_REVEALED);
        $isFlagged  = (bool)($cell & self::F_FLAGGED);

        if (!$isRevealed) {
            if ($isFlagged) {
                $str = ' 🚩';
                return $isCursor ? ColorText::color($str, 'black', 'yellow') : $str;
            }
            $str = ' ■ ';
            return $isCursor
                ? ColorText::color($str, 'black', 'cyan')
                : ColorText::color($str, 'white');
        }

        // Revealed mine — game over display
        if ($isMine) {
            $str = ' 💣';
            return $isCursor ? ColorText::color($str, 'black', 'red') : $str;
        }

        // Revealed safe cell
        if ($count === 0) {
            $str = ' · ';
            return $isCursor
                ? ColorText::color($str, 'black', 'cyan')
                : ColorText::color($str, 'white');
        }

        $color = self::NUM_COLORS[$count] ?? 'white';
        $str   = " {$count} ";
        return $isCursor
            ? ColorText::color($str, 'black', 'cyan')
            : ColorText::color($str, $color);
    }

    // ─────────────────────────────────────────────────────────
    // Result screens
    // ─────────────────────────────────────────────────────────

    private function onResult(): void
    {
        $elapsed = (int)round(microtime(true) - $this->startTime);
        $mm      = str_pad((string)intdiv($elapsed, 60), 2, '0', STR_PAD_LEFT);
        $ss      = str_pad((string)($elapsed % 60),      2, '0', STR_PAD_LEFT);

        echo PHP_EOL;

        if ($this->win) {
            $art = <<<EOD
  ██╗   ██╗ ██████╗ ██╗   ██╗    ██╗    ██╗██╗███╗  ██╗██╗
  ╚██╗ ██╔╝██╔═══██╗██║   ██║    ██║    ██║██║████╗ ██║██║
   ╚████╔╝ ██║   ██║██║   ██║    ██║ █╗ ██║██║██╔██╗██║██║
    ╚██╔╝  ██║   ██║██║   ██║    ██║███╗██║██║██║╚████║╚═╝
     ██║   ╚██████╔╝╚██████╔╝    ╚███╔███╔╝██║██║ ╚███║██╗
     ╚═╝    ╚═════╝  ╚═════╝      ╚══╝╚══╝ ╚═╝╚═╝  ╚══╝╚═╝
EOD;
            Output::printLine(ColorText::color($art, 'green'));
            Output::printLine(ColorText::color(
                sprintf('  🎉  Stage %d cleared!  Time: %s:%s  |  Mines: %d',
                    $this->stage, $mm, $ss, $this->totalMines),
                'yellow'
            ));
        } else {
            $art = <<<EOD
  ██████╗  ██████╗  ██████╗ ███╗   ███╗██╗
  ██╔══██╗██╔═══██╗██╔═══██╗████╗ ████║██║
  ██████╔╝██║   ██║██║   ██║██╔████╔██║██║
  ██╔══██╗██║   ██║██║   ██║██║╚██╔╝██║╚═╝
  ██████╔╝╚██████╔╝╚██████╔╝██║ ╚═╝ ██║██╗
  ╚═════╝  ╚═════╝  ╚═════╝ ╚═╝     ╚═╝╚═╝
EOD;
            Output::printLine(ColorText::color($art, 'red'));
            Output::printLine(ColorText::color(
                sprintf('  💥  BOOM!  Time: %s:%s  |  Revealed: %d / %d safe cells',
                    $mm, $ss, $this->revealed,
                    $this->rows * $this->cols - $this->totalMines),
                'yellow'
            ));
        }

        echo PHP_EOL;
        Output::printLine(
            ColorText::color(' [R] Play again ', 'white', 'blue') . '  ' .
            ColorText::color(' [Any] Quit ',     'white', 'red')
        );
    }

    // ─────────────────────────────────────────────────────────
    // Banner
    // ─────────────────────────────────────────────────────────

    private function showBanner(): void
    {
        $this->clearScreen();

        $banner = <<<EOD

  ███╗   ███╗██╗███╗  ██╗███████╗███████╗██╗    ██╗███████╗███████╗██████╗ ███████╗██████╗
  ████╗ ████║██║████╗ ██║██╔════╝██╔════╝██║    ██║██╔════╝██╔════╝██╔══██╗██╔════╝██╔══██╗
  ██╔████╔██║██║██╔██╗██║█████╗  ███████╗██║ █╗ ██║█████╗  █████╗  ██████╔╝█████╗  ██████╔╝
  ██║╚██╔╝██║██║██║╚████║██╔══╝  ╚════██║██║███╗██║██╔══╝  ██╔══╝  ██╔═══╝ ██╔══╝  ██╔══██╗
  ██║ ╚═╝ ██║██║██║ ╚███║███████╗███████║╚███╔███╔╝███████╗███████╗██║     ███████╗██║  ██║
  ╚═╝     ╚═╝╚═╝╚═╝  ╚══╝╚══════╝╚══════╝ ╚══╝╚══╝ ╚══════╝╚══════╝╚═╝     ╚══════╝╚═╝  ╚═╝

EOD;
        Output::printLine(ColorText::color($banner, 'cyan'));

        $preset = self::PRESETS[$this->difficulty] ?? ['rows' => $this->rows, 'cols' => $this->cols, 'mines' => $this->totalMines];
        Output::printLine(ColorText::color(
            sprintf('  Difficulty: %s  |  Board: %d×%d  |  Mines: %d  |  CloverFramework CLI',
                strtoupper($this->difficulty), $this->cols, $this->rows, $this->totalMines),
            'magenta'
        ));
        echo PHP_EOL;

        // Controls card
        Output::printLine(ColorText::color('  ┌─── Controls ────────────────────────────────────────────┐', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('W/A/S/D  ↑←↓→', 'yellow')  . '  Move cursor                           ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('Space / Enter ', 'cyan')    . '  Reveal cell (on number = Chord)        ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('F             ', 'yellow')  . '  Toggle flag 🚩                         ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('R             ', 'green')   . '  New game                               ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('Q             ', 'red')     . '  Quit                                   ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  └─────────────────────────────────────────────────────────┘', 'white'));
        echo PHP_EOL;

        Output::printLine(
            ColorText::color('  Legend: ', 'white') .
            ColorText::color(' ■ ', 'white')          . ' Hidden   ' .
            ' 🚩'                                     . ' Flagged   ' .
            ' 💣'                                     . ' Mine   ' .
            ColorText::color(' · ', 'white')          . ' Safe (0)'
        );
        echo PHP_EOL;
    }

    // ─────────────────────────────────────────────────────────
    // Terminal helpers
    // ─────────────────────────────────────────────────────────

    private function readKey(): string
    {
        $stty = null;
        if (DIRECTORY_SEPARATOR === '/') {
            $stty = shell_exec('stty -g');
            system('stty cbreak -echo');
        }

        $key = fread(STDIN, 1);

        if ($key === "\x1b") {
            $next = fread(STDIN, 1);
            if ($next === '[') {
                $code = fread(STDIN, 1);
                $key  = "\x1b[{$code}";
            } else {
                $key = $next;
            }
        }

        if ($stty !== null) {
            system("stty '{$stty}'");
        }

        return $key;
    }

    private function clearScreen(): void
    {
        system(DIRECTORY_SEPARATOR === '/' ? 'clear' : 'cls');
    }
}
