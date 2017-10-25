<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Util;

use Localheinz\GitHub\ChangeLog\Util\Git;
use Localheinz\GitHub\ChangeLog\Util\GitInterface;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class GitTest extends Framework\TestCase
{
    use Helper;

    /**
     * @var string[]
     */
    private $remoteUrls = [];

    protected function tearDown()
    {
        if (!\count($this->remoteUrls)) {
            return;
        }

        foreach ($this->remoteUrls as $remoteName => $remoteUrl) {
            \exec(
                \sprintf(
                    'git remote remove %s',
                    $remoteName
                ),
                $output,
                $returnValue
            );
        }

        unset($this->remoteUrls);
    }

    public function testImplementsGitInterface()
    {
        $this->assertClassImplementsInterface(GitInterface::class, Git::class);
    }

    public function testRemoteUrlsReturnsRemoteUrls()
    {
        \exec(
            'git remote',
            $remoteNames,
            $returnValue
        );

        if (0 !== $returnValue) {
            $this->markTestSkipped('Unable to determine existing git remotes.');
        }

        $faker = $this->faker();

        while (3 > \count($this->remoteUrls)) {
            do {
                $remoteName = $faker->unique()->word;
            } while (\in_array($remoteName, \array_merge($remoteNames, $this->remoteUrls), true));

            $owner = $faker->unique()->word;
            $name = $faker->unique()->word;

            $this->remoteUrls[$remoteName] = \sprintf(
                'git@github.com:%s/%s.git',
                $owner,
                $name
            );
        }

        foreach ($this->remoteUrls as $remoteName => $remoteUrl) {
            \exec(
                \sprintf(
                    'git remote add %s %s',
                    $remoteName,
                    $remoteUrl
                ),
                $output,
                $returnValue
            );

            if (0 !== $returnValue) {
                $this->markTestSkipped(\sprintf(
                    'Unable to add remote "%s" with URL "%s".',
                    $remoteName,
                    $remoteUrl
                ));
            }
        }

        $git = new Git();

        $this->assertArraySubset($this->remoteUrls, $git->remoteUrls());
    }
}