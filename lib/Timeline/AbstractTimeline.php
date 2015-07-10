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
 * <https://github.com/baleen/migrations>.
 */

namespace Baleen\Migrations\Timeline;

use Baleen\Migrations\Event\HasEmitterTrait;
use Baleen\Migrations\Migration\Command\MigrateCommand;
use Baleen\Migrations\Migration\Command\MigrationBusFactory;
use Baleen\Migrations\Migration\Options;
use Baleen\Migrations\Migration\MigrationInterface;
use Baleen\Migrations\Version;
use Baleen\Migrations\Version\Collection;
use Baleen\Migrations\Version\Comparator\DefaultComparator;

/**
 * Encapsulates the lower-level methods of a Timeline, leaving the actual timeline logic to the extending class.
 *
 * @author Gabriel Somoza <gabriel@strategery.io>
 *
 * @method TimelineEmitter getEmitter()
 */
abstract class AbstractTimeline implements TimelineInterface
{
    use HasEmitterTrait;

    /** @var \League\Tactician\CommandBus */
    protected $migrationBus;

    /** @var string[] */
    protected $allowedDirections;

    /** @var Collection */
    protected $versions;

    /** @var callable */
    protected $comparator;

    /**
     * @param array|Collection $versions
     * @param callable         $comparator
     */
    public function __construct($versions, callable $comparator = null)
    {
        $this->migrationBus = MigrationBusFactory::create();

        if (is_array($versions)) {
            $versions = new Collection($versions);
        }
        if (null === $comparator) {
            $comparator = new DefaultComparator();
        }
        $versions->sortWith($comparator);
        $this->comparator = $comparator;
        $this->versions = $versions;
    }

    /**
     * @param Version        $version
     * @param Options $options
     *
     * @return bool
     */
    protected function shouldMigrate(Version $version, Options $options)
    {
        return $options->isForced()
        || ($options->isDirectionUp()   && !$version->isMigrated())
        || ($options->isDirectionDown() &&  $version->isMigrated());
    }

    /**
     * Must create and return a default specialised dispatcher.
     *
     * @return \Baleen\Migrations\Event\EmitterInterface
     */
    protected function createEmitter()
    {
        return new TimelineEmitter();
    }

    /**
     * @param MigrationInterface $migration
     * @param Options     $options
     *
     * @return bool
     */
    protected function doRun(MigrationInterface $migration, Options $options)
    {
        $command = new MigrateCommand($migration, $options);
        $this->migrationBus->handle($command);
    }

    /**
     * @param $goalVersion
     * @param Options $options
     * @param $collection
     *
     * @return Collection
     */
    protected function runCollection($goalVersion, Options $options, Collection $collection)
    {
        $goalVersion = $this->versions->getOrException($goalVersion);

        // dispatch COLLECTION_BEFORE
        $this->getEmitter()->dispatchCollectionBefore($goalVersion, $options, $collection);

        $modified = new Collection();
        foreach ($collection as $version) {
            $result = $this->runSingle($version, $options);
            if ($result) {
                $modified->add($version);
            }
            $goalReached = call_user_func($this->comparator, $goalVersion, $version) === 0;
            if ($goalReached) {
                break;
            }
        }

        // dispatch COLLECTION_AFTER
        $this->getEmitter()->dispatchCollectionAfter($goalVersion, $options, $modified);

        return $modified;
    }

    /**
     * @return Collection
     */
    public function getVersions()
    {
        return $this->versions;
    }
}
