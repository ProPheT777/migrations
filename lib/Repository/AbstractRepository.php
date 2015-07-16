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

namespace Baleen\Migrations\Repository;

use Baleen\Migrations\Exception\RepositoryException;
use Baleen\Migrations\Migration\Factory\FactoryInterface;
use Baleen\Migrations\Version\Collection\LinkedVersions;

/**
 * Class AbstractRepository
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
abstract class AbstractRepository implements RepositoryInterface
{

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @inheritdoc
     */
    public function setMigrationFactory(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     * @return LinkedVersions
     * @throws RepositoryException
     */
    public function fetchAll()
    {
        $result = $this->doFetchAll();
        if (!is_object($result) || !$result instanceof LinkedVersions) {
            throw new RepositoryException(
                'Method AbstractRepository::doFetchAll() must return a LinkedVersions collection.'
            );
        }
        return $result;
    }

    /**
     * Must fetch all versions available to the repository, load them with their migrations, and return them as a
     * LinkedVersions collection. It must use a factory (default or supplied by 'setMigrationFactory()') to instantiate
     * each of the migrations.
     *
     * @return mixed
     */
    abstract protected function doFetchAll();
}