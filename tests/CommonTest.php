<?php

namespace Tests;

use Faker\Generator;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inpin\LaraAlert\Alert;
use Inpin\LaraAlert\Alertable;

class CommonTest extends LaraAlertTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    public function setUp()
    {
        parent::setUp();

        Eloquent::unguard();

        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__.'/../migrations'),
        ]);

        $this->loadLaravelMigrations(['--database' => 'testbench']);

        $this->faker = resolve(Generator::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        Schema::create('books', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function tearDown()
    {
        Schema::drop('books');
    }

    /**
     * Create a random user with fake information.
     *
     * @return User|#$this|Eloquent
     */
    public function createRandomUser()
    {
        return User::query()->create([
            'email'    => $this->faker->unique()->email,
            'name'     => $this->faker->name,
            'password' => Hash::make($this->faker->password),
        ]);
    }

    /**
     * Create a random stub with fake information.
     *
     * @return Stub|$this|Eloquent
     */
    public function createRandomStub()
    {
        return Stub::query()->create([
            'name' => $this->faker->word,
        ]);
    }

    public function testBasicAlert()
    {
        /** @var User $user */
        $user = $this->createRandomUser();
        $this->actingAs($user);
        /* @var Stub $stub */
        $stub = $this->createRandomStub();

        $this->assertFalse($stub->isAlertedBy());
        $this->assertFalse($stub->isAlerted());
        $this->assertFalse($stub->isAlerted);
        $this->assertEquals(0, $stub->alertsCount());
        $this->assertEquals(0, $stub->alertsCount);

        $alert = $stub->createAlert();

        $this->assertNotNull($alert);
        $this->assertTrue($alert->isNew());
        $this->assertTrue($alert->isNew);
        $this->assertFalse($alert->isSeen());
        $this->assertFalse($alert->isSeen);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $user->id,
            'type'           => 'alert',
            'description'    => null,
            'seen_at'        => null,
        ]);

        $this->assertEquals(1, $stub->alertsCount());
        $this->assertEquals(1, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy());
        $this->assertTrue($stub->isAlerted());
        $this->assertTrue($stub->isAlerted);
    }

    public function testAlertWithType()
    {
        /** @var User $user */
        $user = $this->createRandomUser();
        $this->actingAs($user);
        /* @var Stub $stub */
        $stub = $this->createRandomStub();

        $this->assertFalse($stub->isAlertedBy());
        $this->assertFalse($stub->isAlerted());
        $this->assertFalse($stub->isAlerted);
        $this->assertEquals(0, $stub->alertsCount());
        $this->assertEquals(0, $stub->alertsCount);

        $alert = $stub->createAlert('some-type');

        $this->assertNotNull($alert);
        $this->assertTrue($alert->isNew());
        $this->assertTrue($alert->isNew);
        $this->assertFalse($alert->isSeen());
        $this->assertFalse($alert->isSeen);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $user->id,
            'type'           => 'some-type',
            'description'    => null,
            'seen_at'        => null,
        ]);

        $this->assertEquals(1, $stub->alertsCount());
        $this->assertEquals(1, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy($user));
        $this->assertTrue($stub->isAlertedBy());
        $this->assertTrue($stub->isAlerted());
        $this->assertTrue($stub->isAlerted);
    }

    public function testAlertWithUser()
    {
        /** @var User $user */
        $user = $this->createRandomUser();
        $this->actingAs($user);
        /* @var Stub $stub */
        $stub = $this->createRandomStub();
        /** @var User $alerter */
        $alerter = $this->createRandomUser();

        $this->assertFalse($stub->isAlertedBy($alerter));
        $this->assertFalse($stub->isAlerted());
        $this->assertFalse($stub->isAlerted);
        $this->assertEquals(0, $stub->alertsCount());
        $this->assertEquals(0, $stub->alertsCount);

        $alert = $stub->createAlert('alert', $alerter);

        $this->assertNotNull($alert);
        $this->assertTrue($alert->isNew());
        $this->assertTrue($alert->isNew);
        $this->assertFalse($alert->isSeen());
        $this->assertFalse($alert->isSeen);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $alerter->id,
            'type'           => 'alert',
            'description'    => null,
            'seen_at'        => null,
        ]);

        $this->assertEquals(1, $stub->alertsCount());
        $this->assertEquals(1, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy($alerter));
        $this->assertFalse($stub->isAlertedBy());
        $this->assertTrue($stub->isAlerted());
        $this->assertTrue($stub->isAlerted);
    }

    public function testAlertWithUserAndType()
    {
        /** @var User $user */
        $user = $this->createRandomUser();
        $this->actingAs($user);
        /* @var Stub $stub */
        $stub = $this->createRandomStub();
        /** @var User $alerter */
        $alerter = $this->createRandomUser();

        $this->assertFalse($stub->isAlertedBy($alerter));
        $this->assertFalse($stub->isAlerted());
        $this->assertFalse($stub->isAlerted);
        $this->assertEquals(0, $stub->alertsCount());
        $this->assertEquals(0, $stub->alertsCount);

        $alert = $stub->createAlert('some-type', $alerter);

        $this->assertNotNull($alert);
        $this->assertTrue($alert->isNew());
        $this->assertTrue($alert->isNew);
        $this->assertFalse($alert->isSeen());
        $this->assertFalse($alert->isSeen);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $alerter->id,
            'type'           => 'some-type',
            'description'    => null,
            'seen_at'        => null,
        ]);

        $this->assertEquals(1, $stub->alertsCount());
        $this->assertEquals(1, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy($alerter));
        $this->assertFalse($stub->isAlertedBy());
        $this->assertTrue($stub->isAlerted());
        $this->assertTrue($stub->isAlerted);
    }

    public function testAlertWithUserAndTypeAndDescription()
    {
        /** @var User $user */
        $user = $this->createRandomUser();
        $this->actingAs($user);
        /* @var Stub $stub */
        $stub = $this->createRandomStub();
        /** @var User $alerter */
        $alerter = $this->createRandomUser();

        $this->assertFalse($stub->isAlertedBy($alerter));
        $this->assertFalse($stub->isAlerted());
        $this->assertFalse($stub->isAlerted);
        $this->assertEquals(0, $stub->alertsCount());
        $this->assertEquals(0, $stub->alertsCount);

        $description = $this->faker->text;

        $alert = $stub->createAlert('some-type', $alerter, $description);

        $this->assertNotNull($alert);
        $this->assertTrue($alert->isNew());
        $this->assertTrue($alert->isNew);
        $this->assertFalse($alert->isSeen());
        $this->assertFalse($alert->isSeen);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $alerter->id,
            'type'           => 'some-type',
            'description'    => $description,
            'seen_at'        => null,
        ]);

        $this->assertEquals(1, $stub->alertsCount());
        $this->assertEquals(1, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy($alerter));
        $this->assertFalse($stub->isAlertedBy());
        $this->assertTrue($stub->isAlerted());
        $this->assertTrue($stub->isAlerted);
    }

    public function testMultipleAlerts()
    {
        $stub = $this->createRandomStub();

        $data = [];

        for ($i = 0; $i < 10; $i++) {
            $data[] = [
                'alerter'     => $this->createRandomUser(),
                'type'        => $this->faker->word,
                'description' => $this->faker->text,
            ];
        }

        $this->assertFalse($stub->isAlerted());
        $this->assertFalse($stub->isAlerted);
        foreach ($data as &$datum) {
            $this->assertFalse($stub->isAlertedBy($datum['alerter']));

            /** @var Alert $alert */
            $alert = $stub->createAlert(
                $datum['type'],
                $datum['alerter'],
                $datum['description']
            );

            $this->assertNotNull($alert);
            $this->assertTrue($alert->isNew());
            $this->assertTrue($alert->isNew);
            $this->assertFalse($alert->isSeen());
            $this->assertFalse($alert->isSeen);
        }

        foreach ($data as $datum) {
            $this->assertDatabaseHas('laraalert_alerts', [
                'alertable_type' => $stub->getMorphClass(),
                'alertable_id'   => $stub->id,
                'user_id'        => $datum['alerter']->id,
                'description'    => $datum['description'],
                'type'           => $datum['type'],
                'seen_at'        => null,
            ]);

            $this->assertTrue($stub->isAlertedBy($datum['alerter']));
            $this->assertFalse($stub->isAlertedBy());
        }

        $this->asserttrue($stub->isAlerted());
        $this->asserttrue($stub->isAlerted);
        $this->assertEquals(count($data), $stub->alertsCount());
        $this->assertEquals(count($data), $stub->alertsCount);
    }

    public function testRecreateAlert()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();
        $this->actingAs($user);

        $type = $this->faker->word;
        $description = $this->faker->text;

        /** @var Alert $alert */
        $alert = $stub->alerts()->save(new Alert([
            'user_id'     => $user->id,
            'type'        => $type,
            'description' => $description,
        ]));

        $this->assertEquals(1, $stub->alertsCount());
        $this->assertEquals(1, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy());
        $this->assertTrue($stub->isAlerted());
        $this->assertTrue($stub->isAlerted);

        $newType = $this->faker->word;
        $newDescription = $this->faker->text;

        $newAlert = $stub->createAlert($newType, null, $newDescription);

        $this->assertDatabaseHas('laraalert_alerts', ['id' => $alert->id]);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $user->id,
            'type'           => $type,
            'description'    => $description,
            'seen_at'        => null,
        ]);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $user->id,
            'type'           => $newType,
            'description'    => $newDescription,
            'seen_at'        => null,
        ]);

        $this->assertEquals(2, $stub->alertsCount());
        $this->assertEquals(2, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy());
        $this->assertTrue($stub->isAlerted());
        $this->assertTrue($stub->isAlerted);
    }

    public function testRecreateAlertWithUser()
    {
        $stub = $this->createRandomStub();
        $this->actingAs($this->createRandomUser());

        $alerter = $this->createRandomUser();
        $type = $this->faker->word;
        $description = $this->faker->text;

        /** @var Alert $alert */
        $alert = $stub->alerts()->save(new Alert([
            'user_id'     => $alerter->id,
            'type'        => $type,
            'description' => $description,
        ]));

        $this->assertEquals(1, $stub->alertsCount());
        $this->assertEquals(1, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy($alerter));

        $newType = $this->faker->word;
        $newDescription = $this->faker->text;

        $newAlert = $stub->createAlert($newType, $alerter, $newDescription);

        $this->assertTrue($newAlert->isNew());
        $this->assertTrue($newAlert->isNew);
        $this->assertFalse($newAlert->isSeen());
        $this->assertFalse($newAlert->isSeen);

        $this->assertDatabaseHas('laraalert_alerts', ['id' => $alert->id]);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $alerter->id,
            'type'           => $type,
            'description'    => $description,
            'seen_at'        => null,
        ]);

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $alerter->id,
            'type'           => $newType,
            'description'    => $newDescription,
            'seen_at'        => null,
        ]);

        $this->assertEquals(2, $stub->alertsCount());
        $this->assertEquals(2, $stub->alertsCount);
        $this->assertTrue($stub->isAlertedBy($alerter));
        $this->assertFalse($stub->isAlertedBy());
        $this->assertTrue($stub->isAlerted());
        $this->assertTrue($stub->isAlerted);
    }

    public function testSeenAlert()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();
        $this->actingAs($user);

        /** @var Alert $alert */
        $alert = $stub->alerts()->save(new Alert(['user_id' => $user->id]));

        $this->assertTrue($alert->isNew());
        $this->assertFalse($alert->isSeen());

        $this->assertTrue($alert->seen());

        $this->assertDatabaseHas('laraalert_alerts', [
            'alertable_type' => $stub->getMorphClass(),
            'alertable_id'   => $stub->id,
            'user_id'        => $user->id,
        ]);

        $this->assertFalse($alert->isNew());
        $this->assertTrue($alert->isSeen());
    }

    public function testUserMethodOfAlertModel()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $alert */
        $alert = $stub->alerts()->save(new Alert(['user_id' => $user->id]));

        $this->assertEquals($user->id, $alert->user->id);
    }

    public function testAlertableMethodOfAlertModel()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $alert */
        $alert = $stub->alerts()->save(new Alert(['user_id' => $user->id]));

        $this->assertEquals($stub->id, $alert->alertable->id);
    }

    public function testDeleteModel()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $alert */
        $alert = $stub->alerts()->save(new Alert(['user_id' => $user->id]));

        $stub->delete();

        $this->assertDatabaseMissing('laraalert_alerts', []);
    }
}

class Stub extends Eloquent
{
    use Alertable;

    protected $morphClass = 'Stub';

    protected $connection = 'testbench';

    public $table = 'books';
}
