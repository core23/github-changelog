<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Test\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use PHPUnit_Framework_TestCase;
use Refinery29\Test\Util\Faker\GeneratorTrait;

class CommitTest extends PHPUnit_Framework_TestCase
{
    use GeneratorTrait;

    public function testConstructorSetsShaAndMessage()
    {
        $faker = $this->getFaker();

        $sha = $faker->sha1;
        $message = $faker->sentence();

        $entity = new Resource\Commit(
            $sha,
            $message
        );

        $this->assertSame($sha, $entity->sha());
        $this->assertSame($message, $entity->message());
    }
}
