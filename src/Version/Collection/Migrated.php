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

namespace Baleen\Migrations\Version\Collection;

use Baleen\Migrations\Exception\Version\Collection\CollectionException;
use Baleen\Migrations\Version\VersionInterface;

/**
 * Class Migrated
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
class Migrated extends Sortable
{
    /**
     * Returns true if the specified version is valid (can be added) to the collection. Otherwise, it MUST throw
     * an exception.
     *
     * @param VersionInterface $version
     *
     * @return bool
     *
     * @throws CollectionException
     * @throws \Baleen\Migrations\Exception\Version\Collection\AlreadyExistsException
     */
    public function validate(VersionInterface $version)
    {
        if (!$version->isMigrated()) {
            throw new CollectionException(
                'Invalid version specified. This collection only accepts versions that are migrated.'
            );
        }
        return parent::validate($version);
    }
}
