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
 * SquidCommand — Lair of Squid
 *
 * Inspired by the HP 200LX built-in game (1994).
 * First-person ASCII raycaster maze.
 *
 * - Explore a randomly generated underwater labyrinth
 * - Collect scrambled password letters scattered around the maze
 * - Reach the exit door and unscramble the word to escape
 * - Avoid wandering squids — touching one is instant death
 *
 * Controls:
 *   ↑ / W       : Move forward
 *   ↓ / S       : Move backward
 *   ← / A       : Rotate left  (90°)
 *   → / D       : Rotate right (90°)
 *   Space       : Skip turn (squids still move!)
 *   M           : Toggle minimap
 *   Q / Esc     : Quit
 */
class SquidCommand implements CommandInterface
{
    // ──────────────────────────────────────────────────────────────
    // CommandInterface
    // ──────────────────────────────────────────────────────────────

    public array $arguments = [];
    public array $options   = [];

    public function getName(): string        { return 'game:squid'; }
    public function getDescription(): string { return 'Lair of Squid — HP 200LX first-person maze (1994)'; }

    public function configure(): void
    {
        $this->options[] = new InputOption('size',   'Maze size: small | normal | large', 'normal');
        $this->options[] = new InputOption('squids', 'Number of squids (1-8)', '3');
    }

    // ──────────────────────────────────────────────────────────────
    // Maze tile constants
    // ──────────────────────────────────────────────────────────────

    private const T_WALL   = '#';
    private const T_PATH   = ' ';
    private const T_EXIT   = 'E';
    private const T_LETTER = 'L';   // password letter pickup
    private const T_SQUID  = 'Q';

    // ──────────────────────────────────────────────────────────────
    // View dimensions
    // ──────────────────────────────────────────────────────────────

    private const VIEW_W   = 60;   // raycaster viewport columns
    private const VIEW_H   = 20;   // raycaster viewport rows
    private const FOV      = 1.0472;  // 60° in radians

    // ──────────────────────────────────────────────────────────────
    // Direction helpers  (angle → dx,dy for 4-way grid movement)
    // ──────────────────────────────────────────────────────────────

    // angle index: 0=East 1=South 2=West 3=North
    private const DIR_ANGLE = [0.0, M_PI / 2, M_PI, M_PI * 1.5];
    private const DIR_DX    = [ 1,  0, -1,  0];
    private const DIR_DY    = [ 0,  1,  0, -1];

    // ──────────────────────────────────────────────────────────────
    // Word list for passwords
    // ──────────────────────────────────────────────────────────────

    private const WORDS = [
        'SQUID', 'CORAL', 'OCEAN', 'DEPTH', 'ABYSS',
        'TRENCH', 'KRAKEN', 'ANCHOR', 'FATHOM', 'KELP',
        'CURRENT', 'SUBMARINE', 'PLANKTON', 'NAUTILUS',
    ];

    // ──────────────────────────────────────────────────────────────
    // Game state
    // ──────────────────────────────────────────────────────────────

    /** @var array<array<string>>  maze grid  [row][col] */
    private array $maze   = [];
    private int   $mRows  = 21;
    private int   $mCols  = 21;

    /** Player grid position and direction index (0-3) */
    private int $pgx = 1;   // grid X (col)
    private int $pgy = 1;   // grid Y (row)
    private int $pdir = 0;  // direction index

    /**
     * Squids: each entry ['gx'=>int, 'gy'=>int, 'dir'=>int]
     * @var array<array{gx:int,gy:int,dir:int}>
     */
    private array $squids      = [];
    private int   $squidCount  = 3;

    /** Password state */
    private string        $word        = '';
    private string        $scrambled   = '';
    private array         $found       = [];   // found[i] = true when letter i collected
    private array         $letterCells = [];   // [gx, gy] for each letter

    private int   $stage      = 1;
    private int   $moves      = 0;
    private bool  $gameOver   = false;
    private bool  $win        = false;
    private bool  $showMap    = true;
    private bool  $atExit     = false;
    private float $startTime  = 0.0;

    /** Z-buffer filled by raycast, used for sprite occlusion  */
    private array $zBuf = [];

    // ──────────────────────────────────────────────────────────────
    // Entry point
    // ──────────────────────────────────────────────────────────────

    public function run(Input $input): bool
    {
        $size = strtolower(trim($input->getOption('size') ?? 'normal'));
        [$this->mRows, $this->mCols] = match ($size) {
            'small' => [15, 15],
            'large' => [31, 31],
            default => [21, 21],
        };
        // Force odd
        if ($this->mRows % 2 === 0) $this->mRows++;
        if ($this->mCols % 2 === 0) $this->mCols++;

        $this->squidCount = max(1, min(8, (int)($input->getOption('squids') ?? 3)));

        $this->showBanner();
        Output::printLine(ColorText::color(' Press any key to dive in… ', 'white', 'blue'));
        $this->readKey();

        $this->newLevel();
        $this->gameLoop();

        return true;
    }

    // ──────────────────────────────────────────────────────────────
    // Game loop
    // ──────────────────────────────────────────────────────────────

    private function gameLoop(): void
    {
        while (true) {
            $this->render();

            if ($this->gameOver) {
                $this->showResult(false);
                $k = $this->readKey();
                if (strtolower($k) === 'r') { $this->newLevel(); continue; }
                $this->cleanExit(); return;
            }

            if ($this->win) {
                $this->showResult(true);
                $k = $this->readKey();
                if (strtolower($k) === 'r') { $this->stage++; $this->newLevel(); continue; }
                $this->cleanExit(); return;
            }

            // At exit: prompt for password entry
            if ($this->atExit && $this->allLettersFound()) {
                $this->handleExitPassword();
                continue;
            }

            $key = $this->readKey();
            $this->handleKey($key);
        }
    }

    private function handleKey(string $key): void
    {
        $moved = false;

        switch (strtolower($key)) {
            case 'w': case "\x1b[A":
                $moved = $this->tryMove($this->pgx + self::DIR_DX[$this->pdir],
                                        $this->pgy + self::DIR_DY[$this->pdir]);
                break;
            case 's': case "\x1b[B":
                $moved = $this->tryMove($this->pgx - self::DIR_DX[$this->pdir],
                                        $this->pgy - self::DIR_DY[$this->pdir]);
                break;
            case 'a': case "\x1b[D":
                $this->pdir = ($this->pdir + 3) % 4;
                $moved = true;
                break;
            case 'd': case "\x1b[C":
                $this->pdir = ($this->pdir + 1) % 4;
                $moved = true;
                break;
            case ' ':
                $moved = true;  // skip turn — squids still move
                break;
            case 'm':
                $this->showMap = !$this->showMap;
                break;
            case 'q': case "\x03": case "\x1b":
                $this->cleanExit(); exit(0);
        }

        if ($moved) {
            $this->moves++;
            $this->moveSquids();
            $this->checkSquidCollision();
            $this->checkPickup();
            $this->atExit = ($this->maze[$this->pgy][$this->pgx] === self::T_EXIT);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Level initialisation
    // ──────────────────────────────────────────────────────────────

    private function newLevel(): void
    {
        mt_srand((int)(microtime(true) * 1000) + $this->stage * 997);

        $this->moves     = 0;
        $this->gameOver  = false;
        $this->win       = false;
        $this->atExit    = false;
        $this->startTime = microtime(true);

        $this->buildMaze();
        $this->placeWord();
        $this->placeSquids();

        // Start position: (1,1), facing East
        $this->pgx  = 1;
        $this->pgy  = 1;
        $this->pdir = 0;
    }

    // ──────────────────────────────────────────────────────────────
    // Maze generation — DFS recursive backtracker
    // ──────────────────────────────────────────────────────────────

    private function buildMaze(): void
    {
        $R = $this->mRows;
        $C = $this->mCols;

        // Fill with walls
        for ($r = 0; $r < $R; $r++) {
            $this->maze[$r] = array_fill(0, $C, self::T_WALL);
        }

        // Carve passages
        $this->maze[1][1] = self::T_PATH;
        $stack   = [[1, 1]];
        $visited = ['1,1' => true];

        while (!empty($stack)) {
            [$cy, $cx] = end($stack);
            $nb = [];
            foreach ([[-2,0],[2,0],[0,-2],[0,2]] as [$dr,$dc]) {
                $ny = $cy + $dr; $nx = $cx + $dc;
                if ($ny > 0 && $ny < $R-1 && $nx > 0 && $nx < $C-1 && !isset($visited["{$ny},{$nx}"])) {
                    $nb[] = [$ny, $nx, $dr, $dc];
                }
            }
            if (empty($nb)) { array_pop($stack); continue; }
            [$ny, $nx, $dr, $dc] = $nb[mt_rand(0, count($nb)-1)];
            $this->maze[$cy + intdiv($dr,2)][$cx + intdiv($dc,2)] = self::T_PATH;
            $this->maze[$ny][$nx] = self::T_PATH;
            $visited["{$ny},{$nx}"] = true;
            $stack[] = [$ny, $nx];
        }

        // Exit at far corner
        $this->maze[$R-2][$C-2] = self::T_EXIT;
    }

    // ──────────────────────────────────────────────────────────────
    // Password / letter system
    // ──────────────────────────────────────────────────────────────

    private function placeWord(): void
    {
        // Pick word (longer word for higher stages)
        $pool = array_filter(self::WORDS, fn($w) => strlen($w) <= 4 + $this->stage);
        $pool = array_values($pool ?: self::WORDS);
        $this->word = $pool[mt_rand(0, count($pool) - 1)];

        // Scramble word
        $letters = str_split($this->word);
        do {
            shuffle($letters);
            $this->scrambled = implode('', $letters);
        } while ($this->scrambled === $this->word && strlen($this->word) > 1);

        $this->found       = array_fill(0, strlen($this->word), false);
        $this->letterCells = [];

        // Collect all open path cells (excluding start and exit area)
        $cells = [];
        for ($r = 1; $r < $this->mRows-1; $r++) {
            for ($c = 1; $c < $this->mCols-1; $c++) {
                if ($this->maze[$r][$c] === self::T_PATH &&
                    !($r <= 2 && $c <= 2) &&        // not near start
                    !($r >= $this->mRows-3 && $c >= $this->mCols-3)) {  // not near exit
                    $cells[] = [$r, $c];
                }
            }
        }

        shuffle($cells);
        $len = strlen($this->word);
        for ($i = 0; $i < $len && $i < count($cells); $i++) {
            [$lr, $lc]                 = $cells[$i];
            $this->maze[$lr][$lc]      = self::T_LETTER;
            $this->letterCells[$i]     = ['gx' => $lc, 'gy' => $lr, 'letter' => $this->word[$i]];
        }
    }

    private function checkPickup(): void
    {
        foreach ($this->letterCells as $i => $lc) {
            if (!$this->found[$i] && $lc['gx'] === $this->pgx && $lc['gy'] === $this->pgy) {
                $this->found[$i] = true;
                $this->maze[$this->pgy][$this->pgx] = self::T_PATH;
            }
        }
    }

    private function allLettersFound(): bool
    {
        return !in_array(false, $this->found, true);
    }

    // ──────────────────────────────────────────────────────────────
    // Squid placement & movement
    // ──────────────────────────────────────────────────────────────

    private function placeSquids(): void
    {
        $this->squids = [];
        $cells = [];
        for ($r = 1; $r < $this->mRows-1; $r++) {
            for ($c = 1; $c < $this->mCols-1; $c++) {
                if (($this->maze[$r][$c] === self::T_PATH) &&
                    ($r > 3 || $c > 3)) {   // keep start area clear
                    $cells[] = [$r, $c];
                }
            }
        }
        shuffle($cells);
        for ($i = 0; $i < $this->squidCount && $i < count($cells); $i++) {
            [$sr, $sc] = $cells[$i];
            $this->squids[] = ['gx' => $sc, 'gy' => $sr, 'dir' => mt_rand(0, 3)];
        }
    }

    private function moveSquids(): void
    {
        foreach ($this->squids as &$sq) {
            // Try to move forward; on failure pick a new random direction
            for ($attempt = 0; $attempt < 4; $attempt++) {
                $nx = $sq['gx'] + self::DIR_DX[$sq['dir']];
                $ny = $sq['gy'] + self::DIR_DY[$sq['dir']];
                $tile = $this->maze[$ny][$nx] ?? self::T_WALL;
                if ($tile !== self::T_WALL) {
                    $sq['gx'] = $nx;
                    $sq['gy'] = $ny;
                    break;
                }
                $sq['dir'] = mt_rand(0, 3);
            }
        }
        unset($sq);
    }

    private function checkSquidCollision(): void
    {
        foreach ($this->squids as $sq) {
            if ($sq['gx'] === $this->pgx && $sq['gy'] === $this->pgy) {
                $this->gameOver = true;
                return;
            }
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Movement
    // ──────────────────────────────────────────────────────────────

    private function tryMove(int $nx, int $ny): bool
    {
        if ($nx < 0 || $ny < 0 || $ny >= $this->mRows || $nx >= $this->mCols) {
            return false;
        }
        $tile = $this->maze[$ny][$nx];
        if ($tile === self::T_WALL) {
            return false;
        }
        $this->pgx = $nx;
        $this->pgy = $ny;
        return true;
    }

    // ──────────────────────────────────────────────────────────────
    // Exit password prompt
    // ──────────────────────────────────────────────────────────────

    private function handleExitPassword(): void
    {
        $this->render();
        echo PHP_EOL;
        Output::printLine(ColorText::color(' 🚪  EXIT REACHED ', 'white', 'blue'));
        Output::printLine(ColorText::color(
            "  Scrambled letters: " . ColorText::color($this->scrambled, 'yellow'),
            'white'
        ));

        // Show found letters
        $revealed = '';
        for ($i = 0; $i < strlen($this->word); $i++) {
            $revealed .= $this->found[$i] ? $this->word[$i] : '_';
        }
        Output::printLine(ColorText::color("  Password collected: " . ColorText::color($revealed, 'cyan'), 'white'));

        echo PHP_EOL;
        // Simple inline input (no raw mode needed here)
        if (function_exists('readline')) {
            $answer = strtoupper(trim(readline(ColorText::color('  Enter the password: ', 'green'))));
        } else {
            echo ColorText::color('  Enter the password: ', 'green');
            $answer = strtoupper(trim(fgets(STDIN)));
        }

        if ($answer === $this->word) {
            $this->win = true;
        } else {
            Output::printLine(ColorText::color("  ✗  Wrong! Keep exploring…", 'red'));
            sleep(1);
            $this->atExit = false;
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Raycasting  (DDA — Digital Differential Analysis)
    // ──────────────────────────────────────────────────────────────

    /**
     * Cast rays across VIEW_W columns and return a char buffer [row][col].
     * Also populates $this->zBuf[] (perpendicular distance per column).
     *
     * @return array<array<string>>
     */
    private function castRays(): array
    {
        $W   = self::VIEW_W;
        $H   = self::VIEW_H;
        $buf = [];
        for ($y = 0; $y < $H; $y++) {
            $buf[$y] = array_fill(0, $W, ' ');
        }
        $this->zBuf = array_fill(0, $W, 1e30);

        // Player floating position (centre of grid cell)
        $px  = $this->pgx + 0.5;
        $py  = $this->pgy + 0.5;
        $ang = self::DIR_ANGLE[$this->pdir];

        for ($x = 0; $x < $W; $x++) {
            // Ray angle within FOV
            $rayAng = $ang - self::FOV / 2.0 + $x * (self::FOV / $W);
            $rdx    = cos($rayAng);
            $rdy    = sin($rayAng);

            // DDA setup
            $mx = (int)floor($px);
            $my = (int)floor($py);

            $ddx = ($rdx == 0.0) ? 1e30 : abs(1.0 / $rdx);
            $ddy = ($rdy == 0.0) ? 1e30 : abs(1.0 / $rdy);

            if ($rdx < 0) { $sx = -1; $sdx = ($px - $mx) * $ddx; }
            else          { $sx =  1; $sdx = ($mx + 1 - $px) * $ddx; }

            if ($rdy < 0) { $sy = -1; $sdy = ($py - $my) * $ddy; }
            else          { $sy =  1; $sdy = ($my + 1 - $py) * $ddy; }

            $side = 0;
            $isExit = false;
            for ($step = 0; $step < 64; $step++) {
                if ($sdx < $sdy) { $sdx += $ddx; $mx += $sx; $side = 0; }
                else             { $sdy += $ddy; $my += $sy; $side = 1; }
                $tile = $this->maze[$my][$mx] ?? self::T_WALL;
                if ($tile === self::T_WALL || $tile === self::T_EXIT) {
                    $isExit = ($tile === self::T_EXIT);
                    break;
                }
            }

            // Perpendicular distance (removes fish-eye distortion)
            $rawDist = ($side === 0) ? $sdx - $ddx : $sdy - $ddy;
            $rawDist = max($rawDist, 0.01);
            $perpDist = $rawDist * cos($rayAng - $ang);
            $perpDist = max($perpDist, 0.01);
            $this->zBuf[$x] = $perpDist;

            // Wall strip height (terminal chars are ~2× taller than wide → * 0.55)
            $wallH  = (int)($H / $perpDist * 0.55);
            $half   = intdiv($H, 2);
            $t      = max(0,     $half - intdiv($wallH, 2));
            $b      = min($H-1,  $half + intdiv($wallH, 2));

            $wCh = $isExit
                ? $this->exitGlyph($perpDist)
                : $this->wallGlyph($perpDist, $side);

            for ($y = $t; $y <= $b; $y++) {
                $buf[$y][$x] = $wCh;
            }
            // Ceiling — water texture
            for ($y = 0; $y < $t; $y++) {
                $buf[$y][$x] = ($y < intdiv($H, 5)) ? '~' : ' ';
            }
            // Floor — seabed texture
            for ($y = $b+1; $y < $H; $y++) {
                $buf[$y][$x] = ($y >= $H - intdiv($H, 5)) ? ',' : '.';
            }
        }

        return $buf;
    }

    /**
     * Wall glyph based on perpendicular distance and wall side (N/S vs E/W).
     */
    private function wallGlyph(float $d, int $side): string
    {
        if ($side === 1) {
            // E/W walls — slightly darker
            if ($d < 1.2) return '▓';
            if ($d < 2.0) return '▒';
            if ($d < 3.5) return '░';
            return ':';
        }
        // N/S walls
        if ($d < 1.2) return '█';
        if ($d < 2.0) return '▓';
        if ($d < 3.5) return '▒';
        if ($d < 6.0) return '░';
        return '.';
    }

    private function exitGlyph(float $d): string
    {
        if ($d < 1.5) return 'E';
        if ($d < 3.0) return '|';
        return ':';
    }

    // ──────────────────────────────────────────────────────────────
    // Sprite rendering  (squids & letter pickups on the 3D view)
    // ──────────────────────────────────────────────────────────────

    /**
     * Render all sprites onto an existing char buffer.
     *
     * @param array<array<string>> $buf  (modified in place)
     */
    private function renderSprites(array &$buf): void
    {
        $W   = self::VIEW_W;
        $H   = self::VIEW_H;
        $px  = $this->pgx + 0.5;
        $py  = $this->pgy + 0.5;
        $ang = self::DIR_ANGLE[$this->pdir];

        // Collect all visible sprites: squids + uncollected letters
        $sprites = [];
        foreach ($this->squids as $sq) {
            $sprites[] = ['x' => $sq['gx'] + 0.5, 'y' => $sq['gy'] + 0.5, 'type' => 'squid'];
        }
        foreach ($this->letterCells as $i => $lc) {
            if (!$this->found[$i]) {
                $sprites[] = ['x' => $lc['gx'] + 0.5, 'y' => $lc['gy'] + 0.5,
                              'type' => 'letter', 'char' => $lc['letter']];
            }
        }

        // Sort sprites by distance (far → near so near overwrites)
        usort($sprites, function ($a, $b) use ($px, $py) {
            $da = ($a['x']-$px)**2 + ($a['y']-$py)**2;
            $db = ($b['x']-$px)**2 + ($b['y']-$py)**2;
            return $db <=> $da;
        });

        foreach ($sprites as $sp) {
            $dx = $sp['x'] - $px;
            $dy = $sp['y'] - $py;

            // Transform into camera space
            //$invDet = 1.0 / (cos($ang) * (-sin($ang)) - (-sin($ang)) * cos($ang));
            // Simplified: use angle difference
            $sprAng   = atan2($dy, $dx);
            $relAng   = $sprAng - $ang;
            // Normalise to [-π, π]
            while ($relAng >  M_PI) $relAng -= 2*M_PI;
            while ($relAng < -M_PI) $relAng += 2*M_PI;

            if (abs($relAng) > self::FOV * 0.6) continue;   // outside FOV

            $dist = sqrt($dx*$dx + $dy*$dy);
            if ($dist < 0.3) continue;

            // Screen X of sprite centre
            $screenX = (int)(($relAng / (self::FOV / 2) + 1.0) * 0.5 * $W);

            // Sprite height (same formula as wall)
            $sprH  = max(1, (int)($H / $dist * 0.5));
            $halfH = intdiv($sprH, 2);
            $sprW  = max(1, (int)($sprH * 0.55));

            $top  = max(0,     intdiv($H, 2) - $halfH);
            $bot  = min($H-1,  intdiv($H, 2) + $halfH);
            $left = $screenX - intdiv($sprW, 2);
            $right= $left + $sprW - 1;

            for ($sx = $left; $sx <= $right; $sx++) {
                if ($sx < 0 || $sx >= $W) continue;
                if ($dist >= $this->zBuf[$sx]) continue;  // occluded by wall

                for ($sy = $top; $sy <= $bot; $sy++) {
                    $buf[$sy][$sx] = $this->spriteGlyph($sp, $dist, $sy, $top, $bot);
                }
            }
        }
    }

    private function spriteGlyph(array $sp, float $dist, int $sy, int $top, int $bot): string
    {
        $mid   = intdiv($top + $bot, 2);
        $isTop = $sy <= $mid;

        if ($sp['type'] === 'squid') {
            if ($dist < 2.0) return $isTop ? ($sy === $top ? 'o' : 'Q') : ($sy === $bot ? '^' : '|');
            if ($dist < 4.0) return $isTop ? 'Q' : '|';
            if ($dist < 7.0) return 'q';
            return '·';
        }
        // letter pickup
        return $sp['char'] ?? '?';
    }

    // ──────────────────────────────────────────────────────────────
    // Minimap
    // ──────────────────────────────────────────────────────────────

    /**
     * Build a small ASCII minimap centred on the player.
     * Returns an array of coloured strings, one per row.
     *
     * @return array<string>
     */
    private function buildMinimap(): array
    {
        $size  = 11;   // map viewport size (cells), must be odd
        $half  = intdiv($size, 2);
        $lines = [];

        for ($dy = -$half; $dy <= $half; $dy++) {
            $line = '';
            for ($dx = -$half; $dx <= $half; $dx++) {
                $mx = $this->pgx + $dx;
                $my = $this->pgy + $dy;
                $isPlayer = ($dx === 0 && $dy === 0);
                $isSquid  = false;
                foreach ($this->squids as $sq) {
                    if ($sq['gx'] === $mx && $sq['gy'] === $my) { $isSquid = true; break; }
                }

                if ($isPlayer) {
                    $arrow = ['→', '↓', '←', '↑'][$this->pdir];
                    $line .= ColorText::color($arrow, 'cyan');
                } elseif ($isSquid) {
                    $line .= ColorText::color('Q', 'red');
                } elseif ($mx < 0 || $my < 0 || $my >= $this->mRows || $mx >= $this->mCols) {
                    $line .= ColorText::color('█', 'white');
                } else {
                    $tile = $this->maze[$my][$mx];
                    $line .= match($tile) {
                        self::T_WALL   => ColorText::color('█', 'white'),
                        self::T_EXIT   => ColorText::color('E', 'yellow'),
                        self::T_LETTER => ColorText::color('*', 'green'),
                        default        => ' ',
                    };
                }
            }
            $lines[] = $line;
        }
        return $lines;
    }

    // ──────────────────────────────────────────────────────────────
    // Full render
    // ──────────────────────────────────────────────────────────────

    private function render(): void
    {
        $this->clearScreen();

        // ── Header ────────────────────────────────────────────
        $elapsed = (int)round(microtime(true) - $this->startTime);
        $mm = str_pad((string)intdiv($elapsed, 60), 2, '0', STR_PAD_LEFT);
        $ss = str_pad((string)($elapsed % 60),      2, '0', STR_PAD_LEFT);

        // Password progress bar
        $pbar = '';
        for ($i = 0; $i < strlen($this->word); $i++) {
            $pbar .= $this->found[$i]
                ? ColorText::color($this->word[$i], 'green')
                : ColorText::color('_', 'white');
        }

        Output::printLine(
            ColorText::color(' 🦑 LAIR OF SQUID ', 'white', 'blue') . ' ' .
            ColorText::color(" Stage {$this->stage} ", 'black', 'cyan') . '  ' .
            ColorText::color(" ⏱ {$mm}:{$ss} ", 'magenta') . '  ' .
            ColorText::color(" Moves: {$this->moves} ", 'yellow') . '  ' .
            ColorText::color(' Password: ', 'white') . $pbar . ' ' .
            ColorText::color(" [{$this->scrambled}] ", 'white')
        );
        echo PHP_EOL;

        // ── 3D view ───────────────────────────────────────────
        $buf = $this->castRays();
        $this->renderSprites($buf);

        $mapLines = $this->showMap ? $this->buildMinimap() : null;
        $mapOffset = 3;   // row to start the minimap (leave space for header rows)

        for ($y = 0; $y < self::VIEW_H; $y++) {
            // 3D view line
            $line = '';
            foreach ($buf[$y] as $ch) {
                $line .= $this->colorViewChar($ch);
            }

            // Attach minimap to the right if enabled
            if ($mapLines !== null) {
                $mapRow = $y - $mapOffset;
                if ($mapRow >= 0 && $mapRow < count($mapLines)) {
                    $line .= '  ' . $mapLines[$mapRow];
                }
            }

            echo $line . PHP_EOL;
        }

        // ── Status bar ────────────────────────────────────────
        echo PHP_EOL;

        $dirLabel = ['East ›', 'South ↓', 'West ‹', 'North ↑'][$this->pdir];
        $squidLeft = count(array_filter($this->squids, fn($s) => true));
        $letLeft   = count(array_filter($this->found, fn($f) => !$f));

        echo ColorText::color(" Facing: {$dirLabel}", 'cyan') . '  ' .
             ColorText::color(" Squids: {$squidLeft} ", 'red') . '  ' .
             ColorText::color(" Letters left: {$letLeft} ", 'green') . PHP_EOL;

        echo PHP_EOL;
        echo ColorText::color('[W/S] Move', 'green')     . '  ' .
             ColorText::color('[A/D] Turn', 'cyan')      . '  ' .
             ColorText::color('[Space] Wait', 'yellow')  . '  ' .
             ColorText::color('[M] Map', 'white')        . '  ' .
             ColorText::color('[Q] Quit', 'red')         . PHP_EOL;

        if ($this->atExit && !$this->allLettersFound()) {
            echo PHP_EOL;
            echo ColorText::color(
                " 🚪  Exit found! Collect all " . strlen($this->word) . " password letters first!",
                'yellow'
            ) . PHP_EOL;
        }
    }

    /**
     * Map a raw view-buffer character to a coloured terminal string.
     */
    private function colorViewChar(string $ch): string
    {
        return match($ch) {
            '█'     => ColorText::color('█', 'white'),
            '▓'     => ColorText::color('▓', 'white'),
            '▒'     => ColorText::color('▒', 'white'),
            '░'     => ColorText::color('░', 'white'),
            ':'     => ColorText::color(':', 'white'),
            '.'     => ColorText::color('.', 'white'),
            '~'     => ColorText::color('~', 'blue'),
            ','     => ColorText::color(',', 'yellow'),
            'E','|' => ColorText::color($ch, 'yellow'),
            'Q','q','o','·' => ColorText::color($ch, 'red'),
            '^' => ColorText::color($ch, 'red'),
            'A','B','C','D','F','G','H','I','J','K','L','M',
            'N','O','P','R','S','T','U','V','W','X','Y','Z'
                    => ColorText::color($ch, 'green'),
            default => $ch,
        };
    }

    // ──────────────────────────────────────────────────────────────
    // Result screens
    // ──────────────────────────────────────────────────────────────

    private function showResult(bool $won): void
    {
        echo PHP_EOL;
        if ($won) {
            $art = <<<'EOD'
  ███████╗███████╗ ██████╗ █████╗ ██████╗ ███████╗██████╗ ██╗
  ██╔════╝██╔════╝██╔════╝██╔══██╗██╔══██╗██╔════╝██╔══██╗██║
  █████╗  ███████╗██║     ███████║██████╔╝█████╗  ██║  ██║██║
  ██╔══╝  ╚════██║██║     ██╔══██║██╔═══╝ ██╔══╝  ██║  ██║╚═╝
  ███████╗███████║╚██████╗██║  ██║██║     ███████╗██████╔╝██╗
  ╚══════╝╚══════╝ ╚═════╝╚═╝  ╚═╝╚═╝     ╚══════╝╚═════╝ ╚═╝
EOD;
            Output::printLine(ColorText::color($art, 'green'));
            $elapsed = (int)round(microtime(true) - $this->startTime);
            Output::printLine(ColorText::color(
                sprintf('  🎉  Stage %d cleared!  Password: %s  Moves: %d  Time: %02d:%02d',
                    $this->stage, $this->word, $this->moves,
                    intdiv($elapsed, 60), $elapsed % 60),
                'yellow'
            ));
        } else {
            $art = <<<'EOD'
  ██████╗ ███████╗██╗   ██╗ ██████╗ ██╗   ██╗██████╗ ███████╗██████╗ ██╗
  ██╔══██╗██╔════╝██║   ██║██╔═══██╗██║   ██║██╔══██╗██╔════╝██╔══██╗██║
  ██║  ██║█████╗  ██║   ██║██║   ██║██║   ██║██████╔╝█████╗  ██║  ██║██║
  ██║  ██║██╔══╝  ╚██╗ ██╔╝██║   ██║██║   ██║██╔══██╗██╔══╝  ██║  ██║╚═╝
  ██████╔╝███████╗ ╚████╔╝ ╚██████╔╝╚██████╔╝██║  ██║███████╗██████╔╝██╗
  ╚═════╝ ╚══════╝  ╚═══╝   ╚═════╝  ╚═════╝ ╚═╝  ╚═╝╚══════╝╚═════╝ ╚═╝
EOD;
            Output::printLine(ColorText::color($art, 'red'));
            Output::printLine(ColorText::color('  💀  A squid got you!', 'yellow'));
        }
        echo PHP_EOL;
        Output::printLine(
            ColorText::color(' [R] Play again ', 'white', 'blue') . '  ' .
            ColorText::color(' [Any] Quit ',     'white', 'red')
        );
    }

    // ──────────────────────────────────────────────────────────────
    // Banner
    // ──────────────────────────────────────────────────────────────

    private function showBanner(): void
    {
        $this->clearScreen();
        $banner = <<<'EOD'

  ██╗      █████╗ ██╗██████╗      ██████╗ ███████╗
  ██║     ██╔══██╗██║██╔══██╗    ██╔═══██╗██╔════╝
  ██║     ███████║██║██████╔╝    ██║   ██║█████╗
  ██║     ██╔══██║██║██╔══██╗    ██║   ██║██╔══╝
  ███████╗██║  ██║██║██║  ██║    ╚██████╔╝██║
  ╚══════╝╚═╝  ╚═╝╚═╝╚═╝  ╚═╝    ╚═════╝ ╚═╝

  ███████╗ ██████╗ ██╗   ██╗██╗██████╗
  ██╔════╝██╔═══██╗██║   ██║██║██╔══██╗
  ███████╗██║   ██║██║   ██║██║██║  ██║
  ╚════██║██║▄▄ ██║██║   ██║██║██║  ██║
  ███████║╚██████╔╝╚██████╔╝██║██████╔╝
  ╚══════╝ ╚══▀▀═╝  ╚═════╝ ╚═╝╚═════╝

EOD;
        Output::printLine(ColorText::color($banner, 'cyan'));
        Output::printLine(ColorText::color(
            sprintf('  HP 200LX Classic (1994 inspired)  |  Maze: %d×%d  |  Squids: %d  |  CloverFramework CLI',
                $this->mCols, $this->mRows, $this->squidCount),
            'magenta'
        ));
        echo PHP_EOL;

        Output::printLine(ColorText::color('  ┌─── Controls ──────────────────────────────────────────┐', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('W / ↑', 'yellow') . '        Move forward                           ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('S / ↓', 'yellow') . '        Move backward                          ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('A / ←', 'cyan')   . '        Rotate left (90°)                      ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('D / →', 'cyan')   . '        Rotate right (90°)                     ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('Space', 'white')  . '        Skip turn (squids still move!)         ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('M    ', 'green')  . '        Toggle minimap                         ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . '  ' . ColorText::color('Q    ', 'red')    . '        Quit                                   ' . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  └───────────────────────────────────────────────────────┘', 'white'));
        echo PHP_EOL;

        Output::printLine(ColorText::color('  Objective:', 'white'));
        Output::printLine('    1. ' . ColorText::color('Explore the maze', 'yellow') . ' and collect all ' . ColorText::color('password letters', 'green') . ' (shown as * on minimap)');
        Output::printLine('    2. Find the ' . ColorText::color('EXIT door', 'yellow') . ' and enter the unscrambled password');
        Output::printLine('    3. ' . ColorText::color('Avoid squids', 'red') . ' — they wander every turn you take!');
        echo PHP_EOL;
    }

    // ──────────────────────────────────────────────────────────────
    // Terminal helpers
    // ──────────────────────────────────────────────────────────────

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
            if ($next === '[') { $code = fread(STDIN, 1); $key = "\x1b[{$code}"; }
            else               { $key = $next; }
        }
        if ($stty !== null) system("stty '{$stty}'");
        return $key;
    }

    private function clearScreen(): void
    {
        system(DIRECTORY_SEPARATOR === '/' ? 'clear' : 'cls');
    }

    private function cleanExit(): void
    {
        $this->clearScreen();
        Output::printLine(ColorText::color(' 👋  The squid remain… for now. ', 'white', 'blue'));
        echo PHP_EOL;
    }
}
