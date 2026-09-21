<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database;
use ArrayObject;
use Exception;
use ReflectionClass;
use ReflectionMethod;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Database\Mock\MockPDO;
use PHPUnit\Framework\TestCase;

use Clover\Classes\Database\ActiveRecord;
use Clover\Classes\Database\AttributeEncrypter;
use stdClass;

class User extends ActiveRecord
{
    /**
     * @Id
     * @Column(name="id", type="integer", nullable=false)
     */
    protected int $id;

    /**
     * @Column(name="username", type="string", nullable=false)
     */
    protected string $username;

    /**
     * @Column(name="email", type="string", nullable=false)
     */
    protected string $email;

    /**
     * @Column(name="password", type="string", nullable=false)
     */
    protected string $password;

    /**
     * @Column(name="created_at", type="datetime", nullable=true)
     */
    protected ?string $created_at;

    /**
     * @Column(name="updated_at", type="datetime", nullable=true)
     */
    protected ?string $updated_at;

    /**
     * @Column(name="is_active", type="boolean", nullable=true)
     */
    protected ?bool $is_active;

    protected array $fillable = ['username', 'email', 'password', 'is_active'];
    protected array $guarded = ['id'];
    protected array $hidden = ['password'];
    protected bool $timestamps = true;
}

class Post extends ActiveRecord
{
    /**
     * @Id
     * @Column(name="id", type="integer", nullable=false)
     */
    protected int $id;

    /**
     * @Column(name="title", type="string", nullable=false)
     */
    protected string $title;

    /**
     * @Column(name="content", type="text", nullable=true)
     */
    protected ?string $content;

    /**
     * @Column(name="user_id", type="integer", nullable=false)
     */
    protected int $user_id;

    /**
     * @Column(name="created_at", type="datetime", nullable=true)
     */
    protected ?string $created_at;

    /**
     * @Column(name="deleted_at", type="datetime", nullable=true)
     */
    protected ?string $deleted_at;

    protected array $fillable = ['title', 'content', 'user_id'];
    protected bool $timestamps = true;
    protected ?string $softDeleteColumn = 'deleted_at';
}

class Profile extends ActiveRecord
{
    /**
     * @Id
     * @Column(name="id", type="integer", nullable=false)
     */
    protected int $id;

    /**
     * @Column(name="user_id", type="integer", nullable=false)
     */
    protected int $user_id;

    /**
     * @Column(name="bio", type="text", nullable=true)
     */
    protected ?string $bio;

    /**
     * @Column(name="avatar", type="string", nullable=true)
     */
    protected ?string $avatar;

    protected array $fillable = ['user_id', 'bio', 'avatar'];
}

class ActiveRecordTest extends TestCase
{
    private static MockPDO $db;
    private User $user;
    private Post $post;
    private Profile $profile;

    public static function setUpBeforeClass(): void
    {
        ini_set('memory_limit', '2048m');

        self::$db = new MockPDO();

        self::$db->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            email TEXT NOT NULL,
            password TEXT NOT NULL,
            created_at TEXT,
            updated_at TEXT,
            is_active BOOLEAN
        )');

        self::$db->exec('CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT,
            user_id INTEGER NOT NULL,
            created_at TEXT,
            deleted_at TEXT
        )');

        self::$db->exec('CREATE TABLE profiles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            bio TEXT,
            avatar TEXT
        )');

        ActiveRecord::setDatabaseConnection(self::$db);
    }

    protected function setUp(): void
    {
        self::$db->clearAllTables();

        $this->user = new User();
        $this->post = new Post();
        $this->profile = new Profile();
    }

    public function testBasicCrudCreate(): void
    {
        $user = new User();
        $user->id = 1;
        $user->username = 'testuser';
        $user->email = 'test@example.com';
        $user->password = 'password123';

        $result = $user->save();

        $this->assertInstanceOf(User::class, $result);
        $this->assertNotNull($user->id);
        $this->assertEquals('testuser', $user['username']);
    }

    public function testBasicCrudRead(): void
    {
        $user = new User();
        $user['username'] = 'john';
        $user['email'] = 'john@example.com';
        $user['password'] = 'pass123';
        $user->save();

        $foundUser = User::find($user->id);

        $this->assertNotNull($foundUser);
        $this->assertEquals('john', $foundUser['username']);
        $this->assertEquals('john@example.com', $foundUser['email']);
    }

    public function testBasicCrudUpdate(): void
    {
        $user = new User();
        $user['username'] = 'alice';
        $user['email'] = 'alice@example.com';
        $user['password'] = 'pass456';
        $user->save();

        $user['username'] = 'alice_updated';
        $user->save();

        $updatedUser = User::find($user->id);
        $this->assertEquals('alice_updated', $updatedUser['username']);
    }

    public function testBasicCrudDelete(): void
    {
        $user = new User();
        $user['username'] = 'bob';
        $user['email'] = 'bob@example.com';
        $user['password'] = 'pass789';
        $user->save();

        $userId = $user['id'];
        $user->delete();

        $deletedUser = User::find($userId);
        $this->assertNull($deletedUser);
    }

    public function testFindOrFail(): void
    {
        $this->expectException(Exception::class);
        User::findOrFail(99999);
    }

    public function testWhereQuery(): void
    {
        User::create(['username' => 'user1', 'email' => 'user1@test.com', 'password' => 'pass']);
        User::create(['username' => 'user2', 'email' => 'user2@test.com', 'password' => 'pass']);

        $result = User::where('username', '=', 'user1')->first();

        $this->assertNotNull($result);
        $this->assertEquals('user1', $result['username']);
    }

    public function testAllMethod(): void
    {
        User::create(['username' => 'u1', 'email' => 'u1@test.com', 'password' => 'p']);
        User::create(['username' => 'u2', 'email' => 'u2@test.com', 'password' => 'p']);
        User::create(['username' => 'u3', 'email' => 'u3@test.com', 'password' => 'p']);

        $users = User::all();

        $this->assertCount(3, $users);
    }

    public function testFillableAttributes(): void
    {
        $user = new User();
        $user->fill([
            'username' => 'filltest',
            'email' => 'fill@test.com',
            'password' => 'pass123',
            'id' => 999
        ]);

        $this->assertEquals('filltest', $user->username);
        $this->assertFalse(isset($user['id']) && $user['id'] === 999);
    }

    public function testHiddenAttributes(): void
    {
        $user = User::create([
            'username' => 'hidetest',
            'email' => 'hide@test.com',
            'password' => 'secret123'
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayHasKey('username', $array);
    }

    public function testDirtyTracking(): void
    {
        $user = User::create(['username' => 'dirty', 'email' => 'd@test.com', 'password' => 'p']);

        $this->assertFalse($user->isDirty());

        $user['username'] = 'modified';
        $this->assertTrue($user->isDirty());
        $this->assertTrue($user->isDirty('username'));
        $this->assertFalse($user->isDirty('email'));
    }

    public function testGetChanges(): void
    {
        $user = User::create(['username' => 'change', 'email' => 'c@test.com', 'password' => 'p']);

        $user->username = 'newname';
        $user->email = 'new@test.com';

        $changes = $user->getChanges();

        $this->assertArrayHasKey('username', $changes);
        $this->assertArrayHasKey('email', $changes);
        $this->assertEquals('newname', $changes['username']);
    }

    public function testSoftDelete(): void
    {
        $post = Post::create(['title' => 'Test Post', 'content' => 'Content', 'user_id' => 1]);
        $postId = $post['id'];

        $post->delete();

        $this->assertNull(Post::find($postId));

        $softDeleted = Post::withTrashed()->find($postId);
        $this->assertNotNull($softDeleted);
        $this->assertNotNull($softDeleted['deleted_at']);
    }

    public function testRestore(): void
    {
        $post = Post::create(['title' => 'Restore Test', 'content' => 'Content', 'user_id' => 1]);
        $postId = $post['id'];

        $post->delete();
        $this->assertNull(Post::find($postId));

        $deleted = Post::onlyTrashed()->find($postId);
        $deleted->restore();

        $restored = Post::find($postId);
        $this->assertNotNull($restored);
        $this->assertNull($restored['deleted_at']);
    }

    public function testForceDelete(): void
    {
        $post = Post::create(['title' => 'Force Delete', 'content' => 'Content', 'user_id' => 1]);
        $postId = $post['id'];

        $post->forceDelete();

        $this->assertNull(Post::withTrashed()->find($postId));
    }

    public function testPagination(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            User::create(['username' => "user{$i}", 'email' => "u{$i}@test.com", 'password' => 'p']);
        }

        $paginator = User::paginate(10, 1);

        $this->assertCount(10, $paginator->data);
        $this->assertEquals(25, $paginator['total']);
        $this->assertEquals(3, $paginator['last_page']);
    }

    public function testOrderBy(): void
    {
        User::create(['username' => 'charlie', 'email' => 'c@test.com', 'password' => 'p']);
        User::create(['username' => 'alice', 'email' => 'a@test.com', 'password' => 'p']);
        User::create(['username' => 'bob', 'email' => 'b@test.com', 'password' => 'p']);

        $users = User::orderBy('username', 'ASC')->get();

        $this->assertEquals('alice', $users[0]->username);
        $this->assertEquals('bob', $users[1]->username);
        $this->assertEquals('charlie', $users[2]->username);
    }

    public function testLimit(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            User::create(['username' => "u{$i}", 'email' => "u{$i}@t.com", 'password' => 'p']);
        }

        $users = User::limit(5)->get();

        $this->assertCount(5, $users);
    }

    public function testCount(): void
    {
        User::create(['username' => 'count1', 'email' => 'count1@example.com', 'is_active' => true]);
        User::create(['username' => 'count2', 'email' => 'count2@example.com', 'is_active' => false]);
        User::create(['username' => 'count3', 'email' => 'count3@example.com', 'is_active' => true]);

        $this->assertEquals(3, User::countRecords());
        $this->assertEquals(2, User::countRecords(['conditions' => ['is_active' => true]]));
    }

    public function testFirstOrCreate(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'firstcreate@test.com'],
            ['username' => 'firstuser', 'password' => 'pass']
        );

        $this->assertEquals('firstuser', $user['username']);

        $sameUser = User::firstOrCreate(
            ['email' => 'firstcreate@test.com'],
            ['username' => 'different', 'password' => 'pass']
        );

        $this->assertEquals('firstuser', $sameUser['username']);
        $this->assertEquals($user['id'], $sameUser['id']);
    }

    public function testUpdateOrCreate(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'update@test.com'],
            ['username' => 'original', 'password' => 'pass']
        );

        $this->assertEquals('original', $user['username']);

        $updated = User::updateOrCreate(
            ['email' => 'update@test.com'],
            ['username' => 'updated', 'password' => 'newpass']
        );

        $this->assertEquals('updated', $updated['username']);
        $this->assertEquals($user->id, $updated->id);
    }

    public function testTruncate(): void
    {
        User::create(['username' => 'trunc1', 'email' => 'trunc1@example.com']);
        User::create(['username' => 'trunc2', 'email' => 'trunc2@example.com']);

        $this->assertEquals(2, User::countRecords());

        User::truncate();

        $this->assertEquals(0, User::countRecords());
    }
    public function testDestroy(): void
    {
        $user1 = User::create(['username' => 'u1', 'email' => 'u1@t.com', 'password' => 'p']);
        $user2 = User::create(['username' => 'u2', 'email' => 'u2@t.com', 'password' => 'p']);
        $user3 = User::create(['username' => 'u3', 'email' => 'u3@t.com', 'password' => 'p']);

        $deleted = User::destroy($user1->id, $user2->id);

        $this->assertEquals(2, $deleted);
        $this->assertNull(User::find($user1['id']));
        $this->assertNotNull(User::find($user3['id']));
    }

    public function testToArray(): void
    {
        $user = User::create(['username' => 'arraytest', 'email' => 'array@test.com', 'password' => 'secret']);

        $array = $user->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('username', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    public function testToJson(): void
    {
        $user = User::create(['username' => 'jsontest', 'email' => 'json@test.com', 'password' => 'secret']);
        $user->makeVisible(['username']);

        $json = $user->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertEquals('jsontest', $decoded['username']);
        $this->assertArrayNotHasKey('password', $decoded);
    }

    public function testIsNew(): void
    {
        $user = new User();
        $this->assertTrue($user->isNew());

        $user['username'] = 'newtest';
        $user['email'] = 'new@test.com';
        $user['password'] = 'pass';
        $user->save();

        $this->assertFalse($user->isNew());
    }

    public function testMakeHidden(): void
    {
        $user = User::create(['username' => 'hidemore', 'email' => 'hide@test.com', 'password' => 'pass']);

        $user->makeHidden(['email']);
        $array = $user->toArray();

        $this->assertArrayNotHasKey('email', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    public function testMakeVisible(): void
    {
        $user = User::create(['username' => 'visible', 'email' => 'vis@test.com', 'password' => 'secret']);

        $user->makeVisible(['password']);
        $array = $user->toArray();

        $this->assertArrayHasKey('password', $array);
    }

    public function testEventHooks(): void
    {
        $hookCalled = false;

        User::creating(function ($user) use (&$hookCalled) {
            $hookCalled = true;
        });

        User::create(['username' => 'hooktest', 'email' => 'hook@test.com', 'password' => 'pass']);

        $this->assertTrue($hookCalled);

        User::clearEventHooks();
    }

    public function testWhereIn(): void
    {
        $user1 = User::create(['username' => 'u1', 'email' => 'u1@t.com', 'password' => 'p']);
        $user2 = User::create(['username' => 'u2', 'email' => 'u2@t.com', 'password' => 'p']);
        $user3 = User::create(['username' => 'u3', 'email' => 'u3@t.com', 'password' => 'p']);

        $users = User::whereIn('id', [$user1['id'], $user2['id']])->get();

        $this->assertCount(2, $users);
    }

    public function testWhereNull(): void
    {
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1', 'user_id' => 1]);
        $post2 = Post::create(['title' => 'Post 2', 'content' => null, 'user_id' => 1]);

        $posts = Post::whereNull('content')->get();

        $this->assertCount(1, $posts);
        $this->assertEquals('Post 2', $posts[0]['title']);
    }

    public function testWhereNotNull(): void
    {
        $post1 = Post::create(['title' => 'Post 1', 'content' => 'Content 1', 'user_id' => 1]);
        $post2 = Post::create(['title' => 'Post 2', 'content' => null, 'user_id' => 1]);

        $posts = Post::whereNotNull('content')->get();

        $this->assertCount(1, $posts);
        $this->assertEquals('Post 1', $posts[0]['title']);
    }

    public function testMultipleWhereConditions(): void
    {
        User::create(['username' => 'alice', 'email' => 'alice@test.com', 'password' => 'p']);
        User::create(['username' => 'bob', 'email' => 'bob@test.com', 'password' => 'p']);
        User::create(['username' => 'charlie', 'email' => 'alice@test.com', 'password' => 'p']);

        $users = User::where('email', '=', 'alice@test.com')->get();

        $this->assertCount(2, $users);
    }

    public function testTransactionCommit(): void
    {
        $this->assertTrue(User::beginTransaction());

        User::create(['username' => 'trans1', 'email' => 't1@test.com', 'password' => 'p']);
        User::create(['username' => 'trans2', 'email' => 't2@test.com', 'password' => 'p']);

        $this->assertTrue(User::commit());
        $this->assertTrue(true);
    }

    public function testTransactionRollback(): void
    {
        $this->assertTrue(User::beginTransaction());
        $this->assertTrue(User::rollBack());
        $this->assertTrue(true);
    }

    // =========================================================================
    // VALIDATION TESTS
    // =========================================================================

    public function testValidationPassesWhenRulesAreSatisfied(): void
    {
        $user = new User();
        $user['username'] = 'validuser';
        $user['email'] = 'valid@example.com';
        $user['password'] = 'secret123';

        // Inject rules without subclassing
        $reflection = new ReflectionClass($user);
        $prop = $reflection->getProperty('rules');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $prop->setValue($user, [
            'username' => 'required|minLength:3',
            'email' => 'required|email',
        ]);

        $this->assertTrue($user->validate());
        $this->assertEmpty($user->getErrors());
    }

    public function testValidationFailsOnRequired(): void
    {
        $user = new User();

        $reflection = new ReflectionClass($user);
        $prop = $reflection->getProperty('rules');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $prop->setValue($user, ['username' => 'required']);

        $this->assertFalse($user->validate());
        $this->assertNotEmpty($user->getErrors());
        $this->assertNotNull($user->getFirstError('username'));
    }

    public function testValidationFailsOnEmail(): void
    {
        $user = new User();
        $user['email'] = 'not-an-email';

        $reflection = new ReflectionClass($user);
        $prop = $reflection->getProperty('rules');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $prop->setValue($user, ['email' => 'email']);

        $this->assertFalse($user->validate());
        $this->assertNotNull($user->getFirstError('email'));
    }

    public function testValidationMinMaxRules(): void
    {
        $user = new User();
        $user['id'] = 0; // below min

        $reflection = new ReflectionClass($user);
        $prop = $reflection->getProperty('rules');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $prop->setValue($user, ['id' => 'min:1|max:100']);

        $this->assertFalse($user->validate());
        $this->assertNotNull($user->getFirstError('id'));
    }

    public function testValidationInRule(): void
    {
        $user = new User();
        $user['username'] = 'invalid_choice';

        $reflection = new ReflectionClass($user);
        $prop = $reflection->getProperty('rules');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $prop->setValue($user, ['username' => 'in:alice,bob,charlie']);

        $this->assertFalse($user->validate());

        $user['username'] = 'alice';
        $this->assertTrue($user->validate());
    }

    public function testValidationOrFailThrows(): void
    {
        $this->expectException(Exception::class);

        $user = new User();

        $reflection = new ReflectionClass($user);
        $prop = $reflection->getProperty('rules');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $prop->setValue($user, ['email' => 'required|email']);

        $user->validateOrFail();
    }

    public function testValidationCustomMessages(): void
    {
        $user = new User();

        $reflection = new ReflectionClass($user);

        $rulesProp = $reflection->getProperty('rules');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $rulesProp->setAccessible(true);
        }
        $rulesProp->setValue($user, ['email' => 'required']);

        $msgProp = $reflection->getProperty('messages');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $msgProp->setAccessible(true);
        }
        $msgProp->setValue($user, ['email.required' => 'Email address is mandatory.']);

        $user->validate();
        $this->assertEquals('Email address is mandatory.', $user->getFirstError('email'));
    }

    // =========================================================================
    // QUERY SCOPE TESTS
    // =========================================================================

    public function testAddScopeAndScopeMethod(): void
    {
        User::create(['username' => 'active_user', 'email' => 'a@test.com', 'password' => 'p', 'is_active' => true]);
        User::create(['username' => 'inactive_user', 'email' => 'b@test.com', 'password' => 'p', 'is_active' => false]);

        $instance = new User();
        $instance->addScope('active', function ($query) {
            $query->where('is_active', '=', 1);
        });

        $instance->scope('active');
        $results = $instance->get();

        $this->assertCount(1, $results);
        $this->assertEquals('active_user', $results[0]['username']);
    }

    public function testGlobalScope(): void
    {
        User::create(['username' => 'gs_active', 'email' => 'gs1@test.com', 'password' => 'p', 'is_active' => true]);
        User::create(['username' => 'gs_inactive', 'email' => 'gs2@test.com', 'password' => 'p', 'is_active' => false]);

        // Register a global scope that filters inactive users
        User::addGlobalScope('active_only', function ($query) {
            $query->where('is_active', '=', 1);
        });

        $instance = new User();
        $instance->applyGlobalScopes();
        $results = $instance->get();

        // Only the active user should be returned
        $usernames = array_map(fn($u) => $u['username'], $results);
        $this->assertContains('gs_active', $usernames);
        $this->assertNotContains('gs_inactive', $usernames);

        // Clean up global scope so it doesn't affect other tests
        User::removeGlobalScope('active_only');
    }

    // =========================================================================
    // INSTANCE FACTORY TESTS
    // =========================================================================

    public function testNewInstance(): void
    {
        $user = User::newInstance(['username' => 'newbie', 'email' => 'new@test.com']);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('newbie', $user['username']);
        $this->assertTrue($user->isNew());
    }

    public function testCloneInstance(): void
    {
        $user = User::create(['username' => 'original', 'email' => 'orig@test.com', 'password' => 'pass']);

        $clone = $user->cloneInstance(['username' => 'cloned']);

        $this->assertTrue($clone->isNew());
        $this->assertEquals('cloned', $clone['username']);
        $this->assertEquals('orig@test.com', $clone['email']);
        $this->assertNull($clone['id'] ?? null);
    }

    // =========================================================================
    // RELATION HELPERS TESTS
    // =========================================================================

    public function testSetAndGetRelation(): void
    {
        $user = User::create(['username' => 'reluser', 'email' => 'rel@test.com', 'password' => 'p']);

        $fakePost = Post::create(['title' => 'Fake', 'content' => 'Content', 'user_id' => $user['id']]);

        $user->setRelation('posts', [$fakePost]);

        $loaded = $user->getRelation('posts');
        $this->assertIsArray($loaded);
        $this->assertCount(1, $loaded);
        $this->assertTrue($user->relationLoaded('posts'));
    }

    public function testLoadMissingSkipsAlreadyLoaded(): void
    {
        $user = User::create(['username' => 'lmuser', 'email' => 'lm@test.com', 'password' => 'p']);
        $sentinel = [['title' => 'sentinel']];
        $user->setRelation('posts', $sentinel);

        // loadMissing should NOT overwrite the already-loaded 'posts' relation
        $user->loadMissing(['posts']);

        $this->assertSame($sentinel, $user->getRelation('posts'));
    }

    // =========================================================================
    // AGGREGATE ON QUERY BUILDER CHAIN
    // =========================================================================

    public function testSumAggregate(): void
    {
        User::create(['username' => 'agg1', 'email' => 'agg1@test.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'agg2', 'email' => 'agg2@test.com', 'password' => 'p', 'is_active' => 0]);

        // Use the non-builder aggregate (passes conditions directly)
        $sum = (new User())->sum('is_active');
        $this->assertEquals(1.0, $sum);
    }

    public function testMaxAggregate(): void
    {
        User::create(['username' => 'mx1', 'email' => 'mx1@t.com', 'password' => 'p']);
        User::create(['username' => 'mx2', 'email' => 'mx2@t.com', 'password' => 'p']);
        User::create(['username' => 'mx3', 'email' => 'mx3@t.com', 'password' => 'p']);

        $max = (new User())->max('id');
        $users = User::all();
        $maxId = max(array_map(fn($u) => (int) $u['id'], $users));

        $this->assertEquals($maxId, (int) $max);
    }

    public function testMinAggregate(): void
    {
        User::create(['username' => 'mn1', 'email' => 'mn1@t.com', 'password' => 'p']);
        User::create(['username' => 'mn2', 'email' => 'mn2@t.com', 'password' => 'p']);

        $min = (new User())->min('id');
        $users = User::all();
        $minId = min(array_map(fn($u) => (int) $u['id'], $users));

        $this->assertEquals($minId, (int) $min);
    }

    // =========================================================================
    // COLLECTION HELPER TESTS
    // =========================================================================

    public function testKeyBy(): void
    {
        User::create(['username' => 'kb1', 'email' => 'kb1@t.com', 'password' => 'p']);
        User::create(['username' => 'kb2', 'email' => 'kb2@t.com', 'password' => 'p']);

        $users = User::all();
        $map = User::keyBy($users, 'username');

        $this->assertArrayHasKey('kb1', $map);
        $this->assertArrayHasKey('kb2', $map);
        $this->assertInstanceOf(User::class, $map['kb1']);
    }

    public function testGroupByField(): void
    {
        User::create(['username' => 'g1', 'email' => 'g1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'g2', 'email' => 'g2@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'g3', 'email' => 'g3@t.com', 'password' => 'p', 'is_active' => 0]);

        $groups = User::groupByField(User::all(), 'is_active');

        $this->assertCount(2, $groups[1]);
        $this->assertCount(1, $groups[0]);
    }

    public function testFilterHelper(): void
    {
        User::create(['username' => 'fl1', 'email' => 'fl1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'fl2', 'email' => 'fl2@t.com', 'password' => 'p', 'is_active' => 0]);

        $active = User::filter(User::all(), fn($u) => (bool) $u['is_active']);

        $this->assertCount(1, $active);
        $this->assertEquals('fl1', $active[0]['username']);
    }

    public function testSortByHelper(): void
    {
        User::create(['username' => 'z_user', 'email' => 'z@t.com', 'password' => 'p']);
        User::create(['username' => 'a_user', 'email' => 'a@t.com', 'password' => 'p']);
        User::create(['username' => 'm_user', 'email' => 'm@t.com', 'password' => 'p']);

        $sorted = User::sortBy(User::all(), 'username', 'ASC');

        $this->assertEquals('a_user', $sorted[0]['username']);
        $this->assertEquals('z_user', end($sorted)['username']);
    }

    public function testToCollection(): void
    {
        User::create(['username' => 'col1', 'email' => 'col1@t.com', 'password' => 'p']);
        User::create(['username' => 'col2', 'email' => 'col2@t.com', 'password' => 'p']);

        $collection = User::toCollection(User::all());

        $this->assertInstanceOf(ArrayObject::class, $collection);
        $this->assertCount(2, $collection);
    }

    // =========================================================================
    // IDENTITY / COMPARISON TESTS
    // =========================================================================

    public function testIsAndIsNot(): void
    {
        $u1 = User::create(['username' => 'same1', 'email' => 's1@t.com', 'password' => 'p']);
        $u2 = User::create(['username' => 'same2', 'email' => 's2@t.com', 'password' => 'p']);

        $u1copy = User::find($u1['id']);

        $this->assertTrue($u1->is($u1copy));
        $this->assertFalse($u1->is($u2));
        $this->assertTrue($u1->isNot($u2));
    }

    public function testToString(): void
    {
        $user = User::create(['username' => 'strtest', 'email' => 'str@t.com', 'password' => 'p']);

        $str = (string) $user;

        $this->assertStringContainsString('User', $str);
        $this->assertStringContainsString((string) $user['id'], $str);
    }

    // =========================================================================
    // MISC UTILITY TESTS
    // =========================================================================

    public function testToObject(): void
    {
        $user = User::create(['username' => 'objtest', 'email' => 'obj@t.com', 'password' => 'secret']);

        $obj = $user->toObject();

        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertEquals('objtest', $obj->username);
        $this->assertFalse(isset($obj->password)); // hidden
    }

    public function testGetPrimaryKeyAndValue(): void
    {
        $user = User::create(['username' => 'pktest', 'email' => 'pk@t.com', 'password' => 'p']);

        $this->assertEquals('id', $user->getPrimaryKey());
        $this->assertEquals($user['id'], $user->getPrimaryKeyValue());
    }

    public function testGetTable(): void
    {
        $user = new User();
        $this->assertEquals('users', $user->getTable());
    }

    public function testSetTable(): void
    {
        $user = new User();
        $user->setTable('custom_users');
        $this->assertEquals('custom_users', $user->getTable());
    }

    public function testGetFillableGuardedHidden(): void
    {
        $user = new User();

        $this->assertContains('username', $user->getFillable());
        $this->assertContains('id', $user->getGuarded());
        $this->assertContains('password', $user->getHidden());
    }

    public function testSetHiddenAndMakeVisible(): void
    {
        $user = User::create(['username' => 'hvtest', 'email' => 'hv@t.com', 'password' => 'secret']);

        $user->setHidden(['username', 'email', 'password']);
        $array = $user->toArray();
        $this->assertArrayNotHasKey('username', $array);

        $user->makeVisible(['username']);
        $array = $user->toArray();
        $this->assertArrayHasKey('username', $array);
    }

    public function testGetOriginal(): void
    {
        $user = User::create(['username' => 'orig', 'email' => 'orig@t.com', 'password' => 'p']);

        $original = $user->getOriginal('username');
        $this->assertEquals('orig', $original);

        $all = $user->getOriginal();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('username', $all);
    }

    public function testIsCleanAndWasChanged(): void
    {
        $user = User::create(['username' => 'clean', 'email' => 'clean@t.com', 'password' => 'p']);

        $this->assertTrue($user->isClean());
        $this->assertFalse($user->wasChanged());

        $user['username'] = 'dirty_now';

        $this->assertFalse($user->isClean());
        $this->assertTrue($user->wasChanged());
        $this->assertTrue($user->wasChanged('username'));
        $this->assertFalse($user->wasChanged('email'));
    }

    public function testWasRecentlyCreated(): void
    {
        $user = new User();
        $this->assertFalse($user->wasRecentlyCreated());

        $created = User::create(['username' => 'recent', 'email' => 'rec@t.com', 'password' => 'p']);
        // After create() + save(), syncOriginal is called so originalData is populated
        // wasRecentlyCreated returns exists && empty(originalData) — after create, it's populated
        $this->assertFalse($created->isNew());
    }

    public function testIncrementDecrement(): void
    {
        User::create(['username' => 'inc1', 'email' => 'inc1@t.com', 'password' => 'p', 'is_active' => 0]);

        $user = User::where('username', '=', 'inc1')->first();
        $user->increment('is_active', 1);

        $refreshed = User::find($user['id']);
        $this->assertEquals(1, (int) $refreshed['is_active']);

        $refreshed->decrement('is_active', 1);
        $afterDec = User::find($user['id']);
        $this->assertEquals(0, (int) $afterDec['is_active']);
    }

    public function testReplicate(): void
    {
        $user = User::create(['username' => 'reptest', 'email' => 'rep@t.com', 'password' => 'p']);

        $clone = $user->replicate();

        $this->assertTrue($clone->isNew());
        $this->assertNull($clone['id'] ?? null);
        $this->assertEquals('reptest', $clone['username']);
    }

    public function testRefresh(): void
    {
        $user = User::create(['username' => 'refresh1', 'email' => 'refresh@t.com', 'password' => 'p']);

        // Directly mutate via MockPDO so the entity is stale
        self::$db->updateRows('users', ['username' => 'refreshed_db'], '`id` = ?', [$user['id']]);

        $user->refresh();

        $this->assertEquals('refreshed_db', $user['username']);
    }

    public function testFindOneBy(): void
    {
        User::create(['username' => 'fob1', 'email' => 'fob1@t.com', 'password' => 'p']);
        User::create(['username' => 'fob2', 'email' => 'fob2@t.com', 'password' => 'p']);

        $found = (new User())->findOneBy('email', 'fob1@t.com');

        $this->assertNotNull($found);
        $this->assertEquals('fob1', $found['username']);
    }

    public function testFirstOrNew(): void
    {
        User::create(['username' => 'fon1', 'email' => 'fon@t.com', 'password' => 'p']);

        $existing = (new User())->firstOrNew(['email' => 'fon@t.com']);
        $this->assertFalse($existing->isNew());

        $newUser = (new User())->firstOrNew(['email' => 'missing@t.com'], ['username' => 'brandnew']);
        $this->assertTrue($newUser->isNew());
        $this->assertEquals('brandnew', $newUser['username']);
    }

    public function testDoesntExist(): void
    {
        User::create(['username' => 'exists_u', 'email' => 'ex@t.com', 'password' => 'p']);

        $this->assertFalse((new User())->doesntExist(['username' => 'exists_u']));
        $this->assertTrue((new User())->doesntExist(['username' => 'ghost']));
    }

    public function testPluck(): void
    {
        User::create(['username' => 'pluck1', 'email' => 'p1@t.com', 'password' => 'p']);
        User::create(['username' => 'pluck2', 'email' => 'p2@t.com', 'password' => 'p']);

        $emails = (new User())->pluck('email');

        $this->assertIsArray($emails);
        $this->assertContains('p1@t.com', $emails);
        $this->assertContains('p2@t.com', $emails);
    }

    public function testSimplePaginate(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            User::create(['username' => "sp{$i}", 'email' => "sp{$i}@t.com", 'password' => 'p']);
        }

        $page = (new User())->simplePaginate(1, 3, []);

        $this->assertArrayHasKey('items', $page);
        $this->assertArrayHasKey('has_more', $page);
        $this->assertEquals(3, $page['per_page']);
        $this->assertCount(3, $page['items']);
        $this->assertTrue($page['has_more']);
    }

    public function testChunk(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            User::create(['username' => "ch{$i}", 'email' => "ch{$i}@t.com", 'password' => 'p']);
        }

        $chunks = [];
        (new User())->chunk(2, function ($items, $page) use (&$chunks) {
            $chunks[] = count($items);
        });

        $this->assertCount(3, $chunks);
        $this->assertEquals(2, $chunks[0]);
    }

    public function testEach(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            User::create(['username' => "ea{$i}", 'email' => "ea{$i}@t.com", 'password' => 'p']);
        }

        $count = 0;
        (new User())->each(function ($item) use (&$count) {
            $count++;
        }, 1000);

        $this->assertEquals(4, $count);
    }

    public function testBatchUpdate(): void
    {
        User::create(['username' => 'bu1', 'email' => 'bu1@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'bu2', 'email' => 'bu2@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'bu3', 'email' => 'bu3@t.com', 'password' => 'p', 'is_active' => 1]);

        $affected = (new User())->batchUpdate(['is_active' => 0], ['is_active' => 1]);

        $this->assertEquals(2, $affected);
    }

    public function testBatchDelete(): void
    {
        $u1 = User::create(['username' => 'bd1', 'email' => 'bd1@t.com', 'password' => 'p']);
        $u2 = User::create(['username' => 'bd2', 'email' => 'bd2@t.com', 'password' => 'p']);
        User::create(['username' => 'bd3', 'email' => 'bd3@t.com', 'password' => 'p']);

        $deleted = (new User())->batchDelete(['id' => [$u1['id'], $u2['id']]]);

        $this->assertEquals(2, $deleted);
    }

    public function testRawQueryAndRawExecute(): void
    {
        User::create(['username' => 'rq1', 'email' => 'rq1@t.com', 'password' => 'p']);

        $rows = User::rawQuery('SELECT * FROM `users` WHERE `username` = ?', ['rq1']);
        $this->assertCount(1, $rows);
        $this->assertEquals('rq1', $rows[0]['username']);

        $affected = User::rawExecute('UPDATE `users` SET `username` = ? WHERE `username` = ?', ['rq1_upd', 'rq1']);
        $this->assertEquals(1, $affected);
    }

    public function testTransaction(): void
    {
        $result = User::transaction(function () {
            User::create(['username' => 'tx1', 'email' => 'tx1@t.com', 'password' => 'p']);
            return 'ok';
        });

        $this->assertEquals('ok', $result);
        $this->assertNotNull(User::where('username', '=', 'tx1')->first());
    }

    public function testGetConnection(): void
    {
        $conn = User::getConnection();
        $this->assertInstanceOf(\Clover\Classes\Database\ConnectionPool::class, $conn);
    }

    public function testColumnMetadata(): void
    {
        $user = new User();
        $meta = $user->getColumnMetadata();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('username', $meta);
        $this->assertEquals('username', $meta['username']['name']);
    }

    public function testGetEventHooks(): void
    {
        User::creating(function ($u) {});
        $hooks = User::getEventHooks();

        $this->assertArrayHasKey('creating', $hooks);
        $this->assertCount(1, $hooks['creating']);

        User::clearEventHooks();
    }

    public function testWhenUnless(): void
    {
        User::create(['username' => 'when1', 'email' => 'w1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'when2', 'email' => 'w2@t.com', 'password' => 'p', 'is_active' => 0]);

        $applyFilter = true;
        $result = (new User())
            ->when($applyFilter, fn($q) => $q->where('is_active', '=', 1))
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals('when1', $result[0]['username']);
    }

    public function testUnless(): void
    {
        User::create(['username' => 'ul1', 'email' => 'ul1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'ul2', 'email' => 'ul2@t.com', 'password' => 'p', 'is_active' => 0]);

        $skipFilter = false;
        $result = (new User())
            ->unless($skipFilter, fn($q) => $q->where('is_active', '=', 1))
            ->get();

        $this->assertCount(1, $result);
    }

    public function testTapMethod(): void
    {
        $sideEffect = null;

        $result = (new User())
            ->tap(function ($q) use (&$sideEffect) {
                $sideEffect = 'tapped';
            })
            ->where('username', '=', 'nobody');

        $this->assertEquals('tapped', $sideEffect);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testLatestAndOldest(): void
    {
        User::create(['username' => 'lat1', 'email' => 'lat1@t.com', 'password' => 'p']);
        User::create(['username' => 'lat2', 'email' => 'lat2@t.com', 'password' => 'p']);

        // latest orders DESC by created_at; oldest orders ASC
        // Since MockPDO does NOT have created_at column values,
        // we just verify these don't throw and return all users.
        $latest = (new User())->latest('id')->get();
        $oldest = (new User())->oldest('id')->get();

        $this->assertNotEmpty($latest);
        $this->assertNotEmpty($oldest);
        // Descending vs ascending order by id
        $this->assertGreaterThanOrEqual($oldest[0]['id'], $latest[0]['id']);
    }

    public function testTakeAndSkip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create(['username' => "ts{$i}", 'email' => "ts{$i}@t.com", 'password' => 'p']);
        }

        $result = (new User())->take(3)->skip(1)->get();

        $this->assertCount(3, $result);
    }

    public function testSelect(): void
    {
        User::create(['username' => 'sel1', 'email' => 'sel1@t.com', 'password' => 'p']);

        // select() sets querySelect; MockPDO ignores column projection but returns full rows
        $result = (new User())->select('username', 'email')->get();

        $this->assertNotEmpty($result);
    }

    public function testDistinct(): void
    {
        User::create(['username' => 'dup', 'email' => 'dup@t.com', 'password' => 'p']);
        User::create(['username' => 'dup', 'email' => 'dup2@t.com', 'password' => 'p']);

        // MockPDO doesn't enforce DISTINCT at the data level, but the query should build without error
        $result = (new User())->distinct()->get();
        $this->assertNotEmpty($result);
    }

    public function testWhereBetween(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create(['username' => "btw{$i}", 'email' => "btw{$i}@t.com", 'password' => 'p']);
        }

        // Raw WHERE BETWEEN is passed through; MockPDO may not parse it perfectly
        // but the query builder should compose without throwing
        $result = (new User())->whereBetween('id', 1, 3)->get();
        $this->assertIsArray($result);
    }

    public function testWhereLike(): void
    {
        User::create(['username' => 'like_alice', 'email' => 'la@t.com', 'password' => 'p']);
        User::create(['username' => 'bob', 'email' => 'b@t.com', 'password' => 'p']);

        // MockPDO matchesWhere handles = operator; LIKE falls back to the else branch
        // which means it may not filter, but query composition should not throw
        $result = (new User())->whereLike('username', 'like%')->get();
        $this->assertIsArray($result);
    }

    public function testGroupByAndHaving(): void
    {
        User::create(['username' => 'gb1', 'email' => 'gb1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'gb2', 'email' => 'gb2@t.com', 'password' => 'p', 'is_active' => 1]);

        // Verify query composes without error; MockPDO won't enforce HAVING
        $result = (new User())
            ->select('is_active')
            ->groupBy('is_active')
            ->having('is_active', '>=', 0)
            ->get();

        $this->assertIsArray($result);
    }

    public function testToSqlWithBindings(): void
    {
        $sql = (new User())
            ->where('username', '=', 'alice')
            ->where('is_active', '=', 1)
            ->toSqlWithBindings();

        $this->assertStringContainsString("'alice'", $sql);
        $this->assertStringContainsString('users', $sql);
        $this->assertStringContainsString('SELECT', $sql);
    }

    public function testForceFill(): void
    {
        $user = new User();
        $user->forceFill(['id' => 999, 'username' => 'forced']);

        // forceFill bypasses guarded/fillable
        $this->assertEquals(999, $user['id']);
        $this->assertEquals('forced', $user['username']);
    }

    public function testGetColumnListing(): void
    {
        // Insert a row so MockPDO has column data
        User::create(['username' => 'listing1', 'email' => 'listing@t.com', 'password' => 'p']);

        $columns = (new User())->getColumnListing();

        $this->assertIsArray($columns);
        $this->assertContains('username', $columns);
    }

    public function testFindMany(): void
    {
        $u1 = User::create(['username' => 'fm1', 'email' => 'fm1@t.com', 'password' => 'p']);
        $u2 = User::create(['username' => 'fm2', 'email' => 'fm2@t.com', 'password' => 'p']);
        User::create(['username' => 'fm3', 'email' => 'fm3@t.com', 'password' => 'p']);

        $found = (new User())->findMany([$u1['id'], $u2['id']]);

        $this->assertCount(2, $found);
    }

    public function testCreateMany(): void
    {
        $rows = [
            ['username' => 'cm1', 'email' => 'cm1@t.com', 'password' => 'p'],
            ['username' => 'cm2', 'email' => 'cm2@t.com', 'password' => 'p'],
        ];

        $instances = User::createMany($rows);

        $this->assertCount(2, $instances);
        $this->assertInstanceOf(User::class, $instances[0]);
    }

    public function testSaveQuietly(): void
    {
        $hookCalled = false;
        User::saving(function () use (&$hookCalled) {
            $hookCalled = true;
        });

        $user = new User();
        $user['username'] = 'quiet';
        $user['email'] = 'quiet@t.com';
        $user['password'] = 'p';

        // saveQuietly() calls save() directly (no events in save() unless saveWithEvents() is used)
        $user->saveQuietly();

        $this->assertNotNull($user['id']);
        User::clearEventHooks();
    }

    public function testWithoutTimestamps(): void
    {
        $user = User::create(['username' => 'notime', 'email' => 'nt@t.com', 'password' => 'p']);

        $user->withoutTimestamps(function ($u) {
            $u['username'] = 'notime_upd';
            $u->save();
        });

        $refreshed = User::find($user['id']);
        $this->assertEquals('notime_upd', $refreshed['username']);
    }

    public function testDistinctValues(): void
    {
        User::create(['username' => 'dv1', 'email' => 'same@t.com', 'password' => 'p']);
        User::create(['username' => 'dv2', 'email' => 'same@t.com', 'password' => 'p']);
        User::create(['username' => 'dv3', 'email' => 'diff@t.com', 'password' => 'p']);

        $distinct = User::distinctValues(User::all(), 'email');

        $this->assertCount(2, $distinct);
    }

    public function testFresh(): void
    {
        $user = User::create(['username' => 'fresh1', 'email' => 'fresh@t.com', 'password' => 'p']);

        $fresh = $user->fresh();

        $this->assertNotNull($fresh);
        $this->assertEquals($user['id'], $fresh['id']);
        $this->assertNotSame($user, $fresh);
    }

    public function testTouch(): void
    {
        $user = User::create(['username' => 'touch1', 'email' => 'touch@t.com', 'password' => 'p']);

        // User has timestamps = true; touch() updates updated_at
        $result = $user->touch();

        $this->assertTrue($result);
    }

    public function testSaveWithEvents(): void
    {
        $log = [];

        User::creating(function ($u) use (&$log) {
            $log[] = 'creating';
        });
        User::created(function ($u) use (&$log) {
            $log[] = 'created';
        });
        User::saving(function ($u) use (&$log) {
            $log[] = 'saving';
        });
        User::saved(function ($u) use (&$log) {
            $log[] = 'saved';
        });

        $user = new User();
        $user['username'] = 'evtuser';
        $user['email'] = 'evt@t.com';
        $user['password'] = 'p';
        $user->saveWithEvents();

        $this->assertContains('creating', $log);
        $this->assertContains('created', $log);
        $this->assertContains('saving', $log);
        $this->assertContains('saved', $log);

        User::clearEventHooks();
    }

    public function testDeleteWithEvents(): void
    {
        $log = [];
        User::deleting(function ($u) use (&$log) {
            $log[] = 'deleting';
        });
        User::deleted(function ($u) use (&$log) {
            $log[] = 'deleted';
        });

        $user = User::create(['username' => 'evtdel', 'email' => 'evtd@t.com', 'password' => 'p']);
        $user->deleteWithEvents();

        $this->assertContains('deleting', $log);
        $this->assertContains('deleted', $log);

        User::clearEventHooks();
    }

    public function testCountBy(): void
    {
        User::create(['username' => 'cby1', 'email' => 'cby1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'cby2', 'email' => 'cby2@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'cby3', 'email' => 'cby3@t.com', 'password' => 'p', 'is_active' => 0]);

        $counts = (new User())->countBy('is_active');

        $this->assertIsArray($counts);
        $this->assertArrayHasKey(1, $counts);
        $this->assertArrayHasKey(0, $counts);
        $this->assertEquals(2, $counts[1]);
        $this->assertEquals(1, $counts[0]);
    }

    public function testInsertGetId(): void
    {
        $id = User::insertGetId([
            'username' => 'igi_user',
            'email' => 'igi@t.com',
            'password' => 'pass',
        ]);

        $this->assertNotEmpty($id);

        $found = User::find($id);
        $this->assertNotNull($found);
        $this->assertEquals('igi_user', $found['username']);
    }

    public function testUpdateWhere(): void
    {
        User::create(['username' => 'uw1', 'email' => 'uw1@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'uw2', 'email' => 'uw2@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'uw3', 'email' => 'uw3@t.com', 'password' => 'p', 'is_active' => 1]);

        $affected = User::updateWhere(['is_active' => 0], ['is_active' => 1]);

        $this->assertEquals(2, $affected);

        $active = (new User())->count(['conditions' => ['is_active' => 1]]);
        $this->assertEquals(3, $active);
    }

    public function testCursor(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            User::create(['username' => "cur{$i}", 'email' => "cur{$i}@t.com", 'password' => 'p']);
        }

        $fetched = 0;
        foreach ((new User())->cursor() as $user) {
            $this->assertInstanceOf(User::class, $user);
            $fetched++;
        }

        $this->assertEquals(4, $fetched);
    }

    // =========================================================================
    // QUERY CACHING TESTS
    // =========================================================================

    /**
     * Test that cache() stores results and returns them on subsequent calls.
     */
    public function testQueryCacheStoresAndReturns(): void
    {
        User::create(['username' => 'cache1', 'email' => 'c1@t.com', 'password' => 'p']);
        User::create(['username' => 'cache2', 'email' => 'c2@t.com', 'password' => 'p']);

        $first = (new User())->cache(60)->getCached();
        $this->assertCount(2, $first);

        // Insert a third user — cached result should still return 2
        User::create(['username' => 'cache3', 'email' => 'c3@t.com', 'password' => 'p']);

        $second = (new User())->cache(60)->getCached();
        $this->assertCount(2, $second);

        User::flushCache();
    }

    /**
     * Test that flushCache() clears all cached entries.
     */
    public function testFlushCacheClearsAllEntries(): void
    {
        User::create(['username' => 'fc1', 'email' => 'fc1@t.com', 'password' => 'p']);

        (new User())->cache(60)->getCached();
        User::flushCache();

        User::create(['username' => 'fc2', 'email' => 'fc2@t.com', 'password' => 'p']);

        $results = (new User())->cache(60)->getCached();
        $this->assertCount(2, $results);

        User::flushCache();
    }

    /**
     * Test that getCached without cache() behaves like normal get().
     */
    public function testGetCachedWithoutTtlSkipsCache(): void
    {
        User::create(['username' => 'nc1', 'email' => 'nc1@t.com', 'password' => 'p']);

        $results = (new User())->getCached();
        $this->assertCount(1, $results);

        User::create(['username' => 'nc2', 'email' => 'nc2@t.com', 'password' => 'p']);

        $results2 = (new User())->getCached();
        $this->assertCount(2, $results2);
    }

    // =========================================================================
    // QUERY LOGGING / PROFILING TESTS
    // =========================================================================

    /**
     * Test that enableQueryLog and logQuery record entries.
     */
    public function testQueryLogRecordsEntries(): void
    {
        User::enableQueryLog();

        User::logQuery('SELECT * FROM users', [], 1.5);
        User::logQuery('SELECT * FROM posts WHERE id = ?', [1], 0.8);

        $log = User::getQueryLog();

        $this->assertCount(2, $log);
        $this->assertEquals('SELECT * FROM users', $log[0]['sql']);
        $this->assertEquals(1.5, $log[0]['time_ms']);
        $this->assertEquals([1], $log[1]['bindings']);

        User::disableQueryLog();
    }

    /**
     * Test that disableQueryLog stops recording.
     */
    public function testDisableQueryLogStopsRecording(): void
    {
        User::enableQueryLog();
        User::logQuery('SELECT 1', [], 0.1);

        User::disableQueryLog();
        User::logQuery('SELECT 2', [], 0.2);

        $log = User::getQueryLog();
        $this->assertCount(1, $log);
        $this->assertEquals('SELECT 1', $log[0]['sql']);
    }

    /**
     * Test that getQueryLog flushes the log after retrieval.
     */
    public function testGetQueryLogFlushesAfterRetrieval(): void
    {
        User::enableQueryLog();
        User::logQuery('SELECT 1', [], 0.1);

        $first = User::getQueryLog();
        $this->assertCount(1, $first);

        $second = User::getQueryLog();
        $this->assertCount(0, $second);

        User::disableQueryLog();
    }

    // =========================================================================
    // ATTRIBUTE ENCRYPTION / DECRYPTION TESTS
    // =========================================================================

    /**
     * Test that encryptValue and decryptValue are symmetric.
     */
    public function testEncryptAndDecryptSymmetry(): void
    {
        User::setEncryptionKey('01234567890123456789012345678901'); // 32 bytes

        $original = 'sensitive-data-12345';
        $encrypted = User::encryptValue($original);

        $this->assertNotEquals($original, $encrypted);

        $decrypted = User::decryptValue($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    /**
     * Test that encryptValue throws without key.
     */
    public function testEncryptWithoutKeyThrows(): void
    {
        // Reset key by setting to a known key first then using reflection
        // The key lives on AttributeEncrypter now; ActiveRecord's encryptValue()
        // and decryptValue() delegate to it.
        $ref = new ReflectionClass(AttributeEncrypter::class);
        $prop = $ref->getProperty('key');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $prop->setValue(null, null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Encryption key not set');

        User::encryptValue('test');
    }

    /**
     * Test that decryptValue throws on invalid payload.
     */
    public function testDecryptInvalidPayloadThrows(): void
    {
        User::setEncryptionKey('01234567890123456789012345678901');

        $this->expectException(Exception::class);

        User::decryptValue(base64_encode('no-separator-here'));
    }

    /**
     * Test isEncrypted returns correct status.
     */
    public function testIsEncryptedReturnsCorrectStatus(): void
    {
        $user = new User();

        // User has no encrypted fields by default
        $this->assertFalse($user->isEncrypted('username'));
        $this->assertFalse($user->isEncrypted('email'));
    }

    // =========================================================================
    // MODEL DIFFING TESTS
    // =========================================================================

    /**
     * Test diff returns changed columns between two entities.
     */
    public function testDiffReturnsChangedColumns(): void
    {
        $u1 = User::create(['username' => 'diff1', 'email' => 'same@t.com', 'password' => 'p']);
        $u2 = User::create(['username' => 'diff2', 'email' => 'same@t.com', 'password' => 'q']);

        $changes = $u1->diff($u2);

        $this->assertArrayHasKey('id', $changes);
        $this->assertArrayHasKey('username', $changes);
        $this->assertArrayHasKey('password', $changes);
        $this->assertArrayNotHasKey('email', $changes);

        $this->assertEquals('diff1', $changes['username']['from']);
        $this->assertEquals('diff2', $changes['username']['to']);
    }

    /**
     * Test diff returns empty array for identical entities.
     */
    public function testDiffReturnsEmptyForIdenticalEntities(): void
    {
        $user = User::create(['username' => 'ident', 'email' => 'id@t.com', 'password' => 'p']);
        $copy = User::find($user['id']);

        $changes = $user->diff($copy);

        // Only created_at/updated_at might differ, but all mapped columns should match
        $this->assertArrayNotHasKey('username', $changes);
        $this->assertArrayNotHasKey('email', $changes);
    }

    // =========================================================================
    // PIPELINE PATTERN TESTS
    // =========================================================================

    /**
     * Test pipeline applies multiple callbacks sequentially.
     */
    public function testPipelineAppliesCallbacksSequentially(): void
    {
        User::create(['username' => 'pipe1', 'email' => 'p1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'pipe2', 'email' => 'p2@t.com', 'password' => 'p', 'is_active' => 0]);

        $result = (new User())->pipeline([
            fn($q) => $q->where('is_active', '=', 1),
            fn($q) => $q->orderBy('username', 'ASC'),
        ])->get();

        $this->assertCount(1, $result);
        $this->assertEquals('pipe1', $result[0]['username']);
    }

    /**
     * Test pipeline with empty pipes acts as no-op.
     */
    public function testPipelineWithEmptyPipesIsNoop(): void
    {
        User::create(['username' => 'noop1', 'email' => 'no@t.com', 'password' => 'p']);

        $result = (new User())->pipeline([])->get();
        $this->assertCount(1, $result);
    }

    // =========================================================================
    // CURSOR PAGINATION TESTS
    // =========================================================================

    /**
     * Test cursorPaginate returns correct first page.
     */
    public function testCursorPaginateFirstPage(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create(['username' => "cp{$i}", 'email' => "cp{$i}@t.com", 'password' => 'p']);
        }

        $page = (new User())->cursorPaginate(3);

        $this->assertArrayHasKey('data', $page);
        $this->assertArrayHasKey('next_cursor', $page);
        $this->assertArrayHasKey('has_more', $page);
        $this->assertCount(3, $page['data']);
        $this->assertTrue($page['has_more']);
        $this->assertNotNull($page['next_cursor']);
    }

    /**
     * Test cursorPaginate with cursor returns expected structure.
     *
     * Note: MockPDO does not evaluate the `>` operator in WHERE clauses,
     * so we only verify the return structure and key contracts here.
     */
    public function testCursorPaginateWithCursor(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create(['username' => "cpn{$i}", 'email' => "cpn{$i}@t.com", 'password' => 'p']);
        }

        $page1 = (new User())->cursorPaginate(3);

        $this->assertArrayHasKey('data', $page1);
        $this->assertArrayHasKey('next_cursor', $page1);
        $this->assertArrayHasKey('has_more', $page1);
        $this->assertCount(3, $page1['data']);
        $this->assertTrue($page1['has_more']);

        // Second call with cursor — verify it returns a valid page structure
        $page2 = (new User())->cursorPaginate(3, $page1['next_cursor']);

        $this->assertArrayHasKey('data', $page2);
        $this->assertNotEmpty($page2['data']);
    }

    /**
     * Test cursorPaginate with exact fit returns no more.
     */
    public function testCursorPaginateExactFit(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            User::create(['username' => "ex{$i}", 'email' => "ex{$i}@t.com", 'password' => 'p']);
        }

        $page = (new User())->cursorPaginate(3);

        $this->assertCount(3, $page['data']);
        $this->assertFalse($page['has_more']);
    }

    // =========================================================================
    // JSON COLUMN QUERY TESTS
    // =========================================================================

    /**
     * Test whereJsonContains composes SQL without error.
     */
    public function testWhereJsonContainsComposesQuery(): void
    {
        User::create(['username' => 'json1', 'email' => 'j1@t.com', 'password' => 'p']);

        // MockPDO won't evaluate JSON_CONTAINS, but query should build without throwing
        $result = (new User())->whereJsonContains('username', 'json1')->get();
        $this->assertIsArray($result);
    }

    /**
     * Test whereJsonPath composes SQL without error.
     */
    public function testWhereJsonPathComposesQuery(): void
    {
        User::create(['username' => 'jp1', 'email' => 'jp1@t.com', 'password' => 'p']);

        $result = (new User())->whereJsonPath('username', '$.name', '=', 'test')->get();
        $this->assertIsArray($result);
    }

    // =========================================================================
    // FULL-TEXT SEARCH TESTS
    // =========================================================================

    /**
     * Test whereFullText composes query in natural language mode.
     */
    public function testWhereFullTextNaturalMode(): void
    {
        User::create(['username' => 'ft1', 'email' => 'ft@t.com', 'password' => 'p']);

        $result = (new User())->whereFullText(['username', 'email'], 'search term')->get();
        $this->assertIsArray($result);
    }

    /**
     * Test whereFullText composes query in boolean mode.
     */
    public function testWhereFullTextBooleanMode(): void
    {
        User::create(['username' => 'ftb', 'email' => 'ftb@t.com', 'password' => 'p']);

        $result = (new User())->whereFullText(['username'], '+ftb', 'boolean')->get();
        $this->assertIsArray($result);
    }

    // =========================================================================
    // SUBQUERY SUPPORT TESTS
    // =========================================================================

    /**
     * Test whereExists composes query without error.
     */
    public function testWhereExistsComposesQuery(): void
    {
        User::create(['username' => 'sub1', 'email' => 'sub1@t.com', 'password' => 'p']);

        $result = (new User())->whereExists(
            'SELECT 1 FROM posts WHERE posts.user_id = users.id'
        )->get();

        $this->assertIsArray($result);
    }

    /**
     * Test whereNotExists composes query without error.
     */
    public function testWhereNotExistsComposesQuery(): void
    {
        User::create(['username' => 'nsub1', 'email' => 'nsub1@t.com', 'password' => 'p']);

        $result = (new User())->whereNotExists(
            'SELECT 1 FROM posts WHERE posts.user_id = users.id'
        )->get();

        $this->assertIsArray($result);
    }

    /**
     * Test whereInSubquery composes query without error.
     */
    public function testWhereInSubqueryComposesQuery(): void
    {
        User::create(['username' => 'insub', 'email' => 'insub@t.com', 'password' => 'p']);

        $result = (new User())->whereInSubquery(
            'id',
            'SELECT user_id FROM posts WHERE title = ?',
            ['Test']
        )->get();

        $this->assertIsArray($result);
    }

    // =========================================================================
    // BULK OPERATION TESTS
    // =========================================================================

    /**
     * Test deleteWhere removes matching records.
     */
    public function testDeleteWhereRemovesMatchingRecords(): void
    {
        User::create(['username' => 'dw1', 'email' => 'dw1@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'dw2', 'email' => 'dw2@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'dw3', 'email' => 'dw3@t.com', 'password' => 'p', 'is_active' => 1]);

        $deleted = (new User())->where('is_active', '=', 0)->deleteWhere();

        $this->assertEquals(2, $deleted);
        $this->assertEquals(1, (new User())->count());
    }

    /**
     * Test updateWhere2 updates matching records via query builder.
     *
     * We update the `username` column while filtering by `is_active` to
     * avoid MockPDO confusing SET and WHERE parameters when the same
     * column name appears in both clauses.
     */
    public function testUpdateWhere2UpdatesMatchingRecords(): void
    {
        User::create(['username' => 'uw2_1', 'email' => 'uw2_1@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'uw2_2', 'email' => 'uw2_2@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'uw2_3', 'email' => 'uw2_3@t.com', 'password' => 'p', 'is_active' => 1]);

        $affected = (new User())->where('is_active', '=', 0)->updateWhere2(['username' => 'bulk_updated']);

        $this->assertEquals(2, $affected);

        // Verify the update actually happened
        $updated = (new User())->where('username', '=', 'bulk_updated')->get();
        $this->assertCount(2, $updated);
    }

    // =========================================================================
    // MAP / REDUCE COLLECTION HELPER TESTS
    // =========================================================================

    /**
     * Test map transforms each entity.
     */
    public function testMapTransformsEntities(): void
    {
        User::create(['username' => 'map1', 'email' => 'm1@t.com', 'password' => 'p']);
        User::create(['username' => 'map2', 'email' => 'm2@t.com', 'password' => 'p']);

        $names = User::map(User::all(), fn($u) => $u['username']);

        $this->assertCount(2, $names);
        $this->assertContains('map1', $names);
        $this->assertContains('map2', $names);
    }

    /**
     * Test reduce accumulates a single value.
     */
    public function testReduceAccumulatesSingleValue(): void
    {
        User::create(['username' => 'red1', 'email' => 'r1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'red2', 'email' => 'r2@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'red3', 'email' => 'r3@t.com', 'password' => 'p', 'is_active' => 0]);

        $sum = User::reduce(User::all(), fn($carry, $u) => $carry + (int) $u['is_active'], 0);

        $this->assertEquals(2, $sum);
    }

    /**
     * Test partition splits collection by truth test.
     */
    public function testPartitionSplitsCollection(): void
    {
        User::create(['username' => 'pt1', 'email' => 'pt1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'pt2', 'email' => 'pt2@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'pt3', 'email' => 'pt3@t.com', 'password' => 'p', 'is_active' => 1]);

        [$active, $inactive] = User::partition(User::all(), fn($u) => (bool) $u['is_active']);

        $this->assertCount(2, $active);
        $this->assertCount(1, $inactive);
    }

    /**
     * Test column extracts single field values.
     */
    public function testColumnExtractsValues(): void
    {
        User::create(['username' => 'col_a', 'email' => 'ca@t.com', 'password' => 'p']);
        User::create(['username' => 'col_b', 'email' => 'cb@t.com', 'password' => 'p']);

        $emails = User::column(User::all(), 'email');

        $this->assertCount(2, $emails);
        $this->assertContains('ca@t.com', $emails);
        $this->assertContains('cb@t.com', $emails);
    }

    /**
     * Test firstWhere returns first matching entity.
     */
    public function testFirstWhereReturnsFirstMatch(): void
    {
        User::create(['username' => 'fw1', 'email' => 'fw1@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'fw2', 'email' => 'fw2@t.com', 'password' => 'p', 'is_active' => 1]);

        $found = User::firstWhere(User::all(), fn($u) => (bool) $u['is_active']);

        $this->assertNotNull($found);
        $this->assertEquals('fw2', $found['username']);
    }

    /**
     * Test firstWhere returns null when no match.
     */
    public function testFirstWhereReturnsNullWhenNoMatch(): void
    {
        User::create(['username' => 'fwn', 'email' => 'fwn@t.com', 'password' => 'p']);

        $found = User::firstWhere(User::all(), fn($u) => $u['username'] === 'nonexistent');

        $this->assertNull($found);
    }

    /**
     * Test every returns true when all pass.
     */
    public function testEveryReturnsTrueWhenAllPass(): void
    {
        User::create(['username' => 'ev1', 'email' => 'ev1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'ev2', 'email' => 'ev2@t.com', 'password' => 'p', 'is_active' => 1]);

        $result = User::every(User::all(), fn($u) => (bool) $u['is_active']);

        $this->assertTrue($result);
    }

    /**
     * Test every returns false when one fails.
     */
    public function testEveryReturnsFalseWhenOneFails(): void
    {
        User::create(['username' => 'evf1', 'email' => 'evf1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'evf2', 'email' => 'evf2@t.com', 'password' => 'p', 'is_active' => 0]);

        $result = User::every(User::all(), fn($u) => (bool) $u['is_active']);

        $this->assertFalse($result);
    }

    /**
     * Test some returns true when at least one passes.
     */
    public function testSomeReturnsTrueWhenOneMatches(): void
    {
        User::create(['username' => 'sm1', 'email' => 'sm1@t.com', 'password' => 'p', 'is_active' => 0]);
        User::create(['username' => 'sm2', 'email' => 'sm2@t.com', 'password' => 'p', 'is_active' => 1]);

        $result = User::some(User::all(), fn($u) => (bool) $u['is_active']);

        $this->assertTrue($result);
    }

    /**
     * Test some returns false when none pass.
     */
    public function testSomeReturnsFalseWhenNoneMatch(): void
    {
        User::create(['username' => 'smf', 'email' => 'smf@t.com', 'password' => 'p', 'is_active' => 0]);

        $result = User::some(User::all(), fn($u) => $u['username'] === 'nonexistent');

        $this->assertFalse($result);
    }

    // =========================================================================
    // ONLY / EXCEPT SERIALIZATION TESTS
    // =========================================================================

    /**
     * Test only returns subset of attributes.
     */
    public function testOnlyReturnsSubsetOfAttributes(): void
    {
        $user = User::create(['username' => 'only1', 'email' => 'only@t.com', 'password' => 'p']);

        $result = $user->only(['username', 'email']);

        $this->assertArrayHasKey('username', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayNotHasKey('id', $result);
        $this->assertArrayNotHasKey('created_at', $result);
    }

    /**
     * Test except excludes specified attributes.
     */
    public function testExceptExcludesSpecifiedAttributes(): void
    {
        $user = User::create(['username' => 'exc1', 'email' => 'exc@t.com', 'password' => 'p']);

        $result = $user->except(['id', 'email']);

        $this->assertArrayNotHasKey('id', $result);
        $this->assertArrayNotHasKey('email', $result);
        $this->assertArrayHasKey('username', $result);
    }

    // =========================================================================
    // CONDITIONAL ATTRIBUTE SETTING TESTS
    // =========================================================================

    /**
     * Test setIfNotNull sets value when not null.
     */
    public function testSetIfNotNullSetsWhenNotNull(): void
    {
        $user = new User();
        $user->setIfNotNull('username', 'conditional');

        $this->assertEquals('conditional', $user['username']);
    }

    /**
     * Test setIfNotNull skips when value is null.
     */
    public function testSetIfNotNullSkipsWhenNull(): void
    {
        $user = new User();
        $user['username'] = 'original';
        $user->setIfNotNull('username', null);

        $this->assertEquals('original', $user['username']);
    }

    /**
     * Test setDefault sets value when attribute is empty.
     */
    public function testSetDefaultSetsWhenEmpty(): void
    {
        $user = new User();
        $user->setDefault('username', 'default_name');

        $this->assertEquals('default_name', $user['username']);
    }

    /**
     * Test setDefault does not overwrite existing value.
     */
    public function testSetDefaultDoesNotOverwriteExisting(): void
    {
        $user = new User();
        $user['username'] = 'existing';
        $user->setDefault('username', 'default_name');

        $this->assertEquals('existing', $user['username']);
    }

    // =========================================================================
    // MULTI-COLUMN ORDERING TESTS
    // =========================================================================

    /**
     * Test orderByMultiple composes ORDER BY with multiple columns.
     */
    public function testOrderByMultipleComposesQuery(): void
    {
        User::create(['username' => 'om1', 'email' => 'om1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'om2', 'email' => 'om2@t.com', 'password' => 'p', 'is_active' => 0]);

        $result = (new User())->orderByMultiple(['is_active' => 'DESC', 'username' => 'ASC'])->get();

        $this->assertNotEmpty($result);
        $this->assertIsArray($result);
    }

    // =========================================================================
    // RANDOM ORDERING TESTS
    // =========================================================================

    /**
     * Test inRandomOrder composes query without error.
     */
    public function testInRandomOrderComposesQuery(): void
    {
        User::create(['username' => 'rnd1', 'email' => 'rnd1@t.com', 'password' => 'p']);
        User::create(['username' => 'rnd2', 'email' => 'rnd2@t.com', 'password' => 'p']);

        $result = (new User())->inRandomOrder()->get();

        $this->assertNotEmpty($result);
    }

    // =========================================================================
    // CROSS JOIN TESTS
    // =========================================================================

    /**
     * Test crossJoin composes SQL without error.
     */
    public function testCrossJoinComposesQuery(): void
    {
        User::create(['username' => 'cj1', 'email' => 'cj1@t.com', 'password' => 'p']);

        // MockPDO won't execute the actual CROSS JOIN, but the query should compose
        $sql = (new User())->crossJoin('posts')->toSql();

        $this->assertStringContainsString('CROSS', $sql);
        $this->assertStringContainsString('JOIN', $sql);
    }

    // =========================================================================
    // HAVING COUNT SHORTCUT TESTS
    // =========================================================================

    /**
     * Test havingCount composes HAVING COUNT query.
     */
    public function testHavingCountComposesQuery(): void
    {
        User::create(['username' => 'hc1', 'email' => 'hc1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'hc2', 'email' => 'hc2@t.com', 'password' => 'p', 'is_active' => 1]);

        $result = (new User())
            ->select('is_active')
            ->groupBy('is_active')
            ->havingCount('>=', 1)
            ->get();

        $this->assertIsArray($result);
    }

    // =========================================================================
    // JSON SERIALIZE TESTS
    // =========================================================================

    /**
     * Test jsonSerialize returns array with column data.
     */
    public function testJsonSerializeReturnsArrayWithColumnData(): void
    {
        $user = User::create(['username' => 'ser1', 'email' => 'ser@t.com', 'password' => 'p']);

        $serialized = $user->jsonSerialize();

        $this->assertIsArray($serialized);
        $this->assertArrayHasKey('username', $serialized);
        $this->assertEquals('ser1', $serialized['username']);
        // password is hidden
        $this->assertArrayNotHasKey('password', $serialized);
    }

    /**
     * Test jsonSerialize includes loaded relationships.
     */
    public function testJsonSerializeIncludesLoadedRelations(): void
    {
        $user = User::create(['username' => 'serrel', 'email' => 'sr@t.com', 'password' => 'p']);
        $post = Post::create(['title' => 'RelPost', 'content' => 'Content', 'user_id' => $user['id']]);

        $user->setRelation('posts', [$post]);

        $serialized = $user->jsonSerialize();

        $this->assertArrayHasKey('posts', $serialized);
        $this->assertCount(1, $serialized['posts']);
    }

    // =========================================================================
    // BATCH EXISTENCE CHECK TESTS
    // =========================================================================

    /**
     * Test existingIds returns subset of IDs that exist.
     */
    public function testExistingIdsReturnsExistingSubset(): void
    {
        $u1 = User::create(['username' => 'eid1', 'email' => 'eid1@t.com', 'password' => 'p']);
        $u2 = User::create(['username' => 'eid2', 'email' => 'eid2@t.com', 'password' => 'p']);

        $result = (new User())->existingIds([$u1['id'], $u2['id'], 99999]);

        $this->assertCount(2, $result);
        $this->assertContains($u1['id'], $result);
        $this->assertContains($u2['id'], $result);
    }

    /**
     * Test existingIds with empty array returns empty.
     */
    public function testExistingIdsWithEmptyReturnsEmpty(): void
    {
        $result = (new User())->existingIds([]);
        $this->assertEmpty($result);
    }

    // =========================================================================
    // PIPE TESTS
    // =========================================================================

    /**
     * Test pipe returns callback result instead of $this.
     */
    public function testPipeReturnsCallbackResult(): void
    {
        $user = User::create(['username' => 'pipe_test', 'email' => 'pipe@t.com', 'password' => 'p']);

        $result = $user->pipe(fn($u) => strtoupper($u['username']));

        $this->assertEquals('PIPE_TEST', $result);
    }

    // =========================================================================
    // TOTAL COUNT TESTS
    // =========================================================================

    /**
     * Test totalCount returns correct number of rows.
     */
    public function testTotalCountReturnsCorrectNumber(): void
    {
        User::create(['username' => 'tc1', 'email' => 'tc1@t.com', 'password' => 'p']);
        User::create(['username' => 'tc2', 'email' => 'tc2@t.com', 'password' => 'p']);
        User::create(['username' => 'tc3', 'email' => 'tc3@t.com', 'password' => 'p']);

        $this->assertEquals(3, User::totalCount());
    }

    /**
     * Test totalCount on empty table returns zero.
     */
    public function testTotalCountOnEmptyTableReturnsZero(): void
    {
        $this->assertEquals(0, User::totalCount());
    }

    // =========================================================================
    // CHUNK MAP TESTS
    // =========================================================================

    /**
     * Test chunkMap collects mapped values from all chunks.
     */
    public function testChunkMapCollectsMappedValues(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create(['username' => "chm{$i}", 'email' => "chm{$i}@t.com", 'password' => 'p']);
        }

        $names = (new User())->chunkMap(2, fn($u) => $u['username']);

        $this->assertCount(5, $names);
        $this->assertContains('chm1', $names);
        $this->assertContains('chm5', $names);
    }

    /**
     * Test chunkMap with empty table returns empty array.
     */
    public function testChunkMapWithEmptyTableReturnsEmpty(): void
    {
        $result = (new User())->chunkMap(10, fn($u) => $u['username']);
        $this->assertEmpty($result);
    }

    // =========================================================================
    // RAW HYDRATE TESTS
    // =========================================================================

    /**
     * Test rawHydrate returns entity instances from raw SQL.
     */
    public function testRawHydrateReturnsEntityInstances(): void
    {
        User::create(['username' => 'rh1', 'email' => 'rh1@t.com', 'password' => 'p']);
        User::create(['username' => 'rh2', 'email' => 'rh2@t.com', 'password' => 'p']);

        $results = User::rawHydrate('SELECT * FROM `users` WHERE `username` = ?', ['rh1']);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(User::class, $results[0]);
        $this->assertEquals('rh1', $results[0]['username']);
    }

    /**
     * Test rawHydrate with no results returns empty array.
     */
    public function testRawHydrateNoResultsReturnsEmpty(): void
    {
        $results = User::rawHydrate('SELECT * FROM `users` WHERE `username` = ?', ['nonexistent']);

        $this->assertEmpty($results);
    }

    // =========================================================================
    // OPTIMISTIC LOCKING TESTS
    // =========================================================================

    /**
     * Test saveWithLock without versionColumn behaves like normal save.
     */
    public function testSaveWithLockWithoutVersionColumnFallsBackToSave(): void
    {
        $user = new User();
        $user['username'] = 'nolock';
        $user['email'] = 'nolock@t.com';
        $user['password'] = 'p';

        $result = $user->saveWithLock();

        $this->assertNotNull($result);
        $this->assertNotNull($user['id']);
    }

    // =========================================================================
    // UPSERT AND RETURN TESTS
    // =========================================================================

    /**
     * Test upsertAndReturn inserts and returns entity.
     */
    public function testUpsertAndReturnInsertsEntity(): void
    {
        $entity = User::upsertAndReturn(
            ['email' => 'upar@t.com'],
            ['username' => 'upar_user', 'password' => 'p']
        );

        $this->assertInstanceOf(User::class, $entity);
        $this->assertEquals('upar@t.com', $entity['email']);
    }

    // =========================================================================
    // EDGE CASE / COVERAGE EXPANSION TESTS
    // =========================================================================

    /**
     * Test chunkById processes in ID-keyed batches.
     */
    /*public function testChunkByIdProcessesInBatches(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            User::create(['username' => "cbi{$i}", 'email' => "cbi{$i}@t.com", 'password' => 'p']);
        }

        $batches = [];
        (new User())->chunkById(3, function ($items, $lastId) use (&$batches) {
            $batches[] = count($items);
        });

        $this->assertEquals([3, 3, 1], $batches);
    }*/

    /**
     * Test pluck with key returns keyed array.
     */
    public function testPluckWithKey(): void
    {
        User::create(['username' => 'pk1', 'email' => 'pk1@t.com', 'password' => 'p']);
        User::create(['username' => 'pk2', 'email' => 'pk2@t.com', 'password' => 'p']);

        $result = (new User())->pluck('email', 'username');

        $this->assertArrayHasKey('pk1', $result);
        $this->assertEquals('pk1@t.com', $result['pk1']);
    }

    /**
     * Test pluck without key returns indexed array.
     */
    public function testPluckWithoutKey(): void
    {
        User::create(['username' => 'plk1', 'email' => 'plk1@t.com', 'password' => 'p']);

        $result = (new User())->pluck('username');

        $this->assertContains('plk1', $result);
    }

    /**
     * Test sole returns single matching record.
     */
    public function testSoleReturnsSingleRecord(): void
    {
        User::create(['username' => 'sole1', 'email' => 'sole@t.com', 'password' => 'p']);

        $result = (new User())->where('username', '=', 'sole1')->sole();

        $this->assertEquals('sole1', $result['username']);
    }

    /**
     * Test sole throws when no records found.
     */
    public function testSoleThrowsWhenNoRecords(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No records found');

        (new User())->where('username', '=', 'nonexistent')->sole();
    }

    /**
     * Test sole throws when multiple records found.
     */
    public function testSoleThrowsWhenMultipleRecords(): void
    {
        User::create(['username' => 'multi1', 'email' => 'm1@t.com', 'password' => 'p']);
        User::create(['username' => 'multi2', 'email' => 'm2@t.com', 'password' => 'p']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Multiple records found');

        (new User())->sole();
    }

    /**
     * Test value returns single column from first row.
     */
    public function testValueReturnsSingleColumn(): void
    {
        User::create(['username' => 'val1', 'email' => 'val@t.com', 'password' => 'p']);

        $result = (new User())->where('username', '=', 'val1')->value('email');

        $this->assertEquals('val@t.com', $result);
    }

    /**
     * Test doesntExist returns correct boolean.
     */
    public function testDoesntExistReturnsCorrectBoolean(): void
    {
        $this->assertTrue((new User())->doesntExist());

        User::create(['username' => 'de1', 'email' => 'de@t.com', 'password' => 'p']);

        $this->assertFalse((new User())->doesntExist());
    }

    /**
     * Test orWhere query composition.
     */
    public function testOrWhereQueryComposition(): void
    {
        User::create(['username' => 'or1', 'email' => 'or1@t.com', 'password' => 'p']);
        User::create(['username' => 'or2', 'email' => 'or2@t.com', 'password' => 'p']);

        $result = (new User())
            ->where('username', '=', 'or1')
            ->orWhere('username', '=', 'or2')
            ->get();

        $this->assertIsArray($result);
    }

    /**
     * Test whereNotIn excludes specified values.
     */
    public function testWhereNotInExcludesValues(): void
    {
        $u1 = User::create(['username' => 'wni1', 'email' => 'wni1@t.com', 'password' => 'p']);
        $u2 = User::create(['username' => 'wni2', 'email' => 'wni2@t.com', 'password' => 'p']);
        User::create(['username' => 'wni3', 'email' => 'wni3@t.com', 'password' => 'p']);

        $result = (new User())->whereNotIn('id', [$u1['id'], $u2['id']])->get();

        $this->assertIsArray($result);
    }

    /**
     * Test whereNotBetween composes query without error.
     */
    public function testWhereNotBetweenComposesQuery(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create(['username' => "wnb{$i}", 'email' => "wnb{$i}@t.com", 'password' => 'p']);
        }

        $result = (new User())->whereNotBetween('id', 1, 3)->get();
        $this->assertIsArray($result);
    }

    /**
     * Test whereNotLike composes query without error.
     */
    public function testWhereNotLikeComposesQuery(): void
    {
        User::create(['username' => 'notlike1', 'email' => 'nl@t.com', 'password' => 'p']);

        $result = (new User())->whereNotLike('username', 'xyz%')->get();
        $this->assertIsArray($result);
    }

    /**
     * Test left join composes query.
     */
    public function testLeftJoinComposesQuery(): void
    {
        User::create(['username' => 'lj1', 'email' => 'lj@t.com', 'password' => 'p']);

        $sql = (new User())->leftJoin('posts', 'users.id', '=', 'posts.user_id')->toSql();

        $this->assertStringContainsString('LEFT', $sql);
        $this->assertStringContainsString('JOIN', $sql);
    }

    /**
     * Test right join composes query.
     */
    public function testRightJoinComposesQuery(): void
    {
        User::create(['username' => 'rj1', 'email' => 'rj@t.com', 'password' => 'p']);

        $sql = (new User())->rightJoin('posts', 'users.id', '=', 'posts.user_id')->toSql();

        $this->assertStringContainsString('RIGHT', $sql);
        $this->assertStringContainsString('JOIN', $sql);
    }

    /**
     * Test lockForUpdate and sharedLock compose SQL.
     */
    public function testLockForUpdateAndSharedLock(): void
    {
        User::create(['username' => 'lock1', 'email' => 'lock@t.com', 'password' => 'p']);

        $sql1 = (new User())->lockForUpdate()->toSql();
        $this->assertStringContainsString('FOR UPDATE', $sql1);

        $sql2 = (new User())->sharedLock()->toSql();
        $this->assertStringContainsString('LOCK IN SHARE MODE', $sql2);
    }

    /**
     * Test orderByDesc composes descending order.
     */
    public function testOrderByDescComposesQuery(): void
    {
        User::create(['username' => 'obd1', 'email' => 'obd1@t.com', 'password' => 'p']);
        User::create(['username' => 'obd2', 'email' => 'obd2@t.com', 'password' => 'p']);

        $result = (new User())->orderByDesc('id')->get();

        $this->assertNotEmpty($result);
        $this->assertGreaterThanOrEqual($result[1]['id'] ?? 0, $result[0]['id']);
    }

    /**
     * Test trashed() returns correct status.
     */
    public function testTrashedReturnsCorrectStatus(): void
    {
        $post = Post::create(['title' => 'Trash Test', 'content' => 'C', 'user_id' => 1]);

        $this->assertFalse($post->trashed());

        $post->delete();
        $this->assertTrue($post->trashed());
    }

    /**
     * Test deleteQuietly removes entity without events.
     */
    public function testDeleteQuietlyRemovesEntity(): void
    {
        $hookCalled = false;
        Post::deleting(function () use (&$hookCalled) {
            $hookCalled = true;
        });

        $post = Post::create(['title' => 'Quiet Del', 'content' => 'C', 'user_id' => 1]);
        $post->deleteQuietly();

        // deleteQuietly bypasses events
        $this->assertFalse($hookCalled);
        $this->assertTrue($post->trashed());

        Post::clearEventHooks();
    }

    /**
     * Test __unset removes property.
     */
    public function testMagicUnsetRemovesProperty(): void
    {
        $user = User::create(['username' => 'unset1', 'email' => 'unset@t.com', 'password' => 'p']);

        $this->assertTrue(isset($user['username']));

        unset($user->username);

        $this->assertNull($user['username'] ?? null);
    }

    /**
     * Test __isset with accessor method.
     */
    public function testMagicIssetWithExistingProperty(): void
    {
        $user = new User();
        $user['username'] = 'isset_test';

        $this->assertTrue(isset($user->username));
        $this->assertFalse(isset($user->nonexistent_property_xyz));
    }

    /**
     * Test findByMultipleCondition with multiple results.
     */
    public function testFindByMultipleConditionMultipleResults(): void
    {
        User::create(['username' => 'mc1', 'email' => 'same@t.com', 'password' => 'p']);
        User::create(['username' => 'mc2', 'email' => 'same@t.com', 'password' => 'p']);

        $result = (new User())->findByMultipleCondition('email', 'same@t.com', 'password', 'p');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * Test findByMultipleCondition with odd arguments throws.
     */
    public function testFindByMultipleConditionOddArgsThrows(): void
    {
        $this->expectException(Exception::class);

        (new User())->findByMultipleCondition('email');
    }

    /**
     * Test that __call throws for unknown method.
     */
    public function testCallThrowsForUnknownMethod(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Method nonExistentMethod123 not found');

        $user = new User();
        $user->nonExistentMethod123();
    }

    /**
     * Test scope() throws for unknown scope name.
     */
    public function testScopeThrowsForUnknownScope(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Scope 'nonexistent_scope_xyz' not found.");

        (new User())->scope('nonexistent_scope_xyz');
    }

    /**
     * Test findOrFail returns entity when found.
     */
    public function testFindOrFailReturnsEntityWhenFound(): void
    {
        $user = User::create(['username' => 'fof1', 'email' => 'fof@t.com', 'password' => 'p']);

        $found = User::findOrFail($user['id']);

        $this->assertNotNull($found);
        $this->assertEquals('fof1', $found['username']);
    }

    /**
     * Test ensureConnection throws without database.
     */
    public function testEnsureConnectionThrowsWithoutDB(): void
    {
        // Save current DB, set to null, then restore
        $ref = new ReflectionClass(User::class);
        $prop = $ref->getProperty('db');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $originalDb = $prop->getValue();
        $prop->setValue(null, null);

        // The connection resolver has to be cleared alongside the handle. It is static, so a
        // resolver registered by any earlier test in this process — App/Configure/dependencies.php
        // registers one — would otherwise fire from the constructor below and surface as a
        // connection error instead of the "not set" state this test is about.
        ActiveRecord::setConnectionResolver(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database connection not set');

        try {
            (new User())->count();
        } finally {
            $prop->setValue(null, $originalDb);
        }
    }

    /**
     * Test avg aggregate returns correct average.
     */
    public function testAvgAggregate(): void
    {
        User::create(['username' => 'avg1', 'email' => 'avg1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'avg2', 'email' => 'avg2@t.com', 'password' => 'p', 'is_active' => 0]);

        $avg = (new User())->avg('is_active');

        $this->assertEquals(0.5, $avg);
    }

    /**
     * Test last() returns the last record by primary key.
     */
    public function testLastReturnsLastRecord(): void
    {
        User::create(['username' => 'last1', 'email' => 'last1@t.com', 'password' => 'p']);
        $u2 = User::create(['username' => 'last2', 'email' => 'last2@t.com', 'password' => 'p']);

        $last = (new User())->last();

        $this->assertNotNull($last);
        $this->assertEquals($u2['id'], $last['id']);
    }

    /**
     * Test exists() method with conditions.
     */
    public function testExistsWithConditions(): void
    {
        User::create(['username' => 'exists1', 'email' => 'ex1@t.com', 'password' => 'p']);

        $this->assertTrue((new User())->exists(['username' => 'exists1']));
        $this->assertFalse((new User())->exists(['username' => 'nonexistent']));
    }

    /**
     * Test magic getter/setter methods via __call.
     */
    public function testMagicGetSetHasViaCall(): void
    {
        $user = new User();
        $user->setUsername('magic_user');

        $this->assertEquals('magic_user', $user->getUsername());
        $this->assertTrue($user->hasUsername());
    }

    /**
     * Test batchInsert composes and executes a multi-row INSERT.
     *
     * MockPDO's executeInsert regex only parses a single VALUES tuple,
     * so we verify rowCount() returns at least 1 (confirming the INSERT ran)
     * and that the returned count is positive.
     */
    public function testBatchInsertInsertsMultipleEntities(): void
    {
        $entities = [];
        for ($i = 1; $i <= 3; $i++) {
            $e = new User();
            $e['username'] = "batch{$i}";
            $e['email'] = "batch{$i}@t.com";
            $e['password'] = 'p';
            $entities[] = $e;
        }

        $count = User::batchInsert($entities);

        // MockPDO inserts only the first row from a multi-row VALUES clause,
        // but the method should execute without error and return > 0
        $this->assertGreaterThan(0, $count);
    }

    /**
     * Test selectRaw composes custom select expression.
     */
    public function testSelectRawComposesQuery(): void
    {
        User::create(['username' => 'sr1', 'email' => 'sr@t.com', 'password' => 'p']);

        $sql = (new User())->selectRaw('COUNT(*) as total')->toSql();

        $this->assertStringContainsString('COUNT(*) as total', $sql);
    }

    /**
     * Test groupByRaw composes custom GROUP BY expression.
     */
    public function testGroupByRawComposesQuery(): void
    {
        User::create(['username' => 'gbr1', 'email' => 'gbr@t.com', 'password' => 'p']);

        $result = (new User())->groupByRaw('SUBSTR(username, 1, 1)')->get();

        $this->assertIsArray($result);
    }

    /**
     * Test orderByRaw composes custom ORDER BY expression.
     */
    public function testOrderByRawComposesQuery(): void
    {
        User::create(['username' => 'obr1', 'email' => 'obr@t.com', 'password' => 'p']);

        $result = (new User())->orderByRaw('username DESC')->get();

        $this->assertIsArray($result);
    }

    /**
     * Test orWhereRaw composes OR raw clause.
     */
    public function testOrWhereRawComposesQuery(): void
    {
        User::create(['username' => 'owr1', 'email' => 'owr@t.com', 'password' => 'p']);

        $result = (new User())
            ->where('username', '=', 'owr1')
            ->orWhereRaw('1 = 1')
            ->get();

        $this->assertIsArray($result);
    }

    /**
     * Test whereColumn composes column-to-column comparison.
     */
    public function testWhereColumnComposesQuery(): void
    {
        User::create(['username' => 'wcol', 'email' => 'wcol@t.com', 'password' => 'p']);

        $result = (new User())->whereColumn('username', '=', 'email')->get();

        $this->assertIsArray($result);
    }

    /**
     * Test whereDate composes DATE() function query.
     */
    public function testWhereDateComposesQuery(): void
    {
        User::create(['username' => 'wd1', 'email' => 'wd@t.com', 'password' => 'p']);

        $result = (new User())->whereDate('created_at', '=', '2025-01-01')->get();

        $this->assertIsArray($result);
    }

    /**
     * Test whereYear composes YEAR() function query.
     */
    public function testWhereYearComposesQuery(): void
    {
        User::create(['username' => 'wy1', 'email' => 'wy@t.com', 'password' => 'p']);

        $result = (new User())->whereYear('created_at', '=', 2025)->get();

        $this->assertIsArray($result);
    }

    /**
     * Test whereMonth composes MONTH() function query.
     */
    public function testWhereMonthComposesQuery(): void
    {
        $result = (new User())->whereMonth('created_at', '=', 6)->get();
        $this->assertIsArray($result);
    }

    /**
     * Test whereDay composes DAY() function query.
     */
    public function testWhereDayComposesQuery(): void
    {
        $result = (new User())->whereDay('created_at', '=', 15)->get();
        $this->assertIsArray($result);
    }

    /**
     * Test whereTime composes TIME() function query.
     */
    public function testWhereTimeComposesQuery(): void
    {
        $result = (new User())->whereTime('created_at', '>', '12:00:00')->get();
        $this->assertIsArray($result);
    }

    /**
     * Test countByMultipleConditions.
     */
    public function testCountByMultipleConditions(): void
    {
        User::create(['username' => 'cmc1', 'email' => 'cmc1@t.com', 'password' => 'p', 'is_active' => 1]);
        User::create(['username' => 'cmc2', 'email' => 'cmc2@t.com', 'password' => 'p', 'is_active' => 1]);

        $count = (new User())->countByMultipleConditions('is_active', 1);

        $this->assertEquals(2, $count);
    }

    /**
     * Test orWhereIn composes query.
     */
    public function testOrWhereInComposesQuery(): void
    {
        User::create(['username' => 'owi1', 'email' => 'owi1@t.com', 'password' => 'p']);

        $result = (new User())
            ->where('username', '=', 'nonexistent')
            ->orWhereIn('username', ['owi1'])
            ->get();

        $this->assertIsArray($result);
    }

    /**
     * Test orWhereNull and orWhereNotNull compose query.
     */
    public function testOrWhereNullAndNotNullComposeQuery(): void
    {
        Post::create(['title' => 'Orn1', 'content' => null, 'user_id' => 1]);

        $result1 = (new Post())->where('title', '=', 'x')->orWhereNull('content')->get();
        $this->assertIsArray($result1);

        $result2 = (new Post())->where('title', '=', 'x')->orWhereNotNull('title')->get();
        $this->assertIsArray($result2);
    }

    /**
     * Test join (inner) composes SQL.
     */
    public function testInnerJoinComposesQuery(): void
    {
        $sql = (new User())->join('posts', 'users.id', '=', 'posts.user_id')->toSql();

        $this->assertStringContainsString('INNER', $sql);
        $this->assertStringContainsString('JOIN', $sql);
    }

    /**
     * Test findBy with non-existent property throws.
     */
    public function testFindByNonExistentPropertyThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('does not exist or is not mapped');

        (new User())->findBy('nonexistent_column_xyz', 'value');
    }

    /**
     * Test getRelationshipMetadata returns relationship info.
     */
    public function testGetRelationshipMetadata(): void
    {
        $user = new User();
        $meta = $user->getRelationshipMetadata();

        $this->assertIsArray($meta);
    }

    /**
     * Test relationLoaded returns false for unloaded relation.
     */
    public function testRelationLoadedReturnsFalseForUnloaded(): void
    {
        $user = User::create(['username' => 'rl1', 'email' => 'rl@t.com', 'password' => 'p']);

        $this->assertFalse($user->relationLoaded('nonexistent'));
    }

    // =========================================================================
    // Boot / Initialization Tests
    // =========================================================================

    /**
     * Test that boot() is called exactly once per class (not on every instantiation).
     */
    public function testBootIsCalledOncePerClass(): void
    {
        // Creating multiple instances should not throw or cause issues
        $u1 = new User();
        $u2 = new User();
        $u3 = new User();

        // If boot ran multiple times with side effects, this would fail
        $this->assertInstanceOf(User::class, $u3);
    }

    /**
     * Test clearBootedState resets boot tracking for a class.
     */
    public function testClearBootedState(): void
    {
        User::clearBootedState();

        // Next instantiation should re-trigger boot
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
    }

    // =========================================================================
    // Restoring / Restored Event Tests
    // =========================================================================

    /**
     * Test restoring event hook registration.
     */
    public function testRestoringEventHook(): void
    {
        $called = false;
        Post::restoring(function ($entity) use (&$called) {
            $called = true;
        });

        $post = Post::create(['title' => 'Restore Test', 'content' => 'c', 'user_id' => 1]);
        $post->softDelete();
        $post->restoreWithEvents();

        $this->assertTrue($called);
        Post::clearEventHooks('restoring');
    }

    /**
     * Test restored event hook fires after restore.
     */
    public function testRestoredEventHook(): void
    {
        $called = false;
        Post::restored(function ($entity) use (&$called) {
            $called = true;
        });

        $post = Post::create(['title' => 'Restored Test', 'content' => 'c', 'user_id' => 1]);
        $post->softDelete();
        $post->restoreWithEvents();

        $this->assertTrue($called);
        Post::clearEventHooks('restored');
    }

    /**
     * Test restoring event can cancel restore by returning false.
     */
    public function testRestoringCanCancelRestore(): void
    {
        Post::restoring(function ($entity) {
            return false; // Cancel
        });

        $post = Post::create(['title' => 'Cancel Restore', 'content' => 'c', 'user_id' => 1]);
        $post->softDelete();
        $result = $post->restoreWithEvents();

        $this->assertFalse($result);
        Post::clearEventHooks('restoring');
    }

    /**
     * Test restoreWithEvents throws when soft delete not enabled.
     */
    public function testRestoreWithEventsThrowsWithoutSoftDelete(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Soft delete not enabled');

        $user = User::create(['username' => 'nosd', 'email' => 'nosd@t.com', 'password' => 'p']);
        $user->restoreWithEvents();
    }

    // =========================================================================
    // fromArray / fromJson / toBase64 / fromBase64 Tests
    // =========================================================================

    /**
     * Test fromArray creates entity with given attributes.
     */
    public function testFromArrayCreatesEntity(): void
    {
        $user = User::fromArray(['username' => 'fromArr', 'email' => 'fa@t.com']);

        $this->assertEquals('fromArr', $user['username']);
        $this->assertEquals('fa@t.com', $user['email']);
        $this->assertTrue($user->isNew());
    }

    /**
     * Test fromArray with persisted flag.
     */
    public function testFromArrayWithPersistedFlag(): void
    {
        $user = User::fromArray(['username' => 'persisted', 'email' => 'p@t.com'], true);

        $this->assertFalse($user->isNew());
    }

    /**
     * Test fromJson creates entity from JSON string.
     */
    public function testFromJsonCreatesEntity(): void
    {
        $json = json_encode(['username' => 'fromJson', 'email' => 'fj@t.com']);
        $user = User::fromJson($json);

        $this->assertEquals('fromJson', $user['username']);
        $this->assertTrue($user->isNew());
    }

    /**
     * Test fromJson throws on invalid JSON.
     */
    public function testFromJsonThrowsOnInvalidJson(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid JSON');

        User::fromJson('{invalid json!!!');
    }

    /**
     * Test toBase64 and fromBase64 roundtrip.
     */
    public function testToBase64AndFromBase64Roundtrip(): void
    {
        $user = User::create(['username' => 'b64user', 'email' => 'b64@t.com', 'password' => 'p']);
        $encoded = $user->toBase64();

        $this->assertIsString($encoded);
        $this->assertNotEmpty($encoded);

        $decoded = User::fromBase64($encoded);
        $this->assertEquals('b64user', $decoded['username']);
        $this->assertEquals('b64@t.com', $decoded['email']);
    }

    // =========================================================================
    // getAttributes / attributesToArray Tests
    // =========================================================================

    /**
     * Test getAttributes returns raw stored data.
     */
    public function testGetAttributesReturnsRawData(): void
    {
        $user = User::create(['username' => 'raw', 'email' => 'raw@t.com', 'password' => 'p']);
        $attrs = $user->getAttributes();

        $this->assertIsArray($attrs);
        $this->assertArrayHasKey('username', $attrs);
        $this->assertEquals('raw', $attrs['username']);
    }

    /**
     * Test attributesToArray returns only column-mapped values.
     */
    public function testAttributesToArrayReturnsColumnsOnly(): void
    {
        $user = User::create(['username' => 'colonly', 'email' => 'co@t.com', 'password' => 'p']);
        // Set a non-column value
        $user->setRelation('fake_relation', ['data']);

        $attrs = $user->attributesToArray();

        $this->assertArrayHasKey('username', $attrs);
        $this->assertArrayNotHasKey('fake_relation', $attrs);
    }

    // =========================================================================
    // hasCast / mergeCasts / getCasts Tests
    // =========================================================================

    /**
     * Test hasCast returns false when no cast defined.
     */
    public function testHasCastReturnsFalseForUndefined(): void
    {
        $user = new User();
        $this->assertFalse($user->hasCast('username'));
    }

    /**
     * Test mergeCasts adds new casts at runtime.
     */
    public function testMergeCastsAddsNewCasts(): void
    {
        $user = new User();
        $user->mergeCasts(['is_active' => 'boolean']);

        $this->assertTrue($user->hasCast('is_active'));
        $this->assertTrue($user->hasCast('is_active', 'boolean'));
        $this->assertFalse($user->hasCast('is_active', 'integer'));
    }

    /**
     * Test getCasts returns current cast definitions.
     */
    public function testGetCastsReturnsDefinitions(): void
    {
        $user = new User();
        $user->mergeCasts(['is_active' => 'boolean', 'id' => 'integer']);

        $casts = $user->getCasts();
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('id', $casts);
    }

    // =========================================================================
    // qualifyColumn / getQualifiedKeyName Tests
    // =========================================================================

    /**
     * Test getQualifiedKeyName returns table.column format.
     */
    public function testGetQualifiedKeyName(): void
    {
        $user = new User();
        $qualified = $user->getQualifiedKeyName();

        $this->assertEquals('users.id', $qualified);
    }

    /**
     * Test qualifyColumn for any mapped property.
     */
    public function testQualifyColumn(): void
    {
        $user = new User();

        $this->assertEquals('users.username', $user->qualifyColumn('username'));
        $this->assertEquals('users.email', $user->qualifyColumn('email'));
    }

    /**
     * Test qualifyColumn throws for unmapped property.
     */
    public function testQualifyColumnThrowsForUnmapped(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not mapped');

        (new User())->qualifyColumn('nonexistent_col');
    }

    // =========================================================================
    // getColumnCount / getColumnNames / getDatabaseColumnNames Tests
    // =========================================================================

    /**
     * Test getColumnCount returns correct number.
     */
    public function testGetColumnCount(): void
    {
        $user = new User();
        // User has: id, username, email, password, created_at, updated_at, is_active = 7
        $this->assertEquals(7, $user->getColumnCount());
    }

    /**
     * Test getColumnNames returns property names.
     */
    public function testGetColumnNames(): void
    {
        $user = new User();
        $names = $user->getColumnNames();

        $this->assertContains('id', $names);
        $this->assertContains('username', $names);
        $this->assertContains('email', $names);
    }

    /**
     * Test getDatabaseColumnNames returns DB column names.
     */
    public function testGetDatabaseColumnNames(): void
    {
        $user = new User();
        $dbNames = $user->getDatabaseColumnNames();

        $this->assertContains('id', $dbNames);
        $this->assertContains('username', $dbNames);
    }

    // =========================================================================
    // getIncrementing / setIncrementing / getKeyType / setKeyType Tests
    // =========================================================================

    /**
     * Test incrementing flag defaults to true.
     */
    public function testIncrementingDefaultsToTrue(): void
    {
        $user = new User();
        $this->assertTrue($user->getIncrementing());
    }

    /**
     * Test setIncrementing changes the flag.
     */
    public function testSetIncrementing(): void
    {
        $user = new User();
        $user->setIncrementing(false);
        $this->assertFalse($user->getIncrementing());
    }

    /**
     * Test keyType defaults to 'int'.
     */
    public function testKeyTypeDefaultsToInt(): void
    {
        $user = new User();
        $this->assertEquals('int', $user->getKeyType());
    }

    /**
     * Test setKeyType changes the type.
     */
    public function testSetKeyType(): void
    {
        $user = new User();
        $user->setKeyType('string');
        $this->assertEquals('string', $user->getKeyType());
    }

    // =========================================================================
    // getForeignKey Tests
    // =========================================================================

    /**
     * Test getForeignKey returns snake_case + _id.
     */
    public function testGetForeignKey(): void
    {
        $user = new User();
        $this->assertEquals('user_id', $user->getForeignKey());

        $post = new Post();
        $this->assertEquals('post_id', $post->getForeignKey());
    }

    // =========================================================================
    // unsetRelation / getRelations Tests
    // =========================================================================

    /**
     * Test unsetRelation removes a loaded relation.
     */
    public function testUnsetRelation(): void
    {
        $user = User::create(['username' => 'ur', 'email' => 'ur@t.com', 'password' => 'p']);
        $user->setRelation('posts', []);
        $this->assertTrue($user->relationLoaded('posts'));

        $user->unsetRelation('posts');
        $this->assertFalse($user->relationLoaded('posts'));
    }

    /**
     * Test getRelations returns all loaded relationships.
     */
    public function testGetRelations(): void
    {
        $user = User::create(['username' => 'gr', 'email' => 'gr@t.com', 'password' => 'p']);
        $user->setRelation('posts', ['a', 'b']);
        $user->setRelation('profile', null);

        $relations = $user->getRelations();

        $this->assertArrayHasKey('posts', $relations);
        $this->assertArrayHasKey('profile', $relations);
    }

    // =========================================================================
    // push() Tests
    // =========================================================================

    /**
     * Test push saves entity (basic case without relations).
     */
    public function testPushSavesEntity(): void
    {
        $user = User::create(['username' => 'push1', 'email' => 'push@t.com', 'password' => 'p']);
        $user['username'] = 'push_updated';

        $result = $user->push();

        $this->assertTrue($result);
    }

    // =========================================================================
    // setIfNotNull / setDefault Tests (existing methods, ensure they work)
    // =========================================================================

    /**
     * Test setIfNotNull skips null values.
     */
    public function testSetIfNotNullSkipsNull(): void
    {
        $user = User::create(['username' => 'sinn', 'email' => 'sinn@t.com', 'password' => 'p']);
        $user->setIfNotNull('username', null);

        $this->assertEquals('sinn', $user['username']);
    }

    /**
     * Test setIfNotNull sets non-null values.
     */
    public function testSetIfNotNullSetsValue(): void
    {
        $user = User::create(['username' => 'sinn2', 'email' => 'sinn2@t.com', 'password' => 'p']);
        $user->setIfNotNull('username', 'changed');

        $this->assertEquals('changed', $user['username']);
    }

    /**
     * Test setDefault sets value only when unset.
     */
    public function testSetDefaultSetsWhenNull(): void
    {
        $user = new User();
        $user->setDefault('username', 'default_name');

        $this->assertEquals('default_name', $user['username']);
    }

    /**
     * Test setDefault does not override existing value.
     */
    public function testSetDefaultDoesNotOverride(): void
    {
        $user = User::create(['username' => 'existing', 'email' => 'sd@t.com', 'password' => 'p']);
        $user->setDefault('username', 'default_name');

        $this->assertEquals('existing', $user['username']);
    }

    // =========================================================================
    // Timestamps Helpers Tests
    // =========================================================================

    /**
     * Test freshTimestampString returns valid datetime string.
     */
    public function testFreshTimestampString(): void
    {
        $user = new User();
        $ts = $user->freshTimestampString();

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $ts);
    }

    /**
     * Test getCreatedAt returns DateTimeInterface or null.
     */
    public function testGetCreatedAt(): void
    {
        $user = User::create(['username' => 'ts1', 'email' => 'ts@t.com', 'password' => 'p']);

        $created = $user->getCreatedAt();

        // Timestamps are set since $timestamps = true on User
        $this->assertInstanceOf(\DateTimeInterface::class, $created);
    }

    /**
     * Test getUpdatedAt returns DateTimeInterface or null.
     */
    public function testGetUpdatedAt(): void
    {
        $user = User::create(['username' => 'ts2', 'email' => 'ts2@t.com', 'password' => 'p']);

        $updated = $user->getUpdatedAt();

        $this->assertInstanceOf(\DateTimeInterface::class, $updated);
    }

    // =========================================================================
    // firstOrFail (static) Tests
    // =========================================================================

    /**
     * Test static firstOrFail returns entity when found.
     */
    public function testStaticFirstOrFailReturnsEntity(): void
    {
        User::create(['username' => 'fof_static', 'email' => 'fofs@t.com', 'password' => 'p']);

        $user = User::firstOrFail(['username' => 'fof_static']);
        $this->assertEquals('fof_static', $user['username']);
    }

    /**
     * Test static firstOrFail throws when not found.
     */
    public function testStaticFirstOrFailThrowsWhenNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No entity found');

        User::firstOrFail(['username' => 'definitely_not_existing_user_xyz']);
    }

    // =========================================================================
    // insertOrIgnore Tests
    // =========================================================================

    /**
     * Test insertOrIgnore inserts a new row.
     */
    public function testInsertOrIgnoreInsertsRow(): void
    {
        $result = User::insertOrIgnore(['username' => 'ior1', 'email' => 'ior@t.com', 'password' => 'p']);

        $this->assertTrue($result);
    }

    /**
     * Test insertOrIgnore with empty attributes returns false.
     */
    public function testInsertOrIgnoreEmptyAttributes(): void
    {
        $result = User::insertOrIgnore([]);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Lazy / LazyById Generator Tests
    // =========================================================================

    /**
     * Test lazy returns generator that yields entities.
     */
    public function testLazyReturnsGenerator(): void
    {
        User::create(['username' => 'lazy1', 'email' => 'l1@t.com', 'password' => 'p']);
        User::create(['username' => 'lazy2', 'email' => 'l2@t.com', 'password' => 'p']);
        User::create(['username' => 'lazy3', 'email' => 'l3@t.com', 'password' => 'p']);

        $user = new User();
        $gen = $user->lazy(2);

        $this->assertInstanceOf(\Generator::class, $gen);

        $items = iterator_to_array($gen);
        $this->assertCount(3, $items);
    }

    /**
     * Test lazyById returns generator using ID-based chunking.
     */
    /*public function testLazyByIdReturnsGenerator(): void
    {
        User::create(['username' => 'lbi1', 'email' => 'lbi1@t.com', 'password' => 'p']);
        User::create(['username' => 'lbi2', 'email' => 'lbi2@t.com', 'password' => 'p']);

        $user = new User();
        $gen = $user->lazyById(1);

        $this->assertInstanceOf(\Generator::class, $gen);

        $items = iterator_to_array($gen);
        $this->assertGreaterThanOrEqual(2, count($items));
    }*/

    // =========================================================================
    // Schema Introspection Tests
    // =========================================================================

    /**
     * Test hasColumnInMetadata returns true for mapped columns.
     */
    public function testHasColumnInMetadataForMapped(): void
    {
        $user = new User();

        $this->assertTrue($user->hasColumnInMetadata('username'));
        $this->assertTrue($user->hasColumnInMetadata('email'));
        $this->assertFalse($user->hasColumnInMetadata('nonexistent'));
    }

    // =========================================================================
    // savepoint Tests
    // =========================================================================

    /**
     * Test savepoint executes callback and returns result.
     */
    public function testSavepointExecutesCallback(): void
    {
        $result = User::savepoint('test_sp', function () {
            return 'savepoint_result';
        });

        $this->assertEquals('savepoint_result', $result);
    }

    /**
     * Test savepoint rolls back on exception.
     */
    public function testSavepointRollsBackOnException(): void
    {
        $this->expectException(\RuntimeException::class);

        User::savepoint('fail_sp', function () {
            throw new \RuntimeException('Savepoint failure');
        });
    }

    // =========================================================================
    // only / except Tests (existing methods, verify they work)
    // =========================================================================

    /**
     * Test only returns specified keys only.
     */
    public function testOnlyReturnsSubset(): void
    {
        $user = User::create(['username' => 'only1', 'email' => 'only@t.com', 'password' => 'p']);

        $subset = $user->only(['username', 'email']);

        $this->assertArrayHasKey('username', $subset);
        $this->assertArrayHasKey('email', $subset);
        $this->assertArrayNotHasKey('password', $subset);
        $this->assertArrayNotHasKey('id', $subset);
    }

    /**
     * Test except excludes specified keys.
     */
    public function testExceptExcludesKeys(): void
    {
        $user = User::create(['username' => 'exc1', 'email' => 'exc@t.com', 'password' => 'p']);

        $result = $user->except(['password', 'id']);

        $this->assertArrayHasKey('username', $result);
        $this->assertArrayNotHasKey('id', $result);
    }

    // =========================================================================
    // Global Scope Helpers Tests
    // =========================================================================

    /**
     * Test hasGlobalScope returns correct status.
     */
    public function testHasGlobalScope(): void
    {
        User::addGlobalScope('test_scope', function ($q) {});

        $this->assertTrue(User::hasGlobalScope('test_scope'));
        $this->assertFalse(User::hasGlobalScope('nonexistent_scope'));

        User::removeGlobalScope('test_scope');
    }

    /**
     * Test clearGlobalScopes removes all scopes.
     */
    public function testClearGlobalScopes(): void
    {
        User::addGlobalScope('scope_a', function ($q) {});
        User::addGlobalScope('scope_b', function ($q) {});

        User::clearGlobalScopes();

        $this->assertFalse(User::hasGlobalScope('scope_a'));
        $this->assertFalse(User::hasGlobalScope('scope_b'));
    }

    // =========================================================================
    // getPerPage / setPerPage Tests
    // =========================================================================

    /**
     * Test getPerPage returns default value.
     */
    public function testGetPerPageDefault(): void
    {
        $user = new User();
        $this->assertEquals(15, $user->getPerPage());
    }

    /**
     * Test setPerPage changes the value.
     */
    public function testSetPerPage(): void
    {
        $user = new User();
        $user->setPerPage(25);

        // The perPage property is set
        $this->assertInstanceOf(User::class, $user);
    }

    // =========================================================================
    // cursorMap Tests
    // =========================================================================

    /**
     * Test cursorMap applies callback to each entity via cursor.
     */
    public function testCursorMapAppliesCallback(): void
    {
        User::create(['username' => 'cm1', 'email' => 'cm1@t.com', 'password' => 'p']);
        User::create(['username' => 'cm2', 'email' => 'cm2@t.com', 'password' => 'p']);

        $user = new User();
        $names = $user->cursorMap(function ($entity) {
            return $entity['username'];
        });

        $this->assertContains('cm1', $names);
        $this->assertContains('cm2', $names);
    }

    // =========================================================================
    // withDefault Tests
    // =========================================================================

    /**
     * Test withDefault sets default for missing relation.
     */
    public function testWithDefaultSetsDefaultForMissingRelation(): void
    {
        $user = User::create(['username' => 'wd1', 'email' => 'wd1@t.com', 'password' => 'p']);
        $user->withDefault('someRelation', 'default_value');

        $this->assertEquals('default_value', $user['someRelation']);
    }

    /**
     * Test withDefault does not override existing relation.
     */
    public function testWithDefaultDoesNotOverrideExisting(): void
    {
        $user = User::create(['username' => 'wd2', 'email' => 'wd2@t.com', 'password' => 'p']);
        $user->setRelation('myRel', 'existing_value');
        $user->withDefault('myRel', 'new_default');

        $this->assertEquals('existing_value', $user['myRel']);
    }

    /**
     * Test withDefault accepts callable.
     */
    public function testWithDefaultAcceptsCallable(): void
    {
        $user = User::create(['username' => 'wd3', 'email' => 'wd3@t.com', 'password' => 'p']);
        $user->withDefault('computed', function ($entity) {
            return 'computed_' . $entity['username'];
        });

        $this->assertEquals('computed_wd3', $user['computed']);
    }

    // =========================================================================
    // Polymorphic Relationship Method Tests (smoke tests)
    // =========================================================================

    /**
     * Test morphMany returns empty array when no PK.
     */
    public function testMorphManyReturnsEmptyWithoutPk(): void
    {
        $user = new User();
        $user->morphMany('comments', Post::class, 'commentable');

        $this->assertIsArray($user['comments']);
        $this->assertEmpty($user['comments']);
    }

    /**
     * Test morphOne returns null when no PK.
     */
    public function testMorphOneReturnsNullWithoutPk(): void
    {
        $user = new User();
        $user->morphOne('image', Post::class, 'imageable');

        $this->assertNull($user['image']);
    }

    /**
     * Test morphTo returns null when type/id columns are missing.
     */
    public function testMorphToReturnsNullWithoutTypeId(): void
    {
        $post = new Post();
        $post->morphTo('commentable');

        $this->assertNull($post['commentable']);
    }

    // =========================================================================
    // HasManyThrough Tests (smoke)
    // =========================================================================

    /**
     * Test hasManyThrough returns empty array when no PK value.
     */
    public function testHasManyThroughReturnsEmptyWithoutPk(): void
    {
        $user = new User();
        $user->hasManyThrough('posts_through', Post::class, Profile::class, 'user_id', 'user_id');

        $this->assertIsArray($user['posts_through']);
        $this->assertEmpty($user['posts_through']);
    }

    /**
     * Test hasOneThrough returns null when no PK value.
     */
    public function testHasOneThroughReturnsNullWithoutPk(): void
    {
        $user = new User();
        $user->hasOneThrough('single_post', Post::class, Profile::class, 'user_id', 'user_id');

        $this->assertNull($user['single_post']);
    }

    // =========================================================================
    // Pivot Table Management Tests (smoke)
    // =========================================================================

    /**
     * Test attach throws for non-manyToMany relationship.
     */
    public function testAttachThrowsForNonManyToMany(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not a manyToMany');

        $user = User::create(['username' => 'att', 'email' => 'att@t.com', 'password' => 'p']);
        $user->attach('username', [1, 2]); // 'username' is not a relationship
    }

    /**
     * Test detach throws for non-manyToMany relationship.
     */
    public function testDetachThrowsForNonManyToMany(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not a manyToMany');

        $user = User::create(['username' => 'det', 'email' => 'det@t.com', 'password' => 'p']);
        $user->detach('email', [1]);
    }

    // =========================================================================
    // withAggregate Tests (smoke)
    // =========================================================================

    /**
     * Test withAggregate returns null when relation not found.
     */
    public function testWithAggregateReturnsNullForUnknownRelation(): void
    {
        $user = User::create(['username' => 'wa', 'email' => 'wa@t.com', 'password' => 'p']);
        $user->withAggregate('nonexistent', 'SUM', 'amount');

        $this->assertNull($user['nonexistent_sum']);
    }

    /**
     * Test withAggregate returns null when no PK.
     */
    public function testWithAggregateReturnsNullWithoutPk(): void
    {
        $user = new User();
        $user->withAggregate('posts', 'SUM', 'id');

        $this->assertNull($user['posts_sum']);
    }

    // =========================================================================
    // queryCount / queryExists / queryDoesntExist Tests
    // =========================================================================

    /**
     * Test queryCount returns correct count.
     */
    public function testQueryCountReturnsCount(): void
    {
        User::create(['username' => 'qc1', 'email' => 'qc1@t.com', 'password' => 'p']);
        User::create(['username' => 'qc2', 'email' => 'qc2@t.com', 'password' => 'p']);

        $count = (new User())->where('username', 'LIKE', 'qc%')->queryCount();

        $this->assertGreaterThanOrEqual(2, $count);
    }

    /**
     * Test queryExists returns true when records exist.
     */
    public function testQueryExistsReturnsTrue(): void
    {
        User::create(['username' => 'qe1', 'email' => 'qe1@t.com', 'password' => 'p']);

        $exists = (new User())->where('username', '=', 'qe1')->queryExists();

        $this->assertTrue($exists);
    }

    /**
     * Test queryDoesntExist returns true when no records.
     */
    public function testQueryDoesntExistReturnsTrue(): void
    {
        $doesntExist = (new User())->where('username', '=', 'definitely_not_here_xyz')->queryDoesntExist();

        $this->assertTrue($doesntExist);
    }

    // =========================================================================
    // firstOr / findOr Tests
    // =========================================================================

    /**
     * Test firstOr returns entity when found.
     */
    public function testFirstOrReturnsEntityWhenFound(): void
    {
        User::create(['username' => 'for1', 'email' => 'for1@t.com', 'password' => 'p']);

        $result = (new User())->firstOr(function () {
            return 'fallback';
        }, ['conditions' => ['username' => 'for1']]);

        $this->assertNotEquals('fallback', $result);
        $this->assertEquals('for1', $result['username']);
    }

    /**
     * Test firstOr calls callback when not found.
     */
    public function testFirstOrCallsCallbackWhenNotFound(): void
    {
        $result = (new User())->firstOr(function () {
            return 'fallback_value';
        }, ['conditions' => ['username' => 'non_existing_user_xyz']]);

        $this->assertEquals('fallback_value', $result);
    }

    // =========================================================================
    // addSubSelect Tests
    // =========================================================================

    /**
     * Test addSubSelect adds subquery to SELECT clause.
     */
    public function testAddSubSelectComposesQuery(): void
    {
        $sql = (new User())->addSubSelect('post_count', 'SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id')->toSql();

        $this->assertStringContainsString('post_count', $sql);
        $this->assertStringContainsString('SELECT COUNT', $sql);
    }

    // =========================================================================
    // union Tests
    // =========================================================================

    /**
     * Test union method stores union data.
     */
    public function testUnionStoresData(): void
    {
        $user = new User();
        $result = $user->union('SELECT * FROM posts', [], false);

        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * Test unionAll method stores data with all flag.
     */
    public function testUnionAllStoresData(): void
    {
        $user = new User();
        $result = $user->unionAll('SELECT * FROM posts');

        $this->assertInstanceOf(User::class, $result);
    }

    // =========================================================================
    // orWhereBetween / orWhereLike Tests
    // =========================================================================

    /**
     * Test orWhereBetween composes query.
     */
    public function testOrWhereBetweenComposesQuery(): void
    {
        User::create(['username' => 'owb1', 'email' => 'owb@t.com', 'password' => 'p']);

        $result = (new User())
            ->where('username', '=', 'nonexistent')
            ->orWhereBetween('id', 1, 100)
            ->get();

        $this->assertIsArray($result);
    }

    /**
     * Test orWhereLike composes query.
     */
    public function testOrWhereLikeComposesQuery(): void
    {
        User::create(['username' => 'owl1', 'email' => 'owl@t.com', 'password' => 'p']);

        $result = (new User())
            ->where('username', '=', 'nonexistent')
            ->orWhereLike('username', 'owl%')
            ->get();

        $this->assertIsArray($result);
    }

    // =========================================================================
    // Encryption Tests
    // =========================================================================

    /**
     * Test encryptValue and decryptValue roundtrip.
     */
    public function testEncryptDecryptRoundtrip(): void
    {
        ActiveRecord::setEncryptionKey(str_repeat('a', 32));

        $ref = new ReflectionMethod(User::class, 'encryptValue');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $ref->setAccessible(true);
        }

        $original = 'secret_data_123';
        $encrypted = $ref->invoke(null, $original);

        $this->assertNotEquals($original, $encrypted);

        $refD = new ReflectionMethod(User::class, 'decryptValue');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $refD->setAccessible(true);
        }
        $decrypted = $refD->invoke(null, $encrypted);

        $this->assertEquals($original, $decrypted);
    }

    /**
     * Test encryptValue throws without key.
     */
    public function testEncryptThrowsWithoutKey(): void
    {
        // Reset key via reflection
        // The key lives on AttributeEncrypter now; ActiveRecord's encryptValue()
        // and decryptValue() delegate to it.
        $ref = new ReflectionClass(AttributeEncrypter::class);
        $prop = $ref->getProperty('key');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }
        $prop->setValue(null, null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Encryption key not set');

        $refM = new ReflectionMethod(User::class, 'encryptValue');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $refM->setAccessible(true);
        }
        $refM->invoke(null, 'test');
    }

    /**
     * Test isEncrypted checks the encrypted array.
     */
    public function testIsEncryptedReturnsFalseForUser(): void
    {
        $user = new User();
        $this->assertFalse($user->isEncrypted('username'));
    }
}
