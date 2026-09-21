<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use Clover\Classes\OperationSystem;
use function array_merge;
use function sprintf;
use function count;

/**
 * Class ThreeBodyCelestialSimulation
 *
 * A simulation class for three-body celestial mechanics.
 */
class ThreeBodyCelestialSimulation
{
    private float $time;
    private array $bodies = [];
    private float $gravitationalConstant = 6.67430e-11; // m^3 kg^-1 s^-2
    private float $deltaTime = 3600;
    private array $trajectories = [];
    private float $scaleFactor = 1e-10;
    private float $collisionThreshold = 1e7;
    private bool $adaptiveTimeStep = true;
    private float $minTimeStep = 60;
    private float $maxTimeStep = 86400;
    private int $animationSpeed = 1;
    private int $imageWidth = 1200;
    private int $imageHeight = 1000;
    private int $pointRadius = 2;
    private array $backgroundColor = [0, 0, 0];
    private int $maxTrajectoryPoints = 1000;

    public function __construct(array $initialBodies, float $initialTime = 0)
    {
        $this->time = $initialTime;
        $this->bodies = $initialBodies;
        foreach ($this->bodies as $name => $body) {
            $this->trajectories[$name] = [$body['position']];
        }
    }

    private function calculateGravitationalForce(array $bodyA, array $bodyB): array
    {
        $rVector = [
            $bodyB['position'][0] - $bodyA['position'][0],
            $bodyB['position'][1] - $bodyA['position'][1],
            $bodyB['position'][2] - $bodyA['position'][2]
        ];

        $distanceSq = $rVector[0] * $rVector[0] + $rVector[1] * $rVector[1] + $rVector[2] * $rVector[2];
        $distance = sqrt($distanceSq);
        $forceMagnitude = $this->gravitationalConstant * $bodyA['mass'] * $bodyB['mass'] / $distanceSq;

        return [
            $forceMagnitude * $rVector[0] / $distance,
            $forceMagnitude * $rVector[1] / $distance,
            $forceMagnitude * $rVector[2] / $distance
        ];
    }

    public function motionEquation(float $t, array $state): array
    {
        $currentState = [];
        $offset = 0;

        foreach ($this->bodies as $name => $body) {
            $currentState[$name] = [
                'position' => [$state[$offset], $state[$offset + 2], $state[$offset + 4]],
                'velocity' => [$state[$offset + 1], $state[$offset + 3], $state[$offset + 5]],
                'mass' => $body['mass'],
            ];
            $offset += 6;
        }

        $accelerations = [];

        foreach ($this->bodies as $nameA => $bodyA) {
            $totalForce = [0, 0, 0];

            foreach ($this->bodies as $nameB => $bodyB) {
                if ($nameA !== $nameB) {
                    $force = $this->calculateGravitationalForce($currentState[$nameA], $currentState[$nameB]);
                    $totalForce[0] += $force[0];
                    $totalForce[1] += $force[1];
                    $totalForce[2] += $force[2];
                }
            }

            $accelerations[$nameA] = [
                $totalForce[0] / $bodyA['mass'],
                $totalForce[1] / $bodyA['mass'],
                $totalForce[2] / $bodyA['mass'],
            ];
        }

        $stateDerivative = [];
        foreach ($this->bodies as $name => $body) {
            $stateDerivative = array_merge($stateDerivative, $currentState[$name]['velocity']);
            $stateDerivative = array_merge($stateDerivative, $accelerations[$name]);
        }

        return $stateDerivative;
    }

    /**
     * Advance simulation by one time step (stability-enhanced version).
     */
    public function step()
    {
        if ($this->adaptiveTimeStep) {
            $this->updateTimeStep();
        }

        $currentStateArray = [];
        foreach ($this->bodies as $body) {
            $currentStateArray = array_merge($currentStateArray, $body['position'], $body['velocity']);
        }

        $nextStateArray = $this->rungeKutta4($this->time, $currentStateArray, $this->deltaTime);

        if (!$this->validateState($nextStateArray)) {
            $this->deltaTime = max($this->minTimeStep, $this->deltaTime * 0.5);
            return $this->step();
        }

        $offset = 0;
        foreach ($this->bodies as $name => &$body) {
            $body['position'] = [$nextStateArray[$offset], $nextStateArray[$offset + 2], $nextStateArray[$offset + 4]];
            $body['velocity'] = [$nextStateArray[$offset + 1], $nextStateArray[$offset + 3], $nextStateArray[$offset + 5]];

            $this->trajectories[$name][] = $body['position'];
            if (count($this->trajectories[$name]) > $this->maxTrajectoryPoints) {
                array_shift($this->trajectories[$name]);
            }

            $offset += 6;
        }

        $this->detectCollisions();
        $this->time += $this->deltaTime;
    }

    private function validateState(array $state): bool
    {

        foreach ($state as $value) {
            if (!is_finite($value) || abs($value) > 1e100) {
                return false;
            }
        }
        return true;
    }

    private function updateTimeStep(): void
    {
        $minDistance = PHP_FLOAT_MAX;

        foreach ($this->bodies as $nameA => $bodyA) {
            foreach ($this->bodies as $nameB => $bodyB) {
                if ($nameA !== $nameB) {
                    $dx = $bodyA['position'][0] - $bodyB['position'][0];
                    $dy = $bodyA['position'][1] - $bodyB['position'][1];
                    $dz = $bodyA['position'][2] - $bodyB['position'][2];
                    $distance = sqrt($dx * $dx + $dy * $dy + $dz * $dz);
                    $minDistance = min($minDistance, $distance);
                }
            }
        }

        if ($minDistance < 1e8) {
            $this->deltaTime = $this->minTimeStep;
        } else if ($minDistance < 1e9) {
            $this->deltaTime = $this->minTimeStep * 10;
        } else if ($minDistance < 1e10) {
            $this->deltaTime = $this->minTimeStep * 100;
        } else {
            $this->deltaTime = $this->maxTimeStep;
        }
    }

    /**
     * Collision detection and handling between celestial bodies
     */
    private function detectCollisions(): void
    {
        foreach ($this->bodies as $nameA => $bodyA) {
            foreach ($this->bodies as $nameB => $bodyB) {
                if ($nameA !== $nameB) {
                    $dx = $bodyA['position'][0] - $bodyB['position'][0];
                    $dy = $bodyA['position'][1] - $bodyB['position'][1];
                    $dz = $bodyA['position'][2] - $bodyB['position'][2];
                    $distance = sqrt($dx * $dx + $dy * $dy + $dz * $dz);

                    if ($distance < $this->collisionThreshold) {
                        $this->handleCollision($nameA, $nameB);
                        return;
                    }
                }
            }
        }
    }

    /**
     * Collision handling - apply conservation of momentum
     */
    private function handleCollision(string $nameA, string $nameB): void
    {
        $bodyA = $this->bodies[$nameA];
        $bodyB = $this->bodies[$nameB];
        $newMass = $bodyA['mass'] + $bodyB['mass'];

        $newPosition = [
            ($bodyA['position'][0] * $bodyA['mass'] + $bodyB['position'][0] * $bodyB['mass']) / $newMass,
            ($bodyA['position'][1] * $bodyA['mass'] + $bodyB['position'][1] * $bodyB['mass']) / $newMass,
            ($bodyA['position'][2] * $bodyA['mass'] + $bodyB['position'][2] * $bodyB['mass']) / $newMass,
        ];

        $newVelocity = [
            ($bodyA['velocity'][0] * $bodyA['mass'] + $bodyB['velocity'][0] * $bodyB['mass']) / $newMass,
            ($bodyA['velocity'][1] * $bodyA['mass'] + $bodyB['velocity'][1] * $bodyB['mass']) / $newMass,
            ($bodyA['velocity'][2] * $bodyA['mass'] + $bodyB['velocity'][2] * $bodyB['mass']) / $newMass,
        ];

        $newRadius = pow($bodyA['radius'] ** 3 + $bodyB['radius'] ** 3, 1 / 3);
        $colorA = sscanf($bodyA['color'], "#%2x%2x%2x");
        $colorB = sscanf($bodyB['color'], "#%2x%2x%2x");
        $ratioA = $bodyA['mass'] / $newMass;
        $newColor = sprintf(
            "#%02x%02x%02x",
            (int) ($colorA[0] * $ratioA + $colorB[0] * (1 - $ratioA)),
            (int) ($colorA[1] * $ratioA + $colorB[1] * (1 - $ratioA)),
            (int) ($colorA[2] * $ratioA + $colorB[2] * (1 - $ratioA))
        );

        $newName = $bodyA['mass'] > $bodyB['mass'] ? $nameA : $nameB;
        $this->bodies[$newName] = [
            'name' => $bodyA['name'] . '-' . $bodyB['name'] . ' collision remnant',
            'mass' => $newMass,
            'radius' => $newRadius,
            'position' => $newPosition,
            'velocity' => $newVelocity,
            'color' => $newColor
        ];

        if ($bodyA['mass'] > $bodyB['mass']) {
            unset($this->bodies[$nameB]);
            unset($this->trajectories[$nameB]);
        } else {
            unset($this->bodies[$nameA]);
            unset($this->trajectories[$nameA]);
        }

        echo "Collision: {$bodyA['name']} and {$bodyB['name']}\n";
    }

    /**
     * Stabilized Runge-Kutta 4th-order implementation
     */
    private function rungeKutta4(float $t, array $y, float $h): array
    {
        try {
            $k1 = $this->motionEquation($t, $y);

            if (!$this->validateDerivative($k1)) {
                throw new \Exception("Invalid k1 derivative");
            }

            $k2y = [];
            for ($i = 0; $i < count($y); $i++) {
                $k2y[$i] = $y[$i] + 0.5 * $h * $k1[$i];
            }
            $k2 = $this->motionEquation($t + 0.5 * $h, $k2y);

            if (!$this->validateDerivative($k2)) {
                throw new \Exception("Invalid k2 derivative");
            }

            $k3y = [];
            for ($i = 0; $i < count($y); $i++) {
                $k3y[$i] = $y[$i] + 0.5 * $h * $k2[$i];
            }
            $k3 = $this->motionEquation($t + 0.5 * $h, $k3y);

            if (!$this->validateDerivative($k3)) {
                throw new \Exception("Invalid k3 derivative");
            }

            $k4y = [];
            for ($i = 0; $i < count($y); $i++) {
                $k4y[$i] = $y[$i] + $h * $k3[$i];
            }
            $k4 = $this->motionEquation($t + $h, $k4y);

            if (!$this->validateDerivative($k4)) {
                throw new \Exception("Invalid k4 derivative");
            }

            $result = [];
            for ($i = 0; $i < count($y); $i++) {
                $result[$i] = $y[$i] + ($h / 6.0) * ($k1[$i] + 2 * $k2[$i] + 2 * $k3[$i] + $k4[$i]);

                if (abs($result[$i]) > 1e50) {
                    throw new \Exception("Result value too large: " . $result[$i]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            $this->deltaTime *= 0.5;
            if ($this->deltaTime < $this->minTimeStep) {
                $this->deltaTime = $this->minTimeStep;

                return $y;
            }
            return $this->rungeKutta4($t, $y, $this->deltaTime);
        }
    }

    /**
     * Derivative value validation
     */
    private function validateDerivative(array $derivative): bool
    {
        foreach ($derivative as $value) {
            if (!is_finite($value) || abs($value) > 1e20) {
                return false;
            }
        }
        return true;
    }

    /**
     * Prepare data for use with Three.js
     */
    public function getBodyDataForThreeJS(): array
    {
        $data = [];
        foreach ($this->bodies as $name => $body) {
            $data[$name] = [
                'name' => $body['name'],
                'color' => $body['color'],
                'radius' => $body['radius'] * $this->scaleFactor * 100,
                'trajectory' => $this->trajectories[$name]
            ];
        }
        return $data;
    }

    /**
     * Generate Three.js visualization code
     */
    public function generateThreeJSCode(string $outputFile = 'solar_system_simulation.html'): void
    {
        $bodyData = $this->getBodyDataForThreeJS();
        $scale = $this->scaleFactor;

        $jsTrajectories = [];
        foreach ($bodyData as $name => $data) {
            $jsTrajectories[$name] = $this->phpArrayToJSForThree($data['trajectory'], $scale);
        }

        $threeJSCode = <<<HTML
   <!DOCTYPE html>
   <html>
   <head>
    <title>Solar System Simulation (Three.js)</title>
    <style>
     body { margin: 0; overflow: hidden; }
     canvas { display: block; }
     .controls {
      position: absolute;
      top: 10px;
      left: 10px;
      background: rgba(0,0,0,0.5);
      color: white;
      padding: 10px;
      border-radius: 5px;
     }
    </style>
   </head>
   <body>
    <div class="controls">
     <button id="pauseBtn">Pause/Resume</button>
     <input type="range" id="speedSlider" min="0.01" max="1" step="0.01" value="{$this->animationSpeed}">
     <label for="speedSlider">Speed: <span id="speedValue">{$this->animationSpeed}</span></label>
    </div> <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/three@0.145/build/three.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/three@0.145/examples/js/controls/OrbitControls.js"></script>
    <script>
     const scene = new THREE.Scene();
     const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
     const renderer = new THREE.WebGLRenderer({ antialias: true });
     renderer.setSize(window.innerWidth, window.innerHeight);
     document.body.appendChild(renderer.domElement);
   
     const ambientLight = new THREE.AmbientLight(0xffffff, 0.2);
     scene.add(ambientLight);
   
     const bodiesData = {
      {$this->arrayToJSForBodiesData($bodyData)}
     };
   
     const trajectories = {
      {$this->arrayToJS($jsTrajectories)}
     };
   
     const bodies = {};
     for (const bodyName in bodiesData) {
      const data = bodiesData [bodyName];
      const geometry = new THREE.SphereGeometry(data.radius, 32, 32);
      let materialOptions = {
       color: data.color,
      };
   
      if (bodyName.toLowerCase() === 'sun') {
          materialOptions.emissive = 0xfff000; 
          materialOptions.emissiveIntensity = 1;
      } else {
          materialOptions.emissive = data.color;
          materialOptions.emissiveIntensity = 0.1;
      }
   
      const material = new THREE.MeshPhongMaterial(materialOptions);
      const sphere = new THREE.Mesh(geometry, material);
      scene.add(sphere);
   
      if (trajectories [bodyName]) {
          const trajectoryPoints = trajectories [bodyName].map(p => new THREE.Vector3(p.x, p.y, p.z));
          const trajectoryGeometry = new THREE.BufferGeometry().setFromPoints(trajectoryPoints);
          const trajectoryMaterial = new THREE.LineBasicMaterial({ color: data.color, opacity: 0.3, transparent: true });
          const trajectoryLine = new THREE.Line(trajectoryGeometry, trajectoryMaterial);
          scene.add(trajectoryLine);
          bodies [bodyName] = {
              mesh: sphere,
              trajectoryIndex: 0,
              trajectory: trajectoryLine
          };
      } else {
          bodies [bodyName] = {
              mesh: sphere,
              trajectoryIndex: 0,
              trajectory: null
          };
      }
     }
   
     camera.position.set(0, 15, 40);
   
     let animationSpeed = {$this->animationSpeed};
     let isPaused = false;
     let animationId = null;
   
     document.getElementById('pauseBtn').addEventListener('click', () => {
      isPaused = !isPaused;
      if (!isPaused && !animationId) {
       animate();
      }
     });
   
     document.getElementById('speedSlider').addEventListener('input', (e) => {
      animationSpeed = parseFloat(e.target.value);
      document.getElementById('speedValue').textContent = animationSpeed.toFixed(2);
     });
   
     function animate() {
      if (!isPaused) {
       animationId = requestAnimationFrame(animate);
   
       for (const bodyName in bodies) {
        const body = bodies [bodyName];
        if (body.trajectory && trajectories [bodyName]) {
         const trajectory = trajectories [bodyName];
         if (body.trajectoryIndex < trajectory.length) {
          const position = trajectory [Math.floor(body.trajectoryIndex)];
          body.mesh.position.set(position.x, position.y, position.z);
          body.trajectoryIndex += animationSpeed;
         } else {
          body.trajectoryIndex = 0; 
         }
        }
       }
   
       renderer.render(scene, camera);
      } else {
       cancelAnimationFrame(animationId);
       animationId = null;
      }
     }
   
     const controls = new THREE.OrbitControls(camera, renderer.domElement);
     controls.enableDamping = true;
     controls.dampingFactor = 0.05;
   
     animate();
   
     window.addEventListener('resize', () => {
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(window.innerWidth, window.innerHeight);
     });
    </script>
   </body>
   </html>
   HTML;

        file_put_contents($outputFile, $threeJSCode);
        echo "Solar System Three.js HTML code generated at: " . $outputFile . "\n";
    }

    /**
     * PHP arrays to JavaScript format for Three.js
     */
    private function phpArrayToJSForThree(array $phpArray, float $scale): string
    {
        $jsArray = '[';
        foreach ($phpArray as $point) {
            $jsArray .= '{x: ' . ((float) $point[0] * $scale) . ', y: ' . ((float) $point[1] * $scale) . ', z: ' . ((float) $point[2] * $scale) . '},';
        }
        $jsArray = rtrim($jsArray, ',');
        $jsArray .= ']';
        return $jsArray;
    }

    /**
     * Convert array to JavaScript object string
     */
    private function arrayToJS(array $arr): string
    {
        $jsString = '';
        foreach ($arr as $key => $value) {
            $jsString .= "'$key': $value,";
        }
        return rtrim($jsString, ',');
    }

    /**
     * Convert celestial body data to JavaScript object string
     */
    private function arrayToJSForBodiesData(array $arr): string
    {
        $jsString = '';
        foreach ($arr as $key => $body) {
            $radius = $body['radius'];
            $jsString .= "'$key': { name: '{$body['name']}', color: '{$body['color']}', radius: $radius },";
        }
        return rtrim($jsString, ',');
    }

    /**
     * Generate 2D trajectory image
     */
    public function generate2DTrajectoryImage(string $imagePath = 'solar_system_2d.png'): void
    {
        $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
        $bgColor = imagecolorallocate($image, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
        imagefill($image, 0, 0, $bgColor);

        $minX = PHP_FLOAT_MAX;
        $maxX = PHP_FLOAT_MIN;
        $minY = PHP_FLOAT_MAX;
        $maxY = PHP_FLOAT_MIN;

        foreach ($this->trajectories as $trajectory) {
            foreach ($trajectory as $point) {
                $x = (float) $point[0];
                $y = (float) $point[1];
                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
                $minY = min($minY, $y);
                $maxY = max($maxY, $y);
            }
        }

        $scaleX = $this->imageWidth / (($maxX - $minX) ?: 1);
        $scaleY = $this->imageHeight / (($maxY - $minY) ?: 1);
        $scale = min($scaleX, $scaleY) * 0.9;

        $offsetX = ($this->imageWidth - $scale * ($maxX - $minX)) / 2 - $scale * $minX;
        $offsetY = ($this->imageHeight - $scale * ($maxY - $minY)) / 2 + $scale * $maxY;

        foreach ($this->bodies as $name => $body) {
            $colorRGB = sscanf($body['color'], "#%2x%2x%2x");
            $pointColor = imagecolorallocate($image, $colorRGB[0], $colorRGB[1], $colorRGB[2]);
            $trajectory = $this->trajectories[$name];

            foreach ($trajectory as $point) {
                $x = (float) $point[0];
                $y = (float) $point[1];
                $pixelX = (int) ($scale * $x + $offsetX);
                $pixelY = (int) ($offsetY - $scale * $y);
                imagefilledellipse($image, $pixelX, $pixelY, $this->pointRadius * 2, $this->pointRadius * 2, $pointColor);
            }

            for ($i = 0; $i < count($trajectory) - 1; $i++) {
                $x1 = (float) $trajectory[$i][0];
                $y1 = (float) $trajectory[$i][1];
                $x2 = (float) $trajectory[$i + 1][0];
                $y2 = (float) $trajectory[$i + 1][1];

                $pixelX1 = (int) ($scale * $x1 + $offsetX);
                $pixelY1 = (int) ($offsetY - $scale * $y1);
                $pixelX2 = (int) ($scale * $x2 + $offsetX);
                $pixelY2 = (int) ($offsetY - $scale * $y2);

                imageline($image, $pixelX1, $pixelY1, $pixelX2, $pixelY2, $pointColor);
            }
        }

        imagepng($image, $imagePath);

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($image);
        }
    }

    public function runSimulation(int $steps): void
    {
        for ($i = 0; $i < $steps; $i++) {
            $this->step();

            if ($steps > 100 && $i % ($steps / 10) === 0) {
                echo "Simulation progress: " . round(($i / $steps) * 100) . "%\n";
            }
        }
        echo "Simulation finished: $steps step(s) completed\n";
    }
}