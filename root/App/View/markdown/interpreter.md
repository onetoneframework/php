# Onetone Framework

Onetone Framework: The Universal Polyglot Runtime & Instrumentation Engine

Onetone is not just another web framework; it is a high-performance polyglot runtime and orchestration engine built on LLVM, designed to shatter the barriers between web development, system programming, and security research.

At its core lies a custom-built compiler and virtual machine, engineered from the ground up to deliver a hybrid architecture that bridges high-level productivity with low-level control.

Key Technical Pillars:

LLVM-Powered Hybrid Core: Features a proprietary language and AOT (Ahead-of-Time) compiler backend leveraging LLVM. It offers a unique selectable GC (Garbage Collection) architecture, allowing developers to toggle between automatic memory management and raw performance suitable for system-level tasks.

True Polyglot Orchestration: Acts as a universal "glue" runtime that seamlessly integrates and controls modules written in PHP, Python, JS/TS, and C/C++. It orchestrates diverse ecosystems within a single execution environment.

Built-in Dynamic Instrumentation: Integrates powerful security tooling like Frida directly into the core framework. This enables real-time dynamic analysis, hooking, and manipulation of running processes, making it a potent platform for security researchers and reverse engineers.

Vertical Integration: Spans the entire stack from low-level memory operations and native app hooking to high-level web service interfaces, providing a unified dashboard for system-wide control.

Onetone is the ultimate toolkit for architects and researchers who refuse to stay in the sandbox—combining the depth of a compiler, the versatility of a polyglot runtime, and the power of a security suite.

## Table of Contents

- [Quick Start](#quickstart)
- [Installation](#installation)
- [Interpreter Usage](#interpreter-usage)
- [Language Reference](#language-reference)
  - [Basic Types and Variables](#basic-types)
  - [Operators](#operators)
  - [String Operations](#string-operations)
  - [Arrays and Collections](#arrays-collections)
  - [Control Flow](#control-flow)
  - [Functions](#functions)
  - [Classes and Objects](#classes)
  - [Modern Syntax](#modern-syntax)
  - [Built-in API](#builtin-api)
- [3D Graphics (gl3d)](#gl3d)
- [GUI Applications (wingui)](#wingui)
- [Build Instructions](#build)
- [Directory Structure](#directory)
- [Project Status](#project-status)
- [Contributing](#contributing)

---

<a id="quickstart"></a>
## Quick Start

### 1. Hello World

```c
function main(): integer {
    console.printLine("Hello, Onetone!");
    return 0;
}
```

### 2. Execution

```bash
# Windows
interpreter.exe hello.otc

# Or specify the full path
C:\path\to\interpreter.exe C:\path\to\hello.otc
```

### 3. Output

```
Hello, Onetone!
```

---

<a id="installation"></a>
## Installation

### Method 1: Download Release Version (Recommended)

1. Download the latest version from [Releases](https://github.com/onetoneframework/framework/releases)
2. Extract the ZIP file to your desired location
3. Add the `interpreter.exe` path to the `PATH` environment variable (optional)

### Method 2: Build from Source

```bash
# Clone the repository
git clone https://github.com/onetoneframework/framework.git
cd framework/res/platform/c/interpreter

# Build with GCC (MinGW-w64 recommended)
./build.bat
```

### System Requirements

| Item | Requirement |
|------|------|
| OS | Windows 10/11 (x64) |
| Memory | 4GB or more recommended |
| GPU | OpenGL 3.3 or higher support (for 3D features) |
| Compiler | GCC 8+ / MSVC 2019+ (for building) |

---

<a id="interpreter-usage"></a>
## Interpreter Usage

### Basic Execution

```bash
# Basic execution
interpreter.exe <filename.otc>

# Examples
interpreter.exe example/hello.otc
interpreter.exe example/unittest.otc
interpreter.exe example/gl3d_cube.otc
```

### File Extension

| Extension | Description |
|--------|------|
| `.otc` | Onetone script file |

### API Lists

## console.print(...)
Print text to the console without newline.

## console.printLine(...)
Print text to the console with newline.

## console.write(...)
Write to console

## console.clear()
Clear console screen

## console.getKey()
Read a key input

## console.hideCursor()
Hide cursor

## console.showCursor()
Show cursor

## console.setCursorPosition(x, y)
Set cursor position

## console.sleep(milliseconds)
Wait for specified time in milliseconds

## file.open(path, mode)
Open a file

## file.read(file)
Read from file

## file.write(file, content)
Write to file

## file.close(file)
Close file

## file.isEof(file)
Check end of file

## math.abs(x)
Absolute value

## math.sqrt(x) 
Square root

## math.pow(x, y)
Power

## math.sin(x) 
Sine

## math.cos(x) 
Cosine

## math.tan(x) 
Tangent

## math.atan2(y, x)
Arctangent with two arguments

## math.floor(x) 
Floor

## math.ceil(x) 
Ceil

## math.pi 
Pi constant

## Array.prototype.map

## Array.prototype.filte

## Array.prototype.reduce

## Array.prototype.forEach

## Array.prototype.find

## Array.prototype.findIndex

## Array.prototype.some

## Array.prototype.every

## Array.prototype.includes

## Array.prototype.flat

## Array.prototype.join(separator)

## Array.prototype.reverse()

## Array.prototype.slice(start[, end])

## Array.prototype.concat(array)

## pathfinding.bfs

## pathfinding.astar

## String.prototype.length()
String length

## String.prototype.indexOf(search[, fromIndex])
Find substring (returns index)

## String.prototype.includes(search[, start])

## String.prototype.endsWith(search[, length])

## String.prototype.concat(...strings)
concatenate all args (coerce to string)

## String.prototype.toCharArray()
Convert string to array of characters

## String.prototype.countOccurrences(needle)
count occurrences of substring

## String.prototype.truncate(length, suffix)
truncate with ellipsis

## String.prototype.rightPad(width, fillChar)
Pad right

## String.prototype.leftPad(width, fillChar)
pad left

## String.prototype.center(width, fillChar) 
center string in width

## String.prototype.decodeURL() 
URL decode string

## String.prototype.encodeURL() 
URL encode string

## String.prototype.decodeBase64() 
decode Base64 string

## String.prototype.encodeBase64()
encode string to Base64

## String.prototype.slugify()
convert to URL slug

## String.prototype.kebabCase()
convert to kebab-case

## String.prototype.snakeCase()
convert to snake_case

## String.prototype.camelCase()
convert to camelCase

## String.prototype.title() 
capitalize first letter of each word

## String.prototype.capitalize()
capitalize first letter

## String.prototype.wordCount() 
count words in string

## String.prototype.trimEnd()
remove trailing whitespace

## String.prototype.isPalindrome()
check if string is a palindrome

## String.prototype.isLowerCase()
check if all letters are lowercase

## String.prototype.isEmpty()
check if string is empty

## String.prototype.isUpperCase()
check if all letters are uppercase

## String.prototype.isAlphanumeric()
check if string contains only alphanumeric chars

## String.prototype.isNumeric()
check if string contains only digits

## String.prototype.contains(needle)
check if string contains substring

## String.prototype.reverseWords()
reverse word order

## String.prototype.reverse()
reverse the string

## String.prototype.trimStart()
remove leading whitespace

## charCodeAt(index)
returns numeric code (byte value for now)

## String.prototype.charAt(index)

## String.prototype.format(args...)
string formatting with {0}, {1}, etc.

## String.prototype.substring(start, end) 
Substring

## String.prototype.substr(start, length)

## String.prototype.lastIndexOf(search[, fromIndex])

## String.prototype.toLowerCase()
To lowercase

## String.prototype.toUpperCase() 
To uppercase

## String.prototype.append(str) 
Append string

## slice(start[, end])
byte-based slicing

## gl2d.init(width, height, title) 
Initialize 2D graphics

## gl2d.drawTextureEx(id, x, y, w, h, angle, flipX, flipY, r, g, b, a)

## gl2d.getTextureHeight(texId)

## gl2d.getTextureWidth(texId)

## gl2d.drawNineSlice(texId, x, y, w, h, cornerSize)

## gl2d.drawTexturePart(texId, x, y, w, h, srcX, srcY, srcW, srcH)

## gl2d.checkCollision(x1, y1, w1, h1, x2, y2, w2, h2)

## gl2d.beginFrame()
Begin frame

## gl2d.endFrame()
End frame

## gl2d.clear()
Clear screen

## gl2d.createSprite(texture, x, y)
Create sprite

## gl2d.drawSprite(sprite)
Draw sprite

## gl2d.setSpritePosition(sprite, x, y)
Set sprite position

## gl2d.moveSprite(sprite, dx, dy)
Move sprite

## gl2d.updateSprites()
Update sprites

## gl2d.loadTexture(path)
Load texture

## gl2d.freeTexture(texture)
Free texture

## gl2d.drawText(x, y, text)
Draw text

## gl2d.drawRect(x, y, width, height)
Draw rectangle

## gl2d.getMouseX()
Mouse X coordinate

## gl2d.getMouseY()
Mouse Y coordinate

## gl2d.isKeyDown(key)
Is key down

## gl2d.isKeyPressed(key)
Is key pressed

## gl2d.isMouseButtonDown(button)
Is mouse button down

## gl2d.camera.init(x, y)
Initialize camera

## gl2d.camera.setPosition(x, y)
Set camera position

## gl2d.camera.setZoom(zoom)
Set camera zoom

## gl2d.camera.update()
Update camera

## gl2d.createTilemap(width, height, tileset)
Create tilemap

## gl2d.drawTilemap(tilemap)
Draw tilemap

## gl2d.isTileSolid(tilemap, x, y)
Check if tile is solid

## gl2d.checkCollisionSprites(sprite1, sprite2)
Check sprite collision

## gl2d.createButton(x, y, width, height, text)
Create button

## gl2d.drawButton(button)
Draw button

## gl2d.isButtonClicked(button)
Check button click

## gl2d.isButtonHovered(button)
Check button hover

## gl2d.updateButtons()
Update buttons

## gl2d.createCharacter(name, x, y)
Create character

## gl2d.showCharacter(character)
Show character

## gl2d.hideCharacter(character)
Hide character

## gl2d.setCharacterExpression(character, expression)
Set character expression

## gl2d.drawCharacters()
Draw characters

## gl2d.createTextbox(x, y, width, height)
Create textbox

## gl2d.showTextbox(textbox)
Show textbox

## gl2d.setTextboxText(textbox, text)
Set textbox text

## gl2d.drawTextbox(textbox)
Draw textbox

## gl2d.isTextboxFinished(textbox)
Is textbox finished

## gl2d.updateTextbox(textbox)
Update textbox

## gl2d.addChoice(label, value)
Add choice

## gl2d.presentChoices()
Present choices

## gl2d.isAwaitingChoice()
Is awaiting choice

## gl2d.getChoiceResultLabel()
Get chosen label

## gl2d.drawChoices()
Draw choices

## gl2d.updateChoices()
Update choices

## gl2d.clearChoices()
Clear choices

## gl2d.loadSound(path)
Load sound

## gl2d.playSound(sound)
Play sound

## gl2d.stopSound(sound)
Stop sound

## gl2d.playBgm(path)
Play BGM

## gl2d.stopBgm()
Stop BGM

## gl2d.initAudio()
Initialize audio

## gl2d.setBackground(path)
Set background

## gl2d.drawBackground()
Draw background

## gl2d.startFade(type, duration)
Start fade

## gl2d.updateFade()
Update fade

## gl2d.drawFade()
Draw fade

## gl2d.loadFont(path, size)
Load font

## gl2d.loadTileset(path)
Load tileset

## gl2d.getDeltaTime()
Get delta time

## gl2d.getTime()
Get time

## gl2d.getVersion()
Get version

## gl2d.updateDeltaTime()
Update delta time

## gl2d.lerp(a, b, t)
Linear interpolation

## gl2d.clamp(value, min, max)
Clamp value

## gl2d.randomFloat()
Random float

## gl2d.randomInt(min, max)
Random integer

## gl2d.debug.init()
Debug init

## gl2d.debug.render()
Debug render

## gl2d.debug.update()
Debug update

## gl2d.debug.togglePhysicsInfo()
Toggle physics info

## gl2d.debug.toggleCollisionBoxes()
Toggle collision boxes

## gl2d.debug.toggleFps()
Toggle FPS

## gl2d.setGamepadDeadzone(deadzone)
Set gamepad deadzone

## gl2d.getGamepadAxis(axis)
Get gamepad axis

## gl2d.isGamepadButtonPressed(button)
Is gamepad button pressed

## gl2d.isGamepadButtonDown(button)
Is gamepad button down

## gl2d.isGamepadConnected()
Is gamepad connected

## gl2d.gamepadUpdate()
Gamepad update

## gl3d.init(width, height, title)
Initialize 3D graphics

## gl3d.beginFrame()
Begin frame

## gl3d.endFrame()
End frame

## gl3d.clear()
Clear screen

## gl3d.setMaterialRoughness(material, roughness)

## gl3d.setMaterialReflectivity(material, reflectivity)

## gl3d.setMaterialMetalness(material, metalness)

## gl3d.setMaterialIor(material, ior)

## gl3d.setObjectMaterial(objectId, material)

## gl3d.enablePostBloom(boolean enable)

## gl3d.setPostBloomParams(float threshold, float intensity)

## gl3d.enablePostFxaa(boolean enable)

## gl3d.enablePostSsao(boolean enable)

## gl3d.enablePostDof(boolean enable)

## gl3d.enablePostMotionBlur(boolean enable)

## gl3d.loadModelGlb(path)

## gl3d.modelGetBounds(model)

## gl3d.modelRenderAt(model, pos_x, pos_y, pos_z, rot_x, rot_y, rot_z, scale_x, scale_y, scale_z)

## gl3d.modelRender(model)

## gl3d.loadModelGlb(path)

## gl3d.loadModelGltf(path)

## gl3d.switchTo3d()
Switch to 3D mode

## gl3d.switchTo2d()
Switch to 2D mode

## gl3d.setCameraPosition(x, y, z)
Set camera position

## gl3d.setCameraRotation(pitch, yaw)
Set camera rotation

## gl3d.cameraMove(dx, dy, dz)
Move camera

## gl3d.captureMouse(capture)
Capture mouse

## gl3d.drawCube(x, y, z, size)
Draw cube

## gl3d.drawBlock(x, y, z, size)
Draw block

## gl3d.drawTexturedCube(x, y, z, size, texture): integer
Draw textured cube

## gl3d.createCubeGeometry(x, y, z, scale_x, scale_y, scale_z): integer
Create cube geometry

## gl3d.createSphereGeometry(x, y, z, radius): integer
Create sphere geometry

## gl3d.createCylinderGeometry(x, y, z, radius): integer
Create sphere geometry

## gl3d.createIcosahedronGeometry(x, y, z, radius): integer
Create icosahedron geometry

## gl3d.createDodecahedronGeometry(x, y, z, radius): integer
Create dodecahedron geometry

## gl3d.createOctahedronGeometry(x, y, z, radius): integer
Create octahedron geometry

## gl3d.createTetrahedronGeometry(x, y, z, size): integer
Create tetrahedron geometry

## gl3d.createTorusGeometry(x, y, z, radius, height): integer
Create torus geometry

## gl3d.drawModel(model, x, y, z)
Draw model

## gl3d.loadModelObj(path)
Load OBJ model

## gl3d.loadTexture(path)
Load texture

## gl3d.bindTexture(texture)
Bind texture

## gl3d.drawText(x, y, text)
Draw text

## gl3d.drawRect(x, y, width, height)
Draw rectangle

## gl3d.drawAxes()
Draw axes

## gl3d.drawGrid(size)
Draw grid

## gl3d.drawCrosshair()
Draw crosshair

## gl3d.setObjectPosition(object, x, y, z)
Set object position

## gl3d.setObjectRotation(object, x, y, z)
Set object rotation

## gl3d.setMaterialReflectivity(texture_id, reflectivity)

## gl3d.setObjectMaterial(object, material)
Set object material

## gl3d.setObjectActive(object, active)
Set object active state

## gl3d.createPhongMaterial(r, g, b, a, shininess): object
Create Phong material

## gl3d.createTransparentMaterial(r, g, b, a, shininess): object
Create ransparent material

## gl3d.createBasicMaterial(r, g, b, a)
Create basic material

## gl3d.createPbrMaterial(r, g, b, a, roughness, metalness)
Create pbr material

## gl3d.createTexturedMaterial(texture_id)
Create textured material

## gl3d.createGlassMaterial(r, g, b, a, reflectivity, transparency, ior)
Create textured material

## gl3d.createAmbientLight(r, g, b, a)
Create ambient light

## gl3d.setLight(lightNum, posX, posY, posZ, ambR, ambG, ambB, ambA, difR, difG, difB, difA, spcR, spcG, spcB, spcA, enabled)
Set light

## gl3d.setLightPosition(texture_id, x, y, z)
Set light position

## gl3d.setLightShadow(texture_id, cast_shadow)
Set light position

## gl3d.setGlobalAmbient(x, y, z, a)
Set global ambient

## gl3d.renderAllObjects()
Render all objects

## gl3d.processInput()
Process input

## gl3d.getDeltaTime()
Get delta time

## gl3d.getFps()
Get FPS

## gl3d.worldInit(width, height, depth)
Initialize world

## gl3d.worldSetBlock(x, y, z, blockType)
Set block

## gl3d.worldGetBlock(x, y, z)
Get block

## gl3d.worldRender()
Render world

## gl3d.generateTerrain(width, height, seed)
Generate terrain

## gl3d.raycastWorld(originX, originY, originZ, dirX, dirY, dirZ)
Raycast

## gl3d.cleanup()
Cleanup

## gui.init()
Initialize GUI

## gui.cleanup()
Cleanup GUI

## gui.createWindow(title, x, y, width, height): integer
Create window

## gui.createRadioButton(text, x, y, w, h, id)

## gui.clearItems(controlId)

## gui.getTrackBarPos(controlId)

## gui.createTrackBar(x, y, w, h, id, min, max)

## gui.setProgressBarPos(controlId, pos)

## gui.createProgressBar(x, y, w, h, id)

## gui.showWindow(window)
Show window

## gui.hideWindow(window)
Hide window

## gui.setWindowTitle(window, title)
Set window title

## gui.setWindowSize(window, width, height)
Set window size

## gui.maximizeWindow(window)
Maximize window

## gui.getClientSize(window)
Get client size

## gui.createButton(window, x, y, width, height, text)
Create button

## gui.createLabel(window, x, y, width, height, text)
Create label

## gui.createTextBox(window, x, y, width, height)
Create textbox

## gui.createCheckbox(window, x, y, width, height, text)
Create checkbox

## gui.createComboBox(window, x, y, width, height)
Create combobox

## gui.createListBox(window, x, y, width, height)
Create listbox

## gui.createPictureBox(window, x, y, width, height)
Create picture box

## gui.setText(control, text)
Set control text

## gui.getText(control)
Get control text

## gui.setImage(control, image)
Set control image

## gui.setCheckboxState(checkbox, checked)
Set checkbox state

## gui.getCheckboxState(checkbox)
Get checkbox state

## gui.getSelectedIndex(control)
Get selected index

## gui.getSelectedText(control)
Get selected text

## gui.addItem(control, item)
Add item

## gui.enableControl(control, enabled)
Enable or disable control

## gui.setFocus(control)
Set focus

## gui.messageBox(window, title, message, type)
Show message box

## gui.createMenuBar(window)
Create menu bar

## gui.createMenu(text)
Create menu

## gui.addMenuItem(menu, text)
Add menu item

## gui.addMenuSeparator(menu)
Add menu separator

## gui.addMenuToBar(menuBar, menu)
Add menu to menu bar

## gui.setMenuBar(window, menuBar)
Set menu bar on window

## gui.setMenuItemChecked(menuItem, checked)
Set menu item checked

## gui.setMenuItemEnabled(menuItem, enabled)
Set menu item enabled

## gui.setTimer(control, interval, callback)
Set timer

## gui.killTimer(control)
Kill timer

## gui.registerCallback(control, type, functionName)
Register callback

## gui.unregisterCallback(control, type)
Unregister callback

## gui.processMessages()
Process messages blocking

## gui.processMessagesNonBlocking()
Process messages nonblocking

## gui.runMessageLoop()
Run message loop

## clipboard.copy(text)
Copy to clipboard

## keyboard.down(key)
Simulate key down

## keyboard.up(key)
Simulate key up

## audio.play(path)
Play audio

## audio.stop()
Stop audio

## file.open(path, mode)
Open file

## file.read(file)
Read file

## file.write(file, content)
Write file

## file.close(file)
Close file

## file.isEof(file)
Check EOF

## downloadFile(url, path)
Download file

## getDocumentsPath()
Get documents path

## getFocusedExplorerPath()
Get focused explorer path

## getGpsLocation()
Get GPS location

## setMouseCursorPosition(x, y)
Set mouse cursor position

## setVolume(volume)
Set volume

## setMonitorBrightnessPercentage(percentage)
Set monitor brightness percentage

## system.hibernate()
Hibernate system

## isRunningAsAdmin()
Check admin privileges

## receiveInput()
Receive input

## server.start(port)
Start server

## server.stop()
Stop server

## server.isRunning()
Check if server is running

## mysql.connect(host, user, password, database)
Connect to MySQL

## mysql.executeQuery(connection, query)
Execute MySQL query

## windows.getInstalledPrograms()
Get installed programs list

## windows.getSelectedFiles()
Get selected files list

## windows.getUsbDevices()
Get USB devices list

## window.showNotificationW(title, message)
Show Windows notification

## captureAsBitmapImage(x, y, width, height)
Capture bitmap image

### Basic Program Structure

```c
function main(): integer {
    // Program code
    
    return 0;  // Exit code
}
```

### Command Line Arguments

```c
function main(): integer {
    array args = system.args;
    
    for (arg of args) {
        console.printLine(arg);
    }
    
    return 0;
}
```

### Execution Examples

```bash
# Basic example
interpreter.exe example/hello.otc

# Run unit tests
interpreter.exe example/unittest.otc

# Run 3D demo
interpreter.exe example/gl3d_demo.otc
```

---

<a id="language-reference"></a>
## Language Reference

<a id="basic-types"></a>
### Basic Types and Variables

```c
// Typed variable declaration
integer i = 42;              // Integer
float f = 3.14159;           // Floating point
string s = "Hello";          // String
bool b = true;               // Boolean

// Type inference
var v = 100;                 // Inferred as integer
let l = "hello";             // Reassignable
const PI = 3.14159;          // Constant (cannot be reassigned)

// Null value
var empty = null;
```

### Type List

| Type | Description | Example |
|----|------|-----|
| `integer` | Integer | `42`, `-100`, `0` |
| `float` | Floating point number | `3.14`, `-0.5` |
| `string` | String | `"Hello"`, `'World'` |
| `bool` | Boolean | `true`, `false` |
| `array` | Array | `[1, 2, 3]` |
| `object` | Object | `{ name: "John" }` |
| `null` | Null value | `null` |

<a id="operators"></a>
### Operators

#### Generics

```c
class NumberBox<T extends Number> {
    var value;
    
    function constructor(T val) {
        this.value = val;
    }
    
    function doubleValue() {
        return this.value * 2;
    }
}
```

#### Connection Operator

The return value of start is passed to end via ~>, and ultimately the return value of end is returned. ~> acts as a connection operator that directly links values between functions (and between methods within a class).

```c
function start() ~> end {
    return 10;
}

function end(integer data) {
    console.printLine(data);
    return 10;
}
```

#### Arithmetic Operators

```c
// Forward concatenation (left-side string concatenation)
"Value: " <+ 100;     // Result: "Value: 100"

// Backward addition (right-side numeric operation)
"100" >+ 3;           // Result: 103

// Other operators
"100" <+ 3;           // Result: "1003"
"10" <* 5;            // Result: 50 (convert left side to number and multiply)

5 + 3      // Addition: 8
10 - 4     // Subtraction: 6
6 * 7      // Multiplication: 42
15 / 4     // Division: 3.75
17 % 5     // Modulo: 2

integer x = 5;
x++;       // Post-increment: x = 6
x--;       // Post-decrement: x = 5
```

#### Comparison Operators

```c
5 == 5     // Equal: true
5 != 3     // Not equal: true
5 > 3      // Greater than: true
3 < 5      // Less than: true
5 >= 5     // Greater than or equal: true
4 <= 5     // Less than or equal: true

// String comparison
"abc" == "abc"   // true
"abc" != "def"   // true
```

#### Logical Operators

```c
true && true     // AND: true
true || false    // OR: true
!false           // NOT: true

// Short-circuit evaluation
false && func()  // func() is not called
true || func()   // func() is not called
```

#### Bitwise Operators

```c
5 & 3      // AND: 1
5 | 3      // OR: 7
5 ^ 3      // XOR: 6
~0         // NOT: -1
1 << 3     // Left shift: 8
16 >> 2    // Right shift: 4
```

<a id="string-operations"></a>
### String Operations

#### Basic Operations

```c
string s1 = "Hello";
string s2 = " World";

// Concatenation
string s3 = s1 + s2;           // "Hello World"

// Length
integer len = s1.length;       // 5

// Template literals
string name = "John";
string msg = `Hello, ${name}!`;

// Escape sequences
string escaped = "Hello\tWorld\n";
```

#### String Methods

```c
string s = "Hello World";

// Substring
s.substring(0, 5)              // "Hello"

// Search
s.indexOf("World")             // 6
s.indexOf("xyz")               // -1

// Case conversion
s.toUpperCase()                // "HELLO WORLD"
s.toLowerCase()                // "hello world"

// Whitespace removal
"  hello  ".trim()             // "hello"
"  hello  ".trimStart()        // "hello  "
"  hello  ".trimEnd()          // "  hello"

// Split
"a,b,c".split(",")             // ["a", "b", "c"]

// Replace
s.replace("World", "Onetone")  // "Hello Onetone"
"aaa".replaceAll("a", "b")     // "bbb"

// Checks
s.startsWith("Hello")          // true
s.endsWith("World")            // true
s.includes("llo")              // true

// Repeat
"ab".repeat(3)                 // "ababab"

// Character access
s.charAt(0)                    // "H"
```

<a id="arrays-collections"></a>
### Arrays and Collections

#### Array

```c
// Declaration and initialization
array arr = [1, 2, 3, 4, 5];

// Property
arr.length                     // 5

// Index access
arr[0]                         // 1
arr[2] = 30;                   // Modify element

// Add/remove elements
arr.put(6);                    // Add to end
integer last = arr.pop();      // Remove from end and return
integer first = arr.shift();   // Remove from beginning and return
arr.unshift(0);                // Add to beginning

// Search
arr.contains(3)                // true
arr.indexOf(3)                 // Returns index
arr.includes(3)                // true

// Transformation
arr.slice(1, 4)                // Subarray [2, 3, 4]
arr.concat([6, 7])             // Concatenate
arr.join("-")                  // "1-2-3-4-5"
arr.reverse()                  // Reverse
arr.sort()                     // Sort
```

#### Higher-order Functions

```c
array nums = [1, 2, 3, 4, 5];

// map - Transform each element
array doubled = nums.map((x) => x * 2);
// [2, 4, 6, 8, 10]

// filter - Extract elements matching condition
array evens = nums.filter((x) => x % 2 == 0);
// [2, 4]

// reduce - Accumulate
integer sum = nums.reduce((acc, x) => acc + x, 0);
// 15

// find - First element matching condition
integer found = nums.find((x) => x > 3);
// 4

// every - Check if all elements satisfy condition
bool allPositive = nums.every((x) => x > 0);
// true

// some - Check if any element satisfies condition
bool hasEven = nums.some((x) => x % 2 == 0);
// true
```

#### HashMap

```c
HashMap map = new HashMap();

// Operations
map.put("name", "John");
map.put("age", 25);
map.get("name")                // "John"
map.containsKey("name")        // true
map.remove("age");
map.size                       // 1
map.clear();
```

#### HashSet

```c
HashSet set = new HashSet();

set.add("apple");
set.add("banana");
set.add("apple");              // Duplicate ignored
set.contains("apple")          // true
set.size                       // 2
set.remove("apple");
```

#### ArrayList

```c
ArrayList list = new ArrayList();

list.add(10);
list.add(20);
list.add(30);
list.get(0)                    // 10
list.size                      // 3
```

#### LinkedHashMap / LinkedHashSet

```c
// Maintains insertion order
LinkedHashMap lhm = new LinkedHashMap();
lhm.put("first", 1);
lhm.put("second", 2);

LinkedHashSet lhs = new LinkedHashSet();
lhs.add("a");
lhs.add("b");
```

#### TreeMap / TreeSet

```c
// Maintains sorted order
TreeMap tm = new TreeMap();
tm.put("banana", 2);
tm.put("apple", 1);
// Keys are sorted

TreeSet ts = new TreeSet();
ts.add(3);
ts.add(1);
ts.add(2);
// Elements are sorted
```

<a id="control-flow"></a>
### Control Flow

#### if / else

```c
integer x = 10;

// Simple if
if (x > 5) {
    console.printLine("x is greater than 5");
}

// if-else
if (x > 15) {
    console.printLine("large");
} else {
    console.printLine("small");
}

// if-else if-else
integer score = 75;
if (score >= 90) {
    console.printLine("A");
} else if (score >= 80) {
    console.printLine("B");
} else if (score >= 70) {
    console.printLine("C");
} else {
    console.printLine("F");
}
```

#### switch

```c
integer day = 3;
string dayName = "";

switch (day) {
    case 1:
        dayName = "Monday";
        break;
    case 2:
        dayName = "Tuesday";
        break;
    case 3:
        dayName = "Wednesday";
        break;
    default:
        dayName = "Other";
        break;
}
```

#### while

```c
integer i = 0;
while (i < 5) {
    console.printLine(i);
    i = i + 1;
}
```

#### do-while

```c
integer i = 0;
do {
    i = i + 1;
} while (i < 5);
```

#### for

```c
// Standard for loop
for (integer i = 0; i < 10; i++) {
    console.printLine(i);
}

// for-of (array iteration)
array items = ["apple", "banana", "orange"];
for (item of items) {
    console.printLine(item);
}
```

#### break / continue

```c
// break - Exit loop
for (integer i = 0; i < 10; i++) {
    if (i == 5) {
        break;
    }
    console.printLine(i);
}

// continue - Skip to next iteration
for (integer i = 0; i < 10; i++) {
    if (i % 2 == 0) {
        continue;
    }
    console.printLine(i);  // Prints odd numbers only
}
```

<a id="functions"></a>
### Functions

#### Basic Functions

```c
// No arguments, no return value
function sayHello() {
    console.printLine("Hello!");
}

// With arguments and return value
function add(integer a, integer b): integer {
    return a + b;
}

// With return type specification
function greet(string name): string {
    return `Hello, ${name}!`;
}
```

#### Default Arguments

```c
function greet(string name = "Guest", string greeting = "Hello") {
    console.printLine(`${greeting}, ${name}!`);
}

greet();                    // "Hello, Guest!"
greet("John");              // "Hello, John!"
greet("John", "Hi");        // "Hi, John!"
```

#### Recursive Functions

```c
function factorial(integer n): integer {
    if (n <= 1) {
        return 1;
    }
    return n * factorial(n - 1);
}

function fibonacci(integer n): integer {
    if (n <= 1) {
        return n;
    }
    return fibonacci(n - 1) + fibonacci(n - 2);
}
```

#### Arrow Functions (Lambda)

```c
// Single expression
var double = (x) => x * 2;
double(5)  // 10

// Multiple arguments
var add = (a, b) => a + b;
add(3, 4)  // 7

// Combined with higher-order functions
array nums = [1, 2, 3, 4, 5];
array doubled = nums.map((x) => x * 2);
```

#### Generator Functions

```c
generator function* range(integer start, integer end) {
    integer i = start;
    while (i < end) {
        yield i;
        i = i + 1;
    }
}

// Usage example
for (i of range(1, 5)) {
    console.printLine(i);  // 1, 2, 3, 4
}

generator function* countdown(integer n) {
    while (n > 0) {
        yield n;
        n = n - 1;
    }
}
```

#### Anonymous Functions

```c
var anonymousFunction = function () {
    console.printLine("Anonymous function executed");
};
anonymousFunction();

var factorial = function (n) {
    if (n <= 1) {
        return 1;
    }
    return n * factorial(n - 1);
};
```

<a id="classes"></a>
### Classes and Objects

#### Class Definition

```c
class Person {
    string name = "";
    integer age = 0;
    
    function constructor(string n, integer a) {
        this.name = n;
        this.age = a;
    }
    
    function greet() {
        return `I am ${this.name}, ${this.age} years old`;
    }
    
    function birthday() {
        this.age = this.age + 1;
    }
}

// Instantiation
Person person = new Person("John", 25);
console.printLine(person.greet());
person.birthday();
```

#### Inheritance

```c
class Animal {
    string name = "";
    integer age = 0;
    
    function constructor(string n, integer a) {
        this.name = n;
        this.age = a;
    }
    
    function speak() {
        return "...";
    }
    
    static function eat() {
        return "Eating food";
    }
}

class Dog extends Animal {
    string breed = "";
    
    function constructor(string n, integer a, string b) {
        this.name = n;
        this.age = a;
        this.breed = b;
    }
    
    // Method override
    function speak() {
        return "Woof!";
    }
    
    function getBreed() {
        return this.breed;
    }
}

Dog dog = new Dog("Buddy", 3, "Labrador");
console.printLine(dog.speak());     // "Woof!"
console.printLine(dog.getBreed());  // "Labrador"
```

#### Enum

```c
enum Status {
    PENDING = 0,
    ACTIVE,
    COMPLETED
}

enum Color {
    RED = 1,
    GREEN,
    BLUE
}

integer status = Status.COMPLETED;  // 2
integer color = Color.RED;          // 1
```

#### Record Type

```c
record Point {
    integer x;
    integer y;
}

record User {
    string name;
    integer age;
}

Point p = new Point(10, 20);
User u = new User("John", 25);
```

#### Object Literal

```c
object person = {
    name: "John",
    age: 25,
    active: true
};

console.printLine(person.name);  // "John"
person.age = 26;

// Nested object
object config = {
    server: {
        host: "localhost",
        port: 8080
    },
    database: {
        name: "mydb"
    }
};
```

<a id="modern-syntax"></a>
### Modern Syntax

#### Ternary Operator

```c
integer x = 10;
string result = x > 5 ? "large" : "small";

// Nested
integer score = 85;
string grade = score >= 90 ? "A" : score >= 80 ? "B" : "C";
```

#### Null Coalescing Operator (??)

```c
var value = null;
string result = value ?? "default";  // "default"

string name = userName ?? guestName ?? "Anonymous";
```

#### Optional Chaining (?.)

```c
object user = {
    name: "John",
    address: {
        city: "Seoul"
    }
};

// Null-safe access
string city = user?.address?.city;  // "Seoul"
string zip = user?.address?.zip;    // null (no error)
```

#### Destructuring Assignment

```c
// Array destructuring
array arr = [1, 2, 3];
let [a, b, c] = arr;
// a = 1, b = 2, c = 3

// Object destructuring
object person = { name: "John", age: 25 };
let { name, age } = person;
// name = "John", age = 25
```

#### Spread Operator

```c
array arr1 = [1, 2, 3];
array arr2 = [4, 5, 6];
array combined = [...arr1, ...arr2];
// [1, 2, 3, 4, 5, 6]

array withExtras = [0, ...arr1, 4, 5];
// [0, 1, 2, 3, 4, 5]
```

#### Range Expression

```c
array r1 = 1..5;    // [1, 2, 3, 4] (exclusive)
array r2 = 1...5;   // [1, 2, 3, 4, 5] (inclusive)

integer sum = 0;
for (i of 1..6) {
    sum = sum + i;
}
```

#### Pipeline Operator (|>)

```c
function double(integer x): integer {
    return x * 2;
}

function addTen(integer x): integer {
    return x + 10;
}

// Chain functions with pipeline
integer result = 5 |> double |> addTen;
// ((5 * 2) + 10) = 20
```

#### try / catch / finally

```c
try {
    // Code that may throw an exception
    integer result = riskyOperation();
} catch (e) {
    // Error handling
    console.printLine(`Error: ${e}`);
} finally {
    // Always executed
    cleanup();
}

// throw statement
function validate(integer x) {
    if (x < 0) {
        throw "Value must be 0 or greater";
    }
}
```

#### async / await

```c
async function fetchData(): string {
    return "data";
}

async function processData() {
    string data = await fetchData();
    console.printLine(data);
}
```

#### typeof

```c
integer x = 42;
string s = "hello";
var arr = [1, 2, 3];
var fn = function () { return 1; };

typeof x      // "number"
typeof s      // "string"
typeof arr    // "array"
typeof fn     // "function"
typeof null   // "null"
```

#### instanceof

```c
var dog = new Dog("Buddy", 5, "Labrador");
dog instanceof Dog      // true
dog instanceof Animal   // true
42 instanceof Number    // true
"hi" instanceof String  // true
[1, 2] instanceof Array // true
```

<a id="builtin-api"></a>
### Built-in API

#### console

```c
console.print("Output without newline");
console.printLine("Output with newline");
console.sleep(1000);  // Wait in milliseconds
```

#### math

```c
math.abs(-5)           // 5
math.floor(3.7)        // 3
math.ceil(3.2)         // 4
math.round(3.5)        // 4
math.pow(2, 8)         // 256
math.sqrt(16)          // 4
math.min(3, 7)         // 3
math.max(3, 7)         // 7
math.min(1, 5, 3)      // 1 (multiple arguments)
math.max(1, 5, 3)      // 5 (multiple arguments)

// Trigonometric functions
math.sin(0)            // 0
math.cos(0)            // 1

// Logarithm/Exponent
math.log(math.E)       // 1
math.log10(100)        // 2
math.exp(1)            // 2.718...

// Random
math.random()          // 0.0 ~ 1.0

// Constants
math.PI                // 3.14159...
math.E                 // 2.71828...
```

#### file

```c
// Read file
FILE handle = file.open("C:\\path\\to\\file.txt");
string content = file.readContent(handle);
file.close(handle);

// Write file
file.writeContent("C:\\path\\to\\output.txt", "Hello World");

// Check file existence
bool exists = file.exists("C:\\path\\to\\file.txt");
```

#### system

```c
// System information
string docs = system.getDocumentsPath();
bool admin = system.isAdmin();
integer time = system.time();

// GPS location (supported devices only)
system.getGPSLocation();
```

#### clipboard

```c
// Clipboard operation
clipboard.copy("Text to copy");
```

#### http

```c
// HTTP request
var response = http.get("https://api.example.com/data");
var postResponse = http.post("https://api.example.com/data", {
    name: "value"
});
```

#### windows (Windows only)

```c
// Screen capture
windows.captureAsBitmapImage("C:\\capture.bmp");

// Message box
windows.messageBox("Title", "Message", 0);
```

#### mysql

```c
// MySQL connection
CONN conn = mysql.connect("127.0.0.1", "user", "password", 3306);
mysql.selectDatabase(conn, "mydb");

// Execute query
var result = mysql.executeQuery(conn, "SELECT * FROM users");

// Close connection
mysql.close(conn);
```

---

<a id="gl3d"></a>
## 3D Graphics (gl3d)

A 3D graphics engine based on OpenGL 3.3 is built in.

### Basic 3D Application

```c
function main(): integer {
    gui.init();

    integer hwnd = gui.createWindow("3D Viewer", 0, 1080, 1920, 1080);
    if (hwnd == 0) {
        console.printLine("Failed to create window");
        return 0;
    }
    
    gui.showWindow();

    if (gl3d.init(1920, 1080) == 0) {
        console.printLine("Failed to initialize gl3d");
        gui.cleanup();
        return 0;
    }
    
    gl3d.setGlobalAmbient(0.0, 0.0, 0.0, 1.0);
    gl3d.setCameraPosition(8.0, 5.0, 8.0);
    gl3d.setCameraRotation(0.0, 50.0, -30.0);
    
    // Create lights
    integer point_light = gl3d.createPointLight(5.0, 3.0, 0.0, 1.0, 1.0, 1.0, 1.0, 150.0, 150.0, 150.0);
    integer directional_light = gl3d.createDirectionalLight(1.0, 1.0, 0.5, 1.0, 1.0, 1.0, 1.0, 0.5);
    
    // Create geometry
    integer sphere_id = gl3d.createSphereGeometry(0.0, 2.0, 0.0, 2.2);
    integer ground = gl3d.createCubeGeometry(0.0, 0.5, 0.0, 200.0, 1.0, 200.0);
    
    // Create and apply PBR material
    object pbr_material = gl3d.createPbrMaterial(0.1, 0.3, 0.9, 1.0, 1.0, 0.0);
    gl3d.setMaterialRoughness(pbr_material, 0.0);
    gl3d.setMaterialReflectivity(pbr_material, 0.7);
    gl3d.setObjectMaterial(sphere_id, pbr_material);

    gl3d.setFxaa(1);

    float running = 1.0;
    integer msgResult = 0;

    // Main loop
    while (running == 1.0) {
        msgResult = gui.processMessages();
        if (msgResult == 0) {
            running = 0.0;
            break;
        }

        gl3d.beginFrame();
        gl3d.clear(0.1, 0.1, 0.1, 1.0);
        
        gl3d.renderPbrObjects();

        gl3d.endFrame();
        console.sleep(5);
    }
    
    gl3d.cleanup();
    gui.cleanup();
    return 0;
}
```

### gl3d API Reference

#### Initialization and Cleanup

```c
gl3d.init(width, height)             // Initialize
gl3d.cleanup()                       // Cleanup
gl3d.getDeltaTime()                  // Get delta time
```

#### Rendering

```c
gl3d.beginFrame()                    // Begin frame
gl3d.endFrame()                      // End frame
gl3d.clear(r, g, b, a)               // Clear screen
gl3d.renderPbrObjects()              // Render PBR objects
gl3d.setFxaa(enabled)                // FXAA anti-aliasing
gl3d.setSSRDebugMode(mode)           // SSR debug mode
```

#### Camera

```c
gl3d.setCameraPosition(x, y, z)      // Camera position
gl3d.setCameraRotation(pitch, yaw, roll)  // Camera rotation
```

#### Geometry Creation

```c
gl3d.createSphereGeometry(x, y, z, radius)
gl3d.createCubeGeometry(x, y, z, width, height, depth)
gl3d.createCylinderGeometry(x, y, z, radius, height)
gl3d.createTorusGeometry(x, y, z, radius, tubeRadius, segments)
gl3d.createDodecahedronGeometry(x, y, z, size, detail)
gl3d.createOctahedronGeometry(x, y, z, size, detail)
gl3d.createTetrahedronGeometry(x, y, z, size, detail)
gl3d.createIcosahedronGeometry(x, y, z, size, detail)
```

#### Materials

```c
gl3d.createPbrMaterial(r, g, b, a, metalness, roughness)
gl3d.createGlassMaterial(r, g, b, a, transparency, ior, reflectivity)
gl3d.setMaterialRoughness(material, roughness)
gl3d.setMaterialReflectivity(material, reflectivity)
gl3d.setMaterialMetalness(material, metalness)
gl3d.setMaterialIor(material, ior)
gl3d.setObjectMaterial(objectId, material)
```

#### Object Manipulation

```c
gl3d.setObjectRotate(objectId, rx, ry, rz)
gl3d.setObjectReceiveShadow(objectId, enabled)
```

#### Lighting

```c
gl3d.setGlobalAmbient(r, g, b, a)
gl3d.createPointLight(x, y, z, r, g, b, a, intensity, range, decay)
gl3d.createDirectionalLight(x, y, z, r, g, b, a, intensity)
gl3d.createHemisphereLight(skyR, skyG, skyB, groundR, groundG, groundB, a, topR, topG, topB, bottomA, intensity)
gl3d.setLightShadow(lightId, enabled)
```

---

<a id="wingui"></a>
## GUI Applications (wingui)

You can create Windows GUI applications.

### Basic GUI App

```c
function onButtonClick() {
    console.printLine("Button clicked!");
    gui.messageBox("Button was clicked!", "Notice", 0);
}

function main(): integer {
    gui.init();
    
    integer window = gui.createWindow("My App", -1, -1, 600, 500);
    gui.showWindow(window);
    
    // Create button
    integer button = gui.createButton("Click", 50, 50, 150, 40, 1001);
    
    // Create label
    integer label = gui.createLabel("Enter text:", 50, 100, 200, 20, 0);
    
    // Register callback
    gui.registerCallback(1001, 0, "onButtonClick");
    
    // Message loop
    gui.runMessageLoop();
    
    gui.cleanup();
    return 0;
}
```

### gui API Reference

#### Initialization and Cleanup

```c
gui.init()                           // Initialize
gui.cleanup()                        // Cleanup
```

#### Window

```c
gui.createWindow(title, x, y, width, height)  // Create window
gui.showWindow(hwnd)                 // Show window
gui.processMessages()                // Process messages
gui.runMessageLoop()                 // Run message loop
```

#### Controls

```c
gui.createButton(text, x, y, width, height, id)
gui.createLabel(text, x, y, width, height, id)
gui.findControlById(id)              // Find control by ID
gui.getSelectedIndex(listbox)        // Get listbox selected index
```

#### Menu

```c
gui.createMenuBar()                  // Create menu bar
gui.createMenu(name)                 // Create menu
gui.addMenuItem(menu, text, callback)  // Add menu item
gui.addMenuSeparator(menu)           // Add separator
gui.addMenuToBar(menuBar, menu, name)  // Add menu to menu bar
gui.setMenuBar(menuBar)              // Set menu bar
```

#### Events

```c
gui.registerCallback(controlId, eventType, callbackName)
gui.messageBox(message, title, type)
```

---

<a id="build"></a>
## Build Instructions

### Prerequisites

- GCC 8 or higher (MinGW-w64 recommended) or Visual Studio 2019 or higher
- Windows SDK (for wingui)
- OpenGL 3.3 support (for gl3d)

### GCC (MinGW-w64)

```bash
cd res/platform/c/interpreter

# Basic build
./build.bat
```

### Source File Structure

| File | Description |
|----------|------|
| `main.c` | Entry point |
| `lexer.c` | Lexical analyzer |
| `parser.c` | Syntax parser |
| `ast.c` / `ast.h` | Abstract syntax tree |
| `interpreter.c` | Interpreter core |
| `eval.c` | Expression evaluation |
| `value.c` | Value representation |
| `environment.c` | Environment (scope) management |
| `gl3d.c` | OpenGL 3D engine |
| `wingui.c` | Windows GUI |

---

<a id="directory"></a>
## Directory Structure

```
onetone/
├── bin/                          # Binaries and tools
│   ├── ctranslate_ffi/          # CTranslate2 FFI
│   ├── onnx_ffi/                # ONNX Runtime FFI
│   └── template/                # Templates
├── conf/                         # Configuration files
│   ├── nginx/                   # Nginx config
│   ├── php/                     # PHP config
│   └── mysql/                   # MySQL config
├── res/                          # Resources
│   ├── platform/
│   │   └── c/
│   │       └── interpreter/     # Interpreter core
│   │           ├── example/     # Sample code
│   │           ├── ext/         # Extensions
│   │           ├── lib/         # Libraries
│   │           ├── shaders/     # GLSL shaders
│   │           ├── ast.c
│   │           ├── eval.c
│   │           ├── interpreter.c
│   │           ├── lexer.c
│   │           ├── parser.c
│   │           └── ...
│   ├── frontend/                # Frontend
│   └── corejs/                  # CoreJS
└── README.md
```

---

<a id="project-status"></a>
## Project Status

Status: Alpha - Under development. Not recommended for production use.

### Implementation Status

| Feature | Status | Notes |
|------|------|------|
| Basic Types/Variables | Complete | integer, float, string, bool, array |
| Operators | Complete | Arithmetic, comparison, logical, bitwise |
| Control Flow | Complete | if, switch, while, for, for-of, do-while |
| Functions | Complete | Recursion, default arguments, arrow functions, anonymous functions |
| Classes | Complete | Inheritance, constructor, this, static |
| Collections | Complete | HashMap, HashSet, ArrayList, etc. |
| Modern Syntax | Complete | ??, ?., destructuring, spread, pipeline |
| Generators | Complete | yield, for-of |
| async/await | Complete | Basic async processing |
| 3D Graphics | Complete | OpenGL 3.3 |
| GUI | Partial | Windows only |
| File I/O | Complete | Read/write |
| HTTP | Complete | GET/POST |
| MySQL | Complete | Connection, query |

### Test Results

```
Total:  267
Passed: 263
Failed: 4
Success Rate: 98.5%
```

---

<a id="contributing"></a>
## Contributing

### Before Opening an Issue/PR

1. Please read `CONTRIBUTING.md`
2. Check existing issues
3. Follow the code style

### Development Environment Setup

```bash
# Fork and clone the repository
git clone https://github.com/YOUR_USERNAME/framework.git
cd framework

# Create a branch
git checkout -b feature/your-feature

# Commit changes
git commit -m "Add: your feature description"

# Create PR
git push origin feature/your-feature
```

### Running Tests

```bash
cd res/platform/c/interpreter
./build.bat
./interpreter.exe example/unittest.otc
```
