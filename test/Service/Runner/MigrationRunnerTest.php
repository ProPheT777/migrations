<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace BaleenTest\Migrations\Service\Runner;

use Baleen\Migrations\Exception\Service\Runner\RunnerException;
use Baleen\Migrations\Migration\Command\MigrateCommand;
use Baleen\Migrations\Migration\Command\MigrateHandler;
use Baleen\Migrations\Migration\Command\MigrationBus;
use Baleen\Migrations\Migration\Command\MigrationBusInterface;
use Baleen\Migrations\Migration\Options;
use Baleen\Migrations\Migration\Options\Direction;
use Baleen\Migrations\Migration\OptionsInterface;
use Baleen\Migrations\Service\Runner\Event\Migration\MigrateAfterEvent;
use Baleen\Migrations\Service\Runner\Event\Migration\MigrateBeforeEvent;
use Baleen\Migrations\Service\Runner\MigrationRunner;
use Baleen\Migrations\Service\Runner\RunnerInterface;
use Baleen\Migrations\Shared\Event\Context\CollectionContextInterface;
use Baleen\Migrations\Shared\Event\PublisherInterface;
use Baleen\Migrations\Version\Collection\Collection;
use Baleen\Migrations\Version\VersionInterface;
use Mockery as m;

/**
 * Class MigrationRunnerTest
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
class MigrationRunnerTest extends RunnerTestCase
{
    /**
     * testConstructor
     */
    public function testConstructor()
    {
        $runner = $this->createMigrationRunner();
        $this->assertInstanceOf(RunnerInterface::class, $runner);
    }

    /**
     * testConstructorWithNoPublisher
     * @return void
     */
    public function testConstructorPublisherIsOptional()
    {
        /** @var MigrationBusInterface|m\Mock $bus */
        $bus = m::mock(MigrationBusInterface::class);
        new MigrationRunner($bus); // should not blow up when publisher is not specified
    }

    /**
     * testConstructorWithNoPublisher
     * @return void
     */
    public function testConstructorBusIsOptional()
    {
        /** @var PublisherInterface|m\Mock $publisher */
        $publisher = m::mock(PublisherInterface::class);
        new MigrationRunner(null, $publisher); // should not blow up when bus is not specified
    }

    /**
     * @param $id
     * @param OptionsInterface $options
     * @param $expectation
     *
     * @throws RunnerException
     *
     * @dataProvider runSingleProvider
     */
    public function testRun($id, OptionsInterface $options, $expectation)
    {
        $collection = new Collection($this->getMixedVersionsFixture());

        /** @var MigrationBusInterface|m\Mock $bus */
        $bus = m::mock(MigrationBusInterface::class);

        /** @var PublisherInterface|m\Mock $publisher */
        $publisher = m::mock(PublisherInterface::class);
        $publisher->shouldReceive('publish')->zeroOrMoreTimes();

        /** @var CollectionContextInterface|m\Mock $context */
        $context = m::mock(CollectionContextInterface::class);
        $runner = new MigrationRunner($bus, $publisher, $context);

        /** @var VersionInterface $version */
        $version = $collection->find($id);

        $bus->shouldReceive('handle')->zeroOrMoreTimes()->with(MigrateCommand::class)->andReturn($version);

        if ($expectation == 'exception') {
            $this->setExpectedException(RunnerException::class);
        }

        $result = $runner->run($version, $options);

        if ($expectation == 'skip') {
            $this->assertFalse($result, 'Expected runSingle() to return false when skipping without exception.');
        } elseif ($expectation !== 'exception') {
            // IMPROVE: this makes sure the events were fired, but doesn't check their order is correct
            $publisher->shouldHaveReceived('publish')->with(m::type(MigrateAfterEvent::class));
            $publisher->shouldHaveReceived('publish')->with(m::type(MigrateBeforeEvent::class));

            $bus->shouldHaveReceived('handle')->once();
            $this->assertTrue($version->isMigrated() == $options->getDirection()->isUp());
            $this->assertSame($version, $result);
        }
    }

    /**
     * runSingleProvider
     * @return array
     */
    public function runSingleProvider()
    {
        return [
            ['v01', new Options(), 'exception' ], // its already up
            ['v01', new Options(Direction::down()), Direction::down()],
            ['v02', new Options(), Direction::up()],
            ['v02', new Options(Direction::down()), 'exception' ], // its already down
            ['v02', new Options(Direction::down(), false, false, false), 'skip' ], // skip without exception
        ];
    }

    /**
     * createMigrationRunner
     * @param PublisherInterface|null $publisher
     * @param MigrationBus|null $migrationBus
     * @return MigrationRunner
     */
    private function createMigrationRunner(PublisherInterface $publisher = null, MigrationBus $migrationBus = null)
    {
        if (null === $publisher) {
            /** @var PublisherInterface|m\Mock $publisher */
            $publisher = m::mock(PublisherInterface::class);
        }
        if (null === $migrationBus) {
            $migrationBus = new MigrationBus([new MigrateHandler()]);
        }
        return new MigrationRunner($migrationBus, $publisher);
    }
}