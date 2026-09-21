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
 * Sokoban Command Class
 *
 * Interactive CLI Sokoban puzzle game.
 * Push all boxes (📦) onto target spots (🎯) to clear each stage.
 *
 * Controls:
 *   W / ↑  : Move Up
 *   S / ↓  : Move Down
 *   A / ←  : Move Left
 *   D / →  : Move Right
 *   U      : Undo last move
 *   R      : Restart current level
 *   Q      : Quit game
 */
class SokobanCommand implements CommandInterface
{
    /**
     * @var array Command arguments.
     */
    public array $arguments = [];

    /**
     * @var array Command options.
     */
    public array $options = [];

    // ──────────────────────────────────────────
    // Tile constants
    // ──────────────────────────────────────────

    private const TILE_EMPTY       = ' ';
    private const TILE_WALL        = '#';
    private const TILE_PLAYER      = '@';
    private const TILE_PLAYER_GOAL = '+';
    private const TILE_BOX         = '$';
    private const TILE_BOX_GOAL    = '*';
    private const TILE_GOAL        = '.';

    // ──────────────────────────────────────────
    // Game state
    // ──────────────────────────────────────────

    /** @var array<array<string>> Current board tiles. */
    private array $board = [];

    /** @var array{row: int, col: int} Player position. */
    private array $player = ['row' => 0, 'col' => 0];

    /** @var int Current level index (0-based). */
    private int $currentLevel = 0;

    /** @var int Total moves taken in current level. */
    private int $moves = 0;

    /** @var int Total pushes in current level. */
    private int $pushes = 0;

    /** @var array History stack for undo. Each entry: [board, player, moves, pushes] */
    private array $history = [];

    /** @var int Max undo history depth. */
    private const MAX_HISTORY = 100;

    // ──────────────────────────────────────────
    // Built-in levels (XSB format)
    // ──────────────────────────────────────────

    /** @var array<array<string>> Level definitions in XSB notation. */
    private array $levels = [
        // Level 1 — Tutorial
        [
            "    #####",
            "    #   #",
            "    #$  #",
            "  ###  $##",
            "  #  $ $ #",
            "### # ## #   ######",
            "#   # ## #####  ..#",
            "# $  $          ..#",
            "##### ### #@##  ..#",
            "    #     #########",
            "    #######",
        ],
        // Level 2 — Warehouse
        [
            "############",
            "#..  #     ###",
            "#..  # $  $  #",
            "#..  #$####  #",
            "#..    @ ##  #",
            "#..  # #  $ ##",
            "######$  $  #",
            "    #  ###  #",
            "    ########",
        ],
        // Level 3 — Cross
        [
            "      ####",
            " #####  #",
            "##  $ $ #",
            "#  #$## #",
            "# . .#@ #",
            "## ## # ##",
            " # .$.  #",
            " #  #   #",
            " #########",
        ],
        // Level 4 — Classic
        [
            "####",
            "# .#",
            "#  ###",
            "#*@  #",
            "#  $ #",
            "#  ###",
            "####",
        ],
        // Level 5 — Symmetry
        [
            "  #######",
            "  # . . #",
            "### $#$ ###",
            "#  $   $  #",
            "# . #@# . #",
            "#  $   $  #",
            "### $#$ ###",
            "  # . . #",
            "  #######",
        ],
    ];

    // ──────────────────────────────────────────
    // CommandInterface implementation
    // ──────────────────────────────────────────

    /**
     * Get the command name.
     */
    public function getName(): string
    {
        return 'game:sokoban';
    }

    /**
     * Get the command description.
     */
    public function getDescription(): string
    {
        return 'Play Sokoban — push boxes onto all goal tiles to advance';
    }

    /**
     * Configure command options.
     */
    public function configure(): void
    {
        $this->options[] = new InputOption('level', 'Starting level (1-' . count($this->levels) . ')', '1');
    }

    /**
     * Entry point — run the game loop.
     *
     * @param Input $input CLI input wrapper.
     * @return bool True on clean exit.
     */
    public function run(Input $input): bool
    {
        // Honour --level option if provided
        $startLevel = (int)($input->getOption('level') ?? 1);
        $this->currentLevel = max(0, min($startLevel - 1, count($this->levels) - 1));

        $this->showBanner();
        $this->showControls();

        Output::printLine(
            ColorText::color(' Press any key to start… ', 'white', 'blue')
        );
        $this->readKey(); // wait for keypress

        $this->loadLevel($this->currentLevel);
        $this->gameLoop();

        return true;
    }

    // ──────────────────────────────────────────
    // Game loop
    // ──────────────────────────────────────────

    /**
     * Main game loop — render → input → update → repeat.
     */
    private function gameLoop(): void
    {
        while (true) {
            $this->render();

            if ($this->isLevelComplete()) {
                $this->onLevelComplete();

                $this->currentLevel++;
                if ($this->currentLevel >= count($this->levels)) {
                    $this->showGameClear();
                    break;
                }

                $this->loadLevel($this->currentLevel);
                continue;
            }

            $key = $this->readKey();

            switch (strtolower($key)) {
                case 'w': case "\x1b[A": // Up
                    $this->tryMove(-1, 0);
                    break;

                case 's': case "\x1b[B": // Down
                    $this->tryMove(1, 0);
                    break;

                case 'd': case "\x1b[C": // Right
                    $this->tryMove(0, 1);
                    break;

                case 'a': case "\x1b[D": // Left
                    $this->tryMove(0, -1);
                    break;

                case 'u':
                    $this->undoMove();
                    break;

                case 'r':
                    $this->loadLevel($this->currentLevel);
                    break;

                case 'q': case "\x03": // q or Ctrl+C
                    $this->clearScreen();
                    Output::printLine(ColorText::color(' 👋  Thanks for playing Sokoban! ', 'white', 'blue'));
                    echo PHP_EOL;
                    return;
            }
        }
    }

    // ──────────────────────────────────────────
    // Level management
    // ──────────────────────────────────────────

    /**
     * Load a level by index, resetting state.
     *
     * @param int $index Zero-based level index.
     */
    private function loadLevel(int $index): void
    {
        $this->board   = [];
        $this->history = [];
        $this->moves   = 0;
        $this->pushes  = 0;

        $rawRows = $this->levels[$index];

        // Normalise row widths
        $maxWidth = max(array_map('strlen', $rawRows));

        foreach ($rawRows as $r => $row) {
            $row = str_pad($row, $maxWidth);
            $this->board[$r] = str_split($row);

            foreach ($this->board[$r] as $c => $tile) {
                if ($tile === self::TILE_PLAYER || $tile === self::TILE_PLAYER_GOAL) {
                    $this->player = ['row' => $r, 'col' => $c];
                }
            }
        }
    }

    // ──────────────────────────────────────────
    // Movement & logic
    // ──────────────────────────────────────────

    /**
     * Attempt to move the player by (dRow, dCol).
     * Handles box pushing and goal tracking.
     *
     * @param int $dRow Row delta (-1 up, +1 down).
     * @param int $dCol Col delta (-1 left, +1 right).
     */
    private function tryMove(int $dRow, int $dCol): void
    {
        $pr  = $this->player['row'];
        $pc  = $this->player['col'];
        $nr  = $pr + $dRow;
        $nc  = $pc + $dCol;

        if (!$this->inBounds($nr, $nc)) {
            return;
        }

        $nextTile = $this->board[$nr][$nc];

        // Moving into a wall — abort
        if ($nextTile === self::TILE_WALL) {
            return;
        }

        $pushed = false;

        // Moving into a box or box-on-goal — try to push it
        if ($nextTile === self::TILE_BOX || $nextTile === self::TILE_BOX_GOAL) {
            $br = $nr + $dRow;
            $bc = $nc + $dCol;

            if (!$this->inBounds($br, $bc)) {
                return;
            }

            $beyondTile = $this->board[$br][$bc];

            if ($beyondTile === self::TILE_WALL ||
                $beyondTile === self::TILE_BOX  ||
                $beyondTile === self::TILE_BOX_GOAL) {
                return; // box is blocked
            }

            // Save state before mutation
            $this->pushHistory();

            // Move box
            $this->board[$br][$bc] = ($beyondTile === self::TILE_GOAL)
                ? self::TILE_BOX_GOAL
                : self::TILE_BOX;

            // Clear box's old cell
            $this->board[$nr][$nc] = ($nextTile === self::TILE_BOX_GOAL)
                ? self::TILE_GOAL
                : self::TILE_EMPTY;

            $pushed = true;
            $this->pushes++;
        } else {
            $this->pushHistory();
        }

        // Move player
        $currentTile = $this->board[$pr][$pc];
        $this->board[$pr][$pc] = ($currentTile === self::TILE_PLAYER_GOAL)
            ? self::TILE_GOAL
            : self::TILE_EMPTY;

        $this->board[$nr][$nc] = ($this->board[$nr][$nc] === self::TILE_GOAL)
            ? self::TILE_PLAYER_GOAL
            : self::TILE_PLAYER;

        $this->player = ['row' => $nr, 'col' => $nc];
        $this->moves++;
    }

    /**
     * Undo the last move.
     */
    private function undoMove(): void
    {
        if (empty($this->history)) {
            return;
        }

        $snapshot         = array_pop($this->history);
        $this->board      = $snapshot['board'];
        $this->player     = $snapshot['player'];
        $this->moves      = $snapshot['moves'];
        $this->pushes     = $snapshot['pushes'];
    }

    /**
     * Push current state onto the undo history stack.
     */
    private function pushHistory(): void
    {
        if (count($this->history) >= self::MAX_HISTORY) {
            array_shift($this->history);
        }

        $this->history[] = [
            'board'  => array_map(fn($row) => $row, $this->board),
            'player' => $this->player,
            'moves'  => $this->moves,
            'pushes' => $this->pushes,
        ];
    }

    /**
     * Check if all boxes are on goal tiles.
     */
    private function isLevelComplete(): bool
    {
        foreach ($this->board as $row) {
            if (in_array(self::TILE_BOX, $row, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check whether (row, col) is within board bounds.
     */
    private function inBounds(int $row, int $col): bool
    {
        return isset($this->board[$row][$col]);
    }

    // ──────────────────────────────────────────
    // Rendering
    // ──────────────────────────────────────────

    /**
     * Render the current game state to the terminal.
     */
    private function render(): void
    {
        $this->clearScreen();

        // ── Header ──────────────────────────────────
        $levelLabel  = ColorText::color(' SOKOBAN ', 'white', 'blue');
        $levelNum    = ColorText::color(
            sprintf(' Stage %d / %d ', $this->currentLevel + 1, count($this->levels)),
            'black', 'cyan'
        );
        $movesLabel  = ColorText::color(
            sprintf(' Moves: %d  Pushes: %d ', $this->moves, $this->pushes),
            'yellow'
        );
        Output::printLine("{$levelLabel} {$levelNum}  {$movesLabel}");
        echo PHP_EOL;

        // ── Board ────────────────────────────────────
        foreach ($this->board as $row) {
            $line = '';
            foreach ($row as $tile) {
                $line .= $this->renderTile($tile);
            }
            echo $line . PHP_EOL;
        }

        // ── Footer ───────────────────────────────────
        echo PHP_EOL;
        $hint = ColorText::color('[W/A/S/D] Move', 'green') . '  ' .
                ColorText::color('[U] Undo', 'cyan')         . '  ' .
                ColorText::color('[R] Restart', 'yellow')    . '  ' .
                ColorText::color('[Q] Quit', 'red');
        echo $hint . PHP_EOL;
    }

    /**
     * Map a tile character to a coloured terminal glyph.
     *
     * @param string $tile One of the TILE_* constants.
     * @return string ANSI-coloured string.
     */
    private function renderTile(string $tile): string
    {
        return match ($tile) {
            self::TILE_WALL        => ColorText::color('▓▓', 'white', 'white'),      // wall  — solid white block
            self::TILE_PLAYER      => ColorText::color('🧍', 'white'),               // player
            self::TILE_PLAYER_GOAL => ColorText::color('😎', 'white'),               // player on goal
            self::TILE_BOX         => ColorText::color('📦', 'yellow'),             // box
            self::TILE_BOX_GOAL    => ColorText::color('✅', 'green'),              // box on goal
            self::TILE_GOAL        => ColorText::color('🎯', 'red'),                // empty goal
            default                => '  ',                                          // empty / space
        };
    }

    // ──────────────────────────────────────────
    // UI helpers
    // ──────────────────────────────────────────

    /**
     * Display the ASCII banner.
     */
    private function showBanner(): void
    {
        $this->clearScreen();

        $banner = <<<EOD

  ███████╗ ██████╗ ██╗  ██╗ ██████╗ ██████╗  █████╗ ███╗  ██╗
  ██╔════╝██╔═══██╗██║ ██╔╝██╔═══██╗██╔══██╗██╔══██╗████╗ ██║
  ███████╗██║   ██║█████╔╝ ██║   ██║██████╔╝███████║██╔██╗██║
  ╚════██║██║   ██║██╔═██╗ ██║   ██║██╔══██╗██╔══██║██║╚████║
  ███████║╚██████╔╝██║  ██╗╚██████╔╝██████╔╝██║  ██║██║ ╚███║
  ╚══════╝ ╚═════╝ ╚═╝  ╚═╝ ╚═════╝ ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚══╝

EOD;

        Output::printLine(ColorText::color($banner, 'cyan'));
        Output::printLine(
            ColorText::color(
                sprintf('  %d stages  |  CloverFramework CLI Game', count($this->levels)),
                'magenta'
            )
        );
        echo PHP_EOL;
    }

    /**
     * Print the control-key reference card.
     */
    private function showControls(): void
    {
        $pad  = '  ';
        $sep  = ColorText::color(' │ ', 'white');

        Output::printLine(ColorText::color('  ┌─── Controls ──────────────────────────┐', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . $pad
            . ColorText::color('W / ↑', 'yellow')  . ' Move up      '
            . ColorText::color('S / ↓', 'yellow')  . ' Move down'
            . ColorText::color('  │', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . $pad
            . ColorText::color('A / ←', 'yellow')  . ' Move left    '
            . ColorText::color('D / →', 'yellow')  . ' Move right'
            . ColorText::color(' │', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . $pad
            . ColorText::color('U', 'cyan')         . '     Undo         '
            . ColorText::color('R', 'green')        . '     Restart level'
            . ColorText::color(' │', 'white'));
        Output::printLine(ColorText::color('  │', 'white') . $pad
            . ColorText::color('Q', 'red')          . '     Quit'
            . str_repeat(' ', 28)
            . ColorText::color('│', 'white'));
        Output::printLine(ColorText::color('  └───────────────────────────────────────┘', 'white'));
        echo PHP_EOL;

        Output::printLine(
            ColorText::color('  Legend: ', 'white') .
            $this->renderTile(self::TILE_PLAYER) . ' Player  ' .
            $this->renderTile(self::TILE_BOX)    . ' Box  '    .
            $this->renderTile(self::TILE_GOAL)   . ' Goal  '   .
            $this->renderTile(self::TILE_BOX_GOAL) . ' Solved'
        );
        echo PHP_EOL;
    }

    /**
     * Show the level-clear celebration screen.
     */
    private function onLevelComplete(): void
    {
        $this->render();
        echo PHP_EOL;
        Output::printLine(
            ColorText::color(' ✔  STAGE CLEAR! ', 'white', 'green') .
            ColorText::color(
                sprintf('  Moves: %d   Pushes: %d ', $this->moves, $this->pushes),
                'green'
            )
        );
        Output::printLine(ColorText::color(' Press any key for the next stage… ', 'white', 'blue'));
        $this->readKey();
    }

    /**
     * Show the all-stages-cleared screen.
     */
    private function showGameClear(): void
    {
        $this->clearScreen();

        $art = <<<EOD

  ██████╗  █████╗ ███╗   ███╗███████╗     ██████╗██╗     ███████╗ █████╗ ██████╗ ██╗
 ██╔════╝ ██╔══██╗████╗ ████║██╔════╝    ██╔════╝██║     ██╔════╝██╔══██╗██╔══██╗██║
 ██║  ███╗███████║██╔████╔██║█████╗      ██║     ██║     █████╗  ███████║██████╔╝██║
 ██║   ██║██╔══██║██║╚██╔╝██║██╔══╝      ██║     ██║     ██╔══╝  ██╔══██║██╔══██╗╚═╝
 ╚██████╔╝██║  ██║██║ ╚═╝ ██║███████╗    ╚██████╗███████╗███████╗██║  ██║██║  ██║██╗
  ╚═════╝ ╚═╝  ╚═╝╚═╝     ╚═╝╚══════╝     ╚═════╝╚══════╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝

EOD;
        Output::printLine(ColorText::color($art, 'yellow'));
        Output::printLine(ColorText::color(
            sprintf('  🎉  You completed all %d stages! Well done! 🎉', count($this->levels)),
            'green'
        ));
        echo PHP_EOL;
        Output::printLine(ColorText::color(' Press any key to exit… ', 'white', 'blue'));
        $this->readKey();
        $this->clearScreen();
    }

    // ──────────────────────────────────────────
    // Terminal helpers
    // ──────────────────────────────────────────

    /**
     * Read a single raw keypress from STDIN (non-blocking, no echo).
     * Handles ANSI escape sequences for arrow keys.
     *
     * @return string The key string ("\x1b[A" etc. for arrows, single char otherwise).
     */
    private function readKey(): string
    {
        // Put terminal into raw mode (POSIX only)
        $stty = null;
        if (DIRECTORY_SEPARATOR === '/') {
            $stty = shell_exec('stty -g');
            system('stty cbreak -echo');
        }

        $key = fread(STDIN, 1);

        // ESC sequence — read the rest of the CSI code
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

    /**
     * Clear the terminal screen.
     */
    private function clearScreen(): void
    {
        if (DIRECTORY_SEPARATOR === '/') {
            system('clear');
        } else {
            system('cls');
        }
    }
}
