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

namespace Baleen\Migrations\Version\Comparator;

use Baleen\Migrations\Version\VersionInterface;

/**
 * Compares two version with each other.
 *
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
interface ComparatorInterface
{
    /** Compare versions in the normal order */
    const ORDER_NORMAL = 1;

    /** Compare versions in reverse order */
    const ORDER_REVERSE = -1;

    /**
     * Compares two versions with each other. The comparison function must return an integer less than, equal to, or
     * greater than zero if the first argument is considered to be respectively less than, equal to, or greater than the
     * second.
     *
     * @param VersionInterface $version1
     * @param VersionInterface $version2
     *
     * @return int
     */
    public function __invoke(VersionInterface $version1, VersionInterface $version2);

    /**
     * MUST return a new instance that sorts in reverse order.
     *
     * @return ComparatorInterface
     */
    public function reverse();

    /**
     * MUST return a new instance that sorts in the specified order.
     *
     * @param int $order One of ORDER_NORMAL or ORDER_REVERSE.
     *
     * @return static
     */
    public function withOrder($order);
}
