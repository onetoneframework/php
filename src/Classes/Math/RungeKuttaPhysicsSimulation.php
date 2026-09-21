<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math;

use Clover\Annotation\Deprecated;
use Clover\Classes\OperationSystem;
use function count;

/**
 * Simulates two-dimensional projectile motion with trajectory rendering.
 */
class RungeKuttaPhysicsSimulation
{
    /**     
     * @var string $time The current time in the simulation, represented as a string to maintain precision during calculations.
     */
    private string $time;
    /**
     * @var array<int> $position An array representing the position of the projectile in the x and y directions, where position[0] is the position in the x direction and position[1] is the position in the y direction.
     */
    private array $position;
    /**
     * @var array<int> $velocity An array representing the velocity of the projectile in the x and y directions, where velocity[0] is the velocity in the x direction and velocity[1] is the velocity in the y direction.
     */
    private array $velocity;
    /**
     * @var string $gravity The gravitational acceleration, which is a constant that affects the vertical motion of the projectile. The default value is set to '15.80665', which corresponds to the standard gravity on Earth in m/s².
     */
    private string $gravity = '15.80665';
    /**
     * @var string $deltaTime The time step for the simulation, which determines how frequently the position and velocity are updated. A smaller value results in a more accurate simulation but requires more computational steps.
     */
    private string $deltaTime = '0.01';
    /**
     * @var array<int> $trajectory An array to store the trajectory points, where each point is an array of [x, y] coordinates.
     */
    private array $trajectory = [];
    /**
     * @var int $imageWidth The width of the generated trajectory image in pixels.
     */
    private int $imageWidth = 600;
    /**
     * @var int $imageHeight The height of the generated trajectory image in pixels.
     */
    private int $imageHeight = 400;
    /**
     * @var int $pointRadius The radius of the points representing the trajectory in the generated image.
     */
    private int $pointRadius = 3;
    /**     
     * @var array<int> $backgroundColor The RGB color for the background of the trajectory image.
     */
    private array $backgroundColor = [255, 255, 255];
    /**
     * @var array<int> $trajectoryColor The RGB color for the trajectory points and lines in the generated image.
     */
    private array $trajectoryColor = [0, 0, 255];

    /**
	 * Creates a simulation from its initial state.
     *
     * @param array<int> $initialPosition An array representing the initial position of the projectile in the x and y directions, where initialPosition[0] is the initial position in the x direction and initialPosition[1] is the initial position in the y direction.
     * @param array<int> $initialVelocity An array representing the initial velocity of the projectile in the x and y directions, where initialVelocity[0] is the initial velocity in the x direction and initialVelocity[1] is the initial velocity in the y direction.
     * @param string $initialTime The initial time for the simulation, represented as a string to maintain precision during calculations. The default value is '0'.
     */
    public function __construct(array $initialPosition, array $initialVelocity, string $initialTime = '0')
    {
        AdvancedMathBCMath::setScale(20);
        $this->position = $initialPosition;
        $this->velocity = $initialVelocity;
        $this->time = $initialTime;
        $this->trajectory[] = [$this->position[0], $this->position[1]];
    }

    /**
     * The motion equation for the projectile, which calculates the derivatives of position and velocity based on the current state and time.
     *
     * @param string $t The current time in the simulation, represented as a string to maintain precision during calculations.
     * @param array<int> $state An array representing the current state of the projectile, where state[0] is the position in the x direction, state[1] is the velocity in the x direction, state[2] is the position in the y direction, and state[3] is the velocity in the y direction.
     * @return array<string> An array containing the derivatives of position and velocity, where the first element is the derivative of position in the x direction (velocity), the second element is the derivative of velocity in the x direction (acceleration), the third element is the derivative of position in the y direction (velocity), and the fourth element is the derivative of velocity in the y direction (acceleration).
     */
    public function motionEquation(string $t, array $state): array
    {
        $currentVelocityX = $state[1];
        $currentVelocityY = $state[3];
        $accelerationX = '0';
        $accelerationY = $this->gravity;
        return [$currentVelocityX, $accelerationX, $currentVelocityY, $accelerationY];
    }

    /**
     * Perform a single step of the simulation using the Runge-Kutta method to update the position and velocity of the projectile based on the motion equation.
     */
    public function step(): void
    {
        $state = [$this->position[0], $this->velocity[0], $this->position[1], $this->velocity[1]];
        $nextState = AdvancedMathBCMath::rungeKutta4Vector($this->time, $state, [$this, 'motionEquation'], $this->deltaTime);
        $this->position = [$nextState[0], $nextState[2]];
        $this->velocity = [$nextState[1], $nextState[3]];
        $this->time = AdvancedMathBCMath::bcadd($this->time, $this->deltaTime);
        $this->trajectory[] = [$this->position[0], $this->position[1]];
    }

    /**
     * Get the current position of the projectile.
     *
     * @return array<int> An array representing the position of the projectile in the x and y directions, where position[0] is the position in the x direction and position[1] is the position in the y direction.
     */
    public function getPosition(): array
    {
        return $this->position;
    }

    /**
     * Get the current velocity of the projectile.
     *
     * @return array<int> An array representing the velocity of the projectile in the x and y directions, where velocity[0] is the velocity in the x direction and velocity[1] is the velocity in the y direction.
     */
    public function getVelocity(): array
    {
        return $this->velocity;
    }

    /**
     * Get the current time in the simulation.
     *
     * @return string The current time in the simulation, represented as a string to maintain precision during calculations.
     */
    public function getTime(): string
    {
        return $this->time;
    }

    /**
     * Get the trajectory points of the projectile, where each point is an array of [x, y] coordinates.
     *
     * @return array<int> An array of trajectory points, where each point is an array of [x, y] coordinates.
     */
    public function getTrajectory(): array
    {
        return $this->trajectory;
    }

    /**
     * Generate a PNG image of the projectile's trajectory based on the stored trajectory points. The image will be saved to the specified path.
     *
     * @param string $imagePath The file path where the generated trajectory image will be saved. The default value is 'trajectory.png'.
     */
    public function generateTrajectoryImage(string $imagePath = 'trajectory.png'): void
    {
        $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
        $bgColor = imagecolorallocate($image, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
        $trajColor = imagecolorallocate($image, $this->trajectoryColor[0], $this->trajectoryColor[1], $this->trajectoryColor[2]);

        imagefill($image, 0, 0, $bgColor);

        if (empty($this->trajectory)) {
            imagepng($image, $imagePath);

            if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                // @phpstan-ignore-next-line
                imagedestroy($image);
            }
            return;
        }

        // Find min and max values for scaling
        $minX = $maxX = (float) $this->trajectory[0][0];
        $minY = $maxY = (float) $this->trajectory[0][1];

        foreach ($this->trajectory as $point) {
            $x = (float) $point[0];
            $y = (float) $point[1];
            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
        }

        $scaleX = $this->imageWidth / ($maxX - $minX + 1e-9);
        $scaleY = $this->imageHeight / ($maxY - $minY + 1e-9);
        $scale = min($scaleX, $scaleY) * 0.9; // Add some padding

        $offsetX = ($this->imageWidth - $scale * ($maxX - $minX)) / 2 - $scale * $minX;
        $offsetY = ($this->imageHeight - $scale * ($maxY - $minY)) / 2 + $scale * $maxY; // Flip Y-axis

        foreach ($this->trajectory as $point) {
            $x = (float) $point[0];
            $y = (float) $point[1];
            $pixelX = (int) ($scale * $x + $offsetX);
            $pixelY = (int) ($offsetY - $scale * $y);
            imagefilledellipse($image, $pixelX, $pixelY, $this->pointRadius * 2, $this->pointRadius * 2, $trajColor);
        }

        // Draw lines connecting the points
        for ($i = 0; $i < count($this->trajectory) - 1; $i++) {
            $x1 = (float) $this->trajectory[$i][0];
            $y1 = (float) $this->trajectory[$i][1];
            $x2 = (float) $this->trajectory[$i + 1][0];
            $y2 = (float) $this->trajectory[$i + 1][1];

            $pixelX1 = (int) ($scale * $x1 + $offsetX);
            $pixelY1 = (int) ($offsetY - $scale * $y1);
            $pixelX2 = (int) ($scale * $x2 + $offsetX);
            $pixelY2 = (int) ($offsetY - $scale * $y2);

            imageline($image, $pixelX1, $pixelY1, $pixelX2, $pixelY2, $trajColor);
        }

        imagepng($image, $imagePath);

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($image);
        }
    }
}

/**
 * Preserves the legacy simulation class name.
 */
#[Deprecated('Use RungeKuttaPhysicsSimulation instead.')]
class RungeKuttaPhysicsSimulationWithImage extends RungeKuttaPhysicsSimulation
{
}
