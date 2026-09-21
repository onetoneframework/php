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
 * MazeCommand Class
 *
 * Randomly generated maze game for the CLI.
 * Uses Recursive Backtracker (DFS) to carve perfect mazes.
 *
 * Controls:
 *   W / ↑  : Move Up
 *   S / ↓  : Move Down
 *   A / ←  : Move Left
 *   D / →  : Move Right
 *   H      : Toggle hint (show shortest path)
 *   R      : Regenerate maze
 *   Q      : Quit
 */
class MazeCommand implements CommandInterface
{
    // ─────────────────────────────────────────────────────────
    // CommandInterface
    // ─────────────────────────────────────────────────────────

    public array $arguments = [];
    public array $options   = [];

    public function getName(): string        { return 'game:maze'; }
    public function getDescription(): string { return 'Randomly generated maze — find the exit!'; }

    public function configure(): void
    {
        $this->options[] = new InputOption('width',  'Maze width  (odd, 11-61)', '21');
        $this->options[] = new InputOption('height', 'Maze height (odd, 11-41)', '21');
        $this->options[] = new InputOption('seed',   'Random seed (0 = random)', '0');
    }

    // ─────────────────────────────────────────────────────────
    // Tile symbols
    // ─────────────────────────────────────────────────────────

    private const T_WALL   = '#';
    private const T_PATH   = ' ';
    private const T_PLAYER = '@';
    private const T_START  = 'S';
    private const T_EXIT   = 'E';
    private const T_CRUMB  = '·';   // visited trail
    private const T_HINT   = '~';   // BFS shortest-path hint

    // ─────────────────────────────────────────────────────────
    // Game state
    // ─────────────────────────────────────────────────────────

    /** @var array<array<string>>  The full maze grid */
    private array $grid   = [];

    /** @var array{r:int,c:int}    Player position */
    private array $player = ['r' => 1, 'c' => 1];

    /** @var array{r:int,c:int}    Start cell */
    private array $start  = ['r' => 1, 'c' => 1];

    /** @var array{r:int,c:int}    Exit cell */
    private array $exit   = ['r' => 1, 'c' => 1];

    private int   $rows   = 21;
    private int   $cols   = 21;
    private int   $moves  = 0;
    private int   $stage  = 1;
    private float $startTime = 0.0;

    /** @var bool  Whether the BFS hint overlay is active */
    private bool $hintActive = false;

    /** @var array<array{r:int,c:int}>  BFS path from player → exit */
    private array $hintPath = [];

    /** @var array<array{r:int,c:int}>  Cells the player has visited */
    private array $visited = [];

    // ─────────────────────────────────────────────────────────
    // Entry point
    // ─────────────────────────────────────────────────────────

    public function run(Input $input): bool
    {
        $w    = (int)($input->getOption('width')  ?? 21);
        $h    = (int)($input->getOption('height') ?? 21);
        $seed = (int)($input->getOption('seed')   ?? 0);

        // Force odd dimensions for the DFS carver
        $this->cols = max(11, min(61, $w % 2 === 0 ? $w + 1 : $w));
        $this->rows = max(11, min(41, $h % 2 === 0 ? $h + 1 : $h));

        $this->showBanner();

        Output::printLine(ColorText::color(' Press any key to start… ', 'white', 'blue'));
        $this->readKey();

        $this->newMaze($seed);
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

            if ($this->player === $this->exit) {
                $this->onStageClear();
                $this->stage++;
                $this->newMaze(0);   // fresh random maze
                continue;
            }

            $key = $this->readKey();

            switch (strtolower($key)) {
                case 'w': case "\x1b[A": $this->tryMove(-1,  0); break;
                case 's': case "\x1b[B": $this->tryMove( 1,  0); break;
                case 'd': case "\x1b[C": $this->tryMove( 0,  1); break;
                case 'a': case "\x1b[D": $this->tryMove( 0, -1); break;

                case 'h':
                    $this->hintActive = !$this->hintActive;
                    if ($this->hintActive) {
                        $this->hintPath = $this->bfsPath(
                            $this->player['r'], $this->player['c'],
                            $this->exit['r'],   $this->exit['c']
                        );
                    }
                    break;

                case 'r':
                    $this->newMaze(0);
                    break;

                case 'q': case "\x03":
                    $this->clearScreen();
                    Output::printLine(ColorText::color(' 👋  Thanks for playing! ', 'white', 'blue'));
                    echo PHP_EOL;
                    return;
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // Maze generation — Recursive Backtracker (iterative DFS)
    // ─────────────────────────────────────────────────────────

    /**
     * Generate a new perfect maze and reset game state.
     *
     * @param int $seed 0 = use microtime as seed
     */
    private function newMaze(int $seed): void
    {
        if ($seed === 0) {
            $seed = (int)(microtime(true) * 1000);
        }
        mt_srand($seed);

        $this->moves      = 0;
        $this->hintActive = false;
        $this->hintPath   = [];
        $this->visited    = [];
        $this->startTime  = microtime(true);

        // ── 1. Fill grid with walls ──────────────────────────
        $this->grid = [];
        for ($r = 0; $r < $this->rows; $r++) {
            $this->grid[$r] = array_fill(0, $this->cols, self::T_WALL);
        }

        // ── 2. DFS carve from (1,1) ─────────────────────────
        $startR = 1;
        $startC = 1;
        $this->grid[$startR][$startC] = self::T_PATH;

        $stack   = [[$startR, $startC]];
        $visited = ["{$startR},{$startC}" => true];

        while (!empty($stack)) {
            [$cr, $cc] = end($stack);

            // Collect unvisited neighbours 2 steps away
            $neighbours = [];
            foreach ([[-2, 0], [2, 0], [0, -2], [0, 2]] as [$dr, $dc]) {
                $nr = $cr + $dr;
                $nc = $cc + $dc;
                if ($nr > 0 && $nr < $this->rows - 1 &&
                    $nc > 0 && $nc < $this->cols - 1 &&
                    !isset($visited["{$nr},{$nc}"])) {
                    $neighbours[] = [$nr, $nc, $dr, $dc];
                }
            }

            if (empty($neighbours)) {
                array_pop($stack);
                continue;
            }

            // Pick a random neighbour and carve a passage
            $idx                          = mt_rand(0, count($neighbours) - 1);
            [$nr, $nc, $dr, $dc]          = $neighbours[$idx];
            $wallR                        = $cr + intdiv($dr, 2);
            $wallC                        = $cc + intdiv($dc, 2);
            $this->grid[$wallR][$wallC]   = self::T_PATH;
            $this->grid[$nr][$nc]         = self::T_PATH;

            $visited["{$nr},{$nc}"] = true;
            $stack[] = [$nr, $nc];
        }

        // ── 3. Start & exit ─────────────────────────────────
        $this->start  = ['r' => 1, 'c' => 1];
        $this->exit   = ['r' => $this->rows - 2, 'c' => $this->cols - 2];
        $this->player = $this->start;

        $this->grid[$this->start['r']][$this->start['c']] = self::T_START;
        $this->grid[$this->exit['r']] [$this->exit['c']]  = self::T_EXIT;

        // ── 4. Mark start as visited ────────────────────────
        $this->visited["{$this->start['r']},{$this->start['c']}"] = true;
    }

    // ─────────────────────────────────────────────────────────
    // Movement
    // ─────────────────────────────────────────────────────────

    private function tryMove(int $dr, int $dc): void
    {
        $nr = $this->player['r'] + $dr;
        $nc = $this->player['c'] + $dc;

        if (!isset($this->grid[$nr][$nc])) {
            return;
        }

        $tile = $this->grid[$nr][$nc];

        if ($tile === self::T_WALL) {
            return;
        }

        // Leave a trail crumb on the old cell (unless it is START)
        $pr = $this->player['r'];
        $pc = $this->player['c'];
        if ($this->grid[$pr][$pc] === self::T_PLAYER) {
            $this->grid[$pr][$pc] = self::T_CRUMB;
        }

        $this->player = ['r' => $nr, 'c' => $nc];
        $this->visited["{$nr},{$nc}"] = true;
        $this->moves++;

        // Recalculate hint path when moving
        if ($this->hintActive) {
            $this->hintPath = $this->bfsPath($nr, $nc, $this->exit['r'], $this->exit['c']);
        }
    }

    // ─────────────────────────────────────────────────────────
    // BFS shortest path  (for hint overlay)
    // ─────────────────────────────────────────────────────────

    /**
     * BFS from (sr,sc) to (er,ec); returns list of cells on the path
     * (excluding start, including end).
     *
     * @return array<array{r:int,c:int}>
     */
    private function bfsPath(int $sr, int $sc, int $er, int $ec): array
    {
        $queue  = [[$sr, $sc]];
        $prev   = ["{$sr},{$sc}" => null];

        while (!empty($queue)) {
            [$r, $c] = array_shift($queue);

            if ($r === $er && $c === $ec) {
                break;
            }

            foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dr, $dc]) {
                $nr = $r + $dr;
                $nc = $c + $dc;
                $k  = "{$nr},{$nc}";
                if (isset($this->grid[$nr][$nc]) &&
                    $this->grid[$nr][$nc] !== self::T_WALL &&
                    !array_key_exists($k, $prev)) {
                    $prev[$k] = [$r, $c];
                    $queue[]  = [$nr, $nc];
                }
            }
        }

        // Reconstruct path
        $path = [];
        $cur  = [$er, $ec];
        $ek   = "{$er},{$ec}";

        if (!array_key_exists($ek, $prev)) {
            return [];
        }

        while ($cur !== null) {
            $k   = "{$cur[0]},{$cur[1]}";
            $path[] = ['r' => $cur[0], 'c' => $cur[1]];
            $cur = $prev[$k] ?? null;
        }

        // Remove start cell from path
        array_pop($path);

        return array_reverse($path);
    }

    // ─────────────────────────────────────────────────────────
    // Rendering
    // ─────────────────────────────────────────────────────────

    private function render(): void
    {
        $this->clearScreen();

        // ── Header ──────────────────────────────────────────
        $elapsed = round(microtime(true) - $this->startTime);
        $mm      = str_pad((string)intdiv((int)$elapsed, 60), 2, '0', STR_PAD_LEFT);
        $ss      = str_pad((string)($elapsed % 60),           2, '0', STR_PAD_LEFT);

        Output::printLine(
            ColorText::color(' MAZE ',        'white', 'blue')   . ' ' .
            ColorText::color(" Stage {$this->stage} ",  'black', 'cyan')   . '  ' .
            ColorText::color(" Moves: {$this->moves} ", 'yellow') . '  ' .
            ColorText::color(" Time: {$mm}:{$ss} ",     'magenta') . '  ' .
            ColorText::color($this->hintActive ? ' [HINT ON] ' : ' [H] Hint ', 'cyan')
        );
        echo PHP_EOL;

        // Build hint cell lookup
        $hintCells = [];
        foreach ($this->hintPath as $cell) {
            $hintCells["{$cell['r']},{$cell['c']}"] = true;
        }

        // ── Board ────────────────────────────────────────────
        $pr = $this->player['r'];
        $pc = $this->player['c'];

        foreach ($this->grid as $r => $row) {
            $line = '';
            foreach ($row as $c => $tile) {
                $isPlayer = ($r === $pr && $c === $pc);
                $isHint   = isset($hintCells["{$r},{$c}"]) && !$isPlayer;

                if ($isPlayer) {
                    $line .= ColorText::color('🧍', 'white');
                } elseif ($isHint) {
                    $line .= ColorText::color('~~', 'cyan');
                } else {
                    $line .= $this->renderTile($tile);
                }
            }
            echo $line . PHP_EOL;
        }

        // ── Footer ───────────────────────────────────────────
        echo PHP_EOL;
        echo ColorText::color('[W/A/S/D] Move', 'green') . '  ' .
             ColorText::color('[H] Hint',        'cyan')  . '  ' .
             ColorText::color('[R] New maze',    'yellow') . '  ' .
             ColorText::color('[Q] Quit',         'red')   . PHP_EOL;
    }

    /**
     * Render a single tile as a 2-char coloured string.
     */
    private function renderTile(string $tile): string
    {
        return match ($tile) {
            self::T_WALL   => ColorText::color('██', 'white', 'white'),
            self::T_START  => ColorText::color('🏁', 'green'),
            self::T_EXIT   => ColorText::color('🚪', 'yellow'),
            self::T_CRUMB  => ColorText::color('· ', 'cyan'),
            default        => '  ',
        };
    }

    // ─────────────────────────────────────────────────────────
    // Stage clear / banner
    // ─────────────────────────────────────────────────────────

    private function onStageClear(): void
    {
        $elapsed = round(microtime(true) - $this->startTime);
        $mm      = str_pad((string)intdiv((int)$elapsed, 60), 2, '0', STR_PAD_LEFT);
        $ss      = str_pad((string)($elapsed % 60),           2, '0', STR_PAD_LEFT);

        $this->clearScreen();

        $art = <<<EOD

   ██████╗██╗     ███████╗ █████╗ ██████╗ ██╗
  ██╔════╝██║     ██╔════╝██╔══██╗██╔══██╗██║
  ██║     ██║     █████╗  ███████║██████╔╝██║
  ██║     ██║     ██╔══╝  ██╔══██║██╔══██╗╚═╝
  ╚██████╗███████╗███████╗██║  ██║██║  ██║██╗
   ╚═════╝╚══════╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝

EOD;
        Output::printLine(ColorText::color($art, 'green'));
        Output::printLine(ColorText::color(
            sprintf('  Stage %d cleared!   Moves: %d   Time: %s:%s', $this->stage, $this->moves, $mm, $ss),
            'yellow'
        ));
        echo PHP_EOL;
        Output::printLine(ColorText::color(' Press any key for the next maze… ', 'white', 'blue'));
        $this->readKey();
    }

    private function showBanner(): void
    {
        $this->clearScreen();

        $banner = <<<EOD

  ███╗   ███╗ █████╗ ███████╗███████╗
  ████╗ ████║██╔══██╗╚══███╔╝██╔════╝
  ██╔████╔██║███████║  ███╔╝ █████╗
  ██║╚██╔╝██║██╔══██║ ███╔╝  ██╔══╝
  ██║ ╚═╝ ██║██║  ██║███████╗███████╗
  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚══════╝

EOD;
        Output::printLine(ColorText::color($banner, 'cyan'));

        $size = sprintf('%d × %d', $this->cols, $this->rows);
        Output::printLine(ColorText::color("  Randomly generated maze  |  Size: {$size}  |  CloverFramework CLI", 'magenta'));
        echo PHP_EOL;

        // Controls card
        Output::printLine(ColorText::color('  ┌─── Controls ──────────────────────────────────┐', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('W/A/S/D  ↑←↓→', 'yellow') . '  Move                     ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('H', 'cyan')   . '              Toggle shortest-path hint   ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('R', 'green')  . '              Regenerate maze             ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('Q', 'red')    . '              Quit                        ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  └───────────────────────────────────────────────┘', 'white'));
        echo PHP_EOL;

        Output::printLine(
            ColorText::color('  Legend: ', 'white') .
            $this->renderTile(self::T_WALL)  . ' Wall  ' .
            $this->renderTile(self::T_START) . ' Start  ' .
            $this->renderTile(self::T_EXIT)  . ' Exit  ' .
            $this->renderTile(self::T_CRUMB) . ' Trail'
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
