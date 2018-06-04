<?php

namespace Tests;

use Faker\Generator;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inpin\LaraReport\Alert;
use Inpin\LaraReport\Reportable;
use Inpin\LaraReport\ReportItem;

class CommonTest extends TestCase
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

        for ($i = 0; $i < 10; $i++) {
            ReportItem::query()->create([
                'type'  => $this->faker->randomElement(['book', 'something-else']),
                'title' => $this->faker->text(),
            ]);
        }
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

    public function testBasicReport()
    {
        /** @var User $user */
        $user = $this->createRandomUser();
        $this->actingAs($user);
        /* @var Stub $stub */
        $stub = $this->createRandomStub();

        $reportItems = ReportItem::query()->inRandomOrder()->take(3)->get();
        $userMessage = $this->faker->text;
        $this->assertFalse($stub->isReported());
        $this->assertFalse($stub->isReported);

        $report = $stub->createReport($reportItems->pluck('id')->toArray(), $userMessage);

        $this->assertNotNull($report);

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'user_message'    => $userMessage,
            'admin_id'        => null,
            'admin_message'   => null,
            'resolved_at'     => null,
        ]);

        /** @var ReportItem $reportItem */
        foreach ($reportItems as $reportItem) {
            $this->assertDatabaseHas('larareport_rel_report_report_item', [
                'report_id'      => $report->id,
                'report_item_id' => $reportItem->id,
            ]);
        }
        $this->assertEquals(1, $stub->reportsCount());
        $this->assertEquals(1, $stub->reportsCount);
        $this->assertTrue($stub->isReported());
        $this->assertTrue($stub->isReported);
    }

    public function testReportWithUser()
    {
        /** @var User $user */
        $user = $this->createRandomUser();
        $this->actingAs($user);
        /* @var Stub $stub */
        $stub = $this->createRandomStub();

        $reporter = $this->createRandomUser();

        $reportItems = ReportItem::query()->inRandomOrder()->take(3)->get();
        $userMessage = $this->faker->text;

        $this->assertFalse($stub->isReported($reporter));

        $report = $stub->createReport($reportItems->pluck('id')->toArray(), $userMessage, $reporter);

        $this->assertNotNull($report);

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $reporter->id,
            'user_message'    => $userMessage,
            'admin_id'        => null,
            'admin_message'   => null,
            'resolved_at'     => null,
        ]);

        /** @var ReportItem $reportItem */
        foreach ($reportItems as $reportItem) {
            $this->assertDatabaseHas('larareport_rel_report_report_item', [
                'report_id'      => $report->id,
                'report_item_id' => $reportItem->id,
            ]);
        }
        $this->assertEquals(1, $stub->reportsCount());
        $this->assertEquals(1, $stub->reportsCount);
        $this->assertTrue($stub->isReported($reporter));
        $this->assertFalse($stub->isReported());
        $this->assertFalse($stub->isReported);
    }

    public function testMultipleReports()
    {
        $stub = $this->createRandomStub();

        $data = [];

        for ($i = 0; $i < 10; $i++) {
            $data[] = [
                'reporter'    => $this->createRandomUser(),
                'reportItems' => ReportItem::query()->inRandomOrder()->take(3)->get(),
                'userMessage' => $this->faker->text,
            ];
        }

        foreach ($data as &$datum) {
            $this->assertFalse($stub->isReported($datum['reporter']));

            $datum['report'] = $stub->createReport(
                $datum['reportItems']->pluck('id')->toArray(),
                $datum['userMessage'],
                $datum['reporter']
            );
        }

        foreach ($data as $datum) {
            $this->assertDatabaseHas('larareport_reports', [
                'reportable_type' => $stub->getMorphClass(),
                'reportable_id'   => $stub->id,
                'user_id'         => $datum['reporter']->id,
                'user_message'    => $datum['userMessage'],
                'admin_id'        => null,
                'admin_message'   => null,
                'resolved_at'     => null,
            ]);

            /** @var ReportItem $reportItem */
            foreach ($datum['reportItems'] as $reportItem) {
                $this->assertDatabaseHas('larareport_rel_report_report_item', [
                    'report_id'      => $datum['report']->id,
                    'report_item_id' => $reportItem->id,
                ]);
            }

            $this->assertTrue($stub->isReported($datum['reporter']));
            $this->assertFalse($stub->isReported());
            $this->assertFalse($stub->isReported);
        }

        $this->assertEquals(count($data), $stub->reportsCount());
        $this->assertEquals(count($data), $stub->reportsCount);
    }

    public function testRecreateReport()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();
        $this->actingAs($user);

        $userMessage = $this->faker->text;
        $reportItems = ReportItem::query()->inRandomOrder()->take(3)->get();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $userMessage,
        ]));
        $report->reportItems()->attach($reportItems->pluck('id')->toArray());

        $this->assertEquals(1, $stub->reportsCount());
        $this->assertEquals(1, $stub->reportsCount);
        $this->assertTrue($stub->isReported());
        $this->assertTrue($stub->isReported);

        $newUserMessage = $this->faker->text;
        $newReportItems = ReportItem::query()
            ->whereNotIn('id', $reportItems->pluck('id')->toArray())
            ->inRandomOrder()
            ->take(3)
            ->get();

        $newReport = $stub->createReport($newReportItems->pluck('id')->toArray(), $newUserMessage);

        $this->assertDatabaseMissing('larareport_reports', ['id' => $report->id]);

        $this->assertDatabaseMissing('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'user_message'    => $userMessage,
            'admin_id'        => null,
            'admin_message'   => null,
            'resolved_at'     => null,
        ]);

        /** @var ReportItem $reportItem */
        foreach ($reportItems as $reportItem) {
            $this->assertDatabaseMissing('larareport_rel_report_report_item', [
                'report_id'      => $report->id,
                'report_item_id' => $reportItem->id,
            ]);
        }

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'user_message'    => $newUserMessage,
            'admin_id'        => null,
            'admin_message'   => null,
            'resolved_at'     => null,
        ]);

        /** @var ReportItem $reportItem */
        foreach ($newReportItems as $reportItem) {
            $this->assertDatabaseHas('larareport_rel_report_report_item', [
                'report_id'      => $newReport->id,
                'report_item_id' => $reportItem->id,
            ]);
        }

        $this->assertEquals(1, $stub->reportsCount());
        $this->assertEquals(1, $stub->reportsCount);
        $this->assertTrue($stub->isReported());
        $this->assertTrue($stub->isReported);
    }

    public function testRecreateReportWithUser()
    {
        $stub = $this->createRandomStub();
        $this->actingAs($this->createRandomUser());

        $reporter = $this->createRandomUser();
        $userMessage = $this->faker->text;
        $reportItems = ReportItem::query()->inRandomOrder()->take(3)->get();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $reporter->id,
            'user_message' => $userMessage,
        ]));
        $report->reportItems()->attach($reportItems->pluck('id')->toArray());

        $this->assertEquals(1, $stub->reportsCount());
        $this->assertEquals(1, $stub->reportsCount);
        $this->assertTrue($stub->isReported($reporter));

        $newUserMessage = $this->faker->text;
        $newReportItems = ReportItem::query()
            ->whereNotIn('id', $reportItems->pluck('id')->toArray())
            ->inRandomOrder()
            ->take(3)
            ->get();

        $newReport = $stub->createReport($newReportItems->pluck('id')->toArray(), $newUserMessage, $reporter);

        $this->assertDatabaseMissing('larareport_reports', ['id' => $report->id]);

        $this->assertDatabaseMissing('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $reporter->id,
            'user_message'    => $userMessage,
            'admin_id'        => null,
            'admin_message'   => null,
            'resolved_at'     => null,
        ]);

        /** @var ReportItem $reportItem */
        foreach ($reportItems as $reportItem) {
            $this->assertDatabaseMissing('larareport_rel_report_report_item', [
                'report_id'      => $report->id,
                'report_item_id' => $reportItem->id,
            ]);
        }

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $reporter->id,
            'user_message'    => $newUserMessage,
            'admin_id'        => null,
            'admin_message'   => null,
            'resolved_at'     => null,
        ]);

        /** @var ReportItem $reportItem */
        foreach ($newReportItems as $reportItem) {
            $this->assertDatabaseHas('larareport_rel_report_report_item', [
                'report_id'      => $newReport->id,
                'report_item_id' => $reportItem->id,
            ]);
        }

        $this->assertEquals(1, $stub->reportsCount());
        $this->assertEquals(1, $stub->reportsCount);
        $this->assertTrue($stub->isReported($reporter));
        $this->assertFalse($stub->isReported());
        $this->assertFalse($stub->isReported);
    }

    public function testAssignReport()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();
        $this->actingAs($user);

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertTrue($report->assign());

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'admin_id'        => $user->id,
        ]);
    }

    public function testAssignReportToUser()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();
        $admin = $this->createRandomUser();
        $this->actingAs($user);

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertTrue($report->assign($admin));

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'admin_id'        => $admin->id,
        ]);
    }

    public function testAssignReportToNotLoggedIn()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertFalse($report->assign());

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'admin_id'        => null,
        ]);
    }

    public function testResolveReport()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();
        $this->actingAs($user);

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertFalse($report->isResolved());

        $this->assertTrue($report->resolve());

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'admin_id'        => $user->id,
        ]);

        $this->assertTrue($report->isResolved());
    }

    public function testResolveReportWithUser()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();
        $admin = $this->createRandomUser();
        $this->actingAs($user);

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertFalse($report->isResolved());

        $this->assertTrue($report->resolve($admin));

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'admin_id'        => $admin->id,
        ]);

        $this->assertTrue($report->isResolved());
    }

    public function testResolveReportNotLoggedIn()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertFalse($report->isResolved());

        $this->assertFalse($report->resolve());

        $this->assertDatabaseHas('larareport_reports', [
            'reportable_type' => $stub->getMorphClass(),
            'reportable_id'   => $stub->id,
            'user_id'         => $user->id,
            'admin_id'        => null,
        ]);

        $this->assertFalse($report->isResolved());
    }

    public function testUserMethodOfReportModel()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertEquals($user->id, $report->user->id);
    }

    public function testAdminMethodOfReportModel()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();
        $admin = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
            'admin_id'     => $admin->id,
        ]));

        $this->assertEquals($admin->id, $report->admin->id);
    }

    public function testAdminMethodOfReportModelWhenAdminIsNull()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertNull($report->admin);
    }

    public function testReportItemsMethodOfReportModel()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $reportItems = ReportItem::query()->inRandomOrder()->take(3)->get();

        $report->reportItems()->attach($reportItems->pluck('id')->toArray());

        foreach ($reportItems as $reportItem) {
            $this->assertDatabaseHas('larareport_rel_report_report_item', [
                'report_id'      => $report->id,
                'report_item_id' => $reportItem->id,
            ]);
        }

        foreach ($report->reportItems as $reportItem) {
            in_array($reportItem->id, $reportItems->pluck('id')->toArray());
        }

        $this->assertEquals($reportItems->count(), $report->reportItems()->count());
    }

    public function testReportItemsMethodOfReportModelWhenNoAttachedReportItemsExists()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        $this->assertDatabaseMissing('larareport_rel_report_report_item', [
            'report_id' => $report->id,
        ]);

        $this->assertEquals(0, $report->reportItems()->count());
    }

    public function TestReportsModelOfReportItemsModel()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        /** @var ReportItem $reportItem */
        $reportItem = ReportItem::query()->inRandomOrder()->first();

        $report->reportItems()->attach($reportItem->id);

        $this->assertEquals(1, $reportItem->reports()->count());
        $this->assertEquals($report->id, $reportItem->reports[0]->id);
    }

    public function TestDeleteModel()
    {
        $stub = $this->createRandomStub();
        $user = $this->createRandomUser();

        /** @var Alert $report */
        $report = $stub->reports()->save(new Alert([
            'user_id'      => $user->id,
            'user_message' => $this->faker->text,
        ]));

        /** @var ReportItem $reportItem */
        $reportItem = ReportItem::query()->inRandomOrder()->first();

        $report->reportItems()->attach($reportItem->id);

        $stub->delete();

        $this->assertDatabaseMissing('larareport_reports', []);
        $this->assertDatabaseMissing('larareport_rel_report_report_item', []);
    }

    public function TestReportsModelOfReportItemsModelWhenNoReportAttached()
    {
        /** @var ReportItem $reportItem */
        $reportItem = ReportItem::query()->inRandomOrder()->first();

        $this->assertEquals(0, $reportItem->reports()->count());
    }
}

class Stub extends Eloquent
{
    use Reportable;

    protected $morphClass = 'Stub';

    protected $connection = 'testbench';

    public $table = 'books';
}
