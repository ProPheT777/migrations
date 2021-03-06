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

namespace Baleen\Migrations\Version\Comparator;

use Baleen\Migrations\Exception\InvalidArgumentException;
use Baleen\Migrations\Exception\Version\ComparatorException;
use Baleen\Migrations\Version\VersionInterface;

/**
 * Takes the version's namespace into account when sorting
 *
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
final class NamespacesAwareComparator extends AbstractComparator
{
    use ComparesLinkedVersionsTrait;

    /** @var array */
    private $namespaces;

    /** @var ComparatorInterface */
    private $fallback;

    /**
     * NamespacesAwareComparator constructor.
     *
     * @param int $order
     * @param ComparatorInterface $fallbackComparator
     * @param array $namespaces Namespaces with keys ordered by priority (highest priority first)
     * @throws ComparatorException
     */
    public function __construct($order, ComparatorInterface $fallbackComparator, array $namespaces)
    {
        $this->fallback = $fallbackComparator;

        if (empty($namespaces)) {
            throw new ComparatorException('Expected at least one namespace for this comparator.');
        }
        // normalize namespaces
        foreach ($namespaces as &$namespace) {
            $namespace = trim($namespace, '\\') . '\\';
        }
        krsort($namespaces); // we search from highest to lowest priority
        $this->namespaces = $namespaces;

        parent::__construct($order);
    }

    /**
     * @inheritDoc
     */
    public function withOrder($order)
    {
        return new static($order, $this->fallback, $this->namespaces);
    }

    /**
     * {@inheritdoc}
     *
     * Given the following $namespaces passed in the constructor:
     *   - Taz (lowest priority)
     *   - Bar
     *   - Foo (highest priority)
     *
     * Will produce the following results based on the migration's FQCN:
     *   - (Foo\v200012, Bar\v201612) => -1
     *   - (Taz\v201612, Foo\v200012) => 1
     *   - (FooBar\v201612, Taz\v200012) => 1
     *   - (Taz\v201612, Taz\v201601) => delegate to fallback
     *   - (FooBar\v201612, FooBar\v200012) => delegate to fallback
     *
     * @param VersionInterface $version1
     * @param VersionInterface $version2
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function compare(VersionInterface $version1, VersionInterface $version2)
    {
        $class1 = $this->getMigrationClass($version1);
        $class2 = $this->getMigrationClass($version2);

        if ($class1 === $class2) {
            // exit early in this case
            return 0;
        }

        $res = $this->compareNamespaces($class1, $class2);

        // null = could not determine order | zero = both orders are equal
        if (empty($res)) {
            // delegate sorting to the fallback comparator
            $res = call_user_func($this->fallback, $version1, $version2);
        }
        return $res;
    }

    /**
     * Compare using namespaces
     *
     * @param $class1
     * @param $class2
     * @return int|null
     */
    private function compareNamespaces($class1, $class2)
    {
        $res = null;
        // loop from highest namespace priority to lowest
        foreach ($this->namespaces as $namespace) {
            if (strpos($class1, $namespace) === 0) {
                $res = 1;
            }
            if (strpos($class2, $namespace) === 0) {
                // subtract 1 from $res, setting it to either -1 or 0
                $res = (int) $res - 1;
            }
            if (null !== $res) {
                break; // exit as soon as we found a sort order
            }
        }
        return $res;
    }
}
